document.addEventListener('DOMContentLoaded', function () {
    const exportButton = document.getElementById('export-button');

    if (exportButton) {
        exportButton.addEventListener('click', function () {
            const confirmation = confirm("Are you sure you want to export the survey results?");
            if (!confirmation) {
                event.preventDefault();
            }
        });
    }
});
