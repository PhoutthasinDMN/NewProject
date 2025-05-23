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

// ตรวจสอบ action
$action = isset($_GET['action']) ? $_GET['action'] : '';
$record_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;

$record = null;
$patients = [];
$errors = [];
$success_message = '';

// ดึงรายการผู้ป่วยทั้งหมดสำหรับ dropdown
$patients_sql = "SELECT id, first_name, last_name FROM patients ORDER BY first_name, last_name";
$patients_result = $conn->query($patients_sql);
if ($patients_result && $patients_result->num_rows > 0) {
    while ($row = $patients_result->fetch_assoc()) {
        $patients[] = $row;
    }
}

// ประมวลผลการส่งข้อมูล
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = (int)$_POST['patient_id'];
    $visit_date = $_POST['visit_date'];
    $diagnosis = trim($_POST['diagnosis']);
    $symptoms = trim($_POST['symptoms']);
    $treatment = trim($_POST['treatment']);
    $prescription = trim($_POST['prescription']);
    $notes = trim($_POST['notes']);
    $doctor_name = trim($_POST['doctor_name']);
    $next_appointment = !empty($_POST['next_appointment']) ? $_POST['next_appointment'] : null;
    
    // Vital Signs
    $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
    $height = !empty($_POST['height']) ? (float)$_POST['height'] : null;
    $pulse_rate = !empty($_POST['pulse_rate']) ? (int)$_POST['pulse_rate'] : null;
    $blood_pressure_systolic = !empty($_POST['blood_pressure_systolic']) ? (int)$_POST['blood_pressure_systolic'] : null;
    $blood_pressure_diastolic = !empty($_POST['blood_pressure_diastolic']) ? (int)$_POST['blood_pressure_diastolic'] : null;
    $temperature = !empty($_POST['temperature']) ? (float)$_POST['temperature'] : null;

    // Validation
    if (empty($patient_id)) {
        $errors[] = "Please select a patient.";
    }
    if (empty($visit_date)) {
        $errors[] = "Visit date is required.";
    }
    if (empty($diagnosis)) {
        $errors[] = "Diagnosis is required.";
    }

    if (empty($errors)) {
        if ($action == 'add') {
            // เพิ่มข้อมูลใหม่
            $sql = "INSERT INTO medical_records (patient_id, visit_date, diagnosis, symptoms, treatment, prescription, notes, doctor_name, weight, height, pulse_rate, blood_pressure_systolic, blood_pressure_diastolic, temperature, next_appointment) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssssssddiiids", $patient_id, $visit_date, $diagnosis, $symptoms, $treatment, $prescription, $notes, $doctor_name, $weight, $height, $pulse_rate, $blood_pressure_systolic, $blood_pressure_diastolic, $temperature, $next_appointment);
            
            if ($stmt->execute()) {
                $success_message = "Medical record added successfully!";
                header("Location: medical_records.php?patient_id=" . $patient_id);
                exit;
            } else {
                $errors[] = "Error adding medical record: " . $conn->error;
            }
        } elseif ($action == 'edit' && $record_id > 0) {
            // แก้ไขข้อมูล
            $sql = "UPDATE medical_records SET patient_id = ?, visit_date = ?, diagnosis = ?, symptoms = ?, treatment = ?, prescription = ?, notes = ?, doctor_name = ?, weight = ?, height = ?, pulse_rate = ?, blood_pressure_systolic = ?, blood_pressure_diastolic = ?, temperature = ?, next_appointment = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssssssddiiidsi", $patient_id, $visit_date, $diagnosis, $symptoms, $treatment, $prescription, $notes, $doctor_name, $weight, $height, $pulse_rate, $blood_pressure_systolic, $blood_pressure_diastolic, $temperature, $next_appointment, $record_id);
            
            if ($stmt->execute()) {
                $success_message = "Medical record updated successfully!";
                header("Location: medical_records.php?patient_id=" . $patient_id);
                exit;
            } else {
                $errors[] = "Error updating medical record: " . $conn->error;
            }
        }
    }
}

// ลบข้อมูล
if ($action == 'delete' && $record_id > 0) {
    $sql = "DELETE FROM medical_records WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $record_id);
    
    if ($stmt->execute()) {
        header("Location: medical_records.php");
        exit;
    } else {
        $errors[] = "Error deleting medical record: " . $conn->error;
    }
}

// ดึงข้อมูลสำหรับแก้ไขหรือดู
if (($action == 'edit' || $action == 'view') && $record_id > 0) {
    $sql = "SELECT mr.*, p.first_name, p.last_name FROM medical_records mr JOIN patients p ON mr.patient_id = p.id WHERE mr.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $record = $result->fetch_assoc();
        $patient_id = $record['patient_id'];
    } else {
        header("Location: medical_records.php");
        exit;
    }
}

// ตั้งค่าหัวข้อหน้า
$page_title = '';
switch ($action) {
    case 'add':
        $page_title = 'Add New Medical Record';
        break;
    case 'edit':
        $page_title = 'Edit Medical Record';
        break;
    case 'view':
        $page_title = 'View Medical Record';
        break;
    default:
        $page_title = 'Medical Records';
        break;
}
?>
<!DOCTYPE html>
<html
    lang="en"
    class="light-style layout-menu-fixed"
    dir="ltr"
    data-theme="theme-default"
    data-assets-path="../assets/"
    data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title><?php echo htmlspecialchars($page_title); ?> | Sneat</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- Helpers -->
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>

    <style>
        .view-mode .form-control,
        .view-mode .form-select {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            pointer-events: none;
        }
        .required {
            color: red;
        }
        .vital-signs-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .bmi-display {
            font-size: 1.2em;
            font-weight: bold;
        }
        .bmi-normal { color: #28a745; }
        .bmi-underweight { color: #17a2b8; }
        .bmi-overweight { color: #ffc107; }
        .bmi-obese { color: #dc3545; }
    </style>
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->
            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                <div class="app-brand demo">
                    <a href="../dashboard/index.php" class="app-brand-link">
                        <span class="app-brand-logo demo">
                            <svg
                                width="25"
                                viewBox="0 0 25 42"
                                version="1.1"
                                xmlns="http://www.w3.org/2000/svg"
                                xmlns:xlink="http://www.w3.org/1999/xlink">
                                <defs>
                                    <path
                                        d="M13.7918663,0.358365126 L3.39788168,7.44174259 C0.566865006,9.69408886 -0.379795268,12.4788597 0.557900856,15.7960551 C0.68998853,16.2305145 1.09562888,17.7872135 3.12357076,19.2293357 C3.8146334,19.7207684 5.32369333,20.3834223 7.65075054,21.2172976 L7.59773219,21.2525164 L2.63468769,24.5493413 C0.445452254,26.3002124 0.0884951797,28.5083815 1.56381646,31.1738486 C2.83770406,32.8170431 5.20850219,33.2640127 7.09180128,32.5391577 C8.347334,32.0559211 11.4559176,30.0011079 16.4175519,26.3747182 C18.0338572,24.4997857 18.6973423,22.4544883 18.4080071,20.2388261 C17.963753,17.5346866 16.1776345,15.5799961 13.0496516,14.3747546 L10.9194936,13.4715819 L18.6192054,7.984237 L13.7918663,0.358365126 Z"
                                        id="path-1"></path>
                                    <path
                                        d="M5.47320593,6.00457225 C4.05321814,8.216144 4.36334763,10.0722806 6.40359441,11.5729822 C8.61520715,12.571656 10.0999176,13.2171421 10.8577257,13.5094407 L15.5088241,14.433041 L18.6192054,7.984237 C15.5364148,3.11535317 13.9273018,0.573395879 13.7918663,0.358365126 C13.5790555,0.511491653 10.8061687,2.3935607 5.47320593,6.00457225 Z"
                                        id="path-3"></path>
                                    <path
                                        d="M7.50063644,21.2294429 L12.3234468,23.3159332 C14.1688022,24.7579751 14.397098,26.4880487 13.008334,28.506154 C11.6195701,30.5242593 10.3099883,31.790241 9.07958868,32.3040991 C5.78142938,33.4346997 4.13234973,34 4.13234973,34 C4.13234973,34 2.75489982,33.0538207 2.37032616e-14,31.1614621 C-0.55822714,27.8186216 -0.55822714,26.0572515 -4.05231404e-15,25.8773518 C0.83734071,25.6075023 2.77988457,22.8248993 3.3049379,22.52991 C3.65497346,22.3332504 5.05353963,21.8997614 7.50063644,21.2294429 Z"
                                        id="path-4"></path>
                                    <path
                                        d="M20.6,7.13333333 L25.6,13.8 C26.2627417,14.6836556 26.0836556,15.9372583 25.2,16.6 C24.8538077,16.8596443 24.4327404,17 24,17 L14,17 C12.8954305,17 12,16.1045695 12,15 C12,14.5672596 12.1403557,14.1461923 12.4,13.8 L17.4,7.13333333 C18.0627417,6.24967773 19.3163444,6.07059163 20.2,6.73333333 C20.3516113,6.84704183 20.4862915,6.981722 20.6,7.13333333 Z"
                                        id="path-5"></path>
                                </defs>
                                <g id="g-app-brand" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                    <g id="Brand-Logo" transform="translate(-27.000000, -15.000000)">
                                        <g id="Icon" transform="translate(27.000000, 15.000000)">
                                            <g id="Mask" transform="translate(0.000000, 8.000000)">
                                                <mask id="mask-2" fill="white">
                                                    <use xlink:href="#path-1"></use>
                                                </mask>
                                                <use fill="#696cff" xlink:href="#path-1"></use>
                                                <g id="Path-3" mask="url(#mask-2)">
                                                    <use fill="#696cff" xlink:href="#path-3"></use>
                                                    <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-3"></use>
                                                </g>
                                                <g id="Path-4" mask="url(#mask-2)">
                                                    <use fill="#696cff" xlink:href="#path-4"></use>
                                                    <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-4"></use>
                                                </g>
                                            </g>
                                            <g
                                                id="Triangle"
                                                transform="translate(19.000000, 11.000000) rotate(-300.000000) translate(-19.000000, -11.000000) ">
                                                <use fill="#696cff" xlink:href="#path-5"></use>
                                                <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-5"></use>
                                            </g>
                                        </g>
                                    </g>
                                </g>
                            </svg>
                        </span>
                        <span class="app-brand-text demo menu-text fw-bolder ms-2">Sneat</span>
                    </a>

                    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
                        <i class="bx bx-chevron-left bx-sm align-middle"></i>
                    </a>
                </div>

                <div class="menu-inner-shadow"></div>

                <ul class="menu-inner py-1">
                    <!-- Dashboard -->
                    <li class="menu-item">
                        <a href="../dashboard/index.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-home-circle"></i>
                            <div data-i18n="Analytics">Dashboard</div>
                        </a>
                    </li>
                    <!-- Profile -->
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-user"></i>
                            <div data-i18n="Profile">Profile</div>
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="../dashboard/profile.php" class="menu-link">
                                    <div data-i18n="View Profile">View Profile</div>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="../dashboard/profile-edit.php" class="menu-link">
                                    <div data-i18n="Edit Profile">Edit Profile</div>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Patients -->
                    <li class="menu-item active open">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-user-plus"></i>
                            <div data-i18n="Patients">Patients</div>
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="patients.php" class="menu-link">
                                    <div data-i18n="All Patients">All Patients</div>
                                </a>
                            </li>
                            <li class="menu-item active">
                                <a href="medical_records.php" class="menu-link">
                                    <div data-i18n="Medical Records">Medical Records</div>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Settings (Admin Only) -->
                    <?php if ($isAdmin): ?>
                        <li class="menu-item">
                            <a href="../dashboard/settings.php" class="menu-link">
                                <i class="menu-icon tf-icons bx bx-cog"></i>
                                <div data-i18n="Settings">Settings</div>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Logout -->
                    <li class="menu-item">
                        <a href="../auth/logout.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-log-out"></i>
                            <div data-i18n="Logout">Logout</div>
                        </a>
                    </li>
                </ul>
            </aside>
            <!-- / Menu -->

            <!-- Layout container -->
            <div class="layout-page">
                <!-- Navbar -->
               <?php include '../includes/sidebar.php'; ?>
                <!-- / Navbar -->

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content -->
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <h4 class="fw-bold py-3 mb-4">
                            <span class="text-muted fw-light">Patients / Medical Records /</span> <?php echo htmlspecialchars($page_title); ?>
                        </h4>

                        <!-- Error Messages -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <h6 class="alert-heading d-flex align-items-center mb-1">
                                    <i class="bx bx-error-circle me-2"></i>
                                    <span class="fw-bold">Errors found!</span>
                                </h6>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Success Message -->
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success alert-dismissible" role="alert">
                                <h6 class="alert-heading d-flex align-items-center mb-1">
                                    <i class="bx bx-check-circle me-2"></i>
                                    <span class="fw-bold">Success!</span>
                                </h6>
                                <p class="mb-0"><?php echo htmlspecialchars($success_message); ?></p>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Medical Record Form -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <h5 class="card-header">
                                        <i class="bx bx-file-plus me-2"></i>
                                        <?php echo htmlspecialchars($page_title); ?>
                                        <?php if ($record && $action == 'view'): ?>
                                            <span class="text-muted">- <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></span>
                                        <?php endif; ?>
                                    </h5>
                                    <div class="card-body <?php echo $action == 'view' ? 'view-mode' : ''; ?>">
                                        <form method="POST" action="">
                                            <!-- Basic Information -->
                                            <div class="row">
                                                <!-- Patient Selection -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="patient_id" class="form-label">
                                                        Patient <span class="required">*</span>
                                                    </label>
                                                    <select class="form-select" id="patient_id" name="patient_id" required <?php echo $action == 'view' ? 'disabled' : ''; ?>>
                                                        <option value="">Select Patient</option>
                                                        <?php foreach ($patients as $patient): ?>
                                                            <option value="<?php echo $patient['id']; ?>" 
                                                                <?php echo ($patient_id == $patient['id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) . ' (ID: ' . str_pad($patient['id'], 4, '0', STR_PAD_LEFT) . ')'; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <!-- Visit Date -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="visit_date" class="form-label">
                                                        Visit Date & Time <span class="required">*</span>
                                                    </label>
                                                    <input type="datetime-local" class="form-control" id="visit_date" name="visit_date" 
                                                           value="<?php echo $record ? date('Y-m-d\TH:i', strtotime($record['visit_date'])) : date('Y-m-d\TH:i'); ?>" 
                                                           required <?php echo $action == 'view' ? 'readonly' : ''; ?>>
                                                </div>
                                            </div>

                                            <!-- Diagnosis -->
                                            <div class="mb-3">
                                                <label for="diagnosis" class="form-label">
                                                    Diagnosis <span class="required">*</span>
                                                </label>
                                                <input type="text" class="form-control" id="diagnosis" name="diagnosis" 
                                                       value="<?php echo $record ? htmlspecialchars($record['diagnosis']) : ''; ?>" 
                                                       placeholder="Enter diagnosis" required <?php echo $action == 'view' ? 'readonly' : ''; ?>>
                                            </div>

                                            <!-- Symptoms -->
                                            <div class="mb-3">
                                                <label for="symptoms" class="form-label">
                                                    <i class="bx bx-pulse me-1"></i>Symptoms
                                                </label>
                                                <textarea class="form-control" id="symptoms" name="symptoms" rows="3" 
                                                          placeholder="Describe patient's symptoms" <?php echo $action == 'view' ? 'readonly' : ''; ?>><?php echo $record ? htmlspecialchars($record['symptoms']) : ''; ?></textarea>
                                            </div>

                                            <!-- Treatment -->
                                            <div class="mb-3">
                                                <label for="treatment" class="form-label">
                                                    <i class="bx bx-plus-medical me-1"></i>Treatment
                                                </label>
                                                <textarea class="form-control" id="treatment" name="treatment" rows="3" 
                                                          placeholder="Describe treatment plan" <?php echo $action == 'view' ? 'readonly' : ''; ?>><?php echo $record ? htmlspecialchars($record['treatment']) : ''; ?></textarea>
                                            </div>

                                            <!-- Prescription -->
                                            <div class="mb-3">
                                                <label for="prescription" class="form-label">
                                                    <i class="bx bx-capsule me-1"></i>Prescription
                                                </label>
                                                <textarea class="form-control" id="prescription" name="prescription" rows="3" 
                                                          placeholder="List medications and dosages" <?php echo $action == 'view' ? 'readonly' : ''; ?>><?php echo $record ? htmlspecialchars($record['prescription']) : ''; ?></textarea>
                                            </div>

                                            <!-- Vital Signs Section -->
                                            <div class="vital-signs-section">
                                                <h6 class="mb-3"><i class="bx bx-heart me-2 text-danger"></i>Vital Signs & Physical Measurements</h6>
                                                
                                                <!-- Weight, Height, BMI, Temperature -->
                                                <div class="row">
                                                    <div class="col-md-3 mb-3">
                                                        <label for="weight" class="form-label">
                                                            <i class="bx bx-dumbbell me-1"></i>Weight (kg)
                                                        </label>
                                                        <input type="number" step="0.1" min="0" max="300" class="form-control" id="weight" name="weight" 
                                                               value="<?php echo $record ? $record['weight'] : ''; ?>" 
                                                               placeholder="65.5" <?php echo $action == 'view' ? 'readonly' : ''; ?>>
                                                    </div>

                                                    <div class="col-md-3 mb-3">
                                                        <label for="height" class="form-label">
                                                            <i class="bx bx-ruler me-1"></i>Height (cm)
                                                        </label>
                                                        <input type="number" step="0.1" min="0" max="250" class="form-control" id="height" name="height" 
                                                               value="<?php echo $record ? $record['height'] : ''; ?>" 
                                                               placeholder="170.0" <?php echo $action == 'view' ? 'readonly' : ''; ?>>
                                                    </div>

                                                    <div class="col-md-3 mb-3">
                                                        <label for="bmi_display" class="form-label">
                                                            <i class="bx bx-calculator me-1"></i>BMI
                                                        </label>
                                                        <input type="text" class="form-control bmi-display" id="bmi_display" 
                                                               value="<?php echo $record && $record['bmi'] ? number_format($record['bmi'], 2) : ''; ?>" 
                                                               placeholder="Auto-calculated" readonly>
                                                        <small class="text-muted">Calculated automatically</small>
                                                    </div>

                                                    <div class="col-md-3 mb-3">
                                                        <label for="temperature" class="form-label">
                                                            <i class="bx bx-thermometer me-1"></i>Temperature (°C)
                                                        </label>
                                                        <input type="number" step="0.1" min="30" max="45" class="form-control" id="temperature" name="temperature" 
                                                               value="<?php echo $record ? $record['temperature'] : ''; ?>" 
                                                               placeholder="36.5" <?php echo $action == 'view' ? 'readonly' : ''; ?>>
                                                    </div>
                                                </div>

                                                <!-- Pulse Rate and Blood Pressure -->
                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label for="pulse_rate" class="form-label">
                                                            <i class="bx bx-heart me-1 text-danger"></i>Pulse Rate (bpm)
                                                        </label>
                                                        <input type="number" min="30" max="200" class="form-control" id="pulse_rate" name="pulse_rate" 
                                                               value="<?php echo $record ? $record['pulse_rate'] : ''; ?>" 
                                                               placeholder="72" <?php echo $action == 'view' ? 'readonly' : ''; ?>>
                                                    </div>

                                                    <div class="col-md-4 mb-3">
                                                        <label for="blood_pressure_systolic" class="form-label">
                                                            <i class="bx bx-droplet me-1 text-primary"></i>Blood Pressure - Systolic (mmHg)
                                                        </label>
                                                        <input type="number" min="60" max="250" class="form-control" id="blood_pressure_systolic" name="blood_pressure_systolic" 
                                                               value="<?php echo $record ? $record['blood_pressure_systolic'] : ''; ?>" 
                                                               placeholder="120" <?php echo $action == 'view' ? 'readonly' : ''; ?>>
                                                    </div>

                                                    <div class="col-md-4 mb-3">
                                                        <label for="blood_pressure_diastolic" class="form-label">
                                                            <i class="bx bx-droplet me-1 text-info"></i>Blood Pressure - Diastolic (mmHg)
                                                        </label>
                                                        <input type="number" min="40" max="150" class="form-control" id="blood_pressure_diastolic" name="blood_pressure_diastolic" 
                                                               value="<?php echo $record ? $record['blood_pressure_diastolic'] : ''; ?>" 
                                                               placeholder="80" <?php echo $action == 'view' ? 'readonly' : ''; ?>>
                                                    </div>
                                                </div>

                                                <!-- Live BMI and BP Status Display -->
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div id="bmi_status" class="alert alert-secondary" style="display: none;">
                                                            <strong>BMI Status:</strong> <span id="bmi_classification"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div id="bp_status" class="alert alert-secondary" style="display: none;">
                                                            <strong>Blood Pressure Status:</strong> <span id="bp_classification"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Doctor and Appointment -->
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="doctor_name" class="form-label">
                                                        <i class="bx bx-user-circle me-1"></i>Doctor Name
                                                    </label>
                                                    <input type="text" class="form-control" id="doctor_name" name="doctor_name" 
                                                           value="<?php echo $record ? htmlspecialchars($record['doctor_name']) : ''; ?>" 
                                                           placeholder="Doctor's name" <?php echo $action == 'view' ? 'readonly' : ''; ?>>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="next_appointment" class="form-label">
                                                        <i class="bx bx-calendar-check me-1"></i>Next Appointment
                                                    </label>
                                                    <input type="datetime-local" class="form-control" id="next_appointment" name="next_appointment" 
                                                           value="<?php echo ($record && $record['next_appointment']) ? date('Y-m-d\TH:i', strtotime($record['next_appointment'])) : ''; ?>" 
                                                           <?php echo $action == 'view' ? 'readonly' : ''; ?>>
                                                </div>
                                            </div>

                                            <!-- Notes -->
                                            <div class="mb-4">
                                                <label for="notes" class="form-label">
                                                    <i class="bx bx-note me-1"></i>Additional Notes
                                                </label>
                                                <textarea class="form-control" id="notes" name="notes" rows="4" 
                                                          placeholder="Any additional notes or observations" <?php echo $action == 'view' ? 'readonly' : ''; ?>><?php echo $record ? htmlspecialchars($record['notes']) : ''; ?></textarea>
                                            </div>

                                            <?php if ($record && $action == 'view'): ?>
                                                <!-- Record Information for View Mode -->
                                                <hr>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p class="text-muted mb-1">
                                                            <i class="bx bx-time me-1"></i>
                                                            <strong>Created:</strong> <?php echo date('d/m/Y H:i', strtotime($record['created_at'])); ?>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <?php if ($record['updated_at'] != $record['created_at']): ?>
                                                            <p class="text-muted mb-1">
                                                                <i class="bx bx-edit me-1"></i>
                                                                <strong>Last Updated:</strong> <?php echo date('d/m/Y H:i', strtotime($record['updated_at'])); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Action Buttons -->
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <?php if ($patient_id): ?>
                                                        <a href="medical_records.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-outline-secondary">
                                                            <i class="bx bx-arrow-back me-1"></i> Back to Records
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="medical_records.php" class="btn btn-outline-secondary">
                                                            <i class="bx bx-arrow-back me-1"></i> Back to All Records
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <?php if ($action == 'view'): ?>
                                                        <a href="medical_records_action.php?action=edit&id=<?php echo $record['id']; ?>" class="btn btn-primary">
                                                            <i class="bx bx-edit-alt me-1"></i> Edit Record
                                                        </a>
                                                    <?php else: ?>
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="bx bx-save me-1"></i>
                                                            <?php echo $action == 'add' ? 'Save Record' : 'Update Record'; ?>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- / Content -->

                    <!-- Footer -->
                    <footer class="content-footer footer bg-footer-theme">
                        <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
                            <div class="mb-2 mb-md-0">
                                ©
                                <script>
                                    document.write(new Date().getFullYear());
                                </script>
                                , made with ❤️ by
                                <a href="https://themeselection.com" target="_blank" class="footer-link fw-bolder">ThemeSelection</a>
                            </div>
                        </div>
                    </footer>
                    <!-- / Footer -->

                    <div class="content-backdrop fade"></div>
                </div>
                <!-- Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>

        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <!-- / Layout wrapper -->

    <!-- Core JS -->
    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    <script src="../assets/js/main.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-set current date/time for new records
            <?php if ($action == 'add' && !$record): ?>
                const visitDateInput = document.getElementById('visit_date');
                if (!visitDateInput.value) {
                    const now = new Date();
                    const year = now.getFullYear();
                    const month = String(now.getMonth() + 1).padStart(2, '0');
                    const day = String(now.getDate()).padStart(2, '0');
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    
                    visitDateInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
                }
            <?php endif; ?>

            // BMI Calculator with Real-time Updates
            const weightInput = document.getElementById('weight');
            const heightInput = document.getElementById('height');
            const bmiDisplay = document.getElementById('bmi_display');
            const bmiStatus = document.getElementById('bmi_status');
            const bmiClassification = document.getElementById('bmi_classification');

            function calculateBMI() {
                const weight = parseFloat(weightInput.value);
                const height = parseFloat(heightInput.value);

                if (weight && height && height > 0) {
                    const heightInMeters = height / 100;
                    const bmi = weight / (heightInMeters * heightInMeters);
                    bmiDisplay.value = bmi.toFixed(2);
                    
                    // Reset classes
                    bmiDisplay.className = 'form-control bmi-display';
                    
                    // BMI Classification
                    let classification = '';
                    let alertClass = 'alert-secondary';
                    
                    if (bmi < 18.5) {
                        classification = `${bmi.toFixed(2)} - Underweight`;
                        bmiDisplay.classList.add('bmi-underweight');
                        alertClass = 'alert-info';
                    } else if (bmi < 25) {
                        classification = `${bmi.toFixed(2)} - Normal Weight`;
                        bmiDisplay.classList.add('bmi-normal');
                        alertClass = 'alert-success';
                    } else if (bmi < 30) {
                        classification = `${bmi.toFixed(2)} - Overweight`;
                        bmiDisplay.classList.add('bmi-overweight');
                        alertClass = 'alert-warning';
                    } else {
                        classification = `${bmi.toFixed(2)} - Obese`;
                        bmiDisplay.classList.add('bmi-obese');
                        alertClass = 'alert-danger';
                    }
                    
                    // Update status display
                    bmiClassification.textContent = classification;
                    bmiStatus.className = `alert ${alertClass}`;
                    bmiStatus.style.display = 'block';
                } else {
                    bmiDisplay.value = '';
                    bmiDisplay.className = 'form-control bmi-display';
                    bmiStatus.style.display = 'none';
                }
            }

            // Blood Pressure Status Calculator
            const systolicInput = document.getElementById('blood_pressure_systolic');
            const diastolicInput = document.getElementById('blood_pressure_diastolic');
            const bpStatus = document.getElementById('bp_status');
            const bpClassification = document.getElementById('bp_classification');

            function calculateBPStatus() {
                const systolic = parseInt(systolicInput.value);
                const diastolic = parseInt(diastolicInput.value);

                if (systolic && diastolic) {
                    let classification = '';
                    let alertClass = 'alert-secondary';
                    
                    if (systolic >= 180 || diastolic >= 120) {
                        classification = `${systolic}/${diastolic} mmHg - Hypertensive Crisis`;
                        alertClass = 'alert-danger';
                    } else if (systolic >= 140 || diastolic >= 90) {
                        classification = `${systolic}/${diastolic} mmHg - High Blood Pressure`;
                        alertClass = 'alert-danger';
                    } else if (systolic >= 130 || diastolic >= 80) {
                        classification = `${systolic}/${diastolic} mmHg - Elevated`;
                        alertClass = 'alert-warning';
                    } else if (systolic >= 90 && diastolic >= 60) {
                        classification = `${systolic}/${diastolic} mmHg - Normal`;
                        alertClass = 'alert-success';
                    } else {
                        classification = `${systolic}/${diastolic} mmHg - Low`;
                        alertClass = 'alert-info';
                    }
                    
                    // Validation: Systolic should be higher than diastolic
                    if (systolic <= diastolic) {
                        classification = `${systolic}/${diastolic} mmHg - Invalid (Systolic should be higher)`;
                        alertClass = 'alert-danger';
                    }
                    
                    bpClassification.textContent = classification;
                    bpStatus.className = `alert ${alertClass}`;
                    bpStatus.style.display = 'block';
                } else {
                    bpStatus.style.display = 'none';
                }
            }

            // Add event listeners
            if (weightInput && heightInput && bmiDisplay) {
                weightInput.addEventListener('input', calculateBMI);
                heightInput.addEventListener('input', calculateBMI);
                calculateBMI(); // Calculate on page load
            }

            if (systolicInput && diastolicInput) {
                systolicInput.addEventListener('input', calculateBPStatus);
                diastolicInput.addEventListener('input', calculateBPStatus);
                calculateBPStatus(); // Calculate on page load
            }

            // Form validation
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const systolic = parseInt(systolicInput.value);
                    const diastolic = parseInt(diastolicInput.value);
                    
                    if (systolic && diastolic && systolic <= diastolic) {
                        e.preventDefault();
                        alert('กรุณาตรวจสอบค่าความดันโลหิต: ค่าบน (Systolic) ต้องมากกว่าค่าล่าง (Diastolic)');
                        systolicInput.focus();
                        return false;
                    }
                });
            }
        });
    </script>
</body>

</html>