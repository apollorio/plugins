<?php
/**
 * Social Block Type
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

namespace Apollo_Social\Builder\Blocks\Types;

use Apollo_Social\Builder\Blocks\AbstractBlock;

/**
 * Class SocialBlock
 *
 * Linha de ícones sociais
 */
class SocialBlock extends AbstractBlock {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->type = 'social';
		$this->name = 'Redes Sociais';
		$this->icon = 'share-2';
		$this->settings = array(
			array(
				'name'    => 'icons',
				'type'    => 'array',
				'label'   => 'Ícones Sociais',
				'default' => array(
					array( 'icon' => 'instagram', 'url' => '#' ),
					array( 'icon' => 'twitter', 'url' => '#' ),
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
		$icons    = isset( $props['icons'] ) && is_array( $props['icons'] ) ? $props['icons'] : array();
		$block_id = isset( $props['id'] ) ? $props['id'] : '';

		if ( empty( $icons ) ) {
			return '';
		}

		$primary_color = $this->get_profile_color( $profile, 'primary', '#000000' );

		$icons_html = '';
		foreach ( $icons as $social ) {
			$icon = isset( $social['icon'] ) ? esc_attr( $social['icon'] ) : '';
			$url  = isset( $social['url'] ) ? esc_url( $social['url'] ) : '#';

			if ( empty( $icon ) ) {
				continue;
			}

			$icons_html .= sprintf(
				'<a href="%s" class="hub-social__link" target="_blank" rel="noopener">
					<div class="hub-social__icon" style="background-image: url(https://cdn.apollo.rio.br/icons/%s.svg); -webkit-mask-image: url(https://cdn.apollo.rio.br/icons/%s.svg); mask-image: url(https://cdn.apollo.rio.br/icons/%s.svg); background-color: %s;"></div>
				</a>',
				$url,
				$icon,
				$icon,
				$icon,
				esc_attr( $primary_color )
			);
		}

		$content = sprintf(
			'<div class="hub-social__container">%s</div>',
			$icons_html
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
		$icons = isset( $props['icons'] ) && is_array( $props['icons'] ) ? $props['icons'] : array();
		$validated_icons = array();

		foreach ( $icons as $social ) {
			if ( ! is_array( $social ) ) {
				continue;
			}

			$validated_icons[] = array(
				'icon' => isset( $social['icon'] ) ? $this->sanitize_text( $social['icon'] ) : '',
				'url'  => isset( $social['url'] ) ? $this->sanitize_url( $social['url'] ) : '#',
			);
		}

		return array(
			'icons' => $validated_icons,
		);
	}
}
