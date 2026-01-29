<?php
/**
 * Event Block Type
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

namespace Apollo_Social\Builder\Blocks\Types;

use Apollo_Social\Builder\Blocks\AbstractBlock;

/**
 * Class EventBlock
 *
 * Bloco de evento com dia/mês visual
 */
class EventBlock extends AbstractBlock {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->type = 'event';
		$this->name = 'Evento';
		$this->icon = 'calendar';
		$this->settings = array(
			array(
				'name'    => 'title',
				'type'    => 'text',
				'label'   => 'Nome do Evento',
				'default' => 'Meu Evento',
			),
			array(
				'name'    => 'day',
				'type'    => 'text',
				'label'   => 'Dia',
				'default' => '01',
			),
			array(
				'name'    => 'month',
				'type'    => 'text',
				'label'   => 'Mês',
				'default' => 'JAN',
			),
			array(
				'name'    => 'url',
				'type'    => 'url',
				'label'   => 'URL',
				'default' => '#',
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
		$title    = isset( $props['title'] ) ? esc_html( $props['title'] ) : 'Meu Evento';
		$day      = isset( $props['day'] ) ? esc_html( $props['day'] ) : '01';
		$month    = isset( $props['month'] ) ? esc_html( strtoupper( $props['month'] ) ) : 'JAN';
		$url      = isset( $props['url'] ) ? esc_url( $props['url'] ) : '#';
		$block_id = isset( $props['id'] ) ? $props['id'] : '';

		$primary_color = $this->get_profile_color( $profile, 'primary', '#000000' );

		$content = sprintf(
			'<a href="%s" class="hub-event__card" data-block-id="%s" target="_blank" rel="noopener">
				<div class="hub-event__date" style="background-color: %s;">
					<div class="hub-event__day">%s</div>
					<div class="hub-event__month">%s</div>
				</div>
				<div class="hub-event__content">
					<div class="hub-event__title">%s</div>
				</div>
			</a>',
			$url,
			esc_attr( $block_id ),
			esc_attr( $primary_color ),
			$day,
			$month,
			$title
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
			'title' => isset( $props['title'] ) ? $this->sanitize_text( $props['title'] ) : 'Meu Evento',
			'day'   => isset( $props['day'] ) ? $this->sanitize_text( $props['day'] ) : '01',
			'month' => isset( $props['month'] ) ? strtoupper( $this->sanitize_text( $props['month'] ) ) : 'JAN',
			'url'   => isset( $props['url'] ) ? $this->sanitize_url( $props['url'] ) : '#',
		);
	}
}
