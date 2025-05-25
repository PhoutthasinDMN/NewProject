<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/patient_functions.php';

requireLogin();

// Get user info
$user_id = $_SESSION['user_id'];
$user = getUserInfo($user_id, $conn);
$isAdmin = ($user['role'] == 'admin');

// Get search parameter and pagination
$search = trim($_GET['search'] ?? '');
$page = (int)($_GET['page'] ?? 1);
$limit = 10; // จำนวนผู้ป่วยต่อหน้า
$offset = ($page - 1) * $limit;

// Build search query for counting total records
$count_sql = "SELECT COUNT(*) as total FROM patients";
$params = [];
$types = "";

if (!empty($search)) {
    $count_sql .= " WHERE id LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR phone LIKE ? OR email LIKE ? OR address LIKE ? OR nationality LIKE ?";
    $search_term = "%$search%";
    $params = array_fill(0, 7, $search_term);
    $types = str_repeat('s', 7);
}

// Get total count
$count_result = executeQuery($conn, $count_sql, $params, $types);
$total_patients = $count_result['stmt']->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_patients / $limit);

// Build main query with pagination
$patients_sql = "SELECT * FROM patients";
$main_params = $params;
$main_types = $types;

if (!empty($search)) {
    $patients_sql .= " WHERE id LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR phone LIKE ? OR email LIKE ? OR address LIKE ? OR nationality LIKE ?";
}

$patients_sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
$main_params[] = $limit;
$main_params[] = $offset;
$main_types .= 'ii';

// Execute main query
$result = executeQuery($conn, $patients_sql, $main_params, $main_types);
$patients_result = $result['stmt']->get_result();

$patients = [];
while ($row = $patients_result->fetch_assoc()) {
    $patients[] = $row;
}

$search_count = count($patients);

// Get total patients for statistics
$all_patients_result = $conn->query("SELECT COUNT(*) as total FROM patients");
$all_patients_count = $all_patients_result->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Patients Management</title>
    
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
    
    <style>
        .search-results { font-size: 14px; color: #666; margin-bottom: 15px; }
        .address-cell { max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .address-cell:hover { 
            white-space: normal; overflow: visible; position: relative; z-index: 10;
            background: white; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); padding: 5px; border-radius: 4px;
        }
        .highlight { background-color: #fff3cd; padding: 2px 4px; border-radius: 3px; }
        .user-role-badge { 
            display: inline-block; padding: 4px 8px; border-radius: 4px; 
            font-size: 0.75rem; font-weight: 500; margin-left: 8px;
        }
        .user-role-admin { background-color: #e7f1ff; color: #0056b3; }
        .user-role-user { background-color: #d4edda; color: #155724; }
        .btn-group .btn { transition: all 0.2s ease; }
        .btn-group .btn:hover { transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        
        /* Pagination Styles */
        .pagination-container {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-top: 20px;
            padding: 15px 0;
            border-top: 1px solid #e9ecef;
        }
        
        .pagination-info {
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .pagination {
            margin: 0;
        }
        
        .page-link {
            border: 1px solid #dee2e6;
            color: #6c757d;
            padding: 0.375rem 0.75rem;
            transition: all 0.2s ease;
        }
        
        .page-link:hover {
            background-color: #e9ecef;
            border-color: #dee2e6;
            color: #495057;
        }
        
        .page-item.active .page-link {
            background-color: #696cff;
            border-color: #696cff;
            color: white;
        }
        
        .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            background-color: #fff;
            border-color: #dee2e6;
        }
        
        /* Search Form Enhancement */
        .search-form {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .search-form .form-control {
            border-radius: 6px;
        }
        
        .search-form .btn {
            border-radius: 6px;
        }
    </style>
    
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php include '../includes/sidebar.php'; ?>
            
            <div class="layout-page">
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <!-- Patients List -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">
                                        <i class="bx bx-group me-2 text-primary"></i>All Patients (<?php echo $total_patients; ?>)
                                        <span class="user-role-badge <?php echo $isAdmin ? 'user-role-admin' : 'user-role-user'; ?>">
                                            <?php echo $isAdmin ? 'Admin' : 'User'; ?> Access
                                        </span>
                                    </h5>
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="text" id="searchInput" class="form-control" 
                                           placeholder="Search patients..." style="width: 250px;"
                                           value=""
                                           oninput="performLiveSearch()">
                                    <a href="patients_action.php?action=add" class="btn btn-primary">
                                        <i class="bx bx-plus me-1"></i> Add New Patient
                                    </a>
                                </div>
                            </div>

                            <div class="card-body">
                                <!-- Search results info -->
                                <div class="search-results">
                                    <i class="bx bx-group me-1"></i>
                                    Total patients: <?php echo $all_patients_count; ?> 
                                    (Showing <?php echo $search_count; ?> of <?php echo $total_patients; ?> on page <?php echo $page; ?>)
                                    <span id="liveSearchInfo" style="display: none;"></span>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="patientsTable">
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
                                                        <div class="py-4">
                                                            <i class="bx <?php echo !empty($search) ? 'bx-search-alt-2' : 'bx-user-plus'; ?> fs-1 text-muted"></i>
                                                            <p class="mt-2 mb-0">
                                                                <?php if (!empty($search)): ?>
                                                                    No search results found for "<?php echo htmlspecialchars($search); ?>"
                                                                <?php else: ?>
                                                                    No patient records found
                                                                <?php endif; ?>
                                                            </p>
                                                            <small class="text-muted">
                                                                <?php if (!empty($search)): ?>
                                                                    Try changing your search terms or <a href="patients.php">view all patients</a>
                                                                <?php else: ?>
                                                                    <a href="patients_action.php?action=add">Add the first patient</a>
                                                                <?php endif; ?>
                                                            </small>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($patients as $patient): ?>
                                                    <tr>
                                                        <td><?php echo highlightSearch(str_pad($patient['id'], 4, '0', STR_PAD_LEFT), $search); ?></td>
                                                        <td><?php echo highlightSearch($patient['first_name'] . ' ' . $patient['last_name'], $search); ?></td>
                                                        <td><?php echo $patient['age']; ?></td>
                                                        <td>
                                                            <span class="<?php echo getGenderBadgeClass($patient['gender']); ?>">
                                                                <?php echo htmlspecialchars(formatGender($patient['gender'])); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php echo highlightSearch($patient['phone'], $search); ?>
                                                            <?php if (!empty($patient['email'])): ?>
                                                                <br><small class="text-muted"><?php echo highlightSearch($patient['email'], $search); ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="address-cell" title="<?php echo htmlspecialchars($patient['address']); ?>">
                                                                <?php echo highlightSearch($patient['address'], $search); ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <a href="patient_view.php?id=<?php echo $patient['id']; ?>" 
                                                                   class="btn btn-sm btn-success" title="View Patient Details">
                                                                    <i class="bx bx-show"></i>
                                                                </a>
                                                                
                                                                <a href="patients_action.php?action=edit&id=<?php echo $patient['id']; ?>" 
                                                                   class="btn btn-sm btn-primary" title="Edit Patient Information">
                                                                    <i class="bx bx-edit-alt"></i>
                                                                </a>
                                                                
                                                                <a href="../medical_records/medical_record_action.php?action=add&patient_id=<?php echo $patient['id']; ?>&auto_fill=1" 
                                                                   class="btn btn-sm btn-info" title="Add Medical Record for this Patient">
                                                                    <i class="bx bx-file"></i>
                                                                </a>
                                                                
                                                                <?php if ($isAdmin): ?>
                                                                    <a href="patients_action.php?action=delete&id=<?php echo $patient['id']; ?>" 
                                                                       class="btn btn-sm btn-danger" title="Delete Patient (Admin Only)" 
                                                                       onclick="return confirm('Are you sure you want to delete this patient? This action cannot be undone and will also delete all associated medical records.');">
                                                                        <i class="bx bx-trash"></i>
                                                                    </a>
                                                                <?php else: ?>
                                                                    <button class="btn btn-sm btn-outline-secondary" 
                                                                            title="Delete requires admin privileges" disabled>
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

                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                    <div class="pagination-container">
                                        <div class="pagination-info">
                                            Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $total_patients); ?> 
                                            of <?php echo $total_patients; ?> results
                                        </div>
                                        
                                        <nav aria-label="Page navigation">
                                            <ul class="pagination">
                                                <!-- Previous Button -->
                                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                                    <a class="page-link" 
                                                       href="<?php echo $page > 1 ? 'patients.php?page=' . ($page - 1) . (!empty($search) ? '&search=' . urlencode($search) : '') : '#'; ?>" 
                                                       aria-label="Previous">
                                                        <span aria-hidden="true">&laquo;</span>
                                                    </a>
                                                </li>

                                                <?php
                                                // Calculate pagination range
                                                $start_page = max(1, $page - 2);
                                                $end_page = min($total_pages, $page + 2);
                                                
                                                // Show first page if not in range
                                                if ($start_page > 1) {
                                                    echo '<li class="page-item"><a class="page-link" href="patients.php?page=1' . (!empty($search) ? '&search=' . urlencode($search) : '') . '">1</a></li>';
                                                    if ($start_page > 2) {
                                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                    }
                                                }
                                                
                                                // Show page numbers
                                                for ($i = $start_page; $i <= $end_page; $i++):
                                                ?>
                                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                        <a class="page-link" 
                                                           href="patients.php?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                                            <?php echo $i; ?>
                                                        </a>
                                                    </li>
                                                <?php 
                                                endfor;
                                                
                                                // Show last page if not in range
                                                if ($end_page < $total_pages) {
                                                    if ($end_page < $total_pages - 1) {
                                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                    }
                                                    echo '<li class="page-item"><a class="page-link" href="patients.php?page=' . $total_pages . (!empty($search) ? '&search=' . urlencode($search) : '') . '">' . $total_pages . '</a></li>';
                                                }
                                                ?>

                                                <!-- Next Button -->
                                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                                    <a class="page-link" 
                                                       href="<?php echo $page < $total_pages ? 'patients.php?page=' . ($page + 1) . (!empty($search) ? '&search=' . urlencode($search) : '') : '#'; ?>" 
                                                       aria-label="Next">
                                                        <span aria-hidden="true">&raquo;</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </nav>
                                    </div>
                                <?php endif; ?>
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
        // Live Search Functionality
        function performLiveSearch() {
            const searchInput = document.getElementById('searchInput');
            const table = document.getElementById('patientsTable');
            const liveSearchInfo = document.getElementById('liveSearchInfo');
            const originalSearchResults = document.querySelector('.search-results');
            
            if (!searchInput || !table) return;
            
            const searchTerm = searchInput.value.toLowerCase().trim();
            const rows = table.querySelectorAll('tbody tr');
            let visibleCount = 0;
            let totalDataRows = 0;
            
            rows.forEach(row => {
                // Skip the "no results" row that spans all columns
                if (row.querySelector('td[colspan]')) {
                    return;
                }
                
                totalDataRows++;
                
                // Get text content from each cell
                const cells = row.querySelectorAll('td');
                if (cells.length >= 6) {
                    const patientId = cells[0].textContent.toLowerCase();
                    const patientName = cells[1].textContent.toLowerCase();
                    const age = cells[2].textContent.toLowerCase();
                    const gender = cells[3].textContent.toLowerCase();
                    const contact = cells[4].textContent.toLowerCase();
                    const address = cells[5].textContent.toLowerCase();
                    
                    const matchesSearch = searchTerm === '' ||
                                        patientId.includes(searchTerm) || 
                                        patientName.includes(searchTerm) || 
                                        age.includes(searchTerm) ||
                                        gender.includes(searchTerm) ||
                                        contact.includes(searchTerm) ||
                                        address.includes(searchTerm);
                    
                    if (matchesSearch) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
            
            // Update search info
            if (searchTerm === '') {
                // Show original info
                liveSearchInfo.style.display = 'none';
                originalSearchResults.querySelector('i').className = 'bx bx-group me-1';
            } else {
                // Show live search info
                liveSearchInfo.style.display = 'inline';
                liveSearchInfo.innerHTML = ` | <i class="bx bx-search me-1"></i>Live search: showing ${visibleCount} of ${totalDataRows} patients for "${searchTerm}"`;
                originalSearchResults.querySelector('i').className = 'bx bx-search me-1';
            }
        }

        // Auto focus on search input when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.focus();
                
                // Add multiple event listeners for better responsiveness
                searchInput.addEventListener('input', performLiveSearch);
                searchInput.addEventListener('keyup', performLiveSearch);
                searchInput.addEventListener('paste', function() {
                    setTimeout(performLiveSearch, 10); // Small delay for paste event
                });
            }
        });

        // Keyboard shortcut for search (Ctrl+F or Cmd+F)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                const searchInput = document.getElementById('searchInput');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }
            
            // ESC to clear search
            if (e.key === 'Escape') {
                const searchInput = document.getElementById('searchInput');
                if (searchInput && searchInput.value) {
                    searchInput.value = '';
                    performLiveSearch();
                }
            }
        });
    </script>
</body>
</html>