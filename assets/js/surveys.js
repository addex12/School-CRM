document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('search-surveys');
    const filterStatus = document.getElementById('filter-status');
    const surveySections = document.querySelectorAll('.survey-section');

    function filterSurveys() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedStatus = filterStatus.value;

        surveySections.forEach(section => {
            const status = section.getAttribute('data-status');
            const rows = section.querySelectorAll('tbody tr');
            let hasVisibleRows = false;

            rows.forEach(row => {
                const title = row.querySelector('td:first-child').textContent.toLowerCase();
                const matchesSearch = title.includes(searchTerm);
                const matchesStatus = !selectedStatus || status === selectedStatus;

                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                    hasVisibleRows = true;
                } else {
                    row.style.display = 'none';
                }
            });

            section.style.display = hasVisibleRows ? '' : 'none';
        });
    }

    searchInput.addEventListener('input', filterSurveys);
    filterStatus.addEventListener('change', filterSurveys);
});
