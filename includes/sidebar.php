<?php
// includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// ตรวจสอบสิทธิ์ผู้ใช้
$user_id = $_SESSION['user_id'] ?? 0;
$isAdmin = false;

if ($user_id > 0 && isset($conn)) {
    $user_sql = "SELECT role FROM users WHERE id = ?";
    $stmt = $conn->prepare($user_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    
    if ($user_result->num_rows > 0) {
        $user_data = $user_result->fetch_assoc();
        $isAdmin = ($user_data['role'] == 'admin');
    }
}
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
        <!-- 1. Dashboard -->
        <li class="menu-item <?php echo ($current_page == 'index' && $current_dir == 'dashboard') ? 'active' : ''; ?>">
            <a href="<?php echo isset($base_url) ? $base_url : '../dashboard/'; ?>index.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div>Dashboard</div>
            </a>
        </li>

        <!-- 2. Patients (Available for ALL logged-in users) -->
        <li class="menu-item <?php echo ($current_dir == 'patients' || in_array($current_page, ['patients', 'patient_view', 'patients_action'])) ? 'active open' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-group"></i>
                <div>Patients</div>
                <?php
                // Get patient count for badge
                if (isset($conn)) {
                    $patient_count = $conn->query("SELECT COUNT(*) as count FROM patients")->fetch_assoc()['count'] ?? 0;
                    if ($patient_count > 0) {
                        echo '<div class="badge badge-center rounded-pill bg-primary ms-auto">' . $patient_count . '</div>';
                    }
                }
                ?>
                <?php if (!$isAdmin): ?>
                    <small class="text-success ms-1" title="User Access">👤</small>
                <?php else: ?>
                    <small class="text-warning ms-1" title="Admin Access">👑</small>
                <?php endif; ?>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo (in_array($current_page, ['patients', 'patient_view'])) ? 'active' : ''; ?>">
                    <a href="<?php echo isset($patients_url) ? $patients_url : '../patients/'; ?>patients.php" class="menu-link">
                        <div>All Patients</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'patients_action' && (isset($_GET['action']) && $_GET['action'] == 'add')) ? 'active' : ''; ?>">
                    <a href="<?php echo isset($patients_url) ? $patients_url : '../patients/'; ?>patients_action.php?action=add" class="menu-link">
                        <div>Add New Patient</div>
                    </a>
                </li>
                <?php if (!$isAdmin): ?>
                
                <?php endif; ?>
            </ul>
        </li>

        <!-- 3. Medical Records (Available for all users) -->
        <li class="menu-item <?php echo ($current_dir == 'medical_records' || in_array($current_page, ['medical_records', 'medical_records_action', 'medical_record_view'])) ? 'active open' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-file-blank"></i>
                <div>Medical Records</div>
                <?php
                // Get medical records count for badge
                if (isset($conn)) {
                    $records_count = $conn->query("SELECT COUNT(*) as count FROM medical_records")->fetch_assoc()['count'] ?? 0;
                    if ($records_count > 0) {
                        echo '<div class="badge badge-center rounded-pill bg-info ms-auto">' . $records_count . '</div>';
                    }
                }
                ?>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo (in_array($current_page, ['medical_records_action', 'medical_record_view']) && (!isset($_GET['action']) || $_GET['action'] == 'list' || $_GET['action'] == 'view')) ? 'active' : ''; ?>">
                    <a href="<?php echo isset($medical_records_url) ? $medical_records_url : '../medical_records/'; ?>medical_records_action.php" class="menu-link">
                        <div>All Records</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'medical_records_action' && (isset($_GET['action']) && $_GET['action'] == 'add')) ? 'active' : ''; ?>">
                    <a href="<?php echo isset($medical_records_url) ? $medical_records_url : '../medical_records/'; ?>medical_records_action.php?action=add" class="menu-link">
                        <div>New Record</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- 4. Appointments (Available for all users) -->
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

        <!-- Divider for Admin-only sections -->
        <?php if ($isAdmin): ?>
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Administration</span>
        </li>

        <!-- 5. Doctors (Admin Only) -->
        <li class="menu-item <?php echo ($current_dir == 'doctors' || in_array($current_page, ['doctors', 'doctor_view', 'doctors_action'])) ? 'active open' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-user-check"></i>
                <div>Doctors</div>
                <small class="text-warning ms-1" title="Admin Only">👑</small>
                <?php
                // Get doctor count for badge
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

        <!-- 6. Nurses (Admin Only) -->
        <li class="menu-item <?php echo ($current_dir == 'nurses' || in_array($current_page, ['nurses_action', 'nurse_view'])) ? 'active open' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-plus-medical"></i>
                <div>Nurses</div>
                <small class="text-warning ms-1" title="Admin Only">👑</small>
                <?php
                // Get nurse count for badge
                if (isset($conn)) {
                    $nurse_count = $conn->query("SELECT COUNT(*) as count FROM nurses WHERE status = 'Active'")->fetch_assoc()['count'] ?? 0;
                    if ($nurse_count > 0) {
                        echo '<div class="badge badge-center rounded-pill bg-success ms-auto">' . $nurse_count . '</div>';
                    }
                }
                ?>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo ($current_page == 'nurses_action' && (!isset($_GET['action']) || $_GET['action'] == 'list')) ? 'active' : ''; ?>">
                    <a href="<?php echo isset($nurses_url) ? $nurses_url : '../nurses/'; ?>nurses_action.php" class="menu-link">
                        <div>All Nurses</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'nurses_action' && (isset($_GET['action']) && $_GET['action'] == 'add')) ? 'active' : ''; ?>">
                    <a href="<?php echo isset($nurses_url) ? $nurses_url : '../nurses/'; ?>nurses_action.php?action=add" class="menu-link">
                        <div>Add Nurse</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- 7. Settings (Admin Only) -->
        <li class="menu-item <?php echo ($current_page == 'settings' || $current_page == 'user-add' || $current_page == 'user-edit') ? 'active' : ''; ?>">
            <a href="<?php echo isset($base_url) ? $base_url : '../dashboard/'; ?>settings.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-cog"></i>
                <div>User Management</div>
                <small class="text-warning ms-1" title="Admin Only">👑</small>
                <?php
                // Get user count for badge
                if (isset($conn)) {
                    $user_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'] ?? 0;
                    if ($user_count > 1) { // More than 1 user
                        echo '<div class="badge badge-center rounded-pill bg-secondary ms-auto">' . $user_count . '</div>';
                    }
                }
                ?>
            </a>
        </li>
        <?php endif; ?>

        <!-- Personal Section -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Personal</span>
        </li>

        <!-- 8. Profile -->
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

        <!-- Account Information -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Account</span>
        </li>

        <!-- 9. Role Display -->
        <li class="menu-item">
            <div class="menu-link disabled">
                <i class="menu-icon tf-icons bx bx-shield-check"></i>
                <div class="d-flex justify-content-between align-items-center w-100">
                    <span>Access Level</span>
                    <span class="badge bg-<?php echo $isAdmin ? 'danger' : 'primary'; ?> rounded-pill">
                        <?php echo $isAdmin ? 'Administrator' : 'User'; ?>
                    </span>
                </div>
            </div>
        </li>

        <!-- Permissions Info -->
        <li class="menu-item">
            <div class="menu-link disabled">
                <div class="w-100">
                    <small class="text-muted">
                        <?php if ($isAdmin): ?>
                            <i class="bx bx-check-circle text-success me-1"></i>Full system access
                        <?php else: ?>
                            <i class="bx bx-user text-primary me-1"></i>Patient management access
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </li>

        <!-- 10. Logout -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link" onclick="confirmLogout()">
                <i class="menu-icon tf-icons bx bx-log-out text-danger"></i>
                <div>Logout</div>
            </a>
        </li>
    </ul>
</aside>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Enhanced CSS for role-based styling -->
<style>
/* Role-based menu styling */
.menu-item .text-success {
    color: #28a745 !important;
    font-size: 0.75rem;
}

.menu-item .text-warning {
    color: #ffc107 !important;
    font-size: 0.75rem;
}

/* Badge styling */
.menu-item .badge {
    font-size: 0.65rem;
    min-width: 1.25rem;
    height: 1.25rem;
    line-height: 1;
}

/* User vs Admin menu distinction */
.user-role .menu-item[data-admin-only] {
    opacity: 0.6;
    pointer-events: none;
}

/* Enhanced tooltips for role indicators */
[title] {
    position: relative;
}

/* Menu header styling */
.menu-header-text {
    color: #a1acb8 !important;
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* Disabled menu items */
.menu-link.disabled {
    pointer-events: none;
    opacity: 0.7;
    cursor: default;
    background: transparent;
}

.menu-link.disabled:hover {
    background: transparent;
}

/* Active state for patients menu */
.menu-item.active.open > .menu-link {
    background-color: rgba(105, 108, 255, 0.12) !important;
    color: #696cff !important;
}

/* Remove highlight for patients menu */

/* Admin-only sections styling */
.admin-role .menu-header-text:contains("Administration") {
    color: #ffc107 !important;
}

/* Responsive adjustments */
@media (max-width: 1199.98px) {
    .layout-menu {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .layout-menu.show {
        transform: translateX(0);
    }
}

/* Animation for role indicators */
@keyframes roleGlow {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.menu-item .text-success,
.menu-item .text-warning {
    animation: roleGlow 3s ease-in-out infinite;
}

/* Enhanced badge styles */
.badge.bg-primary {
    background: linear-gradient(135deg, #696cff, #5a67d8) !important;
}

.badge.bg-danger {
    background: linear-gradient(135deg, #dc3545, #c82333) !important;
}

.badge.bg-warning {
    background: linear-gradient(135deg, #ffc107, #e0a800) !important;
}

.badge.bg-info {
    background: linear-gradient(135deg, #17a2b8, #138496) !important;
}

.badge.bg-success {
    background: linear-gradient(135deg, #28a745, #1e7e34) !important;
}

.badge.bg-secondary {
    background: linear-gradient(135deg, #6c757d, #545b62) !important;
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

    // Role-based UI adjustments
    const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
    
    if (!isAdmin) {
        document.body.classList.add('user-role');
        
        // Add visual highlight to patients menu for users (removed)
        const patientsMenu = document.querySelector('[href*="patients"]')?.closest('.menu-item');
        if (patientsMenu) {
            patientsMenu.setAttribute('title', 'You have full access to patient management');
        }
    } else {
        document.body.classList.add('admin-role');
    }

    // Store role info for other scripts
    window.userRole = {
        isAdmin: isAdmin,
        canManagePatients: true, // All users can now manage patients
        canDeletePatients: isAdmin,
        canManageUsers: isAdmin
    };

    // Badge pulse animation on hover
    const badges = document.querySelectorAll('.badge');
    badges.forEach(badge => {
        badge.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
            this.style.transition = 'transform 0.2s ease';
        });

        badge.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });

    // Welcome message based on role
    console.log(isAdmin ? 
        '👑 Welcome Administrator! You have full system access.' : 
        '👤 Welcome User! You can manage patients and medical records.'
    );
});

// SweetAlert Logout Confirmation
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
        customClass: {
            popup: 'logout-popup',
            title: 'logout-title',
            confirmButton: 'logout-confirm-btn',
            cancelButton: 'logout-cancel-btn'
        },
        backdrop: true,
        allowOutsideClick: false,
        allowEscapeKey: true,
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return new Promise((resolve) => {
                // Simulate logout process
                setTimeout(() => {
                    resolve();
                }, 1000);
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Show success message before redirect
            Swal.fire({
                title: 'Logging Out...',
                text: 'Thank you for using Medical System',
                icon: 'success',
                timer: 1500,
                timerProgressBar: true,
                showConfirmButton: false,
                customClass: {
                    popup: 'logout-success-popup'
                }
            }).then(() => {
                // Redirect to logout
                window.location.href = '<?php echo isset($auth_url) ? $auth_url : "../auth/"; ?>logout.php';
            });
        }
    });
}

// Additional SweetAlert styling
const style = document.createElement('style');
style.textContent = `
    /* Custom SweetAlert styling */
    .logout-popup {
        border-radius: 15px !important;
        padding: 20px !important;
    }
    
    .logout-title {
        color: #dc3545 !important;
        font-weight: 600 !important;
    }
    
    .logout-confirm-btn {
        border-radius: 8px !important;
        font-weight: 500 !important;
        padding: 10px 20px !important;
        transition: all 0.3s ease !important;
    }
    
    .logout-confirm-btn:hover {
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3) !important;
    }
    
    .logout-cancel-btn {
        border-radius: 8px !important;
        font-weight: 500 !important;
        padding: 10px 20px !important;
        transition: all 0.3s ease !important;
    }
    
    .logout-cancel-btn:hover {
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3) !important;
    }
    
    .logout-success-popup {
        border-radius: 15px !important;
    }
    
    .swal2-timer-progress-bar {
        background: linear-gradient(135deg, #28a745, #20c997) !important;
    }
    
    /* Animation for logout icon */
    @keyframes rotateOut {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .logout-confirm-btn:hover .bx-log-out {
        animation: rotateOut 0.5s ease-in-out;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .logout-popup {
            margin: 20px !important;
            width: calc(100% - 40px) !important;
        }
    }
`;
document.head.appendChild(style);
</script>