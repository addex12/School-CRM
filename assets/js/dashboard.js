document.addEventListener('DOMContentLoaded', function () {
    console.log('Dashboard.js loaded');

    // Initialize charts
    const chartCanvas = document.getElementById('surveyChart');
    if (chartCanvas) {
        const chartData = JSON.parse(chartCanvas.getAttribute('data-chart'));
        const labels = chartData.map(item => item.category);
        const data = chartData.map(item => item.survey_count);

        new Chart(chartCanvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Survey Count',
                    data: data,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: true }
                }
            }
        });
    }

    // Refresh widgets dynamically
    const refreshWidgets = async () => {
        const widgets = document.querySelectorAll('.dashboard-widget');
        widgets.forEach(async widget => {
            const query = widget.getAttribute('data-query');
            try {
                const response = await fetch('/api/widget-data', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ query }),
                });
                const data = await response.json();
                widget.querySelector('h3').textContent = data.count || 'Error';
            } catch (error) {
                console.error('Error refreshing widget:', error);
            }
        });
    };

    // Refresh widgets every 5 minutes
    setInterval(refreshWidgets, 300000);

    // Add interactivity to sections
    const sectionHeaders = document.querySelectorAll('.dashboard-section h2');
    sectionHeaders.forEach(header => {
        header.addEventListener('click', () => {
            const section = header.parentElement;
            section.classList.toggle('collapsed');
        });
    });

    // Load widget counts dynamically
    document.querySelectorAll('.dashboard-widget').forEach(widget => {
        const query = widget.dataset.query;
        fetch(`../api/widget_data.php?query=${encodeURIComponent(query)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Failed to fetch widget data: ${response.statusText}`);
                }
                return response.json();
            })
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
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Failed to fetch section data: ${response.statusText}`);
                }
                return response.json();
            })
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
    // Load recent activity data dynamically
    document.querySelectorAll('.recent-activity').forEach(table => {
        const query = table.dataset.query;
        fetch(`../api/recent_activity.php?query=${encodeURIComponent(query)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Failed to fetch recent activity data: ${response.statusText}`);
                }
                return response.json();
            })
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
                console.error('Error loading recent activity data:', error);
                const tbody = table.querySelector('tbody');
                tbody.innerHTML = '<tr><td colspan="100%">Error loading data</td></tr>';
            });
    });
});


