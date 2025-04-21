// Client-side validation for edit_user.php

document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        let valid = true;
        let errorMsg = '';

        // Username validation
        const username = document.getElementById('username');
        if (!username.value.trim()) {
            valid = false;
            errorMsg = 'Username cannot be empty.';
            username.classList.add('input-error');
        } else {
            username.classList.remove('input-error');
        }

        // Email validation
        const email = document.getElementById('email');
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email.value.trim() || !emailPattern.test(email.value.trim())) {
            valid = false;
            errorMsg = 'Please enter a valid email address.';
            email.classList.add('input-error');
        } else {
            email.classList.remove('input-error');
        }

        // Show error message if invalid
        let errorDiv = document.querySelector('.error-message');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            form.parentNode.insertBefore(errorDiv, form);
        }
        errorDiv.textContent = errorMsg;
        errorDiv.style.display = valid ? 'none' : 'block';

        if (!valid) {
            e.preventDefault();
        }
    });
});

// Optional: Add some basic error styling
const style = document.createElement('style');
style.innerHTML = `
.input-error {
    border: 1.5px solid #e74c3c !important;
    background: #fff6f6 !important;
}
`;
document.head.appendChild(style);
