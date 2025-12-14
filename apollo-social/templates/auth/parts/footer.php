<?php
/**
 * ================================================================================
 * APOLLO AUTH - Footer Template Part
 * ================================================================================
 * Displays the footer with copyright and node information.
 *
 * @package Apollo_Social
 * @since 1.0.0
 *
 * PLACEHOLDERS:
 * - {{year}} - Current year
 * - {{node_name}} - Node identifier
 * - {{version}} - Plugin version
 * ================================================================================
 */

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

$current_year = gmdate('Y');
$node_name    = apply_filters('apollo_auth_node_name', 'Rio de Janeiro');
$version      = defined('APOLLO_SOCIAL_VERSION') ? APOLLO_SOCIAL_VERSION : '1.0.0';
?>

<footer data-tooltip="<?php esc_attr_e('Rodapé Apollo', 'apollo-social'); ?>">
	<p data-tooltip="<?php esc_attr_e('Informações do nó', 'apollo-social'); ?>">
		Apollo::Rio Node • <?php echo esc_html($node_name); ?> • v<?php echo esc_html($version); ?>
	</p>
	<p data-tooltip="<?php esc_attr_e('Copyright', 'apollo-social'); ?>">
		© <?php echo esc_html($current_year); ?> Apollo::Rio • 
		<?php esc_html_e('Todos os direitos reservados', 'apollo-social'); ?>
	</p>
</footer>
