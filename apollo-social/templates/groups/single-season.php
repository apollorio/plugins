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

<!-- ====================================================================
	[APP CONTAINER] Main Application Layout
	==================================================================== -->
<div class="app">
	<div class="app-container">

	<!-- ====================================================================
		[SIDEBAR] Apollo Social Navigation
		==================================================================== -->
	<?php get_template_part( 'partials/social-sidebar' ); ?>

	<!-- ====================================================================
		[MAIN CONTENT] Single Season
		==================================================================== -->
	<div class="main-content">
		<div class="content-wrapper">

		<!-- ====================================================================
			[HEADER] Season Header
			==================================================================== -->
		<header class="bg-white/90 border-b border-slate-200 px-4 py-3 md:px-6 md:py-4 sticky top-0 z-40 backdrop-blur-md">
		<div class="flex items-center justify-between">
			<div class="flex items-center gap-3">
			<button type="button" onclick="history.back()" class="h-8 w-8 rounded-full border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50 md:hidden">
				<i class="ri-arrow-left-line text-lg"></i>
			</button>

			<div class="h-9 w-9 rounded-lg bg-slate-900 flex items-center justify-center text-white">
				<i class="ri-calendar-event-line text-lg"></i>
			</div>

			<div class="flex flex-col leading-tight">
				<h1 class="text-[15px] font-extrabold text-slate-900"><?php echo esc_html( $title ); ?></h1>
				<p class="text-[12px] text-slate-500"><?php echo esc_html( $date_range ); ?> · <?php echo intval( $events_count ); ?> eventos</p>
			</div>
			</div>

			<div class="hidden md:flex items-center gap-3">
			<?php if ( $is_active ) : ?>
			<span class="px-3 py-1 bg-green-100 text-green-700 text-sm font-medium rounded-full">
				<i class="ri-live-line mr-1"></i>Ativa
			</span>
			<?php endif; ?>
			</div>
		</div>
		</header>

		<!-- ====================================================================
			[CONTENT] Season Events
			==================================================================== -->
		<main class="flex-1 px-4 md:px-6 py-4 md:py-6 pb-24 md:pb-8">
		<div class="w-full max-w-4xl mx-auto">

		<div class="w-full max-w-4xl mx-auto">
			<!-- Season Description -->
			<div class="bg-white/95 border border-slate-200 rounded-2xl px-4 py-4 mb-4">
			<h2 class="text-[14px] font-semibold text-slate-900 mb-2">Sobre a temporada</h2>
			<?php if ( $description || $content ) : ?>
			<div class="text-[13px] text-slate-600 leading-relaxed">
				<?php echo wp_kses_post( ! empty( $description ) ? $description : $content ); ?>
			</div>
			<?php else : ?>
			<p class="text-[13px] text-slate-500 text-center py-4">
				Uma seleção especial de eventos para você aproveitar.
			</p>
			<?php endif; ?>
			</div>

			<!-- Events List -->
			<div class="bg-white/95 border border-slate-200 rounded-2xl px-4 py-4">
			<h2 class="text-[14px] font-semibold text-slate-900 mb-4 flex items-center gap-2">
				<i class="ri-calendar-event-line text-slate-500"></i>
				Programação (<?php echo intval( $events_count ); ?> eventos)
			</h2>

			<?php
			$events = get_posts(
				array(
					'post_type'      => 'event_listing',
					'posts_per_page' => 20,
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
			<div class="space-y-3">
				<?php
				foreach ( $events as $event ) :
					$event_date = get_post_meta( $event->ID, '_event_start_date', true );
					$date_obj   = $event_date ? DateTime::createFromFormat( 'Y-m-d', $event_date ) : null;
					$venue      = get_post_meta( $event->ID, '_event_venue_name', true );
					$thumb      = get_the_post_thumbnail_url( $event->ID, 'thumbnail' );
					?>
				<a href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 transition-colors">
				<div class="h-12 w-12 rounded-lg overflow-hidden bg-slate-100 flex-shrink-0">
					<?php if ( $thumb ) : ?>
					<img src="<?php echo esc_url( $thumb ); ?>" alt="" class="h-full w-full object-cover">
					<?php elseif ( $date_obj ) : ?>
					<div class="h-full w-full bg-slate-900 text-white flex flex-col items-center justify-center text-xs font-bold">
					<span><?php echo esc_html( $date_obj->format( 'M' ) ); ?></span>
					<span><?php echo esc_html( $date_obj->format( 'd' ) ); ?></span>
					</div>
					<?php else : ?>
					<div class="h-full w-full flex items-center justify-center text-slate-400">
					<i class="ri-music-2-line"></i>
					</div>
					<?php endif; ?>
				</div>
				<div class="flex-1 min-w-0">
					<h3 class="text-[13px] font-semibold text-slate-900 truncate"><?php echo esc_html( $event->post_title ); ?></h3>
					<div class="flex items-center gap-3 text-[11px] text-slate-500 mt-1">
					<?php if ( $date_obj ) : ?>
					<span class="flex items-center gap-1">
						<i class="ri-calendar-line"></i>
						<?php echo esc_html( $date_obj->format( 'd/m' ) ); ?>
					</span>
					<?php endif; ?>
					<?php if ( $venue ) : ?>
					<span class="flex items-center gap-1">
						<i class="ri-map-pin-line"></i>
						<?php echo esc_html( $venue ); ?>
					</span>
					<?php endif; ?>
					</div>
				</div>
				<i class="ri-arrow-right-s-line text-slate-400"></i>
				</a>
				<?php endforeach; ?>
			</div>
			<?php else : ?>
			<div class="text-center py-8">
				<i class="ri-calendar-todo-line text-4xl text-slate-300 mb-3"></i>
				<p class="text-slate-500">Programação em breve.</p>
			</div>
			<?php endif; ?>
			</div>
		</div>
		</main>

		<!-- ====================================================================
			[MOBILE NAV] Bottom Navigation for Mobile
			==================================================================== -->
		<?php get_template_part( 'partials/social-bottom-bar' ); ?>

	</div>
	</div>
</div>

<?php get_footer(); ?>
