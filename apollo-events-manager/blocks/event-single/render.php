<?php
/**
 * Apollo Event Single Block - Server-Side Render
 *
 * Renders a single event with full details.
 *
 * @package Apollo_Events_Manager
 * @subpackage Blocks
 * @since 2.0.0
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

declare(strict_types=1);

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Extract attributes with defaults.
$event_id       = $attributes['eventId'] ?? 0;
$show_banner    = $attributes['showBanner'] ?? true;
$show_date      = $attributes['showDate'] ?? true;
$show_location  = $attributes['showLocation'] ?? true;
$show_map       = $attributes['showMap'] ?? true;
$show_djs       = $attributes['showDJs'] ?? true;
$show_timetable = $attributes['showTimetable'] ?? true;
$show_tickets   = $attributes['showTickets'] ?? true;
$show_share     = $attributes['showShare'] ?? true;
$layout         = $attributes['layout'] ?? 'full';
$class_name     = $attributes['className'] ?? '';

// Validate event ID.
if ( empty( $event_id ) ) {
	return;
}

// Get the event.
$event = get_post( $event_id );

if ( ! $event || 'event_listing' !== $event->post_type || 'publish' !== $event->post_status ) {
	return;
}

// Get event meta.
$event_title    = get_post_meta( $event_id, '_event_title', true ) ?: $event->post_title;
$event_banner   = get_post_meta( $event_id, '_event_banner', true );
$event_video    = get_post_meta( $event_id, '_event_video_url', true );
$event_date     = get_post_meta( $event_id, '_event_start_date', true );
$event_time     = get_post_meta( $event_id, '_event_start_time', true );
$event_end_date = get_post_meta( $event_id, '_event_end_date', true );
$event_end_time = get_post_meta( $event_id, '_event_end_time', true );
$event_location = get_post_meta( $event_id, '_event_location', true );
$event_city     = get_post_meta( $event_id, '_event_city', true );
$event_address  = get_post_meta( $event_id, '_event_address', true );
$event_lat      = get_post_meta( $event_id, '_event_latitude', true ) ?: get_post_meta( $event_id, '_event_lat', true );
$event_lng      = get_post_meta( $event_id, '_event_longitude', true ) ?: get_post_meta( $event_id, '_event_lng', true );
$tickets_url    = get_post_meta( $event_id, '_tickets_ext', true );
$cupom          = get_post_meta( $event_id, '_cupom_ario', true );
$dj_ids         = get_post_meta( $event_id, '_event_dj_ids', true );
$dj_slots       = get_post_meta( $event_id, '_event_dj_slots', true );
$local_ids      = get_post_meta( $event_id, '_event_local_ids', true );
$is_featured    = get_post_meta( $event_id, '_event_featured', true );

// Get featured image if no banner.
if ( empty( $event_banner ) && has_post_thumbnail( $event_id ) ) {
	$event_banner = get_the_post_thumbnail_url( $event_id, 'full' );
}

// Format dates.
$formatted_start_date = '';
$formatted_end_date   = '';

if ( $event_date ) {
	$date_obj = DateTime::createFromFormat( 'Y-m-d', $event_date );
	if ( $date_obj ) {
		$formatted_start_date = wp_date( 'l, j \d\e F \d\e Y', $date_obj->getTimestamp() );
	}
}

if ( $event_end_date && $event_end_date !== $event_date ) {
	$end_date_obj = DateTime::createFromFormat( 'Y-m-d', $event_end_date );
	if ( $end_date_obj ) {
		$formatted_end_date = wp_date( 'l, j \d\e F \d\e Y', $end_date_obj->getTimestamp() );
	}
}

// Get DJs.
$djs = array();
if ( $show_djs && ! empty( $dj_ids ) ) {
	$dj_id_array = is_array( $dj_ids ) ? $dj_ids : explode( ',', $dj_ids );
	foreach ( $dj_id_array as $dj_id ) {
		$dj_id = (int) $dj_id;
		if ( $dj_id > 0 ) {
			$dj_post = get_post( $dj_id );
			if ( $dj_post && 'event_dj' === $dj_post->post_type ) {
				$djs[] = array(
					'id'    => $dj_id,
					'name'  => get_post_meta( $dj_id, '_dj_name', true ) ?: $dj_post->post_title,
					'image' => get_post_meta( $dj_id, '_dj_image', true ),
					'link'  => get_permalink( $dj_id ),
				);
			}
		}
	}
}

// Build wrapper classes.
$wrapper_classes = array(
	'apollo-event-single-block',
	'apollo-event-single',
	"apollo-event-single--{$layout}",
);

if ( $is_featured ) {
	$wrapper_classes[] = 'apollo-event-single--featured';
}

if ( ! empty( $class_name ) ) {
	$wrapper_classes[] = $class_name;
}

// Get block wrapper attributes.
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $wrapper_classes ),
	)
);
?>

<article <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<?php if ( $show_banner && $event_banner ) : ?>
	<div class="apollo-event-single__hero">
		<img
			src="<?php echo esc_url( $event_banner ); ?>"
			alt="<?php echo esc_attr( $event_title ); ?>"
			class="apollo-event-single__banner"
		/>
		<?php if ( $is_featured ) : ?>
			<span class="apollo-event-single__badge">
				<i class="ri-star-fill"></i>
				<?php esc_html_e( 'Destaque', 'apollo-events-manager' ); ?>
			</span>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<div class="apollo-event-single__content">
		<header class="apollo-event-single__header">
			<h1 class="apollo-event-single__title">
				<?php echo esc_html( $event_title ); ?>
			</h1>

			<?php if ( $show_date && $formatted_start_date ) : ?>
			<div class="apollo-event-single__date-info">
				<div class="apollo-event-single__date">
					<i class="ri-calendar-event-fill"></i>
					<span><?php echo esc_html( $formatted_start_date ); ?></span>
					<?php if ( $formatted_end_date ) : ?>
						<span class="apollo-event-single__date-separator">—</span>
						<span><?php echo esc_html( $formatted_end_date ); ?></span>
					<?php endif; ?>
				</div>
				<?php if ( $event_time ) : ?>
				<div class="apollo-event-single__time">
					<i class="ri-time-fill"></i>
					<span>
						<?php echo esc_html( $event_time ); ?>
						<?php if ( $event_end_time ) : ?>
							- <?php echo esc_html( $event_end_time ); ?>
						<?php endif; ?>
					</span>
				</div>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<?php if ( $show_location && ( $event_location || $event_city ) ) : ?>
			<div class="apollo-event-single__location-info">
				<div class="apollo-event-single__venue">
					<i class="ri-building-fill"></i>
					<span><?php echo esc_html( $event_location ); ?></span>
				</div>
				<?php if ( $event_address || $event_city ) : ?>
				<div class="apollo-event-single__address">
					<i class="ri-map-pin-fill"></i>
					<span>
						<?php echo esc_html( $event_address ?: $event_city ); ?>
					</span>
				</div>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</header>

		<?php if ( $event_video ) : ?>
		<div class="apollo-event-single__video">
			<?php echo wp_oembed_get( $event_video ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php endif; ?>

		<?php if ( $event->post_content ) : ?>
		<div class="apollo-event-single__description">
			<?php echo wp_kses_post( apply_filters( 'the_content', $event->post_content ) ); ?>
		</div>
		<?php endif; ?>

		<?php if ( $show_djs && ! empty( $djs ) ) : ?>
		<section class="apollo-event-single__artists">
			<h2 class="apollo-event-single__section-title">
				<i class="ri-user-star-fill"></i>
				<?php esc_html_e( 'Line-up', 'apollo-events-manager' ); ?>
			</h2>
			<div class="apollo-event-single__artists-grid">
				<?php foreach ( $djs as $dj ) : ?>
				<a href="<?php echo esc_url( $dj['link'] ); ?>" class="apollo-event-single__artist-card">
					<div class="apollo-event-single__artist-image">
						<?php if ( $dj['image'] ) : ?>
							<img src="<?php echo esc_url( $dj['image'] ); ?>" alt="<?php echo esc_attr( $dj['name'] ); ?>" />
						<?php else : ?>
							<div class="apollo-event-single__artist-placeholder">
								<i class="ri-user-3-fill"></i>
							</div>
						<?php endif; ?>
					</div>
					<span class="apollo-event-single__artist-name"><?php echo esc_html( $dj['name'] ); ?></span>
				</a>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endif; ?>

		<?php if ( $show_timetable && ! empty( $dj_slots ) && is_array( $dj_slots ) ) : ?>
		<section class="apollo-event-single__timetable">
			<h2 class="apollo-event-single__section-title">
				<i class="ri-time-fill"></i>
				<?php esc_html_e( 'Programação', 'apollo-events-manager' ); ?>
			</h2>
			<div class="apollo-event-single__timetable-list">
				<?php foreach ( $dj_slots as $slot ) : ?>
				<div class="apollo-event-single__timetable-slot">
					<span class="apollo-event-single__slot-time">
						<?php echo esc_html( $slot['start_time'] ?? '' ); ?>
						<?php if ( ! empty( $slot['end_time'] ) ) : ?>
							- <?php echo esc_html( $slot['end_time'] ); ?>
						<?php endif; ?>
					</span>
					<span class="apollo-event-single__slot-name">
						<?php echo esc_html( $slot['name'] ?? $slot['dj_name'] ?? '' ); ?>
					</span>
				</div>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endif; ?>

		<?php if ( $show_map && $event_lat && $event_lng ) : ?>
		<section class="apollo-event-single__map-section">
			<h2 class="apollo-event-single__section-title">
				<i class="ri-map-2-fill"></i>
				<?php esc_html_e( 'Localização', 'apollo-events-manager' ); ?>
			</h2>
			<div
				class="apollo-event-single__map"
				id="apollo-event-map-<?php echo esc_attr( $event_id ); ?>"
				data-lat="<?php echo esc_attr( $event_lat ); ?>"
				data-lng="<?php echo esc_attr( $event_lng ); ?>"
				data-title="<?php echo esc_attr( $event_location ?: $event_title ); ?>"
			></div>
		</section>
		<?php endif; ?>

		<?php if ( $show_tickets && $tickets_url ) : ?>
		<section class="apollo-event-single__tickets">
			<a
				href="<?php echo esc_url( $tickets_url ); ?>"
				class="apollo-btn apollo-btn--primary apollo-btn--lg apollo-btn--block"
				target="_blank"
				rel="noopener noreferrer"
			>
				<i class="ri-ticket-fill"></i>
				<?php esc_html_e( 'Comprar Ingressos', 'apollo-events-manager' ); ?>
			</a>
			<?php if ( $cupom ) : ?>
			<p class="apollo-event-single__cupom">
				<i class="ri-coupon-fill"></i>
				<?php
				printf(
					/* translators: %s: coupon code */
					esc_html__( 'Use o cupom: %s', 'apollo-events-manager' ),
					'<strong>' . esc_html( $cupom ) . '</strong>'
				);
				?>
			</p>
			<?php endif; ?>
		</section>
		<?php endif; ?>

		<?php if ( $show_share ) : ?>
		<section class="apollo-event-single__share">
			<h3 class="apollo-event-single__share-title">
				<i class="ri-share-fill"></i>
				<?php esc_html_e( 'Compartilhar', 'apollo-events-manager' ); ?>
			</h3>
			<div class="apollo-social-share">
				<?php
				$share_url   = get_permalink( $event_id );
				$share_title = rawurlencode( $event_title );
				$share_text  = rawurlencode( $event_title . ' - ' . $formatted_start_date );
				?>
				<a
					href="https://www.facebook.com/sharer/sharer.php?u=<?php echo rawurlencode( $share_url ); ?>"
					class="apollo-share-button apollo-share-button--facebook"
					target="_blank"
					rel="noopener noreferrer"
					title="<?php esc_attr_e( 'Compartilhar no Facebook', 'apollo-events-manager' ); ?>"
				>
					<i class="ri-facebook-fill"></i>
				</a>
				<a
					href="https://twitter.com/intent/tweet?url=<?php echo rawurlencode( $share_url ); ?>&text=<?php echo $share_text; ?>"
					class="apollo-share-button apollo-share-button--twitter"
					target="_blank"
					rel="noopener noreferrer"
					title="<?php esc_attr_e( 'Compartilhar no X/Twitter', 'apollo-events-manager' ); ?>"
				>
					<i class="ri-twitter-x-fill"></i>
				</a>
				<a
					href="https://api.whatsapp.com/send?text=<?php echo $share_text; ?>%20<?php echo rawurlencode( $share_url ); ?>"
					class="apollo-share-button apollo-share-button--whatsapp"
					target="_blank"
					rel="noopener noreferrer"
					title="<?php esc_attr_e( 'Compartilhar no WhatsApp', 'apollo-events-manager' ); ?>"
				>
					<i class="ri-whatsapp-fill"></i>
				</a>
				<a
					href="https://telegram.me/share/url?url=<?php echo rawurlencode( $share_url ); ?>&text=<?php echo $share_text; ?>"
					class="apollo-share-button apollo-share-button--telegram"
					target="_blank"
					rel="noopener noreferrer"
					title="<?php esc_attr_e( 'Compartilhar no Telegram', 'apollo-events-manager' ); ?>"
				>
					<i class="ri-telegram-fill"></i>
				</a>
			</div>
		</section>
		<?php endif; ?>
	</div>
</article>

<?php if ( $show_map && $event_lat && $event_lng ) : ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
	if (typeof L !== 'undefined') {
		const mapElement = document.getElementById('apollo-event-map-<?php echo esc_js( $event_id ); ?>');
		if (mapElement) {
			const lat = parseFloat(mapElement.dataset.lat);
			const lng = parseFloat(mapElement.dataset.lng);
			const title = mapElement.dataset.title;

			const map = L.map(mapElement).setView([lat, lng], 15);

			// STRICT MODE: Use central tileset provider
			if (window.ApolloMapTileset) {
				window.ApolloMapTileset.apply(map);
				window.ApolloMapTileset.ensureAttribution(map);
			} else {
				console.warn('[Apollo] ApolloMapTileset not loaded, using fallback');
				L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
					attribution: '&copy; OpenStreetMap contributors'
				}).addTo(map);
			}

			L.marker([lat, lng])
				.addTo(map)
				.bindPopup(title)
				.openPopup();
		}
	}
});
</script>
<?php endif; ?>
