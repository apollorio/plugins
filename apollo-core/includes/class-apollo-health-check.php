<?php
/**
 * Apollo Health Check System
 *
 * Comprehensive health monitoring for all Apollo plugin components.
 * Validates CPTs, taxonomies, meta, REST endpoints, and relationships.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Health_Check
 *
 * System health validation and diagnostics.
 */
class Apollo_Health_Check {

	/**
	 * Health status constants.
	 */
	const STATUS_GOOD    = 'good';
	const STATUS_WARNING = 'warning';
	const STATUS_ERROR   = 'error';
	const STATUS_UNKNOWN = 'unknown';

	/**
	 * Run all health checks.
	 *
	 * @return array Complete health report.
	 */
	public static function run(): array {
		$start_time = \microtime( true );

		$report = array(
			'timestamp'   => \current_time( 'mysql' ),
			'overall'     => self::STATUS_GOOD,
			'duration_ms' => 0,
			'checks'      => array(
				'system'                => self::check_system(),
				'plugins'               => self::check_plugins(),
				'cpt_registration'      => self::check_cpts(),
				'taxonomy_registration' => self::check_taxonomies(),
				'meta_registration'     => self::check_meta(),
				'rest_endpoints'        => self::check_rest(),
				'relationships'         => self::check_relationships(),
				'permissions'           => self::check_permissions(),
				'database'              => self::check_database(),
				'cron'                  => self::check_cron(),
			),
		);

		// Calculate overall status.
		foreach ( $report['checks'] as $check ) {
			if ( $check['status'] === self::STATUS_ERROR ) {
				$report['overall'] = self::STATUS_ERROR;
				break;
			}
			if ( $check['status'] === self::STATUS_WARNING && $report['overall'] !== self::STATUS_ERROR ) {
				$report['overall'] = self::STATUS_WARNING;
			}
		}

		$report['duration_ms'] = ( \microtime( true ) - $start_time ) * 1000;

		// Store report.
		\update_option( 'apollo_last_health_check', $report );

		return $report;
	}

	/**
	 * Check system requirements.
	 *
	 * @return array
	 */
	private static function check_system(): array {
		$issues = array();

		// PHP Version.
		$min_php = '8.1.0';
		if ( \version_compare( PHP_VERSION, $min_php, '<' ) ) {
			$issues[] = array(
				'type'    => 'error',
				'message' => \sprintf( 'PHP %s required, running %s', $min_php, PHP_VERSION ),
			);
		}

		// WordPress Version.
		global $wp_version;
		$min_wp = '6.0.0';
		if ( \version_compare( $wp_version, $min_wp, '<' ) ) {
			$issues[] = array(
				'type'    => 'error',
				'message' => \sprintf( 'WordPress %s required, running %s', $min_wp, $wp_version ),
			);
		}

		// Memory Limit.
		$memory_limit = \wp_convert_hr_to_bytes( \ini_get( 'memory_limit' ) );
		$min_memory   = 128 * 1024 * 1024; // 128MB
		if ( $memory_limit < $min_memory && $memory_limit > 0 ) {
			$issues[] = array(
				'type'    => 'warning',
				'message' => \sprintf( 'Memory limit is %s, recommend at least 128M', \size_format( $memory_limit ) ),
			);
		}

		// Max Execution Time.
		$max_execution = (int) \ini_get( 'max_execution_time' );
		if ( $max_execution > 0 && $max_execution < 30 ) {
			$issues[] = array(
				'type'    => 'warning',
				'message' => \sprintf( 'Max execution time is %d seconds, recommend at least 30', $max_execution ),
			);
		}

		// Required extensions.
		$required_extensions = array( 'json', 'mbstring', 'mysqli' );
		foreach ( $required_extensions as $ext ) {
			if ( ! \extension_loaded( $ext ) ) {
				$issues[] = array(
					'type'    => 'error',
					'message' => \sprintf( 'Required PHP extension "%s" is not loaded', $ext ),
				);
			}
		}

		return self::build_check_result(
			'System Requirements',
			$issues,
			array(
				'php_version'  => PHP_VERSION,
				'wp_version'   => $wp_version,
				'memory_limit' => \ini_get( 'memory_limit' ),
			)
		);
	}

	/**
	 * Check Apollo plugins status.
	 *
	 * @return array
	 */
	private static function check_plugins(): array {
		$issues  = array();
		$plugins = array();

		$expected_plugins = array(
			'apollo-core'           => 'Apollo Core',
			'apollo-events-manager' => 'Apollo Events Manager',
			'apollo-social'         => 'Apollo Social',
			'apollo-rio'            => 'Apollo Rio',
		);

		$active_plugins = \get_option( 'active_plugins', array() );

		foreach ( $expected_plugins as $slug => $name ) {
			$is_active = false;
			$version   = null;

			foreach ( $active_plugins as $plugin ) {
				if ( \strpos( $plugin, $slug ) !== false ) {
					$is_active   = true;
					$plugin_data = \get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
					$version     = $plugin_data['Version'] ?? 'unknown';
					break;
				}
			}

			$plugins[ $slug ] = array(
				'name'    => $name,
				'active'  => $is_active,
				'version' => $version,
			);
		}

		// Check core is active.
		if ( ! $plugins['apollo-core']['active'] ) {
			$issues[] = array(
				'type'    => 'error',
				'message' => 'Apollo Core is not active',
			);
		}

		// Check companion plugins have core.
		foreach ( array( 'apollo-events-manager', 'apollo-social', 'apollo-rio' ) as $companion ) {
			if ( $plugins[ $companion ]['active'] && ! $plugins['apollo-core']['active'] ) {
				$issues[] = array(
					'type'    => 'error',
					'message' => \sprintf( '%s requires Apollo Core to be active', $expected_plugins[ $companion ] ),
				);
			}
		}

		return self::build_check_result( 'Plugin Status', $issues, array( 'plugins' => $plugins ) );
	}

	/**
	 * Check CPT registration.
	 *
	 * @return array
	 */
	private static function check_cpts(): array {
		$issues = array();
		$cpts   = array();

		$expected_cpts = array(
			'apollo-event'      => 'Events',
			'apollo-dj'         => 'DJs',
			'apollo-local'      => 'Locals/Venues',
			'apollo-classified' => 'Classifieds',
			'apollo-supplier'   => 'Suppliers',
			'apollo-social'     => 'Social Posts',
			'event_listing'     => 'Event Listings (Legacy)',
			'event_dj'          => 'Event DJs (Legacy)',
			'event_local'       => 'Event Locals (Legacy)',
		);

		foreach ( $expected_cpts as $cpt => $label ) {
			$registered   = \post_type_exists( $cpt );
			$cpts[ $cpt ] = array(
				'label'      => $label,
				'registered' => $registered,
			);

			if ( $registered ) {
				$post_type_obj                = \get_post_type_object( $cpt );
				$cpts[ $cpt ]['show_in_rest'] = $post_type_obj->show_in_rest ?? false;
				$cpts[ $cpt ]['rest_base']    = $post_type_obj->rest_base ?? $cpt;
			}
		}

		// Check for duplicates.
		$registered_cpts = \get_post_types( array(), 'names' );
		$apollo_cpts     = \array_filter(
			$registered_cpts,
			function ( $cpt ) {
				return \strpos( $cpt, 'apollo' ) !== false || \strpos( $cpt, 'event' ) !== false;
			}
		);

		// Check conflicts.
		$conflict_checks = array(
			array( 'apollo-event', 'event_listing' ),
			array( 'apollo-dj', 'event_dj' ),
			array( 'apollo-local', 'event_local' ),
		);

		foreach ( $conflict_checks as $pair ) {
			if ( \post_type_exists( $pair[0] ) && \post_type_exists( $pair[1] ) ) {
				$issues[] = array(
					'type'    => 'warning',
					'message' => \sprintf( 'Both %s and %s are registered (potential duplicate)', $pair[0], $pair[1] ),
				);
			}
		}

		// Count posts per CPT.
		foreach ( $cpts as $cpt => &$info ) {
			if ( $info['registered'] ) {
				$count              = \wp_count_posts( $cpt );
				$info['post_count'] = $count->publish ?? 0;
			}
		}

		return self::build_check_result( 'CPT Registration', $issues, array( 'cpts' => $cpts ) );
	}

	/**
	 * Check taxonomy registration.
	 *
	 * @return array
	 */
	private static function check_taxonomies(): array {
		$issues     = array();
		$taxonomies = array();

		$expected_taxonomies = array(
			'event_listing_category' => array( 'Events', 'Categories' ),
			'event_listing_type'     => array( 'Events', 'Types' ),
			'event_listing_tag'      => array( 'Events', 'Tags' ),
			'event_sounds'           => array( 'Events', 'Music Genres' ),
			'apollo_genre'           => array( 'Core', 'Genres' ),
			'apollo_location'        => array( 'Core', 'Locations' ),
		);

		foreach ( $expected_taxonomies as $taxonomy => $info ) {
			$registered              = \taxonomy_exists( $taxonomy );
			$taxonomies[ $taxonomy ] = array(
				'plugin'     => $info[0],
				'label'      => $info[1],
				'registered' => $registered,
			);

			if ( $registered ) {
				$tax_obj                                 = \get_taxonomy( $taxonomy );
				$taxonomies[ $taxonomy ]['object_types'] = $tax_obj->object_type ?? array();
				$taxonomies[ $taxonomy ]['show_in_rest'] = $tax_obj->show_in_rest ?? false;

				// Count terms.
				$term_count                            = \wp_count_terms(
					array(
						'taxonomy'   => $taxonomy,
						'hide_empty' => false,
					)
				);
				$taxonomies[ $taxonomy ]['term_count'] = \is_wp_error( $term_count ) ? 0 : $term_count;
			}
		}

		// Check taxonomy attachments.
		$expected_attachments = array(
			'event_listing_category' => array( 'apollo-event', 'event_listing' ),
			'event_sounds'           => array( 'apollo-event', 'apollo-dj', 'event_listing', 'event_dj' ),
		);

		foreach ( $expected_attachments as $taxonomy => $expected_types ) {
			if ( ! \taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$tax_obj        = \get_taxonomy( $taxonomy );
			$attached_types = $tax_obj->object_type ?? array();

			foreach ( $expected_types as $expected_type ) {
				if ( \post_type_exists( $expected_type ) && ! \in_array( $expected_type, $attached_types, true ) ) {
					$issues[] = array(
						'type'    => 'warning',
						'message' => \sprintf( 'Taxonomy %s is not attached to post type %s', $taxonomy, $expected_type ),
					);
				}
			}
		}

		return self::build_check_result( 'Taxonomy Registration', $issues, array( 'taxonomies' => $taxonomies ) );
	}

	/**
	 * Check meta registration.
	 *
	 * @return array
	 */
	private static function check_meta(): array {
		$issues    = array();
		$meta_keys = array();

		// Core meta keys to check.
		$expected_meta = array(
			'post' => array(
				'_event_dj_ids'        => 'Event DJs',
				'_event_local_ids'     => 'Event Venues',
				'_event_start_date'    => 'Event Start Date',
				'_event_end_date'      => 'Event End Date',
				'_classified_event_id' => 'Classified Event Link',
			),
			'user' => array(
				'_user_event_rsvps' => 'User RSVPs',
				'_user_followers'   => 'User Followers',
				'_user_following'   => 'User Following',
				'_user_favorites'   => 'User Favorites',
				'_apollo_points'    => 'User Points',
			),
		);

		foreach ( $expected_meta as $object_type => $keys ) {
			foreach ( $keys as $key => $label ) {
				$registered        = \registered_meta_key_exists( $object_type, $key );
				$meta_keys[ $key ] = array(
					'object_type' => $object_type,
					'label'       => $label,
					'registered'  => $registered,
				);

				if ( ! $registered ) {
					// Check if meta exists even if not registered.
					global $wpdb;
					$table   = $object_type === 'user' ? $wpdb->usermeta : $wpdb->postmeta;
					$key_col = $object_type === 'user' ? 'meta_key' : 'meta_key';

					// phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$exists = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT COUNT(*) FROM {$table} WHERE {$key_col} = %s LIMIT 1",
							$key
						)
					);

					if ( $exists ) {
						$issues[] = array(
							'type'    => 'warning',
							'message' => \sprintf( 'Meta key %s exists in database but is not registered', $key ),
						);
					}
				}
			}
		}

		return self::build_check_result( 'Meta Registration', $issues, array( 'meta_keys' => $meta_keys ) );
	}

	/**
	 * Check REST endpoints.
	 *
	 * @return array
	 */
	private static function check_rest(): array {
		$issues    = array();
		$endpoints = array();

		$expected_endpoints = array(
			'/apollo/v1/events'                      => array( 'GET', 'POST' ),
			'/apollo/v1/events/(?P<id>[\d]+)'        => array( 'GET', 'PUT', 'DELETE' ),
			'/apollo/v1/djs'                         => array( 'GET' ),
			'/apollo/v1/locals'                      => array( 'GET' ),
			'/apollo/v1/classifieds'                 => array( 'GET', 'POST' ),
			'/apollo/v1/social/feed'                 => array( 'GET', 'POST' ),
			'/apollo/v1/users/(?P<id>[\d]+)/profile' => array( 'GET', 'PUT' ),
			'/apollo/v1/relationships'               => array( 'GET' ),
		);

		// Get registered routes.
		$rest_server = \rest_get_server();
		$routes      = $rest_server->get_routes();

		foreach ( $expected_endpoints as $route => $expected_methods ) {
			$full_route         = '/' . \trim( $route, '/' );
			$found              = false;
			$registered_methods = array();

			foreach ( $routes as $registered_route => $handlers ) {
				if ( \preg_match( '#^' . \preg_quote( $full_route, '#' ) . '$#', $registered_route ) ||
					$registered_route === $full_route ) {
					$found = true;
					foreach ( $handlers as $handler ) {
						if ( isset( $handler['methods'] ) ) {
							$registered_methods = \array_merge(
								$registered_methods,
								\array_keys( $handler['methods'] )
							);
						}
					}
					break;
				}
			}

			$endpoints[ $route ] = array(
				'registered'         => $found,
				'expected_methods'   => $expected_methods,
				'registered_methods' => \array_unique( $registered_methods ),
			);

			if ( ! $found ) {
				$issues[] = array(
					'type'    => 'warning',
					'message' => \sprintf( 'REST endpoint %s is not registered', $route ),
				);
			}
		}

		// Check namespace exists.
		$namespaces        = $rest_server->get_namespaces();
		$apollo_namespaces = \array_filter(
			$namespaces,
			function ( $ns ) {
				return \strpos( $ns, 'apollo' ) !== false;
			}
		);

		return self::build_check_result(
			'REST Endpoints',
			$issues,
			array(
				'endpoints'  => $endpoints,
				'namespaces' => $apollo_namespaces,
			)
		);
	}

	/**
	 * Check relationships.
	 *
	 * @return array
	 */
	private static function check_relationships(): array {
		$issues        = array();
		$relationships = array();

		if ( ! \class_exists( Apollo_Relationships::class ) ) {
			return self::build_check_result(
				'Relationships',
				array(
					array(
						'type'    => 'error',
						'message' => 'Apollo_Relationships class not found',
					),
				),
				array()
			);
		}

		$schema = Apollo_Relationships::get_schema();

		foreach ( $schema as $name => $definition ) {
			$from_type = $definition['from'] ?? '';
			$to_type   = $definition['to'] ?? '';

			$relationships[ $name ] = array(
				'from'  => $from_type,
				'to'    => $to_type,
				'type'  => $definition['type'] ?? '',
				'valid' => true,
			);

			// Check if from type exists.
			if ( $from_type !== 'user' && ! \post_type_exists( $from_type ) ) {
				$relationships[ $name ]['valid'] = false;
				$issues[]                        = array(
					'type'    => 'warning',
					'message' => \sprintf( 'Relationship %s: source type %s does not exist', $name, $from_type ),
				);
			}

			// Check if to type exists.
			if ( \is_array( $to_type ) ) {
				foreach ( $to_type as $t ) {
					if ( $t !== 'user' && ! \post_type_exists( $t ) ) {
						$relationships[ $name ]['valid'] = false;
					}
				}
			} elseif ( $to_type !== 'user' && ! \post_type_exists( $to_type ) ) {
				$relationships[ $name ]['valid'] = false;
				$issues[]                        = array(
					'type'    => 'warning',
					'message' => \sprintf( 'Relationship %s: target type %s does not exist', $name, $to_type ),
				);
			}
		}

		return self::build_check_result(
			'Relationships',
			$issues,
			array(
				'relationships' => $relationships,
				'total'         => \count( $schema ),
			)
		);
	}

	/**
	 * Check permissions and capabilities.
	 *
	 * @return array
	 */
	private static function check_permissions(): array {
		$issues = array();
		$caps   = array();

		$expected_caps = array(
			'edit_apollo_events'    => 'Edit Events',
			'publish_apollo_events' => 'Publish Events',
			'delete_apollo_events'  => 'Delete Events',
			'moderate'              => 'Moderate Content',
			'apollo_create_nucleo'  => 'Create Núcleo',
		);

		$admin_role = \get_role( 'administrator' );

		foreach ( $expected_caps as $cap => $label ) {
			$admin_has    = $admin_role ? $admin_role->has_cap( $cap ) : false;
			$caps[ $cap ] = array(
				'label'     => $label,
				'admin_has' => $admin_has,
			);
		}

		// Check moderator role exists.
		$moderator = \get_role( 'apollo_moderator' );
		if ( ! $moderator ) {
			$issues[] = array(
				'type'    => 'info',
				'message' => 'Apollo Moderator role is not defined',
			);
		}

		return self::build_check_result( 'Permissions', $issues, array( 'capabilities' => $caps ) );
	}

	/**
	 * Check database tables.
	 *
	 * @return array
	 */
	private static function check_database(): array {
		global $wpdb;
		$issues = array();
		$tables = array();

		$expected_tables = array(
			'apollo_activity_log'  => 'Activity Log',
			'apollo_relationships' => 'Relationships Pivot',
			'apollo_event_queue'   => 'Event Queue',
		);

		foreach ( $expected_tables as $table => $label ) {
			$full_name = $wpdb->prefix . $table;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					'SHOW TABLES LIKE %s',
					$full_name
				)
			) === $full_name;

			$tables[ $table ] = array(
				'label'  => $label,
				'exists' => $exists,
			);

			if ( $exists ) {
				// Get row count.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$count                         = $wpdb->get_var( "SELECT COUNT(*) FROM {$full_name}" );
				$tables[ $table ]['row_count'] = (int) $count;
			} else {
				$issues[] = array(
					'type'    => 'error',
					'message' => \sprintf( 'Database table %s does not exist', $full_name ),
				);
			}
		}

		// Check DB version.
		$db_version       = \get_option( Apollo_Activation_Controller::DB_VERSION_OPTION, '0.0.0' );
		$expected_version = Apollo_Activation_Controller::DB_VERSION;

		if ( \version_compare( $db_version, $expected_version, '<' ) ) {
			$issues[] = array(
				'type'    => 'warning',
				'message' => \sprintf( 'Database needs migration: current %s, expected %s', $db_version, $expected_version ),
			);
		}

		return self::build_check_result(
			'Database',
			$issues,
			array(
				'tables'           => $tables,
				'db_version'       => $db_version,
				'expected_version' => $expected_version,
			)
		);
	}

	/**
	 * Check cron jobs.
	 *
	 * @return array
	 */
	private static function check_cron(): array {
		$issues    = array();
		$cron_jobs = array();

		$expected_jobs = array(
			'apollo_daily_cleanup',
			'apollo_weekly_digest',
			'apollo_relationship_integrity_check',
			'apollo_event_reminders',
			'apollo_process_event_queue',
		);

		foreach ( $expected_jobs as $hook ) {
			$timestamp          = \wp_next_scheduled( $hook );
			$cron_jobs[ $hook ] = array(
				'scheduled' => false !== $timestamp,
				'next_run'  => $timestamp ? \date( 'Y-m-d H:i:s', $timestamp ) : null,
			);

			if ( ! $timestamp ) {
				$issues[] = array(
					'type'    => 'warning',
					'message' => \sprintf( 'Cron job %s is not scheduled', $hook ),
				);
			}
		}

		// Check if WP Cron is disabled.
		if ( \defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			$issues[] = array(
				'type'    => 'info',
				'message' => 'WP Cron is disabled. Ensure system cron is configured.',
			);
		}

		return self::build_check_result( 'Cron Jobs', $issues, array( 'cron_jobs' => $cron_jobs ) );
	}

	/**
	 * Build check result array.
	 *
	 * @param string $name   Check name.
	 * @param array  $issues Issues found.
	 * @param array  $data   Additional data.
	 * @return array
	 */
	private static function build_check_result( string $name, array $issues, array $data ): array {
		$status = self::STATUS_GOOD;

		foreach ( $issues as $issue ) {
			if ( ( $issue['type'] ?? '' ) === 'error' ) {
				$status = self::STATUS_ERROR;
				break;
			}
			if ( ( $issue['type'] ?? '' ) === 'warning' && $status !== self::STATUS_ERROR ) {
				$status = self::STATUS_WARNING;
			}
		}

		return array(
			'name'   => $name,
			'status' => $status,
			'issues' => $issues,
			'data'   => $data,
		);
	}

	/**
	 * Render admin health page.
	 *
	 * @return void
	 */
	public static function render_admin_page(): void {
		$report = self::run();

		?>
		<div class="wrap apollo-health-check">
			<h1><?php \esc_html_e( 'Apollo Health Check', 'apollo-core' ); ?></h1>

			<div class="health-status-overall status-<?php echo \esc_attr( $report['overall'] ); ?>">
				<span class="status-icon"></span>
				<span class="status-text">
					<?php
					switch ( $report['overall'] ) {
						case self::STATUS_GOOD:
							\esc_html_e( 'All systems operational', 'apollo-core' );
							break;
						case self::STATUS_WARNING:
							\esc_html_e( 'Some issues detected', 'apollo-core' );
							break;
						case self::STATUS_ERROR:
							\esc_html_e( 'Critical issues found', 'apollo-core' );
							break;
					}
					?>
				</span>
				<span class="status-meta">
					<?php
					echo \esc_html(
						\sprintf(
						/* translators: %1$s: timestamp, %2$s: duration */
							\__( 'Checked at %1$s (took %2$sms)', 'apollo-core' ),
							$report['timestamp'],
							\number_format( $report['duration_ms'], 2 )
						)
					);
					?>
				</span>
			</div>

			<div class="health-checks-grid">
				<?php foreach ( $report['checks'] as $key => $check ) : ?>
					<div class="health-check-card status-<?php echo \esc_attr( $check['status'] ); ?>">
						<h3>
							<span class="status-dot"></span>
							<?php echo \esc_html( $check['name'] ); ?>
						</h3>

						<?php if ( ! empty( $check['issues'] ) ) : ?>
							<ul class="issues-list">
								<?php foreach ( $check['issues'] as $issue ) : ?>
									<li class="issue-<?php echo \esc_attr( $issue['type'] ); ?>">
										<?php echo \esc_html( $issue['message'] ); ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p class="no-issues"><?php \esc_html_e( 'No issues detected', 'apollo-core' ); ?></p>
						<?php endif; ?>

						<button type="button" class="toggle-details" data-check="<?php echo \esc_attr( $key ); ?>">
							<?php \esc_html_e( 'Show Details', 'apollo-core' ); ?>
						</button>
						<div class="check-details" style="display:none;">
							<pre><?php echo \esc_html( \wp_json_encode( $check['data'], JSON_PRETTY_PRINT ) ); ?></pre>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="health-actions">
				<form method="post" action="">
					<?php \wp_nonce_field( 'apollo_health_action' ); ?>
					<button type="submit" name="apollo_action" value="rerun_check" class="button button-primary">
						<?php \esc_html_e( 'Re-run Health Check', 'apollo-core' ); ?>
					</button>
					<button type="submit" name="apollo_action" value="repair_all" class="button">
						<?php \esc_html_e( 'Attempt Auto-Repair', 'apollo-core' ); ?>
					</button>
				</form>
			</div>
		</div>

		<style>
			.apollo-health-check .health-status-overall {
				padding: 20px;
				margin: 20px 0;
				border-radius: 8px;
				display: flex;
				align-items: center;
				gap: 15px;
			}
			.apollo-health-check .status-good { background: #d4edda; border-left: 4px solid #28a745; }
			.apollo-health-check .status-warning { background: #fff3cd; border-left: 4px solid #ffc107; }
			.apollo-health-check .status-error { background: #f8d7da; border-left: 4px solid #dc3545; }
			.apollo-health-check .health-checks-grid {
				display: grid;
				grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
				gap: 20px;
				margin: 20px 0;
			}
			.apollo-health-check .health-check-card {
				background: #fff;
				border: 1px solid #ddd;
				border-radius: 8px;
				padding: 15px;
			}
			.apollo-health-check .health-check-card h3 { margin-top: 0; display: flex; align-items: center; gap: 8px; }
			.apollo-health-check .status-dot {
				width: 12px;
				height: 12px;
				border-radius: 50%;
				display: inline-block;
			}
			.apollo-health-check .status-good .status-dot { background: #28a745; }
			.apollo-health-check .status-warning .status-dot { background: #ffc107; }
			.apollo-health-check .status-error .status-dot { background: #dc3545; }
			.apollo-health-check .issues-list { margin: 10px 0; padding-left: 20px; }
			.apollo-health-check .issue-error { color: #dc3545; }
			.apollo-health-check .issue-warning { color: #856404; }
			.apollo-health-check .issue-info { color: #0c5460; }
			.apollo-health-check .check-details pre {
				background: #f5f5f5;
				padding: 10px;
				overflow: auto;
				max-height: 200px;
				font-size: 11px;
			}
		</style>

		<script>
			document.querySelectorAll('.toggle-details').forEach(btn => {
				btn.addEventListener('click', () => {
					const details = btn.nextElementSibling;
					details.style.display = details.style.display === 'none' ? 'block' : 'none';
					btn.textContent = details.style.display === 'none' ? 'Show Details' : 'Hide Details';
				});
			});
		</script>
		<?php
	}
}
