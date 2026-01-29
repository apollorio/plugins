<?php
/**
 * Template: HUB::rio - Apollo Linktree Builder
 *
 * Full-screen Linktree-style editor with mobile preview, drag-and-drop blocks.
 * Replaces old widget/canvas system with modern row-based block editor.
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

defined( 'ABSPATH' ) || exit;

// Suppress deprecation warnings for this admin page
error_reporting( E_ALL & ~E_USER_DEPRECATED );

// Security: Must be logged in and have capability
if ( ! is_user_logged_in() || ! current_user_can( APOLLO_BUILDER_CAPABILITY ) ) {
	wp_die( __( 'Access denied', 'apollo-social' ), __( 'Error', 'apollo-social' ), array( 'response' => 403 ) );
}

/**
 * Block ALL theme assets in builder template
 */
function apollo_builder_block_theme_assets() {
	global $wp_styles, $wp_scripts;

	$theme_uri       = get_template_directory_uri();
	$child_theme_uri = get_stylesheet_directory_uri();

	// Remove ALL theme styles
	if ( is_object( $wp_styles ) ) {
		foreach ( $wp_styles->registered as $handle => $style ) {
			$src = $style->src ?? '';
			if ( strpos( $src, $theme_uri ) !== false || strpos( $src, $child_theme_uri ) !== false ) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}
		}
		// Remove common theme handles
		$theme_handles = array( 'theme-style', 'style', 'main-style', 'theme-css', 'custom-style', 'twentytwentyfive-style' );
		foreach ( $theme_handles as $handle ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}
	}

	// Remove ALL theme scripts
	if ( is_object( $wp_scripts ) ) {
		foreach ( $wp_scripts->registered as $handle => $script ) {
			$src = $script->src ?? '';
			if ( strpos( $src, $theme_uri ) !== false || strpos( $src, $child_theme_uri ) !== false ) {
				wp_dequeue_script( $handle );
				wp_deregister_script( $handle );
			}
		}
	}
}

// Call immediately
apollo_builder_block_theme_assets();

$user_id = get_current_user_id();

// Enqueue HUB editor JavaScript
wp_enqueue_script(
	'apollo-hub-editor',
	plugins_url( 'assets/js/apollo-hub-editor.js', dirname( __FILE__ ) ),
	array(),
	APOLLO_SOCIAL_VERSION,
	true
);

// Localize script
wp_localize_script(
	'apollo-hub-editor',
	'apolloHubVars',
	array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'apollo_hub_editor' ),
		'userId'  => $user_id,
	)
);

// Include template parts
include plugin_dir_path( __FILE__ ) . 'linktree/hub-head.php';
?>

<body class="apollo-builder-page">

  <!-- Mobile Preview Toggle Button -->
  <div class="mobile-preview-controls">
    <button class="preview-toggle-btn" id="mobilePreviewBtn" title="Preview Mobile">
      <svg class="preview-icon preview-icon-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M21 3C21.5523 3 22 3.44772 22 4V20C22 20.5523 21.5523 21 21 21H3C2.44772 21 2 20.5523 2 20V4C2 3.44772 2.44772 3 3 3H21ZM18 12H16V15H13V17H18V12ZM11 7H6V12H8V9H11V7Z"></path></svg>
      <svg class="preview-icon preview-icon-close" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="display:none;"><path d="M5.63611 12.7071L7.46454 14.5355L8.87875 13.1213L7.05033 11.2929L8.46454 9.87869L10.293 11.7071L11.7072 10.2929L9.87875 8.46448L11.293 7.05026L13.1214 8.87869L14.5356 7.46448L12.7072 5.63605L15.5356 2.80762C15.9261 2.4171 16.5593 2.4171 16.9498 2.80762L21.1925 7.05026C21.583 7.44079 21.583 8.07395 21.1925 8.46448L8.46454 21.1924C8.07401 21.5829 7.44085 21.5829 7.05033 21.1924L2.80768 16.9498C2.41716 16.5592 2.41716 15.9261 2.80768 15.5355L5.63611 12.7071ZM14.1214 18.3635L18.364 14.1208L20.9997 16.7565V20.9999H16.7578L14.1214 18.3635ZM5.63597 9.87806L2.80754 7.04963C2.41702 6.65911 2.41702 6.02594 2.80754 5.63542L5.63597 2.80699C6.02649 2.41647 6.65966 2.41647 7.05018 2.80699L9.87861 5.63542L5.63597 9.87806Z"></path></svg>
    </button>
  </div>

  <!-- Texture Navigation (visible only in mobile preview mode) -->
  <div class="texture-nav-controls">
    <button class="texture-nav-btn" id="texturePrevBtn" title="Textura Anterior">
      <i class="ri-arrow-left-s-line"></i>
    </button>
    <button class="texture-nav-btn" id="textureNextBtn" title="Próxima Textura">
      <i class="ri-arrow-right-s-line"></i>
    </button>
  </div>

  <!-- Main Layout -->
  <div class="app-layout">

    <!-- Sidebar -->
    <aside class="app-sidebar">

      <!-- Sidebar Header -->
      <div class="sidebar-header">
        <div class="sidebar-brand">
          <div class="sidebar-brand-icon">
            <i class="ri-grid-fill"></i>
          </div>
          <span>HUB<span style="color:var(--brand-orange);">::</span>rio</span>
        </div>

        <!-- Save Button -->
        <button class="button is-small is-primary" onclick="HUB.saveState()">
          <i class="ri-save-line"></i>
        </button>
      </div>

      <!-- Sidebar Nav Tabs -->
      <div style="padding: 0 1.25rem;">
        <div class="sidebar-nav">
          <div class="nav-item is-active" data-tab="editor">Editor</div>
          <div class="nav-item" data-tab="perfil">Perfil</div>
          <div class="nav-item" data-tab="analytics">Analytics</div>
        </div>
      </div>

      <!-- Sidebar Content (scrollable) -->
      <div class="sidebar-content">

        <!-- Editor Tab -->
        <?php include plugin_dir_path( __FILE__ ) . 'linktree/hub-sidebar-editor.php'; ?>

        <!-- Profile Tab -->
        <?php include plugin_dir_path( __FILE__ ) . 'linktree/hub-sidebar-profile.php'; ?>

        <!-- Analytics Tab -->
        <?php include plugin_dir_path( __FILE__ ) . 'linktree/hub-sidebar-analytics.php'; ?>

      </div>

    </aside>

    <!-- Preview Area -->
    <main class="app-main">

      <!-- Phone Frame with Preview -->
      <?php include plugin_dir_path( __FILE__ ) . 'linktree/hub-preview.php'; ?>

    </main>

  </div>

  <!-- Modals -->
  <?php include plugin_dir_path( __FILE__ ) . 'linktree/hub-modals.php'; ?>

  <?php wp_footer(); ?>
</body>
</html>

