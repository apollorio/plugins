<?php
declare(strict_types=1);

/**
 * Moderation Admin UI (4 Tabs)
 *
 * Tabs: Settings, Moderation Queue, Moderate Users, Co-autores
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load co-authors settings.
require_once __DIR__ . '/class-coauthors-settings.php';

/**
 * Admin UI class
 */
class Apollo_Moderation_Admin_UI {

	/**
	 * Initialize
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_post_apollo_save_mod_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Add admin menu
	 */
	public static function add_menu() {
		add_menu_page(
			__( 'Apollo Moderation', 'apollo-core' ),
			__( 'Moderation', 'apollo-core' ),
			'view_mod_queue',
			'apollo-mod',
			array( __CLASS__, 'render_page' ),
			'dashicons-shield',
			25
		);
	}

	/**
	 * Enqueue assets
	 *
	 * @param string $hook Hook name.
	 */
	public static function enqueue_assets( $hook ) {
		if ( 'toplevel_page_apollo-mod' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'apollo-mod',
			APOLLO_CORE_PLUGIN_URL . 'modules/mod/assets/mod.css',
			array(),
			APOLLO_CORE_VERSION
		);

		wp_enqueue_script(
			'apollo-mod',
			APOLLO_CORE_PLUGIN_URL . 'modules/mod/assets/mod.js',
			array( 'jquery', 'wp-api' ),
			APOLLO_CORE_VERSION,
			true
		);

		wp_localize_script(
			'apollo-mod',
			'apolloModeration',
			array(
				'restUrl'   => rest_url( Apollo_Core_Rest_Bootstrap::get_namespace() ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'canManage' => current_user_can( 'manage_apollo_mod_settings' ),
			)
		);
	}

	/**
	 * Render admin page
	 */
	public static function render_page() {
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings';
		$can_manage  = current_user_can( 'manage_apollo_mod_settings' );

		if ( ! $can_manage && in_array( $current_tab, array( 'settings', 'coauthors' ), true ) ) {
			$current_tab = 'queue';
		}

		// Show update notice.
		if ( isset( $_GET['updated'] ) && '1' === $_GET['updated'] ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Configurações salvas com sucesso!', 'apollo-core' ) . '</p></div>';
		}
		?>
		<div class="wrap apollo-mod-wrap">
			<h1><?php esc_html_e( 'Apollo Moderation', 'apollo-core' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<?php if ( $can_manage ) : ?>
				<a href="?page=apollo-mod&tab=settings" class="nav-tab <?php echo 'settings' === $current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Settings', 'apollo-core' ); ?>
				</a>
				<?php endif; ?>
				<a href="?page=apollo-mod&tab=queue" class="nav-tab <?php echo 'queue' === $current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Moderation Queue', 'apollo-core' ); ?>
				</a>
				<a href="?page=apollo-mod&tab=users" class="nav-tab <?php echo 'users' === $current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Moderate Users', 'apollo-core' ); ?>
				</a>
				<?php if ( $can_manage ) : ?>
				<a href="?page=apollo-mod&tab=coauthors" class="nav-tab <?php echo 'coauthors' === $current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Co-autores', 'apollo-core' ); ?>
				</a>
				<?php endif; ?>
			</nav>

			<div class="apollo-mod-content">
				<?php
				switch ( $current_tab ) {
					case 'settings':
						self::render_tab_settings();

						break;
					case 'queue':
						self::render_tab_queue();

						break;
					case 'users':
						self::render_tab_users();

						break;
					case 'coauthors':
						Apollo_Coauthors_Settings::render_tab();

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
	private static function render_tab_settings() {
		if ( ! current_user_can( 'manage_apollo_mod_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'apollo-core' ) );
		}

		$settings = get_option( 'apollo_mod_settings', array() );
		$enabled  = isset( $settings['enabled_caps'] ) ? $settings['enabled_caps'] : array();
		$mods     = isset( $settings['mods'] ) ? $settings['mods'] : array();
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="apollo-mod-settings-form">
			<?php wp_nonce_field( 'apollo_mod_settings_save', 'apollo_mod_nonce' ); ?>
			<input type="hidden" name="action" value="apollo_save_mod_settings">

			<h2><?php esc_html_e( 'Moderation Permissions', 'apollo-core' ); ?></h2>

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
							foreach ( $users as $user ) :
								$selected = in_array( $user->ID, $mods, true ) ? 'selected' : '';
								?>
								<option value="<?php echo esc_attr( $user->ID ); ?>" <?php echo esc_attr( $selected ); ?>>
									<?php echo esc_html( $user->display_name ); ?> (<?php echo esc_html( $user->user_email ); ?>)
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Hold Ctrl/Cmd to select multiple users.', 'apollo-core' ); ?></p>
					</td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'Allowed Actions', 'apollo-core' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Enable capabilities for Apollo moderators. Administrators always have full access.', 'apollo-core' ); ?></p>

			<table class="form-table">
				<?php
				$capabilities = array(
					'publish_events'      => __( 'Publish Events', 'apollo-core' ),
					'publish_locals'      => __( 'Publish Venues', 'apollo-core' ),
					'publish_djs'         => __( 'Publish DJs', 'apollo-core' ),
					'publish_nucleos'     => __( 'Publish Núcleos', 'apollo-core' ),
					'publish_comunidades' => __( 'Publish Comunidades', 'apollo-core' ),
					'edit_classifieds'    => __( 'Edit Classifieds', 'apollo-core' ),
					'edit_posts'          => __( 'Edit Social Posts', 'apollo-core' ),
				);

				foreach ( $capabilities as $cap => $label ) :
					$checked = ! empty( $enabled[ $cap ] ) ? 'checked' : '';
					?>
					<tr>
						<th scope="row">
							<label for="cap-<?php echo esc_attr( $cap ); ?>"><?php echo esc_html( $label ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" name="enabled_caps[<?php echo esc_attr( $cap ); ?>]" id="cap-<?php echo esc_attr( $cap ); ?>" value="1" <?php echo esc_attr( $checked ); ?>>
								<?php esc_html_e( 'Enable', 'apollo-core' ); ?>
							</label>
						</td>
					</tr>
				<?php endforeach; ?>

				<tr>
					<th scope="row">
						<label for="audit-log-enabled"><?php esc_html_e( 'Audit Log', 'apollo-core' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="audit_log_enabled" id="audit-log-enabled" value="1" <?php checked( ! empty( $settings['audit_log_enabled'] ) ); ?>>
							<?php esc_html_e( 'Enable audit logging for mod actions', 'apollo-core' ); ?>
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
	private static function render_tab_queue() {
		?>
		<div id="apollo-mod-queue">
			<h2><?php esc_html_e( 'Moderation Queue', 'apollo-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Review and approve pending content.', 'apollo-core' ); ?></p>

			<div class="apollo-queue-filters">
				<label>
					<input type="checkbox" id="filter-events" value="event_listing" checked> <?php esc_html_e( 'Events', 'apollo-core' ); ?>
				</label>
				<label>
					<input type="checkbox" id="filter-locals" value="event_local" checked> <?php esc_html_e( 'Venues', 'apollo-core' ); ?>
				</label>
				<label>
					<input type="checkbox" id="filter-djs" value="event_dj" checked> <?php esc_html_e( 'DJs', 'apollo-core' ); ?>
				</label>
				<label>
					<input type="checkbox" id="filter-posts" value="apollo_social_post" checked> <?php esc_html_e( 'Posts', 'apollo-core' ); ?>
				</label>
			</div>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th class="check-column"><input type="checkbox" id="select-all"></th>
						<th><?php esc_html_e( 'Thumbnail', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Title', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Type', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Author', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Date', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'apollo-core' ); ?></th>
					</tr>
				</thead>
				<tbody id="apollo-queue-tbody">
					<tr>
						<td colspan="7" style="text-align: center;">
							<span class="spinner is-active" style="float: none; margin: 0;"></span>
							<?php esc_html_e( 'Loading...', 'apollo-core' ); ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render Tab 3: Moderate Users
	 */
	private static function render_tab_users() {
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
						<th><?php esc_html_e( 'Status', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'apollo-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$users = get_users(
						array(
							'orderby' => 'registered',
							'order'   => 'DESC',
							'number'  => 50,
						)
					);

					foreach ( $users as $user ) :
						$suspended = get_user_meta( $user->ID, '_apollo_suspended_until', true );
						$blocked   = get_user_meta( $user->ID, '_apollo_blocked', true );
						$status    = 'Active';

						if ( $blocked ) {
							$status = '<span style="color: red;">Blocked</span>';
						} elseif ( $suspended && time() < intval( $suspended ) ) {
							$status = '<span style="color: orange;">Suspended</span>';
						}
						?>
						<tr>
							<td><?php echo get_avatar( $user->ID, 32 ); ?></td>
							<td><?php echo esc_html( $user->display_name ); ?></td>
							<td><?php echo esc_html( $user->user_email ); ?></td>
							<td><?php echo esc_html( implode( ', ', $user->roles ) ); ?></td>
							<td><?php echo wp_kses_post( $status ); ?></td>
							<td>
								<button class="button apollo-notify-user" data-user-id="<?php echo esc_attr( $user->ID ); ?>">
									<?php esc_html_e( 'Notify', 'apollo-core' ); ?>
								</button>
								<?php if ( current_user_can( 'suspend_users' ) && ! $blocked ) : ?>
									<button class="button apollo-suspend-user" data-user-id="<?php echo esc_attr( $user->ID ); ?>">
										<?php esc_html_e( 'Suspend', 'apollo-core' ); ?>
									</button>
								<?php endif; ?>
								<?php if ( current_user_can( 'block_users' ) ) : ?>
									<?php if ( $blocked ) : ?>
										<button class="button apollo-unblock-user" data-user-id="<?php echo esc_attr( $user->ID ); ?>">
											<?php esc_html_e( 'Unblock', 'apollo-core' ); ?>
										</button>
									<?php else : ?>
										<button class="button button-danger apollo-block-user" data-user-id="<?php echo esc_attr( $user->ID ); ?>">
											<?php esc_html_e( 'Block', 'apollo-core' ); ?>
										</button>
									<?php endif; ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Save settings
	 */
	public static function save_settings() {
		if ( ! current_user_can( 'manage_apollo_mod_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'apollo-core' ) );
		}

		check_admin_referer( 'apollo_mod_settings_save', 'apollo_mod_nonce' );

		$enabled_caps = isset( $_POST['enabled_caps'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['enabled_caps'] ) ) : array();
		$mods         = isset( $_POST['mods'] ) ? array_map( 'absint', wp_unslash( $_POST['mods'] ) ) : array();
		$audit_log    = isset( $_POST['audit_log_enabled'] ) ? true : false;

		$settings = array(
			'enabled_caps'      => $enabled_caps,
			'mods'              => $mods,
			'audit_log_enabled' => $audit_log,
		);

		update_option( 'apollo_mod_settings', $settings );

		// Update role capabilities.
		Apollo_Moderation_Roles::setup_content_type_capabilities();

		wp_safe_redirect( admin_url( 'admin.php?page=apollo-mod&tab=settings&updated=1' ) );
		exit;
	}
}
