<?php
/**
 * Template: Private Profile Page (/meu-perfil/)
 *
 * Design Reference: PAGE-PRIVATE-PROFILE-PAGE-TAB stylesheet by tailwind
 * Auto-detects logged-in user and shows private dashboard
 *
 * Tabs: Events | Metrics | Nucleo | Communities | Docs
 * FORCED TOOLTIPS: All placeholder fields show data-tooltip when empty
 *
 * @package Apollo_Social
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Require login
if ( ! is_user_logged_in() ) {
	wp_redirect( wp_login_url( home_url( '/meu-perfil/' ) ) );
	exit;
}

$current_user = wp_get_current_user();
$user_id      = $current_user->ID;

// ============================================
// USER DATA EXTRACTION
// ============================================

$display_name = $current_user->display_name;
$user_email   = $current_user->user_email;
$user_login   = $current_user->user_login;

// Profile meta
$user_bio          = get_user_meta( $user_id, 'description', true );
$user_avatar       = get_avatar_url( $user_id, array( 'size' => 160 ) );
$user_location     = get_user_meta( $user_id, '_apollo_location', true );
$user_role_display = get_user_meta( $user_id, '_apollo_role_display', true );
$industry_access   = get_user_meta( $user_id, '_apollo_industry_access', true );

// Stats
$stats = array(
	'producer'  => (int) get_user_meta( $user_id, '_apollo_producer_count', true ),
	'favorited' => (int) get_user_meta( $user_id, '_apollo_favorited_count', true ),
	'posts'     => (int) count_user_posts( $user_id ),
	'comments'  => (int) get_user_meta( $user_id, '_apollo_comment_count', true ),
	'liked'     => (int) get_user_meta( $user_id, '_apollo_liked_count', true ),
);

// Memberships (roles/badges)
$memberships = get_user_meta( $user_id, '_apollo_memberships', true );
if ( ! is_array( $memberships ) ) {
	$memberships = array( 'Member' );
}

// User's nucleos (private groups)
$user_nucleos = get_user_meta( $user_id, '_apollo_nucleos', true );
if ( ! is_array( $user_nucleos ) ) {
	$user_nucleos = array();
}

// User's communities
$user_communities = get_user_meta( $user_id, '_apollo_communities', true );
if ( ! is_array( $user_communities ) ) {
	$user_communities = array();
}

// Favorited events
$favorited_events = get_user_meta( $user_id, '_apollo_favorited_events', true );
if ( ! is_array( $favorited_events ) ) {
	$favorited_events = array();
}

// Pending documents
$pending_docs = get_user_meta( $user_id, '_apollo_pending_docs', true );
if ( ! is_array( $pending_docs ) ) {
	$pending_docs = array();
}

// Public page URL
$public_page_url = home_url( '/id/' . $user_login );

// Count nucleos and communities
$nucleo_count    = count( $user_nucleos );
$community_count = count( $user_communities );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="h-full bg-white">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Meu Perfil | Apollo</title>
	
	<!-- Tailwind (for layout shell only) -->
	<script src="https://cdn.tailwindcss.com"></script>
	
	<!-- UNI.CSS -->
	<link rel="stylesheet" href="https://assets.apollo.rio.br/uni.css">
	
	<!-- RemixIcon -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css">
	
	<!-- Motion.dev -->
	<script src="https://unpkg.com/@motionone/dom/dist/motion-one.umd.js"></script>
	
	<?php wp_head(); ?>
</head>
<body class="h-full bg-slate-50 text-slate-900">

<section class="aprioEXP-body">
	<div class="min-h-screen flex flex-col">
		
		<!-- ========================== -->
		<!-- TOP HEADER                 -->
		<!-- ========================== -->
		<header class="h-14 flex items-center justify-between border-b bg-white/80 backdrop-blur px-3 md:px-6">
			<div class="flex items-center gap-3">
				<a href="<?php echo esc_url( home_url() ); ?>" class="inline-flex h-8 w-8 items-center justify-center menutags bg-slate-900 text-white" data-tooltip="Voltar ao início">
					<i class="ri-slack-line text-[18px]"></i>
				</a>
				<div class="flex flex-col">
					<span class="text-[10px] uppercase tracking-[0.12em] text-slate-400">Rede Social Cultural Carioca</span>
					<span class="text-sm font-semibold">@<?php echo esc_html( $user_login ); ?> · Apollo::rio</span>
				</div>
			</div>
			<div class="flex items-center gap-2 text-[11px]">
				<a href="<?php echo esc_url( $public_page_url ); ?>" 
					class="hidden md:inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2.5 py-1.5 font-medium text-slate-700 hover:bg-slate-50"
					target="_blank" data-tooltip="Ver sua página pública">
					<i class="ri-eye-line text-xs"></i>
					<span>Ver como visitante</span>
				</a>
				<a href="<?php echo esc_url( $public_page_url ); ?>" 
					class="inline-flex items-center gap-1 rounded-md bg-slate-900 px-3 py-1.5 font-medium text-white"
					target="_blank">
					<i class="ri-external-link-line text-xs"></i>
					<span>Abrir página pública</span>
				</a>
				<button class="inline-flex h-8 w-8 items-center justify-center rounded-full hover:bg-slate-100">
					<div class="h-7 w-7 overflow-hidden rounded-full bg-slate-200">
						<img src="<?php echo esc_url( $user_avatar ); ?>" 
							alt="<?php echo esc_attr( $display_name ); ?>" 
							class="h-full w-full object-cover">
					</div>
				</button>
			</div>
		</header>
		
		<!-- ========================== -->
		<!-- MAIN CONTENT               -->
		<!-- ========================== -->
		<main class="flex-1 flex justify-center px-3 md:px-6 py-4 md:py-6">
			<div class="w-full max-w-6xl grid lg:grid-cols-[minmax(0,2.5fr)_minmax(0,1fr)] gap-4">
				
				<!-- LEFT COLUMN: Profile + Tabs -->
				<div class="space-y-4">
					
					<!-- ========================== -->
					<!-- PROFILE CARD               -->
					<!-- ========================== -->
					<section class="aprioEXP-card-shell p-4 md:p-5">
						<div class="aprioEXP-profile-header-row flex flex-col md:flex-row gap-4">
							
							<!-- USER DATA SECTION -->
							<div class="aprioEXP-user-data-section flex items-start gap-4 flex-1">
								<!-- Avatar -->
								<div class="relative shrink-0">
									<div class="h-16 w-16 md:h-20 md:w-20 overflow-hidden rounded-full bg-gradient-to-tr from-orange-500 via-rose-500 to-amber-400 aspect-square">
										<img src="<?php echo esc_url( $user_avatar ); ?>" 
											alt="Avatar" 
											class="h-full w-full object-cover mix-blend-luminosity">
									</div>
									<!-- Badge -->
									<?php if ( $industry_access ) : ?>
									<span class="absolute bottom-0 right-0 inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 text-[11px] text-white ring-2 ring-white z-10" data-tooltip="Industry Access">
										<i class="ri-flashlight-fill"></i>
									</span>
									<?php endif; ?>
								</div>
								
								<!-- User Info -->
								<div class="min-w-0 flex-1">
									<div class="flex flex-wrap items-center gap-2">
										<h1 class="truncate text-base md:text-lg font-semibold"><?php echo esc_html( $display_name ); ?></h1>
										<?php if ( $user_role_display ) : ?>
										<span class="aprioEXP-metric-chip">
											<i class="ri-music-2-line text-xs"></i>
											<span><?php echo esc_html( $user_role_display ); ?></span>
										</span>
										<?php else : ?>
										<span class="aprioEXP-metric-chip dj-placeholder" data-tooltip="Adicione seu role no perfil">
											<i class="ri-user-line text-xs"></i>
											<span>Membro</span>
										</span>
										<?php endif; ?>
									</div>
									
									<p class="mt-1 text-[11px] md:text-[12px] text-slate-600 line-clamp-2" data-tooltip="<?php echo empty( $user_bio ) ? 'Adicione uma bio no seu perfil' : ''; ?>">
										<?php echo esc_html( $user_bio ?: 'Nenhuma descrição adicionada.' ); ?>
									</p>
									
									<div class="mt-2 flex flex-wrap gap-2 text-[10px] md:text-[11px] text-slate-500">
										<span class="aprioEXP-metric-chip" data-tooltip="<?php echo empty( $user_location ) ? 'Adicione sua localização' : ''; ?>">
											<i class="ri-map-pin-line text-xs"></i>
											<?php echo esc_html( $user_location ?: 'Localização não definida' ); ?>
										</span>
										<?php if ( $industry_access ) : ?>
										<span class="aprioEXP-metric-chip">
											<i class="ri-vip-crown-2-line text-xs"></i>
											Industry access
										</span>
										<?php endif; ?>
										<span class="aprioEXP-metric-chip">
											<i class="ri-group-line text-xs"></i>
											<?php echo esc_html( $nucleo_count ); ?> núcleos · <?php echo esc_html( $community_count ); ?> comunidades
										</span>
									</div>
								</div>
							</div>
							
							<!-- STATS SECTION -->
							<div class="aprioEXP-stats-section">
								<div class="aprioEXP-cards-container grid grid-cols-3 md:grid-cols-6 gap-2">
									<div class="aprioEXP-stat-card text-center p-2 bg-slate-50 rounded">
										<span class="aprioEXP-card-numbers-title text-[10px] text-slate-500">Producer</span>
										<span class="aprioEXP-card-numbers-numbers text-lg font-bold"><?php echo esc_html( $stats['producer'] ); ?></span>
									</div>
									<div class="aprioEXP-stat-card text-center p-2 bg-slate-50 rounded">
										<span class="aprioEXP-card-numbers-title text-[10px] text-slate-500">Favoritado</span>
										<span class="aprioEXP-card-numbers-numbers text-lg font-bold"><?php echo esc_html( $stats['favorited'] ); ?></span>
									</div>
									<div class="aprioEXP-stat-card text-center p-2 bg-slate-50 rounded">
										<span class="aprioEXP-card-numbers-title text-[10px] text-slate-500">Posts</span>
										<span class="aprioEXP-card-numbers-numbers text-lg font-bold"><?php echo esc_html( $stats['posts'] ); ?></span>
									</div>
									<div class="aprioEXP-stat-card text-center p-2 bg-slate-50 rounded">
										<span class="aprioEXP-card-numbers-title text-[10px] text-slate-500">Comments</span>
										<span class="aprioEXP-card-numbers-numbers text-lg font-bold"><?php echo esc_html( $stats['comments'] ); ?></span>
									</div>
									<div class="aprioEXP-stat-card text-center p-2 bg-slate-50 rounded">
										<span class="aprioEXP-card-numbers-title text-[10px] text-slate-500">Liked</span>
										<span class="aprioEXP-card-numbers-numbers text-lg font-bold"><?php echo esc_html( $stats['liked'] ); ?></span>
									</div>
									<div class="aprioEXP-stat-card text-center p-2 bg-slate-50 rounded">
										<span class="aprioEXP-card-numbers-title text-[10px] text-slate-500">Memberships</span>
										<span class="aprioEXP-card-numbers-listing text-[10px]">
											<?php echo esc_html( implode( ' · ', array_slice( $memberships, 0, 2 ) ) ); ?>
										</span>
									</div>
								</div>
								<a href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>" class="aprioEXP-edit-btn inline-flex items-center gap-1 mt-3 px-3 py-1.5 text-[11px] font-medium rounded-md border border-slate-200 hover:bg-slate-50">
									<i class="ri-pencil-line text-xs"></i>
									<span>Editar perfil interno</span>
								</a>
							</div>
						</div>
					</section>
					
					<!-- ========================== -->
					<!-- TABS + CONTENT             -->
					<!-- ========================== -->
					<section class="aprioEXP-card-shell p-3 md:p-4">
						
						<!-- Tabs Header -->
						<div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-2">
							<div class="flex flex-wrap gap-1 md:gap-2" role="tablist" aria-label="Navegação do perfil">
								
								<!-- Tab 1: Eventos favoritos -->
								<button class="aprioEXP-tab-btn px-3 py-1.5 text-[11px] font-medium rounded-md transition-colors" 
										type="button" data-tab-target="events" data-active="true" role="tab" aria-selected="true">
									<i class="ri-heart-3-line text-[13px]"></i>
									<span>Eventos favoritos</span>
								</button>
								
								<!-- Tab 2: Meus números -->
								<button class="aprioEXP-tab-btn px-3 py-1.5 text-[11px] font-medium rounded-md transition-colors" 
										type="button" data-tab-target="metrics" role="tab" aria-selected="false">
									<i class="ri-bar-chart-2-line text-[13px]"></i>
									<span>Meus números</span>
								</button>
								
								<!-- Tab 3: Núcleo (privado) -->
								<button class="aprioEXP-tab-btn px-3 py-1.5 text-[11px] font-medium rounded-md transition-colors" 
										type="button" data-tab-target="nucleo" role="tab" aria-selected="false">
									<i class="ri-lock-2-line text-[13px]"></i>
									<span>Núcleo (privado)</span>
								</button>
								
								<!-- Tab 4: Comunidades -->
								<button class="aprioEXP-tab-btn px-3 py-1.5 text-[11px] font-medium rounded-md transition-colors" 
										type="button" data-tab-target="communities" role="tab" aria-selected="false">
									<i class="ri-community-line text-[13px]"></i>
									<span>Comunidades</span>
								</button>
								
								<!-- Tab 5: Documentos -->
								<button class="aprioEXP-tab-btn px-3 py-1.5 text-[11px] font-medium rounded-md transition-colors" 
										type="button" data-tab-target="docs" role="tab" aria-selected="false">
									<i class="ri-file-text-line text-[13px]"></i>
									<span>Documentos</span>
								</button>
							</div>
							
							<div class="flex items-center gap-2 text-[11px] text-slate-500">
								<span class="hidden sm:inline">Fluxo interno Apollo Social</span>
								<span class="h-4 w-px bg-slate-200"></span>
								<span class="aprioEXP-metric-chip">
									<i class="ri-shining-fill text-[12px]"></i>
									<span>Motion tabs</span>
								</span>
							</div>
						</div>
						
						<!-- Tabs Content -->
						<div class="mt-3 space-y-4">
							
							<!-- TAB: Eventos favoritos -->
							<div data-tab-panel="events" role="tabpanel" class="space-y-3">
								<div class="flex flex-col md:flex-row md:items-center gap-3">
									<div class="flex-1">
										<h2 class="text-sm font-semibold">Eventos favoritados</h2>
										<p class="text-[12px] text-slate-600">
											Eventos que você marcou como <b>Ir</b>, <b>Talvez</b> ou salvou para acompanhar.
										</p>
									</div>
									<button class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-medium text-slate-700 hover:bg-slate-50">
										<i class="ri-filter-3-line text-xs"></i>
										<span>Filtrar por data</span>
									</button>
								</div>
								
								<?php if ( ! empty( $favorited_events ) ) : ?>
								<div class="grid gap-3 md:grid-cols-2 text-[12px]">
									<?php foreach ( $favorited_events as $event_data ) : ?>
									<article class="aprioEXP-card-shell p-3 flex flex-col justify-between bg-white border border-slate-200 rounded-lg">
										<div class="flex items-start justify-between gap-2">
											<div>
												<h3 class="text-sm font-semibold"><?php echo esc_html( $event_data['title'] ?? 'Evento' ); ?></h3>
												<p class="text-[11px] text-slate-600"><?php echo esc_html( $event_data['location'] ?? '' ); ?> · <?php echo esc_html( $event_data['time'] ?? '' ); ?></p>
											</div>
											<div class="flex flex-col items-end text-[11px]">
												<span class="rounded-md bg-slate-900 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-white">
													<?php echo esc_html( $event_data['status'] ?? 'Ir' ); ?>
												</span>
											</div>
										</div>
										<div class="mt-3 flex items-center justify-between text-[11px] text-slate-500">
											<a href="<?php echo esc_url( $event_data['url'] ?? '#' ); ?>" class="inline-flex items-center gap-1 rounded-md px-2 py-1 hover:bg-slate-100">
												<i class="ri-external-link-line text-xs"></i>
												<span>Ver evento</span>
											</a>
										</div>
									</article>
									<?php endforeach; ?>
								</div>
								<?php else : ?>
								<div class="flex items-center justify-center h-32 text-slate-400 text-sm bg-slate-50 rounded-lg" data-tooltip="Favorite eventos para vê-los aqui">
									<p><i class="ri-heart-line mr-2"></i>Nenhum evento favoritado ainda.</p>
								</div>
								<?php endif; ?>
							</div>
							
							<!-- TAB: Meus números -->
							<div data-tab-panel="metrics" role="tabpanel" class="hidden space-y-3">
								<div class="flex items-center justify-center h-32 text-slate-400 text-sm bg-slate-50 rounded-lg" data-tooltip="Dados sendo calculados">
									<p><i class="ri-bar-chart-line mr-2"></i>Dados de performance sendo calculados...</p>
								</div>
							</div>
							
							<!-- TAB: Núcleo (privado) -->
							<div data-tab-panel="nucleo" role="tabpanel" class="hidden space-y-3">
								<div class="flex flex-col md:flex-row md:items-center gap-3">
									<div class="flex-1">
										<h2 class="text-sm font-semibold">Núcleos privados</h2>
										<p class="text-[12px] text-slate-600">
											Espaços de trabalho e coordenação fechados, visíveis apenas para quem tem convite.
										</p>
									</div>
									<div class="flex flex-wrap gap-2 text-[11px]">
										<button class="inline-flex items-center gap-1 rounded-md bg-slate-900 px-3 py-1.5 font-medium text-white">
											<i class="ri-team-line text-xs"></i>
											<span>Criar novo núcleo</span>
										</button>
									</div>
								</div>
								
								<?php if ( ! empty( $user_nucleos ) ) : ?>
								<div class="grid gap-3 md:grid-cols-2 text-[12px]">
									<?php foreach ( $user_nucleos as $nucleo ) : ?>
									<article class="aprioEXP-card-shell p-3 flex flex-col justify-between bg-white border border-slate-200 rounded-lg">
										<div class="flex items-start justify-between gap-2">
											<div>
												<div class="flex items-center gap-2 mb-1">
													<h3 class="text-sm font-semibold"><?php echo esc_html( $nucleo['name'] ?? 'Núcleo' ); ?></h3>
													<span class="aprioEXP-badge-private inline-flex items-center gap-1 px-2 py-0.5 text-[10px] bg-slate-100 rounded">
														<i class="ri-lock-2-line text-[11px]"></i> Privado
													</span>
												</div>
												<p class="text-slate-600 text-[11px] line-clamp-2"><?php echo esc_html( $nucleo['description'] ?? '' ); ?></p>
											</div>
										</div>
									</article>
									<?php endforeach; ?>
								</div>
								<?php else : ?>
								<div class="flex items-center justify-center h-32 text-slate-400 text-sm bg-slate-50 rounded-lg" data-tooltip="Crie ou entre em um núcleo">
									<p><i class="ri-lock-line mr-2"></i>Nenhum núcleo privado ainda.</p>
								</div>
								<?php endif; ?>
							</div>
							
							<!-- TAB: Comunidades -->
							<div data-tab-panel="communities" role="tabpanel" class="hidden space-y-3">
								<div class="flex flex-col md:flex-row md:items-center gap-3">
									<div class="flex-1">
										<h2 class="text-sm font-semibold">Comunidades públicas</h2>
										<p class="text-[12px] text-slate-600">
											Grupos abertos para a cena, onde qualquer pessoa pode participar ou seguir.
										</p>
									</div>
									<button class="inline-flex items-center gap-1 rounded-md bg-slate-900 px-3 py-1.5 text-[11px] font-medium text-white">
										<i class="ri-community-line text-xs"></i>
										<span>Criar nova comunidade</span>
									</button>
								</div>
								
								<?php if ( ! empty( $user_communities ) ) : ?>
								<div class="grid gap-3 md:grid-cols-3 text-[12px]">
									<?php foreach ( $user_communities as $community ) : ?>
									<article class="aprioEXP-card-shell p-3 flex flex-col justify-between bg-white border border-slate-200 rounded-lg">
										<div>
											<div class="flex items-center gap-2 mb-1">
												<h3 class="text-sm font-semibold"><?php echo esc_html( $community['name'] ?? 'Comunidade' ); ?></h3>
												<span class="aprioEXP-badge-public inline-flex items-center gap-1 px-2 py-0.5 text-[10px] bg-emerald-50 text-emerald-700 rounded">
													<i class="ri-sun-line text-[11px]"></i> Aberta
												</span>
											</div>
											<p class="text-slate-600 text-[11px] line-clamp-2"><?php echo esc_html( $community['description'] ?? '' ); ?></p>
										</div>
									</article>
									<?php endforeach; ?>
								</div>
								<?php else : ?>
								<div class="flex items-center justify-center h-32 text-slate-400 text-sm bg-slate-50 rounded-lg" data-tooltip="Entre ou crie uma comunidade">
									<p><i class="ri-community-line mr-2"></i>Nenhuma comunidade ainda.</p>
								</div>
								<?php endif; ?>
							</div>
							
							<!-- TAB: Documentos -->
							<div data-tab-panel="docs" role="tabpanel" class="hidden space-y-3">
								<div class="flex flex-col md:flex-row md:items-center gap-3">
									<div class="flex-1">
										<h2 class="text-sm font-semibold">Documentos para assinar ou criar</h2>
										<p class="text-[12px] text-slate-600">
											Fluxo rápido para contratos de DJ, staff, núcleos e parcerias de eventos.
										</p>
									</div>
									<div class="flex flex-wrap gap-2 text-[11px]">
										<button class="inline-flex items-center gap-1 rounded-md bg-slate-900 px-3 py-1.5 font-medium text-white">
											<i class="ri-file-add-line text-xs"></i>
											<span>Novo documento</span>
										</button>
									</div>
								</div>
								
								<?php if ( ! empty( $pending_docs ) ) : ?>
								<div class="grid gap-3 md:grid-cols-3 text-[12px]">
									<?php foreach ( $pending_docs as $doc ) : ?>
									<article class="aprioEXP-card-shell p-3 bg-white border border-slate-200 rounded-lg">
										<div class="flex items-start justify-between gap-2 mb-1">
											<span class="font-semibold"><?php echo esc_html( $doc['title'] ?? 'Documento' ); ?></span>
											<span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-900">
												<span class="inline-block h-1.5 w-1.5 rounded-full bg-amber-500"></span>
												<?php echo esc_html( $doc['status'] ?? 'Pendente' ); ?>
											</span>
										</div>
										<p class="text-slate-600 text-[11px] mb-2 line-clamp-2"><?php echo esc_html( $doc['description'] ?? '' ); ?></p>
									</article>
									<?php endforeach; ?>
								</div>
								<?php else : ?>
								<div class="flex items-center justify-center h-32 text-slate-400 text-sm bg-slate-50 rounded-lg" data-tooltip="Nenhum documento pendente">
									<p><i class="ri-file-text-line mr-2"></i>Nenhum documento pendente.</p>
								</div>
								<?php endif; ?>
							</div>
							
						</div>
					</section>
					
				</div>
				
				<!-- ========================== -->
				<!-- RIGHT COLUMN: Sidebar      -->
				<!-- ========================== -->
				<div class="space-y-4">
					
					<!-- Resumo rápido Card -->
					<section class="aprioEXP-card-shell p-4 bg-white border border-slate-200 rounded-lg">
						<h3 class="text-sm font-semibold mb-3">Resumo rápido</h3>
						<ul class="space-y-3 text-[12px] text-slate-600">
							<li class="flex gap-2">
								<i class="ri-calendar-event-line text-slate-400 mt-0.5"></i>
								<div>
									<span class="block font-medium text-slate-900">Próximo compromisso</span>
									<span data-tooltip="Baseado nos eventos favoritados">Nenhum evento agendado</span>
								</div>
							</li>
							<li class="flex gap-2">
								<i class="ri-file-text-line text-slate-400 mt-0.5"></i>
								<div>
									<span class="block font-medium text-slate-900">Docs pendentes</span>
									<span><?php echo count( $pending_docs ); ?> documento(s)</span>
								</div>
							</li>
							<li class="flex gap-2">
								<i class="ri-message-3-line text-slate-400 mt-0.5"></i>
								<div>
									<span class="block font-medium text-slate-900">Mensagens não lidas</span>
									<span>0 conversas</span>
								</div>
							</li>
						</ul>
						<a href="<?php echo esc_url( admin_url() ); ?>" class="mt-4 w-full flex items-center justify-center gap-2 rounded-md bg-slate-900 py-2 text-[11px] font-medium text-white transition-colors">
							<span>Abrir Gestor Apollo</span>
							<i class="ri-arrow-right-line"></i>
						</a>
					</section>
					
					<!-- Status social Card -->
					<section class="aprioEXP-card-shell p-4 bg-white border border-slate-200 rounded-lg">
						<h3 class="text-sm font-semibold mb-3">Status social</h3>
						<div class="space-y-3 text-[12px]">
							<div>
								<p class="font-medium text-slate-900 mb-1">Núcleos ativos</p>
								<div class="flex flex-wrap gap-1" data-tooltip="<?php echo empty( $user_nucleos ) ? 'Nenhum núcleo ativo' : ''; ?>">
									<?php if ( ! empty( $user_nucleos ) ) : ?>
										<?php foreach ( array_slice( $user_nucleos, 0, 3 ) as $nucleo ) : ?>
										<span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-slate-600"><?php echo esc_html( $nucleo['name'] ?? 'Núcleo' ); ?></span>
										<?php endforeach; ?>
									<?php else : ?>
										<span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-slate-400">Nenhum</span>
									<?php endif; ?>
								</div>
							</div>
							<div>
								<p class="font-medium text-slate-900 mb-1">Comunidades em destaque</p>
								<div class="flex flex-wrap gap-1" data-tooltip="<?php echo empty( $user_communities ) ? 'Nenhuma comunidade' : ''; ?>">
									<?php if ( ! empty( $user_communities ) ) : ?>
										<?php foreach ( array_slice( $user_communities, 0, 3 ) as $community ) : ?>
										<span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-emerald-700 border border-emerald-100"><?php echo esc_html( $community['name'] ?? 'Comunidade' ); ?></span>
										<?php endforeach; ?>
									<?php else : ?>
										<span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-slate-400">Nenhuma</span>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</section>
					
				</div>
				
			</div>
		</main>
		
	</div>
</section>

<!-- ============================================ -->
<!-- TAB SWITCHING JAVASCRIPT                    -->
<!-- ============================================ -->
<script>
document.addEventListener("DOMContentLoaded", () => {
	const tabs = document.querySelectorAll('[role="tab"]');
	const panels = document.querySelectorAll('[role="tabpanel"]');
	const animate = window.Motion?.animate;

	tabs.forEach((tab) => {
		tab.addEventListener("click", () => {
			// Deselect all
			tabs.forEach((t) => {
				t.setAttribute("aria-selected", "false");
				t.setAttribute("data-active", "false");
				t.classList.remove("bg-slate-900", "text-white");
				t.classList.add("text-slate-700", "hover:bg-slate-100");
			});
			panels.forEach((p) => p.classList.add("hidden"));

			// Select clicked
			tab.setAttribute("aria-selected", "true");
			tab.setAttribute("data-active", "true");
			tab.classList.add("bg-slate-900", "text-white");
			tab.classList.remove("text-slate-700", "hover:bg-slate-100");
			
			const target = tab.getAttribute("data-tab-target");
			const panel = document.querySelector(`[data-tab-panel="${target}"]`);
			if (panel) {
				panel.classList.remove("hidden");
				if (animate) {
					animate(panel, { opacity: [0, 1], y: [10, 0] }, { duration: 0.3 });
				}
			}
		});
	});

	// Initialize first tab as active
	const firstTab = document.querySelector('[data-active="true"]');
	if (firstTab) {
		firstTab.classList.add("bg-slate-900", "text-white");
		firstTab.classList.remove("text-slate-700", "hover:bg-slate-100");
	}
});
</script>

<!-- Base JS (tooltips, theme, etc) -->
<script src="https://assets.apollo.rio.br/base.js"></script>

<?php wp_footer(); ?>
</body>
</html>
