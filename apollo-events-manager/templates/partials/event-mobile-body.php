<div class="mobile-container">

	<!-- HERO -->
	<div class="hero-media">
		<div class="video-cover">
			<?php if ( $youtube_embed ) : ?>
				<iframe
					src="<?php echo esc_url( $youtube_embed ); ?>"
					frameborder="0"
					allow="autoplay; encrypted-media"
					allowfullscreen
				></iframe>
			<?php else : ?>
				<img src="<?php echo esc_url( $featured_img ); ?>" alt="<?php echo esc_attr( $event_title ); ?>">
			<?php endif; ?>
		</div>

		<div class="hero-overlay"></div>

		<div class="hero-content">
			<!-- Event Tags -->
			<?php
			$tags = get_the_terms( $event_id, 'event_listing_type' );
			if ( $tags && ! is_wp_error( $tags ) ) :
				foreach ( $tags as $tag ) :
					$icon_class = 'ri-star-fill'; // default
					switch ( $tag->slug ) {
						case 'featured':
							$icon_class = 'ri-verified-badge-fill';
							break;
						case 'recommended':
							$icon_class = 'ri-award-fill';
							break;
						case 'hot':
							$icon_class = 'ri-fire-fill';
							break;
					}
					?>
				<span class="event-tag-pill"><i class="<?php echo esc_attr( $icon_class ); ?>"></i> <?php echo esc_html( $tag->name ); ?></span>
					<?php
				endforeach;
			endif;
			?>

			<div class="hero-title"><?php echo esc_html( $event_title ); ?></div>

			<div class="hero-meta">
				<?php if ( $event_date_display ) : ?>
					<div class="hero-meta-item">
						<i class="ri-calendar-line"></i>
						<span><?php echo esc_html( $event_date_display ); ?></span>
					</div>
				<?php endif; ?>

				<?php if ( $event_city || $event_address ) : ?>
					<div class="hero-meta-item">
						<i class="ri-map-pin-line"></i>
						<span><?php echo esc_html( $event_city ?: $event_address ); ?></span>
						<?php if ( $event_start_time ) : ?>
							<span class="yoha"><?php echo esc_html( $event_start_time ); ?><?php echo $event_end_time ? ' - ' . esc_html( $event_end_time ) : ''; ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- BODY -->
	<div class="event-body">

		<!-- Quick actions -->
		<div class="quick-actions">
			<div class="quick-action" id="favoriteTrigger">
				<div class="quick-action-icon">
					<i class="ri-rocket-line"></i>
				</div>
				<div class="quick-action-label">Interessado</div>
			</div>

			<div class="quick-action" onclick="navigator.share({title: '<?php echo esc_js( $event_title ); ?>', url: window.location.href})">
				<div class="quick-action-icon">
					<i class="ri-share-forward-line"></i>
				</div>
				<div class="quick-action-label">Compartilhar</div>
			</div>
		</div>

		<!-- Interested avatars -->
		<div class="rsvp-row">
			<div class="avatars-explosion">
				<?php
				$result_count = $total_interested;
				foreach ( $visible_ids as $user_id ) :
					$user = get_userdata( $user_id );
					if ( ! $user ) {
						continue;
					}
					$avatar_url = get_avatar_url( $user_id, array( 'size' => 40 ) );
					?>
					<div class="avatar" title="<?php echo esc_attr( $user->display_name ); ?>">
						<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $user->display_name ); ?>">
					</div>
				<?php endforeach; ?>

				<?php if ( $hidden_count > 0 ) : ?>
					<div class="avatar-count">+<?php echo esc_html( $hidden_count ); ?></div>
				<?php endif; ?>

				<div class="interested-text" id="result"><?php echo esc_html( $result_count ); ?> interessado<?php echo $result_count !== 1 ? 's' : ''; ?></div>
			</div>
		</div>

		<!-- Info -->
		<section class="section">
			<div class="info-card">
				<div class="info-text">
					<?php the_content(); ?>
				</div>
			</div>

			<!-- Music Tags Marquee -->
			<?php if ( ! empty( $sounds ) ) : ?>
				<div class="music-tags-marquee">
					<div class="music-tags-track">
						<?php foreach ( $sounds as $sound ) : ?>
							<span class="music-tag"><?php echo esc_html( $sound ); ?></span>
						<?php endforeach; ?>
						<?php foreach ( $sounds as $sound ) : ?>
							<span class="music-tag"><?php echo esc_html( $sound ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</section>

		<!-- PROMO GALLERY (NEW SECTION) -->
		<?php if ( ! empty( $promo_images ) ) : ?>
		<section class="section">
			<h2 class="section-title"><i class="ri-image-line"></i> Galeria</h2>
			<div class="promo-gallery-slider">
				<div class="promo-track" id="promoTrack">
					<?php foreach ( $promo_images as $image_url ) : ?>
						<div class="promo-slide">
							<img src="<?php echo esc_url( $image_url ); ?>" alt="Promo">
						</div>
					<?php endforeach; ?>
				</div>

				<?php if ( count( $promo_images ) > 1 ) : ?>
				<div class="promo-controls">
					<button class="promo-prev"><i class="ri-arrow-left-s-line"></i></button>
					<button class="promo-next"><i class="ri-arrow-right-s-line"></i></button>
				</div>
				<?php endif; ?>
			</div>
		</section>
		<?php endif; ?>

		<!-- Line-up -->
		<section class="section" id="route_LINE">
			<h2 class="section-title"><i class="ri-disc-line"></i> Line-up</h2>
			<div class="lineup-list">
				<?php
				foreach ( $dj_slots as $slot ) :
					$dj = get_post( $slot['dj_id'] );
					if ( ! $dj ) {
						continue;
					}

					$dj_name      = $dj->post_title;
					$dj_permalink = get_permalink( $dj->ID );
					$dj_image     = get_the_post_thumbnail_url( $dj->ID, 'thumbnail' );
					?>
					<div class="lineup-card">
						<?php if ( $dj_image ) : ?>
							<img src="<?php echo esc_url( $dj_image ); ?>" alt="<?php echo esc_attr( $dj_name ); ?>" class="lineup-avatar-img">
						<?php else : ?>
							<div class="lineup-avatar-fallback"><?php echo esc_html( apollo_initials( $dj_name ) ); ?></div>
						<?php endif; ?>

						<div class="lineup-info">
							<h3 class="lineup-name">
								<a href="<?php echo esc_url( $dj_permalink ); ?>" target="_blank" class="dj-link"><?php echo esc_html( $dj_name ); ?></a>
							</h3>
							<?php if ( $slot['start'] && $slot['end'] ) : ?>
								<div class="lineup-time">
									<i class="ri-time-line"></i>
									<span><?php echo esc_html( $slot['start'] ); ?> - <?php echo esc_html( $slot['end'] ); ?></span>
								</div>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<!-- Route + Venue + Map -->
		<section class="section" id="route_ROUTE">
			<h2 class="section-title"><i class="ri-map-pin-line"></i> Local</h2>

			<!-- Venue Images Slider -->
			<?php if ( ! empty( $venue_images ) ) : ?>
			<div class="local-images-slider">
				<div class="local-images-track" id="localTrack">
					<?php foreach ( $venue_images as $image_url ) : ?>
						<div class="local-image">
							<img src="<?php echo esc_url( $image_url ); ?>" alt="Venue">
						</div>
					<?php endforeach; ?>
				</div>

				<?php if ( count( $venue_images ) > 1 ) : ?>
				<div class="slider-nav" id="localDots"></div>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<!-- Map -->
			<div class="map-view" id="mapView">
				<?php if ( $coords['lat'] && $coords['lng'] ) : ?>
					<div id="eventMap" style="width: 100%; height: 100%; border-radius: var(--radius-card);"></div>
				<?php else : ?>
					Mapa não disponível
				<?php endif; ?>
			</div>

			<!-- Route Input -->
			<div class="route-controls">
				<div class="route-input glass">
					<i class="ri-map-pin-line"></i>
					<input type="text" id="origin-input" placeholder="<?php esc_attr_e( 'Seu endereço de partida', 'apollo-events-manager' ); ?>">
				</div>

				<button id="route-btn" class="route-button">
					<i class="ri-send-plane-line"></i>
				</button>
			</div>
		</section>

		<!-- Tickets -->
		<section class="section" id="route_TICKETS">
			<h2 class="section-title">
				<i class="ri-ticket-2-line"></i> Acessos
			</h2>

			<div class="tickets-grid">
				<?php if ( $tickets_url ) : ?>
				<a href="<?php echo esc_url( $tickets_url ); ?>?ref=apollo.rio.br" class="ticket-card" target="_blank">
					<div class="ticket-icon"><i class="ri-ticket-line"></i></div>
					<div class="ticket-info">
						<h3 class="ticket-name"><span id="changingword">Biglietti</span></h3>
						<span class="ticket-cta">Acessar Bilheteria Digital →</span>
					</div>
				</a>
				<?php endif; ?>

				<!-- Apollo Coupon Detail -->
				<div class="apollo-coupon-detail">
					<i class="ri-coupon-3-line"></i>
					<span>Verifique se o cupom <strong>APOLLO</strong> está ativo com desconto</span>
					<button class="copy-code-mini" onclick="copyPromoCode()">
						<i class="ri-file-copy-fill"></i>
					</button>
				</div>

				<!-- Apollo Lista Amiga -->
				<?php if ( $guestlist_url ) : ?>
				<a href="<?php echo esc_url( $guestlist_url ); ?>" class="ticket-card" target="_blank">
					<div class="ticket-icon">
						<i class="ri-list-check"></i>
					</div>
					<div class="ticket-info">
						<h3 class="ticket-name">Lista Amiga</h3>
						<span class="ticket-cta">Ver Lista Amiga →</span>
					</div>
				</a>
				<?php else : ?>
				<div class="ticket-card disabled">
					<div class="ticket-icon">
						<i class="ri-list-check"></i>
					</div>
					<div class="ticket-info">
						<h3 class="ticket-name">Lista Amiga</h3>
						<span class="ticket-cta">Ver Lista Amiga →</span>
					</div>
				</div>
				<?php endif; ?>
			</div>
		</section>

		<!-- FINAL EVENT IMAGE (NEW) -->
		<?php if ( $final_image ) : ?>
		<section class="section">
			<div class="secondary-image">
				<img src="<?php echo esc_url( $final_image ); ?>" alt="Event Final">
			</div>
		</section>
		<?php endif; ?>

		<!-- Spacer for bottom bar -->
		<div style="height:120px;"></div>

	</div><!-- /event-body -->

	<!-- Bottom Bar -->
	<div class="bottom-bar">
		<a href="#route_TICKETS" class="bottom-btn primary" id="bottomTicketBtn">
			<i class="ri-ticket-fill"></i>
			<span id="changingword">Tickets</span>
		</a>

		<button class="bottom-btn secondary" id="bottomShareBtn">
			<i class="ri-share-forward-line"></i>
		</button>
	</div>

</div><!-- /mobile-container -->
