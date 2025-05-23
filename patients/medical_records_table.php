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
$records_sql = "SELECT mr.*, p.first_name, p.last_name, p.age, p.phone, p.gender
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

// ฟังก์ชันสำหรับแสดงสถานะ BMI
function getBMIStatus($bmi) {
    if (empty($bmi)) return '';
    
    if ($bmi < 18.5) {
        return '<span class="badge bg-info">Underweight</span>';
    } elseif ($bmi < 25) {
        return '<span class="badge bg-success">Normal</span>';
    } elseif ($bmi < 30) {
        return '<span class="badge bg-warning">Overweight</span>';
    } else {
        return '<span class="badge bg-danger">Obese</span>';
    }
}

// ฟังก์ชันสำหรับแสดงสถานะความดันโลหิต
function getBPStatus($systolic, $diastolic) {
    if (empty($systolic) || empty($diastolic)) return '';
    
    if ($systolic >= 180 || $diastolic >= 120) {
        return '<span class="badge bg-danger">Crisis</span>';
    } elseif ($systolic >= 140 || $diastolic >= 90) {
        return '<span class="badge bg-danger">High</span>';
    } elseif ($systolic >= 130 || $diastolic >= 80) {
        return '<span class="badge bg-warning">Elevated</span>';
    } else {
        return '<span class="badge bg-success">Normal</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Medical Records - Table View | Sneat</title>
    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

    <!-- Helpers -->
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>

    <style>
        .patient-info-card {
            background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(105, 108, 255, 0.15);
        }
        
        .patient-avatar-small {
            width: 60px;
            height: 60px;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 20px;
        }

        .records-table {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .table-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px 25px;
            border-bottom: 1px solid #dee2e6;
        }

        .table th {
            background-color: #696cff;
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px 12px;
            font-size: 0.875rem;
        }

        .table td {
            padding: 12px;
            vertical-align: middle;
            border-color: #f1f1f1;
        }

        .table tbody tr:hover {
            background-color: #f8f9ff;
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }

        .diagnosis-text {
            font-weight: 600;
            color: #2d3436;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .vital-signs-mini {
            font-size: 0.75rem;
            color: #666;
        }

        .vital-signs-mini .vital-item {
            display: inline-block;
            margin-right: 8px;
            padding: 2px 6px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .action-buttons .btn {
            margin: 2px;
            padding: 4px 8px;
            font-size: 0.75rem;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .stats-cards {
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
        }

        .view-toggle {
            margin-bottom: 20px;
        }

        .view-toggle .btn {
            margin-right: 10px;
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .patient-info-card {
                padding: 15px;
            }
            
            .patient-avatar-small {
                width: 50px;
                height: 50px;
                font-size: 20px;
                margin-right: 15px;
            }
            
            .table-responsive {
                border-radius: 15px;
            }
        }

        /* Custom DataTables styling */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            margin: 10px 0;
        }

        .dataTables_wrapper .dataTables_filter input {
            border-radius: 8px;
            border: 1px solid #d1d5db;
            padding: 8px 12px;
        }

        .page-link {
            border-radius: 8px !important;
            margin: 0 2px;
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
                            <?php if ($patient_info): ?>
                                <span class="text-muted">/ <?php echo htmlspecialchars($patient_info['first_name'] . ' ' . $patient_info['last_name']); ?></span>
                            <?php endif; ?>
                        </h4>

                        <!-- Patient Information (if specific patient) -->
                        <?php if ($patient_info): ?>
                            <div class="patient-info-card">
                                <div class="d-flex align-items-center">
                                    <div class="patient-avatar-small">
                                        <?php echo strtoupper(substr($patient_info['first_name'], 0, 1) . substr($patient_info['last_name'], 0, 1)); ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-2"><?php echo htmlspecialchars($patient_info['first_name'] . ' ' . $patient_info['last_name']); ?></h5>
                                        <div class="d-flex flex-wrap gap-4">
                                            <span><i class="bx bx-id-card me-1"></i>ID: <?php echo str_pad($patient_info['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                            <span><i class="bx bx-calendar me-1"></i>Age: <?php echo $patient_info['age']; ?></span>
                                            <span><i class="bx bx-phone me-1"></i><?php echo htmlspecialchars($patient_info['phone']); ?></span>
                                            <span><i class="bx bx-user me-1"></i><?php echo $patient_info['gender'] == 'M' ? 'Male' : ($patient_info['gender'] == 'F' ? 'Female' : 'Other'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Statistics Cards -->
                        <?php if (!empty($medical_records)): ?>
                            <div class="row stats-cards">
                                <div class="col-md-3 col-sm-6 mb-4">
                                    <div class="stat-card">
                                        <div class="stat-number text-primary"><?php echo count($medical_records); ?></div>
                                        <div class="stat-label">Total Records</div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-4">
                                    <div class="stat-card">
                                        <div class="stat-number text-success">
                                            <?php 
                                            $latest = reset($medical_records);
                                            echo date('M Y', strtotime($latest['visit_date']));
                                            ?>
                                        </div>
                                        <div class="stat-label">Latest Visit</div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-4">
                                    <div class="stat-card">
                                        <div class="stat-number text-warning">
                                            <?php 
                                            $with_vitals = array_filter($medical_records, function($r) {
                                                return !empty($r['weight']) || !empty($r['height']) || !empty($r['pulse_rate']);
                                            });
                                            echo count($with_vitals);
                                            ?>
                                        </div>
                                        <div class="stat-label">With Vital Signs</div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-4">
                                    <div class="stat-card">
                                        <div class="stat-number text-info">
                                            <?php 
                                            $upcoming = array_filter($medical_records, function($r) {
                                                return !empty($r['next_appointment']) && strtotime($r['next_appointment']) > time();
                                            });
                                            echo count($upcoming);
                                            ?>
                                        </div>
                                        <div class="stat-label">Upcoming Appointments</div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- View Toggle and Action Buttons -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="view-toggle">
                                <a href="medical_records.php<?php echo $patient_id ? '?patient_id=' . $patient_id : ''; ?>" class="btn btn-outline-secondary">
                                    <i class="bx bx-list-ul me-1"></i> Card View
                                </a>
                                <button class="btn btn-primary active">
                                    <i class="bx bx-table me-1"></i> Table View
                                </button>
                            </div>
                            <div>
                                <a href="medical_records_action.php?action=add<?php echo $patient_id ? '&patient_id=' . $patient_id : ''; ?>" class="btn btn-success">
                                    <i class="bx bx-plus me-1"></i> Add New Record
                                </a>
                                <?php if ($patient_id): ?>
                                    <a href="patient_view.php?id=<?php echo $patient_id; ?>" class="btn btn-outline-secondary">
                                        <i class="bx bx-user me-1"></i> Patient Details
                                    </a>
                                    <a href="medical_records_table.php" class="btn btn-outline-secondary">
                                        <i class="bx bx-list-ul me-1"></i> All Records
                                    </a>
                                <?php endif; ?>
                                <a href="patients.php" class="btn btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Patients
                                </a>
                            </div>
                        </div>

                        <!-- Medical Records Table -->
                        <div class="records-table">
                            <?php if (empty($medical_records)): ?>
                                <div class="empty-state">
                                    <i class="bx bx-file-blank"></i>
                                    <h5>No Medical Records Found</h5>
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
                            <?php else: ?>
                                <div class="table-header">
                                    <h5 class="mb-0">
                                        <i class="bx bx-table me-2"></i>
                                        Medical Records Table
                                        <span class="badge bg-primary ms-2"><?php echo count($medical_records); ?> Records</span>
                                    </h5>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover" id="medicalRecordsTable">
                                        <thead>
                                            <tr>
                                                <th width="5%">#</th>
                                                <?php if (!$patient_id): ?><th width="15%">Patient</th><?php endif; ?>
                                                <th width="12%">Visit Date</th>
                                                <th width="20%">Diagnosis</th>
                                                <th width="15%">Vital Signs</th>
                                                <th width="10%">Doctor</th>
                                                <th width="12%">Next Visit</th>
                                                <th width="15%">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($medical_records as $index => $record): ?>
                                                <tr>
                                                    <td>
                                                        <strong class="text-primary">#<?php echo str_pad($record['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                                                    </td>
                                                    
                                                    <?php if (!$patient_id): ?>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="avatar avatar-sm me-2" style="background-color: #696cff; color: white;">
                                                                    <?php echo strtoupper(substr($record['first_name'], 0, 1) . substr($record['last_name'], 0, 1)); ?>
                                                                </div>
                                                                <div>
                                                                    <a href="patient_view.php?id=<?php echo $record['patient_id']; ?>" class="fw-bold text-decoration-none">
                                                                        <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?>
                                                                    </a>
                                                                    <br>
                                                                    <small class="text-muted">
                                                                        ID: <?php echo str_pad($record['patient_id'], 4, '0', STR_PAD_LEFT); ?> | 
                                                                        Age: <?php echo $record['age']; ?>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    <?php endif; ?>
                                                    
                                                    <td>
                                                        <strong><?php echo date('d/m/Y', strtotime($record['visit_date'])); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo date('H:i', strtotime($record['visit_date'])); ?></small>
                                                    </td>
                                                    
                                                    <td>
                                                        <div class="diagnosis-text" title="<?php echo htmlspecialchars($record['diagnosis']); ?>">
                                                            <?php echo htmlspecialchars($record['diagnosis']); ?>
                                                        </div>
                                                        <?php if (!empty($record['symptoms'])): ?>
                                                            <small class="text-muted d-block mt-1">
                                                                <i class="bx bx-pulse me-1"></i>
                                                                <?php echo htmlspecialchars(substr($record['symptoms'], 0, 50)) . (strlen($record['symptoms']) > 50 ? '...' : ''); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </td>
                                                    
                                                    <td>
                                                        <div class="vital-signs-mini">
                                                            <?php if ($record['weight'] && $record['height']): ?>
                                                                <div class="vital-item">
                                                                    <i class="bx bx-dumbbell"></i> <?php echo $record['weight']; ?>kg
                                                                </div>
                                                                <div class="vital-item">
                                                                    <i class="bx bx-ruler"></i> <?php echo $record['height']; ?>cm
                                                                </div>
                                                                <?php if ($record['bmi']): ?>
                                                                    <div class="vital-item">
                                                                        BMI: <?php echo number_format($record['bmi'], 1); ?>
                                                                        <?php echo getBMIStatus($record['bmi']); ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($record['pulse_rate']): ?>
                                                                <div class="vital-item">
                                                                    <i class="bx bx-heart text-danger"></i> <?php echo $record['pulse_rate']; ?> bpm
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($record['blood_pressure_systolic'] && $record['blood_pressure_diastolic']): ?>
                                                                <div class="vital-item">
                                                                    <i class="bx bx-droplet text-primary"></i> 
                                                                    <?php echo $record['blood_pressure_systolic']; ?>/<?php echo $record['blood_pressure_diastolic']; ?>
                                                                    <?php echo getBPStatus($record['blood_pressure_systolic'], $record['blood_pressure_diastolic']); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($record['temperature']): ?>
                                                                <div class="vital-item">
                                                                    <i class="bx bx-thermometer"></i> <?php echo $record['temperature']; ?>°C
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if (!$record['weight'] && !$record['height'] && !$record['pulse_rate'] && !$record['temperature']): ?>
                                                                <small class="text-muted">No vital signs recorded</small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    
                                                    <td>
                                                        <?php if (!empty($record['doctor_name'])): ?>
                                                            <i class="bx bx-user-circle me-1"></i>
                                                            <?php echo htmlspecialchars($record['doctor_name']); ?>
                                                        <?php else: ?>
                                                            <small class="text-muted">Not specified</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    
                                                    <td>
                                                        <?php if (!empty($record['next_appointment'])): ?>
                                                            <?php 
                                                            $next_date = strtotime($record['next_appointment']);
                                                            $is_upcoming = $next_date > time();
                                                            ?>
                                                            <div class="<?php echo $is_upcoming ? 'text-success' : 'text-muted'; ?>">
                                                                <i class="bx bx-calendar-check me-1"></i>
                                                                <?php echo date('d/m/Y', $next_date); ?>
                                                                <br>
                                                                <small><?php echo date('H:i', $next_date); ?></small>
                                                                <?php if ($is_upcoming): ?>
                                                                    <span class="badge bg-success ms-1">Upcoming</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <small class="text-muted">No appointment</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    
                                                    <td>
                                                        <div class="action-buttons">
                                                            <a href="medical_records_action.php?action=view&id=<?php echo $record['id']; ?>" 
                                                               class="btn btn-sm btn-outline-primary" title="View Details">
                                                                <i class="bx bx-show"></i>
                                                            </a>
                                                            <a href="medical_records_action.php?action=edit&id=<?php echo $record['id']; ?>" 
                                                               class="btn btn-sm btn-outline-secondary" title="Edit Record">
                                                                <i class="bx bx-edit-alt"></i>
                                                            </a>
                                                            <a href="medical_records_action.php?action=delete&id=<?php echo $record['id']; ?>" 
                                                               class="btn btn-sm btn-outline-danger" title="Delete Record"
                                                               onclick="return confirm('Are you sure you want to delete this medical record?');">
                                                                <i class="bx bx-trash"></i>
                                                            </a>
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

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#medicalRecordsTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[<?php echo $patient_id ? 1 : 2; ?>, 'desc']], // Sort by visit date descending
                columnDefs: [
                    { 
                        targets: -1, // Actions column
                        orderable: false,
                        searchable: false 
                    },
                    {
                        targets: [<?php echo $patient_id ? 3 : 4; ?>], // Vital signs column
                        orderable: false
                    }
                ],
                language: {
                    search: "Search records:",
                    lengthMenu: "Show _MENU_ records per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ medical records",
                    infoEmpty: "No records available",
                    infoFiltered: "(filtered from _MAX_ total records)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                drawCallback: function() {
                    // Re-initialize tooltips after table redraw
                    $('[title]').tooltip();
                }
            });

            // Initialize tooltips
            $('[title]').tooltip();

            // Add custom styling to DataTables elements
            $('.dataTables_length select').addClass('form-select form-select-sm');
            $('.dataTables_filter input').addClass('form-control form-control-sm');
        });
    </script>
</body>

</html>