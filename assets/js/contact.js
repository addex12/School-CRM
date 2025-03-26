document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contact-form');
    const fileInput = document.getElementById('attachment');
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    form.addEventListener('submit', function(e) {
        // Clear previous errors
        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        
        // Validate email
        const email = document.getElementById('email');
        if (!email.value.includes('@') || !email.value.includes('.')) {
            e.preventDefault();
            email.classList.add('is-invalid');
            return;
        }
        
        // Validate subject
        const subject = document.getElementById('subject');
        if (subject.value.trim().length < 5) {
            e.preventDefault();
            subject.classList.add('is-invalid');
            return;
        }
        
        // Validate message
        const message = document.getElementById('message');
        if (message.value.trim().length < 10) {
            e.preventDefault();
            message.classList.add('is-invalid');
            return;
        }
        
        // Validate file if present
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            
            if (!allowedTypes.includes(file.type)) {
                e.preventDefault();
                alert('Only PDF, JPG, PNG, or DOCX files are allowed');
                fileInput.classList.add('is-invalid');
                return;
            }
            
            if (file.size > maxSize) {
                e.preventDefault();
                alert('File size exceeds 5MB limit');
                fileInput.classList.add('is-invalid');
                return;
            }
        }
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';
    });
});