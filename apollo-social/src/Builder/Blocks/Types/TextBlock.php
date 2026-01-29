<?php
/**
 * Text Block Type
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

namespace Apollo_Social\Builder\Blocks\Types;

use Apollo_Social\Builder\Blocks\AbstractBlock;

/**
 * Class TextBlock
 *
 * Parágrafo de texto
 */
class TextBlock extends AbstractBlock {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->type = 'text';
		$this->name = 'Texto';
		$this->icon = 'text';
		$this->settings = array(
			array(
				'name'    => 'text',
				'type'    => 'textarea',
				'label'   => 'Conteúdo',
				'default' => 'Adicione seu texto aqui.',
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
		$text     = isset( $props['text'] ) ? wpautop( wp_kses_post( $props['text'] ) ) : '<p>Adicione seu texto aqui.</p>';
		$block_id = isset( $props['id'] ) ? $props['id'] : '';

		$content = sprintf(
			'<div class="hub-text__content">%s</div>',
			$text
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
			'text' => isset( $props['text'] ) ? wp_kses_post( $props['text'] ) : 'Adicione seu texto aqui.',
		);
	}
}
