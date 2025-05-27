/**
 * UNIFIED CONFIGURATION
 * ================================================
 * Single source of truth for all JavaScript configurations
 * Replaces multiple config files and scattered configurations
 */

'use strict';

/**
 * Global Medical System Configuration
 */
window.MedicalSystemConfig = {
  // ================================================
  // SYSTEM INFO
  // ================================================
  system: {
    name: 'Medical Management System',
    version: '2.0.0',
    environment: 'production', // development, staging, production
    debug: false
  },

  // ================================================
  // COLOR SYSTEM
  // ================================================
  colors: {
    // Primary colors
    primary: '#696cff',
    'primary-dark': '#5a67d8',
    'primary-light': '#8b92ff',
    'primary-rgb': [105, 108, 255],

    // Semantic colors
    secondary: '#8592a3',
    success: '#71dd37',
    info: '#03c3ec',
    warning: '#ffab00',
    danger: '#ff3e1d',
    
    // Neutral colors
    dark: '#233446',
    light: '#f4f5fb',
    white: '#ffffff',
    black: '#000000',
    
    // Medical specific
    'medical-blue': '#4c84ff',
    'medical-green': '#00d4aa',
    'medical-red': '#ff4757',
    'medical-orange': '#ffa726',
    'medical-purple': '#9c27b0',
    
    // UI colors
    body: '#f4f5fb',
    'heading-color': '#566a7f',
    'text-color': '#566a7f',
    'text-muted': '#a1acb8',
    'axis-color': '#a1acb8',
    'border-color': '#eceef1'
  },

  // ================================================
  // LAYOUT CONFIGURATION
  // ================================================
  layout: {
    // Sidebar
    sidebar: {
      width: 260,
      collapsedWidth: 80,
      breakpoint: 1200,
      animation: {
        duration: 300,
        easing: 'ease'
      },
      storage: {
        key: 'medicalSystem_sidebarState',
        enabled: true
      }
    },
    
    // Navbar
    navbar: {
      height: 64,
      fixed: true,
      shadow: true
    },
    
    // Footer
    footer: {
      height: 60,
      fixed: false
    },
    
    // Breakpoints
    breakpoints: {
      xs: 0,
      sm: 576,
      md: 768,
      lg: 992,
      xl: 1200,
      xxl: 1400
    }
  },

  // ================================================
  // COMPONENT DEFAULTS
  // ================================================
  components: {
    // Buttons
    button: {
      defaultVariant: 'primary',
      defaultSize: 'md',
      animation: {
        hover: true,
        shine: true
      }
    },
    
    // Cards
    card: {
      shadow: true,
      hover: true,
      animation: {
        duration: 300
      }
    },
    
    // Tables
    table: {
      responsive: true,
      hover: true,
      striped: false,
      sorting: true,
      pagination: {
        enabled: true,
        perPage: 10,
        showInfo: true
      }
    },
    
    // Forms
    form: {
      validation: {
        realTime: true,
        showErrors: true,
        errorDelay: 300
      },
      autoSave: {
        enabled: false,
        interval: 30000
      }
    },
    
    // Modals
    modal: {
      backdrop: true,
      keyboard: true,
      focus: true,
      animation: true
    },
    
    // Tooltips
    tooltip: {
      placement: 'top',
      trigger: 'hover',
      delay: { show: 500, hide: 100 },
      animation: true
    },
    
    // Notifications
    notification: {
      position: 'top-right',
      duration: 5000,
      closable: true,
      progress: true,
      maxVisible: 5
    }
  },

  // ================================================
  // DASHBOARD CONFIGURATION
  // ================================================
  dashboard: {
    // Auto refresh
    autoRefresh: {
      enabled: true,
      interval: 300000, // 5 minutes
      showIndicator: true
    },
    
    // Charts
    charts: {
      defaultTheme: 'light',
      animation: true,
      responsive: true,
      colors: ['#696cff', '#71dd37', '#03c3ec', '#ffab00', '#ff3e1d'],
      gradient: true
    },
    
    // Stats cards
    statsCards: {
      animation: {
        counters: true,
        duration: 2000,
        easing: 'easeOutQuart'
      },
      hover: true
    },
    
    // Activity feed
    activity: {
      maxItems: 10,
      autoUpdate: true,
      groupSimilar: true
    }
  },

  // ================================================
  // DATA MANAGEMENT
  // ================================================
  data: {
    // API configuration
    api: {
      baseUrl: '/api',
      timeout: 30000,
      retries: 3,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    },
    
    // Caching
    cache: {
      enabled: true,
      ttl: 300000, // 5 minutes
      maxSize: 100
    },
    
    // Export options
    export: {
      formats: ['csv', 'xlsx', 'pdf'],
      dateFormat: 'YYYY-MM-DD',
      encoding: 'utf-8'
    },
    
    // Pagination
    pagination: {
      defaultPerPage: 10,
      options: [10, 25, 50, 100],
      showTotal: true
    }
  },

  // ================================================
  // SEARCH & FILTER
  // ================================================
  search: {
    // Global search
    global: {
      enabled: true,
      minLength: 2,
      debounce: 300,
      placeholder: 'Search patients, records...',
      shortcut: 'Ctrl+K'
    },
    
    // Table search
    table: {
      enabled: true,
      highlight: true,
      fuzzy: false,
      caseSensitive: false
    },
    
    // Filters
    filter: {
      enabled: true,
      persistent: true,
      presets: true
    }
  },

  // ================================================
  // SECURITY & VALIDATION
  // ================================================
  security: {
    // Session
    session: {
      timeout: 1800000, // 30 minutes
      warningTime: 300000, // 5 minutes before timeout
      extendOnActivity: true
    },
    
    // Input validation
    validation: {
      strictMode: true,
      sanitizeInput: true,
      maxFileSize: 10485760, // 10MB
      allowedFileTypes: ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'csv', 'xlsx']
    },
    
    // CSRF protection
    csrf: {
      enabled: true,
      tokenName: '_token'
    }
  },

  // ================================================
  // PERFORMANCE
  // ================================================
  performance: {
    // Lazy loading
    lazyLoad: {
      enabled: true,
      threshold: 100,
      images: true,
      components: true
    },
    
    // Debouncing
    debounce: {
      search: 300,
      resize: 100,
      scroll: 50,
      input: 200
    },
    
    // Virtual scrolling
    virtualScroll: {
      enabled: false,
      itemHeight: 50,
      buffer: 10
    },
    
    // Image optimization
    images: {
      lazyLoad: true,
      placeholder: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIwIiBoZWlnaHQ9IjI0MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjY2NjIi8+PC9zdmc+',
      quality: 85,
      formats: ['webp', 'jpg', 'png']
    }
  },

  // ================================================
  // ACCESSIBILITY
  // ================================================
  accessibility: {
    // Focus management
    focus: {
      visible: true,
      trap: true,
      restore: true
    },
    
    // Screen reader
    screenReader: {
      announcements: true,
      liveRegions: true,
      skipLinks: true
    },
    
    // Keyboard navigation
    keyboard: {
      shortcuts: true,
      tabIndex: true,
      arrows: true
    },
    
    // High contrast
    highContrast: {
      detect: true,
      support: true
    },
    
    // Reduced motion
    reducedMotion: {
      detect: true,
      respect: true
    }
  },

  // ================================================
  // INTERNATIONALIZATION
  // ================================================
  i18n: {
    // Language settings
    defaultLocale: 'en',
    fallbackLocale: 'en',
    supportedLocales: ['en', 'th'],
    
    // Date & time
    dateFormat: 'YYYY-MM-DD',
    timeFormat: 'HH:mm:ss',
    timezone: 'Asia/Bangkok',
    
    // Number formatting
    numberFormat: {
      decimal: '.',
      thousands: ',',
      currency: 'THB'
    }
  },

  // ================================================
  // MEDICAL SPECIFIC
  // ================================================
  medical: {
    // Patient management
    patient: {
      idFormat: 'P{YYYY}{MM}{DD}{###}',
      requiredFields: ['firstName', 'lastName', 'dob', 'phone'],
      validation: {
        age: { min: 0, max: 150 },
        phone: /^[\d\s\-\(\)\+]{10,}$/,
        email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      }
    },
    
    // Medical records
    record: {
      idFormat: 'R{YYYY}{MM}{DD}{####}',
      autoSave: true,
      versionControl: true,
      encryption: true
    },
    
    // Appointments
    appointment: {
      timeSlots: 30, // minutes
      workingHours: {
        start: '08:00',
        end: '18:00'
      },
      workingDays: [1, 2, 3, 4, 5], // Monday to Friday
      advance: {
        min: 1, // days
        max: 90 // days
      }
    },
    
    // Medication
    medication: {
      dosageUnits: ['mg', 'ml', 'tablets', 'capsules'],
      frequencies: ['Once daily', 'Twice daily', 'Three times daily', 'Four times daily', 'As needed'],
      routes: ['Oral', 'Injection', 'Topical', 'Inhalation']
    }
  },

  // ================================================
  // DEVELOPMENT
  // ================================================
  development: {
    // Debug settings
    debug: {
      enabled: false,
      level: 'info', // error, warn, info, debug
      console: true,
      network: false,
      performance: false
    },
    
    // Hot reload
    hotReload: {
      enabled: false,
      port: 3000
    },
    
    // Testing
    testing: {
      enabled: false,
      coverage: false,
      e2e: false
    }
  },

  // ================================================
  // FEATURE FLAGS
  // ================================================
  features: {
    // Core features
    dashboard: true,
    patients: true,
    appointments: true,
    records: true,
    reports: true,
    
    // Advanced features
    telemedicine: false,
    ai: false,
    integrations: false,
    analytics: true,
    
    // Beta features
    voiceInput: false,
    biometrics: false,
    blockchain: false
  },

  // ================================================
  // PLUGINS & EXTENSIONS
  // ================================================
  plugins: {
    // Chart libraries
    charts: {
      library: 'chart.js', // chart.js, apexcharts, d3
      config: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    },
    
    // Date picker
    datePicker: {
      library: 'flatpickr',
      config: {
        dateFormat: 'Y-m-d',
        enableTime: false,
        locale: 'default'
      }
    },
    
    // Rich text editor
    editor: {
      library: 'quill',
      config: {
        theme: 'snow',
        modules: {
          toolbar: [
            ['bold', 'italic', 'underline'],
            ['link', 'blockquote', 'code-block'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }]
          ]
        }
      }
    },
    
    // File upload
    fileUpload: {
      library: 'dropzone',
      config: {
        maxFilesize: 10, // MB
        acceptedFiles: '.jpg,.jpeg,.png,.pdf,.doc,.docx',
        thumbnailWidth: 150,
        thumbnailHeight: 150
      }
    }
  }
};

/**
 * Configuration getter with dot notation support
 * @param {string} path - Configuration path (e.g., 'layout.sidebar.width')
 * @param {*} defaultValue - Default value if path not found
 * @returns {*} Configuration value
 */
window.MedicalSystemConfig.get = function(path, defaultValue = null) {
  try {
    return path.split('.').reduce((obj, key) => obj?.[key], this) ?? defaultValue;
  } catch (error) {
    console.warn(`Config path '${path}' not found:`, error);
    return defaultValue;
  }
};

/**
 * Configuration setter with dot notation support
 * @param {string} path - Configuration path
 * @param {*} value - Value to set
 */
window.MedicalSystemConfig.set = function(path, value) {
  try {
    const keys = path.split('.');
    const lastKey = keys.pop();
    const target = keys.reduce((obj, key) => {
      if (!(key in obj)) obj[key] = {};
      return obj[key];
    }, this);
    target[lastKey] = value;
    
    // Trigger config change event
    window.dispatchEvent(new CustomEvent('configChanged', {
      detail: { path, value, config: this }
    }));
  } catch (error) {
    console.error(`Failed to set config path '${path}':`, error);
  }
};

/**
 * Check if feature is enabled
 * @param {string} feature - Feature name
 * @returns {boolean} Feature status
 */
window.MedicalSystemConfig.isFeatureEnabled = function(feature) {
  return this.get(`features.${feature}`, false);
};

/**
 * Get color value
 * @param {string} colorName - Color name
 * @returns {string} Color value
 */
window.MedicalSystemConfig.getColor = function(colorName) {
  return this.get(`colors.${colorName}`, '#000000');
};

/**
 * Get breakpoint value
 * @param {string} breakpoint - Breakpoint name
 * @returns {number} Breakpoint value in pixels
 */
window.MedicalSystemConfig.getBreakpoint = function(breakpoint) {
  return this.get(`layout.breakpoints.${breakpoint}`, 0);
};

/**
 * Check if current screen is below breakpoint
 * @param {string} breakpoint - Breakpoint name
 * @returns {boolean} Is mobile
 */
window.MedicalSystemConfig.isMobile = function(breakpoint = 'lg') {
  return window.innerWidth < this.getBreakpoint(breakpoint);
};

/**
 * Environment helpers
 */
window.MedicalSystemConfig.isDevelopment = function() {
  return this.get('system.environment') === 'development';
};

window.MedicalSystemConfig.isProduction = function() {
  return this.get('system.environment') === 'production';
};

window.MedicalSystemConfig.isDebug = function() {
  return this.get('system.debug', false) || this.get('development.debug.enabled', false);
};

/**
 * Initialize configuration based on environment
 */
window.MedicalSystemConfig.init = function() {
  // Set debug mode based on environment
  if (this.isDevelopment()) {
    this.set('system.debug', true);
    this.set('development.debug.enabled', true);
  }
  
  // Detect user preferences
  this.detectUserPreferences();
  
  // Setup CSS custom properties
  this.setupCSSProperties();
  
  // Log initialization
  if (this.isDebug()) {
    console.log('🎛️ Medical System Config initialized:', this);
  }
  
  // Trigger init event
  window.dispatchEvent(new CustomEvent('configInitialized', {
    detail: { config: this }
  }));
};

/**
 * Detect user preferences from browser
 */
window.MedicalSystemConfig.detectUserPreferences = function() {
  // Detect reduced motion preference
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    this.set('accessibility.reducedMotion.enabled', true);
    this.set('components.card.animation.duration', 0);
    this.set('layout.sidebar.animation.duration', 0);
  }
  
  // Detect high contrast preference
  if (window.matchMedia('(prefers-contrast: high)').matches) {
    this.set('accessibility.highContrast.enabled', true);
  }
  
  // Detect color scheme preference
  if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
    this.set('theme.mode', 'dark');
  }
  
  // Detect language from browser
  const browserLang = navigator.language.split('-')[0];
  if (this.get('i18n.supportedLocales').includes(browserLang)) {
    this.set('i18n.currentLocale', browserLang);
  }
};

/**
 * Setup CSS custom properties from config
 */
window.MedicalSystemConfig.setupCSSProperties = function() {
  const root = document.documentElement;
  
  // Set color properties
  Object.entries(this.colors).forEach(([name, value]) => {
    if (Array.isArray(value)) {
      root.style.setProperty(`--${name}`, value.join(', '));
    } else {
      root.style.setProperty(`--${name}`, value);
    }
  });
  
  // Set layout properties
  root.style.setProperty('--sidebar-width', `${this.get('layout.sidebar.width')}px`);
  root.style.setProperty('--sidebar-collapsed-width', `${this.get('layout.sidebar.collapsedWidth')}px`);
  root.style.setProperty('--navbar-height', `${this.get('layout.navbar.height')}px`);
  
  // Set animation properties
  const duration = this.get('layout.sidebar.animation.duration');
  root.style.setProperty('--transition-duration', `${duration}ms`);
};

/**
 * Update configuration from server
 */
window.MedicalSystemConfig.updateFromServer = async function() {
  try {
    const response = await fetch('/api/config');
    const serverConfig = await response.json();
    
    // Merge server config with client config
    Object.assign(this, serverConfig);
    
    // Re-setup CSS properties
    this.setupCSSProperties();
    
    // Trigger update event
    window.dispatchEvent(new CustomEvent('configUpdated', {
      detail: { config: this }
    }));
    
    if (this.isDebug()) {
      console.log('🔄 Config updated from server:', serverConfig);
    }
  } catch (error) {
    console.warn('Failed to update config from server:', error);
  }
};

/**
 * Export configuration for debugging
 */
window.MedicalSystemConfig.export = function() {
  return JSON.stringify(this, null, 2);
};

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    window.MedicalSystemConfig.init();
  });
} else {
  window.MedicalSystemConfig.init();
}

// Handle configuration changes
window.addEventListener('configChanged', (event) => {
  const { path, value } = event.detail;
  
  // Re-setup CSS properties if colors or layout changed
  if (path.startsWith('colors.') || path.startsWith('layout.')) {
    window.MedicalSystemConfig.setupCSSProperties();
  }
  
  if (window.MedicalSystemConfig.isDebug()) {
    console.log(`🎛️ Config changed: ${path} = ${value}`);
  }
});

// Expose for backward compatibility
window.config = {
  colors: window.MedicalSystemConfig.colors
};

// Export for ES6 modules
if (typeof module !== 'undefined' && module.exports) {
  module.exports = window.MedicalSystemConfig;
}