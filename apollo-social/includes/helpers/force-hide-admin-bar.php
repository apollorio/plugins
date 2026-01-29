<?php
/**
 * FORCE HIDE WordPress Admin Bar
 *
 * BRUTAL FORCE: Remove WordPress admin bar from ALL logged-in users
 * ONLY show in wp-admin pages
 * Multiple layers of enforcement to avoid any stress
 *
 * @package Apollo_Social
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * LAYER -1: UNIVERSAL FIX - Override admin-bar inline CSS for ALL users
 * This runs for EVERYONE including admins to fix margin-top issues on Apollo templates
 */
add_action( 'wp_head', function() {
	if ( is_admin() ) {
		return;
	}
	?>
<style type="text/css" id="apollo-admin-bar-fix-universal">
/* UNIVERSAL FIX: Override WordPress admin-bar-inline-css margin-top */
/* This fixes the 32px/46px margin that destroys Apollo layouts */
@media screen {
	html {
		margin-top: 0 !important;
	}
	html.admin-bar {
		margin-top: 0 !important;
	}
}
@media screen and (max-width: 782px) {
	html {
		margin-top: 0 !important;
	}
	html.admin-bar {
		margin-top: 0 !important;
	}
}
/* Reposition admin bar to not affect layout */
#wpadminbar {
	position: fixed !important;
	top: 0 !important;
}
/* Body should not have margin-top from admin bar */
body.admin-bar {
	margin-top: 0 !important;
}
</style>
<?php
}, 9999 ); // Priority 9999 = run AFTER WordPress inline CSS

/**
 * LAYER 0: CRITICAL FIX - Prevent null admin bar render crash
 * This prevents Fatal Error when admin bar wasn't initialized but render is called
 *
 * The issue: WordPress hooks wp_admin_bar_render to wp_body_open but the $wp_admin_bar
 * object may not be initialized yet, especially when using block themes with template-canvas.php
 *
 * FIX: Remove wp_admin_bar_render BEFORE wp_body_open runs, via template_redirect hook
 */
add_action( 'template_redirect', function() {
	global $wp_admin_bar;

	// On frontend, always remove the render hook if admin bar isn't initialized yet
	if ( ! is_admin() ) {
		// Remove with all possible priorities WordPress might use
		remove_action( 'wp_body_open', 'wp_admin_bar_render', 0 );
		remove_action( 'wp_body_open', 'wp_admin_bar_render', 10 );
		remove_action( 'wp_body_open', 'wp_admin_bar_render' );

		// Also add safety hook that runs early in wp_body_open
		add_action( 'wp_body_open', function() {
			global $wp_admin_bar;
			if ( ! is_object( $wp_admin_bar ) ) {
				// Remove any remaining hooks
				remove_action( 'wp_body_open', 'wp_admin_bar_render', 0 );
				remove_action( 'wp_body_open', 'wp_admin_bar_render', 10 );
				remove_action( 'wp_body_open', 'wp_admin_bar_render' );
			}
		}, -99999 ); // Extremely early priority
	}
}, 1 );

/**
 * LAYER 0.5: EXTRA FIX - Hook at wp_loaded to catch any late registrations
 */
add_action( 'wp_loaded', function() {
	if ( ! is_admin() ) {
		// Pre-emptive removal of admin bar render from wp_body_open
		remove_action( 'wp_body_open', 'wp_admin_bar_render', 0 );
		remove_action( 'wp_body_open', 'wp_admin_bar_render', 10 );
		remove_action( 'wp_body_open', 'wp_admin_bar_render' );
	}
}, 9999 );

/**
 * LAYER 1: Server-side filter - highest priority
 * Disable admin bar for non-editors on frontend
 */
add_filter( 'show_admin_bar', function( $show ) {
	// Only show in wp-admin
	if ( is_admin() ) {
		return true;
	}

	// Show admin bar for users who can edit posts (admins/editors)
	if ( current_user_can( 'edit_posts' ) ) {
		return true;
	}

	// FORCE HIDE on frontend for everyone else
	return false;
}, 999 );

/**
 * LAYER 2: Remove admin bar initialization on frontend
 */
add_action( 'init', function() {
	if ( ! is_admin() && ! current_user_can( 'edit_posts' ) ) {
		remove_action( 'init', '_wp_admin_bar_init' );
		remove_action( 'wp_head', '_admin_bar_bump_cb' );
		remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 );

		// FIX: Also remove from wp_body_open hook
		remove_action( 'wp_body_open', 'wp_admin_bar_render', 0 );
	}
}, 1 );

/**
 * LAYER 3: CSS Nuclear Option - hide with !important
 * Triple enforcement via inline styles (only for non-editors)
 */
add_action( 'wp_head', function() {
	if ( ! is_admin() && ! current_user_can( 'edit_posts' ) ) {
		?>
<style type="text/css" id="apollo-force-hide-adminbar">
/* BRUTAL FORCE: Hide WordPress admin bar on frontend */
#wpadminbar,
#wp-admin-bar-root-default,
.admin-bar #wpadminbar,
body.admin-bar #wpadminbar {
	display: none !important;
	visibility: hidden !important;
	opacity: 0 !important;
	height: 0 !important;
	overflow: hidden !important;
	position: absolute !important;
	top: -9999px !important;
	left: -9999px !important;
	z-index: -1 !important;
}

/* Remove admin bar spacing from body */
html.admin-bar,
body.admin-bar {
	margin-top: 0 !important;
	padding-top: 0 !important;
}

html body.admin-bar {
	margin-top: 0 !important;
}

/* Remove admin bar height offsets */
.admin-bar .ab-empty-item,
.admin-bar .ab-empty-item * {
	display: none !important;
}
</style>
<?php
	}
}, 1 );

/**
 * LAYER 4: JavaScript Nuclear Option - remove DOM elements
 * Final enforcement to eliminate any lingering elements (only for non-editors)
 */
add_action( 'wp_footer', function() {
	if ( ! is_admin() && ! current_user_can( 'edit_posts' ) ) {
		?>
<script type="text/javascript" id="apollo-destroy-adminbar">
(function() {
	'use strict';

	// Function to brutally remove admin bar
	function destroyAdminBar() {
		// Remove all admin bar elements
		var selectors = [
			'#wpadminbar',
			'#wp-admin-bar-root-default',
			'.admin-bar #wpadminbar',
			'body.admin-bar #wpadminbar'
		];

		selectors.forEach(function(selector) {
			var elements = document.querySelectorAll(selector);
			elements.forEach(function(el) {
				if (el && el.parentNode) {
					el.parentNode.removeChild(el);
				}
			});
		});

		// Remove admin-bar class from html and body
		document.documentElement.classList.remove('admin-bar');
		document.body.classList.remove('admin-bar');

		// Force reset margin/padding
		document.documentElement.style.marginTop = '0';
		document.body.style.marginTop = '0';
		document.body.style.paddingTop = '0';
	}

	// Execute immediately
	destroyAdminBar();

	// Execute on DOMContentLoaded (backup)
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', destroyAdminBar);
	}

	// Execute on window load (final backup)
	window.addEventListener('load', destroyAdminBar);

	// Mutation observer - nuclear option if admin bar tries to appear
	var observer = new MutationObserver(function(mutations) {
		mutations.forEach(function(mutation) {
			mutation.addedNodes.forEach(function(node) {
				if (node.id === 'wpadminbar' || (node.classList && node.classList.contains(
						'admin-bar'))) {
					destroyAdminBar();
				}
			});
		});
	});

	// Observe body for any admin bar injection
	if (document.body) {
		observer.observe(document.body, {
			childList: true,
			subtree: false
		});
	}
})();
</script>
<?php
	}
}, 999 );

/**
 * LAYER 5: User meta override - disable for all users
 */
add_action( 'wp_before_admin_bar_render', function() {
	if ( ! is_admin() ) {
		global $wp_admin_bar;
		if ( is_object( $wp_admin_bar ) ) {
			$wp_admin_bar = null;
		}
	}
}, 0 );

/**
 * LAYER 6: Body class filter - remove admin-bar class
 */
add_filter( 'body_class', function( $classes ) {
	if ( ! is_admin() ) {
		// Remove admin-bar class
		$classes = array_filter( $classes, function( $class ) {
			return $class !== 'admin-bar';
		});
	}
	return $classes;
}, 999 );

/**
 * LAYER 7: Admin bar menu filter - empty all menus on frontend
 */
add_filter( 'wp_before_admin_bar_render', function() {
	if ( ! is_admin() ) {
		global $wp_admin_bar;
		if ( is_object( $wp_admin_bar ) ) {
			// Remove all nodes
			$nodes = $wp_admin_bar->get_nodes();
			if ( is_array( $nodes ) ) {
				foreach ( $nodes as $node ) {
					$wp_admin_bar->remove_node( $node->id );
				}
			}
		}
	}
}, 999 );
