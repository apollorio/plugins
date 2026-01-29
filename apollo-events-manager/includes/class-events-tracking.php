<?php
/**
 * Apollo Events Tracking
 *
 * Comprehensive tracking for Apollo Events Manager with Apollo Core Analytics.
 * Covers: Events, DJs, Locals/Venues, Interest, Reactions, Bookmarks.
 *
 * @package Apollo_Events_Manager
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Events Tracking Class
 */
class Apollo_Events_Tracking {

	/**
	 * Initialize tracking hooks
	 */
	public static function init(): void {
		// Only init if Apollo Core Analytics is available.
		if ( ! class_exists( '\Apollo_Core\Analytics' ) ) {
			return;
		}

		// ========================================
		// EVENT VIEWS
		// ========================================
		add_action( 'apollo_event_page_viewed', array( __CLASS__, 'track_event_page_view' ), 10, 2 );
		add_action( 'apollo_event_modal_opened', array( __CLASS__, 'track_event_modal_view' ), 10, 2 );

		// ========================================
		// INTERESSE (Interest Toggle)
		// ========================================
		add_action( 'apollo_interest_added', array( __CLASS__, 'track_interest_added' ), 10, 2 );
		add_action( 'apollo_interest_removed', array( __CLASS__, 'track_interest_removed' ), 10, 2 );

		// ========================================
		// BOOKMARKS / FAVORITES
		// ========================================
		add_action( 'apollo_event_bookmarked', array( __CLASS__, 'track_bookmark_added' ), 10, 2 );
		add_action( 'apollo_event_unbookmarked', array( __CLASS__, 'track_bookmark_removed' ), 10, 2 );
		add_action( 'apollo_event_favorite_toggled', array( __CLASS__, 'track_favorite_toggled' ), 10, 3 );

		// ========================================
		// SHARES
		// ========================================
		add_action( 'apollo_event_shared', array( __CLASS__, 'track_event_shared' ), 10, 2 );

		// ========================================
		// DJ PAGE VIEWS
		// ========================================
		add_action( 'apollo_dj_page_viewed', array( __CLASS__, 'track_dj_page_view' ), 10, 2 );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_track_dj_view' ), 20 );

		// ========================================
		// LOCAL/VENUE PAGE VIEWS
		// ========================================
		add_action( 'apollo_local_page_viewed', array( __CLASS__, 'track_local_page_view' ), 10, 2 );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_track_local_view' ), 20 );

		// ========================================
		// REVIEWS & REACTIONS
		// ========================================
		add_action( 'apollo_review_added', array( __CLASS__, 'track_review_added' ), 10, 4 );
		add_action( 'apollo_event_reaction_added', array( __CLASS__, 'track_event_reaction' ), 10, 3 );

		// ========================================
		// TICKETS
		// ========================================
		add_action( 'apollo_ticket_purchased', array( __CLASS__, 'track_ticket_purchase' ), 10, 3 );
		add_action( 'apollo_ticket_click', array( __CLASS__, 'track_ticket_click' ), 10, 2 );

		// ========================================
		// EVENT LIFECYCLE
		// ========================================
		add_action( 'apollo_event_published', array( __CLASS__, 'track_event_published' ), 10, 1 );
		add_action( 'apollo_events_event_expired', array( __CLASS__, 'track_event_expired' ), 10, 1 );
		add_action( 'apollo_event_duplicated', array( __CLASS__, 'track_event_duplicated' ), 10, 3 );

		// ========================================
		// EVENTS LISTING PAGE
		// ========================================
		add_action( 'apollo_events_listing_viewed', array( __CLASS__, 'track_events_listing_view' ), 10, 1 );
		add_action( 'apollo_events_filter_applied', array( __CLASS__, 'track_filter_applied' ), 10, 2 );
	}

	// ========================================
	// EVENT VIEW TRACKING
	// ========================================

	/**
	 * Track event page view (single event page)
	 *
	 * @param int $event_id Event post ID
	 * @param int $user_id  Viewer user ID (0 for anonymous)
	 */
	public static function track_event_page_view( int $event_id, int $user_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'event_view',
			'user_id'  => $user_id,
			'post_id'  => $event_id,
			'plugin'   => 'events',
			'metadata' => array(
				'view_type'   => 'page',
				'event_title' => get_the_title( $event_id ),
				'event_date'  => get_post_meta( $event_id, '_event_date', true ),
			),
		) );
	}

	/**
	 * Track event modal/popup view
	 *
	 * @param int $event_id Event post ID
	 * @param int $user_id  Viewer user ID (0 for anonymous)
	 */
	public static function track_event_modal_view( int $event_id, int $user_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'event_view',
			'user_id'  => $user_id,
			'post_id'  => $event_id,
			'plugin'   => 'events',
			'metadata' => array(
				'view_type'   => 'modal',
				'event_title' => get_the_title( $event_id ),
			),
		) );
	}

	// ========================================
	// INTERESSE (Interest) TRACKING
	// ========================================

	/**
	 * Track interest added to event
	 *
	 * @param int $event_id Event post ID
	 * @param int $user_id  User expressing interest
	 */
	public static function track_interest_added( int $event_id, int $user_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'event_interest',
			'user_id'  => $user_id,
			'post_id'  => $event_id,
			'plugin'   => 'events',
			'metadata' => array(
				'action'      => 'added',
				'event_title' => get_the_title( $event_id ),
				'event_date'  => get_post_meta( $event_id, '_event_date', true ),
			),
		) );
	}

	/**
	 * Track interest removed from event
	 *
	 * @param int $event_id Event post ID
	 * @param int $user_id  User removing interest
	 */
	public static function track_interest_removed( int $event_id, int $user_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'event_interest',
			'user_id'  => $user_id,
			'post_id'  => $event_id,
			'plugin'   => 'events',
			'metadata' => array(
				'action' => 'removed',
			),
		) );
	}

	// ========================================
	// BOOKMARK / FAVORITE TRACKING
	// ========================================

	/**
	 * Track event bookmarked
	 *
	 * @param int $event_id Event post ID
	 * @param int $user_id  User bookmarking
	 */
	public static function track_bookmark_added( int $event_id, int $user_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'event_bookmark',
			'user_id'  => $user_id,
			'post_id'  => $event_id,
			'plugin'   => 'events',
			'metadata' => array(
				'action'      => 'added',
				'event_title' => get_the_title( $event_id ),
			),
		) );
	}

	/**
	 * Track event unbookmarked
	 *
	 * @param int $event_id Event post ID
	 * @param int $user_id  User removing bookmark
	 */
	public static function track_bookmark_removed( int $event_id, int $user_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'event_bookmark',
			'user_id'  => $user_id,
			'post_id'  => $event_id,
			'plugin'   => 'events',
			'metadata' => array(
				'action' => 'removed',
			),
		) );
	}

	/**
	 * Track favorite toggled (legacy compatibility)
	 *
	 * @param int  $event_id Event post ID
	 * @param int  $user_id  User toggling
	 * @param bool $is_favorite Current state after toggle
	 */
	public static function track_favorite_toggled( int $event_id, int $user_id, bool $is_favorite ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'event_bookmark',
			'user_id'  => $user_id,
			'post_id'  => $event_id,
			'plugin'   => 'events',
			'metadata' => array(
				'action' => $is_favorite ? 'added' : 'removed',
				'source' => 'favorite_toggle',
			),
		) );
	}

	// ========================================
	// SHARE TRACKING
	// ========================================

	/**
	 * Track event shared
	 *
	 * @param int    $event_id Event post ID
	 * @param string $network  Social network (whatsapp, facebook, twitter, etc.)
	 */
	public static function track_event_shared( int $event_id, string $network ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'event_share',
			'user_id'  => get_current_user_id(),
			'post_id'  => $event_id,
			'plugin'   => 'events',
			'metadata' => array(
				'platform'    => $network,
				'event_title' => get_the_title( $event_id ),
			),
		) );
	}

	// ========================================
	// DJ PAGE TRACKING
	// ========================================

	/**
	 * Track DJ page view
	 *
	 * @param int $dj_id   DJ post ID
	 * @param int $user_id Viewer user ID
	 */
	public static function track_dj_page_view( int $dj_id, int $user_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'dj_view',
			'user_id'  => $user_id,
			'post_id'  => $dj_id,
			'plugin'   => 'events',
			'metadata' => array(
				'dj_name' => get_the_title( $dj_id ),
			),
		) );
	}

	/**
	 * Auto-track DJ page view on template_redirect
	 */
	public static function maybe_track_dj_view(): void {
		if ( ! is_singular( 'event_dj' ) ) {
			return;
		}

		$dj_id = get_the_ID();
		if ( ! $dj_id ) {
			return;
		}

		/**
		 * Fires when a DJ page is viewed.
		 *
		 * @param int $dj_id   DJ post ID.
		 * @param int $user_id Current user ID.
		 */
		do_action( 'apollo_dj_page_viewed', $dj_id, get_current_user_id() );
	}

	// ========================================
	// LOCAL/VENUE PAGE TRACKING
	// ========================================

	/**
	 * Track local/venue page view
	 *
	 * @param int $local_id Local post ID
	 * @param int $user_id  Viewer user ID
	 */
	public static function track_local_page_view( int $local_id, int $user_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'local_view',
			'user_id'  => $user_id,
			'post_id'  => $local_id,
			'plugin'   => 'events',
			'metadata' => array(
				'local_name'    => get_the_title( $local_id ),
				'local_address' => get_post_meta( $local_id, '_local_address', true ),
			),
		) );
	}

	/**
	 * Auto-track local page view on template_redirect
	 */
	public static function maybe_track_local_view(): void {
		if ( ! is_singular( 'event_local' ) ) {
			return;
		}

		$local_id = get_the_ID();
		if ( ! $local_id ) {
			return;
		}

		/**
		 * Fires when a local/venue page is viewed.
		 *
		 * @param int $local_id Local post ID.
		 * @param int $user_id  Current user ID.
		 */
		do_action( 'apollo_local_page_viewed', $local_id, get_current_user_id() );
	}

	// ========================================
	// REVIEWS & REACTIONS TRACKING
	// ========================================

	/**
	 * Track review added to event
	 *
	 * @param int $review_id Review comment ID
	 * @param int $event_id  Event post ID
	 * @param int $user_id   Reviewer user ID
	 * @param int $rating    Rating given (1-5)
	 */
	public static function track_review_added( int $review_id, int $event_id, int $user_id, int $rating ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'event_review',
			'user_id'  => $user_id,
			'post_id'  => $event_id,
			'plugin'   => 'events',
			'metadata' => array(
				'review_id'   => $review_id,
				'rating'      => $rating,
				'event_title' => get_the_title( $event_id ),
			),
		) );
	}

	/**
	 * Track reaction added to event
	 *
	 * @param int    $event_id      Event post ID
	 * @param int    $user_id       User reacting
	 * @param string $reaction_type Reaction type (like, love, fire, etc.)
	 */
	public static function track_event_reaction( int $event_id, int $user_id, string $reaction_type ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'event_reaction',
			'user_id'  => $user_id,
			'post_id'  => $event_id,
			'plugin'   => 'events',
			'metadata' => array(
				'reaction_type' => $reaction_type,
			),
		) );
	}

	// ========================================
	// TICKET TRACKING
	// ========================================

	/**
	 * Track ticket purchased
	 *
	 * @param int $event_id Event post ID
	 * @param int $user_id  Buyer user ID
	 * @param int $order_id Order ID (WooCommerce or custom)
	 */
	public static function track_ticket_purchase( int $event_id, int $user_id, int $order_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'event_ticket',
			'user_id'  => $user_id,
			'post_id'  => $event_id,
			'plugin'   => 'events',
			'metadata' => array(
				'action'      => 'purchased',
				'order_id'    => $order_id,
				'event_title' => get_the_title( $event_id ),
			),
		) );
	}

	/**
	 * Track ticket link clicked
	 *
	 * @param int $event_id Event post ID
	 * @param int $user_id  User clicking (0 for anonymous)
	 */
	public static function track_ticket_click( int $event_id, int $user_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'event_ticket',
			'user_id'  => $user_id,
			'post_id'  => $event_id,
			'plugin'   => 'events',
			'metadata' => array(
				'action' => 'click',
			),
		) );
	}

	// ========================================
	// EVENT LIFECYCLE TRACKING
	// ========================================

	/**
	 * Track event published
	 *
	 * @param WP_Post $post Event post object
	 */
	public static function track_event_published( WP_Post $post ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'event_lifecycle',
			'user_id'  => $post->post_author,
			'post_id'  => $post->ID,
			'plugin'   => 'events',
			'metadata' => array(
				'action'      => 'published',
				'event_title' => $post->post_title,
			),
		) );
	}

	/**
	 * Track event expired
	 *
	 * @param int $event_id Event post ID
	 */
	public static function track_event_expired( int $event_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'event_lifecycle',
			'user_id'  => 0,
			'post_id'  => $event_id,
			'plugin'   => 'events',
			'metadata' => array(
				'action' => 'expired',
			),
		) );
	}

	/**
	 * Track event duplicated
	 *
	 * @param int   $new_id      New event post ID
	 * @param int   $original_id Original event post ID
	 * @param array $args        Duplication arguments
	 */
	public static function track_event_duplicated( int $new_id, int $original_id, array $args ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'event_lifecycle',
			'user_id'  => get_current_user_id(),
			'post_id'  => $new_id,
			'plugin'   => 'events',
			'metadata' => array(
				'action'      => 'duplicated',
				'original_id' => $original_id,
			),
		) );
	}

	// ========================================
	// EVENTS LISTING TRACKING
	// ========================================

	/**
	 * Track events listing page viewed
	 *
	 * @param int $user_id Viewer user ID
	 */
	public static function track_events_listing_view( int $user_id ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'events_listing',
			'user_id'  => $user_id,
			'post_id'  => 0,
			'plugin'   => 'events',
			'metadata' => array(
				'action' => 'viewed',
			),
		) );
	}

	/**
	 * Track filter applied on events listing
	 *
	 * @param int   $user_id User applying filter
	 * @param array $filters Applied filters
	 */
	public static function track_filter_applied( int $user_id, array $filters ): void {
		\Apollo_Core\Analytics::track( array(
			'type'     => 'events_listing',
			'user_id'  => $user_id,
			'post_id'  => 0,
			'plugin'   => 'events',
			'metadata' => array(
				'action'  => 'filter_applied',
				'filters' => $filters,
			),
		) );
	}
}

// Initialize tracking
add_action( 'init', array( 'Apollo_Events_Tracking', 'init' ) );
