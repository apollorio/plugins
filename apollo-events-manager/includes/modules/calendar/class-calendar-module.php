<?php
/**
 * Calendar Module - Apollo Events Manager
 * Monthly and weekly calendar views for events
 *
 * @package Apollo\Events\Modules
 * @since 2.0.0
 */

namespace Apollo\Events\Modules;

use Apollo\Events\Core\Abstract_Module;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calendar Module Class
 * Provides calendar views for events
 */
class Calendar_Module extends Abstract_Module {

	/**
	 * Portuguese month names
	 *
	 * @var array
	 */
	private array $months_pt = array(
		1  => 'Janeiro',
		2  => 'Fevereiro',
		3  => 'Março',
		4  => 'Abril',
		5  => 'Maio',
		6  => 'Junho',
		7  => 'Julho',
		8  => 'Agosto',
		9  => 'Setembro',
		10 => 'Outubro',
		11 => 'Novembro',
		12 => 'Dezembro',
	);

	/**
	 * Portuguese weekday names (short)
	 *
	 * @var array
	 */
	private array $weekdays_pt = array(
		'Dom',
		'Seg',
		'Ter',
		'Qua',
		'Qui',
		'Sex',
		'Sáb',
	);

	/**
	 * Get module ID
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'calendar';
	}

	/**
	 * Get module name
	 *
	 * @return string
	 */
	public function get_name(): string {
		return __( 'Calendário de Eventos', 'apollo-events-manager' );
	}

	/**
	 * Get module description
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Visualização de eventos em calendário mensal ou semanal.', 'apollo-events-manager' );
	}

	/**
	 * Get module version
	 *
	 * @return string
	 */
	public function get_version(): string {
		return '1.0.0';
	}

	/**
	 * Is default enabled
	 *
	 * @return bool
	 */
	public function is_default_enabled(): bool {
		return true;
	}

	/**
	 * Initialize module
	 *
	 * @return void
	 */
	public function init(): void {
		// Register AJAX handlers
		add_action( 'wp_ajax_apollo_calendar_navigate', array( $this, 'ajax_navigate' ) );
		add_action( 'wp_ajax_nopriv_apollo_calendar_navigate', array( $this, 'ajax_navigate' ) );
	}

	/**
	 * Register shortcodes
	 *
	 * @return void
	 */
	public function register_shortcodes(): void {
		add_shortcode( 'apollo_calendar', array( $this, 'render_calendar' ) );
		add_shortcode( 'apollo_calendar_week', array( $this, 'render_calendar_week' ) );
		add_shortcode( 'apollo_mini_calendar', array( $this, 'render_mini_calendar' ) );
	}

	/**
	 * Register assets
	 *
	 * @return void
	 */
	public function register_assets(): void {
		wp_register_style(
			'apollo-calendar',
			APOLLO_APRIO_URL . 'assets/css/calendar.css',
			array(),
			APOLLO_APRIO_VERSION
		);

		wp_register_script(
			'apollo-calendar',
			APOLLO_APRIO_URL . 'assets/js/calendar.js',
			array( 'jquery' ),
			APOLLO_APRIO_VERSION,
			true
		);

		wp_localize_script(
			'apollo-calendar',
			'apolloCalendar',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'apollo_calendar_nonce' ),
				'months'   => $this->months_pt,
				'i18n'     => array(
					'loading' => __( 'Carregando...', 'apollo-events-manager' ),
					'today'   => __( 'Hoje', 'apollo-events-manager' ),
					'prev'    => __( 'Anterior', 'apollo-events-manager' ),
					'next'    => __( 'Próximo', 'apollo-events-manager' ),
				),
			)
		);
	}

	/**
	 * Enqueue assets
	 *
	 * @return void
	 */
	public function enqueue_assets( string $context = '' ): void {
		// Context is unused here but kept for interface compatibility.
		wp_enqueue_style( 'apollo-calendar' );
		wp_enqueue_script( 'apollo-calendar' );
	}

	/**
	 * Render monthly calendar shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_calendar( $atts ): string {
		$atts = shortcode_atts(
			array(
				'month'       => (int) current_time( 'n' ),
				'year'        => (int) current_time( 'Y' ),
				'category'    => '',
				'local_id'    => '',
				'show_nav'    => 'yes',
				'event_limit' => 3,
				'class'       => '',
			),
			$atts,
			'apollo_calendar'
		);

		$this->enqueue_assets();

		$month = max( 1, min( 12, (int) $atts['month'] ) );
		$year  = (int) $atts['year'];

		$events        = $this->get_events_for_month( $month, $year, $atts );
		$calendar_html = $this->build_month_grid( $month, $year, $events, (int) $atts['event_limit'] );

		ob_start();
		?>
		<div class="apollo-calendar apollo-calendar--month <?php echo esc_attr( $atts['class'] ); ?>"
			data-month="<?php echo esc_attr( $month ); ?>"
			data-year="<?php echo esc_attr( $year ); ?>"
			data-category="<?php echo esc_attr( $atts['category'] ); ?>"
			data-local="<?php echo esc_attr( $atts['local_id'] ); ?>">

			<?php if ( $atts['show_nav'] === 'yes' ) : ?>
			<div class="apollo-calendar__header">
				<button type="button" class="apollo-calendar__nav apollo-calendar__nav--prev" aria-label="<?php esc_attr_e( 'Mês anterior', 'apollo-events-manager' ); ?>">
					<i class="ri-arrow-left-s-line"></i>
				</button>
				<h2 class="apollo-calendar__title">
					<?php echo esc_html( $this->months_pt[ $month ] . ' ' . $year ); ?>
				</h2>
				<button type="button" class="apollo-calendar__nav apollo-calendar__nav--next" aria-label="<?php esc_attr_e( 'Próximo mês', 'apollo-events-manager' ); ?>">
					<i class="ri-arrow-right-s-line"></i>
				</button>
			</div>
			<?php endif; ?>

			<div class="apollo-calendar__grid">
				<?php echo $calendar_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render weekly calendar shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_calendar_week( $atts ): string {
		$atts = shortcode_atts(
			array(
				'start_date' => current_time( 'Y-m-d' ),
				'category'   => '',
				'local_id'   => '',
				'show_nav'   => 'yes',
				'class'      => '',
			),
			$atts,
			'apollo_calendar_week'
		);

		$this->enqueue_assets();

		// Get start of week (Monday)
		$start = new \DateTime( $atts['start_date'] );
		$start->modify( 'monday this week' );

		$events    = $this->get_events_for_week( $start, $atts );
		$week_html = $this->build_week_grid( $start, $events );

		$end = clone $start;
		$end->modify( '+6 days' );

		ob_start();
		?>
		<div class="apollo-calendar apollo-calendar--week <?php echo esc_attr( $atts['class'] ); ?>"
			data-start-date="<?php echo esc_attr( $start->format( 'Y-m-d' ) ); ?>"
			data-category="<?php echo esc_attr( $atts['category'] ); ?>"
			data-local="<?php echo esc_attr( $atts['local_id'] ); ?>">

			<?php if ( $atts['show_nav'] === 'yes' ) : ?>
			<div class="apollo-calendar__header">
				<button type="button" class="apollo-calendar__nav apollo-calendar__nav--prev" aria-label="<?php esc_attr_e( 'Semana anterior', 'apollo-events-manager' ); ?>">
					<i class="ri-arrow-left-s-line"></i>
				</button>
				<h2 class="apollo-calendar__title">
					<?php
					printf(
						'%s - %s',
						esc_html( $start->format( 'd/m' ) ),
						esc_html( $end->format( 'd/m/Y' ) )
					);
					?>
				</h2>
				<button type="button" class="apollo-calendar__nav apollo-calendar__nav--next" aria-label="<?php esc_attr_e( 'Próxima semana', 'apollo-events-manager' ); ?>">
					<i class="ri-arrow-right-s-line"></i>
				</button>
			</div>
			<?php endif; ?>

			<div class="apollo-calendar__grid apollo-calendar__grid--week">
				<?php echo $week_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render mini calendar (sidebar widget style)
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_mini_calendar( $atts ): string {
		$atts = shortcode_atts(
			array(
				'month'    => (int) current_time( 'n' ),
				'year'     => (int) current_time( 'Y' ),
				'category' => '',
				'class'    => '',
			),
			$atts,
			'apollo_mini_calendar'
		);

		$this->enqueue_assets();

		$month = max( 1, min( 12, (int) $atts['month'] ) );
		$year  = (int) $atts['year'];

		$events      = $this->get_events_for_month( $month, $year, $atts );
		$event_dates = $this->get_event_dates( $events );

		ob_start();
		?>
		<div class="apollo-mini-calendar <?php echo esc_attr( $atts['class'] ); ?>"
			data-month="<?php echo esc_attr( $month ); ?>"
			data-year="<?php echo esc_attr( $year ); ?>">

			<div class="apollo-mini-calendar__header">
				<button type="button" class="apollo-mini-calendar__nav" data-dir="prev">
					<i class="ri-arrow-left-s-line"></i>
				</button>
				<span class="apollo-mini-calendar__title">
					<?php echo esc_html( $this->months_pt[ $month ] . ' ' . $year ); ?>
				</span>
				<button type="button" class="apollo-mini-calendar__nav" data-dir="next">
					<i class="ri-arrow-right-s-line"></i>
				</button>
			</div>

			<div class="apollo-mini-calendar__weekdays">
				<?php foreach ( $this->weekdays_pt as $day ) : ?>
					<span><?php echo esc_html( $day ); ?></span>
				<?php endforeach; ?>
			</div>

			<div class="apollo-mini-calendar__days">
				<?php echo $this->build_mini_grid( $month, $year, $event_dates ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get events for a month
	 *
	 * @param int   $month Month number.
	 * @param int   $year  Year.
	 * @param array $atts  Shortcode attributes.
	 * @return array
	 */
	private function get_events_for_month( int $month, int $year, array $atts ): array {
		$first_day = sprintf( '%04d-%02d-01', $year, $month );
		$last_day  = gmdate( 'Y-m-t', strtotime( $first_day ) );

		return $this->query_events( $first_day, $last_day, $atts );
	}

	/**
	 * Get events for a week
	 *
	 * @param \DateTime $start Start date.
	 * @param array     $atts  Shortcode attributes.
	 * @return array
	 */
	private function get_events_for_week( \DateTime $start, array $atts ): array {
		$end = clone $start;
		$end->modify( '+6 days' );

		return $this->query_events( $start->format( 'Y-m-d' ), $end->format( 'Y-m-d' ), $atts );
	}

	/**
	 * Query events by date range
	 *
	 * @param string $from Start date.
	 * @param string $to   End date.
	 * @param array  $atts Shortcode attributes.
	 * @return array
	 */
	private function query_events( string $from, string $to, array $atts ): array {
		$args = array(
			'post_type'      => 'event_listing',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'meta_query'     => array(
				array(
					'key'     => '_event_start_date',
					'value'   => array( $from, $to ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
			'orderby'        => 'meta_value',
			'meta_key'       => '_event_start_date',
			'order'          => 'ASC',
		);

		// Category filter
		if ( ! empty( $atts['category'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'event_listing_category',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $atts['category'] ),
				),
			);
		}

		// Local filter
		if ( ! empty( $atts['local_id'] ) ) {
			$args['meta_query'][] = array(
				'key'     => '_event_local_ids',
				'value'   => absint( $atts['local_id'] ),
				'compare' => '=',
			);
		}

		$query = new \WP_Query( $args );

		$events = array();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post       = get_post();
				$start_date = get_post_meta( $post->ID, '_event_start_date', true );

				$events[] = array(
					'id'         => $post->ID,
					'title'      => $post->post_title,
					'permalink'  => get_permalink( $post->ID ),
					'start_date' => $start_date,
					'start_time' => get_post_meta( $post->ID, '_event_start_time', true ),
					'thumbnail'  => get_the_post_thumbnail_url( $post->ID, 'thumbnail' ),
				);
			}
			wp_reset_postdata();
		}

		return $events;
	}

	/**
	 * Build month grid HTML
	 *
	 * @param int   $month       Month number.
	 * @param int   $year        Year.
	 * @param array $events      Events array.
	 * @param int   $event_limit Max events to show per day.
	 * @return string
	 */
	private function build_month_grid( int $month, int $year, array $events, int $event_limit ): string {
		$first_day_of_month = mktime( 0, 0, 0, $month, 1, $year );
		$days_in_month      = (int) gmdate( 't', $first_day_of_month );
		$first_weekday      = (int) gmdate( 'w', $first_day_of_month ); // 0 = Sunday

		// Group events by date
		$events_by_date = array();
		foreach ( $events as $event ) {
			$date = $event['start_date'];
			if ( ! isset( $events_by_date[ $date ] ) ) {
				$events_by_date[ $date ] = array();
			}
			$events_by_date[ $date ][] = $event;
		}

		$today = current_time( 'Y-m-d' );

		ob_start();

		// Weekday headers
		?>
		<div class="apollo-calendar__row apollo-calendar__row--header">
			<?php foreach ( $this->weekdays_pt as $day ) : ?>
				<div class="apollo-calendar__cell apollo-calendar__cell--header"><?php echo esc_html( $day ); ?></div>
			<?php endforeach; ?>
		</div>
		<?php

		// Days grid
		$current_day = 1;
		$total_cells = $first_weekday + $days_in_month;
		$rows        = ceil( $total_cells / 7 );

		for ( $row = 0; $row < $rows; $row++ ) :
			?>
			<div class="apollo-calendar__row">
				<?php
				for ( $col = 0; $col < 7; $col++ ) :
					$cell_index       = $row * 7 + $col;
					$is_current_month = $cell_index >= $first_weekday && $current_day <= $days_in_month;

					if ( $is_current_month ) :
						$date_str   = sprintf( '%04d-%02d-%02d', $year, $month, $current_day );
						$day_events = $events_by_date[ $date_str ] ?? array();
						$is_today   = $date_str === $today;
						$has_events = ! empty( $day_events );
						?>
						<div class="apollo-calendar__cell apollo-calendar__cell--day <?php echo $is_today ? 'is-today' : ''; ?> <?php echo $has_events ? 'has-events' : ''; ?>">
							<span class="apollo-calendar__day-num"><?php echo esc_html( $current_day ); ?></span>

							<?php if ( $has_events ) : ?>
							<div class="apollo-calendar__events">
								<?php
								$shown = 0;
								foreach ( $day_events as $event ) :
									if ( $shown >= $event_limit ) :
										$remaining = count( $day_events ) - $shown;
										?>
										<a href="<?php echo esc_url( home_url( '/eventos/?date=' . $date_str ) ); ?>" class="apollo-calendar__more">
											+<?php echo esc_html( $remaining ); ?> <?php esc_html_e( 'mais', 'apollo-events-manager' ); ?>
										</a>
										<?php
										break;
									endif;
									?>
									<a href="<?php echo esc_url( $event['permalink'] ); ?>" class="apollo-calendar__event">
										<?php if ( $event['start_time'] ) : ?>
											<span class="apollo-calendar__event-time"><?php echo esc_html( $event['start_time'] ); ?></span>
										<?php endif; ?>
										<span class="apollo-calendar__event-title"><?php echo esc_html( wp_trim_words( $event['title'], 4 ) ); ?></span>
									</a>
									<?php
									++$shown;
								endforeach;
								?>
							</div>
							<?php endif; ?>
						</div>
						<?php
						++$current_day;
					else :
						?>
						<div class="apollo-calendar__cell apollo-calendar__cell--empty"></div>
						<?php
					endif;
				endfor;
				?>
			</div>
			<?php
		endfor;

		return ob_get_clean();
	}

	/**
	 * Build week grid HTML
	 *
	 * @param \DateTime $start  Start date (Monday).
	 * @param array     $events Events array.
	 * @return string
	 */
	private function build_week_grid( \DateTime $start, array $events ): string {
		// Group events by date
		$events_by_date = array();
		foreach ( $events as $event ) {
			$date = $event['start_date'];
			if ( ! isset( $events_by_date[ $date ] ) ) {
				$events_by_date[ $date ] = array();
			}
			$events_by_date[ $date ][] = $event;
		}

		$today = current_time( 'Y-m-d' );

		ob_start();

		for ( $i = 0; $i < 7; $i++ ) :
			$current = clone $start;
			$current->modify( "+{$i} days" );
			$date_str   = $current->format( 'Y-m-d' );
			$day_events = $events_by_date[ $date_str ] ?? array();
			$is_today   = $date_str === $today;
			?>
			<div class="apollo-calendar__week-day <?php echo $is_today ? 'is-today' : ''; ?>">
				<div class="apollo-calendar__week-day-header">
					<span class="apollo-calendar__week-day-name"><?php echo esc_html( $this->weekdays_pt[ (int) $current->format( 'w' ) ] ); ?></span>
					<span class="apollo-calendar__week-day-num"><?php echo esc_html( $current->format( 'd' ) ); ?></span>
				</div>
				<div class="apollo-calendar__week-day-events">
					<?php if ( empty( $day_events ) ) : ?>
						<p class="apollo-calendar__no-events"><?php esc_html_e( 'Sem eventos', 'apollo-events-manager' ); ?></p>
					<?php else : ?>
						<?php foreach ( $day_events as $event ) : ?>
							<a href="<?php echo esc_url( $event['permalink'] ); ?>" class="apollo-calendar__event apollo-calendar__event--full">
								<?php if ( $event['thumbnail'] ) : ?>
									<img src="<?php echo esc_url( $event['thumbnail'] ); ?>" alt="" class="apollo-calendar__event-thumb">
								<?php endif; ?>
								<div class="apollo-calendar__event-info">
									<?php if ( $event['start_time'] ) : ?>
										<span class="apollo-calendar__event-time"><?php echo esc_html( $event['start_time'] ); ?></span>
									<?php endif; ?>
									<span class="apollo-calendar__event-title"><?php echo esc_html( $event['title'] ); ?></span>
								</div>
							</a>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
			<?php
		endfor;

		return ob_get_clean();
	}

	/**
	 * Build mini calendar grid
	 *
	 * @param int   $month       Month number.
	 * @param int   $year        Year.
	 * @param array $event_dates Array of dates with events.
	 * @return string
	 */
	private function build_mini_grid( int $month, int $year, array $event_dates ): string {
		$first_day_of_month = mktime( 0, 0, 0, $month, 1, $year );
		$days_in_month      = (int) gmdate( 't', $first_day_of_month );
		$first_weekday      = (int) gmdate( 'w', $first_day_of_month );

		$today = current_time( 'Y-m-d' );

		ob_start();

		// Empty cells for days before month starts
		for ( $i = 0; $i < $first_weekday; $i++ ) {
			echo '<span class="apollo-mini-calendar__day apollo-mini-calendar__day--empty"></span>';
		}

		// Days
		for ( $day = 1; $day <= $days_in_month; $day++ ) {
			$date_str   = sprintf( '%04d-%02d-%02d', $year, $month, $day );
			$is_today   = $date_str === $today;
			$has_events = in_array( $date_str, $event_dates, true );

			$classes = 'apollo-mini-calendar__day';
			if ( $is_today ) {
				$classes .= ' is-today';
			}
			if ( $has_events ) {
				$classes .= ' has-events';
			}

			if ( $has_events ) {
				printf(
					'<a href="%s" class="%s">%d</a>',
					esc_url( home_url( '/eventos/?date=' . $date_str ) ),
					esc_attr( $classes ),
					$day
				);
			} else {
				printf(
					'<span class="%s">%d</span>',
					esc_attr( $classes ),
					$day
				);
			}
		}

		return ob_get_clean();
	}

	/**
	 * Get event dates from events array
	 *
	 * @param array $events Events array.
	 * @return array
	 */
	private function get_event_dates( array $events ): array {
		$dates = array();
		foreach ( $events as $event ) {
			$dates[] = $event['start_date'];
		}
		return array_unique( $dates );
	}

	/**
	 * AJAX handler for calendar navigation
	 *
	 * @return void
	 */
	public function ajax_navigate(): void {
		check_ajax_referer( 'apollo_calendar_nonce', 'nonce' );

		$month    = absint( $_POST['month'] ?? current_time( 'n' ) );
		$year     = absint( $_POST['year'] ?? current_time( 'Y' ) );
		$category = sanitize_text_field( $_POST['category'] ?? '' );
		$local_id = absint( $_POST['local_id'] ?? 0 );
		$type     = sanitize_key( $_POST['type'] ?? 'month' );

		$atts = array(
			'category' => $category,
			'local_id' => $local_id,
		);

		if ( $type === 'week' ) {
			$start  = new \DateTime( $_POST['start_date'] ?? current_time( 'Y-m-d' ) );
			$events = $this->get_events_for_week( $start, $atts );
			$html   = $this->build_week_grid( $start, $events );

			$end = clone $start;
			$end->modify( '+6 days' );

			wp_send_json_success(
				array(
					'html'  => $html,
					'title' => $start->format( 'd/m' ) . ' - ' . $end->format( 'd/m/Y' ),
				)
			);
		} else {
			$events = $this->get_events_for_month( $month, $year, $atts );
			$html   = $this->build_month_grid( $month, $year, $events, 3 );

			wp_send_json_success(
				array(
					'html'  => $html,
					'title' => $this->months_pt[ $month ] . ' ' . $year,
				)
			);
		}
	}

	/**
	 * Get settings schema
	 *
	 * @return array
	 */
	public function get_settings_schema(): array {
		return array(
			'default_view' => array(
				'type'    => 'select',
				'label'   => __( 'Visualização padrão', 'apollo-events-manager' ),
				'options' => array(
					'month' => __( 'Mensal', 'apollo-events-manager' ),
					'week'  => __( 'Semanal', 'apollo-events-manager' ),
				),
				'default' => 'month',
			),
			'event_limit'  => array(
				'type'    => 'number',
				'label'   => __( 'Limite de eventos por dia', 'apollo-events-manager' ),
				'default' => 3,
				'min'     => 1,
				'max'     => 10,
			),
			'week_starts'  => array(
				'type'    => 'select',
				'label'   => __( 'Semana começa em', 'apollo-events-manager' ),
				'options' => array(
					'0' => __( 'Domingo', 'apollo-events-manager' ),
					'1' => __( 'Segunda-feira', 'apollo-events-manager' ),
				),
				'default' => '0',
			),
		);
	}
}
