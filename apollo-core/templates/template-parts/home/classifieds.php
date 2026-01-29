<?php
/**
 * Apollo Classifieds Section - Ticket Resales & Accommodations
 *
 * Dynamic section pulling from classifieds CPT
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

defined( 'ABSPATH' ) || exit;

$args = $args ?? array();

// Query ticket resales (repasses).
$resales_query = new WP_Query(
	array(
		'post_type'      => 'classified',
		'posts_per_page' => 12,
		'post_status'    => 'publish',
		'tax_query'      => array(
			array(
				'taxonomy' => 'classified_domain',
				'field'    => 'slug',
				'terms'    => array( 'repasse', 'ingresso', 'ticket' ),
			),
		),
		'meta_query'     => array(
			array(
				'key'     => '_classified_status',
				'value'   => 'active',
				'compare' => '=',
			),
		),
	)
);

// Query accommodations.
$accommodations_query = new WP_Query(
	array(
		'post_type'      => 'classified',
		'posts_per_page' => 4,
		'post_status'    => 'publish',
		'tax_query'      => array(
			array(
				'taxonomy' => 'classified_domain',
				'field'    => 'slug',
				'terms'    => array( 'acomodacao', 'hospedagem', 'hosting' ),
			),
		),
		'meta_query'     => array(
			array(
				'key'     => '_classified_status',
				'value'   => 'active',
				'compare' => '=',
			),
		),
	)
);
?>


<section class="classifieds">
	<div class="classifieds-header reveal-up">
		<h2><?php esc_html_e( 'Classificados Apollo', 'apollo-core' ); ?></h2>
		<p><?php esc_html_e( 'Repasses verificados pela comunidade. Preview disponível para membros.', 'apollo-core' ); ?></p>
	</div>

	<!-- TICKET RESALES -->
	<div class="tickets-section reveal-up delay-100">
		<h3><?php esc_html_e( 'Repasse de Ingressos', 'apollo-core' ); ?></h3>
		<div class="tickets-scroll" id="ticketsScroll">
			<div class="tickets-track">
				<?php
				if ( $resales_query->have_posts() ) :
					while ( $resales_query->have_posts() ) :
						$resales_query->the_post();
						$classified_id   = get_the_ID();
						$event_name      = get_post_meta( $classified_id, '_event_name', true ) ?: get_the_title();
						$event_date      = get_post_meta( $classified_id, '_event_date', true );
						$ticket_type     = get_post_meta( $classified_id, '_ticket_type', true ) ?: 'Pista';
						$venue           = get_post_meta( $classified_id, '_venue', true ) ?: 'Local';
						$price           = get_post_meta( $classified_id, '_price', true );
						$original_price  = get_post_meta( $classified_id, '_original_price', true );
						$discount        = 0;

						if ( $original_price && $price && $original_price > $price ) {
							$discount = round( ( ( $original_price - $price ) / $original_price ) * 100 );
						}

						$date_formatted = $event_date ? wp_date( 'd M', strtotime( $event_date ) ) : '';
						?>
						<div class="resell-ticket" data-id="<?php echo esc_attr( $classified_id ); ?>">
							<div class="ticket-event"><?php echo esc_html( $event_name ); ?></div>
							<div class="ticket-info"><?php echo esc_html( $date_formatted . ' · ' . $ticket_type . ' · ' . $venue ); ?></div>
							<div class="ticket-price">
								R$ <?php echo esc_html( number_format( (float) $price, 0, ',', '.' ) ); ?>
								<?php if ( $original_price && $original_price > $price ) : ?>
									<span class="ticket-original">R$ <?php echo esc_html( number_format( (float) $original_price, 0, ',', '.' ) ); ?></span>
								<?php endif; ?>
							</div>
							<?php if ( $discount > 0 ) : ?>
								<span class="ticket-badge">-<?php echo esc_html( $discount ); ?>%</span>
							<?php endif; ?>
						</div>
						<?php
					endwhile;
					wp_reset_postdata();
				else :
					// Fallback placeholder.
					?>
					<div class="resell-ticket">
						<div class="ticket-event"><?php esc_html_e( 'Nenhum repasse disponível', 'apollo-core' ); ?></div>
						<div class="ticket-info"><?php esc_html_e( 'Novos repasses em breve', 'apollo-core' ); ?></div>
						<div class="ticket-price">--</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- ACCOMMODATIONS -->
	<div class="accommodations-section reveal-up delay-200">
		<h3><?php esc_html_e( 'Acomodações', 'apollo-core' ); ?></h3>
		<div class="accommodations-grid">
			<?php
			if ( $accommodations_query->have_posts() ) :
				while ( $accommodations_query->have_posts() ) :
					$accommodations_query->the_post();
					$classified_id = get_the_ID();
					$neighborhood  = get_post_meta( $classified_id, '_neighborhood', true ) ?: get_the_title();
					$price_night   = get_post_meta( $classified_id, '_price_per_night', true );
					$thumbnail     = get_the_post_thumbnail_url( $classified_id, 'medium' );
					?>
					<a href="<?php the_permalink(); ?>" class="accommodation-card">
						<div class="accommodation-image">
							<?php if ( $thumbnail ) : ?>
								<img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php the_title_attribute(); ?>">
							<?php endif; ?>
						</div>
						<div class="accommodation-info">
							<div class="accommodation-hood"><?php echo esc_html( $neighborhood ); ?></div>
							<div class="accommodation-price">
								R$ <?php echo esc_html( number_format( (float) $price_night, 0, ',', '.' ) ); ?>
								<span>/noite</span>
							</div>
						</div>
					</a>
					<?php
				endwhile;
				wp_reset_postdata();
			else :
				// Fallback static cards.
				$fallback_items = array(
					array( 'hood' => 'Copacabana', 'price' => 55, 'img' => 'https://apollo.rio.br/v2/host3.jpg' ),
					array( 'hood' => 'Botafogo', 'price' => 93, 'img' => 'https://apollo.rio.br/v2/host2.jpg' ),
					array( 'hood' => 'Flamengo', 'price' => 65, 'img' => 'https://apollo.rio.br/v2/host1.jpg' ),
				);
				foreach ( $fallback_items as $item ) :
					?>
					<div class="accommodation-card">
						<div class="accommodation-image">
							<img src="<?php echo esc_url( $item['img'] ); ?>" alt="<?php echo esc_attr( $item['hood'] ); ?>">
						</div>
						<div class="accommodation-info">
							<div class="accommodation-hood"><?php echo esc_html( $item['hood'] ); ?></div>
							<div class="accommodation-price">R$ <?php echo esc_html( $item['price'] ); ?><span>/noite</span></div>
						</div>
					</div>
					<?php
				endforeach;
			endif;
			?>

			<!-- Ver Todas Card -->
			<a href="<?php echo esc_url( home_url( '/classificados/?cat=acomodacao' ) ); ?>" class="accommodation-card accommodation-more">
				<div class="accommodation-image">
					<div class="more-overlay">
						<span><?php esc_html_e( 'Ver todas', 'apollo-core' ); ?> <i class="ri-arrow-right-up-long-line" style="font-size:11px!important;"></i></span>
					</div>
				</div>
				<div class="accommodation-info">
					<div class="accommodation-hood"></div>
				</div>
			</a>
		</div>
	</div>
</section>
