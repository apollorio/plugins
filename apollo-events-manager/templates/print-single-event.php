<?php
$event_id = (int) ( $GLOBALS['apollo_print_event_id'] ?? 0 );

if ( ! function_exists( 'apollo_initials' ) ) {
	function apollo_initials( $name ) {
		$name = trim( (string) $name );
		if ( $name === '' ) {
			return 'DJ';
		}
		$parts   = preg_split( '/\s+/', $name );
		$letters = '';
		foreach ( $parts as $p ) {
			$letters .= mb_substr( $p, 0, 1 );
			if ( mb_strlen( $letters ) >= 2 ) {
				break;
			}
		}
		return mb_strtoupper( $letters );
	}
}

if ( ! function_exists( 'apollo_safe_text' ) ) {
	function apollo_safe_text( $value ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}
		return wp_kses_post( (string) $value );
	}
}

if ( ! function_exists( 'apollo_safe_url' ) ) {
	function apollo_safe_url( $url ) {
		$url = (string) $url;
		if ( $url === '' ) {
			return '';
		}
		return esc_url( $url );
	}
}

if ( ! function_exists( 'apollo_youtube_id_from_url' ) ) {
	function apollo_youtube_id_from_url( $url ) {
		$url = trim( (string) $url );
		if ( $url === '' ) {
			return '';
		}

		if ( preg_match( '~^[a-zA-Z0-9_-]{10,15}$~', $url ) ) {
			return $url;
		}

		$patterns = array(
			'~youtube\.com/watch\?v=([^&]+)~',
			'~youtu\.be/([^?&/]+)~',
			'~youtube\.com/embed/([^?&/]+)~',
			'~youtube\.com/shorts/([^?&/]+)~',
		);
		foreach ( $patterns as $p ) {
			if ( preg_match( $p, $url, $m ) ) {
				return $m[1];
			}
		}
		return '';
	}
}

if ( ! function_exists( 'apollo_build_youtube_embed_url' ) ) {
	function apollo_build_youtube_embed_url( $youtube_url_or_id ) {
		$vid = apollo_youtube_id_from_url( $youtube_url_or_id );
		if ( $vid === '' ) {
			return '';
		}

		$params = array(
			'autoplay'       => '1',
			'mute'           => '1',
			'controls'       => '0',
			'loop'           => '1',
			'playlist'       => $vid,
			'playsinline'    => '1',
			'modestbranding' => '1',
			'rel'            => '0',
			'fs'             => '0',
			'disablekb'      => '1',
			'iv_load_policy' => '3',
			'origin'         => home_url(),
		);
		return 'https://www.youtube-nocookie.com/embed/' . rawurlencode( $vid ) . '?' . http_build_query( $params, '', '&', PHP_QUERY_RFC3986 );
	}
}

if ( ! function_exists( 'apollo_event_get_coords' ) ) {
	function apollo_event_get_coords( $event_id ) {
		$event_id = (int) $event_id;

		$lat = null;
		$lng = null;

		$candidates = array(
			array( '_event_lat', '_event_lng' ),
			array( '_event_location_lat', '_event_location_lng' ),
		);

		foreach ( $candidates as $pair ) {
			$la = get_post_meta( $event_id, $pair[0], true );
			$ln = get_post_meta( $event_id, $pair[1], true );
			if ( $la !== '' && $ln !== '' ) {
				$lat = (float) $la;
				$lng = (float) $ln;
				break;
			}
		}

		if ( $lat === null || $lng === null ) {
			$venue_id = (int) get_post_meta( $event_id, '_event_venue_id', true );
			if ( ! $venue_id ) {
				$venue_id = (int) get_post_meta( $event_id, '_event_local_id', true );
			}
			if ( $venue_id ) {
				$la = get_post_meta( $venue_id, '_venue_lat', true );
				$ln = get_post_meta( $venue_id, '_venue_lng', true );
				if ( $la !== '' && $ln !== '' ) {
					$lat = (float) $la;
					$lng = (float) $ln;
				}
			}
		}

		$coords = array(
			'lat' => $lat,
			'lng' => $lng,
		);

		$coords = apply_filters( 'apollo_event_map_coords', $coords, $event_id );

		if ( ! isset( $coords['lat'], $coords['lng'] ) ) {
			$coords = array(
				'lat' => null,
				'lng' => null,
			);
		}

		return $coords;
	}
}

if ( ! function_exists( 'apollo_event_get_dj_slots' ) ) {
	function apollo_event_get_dj_slots( $event_id ) {
		$event_id = (int) $event_id;

		$slots = get_post_meta( $event_id, '_event_dj_slots', true );
		if ( ! is_array( $slots ) ) {
			$slots = array();
		}

		$clean = array();

		foreach ( $slots as $slot ) {
			if ( ! is_array( $slot ) ) {
				continue;
			}

		$dj_id = isset( $slot['dj_id'] ) ? (int) $slot['dj_id'] : ( isset( $slot['dj'] ) ? (int) $slot['dj'] : 0 );
		if ( ! $dj_id ) {
			continue;
		}

		$start = isset( $slot['start'] ) ? sanitize_text_field( (string) $slot['start'] ) : ( isset( $slot['from'] ) ? sanitize_text_field( (string) $slot['from'] ) : '' );
		$end   = isset( $slot['end'] ) ? sanitize_text_field( (string) $slot['end'] ) : ( isset( $slot['to'] ) ? sanitize_text_field( (string) $slot['to'] ) : '' );

		$clean[] = array(
			'dj_id' => $dj_id,
			'start' => $start,
			'end'   => $end,
		);
	}

	// If empty, fallback to simple list
	if ( empty( $clean ) ) {
		$djs = get_post_meta( $event_id, '_event_djs', true );
		if ( is_array( $djs ) ) {
			foreach ( $djs as $dj_id ) {
				$dj_id = (int) $dj_id;
				if ( $dj_id > 0 ) {
					$clean[] = array(
						'dj_id' => $dj_id,
						'start' => '',
						'end'   => '',
					);
				}
			}
		}
	}

	$clean = apply_filters( 'apollo_event_dj_slots', $clean, $event_id );

	if ( ! is_array( $clean ) ) {
		$clean = array();
	}

	// Sort by order if available, then by start time
	usort(
		$clean,
		function ( $a, $b ) {
			// First, check for custom order
			$a_order = isset( $a['order'] ) ? (int) $a['order'] : 0;
			$b_order = isset( $b['order'] ) ? (int) $b['order'] : 0;
			if ( $a_order > 0 && $b_order > 0 ) {
				return $a_order <=> $b_order;
			}
			if ( $a_order > 0 ) {
				return -1;
			}
			if ( $b_order > 0 ) {
				return 1;
			}

			// Fallback to start time
			$as = isset( $a['start'] ) ? $a['start'] : '';
			$bs = isset( $b['start'] ) ? $b['start'] : '';
			if ( $as === '' && $bs === '' ) {
				return 0;
			}
			if ( $as === '' ) {
				return 1;
			}
			if ( $bs === '' ) {
				return -1;
			}
			return strcmp( $as, $bs );
		}
	);

	return $clean;
	}
}

$title     = get_the_title( $event_id );
$permalink = get_permalink( $event_id );

// Pull whatever meta you need (adjust keys to your plugin)
$description = get_post_field( 'post_excerpt', $event_id );
if ( ! $description ) {
	$description = wp_strip_all_tags( get_post_field( 'post_content', $event_id ) );
}

// Replicate variable setup from single-event_listing.php
$event_title = get_the_title( $event_id );

// Meta candidates
$event_city    = get_post_meta( $event_id, '_event_city', true );
$event_address = get_post_meta( $event_id, '_event_address', true );

$event_date_display = get_post_meta( $event_id, '_event_date_display', true );
$event_start_time   = get_post_meta( $event_id, '_event_start_time', true );
$event_end_time     = get_post_meta( $event_id, '_event_end_time', true );

$youtube_url = get_post_meta( $event_id, '_event_youtube_url', true );
if ( ! $youtube_url ) {
	$youtube_url = get_post_meta( $event_id, '_event_video_url', true );
}
$youtube_embed = apollo_build_youtube_embed_url( $youtube_url );

$featured_img = get_the_post_thumbnail_url( $event_id, 'full' );
if ( ! $featured_img ) {
	$featured_img = ( defined( 'APOLLO_CORE_PLUGIN_URL' ) ? APOLLO_CORE_PLUGIN_URL . 'assets/img/' : APOLLO_APRIO_URL . 'assets/img/' ) . 'default-event.jpg';
}

// Tickets / links
$tickets_url   = get_post_meta( $event_id, '_event_ticket_url', true );
$guestlist_url = get_post_meta( $event_id, '_event_guestlist_url', true );

// Coupon
$coupon_code = get_post_meta( $event_id, '_event_coupon_code', true );
if ( $coupon_code === '' ) {
	$coupon_code = 'APOLLO';
}

// Interested users
$interested_ids   = apollo_event_get_interested_user_ids( $event_id );
$total_interested = count( $interested_ids );
$max_visible      = 10;
$visible_ids      = array_slice( $interested_ids, 0, $max_visible );
$hidden_count     = max( 0, $total_interested - count( $visible_ids ) );

// Map coords
$coords = apollo_event_get_coords( $event_id );

// DJ slots
$dj_slots = apollo_event_get_dj_slots( $event_id );

// Sounds tags (taxonomy)
$sounds = array();
$terms  = get_the_terms( $event_id, 'event_sounds' );
if ( is_array( $terms ) ) {
	foreach ( $terms as $t ) {
		if ( $t && isset( $t->name ) ) {
			$sounds[] = $t->name;
		}
	}
}
// Duplicate for marquee
if ( count( $sounds ) > 0 && count( $sounds ) < 6 ) {
	$orig = $sounds;
	while ( count( $sounds ) < 8 ) {
		$sounds = array_merge( $sounds, $orig );
	}
	$sounds = array_slice( $sounds, 0, 8 );
}

// PROMO GALLERY IMAGES (NEW)
$promo_images = get_post_meta( $event_id, '_event_promo_gallery', true );
if ( ! is_array( $promo_images ) ) {
	$promo_images = array();
}
// Limit to 5 images
$promo_images = array_slice( $promo_images, 0, 5 );

// VENUE IMAGES (NEW)
$venue_images = get_post_meta( $event_id, '_event_venue_gallery', true );
if ( ! is_array( $venue_images ) ) {
	$venue_images = array();
}
// Limit to 5 images
$venue_images = array_slice( $venue_images, 0, 5 );

// FINAL EVENT IMAGE (NEW)
$final_image = get_post_meta( $event_id, '_event_final_image', true );

?>
<!doctype html>
<html lang="<?php echo esc_attr( str_replace( '_', '-', get_locale() ) ); ?>">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title><?php echo esc_html( $title ); ?></title>
	<link rel="icon" href="<?php echo esc_url( defined( 'APOLLO_CORE_PLUGIN_URL' ) ? APOLLO_CORE_PLUGIN_URL . 'assets/img/' : APOLLO_APRIO_URL . 'assets/img/' ); ?>neon-green.webp" type="image/webp">

	<?php // remixicon and uni.css are enqueued via wp_head() - do not load from CDN ?>
	<?php wp_head(); ?>

	<!-- Paste/attach your event single CSS here (or link your plugin CSS directly) -->
	<style>
	* {
		-webkit-tap-highlight-color: transparent; corner-shape:squircle;
		box-sizing: border-box;
		margin: 0;
		padding: 0;
	}

	:root {
		--font-primary: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
		--bg-main: #fff;
		--bg-surface: #f5f5f5;
		--text-primary: rgba(19, 21, 23, .85);
		--text-secondary: rgba(19, 21, 23, .7);
		--border-color: #e0e2e4;
		--border-color-2: #e0e2e454;
		--radius-main: 12px;
		--radius-card: 16px;
		--transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
		--card-shadow-light: rgba(0, 0, 0, 0.05);
	}
	i {margin:1px 0 -1px 0}

	html, body {
		font-family: var(--font-primary);
		background: var(--bg-surface);
		color: var(--text-primary);
		line-height: 1.5;
		-webkit-font-smoothing: antialiased;
		-moz-osx-font-smoothing: grayscale;
	}

	@media (min-width: 888px) {
		body {
		display: flex;
		justify-content: center;
		align-items: flex-start;
		min-height: 100vh;
		padding: 5rem 0 0rem;
		background: var(--bg-surface);
		}
		.mobile-container {
		max-width: 500px;
		width: 100%;
		background: var(--bg-main);
		box-shadow: 0 0 60px rgba(0,0,0,0.1);
		border-radius: 2rem;
		overflow: hidden;
		}
	}

	@media (max-width: 888px) {
		.mobile-container {
		width: 100%;
		min-height: 100vh;
		background: var(--bg-main);
		}
	}

	/* HERO */
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
		height: 56.25vw; /* 16:9 ratio */
		min-height: 100vh;
		min-width: 177.77vh; /* ensures full coverage regardless of aspect ratio */
		transform: translate(-50%, -50%);
		overflow: hidden;
	}

	.video-cover iframe,
	.video-cover img {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		border: 0;
		pointer-events: none;
	}

	.hero-overlay {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background: linear-gradient(
		to bottom,
		rgba(0,0,0,0.3) 0%,
		rgba(0,0,0,0.5) 50%,
		rgba(0,0,0,0.8) 100%
		);
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
		gap: 0.5rem;
		background: rgba(255,255,255,0.2);
		backdrop-filter: blur(10px);
		border: 1px solid rgba(255,255,255,0.3);
		border-radius: 20px;
		padding: 0.5rem 1rem;
		margin: 0.25rem;
		font-size: 0.875rem;
		font-weight: 500;
		color: white;
		user-select: none;
		-webkit-user-select: none;
		-moz-user-select: none;
		-ms-user-select: none;
	}

	.event-tag-pill i {
		font-size: 1rem;
		opacity: 0.9;
	}

	.hero-title {
		font-size: 2rem;
		font-weight: 700;
		margin-bottom: 0.5rem;
		color: white;
		line-height: 1.2;
	}

	.hero-meta {
		display: flex;
		flex-direction: column;
		gap: 0.5rem;
		margin: 0 auto;
	}

	.hero-meta-item {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		opacity: 0.9;
	}

	.hero-meta-item .yoha {
		margin-left: .8rem;
	}

	.hero-meta-item i {
		opacity: 0.5;
		font-size: 1.12rem;
	}

	/* BODY */
	.event-body {
		padding: 0;
	}

	.quick-actions {
		display: flex;
		justify-content: space-around;
		align-items: center;
		padding: 1.5rem;
		background: var(--bg-main);
		border-bottom: 1px solid var(--border-color);
	}

	.quick-action {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 0.5rem;
		padding: 0.75rem;
		border-radius: var(--radius-main);
		transition: var(--transition);
		cursor: pointer;
		user-select: none;
		-webkit-user-select: none;
		-moz-user-select: none;
		-ms-user-select: none;
	}

	.quick-action:hover {
		background: var(--bg-surface);
		color: var(--text-primary);
	}

	.quick-action-icon {
		width: 48px;
		height: 48px;
		border-radius: 50%;
		background: var(--bg-surface);
		border: 2px solid var(--border-color);
		display: flex;
		align-items: center;
		justify-content: center;
		transition: var(--transition);
	}

	.quick-action-icon:hover {
		transform: scale(1.1);
		border-color: var(--text-secondary);
		background: var(--bg-main);
	}

	.quick-action-icon i {
		font-size: 1.5rem;
		color: var(--text-primary);
	}

	/* RSVP */
	.rsvp-row {
		padding: 1rem 1.5rem;
		background: var(--bg-main);
	}

	.avatars-explosion {
		display: flex;
		align-items: center;
		gap: 0.5rem;
	}

	.avatar {
		width: 40px;
		height: 40px;
		border-radius: 50%;
		border: 3px solid var(--bg-main);
		overflow: hidden;
		transition: var(--transition);
		cursor: pointer;
		user-select: none;
		-webkit-user-select: none;
		-moz-user-select: none;
		-ms-user-select: none;
	}

	.avatar:first-child {
		margin-left: 0;
	}

	.avatar:hover {
		transform: scale(1.15) translateY(-4px);
		z-index: 10;
	}

	.avatar img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.avatar-count {
		width: 40px;
		height: 40px;
		border-radius: 50%;
		background: var(--bg-surface);
		border: 2px solid var(--border-color);
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 0.75rem;
		font-weight: 600;
		color: var(--text-secondary);
		user-select: none;
		-webkit-user-select: none;
		-moz-user-select: none;
		-ms-user-select: none;
	}

	.interested-text {
		margin-left: 1rem;
		font-size: 0.875rem;
		color: var(--text-secondary);
	}

	.section {
		padding: 1.5rem;
		background: var(--bg-main);
		border-bottom: 1px solid var(--border-color);
	}

	.section-title {
		font-size: 1.5rem;
		font-weight: 700;
		margin-bottom: 1rem;
		display: flex;
		align-items: center;
		gap: 0.5rem;
	}

	.section-title i {
		font-size: 1.75rem;
		opacity: 0.7;
	}

	.info-card {
		background: var(--bg-surface);
		border-radius: var(--radius-card);
		padding: 1rem;
		margin-bottom: 1rem;
	}

	.info-text {
		line-height: 1.6;
		color: var(--text-primary);
	}

	/* Sounds marquee */
	.music-tags-marquee {
		overflow: hidden;
		white-space: nowrap;
		position: relative;
	}

	.music-tags-track {
		display: inline-block;
		animation: marquee 20s linear infinite;
	}

	@keyframes marquee {
		0% { transform: translateX(0); }
		100% { transform: translateX(-50%); }
	}

	.music-tag {
		display: inline-block;
		background: var(--bg-surface);
		border: 1px solid var(--border-color);
		border-radius: 20px;
		padding: 0.5rem 1rem;
		margin: 0 0.5rem;
		font-size: 0.875rem;
		font-weight: 500;
		color: var(--text-primary);
		user-select: none;
		-webkit-user-select: none;
		-moz-user-select: none;
		-ms-user-select: none;
	}

	/* PROMO GALLERY SLIDER */
	.promo-gallery-slider {
		position: relative;
		width: 100%;
		overflow: hidden;
		border-radius: var(--radius-card);
	}

	.promo-track {
		display: flex;
		width: 100%;
		transition: transform 0.3s ease;
	}

	.promo-slide {
		flex-shrink: 0;
		width: 100%;
		height: 200px;
	}

	.promo-slide img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		border-radius: var(--radius-card);
	}

	.promo-controls {
		position: absolute;
		top: 50%;
		left: 0;
		right: 0;
		transform: translateY(-50%);
		display: flex;
		justify-content: space-between;
		padding: 0 1rem;
		pointer-events: none;
	}

	.promo-prev, .promo-next {
		width: 40px;
		height: 40px;
		border-radius: 50%;
		background: rgba(255,255,255,0.9);
		backdrop-filter: blur(10px);
		border: none;
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		transition: var(--transition);
		pointer-events: auto;
		box-shadow: 0 2px 10px rgba(0,0,0,0.1);
	}

	.promo-prev:hover, .promo-next:hover {
		background: white;
		transform: scale(1.1);
	}

	/* Lineup */
	.lineup-list {
		display: flex;
		flex-direction: column;
		gap: 1rem;
	}

	.lineup-card {
		display: flex;
		align-items: center;
		background: var(--bg-surface);
		border-radius: var(--radius-card);
		padding: 1rem;
		transition: var(--transition);
		cursor: pointer;
	}

	.lineup-card:hover {
		box-shadow: 0 4px 12px var(--card-shadow-light);
	}

	.lineup-avatar-img {
		width: 60px;
		height: 60px;
		border-radius: 50%;
		object-fit: cover;
		margin-right: 1rem;
		border: 2px solid var(--border-color);
	}

	.lineup-avatar-fallback {
		width: 60px;
		height: 60px;
		border-radius: 50%;
		background: var(--bg-surface);
		border: 2px solid var(--border-color);
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 1.25rem;
		font-weight: 700;
		color: var(--text-primary);
		margin-right: 1rem;
	}

	.lineup-info {
		flex: 1;
		margin-left: 16px;
	}

	.lineup-name {
		font-size: 1.125rem;
		font-weight: 700;
		margin-bottom: 0.25rem;
	}

	.dj-link {
		color: var(--text-primary);
		text-decoration: none;
		transition: color .3s ease;
	}

	.dj-link:hover {
		color: var(--text-secondary);
	}

	.lineup-time {
		display: flex;
		align-items: center;
		gap: 0.25rem;
		font-size: 0.875rem;
		color: var(--text-secondary);
	}

	.lineup-time i {
		opacity: 0.7;
	}

	/* Venue images slider */
	.local-images-slider {
		position: relative;
		width: 100%;
		height: 200px;
		overflow: hidden;
		border-radius: var(--radius-card);
	}

	.local-images-track {
		display: flex;
		width: 100%;
		height: 100%;
		transition: transform 0.5s ease;
	}

	.local-image {
		flex-shrink: 0;
		width: 100%;
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
		background: rgba(255,255,255,0.5);
		border: none;
		cursor: pointer;
		transition: var(--transition);
	}

	.slider-dot.active {
		background: white;
	}

	/* MAP */
	.map-view {
		width: 100%;
		height: 285px;
		border-radius: var(--radius-card);
		background: #f0f0f0;
		display: flex;
		align-items: center;
		justify-content: center;
		color: var(--text-secondary);
		font-size: 0.875rem;
	}

	/* Route controls */
	.route-controls {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0 1.5rem;
		margin-top: -2rem;
		position: relative;
		z-index: 10;
	}

	.route-input {
		flex: 1;
		display: flex;
		align-items: center;
		gap: 0.5rem;
		background: rgba(255,255,255,0.95);
		backdrop-filter: blur(10px);
		border: 1px solid var(--border-color);
		border-radius: 25px;
		padding: 0.75rem 1rem;
		transition: var(--transition);
	}

	.route-input:focus-within {
		border-color: var(--text-secondary);
		box-shadow: 0 0 0 3px rgba(0,0,0,0.1);
	}

	.route-input input {
		flex: 1;
		border: none;
		background: transparent;
		font-size: 0.875rem;
		color: var(--text-primary);
	}

	.route-input input::placeholder {
		color: var(--text-secondary);
	}

	.route-input i {
		font-size: 1.8rem;
		opacity: 0.5;
	}

	.route-button {
		width: 50px;
		height: 50px;
		border-radius: 50%;
		background: var(--bg-main);
		border: 2px solid var(--border-color);
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		transition: var(--transition);
		box-shadow: 0 2px 10px rgba(0,0,0,0.1);
	}

	.route-button:hover {
		transform: scale(1.05);
		border-color: var(--text-secondary);
	}

	.route-button:hover:active {
		transform: scale(0.95);
	}

	/* Tickets */
	.tickets-grid {
		display: flex;
		flex-direction: column;
		gap: 1rem;
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
		-webkit-user-select: none;
		-moz-user-select: none;
		-ms-user-select: none;
	}

	.ticket-card:hover {
		transform: translateY(4px);
		box-shadow: 0 10px 30px var(--card-shadow-light);
		border: 1px solid #3434341a;
		filter: contrast(1) brightness(1.02);
	}

	.disabled {
		cursor: default;
		pointer-events: none;
		touch-action: none;
		transition: none;
		opacity: .5;
		filter: contrast(1) brightness(1);
	}

	.disabled:hover {
		filter: contrast(1) brightness(1);
	}

	.disabled.ticket-name {
		opacity: .4;
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

	.apollo-coupon-detail {
		display: flex;
		align-items: center;
		justify-content: space-between;
		background: var(--bg-surface);
		border: 1px solid var(--border-color);
		border-radius: var(--radius-card);
		padding: 0.75rem 1rem;
		margin-top: 1rem;
		font-size: 0.875rem;
	}

	.apollo-coupon-detail span {
		margin: auto;
		text-align: center;
	}

	.apollo-coupon-detail strong {
		color: var(--text-primary);
		font-weight: 700;
	}

	.apollo-coupon-detail .ri-coupon-3-line {
		color: var(--text-secondary);
		margin-right: 0.5rem;
	}

	.copy-code-mini {
		background: var(--bg-main);
		border: 1px solid var(--border-color);
		border-radius: 6px;
		padding: 0.25rem 0.5rem;
		cursor: pointer;
		transition: var(--transition);
		display: flex;
		align-items: center;
		gap: 0.25rem;
	}

	.copy-code-mini i {
		font-size: 1.05rem;
	}

	.copy-code-mini:hover {
		background: var(--text-secondary);
	}

	/* Secondary/Final Image */
	.secondary-image {
		width: 100%;
		text-align: center;
		padding: 1.5rem;
	}

	.secondary-image img {
		width: 100%;
		max-width: 400px;
		height: auto;
		border-radius: var(--radius-card);
	}

	/* Bottom bar */
	.bottom-bar {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		background: var(--bg-main);
		border-top: 1px solid var(--border-color);
		padding: 1rem 1.5rem;
		display: flex;
		justify-content: space-between;
		align-items: center;
		z-index: 1000;
	}

	.bottom-btn {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 0.25rem;
		padding: 0.75rem 1rem;
		border-radius: var(--radius-main);
		text-decoration: none;
		transition: var(--transition);
		cursor: pointer;
		user-select: none;
		-webkit-user-select: none;
		-moz-user-select: none;
		-ms-user-select: none;
	}

	.bottom-btn:active {
		transform: scale(0.97);
	}

	.bottom-btn:hover {
		background: var(--bg-surface);
		border-color: var(--text-secondary);
	}

	.bottom-btn.primary {
		background: var(--bg-main);
		border: 2px solid var(--border-color);
		color: var(--text-primary);
	}

	.bottom-btn.primary:hover {
		background: var(--text-secondary);
		color: white;
	}

	.bottom-btn.secondary {
		background: var(--bg-main);
		border: 2px solid var(--border-color);
		color: var(--text-primary);
	}

	.bottom-btn.secondary:hover {
		background: var(--text-secondary);
		color: white;
	}

	/* Responsive */
	@media (max-width: 768px) {
		.hero-media {
		height: 60vh;
		border-radius: 0;
		}

		.hero-title {
		font-size: 1.5rem;
		}

		.hero-content {
		padding: 1.5rem;
		}

		.event-body {
		padding-bottom: 100px;
		}

		.quick-actions {
		padding: 1rem;
		}

		.section {
		padding: 1rem;
		}

		.route-controls {
		padding: 0 1rem;
		}

		.bottom-bar {
		padding: 0.75rem 1rem;
		}
	}

	/* Print hardening */
	@media print {
		html, body { background: #fff !important; }
		.bottom-bar, .quick-actions { display: none !important; }
		.mobile-container { box-shadow: none !important; }
		.hero-media { height: 320px !important; border-radius: 0 !important; }
		a[href]:after { content: "" !important; } /* prevents URL spam in print */
	}
	</style>
</head>

<body class="apollo-page apollo-print">
<?php
// OPTION 1 (recommended): include a partial that outputs ONLY the body markup (mobile-container)
// You will create/adjust this partial by extracting from the current template.
$partial = plugin_dir_path( __FILE__ ) . 'partials/event-mobile-body.php';
if ( file_exists( $partial ) ) {
	require $partial;
} else {
	echo '<pre>Missing partial: templates/partials/event-mobile-body.php</pre>';
}
?>
</body>
</html>
