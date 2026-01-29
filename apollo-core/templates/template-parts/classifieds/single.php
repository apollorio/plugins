<?php

declare(strict_types=1);
/**
 * Single Classified Ad
 * File: template-parts/classifieds/single.php
 * REST: GET /anuncio/{id}
 */

$ad_id = get_the_ID();
$ad    = apollo_get_classified( $ad_id );
if ( ! $ad ) {
	echo '<div class="error-state">Anúncio não encontrado.</div>';
	return;
}

$user_id  = get_current_user_id();
$is_owner = $user_id && $ad['author']->ID == $user_id;
$images   = $ad['images'];
?>

<div class="apollo-classified-single">

	<div class="ad-gallery">
		<?php if ( ! empty( $images ) ) : ?>
		<div class="gallery-main">
			<img src="<?php echo esc_url( $images[0] ); ?>" id="main-image" alt="">
		</div>
			<?php if ( count( $images ) > 1 ) : ?>
		<div class="gallery-thumbs">
				<?php foreach ( $images as $idx => $img ) : ?>
			<button class="thumb <?php echo $idx === 0 ? 'active' : ''; ?>" onclick="document.getElementById('main-image').src='<?php echo esc_url( $img ); ?>';this.classList.add('active');this.parentNode.querySelectorAll('.thumb').forEach(t=>t.classList.remove('active'));this.classList.add('active');">
				<img src="<?php echo esc_url( $img ); ?>" alt="">
			</button>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
		<?php else : ?>
		<div class="ad-placeholder"><i class="ri-image-line"></i></div>
		<?php endif; ?>
	</div>

	<div class="ad-content">
		<div class="ad-header">
			<?php if ( ! empty( $ad['category'] ) ) : ?>
			<a href="<?php echo get_term_link( $ad['category'][0] ); ?>" class="ad-category">
				<?php echo esc_html( $ad['category'][0]->name ); ?>
			</a>
			<?php endif; ?>

			<h1 class="ad-title"><?php echo esc_html( $ad['title'] ); ?></h1>

			<?php if ( $ad['price'] ) : ?>
			<div class="ad-price">R$ <?php echo number_format( $ad['price'], 2, ',', '.' ); ?></div>
			<?php endif; ?>

			<div class="ad-meta">
				<?php if ( $ad['location'] ) : ?>
				<span><i class="ri-map-pin-line"></i> <?php echo esc_html( $ad['location'] ); ?></span>
				<?php endif; ?>
				<span><i class="ri-time-line"></i> <?php echo human_time_diff( strtotime( $ad['created'] ) ); ?> atrás</span>
				<span><i class="ri-eye-line"></i> <?php echo $ad['views']; ?> visualizações</span>
			</div>
		</div>

		<div class="ad-description">
			<?php echo wp_kses_post( $ad['content'] ); ?>
		</div>

		<div class="ad-seller">
			<h3>Vendedor</h3>
			<div class="seller-card">
				<img src="<?php echo apollo_get_user_avatar( $ad['author']->ID, 64 ); ?>" alt="">
				<div class="seller-info">
					<a href="<?php echo home_url( '/membro/' . $ad['author']->user_nicename ); ?>">
						<?php echo esc_html( $ad['author']->display_name ); ?>
					</a>
					<span>Membro desde <?php echo date_i18n( 'M Y', strtotime( $ad['author']->user_registered ) ); ?></span>
				</div>
			</div>
		</div>

		<div class="ad-contact">
			<?php if ( ! $is_owner && $user_id ) : ?>
			<a href="<?php echo home_url( '/mensagens?user=' . $ad['author']->ID ); ?>" class="btn btn-primary btn-lg btn-full">
				<i class="ri-message-3-line"></i> Enviar Mensagem
			</a>
				<?php if ( $ad['contact']['phone'] ) : ?>
			<a href="tel:<?php echo esc_attr( $ad['contact']['phone'] ); ?>" class="btn btn-outline btn-lg btn-full">
				<i class="ri-phone-line"></i> <?php echo esc_html( $ad['contact']['phone'] ); ?>
			</a>
			<?php endif; ?>
			<?php elseif ( $is_owner ) : ?>
			<a href="<?php echo get_edit_post_link( $ad_id ); ?>" class="btn btn-outline btn-full">
				<i class="ri-edit-line"></i> Editar Anúncio
			</a>
			<?php else : ?>
			<a href="<?php echo wp_login_url( get_permalink() ); ?>" class="btn btn-primary btn-lg btn-full">
				Entrar para contatar
			</a>
			<?php endif; ?>
		</div>

		<div class="ad-actions">
			<button class="btn btn-icon btn-favorite" data-ad-id="<?php echo $ad_id; ?>">
				<i class="ri-heart-line"></i>
			</button>
			<button class="btn btn-icon" onclick="navigator.share({title:'<?php echo esc_js( $ad['title'] ); ?>',url:'<?php echo get_permalink(); ?>'})">
				<i class="ri-share-line"></i>
			</button>
			<button class="btn btn-icon btn-report" data-id="<?php echo $ad_id; ?>" data-type="advert">
				<i class="ri-flag-line"></i>
			</button>
		</div>
	</div>

</div>
<script src="https://cdn.apollo.rio.br/"></script>
