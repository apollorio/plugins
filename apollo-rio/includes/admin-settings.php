<?php
// phpcs:ignoreFile
declare(strict_types=1);
/**
 * Admin Settings for Apollo PWA
 *
 * @package Apollo_Rio
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function apollo_add_admin_menu() {
	add_options_page(
		'Apollo::Rio Settings',
		'Apollo::Rio',
		'manage_options',
		'apollo-settings',
		'apollo_settings_page'
	);
}
add_action( 'admin_menu', 'apollo_add_admin_menu' );

function apollo_settings_init() {
	// Register settings with sanitization callbacks
	register_setting( 'apollo_settings', 'apollo_android_app_url', 'apollo_sanitize_android_app_url' );

	add_settings_section(
		'apollo_settings_section',
		__( 'PWA Configuration', 'apollo-rio' ),
		'apollo_settings_section_callback',
		'apollo_settings'
	);

	add_settings_field(
		'apollo_android_app_url',
		__( 'Android App URL', 'apollo-rio' ),
		'apollo_android_app_url_render',
		'apollo_settings',
		'apollo_settings_section'
	);

	// SEO Enhancement Settings.
	$seo_content_types = apollo_get_seo_content_types();
	foreach ( $seo_content_types as $type_key => $type_label ) {
		register_setting(
			'apollo_settings',
			'apollo_seo_' . $type_key,
			array(
				'type'              => 'boolean',
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
			)
		);
	}

	add_settings_section(
		'apollo_seo_section',
		__( 'SEO Enhancement Settings', 'apollo-rio' ),
		'apollo_seo_section_callback',
		'apollo_settings'
	);

	add_settings_field(
		'apollo_seo_toggles',
		__( 'Enable SEO for Content Types', 'apollo-rio' ),
		'apollo_seo_toggles_render',
		'apollo_settings',
		'apollo_seo_section'
	);
}
add_action( 'admin_init', 'apollo_settings_init' );

/**
 * Get the list of content types that support SEO enhancements.
 *
 * @return array Associative array of type_key => type_label.
 */
function apollo_get_seo_content_types() {
	return array(
		'user'        => __( 'User Profiles', 'apollo-rio' ),
		'comunidade'  => __( 'Comunidades (Groups)', 'apollo-rio' ),
		'nucleo'      => __( 'Núcleos (Groups)', 'apollo-rio' ),
		'dj'          => __( 'DJs', 'apollo-rio' ),
		'local'       => __( 'Locais (Venues)', 'apollo-rio' ),
		'event'       => __( 'Events', 'apollo-rio' ),
		'classifieds' => __( 'Classifieds', 'apollo-rio' ),
	);
}

/**
 * SEO section description callback.
 */
function apollo_seo_section_callback() {
	?>
	<p><?php esc_html_e( 'Control SEO enhancements (meta title, description, Open Graph tags) for each content type. Enabled types will have automatic SEO meta tags injected into page headers.', 'apollo-rio' ); ?></p>
	<?php
}

/**
 * Render SEO toggle checkboxes for each content type.
 */
function apollo_seo_toggles_render() {
	$content_types = apollo_get_seo_content_types();
	?>
	<fieldset>
		<legend class="screen-reader-text"><?php esc_html_e( 'SEO Enhancement Toggles', 'apollo-rio' ); ?></legend>
		<table class="form-table apollo-seo-toggles" role="presentation" style="margin-top: 0;">
			<tbody>
			<?php foreach ( $content_types as $type_key => $type_label ) : ?>
				<?php
				$option_name  = 'apollo_seo_' . $type_key;
				$option_value = get_option( $option_name, true );
				?>
				<tr>
					<td style="padding: 8px 0; width: 250px;">
						<label for="<?php echo esc_attr( $option_name ); ?>">
							<input
								type="checkbox"
								id="<?php echo esc_attr( $option_name ); ?>"
								name="<?php echo esc_attr( $option_name ); ?>"
								value="1"
								<?php checked( $option_value, true ); ?>
							>
							<strong><?php echo esc_html( $type_label ); ?></strong>
						</label>
					</td>
					<td style="padding: 8px 0;">
						<?php echo esc_html( apollo_get_seo_type_description( $type_key ) ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</fieldset>
	<p class="description" style="margin-top: 15px;">
		<?php esc_html_e( 'When enabled, Apollo::Rio will automatically generate meta tags including og:title, og:description, og:image, and structured data for better search engine visibility and social sharing.', 'apollo-rio' ); ?>
	</p>
	<?php
}

/**
 * Get description text for each SEO content type.
 *
 * @param string $type_key The content type key.
 * @return string Description text.
 */
function apollo_get_seo_type_description( $type_key ) {
	$descriptions = array(
		'user'        => __( 'User profile pages (/u/username)', 'apollo-rio' ),
		'comunidade'  => __( 'Community group pages (/comunidade/slug)', 'apollo-rio' ),
		'nucleo'      => __( 'Núcleo group pages (/nucleo/slug)', 'apollo-rio' ),
		'dj'          => __( 'DJ profile pages (/dj/slug)', 'apollo-rio' ),
		'local'       => __( 'Venue pages (/local/slug)', 'apollo-rio' ),
		'event'       => __( 'Event listing pages (/event/slug)', 'apollo-rio' ),
		'classifieds' => __( 'Classified ad pages', 'apollo-rio' ),
	);
	return isset( $descriptions[ $type_key ] ) ? $descriptions[ $type_key ] : '';
}

/**
 * Check if SEO is enabled for a specific content type.
 *
 * @param string $type_key The content type key (user, comunidade, nucleo, dj, local, event, classifieds).
 * @return bool Whether SEO is enabled for this type.
 */
function apollo_is_seo_enabled( $type_key ) {
	$option_name = 'apollo_seo_' . sanitize_key( $type_key );
	return (bool) get_option( $option_name, true );
}

function apollo_settings_section_callback() {
	esc_html_e( 'Configure PWA and app download settings', 'apollo-rio' );
}

function apollo_android_app_url_render() {
	$value = get_option( 'apollo_android_app_url', '' );
	?>
	<input type="url" name="apollo_android_app_url" value="<?php echo esc_attr( $value ); ?>" style="width: 400px;">
	<p class="description"><?php esc_html_e( 'Google Play Store URL for Android app', 'apollo-rio' ); ?></p>
	<?php
}

function apollo_sanitize_android_app_url( $value ) {
	$value = esc_url_raw( $value );
	if ( ! empty( $value ) && ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
		add_settings_error( 'apollo_android_app_url', 'invalid_url', __( 'Invalid URL format. Please enter a valid URL.', 'apollo-rio' ) );
		return get_option( 'apollo_android_app_url', '' );
	}
	return $value;
}

function apollo_settings_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Apollo::Rio Settings', 'apollo-rio' ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'apollo_settings' );
			do_settings_sections( 'apollo_settings' );
			submit_button();
			?>
		</form>

		<hr>

		<h2><?php esc_html_e( 'Como Associar Templates PWA às Páginas', 'apollo-rio' ); ?></h2>
		<div class="card" style="max-width: 800px; margin-top: 20px;">
			<ol style="line-height: 1.8;">
				<li>
					<strong><?php esc_html_e( 'Edite a página desejada:', 'apollo-rio' ); ?></strong>
					<?php esc_html_e( 'Vá para Páginas → Todas as Páginas e clique em "Editar" na página que deseja configurar.', 'apollo-rio' ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Selecione o template:', 'apollo-rio' ); ?></strong>
					<?php esc_html_e( 'Na coluna lateral direita, encontre o metabox "Atributos da Página" → "Template".', 'apollo-rio' ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Escolha um dos templates disponíveis:', 'apollo-rio' ); ?></strong>
					<ul style="margin-top: 10px; margin-left: 20px;">
						<li><strong>Site::rio</strong> - <?php esc_html_e( 'Página pública, sempre mostra conteúdo', 'apollo-rio' ); ?></li>
						<li><strong>App::rio</strong> - <?php esc_html_e( 'Página do app com header completo', 'apollo-rio' ); ?></li>
						<li><strong>App::rio clean</strong> - <?php esc_html_e( 'Página do app sem navegação (minimal)', 'apollo-rio' ); ?></li>
					</ul>
				</li>
				<li>
					<strong><?php esc_html_e( 'Salve a página:', 'apollo-rio' ); ?></strong>
					<?php esc_html_e( 'Clique em "Atualizar" para salvar as alterações.', 'apollo-rio' ); ?>
				</li>
			</ol>
		</div>

		<h2><?php esc_html_e( 'Guia de Templates PWA', 'apollo-rio' ); ?></h2>
		<table class="widefat" style="margin-top: 20px;">
			<thead>
				<tr>
					<th style="padding: 12px;"><?php esc_html_e( 'Template', 'apollo-rio' ); ?></th>
					<th style="padding: 12px;"><?php esc_html_e( 'Header/Footer', 'apollo-rio' ); ?></th>
					<th style="padding: 12px;"><?php esc_html_e( 'Desktop', 'apollo-rio' ); ?></th>
					<th style="padding: 12px;"><?php esc_html_e( 'Mobile Browser', 'apollo-rio' ); ?></th>
					<th style="padding: 12px;"><?php esc_html_e( 'Mobile PWA', 'apollo-rio' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><strong>Site::rio</strong></td>
					<td><?php esc_html_e( 'Full (with nav)', 'apollo-rio' ); ?></td>
					<td>✅ <?php esc_html_e( 'Content', 'apollo-rio' ); ?></td>
					<td>✅ <?php esc_html_e( 'Content', 'apollo-rio' ); ?></td>
					<td>✅ <?php esc_html_e( 'Content', 'apollo-rio' ); ?></td>
				</tr>
				<tr>
					<td><strong>App::rio</strong></td>
					<td><?php esc_html_e( 'Full (with nav)', 'apollo-rio' ); ?></td>
					<td>✅ <?php esc_html_e( 'Content', 'apollo-rio' ); ?></td>
					<td>⚠️ <?php esc_html_e( 'Install Page', 'apollo-rio' ); ?></td>
					<td>✅ <?php esc_html_e( 'Content', 'apollo-rio' ); ?></td>
				</tr>
				<tr>
					<td><strong>App::rio clean</strong></td>
					<td><?php esc_html_e( 'Minimal (no nav)', 'apollo-rio' ); ?></td>
					<td>✅ <?php esc_html_e( 'Content', 'apollo-rio' ); ?></td>
					<td>⚠️ <?php esc_html_e( 'Install Page', 'apollo-rio' ); ?></td>
					<td>✅ <?php esc_html_e( 'Content', 'apollo-rio' ); ?></td>
				</tr>
			</tbody>
		</table>

		<div class="notice notice-info" style="margin-top: 20px;">
			<p>
				<strong><?php esc_html_e( 'Nota:', 'apollo-rio' ); ?></strong>
				<?php esc_html_e( 'Os templates "App::rio" e "App::rio clean" mostram uma página de instalação do PWA quando acessados via navegador mobile. Quando instalado como PWA, o conteúdo é exibido normalmente.', 'apollo-rio' ); ?>
			</p>
		</div>
	</div>
	<?php
}
