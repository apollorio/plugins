<?php

declare(strict_types=1);
/**
 * Apollo Dashboard Standard Template
 *
 * Generic dashboard template with stats and quick actions
 * Based on: dashboard-standard.html design
 *
 * @package Apollo_Core
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
if (! $current_user->ID) {
	return;
}

$user_id = $current_user->ID;

// Get user stats
$events_count      = count_user_posts($user_id, 'event_listing');
$communities_count = (int) get_user_meta($user_id, '_communities_count', true);
$connections_count = (int) get_user_meta($user_id, '_connections_count', true);
$points_count      = (int) get_user_meta($user_id, '_gamification_points', true);
$level             = get_user_meta($user_id, '_gamification_level', true) ?: 'Novato';
$avatar            = get_avatar_url($user_id, array('size' => 120));

// Get recent events (upcoming)
$upcoming_events = new WP_Query(array(
	'post_type'      => 'event_listing',
	'posts_per_page' => 3,
	'meta_query'     => array(
		array(
			'key'     => '_event_date',
			'value'   => current_time('Y-m-d'),
			'compare' => '>=',
			'type'    => 'DATE',
		),
		array(
			'key'     => '_interested_users',
			'value'   => $user_id,
			'compare' => 'LIKE',
		),
	),
	'orderby'        => 'meta_value',
	'order'          => 'ASC',
	'meta_key'       => '_event_date',
));

// Get notifications
$notifications = get_user_meta($user_id, '_unread_notifications', true);
$notifications = is_array($notifications) ? array_slice($notifications, 0, 5) : array();

// Get recent activity
$recent_activity = get_user_meta($user_id, '_recent_activity', true);
$recent_activity = is_array($recent_activity) ? array_slice($recent_activity, 0, 5) : array();

// Check user role
$is_producer = in_array('apollo_producer', (array) $current_user->roles, true);
$is_hub      = in_array('apollo_hub', (array) $current_user->roles, true);

// Greeting based on time
$hour = (int) current_time('G');
if ($hour < 12) {
	$greeting = 'Bom dia';
} elseif ($hour < 18) {
	$greeting = 'Boa tarde';
} else {
	$greeting = 'Boa noite';
}

?>
<div class="apollo-dashboard-standard">

	<!-- Header -->
	<header class="dashboard-header">
		<div class="header-content">
			<div class="greeting-wrap">
				<span class="greeting"><?php echo esc_html($greeting); ?>,</span>
				<h1><?php echo esc_html($current_user->display_name); ?></h1>
				<p>Bem-vindo de volta ao Apollo!</p>
			</div>
			<div class="header-actions">
				<a href="<?php echo esc_url(home_url('/eventos')); ?>" class="header-btn">
					<i class="i-search-v" aria-hidden="true"></i>
					Explorar
				</a>
				<?php if ($is_producer || $is_hub) : ?>
					<a href="<?php echo esc_url(home_url('/criar-evento')); ?>" class="header-btn primary">
						<i class="i-add-v" aria-hidden="true"></i>
						Criar Evento
					</a>
				<?php endif; ?>
			</div>
		</div>
	</header>

	<!-- Stats Grid -->
	<section class="stats-grid">
		<div class="stat-card">
			<div class="stat-icon events">
				<i class="i-calendar-v" aria-hidden="true"></i>
			</div>
			<div class="stat-info">
				<span class="stat-value"><?php echo number_format($events_count); ?></span>
				<span class="stat-label">Eventos Criados</span>
			</div>
		</div>

		<div class="stat-card">
			<div class="stat-icon communities">
				<i class="i-community-v" aria-hidden="true"></i>
			</div>
			<div class="stat-info">
				<span class="stat-value"><?php echo number_format($communities_count); ?></span>
				<span class="stat-label">Comunidades</span>
			</div>
		</div>

		<div class="stat-card">
			<div class="stat-icon connections">
				<i class="i-user-heart-v" aria-hidden="true"></i>
			</div>
			<div class="stat-info">
				<span class="stat-value"><?php echo number_format($connections_count); ?></span>
				<span class="stat-label">Conexões</span>
			</div>
		</div>

		<div class="stat-card">
			<div class="stat-icon points">
				<i class="i-medal-v" aria-hidden="true"></i>
			</div>
			<div class="stat-info">
				<span class="stat-value"><?php echo number_format($points_count); ?></span>
				<span class="stat-label"><?php echo esc_html($level); ?></span>
			</div>
		</div>
	</section>

	<!-- Main Content Grid -->
	<div class="dashboard-grid">

		<!-- Left Column -->
		<div class="dashboard-main">

			<!-- Upcoming Events -->
			<section class="dash-card">
				<div class="card-header">
					<h2>
						<i class="i-calendar-v" aria-hidden="true"></i>
						Próximos Eventos
					</h2>
					<a href="<?php echo esc_url(home_url('/minha-conta/eventos')); ?>" class="view-all">
						Ver todos
					</a>
				</div>

				<?php if ($upcoming_events->have_posts()) : ?>
					<div class="events-list">
						<?php while ($upcoming_events->have_posts()) : $upcoming_events->the_post();
							$event_date  = get_post_meta(get_the_ID(), '_event_date', true);
							$event_time  = get_post_meta(get_the_ID(), '_event_time_start', true);
							$event_venue = get_post_meta(get_the_ID(), '_event_venue', true);
							$event_thumb = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
							$date_obj    = DateTime::createFromFormat('Y-m-d', $event_date);
						?>
							<a href="<?php the_permalink(); ?>" class="event-item">
								<div class="event-date">
									<span class="day"><?php echo $date_obj ? $date_obj->format('d') : '--'; ?></span>
									<span class="month"><?php echo $date_obj ? strtoupper($date_obj->format('M')) : '---'; ?></span>
								</div>
								<div class="event-thumb" style="background-image: url('<?php echo esc_url($event_thumb); ?>');"></div>
								<div class="event-info">
									<h4><?php the_title(); ?></h4>
									<span class="meta">
										<i class="i-time-v" aria-hidden="true"></i>
										<?php echo esc_html($event_time); ?>
									</span>
									<span class="meta">
										<i class="i-map-pin-v" aria-hidden="true"></i>
										<?php echo esc_html($event_venue); ?>
									</span>
								</div>
								<i class="i-arrow-right-s-v arrow" aria-hidden="true"></i>
							</a>
						<?php endwhile;
						wp_reset_postdata(); ?>
					</div>
				<?php else : ?>
					<div class="empty-state">
						<i class="i-calendar-close-v" aria-hidden="true"></i>
						<p>Nenhum evento marcado</p>
						<a href="<?php echo esc_url(home_url('/eventos')); ?>">Explorar eventos</a>
					</div>
				<?php endif; ?>
			</section>

			<!-- Quick Actions -->
			<section class="dash-card">
				<div class="card-header">
					<h2>
						<i class="i-flashlight-v" aria-hidden="true"></i>
						Ações Rápidas
					</h2>
				</div>
				<div class="quick-actions">
					<a href="<?php echo esc_url(home_url('/eventos')); ?>" class="action-btn">
						<div class="action-icon search">
							<i class="i-search-v" aria-hidden="true"></i>
						</div>
						<span>Buscar Eventos</span>
					</a>

					<a href="<?php echo esc_url(home_url('/djs')); ?>" class="action-btn">
						<div class="action-icon dj">
							<i class="i-disc-v" aria-hidden="true"></i>
						</div>
						<span>Descobrir DJs</span>
					</a>

					<a href="<?php echo esc_url(home_url('/comunidades')); ?>" class="action-btn">
						<div class="action-icon community">
							<i class="i-community-v" aria-hidden="true"></i>
						</div>
						<span>Comunidades</span>
					</a>

					<a href="<?php echo esc_url(home_url('/fornecedores')); ?>" class="action-btn">
						<div class="action-icon supplier">
							<i class="i-store-3-v" aria-hidden="true"></i>
						</div>
						<span>Fornecedores</span>
					</a>

					<?php if ($is_producer || $is_hub) : ?>
						<a href="<?php echo esc_url(home_url('/criar-evento')); ?>" class="action-btn">
							<div class="action-icon create">
								<i class="i-add-v" aria-hidden="true"></i>
							</div>
							<span>Criar Evento</span>
						</a>

						<a href="<?php echo esc_url(home_url('/minha-conta/analytics')); ?>" class="action-btn">
							<div class="action-icon analytics">
								<i class="i-bar-chart-v" aria-hidden="true"></i>
							</div>
							<span>Analytics</span>
						</a>
					<?php endif; ?>
				</div>
			</section>

			<!-- Recent Activity -->
			<section class="dash-card">
				<div class="card-header">
					<h2>
						<i class="i-history-v" aria-hidden="true"></i>
						Atividade Recente
					</h2>
				</div>

				<?php if (! empty($recent_activity)) : ?>
					<ul class="activity-list">
						<?php foreach ($recent_activity as $activity) : ?>
							<li class="activity-item">
								<div class="activity-icon <?php echo esc_attr($activity['type'] ?? ''); ?>">
									<i class="<?php echo esc_attr($activity['icon'] ?? 'i-notification-v'); ?>" aria-hidden="true"></i>
								</div>
								<div class="activity-content">
									<p><?php echo wp_kses_post($activity['text'] ?? ''); ?></p>
									<span class="time"><?php echo esc_html($activity['time'] ?? ''); ?></span>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<div class="empty-state small">
						<i class="i-history-v" aria-hidden="true"></i>
						<p>Nenhuma atividade recente</p>
					</div>
				<?php endif; ?>
			</section>

		</div>

		<!-- Right Sidebar -->
		<aside class="dashboard-sidebar">

			<!-- Profile Card -->
			<div class="dash-card profile-card">
				<div class="profile-header">
					<img src="<?php echo esc_url($avatar); ?>" alt="" class="profile-avatar">
					<div class="profile-info">
						<h3><?php echo esc_html($current_user->display_name); ?></h3>
						<span class="level-badge">
							<i class="i-medal-v" aria-hidden="true"></i>
							<?php echo esc_html($level); ?>
						</span>
					</div>
				</div>
				<div class="profile-stats">
					<div class="p-stat">
						<span class="val"><?php echo $events_count; ?></span>
						<span class="lbl">Eventos</span>
					</div>
					<div class="p-stat">
						<span class="val"><?php echo $communities_count; ?></span>
						<span class="lbl">Comunidades</span>
					</div>
					<div class="p-stat">
						<span class="val"><?php echo $connections_count; ?></span>
						<span class="lbl">Conexões</span>
					</div>
				</div>
				<a href="<?php echo esc_url(home_url('/meu-perfil')); ?>" class="profile-link">
					Ver meu perfil
					<i class="i-arrow-right-s-v" aria-hidden="true"></i>
				</a>
			</div>

			<!-- Notifications -->
			<div class="dash-card">
				<div class="card-header">
					<h2>
						<i class="i-notification-v" aria-hidden="true"></i>
						Notificações
					</h2>
					<?php if (! empty($notifications)) : ?>
						<span class="badge"><?php echo count($notifications); ?></span>
					<?php endif; ?>
				</div>

				<?php if (! empty($notifications)) : ?>
					<ul class="notifications-list">
						<?php foreach ($notifications as $notif) : ?>
							<li class="notif-item <?php echo empty($notif['read']) ? 'unread' : ''; ?>">
								<div class="notif-icon">
									<i class="<?php echo esc_attr($notif['icon'] ?? 'i-notification-v'); ?>" aria-hidden="true"></i>
								</div>
								<div class="notif-content">
									<p><?php echo wp_kses_post($notif['text'] ?? ''); ?></p>
									<span class="time"><?php echo esc_html($notif['time'] ?? ''); ?></span>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
					<a href="<?php echo esc_url(home_url('/minha-conta/notificacoes')); ?>" class="view-all-link">
						Ver todas as notificações
					</a>
				<?php else : ?>
					<div class="empty-state small">
						<i class="i-notification-v" aria-hidden="true"></i>
						<p>Sem notificações</p>
					</div>
				<?php endif; ?>
			</div>

			<!-- Gamification Progress -->
			<div class="dash-card gamification-card">
				<div class="card-header">
					<h2>
						<i class="i-trophy-v" aria-hidden="true"></i>
						Sua Jornada
					</h2>
				</div>
				<div class="level-progress">
					<div class="current-level">
						<span class="level-name"><?php echo esc_html($level); ?></span>
						<span class="points"><?php echo number_format($points_count); ?> pts</span>
					</div>
					<div class="progress-bar">
						<?php
						$next_level_points = 1000; // Example threshold
						$progress = min(100, ($points_count / $next_level_points) * 100);
						?>
						<div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
					</div>
					<span class="progress-label">
						<?php echo number_format($next_level_points - $points_count); ?> pts para o próximo nível
					</span>
				</div>
				<div class="achievements">
					<h4>Conquistas Recentes</h4>
					<div class="achievement-icons">
						<div class="achievement" title="Primeiro Evento">
							<i class="i-calendar-check-v" aria-hidden="true"></i>
						</div>
						<div class="achievement" title="Explorador">
							<i class="i-compass-v" aria-hidden="true"></i>
						</div>
						<div class="achievement locked" title="Em breve">
							<i class="i-lock-v" aria-hidden="true"></i>
						</div>
					</div>
				</div>
			</div>

		</aside>
	</div>

</div>

<style>
	/* Apollo Dashboard Standard Styles */
	.apollo-dashboard-standard {
		width: 100%;
		min-height: 100vh;
		background: var(--ap-bg-page);
		padding-bottom: 3rem;
	}

	/* Header */
	.dashboard-header {
		background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
		padding: 2rem 1.5rem;
	}

	@media (min-width: 768px) {
		.dashboard-header {
			padding: 2.5rem 2rem;
		}
	}

	.header-content {
		max-width: 1280px;
		margin: 0 auto;
		display: flex;
		justify-content: space-between;
		align-items: center;
		flex-wrap: wrap;
		gap: 1.5rem;
	}

	.greeting-wrap {
		color: #fff;
	}

	.greeting {
		font-size: 0.9rem;
		opacity: 0.8;
	}

	.greeting-wrap h1 {
		font-size: 1.75rem;
		font-weight: 800;
		margin: 0.25rem 0 0.35rem;
	}

	@media (min-width: 768px) {
		.greeting-wrap h1 {
			font-size: 2.25rem;
		}
	}

	.greeting-wrap p {
		font-size: 0.9rem;
		opacity: 0.7;
		margin: 0;
	}

	.header-actions {
		display: flex;
		gap: 0.75rem;
	}

	.header-btn {
		display: inline-flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.65rem 1.25rem;
		background: rgba(255, 255, 255, 0.1);
		backdrop-filter: blur(8px);
		color: #fff;
		border-radius: 999px;
		font-size: 0.85rem;
		font-weight: 600;
		transition: all 0.2s;
	}

	.header-btn:hover {
		background: rgba(255, 255, 255, 0.2);
	}

	.header-btn.primary {
		background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
	}

	.header-btn.primary:hover {
		transform: translateY(-2px);
		box-shadow: 0 8px 20px rgba(249, 115, 22, 0.35);
	}

	/* Stats Grid */
	.stats-grid {
		max-width: 1280px;
		margin: -2rem auto 0;
		padding: 0 1.5rem;
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 1rem;
		position: relative;
		z-index: 10;
	}

	@media (min-width: 768px) {
		.stats-grid {
			grid-template-columns: repeat(4, 1fr);
			margin-top: -1.5rem;
		}
	}

	.stat-card {
		background: #fff;
		border-radius: 1rem;
		border: 1px solid var(--ap-border-default);
		padding: 1.25rem;
		display: flex;
		align-items: center;
		gap: 1rem;
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
	}

	.stat-icon {
		width: 48px;
		height: 48px;
		border-radius: 0.75rem;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 1.35rem;
	}

	.stat-icon.events {
		background: #dbeafe;
		color: #2563eb;
	}

	.stat-icon.communities {
		background: #dcfce7;
		color: #16a34a;
	}

	.stat-icon.connections {
		background: #fce7f3;
		color: #db2777;
	}

	.stat-icon.points {
		background: #fef3c7;
		color: #d97706;
	}

	.stat-info {
		display: flex;
		flex-direction: column;
	}

	.stat-value {
		font-size: 1.5rem;
		font-weight: 800;
		color: var(--ap-text-default);
		line-height: 1;
	}

	.stat-label {
		font-size: 0.75rem;
		color: var(--ap-text-muted);
		margin-top: 0.25rem;
	}

	/* Dashboard Grid */
	.dashboard-grid {
		max-width: 1280px;
		margin: 2rem auto 0;
		padding: 0 1.5rem;
		display: grid;
		grid-template-columns: 1fr;
		gap: 1.5rem;
	}

	@media (min-width: 992px) {
		.dashboard-grid {
			grid-template-columns: 1fr 360px;
		}
	}

	/* Cards */
	.dash-card {
		background: #fff;
		border-radius: 1.25rem;
		border: 1px solid var(--ap-border-default);
		padding: 1.25rem;
		margin-bottom: 1.5rem;
	}

	.dashboard-sidebar .dash-card {
		margin-bottom: 1.25rem;
	}

	.card-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 1rem;
		padding-bottom: 0.75rem;
		border-bottom: 1px solid var(--ap-border-default);
	}

	.card-header h2 {
		font-size: 1rem;
		font-weight: 700;
		margin: 0;
		display: flex;
		align-items: center;
		gap: 0.5rem;
	}

	.card-header h2 i {
		color: #f97316;
	}

	.view-all {
		font-size: 0.8rem;
		color: #f97316;
		font-weight: 600;
	}

	.badge {
		background: #f97316;
		color: #fff;
		font-size: 0.7rem;
		padding: 0.15rem 0.5rem;
		border-radius: 999px;
		font-weight: 600;
	}

	/* Events List */
	.events-list {
		display: flex;
		flex-direction: column;
		gap: 0.75rem;
	}

	.event-item {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		padding: 0.75rem;
		background: var(--ap-bg-surface);
		border-radius: 0.75rem;
		transition: all 0.2s;
	}

	.event-item:hover {
		background: var(--ap-bg-page);
	}

	.event-date {
		min-width: 50px;
		text-align: center;
		background: #1e293b;
		color: #fff;
		padding: 0.5rem;
		border-radius: 0.5rem;
	}

	.event-date .day {
		display: block;
		font-size: 1.25rem;
		font-weight: 800;
		line-height: 1;
	}

	.event-date .month {
		display: block;
		font-size: 0.55rem;
		text-transform: uppercase;
		opacity: 0.8;
	}

	.event-thumb {
		width: 50px;
		height: 50px;
		border-radius: 0.5rem;
		background-size: cover;
		background-position: center;
		background-color: var(--ap-bg-surface);
		flex-shrink: 0;
	}

	.event-info {
		flex: 1;
		min-width: 0;
	}

	.event-info h4 {
		font-size: 0.9rem;
		font-weight: 700;
		margin: 0 0 0.25rem;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}

	.event-info .meta {
		display: inline-flex;
		align-items: center;
		gap: 0.25rem;
		font-size: 0.75rem;
		color: var(--ap-text-muted);
		margin-right: 0.75rem;
	}

	.event-item .arrow {
		color: var(--ap-text-muted);
	}

	/* Quick Actions */
	.quick-actions {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 0.75rem;
	}

	@media (min-width: 576px) {
		.quick-actions {
			grid-template-columns: repeat(3, 1fr);
		}
	}

	.action-btn {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 0.5rem;
		padding: 1rem;
		background: var(--ap-bg-surface);
		border-radius: 0.75rem;
		text-align: center;
		transition: all 0.2s;
	}

	.action-btn:hover {
		background: var(--ap-bg-page);
		transform: translateY(-2px);
	}

	.action-icon {
		width: 44px;
		height: 44px;
		border-radius: 0.75rem;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 1.25rem;
	}

	.action-icon.search {
		background: #dbeafe;
		color: #2563eb;
	}

	.action-icon.dj {
		background: #e9d5ff;
		color: #9333ea;
	}

	.action-icon.community {
		background: #dcfce7;
		color: #16a34a;
	}

	.action-icon.supplier {
		background: #fed7aa;
		color: #ea580c;
	}

	.action-icon.create {
		background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
		color: #fff;
	}

	.action-icon.analytics {
		background: #cffafe;
		color: #0891b2;
	}

	.action-btn span {
		font-size: 0.8rem;
		font-weight: 600;
		color: var(--ap-text-default);
	}

	/* Activity List */
	.activity-list {
		list-style: none;
		padding: 0;
		margin: 0;
	}

	.activity-item {
		display: flex;
		gap: 0.75rem;
		padding: 0.75rem 0;
		border-bottom: 1px solid var(--ap-border-default);
	}

	.activity-item:last-child {
		border: none;
		padding-bottom: 0;
	}

	.activity-item:first-child {
		padding-top: 0;
	}

	.activity-icon {
		width: 36px;
		height: 36px;
		border-radius: 50%;
		background: var(--ap-bg-surface);
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 1rem;
		color: var(--ap-text-muted);
		flex-shrink: 0;
	}

	.activity-content {
		flex: 1;
	}

	.activity-content p {
		font-size: 0.85rem;
		margin: 0 0 0.25rem;
		line-height: 1.4;
	}

	.activity-content .time {
		font-size: 0.7rem;
		color: var(--ap-text-muted);
	}

	/* Profile Card */
	.profile-card .profile-header {
		display: flex;
		align-items: center;
		gap: 1rem;
		padding-bottom: 1rem;
		border-bottom: 1px solid var(--ap-border-default);
	}

	.profile-avatar {
		width: 64px;
		height: 64px;
		border-radius: 50%;
		object-fit: cover;
		border: 3px solid #fff;
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
	}

	.profile-info h3 {
		font-size: 1rem;
		font-weight: 700;
		margin: 0 0 0.35rem;
	}

	.level-badge {
		display: inline-flex;
		align-items: center;
		gap: 0.35rem;
		font-size: 0.75rem;
		color: #d97706;
		background: #fef3c7;
		padding: 0.2rem 0.6rem;
		border-radius: 999px;
		font-weight: 600;
	}

	.profile-stats {
		display: flex;
		justify-content: space-around;
		padding: 1rem 0;
		border-bottom: 1px solid var(--ap-border-default);
	}

	.p-stat {
		text-align: center;
	}

	.p-stat .val {
		display: block;
		font-size: 1.25rem;
		font-weight: 800;
		color: var(--ap-text-default);
	}

	.p-stat .lbl {
		font-size: 0.7rem;
		color: var(--ap-text-muted);
	}

	.profile-link {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 0.75rem 0 0;
		font-size: 0.85rem;
		font-weight: 600;
		color: #f97316;
	}

	/* Notifications */
	.notifications-list {
		list-style: none;
		padding: 0;
		margin: 0;
	}

	.notif-item {
		display: flex;
		gap: 0.75rem;
		padding: 0.65rem 0;
		border-bottom: 1px solid var(--ap-border-default);
	}

	.notif-item:last-child {
		border: none;
	}

	.notif-item:first-child {
		padding-top: 0;
	}

	.notif-item.unread {
		background: #fffbeb;
		margin: 0 -0.5rem;
		padding-left: 0.5rem;
		padding-right: 0.5rem;
		border-radius: 0.5rem;
	}

	.notif-icon {
		width: 32px;
		height: 32px;
		border-radius: 50%;
		background: var(--ap-bg-surface);
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 0.9rem;
		color: var(--ap-text-muted);
		flex-shrink: 0;
	}

	.notif-content {
		flex: 1;
	}

	.notif-content p {
		font-size: 0.8rem;
		margin: 0 0 0.15rem;
		line-height: 1.4;
	}

	.notif-content .time {
		font-size: 0.65rem;
		color: var(--ap-text-muted);
	}

	.view-all-link {
		display: block;
		text-align: center;
		padding: 0.75rem 0 0;
		font-size: 0.8rem;
		color: #f97316;
		font-weight: 600;
	}

	/* Gamification Card */
	.gamification-card .level-progress {
		margin-bottom: 1.25rem;
	}

	.current-level {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 0.5rem;
	}

	.level-name {
		font-size: 0.9rem;
		font-weight: 700;
		color: var(--ap-text-default);
	}

	.points {
		font-size: 0.85rem;
		color: #f97316;
		font-weight: 600;
	}

	.progress-bar {
		height: 8px;
		background: var(--ap-bg-surface);
		border-radius: 999px;
		overflow: hidden;
	}

	.progress-fill {
		height: 100%;
		background: linear-gradient(90deg, #f97316 0%, #ea580c 100%);
		border-radius: 999px;
		transition: width 0.5s ease;
	}

	.progress-label {
		display: block;
		margin-top: 0.35rem;
		font-size: 0.7rem;
		color: var(--ap-text-muted);
		text-align: right;
	}

	.achievements h4 {
		font-size: 0.8rem;
		font-weight: 700;
		margin: 0 0 0.75rem;
	}

	.achievement-icons {
		display: flex;
		gap: 0.5rem;
	}

	.achievement {
		width: 44px;
		height: 44px;
		border-radius: 0.75rem;
		background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
		color: #d97706;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 1.25rem;
	}

	.achievement.locked {
		background: var(--ap-bg-surface);
		color: var(--ap-text-muted);
	}

	/* Empty States */
	.empty-state {
		text-align: center;
		padding: 2rem 1rem;
	}

	.empty-state.small {
		padding: 1.5rem 1rem;
	}

	.empty-state i {
		font-size: 2rem;
		color: var(--ap-text-muted);
		opacity: 0.5;
		margin-bottom: 0.75rem;
		display: block;
	}

	.empty-state p {
		font-size: 0.85rem;
		color: var(--ap-text-muted);
		margin: 0 0 0.75rem;
	}

	.empty-state a {
		font-size: 0.8rem;
		color: #f97316;
		font-weight: 600;
	}

	/* Dark Mode */
	body.dark-mode .dashboard-header {
		background: linear-gradient(135deg, #0f172a 0%, #020617 100%);
	}

	body.dark-mode .stat-card,
	body.dark-mode .dash-card {
		background: var(--ap-bg-card);
		border-color: var(--ap-border-default);
	}

	body.dark-mode .event-date {
		background: var(--ap-bg-surface);
	}

	body.dark-mode .header-btn:not(.primary) {
		background: rgba(255, 255, 255, 0.05);
	}
</style>

<script>
	(function() {
		const dashboard = document.querySelector('.apollo-dashboard-standard');
		if (!dashboard) return;

		// Nothing specific needed for this template
		// Add any dashboard-specific JS here if needed
	})();
</script>
