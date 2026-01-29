<?php

declare(strict_types=1);
/**
 * Apollo Activity Feed Template
 *
 * Social feed showing activity from connections, communities and events
 * Based on: feed.html design
 *
 * @package Apollo_Core
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

// Current user
$current_user = wp_get_current_user();
$user_id      = $current_user->ID;
$avatar       = get_avatar_url($user_id, array('size' => 40));

// Get user's connections and communities for filtering
$connections  = get_user_meta($user_id, '_apollo_connections', true);
$communities  = get_user_meta($user_id, '_apollo_communities', true);

// Simulate feed items (would come from activity table)
$feed_items = array(
	array(
		'id'        => 1,
		'type'      => 'new_event',
		'user'      => array(
			'name'   => 'DJ Marky',
			'avatar' => 'https://randomuser.me/api/portraits/men/32.jpg',
			'url'    => '#',
			'verified' => true,
		),
		'time'      => '2 horas atrás',
		'content'   => 'Criou um novo evento',
		'event'     => array(
			'title' => 'FUTURIZE - Drum & Bass Night',
			'date'  => '15 Mar',
			'local' => 'Audio Club, SP',
			'cover' => 'https://placehold.co/400x200/1e293b/f97316?text=Event',
			'url'   => '#',
		),
		'likes'     => 42,
		'comments'  => 8,
		'shares'    => 3,
		'liked'     => true,
	),
	array(
		'id'        => 2,
		'type'      => 'post',
		'user'      => array(
			'name'   => 'Núcleo Techno BR',
			'avatar' => 'https://placehold.co/40/1e293b/f97316?text=NT',
			'url'    => '#',
			'verified' => true,
			'is_community' => true,
		),
		'time'      => '4 horas atrás',
		'content'   => 'Confira nossa nova playlist no Spotify com os melhores lançamentos de techno brasileiro! 🎵🔥',
		'link'      => array(
			'url'   => 'https://open.spotify.com',
			'title' => 'Techno BR - Playlist',
			'desc'  => 'Os melhores lançamentos do techno nacional',
			'image' => 'https://placehold.co/120x120/1e293b/22c55e?text=Spotify',
		),
		'likes'     => 156,
		'comments'  => 23,
		'shares'    => 45,
		'liked'     => false,
	),
	array(
		'id'        => 3,
		'type'      => 'photo',
		'user'      => array(
			'name'   => 'Fernanda Lima',
			'avatar' => 'https://randomuser.me/api/portraits/women/44.jpg',
			'url'    => '#',
			'verified' => false,
		),
		'time'      => '6 horas atrás',
		'content'   => 'Ontem foi épico! 🙌 @Underground_SP mandou muito!',
		'photos'    => array(
			'https://placehold.co/400x300/1e293b/f97316?text=Photo+1',
			'https://placehold.co/400x300/1e293b/ea580c?text=Photo+2',
			'https://placehold.co/400x300/1e293b/dc2626?text=Photo+3',
		),
		'likes'     => 89,
		'comments'  => 12,
		'shares'    => 2,
		'liked'     => false,
	),
	array(
		'id'        => 4,
		'type'      => 'milestone',
		'user'      => array(
			'name'   => 'System',
			'avatar' => 'https://placehold.co/40/f97316/fff?text=A',
			'url'    => '#',
		),
		'time'      => '1 dia atrás',
		'content'   => '🎉 Parabéns! Você desbloqueou a conquista "Frequentador Assíduo" por participar de 10 eventos!',
		'badge'     => array(
			'name'  => 'Frequentador Assíduo',
			'icon'  => 'i-medal-v',
			'color' => '#f97316',
		),
		'likes'     => 24,
		'comments'  => 5,
		'shares'    => 0,
		'liked'     => false,
	),
);

// Stories/Status
$stories = array(
	array(
		'name'   => 'Você',
		'avatar' => $avatar,
		'url'    => '#add-story',
		'add'    => true,
		'seen'   => false,
	),
	array(
		'name'   => 'DJ Marky',
		'avatar' => 'https://randomuser.me/api/portraits/men/32.jpg',
		'url'    => '#story-1',
		'seen'   => false,
	),
	array(
		'name'   => 'Núcleo Tech',
		'avatar' => 'https://placehold.co/60/1e293b/f97316?text=NT',
		'url'    => '#story-2',
		'seen'   => true,
	),
	array(
		'name'   => 'Fernanda',
		'avatar' => 'https://randomuser.me/api/portraits/women/44.jpg',
		'url'    => '#story-3',
		'seen'   => false,
	),
	array(
		'name'   => 'Loft Club',
		'avatar' => 'https://placehold.co/60/1e293b/22c55e?text=LC',
		'url'    => '#story-4',
		'seen'   => true,
	),
	array(
		'name'   => 'DJ Low',
		'avatar' => 'https://randomuser.me/api/portraits/men/55.jpg',
		'url'    => '#story-5',
		'seen'   => false,
	),
);

// Suggested connections
$suggestions = array(
	array(
		'name'   => 'Victor Hugo',
		'role'   => 'DJ / Producer',
		'avatar' => 'https://randomuser.me/api/portraits/men/22.jpg',
		'mutual' => 5,
	),
	array(
		'name'   => 'Amanda Costa',
		'role'   => 'Promoter',
		'avatar' => 'https://randomuser.me/api/portraits/women/28.jpg',
		'mutual' => 3,
	),
	array(
		'name'   => 'Crew Underbase',
		'role'   => 'Coletivo',
		'avatar' => 'https://placehold.co/60/1e293b/f97316?text=CU',
		'mutual' => 8,
	),
);

// Upcoming events
$upcoming = array(
	array(
		'title' => 'FUTURIZE',
		'date'  => '15 Mar',
		'local' => 'Audio Club',
	),
	array(
		'title' => 'Underground Session',
		'date'  => '18 Mar',
		'local' => 'D-Edge',
	),
);

?>
<div class="apollo-feed">

	<!-- Feed Container -->
	<div class="feed-container">

		<!-- Main Feed -->
		<main class="feed-main">

			<!-- Stories Row -->
			<div class="stories-row">
				<div class="stories-scroll">
					<?php foreach ($stories as $story) : ?>
						<a href="<?php echo esc_url($story['url']); ?>" class="story-item <?php echo $story['seen'] ? 'seen' : ''; ?>">
							<div class="story-avatar <?php echo ! empty($story['add']) ? 'add-story' : ''; ?>">
								<img src="<?php echo esc_url($story['avatar']); ?>" alt="<?php echo esc_attr($story['name']); ?>">
								<?php if (! empty($story['add'])) : ?>
									<span class="add-icon">
										<i class="i-add-v" aria-hidden="true"></i>
									</span>
								<?php endif; ?>
							</div>
							<span class="story-name"><?php echo esc_html($story['name']); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Create Post -->
			<div class="create-post card">
				<div class="post-input-row">
					<img src="<?php echo esc_url($avatar); ?>" alt="" class="user-avatar">
					<button type="button" class="post-input-btn" id="open-post-modal">
						O que está acontecendo?
					</button>
				</div>
				<div class="post-actions">
					<button type="button" class="post-action" data-type="photo">
						<i class="i-image-v" aria-hidden="true"></i>
						<span>Foto</span>
					</button>
					<button type="button" class="post-action" data-type="event">
						<i class="i-calendar-event-v" aria-hidden="true"></i>
						<span>Evento</span>
					</button>
					<button type="button" class="post-action" data-type="music">
						<i class="i-music-v" aria-hidden="true"></i>
						<span>Música</span>
					</button>
					<button type="button" class="post-action" data-type="poll">
						<i class="i-bar-chart-horizontal-v" aria-hidden="true"></i>
						<span>Enquete</span>
					</button>
				</div>
			</div>

			<!-- Feed Filters -->
			<div class="feed-filters">
				<button type="button" class="filter-btn active" data-filter="all">Tudo</button>
				<button type="button" class="filter-btn" data-filter="events">Eventos</button>
				<button type="button" class="filter-btn" data-filter="photos">Fotos</button>
				<button type="button" class="filter-btn" data-filter="communities">Comunidades</button>
			</div>

			<!-- Feed Items -->
			<div class="feed-list" id="feed-list">
				<?php foreach ($feed_items as $item) : ?>
					<article class="feed-item card" data-id="<?php echo $item['id']; ?>" data-type="<?php echo esc_attr($item['type']); ?>">

						<!-- Item Header -->
						<header class="item-header">
							<a href="<?php echo esc_url($item['user']['url']); ?>" class="user-link">
								<img src="<?php echo esc_url($item['user']['avatar']); ?>" alt="" class="user-avatar">
								<div class="user-info">
									<span class="user-name">
										<?php echo esc_html($item['user']['name']); ?>
										<?php if (! empty($item['user']['verified'])) : ?>
											<i class="i-verified-badge-v verified-badge" aria-hidden="true"></i>
										<?php endif; ?>
										<?php if (! empty($item['user']['is_community'])) : ?>
											<span class="community-badge">Comunidade</span>
										<?php endif; ?>
									</span>
									<span class="post-time"><?php echo esc_html($item['time']); ?></span>
								</div>
							</a>
							<button type="button" class="item-menu-btn" aria-label="Menu">
								<i class="i-more-v" aria-hidden="true"></i>
							</button>
						</header>

						<!-- Item Content -->
						<div class="item-content">
							<?php if (! empty($item['content'])) : ?>
								<p class="item-text"><?php echo wp_kses_post($item['content']); ?></p>
							<?php endif; ?>

							<?php if ($item['type'] === 'new_event' && ! empty($item['event'])) : ?>
								<!-- Event Card -->
								<a href="<?php echo esc_url($item['event']['url']); ?>" class="event-preview">
									<div class="event-cover" style="background-image: url('<?php echo esc_url($item['event']['cover']); ?>')">
										<span class="event-date"><?php echo esc_html($item['event']['date']); ?></span>
									</div>
									<div class="event-info">
										<span class="event-title"><?php echo esc_html($item['event']['title']); ?></span>
										<span class="event-local">
											<i class="i-map-pin-v" aria-hidden="true"></i>
											<?php echo esc_html($item['event']['local']); ?>
										</span>
									</div>
								</a>
							<?php endif; ?>

							<?php if (! empty($item['link'])) : ?>
								<!-- Link Preview -->
								<a href="<?php echo esc_url($item['link']['url']); ?>" class="link-preview" target="_blank" rel="noopener">
									<img src="<?php echo esc_url($item['link']['image']); ?>" alt="" class="link-image">
									<div class="link-info">
										<span class="link-title"><?php echo esc_html($item['link']['title']); ?></span>
										<span class="link-desc"><?php echo esc_html($item['link']['desc']); ?></span>
										<span class="link-url"><?php echo esc_html(parse_url($item['link']['url'], PHP_URL_HOST)); ?></span>
									</div>
								</a>
							<?php endif; ?>

							<?php if (! empty($item['photos'])) : ?>
								<!-- Photo Grid -->
								<div class="photo-grid count-<?php echo count($item['photos']); ?>">
									<?php foreach ($item['photos'] as $idx => $photo) : ?>
										<a href="<?php echo esc_url($photo); ?>" class="photo-item" data-lightbox="post-<?php echo $item['id']; ?>">
											<img src="<?php echo esc_url($photo); ?>" alt="">
											<?php if ($idx === 2 && count($item['photos']) > 3) : ?>
												<span class="more-photos">+<?php echo count($item['photos']) - 3; ?></span>
											<?php endif; ?>
										</a>
										<?php if ($idx >= 2) break; ?>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<?php if (! empty($item['badge'])) : ?>
								<!-- Achievement Badge -->
								<div class="achievement-card">
									<div class="achievement-icon" style="background: <?php echo esc_attr($item['badge']['color']); ?>20; color: <?php echo esc_attr($item['badge']['color']); ?>;">
										<i class="<?php echo esc_attr($item['badge']['icon']); ?>" aria-hidden="true"></i>
									</div>
									<span class="achievement-name"><?php echo esc_html($item['badge']['name']); ?></span>
								</div>
							<?php endif; ?>
						</div>

						<!-- Item Stats -->
						<div class="item-stats">
							<span class="stat-likes">
								<i class="i-heart-v" aria-hidden="true"></i>
								<?php echo esc_html($item['likes']); ?>
							</span>
							<span class="stat-comments"><?php echo esc_html($item['comments']); ?> comentários</span>
						</div>

						<!-- Item Actions -->
						<footer class="item-actions">
							<button type="button" class="action-btn like-btn <?php echo $item['liked'] ? 'liked' : ''; ?>" data-id="<?php echo $item['id']; ?>">
								<i class="<?php echo $item['liked'] ? 'i-heart-fill-v' : 'i-heart-v'; ?>" aria-hidden="true"></i>
								<span>Curtir</span>
							</button>
							<button type="button" class="action-btn comment-btn">
								<i class="i-chat-v" aria-hidden="true"></i>
								<span>Comentar</span>
							</button>
							<button type="button" class="action-btn share-btn">
								<i class="i-share-forward-v" aria-hidden="true"></i>
								<span>Compartilhar</span>
							</button>
						</footer>

						<!-- Comments Preview (expandable) -->
						<div class="comments-section" hidden>
							<div class="comment-form">
								<img src="<?php echo esc_url($avatar); ?>" alt="" class="comment-avatar">
								<input type="text" placeholder="Escreva um comentário..." class="comment-input">
								<button type="submit" class="comment-submit">
									<i class="i-send-plane-v" aria-hidden="true"></i>
								</button>
							</div>
							<div class="comments-list">
								<!-- Comments would load here -->
							</div>
						</div>

					</article>
				<?php endforeach; ?>

				<!-- Load More -->
				<div class="load-more">
					<button type="button" class="load-more-btn" id="load-more">
						<i class="i-loader-v" aria-hidden="true"></i>
						Carregar mais
					</button>
				</div>
			</div>

		</main>

		<!-- Sidebar -->
		<aside class="feed-sidebar">

			<!-- Upcoming Events -->
			<div class="sidebar-card">
				<h3 class="sidebar-title">
					<i class="i-calendar-v" aria-hidden="true"></i>
					Próximos Eventos
				</h3>
				<ul class="upcoming-list">
					<?php foreach ($upcoming as $event) : ?>
						<li class="upcoming-item">
							<div class="upcoming-date">
								<span class="day"><?php echo esc_html(explode(' ', $event['date'])[0]); ?></span>
								<span class="month"><?php echo esc_html(explode(' ', $event['date'])[1]); ?></span>
							</div>
							<div class="upcoming-info">
								<span class="upcoming-title"><?php echo esc_html($event['title']); ?></span>
								<span class="upcoming-local"><?php echo esc_html($event['local']); ?></span>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
				<a href="<?php echo esc_url(home_url('/eventos')); ?>" class="sidebar-link">
					Ver todos os eventos
					<i class="i-arrow-right-v" aria-hidden="true"></i>
				</a>
			</div>

			<!-- Suggestions -->
			<div class="sidebar-card">
				<h3 class="sidebar-title">
					<i class="i-user-add-v" aria-hidden="true"></i>
					Sugestões para você
				</h3>
				<ul class="suggestions-list">
					<?php foreach ($suggestions as $person) : ?>
						<li class="suggestion-item">
							<img src="<?php echo esc_url($person['avatar']); ?>" alt="" class="suggestion-avatar">
							<div class="suggestion-info">
								<span class="suggestion-name"><?php echo esc_html($person['name']); ?></span>
								<span class="suggestion-role"><?php echo esc_html($person['role']); ?></span>
								<span class="suggestion-mutual"><?php echo esc_html($person['mutual']); ?> conexões em comum</span>
							</div>
							<button type="button" class="follow-btn">
								<i class="i-add-v" aria-hidden="true"></i>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<!-- Trending -->
			<div class="sidebar-card">
				<h3 class="sidebar-title">
					<i class="i-fire-v" aria-hidden="true"></i>
					Em Alta
				</h3>
				<ul class="trending-list">
					<li class="trending-item">
						<span class="trend-tag">#TechnoParade</span>
						<span class="trend-count">1.2k posts</span>
					</li>
					<li class="trending-item">
						<span class="trend-tag">#DrumAndBass</span>
						<span class="trend-count">890 posts</span>
					</li>
					<li class="trending-item">
						<span class="trend-tag">#Underground</span>
						<span class="trend-count">654 posts</span>
					</li>
				</ul>
			</div>

			<!-- Footer Links -->
			<div class="sidebar-footer">
				<a href="#">Sobre</a>
				<a href="#">Privacidade</a>
				<a href="#">Termos</a>
				<a href="#">Ajuda</a>
			</div>

		</aside>

	</div>

</div>

<!-- Post Modal -->
<div id="post-modal" class="post-modal" aria-hidden="true">
	<div class="modal-overlay"></div>
	<div class="modal-content">
		<header class="modal-header">
			<h2>Criar Publicação</h2>
			<button type="button" class="modal-close" id="close-post-modal">
				<i class="i-close-v" aria-hidden="true"></i>
			</button>
		</header>
		<form id="post-form">
			<div class="post-composer">
				<img src="<?php echo esc_url($avatar); ?>" alt="" class="user-avatar">
				<textarea placeholder="O que está acontecendo?" id="post-textarea"></textarea>
			</div>
			<div class="post-attachments" id="post-attachments">
				<!-- Attachments preview -->
			</div>
			<footer class="modal-footer">
				<div class="attachment-btns">
					<button type="button" class="attach-btn" data-type="photo">
						<i class="i-image-v" aria-hidden="true"></i>
					</button>
					<button type="button" class="attach-btn" data-type="video">
						<i class="i-video-v" aria-hidden="true"></i>
					</button>
					<button type="button" class="attach-btn" data-type="music">
						<i class="i-music-v" aria-hidden="true"></i>
					</button>
					<button type="button" class="attach-btn" data-type="poll">
						<i class="i-bar-chart-horizontal-v" aria-hidden="true"></i>
					</button>
					<button type="button" class="attach-btn" data-type="location">
						<i class="i-map-pin-v" aria-hidden="true"></i>
					</button>
				</div>
				<button type="submit" class="publish-btn" disabled>
					Publicar
				</button>
			</footer>
		</form>
	</div>
</div>

<style>
	/* Apollo Activity Feed Styles */
	.apollo-feed {
		width: 100%;
		min-height: 100vh;
		background: var(--ap-bg-page);
		padding: 1rem;
	}

	.feed-container {
		max-width: 1100px;
		margin: 0 auto;
		display: grid;
		grid-template-columns: 1fr;
		gap: 1.5rem;
	}

	@media (min-width: 992px) {
		.feed-container {
			grid-template-columns: 1fr 320px;
		}
	}

	/* Cards */
	.card {
		background: #fff;
		border-radius: 1rem;
		border: 1px solid var(--ap-border-default);
	}

	/* Stories Row */
	.stories-row {
		background: #fff;
		border-radius: 1rem;
		padding: 1rem;
		border: 1px solid var(--ap-border-default);
		margin-bottom: 1rem;
	}

	.stories-scroll {
		display: flex;
		gap: 1rem;
		overflow-x: auto;
		padding-bottom: 0.5rem;
		scrollbar-width: none;
	}

	.stories-scroll::-webkit-scrollbar {
		display: none;
	}

	.story-item {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 0.35rem;
		text-decoration: none;
		flex-shrink: 0;
	}

	.story-avatar {
		width: 60px;
		height: 60px;
		border-radius: 50%;
		padding: 3px;
		background: linear-gradient(135deg, #f97316 0%, #ea580c 50%, #dc2626 100%);
		position: relative;
	}

	.story-item.seen .story-avatar {
		background: var(--ap-border-default);
	}

	.story-avatar img {
		width: 100%;
		height: 100%;
		border-radius: 50%;
		object-fit: cover;
		border: 2px solid #fff;
	}

	.story-avatar.add-story {
		background: var(--ap-bg-surface);
		border: 2px dashed var(--ap-border-default);
		padding: 0;
	}

	.add-icon {
		position: absolute;
		bottom: 0;
		right: 0;
		width: 20px;
		height: 20px;
		border-radius: 50%;
		background: #f97316;
		color: #fff;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 0.7rem;
		border: 2px solid #fff;
	}

	.story-name {
		font-size: 0.7rem;
		color: var(--ap-text-default);
		max-width: 60px;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	/* Create Post */
	.create-post {
		padding: 1rem;
		margin-bottom: 1rem;
	}

	.post-input-row {
		display: flex;
		gap: 0.75rem;
		align-items: center;
		margin-bottom: 1rem;
	}

	.user-avatar {
		width: 40px;
		height: 40px;
		border-radius: 50%;
		object-fit: cover;
		flex-shrink: 0;
	}

	.post-input-btn {
		flex: 1;
		padding: 0.75rem 1rem;
		background: var(--ap-bg-surface);
		border: none;
		border-radius: 999px;
		text-align: left;
		color: var(--ap-text-muted);
		cursor: pointer;
		transition: background 0.2s;
	}

	.post-input-btn:hover {
		background: var(--ap-bg-page);
	}

	.post-actions {
		display: flex;
		gap: 0.5rem;
		border-top: 1px solid var(--ap-border-default);
		padding-top: 0.75rem;
	}

	.post-action {
		flex: 1;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 0.35rem;
		padding: 0.5rem;
		border: none;
		background: transparent;
		border-radius: 0.5rem;
		font-size: 0.8rem;
		color: var(--ap-text-muted);
		cursor: pointer;
		transition: all 0.2s;
	}

	.post-action:hover {
		background: var(--ap-bg-surface);
		color: #f97316;
	}

	.post-action i {
		font-size: 1.1rem;
	}

	/* Feed Filters */
	.feed-filters {
		display: flex;
		gap: 0.5rem;
		margin-bottom: 1rem;
		overflow-x: auto;
		padding-bottom: 0.25rem;
	}

	.filter-btn {
		padding: 0.5rem 1rem;
		border: 1px solid var(--ap-border-default);
		background: #fff;
		border-radius: 999px;
		font-size: 0.8rem;
		cursor: pointer;
		white-space: nowrap;
		transition: all 0.2s;
	}

	.filter-btn:hover {
		border-color: #f97316;
	}

	.filter-btn.active {
		background: #1e293b;
		color: #fff;
		border-color: #1e293b;
	}

	/* Feed Items */
	.feed-item {
		padding: 1rem;
		margin-bottom: 1rem;
	}

	.item-header {
		display: flex;
		justify-content: space-between;
		align-items: flex-start;
		margin-bottom: 0.75rem;
	}

	.user-link {
		display: flex;
		gap: 0.75rem;
		text-decoration: none;
		color: inherit;
	}

	.user-info {
		display: flex;
		flex-direction: column;
	}

	.user-name {
		display: flex;
		align-items: center;
		gap: 0.35rem;
		font-weight: 600;
		font-size: 0.95rem;
	}

	.verified-badge {
		color: #3b82f6;
		font-size: 0.9rem;
	}

	.community-badge {
		font-size: 0.65rem;
		background: rgba(249, 115, 22, 0.1);
		color: #f97316;
		padding: 0.15rem 0.5rem;
		border-radius: 999px;
		font-weight: 600;
	}

	.post-time {
		font-size: 0.75rem;
		color: var(--ap-text-muted);
	}

	.item-menu-btn {
		width: 32px;
		height: 32px;
		border-radius: 50%;
		background: transparent;
		border: none;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		color: var(--ap-text-muted);
	}

	.item-menu-btn:hover {
		background: var(--ap-bg-surface);
	}

	/* Item Content */
	.item-content {
		margin-bottom: 0.75rem;
	}

	.item-text {
		font-size: 0.95rem;
		line-height: 1.6;
		margin: 0 0 0.75rem;
	}

	/* Event Preview */
	.event-preview {
		display: block;
		border: 1px solid var(--ap-border-default);
		border-radius: 0.75rem;
		overflow: hidden;
		text-decoration: none;
		color: inherit;
		transition: border-color 0.2s;
	}

	.event-preview:hover {
		border-color: #f97316;
	}

	.event-cover {
		height: 150px;
		background-size: cover;
		background-position: center;
		position: relative;
	}

	.event-date {
		position: absolute;
		top: 0.75rem;
		left: 0.75rem;
		background: #fff;
		color: #f97316;
		font-weight: 700;
		font-size: 0.8rem;
		padding: 0.35rem 0.75rem;
		border-radius: 0.35rem;
	}

	.event-info {
		padding: 1rem;
	}

	.event-title {
		display: block;
		font-weight: 700;
		font-size: 1rem;
		margin-bottom: 0.35rem;
	}

	.event-local {
		display: flex;
		align-items: center;
		gap: 0.35rem;
		font-size: 0.8rem;
		color: var(--ap-text-muted);
	}

	/* Link Preview */
	.link-preview {
		display: flex;
		border: 1px solid var(--ap-border-default);
		border-radius: 0.75rem;
		overflow: hidden;
		text-decoration: none;
		color: inherit;
		transition: border-color 0.2s;
	}

	.link-preview:hover {
		border-color: #f97316;
	}

	.link-image {
		width: 120px;
		height: 100px;
		object-fit: cover;
		flex-shrink: 0;
	}

	.link-info {
		padding: 0.75rem;
		display: flex;
		flex-direction: column;
		gap: 0.25rem;
		min-width: 0;
	}

	.link-title {
		font-weight: 600;
		font-size: 0.9rem;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	.link-desc {
		font-size: 0.8rem;
		color: var(--ap-text-muted);
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	.link-url {
		font-size: 0.7rem;
		color: var(--ap-text-muted);
	}

	/* Photo Grid */
	.photo-grid {
		display: grid;
		gap: 0.25rem;
		border-radius: 0.75rem;
		overflow: hidden;
	}

	.photo-grid.count-1 {
		grid-template-columns: 1fr;
	}

	.photo-grid.count-2 {
		grid-template-columns: 1fr 1fr;
	}

	.photo-grid.count-3 {
		grid-template-columns: 2fr 1fr;
		grid-template-rows: 1fr 1fr;
	}

	.photo-grid.count-3 .photo-item:first-child {
		grid-row: span 2;
	}

	.photo-item {
		position: relative;
		display: block;
		aspect-ratio: 1;
		overflow: hidden;
	}

	.photo-grid.count-1 .photo-item {
		aspect-ratio: 16/9;
	}

	.photo-item img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		transition: transform 0.3s;
	}

	.photo-item:hover img {
		transform: scale(1.05);
	}

	.more-photos {
		position: absolute;
		inset: 0;
		background: rgba(0, 0, 0, 0.6);
		display: flex;
		align-items: center;
		justify-content: center;
		color: #fff;
		font-size: 1.5rem;
		font-weight: 700;
	}

	/* Achievement Card */
	.achievement-card {
		display: flex;
		align-items: center;
		gap: 1rem;
		background: var(--ap-bg-surface);
		padding: 1rem;
		border-radius: 0.75rem;
	}

	.achievement-icon {
		width: 50px;
		height: 50px;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 1.5rem;
	}

	.achievement-name {
		font-weight: 700;
		font-size: 1rem;
	}

	/* Item Stats */
	.item-stats {
		display: flex;
		justify-content: space-between;
		padding: 0.5rem 0;
		font-size: 0.8rem;
		color: var(--ap-text-muted);
		border-bottom: 1px solid var(--ap-border-default);
	}

	.stat-likes {
		display: flex;
		align-items: center;
		gap: 0.35rem;
	}

	.stat-likes i {
		color: #dc2626;
	}

	/* Item Actions */
	.item-actions {
		display: flex;
		gap: 0.5rem;
		padding-top: 0.5rem;
	}

	.action-btn {
		flex: 1;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 0.35rem;
		padding: 0.65rem;
		background: transparent;
		border: none;
		border-radius: 0.5rem;
		font-size: 0.85rem;
		color: var(--ap-text-muted);
		cursor: pointer;
		transition: all 0.2s;
	}

	.action-btn:hover {
		background: var(--ap-bg-surface);
	}

	.action-btn.liked {
		color: #dc2626;
	}

	/* Comments Section */
	.comments-section {
		padding-top: 0.75rem;
		margin-top: 0.75rem;
		border-top: 1px solid var(--ap-border-default);
	}

	.comment-form {
		display: flex;
		gap: 0.75rem;
		align-items: center;
	}

	.comment-avatar {
		width: 32px;
		height: 32px;
		border-radius: 50%;
		object-fit: cover;
	}

	.comment-input {
		flex: 1;
		padding: 0.5rem 0.75rem;
		border: 1px solid var(--ap-border-default);
		border-radius: 999px;
		font-size: 0.85rem;
	}

	.comment-input:focus {
		outline: none;
		border-color: #f97316;
	}

	.comment-submit {
		width: 32px;
		height: 32px;
		border-radius: 50%;
		background: #f97316;
		color: #fff;
		border: none;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	/* Load More */
	.load-more {
		text-align: center;
		padding: 1rem;
	}

	.load-more-btn {
		display: inline-flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.75rem 1.5rem;
		background: #fff;
		border: 1px solid var(--ap-border-default);
		border-radius: 999px;
		font-size: 0.85rem;
		cursor: pointer;
		transition: all 0.2s;
	}

	.load-more-btn:hover {
		border-color: #f97316;
	}

	/* Sidebar */
	.feed-sidebar {
		display: none;
	}

	@media (min-width: 992px) {
		.feed-sidebar {
			display: block;
		}
	}

	.sidebar-card {
		background: #fff;
		border: 1px solid var(--ap-border-default);
		border-radius: 1rem;
		padding: 1rem;
		margin-bottom: 1rem;
	}

	.sidebar-title {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		font-size: 0.9rem;
		font-weight: 700;
		margin: 0 0 1rem;
	}

	.sidebar-title i {
		color: #f97316;
	}

	/* Upcoming Events List */
	.upcoming-list {
		list-style: none;
		padding: 0;
		margin: 0;
	}

	.upcoming-item {
		display: flex;
		gap: 0.75rem;
		padding: 0.5rem 0;
		border-bottom: 1px solid var(--ap-border-default);
	}

	.upcoming-item:last-child {
		border: none;
	}

	.upcoming-date {
		display: flex;
		flex-direction: column;
		align-items: center;
		background: var(--ap-bg-surface);
		padding: 0.35rem 0.65rem;
		border-radius: 0.35rem;
	}

	.upcoming-date .day {
		font-size: 1.1rem;
		font-weight: 700;
		line-height: 1;
	}

	.upcoming-date .month {
		font-size: 0.65rem;
		color: #f97316;
		text-transform: uppercase;
		font-weight: 600;
	}

	.upcoming-info {
		display: flex;
		flex-direction: column;
		justify-content: center;
	}

	.upcoming-title {
		font-weight: 600;
		font-size: 0.85rem;
	}

	.upcoming-local {
		font-size: 0.75rem;
		color: var(--ap-text-muted);
	}

	.sidebar-link {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 0.35rem;
		padding: 0.65rem;
		margin-top: 0.75rem;
		background: var(--ap-bg-surface);
		border-radius: 0.5rem;
		font-size: 0.8rem;
		color: var(--ap-text-default);
		text-decoration: none;
		transition: all 0.2s;
	}

	.sidebar-link:hover {
		background: var(--ap-bg-page);
		color: #f97316;
	}

	/* Suggestions List */
	.suggestions-list {
		list-style: none;
		padding: 0;
		margin: 0;
	}

	.suggestion-item {
		display: flex;
		gap: 0.75rem;
		align-items: center;
		padding: 0.65rem 0;
		border-bottom: 1px solid var(--ap-border-default);
	}

	.suggestion-item:last-child {
		border: none;
	}

	.suggestion-avatar {
		width: 44px;
		height: 44px;
		border-radius: 50%;
		object-fit: cover;
	}

	.suggestion-info {
		flex: 1;
		min-width: 0;
	}

	.suggestion-name {
		display: block;
		font-weight: 600;
		font-size: 0.85rem;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	.suggestion-role {
		display: block;
		font-size: 0.75rem;
		color: var(--ap-text-muted);
	}

	.suggestion-mutual {
		display: block;
		font-size: 0.7rem;
		color: var(--ap-text-muted);
	}

	.follow-btn {
		width: 32px;
		height: 32px;
		border-radius: 50%;
		background: #f97316;
		color: #fff;
		border: none;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		transition: all 0.2s;
	}

	.follow-btn:hover {
		transform: scale(1.1);
	}

	/* Trending List */
	.trending-list {
		list-style: none;
		padding: 0;
		margin: 0;
	}

	.trending-item {
		display: flex;
		justify-content: space-between;
		padding: 0.5rem 0;
		border-bottom: 1px solid var(--ap-border-default);
	}

	.trending-item:last-child {
		border: none;
	}

	.trend-tag {
		font-weight: 600;
		font-size: 0.85rem;
		color: #f97316;
	}

	.trend-count {
		font-size: 0.75rem;
		color: var(--ap-text-muted);
	}

	/* Sidebar Footer */
	.sidebar-footer {
		display: flex;
		flex-wrap: wrap;
		gap: 0.75rem;
		padding: 0.5rem 0;
		font-size: 0.7rem;
	}

	.sidebar-footer a {
		color: var(--ap-text-muted);
		text-decoration: none;
	}

	.sidebar-footer a:hover {
		color: #f97316;
		text-decoration: underline;
	}

	/* Post Modal */
	.post-modal {
		position: fixed;
		inset: 0;
		z-index: 300;
		display: none;
		align-items: center;
		justify-content: center;
		padding: 1rem;
	}

	.post-modal[aria-hidden="false"] {
		display: flex;
	}

	.post-modal .modal-overlay {
		position: absolute;
		inset: 0;
		background: rgba(0, 0, 0, 0.5);
	}

	.post-modal .modal-content {
		position: relative;
		background: #fff;
		border-radius: 1rem;
		max-width: 550px;
		width: 100%;
		z-index: 1;
		overflow: hidden;
	}

	.modal-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 1rem;
		border-bottom: 1px solid var(--ap-border-default);
	}

	.modal-header h2 {
		font-size: 1.1rem;
		margin: 0;
	}

	.modal-close {
		width: 36px;
		height: 36px;
		border-radius: 50%;
		background: var(--ap-bg-surface);
		border: none;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.post-composer {
		display: flex;
		gap: 0.75rem;
		padding: 1rem;
	}

	.post-composer textarea {
		flex: 1;
		border: none;
		outline: none;
		font-size: 1rem;
		resize: none;
		min-height: 120px;
		font-family: inherit;
	}

	.post-attachments {
		padding: 0 1rem 1rem;
	}

	.modal-footer {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 1rem;
		border-top: 1px solid var(--ap-border-default);
	}

	.attachment-btns {
		display: flex;
		gap: 0.35rem;
	}

	.attach-btn {
		width: 36px;
		height: 36px;
		border-radius: 50%;
		background: transparent;
		border: none;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		color: var(--ap-text-muted);
		transition: all 0.2s;
	}

	.attach-btn:hover {
		background: var(--ap-bg-surface);
		color: #f97316;
	}

	.publish-btn {
		padding: 0.65rem 1.25rem;
		background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
		color: #fff;
		border: none;
		border-radius: 999px;
		font-weight: 600;
		cursor: pointer;
		transition: all 0.2s;
	}

	.publish-btn:hover:not(:disabled) {
		transform: translateY(-2px);
		box-shadow: 0 8px 20px rgba(249, 115, 22, 0.35);
	}

	.publish-btn:disabled {
		opacity: 0.5;
		cursor: not-allowed;
	}

	/* Dark Mode */
	body.dark-mode .card,
	body.dark-mode .stories-row,
	body.dark-mode .filter-btn,
	body.dark-mode .load-more-btn,
	body.dark-mode .sidebar-card,
	body.dark-mode .post-modal .modal-content {
		background: var(--ap-bg-card);
		border-color: var(--ap-border-default);
	}

	body.dark-mode .event-date {
		background: var(--ap-bg-card);
	}

	body.dark-mode .post-input-btn,
	body.dark-mode .comment-input,
	body.dark-mode .post-composer textarea {
		background: var(--ap-bg-surface);
		color: var(--ap-text-default);
	}
</style>

<script>
	(function() {
		const feed = document.querySelector('.apollo-feed');
		if (!feed) return;

		// Post modal
		const postModal = document.getElementById('post-modal');
		const postTextarea = document.getElementById('post-textarea');
		const publishBtn = document.querySelector('.publish-btn');

		document.getElementById('open-post-modal')?.addEventListener('click', () => {
			postModal?.setAttribute('aria-hidden', 'false');
			postTextarea?.focus();
		});

		document.getElementById('close-post-modal')?.addEventListener('click', () => {
			postModal?.setAttribute('aria-hidden', 'true');
		});

		postModal?.querySelector('.modal-overlay')?.addEventListener('click', () => {
			postModal.setAttribute('aria-hidden', 'true');
		});

		postTextarea?.addEventListener('input', () => {
			publishBtn.disabled = postTextarea.value.trim().length === 0;
		});

		// Feed filters
		document.querySelectorAll('.filter-btn').forEach(btn => {
			btn.addEventListener('click', () => {
				document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
				btn.classList.add('active');

				const filter = btn.dataset.filter;
				document.querySelectorAll('.feed-item').forEach(item => {
					if (filter === 'all') {
						item.hidden = false;
					} else {
						const type = item.dataset.type;
						const typeMap = {
							events: ['new_event'],
							photos: ['photo'],
							communities: ['milestone']
						};
						item.hidden = !typeMap[filter]?.includes(type);
					}
				});
			});
		});

		// Like buttons
		document.querySelectorAll('.like-btn').forEach(btn => {
			btn.addEventListener('click', function() {
				this.classList.toggle('liked');
				const icon = this.querySelector('i');
				if (this.classList.contains('liked')) {
					icon.className = 'i-heart-fill-v';
				} else {
					icon.className = 'i-heart-v';
				}
			});
		});

		// Comment buttons
		document.querySelectorAll('.comment-btn').forEach(btn => {
			btn.addEventListener('click', function() {
				const item = this.closest('.feed-item');
				const comments = item.querySelector('.comments-section');
				if (comments) {
					comments.hidden = !comments.hidden;
					if (!comments.hidden) {
						comments.querySelector('.comment-input')?.focus();
					}
				}
			});
		});

		// Load more (simulated)
		document.getElementById('load-more')?.addEventListener('click', function() {
			this.innerHTML = '<i class="i-loader-v" style="animation: spin 1s linear infinite;"></i> Carregando...';
			this.disabled = true;

			setTimeout(() => {
				this.innerHTML = '<i class="i-loader-v"></i> Carregar mais';
				this.disabled = false;
				alert('Mais itens seriam carregados via AJAX');
			}, 1500);
		});

		// Post form submission
		document.getElementById('post-form')?.addEventListener('submit', function(e) {
			e.preventDefault();

			const content = postTextarea.value.trim();
			if (!content) return;

			publishBtn.disabled = true;
			publishBtn.textContent = 'Publicando...';

			// Would submit via AJAX
			setTimeout(() => {
				alert('Post publicado: ' + content);
				postTextarea.value = '';
				postModal.setAttribute('aria-hidden', 'true');
				publishBtn.disabled = true;
				publishBtn.textContent = 'Publicar';
			}, 1000);
		});
	})();
</script>
