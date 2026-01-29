<?php
/**
 * Classified Card Partial
 *
 * Renders a single classified card for use in loops and grids.
 *
 * @package Apollo\Templates\Classifieds
 * @since 2.2.0
 *
 * @param WP_Post $post    The classified post object.
 * @param array   $options Optional display options.
 */

defined( 'ABSPATH' ) || exit;

use Apollo\Modules\Classifieds\ClassifiedsModule;

// Ensure we have a post
if ( ! isset( $post ) || ! $post instanceof WP_Post ) {
	return;
}

$post_id = $post->ID;

// Taxonomies
$domains = wp_get_object_terms( $post_id, ClassifiedsModule::TAX_DOMAIN, array( 'fields' => 'all' ) );
$intents = wp_get_object_terms( $post_id, ClassifiedsModule::TAX_INTENT, array( 'fields' => 'all' ) );

$domain      = ! empty( $domains ) && ! is_wp_error( $domains ) ? $domains[0] : null;
$domain_slug = $domain ? $domain->slug : '';
$domain_name = $domain ? $domain->name : '';

$intent      = ! empty( $intents ) && ! is_wp_error( $intents ) ? $intents[0] : null;
$intent_slug = $intent ? $intent->slug : '';
$intent_name = $intent ? $intent->name : '';

// Meta
$price      = get_post_meta( $post_id, ClassifiedsModule::META_KEYS['price'], true );
$location   = get_post_meta( $post_id, ClassifiedsModule::META_KEYS['location'], true );
$event_date = get_post_meta( $post_id, ClassifiedsModule::META_KEYS['event_date'], true );
$start_date = get_post_meta( $post_id, ClassifiedsModule::META_KEYS['start_date'], true );
$end_date   = get_post_meta( $post_id, ClassifiedsModule::META_KEYS['end_date'], true );

// Format price
$price_formatted = $price ? 'R$ ' . number_format( (float) $price, 2, ',', '.' ) : '';

// Format dates
$format_date = function ( $yyyymmdd ) {
	if ( strlen( $yyyymmdd ) !== 8 ) {
		return $yyyymmdd;
	}
	return substr( $yyyymmdd, 6, 2 ) . '/' . substr( $yyyymmdd, 4, 2 ) . '/' . substr( $yyyymmdd, 0, 4 );
};

$date_label = '';
if ( $domain_slug === 'ingressos' && $event_date ) {
	$date_label = $format_date( $event_date );
} elseif ( $domain_slug === 'acomodacao' && $start_date && $end_date ) {
	$date_label = $format_date( $start_date ) . ' - ' . $format_date( $end_date );
}

// Author
$author        = get_userdata( $post->post_author );
$author_name   = $author ? $author->display_name : '';
$author_avatar = get_avatar_url( $post->post_author, array( 'size' => 48 ) );

// Permalink
$permalink = get_permalink( $post_id );
?>

<article class="classified-card" data-id="<?php echo esc_attr( $post_id ); ?>">
	<a href="<?php echo esc_url( $permalink ); ?>">
		<div class="card-image">
			<?php if ( has_post_thumbnail( $post_id ) ) : ?>
				<?php echo get_the_post_thumbnail( $post_id, 'medium', array( 'loading' => 'lazy' ) ); ?>
			<?php else : ?>
				<div class="card-image-placeholder">
					<svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1">
						<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
						<circle cx="8.5" cy="8.5" r="1.5"/>
						<polyline points="21 15 16 10 5 21"/>
					</svg>
				</div>
			<?php endif; ?>

			<div class="card-badges">
				<?php if ( $domain_name ) : ?>
					<span class="card-badge badge-<?php echo esc_attr( $domain_slug ); ?>">
						<?php echo esc_html( $domain_name ); ?>
					</span>
				<?php endif; ?>
				<?php if ( $intent_name ) : ?>
					<span class="card-badge badge-<?php echo esc_attr( $intent_slug ); ?>">
						<?php echo esc_html( $intent_name ); ?>
					</span>
				<?php endif; ?>
			</div>
		</div>

		<div class="card-content">
			<h3 class="card-title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>

			<?php if ( $price_formatted ) : ?>
				<div class="card-price"><?php echo esc_html( $price_formatted ); ?></div>
			<?php endif; ?>

			<div class="card-meta">
				<?php if ( $location ) : ?>
					<span class="card-meta-item">
						<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
							<circle cx="12" cy="10" r="3"/>
						</svg>
						<?php echo esc_html( $location ); ?>
					</span>
				<?php endif; ?>

				<?php if ( $date_label ) : ?>
					<span class="card-meta-item">
						<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
							<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
							<line x1="16" y1="2" x2="16" y2="6"/>
							<line x1="8" y1="2" x2="8" y2="6"/>
							<line x1="3" y1="10" x2="21" y2="10"/>
						</svg>
						<?php echo esc_html( $date_label ); ?>
					</span>
				<?php endif; ?>
			</div>
		</div>

		<div class="card-footer">
			<img src="<?php echo esc_url( $author_avatar ); ?>" alt="<?php echo esc_attr( $author_name ); ?>" />
			<span><?php echo esc_html( $author_name ); ?></span>
		</div>
	</a>
</article>
