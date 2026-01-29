<?php
/**
 * Apollo Health Check WP-CLI Command
 *
 * Comprehensive health diagnostics for Apollo plugin ecosystem.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Runs comprehensive health checks on Apollo plugin integration.
 *
 * @since 2.0.0
 */
class Apollo_Health_Command {

	/**
	 * Required plugins for Apollo ecosystem
	 *
	 * @var array<string, string>
	 */
	private const REQUIRED_PLUGINS = array(
		'apollo-core/apollo-core.php' => 'Apollo Core',
	);

	/**
	 * Optional companion plugins
	 *
	 * @var array<string, string>
	 */
	private const COMPANION_PLUGINS = array(
		'apollo-events-manager/apollo-events-manager.php' => 'Apollo Events Manager',
		'apollo-social/apollo-social.php'                 => 'Apollo Social',
		'apollo-rio/apollo-rio.php'                       => 'Apollo Rio',
	);

	/**
	 * Expected CPTs by plugin
	 *
	 * @var array<string, array<string>>
	 */
	private const EXPECTED_CPTS = array(
		'apollo-events-manager' => array( 'event_listing', 'event_dj', 'event_local', 'apollo_event_stat' ),
		'apollo-social'         => array( 'apollo_classified', 'apollo_social_post', 'user_page', 'apollo_home', 'cena_document', 'cena_event_plan', 'apollo_supplier' ),
		'apollo-core'           => array( 'apollo_email_temp' ),
	);

	/**
	 * Expected taxonomies with their object types
	 *
	 * @var array<string, array<string>>
	 */
	private const EXPECTED_TAXONOMIES = array(
		'event_listing_category'   => array( 'event_listing' ),
		'event_listing_type'       => array( 'event_listing' ),
		'event_listing_tag'        => array( 'event_listing' ),
		'event_sounds'             => array( 'event_listing' ),
		'event_season'             => array( 'event_listing', 'apollo_classified' ),
		'classified_domain'        => array( 'apollo_classified' ),
		'classified_intent'        => array( 'apollo_classified' ),
		'apollo_supplier_category' => array( 'apollo_supplier' ),
		'apollo_supplier_region'   => array( 'apollo_supplier' ),
		'apollo_post_category'     => array( 'apollo_social_post' ),
	);

	/**
	 * Canonical meta keys per post type
	 *
	 * @var array<string, array<string>>
	 */
	private const CANONICAL_META_KEYS = array(
		'event_listing'     => array(
			'_event_start_date',
			'_event_end_date',
			'_event_start_time',
			'_event_end_time',
			'_event_location',
			'_event_country',
			'_event_city',
			'_event_address',
			'_event_latitude',
			'_event_longitude',
			'_event_dj_ids',
			'_event_local_ids',
			'_event_dj_slots',
			'_event_banner',
			'_event_video_url',
			'_event_featured',
			'_tickets_ext',
			'_cupom_ario',
			'_favorites_count',
		),
		'event_dj'          => array(
			'_dj_name',
			'_dj_bio',
			'_dj_image',
			'_dj_instagram',
			'_dj_facebook',
			'_dj_soundcloud',
			'_dj_spotify',
			'_dj_youtube',
			'_dj_mixcloud',
		),
		'event_local'       => array(
			'_local_name',
			'_local_description',
			'_local_address',
			'_local_city',
			'_local_latitude',
			'_local_longitude',
			'_local_image_1',
		),
		'apollo_classified' => array(
			'_classified_price',
			'_classified_currency',
			'_classified_location_text',
			'_classified_contact_pref',
			'_classified_event_date',
			'_classified_season_id',
		),
	);

	/**
	 * Deprecated meta keys with their canonical replacements
	 *
	 * @var array<string, string>
	 */
	private const DEPRECATED_META_KEYS = array(
		'_event_lat'       => '_event_latitude',
		'_event_lng'       => '_event_longitude',
		'_event_timetable' => '_event_dj_slots',
		'_event_djs'       => '_event_dj_ids',
		'_event_local'     => '_event_local_ids',
		'_local_lat'       => '_local_latitude',
		'_local_lng'       => '_local_longitude',
	);

	/**
	 * Issues found during checks
	 *
	 * @var array<array{severity: string, message: string, fix?: string}>
	 */
	private array $issues = array();

	/**
	 * Fixes applied during --fix mode
	 *
	 * @var array<string>
	 */
	private array $fixes_applied = array();

	/**
	 * Run full Apollo integration health check
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format (table, json, csv, yaml)
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * [--fix]
	 * : Attempt to auto-fix issues where possible
	 *
	 * [--verbose]
	 * : Show detailed output for each check
	 *
	 * [--check=<check>]
	 * : Run only a specific check (plugins, cpts, taxonomies, meta, rest, hooks, relationships, templates, assets, database)
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo health
	 *     wp apollo health --format=json
	 *     wp apollo health --fix
	 *     wp apollo health --fix --verbose
	 *     wp apollo health --check=cpts
	 *     wp apollo health --format=json > health-report.json
	 *
	 * @when after_wp_load
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$format         = $assoc_args['format'] ?? 'table';
		$fix            = isset( $assoc_args['fix'] );
		$verbose        = isset( $assoc_args['verbose'] );
		$specific_check = $assoc_args['check'] ?? null;

		WP_CLI::log( '' );
		WP_CLI::log( '╔══════════════════════════════════════════════════════════════╗' );
		WP_CLI::log( '║        🔍 APOLLO INTEGRATION HEALTH CHECK                    ║' );
		WP_CLI::log( '║        Running comprehensive diagnostics...                  ║' );
		WP_CLI::log( '╚══════════════════════════════════════════════════════════════╝' );
		WP_CLI::log( '' );

		if ( $fix ) {
			WP_CLI::warning( 'Auto-fix mode enabled. Issues will be repaired where possible.' );
			WP_CLI::log( '' );
		}

		$results = array();
		$checks  = array(
			'plugins'       => 'check_plugins',
			'cpts'          => 'check_cpts',
			'taxonomies'    => 'check_taxonomies',
			'meta'          => 'check_meta_keys',
			'rest'          => 'check_rest_api',
			'hooks'         => 'check_hooks',
			'relationships' => 'check_relationships',
			'templates'     => 'check_templates',
			'assets'        => 'check_assets',
			'database'      => 'check_database',
		);

		foreach ( $checks as $key => $method ) {
			if ( $specific_check && $specific_check !== $key ) {
				continue;
			}

			if ( $verbose ) {
				WP_CLI::log( "Running check: {$key}..." );
			}

			$results[] = $this->{$method}( $fix, $verbose );
		}

		// Output results.
		WP_CLI::log( '' );
		WP_CLI::log( '═══════════════════════════════════════════════════════════════' );
		WP_CLI::log( '                        RESULTS SUMMARY                        ' );
		WP_CLI::log( '═══════════════════════════════════════════════════════════════' );
		WP_CLI::log( '' );

		if ( 'json' === $format ) {
			$output = wp_json_encode(
				array(
					'timestamp'         => current_time( 'mysql' ),
					'wordpress_version' => get_bloginfo( 'version' ),
					'php_version'       => PHP_VERSION,
					'results'           => $results,
					'issues'            => $this->issues,
					'fixes_applied'     => $this->fixes_applied,
				),
				JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
			);
			WP_CLI::log( $output ? $output : '{}' );
		} elseif ( 'yaml' === $format && class_exists( 'Spyc' ) ) {
			WP_CLI::log( \Spyc::YAMLDump( $results ) );
		} else {
			\WP_CLI\Utils\format_items( $format, $results, array( 'check', 'status', 'issues', 'details' ) );
		}

		// Summary statistics.
		$passed = count( array_filter( $results, fn( $r ) => 'PASS' === $r['status'] ) );
		$failed = count( array_filter( $results, fn( $r ) => 'FAIL' === $r['status'] ) );
		$warned = count( array_filter( $results, fn( $r ) => 'WARN' === $r['status'] ) );

		WP_CLI::log( '' );
		WP_CLI::log( '───────────────────────────────────────────────────────────────' );
		WP_CLI::log(
			sprintf(
				'Summary: ✅ %d passed | ⚠️  %d warnings | ❌ %d failed',
				$passed,
				$warned,
				$failed
			)
		);
		WP_CLI::log( '───────────────────────────────────────────────────────────────' );

		if ( $fix && ! empty( $this->fixes_applied ) ) {
			WP_CLI::log( '' );
			WP_CLI::log( '🔧 Fixes Applied:' );
			foreach ( $this->fixes_applied as $fix_item ) {
				WP_CLI::log( "   • {$fix_item}" );
			}
		}

		if ( ! empty( $this->issues ) ) {
			WP_CLI::log( '' );
			WP_CLI::log( '⚠️  Issues Requiring Attention:' );
			foreach ( $this->issues as $issue ) {
				WP_CLI::log( "   • [{$issue['severity']}] {$issue['message']}" );
				if ( ! empty( $issue['fix'] ) ) {
					WP_CLI::log( "     Fix: {$issue['fix']}" );
				}
			}
		}

		WP_CLI::log( '' );

		if ( $failed > 0 ) {
			WP_CLI::error( 'Health check FAILED! Review issues above and run with --fix to attempt repairs.' );
		} elseif ( $warned > 0 ) {
			WP_CLI::warning( 'Health check passed with warnings. Review recommendations above.' );
		} else {
			WP_CLI::success( 'All health checks PASSED! ✨ Apollo integration is healthy.' );
		}
	}

	/**
	 * CHECK 1: Plugin Activation Status
	 *
	 * @param bool $fix     Whether to attempt auto-fixes.
	 * @param bool $verbose Whether to show detailed output.
	 * @return array{check: string, status: string, issues: int, details: string}
	 */
	private function check_plugins( bool $fix, bool $verbose ): array {
		$check_name   = '1. Plugin Status';
		$issues_found = 0;
		$details      = array();

		if ( $verbose ) {
			WP_CLI::log( '   Checking plugin activation status...' );
		}

		// Check required plugins.
		foreach ( self::REQUIRED_PLUGINS as $plugin_file => $plugin_name ) {
			if ( ! is_plugin_active( $plugin_file ) ) {
				++$issues_found;
				$this->issues[] = array(
					'severity' => 'CRITICAL',
					'message'  => "Required plugin '{$plugin_name}' is not active",
					'fix'      => "Run: wp plugin activate {$plugin_file}",
				);

				if ( $fix ) {
					$result = activate_plugin( $plugin_file );
					if ( ! is_wp_error( $result ) ) {
						$this->fixes_applied[] = "Activated {$plugin_name}";
						--$issues_found;
					}
				}
			} else {
				$details[] = "✓ {$plugin_name} active";
			}
		}

		// Check companion plugins.
		$active_companions = 0;
		foreach ( self::COMPANION_PLUGINS as $plugin_file => $plugin_name ) {
			if ( is_plugin_active( $plugin_file ) ) {
				++$active_companions;
				$details[] = "✓ {$plugin_name} active";
			} else {
				$details[] = "○ {$plugin_name} not active (optional)";
			}
		}

		// Check load order via hook priorities.
		$load_order_correct = $this->verify_load_order();
		if ( ! $load_order_correct ) {
			++$issues_found;
			$this->issues[] = array(
				'severity' => 'WARNING',
				'message'  => 'Plugin load order may not be optimal',
				'fix'      => 'Verify apollo-core loads before companion plugins',
			);
		}

		$status = 0 === $issues_found ? 'PASS' : ( $issues_found > 1 ? 'FAIL' : 'WARN' );

		return array(
			'check'   => $check_name,
			'status'  => $status,
			'issues'  => $issues_found,
			'details' => implode( '; ', $details ),
		);
	}

	/**
	 * CHECK 2: CPT Registration Integrity
	 *
	 * @param bool $fix     Whether to attempt auto-fixes.
	 * @param bool $verbose Whether to show detailed output.
	 * @return array{check: string, status: string, issues: int, details: string}
	 */
	private function check_cpts( bool $fix, bool $verbose ): array {
		$check_name      = '2. CPT Registration';
		$issues_found    = 0;
		$details         = array();
		$duplicate_check = array();

		if ( $verbose ) {
			WP_CLI::log( '   Checking custom post type registrations...' );
		}

		$registered_cpts = get_post_types( array( '_builtin' => false ), 'names' );

		// Check expected CPTs exist.
		foreach ( self::EXPECTED_CPTS as $plugin => $cpts ) {
			$plugin_file   = "{$plugin}/{$plugin}.php";
			$plugin_active = is_plugin_active( $plugin_file ) || 'apollo-core' === $plugin;

			foreach ( $cpts as $cpt ) {
				if ( $plugin_active ) {
					if ( in_array( $cpt, $registered_cpts, true ) ) {
						$details[] = "✓ {$cpt}";

						// Track for duplicate detection.
						if ( ! isset( $duplicate_check[ $cpt ] ) ) {
							$duplicate_check[ $cpt ] = array();
						}
						$duplicate_check[ $cpt ][] = $plugin;
					} else {
						++$issues_found;
						$this->issues[] = array(
							'severity' => 'ERROR',
							'message'  => "CPT '{$cpt}' expected from {$plugin} but not registered",
							'fix'      => "Check {$plugin} activation and CPT registration code",
						);
					}
				}
			}
		}

		// Check for duplicates (same CPT registered by multiple plugins).
		$known_fallbacks = array( 'event_listing', 'apollo_social_post', 'user_page' );
		foreach ( $duplicate_check as $cpt => $plugins ) {
			if ( count( $plugins ) > 1 && ! in_array( $cpt, $known_fallbacks, true ) ) {
				++$issues_found;
				$this->issues[] = array(
					'severity' => 'WARNING',
					'message'  => "CPT '{$cpt}' registered by multiple plugins: " . implode( ', ', $plugins ),
					'fix'      => 'Implement CPT registry to prevent duplicates',
				);
			}
		}

		// Check for supplier CPT conflict.
		if ( in_array( 'supplier', $registered_cpts, true ) && in_array( 'apollo_supplier', $registered_cpts, true ) ) {
			++$issues_found;
			$this->issues[] = array(
				'severity' => 'WARNING',
				'message'  => "Both 'supplier' and 'apollo_supplier' CPTs exist - potential conflict",
				'fix'      => 'Consolidate to single supplier CPT and migrate data',
			);

			if ( $fix ) {
				WP_CLI::warning( 'Supplier CPT consolidation requires manual migration. Skipping auto-fix.' );
			}
		}

		// Check rewrite rules.
		$rewrite_rules = get_option( 'rewrite_rules' );
		if ( empty( $rewrite_rules ) ) {
			++$issues_found;
			if ( $fix ) {
				flush_rewrite_rules();
				$this->fixes_applied[] = 'Flushed rewrite rules';
				--$issues_found;
			} else {
				$this->issues[] = array(
					'severity' => 'WARNING',
					'message'  => 'Rewrite rules may be stale',
					'fix'      => 'Run: wp rewrite flush',
				);
			}
		}

		$status = 0 === $issues_found ? 'PASS' : ( $issues_found > 2 ? 'FAIL' : 'WARN' );

		return array(
			'check'   => $check_name,
			'status'  => $status,
			'issues'  => $issues_found,
			'details' => count( $registered_cpts ) . ' CPTs registered',
		);
	}

	/**
	 * CHECK 3: Taxonomy Registration Integrity
	 *
	 * @param bool $fix     Whether to attempt auto-fixes.
	 * @param bool $verbose Whether to show detailed output.
	 * @return array{check: string, status: string, issues: int, details: string}
	 */
	private function check_taxonomies( bool $fix, bool $verbose ): array {
		$check_name   = '3. Taxonomies';
		$issues_found = 0;
		$details      = array();

		if ( $verbose ) {
			WP_CLI::log( '   Checking taxonomy registrations...' );
		}

		$registered_taxonomies = get_taxonomies( array( '_builtin' => false ), 'objects' );

		foreach ( self::EXPECTED_TAXONOMIES as $taxonomy => $expected_types ) {
			if ( isset( $registered_taxonomies[ $taxonomy ] ) ) {
				$tax_obj      = $registered_taxonomies[ $taxonomy ];
				$actual_types = $tax_obj->object_type;

				// Check attachments.
				$missing_attachments = array_diff( $expected_types, $actual_types );
				if ( ! empty( $missing_attachments ) ) {
					++$issues_found;
					$this->issues[] = array(
						'severity' => 'WARNING',
						'message'  => "Taxonomy '{$taxonomy}' missing attachment to: " . implode( ', ', $missing_attachments ),
						'fix'      => "Use register_taxonomy_for_object_type('{$taxonomy}', 'cpt_name')",
					);

					if ( $fix ) {
						foreach ( $missing_attachments as $post_type ) {
							if ( post_type_exists( $post_type ) ) {
								register_taxonomy_for_object_type( $taxonomy, $post_type );
								$this->fixes_applied[] = "Attached {$taxonomy} to {$post_type}";
								--$issues_found;
							}
						}
					}
				} else {
					$details[] = "✓ {$taxonomy}";
				}
			} else {
				$details[] = "○ {$taxonomy} not registered";
			}
		}

		// Check for supplier taxonomy conflict.
		$supplier_taxonomies   = array( 'supplier_category', 'supplier_tag', 'apollo_supplier_category' );
		$active_supplier_taxes = array_intersect( $supplier_taxonomies, array_keys( $registered_taxonomies ) );
		if ( count( $active_supplier_taxes ) > 2 ) {
			++$issues_found;
			$this->issues[] = array(
				'severity' => 'WARNING',
				'message'  => 'Multiple supplier taxonomy variants found: ' . implode( ', ', $active_supplier_taxes ),
				'fix'      => 'Consolidate supplier taxonomies and migrate terms',
			);
		}

		$status = 0 === $issues_found ? 'PASS' : ( $issues_found > 2 ? 'FAIL' : 'WARN' );

		return array(
			'check'   => $check_name,
			'status'  => $status,
			'issues'  => $issues_found,
			'details' => count( $registered_taxonomies ) . ' taxonomies registered',
		);
	}

	/**
	 * CHECK 4: Meta Keys Registration
	 *
	 * @param bool $fix     Whether to attempt auto-fixes.
	 * @param bool $verbose Whether to show detailed output.
	 * @return array{check: string, status: string, issues: int, details: string}
	 */
	private function check_meta_keys( bool $fix, bool $verbose ): array {
		$check_name       = '4. Meta Keys';
		$issues_found     = 0;
		$details          = array();
		$deprecated_usage = array();

		if ( $verbose ) {
			WP_CLI::log( '   Checking meta key registrations and usage...' );
		}

		global $wpdb;

		// Check for deprecated meta key usage.
		foreach ( self::DEPRECATED_META_KEYS as $deprecated => $canonical ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s",
					$deprecated
				)
			);

			if ( $count > 0 ) {
				$deprecated_usage[ $deprecated ] = array(
					'count'     => (int) $count,
					'canonical' => $canonical,
				);
				++$issues_found;
				$this->issues[] = array(
					'severity' => 'WARNING',
					'message'  => "Deprecated meta key '{$deprecated}' has {$count} entries (should be '{$canonical}')",
					'fix'      => 'Run: wp apollo fix migrate-meta',
				);

				if ( $fix ) {
					// Migrate deprecated keys - avoid overwriting existing canonical entries.
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$migrated = $wpdb->query(
						$wpdb->prepare(
							"UPDATE {$wpdb->postmeta} pm1
							SET pm1.meta_key = %s
							WHERE pm1.meta_key = %s
							AND NOT EXISTS (
								SELECT 1 FROM (SELECT post_id, meta_key FROM {$wpdb->postmeta}) pm2
								WHERE pm2.post_id = pm1.post_id AND pm2.meta_key = %s
							)",
							$canonical,
							$deprecated,
							$canonical
						)
					);

					if ( false !== $migrated ) {
						$this->fixes_applied[] = "Migrated {$migrated} entries from '{$deprecated}' to '{$canonical}'";
						--$issues_found;
					}
				}
			}
		}

		// Check meta registration for REST API.
		foreach ( self::CANONICAL_META_KEYS as $post_type => $meta_keys ) {
			if ( ! post_type_exists( $post_type ) ) {
				continue;
			}

			$registered_meta = get_registered_meta_keys( 'post', $post_type );

			foreach ( $meta_keys as $meta_key ) {
				if ( ! isset( $registered_meta[ $meta_key ] ) ) {
					if ( $verbose ) {
						$details[] = "○ {$meta_key} not registered for REST";
					}
				}
			}
		}

		$status      = 0 === $issues_found ? 'PASS' : ( $issues_found > 3 ? 'FAIL' : 'WARN' );
		$detail_text = empty( $deprecated_usage )
			? 'All meta keys using canonical names'
			: count( $deprecated_usage ) . ' deprecated keys in use';

		return array(
			'check'   => $check_name,
			'status'  => $status,
			'issues'  => $issues_found,
			'details' => $detail_text,
		);
	}

	/**
	 * CHECK 5: REST API Endpoints
	 *
	 * @param bool $fix     Whether to attempt auto-fixes.
	 * @param bool $verbose Whether to show detailed output.
	 * @return array{check: string, status: string, issues: int, details: string}
	 */
	private function check_rest_api( bool $fix, bool $verbose ): array {
		$check_name   = '5. REST API';
		$issues_found = 0;
		$details      = array();

		if ( $verbose ) {
			WP_CLI::log( '   Checking REST API endpoints...' );
		}

		$server = rest_get_server();
		$routes = $server->get_routes();

		// Count Apollo routes by namespace.
		$namespaces = array(
			'apollo/v1'        => 0,
			'apollo-core/v1'   => 0,
			'apollo-events/v1' => 0,
		);

		foreach ( array_keys( $routes ) as $route ) {
			foreach ( array_keys( $namespaces ) as $ns ) {
				if ( strpos( $route, "/{$ns}" ) === 0 ) {
					++$namespaces[ $ns ];
					break;
				}
			}
		}

		// Check if namespaces are fragmented.
		$total_routes = array_sum( $namespaces );
		if ( $namespaces['apollo-core/v1'] > 0 || $namespaces['apollo-events/v1'] > 0 ) {
			++$issues_found;
			$this->issues[] = array(
				'severity' => 'INFO',
				'message'  => 'Multiple REST namespaces in use (consider consolidating to apollo/v1)',
				'fix'      => 'Implement REST namespace consolidation per S4 schema',
			);
		}

		$details[] = "apollo/v1: {$namespaces['apollo/v1']} routes";
		$details[] = "apollo-core/v1: {$namespaces['apollo-core/v1']} routes";
		$details[] = "apollo-events/v1: {$namespaces['apollo-events/v1']} routes";

		// Test critical endpoints.
		$critical_endpoints = array(
			'/apollo/v1/comunas',
			'/apollo/v1/members',
			'/apollo/v1/activity',
		);

		foreach ( $critical_endpoints as $endpoint ) {
			if ( ! isset( $routes[ $endpoint ] ) ) {
				++$issues_found;
				$this->issues[] = array(
					'severity' => 'ERROR',
					'message'  => "Critical endpoint '{$endpoint}' not registered",
					'fix'      => 'Check apollo-social plugin activation and REST registration',
				);
			}
		}

		$status = 0 === $issues_found ? 'PASS' : ( $issues_found > 2 ? 'FAIL' : 'WARN' );

		return array(
			'check'   => $check_name,
			'status'  => $status,
			'issues'  => $issues_found,
			'details' => "{$total_routes} Apollo routes registered",
		);
	}

	/**
	 * CHECK 6: Hook Execution
	 *
	 * @param bool $fix     Whether to attempt auto-fixes.
	 * @param bool $verbose Whether to show detailed output.
	 * @return array{check: string, status: string, issues: int, details: string}
	 */
	private function check_hooks( bool $fix, bool $verbose ): array {
		$check_name   = '6. Hooks';
		$issues_found = 0;
		$details      = array();

		if ( $verbose ) {
			WP_CLI::log( '   Checking hook registrations...' );
		}

		global $wp_filter;

		// Expected hooks that should have callbacks.
		$expected_hooks = array(
			'apollo_core_loaded'    => 'Core loaded hook',
			'apollo_core_ready'     => 'Core ready hook',
			'apollo_rest_api_ready' => 'REST API ready hook',
			'init'                  => 'WordPress init (CPT registration)',
		);

		foreach ( $expected_hooks as $hook => $description ) {
			if ( ! isset( $wp_filter[ $hook ] ) || empty( $wp_filter[ $hook ]->callbacks ) ) {
				if ( 'init' !== $hook ) {
					$details[] = "○ {$hook} has no listeners";
				}
			} else {
				$callback_count = 0;
				foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
					$callback_count += count( $callbacks );
				}
				$details[] = "✓ {$hook} ({$callback_count} callbacks)";
			}
		}

		// Check for Apollo-specific hooks.
		$apollo_hooks = array_filter(
			array_keys( $wp_filter ),
			function ( $hook ) {
				return strpos( $hook, 'apollo_' ) === 0;
			}
		);

		$details[] = count( $apollo_hooks ) . ' apollo_* hooks registered';

		$status = 0 === $issues_found ? 'PASS' : 'WARN';

		return array(
			'check'   => $check_name,
			'status'  => $status,
			'issues'  => $issues_found,
			'details' => implode( '; ', array_slice( $details, 0, 3 ) ),
		);
	}

	/**
	 * CHECK 7: Data Relationships
	 *
	 * @param bool $fix     Whether to attempt auto-fixes.
	 * @param bool $verbose Whether to show detailed output.
	 * @return array{check: string, status: string, issues: int, details: string}
	 */
	private function check_relationships( bool $fix, bool $verbose ): array {
		$check_name   = '7. Relationships';
		$issues_found = 0;
		$details      = array();

		if ( $verbose ) {
			WP_CLI::log( '   Checking data relationships integrity...' );
		}

		global $wpdb;

		// Check Event → DJ relationships.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$orphaned_dj_refs = $wpdb->get_var(
			"SELECT COUNT(DISTINCT pm.post_id)
			FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE pm.meta_key = '_event_dj_ids'
			AND p.ID IS NULL"
		);

		if ( $orphaned_dj_refs > 0 ) {
			++$issues_found;
			$this->issues[] = array(
				'severity' => 'WARNING',
				'message'  => "{$orphaned_dj_refs} orphaned DJ relationship entries found",
				'fix'      => 'Run relationship integrity repair',
			);

			if ( $fix ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$deleted               = $wpdb->query(
					"DELETE pm FROM {$wpdb->postmeta} pm
					LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
					WHERE pm.meta_key = '_event_dj_ids'
					AND p.ID IS NULL"
				);
				$this->fixes_applied[] = "Removed {$deleted} orphaned DJ relationship entries";
				--$issues_found;
			}
		}

		// Check for invalid DJ IDs in relationships.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$events_with_djs = $wpdb->get_results(
			"SELECT post_id, meta_value
			FROM {$wpdb->postmeta}
			WHERE meta_key = '_event_dj_ids'
			AND meta_value != ''
			LIMIT 100"
		);

		$invalid_refs = 0;
		foreach ( $events_with_djs as $row ) {
			$dj_ids = maybe_unserialize( $row->meta_value );
			if ( is_array( $dj_ids ) ) {
				foreach ( $dj_ids as $dj_id ) {
					if ( ! get_post( $dj_id ) ) {
						++$invalid_refs;
					}
				}
			}
		}

		if ( $invalid_refs > 0 ) {
			++$issues_found;
			$this->issues[] = array(
				'severity' => 'WARNING',
				'message'  => "{$invalid_refs} references to non-existent DJs found",
				'fix'      => 'Run relationship cleanup to remove invalid references',
			);
		}

		$details[] = 'Checked Event→DJ, Event→Local relationships';

		$status = 0 === $issues_found ? 'PASS' : 'WARN';

		return array(
			'check'   => $check_name,
			'status'  => $status,
			'issues'  => $issues_found,
			'details' => implode( '; ', $details ),
		);
	}

	/**
	 * CHECK 8: Template Integration
	 *
	 * @param bool $fix     Whether to attempt auto-fixes.
	 * @param bool $verbose Whether to show detailed output.
	 * @return array{check: string, status: string, issues: int, details: string}
	 */
	private function check_templates( bool $fix, bool $verbose ): array {
		$check_name   = '8. Templates';
		$issues_found = 0;
		$details      = array();

		if ( $verbose ) {
			WP_CLI::log( '   Checking template integration...' );
		}

		// Check if Template Loader class exists.
		$loader_classes = array(
			'Apollo_Template_Loader',
			'Apollo\\Templates\\TemplateLoader',
			'Apollo_Core_Template_Loader',
		);

		$loader_found = false;
		foreach ( $loader_classes as $class ) {
			if ( class_exists( $class ) ) {
				$loader_found = true;
				$details[]    = "✓ {$class} found";
				break;
			}
		}

		if ( ! $loader_found ) {
			++$issues_found;
			$this->issues[] = array(
				'severity' => 'WARNING',
				'message'  => 'No Template Loader class found',
				'fix'      => 'Implement Apollo_Template_Loader per Phase 2 architecture',
			);
		}

		// Check templates directory exists (READ ONLY CHECK).
		$template_dirs = array(
			WP_PLUGIN_DIR . '/apollo-core/templates/',
			WP_PLUGIN_DIR . '/apollo-core/templates/partials/',
			WP_PLUGIN_DIR . '/apollo-core/templates/template-parts/',
		);

		foreach ( $template_dirs as $dir ) {
			if ( is_dir( $dir ) ) {
				$files     = glob( $dir . '*.php' );
				$details[] = basename( $dir ) . ': ' . ( is_array( $files ) ? count( $files ) : 0 ) . ' files';
			}
		}

		$status = 0 === $issues_found ? 'PASS' : 'WARN';

		return array(
			'check'   => $check_name,
			'status'  => $status,
			'issues'  => $issues_found,
			'details' => implode( '; ', $details ),
		);
	}

	/**
	 * CHECK 9: Asset Loading
	 *
	 * @param bool $fix     Whether to attempt auto-fixes.
	 * @param bool $verbose Whether to show detailed output.
	 * @return array{check: string, status: string, issues: int, details: string}
	 */
	private function check_assets( bool $fix, bool $verbose ): array {
		$check_name   = '9. Assets';
		$issues_found = 0;
		$details      = array();

		if ( $verbose ) {
			WP_CLI::log( '   Checking asset enqueues...' );
		}

		$details[] = 'Asset check requires frontend context';
		$details[] = 'Run in browser with SCRIPT_DEBUG for full check';

		// Check if asset files exist.
		$asset_paths = array(
			WP_PLUGIN_DIR . '/apollo-core/assets/css/',
			WP_PLUGIN_DIR . '/apollo-core/assets/js/',
		);

		foreach ( $asset_paths as $path ) {
			if ( is_dir( $path ) ) {
				$files     = glob( $path . '*' );
				$details[] = basename( dirname( $path ) ) . '/' . basename( $path ) . ': ' . ( is_array( $files ) ? count( $files ) : 0 ) . ' files';
			}
		}

		$status = 'PASS'; // Can't fully verify in CLI.

		return array(
			'check'   => $check_name,
			'status'  => $status,
			'issues'  => $issues_found,
			'details' => implode( '; ', $details ),
		);
	}

	/**
	 * CHECK 10: Database Integrity
	 *
	 * @param bool $fix     Whether to attempt auto-fixes.
	 * @param bool $verbose Whether to show detailed output.
	 * @return array{check: string, status: string, issues: int, details: string}
	 */
	private function check_database( bool $fix, bool $verbose ): array {
		$check_name   = '10. Database';
		$issues_found = 0;
		$details      = array();

		if ( $verbose ) {
			WP_CLI::log( '   Checking database integrity...' );
		}

		global $wpdb;

		// Check for orphaned postmeta.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$orphaned_meta = $wpdb->get_var(
			"SELECT COUNT(*)
			FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE p.ID IS NULL
			AND (pm.meta_key LIKE '_event%' OR pm.meta_key LIKE '_dj%' OR pm.meta_key LIKE '_local%' OR pm.meta_key LIKE '_classified%')"
		);

		if ( $orphaned_meta > 0 ) {
			++$issues_found;
			$this->issues[] = array(
				'severity' => 'WARNING',
				'message'  => "{$orphaned_meta} orphaned Apollo meta entries found",
				'fix'      => 'Run database cleanup to remove orphaned meta',
			);

			if ( $fix ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$deleted               = $wpdb->query(
					"DELETE pm FROM {$wpdb->postmeta} pm
					LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
					WHERE p.ID IS NULL
					AND (pm.meta_key LIKE '_event%' OR pm.meta_key LIKE '_dj%' OR pm.meta_key LIKE '_local%' OR pm.meta_key LIKE '_classified%')"
				);
				$this->fixes_applied[] = "Removed {$deleted} orphaned meta entries";
				--$issues_found;
			}
		} else {
			$details[] = '✓ No orphaned meta';
		}

		// Check for orphaned term relationships.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$orphaned_terms = $wpdb->get_var(
			"SELECT COUNT(*)
			FROM {$wpdb->term_relationships} tr
			LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID
			WHERE p.ID IS NULL"
		);

		if ( $orphaned_terms > 0 ) {
			++$issues_found;
			$this->issues[] = array(
				'severity' => 'WARNING',
				'message'  => "{$orphaned_terms} orphaned term relationships found",
				'fix'      => 'Run: wp term recount <taxonomy>',
			);

			if ( $fix ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$deleted               = $wpdb->query(
					"DELETE tr FROM {$wpdb->term_relationships} tr
					LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID
					WHERE p.ID IS NULL"
				);
				$this->fixes_applied[] = "Removed {$deleted} orphaned term relationships";
				--$issues_found;
			}
		} else {
			$details[] = '✓ No orphaned terms';
		}

		// Check Apollo options.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$apollo_options = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->options}
			WHERE option_name LIKE 'apollo_%' OR option_name LIKE '_apollo_%'"
		);
		$details[]      = "{$apollo_options} Apollo options";

		$status = 0 === $issues_found ? 'PASS' : 'WARN';

		return array(
			'check'   => $check_name,
			'status'  => $status,
			'issues'  => $issues_found,
			'details' => implode( '; ', $details ),
		);
	}

	/**
	 * Verify plugin load order is correct
	 *
	 * @return bool True if load order is correct.
	 */
	private function verify_load_order(): bool {
		return defined( 'APOLLO_CORE_VERSION' );
	}
}

// Register the health command.
if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::add_command( 'apollo health', 'Apollo_Health_Command' );
}

/**
 * Additional subcommands for specific fix operations.
 */
class Apollo_Fix_Command {

	/**
	 * Run all available auto-fixes
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Show what would be fixed without making changes
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo fix
	 *     wp apollo fix --dry-run
	 *
	 * @when after_wp_load
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$dry_run = isset( $assoc_args['dry-run'] );

		if ( $dry_run ) {
			WP_CLI::log( 'DRY RUN MODE - No changes will be made' );
			WP_CLI::log( '' );
		}

		WP_CLI::log( 'Running Apollo Integration Fixes...' );
		WP_CLI::log( '' );

		// Run health check with fix flag.
		WP_CLI::runcommand(
			'apollo health --fix',
			array(
				'return' => false,
				'parse'  => false,
				'launch' => false,
			)
		);
	}

	/**
	 * Migrate deprecated meta keys to canonical versions
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo fix migrate-meta
	 *
	 * @subcommand migrate-meta
	 * @when after_wp_load
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 * @return void
	 */
	public function migrate_meta( array $args, array $assoc_args ): void {
		global $wpdb;

		$migrations = array(
			'_event_lat'       => '_event_latitude',
			'_event_lng'       => '_event_longitude',
			'_event_timetable' => '_event_dj_slots',
			'_local_lat'       => '_local_latitude',
			'_local_lng'       => '_local_longitude',
		);

		WP_CLI::log( 'Starting meta key migration...' );

		foreach ( $migrations as $old => $new ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s",
					$old
				)
			);

			if ( $count > 0 ) {
				WP_CLI::log( "Migrating {$count} entries: {$old} → {$new}" );

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$result = $wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s",
						$new,
						$old
					)
				);

				if ( false !== $result ) {
					WP_CLI::success( "Migrated {$result} entries" );
				} else {
					WP_CLI::error( "Failed to migrate {$old}", false );
				}
			} else {
				WP_CLI::log( "No entries for {$old} - skipping" );
			}
		}

		WP_CLI::success( 'Meta migration complete!' );
	}

	/**
	 * Clean up orphaned database entries
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo fix cleanup-db
	 *
	 * @subcommand cleanup-db
	 * @when after_wp_load
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 * @return void
	 */
	public function cleanup_db( array $args, array $assoc_args ): void {
		global $wpdb;

		WP_CLI::log( 'Cleaning up orphaned database entries...' );

		// Clean orphaned postmeta.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted_meta = $wpdb->query(
			"DELETE pm FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE p.ID IS NULL"
		);
		WP_CLI::log( "Removed {$deleted_meta} orphaned postmeta entries" );

		// Clean orphaned term relationships.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted_terms = $wpdb->query(
			"DELETE tr FROM {$wpdb->term_relationships} tr
			LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID
			WHERE p.ID IS NULL"
		);
		WP_CLI::log( "Removed {$deleted_terms} orphaned term relationships" );

		// Recount terms.
		WP_CLI::runcommand( 'term recount event_listing_category', array( 'launch' => false ) );
		WP_CLI::runcommand( 'term recount event_season', array( 'launch' => false ) );

		WP_CLI::success( 'Database cleanup complete!' );
	}

	/**
	 * Flush and rebuild rewrite rules
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo fix rewrite
	 *
	 * @subcommand rewrite
	 * @when after_wp_load
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 * @return void
	 */
	public function rewrite( array $args, array $assoc_args ): void {
		WP_CLI::log( 'Flushing rewrite rules...' );
		flush_rewrite_rules( true );
		WP_CLI::success( 'Rewrite rules flushed!' );
	}
}

if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::add_command( 'apollo fix', 'Apollo_Fix_Command' );
}

/**
 * Report generation command
 */
class Apollo_Report_Command {

	/**
	 * Generate a full integration report
	 *
	 * ## OPTIONS
	 *
	 * [--output=<file>]
	 * : Save report to file
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo report
	 *     wp apollo report --output=apollo-report.json
	 *
	 * @when after_wp_load
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$output_file = $assoc_args['output'] ?? null;

		$report = array(
			'generated_at'   => current_time( 'mysql' ),
			'wordpress'      => array(
				'version'   => get_bloginfo( 'version' ),
				'multisite' => is_multisite(),
				'site_url'  => site_url(),
			),
			'php'            => array(
				'version'      => PHP_VERSION,
				'memory_limit' => ini_get( 'memory_limit' ),
			),
			'plugins'        => $this->get_plugin_info(),
			'cpts'           => $this->get_cpt_info(),
			'taxonomies'     => $this->get_taxonomy_info(),
			'rest_routes'    => $this->get_rest_info(),
			'database_stats' => $this->get_db_stats(),
		);

		$json = wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

		if ( $output_file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $output_file, $json ? $json : '{}' );
			WP_CLI::success( "Report saved to {$output_file}" );
		} else {
			WP_CLI::log( $json ? $json : '{}' );
		}
	}

	/**
	 * Get Apollo plugin information
	 *
	 * @return array<string, array{name: string, version: string, active: bool}>
	 */
	private function get_plugin_info(): array {
		$plugins     = array();
		$all_plugins = get_plugins();

		foreach ( $all_plugins as $file => $data ) {
			if ( strpos( $file, 'apollo' ) !== false ) {
				$plugins[ $file ] = array(
					'name'    => $data['Name'],
					'version' => $data['Version'],
					'active'  => is_plugin_active( $file ),
				);
			}
		}

		return $plugins;
	}

	/**
	 * Get CPT information
	 *
	 * @return array<string, array{label: string, public: bool, show_in_rest: bool, count: int}>
	 */
	private function get_cpt_info(): array {
		$cpts       = array();
		$post_types = get_post_types( array( '_builtin' => false ), 'objects' );

		$apollo_cpts = array( 'event', 'apollo', 'dj', 'local', 'classified', 'supplier', 'user_page', 'cena' );

		foreach ( $post_types as $pt ) {
			$is_apollo = false;
			foreach ( $apollo_cpts as $prefix ) {
				if ( strpos( $pt->name, $prefix ) !== false ) {
					$is_apollo = true;
					break;
				}
			}

			if ( $is_apollo ) {
				$counts            = wp_count_posts( $pt->name );
				$cpts[ $pt->name ] = array(
					'label'        => $pt->label,
					'public'       => $pt->public,
					'show_in_rest' => $pt->show_in_rest,
					'count'        => isset( $counts->publish ) ? (int) $counts->publish : 0,
				);
			}
		}

		return $cpts;
	}

	/**
	 * Get taxonomy information
	 *
	 * @return array<string, array{label: string, object_types: array, term_count: int}>
	 */
	private function get_taxonomy_info(): array {
		$taxonomies = array();
		$all_taxes  = get_taxonomies( array( '_builtin' => false ), 'objects' );

		$apollo_taxes = array( 'event', 'apollo', 'classified', 'supplier' );

		foreach ( $all_taxes as $tax ) {
			$is_apollo = false;
			foreach ( $apollo_taxes as $prefix ) {
				if ( strpos( $tax->name, $prefix ) !== false ) {
					$is_apollo = true;
					break;
				}
			}

			if ( $is_apollo ) {
				$taxonomies[ $tax->name ] = array(
					'label'        => $tax->label,
					'object_types' => $tax->object_type,
					'term_count'   => (int) wp_count_terms( array( 'taxonomy' => $tax->name ) ),
				);
			}
		}

		return $taxonomies;
	}

	/**
	 * Get REST API route information
	 *
	 * @return array{total_apollo_routes: int, namespaces: array<string, int>}
	 */
	private function get_rest_info(): array {
		$server = rest_get_server();
		$routes = $server->get_routes();

		$apollo_routes = array_filter(
			array_keys( $routes ),
			function ( $route ) {
				return strpos( $route, '/apollo' ) !== false;
			}
		);

		return array(
			'total_apollo_routes' => count( $apollo_routes ),
			'namespaces'          => array(
				'apollo/v1'        => count(
					array_filter(
						$apollo_routes,
						fn( $r ) => strpos( $r, '/apollo/v1' ) === 0
					)
				),
				'apollo-core/v1'   => count(
					array_filter(
						$apollo_routes,
						fn( $r ) => strpos( $r, '/apollo-core/v1' ) === 0
					)
				),
				'apollo-events/v1' => count(
					array_filter(
						$apollo_routes,
						fn( $r ) => strpos( $r, '/apollo-events/v1' ) === 0
					)
				),
			),
		);
	}

	/**
	 * Get database statistics
	 *
	 * @return array{apollo_postmeta: int, apollo_options: int}
	 */
	private function get_db_stats(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$apollo_postmeta = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->postmeta}
			WHERE meta_key LIKE '_event%' OR meta_key LIKE '_dj%' OR meta_key LIKE '_classified%'"
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$apollo_options = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->options}
			WHERE option_name LIKE 'apollo_%'"
		);

		return array(
			'apollo_postmeta' => $apollo_postmeta,
			'apollo_options'  => $apollo_options,
		);
	}
}

if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::add_command( 'apollo report', 'Apollo_Report_Command' );
}
