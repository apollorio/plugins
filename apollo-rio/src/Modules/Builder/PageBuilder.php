<?php
/**
 * Page Builder
 *
 * Basic page building functionality
 *
 * @package Apollo_Rio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page Builder Class
 */
class Apollo_Page_Builder {

	/**
	 * Initialize builder
	 */
	public static function init() {
		add_shortcode( 'apollo_builder', array( __CLASS__, 'render_builder' ) );
	}

	/**
	 * Render builder content
	 */
	public static function render_builder( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => '',
			),
			$atts
		);

		ob_start();
		?>
		<div class="apollo-page-builder" data-id="<?php echo esc_attr( $atts['id'] ); ?>">
			<p>Page builder content will be rendered here.</p>
		</div>
		<?php
		return ob_get_clean();
	}
}

// Initialize
Apollo_Page_Builder::init();
