<?php
/**
 * Apollo Event Calendar Elementor Widget
 *
 * Displays events in a monthly calendar view.
 *
 * @package Apollo_Events_Manager
 * @subpackage Elementor\Widgets
 * @since 2.0.0
 */

declare(strict_types=1);

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure dependencies are loaded.
if ( ! class_exists( 'Apollo_Base_Widget' ) ) {
	return;
}

/**
 * Class Apollo_Event_Calendar_Widget
 *
 * Elementor widget for displaying events in calendar format.
 */
class Apollo_Event_Calendar_Widget extends Apollo_Base_Widget {

	/**
	 * Widget category.
	 *
	 * @var string
	 */
	protected string $widget_category = 'apollo-events';

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name(): string {
		return 'apollo-event-calendar';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title(): string {
		return esc_html__( 'Calendário de Eventos', 'apollo-events-manager' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon(): string {
		return 'eicon-calendar';
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords(): array {
		return array( 'apollo', 'event', 'calendar', 'calendário', 'eventos', 'agenda' );
	}

	/**
	 * Get script dependencies.
	 *
	 * @return array Script dependencies.
	 */
	public function get_script_depends(): array {
		return array( 'apollo-calendar' );
	}

	/**
	 * Get style dependencies.
	 *
	 * @return array Style dependencies.
	 */
	public function get_style_depends(): array {
		return array( 'apollo-calendar' );
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_controls(): void {
		// Layout Section.
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => esc_html__( 'Layout', 'apollo-events-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'view',
			array(
				'label'   => esc_html__( 'Visualização', 'apollo-events-manager' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'month',
				'options' => array(
					'month' => __( 'Mês', 'apollo-events-manager' ),
					'week'  => __( 'Semana', 'apollo-events-manager' ),
					'list'  => __( 'Lista', 'apollo-events-manager' ),
				),
			)
		);

		$this->add_control(
			'start_day',
			array(
				'label'   => esc_html__( 'Primeiro dia da semana', 'apollo-events-manager' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '0',
				'options' => array(
					'0' => __( 'Domingo', 'apollo-events-manager' ),
					'1' => __( 'Segunda-feira', 'apollo-events-manager' ),
				),
			)
		);

		$this->add_control(
			'max_events',
			array(
				'label'     => esc_html__( 'Máximo de eventos por dia', 'apollo-events-manager' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 3,
				'min'       => 1,
				'max'       => 10,
				'condition' => array(
					'view' => 'month',
				),
			)
		);

		$this->end_controls_section();

		// Query Section.
		$this->start_controls_section(
			'section_query',
			array(
				'label' => esc_html__( 'Filtros', 'apollo-events-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		// Taxonomy Controls.
		$this->register_taxonomy_control( 'event_listing_category', __( 'Categorias', 'apollo-events-manager' ) );
		$this->register_taxonomy_control( 'event_listing_type', __( 'Tipos', 'apollo-events-manager' ) );
		$this->register_taxonomy_control( 'event_sounds', __( 'Estilos Musicais', 'apollo-events-manager' ) );

		$this->add_control(
			'featured_only',
			array(
				'label'        => esc_html__( 'Apenas Destaques', 'apollo-events-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->end_controls_section();

		// Display Section.
		$this->start_controls_section(
			'section_display',
			array(
				'label' => esc_html__( 'Elementos', 'apollo-events-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_navigation',
			array(
				'label'        => esc_html__( 'Mostrar Navegação', 'apollo-events-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_weekdays',
			array(
				'label'        => esc_html__( 'Mostrar Dias da Semana', 'apollo-events-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'view' => 'month',
				),
			)
		);

		$this->add_control(
			'show_event_count',
			array(
				'label'        => esc_html__( 'Mostrar Contador', 'apollo-events-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'view' => 'month',
				),
			)
		);

		$this->add_control(
			'show_popup',
			array(
				'label'        => esc_html__( 'Popup ao Clicar', 'apollo-events-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		// Style Section - Calendar.
		$this->start_controls_section(
			'section_style_calendar',
			array(
				'label' => esc_html__( 'Calendário', 'apollo-events-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'calendar_bg_color',
			array(
				'label'     => esc_html__( 'Cor de Fundo', 'apollo-events-manager' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .apollo-calendar' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'calendar_border',
				'selector' => '{{WRAPPER}} .apollo-calendar',
			)
		);

		$this->add_control(
			'calendar_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'apollo-events-manager' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .apollo-calendar' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'calendar_shadow',
				'selector' => '{{WRAPPER}} .apollo-calendar',
			)
		);

		$this->end_controls_section();

		// Style Section - Days.
		$this->start_controls_section(
			'section_style_days',
			array(
				'label' => esc_html__( 'Dias', 'apollo-events-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'day_text_color',
			array(
				'label'     => esc_html__( 'Cor do Texto', 'apollo-events-manager' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .apollo-calendar__day-number' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'today_bg_color',
			array(
				'label'     => esc_html__( 'Fundo do Dia Atual', 'apollo-events-manager' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#6366f1',
				'selectors' => array(
					'{{WRAPPER}} .apollo-calendar__day--today .apollo-calendar__day-number' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'weekend_color',
			array(
				'label'     => esc_html__( 'Cor do Fim de Semana', 'apollo-events-manager' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .apollo-calendar__day--weekend .apollo-calendar__day-number' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// Style Section - Events.
		$this->start_controls_section(
			'section_style_events',
			array(
				'label' => esc_html__( 'Eventos', 'apollo-events-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'event_bg_color',
			array(
				'label'     => esc_html__( 'Cor de Fundo', 'apollo-events-manager' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#6366f1',
				'selectors' => array(
					'{{WRAPPER}} .apollo-calendar__event' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'event_text_color',
			array(
				'label'     => esc_html__( 'Cor do Texto', 'apollo-events-manager' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .apollo-calendar__event' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'featured_event_color',
			array(
				'label'     => esc_html__( 'Cor de Destaque', 'apollo-events-manager' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#f59e0b',
				'selectors' => array(
					'{{WRAPPER}} .apollo-calendar__event--featured' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'event_typography',
				'selector' => '{{WRAPPER}} .apollo-calendar__event',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output.
	 *
	 * @return void
	 */
	protected function render(): void {
		$settings = $this->get_settings_for_display();

		// Get filter values.
		$categories = $settings['event_listing_category'] ?? array();
		$types      = $settings['event_listing_type'] ?? array();
		$sounds     = $settings['event_sounds'] ?? array();
		$featured   = $this->get_toggle_value( $settings, 'featured_only' );

		// Current month/year.
		$current_month = isset( $_GET['calendar_month'] ) ? (int) $_GET['calendar_month'] : (int) date( 'm' );
		$current_year  = isset( $_GET['calendar_year'] ) ? (int) $_GET['calendar_year'] : (int) date( 'Y' );

		// Get month boundaries.
		$first_day         = strtotime( "{$current_year}-{$current_month}-01" );
		$last_day          = strtotime( date( 'Y-m-t', $first_day ) );
		$days_in_month     = (int) date( 't', $first_day );
		$start_weekday     = (int) date( 'w', $first_day );
		$start_day_setting = (int) ( $settings['start_day'] ?? 0 );

		// Adjust for start day.
		$offset = $start_weekday - $start_day_setting;
		if ( $offset < 0 ) {
			$offset += 7;
		}

		// Query events for this month.
		$args = array(
			'post_type'      => 'event_listing',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_event_start_date',
					'value'   => array(
						date( 'Y-m-d', strtotime( '-7 days', $first_day ) ),
						date( 'Y-m-d', strtotime( '+7 days', $last_day ) ),
					),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		);

		// Taxonomy filters.
		$tax_query = array();
		if ( ! empty( $categories ) ) {
			$tax_query[] = array(
				'taxonomy' => 'event_listing_category',
				'field'    => 'term_id',
				'terms'    => $categories,
			);
		}
		if ( ! empty( $types ) ) {
			$tax_query[] = array(
				'taxonomy' => 'event_listing_type',
				'field'    => 'term_id',
				'terms'    => $types,
			);
		}
		if ( ! empty( $sounds ) ) {
			$tax_query[] = array(
				'taxonomy' => 'event_sounds',
				'field'    => 'term_id',
				'terms'    => $sounds,
			);
		}
		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}
		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query;
		}

		// Featured filter.
		if ( $featured ) {
			$args['meta_query'][] = array(
				'key'   => '_event_featured',
				'value' => '1',
			);
		}

		$events_query = new WP_Query( $args );

		// Organize events by date.
		$events_by_date = array();
		if ( $events_query->have_posts() ) {
			foreach ( $events_query->posts as $event ) {
				$event_date = get_post_meta( $event->ID, '_event_start_date', true );
				if ( $event_date ) {
					$date_key = date( 'Y-m-d', strtotime( $event_date ) );
					if ( ! isset( $events_by_date[ $date_key ] ) ) {
						$events_by_date[ $date_key ] = array();
					}
					$events_by_date[ $date_key ][] = $event;
				}
			}
		}
		wp_reset_postdata();

		$weekdays = array(
			__( 'Dom', 'apollo-events-manager' ),
			__( 'Seg', 'apollo-events-manager' ),
			__( 'Ter', 'apollo-events-manager' ),
			__( 'Qua', 'apollo-events-manager' ),
			__( 'Qui', 'apollo-events-manager' ),
			__( 'Sex', 'apollo-events-manager' ),
			__( 'Sáb', 'apollo-events-manager' ),
		);

		// Reorder weekdays based on start day.
		if ( $start_day_setting === 1 ) {
			$sunday     = array_shift( $weekdays );
			$weekdays[] = $sunday;
		}

		$view       = $settings['view'] ?? 'month';
		$max_events = (int) ( $settings['max_events'] ?? 3 );

		// Navigation URLs.
		$prev_month = $current_month - 1;
		$prev_year  = $current_year;
		$next_month = $current_month + 1;
		$next_year  = $current_year;

		if ( $prev_month < 1 ) {
			$prev_month = 12;
			--$prev_year;
		}
		if ( $next_month > 12 ) {
			$next_month = 1;
			++$next_year;
		}

		$base_url = remove_query_arg( array( 'calendar_month', 'calendar_year' ) );
		$prev_url = add_query_arg(
			array(
				'calendar_month' => $prev_month,
				'calendar_year'  => $prev_year,
			),
			$base_url
		);
		$next_url = add_query_arg(
			array(
				'calendar_month' => $next_month,
				'calendar_year'  => $next_year,
			),
			$base_url
		);
		?>
		<div class="apollo-calendar apollo-calendar--<?php echo esc_attr( $view ); ?>"
			data-view="<?php echo esc_attr( $view ); ?>"
			data-popup="<?php echo $this->get_toggle_value( $settings, 'popup' ) ? 'true' : 'false'; ?>">

			<?php if ( $this->get_toggle_value( $settings, 'navigation' ) ) : ?>
				<div class="apollo-calendar__header">
					<button class="apollo-calendar__nav apollo-calendar__nav--prev"
							data-url="<?php echo esc_url( $prev_url ); ?>">
						<i class="ri-arrow-left-s-line"></i>
					</button>
					<h3 class="apollo-calendar__title">
						<?php echo esc_html( date_i18n( 'F Y', $first_day ) ); ?>
					</h3>
					<button class="apollo-calendar__nav apollo-calendar__nav--next"
							data-url="<?php echo esc_url( $next_url ); ?>">
						<i class="ri-arrow-right-s-line"></i>
					</button>
				</div>
			<?php endif; ?>

			<?php if ( $view === 'month' ) : ?>
				<?php if ( $this->get_toggle_value( $settings, 'weekdays' ) ) : ?>
					<div class="apollo-calendar__weekdays">
						<?php foreach ( $weekdays as $idx => $day ) : ?>
							<div class="apollo-calendar__weekday <?php echo ( $idx === 0 || $idx === 6 ) ? 'apollo-calendar__weekday--weekend' : ''; ?>">
								<?php echo esc_html( $day ); ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div class="apollo-calendar__grid">
					<?php
					// Empty cells before first day.
					for ( $i = 0; $i < $offset; $i++ ) :
						?>
						<div class="apollo-calendar__day apollo-calendar__day--empty"></div>
					<?php endfor; ?>

					<?php
					// Days of the month.
					$today = date( 'Y-m-d' );
					for ( $day = 1; $day <= $days_in_month; $day++ ) :
						$date_key   = sprintf( '%04d-%02d-%02d', $current_year, $current_month, $day );
						$is_today   = $date_key === $today;
						$weekday    = ( $start_weekday + $day - 1 ) % 7;
						$is_weekend = ( $weekday === 0 || $weekday === 6 );
						$day_events = $events_by_date[ $date_key ] ?? array();
						$has_events = count( $day_events ) > 0;

						$day_classes = array( 'apollo-calendar__day' );
						if ( $is_today ) {
							$day_classes[] = 'apollo-calendar__day--today';
						}
						if ( $is_weekend ) {
							$day_classes[] = 'apollo-calendar__day--weekend';
						}
						if ( $has_events ) {
							$day_classes[] = 'apollo-calendar__day--has-events';
						}
						?>
						<div class="<?php echo esc_attr( implode( ' ', $day_classes ) ); ?>" data-date="<?php echo esc_attr( $date_key ); ?>">
							<span class="apollo-calendar__day-number">
								<?php echo esc_html( $day ); ?>
								<?php if ( $has_events && $this->get_toggle_value( $settings, 'event_count' ) ) : ?>
									<span class="apollo-calendar__event-count"><?php echo count( $day_events ); ?></span>
								<?php endif; ?>
							</span>

							<?php if ( $has_events ) : ?>
								<div class="apollo-calendar__events">
									<?php
									$shown = 0;
									foreach ( $day_events as $event ) :
										if ( $shown >= $max_events ) {
											break;
										}
										$is_featured = get_post_meta( $event->ID, '_event_featured', true );
										$start_time  = get_post_meta( $event->ID, '_event_start_time', true );
										?>
										<a href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>"
											class="apollo-calendar__event <?php echo $is_featured ? 'apollo-calendar__event--featured' : ''; ?>">
											<?php if ( $start_time ) : ?>
												<span class="apollo-calendar__event-time"><?php echo esc_html( $start_time ); ?></span>
											<?php endif; ?>
											<span class="apollo-calendar__event-title"><?php echo esc_html( $event->post_title ); ?></span>
										</a>
										<?php
										++$shown;
									endforeach;
									?>
									<?php if ( count( $day_events ) > $max_events ) : ?>
										<button class="apollo-calendar__more-events" data-date="<?php echo esc_attr( $date_key ); ?>">
											+<?php echo count( $day_events ) - $max_events; ?> <?php esc_html_e( 'mais', 'apollo-events-manager' ); ?>
										</button>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endfor; ?>

					<?php
					// Empty cells after last day.
					$total_cells = $offset + $days_in_month;
					$remaining   = ( 7 - ( $total_cells % 7 ) ) % 7;
					for ( $i = 0; $i < $remaining; $i++ ) :
						?>
						<div class="apollo-calendar__day apollo-calendar__day--empty"></div>
					<?php endfor; ?>
				</div>

			<?php elseif ( $view === 'list' ) : ?>
				<div class="apollo-calendar__list">
					<?php
					// Sort dates.
					ksort( $events_by_date );
					foreach ( $events_by_date as $date_key => $day_events ) :
						$date_timestamp = strtotime( $date_key );
						if ( $date_timestamp < $first_day || $date_timestamp > $last_day ) {
							continue;
						}
						?>
						<div class="apollo-calendar__list-day">
							<div class="apollo-calendar__list-date">
								<span class="apollo-calendar__list-day-num"><?php echo esc_html( date( 'd', $date_timestamp ) ); ?></span>
								<span class="apollo-calendar__list-day-name"><?php echo esc_html( date_i18n( 'D', $date_timestamp ) ); ?></span>
							</div>
							<div class="apollo-calendar__list-events">
								<?php
								foreach ( $day_events as $event ) :
									$start_time  = get_post_meta( $event->ID, '_event_start_time', true );
									$location    = get_post_meta( $event->ID, '_event_venue', true );
									$is_featured = get_post_meta( $event->ID, '_event_featured', true );
									?>
									<a href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>"
										class="apollo-calendar__list-event <?php echo $is_featured ? 'apollo-calendar__list-event--featured' : ''; ?>">
										<?php if ( has_post_thumbnail( $event->ID ) ) : ?>
											<div class="apollo-calendar__list-event-image">
												<?php echo get_the_post_thumbnail( $event->ID, 'thumbnail' ); ?>
											</div>
										<?php endif; ?>
										<div class="apollo-calendar__list-event-content">
											<h4 class="apollo-calendar__list-event-title"><?php echo esc_html( $event->post_title ); ?></h4>
											<div class="apollo-calendar__list-event-meta">
												<?php if ( $start_time ) : ?>
													<span><i class="ri-time-line"></i> <?php echo esc_html( $start_time ); ?></span>
												<?php endif; ?>
												<?php if ( $location ) : ?>
													<span><i class="ri-map-pin-line"></i> <?php echo esc_html( $location ); ?></span>
												<?php endif; ?>
											</div>
										</div>
									</a>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>

					<?php if ( empty( $events_by_date ) ) : ?>
						<div class="apollo-calendar__empty">
							<i class="ri-calendar-line"></i>
							<p><?php esc_html_e( 'Nenhum evento encontrado neste mês.', 'apollo-events-manager' ); ?></p>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $this->get_toggle_value( $settings, 'popup' ) ) : ?>
				<div class="apollo-calendar__popup" style="display:none;">
					<div class="apollo-calendar__popup-overlay"></div>
					<div class="apollo-calendar__popup-content">
						<button class="apollo-calendar__popup-close">
							<i class="ri-close-line"></i>
						</button>
						<div class="apollo-calendar__popup-body"></div>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
