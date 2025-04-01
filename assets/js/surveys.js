document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('search-surveys');
    const filterStatus = document.getElementById('filter-status');
    const surveySections = document.querySelectorAll('.survey-section');
    const bulkActionSelect = document.getElementById('bulk-action');
    const bulkActionButton = document.getElementById('apply-bulk-action');
    const paginationContainer = document.getElementById('pagination-container');

    let currentPage = 1;
    const rowsPerPage = 10;

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

        paginateSurveys();
    }

    function paginateSurveys() {
        surveySections.forEach(section => {
            const rows = section.querySelectorAll('tbody tr');
            const totalRows = rows.length;
            const totalPages = Math.ceil(totalRows / rowsPerPage);

            rows.forEach((row, index) => {
                row.style.display = index >= (currentPage - 1) * rowsPerPage && index < currentPage * rowsPerPage ? '' : 'none';
            });

            renderPagination(totalPages);
        });
    }

    function renderPagination(totalPages) {
        paginationContainer.innerHTML = '';
        for (let i = 1; i <= totalPages; i++) {
            const pageButton = document.createElement('button');
            pageButton.textContent = i;
            pageButton.className = i === currentPage ? 'active' : '';
            pageButton.addEventListener('click', () => {
                currentPage = i;
                paginateSurveys();
            });
            paginationContainer.appendChild(pageButton);
        }
    }

    function applyBulkAction() {
        const selectedAction = bulkActionSelect.value;
        const selectedSurveys = document.querySelectorAll('.survey-checkbox:checked');

        if (!selectedAction || selectedSurveys.length === 0) {
            alert('Please select an action and at least one survey.');
            return;
        }

        const surveyIds = Array.from(selectedSurveys).map(checkbox => checkbox.value);

        if (selectedAction === 'delete') {
            if (confirm('Are you sure you want to delete the selected surveys?')) {
                // Perform delete action
                console.log('Deleting surveys:', surveyIds);
                // Add AJAX request to delete surveys
            }
        } else if (selectedAction === 'activate') {
            // Perform activate action
            console.log('Activating surveys:', surveyIds);
            // Add AJAX request to activate surveys
        } else if (selectedAction === 'deactivate') {
            // Perform deactivate action
            console.log('Deactivating surveys:', surveyIds);
            // Add AJAX request to deactivate surveys
        }
    }

    function sortSurveys(columnIndex, ascending = true) {
        surveySections.forEach(section => {
            const rows = Array.from(section.querySelectorAll('tbody tr'));
            rows.sort((a, b) => {
                const aText = a.children[columnIndex].textContent.trim().toLowerCase();
                const bText = b.children[columnIndex].textContent.trim().toLowerCase();

                if (aText < bText) return ascending ? -1 : 1;
                if (aText > bText) return ascending ? 1 : -1;
                return 0;
            });

            const tbody = section.querySelector('tbody');
            rows.forEach(row => tbody.appendChild(row));
        });
    }

    // Event Listeners
    searchInput.addEventListener('input', filterSurveys);
    filterStatus.addEventListener('change', filterSurveys);
    bulkActionButton.addEventListener('click', applyBulkAction);

    document.querySelectorAll('.sortable').forEach((header, index) => {
        let ascending = true;
        header.addEventListener('click', () => {
            sortSurveys(index, ascending);
            ascending = !ascending;
        });
    });

    // Initialize pagination and filtering
    paginateSurveys();
    filterSurveys();
});
