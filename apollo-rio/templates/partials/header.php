<?php
// phpcs:ignoreFile
/**
 * ============================================
 * PARTIAL: templates/partials/header.php
 * APOLLO RIO HEADER - BLANK CANVAS
 * ============================================
 *
 * Full HTML document header for Apollo Rio templates.
 * Used by: pagx_site, pagx_app, pagx_apolloapp
 *
 * Features:
 * - Complete DOCTYPE and <head>
 * - PWA meta tags for mobile apps
 * - wp_head() for proper asset loading
 * - Apollo navigation header
 * - Dark mode support
 *
 * @package Apollo_Rio
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

// ========================================
// FORCE LOAD APOLLO ASSETS EARLY
// ========================================
// This ensures assets are queued before wp_head() runs
if (function_exists('apollo_ensure_base_assets')) {
    // Hook early to ensure assets are in the queue
    add_action('wp_enqueue_scripts', function() {
        apollo_ensure_base_assets();
    }, 1);

    // If wp_enqueue_scripts already fired, force load now
    if (did_action('wp_enqueue_scripts')) {
        apollo_ensure_base_assets();
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="apollo-html">
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,maximum-scale=1,user-scalable=no">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">

	<!-- PWA Meta Tags -->
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="theme-color" content="#FFFFFF" media="(prefers-color-scheme: light)">
	<meta name="theme-color" content="#0F172A" media="(prefers-color-scheme: dark)">

	<!-- Preconnect to CDNs for performance -->
	<link rel="preconnect" href="https://cdn.apollo.rio.br" crossorigin>
	<link rel="preconnect" href="https://assets.apollo.rio.br" crossorigin>
	<link rel="dns-prefetch" href="https://cdn.apollo.rio.br">

	<?php
    // WordPress head - loads all enqueued assets
    wp_head();
    ?>
</head>

<body <?php body_class('apollo-body min-h-screen bg-slate-50 dark:bg-slate-900'); ?>>
<?php wp_body_open(); ?>

<div id="apollo-wrapper" class="apollo-site-wrapper min-h-screen flex flex-col">

	<!-- Apollo Header -->
	<header id="apollo-header" class="sticky top-0 z-50 h-14 flex items-center justify-between border-b border-slate-200 dark:border-slate-700 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md px-4 md:px-6" data-component="header-apollo">
		<div class="flex items-center gap-3">

			<!-- Logo -->
			<a href="<?php echo esc_url(home_url('/')); ?>" class="flex items-center gap-3">
				<div class="h-9 w-9 rounded-xl bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center shadow-md shadow-orange-500/60">
					<i class="ri-slack-fill text-white text-[22px]"></i>
				</div>
				<div class="hidden sm:flex flex-col leading-tight">
					<span class="text-[9.5px] font-regular text-slate-400 uppercase tracking-[0.18em]">plataforma</span>
					<span class="text-[15px] font-extrabold text-slate-900 dark:text-white">Apollo::rio</span>
				</div>
			</a>

			<!-- Main Navigation (Desktop) -->
			<nav id="apollo-navigation" class="hidden md:flex items-center gap-4 ml-8">
				<?php
                if (has_nav_menu('apollo_primary')) {
                    wp_nav_menu([
                        'theme_location' => 'apollo_primary',
                        'menu_id'        => 'apollo-primary-menu',
                        'menu_class'     => 'flex items-center gap-4 text-[13px]',
                        'container'      => false,
                        'link_before'    => '<span class="text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-colors">',
                        'link_after'     => '</span>',
                    ]);
                }
                ?>
			</nav>
		</div>

		<!-- Desktop Header Actions -->
		<div class="hidden md:flex items-center gap-3 text-[12px]" data-component="header-actions">
			<!-- Search Input -->
			<div class="relative group">
				<i class="ri-search-line text-slate-400 absolute left-3 top-1.5 text-xs group-focus-within:text-slate-600"></i>
				<input type="text" placeholder="<?php esc_attr_e('Buscar na cena...', 'apollo-rio'); ?>"
					class="pl-8 pr-3 py-1.5 rounded-full border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-800 text-[12px] w-64 focus:outline-none focus:ring-2 focus:ring-slate-900/10 dark:focus:ring-slate-400/20 focus:bg-white dark:focus:bg-slate-700 transition-all"
					data-action="global-search" aria-label="<?php esc_attr_e('Buscar', 'apollo-rio'); ?>" />
			</div>

			<!-- Dark Mode Toggle -->
			<button id="dark-toggle" class="ml-1 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors" title="<?php esc_attr_e('Modo escuro', 'apollo-rio'); ?>" aria-label="<?php esc_attr_e('Alternar modo escuro', 'apollo-rio'); ?>">
				<i class="ri-moon-line text-[14px] dark:hidden"></i>
				<i class="ri-sun-line text-[14px] hidden dark:inline"></i>
			</button>

			<!-- Messages Button -->
			<button type="button" id="btn-messages" class="relative ml-1 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors" aria-label="<?php esc_attr_e('Mensagens', 'apollo-rio'); ?>">
				<i class="ri-message-3-line text-[16px]"></i>
			</button>

			<!-- Notifications Button -->
			<button type="button" class="relative ml-1 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors" data-apollo-notif-trigger aria-label="<?php esc_attr_e('Notificações', 'apollo-rio'); ?>">
				<i class="ri-notification-2-line text-[16px]"></i>
			</button>

			<!-- App Grid Button -->
			<button type="button" class="ml-1 inline-flex h-8 w-8 items-center justify-center rounded-full hover:ring-2 hover:ring-slate-200 dark:hover:ring-slate-600 transition-all" aria-label="<?php esc_attr_e('Aplicativos', 'apollo-rio'); ?>">
				<svg class="w-5 h-5 text-slate-500 dark:text-slate-400" viewBox="0 0 24 24" fill="currentColor">
					<path d="M6,8c1.1,0,2,-0.9,2,-2s-0.9,-2,-2,-2,-2,0.9,-2,2,0.9,2,2,2zM12,20c1.1,0,2,-0.9,2,-2s-0.9,-2,-2,-2,-2,0.9,-2,2,0.9,2,2,2zM6,20c1.1,0,2,-0.9,2,-2s-0.9,-2,-2,-2,-2,0.9,-2,2,0.9,2,2,2zM6,14c1.1,0,2,-0.9,2,-2s-0.9,-2,-2,-2,-2,0.9,-2,2,0.9,2,2,2zM12,14c1.1,0,2,-0.9,2,-2s-0.9,-2,-2,-2,-2,0.9,-2,2,0.9,2,2,2zM18,8c1.1,0,2,-0.9,2,-2s-0.9,-2,-2,-2,-2,0.9,-2,2,0.9,2,2,2zM12,8c1.1,0,2,-0.9,2,-2s-0.9,-2,-2,-2,-2,0.9,-2,2,0.9,2,2,2zM18,14c1.1,0,2,-0.9,2,-2s-0.9,-2,-2,-2,-2,0.9,-2,2,0.9,2,2,2zM18,20c1.1,0,2,-0.9,2,-2s-0.9,-2,-2,-2,-2,0.9,-2,2,0.9,2,2,2z"></path>
				</svg>
			</button>

			<!-- User Actions -->
			<?php if (is_user_logged_in()) : ?>
				<button type="button" class="ml-1 inline-flex h-8 w-8 items-center justify-center rounded-full hover:ring-2 hover:ring-slate-200 dark:hover:ring-slate-600 overflow-hidden transition-all" aria-label="<?php echo esc_attr(wp_get_current_user()->display_name); ?>">
					<?php echo get_avatar(get_current_user_id(), 32, '', '', ['class' => 'h-full w-full rounded-full object-cover']); ?>
				</button>
			<?php else : ?>
				<a href="<?php echo esc_url(wp_login_url()); ?>" class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-900 dark:bg-orange-600 text-white text-[12px] font-semibold rounded-full hover:bg-slate-800 dark:hover:bg-orange-500 transition-colors">
					<i class="ri-login-box-line text-xs"></i>
					<?php esc_html_e('Login', 'apollo-rio'); ?>
				</a>
			<?php endif; ?>
		</div>

		<!-- Mobile Header Actions -->
		<div class="flex md:hidden items-center gap-2">
			<button class="text-slate-500 dark:text-slate-400" data-action="mobile-search" aria-label="<?php esc_attr_e('Buscar', 'apollo-rio'); ?>">
				<i class="ri-search-line text-xl"></i>
			</button>
			<button class="text-slate-500 dark:text-slate-400 relative" data-apollo-notif-trigger aria-label="<?php esc_attr_e('Notificações', 'apollo-rio'); ?>">
				<i class="ri-notification-3-line text-xl"></i>
			</button>
			<?php if (is_user_logged_in()) : ?>
				<button class="h-8 w-8 rounded-full overflow-hidden ring-2 ring-white dark:ring-slate-700 shadow-sm">
					<?php echo get_avatar(get_current_user_id(), 32, '', '', ['class' => 'h-full w-full object-cover']); ?>
				</button>
			<?php else : ?>
				<a href="<?php echo esc_url(wp_login_url()); ?>" class="text-slate-500 dark:text-slate-400">
					<i class="ri-user-line text-xl"></i>
				</a>
			<?php endif; ?>

			<!-- Mobile Menu Toggle -->
			<button id="apollo-mobile-toggle" class="ml-2 p-2 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white" aria-label="<?php esc_attr_e('Menu', 'apollo-rio'); ?>">
				<i class="ri-menu-line text-xl"></i>
			</button>
		</div>
	</header>

	<!-- Main Content Container -->
	<div id="apollo-content" class="apollo-main-content flex-1">
