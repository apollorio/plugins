<?php
// phpcs:ignoreFile
/**
 * ============================================
 * FILE: templates/partials/header.php
 * FULL HEADER with navigation
 * DESIGN LIBRARY: Matches approved header-social-desktop-H0.html
 * Used by: pagx_site, pagx_app
 * ============================================
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="apollo-html">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,maximum-scale=1,user-scalable=no">

	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="theme-color" content="#FFFFFF">

	<!-- Design System Apollo -->
	<link rel="stylesheet" href="https://assets.apollo.rio.br/uni.css">
	<script src="https://cdn.tailwindcss.com"></script>
	<link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">

	<!-- PWA Manifest - served from apollo-social plugin -->
	<?php wp_head(); ?>
</head>

<body <?php body_class( 'apollo-body min-h-screen bg-slate-50' ); ?>>
<?php wp_body_open(); ?>

<div id="apollo-wrapper" class="apollo-site-wrapper min-h-screen">

	<!-- Full Apollo Header -->
	<header id="apollo-header" class="sticky top-0 z-50 h-14 flex items-center justify-between border-b border-slate-200 bg-white/90 backdrop-blur-md px-4 md:px-6" data-component="header-apollo" data-tooltip="Cabeçalho Apollo">
		<div class="flex items-center gap-3">

			<!-- Logo -->
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-3" data-tooltip="Ir para início">
				<div class="h-9 w-9 rounded-xl bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center shadow-md shadow-orange-500/60">
					<i class="ri-slack-fill text-white text-[22px]"></i>
				</div>
				<div class="hidden sm:flex flex-col leading-tight">
					<span class="text-[9.5px] font-regular text-slate-400 uppercase tracking-[0.18em]">plataforma</span>
					<span class="text-[15px] font-extrabold text-slate-900">Apollo::rio</span>
				</div>
			</a>

			<!-- Main Navigation (Desktop) -->
			<nav id="apollo-navigation" class="hidden md:flex items-center gap-4 ml-8">
				<?php
				if ( has_nav_menu( 'apollo_primary' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'apollo_primary',
							'menu_id'        => 'apollo-primary-menu',
							'menu_class'     => 'flex items-center gap-4 text-[13px]',
							'container'      => false,
							'link_before'    => '<span class="text-slate-600 hover:text-slate-900 transition-colors">',
							'link_after'     => '</span>',
						)
					);
				}
				?>
			</nav>
		</div>

		<!-- Desktop Header Actions -->
		<div class="hidden md:flex items-center gap-3 text-[12px]" data-component="header-social-notifications" data-type="H0">
			<!-- Search Input -->
			<div class="relative group">
				<i class="ri-search-line text-slate-400 absolute left-3 top-1.5 text-xs group-focus-within:text-slate-600"></i>
				<input type="text" placeholder="Buscar na cena..."
					class="pl-8 pr-3 py-1.5 rounded-full border border-slate-200 bg-slate-50 text-[12px] w-64 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:bg-white transition-all"
					data-action="global-search" data-tooltip="Buscar conteúdo" aria-label="Buscar" />
			</div>

			<!-- Dark Mode Toggle -->
			<button id="dark-toggle" class="ml-1 text-slate-500 hover:text-slate-900 transition-colors" title="Dark Mode" data-tooltip="Alternar modo escuro" aria-label="Alternar modo escuro">
				<i class="ri-moon-line text-[14px]"></i>
			</button>

			<!-- Messages Button -->
			<button type="button" class="relative ml-1 text-slate-500 hover:text-slate-900 transition-colors" data-tooltip="Mensagens" aria-label="Ver mensagens" title="Messages">
				<i class="ri-message-3-line text-[16px]"></i>
			</button>

			<!-- Notifications Button -->
			<button type="button" class="relative ml-1 text-slate-500 hover:text-slate-900 transition-colors" data-tooltip="Notificações" aria-label="Ver notificações" title="Notifications">
				<i class="ri-notification-2-line text-[16px]"></i>
			</button>

			<!-- App Grid Button -->
			<button type="button" class="ml-1 inline-flex h-8 w-8 items-center justify-center rounded-full hover:ring-2 hover:ring-slate-200 transition-all" data-tooltip="Aplicativos" aria-label="Menu de aplicativos" title="Apps">
				<svg class="w-5 h-5 text-slate-500" viewBox="0 0 24 24" fill="currentColor">
					<path d="M6,8c1.1,0,2,-0.9,2,-2s-0.9,-2,-2,-2,-2,0.9,-2,2,0.9,2,2,2zM12,20c1.1,0,2,-0.9,2,-2s-0.9,-2,-2,-2,-2,0.9,-2,2,0.9,2,2,2zM6,20c1.1,0,2,-0.9,2,-2s-0.9,-2,-2,-2,-2,0.9,-2,2,0.9,2,2,2zM6,14c1.1,0,2,-0.9,2,-2s-0.9,-2,-2,-2,-2,0.9,-2,2,0.9,2,2,2zM12,14c1.1,0,2,-0.9,2,-2s-0.9,-2,-2,-2,-2,0.9,-2,2,0.9,2,2,2zM18,8c1.1,0,2,-0.9,2,-2s-0.9,-2,-2,-2,-2,0.9,-2,2,0.9,2,2,2zM12,8c1.1,0,2,-0.9,2,-2s-0.9,-2,-2,-2,-2,0.9,-2,2,0.9,2,2,2zM18,14c1.1,0,2,-0.9,2,-2s-0.9,-2,-2,-2,-2,0.9,-2,2,0.9,2,2,2zM18,20c1.1,0,2,-0.9,2,-2s-0.9,-2,-2,-2,-2,0.9,-2,2,0.9,2,2,2z"></path>
				</svg>
			</button>

			<!-- User Actions -->
			<?php if ( is_user_logged_in() ) : ?>
				<button type="button" class="ml-1 inline-flex h-8 w-8 items-center justify-center rounded-full hover:ring-2 hover:ring-slate-200 overflow-hidden transition-all" data-tooltip="<?php echo esc_attr( wp_get_current_user()->display_name ); ?>" aria-label="Menu do usuário">
					<?php echo get_avatar( get_current_user_id(), 32, '', '', array( 'class' => 'h-full w-full rounded-full object-cover' ) ); ?>
				</button>
			<?php else : ?>
				<a href="<?php echo esc_url( wp_login_url() ); ?>" class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-900 text-white text-[12px] font-semibold rounded-full hover:bg-slate-800 transition-colors" data-tooltip="Fazer login">
					<i class="ri-login-box-line text-xs"></i>
					<?php esc_html_e( 'Login', 'apollo-rio' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<!-- Mobile Header Actions -->
		<div class="flex md:hidden items-center gap-2">
			<button class="text-slate-500" data-action="mobile-search" data-tooltip="Buscar" aria-label="Buscar">
				<i class="ri-search-line text-xl"></i>
			</button>
			<button class="text-slate-500 relative" data-action="mobile-notifications" data-tooltip="Notificações" aria-label="Notificações">
				<i class="ri-notification-3-line text-xl"></i>
			</button>
			<?php if ( is_user_logged_in() ) : ?>
				<button class="h-8 w-8 rounded-full overflow-hidden ring-2 ring-white shadow-sm">
					<?php echo get_avatar( get_current_user_id(), 32, '', '', array( 'class' => 'h-full w-full object-cover' ) ); ?>
				</button>
			<?php else : ?>
				<a href="<?php echo esc_url( wp_login_url() ); ?>" class="text-slate-500" data-tooltip="Login">
					<i class="ri-user-line text-xl"></i>
				</a>
			<?php endif; ?>

			<!-- Mobile Menu Toggle -->
			<button id="apollo-mobile-toggle" class="ml-2 p-2 text-slate-600 hover:text-slate-900" aria-label="Menu" data-tooltip="Abrir menu">
				<i class="ri-menu-line text-xl"></i>
			</button>
		</div>
	</header>

	<!-- Main Content Start -->
	<main id="apollo-content" class="apollo-main-content">
