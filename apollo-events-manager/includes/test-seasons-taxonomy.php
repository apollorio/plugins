<?php
/**
 * Smoke Test for Event Seasons Taxonomy
 *
 * Tests the implementation of event_season taxonomy across:
 * - apollo-events-manager (event_listing CPT)
 * - apollo-social (apollo_classified CPT)
 *
 * @package Apollo
 * @since 2025-12-31
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access not permitted.' );
}

/**
 * Apollo Seasons Smoke Test
 */
class Apollo_Seasons_Smoke_Test {

	/**
	 * Run all tests
	 *
	 * @return array Test results
	 */
	public static function run(): array {
		$results = array();

		$results['taxonomy_registered']     = self::test_taxonomy_registered();
		$results['default_terms_created']   = self::test_default_terms();
		$results['event_listing_connected'] = self::test_event_listing_connection();
		$results['classifieds_connected']   = self::test_classifieds_connection();
		$results['meta_fields_registered']  = self::test_meta_fields();
		$results['rest_api_available']      = self::test_rest_api();
		$results['yoda_compliance']         = self::test_yoda_conditions();
		$results['namespace_check']         = self::test_namespaces();

		return $results;
	}

	/**
	 * Test if taxonomy is registered
	 *
	 * @return array
	 */
	private static function test_taxonomy_registered(): array {
		$exists = taxonomy_exists( 'event_season' );

		if ( ! $exists ) {
			return array(
				'status'  => 'FAIL',
				'message' => 'Taxonomy event_season not registered',
			);
		}

		$tax_obj = get_taxonomy( 'event_season' );

		return array(
			'status'  => 'PASS',
			'message' => 'Taxonomy registered successfully',
			'data'    => array(
				'slug'         => $tax_obj->name,
				'label'        => $tax_obj->label,
				'hierarchical' => $tax_obj->hierarchical,
				'show_in_rest' => $tax_obj->show_in_rest,
				'rest_base'    => $tax_obj->rest_base,
			),
		);
	}

	/**
	 * Test if default terms were created
	 *
	 * @return array
	 */
	private static function test_default_terms(): array {
		$expected_terms = array( 'verao-26', 'carnaval-26', 'rir-26', 'bey-26' );
		$found_terms    = array();
		$missing_terms  = array();

		foreach ( $expected_terms as $slug ) {
			$term = get_term_by( 'slug', $slug, 'event_season' );

			if ( $term && ! is_wp_error( $term ) ) {
				$found_terms[] = $term->name;
			} else {
				$missing_terms[] = $slug;
			}
		}

		if ( ! empty( $missing_terms ) ) {
			return array(
				'status'  => 'FAIL',
				'message' => 'Missing default terms: ' . implode( ', ', $missing_terms ),
				'data'    => array(
					'found'   => $found_terms,
					'missing' => $missing_terms,
				),
			);
		}

		return array(
			'status'  => 'PASS',
			'message' => 'All default terms created',
			'data'    => $found_terms,
		);
	}

	/**
	 * Test event_listing connection
	 *
	 * @return array
	 */
	private static function test_event_listing_connection(): array {
		if ( ! post_type_exists( 'event_listing' ) ) {
			return array(
				'status'  => 'SKIP',
				'message' => 'event_listing CPT not registered',
			);
		}

		$tax_obj = get_taxonomy( 'event_season' );

		if ( ! in_array( 'event_listing', $tax_obj->object_type, true ) ) {
			return array(
				'status'  => 'FAIL',
				'message' => 'event_season not connected to event_listing',
				'data'    => $tax_obj->object_type,
			);
		}

		return array(
			'status'  => 'PASS',
			'message' => 'event_season connected to event_listing',
		);
	}

	/**
	 * Test classifieds connection
	 *
	 * @return array
	 */
	private static function test_classifieds_connection(): array {
		if ( ! post_type_exists( 'apollo_classified' ) ) {
			return array(
				'status'  => 'SKIP',
				'message' => 'apollo_classified CPT not registered (apollo-social not active)',
			);
		}

		$tax_obj = get_taxonomy( 'event_season' );

		if ( ! in_array( 'apollo_classified', $tax_obj->object_type, true ) ) {
			return array(
				'status'  => 'FAIL',
				'message' => 'event_season not connected to apollo_classified',
				'data'    => $tax_obj->object_type,
			);
		}

		return array(
			'status'  => 'PASS',
			'message' => 'event_season connected to apollo_classified',
		);
	}

	/**
	 * Test meta fields registration
	 *
	 * @return array
	 */
	private static function test_meta_fields(): array {
		$results = array();

		// Test event_listing meta
		if ( post_type_exists( 'event_listing' ) ) {
			$event_meta = get_registered_meta_keys( 'post', 'event_listing' );

			if ( isset( $event_meta['_event_season_id'] ) ) {
				$results['event_listing'] = 'PASS';
			} else {
				$results['event_listing'] = 'FAIL - _event_season_id not registered';
			}
		}

		// Test classified meta
		if ( post_type_exists( 'apollo_classified' ) ) {
			$classified_meta = get_registered_meta_keys( 'post', 'apollo_classified' );

			if ( isset( $classified_meta['_classified_season_id'] ) ) {
				$results['apollo_classified'] = 'PASS';
			} else {
				$results['apollo_classified'] = 'FAIL - _classified_season_id not registered';
			}
		}

		$has_failures = in_array(
			true,
			array_map(
				function ( $r ) {
					return false !== strpos( $r, 'FAIL' );
				},
				$results
			),
			true
		);

		return array(
			'status'  => $has_failures ? 'FAIL' : 'PASS',
			'message' => 'Meta fields check',
			'data'    => $results,
		);
	}

	/**
	 * Test REST API availability
	 *
	 * @return array
	 */
	private static function test_rest_api(): array {
		$tax_obj = get_taxonomy( 'event_season' );

		if ( ! $tax_obj || ! $tax_obj->show_in_rest ) {
			return array(
				'status'  => 'FAIL',
				'message' => 'Taxonomy not exposed in REST API',
			);
		}

		return array(
			'status'  => 'PASS',
			'message' => 'REST API enabled',
			'data'    => array(
				'rest_base'       => $tax_obj->rest_base,
				'rest_controller' => $tax_obj->rest_controller_class,
			),
		);
	}

	/**
	 * Test YODA conditions compliance
	 *
	 * @return array
	 */
	private static function test_yoda_conditions(): array {
		$files_to_check = array(
			APOLLO_APRIO_PATH . 'includes/post-types.php',
			WP_PLUGIN_DIR . '/apollo-social/src/Modules/Classifieds/ClassifiedsModule.php',
		);

		$violations = array();

		foreach ( $files_to_check as $file ) {
			if ( ! file_exists( $file ) ) {
				continue;
			}

			$content = file_get_contents( $file );

			// Check for non-YODA conditions (simple patterns)
			$patterns = array(
				'/\$[a-z_]+ === [A-Z_]+/',  // Variable on left
				'/\$[a-z_]+ !== [A-Z_]+/',
			);

			foreach ( $patterns as $pattern ) {
				if ( preg_match( $pattern, $content ) ) {
					$violations[] = basename( $file ) . ' - potential non-YODA condition found';
				}
			}
		}

		if ( ! empty( $violations ) ) {
			return array(
				'status'  => 'WARNING',
				'message' => 'Potential YODA violations detected',
				'data'    => $violations,
			);
		}

		return array(
			'status'  => 'PASS',
			'message' => 'YODA conditions compliance check passed',
		);
	}

	/**
	 * Test namespace usage
	 *
	 * @return array
	 */
	private static function test_namespaces(): array {
		$errors = array();

		// Check if ClassifiedsModule uses proper namespace
		if ( class_exists( '\Apollo\Modules\Classifieds\ClassifiedsModule' ) ) {
			// Old namespace
			$errors[] = 'Old namespace detected: \Apollo\Modules\Classifieds\ClassifiedsModule';
		}

		if ( ! class_exists( '\Apollo\Social\Modules\Classifieds\ClassifiedsModule' ) && post_type_exists( 'apollo_classified' ) ) {
			$errors[] = 'Expected namespace not found: \Apollo\Social\Modules\Classifieds\ClassifiedsModule';
		}

		if ( ! empty( $errors ) ) {
			return array(
				'status'  => 'FAIL',
				'message' => 'Namespace issues detected',
				'data'    => $errors,
			);
		}

		return array(
			'status'  => 'PASS',
			'message' => 'Namespace check passed',
		);
	}

	/**
	 * Output results as HTML
	 *
	 * @param array $results Test results.
	 * @return void
	 */
	public static function output_html( array $results ): void {
		?>
		<div class="wrap">
			<h1>🧪 Apollo Seasons Taxonomy - Smoke Test Results</h1>
			<p><strong>Date:</strong> <?php echo esc_html( current_time( 'Y-m-d H:i:s' ) ); ?></p>

			<table class="widefat striped">
				<thead>
					<tr>
						<th>Test</th>
						<th>Status</th>
						<th>Message</th>
						<th>Data</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $results as $test_name => $result ) : ?>
						<tr>
							<td><strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $test_name ) ) ); ?></strong></td>
							<td>
								<span style="color: 
								<?php
								echo 'PASS' === $result['status'] ? 'green' : ( 'FAIL' === $result['status'] ? 'red' : 'orange' );
								?>
								">
									<?php echo esc_html( $result['status'] ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $result['message'] ); ?></td>
							<td>
								<?php
								if ( isset( $result['data'] ) ) {
									echo '<pre style="font-size: 11px; max-height: 200px; overflow: auto;">';
									echo esc_html( print_r( $result['data'], true ) );
									echo '</pre>';
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<h2>Summary</h2>
			<ul>
				<?php
				$passed  = count(
					array_filter(
						$results,
						function ( $r ) {
							return 'PASS' === $r['status'];
						}
					)
				);
				$failed  = count(
					array_filter(
						$results,
						function ( $r ) {
							return 'FAIL' === $r['status'];
						}
					)
				);
				$skipped = count(
					array_filter(
						$results,
						function ( $r ) {
							return 'SKIP' === $r['status'];
						}
					)
				);
				?>
				<li><strong>Passed:</strong> <?php echo esc_html( $passed ); ?></li>
				<li><strong>Failed:</strong> <?php echo esc_html( $failed ); ?></li>
				<li><strong>Skipped:</strong> <?php echo esc_html( $skipped ); ?></li>
			</ul>
		</div>
		<?php
	}
}

// Add admin menu for running tests
add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'tools.php',
			'Apollo Seasons Test',
			'Apollo Seasons Test',
			'manage_options',
			'apollo-seasons-test',
			function () {
				$results = Apollo_Seasons_Smoke_Test::run();
				Apollo_Seasons_Smoke_Test::output_html( $results );
			}
		);
	}
);
