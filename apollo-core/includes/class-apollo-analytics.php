<?php
/**
 * Apollo Advanced Analytics
 *
 * Self-hosted analytics system with no external dependencies.
 * Tracks user behavior, interactions, and content performance.
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apollo Analytics Class
 *
 * Handles all analytics tracking, data collection, and statistics retrieval.
 * No external services - 100% self-hosted.
 */
class Analytics {

	/**
	 * Session cookie name
	 */
	private const SESSION_COOKIE = 'apollo_session_id';

	/**
	 * Session duration in seconds (30 minutes)
	 */
	private const SESSION_DURATION = 1800;

	/**
	 * Current session ID
	 */
	private static ?string $session_id = null;

	/**
	 * Initialize analytics
	 */
	public static function init(): void {
		// Start session tracking.
		add_action( 'init', array( __CLASS__, 'start_session' ), 1 );

		// Enqueue tracking scripts.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_tracking_scripts' ), 100 );

		// AJAX endpoints for tracking.
		add_action( 'wp_ajax_apollo_track_pageview', array( __CLASS__, 'ajax_track_pageview' ) );
		add_action( 'wp_ajax_nopriv_apollo_track_pageview', array( __CLASS__, 'ajax_track_pageview' ) );
		add_action( 'wp_ajax_apollo_track_interaction', array( __CLASS__, 'ajax_track_interaction' ) );
		add_action( 'wp_ajax_nopriv_apollo_track_interaction', array( __CLASS__, 'ajax_track_interaction' ) );
		add_action( 'wp_ajax_apollo_track_session_end', array( __CLASS__, 'ajax_track_session_end' ) );
		add_action( 'wp_ajax_nopriv_apollo_track_session_end', array( __CLASS__, 'ajax_track_session_end' ) );
		add_action( 'wp_ajax_apollo_track_heatmap', array( __CLASS__, 'ajax_track_heatmap' ) );
		add_action( 'wp_ajax_nopriv_apollo_track_heatmap', array( __CLASS__, 'ajax_track_heatmap' ) );

		// AJAX endpoint for realtime stats (admin only).
		add_action( 'wp_ajax_apollo_get_realtime_stats', array( __CLASS__, 'ajax_get_realtime_stats' ) );

		// AJAX endpoint for export (admin only).
		add_action( 'wp_ajax_apollo_export_analytics', array( __CLASS__, 'ajax_export_analytics' ) );

		// Daily aggregation cron.
		add_action( 'apollo_analytics_daily_aggregate', array( __CLASS__, 'run_daily_aggregation' ) );
		if ( ! wp_next_scheduled( 'apollo_analytics_daily_aggregate' ) ) {
			wp_schedule_event( strtotime( 'tomorrow 3:00am' ), 'daily', 'apollo_analytics_daily_aggregate' );
		}

		// Admin hooks.
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
	}

	/**
	 * Start or continue session
	 */
	public static function start_session(): void {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		// Check for existing session cookie.
		if ( isset( $_COOKIE[ self::SESSION_COOKIE ] ) ) {
			self::$session_id = sanitize_text_field( wp_unslash( $_COOKIE[ self::SESSION_COOKIE ] ) );
		} else {
			// Generate new session ID.
			self::$session_id = self::generate_session_id();
			setcookie(
				self::SESSION_COOKIE,
				self::$session_id,
				time() + self::SESSION_DURATION,
				COOKIEPATH,
				COOKIE_DOMAIN,
				is_ssl(),
				true
			);
		}
	}

	/**
	 * Generate unique session ID
	 */
	private static function generate_session_id(): string {
		return bin2hex( random_bytes( 16 ) ) . '_' . time();
	}

	/**
	 * Get current session ID
	 */
	public static function get_session_id(): string {
		if ( null === self::$session_id ) {
			self::$session_id = self::generate_session_id();
		}
		return self::$session_id;
	}

	/**
	 * Enqueue tracking scripts
	 */
	public static function enqueue_tracking_scripts(): void {
		// Check if tracking is enabled.
		if ( ! self::is_tracking_enabled() ) {
			return;
		}

		// Don't track admins if setting is off.
		if ( current_user_can( 'manage_options' ) && ! self::track_admins() ) {
			return;
		}

		wp_enqueue_script(
			'apollo-analytics-tracker',
			APOLLO_CORE_PLUGIN_URL . 'assets/js/analytics-tracker.js',
			array(),
			filemtime( APOLLO_CORE_PLUGIN_DIR . 'assets/js/analytics-tracker.js' ),
			true
		);

		// Pass config to JS.
		wp_localize_script(
			'apollo-analytics-tracker',
			'apolloAnalytics',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'apollo_analytics_nonce' ),
				'sessionId'       => self::get_session_id(),
				'userId'          => get_current_user_id(),
				'pageType'        => self::get_page_type(),
				'postId'          => get_queried_object_id(),
				'trackScrollDepth' => true,
				'trackClicks'     => true,
				'trackMouseMove'  => self::is_heatmap_enabled(),
				'trackFormFocus'  => true,
				'scrollThreshold' => 25, // Track at 25%, 50%, 75%, 100%.
				'heartbeatInterval' => 30, // Seconds.
				'batchSize'       => 10, // Batch interactions before sending.
				'isDebug'         => defined( 'WP_DEBUG' ) && WP_DEBUG,
			)
		);
	}

	/**
	 * Check if tracking is enabled
	 */
	public static function is_tracking_enabled(): bool {
		return (bool) get_option( 'apollo_analytics_enabled', true );
	}

	/**
	 * Check if heatmap tracking is enabled
	 */
	public static function is_heatmap_enabled(): bool {
		return (bool) get_option( 'apollo_analytics_heatmap_enabled', false );
	}

	/**
	 * Check if admins should be tracked
	 */
	public static function track_admins(): bool {
		return (bool) get_option( 'apollo_analytics_track_admins', false );
	}

	/**
	 * Get current page type
	 */
	private static function get_page_type(): string {
		if ( is_singular( 'event_listing' ) ) {
			return 'event';
		}
		if ( is_singular( 'apollo_venue' ) ) {
			return 'venue';
		}
		if ( is_singular( 'post' ) ) {
			return 'post';
		}
		if ( is_singular( 'page' ) ) {
			return 'page';
		}
		if ( is_author() ) {
			return 'profile';
		}
		if ( is_archive() ) {
			return 'archive';
		}
		if ( is_search() ) {
			return 'search';
		}
		if ( is_home() || is_front_page() ) {
			return 'home';
		}
		return 'other';
	}

	/**
	 * AJAX: Track pageview
	 */
	public static function ajax_track_pageview(): void {
		check_ajax_referer( 'apollo_analytics_nonce', 'nonce' );

		global $wpdb;

		$data = array(
			'session_id'      => sanitize_text_field( wp_unslash( $_POST['session_id'] ?? '' ) ),
			'user_id'         => absint( $_POST['user_id'] ?? 0 ),
			'page_url'        => esc_url_raw( wp_unslash( $_POST['page_url'] ?? '' ) ),
			'page_title'      => sanitize_text_field( wp_unslash( $_POST['page_title'] ?? '' ) ),
			'page_type'       => sanitize_text_field( wp_unslash( $_POST['page_type'] ?? 'page' ) ),
			'post_id'         => absint( $_POST['post_id'] ?? 0 ),
			'referrer'        => esc_url_raw( wp_unslash( $_POST['referrer'] ?? '' ) ),
			'user_agent'      => sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ),
			'device_type'     => self::detect_device_type(),
			'browser'         => self::detect_browser(),
			'os'              => self::detect_os(),
			'screen_width'    => absint( $_POST['screen_width'] ?? 0 ),
			'screen_height'   => absint( $_POST['screen_height'] ?? 0 ),
			'viewport_width'  => absint( $_POST['viewport_width'] ?? 0 ),
			'viewport_height' => absint( $_POST['viewport_height'] ?? 0 ),
			'ip_hash'         => self::hash_ip(),
			'created_at'      => current_time( 'mysql', 1 ),
		);

		$table = $wpdb->prefix . 'apollo_analytics_pageviews';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert( $table, $data );
		$pageview_id = $wpdb->insert_id;

		// Update or create session.
		self::update_session( $data, $pageview_id );

		wp_send_json_success( array( 'pageview_id' => $pageview_id ) );
	}

	/**
	 * AJAX: Track interaction
	 */
	public static function ajax_track_interaction(): void {
		check_ajax_referer( 'apollo_analytics_nonce', 'nonce' );

		global $wpdb;

		$interactions = isset( $_POST['interactions'] ) ? json_decode( wp_unslash( $_POST['interactions'] ), true ) : array();

		if ( empty( $interactions ) ) {
			wp_send_json_error( 'No interactions' );
		}

		$table = $wpdb->prefix . 'apollo_analytics_interactions';
		$count = 0;

		foreach ( $interactions as $interaction ) {
			$data = array(
				'session_id'       => sanitize_text_field( $interaction['session_id'] ?? '' ),
				'pageview_id'      => absint( $interaction['pageview_id'] ?? 0 ),
				'user_id'          => absint( $interaction['user_id'] ?? 0 ),
				'interaction_type' => sanitize_text_field( $interaction['type'] ?? 'click' ),
				'element_tag'      => sanitize_text_field( $interaction['element_tag'] ?? '' ),
				'element_id'       => sanitize_text_field( $interaction['element_id'] ?? '' ),
				'element_class'    => sanitize_text_field( $interaction['element_class'] ?? '' ),
				'element_text'     => sanitize_text_field( mb_substr( $interaction['element_text'] ?? '', 0, 500 ) ),
				'element_href'     => esc_url_raw( $interaction['element_href'] ?? '' ),
				'position_x'       => absint( $interaction['position_x'] ?? 0 ),
				'position_y'       => absint( $interaction['position_y'] ?? 0 ),
				'scroll_depth'     => absint( $interaction['scroll_depth'] ?? 0 ),
				'scroll_direction' => in_array( $interaction['scroll_direction'] ?? '', array( 'up', 'down' ), true ) ? $interaction['scroll_direction'] : null,
				'viewport_percent' => absint( $interaction['viewport_percent'] ?? 0 ),
				'time_on_page'     => absint( $interaction['time_on_page'] ?? 0 ),
				'extra_data'       => isset( $interaction['extra_data'] ) ? wp_json_encode( $interaction['extra_data'] ) : null,
				'created_at'       => current_time( 'mysql', 1 ),
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert( $table, $data );
			++$count;
		}

		// Update session interaction count.
		self::increment_session_interactions( sanitize_text_field( $_POST['session_id'] ?? '' ), $count );

		wp_send_json_success( array( 'tracked' => $count ) );
	}

	/**
	 * AJAX: Track session end
	 */
	public static function ajax_track_session_end(): void {
		check_ajax_referer( 'apollo_analytics_nonce', 'nonce' );

		global $wpdb;

		$session_id      = sanitize_text_field( wp_unslash( $_POST['session_id'] ?? '' ) );
		$exit_page       = esc_url_raw( wp_unslash( $_POST['exit_page'] ?? '' ) );
		$total_time      = absint( $_POST['total_time'] ?? 0 );
		$max_scroll      = absint( $_POST['max_scroll'] ?? 0 );
		$pageviews_count = absint( $_POST['pageviews_count'] ?? 1 );

		$table = $wpdb->prefix . 'apollo_analytics_sessions';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			array(
				'exit_page'          => $exit_page,
				'total_time_seconds' => $total_time,
				'max_scroll_depth'   => $max_scroll,
				'pageviews_count'    => $pageviews_count,
				'is_bounce'          => $pageviews_count <= 1 ? 1 : 0,
				'ended_at'           => current_time( 'mysql', 1 ),
			),
			array( 'session_id' => $session_id ),
			array( '%s', '%d', '%d', '%d', '%d', '%s' ),
			array( '%s' )
		);

		wp_send_json_success();
	}

	/**
	 * AJAX: Track heatmap data
	 */
	public static function ajax_track_heatmap(): void {
		check_ajax_referer( 'apollo_analytics_nonce', 'nonce' );

		if ( ! self::is_heatmap_enabled() ) {
			wp_send_json_error( 'Heatmap disabled' );
		}

		global $wpdb;

		$points = isset( $_POST['points'] ) ? json_decode( wp_unslash( $_POST['points'] ), true ) : array();

		if ( empty( $points ) ) {
			wp_send_json_error( 'No points' );
		}

		$page_url  = esc_url_raw( wp_unslash( $_POST['page_url'] ?? '' ) );
		$page_hash = md5( $page_url );
		$stat_date = current_time( 'Y-m-d' );

		$table = $wpdb->prefix . 'apollo_analytics_heatmap';

		foreach ( $points as $point ) {
			$x_percent   = round( floatval( $point['x_percent'] ?? 0 ), 2 );
			$y_percent   = round( floatval( $point['y_percent'] ?? 0 ), 2 );
			$type        = sanitize_text_field( $point['type'] ?? 'click' );
			$device_type = self::detect_device_type();

			// Try to update existing point, or insert new.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM $table WHERE page_url_hash = %s AND position_x_percent = %f AND position_y_percent = %f AND interaction_type = %s AND device_type = %s AND stat_date = %s",
					$page_hash,
					$x_percent,
					$y_percent,
					$type,
					$device_type,
					$stat_date
				)
			);

			if ( $existing ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE $table SET count = count + 1 WHERE id = %d",
						$existing
					)
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->insert(
					$table,
					array(
						'page_url_hash'      => $page_hash,
						'page_url'           => $page_url,
						'position_x_percent' => $x_percent,
						'position_y_percent' => $y_percent,
						'interaction_type'   => $type,
						'count'              => 1,
						'device_type'        => $device_type,
						'stat_date'          => $stat_date,
					)
				);
			}
		}

		wp_send_json_success();
	}

	/**
	 * Update or create session
	 */
	private static function update_session( array $pageview_data, int $pageview_id ): void {
		global $wpdb;

		$table      = $wpdb->prefix . 'apollo_analytics_sessions';
		$session_id = $pageview_data['session_id'];

		// Check if session exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $table WHERE session_id = %s",
				$session_id
			)
		);

		// Parse UTM parameters from referrer or URL.
		$utm = self::parse_utm_params( $pageview_data['referrer'] ?: $pageview_data['page_url'] );

		if ( $existing ) {
			// Update pageview count.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE $table SET pageviews_count = pageviews_count + 1 WHERE session_id = %s",
					$session_id
				)
			);
		} else {
			// Create new session.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$table,
				array(
					'session_id'        => $session_id,
					'user_id'           => $pageview_data['user_id'],
					'first_pageview_id' => $pageview_id,
					'landing_page'      => $pageview_data['page_url'],
					'referrer'          => $pageview_data['referrer'],
					'utm_source'        => $utm['source'],
					'utm_medium'        => $utm['medium'],
					'utm_campaign'      => $utm['campaign'],
					'utm_term'          => $utm['term'],
					'utm_content'       => $utm['content'],
					'device_type'       => $pageview_data['device_type'],
					'browser'           => $pageview_data['browser'],
					'os'                => $pageview_data['os'],
					'ip_hash'           => $pageview_data['ip_hash'],
					'pageviews_count'   => 1,
					'started_at'        => current_time( 'mysql', 1 ),
				)
			);
		}
	}

	/**
	 * Increment session interactions count
	 */
	private static function increment_session_interactions( string $session_id, int $count ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'apollo_analytics_sessions';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $table SET interactions_count = interactions_count + %d WHERE session_id = %s",
				$count,
				$session_id
			)
		);
	}

	/**
	 * Parse UTM parameters from URL
	 */
	private static function parse_utm_params( string $url ): array {
		$params = array(
			'source'   => '',
			'medium'   => '',
			'campaign' => '',
			'term'     => '',
			'content'  => '',
		);

		$query = wp_parse_url( $url, PHP_URL_QUERY );
		if ( ! $query ) {
			return $params;
		}

		parse_str( $query, $parsed );

		$params['source']   = sanitize_text_field( $parsed['utm_source'] ?? '' );
		$params['medium']   = sanitize_text_field( $parsed['utm_medium'] ?? '' );
		$params['campaign'] = sanitize_text_field( $parsed['utm_campaign'] ?? '' );
		$params['term']     = sanitize_text_field( $parsed['utm_term'] ?? '' );
		$params['content']  = sanitize_text_field( $parsed['utm_content'] ?? '' );

		return $params;
	}

	/**
	 * Hash IP for privacy
	 */
	private static function hash_ip(): string {
		$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		// Add daily salt for rotation.
		$salt = wp_salt( 'auth' ) . gmdate( 'Y-m-d' );
		return hash( 'sha256', $ip . $salt );
	}

	/**
	 * Detect device type from user agent
	 */
	private static function detect_device_type(): string {
		$ua = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );

		if ( preg_match( '/Mobile|Android.*Mobile|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i', $ua ) ) {
			return 'mobile';
		}
		if ( preg_match( '/iPad|Android(?!.*Mobile)|Tablet/i', $ua ) ) {
			return 'tablet';
		}
		return 'desktop';
	}

	/**
	 * Detect browser from user agent
	 */
	private static function detect_browser(): string {
		$ua = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );

		if ( strpos( $ua, 'Firefox' ) !== false ) {
			return 'Firefox';
		}
		if ( strpos( $ua, 'Edg' ) !== false ) {
			return 'Edge';
		}
		if ( strpos( $ua, 'Chrome' ) !== false ) {
			return 'Chrome';
		}
		if ( strpos( $ua, 'Safari' ) !== false ) {
			return 'Safari';
		}
		if ( strpos( $ua, 'Opera' ) !== false || strpos( $ua, 'OPR' ) !== false ) {
			return 'Opera';
		}
		if ( strpos( $ua, 'MSIE' ) !== false || strpos( $ua, 'Trident' ) !== false ) {
			return 'IE';
		}
		return 'Other';
	}

	/**
	 * Detect OS from user agent
	 */
	private static function detect_os(): string {
		$ua = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );

		if ( strpos( $ua, 'Windows' ) !== false ) {
			return 'Windows';
		}
		if ( strpos( $ua, 'Mac OS' ) !== false ) {
			return 'macOS';
		}
		if ( strpos( $ua, 'Linux' ) !== false ) {
			return 'Linux';
		}
		if ( strpos( $ua, 'Android' ) !== false ) {
			return 'Android';
		}
		if ( strpos( $ua, 'iOS' ) !== false || strpos( $ua, 'iPhone' ) !== false || strpos( $ua, 'iPad' ) !== false ) {
			return 'iOS';
		}
		return 'Other';
	}

	/**
	 * Run daily aggregation for stats
	 */
	public static function run_daily_aggregation(): void {
		global $wpdb;

		$yesterday = gmdate( 'Y-m-d', strtotime( '-1 day' ) );

		// Aggregate user stats.
		self::aggregate_user_stats( $yesterday );

		// Aggregate content stats.
		self::aggregate_content_stats( $yesterday );

		// Cleanup old raw data (keep 90 days).
		self::cleanup_old_data( 90 );
	}

	/**
	 * Aggregate user stats for a date
	 */
	private static function aggregate_user_stats( string $date ): void {
		global $wpdb;

		$pageviews_table    = $wpdb->prefix . 'apollo_analytics_pageviews';
		$interactions_table = $wpdb->prefix . 'apollo_analytics_interactions';
		$user_stats_table   = $wpdb->prefix . 'apollo_analytics_user_stats';

		// Get all users who have content viewed on this date.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$authors = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT p.post_author
				FROM $pageviews_table pv
				JOIN {$wpdb->posts} p ON pv.post_id = p.ID
				WHERE DATE(pv.created_at) = %s AND pv.post_id > 0",
				$date
			)
		);

		foreach ( $authors as $author_id ) {
			$author_id = absint( $author_id );

			// Get profile views.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$profile_views = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $pageviews_table WHERE page_type = 'profile' AND post_id = %d AND DATE(created_at) = %s",
					$author_id,
					$date
				)
			);

			// Get event views.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$event_views = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $pageviews_table pv
					JOIN {$wpdb->posts} p ON pv.post_id = p.ID
					WHERE p.post_author = %d AND p.post_type = 'event_listing' AND DATE(pv.created_at) = %s",
					$author_id,
					$date
				)
			);

			// Get post views.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$post_views = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $pageviews_table pv
					JOIN {$wpdb->posts} p ON pv.post_id = p.ID
					WHERE p.post_author = %d AND p.post_type = 'post' AND DATE(pv.created_at) = %s",
					$author_id,
					$date
				)
			);

			// Get unique visitors.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$unique_visitors = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT session_id) FROM $pageviews_table pv
					JOIN {$wpdb->posts} p ON pv.post_id = p.ID
					WHERE p.post_author = %d AND DATE(pv.created_at) = %s",
					$author_id,
					$date
				)
			);

			// Insert or update.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM $user_stats_table WHERE user_id = %d AND stat_date = %s",
					$author_id,
					$date
				)
			);

			if ( $existing ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$user_stats_table,
					array(
						'profile_views'   => $profile_views,
						'event_views'     => $event_views,
						'post_views'      => $post_views,
						'unique_visitors' => $unique_visitors,
					),
					array( 'id' => $existing )
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->insert(
					$user_stats_table,
					array(
						'user_id'         => $author_id,
						'stat_date'       => $date,
						'profile_views'   => $profile_views,
						'event_views'     => $event_views,
						'post_views'      => $post_views,
						'unique_visitors' => $unique_visitors,
					)
				);
			}
		}
	}

	/**
	 * Aggregate content stats for a date
	 */
	private static function aggregate_content_stats( string $date ): void {
		global $wpdb;

		$pageviews_table    = $wpdb->prefix . 'apollo_analytics_pageviews';
		$sessions_table     = $wpdb->prefix . 'apollo_analytics_sessions';
		$content_stats_table = $wpdb->prefix . 'apollo_analytics_content_stats';

		// Get all posts viewed on this date.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pv.post_id, p.post_type, p.post_author,
					COUNT(*) as views,
					COUNT(DISTINCT pv.session_id) as unique_views
				FROM $pageviews_table pv
				JOIN {$wpdb->posts} p ON pv.post_id = p.ID
				WHERE DATE(pv.created_at) = %s AND pv.post_id > 0
				GROUP BY pv.post_id",
				$date
			)
		);

		foreach ( $posts as $post ) {
			// Get average time and scroll depth from sessions.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$session_stats = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT AVG(s.total_time_seconds) as avg_time, AVG(s.max_scroll_depth) as avg_scroll, SUM(s.is_bounce) as bounces
					FROM $sessions_table s
					WHERE s.session_id IN (
						SELECT DISTINCT session_id FROM $pageviews_table WHERE post_id = %d AND DATE(created_at) = %s
					)",
					$post->post_id,
					$date
				)
			);

			$bounce_rate = $post->views > 0 ? ( ( $session_stats->bounces ?? 0 ) / $post->views ) * 100 : 0;

			// Insert or update.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM $content_stats_table WHERE post_id = %d AND stat_date = %s",
					$post->post_id,
					$date
				)
			);

			$data = array(
				'post_id'           => $post->post_id,
				'post_type'         => $post->post_type,
				'author_id'         => $post->post_author,
				'stat_date'         => $date,
				'views'             => $post->views,
				'unique_views'      => $post->unique_views,
				'avg_time_seconds'  => absint( $session_stats->avg_time ?? 0 ),
				'avg_scroll_depth'  => absint( $session_stats->avg_scroll ?? 0 ),
				'bounce_rate'       => round( $bounce_rate, 2 ),
			);

			if ( $existing ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update( $content_stats_table, $data, array( 'id' => $existing ) );
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->insert( $content_stats_table, $data );
			}
		}
	}

	/**
	 * Cleanup old raw data
	 */
	private static function cleanup_old_data( int $days ): void {
		global $wpdb;

		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// Delete old pageviews.
		$table = $wpdb->prefix . 'apollo_analytics_pageviews';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE created_at < %s", $cutoff ) );

		// Delete old interactions.
		$table = $wpdb->prefix . 'apollo_analytics_interactions';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE created_at < %s", $cutoff ) );

		// Delete old sessions.
		$table = $wpdb->prefix . 'apollo_analytics_sessions';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE started_at < %s", $cutoff ) );
	}

	/**
	 * Register admin menu
	 */
	public static function register_admin_menu(): void {
		add_submenu_page(
			'apollo-core',
			__( 'Advanced Analytics', 'apollo-core' ),
			__( 'Analytics', 'apollo-core' ),
			'manage_options',
			'apollo-analytics',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Render admin page
	 */
	public static function render_admin_page(): void {
		include APOLLO_CORE_PLUGIN_DIR . 'admin/analytics-dashboard.php';
	}

	// =========================================================================.
	// PUBLIC API FOR GETTING STATS.
	// =========================================================================.

	/**
	 * Get user stats for a period
	 *
	 * @param int    $user_id User ID.
	 * @param string $period  Period: 'today', 'week', 'month', 'year', 'all'.
	 * @return array
	 */
	public static function get_user_stats( int $user_id, string $period = 'month' ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'apollo_analytics_user_stats';
		$date_condition = self::get_date_condition( $period );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					SUM(profile_views) as profile_views,
					SUM(event_views) as event_views,
					SUM(post_views) as post_views,
					SUM(total_clicks) as total_clicks,
					SUM(unique_visitors) as unique_visitors,
					SUM(social_shares) as social_shares,
					SUM(likes_received) as likes_received,
					SUM(followers_gained) as followers_gained
				FROM $table
				WHERE user_id = %d $date_condition",
				$user_id
			),
			ARRAY_A
		);

		return $stats ?: array();
	}

	/**
	 * Get content stats for a post
	 *
	 * @param int    $post_id Post ID.
	 * @param string $period  Period.
	 * @return array
	 */
	public static function get_content_stats( int $post_id, string $period = 'month' ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'apollo_analytics_content_stats';
		$date_condition = self::get_date_condition( $period );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					SUM(views) as views,
					SUM(unique_views) as unique_views,
					AVG(avg_time_seconds) as avg_time,
					AVG(avg_scroll_depth) as avg_scroll,
					AVG(bounce_rate) as bounce_rate,
					SUM(shares) as shares,
					SUM(likes) as likes
				FROM $table
				WHERE post_id = %d $date_condition",
				$post_id
			),
			ARRAY_A
		);

		return $stats ?: array();
	}

	/**
	 * Get realtime stats (last 30 minutes)
	 *
	 * @return array
	 */
	public static function get_realtime_stats(): array {
		global $wpdb;

		$table  = $wpdb->prefix . 'apollo_analytics_sessions';
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - 1800 );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$active = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT session_id) FROM $table WHERE started_at > %s OR ended_at IS NULL",
				$cutoff
			)
		);

		$pageviews_table = $wpdb->prefix . 'apollo_analytics_pageviews';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$pageviews = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $pageviews_table WHERE created_at > %s",
				$cutoff
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$top_pages = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT page_url, page_title, COUNT(*) as views
				FROM $pageviews_table
				WHERE created_at > %s
				GROUP BY page_url
				ORDER BY views DESC
				LIMIT 10",
				$cutoff
			)
		);

		return array(
			'active_users'      => (int) $active,
			'pageviews_30min'   => (int) $pageviews,
			'top_pages'         => $top_pages,
		);
	}

	/**
	 * Get date condition for SQL
	 */
	private static function get_date_condition( string $period ): string {
		switch ( $period ) {
			case 'today':
				return "AND stat_date = '" . current_time( 'Y-m-d' ) . "'";
			case 'week':
				return "AND stat_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
			case 'month':
				return "AND stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
			case 'year':
				return "AND stat_date >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)";
			default:
				return '';
		}
	}

	// =========================================================================.
	// USER STATS VISIBILITY CONTROLS.
	// =========================================================================.

	/**
	 * Get user's stats visibility setting
	 *
	 * @param int $user_id User ID.
	 * @return array Visibility settings.
	 */
	public static function get_user_stats_visibility( int $user_id ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'apollo_analytics_settings';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$setting = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT setting_value FROM $table WHERE user_id = %d AND setting_key = 'visibility'",
				$user_id
			)
		);

		$defaults = array(
			'show_profile_views'  => true,
			'show_content_views'  => true,
			'show_engagement'     => false,
			'show_to'             => 'self', // 'self', 'followers', 'public'.
		);

		if ( $setting ) {
			$saved = json_decode( $setting, true );
			return wp_parse_args( $saved, $defaults );
		}

		return $defaults;
	}

	/**
	 * Update user's stats visibility setting
	 *
	 * @param int   $user_id  User ID.
	 * @param array $settings Visibility settings.
	 * @return bool
	 */
	public static function update_user_stats_visibility( int $user_id, array $settings ): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'apollo_analytics_settings';

		// Check admin override.
		$admin_override = get_option( 'apollo_analytics_admin_override', array() );
		if ( ! empty( $admin_override['force_visibility'] ) && ! current_user_can( 'manage_options' ) ) {
			return false; // Admin has locked visibility settings.
		}

		$data = wp_json_encode( $settings );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $table WHERE user_id = %d AND setting_key = 'visibility'",
				$user_id
			)
		);

		if ( $existing ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return (bool) $wpdb->update(
				$table,
				array( 'setting_value' => $data ),
				array( 'id' => $existing )
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return (bool) $wpdb->insert(
			$table,
			array(
				'user_id'       => $user_id,
				'setting_key'   => 'visibility',
				'setting_value' => $data,
			)
		);
	}

	/**
	 * Check if current user can view another user's stats
	 *
	 * @param int $target_user_id The user whose stats are being viewed.
	 * @param int $viewer_user_id The user trying to view (0 for guest).
	 * @return bool
	 */
	public static function can_view_user_stats( int $target_user_id, int $viewer_user_id = 0 ): bool {
		// Admins can always view.
		if ( $viewer_user_id && user_can( $viewer_user_id, 'manage_options' ) ) {
			return true;
		}

		// Users can view their own stats.
		if ( $target_user_id === $viewer_user_id ) {
			return true;
		}

		$visibility = self::get_user_stats_visibility( $target_user_id );

		switch ( $visibility['show_to'] ) {
			case 'public':
				return true;
			case 'followers':
				// Check if viewer follows target.
				if ( function_exists( 'apollo_is_following' ) ) {
					return apollo_is_following( $viewer_user_id, $target_user_id );
				}
				return false;
			case 'self':
			default:
				return false;
		}
	}

	/**
	 * AJAX: Get realtime stats
	 */
	public static function ajax_get_realtime_stats(): void {
		check_ajax_referer( 'apollo_realtime', '_wpnonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		wp_send_json_success( self::get_realtime_stats() );
	}

	/**
	 * AJAX: Export analytics data
	 */
	public static function ajax_export_analytics(): void {
		check_ajax_referer( 'apollo_export', '_wpnonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		global $wpdb;

		$type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : 'csv';

		// Get last 30 days of aggregated data.
		$user_stats_table    = $wpdb->prefix . 'apollo_analytics_user_stats';
		$content_stats_table = $wpdb->prefix . 'apollo_analytics_content_stats';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$user_stats = $wpdb->get_results( "SELECT * FROM $user_stats_table ORDER BY stat_date DESC LIMIT 1000", ARRAY_A );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$content_stats = $wpdb->get_results( "SELECT * FROM $content_stats_table ORDER BY stat_date DESC LIMIT 1000", ARRAY_A );

		$data = array(
			'exported_at'   => current_time( 'mysql' ),
			'user_stats'    => $user_stats,
			'content_stats' => $content_stats,
		);

		$filename = 'apollo-analytics-export-' . gmdate( 'Y-m-d-His' );

		if ( 'json' === $type ) {
			header( 'Content-Type: application/json' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '.json"' );
			echo wp_json_encode( $data, JSON_PRETTY_PRINT );
		} else {
			header( 'Content-Type: text/csv' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '.csv"' );

			$output = fopen( 'php://output', 'w' );

			// User stats.
			fputcsv( $output, array( '--- USER STATS ---' ) );
			if ( ! empty( $user_stats ) ) {
				fputcsv( $output, array_keys( $user_stats[0] ) );
				foreach ( $user_stats as $row ) {
					fputcsv( $output, $row );
				}
			}

			fputcsv( $output, array( '' ) );
			fputcsv( $output, array( '--- CONTENT STATS ---' ) );

			// Content stats.
			if ( ! empty( $content_stats ) ) {
				fputcsv( $output, array_keys( $content_stats[0] ) );
				foreach ( $content_stats as $row ) {
					fputcsv( $output, $row );
				}
			}

			fclose( $output );
		}

		exit;
	}

	/**
	 * Track a custom event from PHP
	 *
	 * @param string $event_type Event type name.
	 * @param int    $user_id    User ID (0 for anonymous).
	 * @param int    $post_id    Related post ID (0 if none).
	 * @param array  $extra_data Additional event data.
	 * @return int|false Insert ID or false.
	 */
	public static function track_event( string $event_type, int $user_id = 0, int $post_id = 0, array $extra_data = array() ): int|false {
		if ( ! self::is_tracking_enabled() ) {
			return false;
		}

		global $wpdb;

		$table = $wpdb->prefix . 'apollo_analytics_interactions';

		$data = array(
			'session_id'       => self::get_session_id(),
			'user_id'          => $user_id ?: get_current_user_id(),
			'interaction_type' => 'custom',
			'extra_data'       => wp_json_encode( array_merge(
				array( 'custom_event_type' => $event_type ),
				$extra_data,
				array( 'post_id' => $post_id )
			) ),
			'created_at'       => current_time( 'mysql', 1 ),
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert( $table, $data );

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Track social interaction (like, share, follow, etc.)
	 *
	 * @param string $action    Action type: like, share, follow, comment.
	 * @param int    $actor_id  User performing the action.
	 * @param int    $target_id Target user or post ID.
	 * @param string $target_type 'user' or 'post'.
	 * @return void
	 */
	public static function track_social_interaction( string $action, int $actor_id, int $target_id, string $target_type = 'user' ): void {
		if ( ! self::is_tracking_enabled() ) {
			return;
		}

		// Update user stats for social interactions.
		if ( 'user' === $target_type ) {
			global $wpdb;
			$user_stats_table = $wpdb->prefix . 'apollo_analytics_user_stats';
			$stat_date        = current_time( 'Y-m-d' );

			// Get or create user stats for today.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM $user_stats_table WHERE user_id = %d AND stat_date = %s",
					$target_id,
					$stat_date
				)
			);

			$column = '';
			switch ( $action ) {
				case 'like':
					$column = 'likes_received';
					break;
				case 'share':
					$column = 'social_shares';
					break;
				case 'follow':
					$column = 'followers_gained';
					break;
				case 'comment':
					$column = 'comments_received';
					break;
			}

			if ( $column ) {
				if ( $existing ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->query(
						$wpdb->prepare(
							"UPDATE $user_stats_table SET $column = $column + 1 WHERE id = %d",
							$existing
						)
					);
				} else {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$wpdb->insert(
						$user_stats_table,
						array(
							'user_id'   => $target_id,
							'stat_date' => $stat_date,
							$column     => 1,
						)
					);
				}
			}
		}

		// Also track as custom event.
		self::track_event( 'social_' . $action, $actor_id, 'user' === $target_type ? 0 : $target_id, array(
			'target_type' => $target_type,
			'target_id'   => $target_id,
		) );
	}
}
