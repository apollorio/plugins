<?php
/**
 * Apollo Classifieds Grid Block - Server-Side Render
 *
 * Renders a grid of classified listings with filters and pagination.
 *
 * @package Apollo_Social
 * @subpackage Blocks
 * @since 2.0.0
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

declare(strict_types=1);

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Extract attributes with defaults.
$layout               = $attributes['layout'] ?? 'grid';
$columns              = (int) ( $attributes['columns'] ?? 3 );
$limit                = (int) ( $attributes['limit'] ?? 9 );
$category             = $attributes['category'] ?? array();
$location             = $attributes['location'] ?? '';
$order_by             = $attributes['orderBy'] ?? 'date';
$status               = $attributes['status'] ?? 'active';
$show_image           = $attributes['showImage'] ?? true;
$show_price           = $attributes['showPrice'] ?? true;
$show_category        = $attributes['showCategory'] ?? true;
$show_location        = $attributes['showLocation'] ?? true;
$show_date            = $attributes['showDate'] ?? true;
$show_author          = $attributes['showAuthor'] ?? true;
$show_condition       = $attributes['showCondition'] ?? true;
$show_views           = $attributes['showViews'] ?? false;
$show_favorite_button = $attributes['showFavoriteButton'] ?? true;
$show_contact_button  = $attributes['showContactButton'] ?? true;
$show_filters         = $attributes['showFilters'] ?? false;
$show_search          = $attributes['showSearch'] ?? false;
$show_pagination      = $attributes['showPagination'] ?? true;
$image_aspect_ratio   = $attributes['imageAspectRatio'] ?? '4/3';
$featured             = $attributes['featured'] ?? false;
$current_user_only    = $attributes['currentUserOnly'] ?? false;
$class_name           = $attributes['className'] ?? '';

// Generate unique ID.
$grid_id = 'apollo-classifieds-' . wp_unique_id();

// Build query args.
$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

$query_args = array(
	'post_type'      => 'apollo_classified',
	'posts_per_page' => $limit,
	'paged'          => $paged,
	'post_status'    => 'publish',
);

// Order handling.
switch ( $order_by ) {
	case 'price_low':
		$query_args['meta_key'] = '_apollo_classified_price';
		$query_args['orderby']  = 'meta_value_num';
		$query_args['order']    = 'ASC';
		break;
	case 'price_high':
		$query_args['meta_key'] = '_apollo_classified_price';
		$query_args['orderby']  = 'meta_value_num';
		$query_args['order']    = 'DESC';
		break;
	case 'views':
		$query_args['meta_key'] = '_apollo_classified_views';
		$query_args['orderby']  = 'meta_value_num';
		$query_args['order']    = 'DESC';
		break;
	case 'title':
		$query_args['orderby'] = 'title';
		$query_args['order']   = 'ASC';
		break;
	default:
		$query_args['orderby'] = 'date';
		$query_args['order']   = 'DESC';
}

// Category filter.
if ( ! empty( $category ) ) {
	$query_args['tax_query'] = array(
		array(
			'taxonomy' => 'classified_domain',
			'field'    => 'term_id',
			'terms'    => $category,
		),
	);
}

// Location filter.
if ( ! empty( $location ) ) {
	if ( ! isset( $query_args['meta_query'] ) ) {
		$query_args['meta_query'] = array();
	}
	$query_args['meta_query'][] = array(
		'key'     => '_apollo_classified_location',
		'value'   => $location,
		'compare' => 'LIKE',
	);
}

// Status filter.
if ( 'active' === $status ) {
	if ( ! isset( $query_args['meta_query'] ) ) {
		$query_args['meta_query'] = array();
	}
	$query_args['meta_query'][] = array(
		'relation' => 'OR',
		array(
			'key'     => '_apollo_classified_sold',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => '_apollo_classified_sold',
			'value'   => '0',
			'compare' => '=',
		),
	);
} elseif ( 'sold' === $status ) {
	if ( ! isset( $query_args['meta_query'] ) ) {
		$query_args['meta_query'] = array();
	}
	$query_args['meta_query'][] = array(
		'key'   => '_apollo_classified_sold',
		'value' => '1',
	);
}

// Featured filter.
if ( $featured ) {
	if ( ! isset( $query_args['meta_query'] ) ) {
		$query_args['meta_query'] = array();
	}
	$query_args['meta_query'][] = array(
		'key'   => '_apollo_classified_featured',
		'value' => '1',
	);
}

// Current user filter.
if ( $current_user_only && is_user_logged_in() ) {
	$query_args['author'] = get_current_user_id();
}

// URL-based filters (search, category, etc.).
$search_term = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
if ( ! empty( $search_term ) ) {
	$query_args['s'] = $search_term;
}

$filter_category = isset( $_GET['categoria'] ) ? absint( $_GET['categoria'] ) : 0;
if ( $filter_category > 0 ) {
	$query_args['tax_query'] = array(
		array(
			'taxonomy' => 'classified_domain',
			'field'    => 'term_id',
			'terms'    => array( $filter_category ),
		),
	);
}

$filter_order = isset( $_GET['ordenar'] ) ? sanitize_key( $_GET['ordenar'] ) : '';
if ( ! empty( $filter_order ) ) {
	switch ( $filter_order ) {
		case 'menor-preco':
			$query_args['meta_key'] = '_apollo_classified_price';
			$query_args['orderby']  = 'meta_value_num';
			$query_args['order']    = 'ASC';
			break;
		case 'maior-preco':
			$query_args['meta_key'] = '_apollo_classified_price';
			$query_args['orderby']  = 'meta_value_num';
			$query_args['order']    = 'DESC';
			break;
	}
}

// Run query.
$query = new WP_Query( $query_args );

// Build wrapper classes.
$wrapper_classes = array(
	'apollo-classifieds-grid-block',
	'apollo-classifieds-grid',
	"apollo-classifieds-grid--{$layout}",
	"apollo-classifieds-grid--cols-{$columns}",
);

if ( ! empty( $class_name ) ) {
	$wrapper_classes[] = $class_name;
}

// Get block wrapper attributes.
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $wrapper_classes ),
		'id'    => $grid_id,
	)
);

// Get categories for filter.
$all_categories = get_terms(
	array(
		'taxonomy'   => 'classified_domain',
		'hide_empty' => true,
	)
);
?>

<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $show_search || $show_filters ) : ?>
		<div class="apollo-classifieds-toolbar">
			<form class="apollo-classifieds-filters" method="get" action="">
				<?php if ( $show_search ) : ?>
					<div class="apollo-classifieds-search">
						<i class="ri-search-line"></i>
						<input
							type="text"
							name="s"
							placeholder="<?php esc_attr_e( 'Buscar classificados...', 'apollo-social' ); ?>"
							value="<?php echo esc_attr( $search_term ); ?>"
						>
					</div>
				<?php endif; ?>

				<?php if ( $show_filters && ! empty( $all_categories ) && ! is_wp_error( $all_categories ) ) : ?>
					<div class="apollo-classifieds-filter-group">
						<select name="categoria" class="apollo-select">
							<option value=""><?php esc_html_e( 'Todas Categorias', 'apollo-social' ); ?></option>
							<?php foreach ( $all_categories as $cat ) : ?>
								<option
									value="<?php echo esc_attr( $cat->term_id ); ?>"
									<?php selected( $filter_category, $cat->term_id ); ?>
								>
									<?php echo esc_html( $cat->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>

						<select name="ordenar" class="apollo-select">
							<option value=""><?php esc_html_e( 'Mais Recentes', 'apollo-social' ); ?></option>
							<option value="menor-preco" <?php selected( $filter_order, 'menor-preco' ); ?>>
								<?php esc_html_e( 'Menor Preço', 'apollo-social' ); ?>
							</option>
							<option value="maior-preco" <?php selected( $filter_order, 'maior-preco' ); ?>>
								<?php esc_html_e( 'Maior Preço', 'apollo-social' ); ?>
							</option>
						</select>

						<button type="submit" class="apollo-btn apollo-btn--primary">
							<i class="ri-filter-3-line"></i>
							<?php esc_html_e( 'Filtrar', 'apollo-social' ); ?>
						</button>
					</div>
				<?php endif; ?>
			</form>

			<?php if ( ! empty( $search_term ) || $filter_category > 0 ) : ?>
				<div class="apollo-classifieds-active-filters">
					<span class="apollo-active-filters-label"><?php esc_html_e( 'Filtros ativos:', 'apollo-social' ); ?></span>
					<?php if ( ! empty( $search_term ) ) : ?>
						<a href="<?php echo esc_url( remove_query_arg( 's' ) ); ?>" class="apollo-filter-tag">
							<?php echo esc_html( $search_term ); ?>
							<i class="ri-close-line"></i>
						</a>
					<?php endif; ?>
					<?php
					if ( $filter_category > 0 ) :
						$cat_term = get_term( $filter_category );
						if ( $cat_term && ! is_wp_error( $cat_term ) ) :
							?>
						<a href="<?php echo esc_url( remove_query_arg( 'categoria' ) ); ?>" class="apollo-filter-tag">
							<?php echo esc_html( $cat_term->name ); ?>
							<i class="ri-close-line"></i>
						</a>
							<?php
					endif;
endif;
					?>
					<a href="<?php echo esc_url( strtok( $_SERVER['REQUEST_URI'] ?? '', '?' ) ); ?>" class="apollo-clear-filters">
						<?php esc_html_e( 'Limpar tudo', 'apollo-social' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( $query->have_posts() ) : ?>
		<div class="apollo-classifieds-list">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				$classified_id = get_the_ID();

				// Get classified meta.
				$price       = get_post_meta( $classified_id, '_apollo_classified_price', true );
				$condition   = get_post_meta( $classified_id, '_apollo_classified_condition', true );
				$location_m  = get_post_meta( $classified_id, '_apollo_classified_location', true );
				$is_sold     = get_post_meta( $classified_id, '_apollo_classified_sold', true );
				$is_featured = get_post_meta( $classified_id, '_apollo_classified_featured', true );
				$views       = (int) get_post_meta( $classified_id, '_apollo_classified_views', true );

				// Get author.
				$author_id     = get_the_author_meta( 'ID' );
				$author_name   = get_the_author();
				$author_avatar = get_avatar_url( $author_id, array( 'size' => 48 ) );

				// Get categories.
				$categories    = get_the_terms( $classified_id, 'classified_domain' );
				$category_name = '';
				if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
					$category_name = $categories[0]->name;
				}

				// Condition labels.
				$condition_labels = array(
					'new'       => __( 'Novo', 'apollo-social' ),
					'like_new'  => __( 'Seminovo', 'apollo-social' ),
					'good'      => __( 'Bom', 'apollo-social' ),
					'fair'      => __( 'Regular', 'apollo-social' ),
					'for_parts' => __( 'Para peças', 'apollo-social' ),
				);
				$condition_label  = $condition_labels[ $condition ] ?? $condition;

				// Card classes.
				$card_classes = array(
					'apollo-classified-card',
					"apollo-classified-card--{$layout}",
				);
				if ( $is_sold ) {
					$card_classes[] = 'apollo-classified-card--sold';
				}
				if ( $is_featured ) {
					$card_classes[] = 'apollo-classified-card--featured';
				}

				// Favorite check.
				$is_favorited = false;
				if ( is_user_logged_in() && function_exists( 'apollo_is_favorited' ) ) {
					$is_favorited = apollo_is_favorited( get_current_user_id(), $classified_id );
				}
				?>
				<article class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>" data-id="<?php echo esc_attr( $classified_id ); ?>">
					<?php if ( $show_image ) : ?>
						<div class="apollo-classified-card__image" style="aspect-ratio: <?php echo esc_attr( $image_aspect_ratio ); ?>;">
							<a href="<?php the_permalink(); ?>">
								<?php if ( has_post_thumbnail() ) : ?>
									<?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); ?>
								<?php else : ?>
									<div class="apollo-classified-card__no-image">
										<i class="ri-image-line"></i>
									</div>
								<?php endif; ?>
							</a>

							<?php if ( $is_sold ) : ?>
								<span class="apollo-classified-card__badge apollo-classified-card__badge--sold">
									<?php esc_html_e( 'Vendido', 'apollo-social' ); ?>
								</span>
							<?php elseif ( $is_featured ) : ?>
								<span class="apollo-classified-card__badge apollo-classified-card__badge--featured">
									<i class="ri-star-fill"></i>
									<?php esc_html_e( 'Destaque', 'apollo-social' ); ?>
								</span>
							<?php endif; ?>

							<?php if ( $show_favorite_button && is_user_logged_in() ) : ?>
								<button
									class="apollo-classified-card__favorite <?php echo $is_favorited ? 'is-active' : ''; ?>"
									data-id="<?php echo esc_attr( $classified_id ); ?>"
									data-nonce="<?php echo esc_attr( wp_create_nonce( 'apollo_favorite_' . $classified_id ) ); ?>"
									aria-label="<?php esc_attr_e( 'Favoritar', 'apollo-social' ); ?>"
								>
									<i class="<?php echo $is_favorited ? 'ri-heart-fill' : 'ri-heart-line'; ?>"></i>
								</button>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<div class="apollo-classified-card__content">
						<?php if ( $show_category && ! empty( $category_name ) ) : ?>
							<span class="apollo-classified-card__category">
								<?php echo esc_html( $category_name ); ?>
							</span>
						<?php endif; ?>

						<h3 class="apollo-classified-card__title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h3>

						<?php if ( $show_price && ! empty( $price ) ) : ?>
							<p class="apollo-classified-card__price">
								R$ <?php echo esc_html( number_format( (float) $price, 2, ',', '.' ) ); ?>
							</p>
						<?php endif; ?>

						<div class="apollo-classified-card__meta">
							<?php if ( $show_location && ! empty( $location_m ) ) : ?>
								<span class="apollo-classified-card__location">
									<i class="ri-map-pin-line"></i>
									<?php echo esc_html( $location_m ); ?>
								</span>
							<?php endif; ?>

							<?php if ( $show_condition && ! empty( $condition_label ) ) : ?>
								<span class="apollo-classified-card__condition">
									<?php echo esc_html( $condition_label ); ?>
								</span>
							<?php endif; ?>

							<?php if ( $show_date ) : ?>
								<span class="apollo-classified-card__date">
									<i class="ri-time-line"></i>
									<?php echo esc_html( human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) ); ?>
								</span>
							<?php endif; ?>
						</div>

						<div class="apollo-classified-card__footer">
							<?php if ( $show_author ) : ?>
								<div class="apollo-classified-card__author">
									<img src="<?php echo esc_url( $author_avatar ); ?>" alt="<?php echo esc_attr( $author_name ); ?>" loading="lazy">
									<span><?php echo esc_html( $author_name ); ?></span>
								</div>
							<?php endif; ?>

							<?php if ( $show_views ) : ?>
								<span class="apollo-classified-card__views">
									<i class="ri-eye-line"></i>
									<?php echo esc_html( number_format_i18n( $views ) ); ?>
								</span>
							<?php endif; ?>
						</div>

						<?php if ( $show_contact_button && ! $is_sold ) : ?>
							<a href="<?php the_permalink(); ?>#contato" class="apollo-btn apollo-btn--primary apollo-classified-card__contact">
								<i class="ri-message-3-line"></i>
								<?php esc_html_e( 'Entrar em Contato', 'apollo-social' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</article>
			<?php endwhile; ?>
		</div>

		<?php if ( $show_pagination && $query->max_num_pages > 1 ) : ?>
			<nav class="apollo-classifieds-pagination">
				<?php
				echo paginate_links(
					array(
						'total'     => $query->max_num_pages,
						'current'   => $paged,
						'prev_text' => '<i class="ri-arrow-left-s-line"></i>',
						'next_text' => '<i class="ri-arrow-right-s-line"></i>',
						'type'      => 'list',
					)
				);
				?>
			</nav>
		<?php endif; ?>
	<?php else : ?>
		<div class="apollo-classifieds-empty">
			<i class="ri-inbox-line"></i>
			<h3><?php esc_html_e( 'Nenhum classificado encontrado', 'apollo-social' ); ?></h3>
			<p><?php esc_html_e( 'Não há classificados que correspondam aos seus critérios de busca.', 'apollo-social' ); ?></p>
			<?php if ( ! empty( $search_term ) || $filter_category > 0 ) : ?>
				<a href="<?php echo esc_url( strtok( $_SERVER['REQUEST_URI'] ?? '', '?' ) ); ?>" class="apollo-btn apollo-btn--outline">
					<?php esc_html_e( 'Limpar Filtros', 'apollo-social' ); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php wp_reset_postdata(); ?>
</div>

<style>
#<?php echo esc_attr( $grid_id ); ?> {
	--grid-columns: <?php echo esc_attr( $columns ); ?>;
	--image-aspect-ratio: <?php echo esc_attr( $image_aspect_ratio ); ?>;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classifieds-toolbar {
	margin-bottom: 1.5rem;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classifieds-filters {
	display: flex;
	flex-wrap: wrap;
	gap: 1rem;
	align-items: center;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classifieds-search {
	flex: 1;
	min-width: 200px;
	position: relative;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classifieds-search i {
	position: absolute;
	left: 1rem;
	top: 50%;
	transform: translateY(-50%);
	color: #94a3b8;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classifieds-search input {
	width: 100%;
	padding: 0.625rem 1rem 0.625rem 2.5rem;
	border: 1px solid #e2e8f0;
	border-radius: 8px;
	font-size: 0.875rem;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classifieds-search input:focus {
	outline: none;
	border-color: #6366f1;
	box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classifieds-filter-group {
	display: flex;
	gap: 0.5rem;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-select {
	padding: 0.625rem 2rem 0.625rem 0.75rem;
	border: 1px solid #e2e8f0;
	border-radius: 6px;
	font-size: 0.875rem;
	background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='%2364748b'%3E%3Cpath d='M12 16L6 10H18L12 16Z'/%3E%3C/svg%3E") no-repeat right 0.75rem center;
	appearance: none;
	cursor: pointer;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classifieds-active-filters {
	display: flex;
	flex-wrap: wrap;
	gap: 0.5rem;
	align-items: center;
	margin-top: 0.75rem;
	padding-top: 0.75rem;
	border-top: 1px solid #e2e8f0;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-active-filters-label {
	font-size: 0.75rem;
	color: #64748b;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-filter-tag {
	display: inline-flex;
	align-items: center;
	gap: 0.25rem;
	padding: 0.25rem 0.5rem;
	background: #e2e8f0;
	border-radius: 4px;
	font-size: 0.75rem;
	color: #475569;
	text-decoration: none;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-filter-tag:hover {
	background: #cbd5e1;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-clear-filters {
	font-size: 0.75rem;
	color: #ef4444;
	text-decoration: none;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-clear-filters:hover {
	text-decoration: underline;
}

/* Grid Layout */
#<?php echo esc_attr( $grid_id ); ?>.apollo-classifieds-grid--grid .apollo-classifieds-list,
#<?php echo esc_attr( $grid_id ); ?>.apollo-classifieds-grid--masonry .apollo-classifieds-list {
	display: grid;
	grid-template-columns: repeat(var(--grid-columns), 1fr);
	gap: 1.5rem;
}

/* List Layout */
#<?php echo esc_attr( $grid_id ); ?>.apollo-classifieds-grid--list .apollo-classifieds-list {
	display: flex;
	flex-direction: column;
	gap: 1rem;
}

#<?php echo esc_attr( $grid_id ); ?>.apollo-classifieds-grid--list .apollo-classified-card {
	display: flex;
	flex-direction: row;
}

#<?php echo esc_attr( $grid_id ); ?>.apollo-classifieds-grid--list .apollo-classified-card__image {
	width: 240px;
	flex-shrink: 0;
}

/* Compact Layout */
#<?php echo esc_attr( $grid_id ); ?>.apollo-classifieds-grid--compact .apollo-classifieds-list {
	display: flex;
	flex-direction: column;
	gap: 0.5rem;
}

#<?php echo esc_attr( $grid_id ); ?>.apollo-classifieds-grid--compact .apollo-classified-card {
	display: flex;
	align-items: center;
	gap: 0.75rem;
	padding: 0.75rem;
}

#<?php echo esc_attr( $grid_id ); ?>.apollo-classifieds-grid--compact .apollo-classified-card__image {
	width: 60px;
	height: 60px;
	aspect-ratio: 1/1;
	border-radius: 6px;
	overflow: hidden;
	flex-shrink: 0;
}

/* Card Styles */
#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card {
	background: #fff;
	border-radius: 12px;
	overflow: hidden;
	box-shadow: 0 1px 3px rgba(0,0,0,0.08);
	transition: transform 0.2s, box-shadow 0.2s;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card--sold {
	opacity: 0.7;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card--featured {
	border: 2px solid #f59e0b;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__image {
	position: relative;
	aspect-ratio: var(--image-aspect-ratio);
	overflow: hidden;
	background: #f1f5f9;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__image img {
	width: 100%;
	height: 100%;
	object-fit: cover;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__no-image {
	width: 100%;
	height: 100%;
	display: flex;
	align-items: center;
	justify-content: center;
	background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
	color: #94a3b8;
	font-size: 2rem;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__badge {
	position: absolute;
	top: 0.5rem;
	left: 0.5rem;
	padding: 0.25rem 0.625rem;
	border-radius: 4px;
	font-size: 0.7rem;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.02em;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__badge--sold {
	background: #ef4444;
	color: #fff;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__badge--featured {
	background: #f59e0b;
	color: #fff;
	display: flex;
	align-items: center;
	gap: 0.25rem;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__favorite {
	position: absolute;
	top: 0.5rem;
	right: 0.5rem;
	width: 36px;
	height: 36px;
	border-radius: 50%;
	background: rgba(255,255,255,0.9);
	border: none;
	cursor: pointer;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 1.125rem;
	color: #94a3b8;
	transition: all 0.2s;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__favorite:hover,
#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__favorite.is-active {
	color: #ef4444;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__content {
	padding: 1rem;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__category {
	display: inline-block;
	font-size: 0.7rem;
	font-weight: 600;
	color: #6366f1;
	text-transform: uppercase;
	letter-spacing: 0.05em;
	margin-bottom: 0.25rem;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__title {
	margin: 0 0 0.5rem;
	font-size: 0.9375rem;
	font-weight: 600;
	line-height: 1.3;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__title a {
	color: inherit;
	text-decoration: none;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__title a:hover {
	color: #6366f1;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__price {
	font-size: 1.25rem;
	font-weight: 700;
	color: #059669;
	margin: 0 0 0.75rem;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__meta {
	display: flex;
	flex-wrap: wrap;
	gap: 0.5rem;
	margin-bottom: 0.75rem;
	font-size: 0.75rem;
	color: #64748b;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__meta span {
	display: inline-flex;
	align-items: center;
	gap: 0.25rem;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__condition {
	background: #e2e8f0;
	padding: 0.125rem 0.375rem;
	border-radius: 4px;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__footer {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding-top: 0.75rem;
	border-top: 1px solid #f1f5f9;
	margin-bottom: 0.75rem;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__author {
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__author img {
	width: 28px;
	height: 28px;
	border-radius: 50%;
	object-fit: cover;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__author span {
	font-size: 0.8125rem;
	color: #475569;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__views {
	font-size: 0.75rem;
	color: #94a3b8;
	display: flex;
	align-items: center;
	gap: 0.25rem;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__contact {
	width: 100%;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 0.375rem;
}

/* Buttons */
#<?php echo esc_attr( $grid_id ); ?> .apollo-btn {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 0.375rem;
	padding: 0.625rem 1rem;
	border-radius: 8px;
	font-size: 0.875rem;
	font-weight: 500;
	text-decoration: none;
	cursor: pointer;
	transition: all 0.2s;
	border: none;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-btn--primary {
	background: #6366f1;
	color: #fff;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-btn--primary:hover {
	background: #4f46e5;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-btn--outline {
	background: transparent;
	border: 1px solid #e2e8f0;
	color: #475569;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-btn--outline:hover {
	border-color: #6366f1;
	color: #6366f1;
}

/* Pagination */
#<?php echo esc_attr( $grid_id ); ?> .apollo-classifieds-pagination {
	margin-top: 2rem;
	display: flex;
	justify-content: center;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classifieds-pagination ul {
	display: flex;
	gap: 0.25rem;
	list-style: none;
	padding: 0;
	margin: 0;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classifieds-pagination a,
#<?php echo esc_attr( $grid_id ); ?> .apollo-classifieds-pagination span {
	display: flex;
	align-items: center;
	justify-content: center;
	min-width: 40px;
	height: 40px;
	padding: 0 0.75rem;
	border: 1px solid #e2e8f0;
	border-radius: 8px;
	font-size: 0.875rem;
	text-decoration: none;
	color: #475569;
	transition: all 0.2s;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classifieds-pagination a:hover {
	border-color: #6366f1;
	color: #6366f1;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classifieds-pagination .current {
	background: #6366f1;
	border-color: #6366f1;
	color: #fff;
}

/* Empty State */
#<?php echo esc_attr( $grid_id ); ?> .apollo-classifieds-empty {
	text-align: center;
	padding: 4rem 2rem;
	background: #f8fafc;
	border-radius: 12px;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classifieds-empty i {
	font-size: 3rem;
	color: #cbd5e1;
	margin-bottom: 1rem;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classifieds-empty h3 {
	margin: 0 0 0.5rem;
	font-size: 1.25rem;
	color: #334155;
}

#<?php echo esc_attr( $grid_id ); ?> .apollo-classifieds-empty p {
	color: #64748b;
	margin: 0 0 1.5rem;
}

/* Responsive */
@media (max-width: 1024px) {
	#<?php echo esc_attr( $grid_id ); ?>.apollo-classifieds-grid--grid .apollo-classifieds-list,
	#<?php echo esc_attr( $grid_id ); ?>.apollo-classifieds-grid--masonry .apollo-classifieds-list {
		grid-template-columns: repeat(2, 1fr);
	}
}

@media (max-width: 640px) {
	#<?php echo esc_attr( $grid_id ); ?>.apollo-classifieds-grid--grid .apollo-classifieds-list,
	#<?php echo esc_attr( $grid_id ); ?>.apollo-classifieds-grid--masonry .apollo-classifieds-list {
		grid-template-columns: 1fr;
	}

	#<?php echo esc_attr( $grid_id ); ?>.apollo-classifieds-grid--list .apollo-classified-card {
		flex-direction: column;
	}

	#<?php echo esc_attr( $grid_id ); ?>.apollo-classifieds-grid--list .apollo-classified-card__image {
		width: 100%;
	}

	#<?php echo esc_attr( $grid_id ); ?> .apollo-classifieds-filter-group {
		flex-wrap: wrap;
		width: 100%;
	}

	#<?php echo esc_attr( $grid_id ); ?> .apollo-select {
		flex: 1;
		min-width: 120px;
	}
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
	#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card {
		background: #1e293b;
	}

	#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__title a {
		color: #f1f5f9;
	}

	#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__footer {
		border-top-color: #334155;
	}

	#<?php echo esc_attr( $grid_id ); ?> .apollo-classified-card__author span {
		color: #94a3b8;
	}

	#<?php echo esc_attr( $grid_id ); ?> .apollo-classifieds-search input,
	#<?php echo esc_attr( $grid_id ); ?> .apollo-select {
		background: #1e293b;
		border-color: #475569;
		color: #f1f5f9;
	}

	#<?php echo esc_attr( $grid_id ); ?> .apollo-classifieds-empty {
		background: #1e293b;
	}

	#<?php echo esc_attr( $grid_id ); ?> .apollo-classifieds-empty h3 {
		color: #f1f5f9;
	}
}
</style>

<script>
(function() {
	const grid = document.getElementById('<?php echo esc_js( $grid_id ); ?>');
	if (!grid) return;

	// Handle favorite buttons.
	grid.querySelectorAll('.apollo-classified-card__favorite').forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			const id = this.dataset.id;
			const nonce = this.dataset.nonce;
			const icon = this.querySelector('i');
			const isActive = this.classList.contains('is-active');

			fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({
					action: isActive ? 'apollo_unfavorite_classified' : 'apollo_favorite_classified',
					classified_id: id,
					nonce: nonce,
				}),
			})
			.then(res => res.json())
			.then(data => {
				if (data.success) {
					this.classList.toggle('is-active');
					icon.className = this.classList.contains('is-active') ? 'ri-heart-fill' : 'ri-heart-line';
				}
			});
		});
	});

	// Auto-submit filters on change.
	grid.querySelectorAll('.apollo-select').forEach(select => {
		select.addEventListener('change', function() {
			this.closest('form').submit();
		});
	});
})();
</script>
