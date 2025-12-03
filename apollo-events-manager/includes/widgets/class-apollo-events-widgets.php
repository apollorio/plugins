<?php
// phpcs:ignoreFile
/**
 * Apollo Events Manager - Widgets
 *
 * Organized widget system with analytics integration
 * Migrated from wp-event-manager with Apollo updates
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Apollo Events Widget Base Class
 */
class Apollo_Events_Widget extends WP_Widget {

	public $widget_cssclass;
	public $widget_description;
	public $widget_id;
	public $widget_name;
	public $settings;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->register();
	}

	/**
	 * Register Widget
	 */
	public function register() {
		$widget_ops = array(
			'classname'   => $this->widget_cssclass,
			'description' => $this->widget_description,
		);

		parent::__construct( $this->widget_id, $this->widget_name, $widget_ops );

		add_action( 'save_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( $this, 'flush_widget_cache' ) );
	}

	/**
	 * Get cached widget
	 */
	function get_cached_widget( $args ) {
		$cache = wp_cache_get( $this->widget_id, 'widget' );

		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo wp_kses_post( $cache[ $args['widget_id'] ] );
			return true;
		}

		return false;
	}

	/**
	 * Cache the widget
	 */
	public function cache_widget( $args, $content ) {
		$cache[ $args['widget_id'] ] = $content;
		wp_cache_set( $this->widget_id, $cache, 'widget' );
	}

	/**
	 * Flush the cache
	 */
	public function flush_widget_cache() {
		wp_cache_delete( $this->widget_id, 'widget' );
	}

	/**
	 * Update widget
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		if ( ! $this->settings ) {
			return $instance;
		}

		foreach ( $this->settings as $key => $setting ) {
			$instance[ $key ] = sanitize_text_field( $new_instance[ $key ] );
		}

		$this->flush_widget_cache();
		return $instance;
	}

	/**
	 * Form
	 */
	function form( $instance ) {
		if ( ! $this->settings ) {
			return;
		}

		foreach ( $this->settings as $key => $setting ) {
			$value = isset( $instance[ $key ] ) ? $instance[ $key ] : $setting['std'];

			switch ( $setting['type'] ) {
				case 'text':
					?>
					<p>
						<label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>">
							<?php echo esc_html( $setting['label'] ); ?>
						</label>
						<input class="widefat" 
								id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" 
								name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>" 
								type="text" 
								value="<?php echo esc_attr( $value ); ?>" />
					</p>
					<?php
					break;

				case 'number':
					?>
					<p>
						<label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>">
							<?php echo esc_html( $setting['label'] ); ?>
						</label>
						<input class="widefat" 
								id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" 
								name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>" 
								type="number" 
								step="<?php echo esc_attr( $setting['step'] ); ?>" 
								min="<?php echo esc_attr( $setting['min'] ); ?>" 
								max="<?php echo esc_attr( $setting['max'] ); ?>" 
								value="<?php echo esc_attr( $value ); ?>" />
					</p>
					<?php
					break;

				case 'select':
					?>
					<p>
						<label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>">
							<?php echo esc_html( $setting['label'] ); ?>
						</label>
						<select class="widefat" 
								id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" 
								name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>">
							<?php
							if ( isset( $setting['options'] ) ) {
								foreach ( $setting['options'] as $option_key => $option_value ) {
									?>
									<option value="<?php echo esc_attr( $option_key ); ?>" 
											<?php selected( $option_key, $value ); ?>>
										<?php echo esc_html( $option_value ); ?>
									</option>
									<?php
								}
							}
							?>
						</select>
					</p>
					<?php
					break;
			}//end switch
		}//end foreach
	}
}

/**
 * Recent Events Widget
 */
class Apollo_Events_Widget_Recent_Events extends Apollo_Events_Widget {

	public function __construct() {
		$this->widget_cssclass    = 'apollo_events widget_recent_events';
		$this->widget_description = __( 'Display a list of recent events on your site.', 'apollo-events-manager' );
		$this->widget_id          = 'apollo_widget_recent_events';
		$this->widget_name        = __( 'Recent Events', 'apollo-events-manager' );

		$this->settings = array(
			'title'    => array(
				'type'  => 'text',
				'std'   => __( 'Recent Events', 'apollo-events-manager' ),
				'label' => __( 'Title', 'apollo-events-manager' ),
			),
			'keyword'  => array(
				'type'  => 'text',
				'std'   => '',
				'label' => __( 'Keyword', 'apollo-events-manager' ),
			),
			'location' => array(
				'type'  => 'text',
				'std'   => '',
				'label' => __( 'Location', 'apollo-events-manager' ),
			),
			'number'   => array(
				'type'  => 'number',
				'step'  => 1,
				'min'   => 1,
				'max'   => 50,
				'std'   => 5,
				'label' => __( 'Number of events to show', 'apollo-events-manager' ),
			),
			'order'    => array(
				'type'    => 'select',
				'std'     => 'DESC',
				'label'   => __( 'Order by', 'apollo-events-manager' ),
				'options' => array(
					'ASC'  => __( 'Ascending (ASC)', 'apollo-events-manager' ),
					'DESC' => __( 'Descending (DESC)', 'apollo-events-manager' ),
				),
			),
		);

		$this->register();
	}

	public function widget( $args, $instance ) {
		if ( $this->get_cached_widget( $args ) ) {
			return;
		}

		ob_start();
		extract( $args );

		$title  = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;

		$query_args = array(
			'post_type'      => 'event_listing',
			'post_status'    => 'publish',
			'posts_per_page' => $number,
			'orderby'        => 'date',
			'order'          => isset( $instance['order'] ) ? $instance['order'] : 'DESC',
		);

		// Keyword filter
		if ( ! empty( $instance['keyword'] ) ) {
			$query_args['s'] = sanitize_text_field( $instance['keyword'] );
		}

		// Location filter
		if ( ! empty( $instance['location'] ) ) {
			$query_args['meta_query'][] = array(
				'key'     => '_event_location',
				'value'   => sanitize_text_field( $instance['location'] ),
				'compare' => 'LIKE',
			);
		}

		$events = new WP_Query( $query_args );

		echo wp_kses_post( $before_widget );

		if ( $title ) {
			echo wp_kses_post( $before_title . $title . $after_title );
		}

		if ( $events->have_posts() ) :
			?>
			<ul class="apollo-event-list-widget">
				<?php
				while ( $events->have_posts() ) :
					$events->the_post();
					$event_id = get_the_ID();

					// Track view
					if ( function_exists( 'apollo_record_event_view' ) ) {
						apollo_record_event_view( $event_id );
					}

					$views      = function_exists( 'apollo_get_event_views' ) ? apollo_get_event_views( $event_id ) : 0;
					$location   = get_post_meta( $event_id, '_event_location', true );
					$start_date = get_post_meta( $event_id, '_event_start_date', true );
					?>
					<li class="apollo-event-widget-item">
						<a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>">
							<strong><?php the_title(); ?></strong>
						</a>
						<?php if ( $location ) : ?>
							<div class="event-location">
								<i class="ri-map-pin-2-line"></i> <?php echo esc_html( $location ); ?>
							</div>
						<?php endif; ?>
						<?php if ( $start_date ) : ?>
							<div class="event-date">
								<i class="ri-calendar-event-line"></i> 
								<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $start_date ) ) ); ?>
							</div>
						<?php endif; ?>
						<?php if ( $views > 0 ) : ?>
							<div class="event-views">
								<i class="ri-eye-line"></i> <?php echo esc_html( $views ); ?> 
								<?php esc_html_e( 'views', 'apollo-events-manager' ); ?>
							</div>
						<?php endif; ?>
					</li>
				<?php endwhile; ?>
			</ul>
		<?php else : ?>
			<p class="apollo-no-events">
				<?php esc_html_e( 'No events found.', 'apollo-events-manager' ); ?>
			</p>
			<?php
		endif;

		echo wp_kses_post( $after_widget );

		wp_reset_postdata();

		$content = ob_get_clean();
		echo wp_kses_post( $content );

		$this->cache_widget( $args, $content );
	}
}

/**
 * Featured Events Widget
 */
class Apollo_Events_Widget_Featured_Events extends Apollo_Events_Widget {

	public function __construct() {
		$this->widget_cssclass    = 'apollo_events widget_featured_events';
		$this->widget_description = __( 'Display a list of featured events on your site.', 'apollo-events-manager' );
		$this->widget_id          = 'apollo_widget_featured_events';
		$this->widget_name        = __( 'Featured Events', 'apollo-events-manager' );

		$this->settings = array(
			'title'  => array(
				'type'  => 'text',
				'std'   => __( 'Featured Events', 'apollo-events-manager' ),
				'label' => __( 'Title', 'apollo-events-manager' ),
			),
			'number' => array(
				'type'  => 'number',
				'step'  => 1,
				'min'   => 1,
				'max'   => 50,
				'std'   => 5,
				'label' => __( 'Number of events to show', 'apollo-events-manager' ),
			),
			'order'  => array(
				'type'    => 'select',
				'std'     => 'DESC',
				'label'   => __( 'Order by', 'apollo-events-manager' ),
				'options' => array(
					'ASC'  => __( 'Ascending (ASC)', 'apollo-events-manager' ),
					'DESC' => __( 'Descending (DESC)', 'apollo-events-manager' ),
				),
			),
		);

		$this->register();
	}

	public function widget( $args, $instance ) {
		if ( $this->get_cached_widget( $args ) ) {
			return;
		}

		ob_start();
		extract( $args );

		$title  = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;

		$query_args = array(
			'post_type'      => 'event_listing',
			'post_status'    => 'publish',
			'posts_per_page' => $number,
			'orderby'        => 'date',
			'order'          => isset( $instance['order'] ) ? $instance['order'] : 'DESC',
			'meta_query'     => array(
				array(
					'key'     => '_featured',
					'value'   => '1',
					'compare' => '=',
				),
			),
		);

		$events = new WP_Query( $query_args );

		echo wp_kses_post( $before_widget );

		if ( $title ) {
			echo wp_kses_post( $before_title . $title . $after_title );
		}

		if ( $events->have_posts() ) :
			?>
			<ul class="apollo-event-list-widget">
				<?php
				while ( $events->have_posts() ) :
					$events->the_post();
					$event_id = get_the_ID();

					// Track view
					if ( function_exists( 'apollo_record_event_view' ) ) {
						apollo_record_event_view( $event_id );
					}

					$views = function_exists( 'apollo_get_event_views' ) ? apollo_get_event_views( $event_id ) : 0;
					?>
					<li class="apollo-event-widget-item featured">
						<a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>">
							<strong><?php the_title(); ?></strong>
							<span class="featured-badge">
								<i class="ri-star-fill"></i> <?php esc_html_e( 'Featured', 'apollo-events-manager' ); ?>
							</span>
						</a>
						<?php if ( $views > 0 ) : ?>
							<div class="event-views">
								<i class="ri-eye-line"></i> <?php echo esc_html( $views ); ?> 
								<?php esc_html_e( 'views', 'apollo-events-manager' ); ?>
							</div>
						<?php endif; ?>
					</li>
				<?php endwhile; ?>
			</ul>
		<?php else : ?>
			<p class="apollo-no-events">
				<?php esc_html_e( 'No featured events found.', 'apollo-events-manager' ); ?>
			</p>
			<?php
		endif;

		echo wp_kses_post( $after_widget );

		wp_reset_postdata();

		$content = ob_get_clean();
		echo wp_kses_post( $content );

		$this->cache_widget( $args, $content );
	}
}

/**
 * Upcoming Events Widget
 */
class Apollo_Events_Widget_Upcoming_Events extends Apollo_Events_Widget {

	public function __construct() {
		$this->widget_cssclass    = 'apollo_events widget_upcoming_events';
		$this->widget_description = __( 'Display a list of upcoming events on your site.', 'apollo-events-manager' );
		$this->widget_id          = 'apollo_widget_upcoming_events';
		$this->widget_name        = __( 'Upcoming Events', 'apollo-events-manager' );

		$this->settings = array(
			'title'  => array(
				'type'  => 'text',
				'std'   => __( 'Upcoming Events', 'apollo-events-manager' ),
				'label' => __( 'Title', 'apollo-events-manager' ),
			),
			'number' => array(
				'type'  => 'number',
				'step'  => 1,
				'min'   => 1,
				'max'   => 50,
				'std'   => 5,
				'label' => __( 'Number of events to show', 'apollo-events-manager' ),
			),
			'order'  => array(
				'type'    => 'select',
				'std'     => 'ASC',
				'label'   => __( 'Order', 'apollo-events-manager' ),
				'options' => array(
					'ASC'  => __( 'Ascending (ASC)', 'apollo-events-manager' ),
					'DESC' => __( 'Descending (DESC)', 'apollo-events-manager' ),
				),
			),
		);

		$this->register();
	}

	public function widget( $args, $instance ) {
		if ( $this->get_cached_widget( $args ) ) {
			return;
		}

		ob_start();
		extract( $args );

		$title  = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$today  = current_time( 'Y-m-d' );

		$query_args = array(
			'post_type'      => 'event_listing',
			'post_status'    => 'publish',
			'posts_per_page' => $number,
			'orderby'        => 'meta_value',
			'meta_key'       => '_event_start_date',
			'meta_type'      => 'DATETIME',
			'order'          => isset( $instance['order'] ) ? $instance['order'] : 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_event_start_date',
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				),
				array(
					'key'     => '_cancelled',
					'value'   => '1',
					'compare' => '!=',
				),
			),
		);

		$events = new WP_Query( $query_args );

		echo wp_kses_post( $before_widget );

		if ( $title ) {
			echo wp_kses_post( $before_title . $title . $after_title );
		}

		if ( $events->have_posts() ) :
			?>
			<ul class="apollo-event-list-widget">
				<?php
				while ( $events->have_posts() ) :
					$events->the_post();
					$event_id = get_the_ID();

					// Track view
					if ( function_exists( 'apollo_record_event_view' ) ) {
						apollo_record_event_view( $event_id );
					}

					$views      = function_exists( 'apollo_get_event_views' ) ? apollo_get_event_views( $event_id ) : 0;
					$start_date = get_post_meta( $event_id, '_event_start_date', true );
					?>
					<li class="apollo-event-widget-item upcoming">
						<a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>">
							<strong><?php the_title(); ?></strong>
						</a>
						<?php if ( $start_date ) : ?>
							<div class="event-date">
								<i class="ri-calendar-event-line"></i> 
								<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $start_date ) ) ); ?>
							</div>
						<?php endif; ?>
						<?php if ( $views > 0 ) : ?>
							<div class="event-views">
								<i class="ri-eye-line"></i> <?php echo esc_html( $views ); ?> 
								<?php esc_html_e( 'views', 'apollo-events-manager' ); ?>
							</div>
						<?php endif; ?>
					</li>
				<?php endwhile; ?>
			</ul>
		<?php else : ?>
			<p class="apollo-no-events">
				<?php esc_html_e( 'No upcoming events found.', 'apollo-events-manager' ); ?>
			</p>
			<?php
		endif;

		echo wp_kses_post( $after_widget );

		wp_reset_postdata();

		$content = ob_get_clean();
		echo wp_kses_post( $content );

		$this->cache_widget( $args, $content );
	}
}

/**
 * Past Events Widget
 */
class Apollo_Events_Widget_Past_Events extends Apollo_Events_Widget {

	public function __construct() {
		$this->widget_cssclass    = 'apollo_events widget_past_events';
		$this->widget_description = __( 'Display a list of past events on your site.', 'apollo-events-manager' );
		$this->widget_id          = 'apollo_widget_past_events';
		$this->widget_name        = __( 'Past Events', 'apollo-events-manager' );

		$this->settings = array(
			'title'  => array(
				'type'  => 'text',
				'std'   => __( 'Past Events', 'apollo-events-manager' ),
				'label' => __( 'Title', 'apollo-events-manager' ),
			),
			'number' => array(
				'type'  => 'number',
				'step'  => 1,
				'min'   => 1,
				'max'   => 50,
				'std'   => 5,
				'label' => __( 'Number of events to show', 'apollo-events-manager' ),
			),
			'order'  => array(
				'type'    => 'select',
				'std'     => 'DESC',
				'label'   => __( 'Order', 'apollo-events-manager' ),
				'options' => array(
					'ASC'  => __( 'Ascending (ASC)', 'apollo-events-manager' ),
					'DESC' => __( 'Descending (DESC)', 'apollo-events-manager' ),
				),
			),
		);

		$this->register();
	}

	public function widget( $args, $instance ) {
		if ( $this->get_cached_widget( $args ) ) {
			return;
		}

		ob_start();
		extract( $args );

		$title  = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$today  = current_time( 'Y-m-d' );

		$query_args = array(
			'post_type'      => 'event_listing',
			'post_status'    => array( 'publish', 'expired' ),
			'posts_per_page' => $number,
			'orderby'        => 'meta_value',
			'meta_key'       => '_event_start_date',
			'meta_type'      => 'DATETIME',
			'order'          => isset( $instance['order'] ) ? $instance['order'] : 'DESC',
			'meta_query'     => array(
				array(
					'key'     => '_event_start_date',
					'value'   => $today,
					'compare' => '<',
					'type'    => 'DATE',
				),
				array(
					'key'     => '_cancelled',
					'value'   => '1',
					'compare' => '!=',
				),
			),
		);

		$events = new WP_Query( $query_args );

		echo wp_kses_post( $before_widget );

		if ( $title ) {
			echo wp_kses_post( $before_title . $title . $after_title );
		}

		if ( $events->have_posts() ) :
			?>
			<ul class="apollo-event-list-widget">
				<?php
				while ( $events->have_posts() ) :
					$events->the_post();
					$event_id   = get_the_ID();
					$views      = function_exists( 'apollo_get_event_views' ) ? apollo_get_event_views( $event_id ) : 0;
					$start_date = get_post_meta( $event_id, '_event_start_date', true );
					?>
					<li class="apollo-event-widget-item past">
						<a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>">
							<strong><?php the_title(); ?></strong>
						</a>
						<?php if ( $start_date ) : ?>
							<div class="event-date">
								<i class="ri-calendar-event-line"></i> 
								<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $start_date ) ) ); ?>
							</div>
						<?php endif; ?>
						<?php if ( $views > 0 ) : ?>
							<div class="event-views">
								<i class="ri-eye-line"></i> <?php echo esc_html( $views ); ?> 
								<?php esc_html_e( 'views', 'apollo-events-manager' ); ?>
							</div>
						<?php endif; ?>
					</li>
				<?php endwhile; ?>
			</ul>
		<?php else : ?>
			<p class="apollo-no-events">
				<?php esc_html_e( 'No past events found.', 'apollo-events-manager' ); ?>
			</p>
			<?php
		endif;

		echo wp_kses_post( $after_widget );

		wp_reset_postdata();

		$content = ob_get_clean();
		echo wp_kses_post( $content );

		$this->cache_widget( $args, $content );
	}
}

/**
 * Register all widgets
 */
function apollo_register_event_widgets() {
	register_widget( 'Apollo_Events_Widget_Recent_Events' );
	register_widget( 'Apollo_Events_Widget_Featured_Events' );
	register_widget( 'Apollo_Events_Widget_Upcoming_Events' );
	register_widget( 'Apollo_Events_Widget_Past_Events' );
}
add_action( 'widgets_init', 'apollo_register_event_widgets' );

