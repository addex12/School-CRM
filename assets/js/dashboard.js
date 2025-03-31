document.addEventListener('DOMContentLoaded', function () {
    // Load widget counts dynamically
    document.querySelectorAll('.dashboard-widget').forEach(widget => {
        const query = widget.dataset.query;
        fetch(`../api/widget_data.php?query=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                widget.querySelector('.widget-count').textContent = data.count || 0;
            })
            .catch(error => {
                console.error('Error loading widget data:', error);
                widget.querySelector('.widget-count').textContent = 'Error';
            });
    });

    // Load section table data dynamically
    document.querySelectorAll('.table').forEach(table => {
        const query = table.dataset.query;
        fetch(`../api/section_data.php?query=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                const tbody = table.querySelector('tbody');
                tbody.innerHTML = '';
                if (data.rows && data.rows.length > 0) {
                    data.rows.forEach(row => {
                        const tr = document.createElement('tr');
                        data.columns.forEach(column => {
                            const td = document.createElement('td');
                            td.textContent = row[column] || 'N/A';
                            tr.appendChild(td);
                        });
                        tbody.appendChild(tr);
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="' + data.columns.length + '">No data available</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error loading section data:', error);
                const tbody = table.querySelector('tbody');
                tbody.innerHTML = '<tr><td colspan="100%">Error loading data</td></tr>';
            });
    });
}); // Ensure this closing brace matches the opening function declaration
