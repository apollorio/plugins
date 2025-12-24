<?php
/**
 * Classifieds Archive Template - /anuncios/
 * STRICT MODE: 100% UNI.CSS conformance
 *
 * @package Apollo_Social
 * @version 2.0.0
 * @uses UNI.CSS v5.2.0 - Classifieds components
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Query classifieds.
$current_page = get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : 1;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public archive filters, no data modification.
$category_filter = isset( $_GET['cat'] ) ? sanitize_text_field( wp_unslash( $_GET['cat'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public archive filters, no data modification.
$search_query = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public archive filters, no data modification.
$sort_by = isset( $_GET['sort'] ) ? sanitize_text_field( wp_unslash( $_GET['sort'] ) ) : 'recent';

$args = array(
	'post_type'      => 'apollo_classified',
	'post_status'    => 'publish',
	'posts_per_page' => 12,
	'paged'          => $current_page,
	'orderby'        => 'date',
	'order'          => 'DESC',
);

// Apply filters.
$meta_query = array();

if ( $category_filter ) {
	$meta_query[] = array(
		'key'     => '_classified_category',
		'value'   => $category_filter,
		'compare' => '=',
	);
}

if ( ! empty( $meta_query ) ) {
    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for category filtering.
	$args['meta_query'] = $meta_query;
}

if ( $search_query ) {
	$args['s'] = $search_query;
}

// Sort options.
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for price sorting.
if ( $sort_by === 'price-low' ) {
	$args['meta_key'] = '_classified_price';
	$args['orderby']  = 'meta_value_num';
	$args['order']    = 'ASC';
} elseif ( $sort_by === 'price-high' ) {
	$args['meta_key'] = '_classified_price';
	$args['orderby']  = 'meta_value_num';
	$args['order']    = 'DESC';
}
// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key

$classifieds = new WP_Query( $args );

// Categories configuration.
$categories = array(
	'tickets'   => array(
		'label' => 'Ingressos',
		'icon'  => 'ri-ticket-2-line',
		'badge' => '',
	),
	'bedroom'   => array(
		'label' => 'Quartos',
		'icon'  => 'ri-home-heart-line',
		'badge' => 'ap-badge-bedroom',
	),
	'equipment' => array(
		'label' => 'Equipamentos',
		'icon'  => 'ri-sound-module-line',
		'badge' => 'ap-badge-equipment',
	),
	'services'  => array(
		'label' => 'Serviços',
		'icon'  => 'ri-service-line',
		'badge' => 'ap-badge-service',
	),
);

// STRICT MODE: base.js handles all core assets - just ensure it's loaded
if ( function_exists( 'apollo_ensure_base_assets' ) ) {
	apollo_ensure_base_assets();
}

get_header();
?>

<body class="ap-classifieds-body">
	<div class="min-h-screen flex">

	<!-- ====================================================================
		[SIDEBAR] Apollo Social Navigation
		==================================================================== -->
	<?php get_template_part( 'partials/social-sidebar' ); ?>

	<!-- ====================================================================
		[MAIN CONTENT] Classifieds Archive
		==================================================================== -->
	<div class="flex-1 flex flex-col min-h-screen">
		<div class="ap-classifieds">

		<!-- Currency Float Widget -->
		<div class="ap-currency-float" id="currencyWidget">
			<div class="ap-currency-float-inner">
			<div class="ap-currency-rate">
				<span class="ap-currency-flag">🇺🇸</span>
				<span class="ap-currency-value" id="usdValue">1.00</span>
				<span class="ap-currency-code">USD</span>
				<i class="ri-arrow-right-line"></i>
				<span class="ap-currency-flag">🇧🇷</span>
				<span class="ap-currency-value ap-currency-value-highlight" id="brlValue">5.80</span>
				<span class="ap-currency-code">BRL</span>
			</div>
			<button class="ap-currency-toggle" id="currencyToggle" data-ap-tooltip="Abrir conversor">
				<i class="ri-exchange-dollar-line"></i>
			</button>
			</div>

			<div class="ap-currency-panel" id="currencyPanel">
			<div class="ap-currency-panel-header">
				<h4><i class="ri-exchange-funds-line"></i> Conversor USD ↔ BRL</h4>
				<button class="ap-currency-close" id="currencyClose"><i class="ri-close-line"></i></button>
			</div>
			<div class="ap-currency-panel-body">
				<div class="ap-currency-input-group">
				<label>USD (Dólar)</label>
				<input type="number" id="inputUSD" value="1" min="0" step="0.01">
				</div>
				<button class="ap-currency-swap" id="currencySwap" data-ap-tooltip="Inverter">
				<i class="ri-arrow-up-down-line"></i>
				</button>
				<div class="ap-currency-input-group">
				<label>BRL (Real)</label>
				<input type="number" id="inputBRL" value="5.80" min="0" step="0.01">
				</div>
			</div>
			<div class="ap-currency-panel-footer">
				<span class="ap-currency-update" id="currencyUpdate"><i class="ri-time-line"></i> Atualizado agora</span>
				<span class="ap-currency-source">Via ExchangeRate API</span>
			</div>
			</div>
		</div>

		<!-- ====================================================================
			[HEADER] Classifieds Header with Brand & Safety Warning
			==================================================================== -->
		<header class="ap-classifieds-header">
			<div class="ap-classifieds-brand">
				<div class="ap-brand-icon">
				<i class="ri-price-tag-3-fill"></i>
				</div>
				<div class="ap-brand-text">
				<h1 class="ap-brand-title">Classificado<span class="ap-brand-accent">::</span>rio</h1>
				<p class="ap-brand-subtitle">Marketplace da Comunidade Apollo</p>
				</div>
			</div>

			<!-- Safety Warning Banner -->
			<div class="ap-safety-banner">
				<div class="ap-safety-icon">
				<i class="ri-shield-check-line"></i>
				</div>
				<div class="ap-safety-text">
				Apollo::rio facilita conexões e pontes, mas <mark>não vendemos</mark> e
				<mark>nem nos responsabilizamos</mark> por negociações e contato
				<mark>de nenhuma transação iniciada aqui</mark>!
				</div>
				<a href="https://dicas.apollo.rio.br" target="_blank" class="ap-safety-link"
				data-ap-tooltip="Dicas de segurança">
				<i class="ri-external-link-line"></i> Dicas de Segurança
				</a>
			</div>
		</header>

		<!-- ====================================================================
			[FILTERS] Search & Category Filters
			==================================================================== -->
		<div class="ap-classifieds-filters">
			<!-- Search Bar -->
			<div class="ap-classifieds-search">
				<form method="get" class="ap-search-form ap-search-lg">
				<i class="ri-search-line"></i>
				<input type="text" name="q" id="advertSearch" class="ap-search-input"
						value="<?php echo esc_attr( $search_query ); ?>"
						placeholder="Buscar ingressos, quartos, equipamentos..."
						data-ap-tooltip="Digite para buscar anúncios">
				<?php if ( $category_filter ) : ?>
				<input type="hidden" name="cat" value="<?php echo esc_attr( $category_filter ); ?>">
				<?php endif; ?>
				</form>
			</div>

			<!-- Category Tabs -->
			<div class="ap-filter-tabs ap-filter-tabs-pills">
				<a href="<?php echo esc_url( remove_query_arg( array( 'cat', 'sort' ) ) ); ?>" class="ap-tab-pill <?php echo ! $category_filter ? 'active' : ''; ?>" data-filter="all" data-ap-tooltip="Mostrar todos os anúncios">
				<i class="ri-apps-line"></i> Todos
				</a>

				<?php foreach ( $categories as $category_slug => $category_data ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'cat', $category_slug ) ); ?>" class="ap-tab-pill <?php echo $category_filter === $category_slug ? 'active' : ''; ?>" data-filter="<?php echo esc_attr( $category_slug ); ?>" data-ap-tooltip="Filtrar por <?php echo esc_attr( $category_data['label'] ); ?>">
					<i class="<?php echo esc_attr( $category_data['icon'] ); ?>"></i>
					<?php echo esc_html( $category_data['label'] ); ?>
				</a>
				<?php endforeach; ?>
			</div>

			<!-- Sort & View Options -->
			<div class="ap-classifieds-options">
				<select class="ap-select-mini" name="sort" onchange="this.form.submit()">
					<option value="recent" <?php selected( $sort_by, 'recent' ); ?>>Mais Recentes</option>
					<option value="price-low" <?php selected( $sort_by, 'price-low' ); ?>>Menor Preço</option>
					<option value="price-high" <?php selected( $sort_by, 'price-high' ); ?>>Maior Preço</option>
					<option value="popular" <?php selected( $sort_by, 'popular' ); ?>>Mais Vistos</option>
				</select>
				<div class="ap-view-toggle">
					<button class="ap-btn-icon-sm active" data-view="grid" data-ap-tooltip="Visualização em grade">
						<i class="ri-grid-fill"></i>
					</button>
					<button class="ap-btn-icon-sm" data-view="list" data-ap-tooltip="Visualização em lista">
						<i class="ri-list-check"></i>
					</button>
				</div>
			</div>
		</div>

		<!-- ====================================================================
			[GRID] Adverts Grid
			==================================================================== -->
		<main class="ap-adverts-grid" id="advertsGrid">
	<?php if ( $classifieds->have_posts() ) : ?>
		<?php
		while ( $classifieds->have_posts() ) :
			$classifieds->the_post();
			$classified_id   = get_the_ID();
			$author_id       = get_post_field( 'post_author', $classified_id );
			$author          = get_userdata( $author_id );
			$author_name     = $author ? $author->display_name : 'Anunciante';
			$author_initials = strtoupper( substr( $author_name, 0, 2 ) );

			$price        = get_post_meta( $classified_id, '_classified_price', true );
			$category_raw = get_post_meta( $classified_id, '_classified_category', true );
			$category     = ! empty( $category_raw ) ? $category_raw : 'other';
			$location     = get_post_meta( $classified_id, '_classified_location', true );
			$event_title  = get_post_meta( $classified_id, '_classified_event_title', true );
			$event_date   = get_post_meta( $classified_id, '_classified_event_date', true );
			$event_venue  = get_post_meta( $classified_id, '_classified_event_venue', true );
			$quantity_raw = get_post_meta( $classified_id, '_classified_quantity', true );
			$quantity     = ! empty( $quantity_raw ) ? $quantity_raw : '1';

			$price_display = $price ? number_format( (float) $price, 0, ',', '.' ) : '—';
			$thumb         = get_the_post_thumbnail_url( $classified_id, 'medium' );

			// Category config.
			$cat_config = $categories[ $category ] ?? array(
				'label' => 'Outro',
				'icon'  => 'ri-price-tag-line',
				'badge' => '',
			);

			// Price unit based on category.
			$price_units = array(
				'tickets'   => '/unid',
				'bedroom'   => '/mês',
				'equipment' => '/dia',
				'services'  => '/evento',
			);
			$price_unit  = $price_units[ $category ] ?? '';

			// Quantity labels.
			$quantity_icons = array(
				'tickets'   => 'ri-ticket-line',
				'bedroom'   => 'ri-door-open-line',
				'equipment' => 'ri-box-3-line',
				'services'  => 'ri-briefcase-line',
			);
			$quantity_icon  = $quantity_icons[ $category ] ?? 'ri-price-tag-line';

			// Avatar gradient colors by category.
			$avatar_colors   = array(
				'tickets'   => '#6366f1, #8b5cf6',
				'bedroom'   => '#ec4899, #f472b6',
				'equipment' => '#22c55e, #4ade80',
				'services'  => '#3b82f6, #60a5fa',
			);
			$avatar_gradient = $avatar_colors[ $category ] ?? '#f97316, #fb923c';
			?>

		<a href="<?php the_permalink(); ?>" class="ap-advert-card" data-category="<?php echo esc_attr( $category ); ?>" data-price="<?php echo esc_attr( $price ); ?>">
		<div class="ap-advert-body">
			<div class="ap-advert-top">
			<div class="ap-advert-author">
				<div class="ap-avatar ap-avatar-sm" style="background: linear-gradient(135deg, <?php echo esc_attr( $avatar_gradient ); ?>);">
				<span><?php echo esc_html( $author_initials ); ?></span>
				</div>
				<div class="ap-advert-author-info">
				<span class="ap-advert-label">Anúncio de</span>
				<span class="ap-advert-author-name"><?php echo esc_html( $author_name ); ?></span>
				</div>
			</div>
			<div class="ap-advert-event">
				<div class="ap-advert-event-info">
				<?php if ( $category === 'tickets' && $event_title ) : ?>
				<span class="ap-advert-event-title"><?php echo esc_html( $event_title ); ?></span>
				<span class="ap-advert-event-meta"><?php echo esc_html( $event_date . ' @ ' . $event_venue ); ?></span>
				<?php else : ?>
				<span class="ap-advert-event-title"><?php the_title(); ?></span>
				<span class="ap-advert-event-meta"><?php echo esc_html( ! empty( $location ) ? $location : 'Rio de Janeiro' ); ?></span>
				<?php endif; ?>
				</div>
			</div>
			</div>

			<div class="ap-advert-image">
			<?php if ( $thumb ) : ?>
			<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>">
			<?php else : ?>
			<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var((--bg-main)-muted);">
				<i class="<?php echo esc_attr( $cat_config['icon'] ); ?>" style="font-size:48px;color:var(--ap-text-muted);"></i>
			</div>
			<?php endif; ?>
			<span class="ap-advert-category-badge <?php echo esc_attr( $cat_config['badge'] ); ?>">
				<i class="<?php echo esc_attr( $cat_config['icon'] ); ?>"></i> <?php echo esc_html( $cat_config['label'] ); ?>
			</span>
			</div>
		</div>

		<div class="ap-advert-info">
			<div class="ap-advert-quantity">
			<i class="<?php echo esc_attr( $quantity_icon ); ?>"></i>
			<span><b><?php echo esc_html( $quantity ); ?>x</b> <?php echo esc_html( $cat_config['label'] ); ?></span>
			</div>
			<div class="ap-advert-price">
			<span class="ap-price-currency">R$</span>
			<span class="ap-price-value"><?php echo esc_html( $price_display ); ?></span>
			<span class="ap-price-unit"><?php echo esc_html( $price_unit ); ?></span>
			</div>
		</div>

		<div class="ap-advert-footer">
			<button type="button" class="ap-advert-btn ap-advert-btn-secondary" data-ap-tooltip="Ver perfil do vendedor" onclick="event.preventDefault(); window.location='<?php echo esc_url( home_url( '/id/' . ( $author ? $author->user_login : '' ) ) ); ?>'">
			<i class="ri-user-search-line"></i> Investigar
			</button>
			<button type="button" class="ap-advert-btn ap-advert-btn-primary" data-ap-tooltip="Iniciar conversa" onclick="event.preventDefault(); apolloChat.open(<?php echo esc_attr( $author_id ); ?>);">
			<i class="ri-chat-3-line"></i> Chat
			</button>
		</div>
		</a>

		<?php endwhile; ?>
	<?php else : ?>

		<!-- Empty State -->
		<div style="grid-column: 1 / -1; text-align: center; padding: 48px 24px;">
		<i class="ri-megaphone-line" style="font-size: 64px; color: var(--ap-text-muted);"></i>
		<h2 class="ap-heading-2" style="margin-top: 16px;">Nenhum anúncio encontrado</h2>
		<p class="ap-text-secondary" style="margin: 8px 0 24px;">
			<?php if ( $search_query ) : ?>
			Não encontramos anúncios para "<?php echo esc_html( $search_query ); ?>".
			<?php else : ?>
			Seja o primeiro a anunciar!
			<?php endif; ?>
		</p>
		<a href="<?php echo esc_url( home_url( '/anunciar/' ) ); ?>" class="ap-btn ap-btn-primary">
			<i class="ri-add-circle-line"></i> Publicar Anúncio
		</a>
		</div>

	<?php endif; ?>
	<?php wp_reset_postdata(); ?>
		</main>

		<!-- ====================================================================
			[FOOTER] Create Ad Button & Pagination
			==================================================================== -->
		<?php if ( $classifieds->max_num_pages > 1 || $classifieds->found_posts > 0 ) : ?>
		<div class="ap-classifieds-footer">
			<?php if ( $classifieds->max_num_pages > 1 ) : ?>
			<div class="ap-pagination">
				<?php
				echo wp_kses_post(
					paginate_links(
						array(
							'total'     => $classifieds->max_num_pages,
							'current'   => $current_page,
							'prev_text' => '<i class="ri-arrow-left-s-line"></i>',
							'next_text' => '<i class="ri-arrow-right-s-line"></i>',
						)
					)
				);
				?>
			</div>
			<?php endif; ?>
			<a href="<?php echo esc_url( home_url( '/anunciar/' ) ); ?>" class="ap-btn ap-btn-primary">
				<i class="ri-add-circle-line"></i> Criar Anúncio
			</a>
		</div>
		<?php endif; ?>

		</div>

		<!-- ====================================================================
			[MOBILE NAV] Bottom Navigation for Mobile
			==================================================================== -->
		<?php get_template_part( 'partials/social-bottom-bar' ); ?>

	</div>
	</div>

<?php get_footer(); ?>
