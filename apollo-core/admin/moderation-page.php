<?php
declare(strict_types=1);

/**
 * Apollo Core - Moderation Admin Page
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register moderation admin menu
 */
function apollo_register_moderation_menu() {
	add_menu_page(
		__( 'Apollo Moderation', 'apollo-core' ),
		__( 'Moderation', 'apollo-core' ),
		'view_moderation_queue',
		'apollo-moderation',
		'apollo_render_moderation_page',
		'dashicons-shield',
		25
	);
}
add_action( 'admin_menu', 'apollo_register_moderation_menu' );

/**
 * Enqueue admin assets
 *
 * @param string $hook Current admin page hook.
 */
function apollo_enqueue_moderation_assets( $hook ) {
	if ( 'toplevel_page_apollo-moderation' !== $hook ) {
		return;
	}

	wp_enqueue_script(
		'apollo-moderation-admin',
		APOLLO_CORE_PLUGIN_URL . 'admin/js/moderation-admin.js',
		array( 'jquery', 'wp-api' ),
		APOLLO_CORE_VERSION,
		true
	);

	wp_localize_script(
		'apollo-moderation-admin',
		'apolloModerationAdmin',
		array(
			'restUrl'  => rest_url( 'apollo/v1/' ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'canManage' => current_user_can( 'manage_apollo_mod_settings' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'apollo_enqueue_moderation_assets' );

/**
 * Render moderation page
 */
function apollo_render_moderation_page() {
	// Get current tab.
	$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings';

	// Check permissions.
	$can_manage = current_user_can( 'manage_apollo_mod_settings' );
	if ( ! $can_manage && 'settings' === $current_tab ) {
		$current_tab = 'queue';
	}

	?>
	<div class="wrap apollo-moderation-wrap">
		<h1><?php esc_html_e( 'Apollo Moderation', 'apollo-core' ); ?></h1>

		<nav class="nav-tab-wrapper">
			<?php if ( $can_manage ) : ?>
			<a href="?page=apollo-moderation&tab=settings" class="nav-tab <?php echo 'settings' === $current_tab ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Settings', 'apollo-core' ); ?>
			</a>
			<?php endif; ?>
			<a href="?page=apollo-moderation&tab=queue" class="nav-tab <?php echo 'queue' === $current_tab ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Moderation Queue', 'apollo-core' ); ?>
			</a>
			<a href="?page=apollo-moderation&tab=users" class="nav-tab <?php echo 'users' === $current_tab ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Moderate Users', 'apollo-core' ); ?>
			</a>
		</nav>

		<div class="apollo-moderation-content">
			<?php
			switch ( $current_tab ) {
				case 'settings':
					apollo_render_settings_tab();
					break;
				case 'queue':
					apollo_render_queue_tab();
					break;
				case 'users':
					apollo_render_users_tab();
					break;
			}
			?>
		</div>
	</div>
	<?php
}

/**
 * Render Tab 1: Settings
 */
function apollo_render_settings_tab() {
	if ( ! current_user_can( 'manage_apollo_mod_settings' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'apollo-core' ) );
	}

	$settings = apollo_get_mod_settings();

	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'apollo_save_mod_settings', 'apollo_mod_nonce' ); ?>
		<input type="hidden" name="action" value="apollo_save_mod_settings">

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="apollo-moderators"><?php esc_html_e( 'Moderators', 'apollo-core' ); ?></label>
				</th>
				<td>
					<select name="mods[]" id="apollo-moderators" multiple size="10" style="width: 100%; max-width: 400px;">
						<?php
						$users = get_users(
							array(
								'role__in' => array( 'apollo', 'editor', 'administrator' ),
								'orderby'  => 'display_name',
							)
						);
						foreach ( $users as $user ) {
							$selected = in_array( $user->ID, $settings['mods'], true ) ? 'selected' : '';
							?>
							<option value="<?php echo esc_attr( $user->ID ); ?>" <?php echo esc_attr( $selected ); ?>>
								<?php echo esc_html( $user->display_name ); ?> (<?php echo esc_html( $user->user_email ); ?>)
							</option>
							<?php
						}
						?>
					</select>
					<p class="description"><?php esc_html_e( 'Hold Ctrl/Cmd to select multiple users.', 'apollo-core' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Enabled Capabilities', 'apollo-core' ); ?></th>
				<td>
					<fieldset>
						<?php
						$capabilities = array(
							'publish_events'      => __( 'Publish Events', 'apollo-core' ),
							'publish_locals'      => __( 'Publish Venues', 'apollo-core' ),
							'publish_djs'         => __( 'Publish DJs', 'apollo-core' ),
							'publish_nucleos'     => __( 'Publish Núcleos', 'apollo-core' ),
							'publish_comunidades' => __( 'Publish Comunidades', 'apollo-core' ),
							'edit_posts'          => __( 'Edit Social Posts', 'apollo-core' ),
							'edit_classifieds'    => __( 'Edit Classifieds', 'apollo-core' ),
						);

						foreach ( $capabilities as $cap => $label ) {
							$checked = ! empty( $settings['enabled_caps'][ $cap ] ) ? 'checked' : '';
							?>
							<label>
								<input type="checkbox" name="enabled_caps[<?php echo esc_attr( $cap ); ?>]" value="1" <?php echo esc_attr( $checked ); ?>>
								<?php echo esc_html( $label ); ?>
							</label><br>
							<?php
						}
						?>
					</fieldset>
					<p class="description"><?php esc_html_e( 'Select which content types Apollo moderators can publish.', 'apollo-core' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="apollo-audit-log"><?php esc_html_e( 'Audit Log', 'apollo-core' ); ?></label>
				</th>
				<td>
					<label>
						<input type="checkbox" name="audit_log_enabled" id="apollo-audit-log" value="1" <?php checked( $settings['audit_log_enabled'] ); ?>>
						<?php esc_html_e( 'Enable audit logging for moderation actions', 'apollo-core' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save Settings', 'apollo-core' ) ); ?>
	</form>
	<?php
}

/**
 * Render Tab 2: Moderation Queue
 */
function apollo_render_queue_tab() {
	if ( ! current_user_can( 'view_moderation_queue' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'apollo-core' ) );
	}

	// Get enabled content types.
	$settings = apollo_get_mod_settings();
	$enabled_types = array();

	foreach ( $settings['enabled_caps'] as $cap => $enabled ) {
		if ( $enabled ) {
			$post_type = apollo_capability_to_post_type( $cap );
			if ( $post_type ) {
				$enabled_types[] = $post_type;
			}
		}
	}

	if ( empty( $enabled_types ) ) {
		$enabled_types = array( 'post' );
	}

	// Query draft posts.
	$query = new WP_Query(
		array(
			'post_type'      => $enabled_types,
			'post_status'    => array( 'draft', 'pending' ),
			'posts_per_page' => 50,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	?>
	<div id="apollo-moderation-queue">
		<h2><?php esc_html_e( 'Moderation Queue', 'apollo-core' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Review and approve pending content.', 'apollo-core' ); ?></p>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Thumbnail', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Title', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Type', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Author', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Date', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'apollo-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( $query->have_posts() ) : ?>
					<?php while ( $query->have_posts() ) : $query->the_post(); ?>
						<tr data-post-id="<?php echo esc_attr( get_the_ID() ); ?>">
							<td>
								<?php if ( has_post_thumbnail() ) : ?>
									<?php the_post_thumbnail( 'thumbnail' ); ?>
								<?php else : ?>
									—
								<?php endif; ?>
							</td>
							<td>
								<strong><a href="<?php echo esc_url( get_edit_post_link() ); ?>" target="_blank"><?php the_title(); ?></a></strong>
							</td>
							<td><?php echo esc_html( get_post_type() ); ?></td>
							<td><?php the_author(); ?></td>
							<td><?php echo esc_html( get_the_date() ); ?></td>
							<td>
								<button class="button apollo-approve-btn" data-post-id="<?php echo esc_attr( get_the_ID() ); ?>">
									<?php esc_html_e( 'Approve', 'apollo-core' ); ?>
								</button>
							</td>
						</tr>
					<?php endwhile; ?>
					<?php wp_reset_postdata(); ?>
				<?php else : ?>
					<tr>
						<td colspan="6" style="text-align: center;">
							<?php esc_html_e( 'No items in queue.', 'apollo-core' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * Render Tab 3: Moderate Users
 */
function apollo_render_users_tab() {
	if ( ! current_user_can( 'edit_apollo_users' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'apollo-core' ) );
	}

	$users = get_users(
		array(
			'orderby' => 'registered',
			'order'   => 'DESC',
			'number'  => 50,
		)
	);

	?>
	<div id="apollo-moderate-users">
		<h2><?php esc_html_e( 'Moderate Users', 'apollo-core' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Manage user accounts, send notifications, suspend or block users.', 'apollo-core' ); ?></p>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Avatar', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Name', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Email', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Role', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Membership', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Status', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'apollo-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $users as $user ) : ?>
					<?php
					$status_info = apollo_get_user_status( $user->ID );
					$status_text = 'Active';
					if ( $status_info['is_blocked'] ) {
						$status_text = '<span style="color: red;">Blocked</span>';
					} elseif ( $status_info['is_suspended'] ) {
						$status_text = '<span style="color: orange;">Suspended</span>';
					}
					?>
					<tr>
						<td><?php echo get_avatar( $user->ID, 32 ); ?></td>
						<td><?php echo esc_html( $user->display_name ); ?></td>
						<td><?php echo esc_html( $user->user_email ); ?></td>
						<td><?php echo esc_html( implode( ', ', $user->roles ) ); ?></td>
						<td><?php apollo_render_user_membership_selector( $user ); ?></td>
						<td><?php echo wp_kses_post( $status_text ); ?></td>
						<td>
							<?php if ( current_user_can( 'suspend_users' ) && ! in_array( 'administrator', $user->roles, true ) ) : ?>
								<button class="button apollo-suspend-btn" data-user-id="<?php echo esc_attr( $user->ID ); ?>">
									<?php esc_html_e( 'Suspend', 'apollo-core' ); ?>
								</button>
							<?php endif; ?>
							<?php if ( current_user_can( 'block_users' ) && ! in_array( 'administrator', $user->roles, true ) ) : ?>
								<button class="button apollo-block-btn" data-user-id="<?php echo esc_attr( $user->ID ); ?>">
									<?php esc_html_e( 'Block', 'apollo-core' ); ?>
								</button>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php apollo_render_membership_types_manager(); ?>
	</div>
	<?php
}

/**
 * Save moderation settings
 */
function apollo_handle_save_settings() {
	// Verify nonce.
	check_admin_referer( 'apollo_save_mod_settings', 'apollo_mod_nonce' );

	// Check permission.
	if ( ! current_user_can( 'manage_apollo_mod_settings' ) ) {
		wp_die( esc_html__( 'You do not have permission to perform this action.', 'apollo-core' ) );
	}

	// Get form data.
	$mods             = isset( $_POST['mods'] ) ? array_map( 'absint', wp_unslash( $_POST['mods'] ) ) : array();
	$enabled_caps     = isset( $_POST['enabled_caps'] ) ? array_map( 'intval', wp_unslash( $_POST['enabled_caps'] ) ) : array();
	$audit_log_enabled = isset( $_POST['audit_log_enabled'] );

	// Update settings.
	$settings = array(
		'mods'              => $mods,
		'enabled_caps'      => $enabled_caps,
		'audit_log_enabled' => $audit_log_enabled,
		'version'           => '1.0.0',
	);

	apollo_update_mod_settings( $settings );

	// Redirect back.
	wp_safe_redirect( admin_url( 'admin.php?page=apollo-moderation&tab=settings&updated=1' ) );
	exit;
}
add_action( 'admin_post_apollo_save_mod_settings', 'apollo_handle_save_settings' );

/**
 * Map capability to post type
 *
 * @param string $capability Capability key.
 * @return string|false Post type or false.
 */
function apollo_capability_to_post_type( $capability ) {
	$map = array(
		'publish_events'      => 'event_listing',
		'publish_locals'      => 'event_local',
		'publish_djs'         => 'event_dj',
		'publish_nucleos'     => 'apollo_nucleo',
		'publish_comunidades' => 'apollo_comunidade',
		'edit_posts'          => 'apollo_social_post',
		'edit_classifieds'    => 'apollo_classified',
	);

	return isset( $map[ $capability ] ) ? $map[ $capability ] : false;
}

