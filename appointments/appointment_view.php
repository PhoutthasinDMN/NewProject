<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

// ตรวจสอบ ID ของ appointment (record_id)
$record_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($record_id <= 0) {
    header("Location: appointments.php");
    exit;
}

// ดึงข้อมูล appointment พร้อมข้อมูลผู้ป่วย
$appointment_sql = "SELECT mr.*, p.first_name, p.last_name, p.age, p.gender, p.phone, p.email, p.address, p.nationality, p.religion, p.marital_status, p.occupation, p.emergency_contact_name, p.emergency_contact_relationship, p.emergency_contact_phone 
                    FROM medical_records mr 
                    JOIN patients p ON mr.patient_id = p.id 
                    WHERE mr.id = ? AND mr.next_appointment IS NOT NULL";
$stmt = $conn->prepare($appointment_sql);
$stmt->bind_param("i", $record_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: appointments.php");
    exit;
}

$appointment = $result->fetch_assoc();

// คำนวณสถานะ appointment
$appointment_date = new DateTime($appointment['next_appointment']);
$now = new DateTime();
$interval = $now->diff($appointment_date);

if ($appointment_date < $now) {
    $status = 'overdue';
    $status_text = 'Overdue';
    $status_class = 'danger';
    $time_diff = $interval->format('%a days ago');
} elseif ($appointment_date->format('Y-m-d') == $now->format('Y-m-d')) {
    $status = 'today';
    $status_text = 'Today';
    $status_class = 'success';
    $time_diff = 'Today';
} elseif ($interval->days <= 7) {
    $status = 'upcoming';
    $status_text = 'Upcoming';
    $status_class = 'warning';
    $time_diff = 'In ' . $interval->format('%a days');
} else {
    $status = 'scheduled';
    $status_text = 'Scheduled';
    $status_class = 'info';
    $time_diff = 'In ' . $interval->format('%a days');
}

// ดึงข้อมูล medical records อื่นๆ ของผู้ป่วยคนเดียวกัน
$other_records_sql = "SELECT id, visit_date, diagnosis, doctor_name, next_appointment FROM medical_records WHERE patient_id = ? AND id != ? ORDER BY visit_date DESC LIMIT 5";
$stmt = $conn->prepare($other_records_sql);
$stmt->bind_param("ii", $appointment['patient_id'], $record_id);
$stmt->execute();
$other_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ดึงข้อมูล appointments อื่นๆ ของผู้ป่วยคนเดียวกัน
$other_appointments_sql = "SELECT id, next_appointment, diagnosis, doctor_name FROM medical_records WHERE patient_id = ? AND id != ? AND next_appointment IS NOT NULL AND next_appointment > NOW() ORDER BY next_appointment ASC LIMIT 3";
$stmt = $conn->prepare($other_appointments_sql);
$stmt->bind_param("ii", $appointment['patient_id'], $record_id);
$stmt->execute();
$other_appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ตั้งค่าสำหรับ includes
$assets_path = '../assets/';
$page_title = 'Appointment Details';

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Layout Page -->
<div class="layout-page">
    <!-- Content wrapper -->
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            
            <!-- Header -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-1">Appointment Details</h4>
                            <p class="text-muted">Complete appointment information and management</p>
                        </div>
                        <div>
                            <button id="printBtn" class="btn btn-secondary me-2">
                                <i class="bx bx-printer me-1"></i> Print
                            </button>
                            <a href="appointments_action.php?action=edit&record_id=<?php echo $appointment['id']; ?>" class="btn btn-primary me-2">
                                <i class="bx bx-edit me-1"></i> Edit Appointment
                            </a>
                            <button type="button" class="btn btn-success me-2" onclick="markAsCompleted(<?php echo $appointment['id']; ?>, '<?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?>')">
                                <i class="bx bx-check me-1"></i> Mark Completed
                            </button>
                            <a href="appointments.php" class="btn btn-outline-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Patient Information -->
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bx bx-user me-2 text-primary"></i>Patient Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <div class="patient-avatar mx-auto mb-3" style="width: 80px; height: 80px; font-size: 32px;">
                                    <?php echo strtoupper(substr($appointment['first_name'], 0, 1) . substr($appointment['last_name'], 0, 1)); ?>
                                </div>
                                <h5 class="mb-1"><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></h5>
                                <p class="text-muted mb-0">Patient ID: <?php echo str_pad($appointment['patient_id'], 4, '0', STR_PAD_LEFT); ?></p>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">Age</div>
                                <div class="info-value"><?php echo $appointment['age']; ?> years old</div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">Gender</div>
                                <div class="info-value">
                                    <?php 
                                    $gender_full = ['M' => 'Male', 'F' => 'Female', 'O' => 'Other'];
                                    echo $gender_full[$appointment['gender']] ?? $appointment['gender']; 
                                    ?>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">Phone</div>
                                <div class="info-value">
                                    <?php if ($appointment['phone']): ?>
                                        <a href="tel:<?php echo $appointment['phone']; ?>" class="text-decoration-none">
                                            <i class="bx bx-phone me-1"></i><?php echo htmlspecialchars($appointment['phone']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Not provided</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($appointment['email']): ?>
                            <div class="info-row">
                                <div class="info-label">Email</div>
                                <div class="info-value">
                                    <a href="mailto:<?php echo $appointment['email']; ?>" class="text-decoration-none">
                                        <i class="bx bx-envelope me-1"></i><?php echo htmlspecialchars($appointment['email']); ?>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="info-row">
                                <div class="info-label">Address</div>
                                <div class="info-value"><?php echo htmlspecialchars($appointment['address']); ?></div>
                            </div>
                            
                            <?php if ($appointment['emergency_contact_name']): ?>
                            <hr class="my-3">
                            <h6 class="text-danger mb-3">Emergency Contact</h6>
                            <div class="info-row">
                                <div class="info-label">Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($appointment['emergency_contact_name']); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Relationship</div>
                                <div class="info-value"><?php echo htmlspecialchars($appointment['emergency_contact_relationship']); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Phone</div>
                                <div class="info-value">
                                    <a href="tel:<?php echo $appointment['emergency_contact_phone']; ?>" class="text-decoration-none">
                                        <i class="bx bx-phone me-1"></i><?php echo htmlspecialchars($appointment['emergency_contact_phone']); ?>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Appointment Details -->
                <div class="col-lg-8 mb-4">
                    <div class="card h-100" id="appointment-card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bx bx-calendar-event me-2 text-success"></i>Appointment Details</h5>
                                <span class="badge bg-<?php echo $status_class; ?> fs-6"><?php echo $status_text; ?></span>
                            </div>
                            <small class="text-muted">Appointment ID: #<?php echo str_pad($appointment['id'], 6, '0', STR_PAD_LEFT); ?></small>
                        </div>
                        <div class="card-body">
                            
                            <!-- Appointment Time Information -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="appointment-time-card p-4 bg-light border border-primary rounded">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6 class="text-primary mb-2">Appointment Date & Time</h6>
                                                <h4 class="mb-1"><?php echo date('F j, Y', strtotime($appointment['next_appointment'])); ?></h4>
                                                <h5 class="text-muted"><?php echo date('l \a\t H:i A', strtotime($appointment['next_appointment'])); ?></h5>
                                            </div>
                                            <div class="col-md-6 text-md-end">
                                                <h6 class="text-primary mb-2">Time Until Appointment</h6>
                                                <h4 class="mb-1 text-<?php echo $status_class; ?>"><?php echo $time_diff; ?></h4>
                                                <p class="text-muted mb-0">
                                                    <?php if ($status == 'today'): ?>
                                                        <i class="bx bx-time-five me-1"></i>Today's appointment
                                                    <?php elseif ($status == 'overdue'): ?>
                                                        <i class="bx bx-error-circle me-1"></i>Overdue appointment
                                                    <?php else: ?>
                                                        <i class="bx bx-calendar-check me-1"></i>Scheduled appointment
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Appointment Information -->
                            <h6 class="text-primary mb-3"><i class="bx bx-info-circle me-2"></i>Appointment Information</h6>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <div class="info-label">Attending Doctor</div>
                                        <div class="info-value">
                                            <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($appointment['doctor_name']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <div class="info-label">Last Visit</div>
                                        <div class="info-value">
                                            <span><?php echo date('M j, Y', strtotime($appointment['visit_date'])); ?></span>
                                            <br><small class="text-muted"><?php echo date('H:i A', strtotime($appointment['visit_date'])); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="info-row mb-3">
                                <div class="info-label">Follow-up For</div>
                                <div class="info-value">
                                    <div class="diagnosis-highlight p-3 bg-light border-start border-primary border-4">
                                        <?php echo nl2br(htmlspecialchars($appointment['diagnosis'])); ?>
                                    </div>
                                </div>
                            </div>

                            <?php if ($appointment['symptoms']): ?>
                            <div class="info-row mb-3">
                                <div class="info-label">Previous Symptoms</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($appointment['symptoms'])); ?></div>
                            </div>
                            <?php endif; ?>

                            <?php if ($appointment['treatment']): ?>
                            <div class="info-row mb-3">
                                <div class="info-label">Previous Treatment</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($appointment['treatment'])); ?></div>
                            </div>
                            <?php endif; ?>

                            <?php if ($appointment['prescription']): ?>
                            <div class="info-row mb-3">
                                <div class="info-label">Current Prescription</div>
                                <div class="info-value">
                                    <div class="prescription-box p-3 bg-warning bg-opacity-10 border border-warning rounded">
                                        <i class="bx bx-capsule me-2 text-warning"></i>
                                        <?php echo nl2br(htmlspecialchars($appointment['prescription'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($appointment['notes']): ?>
                            <div class="info-row mb-3">
                                <div class="info-label">Additional Notes</div>
                                <div class="info-value">
                                    <div class="notes-box p-3 bg-secondary bg-opacity-10 border border-secondary rounded">
                                        <?php echo nl2br(htmlspecialchars($appointment['notes'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Quick Actions -->
                            <hr class="my-4">
                            <h6 class="text-primary mb-3"><i class="bx bx-cog me-2"></i>Quick Actions</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <a href="../medical_records/medical_record_view.php?id=<?php echo $appointment['id']; ?>" class="btn btn-outline-info w-100 mb-2">
                                        <i class="bx bx-file-blank me-2"></i>View Medical Record
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="../medical_records/medical_record_action.php?action=add&patient_id=<?php echo $appointment['patient_id']; ?>" class="btn btn-outline-success w-100 mb-2">
                                        <i class="bx bx-plus me-2"></i>New Medical Record
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="appointments_action.php?action=add&patient_id=<?php echo $appointment['patient_id']; ?>" class="btn btn-outline-warning w-100 mb-2">
                                        <i class="bx bx-calendar-plus me-2"></i>Schedule Follow-up
                                    </a>
                                </div>
                            </div>

                            <!-- Record Metadata -->
                            <hr class="my-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <div class="info-label">Record Created</div>
                                        <div class="info-value text-muted"><?php echo date('F j, Y \a\t H:i', strtotime($appointment['created_at'])); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <div class="info-label">Last Updated</div>
                                        <div class="info-value text-muted"><?php echo date('F j, Y \a\t H:i', strtotime($appointment['updated_at'])); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Records & Appointments -->
            <div class="row">
                <!-- Medical History -->
                <?php if (!empty($other_records)): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bx bx-history me-2 text-info"></i>Medical History</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <?php foreach ($other_records as $record): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-info"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1"><?php echo date('M j, Y', strtotime($record['visit_date'])); ?></h6>
                                        <p class="mb-1"><?php echo htmlspecialchars(substr($record['diagnosis'], 0, 60) . (strlen($record['diagnosis']) > 60 ? '...' : '')); ?></p>
                                        <small class="text-muted"><?php echo htmlspecialchars($record['doctor_name']); ?></small>
                                        <div class="mt-2">
                                            <a href="../medical_records/medical_record_view.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bx bx-show"></i> View
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Other Appointments -->
                <?php if (!empty($other_appointments)): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bx bx-calendar-alt me-2 text-warning"></i>Other Appointments</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($other_appointments as $other_appointment): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded">
                                <div>
                                    <h6 class="mb-1"><?php echo date('M j, Y \a\t H:i A', strtotime($other_appointment['next_appointment'])); ?></h6>
                                    <p class="mb-1"><?php echo htmlspecialchars(substr($other_appointment['diagnosis'], 0, 40) . (strlen($other_appointment['diagnosis']) > 40 ? '...' : '')); ?></p>
                                    <small class="text-muted"><?php echo htmlspecialchars($other_appointment['doctor_name']); ?></small>
                                </div>
                                <div>
                                    <a href="appointment_view.php?id=<?php echo $other_appointment['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bx bx-show"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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
                    This will remove the appointment from the schedule and optionally create a new medical record.
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
.patient-avatar {
    background: linear-gradient(135deg, #696cff, #5a67d8);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.info-row {
    margin-bottom: 15px;
}

.info-label {
    font-weight: 600;
    margin-bottom: 5px;
    color: #333;
    font-size: 0.875rem;
}

.info-value {
    color: #697a8d;
    line-height: 1.5;
}

.appointment-time-card {
    background: linear-gradient(135deg, #f8f9ff, #e8eaff);
    border: 2px solid #696cff !important;
}

.diagnosis-highlight {
    font-weight: 500;
    color: #333;
}

.prescription-box {
    font-family: 'Courier New', monospace;
}

.timeline {
    position: relative;
    padding-left: 20px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
    padding-left: 20px;
}

.timeline-item:before {
    content: '';
    position: absolute;
    left: -8px;
    top: 0;
    bottom: -20px;
    width: 2px;
    background: #e9ecef;
}

.timeline-item:last-child:before {
    display: none;
}

.timeline-marker {
    position: absolute;
    left: -12px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-content {
    background: white;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

/* Status-based styling */
.badge.fs-6 {
    font-size: 0.9rem !important;
    padding: 0.5rem 1rem;
}

/* Print styles */
@media print {
    .btn, .modal {
        display: none !important;
    }
    
    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
    
    .timeline-item:before {
        background: #000 !important;
    }
    
    .timeline-marker {
        background: #000 !important;
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .appointment-time-card {
        text-align: center;
    }
    
    .appointment-time-card .col-md-6:last-child {
        margin-top: 1rem;
        text-align: center !important;
    }
    
    .timeline {
        padding-left: 15px;
    }
    
    .timeline-item {
        padding-left: 15px;
    }
}

/* Enhanced card hover effects */
.card:hover {
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    transition: box-shadow 0.3s ease;
}

/* Status indicator animations */
@keyframes pulse-danger {
    0%, 100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
    50% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
}

@keyframes pulse-success {
    0%, 100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4); }
    50% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
}

.badge.bg-danger {
    animation: pulse-danger 2s infinite;
}

.badge.bg-success {
    animation: pulse-success 3s infinite;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Print functionality
    document.getElementById('printBtn').addEventListener('click', function() {
        window.print();
    });

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Real-time countdown for today's appointments
    const status = '<?php echo $status; ?>';
    if (status === 'today') {
        updateCountdown();
        setInterval(updateCountdown, 60000); // Update every minute
    }

    // Auto-refresh if appointment is overdue
    if (status === 'overdue') {
        setTimeout(() => {
            if (confirm('This appointment is overdue. Would you like to refresh the page to check for updates?')) {
                location.reload();
            }
        }, 5000);
    }

    // Enhanced interactions
    const timelineItems = document.querySelectorAll('.timeline-item');
    timelineItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });

    // Quick action keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
                case 'e':
                    e.preventDefault();
                    window.location.href = 'appointments_action.php?action=edit&record_id=<?php echo $appointment['id']; ?>';
                    break;
                case 'c':
                    e.preventDefault();
                    document.querySelector('button[onclick^="markAsCompleted"]').click();
                    break;
                case 'p':
                    e.preventDefault();
                    window.print();
                    break;
                case 'm':
                    e.preventDefault();
                    window.location.href = '../medical_records/medical_record_view.php?id=<?php echo $appointment['id']; ?>';
                    break;
            }
        }
    });

    // Status change notifications
    checkStatusChange();
    setInterval(checkStatusChange, 300000); // Check every 5 minutes
});

function updateCountdown() {
    const appointmentTime = new Date('<?php echo $appointment['next_appointment']; ?>');
    const now = new Date();
    const diff = appointmentTime - now;
    
    if (diff > 0) {
        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        
        if (hours > 0) {
            document.querySelector('.text-success h4').textContent = `In ${hours}h ${minutes}m`;
        } else {
            document.querySelector('.text-success h4').textContent = `In ${minutes}m`;
        }
        
        // Alert 30 minutes before
        if (diff <= 30 * 60 * 1000 && diff > 29 * 60 * 1000) {
            showNotification('Appointment starting in 30 minutes!', 'warning');
        }
        
        // Alert 5 minutes before
        if (diff <= 5 * 60 * 1000 && diff > 4 * 60 * 1000) {
            showNotification('Appointment starting in 5 minutes!', 'danger');
        }
    } else if (diff > -60 * 60 * 1000) { // Within 1 hour past
        const overdue = Math.abs(diff);
        const minutes = Math.floor(overdue / (1000 * 60));
        document.querySelector('.text-danger h4').textContent = `${minutes} minutes overdue`;
    }
}

function checkStatusChange() {
    // Check if appointment status has changed
    const now = new Date();
    const appointmentTime = new Date('<?php echo $appointment['next_appointment']; ?>');
    const currentStatus = '<?php echo $status; ?>';
    
    let newStatus = '';
    if (appointmentTime < now) {
        newStatus = 'overdue';
    } else if (appointmentTime.toDateString() === now.toDateString()) {
        newStatus = 'today';
    } else {
        newStatus = 'scheduled';
    }
    
    if (newStatus !== currentStatus) {
        showNotification('Appointment status has changed. Refreshing page...', 'info');
        setTimeout(() => {
            location.reload();
        }, 2000);
    }
}

function showNotification(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 1050; min-width: 300px;';
    alertDiv.innerHTML = `
        <i class="bx bx-bell me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.style.opacity = '0';
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 300);
        }
    }, 5000);
}

// Mark appointment as completed
function markAsCompleted(recordId, patientName) {
    document.getElementById('patientNameComplete').textContent = patientName;
    
    const createRecordCheckbox = document.getElementById('createRecordCheckbox');
    const completeLink = document.getElementById('completeLink');
    
    // Update link based on checkbox
    function updateLink() {
        const baseUrl = `appointments_action.php?action=complete&record_id=${recordId}`;
        const createRecord = createRecordCheckbox.checked ? '&create_record=1' : '';
        completeLink.href = baseUrl + createRecord;
    }
    
    createRecordCheckbox.addEventListener('change', updateLink);
    updateLink(); // Set initial link
    
    const modal = new bootstrap.Modal(document.getElementById('completeModal'));
    modal.show();
}

// Enhanced contact actions
function callPatient() {
    const phone = '<?php echo $appointment['phone']; ?>';
    if (phone) {
        window.location.href = `tel:${phone}`;
    } else {
        alert('No phone number available for this patient.');
    }
}

function emailPatient() {
    const email = '<?php echo $appointment['email']; ?>';
    if (email) {
        const subject = encodeURIComponent('Regarding your appointment');
        const body = encodeURIComponent(`Dear ${<?php echo json_encode($appointment['first_name']); ?>},\n\nThis is regarding your upcoming appointment on <?php echo date('F j, Y \a\t H:i A', strtotime($appointment['next_appointment'])); ?>.\n\nBest regards,\nMedical Team`);
        window.location.href = `mailto:${email}?subject=${subject}&body=${body}`;
    } else {
        alert('No email address available for this patient.');
    }
}

// Add quick contact buttons if not already present
document.addEventListener('DOMContentLoaded', function() {
    const quickActionsRow = document.querySelector('.row:has(.btn-outline-info)');
    if (quickActionsRow && <?php echo $appointment['phone'] ? 'true' : 'false'; ?>) {
        const phoneBtn = document.createElement('div');
        phoneBtn.className = 'col-md-4';
        phoneBtn.innerHTML = `
            <button onclick="callPatient()" class="btn btn-outline-success w-100 mb-2">
                <i class="bx bx-phone me-2"></i>Call Patient
            </button>
        `;
        quickActionsRow.appendChild(phoneBtn);
    }
    
    if (quickActionsRow && <?php echo $appointment['email'] ? 'true' : 'false'; ?>) {
        const emailBtn = document.createElement('div');
        emailBtn.className = 'col-md-4';
        emailBtn.innerHTML = `
            <button onclick="emailPatient()" class="btn btn-outline-info w-100 mb-2">
                <i class="bx bx-envelope me-2"></i>Email Patient
            </button>
        `;
        quickActionsRow.appendChild(emailBtn);
    }
});

console.log('👩‍⚕️ Appointment Details Loaded');
console.log('⌨️ Shortcuts: Ctrl+E (Edit), Ctrl+C (Complete), Ctrl+P (Print), Ctrl+M (Medical Record)');
console.log('📱 Status: <?php echo ucfirst($status); ?>');
</script>

<?php include '../includes/footer.php'; ?>