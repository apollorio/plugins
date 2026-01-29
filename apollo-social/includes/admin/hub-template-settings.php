<?php
/**
 * Admin Settings: HUB Default Template Configuration
 *
 * Allows admins to set default profile/blocks for new users.
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the admin submenu page
 */
add_action( 'admin_menu', 'apollo_hub_register_template_settings_page' );
function apollo_hub_register_template_settings_page() {
	add_submenu_page(
		'apollo-social',
		__( 'HUB Template Padrão', 'apollo-social' ),
		__( 'HUB Template', 'apollo-social' ),
		'manage_options',
		'apollo-hub-template',
		'apollo_hub_render_template_settings_page'
	);
}

/**
 * Register settings
 */
add_action( 'admin_init', 'apollo_hub_register_template_settings' );
function apollo_hub_register_template_settings() {
	register_setting( 'apollo_hub_template', 'apollo_hub_default_template', array(
		'type'              => 'array',
		'sanitize_callback' => 'apollo_hub_sanitize_template',
		'default'           => apollo_hub_get_default_template(),
	) );
}

/**
 * Get default template structure
 */
function apollo_hub_get_default_template() {
	return array(
		'profile' => array(
			'avatar'            => '',
			'avatarStyle'       => 'rounded',
			'avatarBorder'      => false,
			'avatarBorderWidth' => 4,
			'avatarBorderColor' => '#ffffff',
			'name'              => '',
			'bio'               => '',
			'bg'                => '',
			'texture'           => 'none',
		),
		'blocks'  => array(),
	);
}

/**
 * Sanitize template data
 */
function apollo_hub_sanitize_template( $input ) {
	$sanitized = apollo_hub_get_default_template();

	if ( isset( $input['profile'] ) && is_array( $input['profile'] ) ) {
		$sanitized['profile'] = array(
			'avatar'            => sanitize_text_field( $input['profile']['avatar'] ?? '' ),
			'avatarStyle'       => in_array( $input['profile']['avatarStyle'] ?? '', array( 'rounded', 'hero' ), true ) ? $input['profile']['avatarStyle'] : 'rounded',
			'avatarBorder'      => ! empty( $input['profile']['avatarBorder'] ),
			'avatarBorderWidth' => intval( $input['profile']['avatarBorderWidth'] ?? 4 ),
			'avatarBorderColor' => sanitize_hex_color( $input['profile']['avatarBorderColor'] ?? '#ffffff' ),
			'name'              => sanitize_text_field( $input['profile']['name'] ?? '' ),
			'bio'               => sanitize_textarea_field( $input['profile']['bio'] ?? '' ),
			'bg'                => esc_url_raw( $input['profile']['bg'] ?? '' ),
			'texture'           => sanitize_text_field( $input['profile']['texture'] ?? 'none' ),
		);
	}

	if ( isset( $input['blocks'] ) && is_string( $input['blocks'] ) ) {
		$blocks = json_decode( $input['blocks'], true );
		if ( is_array( $blocks ) ) {
			$sanitized['blocks'] = $blocks;
		}
	}

	return $sanitized;
}

/**
 * Apply default template to new users
 */
add_action( 'user_register', 'apollo_hub_apply_default_template' );
function apollo_hub_apply_default_template( $user_id ) {
	$template = get_option( 'apollo_hub_default_template', apollo_hub_get_default_template() );

	if ( empty( $template ) || ! is_array( $template ) ) {
		return;
	}

	$profile = $template['profile'] ?? array();
	$blocks  = $template['blocks'] ?? array();

	// Apply profile settings (only if defaults are set)
	if ( ! empty( $profile['avatarStyle'] ) ) {
		update_user_meta( $user_id, '_apollo_hub_avatar_style', $profile['avatarStyle'] );
	}
	if ( ! empty( $profile['avatarBorder'] ) ) {
		update_user_meta( $user_id, '_apollo_hub_avatar_border', '1' );
	}
	if ( ! empty( $profile['avatarBorderWidth'] ) ) {
		update_user_meta( $user_id, '_apollo_hub_avatar_border_width', $profile['avatarBorderWidth'] );
	}
	if ( ! empty( $profile['avatarBorderColor'] ) ) {
		update_user_meta( $user_id, '_apollo_hub_avatar_border_color', $profile['avatarBorderColor'] );
	}
	if ( ! empty( $profile['texture'] ) ) {
		update_user_meta( $user_id, '_apollo_hub_texture', $profile['texture'] );
	}

	// Apply default blocks
	if ( ! empty( $blocks ) ) {
		update_user_meta( $user_id, '_apollo_hub_blocks', wp_json_encode( $blocks ) );
	}
}

/**
 * Render the settings page
 */
function apollo_hub_render_template_settings_page() {
	$template = get_option( 'apollo_hub_default_template', apollo_hub_get_default_template() );
	$profile  = $template['profile'] ?? array();
	$blocks   = $template['blocks'] ?? array();
	?>
	<div class="wrap">
		<h1>
			<span class="dashicons dashicons-layout" style="margin-right:8px;"></span>
			<?php esc_html_e( 'HUB Template Padrão', 'apollo-social' ); ?>
		</h1>

		<p class="description" style="margin-bottom:20px;">
			<?php esc_html_e( 'Configure o template padrão que será aplicado automaticamente para novos usuários.', 'apollo-social' ); ?>
		</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'apollo_hub_template' ); ?>

			<div class="apollo-admin-card">
				<h2><?php esc_html_e( 'Configurações de Avatar', 'apollo-social' ); ?></h2>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="avatar_style"><?php esc_html_e( 'Estilo do Avatar', 'apollo-social' ); ?></label>
						</th>
						<td>
							<select name="apollo_hub_default_template[profile][avatarStyle]" id="avatar_style">
								<option value="rounded" <?php selected( $profile['avatarStyle'] ?? '', 'rounded' ); ?>>
									<?php esc_html_e( 'Arredondado', 'apollo-social' ); ?>
								</option>
								<option value="hero" <?php selected( $profile['avatarStyle'] ?? '', 'hero' ); ?>>
									<?php esc_html_e( 'Hero Animado', 'apollo-social' ); ?>
								</option>
							</select>
							<p class="description">
								<?php esc_html_e( 'Hero = Avatar com animação morphing dinâmica.', 'apollo-social' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="avatar_border"><?php esc_html_e( 'Borda do Avatar', 'apollo-social' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" name="apollo_hub_default_template[profile][avatarBorder]" id="avatar_border" value="1" <?php checked( ! empty( $profile['avatarBorder'] ) ); ?> />
								<?php esc_html_e( 'Mostrar borda ao redor do avatar', 'apollo-social' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="avatar_border_width"><?php esc_html_e( 'Largura da Borda', 'apollo-social' ); ?></label>
						</th>
						<td>
							<input type="number" name="apollo_hub_default_template[profile][avatarBorderWidth]" id="avatar_border_width" value="<?php echo esc_attr( $profile['avatarBorderWidth'] ?? 4 ); ?>" min="1" max="20" class="small-text" /> px
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="avatar_border_color"><?php esc_html_e( 'Cor da Borda', 'apollo-social' ); ?></label>
						</th>
						<td>
							<input type="color" name="apollo_hub_default_template[profile][avatarBorderColor]" id="avatar_border_color" value="<?php echo esc_attr( $profile['avatarBorderColor'] ?? '#ffffff' ); ?>" />
						</td>
					</tr>
				</table>
			</div>

			<div class="apollo-admin-card" style="margin-top:20px;">
				<h2><?php esc_html_e( 'Configurações de Aparência', 'apollo-social' ); ?></h2>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="texture"><?php esc_html_e( 'Textura Padrão', 'apollo-social' ); ?></label>
						</th>
						<td>
							<select name="apollo_hub_default_template[profile][texture]" id="texture">
								<?php
								$textures = array(
									'none'     => __( 'Nenhuma', 'apollo-social' ),
									'dots'     => 'Dots',
									'waves'    => 'Waves',
									'grid'     => 'Grid',
									'noise'    => 'Noise',
									'confetti' => 'Confetti',
								);
								foreach ( $textures as $key => $label ) :
									?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $profile['texture'] ?? 'none', $key ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>
			</div>

			<div class="apollo-admin-card" style="margin-top:20px;">
				<h2><?php esc_html_e( 'Blocos Padrão', 'apollo-social' ); ?></h2>

				<p class="description">
					<?php esc_html_e( 'Cole aqui o JSON dos blocos padrão. Para gerar, crie uma página de exemplo e exporte os blocos.', 'apollo-social' ); ?>
				</p>

				<textarea name="apollo_hub_default_template[blocks]" id="default_blocks" rows="10" class="large-text code" placeholder='[{"id":1,"type":"title_block","text":"Bem-vindo!","align":"center","size":"large","visible":true}]'><?php echo esc_textarea( wp_json_encode( $blocks, JSON_PRETTY_PRINT ) ); ?></textarea>

				<p class="description" style="margin-top:10px;">
					<strong><?php esc_html_e( 'Tipos disponíveis:', 'apollo-social' ); ?></strong><br>
					<code>title_block</code>, <code>paragraph_block</code>, <code>bio_block</code>, <code>card_simple</code>, <code>card_icon</code>,
					<code>image_link</code>, <code>image_overlay</code>, <code>youtube</code>, <code>spotify</code>, <code>soundcloud</code>,
					<code>social_links</code>, <code>share_page</code>, <code>testimonials</code>, <code>rating_stars</code>, <code>orkut_rate</code>,
					<code>marquee</code>, <code>text_block</code>, <code>divider</code>
				</p>
			</div>

			<?php submit_button( __( 'Salvar Template', 'apollo-social' ) ); ?>
		</form>
	</div>

	<style>
		.apollo-admin-card {
			background: #fff;
			border: 1px solid #c3c4c7;
			border-radius: 4px;
			padding: 20px;
			box-shadow: 0 1px 1px rgba(0,0,0,.04);
		}
		.apollo-admin-card h2 {
			margin-top: 0;
			padding-bottom: 10px;
			border-bottom: 1px solid #eee;
		}
	</style>
	<?php
}
