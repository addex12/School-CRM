document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const loginForm = document.getElementById('loginForm');
    const togglePassword = document.querySelector('.toggle-password');
    const passwordInput = document.getElementById('password');
    const rememberCheckbox = document.getElementById('remember');
    
    // Toggle password visibility
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }
    
    // Form validation
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            // Client-side validation
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            let isValid = true;
            
            // Clear previous errors
            document.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            
            // Validate username
            if (!username) {
                document.getElementById('username').classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate password
            if (!password) {
                document.getElementById('password').classList.add('is-invalid');
                isValid = false;
            } else if (password.length < 8) {
                document.getElementById('password').classList.add('is-invalid');
                showToast('Password must be at least 8 characters long', 'error');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                showToast('Please fill all required fields correctly', 'error');
            } else {
                // Show loading state
                const submitButton = loginForm.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
                }
                
                // Save remember me preference
                if (rememberCheckbox && rememberCheckbox.checked) {
                    localStorage.setItem('rememberUsername', username);
                } else {
                    localStorage.removeItem('rememberUsername');
                }
            }
        });
    }
    
    // Check for remembered username
    const rememberedUsername = localStorage.getItem('rememberUsername');
    if (rememberedUsername) {
        document.getElementById('username').value = rememberedUsername;
        document.getElementById('remember').checked = true;
    }
    
    // Add CSS for invalid fields
    const style = document.createElement('style');
    style.textContent = `
        .is-invalid {
            border-color: #e74c3c !important;
        }
        .is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.2) !important;
        }
    `;
    document.head.appendChild(style);
    
    // Toast notification function
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            </div>
            <div class="toast-message">${message}</div>
            <button class="toast-close">&times;</button>
        `;
        
        document.body.appendChild(toast);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
        
        // Manual close
        toast.querySelector('.toast-close').addEventListener('click', () => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        });
    }
    
    // Add toast styles
    const toastStyle = document.createElement('style');
    toastStyle.textContent = `
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            padding: 1rem;
            gap: 1rem;
            z-index: 1000;
            transform: translateX(150%);
            transition: transform 0.3s ease;
        }
        
        .toast.show {
            transform: translateX(0);
        }
        
        .toast.fade-out {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.3s, transform 0.3s;
        }
        
        .toast-success {
            border-left: 4px solid #2ecc71;
        }
        
        .toast-error {
            border-left: 4px solid #e74c3c;
        }
        
        .toast-icon {
            font-size: 1.5rem;
        }
        
        .toast-success .toast-icon {
            color: #2ecc71;
        }
        
        .toast-error .toast-icon {
            color: #e74c3c;
        }
        
        .toast-message {
            flex: 1;
        }
        
        .toast-close {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: #7f8c8d;
            padding: 0 0.5rem;
        }
    `;
    document.head.appendChild(toastStyle);
    
    // Show toast after adding to DOM
    setTimeout(() => {
        const toasts = document.querySelectorAll('.toast');
        toasts.forEach(toast => {
            setTimeout(() => toast.classList.add('show'), 100);
        });
    }, 100);
});