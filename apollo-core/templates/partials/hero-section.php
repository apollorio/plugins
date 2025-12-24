<?php
/**
 * Hero Section Partial - Apollo Design System
 *
 * Displays hero section for events discovery portal
 *
 * @param array $args {
 *     @type string $title           Hero title
 *     @type string $subtitle        Hero subtitle
 *     @type string $background_image Background image URL
 * }
 */

// Set defaults.
$args = wp_parse_args(
	$args ?? array(),
	array(
		'title'            => '',
		'subtitle'         => '',
		'background_image' => '',
	)
);

$title            = esc_html( $args['title'] );
$subtitle         = esc_html( $args['subtitle'] );
$background_image = esc_url( $args['background_image'] );
?>

<section class="hero-section" data-tooltip="<?php esc_attr_e( 'Seção principal', 'apollo-events-manager' ); ?>">
	<h1 class="title-page" data-tooltip="<?php esc_attr_e( 'Título da página', 'apollo-events-manager' ); ?>"><?php echo $title; ?></h1>
	<p class="subtitle-page" data-tooltip="<?php esc_attr_e( 'Descrição do portal', 'apollo-events-manager' ); ?>"><?php echo $subtitle; ?></p>
</section>

<style>
.hero-section {
	padding: 4rem 2rem;
	text-align: center;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	position: relative;
	overflow: hidden;
}

.hero-section::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: url('<?php echo $background_image ?: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?q=80&w=2070'; ?>') no-repeat center;
	background-size: cover;
	opacity: 0.1;
	z-index: 0;
}

.title-page {
	position: relative;
	z-index: 1;
	font-size: 3.5rem;
	font-weight: 800;
	color: white;
	margin-bottom: 1.5rem;
	text-shadow: 0 2px 4px rgba(0,0,0,0.3);
	line-height: 1.1;
}

.subtitle-page {
	position: relative;
	z-index: 1;
	font-size: 1.25rem;
	color: rgba(255, 255, 255, 0.9);
	max-width: 600px;
	margin: 0 auto;
	line-height: 1.6;
	text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.subtitle-page mark {
	background: linear-gradient(45deg, #FFA17F, #fe786d);
	-webkit-background-clip: text;
	-webkit-text-fill-color: transparent;
	background-clip: text;
	font-weight: 600;
}

/* Mobile responsive */
@media (max-width: 768px) {
	.hero-section {
		padding: 3rem 1rem;
	}

	.title-page {
		font-size: 2.5rem;
	}

	.subtitle-page {
		font-size: 1.1rem;
	}
}
</style>
