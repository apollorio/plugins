<?php
/**
 * Apollo Builder Pre-set Themes
 *
 * Provides 10 pre-set visual themes for user profile pages.
 * Themes include color schemes, gradients, fonts, and CSS styles.
 *
 * Themes:
 * 1. Sunset - Warm orange/pink gradient
 * 2. Tropical - Vibrant greens and teals
 * 3. Dark Futuristic - Clean dark mode with neon accents
 * 4. Light Space - Soft whites and purples
 * 5. Retro Wave - 80s synthwave pink/purple
 * 6. Matrix - Green terminal aesthetic
 * 7. Old Paper/Bible - Sepia parchment style
 * 8. Tropical 2 - Ocean blues and coral
 * 9. Sunset Clean - Minimal orange theme
 * 10. Dark Berlin - Industrial dark with red accents
 *
 * @package Apollo_Social
 * @since 1.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Apollo_Builder_Themes
 *
 * Tooltip: Manages pre-set visual themes for Apollo Builder homes.
 */
class Apollo_Builder_Themes {

	/**
	 * Theme meta key
	 */
	public const META_THEME = '_apollo_builder_theme';

	/**
	 * Custom CSS meta key
	 */
	public const META_CUSTOM_CSS = '_apollo_builder_custom_css';

	/**
	 * Initialize hooks
	 */
	public static function init() {
		// AJAX handlers.
		add_action( 'wp_ajax_apollo_builder_set_theme', array( __CLASS__, 'ajax_set_theme' ) );
		add_action( 'wp_ajax_apollo_builder_get_themes', array( __CLASS__, 'ajax_get_themes' ) );

		// Inject theme CSS on frontend.
		add_action( 'wp_head', array( __CLASS__, 'inject_theme_css' ), 99 );
	}

	/**
	 * Get all pre-set themes
	 *
	 * Tooltip: Returns array of all available themes with CSS and metadata.
	 *
	 * @return array
	 */
	public static function get_themes() {
		$themes = array(
			'sunset'          => array(
				'id'          => 'sunset',
				'label'       => __( 'Sunset', 'apollo-social' ),
				'description' => __( 'Warm orange and pink gradient', 'apollo-social' ),
				'preview'     => 'linear-gradient(135deg, #ff6b35 0%, #f7931e 50%, #ff4757 100%)',
				'colors'      => array(
					'primary'    => '#ff6b35',
					'secondary'  => '#f7931e',
					'accent'     => '#ff4757',
					'background' => '#fff5f0',
					'text'       => '#333333',
					'text_light' => '#666666',
				),
				'css'         => '
					.apollo-home-container {
						background: linear-gradient(135deg, #fff5f0 0%, #ffe8dc 100%);
					}
					.apollo-widget {
						background: rgba(255,255,255,0.95);
						border: 1px solid rgba(255,107,53,0.2);
						box-shadow: 0 4px 20px rgba(255,107,53,0.15);
					}
					.apollo-widget:hover {
						box-shadow: 0 8px 30px rgba(255,107,53,0.25);
					}
					.widget-title {
						color: #ff6b35;
						border-bottom: 2px solid #ff6b35;
					}
					.apollo-home-header {
						background: linear-gradient(135deg, #ff6b35 0%, #ff4757 100%);
						color: #fff;
					}
					.btn-primary {
						background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
						color: #fff;
					}
				',
			),

			'tropical'        => array(
				'id'          => 'tropical',
				'label'       => __( 'Tropical', 'apollo-social' ),
				'description' => __( 'Vibrant greens and teals', 'apollo-social' ),
				'preview'     => 'linear-gradient(135deg, #00b894 0%, #00cec9 50%, #55efc4 100%)',
				'colors'      => array(
					'primary'    => '#00b894',
					'secondary'  => '#00cec9',
					'accent'     => '#55efc4',
					'background' => '#f0fff4',
					'text'       => '#2d3436',
					'text_light' => '#636e72',
				),
				'css'         => '
					.apollo-home-container {
						background: linear-gradient(135deg, #f0fff4 0%, #e0fff4 100%);
					}
					.apollo-widget {
						background: rgba(255,255,255,0.95);
						border: 1px solid rgba(0,184,148,0.2);
						box-shadow: 0 4px 20px rgba(0,184,148,0.15);
						border-radius: 12px;
					}
					.widget-title {
						color: #00b894;
						border-bottom: 2px solid #00cec9;
					}
					.apollo-home-header {
						background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
						color: #fff;
					}
					.btn-primary {
						background: linear-gradient(135deg, #00b894 0%, #55efc4 100%);
						color: #fff;
					}
				',
			),

			'dark-futuristic' => array(
				'id'          => 'dark-futuristic',
				'label'       => __( 'Dark Futuristic', 'apollo-social' ),
				'description' => __( 'Clean dark mode with neon accents', 'apollo-social' ),
				'preview'     => 'linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%)',
				'colors'      => array(
					'primary'    => '#00d9ff',
					'secondary'  => '#7b2cbf',
					'accent'     => '#e94560',
					'background' => '#1a1a2e',
					'text'       => '#f0f0f0',
					'text_light' => '#b0b0b0',
				),
				'css'         => '
					.apollo-home-container {
						background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
						color: #f0f0f0;
					}
					.apollo-widget {
						background: rgba(22,33,62,0.95);
						border: 1px solid rgba(0,217,255,0.3);
						box-shadow: 0 4px 20px rgba(0,217,255,0.1);
						border-radius: 8px;
					}
					.apollo-widget:hover {
						border-color: rgba(0,217,255,0.6);
						box-shadow: 0 0 30px rgba(0,217,255,0.2);
					}
					.widget-title {
						color: #00d9ff;
						text-shadow: 0 0 10px rgba(0,217,255,0.5);
					}
					.apollo-home-header {
						background: linear-gradient(135deg, #0f3460 0%, #16213e 100%);
						border-bottom: 2px solid #00d9ff;
					}
					.btn-primary {
						background: linear-gradient(135deg, #00d9ff 0%, #7b2cbf 100%);
						color: #fff;
					}
				',
			),

			'light-space'     => array(
				'id'          => 'light-space',
				'label'       => __( 'Light Space', 'apollo-social' ),
				'description' => __( 'Soft whites and cosmic purples', 'apollo-social' ),
				'preview'     => 'linear-gradient(135deg, #f8f9ff 0%, #e8e0ff 50%, #d4c5ff 100%)',
				'colors'      => array(
					'primary'    => '#6c5ce7',
					'secondary'  => '#a29bfe',
					'accent'     => '#fd79a8',
					'background' => '#f8f9ff',
					'text'       => '#2d3436',
					'text_light' => '#636e72',
				),
				'css'         => '
					.apollo-home-container {
						background: linear-gradient(135deg, #f8f9ff 0%, #e8e0ff 100%);
					}
					.apollo-widget {
						background: rgba(255,255,255,0.98);
						border: 1px solid rgba(108,92,231,0.15);
						box-shadow: 0 4px 25px rgba(108,92,231,0.1);
						border-radius: 16px;
					}
					.widget-title {
						color: #6c5ce7;
						font-weight: 500;
					}
					.apollo-home-header {
						background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
						color: #fff;
					}
					.btn-primary {
						background: linear-gradient(135deg, #6c5ce7 0%, #fd79a8 100%);
						color: #fff;
					}
				',
			),

			'retro-wave'      => array(
				'id'          => 'retro-wave',
				'label'       => __( 'Retro Wave', 'apollo-social' ),
				'description' => __( '80s synthwave pink and purple', 'apollo-social' ),
				'preview'     => 'linear-gradient(135deg, #12062e 0%, #2d1b69 50%, #ff0080 100%)',
				'colors'      => array(
					'primary'    => '#ff0080',
					'secondary'  => '#00ffff',
					'accent'     => '#ff00ff',
					'background' => '#12062e',
					'text'       => '#ffffff',
					'text_light' => '#e0e0e0',
				),
				'css'         => '
					.apollo-home-container {
						background: linear-gradient(135deg, #12062e 0%, #2d1b69 100%);
						color: #fff;
					}
					.apollo-widget {
						background: rgba(45,27,105,0.9);
						border: 2px solid #ff0080;
						box-shadow: 0 0 20px rgba(255,0,128,0.4), inset 0 0 20px rgba(255,0,255,0.1);
						border-radius: 4px;
					}
					.apollo-widget:hover {
						box-shadow: 0 0 40px rgba(255,0,128,0.6), 0 0 60px rgba(0,255,255,0.3);
					}
					.widget-title {
						color: #00ffff;
						text-shadow: 0 0 10px #00ffff, 0 0 20px #00ffff;
						font-family: "Orbitron", sans-serif;
					}
					.apollo-home-header {
						background: linear-gradient(90deg, #ff0080 0%, #00ffff 100%);
						color: #fff;
						text-shadow: 0 0 10px #000;
					}
					.btn-primary {
						background: linear-gradient(90deg, #ff0080 0%, #ff00ff 100%);
						border: 1px solid #00ffff;
						color: #fff;
						text-shadow: 0 0 5px #000;
					}
				',
			),

			'matrix'          => array(
				'id'          => 'matrix',
				'label'       => __( 'Matrix', 'apollo-social' ),
				'description' => __( 'Green terminal aesthetic', 'apollo-social' ),
				'preview'     => 'linear-gradient(180deg, #000000 0%, #001100 50%, #003300 100%)',
				'colors'      => array(
					'primary'    => '#00ff00',
					'secondary'  => '#00cc00',
					'accent'     => '#00ff66',
					'background' => '#000000',
					'text'       => '#00ff00',
					'text_light' => '#00cc00',
				),
				'css'         => '
					.apollo-home-container {
						background: #000000;
						color: #00ff00;
						font-family: "Courier New", monospace;
					}
					.apollo-widget {
						background: rgba(0,17,0,0.95);
						border: 1px solid #00ff00;
						box-shadow: 0 0 10px rgba(0,255,0,0.3);
						border-radius: 0;
					}
					.apollo-widget::before {
						content: "> ";
						color: #00ff00;
					}
					.widget-title {
						color: #00ff00;
						text-transform: uppercase;
						letter-spacing: 2px;
						font-size: 0.9em;
					}
					.apollo-home-header {
						background: #001100;
						border-bottom: 1px solid #00ff00;
						color: #00ff00;
					}
					.btn-primary {
						background: transparent;
						border: 1px solid #00ff00;
						color: #00ff00;
					}
					.btn-primary:hover {
						background: #00ff00;
						color: #000;
					}
				',
			),

			'old-paper'       => array(
				'id'          => 'old-paper',
				'label'       => __( 'Old Paper', 'apollo-social' ),
				'description' => __( 'Vintage parchment style', 'apollo-social' ),
				'preview'     => 'linear-gradient(135deg, #f4e4bc 0%, #e8d5a3 50%, #d4c089 100%)',
				'colors'      => array(
					'primary'    => '#8b4513',
					'secondary'  => '#a0522d',
					'accent'     => '#cd853f',
					'background' => '#f4e4bc',
					'text'       => '#3d2914',
					'text_light' => '#5c4033',
				),
				'css'         => '
					.apollo-home-container {
						background: linear-gradient(135deg, #f4e4bc 0%, #e8d5a3 100%);
						background-image: url("data:image/svg+xml,%3Csvg width=\'100\' height=\'100\' viewBox=\'0 0 100 100\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cfilter id=\'noise\'%3E%3CfeTurbulence type=\'fractalNoise\' baseFrequency=\'0.8\' numOctaves=\'4\' stitchTiles=\'stitch\'/%3E%3C/filter%3E%3Crect width=\'100%25\' height=\'100%25\' filter=\'url(%23noise)\' opacity=\'0.05\'/%3E%3C/svg%3E");
					}
					.apollo-widget {
						background: rgba(244,228,188,0.9);
						border: 2px solid #d4c089;
						box-shadow: 3px 3px 10px rgba(0,0,0,0.1);
						border-radius: 2px;
						font-family: "Georgia", serif;
					}
					.widget-title {
						color: #8b4513;
						font-family: "Times New Roman", serif;
						font-style: italic;
						border-bottom: 1px solid #a0522d;
					}
					.apollo-home-header {
						background: linear-gradient(180deg, #d4c089 0%, #c9b37a 100%);
						color: #3d2914;
						border-bottom: 3px double #8b4513;
					}
					.btn-primary {
						background: #8b4513;
						color: #f4e4bc;
						border: none;
						font-family: "Georgia", serif;
					}
				',
			),

			'tropical-2'      => array(
				'id'          => 'tropical-2',
				'label'       => __( 'Tropical Ocean', 'apollo-social' ),
				'description' => __( 'Ocean blues and coral accents', 'apollo-social' ),
				'preview'     => 'linear-gradient(135deg, #0077b6 0%, #00b4d8 50%, #ff6b6b 100%)',
				'colors'      => array(
					'primary'    => '#0077b6',
					'secondary'  => '#00b4d8',
					'accent'     => '#ff6b6b',
					'background' => '#caf0f8',
					'text'       => '#03045e',
					'text_light' => '#023e8a',
				),
				'css'         => '
					.apollo-home-container {
						background: linear-gradient(180deg, #caf0f8 0%, #ade8f4 50%, #90e0ef 100%);
					}
					.apollo-widget {
						background: rgba(255,255,255,0.95);
						border: 1px solid rgba(0,180,216,0.3);
						box-shadow: 0 4px 20px rgba(0,119,182,0.15);
						border-radius: 20px;
					}
					.widget-title {
						color: #0077b6;
						background: linear-gradient(90deg, #0077b6, #00b4d8);
						-webkit-background-clip: text;
						-webkit-text-fill-color: transparent;
					}
					.apollo-home-header {
						background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
						color: #fff;
					}
					.btn-primary {
						background: linear-gradient(135deg, #ff6b6b 0%, #ee5a5a 100%);
						color: #fff;
						border-radius: 25px;
					}
				',
			),

			'sunset-clean'    => array(
				'id'          => 'sunset-clean',
				'label'       => __( 'Sunset Clean', 'apollo-social' ),
				'description' => __( 'Minimal warm orange theme', 'apollo-social' ),
				'preview'     => 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)',
				'colors'      => array(
					'primary'    => '#ff7e5f',
					'secondary'  => '#feb47b',
					'accent'     => '#ff6b6b',
					'background' => '#ffffff',
					'text'       => '#333333',
					'text_light' => '#666666',
				),
				'css'         => '
					.apollo-home-container {
						background: #ffffff;
					}
					.apollo-widget {
						background: #fff;
						border: none;
						box-shadow: 0 2px 10px rgba(255,126,95,0.1);
						border-radius: 8px;
					}
					.apollo-widget:hover {
						box-shadow: 0 4px 20px rgba(255,126,95,0.2);
					}
					.widget-title {
						color: #ff7e5f;
						font-weight: 600;
						border-bottom: 2px solid transparent;
						border-image: linear-gradient(90deg, #ff7e5f, #feb47b) 1;
					}
					.apollo-home-header {
						background: linear-gradient(90deg, #ff7e5f 0%, #feb47b 100%);
						color: #fff;
					}
					.btn-primary {
						background: linear-gradient(90deg, #ff7e5f 0%, #feb47b 100%);
						color: #fff;
						border: none;
					}
				',
			),

			'dark-berlin'     => array(
				'id'          => 'dark-berlin',
				'label'       => __( 'Dark Berlin', 'apollo-social' ),
				'description' => __( 'Industrial dark with red accents', 'apollo-social' ),
				'preview'     => 'linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 50%, #8b0000 100%)',
				'colors'      => array(
					'primary'    => '#e63946',
					'secondary'  => '#8b0000',
					'accent'     => '#ff6b6b',
					'background' => '#1a1a1a',
					'text'       => '#f5f5f5',
					'text_light' => '#b0b0b0',
				),
				'css'         => '
					.apollo-home-container {
						background: linear-gradient(180deg, #1a1a1a 0%, #2d2d2d 100%);
						color: #f5f5f5;
					}
					.apollo-widget {
						background: rgba(30,30,30,0.95);
						border: 1px solid #3d3d3d;
						box-shadow: 0 4px 20px rgba(0,0,0,0.5);
						border-radius: 4px;
					}
					.apollo-widget:hover {
						border-color: #e63946;
					}
					.widget-title {
						color: #e63946;
						text-transform: uppercase;
						letter-spacing: 1px;
						font-size: 0.85em;
						font-weight: 700;
					}
					.apollo-home-header {
						background: #1a1a1a;
						border-bottom: 3px solid #e63946;
						color: #fff;
					}
					.btn-primary {
						background: #e63946;
						color: #fff;
						border: none;
						text-transform: uppercase;
						letter-spacing: 1px;
					}
					.btn-primary:hover {
						background: #ff6b6b;
					}
				',
			),
		);

		/**
		 * Filter available themes.
		 * Allows other plugins/themes to add or modify themes.
		 *
		 * @param array $themes Array of theme definitions.
		 */
		return apply_filters( 'apollo_builder_themes', $themes );
	}

	/**
	 * Get a single theme by ID
	 *
	 * @param string $theme_id Theme ID.
	 * @return array|null Theme data or null.
	 */
	public static function get_theme( $theme_id ) {
		$themes = self::get_themes();
		return $themes[ $theme_id ] ?? null;
	}

	/**
	 * Get current theme for a post
	 *
	 * @param int $post_id Post ID.
	 * @return string Theme ID or empty.
	 */
	public static function get_post_theme( $post_id ) {
		return get_post_meta( $post_id, self::META_THEME, true ) ?: '';
	}

	/**
	 * Set theme for a post
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $theme_id Theme ID.
	 * @return bool Success.
	 */
	public static function set_post_theme( $post_id, $theme_id ) {
		if ( empty( $theme_id ) ) {
			delete_post_meta( $post_id, self::META_THEME );
			return true;
		}

		$themes = self::get_themes();
		if ( ! isset( $themes[ $theme_id ] ) ) {
			return false;
		}

		return update_post_meta( $post_id, self::META_THEME, sanitize_key( $theme_id ) );
	}

	/**
	 * Get custom CSS for a post
	 *
	 * @param int $post_id Post ID.
	 * @return string Custom CSS.
	 */
	public static function get_custom_css( $post_id ) {
		return get_post_meta( $post_id, self::META_CUSTOM_CSS, true ) ?: '';
	}

	/**
	 * Set custom CSS for a post
	 *
	 * @param int    $post_id Post ID.
	 * @param string $css     Custom CSS.
	 * @return bool Success.
	 */
	public static function set_custom_css( $post_id, $css ) {
		$sanitized = self::sanitize_css( $css );
		return update_post_meta( $post_id, self::META_CUSTOM_CSS, $sanitized );
	}

	/**
	 * Sanitize CSS input
	 *
	 * @param string $css Raw CSS.
	 * @return string Sanitized CSS.
	 */
	private static function sanitize_css( $css ) {
		// Remove potentially dangerous content.
		$css = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $css );
		$css = preg_replace( '/javascript\s*:/i', '', $css );
		$css = preg_replace( '/expression\s*\(/i', '', $css );
		$css = preg_replace( '/url\s*\(\s*["\']?\s*data:/i', 'url(', $css );
		$css = preg_replace( '/@import/i', '', $css );

		// Strip HTML tags.
		$css = wp_strip_all_tags( $css );

		// Limit length.
		$css = substr( $css, 0, 50000 );

		return $css;
	}

	/**
	 * Inject theme CSS on frontend
	 *
	 * Tooltip: Outputs theme CSS in wp_head for apollo_home posts.
	 */
	public static function inject_theme_css() {
		if ( ! is_singular( 'apollo_home' ) ) {
			return;
		}

		global $post;
		$theme_id = self::get_post_theme( $post->ID );

		if ( ! $theme_id ) {
			return;
		}

		$theme = self::get_theme( $theme_id );
		if ( ! $theme || empty( $theme['css'] ) ) {
			return;
		}

		echo '<style id="apollo-builder-theme-' . esc_attr( $theme_id ) . '">';
		echo '/* Apollo Builder Theme: ' . esc_html( $theme['label'] ) . ' */' . "\n";
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS is pre-defined, not user input.
		echo $theme['css'];
		echo '</style>';

		// Also inject custom CSS if present.
		$custom_css = self::get_custom_css( $post->ID );
		if ( $custom_css ) {
			echo '<style id="apollo-builder-custom-css">';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized in set_custom_css.
			echo $custom_css;
			echo '</style>';
		}
	}

	/**
	 * AJAX: Set theme for a post
	 */
	public static function ajax_set_theme() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'apollo-builder-nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'apollo-social' ) ), 403 );
		}

		$post_id  = absint( $_POST['post_id'] ?? 0 );
		$theme_id = sanitize_key( $_POST['theme_id'] ?? '' );

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post.', 'apollo-social' ) ), 400 );
		}

		// Check permissions.
		if ( ! Apollo_Home_CPT::user_can_edit( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'apollo-social' ) ), 403 );
		}

		$success = self::set_post_theme( $post_id, $theme_id );

		if ( $success ) {
			$theme = self::get_theme( $theme_id );
			wp_send_json_success(
				array(
					'theme_id' => $theme_id,
					'css'      => $theme['css'] ?? '',
					'message'  => __( 'Theme applied!', 'apollo-social' ),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to apply theme.', 'apollo-social' ) ), 500 );
		}
	}

	/**
	 * AJAX: Get all themes
	 */
	public static function ajax_get_themes() {
		$themes = self::get_themes();
		$output = array();

		foreach ( $themes as $id => $theme ) {
			$output[] = array(
				'id'          => $id,
				'label'       => $theme['label'],
				'description' => $theme['description'],
				'preview'     => $theme['preview'],
				'colors'      => $theme['colors'],
			);
		}

		wp_send_json_success( array( 'themes' => $output ) );
	}

	/**
	 * Get themes for JS (builder config)
	 *
	 * @return array Themes formatted for JS.
	 */
	public static function get_themes_for_js() {
		$themes = self::get_themes();
		$output = array();

		foreach ( $themes as $id => $theme ) {
			$output[] = array(
				'id'          => $id,
				'label'       => $theme['label'],
				'description' => $theme['description'],
				'preview'     => $theme['preview'],
				'colors'      => $theme['colors'],
				// CSS is applied server-side, not sent to JS for security.
			);
		}

		return $output;
	}
}

// Initialize.
add_action( 'init', array( 'Apollo_Builder_Themes', 'init' ) );
