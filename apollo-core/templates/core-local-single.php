<?php
/**
 * Core Template: Local/Venue Single Page
 *
 * @package Apollo_Core
 * @since   1.0.0
 *
 * SOURCE HTML: eventos - local - single.html
 * WRAPPER: apollo-events-manager/templates/single-event_local.php
 *
 * EXPECTED CONTEXT KEYS:
 * - local            : WP_Post object
 * - local_id         : int
 * - title            : string
 * - description      : string (HTML)
 * - thumbnail_url    : string
 * - gallery_images   : array (venue gallery, max 5)
 * - address          : string
 * - venue_type       : string (e.g., "Nightclub")
 * - lat              : float
 * - lng              : float
 * - website_url      : string
 * - instagram_url    : string
 * - facebook_url     : string
 * - upcoming_events  : array [['id', 'title', 'permalink', 'thumbnail', 'date_day', 'date_month', 'start_time', 'end_time', 'tags']]
 * - testimonials     : array [['user_id', 'name', 'avatar', 'rating', 'text']]
 * - is_print         : bool
 * - template_loader  : Apollo_Template_Loader instance
 */

defined( 'ABSPATH' ) || exit;

// Ensure required context exists.
if ( empty( $local_id ) ) {
	return;
}

// Defaults.
$title           = $title ?? '';
$description     = $description ?? '';
$thumbnail_url   = $thumbnail_url ?? '';
$gallery_images  = $gallery_images ?? array();
$address         = $address ?? '';
$venue_type      = $venue_type ?? 'Venue';
$lat             = $lat ?? null;
$lng             = $lng ?? null;
$website_url     = $website_url ?? '';
$instagram_url   = $instagram_url ?? '';
$facebook_url    = $facebook_url ?? '';
$upcoming_events = $upcoming_events ?? array();
$testimonials    = $testimonials ?? array();
$is_print        = $is_print ?? false;

// Flags.
$has_gallery      = ! empty( $gallery_images );
$has_socials      = $website_url || $instagram_url || $facebook_url;
$has_map          = $lat && $lng;
$has_events       = ! empty( $upcoming_events );
$has_testimonials = ! empty( $testimonials );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $title ); ?> - Apollo</title>

	<?php if ( isset( $template_loader ) ) : ?>
		<?php $template_loader->load_partial( 'assets' ); ?>
	<?php else : ?>
		<?php Apollo_Template_Loader::load_partial( 'assets' ); ?>
	<?php endif; ?>

	<style>
		/* =================================================================
			APOLLO LOCAL/VENUE SINGLE - CSS FROM APPROVED HTML
			Source: eventos - local - single.html
			================================================================= */

		:root {
			--font-primary: var(--ap-font-sans, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif);
			--bg-main: var(--ap-bg, #fff);
			--bg-surface: var(--ap-bg-muted, #f8f9fa);
			--text-primary: var(--ap-text-dark, rgba(19, 21, 23, .9));
			--text-secondary: var(--ap-text-muted, rgba(19, 21, 23, .65));
			--accent-color: var(--ap-accent, #000);
			--border-color: var(--ap-border, #e0e2e4);
			--border-color-2: var(--ap-border-light, #e0e2e454);
			--radius-main: var(--ap-radius-lg, 12px);
			--radius-card: var(--ap-radius-xl, 16px);
			--radius-lg: var(--ap-radius-2xl, 24px);
			--transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
			--card-shadow: 0 10px 30px rgba(0,0,0,0.08);
			--card-shadow-hover: 0 15px 40px rgba(0,0,0,0.12);
		}

		* {
			box-sizing: border-box;
			margin: 0;
			padding: 0;
			-webkit-tap-highlight-color: transparent;
		}

		body {
			font-family: var(--font-primary);
			font-size: 15px;
			color: var(--text-secondary);
			background-color: #f0f2f5;
			background-image: radial-gradient(#e5e7eb 1px, transparent 1px);
			background-size: 20px 20px;
			display: flex;
			justify-content: center;
			min-height: 100vh;
		}

		/* Mobile Container */
		.mobile-container {
			width: 100%;
			max-width: 500px;
			background: var(--bg-main);
			min-height: 100vh;
			position: relative;
			box-shadow: 0 0 50px rgba(0,0,0,0.05);
			padding-bottom: 80px;
		}

		@media (min-width: 501px) {
			body { padding: 2rem 0; }
			.mobile-container { border-radius: 2rem; overflow: hidden; }
		}

		/* Hero Slider */
		.hero-slider-wrapper {
			position: relative;
			width: 100%;
			height: 55vh;
			overflow: hidden;
			background: #000;
		}

		.hero-track {
			display: flex;
			width: 100%;
			height: 100%;
			transition: transform 0.5s ease;
		}

		.hero-slide {
			min-width: 100%;
			height: 100%;
			position: relative;
		}

		.hero-slide img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			filter: brightness(0.85);
		}

		.slider-indicators {
			position: absolute;
			bottom: 30px;
			left: 50%;
			transform: translateX(-50%);
			display: flex;
			gap: 8px;
			z-index: 10;
		}

		.indicator {
			width: 6px;
			height: 6px;
			background: rgba(255,255,255,0.4);
			border-radius: 50%;
			cursor: pointer;
			transition: var(--transition);
		}

		.indicator.active {
			width: 20px;
			background: #fff;
			border-radius: 10px;
		}

		/* Back Button */
		.back-btn-overlay {
			position: absolute;
			top: 20px;
			left: 20px;
			width: 40px;
			height: 40px;
			background: rgba(255,255,255,0.2);
			backdrop-filter: blur(10px);
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			z-index: 20;
			color: #fff;
			text-decoration: none;
			border: 1px solid rgba(255,255,255,0.1);
		}

		/* Venue Body */
		.venue-body {
			position: relative;
			background: var(--bg-main);
			margin-top: -24px;
			border-radius: 24px 24px 0 0;
			padding: 2rem 1.5rem;
			z-index: 5;
		}

		.venue-header {
			margin-bottom: 2rem;
		}

		.venue-label {
			display: inline-block;
			font-size: 0.7rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 1px;
			color: var(--accent-color);
			background: rgba(0,0,0,0.05);
			padding: 4px 10px;
			border-radius: 6px;
			margin-bottom: 0.8rem;
		}

		.venue-title {
			font-size: 2.2rem;
			font-weight: 800;
			color: var(--text-primary);
			line-height: 1.1;
			margin-bottom: 0.5rem;
		}

		.venue-address {
			display: flex;
			align-items: center;
			gap: 8px;
			color: var(--text-secondary);
			font-size: 0.95rem;
			margin-bottom: 1.5rem;
		}

		/* Social Row */
		.social-row {
			display: flex;
			gap: 12px;
			margin-bottom: 2rem;
			border-bottom: 1px solid var(--border-color-2);
			padding-bottom: 2rem;
		}

		.social-btn {
			flex: 1;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
			height: 44px;
			border: 1px solid var(--border-color);
			border-radius: 10px;
			text-decoration: none;
			color: var(--text-primary);
			font-size: 0.9rem;
			font-weight: 600;
			transition: var(--transition);
		}

		.social-btn:hover {
			background: var(--bg-surface);
			border-color: var(--text-primary);
			transform: translateY(-2px);
		}

		.social-btn.primary {
			background: var(--text-primary);
			color: #fff;
			border-color: var(--text-primary);
		}

		/* Section Title */
		.section-title {
			font-size: 1.25rem;
			font-weight: 700;
			color: var(--text-primary);
			margin-bottom: 1rem;
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.section-title i { opacity: 0.6; }

		.venue-bio {
			font-size: 1rem;
			line-height: 1.6;
			color: var(--text-secondary);
			margin-bottom: 2.5rem;
		}

		/* Map Section */
		.map-section {
			margin-bottom: 3rem;
		}

		.static-map-wrapper {
			position: relative;
			height: 200px;
			border-radius: var(--radius-card);
			overflow: hidden;
			background-size: cover;
			background-position: center;
			background-color: var(--bg-surface);
		}

		.map-pin-overlay {
			position: absolute;
			top: 50%;
			left: 50%;
			transform: translate(-50%, -50%);
			font-size: 3rem;
			color: var(--text-primary);
			text-shadow: 0 4px 12px rgba(0,0,0,0.3);
		}

		/* Route Controls */
		.route-controls {
			display: flex;
			margin-top: -24px;
			position: relative;
			z-index: 2;
			padding: 0 10px;
		}

		.route-input {
			flex: 1;
			height: 50px;
			background: #fff;
			border: 1px solid var(--border-color);
			border-radius: 12px 0 0 12px;
			display: flex;
			align-items: center;
			padding: 0 15px;
			gap: 10px;
			box-shadow: 0 4px 15px rgba(0,0,0,0.05);
		}

		.route-input input {
			border: none;
			outline: none;
			width: 100%;
			font-size: 0.9rem;
			color: var(--text-primary);
		}

		.route-btn {
			width: 60px;
			background: var(--bg-surface);
			border: 1px solid var(--border-color);
			border-left: none;
			border-radius: 0 12px 12px 0;
			cursor: pointer;
			font-size: 1.2rem;
			color: var(--text-primary);
			transition: var(--transition);
			box-shadow: 0 4px 15px rgba(0,0,0,0.05);
		}

		.route-btn:hover { background: #e0e0e0; }

		/* Events Section */
		.events-section {
			padding-top: 2rem;
			border-top: 1px dashed var(--border-color);
		}

		.event-card {
			display: block;
			text-decoration: none;
			margin-bottom: 2rem;
			position: relative;
			animation: fadeIn 0.5s ease;
		}

		@keyframes fadeIn {
			from { opacity: 0; transform: translateY(10px); }
			to { opacity: 1; transform: translateY(0); }
		}

		.event-card-media {
			height: 220px;
			position: relative;
			border-radius: var(--radius-card);
			overflow: hidden;
			mask: radial-gradient(circle at 34px 34px, transparent 24px, black 25px);
			-webkit-mask: radial-gradient(circle at 34px 34px, transparent 24px, black 25px);
		}

		.event-card-media img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			transition: transform 0.5s ease;
		}

		.event-card:hover .event-card-media img {
			transform: scale(1.05);
		}

		.event-date-box {
			position: absolute;
			top: 0;
			left: 0;
			width: 68px;
			height: 68px;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			z-index: 5;
			padding-top: 4px;
		}

		.date-day {
			font-size: 1.6rem;
			font-weight: 800;
			color: var(--text-primary);
			line-height: 1;
		}

		.date-month {
			font-size: 0.8rem;
			font-weight: 600;
			text-transform: uppercase;
			color: var(--text-secondary);
		}

		.event-tags {
			position: absolute;
			bottom: 12px;
			right: 12px;
			display: flex;
			gap: 6px;
		}

		.event-tag {
			padding: 4px 10px;
			background: rgba(255,255,255,0.2);
			backdrop-filter: blur(8px);
			border: 1px solid rgba(255,255,255,0.3);
			border-radius: 6px;
			color: #fff;
			font-size: 0.65rem;
			font-weight: 600;
			text-transform: uppercase;
		}

		.event-info {
			padding: 1rem 0.5rem;
		}

		.event-title {
			font-size: 1.2rem;
			font-weight: 700;
			color: var(--text-primary);
			margin-bottom: 0.4rem;
			line-height: 1.3;
		}

		.event-meta {
			display: flex;
			align-items: center;
			gap: 6px;
			color: var(--text-secondary);
			font-size: 0.85rem;
		}

		/* Testimonials */
		.testimonials-section {
			margin-top: 2rem;
			margin-bottom: 4rem;
		}

		.testimonials-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 1.5rem;
		}

		.add-review-btn {
			font-size: 0.8rem;
			font-weight: 600;
			color: var(--text-primary);
			text-decoration: underline;
			cursor: pointer;
		}

		.testimonials-scroller {
			display: flex;
			gap: 1rem;
			overflow-x: auto;
			padding-bottom: 1.5rem;
			margin: 0 -1.5rem;
			padding-left: 1.5rem;
			padding-right: 1.5rem;
			-ms-overflow-style: none;
			scrollbar-width: none;
		}

		.testimonials-scroller::-webkit-scrollbar { display: none; }

		.review-card {
			min-width: 260px;
			background: #fff;
			border: 1px solid var(--border-color);
			border-radius: 16px;
			padding: 1.25rem;
			position: relative;
		}

		.review-card::after {
			content: '';
			position: absolute;
			bottom: -8px;
			left: 20px;
			width: 15px;
			height: 15px;
			background: #fff;
			border-bottom: 1px solid var(--border-color);
			border-right: 1px solid var(--border-color);
			transform: rotate(45deg);
		}

		.reviewer-info {
			display: flex;
			align-items: center;
			gap: 10px;
			margin-bottom: 10px;
		}

		.reviewer-avatar {
			width: 32px;
			height: 32px;
			border-radius: 50%;
			background: #eee;
			object-fit: cover;
		}

		.reviewer-name {
			font-size: 0.9rem;
			font-weight: 700;
			color: var(--text-primary);
		}

		.review-stars {
			color: #FFB400;
			font-size: 0.8rem;
			margin-left: auto;
		}

		.review-text {
			font-size: 0.85rem;
			line-height: 1.5;
			color: var(--text-secondary);
			font-style: italic;
		}

		/* Print Mode */
		<?php if ( $is_print ) : ?>
		@media print {
			.back-btn-overlay,
			.route-controls,
			.add-review-btn {
				display: none !important;
			}

			.mobile-container {
				max-width: 100%;
				box-shadow: none;
			}

			.hero-slider-wrapper {
				height: 30vh;
			}

			.section {
				page-break-inside: avoid;
			}
		}
		<?php endif; ?>
	</style>
</head>

<body>

	<div class="mobile-container">

		<!-- BACK NAVIGATION -->
		<a href="javascript:history.back()" class="back-btn-overlay">
			<i class="ri-arrow-left-line"></i>
		</a>

		<!-- ================================================================
			BLOCK: HERO SLIDER
			================================================================ -->
		<div class="hero-slider-wrapper">
			<div class="hero-track" id="heroTrack">
				<?php if ( $has_gallery ) : ?>
					<?php foreach ( $gallery_images as $img ) : ?>
						<?php $img_url = is_array( $img ) ? ( $img['url'] ?? '' ) : $img; ?>
						<div class="hero-slide">
							<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $title ); ?>">
						</div>
					<?php endforeach; ?>
				<?php elseif ( $thumbnail_url ) : ?>
					<div class="hero-slide">
						<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $title ); ?>">
					</div>
				<?php endif; ?>
			</div>

			<div class="slider-indicators" id="heroIndicators"></div>
		</div>

		<!-- VENUE CONTENT BODY -->
		<div class="venue-body">

			<!-- BLOCK: HEADER INFO -->
			<div class="venue-header">
				<?php if ( $venue_type ) : ?>
					<span class="venue-label">
						<i class="ri-flashlight-fill"></i> <?php echo esc_html( $venue_type ); ?>
					</span>
				<?php endif; ?>

				<h1 class="venue-title"><?php echo esc_html( $title ); ?></h1>

				<?php if ( $address ) : ?>
					<div class="venue-address">
						<i class="ri-map-pin-2-fill"></i>
						<span><?php echo esc_html( $address ); ?></span>
					</div>
				<?php endif; ?>

				<!-- SOCIAL ACTIONS -->
				<?php if ( $has_socials ) : ?>
					<div class="social-row">
						<?php if ( $website_url ) : ?>
							<a href="<?php echo esc_url( $website_url ); ?>" class="social-btn primary" target="_blank">
								<i class="ri-global-line"></i> Website
							</a>
						<?php endif; ?>

						<?php if ( $instagram_url ) : ?>
							<a href="<?php echo esc_url( $instagram_url ); ?>" class="social-btn" target="_blank">
								<i class="ri-instagram-line"></i> Instagram
							</a>
						<?php endif; ?>

						<?php if ( $facebook_url ) : ?>
							<a href="<?php echo esc_url( $facebook_url ); ?>" class="social-btn" target="_blank">
								<i class="ri-facebook-circle-fill"></i>
							</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<!-- BLOCK: BIO -->
				<?php if ( $description ) : ?>
					<h3 class="section-title"><i class="ri-information-fill"></i> Sobre</h3>
					<div class="venue-bio">
						<?php echo wp_kses_post( $description ); ?>
					</div>
				<?php endif; ?>
			</div>

			<!-- BLOCK: MAP & LOCATION -->
			<?php if ( $has_map || $address ) : ?>
				<div class="map-section">
					<h3 class="section-title"><i class="ri-map-2-fill"></i> Localização</h3>

					<div class="static-map-wrapper"
						<?php if ( $has_map ) : ?>
						style="background-image:url('https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/<?php echo esc_attr( $lng ); ?>,<?php echo esc_attr( $lat ); ?>,14,0/500x200?access_token=YOUR_TOKEN');"
						data-lat="<?php echo esc_attr( $lat ); ?>"
						data-lng="<?php echo esc_attr( $lng ); ?>"
						<?php endif; ?>
					>
						<div class="map-pin-overlay"><i class="ri-map-pin-fill"></i></div>
					</div>

					<!-- Route Controls -->
					<div class="route-controls">
						<div class="route-input">
							<i class="ri-navigation-line" style="opacity:0.5"></i>
							<input type="text" id="routeOrigin" placeholder="<?php esc_attr_e( 'Seu endereço...', 'apollo' ); ?>">
						</div>
						<button class="route-btn" id="routeBtn" data-destination="<?php echo esc_attr( $address ); ?>">
							<i class="ri-arrow-right-up-line"></i>
						</button>
					</div>
				</div>
			<?php endif; ?>

			<!-- ================================================================
				BLOCK: EVENTS LIST
				================================================================ -->
			<?php if ( $has_events ) : ?>
				<div class="events-section">
					<h3 class="section-title" style="margin-bottom: 1.5rem;">
						<i class="ri-calendar-event-fill"></i> <?php esc_html_e( 'Próximos Eventos', 'apollo' ); ?>
					</h3>

					<?php foreach ( $upcoming_events as $event ) : ?>
						<a href="<?php echo esc_url( $event['permalink'] ?? '#' ); ?>" class="event-card">
							<div class="event-card-media">
								<div class="event-date-box">
									<span class="date-day"><?php echo esc_html( $event['date_day'] ?? '' ); ?></span>
									<span class="date-month"><?php echo esc_html( $event['date_month'] ?? '' ); ?></span>
								</div>
								<img src="<?php echo esc_url( $event['thumbnail'] ?? '' ); ?>" alt="<?php echo esc_attr( $event['title'] ?? '' ); ?>">

								<?php if ( ! empty( $event['tags'] ) ) : ?>
									<div class="event-tags">
										<?php foreach ( $event['tags'] as $tag ) : ?>
											<span class="event-tag"><?php echo esc_html( is_array( $tag ) ? $tag['name'] : $tag ); ?></span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
							<div class="event-info">
								<h4 class="event-title"><?php echo esc_html( $event['title'] ?? '' ); ?></h4>
								<div class="event-meta">
									<?php if ( ! empty( $event['start_time'] ) ) : ?>
										<i class="ri-time-line"></i>
										<?php echo esc_html( $event['start_time'] ); ?>
										<?php if ( ! empty( $event['end_time'] ) ) : ?>
											- <?php echo esc_html( $event['end_time'] ); ?>
										<?php endif; ?>
									<?php endif; ?>
								</div>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<!-- ================================================================
				BLOCK: TESTIMONIALS
				================================================================ -->
			<?php if ( $has_testimonials ) : ?>
				<div class="testimonials-section">
					<div class="testimonials-header">
						<h3 class="section-title" style="margin-bottom:0">
							<i class="ri-chat-quote-fill"></i> <?php esc_html_e( 'Depoimentos', 'apollo' ); ?>
						</h3>
						<span class="add-review-btn">
							<i class="ri-edit-line"></i> <?php esc_html_e( 'Dar depoimento', 'apollo' ); ?>
						</span>
					</div>

					<div class="testimonials-scroller">
						<?php foreach ( $testimonials as $review ) : ?>
							<div class="review-card">
								<div class="reviewer-info">
									<?php if ( ! empty( $review['avatar'] ) ) : ?>
										<img src="<?php echo esc_url( $review['avatar'] ); ?>" class="reviewer-avatar" alt="">
									<?php endif; ?>
									<span class="reviewer-name"><?php echo esc_html( $review['name'] ?? '' ); ?></span>
									<?php if ( ! empty( $review['rating'] ) ) : ?>
										<div class="review-stars">
											<?php echo esc_html( str_repeat( '★', (int) $review['rating'] ) . str_repeat( '☆', 5 - (int) $review['rating'] ) ); ?>
										</div>
									<?php endif; ?>
								</div>
								<p class="review-text"><?php echo esc_html( $review['text'] ?? '' ); ?></p>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

		</div><!-- .venue-body -->

	</div><!-- .mobile-container -->

	<script>
	'use strict';

	// Hero Slider.
	(function() {
		const track = document.getElementById('heroTrack');
		const slides = track ? track.querySelectorAll('.hero-slide') : [];
		const indicatorsContainer = document.getElementById('heroIndicators');

		if (slides.length < 2) return;

		let currentIndex = 0;

		// Create Indicators.
		slides.forEach((_, index) => {
			const dot = document.createElement('div');
			dot.classList.add('indicator');
			if (index === 0) dot.classList.add('active');
			dot.addEventListener('click', () => goToSlide(index));
			indicatorsContainer.appendChild(dot);
		});

		function goToSlide(index) {
			currentIndex = index;
			track.style.transform = `translateX(-${index * 100}%)`;
			updateIndicators();
		}

		function updateIndicators() {
			document.querySelectorAll('.indicator').forEach((dot, index) => {
				dot.classList.toggle('active', index === currentIndex);
			});
		}

		// Auto Play.
		setInterval(() => {
			currentIndex = (currentIndex + 1) % slides.length;
			goToSlide(currentIndex);
		}, 5000);
	})();

	// Route Button.
	(function() {
		const routeBtn = document.getElementById('routeBtn');
		if (!routeBtn) return;

		routeBtn.addEventListener('click', () => {
			const origin = document.getElementById('routeOrigin').value;
			const destination = routeBtn.dataset.destination;

			if (origin && destination) {
				window.open(
					`https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(destination)}`,
					'_blank'
				);
			} else {
				document.getElementById('routeOrigin').focus();
			}
		});
	})();
	</script>

	<?php wp_footer(); ?>
</body>
</html>
