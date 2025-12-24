<?php

declare(strict_types=1);

namespace Apollo_Core;

/**
 * Canvas Template Loader
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canvas Loader class
 */
class Canvas_Loader {

	/**
	 * Initialize
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter( 'template_include', array( $this, 'load_canvas_template' ), 99 );
	}

	/**
	 * Load canvas template for pages with _apollo_canvas meta
	 *
	 * @param string $template Template path.
	 * @return string
	 */
	public function load_canvas_template( string $template ): string {
		if ( ! is_singular() ) {
			return $template;
		}

		global $post;

		if ( ! $post ) {
			return $template;
		}

		// Check if page has canvas meta.
		$is_canvas = get_post_meta( $post->ID, '_apollo_canvas', true );

		if ( ! $is_canvas ) {
			return $template;
		}

		// Get canvas template.
		$canvas_template = $this->get_canvas_template();

		if ( ! $canvas_template ) {
			return $template;
		}

		return $canvas_template;
	}

	/**
	 * Get canvas template path
	 *
	 * @return string|bool
	 */
	private function get_canvas_template(): string|bool {
		$template_path = APOLLO_CORE_PLUGIN_DIR . 'templates/canvas.php';

		if ( file_exists( $template_path ) ) {
			return $template_path;
		}

		return false;
	}

	/**
	 * Render canvas template with data
	 *
	 * @param string $template_slug Template slug.
	 * @param array  $data Template data.
	 * @return void
	 */
	public static function render( string $template_slug, array $data = array() ): void {
		// Locate template.
		$template_path = self::locate_template( $template_slug );

		if ( ! $template_path ) {
			return;
		}

		// Extract data to variables.
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $data );

		// Include template.
		include $template_path;
	}

	/**
	 * Locate template
	 *
	 * @param string $template_slug Template slug.
	 * @return string|bool
	 */
	private static function locate_template( string $template_slug ): string|bool {
		$template_slug = ltrim( $template_slug, '/' );

		// Check in theme.
		$theme_template = locate_template(
			array(
				'apollo-core/' . $template_slug,
				$template_slug,
			)
		);

		if ( $theme_template ) {
			return $theme_template;
		}

		// Check in plugin.
		$plugin_template = APOLLO_CORE_PLUGIN_DIR . 'templates/' . $template_slug;

		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		return false;
	}
}
