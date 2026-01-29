<?php
/**
 * Apollo Core
 *
 * @package Apollo_Core
 * @license GPL-2.0-or-later
 *
 * Copyright (c) 2026 Apollo
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 */
/**
 * ================================================================================
 * APOLLO AUTH - Login & Registration Template
 * ================================================================================
 * Main template for user authentication with:
 * - Security states (normal, warning, danger, success)
 * - Login/Registration forms
 * - Aptitude Quiz System
 * - Visual effects (corruption, glitch, siren)
 *
 * @package Apollo_Social
 * @since 1.0.0
 *
 * USAGE:
 * - Include this template directly or use [apollo_login_form] shortcode
 * - Configure via WordPress filters: apollo_auth_config
 *
 * TEMPLATE PARTS:
 * - parts/header.php - Apollo branding header
 * - parts/login-form.php - Login form
 * - parts/register-form.php - Registration form
 * - parts/footer.php - Footer with copyright
 * - parts/lockout-overlay.php - Security lockout display
 * - parts/aptitude-quiz.php - Quiz overlay
 * ================================================================================
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Redireciona usuário logado para o dashboard privado via meta refresh.
if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
	echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0; url=' . esc_url( home_url( '/meu-perfil/' ) ) . '"></head><body></body></html>';
	exit;
}

// Get configuration.
$auth_config = apply_filters(
	'apollo_auth_config',
	array(
		'ajax_url'             => admin_url( 'admin-ajax.php' ),
		'nonce'                => wp_create_nonce( 'apollo_auth_nonce' ),
		'max_failed_attempts'  => 3,
		'lockout_duration'     => 60,
		'simon_levels'         => 4,
		'reaction_targets'     => 4,
		'redirect_after_login' => home_url( '/mural/' ),
		'terms_url'            => apply_filters( 'apollo_auth_terms_url', 'https://apollo.rio.br/politica' ),
		'bug_report_url'       => apply_filters( 'apollo_auth_bug_report_url', 'https://apollo.rio.br/bug/' ),
		'show_instagram'       => true,
		'require_cpf'          => true,
	)
);

// Get available sounds/genres for registration.
$available_sounds = apply_filters(
	'apollo_registration_sounds',
	array(
		'techno'      => 'Techno',
		'house'       => 'House',
		'trance'      => 'Trance',
		'drum_bass'   => 'Drum & Bass',
		'funk'        => 'Funk',
		'tribal'      => 'Tribal',
		'minimal'     => 'Minimal',
		'progressive' => 'Progressive',
		'melodic'     => 'Melódico',
		'hard'        => 'Hard',
		'psy'         => 'Psy',
		'ambient'     => 'Ambient',
	)
);

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<meta name="theme-color" content="#000000">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<title><?php echo esc_html__( 'Apollo::Rio - Terminal de Acesso', 'apollo-social' ); ?></title>

	<!-- Apollo CDN Icons -->
	<script src="https://assets.apollo.rio.br/js/icon.js"></script>

	<!-- Remix Icons Fallback -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css">

	<!-- Apollo Auth Inline CSS with !important -->
	<style>
	/*================================================================================
	APOLLO AUTH - CSS STYLES (INLINE WITH !IMPORTANT)
	================================================================================*/
	/* ================================================================================
   APOLLO AUTH - ULTRA STRICT ROOT VARIABLES
   MINIMIZED - United all similar colors into single variables
   ================================================================================ */

	:root {
		/* Typography */
		--sans: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, sans-serif !important;
		--mono: ui-monospace, 'Cascadia Code', 'Source Code Pro', Menlo, monospace !important;

		/* Base Colors */
		--bg: #190e05 !important;
		--glass-bg: rgba(16, 16, 16, 0.9) !important;
		--glass-border: rgba(252, 251, 248, 0.06) !important;

		/* Text Colors */
		--txt: rgba(184, 170, 148, 0.85) !important;
		--txt-muted: rgba(184, 170, 148, 0.4) !important;
		--white: #ffffff !important;

		/* Brand Colors */
		--apollo: #fb923c !important;
		--warning: #facc15 !important;
		--danger: #ef4444 !important;
		--success: #22c55e !important;
		--matrix: #22c55e !important;
		--matrix-dim: #166534 !important;
		--current-color: var(--apollo) !important;

		/* Simon Colors */
		--simon-red: #FD2E24 !important;
		--simon-blue: #FFA33D !important;
		--simon-green: #FF6F02 !important;
		--simon-yellow: #FFD701 !important;

		/* Unified Extended Palette */
		--txt-bright: rgba(248, 250, 252, 0.6) !important;
		--white-hover: #ebe6e5 !important;
		--gray: #e5e7eb !important;
		--gray-dark: #111827 !important;
		--gray-base: #111 !important;
		--black: #000000 !important;
		--slate: #020617 !important;

		/* Brown Tones - UNIFIED */
		--brown: rgba(42, 29, 15, 0.9) !important;
		--brown-light: rgba(42, 29, 15, 0.8) !important;
		--brown-glass: rgba(42, 33, 15, 0.98) !important;
		--brown-border: rgba(184, 177, 148, 0.35) !important;
		--brown-active: #20150b !important;

		/* Orange Tones */
		--orange-light: #fed7aa !important;
		--orange-dark: #7c2d12 !important;
		--orange-alt: rgba(245, 158, 11, 0.2) !important;

		/* Danger Variants */
		--danger-light: #fca5a5 !important;
		--danger-dark: #7f1d1d !important;
		--danger-darker: #450a0a !important;
		--danger-bg: rgba(185, 28, 28, 0.5) !important;

		/* Success Variants */
		--success-light: #86efac !important;
		--success-dark: #166534 !important;
		--success-darker: #052e16 !important;
		--success-bg: rgba(22, 163, 74, 0.4) !important;

		/* Button Colors */
		--btn-gradient-1: #ff8d7f !important;
		--btn-gradient-2: #ffb690 !important;

		/* Border Colors - UNIFIED */
		--border-input: rgba(184, 159, 148, 0.6) !important;
		--border-slate: rgba(148, 163, 184, 0.35) !important;
		--border-toggle: rgba(184, 171, 148, 0.8) !important;

		/* Background Variants */
		--bg-slate: rgba(15, 23, 42, 0.8) !important;
		--bg-notification: rgba(18, 17, 3, 0.96) !important;
		--bg-danger-btn: rgba(252, 249, 248, 0.02) !important;

		/* White Transparency Levels */
		--white-15: rgba(255, 255, 255, 0.15) !important;
		--white-20: rgba(255, 255, 255, 0.2) !important;
		--white-32: rgba(255, 255, 255, 0.32) !important;
		--white-45: rgba(248, 250, 252, 0.45) !important;

		/* Black Transparency */
		--black-75: rgba(0, 0, 0, 0.75) !important;
		--black-55: rgba(0, 0, 0, 0.55) !important;

		/* Grid/Desktop */
		--grid-line: rgba(249, 250, 251, 0.03) !important;
		--desktop-dots: #ddd3 !important;
		--desktop-grad-1: rgba(200, 107, 0, 0.2) !important;
		--desktop-grad-2: rgba(99, 9, 9, 0.2) !important;
		--desktop-grad-3: rgba(217, 255, 0, 0.1) !important;

		/* Toggle Active */
		--toggle-active: rgba(253, 186, 116, 1) !important;
		--txt-full: rgba(249, 250, 251, 0.9) !important;
		--txt-slate: rgba(184, 174, 148, 0.9) !important;

		/* Apollo Opacity Levels - MINIMIZED */
		--apollo-10: rgba(251, 146, 60, 0.12) !important;
		--apollo-15: rgba(251, 146, 60, 0.16) !important;
		--apollo-20: rgba(251, 146, 60, 0.2) !important;
		--apollo-30: rgba(251, 146, 60, 0.3) !important;
		--apollo-45: rgba(251, 146, 60, 0.45) !important;
		--apollo-50: rgba(251, 146, 60, 0.5) !important;
		--apollo-55: rgba(251, 146, 60, 0.55) !important;
		--apollo-75: rgba(251, 146, 60, 0.75) !important;
		--apollo-80: rgba(251, 146, 60, 0.8) !important;
		--apollo-90: rgba(251, 146, 60, 0.9) !important;

		/* Warning Opacity Levels */
		--warning-20: rgba(250, 204, 21, 0.2) !important;
		--warning-50: rgba(250, 204, 21, 0.5) !important;

		/* Danger Opacity Levels - MINIMIZED */
		--danger-15: rgba(239, 68, 68, 0.15) !important;
		--danger-20: rgba(239, 68, 68, 0.2) !important;
		--danger-30: rgba(239, 68, 68, 0.3) !important;
		--danger-40: rgba(239, 68, 68, 0.4) !important;
		--danger-50: rgba(239, 68, 68, 0.5) !important;
		--danger-60: rgba(239, 68, 68, 0.6) !important;
		--danger-80: rgba(239, 68, 68, 0.8) !important;
		--danger-90: rgba(239, 68, 68, 0.9) !important;

		/* Success Opacity Levels */
		--success-20: rgba(34, 197, 94, 0.2) !important;
		--success-30: rgba(34, 197, 94, 0.3) !important;
		--success-50: rgba(34, 197, 94, 0.5) !important;

		/* Button Shadows */
		--btn-shadow: rgba(249, 115, 22, 0.25) !important;
		--btn-shadow-hover: rgba(249, 115, 22, 0.4) !important;
	}

	* {
		box-sizing: border-box !important;
		-webkit-tap-highlight-color: transparent !important;
		margin: 0 !important;
		padding: 0 !important;
	}

	html,
	body {
		margin: 0 !important;
		padding: 0 !important;
		font-family: var(--sans) !important;
		background-color: var(--bg) !important;
		color: var(--txt) !important;
		overflow: hidden !important;
		height: 100vh !important;
		width: 100vw !important;
		display: flex !important;
		align-items: center !important;
		justify-content: center !important;
		transition: all 0.4s ease !important;
	}

	@media (min-width:990px) {
		body {
			position: relative !important;
			background: radial-gradient(farthest-corner at 40px 40px, var(--desktop-grad-1) 0%, var(--desktop-grad-2) 35%, var(--desktop-grad-3) 100%), var(--gray-base) !important;
			display: flex !important;
		}

		body::before {
			content: "" !important;
			position: absolute !important;
			inset: 0px !important;
			background: radial-gradient(circle, var(--desktop-dots) 1.25px, transparent 1px) 0 0 / 22px 22px !important;
			mask: radial-gradient(circle at 20px 20px, #ffff 50%, #fff0 80%) !important;
		}
	}

	body[data-state="normal"] {
		--current-color: var(--apollo) !important;
	}

	body[data-state="warning"] {
		--current-color: var(--warning) !important;
	}

	body[data-state="danger"] {
		--current-color: var(--danger) !important;
		filter: saturate(1.2) hue-rotate(-20deg) !important;
		animation: dangerPulse 0.5s infinite alternate !important;
	}

	body[data-state="success"] {
		--current-color: var(--success) !important;
		filter: saturate(1.1) hue-rotate(80deg) !important;
	}

	@keyframes dangerPulse {
		0% {
			filter: saturate(1.2) hue-rotate(-20deg) brightness(0.9) !important;
		}

		100% {
			filter: saturate(1.5) hue-rotate(-10deg) brightness(1.1) !important;
		}
	}

	body[data-state="warning"] .terminal-wrapper {
		animation: warningPulse 1.5s infinite alternate !important;
		border-color: var(--warning) !important;
	}

	@keyframes warningPulse {
		0% {
			box-shadow: 0 25px 60px var(--black-75), 0 0 30px var(--warning-20) !important;
		}

		100% {
			box-shadow: 0 25px 60px var(--black-75), 0 0 60px var(--warning-50) !important;
		}
	}

	body[data-state="danger"] .terminal-wrapper {
		animation: sirenPulse 0.3s infinite alternate !important;
		border-color: var(--danger) !important;
		box-shadow: 0 0 30px var(--danger-60), 0 0 60px var(--danger-40), inset 0 0 20px var(--danger-20) !important;
	}

	@keyframes sirenPulse {
		0% {
			box-shadow: 0 0 30px var(--danger-60), 0 0 60px var(--danger-40) !important;
		}

		100% {
			box-shadow: 0 0 50px var(--danger-90), 0 0 100px var(--danger-60) !important;
		}
	}

	body[data-state="danger"] .bg-layer {
		background: radial-gradient(circle at 0 0, var(--danger-50), transparent 55%), radial-gradient(circle at 100% 0, var(--danger-bg), transparent 60%), radial-gradient(circle at 50% 120%, var(--danger-darker) 30%, var(--black) 100%) !important;
	}

	body[data-state="success"] .bg-layer {
		background: radial-gradient(circle at 0 0, var(--success-50), transparent 55%), radial-gradient(circle at 100% 0, var(--success-bg), transparent 60%), radial-gradient(circle at 50% 120%, var(--success-darker) 30%, var(--black) 100%) !important;
	}

	body[data-state="success"] .terminal-wrapper {
		border-color: var(--success) !important;
		box-shadow: 0 0 40px var(--success-50), 0 0 80px var(--success-30) !important;
	}

	.bg-layer {
		position: fixed !important;
		inset: 0 !important;
		z-index: -3 !important;
		background: radial-gradient(circle at 0 0, var(--apollo-20), transparent 55%), radial-gradient(circle at 100% 0, var(--orange-alt), transparent 60%), radial-gradient(circle at 50% 120%, var(--slate) 30%, var(--black) 100%) !important;
		transition: background 0.8s ease !important;
	}

	.grid-overlay {
		position: fixed !important;
		inset: 0 !important;
		z-index: -2 !important;
		background-image: linear-gradient(var(--grid-line) 1px, transparent 1px), linear-gradient(90deg, var(--grid-line) 1px, transparent 1px) !important;
		background-size: 40px 40px !important;
		opacity: .5 !important;
		pointer-events: none !important;
	}

	.noise-overlay {
		position: fixed !important;
		inset: 0 !important;
		z-index: -1 !important;
		opacity: .06 !important;
		pointer-events: none !important;
		background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.75' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E") !important;
	}

	.terminal-wrapper {
		width: 100% !important;
		height: 100% !important;
		position: relative !important;
		display: flex !important;
		flex-direction: column !important;
		background: transparent !important;
		transition: all 0.4s ease !important;
	}

	@media (min-width: 768px) {
		.terminal-wrapper {
			width: 420px !important;
			height: 86vh !important;
			max-height: 840px !important;
			border-radius: 24px !important;
			border: 1px solid var(--glass-border) !important;
			background: linear-gradient(145deg, var(--brown-glass), var(--brown)) !important;
			box-shadow: 0 65px 160px var(--black-75), 0 0 0 1px var(--brown) !important;
			overflow: hidden !important;
			backdrop-filter: blur(26px) !important;
		}
	}

	.scroll-area {
		flex: 1 !important;
		overflow-y: auto !important;
		padding: 20px 22px 18px !important;
		scrollbar-width: none !important;
	}

	.scroll-area::-webkit-scrollbar {
		display: none !important;
	}

	header.apollo-header {
		padding: 16px 20px 10px !important;
		border-bottom: 1px solid var(--brown-border) !important;
		background: radial-gradient(circle at 0 0, var(--apollo-15), transparent 65%) !important;
		position: relative !important;
		z-index: 10 !important;
	}

	body[data-state="danger"] header.apollo-header {
		background: radial-gradient(circle at 0 0, var(--danger-30), transparent 65%) !important;
	}

	body[data-state="success"] header.apollo-header {
		background: radial-gradient(circle at 0 0, var(--success-30), transparent 65%) !important;
	}

	.logo-mark {
		display: flex !important;
		align-items: center !important;
		gap: 10px !important;
	}

	.logo-icon {
		width: 26px !important;
		height: 26px !important;
		border-radius: 999px !important;
		background: radial-gradient(circle at 30% 0, var(--orange-light), transparent 55%), radial-gradient(circle at 80% 120%, var(--orange-dark), var(--gray-dark)) !important;
		border: 1px solid var(--white-45) !important;
		box-shadow: 0 0 0 1px var(--brown), 0 0 18px var(--apollo-75) !important;
		position: relative !important;
	}

	.logo-icon::after {
		content: '' !important;
		position: absolute !important;
		inset: 5px !important;
		border-radius: 999px !important;
		border: 1px solid var(--brown) !important;
	}

	body[data-state="danger"] .logo-icon {
		background: radial-gradient(circle at 30% 0, var(--danger-light), transparent 55%), radial-gradient(circle at 80% 120%, var(--danger-dark), var(--gray-dark)) !important;
		box-shadow: 0 0 0 1px var(--brown), 0 0 18px var(--apollo-75) !important;
	}

	body[data-state="success"] .logo-icon {
		background: radial-gradient(circle at 30% 0, var(--success-light), transparent 55%), radial-gradient(circle at 80% 120%, var(--success-dark), var(--gray-dark)) !important;
		box-shadow: 0 0 0 1px var(--brown), 0 0 18px var(--apollo-75) !important;
	}

	.logo-text {
		display: flex !important;
		flex-direction: column !important;
		line-height: 1.1 !important;
	}

	.logo-text .brand {
		font-size: 14px !important;
		font-weight: 800 !important;
		letter-spacing: .12em !important;
		text-transform: uppercase !important;
		color: var(--white) !important;
	}

	.logo-text .sub {
		font-size: 10px !important;
		text-transform: uppercase !important;
		letter-spacing: .18em !important;
		color: var(--txt-bright) !important;
	}

	.coordinates {
		margin-top: 8px !important;
		display: flex !important;
		justify-content: space-between !important;
		align-items: baseline !important;
		font-family: var(--mono) !important;
		font-size: 10px !important;
		color: var(--txt-bright) !important;
	}

	.scan-line {
		position: absolute !important;
		top: 0 !important;
		left: 0 !important;
		right: 0 !important;
		height: 2px !important;
		background: linear-gradient(90deg, transparent, var(--apollo), transparent) !important;
		opacity: .7 !important;
		animation: scan 3.4s linear infinite !important;
		pointer-events: none !important;
		z-index: 40 !important;
	}

	body[data-state="danger"] .scan-line {
		background: linear-gradient(90deg, transparent, var(--danger), transparent) !important;
		animation: scan 0.8s linear infinite !important;
	}

	body[data-state="success"] .scan-line {
		background: linear-gradient(90deg, transparent, var(--success), transparent) !important;
	}

	@keyframes scan {
		0% {
			transform: translateY(0) !important;
			opacity: 0 !important;
		}

		18% {
			opacity: 1 !important;
		}

		100% {
			transform: translateY(780px) !important;
			opacity: 0 !important;
		}
	}

	.flavor-text {
		font-family: var(--mono) !important;
		font-size: 10px !important;
		color: var(--txt) !important;
		text-transform: uppercase !important;
		letter-spacing: .16em !important;
		margin-bottom: 4px !important;
		display: flex !important;
		justify-content: space-between !important;
		gap: 12px !important;
		opacity: .85 !important;
	}

	.form-group {
		margin-bottom: 16px !important;
		position: relative !important;
	}

	label {
		font-size: 11px !important;
		font-weight: 600 !important;
		text-transform: uppercase !important;
		letter-spacing: .14em !important;
		color: var(--txt) !important;
		margin-bottom: 6px !important;
		display: block !important;
	}

	.input-wrapper {
		position: relative !important;
		display: flex !important;
		align-items: center !important;
	}

	.input-prefix {
		position: absolute !important;
		left: 10px !important;
		font-family: var(--mono) !important;
		font-size: 11px !important;
		color: var(--current-color) !important;
		pointer-events: none !important;
	}

	input[type="text"],
	input[type="email"],
	input[type="password"] {
		width: 100% !important;
		border-radius: 10px !important;
		border: 1px solid var(--border-input) !important;
		background: var(--bg) !important;
		color: var(--txt) !important;
		padding: 10px 10px 10px 30px !important;
		font-size: 13px !important;
		transition: all .25s ease !important;
	}

	input::placeholder {
		color: var(--txt) !important;
	}

	input:focus {
		outline: none !important;
		border-color: var(--current-color) !important;
		box-shadow: 0 0 0 1px var(--apollo-45), 0 0 18px var(--apollo-55) !important;
		background: var(--bg) !important;
	}

	body[data-state="danger"] input {
		border-color: var(--danger-80) !important;
		color: var(--danger-light) !important;
	}

	.btn-primary {
		width: 100% !important;
		background: linear-gradient(135deg, var(--btn-gradient-1), var(--btn-gradient-2)) !important;
		border: 1px solid var(--white-15) !important;
		color: var(--txt) !important;
		padding: 13px !important;
		border-radius: 999px !important;
		font-weight: 700 !important;
		font-size: 12px !important;
		letter-spacing: .18em !important;
		text-transform: uppercase !important;
		cursor: pointer !important;
		display: flex !important;
		align-items: center !important;
		justify-content: center !important;
		gap: 8px !important;
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
		position: relative !important;
		overflow: hidden !important;
		box-shadow: 0 8px 24px var(--btn-shadow) !important;
	}

	.btn-primary::before {
		content: '' !important;
		position: absolute !important;
		inset: -40% !important;
		background: radial-gradient(circle at 0 0, var(--white-32), transparent 60%) !important;
		opacity: 0 !important;
		transition: opacity .25s !important;
	}

	.btn-primary:hover {
		background: linear-gradient(135deg, var(--btn-gradient-1), var(--btn-gradient-2)) !important;
		color: var(--white) !important;
		box-shadow: 0 12px 30px var(--btn-shadow-hover) !important;
		transform: scale(1.03) translateY(-2px) !important;
		filter: brightness(1.15) !important;
	}

	.btn-primary:hover::before {
		opacity: 1 !important;
	}

	.btn-primary:disabled {
		opacity: .5 !important;
		cursor: not-allowed !important;
		box-shadow: none !important;
		filter: grayscale(0.5) !important;
	}

	body[data-state="danger"] .btn-primary {
		background: linear-gradient(90deg, var(--danger-30), var(--bg-danger-btn)) !important;
		border-color: var(--danger-80) !important;
		color: var(--danger-light) !important;
	}

	/* =========== APOLLO CHECK BUTTON STYLES =========== */
	.btn-check-wrapper {
		--width: 100%;
		--height: 60px;
		--padding: 8px;
		--border-radius: 24px;
		--btn-check-dot-size: 10px;
		--btn-check-color: #1a1a1a;
		--hue: 36deg;
		--animation-duration: 1.2s;

		position: relative;
		display: flex;
		align-items: center;
		justify-content: center;
		width: var(--width);
		height: var(--height);
		border-radius: var(--border-radius);
		background-color: rgba(0, 0, 0, 0.1);
		box-shadow:
			1px 1px 2px 0 rgba(255, 255, 255, 0.05),
			2px 2px 2px rgba(0, 0, 0, 0.15) inset,
			2px 2px 4px rgba(0, 0, 0, 0.15) inset,
			2px 2px 8px rgba(0, 0, 0, 0.15) inset;
		perspective: 150px;
		cursor: pointer;
		user-select: none;
		transition: box-shadow 0.05s linear, transform 0.05s linear;
		margin-top: 8px;
	}

	.btn-check-toggle {
		position: absolute;
		width: 100%;
		height: 100%;
		opacity: 0;
		z-index: 5;
		cursor: pointer;
	}

	.btn-check {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 0.25em;
		text-align: center;
		padding: 0 calc(var(--height) + 10px) 0 calc(var(--padding) * 2);
		width: calc(100% - 2 * var(--padding));
		height: calc(100% - 2 * var(--padding));
		border-radius: calc(var(--border-radius) - var(--padding));
		border: none;
		cursor: pointer;
		background: linear-gradient(rgba(255, 255, 255, 0.02), rgba(0, 0, 0, 0.05)), var(--btn-check-color);
		box-shadow:
			1px 1px 2px -1px rgba(255, 255, 255, 0.3) inset,
			0 2px 1px rgba(0, 0, 0, 0.1),
			0 4px 2px rgba(0, 0, 0, 0.1),
			0 8px 4px rgba(0, 0, 0, 0.1),
			0 16px 8px rgba(0, 0, 0, 0.1),
			0 32px 16px rgba(0, 0, 0, 0.1);
		transition:
			transform 0.15s cubic-bezier(0.25, 1.5, 0.5, 1.5),
			box-shadow 0.15s ease,
			filter 0.15s ease;
		z-index: 2;
	}

	.btn-check-txt {
		position: absolute;
		font-family: "Montserrat", "Manrope", sans-serif;
		font-weight: 600;
		font-size: 14px;
		letter-spacing: 0.15em;
		text-transform: uppercase;
		color: rgba(255, 255, 255, 0.85);
		text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
		transition: opacity 0.3s ease;
	}

	.btn-check-txt.playing {
		opacity: 0;
	}

	.btn-check-wrapper.loading .btn-check-txt.play {
		opacity: 0;
	}

	.btn-check-wrapper.loading .btn-check-txt.playing {
		opacity: 0;
	}

	.btn-check-wrapper.success .btn-check-txt.play {
		opacity: 0;
	}

	.btn-check-wrapper.success .btn-check-txt.playing {
		opacity: 1;
		color: #4ade80;
	}

	.btn-check-dot {
		position: absolute;
		top: 50%;
		right: calc(var(--height) / 2 - var(--padding) / 2);
		width: var(--btn-check-dot-size);
		aspect-ratio: 1/1;
		border-radius: 50%;
		background-color: rgba(128, 128, 128, 0.2);
		border: 1px solid rgba(128, 128, 128, 0.5);
		box-shadow:
			1px 1px 2px -1px rgba(255, 255, 255, 0.3) inset,
			0 2px 1px rgba(0, 0, 0, 0.1),
			0 4px 2px rgba(0, 0, 0, 0.1),
			0 8px 4px rgba(0, 0, 0, 0.1);
		pointer-events: none;
		z-index: 3;
		transform: translateY(-50%);
		transition: all 0.15s ease;
	}

	@keyframes checkPulse {
		0%, 100% {
			transform: scale(0.8) translateY(-50%);
			opacity: 0.6;
		}
		50% {
			transform: scale(1.4) translateY(-50%);
			opacity: 1;
		}
	}

	.btn-check-wrapper.loading .btn-check-dot {
		animation: checkPulse var(--animation-duration) ease-in-out infinite;
		background-color: hsl(var(--hue), 90%, 50%);
		box-shadow:
			0 0 8px 2px hsl(var(--hue), 100%, 50%),
			0 0 24px 6px hsla(var(--hue), 100%, 60%, 0.5);
	}

	.btn-check-wrapper.success {
		--hue: 142deg;
	}

	.btn-check-wrapper.success .btn-check-dot {
		animation: checkPulse 2s ease-in-out infinite;
		background-color: hsl(142deg, 90%, 50%);
		box-shadow:
			0 0 8px 2px hsl(142deg, 100%, 50%),
			0 0 24px 6px hsla(142deg, 100%, 60%, 0.5);
	}

	.btn-check-wrapper:hover .btn-check,
	.btn-check-wrapper:focus-within .btn-check {
		transform: translate3d(0, -2px, 2px);
		filter: drop-shadow(8px 0 12px hsla(var(--hue), 70%, 60%, 0.4));
	}

	.btn-check-wrapper:active .btn-check {
		transform: translate3d(0, 2px, -2px);
		box-shadow:
			1px 1px 2px 0 rgba(255, 255, 255, 0.1),
			2px 2px 2px rgba(0, 0, 0, 0.15) inset,
			2px 2px 4px rgba(0, 0, 0, 0.15) inset,
			2px 2px 8px rgba(0, 0, 0, 0.15) inset,
			0 0 32px 2px hsla(var(--hue), 50%, 50%, 0.3) inset;
	}

	.btn-check-wrapper.disabled {
		opacity: 0.5;
		cursor: not-allowed;
		pointer-events: none;
	}

	/* Danger state for check button */
	body[data-state="danger"] .btn-check-wrapper {
		--hue: 0deg;
	}

/* Warning state for check button */
body[data-state="warning"] .btn-check-wrapper,
.btn-check-wrapper.warning {
	--hue: 48deg;
	animation: warningPulse 1.2s infinite;
}
@keyframes warningPulse {
	0%, 100% { box-shadow: 0 0 8px 2px #facc15, 0 0 24px 6px #fde04780; }
	50% { box-shadow: 0 0 16px 4px #facc15, 0 0 32px 12px #fde047cc; }
}

body[data-state="warning"] {
	background: linear-gradient(135deg, #fffbe6 0%, #facc15 100%) !important;
	transition: background 0.5s;
}

body.warning-alert {
	animation: warningBodyFlash 0.8s 2;
}
@keyframes warningBodyFlash {
	0%, 100% { background: #fffbe6; }
	50% { background: #facc15; }
}

	body[data-state="danger"] .btn-check {
		background: linear-gradient(rgba(255, 255, 255, 0.02), rgba(0, 0, 0, 0.05)), #2a0a0a;
	}

	body[data-state="danger"] .btn-check-txt {
		color: var(--danger-light);
	}

	.btn-text {
		border: none !important;
		background: none !important;
		padding: 0 !important;
		font-size: 11px !important;
		color: var(--txt-slate) !important;
		text-decoration: underline !important;
		cursor: pointer !important;
	}

	.btn-text:hover {
		color: var(--white-hover) !important;
	}

	.custom-toggle {
		display: flex !important;
		align-items: center !important;
		gap: 8px !important;
		font-size: 11px !important;
		color: var(--txt-slate) !important;
		cursor: pointer !important;
		user-select: none !important;
	}

	.toggle-track {
		width: 38px !important;
		height: 20px !important;
		border-radius: 20px !important;
		background: var(--bg) !important;
		border: 1px solid var(--border-toggle) !important;
		position: relative !important;
		transition: .25s !important;
		flex-shrink: 0 !important;
	}

	.toggle-thumb {
		width: 14px !important;
		height: 14px !important;
		border-radius: 999px !important;
		background: var(--txt) !important;
		position: absolute !important;
		top: 2px !important;
		left: 2px !important;
		box-shadow: 0 3px 6px var(--black-55) !important;
		transition: .25s !important;
	}

	.custom-toggle.active .toggle-track {
		background: var(--apollo-90) !important;
		border-color: var(--toggle-active) !important;
	}

	.custom-toggle.active .toggle-thumb {
		transform: translateX(16px) !important;
		background: var(--brown-active) !important;
	}

	.notification-area {
		position: absolute !important;
		inset: 14px 16px auto 16px !important;
		z-index: 60 !important;
		pointer-events: none !important;
	}

	.auth-alert {
		background: var(--bg-notification) !important;
		border-radius: 9px !important;
		border: 1px solid var(--glass-border) !important;
		padding: 8px 10px !important;
		margin-bottom: 8px !important;
		font-family: var(--mono) !important;
		font-size: 11px !important;
		color: var(--gray) !important;
		pointer-events: auto !important;
		animation: slideIn .25s ease-out !important;
	}

	@keyframes slideIn {
		from {
			opacity: 0 !important;
			transform: translateY(-6px) !important;
		}

		to {
			opacity: 1 !important;
			transform: translateY(0) !important;
		}
	}

	.lockout-overlay {
		position: absolute !important;
		inset: 0 !important;
		background: var(--brown-glass) !important;
		display: none !important;
		flex-direction: column !important;
		align-items: center !important;
		justify-content: center !important;
		text-align: center !important;
		padding: 20px !important;
		z-index: 80 !important;
	}

	body[data-state="danger"] .lockout-overlay {
		display: flex !important;
	}

	.shake {
		animation: shake .5s cubic-bezier(.36, .07, .19, .97) both !important;
	}

	@keyframes shake {

		10%,
		90% {
			transform: translate3d(-1px, 0, 0) !important;
		}

		20%,
		80% {
			transform: translate3d(2px, 0, 0) !important;
		}

		30%,
		50%,
		70% {
			transform: translate3d(-4px, 0, 0) !important;
		}

		40%,
		60% {
			transform: translate3d(4px, 0, 0) !important;
		}
	}

	.aptitude-overlay {
		position: absolute !important;
		inset: 0 !important;
		background: var(--bg) !important;
		z-index: 70 !important;
		display: none !important;
		flex-direction: column !important;
	}

	.aptitude-overlay.active {
		display: flex !important;
	}

	.danger-flash {
		position: fixed !important;
		inset: 0 !important;
		background: var(--danger-15) !important;
		pointer-events: none !important;
		z-index: 100 !important;
		animation: flashRed 0.5s infinite !important;
	}

	@keyframes flashRed {

		0%,
		100% {
			opacity: 0.1 !important;
		}

		50% {
			opacity: 0.4 !important;
		}
	}

	footer {
		padding: 8px 14px 10px !important;
		font-family: var(--mono) !important;
		font-size: 9px !important;
		text-align: center !important;
		color: var(--txt) !important;
		border-top: 1px solid var(--border-slate) !important;
		background: radial-gradient(circle at 100% 0, var(--apollo-10), transparent 55
	</style>
</head>

<body data-state="normal">

	<!-- Background Layers -->
	<div class="bg-layer"></div>
	<div class="grid-overlay"></div>
	<div class="noise-overlay"></div>

	<!-- Terminal Container -->
	<div class="terminal-wrapper"
		data-tooltip="<?php esc_attr_e( 'Terminal de Autenticação Apollo', 'apollo-social' ); ?>">

		<!-- Scan Line Effect -->
		<div class="scan-line"></div>

		<!-- Notification Area -->
		<div class="notification-area"></div>

		<!-- Header -->
		<?php require_once __DIR__ . '/parts/header.php'; ?>

		<!-- Main Content Area -->
		<div class="scroll-area">

			<!-- Login Section -->
			<section id="login-section" data-tooltip="<?php esc_attr_e( 'Formulário de Login', 'apollo-social' ); ?>">
				<?php require_once __DIR__ . '/parts/login-form.php'; ?>
			</section>

			<!-- Register Section (Hidden by default) -->
			<section id="register-section" class="hidden"
				data-tooltip="<?php esc_attr_e( 'Formulário de Registro', 'apollo-social' ); ?>">
				<?php require_once __DIR__ . '/parts/register-form.php'; ?>
			</section>

		</div>

		<!-- Footer -->
		<?php require_once __DIR__ . '/parts/footer.php'; ?>

		<!-- Lockout Overlay -->
		<?php require_once __DIR__ . '/parts/lockout-overlay.php'; ?>

		<!-- Aptitude Quiz Overlay -->
		<?php require_once __DIR__ . '/parts/aptitude-quiz.php'; ?>

	</div>

	<!-- Apollo CDN Scripts -->
	<script src="https://assets.apollo.rio.br/js/jquery.min.js"></script>
	<script src="https://assets.apollo.rio.br/fx/reveal/reveal-up.js"></script>

	<!-- Apollo Auth Config -->
	<script>
	window.apolloAuthConfig = {
		ajaxUrl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
		nonce: '<?php echo esc_js( wp_create_nonce( 'apollo_login_action' ) ); ?>',
		maxFailedAttempts: <?php echo intval( $auth_config['max_failed_attempts'] ); ?>,
		lockoutDuration: <?php echo intval( $auth_config['lockout_duration'] ); ?>,
		simonLevels: <?php echo intval( $auth_config['simon_levels'] ); ?>,
		reactionTargets: <?php echo intval( $auth_config['reaction_targets'] ); ?>,
		redirectAfterLogin: '<?php echo esc_url( $auth_config['redirect_after_login'] ); ?>',
		strings: {
			loginSuccess: '<?php echo esc_js( __( 'Acesso autorizado. Redirecionando...', 'apollo-core' ) ); ?>',
			loginFailed: '<?php echo esc_js( __( 'Credenciais incorretas. Tente novamente.', 'apollo-core' ) ); ?>',
			warningState: '<?php echo esc_js( __( 'Atenção: última tentativa antes do bloqueio.', 'apollo-core' ) ); ?>',
			lockedOut: '<?php echo esc_js( __( 'Sistema bloqueado por segurança.', 'apollo-core' ) ); ?>',
			quizComplete: '<?php echo esc_js( __( 'Teste de aptidão concluído com sucesso!', 'apollo-core' ) ); ?>',
			quizFailed: '<?php echo esc_js( __( 'Resposta incorreta. Reiniciando pergunta...', 'apollo-core' ) ); ?>',
			patternCorrect: '♫♫♫',
			ethicsCorrect: '<?php echo esc_js( __( 'É trabalho, renda, a sonoridade e arte favorita de alguem.', 'apollo-core' ) ); ?>'
		}
	};
	</script>

	<!-- APOLLO AUTH SCRIPTS - COMPLETE SYSTEM -->
	<script>
	(function() {
		'use strict';

		// ========================================================================
		// CONFIGURATION
		// ========================================================================
		const CONFIG = window.apolloAuthConfig || {
			ajaxUrl: '/wp-admin/admin-ajax.php',
			nonce: '',
			maxFailedAttempts: 3,
			lockoutDuration: 60,
			simonLevels: 4,
			reactionTargets: 4,
			redirectAfterLogin: '/feed/',
			strings: {
				loginSuccess: 'Acesso autorizado. Redirecionando...',
				loginFailed: 'Credenciais incorretas. Tente novamente.',
				warningState: 'Atenção: última tentativa antes do bloqueio.',
				lockedOut: 'Sistema bloqueado por segurança.',
				quizComplete: 'Teste de aptidão concluído com sucesso!',
				quizFailed: 'Resposta incorreta. Reiniciando pergunta...',
				patternCorrect: '♫♫♫',
				ethicsCorrect: 'É trabalho, renda, a sonoridade e arte favorita de alguem.'
			}
		};

		// State management
		let state = {
			failedAttempts: 0,
			isLockedOut: false,
			lockoutEndTime: null,
			currentQuizStage: 0,
			simonSequence: [],
			simonUserSequence: [],
			simonLevel: 1,
			reactionCaptures: 0,
			timestampInterval: null,
			glitchInterval: null
		};

		// DOM Elements cache
		let els = {};

		// ========================================================================
		// INITIALIZATION
		// ========================================================================

		document.addEventListener('DOMContentLoaded', function() {
			// Cache DOM elements
			els = {
				body: document.body,
				loginForm: document.getElementById('login-form'),
				registerForm: document.getElementById('register-form'),
				loginSection: document.getElementById('login-section'),
				registerSection: document.getElementById('register-section'),
				aptitudeOverlay: document.getElementById('aptitude-overlay'),
				lockoutOverlay: document.querySelector('.lockout-overlay'),
				lockoutTimer: document.getElementById('lockout-timer'),
				timestamp: document.getElementById('timestamp'),
				testContent: document.getElementById('test-content'),
				testBtn: document.getElementById('test-btn'),
				testBtnText: document.getElementById('test-btn-text'),
				testProgress: document.getElementById('test-progress'),
				dangerFlash: null,
				toggles: document.querySelectorAll('.custom-toggle'),
				switchToRegister: document.getElementById('switch-to-register'),
				switchToLogin: document.getElementById('switch-to-login')
			};

			// Initialize components
			initToggles();
			initFormSwitching();
			initForms();
			initTimestamp();
			initInstagramField();
			initSoundsValidation();

			// Check for existing lockout
			checkExistingLockout();

			// Set initial state to normal
			setSecurityState('normal');

			console.log('Apollo Auth System initialized');
		});

		// ========================================================================
		// TOGGLE SWITCHES
		// ========================================================================

		function initToggles() {
			els.toggles.forEach(t => {
				t.addEventListener('click', () => {
					t.classList.toggle('active');
					const input = t.querySelector('input[type="hidden"]');
					if (input) {
						input.value = t.classList.contains('active') ? '1' : '0';
					}
				});
			});
		}

		// ========================================================================
		// FORM SWITCHING (Login <-> Register)
		// ========================================================================

		function initFormSwitching() {
			if (els.switchToRegister) {
				els.switchToRegister.addEventListener('click', function(e) {
					e.preventDefault();
					els.loginSection.classList.add('hidden');
					els.registerSection.classList.remove('hidden');
				});
			}

			if (els.switchToLogin) {
				els.switchToLogin.addEventListener('click', function(e) {
					e.preventDefault();
					els.registerSection.classList.add('hidden');
					els.loginSection.classList.remove('hidden');
				});
			}
		}

		// ========================================================================
		// FORM SUBMISSION HANDLERS
		// ========================================================================

		function initForms() {
			if (els.loginForm) {
				els.loginForm.addEventListener('submit', handleLogin);
			}
			if (els.registerForm) {
				els.registerForm.addEventListener('submit', handleRegister);
			}
		}

		/**
		 * Handle login form submission
		 * Manages security states based on failed attempts
		 * Works with Apollo Check Button
		 */
		async function handleLogin(e) {
			e.preventDefault();

			if (state.isLockedOut) {
				shakeElement(els.loginForm);
				return;
			}

			const form = e.target;
			const username = form.querySelector('[name="log"]')?.value || form.querySelector('[name="username"]')
				?.value;
			const password = form.querySelector('[name="pwd"]')?.value || form.querySelector('[name="password"]')
				?.value;

			// Get the new Apollo Check Button elements
			const btnWrapper = form.querySelector('.btn-check-wrapper');
			const submitBtn = form.querySelector('.btn-check') || form.querySelector('button[type="submit"]');

			if (!username || !password) {
				showNotification('Preencha todos os campos.', 'warning');
				return;
			}

			// Set loading state on Apollo Check Button
			if (btnWrapper) {
				btnWrapper.classList.add('loading');
				btnWrapper.classList.add('disabled');
			}
			if (submitBtn) {
				submitBtn.disabled = true;
			}

			try {
				const nonceField = form.querySelector('[name="apollo_login_nonce"]');
				const nonce = nonceField ? nonceField.value : CONFIG.nonce;
				const rememberValue = form.querySelector('[name="rememberme"]')?.value || '0';

				const response = await fetch(CONFIG.ajaxUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
					},
					body: new URLSearchParams({
						action: 'apollo_navbar_login',
						nonce: nonce,
						user: username,
						pass: password,
						remember: rememberValue
					})
				});

				const result = await response.json();

				if (result.success) {
					// SUCCESS - Change to green success state
					if (btnWrapper) {
						btnWrapper.classList.remove('loading');
						btnWrapper.classList.add('success');
					}
					handleLoginSuccess(result.data);
				} else {
					// FAILURE - Reset button
					if (btnWrapper) {
						btnWrapper.classList.remove('loading', 'success', 'disabled');
					}
					if (submitBtn) {
						submitBtn.disabled = false;
					}
					handleLoginFailure(result.data?.message || CONFIG.strings.loginFailed);
				}
			} catch (error) {
				console.error('Login error:', error);
				// Reset button on error
				if (btnWrapper) {
					btnWrapper.classList.remove('loading', 'success', 'disabled');
				}
				if (submitBtn) {
					submitBtn.disabled = false;
				}
				showNotification('Erro de conexão. Tente novamente.', 'error');
			}
		}

		/**
		 * Handle successful login
		 */
		function handleLoginSuccess(data) {
			state.failedAttempts = 0;
			setSecurityState('success');
			showNotification(data?.message || CONFIG.strings.loginSuccess, 'success');

			// Redirect after animation
			setTimeout(() => {
				window.location.href = data?.redirect_url || CONFIG.redirectAfterLogin;
			}, 1500);
		}

		/**
		 * Handle failed login attempt
		 */
		function handleLoginFailure(errorMsg) {
			state.failedAttempts++;
			shakeElement(els.loginForm);

			if (state.failedAttempts >= CONFIG.maxFailedAttempts) {
				// LOCKOUT - Danger state
				setSecurityState('danger');
				initiateLockout();
			} else if (state.failedAttempts === CONFIG.maxFailedAttempts - 1) {
				// WARNING state
				setSecurityState('warning');
				showNotification(CONFIG.strings.warningState, 'warning');
				// Apollo Check Button: warning state
				const btnWrapper = els.loginForm.querySelector('.btn-check-wrapper');
				if (btnWrapper) {
					btnWrapper.classList.remove('loading', 'success', 'danger');
					btnWrapper.classList.add('warning');
				}
				// Custom alert effect
				document.body.classList.add('warning-alert');
				setTimeout(() => {
					document.body.classList.remove('warning-alert');
				}, 2000);
			} else {
				showNotification(errorMsg || CONFIG.strings.loginFailed, 'error');
			}
		}

		/**
		 * Handle registration form submission
		 */
		async function handleRegister(e) {
			e.preventDefault();

			const form = e.target;
			const formData = new FormData(form);

			// Validate required fields
			const requiredFields = ['nome', 'email', 'senha'];
			let isValid = true;

			requiredFields.forEach(field => {
				const input = form.querySelector(`[name="${field}"]`);
				if (!input || !input.value.trim()) {
					isValid = false;
					if (input) input.classList.add('error');
				}
			});

			// Validate document type
			const docType = form.querySelector('[name="doc_type"]')?.value;
			if (docType === 'cpf') {
				const cpf = form.querySelector('[name="cpf"]')?.value;
				if (!cpf || !validateCPF(cpf)) {
					isValid = false;
					showNotification('CPF inválido.', 'error');
					return;
				}
			} else if (docType === 'passport') {
				const passport = form.querySelector('[name="passport"]')?.value;
				if (!passport || passport.length < 5) {
					isValid = false;
					showNotification('Número de passaporte inválido.', 'error');
					return;
				}
			}

			// Validate sounds selection (at least 1)
			const soundsSelected = form.querySelectorAll('[name="sounds[]"]:checked');
			if (soundsSelected.length === 0) {
				isValid = false;
				showNotification('Selecione pelo menos 1 gênero musical.', 'error');
				return;
			}

			// Validate terms
			const termsToggle = form.querySelector('.terms-toggle');
			if (!termsToggle || !termsToggle.classList.contains('active')) {
				isValid = false;
				showNotification('Você deve aceitar os termos de uso.', 'error');
				return;
			}

			if (!isValid) {
				shakeElement(form);
				return;
			}

			// Open aptitude test
			openAptitudeTest();
		}

		// ========================================================================
		// CPF VALIDATION
		// ========================================================================

		function validateCPF(cpf) {
			cpf = cpf.replace(/\D/g, '');

			if (cpf.length !== 11) return false;
			if (/^(\d)\1{10}$/.test(cpf)) return false;

			let sum = 0;
			for (let i = 0; i < 9; i++) {
				sum += parseInt(cpf[i]) * (10 - i);
			}
			let d1 = (sum % 11 < 2) ? 0 : 11 - (sum % 11);
			if (parseInt(cpf[9]) !== d1) return false;

			sum = 0;
			for (let i = 0; i < 10; i++) {
				sum += parseInt(cpf[i]) * (11 - i);
			}
			let d2 = (sum % 11 < 2) ? 0 : 11 - (sum % 11);
			return parseInt(cpf[10]) === d2;
		}

		// ========================================================================
		// SECURITY STATES
		// ========================================================================

		function setSecurityState(newState) {
			els.body.setAttribute('data-state', newState);

			// Handle danger-specific effects
			if (newState === 'danger') {
				addDangerFlash();
				corruptVisibleText();
				startGlitchingTimestamp();
			} else {
				removeDangerFlash();
				stopGlitchingTimestamp();
			}

			// Handle success-specific effects
			if (newState === 'success') {
				playSuccessSound();
			}
		}

		function addDangerFlash() {
			if (!els.dangerFlash) {
				els.dangerFlash = document.createElement('div');
				els.dangerFlash.className = 'danger-flash';
				document.body.appendChild(els.dangerFlash);
			}
		}

		function removeDangerFlash() {
			if (els.dangerFlash) {
				els.dangerFlash.remove();
				els.dangerFlash = null;
			}
			const existing = document.querySelector('.danger-flash');
			if (existing) existing.remove();
		}

		// ========================================================================
		// LOCKOUT SYSTEM
		// ========================================================================

		function initiateLockout() {
			state.isLockedOut = true;
			state.lockoutEndTime = Date.now() + (CONFIG.lockoutDuration * 1000);

			// Save to localStorage for persistence
			localStorage.setItem('apollo_lockout_end', state.lockoutEndTime);

			showNotification(CONFIG.strings.lockedOut, 'error');
			updateLockoutTimer();

			const timerInterval = setInterval(() => {
				const remaining = Math.ceil((state.lockoutEndTime - Date.now()) / 1000);

				if (remaining <= 0) {
					clearInterval(timerInterval);
					endLockout();
				} else {
					updateLockoutTimer(remaining);
				}
			}, 1000);
		}

		function updateLockoutTimer(seconds) {
			if (els.lockoutTimer) {
				const mins = Math.floor(seconds / 60);
				const secs = seconds % 60;
				els.lockoutTimer.textContent = `${mins}:${secs.toString().padStart(2, '0')}`;
			}
		}

		function endLockout() {
			state.isLockedOut = false;
			state.failedAttempts = 0;
			state.lockoutEndTime = null;
			localStorage.removeItem('apollo_lockout_end');
			setSecurityState('normal');
		}

		function checkExistingLockout() {
			const savedLockout = localStorage.getItem('apollo_lockout_end');
			if (savedLockout) {
				const endTime = parseInt(savedLockout, 10);
				if (endTime > Date.now()) {
					state.lockoutEndTime = endTime;
					state.isLockedOut = true;
					setSecurityState('danger');
					initiateLockout();
				} else {
					localStorage.removeItem('apollo_lockout_end');
				}
			}
		}

		// ========================================================================
		// TEXT CORRUPTION EFFECTS
		// ========================================================================

		function corruptVisibleText() {
			const elementsToCorrupt = document.querySelectorAll('h1, h2, .logo-text .brand');
			elementsToCorrupt.forEach(el => corruptText(el));
		}

		function corruptText(element) {
			const originalText = element.textContent;
			const corruptChars = '!@#$%^&*<>/\\|{}[]01';
			let timesRun = 0;

			const corruptionInterval = setInterval(() => {
				timesRun++;
				if (timesRun > 20) {
					clearInterval(corruptionInterval);
					setTimeout(() => {
						element.textContent = originalText;
					}, 5000);
					return;
				}

				let corruptedText = '';
				for (let i = 0; i < originalText.length; i++) {
					if (Math.random() > 0.7) {
						corruptedText += corruptChars.charAt(Math.floor(Math.random() * corruptChars.length));
					} else {
						corruptedText += originalText.charAt(i);
					}
				}
				element.textContent = corruptedText;
			}, 200);
		}

		function startGlitchingTimestamp() {
			if (!els.timestamp) return;

			els.timestamp.classList.add('glitching');

			state.glitchInterval = setInterval(() => {
				const year = Math.floor(Math.random() * 50) + 2000;
				const month = Math.floor(Math.random() * 12) + 1;
				const day = Math.floor(Math.random() * 28) + 1;
				const hour = Math.floor(Math.random() * 24);
				const minute = Math.floor(Math.random() * 60);
				const second = Math.floor(Math.random() * 60);

				const glitchedDate =
					`${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')} ` +
					`${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}:${second.toString().padStart(2, '0')} UTC`;
				els.timestamp.textContent = glitchedDate;
			}, 100);
		}

		function stopGlitchingTimestamp() {
			if (state.glitchInterval) {
				clearInterval(state.glitchInterval);
				state.glitchInterval = null;
			}
			if (els.timestamp) {
				els.timestamp.classList.remove('glitching');
				updateTimestamp();
			}
		}

		// ========================================================================
		// TIMESTAMP MANAGEMENT
		// ========================================================================

		function initTimestamp() {
			updateTimestamp();
			state.timestampInterval = setInterval(updateTimestamp, 1000);
		}

		function updateTimestamp() {
			if (!els.timestamp || state.glitchInterval) return;
			const now = new Date();
			els.timestamp.textContent = now.toISOString().replace('T', ' ').split('.')[0] + ' UTC';
		}

		// ========================================================================
		// APTITUDE QUIZ SYSTEM
		// ========================================================================

		function openAptitudeTest() {
			if (els.aptitudeOverlay) {
				els.aptitudeOverlay.classList.add('active');
			}
			state.currentQuizStage = 1;
			runTest(1);
		}

		function runTest(stage) {
			state.currentQuizStage = stage;
			updateTestProgress(stage);

			switch (stage) {
				case 1:
					renderPatternQuiz();
					break;
				case 2:
					renderSimonGame();
					break;
				case 3:
					renderEthicsQuiz();
					break;
				case 4:
					renderReactionTest();
					break;
				default:
					completeQuiz();
			}
		}

		function updateTestProgress(stage) {
			if (els.testProgress) {
				els.testProgress.textContent = `TESTE ${stage} DE 4`;
			}
		}

		// ========================================================================
		// TEST 1: PATTERN RECOGNITION QUIZ
		// ========================================================================

		function renderPatternQuiz() {
			if (!els.testContent) return;

			els.testContent.innerHTML = `
				<h3 style="color: #ffffff !important; font-size: 16px; font-weight: 600; margin-bottom: 8px !important;">RECONHECIMENTO DE PADRÕES RÍTMICOS</h3>
				<p style="color: #e2e8f0 !important; font-size: 11px; margin-bottom: 16px !important; max-width: 280px;">
					Identifique o próximo padrão de batida na sequência.
				</p>
				<div style="display: flex !important; gap: 12px !important; justify-content: center !important; margin-bottom: 20px !important; font-size: 24px !important;">
					<div style="padding: 8px 14px !important; background: rgba(15,23,42,0.8) !important; border: 1px solid rgba(148,163,184,0.5) !important; border-radius: 8px !important; color: #fb923c !important;">♪</div>
					<div style="padding: 8px 14px !important; background: rgba(15,23,42,0.8) !important; border: 1px solid rgba(148,163,184,0.5) !important; border-radius: 8px !important; color: #fb923c !important;">♫</div>
					<div style="padding: 8px 14px !important; background: rgba(15,23,42,0.8) !important; border: 1px solid rgba(148,163,184,0.5) !important; border-radius: 8px !important; color: #fb923c !important;">♪♪</div>
					<div style="padding: 8px 14px !important; background: rgba(15,23,42,0.8) !important; border: 1px solid rgba(148,163,184,0.5) !important; border-radius: 8px !important; color: #fb923c !important;">♫♫</div>
					<div style="padding: 8px 14px !important; background: rgba(15,23,42,0.8) !important; border: 1px solid rgba(250,204,21,0.8) !important; border-radius: 8px !important; color: #facc15 !important; animation: pulse 1s infinite !important;">?</div>
				</div>
				<div style="display: grid !important; grid-template-columns: 1fr 1fr !important; gap: 10px !important; max-width: 280px !important; margin: 0 auto !important;">
					<button class="pattern-option test-option" style="padding: 14px !important; font-size: 20px !important; background: rgba(15,23,42,0.8) !important; border: 1px solid rgba(148,163,184,0.6) !important; border-radius: 10px !important; color: #ffffff !important; cursor: pointer !important;" data-value="♪♪♪">♪♪♪</button>
					<button class="pattern-option test-option" style="padding: 14px !important; font-size: 20px !important; background: rgba(15,23,42,0.8) !important; border: 1px solid rgba(148,163,184,0.6) !important; border-radius: 10px !important; color: #ffffff !important; cursor: pointer !important;" data-value="♪♪♫">♪♪♫</button>
					<button class="pattern-option test-option" style="padding: 14px !important; font-size: 20px !important; background: rgba(15,23,42,0.8) !important; border: 1px solid rgba(148,163,184,0.6) !important; border-radius: 10px !important; color: #ffffff !important; cursor: pointer !important;" data-value="♫♫♫" data-correct="true">♫♫♫</button>
					<button class="pattern-option test-option" style="padding: 14px !important; font-size: 20px !important; background: rgba(15,23,42,0.8) !important; border: 1px solid rgba(148,163,184,0.6) !important; border-radius: 10px !important; color: #ffffff !important; cursor: pointer !important;" data-value="♫♪♫">♫♪♫</button>
				</div>
			`;

			if (els.testBtnText) els.testBtnText.textContent = 'CONFIRMAR PADRÃO';
			if (els.testBtn) els.testBtn.disabled = true;

			// Add click handlers
			const options = els.testContent.querySelectorAll('.pattern-option');
			options.forEach(opt => {
				opt.addEventListener('click', function() {
					options.forEach(o => o.classList.remove('selected'));
					this.classList.add('selected');
					if (els.testBtn) els.testBtn.disabled = false;
				});
			});

			// Set up confirm button
			if (els.testBtn) {
				els.testBtn.onclick = function() {
					const selected = els.testContent.querySelector('.pattern-option.selected');
					if (!selected) return;

					const value = selected.getAttribute('data-value');
					if (value === CONFIG.strings.patternCorrect) {
						selected.classList.add('correct');
						showNotification('Padrão correto! Avançando...', 'success');
						setTimeout(() => runTest(2), 1000);
					} else {
						selected.classList.add('wrong');
						showNotification(CONFIG.strings.quizFailed, 'error');
						setTimeout(() => {
							options.forEach(o => o.classList.remove('selected', 'wrong'));
							els.testBtn.disabled = true;
						}, 1500);
					}
				};
			}
		}

		// ========================================================================
		// TEST 2: SIMON GAME
		// ========================================================================

		function renderSimonGame() {
			state.simonSequence = [];
			state.simonUserSequence = [];
			state.simonLevel = 1;

			if (!els.testContent) return;

			els.testContent.innerHTML = `
				<h3 style="color: #ffffff !important; font-size: 16px; font-weight: 600; margin-bottom: 8px !important;">JOGO DA MEMÓRIA: SIMON</h3>
				<p style="color: #e2e8f0 !important; font-size: 11px; margin-bottom: 16px !important; max-width: 280px;">Memorize e repita a sequência de cores.</p>
				<p style="color: #ffffff !important; margin-bottom: 16px !important;" id="simon-level">Nível: 1 de ${CONFIG.simonLevels}</p>
				<div class="simon-container">
					<div class="simon-btn simon-red" data-color="red"></div>
					<div class="simon-btn simon-blue" data-color="blue"></div>
					<div class="simon-btn simon-green" data-color="green"></div>
					<div class="simon-btn simon-yellow" data-color="yellow"></div>
				</div>
				<p style="margin-top: 16px !important; font-size: 12px !important; color: rgba(148,163,184,0.9) !important;" id="simon-status">
					Observe a sequência...
				</p>
			`;

			if (els.testBtnText) els.testBtnText.textContent = 'AGUARDE...';
			if (els.testBtn) {
				els.testBtn.disabled = true;
				els.testBtn.style.display = 'none';
			}

			// Initialize Simon game
			setTimeout(() => startSimonRound(), 1000);
		}

		function startSimonRound() {
			const colors = ['red', 'blue', 'green', 'yellow'];
			state.simonSequence.push(colors[Math.floor(Math.random() * colors.length)]);
			state.simonUserSequence = [];

			const statusEl = document.getElementById('simon-status');
			if (statusEl) statusEl.textContent = 'Observe a sequência...';

			// Disable buttons during playback
			disableSimonButtons(true);

			// Play the sequence
			playSimonSequence(0);
		}

		function playSimonSequence(index) {
			if (index >= state.simonSequence.length) {
				// Sequence complete, enable player input
				const statusEl = document.getElementById('simon-status');
				if (statusEl) statusEl.textContent = 'Sua vez! Repita a sequência.';
				disableSimonButtons(false);
				attachSimonListeners();
				return;
			}

			const color = state.simonSequence[index];
			const btn = document.querySelector(`.simon-btn[data-color="${color}"]`);

			setTimeout(() => {
				flashSimonButton(btn);
				setTimeout(() => playSimonSequence(index + 1), 600);
			}, 300);
		}

		function flashSimonButton(btn) {
			if (!btn) return;
			btn.classList.add('flash');
			playTone(btn.getAttribute('data-color'));
			setTimeout(() => btn.classList.remove('flash'), 400);
		}

		function disableSimonButtons(disabled) {
			document.querySelectorAll('.simon-btn').forEach(btn => {
				btn.style.pointerEvents = disabled ? 'none' : 'auto';
			});
		}

		function attachSimonListeners() {
			document.querySelectorAll('.simon-btn').forEach(btn => {
				btn.onclick = function() {
					const color = this.getAttribute('data-color');
					flashSimonButton(this);
					state.simonUserSequence.push(color);

					const currentIndex = state.simonUserSequence.length - 1;

					if (state.simonUserSequence[currentIndex] !== state.simonSequence[currentIndex]) {
						// Wrong! Reset this level
						const statusEl = document.getElementById('simon-status');
						if (statusEl) statusEl.textContent = 'Errado! Reiniciando...';
						showNotification('Sequência incorreta. Tente novamente.', 'error');
						disableSimonButtons(true);

						setTimeout(() => {
							state.simonSequence.pop();
							startSimonRound();
						}, 1500);
						return;
					}

					if (state.simonUserSequence.length === state.simonSequence.length) {
						// Level complete!
						state.simonLevel++;
						const levelEl = document.getElementById('simon-level');
						if (levelEl) levelEl.textContent =
							`Nível: ${state.simonLevel} de ${CONFIG.simonLevels}`;

						if (state.simonLevel > CONFIG.simonLevels) {
							// All levels complete!
							const statusEl = document.getElementById('simon-status');
							if (statusEl) statusEl.textContent = 'Excelente! Memória perfeita!';
							showNotification('Simon completo! Avançando...', 'success');
							setTimeout(() => runTest(3), 1500);
						} else {
							const statusEl = document.getElementById('simon-status');
							if (statusEl) statusEl.textContent = `Nível ${state.simonLevel - 1} completo!`;
							disableSimonButtons(true);
							setTimeout(() => startSimonRound(), 1000);
						}
					}
				};
			});
		}

		function playTone(color) {
			try {
				const audioContext = new(window.AudioContext || window.webkitAudioContext)();
				const oscillator = audioContext.createOscillator();
				const gainNode = audioContext.createGain();

				const frequencies = {
					red: 329.63,
					blue: 261.63,
					green: 392.00,
					yellow: 440.00
				};

				oscillator.frequency.value = frequencies[color] || 440;
				oscillator.type = 'sine';
				oscillator.connect(gainNode);
				gainNode.connect(audioContext.destination);
				gainNode.gain.value = 0.1;

				oscillator.start();
				setTimeout(() => {
					oscillator.stop();
					audioContext.close();
				}, 200);
			} catch (e) {
				// Audio not supported
			}
		}

		// ========================================================================
		// TEST 3: ETHICS QUIZ
		// ========================================================================

		function renderEthicsQuiz() {
			if (!els.testContent) return;

			els.testContent.innerHTML = `
				<h3 style="color: #ffffff !important; font-size: 16px; font-weight: 600; margin-bottom: 8px !important;">TESTE DE ÉTICA E RESPEITO</h3>
				<p style="color: #e2e8f0 !important; font-size: 11px; margin-bottom: 16px !important; max-width: 280px;">"Não gosto de Eletrônica com Funk / de Tribal / de Techno / de Melódico", logo..</p>
				<div style="display: flex !important; flex-direction: column !important; gap: 8px !important; max-width: 100% !important;">
					<div class="pattern-option test-option" data-value="1" style="font-size: 13px !important; padding: 12px !important; color: #ffffff !important; cursor: pointer !important;">
						Critíco e não me importo
					</div>
					<div class="pattern-option test-option" data-value="2" style="font-size: 13px !important; padding: 12px !important; color: #ffffff !important; cursor: pointer !important;">
						A depender da lua, posso hablar mal e pesar mão sobre
					</div>
					<div class="pattern-option test-option" data-value="correct" data-correct="true" style="font-size: 13px !important; padding: 12px !important; color: #ffffff !important; cursor: pointer !important;">
						É trabalho, renda, a sonoridade e arte favorita de alguem.
					</div>
					<div class="pattern-option test-option" data-value="4" style="font-size: 13px !important; padding: 12px !important; color: #ffffff !important; cursor: pointer !important;">
						Tenho dúvidas, mas hablo mal e deixo arder.
					</div>
				</div>
			`;

			if (els.testBtn) els.testBtn.style.display = 'block';
			if (els.testBtnText) els.testBtnText.textContent = 'CONFIRMAR RESPOSTA';
			if (els.testBtn) els.testBtn.disabled = true;

			const options = els.testContent.querySelectorAll('.pattern-option');
			options.forEach(opt => {
				opt.addEventListener('click', function() {
					options.forEach(o => o.classList.remove('selected'));
					this.classList.add('selected');
					if (els.testBtn) els.testBtn.disabled = false;
				});
			});

			if (els.testBtn) {
				els.testBtn.onclick = function() {
					const selected = els.testContent.querySelector('.pattern-option.selected');
					if (!selected) return;

					const value = selected.getAttribute('data-value');
					if (value === 'correct') {
						selected.classList.add('correct');
						showNotification('Resposta correta! Avançando...', 'success');
						setTimeout(() => runTest(4), 1000);
					} else {
						selected.classList.add('wrong');
						showNotification(CONFIG.strings.quizFailed, 'error');
						setTimeout(() => {
							options.forEach(o => o.classList.remove('selected', 'wrong'));
							els.testBtn.disabled = true;
						}, 1500);
					}
				};
			}
		}

		// ========================================================================
		// TEST 4: REACTION TEST
		// ========================================================================

		function renderReactionTest() {
			state.reactionCaptures = 0;

			if (!els.testContent) return;

			els.testContent.innerHTML = `
				<h3 style="color: #ffffff !important; font-size: 16px; font-weight: 600; margin-bottom: 8px !important;">TESTE DE REAÇÃO & SINCRONIA</h3>
				<p style="color: #e2e8f0 !important; font-size: 11px; margin-bottom: 16px !important; max-width: 280px;">Toque nos ícones de som piscando antes que desapareçam.</p>
				<div class="reaction-arena" id="reaction-arena">
					<div style="position: absolute !important; top: 10px !important; left: 10px !important; font-size: 12px !important; color: #fb923c !important;">CAPTURAS: <span id="capture-count">0</span>/${CONFIG.reactionTargets}</div>
				</div>
			`;

			if (els.testBtn) els.testBtn.style.display = 'none';

			// Start spawning targets
			spawnReactionTarget();
		}

		function spawnReactionTarget() {
			if (state.reactionCaptures >= CONFIG.reactionTargets) {
				// Test complete!
				showNotification('Reflexos aprovados! Finalizando...', 'success');
				setTimeout(() => completeQuiz(), 1000);
				return;
			}

			const arena = document.getElementById('reaction-arena');
			if (!arena) return;

			const target = document.createElement('div');
			target.className = 'reaction-target';
			target.innerHTML = '♪';

			// Random position within arena
			const maxX = arena.offsetWidth - 60;
			const maxY = arena.offsetHeight - 60;
			target.style.left = (Math.random() * maxX) + 'px';
			target.style.top = (Math.random() * maxY + 30) + 'px';

			target.onclick = function() {
				this.classList.add('captured');
				state.reactionCaptures++;
				const countEl = document.getElementById('capture-count');
				if (countEl) countEl.textContent = state.reactionCaptures;

				setTimeout(() => {
					this.remove();
					spawnReactionTarget();
				}, 300);
			};

			arena.appendChild(target);

			// Target disappears after 2 seconds if not clicked
			setTimeout(() => {
				if (target.parentNode && !target.classList.contains('captured')) {
					target.remove();
					spawnReactionTarget();
				}
			}, 2000);
		}

		// ========================================================================
		// QUIZ COMPLETION
		// ========================================================================

		function completeQuiz() {
			if (!els.testContent) return;

			els.testContent.innerHTML = `
				<div style="text-align: center !important; padding: 40px 20px !important;">
					<div style="font-size: 48px !important; margin-bottom: 20px !important; color: var(--success) !important;">✓</div>
					<h3 style="color: var(--success) !important;">TESTE CONCLUÍDO</h3>
					<p style="margin-top: 12px !important; color: rgba(148,163,184,0.9) !important;">
						Você demonstrou aptidão para participar da comunidade Apollo.
					</p>
				</div>
			`;

			if (els.testBtn) {
				els.testBtn.style.display = 'block';
				els.testBtn.disabled = false;
			}
			if (els.testBtnText) els.testBtnText.textContent = 'FINALIZAR REGISTRO';

			if (els.testBtn) {
				els.testBtn.onclick = function() {
					submitRegistration();
				};
			}
		}

		async function submitRegistration() {
			showNotification('Processando cadastro...', 'info');

			try {
				await new Promise(resolve => setTimeout(resolve, 1500));

				setSecurityState('success');
				showNotification('Cadastro realizado com sucesso!', 'success');

				setTimeout(() => {
					window.location.href = CONFIG.redirectAfterLogin;
				}, 2000);

			} catch (error) {
				console.error('Registration error:', error);
				showNotification('Erro ao processar cadastro. Tente novamente.', 'error');
			}
		}

		// ========================================================================
		// INSTAGRAM FIELD SETUP
		// ========================================================================

		function initInstagramField() {
			const igInput = document.querySelector('input[name="instagram"]');
			if (igInput) {
				igInput.addEventListener('input', function() {
					if (this.value.startsWith('@')) {
						this.value = this.value.substring(1);
					}
				});
			}
		}

		// ========================================================================
		// SOUNDS VALIDATION
		// ========================================================================

		function initSoundsValidation() {
			const soundsContainer = document.querySelector('.sounds-chips');
			if (!soundsContainer) return;

			const chips = soundsContainer.querySelectorAll('.quiz-chip');
			chips.forEach(chip => {
				chip.addEventListener('click', function() {
					this.classList.toggle('selected');
				});
			});
		}

		// ========================================================================
		// UTILITY FUNCTIONS
		// ========================================================================

		function showNotification(message, type = 'info') {
			const area = document.querySelector('.notification-area') || createNotificationArea();

			const alert = document.createElement('div');
			alert.className = `auth-alert auth-alert-${type}`;

			const colors = {
				error: 'var(--danger)',
				warning: 'var(--warning)',
				success: 'var(--success)',
				info: 'var(--apollo)'
			};

			alert.style.borderColor = colors[type] || colors.info;
			alert.textContent = '> ' + message;

			area.appendChild(alert);

			setTimeout(() => {
				alert.style.opacity = '0';
				setTimeout(() => alert.remove(), 300);
			}, 4000);
		}

		function createNotificationArea() {
			const area = document.createElement('div');
			area.className = 'notification-area';
			document.querySelector('.terminal-wrapper').appendChild(area);
			return area;
		}

		function shakeElement(element) {
			if (!element) return;
			element.classList.add('shake');
			setTimeout(() => element.classList.remove('shake'), 500);
		}

		function playSuccessSound() {
			try {
				const audioContext = new(window.AudioContext || window.webkitAudioContext)();
				const oscillator = audioContext.createOscillator();
				const gainNode = audioContext.createGain();

				oscillator.frequency.value = 523.25;
				oscillator.type = 'sine';
				oscillator.connect(gainNode);
				gainNode.connect(audioContext.destination);
				gainNode.gain.value = 0.08;

				oscillator.start();
				setTimeout(() => {
					oscillator.frequency.value = 659.25;
					setTimeout(() => {
						oscillator.frequency.value = 783.99;
						setTimeout(() => {
							oscillator.stop();
							audioContext.close();
						}, 150);
					}, 150);
				}, 150);
			} catch (e) {
				// Audio not supported
			}
		}

		// ========================================================================
		// EXPOSE GLOBAL FUNCTIONS (for PHP integration)
		// ========================================================================

		window.ApolloAuth = {
			setSecurityState: setSecurityState,
			showNotification: showNotification,
			validateCPF: validateCPF,
			openAptitudeTest: openAptitudeTest
		};

	})();
	</script>
</body>

</html>
