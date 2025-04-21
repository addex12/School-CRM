// backup.js - Handles backup progress UI and AJAX request

document.addEventListener('DOMContentLoaded', function () {
    const backupForm = document.getElementById('backupForm');
    const backupBtn = document.getElementById('backupBtn');
    const backupProgress = document.getElementById('backupProgress');
    const progressBar = document.getElementById('progressBar');
    const progressStatus = document.getElementById('progressStatus');

    if (backupForm) {
        backupForm.addEventListener('submit', function (e) {
            e.preventDefault();
            backupBtn.disabled = true;
            backupProgress.style.display = 'block';
            progressBar.style.width = '10%';
            progressStatus.textContent = 'Backup started...';

            // Simulate progress bar (since PHP cannot stream progress natively)
            let progress = 10;
            const interval = setInterval(() => {
                if (progress < 90) {
                    progress += Math.floor(Math.random() * 10) + 1;
                    progressBar.style.width = progress + '%';
                    progressStatus.textContent = 'Backing up... (' + progress + '%)';
                }
            }, 500);

            // Send AJAX request
            fetch('backup.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'backup_system=1'
            })
            .then(response => response.text())
            .then(html => {
                clearInterval(interval);
                progressBar.style.width = '100%';
                progressStatus.textContent = 'Backup complete! Reloading...';
                setTimeout(() => {
                    window.location.reload();
                }, 1200);
            })
            .catch(err => {
                clearInterval(interval);
                progressBar.style.width = '100%';
                progressStatus.textContent = 'Backup failed!';
                backupBtn.disabled = false;
            });
        });
    }
});
