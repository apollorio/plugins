<?php
/**
 * Marquee Block Type
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

namespace Apollo_Social\Builder\Blocks\Types;

use Apollo_Social\Builder\Blocks\AbstractBlock;

/**
 * Class MarqueeBlock
 *
 * Banner animado com texto scrollante
 */
class MarqueeBlock extends AbstractBlock {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->type = 'marquee';
		$this->name = 'Banner Animado';
		$this->icon = 'text-cursor';
		$this->settings = array(
			array(
				'name'    => 'text',
				'type'    => 'text',
				'label'   => 'Texto do Banner',
				'default' => 'Bem-vindo ao meu perfil',
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
		$text     = isset( $props['text'] ) ? esc_html( $props['text'] ) : 'Bem-vindo ao meu perfil';
		$block_id = isset( $props['id'] ) ? $props['id'] : '';

		$accent_color = $this->get_profile_color( $profile, 'accent', '#ff0000' );

		// Duplicar texto para animação contínua
		$repeated_text = str_repeat( $text . ' • ', 10 );

		$content = sprintf(
			'<div class="hub-marquee__container" style="background-color: %s;">
				<div class="hub-marquee__text">%s</div>
			</div>',
			esc_attr( $accent_color ),
			$repeated_text
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
			'text' => isset( $props['text'] ) ? $this->sanitize_text( $props['text'] ) : 'Bem-vindo ao meu perfil',
		);
	}
}
