<?php
/**
 * User Visit Tracker
 *
 * Tracks when logged-in users visit other user pages
 *
 * @package Apollo_Core
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include core fix helper to ensure missing functions are defined.
require_once __DIR__ . '/wp-core-fix-helper.php';

/**
 * User Visit Tracker
 */
class User_Visit_Tracker {

	/**
	 * Initialize
	 */
	public static function init() {
		add_action( 'template_redirect', array( self::class, 'track_visit' ), 20 );
	}

	/**
	 * Track user visit
	 */
	public static function track_visit() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$viewer_id      = get_current_user_id();
		$viewed_user_id = self::get_viewed_user_id();

		if ( ! $viewed_user_id || $viewed_user_id === $viewer_id ) {
			return;
		}

		// Check if tracking is allowed for this viewer.
		$tracking_mode = self::get_tracking_mode( $viewer_id, $viewed_user_id );

		if ( $tracking_mode === 'invisible' ) {
			return; // Don't track, don't notify.
		}

		// Track visit.
		$visits = get_user_meta( $viewed_user_id, '_apollo_recent_visits', true );
		if ( ! is_array( $visits ) ) {
			$visits = array();
		}

		// Add current visit.
		$visits[] = array(
			'user_id' => $viewer_id,
			'time'    => time(),
			'date'    => current_time( 'mysql' ),
		);

		// Keep only last 50 visits.
		$visits = array_slice( $visits, -50 );

		update_user_meta( $viewed_user_id, '_apollo_recent_visits', $visits );

		// Send notification if visible.
		if ( $tracking_mode === 'visible' ) {
			self::send_visit_notification( $viewed_user_id, $viewer_id );
		}
	}

	/**
	 * Get viewed user ID from current page
	 *
	 * @return int|false User ID or false.
	 */
	private static function get_viewed_user_id() {
		$user_id = get_query_var( 'apollo_user_id', false );
		if ( $user_id ) {
			return absint( $user_id );
		}

		// Check if viewing user page via /id/{user_id}.
		global $wp_query;
		if ( isset( $wp_query->query_vars['apollo_user_id'] ) ) {
			return absint( $wp_query->query_vars['apollo_user_id'] );
		}

		return false;
	}

	/**
	 * Get tracking mode for viewer
	 *
	 * @param int $viewer_id Viewer user ID.
	 * @param int $viewed_user_id Viewed user ID (unused but kept for future use).
	 * @return string Tracking mode: 'invisible', 'visible', 'optional'.
	 */
	private static function get_tracking_mode( $viewer_id, $viewed_user_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		unset( $viewed_user_id ); // Mark as intentionally unused for now.

		// Check if user has stealth mode enabled via Control Panel.
		if ( self::user_has_stealth_mode( $viewer_id ) ) {
			return 'invisible';
		}

		// Check viewer's privacy setting first.
		$viewer_setting = get_user_meta( $viewer_id, '_apollo_visit_tracking', true );

		if ( $viewer_setting && $viewer_setting !== 'default' ) {
			return $viewer_setting;
		}

		// Check admin setting for viewer's role.
		$viewer = get_userdata( $viewer_id );
		if ( ! $viewer || empty( $viewer->roles ) ) {
			return 'invisible';
		}

		$settings = get_option( 'apollo_unified_settings', array() );
		$role     = $viewer->roles[0];

		return $settings['privacy']['visit_tracking'][ $role ] ?? 'invisible';
	}

	/**
	 * Check if a user has stealth mode enabled
	 *
	 * Stealth mode allows users to visit profiles without triggering notifications.
	 * This is configured in Apollo Unified Control Panel > Privacy tab.
	 *
	 * @param int $user_id User ID to check.
	 * @return bool Whether user has stealth mode.
	 */
	public static function user_has_stealth_mode( $user_id ) {
		$settings      = get_option( 'apollo_unified_settings', array() );
		$stealth_users = $settings['privacy']['stealth_users'] ?? array();

		return in_array( (int) $user_id, array_map( 'intval', $stealth_users ), true );
	}

	/**
	 * Send visit notification
	 *
	 * @param int $viewed_user_id User who was visited.
	 * @param int $viewer_id User who visited.
	 */
	private static function send_visit_notification( $viewed_user_id, $viewer_id ) {
		$viewer = get_userdata( $viewer_id );
		if ( ! $viewer ) {
			return;
		}

		// Store notification.
		$notifications = get_user_meta( $viewed_user_id, '_apollo_notifications', true );
		if ( ! is_array( $notifications ) ) {
			$notifications = array();
		}

		$notifications[] = array(
			'type'    => 'visit',
			'user_id' => $viewer_id,
			'message' => sprintf(
				/* translators: %s: visitor name */
				__( '%s visitou sua página', 'apollo-core' ),
				$viewer->display_name
			),
			'time'    => time(),
		);

		// Keep only last 100 notifications.
		$notifications = array_slice( $notifications, -100 );

		update_user_meta( $viewed_user_id, '_apollo_notifications', $notifications );

		// Trigger action for email/real-time notification.
		do_action( 'apollo_user_visited', $viewed_user_id, $viewer_id );
	}

	/**
	 * Get recent visits for user
	 *
	 * @param int $user_id User ID.
	 * @param int $limit Number of visits to return.
	 * @return array Recent visits.
	 */
	public static function get_recent_visits( $user_id, $limit = 10 ) {
		$visits = get_user_meta( $user_id, '_apollo_recent_visits', true );
		if ( ! is_array( $visits ) ) {
			return array();
		}

		// Sort by time, most recent first.
		usort(
			$visits,
			function ( $a, $b ) {
				return $b['time'] - $a['time'];
			}
		);

		return array_slice( $visits, 0, $limit );
	}

	/**
	 * Format visit time
	 *
	 * @param int $timestamp Timestamp.
	 * @return string Formatted time.
	 */
	public static function format_visit_time( $timestamp ) {
		$diff = time() - $timestamp;

		if ( $diff < 60 ) {
			return __( 'agora', 'apollo-core' );
		} elseif ( $diff < 3600 ) {
			$minutes = floor( $diff / 60 );
			/* translators: %d: minutes */
			return sprintf( _n( '%d minuto atrás', '%d minutos atrás', $minutes, 'apollo-core' ), $minutes );
		} elseif ( $diff < 86400 ) {
			$hours = floor( $diff / 3600 );
			/* translators: %d: hours */
			return sprintf( _n( '%d hora atrás', '%d horas atrás', $hours, 'apollo-core' ), $hours );
		} else {
			$days = floor( $diff / 86400 );
			/* translators: %d: days */
			return sprintf( _n( '%d dia atrás', '%d dias atrás', $days, 'apollo-core' ), $days );
		}
	}
}

User_Visit_Tracker::init();
