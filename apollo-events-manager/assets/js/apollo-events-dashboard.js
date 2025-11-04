/**
 * Apollo Events Dashboard
 * Admin dashboard JavaScript
 */

(function() {
    'use strict';

    const Dashboard = {
        data: null,
        currentTab: 'eventos',
        chart: null,

        /**
         * Initialize dashboard
         */
        init: function() {
            this.render();
            this.loadData();
            this.bindEvents();
        },

        /**
         * Render dashboard structure
         */
        render: function() {
            const container = document.getElementById('apollo-dashboard-app');
            if (!container) return;

            container.innerHTML = `
                <div class="apollo-dashboard-loading">
                    <div class="apollo-spinner"></div>
                    <p>Carregando dados...</p>
                </div>
            `;
        },

        /**
         * Load data from API
         */
        loadData: function() {
            const container = document.getElementById('apollo-dashboard-app');
            if (!container) return;

            // Try REST API first, fallback to AJAX
            const restUrl = apolloDashboard.rest_url;
            const ajaxUrl = apolloDashboard.ajax_url;

            // Try fetch REST API
            fetch(restUrl, {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': apolloDashboard.nonce,
                },
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('REST API failed');
                }
                return response.json();
            })
            .then(data => {
                this.data = data;
                this.renderDashboard();
            })
            .catch(() => {
                // Fallback to AJAX
                const formData = new FormData();
                formData.append('action', 'apollo_dashboard_data');
                formData.append('nonce', apolloDashboard.nonce);

                return fetch(ajaxUrl, {
                    method: 'POST',
                    body: formData,
                });
            })
            .then(response => {
                if (!response) return;
                return response.json();
            })
            .then(data => {
                if (data && data.success) {
                    this.data = data.data;
                    this.renderDashboard();
                } else {
                    this.renderError('Erro ao carregar dados do dashboard.');
                }
            })
            .catch(error => {
                console.error('Dashboard error:', error);
                this.renderError('Erro ao carregar dados do dashboard.');
            });
        },

        /**
         * Render dashboard
         */
        renderDashboard: function() {
            if (!this.data) {
                this.renderError('Nenhum dado disponível.');
                return;
            }

            const container = document.getElementById('apollo-dashboard-app');
            if (!container) return;

            container.innerHTML = `
                <!-- Tabs -->
                <div class="apollo-dashboard-tabs">
                    <button class="apollo-dashboard-tab ${this.currentTab === 'eventos' ? 'active' : ''}" data-tab="eventos">
                        Eventos
                    </button>
                    <button class="apollo-dashboard-tab ${this.currentTab === 'djs' ? 'active' : ''}" data-tab="djs">
                        DJs
                    </button>
                    <button class="apollo-dashboard-tab ${this.currentTab === 'locais' ? 'active' : ''}" data-tab="locais">
                        Locais
                    </button>
                </div>

                <!-- Tab Content: Eventos -->
                <div class="apollo-dashboard-tab-content ${this.currentTab === 'eventos' ? 'active' : ''}" data-content="eventos">
                    ${this.renderEventosTab()}
                </div>

                <!-- Tab Content: DJs -->
                <div class="apollo-dashboard-tab-content ${this.currentTab === 'djs' ? 'active' : ''}" data-content="djs">
                    ${this.renderDJsTab()}
                </div>

                <!-- Tab Content: Locais -->
                <div class="apollo-dashboard-tab-content ${this.currentTab === 'locais' ? 'active' : ''}" data-content="locais">
                    ${this.renderLocaisTab()}
                </div>
            `;

            // Render chart after DOM is ready
            setTimeout(() => {
                this.renderChart();
            }, 100);
        },

        /**
         * Render Eventos tab
         */
        renderEventosTab: function() {
            const resumo = this.data.resumo || {};
            const eventos = this.data.eventos || [];
            const plausible = this.data.plausible || {};

            return `
                <!-- Summary Cards -->
                <div class="apollo-dashboard-summary">
                    <div class="apollo-dashboard-card">
                        <h3>Total de Eventos</h3>
                        <p class="value">${resumo.total_eventos || 0}</p>
                    </div>
                    <div class="apollo-dashboard-card">
                        <h3>Eventos Futuros</h3>
                        <p class="value">${resumo.eventos_futuros || 0}</p>
                    </div>
                    <div class="apollo-dashboard-card">
                        <h3>Eventos Hoje</h3>
                        <p class="value">${resumo.eventos_hoje || 0}</p>
                    </div>
                    <div class="apollo-dashboard-card">
                        <h3>Eventos Passados</h3>
                        <p class="value">${resumo.eventos_passados || 0}</p>
                    </div>
                </div>

                <!-- Plausible Analytics -->
                ${this.renderPlausibleCards(plausible)}

                <!-- Chart -->
                ${this.renderChartContainer()}

                <!-- Events Table -->
                <div class="apollo-dashboard-table-wrapper">
                    <table class="apollo-dashboard-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Título</th>
                                <th>Local</th>
                                <th>DJs</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${this.renderEventosTable(eventos)}
                        </tbody>
                    </table>
                </div>
            `;
        },

        /**
         * Render Plausible cards
         */
        renderPlausibleCards: function(plausible) {
            const pageviews = plausible.pageviews_30d;
            const topUrls = plausible.top_event_urls || [];

            if (!pageviews && topUrls.length === 0) {
                return `
                    <div class="apollo-dashboard-plausible">
                        <div class="apollo-dashboard-card">
                            <h3>Analytics (Plausible)</h3>
                            <p>Sem dados de analytics disponíveis</p>
                            <p style="font-size: 11px; margin-top: 10px;">
                                Configure o filtro <code>apollo_events_plausible_fetch</code> para conectar com Plausible Analytics.
                            </p>
                        </div>
                    </div>
                `;
            }

            let topUrlsHtml = '';
            if (topUrls.length > 0) {
                topUrlsHtml = '<ul style="list-style: none; padding: 0; margin: 10px 0 0 0;">';
                topUrls.slice(0, 5).forEach(url => {
                    topUrlsHtml += `<li style="padding: 5px 0; border-bottom: 1px solid rgba(255,255,255,0.1);">${url.url || url} - ${url.pageviews || ''} views</li>`;
                });
                topUrlsHtml += '</ul>';
            }

            return `
                <div class="apollo-dashboard-plausible">
                    <div class="apollo-dashboard-card">
                        <h3>Pageviews (30 dias)</h3>
                        <p class="value">${pageviews ? pageviews.toLocaleString() : '—'}</p>
                        <p>Visualizações em /eventos/</p>
                    </div>
                    ${topUrls.length > 0 ? `
                        <div class="apollo-dashboard-card">
                            <h3>Top 5 Eventos</h3>
                            ${topUrlsHtml}
                        </div>
                    ` : ''}
                </div>
            `;
        },

        /**
         * Render chart container
         */
        renderChartContainer: function() {
            return `
                <div class="apollo-dashboard-chart">
                    <h3>Eventos por Mês (Próximos 6 meses)</h3>
                    <canvas id="apollo-events-chart" width="400" height="200"></canvas>
                </div>
            `;
        },

        /**
         * Render chart
         */
        renderChart: function() {
            if (!this.data || !this.data.eventos_por_mes) return;

            const canvas = document.getElementById('apollo-events-chart');
            if (!canvas || typeof Chart === 'undefined') return;

            // Destroy existing chart
            if (this.chart) {
                this.chart.destroy();
            }

            const meses = this.data.eventos_por_mes.map(item => item.label);
            const counts = this.data.eventos_por_mes.map(item => item.count);

            this.chart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: meses,
                    datasets: [{
                        label: 'Eventos',
                        data: counts,
                        backgroundColor: 'rgba(34, 113, 177, 0.8)',
                        borderColor: 'rgba(34, 113, 177, 1)',
                        borderWidth: 1,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                            },
                        },
                    },
                    plugins: {
                        legend: {
                            display: false,
                        },
                    },
                },
            });
        },

        /**
         * Render eventos table
         */
        renderEventosTable: function(eventos) {
            if (eventos.length === 0) {
                return '<tr><td colspan="5" class="apollo-dashboard-empty">Nenhum evento encontrado.</td></tr>';
            }

            return eventos.map(evento => {
                const localDisplay = evento.local || '—';
                const areaDisplay = evento.area ? `<span class="apollo-venue-area">(${evento.area})</span>` : '';
                const statusClass = evento.status || 'futuro';
                const statusLabel = {
                    'passado': 'Passado',
                    'hoje': 'Hoje',
                    'futuro': 'Futuro',
                }[statusClass] || 'Futuro';

                return `
                    <tr>
                        <td>${evento.date || '—'}</td>
                        <td><strong>${evento.title || '—'}</strong></td>
                        <td>${localDisplay}${areaDisplay}</td>
                        <td>${evento.djs_display || '—'}</td>
                        <td><span class="apollo-status-badge ${statusClass}">${statusLabel}</span></td>
                    </tr>
                `;
            }).join('');
        },

        /**
         * Render DJs tab
         */
        renderDJsTab: function() {
            const djs = this.data.djs || [];

            if (djs.length === 0) {
                return '<div class="apollo-dashboard-empty"><p>Nenhum DJ encontrado.</p></div>';
            }

            return `
                <div class="apollo-dashboard-table-wrapper">
                    <table class="apollo-dashboard-table">
                        <thead>
                            <tr>
                                <th>Nome do DJ</th>
                                <th>Eventos Futuros</th>
                                <th>Total de Eventos</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${djs.map(dj => `
                                <tr>
                                    <td><strong>${dj.name || '—'}</strong></td>
                                    <td>${dj.events_future || 0}</td>
                                    <td>${dj.events_total || 0}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        },

        /**
         * Render Locais tab
         */
        renderLocaisTab: function() {
            const locais = this.data.locais || [];

            if (locais.length === 0) {
                return '<div class="apollo-dashboard-empty"><p>Nenhum local encontrado.</p></div>';
            }

            return `
                <div class="apollo-dashboard-table-wrapper">
                    <table class="apollo-dashboard-table">
                        <thead>
                            <tr>
                                <th>Local</th>
                                <th>Área</th>
                                <th>Eventos Futuros</th>
                                <th>Total de Eventos</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${locais.map(local => `
                                <tr>
                                    <td><strong>${local.local || '—'}</strong></td>
                                    <td><span class="apollo-venue-area">${local.area || '—'}</span></td>
                                    <td>${local.events_future || 0}</td>
                                    <td>${local.events_total || 0}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        },

        /**
         * Render error state
         */
        renderError: function(message) {
            const container = document.getElementById('apollo-dashboard-app');
            if (!container) return;

            container.innerHTML = `
                <div class="apollo-dashboard-error">
                    <strong>Erro:</strong> ${message}
                </div>
            `;
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Use event delegation for tabs
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('apollo-dashboard-tab')) {
                    e.preventDefault();
                    const tab = e.target.getAttribute('data-tab');
                    this.switchTab(tab);
                }
            });
        },

        /**
         * Switch tab
         */
        switchTab: function(tab) {
            this.currentTab = tab;

            // Update tab buttons
            document.querySelectorAll('.apollo-dashboard-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`.apollo-dashboard-tab[data-tab="${tab}"]`)?.classList.add('active');

            // Update tab content
            document.querySelectorAll('.apollo-dashboard-tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelector(`.apollo-dashboard-tab-content[data-content="${tab}"]`)?.classList.add('active');

            // Re-render chart if needed
            if (tab === 'eventos') {
                setTimeout(() => {
                    this.renderChart();
                }, 100);
            }
        },
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => Dashboard.init());
    } else {
        Dashboard.init();
    }

})();

