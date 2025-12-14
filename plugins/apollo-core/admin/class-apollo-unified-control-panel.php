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
		add_action( 'admin_menu', [ __CLASS__, 'add_admin_menu' ] );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	/**
	 * Add admin menu
	 */
	public static function add_admin_menu() {
		add_menu_page(
			__( 'Apollo Control', 'apollo-core' ),
			__( 'Apollo', 'apollo-core' ),
			'manage_options',
			'apollo-control',
			[ __CLASS__, 'render_page' ],
			'dashicons-admin-settings',
			3
		);
	}

	/**
	 * Register settings
	 */
	public static function register_settings() {
		register_setting( 'apollo_unified_settings', self::OPTION_NAME, [ __CLASS__, 'sanitize_settings' ] );
		register_setting( 'apollo_email_settings', 'apollo_email_config', [ __CLASS__, 'sanitize_email_settings' ] );
		register_setting( 'apollo_page_access', 'apollo_page_access_rules', [ __CLASS__, 'sanitize_page_access' ] );

		// Memberships section
		add_settings_section(
			'apollo_memberships',
			__( 'Memberships', 'apollo-core' ),
			[ __CLASS__, 'render_memberships_section' ],
			'apollo-control'
		);

		// User Roles section
		add_settings_section(
			'apollo_roles',
			__( 'User Roles', 'apollo-core' ),
			[ __CLASS__, 'render_roles_section' ],
			'apollo-control'
		);

		// Permissions section
		add_settings_section(
			'apollo_permissions',
			__( 'Permissions', 'apollo-core' ),
			[ __CLASS__, 'render_permissions_section' ],
			'apollo-control'
		);

		// Statistics section
		add_settings_section(
			'apollo_statistics',
			__( 'Public Statistics', 'apollo-core' ),
			[ __CLASS__, 'render_statistics_section' ],
			'apollo-control'
		);

		// AJAX handlers
		add_action( 'wp_ajax_apollo_suspend_user', [ __CLASS__, 'ajax_suspend_user' ] );
		add_action( 'wp_ajax_apollo_send_notification', [ __CLASS__, 'ajax_send_notification' ] );
		add_action( 'wp_ajax_apollo_test_email', [ __CLASS__, 'ajax_test_email' ] );
		add_action( 'wp_ajax_apollo_toggle_feature', [ __CLASS__, 'ajax_toggle_feature' ] );
	}

	/**
	 * Sanitize settings
	 *
	 * @param array $input Raw input.
	 * @return array Sanitized settings.
	 */
	public static function sanitize_settings( $input ) {
		$sanitized = [];

		// Sanitize memberships
		if ( isset( $input['memberships'] ) && is_array( $input['memberships'] ) ) {
			foreach ( $input['memberships'] as $key => $value ) {
				$sanitized['memberships'][ sanitize_key( $key ) ] = (bool) $value;
			}
		}

		// Sanitize roles
		if ( isset( $input['roles'] ) && is_array( $input['roles'] ) ) {
			foreach ( $input['roles'] as $role => $caps ) {
				$sanitized['roles'][ sanitize_key( $role ) ] = array_map( 'sanitize_key', (array) $caps );
			}
		}

		// Sanitize permissions
		if ( isset( $input['permissions'] ) && is_array( $input['permissions'] ) ) {
			foreach ( $input['permissions'] as $key => $value ) {
				$sanitized['permissions'][ sanitize_key( $key ) ] = (bool) $value;
			}
		}

		// Sanitize statistics
		if ( isset( $input['statistics'] ) && is_array( $input['statistics'] ) ) {
			foreach ( $input['statistics'] as $key => $value ) {
				$sanitized['statistics'][ sanitize_key( $key ) ] = (bool) $value;
			}
		}

		// Sanitize features
		if ( isset( $input['features'] ) && is_array( $input['features'] ) ) {
			foreach ( $input['features'] as $key => $value ) {
				$sanitized['features'][ sanitize_key( $key ) ] = (bool) $value;
			}
		}

		// Sanitize privacy
		if ( isset( $input['privacy'] ) && is_array( $input['privacy'] ) ) {
			if ( isset( $input['privacy']['visit_tracking'] ) && is_array( $input['privacy']['visit_tracking'] ) ) {
				foreach ( $input['privacy']['visit_tracking'] as $role => $value ) {
					$sanitized['privacy']['visit_tracking'][ sanitize_key( $role ) ] = sanitize_key( $value );
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Render main page
	 */
	public static function render_page() {
		$settings = get_option( self::OPTION_NAME, [] );
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'memberships'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

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

			<form method="post" action="options.php">
				<?php
				settings_fields( 'apollo_unified_settings' );
				do_settings_sections( 'apollo-control' );

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

				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render memberships tab
	 *
	 * @param array $settings Current settings.
	 */
	private static function render_memberships_tab( $settings ) {
		$memberships = $settings['memberships'] ?? [];
		$all_memberships = self::get_all_memberships();

		?>
		<div class="apollo-tab-content">
			<h2><?php esc_html_e( 'Membership Management', 'apollo-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Control which memberships are active and their capabilities.', 'apollo-core' ); ?></p>

			<table class="form-table">
				<?php foreach ( $all_memberships as $key => $label ) : ?>
					<tr>
						<th scope="row">
							<label for="memberships_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
									name="<?php echo esc_attr( self::OPTION_NAME ); ?>[memberships][<?php echo esc_attr( $key ); ?>]" 
									id="memberships_<?php echo esc_attr( $key ); ?>"
									value="1" 
									<?php checked( isset( $memberships[ $key ] ) && $memberships[ $key ] ); ?>>
								<?php esc_html_e( 'Enable', 'apollo-core' ); ?>
							</label>
						</td>
					</tr>
				<?php endforeach; ?>
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
		$roles = $settings['roles'] ?? [];
		$all_roles = wp_roles()->get_names();

		?>
		<div class="apollo-tab-content">
			<h2><?php esc_html_e( 'User Roles Management', 'apollo-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Configure capabilities for each user role.', 'apollo-core' ); ?></p>

			<table class="form-table">
				<?php foreach ( $all_roles as $role_key => $role_name ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $role_name ); ?></th>
						<td>
							<?php
							$role_obj = get_role( $role_key );
							if ( $role_obj ) {
								$caps = $role_obj->capabilities;
								echo '<ul>';
								foreach ( $caps as $cap => $has ) {
									if ( $has ) {
										echo '<li>' . esc_html( $cap ) . '</li>';
									}
								}
								echo '</ul>';
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
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
		$permissions = $settings['permissions'] ?? [];
		$all_permissions = self::get_all_permissions();

		?>
		<div class="apollo-tab-content">
			<h2><?php esc_html_e( 'Permission Management', 'apollo-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Control access to Apollo features.', 'apollo-core' ); ?></p>

			<table class="form-table">
				<?php foreach ( $all_permissions as $key => $label ) : ?>
					<tr>
						<th scope="row">
							<label for="permissions_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
									name="<?php echo esc_attr( self::OPTION_NAME ); ?>[permissions][<?php echo esc_attr( $key ); ?>]" 
									id="permissions_<?php echo esc_attr( $key ); ?>"
									value="1" 
									<?php checked( isset( $permissions[ $key ] ) && $permissions[ $key ] ); ?>>
								<?php esc_html_e( 'Allow', 'apollo-core' ); ?>
							</label>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>
		<?php
	}

	/**
	 * Render statistics tab
	 *
	 * @param array $settings Current settings.
	 */
	private static function render_statistics_tab( $settings ) {
		$statistics = $settings['statistics'] ?? [];
		$all_statistics = self::get_all_statistics();

		?>
		<div class="apollo-tab-content">
			<h2><?php esc_html_e( 'Public Statistics Control', 'apollo-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Control which statistics are visible to the public. Toggle each statistic individually.', 'apollo-core' ); ?></p>

			<table class="form-table">
				<?php foreach ( $all_statistics as $key => $label ) : ?>
					<tr>
						<th scope="row">
							<label for="statistics_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
									name="<?php echo esc_attr( self::OPTION_NAME ); ?>[statistics][<?php echo esc_attr( $key ); ?>]" 
									id="statistics_<?php echo esc_attr( $key ); ?>"
									value="1" 
									<?php checked( isset( $statistics[ $key ] ) && $statistics[ $key ] ); ?>>
								<?php esc_html_e( 'Show Publicly', 'apollo-core' ); ?>
							</label>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
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
			return [];
		}

		$memberships = apollo_get_memberships();
		$list = [];

		foreach ( $memberships as $membership ) {
			$list[ $membership['id'] ] = $membership['name'] ?? $membership['id'];
		}

		return $list;
	}

	/**
	 * Get all permissions
	 *
	 * @return array Permission list.
	 */
	private static function get_all_permissions() {
		return [
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
		];
	}

	/**
	 * Get all statistics
	 *
	 * @return array Statistics list.
	 */
	private static function get_all_statistics() {
		return [
			'event_views'         => __( 'Event Views', 'apollo-core' ),
			'event_clicks'        => __( 'Event Clicks', 'apollo-core' ),
			'dj_views'            => __( 'DJ Profile Views', 'apollo-core' ),
			'dj_clicks'           => __( 'DJ Profile Clicks', 'apollo-core' ),
			'local_views'         => __( 'Venue Views', 'apollo-core' ),
			'local_clicks'        => __( 'Venue Clicks', 'apollo-core' ),
			'classified_views'    => __( 'Classified Views', 'apollo-core' ),
			'classified_clicks'   => __( 'Classified Clicks', 'apollo-core' ),
			'group_views'         => __( 'Group Views', 'apollo-core' ),
			'group_members'       => __( 'Group Members Count', 'apollo-core' ),
			'document_views'      => __( 'Document Views', 'apollo-core' ),
			'document_downloads'  => __( 'Document Downloads', 'apollo-core' ),
			'user_profile_views'  => __( 'User Profile Views', 'apollo-core' ),
			'user_followers'      => __( 'User Followers Count', 'apollo-core' ),
			'user_posts'          => __( 'User Posts Count', 'apollo-core' ),
			'total_events'        => __( 'Total Events Count', 'apollo-core' ),
			'total_djs'           => __( 'Total DJs Count', 'apollo-core' ),
			'total_locals'        => __( 'Total Venues Count', 'apollo-core' ),
			'total_classifieds'   => __( 'Total Classifieds Count', 'apollo-core' ),
			'total_groups'        => __( 'Total Groups Count', 'apollo-core' ),
			'total_users'         => __( 'Total Users Count', 'apollo-core' ),
		];
	}

	/**
	 * Render features tab
	 *
	 * @param array $settings Current settings.
	 */
	private static function render_features_tab( $settings ) {
		$features = $settings['features'] ?? [];
		$all_features = self::get_all_features();

		?>
		<div class="apollo-tab-content">
			<h2><?php esc_html_e( 'Feature Control', 'apollo-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Activate or deactivate Apollo features, blocks, and functions.', 'apollo-core' ); ?></p>

			<table class="form-table">
				<?php foreach ( $all_features as $key => $label ) : ?>
					<tr>
						<th scope="row">
							<label for="features_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
									name="<?php echo esc_attr( self::OPTION_NAME ); ?>[features][<?php echo esc_attr( $key ); ?>]" 
									id="features_<?php echo esc_attr( $key ); ?>"
									value="1" 
									<?php checked( isset( $features[ $key ] ) && $features[ $key ] ); ?>
									class="apollo-feature-toggle"
									data-feature="<?php echo esc_attr( $key ); ?>">
								<?php esc_html_e( 'Enable', 'apollo-core' ); ?>
							</label>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>
		<?php
	}

	/**
	 * Render page access tab
	 *
	 * @param array $settings Current settings.
	 */
	private static function render_page_access_tab( $settings ) {
		$page_access = get_option( 'apollo_page_access_rules', [] );
		$all_pages = get_pages();
		$all_roles = wp_roles()->get_names();

		?>
		<div class="apollo-tab-content">
			<h2><?php esc_html_e( 'Page Access Control', 'apollo-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Control which roles and users can access specific pages.', 'apollo-core' ); ?></p>

			<table class="form-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Page', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Allowed Roles', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Forbidden Roles', 'apollo-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $all_pages as $page ) : ?>
						<tr>
							<td><?php echo esc_html( $page->post_title ); ?></td>
							<td>
								<?php foreach ( $all_roles as $role_key => $role_name ) : ?>
									<label>
										<input type="checkbox" 
											name="apollo_page_access_rules[<?php echo esc_attr( $page->ID ); ?>][allowed][<?php echo esc_attr( $role_key ); ?>]"
											value="1"
											<?php checked( isset( $page_access[ $page->ID ]['allowed'][ $role_key ] ) ); ?>>
										<?php echo esc_html( $role_name ); ?>
									</label><br>
								<?php endforeach; ?>
							</td>
							<td>
								<?php foreach ( $all_roles as $role_key => $role_name ) : ?>
									<label>
										<input type="checkbox" 
											name="apollo_page_access_rules[<?php echo esc_attr( $page->ID ); ?>][forbidden][<?php echo esc_attr( $role_key ); ?>]"
											value="1"
											<?php checked( isset( $page_access[ $page->ID ]['forbidden'][ $role_key ] ) ); ?>>
										<?php echo esc_html( $role_name ); ?>
									</label><br>
								<?php endforeach; ?>
							</td>
						</tr>
					<?php endforeach; ?>
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
		$users = get_users( [ 'number' => 50 ] );
		?>
		<div class="apollo-tab-content">
			<h2><?php esc_html_e( 'User Management', 'apollo-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Suspend users and manage user access.', 'apollo-core' ); ?></p>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'User', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Email', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Role', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Status', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'apollo-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $users as $user ) : 
						$is_suspended = get_user_meta( $user->ID, '_apollo_suspended_until', true );
						$suspended_until = $is_suspended ? absint( $is_suspended ) : 0;
						$is_suspended_now = $suspended_until > time();
					?>
						<tr>
							<td><?php echo esc_html( $user->display_name ); ?></td>
							<td><?php echo esc_html( $user->user_email ); ?></td>
							<td><?php echo esc_html( implode( ', ', $user->roles ) ); ?></td>
							<td>
								<?php if ( $is_suspended_now ) : ?>
									<span class="apollo-status-suspended">
										<?php esc_html_e( 'Suspended until', 'apollo-core' ); ?>: <?php echo esc_html( date_i18n( get_option( 'date_format' ), $suspended_until ) ); ?>
									</span>
								<?php else : ?>
									<span class="apollo-status-active"><?php esc_html_e( 'Active', 'apollo-core' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<button type="button" 
									class="button apollo-suspend-user" 
									data-user-id="<?php echo esc_attr( $user->ID ); ?>"
									data-user-name="<?php echo esc_attr( $user->display_name ); ?>">
									<?php echo $is_suspended_now ? esc_html__( 'Unsuspend', 'apollo-core' ) : esc_html__( 'Suspend', 'apollo-core' ); ?>
								</button>
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
		$email_config = get_option( 'apollo_email_config', [] );
		$email_types = self::get_email_types();

		?>
		<div class="apollo-tab-content">
			<h2><?php esc_html_e( 'Email Configuration', 'apollo-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Configure sender emails for each email type and test emails.', 'apollo-core' ); ?></p>

			<table class="form-table">
				<?php foreach ( $email_types as $key => $label ) : ?>
					<tr>
						<th scope="row">
							<label for="email_sender_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
						</th>
						<td>
							<input type="email" 
								name="apollo_email_config[senders][<?php echo esc_attr( $key ); ?>]"
								id="email_sender_<?php echo esc_attr( $key ); ?>"
								value="<?php echo esc_attr( $email_config['senders'][ $key ] ?? get_option( 'admin_email' ) ); ?>"
								class="regular-text">
							<button type="button" 
								class="button apollo-test-email" 
								data-email-type="<?php echo esc_attr( $key ); ?>">
								<?php esc_html_e( 'Test Email', 'apollo-core' ); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>

			<h3><?php esc_html_e( 'Send Test Email', 'apollo-core' ); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="test_email_to"><?php esc_html_e( 'To', 'apollo-core' ); ?></label>
					</th>
					<td>
						<input type="email" id="test_email_to" class="regular-text" placeholder="<?php esc_attr_e( 'email@example.com', 'apollo-core' ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="test_email_type"><?php esc_html_e( 'Email Type', 'apollo-core' ); ?></label>
					</th>
					<td>
						<select id="test_email_type" class="regular-text">
							<?php foreach ( $email_types as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"></th>
					<td>
						<button type="button" class="button button-primary apollo-send-test-email">
							<?php esc_html_e( 'Send Test Email', 'apollo-core' ); ?>
						</button>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Render notifications tab
	 *
	 * @param array $settings Current settings.
	 */
	private static function render_notifications_tab( $settings ) {
		$notifications = $settings['notifications'] ?? [];
		?>
		<div class="apollo-tab-content">
			<h2><?php esc_html_e( 'Notification Management', 'apollo-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Send notifications to all users or specific users.', 'apollo-core' ); ?></p>

			<h3><?php esc_html_e( 'Send Notification', 'apollo-core' ); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="notification_recipient"><?php esc_html_e( 'Recipient', 'apollo-core' ); ?></label>
					</th>
					<td>
						<select id="notification_recipient" class="regular-text">
							<option value="all"><?php esc_html_e( 'All Users', 'apollo-core' ); ?></option>
							<option value="specific"><?php esc_html_e( 'Specific User', 'apollo-core' ); ?></option>
							<option value="role"><?php esc_html_e( 'By Role', 'apollo-core' ); ?></option>
						</select>
					</td>
				</tr>
				<tr id="notification_user_row" style="display:none;">
					<th scope="row">
						<label for="notification_user_id"><?php esc_html_e( 'User', 'apollo-core' ); ?></label>
					</th>
					<td>
						<select id="notification_user_id" class="regular-text">
							<?php
							$users = get_users( [ 'number' => 100 ] );
							foreach ( $users as $user ) {
								echo '<option value="' . esc_attr( $user->ID ) . '">' . esc_html( $user->display_name . ' (' . $user->user_email . ')' ) . '</option>';
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="notification_title"><?php esc_html_e( 'Title', 'apollo-core' ); ?></label>
					</th>
					<td>
						<input type="text" id="notification_title" class="regular-text" required>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="notification_message"><?php esc_html_e( 'Message', 'apollo-core' ); ?></label>
					</th>
					<td>
						<textarea id="notification_message" class="large-text" rows="5" required></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row"></th>
					<td>
						<button type="button" class="button button-primary apollo-send-notification">
							<?php esc_html_e( 'Send Notification', 'apollo-core' ); ?>
						</button>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Render privacy tab
	 *
	 * @param array $settings Current settings.
	 */
	private static function render_privacy_tab( $settings ) {
		$privacy = $settings['privacy'] ?? [];
		$all_roles = wp_roles()->get_names();

		?>
		<div class="apollo-tab-content">
			<h2><?php esc_html_e( 'Privacy & Visit Tracking', 'apollo-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Control how user page visits are tracked and displayed.', 'apollo-core' ); ?></p>

			<h3><?php esc_html_e( 'Visit Tracking Control', 'apollo-core' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Configure how visits are tracked for each role. Users can override this in their privacy settings.', 'apollo-core' ); ?></p>

			<table class="form-table">
				<?php foreach ( $all_roles as $role_key => $role_name ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $role_name ); ?></th>
						<td>
							<select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[privacy][visit_tracking][<?php echo esc_attr( $role_key ); ?>]" class="regular-text">
								<option value="invisible" <?php selected( $privacy['visit_tracking'][ $role_key ] ?? 'invisible', 'invisible' ); ?>>
									<?php esc_html_e( 'Invisível - Ver páginas sem avisar', 'apollo-core' ); ?>
								</option>
								<option value="visible" <?php selected( $privacy['visit_tracking'][ $role_key ] ?? 'visible', 'visible' ); ?>>
									<?php esc_html_e( 'Visível - "Visitou há X minutos"', 'apollo-core' ); ?>
								</option>
								<option value="optional" <?php selected( $privacy['visit_tracking'][ $role_key ] ?? 'optional', 'optional' ); ?>>
									<?php esc_html_e( 'Opcional ao usuário', 'apollo-core' ); ?>
								</option>
							</select>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>
		<?php
	}

	/**
	 * Get all features
	 *
	 * @return array Feature list.
	 */
	private static function get_all_features() {
		return [
			'events_manager'     => __( 'Events Manager', 'apollo-core' ),
			'social_features'    => __( 'Social Features', 'apollo-core' ),
			'user_pages'         => __( 'User Pages', 'apollo-core' ),
			'classifieds'        => __( 'Classifieds', 'apollo-core' ),
			'groups'             => __( 'Groups', 'apollo-core' ),
			'documents'          => __( 'Documents', 'apollo-core' ),
			'signatures'         => __( 'Signatures', 'apollo-core' ),
			'badges'             => __( 'Badges', 'apollo-core' ),
			'memberships'        => __( 'Memberships', 'apollo-core' ),
			'canvas_builder'     => __( 'Canvas Builder', 'apollo-core' ),
			'plano_editor'       => __( 'Plano Editor', 'apollo-core' ),
			'notifications'      => __( 'Notifications', 'apollo-core' ),
			'analytics'          => __( 'Analytics', 'apollo-core' ),
			'moderation'         => __( 'Moderation', 'apollo-core' ),
			'quiz_system'        => __( 'Quiz System', 'apollo-core' ),
			'forms'              => __( 'Forms', 'apollo-core' ),
		];
	}

	/**
	 * Get email types
	 *
	 * @return array Email types.
	 */
	private static function get_email_types() {
		return [
			'register-confirm'           => __( 'Registration Confirmation', 'apollo-core' ),
			'week-reminder'              => __( '1 Week Non-Opening Reminder', 'apollo-core' ),
			'weekly-summary'             => __( 'Weekly Summary (Visits, Statistics)', 'apollo-core' ),
			'event_created'             => __( 'Event Created', 'apollo-core' ),
			'event_updated'             => __( 'Event Updated', 'apollo-core' ),
			'event_approved'             => __( 'Event Approved', 'apollo-core' ),
			'event_rejected'             => __( 'Event Rejected', 'apollo-core' ),
			'user_suspended'            => __( 'User Suspended', 'apollo-core' ),
			'user_unsuspended'          => __( 'User Unsuspended', 'apollo-core' ),
			'content_approved'           => __( 'Content Approved', 'apollo-core' ),
			'content_rejected'           => __( 'Content Rejected', 'apollo-core' ),
			'group_invitation'          => __( 'Group Invitation', 'apollo-core' ),
			'document_signed'           => __( 'Document Signed', 'apollo-core' ),
			'password_reset'            => __( 'Password Reset', 'apollo-core' ),
			'welcome'                  => __( 'Welcome Email', 'apollo-core' ),
		];
	}

	/**
	 * Sanitize email settings
	 *
	 * @param array $input Raw input.
	 * @return array Sanitized settings.
	 */
	public static function sanitize_email_settings( $input ) {
		$sanitized = [];

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
		$sanitized = [];

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
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'apollo-core' ) ] );
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$days = isset( $_POST['days'] ) ? absint( $_POST['days'] ) : 7;

		if ( ! $user_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid user ID', 'apollo-core' ) ] );
		}

		$suspended_until = time() + ( $days * DAY_IN_SECONDS );
		update_user_meta( $user_id, '_apollo_suspended_until', $suspended_until );

		wp_send_json_success( [ 'message' => __( 'User suspended successfully', 'apollo-core' ) ] );
	}

	/**
	 * AJAX: Send notification
	 */
	public static function ajax_send_notification() {
		check_ajax_referer( 'apollo_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'apollo-core' ) ] );
		}

		$recipient = isset( $_POST['recipient'] ) ? sanitize_text_field( wp_unslash( $_POST['recipient'] ) ) : 'all';
		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

		if ( empty( $title ) || empty( $message ) ) {
			wp_send_json_error( [ 'message' => __( 'Title and message are required', 'apollo-core' ) ] );
		}

		// Send notification logic here
		wp_send_json_success( [ 'message' => __( 'Notification sent successfully', 'apollo-core' ) ] );
	}

	/**
	 * AJAX: Test email
	 */
	public static function ajax_test_email() {
		check_ajax_referer( 'apollo_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'apollo-core' ) ] );
		}

		$to = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';
		$type = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : '';

		if ( empty( $to ) || ! is_email( $to ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid email address', 'apollo-core' ) ] );
		}

		// Send test email logic here
		wp_send_json_success( [ 'message' => __( 'Test email sent', 'apollo-core' ) ] );
	}

	/**
	 * AJAX: Toggle feature
	 */
	public static function ajax_toggle_feature() {
		check_ajax_referer( 'apollo_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'apollo-core' ) ] );
		}

		$feature = isset( $_POST['feature'] ) ? sanitize_key( $_POST['feature'] ) : '';
		$enabled = isset( $_POST['enabled'] ) ? (bool) $_POST['enabled'] : false;

		$settings = get_option( self::OPTION_NAME, [] );
		$settings['features'][ $feature ] = $enabled;
		update_option( self::OPTION_NAME, $settings );

		wp_send_json_success( [ 'message' => __( 'Feature toggled', 'apollo-core' ) ] );
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
		if ( 'toplevel_page_apollo-control' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'apollo-control-panel',
			APOLLO_CORE_PLUGIN_URL . 'admin/css/control-panel.css',
			[],
			APOLLO_CORE_VERSION
		);

		wp_enqueue_script(
			'apollo-control-panel',
			APOLLO_CORE_PLUGIN_URL . 'admin/js/control-panel.js',
			[ 'jquery' ],
			APOLLO_CORE_VERSION,
			true
		);

		wp_localize_script(
			'apollo-control-panel',
			'apolloControl',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'apollo_admin_nonce' ),
				'i18n'     => [
					'confirm_suspend' => __( 'Are you sure you want to suspend this user?', 'apollo-core' ),
					'confirm_unsuspend' => __( 'Are you sure you want to unsuspend this user?', 'apollo-core' ),
					'success'         => __( 'Success!', 'apollo-core' ),
					'error'           => __( 'Error!', 'apollo-core' ),
				],
			]
		);
	}

	/**
	 * Check if permission is allowed
	 *
	 * @param string $permission Permission key.
	 * @return bool True if allowed.
	 */
	public static function is_permission_allowed( $permission ) {
		$settings = get_option( self::OPTION_NAME, [] );
		return isset( $settings['permissions'][ $permission ] ) && $settings['permissions'][ $permission ];
	}

	/**
	 * Check if statistic is public
	 *
	 * @param string $statistic Statistic key.
	 * @return bool True if public.
	 */
	public static function is_statistic_public( $statistic ) {
		$settings = get_option( self::OPTION_NAME, [] );
		return isset( $settings['statistics'][ $statistic ] ) && $settings['statistics'][ $statistic ];
	}
}

Apollo_Unified_Control_Panel::init();

