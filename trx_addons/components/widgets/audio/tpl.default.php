<?php
/**
 * The style "default" of the Widget "Audio"
 *
 * @package ThemeREX Addons
 * @since v1.6.10
 */

$args = get_query_var( 'trx_addons_args_widget_audio' );
extract( $args );

/* Before widget (defined by themes) */
trx_addons_show_layout( $before_widget );

/* Widget title if one was input (before and after defined by themes) */
trx_addons_show_layout( $title, $before_title, $after_title );

/* Widget subtitle */
if ( ! empty( $subtitle ) ) {
	echo '<div class="widget_subtitle">' . esc_html( $subtitle ) . '</div>';
}

/* Widget body */
if ( is_array( $media ) && count( $media ) > 0 ) {

	$wrap_class = ( '1' !== $track_time ? ' hide_time' : '' )
				. ( '1' !== $track_scroll ? ' hide_scroll' : '' )
				. ( '1' !== $track_volume ? ' hide_volume' : '' )
				. ( is_array( $media ) && count( $media ) > 1
					? ' list' . ( ! empty( $playlist ) && '1' === $playlist ? ' with_playlist' : '' )
					: '' )
				. ( 'left' === $cover_pos ? ' cover_left' : ( 'right' === $cover_pos ? ' cover_right' : ' cover_bg' ) );

	?><div class="trx_addons_audio_wrap<?php echo esc_attr( $wrap_class ); ?>"><?php
		if ( $cover_pos == 'left' || $cover_pos == 'right' ) {
			?><div class="trx_addons_audio_cover"><?php
				$num = 0;
				foreach ( $media as $item ) {
					$num++;
					$cover = ! empty( $item['cover'] ) ? $item['cover'] : trx_addons_get_no_image( 'full' );
					?><div class="trx_addons_audio_cover_item<?php if ( $num == 1 ) echo ' current'; ?>" style="background-image:url(<?php echo esc_url( $cover ); ?>);"></div><?php
				}
			?></div><?php
			?><div class="trx_addons_audio_list_wrap"><?php
		}
		?>
		<div class="trx_addons_audio_list">
			<?php
			$button_icons = trx_addons_mediaplayer_icons_selector_allowed() ? trx_addons_mediaplayer_add_button_icons( $args ) : '';
			$num = 0;
			foreach ( $media as $item ) {
				$num++;
				$item['url']         = array_key_exists( 'audio', $item ) && ! empty( $item['audio']['url'] )
										? $item['audio']['url']
										: ( array_key_exists( 'url', $item ) && ! empty( $item['url'] )
											? $item['url']
											: ''
											);
				$item['embed']       = array_key_exists( 'embed', $item ) && ! empty( $item['embed'] ) ? $item['embed'] : '';
				$item['caption']     = array_key_exists( 'caption', $item ) && ! empty( $item['caption'] ) ? $item['caption'] : '';
				$item['author']      = array_key_exists( 'author', $item ) && ! empty( $item['author'] ) ? $item['author'] : '';
				$item['description'] = array_key_exists( 'description', $item ) && ! empty( $item['description'] ) ? $item['description'] : '';
				$item['cover']       = array_key_exists( 'cover', $item ) && ! empty( $item['cover'] ) ? $item['cover'] : '';
				if ( in_array( $cover_pos, array( 'left', 'right' ) ) ) {
					$item['cover'] = '';
				}
				?>
					<div class="trx_addons_audio_player<?php
						if ( $num == 1 ) echo ' current';
						echo ! empty( $item['cover'] ) ? ' with_cover' : ' without_cover';
						?>"
						<?php
						if ( ! empty( $item['cover'] ) ) {
							echo ' style="background-image:url(' . esc_url( $item['cover'] ) . ');"';
						}
					?>>
						<div class="trx_addons_audio_player_wrap">
						<?php

						if ( ! empty( $item['author'] ) || ! empty( $item['caption'] ) ) {
							?>
							<div class="audio_info">
								<?php
								if ( ! empty( $now_show ) && '1' == $now_show && '#' !== $now_text && count( $media ) > 1 ) {
									$now_tag = ! empty( $args['now_tag'] ) ? $args['now_tag'] : 'h5';
									echo '<' . esc_html( $now_tag ) . ' class="audio_now_playing">' . ( ! empty( $now_text ) ? esc_html( $now_text ) : esc_html__( 'Now Playing', 'trx_addons' ) ) . '</' . esc_html( $now_tag ) . '>';
								}
								if ( ! empty( $item['author'] ) ) {
									$author_tag = ! empty( $args['author_tag'] ) ? $args['author_tag'] : 'h6';
									?>
									<<?php echo esc_html( $author_tag ); ?> class="audio_author"><?php echo esc_html( $item['author'] ); ?></<?php echo esc_html( $author_tag ); ?>>
									<?php
								}
								if ( ! empty( $item['caption'] ) ) {
									$caption_tag = ! empty( $args['caption_tag'] ) ? $args['caption_tag'] : 'h5';
									?>
									<<?php echo esc_html( $caption_tag ); ?> class="audio_caption"><?php echo esc_html( $item['caption'] ); ?></<?php echo esc_html( $caption_tag ); ?>>
									<?php
								}
								if ( ! empty( $item['description'] ) ) {
									?>
									<div class="audio_description"><?php echo esc_html( $item['description'] ); ?></div>
									<?php
								}
								?>
							</div>
							<?php
						}
						?>
						<div class="audio_frame audio_<?php echo esc_attr( $item['embed'] ? 'embed' : 'local' ); ?>">
						<?php
						if ( ! empty( $item['url'] ) ) {
							$default_types = wp_get_audio_extensions();
							$type = wp_check_filetype( $item['url'], wp_get_mime_types() );
							$need_replace = false;
							if ( ! in_array( strtolower( $type['ext'] ), $default_types ) ) {
								$need_replace = true;
								$item['url_orig'] = $item['url'];
								$item['url'] .= '.mp3';
							}
							$output = do_shortcode( '[audio src="' . trim( $item['url'] ) . '"]' );
							if ( empty( $output ) ) {
								$need_replace = false;
								$output = '<audio src="' . esc_url( $item['url'] ) . '">'
											. '<source type="audio/mpeg" src="' . esc_url( $item['url'] ) . '">'
										. '</audio>';
							}
							if ( $need_replace ) {
								$output = str_replace( $item['url'], $item['url_orig'], $output );
							}
							$output = str_replace(
											'<audio ',
											'<audio'
												. ' data-src="' . esc_url($need_replace ? $item['url_orig'] : $item['url']) . '"'
												. ' data-cover="' . esc_url($item['cover']) . '"'
												. ' data-caption="' . esc_attr($item['caption']) . '"'
												. ' data-author="' . esc_attr($item['author']) . '"'
												. $button_icons
												. ' ',
											$output );
							trx_addons_show_layout( $output );
						} else if ( ! empty( $item['embed'] ) ) {
							trx_addons_show_layout( $item['embed'] );
						}
						?>
						</div>
					</div>
				</div>
				<?php
			}
			?>
		</div>
		<?php
		if ( count( $media ) > 1 ) {
			if ( ! empty( $args['playlist'] ) && '1' === $args['playlist'] ) {
				?>
				<div class="trx_addons_audio_playlist">
				<?php
				$title_tag = ! empty( $args['playlist_title_tag'] ) ? $args['playlist_title_tag'] : 'span';
				$num = 0;
				foreach ( $media as $item ) {
					$num++;
					?><div class="trx_addons_audio_playlist_item<?php if ( $num == 1 ) echo ' current'; ?>">
						<<?php echo esc_html( $title_tag ); ?> class="trx_addons_audio_playlist_item_title"><?php
							if ( ! empty( $args['playlist_numbers'] ) && '1' === $args['playlist_numbers'] ) {
								?><span class="trx_addons_audio_playlist_item_title_number"><?php echo (int)$num; ?>.</span><?php
							}
							?><span class="trx_addons_audio_playlist_item_title_text"><?php
								echo ! empty( $item['caption'] ) ? esc_html( $item['caption'] ) : '';
							?></span><?php
						?></<?php echo esc_html( $title_tag ); ?>>
						<span class="trx_addons_audio_playlist_item_meta"><?php
							$duration = '';
							if ( empty( $item['audio']['id'] ) && ! empty( $item['audio']['url'] ) && trx_addons_is_from_uploads( $item['audio']['url'] ) ) {
								$item['audio']['id'] = trx_addons_attachment_url_to_postid( $item['audio']['url'] );
							}
							if ( ! empty( $item['audio']['id'] ) ) {
								$meta = wp_get_attachment_metadata( $item['audio']['id'] );
								if ( ! empty( $meta['length_formatted'] ) ) {
									$duration = $meta['length_formatted'];
								} else if ( ! empty( $meta['length'] ) && is_numeric( $meta['length'] ) ) {
									$duration = gmdate( 'i:s', $meta['length'] );
								}
							}
							echo esc_html( $duration );
						?></span>
					</div>
					<?php
				}
				?>
				</div>
				<?php
			}
			if ( '1' === $args['prev_btn'] || '1' === $args['next_btn'] ) {
				echo '<div class="trx_addons_audio_navigation">'
						. ( '1' === $args['prev_btn']
							? '<span class="nav_btn prev">'
								. '<span class="' . ( ! empty( $args['prev_icon'] ) && ! trx_addons_is_off( $args['prev_icon'] ) ? $args['prev_icon'] : 'trx_addons_icon-slider-left' ) . '"></span>'
								. esc_html( $prev_text )
								. '</span>'
							: ''
							)
						. ( '1' === $args['next_btn']
							? '<span class="nav_btn next">'
								. esc_html( $next_text )
								. '<span class="' . ( ! empty( $args['next_icon'] ) && ! trx_addons_is_off( $args['next_icon'] ) ? $args['next_icon'] : 'trx_addons_icon-slider-right' ) . '"></span>'
								. '</span>'
							: ''
							)
					. '</div>';
			}
		}
		if ( $cover_pos == 'left' || $cover_pos == 'right' ) {
			?></div><?php
		}
		?>
	</div><?php
}

/* After widget (defined by themes) */
trx_addons_show_layout( $after_widget );
