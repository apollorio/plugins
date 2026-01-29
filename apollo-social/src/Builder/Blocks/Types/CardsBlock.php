<?php
/**
 * Cards Block Type
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

namespace Apollo_Social\Builder\Blocks\Types;

use Apollo_Social\Builder\Blocks\AbstractBlock;

/**
 * Class CardsBlock
 *
 * Grid de 2 colunas com imagens
 */
class CardsBlock extends AbstractBlock {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->type = 'cards';
		$this->name = 'Grid de Cards';
		$this->icon = 'grid-2x2';
		$this->settings = array(
			array(
				'name'    => 'cards',
				'type'    => 'array',
				'label'   => 'Cards',
				'default' => array(
					array( 'title' => 'Card 1', 'img' => '' ),
					array( 'title' => 'Card 2', 'img' => '' ),
				),
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
		$cards    = isset( $props['cards'] ) && is_array( $props['cards'] ) ? $props['cards'] : array();
		$block_id = isset( $props['id'] ) ? $props['id'] : '';

		if ( empty( $cards ) ) {
			return '';
		}

		$cards_html = '';
		foreach ( $cards as $card ) {
			$title = isset( $card['title'] ) ? esc_html( $card['title'] ) : '';
			$img   = isset( $card['img'] ) ? esc_url( $card['img'] ) : '';

			$cards_html .= sprintf(
				'<div class="hub-cards__item">
					%s
					<div class="hub-cards__title">%s</div>
				</div>',
				! empty( $img ) ? sprintf( '<img src="%s" alt="%s" class="hub-cards__img" loading="lazy" />', $img, $title ) : '',
				$title
			);
		}

		$content = sprintf(
			'<div class="hub-cards__grid">%s</div>',
			$cards_html
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
		$cards = isset( $props['cards'] ) && is_array( $props['cards'] ) ? $props['cards'] : array();
		$validated_cards = array();

		foreach ( $cards as $card ) {
			if ( ! is_array( $card ) ) {
				continue;
			}

			$validated_cards[] = array(
				'title' => isset( $card['title'] ) ? $this->sanitize_text( $card['title'] ) : '',
				'img'   => isset( $card['img'] ) ? $this->sanitize_url( $card['img'] ) : '',
			);
		}

		return array(
			'cards' => $validated_cards,
		);
	}
}
