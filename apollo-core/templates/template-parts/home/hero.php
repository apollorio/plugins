<?php
/**
 * Apollo Home Hero Section
 *
 * Video background hero with title and CTA
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

defined( 'ABSPATH' ) || exit;

// Allow customization via args.
$args = $args ?? array();

$hero_title     = $args['title'] ?? __( 'Não apenas veja.', 'apollo-core' );
$hero_subtitle  = $args['subtitle'] ?? __( 'Venha viver junto!', 'apollo-core' );
$hero_text      = $args['text'] ?? __( 'Sua nova ferramenta de cultura digital e de cadeia produtiva da cultura do Rio de Janeiro. Com caráter inovador, é orientado à economia criativa e à difusão do acesso à arte, música e cultura carioca.', 'apollo-core' );
$cta_text       = $args['cta_text'] ?? __( 'Explorar', 'apollo-core' );
$cta_url        = $args['cta_url'] ?? '#events';
$login_url      = $args['login_url'] ?? wp_login_url();
$video_webm     = $args['video_webm'] ?? 'https://assets.apollo.rio.br/vid/v2.webm';
$video_mp4      = $args['video_mp4'] ?? 'https://assets.apollo.rio.br/vid/v2.mp4';
$video_poster   = $args['video_poster'] ?? '/img/hero-fallback.jpg';
?>


<!-- HEADER -->
<header class="a-hero-aprio-hero-header inverso">
	<div class="a-hero-aprio-hero-brand">
		<i class="apollo i-logo-hero" style="position:absolute;top:6px;left:17px;"></i>
		<a class="txt-logo-hero">apollo<span class="ap-logo-squ">::</span>rio</a>
	</div>
	<?php if ( ! is_user_logged_in() ) : ?>


	<?php endif; ?>
</header>

<!-- HERO -->
<section class="a-hero-aprio-hero">
	<!-- Video Background -->
	<video class="a-hero-hero-video" autoplay muted loop playsinline preload="auto"
		poster="<?php echo esc_url( $video_poster ); ?>">
		<source src="<?php echo esc_url( $video_webm ); ?>" type="video/webm">
		<source src="<?php echo esc_url( $video_mp4 ); ?>" type="video/mp4">
	</video>

	<h1 class="a-hero-aprio-hero-title reveal-up">
		<?php echo esc_html( $hero_title ); ?><br>
		<span class="vetxt"><?php echo esc_html( $hero_subtitle ); ?></span>
	</h1>

	<p class="a-hero-aprio-hero-text reveal-up" style="transition-delay:0.1s;">
		<?php echo esc_html( $hero_text ); ?>
	</p>

	<div class="reveal-up" style="transition-delay:0.2s;">
		<a href="<?php echo esc_url( $cta_url ); ?>"
			class="a-hero-aprio-hero-btn"><?php echo esc_html( $cta_text ); ?></a>
	</div>
</section>