// assets/js/enhanced-functions.js - Enhanced Core Functions
class MedicalSystemEnhancer {
    constructor() {
        this.init();
    }
    
    init() {
        console.log('🚀 Medical System Enhancer Loading...');
        
        // Initialize all enhancements
        this.initFormValidation();
        this.initTableEnhancements();
        this.initModalEnhancements();
        this.initNotificationSystem();
        this.initLoadingStates();
        this.initSearchAndFilter();
        this.initTooltips();
        this.initConfirmDialogs();
        
        console.log('✅ Medical System Enhanced!');
    }
    
    // Enhanced Form Validation
    initFormValidation() {
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            // Real-time validation
            const inputs = form.querySelectorAll('input, select, textarea');
            
            inputs.forEach(input => {
                // Remove existing listeners to prevent duplicates
                input.removeEventListener('blur', this.validateField);
                input.removeEventListener('input', this.clearFieldError);
                
                // Add new listeners
                input.addEventListener('blur', (e) => this.validateField(e));
                input.addEventListener('input', (e) => this.clearFieldError(e));
            });
            
            // Enhanced form submission
            form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        });
    }
    
    validateField(e) {
        const field = e.target;
        const value = field.value.trim();
        const fieldType = field.type;
        const isRequired = field.hasAttribute('required');
        
        // Clear previous errors
        this.clearFieldError(e);
        
        let isValid = true;
        let errorMessage = '';
        
        // Required field validation
        if (isRequired && !value) {
            isValid = false;
            errorMessage = 'This field is required';
        }
        
        // Email validation
        else if (fieldType === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }
        }
        
        // Phone validation
        else if (field.name === 'phone' && value) {
            const phoneRegex = /^[\d\s\-\(\)\+]{10,}$/;
            if (!phoneRegex.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid phone number';
            }
        }
        
        // Password validation
        else if (fieldType === 'password' && value) {
            if (value.length < 8) {
                isValid = false;
                errorMessage = 'Password must be at least 8 characters long';
            } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(value)) {
                isValid = false;
                errorMessage = 'Password must contain uppercase, lowercase, and number';
            }
        }
        
        // Age validation
        else if (field.name === 'age' && value) {
            const age = parseInt(value);
            if (age < 0 || age > 150) {
                isValid = false;
                errorMessage = 'Please enter a valid age (0-150)';
            }
        }
        
        // Date validation
        else if (fieldType === 'date' && value) {
            const date = new Date(value);
            const today = new Date();
            
            if (field.name === 'dob' && date > today) {
                isValid = false;
                errorMessage = 'Date of birth cannot be in the future';
            }
        }
        
        // Show error if invalid
        if (!isValid) {
            this.showFieldError(field, errorMessage);
        }
        
        return isValid;
    }
    
    clearFieldError(e) {
        const field = e.target;
        field.classList.remove('is-invalid');
        
        const errorElement = field.parentNode.querySelector('.invalid-feedback');
        if (errorElement) {
            errorElement.remove();
        }
    }
    
    showFieldError(field, message) {
        field.classList.add('is-invalid');
        
        // Remove existing error message
        const existingError = field.parentNode.querySelector('.invalid-feedback');
        if (existingError) {
            existingError.remove();
        }
        
        // Add new error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }
    
    handleFormSubmit(e) {
        const form = e.target;
        const submitButton = form.querySelector('button[type="submit"]');
        
        // Validate all fields
        let isFormValid = true;
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        
        inputs.forEach(input => {
            const fakeEvent = { target: input };
            if (!this.validateField(fakeEvent)) {
                isFormValid = false;
            }
        });
        
        if (!isFormValid) {
            e.preventDefault();
            this.showNotification('Please correct the errors in the form', 'error');
            return false;
        }
        
        // Show loading state
        if (submitButton) {
            this.setButtonLoading(submitButton, true);
            
            // Reset button after 5 seconds (fallback)
            setTimeout(() => {
                this.setButtonLoading(submitButton, false);
            }, 5000);
        }
        
        return true;
    }
    
    // Enhanced Table Features
    initTableEnhancements() {
        const tables = document.querySelectorAll('table');
        
        tables.forEach(table => {
            // Add hover effects
            this.addTableHoverEffects(table);
            
            // Add sorting capability
            this.addTableSorting(table);
            
            // Add row selection
            this.addTableRowSelection(table);
        });
    }
    
    addTableHoverEffects(table) {
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            row.addEventListener('mouseenter', () => {
                row.style.backgroundColor = '#f8f9fa';
                row.style.transform = 'scale(1.01)';
                row.style.transition = 'all 0.2s ease';
            });
            
            row.addEventListener('mouseleave', () => {
                row.style.backgroundColor = '';
                row.style.transform = '';
            });
        });
    }
    
    addTableSorting(table) {
        const headers = table.querySelectorAll('th');
        
        headers.forEach((header, index) => {
            // Skip action columns
            if (header.textContent.toLowerCase().includes('action')) return;
            
            header.style.cursor = 'pointer';
            header.style.userSelect = 'none';
            header.addEventListener('click', () => this.sortTable(table, index));
            
            // Add sort indicator
            const sortIcon = document.createElement('i');
            sortIcon.className = 'bx bx-sort ms-1';
            sortIcon.style.fontSize = '14px';
            header.appendChild(sortIcon);
        });
    }
    
    sortTable(table, columnIndex) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const header = table.querySelectorAll('th')[columnIndex];
        const sortIcon = header.querySelector('i');
        
        // Reset other sort icons
        table.querySelectorAll('th i').forEach(icon => {
            if (icon !== sortIcon) {
                icon.className = 'bx bx-sort ms-1';
            }
        });
        
        // Determine sort direction
        const isAscending = sortIcon.classList.contains('bx-sort') || sortIcon.classList.contains('bx-sort-down');
        
        // Sort rows
        rows.sort((a, b) => {
            const aValue = a.cells[columnIndex].textContent.trim();
            const bValue = b.cells[columnIndex].textContent.trim();
            
            // Try to parse as numbers
            const aNum = parseFloat(aValue);
            const bNum = parseFloat(bValue);
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return isAscending ? aNum - bNum : bNum - aNum;
            }
            
            // Date comparison
            const aDate = new Date(aValue);
            const bDate = new Date(bValue);
            
            if (!isNaN(aDate.getTime()) && !isNaN(bDate.getTime())) {
                return isAscending ? aDate - bDate : bDate - aDate;
            }
            
            // String comparison
            return isAscending ? 
                aValue.localeCompare(bValue) : 
                bValue.localeCompare(aValue);
        });
        
        // Update sort icon
        sortIcon.className = isAscending ? 'bx bx-sort-up ms-1' : 'bx bx-sort-down ms-1';
        
        // Reorder rows with animation
        rows.forEach((row, index) => {
            row.style.opacity = '0.5';
            setTimeout(() => {
                tbody.appendChild(row);
                row.style.opacity = '1';
            }, index * 20);
        });
    }
    
    addTableRowSelection(table) {
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            row.addEventListener('click', (e) => {
                // Skip if clicked on button or link
                if (e.target.closest('button, a')) return;
                
                // Toggle selection
                row.classList.toggle('table-active');
                
                // Update selection count
                this.updateSelectionCount(table);
            });
        });
        
        // Add select all functionality if checkbox exists
        const selectAllCheckbox = table.querySelector('th input[type="checkbox"]');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => {
                const isChecked = e.target.checked;
                const rowCheckboxes = table.querySelectorAll('tbody input[type="checkbox"]');
                
                rowCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                    checkbox.closest('tr').classList.toggle('table-active', isChecked);
                });
                
                this.updateSelectionCount(table);
            });
        }
    }
    
    updateSelectionCount(table) {
        const selectedRows = table.querySelectorAll('tbody tr.table-active');
        const countElement = table.closest('.card').querySelector('.selection-count');
        
        if (countElement) {
            countElement.textContent = `${selectedRows.length} selected`;
            countElement.style.display = selectedRows.length > 0 ? 'block' : 'none';
        }
    }
    
    // Enhanced Modal System
    initModalEnhancements() {
        const modals = document.querySelectorAll('.modal');
        
        modals.forEach(modal => {
            // Auto-focus first input when modal opens
            modal.addEventListener('shown.bs.modal', () => {
                const firstInput = modal.querySelector('input, select, textarea');
                if (firstInput) firstInput.focus();
            });
            
            // Reset form when modal closes
            modal.addEventListener('hidden.bs.modal', () => {
                const form = modal.querySelector('form');
                if (form) {
                    form.reset();
                    this.clearFormErrors(form);
                }
            });
        });
    }
    
    clearFormErrors(form) {
        const invalidInputs = form.querySelectorAll('.is-invalid');
        const errorMessages = form.querySelectorAll('.invalid-feedback');
        
        invalidInputs.forEach(input => input.classList.remove('is-invalid'));
        errorMessages.forEach(error => error.remove());
    }
    
    // Enhanced Notification System
    initNotificationSystem() {
        // Create notification container if it doesn't exist
        if (!document.querySelector('.notification-container')) {
            const container = document.createElement('div');
            container.className = 'notification-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 400px;
            `;
            document.body.appendChild(container);
        }
    }
    
    showNotification(message, type = 'info', duration = 5000) {
        const container = document.querySelector('.notification-container');
        
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show notification-item`;
        notification.style.cssText = `
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: none;
            border-radius: 8px;
        `;
        
        const icons = {
            success: 'bx-check-circle',
            error: 'bx-error-circle',
            warning: 'bx-error',
            info: 'bx-info-circle'
        };
        
        notification.innerHTML = `
            <i class="bx ${icons[type] || icons.info} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        container.appendChild(notification);
        
        // Auto-remove after duration
        if (duration > 0) {
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 150);
                }
            }, duration);
        }
        
        return notification;
    }
    
    // Loading States
    initLoadingStates() {
        // Add global loading overlay
        const loadingOverlay = document.createElement('div');
        loadingOverlay.id = 'global-loading';
        loadingOverlay.className = 'global-loading';
        loadingOverlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9998;
            backdrop-filter: blur(2px);
        `;
        
        loadingOverlay.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="mt-2 text-muted">Loading...</div>
            </div>
        `;
        
        document.body.appendChild(loadingOverlay);
    }
    
    showGlobalLoading(message = 'Loading...') {
        const overlay = document.getElementById('global-loading');
        const messageElement = overlay.querySelector('.text-muted');
        
        messageElement.textContent = message;
        overlay.style.display = 'flex';
    }
    
    hideGlobalLoading() {
        const overlay = document.getElementById('global-loading');
        overlay.style.display = 'none';
    }
    
    setButtonLoading(button, isLoading) {
        if (isLoading) {
            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                Loading...
            `;
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || button.innerHTML;
        }
    }
    
    // Enhanced Search and Filter
    initSearchAndFilter() {
        const searchInputs = document.querySelectorAll('[id*="search"], [class*="search"]');
        
        searchInputs.forEach(input => {
            // Add search icon if not present
            if (!input.parentNode.querySelector('.bx-search')) {
                const icon = document.createElement('i');
                icon.className = 'bx bx-search position-absolute';
                icon.style.cssText = 'right: 10px; top: 50%; transform: translateY(-50%); z-index: 5; color: #999;';
                
                input.parentNode.style.position = 'relative';
                input.parentNode.appendChild(icon);
                input.style.paddingRight = '35px';
            }
            
            // Debounced search
            let searchTimeout;
            input.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.performSearch(e.target);
                }, 300);
            });
        });
    }
    
    performSearch(searchInput) {
        const searchTerm = searchInput.value.toLowerCase();
        const targetTable = document.querySelector('table tbody');
        
        if (!targetTable) return;
        
        const rows = targetTable.querySelectorAll('tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const isVisible = text.includes(searchTerm);
            
            row.style.display = isVisible ? '' : 'none';
            
            if (isVisible) {
                visibleCount++;
                
                // Highlight search terms
                if (searchTerm) {
                    this.highlightSearchTerms(row, searchTerm);
                } else {
                    this.removeHighlights(row);
                }
            }
        });
        
        // Update result count
        this.updateSearchResults(visibleCount, rows.length);
    }
    
    highlightSearchTerms(element, searchTerm) {
        this.removeHighlights(element);
        
        const walker = document.createTreeWalker(
            element,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );
        
        const textNodes = [];
        let node;
        
        while (node = walker.nextNode()) {
            textNodes.push(node);
        }
        
        textNodes.forEach(textNode => {
            const text = textNode.textContent;
            const regex = new RegExp(`(${searchTerm})`, 'gi');
            
            if (regex.test(text)) {
                const highlightedText = text.replace(regex, '<mark class="search-highlight">$1</mark>');
                const span = document.createElement('span');
                span.innerHTML = highlightedText;
                textNode.parentNode.replaceChild(span, textNode);
            }
        });
    }
    
    removeHighlights(element) {
        const highlights = element.querySelectorAll('.search-highlight');
        highlights.forEach(highlight => {
            const parent = highlight.parentNode;
            parent.replaceChild(document.createTextNode(highlight.textContent), highlight);
            parent.normalize();
        });
    }
    
    updateSearchResults(visibleCount, totalCount) {
        let resultElement = document.querySelector('.search-results');
        
        if (!resultElement) {
            resultElement = document.createElement('div');
            resultElement.className = 'search-results text-muted small mt-2';
            
            const searchInput = document.querySelector('[id*="search"], [class*="search"]');
            if (searchInput) {
                searchInput.parentNode.appendChild(resultElement);
            }
        }
        
        if (visibleCount === totalCount) {
            resultElement.textContent = '';
        } else {
            resultElement.textContent = `Showing ${visibleCount} of ${totalCount} results`;
        }
    }
    
    // Enhanced Tooltips
    initTooltips() {
        // Initialize Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                delay: { show: 500, hide: 100 },
                boundary: 'viewport'
            });
        });
        
        // Add automatic tooltips for truncated text
        const textElements = document.querySelectorAll('td, .text-truncate');
        textElements.forEach(element => {
            if (element.scrollWidth > element.clientWidth) {
                element.setAttribute('data-bs-toggle', 'tooltip');
                element.setAttribute('title', element.textContent);
                new bootstrap.Tooltip(element);
            }
        });
    }
    
    // Enhanced Confirm Dialogs
    initConfirmDialogs() {
        // Replace default confirm dialogs with SweetAlert2
        const deleteButtons = document.querySelectorAll('[onclick*="confirm"], .btn-danger');
        
        deleteButtons.forEach(button => {
            if (button.getAttribute('onclick')) {
                const originalOnclick = button.getAttribute('onclick');
                button.removeAttribute('onclick');
                
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.showConfirmDialog('Delete Confirmation', 'Are you sure you want to delete this item?', 'warning')
                        .then((result) => {
                            if (result.isConfirmed) {
                                eval(originalOnclick);
                            }
                        });
                });
            }
        });
    }
    
    showConfirmDialog(title, text, icon = 'warning') {
        return Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, proceed!',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            focusCancel: true
        });
    }
    
    // Advanced Data Operations
    exportTableToCSV(tableId, filename = 'data.csv') {
        const table = document.getElementById(tableId) || document.querySelector('table');
        if (!table) return;
        
        let csv = [];
        const rows = table.querySelectorAll('tr');
        
        rows.forEach(row => {
            const cols = row.querySelectorAll('td, th');
            const rowData = [];
            
            cols.forEach(col => {
                // Skip action columns
                if (!col.textContent.toLowerCase().includes('action')) {
                    rowData.push('"' + col.textContent.replace(/"/g, '""') + '"');
                }
            });
            
            csv.push(rowData.join(','));
        });
        
        // Download CSV
        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        window.URL.revokeObjectURL(url);
        
        this.showNotification('Data exported successfully!', 'success');
    }
    
    printTable(tableId) {
        const table = document.getElementById(tableId) || document.querySelector('table');
        if (!table) return;
        
        const printWindow = window.open('', '_blank');
        const tableClone = table.cloneNode(true);
        
        // Remove action columns
        const actionHeaders = tableClone.querySelectorAll('th');
        const actionCells = tableClone.querySelectorAll('td');
        
        actionHeaders.forEach((header, index) => {
            if (header.textContent.toLowerCase().includes('action')) {
                header.remove();
                // Remove corresponding cells in all rows
                tableClone.querySelectorAll('tr').forEach(row => {
                    const cell = row.cells[index];
                    if (cell) cell.remove();
                });
            }
        });
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Print</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; font-weight: bold; }
                    @media print {
                        body { margin: 0; }
                        table { font-size: 12px; }
                    }
                </style>
            </head>
            <body>
                <h1>Medical System Report</h1>
                <p>Generated on: ${new Date().toLocaleString()}</p>
                ${tableClone.outerHTML}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        printWindow.print();
    }
    
    // Data refresh with loading state
    refreshData(url, targetSelector, showLoading = true) {
        if (showLoading) {
            this.showGlobalLoading('Refreshing data...');
        }
        
        return fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                const target = document.querySelector(targetSelector);
                if (target) {
                    target.innerHTML = html;
                    // Re-initialize enhancements for new content
                    this.initTableEnhancements();
                    this.initTooltips();
                }
                this.showNotification('Data refreshed successfully!', 'success');
            })
            .catch(error => {
                console.error('Error refreshing data:', error);
                this.showNotification('Failed to refresh data', 'error');
            })
            .finally(() => {
                if (showLoading) {
                    this.hideGlobalLoading();
                }
            });
    }
    
    // Form auto-save functionality
    initAutoSave(formSelector, saveUrl, interval = 30000) {
        const form = document.querySelector(formSelector);
        if (!form) return;
        
        let autoSaveTimer;
        let lastSavedData = new FormData(form);
        
        const autoSave = () => {
            const currentData = new FormData(form);
            
            // Check if data has changed
            let hasChanges = false;
            for (let [key, value] of currentData.entries()) {
                if (lastSavedData.get(key) !== value) {
                    hasChanges = true;
                    break;
                }
            }
            
            if (hasChanges) {
                fetch(saveUrl, {
                    method: 'POST',
                    body: currentData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        lastSavedData = new FormData(form);
                        this.showNotification('Draft saved automatically', 'info', 2000);
                    }
                })
                .catch(error => {
                    console.error('Auto-save failed:', error);
                });
            }
        };
        
        // Start auto-save timer
        autoSaveTimer = setInterval(autoSave, interval);
        
        // Save on form change
        form.addEventListener('input', () => {
            clearInterval(autoSaveTimer);
            autoSaveTimer = setInterval(autoSave, interval);
        });
        
        // Clear timer when form is submitted
        form.addEventListener('submit', () => {
            clearInterval(autoSaveTimer);
        });
    }
    
    // Quick actions helper
    addQuickActions() {
        const quickActionsHtml = `
            <div class="quick-actions-fab">
                <div class="fab-main" id="fabMain">
                    <i class="bx bx-plus"></i>
                </div>
                <div class="fab-menu" id="fabMenu">
                    <div class="fab-item" data-action="refresh">
                        <i class="bx bx-refresh"></i>
                        <span class="fab-tooltip">Refresh</span>
                    </div>
                    <div class="fab-item" data-action="export">
                        <i class="bx bx-download"></i>
                        <span class="fab-tooltip">Export</span>
                    </div>
                    <div class="fab-item" data-action="print">
                        <i class="bx bx-printer"></i>
                        <span class="fab-tooltip">Print</span>
                    </div>
                </div>
            </div>
        `;
        
        const fabStyles = `
            <style>
                .quick-actions-fab {
                    position: fixed;
                    bottom: 30px;
                    right: 30px;
                    z-index: 1000;
                }
                
                .fab-main {
                    width: 56px;
                    height: 56px;
                    background: linear-gradient(135deg, #696cff, #5a67d8);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-size: 24px;
                    cursor: pointer;
                    box-shadow: 0 4px 20px rgba(105, 108, 255, 0.4);
                    transition: all 0.3s ease;
                }
                
                .fab-main:hover {
                    transform: scale(1.1);
                    box-shadow: 0 6px 25px rgba(105, 108, 255, 0.6);
                }
                
                .fab-menu {
                    position: absolute;
                    bottom: 70px;
                    right: 0;
                    opacity: 0;
                    visibility: hidden;
                    transform: translateY(20px);
                    transition: all 0.3s ease;
                }
                
                .fab-menu.active {
                    opacity: 1;
                    visibility: visible;
                    transform: translateY(0);
                }
                
                .fab-item {
                    width: 48px;
                    height: 48px;
                    background: white;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-bottom: 10px;
                    cursor: pointer;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    transition: all 0.2s ease;
                    position: relative;
                }
                
                .fab-item:hover {
                    transform: scale(1.1);
                    background: #f8f9fa;
                }
                
                .fab-tooltip {
                    position: absolute;
                    right: 60px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: rgba(0,0,0,0.8);
                    color: white;
                    padding: 5px 10px;
                    border-radius: 4px;
                    font-size: 12px;
                    white-space: nowrap;
                    opacity: 0;
                    visibility: hidden;
                    transition: all 0.2s ease;
                }
                
                .fab-item:hover .fab-tooltip {
                    opacity: 1;
                    visibility: visible;
                }
                
                @media (max-width: 768px) {
                    .quick-actions-fab {
                        bottom: 20px;
                        right: 20px;
                    }
                    
                    .fab-main {
                        width: 48px;
                        height: 48px;
                        font-size: 20px;
                    }
                    
                    .fab-item {
                        width: 40px;
                        height: 40px;
                    }
                }
            </style>
        `;
        
        // Add styles to head
        document.head.insertAdjacentHTML('beforeend', fabStyles);
        
        // Add FAB to body
        document.body.insertAdjacentHTML('beforeend', quickActionsHtml);
        
        // Bind events
        const fabMain = document.getElementById('fabMain');
        const fabMenu = document.getElementById('fabMenu');
        
        fabMain.addEventListener('click', () => {
            fabMenu.classList.toggle('active');
            fabMain.style.transform = fabMenu.classList.contains('active') ? 'rotate(45deg)' : 'rotate(0deg)';
        });
        
        // FAB actions
        document.querySelectorAll('.fab-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const action = e.currentTarget.getAttribute('data-action');
                
                switch(action) {
                    case 'refresh':
                        location.reload();
                        break;
                    case 'export':
                        this.exportTableToCSV();
                        break;
                    case 'print':
                        this.printTable();
                        break;
                }
                
                // Close menu
                fabMenu.classList.remove('active');
                fabMain.style.transform = 'rotate(0deg)';
            });
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.quick-actions-fab')) {
                fabMenu.classList.remove('active');
                fabMain.style.transform = 'rotate(0deg)';
            }
        });
    }
}

// Initialize the enhancer
let medicalEnhancer;

document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        medicalEnhancer = new MedicalSystemEnhancer();
        
        // Expose globally for external use
        window.medicalEnhancer = medicalEnhancer;
        
        // Add quick actions if not mobile
        if (window.innerWidth > 768) {
            medicalEnhancer.addQuickActions();
        }
        
    }, 200);
});

// CSS for search highlights
const highlightStyles = document.createElement('style');
highlightStyles.textContent = `
    .search-highlight {
        background: #fff3cd;
        padding: 2px 4px;
        border-radius: 3px;
        font-weight: 600;
    }
    
    .notification-item {
        animation: slideInRight 0.3s ease;
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .table-active {
        background-color: rgba(105, 108, 255, 0.1) !important;
    }
    
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }
`;

document.head.appendChild(highlightStyles);