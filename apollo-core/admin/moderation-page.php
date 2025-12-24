<?php
/**
 * Apollo Moderation Page
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Enqueue scripts and styles.
add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( 'toplevel_page_apollo-mod' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'apollo-mod-admin',
			APOLLO_CORE_PLUGIN_URL . 'admin/js/mod-admin.js',
			array( 'jquery', 'wp-api' ),
			APOLLO_CORE_VERSION,
			true
		);

		wp_localize_script(
			'apollo-mod-admin',
			'apolloModerationAdmin',
			array(
				'restUrl'   => rest_url( 'apollo/v1/' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'canManage' => current_user_can( 'manage_apollo_mod_settings' ),
			)
		);
	}
);

// Add menu page.
add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'apollo-control',
			'Apollo Moderation',
			'Moderation',
			'manage_options',
			'apollo-mod',
			'apollo_render_mod_page'
		);
	}
);

// Render mod page.
function apollo_render_mod_page() {
	// Get current tab.
	$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings';

	// Check permissions.
	$can_manage = current_user_can( 'manage_apollo_mod_settings' );
	if ( ! $can_manage && 'settings' === $current_tab ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'apollo-core' ) );
	}

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Apollo Moderation', 'apollo-core' ); ?></h1>

		<h2 class="nav-tab-wrapper">
			<a href="?page=apollo-mod&tab=settings" class="nav-tab <?php echo 'settings' === $current_tab ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Settings', 'apollo-core' ); ?>
			</a>
			<a href="?page=apollo-mod&tab=queue" class="nav-tab <?php echo 'queue' === $current_tab ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Moderation Queue', 'apollo-core' ); ?>
			</a>
		</h2>

		<?php if ( 'settings' === $current_tab ) : ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'apollo_mod_settings' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Moderators', 'apollo-core' ); ?></th>
						<td>
							<p><?php esc_html_e( 'Moderation settings are managed here.', 'apollo-core' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		<?php elseif ( 'queue' === $current_tab ) : ?>
			<div class="apollo-mod-queue">
				<p><?php esc_html_e( 'Moderation queue functionality.', 'apollo-core' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

