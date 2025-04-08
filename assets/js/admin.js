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
$(document).ready(function() {
    // Sidebar Toggle Functionality
    $('#sidebarToggle').click(function() {
        $('.admin-sidebar').toggleClass('collapsed');
    });

    // Initialize collapse functionality for sidebar menu
    $('.category-header').on('click', function() {
        var targetId = $(this).data('target');
        $(targetId).collapse('toggle');
        
        // Toggle icon
        $(this).find('.collapse-icon').toggleClass('fa-chevron-down fa-chevron-up');
    });

    // Active menu item highlighting
    $('.sidebar-menu a').each(function() {
        var currentPage = basename(window.location.pathname);
        var link = $(this).attr('href');
        if (link === currentPage) {
            $(this).closest('li').addClass('active');
            // Also highlight parent menu if it's a submenu item
            if ($(this).closest('.submenu').length > 0) {
                $(this).closest('.menu-category').find('.category-header').addClass('active');
            }
        }
    });

    // Logout confirmation
    $('.logout').click(function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = $(this).attr('href');
        }
    });
});
$(document).ready(function() {
    // Sidebar Toggle Functionality
    $('#sidebarToggle').click(function() {
        $('.admin-sidebar').toggleClass('collapsed');
    });

    // Initialize collapse functionality for sidebar menu
    $('.category-header').on('click', function() {
        var targetId = $(this).data('target');
        $(targetId).collapse('toggle');
        
        // Toggle icon
        $(this).find('.collapse-icon').toggleClass('fa-chevron-down fa-chevron-up');
    });

    // Active menu item highlighting
    $('.sidebar-menu a').each(function() {
        var currentPage = basename(window.location.pathname);
        var link = $(this).attr('href');
        if (link === currentPage) {
            $(this).closest('li').addClass('active');
            // Also highlight parent menu if it's a submenu item
            if ($(this).closest('.submenu').length > 0) {
                $(this).closest('.menu-category').find('.category-header').addClass('active');
            }
        }
    });

    // Logout confirmation
    $('.logout').click(function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = $(this).attr('href');
        }
    });
});
});
    // Add new product modal

