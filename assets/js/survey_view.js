document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts if we have responses
    if (surveyData.responseCount > 0) {
        initResponseTrendChart();
        initFieldCharts();
    }
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

function initResponseTrendChart() {
    const ctx = document.getElementById('responseTrendChart').getContext('2d');
    
    // In a real application, you would fetch this data from your server
    // This is just mock data for demonstration
    const labels = responseTrendData.labels;
    const data = responseTrendData.data;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Responses',
                data: data,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}

function initFieldCharts() {
    surveyData.fields.forEach(field => {
        if (surveyData.fieldData[field.id] && surveyData.fieldData[field.id].length > 0) {
            const ctx = document.getElementById(`fieldChart-${field.id}`);
            if (!ctx) return;
            
            const fieldData = surveyData.fieldData[field.id];
            const labels = fieldData.map(item => item.field_value);
            const data = fieldData.map(item => item.count);
            
            let chartType = 'bar';
            if (field.field_type === 'radio' || field.field_type === 'select') {
                chartType = 'pie';
            } else if (field.field_type === 'rating') {
                chartType = 'doughnut';
            }
            
            new Chart(ctx.getContext('2d'), {
                type: chartType,
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: getChartColors(labels.length),
                        borderColor: '#fff',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        datalabels: {
                            display: chartType !== 'bar',
                            formatter: (value, context) => {
                                const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${percentage}%`;
                            },
                            color: '#fff',
                            font: {
                                weight: 'bold'
                            }
                        }
                    },
                    scales: chartType === 'bar' ? {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    } : {}
                },
                plugins: [ChartDataLabels]
            });
        }
    });
}

function getChartColors(count) {
    const colors = [
        '#4BC0C0', '#36A2EB', '#FFCE56', '#FF6384', '#9966FF',
        '#FF9F40', '#8AC249', '#EA5545', '#F46A9B', '#EF9B20',
        '#EDBF33', '#87BC45', '#27AEEF', '#B33DC6'
    ];
    
    // If we need more colors than we have, repeat the palette
    const result = [];
    for (let i = 0; i < count; i++) {
        result.push(colors[i % colors.length]);
    }
    
    return result;
}

// Helper function to get field type icon (should match PHP function)
function getFieldTypeIcon(type) {
    const icons = {
        'text': 'fa-font',
        'textarea': 'fa-align-left',
        'radio': 'fa-dot-circle',
        'checkbox': 'fa-check-square',
        'select': 'fa-caret-square-down',
        'number': 'fa-hashtag',
        'date': 'fa-calendar-alt',
        'rating': 'fa-star',
        'file': 'fa-file-upload'
    };
    return icons[type] || 'fa-question-circle';
}