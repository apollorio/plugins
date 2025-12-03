<?php
/**
 * Single Núcleo Template
 * DESIGN LIBRARY: Baseado no modelo aprovado (communities.html)
 * Núcleo = Coletivo/Produtora (mais profissional que comunidade)
 *
 * @package Apollo_Social
 * @version 2.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post;

// Get núcleo data
$nucleo_id   = get_the_ID();
$title       = get_the_title();
$content     = get_the_content();
$description = get_post_meta( $nucleo_id, '_group_description', true ) ?: wp_trim_words( $content, 50 );

// Meta data
$cover_url       = get_post_meta( $nucleo_id, '_group_cover', true );
$logo_url        = get_post_meta( $nucleo_id, '_group_avatar', true );
$members_count   = (int) get_post_meta( $nucleo_id, '_group_members_count', true );
$events_count    = (int) get_post_meta( $nucleo_id, '_group_events_count', true );
$followers_count = (int) get_post_meta( $nucleo_id, '_group_followers_count', true );
$founded_year    = get_post_meta( $nucleo_id, '_nucleo_founded_year', true );
$genres          = get_post_meta( $nucleo_id, '_nucleo_genres', true );
$instagram       = get_post_meta( $nucleo_id, '_nucleo_instagram', true );
$website         = get_post_meta( $nucleo_id, '_nucleo_website', true );
$soundcloud      = get_post_meta( $nucleo_id, '_nucleo_soundcloud', true );
$is_verified     = (bool) get_post_meta( $nucleo_id, '_group_verified', true );
$location        = get_post_meta( $nucleo_id, '_group_location', true ) ?: 'Rio de Janeiro';
$tags            = get_post_meta( $nucleo_id, '_group_tags', true );

// Founders/Team
$founders = get_post_meta( $nucleo_id, '_nucleo_founders', true );
if ( ! is_array( $founders ) ) {
	$founders = array();
}

// Team members
$team_members = get_post_meta( $nucleo_id, '_nucleo_team', true );
if ( ! is_array( $team_members ) ) {
	$team_members = array();
}

// Creator
$creator_id = (int) get_post_field( 'post_author', $nucleo_id );
$creator    = get_userdata( $creator_id );

// Current user
$current_user_id = get_current_user_id();
$is_following    = false;
$is_team_member  = false;
if ( $current_user_id ) {
	$following = get_user_meta( $current_user_id, '_following_nucleos', true );
	if ( is_array( $following ) && in_array( $nucleo_id, $following, true ) ) {
		$is_following = true;
	}
	if ( in_array( $current_user_id, $team_members, true ) || in_array( $current_user_id, $founders, true ) ) {
		$is_team_member = true;
	}
}

// Genres array
if ( ! is_array( $genres ) ) {
	$genres = $genres ? array_map( 'trim', explode( ',', $genres ) ) : array();
}

// Tags array
if ( ! is_array( $tags ) ) {
	$tags = $tags ? array_map( 'trim', explode( ',', $tags ) ) : array();
}

// Activity status
$last_activity = get_post_meta( $nucleo_id, '_group_last_activity', true );
$is_active     = $last_activity && ( time() - (int) $last_activity ) < 86400;

// Current user avatar
$current_user_avatar = '';
$current_user_name   = 'Você';
if ( $current_user_id ) {
	$current_user_avatar = get_avatar_url( $current_user_id, array( 'size' => 80 ) );
	$current_user        = wp_get_current_user();
	$current_user_name   = $current_user->display_name;
}

// Fetch núcleo posts/updates
$nucleo_posts = get_posts(
	array(
		'post_type'      => 'apollo_social_post',
		'post_status'    => 'publish',
		'posts_per_page' => 10,
		'meta_query'     => array(
			array(
				'key'   => '_post_nucleo_id',
				'value' => $nucleo_id,
			),
		),
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);

// Fetch upcoming events
$upcoming_events = get_posts(
	array(
		'post_type'      => 'event_listing',
		'post_status'    => 'publish',
		'posts_per_page' => 3,
		'meta_query'     => array(
			array(
				'key'   => '_event_nucleo_id',
				'value' => $nucleo_id,
			),
			array(
				'key'     => '_event_start_date',
				'value'   => date( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'DATE',
			),
		),
		'orderby'        => 'meta_value',
		'meta_key'       => '_event_start_date',
		'order'          => 'ASC',
	)
);

// NO get_header() - Canvas mode
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full w-full bg-slate-50 antialiased selection:bg-orange-500 selection:text-white">
<head>
	<meta charset="UTF-8" />
	<title>Apollo :: Núcleo · <?php echo esc_html( $title ); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0" />

	<!-- Tailwind CSS -->
	<script src="https://cdn.tailwindcss.com"></script>
	<script>
	tailwind.config = {
		theme: {
		extend: {
			fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
		}
		}
	}
	</script>

	<!-- Design system Apollo -->
	<link rel="stylesheet" href="https://assets.apollo.rio.br/uni.css" />
	<script src="https://assets.apollo.rio.br/base.js" defer></script>

	<!-- Remixicon -->
	<link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet" />

	<style>
	:root {
		--font-primary: "Urbanist", system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
		--bg-main: #ffffff;
		--text-main: rgba(15, 23, 42, 0.7);
		--text-primary: rgba(15, 23, 42, 0.95);
		--border-color-2: #e5e7eb;
		--accent-color: #f97316;
	}
	html, body {
		font-family: var(--font-primary);
		background-color: var(--bg-main);
		color: var(--text-main);
		-webkit-tap-highlight-color: transparent;
	}
	.no-scrollbar::-webkit-scrollbar { display: none; }
	.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
	.pb-safe { padding-bottom: env(safe-area-inset-bottom, 20px); }

	.aprio-sidebar-nav a {
		display:flex; align-items:center; gap:0.75rem;
		padding:0.55rem 0.75rem; margin-bottom:0.1rem;
		border-radius:10px; border-left:2px solid transparent;
		font-size:13px; color:#64748b; text-decoration:none;
		transition:background-color .18s,color .18s,border-color .18s;
	}
	.aprio-sidebar-nav a i { font-size:18px; }
	.aprio-sidebar-nav a:hover {
		background-color:#f8fafc; color:#0f172a; border-left-color:#e5e7eb;
	}
	.aprio-sidebar-nav a[aria-current="page"] {
		background-color:#fff7ed; color:#ea580c;
		border-left-color:#f97316; font-weight:600;
	}

	.nav-btn {
		display:flex; flex-direction:column; align-items:center; justify-content:center;
		gap:0.15rem; font-size:10px; color:#64748b; text-align:center;
	}
	.nav-btn i { font-size:20px; }
	.nav-btn.active { color:#f97316; font-weight:600; }
	</style>
	<?php wp_head(); ?>
</head>

<body class="min-h-screen">
	<div class="min-h-screen flex bg-slate-50">

	<!-- SIDEBAR DESKTOP: APOLLO SOCIAL -->
	<aside class="hidden md:flex md:flex-col w-64 border-r border-slate-200 bg-white/95 backdrop-blur-xl">
		<!-- Logo / topo -->
		<div class="h-16 flex items-center gap-3 px-6 border-b border-slate-100">
		<div class="h-9 w-9 rounded-[8px] bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center text-white">
			<i class="ri-fire-fill text-lg"></i>
		</div>
		<div class="flex flex-col leading-tight">
			<span class="text-[10px] font-semibold text-slate-400 uppercase tracking-[0.18em]">Apollo</span>
			<span class="text-[15px] font-extrabold text-slate-900">Núcleos</span>
		</div>
		</div>

		<!-- Navegação -->
		<nav class="aprio-sidebar-nav flex-1 px-4 pt-4 pb-2 overflow-y-auto no-scrollbar text-[13px]">
		<div class="px-1 mb-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Navegação</div>

		<a href="<?php echo esc_url( home_url( '/feed/' ) ); ?>">
			<i class="ri-home-5-line"></i>
			<span>Feed</span>
		</a>

		<a href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>">
			<i class="ri-calendar-event-line"></i>
			<span>Agenda</span>
		</a>

		<a href="<?php echo esc_url( home_url( '/comunidades/' ) ); ?>">
			<i class="ri-group-line"></i>
			<span>Comunidades</span>
		</a>

		<a href="<?php echo esc_url( home_url( '/nucleos/' ) ); ?>" aria-current="page">
			<i class="ri-fire-line"></i>
			<span>Núcleos</span>
		</a>

		<a href="<?php echo esc_url( home_url( '/classificados/' ) ); ?>">
			<i class="ri-ticket-line"></i>
			<span>Classificados</span>
		</a>

		<a href="<?php echo esc_url( home_url( '/docs/' ) ); ?>">
			<i class="ri-file-list-3-line"></i>
			<span>Docs & Contratos</span>
		</a>

		<a href="<?php echo esc_url( home_url( '/perfil/' ) ); ?>">
			<i class="ri-user-3-line"></i>
			<span>Perfil</span>
		</a>

		<div class="mt-4 px-1 mb-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Configurações</div>
		<a href="<?php echo esc_url( home_url( '/ajustes/' ) ); ?>">
			<i class="ri-settings-3-line"></i>
			<span>Ajustes</span>
		</a>
		</nav>

		<!-- User / footer sidebar -->
		<?php if ( $current_user_id ) : ?>
		<div class="border-t border-slate-100 px-4 py-3">
		<div class="flex items-center gap-3">
			<div class="h-8 w-8 rounded-full overflow-hidden bg-slate-100">
			<img
				src="<?php echo esc_url( $current_user_avatar ); ?>"
				class="h-full w-full object-cover"
				alt="<?php echo esc_attr( $current_user_name ); ?>"
			/>
			</div>
			<div class="flex flex-col leading-tight">
			<span class="text-[12px] font-semibold text-slate-900"><?php echo esc_html( $current_user_name ); ?></span>
			<span class="text-[10px] text-slate-500">@<?php echo esc_html( wp_get_current_user()->user_login ); ?></span>
			</div>
			<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="ml-auto text-slate-400 hover:text-slate-700">
			<i class="ri-logout-box-r-line text-lg"></i>
			</a>
		</div>
		</div>
		<?php endif; ?>
	</aside>

	<!-- MAIN COLUMN -->
	<div class="flex-1 flex flex-col min-h-screen bg-slate-50/70">

		<!-- HEADER -->
		<header class="sticky top-0 z-40 h-14 flex items-center justify-between border-b border-slate-200 bg-white/90 backdrop-blur-md px-4 md:px-6">
		<div class="flex items-center gap-3">
			<button type="button" onclick="history.back()" class="h-8 w-8 rounded-full border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50 md:hidden">
			<i class="ri-arrow-left-line text-lg"></i>
			</button>
			<!-- Mobile icon -->
			<div class="h-9 w-9 rounded-[6px] bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center md:hidden text-white overflow-hidden">
			<?php if ( $logo_url ) : ?>
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="" class="w-full h-full object-cover" />
			<?php else : ?>
			<i class="ri-fire-fill text-[20px]"></i>
			<?php endif; ?>
			</div>

			<div class="flex flex-col leading-none">
			<div class="flex items-center gap-1.5">
				<h1 class="text-[15px] font-extrabold mt-2 text-slate-900">
				<?php echo esc_html( $title ); ?>
				</h1>
				<?php if ( $is_verified ) : ?>
				<i class="ri-verified-badge-fill text-blue-500 mt-2"></i>
				<?php endif; ?>
			</div>
			<p class="text-[12px] text-slate-500">
				Núcleo · <?php echo $founded_year ? 'Desde ' . esc_html( $founded_year ) : esc_html( $location ); ?>
			</p>
			</div>
		</div>

		<!-- Desktop controls -->
		<div class="hidden md:flex items-center gap-3 text-[12px]">
			<div class="inline-flex items-center gap-1 text-slate-500">
			<i class="ri-calendar-event-line text-xs"></i>
			<span><?php echo esc_html( $events_count ); ?> eventos</span>
			</div>
			<div class="inline-flex items-center gap-1 text-slate-500">
			<i class="ri-user-follow-line text-xs"></i>
			<span><?php echo esc_html( number_format_i18n( $followers_count ) ); ?> seguidores</span>
			</div>
			<?php if ( ! $is_following && $current_user_id ) : ?>
			<button class="inline-flex items-center gap-1 rounded-full border border-orange-500 bg-orange-500 text-white px-3 py-1.5 text-[12px] font-semibold shadow-sm hover:bg-orange-600" data-action="follow-nucleo" data-nucleo-id="<?php echo esc_attr( $nucleo_id ); ?>">
			<i class="ri-add-line text-xs"></i>
			Seguir
			</button>
			<?php elseif ( $is_following ) : ?>
			<span class="inline-flex items-center gap-1 rounded-full bg-orange-100 text-orange-700 px-3 py-1.5 text-[12px] font-semibold">
			<i class="ri-check-line text-xs"></i>
			Seguindo
			</span>
			<?php endif; ?>
		</div>

		<!-- Mobile actions -->
		<div class="flex md:hidden items-center gap-2">
			<?php if ( ! $is_following && $current_user_id ) : ?>
			<button class="rounded-full border border-orange-500 bg-orange-500 text-white px-3 py-1.5 text-[11px] font-semibold" data-action="follow-nucleo" data-nucleo-id="<?php echo esc_attr( $nucleo_id ); ?>">
			Seguir
			</button>
			<?php elseif ( $is_following ) : ?>
			<span class="rounded-full bg-orange-100 text-orange-700 px-3 py-1.5 text-[11px] font-semibold">
			Seguindo
			</span>
			<?php endif; ?>
		</div>
		</header>

		<!-- MAIN CONTENT -->
		<main class="flex-1 px-0 md:px-6 py-4 md:py-6 pb-24 md:pb-8">
		<div class="w-full max-w-6xl mx-auto flex flex-col lg:flex-row gap-5 lg:gap-7">

			<!-- LEFT COLUMN · INFO DO NÚCLEO -->
			<section class="w-full lg:w-[360px] shrink-0 flex flex-col gap-4 lg:sticky lg:top-16 lg:self-start px-4 lg:px-0">

			<!-- Hero / capa núcleo -->
			<div class="overflow-hidden rounded-2xl bg-slate-900 border border-slate-800 relative">
				<div class="h-36 w-full">
				<?php if ( $cover_url ) : ?>
				<img src="<?php echo esc_url( $cover_url ); ?>" alt="" class="w-full h-full object-cover opacity-90" />
				<?php else : ?>
				<div class="w-full h-full bg-gradient-to-r from-orange-500 via-red-500 to-pink-500 opacity-80"></div>
				<?php endif; ?>
				</div>
				<div class="absolute inset-0 flex flex-col justify-end p-4 bg-gradient-to-t from-slate-950/90 via-slate-950/50 to-transparent">
				<div class="inline-flex items-center gap-2 mb-2">
					<span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-black/40 border border-white/20 overflow-hidden">
					<?php if ( $logo_url ) : ?>
					<img src="<?php echo esc_url( $logo_url ); ?>" alt="" class="w-full h-full object-cover" />
					<?php else : ?>
					<i class="ri-fire-fill text-xl text-orange-400"></i>
					<?php endif; ?>
					</span>
					<?php if ( $is_verified ) : ?>
					<span class="inline-flex items-center gap-1.5 rounded-full bg-blue-500 border border-white/15 px-2.5 py-1">
					<i class="ri-verified-badge-fill text-[11px] text-white"></i>
					<span class="text-[11px] text-white font-medium uppercase tracking-wide">Verificado</span>
					</span>
					<?php elseif ( $is_active ) : ?>
					<span class="inline-flex items-center gap-1.5 rounded-full bg-black/40 border border-white/15 px-2.5 py-1">
					<span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
					<span class="text-[11px] text-slate-100 font-medium uppercase tracking-wide">Ativo</span>
					</span>
					<?php endif; ?>
				</div>
				<h2 class="text-lg md:text-xl font-bold text-white leading-snug">
					<?php echo esc_html( $title ); ?>
				</h2>
				<p class="text-[11px] text-slate-200 mt-1">
					<?php echo esc_html( wp_trim_words( $description, 15 ) ); ?>
				</p>
				<div class="mt-3 flex items-center gap-3 text-[11px] text-slate-300">
					<span class="inline-flex items-center gap-1">
					<i class="ri-calendar-event-line text-xs"></i> <?php echo esc_html( $events_count ); ?> eventos
					</span>
					<span class="inline-flex items-center gap-1">
					<i class="ri-map-pin-line text-xs"></i> <?php echo esc_html( $location ); ?>
					</span>
				</div>
				</div>
			</div>

			<!-- Stats -->
			<div class="bg-white/95 border border-slate-200 rounded-2xl px-4 py-4 shadow-sm">
				<div class="flex items-center justify-around text-center">
				<div>
					<span class="block text-xl font-bold text-slate-900"><?php echo esc_html( $events_count ); ?></span>
					<span class="text-[11px] text-slate-500">Eventos</span>
				</div>
				<div class="h-8 w-px bg-slate-200"></div>
				<div>
					<span class="block text-xl font-bold text-slate-900"><?php echo esc_html( $members_count ); ?></span>
					<span class="text-[11px] text-slate-500">Membros</span>
				</div>
				<div class="h-8 w-px bg-slate-200"></div>
				<div>
					<span class="block text-xl font-bold text-slate-900"><?php echo esc_html( number_format_i18n( $followers_count ) ); ?></span>
					<span class="text-[11px] text-slate-500">Seguidores</span>
				</div>
				</div>
			</div>

			<!-- Sobre -->
			<div class="bg-white/95 border border-slate-200 rounded-2xl px-4 py-3 md:px-4 md:py-4 shadow-sm">
				<div class="flex items-center justify-between mb-2.5">
				<h3 class="text-[12px] font-semibold text-slate-900 uppercase tracking-[0.16em]">
					Sobre
				</h3>
				<?php if ( $founded_year ) : ?>
				<span class="inline-flex items-center gap-1 text-[11px] text-slate-400">
					<i class="ri-time-line text-xs"></i>
					Desde <?php echo esc_html( $founded_year ); ?>
				</span>
				<?php endif; ?>
				</div>
				<p class="text-[13px] leading-relaxed text-slate-600">
				<?php echo esc_html( $description ?: 'Nenhuma descrição adicionada ainda.' ); ?>
				</p>
			   
				<!-- Gêneros -->
				<?php if ( ! empty( $genres ) ) : ?>
				<div class="flex flex-wrap gap-1.5 mt-3">
					<?php foreach ( $genres as $genre ) : ?>
				<span class="inline-flex items-center gap-1 rounded-full bg-orange-100 text-orange-700 px-2 py-0.5 text-[11px] font-medium">
						<?php echo esc_html( $genre ); ?>
				</span>
				<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</div>

			<!-- Links sociais -->
			<?php if ( $instagram || $website || $soundcloud ) : ?>
			<div class="bg-white/95 border border-slate-200 rounded-2xl px-4 py-3 md:px-4 md:py-4 shadow-sm">
				<h3 class="text-[12px] font-semibold text-slate-900 uppercase tracking-[0.16em] mb-3">
				Links
				</h3>
				<div class="space-y-2">
				<?php if ( $instagram ) : ?>
				<a href="https://instagram.com/<?php echo esc_attr( ltrim( $instagram, '@' ) ); ?>" target="_blank" rel="noopener" class="flex items-center gap-3 p-2 hover:bg-slate-50 rounded-lg transition-colors">
					<div class="h-8 w-8 rounded-full bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 flex items-center justify-center text-white">
					<i class="ri-instagram-line text-sm"></i>
					</div>
					<span class="text-[13px] text-slate-700">@<?php echo esc_html( ltrim( $instagram, '@' ) ); ?></span>
				</a>
				<?php endif; ?>
				
				<?php if ( $soundcloud ) : ?>
				<a href="https://soundcloud.com/<?php echo esc_attr( $soundcloud ); ?>" target="_blank" rel="noopener" class="flex items-center gap-3 p-2 hover:bg-slate-50 rounded-lg transition-colors">
					<div class="h-8 w-8 rounded-full bg-orange-500 flex items-center justify-center text-white">
					<i class="ri-soundcloud-line text-sm"></i>
					</div>
					<span class="text-[13px] text-slate-700"><?php echo esc_html( $soundcloud ); ?></span>
				</a>
				<?php endif; ?>
				
				<?php if ( $website ) : ?>
				<a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener" class="flex items-center gap-3 p-2 hover:bg-slate-50 rounded-lg transition-colors">
					<div class="h-8 w-8 rounded-full bg-slate-900 flex items-center justify-center text-white">
					<i class="ri-global-line text-sm"></i>
					</div>
					<span class="text-[13px] text-slate-700"><?php echo esc_html( parse_url( $website, PHP_URL_HOST ) ); ?></span>
				</a>
				<?php endif; ?>
				</div>
			</div>
			<?php endif; ?>

			<!-- Equipe / Fundadores -->
			<?php if ( ! empty( $founders ) || $creator ) : ?>
			<div class="bg-white/95 border border-slate-200 rounded-2xl px-4 py-3 md:px-4 md:py-4 shadow-sm">
				<div class="flex items-center justify-between mb-3">
				<h3 class="text-[12px] font-semibold text-slate-900 uppercase tracking-[0.16em]">
					Equipe
				</h3>
				<span class="inline-flex items-center gap-1 text-[11px] text-slate-400">
					<i class="ri-team-line text-xs"></i>
					Fundadores
				</span>
				</div>
				<div class="space-y-2.5">
				<?php if ( $creator ) : ?>
				<div class="flex items-center gap-3">
					<div class="h-8 w-8 rounded-full overflow-hidden bg-slate-100">
					<?php echo get_avatar( $creator_id, 32, '', $creator->display_name, array( 'class' => 'w-full h-full object-cover' ) ); ?>
					</div>
					<div class="flex-1">
					<p class="text-[13px] font-semibold text-slate-900"><?php echo esc_html( $creator->display_name ); ?></p>
					<p class="text-[11px] text-slate-500">@<?php echo esc_html( $creator->user_login ); ?></p>
					</div>
					<span class="inline-flex items-center gap-1 rounded-full bg-orange-100 text-orange-700 text-[10px] px-2 py-0.5">
					<i class="ri-star-smile-line text-[11px]"></i> fundador
					</span>
				</div>
				<?php endif; ?>
				
				<?php
				foreach ( $founders as $founder_id ) :
					if ( $founder_id === $creator_id ) {
						continue;
					}
					$founder = get_userdata( $founder_id );
					if ( ! $founder ) {
						continue;
					}
					?>
				<div class="flex items-center gap-3">
					<div class="h-8 w-8 rounded-full overflow-hidden bg-slate-100">
					<?php echo get_avatar( $founder_id, 32, '', $founder->display_name, array( 'class' => 'w-full h-full object-cover' ) ); ?>
					</div>
					<div class="flex-1">
					<p class="text-[13px] font-semibold text-slate-900"><?php echo esc_html( $founder->display_name ); ?></p>
					<p class="text-[11px] text-slate-500">@<?php echo esc_html( $founder->user_login ); ?></p>
					</div>
					<span class="inline-flex items-center gap-1 rounded-full bg-slate-100 text-slate-700 text-[10px] px-2 py-0.5">
					<i class="ri-user-star-line text-[11px]"></i> co-fundador
					</span>
				</div>
				<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

			<!-- Próximos eventos -->
			<?php if ( ! empty( $upcoming_events ) ) : ?>
			<div class="bg-white/95 border border-slate-200 rounded-2xl px-4 py-3 md:px-4 md:py-4 shadow-sm">
				<div class="flex items-center justify-between mb-3">
				<h3 class="text-[12px] font-semibold text-slate-900 uppercase tracking-[0.16em]">
					Próximos Eventos
				</h3>
				<a href="<?php echo esc_url( home_url( '/eventos/?nucleo=' . $nucleo_id ) ); ?>" class="text-[11px] text-orange-600 hover:underline">Ver todos</a>
				</div>
				<div class="space-y-2">
				<?php
				foreach ( $upcoming_events as $event ) :
					$event_date = get_post_meta( $event->ID, '_event_start_date', true );
					$date_obj   = $event_date ? DateTime::createFromFormat( 'Y-m-d', $event_date ) : null;
					$day_names  = array( 'DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB' );
					?>
				<a href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>" class="flex gap-3 items-center p-2 hover:bg-slate-50 rounded-lg transition-colors group">
					<div class="h-10 w-10 rounded-lg border border-slate-200 flex flex-col items-center justify-center bg-white text-slate-900 group-hover:border-orange-400 transition-colors">
					<?php if ( $date_obj ) : ?>
					<span class="text-[9px] uppercase font-bold"><?php echo esc_html( $day_names[ (int) $date_obj->format( 'w' ) ] ); ?></span>
					<span class="text-[13px] font-bold"><?php echo esc_html( $date_obj->format( 'd' ) ); ?></span>
					<?php else : ?>
					<i class="ri-calendar-line text-lg"></i>
					<?php endif; ?>
					</div>
					<div class="min-w-0 flex-1">
					<div class="text-[12px] font-bold truncate group-hover:text-orange-600"><?php echo esc_html( $event->post_title ); ?></div>
					<div class="text-[10px] text-slate-500"><?php echo esc_html( get_post_meta( $event->ID, '_event_location', true ) ?: $location ); ?></div>
					</div>
				</a>
				<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>
			</section>

			<!-- RIGHT COLUMN · POSTS / UPDATES -->
			<section class="flex-1 flex flex-col gap-4 px-4 lg:px-0">

			<!-- Caixa de novo post (apenas para membros da equipe) -->
			<?php if ( $is_team_member ) : ?>
			<div class="bg-white/95 border border-slate-200 rounded-2xl px-4 py-3 md:px-5 md:py-4 shadow-sm">
				<div class="flex items-start gap-3">
				<div class="h-9 w-9 rounded-full overflow-hidden bg-slate-100 flex-shrink-0">
					<img
					src="<?php echo esc_url( $current_user_avatar ); ?>"
					class="h-full w-full object-cover"
					alt="<?php echo esc_attr( $current_user_name ); ?>"
					/>
				</div>
				<div class="flex-1">
					<textarea
					rows="2"
					id="nucleo-post-content"
					placeholder="Compartilhe uma atualização do <?php echo esc_attr( $title ); ?>..."
					class="w-full resize-none border border-slate-200 rounded-2xl px-3 py-2 text-[13px] focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500/60"
					></textarea>
					<div class="mt-2 flex items-center justify-between">
					<div class="flex items-center gap-2 text-slate-400 text-[12px]">
						<button type="button" class="inline-flex items-center gap-1 hover:text-orange-600" data-action="add-image">
						<i class="ri-image-add-line text-sm"></i>
						<span>Imagem</span>
						</button>
						<button type="button" class="inline-flex items-center gap-1 hover:text-orange-600" data-action="announce-event">
						<i class="ri-megaphone-line text-sm"></i>
						<span>Anunciar evento</span>
						</button>
					</div>
					<button type="button" class="inline-flex items-center gap-1 rounded-full bg-orange-500 text-white text-[12px] px-3 py-1.5 font-semibold shadow-sm hover:bg-orange-600" data-action="publish-nucleo-post" data-nucleo-id="<?php echo esc_attr( $nucleo_id ); ?>">
						<i class="ri-send-plane-2-line text-xs"></i>
						<span>Publicar</span>
					</button>
					</div>
				</div>
				</div>
			</div>
			<?php endif; ?>

			<!-- Posts -->
			<?php if ( ! empty( $nucleo_posts ) ) : ?>
				<?php
				foreach ( $nucleo_posts as $post_item ) :
					$post_author_id      = (int) $post_item->post_author;
					$post_author         = get_userdata( $post_author_id );
					$post_content        = $post_item->post_content;
					$post_date           = human_time_diff( strtotime( $post_item->post_date ), current_time( 'timestamp' ) );
					$post_image          = get_post_meta( $post_item->ID, '_post_image', true );
					$post_likes          = (int) get_post_meta( $post_item->ID, '_post_likes_count', true );
					$post_comments_count = (int) get_post_meta( $post_item->ID, '_post_comments_count', true );
					$is_announcement     = (bool) get_post_meta( $post_item->ID, '_post_is_announcement', true );
					?>
			<article class="bg-white/95 border border-slate-200 rounded-2xl px-4 py-3 md:px-5 md:py-4 shadow-sm">
				<header class="flex items-start gap-3">
				<div class="h-9 w-9 rounded-xl overflow-hidden bg-slate-100">
					<?php if ( $logo_url ) : ?>
					<img src="<?php echo esc_url( $logo_url ); ?>" alt="" class="h-full w-full object-cover" />
					<?php else : ?>
					<div class="h-full w-full bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center">
					<i class="ri-fire-fill text-white text-sm"></i>
					</div>
					<?php endif; ?>
				</div>
				<div class="flex-1">
					<div class="flex items-center justify-between gap-2">
					<div>
						<div class="flex items-center gap-1.5">
						<span class="text-[13px] font-semibold text-slate-900"><?php echo esc_html( $title ); ?></span>
						<?php if ( $is_verified ) : ?>
						<i class="ri-verified-badge-fill text-blue-500 text-xs"></i>
						<?php endif; ?>
						</div>
						<div class="flex items-center gap-2 text-[11px] text-slate-400">
						<span>há <?php echo esc_html( $post_date ); ?></span>
						<?php if ( $post_author ) : ?>
						<span class="h-1 w-1 rounded-full bg-slate-300"></span>
						<span>por @<?php echo esc_html( $post_author->user_login ); ?></span>
						<?php endif; ?>
						</div>
					</div>
					<?php if ( $is_announcement ) : ?>
					<span class="inline-flex items-center gap-1 rounded-full bg-orange-100 text-orange-700 text-[10px] px-2 py-0.5">
						<i class="ri-megaphone-line text-[11px]"></i> anúncio
					</span>
					<?php else : ?>
					<button class="text-slate-400 hover:text-slate-700">
						<i class="ri-more-2-fill"></i>
					</button>
					<?php endif; ?>
					</div>
				</div>
				</header>

				<div class="mt-3 text-[13px] text-slate-700 leading-relaxed">
					<?php echo wp_kses_post( $post_content ); ?>
				</div>

					<?php if ( $post_image ) : ?>
				<div class="mt-3 rounded-xl overflow-hidden border border-slate-200">
				<img src="<?php echo esc_url( $post_image ); ?>" alt="" class="w-full h-auto" />
				</div>
				<?php endif; ?>

				<div class="mt-3 flex items-center justify-end gap-4 text-[12px] text-slate-500">
				<button class="inline-flex items-center gap-1 hover:text-orange-600" data-action="like-post" data-post-id="<?php echo esc_attr( $post_item->ID ); ?>">
					<i class="ri-heart-3-line text-sm"></i> <?php echo esc_html( $post_likes ); ?>
				</button>
				<button class="inline-flex items-center gap-1 hover:text-slate-900" data-action="view-comments" data-post-id="<?php echo esc_attr( $post_item->ID ); ?>">
					<i class="ri-message-2-line text-sm"></i> <?php echo esc_html( $post_comments_count ); ?>
				</button>
				<button class="inline-flex items-center gap-1 hover:text-slate-900" data-action="share-post" data-post-id="<?php echo esc_attr( $post_item->ID ); ?>">
					<i class="ri-share-forward-line text-sm"></i>
				</button>
				</div>
			</article>
				<?php endforeach; ?>
			<?php else : ?>
			<!-- Empty state -->
			<div class="bg-white/95 border border-slate-200 rounded-2xl px-4 py-8 shadow-sm text-center">
				<div class="h-16 w-16 mx-auto rounded-2xl bg-gradient-to-br from-orange-100 to-red-100 flex items-center justify-center mb-4">
				<i class="ri-fire-line text-3xl text-orange-400"></i>
				</div>
				<h3 class="text-[14px] font-semibold text-slate-900 mb-1">Nenhuma atualização ainda</h3>
				<p class="text-[13px] text-slate-500">O <?php echo esc_html( $title ); ?> ainda não publicou nenhuma atualização.</p>
			</div>
			<?php endif; ?>

			</section>
		</div>
		</main>

		<!-- BOTTOM NAV MOBILE -->
		<nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-xl border-t border-slate-200/50 pb-safe z-50">
		<div class="max-w-2xl mx-auto w-full px-4 py-2 flex items-end justify-between h-[60px]">
			<a href="<?php echo esc_url( home_url( '/feed/' ) ); ?>" class="nav-btn w-14 pb-1">
			<i class="ri-home-5-line"></i>
			<span>Feed</span>
			</a>
			<a href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>" class="nav-btn w-14 pb-1">
			<i class="ri-calendar-line"></i>
			<span>Agenda</span>
			</a>
			<div class="relative -top-5">
			<button class="h-14 w-14 rounded-full bg-gradient-to-br from-orange-500 to-red-500 text-white flex items-center justify-center shadow-[0_8px_20px_-6px_rgba(249,115,22,0.6)]" data-action="mobile-add-menu">
				<i class="ri-add-line text-3xl"></i>
			</button>
			</div>
			<a href="<?php echo esc_url( home_url( '/nucleos/' ) ); ?>" class="nav-btn active w-14 pb-1">
			<i class="ri-fire-line"></i>
			<span>Núcleos</span>
			</a>
			<a href="<?php echo esc_url( home_url( '/perfil/' ) ); ?>" class="nav-btn w-14 pb-1">
			<i class="ri-user-3-line"></i>
			<span>Perfil</span>
			</a>
		</div>
		</nav>
	</div>
	</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Follow núcleo
	document.querySelectorAll('[data-action="follow-nucleo"]').forEach(btn => {
		btn.addEventListener('click', async function() {
			const nucleoId = this.dataset.nucleoId;
			try {
				const response = await fetch('<?php echo esc_url( rest_url( 'apollo-social/v1/nucleo/follow' ) ); ?>', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
					},
					body: JSON.stringify({ nucleo_id: nucleoId })
				});
				const data = await response.json();
				if (data.success) {
					location.reload();
				} else {
					alert(data.message || 'Erro ao seguir núcleo');
				}
			} catch (e) {
				console.error(e);
			}
		});
	});

	// Publish nucleo post
	document.querySelectorAll('[data-action="publish-nucleo-post"]').forEach(btn => {
		btn.addEventListener('click', async function() {
			const nucleoId = this.dataset.nucleoId;
			const content = document.getElementById('nucleo-post-content')?.value;
			if (!content?.trim()) {
				alert('Digite algo para publicar');
				return;
			}
			try {
				const response = await fetch('<?php echo esc_url( rest_url( 'apollo-social/v1/nucleo/post' ) ); ?>', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
					},
					body: JSON.stringify({ nucleo_id: nucleoId, content: content })
				});
				const data = await response.json();
				if (data.success) {
					location.reload();
				} else {
					alert(data.message || 'Erro ao publicar');
				}
			} catch (e) {
				console.error(e);
			}
		});
	});

	// Like post
	document.querySelectorAll('[data-action="like-post"]').forEach(btn => {
		btn.addEventListener('click', async function() {
			const postId = this.dataset.postId;
			try {
				const response = await fetch('<?php echo esc_url( rest_url( 'apollo-social/v1/post/like' ) ); ?>', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
					},
					body: JSON.stringify({ post_id: postId })
				});
				const data = await response.json();
				if (data.success) {
					const icon = this.querySelector('i');
					if (data.liked) {
						icon.className = 'ri-heart-3-fill text-sm text-red-500';
					} else {
						icon.className = 'ri-heart-3-line text-sm';
					}
					this.innerHTML = icon.outerHTML + ' ' + data.count;
				}
			} catch (e) {
				console.error(e);
			}
		});
	});
});
</script>

<?php wp_footer(); ?>
</body>
</html>
