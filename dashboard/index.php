<?php
// dashboard/index.php - Enhanced Medical Dashboard
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/enhanced-db-functions.php';

// ตรวจสอบการล็อกอิน (session_start() ถูกเรียกใน config.php แล้ว)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: ../auth/login.php?error=please_login");
    exit;
}

// ตั้งค่าสำหรับ enhanced-header.php
$assets_path = '../assets/';
$page_title = 'Medical Dashboard - Overview';
$extra_scripts_head = ['https://cdn.jsdelivr.net/npm/chart.js'];
$extra_css = ['../assets/css/dashboard.css'];
$extra_js = [];

// เริ่มต้นระบบฐานข้อมูล
try {
    $enhancedDB = getEnhancedDB();
    $dbManager = new DatabaseManager($conn); // สำหรับ direct DB operations
} catch (Exception $e) {
    error_log("Enhanced DB initialization error: " . $e->getMessage());
    $dbManager = new DatabaseManager($conn);
}

try {
    // ดึงข้อมูลผู้ใช้ด้วยความปลอดภัยสูง
    $user_id = $_SESSION['user_id'];
    
    $user = $dbManager->getRow(
        "SELECT username, email, role, created_at, last_login FROM users WHERE id = ? AND status = 'active'", 
        [$user_id], 
        'i'
    );
    
    if (!$user) {
        // บันทึก log การเข้าถึงที่ไม่ถูกต้อง (ถ้ามี SecurityManager)
        if (class_exists('SecurityManager')) {
            SecurityManager::logSecurityEvent('invalid_user_access', ['user_id' => $user_id], 'WARNING');
        }
        session_destroy();
        header("Location: ../auth/login.php?error=invalid_session");
        exit;
    }
    
    $isAdmin = ($user['role'] === 'admin');
    
    // ดึงสถิติด้วยระบบใหม่ที่มี Caching
    if (method_exists($enhancedDB, 'getDashboardStats')) {
        $stats = $enhancedDB->getDashboardStats();
    } else {
        // ดึงสถิติแบบพื้นฐาน
        $stats = [];
        
        // นับจำนวน patients
        $result = $dbManager->getRow("SELECT COUNT(*) as total FROM patients");
        $stats['patients']['total'] = $result['total'] ?? 0;
        
        $result = $dbManager->getRow("SELECT COUNT(*) as count FROM patients WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        $stats['patients']['new_this_month'] = $result['count'] ?? 0;
        
        // นับจำนวน records
        $result = $dbManager->getRow("SELECT COUNT(*) as total FROM medical_records");
        $stats['records']['total'] = $result['total'] ?? 0;
        
        $result = $dbManager->getRow("SELECT COUNT(*) as count FROM medical_records WHERE MONTH(visit_date) = MONTH(CURRENT_DATE()) AND YEAR(visit_date) = YEAR(CURRENT_DATE())");
        $stats['records']['this_month'] = $result['count'] ?? 0;
        
        // นับจำนวน doctors
        $result = $dbManager->getRow("SELECT COUNT(*) as total FROM doctors");
        $stats['doctors']['total'] = $result['total'] ?? 0;
        
        // นับจำนวน appointments
        $result = $dbManager->getRow("SELECT COUNT(*) as count FROM medical_records WHERE next_appointment > NOW()");
        $stats['appointments']['upcoming'] = $result['count'] ?? 0;
        
        $result = $dbManager->getRow("SELECT COUNT(*) as count FROM medical_records WHERE DATE(next_appointment) = CURDATE()");
        $stats['appointments']['today'] = $result['count'] ?? 0;
    }
    
    // ดึงข้อมูลล่าสุดด้วย Security และ Performance ที่ดีขึ้น
    $recent_patients = $dbManager->getRows(
        "SELECT id, first_name, last_name, age, phone, created_at, gender 
         FROM patients 
         ORDER BY created_at DESC 
         LIMIT 6"
    );
    
    $recent_records = $dbManager->getRows(
        "SELECT mr.id, mr.diagnosis, mr.visit_date, mr.treatment, p.first_name, p.last_name, p.id as patient_id
         FROM medical_records mr 
         JOIN patients p ON mr.patient_id = p.id 
         ORDER BY mr.visit_date DESC 
         LIMIT 6"
    );
    
    $upcoming_appointments = $dbManager->getRows(
        "SELECT mr.id, mr.next_appointment, mr.diagnosis, mr.notes, p.first_name, p.last_name, p.id as patient_id
         FROM medical_records mr 
         JOIN patients p ON mr.patient_id = p.id 
         WHERE mr.next_appointment > NOW() 
         ORDER BY mr.next_appointment 
         LIMIT 6"
    );
    
    // สถิติขั้นสูงสำหรับ Charts
    $daily_patients = $dbManager->getRows(
        "SELECT DATE(created_at) as date, COUNT(*) as count
         FROM patients 
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY DATE(created_at)
         ORDER BY date"
    );
    
    $monthly_patients = $dbManager->getRows(
        "SELECT YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count
         FROM patients 
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
         GROUP BY YEAR(created_at), MONTH(created_at)
         ORDER BY year, month"
    );
    
    // สถิติเพิ่มเติมสำหรับ Dashboard
    $today_appointments = $dbManager->getRow(
        "SELECT COUNT(*) as count 
         FROM medical_records 
         WHERE DATE(next_appointment) = CURDATE()"
    )['count'] ?? 0;
    
    $week_appointments = $dbManager->getRow(
        "SELECT COUNT(*) as count 
         FROM medical_records 
         WHERE next_appointment BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)"
    )['count'] ?? 0;
    
    // บันทึกการเข้าถึง Dashboard (ถ้ามี SecurityManager)
    if (class_exists('SecurityManager')) {
        SecurityManager::logSecurityEvent('dashboard_access', [
            'user_role' => $user['role'],
            'stats_loaded' => true
        ]);
    }
    
} catch (Exception $e) {
    // จัดการ Error อย่างปลอดภัย
    error_log("Dashboard Error: " . $e->getMessage());
    
    // บันทึก error log (ถ้ามี SecurityManager)
    if (class_exists('SecurityManager')) {
        SecurityManager::logSecurityEvent('dashboard_error', [
            'error' => $e->getMessage(),
            'user_id' => $user_id ?? 'unknown'
        ], 'ERROR');
    }
    
    // Fallback ข้อมูลเบื้องต้น
    $stats = ['patients' => ['total' => 0, 'new_this_month' => 0], 'records' => ['total' => 0, 'this_month' => 0], 'doctors' => ['total' => 0], 'appointments' => ['upcoming' => 0, 'today' => 0]];
    $recent_patients = $recent_records = $upcoming_appointments = [];
    $daily_patients = $monthly_patients = [];
    $today_appointments = $week_appointments = 0;
    
    // ตั้งข้อมูล user เบื้องต้น
    if (!isset($user)) {
        $user = [
            'username' => $_SESSION['username'] ?? 'Unknown User',
            'email' => 'N/A',
            'role' => 'user',
            'last_login' => null
        ];
        $isAdmin = false;
    }
}

// Include Enhanced Header (รวม Sidebar แล้ว)
include '../includes/enhanced-header.php';
?>

<!-- Enhanced Page Content -->
<div class="container-xxl flex-grow-1 container-p-y">
    
    <!-- Enhanced Welcome Banner -->
    <div class="welcome-banner mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="welcome-content">
                    <h1 class="welcome-title mb-2">
                        Welcome back, <?php echo htmlspecialchars($user['username']); ?>! 👋
                    </h1>
                    <p class="welcome-subtitle mb-3">
                        Here's what's happening in your medical practice today.
                    </p>
                    <div class="welcome-stats d-flex flex-wrap gap-3">
                        <div class="stat-item">
                            <i class="bx bx-calendar-check text-white-50"></i>
                            <span><?php echo $today_appointments; ?> appointments today</span>
                        </div>
                        <div class="stat-item">
                            <i class="bx bx-time text-white-50"></i>
                            <span><?php echo $week_appointments; ?> this week</span>
                        </div>
                        <div class="stat-item">
                            <i class="bx bx-shield-check text-white-50"></i>
                            <span><?php echo ucfirst($user['role']); ?> Access</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <div class="welcome-avatar">
                    <div class="avatar-container">
                        <img src="<?php echo $assets_path; ?>img/avatars/1.png" alt="Avatar" class="avatar-img">
                        <div class="avatar-status <?php echo $isAdmin ? 'admin' : 'user'; ?>"></div>
                    </div>
                    <div class="avatar-info mt-3">
                        <h6 class="text-white mb-1"><?php echo htmlspecialchars($user['email']); ?></h6>
                        <small class="text-white-50">
                            Last login: <?php echo $user['last_login'] ? date('M j, H:i', strtotime($user['last_login'])) : 'First time'; ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Statistics Cards -->
    <div class="row mb-5">
        <?php 
        $stat_items = [
            [
                'title' => 'Total Patients', 
                'value' => $stats['patients']['total'] ?? 0, 
                'change' => $stats['patients']['new_this_month'] ?? 0,
                'icon' => 'bx-group', 
                'color' => 'linear-gradient(135deg, #28a745, #20c997)', 
                'desc' => 'Registered patients',
                'link' => '../patients/patients.php'
            ],
            [
                'title' => 'Medical Records', 
                'value' => $stats['records']['total'] ?? 0, 
                'change' => $stats['records']['this_month'] ?? 0,
                'icon' => 'bx-file-blank', 
                'color' => 'linear-gradient(135deg, #007bff, #0056b3)', 
                'desc' => 'Total records',
                'link' => '../medical_records/medical_record_action.php'
            ],
            [
                'title' => 'Total Doctors', 
                'value' => $stats['doctors']['total'] ?? 0, 
                'change' => '+2',
                'icon' => 'bx-user-check', 
                'color' => 'linear-gradient(135deg, #ffc107, #e0a800)', 
                'desc' => 'Registered doctors',
                'link' => '../doctors/doctors_action.php'
            ],
            [
                'title' => 'Upcoming Visits', 
                'value' => $stats['appointments']['upcoming'] ?? 0, 
                'change' => $stats['appointments']['today'] ?? 0,
                'icon' => 'bx-calendar-check', 
                'color' => 'linear-gradient(135deg, #17a2b8, #138496)', 
                'desc' => 'Scheduled appointments',
                'link' => '../appointments/appointments.php'
            ]
        ];
        
        foreach ($stat_items as $index => $item): ?>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="dashboard-stats-card" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                <a href="<?php echo $item['link']; ?>" class="text-decoration-none">
                    <div class="card-body text-center py-4">
                        <div class="stats-icon-large mb-3" style="background: <?php echo $item['color']; ?>">
                            <i class="bx <?php echo $item['icon']; ?>"></i>
                        </div>
                        <div class="stats-number-large" data-target="<?php echo $item['value']; ?>">0</div>
                        <h5 class="card-title mb-1"><?php echo $item['title']; ?></h5>
                        <p class="text-muted small mb-2"><?php echo $item['desc']; ?></p>
                        <?php if ($item['change']): ?>
                            <div class="stats-change">
                                <span class="badge bg-success">
                                    <i class="bx bx-trending-up"></i> +<?php echo $item['change']; ?> this month
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Quick Actions Bar -->
    <div class="quick-actions-bar mb-4">
        <div class="card">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h6 class="mb-0">
                            <i class="bx bx-zap text-primary me-2"></i>
                            Quick Actions
                        </h6>
                    </div>
                    <div class="col-md-6">
                        <div class="btn-group" role="group">
                            <a href="../patients/patients_action.php?action=add" class="btn btn-primary btn-sm">
                                <i class="bx bx-user-plus me-1"></i>Add Patient
                            </a>
                            <a href="../medical_records/medical_record_action.php?action=add" class="btn btn-success btn-sm">
                                <i class="bx bx-file-plus me-1"></i>New Record
                            </a>
                            <?php if ($isAdmin): ?>
                            <a href="../doctors/doctors_action.php?action=add" class="btn btn-warning btn-sm">
                                <i class="bx bx-user-check me-1"></i>Add Doctor
                            </a>
                            <?php endif; ?>
                            <button type="button" class="btn btn-info btn-sm" onclick="showGlobalSearch()">
                                <i class="bx bx-search me-1"></i>Search
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Activity Section -->
    <div class="row mb-5">
        <!-- Recent Patients -->
        <div class="col-lg-4 mb-4">
            <div class="activity-card" data-aos="fade-right">
                <div class="activity-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="bx bx-user-plus me-2 text-primary"></i>
                            Recent Patients
                        </h6>
                        <div class="header-actions">
                            <span class="badge bg-primary"><?php echo count($recent_patients); ?></span>
                            <a href="../patients/patients.php" class="btn btn-sm btn-primary ms-2">View All</a>
                        </div>
                    </div>
                </div>
                <div class="activity-body">
                    <?php if (empty($recent_patients)): ?>
                        <div class="empty-state">
                            <i class="bx bx-user-plus display-2 text-muted"></i>
                            <h6 class="mt-3 text-muted">No patients yet</h6>
                            <p class="text-muted small">Start by adding your first patient</p>
                            <a href="../patients/patients_action.php?action=add" class="btn btn-primary btn-sm">
                                <i class="bx bx-plus me-1"></i>Add Patient
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_patients as $patient): ?>
                        <div class="activity-item-enhanced" data-patient-id="<?php echo $patient['id']; ?>">
                            <div class="d-flex align-items-center">
                                <div class="patient-avatar-large">
                                    <?php echo strtoupper(substr($patient['first_name'], 0, 1) . substr($patient['last_name'], 0, 1)); ?>
                                </div>
                                <div class="flex-grow-1">
                                    <a href="../patients/patient_view.php?id=<?php echo $patient['id']; ?>" class="text-decoration-none">
                                        <h6 class="mb-1 fw-bold">
                                            <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                        </h6>
                                    </a>
                                    <div class="patient-info">
                                        <small class="text-muted">
                                            <i class="bx bx-calendar me-1"></i>Age: <?php echo $patient['age']; ?>
                                            <?php if (isset($patient['gender'])): ?>
                                                • <?php echo $patient['gender']; ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <small class="text-muted">
                                            <i class="bx bx-phone me-1"></i><?php echo htmlspecialchars($patient['phone']); ?>
                                        </small>
                                        <span class="badge bg-light text-dark">
                                            <?php echo date('M j', strtotime($patient['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Medical Records -->
        <div class="col-lg-4 mb-4">
            <div class="activity-card" data-aos="fade-up">
                <div class="activity-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="bx bx-file me-2 text-success"></i>
                            Recent Records
                        </h6>
                        <div class="header-actions">
                            <span class="badge bg-success"><?php echo count($recent_records); ?></span>
                            <a href="../medical_records/medical_record_action.php" class="btn btn-sm btn-success ms-2">View All</a>
                        </div>
                    </div>
                </div>
                <div class="activity-body">
                    <?php if (empty($recent_records)): ?>
                        <div class="empty-state">
                            <i class="bx bx-file display-2 text-muted"></i>
                            <h6 class="mt-3 text-muted">No records yet</h6>
                            <p class="text-muted small">Start by creating medical records</p>
                            <a href="../medical_records/medical_record_action.php?action=add" class="btn btn-success btn-sm">
                                <i class="bx bx-plus me-1"></i>New Record
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_records as $record): ?>
                        <div class="activity-item-enhanced" data-record-id="<?php echo $record['id']; ?>">
                            <div class="d-flex align-items-center">
                                <div class="patient-avatar-large" style="background: linear-gradient(135deg, #28a745, #20c997);">
                                    <?php echo strtoupper(substr($record['first_name'], 0, 1) . substr($record['last_name'], 0, 1)); ?>
                                </div>
                                <div class="flex-grow-1">
                                    <a href="../medical_records/medical_record_view.php?id=<?php echo $record['id']; ?>" class="text-decoration-none">
                                        <h6 class="mb-1 fw-bold">
                                            <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?>
                                        </h6>
                                    </a>
                                    <div class="record-info">
                                        <small class="text-muted">
                                            <i class="bx bx-health me-1"></i>
                                            <?php echo htmlspecialchars(substr($record['diagnosis'], 0, 25)); ?>
                                            <?php echo strlen($record['diagnosis']) > 25 ? '...' : ''; ?>
                                        </small>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <?php if ($record['treatment']): ?>
                                            <small class="text-info">
                                                <i class="bx bx-plus-medical me-1"></i>
                                                <?php echo htmlspecialchars(substr($record['treatment'], 0, 20)); ?>...
                                            </small>
                                        <?php endif; ?>
                                        <span class="badge bg-light text-dark">
                                            <?php echo date('M j', strtotime($record['visit_date'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Upcoming Appointments -->
        <div class="col-lg-4 mb-4">
            <div class="activity-card" data-aos="fade-left">
                <div class="activity-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="bx bx-calendar me-2 text-info"></i>
                            Upcoming Appointments
                        </h6>
                        <div class="header-actions">
                            <span class="badge bg-info"><?php echo count($upcoming_appointments); ?></span>
                            <?php if ($week_appointments > 0): ?>
                                <span class="badge bg-warning ms-1 animate-pulse"><?php echo $week_appointments; ?> this week</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="activity-body">
                    <?php if (empty($upcoming_appointments)): ?>
                        <div class="empty-state">
                            <i class="bx bx-calendar-check display-2 text-muted"></i>
                            <h6 class="mt-3 text-muted">No upcoming appointments</h6>
                            <p class="text-muted small">Schedule your first appointment</p>
                            <a href="../appointments/appointments_action.php?action=add" class="btn btn-info btn-sm">
                                <i class="bx bx-plus me-1"></i>Schedule
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($upcoming_appointments as $appointment): ?>
                        <div class="activity-item-enhanced" data-appointment-id="<?php echo $appointment['id']; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-center flex-grow-1">
                                    <div class="patient-avatar-large" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                                        <?php echo strtoupper(substr($appointment['first_name'], 0, 1) . substr($appointment['last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 fw-bold">
                                            <?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?>
                                        </h6>
                                        <small class="text-muted d-block">
                                            <i class="bx bx-health me-1"></i>
                                            <?php echo htmlspecialchars(substr($appointment['diagnosis'], 0, 20)); ?>
                                            <?php echo strlen($appointment['diagnosis']) > 20 ? '...' : ''; ?>
                                        </small>
                                        <?php if ($appointment['notes']): ?>
                                            <small class="text-info d-block">
                                                <i class="bx bx-note me-1"></i>
                                                <?php echo htmlspecialchars(substr($appointment['notes'], 0, 25)); ?>...
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="appointment-badge">
                                        <?php echo date('M j', strtotime($appointment['next_appointment'])); ?>
                                    </span>
                                    <div class="mt-1">
                                        <small class="text-success">
                                            <span class="upcoming-indicator"></span>
                                            <?php echo date('H:i', strtotime($appointment['next_appointment'])); ?>
                                        </small>
                                    </div>
                                    <?php 
                                    $appointmentDate = strtotime($appointment['next_appointment']);
                                    $today = strtotime('today');
                                    $daysDiff = floor(($appointmentDate - $today) / (60 * 60 * 24));
                                    ?>
                                    <?php if ($daysDiff == 0): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-danger">Today</span>
                                        </div>
                                    <?php elseif ($daysDiff == 1): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-warning">Tomorrow</span>
                                        </div>
                                    <?php elseif ($daysDiff <= 7): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-info"><?php echo $daysDiff; ?> days</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Charts Section -->
    <div class="row mb-5">
        <!-- Daily Patients Chart -->
        <div class="col-lg-6 mb-4">
            <div class="chart-container" data-aos="fade-up">
                <div class="chart-header">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="mb-1">
                                <i class="bx bx-trending-up me-2 text-primary"></i>
                                Daily Patient Registrations
                            </h5>
                            <small class="text-muted">Patient registration trends over the last 30 days</small>
                        </div>
                        <div class="chart-controls">
                            <span class="badge bg-primary">Last 30 Days</span>
                            <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="refreshChart('daily')">
                                <i class="bx bx-refresh"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas id="dailyPatientsChart" style="max-height: 350px;"></canvas>
                </div>
                <div class="chart-footer mt-3">
                    <div class="row text-center">
                        <div class="col-4">
                            <small class="text-muted d-block">Average/Day</small>
                            <strong id="dailyAverage">-</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Peak Day</small>
                            <strong id="peakDay">-</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Total</small>
                            <strong id="totalDaily">-</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Patients Chart -->
        <div class="col-lg-6 mb-4">
            <div class="chart-container" data-aos="fade-up" data-aos-delay="100">
                <div class="chart-header">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="mb-1">
                                <i class="bx bx-bar-chart me-2 text-success"></i>
                                Monthly Patient Registrations
                            </h5>
                            <small class="text-muted">Monthly growth patterns over the last year</small>
                        </div>
                        <div class="chart-controls">
                            <span class="badge bg-success">Last 12 Months</span>
                            <button type="button" class="btn btn-sm btn-outline-success ms-2" onclick="refreshChart('monthly')">
                                <i class="bx bx-refresh"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas id="monthlyPatientsChart" style="max-height: 350px;"></canvas>
                </div>
                <div class="chart-footer mt-3">
                    <div class="row text-center">
                        <div class="col-4">
                            <small class="text-muted d-block">Average/Month</small>
                            <strong id="monthlyAverage">-</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Best Month</small>
                            <strong id="bestMonth">-</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Growth Rate</small>
                            <strong id="growthRate" class="text-success">-</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced System Status -->
    <div class="system-status" data-aos="fade-up">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">
                <i class="bx bx-cog me-2 text-secondary"></i>
                System Status & Health
            </h5>
            <div class="status-controls">
                <span class="badge bg-success">All Systems Operational</span>
                <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="checkSystemHealth()">
                    <i class="bx bx-refresh"></i> Check Health
                </button>
            </div>
        </div>
        <div class="row">
            <?php 
            $status_items = [
                ['name' => 'Database', 'status' => 'online', 'uptime' => '99.9%'],
                ['name' => 'API Services', 'status' => 'online', 'uptime' => '99.8%'],
                ['name' => 'Backup System', 'status' => 'online', 'uptime' => '100%'],
                ['name' => 'Security', 'status' => 'online', 'uptime' => '100%']
            ];
            
            foreach ($status_items as $item): ?>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="system-status-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1"><?php echo $item['name']; ?></h6>
                            <small class="text-muted">Uptime: <?php echo $item['uptime']; ?></small>
                        </div>
                        <div class="status-indicator <?php echo $item['status']; ?>"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<!-- Enhanced JavaScript -->
<script>
// Initialize Dashboard
document.addEventListener('DOMContentLoaded', function() {
    initializeCounters();
    initializeCharts();
    initializeRealTimeUpdates();
    initializeInteractiveElements();
});

// Counter Animation
function initializeCounters() {
    const counters = document.querySelectorAll('.stats-number-large');
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'));
        const increment = target / 100;
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            counter.textContent = Math.floor(current);
            
            if (current >= target) {
                counter.textContent = target;
                clearInterval(timer);
            }
        }, 20);
    });
}

// Charts Initialization
function initializeCharts() {
    // Daily Patients Chart
    const dailyCtx = document.getElementById('dailyPatientsChart');
    if (dailyCtx) {
        const dailyData = <?php echo json_encode($daily_patients); ?>;
        
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: dailyData.map(item => new Date(item.date).toLocaleDateString('en-US', {month: 'short', day: 'numeric'})),
                datasets: [{
                    label: 'Daily Registrations',
                    data: dailyData.map(item => item.count),
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        // Calculate and display daily statistics
        const totalDaily = dailyData.reduce((sum, item) => sum + parseInt(item.count), 0);
        const avgDaily = Math.round(totalDaily / dailyData.length);
        const peakDay = Math.max(...dailyData.map(item => parseInt(item.count)));
        
        document.getElementById('dailyAverage').textContent = avgDaily;
        document.getElementById('peakDay').textContent = peakDay;
        document.getElementById('totalDaily').textContent = totalDaily;
    }
    
    // Monthly Patients Chart
    const monthlyCtx = document.getElementById('monthlyPatientsChart');
    if (monthlyCtx) {
        const monthlyData = <?php echo json_encode($monthly_patients); ?>;
        
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: monthlyData.map(item => {
                    const date = new Date(item.year, item.month - 1);
                    return date.toLocaleDateString('en-US', {month: 'short', year: 'numeric'});
                }),
                datasets: [{
                    label: 'Monthly Registrations',
                    data: monthlyData.map(item => item.count),
                    backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    borderColor: '#28a745',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        // Calculate and display monthly statistics
        const totalMonthly = monthlyData.reduce((sum, item) => sum + parseInt(item.count), 0);
        const avgMonthly = Math.round(totalMonthly / monthlyData.length);
        const bestMonth = Math.max(...monthlyData.map(item => parseInt(item.count)));
        
        // Calculate growth rate
        let growthRate = 0;
        if (monthlyData.length >= 2) {
            const lastMonth = monthlyData[monthlyData.length - 1].count;
            const prevMonth = monthlyData[monthlyData.length - 2].count;
            growthRate = Math.round(((lastMonth - prevMonth) / prevMonth) * 100);
        }
        
        document.getElementById('monthlyAverage').textContent = avgMonthly;
        document.getElementById('bestMonth').textContent = bestMonth;
        document.getElementById('growthRate').textContent = (growthRate > 0 ? '+' : '') + growthRate + '%';
    }
}

// Real-time Updates
function initializeRealTimeUpdates() {
    // Update dashboard stats every 5 minutes
    setInterval(function() {
        updateDashboardStats();
    }, 300000);
}

// Interactive Elements
function initializeInteractiveElements() {
    // Add click effects to cards
    document.querySelectorAll('.dashboard-stats-card').forEach(card => {
        card.addEventListener('click', function() {
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 100);
        });
    });
    
    // Add hover effects to activity items
    document.querySelectorAll('.activity-item-enhanced').forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8f9fa';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.backgroundColor = 'transparent';
        });
    });
}

// Dashboard Functions
function updateDashboardStats() {
    fetch('dashboard-stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update counters with new data
                Object.keys(data.stats).forEach(key => {
                    const element = document.querySelector(`[data-stat="${key}"]`);
                    if (element) {
                        element.textContent = data.stats[key];
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error updating stats:', error);
        });
}

function refreshChart(type) {
    // Add refresh functionality for charts
    console.log('Refreshing ' + type + ' chart...');
    // Implementation would reload chart data
}

function checkSystemHealth() {
    // Add system health check functionality
    console.log('Checking system health...');
    // Implementation would check system status
}

function showGlobalSearch() {
    // Add global search functionality
    console.log('Opening global search...');
    // Implementation would show search modal
}

// AOS Animation Library (if included)
if (typeof AOS !== 'undefined') {
    AOS.init({
        duration: 1000,
        once: true
    });
}
</script>

<!-- Additional CSS for enhanced styling -->
<style>
.welcome-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    padding: 2rem;
    color: white;
    margin-bottom: 2rem;
}

.dashboard-stats-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    overflow: hidden;
}

.dashboard-stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.stats-icon-large {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.stats-icon-large i {
    font-size: 24px;
    color: white;
}

.stats-number-large {
    font-size: 2.5rem;
    font-weight: bold;
    color: #2c3e50;
}

.activity-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 1.5rem;
    height: 100%;
}

.activity-header {
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 1rem;
    margin-bottom: 1.5rem;
}

.activity-item-enhanced {
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    border: 1px solid transparent;
}

.activity-item-enhanced:hover {
    background-color: #f8f9fa !important;
    border-color: #dee2e6;
    transform: translateX(5px);
}

.patient-avatar-large {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 1rem;
    font-size: 0.9rem;
}

.chart-container {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.system-status {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.system-status-item {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1rem;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #28a745;
}

.status-indicator.online {
    background: #28a745;
    box-shadow: 0 0 10px rgba(40, 167, 69, 0.3);
}

.appointment-badge {
    background: linear-gradient(135deg, #17a2b8, #138496);
    color: white;
    padding: 4px 8px;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 500;
}

.upcoming-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #28a745;
    margin-right: 5px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.animate-pulse {
    animation: pulse 2s infinite;
}

.empty-state {
    text-align: center;
    padding: 2rem 1rem;
}

.quick-actions-bar .card {
    border: none;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
}

@media (max-width: 768px) {
    .welcome-banner {
        text-align: center;
    }
    
    .welcome-banner .col-md-4 {
        margin-top: 1rem;
    }
    
    .stats-number-large {
        font-size: 2rem;
    }
    
    .activity-card {
        margin-bottom: 1rem;
    }
}
</style>

<?php include '../includes/enhanced-footer.php'; ?>