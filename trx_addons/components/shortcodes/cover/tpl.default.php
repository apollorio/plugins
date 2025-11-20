<?php
/**
 * The style "default" of the Cover link
 *
 * @package ThemeREX Addons
 * @since v1.86.0
 */

$args = get_query_var('trx_addons_args_sc_cover');

?><a href="<?php echo empty( $args['url'] ) ? '#' : esc_url( $args['url'] ); ?>"
	class="sc_cover sc_cover_<?php echo esc_attr($args['type']); ?>"
	data-place="<?php echo esc_attr($args['place']); ?>"<?php
	echo trx_addons_get_link_attributes( $args, 'url' );
	if ( ! empty($args['css']) ) echo ' style="' . esc_attr($args['css']) . '"';
	trx_addons_sc_show_attributes('sc_cover', $args, 'sc_item_wrapper');
?>></a>