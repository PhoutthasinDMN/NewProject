<!-- Content ends here -->
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
<script src="<?php echo isset($assets_path) ? $assets_path : '../assets/'; ?>vendor/libs/jquery/jquery.js"></script>
<script src="<?php echo isset($assets_path) ? $assets_path : '../assets/'; ?>vendor/libs/popper/popper.js"></script>
<script src="<?php echo isset($assets_path) ? $assets_path : '../assets/'; ?>vendor/js/bootstrap.js"></script>
<script src="<?php echo isset($assets_path) ? $assets_path : '../assets/'; ?>vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="<?php echo isset($assets_path) ? $assets_path : '../assets/'; ?>vendor/js/menu.js"></script>
<script src="<?php echo isset($assets_path) ? $assets_path : '../assets/'; ?>js/main.js"></script>

<?php if (isset($extra_js)): ?>
    <?php foreach ($extra_js as $js): ?>
        <script src="<?php echo $js; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (isset($custom_js)): ?>
    <script>
        <?php echo $custom_js; ?>
    </script>
<?php endif; ?>

<script>
    // Fixed Toggle System - วางก่อน </body> tag
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔧 Initializing FIXED Toggle System...');

        // ปิดการใช้งานระบบเดิมที่ขัดแย้ง
        if (window.Helpers && window.Helpers.toggleCollapsed) {
            window.Helpers.toggleCollapsed = function() {
                console.log('⚠️ Original toggle disabled');
                return false;
            };
        }

        // ระบบ Toggle ใหม่
        const FixedToggleSystem = {
            state: {
                isCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
                isMobileOpen: false,
                isMobile: window.innerWidth < 1200
            },

            elements: {
                sidebar: document.getElementById('layout-menu'),
                layoutPage: document.querySelector('.layout-page')
            },

            init() {
                this.updateDeviceState();
                this.applyCurrentState();
                this.bindEvents();
                console.log('✅ Toggle system ready!');
            },

            updateDeviceState() {
                this.state.isMobile = window.innerWidth < 1200;
            },

            applyCurrentState() {
                if (this.state.isMobile) {
                    this.resetToMobileMode();
                } else {
                    this.applyDesktopState();
                }
            },

            resetToMobileMode() {
                this.elements.sidebar?.classList.remove('collapsed');
                this.elements.layoutPage?.classList.remove('sidebar-collapsed');
                if (this.elements.layoutPage) {
                    this.elements.layoutPage.style.marginLeft = '0px';
                    this.elements.layoutPage.style.width = '100%';
                }
            },

            applyDesktopState() {
                if (this.state.isCollapsed) {
                    this.elements.sidebar?.classList.add('collapsed');
                    this.elements.layoutPage?.classList.add('sidebar-collapsed');
                    if (this.elements.layoutPage) {
                        this.elements.layoutPage.style.marginLeft = '80px';
                        this.elements.layoutPage.style.width = 'calc(100% - 80px)';
                    }
                } else {
                    this.elements.sidebar?.classList.remove('collapsed');
                    this.elements.layoutPage?.classList.remove('sidebar-collapsed');
                    if (this.elements.layoutPage) {
                        this.elements.layoutPage.style.marginLeft = '260px';
                        this.elements.layoutPage.style.width = 'calc(100% - 260px)';
                    }
                }
                this.updateToggleIcons();
            },

            updateToggleIcons() {
                const icons = document.querySelectorAll('.layout-menu-toggle i');
                icons.forEach(icon => {
                    icon.style.transform = this.state.isCollapsed ? 'rotate(180deg)' : 'rotate(0deg)';
                });
            },

            toggle() {
                if (this.state.isMobile) {
                    this.toggleMobile();
                } else {
                    this.toggleDesktop();
                }
            },

            toggleMobile() {
                this.state.isMobileOpen = !this.state.isMobileOpen;
                if (this.state.isMobileOpen) {
                    this.elements.sidebar?.classList.add('show');
                    document.body.style.overflow = 'hidden';
                } else {
                    this.elements.sidebar?.classList.remove('show');
                    document.body.style.overflow = '';
                }
            },

            toggleDesktop() {
                this.state.isCollapsed = !this.state.isCollapsed;
                this.applyDesktopState();
                localStorage.setItem('sidebarCollapsed', this.state.isCollapsed.toString());
            },

            bindEvents() {
                // Bind to existing toggle buttons
                const toggles = document.querySelectorAll('.layout-menu-toggle');
                toggles.forEach(toggle => {
                    toggle.onclick = (e) => {
                        e.preventDefault();
                        this.toggle();
                    };
                });

                // Window resize
                window.addEventListener('resize', () => {
                    const wasMobile = this.state.isMobile;
                    this.updateDeviceState();
                    if (wasMobile !== this.state.isMobile) {
                        this.applyCurrentState();
                    }
                });
            }
        };

        // เริ่มต้นระบบ
        setTimeout(() => FixedToggleSystem.init(), 100);

        // Global functions
        window.toggleSidebar = () => FixedToggleSystem.toggle();
        window.toggleSidebarDesktop = () => FixedToggleSystem.toggle();
    });
</script>

<!-- CSS เพิ่มเติม -->
<style>
    .layout-page {
        transition: margin-left 0.3s ease, width 0.3s ease !important;
    }

    .layout-menu.collapsed {
        width: 80px !important;
    }

    .layout-menu.collapsed .app-brand-text,
    .layout-menu.collapsed .menu-text,
    .layout-menu.collapsed .badge {
        display: none !important;
    }

    .layout-page.sidebar-collapsed {
        margin-left: 80px !important;
        width: calc(100% - 80px) !important;
    }

    @media (max-width: 1199.98px) {

        .layout-page,
        .layout-page.sidebar-collapsed {
            margin-left: 0 !important;
            width: 100% !important;
        }
    }
</style>

</body>

</html>