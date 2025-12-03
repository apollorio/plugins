<?php
// phpcs:ignoreFile
/**
 * AJAX Handlers for Apollo Events Manager
 * Handles modal loading and other AJAX requests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'helpers/event-data-helper.php';

/**
 * Register AJAX handlers
 * NOTE: Main handler is in apollo-events-manager.php (ajax_get_event_modal)
 * This handler is kept for backward compatibility but redirects to main handler
 */
add_action( 'wp_ajax_apollo_load_event_modal', 'apollo_ajax_load_event_modal' );
add_action( 'wp_ajax_nopriv_apollo_load_event_modal', 'apollo_ajax_load_event_modal' );

/**
 * AJAX Handler: Load event modal content (Legacy - redirects to main handler)
 * Returns complete HTML for the lightbox modal
 *
 * @deprecated Use apollo_get_event_modal action instead
 */
function apollo_ajax_load_event_modal() {
	// TEMP: Xdebug breakpoint para depuração Apollo.
	if ( function_exists( 'xdebug_break' ) ) {
		xdebug_break();
	}

	try {
		// Verify nonce (standardized)
		check_ajax_referer( 'apollo_events_nonce', 'nonce' );

		// Validate event ID
		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
		if ( ! $event_id ) {
			wp_send_json_error( array( 'message' => 'ID inválido' ) );
			return;
		}

		// Verify event exists
		$event = get_post( $event_id );
		if ( ! $event || $event->post_type !== 'event_listing' ) {
			wp_send_json_error( array( 'message' => 'Evento não encontrado' ) );
			return;
		}

		// Get event data via helper
		$start_date = get_post_meta( $event_id, '_event_start_date', true );
		$date_info  = apollo_eve_parse_start_date( $start_date );

		// Use helper for DJs, local, and banner
		$djs_names  = Apollo_Event_Data_Helper::get_dj_lineup( $event_id );
		$local      = Apollo_Event_Data_Helper::get_local_data( $event_id );
		$banner_url = Apollo_Event_Data_Helper::get_banner_url( $event_id );

		// Format DJ display
		$dj_display = Apollo_Event_Data_Helper::format_dj_display( $djs_names, 6 );

		// Process location
		$event_location      = '';
		$event_location_area = '';
		if ( $local ) {
			$event_location = $local['name'];
			if ( $local['region'] ) {
				$event_location_area = $local['region'];
			}
		}

		// Fallback to _event_location if no local found
		if ( empty( $event_location ) ) {
			$event_location = get_post_meta( $event_id, '_event_location', true );
		}

		// Get event content
		$content = apply_filters( 'the_content', $event->post_content );

		// Build modal HTML
		ob_start();
		?>
	<div class="apollo-event-modal-overlay" data-apollo-close></div>
	<div class="apollo-event-modal-content" role="dialog" aria-modal="true" aria-labelledby="modal-title-<?php echo esc_attr( $event_id ); ?>">

		<button class="apollo-event-modal-close" type="button" data-apollo-close aria-label="Fechar">
			<i class="ri-close-line"></i>
		</button>

		<div class="apollo-event-hero">
			<div class="apollo-event-hero-media">
				<img src="<?php echo esc_url( $banner_url ); ?>" alt="<?php echo esc_attr( $event->post_title ); ?>" loading="lazy">
				<div class="apollo-event-date-chip">
					<span class="d"><?php echo esc_html( $date_info['day'] ); ?></span>
					<span class="m"><?php echo esc_html( $date_info['month_pt'] ); ?></span>
				</div>
			</div>

			<div class="apollo-event-hero-info">
				<h1 class="apollo-event-title" id="modal-title-<?php echo esc_attr( $event_id ); ?>">
					<?php echo esc_html( $event->post_title ); ?>
				</h1>
				<p class="apollo-event-djs">
					<i class="ri-sound-module-fill"></i>
					<span><?php echo wp_kses_post( $dj_display ); ?></span>
				</p>
				<?php if ( ! empty( $event_location ) ) : ?>
				<p class="apollo-event-location">
					<i class="ri-map-pin-2-line"></i>
					<span class="event-location-name"><?php echo esc_html( $event_location ); ?></span>
					<?php if ( ! empty( $event_location_area ) ) : ?>
						<span class="event-location-area">(<?php echo esc_html( $event_location_area ); ?>)</span>
					<?php endif; ?>
				</p>
				<?php endif; ?>
			</div>
		</div>

		<div class="apollo-event-body">
			<?php echo wp_kses_post( $content ); ?>
		</div>

	</div>
		<?php
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );

	} catch ( Exception $e ) {
		// Log error in debug mode
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'APOLLO_PORTAL_DEBUG' ) && APOLLO_PORTAL_DEBUG ) {
			error_log( 'Apollo Events: Error in apollo_ajax_load_event_modal - ' . $e->getMessage() );
		}

		// Return graceful error
		wp_send_json_error(
			array(
				'message' => 'Erro ao carregar evento. Tente novamente mais tarde.',
			)
		);
	}//end try
}

