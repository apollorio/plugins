<?php
/**
 * Apollo AJAX Handler
 *
 * Provides AJAX fallback for environments where REST API is unavailable.
 * Uses admin-ajax.php as a fallback mechanism.
 *
 * @package Apollo_Core
 * @subpackage AJAX
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core\AJAX;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_AJAX_Handler
 *
 * Handles AJAX requests as REST API fallback.
 *
 * @since 2.0.0
 */
class Apollo_AJAX_Handler {

	/**
	 * Instance.
	 *
	 * @since 2.0.0
	 * @var Apollo_AJAX_Handler|null
	 */
	private static ?Apollo_AJAX_Handler $instance = null;

	/**
	 * AJAX actions registry.
	 *
	 * @since 2.0.0
	 * @var array
	 */
	private array $actions = array();

	/**
	 * Nonce action.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $nonce_action = 'apollo_ajax_nonce';

	/**
	 * Get instance.
	 *
	 * @since 2.0.0
	 * @return Apollo_AJAX_Handler
	 */
	public static function get_instance(): Apollo_AJAX_Handler {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 */
	private function __construct() {
		$this->register_default_actions();
		add_action( 'init', array( $this, 'register_ajax_handlers' ) );
	}

	/**
	 * Register default AJAX actions.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function register_default_actions(): void {
		// Events actions.
		$this->actions = array(
			// Events.
			'get_events'      => array(
				'callback' => array( $this, 'handle_get_events' ),
				'nopriv'   => true,
			),
			'get_event'       => array(
				'callback' => array( $this, 'handle_get_event' ),
				'nopriv'   => true,
			),
			'get_calendar'    => array(
				'callback' => array( $this, 'handle_get_calendar' ),
				'nopriv'   => true,
			),
			'toggle_favorite' => array(
				'callback' => array( $this, 'handle_toggle_favorite' ),
				'nopriv'   => false,
			),

			// Social Feed.
			'get_feed'        => array(
				'callback' => array( $this, 'handle_get_feed' ),
				'nopriv'   => true,
			),
			'create_activity' => array(
				'callback' => array( $this, 'handle_create_activity' ),
				'nopriv'   => false,
			),
			'toggle_like'     => array(
				'callback' => array( $this, 'handle_toggle_like' ),
				'nopriv'   => false,
			),
			'add_comment'     => array(
				'callback' => array( $this, 'handle_add_comment' ),
				'nopriv'   => false,
			),

			// User Profile.
			'get_profile'     => array(
				'callback' => array( $this, 'handle_get_profile' ),
				'nopriv'   => true,
			),
			'update_profile'  => array(
				'callback' => array( $this, 'handle_update_profile' ),
				'nopriv'   => false,
			),
			'toggle_follow'   => array(
				'callback' => array( $this, 'handle_toggle_follow' ),
				'nopriv'   => false,
			),

			// Classifieds.
			'get_classifieds' => array(
				'callback' => array( $this, 'handle_get_classifieds' ),
				'nopriv'   => true,
			),
			'get_classified'  => array(
				'callback' => array( $this, 'handle_get_classified' ),
				'nopriv'   => true,
			),
			'send_contact'    => array(
				'callback' => array( $this, 'handle_send_contact' ),
				'nopriv'   => false,
			),
		);

		/**
		 * Filter to add custom AJAX actions.
		 *
		 * @since 2.0.0
		 *
		 * @param array $actions Registered actions.
		 */
		$this->actions = apply_filters( 'apollo_ajax_actions', $this->actions );
	}

	/**
	 * Register AJAX handlers.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_ajax_handlers(): void {
		foreach ( $this->actions as $action => $config ) {
			$wp_action = 'apollo_' . $action;

			// Logged-in users.
			add_action( 'wp_ajax_' . $wp_action, $config['callback'] );

			// Non-logged-in users (if allowed).
			if ( ! empty( $config['nopriv'] ) ) {
				add_action( 'wp_ajax_nopriv_' . $wp_action, $config['callback'] );
			}
		}
	}

	/**
	 * Verify nonce.
	 *
	 * @since 2.0.0
	 *
	 * @param string $nonce The nonce to verify.
	 * @return bool
	 */
	protected function verify_nonce( string $nonce ): bool {
		return wp_verify_nonce( $nonce, $this->nonce_action );
	}

	/**
	 * Send JSON success response.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed  $data    Response data.
	 * @param string $message Success message.
	 * @return void
	 */
	protected function success( $data = null, string $message = '' ): void {
		wp_send_json(
			array(
				'success' => true,
				'data'    => $data,
				'message' => $message,
			)
		);
	}

	/**
	 * Send JSON error response.
	 *
	 * @since 2.0.0
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @param int    $status  HTTP status code.
	 * @return void
	 */
	protected function error( string $code, string $message, int $status = 400 ): void {
		wp_send_json(
			array(
				'success' => false,
				'code'    => $code,
				'message' => $message,
			),
			$status
		);
	}

	/**
	 * Get POST data.
	 *
	 * @since 2.0.0
	 *
	 * @param string $key     Parameter key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	protected function get_param( string $key, $default = null ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : $default;
	}

	/**
	 * Require authentication.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	protected function require_auth(): bool {
		if ( ! is_user_logged_in() ) {
			$this->error( 'unauthorized', __( 'Você precisa estar logado.', 'apollo-core' ), 401 );
			return false;
		}
		return true;
	}

	/**
	 * Require nonce validation.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	protected function require_nonce(): bool {
		$nonce = $this->get_param( 'nonce', '' );
		if ( ! $this->verify_nonce( (string) $nonce ) ) {
			$this->error( 'invalid_nonce', __( 'Requisição inválida.', 'apollo-core' ), 403 );
			return false;
		}
		return true;
	}

	// =========================================================================
	// Event Handlers
	// =========================================================================

	/**
	 * Handle get events.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_get_events(): void {
		$page     = (int) ( $this->get_param( 'page' ) ?? 1 );
		$per_page = min( 100, max( 1, (int) ( $this->get_param( 'per_page' ) ?? 10 ) ) );
		$offset   = ( $page - 1 ) * $per_page;

		$args = array(
			'post_type'      => 'event_listing',
			'posts_per_page' => $per_page,
			'offset'         => $offset,
			'post_status'    => 'publish',
		);

		// Search.
		$search = $this->get_param( 'search' );
		if ( ! empty( $search ) ) {
			$args['s'] = sanitize_text_field( $search );
		}

		// Category.
		$category = $this->get_param( 'category' );
		if ( ! empty( $category ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'event_category',
					'field'    => 'term_id',
					'terms'    => array_map( 'absint', (array) $category ),
				),
			);
		}

		// Upcoming only.
		if ( $this->get_param( 'upcoming' ) ) {
			$args['meta_query'][] = array(
				'key'     => '_event_start_date',
				'value'   => date( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'DATE',
			);
		}

		$query = new \WP_Query( $args );

		$events = array();
		foreach ( $query->posts as $post ) {
			$events[] = $this->prepare_event( $post );
		}

		$this->success(
			array(
				'items'    => $events,
				'total'    => $query->found_posts,
				'page'     => $page,
				'per_page' => $per_page,
				'pages'    => ceil( $query->found_posts / $per_page ),
			)
		);
	}

	/**
	 * Handle get single event.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_get_event(): void {
		$id   = (int) $this->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || $post->post_type !== 'event_listing' || $post->post_status !== 'publish' ) {
			$this->error( 'not_found', __( 'Evento não encontrado.', 'apollo-core' ), 404 );
			return;
		}

		$this->success( $this->prepare_event( $post, true ) );
	}

	/**
	 * Handle get calendar.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_get_calendar(): void {
		$month = (int) ( $this->get_param( 'month' ) ?? date( 'm' ) );
		$year  = (int) ( $this->get_param( 'year' ) ?? date( 'Y' ) );

		$first_day = sprintf( '%04d-%02d-01', $year, $month );
		$last_day  = date( 'Y-m-t', strtotime( $first_day ) );

		$args = array(
			'post_type'      => 'event_listing',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'     => '_event_start_date',
					'value'   => array( $first_day, $last_day ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		);

		$query = new \WP_Query( $args );

		$events_by_date = array();
		foreach ( $query->posts as $post ) {
			$event_date = get_post_meta( $post->ID, '_event_start_date', true );
			if ( ! $event_date ) {
				continue;
			}

			$date_key = date( 'Y-m-d', strtotime( $event_date ) );
			if ( ! isset( $events_by_date[ $date_key ] ) ) {
				$events_by_date[ $date_key ] = array();
			}

			$events_by_date[ $date_key ][] = array(
				'id'         => $post->ID,
				'title'      => get_the_title( $post ),
				'start_time' => get_post_meta( $post->ID, '_event_start_time', true ),
				'link'       => get_permalink( $post ),
			);
		}

		$this->success(
			array(
				'month'  => $month,
				'year'   => $year,
				'events' => $events_by_date,
			)
		);
	}

	/**
	 * Handle toggle favorite.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_toggle_favorite(): void {
		if ( ! $this->require_auth() || ! $this->require_nonce() ) {
			return;
		}

		$event_id = (int) $this->get_param( 'event_id' );
		$user_id  = get_current_user_id();

		$favorites = get_user_meta( $user_id, '_apollo_favorite_events', true );
		if ( ! is_array( $favorites ) ) {
			$favorites = array();
		}

		$is_favorite = in_array( $event_id, $favorites, true );

		if ( $is_favorite ) {
			$favorites   = array_diff( $favorites, array( $event_id ) );
			$is_favorite = false;
			$message     = __( 'Removido dos favoritos.', 'apollo-core' );
		} else {
			$favorites[] = $event_id;
			$is_favorite = true;
			$message     = __( 'Adicionado aos favoritos.', 'apollo-core' );
		}

		update_user_meta( $user_id, '_apollo_favorite_events', array_values( $favorites ) );

		$this->success(
			array(
				'event_id'    => $event_id,
				'is_favorite' => $is_favorite,
			),
			$message
		);
	}

	// =========================================================================
	// Social Feed Handlers
	// =========================================================================

	/**
	 * Handle get feed.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_get_feed(): void {
		global $wpdb;

		$page       = (int) ( $this->get_param( 'page' ) ?? 1 );
		$per_page   = min( 50, max( 1, (int) ( $this->get_param( 'per_page' ) ?? 10 ) ) );
		$offset     = ( $page - 1 ) * $per_page;
		$table_name = $wpdb->prefix . 'apollo_activities';

		// Check if table exists.
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
			$this->success(
				array(
					'items' => array(),
					'total' => 0,
				)
			);
			return;
		}

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );

		$activities = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);

		$items = array();
		foreach ( $activities as $activity ) {
			$items[] = $this->prepare_activity( $activity );
		}

		$this->success(
			array(
				'items'    => $items,
				'total'    => $total,
				'page'     => $page,
				'per_page' => $per_page,
				'pages'    => ceil( $total / $per_page ),
			)
		);
	}

	/**
	 * Handle create activity.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_create_activity(): void {
		if ( ! $this->require_auth() || ! $this->require_nonce() ) {
			return;
		}

		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_activities';
		$user_id    = get_current_user_id();
		$content    = sanitize_textarea_field( $this->get_param( 'content', '' ) );

		if ( empty( $content ) ) {
			$this->error( 'content_required', __( 'O conteúdo é obrigatório.', 'apollo-core' ) );
			return;
		}

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'user_id'    => $user_id,
				'type'       => sanitize_text_field( $this->get_param( 'type', 'status' ) ),
				'content'    => $content,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			$this->error( 'create_failed', __( 'Falha ao criar atividade.', 'apollo-core' ) );
			return;
		}

		$activity = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $wpdb->insert_id )
		);

		$this->success(
			$this->prepare_activity( $activity ),
			__( 'Publicado com sucesso.', 'apollo-core' )
		);
	}

	/**
	 * Handle toggle like.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_toggle_like(): void {
		if ( ! $this->require_auth() || ! $this->require_nonce() ) {
			return;
		}

		global $wpdb;

		$activity_id = (int) $this->get_param( 'activity_id' );
		$user_id     = get_current_user_id();
		$table_name  = $wpdb->prefix . 'apollo_activity_likes';

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $table_name WHERE activity_id = %d AND user_id = %d",
				$activity_id,
				$user_id
			)
		);

		if ( $existing ) {
			$wpdb->delete(
				$table_name,
				array(
					'activity_id' => $activity_id,
					'user_id'     => $user_id,
				),
				array( '%d', '%d' )
			);
			$liked   = false;
			$message = __( 'Curtida removida.', 'apollo-core' );
		} else {
			$wpdb->insert(
				$table_name,
				array(
					'activity_id' => $activity_id,
					'user_id'     => $user_id,
					'created_at'  => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%s' )
			);
			$liked   = true;
			$message = __( 'Curtida adicionada.', 'apollo-core' );
		}

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE activity_id = %d",
				$activity_id
			)
		);

		$this->success(
			array(
				'activity_id' => $activity_id,
				'liked'       => $liked,
				'count'       => $count,
			),
			$message
		);
	}

	/**
	 * Handle add comment.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_add_comment(): void {
		if ( ! $this->require_auth() || ! $this->require_nonce() ) {
			return;
		}

		global $wpdb;

		$activity_id = (int) $this->get_param( 'activity_id' );
		$user_id     = get_current_user_id();
		$content     = sanitize_textarea_field( $this->get_param( 'content', '' ) );
		$table_name  = $wpdb->prefix . 'apollo_activity_comments';

		if ( empty( $content ) ) {
			$this->error( 'content_required', __( 'O comentário é obrigatório.', 'apollo-core' ) );
			return;
		}

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'activity_id' => $activity_id,
				'user_id'     => $user_id,
				'content'     => $content,
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s' )
		);

		if ( ! $inserted ) {
			$this->error( 'comment_failed', __( 'Falha ao adicionar comentário.', 'apollo-core' ) );
			return;
		}

		$user = get_userdata( $user_id );

		$this->success(
			array(
				'id'          => $wpdb->insert_id,
				'activity_id' => $activity_id,
				'content'     => $content,
				'author'      => array(
					'id'     => $user_id,
					'name'   => $user->display_name,
					'avatar' => get_avatar_url( $user_id, array( 'size' => 40 ) ),
				),
				'created_at'  => current_time( 'c' ),
			),
			__( 'Comentário adicionado.', 'apollo-core' )
		);
	}

	// =========================================================================
	// Profile Handlers
	// =========================================================================

	/**
	 * Handle get profile.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_get_profile(): void {
		$id   = (int) $this->get_param( 'user_id' );
		$user = get_userdata( $id );

		if ( ! $user ) {
			$this->error( 'not_found', __( 'Usuário não encontrado.', 'apollo-core' ), 404 );
			return;
		}

		$this->success( $this->prepare_profile( $user ) );
	}

	/**
	 * Handle update profile.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_update_profile(): void {
		if ( ! $this->require_auth() || ! $this->require_nonce() ) {
			return;
		}

		$user_id = get_current_user_id();

		$user_data = array( 'ID' => $user_id );

		$display_name = $this->get_param( 'display_name' );
		if ( $display_name ) {
			$user_data['display_name'] = sanitize_text_field( $display_name );
		}

		$description = $this->get_param( 'description' );
		if ( $description !== null ) {
			$user_data['description'] = sanitize_textarea_field( $description );
		}

		$updated = wp_update_user( $user_data );

		if ( is_wp_error( $updated ) ) {
			$this->error( 'update_failed', $updated->get_error_message() );
			return;
		}

		// Update custom meta.
		$meta_fields = array( 'location', 'phone', 'instagram', 'facebook', 'twitter' );
		foreach ( $meta_fields as $field ) {
			$value = $this->get_param( $field );
			if ( $value !== null ) {
				update_user_meta( $user_id, '_apollo_user_' . $field, sanitize_text_field( $value ) );
			}
		}

		$user = get_userdata( $user_id );
		$this->success( $this->prepare_profile( $user ), __( 'Perfil atualizado.', 'apollo-core' ) );
	}

	/**
	 * Handle toggle follow.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_toggle_follow(): void {
		if ( ! $this->require_auth() || ! $this->require_nonce() ) {
			return;
		}

		global $wpdb;

		$target_id  = (int) $this->get_param( 'user_id' );
		$user_id    = get_current_user_id();
		$table_name = $wpdb->prefix . 'apollo_user_follows';

		if ( $target_id === $user_id ) {
			$this->error( 'invalid', __( 'Você não pode seguir a si mesmo.', 'apollo-core' ) );
			return;
		}

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $table_name WHERE follower_id = %d AND following_id = %d",
				$user_id,
				$target_id
			)
		);

		if ( $existing ) {
			$wpdb->delete(
				$table_name,
				array(
					'follower_id'  => $user_id,
					'following_id' => $target_id,
				),
				array( '%d', '%d' )
			);
			$following = false;
			$message   = __( 'Você deixou de seguir.', 'apollo-core' );
		} else {
			$wpdb->insert(
				$table_name,
				array(
					'follower_id'  => $user_id,
					'following_id' => $target_id,
					'created_at'   => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%s' )
			);
			$following = true;
			$message   = __( 'Você está seguindo.', 'apollo-core' );
		}

		$this->success(
			array(
				'user_id'   => $target_id,
				'following' => $following,
			),
			$message
		);
	}

	// =========================================================================
	// Classifieds Handlers
	// =========================================================================

	/**
	 * Handle get classifieds.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_get_classifieds(): void {
		$page     = (int) ( $this->get_param( 'page' ) ?? 1 );
		$per_page = min( 100, max( 1, (int) ( $this->get_param( 'per_page' ) ?? 10 ) ) );
		$offset   = ( $page - 1 ) * $per_page;

		$args = array(
			'post_type'      => 'apollo_classified',
			'posts_per_page' => $per_page,
			'offset'         => $offset,
			'post_status'    => 'publish',
		);

		$search = $this->get_param( 'search' );
		if ( ! empty( $search ) ) {
			$args['s'] = sanitize_text_field( $search );
		}

		$category = $this->get_param( 'category' );
		if ( ! empty( $category ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'classified_category',
					'field'    => 'term_id',
					'terms'    => array_map( 'absint', (array) $category ),
				),
			);
		}

		$query = new \WP_Query( $args );

		$items = array();
		foreach ( $query->posts as $post ) {
			$items[] = $this->prepare_classified( $post );
		}

		$this->success(
			array(
				'items'    => $items,
				'total'    => $query->found_posts,
				'page'     => $page,
				'per_page' => $per_page,
				'pages'    => ceil( $query->found_posts / $per_page ),
			)
		);
	}

	/**
	 * Handle get single classified.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_get_classified(): void {
		$id   = (int) $this->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || $post->post_type !== 'apollo_classified' || $post->post_status !== 'publish' ) {
			$this->error( 'not_found', __( 'Classificado não encontrado.', 'apollo-core' ), 404 );
			return;
		}

		// Increment views.
		$views = (int) get_post_meta( $id, '_classified_views', true );
		update_post_meta( $id, '_classified_views', $views + 1 );

		$this->success( $this->prepare_classified( $post, true ) );
	}

	/**
	 * Handle send contact.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_send_contact(): void {
		if ( ! $this->require_auth() || ! $this->require_nonce() ) {
			return;
		}

		$id      = (int) $this->get_param( 'classified_id' );
		$message = sanitize_textarea_field( $this->get_param( 'message', '' ) );
		$post    = get_post( $id );

		if ( ! $post || $post->post_type !== 'apollo_classified' ) {
			$this->error( 'not_found', __( 'Classificado não encontrado.', 'apollo-core' ), 404 );
			return;
		}

		if ( empty( $message ) ) {
			$this->error( 'message_required', __( 'A mensagem é obrigatória.', 'apollo-core' ) );
			return;
		}

		$author = get_userdata( (int) $post->post_author );
		$sender = wp_get_current_user();

		if ( $sender->ID === $author->ID ) {
			$this->error( 'invalid', __( 'Você não pode contatar seu próprio anúncio.', 'apollo-core' ) );
			return;
		}

		$subject = sprintf( __( 'Contato sobre: %s', 'apollo-core' ), get_the_title( $post ) );
		$body    = sprintf(
			__( "Olá %1\$s,\n\n%2\$s enviou uma mensagem sobre seu anúncio \"%3\$s\":\n\n%4\$s\n\nResponda para: %5\$s", 'apollo-core' ),
			$author->display_name,
			$sender->display_name,
			get_the_title( $post ),
			$message,
			$sender->user_email
		);

		$sent = false;
		if ( class_exists( '\\Apollo\\Email\\UnifiedEmailService' ) ) {
			$result = \Apollo\Email\UnifiedEmailService::send(
				array(
					'to'      => $author->user_email,
					'type'    => 'classified_message',
					'subject' => $subject,
					'body'    => $body,
					'headers' => array( 'Reply-To: ' . $sender->user_email ),
					'data'    => array(
						'classified_id'    => $id,
						'classified_title' => get_the_title( $post ),
						'sender_id'        => $sender->ID,
						'sender_name'      => $sender->display_name,
						'sender_email'     => $sender->user_email,
					),
					'force'   => true,
				)
			);

			$sent = ( true === $result );
		}

		if ( ! $sent ) {
			$sent = wp_mail( $author->user_email, $subject, $body, array( 'Reply-To: ' . $sender->user_email ) );
		}

		if ( ! $sent ) {
			$this->error( 'email_failed', __( 'Falha ao enviar mensagem.', 'apollo-core' ) );
			return;
		}

		$this->success( array( 'sent' => true ), __( 'Mensagem enviada!', 'apollo-core' ) );
	}

	// =========================================================================
	// Data Preparation Helpers
	// =========================================================================

	/**
	 * Prepare event data.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Post $post The post object.
	 * @param bool     $full Include full content.
	 * @return array
	 */
	protected function prepare_event( \WP_Post $post, bool $full = false ): array {
		$data = array(
			'id'         => $post->ID,
			'title'      => get_the_title( $post ),
			'link'       => get_permalink( $post ),
			'excerpt'    => get_the_excerpt( $post ),
			'thumbnail'  => get_the_post_thumbnail_url( $post, 'medium' ),
			'start_date' => get_post_meta( $post->ID, '_event_start_date', true ),
			'start_time' => get_post_meta( $post->ID, '_event_start_time', true ),
			'venue'      => get_post_meta( $post->ID, '_event_venue', true ),
			'city'       => get_post_meta( $post->ID, '_event_city', true ),
		);

		if ( $full ) {
			$data['content']  = apply_filters( 'the_content', $post->post_content );
			$data['end_date'] = get_post_meta( $post->ID, '_event_end_date', true );
			$data['end_time'] = get_post_meta( $post->ID, '_event_end_time', true );
			$data['address']  = get_post_meta( $post->ID, '_event_address', true );
			$data['price']    = get_post_meta( $post->ID, '_event_price', true );
		}

		return $data;
	}

	/**
	 * Prepare activity data.
	 *
	 * @since 2.0.0
	 *
	 * @param object $activity The activity object.
	 * @return array
	 */
	protected function prepare_activity( object $activity ): array {
		global $wpdb;

		$user = get_userdata( (int) $activity->user_id );

		return array(
			'id'             => (int) $activity->id,
			'type'           => $activity->type,
			'content'        => $activity->content,
			'author'         => array(
				'id'     => (int) $activity->user_id,
				'name'   => $user ? $user->display_name : __( 'Usuário', 'apollo-core' ),
				'avatar' => get_avatar_url( (int) $activity->user_id, array( 'size' => 48 ) ),
			),
			'created_at'     => $activity->created_at,
			'time_ago'       => human_time_diff( strtotime( $activity->created_at ) ) . ' atrás',
			'likes_count'    => (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_activity_likes WHERE activity_id = %d",
					$activity->id
				)
			),
			'comments_count' => (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_activity_comments WHERE activity_id = %d",
					$activity->id
				)
			),
		);
	}

	/**
	 * Prepare profile data.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_User $user The user object.
	 * @return array
	 */
	protected function prepare_profile( \WP_User $user ): array {
		return array(
			'id'           => $user->ID,
			'display_name' => $user->display_name,
			'description'  => $user->description,
			'avatar'       => get_avatar_url( $user->ID, array( 'size' => 150 ) ),
			'meta'         => array(
				'location'  => get_user_meta( $user->ID, '_apollo_user_location', true ),
				'instagram' => get_user_meta( $user->ID, '_apollo_user_instagram', true ),
				'facebook'  => get_user_meta( $user->ID, '_apollo_user_facebook', true ),
			),
		);
	}

	/**
	 * Prepare classified data.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Post $post The post object.
	 * @param bool     $full Include full content.
	 * @return array
	 */
	protected function prepare_classified( \WP_Post $post, bool $full = false ): array {
		$price = (float) get_post_meta( $post->ID, '_classified_price', true );

		$data = array(
			'id'              => $post->ID,
			'title'           => get_the_title( $post ),
			'link'            => get_permalink( $post ),
			'excerpt'         => get_the_excerpt( $post ),
			'thumbnail'       => get_the_post_thumbnail_url( $post, 'medium' ),
			'price'           => $price,
			'price_formatted' => $price > 0 ? 'R$ ' . number_format( $price, 2, ',', '.' ) : __( 'A combinar', 'apollo-core' ),
			'type'            => get_post_meta( $post->ID, '_classified_type', true ) ?: 'sell',
			'location'        => get_post_meta( $post->ID, '_classified_location', true ),
		);

		if ( $full ) {
			$data['content']   = apply_filters( 'the_content', $post->post_content );
			$data['condition'] = get_post_meta( $post->ID, '_classified_condition', true );
			$data['phone']     = get_post_meta( $post->ID, '_classified_phone', true );
			$data['whatsapp']  = get_post_meta( $post->ID, '_classified_whatsapp', true );
		}

		return $data;
	}

	/**
	 * Get nonce for frontend.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_nonce(): string {
		return wp_create_nonce( $this->nonce_action );
	}
}

// Initialize.
Apollo_AJAX_Handler::get_instance();
