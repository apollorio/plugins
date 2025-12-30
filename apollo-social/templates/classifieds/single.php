<?php
/**
 * Single Classified Template - /anuncio/{slug}
 * STRICT MODE: 100% UNI.CSS conformance
 *
 * @package Apollo_Social
 * @version 2.0.0
 * @uses UNI.CSS v5.2.0 - Classifieds components
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Enqueue chat assets for classified conversations
wp_enqueue_script( 'apollo-chat', APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/chat.js', array( 'jquery' ), APOLLO_SOCIAL_VERSION, true );
wp_enqueue_style( 'apollo-chat', APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/chat.css', array(), APOLLO_SOCIAL_VERSION );

// Enqueue classified contact JS
wp_enqueue_script( 'apollo-classified-contact', APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/classified-contact.js', array( 'jquery' ), APOLLO_SOCIAL_VERSION, true );

// Localize chat config
wp_localize_script( 'apollo-chat', 'apolloChatConfig', array(
	'restUrl' => rest_url( 'apollo-social/v1' ),
	'nonce'   => wp_create_nonce( 'wp_rest' ),
) );

// Localize current user
wp_localize_script( 'apollo-chat', 'apolloChatUser', array(
	'id'    => get_current_user_id(),
	'name'  => wp_get_current_user()->display_name ?: 'Usuário',
	'avatar' => get_avatar_url( get_current_user_id(), array( 'size' => 32 ) ) ?: '',
) );

// Localize classified contact
wp_localize_script( 'apollo-classified-contact', 'apolloClassifiedContact', array(
	'restUrl' => rest_url( 'apollo-social/v1' ),
	'nonce'   => wp_create_nonce( 'wp_rest' ),
	'loginUrl' => wp_login_url( get_permalink() ),
	'isLoggedIn' => is_user_logged_in(),
) );

global $post;

// Get classified data
$classified_id   = get_the_ID();
$title           = get_the_title();
$content         = get_the_content();
$author_id       = get_post_field( 'post_author', $classified_id );
$author          = get_userdata( $author_id );
$author_name     = $author ? $author->display_name : 'Anunciante';
$author_login    = $author ? $author->user_login : '';
$author_initials = strtoupper( substr( $author_name, 0, 2 ) );
$author_since    = $author ? date( 'M Y', strtotime( $author->user_registered ) ) : '';

// Meta data.
$price            = get_post_meta( $classified_id, '_classified_price', true );
$currency         = get_post_meta( $classified_id, '_classified_currency', true ) ?: 'BRL';
$location         = get_post_meta( $classified_id, '_classified_location_text', true );
$contact_pref     = get_post_meta( $classified_id, '_classified_contact_pref', true );
$views_count      = (int) get_post_meta( $classified_id, '_classified_views', true );

// Domain and intent taxonomies
$domain_terms     = wp_get_post_terms( $classified_id, 'classified_domain', array( 'fields' => 'slugs' ) );
$intent_terms     = wp_get_post_terms( $classified_id, 'classified_intent', array( 'fields' => 'slugs' ) );
$domain           = ! empty( $domain_terms ) ? $domain_terms[0] : 'ingressos';
$intent           = ! empty( $intent_terms ) ? $intent_terms[0] : 'ofereco';

// Domain-specific meta
if ( $domain === 'ingressos' ) {
	$event_date   = get_post_meta( $classified_id, '_classified_event_date', true );
	$event_title  = get_post_meta( $classified_id, '_classified_event_title', true );
} elseif ( $domain === 'acomodacao' ) {
	$start_date   = get_post_meta( $classified_id, '_classified_start_date', true );
	$end_date     = get_post_meta( $classified_id, '_classified_end_date', true );
	$capacity     = get_post_meta( $classified_id, '_classified_capacity', true );
}

// Gallery
$gallery_ids      = get_post_meta( $classified_id, '_classified_gallery', true );
$gallery          = is_array( $gallery_ids ) ? $gallery_ids : array();

// Increment view count
update_post_meta( $classified_id, '_classified_views', $views_count + 1 );

// Images
$featured_image = get_the_post_thumbnail_url( $classified_id, 'large' );

// Format price.
$price_display = $price ? number_format( (float) $price, 0, ',', '.' ) : '—';
$currency_symbol = $currency === 'BRL' ? 'R$' : $currency;

// Domain and intent labels
$domain_labels = array(
	'ingressos'   => array(
		'label' => 'Ingressos',
		'icon'  => 'ri-ticket-2-fill',
		'badge' => 'ap-badge-tickets',
		'unit'  => '',
	),
	'acomodacao'  => array(
		'label' => 'Acomodação',
		'icon'  => 'ri-home-heart-fill',
		'badge' => 'ap-badge-accommodation',
		'unit'  => '/noite',
	),
);
$domain_config = $domain_labels[ $domain ] ?? array(
	'label' => 'Outro',
	'icon'  => 'ri-price-tag-fill',
	'badge' => '',
	'unit'  => '',
);

$intent_labels = array(
	'ofereco' => 'Ofereço',
	'procuro' => 'Procuro',
);
$intent_label = $intent_labels[ $intent ] ?? 'Ofereço';

// Avatar gradient colors by domain.
$avatar_colors   = array(
	'ingressos'   => '#6366f1, #8b5cf6',
	'acomodacao'  => '#ec4899, #f472b6',
);
$avatar_gradient = $avatar_colors[ $domain ] ?? '#f97316, #fb923c';

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
		[MAIN CONTENT] Single Classified
		==================================================================== -->
	<div class="flex-1 flex flex-col min-h-screen">
		<div class="ap-classifieds" style="max-width: 800px; margin: 0 auto;">

		<!-- ====================================================================
			[HEADER] Classified Header with Navigation
			==================================================================== -->
		<header class="ap-classifieds-header" style="margin-bottom: 0; padding: 16px 0;">
			<div style="display: flex; align-items: center; gap: 12px;">
				<a href="<?php echo esc_url( home_url( '/anuncios/' ) ); ?>" class="ap-btn-icon-sm" data-ap-tooltip="Voltar para anúncios">
				<i class="ri-arrow-left-line"></i>
				</a>
				<div class="ap-classifieds-brand" style="flex: 1;">
				<div class="ap-brand-icon" style="width: 36px; height: 36px; font-size: 18px;">
					<i class="ri-price-tag-3-fill"></i>
				</div>
				<div class="ap-brand-text">
					<h1 class="ap-brand-title" style="font-size: 16px;">Classificado<span class="ap-brand-accent">::</span>rio</h1>
				</div>
				</div>
			</div>

			<div style="display: flex; gap: 8px;">
				<button class="ap-btn-icon-sm" data-ap-tooltip="Compartilhar anúncio" onclick="navigator.share?.({title: '<?php echo esc_js( $title ); ?>', url: window.location.href})">
				<i class="ri-share-line"></i>
				</button>
				<button class="ap-btn-icon-sm" data-ap-tooltip="Salvar nos favoritos" data-action="favorite" data-post-id="<?php echo esc_attr( $classified_id ); ?>">
				<i class="ri-heart-3-line"></i>
				</button>
			</div>
		</header>

	<!-- Image Gallery -->
	<div class="ap-card" style="padding: 0; overflow: hidden; margin-bottom: 16px;">
	<?php if ( $featured_image ) : ?>
	<div style="position: relative; aspect-ratio: 16/10; background: var((--bg-main)-muted);">
		<img src="<?php echo esc_url( $featured_image ); ?>" alt="<?php echo esc_attr( $title ); ?>"
			style="width: 100%; height: 100%; object-fit: cover;"
			data-ap-tooltip="Imagem principal do anúncio">

		<!-- Category Badge -->
		<span class="ap-advert-category-badge <?php echo esc_attr( $domain_config['badge'] ); ?>" style="position: absolute; top: 12px; left: 12px;">
		<i class="<?php echo esc_attr( $domain_config['icon'] ); ?>"></i> <?php echo esc_html( $domain_config['label'] ); ?>
		</span>

		<?php if ( ! empty( $gallery ) && count( $gallery ) > 0 ) : ?>
		<span style="position: absolute; bottom: 12px; right: 12px; background: rgba(0,0,0,0.7); color: white; font-size: 12px; padding: 4px 10px; border-radius: 999px;"
			data-ap-tooltip="Total de fotos">
		<i class="ri-image-line"></i> <?php echo count( $gallery ) + 1; ?> fotos
		</span>
		<?php endif; ?>
	</div>
	<?php else : ?>
	<div style="aspect-ratio: 16/10; display: flex; align-items: center; justify-content: center; background: var((--bg-main)-muted);">
		<i class="<?php echo esc_attr( $domain_config['icon'] ); ?>" style="font-size: 64px; color: var(--ap-text-muted);"></i>
	</div>
	<?php endif; ?>

	<!-- Gallery Thumbnails -->
	<?php if ( ! empty( $gallery ) && count( $gallery ) > 0 ) : ?>
	<div style="display: flex; gap: 4px; padding: 4px; overflow-x: auto;">
		<div style="width: 60px; height: 60px; flex-shrink: 0; border-radius: 8px; overflow: hidden; border: 2px solid var(--ap-orange-500);">
		<img src="<?php echo esc_url( $featured_image ); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
		</div>
		<?php foreach ( $gallery as $image_id ) : ?>
		<div style="width: 60px; height: 60px; flex-shrink: 0; border-radius: 8px; overflow: hidden; cursor: pointer; opacity: 0.7; transition: opacity 0.2s;"
			onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.7">
			<?php echo wp_get_attachment_image( $image_id, 'thumbnail', false, array( 'style' => 'width:100%;height:100%;object-fit:cover;' ) ); ?>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
	</div>

	<!-- Price & Title Card -->
	<div class="ap-card" style="margin-bottom: 16px;">
	<div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px;">
		<div style="flex: 1; min-width: 0;">
		<div class="ap-advert-price" style="margin-bottom: 8px;">
			<span class="ap-price-currency"><?php echo esc_html( $currency_symbol ); ?></span>
			<span class="ap-price-value" style="font-size: 32px;"><?php echo esc_html( $price_display ); ?></span>
			<span class="ap-price-unit"><?php echo esc_html( $domain_config['unit'] ); ?></span>
		</div>
		<h1 style="font-size: 18px; font-weight: 700; color: var(--ap-text-primary); margin: 0;"><?php echo esc_html( $title ); ?></h1>
		</div>

		<span class="ap-badge" style="background: var(--ap-orange-500); color: white; border: none;"
			data-ap-tooltip="<?php echo esc_attr( $intent_label ); ?>">
		<?php echo esc_html( $intent_label ); ?>
		</span>
	</div>

	<!-- Meta Info -->
	<div style="display: flex; flex-wrap: wrap; gap: 16px; margin-top: 16px; font-size: 14px; color: var(--ap-text-muted);">
		<?php if ( $location ) : ?>
		<span style="display: flex; align-items: center; gap: 4px;" data-ap-tooltip="Localização">
		<i class="ri-map-pin-line"></i> <?php echo esc_html( $location ); ?>
		</span>
		<?php endif; ?>

		<span style="display: flex; align-items: center; gap: 4px;" data-ap-tooltip="Data de publicação">
		<i class="ri-time-line"></i> <?php echo esc_html( human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) ); ?> atrás
		</span>

		<span style="display: flex; align-items: center; gap: 4px;" data-ap-tooltip="Visualizações">
		<i class="ri-eye-line"></i> <?php echo esc_html( $views_count + 1 ); ?> views
		</span>

		<?php if ( ! empty( $quantity ) && $quantity !== '1' ) : ?>
		<span style="display: flex; align-items: center; gap: 4px;" data-ap-tooltip="Quantidade disponível">
		<i class="ri-stack-line"></i> <?php echo esc_html( $quantity ); ?>x disponível
		</span>
		<?php endif; ?>
	</div>

	<?php if ( $domain === 'ingressos' && $event_title ) : ?>
	<!-- Event Info for Tickets -->
	<div style="margin-top: 16px; padding: 12px; background: var((--bg-main)-muted); border-radius: var(--ap-radius-lg);">
		<div style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
		<i class="ri-calendar-event-fill" style="color: var(--ap-orange-500);"></i>
		<div>
			<strong><?php echo esc_html( $event_title ); ?></strong>
			<?php if ( $event_date ) : ?>
			<span style="color: var(--ap-text-muted);"> — <?php echo esc_html( date( 'd/m/Y', strtotime( $event_date ) ) ); ?></span>
			<?php endif; ?>
		</div>
		</div>
	</div>
	<?php elseif ( $domain === 'acomodacao' && ( $start_date || $end_date ) ) : ?>
	<!-- Period Info for Accommodation -->
	<div style="margin-top: 16px; padding: 12px; background: var((--bg-main)-muted); border-radius: var(--ap-radius-lg);">
		<div style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
		<i class="ri-calendar-check-fill" style="color: var(--ap-orange-500);"></i>
		<div>
			<strong>Período Disponível</strong>
			<?php if ( $start_date && $end_date ) : ?>
			<span style="color: var(--ap-text-muted);"> — <?php echo esc_html( date( 'd/m', strtotime( $start_date ) ) . ' - ' . date( 'd/m', strtotime( $end_date ) ) ); ?></span>
			<?php endif; ?>
		</div>
		</div>
	</div>
	<?php endif; ?>
	</div>

	<!-- Description Card -->
	<?php if ( $content ) : ?>
	<div class="ap-card" style="margin-bottom: 16px;">
	<h2 style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ap-text-muted); margin: 0 0 12px 0;">
		Descrição
	</h2>
	<div style="font-size: 14px; line-height: 1.6; color: var(--ap-text-secondary);">
		<?php echo wp_kses_post( wpautop( $content ) ); ?>
	</div>
	</div>
	<?php endif; ?>

	<!-- Seller Card -->
	<div class="ap-card" style="margin-bottom: 16px;">
	<h2 style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ap-text-muted); margin: 0 0 12px 0;">
		Anunciante
	</h2>
	<div style="display: flex; align-items: center; gap: 12px;">
		<a href="<?php echo esc_url( home_url( '/id/' . $author_login ) ); ?>" data-ap-tooltip="Ver perfil">
		<div class="ap-avatar ap-avatar-lg" style="background: linear-gradient(135deg, <?php echo esc_attr( $avatar_gradient ); ?>);">
			<span><?php echo esc_html( $author_initials ); ?></span>
		</div>
		</a>
		<div style="flex: 1; min-width: 0;">
		<a href="<?php echo esc_url( home_url( '/id/' . $author_login ) ); ?>"
			style="font-weight: 700; color: var(--ap-text-primary); text-decoration: none;"
			data-ap-tooltip="Ver perfil do anunciante">
			<?php echo esc_html( $author_name ); ?>
		</a>
		<p style="font-size: 13px; color: var(--ap-text-muted); margin: 2px 0 0;">
			<i class="ri-verified-badge-fill" style="color: var(--ap-orange-500);"></i>
			Membro desde <?php echo esc_html( $author_since ); ?>
		</p>
		</div>
		<a href="<?php echo esc_url( home_url( '/id/' . $author_login ) ); ?>" class="ap-btn ap-btn-outline ap-btn-sm">
		Ver Perfil
		</a>
	</div>
	</div>

	<!-- Safety Modal -->
	<div id="apollo-safety-modal" class="ap-modal" style="display: none;">
	<div class="ap-modal-overlay" onclick="document.getElementById('apollo-safety-modal').style.display='none';"></div>
	<div class="ap-modal-content">
		<div class="ap-modal-header">
		<h3>Dicas de Segurança</h3>
		<button class="ap-modal-close" onclick="document.getElementById('apollo-safety-modal').style.display='none';">&times;</button>
		</div>
		<div class="ap-modal-body">
		<ul style="list-style: none; padding: 0;">
			<li style="margin-bottom: 12px;"><i class="ri-shield-check-line" style="color: var(--ap-orange-500); margin-right: 8px;"></i> Prefira encontros em locais públicos e movimentados.</li>
			<li style="margin-bottom: 12px;"><i class="ri-shield-check-line" style="color: var(--ap-orange-500); margin-right: 8px;"></i> Verifique o produto ou serviço antes de efetuar qualquer pagamento.</li>
			<li style="margin-bottom: 12px;"><i class="ri-shield-check-line" style="color: var(--ap-orange-500); margin-right: 8px;"></i> Desconfie de preços muito abaixo do mercado ou ofertas "urgentes".</li>
			<li style="margin-bottom: 12px;"><i class="ri-shield-check-line" style="color: var(--ap-orange-500); margin-right: 8px;"></i> O Apollo Social não processa pagamentos - todas as transações são entre usuários.</li>
			<li><i class="ri-shield-check-line" style="color: var(--ap-orange-500); margin-right: 8px;"></i> Guarde comprovantes e comunique-se sempre pelo chat interno.</li>
		</ul>
		<p style="margin-top: 16px; font-size: 14px; color: var(--ap-text-muted);">
		<a href="https://dicas.apollo.rio.br" target="_blank" rel="noopener">Leia mais dicas de segurança</a>
		</p>
		</div>
		<div class="ap-modal-footer">
		<button class="ap-btn ap-btn-outline" onclick="document.getElementById('apollo-safety-modal').style.display='none';">Cancelar</button>
		<button class="ap-btn ap-btn-primary" id="apollo-safety-confirm">Entendi, continuar</button>
		</div>
	</div>
	</div>

	<!-- Contact Actions -->
	<div class="ap-card" style="margin-bottom: 16px;">
	<h2 style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ap-text-muted); margin: 0 0 12px 0;">
		Contato
	</h2>

	<div style="display: flex; flex-direction: column; gap: 8px;">

	<?php if ( is_user_logged_in() && get_current_user_id() !== $author_id ) : ?>
	<!-- Apollo Chat Integration -->
	<button class="ap-btn ap-btn-primary ap-classified-contact-btn"
		style="justify-content: center; padding: 14px;"
		data-ap-tooltip="Iniciar conversa no Apollo Chat"
		data-ad-id="<?php echo esc_attr( $classified_id ); ?>"
		data-seller-id="<?php echo esc_attr( $author_id ); ?>"
		data-context="classified"
		data-whatsapp="">
		<i class="ri-chat-1-line" style="font-size: 20px;"></i>
		Falar no Chat
	</button>
	<?php endif; ?>

	<?php if ( ! is_user_logged_in() ) : ?>
	<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"
		class="ap-btn ap-btn-primary"
		style="justify-content: center; padding: 14px;"
		data-ap-tooltip="Faça login para enviar mensagem">
		<i class="ri-mail-send-line" style="font-size: 20px;"></i>
		Faça Login para Contatar
	</a>
	<?php endif; ?>
	</div>
	</div>

	<!-- Safety Tips -->
	<div class="ap-safety-banner" style="margin-bottom: 24px;">
	<div class="ap-safety-icon"><i class="ri-shield-check-line"></i></div>
	<div class="ap-safety-text">
		<strong style="display: block; margin-bottom: 4px;">Dicas de Segurança</strong>
		<ul style="margin: 0; padding-left: 16px; font-size: 12px; line-height: 1.5;">
		<li>Prefira encontros em locais públicos</li>
		<li>Verifique o produto antes de pagar</li>
		<li>Desconfie de preços muito baixos</li>
		</ul>
	</div>
	<a href="https://dicas.apollo.rio.br" target="_blank" class="ap-safety-link" data-ap-tooltip="Ver mais dicas">
		<i class="ri-external-link-line"></i>
	</a>
	</div>

	<!-- Related Classifieds -->
	<?php
	$related_args = array(
		'post_type'      => 'apollo_classified',
		'post_status'    => 'publish',
		'posts_per_page' => 4,
		'post__not_in'   => array( $classified_id ),
		'tax_query'      => array(
			array(
				'taxonomy' => 'classified_domain',
				'field'    => 'slug',
				'terms'    => $domain,
			),
		),
	);
	$related      = new WP_Query( $related_args );

	if ( $related->have_posts() ) :
		?>
	<div style="margin-bottom: 24px;">
	<h2 style="font-size: 14px; font-weight: 700; color: var(--ap-text-primary); margin: 0 0 16px 0;">
		Anúncios Relacionados
	</h2>
	<div class="ap-adverts-grid" style="grid-template-columns: repeat(2, 1fr); gap: 12px;">
		<?php
		while ( $related->have_posts() ) :
			$related->the_post();
			$rel_id = get_the_ID();
			$rel_price = get_post_meta( $rel_id, '_classified_price', true );
			$rel_thumb = get_the_post_thumbnail_url( $rel_id, 'medium' );
			$rel_domain_terms = wp_get_post_terms( $rel_id, 'classified_domain', array( 'fields' => 'slugs' ) );
			$rel_domain = ! empty( $rel_domain_terms ) ? $rel_domain_terms[0] : 'ingressos';
			$rel_domain_config = $domain_labels[ $rel_domain ] ?? array( 'icon' => 'ri-price-tag-fill' );
			?>
		<a href="<?php the_permalink(); ?>" class="ap-card" style="padding: 0; overflow: hidden; text-decoration: none;">
		<div style="aspect-ratio: 4/3; background: var((--bg-main)-muted); position: relative;">
			<?php if ( $rel_thumb ) : ?>
			<img src="<?php echo esc_url( $rel_thumb ); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
			<?php endif; ?>
			<span class="ap-advert-category-badge" style="position: absolute; top: 8px; left: 8px; font-size: 12px;">
			<i class="<?php echo esc_attr( $rel_domain_config['icon'] ); ?>"></i>
			</span>
		</div>
		<div style="padding: 10px;">
			<span style="font-size: 14px; font-weight: 700; color: var(--ap-orange-500);">
			<?php echo esc_html( $currency_symbol ); ?> <?php echo esc_html( $rel_price ? number_format( (float) $rel_price, 0, ',', '.' ) : '—' ); ?>
			</span>
			<h3 style="font-size: 12px; color: var(--ap-text-primary); margin: 4px 0 0; line-height: 1.3;">
			<?php the_title(); ?>
			</h3>
		</div>
		</a>
			<?php
		endwhile;
		wp_reset_postdata();
		?>
	</div>
		<?php endif; ?>

		<!-- ====================================================================
			[BACK TO LISTINGS] Footer Action
			==================================================================== -->
		<div style="text-align: center; padding-bottom: 24px;">
			<a href="<?php echo esc_url( home_url( '/anuncios/' ) ); ?>" class="ap-btn ap-btn-outline">
				<i class="ri-arrow-left-line"></i> Ver Todos os Anúncios
			</a>
		</div>

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
