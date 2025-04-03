// assets/js/script.js
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const spinner = submitBtn.querySelector('.spinner-border');
    
    submitBtn.disabled = true;
    spinner.classList.remove('d-none');
    
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form)
        });

        if (!response.ok) {
            throw new Error('Login failed');
        }
        
        window.location.href = response.url;
    } catch (error) {
        spinner.classList.add('d-none');
        submitBtn.disabled = false;
        showErrorToast('Invalid email or password');
    }
});

function showErrorToast(message) {
    const toast = document.createElement('div');
    toast.className = 'error-toast';
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.remove(), 3000);
}