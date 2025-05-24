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

// รับค่าการค้นหา
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// สร้างคำสั่ง SQL สำหรับการค้นหา
$patients_sql = "SELECT * FROM patients";
$search_params = [];
$param_types = "";

if (!empty($search)) {
    $patients_sql .= " WHERE first_name LIKE ? OR last_name LIKE ? OR phone LIKE ? OR email LIKE ? OR address LIKE ? OR nationality LIKE ?";
    $search_term = "%$search%";
    $search_params = [$search_term, $search_term, $search_term, $search_term, $search_term, $search_term];
    $param_types = "ssssss";
}

$patients_sql .= " ORDER BY id DESC";

// เตรียมและรันคำสั่ง SQL
$stmt = $conn->prepare($patients_sql);
if (!empty($search_params)) {
    $stmt->bind_param($param_types, ...$search_params);
}
$stmt->execute();
$patients_result = $stmt->get_result();

$patients = [];
if ($patients_result && $patients_result->num_rows > 0) {
    while ($row = $patients_result->fetch_assoc()) {
        $patients[] = $row;
    }
}

// นับจำนวนผู้ป่วยทั้งหมดและผลการค้นหา
$total_sql = "SELECT COUNT(*) as total FROM patients";
$total_result = $conn->query($total_sql);
$total_patients = $total_result->fetch_assoc()['total'];

$search_count = count($patients);
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

    <title>Patients Management | Sneat</title>

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
        .search-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .search-results {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }

        .address-cell {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .address-cell:hover {
            white-space: normal;
            overflow: visible;
            position: relative;
            z-index: 10;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 5px;
            border-radius: 4px;
        }

        .highlight {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
        }

        /* Styles for user role indication */
        .user-role-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-left: 8px;
        }

        .user-role-admin {
            background-color: #e7f1ff;
            color: #0056b3;
        }

        .user-role-user {
            background-color: #d4edda;
            color: #155724;
        }

        /* Enhanced button styles for better UX */
        .btn-group .btn {
            transition: all 0.2s ease;
        }

        .btn-group .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
                        
                        <!-- Patients List -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="fw-bold py-3 mb-1">
                                        <span class="text-muted fw-light">Patients /</span> All Patients
                                        <span class="user-role-badge <?php echo $isAdmin ? 'user-role-admin' : 'user-role-user'; ?>">
                                            <?php echo $isAdmin ? 'Admin' : 'User'; ?> Access
                                        </span>
                                    </h4>
                                
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="text" id="searchInput" class="form-control" placeholder="Search records..." style="width: 250px;">
                                    <input type="date" id="dateFilter" class="form-control" style="width: 200px;">
                                    <a href="patients_action.php?action=add" class="btn btn-primary">
                                        <i class="bx bx-plus me-1"></i> Add New Patient
                                    </a>
                                </div>
                            </div>

                            <div class="card-body">                      

                                <!-- Search results info -->
                                <?php if (!empty($search)): ?>
                                    <div class="search-results">
                                        <i class="bx bx-info-circle me-1"></i>
                                        Found <?php echo $search_count; ?> result(s) for "<?php echo htmlspecialchars($search); ?>" 
                                        (Total patients: <?php echo $total_patients; ?>)
                                    </div>
                                <?php else: ?>
                                    <div class="search-results">
                                        <i class="bx bx-group me-1"></i>
                                        Total patients: <?php echo $total_patients; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Patient ID</th>
                                                <th>Patient Name</th>
                                                <th>Age</th>
                                                <th>Gender</th>
                                                <th>Contact</th>
                                                <th>Address</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($patients)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">
                                                        <?php if (!empty($search)): ?>
                                                            <div class="py-4">
                                                                <i class="bx bx-search-alt-2 fs-1 text-muted"></i>
                                                                <p class="mt-2 mb-0">No search results found for "<?php echo htmlspecialchars($search); ?>"</p>
                                                                <small class="text-muted">Try changing your search terms or <a href="patients.php">view all patients</a></small>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="py-4">
                                                                <i class="bx bx-user-plus fs-1 text-muted"></i>
                                                                <p class="mt-2 mb-0">No patient records found</p>
                                                                <small class="text-muted"><a href="patients_action.php?action=add">Add the first patient</a></small>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($patients as $patient): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo str_pad($patient['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $full_name = htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']);
                                                            if (!empty($search)) {
                                                                $highlighted_name = preg_replace('/(' . preg_quote($search, '/') . ')/i', '<span class="highlight">$1</span>', $full_name);
                                                                echo $highlighted_name;
                                                            } else {
                                                                echo $full_name;
                                                            }
                                                            ?>
                                                        </td>
                                                        <td><?php echo $patient['age']; ?></td>
                                                        <td>
                                                            <?php
                                                            $gender_display = '';
                                                            $gender_class = '';
                                                            if ($patient['gender'] == 'M') {
                                                                $gender_display = 'Male';
                                                                $gender_class = 'badge bg-primary';
                                                            } elseif ($patient['gender'] == 'F') {
                                                                $gender_display = 'Female';
                                                                $gender_class = 'badge bg-info';
                                                            } elseif ($patient['gender'] == 'O') {
                                                                $gender_display = 'Other';
                                                                $gender_class = 'badge bg-secondary';
                                                            } else {
                                                                $gender_display = $patient['gender'];
                                                                $gender_class = 'badge bg-light text-dark';
                                                            }
                                                            echo '<span class="' . $gender_class . '">' . htmlspecialchars($gender_display) . '</span>';
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $phone = htmlspecialchars($patient['phone']);
                                                            if (!empty($search)) {
                                                                $highlighted_phone = preg_replace('/(' . preg_quote($search, '/') . ')/i', '<span class="highlight">$1</span>', $phone);
                                                                echo $highlighted_phone;
                                                            } else {
                                                                echo $phone;
                                                            }
                                                            ?>
                                                            <?php if (!empty($patient['email'])): ?>
                                                                <br><small class="text-muted"><?php echo htmlspecialchars($patient['email']); ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="address-cell" title="<?php echo htmlspecialchars($patient['address']); ?>">
                                                                <?php
                                                                $address = htmlspecialchars($patient['address']);
                                                                if (!empty($search)) {
                                                                    $highlighted_address = preg_replace('/(' . preg_quote($search, '/') . ')/i', '<span class="highlight">$1</span>', $address);
                                                                    echo $highlighted_address;
                                                                } else {
                                                                    echo $address;
                                                                }
                                                                ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <!-- View button - available to all users -->
                                                                <a href="patient_view.php?id=<?php echo $patient['id']; ?>" 
                                                                   class="btn btn-sm btn-success" 
                                                                   title="View Patient Details">
                                                                    <i class="bx bx-show"></i>
                                                                </a>
                                                                
                                                                <!-- Edit button - available to all users now -->
                                                                <a href="patients_action.php?action=edit&id=<?php echo $patient['id']; ?>" 
                                                                   class="btn btn-sm btn-primary" 
                                                                   title="Edit Patient Information">
                                                                    <i class="bx bx-edit-alt"></i>
                                                                </a>
                                                                
                                                                <!-- Medical Records button - available to all users -->
                                                                <a href="../medical_records/medical_records.php?patient_id=<?php echo $patient['id']; ?>" 
                                                                   class="btn btn-sm btn-info" 
                                                                   title="View Medical Records">
                                                                    <i class="bx bx-file"></i>
                                                                </a>
                                                                
                                                                <!-- Delete button - only for admins -->
                                                                <?php if ($isAdmin): ?>
                                                                    <a href="patients_action.php?action=delete&id=<?php echo $patient['id']; ?>" 
                                                                       class="btn btn-sm btn-danger" 
                                                                       title="Delete Patient (Admin Only)" 
                                                                       onclick="return confirm('Are you sure you want to delete this patient? This action cannot be undone and will also delete all associated medical records.');">
                                                                        <i class="bx bx-trash"></i>
                                                                    </a>
                                                                <?php else: ?>
                                                                    <!-- Disabled delete button for regular users -->
                                                                    <button class="btn btn-sm btn-outline-secondary" 
                                                                            title="Delete requires admin privileges" 
                                                                            disabled>
                                                                        <i class="bx bx-trash"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
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

    <script>
        // Auto focus on search input when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput && !searchInput.value) {
                searchInput.focus();
            }

            // Add enhanced tooltips for action buttons
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Add keyboard shortcut for search (Ctrl+F or Cmd+F)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                const searchInput = document.querySelector('input[name="search"]');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }
        });

        // Enhanced search functionality
        function performSearch() {
            const searchValue = document.querySelector('input[name="search"]').value.trim();
            if (searchValue) {
                window.location.href = `patients.php?search=${encodeURIComponent(searchValue)}`;
            } else {
                window.location.href = 'patients.php';
            }
        }

        // Add Enter key support for search
        document.querySelector('input[name="search"]').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });
    </script>
</body>

</html>