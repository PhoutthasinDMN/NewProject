<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

// ตั้งค่าสำหรับ includes
$assets_path = '../assets/';
$page_title = 'Medical Records Management';
$extra_css = ['../assets/css/dashboard.css'];

$success_message = '';
$error_message = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$record_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ดึงข้อมูลหมอทั้งหมดสำหรับ dropdown
$doctors = [];
$doctors_result = $conn->query("SELECT id, first_name, last_name, specialization FROM doctors ORDER BY first_name, last_name");
if ($doctors_result) {
    $doctors = $doctors_result->fetch_all(MYSQLI_ASSOC);
}

// ดึงข้อมูลพยาบาลที่ active สำหรับ dropdown
$nurses = [];
$nurses_result = $conn->query("SELECT id, first_name, last_name, department FROM nurses WHERE status = 'Active' ORDER BY first_name, last_name");
if ($nurses_result) {
    $nurses = $nurses_result->fetch_all(MYSQLI_ASSOC);
}

// ดึงข้อมูลผู้ป่วยทั้งหมดสำหรับ dropdown
$patients = [];
$patients_result = $conn->query("SELECT id, first_name, last_name, age FROM patients ORDER BY first_name, last_name");
if ($patients_result) {
    $patients = $patients_result->fetch_all(MYSQLI_ASSOC);
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
        
        if ($action == 'add') {
            // Insert new medical record (without nurse_name column)
            // Fields: patient_id, visit_date, diagnosis, symptoms, treatment, prescription, notes, doctor_name, weight, height, pulse_rate, blood_pressure_systolic, blood_pressure_diastolic, temperature, next_appointment (15 fields)
            $sql = "INSERT INTO medical_records (patient_id, visit_date, diagnosis, symptoms, treatment, prescription, notes, doctor_name, weight, height, pulse_rate, blood_pressure_systolic, blood_pressure_diastolic, temperature, next_appointment, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($sql);
            // Types: i=integer, s=string, d=double/decimal
            // patient_id(i), visit_date(s), diagnosis(s), symptoms(s), treatment(s), prescription(s), notes(s), doctor_name(s), weight(d), height(d), pulse_rate(i), blood_pressure_systolic(i), blood_pressure_diastolic(i), temperature(d), next_appointment(s)
            $stmt->bind_param("isssssssddiiids", $patient_id, $visit_date, $diagnosis, $symptoms, $treatment, $prescription, $notes, $doctor_name, $weight, $height, $pulse_rate, $blood_pressure_systolic, $blood_pressure_diastolic, $temperature, $next_appointment);
            
            if ($stmt->execute()) {
                $success_message = "Medical record added successfully!";
                // If nurse was selected, add a note about it
                if (!empty($nurse_name)) {
                    $record_id = $conn->insert_id;
                    $updated_notes = $notes . "\n\nAssisted by Nurse: " . $nurse_name;
                    $update_notes_sql = "UPDATE medical_records SET notes = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_notes_sql);
                    $update_stmt->bind_param("si", $updated_notes, $record_id);
                    $update_stmt->execute();
                }
                $_POST = array(); // Clear form
            } else {
                $error_message = "Error adding medical record: " . $stmt->error;
            }
        } elseif ($action == 'edit' && $record_id > 0) {
            // Update medical record (without nurse_name column)
            // Fields: patient_id, visit_date, diagnosis, symptoms, treatment, prescription, notes, doctor_name, weight, height, pulse_rate, blood_pressure_systolic, blood_pressure_diastolic, temperature, next_appointment, record_id (16 fields)
            $sql = "UPDATE medical_records SET patient_id = ?, visit_date = ?, diagnosis = ?, symptoms = ?, treatment = ?, prescription = ?, notes = ?, doctor_name = ?, weight = ?, height = ?, pulse_rate = ?, blood_pressure_systolic = ?, blood_pressure_diastolic = ?, temperature = ?, next_appointment = ?, updated_at = NOW() WHERE id = ?";
            
            // If nurse was selected, add nurse info to notes
            $final_notes = $notes;
            if (!empty($nurse_name)) {
                $final_notes = $notes . "\n\nAssisted by Nurse: " . $nurse_name;
            }
            
            $stmt = $conn->prepare($sql);
            // Types: patient_id(i), visit_date(s), diagnosis(s), symptoms(s), treatment(s), prescription(s), notes(s), doctor_name(s), weight(d), height(d), pulse_rate(i), blood_pressure_systolic(i), blood_pressure_diastolic(i), temperature(d), next_appointment(s), record_id(i)
            $stmt->bind_param("isssssssddiiidsi", $patient_id, $visit_date, $diagnosis, $symptoms, $treatment, $prescription, $final_notes, $doctor_name, $weight, $height, $pulse_rate, $blood_pressure_systolic, $blood_pressure_diastolic, $temperature, $next_appointment, $record_id);
            
            if ($stmt->execute()) {
                $success_message = "Medical record updated successfully!";
            } else {
                $error_message = "Error updating medical record: " . $stmt->error;
            }
        }
    }
}

// Handle delete action
if ($action == 'delete' && $record_id > 0) {
    $stmt = $conn->prepare("DELETE FROM medical_records WHERE id = ?");
    $stmt->bind_param("i", $record_id);
    
    if ($stmt->execute()) {
        $success_message = "Medical record deleted successfully!";
        $action = 'list';
    } else {
        $error_message = "Error deleting medical record. Please try again.";
    }
}

// Get medical record data for editing or viewing
$record_data = null;
if (($action == 'edit' || $action == 'view') && $record_id > 0) {
    $stmt = $conn->prepare("SELECT mr.*, p.first_name as patient_first_name, p.last_name as patient_last_name FROM medical_records mr JOIN patients p ON mr.patient_id = p.id WHERE mr.id = ?");
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $record_data = $stmt->get_result()->fetch_assoc();
    
    if (!$record_data) {
        $error_message = "Medical record not found.";
        $action = 'list';
    }
}

// Get all medical records for listing
$medical_records = [];
if ($action == 'list') {
    $result = $conn->query("SELECT mr.*, p.first_name, p.last_name FROM medical_records mr JOIN patients p ON mr.patient_id = p.id ORDER BY mr.visit_date DESC");
    $medical_records = $result->fetch_all(MYSQLI_ASSOC);
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
                                    Add New Medical Record
                                <?php elseif ($action == 'edit'): ?>
                                    Edit Medical Record
                                <?php else: ?>
                                    Medical Records Management
                                <?php endif; ?>
                            </h4>
                            <p class="text-muted">
                                <?php if ($action == 'add'): ?>
                                    Create a new medical record for a patient
                                <?php elseif ($action == 'edit'): ?>
                                    Update medical record information
                                <?php else: ?>
                                    Manage all medical records in the system
                                <?php endif; ?>
                            </p>
                        </div>
                        <div>
                            <?php if ($action == 'list'): ?>
                                <a href="?action=add" class="btn btn-primary">
                                    <i class="bx bx-plus me-2"></i>Add New Record
                                </a>
                            <?php else: ?>
                                <a href="medical_records_action.php" class="btn btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-2"></i>Back to List
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

            <?php if ($action == 'list'): ?>
                <!-- Medical Records Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bx bx-file-blank me-2 text-primary"></i>All Medical Records (<?php echo count($medical_records); ?>)</h5>
                                <div class="d-flex gap-2">
                                    <input type="text" id="searchInput" class="form-control" placeholder="Search records..." style="width: 250px;">
                                    <input type="date" id="dateFilter" class="form-control" style="width: 200px;">
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
                                                            <a href="medical_record_view.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-success">
                                                                <i class="bx bx-show"></i>
                                                            </a>
                                                            <a href="?action=edit&id=<?php echo $record['id']; ?>" class="btn btn-sm btn-primary">
                                                                <i class="bx bx-edit-alt"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?>')" title="Delete">
                                                                <i class="bx bx-trash"></i>
                                                            </button>
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
                                        <select class="form-select" id="patient_id" name="patient_id" required>
                                            <option value="">Select Patient</option>
                                            <?php foreach ($patients as $patient): 
                                                $selected = ($record_data && $record_data['patient_id'] == $patient['id']) || (isset($_POST['patient_id']) && $_POST['patient_id'] == $patient['id']) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo $patient['id']; ?>" <?php echo $selected; ?>>
                                                    <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name'] . ' (Age: ' . $patient['age'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
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
                                                if ($record_data && $record_data['doctor_name'] == $doctor_full_name) {
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
                                        <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3" required placeholder="Primary diagnosis..."><?php echo $record_data ? htmlspecialchars($record_data['diagnosis']) : (isset($_POST['diagnosis']) ? htmlspecialchars($_POST['diagnosis']) : ''); ?></textarea>
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
                                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes..."><?php echo $record_data ? htmlspecialchars($record_data['notes']) : (isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''); ?></textarea>
                                    </div>

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
                                            <a href="medical_records_action.php" class="btn btn-outline-secondary">
                                                <i class="bx bx-arrow-back me-2"></i>Back to List
                                            </a>
                                            <div>
                                                <button type="reset" class="btn btn-outline-warning me-2">
                                                    <i class="bx bx-refresh me-2"></i>Reset Form
                                                </button>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bx bx-save me-2"></i><?php echo $action == 'add' ? 'Add Record' : 'Update Record'; ?>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the medical record for <strong id="patientName"></strong>?</p>
                <p class="text-muted">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="deleteLink" class="btn btn-danger">Delete Record</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });

        // BMI calculation
        const weightField = document.getElementById('weight');
        const heightField = document.getElementById('height');
        
        function calculateBMI() {
            const weight = parseFloat(weightField.value);
            const height = parseFloat(heightField.value);
            
            if (weight && height && height > 0) {
                const bmi = weight / Math.pow(height / 100, 2);
                const bmiDisplay = document.getElementById('bmiDisplay');
                
                if (!bmiDisplay) {
                    const bmiDiv = document.createElement('div');
                    bmiDiv.id = 'bmiDisplay';
                    bmiDiv.className = 'mt-2 alert alert-info';
                    heightField.parentNode.appendChild(bmiDiv);
                }
                
                let category = '';
                let categoryClass = '';
                
                if (bmi < 18.5) {
                    category = 'Underweight';
                    categoryClass = 'text-warning';
                } else if (bmi < 25) {
                    category = 'Normal';
                    categoryClass = 'text-success';
                } else if (bmi < 30) {
                    category = 'Overweight';
                    categoryClass = 'text-warning';
                } else {
                    category = 'Obese';
                    categoryClass = 'text-danger';
                }
                
                document.getElementById('bmiDisplay').innerHTML = `
                    <strong>BMI: ${bmi.toFixed(2)}</strong> 
                    <span class="${categoryClass}">(${category})</span>
                `;
            } else {
                const bmiDisplay = document.getElementById('bmiDisplay');
                if (bmiDisplay) {
                    bmiDisplay.remove();
                }
            }
        }
        
        if (weightField && heightField) {
            weightField.addEventListener('input', calculateBMI);
            heightField.addEventListener('input', calculateBMI);
            // Calculate on page load if values exist
            calculateBMI();
        }

        // Blood pressure validation
        const systolicField = document.getElementById('blood_pressure_systolic');
        const diastolicField = document.getElementById('blood_pressure_diastolic');
        
        function validateBloodPressure() {
            const systolic = parseInt(systolicField.value);
            const diastolic = parseInt(diastolicField.value);
            
            if (systolic && diastolic) {
                if (systolic <= diastolic) {
                    systolicField.classList.add('is-invalid');
                    diastolicField.classList.add('is-invalid');
                    
                    let errorDiv = document.getElementById('bpError');
                    if (!errorDiv) {
                        errorDiv = document.createElement('div');
                        errorDiv.id = 'bpError';
                        errorDiv.className = 'text-danger small mt-1';
                        diastolicField.parentNode.appendChild(errorDiv);
                    }
                    errorDiv.textContent = 'Systolic pressure must be higher than diastolic pressure.';
                } else {
                    systolicField.classList.remove('is-invalid');
                    diastolicField.classList.remove('is-invalid');
                    
                    const errorDiv = document.getElementById('bpError');
                    if (errorDiv) {
                        errorDiv.remove();
                    }
                }
            }
        }
        
        if (systolicField && diastolicField) {
            systolicField.addEventListener('input', validateBloodPressure);
            diastolicField.addEventListener('input', validateBloodPressure);
        }

        // Temperature validation
        const temperatureField = document.getElementById('temperature');
        if (temperatureField) {
            temperatureField.addEventListener('input', function() {
                const temp = parseFloat(this.value);
                if (temp && (temp < 30 || temp > 45)) {
                    this.classList.add('is-invalid');
                    
                    let errorDiv = document.getElementById('tempError');
                    if (!errorDiv) {
                        errorDiv = document.createElement('div');
                        errorDiv.id = 'tempError';
                        errorDiv.className = 'text-danger small mt-1';
                        this.parentNode.appendChild(errorDiv);
                    }
                    errorDiv.textContent = 'Temperature should be between 30°C and 45°C.';
                } else {
                    this.classList.remove('is-invalid');
                    const errorDiv = document.getElementById('tempError');
                    if (errorDiv) {
                        errorDiv.remove();
                    }
                }
            });
        }

        // Auto-save draft functionality (localStorage)
        const formInputs = form.querySelectorAll('input, textarea, select');
        const draftKey = 'medical_record_draft';
        
        // Load draft if exists
        const savedDraft = localStorage.getItem(draftKey);
        if (savedDraft && !form.dataset.editing) {
            const draftData = JSON.parse(savedDraft);
            formInputs.forEach(input => {
                if (draftData[input.name] && !input.value) {
                    input.value = draftData[input.name];
                }
            });
            
            if (Object.keys(draftData).length > 0) {
                const draftNotice = document.createElement('div');
                draftNotice.className = 'alert alert-info alert-dismissible';
                draftNotice.innerHTML = `
                    <i class="bx bx-info-circle me-2"></i>
                    Draft data has been restored. 
                    <button type="button" class="btn btn-sm btn-outline-info ms-2" onclick="localStorage.removeItem('${draftKey}'); this.parentElement.remove();">Clear Draft</button>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                form.insertBefore(draftNotice, form.firstChild);
            }
        }
        
        // Save draft on input
        let saveTimeout;
        formInputs.forEach(input => {
            input.addEventListener('input', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    const draftData = {};
                    formInputs.forEach(inp => {
                        if (inp.value) {
                            draftData[inp.name] = inp.value;
                        }
                    });
                    localStorage.setItem(draftKey, JSON.stringify(draftData));
                }, 1000);
            });
        });
        
        // Clear draft on successful submit
        form.addEventListener('submit', function() {
            localStorage.removeItem(draftKey);
        });
    }

    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert && alert.parentNode && !alert.classList.contains('alert-info')) {
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 300);
            }
        }, 5000);
    });

    // Enhanced select2-like functionality for dropdowns
    const selectElements = document.querySelectorAll('select');
    selectElements.forEach(select => {
        select.addEventListener('change', function() {
            if (this.value) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            }
        });
    });
});

// Delete confirmation
function confirmDelete(id, patientName) {
    document.getElementById('patientName').textContent = patientName;
    document.getElementById('deleteLink').href = '?action=delete&id=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Quick fill functions for testing/demo
function fillSampleData() {
    if (confirm('Fill form with sample data for testing?')) {
        document.getElementById('diagnosis').value = 'Common cold with mild fever';
        document.getElementById('symptoms').value = 'Runny nose, cough, mild headache, low-grade fever';
        document.getElementById('treatment').value = 'Rest, increased fluid intake, symptomatic treatment';
        document.getElementById('prescription').value = 'Paracetamol 500mg, 3 times daily for 3 days';
        document.getElementById('weight').value = '70';
        document.getElementById('height').value = '175';
        document.getElementById('temperature').value = '37.2';
        document.getElementById('pulse_rate').value = '78';
        document.getElementById('blood_pressure_systolic').value = '120';
        document.getElementById('blood_pressure_diastolic').value = '80';
        
        // Trigger BMI calculation
        document.getElementById('weight').dispatchEvent(new Event('input'));
    }
}

// Add sample data button in development mode
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('medicalRecordForm');
        if (form) {
            const sampleBtn = document.createElement('button');
            sampleBtn.type = 'button';
            sampleBtn.className = 'btn btn-outline-info btn-sm';
            sampleBtn.innerHTML = '<i class="bx bx-data me-1"></i>Fill Sample Data';
            sampleBtn.onclick = fillSampleData;
            
            const buttonContainer = form.querySelector('.d-flex.justify-content-between div:last-child');
            buttonContainer.insertBefore(sampleBtn, buttonContainer.firstChild);
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>