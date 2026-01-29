<?php
/**
 * Core Template: DJ Single Page (Roster Profile)
 * =============================================
 * Path: apollo-core/templates/core-dj-single.php
 * Called by: apollo-events-manager/templates/single-event_dj.php
 *
 * CONTEXT CONTRACT (Required Variables):
 * --------------------------------------
 *
 * @var int    $dj_id              DJ post ID
 * @var string $dj_name            DJ display name (title)
 * @var string $dj_name_formatted  DJ name with line breaks for display
 * @var string $dj_photo_url       Hero photo URL
 * @var string $dj_tagline         Tagline/subtitle
 * @var string $dj_roles           Roles string (e.g., "DJ · Producer · Live Selector")
 * @var array  $dj_projects        Array of project/label names
 * @var string $dj_bio_excerpt     Short bio excerpt
 * @var string $dj_bio_full        Full bio HTML
 * @var string $dj_track_title     Featured track title
 * @var string $sc_embed_url       SoundCloud embed URL (full iframe src)
 * @var array  $music_links        Filtered music platform links
 * @var array  $social_links       Filtered social network links
 * @var array  $asset_links        Filtered asset links (media kit, rider)
 * @var array  $platform_links     Filtered other platform links
 * @var string $media_kit_url      Direct media kit URL for header button
 * @var bool   $is_print           Print mode flag
 *
 * LINK ARRAY STRUCTURE:
 * Each link: ['url' => string, 'icon' => string, 'label' => string]
 */

defined( 'ABSPATH' ) || exit;

// CDN with local fallback
$cdn_base     = 'https://assets.apollo.rio.br/';
$local_base   = defined( 'APOLLO_CORE_PLUGIN_URL' )
	? APOLLO_CORE_PLUGIN_URL . 'assets/core/'
	: plugin_dir_url( __DIR__ ) . 'assets/core/';
$local_img    = defined( 'APOLLO_CORE_PLUGIN_URL' )
	? APOLLO_CORE_PLUGIN_URL . 'assets/img/'
	: plugin_dir_url( __DIR__ ) . 'assets/img/';

// Ensure required variables have defaults.
$dj_id             = $dj_id ?? 0;
$dj_name           = $dj_name ?? 'DJ';
$dj_name_formatted = $dj_name_formatted ?? $dj_name;
$dj_photo_url      = $dj_photo_url ?? $local_img . 'placeholder-dj.webp';
$dj_tagline        = $dj_tagline ?? '';
$dj_roles          = $dj_roles ?? 'DJ';
$dj_projects       = $dj_projects ?? array();
$dj_bio_excerpt    = $dj_bio_excerpt ?? '';
$dj_bio_full       = $dj_bio_full ?? '';
$dj_track_title    = $dj_track_title ?? '';
$sc_embed_url      = $sc_embed_url ?? '';
$music_links       = $music_links ?? array();
$social_links      = $social_links ?? array();
$asset_links       = $asset_links ?? array();
$platform_links    = $platform_links ?? array();
$media_kit_url     = $media_kit_url ?? '';
$is_print          = $is_print ?? false;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $dj_name ); ?> · Apollo Roster</title>
	<link rel="icon" href="<?php echo esc_url( $local_img . 'neon-green.webp' ); ?>" type="image/webp">

	<!-- Apollo CDN Loader - Auto-loads CSS, icons, dark mode, etc. -->
	<script src="https://assets.apollo.rio.br/index.min.js"></script>
	<?php if ( $sc_embed_url ) : ?>
	<script src="https://w.soundcloud.com/player/api.js"></script>
	<?php endif; ?>

	<style>
		/* ============================================================
			[CSS_VARIABLES] Design Tokens - Using Apollo Design System
			Note: These override/extend the CDN tokens for DJ-specific styles
			============================================================ */
		:root {
			--font-primary: var(--ap-font-display, "Roboto", Roboto, system-ui, sans-serif);
			--fly-border: var(--ap-border-light, rgba(148, 163, 184, 0.32));
			--fly-shadow: var(--ap-shadow-xl, 0 20px 50px rgba(15, 23, 42, 0.05));
			--fly-muted: var(--ap-text-muted, #6b7280);
			--fly-strong: var(--ap-text-dark, #020617);
			--bg-main: var(--ap-bg, #ffffff);
			--bg-surface: var(--ap-bg-muted, #f5f5f5);
			--text-main: var(--ap-text-muted, rgba(19, 21, 23, 0.6));
			--text-primary: var(--ap-text, rgba(19, 21, 23, 0.9));
			--text-secondary: var(--ap-text-muted, rgba(19, 21, 23, 0.6));
			--radius-main: var(--ap-radius-2xl, 18px);
			--transition-main: var(--ap-transition, all 0.25s ease);
		}

		/* ============================================================
			[BASE_STYLES] Body Resets
			============================================================ */
		* {
			corner-shape: squircle;
			box-sizing: border-box;
			-webkit-tap-highlight-color: transparent;
			margin: 0;
			padding: 0;
		}

		html, body {
			font-family: var(--font-primary);
			font-size: 16px;
			background: var(--bg-main);
			color: var(--text-main);
		}

		a { color: inherit; text-decoration: none; }
		img { display: block; max-width: 100%; }

		/* ============================================================
			[LAYOUT::DJ_PAGE_SHELL] Main Container
			============================================================ */
		.dj-shell {
			min-height: 100vh;
			padding: 1.5rem 1rem;
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.dj-page {
			width: 100%;
			max-width: 480px;
			border-radius: 24px;
			border: 1px solid var(--fly-border);
			background: #ffffff;
			box-shadow: var(--fly-shadow);
			padding: 1.5rem;
			position: relative;
			overflow: hidden;
		}

		@media (min-width: 900px) {
			.dj-page {
				max-width: 680px;
				padding: 2rem;
			}
		}

		.dj-page::before {
			content: "";
			position: absolute;
			inset: 0;
			background-image:
				linear-gradient(to right, rgba(148, 163, 184, 0.08) 1px, transparent 1px),
				linear-gradient(to bottom, rgba(148, 163, 184, 0.08) 1px, transparent 1px);
			background-size: 30px 30px;
			opacity: 0.45;
			pointer-events: none;
			z-index: 0;
		}

		.dj-content {
			position: relative;
			z-index: 1;
			display: flex;
			flex-direction: column;
			gap: 2rem;
		}

		/* ============================================================
			[BLOCK::HEADER] DJ Header Section
			============================================================ */
		.dj-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 1rem;
		}

		.dj-header-left span {
			display: block;
			text-transform: uppercase;
			letter-spacing: 0.2em;
			font-size: 0.6rem;
			color: #9ca3af;
		}

		.dj-header-left strong {
			font-size: 0.75rem;
			letter-spacing: 0.18em;
			color: #111827;
		}

		/* [BUTTON::MEDIA_KIT] Media Kit Pill Button */
		.dj-pill-link {
			border-radius: 999px;
			padding: 0.4rem 1rem;
			font-size: 0.7rem;
			display: inline-flex;
			align-items: center;
			gap: 0.35rem;
			border: 1px solid rgba(148, 163, 184, 0.8);
			background: #f9fafb;
			color: #020617;
			transition: var(--transition-main);
		}

		.dj-pill-link:hover {
			background: #020617;
			color: #f9fafb;
			transform: translateY(-1px);
		}

		/* ============================================================
			[BLOCK::HERO] DJ Hero Section
			============================================================ */
		.dj-hero {
			display: grid;
			grid-template-columns: 1.3fr 1fr;
			gap: 1rem;
			align-items: start;
		}

		@media (max-width: 640px) {
			.dj-hero { grid-template-columns: 1fr; }
		}

		.dj-tagline {
			text-transform: uppercase;
			letter-spacing: 0.2em;
			font-size: 0.6rem;
			color: #9ca3af;
			margin-bottom: 0.5rem;
		}

		.dj-name-main {
			font-size: 1.5rem;
			letter-spacing: 0.16em;
			text-transform: uppercase;
			font-weight: 600;
			line-height: 1.2;
			margin-bottom: 0.5rem;
		}

		.dj-name-sub {
			font-size: 0.75rem;
			letter-spacing: 0.14em;
			text-transform: uppercase;
			color: #4b5563;
			margin-bottom: 0.75rem;
		}

		/* [ELEMENT::PROJECT_TAGS] DJ Projects/Labels */
		.dj-projects {
			display: flex;
			flex-wrap: wrap;
			gap: 0.35rem;
			margin-bottom: 35px;
		}

		.dj-projects span {
			border-radius: 999px;
			padding: 0.15rem 0.65rem;
			border: 1px solid rgba(148, 163, 184, 0.5);
			background: #f9fafb;
			font-size: 0.65rem;
			text-transform: uppercase;
			letter-spacing: 0.14em;
			color: #6b7280;
			font-weight: 600;
		}

		/* [ELEMENT::DJ_PHOTO] Hero Photo as Cover */
		.dj-hero-photo {
			margin: 35px auto;
			border-radius: 16px;
			overflow: hidden;
			position: relative;
			width: 100%;
			min-width: 250px;
			aspect-ratio: 3 / 4;
			margin: 0;
			border: none;
			box-shadow: 0 18px 40px rgba(15, 23, 42, 0.10);
			transform: rotate(2.8deg) scale(1.13) translateX(30px);
		}

		@media (max-width: 640px) {
			.dj-hero-photo {
				transform: none;
				min-width: auto;
			}
		}

		.dj-hero-photo img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			object-position: center center;
		}

		/* ============================================================
			[WIDGET::VINYL_PLAYER] SoundCloud Vinyl Player
			============================================================ */
		.dj-player-block {
			display: flex;
			flex-direction: column;
			gap: 1rem;
			margin-top: 35px;
		}

		.dj-player-title {
			font-size: 0.65rem;
			text-transform: uppercase;
			letter-spacing: 0.18em;
			color: #9ca3af;
		}

		.dj-player-sub {
			font-size: 0.75rem;
			letter-spacing: 0.12em;
			text-transform: uppercase;
			color: #111827;
			font-weight: 600;
		}

		.vinyl-zone {
			display: flex;
			justify-content: center;
			align-items: center;
			padding: 1rem 0;
		}

		.vinyl-player {
			position: relative;
			width: min(72vw, 260px);
			aspect-ratio: 1 / 1;
			transition: transform 0.3s ease;
			cursor: pointer;
			border-radius: 30%;
		}

		.vinyl-disc {
			position: relative;
			width: 100%;
			height: auto;
			aspect-ratio: 1 / 1;
			border-radius: 999px;
			background-image: url('<?php echo esc_url( $asset_base . 'img/vinyl.webp' ); ?>');
			background-repeat: no-repeat;
			background-position: center center;
			background-size: 100% 100%;
			box-shadow: 0 18px 40px rgba(15, 23, 42, 0.05);
			overflow: hidden;
			display: flex;
			align-items: center;
			justify-content: center;
			animation: vinyl-spin 10s linear infinite;
			animation-play-state: paused;
		}

		.is-playing .vinyl-disc {
			animation-play-state: running;
		}

		@keyframes vinyl-spin {
			to { transform: rotate(360deg); }
		}

		.vinyl-label {
			corner-shape: round;
			width: 40%;
			height: 40%;
			border-radius: 50%;
			padding: 0.75rem;
			border: 1px solid rgba(148, 163, 184, 0.5);
			display: flex;
			align-items: center;
			justify-content: center;
			position: relative;
			z-index: 2;
			background: rgba(249, 250, 251, 0.96);
		}

		.vinyl-label-text {
			font-size: 0.65rem;
			letter-spacing: 0.12em;
			text-transform: uppercase;
			color: #111827;
			text-align: center;
			line-height: 1.4;
		}

		.now-playing {
			font-size: 0.65rem;
			text-transform: uppercase;
			letter-spacing: 0.14em;
			color: var(--fly-muted);
			text-align: center;
			margin: 0.5rem 0;
		}

		.now-playing strong { color: #111827; }

		/* [BUTTON::PLAYER_CTA] Play/Pause Button */
		.btn-player-main {
			border-radius: 999px;
			padding: 0.6rem 1.25rem;
			font-size: 0.8rem;
			font-weight: 500;
			border: 1px solid #020617;
			background: #020617;
			color: #f9fafb;
			display: inline-flex;
			align-items: center;
			gap: 0.5rem;
			cursor: pointer;
			transition: var(--transition-main);
		}

		.btn-player-main:hover {
			transform: translateY(-1px);
			box-shadow: 0 8px 20px rgba(15, 23, 42, 0.15);
		}

		/* Hidden SoundCloud iframe */
		#scPlayer {
			position: absolute;
			opacity: 0;
			pointer-events: none;
			width: 1px;
			height: 1px;
			left: -9999px;
		}

		.player-cta-row {
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 0.5rem;
		}

		.player-note {
			font-size: 0.6rem;
			color: #9ca3af;
			text-align: center;
		}

		/* ============================================================
			[BLOCK::INFO_GRID] Bio + Links Section
			============================================================ */
		.dj-info-grid {
			display: grid;
			grid-template-columns: 1.2fr 1fr;
			gap: 1.5rem;
		}

		@media (max-width: 640px) {
			.dj-info-grid { grid-template-columns: 1fr; }
		}

		.dj-info-block h2 {
			font-size: 0.75rem;
			letter-spacing: 0.18em;
			text-transform: uppercase;
			color: #111827;
			margin-bottom: 0.75rem;
		}

		.dj-bio-excerpt {
			font-size: 0.75rem;
			color: #4b5563;
			line-height: 1.6;
			max-height: 10rem;
			overflow: hidden;
			position: relative;
		}

		.dj-bio-excerpt::after {
			content: "";
			position: absolute;
			bottom: 0;
			left: 0;
			right: 0;
			height: 2rem;
			background: linear-gradient(to bottom, rgba(255, 255, 255, 0), #ffffff);
		}

		/* [BUTTON::BIO_TOGGLE] Read Full Bio Button */
		.dj-bio-toggle {
			margin-top: 0.5rem;
			font-size: 0.65rem;
			text-transform: uppercase;
			letter-spacing: 0.14em;
			color: #0f172a;
			display: inline-flex;
			align-items: center;
			gap: 0.35rem;
			cursor: pointer;
			border: none;
			background: none;
			padding: 0;
		}

		/* [ELEMENT::LINKS_SECTION] Social & Music Links */
		.dj-links-label {
			font-size: 0.65rem;
			text-transform: uppercase;
			letter-spacing: 0.16em;
			color: #9ca3af;
			padding-top: 0.75rem;
			margin-bottom: 0.35rem;
		}

		.dj-links-row {
			display: flex;
			flex-wrap: wrap;
			gap: 0.35rem;
		}

		/* [BUTTON::LINK_PILL] Social/Platform Link Button */
		.dj-link-pill {
			display: inline-flex;
			align-items: center;
			gap: 0.5rem;
			font-size: 0.68rem;
			padding: 0.2rem 0.65rem;
			border-radius: 999px;
			border: 1px solid rgba(148, 163, 184, 0.7);
			background: #f9fafb;
			color: #111827;
			transition: var(--transition-main);
		}

		.dj-link-pill i { font-size: 0.9rem; }

		.dj-link-pill:hover {
			background: #0f172a;
			color: #f9fafb;
			transform: translateY(-1px);
		}

		.dj-link-pill.active {
			background: #020617;
			color: #f9fafb;
			border-color: #020617;
		}

		/* ============================================================
			[BLOCK::FOOTER] Page Footer
			============================================================ */
		.dj-footer {
			margin-top: 1rem;
			padding-top: 1rem;
			border-top: 1px solid rgba(148, 163, 184, 0.2);
			font-size: 0.6rem;
			color: #9ca3af;
			text-transform: uppercase;
			letter-spacing: 0.16em;
			display: flex;
			justify-content: space-between;
			gap: 1rem;
		}

		/* ============================================================
			[MODAL::BIO_FULL] Full Bio Modal
			============================================================ */
		.dj-bio-modal-backdrop {
			position: fixed;
			inset: 0;
			background: rgba(15, 23, 42, 0.3);
			backdrop-filter: blur(8px);
			display: none;
			align-items: center;
			justify-content: center;
			padding: 1rem;
			z-index: 1000;
		}

		.dj-bio-modal-backdrop[data-open="true"] {
			display: flex;
		}

		.dj-bio-modal {
			max-width: 520px;
			max-height: 85vh;
			width: 100%;
			border-radius: 20px;
			background: #ffffff;
			border: 1px solid rgba(148, 163, 184, 0.4);
			box-shadow: 0 20px 50px rgba(15, 23, 42, 0.2);
			padding: 1.5rem;
			display: flex;
			flex-direction: column;
			gap: 1rem;
		}

		.dj-bio-modal-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			gap: 1rem;
		}

		.dj-bio-modal-header h3 {
			font-size: 0.75rem;
			text-transform: uppercase;
			letter-spacing: 0.18em;
			color: #0f172a;
		}

		.dj-bio-modal-close {
			border-radius: 50%;
			border: 1px solid rgba(148, 163, 184, 0.6);
			background: #f9fafb;
			width: 32px;
			height: 32px;
			display: flex;
			align-items: center;
			justify-content: center;
			cursor: pointer;
			font-size: 1rem;
			transition: var(--transition-main);
		}

		.dj-bio-modal-close:hover { background: #e5e7eb; }

		.dj-bio-modal-body {
			overflow-y: auto;
			font-size: 0.8rem;
			line-height: 1.6;
			color: #4b5563;
		}

		.dj-bio-modal-body p + p { margin-top: 1rem; }

		/* ============================================================
			[PRINT] Print Styles
			============================================================ */
		<?php if ( $is_print ) : ?>
		@media print {
			.dj-shell { padding: 0; }
			.dj-page { box-shadow: none; border: none; max-width: 100%; }
			.dj-pill-link, .btn-player-main, .dj-bio-toggle { display: none !important; }
			.vinyl-zone { display: none !important; }
			.dj-bio-excerpt { max-height: none; }
			.dj-bio-excerpt::after { display: none; }
		}
		<?php endif; ?>
	</style>
</head>

<body>

<!-- ======================================================================
	[LAYOUT::DJ_SHELL] Main DJ Page Container
	====================================================================== -->
<section class="dj-shell">
	<div class="dj-page" id="djPage">
		<div class="dj-content">

			<!-- ==================================================================
				[BLOCK::HEADER] DJ Header with Name + Media Kit
				================================================================== -->
			<header class="dj-header">
				<div class="dj-header-left">
					<span>Apollo::rio · DJ Roster</span>
					<strong id="dj-header-name"><?php echo esc_html( strtoupper( $dj_name ) ); ?></strong>
				</div>

				<?php if ( $media_kit_url ) : ?>
					<a href="<?php echo esc_url( $media_kit_url ); ?>" id="mediakit-link" class="dj-pill-link" target="_blank" rel="noopener noreferrer">
						<i class="ri-clipboard-line"></i>
						Media kit
					</a>
				<?php endif; ?>
			</header>

			<!-- ==================================================================
				[BLOCK::HERO] DJ Hero Section
				================================================================== -->
			<section class="dj-hero" id="djHero">
				<div class="dj-hero-name">
					<?php if ( $dj_tagline ) : ?>
						<div class="dj-tagline" id="dj-tagline"><?php echo esc_html( $dj_tagline ); ?></div>
					<?php endif; ?>

					<div class="dj-name-main" id="dj-name"><?php echo wp_kses_post( $dj_name_formatted ); ?></div>
					<div class="dj-name-sub" id="dj-roles"><?php echo esc_html( $dj_roles ); ?></div>

					<?php if ( ! empty( $dj_projects ) ) : ?>
						<div class="dj-projects" id="dj-projects">
							<?php foreach ( $dj_projects as $project ) : ?>
								<span><?php echo esc_html( $project ); ?></span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>

				<figure class="dj-hero-photo" id="djPhoto">
					<img id="dj-avatar" src="<?php echo esc_url( $dj_photo_url ); ?>" alt="<?php echo esc_attr( sprintf( 'Retrato de %s', $dj_name ) ); ?>">
				</figure>
			</section>

			<!-- ==================================================================
				[WIDGET::VINYL_PLAYER] SoundCloud Vinyl Player
				================================================================== -->
			<?php if ( $sc_embed_url ) : ?>
				<section class="dj-player-block" id="djPlayerBlock">
					<div>
						<div class="dj-player-title">Feature set para escuta</div>
						<?php if ( $dj_track_title ) : ?>
							<div class="dj-player-sub" id="track-title"><?php echo esc_html( $dj_track_title ); ?></div>
						<?php endif; ?>
					</div>

					<main class="vinyl-zone">
						<div class="vinyl-player is-paused" id="vinylPlayer" role="button" aria-label="Play / Pause set">
							<div class="vinyl-disc">
								<div class="vinyl-label">
									<div class="vinyl-label-text" id="vinylLabelText"><?php echo wp_kses_post( $dj_name_formatted ); ?></div>
								</div>
							</div>
						</div>
					</main>

					<p class="now-playing">Set de referência em destaque no <strong>SoundCloud</strong>.</p>

					<iframe id="scPlayer" scrolling="no" frameborder="no" allow="autoplay" src="<?php echo esc_url( $sc_embed_url ); ?>"></iframe>

					<div class="player-cta-row">
						<button class="btn-player-main" id="vinylToggle" type="button">
							<i class="ri-play-fill" id="vinylIcon"></i>
							<span>Play / Pause set</span>
						</button>
						<p class="player-note">Contato e condições completas no media kit e rider técnico.</p>
					</div>
				</section>
			<?php endif; ?>

			<!-- ==================================================================
				[BLOCK::INFO_GRID] Bio + Links Section
				================================================================== -->
			<section class="dj-info-grid">

				<!-- [BLOCK::BIO] About Section -->
				<div class="dj-info-block">
					<h2>Sobre</h2>
					<div class="dj-bio-excerpt" id="dj-bio-excerpt"><?php echo esc_html( $dj_bio_excerpt ); ?></div>
					<button type="button" class="dj-bio-toggle" id="bioToggle">
						<span>ler bio completa</span>
						<i class="ri-arrow-right-up-line"></i>
					</button>
				</div>

				<!-- [BLOCK::LINKS] Social & Platform Links -->
				<div class="dj-info-block">
					<h2>Links principais</h2>

					<!-- Music Links -->
					<?php if ( ! empty( $music_links ) ) : ?>
						<div>
							<div class="dj-links-label">Música</div>
							<div class="dj-links-row" id="music-links">
								<?php $first = true; foreach ( $music_links as $link ) : ?>
									<a href="<?php echo esc_url( $link['url'] ); ?>" class="dj-link-pill <?php echo $first ? 'active' : ''; ?>" target="_blank" rel="noopener noreferrer">
										<i class="<?php echo esc_attr( $link['icon'] ); ?>"></i>
										<span><?php echo esc_html( $link['label'] ); ?></span>
									</a>
									<?php
									$first = false;
endforeach;
								?>
							</div>
						</div>
					<?php endif; ?>

					<!-- Social Links -->
					<?php if ( ! empty( $social_links ) ) : ?>
						<div>
							<div class="dj-links-label">Social</div>
							<div class="dj-links-row" id="social-links">
								<?php foreach ( $social_links as $link ) : ?>
									<a href="<?php echo esc_url( $link['url'] ); ?>" class="dj-link-pill" target="_blank" rel="noopener noreferrer">
										<i class="<?php echo esc_attr( $link['icon'] ); ?>"></i>
										<span><?php echo esc_html( $link['label'] ); ?></span>
									</a>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>

					<!-- Assets Links -->
					<?php if ( ! empty( $asset_links ) ) : ?>
						<div>
							<div class="dj-links-label">Assets</div>
							<div class="dj-links-row" id="asset-links">
								<?php foreach ( $asset_links as $link ) : ?>
									<a href="<?php echo esc_url( $link['url'] ); ?>" class="dj-link-pill" target="_blank" rel="noopener noreferrer">
										<i class="<?php echo esc_attr( $link['icon'] ); ?>"></i>
										<span><?php echo esc_html( $link['label'] ); ?></span>
									</a>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>

					<!-- Other Platforms -->
					<?php if ( ! empty( $platform_links ) ) : ?>
						<div>
							<div class="dj-links-label">Outras plataformas</div>
							<div class="dj-links-row" id="other-links">
								<?php foreach ( $platform_links as $link ) : ?>
									<a href="<?php echo esc_url( $link['url'] ); ?>" class="dj-link-pill" target="_blank" rel="noopener noreferrer">
										<i class="<?php echo esc_attr( $link['icon'] ); ?>"></i>
										<span><?php echo esc_html( $link['label'] ); ?></span>
									</a>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</section>

			<!-- ==================================================================
				[BLOCK::FOOTER] Page Footer
				================================================================== -->
			<footer class="dj-footer">
				<span>Apollo::rio<br>Roster preview</span>
				<span>Para bookers,<br>selos e clubes</span>
			</footer>

		</div>
	</div>
</section>

<!-- ======================================================================
	[MODAL::BIO_FULL] Full Bio Modal
	====================================================================== -->
<div class="dj-bio-modal-backdrop" id="bioBackdrop" data-open="false">
	<div class="dj-bio-modal">
		<div class="dj-bio-modal-header">
			<h3 id="dj-bio-modal-title">Bio completa · <?php echo esc_html( $dj_name ); ?></h3>
			<button type="button" class="dj-bio-modal-close" id="bioClose">
				<i class="ri-close-line"></i>
			</button>
		</div>
		<div class="dj-bio-modal-body" id="bio-full">
			<?php echo wp_kses_post( $dj_bio_full ); ?>
		</div>
	</div>
</div>

<?php wp_footer(); ?>

<!-- ======================================================================
	[SCRIPTS::MAIN] JavaScript Logic
	====================================================================== -->
<?php if ( $sc_embed_url ) : ?>
<script>
(function() {
	'use strict';

	// SoundCloud Widget Controller.
	let scWidget = null;
	let widgetReady = false;

	function setVinylState(isPlaying) {
		const vinylPlayer = document.getElementById("vinylPlayer");
		const icon = document.getElementById("vinylIcon");
		if (!vinylPlayer || !icon) return;

		if (isPlaying) {
			vinylPlayer.classList.add("is-playing");
			vinylPlayer.classList.remove("is-paused");
			icon.classList.remove("ri-play-fill");
			icon.classList.add("ri-pause-fill");
		} else {
			vinylPlayer.classList.remove("is-playing");
			vinylPlayer.classList.add("is-paused");
			icon.classList.remove("ri-pause-fill");
			icon.classList.add("ri-play-fill");
		}
	}

	function toggleVinylPlayback() {
		if (!widgetReady || !scWidget) return;
		scWidget.isPaused(function(paused) {
			if (paused) {
				scWidget.play();
			} else {
				scWidget.pause();
			}
		});
	}

	document.addEventListener("DOMContentLoaded", function() {
		// Init SoundCloud Widget.
		const iframe = document.getElementById("scPlayer");
		if (iframe && window.SC && typeof SC.Widget === "function") {
			scWidget = SC.Widget(iframe);
			scWidget.bind(SC.Widget.Events.READY, function() {
				widgetReady = true;
			});
			scWidget.bind(SC.Widget.Events.PLAY, function() { setVinylState(true); });
			scWidget.bind(SC.Widget.Events.PAUSE, function() { setVinylState(false); });
			scWidget.bind(SC.Widget.Events.FINISH, function() { setVinylState(false); });
		}

		// Vinyl Click Events.
		const vinylPlayer = document.getElementById("vinylPlayer");
		const vinylToggle = document.getElementById("vinylToggle");
		[vinylPlayer, vinylToggle].forEach(function(el) {
			if (!el) return;
			el.addEventListener("click", function(e) {
				e.preventDefault();
				toggleVinylPlayback();
			});
		});

		// Bio Modal Controls.
		const bioToggleBtn = document.getElementById("bioToggle");
		const bioBackdrop = document.getElementById("bioBackdrop");
		const bioClose = document.getElementById("bioClose");

		function openBio() {
			if (!bioBackdrop) return;
			bioBackdrop.setAttribute("data-open", "true");
			document.body.style.overflow = "hidden";
		}

		function closeBio() {
			if (!bioBackdrop) return;
			bioBackdrop.setAttribute("data-open", "false");
			document.body.style.overflow = "";
		}

		if (bioToggleBtn) bioToggleBtn.addEventListener("click", openBio);
		if (bioClose) bioClose.addEventListener("click", closeBio);
		if (bioBackdrop) {
			bioBackdrop.addEventListener("click", function(e) {
				if (e.target === bioBackdrop) closeBio();
			});
		}
	});
})();
</script>
<?php else : ?>
<script>
(function() {
	'use strict';

	document.addEventListener("DOMContentLoaded", function() {
		// Bio Modal Controls (when no player).
		const bioToggleBtn = document.getElementById("bioToggle");
		const bioBackdrop = document.getElementById("bioBackdrop");
		const bioClose = document.getElementById("bioClose");

		function openBio() {
			if (!bioBackdrop) return;
			bioBackdrop.setAttribute("data-open", "true");
			document.body.style.overflow = "hidden";
		}

		function closeBio() {
			if (!bioBackdrop) return;
			bioBackdrop.setAttribute("data-open", "false");
			document.body.style.overflow = "";
		}

		if (bioToggleBtn) bioToggleBtn.addEventListener("click", openBio);
		if (bioClose) bioClose.addEventListener("click", closeBio);
		if (bioBackdrop) {
			bioBackdrop.addEventListener("click", function(e) {
				if (e.target === bioBackdrop) closeBio();
			});
		}
	});
})();
</script>
<?php endif; ?>

</body>
</html>
