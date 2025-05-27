// dashboard.js - Enhanced Medical Dashboard JavaScript

// Global Variables
let dailyPatientsData = [];
let monthlyPatientsData = [];
let currentChartView = 'daily';
let patientChart = null;

// Utility Functions
const DashboardUtils = {
    // Format numbers with commas
    formatNumber: (num) => {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },
    
    // Animate counter
    animateCounter: (element, target, duration = 2000) => {
        if (!element || isNaN(target)) return;
        
        const start = 0;
        const increment = target / (duration / 16);
        let current = start;
        
        const updateCounter = () => {
            if (current < target) {
                current += increment;
                element.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = target;
            }
        };
        
        updateCounter();
    },
    
    // Show notification
    showNotification: (message, type = 'success', duration = 5000) => {
        const alertClass = `alert-${type}`;
        const icon = type === 'success' ? 'bx-check' : type === 'error' ? 'bx-x' : 'bx-info-circle';
        
        const alert = document.createElement('div');
        alert.className = `alert ${alertClass} alert-dismissible fade show dashboard-alert`;
        alert.innerHTML = `
            <i class="bx ${icon} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.container-xxl');
        if (container) {
            container.insertBefore(alert, container.firstChild);
            
            // Auto remove after duration
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, duration);
        }
    },
    
    // Debounce function
    debounce: (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// Global Search Functionality
const GlobalSearch = {
    modal: null,
    searchInput: null,
    resultsContainer: null,
    
    init: () => {
        const modalElement = document.getElementById('globalSearchModal');
        if (modalElement) {
            GlobalSearch.modal = new bootstrap.Modal(modalElement);
            GlobalSearch.searchInput = document.getElementById('globalSearchInput');
            GlobalSearch.resultsContainer = document.getElementById('searchResults');
            
            // Add event listeners
            if (GlobalSearch.searchInput) {
                GlobalSearch.searchInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        GlobalSearch.performSearch();
                    }
                });
                
                // Add real-time search with debounce
                GlobalSearch.searchInput.addEventListener('input', 
                    DashboardUtils.debounce(GlobalSearch.performSearch, 500)
                );
            }
            
            // Focus input when modal opens
            modalElement.addEventListener('shown.bs.modal', () => {
                if (GlobalSearch.searchInput) {
                    GlobalSearch.searchInput.focus();
                }
            });
            
            // Clear search when modal closes
            modalElement.addEventListener('hidden.bs.modal', () => {
                GlobalSearch.clearSearch();
            });
        }
    },
    
    show: () => {
        if (GlobalSearch.modal) {
            GlobalSearch.modal.show();
        }
    },
    
    performSearch: () => {
        if (!GlobalSearch.searchInput || !GlobalSearch.resultsContainer) return;
        
        const searchTerm = GlobalSearch.searchInput.value.trim();
        
        if (searchTerm.length < 2) {
            GlobalSearch.resultsContainer.innerHTML = `
                <div class="alert alert-info mb-0">
                    <i class="bx bx-info-circle me-2"></i>
                    Please enter at least 2 characters to search.
                </div>
            `;
            return;
        }
        
        // Show loading
        GlobalSearch.resultsContainer.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Searching...</span>
                </div>
                <div class="mt-2">Searching for "${searchTerm}"...</div>
            </div>
        `;
        
        // Simulate API call
        setTimeout(() => {
            GlobalSearch.displayResults(searchTerm);
        }, 1000);
    },
    
    displayResults: (searchTerm) => {
        // Mock search results
        const mockResults = [
            {
                type: 'patient',
                id: 1,
                title: 'John Doe',
                subtitle: 'Age: 35, Phone: 123-456-7890',
                url: '../patients/patient_view.php?id=1'
            },
            {
                type: 'record',
                id: 1,
                title: 'Medical Record #001',
                subtitle: 'Patient: John Doe - Diagnosis: Hypertension',
                url: '../medical_records/medical_record_view.php?id=1'
            }
        ];
        
        if (mockResults.length === 0) {
            GlobalSearch.resultsContainer.innerHTML = `
                <div class="alert alert-warning mb-0">
                    <i class="bx bx-search me-2"></i>
                    No results found for "${searchTerm}"
                </div>
            `;
            return;
        }
        
        let resultsHTML = `
            <div class="search-results-header mb-3">
                <h6><i class="bx bx-search me-2"></i>Search Results (${mockResults.length})</h6>
            </div>
        `;
        
        mockResults.forEach(result => {
            const icon = result.type === 'patient' ? 'bx-user' : 'bx-file';
            const badgeClass = result.type === 'patient' ? 'bg-primary' : 'bg-success';
            
            resultsHTML += `
                <div class="search-result-item p-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <div class="search-result-icon me-3">
                            <i class="bx ${icon} text-muted"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">
                                <a href="${result.url}" class="text-decoration-none">${result.title}</a>
                            </h6>
                            <small class="text-muted">${result.subtitle}</small>
                        </div>
                        <div class="search-result-badge">
                            <span class="badge ${badgeClass}">${result.type}</span>
                        </div>
                    </div>
                </div>
            `;
        });
        
        GlobalSearch.resultsContainer.innerHTML = resultsHTML;
    },
    
    clearSearch: () => {
        if (GlobalSearch.searchInput) {
            GlobalSearch.searchInput.value = '';
        }
        if (GlobalSearch.resultsContainer) {
            GlobalSearch.resultsContainer.innerHTML = '';
        }
    }
};

// Chart Management
const ChartManager = {
    patientChart: null,
    
    init: () => {
        const chartCanvas = document.getElementById('patientTrendsChart');
        if (chartCanvas && typeof Chart !== 'undefined') {
            ChartManager.createPatientChart();
        }
    },
    
    createPatientChart: () => {
        const ctx = document.getElementById('patientTrendsChart');
        if (!ctx) return;
        
        // Mock data if not provided
        const defaultData = [
            { date: '2024-01-01', count: 5 },
            { date: '2024-01-02', count: 8 },
            { date: '2024-01-03', count: 12 },
            { date: '2024-01-04', count: 7 },
            { date: '2024-01-05', count: 15 }
        ];
        
        const data = dailyPatientsData.length > 0 ? dailyPatientsData : defaultData;
        
        ChartManager.patientChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'New Patients',
                    data: data.map(item => item.count),
                    borderColor: 'rgb(102, 126, 234)',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    },
    
    toggleView: (view) => {
        if (view === currentChartView) return;
        
        currentChartView = view;
        
        // Update button states
        document.querySelectorAll('.chart-controls .btn').forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');
        });
        
        event.target.classList.remove('btn-outline-primary');
        event.target.classList.add('btn-primary');
        
        // Update chart data
        if (ChartManager.patientChart) {
            const data = view === 'daily' ? dailyPatientsData : monthlyPatientsData;
            ChartManager.updateChartData(data, view);
        }
    },
    
    updateChartData: (data, view) => {
        if (!ChartManager.patientChart || !data.length) return;
        
        const labels = data.map(item => {
            const date = new Date(view === 'daily' ? item.date : `${item.year}-${item.month}-01`);
            return view === 'daily' 
                ? date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
                : date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        });
        
        ChartManager.patientChart.data.labels = labels;
        ChartManager.patientChart.data.datasets[0].data = data.map(item => item.count);
        ChartManager.patientChart.data.datasets[0].label = view === 'daily' ? 'Daily Patients' : 'Monthly Patients';
        ChartManager.patientChart.update('active');
    },
    
    destroy: () => {
        if (ChartManager.patientChart) {
            ChartManager.patientChart.destroy();
            ChartManager.patientChart = null;
        }
    }
};

// Export/Import Functionality
const DataManager = {
    exportData: (type) => {
        const btn = event.target;
        const originalText = btn.innerHTML;
        
        // Disable button and show loading
        btn.disabled = true;
        btn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Exporting...';
        
        // Simulate export process
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            
            // Show success notification
            DashboardUtils.showNotification(
                `${type.charAt(0).toUpperCase() + type.slice(1)} data exported successfully!`,
                'success'
            );
            
            // Simulate file download
            DataManager.downloadFile(type);
        }, 2000);
    },
    
    downloadFile: (type) => {
        // Create mock CSV data
        let csvContent = '';
        const filename = `${type}_export_${new Date().toISOString().split('T')[0]}.csv`;
        
        switch (type) {
            case 'patients':
                csvContent = 'ID,First Name,Last Name,Age,Phone,Email,Created Date\n';
                csvContent += '1,John,Doe,35,123-456-7890,john@email.com,2024-01-01\n';
                csvContent += '2,Jane,Smith,28,098-765-4321,jane@email.com,2024-01-02\n';
                break;
            case 'records':
                csvContent = 'ID,Patient Name,Diagnosis,Visit Date,Treatment\n';
                csvContent += '1,John Doe,Hypertension,2024-01-01,Medication prescribed\n';
                csvContent += '2,Jane Smith,Diabetes,2024-01-02,Diet plan provided\n';
                break;
            default:
                csvContent = 'No data available\n';
        }
        
        // Create and trigger download
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        
        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    },
    
    performBackup: () => {
        const btn = event.target;
        const originalText = btn.innerHTML;
        
        // Show confirmation dialog
        if (!confirm('Are you sure you want to perform a system backup? This may take a few minutes.')) {
            return;
        }
        
        btn.disabled = true;
        btn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Backing up...';
        
        // Simulate backup process
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            
            DashboardUtils.showNotification(
                'System backup completed successfully! Backup saved to server.',
                'success'
            );
        }, 3000);
    }
};

// Activity Management
const ActivityManager = {
    init: () => {
        ActivityManager.setupHoverEffects();
        ActivityManager.setupClickHandlers();
    },
    
    setupHoverEffects: () => {
        // Enhanced hover effects for activity items
        document.querySelectorAll('.activity-item-enhanced').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f8f9fa';
                this.style.borderColor = '#dee2e6';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
                this.style.borderColor = 'transparent';
            });
        });
        
        // Stats card hover effects
        document.querySelectorAll('.dashboard-stats-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                const numberElement = this.querySelector('.stats-number-large');
                if (numberElement) {
                    numberElement.style.transform = 'scale(1.05)';
                }
            });
            
            card.addEventListener('mouseleave', function() {
                const numberElement = this.querySelector('.stats-number-large');
                if (numberElement) {
                    numberElement.style.transform = 'scale(1)';
                }
            });
        });
    },
    
    setupClickHandlers: () => {
        // Add click handlers for activity items
        document.querySelectorAll('[data-patient-id]').forEach(item => {
            item.addEventListener('click', function(e) {
                if (!e.target.closest('a')) {
                    const patientId = this.getAttribute('data-patient-id');
                    window.location.href = `../patients/patient_view.php?id=${patientId}`;
                }
            });
        });
        
        document.querySelectorAll('[data-record-id]').forEach(item => {
            item.addEventListener('click', function(e) {
                if (!e.target.closest('a')) {
                    const recordId = this.getAttribute('data-record-id');
                    window.location.href = `../medical_records/medical_record_view.php?id=${recordId}`;
                }
            });
        });
    }
};

// Counter Animation
const CounterAnimation = {
    animateCounters: () => {
        const counters = document.querySelectorAll('.stats-number-large[data-target]');
        
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
            if (isNaN(target)) return;
            
            DashboardUtils.animateCounter(counter, target, 2000);
        });
    },
    
    // Alternative counter animation with intersection observer
    setupIntersectionObserver: () => {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = parseInt(entry.target.getAttribute('data-target'));
                    if (!isNaN(target)) {
                        DashboardUtils.animateCounter(entry.target, target, 2000);
                        observer.unobserve(entry.target);
                    }
                }
            });
        }, { threshold: 0.5 });
        
        document.querySelectorAll('.stats-number-large[data-target]').forEach(counter => {
            observer.observe(counter);
        });
    }
};

// Auto-refresh functionality
const AutoRefresh = {
    interval: null,
    refreshTime: 5 * 60 * 1000, // 5 minutes
    
    start: () => {
        AutoRefresh.interval = setInterval(() => {
            AutoRefresh.refreshData();
        }, AutoRefresh.refreshTime);
    },
    
    stop: () => {
        if (AutoRefresh.interval) {
            clearInterval(AutoRefresh.interval);
            AutoRefresh.interval = null;
        }
    },
    
    refreshData: () => {
        console.log('Auto-refreshing dashboard data...');
        
        // Show subtle notification
        const refreshIndicator = document.createElement('div');
        refreshIndicator.className = 'position-fixed top-0 end-0 m-3 alert alert-info alert-dismissible fade show';
        refreshIndicator.style.zIndex = '9999';
        refreshIndicator.innerHTML = `
            <i class="bx bx-refresh bx-spin me-2"></i>
            Refreshing data...
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(refreshIndicator);
        
        // Simulate data refresh
        setTimeout(() => {
            refreshIndicator.remove();
        }, 2000);
    }
};

// Keyboard shortcuts
const KeyboardShortcuts = {
    init: () => {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K for global search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                GlobalSearch.show();
            }
            
            // Ctrl/Cmd + R for refresh (prevent default and use custom)
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                AutoRefresh.refreshData();
            }
            
            // Escape to close modals
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal.show');
                if (openModal) {
                    const modal = bootstrap.Modal.getInstance(openModal);
                    if (modal) modal.hide();
                }
            }
        });
    }
};

// Performance monitoring
const PerformanceMonitor = {
    init: () => {
        // Monitor page load time
        window.addEventListener('load', () => {
            const loadTime = performance.now();
            console.log(`Dashboard loaded in ${Math.round(loadTime)}ms`);
        });
        
        // Monitor memory usage (if available)
        if ('memory' in performance) {
            setInterval(() => {
                const memory = performance.memory;
                if (memory.usedJSHeapSize > memory.jsHeapSizeLimit * 0.9) {
                    console.warn('High memory usage detected');
                }
            }, 30000);
        }
    }
};

// Theme management
const ThemeManager = {
    init: () => {
        // Detect system theme preference
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
        
        prefersDark.addEventListener('change', (e) => {
            if (e.matches) {
                ThemeManager.applyDarkMode();
            } else {
                ThemeManager.applyLightMode();
            }
        });
    },
    
    applyDarkMode: () => {
        document.body.classList.add('dark-mode');
    },
    
    applyLightMode: () => {
        document.body.classList.remove('dark-mode');
    }
};

// Global functions (for backward compatibility)
function showGlobalSearch() {
    GlobalSearch.show();
}

function performGlobalSearch() {
    GlobalSearch.performSearch();
}

function toggleChartView(view) {
    ChartManager.toggleView(view);
}

function exportData(type) {
    DataManager.exportData(type);
}

function performBackup() {
    DataManager.performBackup();
}

// Main initialization
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard initializing...');
    
    try {
        // Initialize all modules
        GlobalSearch.init();
        ChartManager.init();
        ActivityManager.init();
        KeyboardShortcuts.init();
        PerformanceMonitor.init();
        ThemeManager.init();
        
        // Initialize AOS if available
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true,
                offset: 50
            });
        }
        
        // Start counter animations after a delay
        setTimeout(() => {
            CounterAnimation.animateCounters();
        }, 500);
        
        // Start auto-refresh
        AutoRefresh.start();
        
        console.log('Dashboard initialized successfully');
        
    } catch (error) {
        console.error('Dashboard initialization error:', error);
        DashboardUtils.showNotification('Dashboard initialization failed', 'error');
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    AutoRefresh.stop();
    ChartManager.destroy();
});

// Handle visibility change (pause/resume auto-refresh)
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        AutoRefresh.stop();
    } else {
        AutoRefresh.start();
    }
});

// Export for external use
window.Dashboard = {
    Utils: DashboardUtils,
    Search: GlobalSearch,
    Charts: ChartManager,
    Data: DataManager,
    Activity: ActivityManager,
    Counter: CounterAnimation,
    AutoRefresh: AutoRefresh,
    Keyboard: KeyboardShortcuts,
    Performance: PerformanceMonitor,
    Theme: ThemeManager
};