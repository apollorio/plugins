<?php

declare(strict_types=1);
/**
 * Apollo HUB User Dashboard Template
 *
 * Enhanced dashboard for HUB users (producers, promoters, organizers)
 * Based on: HUB_user_private_page.html design
 *
 * @package Apollo_Core
 * @since 1.0.0
 *
 * @param array $args {
 *     @type int    $user_id    User ID (default: current user)
 *     @type string $active_tab Active tab slug
 * }
 */

defined('ABSPATH') || exit;

// Get user.
$user_id = $args['user_id'] ?? get_current_user_id();
if (! $user_id) {
	echo '<div class="ap-auth-required">Por favor, faça login para acessar o HUB.</div>';
	return;
}

$user       = get_userdata($user_id);
$is_own     = get_current_user_id() === $user_id;
$active_tab = $args['active_tab'] ?? 'overview';

// Check if user is HUB member.
$is_hub_member = get_user_meta($user_id, 'apollo_hub_member', true);

// User data.
$display_name = $user->display_name;
$user_bio     = get_user_meta($user_id, 'description', true);
$user_avatar  = get_avatar_url($user_id, array('size' => 200));
$hub_role     = get_user_meta($user_id, 'apollo_hub_role', true) ?: 'Produtor';
$hub_since    = get_user_meta($user_id, 'apollo_hub_since', true) ?: '2024';

// HUB Stats.
$total_events    = (int) get_user_meta($user_id, 'apollo_total_events', true);
$total_reach     = (int) get_user_meta($user_id, 'apollo_total_reach', true);
$total_revenue   = (float) get_user_meta($user_id, 'apollo_total_revenue', true);
$active_collabs  = (int) get_user_meta($user_id, 'apollo_active_collabs', true);

// HUB Tabs.
$tabs = array(
	'overview'   => array(
		'label' => 'Visão Geral',
		'icon'  => 'i-dashboard-v',
	),
	'events'     => array(
		'label' => 'Meus Eventos',
		'icon'  => 'i-calendar-event-v',
	),
	'analytics'  => array(
		'label' => 'Analytics',
		'icon'  => 'i-line-chart-v',
	),
	'team'       => array(
		'label' => 'Time',
		'icon'  => 'i-group-v',
	),
	'finance'    => array(
		'label' => 'Financeiro',
		'icon'  => 'i-money-dollar-circle-v',
	),
	'settings'   => array(
		'label' => 'Config',
		'icon'  => 'i-settings-3-v',
	),
);

?>
<div class="hub-shell">

	<!-- HUB Header -->
	<header class="hub-header">
		<div class="hub-header-main">
			<div class="hub-brand">
				<div class="hub-logo">
					<svg viewBox="0 0 40 40" class="hub-logo-svg">
						<circle cx="20" cy="20" r="18" fill="url(#hubGrad)" />
						<text x="20" y="26" text-anchor="middle" fill="#fff" font-size="14" font-weight="800">HUB</text>
						<defs>
							<linearGradient id="hubGrad" x1="0%" y1="0%" x2="100%" y2="100%">
								<stop offset="0%" stop-color="#f97316" />
								<stop offset="100%" stop-color="#ea580c" />
							</linearGradient>
						</defs>
					</svg>
				</div>
				<div class="hub-brand-text">
					<strong>Apollo HUB</strong>
					<span>Painel do Produtor</span>
				</div>
			</div>

			<div class="hub-user-quick">
				<div class="hub-user-avatar">
					<img src="<?php echo esc_url($user_avatar); ?>" alt="<?php echo esc_attr($display_name); ?>">
				</div>
				<div class="hub-user-info">
					<strong><?php echo esc_html($display_name); ?></strong>
					<span class="hub-role-badge"><?php echo esc_html($hub_role); ?></span>
				</div>
			</div>
		</div>
	</header>

	<!-- Stats Bar -->
	<section class="hub-stats-bar">
		<div class="hub-stat">
			<div class="hub-stat-icon">
				<i class="i-calendar-check-v" aria-hidden="true"></i>
			</div>
			<div class="hub-stat-content">
				<span class="hub-stat-value"><?php echo esc_html($total_events); ?></span>
				<span class="hub-stat-label">Eventos Criados</span>
			</div>
		</div>

		<div class="hub-stat">
			<div class="hub-stat-icon">
				<i class="i-user-heart-v" aria-hidden="true"></i>
			</div>
			<div class="hub-stat-content">
				<span class="hub-stat-value"><?php echo number_format($total_reach, 0, ',', '.'); ?></span>
				<span class="hub-stat-label">Alcance Total</span>
			</div>
		</div>

		<div class="hub-stat">
			<div class="hub-stat-icon">
				<i class="i-hand-coin-v" aria-hidden="true"></i>
			</div>
			<div class="hub-stat-content">
				<span class="hub-stat-value">R$ <?php echo number_format($total_revenue, 0, ',', '.'); ?></span>
				<span class="hub-stat-label">Receita Total</span>
			</div>
		</div>

		<div class="hub-stat">
			<div class="hub-stat-icon">
				<i class="i-team-v" aria-hidden="true"></i>
			</div>
			<div class="hub-stat-content">
				<span class="hub-stat-value"><?php echo esc_html($active_collabs); ?></span>
				<span class="hub-stat-label">Colaborações Ativas</span>
			</div>
		</div>
	</section>

	<!-- Main Layout -->
	<div class="hub-layout">
		<!-- Sidebar Nav -->
		<nav class="hub-sidebar">
			<div class="hub-nav-section">
				<div class="hub-nav-title">Menu Principal</div>
				<?php foreach ($tabs as $slug => $tab) : ?>
					<a
						href="?tab=<?php echo esc_attr($slug); ?>"
						class="hub-nav-item <?php echo $slug === $active_tab ? 'active' : ''; ?>"
						data-tab="<?php echo esc_attr($slug); ?>">
						<i class="<?php echo esc_attr($tab['icon']); ?>" aria-hidden="true"></i>
						<?php echo esc_html($tab['label']); ?>
					</a>
				<?php endforeach; ?>
			</div>

			<div class="hub-nav-section">
				<div class="hub-nav-title">Ações Rápidas</div>
				<a href="#new-event" class="hub-nav-item hub-nav-action">
					<i class="i-add-circle-v" aria-hidden="true"></i>
					Criar Evento
				</a>
				<a href="#invite" class="hub-nav-item hub-nav-action">
					<i class="i-user-add-v" aria-hidden="true"></i>
					Convidar Collab
				</a>
			</div>

			<div class="hub-member-since">
				<i class="i-verified-badge-v" aria-hidden="true"></i>
				Membro HUB desde <?php echo esc_html($hub_since); ?>
			</div>
		</nav>

		<!-- Content Area -->
		<main class="hub-content">
			<!-- Tab: Overview -->
			<div id="hub-tab-overview" class="hub-tab-panel <?php echo 'overview' === $active_tab ? 'active' : ''; ?>">
				<div class="hub-section-header">
					<h2>Visão Geral</h2>
					<p>Bem-vindo ao seu painel de produtor, <?php echo esc_html(explode(' ', $display_name)[0]); ?>!</p>
				</div>

				<div class="hub-cards-grid">
					<!-- Upcoming Events -->
					<div class="hub-card hub-card-large">
						<div class="hub-card-header">
							<h3>
								<i class="i-calendar-todo-v" aria-hidden="true"></i>
								Próximos Eventos
							</h3>
							<a href="?tab=events" class="hub-link">Ver todos</a>
						</div>
						<div class="hub-card-body">
							<div class="hub-event-list">
								<!-- Sample events - would be dynamic -->
								<div class="hub-event-item">
									<div class="hub-event-date">
										<span class="day">14</span>
										<span class="month">JUN</span>
									</div>
									<div class="hub-event-info">
										<strong>Dismantle #4</strong>
										<span>Sáb, 23h • Casa Estranha</span>
									</div>
									<div class="hub-event-status">
										<span class="status-dot status-active"></span>
										Publicado
									</div>
								</div>

								<div class="hub-event-item">
									<div class="hub-event-date">
										<span class="day">21</span>
										<span class="month">JUN</span>
									</div>
									<div class="hub-event-info">
										<strong>Tech House Sessions</strong>
										<span>Sáb, 22h • Warehouse X</span>
									</div>
									<div class="hub-event-status">
										<span class="status-dot status-draft"></span>
										Rascunho
									</div>
								</div>

								<div class="hub-event-item">
									<div class="hub-event-date">
										<span class="day">28</span>
										<span class="month">JUN</span>
									</div>
									<div class="hub-event-info">
										<strong>Underground Vibes</strong>
										<span>Sáb, 00h • Local TBD</span>
									</div>
									<div class="hub-event-status">
										<span class="status-dot status-pending"></span>
										Pendente
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Quick Stats -->
					<div class="hub-card">
						<div class="hub-card-header">
							<h3>
								<i class="i-bar-chart-v" aria-hidden="true"></i>
								Este Mês
							</h3>
						</div>
						<div class="hub-card-body">
							<div class="hub-quick-stats">
								<div class="hub-quick-stat">
									<span class="value">127</span>
									<span class="label">Novos interessados</span>
								</div>
								<div class="hub-quick-stat">
									<span class="value">+34%</span>
									<span class="label">vs. mês anterior</span>
								</div>
								<div class="hub-quick-stat">
									<span class="value">3</span>
									<span class="label">Eventos realizados</span>
								</div>
							</div>
						</div>
					</div>

					<!-- Recent Activity -->
					<div class="hub-card">
						<div class="hub-card-header">
							<h3>
								<i class="i-notification-3-v" aria-hidden="true"></i>
								Atividade Recente
							</h3>
						</div>
						<div class="hub-card-body">
							<div class="hub-activity-list">
								<div class="hub-activity-item">
									<div class="hub-activity-icon">
										<i class="i-user-add-v" aria-hidden="true"></i>
									</div>
									<div class="hub-activity-text">
										<strong>@mariabeatriz</strong> marcou interesse no Dismantle #4
										<span class="time">há 2h</span>
									</div>
								</div>
								<div class="hub-activity-item">
									<div class="hub-activity-icon">
										<i class="i-message-3-v" aria-hidden="true"></i>
									</div>
									<div class="hub-activity-text">
										Novo comentário em Tech House Sessions
										<span class="time">há 5h</span>
									</div>
								</div>
								<div class="hub-activity-item">
									<div class="hub-activity-icon">
										<i class="i-check-double-v" aria-hidden="true"></i>
									</div>
									<div class="hub-activity-text">
										Evento "Minimal Monday" foi aprovado
										<span class="time">há 1d</span>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Team Overview -->
					<div class="hub-card">
						<div class="hub-card-header">
							<h3>
								<i class="i-group-v" aria-hidden="true"></i>
								Seu Time
							</h3>
							<a href="?tab=team" class="hub-link">Gerenciar</a>
						</div>
						<div class="hub-card-body">
							<div class="hub-team-avatars">
								<div class="hub-team-avatar" title="Você">
									<img src="<?php echo esc_url($user_avatar); ?>" alt="">
								</div>
								<div class="hub-team-avatar" title="Colaborador">
									<img src="https://i.pravatar.cc/80?img=12" alt="">
								</div>
								<div class="hub-team-avatar" title="Colaborador">
									<img src="https://i.pravatar.cc/80?img=33" alt="">
								</div>
								<div class="hub-team-add">
									<i class="i-add-v" aria-hidden="true"></i>
								</div>
							</div>
							<p class="hub-team-count">3 membros ativos</p>
						</div>
					</div>
				</div>
			</div>

			<!-- Tab: Events -->
			<div id="hub-tab-events" class="hub-tab-panel <?php echo 'events' === $active_tab ? 'active' : ''; ?>">
				<div class="hub-section-header">
					<h2>Meus Eventos</h2>
					<button class="hub-btn-primary" type="button">
						<i class="i-add-v" aria-hidden="true"></i>
						Criar Evento
					</button>
				</div>

				<div class="hub-events-filters">
					<div class="hub-filter-pills">
						<button class="hub-filter-pill active" type="button">Todos</button>
						<button class="hub-filter-pill" type="button">Publicados</button>
						<button class="hub-filter-pill" type="button">Rascunhos</button>
						<button class="hub-filter-pill" type="button">Passados</button>
					</div>
					<div class="hub-search">
						<i class="i-search-v" aria-hidden="true"></i>
						<input type="text" placeholder="Buscar eventos...">
					</div>
				</div>

				<div class="hub-events-table-wrap">
					<table class="hub-events-table">
						<thead>
							<tr>
								<th>Evento</th>
								<th>Data</th>
								<th>Status</th>
								<th>Interessados</th>
								<th>Ações</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>
									<div class="hub-table-event">
										<div class="hub-table-event-thumb" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
										<strong>Dismantle #4</strong>
									</div>
								</td>
								<td>14 Jun, 23h</td>
								<td><span class="hub-status hub-status-published">Publicado</span></td>
								<td>89</td>
								<td>
									<div class="hub-table-actions">
										<button type="button" title="Editar"><i class="i-edit-2-v" aria-hidden="true"></i></button>
										<button type="button" title="Visualizar"><i class="i-eye-v" aria-hidden="true"></i></button>
										<button type="button" title="Analytics"><i class="i-bar-chart-v" aria-hidden="true"></i></button>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<div class="hub-table-event">
										<div class="hub-table-event-thumb" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);"></div>
										<strong>Tech House Sessions</strong>
									</div>
								</td>
								<td>21 Jun, 22h</td>
								<td><span class="hub-status hub-status-draft">Rascunho</span></td>
								<td>–</td>
								<td>
									<div class="hub-table-actions">
										<button type="button" title="Editar"><i class="i-edit-2-v" aria-hidden="true"></i></button>
										<button type="button" title="Publicar"><i class="i-send-plane-v" aria-hidden="true"></i></button>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Tab: Analytics -->
			<div id="hub-tab-analytics" class="hub-tab-panel <?php echo 'analytics' === $active_tab ? 'active' : ''; ?>">
				<div class="hub-section-header">
					<h2>Analytics</h2>
					<p>Acompanhe o desempenho dos seus eventos</p>
				</div>

				<div class="hub-analytics-placeholder">
					<i class="i-line-chart-v" aria-hidden="true"></i>
					<p>Analytics completo em breve.</p>
					<span>Gráficos de alcance, engajamento e conversão.</span>
				</div>
			</div>

			<!-- Tab: Team -->
			<div id="hub-tab-team" class="hub-tab-panel <?php echo 'team' === $active_tab ? 'active' : ''; ?>">
				<div class="hub-section-header">
					<h2>Gerenciar Time</h2>
					<button class="hub-btn-primary" type="button">
						<i class="i-user-add-v" aria-hidden="true"></i>
						Convidar
					</button>
				</div>

				<div class="hub-team-grid">
					<div class="hub-team-card">
						<div class="hub-team-card-avatar">
							<img src="<?php echo esc_url($user_avatar); ?>" alt="">
						</div>
						<div class="hub-team-card-info">
							<strong><?php echo esc_html($display_name); ?></strong>
							<span class="hub-team-role">Proprietário</span>
						</div>
						<div class="hub-team-card-badge">
							<i class="i-verified-badge-v" aria-hidden="true"></i>
						</div>
					</div>

					<div class="hub-team-card">
						<div class="hub-team-card-avatar">
							<img src="https://i.pravatar.cc/80?img=12" alt="">
						</div>
						<div class="hub-team-card-info">
							<strong>Carlos DJ</strong>
							<span class="hub-team-role">Co-produtor</span>
						</div>
						<button type="button" class="hub-team-remove">
							<i class="i-close-v" aria-hidden="true"></i>
						</button>
					</div>

					<div class="hub-team-card hub-team-card-add">
						<i class="i-add-circle-v" aria-hidden="true"></i>
						<span>Adicionar membro</span>
					</div>
				</div>
			</div>

			<!-- Tab: Finance -->
			<div id="hub-tab-finance" class="hub-tab-panel <?php echo 'finance' === $active_tab ? 'active' : ''; ?>">
				<div class="hub-section-header">
					<h2>Financeiro</h2>
					<p>Acompanhe receitas e custos</p>
				</div>

				<div class="hub-analytics-placeholder">
					<i class="i-money-dollar-circle-v" aria-hidden="true"></i>
					<p>Módulo financeiro em breve.</p>
					<span>Integração com gateways de pagamento e relatórios.</span>
				</div>
			</div>

			<!-- Tab: Settings -->
			<div id="hub-tab-settings" class="hub-tab-panel <?php echo 'settings' === $active_tab ? 'active' : ''; ?>">
				<div class="hub-section-header">
					<h2>Configurações do HUB</h2>
					<p>Personalize seu espaço de produtor</p>
				</div>

				<div class="hub-settings-form">
					<div class="hub-form-group">
						<label>Nome de Exibição HUB</label>
						<input type="text" value="<?php echo esc_attr($display_name); ?>">
					</div>

					<div class="hub-form-group">
						<label>Bio do Produtor</label>
						<textarea rows="3" placeholder="Conte um pouco sobre você como produtor..."><?php echo esc_textarea($user_bio); ?></textarea>
					</div>

					<div class="hub-form-group">
						<label>Categoria Principal</label>
						<select>
							<option value="techno">Techno</option>
							<option value="house">House</option>
							<option value="minimal">Minimal</option>
							<option value="bass">Bass Music</option>
							<option value="multi">Multi-gênero</option>
						</select>
					</div>

					<div class="hub-form-actions">
						<button type="button" class="hub-btn-secondary">Cancelar</button>
						<button type="button" class="hub-btn-primary">Salvar Alterações</button>
					</div>
				</div>
			</div>
		</main>
	</div>

</div>

<style>
	/* HUB Dashboard Styles */
	.hub-shell {
		background: var(--ap-bg-page);
		min-height: 100vh;
	}

	/* Header */
	.hub-header {
		background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
		color: #fff;
		padding: 1rem 1.5rem;
		position: sticky;
		top: 0;
		z-index: 100;
	}

	.hub-header-main {
		max-width: 1400px;
		margin: 0 auto;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.hub-brand {
		display: flex;
		align-items: center;
		gap: 0.75rem;
	}

	.hub-logo-svg {
		width: 40px;
		height: 40px;
	}

	.hub-brand-text strong {
		display: block;
		font-size: 1rem;
		font-weight: 800;
	}

	.hub-brand-text span {
		display: block;
		font-size: 0.7rem;
		opacity: 0.7;
		text-transform: uppercase;
		letter-spacing: 0.12em;
	}

	.hub-user-quick {
		display: flex;
		align-items: center;
		gap: 0.75rem;
	}

	.hub-user-avatar {
		width: 40px;
		height: 40px;
		border-radius: 50%;
		overflow: hidden;
		border: 2px solid rgba(255, 255, 255, 0.3);
	}

	.hub-user-avatar img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.hub-user-info strong {
		display: block;
		font-size: 0.85rem;
	}

	.hub-role-badge {
		display: inline-block;
		font-size: 0.6rem;
		text-transform: uppercase;
		letter-spacing: 0.12em;
		background: rgba(249, 115, 22, 0.2);
		color: #fb923c;
		padding: 0.15rem 0.5rem;
		border-radius: 999px;
		margin-top: 0.15rem;
	}

	/* Stats Bar */
	.hub-stats-bar {
		background: #fff;
		border-bottom: 1px solid var(--ap-border-default);
		padding: 1.25rem 1.5rem;
		display: flex;
		gap: 2rem;
		overflow-x: auto;
		justify-content: center;
	}

	.hub-stat {
		display: flex;
		align-items: center;
		gap: 0.75rem;
	}

	.hub-stat-icon {
		width: 44px;
		height: 44px;
		border-radius: 12px;
		background: var(--ap-bg-surface);
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 1.25rem;
		color: #f97316;
	}

	.hub-stat-value {
		display: block;
		font-size: 1.25rem;
		font-weight: 800;
		line-height: 1;
	}

	.hub-stat-label {
		display: block;
		font-size: 0.7rem;
		text-transform: uppercase;
		letter-spacing: 0.1em;
		color: var(--ap-text-muted);
		margin-top: 0.15rem;
	}

	/* Layout */
	.hub-layout {
		max-width: 1400px;
		margin: 0 auto;
		display: grid;
		grid-template-columns: 260px 1fr;
		gap: 0;
		min-height: calc(100vh - 140px);
	}

	@media (max-width: 1024px) {
		.hub-layout {
			grid-template-columns: 1fr;
		}
	}

	/* Sidebar */
	.hub-sidebar {
		background: #fff;
		border-right: 1px solid var(--ap-border-default);
		padding: 1.5rem 1rem;
		display: flex;
		flex-direction: column;
	}

	@media (max-width: 1024px) {
		.hub-sidebar {
			display: none;
		}
	}

	.hub-nav-section {
		margin-bottom: 2rem;
	}

	.hub-nav-title {
		font-size: 0.65rem;
		text-transform: uppercase;
		letter-spacing: 0.16em;
		color: var(--ap-text-muted);
		padding: 0 0.75rem;
		margin-bottom: 0.75rem;
	}

	.hub-nav-item {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		padding: 0.65rem 0.75rem;
		border-radius: 0.75rem;
		font-size: 0.85rem;
		color: var(--ap-text-default);
		transition: all 0.2s;
		margin-bottom: 0.25rem;
	}

	.hub-nav-item:hover {
		background: var(--ap-bg-surface);
	}

	.hub-nav-item.active {
		background: #1e293b;
		color: #fff;
	}

	.hub-nav-item.active i {
		color: #fb923c;
	}

	.hub-nav-action {
		border: 1px dashed var(--ap-border-default);
	}

	.hub-nav-action:hover {
		border-color: #f97316;
		color: #f97316;
	}

	.hub-member-since {
		margin-top: auto;
		display: flex;
		align-items: center;
		gap: 0.5rem;
		font-size: 0.7rem;
		color: var(--ap-text-muted);
		padding: 0.75rem;
		background: var(--ap-bg-surface);
		border-radius: 0.75rem;
	}

	.hub-member-since i {
		color: #f97316;
	}

	/* Content */
	.hub-content {
		padding: 1.5rem;
		background: var(--ap-bg-page);
	}

	.hub-tab-panel {
		display: none;
	}

	.hub-tab-panel.active {
		display: block;
	}

	.hub-section-header {
		margin-bottom: 1.5rem;
		display: flex;
		justify-content: space-between;
		align-items: flex-start;
	}

	.hub-section-header h2 {
		font-size: 1.5rem;
		font-weight: 800;
		margin: 0;
	}

	.hub-section-header p {
		font-size: 0.85rem;
		color: var(--ap-text-muted);
		margin: 0.25rem 0 0;
	}

	/* Cards Grid */
	.hub-cards-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
		gap: 1.25rem;
	}

	.hub-card {
		background: #fff;
		border-radius: 1rem;
		border: 1px solid var(--ap-border-default);
		overflow: hidden;
	}

	.hub-card-large {
		grid-column: span 2;
	}

	@media (max-width: 768px) {
		.hub-card-large {
			grid-column: span 1;
		}
	}

	.hub-card-header {
		padding: 1rem 1.25rem;
		border-bottom: 1px solid var(--ap-border-default);
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.hub-card-header h3 {
		font-size: 0.85rem;
		font-weight: 700;
		margin: 0;
		display: flex;
		align-items: center;
		gap: 0.5rem;
	}

	.hub-card-header h3 i {
		color: var(--ap-text-muted);
	}

	.hub-link {
		font-size: 0.7rem;
		text-transform: uppercase;
		letter-spacing: 0.12em;
		color: #f97316;
	}

	.hub-card-body {
		padding: 1rem 1.25rem;
	}

	/* Event List */
	.hub-event-list {
		display: flex;
		flex-direction: column;
		gap: 0.75rem;
	}

	.hub-event-item {
		display: flex;
		align-items: center;
		gap: 1rem;
		padding: 0.75rem;
		background: var(--ap-bg-surface);
		border-radius: 0.75rem;
	}

	.hub-event-date {
		min-width: 50px;
		text-align: center;
		background: #1e293b;
		color: #fff;
		padding: 0.5rem;
		border-radius: 0.5rem;
	}

	.hub-event-date .day {
		display: block;
		font-size: 1.25rem;
		font-weight: 800;
		line-height: 1;
	}

	.hub-event-date .month {
		display: block;
		font-size: 0.6rem;
		text-transform: uppercase;
		letter-spacing: 0.12em;
		opacity: 0.8;
	}

	.hub-event-info {
		flex: 1;
	}

	.hub-event-info strong {
		display: block;
		font-size: 0.9rem;
	}

	.hub-event-info span {
		display: block;
		font-size: 0.75rem;
		color: var(--ap-text-muted);
	}

	.hub-event-status {
		display: flex;
		align-items: center;
		gap: 0.35rem;
		font-size: 0.7rem;
		text-transform: uppercase;
		letter-spacing: 0.1em;
	}

	.status-dot {
		width: 8px;
		height: 8px;
		border-radius: 50%;
	}

	.status-dot.status-active {
		background: #10b981;
	}

	.status-dot.status-draft {
		background: #f59e0b;
	}

	.status-dot.status-pending {
		background: #6366f1;
	}

	/* Quick Stats */
	.hub-quick-stats {
		display: flex;
		flex-direction: column;
		gap: 1rem;
	}

	.hub-quick-stat {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding-bottom: 0.75rem;
		border-bottom: 1px dashed var(--ap-border-default);
	}

	.hub-quick-stat:last-child {
		border-bottom: none;
		padding-bottom: 0;
	}

	.hub-quick-stat .value {
		font-size: 1.25rem;
		font-weight: 800;
	}

	.hub-quick-stat .label {
		font-size: 0.75rem;
		color: var(--ap-text-muted);
	}

	/* Activity List */
	.hub-activity-list {
		display: flex;
		flex-direction: column;
		gap: 0.75rem;
	}

	.hub-activity-item {
		display: flex;
		gap: 0.75rem;
		align-items: flex-start;
	}

	.hub-activity-icon {
		width: 28px;
		height: 28px;
		border-radius: 50%;
		background: var(--ap-bg-surface);
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 0.75rem;
		color: var(--ap-text-muted);
		flex-shrink: 0;
	}

	.hub-activity-text {
		font-size: 0.8rem;
		line-height: 1.4;
	}

	.hub-activity-text .time {
		display: block;
		font-size: 0.7rem;
		color: var(--ap-text-muted);
		margin-top: 0.15rem;
	}

	/* Team Avatars */
	.hub-team-avatars {
		display: flex;
		gap: -0.5rem;
		margin-bottom: 0.75rem;
	}

	.hub-team-avatar {
		width: 36px;
		height: 36px;
		border-radius: 50%;
		overflow: hidden;
		border: 2px solid #fff;
		margin-left: -8px;
	}

	.hub-team-avatar:first-child {
		margin-left: 0;
	}

	.hub-team-avatar img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.hub-team-add {
		width: 36px;
		height: 36px;
		border-radius: 50%;
		border: 2px dashed var(--ap-border-default);
		display: flex;
		align-items: center;
		justify-content: center;
		color: var(--ap-text-muted);
		cursor: pointer;
		margin-left: -8px;
	}

	.hub-team-count {
		font-size: 0.75rem;
		color: var(--ap-text-muted);
	}

	/* Events Table */
	.hub-events-filters {
		display: flex;
		justify-content: space-between;
		align-items: center;
		gap: 1rem;
		margin-bottom: 1.25rem;
		flex-wrap: wrap;
	}

	.hub-filter-pills {
		display: flex;
		gap: 0.5rem;
	}

	.hub-filter-pill {
		padding: 0.4rem 0.85rem;
		border-radius: 999px;
		font-size: 0.75rem;
		border: 1px solid var(--ap-border-default);
		background: #fff;
		cursor: pointer;
		transition: all 0.2s;
	}

	.hub-filter-pill.active,
	.hub-filter-pill:hover {
		background: #1e293b;
		color: #fff;
		border-color: #1e293b;
	}

	.hub-search {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.5rem 1rem;
		border: 1px solid var(--ap-border-default);
		border-radius: 999px;
		background: #fff;
	}

	.hub-search input {
		border: none;
		outline: none;
		font-size: 0.85rem;
		min-width: 180px;
	}

	.hub-events-table-wrap {
		overflow-x: auto;
	}

	.hub-events-table {
		width: 100%;
		border-collapse: collapse;
		background: #fff;
		border-radius: 1rem;
		overflow: hidden;
		border: 1px solid var(--ap-border-default);
	}

	.hub-events-table th,
	.hub-events-table td {
		padding: 1rem;
		text-align: left;
		border-bottom: 1px solid var(--ap-border-default);
	}

	.hub-events-table th {
		font-size: 0.7rem;
		text-transform: uppercase;
		letter-spacing: 0.12em;
		color: var(--ap-text-muted);
		background: var(--ap-bg-surface);
	}

	.hub-table-event {
		display: flex;
		align-items: center;
		gap: 0.75rem;
	}

	.hub-table-event-thumb {
		width: 40px;
		height: 40px;
		border-radius: 0.5rem;
	}

	.hub-status {
		display: inline-block;
		padding: 0.25rem 0.6rem;
		border-radius: 999px;
		font-size: 0.65rem;
		text-transform: uppercase;
		letter-spacing: 0.1em;
		font-weight: 600;
	}

	.hub-status-published {
		background: #d1fae5;
		color: #065f46;
	}

	.hub-status-draft {
		background: #fef3c7;
		color: #92400e;
	}

	.hub-table-actions {
		display: flex;
		gap: 0.5rem;
	}

	.hub-table-actions button {
		width: 32px;
		height: 32px;
		border-radius: 0.5rem;
		border: 1px solid var(--ap-border-default);
		background: #fff;
		cursor: pointer;
		transition: all 0.2s;
	}

	.hub-table-actions button:hover {
		background: var(--ap-bg-surface);
	}

	/* Buttons */
	.hub-btn-primary {
		display: inline-flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.6rem 1.25rem;
		background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
		color: #fff;
		border: none;
		border-radius: 0.75rem;
		font-size: 0.8rem;
		font-weight: 600;
		cursor: pointer;
		transition: transform 0.2s;
	}

	.hub-btn-primary:hover {
		transform: translateY(-1px);
	}

	.hub-btn-secondary {
		display: inline-flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.6rem 1.25rem;
		background: #fff;
		color: var(--ap-text-default);
		border: 1px solid var(--ap-border-default);
		border-radius: 0.75rem;
		font-size: 0.8rem;
		font-weight: 600;
		cursor: pointer;
	}

	/* Placeholder */
	.hub-analytics-placeholder {
		text-align: center;
		padding: 4rem 2rem;
		background: #fff;
		border-radius: 1rem;
		border: 1px dashed var(--ap-border-default);
	}

	.hub-analytics-placeholder i {
		font-size: 3rem;
		color: var(--ap-text-muted);
		opacity: 0.5;
		margin-bottom: 1rem;
		display: block;
	}

	.hub-analytics-placeholder p {
		font-size: 1rem;
		font-weight: 600;
		margin: 0;
	}

	.hub-analytics-placeholder span {
		font-size: 0.85rem;
		color: var(--ap-text-muted);
	}

	/* Team Grid */
	.hub-team-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
		gap: 1rem;
	}

	.hub-team-card {
		background: #fff;
		border-radius: 1rem;
		border: 1px solid var(--ap-border-default);
		padding: 1.25rem;
		display: flex;
		align-items: center;
		gap: 0.75rem;
	}

	.hub-team-card-avatar {
		width: 48px;
		height: 48px;
		border-radius: 50%;
		overflow: hidden;
	}

	.hub-team-card-avatar img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.hub-team-card-info strong {
		display: block;
		font-size: 0.9rem;
	}

	.hub-team-role {
		font-size: 0.7rem;
		text-transform: uppercase;
		letter-spacing: 0.1em;
		color: var(--ap-text-muted);
	}

	.hub-team-card-badge {
		margin-left: auto;
		color: #f97316;
	}

	.hub-team-remove {
		margin-left: auto;
		width: 28px;
		height: 28px;
		border-radius: 50%;
		border: 1px solid var(--ap-border-default);
		background: #fff;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.hub-team-card-add {
		border-style: dashed;
		flex-direction: column;
		justify-content: center;
		cursor: pointer;
		color: var(--ap-text-muted);
		gap: 0.5rem;
	}

	.hub-team-card-add:hover {
		border-color: #f97316;
		color: #f97316;
	}

	/* Settings Form */
	.hub-settings-form {
		background: #fff;
		border-radius: 1rem;
		border: 1px solid var(--ap-border-default);
		padding: 1.5rem;
		max-width: 600px;
	}

	.hub-form-group {
		margin-bottom: 1.25rem;
	}

	.hub-form-group label {
		display: block;
		font-size: 0.8rem;
		font-weight: 600;
		margin-bottom: 0.5rem;
	}

	.hub-form-group input,
	.hub-form-group textarea,
	.hub-form-group select {
		width: 100%;
		padding: 0.65rem 0.85rem;
		border: 1px solid var(--ap-border-default);
		border-radius: 0.5rem;
		font-size: 0.9rem;
		transition: border-color 0.2s;
	}

	.hub-form-group input:focus,
	.hub-form-group textarea:focus,
	.hub-form-group select:focus {
		outline: none;
		border-color: #f97316;
	}

	.hub-form-actions {
		display: flex;
		gap: 0.75rem;
		justify-content: flex-end;
		padding-top: 1rem;
		border-top: 1px solid var(--ap-border-default);
	}

	/* Dark Mode */
	body.dark-mode .hub-shell {
		background: var(--ap-bg-page);
	}

	body.dark-mode .hub-stats-bar,
	body.dark-mode .hub-sidebar,
	body.dark-mode .hub-card,
	body.dark-mode .hub-events-table,
	body.dark-mode .hub-team-card,
	body.dark-mode .hub-settings-form {
		background: var(--ap-bg-card);
		border-color: var(--ap-border-default);
	}

	body.dark-mode .hub-search,
	body.dark-mode .hub-filter-pill,
	body.dark-mode .hub-table-actions button,
	body.dark-mode .hub-form-group input,
	body.dark-mode .hub-form-group textarea,
	body.dark-mode .hub-form-group select {
		background: var(--ap-bg-surface);
		border-color: var(--ap-border-default);
		color: var(--ap-text-default);
	}

	body.dark-mode .hub-search input {
		background: transparent;
		color: var(--ap-text-default);
	}
</style>

<script>
	(function() {
		const shell = document.querySelector('.hub-shell');
		if (!shell) return;

		// Tab navigation
		const navItems = shell.querySelectorAll('.hub-nav-item[data-tab]');
		const tabPanels = shell.querySelectorAll('.hub-tab-panel');

		navItems.forEach(item => {
			item.addEventListener('click', function(e) {
				e.preventDefault();
				const tabId = this.dataset.tab;

				// Update nav
				navItems.forEach(n => n.classList.remove('active'));
				this.classList.add('active');

				// Update panels
				tabPanels.forEach(p => p.classList.remove('active'));
				const targetPanel = shell.querySelector(`#hub-tab-${tabId}`);
				if (targetPanel) {
					targetPanel.classList.add('active');
				}

				// Update URL
				const url = new URL(window.location);
				url.searchParams.set('tab', tabId);
				history.replaceState(null, '', url);
			});
		});

		// Filter pills
		const filterPills = shell.querySelectorAll('.hub-filter-pill');
		filterPills.forEach(pill => {
			pill.addEventListener('click', function() {
				filterPills.forEach(p => p.classList.remove('active'));
				this.classList.add('active');
			});
		});
	})();
</script>
