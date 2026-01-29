<?php
/**
 * Template Part: DJ Single - Hero Section
 * ========================================
 * Path: apollo-core/templates/parts/dj/hero.php
 *
 * @var string $dj_name_formatted DJ name with line breaks
 * @var string $dj_tagline        DJ tagline/subtitle
 * @var string $dj_roles          DJ roles string
 * @var array  $dj_projects       Array of project names
 * @var string $dj_photo_url      Hero photo URL
 * @var string $dj_name           Plain DJ name (for alt text)
 *
 * @package Apollo_Core
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

$dj_name_formatted = $dj_name_formatted ?? 'DJ';
$dj_tagline        = $dj_tagline ?? '';
$dj_roles          = $dj_roles ?? 'DJ';
$dj_projects       = $dj_projects ?? array();
$dj_photo_url      = $dj_photo_url ?? '';
$dj_name           = $dj_name ?? 'DJ';
?>

<section class="dj-hero" id="djHero">
	<div class="dj-hero-name">
		<?php if ( ! empty( $dj_tagline ) ) : ?>
		<div class="dj-tagline" id="dj-tagline">
			<?php echo esc_html( $dj_tagline ); ?>
		</div>
		<?php endif; ?>

		<div class="dj-name-main" id="dj-name">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped in formatting function
			echo $dj_name_formatted;
			?>
		</div>

		<div class="dj-name-sub" id="dj-roles">
			<?php echo esc_html( $dj_roles ); ?>
		</div>

		<?php if ( ! empty( $dj_projects ) && is_array( $dj_projects ) ) : ?>
		<div class="dj-projects" id="dj-projects">
			<?php foreach ( $dj_projects as $project ) : ?>
				<span><?php echo esc_html( $project ); ?></span>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $dj_photo_url ) ) : ?>
	<figure class="dj-hero-photo" id="djPhoto">
		<img
			id="dj-avatar"
			src="<?php echo esc_url( $dj_photo_url ); ?>"
			alt="<?php echo esc_attr( sprintf( __( 'Retrato de %s', 'apollo-core' ), $dj_name ) ); ?>"
			loading="lazy"
			decoding="async"
		>
	</figure>
	<?php endif; ?>
</section>
