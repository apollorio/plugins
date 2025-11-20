<?php
/**
 * Template to represent shortcode as a widget in the Elementor preview area
 *
 * Written as a Backbone JavaScript template and using to generate the live preview in the Elementor's Editor
 *
 * @package ThemeREX Addons
 * @since v1.6.41
 */

extract( get_query_var( 'trx_addons_args_widget_audio' ) );

extract( trx_addons_prepare_widgets_args( trx_addons_generate_id( 'widget_audio_' ), 'widget_audio' ) );

/* Before widget (defined by themes) */
trx_addons_show_layout( $before_widget );

/* Widget title if one was input (before and after defined by themes) */
?><#
if (settings.title != '') {
	#><?php trx_addons_show_layout( $before_title ); ?><#
	print(settings.title);
	#><?php trx_addons_show_layout( $after_title ); ?><#
}

if ( settings.subtitle != '' ) { 
	#><div class="widget_subtitle"><# print(settings.subtitle); #></div><#
}

if ( settings.media.length > 0 ) {
	var wrap_track_time = settings.track_time != '1' ? ' hide_time' : '';
	var wrap_track_scroll = settings.track_scroll != '1' ? ' hide_scroll' : '';
	var wrap_track_volume = settings.track_volume != '1' ? ' hide_volume' : '';
	var wrap_list = settings.media.length > 1 ? ' list' : '';
	var wrap_playlist = settings.media.length > 1 && settings.playlist == '1' ? ' with_playlist' : '';
	var wrap_cover_pos = settings.cover_pos == 'left' ? ' cover_left' : ( settings.cover_pos == 'right' ? ' cover_right' : ' cover_bg');
	var wrap_class = wrap_track_time + wrap_track_scroll + wrap_track_volume + wrap_list + wrap_playlist + wrap_cover_pos;
	var num = 0;
	#><div class="trx_addons_audio_wrap{{ wrap_class }}"><#
		if ( settings.cover_pos == 'left' || settings.cover_pos == 'right' ) {
			#><div class="trx_addons_audio_cover"><#
				_.each( settings.media, function( item ) {
					num++;
					var cover = item.cover.url != '' ? item.cover.url : '<?php echo esc_url( trx_addons_get_no_image( 'full' ) ); ?>';
					#><div class="trx_addons_audio_cover_item<# if ( num == 1 ) print( ' current' ); #>" style="background-image:url({{ cover }});"></div><#
				} );
			#></div><#
			#><div class="trx_addons_audio_list_wrap"><#
		}
		#><div class="trx_addons_audio_list"><#
			var button_icons = <?php echo (int)trx_addons_mediaplayer_icons_selector_allowed(); ?> ? trx_addons_mediaplayer_add_button_icons( view, settings ) : '';
			num = 0;
			_.each( settings.media, function( item ) {
				num++;
				var cover = settings.cover_pos != 'left' && settings.cover_pos != 'right' && item.cover.url != '' ? item.cover.url : '';
				#><div class="trx_addons_audio_player<# if ( num == 1 ) print( ' current' ); #> <# print( cover ? 'with_cover' : 'without_cover'); #>"<#
					if ( cover ) {
						print( ' style="background-image:url(' + cover + ');"' );
					}
				#>>
					<div class="trx_addons_audio_player_wrap"><#
						if (item.author != '' || item.caption != '') {
							#><div class="audio_info"><#
								var now_text = settings.now_text !== "" ? settings.now_text : "<?php esc_html_e( 'Now Playing', 'trx_addons' ); ?>";
								if ( ( settings.now_show === undefined || settings.now_show == '1' ) && now_text != "#" && settings.media.length > 1 ) {
									var now_tag = elementor.helpers.validateHTMLTag( settings.now_tag || 'h5' );
									#><{{ now_tag }} class="audio_now_playing">{{ now_text }}</{{ now_tag }}><#
								}
								if (item.author != '') {
									var author_tag = elementor.helpers.validateHTMLTag( settings.author_tag || 'h6' );
									#><{{ author_tag }} class="audio_author">{{ item.author }}</{{ author_tag }}><#
								}
								if (item.caption != '') {
									var caption_tag = elementor.helpers.validateHTMLTag( settings.caption_tag || 'h5' );
									#><{{ caption_tag }} class="audio_caption">{{ item.caption }}</{{ caption_tag }}><#
								}
								if (item.description != '') {
									#><div class="audio_description">{{ item.description }}</div><#
								}
							#></div><#
						}

						#><div class="audio_frame audio_<# print(item.embed != '' ? 'embed' : 'local'); #>"><#
							var url = item.audio && item.audio.url != '' ? item.audio.url : item.url;
							if ( url != '' ) {
								#><audio src="{{ url }}" {{ button_icons }}>
									<source type="audio/mpeg" src="{{ url }}">
								</audio><#
							} else if ( item.embed != '' ) {
								print(item.embed);
							}
						#></div>
					</div>
				</div><#
			} );
		#></div><#
		if (settings.media.length > 1) {
			if ( settings.playlist == '1' ) {
				#><div class="trx_addons_audio_playlist"><#
					var title_tag = elementor.helpers.validateHTMLTag( settings.playlist_title_tag || 'span' );
					num = 0;
					_.each( settings.media, function( item ) {
						num++;
						#><div class="trx_addons_audio_playlist_item<# if ( num == 1 ) print( ' current' ); #>">
							<{{ title_tag }} class="trx_addons_audio_playlist_item_title"><#
								if ( settings.playlist_numbers == '1' ) {
									#><span class="trx_addons_audio_playlist_item_title_number">{{ num }}.</span><#
								}
								#><span class="trx_addons_audio_playlist_item_title_text">{{ item.caption }}</span><#
							#></{{ title_tag }}>
							<span class="trx_addons_audio_playlist_item_meta">00:00</span>
						</div><#
					} );
				#></div><#
			}
			if ( '1' === settings.prev_btn || '1' === settings.next_btn ) {
				#><div class="trx_addons_audio_navigation"><#
					if ( '1' === settings.prev_btn ) {
						#><span class="nav_btn prev">
							<span class="<# print( settings.prev_icon && ! trx_addons_is_off( settings.prev_icon ) ? settings.prev_icon : 'trx_addons_icon-slider-left' ); #>"></span>
							{{ settings.prev_text }}
						</span><#
					}
					if ( '1' === settings.next_btn ) {
						#><span class="nav_btn next">
							{{ settings.next_text }}
							<span class="<# print( settings.next_icon && ! trx_addons_is_off( settings.next_icon ) ? settings.next_icon : 'trx_addons_icon-slider-right' ); #>"></span>
						</span><#
					}
				#></div><#
			}
		} 
		if ( settings.cover_pos == 'left' || settings.cover_pos == 'right' ) {
			#></div><#
		}
		#>
	</div><#
}
#><?php

/* After widget (defined by themes) */
trx_addons_show_layout( $after_widget );
