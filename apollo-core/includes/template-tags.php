<?php

/**
 * Template helpers for Apollo Core.
 *
 * @package Apollo_Core
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'apollo_core_component' ) ) {
	/**
	 * Loads a design library component fragment.
	 *
	 * Components live in templates/design-library/_components and are rendered
	 * with the provided data array extracted into the local scope. Values are
	 * echoed by default but you can capture the markup by passing $echo = false.
	 *
	 * @param string $slug Component identifier (relative path inside _components).
	 * @param array  $data Optional associative array exposed to the component.
	 * @param bool   $echo Whether to echo (true) or just return the rendered HTML.
	 *
	 * @return string Rendered component markup.
	 */
	function apollo_core_component( string $slug, array $data = array(), bool $echo = true ): string {
		static $component_base = null;

		if ( '' === trim( $slug ) ) {
			return '';
		}

		if ( null === $component_base ) {
			$component_base = trailingslashit( APOLLO_CORE_PLUGIN_DIR ) . 'templates/design-library/_components/';
		}

		// Basic path hardening: prevent directory traversal or Windows backslashes.
		$safe_slug = str_replace( array( '..', '\\' ), '', $slug );
		$safe_slug = trim( $safe_slug, '/\\' );
		$file_path = $component_base . $safe_slug . '.php';

		if ( ! file_exists( $file_path ) ) {
			do_action( 'apollo_core_component_missing', $safe_slug, $file_path );

			return '';
		}

		$apollo_component_data = $data;
		if ( ! empty( $data ) ) {
			extract( $data, EXTR_SKIP );
		}

		ob_start();
		include $file_path;
		$markup = (string) ob_get_clean();

		if ( $echo ) {
			echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		return $markup;
	}
}
