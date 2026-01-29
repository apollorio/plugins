<?php
/**
 * Apollo Template Migration Smoke Tests - WP-CLI Command
 * PHASE 6: WordPress-Compatible Validation via CLI
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

class Apollo_Template_Tests_CLI_Command {

	/**
	 * Run Apollo template migration smoke tests
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo test-templates
	 *
	 * @when after_wp_load
	 */
	public function __invoke( $args, $assoc_args ) {
		WP_CLI::log( '🚀 Apollo Template Migration Validation' );
		WP_CLI::log( '=====================================' );

		$tests   = new Apollo_Template_Smoke_Tests_CLI();
		$results = $tests->run_all_tests();

		WP_CLI::log( '' );
		WP_CLI::log( '📋 Test Summary' );
		WP_CLI::log( '===============' );

		foreach ( $results as $result ) {
			if ( strpos( $result, '✅' ) === 0 ) {
				WP_CLI::success( str_replace( '✅ ', '', $result ) );
			} elseif ( strpos( $result, '❌' ) === 0 ) {
				WP_CLI::error( str_replace( '❌ ', '', $result ) );
			} else {
				WP_CLI::warning( str_replace( '⚠️ ', '', $result ) );
			}
		}

		WP_CLI::log( '' );
		WP_CLI::success( 'Template validation complete!' );
	}
}

class Apollo_Template_Smoke_Tests_CLI {

	private $test_results = array();

	public function run_all_tests() {
		// Test 1: Core Classes Exist.
		$this->test_core_classes();

		// Test 2: ViewModel Factory.
		$this->test_viewmodel_factory();

		// Test 3: Template Loader.
		$this->test_template_loader();

		// Test 4: CDN Assets.
		$this->test_cdn_assets();

		// Test 5: Template Files Exist.
		$this->test_template_files();

		// Test 6: Template Structure.
		$this->test_template_structure();

		return $this->test_results;
	}

	private function test_core_classes() {
		WP_CLI::log( 'Testing core classes...' );

		$classes = array(
			'\Apollo_Core\Autoloader',
			'Apollo_ViewModel_Factory',
			'Apollo_Template_Loader',
		);

		foreach ( $classes as $class ) {
			if ( class_exists( $class ) ) {
				$this->test_results[] = "✅ {$class} class available";
			} else {
				$this->test_results[] = "❌ {$class} class missing";
			}
		}
	}

	private function test_viewmodel_factory() {
		WP_CLI::log( 'Testing ViewModel Factory...' );

		if ( ! class_exists( 'Apollo_ViewModel_Factory' ) ) {
			$this->test_results[] = '❌ ViewModel Factory not available';
			return;
		}

		try {
			// Test with mock data.
			$mock_post             = new stdClass();
			$mock_post->ID         = 1;
			$mock_post->post_title = 'Test Event';
			$mock_post->post_type  = 'event_listing';

			$viewModel = Apollo_ViewModel_Factory::create_from_data( $mock_post, 'single_event' );

			if ( $viewModel ) {
				$this->test_results[] = '✅ ViewModel Factory functional';
			} else {
				$this->test_results[] = '❌ ViewModel Factory not working';
			}
		} catch ( Exception $e ) {
			$this->test_results[] = '❌ ViewModel Factory error: ' . $e->getMessage();
		}
	}

	private function test_template_loader() {
		WP_CLI::log( 'Testing Template Loader...' );

		if ( ! class_exists( 'Apollo_Template_Loader' ) ) {
			$this->test_results[] = '❌ Template Loader not available';
			return;
		}

		try {
			$loader = new Apollo_Template_Loader();

			// Test loading assets partial.
			ob_start();
			$loader->load_partial( 'assets' );
			$output = ob_get_clean();

			if ( ! empty( $output ) ) {
				$this->test_results[] = '✅ Template Loader functional';
			} else {
				$this->test_results[] = '⚠️ Template Loader needs verification';
			}
		} catch ( Exception $e ) {
			$this->test_results[] = '❌ Template Loader error: ' . $e->getMessage();
		}
	}

	private function test_cdn_assets() {
		WP_CLI::log( 'Testing CDN Assets...' );

		$assets = array(
			'https://assets.apollo.rio.br/index.min.js',
			'https://assets.apollo.rio.br/styles/index.css',
		);

		foreach ( $assets as $asset ) {
			$headers = @get_headers( $asset, true );
			if ( $headers && strpos( $headers[0], '200' ) !== false ) {
				$this->test_results[] = '✅ CDN asset accessible: ' . basename( $asset );
			} else {
				$this->test_results[] = '❌ CDN asset not accessible: ' . basename( $asset );
			}
		}
	}

	private function test_template_files() {
		WP_CLI::log( 'Testing Template Files...' );

		$template_paths = array(
			// Apollo Events Manager.
			WP_PLUGIN_DIR . '/apollo-events-manager/templates/single-event_listing.php',
			WP_PLUGIN_DIR . '/apollo-events-manager/templates/single-event-standalone.php',
			WP_PLUGIN_DIR . '/apollo-events-manager/templates/single-event_dj.php',
			WP_PLUGIN_DIR . '/apollo-events-manager/templates/single-event_local.php',
			WP_PLUGIN_DIR . '/apollo-events-manager/templates/shortcode-user-dashboard.php',
			WP_PLUGIN_DIR . '/apollo-events-manager/templates/shortcode-dj-profile.php',
			WP_PLUGIN_DIR . '/apollo-events-manager/templates/shortcode-cena-rio.php',
			WP_PLUGIN_DIR . '/apollo-events-manager/templates/page-event-dashboard.php',

			// Apollo Social.
			WP_PLUGIN_DIR . '/apollo-social/templates/user-page-view.php',
			WP_PLUGIN_DIR . '/apollo-social/templates/canvas-layout.php',
			WP_PLUGIN_DIR . '/apollo-social/templates/layout-base.php',
		);

		foreach ( $template_paths as $path ) {
			if ( file_exists( $path ) ) {
				$lines                = count( file( $path ) );
				$this->test_results[] = '✅ ' . basename( $path ) . " exists ({$lines} lines)";
			} else {
				$this->test_results[] = '❌ ' . basename( $path ) . ' missing';
			}
		}
	}

	private function test_template_structure() {
		WP_CLI::log( 'Testing Template Structure...' );

		$templates = array(
			WP_PLUGIN_DIR . '/apollo-events-manager/templates/single-event_listing.php',
			WP_PLUGIN_DIR . '/apollo-social/templates/user-page-view.php',
		);

		foreach ( $templates as $template ) {
			if ( file_exists( $template ) ) {
				$content = file_get_contents( $template );

				$checks = array(
					'ViewModel usage' => strpos( $content, 'Apollo_ViewModel_Factory' ) !== false,
					'Autoloader'      => strpos( $content, 'Apollo_Autoloader::init()' ) !== false,
					'HTML5 doctype'   => strpos( $content, '<!DOCTYPE html>' ) !== false,
					'CDN assets'      => strpos( $content, 'assets.apollo.rio.br' ) !== false,
					'Mobile viewport' => strpos( $content, 'viewport' ) !== false,
				);

				$template_name = basename( $template );
				foreach ( $checks as $check => $passed ) {
					if ( $passed ) {
						$this->test_results[] = "✅ {$template_name}: {$check}";
					} else {
						$this->test_results[] = "❌ {$template_name}: Missing {$check}";
					}
				}
			}
		}
	}
}

// Register the WP-CLI command.
WP_CLI::add_command( 'apollo test-templates', 'Apollo_Template_Tests_CLI_Command' );
