<?php
/**
 * Apollo Event Bus
 *
 * Pub/Sub system for cross-plugin event communication.
 * Provides a decoupled way for plugins to communicate without direct dependencies.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Event_Bus
 *
 * Central event bus for cross-plugin communication.
 */
class Apollo_Event_Bus {

	/*
	|--------------------------------------------------------------------------
	| Event Constants - Events
	|--------------------------------------------------------------------------
	*/

	/** Event created */
	public const EVENT_CREATED = 'apollo.event.created';

	/** Event updated */
	public const EVENT_UPDATED = 'apollo.event.updated';

	/** Event deleted */
	public const EVENT_DELETED = 'apollo.event.deleted';

	/** Event published */
	public const EVENT_PUBLISHED = 'apollo.event.published';

	/** Event RSVP added */
	public const RSVP_ADDED = 'apollo.event.rsvp.added';

	/** Event RSVP removed */
	public const RSVP_REMOVED = 'apollo.event.rsvp.removed';

	/** Event interested */
	public const EVENT_INTERESTED = 'apollo.event.interested';

	/*
	|--------------------------------------------------------------------------
	| Event Constants - Users
	|--------------------------------------------------------------------------
	*/

	/** User registered */
	public const USER_REGISTERED = 'apollo.user.registered';

	/** User profile updated */
	public const USER_UPDATED = 'apollo.user.updated';

	/** User followed another */
	public const USER_FOLLOWED = 'apollo.user.followed';

	/** User unfollowed another */
	public const USER_UNFOLLOWED = 'apollo.user.unfollowed';

	/** User added to bubble */
	public const BUBBLE_ADDED = 'apollo.user.bubble.added';

	/** User removed from bubble */
	public const BUBBLE_REMOVED = 'apollo.user.bubble.removed';

	/** User verified */
	public const USER_VERIFIED = 'apollo.user.verified';

	/** User suspended */
	public const USER_SUSPENDED = 'apollo.user.suspended';

	/** User banned */
	public const USER_BANNED = 'apollo.user.banned';

	/*
	|--------------------------------------------------------------------------
	| Event Constants - Content
	|--------------------------------------------------------------------------
	*/

	/** Content liked */
	public const CONTENT_LIKED = 'apollo.content.liked';

	/** Content unliked */
	public const CONTENT_UNLIKED = 'apollo.content.unliked';

	/** Content favorited */
	public const CONTENT_FAVORITED = 'apollo.content.favorited';

	/** Content unfavorited */
	public const CONTENT_UNFAVORITED = 'apollo.content.unfavorited';

	/** Content commented */
	public const CONTENT_COMMENTED = 'apollo.content.commented';

	/** Content shared */
	public const CONTENT_SHARED = 'apollo.content.shared';

	/** Content reported */
	public const CONTENT_REPORTED = 'apollo.content.reported';

	/*
	|--------------------------------------------------------------------------
	| Event Constants - Social
	|--------------------------------------------------------------------------
	*/

	/** Activity posted */
	public const ACTIVITY_POSTED = 'apollo.social.activity.posted';

	/** Activity deleted */
	public const ACTIVITY_DELETED = 'apollo.social.activity.deleted';

	/** Message sent */
	public const MESSAGE_SENT = 'apollo.social.message.sent';

	/** Message read */
	public const MESSAGE_READ = 'apollo.social.message.read';

	/** Notification sent */
	public const NOTIFICATION_SENT = 'apollo.social.notification.sent';

	/*
	|--------------------------------------------------------------------------
	| Event Constants - Groups
	|--------------------------------------------------------------------------
	*/

	/** Group created */
	public const GROUP_CREATED = 'apollo.group.created';

	/** Group updated */
	public const GROUP_UPDATED = 'apollo.group.updated';

	/** Group deleted */
	public const GROUP_DELETED = 'apollo.group.deleted';

	/** Member joined group */
	public const GROUP_JOINED = 'apollo.group.member.joined';

	/** Member left group */
	public const GROUP_LEFT = 'apollo.group.member.left';

	/** Member invited to group */
	public const GROUP_INVITED = 'apollo.group.member.invited';

	/*
	|--------------------------------------------------------------------------
	| Event Constants - DJs/Venues
	|--------------------------------------------------------------------------
	*/

	/** DJ created */
	public const DJ_CREATED = 'apollo.dj.created';

	/** DJ updated */
	public const DJ_UPDATED = 'apollo.dj.updated';

	/** Venue created */
	public const VENUE_CREATED = 'apollo.venue.created';

	/** Venue updated */
	public const VENUE_UPDATED = 'apollo.venue.updated';

	/*
	|--------------------------------------------------------------------------
	| Event Constants - Classifieds
	|--------------------------------------------------------------------------
	*/

	/** Classified created */
	public const CLASSIFIED_CREATED = 'apollo.classified.created';

	/** Classified updated */
	public const CLASSIFIED_UPDATED = 'apollo.classified.updated';

	/** Classified contacted */
	public const CLASSIFIED_CONTACTED = 'apollo.classified.contacted';

	/*
	|--------------------------------------------------------------------------
	| Event Constants - System
	|--------------------------------------------------------------------------
	*/

	/** Cache invalidated */
	public const CACHE_INVALIDATED = 'apollo.system.cache.invalidated';

	/** Plugin activated */
	public const PLUGIN_ACTIVATED = 'apollo.system.plugin.activated';

	/** Plugin deactivated */
	public const PLUGIN_DEACTIVATED = 'apollo.system.plugin.deactivated';

	/** Settings updated */
	public const SETTINGS_UPDATED = 'apollo.system.settings.updated';

	/** Migration completed */
	public const MIGRATION_COMPLETED = 'apollo.system.migration.completed';

	/*
	|--------------------------------------------------------------------------
	| Event Constants - Gamification
	|--------------------------------------------------------------------------
	*/

	/** Points earned */
	public const POINTS_EARNED = 'apollo.gamification.points.earned';

	/** Level up */
	public const LEVEL_UP = 'apollo.gamification.level.up';

	/** Badge earned */
	public const BADGE_EARNED = 'apollo.gamification.badge.earned';

	/*
	|--------------------------------------------------------------------------
	| Instance Management
	|--------------------------------------------------------------------------
	*/

	/**
	 * Singleton instance
	 *
	 * @var Apollo_Event_Bus|null
	 */
	private static ?Apollo_Event_Bus $instance = null;

	/**
	 * Registered listeners
	 *
	 * @var array<string, array>
	 */
	private array $listeners = array();

	/**
	 * Event history (for debugging)
	 *
	 * @var array<array>
	 */
	private array $history = array();

	/**
	 * Event queue (for deferred events)
	 *
	 * @var array<array>
	 */
	private array $queue = array();

	/**
	 * Whether debug mode is enabled
	 *
	 * @var bool
	 */
	private bool $debug = false;

	/**
	 * Maximum history size
	 *
	 * @var int
	 */
	private int $max_history = 100;

	/**
	 * Get singleton instance
	 *
	 * @return Apollo_Event_Bus
	 */
	public static function get_instance(): Apollo_Event_Bus {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		$this->debug = \defined( 'WP_DEBUG' ) && WP_DEBUG;
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Process deferred events on shutdown.
		\add_action( 'shutdown', array( $this, 'process_queue' ), 100 );

		// Log events in debug mode.
		if ( $this->debug ) {
			\add_action( 'shutdown', array( $this, 'log_history' ), 999 );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Static API Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Emit an event
	 *
	 * @param string $event Event name.
	 * @param array  $data  Event data.
	 * @return void
	 */
	public static function emit( string $event, array $data = array() ): void {
		self::get_instance()->dispatch( $event, $data );
	}

	/**
	 * Register an event listener
	 *
	 * @param string   $event    Event name.
	 * @param callable $callback Callback function.
	 * @param int      $priority Priority (lower = earlier).
	 * @return void
	 */
	public static function on( string $event, callable $callback, int $priority = 10 ): void {
		self::get_instance()->subscribe( $event, $callback, $priority );
	}

	/**
	 * Remove an event listener
	 *
	 * @param string   $event    Event name.
	 * @param callable $callback Callback to remove.
	 * @return bool Success.
	 */
	public static function off( string $event, callable $callback ): bool {
		return self::get_instance()->unsubscribe( $event, $callback );
	}

	/**
	 * Emit an event to be processed later
	 *
	 * @param string $event Event name.
	 * @param array  $data  Event data.
	 * @return void
	 */
	public static function defer( string $event, array $data = array() ): void {
		self::get_instance()->enqueue( $event, $data );
	}

	/**
	 * Listen to multiple events with one callback
	 *
	 * @param array    $events   Array of event names.
	 * @param callable $callback Callback function.
	 * @param int      $priority Priority.
	 * @return void
	 */
	public static function on_any( array $events, callable $callback, int $priority = 10 ): void {
		$instance = self::get_instance();
		foreach ( $events as $event ) {
			$instance->subscribe( $event, $callback, $priority );
		}
	}

	/**
	 * Listen once (auto-unsubscribe after first call)
	 *
	 * @param string   $event    Event name.
	 * @param callable $callback Callback function.
	 * @param int      $priority Priority.
	 * @return void
	 */
	public static function once( string $event, callable $callback, int $priority = 10 ): void {
		$wrapper = null;
		$wrapper = function ( array $data ) use ( $event, $callback, &$wrapper ) {
			self::off( $event, $wrapper );
			return $callback( $data );
		};
		self::on( $event, $wrapper, $priority );
	}

	/*
	|--------------------------------------------------------------------------
	| Instance Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Dispatch an event
	 *
	 * @param string $event Event name.
	 * @param array  $data  Event data.
	 * @return void
	 */
	public function dispatch( string $event, array $data = array() ): void {
		// Enrich data.
		$data = $this->enrich_event_data( $event, $data );

		// Log to history.
		$this->add_to_history( $event, $data );

		// Get listeners.
		$listeners = $this->get_listeners( $event );

		// Sort by priority.
		\usort( $listeners, fn( $a, $b ) => $a['priority'] <=> $b['priority'] );

		// Execute listeners.
		foreach ( $listeners as $listener ) {
			try {
				\call_user_func( $listener['callback'], $data );
			} catch ( \Throwable $e ) {
				if ( $this->debug ) {
					\error_log(
						\sprintf(
							'Apollo Event Bus: Error in listener for %s: %s',
							$event,
							$e->getMessage()
						)
					);
				}
			}
		}

		// Also trigger WordPress action for backward compat.
		\do_action( 'apollo_event_bus_' . $event, $data );
	}

	/**
	 * Subscribe to an event
	 *
	 * @param string   $event    Event name.
	 * @param callable $callback Callback.
	 * @param int      $priority Priority.
	 * @return void
	 */
	public function subscribe( string $event, callable $callback, int $priority = 10 ): void {
		if ( ! isset( $this->listeners[ $event ] ) ) {
			$this->listeners[ $event ] = array();
		}

		$this->listeners[ $event ][] = array(
			'callback' => $callback,
			'priority' => $priority,
		);
	}

	/**
	 * Unsubscribe from an event
	 *
	 * @param string   $event    Event name.
	 * @param callable $callback Callback to remove.
	 * @return bool Success.
	 */
	public function unsubscribe( string $event, callable $callback ): bool {
		if ( ! isset( $this->listeners[ $event ] ) ) {
			return false;
		}

		$initial_count = \count( $this->listeners[ $event ] );

		$this->listeners[ $event ] = \array_filter(
			$this->listeners[ $event ],
			fn( $listener ) => $listener['callback'] !== $callback
		);

		return \count( $this->listeners[ $event ] ) < $initial_count;
	}

	/**
	 * Enqueue an event for later processing
	 *
	 * @param string $event Event name.
	 * @param array  $data  Event data.
	 * @return void
	 */
	public function enqueue( string $event, array $data = array() ): void {
		$this->queue[] = array(
			'event' => $event,
			'data'  => $data,
			'time'  => \microtime( true ),
		);
	}

	/**
	 * Process queued events
	 *
	 * @return void
	 */
	public function process_queue(): void {
		while ( ! empty( $this->queue ) ) {
			$item = \array_shift( $this->queue );
			$this->dispatch( $item['event'], $item['data'] );
		}
	}

	/**
	 * Get listeners for an event
	 *
	 * @param string $event Event name.
	 * @return array
	 */
	private function get_listeners( string $event ): array {
		$listeners = $this->listeners[ $event ] ?? array();

		// Also get wildcard listeners.
		foreach ( $this->listeners as $pattern => $pattern_listeners ) {
			if ( $this->matches_pattern( $event, $pattern ) ) {
				$listeners = \array_merge( $listeners, $pattern_listeners );
			}
		}

		return $listeners;
	}

	/**
	 * Check if event matches a pattern
	 *
	 * @param string $event   Event name.
	 * @param string $pattern Pattern (supports * wildcard).
	 * @return bool
	 */
	private function matches_pattern( string $event, string $pattern ): bool {
		if ( $event === $pattern ) {
			return false; // Already handled.
		}

		if ( \strpos( $pattern, '*' ) === false ) {
			return false;
		}

		$regex = '/^' . \str_replace( array( '.', '*' ), array( '\.', '.*' ), $pattern ) . '$/';
		return (bool) \preg_match( $regex, $event );
	}

	/**
	 * Enrich event data with metadata
	 *
	 * @param string $event Event name.
	 * @param array  $data  Event data.
	 * @return array Enriched data.
	 */
	private function enrich_event_data( string $event, array $data ): array {
		return \array_merge(
			$data,
			array(
				'_event'     => $event,
				'_timestamp' => \time(),
				'_user_id'   => \get_current_user_id(),
				'_request'   => isset( $_SERVER['REQUEST_URI'] ) ? \sanitize_text_field( \wp_unslash( $_SERVER['REQUEST_URI'] ) ) : 'cli',
			)
		);
	}

	/**
	 * Add event to history
	 *
	 * @param string $event Event name.
	 * @param array  $data  Event data.
	 * @return void
	 */
	private function add_to_history( string $event, array $data ): void {
		if ( ! $this->debug ) {
			return;
		}

		$this->history[] = array(
			'event'     => $event,
			'data'      => $data,
			'listeners' => \count( $this->get_listeners( $event ) ),
			'memory'    => \memory_get_usage( true ),
		);

		// Trim history if too large.
		if ( \count( $this->history ) > $this->max_history ) {
			$this->history = \array_slice( $this->history, -$this->max_history );
		}
	}

	/**
	 * Log history at shutdown
	 *
	 * @return void
	 */
	public function log_history(): void {
		if ( empty( $this->history ) ) {
			return;
		}

		$count = \count( $this->history );
		\error_log(
			\sprintf(
				'Apollo Event Bus: %d events dispatched this request',
				$count
			)
		);
	}

	/*
	|--------------------------------------------------------------------------
	| Utility Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get all registered event types
	 *
	 * @return array<string>
	 */
	public static function get_event_types(): array {
		$reflection = new \ReflectionClass( self::class );
		$constants  = $reflection->getConstants();

		return \array_filter(
			$constants,
			fn( $value ) => \is_string( $value ) && \str_starts_with( $value, 'apollo.' ),
		);
	}

	/**
	 * Get event history (debug mode only)
	 *
	 * @return array
	 */
	public function get_history(): array {
		return $this->history;
	}

	/**
	 * Get listener count for an event
	 *
	 * @param string $event Event name.
	 * @return int
	 */
	public function get_listener_count( string $event ): int {
		return \count( $this->get_listeners( $event ) );
	}

	/**
	 * Clear all listeners (for testing)
	 *
	 * @return void
	 */
	public function clear_all(): void {
		$this->listeners = array();
		$this->queue     = array();
		$this->history   = array();
	}

	/**
	 * Check if event has listeners
	 *
	 * @param string $event Event name.
	 * @return bool
	 */
	public function has_listeners( string $event ): bool {
		return ! empty( $this->get_listeners( $event ) );
	}
}

// Initialize event bus.
Apollo_Event_Bus::get_instance();
