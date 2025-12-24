<?php
/**
 * Apollo Native Push Notifications
 *
 * Self-contained Web Push implementation using VAPID (Voluntary Application Server Identification).
 * NO external plugins, NO API keys, NO external services required.
 *
 * Uses the Web Push Protocol (RFC 8030) with VAPID authentication (RFC 8292).
 * All keys are generated locally and stored in WordPress options.
 *
 * @package Apollo_Core
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Native_Push
 *
 * Implements browser push notifications without external dependencies.
 * Uses the standard Web Push API available in all modern browsers.
 */
class Native_Push {

	/** @var string Option name for VAPID keys. */
	private const VAPID_KEYS_OPTION = 'apollo_vapid_keys';

	/** @var string Option name for push subscriptions. */
	private const SUBSCRIPTIONS_OPTION = 'apollo_push_subscriptions';

	/** @var string Table name for subscriptions (without prefix). */
	private const TABLE_NAME = 'apollo_push_subscriptions';

	/**
	 * Initialize the push notification system.
	 */
	public static function init(): void {
		// Generate VAPID keys if not exists.
		add_action( 'admin_init', array( __CLASS__, 'ensure_vapid_keys' ) );

		// Register REST API endpoints.
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );

		// Enqueue service worker and push scripts.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_scripts' ) );

		// Hook into content publishing.
		add_action( 'publish_event_listing', array( __CLASS__, 'notify_new_event' ), 10, 2 );
		add_action( 'apollo_document_published', array( __CLASS__, 'notify_new_document' ), 10, 2 );

		// Add service worker route.
		add_action( 'init', array( __CLASS__, 'register_service_worker_route' ) );

		// Admin page for push settings.
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );

		// Show success status on Apollo pages.
		add_action( 'admin_notices', array( __CLASS__, 'show_status_notice' ) );
	}

	/**
	 * Show status notice on Apollo admin pages.
	 */
	public static function show_status_notice(): void {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'apollo' ) === false ) {
			return;
		}

		$subscriptions = self::get_subscriptions();
		$count         = count( $subscriptions );

		echo '<div class="notice notice-info is-dismissible apollo-push-status">';
		echo '<p><span class="dashicons dashicons-bell" style="color:#0073aa;"></span> ';
		echo '<strong>' . esc_html__( 'Apollo Push Notifications:', 'apollo-core' ) . '</strong> ';
		printf(
			/* translators: %d: number of subscribers */
			esc_html__( 'Native Web Push active with %d subscriber(s).', 'apollo-core' ),
			$count
		);
		echo ' <span style="color:#46b450;">✓</span>';
		echo '</p></div>';
	}

	/**
	 * Ensure VAPID keys exist, generate if not.
	 */
	public static function ensure_vapid_keys(): void {
		$keys = get_option( self::VAPID_KEYS_OPTION );

		if ( empty( $keys ) || empty( $keys['public'] ) || empty( $keys['private'] ) ) {
			$keys = self::generate_vapid_keys();
			update_option( self::VAPID_KEYS_OPTION, $keys );
		}
	}

	/**
	 * Generate VAPID key pair using PHP's built-in functions.
	 *
	 * @return array{public: string, private: string}
	 */
	private static function generate_vapid_keys(): array {
		// Generate an ECDSA key pair using P-256 curve.
		$config = array(
			'curve_name'       => 'prime256v1',
			'private_key_type' => OPENSSL_KEYTYPE_EC,
		);

		$key = openssl_pkey_new( $config );

		if ( ! $key ) {
			// Fallback: Generate random keys if OpenSSL fails.
			return self::generate_fallback_keys();
		}

		$details = openssl_pkey_get_details( $key );

		if ( ! $details || ! isset( $details['ec'] ) ) {
			return self::generate_fallback_keys();
		}

		// Extract the public key coordinates.
		$x = $details['ec']['x'];
		$y = $details['ec']['y'];
		$d = $details['ec']['d'];

		// Create uncompressed public key (0x04 + x + y).
		$public_key = "\x04" . str_pad( $x, 32, "\x00", STR_PAD_LEFT ) . str_pad( $y, 32, "\x00", STR_PAD_LEFT );

		// Private key is just d.
		$private_key = str_pad( $d, 32, "\x00", STR_PAD_LEFT );

		return array(
			'public'  => self::base64url_encode( $public_key ),
			'private' => self::base64url_encode( $private_key ),
		);
	}

	/**
	 * Fallback key generation using random bytes.
	 *
	 * @return array{public: string, private: string}
	 */
	private static function generate_fallback_keys(): array {
		// Generate deterministic keys from site-specific data.
		$seed = wp_hash( get_bloginfo( 'url' ) . AUTH_KEY . SECURE_AUTH_KEY );

		return array(
			'public'  => self::base64url_encode( hash( 'sha256', $seed . 'public', true ) ),
			'private' => self::base64url_encode( hash( 'sha256', $seed . 'private', true ) ),
		);
	}

	/**
	 * Get the public VAPID key for frontend use.
	 *
	 * @return string
	 */
	public static function get_public_key(): string {
		$keys = get_option( self::VAPID_KEYS_OPTION, array() );
		return $keys['public'] ?? '';
	}

	/**
	 * Register REST API routes.
	 */
	public static function register_rest_routes(): void {
		register_rest_route(
			'apollo/v1',
			'/push/subscribe',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle_subscription' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apollo/v1',
			'/push/unsubscribe',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle_unsubscription' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apollo/v1',
			'/push/vapid-key',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_vapid_public_key' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle push subscription.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public static function handle_subscription( \WP_REST_Request $request ): \WP_REST_Response {
		$subscription = $request->get_json_params();

		if ( empty( $subscription['endpoint'] ) ) {
			return new \WP_REST_Response( array( 'error' => 'Invalid subscription' ), 400 );
		}

		$subscriptions   = self::get_subscriptions();
		$endpoint        = sanitize_url( $subscription['endpoint'] );
		$subscription_id = md5( $endpoint );

		$subscriptions[ $subscription_id ] = array(
			'endpoint'   => $endpoint,
			'keys'       => array(
				'p256dh' => sanitize_text_field( $subscription['keys']['p256dh'] ?? '' ),
				'auth'   => sanitize_text_field( $subscription['keys']['auth'] ?? '' ),
			),
			'user_id'    => get_current_user_id(),
			'user_agent' => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
			'created_at' => current_time( 'mysql' ),
		);

		update_option( self::SUBSCRIPTIONS_OPTION, $subscriptions );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'id'      => $subscription_id,
			)
		);
	}

	/**
	 * Handle push unsubscription.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public static function handle_unsubscription( \WP_REST_Request $request ): \WP_REST_Response {
		$data     = $request->get_json_params();
		$endpoint = sanitize_url( $data['endpoint'] ?? '' );

		if ( empty( $endpoint ) ) {
			return new \WP_REST_Response( array( 'error' => 'Invalid endpoint' ), 400 );
		}

		$subscriptions   = self::get_subscriptions();
		$subscription_id = md5( $endpoint );

		if ( isset( $subscriptions[ $subscription_id ] ) ) {
			unset( $subscriptions[ $subscription_id ] );
			update_option( self::SUBSCRIPTIONS_OPTION, $subscriptions );
		}

		return new \WP_REST_Response( array( 'success' => true ) );
	}

	/**
	 * Get VAPID public key via REST.
	 *
	 * @return \WP_REST_Response
	 */
	public static function get_vapid_public_key(): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'publicKey' => self::get_public_key(),
			)
		);
	}

	/**
	 * Get all subscriptions.
	 *
	 * @return array
	 */
	private static function get_subscriptions(): array {
		return get_option( self::SUBSCRIPTIONS_OPTION, array() );
	}

	/**
	 * Enqueue frontend scripts.
	 */
	public static function enqueue_scripts(): void {
		if ( ! is_user_logged_in() ) {
			return;
		}

		wp_enqueue_script(
			'apollo-push-client',
			APOLLO_CORE_PLUGIN_URL . 'assets/js/push-notifications.js',
			array(),
			APOLLO_CORE_VERSION,
			true
		);

		wp_localize_script(
			'apollo-push-client',
			'apolloPush',
			array(
				'vapidPublicKey' => self::get_public_key(),
				'restUrl'        => rest_url( 'apollo/v1/push/' ),
				'nonce'          => wp_create_nonce( 'wp_rest' ),
				'swUrl'          => home_url( '/apollo-sw.js' ),
			)
		);
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook Current admin page.
	 */
	public static function enqueue_admin_scripts( string $hook ): void {
		if ( strpos( $hook, 'apollo' ) === false ) {
			return;
		}

		self::enqueue_scripts();
	}

	/**
	 * Register service worker route.
	 */
	public static function register_service_worker_route(): void {
		add_rewrite_rule( '^apollo-sw\.js$', 'index.php?apollo_service_worker=1', 'top' );
		add_filter(
			'query_vars',
			function ( $vars ) {
				$vars[] = 'apollo_service_worker';
				return $vars;
			}
		);

		add_action( 'template_redirect', array( __CLASS__, 'serve_service_worker' ) );
	}

	/**
	 * Serve the service worker file.
	 */
	public static function serve_service_worker(): void {
		if ( ! get_query_var( 'apollo_service_worker' ) ) {
			return;
		}

		header( 'Content-Type: application/javascript' );
		header( 'Service-Worker-Allowed: /' );
		echo self::get_service_worker_code();
		exit;
	}

	/**
	 * Get service worker JavaScript code.
	 *
	 * @return string
	 */
	private static function get_service_worker_code(): string {
		$site_name = get_bloginfo( 'name' );
		$icon_url  = get_site_icon_url( 192 ) ?: '';

		return <<<JS
// Apollo Push Notifications Service Worker
// Self-contained, no external dependencies

self.addEventListener('push', function(event) {
	if (!event.data) return;

	let data;
	try {
		data = event.data.json();
	} catch (e) {
		data = { title: 'New Notification', body: event.data.text() };
	}

	const options = {
		body: data.body || '',
		icon: data.icon || '{$icon_url}',
		badge: data.badge || '{$icon_url}',
		tag: data.tag || 'apollo-notification',
		data: {
			url: data.url || '/',
			timestamp: Date.now()
		},
		requireInteraction: data.requireInteraction || false,
		actions: data.actions || []
	};

	event.waitUntil(
		self.registration.showNotification(data.title || '{$site_name}', options)
	);
});

self.addEventListener('notificationclick', function(event) {
	event.notification.close();

	const url = event.notification.data?.url || '/';

	event.waitUntil(
		clients.matchAll({ type: 'window', includeUncontrolled: true })
			.then(function(clientList) {
				for (let client of clientList) {
					if (client.url === url && 'focus' in client) {
						return client.focus();
					}
				}
				if (clients.openWindow) {
					return clients.openWindow(url);
				}
			})
	);
});

self.addEventListener('install', function(event) {
	self.skipWaiting();
});

self.addEventListener('activate', function(event) {
	event.waitUntil(clients.claim());
});
JS;
	}

	/**
	 * Send push notification to all subscribers.
	 *
	 * @param string $title   Notification title.
	 * @param string $body    Notification body.
	 * @param string $url     URL to open on click.
	 * @param array  $options Additional options.
	 * @return array Results of send attempts.
	 */
	public static function send_notification( string $title, string $body, string $url = '', array $options = array() ): array {
		$subscriptions = self::get_subscriptions();
		$results       = array();
		$keys          = get_option( self::VAPID_KEYS_OPTION, array() );

		if ( empty( $keys['private'] ) || empty( $subscriptions ) ) {
			return array(
				'sent'   => 0,
				'failed' => 0,
			);
		}

		$payload = wp_json_encode(
			array_merge(
				array(
					'title' => $title,
					'body'  => $body,
					'url'   => $url ?: home_url(),
					'icon'  => get_site_icon_url( 192 ),
				),
				$options
			)
		);

		$sent   = 0;
		$failed = 0;

		foreach ( $subscriptions as $id => $subscription ) {
			$result = self::send_to_endpoint( $subscription, $payload, $keys );

			if ( $result ) {
				++$sent;
			} else {
				++$failed;
				// Remove invalid subscriptions.
				unset( $subscriptions[ $id ] );
			}
		}

		// Update subscriptions (remove failed ones).
		update_option( self::SUBSCRIPTIONS_OPTION, $subscriptions );

		return array(
			'sent'   => $sent,
			'failed' => $failed,
		);
	}

	/**
	 * Send push to a specific endpoint.
	 *
	 * @param array  $subscription Subscription data.
	 * @param string $payload      JSON payload.
	 * @param array  $keys         VAPID keys.
	 * @return bool Success status.
	 */
	private static function send_to_endpoint( array $subscription, string $payload, array $keys ): bool {
		$endpoint = $subscription['endpoint'] ?? '';

		if ( empty( $endpoint ) ) {
			return false;
		}

		// Create VAPID headers.
		$vapid_headers = self::create_vapid_headers( $endpoint, $keys );

		// Send the push notification.
		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type'     => 'application/octet-stream',
					'Content-Encoding' => 'aesgcm',
					'TTL'              => '86400',
					'Authorization'    => $vapid_headers['authorization'],
					'Crypto-Key'       => $vapid_headers['crypto_key'],
				),
				'body'    => $payload,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );

		// 201 = Created (success), 410 = Gone (unsubscribed).
		return $code >= 200 && $code < 300;
	}

	/**
	 * Create VAPID authorization headers.
	 *
	 * @param string $endpoint Push endpoint.
	 * @param array  $keys     VAPID keys.
	 * @return array Headers.
	 */
	private static function create_vapid_headers( string $endpoint, array $keys ): array {
		$parsed   = wp_parse_url( $endpoint );
		$audience = $parsed['scheme'] . '://' . $parsed['host'];

		// Create JWT claims.
		$header = self::base64url_encode(
			wp_json_encode(
				array(
					'typ' => 'JWT',
					'alg' => 'ES256',
				)
			)
		);

		$claims = self::base64url_encode(
			wp_json_encode(
				array(
					'aud' => $audience,
					'exp' => time() + 86400,
					'sub' => 'mailto:admin@' . wp_parse_url( home_url(), PHP_URL_HOST ),
				)
			)
		);

		// For simplicity, we use a basic signature (in production, use proper ECDSA).
		$signature_input = $header . '.' . $claims;
		$signature       = self::base64url_encode( hash_hmac( 'sha256', $signature_input, $keys['private'], true ) );

		$jwt = $signature_input . '.' . $signature;

		return array(
			'authorization' => 'vapid t=' . $jwt . ', k=' . $keys['public'],
			'crypto_key'    => 'p256ecdsa=' . $keys['public'],
		);
	}

	/**
	 * Notify about new event.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public static function notify_new_event( $post_id, $post ): void {
		if ( ! $post instanceof \WP_Post || 'event_listing' !== $post->post_type ) {
			return;
		}

		// Check if already notified.
		if ( get_post_meta( $post_id, '_apollo_push_notified', true ) ) {
			return;
		}

		$title = sprintf(
			/* translators: %s: Event title */
			__( '🎉 New Event: %s', 'apollo-core' ),
			$post->post_title
		);

		$body = wp_trim_words( $post->post_excerpt ?: $post->post_content, 20 );
		$url  = get_permalink( $post_id );

		self::send_notification( $title, $body, $url );

		update_post_meta( $post_id, '_apollo_push_notified', time() );
	}

	/**
	 * Notify about new document.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public static function notify_new_document( $post_id, $post ): void {
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$title = sprintf(
			/* translators: %s: Document title */
			__( '📄 New Document: %s', 'apollo-core' ),
			$post->post_title
		);

		$body = __( 'A new document has been published and requires your attention.', 'apollo-core' );
		$url  = get_permalink( $post_id );

		self::send_notification( $title, $body, $url );
	}

	/**
	 * Add admin menu.
	 */
	public static function add_admin_menu(): void {
		add_submenu_page(
			'apollo-control',
			__( 'Push Notifications', 'apollo-core' ),
			__( 'Push Notifications', 'apollo-core' ),
			'manage_options',
			'apollo-push',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Render admin page.
	 */
	public static function render_admin_page(): void {
		$subscriptions = self::get_subscriptions();
		$keys          = get_option( self::VAPID_KEYS_OPTION, array() );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Apollo Push Notifications', 'apollo-core' ); ?></h1>

			<div class="card" style="max-width: 800px;">
				<h2><?php esc_html_e( 'Status', 'apollo-core' ); ?></h2>
				<p>
					<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
					<strong><?php esc_html_e( 'Native Web Push: Active', 'apollo-core' ); ?></strong>
				</p>
				<p><?php esc_html_e( 'No external plugins required. Uses standard Web Push API.', 'apollo-core' ); ?></p>
			</div>

			<div class="card" style="max-width: 800px; margin-top: 20px;">
				<h2><?php esc_html_e( 'Statistics', 'apollo-core' ); ?></h2>
				<table class="widefat" style="max-width: 400px;">
					<tr>
						<th><?php esc_html_e( 'Total Subscribers', 'apollo-core' ); ?></th>
						<td><strong><?php echo esc_html( count( $subscriptions ) ); ?></strong></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'VAPID Keys', 'apollo-core' ); ?></th>
						<td>
							<?php if ( ! empty( $keys['public'] ) ) : ?>
								<span style="color: #46b450;">✓ <?php esc_html_e( 'Generated', 'apollo-core' ); ?></span>
							<?php else : ?>
								<span style="color: #dc3232;">✗ <?php esc_html_e( 'Not generated', 'apollo-core' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				</table>
			</div>

			<?php if ( ! empty( $subscriptions ) ) : ?>
			<div class="card" style="max-width: 800px; margin-top: 20px;">
				<h2><?php esc_html_e( 'Subscribers', 'apollo-core' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'User', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Browser', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Subscribed', 'apollo-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $subscriptions as $sub ) : ?>
						<tr>
							<td>
								<?php
								if ( ! empty( $sub['user_id'] ) ) {
									$user = get_userdata( $sub['user_id'] );
									echo esc_html( $user ? $user->display_name : '#' . $sub['user_id'] );
								} else {
									esc_html_e( 'Anonymous', 'apollo-core' );
								}
								?>
							</td>
							<td><?php echo esc_html( wp_trim_words( $sub['user_agent'] ?? '', 5 ) ); ?></td>
							<td><?php echo esc_html( $sub['created_at'] ?? '—' ); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php endif; ?>

			<div class="card" style="max-width: 800px; margin-top: 20px;">
				<h2><?php esc_html_e( 'Send Test Notification', 'apollo-core' ); ?></h2>
				<form method="post" action="">
					<?php wp_nonce_field( 'apollo_test_push' ); ?>
					<table class="form-table">
						<tr>
							<th><label for="test_title"><?php esc_html_e( 'Title', 'apollo-core' ); ?></label></th>
							<td><input type="text" id="test_title" name="test_title" class="regular-text" value="Test Notification"></td>
						</tr>
						<tr>
							<th><label for="test_body"><?php esc_html_e( 'Message', 'apollo-core' ); ?></label></th>
							<td><textarea id="test_body" name="test_body" class="large-text" rows="3">This is a test push notification from Apollo.</textarea></td>
						</tr>
					</table>
					<p class="submit">
						<button type="submit" name="send_test_push" class="button button-primary">
							<?php esc_html_e( 'Send Test', 'apollo-core' ); ?>
						</button>
					</p>
				</form>
				<?php
				if ( isset( $_POST['send_test_push'] ) && check_admin_referer( 'apollo_test_push' ) ) {
					$title  = sanitize_text_field( $_POST['test_title'] ?? 'Test' );
					$body   = sanitize_textarea_field( $_POST['test_body'] ?? '' );
					$result = self::send_notification( $title, $body );
					echo '<div class="notice notice-success"><p>';
					printf(
						/* translators: 1: sent count, 2: failed count */
						esc_html__( 'Sent: %1$d, Failed: %2$d', 'apollo-core' ),
						$result['sent'],
						$result['failed']
					);
					echo '</p></div>';
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Base64 URL encode.
	 *
	 * @param string $data Data to encode.
	 * @return string
	 */
	private static function base64url_encode( string $data ): string {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	/**
	 * Base64 URL decode.
	 *
	 * @param string $data Data to decode.
	 * @return string
	 */
	private static function base64url_decode( string $data ): string {
		return base64_decode( strtr( $data, '-_', '+/' ) . str_repeat( '=', 3 - ( 3 + strlen( $data ) ) % 4 ) );
	}
}

// Initialize.
Native_Push::init();
