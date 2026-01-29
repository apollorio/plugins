<?php
/**
 * ================================================================================
 * APOLLO AUTH - Header Template Part
 * ================================================================================
 * Displays the Apollo::Rio branding header with logo, coordinates, and timestamp.
 *
 * @package Apollo_Social
 * @since 1.0.0
 *
 * PLACEHOLDERS:
 * - {{brand_name}} - Brand display name (Apollo::Rio)
 * - {{brand_subtitle}} - Subtitle text
 * - {{coordinates}} - Location coordinates
 * - {{timestamp}} - Current UTC timestamp
 * ================================================================================
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get customizable values via filters
$brand_name     = apply_filters( 'apollo_auth_brand_name', 'Apollo::Rio' );
$brand_subtitle = apply_filters( 'apollo_auth_brand_subtitle', 'Terminal de Acesso' );
$coordinates    = apply_filters( 'apollo_auth_coordinates', '-22.9068° S, 43.1729° W' );

// Matrix ASCII art for terminal header
$ascii_art = apply_filters(
	'apollo_auth_ascii_art',
	'
    _    ____   ___  _     _     ___
   / \  |  _ \ / _ \| |   | |   / _ \
  / _ \ | |_) | | | | |   | |  | | | |
 / ___ \|  __/| |_| | |___| |__| |_| |
/_/   \_\_|    \___/|_____|_____\___/
'
);
?>

<header class="apollo-header" data-tooltip="<?php esc_attr_e( 'Cabeçalho Apollo', 'apollo-social' ); ?>">
	<!-- Matrix ASCII Art oculto -->
	<div class="logo-mark" data-tooltip="<?php esc_attr_e( 'Logotipo Apollo::Rio', 'apollo-social' ); ?>">
		<div class="logo-icon" data-tooltip="<?php esc_attr_e( 'Ícone Apollo', 'apollo-social' ); ?>"></div>
		<div class="logo-text">
			<span class="brand" data-tooltip="<?php esc_attr_e( 'Nome da marca', 'apollo-social' ); ?>"><?php echo esc_html( $brand_name ); ?></span>
			<span class="sub" data-tooltip="<?php esc_attr_e( 'Subtítulo', 'apollo-social' ); ?>"><?php echo esc_html( $brand_subtitle ); ?></span>
		</div>
	</div>
	<div class="coordinates" data-tooltip="<?php esc_attr_e( 'Coordenadas e timestamp', 'apollo-social' ); ?>">
		<span data-tooltip="<?php esc_attr_e( 'Localização', 'apollo-social' ); ?>"><?php echo esc_html( $coordinates ); ?></span>
		<span id="timestamp" data-tooltip="<?php esc_attr_e( 'Data e hora atual UTC', 'apollo-social' ); ?>">
			<?php echo esc_html( gmdate( 'Y-m-d H:i:s' ) . ' UTC' ); ?>
		</span>
	</div>
</header>
