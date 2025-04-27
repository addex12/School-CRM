document.addEventListener('DOMContentLoaded', function () {
    console.log('Dashboard.js loaded');

    // Initialize charts
    const chartCanvas = document.getElementById('surveyChart');
    if (chartCanvas) {
        try {
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
                    },
                    maintainAspectRatio: false // Ensure charts adjust dynamically
                }
            });
        } catch (error) {
            console.error('Error initializing chart:', error);
        }
    }

    // Refresh widgets dynamically
    const refreshWidgets = async () => {
        const widgets = document.querySelectorAll('.dashboard-widget');
        widgets.forEach(async widget => {
            try {
                const query = widget.getAttribute('data-query');
                if (!query) {
                    console.warn('Widget missing data-query attribute:', widget);
                    return;
                }

                const response = await fetch('/api/widget-data', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ query }),
                });

                if (!response.ok) {
                    throw new Error(`Failed to fetch widget data: ${response.statusText}`);
                }

                const data = await response.json();
                const countElement = widget.querySelector('h3');
                if (countElement) {
                    countElement.textContent = data.count || 'Error';
                } else {
                    console.warn('Widget missing count element:', widget);
                }
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

    // Adjust sidebar and content dynamically
    const adjustLayout = () => {
        const sidebar = document.querySelector('.admin-sidebar');
        const mainContent = document.querySelector('.admin-main');
        if (window.innerWidth <= 992) {
            sidebar.style.position = 'absolute';
            mainContent.style.marginLeft = '0';
            mainContent.style.padding = '1rem';
        } else {
            sidebar.style.position = 'relative';
            mainContent.style.marginLeft = '250px';
            mainContent.style.padding = '2rem';
        }
    };

    // Call adjustLayout on load and resize
    adjustLayout();
    window.addEventListener('resize', adjustLayout);

    // Load widget counts dynamically
    document.querySelectorAll('.dashboard-widget').forEach(widget => {
        const query = widget.dataset.query;
        if (!query) {
            console.warn('Widget missing data-query attribute:', widget);
            return;
        }

        fetch(`../api/widget_data.php?query=${encodeURIComponent(query)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Failed to fetch widget data: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                const countElement = widget.querySelector('.widget-count');
                if (countElement) {
                    countElement.textContent = data.count || 0;
                } else {
                    console.warn('Widget missing count element:', widget);
                }
            })
            .catch(error => {
                console.error('Error loading widget data:', error);
                const countElement = widget.querySelector('.widget-count');
                if (countElement) {
                    countElement.textContent = 'Error';
                }
            });
    });

    // Load section table data dynamically
    document.querySelectorAll('.table').forEach(table => {
        const query = table.dataset.query;
        if (!query) {
            console.warn('Table missing data-query attribute:', table);
            return;
        }

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
        if (!query) {
            console.warn('Recent activity table missing data-query attribute:', table);
            return;
        }

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


