<?php
/**
 * Groups Directory Template
 * STRICT MODE: 100% UNI.CSS compliance
 * Displays groups listings (/comunidades/, /nucleos/, /temporadas/)
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

// Determine group type from URL
$current_url = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
$group_type  = 'comunidade';
$page_title  = __( 'Comunidades', 'apollo-social' );
$page_icon   = 'ri-group-line';

if ( strpos( $current_url, 'nucleo' ) !== false ) {
	$group_type = 'nucleo';
	$page_title = __( 'Núcleos', 'apollo-social' );
	$page_icon  = 'ri-fire-line';
} elseif ( strpos( $current_url, 'temporada' ) !== false || strpos( $current_url, 'season' ) !== false ) {
	$group_type = 'season';
	$page_title = __( 'Temporadas', 'apollo-social' );
	$page_icon  = 'ri-calendar-event-line';
}

// Query groups
$paged        = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
$search_query = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

$args = array(
	'post_type'      => 'apollo_group',
	'post_status'    => 'publish',
	'posts_per_page' => 12,
	'paged'          => $paged,
	'orderby'        => 'meta_value_num',
	'meta_key'       => '_group_members_count',
	'order'          => 'DESC',
	'meta_query'     => array(
		array(
			'key'     => '_group_type',
			'value'   => $group_type,
			'compare' => '=',
		),
	),
);

if ( $search_query ) {
	$args['s'] = $search_query;
}

$groups = new WP_Query( $args );

// Color schemes per type
$type_colors  = array(
	'comunidade' => 'purple',
	'nucleo'     => 'orange',
	'season'     => 'blue',
);
$accent_color = $type_colors[ $group_type ] ?? 'purple';

get_header();
?>

<!-- STRICT MODE: Groups Directory - UNI.CSS v5.2.0 -->
<div class="ap-page ap-bg-main" data-ap-tooltip="<?php esc_attr_e( 'Diretório de grupos', 'apollo-social' ); ?>">

	<!-- Header -->
	<header class="ap-topbar ap-sticky">
		<div class="ap-container">
			<div class="ap-flex ap-items-center ap-justify-between">
				<div class="ap-flex ap-items-center ap-gap-3">
					<a href="<?php echo esc_url( home_url( '/mural/' ) ); ?>" 
						class="ap-btn ap-btn-icon ap-btn-ghost" 
						data-ap-tooltip="<?php esc_attr_e( 'Voltar ao feed', 'apollo-social' ); ?>">
						<i class="ri-arrow-left-line"></i>
					</a>
					<div class="ap-flex ap-items-center ap-gap-2">
						<div class="ap-avatar ap-avatar-sm ap-bg-<?php echo esc_attr( $accent_color ); ?>">
							<i class="<?php echo esc_attr( $page_icon ); ?>"></i>
						</div>
						<h1 class="ap-heading-lg"><?php echo esc_html( $page_title ); ?></h1>
					</div>
				</div>
				
				<?php if ( $group_type !== 'season' ) : ?>
				<a href="<?php echo esc_url( home_url( '/criar-' . $group_type . '/' ) ); ?>" 
					class="ap-btn ap-btn-primary ap-btn-sm"
					data-ap-tooltip="<?php esc_attr_e( 'Criar novo grupo', 'apollo-social' ); ?>">
					<i class="ri-add-line"></i>
					<?php esc_html_e( 'Criar', 'apollo-social' ); ?>
				</a>
				<?php endif; ?>
			</div>
			
			<!-- Search Bar -->
			<form method="get" class="ap-mt-4">
				<div class="ap-input-group">
					<i class="ri-search-line ap-input-icon"></i>
					<input 
						type="text" 
						name="q" 
						value="<?php echo esc_attr( $search_query ); ?>"
						placeholder="<?php echo esc_attr( sprintf( __( 'Buscar %s...', 'apollo-social' ), strtolower( $page_title ) ) ); ?>"
						class="ap-form-input ap-input-icon-left"
						data-ap-tooltip="<?php esc_attr_e( 'Digite para buscar', 'apollo-social' ); ?>"
					/>
				</div>
			</form>
		</div>
	</header>

	<!-- Type Tabs -->
	<nav class="ap-tabs ap-tabs-pills ap-px-4 ap-py-3 ap-border-b" data-ap-tooltip="<?php esc_attr_e( 'Tipos de grupo', 'apollo-social' ); ?>">
		<div class="ap-container">
			<div class="ap-flex ap-gap-2 ap-overflow-x-auto ap-no-scrollbar">
				<a href="<?php echo esc_url( home_url( '/comunidades/' ) ); ?>" 
					class="ap-tab <?php echo $group_type === 'comunidade' ? 'ap-tab-active ap-bg-purple-100 ap-text-purple-700' : ''; ?>"
					data-ap-tooltip="<?php esc_attr_e( 'Comunidades de interesse', 'apollo-social' ); ?>">
					<i class="ri-group-line"></i>
					<?php esc_html_e( 'Comunidades', 'apollo-social' ); ?>
				</a>
				<a href="<?php echo esc_url( home_url( '/nucleos/' ) ); ?>" 
					class="ap-tab <?php echo $group_type === 'nucleo' ? 'ap-tab-active ap-bg-orange-100 ap-text-orange-700' : ''; ?>"
					data-ap-tooltip="<?php esc_attr_e( 'Coletivos e produtoras', 'apollo-social' ); ?>">
					<i class="ri-fire-line"></i>
					<?php esc_html_e( 'Núcleos', 'apollo-social' ); ?>
				</a>
				<a href="<?php echo esc_url( home_url( '/temporadas/' ) ); ?>" 
					class="ap-tab <?php echo $group_type === 'season' ? 'ap-tab-active ap-bg-blue-100 ap-text-blue-700' : ''; ?>"
					data-ap-tooltip="<?php esc_attr_e( 'Séries de eventos', 'apollo-social' ); ?>">
					<i class="ri-calendar-event-line"></i>
					<?php esc_html_e( 'Temporadas', 'apollo-social' ); ?>
				</a>
			</div>
		</div>
	</nav>

	<!-- Groups Grid -->
	<main class="ap-container ap-py-6">
		
		<?php if ( $groups->have_posts() ) : ?>
		<div class="ap-grid ap-grid-3 ap-gap-4">
			
			<?php
			while ( $groups->have_posts() ) :
				$groups->the_post();
				$group_id    = get_the_ID();
				$members     = (int) get_post_meta( $group_id, '_group_members_count', true );
				$events      = (int) get_post_meta( $group_id, '_group_events_count', true );
				$avatar      = get_post_meta( $group_id, '_group_avatar', true );
				$cover       = get_post_meta( $group_id, '_group_cover', true );
				$is_verified = (bool) get_post_meta( $group_id, '_group_verified', true );
				?>
			<a href="<?php the_permalink(); ?>" 
				class="ap-card ap-card-hover"
				data-ap-tooltip="<?php echo esc_attr( get_the_title() ); ?>">
				
				<!-- Cover -->
				<div class="ap-card-cover ap-h-24">
					<?php if ( $cover ) : ?>
					<img src="<?php echo esc_url( $cover ); ?>" 
						alt="" 
						class="ap-card-cover-img" 
						loading="lazy" />
					<?php else : ?>
					<div class="ap-card-cover-gradient ap-bg-<?php echo esc_attr( $accent_color ); ?>-gradient"></div>
					<?php endif; ?>
				</div>
				
				<!-- Info -->
				<div class="ap-card-body ap-pt-0" style="margin-top: -2rem;">
					<div class="ap-flex ap-items-start ap-gap-3">
						<!-- Avatar -->
						<div class="ap-avatar ap-avatar-lg ap-border-white ap-shadow-md">
							<?php if ( $avatar ) : ?>
							<img src="<?php echo esc_url( $avatar ); ?>" alt="" />
							<?php else : ?>
							<div class="ap-avatar-fallback ap-bg-<?php echo esc_attr( $accent_color ); ?>">
								<i class="<?php echo esc_attr( $page_icon ); ?>"></i>
							</div>
							<?php endif; ?>
						</div>
						
						<div class="ap-flex-1 ap-min-w-0 ap-pt-8">
							<div class="ap-flex ap-items-center ap-gap-1">
								<h3 class="ap-card-title ap-truncate"><?php the_title(); ?></h3>
								<?php if ( $is_verified ) : ?>
								<i class="ri-verified-badge-fill ap-text-blue-500" 
									data-ap-tooltip="<?php esc_attr_e( 'Verificado', 'apollo-social' ); ?>"></i>
								<?php endif; ?>
							</div>
							<div class="ap-flex ap-items-center ap-gap-3 ap-text-xs ap-text-muted ap-mt-1">
								<span data-ap-tooltip="<?php esc_attr_e( 'Membros', 'apollo-social' ); ?>">
									<?php echo esc_html( number_format_i18n( $members ) ); ?> <?php esc_html_e( 'membros', 'apollo-social' ); ?>
								</span>
								<span data-ap-tooltip="<?php esc_attr_e( 'Eventos', 'apollo-social' ); ?>">
									<?php echo esc_html( $events ); ?> <?php esc_html_e( 'eventos', 'apollo-social' ); ?>
								</span>
							</div>
						</div>
					</div>
				</div>
			</a>
			<?php endwhile; ?>
			
		</div>
		
		<!-- Pagination -->
			<?php if ( $groups->max_num_pages > 1 ) : ?>
		<nav class="ap-pagination ap-mt-8" data-ap-tooltip="<?php esc_attr_e( 'Navegação de páginas', 'apollo-social' ); ?>">
				<?php
				echo paginate_links(
					array(
						'total'     => $groups->max_num_pages,
						'current'   => $paged,
						'prev_text' => '<i class="ri-arrow-left-s-line"></i>',
						'next_text' => '<i class="ri-arrow-right-s-line"></i>',
					)
				);
				?>
		</nav>
		<?php endif; ?>
		
			<?php wp_reset_postdata(); ?>
		
		<?php else : ?>
		
		<!-- Empty State -->
		<div class="ap-card ap-text-center ap-py-12">
			<div class="ap-avatar ap-avatar-xl ap-mx-auto ap-mb-4 ap-bg-<?php echo esc_attr( $accent_color ); ?>-100">
				<i class="<?php echo esc_attr( $page_icon ); ?> ap-text-3xl ap-text-<?php echo esc_attr( $accent_color ); ?>-500"></i>
			</div>
			<h2 class="ap-heading-lg ap-mb-2" data-ap-tooltip="<?php esc_attr_e( 'Nenhum resultado', 'apollo-social' ); ?>">
				<?php esc_html_e( 'Nenhum resultado encontrado', 'apollo-social' ); ?>
			</h2>
			<p class="ap-text-muted ap-mb-4">
				<?php if ( $search_query ) : ?>
					<?php echo esc_html( sprintf( __( 'Não encontramos %1$s para "%2$s".', 'apollo-social' ), strtolower( $page_title ), $search_query ) ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Seja o primeiro a criar!', 'apollo-social' ); ?>
				<?php endif; ?>
			</p>
			<?php if ( $group_type !== 'season' ) : ?>
			<a href="<?php echo esc_url( home_url( '/criar-' . $group_type . '/' ) ); ?>" 
				class="ap-btn ap-btn-primary"
				data-ap-tooltip="<?php esc_attr_e( 'Criar novo grupo', 'apollo-social' ); ?>">
				<i class="ri-add-line"></i>
				<?php echo esc_html( sprintf( __( 'Criar %s', 'apollo-social' ), rtrim( $page_title, 's' ) ) ); ?>
			</a>
			<?php endif; ?>
		</div>
		
		<?php endif; ?>
		
	</main>

</div>

<?php get_footer(); ?>
