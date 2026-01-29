<?php
/**
 * Apollo Template Migration Smoke Tests
 * PHASE 6: WordPress-Compatible Validation
 * Tests migrated templates within WordPress environment
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include required files.
require_once plugin_dir_path( __DIR__ ) . 'includes/class-autoloader.php';
$autoloader = new \Apollo_Core\Autoloader();
$autoloader->register();

class Apollo_Template_Smoke_Tests {

	private $test_results = array();

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'wp_ajax_run_template_tests', array( $this, 'run_tests_ajax' ) );
	}

	public function add_admin_menu() {
		add_submenu_page(
			'tools.php',
			'Apollo Template Tests',
			'Apollo Template Tests',
			'manage_options',
			'apollo-template-tests',
			array( $this, 'admin_page' )
		);
	}

	public function admin_page() {
		?>
		<div class="wrap">
			<h1>Apollo Template Migration Tests</h1>
			<p>Test all migrated templates to ensure they work correctly after ViewModel migration.</p>

			<div id="test-results">
				<button id="run-tests" class="button button-primary">Run Template Tests</button>
				<div id="test-output" style="margin-top: 20px; padding: 20px; background: #f5f5f5; border-radius: 5px; display: none;"></div>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#run-tests').click(function() {
				$(this).prop('disabled', true).text('Running Tests...');
				$('#test-output').show().html('<p>Running template validation tests...</p>');

				$.post(ajaxurl, {
					action: 'run_template_tests',
					nonce: '<?php echo wp_create_nonce( 'apollo_template_tests' ); ?>'
				}, function(response) {
					$('#test-output').html(response);
					$('#run-tests').prop('disabled', false).text('Run Template Tests');
				});
			});
		});
		</script>
		<?php
	}

	public function run_tests_ajax() {
		check_ajax_referer( 'apollo_template_tests', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		ob_start();
		$this->run_all_tests();
		$output = ob_get_clean();

		echo $output;
		wp_die();
	}

	private function run_all_tests() {
		echo '<h3>🚀 Apollo Template Migration Validation</h3>';
		echo "<div style='font-family: monospace; background: #000; color: #0f0; padding: 20px; border-radius: 5px;'>";

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

		echo '</div>';
		echo '<h3>📋 Test Summary</h3>';
		echo '<ul>';
		foreach ( $this->test_results as $result ) {
			echo "<li>{$result}</li>";
		}
		echo '</ul>';
	}

	private function test_core_classes() {
		echo "Testing core classes...\n";

		$classes = array(
			'Apollo_Autoloader',
			'Apollo_ViewModel_Factory',
			'Apollo_Template_Loader',
		);

		foreach ( $classes as $class ) {
			if ( class_exists( $class ) ) {
				echo "✅ {$class} exists\n";
				$this->test_results[] = "✅ {$class} class available";
			} else {
				echo "❌ {$class} missing\n";
				$this->test_results[] = "❌ {$class} class missing";
			}
		}
	}

	private function test_viewmodel_factory() {
		echo "\nTesting ViewModel Factory...\n";

		if ( ! class_exists( 'Apollo_ViewModel_Factory' ) ) {
			echo "❌ ViewModel Factory not available\n";
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
				echo "✅ ViewModel Factory working\n";
				$this->test_results[] = '✅ ViewModel Factory functional';
			} else {
				echo "❌ ViewModel Factory failed\n";
				$this->test_results[] = '❌ ViewModel Factory not working';
			}
		} catch ( Exception $e ) {
			echo '❌ ViewModel Factory error: ' . $e->getMessage() . "\n";
			$this->test_results[] = '❌ ViewModel Factory error: ' . $e->getMessage();
		}
	}

	private function test_template_loader() {
		echo "\nTesting Template Loader...\n";

		if ( ! class_exists( 'Apollo_Template_Loader' ) ) {
			echo "❌ Template Loader not available\n";
			return;
		}

		try {
			$loader = new Apollo_Template_Loader();

			// Test loading assets partial.
			ob_start();
			$loader->load_partial( 'assets' );
			$output = ob_get_clean();

			if ( ! empty( $output ) ) {
				echo "✅ Template Loader working\n";
				$this->test_results[] = '✅ Template Loader functional';
			} else {
				echo "⚠️ Template Loader produced no output\n";
				$this->test_results[] = '⚠️ Template Loader needs verification';
			}
		} catch ( Exception $e ) {
			echo '❌ Template Loader error: ' . $e->getMessage() . "\n";
			$this->test_results[] = '❌ Template Loader error: ' . $e->getMessage();
		}
	}

	private function test_cdn_assets() {
		echo "\nTesting CDN Assets...\n";

		$assets = array(
			'https://assets.apollo.rio.br/index.min.js',
			'https://assets.apollo.rio.br/styles/index.css',
		);

		foreach ( $assets as $asset ) {
			$headers = @get_headers( $asset, true );
			if ( $headers && strpos( $headers[0], '200' ) !== false ) {
				echo '✅ CDN asset accessible: ' . basename( $asset ) . "\n";
				$this->test_results[] = '✅ CDN asset accessible: ' . basename( $asset );
			} else {
				echo '❌ CDN asset not accessible: ' . basename( $asset ) . "\n";
				$this->test_results[] = '❌ CDN asset not accessible: ' . basename( $asset );
			}
		}
	}

	private function test_template_files() {
		echo "\nTesting Template Files...\n";

		$template_paths = array(
			// Apollo Events Manager.
			plugin_dir_path( __DIR__ ) . '../apollo-events-manager/templates/single-event_listing.php',
			plugin_dir_path( __DIR__ ) . '../apollo-events-manager/templates/single-event-standalone.php',
			plugin_dir_path( __DIR__ ) . '../apollo-events-manager/templates/single-event_dj.php',
			plugin_dir_path( __DIR__ ) . '../apollo-events-manager/templates/single-event_local.php',
			plugin_dir_path( __DIR__ ) . '../apollo-events-manager/templates/shortcode-user-dashboard.php',
			plugin_dir_path( __DIR__ ) . '../apollo-events-manager/templates/shortcode-dj-profile.php',
			plugin_dir_path( __DIR__ ) . '../apollo-events-manager/templates/shortcode-cena-rio.php',
			plugin_dir_path( __DIR__ ) . '../apollo-events-manager/templates/page-event-dashboard.php',

			// Apollo Social.
			plugin_dir_path( __DIR__ ) . '../apollo-social/templates/user-page-view.php',
			plugin_dir_path( __DIR__ ) . '../apollo-social/templates/canvas-layout.php',
			plugin_dir_path( __DIR__ ) . '../apollo-social/templates/layout-base.php',
		);

		foreach ( $template_paths as $path ) {
			if ( file_exists( $path ) ) {
				$lines = count( file( $path ) );
				echo '✅ ' . basename( $path ) . " exists ({$lines} lines)\n";
				$this->test_results[] = '✅ ' . basename( $path ) . " exists ({$lines} lines)";
			} else {
				echo '❌ ' . basename( $path ) . " missing\n";
				$this->test_results[] = '❌ ' . basename( $path ) . ' missing';
			}
		}
	}

	private function test_template_structure() {
		echo "\nTesting Template Structure...\n";

		$templates = array(
			plugin_dir_path( __DIR__ ) . '../apollo-events-manager/templates/single-event_listing.php',
			plugin_dir_path( __DIR__ ) . '../apollo-social/templates/user-page-view.php',
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
						echo "✅ {$template_name}: {$check}\n";
					} else {
						echo "❌ {$template_name}: Missing {$check}\n";
					}
				}
			}
		}
	}
}

// Initialize the test suite.
new Apollo_Template_Smoke_Tests();
