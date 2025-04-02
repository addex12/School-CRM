function initCharts() {
    // User Registrations Chart
    if (document.getElementById('userRegistrationsChart') && chartData.userRegistrations) {
        const ctx = document.getElementById('userRegistrationsChart').getContext('2d');
        const labels = chartData.userRegistrations.map(item => item.date);
        const data = chartData.userRegistrations.map(item => item.count);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'User Registrations',
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
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    
    // Survey Status Chart
    if (document.getElementById('surveyStatusChart') && chartData.surveyStatus) {
        const ctx = document.getElementById('surveyStatusChart').getContext('2d');
        const labels = chartData.surveyStatus.map(item => item.label);
        const data = chartData.surveyStatus.map(item => item.count);
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(255, 99, 132, 0.7)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                },
                cutout: '70%'
            }
        });
    }
    
    // Ticket Priority Chart
    if (document.getElementById('ticketPriorityChart') && chartData.ticketPriority) {
        const ctx = document.getElementById('ticketPriorityChart').getContext('2d');
        const labels = chartData.ticketPriority.map(item => item.label);
        const data = chartData.ticketPriority.map(item => item.count);
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Tickets',
                    data: data,
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(255, 99, 132, 0.7)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
}

// Initialize charts when DOM is loaded
document.addEventListener('DOMContentLoaded', initCharts);
// Example chartData object
const chartData = {
    userRegistrations: [
        { date: '2023-01-01', count: 10 },
        { date: '2023-01-02', count: 15 },
        { date: '2023-01-03', count: 8 },
        { date: '2023-01-04', count: 12 },
        { date: '2023-01-05', count: 20 }
    ],
    surveyStatus: [
        { label: 'Completed', count: 10 },
        { label: 'In Progress', count: 5 },
        { label: 'Pending', count: 3 },
        { label: 'Cancelled', count: 2 }
    ],
    ticketPriority: [
        { label: 'Low', count: 10 },
        { label: 'Medium', count: 5 },
        { label: 'High', count: 3 }
    ]
}