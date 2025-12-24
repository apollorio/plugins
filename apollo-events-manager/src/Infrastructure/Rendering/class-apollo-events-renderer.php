<?php
/**
 * Events Renderer
 *
 * Handles rendering of events
 *
 * @package Apollo_Events_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Events Renderer Class
 */
class Apollo_Events_Renderer {

	/**
	 * Initialize renderer
	 */
	public static function init() {
		add_shortcode( 'apollo_events', array( __CLASS__, 'render_events' ) );
	}

	/**
	 * Render events shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_events( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'    => 10,
				'category' => '',
			),
			$atts
		);

		$query_args = array(
			'post_type'      => 'event',
			'posts_per_page' => intval( $atts['limit'] ),
			'post_status'    => 'publish',
		);

		if ( ! empty( $atts['category'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'event_category',
					'field'    => 'slug',
					'terms'    => $atts['category'],
				),
			);
		}

		$events_query = new WP_Query( $query_args );

		if ( ! $events_query->have_posts() ) {
			return '<p>No events found.</p>';
		}

		ob_start();
		?>
		<div class="apollo-events-list">
			<?php
			while ( $events_query->have_posts() ) :
				$events_query->the_post();
				?>
				<?php
				// Use APOLLO_APRIO_PATH constant (defined in main plugin file).
				$template_path = defined( 'APOLLO_APRIO_PATH' ) ? APOLLO_APRIO_PATH : plugin_dir_path( dirname( __DIR__, 2 ) );
				include $template_path . 'templates/event-card.php';
				?>
			<?php endwhile; ?>
		</div>
		<?php
		wp_reset_postdata();

		return ob_get_clean();
	}
}

// Initialize.
Apollo_Events_Renderer::init();
