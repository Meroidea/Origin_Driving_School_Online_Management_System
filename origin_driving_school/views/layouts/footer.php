<?php
/**
 * Footer Layout - Origin Driving School Management System
 * 
 * Common footer for all dashboard pages
 * 
 * File path: views/layouts/footer.php
 * 
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 1.0
 */
?>

    <!-- Footer Scripts -->
    <script src="<?php echo asset('js/script.js'); ?>"></script>
    <script src="<?php echo asset('js/dashboard.js'); ?>"></script>
    
    <!-- Common JavaScript Functions -->
    <script>
        // Auto-hide flash messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.transition = 'opacity 0.3s ease';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
            
            // Remove page loader
            const pageLoader = document.getElementById('pageLoader');
            if (pageLoader) {
                pageLoader.classList.remove('active');
            }
        });
        
        // Confirm before deleting
        function confirmDelete(message) {
            return confirm(message || 'Are you sure you want to delete this? This action cannot be undone.');
        }
        
        // Print page function
        function printPage() {
            window.print();
        }
        
        // Export table to CSV
        function exportTableToCSV(filename) {
            const table = document.querySelector('table');
            if (!table) {
                alert('No table found to export.');
                return;
            }
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                let row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    // Skip action columns
                    if (cols[j].classList.contains('actions') || 
                        cols[j].textContent.trim().toLowerCase() === 'actions') {
                        continue;
                    }
                    let data = cols[j].textContent.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ');
                    data = data.replace(/"/g, '""');
                    row.push('"' + data + '"');
                }
                csv.push(row.join(','));
            }
            
            downloadCSV(csv.join('\n'), filename);
        }
        
        function downloadCSV(csv, filename) {
            let csvFile;
            let downloadLink;
            
            csvFile = new Blob([csv], {type: 'text/csv'});
            downloadLink = document.createElement('a');
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
        
        // Show/hide loading spinner
        function showLoader() {
            const loader = document.getElementById('pageLoader');
            if (loader) loader.classList.add('active');
        }
        
        function hideLoader() {
            const loader = document.getElementById('pageLoader');
            if (loader) loader.classList.remove('active');
        }
        
        // Form validation helper
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (!form) return false;
            
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('error');
                    isValid = false;
                    
                    // Remove error class on input
                    field.addEventListener('input', function() {
                        this.classList.remove('error');
                    }, {once: true});
                }
            });
            
            if (!isValid) {
                alert('Please fill in all required fields.');
            }
            
            return isValid;
        }
        
        // Format currency
        function formatCurrency(amount) {
            return '<?php echo CURRENCY_SYMBOL; ?>' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }
        
        // Show notification
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '10000';
            notification.style.minWidth = '300px';
            notification.style.animation = 'slideInRight 0.3s ease';
            
            const icon = type === 'success' ? 'check-circle' : 
                        type === 'error' ? 'exclamation-circle' : 
                        type === 'warning' ? 'exclamation-triangle' : 'info-circle';
            
            notification.innerHTML = `
                <i class="fas fa-${icon}"></i>
                <span>${message}</span>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.transition = 'opacity 0.3s ease';
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }
        
        // Back button functionality
        function goBack() {
            window.history.back();
        }
        
        // Confirm navigation away from unsaved form
        let formChanged = false;
        
        function trackFormChanges(formId) {
            const form = document.getElementById(formId);
            if (!form) return;
            
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('change', () => {
                    formChanged = true;
                });
            });
            
            window.addEventListener('beforeunload', (e) => {
                if (formChanged) {
                    e.preventDefault();
                    e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                    return e.returnValue;
                }
            });
            
            // Reset on form submit
            form.addEventListener('submit', () => {
                formChanged = false;
            });
        }
        
        // Search/filter table
        function filterTable(searchInputId, tableId) {
            const input = document.getElementById(searchInputId);
            const table = document.getElementById(tableId);
            
            if (!input || !table) return;
            
            input.addEventListener('keyup', function() {
                const filter = this.value.toUpperCase();
                const rows = table.getElementsByTagName('tr');
                
                for (let i = 1; i < rows.length; i++) {
                    let visible = false;
                    const cells = rows[i].getElementsByTagName('td');
                    
                    for (let j = 0; j < cells.length; j++) {
                        const cell = cells[j];
                        if (cell) {
                            const textValue = cell.textContent || cell.innerText;
                            if (textValue.toUpperCase().indexOf(filter) > -1) {
                                visible = true;
                                break;
                            }
                        }
                    }
                    
                    rows[i].style.display = visible ? '' : 'none';
                }
            });
        }
        
        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + K for search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = document.querySelector('input[type="search"], input[name="search"]');
                if (searchInput) searchInput.focus();
            }
            
            // Ctrl/Cmd + P for print
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
        
        // Initialize tooltips if needed
        function initTooltips() {
            const tooltips = document.querySelectorAll('[data-tooltip]');
            tooltips.forEach(element => {
                element.setAttribute('title', element.getAttribute('data-tooltip'));
            });
        }
        
        // Call on load
        document.addEventListener('DOMContentLoaded', initTooltips);
    </script>
    
    <!-- Page-specific scripts can be added before this footer -->

</body>
</html>