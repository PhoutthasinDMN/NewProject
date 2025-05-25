<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// บังคับให้ล็อกอินก่อนเข้าใช้งาน
requireLogin();

// ดึงข้อมูลผู้ใช้
$user_id = $_SESSION['user_id'];
$sql = "SELECT username, email, role FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// ตรวจสอบว่าผู้ใช้เป็น admin หรือไม่
$isAdmin = ($user['role'] == 'admin');

// ตรวจสอบ patient_id ถ้ามี
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
$patient_info = null;

// ถ้ามี patient_id ให้ดึงข้อมูลผู้ป่วย
if ($patient_id > 0) {
    $patient_sql = "SELECT * FROM patients WHERE id = ?";
    $stmt = $conn->prepare($patient_sql);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $patient_info = $result->fetch_assoc();
    }
}

// แก้ไข SQL query ให้ถูกต้อง - เปลี่ยนจาก DAYS เป็น DAY
$appointments_sql = "
    SELECT 
        mr.id as record_id,
        mr.patient_id,
        mr.next_appointment,
        mr.diagnosis,
        mr.doctor_name,
        mr.visit_date as last_visit,
        p.first_name,
        p.last_name,
        p.phone,
        p.email,
        p.age,
        CASE 
            WHEN mr.next_appointment < NOW() THEN 'overdue'
            WHEN mr.next_appointment < DATE_ADD(NOW(), INTERVAL 1 DAY) THEN 'today'
            WHEN mr.next_appointment < DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'upcoming'
            ELSE 'scheduled'
        END as status
    FROM medical_records mr
    JOIN patients p ON mr.patient_id = p.id
    WHERE mr.next_appointment IS NOT NULL 
    AND mr.next_appointment > DATE_SUB(NOW(), INTERVAL 30 DAY)
    " . ($patient_id > 0 ? " AND mr.patient_id = ?" : "") . "
    ORDER BY mr.next_appointment ASC
";

// เตรียม statement และ execute
try {
    if ($patient_id > 0) {
        $stmt = $conn->prepare($appointments_sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $appointments_result = $stmt->get_result();
    } else {
        $appointments_result = $conn->query($appointments_sql);
        if (!$appointments_result) {
            throw new Exception("Query failed: " . $conn->error);
        }
    }

    $appointments = [];
    if ($appointments_result && $appointments_result->num_rows > 0) {
        while ($row = $appointments_result->fetch_assoc()) {
            $appointments[] = $row;
        }
    }

} catch (Exception $e) {
    error_log("Database error in appointments.php: " . $e->getMessage());
    $appointments = [];
    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการดึงข้อมูลนัดหมาย กรุณาลองใหม่อีกครั้ง";
}

// สถิติ appointments
$stats = [
    'total' => count($appointments),
    'today' => 0,
    'upcoming' => 0,
    'overdue' => 0,
    'scheduled' => 0
];

foreach ($appointments as $appointment) {
    if (isset($stats[$appointment['status']])) {
        $stats[$appointment['status']]++;
    }
}

// ดึงข้อมูลหมอทั้งหมด
$doctors = [];
try {
    $doctors_result = $conn->query("SELECT id, first_name, last_name, specialization FROM doctors ORDER BY first_name, last_name");
    if ($doctors_result) {
        $doctors = $doctors_result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error fetching doctors: " . $e->getMessage());
    $doctors = [];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Layout Page -->
<div class="layout-page">
    <!-- Content wrapper -->
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            
            <!-- แสดงข้อความ error หากมี -->
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bx bx-error me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Header -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-1"><i class="bx bx-calendar me-2 text-primary"></i>Appointments Management</h4>
                            <p class="text-muted">Manage patient appointments and follow-up visits</p>
                        </div>
                        <div>
                            <a href="appointments_action.php?action=add" class="btn btn-primary me-2">
                                <i class="bx bx-plus me-2"></i>Schedule New Appointment
                            </a>
                            <a href="calendar.php" class="btn btn-outline-info">
                                <i class="bx bx-calendar-alt me-2"></i>Calendar View
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card stats-card h-100">
                        <div class="card-body text-center">
                            <div class="stats-icon bg-primary mb-3">
                                <i class="bx bx-calendar-check"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['total']; ?></h3>
                            <p class="text-muted mb-0">Total Appointments</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card stats-card h-100">
                        <div class="card-body text-center">
                            <div class="stats-icon bg-success mb-3">
                                <i class="bx bx-time"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['today']; ?></h3>
                            <p class="text-muted mb-0">Today's Appointments</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card stats-card h-100">
                        <div class="card-body text-center">
                            <div class="stats-icon bg-info mb-3">
                                <i class="bx bx-calendar-event"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['upcoming']; ?></h3>
                            <p class="text-muted mb-0">Upcoming (7 days)</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card stats-card h-100">
                        <div class="card-body text-center">
                            <div class="stats-icon bg-danger mb-3">
                                <i class="bx bx-calendar-x"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['overdue']; ?></h3>
                            <p class="text-muted mb-0">Overdue</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Patient Information (if specific patient) -->
            <?php if ($patient_info): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card patient-info-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="patient-avatar-small me-3">
                                        <?php echo strtoupper(substr($patient_info['first_name'], 0, 1) . substr($patient_info['last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 text-white"><?php echo htmlspecialchars($patient_info['first_name'] . ' ' . $patient_info['last_name']); ?></h5>
                                        <div class="d-flex gap-3 text-white opacity-75">
                                            <span><i class="bx bx-id-card me-1"></i>ID: <?php echo str_pad($patient_info['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                            <span><i class="bx bx-calendar me-1"></i>Age: <?php echo $patient_info['age']; ?></span>
                                            <span><i class="bx bx-phone me-1"></i><?php echo htmlspecialchars($patient_info['phone']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Filter and Search -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Search Patient</label>
                                    <input type="text" id="searchInput" class="form-control" placeholder="Patient name...">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Filter by Status</label>
                                    <select id="statusFilter" class="form-select">
                                        <option value="">All Status</option>
                                        <option value="overdue">Overdue</option>
                                        <option value="today">Today</option>
                                        <option value="upcoming">Upcoming</option>
                                        <option value="scheduled">Scheduled</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Filter by Doctor</label>
                                    <select id="doctorFilter" class="form-select">
                                        <option value="">All Doctors</option>
                                        <?php foreach ($doctors as $doctor): ?>
                                            <option value="Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>">
                                                Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date Range</label>
                                    <input type="date" id="dateFilter" class="form-control">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="button" class="btn btn-outline-secondary btn-sm me-2" onclick="clearAllFilters()">
                                        <i class="bx bx-refresh me-1"></i>Clear Filters
                                    </button>
                                    <button type="button" class="btn btn-outline-success btn-sm me-2" onclick="exportAppointments()">
                                        <i class="bx bx-download me-1"></i>Export CSV
                                    </button>
                                    <button type="button" class="btn btn-outline-info btn-sm" onclick="printAppointments()">
                                        <i class="bx bx-printer me-1"></i>Print List
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appointments List -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-list-ul me-2"></i>All Appointments</h5>
                                <div id="resultsCounter" class="text-muted small mt-1"></div>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="toggleView('card')">
                                    <i class="bx bx-grid-alt"></i> Card View
                                </button>
                                <button class="btn btn-sm btn-primary" onclick="toggleView('table')">
                                    <i class="bx bx-table"></i> Table View
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            
                            <!-- Table View -->
                            <div id="tableView" class="table-responsive">
                                <?php if (empty($appointments)): ?>
                                    <div class="text-center py-5">
                                        <i class="bx bx-calendar-x display-1 text-muted"></i>
                                        <h5 class="mt-3 text-muted">No Appointments Scheduled</h5>
                                        <p class="text-muted">No upcoming appointments found from medical records</p>
                                        <a href="appointments_action.php?action=add" class="btn btn-primary">
                                            <i class="bx bx-plus me-2"></i>Schedule First Appointment
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <table class="table table-hover mb-0" id="appointmentsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Patient</th>
                                                <th>Appointment Date</th>
                                                <th>Doctor</th>
                                                <th>Last Visit</th>
                                                <th>Follow-up For</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($appointments as $appointment): ?>
                                            <tr data-status="<?php echo $appointment['status']; ?>" data-doctor="<?php echo htmlspecialchars($appointment['doctor_name']); ?>">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-md me-3">
                                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                                                <span class="fw-bold"><?php echo strtoupper(substr($appointment['first_name'], 0, 1) . substr($appointment['last_name'], 0, 1)); ?></span>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-1"><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></h6>
                                                            <small class="text-muted">
                                                                <i class="bx bx-phone me-1"></i><?php echo htmlspecialchars($appointment['phone']); ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="fw-bold"><?php echo date('M j, Y', strtotime($appointment['next_appointment'])); ?></span>
                                                    <br><small class="text-muted"><?php echo date('H:i A', strtotime($appointment['next_appointment'])); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($appointment['doctor_name']); ?></span>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M j, Y', strtotime($appointment['last_visit'])); ?></small>
                                                </td>
                                                <td>
                                                    <small class="text-muted"><?php echo htmlspecialchars(substr($appointment['diagnosis'], 0, 40) . (strlen($appointment['diagnosis']) > 40 ? '...' : '')); ?></small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_classes = [
                                                        'overdue' => 'bg-danger',
                                                        'today' => 'bg-success',
                                                        'upcoming' => 'bg-warning',
                                                        'scheduled' => 'bg-info'
                                                    ];
                                                    $status_labels = [
                                                        'overdue' => 'Overdue',
                                                        'today' => 'Today',
                                                        'upcoming' => 'Upcoming',
                                                        'scheduled' => 'Scheduled'
                                                    ];
                                                    ?>
                                                    <span class="badge <?php echo $status_classes[$appointment['status']]; ?>">
                                                        <?php echo $status_labels[$appointment['status']]; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="../medical_records/medical_record_view.php?id=<?php echo $appointment['record_id']; ?>" class="btn btn-sm btn-outline-info" title="View Medical Record">
                                                            <i class="bx bx-file-blank"></i>
                                                        </a>
                                                        <a href="appointments_action.php?action=edit&record_id=<?php echo $appointment['record_id']; ?>" class="btn btn-sm btn-primary" title="Edit Appointment">
                                                            <i class="bx bx-edit-alt"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-success" onclick="markAsCompleted(<?php echo $appointment['record_id']; ?>, '<?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?>')" title="Mark as Completed">
                                                            <i class="bx bx-check"></i>
                                                        </button>
                                                        <?php if ($appointment['phone']): ?>
                                                            <a href="tel:<?php echo $appointment['phone']; ?>" class="btn btn-sm btn-outline-success" title="Call Patient">
                                                                <i class="bx bx-phone"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>

                            <!-- Card View (Hidden by default) -->
                            <div id="cardView" style="display: none;">
                                <div class="row p-3">
                                    <?php if (!empty($appointments)): ?>
                                        <?php foreach ($appointments as $appointment): ?>
                                        <div class="col-lg-6 col-xl-4 mb-4 appointment-card" data-status="<?php echo $appointment['status']; ?>" data-doctor="<?php echo htmlspecialchars($appointment['doctor_name']); ?>">
                                            <div class="card appointment-item h-100">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <div class="patient-avatar me-3">
                                                                <?php echo strtoupper(substr($appointment['first_name'], 0, 1) . substr($appointment['last_name'], 0, 1)); ?>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-1"><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></h6>
                                                                <small class="text-muted">Age: <?php echo $appointment['age']; ?></small>
                                                            </div>
                                                        </div>
                                                        <?php
                                                        $status_classes = [
                                                            'overdue' => 'bg-danger',
                                                            'today' => 'bg-success',
                                                            'upcoming' => 'bg-warning',
                                                            'scheduled' => 'bg-info'
                                                        ];
                                                        $status_labels = [
                                                            'overdue' => 'Overdue',
                                                            'today' => 'Today',
                                                            'upcoming' => 'Upcoming',
                                                            'scheduled' => 'Scheduled'
                                                        ];
                                                        ?>
                                                        <span class="badge <?php echo $status_classes[$appointment['status']]; ?>">
                                                            <?php echo $status_labels[$appointment['status']]; ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <div class="appointment-details mb-3">
                                                        <div class="d-flex justify-content-between mb-2">
                                                            <span class="text-muted">Appointment:</span>
                                                            <span class="fw-bold"><?php echo date('M j, Y H:i', strtotime($appointment['next_appointment'])); ?></span>
                                                        </div>
                                                        <div class="d-flex justify-content-between mb-2">
                                                            <span class="text-muted">Doctor:</span>
                                                            <span><?php echo htmlspecialchars($appointment['doctor_name']); ?></span>
                                                        </div>
                                                        <div class="mb-2">
                                                            <span class="text-muted">Follow-up for:</span>
                                                            <br><small><?php echo htmlspecialchars($appointment['diagnosis']); ?></small>
                                                        </div>
                                                        <div class="d-flex justify-content-between mb-2">
                                                            <span class="text-muted">Last Visit:</span>
                                                            <span><?php echo date('M j, Y', strtotime($appointment['last_visit'])); ?></span>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="d-flex gap-2">
                                                        <a href="../medical_records/medical_record_view.php?id=<?php echo $appointment['record_id']; ?>" class="btn btn-sm btn-outline-info flex-fill">
                                                            <i class="bx bx-file-blank me-1"></i>View Record
                                                        </a>
                                                        <a href="appointments_action.php?action=edit&record_id=<?php echo $appointment['record_id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="bx bx-edit-alt"></i>
                                                        </a>
                                                        <button class="btn btn-sm btn-success" onclick="markAsCompleted(<?php echo $appointment['record_id']; ?>, '<?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?>')">
                                                            <i class="bx bx-check"></i>
                                                        </button>
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
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Complete Appointment Modal -->
<div class="modal fade" id="completeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bx bx-check-circle me-2 text-success"></i>Complete Appointment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Mark appointment for <strong id="patientNameComplete"></strong> as completed?</p>
                <div class="alert alert-info">
                    <i class="bx bx-info-circle me-2"></i>
                    This will create a new medical record for this visit and remove the appointment from the schedule.
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="createRecordCheckbox" checked>
                    <label class="form-check-label" for="createRecordCheckbox">
                        Create new medical record for this visit
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="completeLink" class="btn btn-success">
                    <i class="bx bx-check me-1"></i>Complete Appointment
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom CSS for appointments */
.stats-card {
    border-radius: 15px;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.stats-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    margin: 0 auto;
}

.appointment-item {
    border-left: 4px solid #696cff;
    transition: all 0.3s ease;
}

.appointment-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.patient-avatar {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #696cff, #5a67d8);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
}

.patient-avatar-small {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    font-size: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-weight: bold;
}

.patient-info-card {
    background: linear-gradient(135deg, #696cff, #5a67d8);
    border-radius: 15px;
    border: none;
    box-shadow: 0 8px 30px rgba(105, 108, 255, 0.2);
}

.appointment-details {
    font-size: 0.9rem;
}

.badge {
    font-size: 0.75rem;
    padding: 0.5rem 0.75rem;
}

.table tbody tr:hover {
    background-color: #f8f9ff;
    transform: translateY(-1px);
    transition: all 0.2s ease;
}

/* Status-based styling */
.appointment-item[data-status="overdue"] {
    border-left-color: #dc3545;
}

.appointment-item[data-status="today"] {
    border-left-color: #28a745;
}

.appointment-item[data-status="upcoming"] {
    border-left-color: #ffc107;
}

.appointment-item[data-status="scheduled"] {
    border-left-color: #17a2b8;
}

/* Responsive */
@media (max-width: 768px) {
    .stats-card {
        margin-bottom: 1rem;
    }
    
    .appointment-item {
        margin-bottom: 1rem;
    }
    
    .btn-group .btn {
        padding: 0.25rem 0.5rem;
    }
}

/* Animation for status badges */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.badge.bg-danger {
    animation: pulse 2s infinite;
}

.badge.bg-success {
    animation: pulse 3s infinite;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search and filter functionality
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const doctorFilter = document.getElementById('doctorFilter');
    const dateFilter = document.getElementById('dateFilter');
    
    function filterAppointments() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedStatus = statusFilter.value;
        const selectedDoctor = doctorFilter.value;
        const selectedDate = dateFilter.value;
        
        // Filter table rows
        const tableRows = document.querySelectorAll('#appointmentsTable tbody tr');
        tableRows.forEach(row => {
            const patientName = row.cells[0].textContent.toLowerCase();
            const appointmentDate = row.cells[1].textContent;
            const doctor = row.getAttribute('data-doctor');
            const status = row.getAttribute('data-status');
            
            // Extract date from appointment date cell for comparison
            const appointmentDateText = row.cells[1].querySelector('span') ? 
                row.cells[1].querySelector('span').textContent : appointmentDate;
            
            const matchesSearch = patientName.includes(searchTerm);
            const matchesStatus = !selectedStatus || status === selectedStatus;
            const matchesDoctor = !selectedDoctor || doctor === selectedDoctor;
            
            let matchesDate = true;
            if (selectedDate) {
                // Convert selected date to match format in table
                const selectedDateObj = new Date(selectedDate);
                const selectedDateFormatted = selectedDateObj.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
                matchesDate = appointmentDateText.includes(selectedDateFormatted);
            }
            
            const shouldShow = matchesSearch && matchesStatus && matchesDoctor && matchesDate;
            row.style.display = shouldShow ? '' : 'none';
        });
        
        // Filter card view
        const cards = document.querySelectorAll('.appointment-card');
        cards.forEach(card => {
            const patientName = card.querySelector('h6').textContent.toLowerCase();
            const doctor = card.getAttribute('data-doctor');
            const status = card.getAttribute('data-status');
            const appointmentDateElement = card.querySelector('.appointment-details .fw-bold');
            const appointmentDateText = appointmentDateElement ? appointmentDateElement.textContent : '';
            
            const matchesSearch = patientName.includes(searchTerm);
            const matchesStatus = !selectedStatus || status === selectedStatus;
            const matchesDoctor = !selectedDoctor || doctor === selectedDoctor;
            
            let matchesDate = true;
            if (selectedDate) {
                const selectedDateObj = new Date(selectedDate);
                const selectedDateFormatted = selectedDateObj.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
                matchesDate = appointmentDateText.includes(selectedDateFormatted);
            }
            
            const shouldShow = matchesSearch && matchesStatus && matchesDoctor && matchesDate;
            card.style.display = shouldShow ? '' : 'none';
        });
        
        // Update results count
        updateResultsCount();
    }
    
    // Function to update results count
    function updateResultsCount() {
        const visibleTableRows = document.querySelectorAll('#appointmentsTable tbody tr[style=""]').length +
                                document.querySelectorAll('#appointmentsTable tbody tr:not([style])').length;
        const visibleCards = document.querySelectorAll('.appointment-card[style=""]').length +
                           document.querySelectorAll('.appointment-card:not([style])').length;
        
        // Create or update results counter
        let resultsCounter = document.getElementById('resultsCounter');
        if (!resultsCounter) {
            resultsCounter = document.createElement('div');
            resultsCounter.id = 'resultsCounter';
            resultsCounter.className = 'text-muted small mt-2';
            const cardHeader = document.querySelector('.card-header h5').parentNode;
            cardHeader.appendChild(resultsCounter);
        }
        
        const totalAppointments = document.querySelectorAll('#appointmentsTable tbody tr').length;
        const currentView = document.getElementById('tableView').style.display !== 'none' ? 'table' : 'card';
        const visibleCount = currentView === 'table' ? visibleTableRows : visibleCards;
        
        resultsCounter.innerHTML = `<i class="bx bx-filter me-1"></i>Showing ${visibleCount} of ${totalAppointments} appointments`;
    }
    
    // Add event listeners for filters
    if (searchInput) searchInput.addEventListener('input', filterAppointments);
    if (statusFilter) statusFilter.addEventListener('change', filterAppointments);
    if (doctorFilter) doctorFilter.addEventListener('change', filterAppointments);
    if (dateFilter) dateFilter.addEventListener('change', filterAppointments);
    
    // Auto-refresh every 5 minutes
    setInterval(() => {
        location.reload();
    }, 300000);
    
    // Show current time and update page title
    function updateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('th-TH', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        const dateString = now.toLocaleDateString('th-TH');
        
        // Update page title with current time
        document.title = `Appointments (${timeString}) | Medical System`;
        
        // Update any time display elements if they exist
        const timeDisplay = document.getElementById('currentTime');
        if (timeDisplay) {
            timeDisplay.textContent = `${dateString} ${timeString}`;
        }
    }
    
    updateTime();
    setInterval(updateTime, 1000);
    
    // Initialize results counter
    updateResultsCount();
});

// Toggle between table and card view
function toggleView(viewType) {
    const tableView = document.getElementById('tableView');
    const cardView = document.getElementById('cardView');
    const tableBtn = document.querySelector('button[onclick="toggleView(\'table\')"]');
    const cardBtn = document.querySelector('button[onclick="toggleView(\'card\')"]');
    
    if (viewType === 'table') {
        tableView.style.display = 'block';
        cardView.style.display = 'none';
        
        // Update button states
        tableBtn.classList.add('btn-primary');
        tableBtn.classList.remove('btn-outline-primary');
        cardBtn.classList.add('btn-outline-primary');
        cardBtn.classList.remove('btn-primary');
    } else {
        tableView.style.display = 'none';
        cardView.style.display = 'block';
        
        // Update button states
        cardBtn.classList.add('btn-primary');
        cardBtn.classList.remove('btn-outline-primary');
        tableBtn.classList.add('btn-outline-primary');
        tableBtn.classList.remove('btn-primary');
    }
    
    // Update results count after view change
    setTimeout(() => {
        const updateEvent = new Event('input');
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.dispatchEvent(updateEvent);
        }
    }, 100);
}

// Mark appointment as completed
function markAsCompleted(recordId, patientName) {
    const modal = document.getElementById('completeModal');
    const patientNameElement = document.getElementById('patientNameComplete');
    const completeLink = document.getElementById('completeLink');
    
    if (patientNameElement) {
        patientNameElement.textContent = patientName;
    }
    
    if (completeLink) {
        completeLink.href = 'appointments_action.php?action=complete&record_id=' + recordId;
    }
    
    if (modal) {
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
    }
}

// Quick actions with keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Only trigger if not typing in an input field
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
        return;
    }
    
    if (e.ctrlKey || e.metaKey) {
        switch(e.key.toLowerCase()) {
            case 'n':
                e.preventDefault();
                window.location.href = 'appointments_action.php?action=add';
                break;
            case 'c':
                e.preventDefault();
                window.location.href = 'calendar.php';
                break;
            case 'f':
                e.preventDefault();
                const searchInput = document.getElementById('searchInput');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
                break;
            case '1':
                e.preventDefault();
                toggleView('table');
                break;
            case '2':
                e.preventDefault();
                toggleView('card');
                break;
        }
    }
    
    // ESC key to clear filters
    if (e.key === 'Escape') {
        clearAllFilters();
    }
});

// Clear all filters function
function clearAllFilters() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const doctorFilter = document.getElementById('doctorFilter');
    const dateFilter = document.getElementById('dateFilter');
    
    if (searchInput) searchInput.value = '';
    if (statusFilter) statusFilter.value = '';
    if (doctorFilter) doctorFilter.value = '';
    if (dateFilter) dateFilter.value = '';
    
    // Trigger filter update
    if (searchInput) {
        const event = new Event('input');
        searchInput.dispatchEvent(event);
    }
}

// Export appointments data
function exportAppointments() {
    const tableRows = document.querySelectorAll('#appointmentsTable tbody tr[style=""], #appointmentsTable tbody tr:not([style])');
    let csvContent = "Patient Name,Appointment Date,Doctor,Last Visit,Follow-up For,Status\n";
    
    tableRows.forEach(row => {
        const cells = row.cells;
        const patientName = cells[0].querySelector('h6').textContent.trim();
        const appointmentDate = cells[1].querySelector('span').textContent.trim();
        const doctor = cells[2].textContent.trim();
        const lastVisit = cells[3].textContent.trim();
        const followUp = cells[4].textContent.trim();
        const status = cells[5].textContent.trim();
        
        csvContent += `"${patientName}","${appointmentDate}","${doctor}","${lastVisit}","${followUp}","${status}"\n`;
    });
    
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `appointments_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    window.URL.revokeObjectURL(url);
}

// Print appointments list
function printAppointments() {
    const printWindow = window.open('', '_blank');
    const currentDate = new Date().toLocaleDateString('th-TH');
    
    printWindow.document.write(`
        <html>
        <head>
            <title>Appointments List - ${currentDate}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .header { text-align: center; margin-bottom: 20px; }
                .status-overdue { color: #dc3545; font-weight: bold; }
                .status-today { color: #28a745; font-weight: bold; }
                .status-upcoming { color: #ffc107; font-weight: bold; }
                .status-scheduled { color: #17a2b8; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>Appointments List</h2>
                <p>Generated on: ${currentDate}</p>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Appointment Date</th>
                        <th>Doctor</th>
                        <th>Last Visit</th>
                        <th>Follow-up For</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
    `);
    
    const visibleRows = document.querySelectorAll('#appointmentsTable tbody tr[style=""], #appointmentsTable tbody tr:not([style])');
    visibleRows.forEach(row => {
        const cells = row.cells;
        const status = row.getAttribute('data-status');
        
        printWindow.document.write(`
            <tr>
                <td>${cells[0].querySelector('h6').textContent}</td>
                <td>${cells[1].querySelector('span').textContent}</td>
                <td>${cells[2].textContent}</td>
                <td>${cells[3].textContent}</td>
                <td>${cells[4].textContent}</td>
                <td class="status-${status}">${cells[5].textContent}</td>
            </tr>
        `);
    });
    
    printWindow.document.write(`
                </tbody>
            </table>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.print();
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Console welcome message
console.log('📅 Appointments System Loaded Successfully');
console.log('🔍 Keyboard Shortcuts:');
console.log('   Ctrl+N - New Appointment');
console.log('   Ctrl+C - Calendar View');
console.log('   Ctrl+F - Focus Search');
console.log('   Ctrl+1 - Table View');
console.log('   Ctrl+2 - Card View');
console.log('   ESC    - Clear Filters');
console.log('💡 Auto-refresh every 5 minutes');
</script>

<?php include '../includes/footer.php'; ?>