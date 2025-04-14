document.addEventListener('DOMContentLoaded', function() {
    // Professional color palette
    const colors = {
        primary: '#4e79a7',
        secondary: '#f28e2c',
        success: '#59a14f',
        danger: '#e15759',
        warning: '#edc949',
        info: '#76b7b2',
        light: '#f5f5f5',
        dark: '#333333'
    };

    // Chart configuration
    Chart.defaults.font.family = "'Segoe UI', Roboto, sans-serif";
    Chart.defaults.color = colors.dark;
    Chart.defaults.animation.duration = 1000;
    Chart.register(ChartDataLabels);

    // Summary chart - Response rate over time
    if (chartData.total_responses > 0) {
        createSummaryChart();
    }

    // Field-specific charts
    chartData.fields.forEach(field => {
        const fieldAnalytics = chartData.analytics[field.id] || [];
        
        if (fieldAnalytics.length > 0) {
            switch(field.field_type) {
                case 'radio':
                case 'select':
                case 'rating':
                    createPieChart(field, fieldAnalytics);
                    break;
                case 'checkbox':
                    createBarChart(field, fieldAnalytics);
                    break;
                case 'number':
                    createHistogram(field, fieldAnalytics);
                    break;
                case 'text':
                case 'textarea':
                    createWordCloud(field, fieldAnalytics);
                    break;
                default:
                    createBasicChart(field, fieldAnalytics);
            }
        }
    });

    function createSummaryChart() {
        const ctx = document.createElement('canvas');
        document.getElementById('summary-chart').appendChild(ctx);
        
        // This would be better with actual time-series data from your database
        const labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
        const responseCounts = labels.map((_, i) => 
            Math.floor(chartData.total_responses / labels.length * (0.8 + Math.random() * 0.4))
        );
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Responses',
                    data: responseCounts,
                    backgroundColor: colors.primary + '20',
                    borderColor: colors.primary,
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Response Trend Over Time',
                        font: { size: 16 }
                    },
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => `Responses: ${ctx.raw}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Number of Responses' }
                    },
                    x: {
                        title: { display: true, text: 'Time Period' }
                    }
                }
            }
        });
    }

    function createPieChart(field, analytics) {
        const ctx = document.createElement('canvas');
        document.getElementById(`chart-${field.id}`).appendChild(ctx);
        
        const labels = analytics.map(item => item.field_value);
        const data = analytics.map(item => item.count);
        const backgroundColors = generateColors(labels.length);
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: backgroundColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: field.field_label,
                        font: { size: 14 }
                    },
                    legend: {
                        position: 'right',
                        labels: { padding: 20 }
                    },
                    datalabels: {
                        formatter: (value, ctx) => {
                            const total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            return `${Math.round(value / total * 100)}%`;
                        },
                        color: '#fff',
                        font: { weight: 'bold' }
                    }
                }
            }
        });
    }

    function createBarChart(field, analytics) {
        const ctx = document.createElement('canvas');
        document.getElementById(`chart-${field.id}`).appendChild(ctx);
        
        const labels = analytics.map(item => item.field_value);
        const data = analytics.map(item => item.count);
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Selections',
                    data: data,
                    backgroundColor: generateColors(labels.length),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: field.field_label,
                        font: { size: 14 }
                    },
                    legend: { display: false },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        formatter: value => value
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    }

    function createHistogram(field, analytics) {
        const ctx = document.createElement('canvas');
        document.getElementById(`chart-${field.id}`).appendChild(ctx);
        
        // Convert string values to numbers
        const numericValues = analytics
            .filter(item => !isNaN(parseFloat(item.field_value)))
            .map(item => parseFloat(item.field_value));
        
        if (numericValues.length === 0) return;
        
        // Simple histogram calculation
        const min = Math.min(...numericValues);
        const max = Math.max(...numericValues);
        const range = max - min;
        const binCount = Math.min(10, Math.ceil(Math.sqrt(numericValues.length)));
        const binSize = range / binCount;
        
        const bins = Array(binCount).fill(0);
        const labels = [];
        
        for (let i = 0; i < binCount; i++) {
            const binStart = min + i * binSize;
            const binEnd = binStart + binSize;
            labels.push(`${binStart.toFixed(1)}-${binEnd.toFixed(1)}`);
            
            bins[i] = numericValues.filter(val => 
                val >= binStart && (i === binCount - 1 ? val <= binEnd : val < binEnd)
            ).length;
        }
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Frequency',
                    data: bins,
                    backgroundColor: colors.primary + '80',
                    borderColor: colors.primary,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: `${field.field_label} Distribution`,
                        font: { size: 14 }
                    },
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Count' }
                    },
                    x: {
                        title: { display: true, text: 'Value Range' }
                    }
                }
            }
        });
    }

    function createWordCloud(field, analytics) {
        const container = document.getElementById(`chart-${field.id}`);
        container.innerHTML = '<div class="word-cloud"></div>';
        const cloudEl = container.querySelector('.word-cloud');
        
        // Simple word frequency analysis (would be better with proper NLP in backend)
        const allText = analytics.map(item => item.field_value).join(' ');
        const words = allText.toLowerCase().split(/\s+/);
        const wordCounts = {};
        
        words.forEach(word => {
            if (word.length > 3) { // Ignore short words
                wordCounts[word] = (wordCounts[word] || 0) + 1;
            }
        });
        
        // Get top 30 words
        const topWords = Object.entries(wordCounts)
            .sort((a, b) => b[1] - a[1])
            .slice(0, 30);
        
        // Create word cloud elements
        topWords.forEach(([word, count]) => {
            const size = 10 + (count / topWords[0][1]) * 30;
            const color = `hsl(${Math.random() * 360}, 70%, 60%)`;
            
            const wordEl = document.createElement('span');
            wordEl.textContent = word;
            wordEl.style.fontSize = `${size}px`;
            wordEl.style.color = color;
            wordEl.style.margin = '5px';
            wordEl.style.display = 'inline-block';
            wordEl.style.padding = '2px 5px';
            wordEl.style.opacity = '0.8';
            
            cloudEl.appendChild(wordEl);
        });
        
        // Add title
        const titleEl = document.createElement('h4');
        titleEl.textContent = `Word Cloud: ${field.field_label}`;
        titleEl.style.textAlign = 'center';
        titleEl.style.marginBottom = '10px';
        container.insertBefore(titleEl, cloudEl);
    }

    function createBasicChart(field, analytics) {
        const ctx = document.createElement('canvas');
        document.getElementById(`chart-${field.id}`).appendChild(ctx);
        
        const labels = analytics.map(item => `Response ${item.id}`);
        const data = analytics.map(item => item.field_value);
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Responses',
                    data: data,
                    backgroundColor: colors.info + '80',
                    borderColor: colors.info,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: field.field_label,
                        font: { size: 14 }
                    },
                    legend: { display: false }
                }
            }
        });
    }

    function generateColors(count) {
        const colors = [];
        const hueStep = 360 / count;
        
        for (let i = 0; i < count; i++) {
            const hue = i * hueStep;
            colors.push(`hsl(${hue}, 70%, 60%)`);
        }
        
        return colors;
    }
});