<?php
/**
 * Header Block Type
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

namespace Apollo_Social\Builder\Blocks\Types;

use Apollo_Social\Builder\Blocks\AbstractBlock;

/**
 * Class HeaderBlock
 *
 * Renderiza cabeçalho de seção em uppercase
 */
class HeaderBlock extends AbstractBlock {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->type = 'header';
		$this->name = 'Cabeçalho de Seção';
		$this->icon = 'text-header';
		$this->settings = array(
			array(
				'name'    => 'text',
				'type'    => 'text',
				'label'   => 'Texto do Cabeçalho',
				'default' => 'SEÇÃO',
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
		$text = isset( $props['text'] ) ? esc_html( $props['text'] ) : 'SEÇÃO';
		$block_id = isset( $props['id'] ) ? $props['id'] : '';

		$content = sprintf(
			'<h3 class="hub-header__text">%s</h3>',
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
			'text' => isset( $props['text'] ) ? $this->sanitize_text( $props['text'] ) : 'SEÇÃO',
		);
	}
}
