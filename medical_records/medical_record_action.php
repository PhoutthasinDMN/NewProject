<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

// ตั้งค่าสำหรับ includes
$assets_path = '../assets/';
$page_title = 'Medical Records Management';
$extra_css = ['../assets/css/dashboard.css'];

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
    
    // ปิด result set และ statement
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
$record_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// **เพิ่ม**: ตรวจสอบว่ามาจาก completed appointment หรือไม่
$from_appointment = isset($_GET['from_appointment']) ? true : false;
$pre_filled_data = [];

if ($from_appointment && isset($_SESSION['completed_appointment'])) {
    $pre_filled_data = $_SESSION['completed_appointment'];
    
    // แสดงข้อความแจ้งเตือน
    $success_message = "Appointment completed! Please record the visit details below.";
}

// ถ้าเป็นการสร้าง medical record ใหม่จาก completed appointment
if ($action == 'add' && $from_appointment && !empty($pre_filled_data)) {
    // Pre-fill ข้อมูลในฟอร์ม
    $default_patient_id = $pre_filled_data['patient_id'];
    $default_doctor = $pre_filled_data['doctor_name'];
    $default_visit_type = 'Follow-up Visit'; // จาก appointment
    $default_chief_complaint = $pre_filled_data['appointment_type'] ? 
        "Scheduled " . $pre_filled_data['appointment_type'] : 
        "Follow-up appointment";
    $default_notes = "Appointment completed on " . date('M j, Y \a\t H:i A', strtotime($pre_filled_data['completed_date'])) . 
                    ($pre_filled_data['appointment_notes'] ? "\nAppointment notes: " . $pre_filled_data['appointment_notes'] : "");
}

// ดึงข้อมูลหมอทั้งหมดสำหรับ dropdown
$doctors = [];
try {
    $doctors_result = $conn->query("SELECT id, first_name, last_name, specialization FROM doctors ORDER BY first_name, last_name");
    if ($doctors_result) {
        $doctors = $doctors_result->fetch_all(MYSQLI_ASSOC);
        $doctors_result->free();
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
        $nurses_result->free();
    }
} catch (Exception $e) {
    error_log("Error fetching nurses: " . $e->getMessage());
}

// ดึงข้อมูลผู้ป่วยทั้งหมดสำหรับ dropdown
$patients = [];
try {
    $patients_result = $conn->query("SELECT id, first_name, last_name, age FROM patients ORDER BY first_name, last_name");
    if ($patients_result) {
        $patients = $patients_result->fetch_all(MYSQLI_ASSOC);
        $patients_result->free();
    }
} catch (Exception $e) {
    error_log("Error fetching patients: " . $e->getMessage());
}

// Handle delete action - เฉพาะ Admin เท่านั้น
if ($action == 'delete' && $record_id > 0) {
    if (!$isAdmin) {
        $error_message = "Access denied. Only administrators can delete medical records.";
    } else {
        try {
            $stmt = $conn->prepare("DELETE FROM medical_records WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("i", $record_id);
            
            if ($stmt->execute()) {
                $success_message = "Medical record deleted successfully!";
                $action = 'list';
                
                // Log การลบ (ถ้ามี logging system)
                if (function_exists('logUserActivity')) {
                    logUserActivity('delete_medical_record', "Deleted medical record ID: {$record_id}", null);
                }
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $stmt->close();
            
        } catch (Exception $e) {
            error_log("Error deleting medical record: " . $e->getMessage());
            $error_message = "Error deleting medical record. Please try again.";
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = intval($_POST['patient_id']);
    $visit_date = $_POST['visit_date'];
    $diagnosis = trim($_POST['diagnosis']);
    $symptoms = trim($_POST['symptoms']);
    $treatment = trim($_POST['treatment']);
    $prescription = trim($_POST['prescription']);
    $notes = trim($_POST['notes']);
    $doctor_id = intval($_POST['doctor_id']);
    $nurse_id = intval($_POST['nurse_id']);
    $next_appointment = !empty($_POST['next_appointment']) ? $_POST['next_appointment'] : null;
    
    // **เพิ่ม**: ข้อมูลจาก completed appointment
    $appointment_outcome = isset($_POST['appointment_outcome']) ? $_POST['appointment_outcome'] : null;
    $next_action = isset($_POST['next_action']) ? $_POST['next_action'] : null;
    $from_appointment_flag = isset($_POST['from_appointment']) ? $_POST['from_appointment'] : null;
    $previous_record_id = isset($_POST['previous_record_id']) ? intval($_POST['previous_record_id']) : null;
    
    // Vital Signs
    $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
    $height = !empty($_POST['height']) ? floatval($_POST['height']) : null;
    $pulse_rate = !empty($_POST['pulse_rate']) ? intval($_POST['pulse_rate']) : null;
    $blood_pressure_systolic = !empty($_POST['blood_pressure_systolic']) ? intval($_POST['blood_pressure_systolic']) : null;
    $blood_pressure_diastolic = !empty($_POST['blood_pressure_diastolic']) ? intval($_POST['blood_pressure_diastolic']) : null;
    $temperature = !empty($_POST['temperature']) ? floatval($_POST['temperature']) : null;
    
    // Validation
    if (empty($patient_id) || empty($diagnosis) || empty($doctor_id)) {
        $error_message = "Please fill in all required fields (Patient, Diagnosis, and Doctor).";
    } else {
        // ดึงชื่อหมอ
        $doctor_name = '';
        foreach ($doctors as $doctor) {
            if ($doctor['id'] == $doctor_id) {
                $doctor_name = 'Dr. ' . $doctor['first_name'] . ' ' . $doctor['last_name'];
                break;
            }
        }
        
        // ดึงชื่อพยาบาล (ถ้ามี)
        $nurse_name = '';
        if ($nurse_id > 0) {
            foreach ($nurses as $nurse) {
                if ($nurse['id'] == $nurse_id) {
                    $nurse_name = $nurse['first_name'] . ' ' . $nurse['last_name'];
                    break;
                }
            }
        }
        
        try {
            if ($action == 'add') {
                // Insert new medical record
                $sql = "INSERT INTO medical_records (patient_id, visit_date, diagnosis, symptoms, treatment, prescription, notes, doctor_name, weight, height, pulse_rate, blood_pressure_systolic, blood_pressure_diastolic, temperature, next_appointment, appointment_outcome, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("isssssssddiiidss", $patient_id, $visit_date, $diagnosis, $symptoms, $treatment, $prescription, $notes, $doctor_name, $weight, $height, $pulse_rate, $blood_pressure_systolic, $blood_pressure_diastolic, $temperature, $next_appointment, $appointment_outcome);
                
                if ($stmt->execute()) {
                    $new_record_id = $conn->insert_id;
                    
                    // If nurse was selected, add a note about it
                    if (!empty($nurse_name)) {
                        $updated_notes = $notes . "\n\nAssisted by Nurse: " . $nurse_name;
                        $update_notes_sql = "UPDATE medical_records SET notes = ? WHERE id = ?";
                        $update_stmt = $conn->prepare($update_notes_sql);
                        $update_stmt->bind_param("si", $updated_notes, $new_record_id);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                    
                    // **เพิ่ม**: ถ้ามาจาก completed appointment
                    if ($from_appointment_flag == '1' && $previous_record_id) {
                        // อัปเดต appointment outcome ใน medical record เดิม
                        if ($appointment_outcome) {
                            $update_stmt = $conn->prepare("UPDATE medical_records SET appointment_outcome = ? WHERE id = ?");
                            $update_stmt->bind_param("si", $appointment_outcome, $previous_record_id);
                            $update_stmt->execute();
                            $update_stmt->close();
                        }
                        
                        // ล้าง session data
                        unset($_SESSION['completed_appointment']);
                        
                        $success_message = "Medical record created successfully from completed appointment!";
                        
                        // Redirect ไปหน้าดู record ที่สร้างใหม่
                        header("Location: medical_record_view.php?id={$new_record_id}&from_appointment=1");
                        exit;
                    } else {
                        $success_message = "Medical record added successfully!";
                    }
                    
                    $_POST = array(); // Clear form
                    
                    // Log การเพิ่ม
                    if (function_exists('logUserActivity')) {
                        logUserActivity('add_medical_record', "Added new medical record for patient ID: {$patient_id}", $patient_id);
                    }
                } else {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                
                $stmt->close();
                
            } elseif ($action == 'edit' && $record_id > 0) {
                // Update medical record
                $sql = "UPDATE medical_records SET patient_id = ?, visit_date = ?, diagnosis = ?, symptoms = ?, treatment = ?, prescription = ?, notes = ?, doctor_name = ?, weight = ?, height = ?, pulse_rate = ?, blood_pressure_systolic = ?, blood_pressure_diastolic = ?, temperature = ?, next_appointment = ?, updated_at = NOW() WHERE id = ?";
                
                // If nurse was selected, add nurse info to notes
                $final_notes = $notes;
                if (!empty($nurse_name)) {
                    $final_notes = $notes . "\n\nAssisted by Nurse: " . $nurse_name;
                }
                
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("isssssssddiiidsi", $patient_id, $visit_date, $diagnosis, $symptoms, $treatment, $prescription, $final_notes, $doctor_name, $weight, $height, $pulse_rate, $blood_pressure_systolic, $blood_pressure_diastolic, $temperature, $next_appointment, $record_id);
                
                if ($stmt->execute()) {
                    $success_message = "Medical record updated successfully!";
                    
                    // Log การแก้ไข
                    if (function_exists('logUserActivity')) {
                        logUserActivity('edit_medical_record', "Updated medical record ID: {$record_id}", $patient_id);
                    }
                } else {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                
                $stmt->close();
            }
            
        } catch (Exception $e) {
            error_log("Error processing medical record: " . $e->getMessage());
            $error_message = "Error processing medical record: " . $e->getMessage();
        }
    }
}

// Get medical record data for editing or viewing
$record_data = null;
if (($action == 'edit' || $action == 'view') && $record_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT mr.*, p.first_name as patient_first_name, p.last_name as patient_last_name FROM medical_records mr JOIN patients p ON mr.patient_id = p.id WHERE mr.id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $record_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $record_data = $result->fetch_assoc();
        
        $result->free();
        $stmt->close();
        
        if (!$record_data) {
            $error_message = "Medical record not found.";
            $action = 'list';
        }
        
    } catch (Exception $e) {
        error_log("Error fetching medical record: " . $e->getMessage());
        $error_message = "Error fetching medical record.";
        $action = 'list';
    }
}

// Get all medical records for listing
$medical_records = [];
if ($action == 'list') {
    try {
        $result = $conn->query("SELECT mr.*, p.first_name, p.last_name FROM medical_records mr JOIN patients p ON mr.patient_id = p.id ORDER BY mr.visit_date DESC");
        if ($result) {
            $medical_records = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
        }
    } catch (Exception $e) {
        error_log("Error fetching medical records: " . $e->getMessage());
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
                                    <i class="bx bx-file-plus me-2 text-primary"></i>Add New Medical Record
                                <?php elseif ($action == 'edit'): ?>
                                    <i class="bx bx-edit me-2 text-primary"></i>Edit Medical Record
                                <?php else: ?>
                                    <i class="bx bx-file-blank me-2 text-primary"></i>Medical Records Management
                                <?php endif; ?>
                            </h4>
                            <p class="text-muted">
                                <?php if ($action == 'add'): ?>
                                    <?php if ($from_appointment): ?>
                                        Recording visit details from completed appointment
                                    <?php else: ?>
                                        Create a new medical record for a patient
                                    <?php endif; ?>
                                <?php elseif ($action == 'edit'): ?>
                                    Update medical record information
                                <?php else: ?>
                                    Manage all medical records in the system
                                    <?php if (!$isAdmin): ?>
                                       
                                    <?php endif; ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div>
                            <?php if ($action == 'list'): ?>
                                <a href="?action=add" class="btn btn-primary">
                                    <i class="bx bx-plus me-2"></i>Add New Record
                                </a>
                            <?php else: ?>
                                <?php if ($from_appointment): ?>
                                    <a href="../appointments/appointments.php" class="btn btn-outline-info me-2">
                                        <i class="bx bx-calendar me-2"></i>Back to Appointments
                                    </a>
                                <?php endif; ?>
                                <a href="medical_record_action.php" class="btn btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-2"></i>Back to List
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- **เพิ่ม**: Appointment Context Card -->
            <?php if ($from_appointment && !empty($pre_filled_data)): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">
                                    <i class="bx bx-check-circle me-2"></i>Recording Visit from Completed Appointment
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Patient:</strong> <?php echo htmlspecialchars($pre_filled_data['patient_name']); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Doctor:</strong> <?php echo htmlspecialchars($pre_filled_data['doctor_name']); ?>
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <strong>Appointment Type:</strong> <?php echo htmlspecialchars($pre_filled_data['appointment_type'] ?: 'General Appointment'); ?>
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <strong>Completed:</strong> <?php echo date('M j, Y \a\t H:i A', strtotime($pre_filled_data['completed_date'])); ?>
                                    </div>
                                </div>
                                <?php if ($pre_filled_data['appointment_notes']): ?>
                                    <div class="row mt-2">
                                        <div class="col-12">
                                            <strong>Appointment Notes:</strong><br>
                                            <em><?php echo htmlspecialchars($pre_filled_data['appointment_notes']); ?></em>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

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

            <?php if ($action == 'list'): ?>
                <!-- Medical Records Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="bx bx-file-blank me-2 text-primary"></i>All Medical Records (<?php echo count($medical_records); ?>)
                                    <?php if (!$isAdmin): ?>
                                        <small class="badge bg-warning ms-2">User Mode</small>
                                    <?php else: ?>
                                        <small class="badge bg-danger ms-2">Admin Mode</small>
                                    <?php endif; ?>
                                </h5>
                                <div class="d-flex gap-2">
                                    <input type="text" id="searchInput" class="form-control" placeholder="Search records..." style="width: 250px;">
                                    <input type="date" id="dateFilter" class="form-control" style="width: 200px;">
                                    <button type="button" class="btn btn-outline-primary" onclick="exportToCSV()">
                                        <i class="bx bx-download me-1"></i>Export
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($medical_records)): ?>
                                    <div class="text-center py-5">
                                        <i class="bx bx-file-blank display-1 text-muted"></i>
                                        <h5 class="mt-3 text-muted">No medical records found</h5>
                                        <p class="text-muted">Start by adding your first medical record</p>
                                        <a href="?action=add" class="btn btn-primary">
                                            <i class="bx bx-plus me-2"></i>Add First Record
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0" id="recordsTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Patient</th>
                                                    <th>Visit Date</th>
                                                    <th>Diagnosis</th>
                                                    <th>Doctor</th>
                                                    <th>Nurse</th>
                                                    <th>Next Visit</th>
                                                    <th width="120">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($medical_records as $record): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar avatar-md me-3">
                                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                                                    <span class="fw-bold"><?php echo strtoupper(substr($record['first_name'], 0, 1) . substr($record['last_name'], 0, 1)); ?></span>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-1"><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></h6>
                                                                <small class="text-muted">ID: <?php echo $record['patient_id']; ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold"><?php echo date('M j, Y', strtotime($record['visit_date'])); ?></span>
                                                        <br><small class="text-muted"><?php echo date('H:i', strtotime($record['visit_date'])); ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars(substr($record['diagnosis'], 0, 30) . (strlen($record['diagnosis']) > 30 ? '...' : '')); ?></span>
                                                        <?php if ($record['symptoms']): ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($record['symptoms'], 0, 40) . (strlen($record['symptoms']) > 40 ? '...' : '')); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <small class="d-block fw-bold"><?php echo htmlspecialchars($record['doctor_name']); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        // Extract nurse name from notes if it exists
                                                        $nurse_display = '-';
                                                        if (preg_match('/Assisted by Nurse: (.+?)(?:\n|$)/', $record['notes'], $matches)) {
                                                            $nurse_display = htmlspecialchars(trim($matches[1]));
                                                        }
                                                        ?>
                                                        <?php if ($nurse_display != '-'): ?>
                                                            <small class="d-block"><?php echo $nurse_display; ?></small>
                                                        <?php else: ?>
                                                            <small class="text-muted">-</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($record['next_appointment']): ?>
                                                            <span class="badge bg-warning"><?php echo date('M j, Y', strtotime($record['next_appointment'])); ?></span>
                                                            <br><small class="text-muted"><?php echo date('H:i', strtotime($record['next_appointment'])); ?></small>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="medical_record_view.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-success" title="View">
                                                                <i class="bx bx-show"></i>
                                                            </a>
                                                            <a href="?action=edit&id=<?php echo $record['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                                <i class="bx bx-edit-alt"></i>
                                                            </a>
                                                            <?php if ($isAdmin): ?>
                                                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?>')" title="Delete (Admin Only)">
                                                                    <i class="bx bx-trash"></i>
                                                                </button>
                                                            <?php else: ?>
                                                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Delete (Admin Only)" data-bs-toggle="tooltip">
                                                                    <i class="bx bx-lock"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($action == 'view'): ?>
                <!-- Embedded View (redirect to dedicated view page) -->
                <script>
                    window.location.href = 'medical_record_view.php?id=<?php echo $record_id; ?>';
                </script>
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Redirecting to detailed view...</p>
                    <p><a href="medical_record_view.php?id=<?php echo $record_id; ?>">Click here if not redirected automatically</a></p>
                </div>

            <?php else: ?>
                <!-- Medical Record Form -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bx bx-file-plus me-2 text-primary"></i>
                                    <?php echo $action == 'add' ? 'Add Medical Record' : 'Edit Medical Record'; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" class="row g-3" id="medicalRecordForm">
                                    
                                    <!-- Basic Information -->
                                    <div class="col-12">
                                        <h6 class="text-primary mb-3"><i class="bx bx-user me-2"></i>Basic Information</h6>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="patient_id" class="form-label">Patient <span class="text-danger">*</span></label>
                                        <select class="form-select" id="patient_id" name="patient_id" required <?php echo ($from_appointment ? 'readonly' : ''); ?>>
                                            <option value="">Select Patient</option>
                                            <?php foreach ($patients as $patient): 
                                                $selected = '';
                                                if ($from_appointment && isset($pre_filled_data['patient_id']) && $patient['id'] == $pre_filled_data['patient_id']) {
                                                    $selected = 'selected';
                                                } elseif ($record_data && $record_data['patient_id'] == $patient['id']) {
                                                    $selected = 'selected';
                                                } elseif (isset($_POST['patient_id']) && $_POST['patient_id'] == $patient['id']) {
                                                    $selected = 'selected';
                                                }
                                            ?>
                                                <option value="<?php echo $patient['id']; ?>" <?php echo $selected; ?>>
                                                    <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name'] . ' (Age: ' . $patient['age'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if ($from_appointment): ?>
                                            <div class="form-text text-success">
                                                <i class="bx bx-info-circle me-1"></i>Patient automatically selected from completed appointment
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="visit_date" class="form-label">Visit Date & Time <span class="text-danger">*</span></label>
                                        <input type="datetime-local" class="form-control" id="visit_date" name="visit_date" 
                                               value="<?php echo $record_data ? date('Y-m-d\TH:i', strtotime($record_data['visit_date'])) : (isset($_POST['visit_date']) ? $_POST['visit_date'] : date('Y-m-d\TH:i')); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="doctor_id" class="form-label">Doctor <span class="text-danger">*</span></label>
                                        <select class="form-select" id="doctor_id" name="doctor_id" required>
                                            <option value="">Select Doctor</option>
                                            <?php foreach ($doctors as $doctor): 
                                                // For edit mode, try to match existing doctor_name
                                                $doctor_full_name = 'Dr. ' . $doctor['first_name'] . ' ' . $doctor['last_name'];
                                                $selected = '';
                                                if ($from_appointment && isset($pre_filled_data['doctor_name']) && $pre_filled_data['doctor_name'] == $doctor_full_name) {
                                                    $selected = 'selected';
                                                } elseif ($record_data && $record_data['doctor_name'] == $doctor_full_name) {
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
                                        <?php if ($from_appointment): ?>
                                            <div class="form-text text-success">
                                                <i class="bx bx-info-circle me-1"></i>Doctor automatically filled from completed appointment
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="nurse_id" class="form-label">Nurse (Optional)</label>
                                        <select class="form-select" id="nurse_id" name="nurse_id">
                                            <option value="">Select Nurse</option>
                                            <?php foreach ($nurses as $nurse): 
                                                // For edit mode, check if nurse is mentioned in notes
                                                $nurse_full_name = $nurse['first_name'] . ' ' . $nurse['last_name'];
                                                $selected = '';
                                                if ($record_data && strpos($record_data['notes'], 'Assisted by Nurse: ' . $nurse_full_name) !== false) {
                                                    $selected = 'selected';
                                                } elseif (isset($_POST['nurse_id']) && $_POST['nurse_id'] == $nurse['id']) {
                                                    $selected = 'selected';
                                                }
                                            ?>
                                                <option value="<?php echo $nurse['id']; ?>" <?php echo $selected; ?>>
                                                    <?php echo htmlspecialchars($nurse['first_name'] . ' ' . $nurse['last_name'] . ' (' . $nurse['department'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Medical Information -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary mb-3"><i class="bx bx-health me-2"></i>Medical Information</h6>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label for="diagnosis" class="form-label">Diagnosis <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3" required placeholder="Primary diagnosis..."><?php 
                                            if ($from_appointment && !empty($default_chief_complaint)) {
                                                echo htmlspecialchars($default_chief_complaint);
                                            } elseif ($record_data) {
                                                echo htmlspecialchars($record_data['diagnosis']);
                                            } elseif (isset($_POST['diagnosis'])) {
                                                echo htmlspecialchars($_POST['diagnosis']);
                                            }
                                        ?></textarea>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="symptoms" class="form-label">Symptoms</label>
                                        <textarea class="form-control" id="symptoms" name="symptoms" rows="3" placeholder="Patient symptoms..."><?php echo $record_data ? htmlspecialchars($record_data['symptoms']) : (isset($_POST['symptoms']) ? htmlspecialchars($_POST['symptoms']) : ''); ?></textarea>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="treatment" class="form-label">Treatment</label>
                                        <textarea class="form-control" id="treatment" name="treatment" rows="3" placeholder="Treatment provided..."><?php echo $record_data ? htmlspecialchars($record_data['treatment']) : (isset($_POST['treatment']) ? htmlspecialchars($_POST['treatment']) : ''); ?></textarea>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="prescription" class="form-label">Prescription</label>
                                        <textarea class="form-control" id="prescription" name="prescription" rows="3" placeholder="Medications prescribed..."><?php echo $record_data ? htmlspecialchars($record_data['prescription']) : (isset($_POST['prescription']) ? htmlspecialchars($_POST['prescription']) : ''); ?></textarea>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="notes" class="form-label">Additional Notes</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes..."><?php 
                                            if ($from_appointment && !empty($default_notes)) {
                                                echo htmlspecialchars($default_notes);
                                            } elseif ($record_data) {
                                                echo htmlspecialchars($record_data['notes']);
                                            } elseif (isset($_POST['notes'])) {
                                                echo htmlspecialchars($_POST['notes']);
                                            }
                                        ?></textarea>
                                    </div>

                                    <!-- **เพิ่ม**: ฟิลด์พิเศษสำหรับ completed appointment -->
                                    <?php if ($from_appointment): ?>
                                        <div class="col-12 mt-4">
                                            <div class="card bg-light">
                                                <div class="card-body">
                                                    <h6 class="card-title text-primary">
                                                        <i class="bx bx-file-blank me-2"></i>Appointment Follow-up Details
                                                    </h6>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <label for="appointment_outcome" class="form-label">Appointment Outcome</label>
                                                            <select class="form-select" id="appointment_outcome" name="appointment_outcome">
                                                                <option value="completed_successfully">Completed Successfully</option>
                                                                <option value="partial_completion">Partial Completion</option>
                                                                <option value="needs_follow_up">Needs Follow-up</option>
                                                                <option value="referred_specialist">Referred to Specialist</option>
                                                                <option value="cancelled_patient">Patient Cancelled</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="next_action" class="form-label">Next Action Required</label>
                                                            <select class="form-select" id="next_action" name="next_action">
                                                                <option value="">No Further Action</option>
                                                                <option value="schedule_follow_up">Schedule Follow-up</option>
                                                                <option value="lab_tests">Order Lab Tests</option>
                                                                <option value="imaging">Imaging Required</option>
                                                                <option value="specialist_referral">Specialist Referral</option>
                                                                <option value="medication_review">Medication Review</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Hidden fields -->
                                        <input type="hidden" name="from_appointment" value="1">
                                        <input type="hidden" name="previous_record_id" value="<?php echo isset($pre_filled_data['previous_record_id']) ? $pre_filled_data['previous_record_id'] : ''; ?>">
                                    <?php endif; ?>

                                    <!-- **เพิ่ม**: Quick Actions สำหรับ completed appointment -->
                                    <?php if ($from_appointment): ?>
                                        <div class="col-12 mt-3">
                                            <div class="card border-info">
                                                <div class="card-header bg-info text-white">
                                                    <h6 class="mb-0">
                                                        <i class="bx bx-lightning me-2"></i>Quick Templates
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <button type="button" class="btn btn-outline-success w-100 mb-2" onclick="setQuickTemplate('routine_positive')">
                                                                <i class="bx bx-check me-1"></i>Routine - Positive
                                                            </button>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <button type="button" class="btn btn-outline-warning w-100 mb-2" onclick="setQuickTemplate('follow_up_needed')">
                                                                <i class="bx bx-time me-1"></i>Follow-up Needed
                                                            </button>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <button type="button" class="btn btn-outline-info w-100 mb-2" onclick="setQuickTemplate('referred_specialist')">
                                                                <i class="bx bx-share me-1"></i>Referred Out
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Vital Signs -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary mb-3"><i class="bx bx-heart me-2"></i>Vital Signs</h6>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="weight" class="form-label">Weight (kg)</label>
                                        <input type="number" step="0.01" class="form-control" id="weight" name="weight" 
                                               value="<?php echo $record_data ? $record_data['weight'] : (isset($_POST['weight']) ? $_POST['weight'] : ''); ?>" placeholder="e.g., 70.5">
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="height" class="form-label">Height (cm)</label>
                                        <input type="number" step="0.01" class="form-control" id="height" name="height" 
                                               value="<?php echo $record_data ? $record_data['height'] : (isset($_POST['height']) ? $_POST['height'] : ''); ?>" placeholder="e.g., 175">
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="temperature" class="form-label">Temperature (°C)</label>
                                        <input type="number" step="0.1" class="form-control" id="temperature" name="temperature" 
                                               value="<?php echo $record_data ? $record_data['temperature'] : (isset($_POST['temperature']) ? $_POST['temperature'] : ''); ?>" placeholder="e.g., 37.0">
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="pulse_rate" class="form-label">Pulse Rate (bpm)</label>
                                        <input type="number" class="form-control" id="pulse_rate" name="pulse_rate" 
                                               value="<?php echo $record_data ? $record_data['pulse_rate'] : (isset($_POST['pulse_rate']) ? $_POST['pulse_rate'] : ''); ?>" placeholder="e.g., 72">
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="blood_pressure_systolic" class="form-label">Blood Pressure - Systolic</label>
                                        <input type="number" class="form-control" id="blood_pressure_systolic" name="blood_pressure_systolic" 
                                               value="<?php echo $record_data ? $record_data['blood_pressure_systolic'] : (isset($_POST['blood_pressure_systolic']) ? $_POST['blood_pressure_systolic'] : ''); ?>" placeholder="e.g., 120">
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="blood_pressure_diastolic" class="form-label">Blood Pressure - Diastolic</label>
                                        <input type="number" class="form-control" id="blood_pressure_diastolic" name="blood_pressure_diastolic" 
                                               value="<?php echo $record_data ? $record_data['blood_pressure_diastolic'] : (isset($_POST['blood_pressure_diastolic']) ? $_POST['blood_pressure_diastolic'] : ''); ?>" placeholder="e.g., 80">
                                    </div>

                                    <!-- Follow-up -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary mb-3"><i class="bx bx-calendar me-2"></i>Follow-up</h6>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="next_appointment" class="form-label">Next Appointment</label>
                                        <input type="datetime-local" class="form-control" id="next_appointment" name="next_appointment" 
                                               value="<?php echo $record_data && $record_data['next_appointment'] ? date('Y-m-d\TH:i', strtotime($record_data['next_appointment'])) : (isset($_POST['next_appointment']) ? $_POST['next_appointment'] : ''); ?>">
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="col-12 mt-4">
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <?php if ($from_appointment): ?>
                                                <a href="../appointments/appointments.php" class="btn btn-outline-info">
                                                    <i class="bx bx-calendar me-2"></i>Back to Appointments
                                                </a>
                                            <?php else: ?>
                                                <a href="medical_record_action.php" class="btn btn-outline-secondary">
                                                    <i class="bx bx-arrow-back me-2"></i>Back to List
                                                </a>
                                            <?php endif; ?>
                                            <div>
                                                <button type="reset" class="btn btn-outline-warning me-2">
                                                    <i class="bx bx-refresh me-2"></i>Reset Form
                                                </button>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bx bx-save me-2"></i><?php echo $action == 'add' ? 'Save Record' : 'Update Record'; ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Delete Confirmation Modal (แสดงเฉพาะ Admin) -->
<?php if ($isAdmin): ?>
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bx bx-trash me-2 text-danger"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bx bx-warning me-2"></i>
                    <strong>Administrator Action Required</strong>
                </div>
                <p>Are you sure you want to delete the medical record for <strong id="patientName"></strong>?</p>
                <p class="text-danger"><i class="bx bx-error-circle me-1"></i><strong>Warning:</strong> This action cannot be undone and will permanently remove all associated medical data.</p>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="confirmCheckbox">
                    <label class="form-check-label text-muted" for="confirmCheckbox">
                        I understand that this action is irreversible
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <a href="#" id="deleteLink" class="btn btn-danger" style="display: none;">
                    <i class="bx bx-trash me-1"></i>Delete Record
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>

<style>
/* Enhanced Styling */
:root {
    --primary-color: #4f46e5;
    --secondary-color: #7c3aed;
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
    --info-color: #06b6d4;
    --light-color: #f8fafc;
    --dark-color: #1e293b;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.layout-page {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    margin: 20px;
    padding: 30px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
}

.card-header {
    border-bottom: 2px solid var(--light-color);
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border-radius: 15px 15px 0 0 !important;
    padding: 20px 25px;
}

.btn {
    border-radius: 10px;
    padding: 10px 20px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4);
}

.btn-success {
    background: linear-gradient(135deg, var(--success-color), #059669);
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.btn-warning {
    background: linear-gradient(135deg, var(--warning-color), #d97706);
    box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
}

.btn-danger {
    background: linear-gradient(135deg, var(--danger-color), #dc2626);
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
}

.form-control, .form-select {
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    padding: 12px 15px;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.alert {
    border-radius: 12px;
    border: none;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.alert-warning {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    color: #856404;
}

.alert-danger {
    background: linear-gradient(135deg, #f8d7da, #fab1a0);
    color: #721c24;
}

.alert-success {
    background: linear-gradient(135deg, #d4edda, #a8e6cf);
    color: #155724;
}

.alert-info {
    background: linear-gradient(135deg, #d1ecf1, #bee5eb);
    color: #0c5460;
}

/* Badge styling */
.badge.bg-danger {
    background: linear-gradient(135deg, #dc3545, #c82333) !important;
}

.badge.bg-warning {
    background: linear-gradient(135deg, #ffc107, #e0a800) !important;
    color: #000 !important;
}

.badge.bg-info {
    background: linear-gradient(135deg, #17a2b8, #138496) !important;
}

/* Disabled button styling */
.btn-outline-secondary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-outline-secondary:disabled:hover {
    background: transparent;
    border-color: #6c757d;
    color: #6c757d;
}

/* Modal enhancements */
.modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.modal-header {
    border-bottom: 1px solid #e9ecef;
    padding: 20px 24px 16px;
}

.modal-body {
    padding: 20px 24px;
}

.modal-footer {
    border-top: 1px solid #e9ecef;
    padding: 16px 24px 20px;
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

/* Appointment context card styling */
.card.border-success {
    border-width: 2px !important;
}

.card.border-success .card-header.bg-success {
    background: linear-gradient(135deg, #28a745, #20c997) !important;
}

.card.bg-light {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef) !important;
}

.card.border-info {
    border-width: 2px !important;
}

.card.border-info .card-header.bg-info {
    background: linear-gradient(135deg, #17a2b8, #20c997) !important;
}

/* Table styling */
.table {
    border-radius: 10px;
    overflow: hidden;
}

.table-light {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
}

.table-hover tbody tr:hover {
    background-color: rgba(79, 70, 229, 0.05);
}

/* Avatar styling */
.avatar {
    position: relative;
}

.avatar .bg-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)) !important;
}

/* BMI and BP display styling */
#bmiDisplay, #bpDisplay {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Auto-save indicator */
#autoSaveIndicator {
    animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
    from { 
        opacity: 0; 
        transform: translateX(100px); 
    }
    to { 
        opacity: 1; 
        transform: translateX(0); 
    }
}

/* Drug warning styling */
#drugWarnings {
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .layout-page {
        margin: 10px;
        padding: 20px;
    }
    
    .btn-group .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    
    .modal-dialog {
        margin: 1rem;
    }
    
    .d-flex.gap-2 {
        flex-direction: column;
        gap: 10px !important;
    }
    
    .d-flex.gap-2 input {
        width: 100% !important;
    }
}
</style>

<script>
// **JavaScript สำหรับ Quick Templates**
function setQuickTemplate(template) {
    const diagnosisField = document.getElementById('diagnosis');
    const treatmentField = document.getElementById('treatment');
    const nextActionField = document.getElementById('next_action');
    const appointmentOutcomeField = document.getElementById('appointment_outcome');
    
    const templates = {
        'routine_positive': {
            diagnosis: 'Routine follow-up visit completed. Patient showing positive progress.',
            treatment: 'Continue current treatment plan. Patient advised to maintain current medications and lifestyle modifications.',
            next_action: '',
            outcome: 'completed_successfully'
        },
        'follow_up_needed': {
            diagnosis: 'Follow-up visit completed. Requires continued monitoring.',
            treatment: 'Ongoing treatment plan reviewed. Modifications made as necessary.',
            next_action: 'schedule_follow_up',
            outcome: 'needs_follow_up'
        },
        'referred_specialist': {
            diagnosis: 'Initial evaluation completed. Referred to specialist for further assessment.',
            treatment: 'Basic care provided. Specialist referral initiated.',
            next_action: 'specialist_referral',
            outcome: 'referred_specialist'
        }
    };
    
    if (templates[template]) {
        const t = templates[template];
        if (diagnosisField) diagnosisField.value = t.diagnosis;
        if (treatmentField) treatmentField.value = t.treatment;
        if (nextActionField) nextActionField.value = t.next_action;
        if (appointmentOutcomeField) appointmentOutcomeField.value = t.outcome;
        
        // Visual feedback
        [diagnosisField, treatmentField].forEach(field => {
            if (field) {
                field.style.backgroundColor = '#d4edda';
                setTimeout(() => {
                    field.style.backgroundColor = '';
                }, 1000);
            }
        });
    }
}

// **Delete confirmation function (for Admin users)**
function confirmDelete(recordId, patientName) {
    const modal = document.getElementById('deleteModal');
    const patientNameElement = document.getElementById('patientName');
    const deleteLink = document.getElementById('deleteLink');
    const confirmCheckbox = document.getElementById('confirmCheckbox');
    
    if (modal && patientNameElement && deleteLink && confirmCheckbox) {
        patientNameElement.textContent = patientName;
        deleteLink.href = `?action=delete&id=${recordId}`;
        confirmCheckbox.checked = false;
        deleteLink.style.display = 'none';
        
        // Show/hide delete button based on checkbox
        confirmCheckbox.addEventListener('change', function() {
            deleteLink.style.display = this.checked ? 'inline-block' : 'none';
        });
        
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
    }
}

// **Export functionality**
function exportToCSV() {
    const table = document.getElementById('recordsTable');
    if (!table) return;
    
    const rows = table.querySelectorAll('tr');
    const csvContent = [];
    
    // Get headers
    const headers = Array.from(rows[0].querySelectorAll('th')).map(th => th.textContent.trim());
    csvContent.push(headers.slice(0, -1)); // Exclude Actions column
    
    // Get data rows
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        if (row.style.display !== 'none') { // Only visible rows
            const cells = Array.from(row.querySelectorAll('td')).map(td => {
                return td.textContent.trim().replace(/\n/g, ' ').replace(/,/g, ';');
            });
            csvContent.push(cells.slice(0, -1)); // Exclude Actions column
        }
    }
    
    // Create CSV file
    const csvString = csvContent.map(row => row.join(',')).join('\n');
    const blob = new Blob([csvString], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    
    // Download file
    const a = document.createElement('a');
    a.href = url;
    a.download = `medical_records_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Search and filter functionality
    const searchInput = document.getElementById('searchInput');
    const dateFilter = document.getElementById('dateFilter');
    const table = document.getElementById('recordsTable');
    
    if (searchInput && table) {
        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedDate = dateFilter ? dateFilter.value : '';
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const patientName = row.cells[0].textContent.toLowerCase();
                const diagnosis = row.cells[2].textContent.toLowerCase();
                const doctor = row.cells[3].textContent.toLowerCase();
                const nurse = row.cells[4].textContent.toLowerCase();
                const visitDate = row.cells[1].textContent;
                
                const matchesSearch = patientName.includes(searchTerm) || 
                                    diagnosis.includes(searchTerm) || 
                                    doctor.includes(searchTerm) ||
                                    nurse.includes(searchTerm);
                
                let matchesDate = true;
                if (selectedDate) {
                    const recordDate = new Date(visitDate).toISOString().split('T')[0];
                    matchesDate = recordDate === selectedDate;
                }
                
                row.style.display = matchesSearch && matchesDate ? '' : 'none';
            });
        }
        
        searchInput.addEventListener('input', filterTable);
        if (dateFilter) {
            dateFilter.addEventListener('change', filterTable);
        }
    }

    // Form validation and enhancements
    const form = document.getElementById('medicalRecordForm');
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
            
            if (!isValid) {
                e.preventDefault();
                // Show error alert
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertDiv.innerHTML = `
                    <i class="bx bx-error-circle me-2"></i>
                    Please fill in all required fields.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                // Insert alert at the top of the form
                form.insertBefore(alertDiv, form.firstChild);
                
                // Focus on first invalid field
                const firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
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
            
            field.addEventListener('input', function() {
                if (this.classList.contains('is-invalid') && this.value.trim()) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            });
        });
    }

    // BMI Calculator
    const weightField = document.getElementById('weight');
    const heightField = document.getElementById('height');
    
    if (weightField && heightField) {
        function calculateBMI() {
            const weight = parseFloat(weightField.value);
            const height = parseFloat(heightField.value) / 100; // Convert cm to m
            
            if (weight > 0 && height > 0) {
                const bmi = weight / (height * height);
                const bmiCategory = getBMICategory(bmi);
                
                // Remove existing BMI display
                const existingBMI = document.getElementById('bmiDisplay');
                if (existingBMI) {
                    existingBMI.remove();
                }
                
                // Create BMI display
                const bmiDiv = document.createElement('div');
                bmiDiv.id = 'bmiDisplay';
                bmiDiv.className = 'mt-2 p-2 rounded border';
                bmiDiv.innerHTML = `
                    <small class="text-muted">
                        <strong>BMI:</strong> ${bmi.toFixed(1)} - ${bmiCategory.category}
                        <i class="bx bx-info-circle ms-1" data-bs-toggle="tooltip" title="${bmiCategory.description}"></i>
                    </small>
                `;
                
                // Add appropriate styling based on BMI category
                if (bmiCategory.color) {
                    bmiDiv.style.borderColor = bmiCategory.color;
                    bmiDiv.style.backgroundColor = bmiCategory.color + '20';
                }
                
                heightField.parentNode.appendChild(bmiDiv);
                
                // Initialize tooltip for new element
                const tooltip = new bootstrap.Tooltip(bmiDiv.querySelector('[data-bs-toggle="tooltip"]'));
            }
        }
        
        function getBMICategory(bmi) {
            if (bmi < 18.5) {
                return {
                    category: 'Underweight',
                    description: 'BMI below 18.5',
                    color: '#17a2b8'
                };
            } else if (bmi < 25) {
                return {
                    category: 'Normal',
                    description: 'BMI 18.5-24.9',
                    color: '#28a745'
                };
            } else if (bmi < 30) {
                return {
                    category: 'Overweight',
                    description: 'BMI 25-29.9',
                    color: '#ffc107'
                };
            } else {
                return {
                    category: 'Obese',
                    description: 'BMI 30 or higher',
                    color: '#dc3545'
                };
            }
        }
        
        weightField.addEventListener('input', calculateBMI);
        heightField.addEventListener('input', calculateBMI);
        
        // Calculate BMI on page load if values exist
        if (weightField.value && heightField.value) {
            calculateBMI();
        }
    }

    // Blood Pressure Validation
    const systolicField = document.getElementById('blood_pressure_systolic');
    const diastolicField = document.getElementById('blood_pressure_diastolic');
    
    if (systolicField && diastolicField) {
        function validateBloodPressure() {
            const systolic = parseInt(systolicField.value);
            const diastolic = parseInt(diastolicField.value);
            
            // Remove existing BP display
            const existingBP = document.getElementById('bpDisplay');
            if (existingBP) {
                existingBP.remove();
            }
            
            if (systolic > 0 && diastolic > 0) {
                const bpCategory = getBPCategory(systolic, diastolic);
                
                // Create BP display
                const bpDiv = document.createElement('div');
                bpDiv.id = 'bpDisplay';
                bpDiv.className = 'mt-2 p-2 rounded border';
                bpDiv.innerHTML = `
                    <small class="text-muted">
                        <strong>BP:</strong> ${systolic}/${diastolic} - ${bpCategory.category}
                        <i class="bx bx-info-circle ms-1" data-bs-toggle="tooltip" title="${bpCategory.description}"></i>
                    </small>
                `;
                
                if (bpCategory.color) {
                    bpDiv.style.borderColor = bpCategory.color;
                    bpDiv.style.backgroundColor = bpCategory.color + '20';
                }
                
                diastolicField.parentNode.appendChild(bpDiv);
                
                // Initialize tooltip
                const tooltip = new bootstrap.Tooltip(bpDiv.querySelector('[data-bs-toggle="tooltip"]'));
                
                // Validation warnings
                if (systolic <= diastolic) {
                    showFieldWarning(systolicField, 'Systolic should be higher than diastolic');
                } else {
                    removeFieldWarning(systolicField);
                    removeFieldWarning(diastolicField);
                }
            }
        }
        
        function getBPCategory(systolic, diastolic) {
            if (systolic < 90 || diastolic < 60) {
                return {
                    category: 'Low',
                    description: 'Hypotension',
                    color: '#17a2b8'
                };
            } else if (systolic < 120 && diastolic < 80) {
                return {
                    category: 'Normal',
                    description: 'Optimal blood pressure',
                    color: '#28a745'
                };
            } else if (systolic < 130 && diastolic < 85) {
                return {
                    category: 'High Normal',
                    description: 'Pre-hypertension',
                    color: '#ffc107'
                };
            } else if (systolic < 140 && diastolic < 90) {
                return {
                    category: 'Stage 1 HTN',
                    description: 'Mild hypertension',
                    color: '#fd7e14'
                };
            } else {
                return {
                    category: 'Stage 2 HTN',
                    description: 'Moderate to severe hypertension',
                    color: '#dc3545'
                };
            }
        }
        
        function showFieldWarning(field, message) {
            removeFieldWarning(field);
            
            const warningDiv = document.createElement('div');
            warningDiv.className = 'field-warning text-danger mt-1';
            warningDiv.innerHTML = `<small><i class="bx bx-warning me-1"></i>${message}</small>`;
            field.parentNode.appendChild(warningDiv);
        }
        
        function removeFieldWarning(field) {
            const warning = field.parentNode.querySelector('.field-warning');
            if (warning) {
                warning.remove();
            }
        }
        
        systolicField.addEventListener('input', validateBloodPressure);
        diastolicField.addEventListener('input', validateBloodPressure);
        
        // Validate on page load
        if (systolicField.value && diastolicField.value) {
            validateBloodPressure();
        }
    }

    // Auto-save draft functionality
    let autoSaveTimer;
    const autoSaveFields = ['diagnosis', 'symptoms', 'treatment', 'prescription', 'notes'];
    
    function autoSaveDraft() {
        const draftData = {};
        let hasContent = false;
        
        autoSaveFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field && field.value.trim()) {
                draftData[fieldId] = field.value;
                hasContent = true;
            }
        });
        
        if (hasContent) {
            localStorage.setItem('medical_record_draft', JSON.stringify({
                data: draftData,
                timestamp: new Date().toISOString()
            }));
            
            showAutoSaveIndicator();
        }
    }
    
    function showAutoSaveIndicator() {
        // Remove existing indicator
        const existing = document.getElementById('autoSaveIndicator');
        if (existing) existing.remove();
        
        // Create indicator
        const indicator = document.createElement('div');
        indicator.id = 'autoSaveIndicator';
        indicator.className = 'position-fixed bottom-0 end-0 m-3 alert alert-success fade show';
        indicator.style.zIndex = '9999';
        indicator.innerHTML = `
            <small>
                <i class="bx bx-check me-1"></i>Draft saved automatically
            </small>
        `;
        
        document.body.appendChild(indicator);
        
        // Auto hide after 2 seconds
        setTimeout(() => {
            if (indicator) {
                indicator.classList.remove('show');
                setTimeout(() => indicator.remove(), 150);
            }
        }, 2000);
    }
    
    function loadDraft() {
        const draft = localStorage.getItem('medical_record_draft');
        if (draft) {
            try {
                const parsedDraft = JSON.parse(draft);
                const isRecent = new Date() - new Date(parsedDraft.timestamp) < 24 * 60 * 60 * 1000; // 24 hours
                
                if (isRecent && confirm('Found a recent draft. Would you like to restore it?')) {
                    Object.entries(parsedDraft.data).forEach(([fieldId, value]) => {
                        const field = document.getElementById(fieldId);
                        if (field && !field.value) {
                            field.value = value;
                            field.style.backgroundColor = '#fff3cd';
                            setTimeout(() => {
                                field.style.backgroundColor = '';
                            }, 2000);
                        }
                    });
                }
            } catch (e) {
                console.error('Error loading draft:', e);
            }
        }
    }
    
    // Set up auto-save for form fields
    autoSaveFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', () => {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(autoSaveDraft, 3000); // Save after 3 seconds of inactivity
            });
        }
    });
    
    // Load draft on page load (only for add mode)
    if (window.location.search.includes('action=add') && !window.location.search.includes('from_appointment')) {
        loadDraft();
    }
    
    // Clear draft on successful submit
    if (form) {
        form.addEventListener('submit', () => {
            localStorage.removeItem('medical_record_draft');
        });
    }

    // Enhanced patient selection with search
    const patientSelect = document.getElementById('patient_id');
    if (patientSelect) {
        // Add patient info display
        patientSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                showPatientInfo(selectedOption.text);
            }
        });
    }
    
    function showPatientInfo(patientText) {
        // Remove existing info
        const existingInfo = document.getElementById('patientInfo');
        if (existingInfo) existingInfo.remove();
        
        // Extract age from option text
        const ageMatch = patientText.match(/Age: (\d+)/);
        const age = ageMatch ? ageMatch[1] : 'Unknown';
        
        const infoDiv = document.createElement('div');
        infoDiv.id = 'patientInfo';
        infoDiv.className = 'mt-2 p-2 bg-light rounded border';
        infoDiv.innerHTML = `
            <small class="text-muted">
                <i class="bx bx-user me-1"></i>Patient Age: <strong>${age} years</strong>
            </small>
        `;
        
        patientSelect.parentNode.appendChild(infoDiv);
    }

    // Prescription drug interaction checker (basic)
    const prescriptionField = document.getElementById('prescription');
    if (prescriptionField) {
        prescriptionField.addEventListener('blur', function() {
            checkDrugInteractions(this.value);
        });
    }
    
    function checkDrugInteractions(prescription) {
        // Basic drug interaction warnings
        const commonInteractions = {
            'warfarin': ['aspirin', 'ibuprofen', 'naproxen'],
            'aspirin': ['warfarin', 'methotrexate'],
            'metformin': ['alcohol', 'iodine contrast'],
            'digoxin': ['quinidine', 'verapamil']
        };
        
        const prescriptionLower = prescription.toLowerCase();
        const warnings = [];
        
        Object.entries(commonInteractions).forEach(([drug, interactions]) => {
            if (prescriptionLower.includes(drug)) {
                interactions.forEach(interactingDrug => {
                    if (prescriptionLower.includes(interactingDrug)) {
                        warnings.push(`Potential interaction: ${drug} + ${interactingDrug}`);
                    }
                });
            }
        });
        
        // Remove existing warnings
        const existingWarnings = document.getElementById('drugWarnings');
        if (existingWarnings) existingWarnings.remove();
        
        if (warnings.length > 0) {
            const warningDiv = document.createElement('div');
            warningDiv.id = 'drugWarnings';
            warningDiv.className = 'mt-2 p-2 bg-warning bg-opacity-25 border border-warning rounded';
            warningDiv.innerHTML = `
                <small>
                    <i class="bx bx-warning text-warning me-1"></i>
                    <strong>Drug Interaction Warnings:</strong>
                    <ul class="mb-0 mt-1">
                        ${warnings.map(warning => `<li>${warning}</li>`).join('')}
                    </ul>
                    <em>Please verify with pharmacist or drug interaction database.</em>
                </small>
            `;
            
            prescriptionField.parentNode.appendChild(warningDiv);
        }
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+S to save form
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            const form = document.getElementById('medicalRecordForm');
            if (form) {
                form.submit();
            }
        }
        
        // Ctrl+N for new record (on list page)
        if (e.ctrlKey && e.key === 'n' && window.location.search === '') {
            e.preventDefault();
            window.location.href = '?action=add';
        }
        
        // Escape to go back
        if (e.key === 'Escape' && window.location.search.includes('action=')) {
            const backButton = document.querySelector('a[href="medical_record_action.php"]');
            if (backButton) {
                window.location.href = backButton.href;
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>