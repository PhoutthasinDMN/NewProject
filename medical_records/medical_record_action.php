<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

// ตั้งค่าสำหรับ includes
$assets_path = '../assets/';
$page_title = 'Medical Records Management';

// ตรวจสอบสิทธิ์ผู้ใช้
$user_id = $_SESSION['user_id'];
$sql = "SELECT username, email, role FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$result->free();
$stmt->close();

$isAdmin = ($user['role'] == 'admin');
$success_message = '';
$error_message = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$record_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ตรวจสอบ auto-fill จากหน้า patients
$auto_fill_patient_id = isset($_GET['patient_id']) && isset($_GET['auto_fill']) ? intval($_GET['patient_id']) : 0;
$auto_fill_data = [];

if ($auto_fill_patient_id > 0 && $action == 'add') {
    // ดึงข้อมูลผู้ป่วยสำหรับ auto-fill
    $stmt = $conn->prepare("SELECT id, first_name, last_name, age, address FROM patients WHERE id = ?");
    $stmt->bind_param("i", $auto_fill_patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $auto_fill_data = $result->fetch_assoc();
    $result->free();
    $stmt->close();
    
    if ($auto_fill_data) {
        $success_message = "Patient data loaded: " . $auto_fill_data['first_name'] . " " . $auto_fill_data['last_name'] . " (ID: CN" . str_pad($auto_fill_patient_id, 3, '0', STR_PAD_LEFT) . ")";
    }
}

// ตรวจสอบ completed appointment
$from_appointment = isset($_GET['from_appointment']) ? true : false;
$pre_filled_data = [];

if ($from_appointment && isset($_SESSION['completed_appointment'])) {
    $pre_filled_data = $_SESSION['completed_appointment'];
    $success_message = "Appointment completed! Please record the visit details below.";
}

// ดึงข้อมูลพื้นฐาน
$doctors = [];
$nurses = [];
$patients = [];

try {
    // ดึงข้อมูลหมอ
    $doctors_result = $conn->query("SELECT id, first_name, last_name, specialization FROM doctors ORDER BY first_name, last_name");
    if ($doctors_result) {
        $doctors = $doctors_result->fetch_all(MYSQLI_ASSOC);
        $doctors_result->free();
    }
    
    // ดึงข้อมูลพยาบาล
    $nurses_result = $conn->query("SELECT id, first_name, last_name, department FROM nurses WHERE status = 'Active' ORDER BY first_name, last_name");
    if ($nurses_result) {
        $nurses = $nurses_result->fetch_all(MYSQLI_ASSOC);
        $nurses_result->free();
    }
    
    // ดึงข้อมูลผู้ป่วย - เพิ่ม age และ address
    $patients_result = $conn->query("SELECT id, first_name, last_name, age, address FROM patients ORDER BY first_name, last_name");
    if ($patients_result) {
        $patients = $patients_result->fetch_all(MYSQLI_ASSOC);
        $patients_result->free();
    }
} catch (Exception $e) {
    error_log("Error fetching data: " . $e->getMessage());
}

// Handle delete action - เฉพาะ Admin เท่านั้น
if ($action == 'delete' && $record_id > 0) {
    if (!$isAdmin) {
        $error_message = "Access denied. Only administrators can delete medical records.";
    } else {
        try {
            $stmt = $conn->prepare("DELETE FROM medical_records WHERE id = ?");
            $stmt->bind_param("i", $record_id);
            if ($stmt->execute()) {
                $success_message = "Medical record deleted successfully!";
                $action = 'list';
            }
            $stmt->close();
        } catch (Exception $e) {
            $error_message = "Error deleting medical record.";
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
                // 🔧 แก้ไข SQL - ลบฟิลด์ appointment_outcome ที่ไม่มีในตาราง
                $sql = "INSERT INTO medical_records (patient_id, visit_date, diagnosis, symptoms, treatment, prescription, notes, doctor_name, weight, height, pulse_rate, blood_pressure_systolic, blood_pressure_diastolic, temperature, next_appointment) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($sql);
                // 🔧 แก้ไข bind_param - ลด parameter ให้ตรงกับฟิลด์
                $stmt->bind_param("isssssssddiiids", $patient_id, $visit_date, $diagnosis, $symptoms, $treatment, $prescription, $notes, $doctor_name, $weight, $height, $pulse_rate, $blood_pressure_systolic, $blood_pressure_diastolic, $temperature, $next_appointment);
                
                if ($stmt->execute()) {
                    $new_record_id = $conn->insert_id;
                    
                    // เพิ่มข้อมูลพยาบาลใน notes
                    if (!empty($nurse_name)) {
                        $updated_notes = $notes . "\n\nAssisted by Nurse: " . $nurse_name;
                        $update_stmt = $conn->prepare("UPDATE medical_records SET notes = ? WHERE id = ?");
                        $update_stmt->bind_param("si", $updated_notes, $new_record_id);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                    
                    // ตรวจสอบ completed appointment
                    if ($from_appointment && isset($_POST['from_appointment'])) {
                        unset($_SESSION['completed_appointment']);
                        header("Location: medical_record_view.php?id={$new_record_id}&from_appointment=1");
                        exit;
                    }
                    
                    $success_message = "Medical record added successfully!";
                    $_POST = array(); // Clear form
                }
                $stmt->close();
                
            } elseif ($action == 'edit' && $record_id > 0) {
                // Update medical record
                $sql = "UPDATE medical_records SET patient_id = ?, visit_date = ?, diagnosis = ?, symptoms = ?, treatment = ?, prescription = ?, notes = ?, doctor_name = ?, weight = ?, height = ?, pulse_rate = ?, blood_pressure_systolic = ?, blood_pressure_diastolic = ?, temperature = ?, next_appointment = ? WHERE id = ?";
                
                $final_notes = $notes;
                if (!empty($nurse_name)) {
                    $final_notes = $notes . "\n\nAssisted by Nurse: " . $nurse_name;
                }
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssssssddiiidsi", $patient_id, $visit_date, $diagnosis, $symptoms, $treatment, $prescription, $final_notes, $doctor_name, $weight, $height, $pulse_rate, $blood_pressure_systolic, $blood_pressure_diastolic, $temperature, $next_appointment, $record_id);
                
                if ($stmt->execute()) {
                    $success_message = "Medical record updated successfully!";
                }
                $stmt->close();
            }
            
        } catch (Exception $e) {
            $error_message = "Error processing medical record: " . $e->getMessage();
        }
    }
}

// Get medical record data for editing
$record_data = null;
if (($action == 'edit' || $action == 'view') && $record_id > 0) {
    $stmt = $conn->prepare("SELECT mr.*, p.first_name as patient_first_name, p.last_name as patient_last_name, p.age as patient_age, p.address as patient_address FROM medical_records mr JOIN patients p ON mr.patient_id = p.id WHERE mr.id = ?");
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
}

// Get all medical records for listing - เพิ่ม age และ address
$medical_records = [];
if ($action == 'list') {
    $result = $conn->query("SELECT mr.*, p.first_name, p.last_name, p.age, p.address FROM medical_records mr JOIN patients p ON mr.patient_id = p.id ORDER BY mr.visit_date DESC");
    if ($result) {
        $medical_records = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Layout Page -->
<div class="layout-page">
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
                        </div>
                        <div>
                            <?php if ($action == 'list'): ?>
                                <a href="?action=add" class="btn btn-primary">
                                    <i class="bx bx-plus me-2"></i>Add New Record
                                </a>
                            <?php else: ?>
                                <a href="medical_record_action.php" class="btn btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-2"></i>Back to List
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bx bx-check-circle me-2"></i><?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bx bx-error-circle me-2"></i><?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($action == 'list'): ?>
                <!-- Medical Records Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bx bx-file-blank me-2 text-primary"></i>All Medical Records (<?php echo count($medical_records); ?>)
                        </h5>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search records..." style="width: 250px;">
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($medical_records)): ?>
                            <div class="text-center py-5">
                                <i class="bx bx-file-blank display-1 text-muted"></i>
                                <h5 class="mt-3 text-muted">No medical records found</h5>
                                <a href="?action=add" class="btn btn-primary">
                                    <i class="bx bx-plus me-2"></i>Add First Record
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="recordsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Patient Name</th>
                                            <th>Age</th>
                                            <th>Address</th>
                                            <th>Visit Date</th>
                                            <th>Diagnosis</th>
                                            <th>Next Visit</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($medical_records as $record): ?>
                                        <tr>
                                            <td>
                                                <span class="fw-bold text-primary">CN<?php echo str_pad($record['patient_id'], 3, '0', STR_PAD_LEFT); ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-sm me-2">
                                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                                            <span class="fw-bold small"><?php echo strtoupper(substr($record['first_name'], 0, 1) . substr($record['last_name'], 0, 1)); ?></span>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($record['age']); ?></span>
                                            </td>
                                            <td>
                                                <small class="text-muted" title="<?php echo htmlspecialchars($record['address']); ?>">
                                                    <?php echo htmlspecialchars(strlen($record['address']) > 30 ? substr($record['address'], 0, 30) . '...' : $record['address']); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div>
                                                    <span class="fw-bold"><?php echo date('M j, Y', strtotime($record['visit_date'])); ?></span>
                                                    <br><small class="text-muted"><?php echo date('H:i', strtotime($record['visit_date'])); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info text-wrap" style="max-width: 200px;" title="<?php echo htmlspecialchars($record['diagnosis']); ?>">
                                                    <?php echo htmlspecialchars(strlen($record['diagnosis']) > 40 ? substr($record['diagnosis'], 0, 40) . '...' : $record['diagnosis']); ?>
                                                </span>
                                                <?php if ($record['symptoms']): ?>
                                                    <br><small class="text-muted" title="<?php echo htmlspecialchars($record['symptoms']); ?>">
                                                        <?php echo htmlspecialchars(strlen($record['symptoms']) > 30 ? substr($record['symptoms'], 0, 30) . '...' : $record['symptoms']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($record['next_appointment']) && $record['next_appointment'] != '0000-00-00 00:00:00'): ?>
                                                    <div>
                                                        <span class="badge bg-warning"><?php echo date('M j, Y', strtotime($record['next_appointment'])); ?></span>
                                                        <br><small class="text-muted"><?php echo date('H:i', strtotime($record['next_appointment'])); ?></small>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="medical_record_view.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-success" title="View">
                                                        <i class="bx bx-show"></i>
                                                    </a>
                                                    <a href="?action=edit&id=<?php echo $record['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="bx bx-edit-alt"></i>
                                                    </a>
                                                    <?php if ($isAdmin): ?>
                                                        <a href="?action=delete&id=<?php echo $record['id']; ?>" class="btn btn-sm btn-danger" title="Delete (Admin Only)" onclick="return confirm('Are you sure you want to delete this medical record?')">
                                                            <i class="bx bx-trash"></i>
                                                        </a>
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

            <?php elseif ($action == 'view'): ?>
                <!-- Redirect to view page -->
                <script>window.location.href = 'medical_record_view.php?id=<?php echo $record_id; ?>';</script>

            <?php else: ?>
                <!-- Medical Record Form -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bx bx-file-plus me-2 text-primary"></i>
                            <?php echo $action == 'add' ? 'Add Medical Record' : 'Edit Medical Record'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            
                            <!-- Basic Information -->
                            <div class="col-12">
                                <h6 class="text-primary mb-3"><i class="bx bx-user me-2"></i>Basic Information</h6>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="patient_id" class="form-label">Patient <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select class="form-select" id="patient_id" name="patient_id" required>
                                        <option value="">Select Patient</option>
                                        <?php foreach ($patients as $patient): ?>
                                            <option value="<?php echo $patient['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>"
                                                data-age="<?php echo $patient['age']; ?>"
                                                data-address="<?php echo htmlspecialchars($patient['address']); ?>"
                                                <?php 
                                                // Auto-select for auto-fill or edit mode
                                                $is_selected = false;
                                                if ($auto_fill_data && $auto_fill_data['id'] == $patient['id']) {
                                                    $is_selected = true;
                                                } elseif ($record_data && $record_data['patient_id'] == $patient['id']) {
                                                    $is_selected = true;
                                                }
                                                echo $is_selected ? 'selected' : ''; 
                                                ?>>
                                                <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name'] . ' (Age: ' . $patient['age'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" id="quickSearchBtn" data-bs-toggle="modal" data-bs-target="#quickSearchModal">
                                        <i class="bx bx-search"></i> Quick Search
                                    </button>
                                </div>
                                <?php if ($auto_fill_data): ?>
                                    <small class="text-success">
                                        <i class="bx bx-check-circle me-1"></i>
                                        Patient auto-loaded from Patients page
                                    </small>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Patient Info Display -->
                            <div class="col-md-6" id="patientInfoDisplay" style="display: none;">
                                <label class="form-label">Selected Patient Info</label>
                                <div class="card border-primary">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-3">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" id="patientAvatar">
                                                    <span class="fw-bold small" id="patientInitials"></span>
                                                </div>
                                            </div>
                                            <div>
                                                <h6 class="mb-1" id="patientName"></h6>
                                                <small class="text-muted">
                                                    ID: <span class="fw-bold text-primary" id="patientId"></span> | 
                                                    Age: <span id="patientAge"></span>
                                                </small>
                                                <br><small class="text-muted">Address: <span id="patientAddress"></span></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="visit_date" class="form-label">Visit Date & Time <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="visit_date" name="visit_date" 
                                       value="<?php echo $record_data ? date('Y-m-d\TH:i', strtotime($record_data['visit_date'])) : date('Y-m-d\TH:i'); ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="doctor_id" class="form-label">Doctor <span class="text-danger">*</span></label>
                                <select class="form-select" id="doctor_id" name="doctor_id" required>
                                    <option value="">Select Doctor</option>
                                    <?php foreach ($doctors as $doctor): ?>
                                        <?php $doctor_full_name = 'Dr. ' . $doctor['first_name'] . ' ' . $doctor['last_name']; ?>
                                        <option value="<?php echo $doctor['id']; ?>" 
                                            <?php echo ($record_data && $record_data['doctor_name'] == $doctor_full_name) ? 'selected' : ''; ?>>
                                            Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name'] . ' (' . $doctor['specialization'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="nurse_id" class="form-label">Nurse (Optional)</label>
                                <select class="form-select" id="nurse_id" name="nurse_id">
                                    <option value="">Select Nurse</option>
                                    <?php foreach ($nurses as $nurse): ?>
                                        <option value="<?php echo $nurse['id']; ?>">
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
                                <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3" required><?php echo $record_data ? htmlspecialchars($record_data['diagnosis']) : ''; ?></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="symptoms" class="form-label">Symptoms</label>
                                <textarea class="form-control" id="symptoms" name="symptoms" rows="3"><?php echo $record_data ? htmlspecialchars($record_data['symptoms']) : ''; ?></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="treatment" class="form-label">Treatment</label>
                                <textarea class="form-control" id="treatment" name="treatment" rows="3"><?php echo $record_data ? htmlspecialchars($record_data['treatment']) : ''; ?></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="prescription" class="form-label">Prescription</label>
                                <textarea class="form-control" id="prescription" name="prescription" rows="3"><?php echo $record_data ? htmlspecialchars($record_data['prescription']) : ''; ?></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo $record_data ? htmlspecialchars($record_data['notes']) : ''; ?></textarea>
                            </div>

                            <!-- Vital Signs -->
                            <div class="col-12 mt-4">
                                <h6 class="text-primary mb-3"><i class="bx bx-heart me-2"></i>Vital Signs</h6>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="weight" class="form-label">Weight (kg)</label>
                                <input type="number" step="0.01" class="form-control" id="weight" name="weight" 
                                       value="<?php echo $record_data ? $record_data['weight'] : ''; ?>" placeholder="70.5">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="height" class="form-label">Height (cm)</label>
                                <input type="number" step="0.01" class="form-control" id="height" name="height" 
                                       value="<?php echo $record_data ? $record_data['height'] : ''; ?>" placeholder="175">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="temperature" class="form-label">Temperature (°C)</label>
                                <input type="number" step="0.1" class="form-control" id="temperature" name="temperature" 
                                       value="<?php echo $record_data ? $record_data['temperature'] : ''; ?>" placeholder="37.0">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="pulse_rate" class="form-label">Pulse Rate (bpm)</label>
                                <input type="number" class="form-control" id="pulse_rate" name="pulse_rate" 
                                       value="<?php echo $record_data ? $record_data['pulse_rate'] : ''; ?>" placeholder="72">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="blood_pressure_systolic" class="form-label">Blood Pressure - Systolic</label>
                                <input type="number" class="form-control" id="blood_pressure_systolic" name="blood_pressure_systolic" 
                                       value="<?php echo $record_data ? $record_data['blood_pressure_systolic'] : ''; ?>" placeholder="120">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="blood_pressure_diastolic" class="form-label">Blood Pressure - Diastolic</label>
                                <input type="number" class="form-control" id="blood_pressure_diastolic" name="blood_pressure_diastolic" 
                                       value="<?php echo $record_data ? $record_data['blood_pressure_diastolic'] : ''; ?>" placeholder="80">
                            </div>

                            <!-- Follow-up -->
                            <div class="col-12 mt-4">
                                <h6 class="text-primary mb-3"><i class="bx bx-calendar me-2"></i>Follow-up</h6>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="next_appointment" class="form-label">Next Appointment</label>
                                <input type="datetime-local" class="form-control" id="next_appointment" name="next_appointment" 
                                       value="<?php echo $record_data && $record_data['next_appointment'] ? date('Y-m-d\TH:i', strtotime($record_data['next_appointment'])) : ''; ?>">
                            </div>

                            <!-- Form Actions -->
                            <div class="col-12 mt-4">
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <a href="medical_record_action.php" class="btn btn-outline-secondary">
                                        <i class="bx bx-arrow-back me-2"></i>Back to List
                                    </a>
                                    <div>
                                        <button type="reset" class="btn btn-outline-warning me-2">
                                            <i class="bx bx-refresh me-2"></i>Reset
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bx bx-save me-2"></i><?php echo $action == 'add' ? 'Save Record' : 'Update Record'; ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hidden fields for appointment -->
                            <?php if ($from_appointment): ?>
                                <input type="hidden" name="from_appointment" value="1">
                            <?php endif; ?>
                            
                        </form>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Quick Search Modal -->
<div class="modal fade" id="quickSearchModal" tabindex="-1" aria-labelledby="quickSearchModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickSearchModalLabel">
                    <i class="bx bx-search me-2"></i>Quick Patient Search
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="searchPatientId" class="form-label">Enter Patient ID (e.g., CN001, 1, or 001)</label>
                    <div class="input-group">
                        <span class="input-group-text">CN</span>
                        <input type="text" class="form-control" id="searchPatientId" placeholder="001" maxlength="10">
                        <button type="button" class="btn btn-primary" id="searchPatientBtn">
                            <i class="bx bx-search"></i> Search
                        </button>
                    </div>
                    <small class="form-text text-muted">You can enter: 1, 001, CN001, or CN1</small>
                </div>
                
                <div id="searchResult" style="display: none;">
                    <hr>
                    <h6 class="text-success"><i class="bx bx-check-circle me-2"></i>Patient Found</h6>
                    <div class="card border-success">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-md me-3">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;" id="searchResultAvatar">
                                        <span class="fw-bold" id="searchResultInitials"></span>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-1" id="searchResultName"></h6>
                                    <small class="text-muted">
                                        ID: <span class="fw-bold text-primary" id="searchResultId"></span> | 
                                        Age: <span id="searchResultAge"></span>
                                    </small>
                                    <br><small class="text-muted">Address: <span id="searchResultAddress"></span></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="searchNotFound" style="display: none;">
                    <hr>
                    <div class="text-center text-muted">
                        <i class="bx bx-user-x display-4 text-muted"></i>
                        <h6 class="mt-2">Patient not found</h6>
                        <small>Please check the ID and try again</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="selectPatientBtn" style="display: none;">
                    <i class="bx bx-check me-2"></i>Select This Patient
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced search functionality for new table structure
    const searchInput = document.getElementById('searchInput');
    const table = document.getElementById('recordsTable');
    
    if (searchInput && table) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const id = row.cells[0].textContent.toLowerCase();
                const patientName = row.cells[1].textContent.toLowerCase();
                const age = row.cells[2].textContent.toLowerCase();
                const address = row.cells[3].textContent.toLowerCase();
                const visitDate = row.cells[4].textContent.toLowerCase();
                const diagnosis = row.cells[5].textContent.toLowerCase();
                
                const matchesSearch = id.includes(searchTerm) || 
                                    patientName.includes(searchTerm) || 
                                    age.includes(searchTerm) ||
                                    address.includes(searchTerm) ||
                                    visitDate.includes(searchTerm) ||
                                    diagnosis.includes(searchTerm);
                
                row.style.display = matchesSearch ? '' : 'none';
            });
        });
    }

    // Form validation
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const required = ['patient_id', 'diagnosis', 'doctor_id'];
            let valid = true;
            
            required.forEach(field => {
                const element = document.getElementById(field);
                if (!element.value.trim()) {
                    element.classList.add('is-invalid');
                    valid = false;
                } else {
                    element.classList.remove('is-invalid');
                }
            });
            
            if (!valid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    }

    // Initialize tooltips for truncated text
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    if (typeof bootstrap !== 'undefined') {
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Patient selection functionality
    const patientSelect = document.getElementById('patient_id');
    const patientInfoDisplay = document.getElementById('patientInfoDisplay');
    
    if (patientSelect) {
        patientSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            if (selectedOption.value) {
                // Show patient info
                showPatientInfo(selectedOption);
            } else {
                // Hide patient info
                patientInfoDisplay.style.display = 'none';
            }
        });
        
        // Show info if patient is already selected (for edit mode or auto-fill)
        if (patientSelect.value) {
            const selectedOption = patientSelect.options[patientSelect.selectedIndex];
            showPatientInfo(selectedOption);
        }
    }
    
    function showPatientInfo(option) {
        const patientId = option.value;
        const patientName = option.getAttribute('data-name');
        const patientAge = option.getAttribute('data-age');
        const patientAddress = option.getAttribute('data-address');
        
        if (patientName) {
            // Update patient info display
            document.getElementById('patientName').textContent = patientName;
            document.getElementById('patientId').textContent = 'CN' + String(patientId).padStart(3, '0');
            document.getElementById('patientAge').textContent = patientAge;
            document.getElementById('patientAddress').textContent = patientAddress || 'N/A';
            
            // Update initials
            const names = patientName.split(' ');
            const initials = names.map(name => name.charAt(0).toUpperCase()).join('');
            document.getElementById('patientInitials').textContent = initials;
            
            // Show the info display
            patientInfoDisplay.style.display = 'block';
        }
    }

    // Quick Search Modal functionality
    const searchPatientBtn = document.getElementById('searchPatientBtn');
    const searchPatientId = document.getElementById('searchPatientId');
    const selectPatientBtn = document.getElementById('selectPatientBtn');
    let foundPatientOption = null;
    
    if (searchPatientBtn) {
        searchPatientBtn.addEventListener('click', function() {
            const searchValue = searchPatientId.value.trim();
            if (!searchValue) {
                alert('Please enter a patient ID');
                return;
            }
            
            searchPatient(searchValue);
        });
    }
    
    if (searchPatientId) {
        searchPatientId.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchPatientBtn.click();
            }
        });
    }
    
    if (selectPatientBtn) {
        selectPatientBtn.addEventListener('click', function() {
            if (foundPatientOption) {
                // Select the patient in the dropdown
                patientSelect.value = foundPatientOption.value;
                
                // Trigger change event to show patient info
                const event = new Event('change');
                patientSelect.dispatchEvent(event);
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('quickSearchModal'));
                modal.hide();
                
                // Clear search
                searchPatientId.value = '';
                document.getElementById('searchResult').style.display = 'none';
                document.getElementById('searchNotFound').style.display = 'none';
                selectPatientBtn.style.display = 'none';
            }
        });
    }
    
    function searchPatient(searchValue) {
        // Clean the search value - remove CN prefix and leading zeros
        let cleanId = searchValue.replace(/^CN/i, '').replace(/^0+/, '') || '0';
        
        // Find patient in the dropdown options
        const options = patientSelect.options;
        foundPatientOption = null;
        
        for (let i = 1; i < options.length; i++) { // Start from 1 to skip the "Select Patient" option
            if (options[i].value === cleanId) {
                foundPatientOption = options[i];
                break;
            }
        }
        
        if (foundPatientOption) {
            // Show found patient
            const patientName = foundPatientOption.getAttribute('data-name');
            const patientAge = foundPatientOption.getAttribute('data-age');
            const patientAddress = foundPatientOption.getAttribute('data-address');
            const patientId = foundPatientOption.value;
            
            document.getElementById('searchResultName').textContent = patientName;
            document.getElementById('searchResultId').textContent = 'CN' + String(patientId).padStart(3, '0');
            document.getElementById('searchResultAge').textContent = patientAge;
            document.getElementById('searchResultAddress').textContent = patientAddress || 'N/A';
            
            // Update initials
            const names = patientName.split(' ');
            const initials = names.map(name => name.charAt(0).toUpperCase()).join('');
            document.getElementById('searchResultInitials').textContent = initials;
            
            // Show result
            document.getElementById('searchResult').style.display = 'block';
            document.getElementById('searchNotFound').style.display = 'none';
            selectPatientBtn.style.display = 'inline-block';
        } else {
            // Show not found
            document.getElementById('searchResult').style.display = 'none';
            document.getElementById('searchNotFound').style.display = 'block';
            selectPatientBtn.style.display = 'none';
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>