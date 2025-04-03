document.addEventListener('DOMContentLoaded', () => {
    // Password visibility toggle
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', (e) => {
            const input = e.currentTarget.previousElementSibling;
            const isHidden = input.type === 'password';
            
            input.type = isHidden ? 'text' : 'password';
            e.currentTarget.innerHTML = `<i class="fas fa-eye${isHidden ? '-slash' : ''}"></i>`;
            e.currentTarget.setAttribute('aria-label', 
                `${isHidden ? 'Hide' : 'Show'} password`);
        });
    });

    // Form validation
    document.querySelector('form').addEventListener('submit', (e) => {
        const form = e.target;
        const username = form.username.value.trim();
        const password = form.password.value.trim();
        
        if (!username || !password) {
            e.preventDefault();
            alert('Please fill in all fields');
        }
    });

    // Service worker for PWA
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js');
    }
});