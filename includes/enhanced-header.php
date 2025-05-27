<?php
// includes/enhanced-header.php - Complete Enhanced Header with All Fixes
// กำหนด path เริ่มต้นถ้าไม่ได้กำหนด
if (!isset($assets_path)) {
    $assets_path = '../assets/';
}

// กำหนด title เริ่มต้น
if (!isset($page_title)) {
    $page_title = 'Medical Dashboard';
}

// Enhanced session security
require_once dirname(__FILE__) . '/enhanced-db-functions.php';
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="<?php echo $assets_path; ?>">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title><?php echo htmlspecialchars($page_title); ?> | Medical System</title>
    <meta name="description" content="Professional Medical Management System - Secure, Efficient, User-Friendly" />
    <meta name="keywords" content="medical, healthcare, patient management, doctor, clinic" />

    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">

    <!-- PWA Support -->
    <meta name="theme-color" content="#696cff">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $assets_path; ?>img/favicon/favicon.ico" />
    <link rel="apple-touch-icon" href="<?php echo $assets_path; ?>img/favicon/apple-touch-icon.png" />

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
    <style>
        /* Include the enhanced styles directly */
        <?php include dirname(__FILE__) . '/../css/enhanced-ui-styles.css'; ?>
    </style>

    <!-- Extra CSS -->
    <?php if (isset($extra_css) && is_array($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>" />
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Critical CSS for immediate rendering -->
    <style>
        /* Critical above-the-fold styles */
        body {
            font-family: 'Public Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: #f4f5fb;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        body.loaded {
            opacity: 1;
        }

        .layout-menu {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        /* Loading screen */
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

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>

    <!-- Extra Scripts (head) -->
    <?php if (isset($extra_scripts_head) && is_array($extra_scripts_head)): ?>
        <?php foreach ($extra_scripts_head as $script): ?>
            <script src="<?php echo htmlspecialchars($script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Essential Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo SecurityManager::generateCsrfToken(); ?>">

    <!-- Helpers -->
    <script src="<?php echo $assets_path; ?>vendor/js/helpers.js"></script>
    <script src="<?php echo $assets_path; ?>js/config.js"></script>

    <!-- Performance monitoring -->
    <script>
        // Performance monitoring
        window.addEventListener('load', function() {
            setTimeout(() => {
                if (window.performance && window.performance.timing) {
                    const timing = window.performance.timing;
                    const loadTime = timing.loadEventEnd - timing.navigationStart;
                    console.log('🚀 Page loaded in:', loadTime + 'ms');

                    // Report to analytics if needed
                    if (loadTime > 3000) {
                        console.warn('⚠️ Slow page load detected');
                    }
                }
            }, 0);
        });
    </script>
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
            <?php include_once dirname(__FILE__) . '/enhanced-sidebar.php'; ?>

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
                        <button type="button" class="sidebar-toggle" onclick="toggleSidebarDesktop()" id="desktop-toggle" title="Toggle Sidebar">
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

                        <!-- Quick Actions -->
                        <div class="navbar-nav align-items-center me-3">
                            <div class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown" title="Quick Actions">
                                    <i class="bx bx-plus-circle fs-4"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <h6 class="dropdown-header">Quick Actions</h6>
                                    </li>
                                    <li><a class="dropdown-item" href="../patients/patients_action.php?action=add">
                                            <i class="bx bx-user-plus me-2"></i>Add Patient
                                        </a></li>
                                    <li><a class="dropdown-item" href="../medical_records/medical_record_action.php?action=add">
                                            <i class="bx bx-file-plus me-2"></i>New Record
                                        </a></li>
                                    <li><a class="dropdown-item" href="../doctors/doctors_action.php?action=add">
                                            <i class="bx bx-user-check me-2"></i>Add Doctor
                                        </a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="exportData()">
                                            <i class="bx bx-download me-2"></i>Export Data
                                        </a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="window.print()">
                                            <i class="bx bx-printer me-2"></i>Print Page
                                        </a></li>
                                </ul>
                            </div>
                        </div>

                        <!-- Notifications -->
                        <div class="navbar-nav align-items-center me-3">
                            <div class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow position-relative" href="javascript:void(0);" data-bs-toggle="dropdown" title="Notifications">
                                    <i class="bx bx-bell fs-4"></i>
                                    <span class="position-absolute top-0 start-100 translate-middle badge badge-center rounded-pill bg-danger" style="font-size: 10px;">3</span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" style="width: 300px;">
                                    <li>
                                        <h6 class="dropdown-header d-flex justify-content-between align-items-center">
                                            Notifications
                                            <span class="badge bg-primary rounded-pill">3</span>
                                        </h6>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item d-flex" href="#">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar avatar-sm">
                                                    <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                        <i class="bx bx-calendar"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">Appointment Reminder</h6>
                                                <p class="mb-0 small text-muted">John Doe has an appointment today at 2:00 PM</p>
                                                <small class="text-muted">5 minutes ago</small>
                                            </div>
                                        </a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item text-center" href="#">View all notifications</a></li>
                                </ul>
                            </div>
                        </div>

                        <!-- Theme Toggle -->
                        <div class="navbar-nav align-items-center me-3">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="theme-toggle" title="Toggle Theme">
                                <i class="bx bx-moon"></i>
                            </button>
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
                                        <a class="dropdown-item" href="#">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar avatar-online">
                                                        <img src="<?php echo $assets_path; ?>img/avatars/1.png" alt="Avatar" class="w-px-40 h-auto rounded-circle" />
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <span class="fw-semibold d-block"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                                                    <small class="text-muted"><?php echo htmlspecialchars($_SESSION['email'] ?? 'user@example.com'); ?></small>
                                                    <small class="badge bg-primary mt-1"><?php echo htmlspecialchars($_SESSION['role'] ?? 'User'); ?></small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                    </li>
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
                                        <a class="dropdown-item" href="javascript:void(0)" onclick="showShortcuts()">
                                            <i class="bx bx-help-circle me-2"></i>
                                            <span class="align-middle">Keyboard Shortcuts</span>
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

                    <!-- Enhanced JavaScript System -->
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

                            // Hide loading screen after minimum time
                            setTimeout(() => {
                                const loadingScreen = document.getElementById('loading-screen');
                                if (loadingScreen) {
                                    loadingScreen.classList.add('fade-out');
                                    setTimeout(() => {
                                        loadingScreen.remove();
                                    }, 500);
                                }

                                // Show body
                                document.body.classList.add('loaded');

                            }, 1000);

                            // Initialize all systems
                            initializeGlobalSearch();
                            initializeThemeToggle();
                            initializeKeyboardShortcuts();
                            initializeNotificationSystem();
                            initializePerformanceMonitoring();

                            console.log('✅ Medical System Ready!');
                        });

                        // Global Search System
                        function initializeGlobalSearch() {
                            const searchInput = document.getElementById('global-search');
                            const resultsContainer = document.getElementById('search-results');
                            let searchTimeout;

                            if (!searchInput || !resultsContainer) return;

                            searchInput.addEventListener('input', function(e) {
                                const query = e.target.value.trim();

                                clearTimeout(searchTimeout);

                                if (query.length < 2) {
                                    resultsContainer.classList.add('d-none');
                                    return;
                                }

                                searchTimeout = setTimeout(() => {
                                    performGlobalSearch(query);
                                }, 300);
                            });

                            // Hide results when clicking outside
                            document.addEventListener('click', function(e) {
                                if (!e.target.closest('#global-search') && !e.target.closest('#search-results')) {
                                    resultsContainer.classList.add('d-none');
                                }
                            });

                            // Keyboard navigation
                            searchInput.addEventListener('keydown', function(e) {
                                const items = resultsContainer.querySelectorAll('.search-result-item');
                                const activeItem = resultsContainer.querySelector('.search-result-item.active');

                                if (e.key === 'ArrowDown') {
                                    e.preventDefault();
                                    const nextItem = activeItem ? activeItem.nextElementSibling : items[0];
                                    if (nextItem) {
                                        if (activeItem) activeItem.classList.remove('active');
                                        nextItem.classList.add('active');
                                    }
                                } else if (e.key === 'ArrowUp') {
                                    e.preventDefault();
                                    const prevItem = activeItem ? activeItem.previousElementSibling : items[items.length - 1];
                                    if (prevItem) {
                                        if (activeItem) activeItem.classList.remove('active');
                                        prevItem.classList.add('active');
                                    }
                                } else if (e.key === 'Enter') {
                                    e.preventDefault();
                                    if (activeItem) {
                                        activeItem.click();
                                    }
                                } else if (e.key === 'Escape') {
                                    resultsContainer.classList.add('d-none');
                                    searchInput.blur();
                                }
                            });
                        }

                        async function performGlobalSearch(query) {
                            const resultsContainer = document.getElementById('search-results');

                            try {
                                resultsContainer.innerHTML = '<div class="p-3 text-center"><div class="spinner-border spinner-border-sm" role="status"></div></div>';
                                resultsContainer.classList.remove('d-none');

                                const response = await fetch('../api/global-search.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-Token': window.MedicalConfig.csrfToken
                                    },
                                    body: JSON.stringify({
                                        query: query
                                    })
                                });

                                const data = await response.json();

                                if (data.success && data.results.length > 0) {
                                    let html = '';

                                    data.results.forEach(result => {
                                        const icon = getTypeIcon(result.type);
                                        html += `
                            <a href="${getResultUrl(result)}" class="search-result-item dropdown-item d-flex align-items-center p-3 border-bottom">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-sm">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            <i class="bx ${icon}"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">${highlightMatch(result.title, query)}</h6>
                                    <p class="mb-0 small text-muted">${result.subtitle || ''}</p>
                                    <small class="text-muted">${result.type}</small>
                                </div>
                            </a>
                        `;
                                    });

                                    resultsContainer.innerHTML = html;
                                } else {
                                    resultsContainer.innerHTML = '<div class="p-3 text-center text-muted">No results found</div>';
                                }

                            } catch (error) {
                                console.error('Search error:', error);
                                resultsContainer.innerHTML = '<div class="p-3 text-center text-danger">Search error occurred</div>';
                            }
                        }

                        function getTypeIcon(type) {
                            const icons = {
                                'patient': 'bx-user',
                                'doctor': 'bx-user-check',
                                'record': 'bx-file-blank'
                            };
                            return icons[type] || 'bx-search';
                        }

                        function getResultUrl(result) {
                            const urls = {
                                'patient': `../patients/patient_view.php?id=${result.id}`,
                                'doctor': `../doctors/doctor_view.php?id=${result.id}`,
                                'record': `../medical_records/medical_record_view.php?id=${result.id}`
                            };
                            return urls[result.type] || '#';
                        }

                        function highlightMatch(text, query) {
                            if (!query) return text;
                            const regex = new RegExp(`(${query})`, 'gi');
                            return text.replace(regex, '<mark>$1</mark>');
                        }

                        // Theme Toggle System
                        function initializeThemeToggle() {
                            const themeToggle = document.getElementById('theme-toggle');
                            const currentTheme = localStorage.getItem('theme') || 'light';

                            // Apply saved theme
                            document.documentElement.setAttribute('data-theme', currentTheme);
                            updateThemeIcon(currentTheme);

                            themeToggle?.addEventListener('click', function() {
                                const currentTheme = document.documentElement.getAttribute('data-theme');
                                const newTheme = currentTheme === 'light' ? 'dark' : 'light';

                                document.documentElement.setAttribute('data-theme', newTheme);
                                localStorage.setItem('theme', newTheme);
                                updateThemeIcon(newTheme);

                                // Show notification
                                showNotification(`Switched to ${newTheme} theme`, 'info', 2000);
                            });
                        }

                        function updateThemeIcon(theme) {
                            const icon = document.querySelector('#theme-toggle i');
                            if (icon) {
                                icon.className = theme === 'light' ? 'bx bx-moon' : 'bx bx-sun';
                            }
                        }

                        // Keyboard Shortcuts
                        function initializeKeyboardShortcuts() {
                            document.addEventListener('keydown', function(e) {
                                // Ctrl/Cmd + K for search
                                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                                    e.preventDefault();
                                    document.getElementById('global-search')?.focus();
                                }

                                // Ctrl/Cmd + N for new patient
                                if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                                    e.preventDefault();
                                    window.location.href = '../patients/patients_action.php?action=add';
                                }

                                // Ctrl/Cmd + Shift + N for new record
                                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'N') {
                                    e.preventDefault();
                                    window.location.href = '../medical_records/medical_record_action.php?action=add';
                                }

                                // Ctrl/Cmd + / for shortcuts help
                                if ((e.ctrlKey || e.metaKey) && e.key === '/') {
                                    e.preventDefault();
                                    showShortcuts();
                                }

                                // Esc to close modals/dropdowns
                                if (e.key === 'Escape') {
                                    document.querySelectorAll('.modal.show').forEach(modal => {
                                        const modalInstance = bootstrap.Modal.getInstance(modal);
                                        modalInstance?.hide();
                                    });
                                }
                            });
                        }

                        function showShortcuts() {
                            Swal.fire({
                                title: 'Keyboard Shortcuts',
                                html: `
                    <div class="text-start">
                        <div class="mb-3">
                            <strong>General:</strong><br>
                            <kbd>Ctrl</kbd> + <kbd>K</kbd> - Global Search<br>
                            <kbd>Ctrl</kbd> + <kbd>/</kbd> - Show Shortcuts<br>
                            <kbd>Esc</kbd> - Close Modals
                        </div>
                        <div class="mb-3">
                            <strong>Quick Actions:</strong><br>
                            <kbd>Ctrl</kbd> + <kbd>N</kbd> - New Patient<br>
                            <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>N</kbd> - New Record
                        </div>
                        <div>
                            <strong>Navigation:</strong><br>
                            <kbd>↑</kbd> <kbd>↓</kbd> - Navigate Search Results<br>
                            <kbd>Enter</kbd> - Select Result
                        </div>
                    </div>
                `,
                                icon: 'info',
                                showConfirmButton: false,
                                showCloseButton: true,
                                width: 500
                            });
                        }

                        // Enhanced Notification System
                        function initializeNotificationSystem() {
                            // Create notification container if it doesn't exist
                            if (!document.querySelector('.notification-container')) {
                                const container = document.createElement('div');
                                container.className = 'notification-container';
                                container.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    max-width: 400px;
                `;
                                document.body.appendChild(container);
                            }
                        }

                        function showNotification(message, type = 'info', duration = 5000) {
                            if (typeof window.medicalEnhancer !== 'undefined') {
                                return window.medicalEnhancer.showNotification(message, type, duration);
                            }

                            // Fallback notification
                            const container = document.querySelector('.notification-container');
                            if (!container) return;

                            const notification = document.createElement('div');
                            notification.className = `alert alert-${type} alert-dismissible fade show notification-item`;
                            notification.style.marginBottom = '10px';

                            const icons = {
                                success: 'bx-check-circle',
                                error: 'bx-error-circle',
                                warning: 'bx-error',
                                info: 'bx-info-circle'
                            };

                            notification.innerHTML = `
                <i class="bx ${icons[type] || icons.info} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

                            container.appendChild(notification);

                            if (duration > 0) {
                                setTimeout(() => {
                                    if (notification.parentNode) {
                                        notification.classList.remove('show');
                                        setTimeout(() => notification.remove(), 150);
                                    }
                                }, duration);
                            }
                        }

                        // Performance Monitoring
                        function initializePerformanceMonitoring() {
                            // Monitor slow operations
                            const originalFetch = window.fetch;
                            window.fetch = function(...args) {
                                const start = performance.now();
                                return originalFetch.apply(this, args).then(response => {
                                    const duration = performance.now() - start;
                                    if (duration > 2000) {
                                        console.warn(`⚠️ Slow API call detected: ${args[0]} (${duration.toFixed(2)}ms)`);
                                    }
                                    return response;
                                });
                            };

                            // Monitor memory usage
                            if ('memory' in performance) {
                                setInterval(() => {
                                    const memory = performance.memory;
                                    if (memory.usedJSHeapSize > memory.jsHeapSizeLimit * 0.9) {
                                        console.warn('⚠️ High memory usage detected');
                                    }
                                }, 30000);
                            }
                        }

                        // Enhanced logout function
                        function confirmLogout() {
                            Swal.fire({
                                title: 'Confirm Logout',
                                text: 'Are you sure you want to logout?',
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonColor: '#dc3545',
                                cancelButtonColor: '#6c757d',
                                confirmButtonText: '<i class="bx bx-log-out me-1"></i>Yes, Logout',
                                cancelButtonText: '<i class="bx bx-x me-1"></i>Cancel',
                                reverseButtons: true,
                                backdrop: true,
                                allowOutsideClick: false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Show loading
                                    Swal.fire({
                                        title: 'Logging Out...',
                                        text: 'Thank you for using Medical System',
                                        icon: 'success',
                                        timer: 1500,
                                        timerProgressBar: true,
                                        showConfirmButton: false,
                                        allowOutsideClick: false
                                    }).then(() => {
                                        window.location.href = '../auth/logout.php';
                                    });
                                }
                            });
                        }

                        // Export function
                        function exportData() {
                            if (typeof window.medicalEnhancer !== 'undefined') {
                                window.medicalEnhancer.exportTableToCSV();
                            } else {
                                showNotification('Export functionality is loading...', 'info');
                            }
                        }

                        // Global error handler
                        window.addEventListener('error', function(e) {
                            console.error('Global error:', e.error);
                            if (e.error.name !== 'ChunkLoadError') {
                                showNotification('An unexpected error occurred', 'error');
                            }
                        });

                        // Unhandled promise rejection handler
                        window.addEventListener('unhandledrejection', function(e) {
                            console.error('Unhandled promise rejection:', e.reason);
                            showNotification('An error occurred while processing your request', 'error');
                        });
                    </script>

                    <!-- Core JS -->
                    <script src="<?php echo $assets_path; ?>vendor/libs/jquery/jquery.js"></script>
                    <script src="<?php echo $assets_path; ?>vendor/libs/popper/popper.js"></script>
                    <script src="<?php echo $assets_path; ?>vendor/js/bootstrap.js"></script>
                    <script src="<?php echo $assets_path; ?>vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
                    <script src="<?php echo $assets_path; ?>vendor/js/menu.js"></script>
                    <script src="<?php echo $assets_path; ?>js/main.js"></script>

                    <!-- Enhanced Scripts -->
                    <script src="<?php echo $assets_path; ?>js/sidebar-manager.js"></script>
                    <script src="<?php echo $assets_path; ?>js/enhanced-functions.js"></script>

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

                    <!-- Service Worker Registration -->
                    <script>
                        if ('serviceWorker' in navigator) {
                            window.addEventListener('load', function() {
                                navigator.serviceWorker.register('../sw.js').then(function(registration) {
                                    console.log('💾 Service Worker registered successfully');
                                }, function(err) {
                                    console.log('❌ Service Worker registration failed');
                                });
                            });
                        }

                        // Global configuration with API endpoints
                        window.MedicalConfig = {
                            baseUrl: '<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']); ?>',
                            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            currentUser: {
                                id: <?php echo $_SESSION['user_id'] ?? 'null'; ?>,
                                username: '<?php echo addslashes($_SESSION['username'] ?? ''); ?>',
                                role: '<?php echo addslashes($_SESSION['role'] ?? ''); ?>'
                            },
                            api: {
                                dashboardStats: '../api/dashboard-stats.php',
                                systemHealth: '../api/system-health.php',
                                quickStats: '../api/quick-stats.php',
                                globalSearch: '../api/global-search.php',
                                notificationCounts: '../api/notification-counts.php'
                            },
                            intervals: {
                                dashboardRefresh: 300000, // 5 minutes
                                healthCheck: 600000, // 10 minutes
                                notificationUpdate: 60000 // 1 minute
                            }
                        };
                    </script>
</body>

</html>