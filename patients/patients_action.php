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

// การลบผู้ป่วย
if ($action == 'delete' && $patient_id > 0) {
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

// การบันทึกข้อมูลผู้ป่วย (เพิ่มหรือแก้ไข)
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

        // สร้างคำสั่ง SQL ตามการกระทำ
        if ($action == 'edit' && $patient_id > 0) {
            // อัปเดตข้อมูลผู้ป่วย
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
            // เพิ่มผู้ป่วยใหม่
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
            $success = alert(($action == 'edit' ? "อัปเดตข้อมูลผู้ป่วยเรียบร้อยแล้ว" : "เพิ่มผู้ป่วยเรียบร้อยแล้ว"), "success");

            if ($action != 'edit') {
                // ล้างข้อมูลในฟอร์มหลังจากเพิ่มผู้ป่วยสำเร็จ
                $patient = array_fill_keys($table_columns, '');
                if (isset($patient['id'])) {
                    unset($patient['id']);
                }
            }
        } else {
            $error = alert("เกิดข้อผิดพลาด: " . $stmt->error, "danger");
        }
    }
}

// หัวข้อและชื่อปุ่มตามการกระทำ
$page_title = ($action == 'edit') ? 'Edit Patient' : 'Add New Patient';
$button_text = ($action == 'edit') ? 'Update Patient' : 'Add Patient';
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

    <title><?php echo $page_title; ?> | Sneat</title>

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

                    <!-- Patients -->
                    <li class="menu-item active open">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-user-plus"></i>
                            <div data-i18n="Patients">Patients</div>
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item active">
                                <a href="patients.php" class="menu-link">
                                    <div data-i18n="All Patients">All Patients</div>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="medical_records.php" class="menu-link">
                                    <div data-i18n="Medical Records">Medical Records</div>
                                </a>
                            </li>
                        </ul>
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
                            <span class="text-muted fw-light">Patients /</span> <?php echo $page_title; ?>
                        </h4>

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
                                                    <label for="first_name" class="form-label">First Name</label>
                                                    <input
                                                        class="form-control"
                                                        type="text"
                                                        id="first_name"
                                                        name="first_name"
                                                        value="<?php echo isset($patient['first_name']) ? htmlspecialchars($patient['first_name']) : ''; ?>"
                                                        required />
                                                </div>
                                                <div class="mb-3 col-md-6">
                                                    <label for="last_name" class="form-label">Last Name</label>
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

                                            <div class="mt-2">
                                                <button type="submit" class="btn btn-primary me-2"><?php echo $button_text; ?></button>
                                                <a href="patients.php" class="btn btn-outline-secondary">Cancel</a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
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
</body>

</html>