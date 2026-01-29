<?php

/**
 * Apollo Widget Base Class
 *
 * Abstract class for all Apollo Builder widgets.
 *
 * Pattern source: WOW Page Builder addon class structure
 * - get_name() - unique identifier
 * - get_title() - display name
 * - get_icon() - icon class
 * - get_settings() - config fields
 * - render($data) - output HTML
 *
 * @package Apollo_Social
 * @since 1.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abstract Class Apollo_Widget_Base
 */
abstract class Apollo_Widget_Base {

	/**
	 * Get widget unique name (slug)
	 * Pattern: WOW get_name()
	 * Tooltip: Unique identifier used in layout JSON
	 *
	 * @return string Widget name (e.g., 'profile-card')
	 */
	abstract public function get_name();

	/**
	 * Get widget display title
	 * Pattern: WOW get_title()
	 * Tooltip: Human-readable name shown in sidebar
	 *
	 * @return string Widget title (e.g., 'Profile Card')
	 */
	abstract public function get_title();

	/**
	 * Get widget icon class
	 * Pattern: WOW get_icon()
	 * Tooltip: CSS class for icon (Dashicon or custom)
	 *
	 * @return string Icon class (e.g., 'dashicons-admin-users')
	 */
	public function get_icon() {
		return 'dashicons-admin-generic';
	}

	/**
	 * Get widget description
	 * Tooltip: Brief description shown in widget info
	 *
	 * @return string
	 */
	public function get_description() {
		return '';
	}

	/**
	 * Get widget tooltip (hover help)
	 * Tooltip: Extended help shown on hover
	 *
	 * @return string
	 */
	public function get_tooltip() {
		return $this->get_description();
	}

	/**
	 * Get widget settings fields
	 * Pattern: WOW get_settings()
	 *
	 * Tooltip: Array defining configurable options
	 *
	 * @return array Array of setting fields
	 */
	public function get_settings() {
		return array();
	}

	/**
	 * Get default values for settings
	 *
	 * @return array
	 */
	public function get_defaults() {
		$defaults = array();

		foreach ( $this->get_settings() as $key => $field ) {
			if ( isset( $field['std'] ) ) {
				$defaults[ $key ] = $field['std'];
			}
		}

		return $defaults;
	}

	/**
	 * Can this widget be deleted?
	 * Tooltip: Some widgets like profile-card are always present
	 *
	 * @return bool
	 */
	public function can_delete() {
		return true;
	}

	/**
	 * Maximum instances of this widget
	 * Tooltip: -1 = unlimited
	 *
	 * @return int
	 */
	public function get_max_instances() {
		return -1;
	}

	/**
	 * Default width in pixels
	 * Tooltip: Initial width when added to canvas
	 *
	 * @return int
	 */
	public function get_default_width() {
		return 200;
	}

	/**
	 * Default height in pixels
	 * Tooltip: Initial height when added to canvas
	 *
	 * @return int
	 */
	public function get_default_height() {
		return 150;
	}

	/**
	 * Render widget HTML output
	 * Pattern: WOW render($data)
	 *
	 * Tooltip: Returns HTML for frontend display
	 *
	 * @param array $data Widget data including settings, position, post_id
	 * @return string HTML output
	 */
	abstract public function render( $data );

	/**
	 * Get editor template (for live preview in JS)
	 * Pattern: WOW getTemplate()
	 *
	 * Tooltip: JS template for builder preview
	 *
	 * @return string JS template string
	 */
	public function get_editor_template() {
		return '';
	}

	/**
	 * Helper: Get post author (user) data
	 *
	 * @param int $post_id
	 * @return WP_User|null
	 */
	protected function get_post_author( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return null;
		}

		return get_userdata( $post->post_author );
	}

	/**
	 * Helper: Escape output (security)
	 * Tooltip: Always escape user-generated content
	 *
	 * @param string $text
	 * @param string $context esc_html, esc_attr, esc_url
	 * @return string
	 */
	protected function esc( $text, $context = 'html' ) {
		switch ( $context ) {
			case 'attr':
				return esc_attr( $text );
			case 'url':
				return esc_url( $text );
			case 'html':
			default:
				return esc_html( $text );
		}
	}

	/**
	 * Helper: Build setting field array
	 * Pattern: WOW settings structure
	 *
	 * @param string $type Field type: text, switch, color, slider, select, image
	 * @param string $title Field label
	 * @param mixed  $default Default value
	 * @param array  $extra Extra options
	 * @return array
	 */
	protected function field( $type, $title, $default = '', $extra = array() ) {
		return array_merge(
			array(
				'type'  => $type,
				'title' => $title,
				'std'   => $default,
			),
			$extra
		);
	}
}
