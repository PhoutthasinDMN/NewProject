<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

// ตั้งค่าสำหรับ includes
$assets_path = '../assets/';
$page_title = 'Appointment Management';

// ตรวจสอบสิทธิ์ผู้ใช้
$user_id = $_SESSION['user_id'];
try {
    $sql = "SELECT username, email, role FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // **สำคัญ**: ปิด result set และ statement
    $result->free();
    $stmt->close();
    
    $isAdmin = ($user['role'] == 'admin');
    
} catch (Exception $e) {
    error_log("Error getting user info: " . $e->getMessage());
    header("Location: ../auth/login.php");
    exit;
}

$success_message = '';
$error_message = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$record_id = isset($_GET['record_id']) ? intval($_GET['record_id']) : 0;

// ดึงข้อมูลหมอทั้งหมด
$doctors = [];
try {
    $doctors_result = $conn->query("SELECT id, first_name, last_name, specialization FROM doctors ORDER BY first_name, last_name");
    if ($doctors_result) {
        $doctors = $doctors_result->fetch_all(MYSQLI_ASSOC);
        $doctors_result->free(); // ปิด result set
    }
} catch (Exception $e) {
    error_log("Error fetching doctors: " . $e->getMessage());
}

// ดึงข้อมูลพยาบาลที่ active สำหรับ dropdown
$nurses = [];
try {
    $nurses_result = $conn->query("SELECT id, first_name, last_name, department FROM nurses WHERE status = 'Active' ORDER BY first_name, last_name");
    if ($nurses_result) {
        $nurses = $nurses_result->fetch_all(MYSQLI_ASSOC);
        $nurses_result->free(); // ปิด result set
    }
} catch (Exception $e) {
    error_log("Error fetching nurses: " . $e->getMessage());
}

// ดึงข้อมูลผู้ป่วยทั้งหมด
$patients = [];
try {
    $patients_result = $conn->query("SELECT id, first_name, last_name, phone, age FROM patients ORDER BY first_name, last_name");
    if ($patients_result) {
        $patients = $patients_result->fetch_all(MYSQLI_ASSOC);
        $patients_result->free(); // ปิด result set
    }
} catch (Exception $e) {
    error_log("Error fetching patients: " . $e->getMessage());
}

// Handle Complete Appointment
if ($action == 'complete' && $record_id > 0) {
    try {
        // ดึงข้อมูล appointment ก่อนทำการ complete
        $appointment_stmt = $conn->prepare("SELECT mr.*, p.first_name, p.last_name, p.age, p.phone FROM medical_records mr JOIN patients p ON mr.patient_id = p.id WHERE mr.id = ?");
        if (!$appointment_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $appointment_stmt->bind_param("i", $record_id);
        $appointment_stmt->execute();
        $appointment_result = $appointment_stmt->get_result();
        $appointment_data = $appointment_result->fetch_assoc();
        
        $appointment_result->free();
        $appointment_stmt->close();
        
        if (!$appointment_data) {
            throw new Exception("Appointment not found");
        }
        
        // ทำการ complete appointment
        $complete_stmt = $conn->prepare("UPDATE medical_records SET next_appointment = NULL, appointment_status = 'completed', updated_at = NOW() WHERE id = ?");
        if (!$complete_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $complete_stmt->bind_param("i", $record_id);
        $complete_stmt->execute();
        $complete_stmt->close();
        
        // Log activity
        if (function_exists('logUserActivity')) {
            logUserActivity('complete_appointment', "Completed appointment for medical record ID: {$record_id}", $appointment_data['patient_id']);
        }
        
        // เก็บข้อมูล appointment ใน session สำหรับใช้ในหน้าสร้าง medical record
        $_SESSION['completed_appointment'] = [
            'patient_id' => $appointment_data['patient_id'],
            'patient_name' => $appointment_data['first_name'] . ' ' . $appointment_data['last_name'],
            'doctor_name' => $appointment_data['doctor_name'],
            'appointment_type' => $appointment_data['appointment_type'],
            'appointment_notes' => $appointment_data['appointment_notes'],
            'original_diagnosis' => $appointment_data['diagnosis'],
            'completed_date' => date('Y-m-d H:i:s'),
            'previous_record_id' => $record_id
        ];
        
        // Redirect ไปหน้าสร้าง medical record ใหม่พร้อมข้อมูล pre-filled
        header("Location: ../medical_records/medical_record_action.php?action=add&from_appointment=1&patient_id=" . $appointment_data['patient_id']);
        exit;
        
    } catch (Exception $e) {
        error_log("Error completing appointment: " . $e->getMessage());
        $error_message = "Error completing appointment: " . $e->getMessage();
        
        // ปิด statement หากมี error
        if (isset($appointment_stmt) && $appointment_stmt) {
            $appointment_stmt->close();
        }
        if (isset($complete_stmt) && $complete_stmt) {
            $complete_stmt->close();
        }
        if (isset($appointment_result) && $appointment_result) {
            $appointment_result->free();
        }
    }
}
// Handle form submission for add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = intval($_POST['patient_id']);
    $appointment_date = $_POST['appointment_date'];
    $doctor_id = intval($_POST['doctor_id']);
    $appointment_type = trim($_POST['appointment_type']);
    $notes = trim($_POST['notes']);
    
    // Validation
    if (empty($patient_id) || empty($appointment_date) || empty($doctor_id)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Get doctor name
        $doctor_name = '';
        foreach ($doctors as $doctor) {
            if ($doctor['id'] == $doctor_id) {
                $doctor_name = 'Dr. ' . $doctor['first_name'] . ' ' . $doctor['last_name'];
                break;
            }
        }
        
        try {
            if ($action == 'add') {
                // Create new appointment
                $diagnosis = !empty($appointment_type) ? "Scheduled appointment: " . $appointment_type : "Scheduled appointment";
                
                $sql = "INSERT INTO medical_records (patient_id, visit_date, diagnosis, doctor_name, next_appointment, appointment_type, appointment_notes, appointment_status, created_at, updated_at) VALUES (?, NOW(), ?, ?, ?, ?, ?, 'scheduled', NOW(), NOW())";
                
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("isssss", $patient_id, $diagnosis, $doctor_name, $appointment_date, $appointment_type, $notes);
                
                if ($stmt->execute()) {
                    $success_message = "Appointment scheduled successfully!";
                    if (function_exists('logUserActivity')) {
                        logUserActivity('schedule_appointment', "Scheduled appointment for patient ID: {$patient_id}", $patient_id);
                    }
                    $_POST = array(); // Clear form
                } else {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                
                $stmt->close();
                
            } elseif ($action == 'edit' && $record_id > 0) {
                // Update existing appointment
                $sql = "UPDATE medical_records SET next_appointment = ?, doctor_name = ?, appointment_type = ?, appointment_notes = ?, updated_at = NOW() WHERE id = ?";
                
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("ssssi", $appointment_date, $doctor_name, $appointment_type, $notes, $record_id);
                
                if ($stmt->execute()) {
                    $success_message = "Appointment updated successfully!";
                    if (function_exists('logUserActivity')) {
                        logUserActivity('update_appointment', "Updated appointment for medical record ID: {$record_id}", null);
                    }
                } else {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                
                $stmt->close();
            }
            
        } catch (Exception $e) {
            error_log("Error processing appointment: " . $e->getMessage());
            $error_message = "Error processing appointment: " . $e->getMessage();
            
            // ปิด statement หากมี error
            if (isset($stmt) && $stmt) {
                $stmt->close();
            }
        }
    }
}

// Get appointment data for editing
$appointment_data = null;
if ($action == 'edit' && $record_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT mr.*, p.first_name, p.last_name FROM medical_records mr JOIN patients p ON mr.patient_id = p.id WHERE mr.id = ? AND mr.next_appointment IS NOT NULL");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $record_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $appointment_data = $result->fetch_assoc();
        
        // ปิด result set และ statement
        $result->free();
        $stmt->close();
        
        if (!$appointment_data) {
            $error_message = "Appointment not found.";
            $action = 'list';
        }
        
    } catch (Exception $e) {
        error_log("Error fetching appointment data: " . $e->getMessage());
        $error_message = "Error fetching appointment data.";
        $action = 'list';
        
        // ปิด statement หากมี error
        if (isset($stmt) && $stmt) {
            $stmt->close();
        }
        if (isset($result) && $result) {
            $result->free();
        }
    }
}

// Include Header และ Sidebar
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
                            <h4 class="mb-1">
                                <?php if ($action == 'add'): ?>
                                    <i class="bx bx-calendar-plus me-2 text-primary"></i>Schedule New Appointment
                                <?php elseif ($action == 'edit'): ?>
                                    <i class="bx bx-calendar-edit me-2 text-primary"></i>Edit Appointment
                                <?php else: ?>
                                    <i class="bx bx-calendar me-2 text-primary"></i>Appointment Management
                                <?php endif; ?>
                            </h4>
                            <p class="text-muted">
                                <?php if ($action == 'add'): ?>
                                    Schedule a new appointment for a patient
                                <?php elseif ($action == 'edit'): ?>
                                    Update appointment information
                                <?php else: ?>
                                    Manage patient appointments
                                <?php endif; ?>
                            </p>
                        </div>
                        <div>
                            <?php if ($action == 'add' || $action == 'edit'): ?>
                                <a href="appointments.php" class="btn btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-2"></i>Back to Appointments
                                </a>
                            <?php else: ?>
                                <a href="?action=add" class="btn btn-primary me-2">
                                    <i class="bx bx-plus me-2"></i>Schedule New
                                </a>
                                <a href="calendar.php" class="btn btn-outline-info">
                                    <i class="bx bx-calendar-alt me-2"></i>Calendar View
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($action == 'add' || $action == 'edit'): ?>
                <!-- Appointment Form -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bx bx-calendar-event me-2 text-primary"></i>
                                    <?php echo $action == 'add' ? 'Schedule New Appointment' : 'Edit Appointment'; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                
                                <?php if ($action == 'edit' && $appointment_data): ?>
                                <div class="alert alert-info mb-4">
                                    <i class="bx bx-info-circle me-2"></i>
                                    <strong>Editing appointment for:</strong> 
                                    <?php echo htmlspecialchars($appointment_data['first_name'] . ' ' . $appointment_data['last_name']); ?>
                                    <br><small>Current appointment: <?php echo $appointment_data['next_appointment'] ? date('F j, Y \a\t H:i A', strtotime($appointment_data['next_appointment'])) : 'Not set'; ?></small>
                                </div>
                                <?php endif; ?>
                                
                                <form method="POST" action="" class="row g-3" id="appointmentForm">
                                    
                                    <!-- Basic Information -->
                                    <div class="col-12">
                                        <h6 class="text-primary mb-3"><i class="bx bx-user me-2"></i>Appointment Information</h6>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="patient_id" class="form-label">Patient <span class="text-danger">*</span></label>
                                        <select class="form-select" id="patient_id" name="patient_id" required <?php echo $action == 'edit' ? 'disabled' : ''; ?>>
                                            <option value="">Select Patient</option>
                                            <?php foreach ($patients as $patient): 
                                                $selected = ($appointment_data && $appointment_data['patient_id'] == $patient['id']) || (isset($_POST['patient_id']) && $_POST['patient_id'] == $patient['id']) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo $patient['id']; ?>" <?php echo $selected; ?>>
                                                    <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name'] . ' (Age: ' . $patient['age'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if ($action == 'edit'): ?>
                                            <input type="hidden" name="patient_id" value="<?php echo $appointment_data['patient_id']; ?>">
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="appointment_date" class="form-label">Appointment Date & Time <span class="text-danger">*</span></label>
                                        <input type="datetime-local" class="form-control" id="appointment_date" name="appointment_date" 
                                               value="<?php echo $appointment_data && $appointment_data['next_appointment'] ? date('Y-m-d\TH:i', strtotime($appointment_data['next_appointment'])) : (isset($_POST['appointment_date']) ? $_POST['appointment_date'] : ''); ?>" 
                                               min="<?php echo date('Y-m-d\TH:i'); ?>" required>
                                        <div class="form-text">Select future date and time</div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="doctor_id" class="form-label">Doctor <span class="text-danger">*</span></label>
                                        <select class="form-select" id="doctor_id" name="doctor_id" required>
                                            <option value="">Select Doctor</option>
                                            <?php foreach ($doctors as $doctor): 
                                                $doctor_full_name = 'Dr. ' . $doctor['first_name'] . ' ' . $doctor['last_name'];
                                                $selected = '';
                                                if ($appointment_data && $appointment_data['doctor_name'] == $doctor_full_name) {
                                                    $selected = 'selected';
                                                } elseif (isset($_POST['doctor_id']) && $_POST['doctor_id'] == $doctor['id']) {
                                                    $selected = 'selected';
                                                }
                                            ?>
                                                <option value="<?php echo $doctor['id']; ?>" <?php echo $selected; ?>>
                                                    Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name'] . ' (' . $doctor['specialization'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="appointment_type" class="form-label">Appointment Type</label>
                                        <select class="form-select" id="appointment_type" name="appointment_type">
                                            <option value="">Select Type</option>
                                            <?php 
                                            $appointment_types = [
                                                'Follow-up Visit' => 'Follow-up Visit',
                                                'Routine Check-up' => 'Routine Check-up',
                                                'Consultation' => 'Consultation',
                                                'Emergency' => 'Emergency',
                                                'Lab Results Review' => 'Lab Results Review',
                                                'Prescription Refill' => 'Prescription Refill',
                                                'Other' => 'Other'
                                            ];
                                            
                                            foreach ($appointment_types as $value => $label):
                                                $selected = '';
                                                if ($appointment_data && $appointment_data['appointment_type'] == $value) {
                                                    $selected = 'selected';
                                                } elseif (isset($_POST['appointment_type']) && $_POST['appointment_type'] == $value) {
                                                    $selected = 'selected';
                                                }
                                            ?>
                                                <option value="<?php echo $value; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label for="notes" class="form-label">Appointment Notes</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes for the appointment..."><?php echo $appointment_data ? htmlspecialchars($appointment_data['appointment_notes']) : (isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''); ?></textarea>
                                    </div>

                                    <!-- Quick Schedule Templates -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary mb-3"><i class="bx bx-lightning me-2"></i>Quick Schedule</h6>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-outline-primary w-100 mb-2" onclick="quickSchedule('today', '14:00')">
                                                    <i class="bx bx-time me-1"></i>Today 2:00 PM
                                                </button>
                                            </div>
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-outline-primary w-100 mb-2" onclick="quickSchedule('tomorrow', '10:00')">
                                                    <i class="bx bx-time me-1"></i>Tomorrow 10:00 AM
                                                </button>
                                            </div>
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-outline-primary w-100 mb-2" onclick="quickSchedule('nextweek', '09:00')">
                                                    <i class="bx bx-time me-1"></i>Next Week 9:00 AM
                                                </button>
                                            </div>
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-outline-primary w-100 mb-2" onclick="quickSchedule('nextmonth', '11:00')">
                                                    <i class="bx bx-time me-1"></i>Next Month 11:00 AM
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="col-12 mt-4">
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <a href="appointments.php" class="btn btn-outline-secondary">
                                                <i class="bx bx-arrow-back me-2"></i>Back to Appointments
                                            </a>
                                            <div>
                                                <button type="reset" class="btn btn-outline-warning me-2">
                                                    <i class="bx bx-refresh me-2"></i>Reset Form
                                                </button>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bx bx-calendar-check me-2"></i><?php echo $action == 'add' ? 'Schedule Appointment' : 'Update Appointment'; ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Redirect to main appointments page -->
                <script>
                    window.location.href = 'appointments.php';
                </script>
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Redirecting to appointments...</p>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<style>
/* Form styling */
.form-control:focus,
.form-select:focus {
    border-color: #696cff;
    box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.25);
}

.form-check-input:checked {
    background-color: #696cff;
    border-color: #696cff;
}

/* Quick schedule buttons */
.btn-outline-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(105, 108, 255, 0.3);
}

/* Alert styling */
.alert {
    border-radius: 12px;
    border: none;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

/* Card enhancements */
.card {
    border-radius: 15px;
    border: none;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.card-header {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-bottom: 1px solid #dee2e6;
}

/* Form validation states */
.form-control.is-invalid,
.form-select.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.form-control.is-valid,
.form-select.is-valid {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .card-body {
        padding: 1rem;
    }
    
    .btn-group .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.getElementById('appointmentForm');
    if (form) {
        const requiredFields = form.querySelectorAll('[required]');
        
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                    field.classList.add('is-valid');
                }
            });
            
            // Validate appointment date is in future
            const appointmentDate = document.getElementById('appointment_date');
            if (appointmentDate.value) {
                const selectedDate = new Date(appointmentDate.value);
                const now = new Date();
                
                if (selectedDate <= now) {
                    appointmentDate.classList.add('is-invalid');
                    isValid = false;
                    
                    // Show error message
                    let errorDiv = document.getElementById('dateError');
                    if (!errorDiv) {
                        errorDiv = document.createElement('div');
                        errorDiv.id = 'dateError';
                        errorDiv.className = 'text-danger small mt-1';
                        appointmentDate.parentNode.appendChild(errorDiv);
                    }
                    errorDiv.innerHTML = '<i class="bx bx-error-circle me-1"></i>Appointment must be scheduled for a future date and time.';
                } else {
                    appointmentDate.classList.remove('is-invalid');
                    appointmentDate.classList.add('is-valid');
                    
                    const errorDiv = document.getElementById('dateError');
                    if (errorDiv) {
                        errorDiv.remove();
                    }
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                
                // Show error alert
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertDiv.innerHTML = `
                    <i class="bx bx-error-circle me-2"></i>
                    Please fix the errors in the form before submitting.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                form.insertBefore(alertDiv, form.firstChild);
                
                // Scroll to top
                form.scrollIntoView({ behavior: 'smooth' });
                
                // Auto dismiss after 5 seconds
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 5000);
            }
        });

        // Real-time validation
        requiredFields.forEach(field => {
            field.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                }
            });
        });

        // Appointment type change handler
        const typeSelect = document.getElementById('appointment_type');
        if (typeSelect) {
            typeSelect.addEventListener('change', function() {
                const notesField = document.getElementById('notes');
                if (this.value && !notesField.value) {
                    // Auto-suggest notes based on appointment type
                    const suggestions = {
                        'Follow-up Visit': 'Follow-up appointment to monitor treatment progress.',
                        'Routine Check-up': 'Regular health check-up and preventive care.',
                        'Consultation': 'Medical consultation for health concerns.',
                        'Emergency': 'Urgent medical attention required.',
                        'Lab Results Review': 'Review and discuss laboratory test results.',
                        'Prescription Refill': 'Prescription renewal and medication review.'
                    };
                    
                    if (suggestions[this.value]) {
                        notesField.value = suggestions[this.value];
                    }
                }
            });
        }
    }

    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert:not(.alert-info)');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert && alert.parentNode) {
                alert.style.transition = 'opacity 0.3s ease';
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 300);
            }
        }, 5000);
    });
});

// Quick schedule functions
function quickSchedule(when, time) {
    const appointmentDate = document.getElementById('appointment_date');
    const now = new Date();
    let targetDate = new Date();
    
    switch(when) {
        case 'today':
            targetDate = now;
            break;
        case 'tomorrow':
            targetDate.setDate(now.getDate() + 1);
            break;
        case 'nextweek':
            targetDate.setDate(now.getDate() + 7);
            break;
        case 'nextmonth':
            targetDate.setMonth(now.getMonth() + 1);
            break;
    }
    
    // Set time
    const [hours, minutes] = time.split(':');
    targetDate.setHours(parseInt(hours), parseInt(minutes), 0, 0);
    
    // Format for datetime-local input
    const year = targetDate.getFullYear();
    const month = String(targetDate.getMonth() + 1).padStart(2, '0');
    const day = String(targetDate.getDate()).padStart(2, '0');
    const hour = String(targetDate.getHours()).padStart(2, '0');
    const minute = String(targetDate.getMinutes()).padStart(2, '0');
    
    const dateTimeString = `${year}-${month}-${day}T${hour}:${minute}`;
    appointmentDate.value = dateTimeString;
    
    // Trigger validation
    appointmentDate.dispatchEvent(new Event('change'));
    appointmentDate.classList.add('is-valid');
    
    // Visual feedback
    appointmentDate.style.transition = 'all 0.3s ease';
    appointmentDate.style.backgroundColor = '#d4edda';
    setTimeout(() => {
        appointmentDate.style.backgroundColor = '';
    }, 1000);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey) {
        switch(e.key) {
            case 's':
                e.preventDefault();
                const submitBtn = document.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.click();
                break;
            case 'r':
                e.preventDefault();
                const resetBtn = document.querySelector('button[type="reset"]');
                if (resetBtn) resetBtn.click();
                break;
        }
    }
});

// Enhanced error handling for AJAX operations
function handleDatabaseError(error) {
    console.error('Database Error:', error);
    
    const errorAlert = document.createElement('div');
    errorAlert.className = 'alert alert-danger alert-dismissible fade show';
    errorAlert.innerHTML = `
        <i class="bx bx-error-circle me-2"></i>
        <strong>Database Error:</strong> ${error.message || 'An unexpected error occurred.'}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-xxl');
    if (container) {
        container.insertBefore(errorAlert, container.firstChild);
    }
    
    // Auto-dismiss after 10 seconds
    setTimeout(() => {
        if (errorAlert.parentNode) {
            errorAlert.remove();
        }
    }, 10000);
}

// Patient search functionality
function initializePatientSearch() {
    const patientSelect = document.getElementById('patient_id');
    if (patientSelect) {
        // Add search functionality
        patientSelect.addEventListener('focus', function() {
            this.setAttribute('data-original-size', this.options.length);
        });
        
        // Enhanced patient selection with additional info
        patientSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                // Could add patient details preview here
                console.log('Selected patient:', selectedOption.text);
            }
        });
    }
}

// Doctor availability checker (placeholder for future enhancement)
function checkDoctorAvailability(doctorId, appointmentDate) {
    // This would typically make an AJAX call to check availability
    console.log(`Checking availability for doctor ${doctorId} on ${appointmentDate}`);
    
    // Placeholder implementation
    return new Promise((resolve) => {
        setTimeout(() => {
            resolve({
                available: true,
                suggestedTimes: ['09:00', '10:30', '14:00', '15:30']
            });
        }, 1000);
    });
}

// Auto-save functionality for draft appointments
function initializeAutoSave() {
    const form = document.getElementById('appointmentForm');
    if (!form || form.dataset.editing === 'true') return;
    
    const formInputs = form.querySelectorAll('input:not([type="hidden"]), textarea, select');
    const draftKey = 'appointment_draft_' + Date.now();
    
    let saveTimeout;
    const autoSave = () => {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            const draftData = {};
            formInputs.forEach(input => {
                if (input.value && input.name) {
                    draftData[input.name] = input.value;
                }
            });
            
            try {
                localStorage.setItem(draftKey, JSON.stringify(draftData));
                console.log('Draft saved automatically');
            } catch (e) {
                console.warn('Failed to save draft:', e);
            }
        }, 2000);
    };
    
    formInputs.forEach(input => {
        input.addEventListener('input', autoSave);
        input.addEventListener('change', autoSave);
    });
    
    // Clear draft on successful submit
    form.addEventListener('submit', function() {
        localStorage.removeItem(draftKey);
    });
    
    // Load existing draft if available
    const savedDraft = localStorage.getItem(draftKey);
    if (savedDraft) {
        try {
            const draftData = JSON.parse(savedDraft);
            Object.keys(draftData).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input && !input.value) {
                    input.value = draftData[key];
                }
            });
        } catch (e) {
            console.warn('Failed to restore draft:', e);
        }
    }
}

// Form submission with loading state
function enhanceFormSubmission() {
    const form = document.getElementById('appointmentForm');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    if (!form || !submitBtn) return;
    
    form.addEventListener('submit', function() {
        // Show loading state
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
            Processing...
        `;
        
        // Reset button state if form validation fails
        setTimeout(() => {
            if (submitBtn.disabled) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }, 5000);
    });
}

// Initialize all enhancements
document.addEventListener('DOMContentLoaded', function() {
    initializePatientSearch();
    initializeAutoSave();
    enhanceFormSubmission();
    
    // Add tooltips for form fields
    const tooltipElements = [
        { selector: '#patient_id', title: 'Select the patient for this appointment' },
        { selector: '#appointment_date', title: 'Choose a future date and time' },
        { selector: '#doctor_id', title: 'Select the attending doctor' },
        { selector: '#appointment_type', title: 'Specify the type of appointment' }
    ];
    
    tooltipElements.forEach(item => {
        const element = document.querySelector(item.selector);
        if (element) {
            element.setAttribute('title', item.title);
            element.setAttribute('data-bs-toggle', 'tooltip');
        }
    });
    
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Utility function to format dates consistently
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Console logging for debugging
console.log('📅 Appointment Management System Loaded');
console.log('⌨️ Shortcuts: Ctrl+S (Save), Ctrl+R (Reset)');
console.log('💾 Auto-save enabled for drafts');
console.log('🔍 Enhanced form validation active');
</script>

<?php include '../includes/footer.php'; ?>