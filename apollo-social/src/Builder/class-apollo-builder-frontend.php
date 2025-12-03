<?php
/**
 * Apollo Builder Frontend
 *
 * Handles routing, template loading, and asset enqueueing.
 *
 * Pattern source: Live Composer - rewrite endpoints, template_include filter
 * Pattern source: WOW - script localization, assets loading
 *
 * @package Apollo_Social
 * @since 1.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Apollo_Builder_Frontend
 */
class Apollo_Builder_Frontend {

	/**
	 * Initialize hooks
	 */
	public static function init() {
		// Query vars for edit mode
		add_filter( 'query_vars', array( __CLASS__, 'add_query_vars' ) );

		// Template loading
		add_action( 'template_redirect', array( __CLASS__, 'template_redirect' ) );
		add_filter( 'template_include', array( __CLASS__, 'template_include' ) );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

		// Render layout in content
		add_filter( 'the_content', array( __CLASS__, 'render_home_content' ), 20 );

		// Body class
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
	}

	/**
	 * Add query vars
	 */
	public static function add_query_vars( $vars ) {
		$vars[] = 'editar';
		// ?editar=pagina
		return $vars;
	}

	/**
	 * Template redirect
	 * Handles access control for edit mode
	 */
	public static function template_redirect() {
		// Check if we are on a single apollo_home page with ?editar=pagina
		if ( is_singular( Apollo_Home_CPT::POST_TYPE ) && get_query_var( 'editar' ) === 'pagina' ) {

			// Must be logged in
			if ( ! is_user_logged_in() ) {
				wp_redirect( wp_login_url( get_permalink() ) );
				exit;
			}

			// Must have capability
			if ( ! current_user_can( APOLLO_BUILDER_CAPABILITY ) ) {
				wp_die(
					__( 'You do not have permission to access the builder.', 'apollo-social' ),
					__( 'Access Denied', 'apollo-social' ),
					array( 'response' => 403 )
				);
			}

			// Must be owner or admin
			global $post;
			if ( ! Apollo_Home_CPT::user_can_edit( $post->ID ) ) {
				wp_die(
					__( 'You can only edit your own home.', 'apollo-social' ),
					__( 'Access Denied', 'apollo-social' ),
					array( 'response' => 403 )
				);
			}
		}//end if
	}

	/**
	 * Load custom template for builder
	 * Only loads if ?editar=pagina matches
	 */
	public static function template_include( $template ) {
		// Builder mode check
		if ( is_singular( Apollo_Home_CPT::POST_TYPE ) && get_query_var( 'editar' ) === 'pagina' ) {
			$custom_template = APOLLO_BUILDER_DIR . '../../../templates/apollo-builder.php';

			if ( file_exists( $custom_template ) ) {
				return $custom_template;
			}

			// Fallback: generate inline (should rarely happen if file exists)
			self::render_builder_page();
			exit;
		}

		return $template;
	}

	/**
	 * Render builder page inline (fallback)
	 */
	private static function render_builder_page() {
		global $post;
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php wp_head(); ?>
	<title><?php _e( 'Apollo Builder', 'apollo-social' ); ?></title>
</head>
<body <?php body_class( 'apollo-builder-page' ); ?>>
	<div id="apollo-builder-root" data-home-id="<?php echo absint( $post->ID ); ?>"></div>
		<?php wp_footer(); ?>
</body>
</html>
		<?php
	}

	/**
	 * Enqueue scripts
	 * Pattern: WOW Assets.php - conditional loading
	 */
	public static function enqueue_scripts() {
		// Builder mode assets
		if ( is_singular( Apollo_Home_CPT::POST_TYPE ) && get_query_var( 'editar' ) === 'pagina' ) {
			self::enqueue_builder_assets();
			return;
		}

		// Frontend view assets
		if ( is_singular( Apollo_Home_CPT::POST_TYPE ) ) {
			self::enqueue_frontend_assets();
		}
	}

	/**
	 * Enqueue builder-specific assets
	 */
	private static function enqueue_builder_assets() {
		global $post;
		$user_id = get_current_user_id();
		$user    = get_userdata( $user_id );

		// CSS
		wp_enqueue_style(
			'apollo-builder-css',
			plugins_url( 'assets/css/apollo-builder.css', dirname( __DIR__ ) ),
			array(),
			APOLLO_BUILDER_VERSION
		);

		// JS
		wp_enqueue_script(
			'apollo-builder-js',
			plugins_url( 'assets/js/apollo-builder.js', dirname( __DIR__ ) ),
			array( 'jquery' ),
			APOLLO_BUILDER_VERSION,
			true
		);

		// Localize (pattern: WOW wp_localize_script)
		wp_localize_script(
			'apollo-builder-js',
			'apolloBuilderConfig',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'restUrl'       => rest_url( 'apollo/v1/' ),
				'nonce'         => wp_create_nonce( 'apollo-builder-nonce' ),
				'homePostId'    => $post->ID,
				'userId'        => $user_id,
				'userName'      => $user ? $user->display_name : '',
				'userAvatar'    => get_avatar_url( $user_id, array( 'size' => 80 ) ),
				'homeUrl'       => get_permalink( $post ),
				'stickers'      => self::get_stickers_for_js(),
				'textures'      => self::get_textures_for_js(),
				'currentLayout' => Apollo_Home_CPT::get_layout( $post->ID ),
				'currentBg'     => get_post_meta( $post->ID, APOLLO_BUILDER_META_BACKGROUND, true ),
				'currentTrax'   => get_post_meta( $post->ID, APOLLO_BUILDER_META_TRAX, true ),
				'gridSize'      => 24,
				'canvasWidth'   => 800,
				'canvasHeight'  => 600,
				'i18n'          => array(
					'save'           => __( 'Save', 'apollo-social' ),
					'saved'          => __( 'Saved!', 'apollo-social' ),
					'saving'         => __( 'Saving...', 'apollo-social' ),
					'error'          => __( 'Error saving', 'apollo-social' ),
					'delete'         => __( 'Delete', 'apollo-social' ),
					'confirm_delete' => __( 'Remove this widget?', 'apollo-social' ),
					'view_home'      => __( 'View Home', 'apollo-social' ),
					'exit_builder'   => __( 'Exit Builder', 'apollo-social' ),
					'widgets'        => __( 'Widgets', 'apollo-social' ),
					'backgrounds'    => __( 'Backgrounds', 'apollo-social' ),
					'music'          => __( 'Music', 'apollo-social' ),
				),
			)
		);
	}

	/**
	 * Enqueue frontend view assets
	 */
	private static function enqueue_frontend_assets() {
		wp_enqueue_style(
			'apollo-home-css',
			plugins_url( 'assets/css/apollo-home.css', dirname( __DIR__ ) ),
			array(),
			APOLLO_BUILDER_VERSION
		);

		wp_enqueue_script(
			'apollo-home-js',
			plugins_url( 'assets/js/apollo-home.js', dirname( __DIR__ ) ),
			array( 'jquery' ),
			APOLLO_BUILDER_VERSION,
			true
		);

		global $post;

		// Construct builder URL: current permalink + ?editar=pagina
		$builder_url = add_query_arg( 'editar', 'pagina', get_permalink( $post ) );

		wp_localize_script(
			'apollo-home-js',
			'apolloHomeConfig',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'apollo-builder-nonce' ),
				'postId'     => $post ? $post->ID : 0,
				'canEdit'    => $post ? Apollo_Home_CPT::user_can_edit( $post->ID ) : false,
				'builderUrl' => $builder_url,
			)
		);
	}

	/**
	 * Get stickers for JS
	 */
	private static function get_stickers_for_js() {
		$stickers = get_option( 'apollo_builder_stickers', array() );
		$output   = array();

		foreach ( $stickers as $s ) {
			if ( empty( $s['id'] ) || empty( $s['image_id'] ) ) {
				continue;
			}

			$output[] = array(
				'id'       => $s['id'],
				'label'    => $s['label'] ?? '',
				'imageUrl' => wp_get_attachment_image_url( $s['image_id'], 'medium' ),
			);
		}

		return $output;
	}

	/**
	 * Get textures for JS
	 */
	private static function get_textures_for_js() {
		$textures = get_option( 'apollo_builder_textures', array() );
		$output   = array();

		foreach ( $textures as $t ) {
			if ( empty( $t['id'] ) || empty( $t['image_id'] ) ) {
				continue;
			}

			$output[] = array(
				'id'       => $t['id'],
				'label'    => $t['label'] ?? '',
				'imageUrl' => wp_get_attachment_image_url( $t['image_id'], 'large' ),
				'thumbUrl' => wp_get_attachment_image_url( $t['image_id'], 'thumbnail' ),
			);
		}

		return $output;
	}

	/**
	 * Render home content
	 * Pattern: Hook into the_content for apollo_home posts
	 */
	public static function render_home_content( $content ) {
		global $post;

		if ( ! is_singular( Apollo_Home_CPT::POST_TYPE ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		// Don't render content if in builder mode (handled by template)
		if ( get_query_var( 'editar' ) === 'pagina' ) {
			return $content;
		}

		$layout     = Apollo_Home_CPT::get_layout( $post->ID );
		$background = get_post_meta( $post->ID, APOLLO_BUILDER_META_BACKGROUND, true );

		// Get background URL
		$bg_url = '';
		if ( $background ) {
			$textures = get_option( 'apollo_builder_textures', array() );
			foreach ( $textures as $t ) {
				if ( isset( $t['id'] ) && $t['id'] === $background && ! empty( $t['image_id'] ) ) {
					$bg_url = wp_get_attachment_image_url( $t['image_id'], 'full' );
					break;
				}
			}
		}

		$edit_url = add_query_arg( 'editar', 'pagina', get_permalink( $post ) );

		ob_start();
		?>
		<div class="apollo-home-board" 
			style="<?php echo $bg_url ? 'background-image:url(' . esc_url( $bg_url ) . ');' : ''; ?>">
			
			<?php if ( empty( $layout['widgets'] ) ) : ?>
				<div class="apollo-home-empty">
					<?php _e( 'This home is empty!', 'apollo-social' ); ?>
					<?php if ( Apollo_Home_CPT::user_can_edit( $post->ID ) ) : ?>
						<a href="<?php echo esc_url( $edit_url ); ?>" class="apollo-edit-btn">
							<?php _e( 'Customize your home', 'apollo-social' ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<?php foreach ( $layout['widgets'] as $widget_data ) : ?>
					<?php
					$html = apollo_builder_render_widget( $widget_data, $post->ID );
					$x    = absint( $widget_data['x'] ?? 0 );
					$y    = absint( $widget_data['y'] ?? 0 );
					$w    = absint( $widget_data['width'] ?? 200 );
					$h    = absint( $widget_data['height'] ?? 150 );
					$z    = absint( $widget_data['zIndex'] ?? 1 );
					$id   = esc_attr( $widget_data['id'] ?? '' );
					$type = esc_attr( $widget_data['type'] ?? '' );
					?>
					<div class="apollo-home-widget widget-type-<?php echo $type; ?>"
						data-widget-id="<?php echo $id; ?>"
						style="left:<?php echo $x; ?>px;top:<?php echo $y; ?>px;width:<?php echo $w; ?>px;height:<?php echo $h; ?>px;z-index:<?php echo $z; ?>;">
						<?php echo $html; ?>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
			
			<?php if ( Apollo_Home_CPT::user_can_edit( $post->ID ) ) : ?>
				<a href="<?php echo esc_url( $edit_url ); ?>" class="apollo-builder-link">
					<span class="dashicons dashicons-edit"></span>
					<?php _e( 'Edit', 'apollo-social' ); ?>
				</a>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Add body class
	 */
	public static function body_class( $classes ) {
		if ( get_query_var( 'editar' ) === 'pagina' ) {
			$classes[] = 'apollo-builder-active';
		}

		if ( is_singular( Apollo_Home_CPT::POST_TYPE ) ) {
			$classes[] = 'apollo-home-view';
		}

		return $classes;
	}
}
