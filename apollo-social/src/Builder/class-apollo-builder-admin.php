<?php
/**
 * Apollo Builder Admin
 *
 * Handles WP-Admin integration for the Builder.
 * Registers the "Apollo Builder" submenu and the "Builder Assets" page.
 *
 * @package Apollo_Social
 * @since 1.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Apollo_Builder_Admin
 */
class Apollo_Builder_Admin {

	/**
	 * Initialize
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_pages' ) );
	}

	/**
	 * Add menu pages
	 */
	public static function add_menu_pages() {
		// Main Builder Launcher (matches ?page=apollo-builder)
		add_submenu_page(
			'apollo-social-hub',
			__( 'Apollo Builder', 'apollo-social' ),
			__( '🏗️ Apollo Builder', 'apollo-social' ),
			'read',
			// Allow subscribers to see the menu, access control handled in render
			'apollo-builder',
			array( __CLASS__, 'render_launcher_page' )
		);
	}

	/**
	 * Render Launcher Page
	 *
	 * Displays a simple dashboard to launch the frontend builder.
	 */
	public static function render_launcher_page() {
		// Capability check for using the builder
		if ( ! is_user_logged_in() ) {
			echo '<div class="wrap"><p>' . __( 'You must be logged in.', 'apollo-social' ) . '</p></div>';

			return;
		}

		// Check if user has permission to use builder
		if ( ! current_user_can( APOLLO_BUILDER_CAPABILITY ) ) {
			echo '<div class="wrap"><h1>' . __( 'Apollo Builder', 'apollo-social' ) . '</h1>';
			echo '<div class="notice notice-error"><p>' . __( 'You do not have permission to use the builder.', 'apollo-social' ) . '</p></div></div>';

			return;
		}

		$user_id   = get_current_user_id();
		$home_post = Apollo_Home_CPT::get_or_create_home( $user_id );

		$preview_url = $home_post ? get_permalink( $home_post ) : home_url();
		// Builder URL is preview URL + ?editar=pagina
		$builder_url = $home_post ? add_query_arg( 'editar', 'pagina', get_permalink( $home_post ) ) : '#';

		?>
		<div class="wrap">
			<h1>🏗️ <?php _e( 'Apollo Builder', 'apollo-social' ); ?></h1>
			
			<div class="card" style="max-width: 600px; margin-top: 20px; padding: 20px;">
				<h2><?php _e( 'Welcome to your creative space!', 'apollo-social' ); ?></h2>
				<p class="description">
					<?php _e( 'The Apollo Builder allows you to customize your personal "Clubber Home" with widgets, stickers, and music.', 'apollo-social' ); ?>
				</p>
				
				<?php if ( ! $home_post ) : ?>
					<div class="notice notice-error inline">
						<p><?php _e( 'Could not create your home page. Please contact support.', 'apollo-social' ); ?></p>
					</div>
				<?php else : ?>
					<div style="margin-top: 30px; display: flex; gap: 15px;">
						<a href="<?php echo esc_url( $builder_url ); ?>" class="button button-primary button-hero" target="_blank">
							<span class="dashicons dashicons-edit" style="margin-top: 5px;"></span> 
							<?php _e( 'Launch Builder', 'apollo-social' ); ?>
						</a>
						
						<a href="<?php echo esc_url( $preview_url ); ?>" class="button button-secondary button-hero" target="_blank">
							<span class="dashicons dashicons-visibility" style="margin-top: 5px;"></span> 
							<?php _e( 'View My Home', 'apollo-social' ); ?>
						</a>
					</div>
				<?php endif; ?>
				
				<hr style="margin: 20px 0;">
				
				<h3><?php _e( 'Quick Tips', 'apollo-social' ); ?></h3>
				<ul style="list-style: disc; margin-left: 20px;">
					<li><?php _e( 'Drag widgets from the sidebar to your canvas.', 'apollo-social' ); ?></li>
					<li><?php _e( 'Use the "Settings" panel to change backgrounds and music.', 'apollo-social' ); ?></li>
					<li><?php _e( 'Click on a widget to configure or delete it.', 'apollo-social' ); ?></li>
					<li><?php _e( 'Changes are auto-saved to your draft, but remember to click Save!', 'apollo-social' ); ?></li>
				</ul>
			</div>
		</div>
		<?php
	}
}
