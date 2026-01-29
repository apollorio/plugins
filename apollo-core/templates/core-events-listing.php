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

// CDN with local fallback
$cdn_base     = 'https://assets.apollo.rio.br/';
$local_base   = defined( 'APOLLO_CORE_PLUGIN_URL' )
	? APOLLO_CORE_PLUGIN_URL . 'assets/core/'
	: plugin_dir_url( __DIR__ ) . 'assets/core/';
$local_img    = defined( 'APOLLO_CORE_PLUGIN_URL' )
	? APOLLO_CORE_PLUGIN_URL . 'assets/img/'
	: plugin_dir_url( __DIR__ ) . 'assets/img/';
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
	<link rel="icon" href="<?php echo esc_url( $local_img . 'neon-green.webp' ); ?>" type="image/webp">
	<?php Apollo_Template_Loader::load_partial( 'assets' ); ?>

	<style>
		/* ==========================================================================
			1. ROOT VARIABLES & THEME SETUP (from approved HTML)
			========================================================================== */
		:root {
			--font-primary: var(--ap-font-sans, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif);
			--radius-main: var(--ap-radius-lg, 12px);
			--radius-sec: var(--ap-radius-xl, 20px);
			--transition-main: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
			--bg-main: var(--ap-bg, #fff);
			--bg-main-translucent: var(--ap-bg-translucent, rgba(255, 255, 255, .68));
			--header-blur-bg: linear-gradient(to bottom, rgb(253 253 253 / .35) 0%, rgb(253 253 253 / .1) 50%, #fff0 100%);
			--text-main: var(--ap-text-muted, rgba(19, 21, 23, .7));
			--text-primary: var(--ap-text-dark, rgba(19, 21, 23, .85));
			--text-secondary: var(--ap-text-muted, rgba(19, 21, 23, .7));
			--border-color: var(--ap-border, #e0e2e4);
			--border-color-2: var(--ap-border-light, #e0e2e454);
			--card-border-light: var(--ap-border-card, rgba(0, 0, 0, 0.13));
			--card-shadow-light: rgba(0, 0, 0, 0.05);
			--accent-color: var(--ap-orange, #FFA17F);
			--vermelho: var(--ap-red, #fe786d);
			--laranja: var(--ap-orange, #FFA17F);
		}

		body.dark-mode {
			--bg-main: var(--ap-bg-dark, #131517);
			--bg-main-translucent: var(--ap-bg-dark-translucent, rgba(19, 21, 23, 0.68));
			--header-blur-bg: linear-gradient(to bottom, rgb(19 21 23 / .35) 0%, rgb(19 21 23 / .1) 50%, #13151700 100%);
			--text-main: var(--ap-text-light-muted, #ffffff91);
			--text-primary: var(--ap-text-light, #fdfdfdfa);
			--text-secondary: var(--ap-text-light-muted, #ffffff91);
			--border-color: var(--ap-border-dark, #333537);
			--border-color-2: var(--ap-border-dark-light, #e0e2e40a);
			--card-border-light: var(--ap-border-card-dark, rgba(255, 255, 255, 0.1));
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
			3. HEADER STYLES - DEPRECATED: Old header styles removed
			   New navbar is loaded via wp_footer hook from class-apollo-navbar-apps.php
			========================================================================== */
		/* OLD STYLES REMOVED - The new navbar has its own CSS */

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
			9. RESPONSIVE - Mobile-First approach
			Default: 2 cols → 768px+: 4 cols (clamp handles sizing)
			========================================================================== */
		@media (max-width: 1024px) {
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

			.banner-ario-1-wrapper { padding: 30px; }
			.ban-ario-1-titl { font-size: 2rem; }
		}
		@media (max-width: 480px) {
			.title-page { font-size: 2.5rem; }
			.menutags { gap: 8px; }
			.menutag { padding: 8px 16px; font-size: 0.85rem; }
			.hero-section { padding: 20px 0; margin-bottom: 30px; }
			.dark-mode-toggle { bottom: 15px; right: 15px; }
		}

		<?php if ( $is_print ) : ?>
		/* Print Mode Overrides */
		.dark-mode-toggle, .box-search, .box-datepicker { display: none !important; }
		.main-container { padding: 20px !important; }
		.event_listing { break-inside: avoid; page-break-inside: avoid; }
		<?php endif; ?>
	</style>
</head>

<body<?php echo $is_print ? '' : ' class="apollo-canvas-mode"'; ?>>

	<?php if ( ! $is_print ) : ?>
	<!-- NAVBAR: New navbar is loaded via wp_footer hook from class-apollo-navbar-apps.php -->
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

	<?php
	// Load the new official navbar via wp_footer hook
	if ( ! $is_print ) {
		wp_footer();
	}
	?>
</body>
</html>
