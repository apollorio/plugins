<?php
/**
 * Apollo Admin Cabin - Central Control Panel
 *
 * Painel administrativo completo para gerenciar:
 * - Módulos (ligar/desligar)
 * - Limites (editar valores globais)
 * - Moderadores (promover/rebaixar entre níveis 0/1/3)
 * - Segurança (IP blocklist, lockdown)
 * - Logs (registros de audit log)
 *
 * FASE 3 do plano de modularização Apollo.
 *
 * @package Apollo_Core
 * @since 4.0.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Register Apollo Admin Cabin menu
 */
function apollo_admin_cabin_register_menu(): void {
	// Main menu - Apollo Cabin (admin only).
	add_menu_page(
		__( 'Apollo Cabin', 'apollo-core' ),
		__( 'Apollo Cabin', 'apollo-core' ),
		'manage_options',
		'apollo-cabin',
		'apollo_admin_cabin_render_page',
		'dashicons-building',
		3
	);

	// Submenus.
	add_submenu_page(
		'apollo-cabin',
		__( 'Módulos', 'apollo-core' ),
		__( 'Módulos', 'apollo-core' ),
		'manage_options',
		'apollo-cabin',
		'apollo_admin_cabin_render_page'
	);

	add_submenu_page(
		'apollo-cabin',
		__( 'Limites', 'apollo-core' ),
		__( 'Limites', 'apollo-core' ),
		'manage_options',
		'apollo-cabin-limits',
		'apollo_admin_cabin_render_limits'
	);

	add_submenu_page(
		'apollo-cabin',
		__( 'Moderadores', 'apollo-core' ),
		__( 'Moderadores', 'apollo-core' ),
		'manage_options',
		'apollo-cabin-moderators',
		'apollo_admin_cabin_render_moderators'
	);

	add_submenu_page(
		'apollo-cabin',
		__( 'Segurança', 'apollo-core' ),
		__( 'Segurança', 'apollo-core' ),
		'manage_options',
		'apollo-cabin-security',
		'apollo_admin_cabin_render_security'
	);

	add_submenu_page(
		'apollo-cabin',
		__( 'Logs', 'apollo-core' ),
		__( 'Logs', 'apollo-core' ),
		'manage_options',
		'apollo-cabin-logs',
		'apollo_admin_cabin_render_logs'
	);
}
add_action( 'admin_menu', 'apollo_admin_cabin_register_menu', 3 );

/**
 * Enqueue admin cabin assets
 */
function apollo_admin_cabin_enqueue_assets( string $hook ): void {
	if ( strpos( $hook, 'apollo-cabin' ) === false ) {
		return;
	}

	wp_enqueue_style(
		'apollo-admin-cabin',
		APOLLO_CORE_PLUGIN_URL . 'admin/css/admin-cabin.css',
		array(),
		APOLLO_CORE_VERSION
	);

	wp_enqueue_script(
		'apollo-admin-cabin',
		APOLLO_CORE_PLUGIN_URL . 'admin/js/admin-cabin.js',
		array( 'jquery' ),
		APOLLO_CORE_VERSION,
		true
	);

	wp_localize_script(
		'apollo-admin-cabin',
		'apolloCabin',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'restUrl' => rest_url( 'apollo/v1' ),
			'nonce'   => wp_create_nonce( 'apollo_cabin_nonce' ),
			'i18n'    => array(
				'confirmToggle'  => __( 'Tem certeza que deseja alterar este módulo?', 'apollo-core' ),
				'confirmSuspend' => __( 'Tem certeza que deseja suspender este usuário?', 'apollo-core' ),
				'confirmBan'     => __( 'ATENÇÃO: Banir é permanente. Tem certeza?', 'apollo-core' ),
				'confirmBlockIP' => __( 'Tem certeza que deseja bloquear este IP?', 'apollo-core' ),
				'saved'          => __( 'Salvo com sucesso!', 'apollo-core' ),
				'error'          => __( 'Erro ao salvar.', 'apollo-core' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'apollo_admin_cabin_enqueue_assets' );

/**
 * Handle form submissions
 */
function apollo_admin_cabin_handle_submissions(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Handle modules update.
	if ( isset( $_POST['apollo_cabin_modules_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apollo_cabin_modules_nonce'] ) ), 'apollo_cabin_modules' ) ) {
		$modules   = array();
		$available = Apollo_Modules_Config::get_available_modules();

		foreach ( $available as $module ) {
			$modules[ $module ] = isset( $_POST['modules'][ $module ] );
		}

		Apollo_Modules_Config::update_modules( $modules, get_current_user_id() );
		add_settings_error( 'apollo_cabin', 'modules_updated', __( 'Módulos atualizados com sucesso.', 'apollo-core' ), 'success' );
	}

	// Handle limits update.
	if ( isset( $_POST['apollo_cabin_limits_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apollo_cabin_limits_nonce'] ) ), 'apollo_cabin_limits' ) ) {
		$limits = array();

		if ( isset( $_POST['limits'] ) && is_array( $_POST['limits'] ) ) {
			foreach ( $_POST['limits'] as $key => $value ) {
				$limits[ sanitize_key( $key ) ] = absint( $value );
			}
		}

		Apollo_Modules_Config::update_limits( $limits, get_current_user_id() );
		add_settings_error( 'apollo_cabin', 'limits_updated', __( 'Limites atualizados com sucesso.', 'apollo-core' ), 'success' );
	}

	// Handle mod level change.
	if ( isset( $_POST['apollo_cabin_mod_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apollo_cabin_mod_nonce'] ) ), 'apollo_cabin_mod' ) ) {
		$user_id = isset( $_POST['mod_user_id'] ) ? absint( $_POST['mod_user_id'] ) : 0;
		$level   = isset( $_POST['mod_level'] ) ? intval( $_POST['mod_level'] ) : -1;

		if ( $user_id > 0 && class_exists( 'Apollo_User_Moderation' ) ) {
			Apollo_User_Moderation::set_mod_level( $user_id, $level, get_current_user_id() );
			add_settings_error( 'apollo_cabin', 'mod_updated', __( 'Nível de moderador atualizado.', 'apollo-core' ), 'success' );
		}
	}

	// Handle IP block.
	if ( isset( $_POST['apollo_cabin_ip_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apollo_cabin_ip_nonce'] ) ), 'apollo_cabin_ip' ) ) {
		$ip     = isset( $_POST['block_ip'] ) ? sanitize_text_field( wp_unslash( $_POST['block_ip'] ) ) : '';
		$reason = isset( $_POST['block_reason'] ) ? sanitize_text_field( wp_unslash( $_POST['block_reason'] ) ) : '';

		if ( $ip && class_exists( 'Apollo_User_Moderation' ) ) {
			$result = Apollo_User_Moderation::block_ip( $ip, get_current_user_id(), $reason );
			if ( $result ) {
				add_settings_error( 'apollo_cabin', 'ip_blocked', __( 'IP bloqueado com sucesso.', 'apollo-core' ), 'success' );
			} else {
				add_settings_error( 'apollo_cabin', 'ip_error', __( 'Erro ao bloquear IP.', 'apollo-core' ), 'error' );
			}
		}
	}
}
add_action( 'admin_init', 'apollo_admin_cabin_handle_submissions' );

/**
 * Render cabin header
 */
function apollo_admin_cabin_header( string $title, string $subtitle = '' ): void {
	?>
	<div class="wrap apollo-cabin-wrap">
		<div class="apollo-cabin-header">
			<h1>
				<span class="dashicons dashicons-building"></span>
				<?php echo esc_html( $title ); ?>
			</h1>
			<?php if ( $subtitle ) : ?>
				<p class="description"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
		</div>
		<?php settings_errors( 'apollo_cabin' ); ?>
	<?php
}

/**
 * Render cabin footer
 */
function apollo_admin_cabin_footer(): void {
	?>
	</div><!-- .apollo-cabin-wrap -->
	<?php
}

// =========================================================================
// MODULES PAGE
// =========================================================================

/**
 * Render main modules page
 */
function apollo_admin_cabin_render_page(): void {
	apollo_admin_cabin_header(
		__( 'Módulos Apollo', 'apollo-core' ),
		__( 'Ative ou desative funcionalidades do ecossistema Apollo.', 'apollo-core' )
	);

	$modules = Apollo_Modules_Config::get_modules();

	$module_info = array(
		'social'          => array(
			'name' => __( 'Social', 'apollo-core' ),
			'desc' => __( 'Feed social, posts, likes, comentários.', 'apollo-core' ),
			'icon' => 'dashicons-share',
		),
		'events'          => array(
			'name' => __( 'Eventos', 'apollo-core' ),
			'desc' => __( 'Apollo Events Manager - calendário e eventos.', 'apollo-core' ),
			'icon' => 'dashicons-calendar-alt',
		),
		'bolha'           => array(
			'name' => __( 'Bolha', 'apollo-core' ),
			'desc' => __( 'Sistema de bolha (até 15 pessoas).', 'apollo-core' ),
			'icon' => 'dashicons-groups',
		),
		'chat'            => array(
			'name' => __( 'Chat', 'apollo-core' ),
			'desc' => __( 'Mensagens diretas entre usuários.', 'apollo-core' ),
			'icon' => 'dashicons-format-chat',
		),
		'docs'            => array(
			'name' => __( 'Documentos', 'apollo-core' ),
			'desc' => __( 'Documentos e assinaturas digitais.', 'apollo-core' ),
			'icon' => 'dashicons-media-document',
		),
		'comunas'         => array(
			'name' => __( 'Comunas', 'apollo-core' ),
			'desc' => __( 'Comunidades e grupos.', 'apollo-core' ),
			'icon' => 'dashicons-networking',
		),
		'compatibilidade' => array(
			'name' => __( 'Matchmaking', 'apollo-core' ),
			'desc' => __( 'Match entre usuários (futuro).', 'apollo-core' ),
			'icon' => 'dashicons-heart',
		),
		'cena_rio'        => array(
			'name' => __( 'CENA::RIO', 'apollo-core' ),
			'desc' => __( 'Submissões e curadoria de eventos.', 'apollo-core' ),
			'icon' => 'dashicons-location-alt',
		),
		'classifieds'     => array(
			'name' => __( 'Classificados', 'apollo-core' ),
			'desc' => __( 'Anúncios e marketplace.', 'apollo-core' ),
			'icon' => 'dashicons-megaphone',
		),
		'notifications'   => array(
			'name' => __( 'Notificações', 'apollo-core' ),
			'desc' => __( 'Sistema de notificações push/in-app.', 'apollo-core' ),
			'icon' => 'dashicons-bell',
		),
		'onboarding'      => array(
			'name' => __( 'Onboarding', 'apollo-core' ),
			'desc' => __( 'Trilha de boas-vindas para novos usuários.', 'apollo-core' ),
			'icon' => 'dashicons-welcome-learn-more',
		),
		'achievements'    => array(
			'name' => __( 'Conquistas', 'apollo-core' ),
			'desc' => __( 'Conquistas privadas (futuro).', 'apollo-core' ),
			'icon' => 'dashicons-awards',
		),
	);
	?>

	<form method="post" class="apollo-cabin-form">
		<?php wp_nonce_field( 'apollo_cabin_modules', 'apollo_cabin_modules_nonce' ); ?>

		<div class="apollo-cabin-grid">
			<?php foreach ( $modules as $key => $enabled ) : ?>
				<?php
				$info = $module_info[ $key ] ?? array(
					'name' => ucfirst( $key ),
					'desc' => '',
					'icon' => 'dashicons-admin-plugins',
				);
				?>
				<div class="apollo-cabin-card <?php echo $enabled ? 'is-enabled' : 'is-disabled'; ?>">
					<div class="card-header">
						<span class="dashicons <?php echo esc_attr( $info['icon'] ); ?>"></span>
						<h3><?php echo esc_html( $info['name'] ); ?></h3>
					</div>
					<p class="card-desc"><?php echo esc_html( $info['desc'] ); ?></p>
					<label class="apollo-toggle">
						<input type="checkbox"
							name="modules[<?php echo esc_attr( $key ); ?>]"
							value="1"
							<?php checked( $enabled ); ?>>
						<span class="toggle-slider"></span>
						<span class="toggle-label">
							<?php echo $enabled ? esc_html__( 'Ativo', 'apollo-core' ) : esc_html__( 'Inativo', 'apollo-core' ); ?>
						</span>
					</label>
				</div>
			<?php endforeach; ?>
		</div>

		<p class="submit">
			<button type="submit" class="button button-primary button-hero">
				<span class="dashicons dashicons-saved"></span>
				<?php esc_html_e( 'Salvar Módulos', 'apollo-core' ); ?>
			</button>
		</p>
	</form>

	<?php
	apollo_admin_cabin_footer();
}

// =========================================================================
// LIMITS PAGE
// =========================================================================

/**
 * Render limits page
 */
function apollo_admin_cabin_render_limits(): void {
	apollo_admin_cabin_header(
		__( 'Limites Globais', 'apollo-core' ),
		__( 'Configure os limites de recursos por usuário.', 'apollo-core' )
	);

	$limits   = Apollo_Modules_Config::get_limits();
	$defaults = Apollo_Modules_Config::get_default_limits();

	$limit_groups = array(
		'events'      => array(
			'title'  => __( 'Eventos', 'apollo-core' ),
			'icon'   => 'dashicons-calendar-alt',
			'limits' => array(
				'max_events_per_user_month' => __( 'Eventos por mês', 'apollo-core' ),
				'max_events_pending_review' => __( 'Eventos pendentes de revisão', 'apollo-core' ),
			),
		),
		'social'      => array(
			'title'  => __( 'Social', 'apollo-core' ),
			'icon'   => 'dashicons-share',
			'limits' => array(
				'max_social_posts_per_day' => __( 'Posts por dia', 'apollo-core' ),
				'max_comments_per_hour'    => __( 'Comentários por hora', 'apollo-core' ),
			),
		),
		'bolha'       => array(
			'title'  => __( 'Bolha', 'apollo-core' ),
			'icon'   => 'dashicons-groups',
			'limits' => array(
				'max_bubble_members'         => __( 'Membros na bolha', 'apollo-core' ),
				'max_bubble_invites_per_day' => __( 'Convites de bolha por dia', 'apollo-core' ),
			),
		),
		'comunas'     => array(
			'title'  => __( 'Comunas', 'apollo-core' ),
			'icon'   => 'dashicons-networking',
			'limits' => array(
				'max_comunas_per_user' => __( 'Comunas por usuário', 'apollo-core' ),
				'max_comuna_members'   => __( 'Membros por comuna', 'apollo-core' ),
			),
		),
		'chat'        => array(
			'title'  => __( 'Chat', 'apollo-core' ),
			'icon'   => 'dashicons-format-chat',
			'limits' => array(
				'max_dm_per_hour'           => __( 'DMs por hora', 'apollo-core' ),
				'max_dm_recipients_per_day' => __( 'Destinatários por dia', 'apollo-core' ),
			),
		),
		'docs'        => array(
			'title'  => __( 'Documentos', 'apollo-core' ),
			'icon'   => 'dashicons-media-document',
			'limits' => array(
				'max_docs_per_user'      => __( 'Documentos por usuário', 'apollo-core' ),
				'max_pending_signatures' => __( 'Assinaturas pendentes', 'apollo-core' ),
			),
		),
		'classifieds' => array(
			'title'  => __( 'Classificados', 'apollo-core' ),
			'icon'   => 'dashicons-megaphone',
			'limits' => array(
				'max_ads_per_user'     => __( 'Anúncios ativos', 'apollo-core' ),
				'max_ad_duration_days' => __( 'Duração do anúncio (dias)', 'apollo-core' ),
			),
		),
		'general'     => array(
			'title'  => __( 'Geral', 'apollo-core' ),
			'icon'   => 'dashicons-admin-settings',
			'limits' => array(
				'max_uploads_per_day' => __( 'Uploads por dia', 'apollo-core' ),
				'max_upload_size_mb'  => __( 'Tamanho máximo de upload (MB)', 'apollo-core' ),
				'max_reports_per_day' => __( 'Denúncias por dia', 'apollo-core' ),
			),
		),
	);
	?>

	<form method="post" class="apollo-cabin-form">
		<?php wp_nonce_field( 'apollo_cabin_limits', 'apollo_cabin_limits_nonce' ); ?>

		<div class="apollo-cabin-limits-grid">
			<?php foreach ( $limit_groups as $group_key => $group ) : ?>
				<div class="limits-group">
					<h3>
						<span class="dashicons <?php echo esc_attr( $group['icon'] ); ?>"></span>
						<?php echo esc_html( $group['title'] ); ?>
					</h3>
					<table class="widefat">
						<tbody>
							<?php foreach ( $group['limits'] as $key => $label ) : ?>
								<tr>
									<td class="limit-label">
										<label for="limit-<?php echo esc_attr( $key ); ?>">
											<?php echo esc_html( $label ); ?>
										</label>
									</td>
									<td class="limit-input">
										<input type="number"
											id="limit-<?php echo esc_attr( $key ); ?>"
											name="limits[<?php echo esc_attr( $key ); ?>]"
											value="<?php echo esc_attr( $limits[ $key ] ?? $defaults[ $key ] ?? 0 ); ?>"
											min="0"
											class="small-text">
									</td>
									<td class="limit-default">
										<span class="description">
											<?php
											printf(
												/* translators: %d: default value */
												esc_html__( 'Padrão: %d', 'apollo-core' ),
												$defaults[ $key ] ?? 0
											);
											?>
										</span>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endforeach; ?>
		</div>

		<p class="submit">
			<button type="submit" class="button button-primary button-hero">
				<span class="dashicons dashicons-saved"></span>
				<?php esc_html_e( 'Salvar Limites', 'apollo-core' ); ?>
			</button>
		</p>
	</form>

	<?php
	apollo_admin_cabin_footer();
}

// =========================================================================
// MODERATORS PAGE
// =========================================================================

/**
 * Render moderators page
 */
function apollo_admin_cabin_render_moderators(): void {
	apollo_admin_cabin_header(
		__( 'Moderadores', 'apollo-core' ),
		__( 'Gerencie níveis de moderação dos usuários.', 'apollo-core' )
	);

	// Get users with mod levels.
	$mod_users = get_users(
		array(
			'meta_key'     => 'apollo_mod_level',
			'meta_compare' => 'EXISTS',
		)
	);

	$level_labels = array(
		-1 => __( 'Nenhum', 'apollo-core' ),
		0  => __( 'MOD 0 - Básico', 'apollo-core' ),
		1  => __( 'MOD 1 - Avançado', 'apollo-core' ),
		3  => __( 'MOD 3 - Pleno', 'apollo-core' ),
	);

	$level_descriptions = array(
		-1 => __( 'Usuário comum, sem permissões de moderação.', 'apollo-core' ),
		0  => __( 'Visualizar fila de moderação, aprovar conteúdo.', 'apollo-core' ),
		1  => __( 'Editar/remover conteúdo, suspensão temporária.', 'apollo-core' ),
		3  => __( 'Suspender/banir usuários, acesso total exceto IP e configurações.', 'apollo-core' ),
	);
	?>

	<div class="apollo-cabin-moderators">
		<!-- Add new moderator -->
		<div class="add-moderator-section">
			<h3><?php esc_html_e( 'Adicionar/Alterar Moderador', 'apollo-core' ); ?></h3>
			<form method="post" class="add-moderator-form">
				<?php wp_nonce_field( 'apollo_cabin_mod', 'apollo_cabin_mod_nonce' ); ?>

				<div class="form-row">
					<label for="mod-user-search"><?php esc_html_e( 'Usuário:', 'apollo-core' ); ?></label>
					<?php
					wp_dropdown_users(
						array(
							'name'             => 'mod_user_id',
							'id'               => 'mod-user-search',
							'show_option_none' => __( 'Selecione um usuário...', 'apollo-core' ),
							'class'            => 'regular-text',
						)
					);
					?>
				</div>

				<div class="form-row">
					<label for="mod-level"><?php esc_html_e( 'Nível:', 'apollo-core' ); ?></label>
					<select name="mod_level" id="mod-level" class="regular-text">
						<?php foreach ( $level_labels as $level => $label ) : ?>
							<option value="<?php echo esc_attr( $level ); ?>">
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="form-row">
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Definir Nível', 'apollo-core' ); ?>
					</button>
				</div>
			</form>
		</div>

		<!-- Levels explanation -->
		<div class="levels-explanation">
			<h3><?php esc_html_e( 'Níveis de Moderação', 'apollo-core' ); ?></h3>
			<div class="levels-grid">
				<?php foreach ( $level_labels as $level => $label ) : ?>
					<?php
					if ( $level < 0 ) {
						continue;
					}
					?>
					<div class="level-card level-<?php echo esc_attr( $level ); ?>">
						<h4><?php echo esc_html( $label ); ?></h4>
						<p><?php echo esc_html( $level_descriptions[ $level ] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Current moderators -->
		<div class="current-moderators">
			<h3><?php esc_html_e( 'Moderadores Atuais', 'apollo-core' ); ?></h3>
			<?php if ( empty( $mod_users ) ) : ?>
				<p class="no-moderators"><?php esc_html_e( 'Nenhum moderador configurado.', 'apollo-core' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Usuário', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Email', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Nível', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Status', 'apollo-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $mod_users as $mod ) : ?>
							<?php
							$level  = Apollo_User_Moderation::get_mod_level( $mod->ID );
							$status = Apollo_User_Moderation::get_user_status( $mod->ID );
							?>
							<tr>
								<td>
									<?php echo get_avatar( $mod->ID, 32 ); ?>
									<strong><?php echo esc_html( $mod->display_name ); ?></strong>
								</td>
								<td><?php echo esc_html( $mod->user_email ); ?></td>
								<td>
									<span class="mod-level-badge level-<?php echo esc_attr( $level ); ?>">
										<?php echo esc_html( $level_labels[ $level ] ?? __( 'Desconhecido', 'apollo-core' ) ); ?>
									</span>
								</td>
								<td>
									<span class="user-status-badge status-<?php echo esc_attr( $status ); ?>">
										<?php echo esc_html( ucfirst( $status ) ); ?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>

	<?php
	apollo_admin_cabin_footer();
}

// =========================================================================
// SECURITY PAGE
// =========================================================================

/**
 * Render security page
 */
function apollo_admin_cabin_render_security(): void {
	apollo_admin_cabin_header(
		__( 'Segurança', 'apollo-core' ),
		__( 'Bloqueio de IP e controles de segurança.', 'apollo-core' )
	);

	$blocklist = Apollo_User_Moderation::get_ip_blocklist();
	?>

	<div class="apollo-cabin-security">
		<!-- Block IP form -->
		<div class="block-ip-section">
			<h3><?php esc_html_e( 'Bloquear IP', 'apollo-core' ); ?></h3>
			<form method="post" class="block-ip-form">
				<?php wp_nonce_field( 'apollo_cabin_ip', 'apollo_cabin_ip_nonce' ); ?>

				<div class="form-row">
					<label for="block-ip"><?php esc_html_e( 'Endereço IP:', 'apollo-core' ); ?></label>
					<input type="text"
						id="block-ip"
						name="block_ip"
						placeholder="192.168.1.1"
						pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$|^(?:[a-fA-F0-9]{1,4}:){7}[a-fA-F0-9]{1,4}$"
						class="regular-text">
				</div>

				<div class="form-row">
					<label for="block-reason"><?php esc_html_e( 'Motivo:', 'apollo-core' ); ?></label>
					<input type="text"
						id="block-reason"
						name="block_reason"
						placeholder="<?php esc_attr_e( 'Spam, abuso, etc.', 'apollo-core' ); ?>"
						class="regular-text">
				</div>

				<div class="form-row">
					<button type="submit" class="button button-primary">
						<span class="dashicons dashicons-shield"></span>
						<?php esc_html_e( 'Bloquear IP', 'apollo-core' ); ?>
					</button>
				</div>
			</form>

			<div class="notice notice-warning inline">
				<p>
					<strong><?php esc_html_e( 'Atenção:', 'apollo-core' ); ?></strong>
					<?php esc_html_e( 'Bloquear IPs incorretamente pode impedir acesso legítimo. Use com cuidado.', 'apollo-core' ); ?>
				</p>
			</div>
		</div>

		<!-- Current blocklist -->
		<div class="current-blocklist">
			<h3><?php esc_html_e( 'IPs Bloqueados', 'apollo-core' ); ?></h3>
			<?php if ( empty( $blocklist ) ) : ?>
				<p class="no-blocked"><?php esc_html_e( 'Nenhum IP bloqueado.', 'apollo-core' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Hash (parcial)', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Bloqueado em', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Bloqueado por', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Motivo', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Ações', 'apollo-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $blocklist as $entry ) : ?>
							<?php $blocker = get_userdata( $entry['blocked_by'] ?? 0 ); ?>
							<tr>
								<td><code><?php echo esc_html( substr( $entry['hash'], 0, 16 ) . '...' ); ?></code></td>
								<td><?php echo esc_html( wp_date( 'd/m/Y H:i', $entry['blocked_at'] ?? 0 ) ); ?></td>
								<td><?php echo esc_html( $blocker ? $blocker->display_name : '-' ); ?></td>
								<td><?php echo esc_html( $entry['reason'] ?? '-' ); ?></td>
								<td>
									<button type="button"
										class="button button-small unblock-ip-btn"
										data-hash="<?php echo esc_attr( $entry['hash'] ); ?>">
										<?php esc_html_e( 'Desbloquear', 'apollo-core' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<!-- Audit log settings -->
		<div class="audit-settings">
			<h3><?php esc_html_e( 'Configurações de Auditoria', 'apollo-core' ); ?></h3>
			<?php
			$settings = apollo_get_mod_settings();
			?>
			<form method="post">
				<?php wp_nonce_field( 'apollo_cabin_audit', 'apollo_cabin_audit_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Log de Auditoria', 'apollo-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox"
									name="audit_log_enabled"
									value="1"
									<?php checked( ! empty( $settings['audit_log_enabled'] ) ); ?>>
								<?php esc_html_e( 'Registrar ações de moderação', 'apollo-core' ); ?>
							</label>
						</td>
					</tr>
				</table>
			</form>
		</div>
	</div>

	<?php
	apollo_admin_cabin_footer();
}

// =========================================================================
// LOGS PAGE
// =========================================================================

/**
 * Render logs page
 */
function apollo_admin_cabin_render_logs(): void {
	apollo_admin_cabin_header(
		__( 'Logs de Auditoria', 'apollo-core' ),
		__( 'Histórico de ações de moderação e segurança.', 'apollo-core' )
	);

	// Get filters.
	$filter_action = isset( $_GET['filter_action'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_action'] ) ) : '';
	$filter_actor  = isset( $_GET['filter_actor'] ) ? absint( $_GET['filter_actor'] ) : 0;
	$page_num      = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	$per_page      = 50;

	// Build query args.
	$args = array(
		'limit'  => $per_page,
		'offset' => ( $page_num - 1 ) * $per_page,
	);

	if ( $filter_action ) {
		$args['action'] = $filter_action;
	}

	if ( $filter_actor ) {
		$args['actor_id'] = $filter_actor;
	}

	// Get logs.
	$logs = function_exists( 'apollo_get_mod_log' ) ? apollo_get_mod_log( $args ) : array();

	// Available action types for filter.
	$action_types = array(
		''               => __( 'Todas as ações', 'apollo-core' ),
		'suspend_user'   => __( 'Suspensões', 'apollo-core' ),
		'unsuspend_user' => __( 'Reativações', 'apollo-core' ),
		'ban_user'       => __( 'Banimentos', 'apollo-core' ),
		'unban_user'     => __( 'Desbanimentos', 'apollo-core' ),
		'block_ip'       => __( 'Bloqueio de IP', 'apollo-core' ),
		'unblock_ip'     => __( 'Desbloqueio de IP', 'apollo-core' ),
		'set_mod_level'  => __( 'Alteração de nível', 'apollo-core' ),
		'enable_module'  => __( 'Módulo ativado', 'apollo-core' ),
		'disable_module' => __( 'Módulo desativado', 'apollo-core' ),
		'update_limits'  => __( 'Limites alterados', 'apollo-core' ),
	);
	?>

	<div class="apollo-cabin-logs">
		<!-- Filters -->
		<div class="log-filters">
			<form method="get">
				<input type="hidden" name="page" value="apollo-cabin-logs">

				<select name="filter_action">
					<?php foreach ( $action_types as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filter_action, $value ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<?php
				wp_dropdown_users(
					array(
						'name'            => 'filter_actor',
						'show_option_all' => __( 'Todos os atores', 'apollo-core' ),
						'selected'        => $filter_actor,
					)
				);
				?>

				<button type="submit" class="button"><?php esc_html_e( 'Filtrar', 'apollo-core' ); ?></button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo-cabin-logs' ) ); ?>" class="button">
					<?php esc_html_e( 'Limpar', 'apollo-core' ); ?>
				</a>
			</form>
		</div>

		<!-- Logs table -->
		<?php if ( empty( $logs ) ) : ?>
			<div class="no-logs">
				<p><?php esc_html_e( 'Nenhum registro encontrado.', 'apollo-core' ); ?></p>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th class="column-date"><?php esc_html_e( 'Data', 'apollo-core' ); ?></th>
						<th class="column-actor"><?php esc_html_e( 'Ator', 'apollo-core' ); ?></th>
						<th class="column-action"><?php esc_html_e( 'Ação', 'apollo-core' ); ?></th>
						<th class="column-target"><?php esc_html_e( 'Alvo', 'apollo-core' ); ?></th>
						<th class="column-details"><?php esc_html_e( 'Detalhes', 'apollo-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $logs as $log ) : ?>
						<?php
						$actor  = get_userdata( $log->actor_id );
						$target = null;
						if ( 'user' === $log->target_type && $log->target_id > 0 ) {
							$target = get_userdata( $log->target_id );
						}
						?>
						<tr>
							<td>
								<?php echo esc_html( wp_date( 'd/m/Y H:i:s', strtotime( $log->created_at ) ) ); ?>
							</td>
							<td>
								<?php if ( $actor ) : ?>
									<?php echo get_avatar( $actor->ID, 24 ); ?>
									<?php echo esc_html( $actor->display_name ); ?>
								<?php else : ?>
									<em><?php esc_html_e( 'Sistema', 'apollo-core' ); ?></em>
								<?php endif; ?>
							</td>
							<td>
								<span class="action-badge action-<?php echo esc_attr( $log->action ); ?>">
									<?php echo esc_html( $action_types[ $log->action ] ?? $log->action ); ?>
								</span>
							</td>
							<td>
								<?php if ( $target ) : ?>
									<?php echo get_avatar( $target->ID, 24 ); ?>
									<?php echo esc_html( $target->display_name ); ?>
								<?php elseif ( $log->target_type && $log->target_id ) : ?>
									<?php echo esc_html( ucfirst( $log->target_type ) . ' #' . $log->target_id ); ?>
								<?php else : ?>
									-
								<?php endif; ?>
							</td>
							<td>
								<?php if ( ! empty( $log->details ) ) : ?>
									<button type="button" class="button button-small toggle-details">
										<?php esc_html_e( 'Ver', 'apollo-core' ); ?>
									</button>
									<div class="log-details hidden">
										<pre><?php echo esc_html( wp_json_encode( $log->details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></pre>
									</div>
								<?php else : ?>
									-
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<!-- Pagination -->
			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<?php if ( $page_num > 1 ) : ?>
						<a class="button" href="<?php echo esc_url( add_query_arg( 'paged', $page_num - 1 ) ); ?>">
							&laquo; <?php esc_html_e( 'Anterior', 'apollo-core' ); ?>
						</a>
					<?php endif; ?>

					<span class="paging-input">
						<?php
						printf(
							/* translators: %d: page number */
							esc_html__( 'Página %d', 'apollo-core' ),
							$page_num
						);
						?>
					</span>

					<?php if ( count( $logs ) === $per_page ) : ?>
						<a class="button" href="<?php echo esc_url( add_query_arg( 'paged', $page_num + 1 ) ); ?>">
							<?php esc_html_e( 'Próxima', 'apollo-core' ); ?> &raquo;
						</a>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<?php
	apollo_admin_cabin_footer();
}
