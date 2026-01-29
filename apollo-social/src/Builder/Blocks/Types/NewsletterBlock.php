<?php
/**
 * Newsletter Block Type
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

namespace Apollo_Social\Builder\Blocks\Types;

use Apollo_Social\Builder\Blocks\AbstractBlock;

/**
 * Class NewsletterBlock
 *
 * Formulário de inscrição em newsletter
 */
class NewsletterBlock extends AbstractBlock {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->type = 'newsletter';
		$this->name = 'Newsletter';
		$this->icon = 'mail';
		$this->settings = array(
			array(
				'name'    => 'title',
				'type'    => 'text',
				'label'   => 'Título',
				'default' => 'Receba novidades',
			),
			array(
				'name'    => 'placeholder',
				'type'    => 'text',
				'label'   => 'Placeholder',
				'default' => 'Seu e-mail',
			),
			array(
				'name'    => 'button',
				'type'    => 'text',
				'label'   => 'Texto do Botão',
				'default' => 'Inscrever',
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
		$title       = isset( $props['title'] ) ? esc_html( $props['title'] ) : 'Receba novidades';
		$placeholder = isset( $props['placeholder'] ) ? esc_attr( $props['placeholder'] ) : 'Seu e-mail';
		$button      = isset( $props['button'] ) ? esc_html( $props['button'] ) : 'Inscrever';
		$block_id    = isset( $props['id'] ) ? $props['id'] : '';

		$primary_color = $this->get_profile_color( $profile, 'primary', '#000000' );

		$content = sprintf(
			'<div class="hub-newsletter__container">
				<h4 class="hub-newsletter__title">%s</h4>
				<form class="hub-newsletter__form" data-block-id="%s">
					<input
						type="email"
						class="hub-newsletter__input"
						placeholder="%s"
						required
					/>
					<button
						type="submit"
						class="hub-newsletter__button"
						style="background-color: %s;"
					>%s</button>
				</form>
			</div>',
			$title,
			esc_attr( $block_id ),
			$placeholder,
			esc_attr( $primary_color ),
			$button
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
			'title'       => isset( $props['title'] ) ? $this->sanitize_text( $props['title'] ) : 'Receba novidades',
			'placeholder' => isset( $props['placeholder'] ) ? $this->sanitize_text( $props['placeholder'] ) : 'Seu e-mail',
			'button'      => isset( $props['button'] ) ? $this->sanitize_text( $props['button'] ) : 'Inscrever',
		);
	}
}
