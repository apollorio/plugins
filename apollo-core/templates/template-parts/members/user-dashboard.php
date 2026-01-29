<?php

declare(strict_types=1);
/**
 * Apollo User Private Page / Dashboard Template
 *
 * User profile with tabs for interests, communities, settings
 * Based on: user_private_page.html design
 *
 * @package Apollo_Core
 * @since 1.0.0
 *
 * @param array $args {
 *     @type int    $user_id    User ID (default: current user)
 *     @type string $active_tab Active tab slug (default: 'mundo')
 * }
 */

defined('ABSPATH') || exit;

// Get user.
$user_id = $args['user_id'] ?? get_current_user_id();
if (! $user_id) {
	echo '<div class="ap-auth-required">Por favor, faça login para acessar seu perfil.</div>';
	return;
}

$user       = get_userdata($user_id);
$is_own     = get_current_user_id() === $user_id;
$active_tab = $args['active_tab'] ?? 'mundo';

// User meta.
$display_name = $user->display_name;
$user_bio     = get_user_meta($user_id, 'description', true);
$user_city    = get_user_meta($user_id, 'apollo_city', true) ?: 'Rio de Janeiro, RJ';
$user_avatar  = get_avatar_url($user_id, array('size' => 200));
$user_role    = get_user_meta($user_id, 'apollo_role', true) ?: 'Membro';

// Stats.
$interactions = (int) get_user_meta($user_id, 'apollo_interactions', true);
$events_count = (int) get_user_meta($user_id, 'apollo_events_count', true);
$interests    = (int) get_user_meta($user_id, 'apollo_interests_count', true);

// User initials.
$names    = explode(' ', trim($display_name));
$initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));

// Tabs configuration.
$tabs = array(
	'mundo'      => array(
		'label' => 'Meu Mundo',
		'icon'  => 'i-fingerprint-s',
	),
	'interesses' => array(
		'label' => 'Interesses',
		'icon'  => 'i-rocket-s',
	),
	'bolha'      => array(
		'label' => 'Minha Bolha',
		'icon'  => 'i-bubble-chart-s',
	),
	'coevents'   => array(
		'label' => 'Co-Author Eventos',
		'icon'  => 'i-calendar-todo-s',
	),
	'ajustes'    => array(
		'label' => 'Ajustes',
		'icon'  => 'i-equalizer-s',
	),
);

?>
<div class="apollo-shell">

	<!-- Profile Hero -->
	<section class="profile-hero">
		<div class="profile-main-info">
			<div class="profile-avatar-wrap">
				<div class="profile-avatar">
					<img src="<?php echo esc_url($user_avatar); ?>" alt="<?php echo esc_attr($display_name); ?>">
				</div>
				<div class="profile-badge-icon" title="Atividade em alta">
					<i class="i-sparkling-2-s" aria-hidden="true"></i>
				</div>
			</div>

			<div class="profile-details">
				<h1 class="profile-name">
					<?php echo esc_html($display_name); ?>
					<span class="profile-role-badge">
						<i class="i-user-3-v" aria-hidden="true"></i>
						<?php echo esc_html($user_role); ?>
					</span>
				</h1>

				<?php if ($user_bio) : ?>
					<p class="profile-bio"><?php echo esc_html($user_bio); ?></p>
				<?php else : ?>
					<p class="profile-bio">Descobrindo eventos, pessoas e comunidades criativas no Rio — tudo em um só lugar.</p>
				<?php endif; ?>

				<div class="profile-meta-row">
					<span class="meta-tag">
						<i class="i-map-pin-v" aria-hidden="true"></i>
						<?php echo esc_html($user_city); ?>
					</span>
				</div>
			</div>
		</div>

		<div class="profile-stats-grid">
			<div class="stat-pill">
				<span class="stat-label">Interações</span>
				<span class="stat-value"><?php echo esc_html($interactions); ?></span>
			</div>
			<div class="stat-pill">
				<span class="stat-label">Eventos</span>
				<span class="stat-value"><?php echo esc_html(str_pad($events_count, 2, '0', STR_PAD_LEFT)); ?></span>
			</div>
			<div class="stat-pill">
				<span class="stat-label">Interesses</span>
				<span class="stat-value"><?php echo esc_html($interests); ?></span>
			</div>
		</div>
	</section>

	<!-- Main Content -->
	<main class="main-grid">
		<section>
			<!-- Tabs Navigation -->
			<div class="tabs-wrapper">
				<div class="tabs-list" role="tablist" aria-label="Dashboard tabs">
					<?php foreach ($tabs as $slug => $tab) : ?>
						<button
							class="tab-btn <?php echo $slug === $active_tab ? 'active' : ''; ?>"
							type="button"
							role="tab"
							aria-selected="<?php echo $slug === $active_tab ? 'true' : 'false'; ?>"
							data-tab="<?php echo esc_attr($slug); ?>">
							<i class="<?php echo esc_attr($tab['icon']); ?>" aria-hidden="true"></i>
							<?php echo esc_html($tab['label']); ?>
						</button>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Tab: Meu Mundo -->
			<div id="tab-mundo" class="content-section <?php echo 'mundo' === $active_tab ? 'active' : ''; ?>" role="tabpanel">
				<div class="section-header">
					<div>
						<div class="section-title">Hoje no Apollo</div>
						<div class="section-desc">Atalhos, pendências e o que está te chamando agora.</div>
					</div>
					<?php if ($is_own) : ?>
						<div class="quick-actions">
							<button class="btn-pill-sm" type="button">
								<i class="i-message-3-v" aria-hidden="true"></i> Mensagens
							</button>
							<button class="btn-pill-sm" type="button">
								<i class="i-add-circle-v" aria-hidden="true"></i> Novo Post
							</button>
							<button class="btn-pill-sm" type="button">
								<i class="i-calendar-check-v" aria-hidden="true"></i> Novo Evento
							</button>
						</div>
					<?php endif; ?>
				</div>

				<div class="cards-grid">
					<!-- Messages Card -->
					<article class="apollo-card">
						<div class="card-top">
							<span class="card-meta">Mensagens</span>
							<span class="status-badge status-public">4 não lidas</span>
						</div>
						<h3 class="card-title">Inbox em dia</h3>
						<p class="card-text">Conversas recentes e respostas pendentes com amigos do Apollo.</p>
						<div class="card-footer">
							<div class="card-tags">
								<span class="mini-tag">2 chats ativos</span>
								<span class="mini-tag">1 convite</span>
							</div>
							<a href="#" class="link-action">Abrir <i class="i-arrow-right-up-v" aria-hidden="true"></i></a>
						</div>
					</article>

					<!-- Notifications Card -->
					<article class="apollo-card">
						<div class="card-top">
							<span class="card-meta">Notificações</span>
							<span class="status-badge status-public">8 novas</span>
						</div>
						<h3 class="card-title">Centro de alertas</h3>
						<p class="card-text">Menções, respostas e novidades dos seus interesses.</p>
						<div class="card-footer">
							<div class="card-tags">
								<span class="mini-tag">2 menções</span>
								<span class="mini-tag">6 atualizações</span>
							</div>
							<a href="#" class="link-action">Ver tudo <i class="i-arrow-right-up-v" aria-hidden="true"></i></a>
						</div>
					</article>

					<!-- Upcoming Events Card -->
					<article class="apollo-card">
						<div class="card-top">
							<span class="card-meta">Próximos Eventos</span>
							<span class="status-badge status-euvou">#euvou</span>
						</div>
						<h3 class="card-title">Agenda da semana</h3>
						<p class="card-text">Eventos que você marcou interesse ou confirmou presença.</p>
						<div class="card-footer">
							<div class="card-tags">
								<span class="mini-tag">3 confirmados</span>
							</div>
							<a href="#" class="link-action">Ver agenda <i class="i-arrow-right-up-v" aria-hidden="true"></i></a>
						</div>
					</article>

					<!-- Communities Card -->
					<article class="apollo-card">
						<div class="card-top">
							<span class="card-meta">Comunidades</span>
						</div>
						<h3 class="card-title">Suas tribos</h3>
						<p class="card-text">Comunidades e núcleos que você participa ativamente.</p>
						<div class="card-footer">
							<div class="card-tags">
								<span class="mini-tag">5 comunidades</span>
								<span class="mini-tag">2 núcleos</span>
							</div>
							<a href="#" class="link-action">Explorar <i class="i-arrow-right-up-v" aria-hidden="true"></i></a>
						</div>
					</article>
				</div>
			</div>

			<!-- Tab: Interesses -->
			<div id="tab-interesses" class="content-section <?php echo 'interesses' === $active_tab ? 'active' : ''; ?>" role="tabpanel">
				<div class="section-header">
					<div>
						<div class="section-title">Eventos Interessados</div>
						<div class="section-desc">Eventos que você salvou ou marcou interesse.</div>
					</div>
				</div>
				<?php
				// Load saved events grid.
				apollo_get_template_part(
					'template-parts/events/grid',
					null,
					array(
						'per_page'  => 6,
						'card_size' => 'small',
						'filters'   => false,
					)
				);
				?>
			</div>

			<!-- Tab: Minha Bolha -->
			<div id="tab-bolha" class="content-section <?php echo 'bolha' === $active_tab ? 'active' : ''; ?>" role="tabpanel">
				<div class="section-header">
					<div>
						<div class="section-title">Minha Bolha</div>
						<div class="section-desc">Pessoas que você segue e que te seguem.</div>
					</div>
				</div>
				<p class="text-muted">Em breve: visualização do seu círculo social.</p>
			</div>

			<!-- Tab: Co-Author Eventos -->
			<div id="tab-coevents" class="content-section <?php echo 'coevents' === $active_tab ? 'active' : ''; ?>" role="tabpanel">
				<div class="section-header">
					<div>
						<div class="section-title">Eventos como Co-Author</div>
						<div class="section-desc">Eventos onde você é organizador ou colaborador.</div>
					</div>
				</div>
				<p class="text-muted">Você ainda não é co-autor de nenhum evento.</p>
			</div>

			<!-- Tab: Ajustes -->
			<div id="tab-ajustes" class="content-section <?php echo 'ajustes' === $active_tab ? 'active' : ''; ?>" role="tabpanel">
				<div class="section-header">
					<div>
						<div class="section-title">Configurações</div>
						<div class="section-desc">Personalize sua experiência no Apollo.</div>
					</div>
				</div>

				<?php if ($is_own) : ?>
					<div class="settings-card">
						<div class="toggle-row">
							<div class="toggle-label">
								<strong>Modo Escuro</strong>
								<span>Ativar tema dark em toda a plataforma</span>
							</div>
							<label class="switch">
								<input type="checkbox" data-setting="dark_mode">
								<span class="slider"></span>
							</label>
						</div>

						<div class="toggle-row">
							<div class="toggle-label">
								<strong>Notificações por Email</strong>
								<span>Receber alertas de eventos e mensagens</span>
							</div>
							<label class="switch">
								<input type="checkbox" data-setting="email_notifications" checked>
								<span class="slider"></span>
							</label>
						</div>

						<div class="toggle-row">
							<div class="toggle-label">
								<strong>Perfil Público</strong>
								<span>Permitir que outros vejam seu perfil</span>
							</div>
							<label class="switch">
								<input type="checkbox" data-setting="public_profile" checked>
								<span class="slider"></span>
							</label>
						</div>
					</div>

					<button class="btn-full" type="button">
						<i class="i-save-v" aria-hidden="true"></i>
						Salvar Configurações
					</button>
				<?php endif; ?>
			</div>
		</section>

		<!-- Sidebar -->
		<aside>
			<div class="sidebar-block">
				<div class="sidebar-title">
					Resumo Rápido
					<a href="#" class="link-action">Ver mais</a>
				</div>

				<div class="summary-list">
					<div class="summary-item">
						<div class="summary-icon">
							<i class="i-calendar-event-v" aria-hidden="true"></i>
						</div>
						<div class="summary-content">
							<strong>Próximo evento</strong>
							<span>Dismantle #3 - Sábado, 20h</span>
						</div>
					</div>

					<div class="summary-item">
						<div class="summary-icon">
							<i class="i-user-add-v" aria-hidden="true"></i>
						</div>
						<div class="summary-content">
							<strong>Novos seguidores</strong>
							<span>+12 esta semana</span>
						</div>
					</div>

					<div class="summary-item">
						<div class="summary-icon">
							<i class="i-fire-v" aria-hidden="true"></i>
						</div>
						<div class="summary-content">
							<strong>Trending</strong>
							<span>Gay Techno RJ está bombando</span>
						</div>
					</div>
				</div>
			</div>
		</aside>
	</main>

</div>

<style>
	/* User Dashboard Styles */
	.apollo-shell {
		width: 100%;
		max-width: 1200px;
		margin: 0 auto;
		padding: 1.5rem 1.25rem 3rem;
	}

	@media (min-width: 1024px) {
		.apollo-shell {
			padding: 2.5rem 1.5rem 4rem;
		}
	}

	/* Profile Hero */
	.profile-hero {
		border-radius: 1.75rem;
		border: 1px solid rgba(15, 23, 42, 0.06);
		background: #fff;
		box-shadow: 0 24px 60px rgba(15, 23, 42, 0.07);
		padding: 1.75rem 1.5rem;
		margin-bottom: 2rem;
		display: flex;
		flex-direction: column;
		gap: 1.5rem;
	}

	@media (min-width: 768px) {
		.profile-hero {
			flex-direction: row;
			align-items: flex-end;
			justify-content: space-between;
		}
	}

	.profile-main-info {
		display: flex;
		align-items: center;
		gap: 1.25rem;
		width: 100%;
	}

	.profile-avatar-wrap {
		position: relative;
		flex-shrink: 0;
	}

	.profile-avatar {
		width: 80px;
		height: 80px;
		border-radius: 999px;
		overflow: hidden;
		border: 1px solid rgba(0, 0, 0, 0.1);
		box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.75), 0 0 0 5px rgba(0, 0, 0, 0.08);
	}

	@media (min-width: 768px) {
		.profile-avatar {
			width: 100px;
			height: 100px;
		}
	}

	.profile-avatar img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.profile-badge-icon {
		position: absolute;
		bottom: 0;
		right: 0;
		width: 24px;
		height: 24px;
		background: linear-gradient(135deg, #ff5a5f, #ffb347);
		color: #fff;
		border-radius: 50%;
		border: 2px solid #fff;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 0.7rem;
	}

	.profile-details {
		flex: 1;
	}

	.profile-name {
		font-size: 1.5rem;
		font-weight: 700;
		line-height: 1.1;
		margin: 0 0 0.35rem;
		letter-spacing: -0.02em;
		display: flex;
		flex-wrap: wrap;
		gap: 0.5rem;
		align-items: center;
	}

	.profile-role-badge {
		display: inline-flex;
		align-items: center;
		gap: 0.25rem;
		font-size: 0.7rem;
		text-transform: uppercase;
		letter-spacing: 0.12em;
		background: var(--ap-bg-surface);
		border: 1px solid var(--ap-border-default);
		padding: 0.15rem 0.5rem;
		border-radius: 999px;
		color: var(--ap-text-muted);
		font-weight: 600;
	}

	.profile-bio {
		font-size: 0.9rem;
		color: var(--ap-text-muted);
		margin: 0.15rem 0 0.75rem;
		max-width: 560px;
		line-height: 1.4;
	}

	.profile-meta-row {
		display: flex;
		flex-wrap: wrap;
		gap: 0.5rem;
	}

	.meta-tag {
		font-size: 0.7rem;
		letter-spacing: 0.08em;
		text-transform: uppercase;
		color: var(--ap-text-muted);
		opacity: 0.85;
		display: flex;
		align-items: center;
		gap: 0.35rem;
	}

	/* Stats */
	.profile-stats-grid {
		display: flex;
		gap: 0.75rem;
		flex-wrap: wrap;
	}

	.stat-pill {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		min-width: 96px;
		padding: 0.6rem 0.75rem;
		border-radius: 1rem;
		border: 1px solid var(--ap-border-default);
		background: var(--ap-bg-surface);
	}

	.stat-label {
		font-size: 0.65rem;
		text-transform: uppercase;
		letter-spacing: 0.1em;
		color: var(--ap-text-muted);
		margin-bottom: 0.15rem;
	}

	.stat-value {
		font-size: 1rem;
		font-weight: 800;
		line-height: 1;
	}

	/* Main Grid */
	.main-grid {
		display: grid;
		grid-template-columns: 1fr;
		gap: 1.75rem;
	}

	@media (min-width: 1024px) {
		.main-grid {
			grid-template-columns: 2.4fr 1fr;
		}
	}

	/* Tabs */
	.tabs-wrapper {
		margin-bottom: 1.5rem;
		border-bottom: 1px solid var(--ap-border-default);
		padding-bottom: 0.25rem;
	}

	.tabs-list {
		display: flex;
		gap: 0.5rem;
		overflow-x: auto;
		padding-bottom: 0.5rem;
	}

	.tab-btn {
		padding: 0.5rem 1rem;
		border-radius: 999px;
		font-size: 0.75rem;
		text-transform: uppercase;
		letter-spacing: 0.12em;
		color: var(--ap-text-muted);
		border: 1px solid transparent;
		background: transparent;
		white-space: nowrap;
		display: flex;
		align-items: center;
		gap: 0.4rem;
		cursor: pointer;
		transition: all 0.2s;
	}

	.tab-btn:hover {
		background: var(--ap-bg-surface);
	}

	.tab-btn.active {
		background: #1e293b;
		color: #fff;
		border-color: #1e293b;
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
	}

	.tab-btn.active i {
		color: #ffb347;
	}

	/* Content Sections */
	.content-section {
		display: none;
		flex-direction: column;
		gap: 1.25rem;
	}

	.content-section.active {
		display: flex;
	}

	.section-header {
		display: flex;
		justify-content: space-between;
		align-items: flex-end;
		margin-bottom: 0.5rem;
		gap: 1rem;
	}

	.section-title {
		font-size: 0.8rem;
		text-transform: uppercase;
		letter-spacing: 0.16em;
		font-weight: 800;
	}

	.section-desc {
		font-size: 0.8rem;
		color: var(--ap-text-muted);
		margin-top: 0.25rem;
	}

	/* Cards Grid */
	.cards-grid {
		display: grid;
		grid-template-columns: 1fr;
		gap: 1rem;
	}

	@media (min-width: 768px) {
		.cards-grid {
			grid-template-columns: 1fr 1fr;
		}
	}

	.apollo-card {
		border-radius: 1.15rem;
		border: 1px solid var(--ap-border-default);
		background: #fff;
		padding: 1rem;
		display: flex;
		flex-direction: column;
		gap: 0.5rem;
		transition: transform 0.2s, box-shadow 0.2s;
	}

	.apollo-card:hover {
		transform: translateY(-2px);
		box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
	}

	.card-top {
		display: flex;
		justify-content: space-between;
		align-items: flex-start;
		margin-bottom: 0.25rem;
		gap: 0.75rem;
	}

	.card-meta {
		font-size: 0.65rem;
		text-transform: uppercase;
		letter-spacing: 0.14em;
		color: var(--ap-text-muted);
	}

	.card-title {
		font-size: 0.95rem;
		font-weight: 700;
		line-height: 1.3;
	}

	.card-text {
		font-size: 0.8rem;
		color: var(--ap-text-muted);
		line-height: 1.4;
	}

	.card-footer {
		margin-top: auto;
		padding-top: 0.75rem;
		display: flex;
		justify-content: space-between;
		align-items: center;
		border-top: 1px dashed var(--ap-border-default);
		gap: 0.75rem;
	}

	.card-tags {
		display: flex;
		gap: 0.4rem;
		flex-wrap: wrap;
	}

	.mini-tag {
		font-size: 0.65rem;
		padding: 0.15rem 0.5rem;
		border-radius: 4px;
		background: var(--ap-bg-surface);
		color: var(--ap-text-muted);
	}

	/* Status Badges */
	.status-badge {
		font-size: 0.65rem;
		text-transform: uppercase;
		letter-spacing: 0.12em;
		padding: 0.2rem 0.6rem;
		border-radius: 999px;
		font-weight: 900;
		white-space: nowrap;
	}

	.status-euvou {
		background: #111827;
		color: #fff;
	}

	.status-public {
		background: #ecfdf3;
		color: #065f46;
		border: 1px solid #a7f3d0;
	}

	/* Quick Actions */
	.quick-actions {
		display: flex;
		gap: 0.5rem;
		flex-wrap: wrap;
	}

	.btn-pill-sm {
		display: inline-flex;
		align-items: center;
		gap: 0.35rem;
		padding: 0.25rem 0.75rem;
		border-radius: 999px;
		border: 1px solid var(--ap-border-default);
		background: #fff;
		font-size: 0.7rem;
		text-transform: uppercase;
		letter-spacing: 0.1em;
		cursor: pointer;
		transition: all 0.2s;
	}

	.btn-pill-sm:hover {
		background: var(--ap-bg-surface);
	}

	/* Link Action */
	.link-action {
		font-size: 0.7rem;
		text-transform: uppercase;
		letter-spacing: 0.12em;
		display: flex;
		align-items: center;
		gap: 0.25rem;
		opacity: 0.85;
		transition: opacity 0.2s;
	}

	.link-action:hover {
		opacity: 1;
	}

	/* Sidebar */
	.sidebar-block {
		border-radius: 1.5rem;
		border: 1px solid var(--ap-border-default);
		background: #fff;
		padding: 1.25rem;
		margin-bottom: 1.25rem;
	}

	.sidebar-title {
		font-size: 0.75rem;
		text-transform: uppercase;
		letter-spacing: 0.16em;
		margin-bottom: 1rem;
		font-weight: 800;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.summary-list {
		display: flex;
		flex-direction: column;
		gap: 0.85rem;
	}

	.summary-item {
		display: flex;
		gap: 0.75rem;
		align-items: flex-start;
		padding-bottom: 0.85rem;
		border-bottom: 1px dashed var(--ap-border-default);
	}

	.summary-item:last-child {
		border-bottom: none;
		padding-bottom: 0;
	}

	.summary-icon {
		color: #9ca3af;
		margin-top: 0.15rem;
	}

	.summary-content strong {
		display: block;
		font-size: 0.8rem;
		margin-bottom: 0.1rem;
	}

	.summary-content span {
		display: block;
		font-size: 0.75rem;
		color: var(--ap-text-muted);
	}

	/* Settings */
	.settings-card {
		background: var(--ap-bg-surface);
		border-radius: 1rem;
		padding: 1rem;
	}

	.toggle-row {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 1rem;
		padding: 0.6rem 0;
		border-bottom: 1px dashed var(--ap-border-default);
	}

	.toggle-row:last-child {
		border-bottom: none;
	}

	.toggle-label {
		display: flex;
		flex-direction: column;
		gap: 0.2rem;
	}

	.toggle-label strong {
		font-size: 0.78rem;
	}

	.toggle-label span {
		font-size: 0.72rem;
		color: var(--ap-text-muted);
		line-height: 1.2;
	}

	/* Switch */
	.switch {
		position: relative;
		display: inline-block;
		width: 44px;
		height: 26px;
		flex: 0 0 auto;
	}

	.switch input {
		opacity: 0;
		width: 0;
		height: 0;
	}

	.slider {
		position: absolute;
		cursor: pointer;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: #e2e8f0;
		border: 1px solid var(--ap-border-default);
		transition: 0.2s;
		border-radius: 999px;
	}

	.slider:before {
		position: absolute;
		content: "";
		height: 20px;
		width: 20px;
		left: 3px;
		top: 2px;
		background: white;
		transition: 0.2s;
		border-radius: 50%;
		box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
	}

	.switch input:checked+.slider {
		background: #111827;
	}

	.switch input:checked+.slider:before {
		transform: translateX(18px);
	}

	.btn-full {
		width: 100%;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 0.5rem;
		padding: 0.75rem;
		margin-top: 1rem;
		background: #1e293b;
		color: #fff;
		border: none;
		border-radius: 0.75rem;
		font-size: 0.75rem;
		text-transform: uppercase;
		letter-spacing: 0.12em;
		cursor: pointer;
		transition: background 0.2s;
	}

	.btn-full:hover {
		background: #0f172a;
	}

	/* Dark Mode */
	body.dark-mode .profile-hero,
	body.dark-mode .apollo-card,
	body.dark-mode .sidebar-block {
		background: var(--ap-bg-card);
		border-color: var(--ap-border-default);
	}

	body.dark-mode .tab-btn.active {
		background: #f8fafc;
		color: #0f172a;
	}

	body.dark-mode .tab-btn.active i {
		color: #f97316;
	}
</style>

<script>
	(function() {
		const shell = document.querySelector('.apollo-shell');
		if (!shell) return;

		// Tab switching
		const tabBtns = shell.querySelectorAll('.tab-btn');
		const tabPanels = shell.querySelectorAll('.content-section');

		tabBtns.forEach(btn => {
			btn.addEventListener('click', function() {
				const tabId = this.dataset.tab;

				// Update buttons
				tabBtns.forEach(b => {
					b.classList.remove('active');
					b.setAttribute('aria-selected', 'false');
				});
				this.classList.add('active');
				this.setAttribute('aria-selected', 'true');

				// Update panels
				tabPanels.forEach(panel => {
					panel.classList.remove('active');
				});
				const targetPanel = shell.querySelector(`#tab-${tabId}`);
				if (targetPanel) {
					targetPanel.classList.add('active');
				}

				// Update URL without reload
				const url = new URL(window.location);
				url.searchParams.set('tab', tabId);
				history.replaceState(null, '', url);
			});
		});

		// Settings toggles
		const toggles = shell.querySelectorAll('.switch input');
		toggles.forEach(toggle => {
			toggle.addEventListener('change', function() {
				const setting = this.dataset.setting;
				const value = this.checked;

				// Save via AJAX
				if (typeof apolloAjax !== 'undefined') {
					fetch(apolloAjax.ajaxurl, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded'
						},
						body: new URLSearchParams({
							action: 'apollo_save_user_setting',
							setting: setting,
							value: value ? '1' : '0',
							nonce: apolloAjax.nonce
						})
					});
				}

				// Handle dark mode toggle
				if (setting === 'dark_mode') {
					document.body.classList.toggle('dark-mode', value);
				}
			});
		});
	})();
</script>
