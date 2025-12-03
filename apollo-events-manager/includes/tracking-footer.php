<?php
// phpcs:ignoreFile
/**
 * Auto-tracking in Footer
 *
 * Automatically tracks event views (page or modal)
 *
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Track event view in footer
 */
add_action( 'wp_footer', 'apollo_track_event_view_footer', 99 );

function apollo_track_event_view_footer() {
	// Only track on single event pages
	if ( ! is_singular( 'event_listing' ) ) {
		return;
	}

	$event_id = get_the_ID();
	if ( ! $event_id ) {
		return;
	}

	// Check if this is a modal context (will be set by JavaScript)
	$is_modal  = isset( $_GET['modal'] ) || isset( $_GET['popup'] );
	$view_type = $is_modal ? 'popup' : 'page';

	// Track view immediately
	if ( class_exists( 'Apollo_Event_Statistics' ) ) {
		Apollo_Event_Statistics::track_event_view( $event_id, $view_type );
	}

	// Also add JavaScript tracking for dynamic contexts
	?>
	<script>
	(function() {
		'use strict';
		
		// Track view on page load
		function trackPageView() {
			var eventId = <?php echo absint( $event_id ); ?>;
			var isModal = document.querySelector('[data-apollo-modal="1"]') !== null;
			var viewType = isModal ? 'popup' : 'page';
			
			if (typeof jQuery !== 'undefined' && typeof apollo_events_ajax !== 'undefined') {
				jQuery.ajax({
					url: apollo_events_ajax.ajax_url,
					type: 'POST',
					data: {
						action: 'apollo_track_event_view',
						event_id: eventId,
						type: viewType,
						nonce: apollo_events_ajax.nonce
					}
				});
			}
		}
		
		// Track on load
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', trackPageView);
		} else {
			trackPageView();
		}
		
		// Track modal views
		document.addEventListener('modal:opened', function(e) {
			if (e.detail && e.detail.eventId) {
				if (typeof jQuery !== 'undefined' && typeof apollo_events_ajax !== 'undefined') {
					jQuery.ajax({
						url: apollo_events_ajax.ajax_url,
						type: 'POST',
						data: {
							action: 'apollo_track_event_view',
							event_id: e.detail.eventId,
							type: 'popup',
							nonce: apollo_events_ajax.nonce
						}
					});
				}
			}
		});
	})();
	</script>
	<?php
}

