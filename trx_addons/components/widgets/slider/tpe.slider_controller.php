<?php
/**
 * Template to represent shortcode as a widget in the Elementor preview area
 *
 * Written as a Backbone JavaScript template and using to generate the live preview in the Elementor's Editor
 *
 * @package ThemeREX Addons
 * @since v1.6.41
 */

extract(get_query_var('trx_addons_args_widget_slider_controller'));
?><#
var id = settings._element_id ? settings._element_id + '_sc' : 'sc_slider_controller_'+(''+Math.random()).replace('.', '');
if (settings.slider_id !='') {
	var styles_allowed = settings.arrows_icon_prev || settings.arrows_icon_next;
	#><div id="{{ id }}"
			class="sc_slider_controller
							 sc_slider_controller_{{ settings.controller_style }}
							 sc_slider_controller_{{ settings.direction }}
							 sc_slider_controller_height_<# print(settings.height.size > 0 ? 'fixed' : 'auto'); #>"
			data-slider-id="{{ settings.slider_id }}"
			data-style="{{ settings.controller_style }}"
			data-controls="<# print(settings.controls > 0 ? 1 : 0); #>"
			data-controls-icon="{{ settings.icon }}"
			<#
			if ( styles_allowed ) {
				#>
				data-controls-icon-prev="<# print( trx_addons_esc_attr( trx_addons_elm_render_icon( view, settings.arrows_icon_prev, 'eicon-' + ( settings.direction == 'horizontal' ? 'chevron-left' : 'sort-up' ) ) ) ); #>"
				data-controls-icon-next="<# print( trx_addons_esc_attr( trx_addons_elm_render_icon( view, settings.arrows_icon_next, 'eicon-' + ( settings.direction == 'horizontal' ? 'chevron-right' : 'sort-down' ) ) ) ); #>"
				<#
			}
			#>
			data-interval="{{ settings.interval.size }}"
			data-effect="{{ settings.effect }}"
			data-direction="{{ settings.direction }}"
			data-slides-per-view="{{ settings.slides_per_view.size }}"
			data-slides-space="{{ settings.slides_space.size }}"<#
			if (settings.height.size > 0 && settings.direction == 'horizontal') {
				print(' style="--sc-slider-controller-height:' + settings.height.size + settings.height.unit + '"');
			}
	#>></div><#
}
#>