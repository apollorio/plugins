<?php
/**
 * Blocks Module
 *
 * Gutenberg blocks for events.
 *
 * @package Apollo_Events_Manager
 * @subpackage Modules
 * @since 2.0.0
 */

namespace Apollo\Events\Modules;

use Apollo\Events\Core\Abstract_Module;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Blocks_Module
 *
 * Gutenberg blocks for event display.
 *
 * @since 2.0.0
 */
class Blocks_Module extends Abstract_Module {

	/**
	 * Get module unique identifier.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_id(): string {
		return 'blocks';
	}

	/**
	 * Get module name.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_name(): string {
		return __( 'Gutenberg Blocks', 'apollo-events' );
	}

	/**
	 * Get module description.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Blocos Gutenberg para exibição de eventos.', 'apollo-events' );
	}

	/**
	 * Get module version.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_version(): string {
		return '2.0.0';
	}

	/**
	 * Check if module is enabled by default.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function is_default_enabled(): bool {
		return true;
	}

	/**
	 * Initialize the module.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function init(): void {
		$this->register_hooks();
		$this->register_assets();
	}

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function register_hooks(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ), 10, 2 );
	}

	/**
	 * Register block category.
	 *
	 * @since 2.0.0
	 * @param array                    $categories Block categories.
	 * @param \WP_Block_Editor_Context $context    Editor context.
	 * @return array
	 */
	public function register_block_category( array $categories, $context ): array {
		return array_merge(
			array(
				array(
					'slug'  => 'apollo-events',
					'title' => __( 'Apollo Events', 'apollo-events' ),
					'icon'  => 'calendar-alt',
				),
			),
			$categories
		);
	}

	/**
	 * Register blocks.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_blocks(): void {
		// Event List Block.
		register_block_type(
			'apollo-events/event-list',
			array(
				'editor_script'   => 'apollo-blocks-editor',
				'editor_style'    => 'apollo-blocks-editor',
				'style'           => 'apollo-blocks',
				'render_callback' => array( $this, 'render_event_list_block' ),
				'attributes'      => array(
					'layout'    => array(
						'type'    => 'string',
						'default' => 'grid',
					),
					'columns'   => array(
						'type'    => 'number',
						'default' => 3,
					),
					'count'     => array(
						'type'    => 'number',
						'default' => 6,
					),
					'showPast'  => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'showDate'  => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showLocal' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showDJs'   => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'className' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);

		// Single Event Block.
		register_block_type(
			'apollo-events/single-event',
			array(
				'editor_script'   => 'apollo-blocks-editor',
				'editor_style'    => 'apollo-blocks-editor',
				'style'           => 'apollo-blocks',
				'render_callback' => array( $this, 'render_single_event_block' ),
				'attributes'      => array(
					'eventId'    => array(
						'type'    => 'number',
						'default' => 0,
					),
					'layout'     => array(
						'type'    => 'string',
						'default' => 'card',
					),
					'showImage'  => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showDate'   => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showLocal'  => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showButton' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'className'  => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);

		// Event Countdown Block.
		register_block_type(
			'apollo-events/countdown',
			array(
				'editor_script'   => 'apollo-blocks-editor',
				'editor_style'    => 'apollo-blocks-editor',
				'style'           => 'apollo-blocks',
				'render_callback' => array( $this, 'render_countdown_block' ),
				'attributes'      => array(
					'eventId'     => array(
						'type'    => 'number',
						'default' => 0,
					),
					'showTitle'   => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showDays'    => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showHours'   => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showMinutes' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showSeconds' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'style'       => array(
						'type'    => 'string',
						'default' => 'default',
					),
					'className'   => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);

		// Event Calendar Block.
		register_block_type(
			'apollo-events/calendar',
			array(
				'editor_script'   => 'apollo-blocks-editor',
				'editor_style'    => 'apollo-blocks-editor',
				'style'           => 'apollo-blocks',
				'render_callback' => array( $this, 'render_calendar_block' ),
				'attributes'      => array(
					'month'     => array(
						'type'    => 'number',
						'default' => 0,
					),
					'year'      => array(
						'type'    => 'number',
						'default' => 0,
					),
					'showNav'   => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'className' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);

		// DJ Grid Block.
		register_block_type(
			'apollo-events/dj-grid',
			array(
				'editor_script'   => 'apollo-blocks-editor',
				'editor_style'    => 'apollo-blocks-editor',
				'style'           => 'apollo-blocks',
				'render_callback' => array( $this, 'render_dj_grid_block' ),
				'attributes'      => array(
					'columns'   => array(
						'type'    => 'number',
						'default' => 4,
					),
					'count'     => array(
						'type'    => 'number',
						'default' => 8,
					),
					'showName'  => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showGenre' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'className' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);

		// Local Grid Block.
		register_block_type(
			'apollo-events/local-grid',
			array(
				'editor_script'   => 'apollo-blocks-editor',
				'editor_style'    => 'apollo-blocks-editor',
				'style'           => 'apollo-blocks',
				'render_callback' => array( $this, 'render_local_grid_block' ),
				'attributes'      => array(
					'columns'     => array(
						'type'    => 'number',
						'default' => 3,
					),
					'count'       => array(
						'type'    => 'number',
						'default' => 6,
					),
					'showAddress' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'className'   => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);

		// Event Search Block.
		register_block_type(
			'apollo-events/search',
			array(
				'editor_script'   => 'apollo-blocks-editor',
				'editor_style'    => 'apollo-blocks-editor',
				'style'           => 'apollo-blocks',
				'render_callback' => array( $this, 'render_search_block' ),
				'attributes'      => array(
					'showDate'    => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showLocal'   => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showDJ'      => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'placeholder' => array(
						'type'    => 'string',
						'default' => '',
					),
					'className'   => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);
	}

	/**
	 * Register module assets.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_assets(): void {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function enqueue_editor_assets(): void {
		$plugin_url = plugins_url( '', dirname( dirname( __DIR__ ) ) );

		wp_enqueue_script(
			'apollo-blocks-editor',
			$plugin_url . '/assets/js/blocks-editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-data' ),
			$this->get_version(),
			true
		);

		wp_enqueue_style(
			'apollo-blocks-editor',
			$plugin_url . '/assets/css/blocks-editor.css',
			array( 'wp-edit-blocks' ),
			$this->get_version()
		);

		// Pass data to editor.
		wp_localize_script(
			'apollo-blocks-editor',
			'apolloBlocksData',
			array(
				'events' => $this->get_events_for_editor(),
				'djs'    => $this->get_djs_for_editor(),
				'locals' => $this->get_locals_for_editor(),
				'i18n'   => array(
					'title'       => __( 'Apollo Events', 'apollo-events' ),
					'eventList'   => __( 'Lista de Eventos', 'apollo-events' ),
					'singleEvent' => __( 'Evento Único', 'apollo-events' ),
					'countdown'   => __( 'Contagem Regressiva', 'apollo-events' ),
					'calendar'    => __( 'Calendário', 'apollo-events' ),
					'djGrid'      => __( 'Grid de DJs', 'apollo-events' ),
					'localGrid'   => __( 'Grid de Locais', 'apollo-events' ),
					'search'      => __( 'Busca de Eventos', 'apollo-events' ),
				),
			)
		);
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function enqueue_assets( string $context = '' ): void {
		if ( ! has_block( 'apollo-events' ) ) {
			return;
		}

		$plugin_url = plugins_url( '', dirname( dirname( __DIR__ ) ) );

		wp_enqueue_style(
			'apollo-blocks',
			$plugin_url . '/assets/css/blocks.css',
			array(),
			$this->get_version()
		);

		wp_enqueue_script(
			'apollo-blocks',
			$plugin_url . '/assets/js/blocks.js',
			array(),
			$this->get_version(),
			true
		);
	}

	/**
	 * Render event list block.
	 *
	 * @since 2.0.0
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_event_list_block( array $attributes ): string {
		$layout    = $attributes['layout'] ?? 'grid';
		$columns   = (int) ( $attributes['columns'] ?? 3 );
		$count     = (int) ( $attributes['count'] ?? 6 );
		$show_past = $attributes['showPast'] ?? false;
		$class     = $attributes['className'] ?? '';

		$args = array(
			'post_type'      => 'event_listing',
			'post_status'    => 'publish',
			'posts_per_page' => $count,
		);

		if ( ! $show_past ) {
			$args['meta_query'] = array(
				array(
					'key'     => '_event_start_date',
					'value'   => current_time( 'Y-m-d' ),
					'compare' => '>=',
					'type'    => 'DATE',
				),
			);
			$args['meta_key']   = '_event_start_date';
			$args['orderby']    = 'meta_value';
			$args['order']      = 'ASC';
		}

		$events = get_posts( $args );

		if ( empty( $events ) ) {
			return '<p class="apollo-no-events">' . esc_html__( 'Nenhum evento encontrado.', 'apollo-events' ) . '</p>';
		}

		$html = sprintf(
			'<div class="apollo-block apollo-event-list apollo-event-list--%s apollo-columns-%d %s">',
			esc_attr( $layout ),
			$columns,
			esc_attr( $class )
		);

		foreach ( $events as $event ) {
			$html .= $this->render_event_card( $event->ID, $attributes );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render single event block.
	 *
	 * @since 2.0.0
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_single_event_block( array $attributes ): string {
		$event_id = (int) ( $attributes['eventId'] ?? 0 );

		if ( ! $event_id ) {
			return '<p class="apollo-no-event">' . esc_html__( 'Selecione um evento.', 'apollo-events' ) . '</p>';
		}

		$event = get_post( $event_id );

		if ( ! $event || 'event_listing' !== $event->post_type ) {
			return '<p class="apollo-no-event">' . esc_html__( 'Evento não encontrado.', 'apollo-events' ) . '</p>';
		}

		$class  = $attributes['className'] ?? '';
		$layout = $attributes['layout'] ?? 'card';

		return sprintf(
			'<div class="apollo-block apollo-single-event apollo-single-event--%s %s">%s</div>',
			esc_attr( $layout ),
			esc_attr( $class ),
			$this->render_event_card( $event_id, $attributes )
		);
	}

	/**
	 * Render countdown block.
	 *
	 * @since 2.0.0
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_countdown_block( array $attributes ): string {
		$event_id = (int) ( $attributes['eventId'] ?? 0 );

		if ( ! $event_id ) {
			// Get next upcoming event.
			$events = get_posts(
				array(
					'post_type'      => 'event_listing',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'meta_key'       => '_event_start_date',
					'orderby'        => 'meta_value',
					'order'          => 'ASC',
					'meta_query'     => array(
						array(
							'key'     => '_event_start_date',
							'value'   => current_time( 'Y-m-d H:i:s' ),
							'compare' => '>',
							'type'    => 'DATETIME',
						),
					),
				)
			);

			if ( ! empty( $events ) ) {
				$event_id = $events[0]->ID;
			}
		}

		if ( ! $event_id ) {
			return '<p class="apollo-no-event">' . esc_html__( 'Nenhum evento próximo.', 'apollo-events' ) . '</p>';
		}

		$event      = get_post( $event_id );
		$start_date = get_post_meta( $event_id, '_event_start_date', true );
		$class      = $attributes['className'] ?? '';
		$style      = $attributes['style'] ?? 'default';
		$show_title = $attributes['showTitle'] ?? true;

		if ( ! $start_date ) {
			return '<p class="apollo-no-date">' . esc_html__( 'Data não definida.', 'apollo-events' ) . '</p>';
		}

		$html = sprintf(
			'<div class="apollo-block apollo-countdown apollo-countdown--%s %s" data-date="%s">',
			esc_attr( $style ),
			esc_attr( $class ),
			esc_attr( $start_date )
		);

		if ( $show_title && $event ) {
			$html .= sprintf(
				'<h3 class="apollo-countdown__title"><a href="%s">%s</a></h3>',
				esc_url( get_permalink( $event_id ) ),
				esc_html( $event->post_title )
			);
		}

		$html .= '<div class="apollo-countdown__timer">';

		if ( $attributes['showDays'] ?? true ) {
			$html .= '<div class="apollo-countdown__unit"><span class="apollo-countdown__value" data-unit="days">00</span><span class="apollo-countdown__label">' . esc_html__( 'Dias', 'apollo-events' ) . '</span></div>';
		}

		if ( $attributes['showHours'] ?? true ) {
			$html .= '<div class="apollo-countdown__unit"><span class="apollo-countdown__value" data-unit="hours">00</span><span class="apollo-countdown__label">' . esc_html__( 'Horas', 'apollo-events' ) . '</span></div>';
		}

		if ( $attributes['showMinutes'] ?? true ) {
			$html .= '<div class="apollo-countdown__unit"><span class="apollo-countdown__value" data-unit="minutes">00</span><span class="apollo-countdown__label">' . esc_html__( 'Minutos', 'apollo-events' ) . '</span></div>';
		}

		if ( $attributes['showSeconds'] ?? true ) {
			$html .= '<div class="apollo-countdown__unit"><span class="apollo-countdown__value" data-unit="seconds">00</span><span class="apollo-countdown__label">' . esc_html__( 'Segundos', 'apollo-events' ) . '</span></div>';
		}

		$html .= '</div></div>';

		return $html;
	}

	/**
	 * Render calendar block.
	 *
	 * @since 2.0.0
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_calendar_block( array $attributes ): string {
		$month    = (int) ( $attributes['month'] ?? 0 ) ?: (int) current_time( 'm' );
		$year     = (int) ( $attributes['year'] ?? 0 ) ?: (int) current_time( 'Y' );
		$show_nav = $attributes['showNav'] ?? true;
		$class    = $attributes['className'] ?? '';

		// Get events for this month.
		$events = get_posts(
			array(
				'post_type'      => 'event_listing',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'     => '_event_start_date',
						'value'   => array(
							$year . '-' . str_pad( $month, 2, '0', STR_PAD_LEFT ) . '-01',
							$year . '-' . str_pad( $month, 2, '0', STR_PAD_LEFT ) . '-31',
						),
						'compare' => 'BETWEEN',
						'type'    => 'DATE',
					),
				),
			)
		);

		// Map events by day.
		$event_days = array();
		foreach ( $events as $event ) {
			$start_date = get_post_meta( $event->ID, '_event_start_date', true );
			$day        = (int) wp_date( 'j', strtotime( $start_date ) );
			if ( ! isset( $event_days[ $day ] ) ) {
				$event_days[ $day ] = array();
			}
			$event_days[ $day ][] = $event;
		}

		$html = sprintf(
			'<div class="apollo-block apollo-calendar %s">',
			esc_attr( $class )
		);

		// Month navigation.
		if ( $show_nav ) {
			$prev_month = $month - 1;
			$prev_year  = $year;
			if ( $prev_month < 1 ) {
				$prev_month = 12;
				--$prev_year;
			}

			$next_month = $month + 1;
			$next_year  = $year;
			if ( $next_month > 12 ) {
				$next_month = 1;
				++$next_year;
			}

			$month_names = array(
				1  => __( 'Janeiro', 'apollo-events' ),
				2  => __( 'Fevereiro', 'apollo-events' ),
				3  => __( 'Março', 'apollo-events' ),
				4  => __( 'Abril', 'apollo-events' ),
				5  => __( 'Maio', 'apollo-events' ),
				6  => __( 'Junho', 'apollo-events' ),
				7  => __( 'Julho', 'apollo-events' ),
				8  => __( 'Agosto', 'apollo-events' ),
				9  => __( 'Setembro', 'apollo-events' ),
				10 => __( 'Outubro', 'apollo-events' ),
				11 => __( 'Novembro', 'apollo-events' ),
				12 => __( 'Dezembro', 'apollo-events' ),
			);

			$html .= '<div class="apollo-calendar__nav">';
			$html .= sprintf(
				'<button class="apollo-calendar__nav-btn" data-month="%d" data-year="%d">&laquo;</button>',
				$prev_month,
				$prev_year
			);
			$html .= sprintf(
				'<span class="apollo-calendar__month">%s %d</span>',
				$month_names[ $month ],
				$year
			);
			$html .= sprintf(
				'<button class="apollo-calendar__nav-btn" data-month="%d" data-year="%d">&raquo;</button>',
				$next_month,
				$next_year
			);
			$html .= '</div>';
		}

		// Calendar grid.
		$html .= '<div class="apollo-calendar__grid">';

		// Day headers.
		$day_names = array(
			__( 'Dom', 'apollo-events' ),
			__( 'Seg', 'apollo-events' ),
			__( 'Ter', 'apollo-events' ),
			__( 'Qua', 'apollo-events' ),
			__( 'Qui', 'apollo-events' ),
			__( 'Sex', 'apollo-events' ),
			__( 'Sáb', 'apollo-events' ),
		);

		foreach ( $day_names as $day_name ) {
			$html .= '<div class="apollo-calendar__day-header">' . esc_html( $day_name ) . '</div>';
		}

		// Days.
		$first_day     = mktime( 0, 0, 0, $month, 1, $year );
		$days_in_month = (int) date( 't', $first_day );
		$day_of_week   = (int) date( 'w', $first_day );
		$today         = (int) current_time( 'j' );
		$current_month = (int) current_time( 'm' );
		$current_year  = (int) current_time( 'Y' );

		// Empty cells before first day.
		for ( $i = 0; $i < $day_of_week; $i++ ) {
			$html .= '<div class="apollo-calendar__day apollo-calendar__day--empty"></div>';
		}

		// Days.
		for ( $day = 1; $day <= $days_in_month; $day++ ) {
			$is_today    = ( $day === $today && $month === $current_month && $year === $current_year );
			$has_events  = isset( $event_days[ $day ] );
			$day_classes = array( 'apollo-calendar__day' );

			if ( $is_today ) {
				$day_classes[] = 'apollo-calendar__day--today';
			}
			if ( $has_events ) {
				$day_classes[] = 'apollo-calendar__day--has-events';
			}

			$html .= sprintf( '<div class="%s">', implode( ' ', $day_classes ) );
			$html .= '<span class="apollo-calendar__day-number">' . $day . '</span>';

			if ( $has_events ) {
				$html .= '<div class="apollo-calendar__events">';
				foreach ( array_slice( $event_days[ $day ], 0, 3 ) as $event ) {
					$html .= sprintf(
						'<a href="%s" class="apollo-calendar__event">%s</a>',
						esc_url( get_permalink( $event->ID ) ),
						esc_html( wp_trim_words( $event->post_title, 3 ) )
					);
				}
				if ( count( $event_days[ $day ] ) > 3 ) {
					$html .= sprintf(
						'<span class="apollo-calendar__more">+%d</span>',
						count( $event_days[ $day ] ) - 3
					);
				}
				$html .= '</div>';
			}

			$html .= '</div>';
		}

		$html .= '</div></div>';

		return $html;
	}

	/**
	 * Render DJ grid block.
	 *
	 * @since 2.0.0
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_dj_grid_block( array $attributes ): string {
		$columns    = (int) ( $attributes['columns'] ?? 4 );
		$count      = (int) ( $attributes['count'] ?? 8 );
		$show_name  = $attributes['showName'] ?? true;
		$show_genre = $attributes['showGenre'] ?? true;
		$class      = $attributes['className'] ?? '';

		$djs = get_posts(
			array(
				'post_type'      => 'event_dj',
				'post_status'    => 'publish',
				'posts_per_page' => $count,
			)
		);

		if ( empty( $djs ) ) {
			return '<p class="apollo-no-djs">' . esc_html__( 'Nenhum DJ encontrado.', 'apollo-events' ) . '</p>';
		}

		$html = sprintf(
			'<div class="apollo-block apollo-dj-grid apollo-columns-%d %s">',
			$columns,
			esc_attr( $class )
		);

		foreach ( $djs as $dj ) {
			$html .= '<div class="apollo-dj-card">';
			$html .= sprintf(
				'<a href="%s" class="apollo-dj-card__link">',
				esc_url( get_permalink( $dj->ID ) )
			);

			if ( has_post_thumbnail( $dj->ID ) ) {
				$html .= '<div class="apollo-dj-card__image">';
				$html .= get_the_post_thumbnail( $dj->ID, 'medium' );
				$html .= '</div>';
			} else {
				$html .= '<div class="apollo-dj-card__image apollo-dj-card__image--placeholder"><span class="dashicons dashicons-admin-users"></span></div>';
			}

			if ( $show_name ) {
				$html .= '<h4 class="apollo-dj-card__name">' . esc_html( $dj->post_title ) . '</h4>';
			}

			if ( $show_genre ) {
				$genre = get_post_meta( $dj->ID, '_dj_genre', true );
				if ( $genre ) {
					$html .= '<p class="apollo-dj-card__genre">' . esc_html( $genre ) . '</p>';
				}
			}

			$html .= '</a></div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render local grid block.
	 *
	 * @since 2.0.0
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_local_grid_block( array $attributes ): string {
		$columns      = (int) ( $attributes['columns'] ?? 3 );
		$count        = (int) ( $attributes['count'] ?? 6 );
		$show_address = $attributes['showAddress'] ?? true;
		$class        = $attributes['className'] ?? '';

		$locals = get_posts(
			array(
				'post_type'      => 'event_local',
				'post_status'    => 'publish',
				'posts_per_page' => $count,
			)
		);

		if ( empty( $locals ) ) {
			return '<p class="apollo-no-locals">' . esc_html__( 'Nenhum local encontrado.', 'apollo-events' ) . '</p>';
		}

		$html = sprintf(
			'<div class="apollo-block apollo-local-grid apollo-columns-%d %s">',
			$columns,
			esc_attr( $class )
		);

		foreach ( $locals as $local ) {
			$html .= '<div class="apollo-local-card">';
			$html .= sprintf(
				'<a href="%s" class="apollo-local-card__link">',
				esc_url( get_permalink( $local->ID ) )
			);

			if ( has_post_thumbnail( $local->ID ) ) {
				$html .= '<div class="apollo-local-card__image">';
				$html .= get_the_post_thumbnail( $local->ID, 'medium_large' );
				$html .= '</div>';
			}

			$html .= '<div class="apollo-local-card__content">';
			$html .= '<h4 class="apollo-local-card__name">' . esc_html( $local->post_title ) . '</h4>';

			if ( $show_address ) {
				$address = get_post_meta( $local->ID, '_local_address', true );
				if ( $address ) {
					$html .= '<p class="apollo-local-card__address">' . esc_html( $address ) . '</p>';
				}
			}

			$html .= '</div></a></div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render search block.
	 *
	 * @since 2.0.0
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_search_block( array $attributes ): string {
		$show_date   = $attributes['showDate'] ?? true;
		$show_local  = $attributes['showLocal'] ?? true;
		$show_dj     = $attributes['showDJ'] ?? true;
		$placeholder = $attributes['placeholder'] ?? __( 'Buscar eventos...', 'apollo-events' );
		$class       = $attributes['className'] ?? '';

		$html = sprintf(
			'<div class="apollo-block apollo-search %s">',
			esc_attr( $class )
		);

		$html .= '<form class="apollo-search__form" method="get" action="' . esc_url( get_post_type_archive_link( 'event_listing' ) ) . '">';

		$html .= '<div class="apollo-search__row">';
		$html .= sprintf(
			'<input type="text" name="s" class="apollo-search__input" placeholder="%s">',
			esc_attr( $placeholder )
		);
		$html .= '<input type="hidden" name="post_type" value="event_listing">';
		$html .= '</div>';

		$html .= '<div class="apollo-search__filters">';

		if ( $show_date ) {
			$html .= '<div class="apollo-search__filter">';
			$html .= '<label>' . esc_html__( 'Data', 'apollo-events' ) . '</label>';
			$html .= '<input type="date" name="event_date" class="apollo-search__date">';
			$html .= '</div>';
		}

		if ( $show_local ) {
			$locals = get_posts(
				array(
					'post_type'      => 'event_local',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
				)
			);

			if ( ! empty( $locals ) ) {
				$html .= '<div class="apollo-search__filter">';
				$html .= '<label>' . esc_html__( 'Local', 'apollo-events' ) . '</label>';
				$html .= '<select name="event_local" class="apollo-search__select">';
				$html .= '<option value="">' . esc_html__( 'Todos', 'apollo-events' ) . '</option>';
				foreach ( $locals as $local ) {
					$html .= sprintf(
						'<option value="%d">%s</option>',
						$local->ID,
						esc_html( $local->post_title )
					);
				}
				$html .= '</select></div>';
			}
		}

		if ( $show_dj ) {
			$djs = get_posts(
				array(
					'post_type'      => 'event_dj',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
				)
			);

			if ( ! empty( $djs ) ) {
				$html .= '<div class="apollo-search__filter">';
				$html .= '<label>' . esc_html__( 'DJ', 'apollo-events' ) . '</label>';
				$html .= '<select name="event_dj" class="apollo-search__select">';
				$html .= '<option value="">' . esc_html__( 'Todos', 'apollo-events' ) . '</option>';
				foreach ( $djs as $dj ) {
					$html .= sprintf(
						'<option value="%d">%s</option>',
						$dj->ID,
						esc_html( $dj->post_title )
					);
				}
				$html .= '</select></div>';
			}
		}

		$html .= '</div>';

		$html .= '<button type="submit" class="apollo-search__submit">';
		$html .= '<span class="dashicons dashicons-search"></span>';
		$html .= esc_html__( 'Buscar', 'apollo-events' );
		$html .= '</button>';

		$html .= '</form></div>';

		return $html;
	}

	/**
	 * Render event card.
	 *
	 * @since 2.0.0
	 * @param int   $event_id   Event ID.
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	private function render_event_card( int $event_id, array $attributes ): string {
		$event       = get_post( $event_id );
		$show_date   = $attributes['showDate'] ?? true;
		$show_local  = $attributes['showLocal'] ?? true;
		$show_djs    = $attributes['showDJs'] ?? true;
		$show_image  = $attributes['showImage'] ?? true;

		// Parse date
		$date_day   = '';
		$date_month = '';
		if ( $show_date ) {
			$start_date = get_post_meta( $event_id, '_event_start_date', true );
			if ( $start_date ) {
				$timestamp  = strtotime( $start_date );
				$date_day   = wp_date( 'd', $timestamp );
				$date_month = wp_date( 'M', $timestamp );
			}
		}

		// Get banner image
		$event_image = get_post_meta( $event_id, '_event_banner', true );
		if ( empty( $event_image ) && has_post_thumbnail( $event_id ) ) {
			$event_image = get_the_post_thumbnail_url( $event_id, 'medium_large' );
		}

		// Get location
		$event_location = '';
		if ( $show_local ) {
			$local_ids = get_post_meta( $event_id, '_event_local_ids', true );
			if ( is_array( $local_ids ) && ! empty( $local_ids ) ) {
				$local = get_post( $local_ids[0] );
				if ( $local ) {
					$event_location = get_post_meta( $local->ID, '_local_name', true ) ?: $local->post_title;
				}
			}
		}

		// Get DJs
		$dj_names = array();
		if ( $show_djs ) {
			$dj_slots = get_post_meta( $event_id, '_event_dj_slots', true );
			if ( ! empty( $dj_slots ) && is_array( $dj_slots ) ) {
				foreach ( array_slice( $dj_slots, 0, 3 ) as $slot ) {
					if ( isset( $slot['dj_id'] ) && $slot['dj_id'] ) {
						$dj_name = get_post_meta( $slot['dj_id'], '_dj_name', true );
						if ( empty( $dj_name ) ) {
							$dj = get_post( $slot['dj_id'] );
							$dj_name = $dj ? $dj->post_title : '';
						}
						if ( $dj_name ) {
							$dj_names[] = $dj_name;
						}
					}
				}
			}
			// Fallback to legacy DJ IDs
			if ( empty( $dj_names ) ) {
				$dj_ids = get_post_meta( $event_id, '_event_dj_ids', true );
				if ( is_array( $dj_ids ) && ! empty( $dj_ids ) ) {
					foreach ( array_slice( $dj_ids, 0, 3 ) as $dj_id ) {
						$dj = get_post( $dj_id );
						if ( $dj ) {
							$dj_names[] = $dj->post_title;
						}
					}
				}
			}
		}

		// Get sounds from taxonomy
		$event_sounds = array();
		$sounds_terms = wp_get_post_terms( $event_id, 'event_sounds', array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $sounds_terms ) && ! empty( $sounds_terms ) ) {
			$event_sounds = array_slice( $sounds_terms, 0, 3 );
		}

		// Get categories for tags
		$event_categories = array();
		$cat_terms = wp_get_post_terms( $event_id, 'event_listing_category', array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $cat_terms ) && ! empty( $cat_terms ) ) {
			$event_categories = array_slice( $cat_terms, 0, 3 );
		}

		$delay_index = wp_rand( 100, 300 );

		$html = sprintf(
			'<a href="%s" class="a-eve-card reveal-up delay-%d" data-idx="%d">',
			esc_url( get_permalink( $event_id ) ),
			$delay_index,
			$event_id
		);

		// Date block
		if ( $date_day && $date_month ) {
			$html .= '<div class="a-eve-date">';
			$html .= '<span class="a-eve-date-day">' . esc_html( $date_day ) . '</span>';
			$html .= '<span class="a-eve-date-month">' . esc_html( $date_month ) . '</span>';
			$html .= '</div>';
		}

		// Media block
		$html .= '<div class="a-eve-media">';
		if ( $show_image && $event_image ) {
			$html .= sprintf(
				'<img src="%s" alt="%s" loading="lazy" decoding="async">',
				esc_url( $event_image ),
				esc_attr( $event->post_title )
			);
		}
		if ( ! empty( $event_categories ) ) {
			$html .= '<div class="a-eve-tags">';
			foreach ( $event_categories as $cat ) {
				$html .= '<span class="a-eve-tag">' . esc_html( $cat ) . '</span>';
			}
			$html .= '</div>';
		}
		$html .= '</div>';

		// Content block
		$html .= '<div class="a-eve-content">';
		$html .= '<h2 class="a-eve-title">' . esc_html( $event->post_title ) . '</h2>';

		if ( ! empty( $dj_names ) ) {
			$html .= '<p class="a-eve-meta"><i class="ri-sound-module-fill"></i>';
			$html .= '<span>' . esc_html( implode( ', ', $dj_names ) ) . '</span></p>';
		}

		if ( $event_location ) {
			$html .= '<p class="a-eve-meta"><i class="ri-map-pin-2-line"></i>';
			$html .= '<span>' . esc_html( $event_location ) . '</span></p>';
		}

		if ( ! empty( $event_sounds ) ) {
			$html .= '<p class="a-eve-meta"><i class="ri-music-2-line"></i>';
			$html .= '<span>' . esc_html( implode( ', ', $event_sounds ) ) . '</span></p>';
		}

		$html .= '</div>';
		$html .= '</a>';

		return $html;
	}

	/**
	 * Get events for editor.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	private function get_events_for_editor(): array {
		$events = get_posts(
			array(
				'post_type'      => 'event_listing',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
			)
		);

		$data = array();
		foreach ( $events as $event ) {
			$data[] = array(
				'id'    => $event->ID,
				'title' => $event->post_title,
			);
		}

		return $data;
	}

	/**
	 * Get DJs for editor.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	private function get_djs_for_editor(): array {
		$djs = get_posts(
			array(
				'post_type'      => 'event_dj',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
			)
		);

		$data = array();
		foreach ( $djs as $dj ) {
			$data[] = array(
				'id'    => $dj->ID,
				'title' => $dj->post_title,
			);
		}

		return $data;
	}

	/**
	 * Get locals for editor.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	private function get_locals_for_editor(): array {
		$locals = get_posts(
			array(
				'post_type'      => 'event_local',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
			)
		);

		$data = array();
		foreach ( $locals as $local ) {
			$data[] = array(
				'id'    => $local->ID,
				'title' => $local->post_title,
			);
		}

		return $data;
	}

	/**
	 * Register shortcodes.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_shortcodes(): void {
		// Blocks module doesn't use shortcodes.
	}

	/**
	 * Get settings schema.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_settings_schema(): array {
		return array(
			'enable_blocks' => array(
				'type'    => 'boolean',
				'label'   => __( 'Habilitar blocos Gutenberg', 'apollo-events' ),
				'default' => true,
			),
		);
	}
}
