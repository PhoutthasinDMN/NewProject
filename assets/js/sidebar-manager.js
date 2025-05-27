// assets/js/sidebar-manager.js - Complete Sidebar Fix
class SidebarManager {
    constructor() {
        this.state = {
            isCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
            isMobile: window.innerWidth < 1200,
            isOpen: false,
            isAnimating: false
        };
        
        this.elements = {
            sidebar: null,
            layoutPage: null,
            overlay: null,
            toggleButtons: []
        };
        
        this.init();
    }
    
    init() {
        console.log('🔧 Initializing Enhanced Sidebar Manager...');
        
        // Get elements
        this.elements.sidebar = document.getElementById('layout-menu');
        this.elements.layoutPage = document.querySelector('.layout-page');
        this.elements.overlay = document.querySelector('.layout-overlay');
        this.elements.toggleButtons = document.querySelectorAll('.layout-menu-toggle, .sidebar-toggle');
        
        // Force show sidebar initially
        this.forceShowSidebar();
        
        // Apply initial state
        this.updateDeviceState();
        this.applyState();
        
        // Bind events
        this.bindEvents();
        
        // Auto-expand active menus
        this.expandActiveMenus();
        
        console.log('✅ Sidebar Manager Ready!');
    }
    
    forceShowSidebar() {
        if (this.elements.sidebar) {
            // Remove any conflicting styles
            this.elements.sidebar.style.removeProperty('display');
            this.elements.sidebar.style.removeProperty('visibility');
            this.elements.sidebar.style.removeProperty('opacity');
            
            // Ensure sidebar is visible
            this.elements.sidebar.style.display = 'block';
            this.elements.sidebar.style.visibility = 'visible';
            this.elements.sidebar.style.opacity = '1';
            
            // Remove any hidden classes
            this.elements.sidebar.classList.remove('d-none', 'hidden');
        }
    }
    
    updateDeviceState() {
        const wasMobile = this.state.isMobile;
        this.state.isMobile = window.innerWidth < 1200;
        
        // Reset mobile state when switching to desktop
        if (wasMobile && !this.state.isMobile && this.state.isOpen) {
            this.closeMobile();
        }
    }
    
    applyState() {
        if (this.state.isMobile) {
            this.applyMobileState();
        } else {
            this.applyDesktopState();
        }
    }
    
    applyMobileState() {
        // Reset desktop styles
        this.elements.sidebar?.classList.remove('collapsed');
        this.elements.layoutPage?.classList.remove('sidebar-collapsed');
        
        // Reset layout page margins
        if (this.elements.layoutPage) {
            this.elements.layoutPage.style.marginLeft = '0';
            this.elements.layoutPage.style.width = '100%';
        }
        
        // Apply mobile state
        if (this.state.isOpen) {
            this.elements.sidebar?.classList.add('show');
            this.elements.overlay?.classList.add('show');
            document.body.style.overflow = 'hidden';
        } else {
            this.elements.sidebar?.classList.remove('show');
            this.elements.overlay?.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
    
    applyDesktopState() {
        // Remove mobile classes
        this.elements.sidebar?.classList.remove('show');
        this.elements.overlay?.classList.remove('show');
        document.body.style.overflow = '';
        
        // Apply desktop state
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
    }
    
    updateToggleIcons() {
        const icons = document.querySelectorAll('.sidebar-toggle i, .layout-menu-toggle i');
        icons.forEach(icon => {
            icon.style.transform = this.state.isCollapsed ? 'rotate(180deg)' : 'rotate(0deg)';
            icon.style.transition = 'transform 0.3s ease';
        });
    }
    
    bindEvents() {
        // Toggle button events
        this.elements.toggleButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggle();
            });
        });
        
        // Window resize
        window.addEventListener('resize', () => {
            this.updateDeviceState();
            this.applyState();
        });
        
        // Overlay click (mobile)
        this.elements.overlay?.addEventListener('click', () => {
            if (this.state.isMobile) {
                this.closeMobile();
            }
        });
        
        // Escape key (mobile)
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.state.isMobile && this.state.isOpen) {
                this.closeMobile();
            }
        });
        
        // Menu link clicks on mobile
        const menuLinks = document.querySelectorAll('.menu-link:not(.menu-toggle)');
        menuLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (this.state.isMobile && this.state.isOpen) {
                    setTimeout(() => this.closeMobile(), 200);
                }
            });
        });
        
        // Submenu handling
        this.handleSubmenus();
        
        // Prevent sidebar from being hidden by other scripts
        this.preventSidebarHiding();
    }
    
    handleSubmenus() {
        const menuToggles = document.querySelectorAll('.menu-toggle');
        menuToggles.forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Don't open submenus when collapsed on desktop
                if (!this.state.isMobile && this.state.isCollapsed) {
                    return;
                }
                
                const menuItem = toggle.closest('.menu-item');
                const submenu = menuItem?.querySelector('.menu-sub');
                
                if (submenu) {
                    const isOpen = menuItem.classList.contains('open');
                    
                    // Close other submenus (accordion behavior)
                    const otherOpenItems = document.querySelectorAll('.menu-item.open');
                    otherOpenItems.forEach(item => {
                        if (item !== menuItem && !this.isParentOf(menuItem, item)) {
                            item.classList.remove('open');
                            const otherSubmenu = item.querySelector('.menu-sub');
                            if (otherSubmenu) {
                                otherSubmenu.style.display = 'none';
                            }
                        }
                    });
                    
                    // Toggle current submenu
                    if (isOpen) {
                        menuItem.classList.remove('open');
                        submenu.style.display = 'none';
                    } else {
                        menuItem.classList.add('open');
                        submenu.style.display = 'block';
                    }
                }
            });
        });
    }
    
    isParentOf(parent, child) {
        let node = child.parentNode;
        while (node && node !== document) {
            if (node === parent) {
                return true;
            }
            node = node.parentNode;
        }
        return false;
    }
    
    expandActiveMenus() {
        const activeItems = document.querySelectorAll('.menu-item.active');
        activeItems.forEach(item => {
            // Expand parent menus
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
    
    preventSidebarHiding() {
        // Use MutationObserver to prevent other scripts from hiding sidebar
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && 
                    (mutation.attributeName === 'style' || mutation.attributeName === 'class')) {
                    
                    const sidebar = mutation.target;
                    if (sidebar === this.elements.sidebar) {
                        // Check if sidebar was hidden
                        if (sidebar.style.display === 'none' || 
                            sidebar.style.visibility === 'hidden' ||
                            sidebar.classList.contains('d-none')) {
                            
                            console.warn('🚨 Sidebar was hidden by external script, restoring...');
                            this.forceShowSidebar();
                        }
                    }
                }
            });
        });
        
        if (this.elements.sidebar) {
            observer.observe(this.elements.sidebar, {
                attributes: true,
                attributeFilter: ['style', 'class']
            });
        }
    }
    
    toggle() {
        if (this.state.isAnimating) return;
        
        if (this.state.isMobile) {
            this.toggleMobile();
        } else {
            this.toggleDesktop();
        }
    }
    
    toggleMobile() {
        if (this.state.isOpen) {
            this.closeMobile();
        } else {
            this.openMobile();
        }
    }
    
    openMobile() {
        this.state.isOpen = true;
        this.applyMobileState();
    }
    
    closeMobile() {
        this.state.isOpen = false;
        this.applyMobileState();
    }
    
    toggleDesktop() {
        this.state.isAnimating = true;
        this.state.isCollapsed = !this.state.isCollapsed;
        
        // Save state
        localStorage.setItem('sidebarCollapsed', this.state.isCollapsed.toString());
        
        // Apply state
        this.applyDesktopState();
        
        // Reset animation flag
        setTimeout(() => {
            this.state.isAnimating = false;
        }, 300);
        
        // Trigger resize event for charts and other components
        setTimeout(() => {
            window.dispatchEvent(new Event('resize'));
        }, 350);
    }
    
    // Public methods
    collapse() {
        if (!this.state.isMobile && !this.state.isCollapsed) {
            this.toggleDesktop();
        }
    }
    
    expand() {
        if (!this.state.isMobile && this.state.isCollapsed) {
            this.toggleDesktop();
        }
    }
    
    isCollapsed() {
        return this.state.isMobile ? false : this.state.isCollapsed;
    }
    
    isMobile() {
        return this.state.isMobile;
    }
}

// Initialize sidebar manager
let sidebarManager;

document.addEventListener('DOMContentLoaded', function() {
    // Small delay to ensure DOM is fully ready
    setTimeout(() => {
        sidebarManager = new SidebarManager();
        
        // Expose global functions for backward compatibility
        window.toggleSidebar = () => sidebarManager.toggle();
        window.toggleSidebarDesktop = () => sidebarManager.toggle();
        window.closeSidebar = () => sidebarManager.closeMobile();
        
        // Expose sidebar manager globally for debugging
        window.sidebarManager = sidebarManager;
        
        console.log('🎉 Enhanced Sidebar System Ready!');
    }, 100);
});

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SidebarManager;
}