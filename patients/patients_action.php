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

// ตรวจสอบการกระทำที่ต้องการ (add, edit, delete)
$action = isset($_GET['action']) ? $_GET['action'] : '';
$patient_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ข้อความแจ้งเตือน
$error = '';
$success = '';

// ตรวจสอบสิทธิ์สำหรับการลบ - เฉพาะ admin เท่านั้น
if ($action == 'delete' && !$isAdmin) {
    header("Location: patients.php?error=access_denied");
    exit;
}

// ดึงโครงสร้างตาราง patients เพื่อดูว่ามีคอลัมน์อะไรบ้าง
$table_structure_sql = "DESCRIBE patients";
$table_structure_result = $conn->query($table_structure_sql);
$table_columns = [];
$column_types = []; // เก็บข้อมูลประเภทของคอลัมน์

if ($table_structure_result && $table_structure_result->num_rows > 0) {
    while ($col = $table_structure_result->fetch_assoc()) {
        $table_columns[] = $col['Field'];
        $column_types[$col['Field']] = $col['Type']; // เก็บประเภทของคอลัมน์
    }
} else {
    $error = alert("ไม่สามารถดึงโครงสร้างตารางได้", "danger");
}

// ตัวแปรสำหรับเก็บข้อมูลผู้ป่วย - ใช้คอลัมน์จากตารางจริง
$patient = array_fill_keys($table_columns, '');
// ลบคอลัมน์ id ออกจากการตั้งค่าเริ่มต้น
if (isset($patient['id'])) {
    unset($patient['id']);
}

// ถ้าเป็นการแก้ไขหรือลบ ให้ดึงข้อมูลผู้ป่วยก่อน
if (($action == 'edit' || $action == 'delete') && $patient_id > 0) {
    $sql = "SELECT * FROM patients WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // ถ้าไม่พบผู้ป่วย ให้กลับไปที่หน้ารายการผู้ป่วย
        header("Location: patients.php");
        exit;
    }

    $patient = $result->fetch_assoc();
}

// การลบผู้ป่วย - เฉพาะ admin เท่านั้น
if ($action == 'delete' && $patient_id > 0 && $isAdmin) {
    // ตรวจสอบว่ามี medical records หรือไม่
    $check_records_sql = "SELECT COUNT(*) as count FROM medical_records WHERE patient_id = ?";
    $check_stmt = $conn->prepare($check_records_sql);
    $check_stmt->bind_param("i", $patient_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $records_count = $check_result->fetch_assoc()['count'];

    if ($records_count > 0) {
        // มี medical records ให้แจ้งเตือนและขอยืนยัน
        if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
            // ลบ medical records ก่อน
            $delete_records_sql = "DELETE FROM medical_records WHERE patient_id = ?";
            $delete_records_stmt = $conn->prepare($delete_records_sql);
            $delete_records_stmt->bind_param("i", $patient_id);
            $delete_records_stmt->execute();
        } else {
            // แสดงหน้าขอยืนยัน
            $action = 'confirm_delete';
        }
    }

    if ($action != 'confirm_delete') {
        $sql = "DELETE FROM patients WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $patient_id);

        if ($stmt->execute()) {
            header("Location: patients.php?success=deleted");
        } else {
            header("Location: patients.php?error=delete_failed");
        }
        exit;
    }
}

// การบันทึกข้อมูลผู้ป่วย (เพิ่มหรือแก้ไข) - ทุกคนทำได้
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // รับค่าจากฟอร์มเฉพาะฟิลด์ที่มีในตาราง
    $formData = [];
    foreach ($_POST as $key => $value) {
        if (in_array($key, $table_columns)) {
            // ตรวจสอบและแปลงรูปแบบวันที่ถ้าจำเป็น
            if (strpos(strtolower($column_types[$key]), 'date') !== false) {
                // แปลงวันที่จากรูปแบบ DD/MM/YYYY เป็น YYYY-MM-DD
                if (!empty($value) && preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
                    $date_parts = explode('/', $value);
                    if (count($date_parts) === 3) {
                        $value = $date_parts[2] . '-' . str_pad($date_parts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($date_parts[0], 2, '0', STR_PAD_LEFT);
                    }
                }
                // แปลงวันที่จากรูปแบบ DD-MM-YYYY เป็น YYYY-MM-DD
                elseif (!empty($value) && preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $value)) {
                    $date_parts = explode('-', $value);
                    if (count($date_parts) === 3) {
                        $value = $date_parts[2] . '-' . str_pad($date_parts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($date_parts[0], 2, '0', STR_PAD_LEFT);
                    }
                }
            }
            $formData[$key] = sanitize($value);
        }
    }

    // ตรวจสอบว่ามีข้อมูลที่จำเป็นหรือไม่
    $required_message = '';

    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($formData['first_name'])) {
        $required_message = "กรุณากรอกชื่อผู้ป่วย";
    } elseif (empty($formData['last_name'])) {
        $required_message = "กรุณากรอกนามสกุลผู้ป่วย";
    }

    if (!empty($required_message)) {
        $error = alert($required_message, "danger");
    } else {
        // กรณีมีฟิลด์ created_at และกำลังเพิ่มข้อมูลใหม่
        if (in_array('created_at', $table_columns) && $action != 'edit') {
            $formData['created_at'] = date('Y-m-d H:i:s');
        }

        // กรณีมีฟิลด์ updated_at
        if (in_array('updated_at', $table_columns)) {
            $formData['updated_at'] = date('Y-m-d H:i:s');
        }

        // เพิ่มข้อมูลผู้ที่แก้ไข
        if ($action == 'edit' && in_array('updated_by', $table_columns)) {
            $formData['updated_by'] = $user_id;
        } elseif ($action != 'edit' && in_array('created_by', $table_columns)) {
            $formData['created_by'] = $user_id;
        }

        // สร้างคำสั่ง SQL ตามการกระทำ
        if ($action == 'edit' && $patient_id > 0) {
            // อัปเดตข้อมูลผู้ป่วย - ทุกคนทำได้
            $setClause = [];
            foreach ($formData as $key => $value) {
                $setClause[] = "$key = ?";
            }

            // เพิ่ม id เข้าไปในเงื่อนไข WHERE
            $sql = "UPDATE patients SET " . implode(', ', $setClause) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);

            // สร้าง parameter type และ values
            $paramTypes = str_repeat('s', count($formData)) . 'i'; // string ตามจำนวน + integer 1 ตัว
            $paramValues = array_values($formData);
            $paramValues[] = $patient_id;

            // ใช้ refs เพื่อ bind parameter
            $refs = [];
            foreach ($paramValues as $key => $value) {
                $refs[$key] = &$paramValues[$key];
            }
            array_unshift($refs, $stmt, $paramTypes);
            call_user_func_array('mysqli_stmt_bind_param', $refs);
        } else {
            // เพิ่มผู้ป่วยใหม่ - ทุกคนทำได้
            $columns = array_keys($formData);
            $placeholders = array_fill(0, count($columns), '?');

            $sql = "INSERT INTO patients (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $conn->prepare($sql);

            // สร้าง parameter type และ values
            $paramTypes = str_repeat('s', count($formData));
            $paramValues = array_values($formData);

            // ใช้ refs เพื่อ bind parameter
            $refs = [];
            foreach ($paramValues as $key => $value) {
                $refs[$key] = &$paramValues[$key];
            }
            array_unshift($refs, $stmt, $paramTypes);
            call_user_func_array('mysqli_stmt_bind_param', $refs);
        }

        if ($stmt->execute()) {
            $action_text = ($action == 'edit') ? "Patient information has been successfully updated." : "Patient added successfully";
            $success = alert($action_text, "success");

            if ($action != 'edit') {
                // ล้างข้อมูลในฟอร์มหลังจากเพิ่มผู้ป่วยสำเร็จ
                $patient = array_fill_keys($table_columns, '');
                if (isset($patient['id'])) {
                    unset($patient['id']);
                }
            }
        } else {
            $error = alert("An error occurred.: " . $stmt->error, "danger");
        }
    }
}

// หัวข้อและชื่อปุ่มตามการกระทำ
if ($action == 'confirm_delete') {
    $page_title = 'Confirm Delete Patient';
    $button_text = '';
} else {
    $page_title = ($action == 'edit') ? 'Edit Patient' : 'Add New Patient';
    $button_text = ($action == 'edit') ? 'Update Patient' : 'Add Patient';
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

    <title><?php echo $page_title; ?> | Onemeds</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" />

    <!-- Icons. Uncomment required icon fonts -->
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- Page CSS -->
    <style>
        .user-role-indicator {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-left: 10px;
        }

        .admin-role-indicator {
            background: linear-gradient(135deg, #007bff, #0056b3);
        }

        .permission-info {
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .admin-permission-info {
            border-left-color: #007bff;
        }

        .confirm-delete-card {
            border: 2px solid #dc3545;
            background: #fff5f5;
        }

        .patient-info-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>

    <!-- Helpers -->
    <script src="../assets/vendor/js/helpers.js"></script>

    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="../assets/js/config.js"></script>
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
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
                            <span class="text-muted fw-light">Patients /</span> <?php echo $page_title; ?>
                            <span class="user-role-indicator <?php echo $isAdmin ? 'admin-role-indicator' : ''; ?>">
                                <?php echo $isAdmin ? 'Admin Access' : 'User Access'; ?>
                            </span>
                        </h4>

                        <?php if ($action == 'confirm_delete'): ?>
                            <!-- Confirm Delete Section -->
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card mb-4 confirm-delete-card">
                                        <h5 class="card-header text-danger">
                                            <i class="bx bx-warning me-2"></i>
                                            Confirm Patient Deletion
                                        </h5>
                                        <div class="card-body">
                                            <div class="patient-info-card">
                                                <h6 class="mb-3">Patient Information:</h6>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p><strong>Name:</strong> <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></p>
                                                        <p><strong>Age:</strong> <?php echo htmlspecialchars($patient['age']); ?></p>
                                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($patient['phone']); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($patient['email']); ?></p>
                                                        <p><strong>Patient ID:</strong> <?php echo str_pad($patient['id'], 4, '0', STR_PAD_LEFT); ?></p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="alert alert-danger">
                                                <h6 class="mb-2">
                                                    <i class="bx bx-error-circle me-1"></i>
                                                    Warning: This action cannot be undone!
                                                </h6>
                                                <p class="mb-2">This patient has <?php echo $records_count; ?> medical record(s) associated with them.</p>
                                                <p class="mb-0">Deleting this patient will also permanently delete all their medical records.</p>
                                            </div>

                                            <div class="text-center">
                                                <a href="patients_action.php?action=delete&id=<?php echo $patient_id; ?>&confirm=yes" 
                                                   class="btn btn-danger me-3">
                                                    <i class="bx bx-trash me-1"></i>
                                                    Yes, Delete Patient and All Records
                                                </a>
                                                <a href="patients.php" class="btn btn-secondary">
                                                    <i class="bx bx-x me-1"></i>
                                                    Cancel
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Add/Edit Form Section -->
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card mb-4">
                                        <h5 class="card-header"><?php echo $page_title; ?></h5>

                                        <!-- Alerts -->
                                        <?php if (!empty($error)): ?>
                                            <?php echo $error; ?>
                                        <?php endif; ?>

                                        <?php if (!empty($success)): ?>
                                            <?php echo $success; ?>
                                        <?php endif; ?>

                                        <div class="card-body">
                                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?action=' . $action . ($patient_id > 0 ? '&id=' . $patient_id : '')); ?>">
                                                <div class="row">
                                                    <div class="mb-3 col-md-6">
                                                        <label for="first_name" class="form-label">First Name *</label>
                                                        <input
                                                            class="form-control"
                                                            type="text"
                                                            id="first_name"
                                                            name="first_name"
                                                            value="<?php echo isset($patient['first_name']) ? htmlspecialchars($patient['first_name']) : ''; ?>"
                                                            required />
                                                    </div>
                                                    <div class="mb-3 col-md-6">
                                                        <label for="last_name" class="form-label">Last Name *</label>
                                                        <input class="form-control"
                                                            type="text"
                                                            id="last_name"
                                                            name="last_name"
                                                            value="<?php echo isset($patient['last_name']) ? htmlspecialchars($patient['last_name']) : ''; ?>"
                                                            required />
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="mb-3 col-md-3">
                                                        <label for="age" class="form-label">Age</label>
                                                        <input
                                                            class="form-control"
                                                            type="number"
                                                            id="age"
                                                            name="age"
                                                            min="1"
                                                            max="150"
                                                            value="<?php echo isset($patient['age']) ? htmlspecialchars($patient['age']) : ''; ?>" />
                                                    </div>
                                                    <div class="mb-3 col-md-6">
                                                        <label for="dob" class="form-label">Date of Birth</label>
                                                        <input
                                                            class="form-control"
                                                            type="date"
                                                            id="dob"
                                                            name="dob"
                                                            value="<?php
                                                                    if (isset($patient['dob']) && !empty($patient['dob']) && $patient['dob'] != '0000-00-00') {
                                                                        $date_obj = date_create($patient['dob']);
                                                                        if ($date_obj) {
                                                                            echo date_format($date_obj, 'Y-m-d');
                                                                        }
                                                                    }
                                                                    ?>" />
                                                        <small class="text-muted">Format: YYYY-MM-DD</small>
                                                    </div>
                                                    <div class="mb-3 col-md-3">
                                                        <label for="gender" class="form-label">Gender</label>
                                                        <select id="gender" name="gender" class="form-select">
                                                            <option value="">Select Gender</option>
                                                            <option value="M" <?php echo (isset($patient['gender']) && $patient['gender'] == 'M') ? 'selected' : ''; ?>>Male</option>
                                                            <option value="F" <?php echo (isset($patient['gender']) && $patient['gender'] == 'F') ? 'selected' : ''; ?>>Female</option>
                                                            <option value="O" <?php echo (isset($patient['gender']) && $patient['gender'] == 'O') ? 'selected' : ''; ?>>Other</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="address" class="form-label">Address</label>
                                                    <textarea
                                                        class="form-control"
                                                        id="address"
                                                        name="address"
                                                        rows="3"><?php echo isset($patient['address']) ? htmlspecialchars($patient['address']) : ''; ?></textarea>
                                                </div>

                                                <div class="row">
                                                    <div class="mb-3 col-md-6">
                                                        <label for="phone" class="form-label">Phone</label>
                                                        <input
                                                            class="form-control"
                                                            type="text"
                                                            id="phone"
                                                            name="phone"
                                                            value="<?php echo isset($patient['phone']) ? htmlspecialchars($patient['phone']) : ''; ?>" />
                                                    </div>
                                                    <div class="mb-3 col-md-6">
                                                        <label for="email" class="form-label">Email</label>
                                                        <input
                                                            class="form-control"
                                                            type="email"
                                                            id="email"
                                                            name="email"
                                                            value="<?php echo isset($patient['email']) ? htmlspecialchars($patient['email']) : ''; ?>" />
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="mb-3 col-md-6">
                                                        <label for="nationality" class="form-label">Nationality</label>
                                                        <input
                                                            class="form-control"
                                                            type="text"
                                                            id="nationality"
                                                            name="nationality"
                                                            value="<?php echo isset($patient['nationality']) ? htmlspecialchars($patient['nationality']) : ''; ?>" />
                                                    </div>
                                                    <div class="mb-3 col-md-6">
                                                        <label for="religion" class="form-label">Religion</label>
                                                        <input
                                                            class="form-control"
                                                            type="text"
                                                            id="religion"
                                                            name="religion"
                                                            value="<?php echo isset($patient['religion']) ? htmlspecialchars($patient['religion']) : ''; ?>" />
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="mb-3 col-md-6">
                                                        <label for="marital_status" class="form-label">Marital Status</label>
                                                        <select id="marital_status" name="marital_status" class="form-select">
                                                            <option value="">Select Marital Status</option>
                                                            <option value="Single" <?php echo (isset($patient['marital_status']) && $patient['marital_status'] == 'Single') ? 'selected' : ''; ?>>Single</option>
                                                            <option value="Married" <?php echo (isset($patient['marital_status']) && $patient['marital_status'] == 'Married') ? 'selected' : ''; ?>>Married</option>
                                                            <option value="Divorced" <?php echo (isset($patient['marital_status']) && $patient['marital_status'] == 'Divorced') ? 'selected' : ''; ?>>Divorced</option>
                                                            <option value="Widowed" <?php echo (isset($patient['marital_status']) && $patient['marital_status'] == 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3 col-md-6">
                                                        <label for="occupation" class="form-label">Occupation</label>
                                                        <input
                                                            class="form-control"
                                                            type="text"
                                                            id="occupation"
                                                            name="occupation"
                                                            value="<?php echo isset($patient['occupation']) ? htmlspecialchars($patient['occupation']) : ''; ?>" />
                                                    </div>
                                                </div>

                                                <hr class="my-4">
                                                <h6 class="mb-3">Emergency Contact Information</h6>

                                                <div class="row">
                                                    <div class="mb-3 col-md-6">
                                                        <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                                                        <input
                                                            class="form-control"
                                                            type="text"
                                                            id="emergency_contact_name"
                                                            name="emergency_contact_name"
                                                            value="<?php echo isset($patient['emergency_contact_name']) ? htmlspecialchars($patient['emergency_contact_name']) : ''; ?>" />
                                                    </div>
                                                    <div class="mb-3 col-md-6">
                                                        <label for="emergency_contact_relationship" class="form-label">Relationship</label>
                                                        <input
                                                            class="form-control"
                                                            type="text"
                                                            id="emergency_contact_relationship"
                                                            name="emergency_contact_relationship"
                                                            value="<?php echo isset($patient['emergency_contact_relationship']) ? htmlspecialchars($patient['emergency_contact_relationship']) : ''; ?>" />
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone</label>
                                                    <input
                                                        class="form-control"
                                                        type="text"
                                                        id="emergency_contact_phone"
                                                        name="emergency_contact_phone"
                                                        value="<?php echo isset($patient['emergency_contact_phone']) ? htmlspecialchars($patient['emergency_contact_phone']) : ''; ?>" />
                                                </div>

                                                <div class="mt-4">
                                                    <button type="submit" class="btn btn-primary me-2">
                                                        <i class="bx bx-check me-1"></i>
                                                        <?php echo $button_text; ?>
                                                    </button>
                                                    <a href="patients.php" class="btn btn-outline-secondary">
                                                        <i class="bx bx-x me-1"></i>
                                                        Cancel
                                                    </a>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- / Content -->

                    <!-- Footer -->
                    <?php include '../includes/footer.php'; ?>
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
    <!-- build:js assets/vendor/js/core.js -->
    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

    <script src="../assets/vendor/js/menu.js"></script>
    <!-- endbuild -->

    <!-- Vendors JS -->

    <!-- Main JS -->
    <script src="../assets/js/main.js"></script>

    <script>
        // Auto focus on first name field when adding new patient
        document.addEventListener('DOMContentLoaded', function() {
            const firstNameInput = document.getElementById('first_name');
            if (firstNameInput && '<?php echo $action; ?>' === 'add') {
                firstNameInput.focus();
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            
            if (!firstName || !lastName) {
                e.preventDefault();
                alert('Please fill in both first name and last name.');
                return false;
            }
        });

        // Calculate age from date of birth
        document.getElementById('dob').addEventListener('change', function() {
            const dobValue = this.value;
            if (dobValue) {
                const dob = new Date(dobValue);
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const monthDiff = today.getMonth() - dob.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                
                if (age >= 0 && age <= 150) {
                    document.getElementById('age').value = age;
                }
            }
        });
    </script>
</body>

</html>