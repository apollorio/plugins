<?php
/**
 * Widget Canvas Plano - Reuso do editor Fabric.js
 * Abre modal do editor, gera imagem e insere no layout
 */
class Apollo_User_Page_Widget_Canvas {
	public static function register() {
		add_filter( 'apollo_userpage_widgets', array( __CLASS__, 'add_widget' ) );
	}

	public static function add_widget( $widgets ) {
		$widgets['canvas_plano'] = array(
			'title'       => 'Canvas Plano',
			'icon'        => 'image-edit',
			'propsSchema' => array(
				'image_id'  => 'integer',
				'image_url' => 'string',
			),
			'render'      => function ( $props, $ctx ) {
				$image_url = $props['image_url'] ?? '';
				if ( ! $image_url && ! empty( $props['image_id'] ) ) {
					$image_url = wp_get_attachment_image_url( $props['image_id'], 'large' );
				}

				if ( ! $image_url ) {
					return '<div class="card p-4 bg-card border rounded-lg text-center text-muted-foreground">Imagem do Canvas não configurada</div>';
				}

				return '<div class="card bg-card border rounded-lg overflow-hidden">'
					. '<img src="' . esc_url( $image_url ) . '" alt="Canvas Plano" class="w-full h-auto">'
					. '</div>';
			},
		);

		return $widgets;
	}
}
Apollo_User_Page_Widget_Canvas::register();
