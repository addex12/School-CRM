document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar on mobile
    const sidebarToggle = document.createElement('div');
    sidebarToggle.className = 'sidebar-toggle';
    sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
    document.querySelector('.admin-header').prepend(sidebarToggle);
    
    sidebarToggle.addEventListener('click', function() {
        document.querySelector('.admin-dashboard').classList.toggle('sidebar-collapsed');
    });
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Notification dropdown
    const notifications = document.querySelector('.notifications');
    if (notifications) {
        notifications.addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('active');
        });
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        if (notifications) notifications.classList.remove('active');
    });
    
    // User profile dropdown
    const userProfile = document.querySelector('.user-profile');
    if (userProfile) {
        userProfile.addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('active');
        });
    }
    
    // Initialize charts
    if (typeof initCharts === 'function') {
        initCharts();
    }
    
    // Add animation to widgets on scroll
    const animateOnScroll = function() {
        const widgets = document.querySelectorAll('.dashboard-widget');
        widgets.forEach(widget => {
            const widgetPosition = widget.getBoundingClientRect().top;
            const screenPosition = window.innerHeight / 1.3;
            
            if (widgetPosition < screenPosition) {
                widget.style.opacity = '1';
                widget.style.transform = 'translateY(0)';
            }
        });
    };
    
    // Set initial state for animation
    const widgets = document.querySelectorAll('.dashboard-widget');
    widgets.forEach(widget => {
        widget.style.opacity = '0';
        widget.style.transform = 'translateY(20px)';
        widget.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    });
    
    // Run once on load
    animateOnScroll();
    
    // Run on scroll
    window.addEventListener('scroll', animateOnScroll);
});