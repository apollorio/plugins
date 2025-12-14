<?php
/**
 * Apollo Social - Admin Hub Page
 *
 * Central documentation and settings page with tabs:
 * - Introduction (How to Use)
 * - Shortcodes
 * - Placeholders
 * - Forms
 * - User Roles
 * - CPT/Meta Keys
 * - Security
 * - Settings
 *
 * @package Apollo_Social
 * @version 1.0.0
 */

namespace Apollo\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdminHubPage
 *
 * Provides a centralized admin hub for Apollo Social documentation and settings.
 */
class AdminHubPage {

	/**
	 * Instance
	 *
	 * @var AdminHubPage|null
	 */
	private static ?AdminHubPage $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return AdminHubPage
	 */
	public static function getInstance(): AdminHubPage {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the admin hub
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'registerMenuPage' ], 5 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueStyles' ] );
	}

	/**
	 * Register admin menu page
	 */
	public function registerMenuPage(): void {
		add_menu_page(
			__( 'Apollo Social', 'apollo-social' ),
			__( 'Apollo Social', 'apollo-social' ),
			'manage_options',
			'apollo-social-hub',
			[ $this, 'renderHubPage' ],
			'dashicons-share',
			27
		);

		add_submenu_page(
			'apollo-social-hub',
			__( 'Documentação', 'apollo-social' ),
			__( 'Documentação', 'apollo-social' ),
			'manage_options',
			'apollo-social-hub',
			[ $this, 'renderHubPage' ]
		);

		add_submenu_page(
			'apollo-social-hub',
			__( 'Shortcodes', 'apollo-social' ),
			__( 'Shortcodes', 'apollo-social' ),
			'manage_options',
			'apollo-social-shortcodes',
			[ $this, 'renderShortcodesPage' ]
		);

		add_submenu_page(
			'apollo-social-hub',
			__( 'Segurança', 'apollo-social' ),
			__( 'Segurança', 'apollo-social' ),
			'manage_options',
			'apollo-social-security',
			[ $this, 'renderSecurityPage' ]
		);
	}

	/**
	 * Enqueue admin styles
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueueStyles( string $hook ): void {
		if ( strpos( $hook, 'apollo-social' ) === false ) {
			return;
		}

		// Use inline styles for now
		wp_add_inline_style( 'wp-admin', $this->getAdminStyles() );
	}

	/**
	 * Get admin styles
	 *
	 * @return string CSS styles
	 */
	private function getAdminStyles(): string {
		return '
            .apollo-hub-wrap { max-width: 1200px; }
            .apollo-hub-tabs { margin-bottom: 20px; }
            .apollo-hub-tabs .dashicons { margin-right: 4px; vertical-align: middle; }
            .apollo-hub-section { background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 20px; }
            .apollo-intro-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin: 20px 0; }
            .apollo-intro-card { background: #f9f9f9; padding: 15px; border-radius: 8px; border-left: 4px solid #0073aa; }
            .apollo-intro-card h3 { margin-top: 0; color: #23282d; }
            .apollo-intro-card .dashicons { color: #0073aa; }
            .apollo-shortcode-table code { background: #f1f1f1; padding: 2px 6px; border-radius: 3px; }
            .apollo-copy-btn { cursor: pointer; }
            .apollo-notice { padding: 15px; border-radius: 4px; margin: 15px 0; }
            .apollo-notice-info { background: #d9edf7; border-left: 4px solid #0073aa; }
            .apollo-notice-warning { background: #fcf8e3; border-left: 4px solid #f0ad4e; }
            .apollo-notice-danger { background: #f2dede; border-left: 4px solid #d9534f; }
            .apollo-notice h4 { margin: 0 0 10px; }
            .apollo-steps { padding-left: 20px; }
            .apollo-steps li { margin-bottom: 10px; }
            .apollo-form-card { background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #46b450; }
            .apollo-form-details { display: flex; gap: 20px; flex-wrap: wrap; margin: 10px 0; }
            .apollo-form-detail { flex: 1; min-width: 200px; }
            .apollo-form-fields { margin-top: 15px; }
            .apollo-form-fields summary { cursor: pointer; font-weight: bold; }
            .cap-yes { color: #46b450; }
            .cap-no { color: #dc3232; }
            .apollo-security-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
            .apollo-security-card { background: #f9f9f9; padding: 20px; border-radius: 8px; }
            .apollo-security-card h3 { margin-top: 0; }
            .apollo-threat-list { max-height: 300px; overflow-y: auto; }
            .apollo-threat-item { padding: 10px; background: #fff; margin-bottom: 5px; border-left: 3px solid #dc3232; }
        ';
	}

	/**
	 * Render the main hub page
	 */
	public function renderHubPage(): void {
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'intro';
		?>
		<div class="wrap apollo-hub-wrap">
			<h1>
				<span class="dashicons dashicons-share"></span>
				<?php esc_html_e( 'Apollo Social', 'apollo-social' ); ?>
			</h1>

			<nav class="nav-tab-wrapper apollo-hub-tabs">
				<a href="?page=apollo-social-hub&tab=intro"
					class="nav-tab <?php echo $current_tab === 'intro' ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-info"></span>
					<?php esc_html_e( 'Introdução', 'apollo-social' ); ?>
				</a>
				<a href="?page=apollo-social-hub&tab=shortcodes"
					class="nav-tab <?php echo $current_tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-shortcode"></span>
					<?php esc_html_e( 'Shortcodes', 'apollo-social' ); ?>
				</a>
				<a href="?page=apollo-social-hub&tab=placeholders"
					class="nav-tab <?php echo $current_tab === 'placeholders' ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-editor-code"></span>
					<?php esc_html_e( 'Placeholders', 'apollo-social' ); ?>
				</a>
				<a href="?page=apollo-social-hub&tab=forms"
					class="nav-tab <?php echo $current_tab === 'forms' ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-feedback"></span>
					<?php esc_html_e( 'Forms', 'apollo-social' ); ?>
				</a>
				<a href="?page=apollo-social-hub&tab=cpt"
					class="nav-tab <?php echo $current_tab === 'cpt' ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-database"></span>
					<?php esc_html_e( 'CPT/Meta Keys', 'apollo-social' ); ?>
				</a>
				<a href="?page=apollo-social-hub&tab=security"
					class="nav-tab <?php echo $current_tab === 'security' ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-shield"></span>
					<?php esc_html_e( 'Segurança', 'apollo-social' ); ?>
				</a>
				<a href="?page=apollo-social-hub&tab=settings"
					class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-admin-settings"></span>
					<?php esc_html_e( 'Settings', 'apollo-social' ); ?>
				</a>
			</nav>

			<div class="apollo-hub-content">
				<?php
				switch ( $current_tab ) {
					case 'shortcodes':
						$this->renderShortcodesContent();

						break;
					case 'placeholders':
						$this->renderPlaceholdersContent();

						break;
					case 'forms':
						$this->renderFormsContent();

						break;
					case 'cpt':
						$this->renderCptContent();

						break;
					case 'security':
						$this->renderSecurityContent();

						break;
					case 'settings':
						$this->renderSettingsContent();

						break;
					default:
						$this->renderIntroContent();
				}//end switch
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render introduction content
	 */
	private function renderIntroContent(): void {
		?>
		<div class="apollo-hub-section">
			<h2><?php esc_html_e( 'Bem-vindo ao Apollo Social', 'apollo-social' ); ?></h2>

			<div class="apollo-intro-grid">
				<div class="apollo-intro-card">
					<h3><span class="dashicons dashicons-edit"></span> Documentos</h3>
					<p>Sistema completo de documentos com editor WYSIWYG e assinatura digital.</p>
					<ul>
						<li>Shortcode: <code>[apollo_documents]</code></li>
						<li>Editor: <code>[apollo_document_editor]</code></li>
						<li>Assinatura: <code>[apollo_sign_document]</code></li>
					</ul>
				</div>

				<div class="apollo-intro-card">
					<h3><span class="dashicons dashicons-groups"></span> Grupos</h3>
					<p>Comunidades e Núcleos para organização social.</p>
					<ul>
						<li>CPT: <code>apollo_group</code></li>
						<li>Tipos: Comunidade (público), Núcleo (privado)</li>
					</ul>
				</div>

				<div class="apollo-intro-card">
					<h3><span class="dashicons dashicons-admin-users"></span> Memberships</h3>
					<p>Sistema de membros com badges e roles customizados.</p>
					<ul>
						<li>Badges: Apollo, DJ, Producer, etc.</li>
						<li>Verificação: CPF obrigatório para assinaturas</li>
					</ul>
				</div>

				<div class="apollo-intro-card">
					<h3><span class="dashicons dashicons-shield"></span> Segurança</h3>
					<p>Proteção contra uploads maliciosos e verificação de conteúdo.</p>
					<ul>
						<li>Scanner de uploads (virus/malware)</li>
						<li>Validação CPF/Passaporte</li>
					</ul>
				</div>
			</div>

			<h3><?php esc_html_e( 'Versão e Status', 'apollo-social' ); ?></h3>
			<table class="widefat" style="max-width: 500px;">
				<tr>
					<td>Versão</td>
					<td><strong><?php echo esc_html( APOLLO_SOCIAL_VERSION ?? '1.0.0' ); ?></strong></td>
				</tr>
				<tr>
					<td>PHP</td>
					<td><?php echo esc_html( PHP_VERSION ); ?></td>
				</tr>
				<tr>
					<td>WordPress</td>
					<td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Render shortcodes content
	 */
	private function renderShortcodesContent(): void {
		$shortcodes = $this->getAllShortcodes();
		?>
		<div class="apollo-hub-section">
			<h2><?php esc_html_e( 'Shortcodes Apollo Social', 'apollo-social' ); ?></h2>
			<p><?php esc_html_e( 'Todos os shortcodes disponíveis no Apollo Social.', 'apollo-social' ); ?></p>

			<?php foreach ( $shortcodes as $category => $items ) : ?>
			<div class="apollo-shortcode-category">
				<h3><?php echo esc_html( $category ); ?></h3>
				<table class="widefat apollo-shortcode-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Shortcode', 'apollo-social' ); ?></th>
							<th><?php esc_html_e( 'Descrição', 'apollo-social' ); ?></th>
							<th><?php esc_html_e( 'Atributos', 'apollo-social' ); ?></th>
							<th><?php esc_html_e( 'Copiar', 'apollo-social' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $items as $shortcode ) : ?>
						<tr>
							<td><code><?php echo esc_html( $shortcode['code'] ); ?></code></td>
							<td><?php echo esc_html( $shortcode['description'] ); ?></td>
							<td><small><?php echo esc_html( $shortcode['attributes'] ?: '—' ); ?></small></td>
							<td>
								<button class="button button-small apollo-copy-btn"
										data-copy="<?php echo esc_attr( $shortcode['code'] ); ?>"
										title="Copiar shortcode">
									<span class="dashicons dashicons-clipboard"></span>
								</button>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php endforeach; ?>
		</div>

		<script>
		document.querySelectorAll('.apollo-copy-btn').forEach(btn => {
			btn.addEventListener('click', function() {
				const text = this.dataset.copy;
				navigator.clipboard.writeText(text).then(() => {
					this.innerHTML = '<span class="dashicons dashicons-yes"></span>';
					setTimeout(() => {
						this.innerHTML = '<span class="dashicons dashicons-clipboard"></span>';
					}, 1500);
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Render placeholders content
	 */
	private function renderPlaceholdersContent(): void {
		$placeholders = $this->getAllPlaceholders();
		?>
		<div class="apollo-hub-section">
			<h2><?php esc_html_e( 'Placeholders Disponíveis', 'apollo-social' ); ?></h2>
			<p><?php esc_html_e( 'Use estes placeholders em templates com data-tooltip para documentação inline.', 'apollo-social' ); ?></p>

			<?php foreach ( $placeholders as $category => $items ) : ?>
			<div class="apollo-placeholder-category">
				<h3><?php echo esc_html( $category ); ?></h3>
				<table class="widefat">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Placeholder', 'apollo-social' ); ?></th>
							<th><?php esc_html_e( 'Descrição', 'apollo-social' ); ?></th>
							<th><?php esc_html_e( 'Exemplo', 'apollo-social' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $items as $placeholder ) : ?>
						<tr>
							<td><code data-tooltip="<?php echo esc_attr( $placeholder['description'] ); ?>"><?php echo esc_html( $placeholder['code'] ); ?></code></td>
							<td><?php echo esc_html( $placeholder['description'] ); ?></td>
							<td><small><?php echo esc_html( $placeholder['example'] ); ?></small></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render forms content
	 */
	private function renderFormsContent(): void {
		$forms = $this->getAllForms();
		?>
		<div class="apollo-hub-section">
			<h2><?php esc_html_e( 'Formulários do Sistema', 'apollo-social' ); ?></h2>

			<?php foreach ( $forms as $form ) : ?>
			<div class="apollo-form-card">
				<h3><?php echo esc_html( $form['name'] ); ?></h3>
				<p><?php echo esc_html( $form['description'] ); ?></p>

				<div class="apollo-form-details">
					<div class="apollo-form-detail">
						<strong><?php esc_html_e( 'Shortcode:', 'apollo-social' ); ?></strong>
						<code><?php echo esc_html( $form['shortcode'] ); ?></code>
					</div>
					<div class="apollo-form-detail">
						<strong><?php esc_html_e( 'Permissão:', 'apollo-social' ); ?></strong>
						<?php echo esc_html( $form['permission'] ); ?>
					</div>
				</div>

				<?php if ( ! empty( $form['fields'] ) ) : ?>
				<details class="apollo-form-fields">
					<summary><?php esc_html_e( 'Campos do Formulário', 'apollo-social' ); ?></summary>
					<table class="widefat">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Campo', 'apollo-social' ); ?></th>
								<th><?php esc_html_e( 'Tipo', 'apollo-social' ); ?></th>
								<th><?php esc_html_e( 'Obrigatório', 'apollo-social' ); ?></th>
								<th><?php esc_html_e( 'Meta Key', 'apollo-social' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $form['fields'] as $field ) : ?>
							<tr>
								<td><?php echo esc_html( $field['label'] ); ?></td>
								<td><code><?php echo esc_html( $field['type'] ); ?></code></td>
								<td><?php echo $field['required'] ? '✅' : '—'; ?></td>
								<td><code><?php echo esc_html( $field['meta_key'] ?? '—' ); ?></code></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</details>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render CPT/Meta Keys content
	 */
	private function renderCptContent(): void {
		$metakeys = $this->getAllMetakeys();
		?>
		<div class="apollo-hub-section">
			<h2><?php esc_html_e( 'CPTs e Meta Keys', 'apollo-social' ); ?></h2>
			<p><?php esc_html_e( 'Todas as meta keys utilizadas pelo Apollo Social.', 'apollo-social' ); ?></p>

			<?php foreach ( $metakeys as $cpt => $keys ) : ?>
			<div class="apollo-metakeys-category">
				<h3><?php echo esc_html( $cpt ); ?></h3>
				<table class="widefat">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Meta Key', 'apollo-social' ); ?></th>
							<th><?php esc_html_e( 'Tipo', 'apollo-social' ); ?></th>
							<th><?php esc_html_e( 'Descrição', 'apollo-social' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $keys as $key ) : ?>
						<tr>
							<td><code data-tooltip="<?php echo esc_attr( $key['description'] ); ?>"><?php echo esc_html( $key['key'] ); ?></code></td>
							<td><code><?php echo esc_html( $key['type'] ); ?></code></td>
							<td><?php echo esc_html( $key['description'] ); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render security content
	 */
	private function renderSecurityContent(): void {
		// Get security threats log
		$threats = get_option( 'apollo_security_threats', [] );
		$threats = array_reverse( $threats );
		// Most recent first
		?>
		<div class="apollo-hub-section">
			<h2><?php esc_html_e( 'Segurança de Uploads', 'apollo-social' ); ?></h2>

			<div class="apollo-security-grid">
				<div class="apollo-security-card">
					<h3><span class="dashicons dashicons-shield"></span> <?php esc_html_e( 'Proteções Ativas', 'apollo-social' ); ?></h3>
					<ul>
						<li>✅ <?php esc_html_e( 'Verificação de nonce CSRF', 'apollo-social' ); ?></li>
						<li>✅ <?php esc_html_e( 'Verificação de capability (upload_files)', 'apollo-social' ); ?></li>
						<li>✅ <?php esc_html_e( 'Validação de MIME type', 'apollo-social' ); ?></li>
						<li>✅ <?php esc_html_e( 'Validação de tamanho máximo (5MB)', 'apollo-social' ); ?></li>
						<li>✅ <?php esc_html_e( 'Scanner de strings maliciosas', 'apollo-social' ); ?></li>
						<li>✅ <?php esc_html_e( 'Verificação de magic bytes', 'apollo-social' ); ?></li>
						<li>✅ <?php esc_html_e( 'Detecção de extensões duplas', 'apollo-social' ); ?></li>
						<li>✅ <?php esc_html_e( 'Detecção de executáveis ocultos', 'apollo-social' ); ?></li>
						<li>✅ <?php esc_html_e( 'Scanner de ameaças em PDF', 'apollo-social' ); ?></li>
					</ul>
				</div>

				<div class="apollo-security-card">
					<h3><span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'Tipos Permitidos', 'apollo-social' ); ?></h3>
					<table class="widefat">
						<tr><td>JPEG</td><td><code>image/jpeg</code></td></tr>
						<tr><td>PNG</td><td><code>image/png</code></td></tr>
						<tr><td>GIF</td><td><code>image/gif</code></td></tr>
						<tr><td>WebP</td><td><code>image/webp</code></td></tr>
					</table>
				</div>
			</div>

			<h3><?php esc_html_e( 'Log de Ameaças Detectadas', 'apollo-social' ); ?></h3>
			<?php if ( empty( $threats ) ) : ?>
			<div class="apollo-notice apollo-notice-info">
				<p><?php esc_html_e( 'Nenhuma ameaça detectada.', 'apollo-social' ); ?> 🎉</p>
			</div>
			<?php else : ?>
			<div class="apollo-threat-list">
				<?php foreach ( array_slice( $threats, 0, 20 ) as $threat ) : ?>
				<div class="apollo-threat-item">
					<strong><?php echo esc_html( $threat['type'] ); ?></strong>
					<span style="float:right;color:#999;"><?php echo esc_html( $threat['timestamp'] ); ?></span>
					<br>
					<small>
						<?php esc_html_e( 'Arquivo:', 'apollo-social' ); ?> <?php echo esc_html( $threat['file'] ); ?> |
						<?php esc_html_e( 'User ID:', 'apollo-social' ); ?> <?php echo esc_html( $threat['user_id'] ); ?> |
						<?php esc_html_e( 'IP:', 'apollo-social' ); ?> <?php echo esc_html( $threat['ip'] ); ?>
					</small>
				</div>
				<?php endforeach; ?>
			</div>

			<form method="post" style="margin-top: 15px;">
				<?php wp_nonce_field( 'apollo_clear_threats', 'apollo_clear_threats_nonce' ); ?>
				<input type="hidden" name="action" value="clear_threats">
				<button type="submit" class="button">
					<?php esc_html_e( 'Limpar Log', 'apollo-social' ); ?>
				</button>
			</form>
				<?php
				// Handle clear action
				if ( isset( $_POST['action'] ) && $_POST['action'] === 'clear_threats' && isset( $_POST['apollo_clear_threats_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apollo_clear_threats_nonce'] ) ), 'apollo_clear_threats' ) ) {
					delete_option( 'apollo_security_threats' );
					echo '<script>location.reload();</script>';
				}
				?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render settings content
	 */
	private function renderSettingsContent(): void {
		// Handle form submission
		if ( isset( $_POST['apollo_social_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apollo_social_settings_nonce'] ) ), 'apollo_social_save_settings' ) ) {

			update_option( 'apollo_social_documents_enabled', isset( $_POST['documents_enabled'] ) ? 1 : 0 );
			update_option( 'apollo_social_groups_enabled', isset( $_POST['groups_enabled'] ) ? 1 : 0 );
			update_option( 'apollo_social_signatures_enabled', isset( $_POST['signatures_enabled'] ) ? 1 : 0 );
			update_option( 'apollo_social_cpf_required', isset( $_POST['cpf_required'] ) ? 1 : 0 );
			update_option( 'apollo_social_upload_scanner_enabled', isset( $_POST['upload_scanner_enabled'] ) ? 1 : 0 );
			update_option( 'apollo_social_max_upload_size', absint( $_POST['max_upload_size'] ?? 5 ) );

			echo '<div class="notice notice-success"><p>' . esc_html__( 'Configurações salvas!', 'apollo-social' ) . '</p></div>';
		}

		// Get current settings
		$documents_enabled      = get_option( 'apollo_social_documents_enabled', 1 );
		$groups_enabled         = get_option( 'apollo_social_groups_enabled', 1 );
		$signatures_enabled     = get_option( 'apollo_social_signatures_enabled', 1 );
		$cpf_required           = get_option( 'apollo_social_cpf_required', 1 );
		$upload_scanner_enabled = get_option( 'apollo_social_upload_scanner_enabled', 1 );
		$max_upload_size        = get_option( 'apollo_social_max_upload_size', 5 );
		?>
		<div class="apollo-hub-section">
			<h2><?php esc_html_e( 'Configurações Gerais', 'apollo-social' ); ?></h2>

			<form method="post" action="">
				<?php wp_nonce_field( 'apollo_social_save_settings', 'apollo_social_settings_nonce' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Módulos', 'apollo-social' ); ?></th>
						<td>
							<fieldset>
								<label>
									<input type="checkbox" name="documents_enabled" value="1"
											<?php checked( $documents_enabled, 1 ); ?>>
									<?php esc_html_e( 'Habilitar Módulo de Documentos', 'apollo-social' ); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="groups_enabled" value="1"
											<?php checked( $groups_enabled, 1 ); ?>>
									<?php esc_html_e( 'Habilitar Módulo de Grupos', 'apollo-social' ); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="signatures_enabled" value="1"
											<?php checked( $signatures_enabled, 1 ); ?>>
									<?php esc_html_e( 'Habilitar Assinaturas Digitais', 'apollo-social' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Segurança', 'apollo-social' ); ?></th>
						<td>
							<fieldset>
								<label>
									<input type="checkbox" name="cpf_required" value="1"
											<?php checked( $cpf_required, 1 ); ?>>
									<?php esc_html_e( 'Exigir CPF para Assinaturas Digitais', 'apollo-social' ); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="upload_scanner_enabled" value="1"
											<?php checked( $upload_scanner_enabled, 1 ); ?>>
									<?php esc_html_e( 'Habilitar Scanner de Uploads (anti-vírus)', 'apollo-social' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="max_upload_size">
								<?php esc_html_e( 'Tamanho Máximo Upload (MB)', 'apollo-social' ); ?>
							</label>
						</th>
						<td>
							<input type="number" id="max_upload_size" name="max_upload_size"
									value="<?php echo esc_attr( $max_upload_size ); ?>"
									min="1" max="50" class="small-text">
							<p class="description">
								<?php esc_html_e( 'Tamanho máximo para uploads de imagens (padrão: 5MB).', 'apollo-social' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Salvar Configurações', 'apollo-social' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render shortcodes page (standalone)
	 */
	public function renderShortcodesPage(): void {
		$_GET['tab'] = 'shortcodes';
		$this->renderHubPage();
	}

	/**
	 * Render security page (standalone)
	 */
	public function renderSecurityPage(): void {
		$_GET['tab'] = 'security';
		$this->renderHubPage();
	}

	// ============================================
	// DATA FUNCTIONS
	// ============================================

	/**
	 * Get all shortcodes
	 *
	 * @return array
	 */
	private function getAllShortcodes(): array {
		return [
			'Documentos'      => [
				[
					'code'        => '[apollo_documents]',
					'description' => 'Lista de documentos',
					'attributes'  => 'library="apollo|cenario|private"',
				],
				[
					'code'        => '[apollo_document_editor]',
					'description' => 'Editor WYSIWYG de documentos',
					'attributes'  => 'doc_id',
				],
				[
					'code'        => '[apollo_sign_document]',
					'description' => 'Página de assinatura digital',
					'attributes'  => 'doc_id',
				],
				[
					'code'        => '[apollo_verify_document]',
					'description' => 'Verificar autenticidade de documento',
					'attributes'  => '',
				],
			],
			'Grupos'          => [
				[
					'code'        => '[apollo_groups]',
					'description' => 'Lista de comunidades e núcleos',
					'attributes'  => 'type="community|nucleo"',
				],
				[
					'code'        => '[apollo_group_single]',
					'description' => 'Página single de grupo',
					'attributes'  => 'group_id',
				],
			],
			'Perfil e Social' => [
				[
					'code'        => '[apollo_profile]',
					'description' => 'Perfil do usuário',
					'attributes'  => 'user_id',
				],
				[
					'code'        => '[apollo_profile_card]',
					'description' => 'Card compacto de perfil',
					'attributes'  => 'user_id',
				],
				[
					'code'        => '[apollo_user_mention]',
					'description' => 'Menção de usuário inline',
					'attributes'  => 'user_id',
				],
			],
			'Feed'            => [
				[
					'code'        => '[apollo_feed]',
					'description' => 'Feed social principal',
					'attributes'  => '',
				],
				[
					'code'        => '[apollo_post_form]',
					'description' => 'Formulário de criação de post',
					'attributes'  => '',
				],
			],
			'Autenticação'    => [
				[
					'code'        => '[apollo_login]',
					'description' => 'Formulário de login',
					'attributes'  => 'redirect',
				],
				[
					'code'        => '[apollo_register]',
					'description' => 'Formulário de registro',
					'attributes'  => '',
				],
				[
					'code'        => '[apollo_aptitude_quiz]',
					'description' => 'Quiz de aptitude para registro',
					'attributes'  => '',
				],
			],
		];
	}

	/**
	 * Get all placeholders
	 *
	 * @return array
	 */
	private function getAllPlaceholders(): array {
		return [
			'Usuário'   => [
				[
					'code'        => '{{user_name}}',
					'description' => 'Nome do usuário',
					'example'     => 'João Silva',
				],
				[
					'code'        => '{{user_handle}}',
					'description' => 'Handle do usuário',
					'example'     => '@joaosilva',
				],
				[
					'code'        => '{{user_avatar_url}}',
					'description' => 'URL do avatar',
					'example'     => 'https://...',
				],
				[
					'code'        => '{{user_permalink}}',
					'description' => 'URL do perfil',
					'example'     => '/perfil/joaosilva',
				],
				[
					'code'        => '{{is_verified}}',
					'description' => 'Se usuário é verificado',
					'example'     => 'true/false',
				],
				[
					'code'        => '{{badges}}',
					'description' => 'Array de badges',
					'example'     => "['apollo', 'dj']",
				],
				[
					'code'        => '{{nucleos}}',
					'description' => 'Array de núcleos',
					'example'     => "['Núcleo Apollo']",
				],
			],
			'Documento' => [
				[
					'code'        => '{{doc_title}}',
					'description' => 'Título do documento',
					'example'     => 'Contrato de Serviço',
				],
				[
					'code'        => '{{doc_protocol}}',
					'description' => 'Código do protocolo',
					'example'     => 'APR-DOC-2025-A1B2C',
				],
				[
					'code'        => '{{doc_hash}}',
					'description' => 'Hash SHA-256',
					'example'     => 'abc123...',
				],
				[
					'code'        => '{{doc_status}}',
					'description' => 'Status do documento',
					'example'     => 'draft|ready|signed',
				],
				[
					'code'        => '{{signer_name}}',
					'description' => 'Nome do assinante',
					'example'     => 'João Silva',
				],
				[
					'code'        => '{{signer_cpf}}',
					'description' => 'CPF mascarado',
					'example'     => '***.456.789-**',
				],
			],
			'Grupo'     => [
				[
					'code'        => '{{group_name}}',
					'description' => 'Nome do grupo',
					'example'     => 'Núcleo Apollo',
				],
				[
					'code'        => '{{group_type}}',
					'description' => 'Tipo do grupo',
					'example'     => 'community|nucleo',
				],
				[
					'code'        => '{{group_members_count}}',
					'description' => 'Número de membros',
					'example'     => '42',
				],
				[
					'code'        => '{{group_avatar_url}}',
					'description' => 'Avatar do grupo',
					'example'     => 'https://...',
				],
			],
		];
	}

	/**
	 * Get all forms
	 *
	 * @return array
	 */
	private function getAllForms(): array {
		return [
			[
				'name'        => 'Registro de Usuário',
				'description' => 'Formulário de registro com CPF, gêneros musicais e quiz.',
				'shortcode'   => '[apollo_register]',
				'permission'  => 'Visitantes (não logados)',
				'fields'      => [
					[
						'label'    => 'Nome social & Sobrenome',
						'type'     => 'text',
						'required' => true,
						'meta_key' => 'display_name',
					],
					[
						'label'    => 'Tipo de Documento',
						'type'     => 'select',
						'required' => true,
						'meta_key' => 'apollo_doc_type',
					],
					[
						'label'    => 'CPF',
						'type'     => 'cpf',
						'required' => false,
						'meta_key' => 'apollo_cpf',
					],
					[
						'label'    => 'Passaporte',
						'type'     => 'text',
						'required' => false,
						'meta_key' => 'apollo_passport',
					],
					[
						'label'    => 'E-mail',
						'type'     => 'email',
						'required' => true,
						'meta_key' => 'user_email',
					],
					[
						'label'    => 'Senha',
						'type'     => 'password',
						'required' => true,
						'meta_key' => 'user_pass',
					],
					[
						'label'    => 'Gêneros Musicais (SOUNDS)',
						'type'     => 'multiselect',
						'required' => true,
						'meta_key' => 'apollo_sounds',
					],
					[
						'label'    => 'Quiz de Registro',
						'type'     => 'quiz',
						'required' => true,
						'meta_key' => 'apollo_quiz_completed',
					],
				],
			],
			[
				'name'        => 'Editor de Documento',
				'description' => 'Editor WYSIWYG com Quill para criar documentos.',
				'shortcode'   => '[apollo_document_editor]',
				'permission'  => 'Usuários logados (edit_posts)',
				'fields'      => [],
			],
			[
				'name'        => 'Assinatura Digital',
				'description' => 'Formulário de assinatura com verificação de CPF.',
				'shortcode'   => '[apollo_sign_document]',
				'permission'  => 'Usuários com CPF válido',
				'fields'      => [
					[
						'label'    => 'Nome Completo',
						'type'     => 'text',
						'required' => true,
						'meta_key' => '—',
					],
					[
						'label'    => 'CPF',
						'type'     => 'cpf',
						'required' => true,
						'meta_key' => '—',
					],
					[
						'label'    => 'Aceitar Termos',
						'type'     => 'checkbox',
						'required' => true,
						'meta_key' => '—',
					],
					[
						'label'    => 'Assinatura Canvas',
						'type'     => 'canvas',
						'required' => true,
						'meta_key' => '—',
					],
				],
			],
		];
	}

	/**
	 * Get all meta keys
	 *
	 * @return array
	 */
	private function getAllMetakeys(): array {
		return [
			'User Meta'                     => [
				[
					'key'         => 'apollo_doc_type',
					'type'        => 'string',
					'description' => 'Tipo de documento (cpf|passport)',
				],
				[
					'key'         => 'apollo_cpf',
					'type'        => 'string',
					'description' => 'CPF do usuário',
				],
				[
					'key'         => 'apollo_cpf_formatted',
					'type'        => 'string',
					'description' => 'CPF formatado',
				],
				[
					'key'         => 'apollo_passport',
					'type'        => 'string',
					'description' => 'Número do passaporte',
				],
				[
					'key'         => 'apollo_passport_country',
					'type'        => 'string',
					'description' => 'País do passaporte',
				],
				[
					'key'         => 'apollo_can_sign_documents',
					'type'        => 'bool',
					'description' => 'Se pode assinar documentos',
				],
				[
					'key'         => 'apollo_sounds',
					'type'        => 'array',
					'description' => 'Gêneros musicais preferidos',
				],
				[
					'key'         => 'apollo_quiz_completed',
					'type'        => 'bool',
					'description' => 'Quiz de registro completado',
				],
				[
					'key'         => 'apollo_quiz_score',
					'type'        => 'int',
					'description' => 'Pontuação do quiz',
				],
				[
					'key'         => 'apollo_membership_type',
					'type'        => 'string',
					'description' => 'Tipo de membership',
				],
				[
					'key'         => 'apollo_badges',
					'type'        => 'array',
					'description' => 'Badges do usuário',
				],
			],
			'Documento (apollo_document)'   => [
				[
					'key'         => '_apollo_doc_protocol',
					'type'        => 'string',
					'description' => 'Código do protocolo',
				],
				[
					'key'         => '_apollo_doc_hash',
					'type'        => 'string',
					'description' => 'Hash SHA-256 do conteúdo',
				],
				[
					'key'         => '_apollo_doc_library',
					'type'        => 'string',
					'description' => 'Biblioteca (apollo|cenario|private)',
				],
				[
					'key'         => '_apollo_doc_status',
					'type'        => 'string',
					'description' => 'Status (draft|ready|signed)',
				],
				[
					'key'         => '_apollo_doc_delta',
					'type'        => 'json',
					'description' => 'Conteúdo Delta do Quill',
				],
			],
			'Assinatura (apollo_signature)' => [
				[
					'key'         => '_apollo_sig_type',
					'type'        => 'string',
					'description' => 'Tipo (canvas|icp-brasil)',
				],
				[
					'key'         => '_apollo_sig_signer_id',
					'type'        => 'int',
					'description' => 'ID do assinante',
				],
				[
					'key'         => '_apollo_sig_cpf',
					'type'        => 'string',
					'description' => 'CPF (criptografado)',
				],
				[
					'key'         => '_apollo_sig_timestamp',
					'type'        => 'datetime',
					'description' => 'Data/hora UTC',
				],
				[
					'key'         => '_apollo_sig_ip',
					'type'        => 'string',
					'description' => 'IP do assinante',
				],
				[
					'key'         => '_apollo_sig_canvas_data',
					'type'        => 'text',
					'description' => 'Imagem da assinatura (base64)',
				],
			],
			'Grupo (apollo_group)'          => [
				[
					'key'         => '_apollo_group_type',
					'type'        => 'string',
					'description' => 'Tipo (community|nucleo)',
				],
				[
					'key'         => '_apollo_group_visibility',
					'type'        => 'string',
					'description' => 'Visibilidade (public|private)',
				],
				[
					'key'         => '_apollo_group_members',
					'type'        => 'array',
					'description' => 'IDs dos membros',
				],
				[
					'key'         => '_apollo_group_admins',
					'type'        => 'array',
					'description' => 'IDs dos admins',
				],
			],
		];
	}
}

// Initialize on admin_init
add_action(
	'admin_init',
	function () {
		if ( is_admin() ) {
			AdminHubPage::getInstance()->init();
		}
	}
);
