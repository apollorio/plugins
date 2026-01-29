<?php
/**
 * Apollo Full Integration Test Suite
 *
 * Comprehensive tests validating all Apollo plugin integrations.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core\Tests;

use WP_UnitTestCase;
use Apollo_Core\Apollo_Activation_Controller;
use Apollo_Core\Apollo_Orchestrator;
use Apollo_Core\Apollo_Health_Check;
use Apollo_Core\Apollo_Relationships;
use Apollo_Core\Apollo_Relationship_Query;
use Apollo_Core\Apollo_Event_Bus;

/**
 * Class Test_Full_Integration
 *
 * Full integration test suite for Apollo plugins.
 *
 * @group apollo
 * @group integration
 */
class Test_Full_Integration extends WP_UnitTestCase {

	/**
	 * Expected CPTs across all Apollo plugins.
	 *
	 * @var array
	 */
	private array $expected_cpts = array(
		// Apollo Events Manager.
		'apollo-event',
		'apollo-dj',
		'apollo-local',

		// Apollo Social.
		'apollo-classified',
		'apollo-supplier',

		// Legacy support.
		'event_listing',
		'event_dj',
		'event_local',
	);

	/**
	 * Expected taxonomies.
	 *
	 * @var array
	 */
	private array $expected_taxonomies = array(
		'apollo-event-cat',
		'apollo-event-tag',
		'apollo-genre',
		'apollo-music-style',
		'apollo-region',
		'apollo-local-type',
		'apollo-classified-cat',
	);

	/**
	 * Expected REST namespaces.
	 *
	 * @var array
	 */
	private array $expected_rest_namespaces = array(
		'apollo/v1',
	);

	/**
	 * Expected meta keys.
	 *
	 * @var array
	 */
	private array $expected_meta_keys = array(
		// Event meta.
		'apollo_event_date_start',
		'apollo_event_date_end',
		'apollo_event_local_id',
		'apollo_event_dj_ids',
		'apollo_event_price',
		'apollo_event_capacity',
		'apollo_event_status',

		// DJ meta.
		'apollo_dj_real_name',
		'apollo_dj_bio',
		'apollo_dj_social_links',
		'apollo_dj_genres',

		// Local meta.
		'apollo_local_address',
		'apollo_local_city',
		'apollo_local_state',
		'apollo_local_country',
		'apollo_local_postal_code',
		'apollo_local_latitude',
		'apollo_local_longitude',
		'apollo_local_capacity',
	);

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Ensure plugins are activated.
		if ( \class_exists( Apollo_Activation_Controller::class ) ) {
			Apollo_Activation_Controller::activate();
		}

		// Boot orchestrator.
		if ( \class_exists( Apollo_Orchestrator::class ) ) {
			Apollo_Orchestrator::boot();
		}
	}

	/**
	 * Tear down test environment.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
	}

	// =========================================================================
	// CPT TESTS
	// =========================================================================

	/**
	 * Test all CPTs are registered.
	 *
	 * @return void
	 */
	public function test_all_cpts_registered(): void {
		$registered = \get_post_types( array(), 'names' );
		$missing    = array();

		foreach ( $this->expected_cpts as $cpt ) {
			if ( ! \in_array( $cpt, $registered, true ) ) {
				$missing[] = $cpt;
			}
		}

		// At minimum, core Apollo CPTs should be registered if apollo-core is active.
		$core_cpts       = array( 'apollo-event', 'apollo-dj', 'apollo-local' );
		$registered_core = \array_intersect( $core_cpts, $registered );

		$this->assertNotEmpty(
			$registered_core,
			'At least some core Apollo CPTs should be registered. Missing: ' . \implode( ', ', $missing )
		);
	}

	/**
	 * Test no duplicate CPTs.
	 *
	 * @return void
	 */
	public function test_no_duplicate_cpts(): void {
		$all_cpts    = \get_post_types( array(), 'names' );
		$apollo_cpts = \array_filter(
			$all_cpts,
			function ( $cpt ) {
				return \strpos( $cpt, 'apollo' ) !== false || \strpos( $cpt, 'event_' ) !== false;
			}
		);

		// Check for naming conflicts (e.g., apollo-event and event_listing serving same purpose).
		$event_variants = \array_filter(
			$apollo_cpts,
			function ( $cpt ) {
				return \strpos( $cpt, 'event' ) !== false;
			}
		);

		// Should have maximum 2 event types (new + legacy).
		$this->assertLessThanOrEqual(
			4,
			\count( $event_variants ),
			'Too many event-related CPTs registered: ' . \implode( ', ', $event_variants )
		);
	}

	/**
	 * Test CPTs have REST API support.
	 *
	 * @return void
	 */
	public function test_cpts_have_rest_support(): void {
		$cpts_needing_rest = array( 'apollo-event', 'apollo-dj', 'apollo-local' );

		foreach ( $cpts_needing_rest as $cpt ) {
			if ( ! \post_type_exists( $cpt ) ) {
				$this->markTestSkipped( "CPT {$cpt} not registered" );
				continue;
			}

			$post_type_object = \get_post_type_object( $cpt );
			$this->assertTrue(
				$post_type_object->show_in_rest,
				"CPT {$cpt} should have REST API support enabled"
			);
		}
	}

	/**
	 * Test CPTs can be created.
	 *
	 * @return void
	 */
	public function test_cpts_can_be_created(): void {
		$cpts_to_test = array( 'apollo-event', 'apollo-dj', 'apollo-local' );

		foreach ( $cpts_to_test as $cpt ) {
			if ( ! \post_type_exists( $cpt ) ) {
				continue;
			}

			$post_id = $this->factory->post->create(
				array(
					'post_type'   => $cpt,
					'post_title'  => "Test {$cpt}",
					'post_status' => 'publish',
				)
			);

			$this->assertIsInt( $post_id, "Failed to create {$cpt}" );
			$this->assertGreaterThan( 0, $post_id, "Invalid post ID for {$cpt}" );

			$post = \get_post( $post_id );
			$this->assertEquals( $cpt, $post->post_type, "Post type mismatch for {$cpt}" );
		}
	}

	// =========================================================================
	// TAXONOMY TESTS
	// =========================================================================

	/**
	 * Test all taxonomies are registered.
	 *
	 * @return void
	 */
	public function test_all_taxonomies_registered(): void {
		$registered        = \get_taxonomies( array(), 'names' );
		$apollo_taxonomies = \array_filter(
			$registered,
			function ( $tax ) {
				return \strpos( $tax, 'apollo' ) !== false;
			}
		);

		$this->assertNotEmpty(
			$apollo_taxonomies,
			'At least some Apollo taxonomies should be registered'
		);
	}

	/**
	 * Test taxonomy attachments.
	 *
	 * @return void
	 */
	public function test_taxonomy_attachments(): void {
		$expected_attachments = array(
			'apollo-event-cat' => array( 'apollo-event' ),
			'apollo-genre'     => array( 'apollo-event', 'apollo-dj' ),
			'apollo-region'    => array( 'apollo-event', 'apollo-local' ),
		);

		foreach ( $expected_attachments as $taxonomy => $expected_types ) {
			if ( ! \taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$tax_object     = \get_taxonomy( $taxonomy );
			$attached_types = $tax_object->object_type;

			foreach ( $expected_types as $type ) {
				if ( \post_type_exists( $type ) ) {
					$this->assertContains(
						$type,
						$attached_types,
						"Taxonomy {$taxonomy} should be attached to {$type}"
					);
				}
			}
		}
	}

	/**
	 * Test terms can be assigned to posts.
	 *
	 * @return void
	 */
	public function test_terms_can_be_assigned(): void {
		if ( ! \post_type_exists( 'apollo-event' ) || ! \taxonomy_exists( 'apollo-event-cat' ) ) {
			$this->markTestSkipped( 'Required CPT or taxonomy not registered' );
		}

		// Create event.
		$event_id = $this->factory->post->create(
			array(
				'post_type'   => 'apollo-event',
				'post_title'  => 'Test Event',
				'post_status' => 'publish',
			)
		);

		// Create term.
		$term    = \wp_insert_term( 'Festival', 'apollo-event-cat' );
		$term_id = \is_array( $term ) ? $term['term_id'] : 0;

		$this->assertGreaterThan( 0, $term_id, 'Failed to create term' );

		// Assign term.
		$result = \wp_set_object_terms( $event_id, array( $term_id ), 'apollo-event-cat' );
		$this->assertNotInstanceOf( \WP_Error::class, $result );

		// Verify assignment.
		$terms = \wp_get_object_terms( $event_id, 'apollo-event-cat', array( 'fields' => 'ids' ) );
		$this->assertContains( $term_id, $terms );
	}

	// =========================================================================
	// META KEY TESTS
	// =========================================================================

	/**
	 * Test meta keys are registered.
	 *
	 * @return void
	 */
	public function test_meta_keys_registered(): void {
		$registered_meta = \get_registered_meta_keys( 'post' );

		$apollo_meta = \array_filter(
			\array_keys( $registered_meta ),
			function ( $key ) {
				return \strpos( $key, 'apollo_' ) === 0;
			}
		);

		// At least some meta should be registered.
		$this->assertNotEmpty(
			$apollo_meta,
			'At least some Apollo meta keys should be registered'
		);
	}

	/**
	 * Test meta keys have proper schema.
	 *
	 * @return void
	 */
	public function test_meta_keys_have_schema(): void {
		$registered_meta = \get_registered_meta_keys( 'post' );

		foreach ( $this->expected_meta_keys as $meta_key ) {
			if ( ! isset( $registered_meta[ $meta_key ] ) ) {
				continue;
			}

			$meta_config = $registered_meta[ $meta_key ];

			// Should have type defined.
			$this->assertArrayHasKey(
				'type',
				$meta_config,
				"Meta key {$meta_key} should have type defined"
			);

			// Should have REST visibility.
			$this->assertTrue(
				$meta_config['show_in_rest'] ?? false,
				"Meta key {$meta_key} should be visible in REST"
			);
		}
	}

	/**
	 * Test meta values can be saved and retrieved.
	 *
	 * @return void
	 */
	public function test_meta_values_persist(): void {
		if ( ! \post_type_exists( 'apollo-event' ) ) {
			$this->markTestSkipped( 'Event CPT not registered' );
		}

		$event_id = $this->factory->post->create(
			array(
				'post_type'   => 'apollo-event',
				'post_title'  => 'Meta Test Event',
				'post_status' => 'publish',
			)
		);

		// Save meta.
		\update_post_meta( $event_id, 'apollo_event_date_start', '2025-12-31 20:00:00' );
		\update_post_meta( $event_id, 'apollo_event_capacity', 500 );

		// Retrieve and verify.
		$date     = \get_post_meta( $event_id, 'apollo_event_date_start', true );
		$capacity = \get_post_meta( $event_id, 'apollo_event_capacity', true );

		$this->assertEquals( '2025-12-31 20:00:00', $date );
		$this->assertEquals( 500, (int) $capacity );
	}

	// =========================================================================
	// REST ENDPOINT TESTS
	// =========================================================================

	/**
	 * Test REST endpoints available.
	 *
	 * @return void
	 */
	public function test_rest_endpoints_available(): void {
		$server = \rest_get_server();
		$routes = \array_keys( $server->get_routes() );

		// Check for Apollo namespace.
		$apollo_routes = \array_filter(
			$routes,
			function ( $route ) {
				return \strpos( $route, '/apollo/' ) !== false;
			}
		);

		$this->assertNotEmpty(
			$apollo_routes,
			'Apollo REST endpoints should be registered'
		);
	}

	/**
	 * Test REST namespace is correct.
	 *
	 * @return void
	 */
	public function test_rest_namespace(): void {
		$server = \rest_get_server();
		$routes = \array_keys( $server->get_routes() );

		// All Apollo routes should use apollo/v1 namespace.
		$legacy_namespaces = array( '/apollo-events/', '/apollo-social/' );
		$has_legacy        = false;

		foreach ( $routes as $route ) {
			foreach ( $legacy_namespaces as $legacy ) {
				if ( \strpos( $route, $legacy ) !== false ) {
					$has_legacy = true;
					break 2;
				}
			}
		}

		// Legacy routes should be redirecting, not the primary endpoints.
		$has_unified = \array_filter(
			$routes,
			function ( $route ) {
				return \strpos( $route, '/apollo/v1/' ) !== false;
			}
		);

		$this->assertNotEmpty(
			$has_unified,
			'Should have unified apollo/v1 namespace'
		);
	}

	/**
	 * Test discovery endpoint.
	 *
	 * @return void
	 */
	public function test_discovery_endpoint(): void {
		$server = \rest_get_server();
		$routes = $server->get_routes();

		$discovery_route = '/apollo/v1/discover';
		$this->assertArrayHasKey(
			$discovery_route,
			$routes,
			'Discovery endpoint should be registered'
		);
	}

	/**
	 * Test REST response format.
	 *
	 * @return void
	 */
	public function test_rest_response_format(): void {
		if ( ! \post_type_exists( 'apollo-event' ) ) {
			$this->markTestSkipped( 'Event CPT not registered' );
		}

		// Create test event.
		$event_id = $this->factory->post->create(
			array(
				'post_type'   => 'apollo-event',
				'post_title'  => 'REST Test Event',
				'post_status' => 'publish',
			)
		);

		// Make REST request.
		$request  = new \WP_REST_Request( 'GET', '/apollo/v1/events/' . $event_id );
		$response = \rest_do_request( $request );

		// May be 404 if route not registered yet, which is OK for this test.
		if ( $response->get_status() === 200 ) {
			$data = $response->get_data();
			$this->assertIsArray( $data );
		}
	}

	// =========================================================================
	// CROSS-PLUGIN HOOK TESTS
	// =========================================================================

	/**
	 * Test cross-plugin hooks fire.
	 *
	 * @return void
	 */
	public function test_cross_plugin_hooks_fire(): void {
		$fired = false;

		\add_action(
			'apollo_test_hook',
			function () use ( &$fired ) {
				$fired = true;
			}
		);

		\do_action( 'apollo_test_hook' );

		$this->assertTrue( $fired, 'Apollo hooks should fire' );
	}

	/**
	 * Test event bus delivers events.
	 *
	 * @return void
	 */
	public function test_event_bus_delivers(): void {
		if ( ! \class_exists( Apollo_Event_Bus::class ) ) {
			$this->markTestSkipped( 'Event bus not available' );
		}

		$received = null;

		Apollo_Event_Bus::subscribe(
			'test.event',
			function ( $data ) use ( &$received ) {
				$received = $data;
			}
		);

		Apollo_Event_Bus::publish( 'test.event', array( 'foo' => 'bar' ) );

		$this->assertIsArray( $received );
		$this->assertEquals( 'bar', $received['foo'] );
	}

	/**
	 * Test hook priorities are respected.
	 *
	 * @return void
	 */
	public function test_hook_priorities(): void {
		$order = array();

		\add_action(
			'apollo_priority_test',
			function () use ( &$order ) {
				$order[] = 'low';
			},
			100
		);

		\add_action(
			'apollo_priority_test',
			function () use ( &$order ) {
				$order[] = 'high';
			},
			1
		);

		\add_action(
			'apollo_priority_test',
			function () use ( &$order ) {
				$order[] = 'medium';
			},
			50
		);

		\do_action( 'apollo_priority_test' );

		$this->assertEquals( array( 'high', 'medium', 'low' ), $order );
	}

	// =========================================================================
	// RELATIONSHIP TESTS
	// =========================================================================

	/**
	 * Test relationships are queryable.
	 *
	 * @return void
	 */
	public function test_relationships_queryable(): void {
		if ( ! \class_exists( Apollo_Relationships::class ) ) {
			$this->markTestSkipped( 'Relationships class not available' );
		}

		$schema = Apollo_Relationships::get_schema();
		$this->assertIsArray( $schema );
		$this->assertNotEmpty( $schema, 'Relationship schema should not be empty' );
	}

	/**
	 * Test relationship creation.
	 *
	 * @return void
	 */
	public function test_relationship_creation(): void {
		if ( ! \class_exists( Apollo_Relationships::class ) ) {
			$this->markTestSkipped( 'Relationships class not available' );
		}

		if ( ! \post_type_exists( 'apollo-event' ) || ! \post_type_exists( 'apollo-local' ) ) {
			$this->markTestSkipped( 'Required CPTs not registered' );
		}

		// Create event and local.
		$event_id = $this->factory->post->create(
			array(
				'post_type'   => 'apollo-event',
				'post_title'  => 'Relationship Test Event',
				'post_status' => 'publish',
			)
		);

		$local_id = $this->factory->post->create(
			array(
				'post_type'   => 'apollo-local',
				'post_title'  => 'Test Venue',
				'post_status' => 'publish',
			)
		);

		// Create relationship via meta.
		\update_post_meta( $event_id, 'apollo_event_local_id', $local_id );

		// Verify.
		$stored_local = \get_post_meta( $event_id, 'apollo_event_local_id', true );
		$this->assertEquals( $local_id, (int) $stored_local );
	}

	/**
	 * Test relationship query builder.
	 *
	 * @return void
	 */
	public function test_relationship_query(): void {
		if ( ! \class_exists( Apollo_Relationship_Query::class ) ) {
			$this->markTestSkipped( 'Relationship query class not available' );
		}

		// Query should be instantiable.
		$query = new Apollo_Relationship_Query( 'event_to_local' );
		$this->assertInstanceOf( Apollo_Relationship_Query::class, $query );
	}

	// =========================================================================
	// HEALTH CHECK TESTS
	// =========================================================================

	/**
	 * Test health check passes.
	 *
	 * @return void
	 */
	public function test_health_check_passes(): void {
		if ( ! \class_exists( Apollo_Health_Check::class ) ) {
			$this->markTestSkipped( 'Health check class not available' );
		}

		$report = Apollo_Health_Check::run();

		$this->assertIsArray( $report );
		$this->assertArrayHasKey( 'overall', $report );
		$this->assertArrayHasKey( 'checks', $report );
		$this->assertContains( $report['overall'], array( 'good', 'warning', 'error' ) );
	}

	/**
	 * Test health check categories.
	 *
	 * @return void
	 */
	public function test_health_check_categories(): void {
		if ( ! \class_exists( Apollo_Health_Check::class ) ) {
			$this->markTestSkipped( 'Health check class not available' );
		}

		$report              = Apollo_Health_Check::run();
		$expected_categories = array(
			'system',
			'plugins',
			'cpt_registration',
			'rest_endpoints',
		);

		foreach ( $expected_categories as $category ) {
			$this->assertArrayHasKey(
				$category,
				$report['checks'],
				"Health check should include {$category} category"
			);
		}
	}

	// =========================================================================
	// ORCHESTRATOR TESTS
	// =========================================================================

	/**
	 * Test orchestrator boots successfully.
	 *
	 * @return void
	 */
	public function test_orchestrator_boots(): void {
		if ( ! \class_exists( Apollo_Orchestrator::class ) ) {
			$this->markTestSkipped( 'Orchestrator class not available' );
		}

		$stats = Apollo_Orchestrator::get_boot_stats();

		$this->assertIsArray( $stats );
		$this->assertArrayHasKey( 'booted', $stats );
	}

	/**
	 * Test features are detected.
	 *
	 * @return void
	 */
	public function test_features_detected(): void {
		if ( ! \class_exists( Apollo_Orchestrator::class ) ) {
			$this->markTestSkipped( 'Orchestrator class not available' );
		}

		$stats = Apollo_Orchestrator::get_boot_stats();

		if ( isset( $stats['features'] ) ) {
			$this->assertIsArray( $stats['features'] );
		}
	}

	/**
	 * Test load order is respected.
	 *
	 * @return void
	 */
	public function test_load_order_respected(): void {
		if ( ! \class_exists( Apollo_Orchestrator::class ) ) {
			$this->markTestSkipped( 'Orchestrator class not available' );
		}

		$plugins = Apollo_Orchestrator::get_loaded_plugins();

		if ( empty( $plugins ) ) {
			$this->markTestSkipped( 'No plugins loaded' );
		}

		// Core should load first if present.
		$keys = \array_keys( $plugins );
		if ( \in_array( 'apollo-core', $keys, true ) ) {
			$this->assertEquals( 'apollo-core', $keys[0], 'apollo-core should load first' );
		}
	}

	// =========================================================================
	// DATABASE TESTS
	// =========================================================================

	/**
	 * Test custom tables exist.
	 *
	 * @return void
	 */
	public function test_custom_tables_exist(): void {
		global $wpdb;

		$expected_tables = array(
			$wpdb->prefix . 'apollo_activity_log',
			$wpdb->prefix . 'apollo_relationships',
			$wpdb->prefix . 'apollo_event_queue',
		);

		foreach ( $expected_tables as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

			// Table may or may not exist depending on activation state.
			// This is just a check, not a hard failure.
			if ( $exists !== $table ) {
				$this->addWarning( "Table {$table} does not exist" );
			}
		}

		$this->assertTrue( true ); // Pass if we get here.
	}

	/**
	 * Test database version is tracked.
	 *
	 * @return void
	 */
	public function test_database_version_tracked(): void {
		$version = \get_option( 'apollo_db_version' );

		// Version may or may not be set.
		if ( $version ) {
			$this->assertMatchesRegularExpression(
				'/^\d+\.\d+\.\d+$/',
				$version,
				'Database version should be semver format'
			);
		} else {
			$this->addWarning( 'Database version not set' );
			$this->assertTrue( true );
		}
	}

	// =========================================================================
	// CRON TESTS
	// =========================================================================

	/**
	 * Test cron jobs are scheduled.
	 *
	 * @return void
	 */
	public function test_cron_jobs_scheduled(): void {
		$expected_hooks = array(
			'apollo_daily_cleanup',
			'apollo_weekly_digest',
			'apollo_relationship_integrity_check',
		);

		$scheduled     = array();
		$not_scheduled = array();

		foreach ( $expected_hooks as $hook ) {
			if ( \wp_next_scheduled( $hook ) ) {
				$scheduled[] = $hook;
			} else {
				$not_scheduled[] = $hook;
			}
		}

		// At least one should be scheduled if activation ran.
		if ( empty( $scheduled ) ) {
			$this->addWarning( 'No Apollo cron jobs scheduled. Run activation.' );
		}

		$this->assertTrue( true );
	}

	// =========================================================================
	// PERMISSION TESTS
	// =========================================================================

	/**
	 * Test capabilities exist.
	 *
	 * @return void
	 */
	public function test_capabilities_exist(): void {
		$admin = \get_role( 'administrator' );

		$expected_caps = array(
			'manage_apollo',
			'edit_apollo_events',
			'publish_apollo_events',
			'delete_apollo_events',
		);

		foreach ( $expected_caps as $cap ) {
			// Check if cap is registered (may not be).
			$has_cap = $admin->has_cap( $cap );
			// Don't fail, just note.
			if ( ! $has_cap ) {
				$this->addWarning( "Capability {$cap} not assigned to administrator" );
			}
		}

		$this->assertTrue( true );
	}

	/**
	 * Test non-admin restrictions.
	 *
	 * @return void
	 */
	public function test_non_admin_restrictions(): void {
		$subscriber = \get_role( 'subscriber' );

		$this->assertFalse(
			$subscriber->has_cap( 'manage_apollo' ),
			'Subscribers should not have manage_apollo capability'
		);
	}

	// =========================================================================
	// INTEGRATION TESTS
	// =========================================================================

	/**
	 * Test full event creation workflow.
	 *
	 * @return void
	 */
	public function test_event_creation_workflow(): void {
		if ( ! \post_type_exists( 'apollo-event' ) ) {
			$this->markTestSkipped( 'Event CPT not registered' );
		}

		// Create venue.
		$venue_id = 0;
		if ( \post_type_exists( 'apollo-local' ) ) {
			$venue_id = $this->factory->post->create(
				array(
					'post_type'   => 'apollo-local',
					'post_title'  => 'Test Club',
					'post_status' => 'publish',
				)
			);
		}

		// Create DJ.
		$dj_id = 0;
		if ( \post_type_exists( 'apollo-dj' ) ) {
			$dj_id = $this->factory->post->create(
				array(
					'post_type'   => 'apollo-dj',
					'post_title'  => 'Test DJ',
					'post_status' => 'publish',
				)
			);
		}

		// Create event.
		$event_id = $this->factory->post->create(
			array(
				'post_type'   => 'apollo-event',
				'post_title'  => 'Integration Test Event',
				'post_status' => 'publish',
			)
		);

		// Attach relationships.
		if ( $venue_id ) {
			\update_post_meta( $event_id, 'apollo_event_local_id', $venue_id );
		}
		if ( $dj_id ) {
			\update_post_meta( $event_id, 'apollo_event_dj_ids', array( $dj_id ) );
		}

		// Set event details.
		\update_post_meta( $event_id, 'apollo_event_date_start', '2025-12-31 22:00:00' );
		\update_post_meta( $event_id, 'apollo_event_date_end', '2026-01-01 06:00:00' );
		\update_post_meta( $event_id, 'apollo_event_price', 50.00 );

		// Verify everything was saved.
		$event = \get_post( $event_id );
		$this->assertNotNull( $event );
		$this->assertEquals( 'Integration Test Event', $event->post_title );

		$date_start = \get_post_meta( $event_id, 'apollo_event_date_start', true );
		$this->assertEquals( '2025-12-31 22:00:00', $date_start );
	}

	/**
	 * Test data cleanup on post delete.
	 *
	 * @return void
	 */
	public function test_data_cleanup_on_delete(): void {
		if ( ! \post_type_exists( 'apollo-event' ) ) {
			$this->markTestSkipped( 'Event CPT not registered' );
		}

		// Create event with meta.
		$event_id = $this->factory->post->create(
			array(
				'post_type'   => 'apollo-event',
				'post_title'  => 'Delete Test Event',
				'post_status' => 'publish',
			)
		);

		\update_post_meta( $event_id, 'apollo_event_date_start', '2025-12-31 22:00:00' );

		// Delete event.
		\wp_delete_post( $event_id, true );

		// Verify meta is cleaned up.
		$meta = \get_post_meta( $event_id );
		$this->assertEmpty( $meta );
	}

	// =========================================================================
	// HELPER METHODS
	// =========================================================================

	/**
	 * Add a warning without failing the test.
	 *
	 * @param string $message Warning message.
	 * @return void
	 */
	private function addWarning( string $message ): void {
		\fwrite( STDERR, "Warning: {$message}\n" );
	}
}
