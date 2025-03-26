document.addEventListener('DOMContentLoaded', () => {
    const emailFileInput = document.getElementById('email_file');

    emailFileInput.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (file && file.type !== 'text/csv') {
            alert('Please upload a valid CSV file.');
            emailFileInput.value = '';
        }
    });
});
