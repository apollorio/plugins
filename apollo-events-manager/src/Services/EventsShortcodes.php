<?php
/**
 * Events Shortcodes Service
 *
 * Handles all shortcode registrations for Apollo Events Manager.
 * Extracted from the main plugin class to follow SRP.
 *
 * @package Apollo\Events\Services
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo\Events\Services;

/**
 * Manages all shortcode definitions for events.
 */
final class EventsShortcodes {

	/**
	 * Register shortcodes.
	 *
	 * @return void
	 */
	public function register(): void {
		add_shortcode( 'apollo_events', array( $this, 'eventsGrid' ) );
		add_shortcode( 'apollo_eventos', array( $this, 'eventosPage' ) );
		add_shortcode( 'apollo_event', array( $this, 'eventSingle' ) );
		add_shortcode( 'apollo_event_single', array( $this, 'eventSingle' ) );
		add_shortcode( 'apollo_event_djs', array( $this, 'eventDjs' ) );
		add_shortcode( 'apollo_event_user_overview', array( $this, 'userOverview' ) );
		add_shortcode( 'apollo_user_dashboard', array( $this, 'userDashboard' ) );
		add_shortcode( 'apollo_cena_rio', array( $this, 'cenaRio' ) );
	}

	/**
	 * Render events grid.
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public function eventsGrid( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'per_page'   => 12,
				'columns'    => 3,
				'category'   => '',
				'orderby'    => 'date',
				'order'      => 'DESC',
				'show_filters' => 'true',
				'show_map'   => 'false',
			),
			$atts,
			'apollo_events'
		);

		$query_args = array(
			'post_type'      => 'event_listing',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['per_page'] ),
			'orderby'        => sanitize_key( $atts['orderby'] ),
			'order'          => strtoupper( $atts['order'] ) === 'ASC' ? 'ASC' : 'DESC',
		);

		if ( ! empty( $atts['category'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'event_listing_category',
					'field'    => 'slug',
					'terms'    => array_map( 'sanitize_title', explode( ',', $atts['category'] ) ),
				),
			);
		}

		// Filter future events only
		$query_args['meta_query'] = array(
			array(
				'key'     => '_event_start_date',
				'value'   => current_time( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'DATE',
			),
		);

		$query = new \WP_Query( $query_args );

		ob_start();

		$template_path = APOLLO_APRIO_PATH . 'templates/events-grid.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			$this->renderDefaultGrid( $query, $atts );
		}

		wp_reset_postdata();

		return ob_get_clean();
	}

	/**
	 * Render eventos page (full featured).
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public function eventosPage( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'layout'     => 'modern',
				'show_hero'  => 'true',
				'show_filters' => 'true',
				'show_map'   => 'true',
			),
			$atts,
			'apollo_eventos'
		);

		ob_start();

		$template_path = APOLLO_APRIO_PATH . 'templates/eventos-page.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}

		return ob_get_clean();
	}

	/**
	 * Render single event.
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public function eventSingle( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'id'     => 0,
				'layout' => 'full',
			),
			$atts,
			'apollo_event_single'
		);

		$event_id = absint( $atts['id'] );

		if ( ! $event_id ) {
			$event_id = get_the_ID();
		}

		if ( ! $event_id || 'event_listing' !== get_post_type( $event_id ) ) {
			return '<p class="apollo-error">' . esc_html__( 'Evento não encontrado', 'apollo-events-manager' ) . '</p>';
		}

		ob_start();

		$template_path = APOLLO_APRIO_PATH . 'templates/single-event.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}

		return ob_get_clean();
	}

	/**
	 * Render event DJs list.
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public function eventDjs( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'id'     => 0,
				'layout' => 'grid',
			),
			$atts,
			'apollo_event_djs'
		);

		$event_id = absint( $atts['id'] ) ?: get_the_ID();

		if ( ! $event_id ) {
			return '';
		}

		$djs = get_post_meta( $event_id, '_event_djs', true );

		if ( empty( $djs ) || ! is_array( $djs ) ) {
			return '';
		}

		ob_start();

		$template_path = APOLLO_APRIO_PATH . 'templates/event-djs.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			$this->renderDefaultDjs( $djs, $atts['layout'] );
		}

		return ob_get_clean();
	}

	/**
	 * Render user overview.
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public function userOverview( array $atts = array() ): string {
		if ( ! is_user_logged_in() ) {
			return '<p class="apollo-notice">' . esc_html__( 'Faça login para ver seu perfil', 'apollo-events-manager' ) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'user_id' => get_current_user_id(),
				'layout'  => 'default',
			),
			$atts,
			'apollo_event_user_overview'
		);

		ob_start();

		$template_path = APOLLO_APRIO_PATH . 'templates/user-overview.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}

		return ob_get_clean();
	}

	/**
	 * Render user dashboard.
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public function userDashboard( array $atts = array() ): string {
		if ( ! is_user_logged_in() ) {
			return '<p class="apollo-notice">' . esc_html__( 'Faça login para acessar o dashboard', 'apollo-events-manager' ) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'layout' => 'tabs',
			),
			$atts,
			'apollo_user_dashboard'
		);

		ob_start();

		$template_path = APOLLO_APRIO_PATH . 'templates/user-dashboard.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}

		return ob_get_clean();
	}

	/**
	 * Render Cena Rio shortcode.
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public function cenaRio( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'mode' => 'default',
			),
			$atts,
			'apollo_cena_rio'
		);

		ob_start();

		$template_path = APOLLO_APRIO_PATH . 'templates/cena-rio.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}

		return ob_get_clean();
	}

	/**
	 * Render default grid when no template exists.
	 *
	 * @param \WP_Query            $query Query object.
	 * @param array<string, mixed> $atts  Shortcode attributes.
	 * @return void
	 */
	private function renderDefaultGrid( \WP_Query $query, array $atts ): void {
		$columns = absint( $atts['columns'] );
		?>
		<div class="apollo-events-grid" style="display: grid; grid-template-columns: repeat(<?php echo esc_attr( (string) $columns ); ?>, 1fr); gap: 1.5rem;">
			<?php if ( $query->have_posts() ) : ?>
				<?php while ( $query->have_posts() ) : $query->the_post(); ?>
					<article class="apollo-event-card">
						<?php if ( has_post_thumbnail() ) : ?>
							<div class="apollo-event-thumbnail">
								<?php the_post_thumbnail( 'medium' ); ?>
							</div>
						<?php endif; ?>
						<div class="apollo-event-content">
							<h3 class="apollo-event-title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h3>
							<?php
							$start_date = get_post_meta( get_the_ID(), '_event_start_date', true );
							if ( $start_date ) :
								?>
								<time class="apollo-event-date" datetime="<?php echo esc_attr( $start_date ); ?>">
									<?php echo esc_html( wp_date( 'd/m/Y', strtotime( $start_date ) ) ); ?>
								</time>
							<?php endif; ?>
							<?php
							$location = get_post_meta( get_the_ID(), '_event_location', true );
							if ( $location ) :
								?>
								<p class="apollo-event-location"><?php echo esc_html( $location ); ?></p>
							<?php endif; ?>
						</div>
					</article>
				<?php endwhile; ?>
			<?php else : ?>
				<p class="apollo-no-events"><?php esc_html_e( 'Nenhum evento encontrado', 'apollo-events-manager' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render default DJs list.
	 *
	 * @param array<mixed> $djs    DJs data.
	 * @param string       $layout Layout type.
	 * @return void
	 */
	private function renderDefaultDjs( array $djs, string $layout ): void {
		?>
		<div class="apollo-event-djs apollo-djs-<?php echo esc_attr( $layout ); ?>">
			<h3><?php esc_html_e( 'Line-up', 'apollo-events-manager' ); ?></h3>
			<ul class="apollo-djs-list">
				<?php foreach ( $djs as $dj ) : ?>
					<li class="apollo-dj-item">
						<?php
						$name = is_array( $dj ) ? ( $dj['name'] ?? '' ) : $dj;
						echo esc_html( $name );
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}
