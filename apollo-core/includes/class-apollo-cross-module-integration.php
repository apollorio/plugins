<?php

/**
 * Apollo Cross-Module Integration
 *
 * Integração entre módulos: Social <-> Eventos <-> Bolha <-> Comunas.
 * Implementa priorização de feed, cross-posting, e notificações.
 *
 * FASE 4 do plano de modularização Apollo.
 *
 * @package Apollo_Core
 * @since 4.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apollo Cross-Module Integration class
 */
class Apollo_Cross_Module_Integration {

	/**
	 * Initialize hooks
	 */
	public static function init(): void {
		// Cross-post event to social feed.
		add_action( 'publish_event_listing', [ __CLASS__, 'maybe_create_event_social_post' ], 10, 2 );

		// Filter explore endpoint for bolha priority.
		add_filter( 'apollo_explore_query_args', [ __CLASS__, 'prioritize_bolha_content' ], 10, 2 );

		// Add "Eventos que vou" filter to feed.
		add_filter( 'apollo_feed_filters', [ __CLASS__, 'add_events_attending_filter' ] );

		// Hook into bolha changes for notifications.
		add_action( 'apollo_user_added_to_bolha', [ __CLASS__, 'notify_bolha_accepted' ], 10, 2 );
		add_action( 'apollo_bolha_invite_sent', [ __CLASS__, 'notify_bolha_invite' ], 10, 2 );

		// Hook into event RSVP for notifications.
		add_action( 'apollo_event_rsvp_added', [ __CLASS__, 'notify_event_rsvp' ], 10, 3 );

		// Check module enabled before each feature.
		add_filter( 'apollo_can_create_event', [ __CLASS__, 'check_events_module' ] );
		add_filter( 'apollo_can_create_post', [ __CLASS__, 'check_social_module' ] );
		add_filter( 'apollo_can_use_bolha', [ __CLASS__, 'check_bolha_module' ] );
		add_filter( 'apollo_can_create_comuna', [ __CLASS__, 'check_comunas_module' ] );

		// Integrate events into comuna pages.
		add_filter( 'apollo_comuna_sidebar_widgets', [ __CLASS__, 'add_comuna_events_widget' ] );

		// REST API filters.
		add_action( 'rest_api_init', [ __CLASS__, 'register_integration_routes' ] );
	}

	// =========================================================================
	// MODULE CHECKS
	// =========================================================================

	/**
	 * Check if events module is enabled
	 */
	public static function check_events_module( bool $can ): bool {
		if ( ! function_exists( 'apollo_is_module_enabled' ) ) {
			return $can;
		}

		return $can && apollo_is_module_enabled( 'events' );
	}

	/**
	 * Check if social module is enabled
	 */
	public static function check_social_module( bool $can ): bool {
		if ( ! function_exists( 'apollo_is_module_enabled' ) ) {
			return $can;
		}

		return $can && apollo_is_module_enabled( 'social' );
	}

	/**
	 * Check if bolha module is enabled
	 */
	public static function check_bolha_module( bool $can ): bool {
		if ( ! function_exists( 'apollo_is_module_enabled' ) ) {
			return $can;
		}

		return $can && apollo_is_module_enabled( 'bolha' );
	}

	/**
	 * Check if comunas module is enabled
	 */
	public static function check_comunas_module( bool $can ): bool {
		if ( ! function_exists( 'apollo_is_module_enabled' ) ) {
			return $can;
		}

		return $can && apollo_is_module_enabled( 'comunas' );
	}

	// =========================================================================
	// EVENT -> SOCIAL CROSS-POST
	// =========================================================================

	/**
	 * Create social post when event is published (if option enabled)
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function maybe_create_event_social_post( int $post_id, WP_Post $post ): void {
		// Check if auto-post is enabled.
		$auto_post = get_post_meta( $post_id, '_apollo_auto_social_post', true );
		if ( ! $auto_post ) {
			return;
		}

		// Check if already posted.
		$already_posted = get_post_meta( $post_id, '_apollo_social_post_id', true );
		if ( $already_posted ) {
			return;
		}

		// Check modules.
		if ( ! apollo_is_module_enabled( 'social' ) || ! apollo_is_module_enabled( 'events' ) ) {
			return;
		}

		// Create social post.
		$event_title = $post->post_title;
		$event_url   = get_permalink( $post_id );
		$event_date  = get_post_meta( $post_id, '_event_start_date', true );
		$thumbnail   = get_the_post_thumbnail_url( $post_id, 'medium' );

		$content = sprintf(
			/* translators: 1: event title, 2: event date */
			__( '🎉 Novo evento: %1$s! 📅 %2$s', 'apollo-core' ),
			$event_title,
			$event_date ? wp_date( 'd/m/Y H:i', strtotime( $event_date ) ) : ''
		);

		$social_post_id = wp_insert_post(
			[
				'post_type'    => 'apollo_social_post',
				'post_status'  => 'publish',
				'post_author'  => $post->post_author,
				'post_content' => $content,
				'meta_input'   => [
					'_apollo_linked_event_id' => $post_id,
					'_apollo_post_type'       => 'event_share',
					'_apollo_event_url'       => $event_url,
					'_apollo_event_thumbnail' => $thumbnail,
				],
			]
		);

		if ( $social_post_id && ! is_wp_error( $social_post_id ) ) {
			update_post_meta( $post_id, '_apollo_social_post_id', $social_post_id );
		}
	}

	// =========================================================================
	// BOLHA PRIORITY IN EXPLORE/FEED
	// =========================================================================

	/**
	 * Prioritize bolha content in explore queries
	 *
	 * This modifies the query to boost content from users in the current user's bolha.
	 *
	 * @param array $args  Query arguments.
	 * @param int   $user_id Current user ID.
	 * @return array Modified arguments.
	 */
	public static function prioritize_bolha_content( array $args, int $user_id ): array {
		if ( ! $user_id || ! apollo_is_module_enabled( 'bolha' ) ) {
			return $args;
		}

		// Get user's bolha.
		$bolha = get_user_meta( $user_id, 'apollo_bolha', true );
		if ( ! is_array( $bolha ) || empty( $bolha ) ) {
			return $args;
		}

		// Add bolha_priority flag for rendering layer.
		$args['apollo_bolha_users'] = array_map( 'intval', $bolha );

		return $args;
	}

	/**
	 * Add "Eventos que vou" filter option
	 *
	 * @param array $filters Available filters.
	 * @return array Modified filters.
	 */
	public static function add_events_attending_filter( array $filters ): array {
		if ( ! apollo_is_module_enabled( 'events' ) ) {
			return $filters;
		}

		$filters['events_attending'] = [
			'label' => __( 'Eventos que vou', 'apollo-core' ),
			'icon'  => 'ri-calendar-check-line',
		];

		return $filters;
	}

	// =========================================================================
	// NOTIFICATIONS
	// =========================================================================

	/**
	 * Notify user when bolha invite is accepted
	 *
	 * @param int $inviter_id User who sent the invite.
	 * @param int $invitee_id User who accepted.
	 */
	public static function notify_bolha_accepted( int $inviter_id, int $invitee_id ): void {
		if ( ! apollo_is_module_enabled( 'notifications' ) ) {
			return;
		}

		$invitee = get_userdata( $invitee_id );
		if ( ! $invitee ) {
			return;
		}

		$notification_data = [
			'user_id' => $inviter_id,
			'type'    => 'bolha_accepted',
			'title'   => __( 'Convite de bolha aceito!', 'apollo-core' ),
			'message' => sprintf(
				/* translators: %s: user name */
				__( '%s aceitou seu convite e agora está na sua bolha.', 'apollo-core' ),
				$invitee->display_name
			),
			'url'     => home_url( '/u/' . $invitee->user_nicename ),
			'icon'    => 'ri-bubble-chart-line',
		];

		do_action( 'apollo_send_notification', $notification_data );
	}

	/**
	 * Notify user of bolha invite
	 *
	 * @param int $inviter_id User sending invite.
	 * @param int $invitee_id User receiving invite.
	 */
	public static function notify_bolha_invite( int $inviter_id, int $invitee_id ): void {
		if ( ! apollo_is_module_enabled( 'notifications' ) ) {
			return;
		}

		$inviter = get_userdata( $inviter_id );
		if ( ! $inviter ) {
			return;
		}

		$notification_data = [
			'user_id' => $invitee_id,
			'type'    => 'bolha_invite',
			'title'   => __( 'Novo convite de bolha!', 'apollo-core' ),
			'message' => sprintf(
				/* translators: %s: user name */
				__( '%s quer te adicionar à bolha dele(a).', 'apollo-core' ),
				$inviter->display_name
			),
			'url'     => home_url( '/u/' . $inviter->user_nicename ),
			'icon'    => 'ri-bubble-chart-line',
			'actions' => [
				[
					'label'  => __( 'Aceitar', 'apollo-core' ),
					'action' => 'accept_bolha',
					'data'   => [ 'inviter_id' => $inviter_id ],
				],
				[
					'label'  => __( 'Recusar', 'apollo-core' ),
					'action' => 'reject_bolha',
					'data'   => [ 'inviter_id' => $inviter_id ],
				],
			],
		];

		do_action( 'apollo_send_notification', $notification_data );
	}

	/**
	 * Notify event organizer of RSVP
	 *
	 * @param int    $event_id Event ID.
	 * @param int    $user_id  User who RSVP'd.
	 * @param string $status   RSVP status.
	 */
	public static function notify_event_rsvp( int $event_id, int $user_id, string $status ): void {
		if ( ! apollo_is_module_enabled( 'notifications' ) ) {
			return;
		}

		$event = get_post( $event_id );
		if ( ! $event ) {
			return;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$organizer_id = $event->post_author;

		// Don't notify self.
		if ( $organizer_id === $user_id ) {
			return;
		}

		$status_labels = [
			'going'      => __( 'confirmou presença', 'apollo-core' ),
			'interested' => __( 'está interessado(a)', 'apollo-core' ),
			'maybe'      => __( 'talvez vá', 'apollo-core' ),
		];

		$notification_data = [
			'user_id' => $organizer_id,
			'type'    => 'event_rsvp',
			'title'   => __( 'Nova confirmação no evento!', 'apollo-core' ),
			'message' => sprintf(
				/* translators: 1: user name, 2: status, 3: event title */
				__( '%1$s %2$s no seu evento "%3$s".', 'apollo-core' ),
				$user->display_name,
				$status_labels[ $status ] ?? $status,
				$event->post_title
			),
			'url'     => get_permalink( $event_id ),
			'icon'    => 'ri-calendar-check-line',
		];

		do_action( 'apollo_send_notification', $notification_data );
	}

	// =========================================================================
	// COMUNA INTEGRATION
	// =========================================================================

	/**
	 * Add events widget to comuna sidebar
	 *
	 * @param array $widgets Sidebar widgets.
	 * @return array Modified widgets.
	 */
	public static function add_comuna_events_widget( array $widgets ): array {
		if ( ! apollo_is_module_enabled( 'events' ) || ! apollo_is_module_enabled( 'comunas' ) ) {
			return $widgets;
		}

		$widgets['comuna_events'] = [
			'title'    => __( 'Próximos Eventos', 'apollo-core' ),
			'callback' => [ __CLASS__, 'render_comuna_events_widget' ],
			'priority' => 20,
		];

		return $widgets;
	}

	/**
	 * Render comuna events widget
	 *
	 * @param int $comuna_id Comuna ID.
	 */
	public static function render_comuna_events_widget( int $comuna_id ): void {
		// Get events linked to this comuna.
		$events = get_posts(
			[
				'post_type'      => 'event_listing',
				'post_status'    => 'publish',
				'posts_per_page' => 5,
				'meta_query'     => [
					[
						'key'   => '_apollo_comuna_id',
						'value' => $comuna_id,
					],
				],
				'meta_key'       => '_event_start_date',
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
			]
		);

		if ( empty( $events ) ) {
			echo '<p class="no-events">' . esc_html__( 'Nenhum evento agendado.', 'apollo-core' ) . '</p>';

			return;
		}

		echo '<ul class="comuna-events-list">';
		foreach ( $events as $event ) {
			$date = get_post_meta( $event->ID, '_event_start_date', true );
			printf(
				'<li><a href="%s">%s</a><span class="event-date">%s</span></li>',
				esc_url( get_permalink( $event->ID ) ),
				esc_html( $event->post_title ),
				esc_html( $date ? wp_date( 'd/m', strtotime( $date ) ) : '' )
			);
		}
		echo '</ul>';
	}

	// =========================================================================
	// REST API INTEGRATION ROUTES
	// =========================================================================

	/**
	 * Register integration REST routes
	 */
	public static function register_integration_routes(): void {
		// Get events the user is attending.
		register_rest_route(
			'apollo/v1',
			'me/events-attending',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'rest_get_events_attending' ],
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			]
		);

		// Get feed filtered by bolha.
		register_rest_route(
			'apollo/v1',
			'explore/bolha',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'rest_get_bolha_feed' ],
				'permission_callback' => function () {
					return is_user_logged_in();
				},
				'args'                => [
					'page'     => [
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					],
					'per_page' => [
						'type'              => 'integer',
						'default'           => 20,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		// Get comuna events.
		register_rest_route(
			'apollo/v1',
			'comuna/(?P<id>\d+)/eventos',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'rest_get_comuna_events' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}

	/**
	 * REST: Get events user is attending
	 */
	public static function rest_get_events_attending( WP_REST_Request $request ): WP_REST_Response {
		$user_id = get_current_user_id();

		// Get events where user has RSVP.
		global $wpdb;

		$event_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_id FROM {$wpdb->postmeta}
				WHERE meta_key = '_apollo_rsvp_user_%d'
				AND meta_value IN ('going', 'interested')",
				$user_id
			)
		);

		if ( empty( $event_ids ) ) {
			return new WP_REST_Response( [ 'events' => [] ], 200 );
		}

		$events = get_posts(
			[
				'post_type'      => 'event_listing',
				'post_status'    => 'publish',
				'post__in'       => $event_ids,
				'posts_per_page' => 50,
				'meta_key'       => '_event_start_date',
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
			]
		);

		$result = [];
		foreach ( $events as $event ) {
			$result[] = [
				'id'        => $event->ID,
				'title'     => $event->post_title,
				'url'       => get_permalink( $event->ID ),
				'date'      => get_post_meta( $event->ID, '_event_start_date', true ),
				'thumbnail' => get_the_post_thumbnail_url( $event->ID, 'thumbnail' ),
			];
		}

		return new WP_REST_Response( [ 'events' => $result ], 200 );
	}

	/**
	 * REST: Get bolha-only feed
	 */
	public static function rest_get_bolha_feed( WP_REST_Request $request ): WP_REST_Response {
		$user_id  = get_current_user_id();
		$page     = $request->get_param( 'page' );
		$per_page = min( 50, $request->get_param( 'per_page' ) );

		// Get user's bolha.
		$bolha = get_user_meta( $user_id, 'apollo_bolha', true );
		if ( ! is_array( $bolha ) || empty( $bolha ) ) {
			return new WP_REST_Response(
				[
					'posts'   => [],
					'message' => __( 'Sua bolha está vazia.', 'apollo-core' ),
				],
				200
			);
		}

		// Get posts from bolha users.
		$posts = get_posts(
			[
				'post_type'      => 'apollo_social_post',
				'post_status'    => 'publish',
				'author__in'     => $bolha,
				'posts_per_page' => $per_page,
				'paged'          => $page,
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		$result = [];
		foreach ( $posts as $post ) {
			$author   = get_userdata( $post->post_author );
			$result[] = [
				'id'      => $post->ID,
				'content' => wp_trim_words( $post->post_content, 50 ),
				'date'    => $post->post_date,
				'author'  => [
					'id'     => $author->ID,
					'name'   => $author->display_name,
					'avatar' => get_avatar_url( $author->ID, [ 'size' => 48 ] ),
				],
				'url'     => get_permalink( $post->ID ),
			];
		}

		return new WP_REST_Response( [ 'posts' => $result ], 200 );
	}

	/**
	 * REST: Get comuna events
	 */
	public static function rest_get_comuna_events( WP_REST_Request $request ): WP_REST_Response {
		$comuna_id = $request->get_param( 'id' );

		$events = get_posts(
			[
				'post_type'      => 'event_listing',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'meta_query'     => [
					[
						'key'   => '_apollo_comuna_id',
						'value' => $comuna_id,
					],
				],
				'meta_key'       => '_event_start_date',
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
			]
		);

		$result = [];
		foreach ( $events as $event ) {
			$result[] = [
				'id'        => $event->ID,
				'title'     => $event->post_title,
				'url'       => get_permalink( $event->ID ),
				'date'      => get_post_meta( $event->ID, '_event_start_date', true ),
				'thumbnail' => get_the_post_thumbnail_url( $event->ID, 'thumbnail' ),
			];
		}

		return new WP_REST_Response( [ 'events' => $result ], 200 );
	}
}

// Initialize on plugins_loaded.
add_action( 'plugins_loaded', [ 'Apollo_Cross_Module_Integration', 'init' ], 15 );
