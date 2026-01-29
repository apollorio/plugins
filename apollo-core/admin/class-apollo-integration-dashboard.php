<?php
/**
 * Apollo Integration Dashboard
 *
 * Admin dashboard for monitoring and managing Apollo plugin integration.
 * Provides visual status, relationship diagrams, and troubleshooting tools.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core\Admin;

use Apollo_Core\Apollo_Health_Check;
use Apollo_Core\Apollo_Orchestrator;
use Apollo_Core\Apollo_Relationships;
use Apollo_Core\Apollo_Relationship_Integrity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Integration_Dashboard
 *
 * Admin dashboard for integration management.
 */
class Apollo_Integration_Dashboard {

	/**
	 * Menu slug.
	 */
	const MENU_SLUG = 'apollo-integration';

	/**
	 * Initialize the dashboard.
	 *
	 * @return void
	 */
	public static function init(): void {
		\add_action( 'admin_menu', array( self::class, 'register_menu' ) );
		\add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		\add_action( 'wp_ajax_apollo_dashboard_action', array( self::class, 'handle_ajax_action' ) );
		\add_action( 'admin_init', array( self::class, 'handle_form_actions' ) );
	}

	/**
	 * Register admin menu.
	 *
	 * @return void
	 */
	public static function register_menu(): void {
		\add_menu_page(
			\__( 'Apollo Integration', 'apollo-core' ),
			\__( 'Apollo', 'apollo-core' ),
			'manage_options',
			self::MENU_SLUG,
			array( self::class, 'render_dashboard' ),
			'dashicons-networking',
			3
		);

		\add_submenu_page(
			self::MENU_SLUG,
			\__( 'Dashboard', 'apollo-core' ),
			\__( 'Dashboard', 'apollo-core' ),
			'manage_options',
			self::MENU_SLUG,
			array( self::class, 'render_dashboard' )
		);

		\add_submenu_page(
			self::MENU_SLUG,
			\__( 'Health Check', 'apollo-core' ),
			\__( 'Health Check', 'apollo-core' ),
			'manage_options',
			self::MENU_SLUG . '-health',
			array( Apollo_Health_Check::class, 'render_admin_page' )
		);

		\add_submenu_page(
			self::MENU_SLUG,
			\__( 'Relationships', 'apollo-core' ),
			\__( 'Relationships', 'apollo-core' ),
			'manage_options',
			self::MENU_SLUG . '-relationships',
			array( self::class, 'render_relationships_page' )
		);

		\add_submenu_page(
			self::MENU_SLUG,
			\__( 'Troubleshooting', 'apollo-core' ),
			\__( 'Troubleshooting', 'apollo-core' ),
			'manage_options',
			self::MENU_SLUG . '-troubleshoot',
			array( self::class, 'render_troubleshooting_page' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public static function enqueue_assets( string $hook ): void {
		if ( \strpos( $hook, self::MENU_SLUG ) === false ) {
			return;
		}

		\wp_enqueue_style(
			'apollo-dashboard',
			\plugins_url( 'assets/css/dashboard.css', \dirname( __DIR__ ) ),
			array(),
			APOLLO_CORE_VERSION ?? '2.0.0'
		);

		\wp_enqueue_script(
			'apollo-dashboard',
			\plugins_url( 'assets/js/dashboard.js', \dirname( __DIR__ ) ),
			array( 'jquery', 'wp-api-fetch' ),
			APOLLO_CORE_VERSION ?? '2.0.0',
			true
		);

		\wp_localize_script(
			'apollo-dashboard',
			'apolloDashboard',
			array(
				'ajaxUrl' => \admin_url( 'admin-ajax.php' ),
				'nonce'   => \wp_create_nonce( 'apollo_dashboard' ),
				'strings' => array(
					'confirm_repair' => \__( 'Are you sure you want to run the repair? This may take a few minutes.', 'apollo-core' ),
					'repairing'      => \__( 'Repairing...', 'apollo-core' ),
					'success'        => \__( 'Operation completed successfully.', 'apollo-core' ),
					'error'          => \__( 'An error occurred. Please try again.', 'apollo-core' ),
				),
			)
		);
	}

	/**
	 * Render main dashboard.
	 *
	 * @return void
	 */
	public static function render_dashboard(): void {
		$health_report = Apollo_Health_Check::run();
		$boot_stats    = Apollo_Orchestrator::get_boot_stats();
		$plugins       = Apollo_Orchestrator::get_loaded_plugins();

		?>
		<div class="wrap apollo-dashboard">
			<h1><?php \esc_html_e( 'Apollo Integration Dashboard', 'apollo-core' ); ?></h1>

			<!-- Overall Status Banner -->
			<div class="apollo-status-banner status-<?php echo \esc_attr( $health_report['overall'] ); ?>">
				<div class="status-icon">
					<?php self::render_status_icon( $health_report['overall'] ); ?>
				</div>
				<div class="status-info">
					<h2>
						<?php
						switch ( $health_report['overall'] ) {
							case 'good':
								\esc_html_e( 'All Apollo Plugins Operating Normally', 'apollo-core' );
								break;
							case 'warning':
								\esc_html_e( 'Apollo Plugins Running With Warnings', 'apollo-core' );
								break;
							case 'error':
								\esc_html_e( 'Apollo Plugins Have Critical Issues', 'apollo-core' );
								break;
						}
						?>
					</h2>
					<p>
						<?php
						echo \esc_html(
							\sprintf(
							/* translators: %1$d: plugin count, %2$d: feature count, %3$s: boot time */
								\__( '%1$d plugins loaded • %2$d features available • Boot time: %3$sms', 'apollo-core' ),
								$boot_stats['total_plugins'],
								\count( $boot_stats['features'] ),
								\number_format( $boot_stats['boot_duration'] * 1000, 2 )
							)
						);
						?>
					</p>
				</div>
				<div class="status-actions">
					<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=' . self::MENU_SLUG . '-health' ) ); ?>" class="button">
						<?php \esc_html_e( 'View Health Report', 'apollo-core' ); ?>
					</a>
				</div>
			</div>

			<!-- Plugin Status Grid -->
			<div class="apollo-section">
				<h2><?php \esc_html_e( 'Plugin Status', 'apollo-core' ); ?></h2>
				<div class="plugin-grid">
					<?php self::render_plugin_card( 'apollo-core', $plugins ); ?>
					<?php self::render_plugin_card( 'apollo-events-manager', $plugins ); ?>
					<?php self::render_plugin_card( 'apollo-social', $plugins ); ?>
					<?php self::render_plugin_card( 'apollo-rio', $plugins ); ?>
				</div>
			</div>

			<!-- Quick Stats -->
			<div class="apollo-section">
				<h2><?php \esc_html_e( 'Quick Stats', 'apollo-core' ); ?></h2>
				<div class="stats-grid">
					<?php
					$stats = self::get_quick_stats();
					foreach ( $stats as $stat ) :
						?>
						<div class="stat-card">
							<span class="stat-value"><?php echo \esc_html( $stat['value'] ); ?></span>
							<span class="stat-label"><?php echo \esc_html( $stat['label'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Recent Activity -->
			<div class="apollo-section">
				<h2><?php \esc_html_e( 'Recent Activity', 'apollo-core' ); ?></h2>
				<?php self::render_recent_activity(); ?>
			</div>

			<!-- Quick Actions -->
			<div class="apollo-section">
				<h2><?php \esc_html_e( 'Quick Actions', 'apollo-core' ); ?></h2>
				<div class="actions-grid">
					<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=' . self::MENU_SLUG . '-health' ) ); ?>" class="action-card">
						<span class="dashicons dashicons-heart"></span>
						<span><?php \esc_html_e( 'Run Health Check', 'apollo-core' ); ?></span>
					</a>
					<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=' . self::MENU_SLUG . '-relationships' ) ); ?>" class="action-card">
						<span class="dashicons dashicons-admin-links"></span>
						<span><?php \esc_html_e( 'View Relationships', 'apollo-core' ); ?></span>
					</a>
					<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=' . self::MENU_SLUG . '-troubleshoot' ) ); ?>" class="action-card">
						<span class="dashicons dashicons-admin-tools"></span>
						<span><?php \esc_html_e( 'Troubleshoot', 'apollo-core' ); ?></span>
					</a>
					<a href="<?php echo \esc_url( \rest_url( 'apollo/v1/discover' ) ); ?>" target="_blank" class="action-card">
						<span class="dashicons dashicons-rest-api"></span>
						<span><?php \esc_html_e( 'API Discovery', 'apollo-core' ); ?></span>
					</a>
				</div>
			</div>
		</div>

		<?php self::render_dashboard_styles(); ?>
		<?php
	}

	/**
	 * Render plugin status card.
	 *
	 * @param string $slug    Plugin slug.
	 * @param array  $plugins Loaded plugins data.
	 * @return void
	 */
	private static function render_plugin_card( string $slug, array $plugins ): void {
		$plugin_info = array(
			'apollo-core'           => array(
				'name'  => 'Apollo Core',
				'icon'  => 'dashicons-admin-generic',
				'color' => '#3498db',
			),
			'apollo-events-manager' => array(
				'name'  => 'Apollo Events',
				'icon'  => 'dashicons-calendar-alt',
				'color' => '#e74c3c',
			),
			'apollo-social'         => array(
				'name'  => 'Apollo Social',
				'icon'  => 'dashicons-groups',
				'color' => '#9b59b6',
			),
			'apollo-rio'            => array(
				'name'  => 'Apollo Rio (PWA)',
				'icon'  => 'dashicons-smartphone',
				'color' => '#2ecc71',
			),
		);

		$info         = $plugin_info[ $slug ] ?? array(
			'name'  => $slug,
			'icon'  => 'dashicons-admin-plugins',
			'color' => '#666',
		);
		$is_loaded    = isset( $plugins[ $slug ] );
		$status_class = $is_loaded ? 'loaded' : 'not-loaded';

		?>
		<div class="plugin-card <?php echo \esc_attr( $status_class ); ?>" style="--plugin-color: <?php echo \esc_attr( $info['color'] ); ?>">
			<div class="plugin-icon">
				<span class="dashicons <?php echo \esc_attr( $info['icon'] ); ?>"></span>
			</div>
			<div class="plugin-info">
				<h3><?php echo \esc_html( $info['name'] ); ?></h3>
				<p class="plugin-status">
					<?php if ( $is_loaded ) : ?>
						<span class="status-dot active"></span>
						<?php \esc_html_e( 'Active', 'apollo-core' ); ?>
						<?php if ( isset( $plugins[ $slug ]['version'] ) ) : ?>
							<span class="version">v<?php echo \esc_html( $plugins[ $slug ]['version'] ); ?></span>
						<?php endif; ?>
					<?php else : ?>
						<span class="status-dot inactive"></span>
						<?php \esc_html_e( 'Not Active', 'apollo-core' ); ?>
					<?php endif; ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Get quick stats.
	 *
	 * @return array
	 */
	private static function get_quick_stats(): array {
		$stats = array();

		// Events count.
		$events_count = 0;
		foreach ( array( 'apollo-event', 'event_listing' ) as $type ) {
			if ( \post_type_exists( $type ) ) {
				$count         = \wp_count_posts( $type );
				$events_count += $count->publish ?? 0;
			}
		}
		$stats[] = array(
			'value' => \number_format( $events_count ),
			'label' => \__( 'Events', 'apollo-core' ),
		);

		// Users count.
		$users_count = \count_users();
		$stats[]     = array(
			'value' => \number_format( $users_count['total_users'] ),
			'label' => \__( 'Users', 'apollo-core' ),
		);

		// Relationships.
		if ( \class_exists( Apollo_Relationships::class ) ) {
			$schema  = Apollo_Relationships::get_schema();
			$stats[] = array(
				'value' => \count( $schema ),
				'label' => \__( 'Relationships', 'apollo-core' ),
			);
		}

		// REST Endpoints.
		$rest_server   = \rest_get_server();
		$routes        = $rest_server->get_routes();
		$apollo_routes = \array_filter(
			\array_keys( $routes ),
			function ( $route ) {
				return \strpos( $route, 'apollo' ) !== false;
			}
		);
		$stats[]       = array(
			'value' => \count( $apollo_routes ),
			'label' => \__( 'API Endpoints', 'apollo-core' ),
		);

		return $stats;
	}

	/**
	 * Render recent activity.
	 *
	 * @return void
	 */
	private static function render_recent_activity(): void {
		global $wpdb;

		// Check if activity log table exists.
		$table = $wpdb->prefix . 'apollo_activity_log';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		if ( $table_exists !== $table ) {
			echo '<p>' . \esc_html__( 'Activity log table not found. Run activation to create it.', 'apollo-core' ) . '</p>';
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$activities = $wpdb->get_results(
			"SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 10"
		);

		if ( empty( $activities ) ) {
			echo '<p>' . \esc_html__( 'No recent activity.', 'apollo-core' ) . '</p>';
			return;
		}

		echo '<table class="wp-list-table widefat fixed striped">';
		echo '<thead><tr>';
		echo '<th>' . \esc_html__( 'Time', 'apollo-core' ) . '</th>';
		echo '<th>' . \esc_html__( 'User', 'apollo-core' ) . '</th>';
		echo '<th>' . \esc_html__( 'Action', 'apollo-core' ) . '</th>';
		echo '<th>' . \esc_html__( 'Object', 'apollo-core' ) . '</th>';
		echo '</tr></thead>';
		echo '<tbody>';

		foreach ( $activities as $activity ) {
			$user = \get_userdata( $activity->user_id );
			echo '<tr>';
			echo '<td>' . \esc_html( \human_time_diff( \strtotime( $activity->created_at ) ) ) . ' ago</td>';
			echo '<td>' . \esc_html( $user ? $user->display_name : '#' . $activity->user_id ) . '</td>';
			echo '<td>' . \esc_html( $activity->action ) . '</td>';
			echo '<td>' . \esc_html( $activity->object_type . ' #' . $activity->object_id ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Render relationships page.
	 *
	 * @return void
	 */
	public static function render_relationships_page(): void {
		if ( ! \class_exists( Apollo_Relationships::class ) ) {
			echo '<div class="wrap"><h1>Relationships</h1><p>Apollo Relationships class not found.</p></div>';
			return;
		}

		$schema = Apollo_Relationships::get_schema();

		?>
		<div class="wrap apollo-relationships">
			<h1><?php \esc_html_e( 'Apollo Relationships', 'apollo-core' ); ?></h1>

			<!-- Relationship Diagram -->
			<div class="apollo-section">
				<h2><?php \esc_html_e( 'Relationship Diagram', 'apollo-core' ); ?></h2>
				<div class="relationship-diagram">
					<?php self::render_relationship_diagram( $schema ); ?>
				</div>
			</div>

			<!-- Relationship List -->
			<div class="apollo-section">
				<h2><?php \esc_html_e( 'All Relationships', 'apollo-core' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php \esc_html_e( 'Name', 'apollo-core' ); ?></th>
							<th><?php \esc_html_e( 'From', 'apollo-core' ); ?></th>
							<th><?php \esc_html_e( 'To', 'apollo-core' ); ?></th>
							<th><?php \esc_html_e( 'Type', 'apollo-core' ); ?></th>
							<th><?php \esc_html_e( 'Storage', 'apollo-core' ); ?></th>
							<th><?php \esc_html_e( 'Bidirectional', 'apollo-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $schema as $name => $definition ) : ?>
							<tr>
								<td><code><?php echo \esc_html( $name ); ?></code></td>
								<td><?php echo \esc_html( $definition['from'] ?? '' ); ?></td>
								<td><?php echo \esc_html( \is_array( $definition['to'] ?? '' ) ? \implode( ', ', $definition['to'] ) : ( $definition['to'] ?? '' ) ); ?></td>
								<td><?php echo \esc_html( $definition['type'] ?? '' ); ?></td>
								<td><?php echo \esc_html( $definition['storage'] ?? '' ); ?></td>
								<td><?php echo ( $definition['bidirectional'] ?? false ) ? '✓' : '—'; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<!-- Integrity Check -->
			<div class="apollo-section">
				<h2><?php \esc_html_e( 'Integrity Check', 'apollo-core' ); ?></h2>
				<form method="post" action="">
					<?php \wp_nonce_field( 'apollo_integrity_check' ); ?>
					<p>
						<button type="submit" name="apollo_action" value="run_integrity_check" class="button button-primary">
							<?php \esc_html_e( 'Run Integrity Check', 'apollo-core' ); ?>
						</button>
						<button type="submit" name="apollo_action" value="repair_relationships" class="button">
							<?php \esc_html_e( 'Repair All Issues', 'apollo-core' ); ?>
						</button>
					</p>
				</form>

				<?php
				$last_report = \get_option( 'apollo_last_integrity_report' );
				if ( $last_report ) :
					?>
					<div class="integrity-report">
						<h3><?php \esc_html_e( 'Last Check Results', 'apollo-core' ); ?></h3>
						<p>
							<?php
							echo \esc_html(
								\sprintf(
								/* translators: %1$s: timestamp, %2$d: issues */
									\__( 'Checked at %1$s • %2$d issues found', 'apollo-core' ),
									$last_report['checked_at'] ?? 'unknown',
									$last_report['summary']['total_issues'] ?? 0
								)
							);
							?>
						</p>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render relationship diagram.
	 *
	 * @param array $schema Relationship schema.
	 * @return void
	 */
	private static function render_relationship_diagram( array $schema ): void {
		// Build node list.
		$nodes = array();
		foreach ( $schema as $name => $definition ) {
			$from = $definition['from'] ?? '';
			$to   = $definition['to'] ?? '';

			if ( ! \in_array( $from, $nodes, true ) ) {
				$nodes[] = $from;
			}

			if ( \is_array( $to ) ) {
				foreach ( $to as $t ) {
					if ( ! \in_array( $t, $nodes, true ) ) {
						$nodes[] = $t;
					}
				}
			} elseif ( ! \in_array( $to, $nodes, true ) ) {
				$nodes[] = $to;
			}
		}

		$node_colors = array(
			'apollo-event'      => '#e74c3c',
			'apollo-dj'         => '#9b59b6',
			'apollo-local'      => '#3498db',
			'apollo-classified' => '#f39c12',
			'apollo-supplier'   => '#1abc9c',
			'apollo-social'     => '#e91e63',
			'user'              => '#2ecc71',
			'event_listing'     => '#e74c3c',
			'event_dj'          => '#9b59b6',
			'event_local'       => '#3498db',
		);

		?>
		<svg viewBox="0 0 800 500" class="diagram-svg">
			<defs>
				<marker id="arrowhead" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
					<polygon points="0 0, 10 3.5, 0 7" fill="#666" />
				</marker>
			</defs>

			<?php
			$node_positions = array();
			$angle_step     = 360 / \count( $nodes );
			$center_x       = 400;
			$center_y       = 250;
			$radius         = 180;

			foreach ( $nodes as $i => $node ) {
				$angle                   = \deg2rad( $i * $angle_step - 90 );
				$x                       = $center_x + $radius * \cos( $angle );
				$y                       = $center_y + $radius * \sin( $angle );
				$node_positions[ $node ] = array(
					'x' => $x,
					'y' => $y,
				);
				$color                   = $node_colors[ $node ] ?? '#666';
				?>
				<g class="node" transform="translate(<?php echo $x; ?>, <?php echo $y; ?>)">
					<circle r="40" fill="<?php echo \esc_attr( $color ); ?>" opacity="0.8" />
					<text text-anchor="middle" dy="4" fill="white" font-size="10">
						<?php echo \esc_html( \str_replace( array( 'apollo-', 'event_' ), '', $node ) ); ?>
					</text>
				</g>
				<?php
			}

			// Draw edges.
			foreach ( $schema as $name => $definition ) {
				if ( ! empty( $definition['is_reverse'] ) ) {
					continue; // Skip reverse relationships.
				}

				$from = $definition['from'] ?? '';
				$to   = $definition['to'] ?? '';

				if ( \is_array( $to ) ) {
					$to = $to[0]; // Just draw first for simplicity.
				}

				if ( isset( $node_positions[ $from ] ) && isset( $node_positions[ $to ] ) ) {
					$x1 = $node_positions[ $from ]['x'];
					$y1 = $node_positions[ $from ]['y'];
					$x2 = $node_positions[ $to ]['x'];
					$y2 = $node_positions[ $to ]['y'];

					// Shorten line to not overlap circles.
					$dx     = $x2 - $x1;
					$dy     = $y2 - $y1;
					$len    = \sqrt( $dx * $dx + $dy * $dy );
					$offset = 45 / $len;

					$x1 += $dx * $offset;
					$y1 += $dy * $offset;
					$x2 -= $dx * $offset;
					$y2 -= $dy * $offset;
					?>
					<line x1="<?php echo $x1; ?>" y1="<?php echo $y1; ?>"
							x2="<?php echo $x2; ?>" y2="<?php echo $y2; ?>"
							stroke="#666" stroke-width="1.5" marker-end="url(#arrowhead)" />
					<?php
				}
			}
			?>
		</svg>
		<style>
			.diagram-svg { max-width: 100%; height: auto; background: #f9f9f9; border-radius: 8px; }
			.diagram-svg .node { cursor: pointer; }
			.diagram-svg .node:hover circle { opacity: 1; }
		</style>
		<?php
	}

	/**
	 * Render troubleshooting page.
	 *
	 * @return void
	 */
	public static function render_troubleshooting_page(): void {
		?>
		<div class="wrap apollo-troubleshoot">
			<h1><?php \esc_html_e( 'Troubleshooting Tools', 'apollo-core' ); ?></h1>

			<!-- Quick Fixes -->
			<div class="apollo-section">
				<h2><?php \esc_html_e( 'Quick Fixes', 'apollo-core' ); ?></h2>
				<form method="post" action="">
					<?php \wp_nonce_field( 'apollo_troubleshoot' ); ?>
					<table class="form-table">
						<tr>
							<th><?php \esc_html_e( 'Flush Rewrite Rules', 'apollo-core' ); ?></th>
							<td>
								<button type="submit" name="apollo_action" value="flush_rewrite" class="button">
									<?php \esc_html_e( 'Flush Now', 'apollo-core' ); ?>
								</button>
								<p class="description"><?php \esc_html_e( 'Regenerates URL rewrite rules. Fixes 404 errors on CPT archives.', 'apollo-core' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><?php \esc_html_e( 'Clear Transients', 'apollo-core' ); ?></th>
							<td>
								<button type="submit" name="apollo_action" value="clear_transients" class="button">
									<?php \esc_html_e( 'Clear Now', 'apollo-core' ); ?>
								</button>
								<p class="description"><?php \esc_html_e( 'Clears all Apollo cached data.', 'apollo-core' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><?php \esc_html_e( 'Reschedule Cron Jobs', 'apollo-core' ); ?></th>
							<td>
								<button type="submit" name="apollo_action" value="reschedule_cron" class="button">
									<?php \esc_html_e( 'Reschedule Now', 'apollo-core' ); ?>
								</button>
								<p class="description"><?php \esc_html_e( 'Clears and reschedules all Apollo cron jobs.', 'apollo-core' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><?php \esc_html_e( 'Run Database Migration', 'apollo-core' ); ?></th>
							<td>
								<button type="submit" name="apollo_action" value="run_migration" class="button">
									<?php \esc_html_e( 'Run Now', 'apollo-core' ); ?>
								</button>
								<p class="description"><?php \esc_html_e( 'Runs any pending database migrations.', 'apollo-core' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><?php \esc_html_e( 'Repair Relationship Integrity', 'apollo-core' ); ?></th>
							<td>
								<button type="submit" name="apollo_action" value="repair_integrity" class="button">
									<?php \esc_html_e( 'Repair Now', 'apollo-core' ); ?>
								</button>
								<p class="description"><?php \esc_html_e( 'Finds and repairs orphaned relationship references.', 'apollo-core' ); ?></p>
							</td>
						</tr>
					</table>
				</form>
			</div>

			<!-- System Info -->
			<div class="apollo-section">
				<h2><?php \esc_html_e( 'System Information', 'apollo-core' ); ?></h2>
				<textarea readonly class="large-text code" rows="20"><?php echo \esc_textarea( self::get_system_info() ); ?></textarea>
				<p>
					<button type="button" class="button" onclick="navigator.clipboard.writeText(this.previousElementSibling.previousElementSibling.value); alert('Copied!');">
						<?php \esc_html_e( 'Copy to Clipboard', 'apollo-core' ); ?>
					</button>
				</p>
			</div>

			<!-- Debug Log -->
			<div class="apollo-section">
				<h2><?php \esc_html_e( 'Recent Error Log', 'apollo-core' ); ?></h2>
				<?php self::render_error_log(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get system information.
	 *
	 * @return string
	 */
	private static function get_system_info(): string {
		global $wpdb, $wp_version;

		$info   = array();
		$info[] = '### Apollo Integration System Info ###';
		$info[] = '';
		$info[] = '## WordPress';
		$info[] = 'Version: ' . $wp_version;
		$info[] = 'Multisite: ' . ( \is_multisite() ? 'Yes' : 'No' );
		$info[] = 'Language: ' . \get_locale();
		$info[] = 'Permalink Structure: ' . ( \get_option( 'permalink_structure' ) ?: 'Default' );
		$info[] = '';
		$info[] = '## PHP';
		$info[] = 'Version: ' . PHP_VERSION;
		$info[] = 'Memory Limit: ' . \ini_get( 'memory_limit' );
		$info[] = 'Max Execution Time: ' . \ini_get( 'max_execution_time' );
		$info[] = 'Upload Max Filesize: ' . \ini_get( 'upload_max_filesize' );
		$info[] = '';
		$info[] = '## Database';
		$info[] = 'MySQL Version: ' . $wpdb->db_version();
		$info[] = 'Table Prefix: ' . $wpdb->prefix;
		$info[] = 'Apollo DB Version: ' . \get_option( 'apollo_db_version', 'Not set' );
		$info[] = '';
		$info[] = '## Apollo Plugins';

		$active_plugins = \get_option( 'active_plugins', array() );
		foreach ( $active_plugins as $plugin ) {
			if ( \strpos( $plugin, 'apollo' ) !== false ) {
				$plugin_data = \get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
				$info[]      = $plugin_data['Name'] . ': ' . $plugin_data['Version'];
			}
		}

		$info[] = '';
		$info[] = '## Theme';
		$theme  = \wp_get_theme();
		$info[] = 'Active Theme: ' . $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' );
		$info[] = 'Parent Theme: ' . ( $theme->parent() ? $theme->parent()->get( 'Name' ) : 'N/A' );
		$info[] = '';
		$info[] = '## Server';
		$info[] = 'Software: ' . ( $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' );
		$info[] = 'HTTPS: ' . ( \is_ssl() ? 'Yes' : 'No' );
		$info[] = 'WP Cron: ' . ( \defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? 'Disabled' : 'Enabled' );
		$info[] = 'WP Debug: ' . ( \defined( 'WP_DEBUG' ) && WP_DEBUG ? 'Enabled' : 'Disabled' );

		return \implode( "\n", $info );
	}

	/**
	 * Render error log.
	 *
	 * @return void
	 */
	private static function render_error_log(): void {
		$log_file = WP_CONTENT_DIR . '/debug.log';

		if ( ! \file_exists( $log_file ) ) {
			echo '<p>' . \esc_html__( 'Debug log file not found. Enable WP_DEBUG_LOG to see errors.', 'apollo-core' ) . '</p>';
			return;
		}

		// Get last 50 lines.
		$lines = array();
		$file  = new \SplFileObject( $log_file, 'r' );
		$file->seek( PHP_INT_MAX );
		$total_lines = $file->key();
		$start       = \max( 0, $total_lines - 50 );

		$file->seek( $start );
		while ( ! $file->eof() ) {
			$line = $file->fgets();
			if ( \strpos( $line, 'apollo' ) !== false || \strpos( $line, 'Apollo' ) !== false ) {
				$lines[] = $line;
			}
		}

		if ( empty( $lines ) ) {
			echo '<p>' . \esc_html__( 'No Apollo-related errors in recent log.', 'apollo-core' ) . '</p>';
			return;
		}

		echo '<pre class="code" style="max-height: 300px; overflow: auto; background: #f5f5f5; padding: 10px;">';
		echo \esc_html( \implode( '', \array_slice( $lines, -20 ) ) );
		echo '</pre>';
	}

	/**
	 * Handle form actions.
	 *
	 * @return void
	 */
	public static function handle_form_actions(): void {
		if ( ! isset( $_POST['apollo_action'] ) ) {
			return;
		}

		// Verify nonce.
		$valid_nonce = false;
		foreach ( array( 'apollo_troubleshoot', 'apollo_integrity_check', 'apollo_health_action' ) as $nonce_action ) {
			if ( \wp_verify_nonce( $_POST['_wpnonce'] ?? '', $nonce_action ) ) {
				$valid_nonce = true;
				break;
			}
		}

		if ( ! $valid_nonce || ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		$action   = \sanitize_key( $_POST['apollo_action'] );
		$redirect = \remove_query_arg( 'apollo_message' );

		switch ( $action ) {
			case 'flush_rewrite':
				\flush_rewrite_rules();
				$redirect = \add_query_arg( 'apollo_message', 'rewrite_flushed', $redirect );
				break;

			case 'clear_transients':
				global $wpdb;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->query(
					"DELETE FROM {$wpdb->options}
					WHERE option_name LIKE '_transient_apollo_%'
					OR option_name LIKE '_transient_timeout_apollo_%'"
				);
				$redirect = \add_query_arg( 'apollo_message', 'transients_cleared', $redirect );
				break;

			case 'reschedule_cron':
				// Clear and reschedule.
				$hooks = array(
					'apollo_daily_cleanup',
					'apollo_weekly_digest',
					'apollo_relationship_integrity_check',
					'apollo_event_reminders',
					'apollo_process_event_queue',
				);
				foreach ( $hooks as $hook ) {
					$timestamp = \wp_next_scheduled( $hook );
					if ( $timestamp ) {
						\wp_unschedule_event( $timestamp, $hook );
					}
				}
				// Trigger reactivation.
				if ( \class_exists( \Apollo_Core\Apollo_Activation_Controller::class ) ) {
					\Apollo_Core\Apollo_Activation_Controller::activate();
				}
				$redirect = \add_query_arg( 'apollo_message', 'cron_rescheduled', $redirect );
				break;

			case 'run_migration':
				if ( \class_exists( \Apollo_Core\Apollo_Activation_Controller::class ) ) {
					\Apollo_Core\Apollo_Activation_Controller::activate();
				}
				$redirect = \add_query_arg( 'apollo_message', 'migration_complete', $redirect );
				break;

			case 'repair_integrity':
			case 'repair_relationships':
				if ( \class_exists( Apollo_Relationship_Integrity::class ) ) {
					$report = Apollo_Relationship_Integrity::check_all();
					// Auto-repair all.
					$schema = Apollo_Relationships::get_schema();
					foreach ( $schema as $name => $definition ) {
						if ( empty( $definition['is_reverse'] ) ) {
							Apollo_Relationship_Integrity::repair_relationship( $name );
						}
					}
				}
				$redirect = \add_query_arg( 'apollo_message', 'integrity_repaired', $redirect );
				break;

			case 'run_integrity_check':
				if ( \class_exists( Apollo_Relationship_Integrity::class ) ) {
					$report = Apollo_Relationship_Integrity::check_all();
					\update_option( 'apollo_last_integrity_report', $report );
				}
				$redirect = \add_query_arg( 'apollo_message', 'integrity_checked', $redirect );
				break;
		}

		\wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Handle AJAX action.
	 *
	 * @return void
	 */
	public static function handle_ajax_action(): void {
		\check_ajax_referer( 'apollo_dashboard', 'nonce' );

		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$action = \sanitize_key( $_POST['dashboard_action'] ?? '' );

		switch ( $action ) {
			case 'get_health':
				$report = Apollo_Health_Check::run();
				\wp_send_json_success( $report );
				break;

			case 'get_stats':
				$stats = self::get_quick_stats();
				\wp_send_json_success( $stats );
				break;

			default:
				\wp_send_json_error( array( 'message' => 'Unknown action' ) );
		}
	}

	/**
	 * Render status icon.
	 *
	 * @param string $status Status.
	 * @return void
	 */
	private static function render_status_icon( string $status ): void {
		switch ( $status ) {
			case 'good':
				echo '<span class="dashicons dashicons-yes-alt" style="color: #28a745; font-size: 48px;"></span>';
				break;
			case 'warning':
				echo '<span class="dashicons dashicons-warning" style="color: #ffc107; font-size: 48px;"></span>';
				break;
			case 'error':
				echo '<span class="dashicons dashicons-dismiss" style="color: #dc3545; font-size: 48px;"></span>';
				break;
		}
	}

	/**
	 * Render dashboard styles.
	 *
	 * @return void
	 */
	private static function render_dashboard_styles(): void {
		?>
		<style>
			.apollo-dashboard { max-width: 1200px; }
			.apollo-status-banner {
				display: flex;
				align-items: center;
				gap: 20px;
				padding: 20px;
				margin: 20px 0;
				border-radius: 8px;
				background: #fff;
				box-shadow: 0 1px 3px rgba(0,0,0,0.1);
			}
			.apollo-status-banner.status-good { border-left: 4px solid #28a745; }
			.apollo-status-banner.status-warning { border-left: 4px solid #ffc107; }
			.apollo-status-banner.status-error { border-left: 4px solid #dc3545; }
			.apollo-status-banner .status-info { flex: 1; }
			.apollo-status-banner .status-info h2 { margin: 0 0 5px; }
			.apollo-status-banner .status-info p { margin: 0; color: #666; }
			.apollo-section { background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
			.apollo-section h2 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; }
			.plugin-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; }
			.plugin-card {
				display: flex;
				align-items: center;
				gap: 15px;
				padding: 15px;
				background: #f9f9f9;
				border-radius: 8px;
				border-left: 4px solid var(--plugin-color, #666);
			}
			.plugin-card.not-loaded { opacity: 0.6; }
			.plugin-card .plugin-icon { font-size: 32px; color: var(--plugin-color); }
			.plugin-card h3 { margin: 0; font-size: 14px; }
			.plugin-card .plugin-status { margin: 5px 0 0; font-size: 12px; color: #666; }
			.plugin-card .status-dot {
				display: inline-block;
				width: 8px;
				height: 8px;
				border-radius: 50%;
				margin-right: 5px;
			}
			.plugin-card .status-dot.active { background: #28a745; }
			.plugin-card .status-dot.inactive { background: #999; }
			.plugin-card .version { color: #999; margin-left: 5px; }
			.stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; }
			.stat-card {
				text-align: center;
				padding: 20px;
				background: #f9f9f9;
				border-radius: 8px;
			}
			.stat-card .stat-value { display: block; font-size: 32px; font-weight: bold; color: #3498db; }
			.stat-card .stat-label { display: block; font-size: 12px; color: #666; margin-top: 5px; }
			.actions-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; }
			.action-card {
				display: flex;
				flex-direction: column;
				align-items: center;
				gap: 10px;
				padding: 20px;
				background: #f9f9f9;
				border-radius: 8px;
				text-decoration: none;
				color: #333;
				transition: background 0.2s;
			}
			.action-card:hover { background: #e9e9e9; }
			.action-card .dashicons { font-size: 32px; width: 32px; height: 32px; color: #3498db; }
		</style>
		<?php
	}
}
