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

// สร้าง query สำหรับดึงข้อมูล medical records
$where_clause = "";
$params = [];
$types = "";

if ($patient_id > 0) {
    $where_clause = "WHERE mr.patient_id = ?";
    $params[] = $patient_id;
    $types = "i";
}

// ดึงข้อมูล medical records พร้อมข้อมูลผู้ป่วย
$records_sql = "SELECT mr.*, p.first_name, p.last_name, p.age, p.phone 
                FROM medical_records mr 
                JOIN patients p ON mr.patient_id = p.id 
                $where_clause 
                ORDER BY mr.visit_date DESC";

$stmt = $conn->prepare($records_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$records_result = $stmt->get_result();
$medical_records = [];

if ($records_result && $records_result->num_rows > 0) {
    while ($row = $records_result->fetch_assoc()) {
        $medical_records[] = $row;
    }
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

    <title>Medical Records | Sneat</title>

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
        .record-card {
            border-left: 4px solid #696cff;
            margin-bottom: 20px;
        }
        .record-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        .record-body {
            padding: 15px;
        }
        .record-meta {
            font-size: 0.875rem;
            color: #6c757d;
        }
        .diagnosis-badge {
            background-color: #696cff;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        .patient-info-card {
            background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .patient-avatar-small {
            width: 50px;
            height: 50px;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 15px;
        }
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
                            <span class="text-muted fw-light">Patients /</span> Medical Records
                        </h4>

                        <!-- Patient Information (if specific patient) -->
                        <?php if ($patient_info): ?>
                            <div class="patient-info-card">
                                <div class="d-flex align-items-center">
                                    <div class="patient-avatar-small">
                                        <?php echo strtoupper(substr($patient_info['first_name'], 0, 1) . substr($patient_info['last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($patient_info['first_name'] . ' ' . $patient_info['last_name']); ?></h5>
                                        <div class="d-flex gap-3">
                                            <span><i class="bx bx-id-card me-1"></i>ID: <?php echo str_pad($patient_info['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                            <span><i class="bx bx-calendar me-1"></i>Age: <?php echo $patient_info['age']; ?></span>
                                            <span><i class="bx bx-phone me-1"></i><?php echo htmlspecialchars($patient_info['phone']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <button class="btn btn-primary active me-2">
                                            <i class="bx bx-list-ul me-1"></i> Card View
                                        </button>
                                        <a href="medical_records_table.php<?php echo $patient_id ? '?patient_id=' . $patient_id : ''; ?>" class="btn btn-outline-secondary me-3">
                                            <i class="bx bx-table me-1"></i> Table View
                                        </a>
                                        
                                        <a href="medical_records_action.php?action=add<?php echo $patient_id ? '&patient_id=' . $patient_id : ''; ?>" class="btn btn-success">
                                            <i class="bx bx-plus me-1"></i> Add New Record
                                        </a>
                                        <?php if ($patient_id): ?>
                                            <a href="patient_view.php?id=<?php echo $patient_id; ?>" class="btn btn-outline-secondary ms-2">
                                                <i class="bx bx-user me-1"></i> View Patient Details
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($patient_id): ?>
                                            <a href="medical_records.php" class="btn btn-outline-secondary">
                                                <i class="bx bx-list-ul me-1"></i> All Records
                                            </a>
                                        <?php endif; ?>
                                        <a href="patients.php" class="btn btn-outline-secondary">
                                            <i class="bx bx-arrow-back me-1"></i> Back to Patients
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Medical Records -->
                        <div class="row">
                            <div class="col-md-12">
                                <?php if (empty($medical_records)): ?>
                                    <div class="card">
                                        <div class="card-body text-center py-5">
                                            <i class="bx bx-file-blank display-1 text-muted"></i>
                                            <h5 class="mt-3">No Medical Records Found</h5>
                                            <p class="text-muted">
                                                <?php if ($patient_id): ?>
                                                    This patient doesn't have any medical records yet.
                                                <?php else: ?>
                                                    No medical records available in the system.
                                                <?php endif; ?>
                                            </p>
                                            <a href="medical_records_action.php?action=add<?php echo $patient_id ? '&patient_id=' . $patient_id : ''; ?>" class="btn btn-primary">
                                                <i class="bx bx-plus me-1"></i> Add First Record
                                            </a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($medical_records as $record): ?>
                                        <div class="card record-card">
                                            <div class="record-header">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1">
                                                            <?php if (!$patient_id): ?>
                                                                <a href="patient_view.php?id=<?php echo $record['patient_id']; ?>" class="text-decoration-none">
                                                                    <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?>
                                                                </a>
                                                                <span class="text-muted">- ID: <?php echo str_pad($record['patient_id'], 4, '0', STR_PAD_LEFT); ?></span>
                                                            <?php endif; ?>
                                                        </h6>
                                                        <div class="diagnosis-badge">
                                                            <?php echo htmlspecialchars($record['diagnosis']); ?>
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="record-meta">
                                                            <i class="bx bx-calendar me-1"></i>
                                                            <?php echo date('d/m/Y H:i', strtotime($record['visit_date'])); ?>
                                                        </div>
                                                        <?php if (!empty($record['doctor_name'])): ?>
                                                            <div class="record-meta mt-1">
                                                                <i class="bx bx-user-circle me-1"></i>
                                                                <?php echo htmlspecialchars($record['doctor_name']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="record-body">
                                                <div class="row">
                                                    <?php if (!empty($record['symptoms'])): ?>
                                                        <div class="col-md-6 mb-3">
                                                            <h6 class="text-primary mb-2"><i class="bx bx-pulse me-1"></i>Symptoms</h6>
                                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($record['symptoms'])); ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($record['treatment'])): ?>
                                                        <div class="col-md-6 mb-3">
                                                            <h6 class="text-success mb-2"><i class="bx bx-plus-medical me-1"></i>Treatment</h6>
                                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($record['treatment'])); ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <?php if (!empty($record['prescription'])): ?>
                                                    <div class="mb-3">
                                                        <h6 class="text-warning mb-2"><i class="bx bx-capsule me-1"></i>Prescription</h6>
                                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($record['prescription'])); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($record['notes'])): ?>
                                                    <div class="mb-3">
                                                        <h6 class="text-info mb-2"><i class="bx bx-note me-1"></i>Notes</h6>
                                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($record['notes'])); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($record['next_appointment'])): ?>
                                                    <div class="alert alert-info mb-3">
                                                        <i class="bx bx-calendar-check me-2"></i>
                                                        <strong>Next Appointment:</strong> 
                                                        <?php echo date('d/m/Y H:i', strtotime($record['next_appointment'])); ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="record-meta">
                                                        <i class="bx bx-time me-1"></i>
                                                        Record created: <?php echo date('d/m/Y H:i', strtotime($record['created_at'])); ?>
                                                        <?php if ($record['updated_at'] != $record['created_at']): ?>
                                                            <br><i class="bx bx-edit me-1"></i>
                                                            Last updated: <?php echo date('d/m/Y H:i', strtotime($record['updated_at'])); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <a href="medical_records_action.php?action=view&id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="bx bx-show me-1"></i>View
                                                        </a>
                                                        <a href="medical_records_action.php?action=edit&id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                            <i class="bx bx-edit-alt me-1"></i>Edit
                                                        </a>
                                                        <a href="medical_records_action.php?action=delete&id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this medical record?');">
                                                            <i class="bx bx-trash me-1"></i>Delete
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
</body>

</html>