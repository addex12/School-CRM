document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('audit-log-search');
    const tableRows = document.querySelectorAll('.audit-log-table tbody tr');

    // Filter table rows based on search input
    searchInput.addEventListener('input', function () {
        const query = searchInput.value.toLowerCase();
        tableRows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            row.style.display = rowText.includes(query) ? '' : 'none';
        });
    });

    // Add export functionality
    document.getElementById('export-audit-log').addEventListener('click', function () {
        const table = document.querySelector('.audit-log-table');
        const rows = Array.from(table.rows);
        const csvContent = rows.map(row => {
            const cells = Array.from(row.cells);
            return cells.map(cell => `"${cell.textContent}"`).join(',');
        }).join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'audit_log.csv';
        link.click();
        URL.revokeObjectURL(url);
    });
});
