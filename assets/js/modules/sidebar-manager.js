/**
 * UNIFIED SIDEBAR MANAGER
 * ================================================
 * Replaces: sidebar-manager.js, parts of helpers.js, parts of main.js
 * Single source of truth for all sidebar functionality
 */

class UnifiedSidebarManager {
    constructor() {
        this.config = {
            breakpoint: 1200,
            animationDuration: 300,
            storageKey: 'sidebarState'
        };
        
        this.state = {
            isCollapsed: this.loadState(),
            isMobile: this.checkMobile(),
            isOpen: false,
            isAnimating: false,
            isInitialized: false
        };
        
        this.elements = {};
        this.eventListeners = new Map();
        
        this.init();
    }
    
    /**
     * Initialize sidebar manager
     */
    init() {
        console.log('🚀 Initializing Unified Sidebar Manager...');
        
        // Get DOM elements
        this.getElements();
        
        // Ensure sidebar visibility
        this.ensureSidebarVisibility();
        
        // Apply initial state
        this.updateDeviceState();
        this.applyState();
        
        // Bind events
        this.bindEvents();
        
        // Initialize menu system
        this.initializeMenu();
        
        // Mark as initialized
        this.state.isInitialized = true;
        
        console.log('✅ Unified Sidebar Manager Ready!');
        this.triggerEvent('sidebarInitialized');
    }
    
    /**
     * Get DOM elements
     */
    getElements() {
        this.elements = {
            sidebar: document.getElementById('layout-menu') || document.querySelector('.layout-menu'),
            layoutPage: document.querySelector('.layout-page'),
            overlay: document.querySelector('.layout-overlay') || this.createOverlay(),
            toggleButtons: document.querySelectorAll('.layout-menu-toggle, .sidebar-toggle'),
            menuItems: document.querySelectorAll('.menu-item'),
            menuToggles: document.querySelectorAll('.menu-toggle'),
            menuLinks: document.querySelectorAll('.menu-link:not(.menu-toggle)'),
            body: document.body,
            html: document.documentElement
        };
        
        // Validate critical elements
        if (!this.elements.sidebar) {
            console.error('❌ Sidebar element not found!');
            return false;
        }
        
        if (!this.elements.layoutPage) {
            console.error('❌ Layout page element not found!');
            return false;
        }
        
        return true;
    }
    
    /**
     * Create overlay if not exists
     */
    createOverlay() {
        let overlay = document.querySelector('.layout-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'layout-overlay';
            overlay.style.cssText = `
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
                backdrop-filter: blur(4px);
            `;
            document.body.appendChild(overlay);
        }
        return overlay;
    }
    
    /**
     * Ensure sidebar is visible and not hidden by other scripts
     */
    ensureSidebarVisibility() {
        if (!this.elements.sidebar) return;
        
        // Force show sidebar
        this.elements.sidebar.style.removeProperty('display');
        this.elements.sidebar.style.removeProperty('visibility');
        this.elements.sidebar.style.removeProperty('opacity');
        this.elements.sidebar.classList.remove('d-none', 'hidden');
        
        // Monitor for external changes
        this.observeSidebarChanges();
    }
    
    /**
     * Monitor sidebar for external modifications
     */
    observeSidebarChanges() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && 
                    (mutation.attributeName === 'style' || mutation.attributeName === 'class')) {
                    
                    const sidebar = mutation.target;
                    if (sidebar === this.elements.sidebar) {
                        // Check if sidebar was hidden
                        const computedStyle = window.getComputedStyle(sidebar);
                        if (computedStyle.display === 'none' || 
                            computedStyle.visibility === 'hidden' ||
                            sidebar.classList.contains('d-none')) {
                            
                            console.warn('🚨 Sidebar was hidden externally, restoring...');
                            this.ensureSidebarVisibility();
                        }
                    }
                }
            });
        });
        
        observer.observe(this.elements.sidebar, {
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    }
    
    /**
     * Check if device is mobile
     */
    checkMobile() {
        return window.innerWidth < this.config.breakpoint;
    }
    
    /**
     * Update device state
     */
    updateDeviceState() {
        const wasMobile = this.state.isMobile;
        this.state.isMobile = this.checkMobile();
        
        // Close mobile menu when switching to desktop
        if (wasMobile && !this.state.isMobile && this.state.isOpen) {
            this.closeMobile();
        }
        
        // Reset collapsed state on mobile
        if (this.state.isMobile) {
            this.state.isCollapsed = false;
        }
    }
    
    /**
     * Apply current state to DOM
     */
    applyState() {
        if (this.state.isMobile) {
            this.applyMobileState();
        } else {
            this.applyDesktopState();
        }
    }
    
    /**
     * Apply mobile state
     */
    applyMobileState() {
        // Remove desktop classes
        this.elements.sidebar?.classList.remove('collapsed');
        this.elements.layoutPage?.classList.remove('sidebar-collapsed');
        
        // Reset layout page styles
        if (this.elements.layoutPage) {
            this.elements.layoutPage.style.marginLeft = '0';
            this.elements.layoutPage.style.width = '100%';
        }
        
        // Apply mobile state
        if (this.state.isOpen) {
            this.elements.sidebar?.classList.add('show');
            this.showOverlay();
            this.elements.body.style.overflow = 'hidden';
        } else {
            this.elements.sidebar?.classList.remove('show');
            this.hideOverlay();
            this.elements.body.style.overflow = '';
        }
    }
    
    /**
     * Apply desktop state
     */
    applyDesktopState() {
        // Remove mobile classes
        this.elements.sidebar?.classList.remove('show');
        this.hideOverlay();
        this.elements.body.style.overflow = '';
        
        // Apply desktop state
        if (this.state.isCollapsed) {
            this.elements.sidebar?.classList.add('collapsed');
            this.elements.layoutPage?.classList.add('sidebar-collapsed');
            
            if (this.elements.layoutPage) {
                this.elements.layoutPage.style.marginLeft = 'var(--sidebar-collapsed-width)';
                this.elements.layoutPage.style.width = 'calc(100% - var(--sidebar-collapsed-width))';
            }
        } else {
            this.elements.sidebar?.classList.remove('collapsed');
            this.elements.layoutPage?.classList.remove('sidebar-collapsed');
            
            if (this.elements.layoutPage) {
                this.elements.layoutPage.style.marginLeft = 'var(--sidebar-width)';
                this.elements.layoutPage.style.width = 'calc(100% - var(--sidebar-width))';
            }
        }
        
        this.updateToggleIcons();
    }
    
    /**
     * Update toggle button icons
     */
    updateToggleIcons() {
        const icons = document.querySelectorAll('.sidebar-toggle i, .layout-menu-toggle i');
        icons.forEach(icon => {
            icon.style.transform = this.state.isCollapsed ? 'rotate(180deg)' : 'rotate(0deg)';
            icon.style.transition = 'transform 0.3s ease';
        });
    }
    
    /**
     * Show overlay
     */
    showOverlay() {
        if (this.elements.overlay) {
            this.elements.overlay.style.opacity = '1';
            this.elements.overlay.style.visibility = 'visible';
            this.elements.overlay.classList.add('show');
        }
    }
    
    /**
     * Hide overlay
     */
    hideOverlay() {
        if (this.elements.overlay) {
            this.elements.overlay.style.opacity = '0';
            this.elements.overlay.style.visibility = 'hidden';
            this.elements.overlay.classList.remove('show');
        }
    }
    
    /**
     * Bind all events
     */
    bindEvents() {
        this.bindToggleEvents();
        this.bindResizeEvents();
        this.bindOverlayEvents();
        this.bindKeyboardEvents();
        this.bindMenuEvents();
        this.bindPreventionEvents();
    }
    
    /**
     * Bind toggle button events
     */
    bindToggleEvents() {
        this.elements.toggleButtons.forEach(button => {
            const handler = (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggle();
            };
            
            button.addEventListener('click', handler);
            this.eventListeners.set(button, { event: 'click', handler });
        });
    }
    
    /**
     * Bind window resize events
     */
    bindResizeEvents() {
        let resizeTimeout;
        const handler = () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                this.updateDeviceState();
                this.applyState();
                this.triggerEvent('sidebarResize');
            }, 100);
        };
        
        window.addEventListener('resize', handler);
        this.eventListeners.set(window, { event: 'resize', handler });
    }
    
    /**
     * Bind overlay events
     */
    bindOverlayEvents() {
        if (this.elements.overlay) {
            const handler = () => {
                if (this.state.isMobile) {
                    this.closeMobile();
                }
            };
            
            this.elements.overlay.addEventListener('click', handler);
            this.eventListeners.set(this.elements.overlay, { event: 'click', handler });
        }
    }
    
    /**
     * Bind keyboard events
     */
    bindKeyboardEvents() {
        const handler = (e) => {
            if (e.key === 'Escape' && this.state.isMobile && this.state.isOpen) {
                this.closeMobile();
            }
        };
        
        document.addEventListener('keydown', handler);
        this.eventListeners.set(document, { event: 'keydown', handler });
    }
    
    /**
     * Bind menu events
     */
    bindMenuEvents() {
        // Menu link clicks on mobile
        this.elements.menuLinks.forEach(link => {
            const handler = () => {
                if (this.state.isMobile && this.state.isOpen) {
                    setTimeout(() => this.closeMobile(), 200);
                }
            };
            
            link.addEventListener('click', handler);
            this.eventListeners.set(link, { event: 'click', handler });
        });
        
        // Menu toggle events
        this.elements.menuToggles.forEach(toggle => {
            const handler = (e) => {
                e.preventDefault();
                this.handleMenuToggle(toggle);
            };
            
            toggle.addEventListener('click', handler);
            this.eventListeners.set(toggle, { event: 'click', handler });
        });
    }
    
    /**
     * Bind prevention events
     */
    bindPreventionEvents() {
        // Prevent sidebar hiding
        const preventHiding = () => {
            if (this.elements.sidebar) {
                this.ensureSidebarVisibility();
            }
        };
        
        // Check periodically
        setInterval(preventHiding, 5000);
    }
    
    /**
     * Initialize menu system
     */
    initializeMenu() {
        this.expandActiveMenus();
        this.initializeScrollbar();
    }
    
    /**
     * Handle menu toggle
     */
    handleMenuToggle(toggle) {
        // Don't open submenus when collapsed on desktop
        if (!this.state.isMobile && this.state.isCollapsed) {
            return;
        }
        
        const menuItem = toggle.closest('.menu-item');
        const submenu = menuItem?.querySelector('.menu-sub');
        
        if (submenu) {
            const isOpen = menuItem.classList.contains('open');
            
            // Close other submenus (accordion behavior)
            this.closeOtherSubmenus(menuItem);
            
            // Toggle current submenu
            if (isOpen) {
                this.closeSubmenu(menuItem, submenu);
            } else {
                this.openSubmenu(menuItem, submenu);
            }
        }
    }
    
    /**
     * Close other submenus
     */
    closeOtherSubmenus(currentItem) {
        const otherOpenItems = document.querySelectorAll('.menu-item.open');
        otherOpenItems.forEach(item => {
            if (item !== currentItem && !this.isParentOf(currentItem, item)) {
                const submenu = item.querySelector('.menu-sub');
                if (submenu) {
                    this.closeSubmenu(item, submenu);
                }
            }
        });
    }
    
    /**
     * Open submenu
     */
    openSubmenu(menuItem, submenu) {
        menuItem.classList.add('open');
        submenu.style.display = 'block';
        
        // Animate if needed
        if (this.config.animationDuration > 0) {
            submenu.style.maxHeight = '0';
            submenu.style.overflow = 'hidden';
            submenu.style.transition = `max-height ${this.config.animationDuration}ms ease`;
            
            requestAnimationFrame(() => {
                submenu.style.maxHeight = submenu.scrollHeight + 'px';
            });
        }
    }
    
    /**
     * Close submenu
     */
    closeSubmenu(menuItem, submenu) {
        if (this.config.animationDuration > 0) {
            submenu.style.maxHeight = '0';
            
            setTimeout(() => {
                menuItem.classList.remove('open');
                submenu.style.display = 'none';
                submenu.style.maxHeight = '';
                submenu.style.overflow = '';
                submenu.style.transition = '';
            }, this.config.animationDuration);
        } else {
            menuItem.classList.remove('open');
            submenu.style.display = 'none';
        }
    }
    
    /**
     * Check if element is parent of another
     */
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
    
    /**
     * Expand active menus
     */
    expandActiveMenus() {
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
    
    /**
     * Initialize scrollbar
     */
    initializeScrollbar() {
        const menuInner = this.elements.sidebar?.querySelector('.menu-inner');
        if (menuInner && window.PerfectScrollbar) {
            try {
                if (this.perfectScrollbar) {
                    this.perfectScrollbar.destroy();
                }
                
                this.perfectScrollbar = new PerfectScrollbar(menuInner, {
                    suppressScrollX: true,
                    wheelPropagation: false
                });
            } catch (error) {
                console.warn('PerfectScrollbar initialization failed:', error);
            }
        }
    }
    
    /**
     * Main toggle function
     */
    toggle() {
        if (this.state.isAnimating) return;
        
        if (this.state.isMobile) {
            this.toggleMobile();
        } else {
            this.toggleDesktop();
        }
    }
    
    /**
     * Toggle mobile sidebar
     */
    toggleMobile() {
        if (this.state.isOpen) {
            this.closeMobile();
        } else {
            this.openMobile();
        }
    }
    
    /**
     * Open mobile sidebar
     */
    openMobile() {
        this.state.isOpen = true;
        this.applyMobileState();
        this.triggerEvent('mobileOpened');
    }
    
    /**
     * Close mobile sidebar
     */
    closeMobile() {
        this.state.isOpen = false;
        this.applyMobileState();
        this.triggerEvent('mobileClosed');
    }
    
    /**
     * Toggle desktop sidebar
     */
    toggleDesktop() {
        this.state.isAnimating = true;
        this.state.isCollapsed = !this.state.isCollapsed;
        
        // Save state
        this.saveState();
        
        // Apply state
        this.applyDesktopState();
        
        // Trigger events
        this.triggerEvent(this.state.isCollapsed ? 'collapsed' : 'expanded');
        this.triggerEvent('toggle');
        
        // Reset animation flag
        setTimeout(() => {
            this.state.isAnimating = false;
            
            // Trigger resize for charts and other components
            window.dispatchEvent(new Event('resize'));
        }, this.config.animationDuration);
    }
    
    /**
     * Load state from storage
     */
    loadState() {
        try {
            const saved = localStorage.getItem(this.config.storageKey);
            return saved ? JSON.parse(saved).isCollapsed : false;
        } catch (error) {
            console.warn('Failed to load sidebar state:', error);
            return false;
        }
    }
    
    /**
     * Save state to storage
     */
    saveState() {
        try {
            const state = {
                isCollapsed: this.state.isCollapsed,
                timestamp: Date.now()
            };
            localStorage.setItem(this.config.storageKey, JSON.stringify(state));
        } catch (error) {
            console.warn('Failed to save sidebar state:', error);
        }
    }
    
    /**
     * Trigger custom events
     */
    triggerEvent(eventName, detail = {}) {
        const event = new CustomEvent(`sidebar${eventName.charAt(0).toUpperCase() + eventName.slice(1)}`, {
            detail: { ...detail, state: this.state }
        });
        window.dispatchEvent(event);
    }
    
    /**
     * Public API methods
     */
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
    
    isOpen() {
        return this.state.isMobile ? this.state.isOpen : !this.state.isCollapsed;
    }
    
    getState() {
        return { ...this.state };
    }
    
    /**
     * Destroy sidebar manager
     */
    destroy() {
        // Remove event listeners
        this.eventListeners.forEach((listenerData, element) => {
            element.removeEventListener(listenerData.event, listenerData.handler);
        });
        this.eventListeners.clear();
        
        // Destroy perfect scrollbar
        if (this.perfectScrollbar) {
            this.perfectScrollbar.destroy();
            this.perfectScrollbar = null;
        }
        
        // Reset styles
        if (this.elements.layoutPage) {
            this.elements.layoutPage.style.marginLeft = '';
            this.elements.layoutPage.style.width = '';
        }
        
        // Remove classes
        this.elements.sidebar?.classList.remove('collapsed', 'show');
        this.elements.layoutPage?.classList.remove('sidebar-collapsed');
        this.hideOverlay();
        this.elements.body.style.overflow = '';
        
        console.log('🗑️ Unified Sidebar Manager destroyed');
    }
}

// Auto-initialize when DOM is ready
let sidebarManager = null;

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        sidebarManager = new UnifiedSidebarManager();
        
        // Expose global functions for backward compatibility
        window.sidebarManager = sidebarManager;
        window.toggleSidebar = () => sidebarManager.toggle();
        window.collapseSidebar = () => sidebarManager.collapse();
        window.expandSidebar = () => sidebarManager.expand();
        
        console.log('🎉 Unified Sidebar System Ready!');
    }, 100);
});

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = UnifiedSidebarManager;
}

// Export for ES6 modules
export { UnifiedSidebarManager };