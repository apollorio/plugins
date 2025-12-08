<?php
/**
 * Enqueue dos assets do editor (Tailwind, Muuri, shadcn, uni.css)
 */
class Apollo_User_Page_Editor_Assets {
	public static function enqueue() {
		if ( is_page_template( 'user-page-editor.php' ) ) {
			wp_enqueue_style( 'apollo-uni', 'https://assets.apollo.rio.br/uni.css' );
			wp_enqueue_style( 'apollo-tailwind', 'https://cdn.tailwindcss.com' );
			// Adicionar bundle local de tokens shadcn e Muuri
			wp_enqueue_script( 'apollo-muuri', 'https://cdn.jsdelivr.net/npm/muuri@0.9.6/dist/muuri.min.js', [], null, true );
			wp_enqueue_script( 'apollo-editor', plugins_url( 'user-pages/editor-bundle.js', __FILE__ ), [], null, true );
		}
	}
}
add_action( 'wp_enqueue_scripts', [ 'Apollo_User_Page_Editor_Assets', 'enqueue' ] );
