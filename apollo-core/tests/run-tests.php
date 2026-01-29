<?php
/**
 * Apollo Template Migration Test Runner
 * PHASE 6: Web-Accessible Validation
 * Access via: /wp-content/plugins/apollo-core/tests/run-tests.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Bootstrap WordPress.
	$wp_load = dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/wp-load.php';
	if ( file_exists( $wp_load ) ) {
		require_once $wp_load;
	} else {
		die( 'WordPress not found. Please run this from within a WordPress installation.' );
	}
}

// Security check.
if ( ! current_user_can( 'manage_options' ) && ! defined( 'WP_CLI' ) ) {
	wp_die( 'Access denied. You must be logged in as an administrator.' );
}

// Include required files.
require_once plugin_dir_path( __DIR__ ) . 'includes/class-autoloader.php';
if ( class_exists( '\Apollo_Core\Autoloader' ) ) {
	$autoloader = new \Apollo_Core\Autoloader();
	$autoloader->register();
}

// Include smoke tests.
require_once __DIR__ . '/apollo-smoke-tests.php';

class Apollo_Template_Test_Runner {

	public function run() {
		header( 'Content-Type: text/html; charset=utf-8' );
		?>
		<!DOCTYPE html>
		<html lang="en">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Apollo Template Migration Tests</title>
			<style>
				body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 40px; background: #f5f5f5; }
				.container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
				h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
				.test-section { margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #3498db; }
				.test-output { font-family: 'Courier New', monospace; background: #000; color: #0f0; padding: 20px; border-radius: 5px; white-space: pre-wrap; }
				.success { color: #27ae60; }
				.error { color: #e74c3c; }
				.warning { color: #f39c12; }
				.summary { background: #ecf0f1; padding: 20px; border-radius: 5px; margin-top: 30px; }
				.summary ul { list-style: none; padding: 0; }
				.summary li { margin: 5px 0; }
			</style>
		</head>
		<body>
			<div class="container">
				<h1>🚀 Apollo Template Migration Validation</h1>
				<p><strong>Phase 6: Testing & Validation</strong> - Comprehensive smoke tests for all migrated templates</p>

				<?php $this->run_tests(); ?>

				<div class="summary">
					<h3>📋 Test Summary</h3>
					<ul id="summary-list">
						<!-- Summary will be populated by JavaScript -->
					</ul>
				</div>
			</div>

			<script>
				// Simple summary generator.
				document.addEventListener('DOMContentLoaded', function() {
					const output = document.querySelector('.test-output');
					if (!output) return;

					const lines = output.textContent.split('\n');
					const summaryList = document.getElementById('summary-list');

					lines.forEach(line => {
						if (line.includes('✅') || line.includes('❌') || line.includes('⚠️')) {
							const li = document.createElement('li');
							li.className = line.includes('✅') ? 'success' :
											line.includes('❌') ? 'error' : 'warning';
							li.textContent = line.trim();
							summaryList.appendChild(li);
						}
					});
				});
			</script>
		</body>
		</html>
		<?php
	}

	private function run_tests() {
		echo '<div class="test-section">';
		echo '<h3>Running Template Validation Tests...</h3>';
		echo '<div class="test-output">';

		$tests   = new Apollo_Template_Smoke_Tests_CLI();
		$results = $tests->run_all_tests();

		echo '</div>';
		echo '</div>';
	}
}

// Run the tests.
$runner = new Apollo_Template_Test_Runner();
$runner->run();
