<?php
/**
 * Events Cron Jobs Service
 *
 * Handles scheduled tasks for Apollo Events Manager.
 * Extracted from the main plugin class to follow SRP.
 *
 * @package Apollo\Events\Services
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo\Events\Services;

/**
 * Manages cron jobs and scheduled tasks for events.
 */
final class EventsCronJobs {

	/**
	 * Cron hook for expired events.
	 */
	public const EXPIRED_EVENTS_HOOK = 'apollo_events_check_expired';

	/**
	 * Cron hook for cache cleanup.
	 */
	public const CACHE_CLEANUP_HOOK = 'apollo_events_cache_cleanup';

	/**
	 * Cron hook for stats aggregation.
	 */
	public const STATS_AGGREGATION_HOOK = 'apollo_events_aggregate_stats';

	/**
	 * Cron hook for legacy stats migration.
	 */
	public const STATS_MIGRATION_HOOK = 'apollo_events_migrate_event_stats';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// Schedule cron events
		add_action( 'init', array( $this, 'scheduleCronEvents' ) );

		// Register cron handlers
		add_action( self::EXPIRED_EVENTS_HOOK, array( $this, 'processExpiredEvents' ) );
		add_action( self::CACHE_CLEANUP_HOOK, array( $this, 'cleanupTransientCache' ) );
		add_action( self::STATS_AGGREGATION_HOOK, array( $this, 'aggregateStats' ) );
		add_action( self::STATS_MIGRATION_HOOK, array( $this, 'migrateEventStats' ) );

		// Deactivation cleanup
		register_deactivation_hook(
			APOLLO_APRIO_PATH . 'apollo-events-manager.php',
			array( $this, 'unscheduleCronEvents' )
		);
	}

	/**
	 * Schedule cron events if not already scheduled.
	 *
	 * @return void
	 */
	public function scheduleCronEvents(): void {
		// Check expired events every hour
		if ( ! wp_next_scheduled( self::EXPIRED_EVENTS_HOOK ) ) {
			wp_schedule_event( time(), 'hourly', self::EXPIRED_EVENTS_HOOK );
		}

		// Cache cleanup daily
		if ( ! wp_next_scheduled( self::CACHE_CLEANUP_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CACHE_CLEANUP_HOOK );
		}

		// Stats aggregation daily at midnight
		if ( ! wp_next_scheduled( self::STATS_AGGREGATION_HOOK ) ) {
			$midnight = strtotime( 'tomorrow midnight' );
			wp_schedule_event( $midnight, 'daily', self::STATS_AGGREGATION_HOOK );
		}

		// Legacy stats migration daily (safe, idempotent)
		if ( ! wp_next_scheduled( self::STATS_MIGRATION_HOOK ) ) {
			$migrate_time = strtotime( 'tomorrow 2:00am' );
			wp_schedule_event( $migrate_time, 'daily', self::STATS_MIGRATION_HOOK );
		}
	}

	/**
	 * Unschedule all cron events.
	 *
	 * @return void
	 */
	public function unscheduleCronEvents(): void {
		wp_clear_scheduled_hook( self::EXPIRED_EVENTS_HOOK );
		wp_clear_scheduled_hook( self::CACHE_CLEANUP_HOOK );
		wp_clear_scheduled_hook( self::STATS_AGGREGATION_HOOK );
		wp_clear_scheduled_hook( self::STATS_MIGRATION_HOOK );
	}

	/**
	 * Process expired events.
	 *
	 * Updates status of events whose end date has passed.
	 *
	 * @return void
	 */
	public function processExpiredEvents(): void {
		global $wpdb;

		// Find published events where end date (or start date if no end) has passed
		$expired_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT p.ID
				FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm_end ON p.ID = pm_end.post_id AND pm_end.meta_key = '_event_end_date'
				LEFT JOIN {$wpdb->postmeta} pm_start ON p.ID = pm_start.post_id AND pm_start.meta_key = '_event_start_date'
				WHERE p.post_type = 'event_listing'
				AND p.post_status = 'publish'
				AND (
					( pm_end.meta_value IS NOT NULL AND pm_end.meta_value != '' AND pm_end.meta_value < %s )
					OR
					( ( pm_end.meta_value IS NULL OR pm_end.meta_value = '' ) AND pm_start.meta_value < %s )
				)
				LIMIT 100",
				current_time( 'Y-m-d' ),
				current_time( 'Y-m-d' )
			)
		);

		if ( empty( $expired_ids ) ) {
			return;
		}

		$processed = 0;

		foreach ( $expired_ids as $event_id ) {
			$event_id = (int) $event_id;

			// Update post meta to mark as expired
			update_post_meta( $event_id, '_event_expired', '1' );
			update_post_meta( $event_id, '_event_expired_at', current_time( 'mysql' ) );

			// Optionally change status to 'past' if using custom status
			// Or add to taxonomy for filtering
			wp_set_object_terms( $event_id, 'past', 'event_listing_status', false );

			// Fire action for other plugins to hook into
			do_action( 'apollo_events_event_expired', $event_id );

			++$processed;
		}

		// Log processing
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && $processed > 0 ) {
			error_log( sprintf( '[Apollo Events] Processed %d expired events', $processed ) );
		}
	}

	/**
	 * Cleanup expired transient cache.
	 *
	 * @return void
	 */
	public function cleanupTransientCache(): void {
		global $wpdb;

		// Delete expired Apollo Events transients
		$wpdb->query(
			$wpdb->prepare(
				"DELETE a, b FROM {$wpdb->options} a
				INNER JOIN {$wpdb->options} b ON b.option_name = REPLACE( a.option_name, '_timeout', '' )
				WHERE a.option_name LIKE %s
				AND a.option_value < %d",
				'_transient_timeout_apollo_events_%',
				time()
			)
		);

		// Also clean up object cache if available
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( 'apollo_events' );
		}
	}

	/**
	 * Aggregate statistics.
	 *
	 * Calculates daily/weekly/monthly stats and stores them.
	 *
	 * @return void
	 */
	public function aggregateStats(): void {
		global $wpdb;

		$analytics_table = $wpdb->prefix . 'apollo_event_analytics';
		$stats_table     = $wpdb->prefix . 'apollo_event_stats_daily';

		// Check if analytics table exists
		$table_exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $analytics_table )
		);

		if ( ! $table_exists ) {
			return;
		}

		// Aggregate yesterday's stats
		$yesterday = wp_date( 'Y-m-d', strtotime( '-1 day' ) );

		$daily_stats = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT event_id,
					COUNT(*) as views,
					COUNT( DISTINCT ip_hash ) as unique_views,
					COUNT( DISTINCT user_id ) as logged_in_views
				FROM {$analytics_table}
				WHERE DATE( viewed_at ) = %s
				GROUP BY event_id",
				$yesterday
			),
			ARRAY_A
		);

		if ( empty( $daily_stats ) ) {
			return;
		}

		// Check if stats table exists, create if not
		$this->maybeCreateStatsTable();

		// Insert aggregated stats
		foreach ( $daily_stats as $stat ) {
			$wpdb->replace(
				$stats_table,
				array(
					'event_id'         => (int) $stat['event_id'],
					'date'             => $yesterday,
					'views'            => (int) $stat['views'],
					'unique_views'     => (int) $stat['unique_views'],
					'logged_in_views'  => (int) $stat['logged_in_views'],
				),
				array( '%d', '%s', '%d', '%d', '%d' )
			);
		}

		// Optionally, clean up old raw analytics data (keep 90 days)
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$analytics_table}
				WHERE viewed_at < DATE_SUB( NOW(), INTERVAL %d DAY )",
				90
			)
		);
	}

	/**
	 * Migrate legacy event stats stored in post_meta to Apollo Core tables.
	 *
	 * @return void
	 */
	public function migrateEventStats(): void {
		// Run once, keep idempotent safety.
		if ( get_option( 'apollo_events_stats_migration_done' ) ) {
			return;
		}

		if ( ! class_exists( '\\Apollo_Core\\Analytics' ) ) {
			return;
		}

		global $wpdb;
		$content_stats_table = $wpdb->prefix . 'apollo_analytics_content_stats';

		$table_exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $content_stats_table )
		);

		if ( ! $table_exists ) {
			return;
		}

		$offset = absint( get_option( 'apollo_events_stats_migration_offset', 0 ) );
		$limit  = 100;

		$query = new \WP_Query(
			array(
				'post_type'      => 'event_listing',
				'posts_per_page' => $limit,
				'offset'         => $offset,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'meta_key'       => '_apollo_event_stats',
			)
		);

		if ( empty( $query->posts ) ) {
			delete_option( 'apollo_events_stats_migration_offset' );
			update_option( 'apollo_events_stats_migration_done', 1, false );
			return;
		}

		foreach ( $query->posts as $event_id ) {
			$event_id = absint( $event_id );
			$stats    = get_post_meta( $event_id, '_apollo_event_stats', true );

			if ( ! is_array( $stats ) ) {
				continue;
			}

			$total_views = absint( $stats['total_views'] ?? 0 );
			if ( $total_views <= 0 ) {
				continue;
			}

			$last_updated = $stats['last_updated'] ?? '';
			$stat_date    = $last_updated ? wp_date( 'Y-m-d', strtotime( $last_updated ) ) : current_time( 'Y-m-d' );
			$post_type    = get_post_type( $event_id ) ?: 'event_listing';
			$author_id    = (int) get_post_field( 'post_author', $event_id );

			$existing_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$content_stats_table} WHERE post_id = %d AND stat_date = %s",
					$event_id,
					$stat_date
				)
			);

			if ( $existing_id ) {
				$wpdb->update(
					$content_stats_table,
					array(
						'post_type' => $post_type,
						'author_id' => $author_id,
						'views'     => $total_views,
					),
					array( 'id' => $existing_id )
				);
			} else {
				$wpdb->insert(
					$content_stats_table,
					array(
						'post_id'          => $event_id,
						'post_type'        => $post_type,
						'author_id'        => $author_id,
						'stat_date'        => $stat_date,
						'views'            => $total_views,
						'unique_views'     => 0,
						'avg_time_seconds' => 0,
						'avg_scroll_depth' => 0,
						'clicks'           => 0,
						'shares'           => 0,
						'comments'         => 0,
						'likes'            => 0,
						'bounce_rate'      => 0.00,
					)
				);
			}

			// Keep legacy meta in sync for safety.
			$existing_views = absint( get_post_meta( $event_id, '_event_views', true ) );
			if ( $total_views > $existing_views ) {
				update_post_meta( $event_id, '_event_views', $total_views );
			}

			update_post_meta( $event_id, '_apollo_event_stats_migrated', current_time( 'mysql' ) );
		}

		update_option( 'apollo_events_stats_migration_offset', $offset + $limit, false );
	}

	/**
	 * Create stats table if it doesn't exist.
	 *
	 * @return void
	 */
	private function maybeCreateStatsTable(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'apollo_event_stats_daily';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			event_id BIGINT(20) UNSIGNED NOT NULL,
			date DATE NOT NULL,
			views INT(11) UNSIGNED NOT NULL DEFAULT 0,
			unique_views INT(11) UNSIGNED NOT NULL DEFAULT 0,
			logged_in_views INT(11) UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			UNIQUE KEY event_date (event_id, date),
			KEY event_id (event_id),
			KEY date (date)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Send event reminders.
	 *
	 * Can be called manually or scheduled.
	 *
	 * @param int $hours_before Hours before event to send reminder.
	 * @return int Number of reminders sent.
	 */
	public function sendEventReminders( int $hours_before = 24 ): int {
		global $wpdb;

		$target_time = wp_date( 'Y-m-d H:i:s', strtotime( "+{$hours_before} hours" ) );
		$current_time = current_time( 'mysql' );

		// Find events starting in the target window
		$events = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_title, pm.meta_value as start_date
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_event_start_date'
				LEFT JOIN {$wpdb->postmeta} reminder ON p.ID = reminder.post_id AND reminder.meta_key = '_reminder_sent_%d'
				WHERE p.post_type = 'event_listing'
				AND p.post_status = 'publish'
				AND pm.meta_value BETWEEN %s AND %s
				AND reminder.meta_value IS NULL
				LIMIT 50",
				$hours_before,
				$current_time,
				$target_time
			),
			ARRAY_A
		);

		if ( empty( $events ) ) {
			return 0;
		}

		$sent = 0;

		foreach ( $events as $event ) {
			$event_id = (int) $event['ID'];

			// Get interested users
			$interested_users = $this->getInterestedUsers( $event_id );

			foreach ( $interested_users as $user_id ) {
				$user = get_userdata( $user_id );
				if ( ! $user ) {
					continue;
				}

				// Send notification
				$this->sendReminderNotification( $user, $event );
			}

			// Mark reminder as sent
			update_post_meta( $event_id, "_reminder_sent_{$hours_before}", current_time( 'mysql' ) );
			++$sent;
		}

		return $sent;
	}

	/**
	 * Get users interested in an event.
	 *
	 * @param int $event_id Event ID.
	 * @return array<int>
	 */
	private function getInterestedUsers( int $event_id ): array {
		global $wpdb;

		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT user_id
				FROM {$wpdb->usermeta}
				WHERE meta_key = '_apollo_favorite_events'
				AND meta_value LIKE %s",
				'%:' . $event_id . ';%'
			)
		);
	}

	/**
	 * Send reminder notification to user.
	 *
	 * @param \WP_User             $user  User object.
	 * @param array<string, mixed> $event Event data.
	 * @return bool
	 */
	private function sendReminderNotification( \WP_User $user, array $event ): bool {
		$subject = sprintf(
			/* translators: %s: event title */
			__( 'Lembrete: %s começa em breve!', 'apollo-events-manager' ),
			$event['post_title']
		);

		// Use EventEmailIntegration class which connects to UnifiedEmailService
		if ( class_exists( 'Apollo_Events_Email_Integration' ) ) {
			\Apollo_Events_Email_Integration::send_event_reminder( (int) $event['ID'], $user->ID );
			return true;
		}

		// Fallback (should not happen if integration is loaded)
		$message = sprintf(
			/* translators: 1: user name, 2: event title, 3: event date, 4: event URL */
			__(
				"Olá %1\$s,\n\nLembrando que o evento \"%2\$s\" começa em breve!\n\nData: %3\$s\n\nVeja mais detalhes: %4\$s\n\nAté lá!",
				'apollo-events-manager'
			),
			$user->display_name,
			$event['post_title'],
			wp_date( 'd/m/Y H:i', strtotime( $event['start_date'] ) ),
			get_permalink( (int) $event['ID'] )
		);
		error_log( 'Apollo Events: EventEmailIntegration class not found, using fallback wp_mail()' );
		return false;
	}
}
