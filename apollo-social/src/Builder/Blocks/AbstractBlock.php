<?php
/**
 * Abstract Block Base Class
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

namespace Apollo_Social\Builder\Blocks;

/**
 * Class AbstractBlock
 *
 * Implementação base para todos os blocos
 */
abstract class AbstractBlock implements BlockInterface {

	/**
	 * Block type identifier
	 *
	 * @var string
	 */
	protected $type = '';

	/**
	 * Block display name
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Apollo icon name
	 *
	 * @var string
	 */
	protected $icon = '';

	/**
	 * Block settings schema
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Get block name
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Get block icon
	 *
	 * @return string
	 */
	public function get_icon() {
		return $this->icon;
	}

	/**
	 * Get block settings
	 *
	 * @return array
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Sanitize string
	 *
	 * @param string $value Value to sanitize
	 * @return string
	 */
	protected function sanitize_text( $value ) {
		return sanitize_text_field( $value );
	}

	/**
	 * Sanitize URL
	 *
	 * @param string $value Value to sanitize
	 * @return string
	 */
	protected function sanitize_url( $value ) {
		return esc_url_raw( $value );
	}

	/**
	 * Sanitize color hex
	 *
	 * @param string $value Value to sanitize
	 * @return string
	 */
	protected function sanitize_color( $value ) {
		return sanitize_hex_color( $value );
	}

	/**
	 * Render block wrapper
	 *
	 * @param string $content Inner HTML
	 * @param string $block_id Block unique ID
	 * @param array $extra_classes Additional CSS classes
	 * @return string
	 */
	protected function render_wrapper( $content, $block_id, $extra_classes = array() ) {
		$classes = array_merge( array( 'hub-block', 'hub-block--' . $this->type ), $extra_classes );
		$class_string = esc_attr( implode( ' ', $classes ) );

		return sprintf(
			'<div class="%s" data-block-id="%s" data-block-type="%s">%s</div>',
			$class_string,
			esc_attr( $block_id ),
			esc_attr( $this->type ),
			$content
		);
	}

	/**
	 * Get profile color with fallback
	 *
	 * @param array $profile Profile data
	 * @param string $key Color key (primary|accent)
	 * @param string $default Default color
	 * @return string
	 */
	protected function get_profile_color( $profile, $key, $default = '#000000' ) {
		return isset( $profile[ $key ] ) && ! empty( $profile[ $key ] ) ? $profile[ $key ] : $default;
	}
}
