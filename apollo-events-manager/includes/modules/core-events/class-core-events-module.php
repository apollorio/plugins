<?php
/**
 * Core Events Module
 *
 * The base module that provides core event functionality.
 * This module is always enabled and cannot be deactivated.
 *
 * @package Apollo_Events_Manager
 * @subpackage Modules
 * @since 1.0.0
 */

declare( strict_types = 1 );

namespace Apollo\Events\Modules;

use Apollo\Events\Core\Abstract_Module;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Core_Events_Module
 *
 * Provides core event listing functionality.
 *
 * @since 1.0.0
 */
class Core_Events_Module extends Abstract_Module {

	/**
	 * Module version.
	 *
	 * @var string
	 */
	protected string $version = '1.0.0';

	/**
	 * Default enabled status.
	 *
	 * @var bool
	 */
	protected bool $default_enabled = true;

	/**
	 * Get the module ID.
	 *
	 * @since 1.0.0
	 *
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return 'core-events';
	}

	/**
	 * Get the module name.
	 *
	 * @since 1.0.0
	 *
	 * @return string Module name.
	 */
	public function get_name(): string {
		return __( 'Core Events', 'apollo-events-manager' );
	}

	/**
	 * Get the module description.
	 *
	 * @since 1.0.0
	 *
	 * @return string Module description.
	 */
	public function get_description(): string {
		return __( 'Funcionalidade base de eventos: CPT, taxonomias, metaboxes e templates.', 'apollo-events-manager' );
	}

	/**
	 * Initialize the module.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		// Core events functionality is loaded by the main plugin file.
		// This module just ensures it's registered in the system.
		$this->log( 'Core Events module initialized.' );
	}

	/**
	 * Register shortcodes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_shortcodes(): void {
		add_shortcode( 'apollo_event_single', array( $this, 'render_event_single' ) );
	}

	/**
	 * Render events list shortcode.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 *
	 * @return string Rendered HTML.
	 */
	public function render_events_list( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'limit'    => 10,
				'category' => '',
				'orderby'  => 'date',
				'order'    => 'DESC',
				'layout'   => 'grid',
			),
			$atts,
			'apollo_events_list'
		);

		$args = array(
			'post_type'      => 'event_listing',
			'posts_per_page' => absint( $atts['limit'] ),
			'orderby'        => sanitize_key( $atts['orderby'] ),
			'order'          => 'ASC' === strtoupper( $atts['order'] ) ? 'ASC' : 'DESC',
			'post_status'    => 'publish',
		);

		if ( ! empty( $atts['category'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'event_listing_category',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $atts['category'] ),
				),
			);
		}

		$query = new \WP_Query( $args );

		ob_start();

		if ( $query->have_posts() ) {
			$layout_class = 'grid' === $atts['layout'] ? 'apollo-events-grid' : 'apollo-events-list';
			echo '<div class="apollo-events-container ' . esc_attr( $layout_class ) . '">';

			while ( $query->have_posts() ) {
				$query->the_post();
				$this->render_event_card( get_the_ID() );
			}

			echo '</div>';
			wp_reset_postdata();
		} else {
			echo '<p class="apollo-no-events">' . esc_html__( 'Nenhum evento encontrado.', 'apollo-events-manager' ) . '</p>';
		}

		return ob_get_clean();
	}

	/**
	 * Render a single event card.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 *
	 * @return void
	 */
	private function render_event_card( int $event_id ): void {
		$title     = get_the_title( $event_id );
		$permalink = get_permalink( $event_id );
		$thumbnail = get_the_post_thumbnail_url( $event_id, 'medium' );
		$excerpt   = get_the_excerpt( $event_id );

		// Get event meta.
		$start_date = get_post_meta( $event_id, '_event_start_date', true );
		$location   = get_post_meta( $event_id, '_event_location', true );

		?>
		<article class="apollo-event-card" data-event-id="<?php echo esc_attr( $event_id ); ?>">
			<?php if ( $thumbnail ) : ?>
				<div class="event-thumbnail">
					<a href="<?php echo esc_url( $permalink ); ?>">
						<img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
					</a>
				</div>
			<?php endif; ?>

			<div class="event-content">
				<h3 class="event-title">
					<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
				</h3>

				<?php if ( $start_date ) : ?>
					<div class="event-date">
						<span class="dashicons dashicons-calendar-alt"></span>
						<?php echo esc_html( wp_date( 'd/m/Y H:i', strtotime( $start_date ) ) ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $location ) : ?>
					<div class="event-location">
						<span class="dashicons dashicons-location"></span>
						<?php echo esc_html( $location ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $excerpt ) : ?>
					<div class="event-excerpt">
						<?php echo wp_kses_post( $excerpt ); ?>
					</div>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}

	/**
	 * Render single event shortcode.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 *
	 * @return string Rendered HTML.
	 */
	public function render_event_single( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'apollo_event_single'
		);

		$event_id = absint( $atts['id'] );

		if ( 0 === $event_id ) {
			return '<p class="apollo-error">' . esc_html__( 'ID do evento não especificado.', 'apollo-events-manager' ) . '</p>';
		}

		$event = get_post( $event_id );

		if ( ! $event || 'event_listing' !== $event->post_type ) {
			return '<p class="apollo-error">' . esc_html__( 'Evento não encontrado.', 'apollo-events-manager' ) . '</p>';
		}

		ob_start();
		$this->render_event_card( $event_id );
		return ob_get_clean();
	}

	/**
	 * Register assets.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_assets(): void {
		wp_register_style(
			'apollo-core-events',
			APOLLO_APRIO_URL . 'assets/css/modules/core-events.css',
			array(),
			$this->version
		);

		wp_register_script(
			'apollo-core-events',
			APOLLO_APRIO_URL . 'assets/js/modules/core-events.js',
			array( 'jquery' ),
			$this->version,
			true
		);
	}

	/**
	 * Enqueue assets.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Context (frontend/admin).
	 *
	 * @return void
	 */
	public function enqueue_assets( string $context ): void {
		if ( 'frontend' === $context ) {
			wp_enqueue_style( 'apollo-core-events' );
			wp_enqueue_script( 'apollo-core-events' );
		}
	}

	/**
	 * Get settings schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Settings schema.
	 */
	public function get_settings_schema(): array {
		return array(
			'events_per_page' => array(
				'type'        => 'number',
				'label'       => __( 'Eventos por página', 'apollo-events-manager' ),
				'default'     => 12,
				'description' => __( 'Número de eventos exibidos por página nas listagens.', 'apollo-events-manager' ),
			),
			'default_layout'  => array(
				'type'    => 'select',
				'label'   => __( 'Layout padrão', 'apollo-events-manager' ),
				'default' => 'grid',
				'options' => array(
					'grid' => __( 'Grade', 'apollo-events-manager' ),
					'list' => __( 'Lista', 'apollo-events-manager' ),
				),
			),
		);
	}
}
