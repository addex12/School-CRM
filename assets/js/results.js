document.addEventListener('DOMContentLoaded', function () {
    const chartContainer = document.getElementById('chart-container');
    const surveyResults = JSON.parse(document.getElementById('survey-results-data').textContent);

    // Professional color palette
    const professionalColors = [
        '#4e79a7', '#f28e2c', '#e15759', '#76b7b2', 
        '#59a14f', '#edc949', '#af7aa1', '#ff9da7'
    ];

    Chart.defaults.animation.duration = 2000;
    Chart.register(ChartDataLabels);

    // Create charts for each survey field
    surveyResults.fields.forEach((field, index) => {
        if (['radio', 'checkbox', 'dropdown'].includes(field.field_type)) {
            const fieldData = surveyResults.responses.map(response => response.answers[field.field_name] || 'N/A');
            const uniqueOptions = [...new Set(fieldData)];
            const optionCounts = uniqueOptions.map(option => fieldData.filter(value => value === option).length);
            const total = optionCounts.reduce((a, b) => a + b, 0);

            // Create chart card
            const card = document.createElement('div');
            card.className = 'chart-card';
            const canvas = document.createElement('canvas');
            canvas.id = `chart-${index}`;
            card.appendChild(canvas);
            chartContainer.appendChild(card);

            // Generate chart
            new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: uniqueOptions,
                    datasets: [{
                        data: optionCounts,
                        backgroundColor: professionalColors.slice(0, uniqueOptions.length),
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        datalabels: {
                            color: '#fff',
                            formatter: (value) => {
                                return `${((value / total) * 100).toFixed(1)}%`;
                            }
                        },
                        legend: {
                            position: 'right',
                            labels: { boxWidth: 20 }
                        },
                        title: {
                            display: true,
                            text: field.field_label,
                            font: { size: 16 }
                        }
                    },
                    animation: {
                        animateRotate: true,
                        animateScale: true
                    }
                }
            });
        }
    });

    // Enhanced bar chart
    const responseCounts = surveyResults.fields.map(field => {
        return surveyResults.responses.filter(response => response.answers[field.field_name]).length;
    });

    const barCard = document.createElement('div');
    barCard.className = 'chart-card-wide';
    const barCanvas = document.createElement('canvas');
    barCanvas.id = 'bar-chart';
    barCard.appendChild(barCanvas);
    chartContainer.appendChild(barCard);

    new Chart(barCanvas, {
        type: 'bar',
        data: {
            labels: surveyResults.fields.map(field => field.field_label),
            datasets: [{
                label: 'Response Count',
                data: responseCounts,
                backgroundColor: professionalColors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    color: '#444',
                    formatter: (value) => value
                },
                title: {
                    display: true,
                    text: 'Response Distribution Across Fields',
                    font: { size: 18 }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { autoSkip: false }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f5f5f5' }
                }
            }
        }
    });

    // PDF Export Handler
    document.getElementById('export-pdf').addEventListener('click', async () => {
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('landscape');
        const elements = [
            document.getElementById('chart-container'),
            document.querySelector('.table')
        ];

        for (let i = 0; i < elements.length; i++) {
            const canvas = await html2canvas(elements[i]);
            const imgData = canvas.toDataURL('image/png');
            if (i > 0) pdf.addPage();
            pdf.setFontSize(18);
            pdf.text(i === 0 ? 'Survey Charts' : 'Response Data', 10, 20);
            pdf.addImage(imgData, 'PNG', 10, 30, 280, 160);
        }

        pdf.save(`survey-results-${Date.now()}.pdf`);
    });
});
