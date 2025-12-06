<?php
// phpcs:ignoreFile
/**
 * ============================================
 * FILE: templates/pwa-redirector.php
 * PAGE BUILDER: PWA Redirector
 *
 * Blank canvas template for PWA redirection.
 * Does NOT use get_header() or get_footer().
 * Outputs a complete HTML page with:
 * - Device/PWA detection
 * - Meta tag extraction from target URL
 * - Iframe loading of appropriate content
 *
 * Usage: /pwa-redirector/?target=https://example.com/page
 * Or POST with 'target' parameter
 * ============================================
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Determine the requested URL from POST or GET.
$requested_url = '';
if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' && ! empty( $_POST['target'] ) ) {
	$requested_url = esc_url_raw( wp_unslash( $_POST['target'] ) );
} elseif ( ! empty( $_GET['target'] ) ) {
	$requested_url = esc_url_raw( wp_unslash( $_GET['target'] ) );
}

// Fallback if no URL provided.
if ( empty( $requested_url ) ) {
	$requested_url = home_url();
}

// ============================================
// META RETRIEVAL: Fetch target page metadata
// ============================================
$title       = '';
$description = '';
$og_image    = '';
$og_title    = '';

$response = wp_remote_get(
	$requested_url,
	array(
		'timeout'    => 10,
		'user-agent' => 'Apollo-PWA-Redirector/1.0 (+' . home_url() . ')',
		'sslverify'  => false, // Allow self-signed certs in local dev.
	)
);

if ( ! is_wp_error( $response ) ) {
	$html = wp_remote_retrieve_body( $response );

	// Extract <title>.
	if ( preg_match( '/<title[^>]*>([^<]*)<\/title>/i', $html, $matches ) ) {
		$title = trim( wp_strip_all_tags( $matches[1] ) );
	}

	// Extract meta description.
	if ( preg_match( '/<meta\s+name=["\']description["\']\s+content=["\']([^"\']*)/i', $html, $matches ) ) {
		$description = trim( wp_strip_all_tags( $matches[1] ) );
	}
	// Alternative order: content before name.
	if ( empty( $description ) && preg_match( '/<meta\s+content=["\']([^"\']*)["\'\s]+name=["\']description["\']/i', $html, $matches ) ) {
		$description = trim( wp_strip_all_tags( $matches[1] ) );
	}

	// Extract og:image.
	if ( preg_match( '/<meta\s+property=["\']og:image["\']\s+content=["\']([^"\']*)/i', $html, $matches ) ) {
		$og_image = trim( esc_url_raw( $matches[1] ) );
	}
	// Alternative order.
	if ( empty( $og_image ) && preg_match( '/<meta\s+content=["\']([^"\']*)["\'\s]+property=["\']og:image["\']/i', $html, $matches ) ) {
		$og_image = trim( esc_url_raw( $matches[1] ) );
	}

	// Extract og:title as fallback.
	if ( preg_match( '/<meta\s+property=["\']og:title["\']\s+content=["\']([^"\']*)/i', $html, $matches ) ) {
		$og_title = trim( wp_strip_all_tags( $matches[1] ) );
	}

	// Use og:title if no <title> found.
	if ( empty( $title ) && ! empty( $og_title ) ) {
		$title = $og_title;
	}

	// Extract twitter:image as fallback.
	if ( empty( $og_image ) && preg_match( '/<meta\s+(?:name|property)=["\']twitter:image["\']\s+content=["\']([^"\']*)/i', $html, $matches ) ) {
		$og_image = trim( esc_url_raw( $matches[1] ) );
	}
}

// Fallback title.
if ( empty( $title ) ) {
	$title = get_bloginfo( 'name' );
}

// ============================================
// DEVICE/PWA DETECTION
// ============================================
$is_pwa    = function_exists( 'apollo_is_pwa' ) ? apollo_is_pwa() : false;
$is_mobile = function_exists( 'apollo_is_mobile' ) ? apollo_is_mobile() : wp_is_mobile();

$ua         = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';
$is_android = $is_mobile && strpos( $ua, 'android' ) !== false;
$is_ios     = $is_mobile && ( strpos( $ua, 'iphone' ) !== false || strpos( $ua, 'ipad' ) !== false );

// ============================================
// DETERMINE IFRAME/REDIRECT SOURCE
// ============================================
if ( $is_pwa ) {
	// Already in PWA standalone mode - load the requested content.
	$frame_src = $requested_url;
} elseif ( $is_android ) {
	// Android browser - show Android PWA install page.
	$frame_src = 'https://assets.apollo.rio/pages/android/index.html';
} elseif ( $is_ios ) {
	// iOS browser - show iOS PWA install page.
	$frame_src = 'https://assets.apollo.rio/pages/ios/index.html';
} else {
	// Desktop or unknown - show the requested content directly.
	$frame_src = $requested_url;
}

// Allow filtering the frame source.
$frame_src = apply_filters( 'apollo_pwa_redirector_frame_src', $frame_src, array(
	'requested_url' => $requested_url,
	'is_pwa'        => $is_pwa,
	'is_mobile'     => $is_mobile,
	'is_android'    => $is_android,
	'is_ios'        => $is_ios,
) );

// ============================================
// OUTPUT THE HTML PAGE
// ============================================
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<title><?php echo esc_html( $title ); ?></title>
	<?php if ( $description ) : ?>
	<meta name="description" content="<?php echo esc_attr( $description ); ?>">
	<?php endif; ?>
	<?php if ( $og_image ) : ?>
	<meta property="og:image" content="<?php echo esc_url( $og_image ); ?>">
	<meta name="twitter:image" content="<?php echo esc_url( $og_image ); ?>">
	<?php endif; ?>
	<meta property="og:title" content="<?php echo esc_attr( $title ); ?>">
	<meta property="og:url" content="<?php echo esc_url( $requested_url ); ?>">
	<meta property="og:type" content="website">

	<!-- PWA Meta Tags -->
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="theme-color" content="#000000">

	<!-- Preconnect for performance -->
	<link rel="preconnect" href="https://assets.apollo.rio.br" crossorigin>
	<link rel="preconnect" href="https://assets.apollo.rio" crossorigin>

	<!-- Only load Apollo assets - no theme CSS/JS -->
	<link rel="preload" href="https://assets.apollo.rio.br/uni.css" as="style">
	<link rel="stylesheet" href="https://assets.apollo.rio.br/uni.css">
	<script src="https://assets.apollo.rio.br/base.js" defer></script>

	<style>
		/* Reset and full-viewport iframe */
		*, *::before, *::after {
			box-sizing: border-box;
		}
		html, body {
			margin: 0;
			padding: 0;
			overflow: hidden;
			height: 100%;
			width: 100%;
			background: #000;
		}
		#apollo-frame {
			width: 100%;
			height: 100vh;
			height: 100dvh; /* Dynamic viewport height for mobile */
			border: 0;
			display: block;
		}

		/* Safari iOS safe area support */
		@supports (padding: env(safe-area-inset-top)) {
			#apollo-frame {
				height: calc(100vh - env(safe-area-inset-top) - env(safe-area-inset-bottom));
				padding-top: env(safe-area-inset-top);
			}
		}

		/* PWA mask compatibility for iOS Safari */
		.pwa-install-mask {
			-webkit-clip-path: url(#phone_clip);
			clip-path: url(#phone_clip);
		}

		/* Loading state */
		.apollo-loading {
			position: fixed;
			inset: 0;
			display: flex;
			align-items: center;
			justify-content: center;
			background: #000;
			z-index: 9999;
			transition: opacity 0.3s ease;
		}
		.apollo-loading.hidden {
			opacity: 0;
			pointer-events: none;
		}
		.apollo-spinner {
			width: 40px;
			height: 40px;
			border: 3px solid rgba(255,255,255,0.1);
			border-top-color: #fff;
			border-radius: 50%;
			animation: spin 1s linear infinite;
		}
		@keyframes spin {
			to { transform: rotate(360deg); }
		}

		/* Focus outline for accessibility */
		:focus-visible {
			outline: 2px solid #fff;
			outline-offset: 2px;
		}
	</style>
</head>
<body>
	<!-- Loading indicator -->
	<div class="apollo-loading" id="apollo-loading" aria-label="<?php esc_attr_e( 'Carregando...', 'apollo-rio' ); ?>">
		<div class="apollo-spinner" role="status">
			<span class="sr-only"><?php esc_html_e( 'Carregando...', 'apollo-rio' ); ?></span>
		</div>
	</div>

	<!-- Main iframe -->
	<iframe
		id="apollo-frame"
		src="<?php echo esc_url( $frame_src ); ?>"
		title="<?php echo esc_attr( $title ); ?>"
		allow="fullscreen; autoplay; encrypted-media; picture-in-picture"
		allowfullscreen
		loading="eager"
		aria-label="<?php echo esc_attr( $title ); ?>"
	></iframe>

	<script>
	(function() {
		'use strict';

		var frame = document.getElementById('apollo-frame');
		var loading = document.getElementById('apollo-loading');

		// Hide loading when iframe loads
		if (frame && loading) {
			frame.addEventListener('load', function() {
				loading.classList.add('hidden');
				// Remove loading element after transition
				setTimeout(function() {
					loading.style.display = 'none';
				}, 300);
			});

			// Fallback: hide loading after 10 seconds
			setTimeout(function() {
				if (!loading.classList.contains('hidden')) {
					loading.classList.add('hidden');
				}
			}, 10000);
		}

		// Pass messages between iframe and parent
		window.addEventListener('message', function(event) {
			// Validate origin if needed
			if (event.data && event.data.type === 'apollo-navigate') {
				var targetUrl = event.data.url;
				if (targetUrl && typeof targetUrl === 'string') {
					window.location.href = targetUrl;
				}
			}
		});

		// Detect standalone mode and set cookie
		if (window.matchMedia('(display-mode: standalone)').matches ||
			window.navigator.standalone === true) {
			document.cookie = 'apollo_display_mode=standalone; path=/; max-age=31536000; SameSite=Lax';
		}
	})();
	</script>

	<!-- Screen reader only class -->
	<style>
		.sr-only {
			position: absolute;
			width: 1px;
			height: 1px;
			padding: 0;
			margin: -1px;
			overflow: hidden;
			clip: rect(0, 0, 0, 0);
			white-space: nowrap;
			border: 0;
		}
	</style>
</body>
</html>
<?php
// Exit to prevent any theme output.
exit;
