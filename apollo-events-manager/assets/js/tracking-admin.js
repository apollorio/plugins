/**
 * Apollo Events Manager - Tracking Admin JavaScript
 *
 * @package Apollo_Events_Manager
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Apollo Analytics Dashboard
     */
    class ApolloAnalyticsDashboard {
        constructor() {
            this.config = window.apolloTrackingAdmin || {};
            this.charts = {};
            this.init();
        }

        init() {
            if (typeof apolloAnalyticsData === 'undefined') {
                return;
            }

            this.initViewsChart();
            this.initPopularChart();
        }

        /**
         * Initialize views line chart
         */
        initViewsChart() {
            const ctx = document.getElementById('apollo-views-chart');

            if (!ctx) return;

            const data = apolloAnalyticsData.views || { labels: [], values: [] };

            this.charts.views = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Visualizações',
                        data: data.values,
                        borderColor: '#7c3aed',
                        backgroundColor: 'rgba(124, 58, 237, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3,
                        pointBackgroundColor: '#7c3aed',
                        pointHoverRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#1d2327',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' visualizações';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f0f0f1'
                            },
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
        }

        /**
         * Initialize popular events bar chart
         */
        initPopularChart() {
            const ctx = document.getElementById('apollo-popular-chart');

            if (!ctx) return;

            const data = apolloAnalyticsData.popular || [];

            // Truncate long titles
            const labels = data.map(e => {
                const title = e.title || 'Sem título';
                return title.length > 25 ? title.substring(0, 25) + '...' : title;
            });

            const values = data.map(e => e.views || 0);

            this.charts.popular = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Views',
                        data: values,
                        backgroundColor: [
                            'rgba(124, 58, 237, 0.8)',
                            'rgba(168, 85, 247, 0.8)',
                            'rgba(192, 132, 252, 0.8)',
                            'rgba(216, 180, 254, 0.8)',
                            'rgba(233, 213, 255, 0.8)',
                            'rgba(124, 58, 237, 0.6)',
                            'rgba(168, 85, 247, 0.6)',
                            'rgba(192, 132, 252, 0.6)',
                            'rgba(216, 180, 254, 0.6)',
                            'rgba(233, 213, 255, 0.6)'
                        ],
                        borderWidth: 0,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#1d2327',
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.x + ' visualizações';
                                },
                                title: function(context) {
                                    // Return full title from data
                                    const index = context[0].dataIndex;
                                    return apolloAnalyticsData.popular[index]?.title || '';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                color: '#f0f0f1'
                            },
                            ticks: {
                                precision: 0
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        /**
         * Update chart with new data
         */
        updateChart(chartName, newData) {
            if (!this.charts[chartName]) return;

            const chart = this.charts[chartName];
            chart.data.labels = newData.labels;
            chart.data.datasets[0].data = newData.values;
            chart.update();
        }

        /**
         * Destroy charts
         */
        destroy() {
            Object.values(this.charts).forEach(chart => {
                if (chart) chart.destroy();
            });
            this.charts = {};
        }
    }

    /**
     * Date Range Filter
     */
    class DateRangeFilter {
        constructor() {
            this.$form = $('.apollo-date-range-picker');

            if (this.$form.length) {
                this.bindEvents();
            }
        }

        bindEvents() {
            this.$form.on('submit', this.handleSubmit.bind(this));
            this.$form.find('.quick-range').on('click', this.handleQuickRange.bind(this));
        }

        handleSubmit(e) {
            e.preventDefault();

            const startDate = this.$form.find('[name="start_date"]').val();
            const endDate = this.$form.find('[name="end_date"]').val();

            if (startDate && endDate) {
                window.location.href = this.buildUrl(startDate, endDate);
            }
        }

        handleQuickRange(e) {
            e.preventDefault();

            const range = $(e.currentTarget).data('range');
            const today = new Date();
            let startDate = new Date();

            switch (range) {
                case '7days':
                    startDate.setDate(today.getDate() - 7);
                    break;
                case '30days':
                    startDate.setDate(today.getDate() - 30);
                    break;
                case '90days':
                    startDate.setDate(today.getDate() - 90);
                    break;
                case 'year':
                    startDate.setFullYear(today.getFullYear() - 1);
                    break;
            }

            window.location.href = this.buildUrl(
                this.formatDate(startDate),
                this.formatDate(today)
            );
        }

        buildUrl(startDate, endDate) {
            const url = new URL(window.location.href);
            url.searchParams.set('start_date', startDate);
            url.searchParams.set('end_date', endDate);
            return url.toString();
        }

        formatDate(date) {
            return date.toISOString().split('T')[0];
        }
    }

    /**
     * Export functionality
     */
    class AnalyticsExport {
        constructor() {
            $(document).on('click', '.apollo-export-csv', this.exportCSV.bind(this));
            $(document).on('click', '.apollo-export-pdf', this.exportPDF.bind(this));
        }

        exportCSV(e) {
            e.preventDefault();

            const data = apolloAnalyticsData;
            let csv = 'Evento,Views,Interesses,Cliques\n';

            if (data.popular) {
                data.popular.forEach(event => {
                    csv += `"${event.title}",${event.views},0,0\n`;
                });
            }

            this.downloadFile(csv, 'apollo-analytics.csv', 'text/csv');
        }

        exportPDF(e) {
            e.preventDefault();
            // PDF export would require additional library
            alert('Exportação PDF em desenvolvimento');
        }

        downloadFile(content, filename, mimeType) {
            const blob = new Blob([content], { type: mimeType });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }
    }

    /**
     * Initialize on document ready
     */
    $(function() {
        // Wait for Chart.js to load
        if (typeof Chart !== 'undefined') {
            window.apolloAnalyticsDashboard = new ApolloAnalyticsDashboard();
        } else {
            // Retry after a short delay
            setTimeout(function() {
                if (typeof Chart !== 'undefined') {
                    window.apolloAnalyticsDashboard = new ApolloAnalyticsDashboard();
                }
            }, 500);
        }

        new DateRangeFilter();
        new AnalyticsExport();
    });

    // Export to global scope
    window.ApolloAnalyticsDashboard = ApolloAnalyticsDashboard;

})(jQuery);
