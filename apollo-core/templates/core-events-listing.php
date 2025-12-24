<?php
/**
 * Core Template: Events Listing
 * Source HTML: body_eventos ----list all.html
 *
 * This template receives extracted variables via Apollo_Template_Loader::load()
 * and renders the approved HTML design for the events discovery portal.
 *
 * Required context keys - see docs/conversion-map.md for full data contract
 *
 * @package ApolloCore\Templates
 */

defined( 'ABSPATH' ) || exit;

// Extract with defaults.
$page_title       = isset( $page_title ) ? esc_html( $page_title ) : __( 'Discover Events', 'apollo-events-manager' );
$is_print         = isset( $is_print ) && $is_print;
$current_user_var = isset( $current_user ) ? $current_user : null;
$navigation_links = isset( $navigation_links ) ? $navigation_links : array();
$hero_title       = isset( $hero_title ) ? esc_html( $hero_title ) : __( 'Experience Tomorrow\'s Events', 'apollo-events-manager' );
$hero_subtitle    = isset( $hero_subtitle ) ? $hero_subtitle : '';
$hero_background  = isset( $hero_background ) ? esc_url( $hero_background ) : '';
$period_filters   = isset( $period_filters ) && is_array( $period_filters ) ? $period_filters : array();
$category_filters = isset( $category_filters ) && is_array( $category_filters ) ? $category_filters : array();
$current_month    = isset( $current_month ) ? esc_html( $current_month ) : date_i18n( 'F Y' );
$event_sections   = isset( $event_sections ) && is_array( $event_sections ) ? $event_sections : array();
$banner           = isset( $banner ) && is_array( $banner ) ? $banner : null;
$show_bottom_bar  = isset( $show_bottom_bar ) && $show_bottom_bar;
$bottom_bar_data  = isset( $bottom_bar_data ) && is_array( $bottom_bar_data ) ? $bottom_bar_data : array();
$template_loader  = isset( $template_loader ) ? $template_loader : new Apollo_Template_Loader();

// Local asset base URL.
$asset_base = defined( 'APOLLO_CORE_PLUGIN_URL' )
	? APOLLO_CORE_PLUGIN_URL . 'assets/'
	: plugin_dir_url( __DIR__ ) . 'assets/';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<title><?php echo esc_html( $page_title ); ?> - Apollo::rio</title>
	<link rel="icon" href="<?php echo esc_url( $asset_base . 'img/neon-green.webp' ); ?>" type="image/webp">
	<?php Apollo_Template_Loader::load_partial( 'assets' ); ?>

	<style>
		/* ==========================================================================
			1. ROOT VARIABLES & THEME SETUP (from approved HTML)
			========================================================================== */
		:root {
			--font-primary: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
			--radius-main: 12px;
			--radius-sec: 20px;
			--transition-main: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
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
		}

		body.dark-mode {
			--bg-main: #131517;
			--bg-main-translucent: rgba(19, 21, 23, 0.68);
			--header-blur-bg: linear-gradient(to bottom, rgb(19 21 23 / .35) 0%, rgb(19 21 23 / .1) 50%, #13151700 100%);
			--text-main: #ffffff91;
			--text-primary: #fdfdfdfa;
			--text-secondary: #ffffff91;
			--border-color: #333537;
			--border-color-2: #e0e2e40a;
			--card-border-light: rgba(255, 255, 255, 0.1);
			--card-shadow-light: rgba(0, 0, 0, 0.2);
		}

		/* ==========================================================================
			2. BASE STYLES
			========================================================================== */
		*, :before, :after { box-sizing: border-box; }
		* { margin: 0; padding: 0; }
		html, body {
			color: var(--text-secondary);
			font-family: var(--font-primary);
			font-size: 15px;
			font-weight: 400;
			line-height: 1.2rem;
			background-color: var(--bg-main);
			transition: background-color 0.4s ease, color 0.4s ease;
			overflow-x: hidden !important;
			scroll-behavior: smooth;
		}
		a { text-decoration: none; color: var(--text-main); }
		a:hover { color: var(--text-primary); }
		p { color: var(--text-main); line-height: 1.5; }
		.visually-hidden {
			position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
			overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
		}
		.mb04rem { margin-bottom: 0.5rem; }

		/* ==========================================================================
			3. HEADER STYLES
			========================================================================== */
		.site-header { position: relative; z-index: 999; }
		.menu-h-apollo-blur {
			position: fixed; top: 0; left: 0; width: 100%; height: 75px;
			backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
			background: var(--header-blur-bg);
			mask: linear-gradient(to bottom, rgb(0 0 0) 0%, rgb(0 0 0 / .8) 40%, rgb(0 0 0 / .3) 70%, #fff0 100%);
			-webkit-mask: linear-gradient(to bottom, rgb(0 0 0) 0%, rgb(0 0 0 / .8) 40%, rgb(0 0 0 / .3) 70%, #fff0 100%);
			z-index: 998; pointer-events: none;
		}
		.main-nav { display: flex; position: fixed; height: 22px; top: 18px; right: 19px; align-items: center; z-index: 999; }
		.menu-apollo-logo { position: fixed; height: 23px; top: 14px; left: 19px; opacity: .8; z-index: 999; transition: var(--transition-main); }
		.menu-apollo-logo:hover { opacity: 1; }
		.main-nav a, .main-nav .menu-h-apollo-button { display: inline-block; margin: 0 16px; color: var(--text-secondary); font-size: .95rem; transition: var(--transition-main); }
		.menu-h-apollo-button {
			margin: 0px 10px 0px 13px !important; border: 1px solid var(--border-color);
			border-radius: 5px; padding: 4px 12px; font-size: .84rem !important;
			cursor: pointer; background: none; font-family: var(--font-primary);
		}
		.menu-h-apollo-button:hover { background: var(--border-color); color: var(--text-primary); }
		div.menu-h-lista { position: relative; }
		div.menu-h-lista > div.list {
			position: absolute; top: 40px; right: 5px; color: var(--text-secondary);
			border-radius: var(--radius-main); padding: 0.8rem 1rem !important; display: none;
			width: 160px; background: var(--bg-main-translucent);
			backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(8px);
			border: 1px solid var(--border-color-2); box-shadow: 0 10px 30px var(--card-shadow-light);
		}
		div.menu-h-lista.open > div.list { display: block; }
		div.menu-h-lista > div.list > .item {
			font-size: .85rem !important; margin: 0; text-align: left; width: 100%; padding: 0.5rem 0.2rem;
			display: flex; align-items: center; gap: 8px; cursor: pointer; transition: var(--transition-main);
		}
		.menu-h-lista .item:hover { color: var(--text-primary); font-weight: 600; }
		.menu-h-lista hr { height: 1px; background-color: var(--border-color-2); border: none; width: 100%; margin: 6px 0; }

		/* ==========================================================================
			4. MAIN LAYOUT & HERO
			========================================================================== */
		.main-container { max-width: 1320px; margin: 0 auto; padding: 100px 40px 50px 40px; }
		.hero-section { text-align: center; padding: 80px 0; margin-bottom: 50px; }
		.title-page { font-size: 4rem; font-weight: 800; color: var(--text-primary); line-height: 1.1; }
		.subtitle-page { font-size: 1.2rem; max-width: 600px; margin: 20px auto 0 auto; color: var(--text-secondary); }
		mark { background-color: #fd5c021a; font-weight: 500; color: #fd5c02; padding: 0 0.25em; border-radius: 3px; }
		.title-page { font-size: 4rem; font-weight: 800; color: var(--text-primary); line-height: 1.1; }
		.subtitle-page { font-size: 1.2rem; max-width: 600px; margin: 20px auto 0 auto; color: var(--text-secondary); }
		mark { background-color: #fd5c021a; font-weight: 500; color: #fd5c02; }

		/* ==========================================================================
			5. FILTERS & SEARCH
			========================================================================== */
		.filters-and-search { display: flex; flex-direction: column; align-items: center; gap: 20px; margin-bottom: 40px; }
		.menutags { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; }
		.menutag {
			font-size: 0.9rem; font-family: var(--font-primary); font-weight: 600;
			padding: 10px 20px; border-radius: 999px; background: transparent;
			border: 1px solid var(--border-color); color: var(--text-secondary);
			cursor: pointer; transition: var(--transition-main);
		}
		.menutag:hover, .menutag.active { background: var(--text-primary); color: var(--bg-main); border-color: var(--text-primary); }
		.search-date-controls { display: flex; gap: 10px; width: 100%; max-width: 500px; justify-content: center; }
		.box-search {
			height: 48px; border-radius: var(--radius-main); background-color: transparent;
			border: 1px solid var(--border-color); display: flex; align-items: center;
			padding: 0 10px 0 20px; gap: 10px; transition: var(--transition-main);
			flex-grow: 1; min-width: 180px;
		}
		.box-search:focus-within { border-color: var(--text-primary); box-shadow: 0 0 0 2px var(--border-color-2); }
		.box-search input { background: none; border: none; height: 100%; color: var(--text-primary); font-size: 1rem; width: 100%; outline: none; }
		.box-search i { font-size: 1.2rem; opacity: 0.5; }
		.box-datepicker {
			height: 48px; border-radius: var(--radius-main); background-color: transparent;
			border: 1px solid var(--border-color); display: flex; align-items: center;
			justify-content: space-between; padding: 0 10px; gap: 10px;
			font-family: var(--font-primary); font-weight: 600; color: var(--text-primary); flex-shrink: 0;
		}
		.date-arrow { background: none; border: none; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer; padding: 0 5px; transition: var(--transition-main); line-height: 1; }
		.date-arrow:hover { color: var(--text-primary); }
		.date-display { font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }

		/* ==========================================================================
			6. EVENT LISTING & CARD STYLES (Strict Grid: 4/2/1)
			========================================================================== */
		.event_listings {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: 2rem;
		}
		.event_listing {
			cursor: pointer;
			position: relative;
			transition: transform 0.4s ease;
			display: block;
			animation: fadeIn 0.5s ease;
		}
		.event_listing.hidden { display: none; }
		@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
		.event_listing:hover { transform: translateY(-5px); }

		.event_listing .picture {
			--r: 12px; --s: 12px; --x: 48px; --y: 42px;
			height: 400px; position: relative; border-radius: var(--radius-main); overflow: hidden;
			box-shadow: 0 10px 30px var(--card-shadow-light); border: 1px solid var(--card-border-light);
			transition: transform 0.4s ease, box-shadow 0.4s ease;
			--_m: /calc(2*var(--r)) calc(2*var(--r)) radial-gradient(#000 70%, #0000 72%);
			--_g: conic-gradient(at var(--r) var(--r), #000 75%, #0000 0);
			--_d: (var(--s) + var(--r));
			mask: calc(var(--_d) + var(--x)) 0 var(--_m), 0 calc(var(--_d) + var(--y)) var(--_m),
				radial-gradient(var(--s) at 0 0, #0000 99%, #000 calc(100% + 1px)) calc(var(--r) + var(--x)) calc(var(--r) + var(--y)),
				var(--_g) calc(var(--_d) + var(--x)) 0, var(--_g) 0 calc(var(--_d) + var(--y));
			mask-repeat: no-repeat;
		}
		.event_listing:hover .picture { transform: scale(1.05); box-shadow: 0 15px 40px var(--card-shadow-light); }
		.event_listing .picture img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; }
		.box-date-event {
			position: absolute; top: 5px; left: 7px; width: 60px; height: 54px;
			text-align: center; display: flex; flex-direction: column; justify-content: center;
			line-height: 1.1; transition: var(--transition-main); z-index: 2;
		}
		.date-day { font-size: 1.6em; font-weight: 700; color: var(--text-primary); display: block; }
		.date-month { font-size: 0.9em; font-weight: 600; text-transform: uppercase; color: var(--text-secondary); opacity: 0.9; }
		.event-card-tags { position: absolute; bottom: 10px; right: 10px; display: flex; gap: 10px; pointer-events: none; z-index: 3; }
		.event-card-tags span {
			padding: 2px 8px; border-radius: 4px; border: 1px solid #ffffff2a;
			background: linear-gradient(30deg, rgba(255, 255, 255, 0.1) -49%, rgba(255, 255, 255, 0.35) 160%);
			backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px);
			font-size: 0.625rem; color: rgba(255, 255, 255, .8); font-weight: 600; text-transform: uppercase;
		}
		.event-line { padding: 1.25em 0.5rem; width: 100%; transition: 0.3s ease; word-break: break-word; }
		.event-li-title { font-size: 1.1rem; font-weight: 700; color: var(--text-primary); line-height: 1.3; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; }
		.event-li-detail { color: var(--text-secondary); font-size: 0.84rem; display: flex; align-items: center; gap: 6px; }
		.event-li-detail > i { font-size: .9rem; flex-shrink: 0; }
		.of-dj, .of-location { font-size: 0.82rem; font-weight: 400; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; }

		/* ==========================================================================
			7. BANNER
			========================================================================== */
		.banner-ario-1-wrapper {
			margin: 20px 0; padding: 50px; border-radius: var(--radius-sec);
			border: 1px solid var(--border-color); background: var(--border-color-2);
			backdrop-filter: blur(8px); display: flex; gap: 50px; align-items: center; overflow: hidden;
		}
		.ban-ario-1-img { width: 40%; max-width: 400px; border-radius: var(--radius-main); object-fit: cover; align-self: stretch; transition: var(--transition-main); }
		.banner-ario-1-wrapper:hover .ban-ario-1-img { transform: scale(1.03); }
		.ban-ario-1-content { position: relative; flex: 1; }
		.ban-ario-1-subtit { font-size: 1rem; color: var(--accent-color); font-weight: 600; text-transform: uppercase; }
		.ban-ario-1-titl { font-size: 2.5rem; font-weight: 700; color: var(--text-primary); margin: 15px 0 38px 0; opacity: .75; }
		.ban-ario-1-txt { font-size: 1.1rem; line-height: 1.4; margin-bottom: 40px; hyphens: auto; -webkit-hyphens: auto; }
		.ban-ario-1-btn {
			background: linear-gradient(135deg, var(--vermelho), var(--laranja)); color: #fff;
			padding: 15px 30px; border-radius: var(--radius-main); font-weight: 500;
			transition: var(--transition-main); border: 1px solid var(--card-border-light);
			box-shadow: 0 8px 24px rgba(249, 115, 22, 0.2); display: inline-flex; align-items: center; gap: 8px;
		}
		.ban-ario-1-btn:hover { box-shadow: 0 12px 30px rgba(249, 115, 22, 0.3); transform: scale(1.03) translateY(-2px); filter: brightness(1.1); color: #fff; }

		/* ==========================================================================
			8. DARK MODE TOGGLE
			========================================================================== */
		.dark-mode-toggle {
		position: fixed; bottom: 20px; right: 20px; width: 50px; height: 50px;
		background: var(--bg-main); border: 2px solid var(--border-color); border-radius: 50%;
		display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 1000;
		transition: var(--transition-main); box-shadow: 0 4px 12px var(--card-shadow-light);
	}
	.dark-mode-toggle:hover {
		transform: scale(1.1);
		box-shadow: 0 6px 20px rgba(255, 161, 127, 0.3);
		border-color: var(--accent-color);
	}
	.dark-mode-toggle i { font-size: 1.3rem; color: var(--accent-color); transition: var(--transition-main); }

		/* ==========================================================================
			9. RESPONSIVE (Strict: Desktop 4 cols, Tablet 2 cols, Mobile 1 col)
			========================================================================== */
		@media (max-width: 1280px) {
			.event_listings { grid-template-columns: repeat(3, 1fr); }
		}
		@media (max-width: 1024px) {
			.event_listings { grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
			.banner-ario-1-wrapper { flex-direction: column; text-align: center; gap: 30px; }
			.ban-ario-1-img { width: 100%; max-width: 500px; height: 300px; }
		}
		@media (max-width: 768px) {
			.main-container { padding: 80px 20px 30px 20px; }
			.hero-section { padding: 40px 0; }
			.title-page { font-size: 3rem; }
			.search-date-controls { flex-direction: column; width: 100%; max-width: none; gap: 15px; }
			.box-search { width: 100%; }
			.box-datepicker { width: 100%; justify-content: space-between; }
			.event_listing .picture { height: 300px; --x: 38px; --y: 30px; }
			.box-date-event { width: 50px; height: 42px; }
			.date-day { font-size: 1.2em; }
			.date-month { font-size: 0.8em; }
			.event-card-tags span { font-size: 0.6rem; padding: 2px 6px; }
			.event-li-title { font-size: 1rem; font-weight: 700; line-height: 1.3; }
			.main-nav a.ario-eve { display: none; }
			.main-nav { right: 10px; }
			.menu-apollo-logo { left: 10px; }
			.main-nav a, .main-nav .menu-h-apollo-button { margin: 0 8px; }
			.menu-h-apollo-button { margin: 0 0 0 8px !important; }
			.banner-ario-1-wrapper { padding: 30px; }
			.ban-ario-1-titl { font-size: 2rem; }
		}
		@media (max-width: 640px) {
			.event_listings { grid-template-columns: 1fr; gap: 2rem; }
		}
		@media (max-width: 480px) {
			.event_listings { grid-template-columns: 1fr; gap: 30px; }
			.title-page { font-size: 2.5rem; }
			.menutags { gap: 8px; }
			.menutag { padding: 8px 16px; font-size: 0.85rem; }
			.hero-section { padding: 20px 0; margin-bottom: 30px; }
			.dark-mode-toggle { bottom: 15px; right: 15px; }
			.event_listing .picture { height: 350px; --r: 12px; --s: 12px; --x: 48px; --y: 42px; }
			.box-date-event { width: 60px; height: 54px; }
			.date-day { font-size: 1.6em; }
			.date-month { font-size: 0.9em; }
		}

		<?php if ( $is_print ) : ?>
		/* Print Mode Overrides */
		.site-header, .dark-mode-toggle, .menu-h-apollo-blur, .box-search, .box-datepicker { display: none !important; }
		.main-container { padding: 20px !important; }
		.event_listing { break-inside: avoid; page-break-inside: avoid; }
		<?php endif; ?>
	</style>
</head>

<body<?php echo $is_print ? '' : ' class="apollo-canvas-mode"'; ?>>

	<?php if ( ! $is_print ) : ?>
	<!-- ======================= HEADER ======================= -->
	<header class="site-header">
		<div class="menu-h-apollo-blur"></div>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" alt="Logo Apollo::rio" title="Apollo::rio">
			<img src="https://vertente.apollo.rio.br/i.png" class="menu-apollo-logo" alt="Apollo">
		</a>
		<nav class="main-nav">
			<a class="a-hover off"><span id="agoraH">--:--</span> RJ</a>
			<a href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>" class="ario-eve" title="<?php esc_attr_e( 'Portal de Eventos', 'apollo-events-manager' ); ?>">
				<?php esc_html_e( 'Eventos', 'apollo-events-manager' ); ?><i class="ri-arrow-right-up-line"></i>
			</a>
			<div class="menu-h-lista">
				<?php if ( is_user_logged_in() && $current_user ) : ?>
					<button class="menu-h-apollo-button caption" id="userMenuTrigger">
						<?php echo esc_html( is_array( $current_user ) ? $current_user['name'] : $current_user->display_name ); ?>
					</button>
					<div class="list">
						<div class="item ok"><i class="ri-global-line"></i> Explorer</div>
						<hr>
						<div class="item ok"><i class="ri-fingerprint-2-fill"></i> My Apollo</div>
						<div class="item ok"><a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>"><i class="ri-logout-box-r-line"></i> Logout</a></div>
					</div>
				<?php else : ?>
					<button class="menu-h-apollo-button caption" id="userMenuTrigger">Login</button>
					<div class="list">
						<div class="item ok"><i class="ri-global-line"></i> Explorer</div>
						<hr>
						<div class="item ok"><i class="ri-fingerprint-2-fill"></i> My Apollo</div>
					</div>
				<?php endif; ?>
			</div>
		</nav>
	</header>
	<?php endif; ?>

	<main class="main-container">
		<div class="event-manager-shortcode-wrapper discover-events-now-shortcode">

			<!-- ======================= HERO SECTION ======================= -->
			<section class="hero-section">
				<h1 class="title-page"><?php echo esc_html( $hero_title ); ?></h1>
				<?php if ( $hero_subtitle ) : ?>
					<p class="subtitle-page"><?php echo wp_kses_post( $hero_subtitle ); ?></p>
				<?php endif; ?>
			</section>

			<!-- ======================= FILTERS & SEARCH ======================= -->
			<?php if ( ! $is_print ) : ?>
			<div class="filters-and-search">
				<!-- Category Tags -->
				<div class="menutags event_types">
					<?php
					foreach ( $category_filters as $filter ) :
						$filter = wp_parse_args(
							$filter,
							array(
								'slug'   => '',
								'label'  => '',
								'url'    => '#',
								'active' => false,
								'type'   => 'button',
							)
						);
						?>
						<?php if ( 'link' === $filter['type'] ) : ?>
							<a href="<?php echo esc_url( $filter['url'] ); ?>"
								class="menutag event-category <?php echo $filter['active'] ? 'active' : ''; ?>"
								data-slug="<?php echo esc_attr( $filter['slug'] ); ?>">
								<?php echo esc_html( $filter['label'] ); ?>
							</a>
						<?php else : ?>
							<button class="menutag event-category <?php echo $filter['active'] ? 'active' : ''; ?>"
									data-slug="<?php echo esc_attr( $filter['slug'] ); ?>">
								<?php echo esc_html( $filter['label'] ); ?>
							</button>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>

				<!-- Search + Date Controls -->
				<div class="search-date-controls">
					<form class="box-search" role="search" id="eventSearchForm">
						<label for="eventSearchInput" class="visually-hidden"><?php esc_html_e( 'Buscar eventos', 'apollo-events-manager' ); ?></label>
						<i class="ri-search-line"></i>
						<input type="text" name="search_keywords" id="eventSearchInput"
								placeholder="<?php esc_attr_e( 'Buscar eventos...', 'apollo-events-manager' ); ?>">
						<input type="hidden" name="post_type" value="event_listing">
					</form>

					<div class="box-datepicker" id="eventDatePicker">
						<button type="button" class="date-arrow" id="datePrev" aria-label="<?php esc_attr_e( 'Mês anterior', 'apollo-events-manager' ); ?>">‹</button>
						<span class="date-display" id="dateDisplay"><?php echo esc_html( $current_month ); ?></span>
						<button type="button" class="date-arrow" id="dateNext" aria-label="<?php esc_attr_e( 'Próximo mês', 'apollo-events-manager' ); ?>">›</button>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<!-- ======================= EVENT SECTIONS ======================= -->
			<?php
			foreach ( $event_sections as $section ) :
				$section = wp_parse_args(
					$section,
					array(
						'slug'       => '',
						'title'      => '',
						'icon'       => 'ri-calendar-event-line',
						'show_title' => true,
						'grid_class' => '',
						'events'     => array(),
					)
				);

				if ( empty( $section['events'] ) ) {
					continue;
				}
				?>
				<section class="apollo-events-section apollo-events-section--<?php echo esc_attr( $section['slug'] ); ?>">
					<?php if ( $section['show_title'] && $section['title'] ) : ?>
						<h2 class="apollo-section-title" style="margin-bottom: 1.5rem; font-size: 1.5rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 0.75rem;">
							<i class="<?php echo esc_attr( $section['icon'] ); ?>"></i>
							<?php echo esc_html( $section['title'] ); ?>
						</h2>
					<?php endif; ?>

					<div class="event_listings <?php echo esc_attr( $section['grid_class'] ); ?>">
						<?php
						foreach ( $section['events'] as $event_data ) :
							$event_data = wp_parse_args(
								$event_data,
								array(
									'id'            => 0,
									'title'         => '',
									'permalink'     => '#',
									'thumbnail_url' => '',
									'day'           => '',
									'month'         => '',
									'tags'          => array(),
									'djs'           => array(),
									'venue_name'    => '',
									'category'      => '',
								)
							);

							// Build DJ names string.
							$dj_names = '';
							if ( ! empty( $event_data['djs'] ) ) {
								$dj_names_arr = array_map(
									function ( $dj ) {
										return is_array( $dj ) ? $dj['name'] : $dj;
									},
									$event_data['djs']
								);
								$dj_names     = implode( ', ', array_slice( $dj_names_arr, 0, 3 ) );
							}
							?>
							<a href="<?php echo esc_url( $event_data['permalink'] ); ?>"
								class="event_listing"
								data-event-id="<?php echo intval( $event_data['id'] ); ?>"
								data-category="<?php echo esc_attr( $event_data['category'] ); ?>"
								data-month-str="<?php echo esc_attr( strtolower( $event_data['month'] ) ); ?>">

								<div class="box-date-event">
									<span class="date-day"><?php echo esc_html( $event_data['day'] ); ?></span>
									<span class="date-month"><?php echo esc_html( $event_data['month'] ); ?></span>
								</div>

								<div class="picture">
									<?php if ( $event_data['thumbnail_url'] ) : ?>
										<img src="<?php echo esc_url( $event_data['thumbnail_url'] ); ?>"
											alt="<?php echo esc_attr( $event_data['title'] ); ?>"
											loading="lazy">
									<?php else : ?>
										<img src="https://images.unsplash.com/photo-1524368535928-5b5e00ddc76b?q=80&w=2070&auto=format&fit=crop"
											alt="<?php echo esc_attr( $event_data['title'] ); ?>"
											loading="lazy">
									<?php endif; ?>

									<?php if ( ! empty( $event_data['tags'] ) ) : ?>
										<div class="event-card-tags">
											<?php
											foreach ( array_slice( $event_data['tags'], 0, 2 ) as $tag_item ) :
												$tag_name = is_array( $tag_item ) ? $tag_item['name'] : $tag_item;
												?>
												<span><?php echo esc_html( $tag_name ); ?></span>
												<?php endforeach; ?>
										</div>
									<?php endif; ?>
								</div>

								<div class="event-line">
									<div class="box-info-event">
										<h2 class="event-li-title mb04rem"><?php echo esc_html( $event_data['title'] ); ?></h2>
										<?php if ( $dj_names ) : ?>
											<p class="event-li-detail of-dj mb04rem">
												<i class="ri-sound-module-fill"></i>
												<span><?php echo esc_html( $dj_names ); ?></span>
											</p>
										<?php endif; ?>
										<?php if ( $event_data['venue_name'] ) : ?>
											<p class="event-li-detail of-location mb04rem">
												<i class="ri-map-pin-2-line"></i>
												<span><?php echo esc_html( $event_data['venue_name'] ); ?></span>
											</p>
										<?php endif; ?>
									</div>
								</div>
							</a>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endforeach; ?>

			<!-- ======================= BANNER SECTION ======================= -->
			<?php
			if ( $banner && ! $is_print ) :
				$banner = wp_parse_args(
					$banner,
					array(
						'image'    => '',
						'title'    => '',
						'subtitle' => '',
						'excerpt'  => '',
						'url'      => '#',
						'cta_text' => __( 'Saiba mais', 'apollo-events-manager' ),
					)
				);
				?>
				<section class="banner-ario-1-wrapper" style="margin-top: 80px;">
					<?php if ( $banner['image'] ) : ?>
						<img src="<?php echo esc_url( $banner['image'] ); ?>"
							class="ban-ario-1-img"
							alt="<?php echo esc_attr( $banner['title'] ); ?>">
					<?php endif; ?>
					<div class="ban-ario-1-content">
						<?php if ( $banner['subtitle'] ) : ?>
							<h3 class="ban-ario-1-subtit"><?php echo esc_html( $banner['subtitle'] ); ?></h3>
						<?php endif; ?>
						<h2 class="ban-ario-1-titl"><?php echo esc_html( $banner['title'] ); ?></h2>
						<?php if ( $banner['excerpt'] ) : ?>
							<p class="ban-ario-1-txt"><?php echo esc_html( $banner['excerpt'] ); ?></p>
						<?php endif; ?>
						<a href="<?php echo esc_url( $banner['url'] ); ?>" class="ban-ario-1-btn">
							<?php echo esc_html( $banner['cta_text'] ); ?>
							<i class="ri-arrow-right-long-line"></i>
						</a>
					</div>
				</section>
			<?php endif; ?>

		</div>
	</main>

	<?php if ( ! $is_print ) : ?>
	<!-- ======================= DARK MODE TOGGLE ======================= -->
	<div class="dark-mode-toggle" id="darkModeToggle" role="button" aria-label="<?php esc_attr_e( 'Alternar modo escuro', 'apollo-events-manager' ); ?>">
		<i class="ri-sun-line"></i>
		<i class="ri-moon-line"></i>
	</div>

		<?php if ( $show_bottom_bar ) : ?>
			<?php Apollo_Template_Loader::load_partial( 'bottom-bar', $bottom_bar_data ); ?>
	<?php endif; ?>
	<?php endif; ?>

	<?php wp_footer(); ?>

	<script>
		// Clock update.
		function updateClock() {
			const now = new Date();
			const h = String(now.getHours()).padStart(2, '0');
			const m = String(now.getMinutes()).padStart(2, '0');
			const el = document.getElementById('agoraH');
			if (el) el.textContent = h + ':' + m;
		}
		updateClock();
		setInterval(updateClock, 60000);

		// User menu toggle.
		document.getElementById('userMenuTrigger')?.addEventListener('click', function(e) {
			e.stopPropagation();
			this.closest('.menu-h-lista').classList.toggle('open');
		});
		document.addEventListener('click', function() {
			document.querySelectorAll('.menu-h-lista.open').forEach(el => el.classList.remove('open'));
		});

		// Dark mode toggle.
		document.getElementById('darkModeToggle')?.addEventListener('click', function() {
			document.body.classList.toggle('dark-mode');
			localStorage.setItem('apolloDarkMode', document.body.classList.contains('dark-mode') ? 'true' : 'false');
		});
		if (localStorage.getItem('apolloDarkMode') === 'true') {
			document.body.classList.add('dark-mode');
		}

		// Category filter.
		document.querySelectorAll('.menutag.event-category').forEach(btn => {
			btn.addEventListener('click', function(e) {
				e.preventDefault();
				const slug = this.dataset.slug;
				document.querySelectorAll('.menutag.event-category').forEach(b => b.classList.remove('active'));
				this.classList.add('active');
				document.querySelectorAll('.event_listing').forEach(card => {
					if (slug === 'all' || card.dataset.category === slug || slug === '') {
						card.classList.remove('hidden');
					} else {
						card.classList.add('hidden');
					}
				});
			});
		});

		// Search filter.
		document.getElementById('eventSearchForm')?.addEventListener('submit', function(e) {
			e.preventDefault();
		});
		document.getElementById('eventSearchInput')?.addEventListener('input', function() {
			const query = this.value.toLowerCase().trim();
			document.querySelectorAll('.event_listing').forEach(card => {
				const title = card.querySelector('.event-li-title')?.textContent.toLowerCase() || '';
				const dj = card.querySelector('.of-dj span')?.textContent.toLowerCase() || '';
				const venue = card.querySelector('.of-location span')?.textContent.toLowerCase() || '';
				if (title.includes(query) || dj.includes(query) || venue.includes(query) || query === '') {
					card.classList.remove('hidden');
				} else {
					card.classList.add('hidden');
				}
			});
		});
	</script>
</body>
</html>
