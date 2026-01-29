<?php

declare(strict_types=1);
/**
 * Apollo Single Community / Núcleo Page Template
 *
 * Community profile page with description, members, events, posts
 * Based on: comuna_and_nucleao_single_page.html design
 *
 * @package Apollo_Core
 * @since 1.0.0
 *
 * @param array $args {
 *     @type int|WP_Post $community Community post object or ID
 * }
 */

defined('ABSPATH') || exit;

// Get community data.
$community = $args['community'] ?? get_post();
if (is_numeric($community)) {
	$community = get_post($community);
}

if (! $community || 'apollo_community' !== $community->post_type) {
	echo '<div class="ap-error">Comunidade não encontrada.</div>';
	return;
}

$current_user_id = get_current_user_id();

// Community Meta.
$name        = $community->post_title;
$description = $community->post_content;
$cover_image = get_post_meta($community->ID, '_community_cover', true) ?: get_the_post_thumbnail_url($community->ID, 'large');
$logo        = get_post_meta($community->ID, '_community_logo', true);
$type        = get_post_meta($community->ID, '_community_type', true) ?: 'comuna'; // comuna or nucleo
$privacy     = get_post_meta($community->ID, '_community_privacy', true) ?: 'public';
$category    = get_post_meta($community->ID, '_community_category', true);
$location    = get_post_meta($community->ID, '_community_location', true) ?: 'Rio de Janeiro, RJ';
$website     = get_post_meta($community->ID, '_community_website', true);
$instagram   = get_post_meta($community->ID, '_community_instagram', true);

// Stats.
$members_count = (int) get_post_meta($community->ID, '_community_members_count', true);
$events_count  = (int) get_post_meta($community->ID, '_community_events_count', true);
$posts_count   = (int) get_post_meta($community->ID, '_community_posts_count', true);

// Check membership.
$is_member     = false; // Would check user membership status.
$is_admin      = false; // Would check if user is admin.
$pending       = false; // Would check if membership is pending.

// Type labels.
$type_labels = array(
	'comuna' => array(
		'name'  => 'Comuna',
		'icon'  => 'i-community-v',
		'desc'  => 'Comunidade aberta',
	),
	'nucleo' => array(
		'name'  => 'Núcleo',
		'icon'  => 'i-vip-crown-v',
		'desc'  => 'Grupo exclusivo',
	),
);
$type_info = $type_labels[$type] ?? $type_labels['comuna'];

// Get members (sample).
$members = array(); // Would fetch from database.

// Get upcoming events.
$upcoming_events = new WP_Query(array(
	'post_type'      => 'event_listing',
	'posts_per_page' => 4,
	'meta_query'     => array(
		array(
			'key'     => '_event_date',
			'value'   => current_time('Y-m-d'),
			'compare' => '>=',
			'type'    => 'DATE',
		),
		array(
			'key'     => '_event_community',
			'value'   => $community->ID,
			'compare' => '=',
		),
	),
	'orderby'        => 'meta_value',
	'order'          => 'ASC',
	'meta_key'       => '_event_date',
));

// Initials for logo fallback.
$words    = explode(' ', trim($name));
$initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));

?>
<article class="community-page">

	<!-- Cover Header -->
	<header class="community-header">
		<?php if ($cover_image) : ?>
			<div class="community-cover" style="background-image: url('<?php echo esc_url($cover_image); ?>');"></div>
		<?php else : ?>
			<div class="community-cover community-cover-gradient"></div>
		<?php endif; ?>
		<div class="community-cover-overlay"></div>

		<div class="community-header-content">
			<div class="community-logo-wrap">
				<?php if ($logo) : ?>
					<div class="community-logo">
						<img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($name); ?>">
					</div>
				<?php else : ?>
					<div class="community-logo community-logo-initials">
						<?php echo esc_html($initials); ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="community-header-info">
				<div class="community-badges">
					<span class="community-type-badge type-<?php echo esc_attr($type); ?>">
						<i class="<?php echo esc_attr($type_info['icon']); ?>" aria-hidden="true"></i>
						<?php echo esc_html($type_info['name']); ?>
					</span>
					<span class="community-privacy-badge">
						<i class="<?php echo 'public' === $privacy ? 'i-global-v' : 'i-lock-v'; ?>" aria-hidden="true"></i>
						<?php echo 'public' === $privacy ? 'Pública' : 'Privada'; ?>
					</span>
				</div>

				<h1 class="community-name"><?php echo esc_html($name); ?></h1>

				<?php if ($category) : ?>
					<div class="community-category">
						<i class="i-hashtag-v" aria-hidden="true"></i>
						<?php echo esc_html($category); ?>
					</div>
				<?php endif; ?>

				<div class="community-meta-row">
					<span class="meta-item">
						<i class="i-map-pin-v" aria-hidden="true"></i>
						<?php echo esc_html($location); ?>
					</span>
					<span class="meta-item">
						<i class="i-group-v" aria-hidden="true"></i>
						<?php echo number_format($members_count, 0, ',', '.'); ?> membros
					</span>
				</div>
			</div>

			<div class="community-header-actions">
				<?php if ($is_member) : ?>
					<button class="btn-member" type="button" disabled>
						<i class="i-check-v" aria-hidden="true"></i>
						Membro
					</button>
				<?php elseif ($pending) : ?>
					<button class="btn-pending" type="button" disabled>
						<i class="i-time-v" aria-hidden="true"></i>
						Solicitado
					</button>
				<?php else : ?>
					<button class="btn-join" type="button" data-community-id="<?php echo esc_attr($community->ID); ?>">
						<i class="i-add-v" aria-hidden="true"></i>
						<?php echo 'private' === $privacy ? 'Solicitar Entrada' : 'Participar'; ?>
					</button>
				<?php endif; ?>

				<button class="btn-share" type="button">
					<i class="i-share-v" aria-hidden="true"></i>
				</button>

				<?php if ($is_admin) : ?>
					<button class="btn-settings" type="button">
						<i class="i-settings-3-v" aria-hidden="true"></i>
					</button>
				<?php endif; ?>
			</div>
		</div>
	</header>

	<!-- Stats Bar -->
	<section class="community-stats">
		<div class="stat-item">
			<i class="i-group-v" aria-hidden="true"></i>
			<div class="stat-content">
				<span class="stat-value"><?php echo number_format($members_count, 0, ',', '.'); ?></span>
				<span class="stat-label">Membros</span>
			</div>
		</div>
		<div class="stat-item">
			<i class="i-calendar-event-v" aria-hidden="true"></i>
			<div class="stat-content">
				<span class="stat-value"><?php echo esc_html($events_count); ?></span>
				<span class="stat-label">Eventos</span>
			</div>
		</div>
		<div class="stat-item">
			<i class="i-chat-3-v" aria-hidden="true"></i>
			<div class="stat-content">
				<span class="stat-value"><?php echo esc_html($posts_count); ?></span>
				<span class="stat-label">Posts</span>
			</div>
		</div>
	</section>

	<!-- Navigation Tabs -->
	<nav class="community-nav">
		<button class="community-nav-btn active" data-tab="sobre">
			<i class="i-information-v" aria-hidden="true"></i>
			Sobre
		</button>
		<button class="community-nav-btn" data-tab="eventos">
			<i class="i-calendar-v" aria-hidden="true"></i>
			Eventos
		</button>
		<button class="community-nav-btn" data-tab="membros">
			<i class="i-group-v" aria-hidden="true"></i>
			Membros
		</button>
		<button class="community-nav-btn" data-tab="feed">
			<i class="i-chat-3-v" aria-hidden="true"></i>
			Feed
		</button>
		<?php if ($is_admin) : ?>
			<button class="community-nav-btn" data-tab="admin">
				<i class="i-settings-3-v" aria-hidden="true"></i>
				Admin
			</button>
		<?php endif; ?>
	</nav>

	<!-- Main Content -->
	<div class="community-main">

		<!-- Tab: Sobre -->
		<section id="tab-sobre" class="community-tab active">
			<div class="community-grid">
				<div class="community-content-main">

					<!-- About Section -->
					<div class="community-block">
						<h2 class="block-title">
							<i class="i-file-text-v" aria-hidden="true"></i>
							Sobre a <?php echo esc_html($type_info['name']); ?>
						</h2>
						<div class="community-description">
							<?php echo wp_kses_post(wpautop($description)); ?>
						</div>
					</div>

					<!-- Highlighted Members -->
					<div class="community-block">
						<div class="block-header">
							<h2 class="block-title">
								<i class="i-star-v" aria-hidden="true"></i>
								Membros em Destaque
							</h2>
							<a href="#" class="block-link" data-tab-switch="membros">Ver todos</a>
						</div>

						<div class="members-showcase">
							<div class="member-card">
								<div class="member-avatar">
									<img src="https://i.pravatar.cc/80?img=12" alt="">
								</div>
								<div class="member-info">
									<strong>Carlos Lima</strong>
									<span>Fundador</span>
								</div>
								<span class="member-badge badge-admin">
									<i class="i-shield-star-v" aria-hidden="true"></i>
								</span>
							</div>

							<div class="member-card">
								<div class="member-avatar">
									<img src="https://i.pravatar.cc/80?img=25" alt="">
								</div>
								<div class="member-info">
									<strong>Ana Beatriz</strong>
									<span>Moderadora</span>
								</div>
								<span class="member-badge badge-mod">
									<i class="i-verified-badge-v" aria-hidden="true"></i>
								</span>
							</div>

							<div class="member-card">
								<div class="member-avatar">
									<img src="https://i.pravatar.cc/80?img=33" alt="">
								</div>
								<div class="member-info">
									<strong>Pedro DJ</strong>
									<span>Membro Ativo</span>
								</div>
							</div>
						</div>
					</div>

				</div>

				<!-- Sidebar -->
				<aside class="community-sidebar">

					<!-- Quick Info -->
					<div class="sidebar-block">
						<h3 class="sidebar-title">Informações</h3>
						<ul class="info-list">
							<li>
								<i class="i-calendar-2-v" aria-hidden="true"></i>
								<span>Criada em <?php echo get_the_date('M Y', $community->ID); ?></span>
							</li>
							<li>
								<i class="i-map-pin-v" aria-hidden="true"></i>
								<span><?php echo esc_html($location); ?></span>
							</li>
							<?php if ($website) : ?>
								<li>
									<i class="i-global-v" aria-hidden="true"></i>
									<a href="<?php echo esc_url($website); ?>" target="_blank"><?php echo esc_html(parse_url($website, PHP_URL_HOST)); ?></a>
								</li>
							<?php endif; ?>
							<?php if ($instagram) : ?>
								<li>
									<i class="ri-instagram-line"></i>
									<a href="<?php echo esc_url('https://instagram.com/' . ltrim($instagram, '@')); ?>" target="_blank">@<?php echo esc_html(ltrim($instagram, '@')); ?></a>
								</li>
							<?php endif; ?>
						</ul>
					</div>

					<!-- Rules -->
					<div class="sidebar-block">
						<h3 class="sidebar-title">Regras da Comunidade</h3>
						<ol class="rules-list">
							<li>Respeite todos os membros</li>
							<li>Sem spam ou auto-promoção excessiva</li>
							<li>Mantenha o foco no tema da comunidade</li>
							<li>Não compartilhe conteúdo ofensivo</li>
						</ol>
					</div>

					<!-- Related Communities -->
					<div class="sidebar-block">
						<h3 class="sidebar-title">Comunidades Relacionadas</h3>
						<div class="related-communities">
							<a href="#" class="related-item">
								<div class="related-logo">GT</div>
								<div class="related-info">
									<strong>Gay Techno RJ</strong>
									<span>1.2k membros</span>
								</div>
							</a>
							<a href="#" class="related-item">
								<div class="related-logo">MH</div>
								<div class="related-info">
									<strong>Minimal House</strong>
									<span>890 membros</span>
								</div>
							</a>
						</div>
					</div>

				</aside>
			</div>
		</section>

		<!-- Tab: Eventos -->
		<section id="tab-eventos" class="community-tab">
			<div class="community-block">
				<div class="block-header">
					<h2 class="block-title">
						<i class="i-calendar-event-v" aria-hidden="true"></i>
						Próximos Eventos
					</h2>
					<?php if ($is_member) : ?>
						<button class="btn-create-event" type="button">
							<i class="i-add-v" aria-hidden="true"></i>
							Criar Evento
						</button>
					<?php endif; ?>
				</div>

				<?php if ($upcoming_events->have_posts()) : ?>
					<div class="events-list">
						<?php while ($upcoming_events->have_posts()) : $upcoming_events->the_post();
							$event_date = get_post_meta(get_the_ID(), '_event_date', true);
							$event_time = get_post_meta(get_the_ID(), '_event_time', true);
							$event_venue = get_post_meta(get_the_ID(), '_event_venue', true);
							$date_obj = DateTime::createFromFormat('Y-m-d', $event_date);
						?>
							<a href="<?php the_permalink(); ?>" class="event-list-item">
								<div class="event-date-col">
									<span class="event-day"><?php echo $date_obj ? $date_obj->format('d') : '--'; ?></span>
									<span class="event-month"><?php echo $date_obj ? strtoupper($date_obj->format('M')) : '---'; ?></span>
								</div>
								<div class="event-info-col">
									<h3><?php the_title(); ?></h3>
									<span class="event-meta">
										<i class="i-time-v" aria-hidden="true"></i> <?php echo esc_html($event_time); ?>
										<i class="i-map-pin-v" aria-hidden="true"></i> <?php echo esc_html($event_venue); ?>
									</span>
								</div>
								<div class="event-action-col">
									<i class="i-arrow-right-v" aria-hidden="true"></i>
								</div>
							</a>
						<?php endwhile;
						wp_reset_postdata(); ?>
					</div>
				<?php else : ?>
					<div class="empty-state">
						<i class="i-calendar-v" aria-hidden="true"></i>
						<p>Nenhum evento agendado</p>
						<?php if ($is_member) : ?>
							<button class="btn-outline" type="button">Criar o primeiro evento</button>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</section>

		<!-- Tab: Membros -->
		<section id="tab-membros" class="community-tab">
			<div class="community-block">
				<div class="block-header">
					<h2 class="block-title">
						<i class="i-group-v" aria-hidden="true"></i>
						Membros (<?php echo number_format($members_count, 0, ',', '.'); ?>)
					</h2>
					<div class="members-search">
						<i class="i-search-v" aria-hidden="true"></i>
						<input type="text" placeholder="Buscar membros...">
					</div>
				</div>

				<div class="members-grid">
					<!-- Sample members - would be dynamic -->
					<?php for ($i = 1; $i <= 12; $i++) : ?>
						<div class="member-tile">
							<div class="member-tile-avatar">
								<img src="https://i.pravatar.cc/80?img=<?php echo $i + 10; ?>" alt="">
							</div>
							<div class="member-tile-info">
								<strong>Membro <?php echo $i; ?></strong>
								<span>Desde 2024</span>
							</div>
						</div>
					<?php endfor; ?>
				</div>

				<button class="btn-load-more" type="button">Carregar mais membros</button>
			</div>
		</section>

		<!-- Tab: Feed -->
		<section id="tab-feed" class="community-tab">
			<div class="community-block">
				<?php if ($is_member) : ?>
					<div class="post-composer">
						<div class="composer-avatar">
							<img src="<?php echo esc_url(get_avatar_url($current_user_id, array('size' => 80))); ?>" alt="">
						</div>
						<div class="composer-input">
							<textarea placeholder="Compartilhe algo com a comunidade..."></textarea>
							<div class="composer-actions">
								<button type="button" class="composer-btn">
									<i class="i-image-v" aria-hidden="true"></i>
								</button>
								<button type="button" class="composer-btn">
									<i class="i-link-v" aria-hidden="true"></i>
								</button>
								<button type="submit" class="btn-post">Publicar</button>
							</div>
						</div>
					</div>
				<?php endif; ?>

				<div class="feed-list">
					<!-- Sample post -->
					<article class="feed-post">
						<div class="post-header">
							<div class="post-author-avatar">
								<img src="https://i.pravatar.cc/80?img=12" alt="">
							</div>
							<div class="post-author-info">
								<strong>Carlos Lima</strong>
								<span>há 2 horas</span>
							</div>
							<button class="post-menu" type="button">
								<i class="i-more-v" aria-hidden="true"></i>
							</button>
						</div>
						<div class="post-content">
							<p>Galera, confirmado o próximo evento! Vamos ter uma noite épica de techno no warehouse. Preparem-se! 🔊</p>
						</div>
						<div class="post-actions">
							<button class="post-action" type="button">
								<i class="i-heart-v" aria-hidden="true"></i>
								<span>24</span>
							</button>
							<button class="post-action" type="button">
								<i class="i-chat-1-v" aria-hidden="true"></i>
								<span>8</span>
							</button>
							<button class="post-action" type="button">
								<i class="i-share-v" aria-hidden="true"></i>
							</button>
						</div>
					</article>
				</div>
			</div>
		</section>

		<!-- Tab: Admin (if admin) -->
		<?php if ($is_admin) : ?>
			<section id="tab-admin" class="community-tab">
				<div class="community-block">
					<h2 class="block-title">
						<i class="i-settings-3-v" aria-hidden="true"></i>
						Configurações da Comunidade
					</h2>
					<p class="text-muted">Em breve: painel de administração completo.</p>
				</div>
			</section>
		<?php endif; ?>

	</div>

</article>

<style>
	/* Community Page Styles */
	.community-page {
		width: 100%;
		background: var(--ap-bg-page);
	}

	/* Header */
	.community-header {
		position: relative;
		min-height: 320px;
		display: flex;
		align-items: flex-end;
		padding: 2rem 1.5rem;
	}

	@media (min-width: 768px) {
		.community-header {
			min-height: 380px;
			padding: 2.5rem 2rem;
		}
	}

	.community-cover {
		position: absolute;
		inset: 0;
		background-size: cover;
		background-position: center;
	}

	.community-cover-gradient {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	}

	.community-cover-overlay {
		position: absolute;
		inset: 0;
		background: linear-gradient(to top, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0.3) 50%, rgba(0, 0, 0, 0.1) 100%);
	}

	.community-header-content {
		position: relative;
		z-index: 1;
		max-width: 1200px;
		margin: 0 auto;
		width: 100%;
		display: flex;
		flex-direction: column;
		gap: 1.25rem;
		align-items: center;
		text-align: center;
	}

	@media (min-width: 768px) {
		.community-header-content {
			flex-direction: row;
			text-align: left;
			align-items: flex-end;
		}
	}

	.community-logo-wrap {
		flex-shrink: 0;
	}

	.community-logo {
		width: 100px;
		height: 100px;
		border-radius: 1.25rem;
		overflow: hidden;
		border: 4px solid rgba(255, 255, 255, 0.2);
		box-shadow: 0 16px 40px rgba(0, 0, 0, 0.3);
		background: #fff;
	}

	@media (min-width: 768px) {
		.community-logo {
			width: 120px;
			height: 120px;
		}
	}

	.community-logo img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.community-logo-initials {
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 2.5rem;
		font-weight: 900;
		color: #1e293b;
		background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
	}

	.community-header-info {
		flex: 1;
		color: #fff;
	}

	.community-badges {
		display: flex;
		flex-wrap: wrap;
		gap: 0.5rem;
		margin-bottom: 0.75rem;
		justify-content: center;
	}

	@media (min-width: 768px) {
		.community-badges {
			justify-content: flex-start;
		}
	}

	.community-type-badge,
	.community-privacy-badge {
		display: inline-flex;
		align-items: center;
		gap: 0.35rem;
		font-size: 0.65rem;
		text-transform: uppercase;
		letter-spacing: 0.12em;
		padding: 0.25rem 0.65rem;
		border-radius: 999px;
		font-weight: 600;
	}

	.community-type-badge.type-comuna {
		background: rgba(16, 185, 129, 0.2);
		color: #34d399;
		border: 1px solid rgba(16, 185, 129, 0.3);
	}

	.community-type-badge.type-nucleo {
		background: rgba(249, 115, 22, 0.2);
		color: #fb923c;
		border: 1px solid rgba(249, 115, 22, 0.3);
	}

	.community-privacy-badge {
		background: rgba(255, 255, 255, 0.1);
		border: 1px solid rgba(255, 255, 255, 0.2);
	}

	.community-name {
		font-size: 2rem;
		font-weight: 900;
		margin: 0;
		letter-spacing: -0.02em;
		line-height: 1.1;
	}

	@media (min-width: 768px) {
		.community-name {
			font-size: 2.5rem;
		}
	}

	.community-category {
		display: inline-flex;
		align-items: center;
		gap: 0.35rem;
		font-size: 0.8rem;
		opacity: 0.85;
		margin-top: 0.35rem;
	}

	.community-meta-row {
		display: flex;
		flex-wrap: wrap;
		gap: 1rem;
		margin-top: 0.75rem;
		justify-content: center;
	}

	@media (min-width: 768px) {
		.community-meta-row {
			justify-content: flex-start;
		}
	}

	.meta-item {
		display: inline-flex;
		align-items: center;
		gap: 0.35rem;
		font-size: 0.8rem;
		opacity: 0.85;
	}

	/* Header Actions */
	.community-header-actions {
		display: flex;
		gap: 0.5rem;
	}

	.btn-join,
	.btn-member,
	.btn-pending {
		display: inline-flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.65rem 1.25rem;
		border-radius: 999px;
		font-size: 0.8rem;
		font-weight: 600;
		cursor: pointer;
		transition: all 0.2s;
		border: none;
	}

	.btn-join {
		background: #fff;
		color: #0f172a;
	}

	.btn-join:hover {
		transform: translateY(-1px);
		box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
	}

	.btn-member {
		background: rgba(16, 185, 129, 0.2);
		color: #34d399;
	}

	.btn-pending {
		background: rgba(251, 191, 36, 0.2);
		color: #fbbf24;
	}

	.btn-share,
	.btn-settings {
		width: 44px;
		height: 44px;
		border-radius: 50%;
		border: 1px solid rgba(255, 255, 255, 0.3);
		background: transparent;
		color: #fff;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		transition: all 0.2s;
	}

	.btn-share:hover,
	.btn-settings:hover {
		background: rgba(255, 255, 255, 0.1);
	}

	/* Stats */
	.community-stats {
		display: flex;
		justify-content: center;
		gap: 2rem;
		padding: 1.25rem;
		background: #fff;
		border-bottom: 1px solid var(--ap-border-default);
	}

	.stat-item {
		display: flex;
		align-items: center;
		gap: 0.75rem;
	}

	.stat-item>i {
		font-size: 1.25rem;
		color: var(--ap-text-muted);
	}

	.stat-value {
		display: block;
		font-size: 1.25rem;
		font-weight: 800;
		line-height: 1;
	}

	.stat-label {
		display: block;
		font-size: 0.7rem;
		text-transform: uppercase;
		letter-spacing: 0.1em;
		color: var(--ap-text-muted);
	}

	/* Nav */
	.community-nav {
		display: flex;
		gap: 0.25rem;
		padding: 0.75rem 1.5rem;
		background: #fff;
		border-bottom: 1px solid var(--ap-border-default);
		overflow-x: auto;
		justify-content: center;
	}

	.community-nav-btn {
		display: inline-flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.5rem 1rem;
		border-radius: 999px;
		font-size: 0.8rem;
		background: transparent;
		border: 1px solid transparent;
		color: var(--ap-text-muted);
		cursor: pointer;
		white-space: nowrap;
		transition: all 0.2s;
	}

	.community-nav-btn:hover {
		background: var(--ap-bg-surface);
	}

	.community-nav-btn.active {
		background: #1e293b;
		color: #fff;
	}

	.community-nav-btn.active i {
		color: #fb923c;
	}

	/* Main Content */
	.community-main {
		max-width: 1200px;
		margin: 0 auto;
		padding: 2rem 1.5rem;
	}

	.community-tab {
		display: none;
	}

	.community-tab.active {
		display: block;
	}

	.community-grid {
		display: grid;
		grid-template-columns: 1fr;
		gap: 2rem;
	}

	@media (min-width: 1024px) {
		.community-grid {
			grid-template-columns: 1fr 320px;
		}
	}

	/* Blocks */
	.community-block {
		margin-bottom: 2rem;
	}

	.block-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 1.25rem;
	}

	.block-title {
		font-size: 1rem;
		font-weight: 800;
		margin: 0;
		display: flex;
		align-items: center;
		gap: 0.5rem;
	}

	.block-title i {
		color: var(--ap-text-muted);
	}

	.block-link {
		font-size: 0.7rem;
		text-transform: uppercase;
		letter-spacing: 0.12em;
		color: #f97316;
	}

	/* Description */
	.community-description {
		font-size: 0.95rem;
		line-height: 1.7;
		color: var(--ap-text-default);
	}

	.community-description p {
		margin: 0 0 1rem;
	}

	/* Members Showcase */
	.members-showcase {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
		gap: 1rem;
	}

	.member-card {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		padding: 1rem;
		background: #fff;
		border-radius: 1rem;
		border: 1px solid var(--ap-border-default);
	}

	.member-avatar {
		width: 48px;
		height: 48px;
		border-radius: 50%;
		overflow: hidden;
		flex-shrink: 0;
	}

	.member-avatar img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.member-info strong {
		display: block;
		font-size: 0.9rem;
	}

	.member-info span {
		display: block;
		font-size: 0.7rem;
		color: var(--ap-text-muted);
	}

	.member-badge {
		margin-left: auto;
		width: 28px;
		height: 28px;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.badge-admin {
		background: linear-gradient(135deg, #f97316, #ea580c);
		color: #fff;
	}

	.badge-mod {
		background: linear-gradient(135deg, #3b82f6, #2563eb);
		color: #fff;
	}

	/* Sidebar */
	.sidebar-block {
		background: #fff;
		border-radius: 1.25rem;
		border: 1px solid var(--ap-border-default);
		padding: 1.25rem;
		margin-bottom: 1.25rem;
	}

	.sidebar-title {
		font-size: 0.75rem;
		text-transform: uppercase;
		letter-spacing: 0.16em;
		margin: 0 0 1rem;
		font-weight: 800;
	}

	.info-list {
		list-style: none;
		margin: 0;
		padding: 0;
	}

	.info-list li {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		padding: 0.5rem 0;
		font-size: 0.85rem;
		border-bottom: 1px dashed var(--ap-border-default);
	}

	.info-list li:last-child {
		border-bottom: none;
	}

	.info-list li i {
		color: var(--ap-text-muted);
		width: 20px;
		text-align: center;
	}

	.info-list li a {
		color: #f97316;
	}

	.rules-list {
		margin: 0;
		padding-left: 1.25rem;
	}

	.rules-list li {
		font-size: 0.85rem;
		margin-bottom: 0.5rem;
		line-height: 1.4;
	}

	/* Related */
	.related-communities {
		display: flex;
		flex-direction: column;
		gap: 0.75rem;
	}

	.related-item {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		padding: 0.5rem;
		border-radius: 0.75rem;
		transition: background 0.2s;
	}

	.related-item:hover {
		background: var(--ap-bg-surface);
	}

	.related-logo {
		width: 40px;
		height: 40px;
		border-radius: 0.5rem;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		color: #fff;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 0.85rem;
		font-weight: 800;
	}

	.related-info strong {
		display: block;
		font-size: 0.85rem;
	}

	.related-info span {
		display: block;
		font-size: 0.7rem;
		color: var(--ap-text-muted);
	}

	/* Events List */
	.events-list {
		display: flex;
		flex-direction: column;
		gap: 0.75rem;
	}

	.event-list-item {
		display: flex;
		align-items: center;
		gap: 1rem;
		padding: 1rem;
		background: #fff;
		border-radius: 1rem;
		border: 1px solid var(--ap-border-default);
		transition: all 0.2s;
	}

	.event-list-item:hover {
		transform: translateX(4px);
		box-shadow: 0 8px 20px rgba(0, 0, 0, 0.04);
	}

	.event-date-col {
		min-width: 50px;
		text-align: center;
		background: #1e293b;
		color: #fff;
		padding: 0.5rem;
		border-radius: 0.5rem;
	}

	.event-day {
		display: block;
		font-size: 1.25rem;
		font-weight: 800;
		line-height: 1;
	}

	.event-month {
		display: block;
		font-size: 0.6rem;
		text-transform: uppercase;
		letter-spacing: 0.1em;
		opacity: 0.8;
	}

	.event-info-col {
		flex: 1;
	}

	.event-info-col h3 {
		font-size: 0.95rem;
		font-weight: 700;
		margin: 0 0 0.25rem;
	}

	.event-meta {
		font-size: 0.75rem;
		color: var(--ap-text-muted);
		display: flex;
		gap: 0.75rem;
	}

	.event-meta i {
		margin-right: 0.25rem;
	}

	.event-action-col {
		color: var(--ap-text-muted);
	}

	/* Members Grid */
	.members-search {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.5rem 1rem;
		border: 1px solid var(--ap-border-default);
		border-radius: 999px;
		background: #fff;
	}

	.members-search input {
		border: none;
		outline: none;
		font-size: 0.85rem;
		width: 160px;
	}

	.members-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
		gap: 1rem;
		margin-bottom: 1.5rem;
	}

	.member-tile {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		padding: 0.75rem;
		background: #fff;
		border-radius: 1rem;
		border: 1px solid var(--ap-border-default);
	}

	.member-tile-avatar {
		width: 44px;
		height: 44px;
		border-radius: 50%;
		overflow: hidden;
	}

	.member-tile-avatar img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.member-tile-info strong {
		display: block;
		font-size: 0.85rem;
	}

	.member-tile-info span {
		display: block;
		font-size: 0.7rem;
		color: var(--ap-text-muted);
	}

	.btn-load-more {
		display: block;
		width: 100%;
		padding: 0.75rem;
		border: 1px dashed var(--ap-border-default);
		border-radius: 0.75rem;
		background: transparent;
		font-size: 0.8rem;
		cursor: pointer;
		transition: all 0.2s;
	}

	.btn-load-more:hover {
		border-color: #f97316;
		color: #f97316;
	}

	/* Feed */
	.post-composer {
		display: flex;
		gap: 1rem;
		padding: 1.25rem;
		background: #fff;
		border-radius: 1rem;
		border: 1px solid var(--ap-border-default);
		margin-bottom: 1.5rem;
	}

	.composer-avatar {
		width: 44px;
		height: 44px;
		border-radius: 50%;
		overflow: hidden;
		flex-shrink: 0;
	}

	.composer-avatar img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.composer-input {
		flex: 1;
	}

	.composer-input textarea {
		width: 100%;
		border: none;
		resize: none;
		font-size: 0.95rem;
		min-height: 60px;
		outline: none;
	}

	.composer-actions {
		display: flex;
		gap: 0.5rem;
		justify-content: flex-end;
		padding-top: 0.75rem;
		border-top: 1px solid var(--ap-border-default);
	}

	.composer-btn {
		width: 36px;
		height: 36px;
		border-radius: 50%;
		border: 1px solid var(--ap-border-default);
		background: #fff;
		cursor: pointer;
	}

	.btn-post {
		padding: 0.5rem 1.25rem;
		background: #1e293b;
		color: #fff;
		border: none;
		border-radius: 999px;
		font-size: 0.8rem;
		font-weight: 600;
		cursor: pointer;
	}

	/* Feed Post */
	.feed-post {
		background: #fff;
		border-radius: 1rem;
		border: 1px solid var(--ap-border-default);
		padding: 1.25rem;
		margin-bottom: 1rem;
	}

	.post-header {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		margin-bottom: 1rem;
	}

	.post-author-avatar {
		width: 40px;
		height: 40px;
		border-radius: 50%;
		overflow: hidden;
	}

	.post-author-avatar img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.post-author-info strong {
		display: block;
		font-size: 0.9rem;
	}

	.post-author-info span {
		display: block;
		font-size: 0.75rem;
		color: var(--ap-text-muted);
	}

	.post-menu {
		margin-left: auto;
		width: 32px;
		height: 32px;
		border-radius: 50%;
		border: none;
		background: transparent;
		cursor: pointer;
	}

	.post-content {
		font-size: 0.95rem;
		line-height: 1.6;
		margin-bottom: 1rem;
	}

	.post-content p {
		margin: 0;
	}

	.post-actions {
		display: flex;
		gap: 1rem;
		padding-top: 0.75rem;
		border-top: 1px solid var(--ap-border-default);
	}

	.post-action {
		display: flex;
		align-items: center;
		gap: 0.35rem;
		padding: 0.35rem 0.75rem;
		border-radius: 999px;
		border: none;
		background: transparent;
		font-size: 0.8rem;
		color: var(--ap-text-muted);
		cursor: pointer;
		transition: all 0.2s;
	}

	.post-action:hover {
		background: var(--ap-bg-surface);
	}

	/* Empty State */
	.empty-state {
		text-align: center;
		padding: 3rem 2rem;
		background: #fff;
		border-radius: 1rem;
		border: 1px dashed var(--ap-border-default);
	}

	.empty-state i {
		font-size: 2.5rem;
		color: var(--ap-text-muted);
		opacity: 0.5;
		margin-bottom: 0.75rem;
		display: block;
	}

	.empty-state p {
		font-size: 0.95rem;
		margin: 0 0 1rem;
	}

	.btn-outline {
		padding: 0.5rem 1.25rem;
		border: 1px solid var(--ap-border-default);
		border-radius: 999px;
		background: transparent;
		font-size: 0.8rem;
		cursor: pointer;
	}

	.btn-create-event {
		display: inline-flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.5rem 1rem;
		background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
		color: #fff;
		border: none;
		border-radius: 999px;
		font-size: 0.75rem;
		cursor: pointer;
	}

	/* Dark Mode */
	body.dark-mode .community-stats,
	body.dark-mode .community-nav,
	body.dark-mode .member-card,
	body.dark-mode .sidebar-block,
	body.dark-mode .event-list-item,
	body.dark-mode .member-tile,
	body.dark-mode .post-composer,
	body.dark-mode .feed-post {
		background: var(--ap-bg-card);
		border-color: var(--ap-border-default);
	}

	body.dark-mode .members-search,
	body.dark-mode .composer-btn {
		background: var(--ap-bg-surface);
		border-color: var(--ap-border-default);
	}

	body.dark-mode .members-search input,
	body.dark-mode .composer-input textarea {
		background: transparent;
		color: var(--ap-text-default);
	}
</style>

<script>
	(function() {
		const page = document.querySelector('.community-page');
		if (!page) return;

		// Tab navigation
		const navBtns = page.querySelectorAll('.community-nav-btn');
		const tabPanels = page.querySelectorAll('.community-tab');

		navBtns.forEach(btn => {
			btn.addEventListener('click', function() {
				const tabId = this.dataset.tab;

				// Update nav
				navBtns.forEach(b => b.classList.remove('active'));
				this.classList.add('active');

				// Update panels
				tabPanels.forEach(p => p.classList.remove('active'));
				const targetPanel = page.querySelector(`#tab-${tabId}`);
				if (targetPanel) {
					targetPanel.classList.add('active');
				}

				// Update URL
				const url = new URL(window.location);
				url.searchParams.set('tab', tabId);
				history.replaceState(null, '', url);
			});
		});

		// Tab switch links
		page.querySelectorAll('[data-tab-switch]').forEach(link => {
			link.addEventListener('click', function(e) {
				e.preventDefault();
				const tabId = this.dataset.tabSwitch;
				const navBtn = page.querySelector(`.community-nav-btn[data-tab="${tabId}"]`);
				if (navBtn) navBtn.click();
			});
		});

		// Join button
		const joinBtn = page.querySelector('.btn-join');
		if (joinBtn) {
			joinBtn.addEventListener('click', function() {
				const communityId = this.dataset.communityId;

				if (typeof apolloAjax !== 'undefined') {
					fetch(apolloAjax.ajaxurl, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded'
						},
						body: new URLSearchParams({
							action: 'apollo_join_community',
							community_id: communityId,
							nonce: apolloAjax.nonce
						})
					}).then(res => res.json()).then(data => {
						if (data.success) {
							location.reload();
						}
					});
				}
			});
		}

		// Post actions
		page.querySelectorAll('.post-action').forEach(btn => {
			btn.addEventListener('click', function() {
				const icon = this.querySelector('i');
				if (icon.classList.contains('i-heart-v')) {
					icon.classList.toggle('i-heart-fill-v');
				}
			});
		});
	})();
</script>
