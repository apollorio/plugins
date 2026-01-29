<?php
/**
 * Apollo Navbar Template - Oficial apollo::rio
 *
 * Background: TRANSPARENT with blur mask (NO solid color)
 * CSS: background: none, backdrop-filter: blur(12px), -webkit-mask gradient
 *
 * @package Apollo_Core
 * @since 1.9.0
 * @version 2.1.1 - Fixed admin bar conflict
 */

defined('ABSPATH') || exit;

$supported_languages = array(
    'pt' => 'Português',
    'en' => 'English',
    'es' => 'Español',
    'fr' => 'Français',
    'de' => 'Deutsch',
    'it' => 'Italiano',
    'el' => 'Ελληνικά',
    'he' => 'עברית',
    'zh' => '中文',
    'ja' => '日本語',
    'ko' => '한국어',
    'ru' => 'Русский',
    'ar' => 'العربية',
    'nl' => 'Nederlands',
);

$current_lang = get_locale();
$current_lang_code = substr($current_lang, 0, 2);
if (!isset($supported_languages[$current_lang_code])) {
    $current_lang_code = 'pt';
}

// Safely remove admin bar render ONLY if show_admin_bar filter allows it.
// This prevents "Call to member function render() on null" fatal error.
if ( ! is_admin() && ! is_customize_preview() ) {
	add_filter( 'show_admin_bar', '__return_false' );
}

$is_logged_in = is_user_logged_in();
$auth_state = $is_logged_in ? 'logged' : 'guest';
$current_user = $is_logged_in ? wp_get_current_user() : null;

// 2-letter initials
$user_initial = 'G';
if ($current_user && !empty($current_user->display_name)) {
	$display_name = sanitize_text_field($current_user->display_name);
	$names = preg_split('/\s+/', trim($display_name));
	if (count($names) >= 2) {
		$user_initial = strtoupper(substr($names[0], 0, 1) . substr(end($names), 0, 1));
	} else {
		$user_initial = strtoupper(substr($display_name, 0, 2));
	}
}

// Data sources
$navbar_apps = function_exists('apollo_navbar_apps') ? apollo_navbar_apps()->get_apps() : [];
$navbar_apps = array_values(array_filter($navbar_apps, function ($app) {
	return !isset($app['active']) || $app['active'];
}));

$notifications = apply_filters('apollo_navbar_notifications', []);
$chat_conversations = apply_filters('apollo_navbar_chat_conversations', []);

// Fallback samples for preview/testing
$sample_notifications = [
	['icon_text' => 'A', 'title' => 'Alerta de Sistema', 'message' => 'Backup do servidor finalizado.', 'time' => '2 min atrás', 'color' => 'bg-red'],
	['icon_text' => 'J', 'title' => 'Júlia M.', 'message' => 'Marcou você em "Revisão de UI".', 'time' => '15 min atrás', 'color' => 'bg-blue'],
	['icon_text' => 'R', 'title' => 'Recursos Humanos', 'message' => 'Documento de férias aprovado.', 'time' => '1 hora atrás', 'color' => 'bg-green'],
];

$sample_apps = [
	['label' => 'Excel', 'icon_text' => 'XL', 'color' => 'bg-green'],
	['label' => 'Word', 'icon_text' => 'WD', 'color' => 'bg-blue'],
	['label' => 'Slide', 'icon_text' => 'PP', 'color' => 'bg-orange'],
	['label' => 'Teams', 'icon_text' => 'TM', 'color' => 'bg-purple'],
	['label' => 'Leitor', 'icon_text' => 'PDF', 'color' => 'bg-red'],
	['label' => 'Drive', 'icon_text' => 'DR', 'color' => 'bg-gray'],
	['label' => 'Meet', 'icon_text' => 'MT', 'color' => 'bg-blue'],
	['label' => 'Mais', 'icon_text' => '+', 'color' => 'bg-gray'],
];

$sample_chat = [
	['name' => 'Matheus', 'time' => 'Agora', 'msg' => 'Cara, você viu o novo layout? Ficou insano!', 'avatar' => 'M', 'color' => 'bg-gray'],
	['name' => 'Bruna', 'time' => '5m atrás', 'msg' => 'Reunião adiada para as 16h.', 'avatar' => 'B', 'color' => 'bg-purple'],
	['name' => 'Equipe Dev', 'time' => '20m atrás', 'msg' => 'Eu: Subindo o deploy em 5 minutos...', 'avatar' => 'E', 'color' => 'bg-orange', 'is_me' => true],
];

// Use real data or fallback for preview
$notif_list = !empty($notifications) ? $notifications : $sample_notifications;
$apps_list = !empty($navbar_apps) ? $navbar_apps : $sample_apps;
$chat_list = !empty($chat_conversations) ? $chat_conversations : $sample_chat;

$login_nonce = wp_create_nonce('apollo_login_action');
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
	<title>Oficial - NavBar apollo::rio</title>
	<script src="https://assets.apollo.rio.br/index.js"></script>
	<link rel="stylesheet" href="https://assets.apollo.rio.br/font/roboto/roboto.css">
	<style>
	<?php

	/* Inline CSS mirrors approved layout */
	?>:root {
		--ap-font-primary: Urbanist, system-ui, -apple-system, sans-serif;
		--ap-bg-main: #fff;
		--ap-bg-surface: #f8fafc;
		--ap-text-primary: #131517a6;
		--ap-text-secondary: #0f172ae0;
		--ap-text-muted: #13151766;
		--ap-orange-500: #FF6925;
		--ap-orange-600: #E55A1E;
		--glass-surface: #ffffffeb;
		--glass-border: #e2e8f0cc;
		--glass-shadow: 0 12px 40px #0f172a1f;
		--item-hover: #0f172a0a;
		--card-bg: #fff;
		--nav-height: 60px;
		--menu-width: 380px;
		--menu-radius: 20px;

		.language-switcher {
			background: none;
			border: none;
			cursor: pointer;
			text-align: left;
			width: 100%;
			padding: 0;
		}

		.lang-wrapper {
			position: relative;
			display: inline-block;
			overflow: hidden;
			min-width: 80px;
		}

		.lang-text {
			display: inline-block;
			transition: transform 0.3s ease, opacity 0.3s ease;
		}
	}

	*,
	::before,
	::after {
		box-sizing: border-box;
		margin: 0;
		padding: 0;
		-webkit-tap-highlight-color: transparent;
	}

	body {
		font-family: var(--ap-font-primary);
		background-color: var(--ap-bg-main);
		color: var(--ap-text-secondary);
		min-height: 100vh;
		overflow-x: hidden;
	}

	button {
		background: none;
		border: none;
		cursor: pointer;
		font-family: inherit;
	}

	a {
		text-decoration: none;
		color: inherit;
		transition: color .2s;
	}

	a:hover,
	.txt-orange:hover {
		color: var(--ap-orange-500) !important;
	}

	.txt-orange {
		color: var(--ap-orange-500) !important;
	}

	.navbar {
		display: flex;
		justify-content: flex-end;
		align-items: center;
		padding: 0 1.5rem;
		height: var(--nav-height);
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		z-index: 1000;
		gap: 8px;
	}

	.clock-pill {
		padding: .4rem 1rem;
		border-radius: 99px;
		background: transparent;
		font-size: .75rem;
		font-weight: 300;
		color: var(--ap-text-primary);
		cursor: default;
		letter-spacing: .02em;
		transition: all .3s ease;
	}

	.navbar[data-auth="guest"] .clock-pill {
		font-size: .8rem;
		font-weight: 400;
		color: var(--ap-text-secondary);
		letter-spacing: .03em;
	}

	.nav-btn {
		width: 40px;
		height: 40px;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		color: var(--ap-text-primary);
		transition: all .2s ease;
		position: relative;
	}

	.nav-btn:hover,
	.nav-btn[aria-expanded="true"] {
		background: var(--item-hover);
		color: var(--ap-orange-500);
		transform: scale(1.05);
	}

	.nav-btn svg {
		width: 19px;
		height: 19px;
	}

	.avatar-btn {
		background: var(--ap-bg-surface);
		border: 1px solid var(--glass-border);
		font-weight: 700;
		font-size: .9rem;
	}

	.navbar[data-auth="guest"] .nav-btn {
		opacity: .8;
	}

	.navbar[data-auth="guest"] .nav-btn:hover {
		opacity: 1;
	}

	.navbar[data-auth="guest"] [data-auth-require="logged"] {
		display: none !important;
	}

	.navbar[data-auth="logged"] [data-auth-require="guest"] {
		display: none !important;
	}

	.badge {
		position: absolute;
		top: 18px;
		left: 18px;
		width: 5px;
		height: 5px;
		border-radius: 50%;
		z-index: 99;
		pointer-events: none;
		transition: background-color .2s ease, box-shadow .2s ease;
	}

	.badge[data-notif="true"] {
		background: #ff8c00;
		box-shadow: 0 0 20px #ff8c00, 0 0 40px #ffc13b, 0 0 60px #ffaa3b, 0 0 100px #ff8d3b;
		opacity: .7;
		animation: pulsar 2s infinite ease-in;
	}

	.badge[data-notif="false"] {
		background: transparent;
		box-shadow: none;
		animation: none;
	}

	@keyframes pulsar {
		0% {
			transform: scale(1);
			box-shadow: 0 0 0 0 #ff8c00b3;
		}

		100% {
			transform: scale(1.03);
			filter: brightness(1.3) saturate(1.5);
			box-shadow: 0 0 0 15px #ff8c0000;
		}
	}

	.dropdown-menu {
		display: none;
		position: absolute;
		top: calc(var(--nav-height) + 10px);
		right: 10px;
		width: var(--menu-width);
		max-width: 90vw;
		max-height: 90vh;
		overflow-y: auto;
		overflow-x: hidden;
		background: var(--glass-surface);
		backdrop-filter: blur(30px) saturate(180%);
		-webkit-backdrop-filter: blur(30px) saturate(180%);
		border: 1px solid var(--glass-border);
		border-radius: var(--menu-radius);
		box-shadow: var(--glass-shadow);
		z-index: 999;
		flex-direction: column;
		opacity: 0;
		transform: translateY(-10px) scale(0.98);
		transform-origin: top right;
		transition: opacity .25s cubic-bezier(0.16, 1, 0.3, 1), transform .25s cubic-bezier(0.16, 1, 0.3, 1);
		overscroll-behavior: contain;
	}

	.dropdown-menu.active {
		display: flex;
		opacity: 1;
		transform: translateY(0) scale(1);
	}

	.dropdown-menu::-webkit-scrollbar {
		width: 4px;
	}

	.dropdown-menu::-webkit-scrollbar-track {
		background: transparent;
	}

	.dropdown-menu::-webkit-scrollbar-thumb {
		background: #0000001a;
		border-radius: 4px;
	}

	.section-title {
		font-size: .7rem;
		text-transform: uppercase;
		letter-spacing: .08em;
		color: var(--ap-text-muted);
		font-weight: 700;
		padding: 1rem 1.25rem .5rem;
		display: flex;
		justify-content: space-between;
		align-items: center;
		user-select: none;
		-webkit-user-select: none;
	}

	.section-title:first-child {
		padding-top: 1.25rem;
	}

	.ario-see-all {
		font-size: .7rem;
		font-weight: 400;
		color: var(--ap-orange-500);
		cursor: pointer;
		text-transform: none;
		letter-spacing: 0;
	}

	#menu-notif {
		padding: .5rem 0;
	}

	.notif-item {
		padding: .75rem 1.25rem;
		display: flex;
		gap: .75rem;
		transition: background .15s ease;
		cursor: pointer;
		user-select: none;
		-webkit-user-select: none;
		-webkit-touch-callout: none;
	}

	.notif-item:hover {
		background: var(--item-hover);
	}

	.notif-item:active {
		background: #0f172a12;
		transform: scale(0.98);
	}

	.notif-icon {
		width: 36px;
		height: 36px;
		flex-shrink: 0;
		border-radius: 10px;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: .8rem;
		font-weight: 700;
		color: #fff;
	}

	.notif-content {
		display: flex;
		flex-direction: column;
		gap: 2px;
	}

	.notif-title {
		font-size: .85rem;
		font-weight: 600;
		color: var(--ap-text-secondary);
	}

	.notif-desc {
		font-size: .8rem;
		color: var(--ap-text-primary);
		line-height: 1.3;
	}

	.notif-time {
		font-size: .7rem;
		color: var(--ap-text-muted);
		margin-top: 2px;
	}

	.empty-state {
		padding: 3rem 1rem;
		text-align: center;
		color: var(--ap-text-muted);
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: .5rem;
		user-select: none;
		-webkit-user-select: none;
	}

	.empty-state svg {
		width: 32px;
		height: 32px;
		opacity: .5;
	}

	#menu-app {
		padding: 0 1rem 1.25rem;
		width: 340px;
	}

	.apps-grid {
		display: grid;
		grid-template-columns: repeat(4, 1fr);
		gap: 1rem;
		padding: 0 .25rem;
	}

	.app-item {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: .5rem;
		transition: transform .15s ease;
		user-select: none;
		-webkit-user-select: none;
		-webkit-touch-callout: none;
	}

	.app-item:hover {
		transform: translateY(-3px);
	}

	.app-item:active {
		transform: scale(0.95);
	}

	.app-icon {
		width: 48px;
		height: 48px;
		border-radius: 12px;
		display: flex;
		align-items: center;
		justify-content: center;
		font-weight: 700;
		color: #fff;
		font-size: 1rem;
		box-shadow: 0 4px 10px #0000000d;
	}

	.app-label {
		font-size: .7rem;
		color: var(--ap-text-secondary);
	}

	.scroll-row {
		display: flex;
		gap: .75rem;
		overflow-x: auto;
		overflow-y: hidden;
		padding: .5rem .25rem;
		scrollbar-width: thin;
		scroll-behavior: smooth;
		-webkit-overflow-scrolling: touch;
		overscroll-behavior-x: contain;
		scroll-snap-type: x proximity;
	}

	.scroll-row::-webkit-scrollbar {
		height: 4px;
	}

	.scroll-row::-webkit-scrollbar-track {
		background: transparent;
	}

	.scroll-row::-webkit-scrollbar-thumb {
		background: #0000001a;
		border-radius: 2px;
	}

	.scroll-row.scroll-active {
		outline: 2px solid var(--ap-orange-500);
		outline-offset: -2px;
		border-radius: 12px;
	}

	.msg-card {
		flex: 0 0 13rem;
		background: var(--card-bg);
		border: 1px solid var(--glass-border);
		border-radius: .9rem;
		padding: .8rem;
		display: flex;
		flex-direction: column;
		gap: .5rem;
		transition: all .2s cubic-bezier(0.25, 0.8, 0.25, 1);
		cursor: pointer;
		user-select: none;
		-webkit-user-select: none;
		-webkit-touch-callout: none;
		scroll-snap-align: start;
		touch-action: pan-x pan-y;
	}

	.msg-card:hover {
		background: #fff;
		transform: translateY(-4px);
		box-shadow: 0 8px 20px -5px #0000001a;
	}

	.msg-card:active {
		transform: scale(0.97);
		box-shadow: 0 4px 10px -3px #0000001a;
	}

	.msg-header {
		display: flex;
		align-items: center;
		gap: .5rem;
	}

	.msg-avatar {
		width: 2rem;
		height: 2rem;
		border-radius: 50%;
		background: #334155;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: .7rem;
		font-weight: 700;
		color: #fff;
		flex-shrink: 0;
	}

	.msg-info {
		display: flex;
		flex-direction: column;
		line-height: 1.1;
		overflow: hidden;
	}

	.msg-name {
		font-size: .8rem;
		font-weight: 600;
		color: var(--ap-text-secondary);
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}

	.msg-time {
		font-size: .65rem;
		color: var(--ap-text-muted);
	}

	.msg-preview {
		font-size: .75rem;
		color: var(--ap-text-primary);
		line-height: 1.3;
		display: -webkit-box;
		-webkit-line-clamp: 2;
		-webkit-box-orient: vertical;
		overflow: hidden;
	}

	.msg-preview.me {
		font-style: italic;
		color: var(--ap-text-muted);
	}

	.load-more-card {
		justify-content: center;
		align-items: center;
		background: #00000005;
		border-style: dashed;
		min-width: 8rem;
		flex: 0 0 8rem;
	}

	.load-more-card:hover {
		background: #0000000a;
	}

	.load-more-card:active {
		background: #00000012;
	}

	.load-more-text {
		font-weight: 600;
		color: var(--ap-orange-500);
		font-size: .85rem;
	}

	#menu-profile {
		padding: .5rem;
		min-width: 180px;
		width: auto;
	}

	.profile-link {
		padding: .6rem .75rem;
		border-radius: 8px;
		font-size: .85rem;
		color: var(--ap-text-secondary);
		display: block;
		transition: background .15s ease;
		user-select: none;
		-webkit-user-select: none;
		-webkit-touch-callout: none;
	}

	.profile-link:hover {
		background: var(--item-hover);
	}

	.profile-link:active {
		background: #0f172a12;
	}

	.profile-link.danger {
		color: #ef4444;
	}

	.menu-divider {
		height: 1px;
		background: var(--glass-border);
		margin: .5rem 0;
	}

	.login-section {
		padding: 1.25rem;
		transition: all .4s cubic-bezier(0.16, 1, 0.3, 1);
	}

	.login-section.fade-out {
		opacity: 0;
		transform: translateY(-10px);
		pointer-events: none;
	}

	.login-section.hidden {
		display: none;
	}

	.login-header {
		text-align: center;
		margin-bottom: 1.5rem;
	}

	.login-title {
		font-size: 1.1rem;
		font-weight: 600;
		color: var(--ap-text-secondary);
		margin-bottom: .25rem;
		letter-spacing: -.01em;
	}

	.login-subtitle {
		font-size: .75rem;
		color: var(--ap-text-muted);
		font-weight: 400;
	}

	.input-group {
		margin-bottom: 1rem;
		position: relative;
	}

	.input-label {
		display: block;
		font-size: .65rem;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: .1em;
		color: var(--ap-text-muted);
		margin-bottom: .4rem;
		padding-left: .125rem;
	}

	.input-field {
		width: 100%;
		padding: .75rem 1rem;
		font-size: .875rem;
		font-family: var(--ap-font-primary);
		color: var(--ap-text-secondary);
		background: #00000005;
		border: 1px solid transparent;
		border-radius: 12px;
		outline: none;
		transition: all .2s ease;
	}

	.input-field::placeholder {
		color: var(--ap-text-muted);
		opacity: .6;
	}

	.input-field:hover {
		background: #00000008;
	}

	.input-field:focus {
		background: #fff;
		border-color: var(--ap-orange-500);
		box-shadow: 0 0 0 3px #ff692510;
	}

	.checkbox-group {
		display: flex;
		align-items: center;
		gap: .5rem;
		margin: 1rem 0;
		cursor: pointer;
		user-select: none;
		-webkit-user-select: none;
	}

	.checkbox-input {
		position: absolute;
		opacity: 0;
		width: 0;
		height: 0;
	}

	.checkbox-custom {
		width: 18px;
		height: 18px;
		border: 1.5px solid var(--glass-border);
		border-radius: 5px;
		display: flex;
		align-items: center;
		justify-content: center;
		transition: all .2s ease;
		flex-shrink: 0;
	}

	.checkbox-custom svg {
		width: 12px;
		height: 12px;
		stroke: #fff;
		stroke-width: 2.5;
		opacity: 0;
		transform: scale(0.5);
		transition: all .15s ease;
	}

	.checkbox-input:checked+.checkbox-custom {
		background: var(--ap-orange-500);
		border-color: var(--ap-orange-500);
	}

	.checkbox-input:checked+.checkbox-custom svg {
		opacity: 1;
		transform: scale(1);
	}

	.checkbox-input:focus+.checkbox-custom {
		box-shadow: 0 0 0 3px #ff692515;
	}

	.checkbox-label {
		font-size: .8rem;
		color: var(--ap-text-primary);
	}

	.login-btn {
		width: 100%;
		padding: .875rem 1.5rem;
		font-size: .875rem;
		font-weight: 600;
		font-family: var(--ap-font-primary);
		color: #fff;
		background: linear-gradient(135deg, var(--ap-orange-500), var(--ap-orange-600));
		border: none;
		border-radius: 12px;
		cursor: pointer;
		transition: all .2s ease;
		position: relative;
		overflow: hidden;
	}

	.login-btn:hover {
		transform: translateY(-2px);
		box-shadow: 0 8px 20px -5px #ff692540;
	}

	.login-btn:active {
		transform: translateY(0);
		box-shadow: 0 4px 10px -3px #ff692540;
	}

	.login-btn:disabled {
		opacity: .6;
		cursor: not-allowed;
		transform: none;
		box-shadow: none;
	}

	.login-btn .spinner {
		display: none;
		width: 18px;
		height: 18px;
		border: 2px solid #ffffff40;
		border-top-color: #fff;
		border-radius: 50%;
		animation: spin .6s linear infinite;
		margin: 0 auto;
	}

	.login-btn.loading .btn-text {
		opacity: 0;
	}

	.login-btn.loading .spinner {
		display: block;
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
	}

	@keyframes spin {
		to {
			transform: translate(-50%, -50%) rotate(360deg);
		}
	}

	.login-links {
		margin-top: 1.25rem;
		display: flex;
		flex-direction: column;
		gap: .5rem;
		text-align: center;
	}

	.login-link {
		font-size: .75rem;
		color: var(--ap-text-muted);
		transition: color .2s ease;
	}

	.login-link:hover {
		color: var(--ap-orange-500) !important;
	}

	.login-link.register {
		font-weight: 600;
		color: var(--ap-text-secondary);
	}

	.login-divider {
		display: flex;
		align-items: center;
		gap: 1rem;
		margin: 1.25rem 0;
	}

	.login-divider::before,
	.login-divider::after {
		content: '';
		flex: 1;
		height: 1px;
		background: var(--glass-border);
	}

	.login-divider span {
		font-size: .65rem;
		text-transform: uppercase;
		letter-spacing: .1em;
		color: var(--ap-text-muted);
	}

	.logged-content {
		transition: all .4s cubic-bezier(0.16, 1, 0.3, 1);
	}

	.logged-content.hidden {
		display: none;
	}

	.logged-content.reveal-up {
		animation: revealUp .5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
	}

	@keyframes revealUp {
		from {
			opacity: 0;
			transform: translateY(20px);
		}

		to {
			opacity: 1;
			transform: translateY(0);
		}
	}

	.bg-blue {
		background: linear-gradient(135deg, #3b82f6, #2563eb);
	}

	.bg-green {
		background: linear-gradient(135deg, #22c55e, #16a34a);
	}

	.bg-orange {
		background: linear-gradient(135deg, #f97316, #ea580c);
	}

	.bg-purple {
		background: linear-gradient(135deg, #a855f7, #9333ea);
	}

	.bg-red {
		background: linear-gradient(135deg, #ef4444, #dc2626);
	}

	.bg-gray {
		background: #64748b;
	}

	@media (hover: none) and (pointer: coarse) {
		.nav-btn:hover {
			background: none;
			transform: none;
		}

		.nav-btn:active {
			background: var(--item-hover);
			transform: scale(1.05);
		}

		.msg-card:hover {
			transform: none;
			box-shadow: none;
		}

		.msg-card:active {
			transform: scale(0.97);
			background: #fafafa;
		}

		.app-item:hover {
			transform: none;
		}

		.app-item:active {
			transform: scale(0.95);
		}

		.login-btn:hover {
			transform: none;
			box-shadow: none;
		}

		.login-btn:active {
			transform: scale(0.98);
		}
	}
	</style>
</head>

<body>

	<nav class="navbar" id="apollo-navbar" data-auth="<?php echo esc_attr($auth_state); ?>">
		<div class="clock-pill" id="digital-clock">--:--:--</div>

		<?php if ($is_logged_in): ?>
		<button id="btn-notif" class="nav-btn" aria-label="Notificações" aria-expanded="false"
			aria-controls="menu-notif" data-auth-require="logged">
			<div class="badge" id="notif-badge" data-notif="<?php echo !empty($notifications) ? 'true' : 'false'; ?>">
			</div>
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
				<path
					d="M6.11629 20.0868C3.62137 18.2684 2 15.3236 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 15.3236 20.3786 18.2684 17.8837 20.0868L16.8692 18.348C18.7729 16.8856 20 14.5861 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12C4 14.5861 5.2271 16.8856 7.1308 18.348L6.11629 20.0868ZM8.14965 16.6018C6.83562 15.5012 6 13.8482 6 12C6 8.68629 8.68629 6 12 6C15.3137 6 18 8.68629 18 12C18 13.8482 17.1644 15.5012 15.8503 16.6018L14.8203 14.8365C15.549 14.112 16 13.1087 16 12C16 9.79086 14.2091 8 12 8C9.79086 8 8 9.79086 8 12C8 13.1087 8.45105 14.112 9.17965 14.8365L8.14965 16.6018ZM11 13H13L14 22H10L11 13Z">
				</path>
			</svg>
		</button>
		<?php endif; ?>

		<button id="btn-apps" class="nav-btn" aria-label="Aplicativos" aria-expanded="false" aria-controls="menu-app">
			<svg viewBox="0 0 24 24" fill="currentColor">
				<path
					d="M4 8h4V4H4v4zm6 12h4v-4h-4v4zm-6 0h4v-4H4v4zm0-6h4v-4H4v4zm6 0h4v-4h-4v4zm6-10v4h4V4h-4zm-6 4h4V4h-4v4zm6 6h4v-4h-4v4zm0 6h4v-4h-4v4z">
				</path>
			</svg>
		</button>

		<?php if ($is_logged_in): ?>
		<button id="btn-profile" class="nav-btn avatar-btn" aria-label="Perfil" aria-expanded="false"
			aria-controls="menu-profile" data-auth-require="logged">
			<?php echo esc_html($user_initial); ?>
		</button>
		<?php endif; ?>
	</nav>

	<?php if ($is_logged_in): ?>
	<div id="menu-notif" class="dropdown-menu" role="menu" aria-hidden="true">
		<div class="section-title">
			<span>Notificações</span>
			<a href="#" class="ario-see-all">Ver todas</a>
		</div>

		<div class="empty-state" id="notif-empty" style="display: none;">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
				<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
				<path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
			</svg>
			<span>Sem notificações</span>
		</div>

		<div id="notif-list">
			<?php foreach ($notif_list as $notif): ?>
			<div class="notif-item">
				<div class="notif-icon <?php echo esc_attr($notif['color'] ?? 'bg-gray'); ?>">
					<?php echo esc_html($notif['icon_text'] ?? ''); ?></div>
				<div class="notif-content">
					<span class="notif-title"><?php echo esc_html($notif['title'] ?? ''); ?></span>
					<span class="notif-desc"><?php echo esc_html($notif['message'] ?? ''); ?></span>
					<span class="notif-time"><?php echo esc_html($notif['time'] ?? ''); ?></span>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

	<div id="menu-app" class="dropdown-menu" role="menu" aria-hidden="true">
		<div class="section-title">Aplicativos</div>

		<div class="apps-grid">
			<?php foreach ($apps_list as $app): ?>
			<a href="<?php echo esc_url($app['url'] ?? '#'); ?>" class="app-item"
				target="<?php echo esc_attr($app['target'] ?? '_self'); ?>">
				<div class="app-icon <?php echo esc_attr($app['color'] ?? 'bg-gray'); ?>">
					<?php echo esc_html($app['icon_text'] ?? ''); ?></div>
				<span class="app-label"><?php echo esc_html($app['label'] ?? ''); ?></span>
			</a>
			<?php endforeach; ?>
		</div>

		<div class="login-section" id="login-section" data-auth-section="guest"
			<?php echo $is_logged_in ? 'style="display:none"' : ''; ?>>
			<div class="login-divider"><span>Acesso</span></div>
			<div class="login-header">
				<div class="login-title">Bem-vindo ao Apollo</div>
				<div class="login-subtitle">Entre para acessar todos os recursos</div>
			</div>

			<form id="apollo-login-form" action="<?php echo esc_url(site_url('wp-login.php', 'login_post')); ?>"
				method="post" autocomplete="off">
				<?php wp_nonce_field('apollo_login_action', 'apollo_login_nonce'); ?>
				<div class="input-group">
					<label class="input-label" for="login-user">Usuário</label>
					<input type="text" name="user" id="login-user" class="input-field" placeholder="seu@email.com"
						autocomplete="username" required>
				</div>
				<div class="input-group">
					<label class="input-label" for="login-pass">Código</label>
					<input type="password" name="pass" id="login-pass" class="input-field" placeholder="••••••••"
						autocomplete="current-password" required>
				</div>
				<label class="checkbox-group">
					<input type="checkbox" name="remember" value="1" class="checkbox-input" id="login-remember">
					<span class="checkbox-custom"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
							<polyline points="20 6 9 17 4 12"></polyline>
						</svg></span>
					<span class="checkbox-label">Permanecer sempre conectado</span>
				</label>
				<button type="submit" class="login-btn" id="login-submit">
					<span class="btn-text">Entrar</span>
					<span class="spinner"></span>
				</button>
				<input type="hidden" name="redirect_to" value="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
			</form>

			<div class="login-links">
				<a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="login-link" target="_blank">Esqueci meu
					código de acesso</a>
				<?php if (get_option('users_can_register')): ?>
				<a href="<?php echo esc_url(home_url('/registre')); ?>" class="login-link register">Registrar nova conta</a>
				<?php endif; ?>
			</div>
		</div>

		<?php if ($is_logged_in): ?>
		<div class="logged-content" id="logged-content" data-auth-section="logged">
			<div class="section-title"><span>Notificações</span><a href="#" class="ario-see-all">Ver mais</a></div>
			<div class="scroll-row" data-scroll-capture="true">
				<?php foreach ($notif_list as $notif): ?>
				<div class="msg-card">
					<div class="msg-header">
						<div class="msg-avatar <?php echo esc_attr($notif['color'] ?? 'bg-gray'); ?>">
							<?php echo esc_html($notif['icon_text'] ?? ''); ?></div>
						<div class="msg-info">
							<span class="msg-name"><?php echo esc_html($notif['title'] ?? ''); ?></span>
							<span class="msg-time"><?php echo esc_html($notif['time'] ?? ''); ?></span>
						</div>
					</div>
					<div class="msg-preview"><?php echo esc_html($notif['message'] ?? ''); ?></div>
				</div>
				<?php endforeach; ?>
			</div>

			<div class="section-title"><span>Chat</span><a href="#" class="ario-see-all">Ver mais</a></div>
			<div class="scroll-row" id="chat-scroller" data-scroll-capture="true">
				<?php foreach ($chat_list as $chat): ?>
				<div class="msg-card">
					<div class="msg-header">
						<div class="msg-avatar <?php echo esc_attr($chat['color'] ?? 'bg-gray'); ?>">
							<?php echo esc_html($chat['avatar'] ?? ''); ?></div>
						<div class="msg-info">
							<span class="msg-name"><?php echo esc_html($chat['name'] ?? ''); ?></span>
							<span class="msg-time"><?php echo esc_html($chat['time'] ?? ''); ?></span>
						</div>
					</div>
					<div class="msg-preview <?php echo !empty($chat['is_me']) ? 'me' : ''; ?>">
						<?php echo !empty($chat['is_me']) ? 'Eu: ' : ''; ?><?php echo esc_html($chat['msg'] ?? ''); ?>
					</div>
				</div>
				<?php endforeach; ?>
				<div class="msg-card load-more-card" id="load-more-btn"><span class="load-more-text">+ Mais</span></div>
			</div>
		</div>
		<?php endif; ?>
	</div>

	<?php if ($is_logged_in): ?>
	<div id="menu-profile" class="dropdown-menu" role="menu" aria-hidden="true">
		<div class="section-title">Conta</div>
		<a href="<?php echo esc_url(get_edit_profile_url($current_user->ID)); ?>"
			class="profile-link"><?php echo esc_html($current_user->display_name); ?></a>
		<a href="#" class="profile-link">Configurações</a>
		<div class="menu-divider"></div>
		<a href="#" class="profile-link" id="dark-mode-toggle">Modo Noturno</a>
		<button type="button" class="profile-link language-switcher" id="language-switcher"
			data-current="<?php echo esc_attr($current_lang_code); ?>"
			data-languages="<?php echo esc_attr(json_encode($supported_languages)); ?>">
			Idioma: <span class="lang-wrapper"><span
					class="lang-text"><?php echo esc_html($supported_languages[$current_lang_code]); ?></span></span>
		</button>
		<div class="menu-divider"></div>
		<a href="#" class="profile-link txt-orange">Suporte</a>
		<a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="profile-link danger">Sair</a>
	</div>
	<?php endif; ?>

	<script>
	(function() {
		'use strict';

		if (window.__APOLLO_NAVBAR__) return;
		window.__APOLLO_NAVBAR__ = 1;

		var d = document;
		var w = window;

		function init() {
			var navbar = d.getElementById('apollo-navbar');
			var clockEl = d.getElementById('digital-clock');
			var authState = navbar ? navbar.getAttribute('data-auth') : 'guest';

			var toggles = [{
					btn: 'btn-notif',
					menu: 'menu-notif',
					auth: 'logged'
				},
				{
					btn: 'btn-apps',
					menu: 'menu-app',
					auth: 'all'
				},
				{
					btn: 'btn-profile',
					menu: 'menu-profile',
					auth: 'logged'
				}
			];

			if (clockEl) {
				(function updateClock() {
					var now = new Date();
					clockEl.textContent = now.toLocaleTimeString('pt-BR', {
						hour12: false
					});
					setTimeout(updateClock, 1000 - (Date.now() % 1000));
				})();
			}

			function updateAuthUI(newState) {
				if (!navbar) return;
				navbar.setAttribute('data-auth', newState);
				authState = newState;

				var loginSection = d.getElementById('login-section');
				var loggedContent = d.getElementById('logged-content');

				if (newState === 'logged') {
					if (loginSection) loginSection.classList.add('hidden');
					if (loggedContent) loggedContent.classList.remove('hidden');
				} else {
					if (loginSection) loginSection.classList.remove('hidden');
					if (loggedContent) loggedContent.classList.add('hidden');
				}
			}

			function closeAll() {
				toggles.forEach(function(t) {
					var menu = d.getElementById(t.menu);
					var btn = d.getElementById(t.btn);
					if (menu && btn) {
						menu.classList.remove('active');
						menu.setAttribute('aria-hidden', 'true');
						btn.setAttribute('aria-expanded', 'false');
					}
				});
			}

			toggles.forEach(function(t) {
				var btn = d.getElementById(t.btn);
				var menu = d.getElementById(t.menu);
				if (!btn || !menu) return;

				btn.addEventListener('click', function(e) {
					e.preventDefault();
					e.stopPropagation();

					if (t.auth === 'logged' && authState !== 'logged') return;

					var isActive = menu.classList.contains('active');
					closeAll();

					if (!isActive) {
						menu.style.display = 'flex';
						menu.offsetHeight;
						menu.classList.add('active');
						menu.setAttribute('aria-hidden', 'false');
						btn.setAttribute('aria-expanded', 'true');
					}
				});

				menu.addEventListener('click', function(e) {
					e.stopPropagation();
				});
			});

			d.addEventListener('click', closeAll);
			d.addEventListener('keydown', function(e) {
				if (e.key === 'Escape') closeAll();
			});

			function updateNotifState() {
				var list = d.getElementById('notif-list');
				var empty = d.getElementById('notif-empty');
				var badge = d.getElementById('notif-badge');
				if (!list || !badge) return;
				var count = list.children.length;
				if (empty) empty.style.display = count === 0 ? 'flex' : 'none';
				list.style.display = count === 0 ? 'none' : 'block';
				badge.setAttribute('data-notif', count > 0 ? 'true' : 'false');
			}
			if (authState === 'logged') updateNotifState();

			var loginForm = d.getElementById('apollo-login-form');
			var loginSection = d.getElementById('login-section');
			var loggedContent = d.getElementById('logged-content');
			var loginBtn = d.getElementById('login-submit');

			if (loginForm) {
				loginForm.addEventListener('submit', function(e) {
					e.preventDefault();

					var user = d.getElementById('login-user').value.trim();
					var pass = d.getElementById('login-pass').value;
					var remember = d.getElementById('login-remember').checked;
					var nonce = d.querySelector('[name="apollo_login_nonce"]').value;

					if (!user || !pass) return;

					loginBtn.classList.add('loading');
					loginBtn.disabled = true;

					// Real AJAX Login to WordPress
					fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded',
						},
						body: 'action=apollo_navbar_login&user=' + encodeURIComponent(user) +
							  '&pass=' + encodeURIComponent(pass) +
							  '&remember=' + (remember ? '1' : '0') +
							  '&nonce=' + encodeURIComponent(nonce)
					})
					.then(function(response) { return response.json(); })
					.then(function(data) {
						if (data.success) {
							handleLoginSuccess();
							// Reload page after animation to get real logged-in state
							setTimeout(function() {
								window.location.reload();
							}, 800);
						} else {
							handleLoginError(data.data || 'Credenciais inválidas');
							// Shake form on error
							loginForm.classList.add('shake');
							setTimeout(function() {
								loginForm.classList.remove('shake');
							}, 500);
						}
					})
					.catch(function(err) {
						handleLoginError('Erro de conexão');
					});
				});
			}

			function handleLoginSuccess() {
				loginBtn.classList.remove('loading');
				loginBtn.disabled = false;

				if (loginSection) {
					loginSection.classList.add('fade-out');
					setTimeout(function() {
						loginSection.classList.add('hidden');
						loginSection.classList.remove('fade-out');
						if (loggedContent) {
							loggedContent.classList.remove('hidden');
							loggedContent.classList.add('reveal-up');
							setTimeout(function() {
								loggedContent.classList.remove('reveal-up');
							}, 500);
						}
						updateAuthUI('logged');
						updateNotifState();
					}, 400);
				}
			}

			function handleLoginError(msg) {
				loginBtn.classList.remove('loading');
				loginBtn.disabled = false;
				// Show error message
				var errorEl = d.getElementById('login-error');
				if (!errorEl) {
					errorEl = d.createElement('div');
					errorEl.id = 'login-error';
					errorEl.style.cssText = 'color:#ef4444;font-size:12px;text-align:center;margin-top:10px;padding:8px;background:rgba(239,68,68,0.1);border-radius:8px;';
					loginBtn.parentNode.insertBefore(errorEl, loginBtn.nextSibling);
				}
				errorEl.textContent = msg;
				errorEl.style.display = 'block';
				setTimeout(function() {
					errorEl.style.display = 'none';
				}, 4000);
				console.error('Login failed:', msg);
			}

			var loadBtn = d.getElementById('load-more-btn');
			var chatScroller = d.getElementById('chat-scroller');

			if (loadBtn && chatScroller) {
				loadBtn.addEventListener('click', function(e) {
					e.stopPropagation();
					var text = loadBtn.querySelector('.load-more-text');
					if (!text) return;
					text.textContent = '...';
					loadBtn.style.opacity = '0.5';
					loadBtn.style.pointerEvents = 'none';

					setTimeout(function() {
						var newMsgs = [{
								name: 'Lucas',
								time: '1h',
								msg: 'Preciso do relatório.',
								avatar: 'L',
								color: 'bg-green'
							},
							{
								name: 'Ana',
								time: '2h',
								msg: 'Reunião confirmada.',
								avatar: 'A',
								color: 'bg-blue'
							}
						];

						newMsgs.forEach(function(m) {
							var card = d.createElement('div');
							card.className = 'msg-card';
							card.innerHTML = '<div class="msg-header">' +
								'<div class="msg-avatar ' + m.color + '">' + m.avatar +
								'</div>' +
								'<div class="msg-info">' +
								'<span class="msg-name">' + m.name + '</span>' +
								'<span class="msg-time">' + m.time + '</span>' +
								'</div>' +
								'</div>' +
								'<div class="msg-preview">' + m.msg + '</div>';
							chatScroller.insertBefore(card, loadBtn);
						});

						text.textContent = '+ Mais';
						loadBtn.style.opacity = '1';
						loadBtn.style.pointerEvents = 'auto';
						chatScroller.scrollBy({
							left: 150,
							behavior: 'smooth'
						});
					}, 400);
				});
			}

			var scrollRows = d.querySelectorAll('[data-scroll-capture="true"]');

			function canScrollLeft(el) {
				return el.scrollLeft > 0;
			}

			function canScrollRight(el) {
				return el.scrollLeft < (el.scrollWidth - el.clientWidth - 1);
			}

			function handleWheel(e) {
				var target = e.target.closest('[data-scroll-capture="true"]');
				if (!target) return;
				var deltaY = e.deltaY;
				var deltaX = e.deltaX;
				if (Math.abs(deltaY) > Math.abs(deltaX)) {
					var canRight = canScrollRight(target);
					var canLeft = canScrollLeft(target);
					if ((deltaY > 0 && canRight) || (deltaY < 0 && canLeft)) {
						e.preventDefault();
						e.stopPropagation();
						target.scrollLeft += deltaY;
						target.classList.add('scroll-active');
						clearTimeout(target._scrollTimer);
						target._scrollTimer = setTimeout(function() {
							target.classList.remove('scroll-active');
						}, 150);
					} else {
						target.classList.remove('scroll-active');
					}
				}
			}

			scrollRows.forEach(function(row) {
				row.addEventListener('wheel', handleWheel, {
					passive: false
				});
			});

			var touchTarget = null;
			d.addEventListener('touchstart', function(e) {
				touchTarget = e.target.closest('.msg-card, .notif-item, .app-item, .profile-link');
				if (touchTarget) touchTarget.style.transition = 'none';
			}, {
				passive: true
			});

			d.addEventListener('touchend', function() {
				if (touchTarget) {
					touchTarget.style.transition = '';
					touchTarget = null;
				}
			}, {
				passive: true
			});

			d.addEventListener('contextmenu', function(e) {
				if (e.target.closest('.msg-card, .notif-item, .app-item')) {
					e.preventDefault();
				}
			});

			updateAuthUI(authState);
		}

		if (d.readyState === 'loading') {
			d.addEventListener('DOMContentLoaded', init);
		} else {
			init();
		}
	})();

	// Language switcher
	var langSwitcher = d.getElementById('language-switcher');
	if (langSwitcher) {
		var languages = JSON.parse(langSwitcher.dataset.languages);
		var currentLang = langSwitcher.dataset.current;
		var langKeys = Object.keys(languages);
		var currentIndex = langKeys.indexOf(currentLang);
		var langText = langSwitcher.querySelector('.lang-text');
		langSwitcher.addEventListener('click', function(e) {
			e.preventDefault();
			// Slide out
			langText.style.transform = 'translateX(-100%)';
			langText.style.opacity = '0';
			setTimeout(function() {
				currentIndex = (currentIndex + 1) % langKeys.length;
				var newLang = langKeys[currentIndex];
				langText.textContent = languages[newLang];
				// Slide in
				langText.style.transform = 'translateX(100%)';
				langText.style.opacity = '0';
				setTimeout(function() {
					langText.style.transform = 'translateX(0)';
					langText.style.opacity = '1';
				}, 10);
				// Save and reload
				if (authState === 'logged') {
					fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded',
						},
						body: 'action=apollo_save_language_preference&nonce=<?php echo esc_js(wp_create_nonce('apollo_user_language_nonce')); ?>&language=' +
							newLang
					}).then(function(response) {
						return response.json();
					}).then(function(data) {
						if (data.success) {
							window.location.reload();
						}
					});
				} else {
					d.cookie = 'apollo_language=' + newLang + '; path=/; max-age=31536000';
					window.location.reload();
				}
			}, 300);
		});
	}
	</script>

</body>

</html>
