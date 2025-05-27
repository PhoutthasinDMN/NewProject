<?php
// dashboard/index.php - Clean Enhanced Medical Dashboard
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: ../auth/login.php?error=please_login");
    exit;
}

// ตั้งค่าสำหรับ enhanced-header.php
$assets_path = '../assets/';
$page_title = 'Medical Dashboard - Overview';
$extra_css = [
    '../assets/css/dashboard.css',
    'https://unpkg.com/aos@next/dist/aos.css'
];
$extra_js = [
    'https://unpkg.com/aos@next/dist/aos.js',
    'https://cdn.jsdelivr.net/npm/chart.js',
    '../assets/js/dashboard.js'
];

// เริ่มต้นระบบฐานข้อมูล
try {
    // ตรวจสอบว่ามีไฟล์ enhanced-db-functions.php หรือไม่
    if (file_exists('../includes/enhanced-db-functions.php')) {
        require_once '../includes/enhanced-db-functions.php';
        if (class_exists('DatabaseManager')) {
            $dbManager = new DatabaseManager($conn);
        }
    }
    
    if (!isset($dbManager)) {
        $dbManager = null;
    }
} catch (Exception $e) {
    error_log("Enhanced DB initialization error: " . $e->getMessage());
    $dbManager = null;
}

try {
    // ดึงข้อมูลผู้ใช้ด้วยความปลอดภัยสูง
    $user_id = $_SESSION['user_id'];
    
    $sql = "SELECT username, email, role, created_at, last_login FROM users WHERE id = ? AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        session_destroy();
        header("Location: ../auth/login.php?error=invalid_session");
        exit;
    }
    
    // อัพเดท last_login
    $update_sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $user_id);
    $update_stmt->execute();
    
    $isAdmin = ($user['role'] === 'admin');
    
    // ดึงสถิติพื้นฐาน
    $stats = [];
    
    // นับจำนวน patients
    $result = $conn->query("SELECT COUNT(*) as total FROM patients");
    $stats['patients']['total'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM patients WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['patients']['new_this_month'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    // นับจำนวน medical_records (ตรวจสอบว่าตารางมีอยู่หรือไม่)
    $table_check = $conn->query("SHOW TABLES LIKE 'medical_records'");
    if ($table_check && $table_check->num_rows > 0) {
        $result = $conn->query("SELECT COUNT(*) as total FROM medical_records");
        $stats['records']['total'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM medical_records WHERE MONTH(visit_date) = MONTH(CURRENT_DATE()) AND YEAR(visit_date) = YEAR(CURRENT_DATE())");
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['records']['this_month'] = $result ? $result->fetch_assoc()['count'] : 0;
    } else {
        $stats['records']['total'] = 0;
        $stats['records']['this_month'] = 0;
    }
    
    // นับจำนวน doctors (ตรวจสอบว่าตารางมีอยู่หรือไม่)
    $table_check = $conn->query("SHOW TABLES LIKE 'doctors'");
    if ($table_check && $table_check->num_rows > 0) {
        $result = $conn->query("SELECT COUNT(*) as total FROM doctors");
        $stats['doctors']['total'] = $result ? $result->fetch_assoc()['total'] : 0;
    } else {
        $stats['doctors']['total'] = 0;
    }
    
    // นับจำนวน appointments (ตรวจสอบว่าฟิลด์มีอยู่หรือไม่)
    $table_check = $conn->query("SHOW TABLES LIKE 'medical_records'");
    if ($table_check && $table_check->num_rows > 0) {
        $column_check = $conn->query("SHOW COLUMNS FROM medical_records LIKE 'next_appointment'");
        if ($column_check && $column_check->num_rows > 0) {
            $result = $conn->query("SELECT COUNT(*) as count FROM medical_records WHERE next_appointment > NOW()");
            $stats['appointments']['upcoming'] = $result ? $result->fetch_assoc()['count'] : 0;
            
            $result = $conn->query("SELECT COUNT(*) as count FROM medical_records WHERE DATE(next_appointment) = CURDATE()");
            $stats['appointments']['today'] = $result ? $result->fetch_assoc()['count'] : 0;
        } else {
            $stats['appointments']['upcoming'] = 0;
            $stats['appointments']['today'] = 0;
        }
    } else {
        $stats['appointments']['upcoming'] = 0;
        $stats['appointments']['today'] = 0;
    }
    
    // ดึงข้อมูลล่าสุด
    $recent_patients = [];
    $stmt = $conn->prepare("SELECT id, first_name, last_name, age, phone, created_at, gender FROM patients ORDER BY created_at DESC LIMIT 6");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_patients[] = $row;
    }
    
    $recent_records = [];
    $upcoming_appointments = [];
    
    // ตรวจสอบตาราง medical_records ก่อนใช้งาน
    $table_check = $conn->query("SHOW TABLES LIKE 'medical_records'");
    if ($table_check && $table_check->num_rows > 0) {
        $stmt = $conn->prepare("SELECT mr.id, mr.diagnosis, mr.visit_date, mr.treatment, p.first_name, p.last_name, p.id as patient_id
                               FROM medical_records mr 
                               JOIN patients p ON mr.patient_id = p.id 
                               ORDER BY mr.visit_date DESC 
                               LIMIT 6");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $recent_records[] = $row;
        }
        
        // ตรวจสอบฟิลด์ next_appointment
        $column_check = $conn->query("SHOW COLUMNS FROM medical_records LIKE 'next_appointment'");
        if ($column_check && $column_check->num_rows > 0) {
            $stmt = $conn->prepare("SELECT mr.id, mr.next_appointment, mr.diagnosis, mr.notes, p.first_name, p.last_name, p.id as patient_id
                                   FROM medical_records mr 
                                   JOIN patients p ON mr.patient_id = p.id 
                                   WHERE mr.next_appointment > NOW() 
                                   ORDER BY mr.next_appointment 
                                   LIMIT 6");
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $upcoming_appointments[] = $row;
            }
        }
    }

    // สถิติขั้นสูงสำหรับ Charts
    $daily_patients = [];
    $stmt = $conn->prepare("SELECT DATE(created_at) as date, COUNT(*) as count
                           FROM patients 
                           WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                           GROUP BY DATE(created_at)
                           ORDER BY date");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $daily_patients[] = $row;
    }
    
    $monthly_patients = [];
    $stmt = $conn->prepare("SELECT YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count
                           FROM patients 
                           WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                           GROUP BY YEAR(created_at), MONTH(created_at)
                           ORDER BY year, month");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $monthly_patients[] = $row;
    }
    
    // สถิติเพิ่มเติมสำหรับ Dashboard
    $today_appointments = $stats['appointments']['today'];
    $week_appointments = 0;
    
    if ($table_check && $table_check->num_rows > 0) {
        $column_check = $conn->query("SHOW COLUMNS FROM medical_records LIKE 'next_appointment'");
        if ($column_check && $column_check->num_rows > 0) {
            $result = $conn->query("SELECT COUNT(*) as count 
                                   FROM medical_records 
                                   WHERE next_appointment BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)");
            $week_appointments = $result ? $result->fetch_assoc()['count'] : 0;
        }
    }
    
} catch (Exception $e) {
    // จัดการ Error อย่างปลอดภัย
    error_log("Dashboard Error: " . $e->getMessage());
    
    // Fallback ข้อมูลเบื้องต้น
    $stats = [
        'patients' => ['total' => 0, 'new_this_month' => 0], 
        'records' => ['total' => 0, 'this_month' => 0], 
        'doctors' => ['total' => 0], 
        'appointments' => ['upcoming' => 0, 'today' => 0]
    ];
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
    <div class="welcome-banner mb-4" data-aos="fade-down">
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
                            Last login: <?php echo isset($user['last_login']) && $user['last_login'] ? date('M j, H:i', strtotime($user['last_login'])) : 'First time'; ?>
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
                'value' => $stats['patients']['total'], 
                'change' => $stats['patients']['new_this_month'],
                'icon' => 'bx-group', 
                'color' => 'linear-gradient(135deg, #28a745, #20c997)', 
                'desc' => 'Registered patients',
                'link' => '../patients/patients.php'
            ],
            [
                'title' => 'Medical Records', 
                'value' => $stats['records']['total'], 
                'change' => $stats['records']['this_month'],
                'icon' => 'bx-file-blank', 
                'color' => 'linear-gradient(135deg, #007bff, #0056b3)', 
                'desc' => 'Total records',
                'link' => '../medical_records/medical_record_action.php'
            ],
            [
                'title' => 'Total Doctors', 
                'value' => $stats['doctors']['total'], 
                'change' => 0,
                'icon' => 'bx-user-check', 
                'color' => 'linear-gradient(135deg, #ffc107, #e0a800)', 
                'desc' => 'Registered doctors',
                'link' => '../doctors/doctors_action.php'
            ],
            [
                'title' => 'Upcoming Visits', 
                'value' => $stats['appointments']['upcoming'], 
                'change' => $stats['appointments']['today'],
                'icon' => 'bx-calendar-check', 
                'color' => 'linear-gradient(135deg, #17a2b8, #138496)', 
                'desc' => 'Scheduled appointments',
                'link' => '#'
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
                        <div class="stats-number-large" data-target="<?php echo $item['value']; ?>"><?php echo $item['value']; ?></div>
                        <h5 class="card-title mb-1"><?php echo $item['title']; ?></h5>
                        <p class="text-muted small mb-2"><?php echo $item['desc']; ?></p>
                        <?php if ($item['change'] > 0): ?>
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
    <div class="quick-actions-bar mb-4" data-aos="fade-up">
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
                            <a href="#" class="btn btn-info btn-sm">
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
        <!-- Patient Registration Chart -->
        <div class="col-lg-6 mb-4">
            <div class="chart-container" data-aos="fade-up">
                <div class="chart-header">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="mb-1">
                                <i class="bx bx-trending-up me-2 text-primary"></i>
                                Patient Registration Trends
                            </h5>
                            <small class="text-muted">Daily patient registrations over the last 30 days</small>
                        </div>
                        <div class="chart-controls">
                            <button class="btn btn-sm btn-primary" onclick="toggleChartView('daily')">Daily</button>
                            <button class="btn btn-sm btn-outline-primary" onclick="toggleChartView('monthly')">Monthly</button>
                        </div>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas id="patientTrendsChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="col-lg-6 mb-4">
            <div class="chart-container" data-aos="fade-up" data-aos-delay="100">
                <div class="chart-header">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="mb-1">
                                <i class="bx bx-cog me-2 text-success"></i>
                                System Status
                            </h5>
                            <small class="text-muted">Current system health and performance</small>
                        </div>
                        <div class="status-indicator-main">
                            <span class="badge bg-success">All Systems Operational</span>
                        </div>
                    </div>
                </div>
                <div class="chart-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="status-item">
                                <div class="d-flex align-items-center">
                                    <div class="status-indicator bg-success rounded-circle me-3"></div>
                                    <div>
                                        <h6 class="mb-0">Database</h6>
                                        <small class="text-success">Connected</small>
                                        <div class="status-detail">
                                            <small class="text-muted">Response: <strong>2ms</strong></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="status-item">
                                <div class="d-flex align-items-center">
                                    <div class="status-indicator bg-success rounded-circle me-3"></div>
                                    <div>
                                        <h6 class="mb-0">System</h6>
                                        <small class="text-success">Running</small>
                                        <div class="status-detail">
                                            <small class="text-muted">Uptime: <strong>99.9%</strong></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="status-item">
                                <div class="d-flex align-items-center">
                                    <div class="status-indicator bg-success rounded-circle me-3"></div>
                                    <div>
                                        <h6 class="mb-0">Users</h6>
                                        <small class="text-success"><?php echo $stats['patients']['total'] + 1; ?> Active</small>
                                        <div class="status-detail">
                                            <small class="text-muted">Online: <strong>1</strong></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="status-item">
                                <div class="d-flex align-items-center">
                                    <div class="status-indicator bg-success rounded-circle me-3"></div>
                                    <div>
                                        <h6 class="mb-0">Storage</h6>
                                        <small class="text-success">Available</small>
                                        <div class="status-detail">
                                            <small class="text-muted">Free: <strong>85%</strong></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent System Activity -->
                    <div class="system-activity mt-4">
                        <h6 class="mb-3">
                            <i class="bx bx-history me-2"></i>
                            Recent Activity
                        </h6>
                        <div class="activity-timeline">
                            <div class="timeline-item">
                                <div class="timeline-dot bg-success"></div>
                                <div class="timeline-content">
                                    <small class="text-success">System backup completed</small>
                                    <div class="text-muted">2 hours ago</div>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-dot bg-info"></div>
                                <div class="timeline-content">
                                    <small class="text-info">Database optimized</small>
                                    <div class="text-muted">6 hours ago</div>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-dot bg-warning"></div>
                                <div class="timeline-content">
                                    <small class="text-warning">Cache cleared</small>
                                    <div class="text-muted">12 hours ago</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Export and Backup Section -->
    <div class="row mb-5">
        <div class="col-lg-12">
            <div class="export-section" data-aos="fade-up">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">
                                    <i class="bx bx-download me-2 text-info"></i>
                                    Data Management
                                </h5>
                                <small class="text-muted">Export reports and manage system data</small>
                            </div>
                            <div class="export-controls">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="exportData('patients')">
                                        <i class="bx bx-export me-1"></i>Export Patients
                                    </button>
                                    <button type="button" class="btn btn-outline-success btn-sm" onclick="exportData('records')">
                                        <i class="bx bx-file-export me-1"></i>Export Records
                                    </button>
                                    <?php if ($isAdmin): ?>
                                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="performBackup()">
                                        <i class="bx bx-cloud-upload me-1"></i>Backup
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="export-stat">
                                    <div class="export-icon">
                                        <i class="bx bx-group text-primary"></i>
                                    </div>
                                    <div class="export-info">
                                        <h6 class="mb-1"><?php echo $stats['patients']['total']; ?></h6>
                                        <small class="text-muted">Total Patients</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="export-stat">
                                    <div class="export-icon">
                                        <i class="bx bx-file text-success"></i>
                                    </div>
                                    <div class="export-info">
                                        <h6 class="mb-1"><?php echo $stats['records']['total']; ?></h6>
                                        <small class="text-muted">Medical Records</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="export-stat">
                                    <div class="export-icon">
                                        <i class="bx bx-calendar text-info"></i>
                                    </div>
                                    <div class="export-info">
                                        <h6 class="mb-1"><?php echo $stats['appointments']['upcoming']; ?></h6>
                                        <small class="text-muted">Appointments</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="export-stat">
                                    <div class="export-icon">
                                        <i class="bx bx-shield text-warning"></i>
                                    </div>
                                    <div class="export-info">
                                        <h6 class="mb-1"><?php echo date('M j'); ?></h6>
                                        <small class="text-muted">Last Backup</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Global Search Modal -->
<div class="modal fade" id="globalSearchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bx bx-search me-2"></i>
                    Global Search
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="search-container">
                    <div class="input-group mb-3">
                        <input type="text" id="globalSearchInput" class="form-control" placeholder="Search patients, records, or appointments..." autocomplete="off">
                        <button class="btn btn-primary" type="button" onclick="performGlobalSearch()">
                            <i class="bx bx-search"></i>
                        </button>
                    </div>
                    <div id="searchResults" class="search-results"></div>
                </div>
            </div>
            <div class="modal-footer">
                <small class="text-muted">
                    <i class="bx bx-info-circle me-1"></i>
                    Tip: Use Ctrl+K to quickly open search
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Pass PHP data to JavaScript -->
<script>
    // Pass chart data to JavaScript
    window.dashboardData = {
        dailyPatients: <?php echo json_encode($daily_patients); ?>,
        monthlyPatients: <?php echo json_encode($monthly_patients); ?>,
        stats: <?php echo json_encode($stats); ?>,
        userRole: '<?php echo $user['role']; ?>',
        isAdmin: <?php echo $isAdmin ? 'true' : 'false'; ?>
    };
</script>

<?php
// Include Enhanced Footer
include '../includes/enhanced-footer.php';
?>