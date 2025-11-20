<#
/**
 * Template to represent shortcode as Widget in the Elementor preview area
 *
 * Written as a Backbone JavaScript template and used to generate the live preview.
 *
 * @package ThemeREX Addons
 * @since v1.86.0
 */
#><a href="{{ settings.url.url }}"
	class="sc_cover sc_cover_{{ settings.type }}"
	data-place="{{ settings.place }}"<#
	print( trx_addons_get_link_attributes( settings.url ) );
	if (settings.css) print(' style="' + settings.css + '"');
#>></a>
