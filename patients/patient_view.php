<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/patient_functions.php';

requireLogin();

// Get user info
$user = getUserInfo($_SESSION['user_id'], $conn);
$isAdmin = ($user['role'] == 'admin');

// Get patient ID and data
$patient_id = (int)($_GET['id'] ?? 0);
if ($patient_id <= 0) redirect('patients.php');

$patient = getPatientById($conn, $patient_id);
if (!$patient) redirect('patients.php');

// Format display data
$gender_display = formatGender($patient['gender']);
$dob_formatted = formatDate($patient['dob']);
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Patient Details | Healthcare System</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    
    <!-- PDF Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <style>
        .patient-avatar {
            width: 100px; height: 100px; background-color: #696cff; color: white;
            font-size: 40px; display: flex; align-items: center; justify-content: center;
            border-radius: 50%; margin-bottom: 15px;
        }
        .info-row { margin-bottom: 15px; }
        .info-label { font-weight: 600; margin-bottom: 5px; }
        .info-value { color: #697a8d; }
        
        @media print {
            .layout-menu, .layout-navbar, .content-footer, .btn-actions, .btn-back { display: none !important; }
            .layout-wrapper, .layout-container, .layout-page, .content-wrapper { 
                width: 100% !important; padding: 0 !important; margin: 0 !important; 
            }
            .card { box-shadow: none !important; border: 1px solid #ddd !important; }
        }
    </style>
    
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Sidebar -->
            <?php include '../includes/sidebar.php'; ?>
            
            <div class="layout-page">
                <!-- Navbar -->
                <?php 
                if (file_exists('../includes/navbar.php')) {
                    include '../includes/navbar.php'; 
                } 
                ?>
                
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <!-- Header & Actions -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="fw-bold mb-0">
                                <span class="text-muted fw-light">Patients /</span> Patient Details
                            </h4>
                            <div>
                                <button onclick="window.print()" class="btn btn-secondary me-2">
                                    <i class="bx bx-printer me-1"></i> Print
                                </button>
                                <button onclick="exportPDF()" class="btn btn-danger me-2">
                                    <i class="bx bx-file-pdf me-1"></i> Export PDF
                                </button>
                                <a href="patients.php" class="btn btn-outline-secondary btn-back">
                                    <i class="bx bx-arrow-back me-1"></i> Back
                                </a>
                            </div>
                        </div>

                        <!-- Patient Details Card -->
                        <div class="card" id="patient-details-card">
                            <h5 class="card-header">Patient Information</h5>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Avatar & Basic Info -->
                                    <div class="col-md-3 mb-4 text-center">
                                        <div class="patient-avatar mx-auto">
                                            <?php echo strtoupper(substr($patient['first_name'], 0, 1) . substr($patient['last_name'], 0, 1)); ?>
                                        </div>
                                        <h5><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h5>
                                        <p class="text-muted">Patient ID: <?php echo str_pad($patient['id'], 4, '0', STR_PAD_LEFT); ?></p>
                                        <div class="mt-4 btn-actions">
                                            <a href="patients_action.php?action=edit&id=<?php echo $patient['id']; ?>" class="btn btn-primary me-2">
                                                <i class="bx bx-edit-alt me-1"></i> Edit
                                            </a>
                                            <a href="../medical_records/medical_record_action.php?patient_id=<?php echo $patient['id']; ?>" class="btn btn-info">
                                                <i class="bx bx-file me-1"></i> Medical Records
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <!-- Patient Details -->
                                    <div class="col-md-9">
                                        <!-- Personal Information -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="info-row">
                                                    <div class="info-label">First Name</div>
                                                    <div class="info-value"><?php echo htmlspecialchars($patient['first_name']); ?></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-row">
                                                    <div class="info-label">Last Name</div>
                                                    <div class="info-value"><?php echo htmlspecialchars($patient['last_name']); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="info-row">
                                                    <div class="info-label">Age</div>
                                                    <div class="info-value"><?php echo $patient['age']; ?></div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-row">
                                                    <div class="info-label">Gender</div>
                                                    <div class="info-value"><?php echo htmlspecialchars($gender_display); ?></div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-row">
                                                    <div class="info-label">Date of Birth</div>
                                                    <div class="info-value"><?php echo htmlspecialchars($dob_formatted); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Contact Information -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="info-row">
                                                    <div class="info-label">Phone</div>
                                                    <div class="info-value"><?php echo htmlspecialchars($patient['phone']); ?></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-row">
                                                    <div class="info-label">Email</div>
                                                    <div class="info-value"><?php echo htmlspecialchars($patient['email']); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="info-row">
                                            <div class="info-label">Address</div>
                                            <div class="info-value"><?php echo htmlspecialchars($patient['address']); ?></div>
                                        </div>
                                        
                                        <!-- Additional Information -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="info-row">
                                                    <div class="info-label">Nationality</div>
                                                    <div class="info-value"><?php echo htmlspecialchars($patient['nationality']); ?></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-row">
                                                    <div class="info-label">Religion</div>
                                                    <div class="info-value"><?php echo htmlspecialchars($patient['religion']); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="info-row">
                                                    <div class="info-label">Marital Status</div>
                                                    <div class="info-value"><?php echo htmlspecialchars($patient['marital_status']); ?></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-row">
                                                    <div class="info-label">Occupation</div>
                                                    <div class="info-value"><?php echo htmlspecialchars($patient['occupation']); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Emergency Contact -->
                                        <hr class="my-4">
                                        <h6 class="mb-3">Emergency Contact Information</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="info-row">
                                                    <div class="info-label">Contact Name</div>
                                                    <div class="info-value"><?php echo htmlspecialchars($patient['emergency_contact_name']); ?></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-row">
                                                    <div class="info-label">Relationship</div>
                                                    <div class="info-value"><?php echo htmlspecialchars($patient['emergency_contact_relationship']); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="info-row">
                                            <div class="info-label">Emergency Contact Phone</div>
                                            <div class="info-value"><?php echo htmlspecialchars($patient['emergency_contact_phone']); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php include '../includes/footer.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JS -->
    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    <script src="../assets/js/main.js"></script>
    
    <script>
        function exportPDF() {
            const element = document.getElementById('patient-details-card');
            const opt = {
                margin: 10,
                filename: 'patient_<?php echo str_pad($patient['id'], 4, '0', STR_PAD_LEFT); ?>_details.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
        
        // Add getUserInfo helper function to functions.php if not exists
        if (typeof getUserInfo === 'undefined') {
            console.log('getUserInfo function should be added to functions.php');
        }
    </script>
</body>
</html>ห