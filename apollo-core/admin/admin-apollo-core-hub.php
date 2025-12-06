<?php
/**
 * Apollo Core - Admin Hub Page
 *
 * Central documentation and settings page with tabs:
 * - Introduction (How to Use)
 * - Shortcodes
 * - Placeholders
 * - Forms
 * - User Roles
 * - Meta Keys
 * - Settings
 *
 * @package Apollo_Core
 * @version 1.0.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Register the Apollo Core Hub menu
 */
function apollo_core_register_hub_page(): void {
	add_menu_page(
		__( 'Apollo Core', 'apollo-core' ),
		__( 'Apollo Core', 'apollo-core' ),
		'manage_options',
		'apollo-core-hub',
		'apollo_core_render_hub_page',
		'dashicons-admin-generic',
		25
	);

	// Submenus
	add_submenu_page(
		'apollo-core-hub',
		__( 'Documentação', 'apollo-core' ),
		__( 'Documentação', 'apollo-core' ),
		'manage_options',
		'apollo-core-hub',
		'apollo_core_render_hub_page'
	);

	add_submenu_page(
		'apollo-core-hub',
		__( 'CENA-RIO', 'apollo-core' ),
		__( 'CENA-RIO', 'apollo-core' ),
		'manage_options',
		'apollo-core-cenario',
		'apollo_core_render_cenario_page'
	);

	add_submenu_page(
		'apollo-core-hub',
		__( 'Design Library', 'apollo-core' ),
		__( 'Design Library', 'apollo-core' ),
		'manage_options',
		'apollo-core-design',
		'apollo_core_render_design_page'
	);
}
add_action( 'admin_menu', 'apollo_core_register_hub_page', 5 );

/**
 * Enqueue admin styles
 */
function apollo_core_hub_admin_styles( string $hook ): void {
	if ( strpos( $hook, 'apollo-core' ) === false ) {
		return;
	}

	wp_enqueue_style(
		'apollo-core-hub-admin',
		APOLLO_CORE_PLUGIN_URL . 'admin/css/admin-hub.css',
		array(),
		APOLLO_CORE_VERSION
	);
}
add_action( 'admin_enqueue_scripts', 'apollo_core_hub_admin_styles' );

/**
 * Render main hub page with tabs
 */
function apollo_core_render_hub_page(): void {
	$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'intro';
	?>
	<div class="wrap apollo-hub-wrap">
		<h1>
			<span class="dashicons dashicons-admin-generic"></span>
			<?php esc_html_e( 'Apollo Core', 'apollo-core' ); ?>
		</h1>
		
		<nav class="nav-tab-wrapper apollo-hub-tabs">
			<a href="?page=apollo-core-hub&tab=intro" 
				class="nav-tab <?php echo $current_tab === 'intro' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-info"></span>
				<?php esc_html_e( 'Introdução', 'apollo-core' ); ?>
			</a>
			<a href="?page=apollo-core-hub&tab=shortcodes" 
				class="nav-tab <?php echo $current_tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-shortcode"></span>
				<?php esc_html_e( 'Shortcodes', 'apollo-core' ); ?>
			</a>
			<a href="?page=apollo-core-hub&tab=placeholders" 
				class="nav-tab <?php echo $current_tab === 'placeholders' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-editor-code"></span>
				<?php esc_html_e( 'Placeholders', 'apollo-core' ); ?>
			</a>
			<a href="?page=apollo-core-hub&tab=forms" 
				class="nav-tab <?php echo $current_tab === 'forms' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-feedback"></span>
				<?php esc_html_e( 'Forms', 'apollo-core' ); ?>
			</a>
			<a href="?page=apollo-core-hub&tab=roles" 
				class="nav-tab <?php echo $current_tab === 'roles' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-groups"></span>
				<?php esc_html_e( 'User Roles', 'apollo-core' ); ?>
			</a>
			<a href="?page=apollo-core-hub&tab=metakeys" 
				class="nav-tab <?php echo $current_tab === 'metakeys' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-database"></span>
				<?php esc_html_e( 'Meta Keys', 'apollo-core' ); ?>
			</a>
			<a href="?page=apollo-core-hub&tab=settings" 
				class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-admin-settings"></span>
				<?php esc_html_e( 'Settings', 'apollo-core' ); ?>
			</a>
		</nav>
		
		<div class="apollo-hub-content">
			<?php
			switch ( $current_tab ) {
				case 'shortcodes':
					apollo_core_render_shortcodes_content();
					break;
				case 'placeholders':
					apollo_core_render_placeholders_content();
					break;
				case 'forms':
					apollo_core_render_forms_content();
					break;
				case 'roles':
					apollo_core_render_roles_content();
					break;
				case 'metakeys':
					apollo_core_render_metakeys_content();
					break;
				case 'settings':
					apollo_core_render_settings_content();
					break;
				default:
					apollo_core_render_intro_content();
			}//end switch
			?>
		</div>
	</div>
	<?php
}

/**
 * TAB: Introduction
 */
function apollo_core_render_intro_content(): void {
	?>
	<div class="apollo-hub-section">
		<h2><?php esc_html_e( 'Bem-vindo ao Apollo Core', 'apollo-core' ); ?></h2>
		
		<div class="apollo-intro-grid">
			<div class="apollo-intro-card">
				<h3><span class="dashicons dashicons-shield"></span> Moderação</h3>
				<p>Sistema unificado de moderação para aprovar/rejeitar conteúdo.</p>
				<ul>
					<li>Página: <a href="<?php echo admin_url( 'admin.php?page=apollo-moderation' ); ?>">Moderation Queue</a></li>
					<li>REST API: <code>/wp-json/apollo-mod/v1/*</code></li>
				</ul>
			</div>
			
			<div class="apollo-intro-card">
				<h3><span class="dashicons dashicons-calendar-alt"></span> CENA-RIO</h3>
				<p>Sistema de submissão de eventos para a indústria.</p>
				<ul>
					<li>Rota: <code>/cena-rio/</code></li>
					<li>Shortcode: <code>[apollo_cena_submit_event]</code></li>
				</ul>
			</div>
			
			<div class="apollo-intro-card">
				<h3><span class="dashicons dashicons-media-document"></span> Design Library</h3>
				<p>Biblioteca de templates HTML aprovados para o sistema.</p>
				<ul>
					<li>Página: <a href="<?php echo admin_url( 'admin.php?page=apollo-core-design' ); ?>">Design Library</a></li>
					<li>Templates: <?php echo count( glob( APOLLO_CORE_PLUGIN_DIR . 'templates/design-library/*.html' ) ); ?> arquivos</li>
				</ul>
			</div>
			
			<div class="apollo-intro-card">
				<h3><span class="dashicons dashicons-edit-page"></span> Forms Builder</h3>
				<p>Construtor de formulários dinâmicos com validação.</p>
				<ul>
					<li>Página: <a href="<?php echo admin_url( 'admin.php?page=apollo-forms' ); ?>">Forms</a></li>
					<li>REST API: <code>/wp-json/apollo-core/v1/forms/*</code></li>
				</ul>
			</div>
		</div>
		
		<h3><?php esc_html_e( 'Módulos Disponíveis', 'apollo-core' ); ?></h3>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Módulo', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Status', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Descrição', 'apollo-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><strong>Moderation</strong></td>
					<td><span style="color:green;">✅ Ativo</span></td>
					<td>Sistema de moderação com roles, queue e audit log</td>
				</tr>
				<tr>
					<td><strong>CENA-RIO</strong></td>
					<td><span style="color:green;">✅ Ativo</span></td>
					<td>Calendário e submissão de eventos para indústria</td>
				</tr>
				<tr>
					<td><strong>Forms</strong></td>
					<td><span style="color:green;">✅ Ativo</span></td>
					<td>Formulários dinâmicos com validação JSON Schema</td>
				</tr>
				<tr>
					<td><strong>Memberships</strong></td>
					<td><span style="color:green;">✅ Ativo</span></td>
					<td>Sistema de memberships e tipos de usuário</td>
				</tr>
				<tr>
					<td><strong>Quiz</strong></td>
					<td><span style="color:green;">✅ Ativo</span></td>
					<td>Quiz de registro com validação</td>
				</tr>
			</tbody>
		</table>
		
		<div class="apollo-notice apollo-notice-info">
			<h4>📚 Versão: <?php echo esc_html( APOLLO_CORE_VERSION ); ?></h4>
			<p>Use as abas acima para ver todos os shortcodes, placeholders, formulários e configurações.</p>
		</div>
	</div>
	<?php
}

/**
 * TAB: Shortcodes
 */
function apollo_core_render_shortcodes_content(): void {
	$shortcodes = apollo_core_get_all_shortcodes();
	?>
	<div class="apollo-hub-section">
		<h2><?php esc_html_e( 'Shortcodes Apollo Core', 'apollo-core' ); ?></h2>
		<p><?php esc_html_e( 'Copie e cole estes shortcodes em qualquer página.', 'apollo-core' ); ?></p>
		
		<?php foreach ( $shortcodes as $category => $items ) : ?>
		<div class="apollo-shortcode-category">
			<h3><?php echo esc_html( $category ); ?></h3>
			<table class="widefat apollo-shortcode-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Shortcode', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Descrição', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Atributos', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Copiar', 'apollo-core' ); ?></th>
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
									data-copy="<?php echo esc_attr( $shortcode['code'] ); ?>">
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
 * TAB: Placeholders
 */
function apollo_core_render_placeholders_content(): void {
	$placeholders = apollo_core_get_all_placeholders();
	?>
	<div class="apollo-hub-section">
		<h2><?php esc_html_e( 'Placeholders Disponíveis', 'apollo-core' ); ?></h2>
		<p><?php esc_html_e( 'Use estes placeholders em templates e notificações.', 'apollo-core' ); ?></p>
		
		<?php foreach ( $placeholders as $category => $items ) : ?>
		<div class="apollo-placeholder-category">
			<h3><?php echo esc_html( $category ); ?></h3>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Placeholder', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Descrição', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Exemplo', 'apollo-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $items as $placeholder ) : ?>
					<tr>
						<td><code><?php echo esc_html( $placeholder['code'] ); ?></code></td>
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
 * TAB: Forms
 */
function apollo_core_render_forms_content(): void {
	$forms = apollo_core_get_all_forms();
	?>
	<div class="apollo-hub-section">
		<h2><?php esc_html_e( 'Formulários do Sistema', 'apollo-core' ); ?></h2>
		<p><?php esc_html_e( 'Formulários disponíveis no Apollo Core e Social.', 'apollo-core' ); ?></p>
		
		<?php foreach ( $forms as $form ) : ?>
		<div class="apollo-form-card">
			<h3><?php echo esc_html( $form['name'] ); ?></h3>
			<p><?php echo esc_html( $form['description'] ); ?></p>
			
			<div class="apollo-form-details">
				<div class="apollo-form-detail">
					<strong><?php esc_html_e( 'Shortcode:', 'apollo-core' ); ?></strong>
					<code><?php echo esc_html( $form['shortcode'] ); ?></code>
				</div>
				
				<div class="apollo-form-detail">
					<strong><?php esc_html_e( 'Módulo:', 'apollo-core' ); ?></strong>
					<?php echo esc_html( $form['module'] ); ?>
				</div>
				
				<div class="apollo-form-detail">
					<strong><?php esc_html_e( 'Permissão:', 'apollo-core' ); ?></strong>
					<?php echo esc_html( $form['permission'] ); ?>
				</div>
			</div>
			
			<?php if ( ! empty( $form['fields'] ) ) : ?>
			<details class="apollo-form-fields">
				<summary><?php esc_html_e( 'Campos do Formulário', 'apollo-core' ); ?></summary>
				<table class="widefat">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Campo', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Tipo', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Obrigatório', 'apollo-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $form['fields'] as $field ) : ?>
						<tr>
							<td><?php echo esc_html( $field['label'] ); ?></td>
							<td><code><?php echo esc_html( $field['type'] ); ?></code></td>
							<td><?php echo $field['required'] ? '✅' : '—'; ?></td>
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
 * TAB: User Roles
 */
function apollo_core_render_roles_content(): void {
	$roles = apollo_core_get_all_roles();
	?>
	<div class="apollo-hub-section">
		<h2><?php esc_html_e( 'Controle de Permissões', 'apollo-core' ); ?></h2>
		<p><?php esc_html_e( 'Gerencie quem pode fazer o quê no Apollo Core.', 'apollo-core' ); ?></p>
		
		<h3><?php esc_html_e( 'Roles Customizados Apollo', 'apollo-core' ); ?></h3>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Role', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Slug', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Descrição', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Capabilities', 'apollo-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $roles['custom_roles'] as $role ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $role['name'] ); ?></strong></td>
					<td><code><?php echo esc_html( $role['slug'] ); ?></code></td>
					<td><?php echo esc_html( $role['description'] ); ?></td>
					<td>
						<?php foreach ( $role['caps'] as $cap ) : ?>
							<code style="display:inline-block;margin:2px;"><?php echo esc_html( $cap ); ?></code>
						<?php endforeach; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		
		<h3><?php esc_html_e( 'Matriz de Permissões', 'apollo-core' ); ?></h3>
		<div class="apollo-roles-matrix">
			<table class="widefat apollo-roles-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Capability', 'apollo-core' ); ?></th>
						<th>Admin</th>
						<th>Apollo MOD</th>
						<th>CENA-RIO</th>
						<th>Subscriber</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $roles['capabilities'] as $cap => $info ) : ?>
					<tr>
						<td>
							<strong><?php echo esc_html( $info['label'] ); ?></strong>
							<br><small><code><?php echo esc_html( $cap ); ?></code></small>
						</td>
						<td class="<?php echo $info['admin'] ? 'cap-yes' : 'cap-no'; ?>">
							<?php echo $info['admin'] ? '✅' : '❌'; ?>
						</td>
						<td class="<?php echo $info['apollo'] ? 'cap-yes' : 'cap-no'; ?>">
							<?php echo $info['apollo'] ? '✅' : '❌'; ?>
						</td>
						<td class="<?php echo $info['cena'] ? 'cap-yes' : 'cap-no'; ?>">
							<?php echo $info['cena'] ? '✅' : '❌'; ?>
						</td>
						<td class="<?php echo $info['subscriber'] ? 'cap-yes' : 'cap-no'; ?>">
							<?php echo $info['subscriber'] ? '✅' : '❌'; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		
		<div class="apollo-notice apollo-notice-warning">
			<h4>⚠️ Editar Permissões</h4>
			<p>Apenas <strong>Administradores</strong> podem editar permissões. 
				Use o plugin <a href="https://wordpress.org/plugins/user-role-editor/" target="_blank">User Role Editor</a> 
				para customizar.</p>
		</div>
	</div>
	<?php
}

/**
 * TAB: Meta Keys
 */
function apollo_core_render_metakeys_content(): void {
	$metakeys = apollo_core_get_all_metakeys();
	?>
	<div class="apollo-hub-section">
		<h2><?php esc_html_e( 'Meta Keys do Sistema', 'apollo-core' ); ?></h2>
		<p><?php esc_html_e( 'Todas as meta keys utilizadas pelo Apollo Core.', 'apollo-core' ); ?></p>
		
		<?php foreach ( $metakeys as $category => $keys ) : ?>
		<div class="apollo-metakeys-category">
			<h3><?php echo esc_html( $category ); ?></h3>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Meta Key', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Tipo', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Descrição', 'apollo-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $keys as $key ) : ?>
					<tr>
						<td><code><?php echo esc_html( $key['key'] ); ?></code></td>
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
 * TAB: Settings
 */
function apollo_core_render_settings_content(): void {
	// Handle form submission
	if ( isset( $_POST['apollo_core_settings_nonce'] ) &&
		wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apollo_core_settings_nonce'] ) ), 'apollo_core_save_settings' ) ) {

		update_option( 'apollo_core_moderation_enabled', isset( $_POST['moderation_enabled'] ) ? 1 : 0 );
		update_option( 'apollo_core_cenario_enabled', isset( $_POST['cenario_enabled'] ) ? 1 : 0 );
		update_option( 'apollo_core_forms_enabled', isset( $_POST['forms_enabled'] ) ? 1 : 0 );
		update_option( 'apollo_core_quiz_enabled', isset( $_POST['quiz_enabled'] ) ? 1 : 0 );
		update_option( 'apollo_core_rate_limiting_enabled', isset( $_POST['rate_limiting_enabled'] ) ? 1 : 0 );
		update_option( 'apollo_core_audit_log_enabled', isset( $_POST['audit_log_enabled'] ) ? 1 : 0 );

		echo '<div class="notice notice-success"><p>' . esc_html__( 'Configurações salvas!', 'apollo-core' ) . '</p></div>';
	}

	// Get current settings
	$moderation_enabled    = get_option( 'apollo_core_moderation_enabled', 1 );
	$cenario_enabled       = get_option( 'apollo_core_cenario_enabled', 1 );
	$forms_enabled         = get_option( 'apollo_core_forms_enabled', 1 );
	$quiz_enabled          = get_option( 'apollo_core_quiz_enabled', 1 );
	$rate_limiting_enabled = get_option( 'apollo_core_rate_limiting_enabled', 1 );
	$audit_log_enabled     = get_option( 'apollo_core_audit_log_enabled', 1 );
	?>
	<div class="apollo-hub-section">
		<h2><?php esc_html_e( 'Configurações Gerais', 'apollo-core' ); ?></h2>
		
		<form method="post" action="">
			<?php wp_nonce_field( 'apollo_core_save_settings', 'apollo_core_settings_nonce' ); ?>
			
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Módulos', 'apollo-core' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="checkbox" name="moderation_enabled" value="1" 
										<?php checked( $moderation_enabled, 1 ); ?>>
								<?php esc_html_e( 'Habilitar Sistema de Moderação', 'apollo-core' ); ?>
							</label>
							<br>
							<label>
								<input type="checkbox" name="cenario_enabled" value="1" 
										<?php checked( $cenario_enabled, 1 ); ?>>
								<?php esc_html_e( 'Habilitar CENA-RIO', 'apollo-core' ); ?>
							</label>
							<br>
							<label>
								<input type="checkbox" name="forms_enabled" value="1" 
										<?php checked( $forms_enabled, 1 ); ?>>
								<?php esc_html_e( 'Habilitar Forms Builder', 'apollo-core' ); ?>
							</label>
							<br>
							<label>
								<input type="checkbox" name="quiz_enabled" value="1" 
										<?php checked( $quiz_enabled, 1 ); ?>>
								<?php esc_html_e( 'Habilitar Quiz de Registro', 'apollo-core' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
				
				<tr>
					<th scope="row"><?php esc_html_e( 'Segurança', 'apollo-core' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="checkbox" name="rate_limiting_enabled" value="1" 
										<?php checked( $rate_limiting_enabled, 1 ); ?>>
								<?php esc_html_e( 'Habilitar Rate Limiting', 'apollo-core' ); ?>
							</label>
							<br>
							<label>
								<input type="checkbox" name="audit_log_enabled" value="1" 
										<?php checked( $audit_log_enabled, 1 ); ?>>
								<?php esc_html_e( 'Habilitar Audit Log', 'apollo-core' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
			</table>
			
			<?php submit_button( __( 'Salvar Configurações', 'apollo-core' ) ); ?>
		</form>
	</div>
	<?php
}

/**
 * CENA-RIO Page
 */
function apollo_core_render_cenario_page(): void {
	?>
	<div class="wrap apollo-hub-wrap">
		<h1>
			<span class="dashicons dashicons-calendar-alt"></span>
			<?php esc_html_e( 'CENA-RIO', 'apollo-core' ); ?>
		</h1>
		
		<div class="apollo-hub-content">
			<div class="apollo-hub-section">
				<h2><?php esc_html_e( 'Sistema CENA-RIO', 'apollo-core' ); ?></h2>
				
				<div class="apollo-intro-grid">
					<div class="apollo-intro-card">
						<h3><span class="dashicons dashicons-calendar"></span> Calendário</h3>
						<p>Calendário de eventos para usuários da indústria.</p>
						<ul>
							<li>URL: <code>/cena-rio/</code></li>
							<li>Shortcode: <code>[apollo_cena_submit_event]</code></li>
						</ul>
					</div>
					
					<div class="apollo-intro-card">
						<h3><span class="dashicons dashicons-visibility"></span> Moderação Interna</h3>
						<p>Queue de moderação para CENA-RIO moderators.</p>
						<ul>
							<li>URL: <code>/cena-rio/mod</code></li>
							<li>Role: <code>cena_moderator</code></li>
						</ul>
					</div>
				</div>
				
				<h3><?php esc_html_e( 'Fluxo de Status', 'apollo-core' ); ?></h3>
				<table class="widefat">
					<tr>
						<td><strong>1. Expected</strong></td>
						<td>→</td>
						<td>Evento submetido, visível apenas internamente</td>
					</tr>
					<tr>
						<td><strong>2. Confirmed</strong></td>
						<td>→</td>
						<td>Confirmado por CENA-RIO, vai para MOD queue</td>
					</tr>
					<tr>
						<td><strong>3. Published</strong></td>
						<td>→</td>
						<td>Aprovado pelo MOD, visível publicamente</td>
					</tr>
				</table>
				
				<h3><?php esc_html_e( 'Estatísticas', 'apollo-core' ); ?></h3>
				<?php
				global $wpdb;
				$expected  = $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->posts} p 
                    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
                    WHERE pm.meta_key = '_apollo_cena_status' AND pm.meta_value = 'expected'"
				);
				$confirmed = $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->posts} p 
                    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
                    WHERE pm.meta_key = '_apollo_cena_status' AND pm.meta_value = 'confirmed'"
				);
				?>
				<table class="widefat" style="max-width: 400px;">
					<tr>
						<td>Eventos Expected</td>
						<td><strong><?php echo intval( $expected ); ?></strong></td>
					</tr>
					<tr>
						<td>Eventos Confirmed</td>
						<td><strong><?php echo intval( $confirmed ); ?></strong></td>
					</tr>
				</table>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Design Library Page
 */
function apollo_core_render_design_page(): void {
	$templates_dir = APOLLO_CORE_PLUGIN_DIR . 'templates/design-library/';
	$templates     = glob( $templates_dir . '*.html' );
	$index_file    = $templates_dir . '_index.json';
	$index         = array();

	if ( file_exists( $index_file ) ) {
		$index = json_decode( file_get_contents( $index_file ), true );
	}
	?>
	<div class="wrap apollo-hub-wrap">
		<h1>
			<span class="dashicons dashicons-media-document"></span>
			<?php esc_html_e( 'Design Library', 'apollo-core' ); ?>
		</h1>
		
		<div class="apollo-hub-content">
			<div class="apollo-hub-section">
				<h2><?php esc_html_e( 'Templates Aprovados', 'apollo-core' ); ?></h2>
				<p><?php esc_html_e( 'Biblioteca de templates HTML aprovados para referência do AI.', 'apollo-core' ); ?></p>
				
				<table class="widefat">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Template', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Descrição', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Arquivo', 'apollo-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $index['templates'] ) ) : ?>
							<?php foreach ( $index['templates'] as $key => $template ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $template['name'] ?? $key ); ?></strong></td>
								<td><?php echo esc_html( $template['description'] ?? '' ); ?></td>
								<td><code><?php echo esc_html( $template['file'] ?? '' ); ?></code></td>
							</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<?php foreach ( $templates as $template ) : ?>
							<tr>
								<td><strong><?php echo esc_html( basename( $template, '.html' ) ); ?></strong></td>
								<td>—</td>
								<td><code><?php echo esc_html( basename( $template ) ); ?></code></td>
							</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
				
				<h3><?php esc_html_e( 'Componentes', 'apollo-core' ); ?></h3>
				<?php
				$components = glob( $templates_dir . '_components/*.html' );
				if ( $components ) :
					?>
				<table class="widefat" style="max-width: 600px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Componente', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Arquivo', 'apollo-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $components as $component ) : ?>
						<tr>
							<td><strong><?php echo esc_html( basename( $component, '.html' ) ); ?></strong></td>
							<td><code>_components/<?php echo esc_html( basename( $component ) ); ?></code></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}

// ============================================
// DATA FUNCTIONS
// ============================================

/**
 * Get all shortcodes data
 */
function apollo_core_get_all_shortcodes(): array {
	return array(
		'CENA-RIO'                   => array(
			array(
				'code'        => '[apollo_cena_submit_event]',
				'description' => 'Formulário de submissão CENA-RIO',
				'attributes'  => '',
			),
			array(
				'code'        => '[apollo_cena_moderation_queue]',
				'description' => 'Queue de moderação CENA-RIO',
				'attributes'  => '',
			),
		),
		'Documentos (Apollo Social)' => array(
			array(
				'code'        => '[apollo_document_editor]',
				'description' => 'Editor de documentos WYSIWYG',
				'attributes'  => 'doc_id',
			),
			array(
				'code'        => '[apollo_documents]',
				'description' => 'Lista de documentos',
				'attributes'  => 'library',
			),
			array(
				'code'        => '[apollo_sign_document]',
				'description' => 'Página de assinatura',
				'attributes'  => 'doc_id',
			),
			array(
				'code'        => '[apollo_verify_document]',
				'description' => 'Verificar documento',
				'attributes'  => '',
			),
		),
		'Social'                     => array(
			array(
				'code'        => '[apollo_profile]',
				'description' => 'Perfil do usuário',
				'attributes'  => 'user_id',
			),
			array(
				'code'        => '[apollo_profile_card]',
				'description' => 'Card de perfil',
				'attributes'  => 'user_id',
			),
			array(
				'code'        => '[apollo_event_list]',
				'description' => 'Lista de eventos social',
				'attributes'  => '',
			),
			array(
				'code'        => '[apollo_dj_contacts]',
				'description' => 'Contatos de DJs',
				'attributes'  => '',
			),
		),
	);
}

/**
 * Get all placeholders data
 */
function apollo_core_get_all_placeholders(): array {
	return array(
		'Moderação'  => array(
			array(
				'code'        => '{mod_action}',
				'description' => 'Ação de moderação',
				'example'     => 'approved/rejected',
			),
			array(
				'code'        => '{mod_reason}',
				'description' => 'Motivo da ação',
				'example'     => 'Conteúdo aprovado',
			),
			array(
				'code'        => '{mod_actor}',
				'description' => 'Moderador que agiu',
				'example'     => 'admin',
			),
			array(
				'code'        => '{mod_date}',
				'description' => 'Data da ação',
				'example'     => '25/12/2025',
			),
		),
		'Documentos' => array(
			array(
				'code'        => '{doc_title}',
				'description' => 'Título do documento',
				'example'     => 'Contrato de Serviço',
			),
			array(
				'code'        => '{doc_protocol}',
				'description' => 'Código do protocolo',
				'example'     => 'APR-DOC-2025-A1B2C',
			),
			array(
				'code'        => '{doc_hash}',
				'description' => 'Hash SHA-256',
				'example'     => 'abc123...',
			),
			array(
				'code'        => '{doc_status}',
				'description' => 'Status do documento',
				'example'     => 'signed',
			),
			array(
				'code'        => '{signer_name}',
				'description' => 'Nome do assinante',
				'example'     => 'João Silva',
			),
			array(
				'code'        => '{signer_cpf}',
				'description' => 'CPF mascarado',
				'example'     => '***.456.789-**',
			),
		),
		'CENA-RIO'   => array(
			array(
				'code'        => '{cena_event_title}',
				'description' => 'Título do evento',
				'example'     => 'Festa Techno',
			),
			array(
				'code'        => '{cena_status}',
				'description' => 'Status interno',
				'example'     => 'expected/confirmed',
			),
			array(
				'code'        => '{cena_submitter}',
				'description' => 'Quem submeteu',
				'example'     => 'user@email.com',
			),
		),
	);
}

/**
 * Get all forms data
 */
function apollo_core_get_all_forms(): array {
	return array(
		array(
			'name'        => 'Submissão CENA-RIO',
			'description' => 'Formulário para submissão de eventos pela indústria.',
			'shortcode'   => '[apollo_cena_submit_event]',
			'module'      => 'CENA-RIO',
			'permission'  => 'cena_role ou cena_moderator',
			'fields'      => array(
				array(
					'label'    => 'Título do Evento',
					'type'     => 'text',
					'required' => true,
				),
				array(
					'label'    => 'Data',
					'type'     => 'date',
					'required' => true,
				),
				array(
					'label'    => 'Local',
					'type'     => 'select',
					'required' => true,
				),
				array(
					'label'    => 'Descrição',
					'type'     => 'textarea',
					'required' => false,
				),
			),
		),
		array(
			'name'        => 'Editor de Documento',
			'description' => 'Editor WYSIWYG para criar e editar documentos.',
			'shortcode'   => '[apollo_document_editor]',
			'module'      => 'Documents',
			'permission'  => 'Usuários logados (edit_posts)',
			'fields'      => array(),
		),
		array(
			'name'        => 'Assinatura Digital',
			'description' => 'Página para assinar documentos digitalmente.',
			'shortcode'   => '[apollo_sign_document]',
			'module'      => 'Signatures',
			'permission'  => 'Usuário convidado ou logado',
			'fields'      => array(
				array(
					'label'    => 'Nome Completo',
					'type'     => 'text',
					'required' => true,
				),
				array(
					'label'    => 'CPF',
					'type'     => 'cpf',
					'required' => true,
				),
				array(
					'label'    => 'Aceitar Termos',
					'type'     => 'checkbox',
					'required' => true,
				),
				array(
					'label'    => 'Assinatura',
					'type'     => 'canvas',
					'required' => true,
				),
			),
		),
		array(
			'name'        => 'Quiz de Registro',
			'description' => 'Quiz obrigatório no processo de registro.',
			'shortcode'   => '(interno)',
			'module'      => 'Quiz',
			'permission'  => 'Durante registro',
			'fields'      => array(),
		),
	);
}

/**
 * Get all roles data
 */
function apollo_core_get_all_roles(): array {
	return array(
		'custom_roles' => array(
			array(
				'name'        => 'Apollo Moderator',
				'slug'        => 'apollo',
				'description' => 'Moderador geral do sistema Apollo',
				'caps'        => array( 'view_moderation_queue', 'approve_content', 'reject_content', 'send_mod_notifications' ),
			),
			array(
				'name'        => 'CENA-RIO User',
				'slug'        => 'cena_role',
				'description' => 'Usuário da indústria com acesso ao CENA-RIO',
				'caps'        => array( 'submit_cena_events', 'view_cena_calendar', 'edit_own_cena_events' ),
			),
			array(
				'name'        => 'CENA-RIO Moderator',
				'slug'        => 'cena_moderator',
				'description' => 'Moderador interno do CENA-RIO',
				'caps'        => array( 'submit_cena_events', 'view_cena_calendar', 'edit_own_cena_events', 'moderate_cena_events', 'confirm_cena_events' ),
			),
		),
		'capabilities' => array(
			'view_moderation_queue'  => array(
				'label'      => 'Ver Fila de Moderação',
				'admin'      => true,
				'apollo'     => true,
				'cena'       => false,
				'subscriber' => false,
			),
			'approve_content'        => array(
				'label'      => 'Aprovar Conteúdo',
				'admin'      => true,
				'apollo'     => true,
				'cena'       => false,
				'subscriber' => false,
			),
			'reject_content'         => array(
				'label'      => 'Rejeitar Conteúdo',
				'admin'      => true,
				'apollo'     => true,
				'cena'       => false,
				'subscriber' => false,
			),
			'suspend_users'          => array(
				'label'      => 'Suspender Usuários',
				'admin'      => true,
				'apollo'     => false,
				'cena'       => false,
				'subscriber' => false,
			),
			'submit_cena_events'     => array(
				'label'      => 'Submeter Eventos CENA-RIO',
				'admin'      => true,
				'apollo'     => false,
				'cena'       => true,
				'subscriber' => false,
			),
			'view_cena_calendar'     => array(
				'label'      => 'Ver Calendário CENA-RIO',
				'admin'      => true,
				'apollo'     => true,
				'cena'       => true,
				'subscriber' => false,
			),
			'confirm_cena_events'    => array(
				'label'      => 'Confirmar Eventos CENA-RIO',
				'admin'      => true,
				'apollo'     => false,
				'cena'       => true,
				'subscriber' => false,
			),
			'manage_apollo_settings' => array(
				'label'      => 'Gerenciar Configurações Apollo',
				'admin'      => true,
				'apollo'     => false,
				'cena'       => false,
				'subscriber' => false,
			),
		),
	);
}

/**
 * Get all meta keys data
 */
function apollo_core_get_all_metakeys(): array {
	return array(
		'Moderação'   => array(
			array(
				'key'         => '_apollo_mod_status',
				'type'        => 'string',
				'description' => 'Status de moderação',
			),
			array(
				'key'         => '_apollo_mod_actor',
				'type'        => 'int',
				'description' => 'ID do moderador',
			),
			array(
				'key'         => '_apollo_mod_date',
				'type'        => 'datetime',
				'description' => 'Data da ação',
			),
			array(
				'key'         => '_apollo_mod_reason',
				'type'        => 'text',
				'description' => 'Motivo da ação',
			),
		),
		'CENA-RIO'    => array(
			array(
				'key'         => '_apollo_cena_status',
				'type'        => 'string',
				'description' => 'Status interno (expected/confirmed)',
			),
			array(
				'key'         => '_apollo_cena_submitter',
				'type'        => 'int',
				'description' => 'ID do submissor',
			),
			array(
				'key'         => '_apollo_cena_confirmed_by',
				'type'        => 'int',
				'description' => 'ID de quem confirmou',
			),
			array(
				'key'         => '_apollo_cena_confirmed_at',
				'type'        => 'datetime',
				'description' => 'Data de confirmação',
			),
		),
		'Documentos'  => array(
			array(
				'key'         => '_apollo_doc_protocol',
				'type'        => 'string',
				'description' => 'Código do protocolo',
			),
			array(
				'key'         => '_apollo_doc_hash',
				'type'        => 'string',
				'description' => 'Hash SHA-256',
			),
			array(
				'key'         => '_apollo_doc_library',
				'type'        => 'string',
				'description' => 'Biblioteca (apollo/cenario/private)',
			),
			array(
				'key'         => '_apollo_doc_status',
				'type'        => 'string',
				'description' => 'Status (draft/ready/signed)',
			),
		),
		'Assinaturas' => array(
			array(
				'key'         => '_apollo_sig_type',
				'type'        => 'string',
				'description' => 'Tipo (canvas/icp-brasil)',
			),
			array(
				'key'         => '_apollo_sig_signer_id',
				'type'        => 'int',
				'description' => 'ID do assinante',
			),
			array(
				'key'         => '_apollo_sig_cpf',
				'type'        => 'string',
				'description' => 'CPF (criptografado)',
			),
			array(
				'key'         => '_apollo_sig_timestamp',
				'type'        => 'datetime',
				'description' => 'Data/hora da assinatura',
			),
			array(
				'key'         => '_apollo_sig_ip',
				'type'        => 'string',
				'description' => 'IP do assinante',
			),
		),
		'Usuário'     => array(
			array(
				'key'         => '_apollo_membership_type',
				'type'        => 'string',
				'description' => 'Tipo de membership',
			),
			array(
				'key'         => '_apollo_quiz_completed',
				'type'        => 'bool',
				'description' => 'Quiz completado',
			),
			array(
				'key'         => '_apollo_quiz_score',
				'type'        => 'int',
				'description' => 'Pontuação do quiz',
			),
		),
	);
}




