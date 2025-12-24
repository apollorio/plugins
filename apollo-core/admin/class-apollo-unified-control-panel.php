<?php
/**
 * Apollo Unified Control Panel
 *
 * Centralized admin interface with tab system for:
 * - Memberships
 * - User Roles
 * - Permissions
 * - Statistics (with granular toggles)
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apollo Unified Control Panel
 */
class Apollo_Unified_Control_Panel {

	/**
	 * Settings option name
	 */
	const OPTION_NAME = 'apollo_unified_settings';

	/**
	 * Initialize
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Add admin menu
	 */
	public static function add_admin_menu() {
		// Changed to submenu under Apollo Cabin to organize the menu better.
		add_submenu_page(
			'apollo-cabin',
			__( 'Apollo Control', 'apollo-core' ),
			__( '🎛️ Control', 'apollo-core' ),
			'manage_options',
			'apollo-control',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register settings
	 */
	public static function register_settings() {
		register_setting( 'apollo_unified_settings', self::OPTION_NAME, array( __CLASS__, 'sanitize_settings' ) );
		register_setting( 'apollo_email_settings', 'apollo_email_config', array( __CLASS__, 'sanitize_email_settings' ) );
		register_setting( 'apollo_page_access', 'apollo_page_access_rules', array( __CLASS__, 'sanitize_page_access' ) );

		// Memberships section.
		add_settings_section(
			'apollo_memberships',
			__( 'Memberships', 'apollo-core' ),
			array( __CLASS__, 'render_memberships_section' ),
			'apollo-control'
		);

		// User Roles section.
		add_settings_section(
			'apollo_roles',
			__( 'User Roles', 'apollo-core' ),
			array( __CLASS__, 'render_roles_section' ),
			'apollo-control'
		);

		// Permissions section.
		add_settings_section(
			'apollo_permissions',
			__( 'Permissions', 'apollo-core' ),
			array( __CLASS__, 'render_permissions_section' ),
			'apollo-control'
		);

		// Statistics section.
		add_settings_section(
			'apollo_statistics',
			__( 'Public Statistics', 'apollo-core' ),
			array( __CLASS__, 'render_statistics_section' ),
			'apollo-control'
		);

		// AJAX handlers.
		add_action( 'wp_ajax_apollo_suspend_user', array( __CLASS__, 'ajax_suspend_user' ) );
		add_action( 'wp_ajax_apollo_send_notification', array( __CLASS__, 'ajax_send_notification' ) );
		add_action( 'wp_ajax_apollo_test_email', array( __CLASS__, 'ajax_test_email' ) );
		add_action( 'wp_ajax_apollo_toggle_feature', array( __CLASS__, 'ajax_toggle_feature' ) );
		add_action( 'wp_ajax_apollo_change_user_role', array( __CLASS__, 'ajax_change_user_role' ) );
	}

	/**
	 * Check if user can access a statistic
	 *
	 * @param string   $stat_key Statistic key.
	 * @param int|null $user_id User ID (optional, defaults to current user).
	 * @return bool Whether user can access the statistic.
	 */
	public static function user_can_access_statistic( $stat_key, $user_id = null ) {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		// Admins can always access.
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		$settings   = get_option( self::OPTION_NAME, array() );
		$stat_roles = $settings['statistics_roles'][ $stat_key ] ?? array();

		if ( empty( $stat_roles ) ) {
			return false;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		// Check if user has any of the allowed roles.
		foreach ( $stat_roles as $role ) {
			if ( in_array( $role, $user->roles ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if user has stealth mode (can visit profiles without notifications)
	 *
	 * @param int|null $user_id User ID (optional, defaults to current user).
	 * @return bool Whether user has stealth mode.
	 */
	public static function user_has_stealth_mode( $user_id = null ) {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		// Admins always have stealth mode.
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		$settings      = get_option( self::OPTION_NAME, array() );
		$stealth_users = $settings['privacy']['stealth_users'] ?? array();

		if ( empty( $stealth_users ) ) {
			return false;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		// Check if user has any of the stealth roles.
		foreach ( $stealth_users as $role ) {
			if ( in_array( $role, $user->roles ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if user can see a dashboard statistic
	 *
	 * @param string   $stat_key Statistic key.
	 * @param int|null $user_id User ID (optional, defaults to current user).
	 * @return bool Whether user can see the statistic on their dashboard.
	 */
	public static function user_can_see_dashboard_stat( $stat_key, $user_id = null ) {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		// Admins can see all dashboard stats.
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		$settings        = get_option( self::OPTION_NAME, array() );
		$dashboard_stats = $settings['dashboard_statistics'][ $stat_key ] ?? array();

		if ( empty( $dashboard_stats ) ) {
			return false;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		// Check if user has any of the allowed roles.
		foreach ( $dashboard_stats as $role ) {
			if ( in_array( $role, $user->roles ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Sanitize settings
	 *
	 * @param array $input Raw input.
	 * @return array Sanitized settings.
	 */
	public static function sanitize_settings( $input ) {
		$sanitized = array();

		// Sanitize memberships.
		if ( isset( $input['memberships'] ) && is_array( $input['memberships'] ) ) {
			foreach ( $input['memberships'] as $key => $value ) {
				$sanitized['memberships'][ sanitize_key( $key ) ] = (bool) $value;
			}
		}

		// Sanitize roles.
		if ( isset( $input['roles'] ) && is_array( $input['roles'] ) ) {
			foreach ( $input['roles'] as $role => $caps ) {
				$sanitized['roles'][ sanitize_key( $role ) ] = array_map( 'sanitize_key', (array) $caps );
			}
		}

		// Sanitize permissions.
		if ( isset( $input['permissions'] ) && is_array( $input['permissions'] ) ) {
			foreach ( $input['permissions'] as $key => $value ) {
				$sanitized['permissions'][ sanitize_key( $key ) ] = (bool) $value;
			}
		}

		// Sanitize statistics.
		if ( isset( $input['statistics'] ) && is_array( $input['statistics'] ) ) {
			foreach ( $input['statistics'] as $key => $value ) {
				$sanitized['statistics'][ sanitize_key( $key ) ] = (bool) $value;
			}
		}

		// Sanitize statistics roles.
		if ( isset( $input['statistics_roles'] ) && is_array( $input['statistics_roles'] ) ) {
			foreach ( $input['statistics_roles'] as $stat_key => $roles ) {
				$sanitized['statistics_roles'][ sanitize_key( $stat_key ) ] = array_map( 'sanitize_key', (array) $roles );
			}
		}

		// Sanitize features.
		if ( isset( $input['features'] ) && is_array( $input['features'] ) ) {
			foreach ( $input['features'] as $key => $value ) {
				$sanitized['features'][ sanitize_key( $key ) ] = (bool) $value;
			}
		}

		// Sanitize privacy.
		if ( isset( $input['privacy'] ) && is_array( $input['privacy'] ) ) {
			if ( isset( $input['privacy']['visit_tracking'] ) && is_array( $input['privacy']['visit_tracking'] ) ) {
				foreach ( $input['privacy']['visit_tracking'] as $role => $value ) {
					$sanitized['privacy']['visit_tracking'][ sanitize_key( $role ) ] = sanitize_key( $value );
				}
			}
			if ( isset( $input['privacy']['stealth_users'] ) && is_array( $input['privacy']['stealth_users'] ) ) {
				$sanitized['privacy']['stealth_users'] = array_map( 'sanitize_key', $input['privacy']['stealth_users'] );
			}
		}

		// Sanitize dashboard statistics.
		if ( isset( $input['dashboard_statistics'] ) && is_array( $input['dashboard_statistics'] ) ) {
			foreach ( $input['dashboard_statistics'] as $stat_key => $roles ) {
				$sanitized['dashboard_statistics'][ sanitize_key( $stat_key ) ] = array_map( 'sanitize_key', (array) $roles );
			}
		}

		return $sanitized;
	}

	/**
	 * Render main page
	 */
	public static function render_page() {
		$settings   = get_option( self::OPTION_NAME, array() );
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'memberships'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Handle permissions update.
		if ( isset( $_POST['update_permissions'] ) && isset( $_POST['apollo_role'] ) ) {
			$role_key = sanitize_key( $_POST['apollo_role'] );
			check_admin_referer( 'apollo_permissions_' . $role_key );

			if ( current_user_can( 'manage_options' ) ) {
				$role = get_role( $role_key );
				if ( $role ) {
					// Fetch capabilities from POST in a safe way, then unslash and sanitize keys.
					$capabilities_raw = filter_input( INPUT_POST, 'capabilities', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
					if ( ! is_array( $capabilities_raw ) ) {
						// Fallback to an empty array if nothing provided.
						$capabilities_raw = array();
					} else {
						// Ensure values are unslashed strings.
						$capabilities_raw = array_map(
							function ( $v ) {
								return is_scalar( $v ) ? wp_unslash( (string) $v ) : $v;
							},
							$capabilities_raw
						);
					}

					$capabilities = array();
					foreach ( (array) $capabilities_raw as $cap => $val ) {
						// Sanitize the capability key and ignore empty results.
						$cap_sanitized = sanitize_key( wp_strip_all_tags( (string) $cap ) );
						if ( '' === $cap_sanitized ) {
							continue;
						}
						$capabilities[ $cap_sanitized ] = true;
					}

					$all_permissions = self::get_all_permissions();

					foreach ( $all_permissions as $cap => $label ) {
						if ( isset( $capabilities[ $cap ] ) ) {
							$role->add_cap( $cap );
						} else {
							$role->remove_cap( $cap );
						}
					}

					add_settings_error( 'apollo_permissions', 'permissions_updated', __( 'Permissions updated successfully.', 'apollo-core' ), 'updated' );
				}
			}
		}

		?>
		<div class="wrap apollo-control-panel">
			<h1><?php esc_html_e( 'Apollo Unified Control Panel', 'apollo-core' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<a href="?page=apollo-control&tab=memberships" class="nav-tab <?php echo $active_tab === 'memberships' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Memberships', 'apollo-core' ); ?>
				</a>
				<a href="?page=apollo-control&tab=roles" class="nav-tab <?php echo $active_tab === 'roles' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'User Roles', 'apollo-core' ); ?>
				</a>
				<a href="?page=apollo-control&tab=permissions" class="nav-tab <?php echo $active_tab === 'permissions' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Permissions', 'apollo-core' ); ?>
				</a>
				<a href="?page=apollo-control&tab=statistics" class="nav-tab <?php echo $active_tab === 'statistics' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Statistics', 'apollo-core' ); ?>
				</a>
				<a href="?page=apollo-control&tab=features" class="nav-tab <?php echo $active_tab === 'features' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Features', 'apollo-core' ); ?>
				</a>
				<a href="?page=apollo-control&tab=page-access" class="nav-tab <?php echo $active_tab === 'page-access' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Page Access', 'apollo-core' ); ?>
				</a>
				<a href="?page=apollo-control&tab=users" class="nav-tab <?php echo $active_tab === 'users' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Users', 'apollo-core' ); ?>
				</a>
				<a href="?page=apollo-control&tab=emails" class="nav-tab <?php echo $active_tab === 'emails' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Emails', 'apollo-core' ); ?>
				</a>
				<a href="?page=apollo-control&tab=notifications" class="nav-tab <?php echo $active_tab === 'notifications' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Notifications', 'apollo-core' ); ?>
				</a>
				<a href="?page=apollo-control&tab=privacy" class="nav-tab <?php echo $active_tab === 'privacy' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Privacy', 'apollo-core' ); ?>
				</a>
			</nav>

			<?php if ( $active_tab !== 'permissions' ) : ?>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'apollo_unified_settings' );
				// Note: We don't call do_settings_sections() here as tabs render their own content.
				?>
			<?php endif; ?>

				<?php
				switch ( $active_tab ) {
					case 'memberships':
						self::render_memberships_tab( $settings );
						break;
					case 'roles':
						self::render_roles_tab( $settings );
						break;
					case 'permissions':
						self::render_permissions_tab( $settings );
						break;
					case 'statistics':
						self::render_statistics_tab( $settings );
						break;
					case 'features':
						self::render_features_tab( $settings );
						break;
					case 'page-access':
						self::render_page_access_tab( $settings );
						break;
					case 'users':
						self::render_users_tab( $settings );
						break;
					case 'emails':
						self::render_emails_tab( $settings );
						break;
					case 'notifications':
						self::render_notifications_tab( $settings );
						break;
					case 'privacy':
						self::render_privacy_tab( $settings );
						break;
				}

				if ( $active_tab !== 'permissions' ) {
					submit_button();
				}
				?>

			<?php if ( $active_tab !== 'permissions' ) : ?>
			</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render memberships tab
	 *
	 * @param array $settings Current settings.
	 */
	private static function render_memberships_tab( $settings ) {
		$memberships     = $settings['memberships'] ?? array();
		$all_memberships = self::get_all_memberships();

		// If no memberships found, show message.
		if ( empty( $all_memberships ) ) {
			$all_memberships = array(
				'nao_verificado' => __( 'Não Verificado', 'apollo-core' ),
				'apollo'         => __( 'Apollo', 'apollo-core' ),
				'prod'           => __( 'Prod', 'apollo-core' ),
				'dj'             => __( 'DJ', 'apollo-core' ),
				'host'           => __( 'Host', 'apollo-core' ),
				'govern'         => __( 'Govern', 'apollo-core' ),
				'business'       => __( 'Business', 'apollo-core' ),
			);
		}

		?>
		<div class="apollo-tab-content">
			<h2><?php esc_html_e( 'Membership Management', 'apollo-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Control which memberships are active and their capabilities.', 'apollo-core' ); ?></p>

			<table class="form-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Membership', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Status', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Users', 'apollo-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $all_memberships as $key => $label ) :
					$user_count = count_users();
					$role_count = $user_count['avail_roles'][ $key ] ?? 0;
					?>
					<tr>
						<th scope="row">
							<strong><?php echo esc_html( $label ); ?></strong>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php echo esc_html( $label ); ?></span></legend>
								<label class="apollo-toggle-switch" for="memberships_<?php echo esc_attr( $key ); ?>">
									<input type="checkbox"
										name="<?php echo esc_attr( self::OPTION_NAME ); ?>[memberships][<?php echo esc_attr( $key ); ?>]"
										id="memberships_<?php echo esc_attr( $key ); ?>"
										value="1"
									<?php checked( isset( $memberships[ $key ] ) && $memberships[ $key ] ); ?> />
									<span class="apollo-toggle-slider"></span>
								</label>
								<span class="ap-text-sm ap-text-muted" style="margin-left: 12px;">
								<?php echo isset( $memberships[ $key ] ) && $memberships[ $key ] ? esc_html__( 'Active', 'apollo-core' ) : esc_html__( 'Inactive', 'apollo-core' ); ?>
								</span>
							</fieldset>
						</td>
						<td>
							<span class="ap-text-sm"><?php echo esc_html( $role_count ); ?> <?php esc_html_e( 'users', 'apollo-core' ); ?></span>
							<?php if ( $role_count > 0 ) : ?>
								<a href="?page=apollo-control&tab=users&filter_role=<?php echo esc_attr( $key ); ?>" class="button button-small" style="margin-left: 8px;">
									<?php esc_html_e( 'Manage', 'apollo-core' ); ?>
								</a>
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
	 * Render roles tab
	 *
	 * @param array $settings Current settings.
	 */
	private static function render_roles_tab( $settings ) {
		$roles         = $settings['roles'] ?? array();
		$all_roles     = wp_roles()->roles;
		$selected_role = isset( $_GET['edit_role'] ) ? sanitize_key( $_GET['edit_role'] ) : '';

		?>
		<div class="apollo-tab-content">
			<h2><?php esc_html_e( 'User Roles Management', 'apollo-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'View and manage capabilities for each user role.', 'apollo-core' ); ?></p>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width: 200px;"><?php esc_html_e( 'Role', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Capabilities Count', 'apollo-core' ); ?></th>
						<th style="width: 100px;"><?php esc_html_e( 'Users', 'apollo-core' ); ?></th>
						<th style="width: 150px;"><?php esc_html_e( 'Actions', 'apollo-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				$user_count = count_users();
				foreach ( $all_roles as $role_key => $role_data ) :
					$role_obj      = get_role( $role_key );
					$caps_count    = $role_obj ? count( array_filter( $role_obj->capabilities ) ) : 0;
					$users_in_role = $user_count['avail_roles'][ $role_key ] ?? 0;
					?>
					<tr>
						<td>
							<strong><?php echo esc_html( translate_user_role( $role_data['name'] ) ); ?></strong>
							<br><code class="ap-text-xs"><?php echo esc_html( $role_key ); ?></code>
						</td>
						<td>
							<span class="ap-text-sm"><?php echo esc_html( $caps_count ); ?> <?php esc_html_e( 'capabilities', 'apollo-core' ); ?></span>
						</td>
						<td>
							<span class="ap-text-sm"><?php echo esc_html( $users_in_role ); ?></span>
						</td>
						<td>
							<a href="
							<?php
							echo esc_url(
								add_query_arg(
									array(
										'page' => 'apollo-control',
										'tab'  => 'permissions',
										'role' => $role_key,
									),
									admin_url( 'admin.php' )
								)
							);
							?>
										" class="button button-small">
								<?php esc_html_e( 'Edit Permissions', 'apollo-core' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render permissions tab
	 *
	 * @param array $settings Current settings.
	 */
	private static function render_permissions_tab( $settings ) {
		$selected_role   = isset( $_GET['role'] ) ? sanitize_key( $_GET['role'] ) : 'subscriber';
		$role_object     = get_role( $selected_role );
		$all_permissions = self::get_all_permissions();

		// Get all roles.
		$roles      = wp_roles()->roles;
		$user_count = count_users();
		?>
		<div class="apollo-tab-content">
			<h2><?php esc_html_e( 'Permission Management', 'apollo-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Control access to Apollo features by role.', 'apollo-core' ); ?></p>

			<div class="apollo-role-selector" style="background: #fff; border: 1px solid var(--ap-border-color, #e0e0e0); border-radius: var(--ap-radius-md, 8px); padding: 20px; margin-bottom: 24px;">
				<form method="get" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
					<input type="hidden" name="page" value="apollo-control">
					<input type="hidden" name="tab" value="permissions">
					<label for="role_select" style="font-weight: 500;">
						<span class="dashicons dashicons-admin-users" style="vertical-align: middle; color: var(--ap-orange-500, #ff6925);"></span>
						<?php esc_html_e( 'Select Role:', 'apollo-core' ); ?>
					</label>
					<select name="role" id="role_select" onchange="this.form.submit()" style="min-width: 200px;">
						<?php
						foreach ( $roles as $role_key => $role_data ) :
							$users_in_role = $user_count['avail_roles'][ $role_key ] ?? 0;
							?>
							<option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( $selected_role, $role_key ); ?>>
								<?php echo esc_html( translate_user_role( $role_data['name'] ) . ' (' . $users_in_role . ' users)' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</form>
			</div>

			<?php
			if ( $role_object ) :
				$role_display = translate_user_role( $roles[ $selected_role ]['name'] );
				?>
				<div class="apollo-permissions-editor" style="background: #fff; border: 1px solid var(--ap-border-color, #e0e0e0); border-radius: var(--ap-radius-md, 8px); padding: 24px;">
					<h3 style="margin-top: 0;">
						<?php
						/* translators: %s: Role name */
						printf( esc_html__( 'Permissions for %s', 'apollo-core' ), '<strong>' . esc_html( $role_display ) . '</strong>' );
						?>
					</h3>

					<form method="post" action="">
						<?php wp_nonce_field( 'apollo_permissions_' . $selected_role ); ?>
						<input type="hidden" name="apollo_role" value="<?php echo esc_attr( $selected_role ); ?>">

						<div class="apollo-permissions-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px; margin-bottom: 24px;">
							<?php
							foreach ( $all_permissions as $cap => $label ) :
								$has_cap = $role_object->has_cap( $cap );
								?>
								<div class="apollo-permission-item" style="background: <?php echo $has_cap ? 'rgba(40, 167, 69, 0.05)' : '#f9f9f9'; ?>; border: 1px solid <?php echo $has_cap ? 'rgba(40, 167, 69, 0.2)' : '#e0e0e0'; ?>; border-radius: var(--ap-radius-md, 8px); padding: 12px 16px; display: flex; justify-content: space-between; align-items: center;">
									<label for="cap_<?php echo esc_attr( $cap ); ?>" style="cursor: pointer; flex: 1;">
										<?php echo esc_html( $label ); ?>
									</label>
									<label class="apollo-toggle-switch" for="cap_<?php echo esc_attr( $cap ); ?>">
										<input type="checkbox"
											name="capabilities[<?php echo esc_attr( $cap ); ?>]"
											id="cap_<?php echo esc_attr( $cap ); ?>"
											value="1"
											<?php checked( $has_cap ); ?>>
										<span class="apollo-toggle-slider"></span>
									</label>
								</div>
							<?php endforeach; ?>
						</div>

						<div class="apollo-form-actions" style="display: flex; gap: 12px; padding-top: 16px; border-top: 1px solid var(--ap-border-color, #e0e0e0);">
							<button type="submit" name="update_permissions" class="button button-primary">
								<span class="dashicons dashicons-saved" style="vertical-align: middle; margin-right: 4px;"></span>
								<?php esc_html_e( 'Save Permissions', 'apollo-core' ); ?>
							</button>
							<button type="button" class="button apollo-select-all-caps" onclick="jQuery('.apollo-permissions-grid input[type=checkbox]').prop('checked', true);">
								<?php esc_html_e( 'Select All', 'apollo-core' ); ?>
							</button>
							<button type="button" class="button apollo-deselect-all-caps" onclick="jQuery('.apollo-permissions-grid input[type=checkbox]').prop('checked', false);">
								<?php esc_html_e( 'Deselect All', 'apollo-core' ); ?>
							</button>
						</div>
					</form>
				</div>
			<?php else : ?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Role not found.', 'apollo-core' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render statistics tab
	 *
	 * @param array $settings Current settings.
	 */
	private static function render_statistics_tab( $settings ) {
		$statistics     = $settings['statistics'] ?? array();
		$stat_roles     = $settings['statistics_roles'] ?? array();
		$all_statistics = self::get_all_statistics();
		$all_roles      = self::get_all_memberships();

		// Group statistics by category.
		$stat_groups = array(
			'views'             => array(
				'title' => __( 'View Statistics', 'apollo-core' ),
				'icon'  => 'visibility',
				'items' => array( 'event_views', 'dj_views', 'local_views', 'classified_views', 'group_views', 'document_views', 'user_profile_views' ),
			),
			'event_clicks'      => array(
				'title' => __( 'Event Click Statistics', 'apollo-core' ),
				'icon'  => 'calendar-alt',
				'items' => array( 'event_clicks', 'event_clicks_share', 'event_clicks_ticket', 'event_clicks_other' ),
			),
			'dj_clicks'         => array(
				'title' => __( 'DJ Profile Click Statistics', 'apollo-core' ),
				'icon'  => 'album',
				'items' => array( 'dj_clicks', 'dj_clicks_media_kit', 'dj_clicks_play_vinyl', 'dj_clicks_social', 'dj_clicks_read_more' ),
			),
			'classified_clicks' => array(
				'title' => __( 'Classified Click Statistics', 'apollo-core' ),
				'icon'  => 'megaphone',
				'items' => array( 'classified_clicks', 'classified_clicks_inbox_chat' ),
			),
			'other_clicks'      => array(
				'title' => __( 'Other Click Statistics', 'apollo-core' ),
				'icon'  => 'admin-links',
				'items' => array( 'local_clicks', 'group_clicks', 'document_clicks' ),
			),
			'counts'            => array(
				'title' => __( 'Count Statistics', 'apollo-core' ),
				'icon'  => 'chart-bar',
				'items' => array( 'group_members', 'document_downloads', 'user_followers', 'user_posts' ),
			),
			'totals'            => array(
				'title' => __( 'Total Counts', 'apollo-core' ),
				'icon'  => 'dashboard',
				'items' => array( 'total_events', 'total_djs', 'total_locals', 'total_classifieds', 'total_groups', 'total_users' ),
			),
			'device_platform'   => array(
				'title' => __( 'Device & Platform Analytics', 'apollo-core' ),
				'icon'  => 'smartphone',
				'items' => array( 'pct_android', 'pct_ios', 'pct_desktop', 'pct_tablet', 'screen_mobile_sm', 'screen_mobile_lg', 'screen_tablet', 'screen_desktop', 'screen_large' ),
			),
			'user_activity'     => array(
				'title' => __( 'User Activity Tracking', 'apollo-core' ),
				'icon'  => 'admin-users',
				'items' => array( 'logged_in_users', 'guest_visitors', 'user_session_duration', 'user_page_depth', 'user_return_rate' ),
			),
			'internal_metrics'  => array(
				'title' => __( 'Internal Admin Metrics (Secure)', 'apollo-core' ),
				'icon'  => 'lock',
				'items' => array( 'top_message_senders', 'top_active_groups', 'most_visited_pages', 'peak_usage_hours', 'conversion_rates', 'engagement_scores', 'retention_metrics', 'churn_indicators' ),
			),
		);

		?>
		<div class="apollo-tab-content">
			<h2><?php esc_html_e( 'Statistics Access Control', 'apollo-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Control which statistics are visible to the public and which user roles can access them.', 'apollo-core' ); ?></p>

			<?php foreach ( $stat_groups as $group_key => $group ) : ?>
				<div class="apollo-stat-group" style="margin-bottom: 30px;">
					<h3 style="display: flex; align-items: center; gap: 8px;">
						<span class="dashicons dashicons-<?php echo esc_attr( $group['icon'] ); ?>" style="color: var(--ap-orange-500, #ff6925);"></span>
						<?php echo esc_html( $group['title'] ); ?>
					</h3>
					<div class="apollo-stat-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 12px;">
						<?php
						foreach ( $group['items'] as $key ) :
							if ( ! isset( $all_statistics[ $key ] ) ) {
								continue;
							}
							$label         = $all_statistics[ $key ];
							$is_visible    = isset( $statistics[ $key ] ) && $statistics[ $key ];
							$allowed_roles = $stat_roles[ $key ] ?? array();
							?>
							<div class="apollo-stat-item" style="background: #fff; border: 1px solid var(--ap-border-color, #e0e0e0); border-radius: var(--ap-radius-md, 8px); padding: 16px;">
								<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
									<strong><?php echo esc_html( $label ); ?></strong>
									<label class="apollo-toggle-switch" for="statistics_<?php echo esc_attr( $key ); ?>">
										<input type="checkbox"
											name="<?php echo esc_attr( self::OPTION_NAME ); ?>[statistics][<?php echo esc_attr( $key ); ?>]"
											id="statistics_<?php echo esc_attr( $key ); ?>"
											value="1"
											<?php checked( $is_visible ); ?>>
										<span class="apollo-toggle-slider"></span>
									</label>
								</div>
								<div style="font-size: 12px; color: #666; margin-bottom: 8px;">
									<?php esc_html_e( 'Public visibility', 'apollo-core' ); ?>
								</div>
								<div>
									<label style="display: block; font-size: 12px; font-weight: 600; color: #333; margin-bottom: 4px;">
										<?php esc_html_e( 'Allowed User Roles:', 'apollo-core' ); ?>
									</label>
									<div style="max-height: 120px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 8px; background: #f9f9f9;">
										<?php foreach ( $all_roles as $role_key => $role_label ) : ?>
											<label style="display: block; font-size: 11px; margin-bottom: 2px;">
												<input type="checkbox"
													name="<?php echo esc_attr( self::OPTION_NAME ); ?>[statistics_roles][<?php echo esc_attr( $key ); ?>][]"
													value="<?php echo esc_attr( $role_key ); ?>"
													<?php checked( in_array( $role_key, $allowed_roles ) ); ?>>
												<?php echo esc_html( $role_label ); ?>
											</label>
										<?php endforeach; ?>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Get all memberships
	 *
	 * @return array Membership list.
	 */
	private static function get_all_memberships() {
		if ( ! function_exists( 'apollo_get_memberships' ) ) {
			return array();
		}

		$memberships = apollo_get_memberships();
		$list        = array();

		foreach ( $memberships as $id => $membership ) {
			$list[ $id ] = $membership['label'] ?? $id;
		}

		return $list;
	}

	/**
	 * Get all permissions
	 *
	 * @return array Permission list.
	 */
	private static function get_all_permissions() {
		return array(
			'view_events'        => __( 'View Events', 'apollo-core' ),
			'create_events'      => __( 'Create Events', 'apollo-core' ),
			'edit_events'        => __( 'Edit Events', 'apollo-core' ),
			'delete_events'      => __( 'Delete Events', 'apollo-core' ),
			'view_djs'           => __( 'View DJs', 'apollo-core' ),
			'create_djs'         => __( 'Create DJs', 'apollo-core' ),
			'view_locals'        => __( 'View Venues', 'apollo-core' ),
			'create_locals'      => __( 'Create Venues', 'apollo-core' ),
			'view_classifieds'   => __( 'View Classifieds', 'apollo-core' ),
			'create_classifieds' => __( 'Create Classifieds', 'apollo-core' ),
			'view_groups'        => __( 'View Groups', 'apollo-core' ),
			'create_groups'      => __( 'Create Groups', 'apollo-core' ),
			'view_documents'     => __( 'View Documents', 'apollo-core' ),
			'create_documents'   => __( 'Create Documents', 'apollo-core' ),
			'moderate_content'   => __( 'Moderate Content', 'apollo-core' ),
			'view_analytics'     => __( 'View Analytics', 'apollo-core' ),
		);
	}

	/**
	 * Get all statistics
	 *
	 * @return array Statistics list.
	 */
	private static function get_all_statistics() {
		return array(
			// View Statistics
			'event_views'                  => __( 'Event Views', 'apollo-core' ),
			'dj_views'                     => __( 'DJ Profile Views', 'apollo-core' ),
			'local_views'                  => __( 'Venue Views', 'apollo-core' ),
			'classified_views'             => __( 'Classified Views', 'apollo-core' ),
			'group_views'                  => __( 'Group Views', 'apollo-core' ),
			'document_views'               => __( 'Document Views', 'apollo-core' ),
			'user_profile_views'           => __( 'User Profile Views', 'apollo-core' ),

			// Event Click Statistics
			'event_clicks'                 => __( 'Event Clicks (Total)', 'apollo-core' ),
			'event_clicks_share'           => __( 'Event Clicks to Share', 'apollo-core' ),
			'event_clicks_ticket'          => __( 'Event Clicks to Ticket', 'apollo-core' ),
			'event_clicks_other'           => __( 'Event Clicks to Other', 'apollo-core' ),

			// DJ Profile Click Statistics
			'dj_clicks'                    => __( 'DJ Profile Clicks (Total)', 'apollo-core' ),
			'dj_clicks_media_kit'          => __( 'DJ Clicks on Media Kit', 'apollo-core' ),
			'dj_clicks_play_vinyl'         => __( 'DJ Clicks on Play (Vinyl)', 'apollo-core' ),
			'dj_clicks_social'             => __( 'DJ Clicks on Social Buttons', 'apollo-core' ),
			'dj_clicks_read_more'          => __( 'DJ Clicks on Read More/Load More', 'apollo-core' ),

			// Classified Click Statistics
			'classified_clicks'            => __( 'Classified Clicks (Total)', 'apollo-core' ),
			'classified_clicks_inbox_chat' => __( 'Classified Clicks to Inbox Chat', 'apollo-core' ),

			// Other Click Statistics
			'local_clicks'                 => __( 'Venue Clicks', 'apollo-core' ),
			'group_clicks'                 => __( 'Group Clicks', 'apollo-core' ),
			'document_clicks'              => __( 'Document Clicks', 'apollo-core' ),

			// Count Statistics
			'group_members'                => __( 'Group Members Count', 'apollo-core' ),
			'document_downloads'           => __( 'Document Downloads', 'apollo-core' ),
			'user_followers'               => __( 'User Followers Count', 'apollo-core' ),
			'user_posts'                   => __( 'User Posts Count', 'apollo-core' ),

			// Total Counts
			'total_events'                 => __( 'Total Events Count', 'apollo-core' ),
			'total_djs'                    => __( 'Total DJs Count', 'apollo-core' ),
			'total_locals'                 => __( 'Total Venues Count', 'apollo-core' ),
			'total_classifieds'            => __( 'Total Classifieds Count', 'apollo-core' ),
			'total_groups'                 => __( 'Total Groups Count', 'apollo-core' ),
			'total_users'                  => __( 'Total Users Count', 'apollo-core' ),

			// Device & Platform Analytics
			'pct_android'                  => __( '% Android Devices', 'apollo-core' ),
			'pct_ios'                      => __( '% iOS/iPhone Devices', 'apollo-core' ),
			'pct_desktop'                  => __( '% Desktop Users', 'apollo-core' ),
			'pct_tablet'                   => __( '% Tablet Users', 'apollo-core' ),
			'screen_mobile_sm'             => __( 'Screen: Mobile Small (< 375px)', 'apollo-core' ),
			'screen_mobile_lg'             => __( 'Screen: Mobile Large (375-767px)', 'apollo-core' ),
			'screen_tablet'                => __( 'Screen: Tablet (768-1023px)', 'apollo-core' ),
			'screen_desktop'               => __( 'Screen: Desktop (1024-1439px)', 'apollo-core' ),
			'screen_large'                 => __( 'Screen: Large Desktop (1440px+)', 'apollo-core' ),

			// User Activity Tracking
			'logged_in_users'              => __( 'Logged-in Users Sessions', 'apollo-core' ),
			'guest_visitors'               => __( 'Guest Visitor Sessions', 'apollo-core' ),
			'user_session_duration'        => __( 'Avg Session Duration', 'apollo-core' ),
			'user_page_depth'              => __( 'Avg Pages per Session', 'apollo-core' ),
			'user_return_rate'             => __( 'User Return Rate %', 'apollo-core' ),

			// Internal Admin Metrics (Secure Data)
			'top_message_senders'          => __( 'Top Message Senders', 'apollo-core' ),
			'top_active_groups'            => __( 'Most Active Groups', 'apollo-core' ),
			'most_visited_pages'           => __( 'Most Visited Pages', 'apollo-core' ),
			'peak_usage_hours'             => __( 'Peak Usage Hours', 'apollo-core' ),
			'conversion_rates'             => __( 'Conversion Rates', 'apollo-core' ),
			'engagement_scores'            => __( 'User Engagement Scores', 'apollo-core' ),
			'retention_metrics'            => __( 'Retention Metrics', 'apollo-core' ),
			'churn_indicators'             => __( 'Churn Risk Indicators', 'apollo-core' ),
		);
	}

	/**
	 * Render features tab
	 *
	 * @param array $settings Current settings.
	 */
	private static function render_features_tab( $settings ) {
		$features     = $settings['features'] ?? array();
		$all_features = self::get_all_features();

		// Group features by category.
		$feature_groups = array(
			'core'    => array(
				'title'    => __( 'Core Features', 'apollo-core' ),
				'features' => array( 'events_manager', 'social_features', 'user_pages', 'classifieds', 'groups', 'documents' ),
			),
			'content' => array(
				'title'    => __( 'Content Features', 'apollo-core' ),
				'features' => array( 'signatures', 'badges', 'memberships', 'canvas_builder', 'plano_editor' ),
			),
			'system'  => array(
				'title'    => __( 'System Features', 'apollo-core' ),
				'features' => array( 'notifications', 'analytics', 'moderation', 'quiz_system', 'forms' ),
			),
		);

		?>
		<div class="apollo-tab-content">
			<h2><?php esc_html_e( 'Feature Control', 'apollo-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Activate or deactivate Apollo features, blocks, and functions.', 'apollo-core' ); ?></p>

			<?php foreach ( $feature_groups as $group_key => $group ) : ?>
				<div class="apollo-feature-group" style="margin-bottom: 30px;">
					<h3><?php echo esc_html( $group['title'] ); ?></h3>
					<div class="apollo-features-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">
						<?php
						foreach ( $group['features'] as $key ) :
							if ( ! isset( $all_features[ $key ] ) ) {
								continue;
							}
							$label      = $all_features[ $key ];
							$is_enabled = isset( $features[ $key ] ) && $features[ $key ];
							?>
							<div class="apollo-feature-card" style="background: #fff; border: 1px solid var(--ap-border-color, #e0e0e0); border-radius: var(--ap-radius-md, 8px); padding: 16px;">
								<div style="display: flex; justify-content: space-between; align-items: center;">
									<div>
										<strong><?php echo esc_html( $label ); ?></strong>
									</div>
									<label class="apollo-toggle-switch" for="features_<?php echo esc_attr( $key ); ?>">
										<input type="checkbox"
											name="<?php echo esc_attr( self::OPTION_NAME ); ?>[features][<?php echo esc_attr( $key ); ?>]"
											id="features_<?php echo esc_attr( $key ); ?>"
											value="1"
											<?php checked( $is_enabled ); ?>
											class="apollo-feature-toggle"
											data-feature="<?php echo esc_attr( $key ); ?>">
										<span class="apollo-toggle-slider"></span>
									</label>
								</div>
								<span class="ap-text-sm ap-text-muted" style="display: block; margin-top: 8px;">
									<?php echo $is_enabled ? esc_html__( 'Enabled', 'apollo-core' ) : esc_html__( 'Disabled', 'apollo-core' ); ?>
								</span>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render page access tab
	 *
	 * @param array $settings Current settings.
	 */
	private static function render_page_access_tab( $settings ) {
		$page_access = get_option( 'apollo_page_access_rules', array() );
		$all_pages   = get_pages( array( 'number' => 50 ) );
		$all_roles   = wp_roles()->get_names();

		?>
		<div class="apollo-tab-content">
			<h2><?php esc_html_e( 'Page Access Control', 'apollo-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Control which roles and users can access specific pages.', 'apollo-core' ); ?></p>

			<table class="wp-list-table widefat fixed striped" style="table-layout: auto;">
				<thead>
					<tr>
						<th style="width: 200px;"><?php esc_html_e( 'Page', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Allowed Roles', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Forbidden Roles', 'apollo-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $all_pages ) ) : ?>
						<tr>
							<td colspan="3"><?php esc_html_e( 'No pages found.', 'apollo-core' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $all_pages as $page ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( $page->post_title ); ?></strong>
									<br><code class="ap-text-xs">/<?php echo esc_html( $page->post_name ); ?></code>
								</td>
								<td>
									<div class="apollo-checkbox-grid" style="display: flex; flex-wrap: wrap; gap: 8px;">
										<?php foreach ( $all_roles as $role_key => $role_name ) : ?>
											<label class="apollo-inline-checkbox" style="display: inline-flex; align-items: center; background: #f9f9f9; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
												<input type="checkbox"
													name="apollo_page_access_rules[<?php echo esc_attr( $page->ID ); ?>][allowed][<?php echo esc_attr( $role_key ); ?>]"
													value="1"
													<?php checked( isset( $page_access[ $page->ID ]['allowed'][ $role_key ] ) ); ?>
													style="margin-right: 4px;">
												<span><?php echo esc_html( $role_name ); ?></span>
											</label>
										<?php endforeach; ?>
									</div>
								</td>
								<td>
									<div class="apollo-checkbox-grid" style="display: flex; flex-wrap: wrap; gap: 8px;">
										<?php foreach ( $all_roles as $role_key => $role_name ) : ?>
											<label class="apollo-inline-checkbox" style="display: inline-flex; align-items: center; background: #fff5f5; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
												<input type="checkbox"
													name="apollo_page_access_rules[<?php echo esc_attr( $page->ID ); ?>][forbidden][<?php echo esc_attr( $role_key ); ?>]"
													value="1"
													<?php checked( isset( $page_access[ $page->ID ]['forbidden'][ $role_key ] ) ); ?>
													style="margin-right: 4px;">
												<span><?php echo esc_html( $role_name ); ?></span>
											</label>
										<?php endforeach; ?>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render users tab
	 *
	 * @param array $settings Current settings.
	 */
	private static function render_users_tab( $settings ) {
		$paged       = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$per_page    = 20;
		$filter_role = isset( $_GET['filter_role'] ) ? sanitize_key( $_GET['filter_role'] ) : '';

		$user_args = array(
			'number' => $per_page,
			'offset' => ( $paged - 1 ) * $per_page,
		);

		if ( ! empty( $filter_role ) ) {
			$user_args['role'] = $filter_role;
		}

		$users       = get_users( $user_args );
		$total_users = count_users();
		$total       = ! empty( $filter_role ) && isset( $total_users['avail_roles'][ $filter_role ] ) ? $total_users['avail_roles'][ $filter_role ] : $total_users['total_users'];
		$total_pages = ceil( $total / $per_page );

		?>
		<div class="apollo-tab-content">
			<h2><?php esc_html_e( 'User Management', 'apollo-core' ); ?></h2>
			<p class="description">
				<?php
				if ( ! empty( $filter_role ) ) {
					$role_name = self::get_all_memberships()[ $filter_role ] ?? $filter_role;
					echo esc_html( sprintf( __( 'Showing users with role: %s', 'apollo-core' ), $role_name ) );
					?>
					<a href="?page=apollo-control&tab=users" class="button button-small" style="margin-left: 10px;">
						<?php esc_html_e( 'Show All Users', 'apollo-core' ); ?>
					</a>
					<?php
				} else {
					esc_html_e( 'Suspend users and manage user access.', 'apollo-core' );
				}
				?>
			</p>

			<div class="tablenav top">
				<div class="tablenav-pages">
					<span class="displaying-num"><?php echo esc_html( sprintf( __( '%s users', 'apollo-core' ), $total ) ); ?></span>
					<?php if ( $total_pages > 1 ) : ?>
						<span class="pagination-links">
							<?php if ( $paged > 1 ) : ?>
								<a class="prev-page button" href="<?php echo esc_url( add_query_arg( 'paged', $paged - 1 ) ); ?>">‹</a>
							<?php endif; ?>
							<span class="paging-input"><?php echo esc_html( $paged ); ?> / <?php echo esc_html( $total_pages ); ?></span>
							<?php if ( $paged < $total_pages ) : ?>
								<a class="next-page button" href="<?php echo esc_url( add_query_arg( 'paged', $paged + 1 ) ); ?>">›</a>
							<?php endif; ?>
						</span>
					<?php endif; ?>
				</div>
			</div>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width: 180px;"><?php esc_html_e( 'User', 'apollo-core' ); ?></th>
						<th style="width: 200px;"><?php esc_html_e( 'Email', 'apollo-core' ); ?></th>
						<th style="width: 200px;"><?php esc_html_e( 'Role / Membership', 'apollo-core' ); ?></th>
						<th style="width: 150px;"><?php esc_html_e( 'Status', 'apollo-core' ); ?></th>
						<th style="width: 180px;"><?php esc_html_e( 'Actions', 'apollo-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $users as $user ) :
						$is_suspended     = get_user_meta( $user->ID, '_apollo_suspended_until', true );
						$suspended_until  = $is_suspended ? absint( $is_suspended ) : 0;
						$is_suspended_now = $suspended_until > time();
						?>
						<tr>
							<td>
								<?php echo get_avatar( $user->ID, 32, '', '', array( 'style' => 'border-radius: 50%; vertical-align: middle; margin-right: 8px;' ) ); ?>
								<strong><?php echo esc_html( $user->display_name ); ?></strong>
							</td>
							<td>
								<a href="mailto:<?php echo esc_attr( $user->user_email ); ?>"><?php echo esc_html( $user->user_email ); ?></a>
							</td>
							<td>
								<div class="apollo-role-inline-editor">
									<select class="apollo-change-role"
										data-user-id="<?php echo esc_attr( $user->ID ); ?>"
										data-current-role="<?php echo esc_attr( $user->roles[0] ?? '' ); ?>"
										style="width: 140px;">
										<?php
										$all_roles = wp_roles()->roles;
										$user_role = $user->roles[0] ?? '';
										foreach ( $all_roles as $role_key => $role_data ) :
											?>
											<option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( $user_role, $role_key ); ?>>
												<?php echo esc_html( translate_user_role( $role_data['name'] ) ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<span class="apollo-role-saving" style="display: none; color: var(--ap-orange-500);">
										<span class="dashicons dashicons-update spin"></span>
									</span>
									<span class="apollo-role-saved" style="display: none; color: #46b450;">
										<span class="dashicons dashicons-yes"></span>
									</span>
								</div>
							</td>
							<td>
								<?php if ( $is_suspended_now ) : ?>
									<span class="ap-status-badge ap-status-error">
										<?php esc_html_e( 'Suspended', 'apollo-core' ); ?>
									</span>
									<br><span class="ap-text-xs ap-text-muted"><?php esc_html_e( 'Until', 'apollo-core' ); ?>: <?php echo esc_html( date_i18n( get_option( 'date_format' ), $suspended_until ) ); ?></span>
								<?php else : ?>
									<span class="ap-status-badge ap-status-success"><?php esc_html_e( 'Active', 'apollo-core' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<button type="button"
									class="button button-small apollo-suspend-user"
									data-user-id="<?php echo esc_attr( $user->ID ); ?>"
									data-user-name="<?php echo esc_attr( $user->display_name ); ?>"
									data-is-suspended="<?php echo $is_suspended_now ? '1' : '0'; ?>">
									<?php echo $is_suspended_now ? esc_html__( 'Unsuspend', 'apollo-core' ) : esc_html__( 'Suspend', 'apollo-core' ); ?>
								</button>
								<a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>" class="button button-small">
									<?php esc_html_e( 'Edit', 'apollo-core' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render emails tab
	 *
	 * @param array $settings Current settings.
	 */
	private static function render_emails_tab( $settings ) {
		$email_config = get_option( 'apollo_email_config', array() );
		$email_types  = self::get_email_types();

		?>
		<div class="apollo-tab-content">
			<h2><?php esc_html_e( 'Email Configuration', 'apollo-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Configure sender emails for each email type and test emails.', 'apollo-core' ); ?></p>

			<div class="apollo-email-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 20px; margin-bottom: 30px;">
				<?php
				foreach ( $email_types as $key => $label ) :
					$sender = $email_config['senders'][ $key ] ?? get_option( 'admin_email' );
					?>
					<div class="apollo-email-card" style="background: #fff; border: 1px solid var(--ap-border-color, #e0e0e0); border-radius: var(--ap-radius-md, 8px); padding: 20px;">
						<h4 style="margin: 0 0 12px 0; display: flex; align-items: center; gap: 8px;">
							<span class="dashicons dashicons-email" style="color: var(--ap-orange-500, #ff6925);"></span>
							<?php echo esc_html( $label ); ?>
						</h4>
						<div style="display: flex; gap: 8px; align-items: center;">
							<input type="email"
								name="apollo_email_config[senders][<?php echo esc_attr( $key ); ?>]"
								id="email_sender_<?php echo esc_attr( $key ); ?>"
								value="<?php echo esc_attr( $sender ); ?>"
								class="regular-text"
								style="flex: 1;">
							<button type="button"
								class="button apollo-test-email"
								data-email-type="<?php echo esc_attr( $key ); ?>"
								title="<?php esc_attr_e( 'Send test email', 'apollo-core' ); ?>">
								<span class="dashicons dashicons-email-alt" style="vertical-align: middle;"></span>
							</button>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="apollo-email-test-section" style="background: #f9f9f9; border-radius: var(--ap-radius-md, 8px); padding: 24px; margin-top: 30px;">
				<h3 style="margin-top: 0;"><?php esc_html_e( 'Send Test Email', 'apollo-core' ); ?></h3>
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; max-width: 600px;">
					<div>
						<label for="test_email_to" style="display: block; margin-bottom: 4px; font-weight: 500;"><?php esc_html_e( 'To', 'apollo-core' ); ?></label>
						<input type="email" id="test_email_to" class="regular-text" style="width: 100%;" placeholder="<?php esc_attr_e( 'email@example.com', 'apollo-core' ); ?>">
					</div>
					<div>
						<label for="test_email_type" style="display: block; margin-bottom: 4px; font-weight: 500;"><?php esc_html_e( 'Email Type', 'apollo-core' ); ?></label>
						<select id="test_email_type" class="regular-text" style="width: 100%;">
							<?php foreach ( $email_types as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<button type="button" class="button button-primary apollo-send-test-email" style="margin-top: 16px;">
					<span class="dashicons dashicons-email" style="vertical-align: middle; margin-right: 4px;"></span>
					<?php esc_html_e( 'Send Test Email', 'apollo-core' ); ?>
				</button>
				<div id="apollo-test-email-result" style="margin-top: 12px;"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render notifications tab
	 *
	 * @param array $settings Current settings.
	 */
	private static function render_notifications_tab( $settings ) {
		$notifications = $settings['notifications'] ?? array();
		$all_roles     = wp_roles()->get_names();
		?>
		<div class="apollo-tab-content">
			<h2><?php esc_html_e( 'Notification Management', 'apollo-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Send notifications to all users or specific users.', 'apollo-core' ); ?></p>

			<div class="apollo-notification-composer" style="background: #fff; border: 1px solid var(--ap-border-color, #e0e0e0); border-radius: var(--ap-radius-md, 8px); padding: 24px; max-width: 700px;">
				<h3 style="margin-top: 0; display: flex; align-items: center; gap: 8px;">
					<span class="dashicons dashicons-megaphone" style="color: var(--ap-orange-500, #ff6925);"></span>
					<?php esc_html_e( 'Send Notification', 'apollo-core' ); ?>
				</h3>

				<div class="apollo-form-group" style="margin-bottom: 16px;">
					<label for="notification_recipient" style="display: block; margin-bottom: 4px; font-weight: 500;"><?php esc_html_e( 'Recipient', 'apollo-core' ); ?></label>
					<select id="notification_recipient" class="regular-text" style="width: 100%;">
						<option value="all"><?php esc_html_e( 'All Users', 'apollo-core' ); ?></option>
						<option value="specific"><?php esc_html_e( 'Specific User', 'apollo-core' ); ?></option>
						<option value="role"><?php esc_html_e( 'By Role', 'apollo-core' ); ?></option>
					</select>
				</div>

				<div id="notification_user_row" class="apollo-form-group" style="margin-bottom: 16px; display: none;">
					<label for="notification_user_id" style="display: block; margin-bottom: 4px; font-weight: 500;"><?php esc_html_e( 'User', 'apollo-core' ); ?></label>
					<select id="notification_user_id" class="regular-text" style="width: 100%;">
						<?php
						$users = get_users( array( 'number' => 100 ) );
						foreach ( $users as $user ) {
							echo '<option value="' . esc_attr( $user->ID ) . '">' . esc_html( $user->display_name . ' (' . $user->user_email . ')' ) . '</option>';
						}
						?>
					</select>
				</div>

				<div id="notification_role_row" class="apollo-form-group" style="margin-bottom: 16px; display: none;">
					<label for="notification_role" style="display: block; margin-bottom: 4px; font-weight: 500;"><?php esc_html_e( 'Role', 'apollo-core' ); ?></label>
					<select id="notification_role" class="regular-text" style="width: 100%;">
						<?php foreach ( $all_roles as $role_key => $role_name ) : ?>
							<option value="<?php echo esc_attr( $role_key ); ?>"><?php echo esc_html( $role_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="apollo-form-group" style="margin-bottom: 16px;">
					<label for="notification_title" style="display: block; margin-bottom: 4px; font-weight: 500;"><?php esc_html_e( 'Title', 'apollo-core' ); ?></label>
					<input type="text" id="notification_title" class="regular-text" style="width: 100%;" required>
				</div>

				<div class="apollo-form-group" style="margin-bottom: 16px;">
					<label for="notification_message" style="display: block; margin-bottom: 4px; font-weight: 500;"><?php esc_html_e( 'Message', 'apollo-core' ); ?></label>
					<textarea id="notification_message" class="large-text" rows="5" style="width: 100%;" required></textarea>
				</div>

				<div class="apollo-form-actions" style="display: flex; gap: 8px; align-items: center;">
					<button type="button" class="button button-primary apollo-send-notification">
						<span class="dashicons dashicons-bell" style="vertical-align: middle; margin-right: 4px;"></span>
						<?php esc_html_e( 'Send Notification', 'apollo-core' ); ?>
					</button>
					<span id="apollo-notification-result"></span>
				</div>
			</div>

			<script>
			jQuery(document).ready(function($) {
				$('#notification_recipient').on('change', function() {
					var val = $(this).val();
					$('#notification_user_row').toggle(val === 'specific');
					$('#notification_role_row').toggle(val === 'role');
				});
			});
			</script>
		</div>
		<?php
	}

	/**
	 * Render privacy tab
	 *
	 * @param array $settings Current settings.
	 */
	private static function render_privacy_tab( $settings ) {
		$privacy         = $settings['privacy'] ?? array();
		$stealth_users   = $privacy['stealth_users'] ?? array();
		$dashboard_stats = $settings['dashboard_statistics'] ?? array();
		$all_roles       = wp_roles()->get_names();
		$all_stats       = self::get_all_statistics();
		$all_memberships = self::get_all_memberships();

		?>
		<div class="apollo-tab-content">
			<h2><?php esc_html_e( 'Privacy & Visit Tracking', 'apollo-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Control how user page visits are tracked and displayed.', 'apollo-core' ); ?></p>

			<!-- Stealth Mode Section -->
			<div class="apollo-privacy-section" style="background: #fff; border: 1px solid var(--ap-border-color, #e0e0e0); border-radius: var(--ap-radius-md, 8px); padding: 24px; margin-bottom: 24px;">
				<h3 style="margin-top: 0; display: flex; align-items: center; gap: 8px;">
					<span class="dashicons dashicons-hidden" style="color: var(--ap-orange-500, #ff6925);"></span>
					<?php esc_html_e( 'Stealth Mode - Invisible Visitors', 'apollo-core' ); ?>
				</h3>
				<p class="description" style="margin-bottom: 20px;"><?php esc_html_e( 'Select which user roles can browse profiles without triggering "User X visited your page" notifications. Administrators always have stealth mode.', 'apollo-core' ); ?></p>

				<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; margin-bottom: 20px;">
					<?php foreach ( $all_memberships as $role_key => $role_label ) : ?>
						<label style="display: flex; align-items: center; gap: 8px; padding: 12px; background: #f9f9f9; border-radius: 6px; cursor: pointer;">
							<input type="checkbox"
								name="<?php echo esc_attr( self::OPTION_NAME ); ?>[privacy][stealth_users][]"
								value="<?php echo esc_attr( $role_key ); ?>"
								<?php checked( in_array( $role_key, $stealth_users ) ); ?>>
							<span class="dashicons dashicons-hidden" style="font-size: 16px; color: #666;"></span>
							<strong><?php echo esc_html( $role_label ); ?></strong>
						</label>
					<?php endforeach; ?>
				</div>

				<div style="background: rgba(255, 105, 37, 0.1); border-left: 4px solid #ff6925; padding: 12px 16px; border-radius: 0 6px 6px 0;">
					<strong><?php esc_html_e( 'Note:', 'apollo-core' ); ?></strong>
					<?php esc_html_e( 'Users with stealth mode can view any profile page without the owner receiving a "User X visited your page" notification. This is useful for moderation, support, or admin purposes.', 'apollo-core' ); ?>
				</div>
			</div>

			<!-- Dashboard Statistics Access Section -->
			<div class="apollo-privacy-section" style="background: #fff; border: 1px solid var(--ap-border-color, #e0e0e0); border-radius: var(--ap-radius-md, 8px); padding: 24px; margin-bottom: 24px;">
				<h3 style="margin-top: 0; display: flex; align-items: center; gap: 8px;">
					<span class="dashicons dashicons-chart-bar" style="color: var(--ap-orange-500, #ff6925);"></span>
					<?php esc_html_e( 'Dashboard Statistics by Role', 'apollo-core' ); ?>
				</h3>
				<p class="description" style="margin-bottom: 20px;"><?php esc_html_e( 'Define which statistics each user role can see on their dashboard. Administrators see all metrics.', 'apollo-core' ); ?></p>

				<div class="apollo-dashboard-stats-grid" style="overflow-x: auto;">
					<table class="wp-list-table widefat fixed" style="min-width: 800px;">
						<thead>
							<tr>
								<th style="width: 250px; position: sticky; left: 0; background: #fff; z-index: 1;"><?php esc_html_e( 'Statistic', 'apollo-core' ); ?></th>
								<?php foreach ( $all_memberships as $role_key => $role_label ) : ?>
									<th style="text-align: center; width: 100px;">
										<span title="<?php echo esc_attr( $role_label ); ?>"><?php echo esc_html( substr( $role_label, 0, 10 ) ); ?></span>
									</th>
								<?php endforeach; ?>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( $all_stats as $stat_key => $stat_label ) :
								$stat_roles = $dashboard_stats[ $stat_key ] ?? array();
								?>
								<tr>
									<td style="position: sticky; left: 0; background: #fff;">
										<strong><?php echo esc_html( $stat_label ); ?></strong>
									</td>
									<?php foreach ( $all_memberships as $role_key => $role_label ) : ?>
										<td style="text-align: center;">
											<input type="checkbox"
												name="<?php echo esc_attr( self::OPTION_NAME ); ?>[dashboard_statistics][<?php echo esc_attr( $stat_key ); ?>][]"
												value="<?php echo esc_attr( $role_key ); ?>"
												<?php checked( in_array( $role_key, $stat_roles ) ); ?>
												title="<?php echo esc_attr( $role_label ); ?> - <?php echo esc_attr( $stat_label ); ?>">
										</td>
									<?php endforeach; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<div style="margin-top: 16px; padding: 12px; background: #f5f5f5; border-radius: 6px;">
					<span class="dashicons dashicons-info" style="color: #0073aa;"></span>
					<?php esc_html_e( 'Tip: Click "Select All" buttons below to quickly configure role access.', 'apollo-core' ); ?>
					<div style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 8px;">
						<?php foreach ( $all_memberships as $role_key => $role_label ) : ?>
							<button type="button" class="button button-small apollo-select-all-role" data-role="<?php echo esc_attr( $role_key ); ?>">
								<?php echo esc_html( sprintf( __( 'All for %s', 'apollo-core' ), $role_label ) ); ?>
							</button>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<!-- Visit Tracking Control Section -->
			<div class="apollo-privacy-section" style="background: #fff; border: 1px solid var(--ap-border-color, #e0e0e0); border-radius: var(--ap-radius-md, 8px); padding: 24px; margin-bottom: 24px;">
				<h3 style="margin-top: 0; display: flex; align-items: center; gap: 8px;">
					<span class="dashicons dashicons-visibility" style="color: var(--ap-orange-500, #ff6925);"></span>
					<?php esc_html_e( 'Visit Tracking Control', 'apollo-core' ); ?>
				</h3>
				<p class="description" style="margin-bottom: 20px;"><?php esc_html_e( 'Configure how visits are tracked for each role. Users can override this in their privacy settings.', 'apollo-core' ); ?></p>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style="width: 200px;"><?php esc_html_e( 'Role', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Visit Tracking Mode', 'apollo-core' ); ?></th>
							<th style="width: 150px;"><?php esc_html_e( 'Users', 'apollo-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$user_count = count_users();
						foreach ( $all_roles as $role_key => $role_name ) :
							$current_value = $privacy['visit_tracking'][ $role_key ] ?? 'optional';
							$users_in_role = $user_count['avail_roles'][ $role_key ] ?? 0;
							?>
							<tr>
								<td>
									<strong><?php echo esc_html( $role_name ); ?></strong>
									<br><code class="ap-text-xs"><?php echo esc_html( $role_key ); ?></code>
								</td>
								<td>
									<div class="apollo-radio-group" style="display: flex; flex-wrap: wrap; gap: 12px;">
										<label class="apollo-radio-label" style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; background: <?php echo 'invisible' === $current_value ? 'rgba(255, 105, 37, 0.1)' : '#f5f5f5'; ?>; border-radius: 4px; cursor: pointer;">
											<input type="radio"
												name="<?php echo esc_attr( self::OPTION_NAME ); ?>[privacy][visit_tracking][<?php echo esc_attr( $role_key ); ?>]"
												value="invisible"
												<?php checked( $current_value, 'invisible' ); ?>>
											<span class="dashicons dashicons-hidden" style="font-size: 14px;"></span>
											<?php esc_html_e( 'Invisible', 'apollo-core' ); ?>
										</label>
										<label class="apollo-radio-label" style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; background: <?php echo 'visible' === $current_value ? 'rgba(255, 105, 37, 0.1)' : '#f5f5f5'; ?>; border-radius: 4px; cursor: pointer;">
											<input type="radio"
												name="<?php echo esc_attr( self::OPTION_NAME ); ?>[privacy][visit_tracking][<?php echo esc_attr( $role_key ); ?>]"
												value="visible"
												<?php checked( $current_value, 'visible' ); ?>>
											<span class="dashicons dashicons-visibility" style="font-size: 14px;"></span>
											<?php esc_html_e( 'Visible', 'apollo-core' ); ?>
										</label>
										<label class="apollo-radio-label" style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; background: <?php echo 'optional' === $current_value ? 'rgba(255, 105, 37, 0.1)' : '#f5f5f5'; ?>; border-radius: 4px; cursor: pointer;">
											<input type="radio"
												name="<?php echo esc_attr( self::OPTION_NAME ); ?>[privacy][visit_tracking][<?php echo esc_attr( $role_key ); ?>]"
												value="optional"
												<?php checked( $current_value, 'optional' ); ?>>
											<span class="dashicons dashicons-admin-users" style="font-size: 14px;"></span>
											<?php esc_html_e( 'User Choice', 'apollo-core' ); ?>
										</label>
									</div>
								</td>
								<td>
									<span class="ap-text-sm"><?php echo esc_html( $users_in_role ); ?></span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<div class="apollo-privacy-legend" style="background: #f9f9f9; border-radius: var(--ap-radius-md, 8px); padding: 16px;">
				<h4 style="margin-top: 0;"><?php esc_html_e( 'Mode Descriptions', 'apollo-core' ); ?></h4>
				<ul style="margin: 0; padding-left: 20px;">
					<li><strong><?php esc_html_e( 'Stealth Mode', 'apollo-core' ); ?>:</strong> <?php esc_html_e( 'Selected roles can browse any profile without triggering notifications.', 'apollo-core' ); ?></li>
					<li><strong><?php esc_html_e( 'Invisible', 'apollo-core' ); ?>:</strong> <?php esc_html_e( 'Users can view pages without notifying the owner.', 'apollo-core' ); ?></li>
					<li><strong><?php esc_html_e( 'Visible', 'apollo-core' ); ?>:</strong> <?php esc_html_e( 'Page owner sees "Visited X minutes ago" message.', 'apollo-core' ); ?></li>
					<li><strong><?php esc_html_e( 'User Choice', 'apollo-core' ); ?>:</strong> <?php esc_html_e( 'Users can choose their own visibility preference.', 'apollo-core' ); ?></li>
				</ul>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('.apollo-select-all-role').on('click', function() {
				var role = $(this).data('role');
				$('input[name$="[' + role + ']"]').prop('checked', true);
			});
		});
		</script>
		<?php
	}

	/**
	 * Get all features
	 *
	 * @return array Feature list.
	 */
	private static function get_all_features() {
		return array(
			'events_manager'  => __( 'Events Manager', 'apollo-core' ),
			'social_features' => __( 'Social Features', 'apollo-core' ),
			'user_pages'      => __( 'User Pages', 'apollo-core' ),
			'classifieds'     => __( 'Classifieds', 'apollo-core' ),
			'groups'          => __( 'Groups', 'apollo-core' ),
			'documents'       => __( 'Documents', 'apollo-core' ),
			'signatures'      => __( 'Signatures', 'apollo-core' ),
			'badges'          => __( 'Badges', 'apollo-core' ),
			'memberships'     => __( 'Memberships', 'apollo-core' ),
			'canvas_builder'  => __( 'Canvas Builder', 'apollo-core' ),
			'plano_editor'    => __( 'Plano Editor', 'apollo-core' ),
			'notifications'   => __( 'Notifications', 'apollo-core' ),
			'analytics'       => __( 'Analytics', 'apollo-core' ),
			'moderation'      => __( 'Moderation', 'apollo-core' ),
			'quiz_system'     => __( 'Quiz System', 'apollo-core' ),
			'forms'           => __( 'Forms', 'apollo-core' ),
		);
	}

	/**
	 * Get email types
	 *
	 * @return array Email types.
	 */
	private static function get_email_types() {
		return array(
			'register-confirm' => __( 'Registration Confirmation', 'apollo-core' ),
			'week-reminder'    => __( '1 Week Non-Opening Reminder', 'apollo-core' ),
			'weekly-summary'   => __( 'Weekly Summary (Visits, Statistics)', 'apollo-core' ),
			'event_created'    => __( 'Event Created', 'apollo-core' ),
			'event_updated'    => __( 'Event Updated', 'apollo-core' ),
			'event_approved'   => __( 'Event Approved', 'apollo-core' ),
			'event_rejected'   => __( 'Event Rejected', 'apollo-core' ),
			'user_suspended'   => __( 'User Suspended', 'apollo-core' ),
			'user_unsuspended' => __( 'User Unsuspended', 'apollo-core' ),
			'content_approved' => __( 'Content Approved', 'apollo-core' ),
			'content_rejected' => __( 'Content Rejected', 'apollo-core' ),
			'group_invitation' => __( 'Group Invitation', 'apollo-core' ),
			'document_signed'  => __( 'Document Signed', 'apollo-core' ),
			'password_reset'   => __( 'Password Reset', 'apollo-core' ),
			'welcome'          => __( 'Welcome Email', 'apollo-core' ),
		);
	}

	/**
	 * Sanitize email settings
	 *
	 * @param array $input Raw input.
	 * @return array Sanitized settings.
	 */
	public static function sanitize_email_settings( $input ) {
		$sanitized = array();

		if ( isset( $input['senders'] ) && is_array( $input['senders'] ) ) {
			foreach ( $input['senders'] as $key => $email ) {
				$sanitized['senders'][ sanitize_key( $key ) ] = sanitize_email( $email );
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize page access
	 *
	 * @param array $input Raw input.
	 * @return array Sanitized settings.
	 */
	public static function sanitize_page_access( $input ) {
		$sanitized = array();

		foreach ( $input as $page_id => $rules ) {
			$page_id = absint( $page_id );
			if ( isset( $rules['allowed'] ) && is_array( $rules['allowed'] ) ) {
				$sanitized[ $page_id ]['allowed'] = array_map( 'sanitize_key', array_keys( array_filter( $rules['allowed'] ) ) );
			}
			if ( isset( $rules['forbidden'] ) && is_array( $rules['forbidden'] ) ) {
				$sanitized[ $page_id ]['forbidden'] = array_map( 'sanitize_key', array_keys( array_filter( $rules['forbidden'] ) ) );
			}
		}

		return $sanitized;
	}

	/**
	 * AJAX: Suspend user
	 */
	public static function ajax_suspend_user() {
		check_ajax_referer( 'apollo_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'apollo-core' ) ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$days    = isset( $_POST['days'] ) ? absint( $_POST['days'] ) : 7;

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID', 'apollo-core' ) ) );
		}

		$suspended_until = time() + ( $days * DAY_IN_SECONDS );
		update_user_meta( $user_id, '_apollo_suspended_until', $suspended_until );

		wp_send_json_success( array( 'message' => __( 'User suspended successfully', 'apollo-core' ) ) );
	}

	/**
	 * AJAX: Change user role/membership
	 */
	public static function ajax_change_user_role() {
		check_ajax_referer( 'apollo_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'promote_users' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied. You need the promote_users capability.', 'apollo-core' ) ) );
		}

		$user_id  = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$new_role = isset( $_POST['new_role'] ) ? sanitize_key( $_POST['new_role'] ) : '';

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID', 'apollo-core' ) ) );
		}

		if ( empty( $new_role ) ) {
			wp_send_json_error( array( 'message' => __( 'No role specified', 'apollo-core' ) ) );
		}

		// Validate role exists.
		$roles = wp_roles()->roles;
		if ( ! isset( $roles[ $new_role ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid role specified', 'apollo-core' ) ) );
		}

		// Prevent demoting yourself.
		if ( $user_id === get_current_user_id() && $new_role !== 'administrator' ) {
			wp_send_json_error( array( 'message' => __( 'You cannot change your own role to a non-admin role', 'apollo-core' ) ) );
		}

		$user = new \WP_User( $user_id );
		if ( ! $user->exists() ) {
			wp_send_json_error( array( 'message' => __( 'User not found', 'apollo-core' ) ) );
		}

		// Change the role.
		$user->set_role( $new_role );

		wp_send_json_success(
			array(
				'message'   => sprintf(
					/* translators: 1: User name, 2: Role name */
					__( 'User %1$s role changed to %2$s', 'apollo-core' ),
					$user->display_name,
					translate_user_role( $roles[ $new_role ]['name'] )
				),
				'user_id'   => $user_id,
				'new_role'  => $new_role,
				'role_name' => translate_user_role( $roles[ $new_role ]['name'] ),
			)
		);
	}

	/**
	 * AJAX: Send notification
	 */
	public static function ajax_send_notification() {
		check_ajax_referer( 'apollo_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'apollo-core' ) ) );
		}

		$recipient = isset( $_POST['recipient'] ) ? sanitize_text_field( wp_unslash( $_POST['recipient'] ) ) : 'all';
		$user_id   = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$title     = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$message   = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

		if ( empty( $title ) || empty( $message ) ) {
			wp_send_json_error( array( 'message' => __( 'Title and message are required', 'apollo-core' ) ) );
		}

		// Send notification.
		$result = false;

		switch ( $recipient ) {
			case 'all':
				// Send to all users (push notification).
				if ( class_exists( 'Apollo_Push_Notifications' ) && Apollo_Push_Notifications::is_available() ) {
					$result = Apollo_Push_Notifications::send_custom_notification( $title, $message, home_url() );
				}
				break;

			case 'specific':
				// For specific user, we could send email or something, but for now, just push if available.
				if ( $user_id && class_exists( 'Apollo_Push_Notifications' ) && Apollo_Push_Notifications::is_available() ) {
					$result = Apollo_Push_Notifications::send_custom_notification( $title, $message, home_url() );
				}
				break;

			case 'role':
				// Not implemented yet.
				break;
		}

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Notification sent successfully', 'apollo-core' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to send notification or no push plugin available', 'apollo-core' ) ) );
		}
	}

	/**
	 * AJAX: Test email
	 */
	public static function ajax_test_email() {
		check_ajax_referer( 'apollo_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'apollo-core' ) ) );
		}

		$to   = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';
		$type = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : '';

		if ( empty( $to ) || ! is_email( $to ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid email address', 'apollo-core' ) ) );
		}

		// Get email configuration.
		$email_config = get_option( 'apollo_email_config', array() );
		$senders      = isset( $email_config['senders'] ) ? $email_config['senders'] : array();

		// Get sender email for this type.
		$from_email = isset( $senders[ $type ] ) ? $senders[ $type ] : get_option( 'admin_email' );

		if ( empty( $from_email ) || ! is_email( $from_email ) ) {
			wp_send_json_error( array( 'message' => __( 'No valid sender email configured for this type', 'apollo-core' ) ) );
		}

		// Get email type labels.
		$email_types = self::get_email_types();
		$type_label  = isset( $email_types[ $type ] ) ? $email_types[ $type ] : $type;

		// Prepare email content.
		$subject = sprintf( __( 'Apollo Test Email - %s', 'apollo-core' ), $type_label );
		$message = sprintf(
			__( "This is a test email from Apollo.\n\nEmail Type: %1\$s\nSent to: %2\$s\nFrom: %3\$s\n\nIf you received this email, the email configuration is working correctly.", 'apollo-core' ),
			$type_label,
			$to,
			$from_email
		);

		$headers = array(
			'Content-Type: text/plain; charset=UTF-8',
			'From: Apollo <' . $from_email . '>',
		);

		// Send the test email.
		$sent = wp_mail( $to, $subject, $message, $headers );

		if ( $sent ) {
			wp_send_json_success( array( 'message' => __( 'Test email sent successfully', 'apollo-core' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to send test email', 'apollo-core' ) ) );
		}
	}

	/**
	 * AJAX: Toggle feature
	 */
	public static function ajax_toggle_feature() {
		check_ajax_referer( 'apollo_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'apollo-core' ) ) );
		}

		$feature = isset( $_POST['feature'] ) ? sanitize_key( $_POST['feature'] ) : '';
		$enabled = isset( $_POST['enabled'] ) ? (bool) $_POST['enabled'] : false;

		$settings                         = get_option( self::OPTION_NAME, array() );
		$settings['features'][ $feature ] = $enabled;
		update_option( self::OPTION_NAME, $settings );

		wp_send_json_success( array( 'message' => __( 'Feature toggled', 'apollo-core' ) ) );
	}

	/**
	 * Render sections (empty - handled by tabs)
	 */
	public static function render_memberships_section() {}
	public static function render_roles_section() {}
	public static function render_permissions_section() {}
	public static function render_statistics_section() {}

	/**
	 * Enqueue assets
	 *
	 * @param string $hook Current hook.
	 */
	public static function enqueue_assets( $hook ) {
		// Load base CSS on all Apollo admin pages.
		if ( strpos( $hook, 'apollo' ) !== false || strpos( $hook, 'apollo-cabin' ) !== false ) {
			wp_enqueue_style(
				'apollo-admin-base',
				APOLLO_CORE_PLUGIN_URL . 'admin/css/apollo-admin-base.css',
				array(),
				APOLLO_CORE_VERSION
			);
		}

		// Only load control panel scripts on control panel page.
		if ( strpos( $hook, 'apollo-control' ) === false && strpos( $hook, 'apollo-cabin_page_apollo-control' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'apollo-control-panel',
			APOLLO_CORE_PLUGIN_URL . 'admin/css/control-panel.css',
			array( 'apollo-admin-base' ),
			APOLLO_CORE_VERSION
		);

		wp_enqueue_script(
			'apollo-control-panel',
			APOLLO_CORE_PLUGIN_URL . 'admin/js/control-panel.js',
			array( 'jquery' ),
			APOLLO_CORE_VERSION,
			true
		);

		wp_localize_script(
			'apollo-control-panel',
			'apolloControl',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'apollo_admin_nonce' ),
				'i18n'     => array(
					'confirm_suspend'   => __( 'Are you sure you want to suspend this user?', 'apollo-core' ),
					'confirm_unsuspend' => __( 'Are you sure you want to unsuspend this user?', 'apollo-core' ),
					'success'           => __( 'Success!', 'apollo-core' ),
					'error'             => __( 'Error!', 'apollo-core' ),
				),
			)
		);
	}

	/**
	 * Check if permission is allowed
	 *
	 * @param string $permission Permission key.
	 * @return bool True if allowed.
	 */
	public static function is_permission_allowed( $permission ) {
		$settings = get_option( self::OPTION_NAME, array() );
		return isset( $settings['permissions'][ $permission ] ) && $settings['permissions'][ $permission ];
	}

	/**
	 * Check if statistic is public
	 *
	 * @param string $statistic Statistic key.
	 * @return bool True if public.
	 */
	public static function is_statistic_public( $statistic ) {
		$settings = get_option( self::OPTION_NAME, array() );
		return isset( $settings['statistics'][ $statistic ] ) && $settings['statistics'][ $statistic ];
	}
}

Apollo_Unified_Control_Panel::init();

