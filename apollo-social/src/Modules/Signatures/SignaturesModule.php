<?php
/**
 * Apollo Signatures Module
 *
 * Main module bootstrap for signature functionality.
 * Handles initialization, hooks, and service registration.
 *
 * @package Apollo\Modules\Signatures
 * @since   2.0.0
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * phpcs:disable Squiz.Commenting
 * phpcs:disable Generic.Commenting.DocComment.MissingShort
 * phpcs:disable WordPress.PHP.YodaConditions.NotYoda
 * phpcs:disable WordPress.DB
 * phpcs:disable WordPress.Security
 * phpcs:disable WordPressVIPMinimum
 * phpcs:disable Universal.Operators.DisallowShortTernary.Found
 */

declare( strict_types=1 );

namespace Apollo\Modules\Signatures;

use Apollo\Modules\Signatures\Controllers\SignaturesRestController;
use Apollo\Modules\Signatures\Controllers\LocalSignatureController;
use Apollo\Modules\Signatures\Services\DocumentSignatureService;

/**
 * Class SignaturesModule
 *
 * Bootstrap class for Signatures module.
 */
class SignaturesModule {

	/**
	 * Module version.
	 *
	 * @var string
	 */
	private const VERSION = '2.0.0';

	/**
	 * Initialized flag.
	 *
	 * @var bool
	 */
	private static bool $initialized = false;

	/**
	 * Signature service instance.
	 *
	 * @var DocumentSignatureService|null
	 */
	private static ?DocumentSignatureService $service = null;

	/**
	 * Initialize the module.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}

		self::$initialized = true;

		// Register activation hook.
		if ( defined( '\APOLLO_SOCIAL_PLUGIN_FILE' ) ) {
			$plugin_file = \APOLLO_SOCIAL_PLUGIN_FILE;
		} else {
			$plugin_file = dirname( dirname( dirname( __DIR__ ) ) ) . '/apollo-social.php';
		}

		register_activation_hook( $plugin_file, [ self::class, 'activate' ] );

		// Initialize on plugins loaded.
		add_action( 'plugins_loaded', [ self::class, 'setup' ], 20 );

		// Register REST endpoints.
		add_action( 'rest_api_init', [ self::class, 'register_rest_routes' ] );

		// Register AJAX handlers.
		add_action( 'init', [ self::class, 'register_ajax_handlers' ] );

		// Admin menu.
		if ( is_admin() ) {
			add_action( 'admin_menu', [ self::class, 'register_admin_menu' ] );
		}
	}

	/**
	 * Activate module (create tables).
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Create audit tables.
		$audit = new AuditLog();
		$audit->createTables();

		// Create signatures service tables.
		$signatures_service = new Services\SignaturesService();
		$signatures_service->createSignaturesTable();

		// Set version.
		update_option( 'apollo_signatures_version', self::VERSION );
	}

	/**
	 * Setup module.
	 *
	 * @return void
	 */
	public static function setup(): void {
		// Check if tables need update.
		$current_version = get_option( 'apollo_signatures_version', '0.0.0' );

		if ( version_compare( $current_version, self::VERSION, '<' ) ) {
			self::activate();
		}

		// Initialize signature service.
		self::$service = new DocumentSignatureService();
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public static function register_rest_routes(): void {
		$controller = new SignaturesRestController();
		$controller->register_routes();
	}

	/**
	 * Register AJAX handlers.
	 *
	 * @return void
	 */
	public static function register_ajax_handlers(): void {
		// Local signature controller handles its own AJAX.
		new LocalSignatureController();

		// Additional AJAX handlers.
		add_action( 'wp_ajax_apollo_get_signature_backends', [ self::class, 'ajax_get_backends' ] );
		add_action( 'wp_ajax_apollo_sign_document', [ self::class, 'ajax_sign_document' ] );
	}

	/**
	 * AJAX: Get available backends.
	 *
	 * @return void
	 */
	public static function ajax_get_backends(): void {
		check_ajax_referer( 'apollo_signatures', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Acesso negado.', 'apollo-social' ) ] );
		}

		$service  = self::get_service();
		$backends = $service->get_backends_info();

		wp_send_json_success( [ 'backends' => $backends ] );
	}

	/**
	 * AJAX: Sign document.
	 *
	 * @return void
	 */
	public static function ajax_sign_document(): void {
		check_ajax_referer( 'apollo_sign_document', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'Você precisa estar logado.', 'apollo-social' ) ] );
		}

		$document_id = isset( $_POST['document_id'] ) ? absint( $_POST['document_id'] ) : 0;

		if ( ! $document_id ) {
			wp_send_json_error( [ 'message' => __( 'ID do documento inválido.', 'apollo-social' ) ] );
		}

		$cert_type = isset( $_POST['certificate_type'] ) ? sanitize_text_field( wp_unslash( $_POST['certificate_type'] ) ) : 'A1';
		$reason    = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : '';
		$location  = isset( $_POST['location'] ) ? sanitize_text_field( wp_unslash( $_POST['location'] ) ) : get_bloginfo( 'name' );

		$options = [
			'certificate_type' => $cert_type,
			'reason'           => $reason,
			'location'         => $location,
		];

		$service = self::get_service();
		$result  = $service->sign_document( $document_id, get_current_user_id(), $options );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				[
					'message' => $result->get_error_message(),
					'code'    => $result->get_error_code(),
				]
			);
		}

		wp_send_json_success(
			[
				'message'      => __( 'Documento assinado com sucesso.', 'apollo-social' ),
				'signature_id' => $result['signature_id'] ?? '',
				'timestamp'    => $result['timestamp'] ?? '',
				'is_stub'      => $result['is_stub'] ?? false,
			]
		);
	}

	/**
	 * Register admin menu.
	 *
	 * @return void
	 */
	public static function register_admin_menu(): void {
		add_submenu_page(
			'apollo-social',
			__( 'Assinaturas', 'apollo-social' ),
			__( 'Assinaturas', 'apollo-social' ),
			'manage_options',
			'apollo-signatures',
			[ self::class, 'render_admin_page' ]
		);
	}

	/**
	 * Render admin page.
	 *
	 * @return void
	 */
	public static function render_admin_page(): void {
		$service  = self::get_service();
		$backends = $service->get_backends_info();
		$active   = $service->get_active_backend();

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Apollo Assinaturas Digitais', 'apollo-social' ); ?></h1>

			<div class="notice notice-info">
				<p>
					<?php esc_html_e( 'Configure os backends de assinatura digital. Para produção com ICP-Brasil, configure o Demoiselle Signer.', 'apollo-social' ); ?>
				</p>
			</div>

			<!-- Backends -->
			<div class="card" style="max-width: 800px; margin-top: 20px;">
				<h2><?php esc_html_e( 'Backends Disponíveis', 'apollo-social' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Backend', 'apollo-social' ); ?></th>
							<th><?php esc_html_e( 'Status', 'apollo-social' ); ?></th>
							<th><?php esc_html_e( 'Recursos', 'apollo-social' ); ?></th>
							<th><?php esc_html_e( 'Ação', 'apollo-social' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $backends as $id => $backend ) : ?>
						<tr>
							<td>
								<strong><?php echo esc_html( $backend['name'] ); ?></strong>
								<?php if ( $backend['active'] ) : ?>
									<span class="dashicons dashicons-star-filled" style="color: #f0ad4e;" title="<?php esc_attr_e( 'Ativo', 'apollo-social' ); ?>"></span>
								<?php endif; ?>
								<br>
								<code><?php echo esc_html( $id ); ?></code>
							</td>
							<td>
								<?php if ( $backend['available'] ) : ?>
									<span style="color: #28a745;">✓ <?php esc_html_e( 'Disponível', 'apollo-social' ); ?></span>
								<?php else : ?>
									<span style="color: #dc3545;">✗ <?php esc_html_e( 'Indisponível', 'apollo-social' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<?php
								$caps = $backend['capabilities'];
								$tags = [];
								if ( ! empty( $caps['icp_brasil'] ) ) {
									$tags[] = '<span class="tag tag-success">ICP-Brasil</span>';
								}
								if ( ! empty( $caps['pades'] ) ) {
									$tags[] = '<span class="tag">PAdES</span>';
								}
								if ( ! empty( $caps['timestamp'] ) ) {
									$tags[] = '<span class="tag">TSA</span>';
								}
								if ( ! empty( $caps['certificate_a1'] ) ) {
									$tags[] = '<span class="tag tag-info">A1</span>';
								}
								if ( ! empty( $caps['certificate_a3'] ) ) {
									$tags[] = '<span class="tag tag-info">A3</span>';
								}
								echo wp_kses_post( implode( ' ', $tags ) );
								?>
							</td>
							<td>
								<?php if ( $backend['available'] && ! $backend['active'] ) : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<?php wp_nonce_field( 'apollo_set_backend', 'apollo_nonce' ); ?>
										<input type="hidden" name="action" value="apollo_set_signature_backend">
										<input type="hidden" name="backend" value="<?php echo esc_attr( $id ); ?>">
										<button type="submit" class="button button-primary">
											<?php esc_html_e( 'Ativar', 'apollo-social' ); ?>
										</button>
									</form>
								<?php elseif ( $backend['active'] ) : ?>
									<span class="button button-disabled"><?php esc_html_e( 'Ativo', 'apollo-social' ); ?></span>
								<?php else : ?>
									<span class="button button-disabled"><?php esc_html_e( 'Configurar', 'apollo-social' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<!-- Configuration -->
			<div class="card" style="max-width: 800px; margin-top: 20px;">
				<h2><?php esc_html_e( 'Configuração Demoiselle (ICP-Brasil)', 'apollo-social' ); ?></h2>
				<p><?php esc_html_e( 'Para usar assinaturas ICP-Brasil reais, configure as seguintes constantes no wp-config.php:', 'apollo-social' ); ?></p>
				<pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto;">
// Caminho para o JAR do Demoiselle Signer
define( 'APOLLO_DEMOISELLE_JAR_PATH', '/path/to/demoiselle-signer.jar' );

// Caminho para o Java (opcional, padrão: 'java')
define( 'APOLLO_DEMOISELLE_JAVA_PATH', '/usr/bin/java' );

// URL da TSA (Timestamp Authority)
define( 'APOLLO_DEMOISELLE_TSA_URL', 'http://timestamp.digicert.com' );

// Habilitar verificação de CRL
define( 'APOLLO_DEMOISELLE_CRL_CHECK', true );
				</pre>
			</div>

			<!-- REST Endpoints -->
			<div class="card" style="max-width: 800px; margin-top: 20px;">
				<h2><?php esc_html_e( 'Endpoints REST', 'apollo-social' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Endpoint', 'apollo-social' ); ?></th>
							<th><?php esc_html_e( 'Método', 'apollo-social' ); ?></th>
							<th><?php esc_html_e( 'Descrição', 'apollo-social' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>/wp-json/apollo-social/v1/doc/{id}/sign</code></td>
							<td>POST</td>
							<td><?php esc_html_e( 'Assinar documento', 'apollo-social' ); ?></td>
						</tr>
						<tr>
							<td><code>/wp-json/apollo-social/v1/doc/{id}/signatures</code></td>
							<td>GET</td>
							<td><?php esc_html_e( 'Listar assinaturas', 'apollo-social' ); ?></td>
						</tr>
						<tr>
							<td><code>/wp-json/apollo-social/v1/doc/{id}/verify</code></td>
							<td>POST</td>
							<td><?php esc_html_e( 'Verificar assinatura', 'apollo-social' ); ?></td>
						</tr>
						<tr>
							<td><code>/wp-json/apollo-social/v1/signatures/backends</code></td>
							<td>GET</td>
							<td><?php esc_html_e( 'Listar backends', 'apollo-social' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>

			<style>
				.tag { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 11px; background: #e0e0e0; margin: 2px; }
				.tag-success { background: #d4edda; color: #155724; }
				.tag-info { background: #cce5ff; color: #004085; }
			</style>
		</div>
		<?php
	}

	/**
	 * Get signature service instance.
	 *
	 * @return DocumentSignatureService Service instance.
	 */
	public static function get_service(): DocumentSignatureService {
		if ( null === self::$service ) {
			self::$service = new DocumentSignatureService();
		}
		return self::$service;
	}
}
