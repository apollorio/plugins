<?php
/**
 * Block Interface for HUB::rio Linktree-style blocks
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

namespace Apollo_Social\Builder\Blocks;

/**
 * Interface BlockInterface
 *
 * Define os métodos obrigatórios para todos os blocos do HUB::rio
 */
interface BlockInterface {

	/**
	 * Get block type identifier
	 *
	 * @return string Block type (header|link|event|marquee|cards|divider|text|image|video|social|newsletter)
	 */
	public function get_type();

	/**
	 * Get human-readable block name
	 *
	 * @return string Display name for UI
	 */
	public function get_name();

	/**
	 * Get Apollo icon identifier
	 *
	 * @return string Icon name from https://cdn.apollo.rio.br/icons/
	 */
	public function get_icon();

	/**
	 * Get block settings schema
	 *
	 * @return array Array of setting definitions [{name, type, label, default}]
	 */
	public function get_settings();

	/**
	 * Render block HTML for public view
	 *
	 * @param array $props Block properties from JSON
	 * @param array $profile User profile data {name, bio, avatar, bg, primary, accent}
	 * @return string Rendered HTML
	 */
	public function render( $props, $profile );

	/**
	 * Validate block properties
	 *
	 * @param array $props Block properties to validate
	 * @return array Sanitized/validated properties
	 */
	public function validate( $props );
}
