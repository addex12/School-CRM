document.addEventListener('DOMContentLoaded', function() {
    // Initialize the reply form with auto-resizing textarea
    const replyTextarea = document.getElementById('reply_message');
    if (replyTextarea) {
        // Auto-resize textarea as user types
        replyTextarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        
        // Focus the textarea when page loads
        replyTextarea.focus();
    }
    
    // Handle status update checkbox toggle
    const statusCheckbox = document.getElementById('update_status');
    const statusSelect = document.getElementById('ticket_status');
    
    if (statusCheckbox && statusSelect) {
        statusCheckbox.addEventListener('change', function() {
            statusSelect.disabled = !this.checked;
        });
        
        // Initialize disabled state based on checkbox
        statusSelect.disabled = !statusCheckbox.checked;
    }
    
    // Handle form submission with loading state
    const replyForm = document.querySelector('.reply-form');
    if (replyForm) {
        replyForm.addEventListener('submit', function(e) {
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            }
        });
    }
    
    // Scroll to bottom of replies section
    const repliesContainer = document.querySelector('.ticket-replies');
    if (repliesContainer) {
        repliesContainer.scrollTop = repliesContainer.scrollHeight;
    }
    
    // Handle attachment preview if any
    const attachmentLinks = document.querySelectorAll('.attachment-link');
    attachmentLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const ext = this.href.split('.').pop().toLowerCase();
            if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                e.preventDefault();
                // Show image in a modal or new tab
                window.open(this.href, '_blank');
            }
            // Other file types will download normally
        });
    });
});