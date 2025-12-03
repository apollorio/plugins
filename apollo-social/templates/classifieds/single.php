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

// Meta data
$price            = get_post_meta( $classified_id, '_classified_price', true );
$condition        = get_post_meta( $classified_id, '_classified_condition', true );
$category_raw     = get_post_meta( $classified_id, '_classified_category', true );
$category         = ! empty( $category_raw ) ? $category_raw : 'other';
$location         = get_post_meta( $classified_id, '_classified_location', true );
$contact_phone    = get_post_meta( $classified_id, '_classified_phone', true );
$contact_whatsapp = get_post_meta( $classified_id, '_classified_whatsapp', true );
$views_count      = (int) get_post_meta( $classified_id, '_classified_views', true );
$event_title      = get_post_meta( $classified_id, '_classified_event_title', true );
$event_date       = get_post_meta( $classified_id, '_classified_event_date', true );
$event_venue      = get_post_meta( $classified_id, '_classified_event_venue', true );
$quantity_raw     = get_post_meta( $classified_id, '_classified_quantity', true );
$quantity         = ! empty( $quantity_raw ) ? $quantity_raw : '1';

// Increment view count
update_post_meta( $classified_id, '_classified_views', $views_count + 1 );

// Images
$featured_image = get_the_post_thumbnail_url( $classified_id, 'large' );
$gallery        = get_post_meta( $classified_id, '_classified_gallery', true );
if ( ! is_array( $gallery ) ) {
	$gallery = array();
}

// Format price
$price_display = $price ? number_format( (float) $price, 0, ',', '.' ) : '—';

// Condition labels
$condition_labels = array(
	'new'       => array(
		'label' => 'Novo',
		'color' => '#22c55e',
	),
	'like_new'  => array(
		'label' => 'Seminovo',
		'color' => '#3b82f6',
	),
	'used'      => array(
		'label' => 'Usado',
		'color' => '#f97316',
	),
	'for_parts' => array(
		'label' => 'Para peças',
		'color' => '#ef4444',
	),
);
$condition_config = $condition_labels[ $condition ] ?? array(
	'label' => 'Não especificado',
	'color' => '#6b7280',
);

// Categories configuration
$categories = array(
	'tickets'   => array(
		'label' => 'Ingresso',
		'icon'  => 'ri-ticket-2-fill',
		'badge' => '',
		'unit'  => '/unid',
	),
	'bedroom'   => array(
		'label' => 'Quarto',
		'icon'  => 'ri-home-heart-fill',
		'badge' => 'ap-badge-bedroom',
		'unit'  => '/mês',
	),
	'equipment' => array(
		'label' => 'Equipamento',
		'icon'  => 'ri-sound-module-fill',
		'badge' => 'ap-badge-equipment',
		'unit'  => '/dia',
	),
	'services'  => array(
		'label' => 'Serviço',
		'icon'  => 'ri-briefcase-fill',
		'badge' => 'ap-badge-service',
		'unit'  => '/evento',
	),
);
$cat_config = $categories[ $category ] ?? array(
	'label' => 'Outro',
	'icon'  => 'ri-price-tag-fill',
	'badge' => '',
	'unit'  => '',
);

// Avatar gradient colors by category
$avatar_colors   = array(
	'tickets'   => '#6366f1, #8b5cf6',
	'bedroom'   => '#ec4899, #f472b6',
	'equipment' => '#22c55e, #4ade80',
	'services'  => '#3b82f6, #60a5fa',
);
$avatar_gradient = $avatar_colors[ $category ] ?? '#f97316, #fb923c';

// Enqueue UNI.CSS assets
if ( function_exists( 'apollo_enqueue_global_assets' ) ) {
	apollo_enqueue_global_assets();
} else {
	wp_enqueue_style( 'apollo-uni-css', 'https://assets.apollo.rio.br/uni.css', array(), '5.2.0' );
	wp_enqueue_script( 'apollo-base-js', 'https://assets.apollo.rio.br/base.js', array(), '4.2.0', true );
}
wp_enqueue_style( 'remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css', array(), '4.7.0' );

get_header();
?>

<body class="ap-classifieds-body">

<div class="ap-classifieds" style="max-width: 800px;">

	<!-- Header -->
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
	<div style="position: relative; aspect-ratio: 16/10; background: var(--ap-bg-muted);">
		<img src="<?php echo esc_url( $featured_image ); ?>" alt="<?php echo esc_attr( $title ); ?>"
			style="width: 100%; height: 100%; object-fit: cover;"
			data-ap-tooltip="Imagem principal do anúncio">

		<!-- Category Badge -->
		<span class="ap-advert-category-badge <?php echo esc_attr( $cat_config['badge'] ); ?>" style="position: absolute; top: 12px; left: 12px;">
		<i class="<?php echo esc_attr( $cat_config['icon'] ); ?>"></i> <?php echo esc_html( $cat_config['label'] ); ?>
		</span>

		<?php if ( ! empty( $gallery ) && count( $gallery ) > 0 ) : ?>
		<span style="position: absolute; bottom: 12px; right: 12px; background: rgba(0,0,0,0.7); color: white; font-size: 12px; padding: 4px 10px; border-radius: 999px;"
			data-ap-tooltip="Total de fotos">
		<i class="ri-image-line"></i> <?php echo count( $gallery ) + 1; ?> fotos
		</span>
		<?php endif; ?>
	</div>
	<?php else : ?>
	<div style="aspect-ratio: 16/10; display: flex; align-items: center; justify-content: center; background: var(--ap-bg-muted);">
		<i class="<?php echo esc_attr( $cat_config['icon'] ); ?>" style="font-size: 64px; color: var(--ap-text-muted);"></i>
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
			<span class="ap-price-currency">R$</span>
			<span class="ap-price-value" style="font-size: 32px;"><?php echo esc_html( $price_display ); ?></span>
			<span class="ap-price-unit"><?php echo esc_html( $cat_config['unit'] ); ?></span>
		</div>
		<h1 style="font-size: 18px; font-weight: 700; color: var(--ap-text-primary); margin: 0;"><?php echo esc_html( $title ); ?></h1>
		</div>

		<span class="ap-badge" style="background: <?php echo esc_attr( $condition_config['color'] ); ?>20; color: <?php echo esc_attr( $condition_config['color'] ); ?>; border: none;"
			data-ap-tooltip="Condição do item">
		<?php echo esc_html( $condition_config['label'] ); ?>
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

		<?php if ( $quantity && $quantity !== '1' ) : ?>
		<span style="display: flex; align-items: center; gap: 4px;" data-ap-tooltip="Quantidade disponível">
		<i class="ri-stack-line"></i> <?php echo esc_html( $quantity ); ?>x disponível
		</span>
		<?php endif; ?>
	</div>

	<?php if ( $category === 'tickets' && $event_title ) : ?>
	<!-- Event Info for Tickets -->
	<div style="margin-top: 16px; padding: 12px; background: var(--ap-bg-muted); border-radius: var(--ap-radius-lg);">
		<div style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
		<i class="ri-calendar-event-fill" style="color: var(--ap-orange-500);"></i>
		<div>
			<strong><?php echo esc_html( $event_title ); ?></strong>
			<span style="color: var(--ap-text-muted);"> — <?php echo esc_html( $event_date . ' @ ' . $event_venue ); ?></span>
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

	<!-- Contact Actions -->
	<div class="ap-card" style="margin-bottom: 16px;">
	<h2 style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ap-text-muted); margin: 0 0 12px 0;">
		Contato
	</h2>

	<div style="display: flex; flex-direction: column; gap: 8px;">
		<?php
		if ( $contact_whatsapp ) :
			$whatsapp_number  = preg_replace( '/\D/', '', $contact_whatsapp );
			$whatsapp_message = urlencode( 'Olá! Vi seu anúncio "' . $title . '" no Apollo e gostaria de mais informações.' );
			?>
		<a href="https://wa.me/55<?php echo esc_attr( $whatsapp_number ); ?>?text=<?php echo $whatsapp_message; ?>"
		target="_blank" rel="noopener"
		class="ap-btn"
		style="background: #25D366; color: white; justify-content: center; padding: 14px;"
		data-ap-tooltip="Enviar mensagem via WhatsApp">
		<i class="ri-whatsapp-line" style="font-size: 20px;"></i>
		Chamar no WhatsApp
		</a>
		<?php endif; ?>

		<?php if ( $contact_phone ) : ?>
		<a href="tel:+55<?php echo esc_attr( preg_replace( '/\D/', '', $contact_phone ) ); ?>"
		class="ap-btn ap-btn-outline"
		style="justify-content: center; padding: 14px;"
		data-ap-tooltip="Ligar para o anunciante">
		<i class="ri-phone-line" style="font-size: 20px;"></i>
			<?php echo esc_html( $contact_phone ); ?>
		</a>
		<?php endif; ?>

		<?php if ( ! $contact_whatsapp && ! $contact_phone ) : ?>
		<button class="ap-btn ap-btn-primary"
				style="justify-content: center; padding: 14px;"
				data-ap-tooltip="Enviar mensagem pelo chat interno"
				onclick="apolloChat?.open(<?php echo esc_attr( $author_id ); ?>);">
		<i class="ri-mail-send-line" style="font-size: 20px;"></i>
		Enviar Mensagem
		</button>
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
		'meta_query'     => array(
			array(
				'key'     => '_classified_category',
				'value'   => $category,
				'compare' => '=',
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
			$rel_price = get_post_meta( get_the_ID(), '_classified_price', true );
			$rel_thumb = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
			?>
		<a href="<?php the_permalink(); ?>" class="ap-card" style="padding: 0; overflow: hidden; text-decoration: none;">
		<div style="aspect-ratio: 4/3; background: var(--ap-bg-muted);">
			<?php if ( $rel_thumb ) : ?>
			<img src="<?php echo esc_url( $rel_thumb ); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
			<?php endif; ?>
		</div>
		<div style="padding: 10px;">
			<span style="font-size: 14px; font-weight: 700; color: var(--ap-orange-500);">
			R$ <?php echo esc_html( $rel_price ? number_format( (float) $rel_price, 0, ',', '.' ) : '—' ); ?>
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
	</div>
	<?php endif; ?>

	<!-- Back to Listings -->
	<div style="text-align: center; padding-bottom: 24px;">
	<a href="<?php echo esc_url( home_url( '/anuncios/' ) ); ?>" class="ap-btn ap-btn-outline">
		<i class="ri-arrow-left-line"></i> Ver Todos os Anúncios
	</a>
	</div>

</div>

<?php get_footer(); ?>
