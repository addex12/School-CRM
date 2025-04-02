document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            // Client-side validation
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('Please enter both username and password');
                return false;
            }
            
            // You could add more client-side validation here
            return true;
        });
    }
    
    // Focus on username field when page loads
    const usernameField = document.getElementById('username');
    if (usernameField) {
        usernameField.focus();
    }
});