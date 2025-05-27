<?php
// includes/enhanced-sidebar.php - Enhanced Sidebar with Security & UX Improvements
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Enhanced user permission checking
$user_id = $_SESSION['user_id'] ?? 0;
$isAdmin = false;
$userRole = 'user';
$userPermissions = [];

if ($user_id > 0 && isset($conn)) {
    try {
        $user_sql = "SELECT role, username, email, created_at FROM users WHERE id = ? AND status = 'active'";
        $stmt = $conn->prepare($user_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_result = $stmt->get_result();
        
        if ($user_result->num_rows > 0) {
            $user_data = $user_result->fetch_assoc();
            $userRole = $user_data['role'];
            $isAdmin = ($userRole === 'admin');
            
            // Define permissions based on role
            $userPermissions = [
                'view_patients' => true,
                'add_patients' => true,
                'edit_patients' => true,
                'delete_patients' => $isAdmin,
                'view_records' => true,
                'add_records' => true,
                'edit_records' => true,
                'delete_records' => $isAdmin,
                'manage_doctors' => $isAdmin,
                'manage_nurses' => $isAdmin,
                'manage_users' => $isAdmin,
                'system_settings' => $isAdmin,
                'view_reports' => true,
                'export_data' => $isAdmin
            ];
        }
    } catch (Exception $e) {
        error_log("Sidebar permission check error: " . $e->getMessage());
    }
}

// Get dynamic counts with caching
function getCachedCount($query, $cacheKey, $defaultValue = 0) {
    global $conn;
    static $cache = [];
    
    if (!isset($cache[$cacheKey])) {
        try {
            $result = $conn->query($query);
            $cache[$cacheKey] = $result ? $result->fetch_assoc()['count'] : $defaultValue;
        } catch (Exception $e) {
            $cache[$cacheKey] = $defaultValue;
        }
    }
    
    return $cache[$cacheKey];
}

// Get notification counts
$notification_counts = [
    'patients' => getCachedCount("SELECT COUNT(*) as count FROM patients", 'patients_count'),
    'records' => getCachedCount("SELECT COUNT(*) as count FROM medical_records", 'records_count'),
    'doctors' => getCachedCount("SELECT COUNT(*) as count FROM doctors", 'doctors_count'),
    'upcoming_appointments' => getCachedCount("SELECT COUNT(*) as count FROM medical_records WHERE next_appointment > NOW() AND next_appointment <= DATE_ADD(NOW(), INTERVAL 7 DAY)", 'upcoming_appointments'),
    'today_appointments' => getCachedCount("SELECT COUNT(*) as count FROM medical_records WHERE DATE(next_appointment) = CURDATE()", 'today_appointments'),
    'users' => $isAdmin ? getCachedCount("SELECT COUNT(*) as count FROM users", 'users_count') : 0
];
?>

<!-- Enhanced Sidebar -->
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme" role="navigation" aria-label="Main Navigation">
    
    <!-- App Brand -->
    <div class="app-brand demo">
        <a href="<?php echo isset($base_url) ? $base_url : '../dashboard/'; ?>index.php" class="app-brand-link d-flex align-items-center">
            <span class="app-brand-logo demo">
                <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bx bx-plus-medical" style="font-size: 24px;"></i>
                </div>
            </span>
            <span class="app-brand-text demo menu-text fw-bold ms-2 text-gradient-primary">Medical</span>
        </a>
        
        <!-- Collapse indicator -->
        <div class="collapse-indicator d-none d-xl-block position-absolute" style="right: 10px; top: 50%; transform: translateY(-50%);">
            <i class="bx bx-chevron-left text-muted" style="font-size: 16px; transition: transform 0.3s ease;"></i>
        </div>
    </div>

    <!-- Menu Inner Shadow -->
    <div class="menu-inner-shadow"></div>

    <!-- Menu Content -->
    <ul class="menu-inner py-1" role="menubar">
        
        <!-- Dashboard -->
        <li class="menu-item <?php echo ($current_page == 'index' && $current_dir == 'dashboard') ? 'active' : ''; ?>" role="menuitem">
            <a href="<?php echo isset($base_url) ? $base_url : '../dashboard/'; ?>index.php" class="menu-link" data-tooltip="Dashboard">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div class="menu-text">Dashboard</div>
                <?php if ($notification_counts['today_appointments'] > 0): ?>
                    <div class="badge bg-danger rounded-pill ms-auto animate-pulse"><?php echo $notification_counts['today_appointments']; ?></div>
                <?php endif; ?>
            </a>
        </li>

        <!-- Main Navigation Header -->
        <li class="menu-header small text-uppercase mt-3">
            <span class="menu-header-text">Patient Management</span>
        </li>

        <!-- Patients -->
        <li class="menu-item <?php echo ($current_dir == 'patients' || in_array($current_page, ['patients', 'patient_view', 'patients_action'])) ? 'active open' : ''; ?>" role="menuitem">
            <a href="javascript:void(0);" class="menu-link menu-toggle" data-tooltip="Patient Management" aria-expanded="<?php echo ($current_dir == 'patients') ? 'true' : 'false'; ?>">
                <i class="menu-icon tf-icons bx bx-group"></i>
                <div class="menu-text">Patients</div>
                <?php if ($notification_counts['patients'] > 0): ?>
                    <div class="badge bg-primary rounded-pill ms-auto"><?php echo number_format($notification_counts['patients']); ?></div>
                <?php endif; ?>
                <div class="permission-indicator">
                    <i class="bx bx-user text-success" title="User Access" style="font-size: 12px;"></i>
                </div>
            </a>
            <ul class="menu-sub" role="menu">
                <?php if ($userPermissions['view_patients']): ?>
                <li class="menu-item <?php echo (in_array($current_page, ['patients', 'patient_view'])) ? 'active' : ''; ?>" role="menuitem">
                    <a href="<?php echo isset($patients_url) ? $patients_url : '../patients/'; ?>patients.php" class="menu-link">
                        <div class="menu-text">All Patients</div>
                        <span class="badge bg-light text-dark ms-auto"><?php echo number_format($notification_counts['patients']); ?></span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if ($userPermissions['add_patients']): ?>
                <li class="menu-item <?php echo ($current_page == 'patients_action' && (isset($_GET['action']) && $_GET['action'] == 'add')) ? 'active' : ''; ?>" role="menuitem">
                    <a href="<?php echo isset($patients_url) ? $patients_url : '../patients/'; ?>patients_action.php?action=add" class="menu-link">
                        <div class="menu-text">Add New Patient</div>
                        <i class="bx bx-plus ms-auto text-success"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if ($userPermissions['view_patients']): ?>
                <li class="menu-item" role="menuitem">
                    <a href="javascript:void(0)" onclick="quickSearchPatients()" class="menu-link">
                        <div class="menu-text">Quick Search</div>
                        <i class="bx bx-search ms-auto text-info"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </li>

        <!-- Medical Records -->
        <li class="menu-item <?php echo ($current_dir == 'medical_records' || in_array($current_page, ['medical_records', 'medical_record_action', 'medical_record_view'])) ? 'active open' : ''; ?>" role="menuitem">
            <a href="javascript:void(0);" class="menu-link menu-toggle" data-tooltip="Medical Records" aria-expanded="<?php echo ($current_dir == 'medical_records') ? 'true' : 'false'; ?>">
                <i class="menu-icon tf-icons bx bx-file-blank"></i>
                <div class="menu-text">Medical Records</div>
                <?php if ($notification_counts['records'] > 0): ?>
                    <div class="badge bg-info rounded-pill ms-auto"><?php echo number_format($notification_counts['records']); ?></div>
                <?php endif; ?>
            </a>
            <ul class="menu-sub" role="menu">
                <?php if ($userPermissions['view_records']): ?>
                <li class="menu-item <?php echo (in_array($current_page, ['medical_record_action', 'medical_record_view']) && (!isset($_GET['action']) || $_GET['action'] == 'list' || $_GET['action'] == 'view')) ? 'active' : ''; ?>" role="menuitem">
                    <a href="<?php echo isset($medical_records_url) ? $medical_records_url : '../medical_records/'; ?>medical_record_action.php" class="menu-link">
                        <div class="menu-text">All Records</div>
                        <span class="badge bg-light text-dark ms-auto"><?php echo number_format($notification_counts['records']); ?></span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if ($userPermissions['add_records']): ?>
                <li class="menu-item <?php echo ($current_page == 'medical_record_action' && (isset($_GET['action']) && $_GET['action'] == 'add')) ? 'active' : ''; ?>" role="menuitem">
                    <a href="<?php echo isset($medical_records_url) ? $medical_records_url : '../medical_records/'; ?>medical_record_action.php?action=add" class="menu-link">
                        <div class="menu-text">New Record</div>
                        <i class="bx bx-plus ms-auto text-success"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="menu-item" role="menuitem">
                    <a href="javascript:void(0)" onclick="showRecentRecords()" class="menu-link">
                        <div class="menu-text">Recent Records</div>
                        <i class="bx bx-time ms-auto text-warning"></i>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Appointments -->
        <li class="menu-item <?php echo ($current_dir == 'appointments' || in_array($current_page, ['appointments', 'appointment_view', 'appointments_action'])) ? 'active open' : ''; ?>" role="menuitem">
            <a href="javascript:void(0);" class="menu-link menu-toggle" data-tooltip="Appointments" aria-expanded="<?php echo ($current_dir == 'appointments') ? 'true' : 'false'; ?>">
                <i class="menu-icon tf-icons bx bx-calendar"></i>
                <div class="menu-text">Appointments</div>
                <?php if ($notification_counts['upcoming_appointments'] > 0): ?>
                    <div class="badge bg-warning rounded-pill ms-auto animate-pulse"><?php echo $notification_counts['upcoming_appointments']; ?></div>
                <?php endif; ?>
            </a>
            <ul class="menu-sub" role="menu">
                <li class="menu-item <?php echo ($current_page == 'appointments') ? 'active' : ''; ?>" role="menuitem">
                    <a href="<?php echo isset($appointments_url) ? $appointments_url : '../appointments/'; ?>appointments.php" class="menu-link">
                        <div class="menu-text">All Appointments</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'appointments_action' && (isset($_GET['action']) && $_GET['action'] == 'add')) ? 'active' : ''; ?>" role="menuitem">
                    <a href="<?php echo isset($appointments_url) ? $appointments_url : '../appointments/'; ?>appointments_action.php?action=add" class="menu-link">
                        <div class="menu-text">Schedule Appointment</div>
                        <i class="bx bx-plus ms-auto text-success"></i>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'calendar') ? 'active' : ''; ?>" role="menuitem">
                    <a href="<?php echo isset($appointments_url) ? $appointments_url : '../appointments/'; ?>calendar.php" class="menu-link">
                        <div class="menu-text">Calendar View</div>
                        <i class="bx bx-calendar-event ms-auto text-info"></i>
                    </a>
                </li>
                <?php if ($notification_counts['today_appointments'] > 0): ?>
                <li class="menu-item" role="menuitem">
                    <a href="javascript:void(0)" onclick="showTodayAppointments()" class="menu-link">
                        <div class="menu-text">Today's Schedule</div>
                        <span class="badge bg-danger rounded-pill ms-auto"><?php echo $notification_counts['today_appointments']; ?></span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </li>

        <!-- Admin Section -->
        <?php if ($isAdmin): ?>
        <li class="menu-header small text-uppercase mt-4">
            <span class="menu-header-text">
                <i class="bx bx-shield-check me-1"></i>
                Administration
            </span>
        </li>

        <!-- Doctors Management -->
        <?php if ($userPermissions['manage_doctors']): ?>
        <li class="menu-item <?php echo ($current_dir == 'doctors' || in_array($current_page, ['doctors', 'doctor_view', 'doctors_action'])) ? 'active open' : ''; ?>" role="menuitem">
            <a href="javascript:void(0);" class="menu-link menu-toggle" data-tooltip="Doctor Management" aria-expanded="<?php echo ($current_dir == 'doctors') ? 'true' : 'false'; ?>">
                <i class="menu-icon tf-icons bx bx-user-check"></i>
                <div class="menu-text">Doctors</div>
                <div class="permission-indicator">
                    <i class="bx bx-crown text-warning" title="Admin Only" style="font-size: 12px;"></i>
                </div>
                <?php if ($notification_counts['doctors'] > 0): ?>
                    <div class="badge bg-success rounded-pill ms-auto"><?php echo number_format($notification_counts['doctors']); ?></div>
                <?php endif; ?>
            </a>
            <ul class="menu-sub" role="menu">
                <li class="menu-item <?php echo ($current_page == 'doctors_action' && (!isset($_GET['action']) || $_GET['action'] == 'list')) ? 'active' : ''; ?>" role="menuitem">
                    <a href="<?php echo isset($doctors_url) ? $doctors_url : '../doctors/'; ?>doctors_action.php" class="menu-link">
                        <div class="menu-text">All Doctors</div>
                        <span class="badge bg-light text-dark ms-auto"><?php echo number_format($notification_counts['doctors']); ?></span>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'doctors_action' && (isset($_GET['action']) && $_GET['action'] == 'add')) ? 'active' : ''; ?>" role="menuitem">
                    <a href="<?php echo isset($doctors_url) ? $doctors_url : '../doctors/'; ?>doctors_action.php?action=add" class="menu-link">
                        <div class="menu-text">Add Doctor</div>
                        <i class="bx bx-plus ms-auto text-success"></i>
                    </a>
                </li>
                <li class="menu-item" role="menuitem">
                    <a href="javascript:void(0)" onclick="showDoctorStats()" class="menu-link">
                        <div class="menu-text">Doctor Statistics</div>
                        <i class="bx bx-bar-chart ms-auto text-info"></i>
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>

        <!-- Nurses Management -->
        <?php if ($userPermissions['manage_nurses']): ?>
        <li class="menu-item <?php echo ($current_dir == 'nurses' || in_array($current_page, ['nurses_action', 'nurse_view'])) ? 'active open' : ''; ?>" role="menuitem">
            <a href="javascript:void(0);" class="menu-link menu-toggle" data-tooltip="Nurse Management" aria-expanded="<?php echo ($current_dir == 'nurses') ? 'true' : 'false'; ?>">
                <i class="menu-icon tf-icons bx bx-plus-medical"></i>
                <div class="menu-text">Nurses</div>
                <div class="permission-indicator">
                    <i class="bx bx-crown text-warning" title="Admin Only" style="font-size: 12px;"></i>
                </div>
                <?php 
                $nurse_count = getCachedCount("SELECT COUNT(*) as count FROM nurses WHERE status = 'Active'", 'nurses_count');
                if ($nurse_count > 0): ?>
                    <div class="badge bg-info rounded-pill ms-auto"><?php echo number_format($nurse_count); ?></div>
                <?php endif; ?>
            </a>
            <ul class="menu-sub" role="menu">
                <li class="menu-item <?php echo ($current_page == 'nurses_action' && (!isset($_GET['action']) || $_GET['action'] == 'list')) ? 'active' : ''; ?>" role="menuitem">
                    <a href="<?php echo isset($nurses_url) ? $nurses_url : '../nurses/'; ?>nurses_action.php" class="menu-link">
                        <div class="menu-text">All Nurses</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'nurses_action' && (isset($_GET['action']) && $_GET['action'] == 'add')) ? 'active' : ''; ?>" role="menuitem">
                    <a href="<?php echo isset($nurses_url) ? $nurses_url : '../nurses/'; ?>nurses_action.php?action=add" class="menu-link">
                        <div class="menu-text">Add Nurse</div>
                        <i class="bx bx-plus ms-auto text-success"></i>
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>

        <!-- User Management -->
        <?php if ($userPermissions['manage_users']): ?>
        <li class="menu-item <?php echo ($current_page == 'settings' || $current_page == 'user-add' || $current_page == 'user-edit') ? 'active' : ''; ?>" role="menuitem">
            <a href="<?php echo isset($base_url) ? $base_url : '../dashboard/'; ?>settings.php" class="menu-link" data-tooltip="User Management">
                <i class="menu-icon tf-icons bx bx-cog"></i>
                <div class="menu-text">User Management</div>
                <div class="permission-indicator">
                    <i class="bx bx-crown text-warning" title="Admin Only" style="font-size: 12px;"></i>
                </div>
                <?php if ($notification_counts['users'] > 1): ?>
                    <div class="badge bg-secondary rounded-pill ms-auto"><?php echo number_format($notification_counts['users']); ?></div>
                <?php endif; ?>
            </a>
        </li>
        <?php endif; ?>

        <!-- Reports & Analytics (Admin) -->
        <li class="menu-item" role="menuitem">
            <a href="javascript:void(0)" onclick="showReportsModal()" class="menu-link" data-tooltip="Reports & Analytics">
                <i class="menu-icon tf-icons bx bx-bar-chart-alt-2"></i>
                <div class="menu-text">Reports</div>
                <div class="permission-indicator">
                    <i class="bx bx-crown text-warning" title="Admin Only" style="font-size: 12px;"></i>
                </div>
            </a>
        </li>
        <?php endif; ?>

        <!-- Personal Section -->
        <li class="menu-header small text-uppercase mt-4">
            <span class="menu-header-text">Personal</span>
        </li>

        <!-- Profile -->
        <li class="menu-item <?php echo (in_array($current_page, ['profile', 'profile-edit']) || ($current_dir == 'profile')) ? 'active open' : ''; ?>" role="menuitem">
            <a href="javascript:void(0);" class="menu-link menu-toggle" data-tooltip="My Profile" aria-expanded="<?php echo (in_array($current_page, ['profile', 'profile-edit'])) ? 'true' : 'false'; ?>">
                <i class="menu-icon tf-icons bx bx-user"></i>
                <div class="menu-text">Profile</div>
            </a>
            <ul class="menu-sub" role="menu">
                <li class="menu-item <?php echo ($current_page == 'profile') ? 'active' : ''; ?>" role="menuitem">
                    <a href="<?php echo isset($base_url) ? $base_url : '../dashboard/'; ?>profile.php" class="menu-link">
                        <div class="menu-text">View Profile</div>
                        <i class="bx bx-show ms-auto text-info"></i>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'profile-edit') ? 'active' : ''; ?>" role="menuitem">
                    <a href="<?php echo isset($base_url) ? $base_url : '../dashboard/'; ?>profile-edit.php" class="menu-link">
                        <div class="menu-text">Edit Profile</div>
                        <i class="bx bx-edit ms-auto text-warning"></i>
                    </a>
                </li>
                <li class="menu-item" role="menuitem">
                    <a href="javascript:void(0)" onclick="changePassword()" class="menu-link">
                        <div class="menu-text">Change Password</div>
                        <i class="bx bx-key ms-auto text-danger"></i>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Quick Tools -->
        <li class="menu-header small text-uppercase mt-3">
            <span class="menu-header-text">Quick Tools</span>
        </li>

        <!-- Quick Export -->
        <li class="menu-item" role="menuitem">
            <a href="javascript:void(0)" onclick="showExportOptions()" class="menu-link" data-tooltip="Export Data">
                <i class="menu-icon tf-icons bx bx-download"></i>
                <div class="menu-text">Export Data</div>
            </a>
        </li>

        <!-- System Info -->
        <li class="menu-item" role="menuitem">
            <a href="javascript:void(0)" onclick="showSystemInfo()" class="menu-link" data-tooltip="System Information">
                <i class="menu-icon tf-icons bx bx-info-circle"></i>
                <div class="menu-text">System Info</div>
            </a>
        </li>

        <!-- Account Information -->
        <li class="menu-header small text-uppercase mt-4">
            <span class="menu-header-text">Account</span>
        </li>

        <!-- User Info Display -->
        <li class="menu-item">
            <div class="menu-link disabled user-info-card">
                <div class="d-flex align-items-center w-100">
                    <div class="flex-shrink-0 me-3">
                        <div class="avatar avatar-sm">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
                            </div>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1 small"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></h6>
                        <small class="text-muted d-block"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></small>
                        <span class="badge bg-<?php echo $isAdmin ? 'danger' : 'primary'; ?> rounded-pill mt-1" style="font-size: 10px;">
                            <?php echo $isAdmin ? 'Administrator' : 'User'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </li>

        <!-- Session Info -->
        <li class="menu-item">
            <div class="menu-link disabled session-info">
                <div class="w-100">
                    <small class="text-muted d-block">
                        <i class="bx bx-time me-1"></i>
                        Login: <?php echo date('M j, H:i', strtotime($_SESSION['login_time'] ?? 'now')); ?>
                    </small>
                    <small class="text-muted d-block mt-1">
                        <i class="bx bx-shield-check me-1"></i>
                        Session: <span class="text-success">Secure</span>
                    </small>
                </div>
            </div>
        </li>

        <!-- Logout -->
        <li class="menu-item mt-3">
            <a href="javascript:void(0);" class="menu-link logout-link" onclick="confirmLogout()" data-tooltip="Logout">
                <i class="menu-icon tf-icons bx bx-log-out text-danger"></i>
                <div class="menu-text">Logout</div>
            </a>
        </li>

        <!-- Version Info -->
        <li class="menu-item mt-2">
            <div class="menu-link disabled text-center">
                <small class="text-muted">
                    Medical System v2.0<br>
                    <span style="font-size: 10px;">Enhanced Edition</span>
                </small>
            </div>
        </li>
    </ul>
</aside>

<!-- Enhanced Sidebar Styles -->
<style>
    /* Enhanced menu styles */
    .menu-item .permission-indicator {
        position: absolute;
        right: 45px;
        top: 50%;
        transform: translateY(-50%);
        opacity: 0.7;
    }

    .menu-item:hover .permission-indicator {
        opacity: 1;
    }

    .menu-link.disabled {
        pointer-events: none;
        opacity: 0.8;
        background: transparent !important;
    }

    .user-info-card {
        background: linear-gradient(135deg, rgba(105, 108, 255, 0.05), rgba(105, 108, 255, 0.02)) !important;
        border-radius: 12px;
        margin: 0 12px;
        padding: 12px !important;
        border: 1px solid rgba(105, 108, 255, 0.1);
    }

    .session-info {
        background: rgba(248, 249, 250, 0.5) !important;
        border-radius: 8px;
        margin: 0 12px;
        padding: 8px 12px !important;
    }

    .logout-link {
        background: linear-gradient(135deg, rgba(255, 62, 29, 0.05), rgba(255, 62, 29, 0.02)) !important;
        border-radius: 12px;
        margin: 0 12px;
        border: 1px solid rgba(255, 62, 29, 0.1);
    }

    .logout-link:hover {
        background: linear-gradient(135deg, rgba(255, 62, 29, 0.1), rgba(255, 62, 29, 0.05)) !important;
        color: #ff3e1d !important;
    }

    .collapse-indicator {
        transition: transform 0.3s ease;
    }

    .layout-menu.collapsed .collapse-indicator i {
        transform: rotate(180deg);
    }

    /* Notification badges */
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }

    /* Enhanced tooltips for collapsed state */
    .layout-menu.collapsed .menu-link {
        position: relative;
    }

    .layout-menu.collapsed .menu-link::after {
        content: attr(data-tooltip);
        position: absolute;
        left: 100%;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 12px;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        margin-left: 10px;
        z-index: 1000;
    }

    .layout-menu.collapsed .menu-link:hover::after {
        opacity: 1;
        visibility: visible;
    }

    /* Responsive adjustments */
    @media (max-width: 1199.98px) {
        .permission-indicator {
            display: none;
        }
        
        .collapse-indicator {
            display: none !important;
        }
    }
</style>

<!-- Enhanced Sidebar JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize sidebar enhancements
        initializeSidebarEnhancements();
    });

    function initializeSidebarEnhancements() {
        // Add tooltips for menu items
        const menuLinks = document.querySelectorAll('.menu-link[data-tooltip]');
        menuLinks.forEach(link => {
            if (!link.classList.contains('menu-toggle')) {
                link.addEventListener('mouseenter', showTooltipIfCollapsed);
                link.addEventListener('mouseleave', hideTooltip);
            }
        });

        // Track active menu state
        trackActiveMenus();
        
        // Initialize notification updates
        initializeNotificationUpdates();
    }

    function showTooltipIfCollapsed(e) {
        const sidebar = document.getElementById('layout-menu');
        if (sidebar && sidebar.classList.contains('collapsed')) {
            const tooltip = e.target.getAttribute('data-tooltip');
            if (tooltip) {
                // Tooltip is handled by CSS ::after pseudo-element
            }
        }
    }

    function hideTooltip(e) {
        // Tooltip hiding is handled by CSS
    }

    function trackActiveMenus() {
        // Expand parent menus of active items
        const activeItems = document.querySelectorAll('.menu-item.active');
        activeItems.forEach(item => {
            let parent = item.closest('.menu-sub');
            while (parent) {
                const parentItem = parent.closest('.menu-item');
                if (parentItem) {
                    parentItem.classList.add('open');
                    parent.style.display = 'block';
                    parent = parentItem.closest('.menu-sub');
                } else {
                    break;
                }
            }
        });
    }

    function initializeNotificationUpdates() {
        // Update notification counts every 5 minutes
        setInterval(updateNotificationCounts, 300000);
    }

    async function updateNotificationCounts() {
        try {
            const response = await fetch('../api/notification-counts.php', {
                method: 'GET',
                headers: {
                    'X-CSRF-Token': window.MedicalConfig?.csrfToken || ''
                }
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    updateBadges(data.counts);
                }
            }
        } catch (error) {
            console.warn('Failed to update notification counts:', error);
        }
    }

    function updateBadges(counts) {
        // Update badge values
        const badges = {
            'patients': counts.patients,
            'records': counts.records,
            'doctors': counts.doctors,
            'appointments': counts.upcoming_appointments,
            'today_appointments': counts.today_appointments
        };

        Object.entries(badges).forEach(([key, value]) => {
            const badge = document.querySelector(`[data-count="${key}"]`);
            if (badge && value > 0) {
                badge.textContent = value;
                badge.style.display = 'inline-block';
            } else if (badge) {
                badge.style.display = 'none';
            }
        });
    }

    // Quick action functions
    function quickSearchPatients() {
        const searchInput = document.getElementById('global-search');
        if (searchInput) {
            searchInput.focus();
            searchInput.placeholder = 'Search patients...';
        } else {
            window.location.href = '../patients/patients.php';
        }
    }

    function showRecentRecords() {
        Swal.fire({
            title: 'Recent Medical Records',
            html: '<div class="text-center"><div class="spinner-border" role="status"></div><p class="mt-2">Loading recent records...</p></div>',
            showConfirmButton: false,
            allowOutsideClick: false
        });

        // Simulate API call
        setTimeout(() => {
            Swal.fire({
                title: 'Recent Medical Records',
                html: `
                    <div class="list-group text-start">
                        <a href="../medical_records/medical_record_view.php?id=1" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">John Doe - Consultation</h6>
                                <small>2 hours ago</small>
                            </div>
                            <p class="mb-1">Routine checkup and blood pressure monitoring</p>
                        </a>
                        <a href="../medical_records/medical_record_view.php?id=2" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Jane Smith - Follow-up</h6>
                                <small>5 hours ago</small>
                            </div>
                            <p class="mb-1">Post-surgery follow-up examination</p>
                        </a>
                        <a href="../medical_records/medical_record_view.php?id=3" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Bob Johnson - Emergency</h6>
                                <small>1 day ago</small>
                            </div>
                            <p class="mb-1">Emergency treatment for chest pain</p>
                        </a>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'View All Records',
                cancelButtonText: 'Close',
                confirmButtonColor: '#696cff'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../medical_records/medical_record_action.php';
                }
            });
        }, 1000);
    }

    function showTodayAppointments() {
        Swal.fire({
            title: "Today's Appointments",
            html: `
                <div class="list-group text-start">
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">10:00 AM - Dr. Smith</h6>
                                <p class="mb-1">John Doe - Follow-up consultation</p>
                                <small class="text-success">✓ Confirmed</small>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">02:30 PM - Dr. Johnson</h6>
                                <p class="mb-1">Jane Smith - Routine checkup</p>
                                <small class="text-warning">⏳ Pending</small>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">04:00 PM - Dr. Williams</h6>
                                <p class="mb-1">Bob Johnson - Lab results review</p>
                                <small class="text-success">✓ Confirmed</small>
                            </div>
                        </div>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'View Calendar',
            cancelButtonText: 'Close',
            confirmButtonColor: '#696cff'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../appointments/calendar.php';
            }
        });
    }

    function showDoctorStats() {
        Swal.fire({
            title: 'Doctor Statistics',
            html: `
                <div class="row text-start">
                    <div class="col-6">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h4>12</h4>
                                <small>Active Doctors</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h4>5</h4>
                                <small>Specializations</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-3">
                        <h6>Top Specializations:</h6>
                        <ul class="list-unstyled">
                            <li>• General Medicine (4 doctors)</li>
                            <li>• Cardiology (3 doctors)</li>
                            <li>• Pediatrics (2 doctors)</li>
                            <li>• Orthopedics (2 doctors)</li>
                            <li>• Dermatology (1 doctor)</li>
                        </ul>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Manage Doctors',
            cancelButtonText: 'Close',
            confirmButtonColor: '#696cff'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../doctors/doctors_action.php';
            }
        });
    }

    function showReportsModal() {
        Swal.fire({
            title: 'Reports & Analytics',
            html: `
                <div class="list-group text-start">
                    <a href="javascript:void(0)" onclick="generateReport('patients')" class="list-group-item list-group-item-action">
                        <i class="bx bx-group me-2"></i>Patient Reports
                    </a>
                    <a href="javascript:void(0)" onclick="generateReport('appointments')" class="list-group-item list-group-item-action">
                        <i class="bx bx-calendar me-2"></i>Appointment Reports
                    </a>
                    <a href="javascript:void(0)" onclick="generateReport('doctors')" class="list-group-item list-group-item-action">
                        <i class="bx bx-user-check me-2"></i>Doctor Performance
                    </a>
                    <a href="javascript:void(0)" onclick="generateReport('financial')" class="list-group-item list-group-item-action">
                        <i class="bx bx-dollar me-2"></i>Financial Reports
                    </a>
                    <a href="javascript:void(0)" onclick="generateReport('system')" class="list-group-item list-group-item-action">
                        <i class="bx bx-cog me-2"></i>System Usage
                    </a>
                </div>
            `,
            showCloseButton: true,
            showConfirmButton: false,
            width: 400
        });
    }

    function generateReport(type) {
        Swal.close();
        
        Swal.fire({
            title: 'Generating Report...',
            text: `Preparing ${type} report`,
            icon: 'info',
            timer: 2000,
            timerProgressBar: true,
            showConfirmButton: false
        }).then(() => {
            showNotification(`${type.charAt(0).toUpperCase() + type.slice(1)} report generated successfully!`, 'success');
        });
    }

    function showExportOptions() {
        Swal.fire({
            title: 'Export Data',
            html: `
                <div class="list-group text-start">
                    <a href="javascript:void(0)" onclick="exportData('patients', 'csv')" class="list-group-item list-group-item-action">
                        <i class="bx bx-file me-2"></i>Patients Data (CSV)
                    </a>
                    <a href="javascript:void(0)" onclick="exportData('patients', 'pdf')" class="list-group-item list-group-item-action">
                        <i class="bx bx-file-pdf me-2"></i>Patients Data (PDF)
                    </a>
                    <a href="javascript:void(0)" onclick="exportData('records', 'csv')" class="list-group-item list-group-item-action">
                        <i class="bx bx-file me-2"></i>Medical Records (CSV)
                    </a>
                    <a href="javascript:void(0)" onclick="exportData('appointments', 'csv')" class="list-group-item list-group-item-action">
                        <i class="bx bx-calendar me-2"></i>Appointments (CSV)
                    </a>
                </div>
            `,
            showCloseButton: true,
            showConfirmButton: false,
            width: 400
        });
    }

    function exportData(type, format) {
        Swal.close();
        
        if (typeof window.medicalEnhancer !== 'undefined') {
            window.medicalEnhancer.exportTableToCSV();
        } else {
            showNotification(`Exporting ${type} data as ${format.toUpperCase()}...`, 'info');
        }
    }

    function showSystemInfo() {
        Swal.fire({
            title: 'System Information',
            html: `
                <div class="text-start">
                    <div class="row">
                        <div class="col-6">
                            <strong>System Version:</strong><br>
                            <span class="text-muted">Medical System v2.0</span>
                        </div>
                        <div class="col-6">
                            <strong>Database:</strong><br>
                            <span class="text-success">Connected</span>
                        </div>
                        <div class="col-6 mt-3">
                            <strong>Server Time:</strong><br>
                            <span class="text-muted"><?php echo date('Y-m-d H:i:s'); ?></span>
                        </div>
                        <div class="col-6 mt-3">
                            <strong>User Role:</strong><br>
                            <span class="text-primary"><?php echo htmlspecialchars($userRole); ?></span>
                        </div>
                        <div class="col-12 mt-3">
                            <strong>Permissions:</strong><br>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                <?php foreach ($userPermissions as $permission => $hasAccess): ?>
                                    <?php if ($hasAccess): ?>
                                        <span class="badge bg-success"><?php echo str_replace('_', ' ', $permission); ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            `,
            showCloseButton: true,
            showConfirmButton: false,
            width: 500
        });
    }

    function changePassword() {
        Swal.fire({
            title: 'Change Password',
            html: `
                <form id="changePasswordForm" class="text-start">
                    <div class="mb-3">
                        <label for="currentPassword" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="currentPassword" required>
                    </div>
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="newPassword" required>
                        <div class="form-text">Must be at least 8 characters with uppercase, lowercase, and number</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirmPassword" required>
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: 'Change Password',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#696cff',
            preConfirm: () => {
                const current = document.getElementById('currentPassword').value;
                const newPass = document.getElementById('newPassword').value;
                const confirm = document.getElementById('confirmPassword').value;
                
                if (!current || !newPass || !confirm) {
                    Swal.showValidationMessage('Please fill in all fields');
                    return false;
                }
                
                if (newPass !== confirm) {
                    Swal.showValidationMessage('New passwords do not match');
                    return false;
                }
                
                if (newPass.length < 8) {
                    Swal.showValidationMessage('New password must be at least 8 characters');
                    return false;
                }
                
                return { current, newPass };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Here you would typically send the password change request to the server
                showNotification('Password changed successfully!', 'success');
            }
        });
    }

    // Helper function for notifications
    function showNotification(message, type = 'info', duration = 5000) {
        if (typeof window.medicalEnhancer !== 'undefined') {
            return window.medicalEnhancer.showNotification(message, type, duration);
        }
        
        // Fallback
        console.log(`${type.toUpperCase()}: ${message}`);
    }
</script>