<?php
// Check for notifications
$hasNotifications = isset($notifications) && count($notifications) > 0;
$notificationCount = $hasNotifications ? count($notifications) : 0;
?>
<!-- Navbar -->
<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="bx bx-menu bx-sm"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        <!-- Notification Section -->
        <ul class="navbar-nav flex-row align-items-center ms-auto">
            <!-- Notification Dropdown -->
            <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-1">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bx bx-bell bx-sm"></i>
                    <?php if ($notificationCount > 0): ?>
                        <span class="badge bg-danger rounded-pill badge-notifications"><?php echo $notificationCount; ?></span>
                    <?php endif; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end py-0">
                    <li class="dropdown-menu-header border-bottom">
                        <div class="dropdown-header d-flex align-items-center py-3">
                            <h5 class="text-body mb-0 me-auto">Notifications</h5>
                            <?php if ($notificationCount > 0): ?>
                                <span class="badge rounded-pill bg-label-primary"><?php echo $notificationCount; ?> New</span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <li class="dropdown-notifications-list scrollable-container">
                        <ul class="list-group list-group-flush">
                            <?php if ($hasNotifications): ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <li class="list-group-item list-group-item-action dropdown-notifications-item">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar">
                                                    <span class="avatar-initial rounded-circle bg-label-<?php echo $notification['type'] ?? 'primary'; ?>">
                                                        <i class="bx <?php echo $notification['icon'] ?? 'bx-bell'; ?>"></i>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                                <p class="mb-0"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                <small class="text-muted"><?php echo htmlspecialchars($notification['time']); ?></small>
                                            </div>
                                            <div class="flex-shrink-0 dropdown-notifications-actions">
                                                <a href="javascript:void(0)" class="dropdown-notifications-read"><span class="badge badge-dot"></span></a>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item">
                                    <div class="text-center py-4">
                                        <i class="bx bx-bell-off bx-lg text-muted"></i>
                                        <p class="mt-2 mb-0">No new notifications</p>
                                    </div>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <li class="dropdown-menu-footer border-top">
                        <a href="<?php echo isset($base_url) ? $base_url : '../dashboard/'; ?>notifications.php" class="dropdown-item d-flex justify-content-center py-2">
                            View all notifications
                        </a>
                    </li>
                </ul>
            </li>
            <!--/ Notification Dropdown -->

            <!-- User Dropdown -->
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online position-relative">
                        <img src="<?php echo isset($assets_url) ? $assets_url : '../assets/'; ?>img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
                        <?php if ($notificationCount > 0): ?>
                            <span class="avatar-status position-absolute bottom-0 end-0 bg-danger rounded-circle border border-white" style="width: 10px; height: 10px;"></span>
                        <?php endif; ?>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="#">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        <img src="<?php echo isset($assets_url) ? $assets_url : '../assets/'; ?>img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="fw-semibold d-block"><?php echo isset($user) ? htmlspecialchars($user['username']) : 'User'; ?></span>
                                    <small class="text-muted"><?php echo isset($user) ? htmlspecialchars($user['email']) : 'user@example.com'; ?></small>
                                    <?php if ($notificationCount > 0): ?>
                                        <span class="badge bg-label-warning mt-1"><?php echo $notificationCount; ?> Notifications</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li><div class="dropdown-divider"></div></li>
                    <li>
                        <a class="dropdown-item" href="<?php echo isset($base_url) ? $base_url : '../dashboard/'; ?>profile.php">
                            <i class="bx bx-user me-2"></i>
                            <span class="align-middle">My Profile</span>
                        </a>
                    </li>
                    <?php if (isset($isAdmin) && $isAdmin): ?>
                    <li>
                        <a class="dropdown-item" href="<?php echo isset($base_url) ? $base_url : '../dashboard/'; ?>settings.php">
                            <i class="bx bx-cog me-2"></i>
                            <span class="align-middle">Settings</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li><div class="dropdown-divider"></div></li>
                    <li>
                        <a class="dropdown-item" href="<?php echo isset($auth_url) ? $auth_url : '../auth/'; ?>logout.php">
                            <i class="bx bx-power-off me-2"></i>
                            <span class="align-middle">Log Out</span>
                        </a>
                    </li>
                </ul>
            </li>
            <!--/ User Dropdown -->
        </ul>
    </div>
</nav>