<?php
// phpcs:ignoreFile
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
function apollo_register_moderation_menu(): void {
	// Get pending count for badge
	$pending_count = 0;
	if ( class_exists( 'Apollo_Moderation_Queue_Unified' ) ) {
		$pending_count = Apollo_Moderation_Queue_Unified::get_pending_count();
	}

	// Build menu title with badge if there are pending items
	$menu_title = __( 'Moderation', 'apollo-core' );
	if ( $pending_count > 0 ) {
		$menu_title = sprintf(
			/* translators: %1$s: Menu title, %2$d: pending count, %3$d: pending count for badge */
			'%1$s <span class="awaiting-mod count-%2$d"><span class="pending-count">%3$d</span></span>',
			esc_html__( 'Moderation', 'apollo-core' ),
			$pending_count,
			$pending_count
		);
	}

	add_menu_page(
		__( 'Apollo Moderation', 'apollo-core' ),
		$menu_title,
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
			'restUrl'   => rest_url( 'apollo/v1/' ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
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
function apollo_render_queue_tab(): void {
	if ( ! current_user_can( 'view_moderation_queue' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'apollo-core' ) );
	}

	// Use unified queue if available
	$enabled_types = array();
	if ( class_exists( 'Apollo_Moderation_Queue_Unified' ) ) {
		$enabled_types = Apollo_Moderation_Queue_Unified::get_moderation_cpts();
	} else {
		// Fallback to legacy method
		$settings = apollo_get_mod_settings();
		foreach ( $settings['enabled_caps'] as $cap => $enabled ) {
			if ( $enabled ) {
				$post_type = apollo_capability_to_post_type( $cap );
				if ( $post_type ) {
					$enabled_types[] = $post_type;
				}
			}
		}
	}

	if ( empty( $enabled_types ) ) {
		$enabled_types = array( 'event_listing', 'post' );
	}

	// Query ALL pending/draft posts
	$query = new WP_Query(
		array(
			'post_type'      => $enabled_types,
			'post_status'    => array( 'draft', 'pending' ),
			'posts_per_page' => 100,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	// Get counts by source
	$cena_rio_count = 0;
	$other_count    = 0;
	foreach ( $query->posts as $post ) {
		$source = get_post_meta( $post->ID, '_apollo_source', true );
		if ( 'cena-rio' === $source ) {
			++$cena_rio_count;
		} else {
			++$other_count;
		}
	}

	// Get filter from URL
	$current_filter = isset( $_GET['source'] ) ? sanitize_text_field( wp_unslash( $_GET['source'] ) ) : 'all';

	?>
	<div id="apollo-moderation-queue">
		<h2><?php esc_html_e( 'Moderation Queue', 'apollo-core' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Review and approve pending content from all sources.', 'apollo-core' ); ?></p>

		<!-- Source filter tabs -->
		<div class="apollo-queue-source-filter" style="margin: 16px 0; display: flex; gap: 8px;">
			<a href="?page=apollo-moderation&tab=queue&source=all" 
				class="button <?php echo 'all' === $current_filter ? 'button-primary' : ''; ?>">
				<?php echo esc_html( sprintf( __( 'Todos (%d)', 'apollo-core' ), $query->found_posts ) ); ?>
			</a>
			<a href="?page=apollo-moderation&tab=queue&source=cena-rio" 
				class="button <?php echo 'cena-rio' === $current_filter ? 'button-primary' : ''; ?>"
				style="<?php echo $cena_rio_count > 0 ? 'background: #f97316; border-color: #f97316; color: #fff;' : ''; ?>">
				<span class="dashicons dashicons-calendar-alt" style="margin-top: 3px;"></span>
				<?php echo esc_html( sprintf( __( 'CENA-RIO (%d)', 'apollo-core' ), $cena_rio_count ) ); ?>
			</a>
			<a href="?page=apollo-moderation&tab=queue&source=other" 
				class="button <?php echo 'other' === $current_filter ? 'button-primary' : ''; ?>">
				<?php echo esc_html( sprintf( __( 'Outros (%d)', 'apollo-core' ), $other_count ) ); ?>
			</a>
		</div>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width: 60px;"><?php esc_html_e( 'Thumbnail', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Title', 'apollo-core' ); ?></th>
					<th style="width: 100px;"><?php esc_html_e( 'Type', 'apollo-core' ); ?></th>
					<th style="width: 80px;"><?php esc_html_e( 'Source', 'apollo-core' ); ?></th>
					<th style="width: 120px;"><?php esc_html_e( 'Author', 'apollo-core' ); ?></th>
					<th style="width: 120px;"><?php esc_html_e( 'Date', 'apollo-core' ); ?></th>
					<th style="width: 180px;"><?php esc_html_e( 'Actions', 'apollo-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$has_items = false;
				if ( $query->have_posts() ) :
					while ( $query->have_posts() ) :
						$query->the_post();
						$post_id = get_the_ID();
						$source  = get_post_meta( $post_id, '_apollo_source', true );
						$is_cena = 'cena-rio' === $source;

						// Apply source filter
						if ( 'cena-rio' === $current_filter && ! $is_cena ) {
							continue;
						}
						if ( 'other' === $current_filter && $is_cena ) {
							continue;
						}

						$has_items = true;
						$row_style = $is_cena ? 'border-left: 4px solid #f97316; background: #fff7ed;' : '';

						// Get post type label
						$post_type   = get_post_type();
						$type_labels = array(
							'event_listing'      => __( 'Evento', 'apollo-core' ),
							'event_local'        => __( 'Local', 'apollo-core' ),
							'event_dj'           => __( 'DJ', 'apollo-core' ),
							'apollo_social_post' => __( 'Post', 'apollo-core' ),
							'post'               => __( 'Post', 'apollo-core' ),
						);
						$type_label  = $type_labels[ $post_type ] ?? $post_type;
						?>
						<tr data-post-id="<?php echo esc_attr( $post_id ); ?>" style="<?php echo esc_attr( $row_style ); ?>">
							<td>
								<?php if ( has_post_thumbnail() ) : ?>
									<?php the_post_thumbnail( array( 50, 50 ), array( 'style' => 'border-radius: 4px;' ) ); ?>
								<?php else : ?>
									<span class="dashicons dashicons-format-image" style="font-size: 30px; color: #ccc;"></span>
								<?php endif; ?>
							</td>
							<td>
								<strong><a href="<?php echo esc_url( get_edit_post_link() ); ?>" target="_blank"><?php the_title(); ?></a></strong>
								<?php if ( $is_cena ) : ?>
									<br><small style="color: #f97316;">via Cena::Rio</small>
								<?php endif; ?>
							</td>
							<td>
								<span class="post-type-badge" style="display: inline-block; padding: 2px 8px; background: #e5e7eb; border-radius: 4px; font-size: 12px;">
									<?php echo esc_html( $type_label ); ?>
								</span>
							</td>
							<td>
								<?php if ( $is_cena ) : ?>
									<span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; background: #f97316; color: #fff; border-radius: 4px; font-size: 11px; font-weight: 600;">
										<span class="dashicons dashicons-calendar-alt" style="font-size: 14px; width: 14px; height: 14px;"></span>
										CENA
									</span>
								<?php else : ?>
									<span style="color: #6b7280; font-size: 12px;">WP</span>
								<?php endif; ?>
							</td>
							<td><?php the_author(); ?></td>
							<td>
								<span title="<?php echo esc_attr( get_the_date( 'Y-m-d H:i:s' ) ); ?>">
									<?php echo esc_html( human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) ); ?> <?php esc_html_e( 'atrás', 'apollo-core' ); ?>
								</span>
							</td>
							<td>
								<button class="button button-primary apollo-approve-btn" data-post-id="<?php echo esc_attr( $post_id ); ?>">
									<span class="dashicons dashicons-yes" style="margin-top: 3px;"></span>
									<?php esc_html_e( 'Aprovar', 'apollo-core' ); ?>
								</button>
								<button class="button apollo-reject-btn" data-post-id="<?php echo esc_attr( $post_id ); ?>" style="color: #dc2626;">
									<span class="dashicons dashicons-no" style="margin-top: 3px;"></span>
								</button>
							</td>
						</tr>
					<?php endwhile; ?>
					<?php wp_reset_postdata(); ?>
				<?php endif; ?>
				<?php if ( ! $has_items ) : ?>
					<tr>
						<td colspan="7" style="text-align: center; padding: 40px;">
							<span class="dashicons dashicons-yes-alt" style="font-size: 48px; color: #10b981; display: block; margin-bottom: 12px;"></span>
							<strong><?php esc_html_e( 'Nenhum item pendente!', 'apollo-core' ); ?></strong>
							<p style="color: #6b7280; margin: 8px 0 0 0;">
								<?php esc_html_e( 'Todos os conteúdos foram moderados.', 'apollo-core' ); ?>
							</p>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>

		<style>
			.apollo-moderation-queue tr:hover { background: #f9fafb !important; }
			.apollo-approve-btn:hover { background: #059669 !important; border-color: #059669 !important; }
			.apollo-reject-btn:hover { background: #fef2f2 !important; border-color: #dc2626 !important; }
		</style>
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
	$mods              = isset( $_POST['mods'] ) ? array_map( 'absint', wp_unslash( $_POST['mods'] ) ) : array();
	$enabled_caps      = isset( $_POST['enabled_caps'] ) ? array_map( 'intval', wp_unslash( $_POST['enabled_caps'] ) ) : array();
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

