// Admin-specific JavaScript functions

// Initialize all tooltips
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseover', function() {
            const tooltipText = this.getAttribute('data-tooltip');
            const tooltipElement = document.createElement('div');
            tooltipElement.className = 'custom-tooltip';
            tooltipElement.textContent = tooltipText;
            document.body.appendChild(tooltipElement);
            
            const rect = this.getBoundingClientRect();
            tooltipElement.style.top = `${rect.top - tooltipElement.offsetHeight - 5}px`;
            tooltipElement.style.left = `${rect.left + rect.width/2 - tooltipElement.offsetWidth/2}px`;
            
            this.addEventListener('mouseout', function() {
                document.body.removeChild(tooltipElement);
            });
        });
    });
}

// Confirm before executing destructive actions
function confirmAction(message) {
    return confirm(message || 'Are you sure you want to perform this action?');
}

// Initialize date pickers
function initDatePickers() {
    document.querySelectorAll('input[type="date"], input[type="datetime-local"]').forEach(input => {
        if (!input._flatpickr) {
            input._flatpickr = flatpickr(input, {
                enableTime: input.type === 'datetime-local',
                dateFormat: input.type === 'date' ? 'Y-m-d' : 'Y-m-d H:i',
                time_24hr: true
            });
        }
    });
}

// AJAX form submission
function handleAjaxForm(form, callback) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';
        
        fetch(form.action, {
            method: form.method,
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (typeof callback === 'function') {
                callback(data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });
}

// Initialize all admin functionality
document.addEventListener('DOMContentLoaded', function() {
    initTooltips();
    initDatePickers();
    
    // Handle all AJAX forms
    document.querySelectorAll('form.ajax-form').forEach(form => {
        handleAjaxForm(form, function(data) {
            if (data.success) {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else if (data.message) {
                    alert(data.message);
                }
            } else {
                alert(data.message || 'An error occurred');
            }
        });
    });
    
    // Confirm before delete
    document.querySelectorAll('form.delete-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirmAction('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });
});