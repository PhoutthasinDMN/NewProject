<?php
// includes/header.php - Complete fix for layout system

// กำหนด path เริ่มต้นถ้าไม่ได้กำหนด
if (!isset($assets_path)) {
    $assets_path = '../assets/';
}

// กำหนด title เริ่มต้น
if (!isset($page_title)) {
    $page_title = 'Medical Dashboard';
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="<?php echo $assets_path; ?>">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title><?php echo htmlspecialchars($page_title); ?> | Medical System</title>
    
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
    
    <!-- Extra CSS -->
    <?php if (isset($extra_css) && is_array($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>" />
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Extra Scripts (head) -->
    <?php if (isset($extra_scripts_head) && is_array($extra_scripts_head)): ?>
        <?php foreach ($extra_scripts_head as $script): ?>
            <script src="<?php echo htmlspecialchars($script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Helpers -->
    <script src="<?php echo $assets_path; ?>vendor/js/helpers.js"></script>
    <script src="<?php echo $assets_path; ?>js/config.js"></script>
    
    <style>
        /* RESET LAYOUT AND OVERRIDE CONFLICTING STYLES */
        :root {
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 80px;
            --primary-color: #696cff;
            --primary-dark: #5a67d8;
            --transition-duration: 0.3s;
        }

        /* Force override any existing layout */
        .layout-wrapper,
        .layout-container {
            display: block !important;
            position: relative !important;
            width: 100% !important;
            min-height: 100vh !important;
        }

        /* Sidebar styles - FORCE DISPLAY */
        .layout-menu {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: var(--sidebar-width) !important;
            height: 100vh !important;
            background: #fff !important;
            border-right: 1px solid #e7eaf3 !important;
            z-index: 1050 !important;
            transform: translateX(0) !important;
            transition: all var(--transition-duration) ease !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.08) !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        /* Main content area */
        .layout-page {
            margin-left: var(--sidebar-width) !important;
            min-height: 100vh !important;
            transition: margin-left var(--transition-duration) ease !important;
            position: relative !important;
            width: calc(100% - var(--sidebar-width)) !important;
            display: block !important;
        }

        /* Collapsed state */
        .layout-menu.collapsed {
            width: var(--sidebar-collapsed-width) !important;
        }

        .layout-page.sidebar-collapsed {
            margin-left: var(--sidebar-collapsed-width) !important;
            width: calc(100% - var(--sidebar-collapsed-width)) !important;
        }

        /* Hide elements when collapsed */
        .layout-menu.collapsed .app-brand-text,
        .layout-menu.collapsed .menu-text,
        .layout-menu.collapsed .badge,
        .layout-menu.collapsed .text-success,
        .layout-menu.collapsed .text-warning {
            display: none !important;
        }

        .layout-menu.collapsed .menu-sub {
            display: none !important;
        }

        .layout-menu.collapsed .menu-link {
            justify-content: center !important;
            padding: 0.75rem !important;
        }

        .layout-menu.collapsed .menu-icon {
            margin-right: 0 !important;
        }

        /* Mobile responsive */
        @media (max-width: 1199.98px) {
            .layout-menu {
                transform: translateX(-100%) !important;
            }

            .layout-menu.show {
                transform: translateX(0) !important;
            }

            .layout-page {
                margin-left: 0 !important;
                width: 100% !important;
            }

            .layout-page.sidebar-collapsed {
                margin-left: 0 !important;
                width: 100% !important;
            }

            /* Reset collapsed styles on mobile */
            .layout-menu.collapsed .app-brand-text,
            .layout-menu.collapsed .menu-text,
            .layout-menu.collapsed .badge,
            .layout-menu.collapsed .text-success,
            .layout-menu.collapsed .text-warning {
                display: block !important;
            }

            .layout-menu.collapsed .menu-sub {
                display: block !important;
            }

            .layout-menu.collapsed .menu-link {
                justify-content: flex-start !important;
                padding: 0.75rem 1rem !important;
            }

            .layout-menu.collapsed .menu-icon {
                margin-right: 0.75rem !important;
            }
        }

        /* Overlay for mobile */
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
            transition: all var(--transition-duration) ease;
            backdrop-filter: blur(4px);
        }

        .layout-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        /* Navbar */
        .layout-navbar {
            position: sticky;
            top: 0;
            z-index: 1040;
            background: #fff !important;
            border-bottom: 1px solid #e7eaf3;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            height: 64px;
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
        }

        /* Toggle buttons */
        .layout-menu-toggle,
        .sidebar-toggle {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: #697a8d;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .layout-menu-toggle:hover,
        .sidebar-toggle:hover {
            background: #f5f5f9;
            color: var(--primary-color);
        }

        .layout-menu-toggle {
            display: none;
        }

        .sidebar-toggle {
            display: flex;
            margin-right: 1rem;
        }

        @media (max-width: 1199.98px) {
            .layout-menu-toggle {
                display: flex;
            }
            .sidebar-toggle {
                display: none;
            }
        }

        /* Content wrapper */
        .content-wrapper {
            padding: 1.5rem;
            min-height: calc(100vh - 64px);
        }

        @media (max-width: 767.98px) {
            .content-wrapper {
                padding: 1rem;
            }
        }

        /* Medical System Styles */
        .stats-card { 
            border-radius: 15px; 
            transition: all 0.3s ease; 
            border: none; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .stats-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 12px 40px rgba(0,0,0,0.15); 
        }

        .welcome-card { 
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); 
            color: white; 
            border-radius: 20px;
            border: none;
            box-shadow: 0 8px 30px rgba(105, 108, 255, 0.2);
        }

        .card { 
            border-radius: 15px; 
            border: none; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: box-shadow 0.3s ease;
        }

        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(105, 108, 255, 0.3);
        }

        /* Force display sidebar */
        #layout-menu {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        /* Custom CSS from page */
        <?php if (isset($custom_css)): ?>
            <?php echo $custom_css; ?>
        <?php endif; ?>
    </style>
</head>
<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            
            <!-- Mobile Overlay -->
            <div class="layout-overlay" onclick="closeSidebar()"></div>
            
            <!-- Include Sidebar -->
            <?php include_once dirname(__FILE__) . '/sidebar.php'; ?>
            
            <!-- Layout page -->
            <div class="layout-page">
                
                <!-- Navbar -->
                <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
                    
                    <!-- Mobile Menu Toggle -->
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)" onclick="toggleSidebar()">
                            <i class="bx bx-menu bx-sm"></i>
                        </a>
                    </div>

                    <!-- Desktop Sidebar Toggle -->
                    <div class="d-none d-xl-flex">
                        <button type="button" class="sidebar-toggle" onclick="toggleSidebarDesktop()" id="desktop-toggle">
                            <i class="bx bx-menu"></i>
                        </button>
                    </div>

                    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
                        <!-- Search -->
                        <div class="navbar-nav align-items-center">
                            <div class="nav-item d-flex align-items-center">
                                <i class="bx bx-search fs-4 lh-0"></i>
                                <input
                                    type="text"
                                    class="form-control border-0 shadow-none ps-1 ps-sm-2"
                                    placeholder="Search..."
                                    aria-label="Search..." />
                            </div>
                        </div>
                        <!-- /Search -->

                        <ul class="navbar-nav flex-row align-items-center ms-auto">
                            <!-- User Dropdown -->
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <img src="<?php echo $assets_path; ?>img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="#">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar avatar-online">
                                                        <img src="<?php echo $assets_path; ?>img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <span class="fw-semibold d-block"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                                                    <small class="text-muted"><?php echo htmlspecialchars($_SESSION['email'] ?? 'user@example.com'); ?></small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo isset($base_url) ? $base_url : '../dashboard/'; ?>profile.php">
                                            <i class="bx bx-user me-2"></i>
                                            <span class="align-middle">My Profile</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo isset($base_url) ? $base_url : '../dashboard/'; ?>settings.php">
                                            <i class="bx bx-cog me-2"></i>
                                            <span class="align-middle">Settings</span>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0);" onclick="confirmLogout()">
                                            <i class="bx bx-power-off me-2"></i>
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

    <!-- Complete JavaScript System -->
    <script>
        // Disable conflicting scripts
        if (window.Helpers && window.Helpers.toggleCollapsed) {
            window.Helpers.toggleCollapsed = function() {
                // Disabled to prevent conflicts
            };
        }

        // Clean sidebar management
        const MedicalSidebarManager = {
            state: {
                isCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
                isMobile: window.innerWidth < 1200,
                isOpen: false
            },
            
            elements: {
                sidebar: null,
                layoutPage: null,
                overlay: null
            },
            
            init() {
                console.log('🏥 Medical Sidebar Manager Initialized');
                
                // Get elements
                this.elements.sidebar = document.getElementById('layout-menu');
                this.elements.layoutPage = document.querySelector('.layout-page');
                this.elements.overlay = document.querySelector('.layout-overlay');
                
                // Force display sidebar
                if (this.elements.sidebar) {
                    this.elements.sidebar.style.display = 'block';
                    this.elements.sidebar.style.visibility = 'visible';
                    this.elements.sidebar.style.opacity = '1';
                }
                
                // Setup initial state
                this.updateDeviceState();
                this.applyState();
                this.setupEventListeners();
                
                console.log('✅ Sidebar is now visible and functional');
            },
            
            updateDeviceState() {
                this.state.isMobile = window.innerWidth < 1200;
            },
            
            applyState() {
                if (!this.state.isMobile && this.state.isCollapsed) {
                    this.elements.sidebar?.classList.add('collapsed');
                    this.elements.layoutPage?.classList.add('sidebar-collapsed');
                } else {
                    this.elements.sidebar?.classList.remove('collapsed');
                    this.elements.layoutPage?.classList.remove('sidebar-collapsed');
                }
            },
            
            setupEventListeners() {
                // Window resize
                window.addEventListener('resize', () => {
                    this.updateDeviceState();
                    this.applyState();
                });
                
                // Escape key for mobile
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && this.state.isMobile && this.state.isOpen) {
                        this.closeMobile();
                    }
                });
                
                // Menu clicks on mobile
                const menuLinks = document.querySelectorAll('.menu-link:not(.menu-toggle)');
                menuLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        if (this.state.isMobile && this.state.isOpen) {
                            setTimeout(() => this.closeMobile(), 200);
                        }
                    });
                });
                
                // Submenu toggles
                const menuToggles = document.querySelectorAll('.menu-toggle');
                menuToggles.forEach(toggle => {
                    toggle.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (!this.state.isMobile && this.state.isCollapsed) return;
                        
                        const menuItem = toggle.closest('.menu-item');
                        const submenu = menuItem?.querySelector('.menu-sub');
                        
                        if (submenu) {
                            const isOpen = submenu.style.display === 'block';
                            
                            // Close all other submenus
                            document.querySelectorAll('.menu-sub').forEach(sub => {
                                if (sub !== submenu) {
                                    sub.style.display = 'none';
                                    sub.closest('.menu-item')?.classList.remove('open');
                                }
                            });
                            
                            // Toggle current submenu
                            if (isOpen) {
                                submenu.style.display = 'none';
                                menuItem?.classList.remove('open');
                            } else {
                                submenu.style.display = 'block';
                                menuItem?.classList.add('open');
                            }
                        }
                    });
                });
            },
            
            toggleMobile() {
                if (!this.state.isMobile) return;
                
                if (this.state.isOpen) {
                    this.closeMobile();
                } else {
                    this.openMobile();
                }
            },
            
            openMobile() {
                if (!this.state.isMobile) return;
                
                this.elements.sidebar?.classList.add('show');
                this.elements.overlay?.classList.add('show');
                document.body.style.overflow = 'hidden';
                this.state.isOpen = true;
            },
            
            closeMobile() {
                this.elements.sidebar?.classList.remove('show');
                this.elements.overlay?.classList.remove('show');
                document.body.style.overflow = '';
                this.state.isOpen = false;
            },
            
            toggleDesktop() {
                if (this.state.isMobile) return;
                
                this.state.isCollapsed = !this.state.isCollapsed;
                this.applyState();
                localStorage.setItem('sidebarCollapsed', this.state.isCollapsed.toString());
                
                // Update toggle button icon
                const icon = document.querySelector('#desktop-toggle i');
                if (icon) {
                    icon.style.transform = this.state.isCollapsed ? 'rotate(180deg)' : 'rotate(0deg)';
                }
            }
        };

        // Global functions
        function toggleSidebar() {
            MedicalSidebarManager.toggleMobile();
        }
        
        function toggleSidebarDesktop() {
            MedicalSidebarManager.toggleDesktop();
        }
        
        function closeSidebar() {
            MedicalSidebarManager.closeMobile();
        }

        // Logout function
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
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Logging Out...',
                        text: 'Thank you for using Medical System',
                        icon: 'success',
                        timer: 1500,
                        timerProgressBar: true,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = '<?php echo isset($auth_url) ? $auth_url : "../auth/"; ?>logout.php';
                    });
                }
            });
        }

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎬 Medical System Loading...');
            
            // Small delay to ensure everything is ready
            setTimeout(() => {
                MedicalSidebarManager.init();
                
                // Auto-expand active menus
                const activeMenuItem = document.querySelector('.menu-item.active.open');
                if (activeMenuItem) {
                    const submenu = activeMenuItem.querySelector('.menu-sub');
                    if (submenu) {
                        submenu.style.display = 'block';
                        activeMenuItem.classList.add('open');
                    }
                }
                
                // Force show sidebar
                const sidebar = document.getElementById('layout-menu');
                if (sidebar) {
                    sidebar.style.display = 'block';
                    sidebar.style.visibility = 'visible';
                    sidebar.style.opacity = '1';
                }
                
                // Expose for debugging
                window.MedicalSidebarManager = MedicalSidebarManager;
                
                console.log('✅ Medical System Ready!');
            }, 100);
        });

        // Prevent any script that might hide the sidebar
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                const sidebar = document.getElementById('layout-menu');
                if (sidebar && (sidebar.style.display === 'none' || sidebar.style.visibility === 'hidden')) {
                    sidebar.style.display = 'block';
                    sidebar.style.visibility = 'visible';
                    sidebar.style.opacity = '1';
                }
            });
        });

        if (document.getElementById('layout-menu')) {
            observer.observe(document.getElementById('layout-menu'), {
                attributes: true,
                attributeFilter: ['style', 'class']
            });
        }
    </script>

    <!-- Core JS -->
    <script src="<?php echo $assets_path; ?>vendor/libs/jquery/jquery.js"></script>
    <script src="<?php echo $assets_path; ?>vendor/libs/popper/popper.js"></script>
    <script src="<?php echo $assets_path; ?>vendor/js/bootstrap.js"></script>
    <script src="<?php echo $assets_path; ?>vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="<?php echo $assets_path; ?>vendor/js/menu.js"></script>
    <script src="<?php echo $assets_path; ?>js/main.js"></script>

    <!-- Extra JS -->
    <?php if (isset($extra_js) && is_array($extra_js)): ?>
        <?php foreach ($extra_js as $js): ?>
            <script src="<?php echo htmlspecialchars($js); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Custom JS -->
    <?php if (isset($custom_js)): ?>
        <script><?php echo $custom_js; ?></script>
    <?php endif; ?>