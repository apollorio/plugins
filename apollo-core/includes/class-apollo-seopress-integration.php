<?php
/**
 * Apollo SEOPress Integration - Legacy Wrapper
 *
 * This file has been replaced by class-apollo-native-seo.php which provides
 * a fully self-contained SEO solution with NO external plugin dependencies.
 *
 * Features (all native, no external plugins):
 * - Meta tags (title, description)
 * - Open Graph for social sharing
 * - Twitter Cards
 * - Schema.org JSON-LD
 * - XML Sitemaps
 *
 * @package Apollo_Core
 * @since   1.0.0
 * @deprecated Use Apollo_Core\Native_SEO instead.
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SEOPress_Integration
 *
 * @deprecated This class is now a legacy wrapper. The actual implementation is in Native_SEO.
 *             This wrapper exists to maintain backward compatibility.
 */
class SEOPress_Integration {

	/**
	 * Initialize hooks.
	 *
	 * Redirects to native implementation.
	 */
	public static function init(): void {
		// Native_SEO is now the primary implementation.
		// No external plugins required.
		// This wrapper is kept for backward compatibility only.

		// Include native SEO if not already loaded.
		$native_seo_file = __DIR__ . '/class-apollo-native-seo.php';
		if ( file_exists( $native_seo_file ) && ! class_exists( 'Apollo_Core\Native_SEO' ) ) {
			require_once $native_seo_file;
		}

		// Note: Native_SEO::init() is called automatically at the end of its file.
	}

	/**
	 * Get SEO settings.
	 *
	 * Redirects to native implementation.
	 *
	 * @return array
	 */
	public static function get_settings(): array {
		if ( class_exists( 'Apollo_Core\Native_SEO' ) ) {
			return Native_SEO::get_settings();
		}

		return array();
	}

	/**
	 * Check if SEO is active.
	 *
	 * @return bool Always true - native SEO is always available.
	 */
	public static function is_active(): bool {
		return true;
	}
}

// Note: No init() call here - Native_SEO handles everything.
