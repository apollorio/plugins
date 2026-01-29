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
$domain_filter = isset( $_GET['domain'] ) ? sanitize_text_field( wp_unslash( $_GET['domain'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public archive filters, no data modification.
$intent_filter = isset( $_GET['intent'] ) ? sanitize_text_field( wp_unslash( $_GET['intent'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public archive filters, no data modification.
$search_query = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public archive filters, no data modification.
$sort_by = isset( $_GET['sort'] ) ? sanitize_text_field( wp_unslash( $_GET['sort'] ) ) : 'recent';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public archive filters, no data modification.
$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public archive filters, no data modification.
$date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';

$args = array(
	'post_type'      => 'apollo_classified',
	'post_status'    => 'publish',
	'posts_per_page' => 12,
	'paged'          => $current_page,
	'orderby'        => 'date',
	'order'          => 'DESC',
);

// Apply taxonomy filters.
$tax_query = array();

if ( $domain_filter && in_array( $domain_filter, array( 'ingressos', 'acomodacao' ), true ) ) {
	$tax_query[] = array(
		'taxonomy' => 'classified_domain',
		'field'    => 'slug',
		'terms'    => $domain_filter,
	);
}

if ( $intent_filter && in_array( $intent_filter, array( 'ofereco', 'procuro' ), true ) ) {
	$tax_query[] = array(
		'taxonomy' => 'classified_intent',
		'field'    => 'slug',
		'terms'    => $intent_filter,
	);
}

if ( ! empty( $tax_query ) ) {
	$args['tax_query'] = $tax_query;
}

// Apply meta filters for dates.
$meta_query = array();

if ( $date_from || $date_to ) {
	if ( $domain_filter === 'ingressos' ) {
		$date_meta_key = '_classified_event_date';
	} elseif ( $domain_filter === 'acomodacao' ) {
		$date_meta_key = '_classified_start_date';
	} else {
		// If no domain, skip date filter
		$date_from = '';
		$date_to   = '';
	}

	if ( $date_from ) {
		$meta_query[] = array(
			'key'     => $date_meta_key,
			'value'   => $date_from,
			'compare' => '>=',
			'type'    => 'NUMERIC',
		);
	}

	if ( $date_to ) {
		$meta_query[] = array(
			'key'     => $date_meta_key,
			'value'   => $date_to,
			'compare' => '<=',
			'type'    => 'NUMERIC',
		);
	}
}

if ( ! empty( $meta_query ) ) {
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

// Domains configuration.
$domains = array(
	'ingressos'  => array(
		'label' => 'Ingressos',
		'icon'  => 'ri-ticket-2-line',
		'badge' => 'ap-badge-tickets',
	),
	'acomodacao' => array(
		'label' => 'Acomodação',
		'icon'  => 'ri-home-heart-line',
		'badge' => 'ap-badge-accommodation',
	),
);

// Intents configuration.
$intents = array(
	'ofereco' => array(
		'label' => 'Ofereço',
		'icon'  => 'ri-add-circle-line',
	),
	'procuro' => array(
		'label' => 'Procuro',
		'icon'  => 'ri-search-line',
	),
);

// STRICT MODE: base.js handles all core assets - just ensure it's loaded
if ( function_exists( 'apollo_ensure_base_assets' ) ) {
	apollo_ensure_base_assets();
}

// Enqueue classified contact JS
wp_enqueue_script( 'apollo-classified-contact', APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/classified-contact.js', array( 'jquery' ), APOLLO_SOCIAL_VERSION, true );

// Localize classified contact
wp_localize_script(
	'apollo-classified-contact',
	'apolloClassifiedContact',
	array(
		'restUrl'    => rest_url( 'apollo-social/v1' ),
		'nonce'      => wp_create_nonce( 'wp_rest' ),
		'loginUrl'   => wp_login_url( get_permalink() ),
		'isLoggedIn' => is_user_logged_in(),
	)
);

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
						placeholder="<?php esc_attr_e( 'Buscar ingressos, quartos, equipamentos...', 'apollo-social' ); ?>"
						data-ap-tooltip="Digite para buscar anúncios">
				<?php if ( $domain_filter ) : ?>
				<input type="hidden" name="domain" value="<?php echo esc_attr( $domain_filter ); ?>">
				<?php endif; ?>
				<?php if ( $intent_filter ) : ?>
				<input type="hidden" name="intent" value="<?php echo esc_attr( $intent_filter ); ?>">
				<?php endif; ?>
				<?php if ( $date_from ) : ?>
				<input type="hidden" name="date_from" value="<?php echo esc_attr( $date_from ); ?>">
				<?php endif; ?>
				<?php if ( $date_to ) : ?>
				<input type="hidden" name="date_to" value="<?php echo esc_attr( $date_to ); ?>">
				<?php endif; ?>
				</form>
			</div>

			<!-- Domain Tabs -->
			<div class="ap-filter-tabs ap-filter-tabs-pills">
				<a href="<?php echo esc_url( remove_query_arg( array( 'domain', 'intent', 'date_from', 'date_to', 'sort' ) ) ); ?>" class="ap-tab-pill <?php echo ! $domain_filter ? 'active' : ''; ?>" data-filter="all" data-ap-tooltip="Mostrar todos os anúncios">
				<i class="ri-apps-line"></i> Todos
				</a>

				<?php foreach ( $domains as $domain_slug => $domain_data ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'domain', $domain_slug, remove_query_arg( array( 'intent', 'date_from', 'date_to' ) ) ) ); ?>" class="ap-tab-pill <?php echo $domain_filter === $domain_slug ? 'active' : ''; ?>" data-filter="<?php echo esc_attr( $domain_slug ); ?>" data-ap-tooltip="Filtrar por <?php echo esc_attr( $domain_data['label'] ); ?>">
					<i class="<?php echo esc_attr( $domain_data['icon'] ); ?>"></i>
					<?php echo esc_html( $domain_data['label'] ); ?>
				</a>
				<?php endforeach; ?>
			</div>

			<!-- Intent Filters -->
			<div class="ap-filter-tabs ap-filter-tabs-secondary">
				<?php foreach ( $intents as $intent_slug => $intent_data ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'intent', $intent_slug ) ); ?>" class="ap-tab-secondary <?php echo $intent_filter === $intent_slug ? 'active' : ''; ?>" data-intent="<?php echo esc_attr( $intent_slug ); ?>" data-ap-tooltip="<?php echo esc_attr( $intent_data['label'] ); ?>">
					<i class="<?php echo esc_attr( $intent_data['icon'] ); ?>"></i>
					<?php echo esc_html( $intent_data['label'] ); ?>
				</a>
				<?php endforeach; ?>
			</div>

			<!-- Date Filters (conditional) -->
			<?php if ( $domain_filter ) : ?>
			<div class="ap-date-filters">
				<div class="ap-date-input-group">
					<label for="date_from">De:</label>
					<input type="date" name="date_from" id="date_from" value="<?php echo esc_attr( $date_from ); ?>" onchange="this.form.submit()">
				</div>
				<div class="ap-date-input-group">
					<label for="date_to">Até:</label>
					<input type="date" name="date_to" id="date_to" value="<?php echo esc_attr( $date_to ); ?>" onchange="this.form.submit()">
				</div>
			</div>
			<?php endif; ?>

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

			$price    = get_post_meta( $classified_id, '_classified_price', true );
			$currency = get_post_meta( $classified_id, '_classified_currency', true ) ?: 'BRL';
			$location = get_post_meta( $classified_id, '_classified_location_text', true );

			// Domain and intent taxonomies
			$domain_terms = wp_get_post_terms( $classified_id, 'classified_domain', array( 'fields' => 'slugs' ) );
			$intent_terms = wp_get_post_terms( $classified_id, 'classified_intent', array( 'fields' => 'slugs' ) );
			$domain       = ! empty( $domain_terms ) ? $domain_terms[0] : 'ingressos';
			$intent       = ! empty( $intent_terms ) ? $intent_terms[0] : 'ofereco';

			// Domain-specific meta
			if ( $domain === 'ingressos' ) {
				$event_date  = get_post_meta( $classified_id, '_classified_event_date', true );
				$event_title = get_post_meta( $classified_id, '_classified_event_title', true );
			} elseif ( $domain === 'acomodacao' ) {
				$start_date = get_post_meta( $classified_id, '_classified_start_date', true );
				$end_date   = get_post_meta( $classified_id, '_classified_end_date', true );
				$capacity   = get_post_meta( $classified_id, '_classified_capacity', true );
			}

			$price_display = $price ? number_format( (float) $price, 0, ',', '.' ) : '—';
			$thumb         = get_the_post_thumbnail_url( $classified_id, 'medium' );

			// Domain config.
			$domain_config = $domains[ $domain ] ?? array(
				'label' => 'Outro',
				'icon'  => 'ri-price-tag-line',
				'badge' => '',
			);

			// Intent config.
			$intent_config = $intents[ $intent ] ?? array(
				'label' => 'Ofereço',
				'icon'  => 'ri-add-circle-line',
			);

			// Price unit based on domain.
			$price_units = array(
				'ingressos'  => '',
				'acomodacao' => '/mês',
			);
			$price_unit  = $price_units[ $domain ] ?? '';

			// Avatar gradient colors by domain.
			$avatar_colors   = array(
				'ingressos'  => '#6366f1, #8b5cf6',
				'acomodacao' => '#ec4899, #f472b6',
			);
			$avatar_gradient = $avatar_colors[ $domain ] ?? '#f97316, #fb923c';
			?>

		<a href="<?php the_permalink(); ?>" class="ap-advert-card" data-domain="<?php echo esc_attr( $domain ); ?>" data-intent="<?php echo esc_attr( $intent ); ?>" data-price="<?php echo esc_attr( $price ); ?>">
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
				<?php if ( $domain === 'ingressos' && $event_title ) : ?>
				<span class="ap-advert-event-title"><?php echo esc_html( $event_title ); ?></span>
				<span class="ap-advert-event-meta"><?php echo esc_html( $event_date ? date( 'd/m/Y', strtotime( $event_date ) ) : '' ); ?></span>
				<?php elseif ( $domain === 'acomodacao' ) : ?>
				<span class="ap-advert-event-title"><?php the_title(); ?></span>
				<span class="ap-advert-event-meta"><?php echo esc_html( $start_date && $end_date ? date( 'd/m', strtotime( $start_date ) ) . ' - ' . date( 'd/m', strtotime( $end_date ) ) : 'Período disponível' ); ?></span>
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
				<i class="<?php echo esc_attr( $domain_config['icon'] ); ?>" style="font-size:48px;color:var(--ap-text-muted);"></i>
			</div>
			<?php endif; ?>
			<span class="ap-advert-category-badge <?php echo esc_attr( $domain_config['badge'] ); ?>">
				<i class="<?php echo esc_attr( $domain_config['icon'] ); ?>"></i> <?php echo esc_html( $domain_config['label'] ); ?>
			</span>
			<span class="ap-advert-intent-badge">
				<i class="<?php echo esc_attr( $intent_config['icon'] ); ?>"></i> <?php echo esc_html( $intent_config['label'] ); ?>
			</span>
			</div>
		</div>

		<div class="ap-advert-info">
			<div class="ap-advert-location">
			<i class="ri-map-pin-line"></i>
			<span><?php echo esc_html( ! empty( $location ) ? $location : 'Rio de Janeiro' ); ?></span>
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
			<button type="button" class="ap-classified-contact-btn ap-advert-btn ap-advert-btn-primary" data-ap-tooltip="Falar no chat" data-ad-id="<?php echo esc_attr( $classified_id ); ?>" data-seller-id="<?php echo esc_attr( $author_id ); ?>" data-context="classified">
			<i class="ri-chat-3-line"></i> Falar no chat
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

	<!-- ====================================================================
		[SAFETY MODAL] Anti-Scam Guardrail
		==================================================================== -->
	<div id="apollo-safety-modal" class="ap-modal" style="display: none;">
		<div class="ap-modal-overlay" onclick="document.getElementById('apollo-safety-modal').style.display='none';"></div>
		<div class="ap-modal-content ap-modal-sm">
			<div class="ap-modal-header">
				<h3><i class="ri-shield-check-line"></i> Dicas de Segurança</h3>
				<button class="ap-modal-close" onclick="document.getElementById('apollo-safety-modal').style.display='none';">
					<i class="ri-close-line"></i>
				</button>
			</div>
			<div class="ap-modal-body">
				<p>Antes de iniciar uma conversa, lembre-se:</p>
				<ul class="ap-safety-tips">
					<li><i class="ri-check-line"></i> Nunca envie dinheiro antecipadamente</li>
					<li><i class="ri-check-line"></i> Verifique a reputação do vendedor</li>
					<li><i class="ri-check-line"></i> Prefira encontros em locais públicos</li>
					<li><i class="ri-check-line"></i> Use o chat interno para negociações</li>
					<li><i class="ri-check-line"></i> A Apollo Social não processa pagamentos</li>
				</ul>
				<p>
					<a href="https://dicas.apollo.rio.br" target="_blank" class="ap-link">
						<i class="ri-external-link-line"></i> Leia mais sobre segurança
					</a>
				</p>
			</div>
			<div class="ap-modal-footer">
				<button class="ap-btn ap-btn-outline" onclick="document.getElementById('apollo-safety-modal').style.display='none';">
					Cancelar
				</button>
				<button id="apollo-safety-confirm" class="ap-btn ap-btn-primary">
					Entendi, continuar
				</button>
			</div>
		</div>
	</div>

<?php get_footer(); ?>
