<?php
/**
 * Modules Admin Settings
 *
 * Admin page for managing Apollo Events Manager modules.
 * Part of the CABIN APOLLO admin interface.
 *
 * @package Apollo_Events_Manager
 * @subpackage Core
 * @since 1.0.0
 */

declare( strict_types = 1 );

namespace Apollo\Events\Core;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Modules_Admin
 *
 * Handles the admin interface for module management.
 *
 * @since 1.0.0
 */
final class Modules_Admin {

	/**
	 * Singleton instance.
	 *
	 * @var Modules_Admin|null
	 */
	private static ?Modules_Admin $instance = null;

	/**
	 * Module registry.
	 *
	 * @var Module_Registry
	 */
	private Module_Registry $registry;

	/**
	 * Admin page slug.
	 *
	 * @var string
	 */
	private const PAGE_SLUG = 'apollo-em-modules';

	/**
	 * Nonce action.
	 *
	 * @var string
	 */
	private const NONCE_ACTION = 'apollo_em_modules_settings';

	/**
	 * Private constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->registry = Module_Registry::get_instance();
	}

	/**
	 * Get singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Modules_Admin The instance.
	 */
	public static function get_instance(): Modules_Admin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize admin hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 20 );
		add_action( 'admin_init', array( $this, 'handle_form_submission' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register admin menu page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_admin_menu(): void {
		add_submenu_page(
			'apollo-hub',
			__( 'Módulos', 'apollo-events-manager' ),
			__( 'Módulos', 'apollo-events-manager' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook Current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( false === strpos( $hook, self::PAGE_SLUG ) ) {
			return;
		}

		wp_enqueue_style(
			'apollo-em-modules-admin',
			APOLLO_APRIO_URL . 'assets/css/admin-modules.css',
			array(),
			APOLLO_APRIO_VERSION
		);
	}

	/**
	 * Handle form submission.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_form_submission(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['apollo_em_modules_submit'] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Nonce inválido.', 'apollo-events-manager' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permissão negada.', 'apollo-events-manager' ) );
		}

		// Get enabled modules from form.
		$enabled_modules = array();

		if ( isset( $_POST['apollo_em_modules'] ) && is_array( $_POST['apollo_em_modules'] ) ) {
			$enabled_modules = array_map( 'sanitize_key', wp_unslash( $_POST['apollo_em_modules'] ) );
		}

		// Update each module status.
		$all_modules = $this->registry->get_all();

		foreach ( $all_modules as $module_id => $module ) {
			$should_be_active = in_array( $module_id, $enabled_modules, true );
			$is_active        = $this->registry->is_active( $module_id );

			if ( $should_be_active && ! $is_active ) {
				$this->registry->activate( $module_id );
			} elseif ( ! $should_be_active && $is_active ) {
				$this->registry->deactivate( $module_id );
			}
		}

		// Redirect with success message.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::PAGE_SLUG,
					'updated' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render the admin page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_page(): void {
		$modules = $this->registry->get_modules_info();
		?>
		<div class="wrap apollo-em-modules-wrap">
			<h1>
				<span class="dashicons dashicons-admin-plugins"></span>
				<?php esc_html_e( 'Apollo Events — Módulos', 'apollo-events-manager' ); ?>
			</h1>

			<?php if ( isset( $_GET['updated'] ) && '1' === $_GET['updated'] ) : // phpcs:ignore ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Configurações salvas com sucesso!', 'apollo-events-manager' ); ?></p>
				</div>
			<?php endif; ?>

			<p class="description">
				<?php esc_html_e( 'Ative ou desative módulos para personalizar as funcionalidades do plugin.', 'apollo-events-manager' ); ?>
			</p>

			<form method="post" action="">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>

				<div class="apollo-modules-grid">
					<?php foreach ( $modules as $module_id => $info ) : ?>
						<div class="apollo-module-card <?php echo $info['is_active'] ? 'is-active' : ''; ?>">
							<div class="module-header">
								<label class="module-toggle">
									<input
										type="checkbox"
										name="apollo_em_modules[]"
										value="<?php echo esc_attr( $module_id ); ?>"
										<?php checked( $info['is_active'] ); ?>
										<?php disabled( ! $info['deps_met'] ); ?>
									/>
									<span class="toggle-slider"></span>
								</label>
								<h3 class="module-name"><?php echo esc_html( $info['name'] ); ?></h3>
								<span class="module-version">v<?php echo esc_html( $info['version'] ); ?></span>
							</div>

							<div class="module-body">
								<p class="module-description"><?php echo esc_html( $info['description'] ); ?></p>

								<?php if ( ! empty( $info['dependencies'] ) ) : ?>
									<div class="module-dependencies">
										<strong><?php esc_html_e( 'Dependências:', 'apollo-events-manager' ); ?></strong>
										<ul>
											<?php foreach ( $info['dependencies'] as $dep_id ) : ?>
												<?php
												$dep_name   = isset( $modules[ $dep_id ] ) ? $modules[ $dep_id ]['name'] : $dep_id;
												$dep_active = isset( $modules[ $dep_id ] ) && $modules[ $dep_id ]['is_active'];
												?>
												<li class="<?php echo $dep_active ? 'dep-met' : 'dep-unmet'; ?>">
													<?php echo esc_html( $dep_name ); ?>
													<?php if ( $dep_active ) : ?>
														<span class="dashicons dashicons-yes"></span>
													<?php else : ?>
														<span class="dashicons dashicons-no"></span>
													<?php endif; ?>
												</li>
											<?php endforeach; ?>
										</ul>
									</div>
								<?php endif; ?>

								<?php if ( $info['default_enabled'] ) : ?>
									<span class="module-badge badge-core">
										<?php esc_html_e( 'Core', 'apollo-events-manager' ); ?>
									</span>
								<?php endif; ?>
							</div>

							<div class="module-footer">
								<span class="module-status">
									<?php if ( $info['is_active'] ) : ?>
										<span class="status-active">
											<span class="dashicons dashicons-yes-alt"></span>
											<?php esc_html_e( 'Ativo', 'apollo-events-manager' ); ?>
										</span>
									<?php else : ?>
										<span class="status-inactive">
											<span class="dashicons dashicons-marker"></span>
											<?php esc_html_e( 'Inativo', 'apollo-events-manager' ); ?>
										</span>
									<?php endif; ?>
								</span>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<?php if ( empty( $modules ) ) : ?>
					<div class="apollo-no-modules">
						<p><?php esc_html_e( 'Nenhum módulo registrado.', 'apollo-events-manager' ); ?></p>
					</div>
				<?php endif; ?>

				<p class="submit">
					<input
						type="submit"
						name="apollo_em_modules_submit"
						class="button button-primary button-hero"
						value="<?php esc_attr_e( 'Salvar Configurações', 'apollo-events-manager' ); ?>"
					/>
				</p>
			</form>
		</div>

		<style>
			.apollo-em-modules-wrap {
				max-width: 1200px;
			}
			.apollo-em-modules-wrap h1 {
				display: flex;
				align-items: center;
				gap: 10px;
			}
			.apollo-modules-grid {
				display: grid;
				grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
				gap: 20px;
				margin: 20px 0;
			}
			.apollo-module-card {
				background: #fff;
				border: 1px solid #c3c4c7;
				border-radius: 8px;
				padding: 0;
				transition: all 0.2s ease;
			}
			.apollo-module-card.is-active {
				border-color: #2271b1;
				box-shadow: 0 0 0 1px #2271b1;
			}
			.module-header {
				display: flex;
				align-items: center;
				gap: 12px;
				padding: 16px;
				border-bottom: 1px solid #f0f0f1;
			}
			.module-name {
				margin: 0;
				font-size: 14px;
				flex: 1;
			}
			.module-version {
				color: #757575;
				font-size: 12px;
			}
			.module-body {
				padding: 16px;
			}
			.module-description {
				margin: 0 0 12px;
				color: #50575e;
			}
			.module-dependencies {
				font-size: 12px;
				background: #f6f7f7;
				padding: 10px;
				border-radius: 4px;
				margin-bottom: 12px;
			}
			.module-dependencies ul {
				margin: 5px 0 0;
				padding-left: 20px;
			}
			.module-dependencies .dep-met {
				color: #00a32a;
			}
			.module-dependencies .dep-unmet {
				color: #d63638;
			}
			.module-badge {
				display: inline-block;
				padding: 2px 8px;
				border-radius: 3px;
				font-size: 11px;
				font-weight: 600;
			}
			.badge-core {
				background: #2271b1;
				color: #fff;
			}
			.module-footer {
				padding: 12px 16px;
				background: #f6f7f7;
				border-radius: 0 0 8px 8px;
			}
			.status-active {
				color: #00a32a;
			}
			.status-inactive {
				color: #757575;
			}
			.module-toggle {
				position: relative;
				display: inline-block;
				width: 44px;
				height: 24px;
			}
			.module-toggle input {
				opacity: 0;
				width: 0;
				height: 0;
			}
			.toggle-slider {
				position: absolute;
				cursor: pointer;
				top: 0;
				left: 0;
				right: 0;
				bottom: 0;
				background-color: #ccc;
				transition: 0.3s;
				border-radius: 24px;
			}
			.toggle-slider:before {
				position: absolute;
				content: "";
				height: 18px;
				width: 18px;
				left: 3px;
				bottom: 3px;
				background-color: white;
				transition: 0.3s;
				border-radius: 50%;
			}
			.module-toggle input:checked + .toggle-slider {
				background-color: #2271b1;
			}
			.module-toggle input:checked + .toggle-slider:before {
				transform: translateX(20px);
			}
			.module-toggle input:disabled + .toggle-slider {
				opacity: 0.5;
				cursor: not-allowed;
			}
		</style>
		<?php
	}
}
