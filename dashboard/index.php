<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

// ตั้งค่าสำหรับ includes
$assets_path = '../assets/';
$page_title = 'Medical Dashboard';
$extra_scripts_head = ['https://cdn.jsdelivr.net/npm/chart.js'];
$extra_css = ['../assets/css/dashboard.css'];

// ดึงข้อมูลผู้ใช้
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT username, email, role, created_at FROM users WHERE id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$isAdmin = ($user['role'] == 'admin');

// ดึงสถิติต่างๆ (เก็บเฉพาะที่ต้องการ)
$stats = [
    'patients' => $conn->query("SELECT COUNT(*) as count FROM patients")->fetch_assoc()['count'],
    'records' => $conn->query("SELECT COUNT(*) as count FROM medical_records")->fetch_assoc()['count'],
    'doctors' => $conn->query("SELECT COUNT(*) as count FROM doctors")->fetch_assoc()['count'],
    'appointments' => $conn->query("SELECT COUNT(*) as count FROM medical_records WHERE next_appointment > NOW()")->fetch_assoc()['count']
];

// ดึงข้อมูลล่าสุด (เฉพาะที่ต้องการ)
$recent_patients = $conn->query("SELECT id, first_name, last_name, age, phone, created_at FROM patients ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$recent_records = $conn->query("SELECT mr.id, mr.diagnosis, mr.visit_date, p.first_name, p.last_name FROM medical_records mr JOIN patients p ON mr.patient_id = p.id ORDER BY mr.visit_date DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$upcoming_appointments = $conn->query("SELECT mr.next_appointment, mr.diagnosis, p.first_name, p.last_name FROM medical_records mr JOIN patients p ON mr.patient_id = p.id WHERE mr.next_appointment > NOW() ORDER BY mr.next_appointment LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// สถิติผู้ป่วยรายวัน (30 วันล่าสุด)
$daily_patients = $conn->query("SELECT 
    DATE(created_at) as date,
    COUNT(*) as count
    FROM patients 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date")->fetch_all(MYSQLI_ASSOC);

// สถิติผู้ป่วยรายเดือน (12 เดือนล่าสุด)
$monthly_patients = $conn->query("SELECT 
    YEAR(created_at) as year,
    MONTH(created_at) as month,
    COUNT(*) as count
    FROM patients 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY year, month")->fetch_all(MYSQLI_ASSOC);

// Include Header และ Sidebar
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Layout Page -->
<div class="layout-page">
  
    <!-- Content wrapper -->
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">Dashboard</h4>
                    <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($user['username']); ?>! Here's what's happening today.</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-md me-3">
                            <img src="<?php echo $assets_path; ?>img/avatars/1.png" alt="Avatar" class="rounded-circle" width="40" height="40">
                        </div>
                        <div>
                            <h6 class="mb-0"><?php echo htmlspecialchars($user['username']); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-5">
                <?php 
                $stat_items = [
                    ['title' => 'Total Patients', 'value' => $stats['patients'], 'icon' => 'bx-group', 'color' => 'linear-gradient(135deg, #28a745, #20c997)', 'desc' => 'Registered patients'],
                    ['title' => 'Medical Records', 'value' => $stats['records'], 'icon' => 'bx-file-blank', 'color' => 'linear-gradient(135deg, #007bff, #0056b3)', 'desc' => 'Total records'],
                    ['title' => 'Total Doctors', 'value' => $stats['doctors'], 'icon' => 'bx-user-check', 'color' => 'linear-gradient(135deg, #ffc107, #e0a800)', 'desc' => 'Registered doctors'],
                    ['title' => 'Upcoming Visits', 'value' => $stats['appointments'], 'icon' => 'bx-calendar-check', 'color' => 'linear-gradient(135deg, #17a2b8, #138496)', 'desc' => 'Scheduled appointments']
                ];
                foreach ($stat_items as $item): ?>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="dashboard-stats-card">
                        <div class="card-body text-center py-4">
                            <div class="stats-icon-large mb-3" style="background: <?php echo $item['color']; ?>">
                                <i class="bx <?php echo $item['icon']; ?>"></i>
                            </div>
                            <div class="stats-number-large"><?php echo number_format($item['value']); ?></div>
                            <h5 class="card-title mb-1"><?php echo $item['title']; ?></h5>
                            <p class="text-muted small mb-0"><?php echo $item['desc']; ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Activity Section -->
            <div class="row mb-5">
                <!-- Recent Patients -->
                <div class="col-lg-4 mb-4">
                    <div class="activity-card">
                        <div class="activity-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="bx bx-user-plus me-2 text-primary"></i>Recent Patients</h6>
                                <a href="../patients/patients.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                        </div>
                        <div class="activity-body">
                            <?php if (empty($recent_patients)): ?>
                                <div class="text-center py-4">
                                    <i class="bx bx-user-plus display-2 text-muted"></i>
                                    <p class="mt-2 text-muted small">No patients yet</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recent_patients as $patient): ?>
                                <div class="activity-item-enhanced">
                                    <div class="d-flex align-items-center">
                                        <div class="patient-avatar-large">
                                            <?php echo strtoupper(substr($patient['first_name'], 0, 1) . substr($patient['last_name'], 0, 1)); ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <a href="../patients/patient_view.php?id=<?php echo $patient['id']; ?>" class="text-decoration-none">
                                                <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h6>
                                            </a>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="bx bx-calendar me-1"></i>Age: <?php echo $patient['age']; ?>
                                                </small>
                                                <span class="badge bg-light text-dark"><?php echo date('M j', strtotime($patient['created_at'])); ?></span>
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
                    <div class="activity-card">
                        <div class="activity-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="bx bx-file me-2 text-success"></i>Recent Records</h6>
                                <a href="../medical_records/medical_records_action.php" class="btn btn-sm btn-success">View All</a>
                            </div>
                        </div>
                        <div class="activity-body">
                            <?php if (empty($recent_records)): ?>
                                <div class="text-center py-4">
                                    <i class="bx bx-file display-2 text-muted"></i>
                                    <p class="mt-2 text-muted small">No records yet</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recent_records as $record): ?>
                                <div class="activity-item-enhanced">
                                    <div class="d-flex align-items-center">
                                        <div class="patient-avatar-large" style="background: linear-gradient(135deg, #28a745, #20c997);">
                                            <?php echo strtoupper(substr($record['first_name'], 0, 1) . substr($record['last_name'], 0, 1)); ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <a href="../medical_records/medical_record_view.php?id=<?php echo $record['id']; ?>" class="text-decoration-none">
                                                <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></h6>
                                            </a>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="bx bx-health me-1"></i><?php echo htmlspecialchars(substr($record['diagnosis'], 0, 20)); ?><?php echo strlen($record['diagnosis']) > 20 ? '...' : ''; ?>
                                                </small>
                                                <span class="badge bg-light text-dark"><?php echo date('M j', strtotime($record['visit_date'])); ?></span>
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
                    <div class="activity-card">
                        <div class="activity-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="bx bx-calendar me-2 text-info"></i>Upcoming Appointments</h6>
                                <span class="badge bg-info"><?php echo count($upcoming_appointments); ?></span>
                            </div>
                        </div>
                        <div class="activity-body">
                            <?php if (empty($upcoming_appointments)): ?>
                                <div class="text-center py-4">
                                    <i class="bx bx-calendar-check display-2 text-muted"></i>
                                    <p class="mt-2 text-muted small">No upcoming appointments</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($upcoming_appointments as $appointment): ?>
                                <div class="activity-item-enhanced">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="d-flex align-items-center flex-grow-1">
                                            <div class="patient-avatar-large" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                                                <?php echo strtoupper(substr($appointment['first_name'], 0, 1) . substr($appointment['last_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></h6>
                                                <small class="text-muted">
                                                    <i class="bx bx-health me-1"></i><?php echo htmlspecialchars(substr($appointment['diagnosis'], 0, 15)); ?><?php echo strlen($appointment['diagnosis']) > 15 ? '...' : ''; ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="appointment-badge"><?php echo date('M j', strtotime($appointment['next_appointment'])); ?></span>
                                            <div class="mt-1">
                                                <small class="text-success"><span class="upcoming-indicator"></span> <?php echo date('H:i', strtotime($appointment['next_appointment'])); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="row mb-5">
                <!-- Daily Patients Chart -->
                <div class="col-lg-6 mb-4">
                    <div class="chart-container">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0"><i class="bx bx-trending-up me-2 text-primary"></i>Daily Patient Registrations</h5>
                            <span class="badge bg-primary">Last 30 Days</span>
                        </div>
                        <canvas id="dailyPatientsChart" style="max-height: 350px;"></canvas>
                    </div>
                </div>

                <!-- Monthly Patients Chart -->
                <div class="col-lg-6 mb-4">
                    <div class="chart-container">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0"><i class="bx bx-bar-chart me-2 text-success"></i>Monthly Patient Registrations</h5>
                            <span class="badge bg-success">Last 12 Months</span>
                        </div>
                        <canvas id="monthlyPatientsChart" style="max-height: 350px;"></canvas>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="system-status">
                <h5 class="mb-4"><i class="bx bx-cog me-2 text-secondary"></i>System Status</h5>
                <div class="row">
                    <?php 
                    $status_items = [
                        ['icon' => 'bx-check', 'color' => 'linear-gradient(135deg, #28a745, #20c997)', 'title' => 'Database Connection', 'status' => 'Online - Healthy', 'class' => 'success'],
                        ['icon' => 'bx-server', 'color' => 'linear-gradient(135deg, #007bff, #0056b3)', 'title' => 'Server Status', 'status' => 'Running - Normal', 'class' => 'primary'],
                        ['icon' => 'bx-shield', 'color' => 'linear-gradient(135deg, #ffc107, #e0a800)', 'title' => 'Security', 'status' => 'Protected - SSL Active', 'class' => 'warning'],
                        ['icon' => 'bx-time', 'color' => 'linear-gradient(135deg, #17a2b8, #138496)', 'title' => 'Last Backup', 'status' => 'Today - ' . date('H:i'), 'class' => 'info']
                    ];
                    foreach ($status_items as $status): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="status-item">
                            <div class="status-icon" style="background: <?php echo $status['color']; ?>;">
                                <i class="bx <?php echo $status['icon']; ?>"></i>
                            </div>
                            <div>
                                <h6 class="mb-1"><?php echo $status['title']; ?></h6>
                                <small class="text-<?php echo $status['class']; ?>"><?php echo $status['status']; ?></small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Chart.js Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Daily Patients Chart
    const dailyCtx = document.getElementById('dailyPatientsChart').getContext('2d');
    
    // Prepare daily data
    const dailyData = [];
    const dailyLabels = [];
    
    // Generate last 30 days
    const today = new Date();
    for (let i = 29; i >= 0; i--) {
        const date = new Date(today);
        date.setDate(date.getDate() - i);
        const dateStr = date.toISOString().split('T')[0];
        dailyLabels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
        
        // Find matching data
        const found = <?php echo json_encode($daily_patients); ?>.find(item => item.date === dateStr);
        dailyData.push(found ? parseInt(found.count) : 0);
    }
    
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: dailyLabels,
            datasets: [{
                label: 'New Patients',
                data: dailyData,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#007bff',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#007bff',
                    borderWidth: 1
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { color: 'rgba(0,0,0,0.1)' },
                    ticks: { stepSize: 1 }
                },
                x: { 
                    grid: { display: false },
                    ticks: { maxTicksLimit: 10 }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });

    // Monthly Patients Chart
    const monthlyCtx = document.getElementById('monthlyPatientsChart').getContext('2d');
    
    // Prepare monthly data
    const monthlyData = [];
    const monthlyLabels = [];
    
    // Generate last 12 months
    for (let i = 11; i >= 0; i--) {
        const date = new Date();
        date.setMonth(date.getMonth() - i);
        const year = date.getFullYear();
        const month = date.getMonth() + 1;
        
        monthlyLabels.push(date.toLocaleDateString('en-US', { year: 'numeric', month: 'short' }));
        
        // Find matching data
        const found = <?php echo json_encode($monthly_patients); ?>.find(item => 
            parseInt(item.year) === year && parseInt(item.month) === month
        );
        monthlyData.push(found ? parseInt(found.count) : 0);
    }
    
    new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: monthlyLabels,
            datasets: [{
                label: 'New Patients',
                data: monthlyData,
                backgroundColor: 'rgba(40, 167, 69, 0.8)',
                borderColor: '#28a745',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#28a745',
                    borderWidth: 1
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { color: 'rgba(0,0,0,0.1)' },
                    ticks: { stepSize: 1 }
                },
                x: { 
                    grid: { display: false },
                    ticks: { maxTicksLimit: 8 }
                }
            }
        }
    });

    // Animations
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.activity-item-enhanced').forEach(item => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';
        item.style.transition = 'all 0.6s ease';
        observer.observe(item);
    });

    // Counter Animation
    function animateCounter(element, target) {
        let current = 0;
        const increment = target / 50;
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current).toLocaleString();
        }, 20);
    }

    const statsObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const statsNumber = entry.target.querySelector('.stats-number-large');
                if (statsNumber && !statsNumber.dataset.animated) {
                    const target = parseInt(statsNumber.textContent.replace(/,/g, ''));
                    statsNumber.dataset.animated = 'true';
                    animateCounter(statsNumber, target);
                }
            }
        });
    }, { threshold: 0.5 });

    document.querySelectorAll('.dashboard-stats-card').forEach(card => {
        statsObserver.observe(card);
    });

    // Chart animations
    const chartObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.2 });

    document.querySelectorAll('.chart-container').forEach(chart => {
        chart.style.opacity = '0';
        chart.style.transform = 'translateY(30px)';
        chart.style.transition = 'all 0.8s ease';
        chartObserver.observe(chart);
    });
});
</script>

<?php include '../includes/footer.php'; ?>