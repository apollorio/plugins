<?php

declare(strict_types=1);

/**
 * WP-CLI Commands for Moderation
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP CLI class
 */
class Apollo_Moderation_WP_CLI {

	/**
	 * Initialize
	 */
	public static function init() {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		WP_CLI::add_command( 'apollo mod-log', array( __CLASS__, 'mod_log' ) );
		WP_CLI::add_command( 'apollo mod-stats', array( __CLASS__, 'mod_stats' ) );
		WP_CLI::add_command( 'apollo mod-approve', array( __CLASS__, 'mod_approve' ) );
		WP_CLI::add_command( 'apollo mod-suspend', array( __CLASS__, 'mod_suspend' ) );
	}

	/**
	 * Show recent mod log
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<number>]
	 * : Number of entries to show. Default 100.
	 *
	 * [--actor=<user_id>]
	 * : Filter by actor user ID.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo mod-log
	 *     wp apollo mod-log --limit=50
	 *     wp apollo mod-log --actor=1
	 *
	 * @param array $args Command arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public static function mod_log( $args, $assoc_args ) {
		$limit    = isset( $assoc_args['limit'] ) ? intval( $assoc_args['limit'] ) : 100;
		$actor_id = isset( $assoc_args['actor'] ) ? intval( $assoc_args['actor'] ) : null;

		if ( $actor_id ) {
			$logs = Apollo_Moderation_Audit_Log::get_log_for_actor( $actor_id, $limit );
		} else {
			$logs = Apollo_Moderation_Audit_Log::get_recent_log( $limit );
		}

		if ( empty( $logs ) ) {
			WP_CLI::warning( 'No log entries found.' );

			return;
		}

		$table_data = array();
		foreach ( $logs as $log ) {
			$table_data[] = array(
				'ID'      => $log->id,
				'Date'    => $log->created_at,
				'Actor'   => $log->actor_id . ' (' . $log->actor_role . ')',
				'Action'  => $log->action,
				'Target'  => $log->target_type . ':' . $log->target_id,
				'Details' => is_array( $log->details ) ? wp_json_encode( $log->details ) : '',
			);
		}

		WP_CLI\Utils\format_items( 'table', $table_data, array( 'ID', 'Date', 'Actor', 'Action', 'Target', 'Details' ) );
	}

	/**
	 * Show mod statistics
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo mod-stats
	 */
	public static function mod_stats() {
		global $wpdb;
		$table = $wpdb->prefix . 'apollo_mod_log';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$by_action = $wpdb->get_results(
			"SELECT action, COUNT(*) as count FROM $table GROUP BY action ORDER BY count DESC"
		);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$by_actor = $wpdb->get_results(
			"SELECT actor_id, COUNT(*) as count FROM $table GROUP BY actor_id ORDER BY count DESC LIMIT 10"
		);

		WP_CLI::log( WP_CLI::colorize( '%BModeration Statistics%n' ) );
		WP_CLI::log( '─────────────────────' );
		WP_CLI::log( "Total actions: $total" );
		WP_CLI::log( '' );

		WP_CLI::log( 'Actions by type:' );
		foreach ( $by_action as $row ) {
			WP_CLI::log( "  - {$row->action}: {$row->count}" );
		}
		WP_CLI::log( '' );

		WP_CLI::log( 'Top 10 moderators:' );
		foreach ( $by_actor as $row ) {
			$user = get_userdata( $row->actor_id );
			$name = $user ? $user->display_name : 'Unknown';
			WP_CLI::log( "  - User #{$row->actor_id} ($name): {$row->count} actions" );
		}
	}

	/**
	 * Approve a post
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : Post ID to approve.
	 *
	 * [--note=<text>]
	 * : Optional note.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo mod-approve 123
	 *     wp apollo mod-approve 123 --note="Looks good"
	 *
	 * @param array $args Command arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public static function mod_approve( $args, $assoc_args ) {
		$post_id = intval( $args[0] );
		$note    = isset( $assoc_args['note'] ) ? sanitize_text_field( $assoc_args['note'] ) : '';

		$post = get_post( $post_id );

		if ( ! $post ) {
			WP_CLI::error( 'Post not found.' );

			return;
		}

		if ( ! in_array( $post->post_status, array( 'draft', 'pending' ), true ) ) {
			WP_CLI::error( 'Post is not in draft or pending status.' );

			return;
		}

		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			)
		);

		Apollo_Moderation_Audit_Log::log_action(
			get_current_user_id(),
			'approve_publish_cli',
			$post->post_type,
			$post_id,
			array( 'note' => $note )
		);

		WP_CLI::success( "Post #$post_id approved and published." );
	}

	/**
	 * Suspend a user
	 *
	 * ## OPTIONS
	 *
	 * <user_id>
	 * : User ID to suspend.
	 *
	 * <days>
	 * : Number of days to suspend.
	 *
	 * [--reason=<text>]
	 * : Reason for suspension.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo mod-suspend 5 7
	 *     wp apollo mod-suspend 5 7 --reason="Spam"
	 *
	 * @param array $args Command arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public static function mod_suspend( $args, $assoc_args ) {
		$user_id = intval( $args[0] );
		$days    = intval( $args[1] );
		$reason  = isset( $assoc_args['reason'] ) ? sanitize_text_field( $assoc_args['reason'] ) : '';

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			WP_CLI::error( 'User not found.' );

			return;
		}

		$success = Apollo_Moderation_Suspension::suspend_user( $user_id, $days, $reason, 1 );

		if ( ! $success ) {
			WP_CLI::error( 'Failed to suspend user.' );

			return;
		}

		WP_CLI::success( "User #{$user_id} suspended for $days days." );
	}
}
