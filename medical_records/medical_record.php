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
                                        <a href="medical_record_action.php?action=add<?php echo $patient_id ? '&patient_id=' . $patient_id : ''; ?>" class="btn btn-success">
                                            <i class="bx bx-plus me-1"></i> Add New Record
                                        </a>
                                        <?php if ($patient_id): ?>
                                            <a href="../patients/patient_view.php?id=<?php echo $patient_id; ?>" class="btn btn-outline-secondary ms-2">
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
                                            <a href="medical_record_action.php?action=add<?php echo $patient_id ? '&patient_id=' . $patient_id : ''; ?>" class="btn btn-primary">
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
                                                        <a href="medical_record_action.php?action=view&id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="bx bx-show me-1"></i>View
                                                        </a>
                                                        <a href="medical_record_action.php?action=edit&id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                            <i class="bx bx-edit-alt me-1"></i>Edit
                                                        </a>
                                                        <a href="medical_record_action.php?action=delete&id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this medical record?');">
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
    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    <script src="../assets/js/main.js"></script>
</body>

</html>