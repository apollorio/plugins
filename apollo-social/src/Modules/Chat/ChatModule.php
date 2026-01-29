<?php
/**
 * Apollo Chat Module.
 *
 * Módulo completo de chat/mensagens instantâneas para Apollo Social.
 *
 * Funcionalidades:
 * - Mensagens diretas (DMs) entre usuários.
 * - Conversas de grupo (núcleos, comunidades).
 * - Histórico de mensagens.
 * - Notificações de novas mensagens.
 * - Integração com Classifieds e Suppliers.
 *
 * @package Apollo\Modules\Chat
 * @since   2.0.0
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * phpcs:disable WordPress.PHP.YodaConditions.NotYoda
 */

declare(strict_types=1);

namespace Apollo\Modules\Chat;

use Apollo\Infrastructure\FeatureFlags;
use Apollo\Infrastructure\Http\Apollo_Router;

/**
 * Chat Module Initializer
 */
class ChatModule {

	/** @var string Module version */
	private const VERSION = '2.0.0';

	/** @var string Table name for conversations */
	public const TABLE_CONVERSATIONS = 'apollo_chat_conversations';

	/** @var string Table name for messages */
	public const TABLE_MESSAGES = 'apollo_chat_messages';

	/** @var string Table name for participants */
	public const TABLE_PARTICIPANTS = 'apollo_chat_participants';

	/** @var bool Initialized flag */
	private static bool $initialized = false;

	/**
	 * Initialize the module
	 */
	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}

		// Respect feature flag to avoid registering routes/endpoints when disabled.
		if ( FeatureFlags::isDisabled( 'chat' ) ) {
			return;
		}

		self::$initialized = true;

		// Register activation hook.
		if ( defined( '\APOLLO_SOCIAL_PLUGIN_FILE' ) ) {
			$plugin_file = \APOLLO_SOCIAL_PLUGIN_FILE;
		} else {
			$plugin_file = dirname( dirname( dirname( __DIR__ ) ) ) . '/apollo-social.php';
		}
		register_activation_hook( $plugin_file, array( self::class, 'activate' ) );

		// Initialize on plugins loaded.
		add_action( 'plugins_loaded', array( self::class, 'setup' ), 20 );

		// Register REST endpoints.
		add_action( 'rest_api_init', array( self::class, 'register_endpoints' ) );

		// Register AJAX handlers for real-time polling.
		add_action( 'wp_ajax_apollo_chat_send', array( self::class, 'ajax_send_message' ) );
		add_action( 'wp_ajax_apollo_chat_poll', array( self::class, 'ajax_poll_messages' ) );
		add_action( 'wp_ajax_apollo_chat_conversations', array( self::class, 'ajax_get_conversations' ) );
		add_action( 'wp_ajax_apollo_chat_history', array( self::class, 'ajax_get_history' ) );
		add_action( 'wp_ajax_apollo_chat_start', array( self::class, 'ajax_start_conversation' ) );
		add_action( 'wp_ajax_apollo_chat_mark_read', array( self::class, 'ajax_mark_read' ) );
	}

	/**
	 * Activate module (create tables)
	 */
	public static function activate( bool $with_flush = true ): void {
		// Skip activation work if feature is disabled.
		if ( FeatureFlags::isDisabled( 'chat' ) ) {
			return;
		}

		self::create_tables();

		if ( $with_flush ) {
			if ( class_exists( Apollo_Router::class ) ) {
				Apollo_Router::flush();
			} else {
				flush_rewrite_rules();
			}
		}

		update_option( 'apollo_chat_version', self::VERSION );
	}

	/**
	 * Setup module
	 */
	public static function setup(): void {
		$current_version = get_option( 'apollo_chat_version', '0.0.0' );

		if ( version_compare( $current_version, self::VERSION, '<' ) ) {
			// Run migrations without runtime flush to avoid frontend impact.
			self::activate( false );
		}
	}

	/**
	 * Create database tables
	 */
	public static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_conversations = $wpdb->prefix . self::TABLE_CONVERSATIONS;
		$table_messages      = $wpdb->prefix . self::TABLE_MESSAGES;
		$table_participants  = $wpdb->prefix . self::TABLE_PARTICIPANTS;

		$sql_conversations = "CREATE TABLE IF NOT EXISTS {$table_conversations} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			type ENUM('direct', 'group', 'nucleo', 'comunidade', 'classified', 'supplier') DEFAULT 'direct',
			title VARCHAR(255) DEFAULT NULL,
			context_type VARCHAR(50) DEFAULT NULL,
			context_id BIGINT(20) UNSIGNED DEFAULT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_type (type),
			KEY idx_context (context_type, context_id),
			KEY idx_updated (updated_at)
		) {$charset_collate};";

		$sql_messages = "CREATE TABLE IF NOT EXISTS {$table_messages} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			conversation_id BIGINT(20) UNSIGNED NOT NULL,
			sender_id BIGINT(20) UNSIGNED NOT NULL,
			message_type ENUM('text', 'image', 'file', 'system') DEFAULT 'text',
			content LONGTEXT NOT NULL,
			metadata JSON DEFAULT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_conversation (conversation_id),
			KEY idx_sender (sender_id),
			KEY idx_created (created_at)
		) {$charset_collate};";

		$sql_participants = "CREATE TABLE IF NOT EXISTS {$table_participants} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			conversation_id BIGINT(20) UNSIGNED NOT NULL,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			role ENUM('owner', 'admin', 'member') DEFAULT 'member',
			last_read_at DATETIME DEFAULT NULL,
			joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY idx_conv_user (conversation_id, user_id),
			KEY idx_user (user_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_conversations );
		dbDelta( $sql_messages );
		dbDelta( $sql_participants );
	}

	/**
	 * Register REST API endpoints
	 */
	public static function register_endpoints(): void {
		if ( FeatureFlags::isDisabled( 'chat' ) ) {
			return;
		}

		$namespace = 'apollo-social/v1';

		// Get conversations for current user.
		register_rest_route(
			$namespace,
			'/chat/conversations',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'rest_get_conversations' ),
				'permission_callback' => array( self::class, 'check_user_logged_in' ),
			)
		);

		// Get messages for a conversation.
		register_rest_route(
			$namespace,
			'/chat/messages/(?P<conversation_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'rest_get_messages' ),
				'permission_callback' => array( self::class, 'check_user_logged_in' ),
			)
		);

		// Send a message.
		register_rest_route(
			$namespace,
			'/chat/send',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'rest_send_message' ),
				'permission_callback' => array( self::class, 'check_user_logged_in' ),
			)
		);

		// Start a new conversation.
		register_rest_route(
			$namespace,
			'/chat/start',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'rest_start_conversation' ),
				'permission_callback' => array( self::class, 'check_user_logged_in' ),
			)
		);

		// Poll for new messages.
		register_rest_route(
			$namespace,
			'/chat/poll',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'rest_poll_messages' ),
				'permission_callback' => array( self::class, 'check_user_logged_in' ),
			)
		);

		// Start or get context-specific conversation (e.g., classifieds).
		register_rest_route(
			$namespace,
			'/chat/context-thread',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'rest_get_or_create_context_conversation' ),
				'permission_callback' => array( self::class, 'check_user_logged_in' ),
			)
		);
	}

	/**
	 * Permission callback: check if user is logged in
	 *
	 * @return bool
	 */
	public static function check_user_logged_in(): bool {
		return is_user_logged_in();
	}

	/**
	 * REST: Get conversations for current user
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public static function rest_get_conversations( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id       = get_current_user_id();
		$conversations = self::get_user_conversations( $user_id );

		return new \WP_REST_Response( $conversations, 200 );
	}

	/**
	 * REST: Get messages for a conversation
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public static function rest_get_messages( \WP_REST_Request $request ): \WP_REST_Response {
		$conversation_id = (int) $request->get_param( 'conversation_id' );
		$user_id         = get_current_user_id();
		$limit           = (int) $request->get_param( 'limit' ) ?: 50;
		$before_id       = (int) $request->get_param( 'before_id' ) ?: 0;

		// Verify user is participant.
		if ( ! self::is_participant( $conversation_id, $user_id ) ) {
			return new \WP_REST_Response( array( 'error' => 'Acesso negado' ), 403 );
		}

		$messages = self::get_messages( $conversation_id, $limit, $before_id );

		// Mark as read.
		self::mark_conversation_read( $conversation_id, $user_id );

		return new \WP_REST_Response( $messages, 200 );
	}

	/**
	 * REST: Send a message
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public static function rest_send_message( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id         = get_current_user_id();
		$conversation_id = (int) $request->get_param( 'conversation_id' );
		$content         = sanitize_textarea_field( $request->get_param( 'content' ) );
		$message_type    = sanitize_key( $request->get_param( 'type' ) ) ?: 'text';

		if ( empty( $content ) ) {
			return new \WP_REST_Response( array( 'error' => 'Mensagem vazia' ), 400 );
		}

		// Verify user is participant.
		if ( ! self::is_participant( $conversation_id, $user_id ) ) {
			return new \WP_REST_Response( array( 'error' => 'Acesso negado' ), 403 );
		}

		$message_id = self::insert_message( $conversation_id, $user_id, $content, $message_type );

		if ( ! $message_id ) {
			return new \WP_REST_Response( array( 'error' => 'Erro ao enviar mensagem' ), 500 );
		}

		$message = self::get_message_by_id( $message_id );

		return new \WP_REST_Response( $message, 201 );
	}

	/**
	 * REST: Start a new conversation
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public static function rest_start_conversation( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id      = get_current_user_id();
		$type         = sanitize_key( $request->get_param( 'type' ) ) ?: 'direct';
		$recipient_id = (int) $request->get_param( 'recipient_id' );
		$context_type = sanitize_key( $request->get_param( 'context_type' ) );
		$context_id   = (int) $request->get_param( 'context_id' );
		$title        = sanitize_text_field( $request->get_param( 'title' ) );

		// For direct messages, check if conversation already exists.
		if ( 'direct' === $type && $recipient_id ) {
			$existing = self::find_direct_conversation( $user_id, $recipient_id );
			if ( $existing ) {
				return new \WP_REST_Response( $existing, 200 );
			}
		}

		// Create new conversation.
		$conversation_id = self::create_conversation( $type, $title, $context_type, $context_id );

		if ( ! $conversation_id ) {
			return new \WP_REST_Response( array( 'error' => 'Erro ao criar conversa' ), 500 );
		}

		// Add participants.
		self::add_participant( $conversation_id, $user_id, 'owner' );

		if ( $recipient_id ) {
			self::add_participant( $conversation_id, $recipient_id, 'member' );
		}

		$conversation = self::get_conversation_by_id( $conversation_id );

		return new \WP_REST_Response( $conversation, 201 );
	}

	/**
	 * REST: Poll for new messages
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public static function rest_poll_messages( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id  = get_current_user_id();
		$since    = $request->get_param( 'since' );
		$messages = self::get_new_messages_for_user( $user_id, $since );

		return new \WP_REST_Response( $messages, 200 );
	}

	/**
	 * REST: Get or create context-specific conversation (e.g., classifieds)
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public static function rest_get_or_create_context_conversation( \WP_REST_Request $request ): \WP_REST_Response {
		$buyer_id    = get_current_user_id();
		$context     = sanitize_key( $request->get_param( 'context' ) );
		$entity_type = sanitize_key( $request->get_param( 'entity_type' ) );
		$entity_id   = (int) $request->get_param( 'entity_id' );
		$seller_id   = (int) $request->get_param( 'seller_id' );

		// Validation
		if ( ! $context || ! $entity_type || ! $entity_id || ! $seller_id ) {
			return new \WP_REST_Response( array( 'error' => 'Parâmetros obrigatórios ausentes' ), 400 );
		}

		if ( $buyer_id === $seller_id ) {
			return new \WP_REST_Response( array( 'error' => 'Não é possível conversar consigo mesmo' ), 400 );
		}

		// Validate entity exists (for classifieds, check if post exists and is published)
		if ( 'classified' === $context && 'ad' === $entity_type ) {
			$post = get_post( $entity_id );
			if ( ! $post || 'publish' !== $post->post_status ) {
				return new \WP_REST_Response( array( 'error' => 'Anúncio não encontrado ou não publicado' ), 404 );
			}
			// Verify seller is the post author
			if ( (int) $post->post_author !== $seller_id ) {
				return new \WP_REST_Response( array( 'error' => 'Vendedor inválido para este anúncio' ), 400 );
			}
		}

		// Find existing conversation
		$existing = self::find_context_conversation( $context, $entity_type, $entity_id, $buyer_id, $seller_id );
		if ( $existing ) {
			return new \WP_REST_Response(
				array(
					'success'         => true,
					'conversation_id' => (int) $existing['id'],
					'open_url'        => self::get_chat_open_url( (int) $existing['id'] ),
					'metadata'        => self::get_conversation_metadata( $existing ),
				),
				200
			);
		}

		// Create new conversation
		$conversation_id = self::create_context_conversation( $context, $entity_type, $entity_id, $buyer_id, $seller_id );
		if ( ! $conversation_id ) {
			return new \WP_REST_Response( array( 'error' => 'Erro ao criar conversa' ), 500 );
		}

		$conversation = self::get_conversation_by_id( $conversation_id );

		// Send initial message
		$initial_message = self::get_initial_message( $context, $entity_type, $entity_id );
		if ( $initial_message ) {
			self::insert_message( $conversation_id, $buyer_id, $initial_message, 'text' );
		}

		return new \WP_REST_Response(
			array(
				'success'         => true,
				'conversation_id' => (int) $conversation_id,
				'open_url'        => self::get_chat_open_url( (int) $conversation_id ),
				'metadata'        => self::get_conversation_metadata( $conversation ),
			),
			201
		);
	}

	// =========================================================================
	// AJAX HANDLERS
	// =========================================================================

	/**
	 * AJAX: Send message
	 */
	public static function ajax_send_message(): void {
		check_ajax_referer( 'apollo_chat_nonce', 'nonce' );

		$user_id         = get_current_user_id();
		$conversation_id = (int) ( $_POST['conversation_id'] ?? 0 );
		$content         = sanitize_textarea_field( $_POST['content'] ?? '' );
		$message_type    = sanitize_key( $_POST['type'] ?? 'text' );

		if ( ! $user_id || ! $conversation_id || empty( $content ) ) {
			wp_send_json_error( array( 'message' => 'Dados inválidos' ) );
		}

		if ( ! self::is_participant( $conversation_id, $user_id ) ) {
			wp_send_json_error( array( 'message' => 'Acesso negado' ) );
		}

		$message_id = self::insert_message( $conversation_id, $user_id, $content, $message_type );

		if ( $message_id ) {
			$message = self::get_message_by_id( $message_id );
			wp_send_json_success( $message );
		} else {
			wp_send_json_error( array( 'message' => 'Erro ao enviar' ) );
		}
	}

	/**
	 * AJAX: Poll for new messages
	 */
	public static function ajax_poll_messages(): void {
		check_ajax_referer( 'apollo_chat_nonce', 'nonce' );

		$user_id = get_current_user_id();
		$since   = sanitize_text_field( $_GET['since'] ?? '' );

		$messages = self::get_new_messages_for_user( $user_id, $since );
		wp_send_json_success( $messages );
	}

	/**
	 * AJAX: Get conversations
	 */
	public static function ajax_get_conversations(): void {
		check_ajax_referer( 'apollo_chat_nonce', 'nonce' );

		$user_id       = get_current_user_id();
		$conversations = self::get_user_conversations( $user_id );
		wp_send_json_success( $conversations );
	}

	/**
	 * AJAX: Get message history
	 */
	public static function ajax_get_history(): void {
		check_ajax_referer( 'apollo_chat_nonce', 'nonce' );

		$user_id         = get_current_user_id();
		$conversation_id = (int) ( $_GET['conversation_id'] ?? 0 );
		$limit           = (int) ( $_GET['limit'] ?? 50 );
		$before_id       = (int) ( $_GET['before_id'] ?? 0 );

		if ( ! self::is_participant( $conversation_id, $user_id ) ) {
			wp_send_json_error( array( 'message' => 'Acesso negado' ) );
		}

		$messages = self::get_messages( $conversation_id, $limit, $before_id );
		self::mark_conversation_read( $conversation_id, $user_id );

		wp_send_json_success( $messages );
	}

	/**
	 * AJAX: Start conversation
	 */
	public static function ajax_start_conversation(): void {
		check_ajax_referer( 'apollo_chat_nonce', 'nonce' );

		$user_id      = get_current_user_id();
		$type         = sanitize_key( $_POST['type'] ?? 'direct' );
		$recipient_id = (int) ( $_POST['recipient_id'] ?? 0 );
		$context_type = sanitize_key( $_POST['context_type'] ?? '' );
		$context_id   = (int) ( $_POST['context_id'] ?? 0 );
		$title        = sanitize_text_field( $_POST['title'] ?? '' );

		// For direct messages, check existing.
		if ( 'direct' === $type && $recipient_id ) {
			$existing = self::find_direct_conversation( $user_id, $recipient_id );
			if ( $existing ) {
				wp_send_json_success( $existing );
			}
		}

		$conversation_id = self::create_conversation( $type, $title, $context_type, $context_id );

		if ( ! $conversation_id ) {
			wp_send_json_error( array( 'message' => 'Erro ao criar conversa' ) );
		}

		self::add_participant( $conversation_id, $user_id, 'owner' );

		if ( $recipient_id ) {
			self::add_participant( $conversation_id, $recipient_id, 'member' );
		}

		$conversation = self::get_conversation_by_id( $conversation_id );
		wp_send_json_success( $conversation );
	}

	/**
	 * AJAX: Mark conversation as read
	 */
	public static function ajax_mark_read(): void {
		check_ajax_referer( 'apollo_chat_nonce', 'nonce' );

		$user_id         = get_current_user_id();
		$conversation_id = (int) ( $_POST['conversation_id'] ?? 0 );

		if ( self::is_participant( $conversation_id, $user_id ) ) {
			self::mark_conversation_read( $conversation_id, $user_id );
			wp_send_json_success();
		} else {
			wp_send_json_error( array( 'message' => 'Acesso negado' ) );
		}
	}

	// =========================================================================
	// DATA METHODS
	// =========================================================================

	/**
	 * Get conversations for a user
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	public static function get_user_conversations( int $user_id ): array {
		global $wpdb;

		$table_conv = $wpdb->prefix . self::TABLE_CONVERSATIONS;
		$table_part = $wpdb->prefix . self::TABLE_PARTICIPANTS;
		$table_msg  = $wpdb->prefix . self::TABLE_MESSAGES;

		$sql = $wpdb->prepare(
			"SELECT c.*, p.last_read_at, p.role,
				(SELECT COUNT(*) FROM {$table_msg} m
				 WHERE m.conversation_id = c.id
				 AND m.created_at > IFNULL(p.last_read_at, '1970-01-01')) as unread_count,
				(SELECT m2.content FROM {$table_msg} m2
				 WHERE m2.conversation_id = c.id
				 ORDER BY m2.created_at DESC LIMIT 1) as last_message,
				(SELECT m3.sender_id FROM {$table_msg} m3
				 WHERE m3.conversation_id = c.id
				 ORDER BY m3.created_at DESC LIMIT 1) as last_sender_id,
				(SELECT m4.created_at FROM {$table_msg} m4
				 WHERE m4.conversation_id = c.id
				 ORDER BY m4.created_at DESC LIMIT 1) as last_message_at
			FROM {$table_conv} c
			INNER JOIN {$table_part} p ON c.id = p.conversation_id
			WHERE p.user_id = %d
			ORDER BY last_message_at DESC, c.updated_at DESC",
			$user_id
		);

		$conversations = $wpdb->get_results( $sql, ARRAY_A );

		// Enrich with participant info.
		foreach ( $conversations as &$conv ) {
			$conv['participants'] = self::get_participants( (int) $conv['id'] );
			if ( ! empty( $conv['last_sender_id'] ) ) {
				$sender                   = get_userdata( (int) $conv['last_sender_id'] );
				$conv['last_sender_name'] = $sender ? $sender->display_name : 'Usuário';
			}
		}

		return $conversations ?: array();
	}

	/**
	 * Get messages for a conversation
	 *
	 * @param int $conversation_id Conversation ID.
	 * @param int $limit           Limit.
	 * @param int $before_id       Load messages before this ID.
	 * @return array
	 */
	public static function get_messages( int $conversation_id, int $limit = 50, int $before_id = 0 ): array {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_MESSAGES;

		$where_before = '';
		if ( $before_id > 0 ) {
			$where_before = $wpdb->prepare( ' AND id < %d', $before_id );
		}

		$sql = $wpdb->prepare(
			"SELECT * FROM {$table}
			WHERE conversation_id = %d {$where_before}
			ORDER BY created_at DESC
			LIMIT %d",
			$conversation_id,
			$limit
		);

		$messages = $wpdb->get_results( $sql, ARRAY_A );

		// Enrich with sender info.
		foreach ( $messages as &$msg ) {
			$sender        = get_userdata( (int) $msg['sender_id'] );
			$msg['sender'] = array(
				'id'     => (int) $msg['sender_id'],
				'name'   => $sender ? $sender->display_name : 'Usuário',
				'avatar' => get_avatar_url( (int) $msg['sender_id'], array( 'size' => 48 ) ),
			);
		}

		return array_reverse( $messages ) ?: array();
	}

	/**
	 * Get new messages for a user since a timestamp
	 *
	 * @param int    $user_id User ID.
	 * @param string $since   ISO timestamp.
	 * @return array
	 */
	public static function get_new_messages_for_user( int $user_id, string $since = '' ): array {
		global $wpdb;

		$table_msg  = $wpdb->prefix . self::TABLE_MESSAGES;
		$table_part = $wpdb->prefix . self::TABLE_PARTICIPANTS;

		$since_clause = '';
		if ( ! empty( $since ) ) {
			$since_clause = $wpdb->prepare( ' AND m.created_at > %s', $since );
		}

		$sql = $wpdb->prepare(
			"SELECT m.*, c.type as conversation_type
			FROM {$table_msg} m
			INNER JOIN {$table_part} p ON m.conversation_id = p.conversation_id
			LEFT JOIN {$wpdb->prefix}" . self::TABLE_CONVERSATIONS . " c ON m.conversation_id = c.id
			WHERE p.user_id = %d
			AND m.sender_id != %d
			{$since_clause}
			ORDER BY m.created_at ASC
			LIMIT 100",
			$user_id,
			$user_id
		);

		$messages = $wpdb->get_results( $sql, ARRAY_A );

		// Enrich.
		foreach ( $messages as &$msg ) {
			$sender        = get_userdata( (int) $msg['sender_id'] );
			$msg['sender'] = array(
				'id'     => (int) $msg['sender_id'],
				'name'   => $sender ? $sender->display_name : 'Usuário',
				'avatar' => get_avatar_url( (int) $msg['sender_id'], array( 'size' => 48 ) ),
			);
		}

		return $messages ?: array();
	}

	/**
	 * Insert a new message
	 *
	 * @param int    $conversation_id Conversation ID.
	 * @param int    $sender_id       Sender user ID.
	 * @param string $content         Message content.
	 * @param string $message_type    Message type.
	 * @return int|false Message ID or false.
	 */
	public static function insert_message( int $conversation_id, int $sender_id, string $content, string $message_type = 'text' ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_MESSAGES;

		$inserted = $wpdb->insert(
			$table,
			array(
				'conversation_id' => $conversation_id,
				'sender_id'       => $sender_id,
				'content'         => $content,
				'message_type'    => $message_type,
				'created_at'      => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s' )
		);

		if ( $inserted ) {
			// Update conversation timestamp.
			$wpdb->update(
				$wpdb->prefix . self::TABLE_CONVERSATIONS,
				array( 'updated_at' => current_time( 'mysql' ) ),
				array( 'id' => $conversation_id ),
				array( '%s' ),
				array( '%d' )
			);

			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Get message by ID
	 *
	 * @param int $message_id Message ID.
	 * @return array|null
	 */
	public static function get_message_by_id( int $message_id ): ?array {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_MESSAGES;

		$msg = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $message_id ),
			ARRAY_A
		);

		if ( $msg ) {
			$sender        = get_userdata( (int) $msg['sender_id'] );
			$msg['sender'] = array(
				'id'     => (int) $msg['sender_id'],
				'name'   => $sender ? $sender->display_name : 'Usuário',
				'avatar' => get_avatar_url( (int) $msg['sender_id'], array( 'size' => 48 ) ),
			);
		}

		return $msg;
	}

	/**
	 * Create a new conversation
	 *
	 * @param string $type         Conversation type.
	 * @param string $title        Title (for groups).
	 * @param string $context_type Context type (classified, supplier, etc).
	 * @param int    $context_id   Context ID.
	 * @return int|false Conversation ID or false.
	 */
	public static function create_conversation( string $type, string $title = '', string $context_type = '', int $context_id = 0 ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_CONVERSATIONS;

		$data = array(
			'type'       => $type,
			'title'      => $title ?: null,
			'created_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
		);

		$format = array( '%s', '%s', '%s', '%s' );

		if ( ! empty( $context_type ) ) {
			$data['context_type'] = $context_type;
			$data['context_id']   = $context_id;
			$format[]             = '%s';
			$format[]             = '%d';
		}

		$inserted = $wpdb->insert( $table, $data, $format );

		return $inserted ? $wpdb->insert_id : false;
	}

	/**
	 * Get conversation by ID
	 *
	 * @param int $conversation_id Conversation ID.
	 * @return array|null
	 */
	public static function get_conversation_by_id( int $conversation_id ): ?array {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_CONVERSATIONS;

		$conv = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $conversation_id ),
			ARRAY_A
		);

		if ( $conv ) {
			$conv['participants'] = self::get_participants( $conversation_id );
		}

		return $conv;
	}

	/**
	 * Find existing direct conversation between two users
	 *
	 * @param int $user1_id First user ID.
	 * @param int $user2_id Second user ID.
	 * @return array|null
	 */
	public static function find_direct_conversation( int $user1_id, int $user2_id ): ?array {
		global $wpdb;

		$table_conv = $wpdb->prefix . self::TABLE_CONVERSATIONS;
		$table_part = $wpdb->prefix . self::TABLE_PARTICIPANTS;

		$sql = $wpdb->prepare(
			"SELECT c.id FROM {$table_conv} c
			INNER JOIN {$table_part} p1 ON c.id = p1.conversation_id AND p1.user_id = %d
			INNER JOIN {$table_part} p2 ON c.id = p2.conversation_id AND p2.user_id = %d
			WHERE c.type = 'direct'
			LIMIT 1",
			$user1_id,
			$user2_id
		);

		$result = $wpdb->get_var( $sql );

		if ( $result ) {
			return self::get_conversation_by_id( (int) $result );
		}

		return null;
	}

	/**
	 * Add a participant to a conversation
	 *
	 * @param int    $conversation_id Conversation ID.
	 * @param int    $user_id         User ID.
	 * @param string $role            Role (owner, admin, member).
	 * @return bool
	 */
	public static function add_participant( int $conversation_id, int $user_id, string $role = 'member' ): bool {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_PARTICIPANTS;

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE conversation_id = %d AND user_id = %d",
				$conversation_id,
				$user_id
			)
		);

		if ( $existing ) {
			return true;
		}

		$inserted = $wpdb->insert(
			$table,
			array(
				'conversation_id' => $conversation_id,
				'user_id'         => $user_id,
				'role'            => $role,
				'joined_at'       => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s' )
		);

		return (bool) $inserted;
	}

	/**
	 * Get participants for a conversation
	 *
	 * @param int $conversation_id Conversation ID.
	 * @return array
	 */
	public static function get_participants( int $conversation_id ): array {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_PARTICIPANTS;

		$participants = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE conversation_id = %d",
				$conversation_id
			),
			ARRAY_A
		);

		foreach ( $participants as &$p ) {
			$user        = get_userdata( (int) $p['user_id'] );
			$p['name']   = $user ? $user->display_name : 'Usuário';
			$p['avatar'] = get_avatar_url( (int) $p['user_id'], array( 'size' => 48 ) );
			$p['login']  = $user ? $user->user_login : '';
		}

		return $participants ?: array();
	}

	/**
	 * Check if a user is a participant in a conversation
	 *
	 * @param int $conversation_id Conversation ID.
	 * @param int $user_id         User ID.
	 * @return bool
	 */
	public static function is_participant( int $conversation_id, int $user_id ): bool {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_PARTICIPANTS;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE conversation_id = %d AND user_id = %d",
				$conversation_id,
				$user_id
			)
		);

		return (bool) $result;
	}

	/**
	 * Mark a conversation as read for a user
	 *
	 * @param int $conversation_id Conversation ID.
	 * @param int $user_id         User ID.
	 * @return bool
	 */
	public static function mark_conversation_read( int $conversation_id, int $user_id ): bool {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_PARTICIPANTS;

		$updated = $wpdb->update(
			$table,
			array( 'last_read_at' => current_time( 'mysql' ) ),
			array(
				'conversation_id' => $conversation_id,
				'user_id'         => $user_id,
			),
			array( '%s' ),
			array( '%d', '%d' )
		);

		return false !== $updated;
	}

	// =========================================================================
	// INTEGRATION HELPERS (for Classifieds & Suppliers)
	// =========================================================================

	/**
	 * Start or get conversation for a classified ad
	 *
	 * @param int $ad_id        Classified ad ID.
	 * @param int $user_id      User starting the conversation.
	 * @param int $seller_id    Seller user ID.
	 * @return array|null
	 */
	public static function get_or_create_classified_conversation( int $ad_id, int $user_id, int $seller_id ): ?array {
		global $wpdb;

		$table_conv = $wpdb->prefix . self::TABLE_CONVERSATIONS;
		$table_part = $wpdb->prefix . self::TABLE_PARTICIPANTS;

		// Check if conversation exists for this ad between these users.
		$sql = $wpdb->prepare(
			"SELECT c.id FROM {$table_conv} c
			INNER JOIN {$table_part} p1 ON c.id = p1.conversation_id AND p1.user_id = %d
			INNER JOIN {$table_part} p2 ON c.id = p2.conversation_id AND p2.user_id = %d
			WHERE c.context_type = 'classified' AND c.context_id = %d
			LIMIT 1",
			$user_id,
			$seller_id,
			$ad_id
		);

		$existing_id = $wpdb->get_var( $sql );

		if ( $existing_id ) {
			return self::get_conversation_by_id( (int) $existing_id );
		}

		// Create new conversation.
		$ad    = get_post( $ad_id );
		$title = $ad ? 'Anúncio: ' . $ad->post_title : 'Anúncio #' . $ad_id;

		$conversation_id = self::create_conversation( 'classified', $title, 'classified', $ad_id );

		if ( ! $conversation_id ) {
			return null;
		}

		self::add_participant( $conversation_id, $user_id, 'member' );
		self::add_participant( $conversation_id, $seller_id, 'owner' );

		return self::get_conversation_by_id( $conversation_id );
	}

	/**
	 * Start or get conversation for a supplier
	 *
	 * @param int $supplier_id Supplier post ID.
	 * @param int $user_id     User starting the conversation.
	 * @param int $owner_id    Supplier owner user ID.
	 * @return array|null
	 */
	public static function get_or_create_supplier_conversation( int $supplier_id, int $user_id, int $owner_id ): ?array {
		global $wpdb;

		$table_conv = $wpdb->prefix . self::TABLE_CONVERSATIONS;
		$table_part = $wpdb->prefix . self::TABLE_PARTICIPANTS;

		// Check if conversation exists.
		$sql = $wpdb->prepare(
			"SELECT c.id FROM {$table_conv} c
			INNER JOIN {$table_part} p1 ON c.id = p1.conversation_id AND p1.user_id = %d
			INNER JOIN {$table_part} p2 ON c.id = p2.conversation_id AND p2.user_id = %d
			WHERE c.context_type = 'supplier' AND c.context_id = %d
			LIMIT 1",
			$user_id,
			$owner_id,
			$supplier_id
		);

		$existing_id = $wpdb->get_var( $sql );

		if ( $existing_id ) {
			return self::get_conversation_by_id( (int) $existing_id );
		}

		// Create new conversation.
		$supplier = get_post( $supplier_id );
		$title    = $supplier ? 'Fornecedor: ' . $supplier->post_title : 'Fornecedor #' . $supplier_id;

		$conversation_id = self::create_conversation( 'supplier', $title, 'supplier', $supplier_id );

		if ( ! $conversation_id ) {
			return null;
		}

		self::add_participant( $conversation_id, $user_id, 'member' );
		self::add_participant( $conversation_id, $owner_id, 'owner' );

		return self::get_conversation_by_id( $conversation_id );
	}

	/**
	 * Get unread message count for a user
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	public static function get_unread_count( int $user_id ): int {
		global $wpdb;

		$table_msg  = $wpdb->prefix . self::TABLE_MESSAGES;
		$table_part = $wpdb->prefix . self::TABLE_PARTICIPANTS;

		$sql = $wpdb->prepare(
			"SELECT COUNT(m.id) FROM {$table_msg} m
			INNER JOIN {$table_part} p ON m.conversation_id = p.conversation_id
			WHERE p.user_id = %d
			AND m.sender_id != %d
			AND m.created_at > IFNULL(p.last_read_at, '1970-01-01')",
			$user_id,
			$user_id
		);

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Find existing context-specific conversation
	 *
	 * @param string $context     Context (e.g., 'classified').
	 * @param string $entity_type Entity type (e.g., 'ad').
	 * @param int    $entity_id   Entity ID.
	 * @param int    $buyer_id    Buyer user ID.
	 * @param int    $seller_id   Seller user ID.
	 * @return array|null
	 */
	public static function find_context_conversation( string $context, string $entity_type, int $entity_id, int $buyer_id, int $seller_id ): ?array {
		global $wpdb;

		$table_conv = $wpdb->prefix . self::TABLE_CONVERSATIONS;
		$table_part = $wpdb->prefix . self::TABLE_PARTICIPANTS;

		$sql = $wpdb->prepare(
			"SELECT c.id FROM {$table_conv} c
			INNER JOIN {$table_part} p1 ON c.id = p1.conversation_id AND p1.user_id = %d
			INNER JOIN {$table_part} p2 ON c.id = p2.conversation_id AND p2.user_id = %d
			WHERE c.context_type = %s AND c.context_id = %d
			LIMIT 1",
			$buyer_id,
			$seller_id,
			$context,
			$entity_id
		);

		$existing_id = $wpdb->get_var( $sql );

		if ( $existing_id ) {
			return self::get_conversation_by_id( (int) $existing_id );
		}

		return null;
	}

	/**
	 * Create context-specific conversation
	 *
	 * @param string $context     Context.
	 * @param string $entity_type Entity type.
	 * @param int    $entity_id   Entity ID.
	 * @param int    $buyer_id    Buyer user ID.
	 * @param int    $seller_id   Seller user ID.
	 * @return int|null Conversation ID.
	 */
	public static function create_context_conversation( string $context, string $entity_type, int $entity_id, int $buyer_id, int $seller_id ): ?int {
		$post  = get_post( $entity_id );
		$title = $post ? esc_html( $post->post_title ) : "Conversa sobre {$entity_type} #{$entity_id}";

		$conversation_id = self::create_conversation( $context, $title, $context, $entity_id );

		if ( ! $conversation_id ) {
			return null;
		}

		self::add_participant( $conversation_id, $buyer_id, 'member' );
		self::add_participant( $conversation_id, $seller_id, 'owner' );

		return $conversation_id;
	}

	/**
	 * Get initial message for context
	 *
	 * @param string $context     Context.
	 * @param string $entity_type Entity type.
	 * @param int    $entity_id   Entity ID.
	 * @return string|null
	 */
	public static function get_initial_message( string $context, string $entity_type, int $entity_id ): ?string {
		if ( 'classified' === $context && 'ad' === $entity_type ) {
			$post = get_post( $entity_id );
			if ( $post ) {
				return sprintf(
					'Olá! Tenho interesse no anúncio "%s". Ainda está disponível?',
					esc_html( $post->post_title )
				);
			}
		}

		return null;
	}

	/**
	 * Get chat open URL for conversation
	 *
	 * @param int $conversation_id Conversation ID.
	 * @return string
	 */
	public static function get_chat_open_url( int $conversation_id ): string {
		return home_url( '/chat/?conversation=' . $conversation_id );
	}

	/**
	 * Get conversation metadata for frontend
	 *
	 * @param array $conversation Conversation data.
	 * @return array
	 */
	public static function get_conversation_metadata( array $conversation ): array {
		$metadata = array(
			'id'    => $conversation['id'],
			'title' => $conversation['title'],
			'type'  => $conversation['type'],
		);

		if ( 'classified' === $conversation['context_type'] && $conversation['context_id'] ) {
			$post = get_post( $conversation['context_id'] );
			if ( $post ) {
				$metadata['ad_title'] = esc_html( $post->post_title );
				$metadata['ad_url']   = get_permalink( $post );
				$metadata['ad_price'] = get_post_meta( $post->ID, '_classified_price', true );
				$metadata['ad_image'] = get_the_post_thumbnail_url( $post->ID, 'thumbnail' );
			}
		}

		return $metadata;
	}
}
