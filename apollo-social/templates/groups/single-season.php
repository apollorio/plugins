<?php
/**
 * Single Season Template
 * STRICT MODE: 100% UNI.CSS compliance
 * Season = Event series (e.g., "Carnaval 2025", "Verão 2025")
 *
 * @package Apollo_Social
 * @version 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Enqueue global assets
if ( function_exists( 'apollo_enqueue_global_assets' ) ) {
	apollo_enqueue_global_assets();
}
wp_enqueue_style( 'remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css', array(), '4.7.0' );

global $post;

// Get season data
$season_id   = get_the_ID();
$title       = get_the_title();
$content     = get_the_content();
$description = get_post_meta( $season_id, '_season_description', true );

// Meta data
$cover_url    = get_post_meta( $season_id, '_season_cover', true );
$start_date   = get_post_meta( $season_id, '_season_start_date', true );
$end_date     = get_post_meta( $season_id, '_season_end_date', true );
$events_count = (int) get_post_meta( $season_id, '_season_events_count', true );
$is_active    = (bool) get_post_meta( $season_id, '_season_is_active', true );
$theme_raw    = get_post_meta( $season_id, '_season_theme_color', true );
$theme_color  = ! empty( $theme_raw ) ? $theme_raw : 'orange';

// Date formatting
$start_obj  = $start_date ? DateTime::createFromFormat( 'Y-m-d', $start_date ) : null;
$end_obj    = $end_date ? DateTime::createFromFormat( 'Y-m-d', $end_date ) : null;
$date_range = '';
if ( $start_obj && $end_obj ) {
	$date_range = $start_obj->format( 'd M' ) . ' - ' . $end_obj->format( 'd M Y' );
} elseif ( $start_obj ) {
	$date_range = __( 'A partir de ', 'apollo-social' ) . $start_obj->format( 'd M Y' );
}

// Default cover
if ( ! $cover_url ) {
	$cover_url = 'https://assets.apollo.rio.br/covers/default-season.jpg';
}

get_header();
?>

<!-- STRICT MODE: Single Season View - UNI.CSS v5.2.0 -->
<div class="ap-page ap-bg-main">

	<!-- Hero Cover -->
	<div class="ap-hero ap-hero-cover ap-h-64 md:ap-h-80">
		<img
			src="<?php echo esc_url( $cover_url ); ?>"
			alt="<?php echo esc_attr( $title ); ?>"
			class="ap-hero-bg"
			data-ap-tooltip="<?php esc_attr_e( 'Capa da temporada', 'apollo-social' ); ?>"
		/>
		<div class="ap-hero-overlay ap-hero-overlay-gradient"></div>

		<!-- Back Button -->
		<a href="<?php echo esc_url( home_url( '/temporadas/' ) ); ?>"
			class="ap-btn ap-btn-icon ap-btn-glass ap-absolute ap-top-4 ap-left-4"
			data-ap-tooltip="<?php esc_attr_e( 'Voltar', 'apollo-social' ); ?>">
			<i class="ri-arrow-left-line"></i>
		</a>

		<!-- Status Badge -->
		<?php if ( $is_active ) : ?>
		<span class="ap-badge ap-badge-success ap-badge-pulse ap-absolute ap-top-4 ap-right-4"
				data-ap-tooltip="<?php esc_attr_e( 'Temporada ativa agora!', 'apollo-social' ); ?>">
			<i class="ri-live-line"></i>
			<?php esc_html_e( 'ATIVA', 'apollo-social' ); ?>
		</span>
		<?php endif; ?>

		<!-- Title Overlay -->
		<div class="ap-hero-content ap-absolute ap-bottom-0 ap-left-0 ap-right-0 ap-p-5">
			<h1 class="ap-heading-2xl ap-text-white"><?php echo esc_html( $title ); ?></h1>
			<?php if ( $date_range ) : ?>
			<p class="ap-text-white-80 ap-mt-1 ap-flex ap-items-center ap-gap-2"
				data-ap-tooltip="<?php esc_attr_e( 'Período da temporada', 'apollo-social' ); ?>">
				<i class="ri-calendar-2-line"></i>
				<?php echo esc_html( $date_range ); ?>
			</p>
			<?php endif; ?>
		</div>
	</div>

	<!-- Content -->
	<div class="ap-container ap-py-6 ap-space-y-4">

		<!-- About Card -->
		<div class="ap-card">
			<div class="ap-card-body">
				<?php if ( $description || $content ) : ?>
				<div class="ap-prose">
					<?php echo wp_kses_post( ! empty( $description ) ? $description : $content ); ?>
				</div>
				<?php else : ?>
				<p class="ap-text-muted ap-text-center ap-py-4"
					data-ap-tooltip="<?php esc_attr_e( 'Descrição da temporada', 'apollo-social' ); ?>">
					<?php esc_html_e( 'Uma seleção especial de eventos para você aproveitar.', 'apollo-social' ); ?>
				</p>
				<?php endif; ?>
			</div>
		</div>

		<!-- Events Counter -->
		<div class="ap-card ap-card-gradient ap-bg-<?php echo esc_attr( $theme_color ); ?>-gradient">
			<div class="ap-card-body ap-flex ap-items-center ap-justify-between ap-text-white">
				<div>
					<span class="ap-text-3xl ap-font-bold"><?php echo esc_html( $events_count ); ?></span>
					<span class="ap-text-white-80 ap-ml-2"><?php esc_html_e( 'eventos nesta temporada', 'apollo-social' ); ?></span>
				</div>
				<i class="ri-calendar-event-fill ap-text-4xl ap-opacity-30"></i>
			</div>
		</div>

		<!-- Events List -->
		<div class="ap-card">
			<div class="ap-card-header">
				<h2 class="ap-card-title ap-flex ap-items-center ap-gap-2"
					data-ap-tooltip="<?php esc_attr_e( 'Eventos desta temporada', 'apollo-social' ); ?>">
					<i class="ri-calendar-event-line ap-text-<?php echo esc_attr( $theme_color ); ?>-500"></i>
					<?php esc_html_e( 'Programação', 'apollo-social' ); ?>
				</h2>
			</div>
			<div class="ap-card-body">
				<?php
				$events = get_posts(
					array(
						'post_type'      => 'event_listing',
						'posts_per_page' => 10,
						'meta_query'     => array(
							array(
								'key'   => '_event_season_id',
								'value' => $season_id,
							),
						),
						'orderby'        => 'meta_value',
						'meta_key'       => '_event_start_date',
						'order'          => 'ASC',
					)
				);

				if ( ! empty( $events ) ) :
					?>
				<div class="ap-list ap-list-divided">
					<?php
					foreach ( $events as $event ) :
						$event_date = get_post_meta( $event->ID, '_event_start_date', true );
						$date_obj   = $event_date ? DateTime::createFromFormat( 'Y-m-d', $event_date ) : null;
						$venue      = get_post_meta( $event->ID, '_event_venue_name', true );
						$thumb      = get_the_post_thumbnail_url( $event->ID, 'thumbnail' );
						$day_names  = array( 'DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB' );
						?>
					<a href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>"
						class="ap-list-item ap-list-item-hover"
						data-ap-tooltip="<?php echo esc_attr( $event->post_title ); ?>">
						<div class="ap-avatar ap-avatar-md ap-avatar-square">
							<?php if ( $thumb ) : ?>
							<img src="<?php echo esc_url( $thumb ); ?>" alt="" />
							<?php elseif ( $date_obj ) : ?>
							<div class="ap-avatar-fallback ap-bg-<?php echo esc_attr( $theme_color ); ?>-100 ap-text-<?php echo esc_attr( $theme_color ); ?>-700 ap-flex-col">
								<span class="ap-text-[9px] ap-font-bold"><?php echo esc_html( $day_names[ (int) $date_obj->format( 'w' ) ] ); ?></span>
								<span class="ap-text-sm ap-font-bold"><?php echo esc_html( $date_obj->format( 'd' ) ); ?></span>
							</div>
							<?php else : ?>
							<div class="ap-avatar-fallback ap-bg-<?php echo esc_attr( $theme_color ); ?>-100">
								<i class="ri-music-2-line ap-text-<?php echo esc_attr( $theme_color ); ?>-500"></i>
							</div>
							<?php endif; ?>
						</div>
						<div class="ap-list-item-content">
							<h3 class="ap-list-item-title"><?php echo esc_html( $event->post_title ); ?></h3>
							<?php if ( $date_obj ) : ?>
							<p class="ap-list-item-meta">
								<i class="ri-calendar-line"></i>
								<?php echo esc_html( $date_obj->format( 'D, d M' ) ); ?>
							</p>
							<?php endif; ?>
							<?php if ( $venue ) : ?>
							<p class="ap-list-item-meta">
								<i class="ri-map-pin-line"></i>
								<?php echo esc_html( $venue ); ?>
							</p>
							<?php endif; ?>
						</div>
						<i class="ri-arrow-right-s-line ap-text-muted"></i>
					</a>
					<?php endforeach; ?>
				</div>
				<?php else : ?>
				<div class="ap-empty-state ap-py-8" data-ap-tooltip="<?php esc_attr_e( 'Programação em breve', 'apollo-social' ); ?>">
					<i class="ri-calendar-todo-line ap-empty-state-icon"></i>
					<p class="ap-empty-state-text"><?php esc_html_e( 'Programação em breve.', 'apollo-social' ); ?></p>
				</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Share Section -->
		<div class="ap-flex ap-gap-2">
			<button class="ap-btn ap-btn-primary ap-flex-1"
					data-ap-tooltip="<?php esc_attr_e( 'Receber notificações desta temporada', 'apollo-social' ); ?>">
				<i class="ri-notification-3-line"></i>
				<?php esc_html_e( 'Ativar Alertas', 'apollo-social' ); ?>
			</button>
			<button class="ap-btn ap-btn-outline"
					data-ap-tooltip="<?php esc_attr_e( 'Compartilhar temporada', 'apollo-social' ); ?>">
				<i class="ri-share-line"></i>
			</button>
		</div>

	</div>

</div>

<?php get_footer(); ?>
