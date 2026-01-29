<?php
/**
 * Divider Block Type
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

namespace Apollo_Social\Builder\Blocks\Types;

use Apollo_Social\Builder\Blocks\AbstractBlock;

/**
 * Class DividerBlock
 *
 * Separador visual simples
 */
class DividerBlock extends AbstractBlock {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->type = 'divider';
		$this->name = 'Divisor';
		$this->icon = 'minus';
		$this->settings = array();
	}

	/**
	 * Render block
	 *
	 * @param array $props Block properties
	 * @param array $profile Profile data
	 * @return string
	 */
	public function render( $props, $profile ) {
		$block_id = isset( $props['id'] ) ? $props['id'] : '';

		$content = '<hr class="hub-divider__line" />';

		return $this->render_wrapper( $content, $block_id );
	}

	/**
	 * Validate properties
	 *
	 * @param array $props Properties to validate
	 * @return array
	 */
	public function validate( $props ) {
		return array();
	}
}
