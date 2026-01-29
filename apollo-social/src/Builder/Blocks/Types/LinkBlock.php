<?php
/**
 * Link Block Type
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

namespace Apollo_Social\Builder\Blocks\Types;

use Apollo_Social\Builder\Blocks\AbstractBlock;

/**
 * Class LinkBlock
 *
 * Card clicável com ícone Apollo
 */
class LinkBlock extends AbstractBlock {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->type = 'link';
		$this->name = 'Link Card';
		$this->icon = 'link';
		$this->settings = array(
			array(
				'name'    => 'title',
				'type'    => 'text',
				'label'   => 'Título',
				'default' => 'Meu Link',
			),
			array(
				'name'    => 'sub',
				'type'    => 'text',
				'label'   => 'Subtítulo',
				'default' => '',
			),
			array(
				'name'    => 'url',
				'type'    => 'url',
				'label'   => 'URL',
				'default' => '#',
			),
			array(
				'name'    => 'icon',
				'type'    => 'icon',
				'label'   => 'Ícone Apollo',
				'default' => 'link',
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
		$title    = isset( $props['title'] ) ? esc_html( $props['title'] ) : 'Meu Link';
		$sub      = isset( $props['sub'] ) ? esc_html( $props['sub'] ) : '';
		$url      = isset( $props['url'] ) ? esc_url( $props['url'] ) : '#';
		$icon     = isset( $props['icon'] ) ? esc_attr( $props['icon'] ) : 'link';
		$block_id = isset( $props['id'] ) ? $props['id'] : '';

		$primary_color = $this->get_profile_color( $profile, 'primary', '#000000' );

		$content = sprintf(
			'<a href="%s" class="hub-link__card" data-block-id="%s" target="_blank" rel="noopener">
				<div class="hub-link__icon" style="background-image: url(https://cdn.apollo.rio.br/icons/%s.svg); -webkit-mask-image: url(https://cdn.apollo.rio.br/icons/%s.svg); mask-image: url(https://cdn.apollo.rio.br/icons/%s.svg); background-color: %s;"></div>
				<div class="hub-link__content">
					<div class="hub-link__title">%s</div>
					%s
				</div>
			</a>',
			$url,
			esc_attr( $block_id ),
			$icon,
			$icon,
			$icon,
			esc_attr( $primary_color ),
			$title,
			! empty( $sub ) ? sprintf( '<div class="hub-link__sub">%s</div>', $sub ) : ''
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
			'title' => isset( $props['title'] ) ? $this->sanitize_text( $props['title'] ) : 'Meu Link',
			'sub'   => isset( $props['sub'] ) ? $this->sanitize_text( $props['sub'] ) : '',
			'url'   => isset( $props['url'] ) ? $this->sanitize_url( $props['url'] ) : '#',
			'icon'  => isset( $props['icon'] ) ? $this->sanitize_text( $props['icon'] ) : 'link',
		);
	}
}
