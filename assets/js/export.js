document.addEventListener('DOMContentLoaded', function() {
    // Initialize form validation
    const exportForm = document.querySelector('.export-form');
    if (exportForm) {
        exportForm.addEventListener('submit', function(e) {
            const surveySelect = document.getElementById('survey_id');
            if (!surveySelect.value) {
                e.preventDefault();
                alert('Please select a survey to export');
                surveySelect.focus();
                return;
            }
            
            // Show loading indicator
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparing Export...';
            }
        });
    }
    
    // Format selection animation
    const formatOptions = document.querySelectorAll('.format-option');
    formatOptions.forEach(option => {
        option.addEventListener('click', function() {
            formatOptions.forEach(opt => {
                opt.querySelector('.format-card').classList.remove('selected');
            });
            this.querySelector('.format-card').classList.add('selected');
        });
    });
    
    // Show warning for large exports
    const surveySelect = document.getElementById('survey_id');
    if (surveySelect) {
        surveySelect.addEventListener('change', function() {
            // In a real implementation, you might fetch response count via AJAX
            // Here we just show a generic warning for any selected survey
            const warning = document.querySelector('.export-warning');
            if (warning) {
                warning.style.display = this.value ? 'block' : 'none';
            }
        });
    }
});