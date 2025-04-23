document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('bulkImportForm');
    var progressContainer = document.getElementById('progressContainer');
    var progressBar = document.getElementById('progressBar');
    var importBtn = document.getElementById('importBtn');
    var csvInput = document.getElementById('csv_file');

    function pollProgress() {
        fetch('add_users.php?bulk_progress=1')
            .then(res => res.json())
            .then(data => {
                progressBar.style.width = data.percent + '%';
                progressBar.textContent = data.percent + '% - ' + data.status;
                if (data.percent < 100) {
                    setTimeout(pollProgress, 500);
                } else {
                    importBtn.disabled = false;
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                }
            });
    }

    if (form && progressContainer && progressBar && importBtn && csvInput) {
        form.addEventListener('submit', function(e) {
            if (!csvInput.files.length) return;
            progressContainer.style.display = 'block';
            progressBar.style.width = '0%';
            progressBar.textContent = '0%';
            importBtn.disabled = true;

            var file = csvInput.files[0];
            var formData = new FormData();
            formData.append('csv_file', file);
            formData.append('csrf_token', form.querySelector('[name="csrf_token"]').value);
            formData.append('bulk_import', '1');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'add_users.php', true);

            xhr.onload = function() {
                // Start polling for progress
                pollProgress();
            };

            xhr.onerror = function() {
                progressBar.style.background = '#e74c3c';
                progressBar.textContent = 'Upload failed';
                importBtn.disabled = false;
            };

            xhr.send(formData);
            e.preventDefault();
        });
    }
});
