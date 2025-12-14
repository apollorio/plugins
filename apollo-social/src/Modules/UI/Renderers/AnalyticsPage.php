<?php

namespace Apollo\Modules\UI\Renderers;

/**
 * Analytics Page Renderer
 *
 * Renders analytics dashboard in Canvas mode (script-only, no API).
 */
class AnalyticsPage
{
    /**
     * Render analytics panel
     */
    public function render(): void
    {
        $analytics_config = config('analytics');

        if (! ($analytics_config['enabled'] ?? false)) {
            $this->renderDisabled();

            return;
        }

        $this->renderDashboard($analytics_config);
    }

    /**
     * Render analytics dashboard
     */
    private function renderDashboard(array $config): void
    {
        ?>
		<!DOCTYPE html>
		<html lang="pt-BR">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Estatísticas - Apollo Social</title>
			<style>
				* {
					margin: 0;
					padding: 0;
					box-sizing: border-box;
				}

				body {
					font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
					background: #f8fafc;
					color: #334155;
					min-height: 100vh;
				}

				.analytics-header {
					background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
					color: white;
					padding: 30px;
					box-shadow: 0 4px 12px rgba(0,0,0,0.1);
				}

				.analytics-header h1 {
					font-size: 32px;
					margin-bottom: 10px;
				}

				.analytics-container {
					max-width: 1200px;
					margin: 0 auto;
					padding: 30px;
				}

				.stats-grid {
					display: grid;
					grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
					gap: 20px;
					margin-bottom: 40px;
				}

				.stat-card {
					background: white;
					border-radius: 12px;
					padding: 25px;
					box-shadow: 0 2px 8px rgba(0,0,0,0.1);
					border-left: 4px solid #3b82f6;
					position: relative;
					overflow: hidden;
				}

				.stat-card::before {
					content: '';
					position: absolute;
					top: 0;
					right: 0;
					width: 60px;
					height: 60px;
					background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(29, 78, 216, 0.1) 100%);
					border-radius: 0 12px 0 60px;
				}

				.stat-icon {
					font-size: 24px;
					margin-bottom: 15px;
					display: block;
				}

				.stat-card h3 {
					color: #64748b;
					font-size: 14px;
					margin-bottom: 10px;
					text-transform: uppercase;
					letter-spacing: 0.5px;
				}

				.stat-value {
					font-size: 36px;
					font-weight: 700;
					color: #1e293b;
					margin-bottom: 8px;
				}

				.stat-label {
					color: #64748b;
					font-size: 14px;
				}

				.stat-change {
					font-size: 12px;
					font-weight: 600;
					margin-top: 5px;
				}

				.stat-change.positive {
					color: #10b981;
				}

				.stat-change.neutral {
					color: #6b7280;
				}

				.events-section {
					background: white;
					border-radius: 12px;
					padding: 30px;
					box-shadow: 0 2px 8px rgba(0,0,0,0.1);
					margin-bottom: 30px;
				}

				.events-section h2 {
					color: #1e293b;
					margin-bottom: 20px;
					display: flex;
					align-items: center;
					gap: 10px;
				}

				.event-list {
					display: grid;
					gap: 15px;
				}

				.event-item {
					display: flex;
					justify-content: space-between;
					align-items: center;
					padding: 15px;
					background: #f8fafc;
					border-radius: 8px;
					border-left: 3px solid #e5e7eb;
				}

				.event-name {
					font-weight: 600;
					color: #374151;
				}

				.event-count {
					font-size: 18px;
					font-weight: 700;
					color: #3b82f6;
				}

				.test-section {
					background: #f0f9ff;
					border: 1px solid #0ea5e9;
					border-radius: 12px;
					padding: 20px;
					margin-bottom: 30px;
				}

				.test-section h3 {
					color: #0ea5e9;
					margin-bottom: 15px;
				}

				.test-buttons {
					display: flex;
					gap: 10px;
					flex-wrap: wrap;
				}

				.test-btn {
					padding: 8px 16px;
					background: #0ea5e9;
					color: white;
					border: none;
					border-radius: 6px;
					font-size: 14px;
					cursor: pointer;
					transition: background 0.2s;
				}

				.test-btn:hover {
					background: #0284c7;
				}

				.info-section {
					background: #fffbeb;
					border: 1px solid #f59e0b;
					border-radius: 12px;
					padding: 20px;
				}

				.info-section h3 {
					color: #d97706;
					margin-bottom: 10px;
				}

				.info-section p {
					color: #92400e;
					line-height: 1.6;
				}

				@media (max-width: 768px) {
					.analytics-container {
						padding: 20px 15px;
					}
					
					.stats-grid {
						grid-template-columns: 1fr;
					}
					
					.test-buttons {
						flex-direction: column;
					}
				}
			</style>
		</head>
		<body>
			<div class="analytics-header">
				<h1>📊 Estatísticas Apollo</h1>
				<p>Analytics leve e local - Plausible Script Only</p>
			</div>

			<div class="analytics-container">
				<!-- Session Counters -->
				<div class="stats-grid">
					<div class="stat-card">
						<span class="stat-icon">👥</span>
						<h3>Visualizações de Grupo</h3>
						<div class="stat-value" id="groupViews">--</div>
						<div class="stat-label">Nesta sessão</div>
						<div class="stat-change neutral" id="groupViewsChange">Dados carregando...</div>
					</div>
					
					<div class="stat-card">
						<span class="stat-icon">🤝</span>
						<h3>Novos Membros</h3>
						<div class="stat-value" id="groupJoins">--</div>
						<div class="stat-label">Nesta sessão</div>
						<div class="stat-change neutral" id="groupJoinsChange">Dados carregando...</div>
					</div>
					
					<div class="stat-card">
						<span class="stat-icon">📧</span>
						<h3>Convites Enviados</h3>
						<div class="stat-value" id="invitesSent">--</div>
						<div class="stat-label">Nesta sessão</div>
						<div class="stat-change neutral" id="invitesSentChange">Dados carregando...</div>
					</div>
					
					<div class="stat-card">
						<span class="stat-icon">📅</span>
						<h3>Eventos Visualizados</h3>
						<div class="stat-value" id="eventViews">--</div>
						<div class="stat-label">Nesta sessão</div>
						<div class="stat-change neutral" id="eventViewsChange">Dados carregando...</div>
					</div>
					
					<div class="stat-card">
						<span class="stat-icon">🛒</span>
						<h3>Anúncios Visualizados</h3>
						<div class="stat-value" id="adViews">--</div>
						<div class="stat-label">Nesta sessão</div>
						<div class="stat-change neutral" id="adViewsChange">Dados carregando...</div>
					</div>
					
					<div class="stat-card">
						<span class="stat-icon">🎯</span>
						<h3>Filtros Aplicados</h3>
						<div class="stat-value" id="filtersApplied">--</div>
						<div class="stat-label">Nesta sessão</div>
						<div class="stat-change neutral" id="filtersAppliedChange">Dados carregando...</div>
					</div>
				</div>

				<!-- Live Events -->
				<div class="events-section">
					<h2>📈 Eventos Recentes</h2>
					<div class="event-list" id="recentEvents">
						<div class="event-item">
							<span class="event-name">Aguardando eventos...</span>
							<span class="event-count">--</span>
						</div>
					</div>
				</div>

				<!-- Test Section -->
				<?php if (($config['plausible']['custom_events'] ?? false)) : ?>
				<div class="test-section">
					<h3>🧪 Testar Eventos Analytics</h3>
					<p style="margin-bottom: 15px; color: #0369a1;">Clique nos botões para testar o tracking de eventos:</p>
					<div class="test-buttons">
						<button class="test-btn" onclick="testEvent('group_view', {group_type: 'comunidade', group_slug: 'test'})">
							👥 Testar Group View
						</button>
						<button class="test-btn" onclick="testEvent('group_join', {group_type: 'nucleo', group_slug: 'test'})">
							🤝 Testar Group Join
						</button>
						<button class="test-btn" onclick="testEvent('invite_sent', {group_type: 'comunidade', invite_type: 'email'})">
							📧 Testar Invite Sent
						</button>
						<button class="test-btn" onclick="testEvent('event_view', {event_id: 'test-123', category: 'festival'})">
							📅 Testar Event View
						</button>
						<button class="test-btn" onclick="testEvent('ad_view', {ad_id: 'test-456', category: 'venda'})">
							🛒 Testar Ad View
						</button>
					</div>
				</div>
				<?php endif; ?>

				<!-- Info Section -->
				<div class="info-section">
					<h3>💡 Informações do Sistema</h3>
					<p>
						<strong>Driver:</strong> <?php echo esc_html($config['driver']); ?> | 
						<strong>Domínio:</strong> <?php echo esc_html($config['plausible']['domain'] ?? 'Não configurado'); ?> | 
						<strong>Script:</strong> Apenas injeção (sem API)
					</p>
					<p style="margin-top: 10px;">
						Os contadores mostrados são baseados em localStorage (sessão atual). 
						Para histórico completo e análises avançadas, configure o dashboard público do Plausible.
					</p>
				</div>
			</div>

			<script>
				// Apollo Analytics Dashboard
				class AnalyticsDashboard {
					constructor() {
						this.events = [];
						this.counters = this.loadCounters();
						this.init();
					}

					init() {
						this.updateDisplay();
						this.setupEventListener();
						this.startPolling();
					}

					loadCounters() {
						const stored = localStorage.getItem('apollo_analytics_counters');
						return stored ? JSON.parse(stored) : {
							group_view: 0,
							group_join: 0,
							invite_sent: 0,
							invite_approved: 0,
							event_view: 0,
							event_filter_applied: 0,
							ad_view: 0,
							ad_create: 0
						};
					}

					saveCounters() {
						localStorage.setItem('apollo_analytics_counters', JSON.stringify(this.counters));
					}

					incrementCounter(eventName) {
						if (this.counters.hasOwnProperty(eventName)) {
							this.counters[eventName]++;
							this.saveCounters();
							this.updateDisplay();
						}
					}

					updateDisplay() {
						document.getElementById('groupViews').textContent = this.counters.group_view;
						document.getElementById('groupJoins').textContent = this.counters.group_join;
						document.getElementById('invitesSent').textContent = this.counters.invite_sent;
						document.getElementById('eventViews').textContent = this.counters.event_view;
						document.getElementById('adViews').textContent = this.counters.ad_view;
						document.getElementById('filtersApplied').textContent = this.counters.event_filter_applied;
					}

					addRecentEvent(eventName, props) {
						const event = {
							name: eventName,
							props: props,
							timestamp: new Date()
						};
						
						this.events.unshift(event);
						this.events = this.events.slice(0, 10); // Keep last 10
						
						this.updateRecentEvents();
						this.incrementCounter(eventName);
					}

					updateRecentEvents() {
						const container = document.getElementById('recentEvents');
						
						if (this.events.length === 0) {
							container.innerHTML = '<div class="event-item"><span class="event-name">Nenhum evento registrado ainda</span><span class="event-count">--</span></div>';
							return;
						}
						
						container.innerHTML = this.events.map(event => {
							const timeStr = event.timestamp.toLocaleTimeString('pt-BR');
							return `
								<div class="event-item">
									<span class="event-name">${this.getEventDisplayName(event.name)} - ${timeStr}</span>
									<span class="event-count">1</span>
								</div>
							`;
						}).join('');
					}

					getEventDisplayName(eventName) {
						const names = {
							'group_view': '👥 Visualização de Grupo',
							'group_join': '🤝 Novo Membro',
							'invite_sent': '📧 Convite Enviado',
							'invite_approved': '✅ Convite Aprovado',
							'event_view': '📅 Evento Visualizado',
							'event_filter_applied': '🎯 Filtro Aplicado',
							'ad_view': '🛒 Anúncio Visualizado',
							'ad_create': '📝 Anúncio Criado'
						};
						
						return names[eventName] || eventName;
					}

					setupEventListener() {
						// Listen for Plausible events (if available)
						const originalPlausible = window.plausible;
						window.plausible = (...args) => {
							if (originalPlausible) {
								originalPlausible(...args);
							}
							
							// Capture for local dashboard
							if (args.length >= 1) {
								const eventName = args[0];
								const props = args[1]?.props || {};
								this.addRecentEvent(eventName, props);
							}
						};
					}

					startPolling() {
						// Update display every 5 seconds
						setInterval(() => {
							this.updateDisplay();
						}, 5000);
					}
				}

				// Test function
				function testEvent(eventName, props) {
					if (typeof plausible !== 'undefined') {
						plausible(eventName, { props });
					}
					
					// Also trigger local tracking
					window.analyticsDashboard.addRecentEvent(eventName, props);
					
					// Visual feedback
					const btn = event.target;
					const originalText = btn.textContent;
					btn.textContent = '✅ Enviado!';
					btn.disabled = true;
					
					setTimeout(() => {
						btn.textContent = originalText;
						btn.disabled = false;
					}, 1500);
				}

				// Initialize dashboard
				window.analyticsDashboard = new AnalyticsDashboard();
			</script>
		</body>
		</html>
		<?php
    }

    /**
     * Render disabled state
     */
    private function renderDisabled(): void
    {
        ?>
		<!DOCTYPE html>
		<html lang="pt-BR">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Analytics Desabilitado - Apollo Social</title>
		</head>
		<body style="font-family: system-ui; text-align: center; padding: 50px; color: #666;">
			<h1>📊 Analytics Desabilitado</h1>
			<p>O sistema de analytics está desabilitado na configuração.</p>
			<p>Para ativar, configure <code>config/analytics.php</code></p>
		</body>
		</html>
		<?php
    }
}
