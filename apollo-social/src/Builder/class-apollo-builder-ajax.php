<?php
/**
 * Apollo Builder AJAX Handlers
 *
 * Handles all AJAX requests for the builder.
 *
 * Pattern source: WOW Page Builder Ajax.php - wow_page_save(), wow_render_addon()
 * Pattern source: Live Composer ajax.php - capability check + nonce verification
 *
 * Security measures applied:
 * - Nonce verification (wp_verify_nonce) with specific action names
 * - Capability check (current_user_can)
 * - Ownership check (post_author)
 * - Input sanitization (all inputs sanitized before use)
 * - Response standardization via Apollo\API\Response
 *
 * @package Apollo_Social
 * @since 1.4.0
 */

defined( 'ABSPATH' ) || exit;

use Apollo\API\Response;

/**
 * Class Apollo_Builder_Ajax
 *
 * Tooltip: All AJAX handlers for Apollo Builder operations.
 * Data flow: Frontend JS → AJAX → This class → Database
 */
class Apollo_Builder_Ajax {

	/**
	 * Nonce action prefix for all builder actions
	 * Tooltip: Used to generate unique nonce actions per endpoint
	 */
	const NONCE_PREFIX = 'apollo_builder_';

	/**
	 * Initialize AJAX handlers
	 *
	 * Tooltip: Registers all wp_ajax_* hooks for builder functionality
	 */
	public static function init() {
		// Save layout (pattern: WOW wow_page_save)
		// Tooltip: Saves layout JSON to _apollo_builder_content meta
		add_action( 'wp_ajax_apollo_builder_save', array( __CLASS__, 'save_layout' ) );

		// Render widget (pattern: WOW wow_render_addon)
		// Tooltip: Returns rendered HTML for a widget preview
		add_action( 'wp_ajax_apollo_builder_render_widget', array( __CLASS__, 'render_widget' ) );

		// Get widget settings form
		// Tooltip: Returns widget configuration form data
		add_action( 'wp_ajax_apollo_builder_widget_form', array( __CLASS__, 'widget_form' ) );

		// Update background
		// Tooltip: Updates _apollo_background_texture meta
		add_action( 'wp_ajax_apollo_builder_update_bg', array( __CLASS__, 'update_background' ) );

		// Update trax URL
		// Tooltip: Updates _apollo_trax_url meta (SoundCloud/Spotify only)
		add_action( 'wp_ajax_apollo_builder_update_trax', array( __CLASS__, 'update_trax' ) );

		// Add depoimento (guestbook comment)
		// Tooltip: Creates WP comment as "depoimento" on apollo_home post
		add_action( 'wp_ajax_apollo_builder_add_depoimento', array( __CLASS__, 'add_depoimento' ) );
		add_action( 'wp_ajax_nopriv_apollo_builder_add_depoimento', array( __CLASS__, 'add_depoimento' ) );

		// Get all widgets data
		// Tooltip: Returns all registered widget definitions for sidebar
		add_action( 'wp_ajax_apollo_builder_get_widgets', array( __CLASS__, 'get_widgets' ) );
	}

	/**
	 * Verify request security
	 *
	 * Pattern: Live Composer - is_user_logged_in() && current_user_can() && wp_verify_nonce()
	 * Tooltip: Security check combining authentication, authorization, and CSRF protection
	 *
	 * @param string   $action Specific action name for nonce (e.g., 'save', 'update_bg')
	 * @param int|null $required_post_id If provided, checks ownership
	 * @return array|false [user_id, post] on success, false on failure
	 */
	private static function verify_request( string $action = 'general', $required_post_id = null ) {
		// Nonce check with specific action
		$nonce        = $_REQUEST['_wpnonce'] ?? $_REQUEST['nonce'] ?? '';
		$nonce_action = self::NONCE_PREFIX . $action;

		// Also accept generic builder nonce for backwards compatibility
		if ( ! wp_verify_nonce( $nonce, $nonce_action ) && ! wp_verify_nonce( $nonce, 'apollo-builder-nonce' ) ) {
			self::send_error( 'invalid_nonce', __( 'Verificação de segurança falhou. Recarregue a página.', 'apollo-social' ), 403 );
			return false;
		}

		// Auth check (pattern: Live Composer)
		if ( ! is_user_logged_in() ) {
			self::send_error( 'not_logged_in', __( 'Autenticação necessária.', 'apollo-social' ), 401 );
			return false;
		}

		// Capability check (pattern: Live Composer DS_LIVE_COMPOSER_CAPABILITY)
		if ( ! current_user_can( APOLLO_BUILDER_CAPABILITY ) ) {
			self::send_error( 'insufficient_capability', __( 'Permissão negada.', 'apollo-social' ), 403 );
			return false;
		}

		$user_id = get_current_user_id();
		$post    = null;

		// Ownership check if post_id required
		if ( $required_post_id !== null ) {
			$post_id = absint( $required_post_id );
			$post    = get_post( $post_id );

			if ( ! $post || $post->post_type !== Apollo_Home_CPT::POST_TYPE ) {
				self::send_error( 'invalid_post', __( 'Home inválida.', 'apollo-social' ), 404 );
				return false;
			}

			if ( ! Apollo_Home_CPT::user_can_edit( $post_id, $user_id ) ) {
				self::send_error( 'not_owner', __( 'Você não pode editar esta home.', 'apollo-social' ), 403 );
				return false;
			}
		}

		return array( $user_id, $post );
	}

	/**
	 * Send error response using Apollo\API\Response pattern
	 *
	 * Tooltip: Standardized error response compatible with Apollo API patterns
	 *
	 * @param string $code    Error code
	 * @param string $message User-facing message
	 * @param int    $status  HTTP status code
	 */
	private static function send_error( string $code, string $message, int $status = 400 ): void {
		// Log security events
		$security_codes = array( 'invalid_nonce', 'not_logged_in', 'not_owner', 'insufficient_capability' );
		if ( in_array( $code, $security_codes, true ) ) {
			self::log_security_event( $code, $message );
		}

		// Use Apollo\API\Response if available
		if ( class_exists( Response::class ) ) {
			$response = Response::error( $code, $message, array(), $status );
			// wp_send_json expects raw data, Response object is wrapper
			wp_send_json( $response->get_data(), $response->get_status() );
			// wp_send_json exits
		}

		// Fallback Standard AJAX response
		wp_send_json(
			array(
				'success' => false,
				'error'   => array(
					'code'      => $code,
					'message'   => $message,
					'timestamp' => current_time( 'c' ),
				),
			),
			$status
		);
	}

	/**
	 * Send success response using Apollo\API\Response pattern
	 *
	 * Tooltip: Standardized success response compatible with Apollo API patterns
	 *
	 * @param mixed  $data    Response data
	 * @param string $message Success message
	 * @param int    $status  HTTP status code
	 */
	private static function send_success( $data = null, string $message = '', int $status = 200 ): void {
		// Use Apollo\API\Response if available
		if ( class_exists( Response::class ) ) {
			$response = Response::success( $data, $message, $status );
			wp_send_json( $response->get_data(), $response->get_status() );
			// wp_send_json exits
		}

		// Fallback Standard AJAX response
		$response = array(
			'success' => true,
			'data'    => $data,
		);

		if ( $message ) {
			$response['message'] = $message;
		}

		wp_send_json( $response, $status );
	}

	/**
	 * Log security event
	 *
	 * Tooltip: Records security-relevant events for audit purposes
	 */
	private static function log_security_event( string $code, string $message ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$ip          = self::get_client_ip();
		$user_id     = get_current_user_id();
		$request_uri = sanitize_text_field( $_SERVER['REQUEST_URI'] ?? '' );

		error_log(
			sprintf(
				'[Apollo Builder Security] Code: %s | Message: %s | IP: %s | User: %d | URI: %s',
				$code,
				$message,
				$ip,
				$user_id,
				$request_uri
			)
		);
	}

	/**
	 * Get client IP with security sanitization
	 *
	 * Tooltip: Returns sanitized client IP, checking proxy headers
	 */
	private static function get_client_ip(): string {
		// Cloudflare
		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
		}
		// Proxy
		elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
			$ip  = trim( $ips[0] );
		}
		// Direct
		elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		} else {
			$ip = '0.0.0.0';
		}

		// Sanitize and validate
		$ip = sanitize_text_field( $ip );
		return filter_var( $ip, FILTER_VALIDATE_IP ) ?: '0.0.0.0';
	}

	/**
	 * AJAX: Save layout
	 *
	 * Pattern: WOW wow_page_save() - saves _wow_content post meta
	 *
	 * Tooltip: Salva o JSON do layout no meta _apollo_builder_content.
	 * Data flow: POST[layout] → sanitize → update_post_meta
	 */
	public static function save_layout(): void {
		$post_id = absint( $_POST['post_id'] ?? 0 );

		$verified = self::verify_request( 'save', $post_id );
		if ( ! $verified ) {
			return;
		}

		[$user_id, $post] = $verified;

		// Get and validate layout JSON
		$layout_json = wp_unslash( $_POST['layout'] ?? '' );

		if ( empty( $layout_json ) ) {
			self::send_error( 'empty_layout', __( 'Dados do layout são obrigatórios.', 'apollo-social' ), 400 );
			return;
		}

		// Validate JSON structure before save
		$decoded = json_decode( $layout_json, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			self::send_error( 'invalid_json', __( 'JSON do layout inválido.', 'apollo-social' ), 400 );
			return;
		}

		// Save layout (includes sanitization)
		$success = Apollo_Home_CPT::save_layout( $post_id, $layout_json );

		if ( ! $success ) {
			// Check if value is same (update_post_meta returns false if unchanged)
			$current   = get_post_meta( $post_id, APOLLO_BUILDER_META_CONTENT, true );
			$sanitized = apollo_builder_sanitize_layout( $layout_json );

			if ( $current === $sanitized ) {
				self::send_success( array( 'synced' => true ), __( 'Layout já está atualizado.', 'apollo-social' ) );
				return;
			}

			self::send_error( 'save_failed', __( 'Falha ao salvar layout.', 'apollo-social' ), 500 );
			return;
		}

		// Log successful save in debug mode
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(
				sprintf(
					'[Apollo Builder] Layout saved: post=%d user=%d widgets=%d',
					$post_id,
					$user_id,
					count( $decoded['widgets'] ?? array() )
				)
			);
		}

		self::send_success( array( 'saved' => true ), __( 'Layout salvo com sucesso!', 'apollo-social' ) );
	}

	/**
	 * AJAX: Render widget
	 *
	 * Pattern: WOW wow_render_addon() - renders addon HTML via AJAX
	 *
	 * Tooltip: Retorna HTML renderizado para preview de widget no builder.
	 */
	public static function render_widget(): void {
		$post_id = absint( $_POST['post_id'] ?? 0 );

		$verified = self::verify_request( 'render', $post_id );
		if ( ! $verified ) {
			return;
		}

		// Sanitize widget data
		$widget_data = array();
		$raw_widget  = $_POST['widget'] ?? array();

		if ( ! is_array( $raw_widget ) ) {
			self::send_error( 'invalid_widget_data', __( 'Dados do widget inválidos.', 'apollo-social' ), 400 );
			return;
		}

		// Sanitize each field
		$widget_data['type']   = sanitize_key( $raw_widget['type'] ?? '' );
		$widget_data['id']     = sanitize_key( $raw_widget['id'] ?? '' );
		$widget_data['x']      = absint( $raw_widget['x'] ?? 0 );
		$widget_data['y']      = absint( $raw_widget['y'] ?? 0 );
		$widget_data['width']  = absint( $raw_widget['width'] ?? 200 );
		$widget_data['height'] = absint( $raw_widget['height'] ?? 150 );
		$widget_data['zIndex'] = absint( $raw_widget['zIndex'] ?? 1 );
		$widget_data['config'] = is_array( $raw_widget['config'] ?? null ) ? $raw_widget['config'] : array();

		if ( empty( $widget_data['type'] ) ) {
			self::send_error( 'missing_type', __( 'Tipo de widget é obrigatório.', 'apollo-social' ), 400 );
			return;
		}

		// Render widget
		$html = apollo_builder_render_widget( $widget_data, $post_id );

		self::send_success(
			array(
				'html'   => $html,
				'assets' => array(), 
			// Future: CSS/JS assets
			)
		);
	}

	/**
	 * AJAX: Get widget settings form
	 *
	 * Pattern: WOW render_widget_form_data
	 *
	 * Tooltip: Retorna dados do formulário de configuração do widget.
	 */
	public static function widget_form(): void {
		$verified = self::verify_request( 'widget_form' );
		if ( ! $verified ) {
			return;
		}

		$widget_type = sanitize_key( $_POST['widget_type'] ?? '' );

		if ( empty( $widget_type ) ) {
			self::send_error( 'missing_type', __( 'Tipo de widget é obrigatório.', 'apollo-social' ), 400 );
			return;
		}

		$instance = apollo_builder_get_widget( $widget_type );

		if ( ! $instance ) {
			self::send_error( 'widget_not_found', __( 'Widget não encontrado.', 'apollo-social' ), 404 );
			return;
		}

		self::send_success(
			array(
				'name'        => $instance->get_name(),
				'title'       => $instance->get_title(),
				'icon'        => $instance->get_icon(),
				'description' => $instance->get_description(),
				'tooltip'     => $instance->get_tooltip(),
				'settings'    => $instance->get_settings(),
				'defaults'    => $instance->get_defaults(),
				'canDelete'   => $instance->can_delete(),
			)
		);
	}

	/**
	 * AJAX: Update background
	 *
	 * Tooltip: Atualiza a textura de fundo (_apollo_background_texture meta).
	 * Apenas texturas do catálogo admin são permitidas.
	 */
	public static function update_background(): void {
		$post_id = absint( $_POST['post_id'] ?? 0 );

		$verified = self::verify_request( 'update_bg', $post_id );
		if ( ! $verified ) {
			return;
		}

		$texture_id = sanitize_key( $_POST['texture_id'] ?? '' );

		// Validate texture exists in library (if not empty)
		if ( $texture_id !== '' ) {
			$textures = get_option( 'apollo_builder_textures', array() );
			$valid    = false;

			foreach ( $textures as $t ) {
				if ( isset( $t['id'] ) && $t['id'] === $texture_id ) {
					$valid = true;
					break;
				}
			}

			if ( ! $valid ) {
				self::send_error( 'invalid_texture', __( 'Textura não encontrada no catálogo.', 'apollo-social' ), 400 );
				return;
			}
		}

		update_post_meta( $post_id, APOLLO_BUILDER_META_BACKGROUND, $texture_id );

		self::send_success( array( 'background' => $texture_id ), __( 'Fundo atualizado!', 'apollo-social' ) );
	}

	/**
	 * AJAX: Update Trax URL
	 *
	 * Tooltip: Atualiza URL do player de música (_apollo_trax_url meta).
	 * Apenas SoundCloud e Spotify são permitidos.
	 */
	public static function update_trax(): void {
		$post_id = absint( $_POST['post_id'] ?? 0 );

		$verified = self::verify_request( 'update_trax', $post_id );
		if ( ! $verified ) {
			return;
		}

		$url = esc_url_raw( $_POST['trax_url'] ?? '' );

		// Validate domain (only SoundCloud and Spotify)
		if ( $url !== '' ) {
			$allowed_domains = array( 'soundcloud.com', 'spotify.com', 'open.spotify.com' );
			$parsed          = wp_parse_url( $url );
			$host            = isset( $parsed['host'] ) ? str_replace( 'www.', '', strtolower( $parsed['host'] ) ) : '';

			$is_valid = false;
			foreach ( $allowed_domains as $domain ) {
				if ( $host === $domain || str_ends_with( $host, '.' . $domain ) ) {
					$is_valid = true;
					break;
				}
			}

			if ( ! $is_valid ) {
				self::send_error(
					'invalid_domain',
					__( 'Apenas URLs do SoundCloud ou Spotify são permitidos.', 'apollo-social' ),
					400
				);
				return;
			}
		}//end if

		update_post_meta( $post_id, APOLLO_BUILDER_META_TRAX, $url );

		self::send_success( array( 'trax_url' => $url ), __( 'Música atualizada!', 'apollo-social' ) );
	}

	/**
	 * AJAX: Add depoimento (guestbook comment)
	 *
	 * Tooltip: Adiciona comentário como "depoimento" na home do usuário.
	 * Usa WP Comments nativo. Rate limiting aplicado.
	 * Data flow: POST[content] → rate_limit_check → wp_insert_comment → wp_comments table
	 */
	public static function add_depoimento(): void {
		// Nonce check (accepts both specific and generic)
		$nonce = $_REQUEST['_wpnonce'] ?? $_REQUEST['nonce'] ?? '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_PREFIX . 'depoimento' ) &&
			! wp_verify_nonce( $nonce, 'apollo-builder-nonce' ) ) {
			self::send_error( 'invalid_nonce', __( 'Verificação de segurança falhou.', 'apollo-social' ), 403 );
			return;
		}

		$post_id = absint( $_POST['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		if ( ! $post || $post->post_type !== Apollo_Home_CPT::POST_TYPE ) {
			self::send_error( 'invalid_post', __( 'Home inválida.', 'apollo-social' ), 404 );
			return;
		}

		// Sanitize content
		$content = sanitize_textarea_field( $_POST['content'] ?? '' );

		if ( empty( $content ) ) {
			self::send_error( 'empty_content', __( 'O depoimento não pode estar vazio.', 'apollo-social' ), 400 );
			return;
		}

		// Length limit
		if ( mb_strlen( $content ) > 1000 ) {
			self::send_error( 'content_too_long', __( 'Depoimento muito longo (máx. 1000 caracteres).', 'apollo-social' ), 400 );
			return;
		}

		// Rate limiting (5 per hour per user/IP)
		$rate_key = is_user_logged_in()
			? 'apollo_depo_rate_' . get_current_user_id()
			: 'apollo_depo_rate_' . md5( self::get_client_ip() );

		$recent_count = (int) get_transient( $rate_key );

		if ( $recent_count >= 5 ) {
			self::send_error(
				'rate_limited',
				__( 'Muitos depoimentos recentes. Aguarde um pouco!', 'apollo-social' ),
				429
			);
			return;
		}

		// Prepare comment data
		$comment_data = array(
			'comment_post_ID'  => $post_id,
			'comment_content'  => $content,
			'comment_type'     => 'depoimento',
			'comment_approved' => is_user_logged_in() ? 1 : 0, 
		// Auto-approve for logged in
		);

		if ( is_user_logged_in() ) {
			$user                                 = wp_get_current_user();
			$comment_data['user_id']              = $user->ID;
			$comment_data['comment_author']       = $user->display_name;
			$comment_data['comment_author_email'] = $user->user_email;
		} else {
			$author                               = sanitize_text_field( $_POST['author'] ?? '' );
			$comment_data['comment_author']       = $author ?: __( 'Anônimo', 'apollo-social' );
			$comment_data['comment_author_email'] = sanitize_email( $_POST['email'] ?? '' );
		}

		// Insert comment
		$comment_id = wp_insert_comment( $comment_data );

		if ( ! $comment_id ) {
			self::send_error( 'insert_failed', __( 'Falha ao adicionar depoimento.', 'apollo-social' ), 500 );
			return;
		}

		// Update rate limit counter
		set_transient( $rate_key, $recent_count + 1, HOUR_IN_SECONDS );

		$comment = get_comment( $comment_id );

		self::send_success(
			array(
				'comment_id' => $comment_id,
				'author'     => esc_html( $comment->comment_author ),
				'content'    => wp_kses_post( $comment->comment_content ),
				'date'       => get_comment_date( get_option( 'date_format' ), $comment ),
				'avatar'     => get_avatar_url( $comment->comment_author_email, array( 'size' => 50 ) ),
				'approved'   => $comment->comment_approved === '1',
			),
			__( 'Depoimento adicionado!', 'apollo-social' )
		);
	}

	/**
	 * AJAX: Get all widgets data
	 *
	 * Pattern: WOW wow_addons_single_data - returns widget info
	 *
	 * Tooltip: Retorna definições de todos os widgets registrados para a sidebar do builder.
	 */
	public static function get_widgets(): void {
		$verified = self::verify_request( 'get_widgets' );
		if ( ! $verified ) {
			return;
		}

		$widgets_data   = array();
		$widget_classes = apollo_builder_get_widgets();

		foreach ( $widget_classes as $class ) {
			if ( ! class_exists( $class ) ) {
				continue;
			}

			try {
				$instance = new $class();

				$widgets_data[] = array(
					'name'          => $instance->get_name(),
					'title'         => $instance->get_title(),
					'icon'          => $instance->get_icon(),
					'description'   => $instance->get_description(),
					'tooltip'       => $instance->get_tooltip(),
					'settings'      => $instance->get_settings(),
					'defaults'      => $instance->get_defaults(),
					'canDelete'     => $instance->can_delete(),
					'maxInstances'  => $instance->get_max_instances(),
					'defaultWidth'  => $instance->get_default_width(),
					'defaultHeight' => $instance->get_default_height(),
				);
			} catch ( \Throwable $e ) {
				// Skip broken widgets but log in debug mode
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[Apollo Builder] Widget class error: ' . $class . ' - ' . $e->getMessage() );
				}
			}//end try
		}//end foreach

		self::send_success( array( 'widgets' => $widgets_data ) );
	}
}
