<?php
/**
 * Asset Loading Partial - Apollo Design System
 * CDN-first with local fallback strategy
 * @package Apollo_Core
 * @since 2.0.0
 */

$args = wp_parse_args(
	$args ?? array(),
	array(
		'load_uni_css'   => true,
		'load_base_js'   => true,
		'load_remixicon' => true,
		'extra_css'      => array(),
		'extra_js'       => array(),
	)
);

// CDN URLs (primary source)
$cdn_base = 'https://assets.apollo.rio.br/';

// Local fallback URLs
$local_base = defined( 'APOLLO_CORE_PLUGIN_URL' )
	? APOLLO_CORE_PLUGIN_URL . 'assets/core/'
	: plugin_dir_url( dirname( __DIR__ ) ) . 'assets/core/';

$local_vendor = defined( 'APOLLO_CORE_PLUGIN_URL' )
	? APOLLO_CORE_PLUGIN_URL . 'assets/vendor/'
	: plugin_dir_url( dirname( __DIR__ ) ) . 'assets/vendor/';

// Version for cache busting
$ver = defined( 'APOLLO_CORE_VERSION' ) ? APOLLO_CORE_VERSION : '1.0.0';

// Load uni.css from CDN with local fallback
if ( $args['load_uni_css'] ) {
	$cdn_url   = esc_url( $cdn_base . 'uni.css?ver=' . $ver );
	$local_url = esc_url( $local_base . 'uni.css?ver=' . $ver );
	echo '<link rel="stylesheet" href="' . $cdn_url . '" onerror="this.onerror=null;this.href=\'' . $local_url . '\'">' . "\n";
}

// Load RemixIcon from CDN with local fallback
if ( $args['load_remixicon'] ) {
	$cdn_remix   = 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css';
	$local_remix = esc_url( $local_vendor . 'remixicon/remixicon.css?ver=' . $ver );
	echo '<link rel="stylesheet" href="' . $cdn_remix . '" onerror="this.onerror=null;this.href=\'' . $local_remix . '\'">' . "\n";
}

// Load additional CSS files
foreach ( (array) $args['extra_css'] as $css_url ) {
	echo '<link rel="stylesheet" href="' . esc_url( $css_url ) . '">' . "\n";
}

// Load base.js from CDN with local fallback at end of body
if ( $args['load_base_js'] ) {
	add_action(
		'wp_footer',
		function () use ( $cdn_base, $local_base, $ver ) {
			$cdn_url   = esc_url( $cdn_base . 'base.js?ver=' . $ver );
			$local_url = esc_url( $local_base . 'base.js?ver=' . $ver );
			echo '<script src="' . $cdn_url . '" onerror="this.onerror=null;this.src=\'' . $local_url . '\'"></script>' . "\n";
		},
		999
	);
}

// Load additional JS files at end of body
if ( ! empty( $args['extra_js'] ) ) {
	add_action(
		'wp_footer',
		function () use ( $args ) {
			foreach ( (array) $args['extra_js'] as $js_url ) {
				echo '<script src="' . esc_url( $js_url ) . '"></script>' . "\n";
			}
		},
		999
	);
}
?>

<?php
/**
 * Base CSS Variables - Apollo Design System
 * These should be included in all templates
 */
?>
<style>
:root {
	/* Font System */
	--font-primary: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;

	/* Layout */
	--radius-main: 12px;
	--radius-sec: 20px;
	--transition-main: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);

	/* Light Mode Palette */
	--bg-main: #fff;
	--bg-main-translucent: rgba(255, 255, 255, .68);
	--header-blur-bg: linear-gradient(to bottom, rgb(253 253 253 / .35) 0%, rgb(253 253 253 / .1) 50%, #fff0 100%);
	--text-main: rgba(19, 21, 23, .7);
	--text-primary: rgba(19, 21, 23, .85);
	--text-secondary: rgba(19, 21, 23, .7);
	--border-color: #e0e2e4;
	--border-color-2: #e0e2e454;
	--card-border-light: rgba(0, 0, 0, 0.13);
	--card-shadow-light: rgba(0, 0, 0, 0.05);
	--accent-color: #FFA17F;
	--vermelho: #fe786d;
	--laranja: #FFA17F;

	/* Dark Mode Palette */
	--bg-main-dark: #131517;
	--text-primary-dark: rgba(255, 255, 255, .85);
	--text-secondary-dark: rgba(255, 255, 255, .7);
	--border-color-dark: rgba(255, 255, 255, 0.2);
	--card-shadow-dark: rgba(0, 0, 0, 0.3);
}

/* Base Styles */
* {
	-webkit-tap-highlight-color: transparent;
	corner-shape: squircle;
	box-sizing: border-box;
	margin: 0;
	padding: 0;
}

html, body {
	font-family: var(--font-primary);
	font-size: 15px;
	color: var(--text-secondary);
	background-color: var(--bg-main);
	-webkit-font-smoothing: antialiased;
	scroll-behavior: smooth;
}

p {
	color: var(--text-main);
	line-height: 1.5;
}

.visually-hidden {
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

/* Dark Mode Support */
body.dark-mode {
	--bg-main: var(--bg-main-dark);
	--text-primary: var(--text-primary-dark);
	--text-secondary: var(--text-secondary-dark);
	--border-color: var(--border-color-dark);
	--card-shadow-light: var(--card-shadow-dark);
}
</style>
