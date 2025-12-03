<?php
/**
 * Apollo Widget: Sticker
 *
 * Admin-controlled stickers from library.
 * User cannot upload, only select from available stickers.
 *
 * @package Apollo_Social
 * @since 1.4.0
 */

defined( 'ABSPATH' ) || exit;

class Apollo_Widget_Sticker extends Apollo_Widget_Base {

	public function get_name() {
		return 'sticker';
	}

	public function get_title() {
		return __( 'Sticker', 'apollo-social' );
	}

	public function get_icon() {
		return 'dashicons-smiley';
	}

	public function get_description() {
		return __( 'Decorate with stickers from our library.', 'apollo-social' );
	}

	public function get_tooltip() {
		return __( 'Stickers are admin-controlled. Choose from available stickers - no uploads allowed.', 'apollo-social' );
	}

	public function get_default_width() {
		return 80;
	}

	public function get_default_height() {
		return 80;
	}

	/**
	 * Max 20 stickers per home
	 */
	public function get_max_instances() {
		return 20;
	}

	/**
	 * Settings
	 */
	public function get_settings() {
		return array(
			'stickerId' => $this->field(
				'select',
				__( 'Sticker', 'apollo-social' ),
				'',
				array(
					'options' => array(), 
					// Populated dynamically from apollo_builder_stickers option
															'dynamic' => true,
				)
			),
			'rotation'  => $this->field(
				'slider',
				__( 'Rotation', 'apollo-social' ),
				0,
				array(
					'min'  => -180,
					'max'  => 180,
					'unit' => '°',
				)
			),
			'flip'      => $this->field( 'switch', __( 'Flip Horizontal', 'apollo-social' ), false ),
		);
	}

	/**
	 * Render widget
	 *
	 * Data source: apollo_builder_stickers option (admin managed)
	 */
	public function render( $data ) {
		$settings = $data['settings'] ?? array();

		$sticker_id = sanitize_key( $settings['stickerId'] ?? '' );
		$rotation   = intval( $settings['rotation'] ?? 0 );
		$flip       = ! empty( $settings['flip'] );

		if ( empty( $sticker_id ) ) {
			return '<div class="apollo-widget-sticker apollo-widget-empty">'
				. '<span class="dashicons dashicons-smiley"></span>'
				. '</div>';
		}

		// Get sticker from library
		$stickers     = get_option( 'apollo_builder_stickers', array() );
		$sticker_data = null;

		foreach ( $stickers as $s ) {
			if ( isset( $s['id'] ) && $s['id'] === $sticker_id ) {
				$sticker_data = $s;
				break;
			}
		}

		if ( ! $sticker_data || empty( $sticker_data['image_id'] ) ) {
			return '<div class="apollo-widget-sticker apollo-widget-error">'
				. __( 'Sticker not found', 'apollo-social' )
				. '</div>';
		}

		$image_url = wp_get_attachment_image_url( $sticker_data['image_id'], 'medium' );
		$label     = $sticker_data['label'] ?? '';

		// Build transform
		$transform = array();
		if ( $rotation ) {
			$transform[] = 'rotate(' . $rotation . 'deg)';
		}
		if ( $flip ) {
			$transform[] = 'scaleX(-1)';
		}
		$transform_css = $transform ? 'transform:' . implode( ' ', $transform ) . ';' : '';

		ob_start();
		?>
		<div class="apollo-widget-sticker" title="<?php echo $this->esc( $label, 'attr' ); ?>">
			<img src="<?php echo $this->esc( $image_url, 'url' ); ?>" 
				alt="<?php echo $this->esc( $label, 'attr' ); ?>"
				class="sticker-image"
				style="<?php echo esc_attr( $transform_css ); ?>"
				loading="lazy"
				draggable="false">
		</div>
		<?php
		return ob_get_clean();
	}
}

