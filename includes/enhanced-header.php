<?php
// includes/enhanced-header.php - Fixed Enhanced Header
// กำหนด path เริ่มต้นถ้าไม่ได้กำหนด
if (!isset($assets_path)) {
    $assets_path = '../assets/';
}

// กำหนด title เริ่มต้น
if (!isset($page_title)) {
    $page_title = 'Medical Dashboard';
}

// Enhanced session security
if (file_exists(dirname(__FILE__) . '/enhanced-db-functions.php')) {
    require_once dirname(__FILE__) . '/enhanced-db-functions.php';
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="<?php echo $assets_path; ?>">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title><?php echo htmlspecialchars($page_title); ?> | Medical System</title>
    <meta name="description" content="Professional Medical Management System - Secure, Efficient, User-Friendly" />

    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $assets_path; ?>img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="<?php echo $assets_path; ?>vendor/fonts/boxicons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="<?php echo $assets_path; ?>vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="<?php echo $assets_path; ?>vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="<?php echo $assets_path; ?>vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- Enhanced UI Styles -->
    <?php if (file_exists(dirname(__FILE__) . '/../assets/css/enhanced-ui-styles.css')): ?>
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/enhanced-ui-styles.css" />
    <?php endif; ?>

    <!-- Extra CSS -->
    <?php if (isset($extra_css) && is_array($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>" />
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Critical CSS for immediate rendering -->
    <style>
        /* Loading and Critical Styles */
        body {
            font-family: 'Public Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: #f4f5fb;
            opacity: 1;
        }

        .layout-menu {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        /* Enhanced Loading Screen */
        #loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #696cff, #5a67d8);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 1;
            transition: opacity 0.5s ease;
        }

        #loading-screen.fade-out {
            opacity: 0;
            pointer-events: none;
        }

        .loading-logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        .loading-text {
            color: white;
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Fix for Sidebar Issues */
        .layout-wrapper {
            min-height: 100vh;
        }

        .layout-menu-fixed .layout-navbar-full .layout-menu,
        .layout-page {
            padding-top: 0 !important;
        }

        /* Responsive fixes */
        @media (max-width: 1199.98px) {
            .layout-menu {
                transform: translateX(-100%);
                width: 260px;
                transition: transform 0.3s ease;
            }

            .layout-menu.show {
                transform: translateX(0);
            }

            .layout-page {
                margin-left: 0 !important;
                width: 100% !important;
            }

            .layout-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1045;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            }

            .layout-overlay.show {
                opacity: 1;
                visibility: visible;
            }
        }

        @media (min-width: 1200px) {
            .layout-page {
                margin-left: 260px;
                width: calc(100% - 260px);
                transition: all 0.3s ease;
            }

            .layout-page.sidebar-collapsed {
                margin-left: 80px;
                width: calc(100% - 80px);
            }
        }
    </style>

    <!-- Extra Scripts (head) -->
    <?php if (isset($extra_scripts_head) && is_array($extra_scripts_head)): ?>
        <?php foreach ($extra_scripts_head as $script): ?>
            <script src="<?php echo htmlspecialchars($script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo isset($_SESSION) && function_exists('SecurityManager::generateCsrfToken') ? SecurityManager::generateCsrfToken() : bin2hex(random_bytes(16)); ?>">

    <!-- Helpers -->
    <script src="<?php echo $assets_path; ?>vendor/js/helpers.js"></script>
    <script src="<?php echo $assets_path; ?>js/config.js"></script>
</head>

<body>
    <!-- Loading Screen -->
    <div id="loading-screen">
        <div class="loading-logo">
            <i class="bx bx-plus-medical" style="font-size: 40px; color: #696cff;"></i>
        </div>
        <div class="loading-text">Medical System</div>
        <div class="loading-spinner"></div>
    </div>

    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">

            <!-- Mobile Overlay -->
            <div class="layout-overlay" onclick="closeSidebar()"></div>

            <!-- Include Enhanced Sidebar -->
            <?php 
            $sidebar_path = dirname(__FILE__) . '/enhanced-sidebar.php';
            if (file_exists($sidebar_path)) {
                include_once $sidebar_path;
            } else {
                // Fallback to simple sidebar
                echo '<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                        <div class="app-brand demo">
                            <a href="../dashboard/index.php" class="app-brand-link">
                                <span class="app-brand-text demo menu-text fw-bold">Medical</span>
                            </a>
                        </div>
                        <div class="menu-inner-shadow"></div>
                        <ul class="menu-inner py-1">
                            <li class="menu-item">
                                <a href="../dashboard/index.php" class="menu-link">
                                    <i class="menu-icon tf-icons bx bx-home-circle"></i>
                                    <div class="menu-text">Dashboard</div>
                                </a>
                            </li>
                        </ul>
                      </aside>';
            }
            ?>

            <!-- Layout page -->
            <div class="layout-page">

                <!-- Enhanced Navbar -->
                <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">

                    <!-- Mobile Menu Toggle -->
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)" onclick="toggleSidebar()">
                            <i class="bx bx-menu bx-sm"></i>
                        </a>
                    </div>

                    <!-- Desktop Sidebar Toggle -->
                    <div class="d-none d-xl-flex">
                        <button type="button" class="sidebar-toggle btn btn-sm btn-outline-secondary" onclick="toggleSidebarDesktop()" id="desktop-toggle" title="Toggle Sidebar">
                            <i class="bx bx-menu"></i>
                        </button>
                    </div>

                    <!-- Navbar Content -->
                    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">

                        <!-- Global Search -->
                        <div class="navbar-nav align-items-center me-3">
                            <div class="nav-item d-flex align-items-center position-relative">
                                <i class="bx bx-search fs-4 lh-0 position-absolute" style="left: 12px; z-index: 5; color: #999;"></i>
                                <input
                                    type="text"
                                    id="global-search"
                                    class="form-control border-0 shadow-none ps-5 pe-2"
                                    placeholder="Search patients, doctors, records..."
                                    style="background: #f8f9fa; border-radius: 25px; width: 300px;"
                                    autocomplete="off" />

                                <!-- Search Results Dropdown -->
                                <div id="search-results" class="position-absolute top-100 start-0 w-100 bg-white shadow-lg rounded-3 mt-2 d-none" style="z-index: 1000; max-height: 400px; overflow-y: auto;">
                                    <!-- Results will be populated here -->
                                </div>
                            </div>
                        </div>

                        <!-- User Dropdown -->
                        <ul class="navbar-nav flex-row align-items-center ms-auto">
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow d-flex align-items-center" href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online me-2">
                                        <img src="<?php echo $assets_path; ?>img/avatars/1.png" alt="Avatar" class="w-px-40 h-auto rounded-circle" />
                                    </div>
                                    <div class="d-none d-md-block">
                                        <span class="fw-semibold d-block"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                                        <small class="text-muted"><?php echo htmlspecialchars($_SESSION['role'] ?? 'User'); ?></small>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="../dashboard/profile.php">
                                            <i class="bx bx-user me-2"></i>
                                            <span class="align-middle">My Profile</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="../dashboard/settings.php">
                                            <i class="bx bx-cog me-2"></i>
                                            <span class="align-middle">Settings</span>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0);" onclick="confirmLogout()">
                                            <i class="bx bx-power-off me-2 text-danger"></i>
                                            <span class="align-middle">Log Out</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
                <!-- / Navbar -->

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content will be inserted here by each page -->

                    <!-- Basic JavaScript System -->
                    <script>
                        // Global configuration
                        window.MedicalConfig = {
                            baseUrl: '<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']); ?>',
                            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            currentUser: {
                                id: <?php echo $_SESSION['user_id'] ?? 'null'; ?>,
                                username: '<?php echo addslashes($_SESSION['username'] ?? ''); ?>',
                                role: '<?php echo addslashes($_SESSION['role'] ?? ''); ?>'
                            }
                        };

                        // Enhanced loading system
                        document.addEventListener('DOMContentLoaded', function() {
                            console.log('🏥 Medical System Initializing...');

                            // Hide loading screen
                            setTimeout(() => {
                                const loadingScreen = document.getElementById('loading-screen');
                                if (loadingScreen) {
                                    loadingScreen.classList.add('fade-out');
                                    setTimeout(() => {
                                        loadingScreen.remove();
                                    }, 500);
                                }
                            }, 1000);

                            // Initialize basic systems
                            initializeSidebarFunctions();
                            initializeBasicFeatures();

                            console.log('✅ Medical System Ready!');
                        });

                        // Basic sidebar functions
                        function initializeSidebarFunctions() {
                            // Mobile sidebar toggle
                            window.toggleSidebar = function() {
                                const sidebar = document.getElementById('layout-menu');
                                const overlay = document.querySelector('.layout-overlay');
                                
                                if (sidebar && overlay) {
                                    sidebar.classList.toggle('show');
                                    overlay.classList.toggle('show');
                                    
                                    if (sidebar.classList.contains('show')) {
                                        document.body.style.overflow = 'hidden';
                                    } else {
                                        document.body.style.overflow = '';
                                    }
                                }
                            };

                            // Desktop sidebar toggle
                            window.toggleSidebarDesktop = function() {
                                const sidebar = document.getElementById('layout-menu');
                                const layoutPage = document.querySelector('.layout-page');
                                
                                if (sidebar && layoutPage) {
                                    sidebar.classList.toggle('collapsed');
                                    layoutPage.classList.toggle('sidebar-collapsed');
                                }
                            };

                            // Close sidebar
                            window.closeSidebar = function() {
                                const sidebar = document.getElementById('layout-menu');
                                const overlay = document.querySelector('.layout-overlay');
                                
                                if (sidebar && overlay) {
                                    sidebar.classList.remove('show');
                                    overlay.classList.remove('show');
                                    document.body.style.overflow = '';
                                }
                            };
                        }

                        function initializeBasicFeatures() {
                            // Basic search functionality
                            const searchInput = document.getElementById('global-search');
                            if (searchInput) {
                                searchInput.addEventListener('input', function(e) {
                                    const query = e.target.value.trim();
                                    if (query.length > 2) {
                                        // Basic search implementation
                                        console.log('Searching for:', query);
                                    }
                                });
                            }
                        }

                        // Enhanced logout function
                        function confirmLogout() {
                            if (confirm('Are you sure you want to logout?')) {
                                window.location.href = '../auth/logout.php';
                            }
                        }

                        // Global error handler
                        window.addEventListener('error', function(e) {
                            console.error('Global error:', e.error);
                        });
                    </script>

                    <!-- Core JS -->
                    <script src="<?php echo $assets_path; ?>vendor/libs/jquery/jquery.js"></script>
                    <script src="<?php echo $assets_path; ?>vendor/libs/popper/popper.js"></script>
                    <script src="<?php echo $assets_path; ?>vendor/js/bootstrap.js"></script>
                    <script src="<?php echo $assets_path; ?>vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
                    <script src="<?php echo $assets_path; ?>vendor/js/menu.js"></script>
                    <script src="<?php echo $assets_path; ?>js/main.js"></script>

                    <!-- Enhanced Scripts (if available) -->
                    <?php if (file_exists(dirname(__FILE__) . '/../assets/js/sidebar-manager.js')): ?>
                        <script src="<?php echo $assets_path; ?>js/sidebar-manager.js"></script>
                    <?php endif; ?>

                    <?php if (file_exists(dirname(__FILE__) . '/../assets/js/enhanced-functions.js')): ?>
                        <script src="<?php echo $assets_path; ?>js/enhanced-functions.js"></script>
                    <?php endif; ?>

                    <!-- Extra JS -->
                    <?php if (isset($extra_js) && is_array($extra_js)): ?>
                        <?php foreach ($extra_js as $js): ?>
                            <script src="<?php echo htmlspecialchars($js); ?>"></script>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Custom JS -->
                    <?php if (isset($custom_js)): ?>
                        <script>
                            <?php echo $custom_js; ?>
                        </script>
                    <?php endif; ?>
</body>
</html>