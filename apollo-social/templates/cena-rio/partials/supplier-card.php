<?php
/**
 * Supplier Card Partial Template
 *
 * Renders a single supplier card for the grid.
 * Expects $supplier variable to be set (array from Supplier::to_array()).
 *
 * @package Apollo\Templates\CenaRio\Partials
 * @since   1.0.0
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure supplier data is available.
if ( ! isset( $supplier ) || ! is_array( $supplier ) ) {
	return;
}

// Extract data with defaults.
$supplier_id    = isset( $supplier['id'] ) ? absint( $supplier['id'] ) : 0;
$name           = isset( $supplier['name'] ) ? $supplier['name'] : '';
$description    = isset( $supplier['description'] ) ? $supplier['description'] : '';
$logo_url       = isset( $supplier['logo_url'] ) ? $supplier['logo_url'] : '';
$category       = isset( $supplier['category'] ) ? $supplier['category'] : '';
$category_label = isset( $supplier['category_label'] ) ? $supplier['category_label'] : $category;
$region         = isset( $supplier['region'] ) ? $supplier['region'] : '';
$region_label   = isset( $supplier['region_label'] ) ? $supplier['region_label'] : $region;
$is_verified    = isset( $supplier['is_verified'] ) && $supplier['is_verified'];
$is_featured    = isset( $supplier['is_featured'] ) && $supplier['is_featured'];
$rating_avg     = isset( $supplier['rating_avg'] ) ? floatval( $supplier['rating_avg'] ) : 5.0;
$reviews_count  = isset( $supplier['reviews_count'] ) ? absint( $supplier['reviews_count'] ) : 0;
$initials       = isset( $supplier['initials'] ) ? $supplier['initials'] : mb_strtoupper( mb_substr( $name, 0, 2 ) );
$tags           = isset( $supplier['tags'] ) && is_array( $supplier['tags'] ) ? $supplier['tags'] : array();
$supplier_type  = isset( $supplier['type'] ) ? $supplier['type'] : '';
$supplier_mode  = isset( $supplier['mode'] ) ? $supplier['mode'] : '';

// Truncate description.
$short_description = mb_strlen( $description ) > 100 ? mb_substr( $description, 0, 100 ) . '...' : $description;

// Stars.
$full_stars  = intval( floor( $rating_avg ) );
$has_half    = ( $rating_avg - $full_stars ) >= 0.5;
$empty_stars = 5 - $full_stars - ( $has_half ? 1 : 0 );
?>
<article
	class="supplier-card group"
	data-supplier-id="<?php echo esc_attr( $supplier_id ); ?>"
	data-category="<?php echo esc_attr( $category ); ?>"
	data-region="<?php echo esc_attr( $region ); ?>"
	role="button"
	tabindex="0"
	aria-label="<?php echo esc_attr( sprintf( 'Ver detalhes de %s', $name ) ); ?>"
>
	<!-- Header -->
	<div class="flex items-start justify-between mb-3">
		<div class="flex items-center gap-3">
			<!-- Logo / Avatar -->
			<div class="h-12 w-12 rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 overflow-hidden flex items-center justify-center shrink-0">
				<?php if ( $logo_url ) : ?>
					<img
						src="<?php echo esc_url( $logo_url ); ?>"
						alt="<?php echo esc_attr( $name ); ?>"
						class="w-full h-full object-contain"
						loading="lazy"
					>
				<?php else : ?>
					<span class="text-sm font-bold text-slate-500"><?php echo esc_html( $initials ); ?></span>
				<?php endif; ?>
			</div>

			<div class="min-w-0">
				<div class="flex items-center gap-2">
					<h3 class="text-sm font-bold text-slate-900 truncate"><?php echo esc_html( $name ); ?></h3>
					<?php if ( $is_verified ) : ?>
						<i class="ri-verified-badge-fill text-green-500 text-sm shrink-0" title="Verificado"></i>
					<?php endif; ?>
				</div>

				<div class="flex items-center gap-1.5 text-xs text-slate-500 mt-0.5">
					<span class="text-orange-600 font-semibold uppercase text-[10px] tracking-wide"><?php echo esc_html( $category_label ); ?></span>
					<?php if ( $region_label ) : ?>
						<span class="text-slate-300">•</span>
						<span><?php echo esc_html( $region_label ); ?></span>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- Rating -->
		<div class="flex flex-col items-end shrink-0">
			<div class="flex items-center gap-1">
				<div class="flex text-yellow-400 text-[11px]">
					<?php
					for ( $i = 0; $i < $full_stars; $i++ ) {
						echo '<i class="ri-star-fill"></i>';
					}
					if ( $has_half ) {
						echo '<i class="ri-star-half-fill"></i>';
					}
					for ( $i = 0; $i < $empty_stars; $i++ ) {
						echo '<i class="ri-star-line text-slate-300"></i>';
					}
					?>
				</div>
				<span class="text-xs font-bold text-slate-700"><?php echo esc_html( number_format( $rating_avg, 1 ) ); ?></span>
			</div>
			<?php if ( $reviews_count > 0 ) : ?>
				<span class="text-[10px] text-slate-400"><?php echo esc_html( $reviews_count ); ?> avaliações</span>
			<?php endif; ?>
		</div>
	</div>

	<!-- Description -->
	<?php if ( $short_description ) : ?>
		<p class="text-xs text-slate-500 line-clamp-2 mb-3 leading-relaxed"><?php echo esc_html( $short_description ); ?></p>
	<?php endif; ?>

	<!-- Tags -->
	<?php if ( ! empty( $tags ) ) : ?>
		<div class="flex flex-wrap gap-1.5">
			<?php foreach ( array_slice( $tags, 0, 3 ) as $tag_item ) : ?>
				<span class="px-2 py-1 rounded-full bg-slate-50 text-[10px] font-semibold text-slate-500 uppercase tracking-wide border border-slate-100">
					<?php echo esc_html( $tag_item ); ?>
				</span>
			<?php endforeach; ?>
			<?php if ( count( $tags ) > 3 ) : ?>
				<span class="px-2 py-1 rounded-full bg-slate-50 text-[10px] font-semibold text-slate-400 border border-slate-100">
					+<?php echo esc_html( count( $tags ) - 3 ); ?>
				</span>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<!-- Featured Badge -->
	<?php if ( $is_featured ) : ?>
		<div class="absolute top-2 right-2 px-2 py-0.5 rounded-md bg-gradient-to-r from-amber-400 to-orange-500 text-white text-[9px] font-bold uppercase tracking-wider shadow-sm">
			Destaque
		</div>
	<?php endif; ?>

	<!-- Hover Indicator -->
	<div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
		<i class="ri-arrow-right-up-line text-slate-400 text-lg"></i>
	</div>
</article>
