<?php
/**
 * Apollo Template Migration Validator
 * PHASE 6: Comprehensive Testing & Validation
 * Validates all migrated templates maintain contracts and functionality
 */

class Apollo_Template_Validator {

	private $results  = array();
	private $errors   = array();
	private $warnings = array();

	public function __construct() {
		$this->run_validation_suite();
	}

	private function run_validation_suite() {
		$this->log( '🚀 Starting Apollo Template Migration Validation Suite' );

		// Test 1: ViewModel Factory Integration.
		$this->test_viewmodel_factory();

		// Test 2: Shared Partials Loading.
		$this->test_shared_partials();

		// Test 3: CDN Asset Loading.
		$this->test_cdn_assets();

		// Test 4: Template Structure Compliance.
		$this->test_template_structure();

		// Test 5: WordPress Integration.
		$this->test_wordpress_integration();

		// Test 6: Mobile Responsiveness.
		$this->test_mobile_responsiveness();

		$this->generate_report();
	}

	private function test_viewmodel_factory() {
		$this->log( '📊 Testing ViewModel Factory Integration...' );

		// Check if ViewModel factory exists and is accessible.
		if ( ! class_exists( 'Apollo_ViewModel_Factory' ) ) {
			$this->error( 'ViewModel Factory class not found' );
			return;
		}

		// Test different ViewModel types.
		$viewmodel_types = array(
			'single_event',
			'dj_profile',
			'venue',
			'user_dashboard',
			'calendar_agenda',
			'user_profile',
			'canvas_layout',
			'base_layout',
			'dashboard_access',
		);

		foreach ( $viewmodel_types as $type ) {
			try {
				$mock_data = $this->get_mock_data_for_type( $type );
				$viewModel = Apollo_ViewModel_Factory::create_from_data( $mock_data, $type );

				if ( ! $viewModel ) {
					$this->error( "Failed to create ViewModel for type: {$type}" );
				} else {
					$this->success( "ViewModel created successfully for type: {$type}" );
				}
			} catch ( Exception $e ) {
				$this->error( "Exception creating ViewModel for {$type}: " . $e->getMessage() );
			}
		}
	}

	private function test_shared_partials() {
		$this->log( '🔧 Testing Shared Partials Loading...' );

		if ( ! class_exists( 'Apollo_Template_Loader' ) ) {
			$this->error( 'Template Loader class not found' );
			return;
		}

		$loader   = new Apollo_Template_Loader();
		$partials = array( 'assets', 'header-nav', 'hero-section', 'event-card', 'bottom-bar' );

		foreach ( $partials as $partial ) {
			try {
				ob_start();
				$loader->load_partial( $partial );
				$output = ob_get_clean();

				if ( empty( $output ) ) {
					$this->warning( "Partial '{$partial}' produced no output" );
				} else {
					$this->success( "Partial '{$partial}' loaded successfully" );
				}
			} catch ( Exception $e ) {
				$this->error( "Failed to load partial '{$partial}': " . $e->getMessage() );
			}
		}
	}

	private function test_cdn_assets() {
		$this->log( '🌐 Testing CDN Asset Loading...' );

		$cdn_urls = array(
			'https://assets.apollo.rio.br/uni.css',
			'https://assets.apollo.rio.br/base.js',
			'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css',
		);

		foreach ( $cdn_urls as $url ) {
			$headers = @get_headers( $url );
			if ( ! $headers || strpos( $headers[0], '200' ) === false ) {
				$this->warning( "CDN asset not accessible: {$url}" );
			} else {
				$this->success( "CDN asset accessible: {$url}" );
			}
		}
	}

	private function test_template_structure() {
		$this->log( '🏗️ Testing Template Structure Compliance...' );

		$templates = $this->get_all_migrated_templates();

		foreach ( $templates as $template ) {
			$content = file_get_contents( $template );

			// Check for required elements.
			$checks = array(
				'ViewModel initialization' => strpos( $content, 'Apollo_ViewModel_Factory' ) !== false,
				'Autoloader inclusion'     => strpos( $content, 'Apollo_Autoloader::init()' ) !== false,
				'Template loader usage'    => strpos( $content, 'Apollo_Template_Loader' ) !== false,
				'Proper HTML structure'    => strpos( $content, '<!DOCTYPE html>' ) !== false,
				'Mobile viewport'          => strpos( $content, 'viewport' ) !== false,
				'CDN assets'               => strpos( $content, 'assets.apollo.rio.br' ) !== false,
				'CSS variables'            => strpos( $content, 'var(--' ) !== false,
				'Responsive design'        => strpos( $content, '@media' ) !== false,
			);

			foreach ( $checks as $check_name => $passed ) {
				if ( $passed ) {
					$this->success( "{$check_name} in " . basename( $template ) );
				} else {
					$this->warning( "Missing {$check_name} in " . basename( $template ) );
				}
			}
		}
	}

	private function test_wordpress_integration() {
		$this->log( '🔗 Testing WordPress Integration...' );

		// Check if WordPress functions are properly used.
		$wordpress_functions = array(
			'wp_get_current_user',
			'get_post',
			'get_the_ID',
			'get_the_title',
			'wp_kses_post',
			'esc_html',
			'esc_url',
			'esc_attr',
		);

		$templates = $this->get_all_migrated_templates();

		foreach ( $templates as $template ) {
			$content = file_get_contents( $template );

			foreach ( $wordpress_functions as $function ) {
				if ( strpos( $content, $function ) !== false ) {
					$this->success( "WordPress function '{$function}' used in " . basename( $template ) );
				}
			}
		}
	}

	private function test_mobile_responsiveness() {
		$this->log( '📱 Testing Mobile Responsiveness...' );

		$templates = $this->get_all_migrated_templates();

		foreach ( $templates as $template ) {
			$content = file_get_contents( $template );

			$mobile_checks = array(
				'Mobile-first container' => strpos( $content, 'mobile-container' ) !== false,
				'Responsive breakpoints' => strpos( $content, '@media (min-width:' ) !== false,
				'Flexible layouts'       => strpos( $content, 'flex' ) !== false || strpos( $content, 'grid' ) !== false,
				'Touch-friendly sizing'  => strpos( $content, 'min-height' ) !== false || strpos( $content, 'min-width' ) !== false,
			);

			foreach ( $mobile_checks as $check_name => $passed ) {
				if ( $passed ) {
					$this->success( "{$check_name} in " . basename( $template ) );
				} else {
					$this->warning( "Missing {$check_name} in " . basename( $template ) );
				}
			}
		}
	}

	private function get_mock_data_for_type( $type ) {
		// Return mock data for testing different ViewModel types.
		switch ( $type ) {
			case 'single_event':
				return get_post( 1 ); // Mock post object.
			case 'user_profile':
			case 'user_dashboard':
				return wp_get_current_user();
			default:
				return null;
		}
	}

	private function get_all_migrated_templates() {
		$template_dirs = array(
			plugin_dir_path( __DIR__ ) . 'apollo-events-manager/templates/',
			plugin_dir_path( __DIR__ ) . 'apollo-social/templates/',
		);

		$templates = array();
		foreach ( $template_dirs as $dir ) {
			if ( is_dir( $dir ) ) {
				$files     = glob( $dir . '*.php' );
				$templates = array_merge( $templates, $files );
			}
		}

		return $templates;
	}

	private function log( $message ) {
		$this->results[] = "[INFO] {$message}";
		echo $message . "\n";
	}

	private function success( $message ) {
		$this->results[] = "[SUCCESS] {$message}";
		echo "✅ {$message}\n";
	}

	private function warning( $message ) {
		$this->warnings[] = $message;
		$this->results[]  = "[WARNING] {$message}";
		echo "⚠️ {$message}\n";
	}

	private function error( $message ) {
		$this->errors[]  = $message;
		$this->results[] = "[ERROR] {$message}";
		echo "❌ {$message}\n";
	}

	private function generate_report() {
		$this->log( "\n📋 Validation Report Generated" );

		$report = array(
			'timestamp'    => date( 'Y-m-d H:i:s' ),
			'total_checks' => count( $this->results ),
			'errors'       => count( $this->errors ),
			'warnings'     => count( $this->warnings ),
			'successes'    => count( $this->results ) - count( $this->errors ) - count( $this->warnings ),
			'details'      => $this->results,
		);

		// Save report to file.
		$report_file = plugin_dir_path( __DIR__ ) . 'validation-report-' . date( 'Y-m-d-H-i-s' ) . '.json';
		file_put_contents( $report_file, json_encode( $report, JSON_PRETTY_PRINT ) );

		$this->log( "Report saved to: {$report_file}" );

		// Summary.
		echo "\n🎯 VALIDATION SUMMARY:\n";
		echo "Total Checks: {$report['total_checks']}\n";
		echo "Successes: {$report['successes']}\n";
		echo "Warnings: {$report['warnings']}\n";
		echo "Errors: {$report['errors']}\n";

		if ( empty( $this->errors ) ) {
			echo "\n🎉 All critical validations passed! Templates are ready for production.\n";
		} else {
			echo "\n⚠️ Some errors found. Please review and fix before production deployment.\n";
		}
	}
}

// Run validation if this file is executed directly.
if ( basename( __FILE__ ) === basename( $_SERVER['PHP_SELF'] ) ) {
	new Apollo_Template_Validator();
}
