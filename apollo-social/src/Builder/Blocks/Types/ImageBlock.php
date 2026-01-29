<?php
/**
 * Image Block Type
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

namespace Apollo_Social\Builder\Blocks\Types;

use Apollo_Social\Builder\Blocks\AbstractBlock;

/**
 * Class ImageBlock
 *
 * Imagem com caption opcional
 */
class ImageBlock extends AbstractBlock {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->type = 'image';
		$this->name = 'Imagem';
		$this->icon = 'image';
		$this->settings = array(
			array(
				'name'    => 'img',
				'type'    => 'image',
				'label'   => 'URL da Imagem',
				'default' => '',
			),
			array(
				'name'    => 'caption',
				'type'    => 'text',
				'label'   => 'Legenda',
				'default' => '',
			),
		);
	}

	/**
	 * Render block
	 *
	 * @param array $props Block properties
	 * @param array $profile Profile data
	 * @return string
	 */
	public function render( $props, $profile ) {
		$img      = isset( $props['img'] ) ? esc_url( $props['img'] ) : '';
		$caption  = isset( $props['caption'] ) ? esc_html( $props['caption'] ) : '';
		$block_id = isset( $props['id'] ) ? $props['id'] : '';

		if ( empty( $img ) ) {
			return '';
		}

		$content = sprintf(
			'<figure class="hub-image__figure">
				<img src="%s" alt="%s" class="hub-image__img" loading="lazy" />
				%s
			</figure>',
			$img,
			$caption,
			! empty( $caption ) ? sprintf( '<figcaption class="hub-image__caption">%s</figcaption>', $caption ) : ''
		);

		return $this->render_wrapper( $content, $block_id );
	}

	/**
	 * Validate properties
	 *
	 * @param array $props Properties to validate
	 * @return array
	 */
	public function validate( $props ) {
		return array(
			'img'     => isset( $props['img'] ) ? $this->sanitize_url( $props['img'] ) : '',
			'caption' => isset( $props['caption'] ) ? $this->sanitize_text( $props['caption'] ) : '',
		);
	}
}
