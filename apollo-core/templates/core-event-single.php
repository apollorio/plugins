<?php
/**
 * Core Template: Event Single Page
 *
 * @package Apollo_Core
 * @since   1.0.0
 *
 * SOURCE HTML: body_evento_eventoID ----single page.html
 * WRAPPER: apollo-events-manager/templates/single-event_listing.php
 *
 * EXPECTED CONTEXT KEYS:
 * - event           : WP_Post object
 * - event_id        : int
 * - title           : string
 * - description     : string (HTML)
 * - thumbnail_url   : string
 * - banner_url      : string
 * - video_url       : string (YouTube embed URL)
 * - start_date      : string (Y-m-d H:i:s)
 * - formatted_date  : string ("25 Out '25")
 * - start_time      : string ("22:00")
 * - end_time        : string ("06:00")
 * - venue           : array ['id', 'name', 'address', 'lat', 'lng', 'images']
 * - ticket_url      : string
 * - ticket_coupon   : string (optional)
 * - dj_slots        : array [['dj_id', 'name', 'thumbnail', 'permalink', 'start', 'end']]
 * - tags            : array [['name', 'icon', 'class']]
 * - sounds          : array (music genre tags)
 * - gallery_images  : array (promo images, max 5)
 * - interested_users: array (user objects with avatars)
 * - total_interested: int
 * - final_image_url : string
 * - is_print        : bool
 * - template_loader : Apollo_Template_Loader instance
 */

defined( 'ABSPATH' ) || exit;

// Ensure required context exists.
if ( empty( $event_id ) ) {
	return;
}

// Defaults.
$title            = $title ?? '';
$description      = $description ?? '';
$thumbnail_url    = $thumbnail_url ?? '';
$banner_url       = $banner_url ?? '';
$video_url        = $video_url ?? '';
$start_date       = $start_date ?? '';
$formatted_date   = $formatted_date ?? '';
$start_time       = $start_time ?? '00:00';
$end_time         = $end_time ?? '';
$venue            = $venue ?? array();
$ticket_url       = $ticket_url ?? '';
$ticket_coupon    = $ticket_coupon ?? 'APOLLO';
$dj_slots         = $dj_slots ?? array();
$tags             = $tags ?? array();
$sounds           = $sounds ?? array();
$gallery_images   = $gallery_images ?? array();
$interested_users = $interested_users ?? array();
$total_interested = $total_interested ?? 0;
$final_image_url  = $final_image_url ?? '';
$is_print         = $is_print ?? false;

// Venue data.
$venue_name    = $venue['name'] ?? '';
$venue_address = $venue['address'] ?? '';
$venue_lat     = $venue['lat'] ?? '';
$venue_lng     = $venue['lng'] ?? '';
$venue_images  = $venue['images'] ?? array();

// Has content flags.
$has_video   = ! empty( $video_url );
$has_lineup  = ! empty( $dj_slots );
$has_venue   = ! empty( $venue_name );
$has_tickets = ! empty( $ticket_url );
$has_gallery = ! empty( $gallery_images );

// Max visible avatars.
$max_visible_avatars = 10;
$visible_users       = array_slice( $interested_users, 0, $max_visible_avatars );
$hidden_count        = max( 0, $total_interested - $max_visible_avatars );
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
			APOLLO EVENT SINGLE - CSS FROM APPROVED HTML
			Source: body_evento_eventoID ----single page.html
			================================================================= */

		:root {
			--font-primary: 'Instrument Sans', -apple-system, BlinkMacSystemFont, sans-serif;
			--bg-main: #ffffff;
			--bg-surface: #f8f9fa;
			--text-primary: #131313;
			--text-secondary: #6c757d;
			--border-color: rgba(0, 0, 0, 0.08);
			--border-color-2: rgba(0, 0, 0, 0.2);
			--radius-main: 14px;
			--radius-card: 16px;
			--transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
			--card-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
			--card-shadow-light: rgba(0, 0, 0, 0.06);
		}

		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}

		body {
			font-family: var(--font-primary);
			background: var(--bg-main);
			color: var(--text-primary);
			line-height: 1.6;
		}

		/* Mobile Container */
		.mobile-container {
			max-width: 500px;
			margin: 0 auto;
			background: var(--bg-main);
			min-height: 100vh;
			padding-bottom: 100px;
		}

		@media (min-width: 768px) {
			.mobile-container {
				box-shadow: 0 0 60px rgba(0, 0, 0, 0.1);
				border-radius: 24px;
				margin: 2rem auto;
				min-height: calc(100vh - 4rem);
				overflow: hidden;
			}
		}

		/* Hero Section */
		.hero-media {
			position: relative;
			width: 100%;
			height: 75vh;
			overflow: hidden;
			background: #000;
			border-radius: 20px;
		}

		.video-cover {
			position: absolute;
			top: 50%;
			left: 50%;
			width: 100vw;
			height: 56.25vw;
			min-height: 100vh;
			min-width: 177.77vh;
			transform: translate(-50%, -50%);
			overflow: hidden;
		}

		.video-cover iframe {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			border: 0;
			pointer-events: none;
		}

		.hero-image {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}

		.hero-overlay {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: linear-gradient(to bottom,
				rgba(0, 0, 0, 0.3) 0%,
				transparent 30%,
				transparent 70%,
				rgba(0, 0, 0, 0.8) 100%);
			z-index: 1;
		}

		.hero-content {
			position: absolute;
			bottom: 0;
			left: 0;
			width: 100%;
			padding: 2rem 1.5rem;
			z-index: 2;
			color: white;
		}

		.event-tag-pill {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			background: rgba(255, 255, 255, 0.15);
			backdrop-filter: blur(10px);
			padding: 6px 12px;
			border-radius: 20px;
			font-size: 0.75rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			margin-right: 0.5rem;
			margin-bottom: 0.75rem;
		}

		.event-tag-pill i {
			font-size: 0.85rem;
		}

		.hero-title {
			font-size: 3.2rem;
			font-weight: 800;
			line-height: 1.1;
			margin-bottom: 1rem;
			letter-spacing: -0.02em;
		}

		.hero-meta {
			display: flex;
			flex-direction: column;
			gap: 0.5rem;
		}

		.hero-meta-item {
			display: flex;
			align-items: center;
			gap: 0.5rem;
			font-size: 0.95rem;
			opacity: 0.95;
		}

		.hero-meta-item i {
			font-size: 1.1rem;
			opacity: 0.8;
		}

		/* Event Body */
		.event-body {
			padding: 0 1.5rem;
		}

		/* Quick Actions */
		.quick-actions {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: 0.5rem;
			padding: 0.5rem 0;
			margin: 1.5rem auto 0;
		}

		.quick-action {
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 0.5rem;
			text-decoration: none;
			color: var(--text-primary);
		}

		.quick-action-icon {
			width: 60px;
			height: 60px;
			border-radius: 50%;
			background: var(--bg-surface);
			border: 1px solid var(--border-color);
			display: flex;
			align-items: center;
			justify-content: center;
			transition: var(--transition);
		}

		.quick-action-icon i {
			font-size: 1.5rem;
		}

		.quick-action-label {
			font-size: 0.75rem;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}

		.quick-action:hover .quick-action-icon {
			background: var(--text-primary);
			color: white;
			border-color: var(--text-primary);
		}

		/* Fly Away Animation for Favorite */
		@keyframes flyAway {
			0% { transform: scale(1); opacity: 1; }
			50% { transform: scale(1.5) translateY(-20px); opacity: 0.5; }
			100% { transform: scale(0) translateY(-50px); opacity: 0; }
		}

		.fly-away {
			animation: flyAway 0.5s ease-out forwards;
		}

		/* RSVP Avatars */
		.rsvp-row {
			display: flex;
			justify-content: center;
			align-items: center;
			padding: 2rem;
			margin: 0.7rem auto;
		}

		.avatars-explosion {
			display: flex;
			align-items: center;
			position: relative;
		}

		.avatar {
			width: 40px;
			height: 40px;
			border-radius: 50%;
			background-size: cover;
			background-position: center;
			border: 3px solid var(--bg-main);
			margin-left: -12px;
			box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
		}

		.avatar:first-child {
			margin-left: 0;
		}

		.avatar-count {
			min-width: 40px;
			height: 40px;
			border-radius: 50%;
			background: var(--text-primary);
			color: white;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 0.75rem;
			font-weight: 700;
			margin-left: -12px;
			border: 3px solid var(--bg-main);
		}

		.interested-text {
			font-size: 0.85rem;
			font-weight: 600;
			color: var(--text-secondary);
			display: flex;
			align-items: center;
			gap: 4px;
			position: relative;
			right: -55px;
		}

		/* Section Styling */
		.section {
			margin: 2rem 0;
		}

		.section-title {
			font-size: 1.25rem;
			font-weight: 700;
			margin-bottom: 1rem;
			display: flex;
			align-items: center;
			gap: 0.5rem;
		}

		.section-title i {
			font-size: 1.4rem;
		}

		/* Info Card */
		.info-card {
			background: var(--bg-surface);
			border-radius: var(--radius-card);
			padding: 1.25rem;
			border: 1px solid var(--border-color);
		}

		.info-text {
			font-size: 0.95rem;
			line-height: 1.7;
			color: var(--text-secondary);
		}

		/* Music Tags Marquee */
		.music-tags-marquee {
			overflow: hidden;
			margin-top: 1rem;
			-webkit-mask-image: linear-gradient(90deg, transparent, black 10%, black 90%, transparent);
			mask-image: linear-gradient(90deg, transparent, black 10%, black 90%, transparent);
		}

		.music-tags-track {
			display: flex;
			gap: 1rem;
			animation: marquee 20s linear infinite;
			width: max-content;
		}

		@keyframes marquee {
			0% { transform: translateX(0); }
			100% { transform: translateX(-50%); }
		}

		.music-tag {
			background: var(--bg-surface);
			border: 1px solid var(--border-color);
			padding: 0.5rem 1rem;
			border-radius: 20px;
			font-size: 0.85rem;
			font-weight: 600;
			white-space: nowrap;
		}

		/* Promo Gallery Slider */
		.promo-gallery-slider {
			position: relative;
			border-radius: var(--radius-card);
			overflow: hidden;
			height: 300px;
			margin: 1.5rem 0;
		}

		.promo-track {
			display: flex;
			height: 100%;
			transition: transform 0.5s ease;
		}

		.promo-slide {
			min-width: 100%;
			height: 100%;
		}

		.promo-slide img {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}

		.promo-controls {
			position: absolute;
			bottom: 1rem;
			right: 1rem;
			display: flex;
			gap: 0.5rem;
		}

		.promo-prev,
		.promo-next {
			width: 40px;
			height: 40px;
			border-radius: 50%;
			background: rgba(255, 255, 255, 0.9);
			border: none;
			display: flex;
			align-items: center;
			justify-content: center;
			cursor: pointer;
			transition: var(--transition);
		}

		.promo-prev:hover,
		.promo-next:hover {
			background: white;
			transform: scale(1.1);
		}

		.promo-prev i,
		.promo-next i {
			font-size: 1.25rem;
		}

		/* Lineup Section */
		.lineup-list {
			display: flex;
			flex-direction: column;
			gap: 0.75rem;
		}

		.lineup-card {
			display: flex;
			align-items: center;
			gap: 1rem;
			background: var(--bg-surface);
			border: 1px solid var(--border-color);
			border-radius: var(--radius-card);
			padding: 0.75rem;
			min-height: 85px;
		}

		.lineup-avatar-img,
		.lineup-avatar-fallback {
			width: 60px;
			height: 60px;
			border-radius: 50%;
			object-fit: cover;
			flex-shrink: 0;
		}

		.lineup-avatar-fallback {
			background: var(--text-primary);
			color: white;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 1.5rem;
			font-weight: 700;
		}

		.lineup-info {
			flex: 1;
		}

		.lineup-name {
			font-size: 1rem;
			font-weight: 700;
			margin-bottom: 0.25rem;
		}

		.lineup-name a {
			text-decoration: none;
			color: inherit;
		}

		.lineup-name a:hover {
			text-decoration: underline;
		}

		.lineup-time {
			font-size: 0.85rem;
			color: var(--text-secondary);
			display: flex;
			align-items: center;
			gap: 0.25rem;
		}

		/* Venue Images Slider */
		.local-images-slider {
			position: relative;
			border-radius: var(--radius-card);
			overflow: hidden;
			height: 350px;
			margin-bottom: 1rem;
		}

		.local-images-track {
			display: flex;
			height: 100%;
			transition: transform 0.5s ease;
		}

		.local-image {
			min-width: 100%;
			height: 100%;
		}

		.local-image img {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}

		.slider-nav {
			position: absolute;
			bottom: 1rem;
			left: 50%;
			transform: translateX(-50%);
			display: flex;
			gap: 0.5rem;
		}

		.slider-dot {
			width: 8px;
			height: 8px;
			border-radius: 50%;
			background: rgba(255, 255, 255, 0.5);
			cursor: pointer;
			transition: var(--transition);
		}

		.slider-dot.active {
			background: white;
			width: 24px;
			border-radius: 4px;
		}

		/* Map View */
		.map-view {
			width: 100%;
			height: 285px;
			border-radius: 12px;
			background: var(--bg-surface);
			margin: 0 auto;
		}

		/* Route Controls */
		.route-controls {
			display: flex;
			gap: 0;
			margin-top: 1.5rem;
		}

		.route-input {
			height: 48px;
			border-radius: 12px 0 0 12px;
			background-color: transparent;
			border: 1px solid var(--border-color);
			border-right: none;
			display: flex;
			align-items: center;
			padding: 0 20px;
			gap: 10px;
			flex: 1;
			transition: var(--transition);
		}

		.route-input:focus-within {
			border-color: var(--text-primary);
			box-shadow: 0 0 0 2px var(--border-color-2);
		}

		.route-input input {
			background: none;
			border: none;
			flex: 1;
			font-size: 0.87rem;
			color: var(--text-primary);
			outline: none;
		}

		.route-input i {
			font-size: 1.1rem;
			opacity: 0.5;
		}

		.route-button {
			height: 48px;
			padding: 6px 18px;
			border-radius: 0 12px 12px 0;
			text-transform: uppercase;
			background: rgb(240, 240, 240);
			color: #13131380;
			border: 1px solid rgb(224, 226, 228);
			border-left: none;
			font-weight: 600;
			font-size: 1.2rem;
			cursor: pointer;
			transition: var(--transition);
			display: flex;
			align-items: center;
			gap: 8px;
			white-space: nowrap;
		}

		.route-button:hover {
			background: var(--text-secondary);
			color: #fdfdfd;
			filter: brightness(1.08);
		}

		.route-button:hover:active {
			background: #fff;
			color: #000;
			filter: brightness(1.8);
			transform: scale(1.02);
		}

		/* Ticket Cards */
		.tickets-grid {
			display: flex;
			flex-direction: column;
		}

		.ticket-card {
			background: var(--bg-surface);
			border: 1px solid var(--border-color);
			border-radius: var(--radius-card);
			padding: 1rem;
			margin-top: 1.45rem;
			text-decoration: none;
			display: flex;
			flex-direction: row;
			align-items: center;
			justify-content: flex-start;
			text-align: left;
			transition: var(--transition);
			filter: contrast(0.8);
			user-select: none;
		}

		.ticket-card:hover {
			transform: translateY(4px);
			box-shadow: 0 10px 30px var(--card-shadow-light);
			border: 1px solid #3434341a;
			filter: contrast(1) brightness(1.02);
		}

		.ticket-card.disabled {
			cursor: default;
			pointer-events: none;
			opacity: 0.5;
			filter: contrast(1) brightness(1);
		}

		.ticket-icon {
			width: 50px;
			height: 50px;
			border-radius: 50%;
			background: var(--bg-surface);
			border: 2px solid var(--border-color);
			display: flex;
			align-items: center;
			justify-content: center;
			margin-right: 1rem;
		}

		.ticket-icon i {
			font-size: 1.75rem;
			color: var(--text-primary);
		}

		.ticket-info {
			display: flex;
			flex-direction: column;
			align-items: flex-start;
		}

		.ticket-name {
			font-size: 1.25rem;
			font-weight: 700;
			margin-bottom: 0.25rem;
			color: var(--text-primary);
		}

		.ticket-cta {
			font-size: 0.875rem;
			font-weight: 600;
			color: var(--text-secondary);
		}

		/* Apollo Coupon Detail */
		.apollo-coupon-detail {
			background: rgba(247, 148, 27, 0.08);
			border: 1px dashed var(--border-color);
			border-radius: 0 0 12px 12px;
			padding: 0.5rem 0.6rem 0.3rem 0.6rem;
			display: flex;
			margin: -0.2rem auto 0.2rem auto;
			align-items: center;
			gap: 0.84rem;
			font-size: 0.78rem;
			width: 96%;
			letter-spacing: 0.5px;
		}

		.apollo-coupon-detail span {
			margin: auto;
			text-align: center;
		}

		.apollo-coupon-detail .ri-coupon-3-line {
			font-size: 0.99rem;
			padding: 0.3rem 0;
			color: var(--text-secondary);
			transform: rotate(145deg);
		}

		.apollo-coupon-detail strong {
			font-size: 0.75rem;
			position: relative;
			display: inline-flex;
			font-weight: 800;
			text-align: center;
			margin: auto;
			color: var(--text-primary);
			letter-spacing: 1px;
		}

		.copy-code-mini {
			background: transparent;
			border: none;
			color: var(--text-secondary);
			border-radius: 3px;
			width: 17px;
			height: 17px;
			display: flex;
			align-items: center;
			justify-content: center;
			cursor: pointer;
			margin-left: auto;
			transition: var(--transition);
		}

		.copy-code-mini i {
			font-size: 1.05rem;
		}

		.copy-code-mini:hover {
			background: var(--text-secondary);
		}

		/* Secondary Image */
		.secondary-image {
			border-radius: var(--radius-card);
			overflow: hidden;
		}

		.secondary-image img {
			width: 100%;
			height: auto;
			display: block;
		}

		/* Bottom Bar */
		.bottom-bar {
			position: fixed;
			bottom: 0;
			left: 50%;
			transform: translateX(-50%);
			width: 100%;
			max-width: 500px;
			background: rgba(255, 255, 255, 0.95);
			backdrop-filter: blur(20px);
			padding: 1rem 1.5rem;
			box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
			z-index: 100;
			display: flex;
			gap: 0.75rem;
			border-radius: 22px 22px 0 0;
		}

		.bottom-btn {
			flex: 1;
			padding: 1rem;
			border-radius: var(--radius-main);
			border: 1px solid var(--border-color);
			font-weight: 700;
			font-size: 0.95rem;
			cursor: pointer;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 0.5rem;
			transition: var(--transition);
			text-decoration: none;
			background: var(--bg-main);
			color: var(--text-primary);
		}

		.bottom-btn:active {
			transform: scale(0.97);
		}

		.bottom-btn:hover {
			background: var(--bg-surface);
			border-color: var(--text-secondary);
		}

		.bottom-btn.primary {
			background: var(--text-primary);
			color: white;
			border-color: var(--text-primary);
		}

		.bottom-btn.primary:hover {
			background: var(--text-secondary);
			border-color: var(--text-secondary);
		}

		/* Responsive */
		@media (max-width: 768px) {
			.hero-media {
				height: 60vh;
			}

			.quick-actions {
				grid-template-columns: repeat(4, auto);
				gap: 0;
				padding: 0.1rem 1rem 0 1rem;
				margin: 1.5rem auto 0;
			}

			.rsvp-row {
				padding: 2rem;
				margin: 0.7rem auto;
			}

			.tickets-grid {
				grid-template-columns: 1fr;
			}

			.interested-text {
				right: -55px;
			}

			.avatars-explosion {
				right: 70px;
			}

			.promo-gallery-slider {
				height: 200px;
			}

			.local-images-slider {
				height: 250px;
			}

			.avatar {
				width: 36px;
				height: 36px;
			}

			.lineup-avatar-img,
			.lineup-avatar-fallback {
				width: 30%;
				height: 60px;
				font-size: 1.5rem;
			}
		}

		/* Print Mode */
		<?php if ( $is_print ) : ?>
		@media print {
			.bottom-bar,
			.quick-actions,
			.route-controls,
			.promo-controls {
				display: none !important;
			}

			.mobile-container {
				max-width: 100%;
				box-shadow: none;
				margin: 0;
				padding-bottom: 0;
			}

			.hero-media {
				height: 40vh;
				page-break-inside: avoid;
			}

			.section {
				page-break-inside: avoid;
			}
		}
		<?php endif; ?>
	</style>
</head>

<body>
	<!-- LAYOUT: MOBILE_CONTAINER -->
	<div class="mobile-container">

		<!-- ================================================================
			BLOCK: HERO_MEDIA
			================================================================ -->
		<div class="hero-media">
			<?php if ( $has_video ) : ?>
				<div class="video-cover">
					<iframe
						src="<?php echo esc_url( $video_url ); ?>"
						allow="autoplay; fullscreen"
						allowfullscreen
						frameborder="0"
					></iframe>
				</div>
			<?php elseif ( $banner_url ) : ?>
				<img src="<?php echo esc_url( $banner_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="hero-image">
			<?php elseif ( $thumbnail_url ) : ?>
				<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="hero-image">
			<?php endif; ?>

			<div class="hero-overlay"></div>

			<div class="hero-content">
				<?php if ( ! empty( $tags ) ) : ?>
					<?php foreach ( $tags as $tag ) : ?>
						<span class="event-tag-pill">
							<?php if ( ! empty( $tag['icon'] ) ) : ?>
								<i class="<?php echo esc_attr( $tag['icon'] ); ?>"></i>
							<?php endif; ?>
							<?php echo esc_html( $tag['name'] ); ?>
						</span>
					<?php endforeach; ?>
				<?php endif; ?>

				<h1 class="hero-title"><?php echo esc_html( $title ); ?></h1>

				<div class="hero-meta">
					<div class="hero-meta-item">
						<i class="ri-calendar-line"></i>
						<span><?php echo esc_html( $formatted_date ); ?></span>
					</div>
					<div class="hero-meta-item">
						<i class="ri-time-line"></i>
						<span id="Hora">
							<?php echo esc_html( $start_time ); ?>
							<?php if ( $end_time ) : ?>
								- <?php echo esc_html( $end_time ); ?>
							<?php endif; ?>
						</span>
						<font style="opacity:.7;font-weight:300;font-size:.81rem;vertical-align:bottom;">(GMT-03h00)</font>
					</div>
					<?php if ( $has_venue ) : ?>
						<div class="hero-meta-item">
							<i class="ri-map-pin-line"></i>
							<span><?php echo esc_html( $venue_name ); ?></span>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- ================================================================
			BLOCK: EVENT_BODY
			================================================================ -->
		<div class="event-body">

			<!-- WIDGET: QUICK_ACTIONS -->
			<div class="quick-actions">
				<?php if ( $has_tickets ) : ?>
					<a href="#route_TICKETS" class="quick-action">
						<div class="quick-action-icon">
							<i class="ri-ticket-2-line"></i>
						</div>
						<span class="quick-action-label">TICKETS</span>
					</a>
				<?php endif; ?>

				<?php if ( $has_lineup ) : ?>
					<a href="#route_LINE" class="quick-action">
						<div class="quick-action-icon">
							<i class="ri-draft-line"></i>
						</div>
						<span class="quick-action-label">Line-up</span>
					</a>
				<?php endif; ?>

				<?php if ( $has_venue ) : ?>
					<a href="#route_ROUTE" class="quick-action">
						<div class="quick-action-icon">
							<i class="ri-treasure-map-line"></i>
						</div>
						<span class="quick-action-label">ROUTE</span>
					</a>
				<?php endif; ?>

				<a href="#" class="quick-action" id="favoriteTrigger">
					<div class="quick-action-icon">
						<i class="ri-rocket-line"></i>
					</div>
					<span class="quick-action-label">Interesse</span>
				</a>
			</div>

			<!-- WIDGET: RSVP_AVATARS -->
			<?php if ( ! empty( $visible_users ) || $total_interested > 0 ) : ?>
				<div class="rsvp-row">
					<div class="avatars-explosion">
						<?php foreach ( $visible_users as $user ) : ?>
							<?php
							$avatar_url = '';
							if ( is_object( $user ) && isset( $user->ID ) ) {
								$avatar_url = get_avatar_url( $user->ID, array( 'size' => 80 ) );
							} elseif ( is_array( $user ) && isset( $user['avatar_url'] ) ) {
								$avatar_url = $user['avatar_url'];
							}
							?>
							<?php if ( $avatar_url ) : ?>
								<div class="avatar" style="background-image: url('<?php echo esc_url( $avatar_url ); ?>')"></div>
							<?php endif; ?>
						<?php endforeach; ?>

						<?php if ( $hidden_count > 0 ) : ?>
							<div class="avatar-count">+<?php echo esc_html( $hidden_count ); ?></div>
						<?php endif; ?>

						<p class="interested-text" style="margin: 0 8px 0 20px;">
							<i class="ri-bar-chart-2-fill"></i>
							<span id="result"><?php echo esc_html( $total_interested ); ?></span>
						</p>
					</div>
				</div>
			<?php endif; ?>

			<!-- INFO SECTION -->
			<?php if ( $description || ! empty( $sounds ) ) : ?>
				<section class="section">
					<h2 class="section-title">
						<i class="ri-brain-ai-3-fill"></i> Info
					</h2>

					<?php if ( $description ) : ?>
						<div class="info-card">
							<p class="info-text"><?php echo wp_kses_post( $description ); ?></p>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $sounds ) ) : ?>
						<div class="music-tags-marquee">
							<div class="music-tags-track">
								<?php
								// Duplicate sounds for infinite loop effect.
								$loop_sounds = array_merge( $sounds, $sounds );
								foreach ( $loop_sounds as $sound ) :
									$sound_name = is_object( $sound ) ? $sound->name : ( is_array( $sound ) ? $sound['name'] : $sound );
									?>
									<span class="music-tag"><?php echo esc_html( $sound_name ); ?></span>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<!-- PROMO GALLERY (max 5 images) -->
			<?php if ( $has_gallery ) : ?>
				<div class="promo-gallery-slider">
					<div class="promo-track" id="promoTrack">
						<?php foreach ( array_slice( $gallery_images, 0, 5 ) as $index => $image ) : ?>
							<?php $image_url = is_array( $image ) ? ( $image['url'] ?? '' ) : $image; ?>
							<div class="promo-slide">
								<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( sprintf( __( 'Promo %d', 'apollo' ), $index + 1 ) ); ?>">
							</div>
						<?php endforeach; ?>
					</div>

					<div class="promo-controls">
						<button class="promo-prev"><i class="ri-arrow-left-s-line"></i></button>
						<button class="promo-next"><i class="ri-arrow-right-s-line"></i></button>
					</div>
				</div>
			<?php endif; ?>

			<!-- DJ LINEUP SECTION -->
			<?php if ( $has_lineup ) : ?>
				<section class="section" id="route_LINE">
					<h2 class="section-title"><i class="ri-disc-line"></i> Line-up</h2>
					<div class="lineup-list">
						<?php foreach ( $dj_slots as $slot ) : ?>
							<div class="lineup-card">
								<?php if ( ! empty( $slot['thumbnail'] ) ) : ?>
									<img
										src="<?php echo esc_url( $slot['thumbnail'] ); ?>"
										alt="<?php echo esc_attr( $slot['name'] ?? '' ); ?>"
										class="lineup-avatar-img"
									>
								<?php else : ?>
									<div class="lineup-avatar-fallback">
										<?php echo esc_html( mb_substr( $slot['name'] ?? 'DJ', 0, 1 ) ); ?>
									</div>
								<?php endif; ?>

								<div class="lineup-info">
									<h3 class="lineup-name">
										<?php if ( ! empty( $slot['permalink'] ) ) : ?>
											<a href="<?php echo esc_url( $slot['permalink'] ); ?>" class="dj-link" target="_blank">
												<?php echo esc_html( $slot['name'] ?? '' ); ?>
											</a>
										<?php else : ?>
											<?php echo esc_html( $slot['name'] ?? '' ); ?>
										<?php endif; ?>
									</h3>
									<?php if ( ! empty( $slot['start'] ) ) : ?>
										<div class="lineup-time">
											<i class="ri-time-line"></i>
											<span>
												<?php echo esc_html( $slot['start'] ); ?>
												<?php if ( ! empty( $slot['end'] ) ) : ?>
													- <?php echo esc_html( $slot['end'] ); ?>
												<?php endif; ?>
											</span>
										</div>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<!-- VENUE SECTION -->
			<?php if ( $has_venue ) : ?>
				<section class="section" id="route_ROUTE">
					<h2 class="section-title">
						<i class="ri-map-pin-2-line"></i> <?php echo esc_html( $venue_name ); ?>
					</h2>
					<?php if ( $venue_address ) : ?>
						<p style="margin:0.5rem 0 1.5rem 0;font-size:0.95rem;"><?php echo esc_html( $venue_address ); ?></p>
					<?php endif; ?>

					<!-- Venue Images Slider (max 5) -->
					<?php if ( ! empty( $venue_images ) ) : ?>
						<div class="local-images-slider">
							<div class="local-images-track" id="localTrack">
								<?php foreach ( array_slice( $venue_images, 0, 5 ) as $index => $img ) : ?>
									<?php $img_url = is_array( $img ) ? ( $img['url'] ?? '' ) : $img; ?>
									<div class="local-image">
										<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( sprintf( __( 'Venue %d', 'apollo' ), $index + 1 ) ); ?>">
									</div>
								<?php endforeach; ?>
							</div>
							<div class="slider-nav" id="localDots"></div>
						</div>
					<?php endif; ?>

					<!-- Map View -->
					<?php if ( $venue_lat && $venue_lng ) : ?>
						<div class="map-view"
							id="mapView"
							data-lat="<?php echo esc_attr( $venue_lat ); ?>"
							data-lng="<?php echo esc_attr( $venue_lng ); ?>"
							style="background-image:url('https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/<?php echo esc_attr( $venue_lng ); ?>,<?php echo esc_attr( $venue_lat ); ?>,14,0/500x285?access_token=YOUR_TOKEN');background-size:cover;background-position:center;">
						</div>

						<!-- Route Input -->
						<div class="route-controls" style="transform:translateY(-80px);padding:0 0.5rem;">
							<div class="route-input glass">
								<i class="ri-map-pin-line"></i>
								<input type="text" id="origin-input" placeholder="<?php esc_attr_e( 'Seu endereço de partida', 'apollo' ); ?>">
							</div>
							<button id="route-btn" class="route-button" data-destination="<?php echo esc_attr( $venue_address ); ?>">
								<i class="ri-send-plane-line"></i>
							</button>
						</div>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<!-- TICKETS SECTION -->
			<?php if ( $has_tickets ) : ?>
				<section class="section" id="route_TICKETS">
					<h2 class="section-title">
						<i class="ri-ticket-2-line" style="margin:2px 0 -2px 0"></i> Acessos
					</h2>

					<div class="tickets-grid">
						<a href="<?php echo esc_url( $ticket_url . '?ref=apollo.rio.br' ); ?>" class="ticket-card" target="_blank">
							<div class="ticket-icon"><i class="ri-ticket-line"></i></div>
							<div class="ticket-info">
								<h3 class="ticket-name"><span id="changingword">Tickets</span></h3>
								<span class="ticket-cta"><?php esc_html_e( 'Acessar Bilheteria Digital →', 'apollo' ); ?></span>
							</div>
						</a>

						<?php if ( $ticket_coupon ) : ?>
							<div class="apollo-coupon-detail">
								<i class="ri-coupon-3-line"></i>
								<span><?php esc_html_e( 'Verifique se o cupom', 'apollo' ); ?> <strong><?php echo esc_html( $ticket_coupon ); ?></strong> <?php esc_html_e( 'está ativo com desconto', 'apollo' ); ?></span>
								<button class="copy-code-mini" onclick="copyPromoCode('<?php echo esc_js( $ticket_coupon ); ?>')">
									<i class="ri-file-copy-fill"></i>
								</button>
							</div>
						<?php endif; ?>

						<!-- Lista Amiga (disabled by default) -->
						<div class="ticket-card disabled">
							<div class="ticket-icon">
								<i class="ri-list-check"></i>
							</div>
							<div class="ticket-info">
								<h3 class="ticket-name"><?php esc_html_e( 'Lista Amiga', 'apollo' ); ?></h3>
								<span class="ticket-cta"><?php esc_html_e( 'Ver Lista Amiga →', 'apollo' ); ?></span>
							</div>
						</div>
					</div>
				</section>
			<?php endif; ?>

			<!-- FINAL EVENT IMAGE -->
			<?php if ( $final_image_url ) : ?>
				<section class="section">
					<div class="secondary-image">
						<img src="<?php echo esc_url( $final_image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>">
					</div>
				</section>
			<?php endif; ?>

		</div><!-- .event-body -->

		<!-- BOTTOM BAR -->
		<?php if ( ! $is_print ) : ?>
			<div class="bottom-bar">
				<?php if ( $has_tickets ) : ?>
					<a href="#route_TICKETS" class="bottom-btn primary" id="bottomTicketBtn">
						<i class="ri-ticket-fill"></i>
						<span id="changingword">Tickets</span>
					</a>
				<?php endif; ?>

				<button class="bottom-btn secondary" id="bottomShareBtn">
					<i class="ri-share-forward-line"></i>
				</button>
			</div>
		<?php endif; ?>

	</div><!-- .mobile-container -->

	<script>
	'use strict';

	// Promo Gallery Slider.
	(function() {
		const promoTrack = document.getElementById('promoTrack');
		if (!promoTrack) return;

		const promoSlides = promoTrack.children.length;
		let currentPromo = 0;

		document.querySelector('.promo-prev')?.addEventListener('click', () => {
			currentPromo = (currentPromo - 1 + promoSlides) % promoSlides;
			promoTrack.style.transform = `translateX(-${currentPromo * 100}%)`;
		});

		document.querySelector('.promo-next')?.addEventListener('click', () => {
			currentPromo = (currentPromo + 1) % promoSlides;
			promoTrack.style.transform = `translateX(-${currentPromo * 100}%)`;
		});
	})();

	// Venue Images Slider.
	(function() {
		const localTrack = document.getElementById('localTrack');
		const localDots = document.getElementById('localDots');
		if (!localTrack || !localTrack.children.length) return;

		const slideCount = localTrack.children.length;
		let currentSlide = 0;

		// Create dots.
		for (let i = 0; i < slideCount; i++) {
			const dot = document.createElement('div');
			dot.classList.add('slider-dot');
			if (i === 0) dot.classList.add('active');
			dot.addEventListener('click', () => goToSlide(i));
			localDots.appendChild(dot);
		}

		function goToSlide(index) {
			currentSlide = index;
			localTrack.style.transition = 'transform 0.5s ease';
			localTrack.style.transform = `translateX(-${index * 100}%)`;
			updateDots();
		}

		function updateDots() {
			document.querySelectorAll('.slider-dot').forEach((dot, i) => {
				dot.classList.toggle('active', i === currentSlide);
			});
		}

		// Auto-advance.
		setInterval(() => {
			currentSlide = (currentSlide + 1) % slideCount;
			goToSlide(currentSlide);
		}, 4000);
	})();

	// Favorite Toggle.
	(function() {
		const trigger = document.getElementById('favoriteTrigger');
		if (!trigger) return;

		trigger.addEventListener('click', function(event) {
			event.preventDefault();

			const iconContainer = this.querySelector('.quick-action-icon');
			const icon = iconContainer.querySelector('i');

			if (icon.classList.contains('ri-rocket-line')) {
				iconContainer.classList.add('fly-away');
				setTimeout(() => {
					iconContainer.classList.remove('fly-away');
					icon.className = 'ri-ai-agent-fill fade-in';
					iconContainer.style.borderColor = 'rgba(0,0,0,0.2)';
				}, 500);
			} else {
				icon.className = 'ri-rocket-line fade-in';
			}
		});
	})();

	// Route Button.
	(function() {
		const routeBtn = document.getElementById('route-btn');
		if (!routeBtn) return;

		routeBtn.addEventListener('click', () => {
			const originInput = document.getElementById('origin-input');
			const origin = originInput.value;
			const destination = routeBtn.dataset.destination;

			if (origin && destination) {
				window.open(
					`https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(destination)}`,
					'_blank'
				);
			} else {
				originInput.placeholder = 'Por favor, insira um endereço!';
				setTimeout(() => {
					originInput.placeholder = 'Seu endereço de partida';
				}, 2000);
			}
		});
	})();

	// Copy Promo Code.
	function copyPromoCode(code) {
		navigator.clipboard.writeText(code).then(() => {
			const btn = event.target.closest('.copy-code-mini');
			const originalHTML = btn.innerHTML;
			btn.innerHTML = '<i class="ri-check-line"></i>';
			setTimeout(() => {
				btn.innerHTML = originalHTML;
			}, 2000);
		});
	}

	// Share Function.
	(function() {
		const shareBtn = document.getElementById('bottomShareBtn');
		if (!shareBtn) return;

		shareBtn.addEventListener('click', () => {
			const title = <?php echo wp_json_encode( $title ); ?>;

			if (navigator.share) {
				navigator.share({
					title: title,
					text: 'Confere esse evento no Apollo::rio!',
					url: window.location.href
				});
			} else {
				navigator.clipboard.writeText(window.location.href);
				alert('Link copiado!');
			}
		});
	})();

	// Bottom Ticket Button Smooth Scroll.
	(function() {
		const ticketBtn = document.getElementById('bottomTicketBtn');
		if (!ticketBtn) return;

		ticketBtn.addEventListener('click', (e) => {
			e.preventDefault();
			document.getElementById('route_TICKETS')?.scrollIntoView({
				behavior: 'smooth',
				block: 'start'
			});
		});
	})();

	// Ticket Name Animation.
	(function() {
		const words = ['Entradas', 'Ingressos', 'Billets', 'Ticket', 'Acessos', 'Biglietti'];
		let i = 0;

		const elems = document.querySelectorAll('#changingword');
		if (!elems.length) return;

		function fadeOut(el, duration, callback) {
			el.style.opacity = 1;
			let start = null;

			function step(timestamp) {
				if (!start) start = timestamp;
				const progress = timestamp - start;
				const fraction = progress / duration;

				if (fraction < 1) {
					el.style.opacity = 1 - fraction;
					window.requestAnimationFrame(step);
				} else {
					el.style.opacity = 0;
					if (callback) callback();
				}
			}
			window.requestAnimationFrame(step);
		}

		function fadeIn(el, duration, callback) {
			el.style.opacity = 0;
			let start = null;

			function step(timestamp) {
				if (!start) start = timestamp;
				const progress = timestamp - start;
				const fraction = progress / duration;

				if (fraction < 1) {
					el.style.opacity = fraction;
					window.requestAnimationFrame(step);
				} else {
					el.style.opacity = 1;
					if (callback) callback();
				}
			}
			window.requestAnimationFrame(step);
		}

		setInterval(() => {
			elems.forEach(elem => {
				fadeOut(elem, 400, () => {
					i = (i + 1) % words.length;
					elem.textContent = words[i];
					fadeIn(elem, 400);
				});
			});
		}, 4000);
	})();
	</script>

	<?php wp_footer(); ?>
</body>
</html>
