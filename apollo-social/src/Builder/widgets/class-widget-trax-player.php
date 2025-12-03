<?php
/**
 * Apollo Widget: Trax Player
 *
 * Embeds SoundCloud or Spotify player.
 *
 * Pattern: WOW SoundCloud addon (soundcloud.php)
 * Uses iframe embed for both services.
 *
 * @package Apollo_Social
 * @since 1.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Apollo_Widget_Trax_Player
 */
class Apollo_Widget_Trax_Player extends Apollo_Widget_Base {

	public function get_name() {
		return 'trax-player';
	}

	public function get_title() {
		return __( 'Trax Player', 'apollo-social' );
	}

	public function get_icon() {
		return 'dashicons-format-audio';
	}

	public function get_description() {
		return __( 'Play your favorite tracks from SoundCloud or Spotify.', 'apollo-social' );
	}

	public function get_tooltip() {
		return __( 'Add a SoundCloud or Spotify URL to embed a player. The URL is saved in your home settings.', 'apollo-social' );
	}

	public function get_default_width() {
		return 300;
	}

	public function get_default_height() {
		return 160;
	}

	/**
	 * Settings
	 * Pattern: WOW SoundCloud addon settings
	 * Tooltip: Controls for autoplay, visual player, etc.
	 */
	public function get_settings() {
		return array(
			'url'          => $this->field(
				'text',
				__( 'SoundCloud/Spotify URL', 'apollo-social' ),
				'',
				array(
					'description' => __( 'Paste a SoundCloud track/playlist or Spotify URL', 'apollo-social' ),
				)
			),
			'autoplay'     => $this->field( 'switch', __( 'Auto Play', 'apollo-social' ), false ),
			'show_artwork' => $this->field( 'switch', __( 'Show Artwork', 'apollo-social' ), true ),
			'color'        => $this->field( 'color', __( 'Player Color', 'apollo-social' ), '#ff5500' ),
		);
	}

	/**
	 * Render widget
	 *
	 * Pattern: WOW soundcloud.php render()
	 * Data source: _apollo_trax_url post meta or widget config
	 */
	public function render( $data ) {
		$settings = $data['settings'] ?? array();
		$post_id  = $data['post_id'] ?? 0;

		// Get URL from settings or post meta
		$url = $settings['url'] ?? '';

		if ( empty( $url ) && $post_id ) {
			$url = get_post_meta( $post_id, APOLLO_BUILDER_META_TRAX, true );
		}

		if ( empty( $url ) ) {
			return '<div class="apollo-widget-trax-player apollo-widget-empty">'
				. '<span class="dashicons dashicons-format-audio"></span> '
				. __( 'No track configured', 'apollo-social' )
				. '</div>';
		}

		$autoplay     = ! empty( $settings['autoplay'] ) ? 'true' : 'false';
		$show_artwork = ! empty( $settings['show_artwork'] ) ? 'true' : 'false';
		$color        = sanitize_hex_color( $settings['color'] ?? '#ff5500' );
		$color_clean  = ltrim( $color, '#' );

		ob_start();
		?>
		<div class="apollo-widget-trax-player">
			<?php if ( strpos( $url, 'soundcloud.com' ) !== false ) : ?>
				<?php
				// Pattern: WOW SoundCloud iframe params
				$params = http_build_query(
					array(
						'url'           => $url,
						'auto_play'     => $autoplay,
						'color'         => $color_clean,
						'show_artwork'  => $show_artwork,
						'show_user'     => 'true',
						'show_comments' => 'false',
						'show_reposts'  => 'false',
					)
				);
				?>
				<iframe 
					class="trax-soundcloud"
					width="100%" 
					height="<?php echo absint( $data['height'] ?? 160 ) - 20; ?>" 
					scrolling="no" 
					frameborder="no" 
					allow="autoplay"
					src="https://w.soundcloud.com/player/?<?php echo esc_attr( $params ); ?>"
				></iframe>
				
			<?php elseif ( strpos( $url, 'spotify.com' ) !== false ) : ?>
				<?php
				// Convert Spotify URL to embed URL
				// https://open.spotify.com/track/xxx -> https://open.spotify.com/embed/track/xxx
				$embed_url = preg_replace(
					'#^(https?://open\.spotify\.com)/(track|album|playlist|episode|show)/(.+)$#',
					'$1/embed/$2/$3',
					$url
				);
				// Remove query string
				$embed_url = strtok( $embed_url, '?' );
				?>
				<iframe 
					class="trax-spotify"
					src="<?php echo esc_url( $embed_url ); ?>"
					width="100%" 
					height="<?php echo absint( $data['height'] ?? 160 ) - 20; ?>" 
					frameborder="0" 
					allowtransparency="true" 
					allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
					loading="lazy"
				></iframe>
				
			<?php else : ?>
				<div class="trax-unsupported">
					<?php _e( 'Unsupported URL. Use SoundCloud or Spotify.', 'apollo-social' ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Editor template
	 */
	public function get_editor_template() {
		return '
        <div class="apollo-widget-trax-player">
            <# if (data.url) { #>
                <div class="trax-preview">
                    <span class="dashicons dashicons-format-audio"></span>
                    <span class="trax-url">{{data.url}}</span>
                </div>
            <# } else { #>
                <div class="trax-placeholder">
                    <span class="dashicons dashicons-format-audio"></span>
                    ' . __( 'Configure Trax URL', 'apollo-social' ) . '
                </div>
            <# } #>
        </div>
        ';
	}
}

