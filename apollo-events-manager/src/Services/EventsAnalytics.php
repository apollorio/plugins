<?php
/**
 * Events Analytics Service
 *
 * Handles analytics tracking and dashboard for Apollo Events Manager.
 * Extracted from the main plugin class to follow SRP.
 *
 * @package Apollo\Events\Services
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo\Events\Services;

/**
 * Manages event view tracking and analytics dashboard.
 */
final class EventsAnalytics {

	/**
	 * Table name for analytics.
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'apollo_event_analytics';
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// View tracking
		add_action( 'template_redirect', array( $this, 'trackEventView' ) );
		if ( ! function_exists( 'apollo_ajax_track_event_view' ) ) {
			add_action( 'wp_ajax_apollo_track_event_view', array( $this, 'trackEventViewOnModal' ) );
			add_action( 'wp_ajax_nopriv_apollo_track_event_view', array( $this, 'trackEventViewOnModal' ) );
		}

		// Admin menu
		add_action( 'admin_menu', array( $this, 'addAnalyticsMenu' ) );

		// REST API
		add_action( 'rest_api_init', array( $this, 'registerRestRoutes' ) );
	}

	/**
	 * Track event view on page load.
	 *
	 * @return void
	 */
	public function trackEventView(): void {
		if ( ! is_singular( 'event_listing' ) ) {
			return;
		}

		$event_id = get_the_ID();
		if ( ! $event_id ) {
			return;
		}

		$this->recordView( $event_id );
	}

	/**
	 * Track event view from modal (AJAX).
	 *
	 * @return void
	 */
	public function trackEventViewOnModal(): void {
		check_ajax_referer( 'apollo_events_nonce', 'nonce' );

		$event_id = absint( $_POST['event_id'] ?? 0 );

		if ( ! $event_id || 'event_listing' !== get_post_type( $event_id ) ) {
			wp_send_json_error();
		}

		$this->recordView( $event_id );

		wp_send_json_success();
	}

	/**
	 * Record a view.
	 *
	 * @param int $event_id Event ID.
	 * @return void
	 */
	private function recordView( int $event_id ): void {
		// Increment simple counter
		$views = (int) get_post_meta( $event_id, '_event_views', true );
		update_post_meta( $event_id, '_event_views', $views + 1 );

		// Record detailed analytics if table exists
		if ( $this->tableExists() ) {
			$this->insertAnalyticsRecord( $event_id );
		}
	}

	/**
	 * Check if analytics table exists.
	 *
	 * @return bool
	 */
	private function tableExists(): bool {
		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $this->table )
		);

		return $result === $this->table;
	}

	/**
	 * Insert analytics record.
	 *
	 * @param int $event_id Event ID.
	 * @return void
	 */
	private function insertAnalyticsRecord( int $event_id ): void {
		global $wpdb;

		$wpdb->insert(
			$this->table,
			array(
				'event_id'   => $event_id,
				'user_id'    => get_current_user_id() ?: null,
				'ip_hash'    => wp_hash( $this->getClientIp() ),
				'user_agent' => sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ),
				'referer'    => esc_url_raw( wp_get_referer() ?: '' ),
				'viewed_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Get client IP (privacy-safe).
	 *
	 * @return string
	 */
	private function getClientIp(): string {
		$ip_keys = array( 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' );

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// Handle comma-separated IPs
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}

	/**
	 * Add analytics submenu.
	 *
	 * @return void
	 */
	public function addAnalyticsMenu(): void {
		add_submenu_page(
			'edit.php?post_type=event_listing',
			__( 'Analytics', 'apollo-events-manager' ),
			__( 'Analytics', 'apollo-events-manager' ),
			'edit_others_posts',
			'apollo-events-analytics',
			array( $this, 'renderDashboard' )
		);
	}

	/**
	 * Render analytics dashboard.
	 *
	 * @return void
	 */
	public function renderDashboard(): void {
		$stats = $this->getOverviewStats();
		$top_events = $this->getTopEvents( 10 );
		$daily_views = $this->getDailyViews( 30 );

		$template_path = APOLLO_APRIO_PATH . 'templates/admin/analytics-dashboard.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			$this->renderDefaultDashboard( $stats, $top_events, $daily_views );
		}
	}

	/**
	 * Get overview statistics.
	 *
	 * @return array<string, int>
	 */
	public function getOverviewStats(): array {
		global $wpdb;

		$total_events = (int) wp_count_posts( 'event_listing' )->publish;

		$total_views = (int) $wpdb->get_var(
			"SELECT SUM( CAST( meta_value AS UNSIGNED ) )
			FROM {$wpdb->postmeta}
			WHERE meta_key = '_event_views'"
		);

		$upcoming_events = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT( DISTINCT p.ID )
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = 'event_listing'
				AND p.post_status = 'publish'
				AND pm.meta_key = '_event_start_date'
				AND pm.meta_value >= %s",
				current_time( 'Y-m-d' )
			)
		);

		$total_favorites = (int) $wpdb->get_var(
			"SELECT SUM( CAST( meta_value AS UNSIGNED ) )
			FROM {$wpdb->postmeta}
			WHERE meta_key = '_favorite_count'"
		);

		return array(
			'total_events'    => $total_events,
			'total_views'     => $total_views,
			'upcoming_events' => $upcoming_events,
			'total_favorites' => $total_favorites,
		);
	}

	/**
	 * Get top events by views.
	 *
	 * @param int $limit Number of events.
	 * @return array<int, array{id: int, title: string, views: int}>
	 */
	public function getTopEvents( int $limit = 10 ): array {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_title, CAST( pm.meta_value AS UNSIGNED ) as views
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = 'event_listing'
				AND p.post_status = 'publish'
				AND pm.meta_key = '_event_views'
				ORDER BY views DESC
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		return array_map(
			function ( $row ) {
				return array(
					'id'    => (int) $row['ID'],
					'title' => $row['post_title'],
					'views' => (int) $row['views'],
				);
			},
			$results ?: array()
		);
	}

	/**
	 * Get daily views for chart.
	 *
	 * @param int $days Number of days.
	 * @return array<string, int>
	 */
	public function getDailyViews( int $days = 30 ): array {
		if ( ! $this->tableExists() ) {
			return array();
		}

		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE( viewed_at ) as date, COUNT(*) as views
				FROM {$this->table}
				WHERE viewed_at >= DATE_SUB( NOW(), INTERVAL %d DAY )
				GROUP BY DATE( viewed_at )
				ORDER BY date ASC",
				$days
			),
			ARRAY_A
		);

		$daily = array();
		foreach ( $results ?: array() as $row ) {
			$daily[ $row['date'] ] = (int) $row['views'];
		}

		return $daily;
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function registerRestRoutes(): void {
		register_rest_route(
			'apollo-events/v1',
			'/analytics/overview',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'restGetOverview' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_others_posts' );
				},
			)
		);

		register_rest_route(
			'apollo-events/v1',
			'/analytics/top-events',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'restGetTopEvents' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_others_posts' );
				},
			)
		);
	}

	/**
	 * REST: Get overview stats.
	 *
	 * @return \WP_REST_Response
	 */
	public function restGetOverview(): \WP_REST_Response {
		return new \WP_REST_Response( $this->getOverviewStats() );
	}

	/**
	 * REST: Get top events.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function restGetTopEvents( \WP_REST_Request $request ): \WP_REST_Response {
		$limit = $request->get_param( 'limit' ) ?: 10;
		return new \WP_REST_Response( $this->getTopEvents( $limit ) );
	}

	/**
	 * Render default dashboard.
	 *
	 * @param array<string, int>                              $stats      Overview stats.
	 * @param array<int, array{id: int, title: string, views: int}> $top_events Top events.
	 * @param array<string, int>                              $daily      Daily views.
	 * @return void
	 */
	private function renderDefaultDashboard( array $stats, array $top_events, array $daily ): void {
		?>
		<div class="wrap apollo-analytics-dashboard">
			<h1><?php esc_html_e( 'Event Analytics', 'apollo-events-manager' ); ?></h1>

			<div class="apollo-stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin: 2rem 0;">
				<div class="apollo-stat-card" style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
					<h3 style="margin: 0; color: #666;"><?php esc_html_e( 'Total Events', 'apollo-events-manager' ); ?></h3>
					<p style="font-size: 2rem; font-weight: bold; margin: 0.5rem 0;"><?php echo esc_html( number_format_i18n( $stats['total_events'] ) ); ?></p>
				</div>
				<div class="apollo-stat-card" style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
					<h3 style="margin: 0; color: #666;"><?php esc_html_e( 'Total Views', 'apollo-events-manager' ); ?></h3>
					<p style="font-size: 2rem; font-weight: bold; margin: 0.5rem 0;"><?php echo esc_html( number_format_i18n( $stats['total_views'] ) ); ?></p>
				</div>
				<div class="apollo-stat-card" style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
					<h3 style="margin: 0; color: #666;"><?php esc_html_e( 'Upcoming', 'apollo-events-manager' ); ?></h3>
					<p style="font-size: 2rem; font-weight: bold; margin: 0.5rem 0;"><?php echo esc_html( number_format_i18n( $stats['upcoming_events'] ) ); ?></p>
				</div>
				<div class="apollo-stat-card" style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
					<h3 style="margin: 0; color: #666;"><?php esc_html_e( 'Favorites', 'apollo-events-manager' ); ?></h3>
					<p style="font-size: 2rem; font-weight: bold; margin: 0.5rem 0;"><?php echo esc_html( number_format_i18n( $stats['total_favorites'] ) ); ?></p>
				</div>
			</div>

			<div class="apollo-top-events" style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
				<h2><?php esc_html_e( 'Top Events', 'apollo-events-manager' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Event', 'apollo-events-manager' ); ?></th>
							<th style="width: 100px;"><?php esc_html_e( 'Views', 'apollo-events-manager' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $top_events as $event ) : ?>
							<tr>
								<td>
									<a href="<?php echo esc_url( get_edit_post_link( $event['id'] ) ); ?>">
										<?php echo esc_html( $event['title'] ); ?>
									</a>
								</td>
								<td><?php echo esc_html( number_format_i18n( $event['views'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}
}
