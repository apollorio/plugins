<?php

/**
 * Enqueue dos assets do editor
 * STRICT MODE: base.js handles uni.css and core assets automatically
 */
class Apollo_User_Page_Editor_Assets {

	public static function enqueue() {
		if ( is_page_template( 'user-page-editor.php' ) ) {
			// Ensure base assets are loaded (handles uni.css)
			if ( function_exists( 'apollo_ensure_base_assets' ) ) {
				apollo_ensure_base_assets();
			}
			// Additional editor-specific assets only
			wp_enqueue_script( 'apollo-muuri', 'https://cdn.jsdelivr.net/npm/muuri@0.9.6/dist/muuri.min.js', array(), null, true );
			wp_enqueue_script( 'apollo-editor', plugins_url( 'user-pages/editor-bundle.js', __FILE__ ), array(), null, true );
		}
	}
}
add_action( 'wp_enqueue_scripts', array( 'Apollo_User_Page_Editor_Assets', 'enqueue' ) );
