document.addEventListener('DOMContentLoaded', function () {
    const chartContainer = document.getElementById('chart-container');
    const surveyResults = JSON.parse(document.getElementById('survey-results-data').textContent);

    // Helper function to generate random colors
    function generateRandomColors(count) {
        const colors = [];
        for (let i = 0; i < count; i++) {
            const color = `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 0.7)`;
            colors.push(color);
        }
        return colors;
    }

    // Create charts for each survey field
    surveyResults.fields.forEach((field, index) => {
        if (['radio', 'checkbox', 'dropdown'].includes(field.field_type)) {
            const fieldData = surveyResults.responses.map(response => response.answers[field.field_name] || 'N/A');
            const uniqueOptions = [...new Set(fieldData)];
            const optionCounts = uniqueOptions.map(option => fieldData.filter(value => value === option).length);

            // Create a canvas for the chart
            const canvas = document.createElement('canvas');
            canvas.id = `chart-${index}`;
            canvas.style.marginBottom = '30px';
            chartContainer.appendChild(canvas);

            // Generate chart
            new Chart(canvas, {
                type: 'pie',
                data: {
                    labels: uniqueOptions,
                    datasets: [{
                        data: optionCounts,
                        backgroundColor: generateRandomColors(uniqueOptions.length),
                        borderColor: '#fff',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        title: {
                            display: true,
                            text: field.field_label
                        }
                    }
                }
            });
        }
    });

    // Add a bar chart for overall response counts
    const responseCounts = surveyResults.fields.map(field => {
        return surveyResults.responses.filter(response => response.answers[field.field_name]).length;
    });

    const barCanvas = document.createElement('canvas');
    barCanvas.id = 'bar-chart';
    chartContainer.appendChild(barCanvas);

    new Chart(barCanvas, {
        type: 'bar',
        data: {
            labels: surveyResults.fields.map(field => field.field_label),
            datasets: [{
                label: 'Response Count',
                data: responseCounts,
                backgroundColor: generateRandomColors(surveyResults.fields.length),
                borderColor: '#fff',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Overall Response Counts'
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Survey Fields'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Response Count'
                    },
                    beginAtZero: true
                }
            }
        }
    });
});
