<?php
/**
 * Apollo Template Migration Static Validation
 * PHASE 6: File-Based Validation (No WordPress Required)
 * Tests template files, structure, and basic integrity
 */

class Apollo_Template_Static_Validator {

	private $base_path;
	private $results = array();

	public function __construct() {
		$this->base_path = dirname( __DIR__ );
	}

	public function run_validation() {
		echo "🚀 Apollo Template Migration Static Validation\n";
		echo "==============================================\n\n";

		$this->test_template_files_exist();
		$this->test_template_structure();
		$this->test_shared_partials();
		$this->test_cdn_assets_accessibility();
		$this->test_viewmodel_classes_exist();
		$this->test_migration_completeness();

		$this->print_summary();
	}

	private function test_template_files_exist() {
		echo "Testing Template Files Existence...\n";

		$templates = array(
			// Apollo Events Manager (8 templates).
			'../apollo-events-manager/templates/single-event_listing.php',
			'../apollo-events-manager/templates/single-event-standalone.php',
			'../apollo-events-manager/templates/single-event_dj.php',
			'../apollo-events-manager/templates/single-event_local.php',
			'../apollo-events-manager/templates/shortcode-user-dashboard.php',
			'../apollo-events-manager/templates/shortcode-dj-profile.php',
			'../apollo-events-manager/templates/shortcode-cena-rio.php',
			'../apollo-events-manager/templates/page-event-dashboard.php',

			// Apollo Social (3 templates).
			'../apollo-social/templates/user-page-view.php',
			'../apollo-social/templates/canvas-layout.php',
			'../apollo-social/templates/layout-base.php',

			// Shared Partials (5 partials).
			'templates/partials/assets.php',
			'templates/partials/header-nav.php',
			'templates/partials/hero-section.php',
			'templates/partials/event-card.php',
			'templates/partials/bottom-bar.php',
		);

		foreach ( $templates as $template ) {
			$path = $this->base_path . '/' . $template;
			if ( file_exists( $path ) ) {
				$lines = count( file( $path ) );
				echo "✅ {$template} exists ({$lines} lines)\n";
				$this->results[] = "✅ {$template} exists ({$lines} lines)";
			} else {
				echo "❌ {$template} missing\n";
				$this->results[] = "❌ {$template} missing";
			}
		}
		echo "\n";
	}

	private function test_template_structure() {
		echo "Testing Template Structure...\n";

		$full_html_templates = array(
			'../apollo-events-manager/templates/single-event_listing.php',
			'../apollo-events-manager/templates/single-event-standalone.php',
			'../apollo-events-manager/templates/single-event_dj.php',
			'../apollo-events-manager/templates/single-event_local.php',
		);

		$wordpress_theme_templates = array(
			'../apollo-social/templates/user-page-view.php',
			'../apollo-social/templates/canvas-layout.php',
			'../apollo-social/templates/layout-base.php',
			'../apollo-events-manager/templates/shortcode-user-dashboard.php',
			'../apollo-events-manager/templates/shortcode-dj-profile.php',
			'../apollo-events-manager/templates/shortcode-cena-rio.php',
			'../apollo-events-manager/templates/page-event-dashboard.php',
		);

		// Test full HTML templates.
		foreach ( $full_html_templates as $template ) {
			$path = $this->base_path . '/' . $template;
			if ( file_exists( $path ) ) {
				$content       = file_get_contents( $path );
				$template_name = basename( $template );

				$checks = array(
					'HTML5 doctype'   => strpos( $content, '<!DOCTYPE html>' ) !== false,
					'HTML structure'  => strpos( $content, '<html' ) !== false && strpos( $content, '</html>' ) !== false,
					'Head section'    => strpos( $content, '<head>' ) !== false && strpos( $content, '</head>' ) !== false,
					'Body section'    => strpos( $content, '<body' ) !== false && strpos( $content, '</body>' ) !== false,
					'Mobile viewport' => strpos( $content, 'viewport' ) !== false,
					'CDN assets'      => strpos( $content, 'assets.apollo.rio.br' ) !== false,
					'ViewModel usage' => strpos( $content, 'Apollo_ViewModel_Factory' ) !== false,
					'Autoloader init' => strpos( $content, 'Apollo_Autoloader::init()' ) !== false,
					'Template Loader' => strpos( $content, 'Apollo_Template_Loader' ) !== false,
				);

				foreach ( $checks as $check => $passed ) {
					if ( $passed ) {
						echo "✅ {$template_name}: {$check}\n";
						$this->results[] = "✅ {$template_name}: {$check}";
					} else {
						echo "❌ {$template_name}: Missing {$check}\n";
						$this->results[] = "❌ {$template_name}: Missing {$check}";
					}
				}
			}
		}

		// Test WordPress theme-integrated templates.
		foreach ( $wordpress_theme_templates as $template ) {
			$path = $this->base_path . '/' . $template;
			if ( file_exists( $path ) ) {
				$content       = file_get_contents( $path );
				$template_name = basename( $template );

				$checks = array(
					'WordPress theme integration' => ( strpos( $content, 'get_header()' ) !== false || strpos( $content, 'get_footer()' ) !== false ),
					'ViewModel usage'             => strpos( $content, 'Apollo_ViewModel_Factory' ) !== false,
					'Autoloader init'             => strpos( $content, 'Apollo_Autoloader::init()' ) !== false,
					'Template Loader'             => strpos( $content, 'Apollo_Template_Loader' ) !== false,
					'Mobile responsive'           => strpos( $content, 'mobile' ) !== false || strpos( $content, 'responsive' ) !== false,
				);

				foreach ( $checks as $check => $passed ) {
					if ( $passed ) {
						echo "✅ {$template_name}: {$check}\n";
						$this->results[] = "✅ {$template_name}: {$check}";
					} else {
						echo "❌ {$template_name}: Missing {$check}\n";
						$this->results[] = "❌ {$template_name}: Missing {$check}";
					}
				}
			}
		}
		echo "\n";
	}

	private function test_shared_partials() {
		echo "Testing Shared Partials...\n";

		$partials = array(
			'templates/partials/assets.php',
			'templates/partials/header-nav.php',
			'templates/partials/hero-section.php',
			'templates/partials/event-card.php',
			'templates/partials/bottom-bar.php',
		);

		foreach ( $partials as $partial ) {
			$path = $this->base_path . '/' . $partial;
			if ( file_exists( $path ) ) {
				$content      = file_get_contents( $path );
				$partial_name = basename( $partial, '.php' );

				// Check for expected content in partials.
				$checks = array();
				switch ( $partial_name ) {
					case 'assets':
						$checks = array(
							'UNI CSS'   => strpos( $content, 'uni.css' ) !== false,
							'Base JS'   => strpos( $content, 'base.js' ) !== false,
							'RemixIcon' => strpos( $content, 'remixicon' ) !== false,
						);
						break;
					case 'header-nav':
						$checks = array(
							'Navigation' => strpos( $content, 'nav' ) !== false,
							'Responsive' => strpos( $content, 'mobile' ) !== false || strpos( $content, 'hamburger' ) !== false,
						);
						break;
					case 'hero-section':
						$checks = array(
							'Hero content'    => strpos( $content, 'hero' ) !== false,
							'Visual elements' => strpos( $content, 'img' ) !== false || strpos( $content, 'background' ) !== false,
						);
						break;
					case 'event-card':
						$checks = array(
							'Card structure' => strpos( $content, 'card' ) !== false,
							'Event data'     => strpos( $content, 'event' ) !== false,
						);
						break;
					case 'bottom-bar':
						$checks = array(
							'Footer content' => strpos( $content, 'footer' ) !== false || strpos( $content, 'bottom' ) !== false,
							'Links'          => strpos( $content, 'href' ) !== false,
						);
						break;
				}

				foreach ( $checks as $check => $passed ) {
					if ( $passed ) {
						echo "✅ {$partial_name}: {$check}\n";
						$this->results[] = "✅ {$partial_name}: {$check}";
					} else {
						echo "⚠️ {$partial_name}: Missing {$check}\n";
						$this->results[] = "⚠️ {$partial_name}: Missing {$check}";
					}
				}
			}
		}
		echo "\n";
	}

	private function test_cdn_assets_accessibility() {
		echo "Testing CDN Assets Accessibility...\n";

		$assets = array(
			'https://assets.apollo.rio.br/index.min.js',
			'https://assets.apollo.rio.br/styles/index.css',
		);

		foreach ( $assets as $asset ) {
			$headers = @get_headers( $asset, true );
			if ( $headers && strpos( $headers[0], '200' ) !== false ) {
				echo '✅ CDN asset accessible: ' . basename( $asset ) . "\n";
				$this->results[] = '✅ CDN asset accessible: ' . basename( $asset );
			} else {
				echo '❌ CDN asset not accessible: ' . basename( $asset ) . "\n";
				$this->results[] = '❌ CDN asset not accessible: ' . basename( $asset );
			}
		}
		echo "\n";
	}

	private function test_viewmodel_classes_exist() {
		echo "Testing ViewModel Classes...\n";

		$viewmodel_files = array(
			'src/ViewModels/class-apollo-base-viewmodel.php',
			'src/ViewModels/class-apollo-event-viewmodel.php',
			'src/ViewModels/class-apollo-social-viewmodel.php',
			'src/ViewModels/class-apollo-user-viewmodel.php',
			'src/ViewModels/class-apollo-viewmodel-factory.php',
		);

		foreach ( $viewmodel_files as $file ) {
			$path = $this->base_path . '/' . $file;
			if ( file_exists( $path ) ) {
				$lines = count( file( $path ) );
				echo '✅ ' . basename( $file, '.php' ) . " exists ({$lines} lines)\n";
				$this->results[] = '✅ ' . basename( $file, '.php' ) . " exists ({$lines} lines)";
			} else {
				echo '❌ ' . basename( $file, '.php' ) . " missing\n";
				$this->results[] = '❌ ' . basename( $file, '.php' ) . ' missing';
			}
		}
		echo "\n";
	}

	private function test_migration_completeness() {
		echo "Testing Migration Completeness...\n";

		// Check for migration markers in templates.
		$templates_to_check = array(
			'../apollo-events-manager/templates/single-event_listing.php',
			'../apollo-social/templates/user-page-view.php',
		);

		foreach ( $templates_to_check as $template ) {
			$path = $this->base_path . '/' . $template;
			if ( file_exists( $path ) ) {
				$content       = file_get_contents( $path );
				$template_name = basename( $template );

				$migration_markers = array(
					'Phase 5 Migration Complete' => strpos( $content, 'Phase 5' ) !== false || strpos( $content, 'Migration Complete' ) !== false,
					'ViewModel Integration'      => strpos( $content, 'ViewModel' ) !== false,
					'Shared Partials'            => strpos( $content, 'load_partial' ) !== false,
					'Mobile Responsive'          => strpos( $content, 'mobile' ) !== false || strpos( $content, 'responsive' ) !== false,
				);

				foreach ( $migration_markers as $marker => $found ) {
					if ( $found ) {
						echo "✅ {$template_name}: {$marker}\n";
						$this->results[] = "✅ {$template_name}: {$marker}";
					} else {
						echo "⚠️ {$template_name}: {$marker} not found\n";
						$this->results[] = "⚠️ {$template_name}: {$marker} not found";
					}
				}
			}
		}
		echo "\n";
	}

	private function print_summary() {
		echo "📋 Validation Summary\n";
		echo "====================\n";

		$success_count = 0;
		$warning_count = 0;
		$error_count   = 0;

		foreach ( $this->results as $result ) {
			if ( strpos( $result, '✅' ) === 0 ) {
				++$success_count;
				echo $result . "\n";
			} elseif ( strpos( $result, '❌' ) === 0 ) {
				++$error_count;
				echo $result . "\n";
			} elseif ( strpos( $result, '⚠️' ) === 0 ) {
				++$warning_count;
				echo $result . "\n";
			}
		}

		echo "\n";
		echo "📊 Results: {$success_count} ✅, {$warning_count} ⚠️, {$error_count} ❌\n";

		if ( $error_count === 0 ) {
			echo "🎉 All critical validations passed!\n";
		} else {
			echo "⚠️ Some validations failed. Please review the errors above.\n";
		}
	}
}

// Run the validation.
$validator = new Apollo_Template_Static_Validator();
$validator->run_validation();
