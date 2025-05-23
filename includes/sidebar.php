<?php
// includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!-- Sidebar -->
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="<?php echo isset($base_url) ? $base_url : '../dashboard/'; ?>index.php" class="app-brand-link">
            <span class="app-brand-logo demo">
                <svg width="25" viewBox="0 0 25 42" version="1.1" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <path d="M13.7918663,0.358365126 L3.39788168,7.44174259 C0.566865006,9.69408886 -0.379795268,12.4788597 0.557900856,15.7960551 C0.68998853,16.2305145 1.09562888,17.7872135 3.12357076,19.2293357 C3.8146334,19.7207684 5.32369333,20.3834223 7.65075054,21.2172976 L7.59773219,21.2525164 L2.63468769,24.5493413 C0.445452254,26.3002124 0.0884951797,28.5083815 1.56381646,31.1738486 C2.83770406,32.8170431 5.20850219,33.2640127 7.09180128,32.5391577 C8.347334,32.0559211 11.4559176,30.0011079 16.4175519,26.3747182 C18.0338572,24.4997857 18.6973423,22.4544883 18.4080071,20.2388261 C17.963753,17.5346866 16.1776345,15.5799961 13.0496516,14.3747546 L10.9194936,13.4715819 L18.6192054,7.984237 L13.7918663,0.358365126 Z" id="path-1"></path>
                    </defs>
                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <g transform="translate(-27.000000, -15.000000)">
                            <g transform="translate(27.000000, 15.000000)">
                                <g transform="translate(0.000000, 8.000000)">
                                    <mask fill="white">
                                        <use xlink:href="#path-1"></use>
                                    </mask>
                                    <use fill="#696cff" xlink:href="#path-1"></use>
                                </g>
                            </g>
                        </g>
                    </g>
                </svg>
            </span>
            <span class="app-brand-text demo menu-text fw-bolder ms-2">Medical</span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <!-- Dashboard -->
        <li class="menu-item <?php echo ($current_page == 'index' && $current_dir == 'dashboard') ? 'active' : ''; ?>">
            <a href="<?php echo isset($base_url) ? $base_url : '../dashboard/'; ?>index.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div>Dashboard</div>
            </a>
        </li>

        <!-- Profile -->
        <li class="menu-item <?php echo (in_array($current_page, ['profile', 'profile-edit']) || ($current_dir == 'profile')) ? 'active open' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-user"></i>
                <div>Profile</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo ($current_page == 'profile') ? 'active' : ''; ?>">
                    <a href="<?php echo isset($base_url) ? $base_url : '../dashboard/'; ?>profile.php" class="menu-link">
                        <div>View Profile</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'profile-edit') ? 'active' : ''; ?>">
                    <a href="<?php echo isset($base_url) ? $base_url : '../dashboard/'; ?>profile-edit.php" class="menu-link">
                        <div>Edit Profile</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Patients -->
        <li class="menu-item <?php echo ($current_dir == 'patients' || in_array($current_page, ['patients', 'patient_view', 'patients_action', 'medical_records', 'medical_records_action', 'medical_records_table'])) ? 'active open' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-group"></i>
                <div>Patients</div>
                <?php
                // Get patient count for badge (optional)
                if (isset($conn)) {
                    $patient_count = $conn->query("SELECT COUNT(*) as count FROM patients")->fetch_assoc()['count'] ?? 0;
                    if ($patient_count > 0) {
                        echo '<div class="badge badge-center rounded-pill bg-primary ms-auto">' . $patient_count . '</div>';
                    }
                }
                ?>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo (in_array($current_page, ['patients', 'patient_view'])) ? 'active' : ''; ?>">
                    <a href="<?php echo isset($patients_url) ? $patients_url : '../patients/'; ?>patients.php" class="menu-link">
                        <div>All Patients</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'patients_action' && (isset($_GET['action']) && $_GET['action'] == 'add')) ? 'active' : ''; ?>">
                    <a href="<?php echo isset($patients_url) ? $patients_url : '../patients/'; ?>patients_action.php?action=add" class="menu-link">
                        <div>Add Patient</div>
                    </a>
                </li>
                <li class="menu-item <?php echo (in_array($current_page, ['medical_records', 'medical_records_table'])) ? 'active' : ''; ?>">
                    <a href="<?php echo isset($patients_url) ? $patients_url : '../patients/'; ?>medical_records.php" class="menu-link">
                        <div>Medical Records</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'medical_records_action' && (isset($_GET['action']) && $_GET['action'] == 'add')) ? 'active' : ''; ?>">
                    <a href="<?php echo isset($patients_url) ? $patients_url : '../patients/'; ?>medical_records_action.php?action=add" class="menu-link">
                        <div>New Record</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Doctors -->
        <li class="menu-item <?php echo ($current_dir == 'doctors' || in_array($current_page, ['doctors', 'doctor_view', 'doctors_action'])) ? 'active open' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-user-check"></i>
                <div>Doctors</div>
                <?php
                // Get doctor count for badge (optional)
                if (isset($conn)) {
                    $doctor_count = $conn->query("SELECT COUNT(*) as count FROM doctors")->fetch_assoc()['count'] ?? 0;
                    if ($doctor_count > 0) {
                        echo '<div class="badge badge-center rounded-pill bg-warning ms-auto">' . $doctor_count . '</div>';
                    }
                }
                ?>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo ($current_page == 'doctors_action' && (!isset($_GET['action']) || $_GET['action'] == 'list')) ? 'active' : ''; ?>">
                    <a href="<?php echo isset($doctors_url) ? $doctors_url : '../doctors/'; ?>doctors_action.php" class="menu-link">
                        <div>All Doctors</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'doctors_action' && (isset($_GET['action']) && $_GET['action'] == 'add')) ? 'active' : ''; ?>">
                    <a href="<?php echo isset($doctors_url) ? $doctors_url : '../doctors/'; ?>doctors_action.php?action=add" class="menu-link">
                        <div>Add Doctor</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Appointments -->
        <li class="menu-item <?php echo ($current_dir == 'appointments' || in_array($current_page, ['appointments', 'appointment_view', 'appointments_action'])) ? 'active open' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-calendar"></i>
                <div>Appointments</div>
                <?php
                // Get upcoming appointments count for badge
                if (isset($conn)) {
                    $appointment_count = $conn->query("SELECT COUNT(*) as count FROM medical_records WHERE next_appointment > NOW()")->fetch_assoc()['count'] ?? 0;
                    if ($appointment_count > 0) {
                        echo '<div class="badge badge-center rounded-pill bg-info ms-auto">' . $appointment_count . '</div>';
                    }
                }
                ?>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo ($current_page == 'appointments') ? 'active' : ''; ?>">
                    <a href="<?php echo isset($appointments_url) ? $appointments_url : '../appointments/'; ?>appointments.php" class="menu-link">
                        <div>All Appointments</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'appointments_action' && (isset($_GET['action']) && $_GET['action'] == 'add')) ? 'active' : ''; ?>">
                    <a href="<?php echo isset($appointments_url) ? $appointments_url : '../appointments/'; ?>appointments_action.php?action=add" class="menu-link">
                        <div>Schedule Appointment</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'calendar') ? 'active' : ''; ?>">
                    <a href="<?php echo isset($appointments_url) ? $appointments_url : '../appointments/'; ?>calendar.php" class="menu-link">
                        <div>Calendar View</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Reports -->
        <li class="menu-item <?php echo ($current_dir == 'reports' || in_array($current_page, ['reports', 'analytics', 'export'])) ? 'active open' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-bar-chart-alt-2"></i>
                <div>Reports</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo ($current_page == 'patients' && $current_dir == 'reports') ? 'active' : ''; ?>">
                    <a href="<?php echo isset($reports_url) ? $reports_url : '../reports/'; ?>patients.php" class="menu-link">
                        <div>Patient Reports</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'medical' && $current_dir == 'reports') ? 'active' : ''; ?>">
                    <a href="<?php echo isset($reports_url) ? $reports_url : '../reports/'; ?>medical.php" class="menu-link">
                        <div>Medical Reports</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'analytics') ? 'active' : ''; ?>">
                    <a href="<?php echo isset($reports_url) ? $reports_url : '../reports/'; ?>analytics.php" class="menu-link">
                        <div>Analytics</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'export') ? 'active' : ''; ?>">
                    <a href="<?php echo isset($reports_url) ? $reports_url : '../reports/'; ?>export.php" class="menu-link">
                        <div>Export Data</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Inventory (Optional) -->
        <li class="menu-item <?php echo ($current_dir == 'inventory' || in_array($current_page, ['inventory', 'medicines', 'equipment'])) ? 'active open' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-package"></i>
                <div>Inventory</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo ($current_page == 'medicines') ? 'active' : ''; ?>">
                    <a href="<?php echo isset($inventory_url) ? $inventory_url : '../inventory/'; ?>medicines.php" class="menu-link">
                        <div>Medicines</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'equipment') ? 'active' : ''; ?>">
                    <a href="<?php echo isset($inventory_url) ? $inventory_url : '../inventory/'; ?>equipment.php" class="menu-link">
                        <div>Equipment</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'stock') ? 'active' : ''; ?>">
                    <a href="<?php echo isset($inventory_url) ? $inventory_url : '../inventory/'; ?>stock.php" class="menu-link">
                        <div>Stock Management</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Quick Actions Section -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Quick Actions</span>
        </li>

        <!-- Quick Add Patient -->
        <li class="menu-item">
            <a href="<?php echo isset($patients_url) ? $patients_url : '../patients/'; ?>patients_action.php?action=add" class="menu-link">
                <i class="menu-icon tf-icons bx bx-plus-circle text-success"></i>
                <div>Add Patient</div>
            </a>
        </li>

        <!-- Quick Add Doctor -->
        <li class="menu-item">
            <a href="<?php echo isset($doctors_url) ? $doctors_url : '../doctors/'; ?>doctors_action.php?action=add" class="menu-link">
                <i class="menu-icon tf-icons bx bx-user-plus text-warning"></i>
                <div>Add Doctor</div>
            </a>
        </li>

        <!-- Logout -->
        <li class="menu-item">
            <a href="<?php echo isset($auth_url) ? $auth_url : '../auth/'; ?>logout.php" class="menu-link" onclick="return confirm('Are you sure you want to logout?')">
                <i class="menu-icon tf-icons bx bx-log-out text-danger"></i>
                <div>Logout</div>
            </a>
        </li>
    </ul>
</aside>

<!-- Additional CSS for enhanced sidebar -->
 <style>
   /* Sidebar menu styles */
.menu-item .badge {
    font-size: 0.65rem;
    min-width: 1.25rem;
    height: 1.25rem;
    line-height: 1;
}

/* Menu header styling */
.menu-header-text {
    color: #a1acb8 !important;
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* Quick actions styling */
.menu-item .text-success {
    color: #28a745 !important;
}

.menu-item .text-warning {
    color: #ffc107 !important;
}

.menu-item .text-danger {
    color: #dc3545 !important;
}

/* Hover effects */
.menu-link:hover .menu-icon {
    transform: scale(1.1);
    transition: transform 0.2s ease;
}

/* Active state enhancement */
.menu-item.active > .menu-link {
    background-color: rgba(105, 108, 255, 0.12) !important;
    color: #696cff !important;
}

.menu-item.active > .menu-link .menu-icon {
    color: #696cff !important;
}

/* Sub-menu active state */
.menu-sub .menu-item.active > .menu-link {
    background-color: rgba(105, 108, 255, 0.08) !important;
    color: #696cff !important;
    font-weight: 600;
}

/* Responsive adjustments */
@media (max-width: 1199.98px) {
    .layout-menu {
        transform: translateX(-100%);
    }
    
    .layout-menu.show {
        transform: translateX(0);
    }
}

/* Smooth transitions */
.menu-item {
    transition: all 0.2s ease;
}

.menu-link {
    transition: all 0.2s ease;
}

/* Badge animation */
.badge {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}
</style>
    
<!-- JavaScript for enhanced functionality -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-expand current menu
        const activeMenuItem = document.querySelector('.menu-item.active.open');
        if (activeMenuItem) {
            const submenu = activeMenuItem.querySelector('.menu-sub');
            if (submenu) {
                submenu.style.display = 'block';
            }
        }

        // Add click tracking for analytics (optional)
        const menuLinks = document.querySelectorAll('.menu-link');
        menuLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const menuText = this.querySelector('div')?.textContent;
                if (menuText && typeof gtag !== 'undefined') {
                    gtag('event', 'menu_click', {
                        'event_category': 'Navigation',
                        'event_label': menuText
                    });
                }
            });
        });

        // Badge animation on hover
        const badges = document.querySelectorAll('.badge');
        badges.forEach(badge => {
            badge.addEventListener('mouseenter', function() {
                this.style.animationDuration = '0.5s';
            });

            badge.addEventListener('mouseleave', function() {
                this.style.animationDuration = '2s';
            });
        });
    });
</script>