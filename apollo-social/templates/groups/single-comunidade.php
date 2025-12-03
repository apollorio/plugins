<?php
/**
 * Single Comunidade Template
 * DESIGN LIBRARY: Exatamente conforme HTML aprovado (communities.html)
 * Full layout com sidebar, header, posts da comunidade
 *
 * @package Apollo_Social
 * @version 2.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Enqueue assets via WordPress proper methods.
add_action(
	'wp_enqueue_scripts',
	function () {
		// UNI.CSS Framework.
		wp_enqueue_style(
			'apollo-uni-css',
			'https://assets.apollo.rio.br/uni.css',
			array(),
			'2.0.0'
		);

		// Remix Icons.
		wp_enqueue_style(
			'remixicon',
			'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css',
			array(),
			'4.7.0'
		);

		// Base JS.
		wp_enqueue_script(
			'apollo-base-js',
			'https://assets.apollo.rio.br/base.js',
			array(),
			'2.0.0',
			true
		);

		// Inline comunidade-specific styles.
		$comunidade_css = '
			:root {
				--font-primary: "Urbanist", system-ui, sans-serif;
				--bg-main: #ffffff;
				--text-main: rgba(15, 23, 42, 0.7);
				--text-primary: rgba(15, 23, 42, 0.95);
				--border-color-2: #e5e7eb;
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
				background-color:#f1f5f9; color:#0f172a;
				border-left-color:#0f172a; font-weight:600;
			}
			.nav-btn {
				display:flex; flex-direction:column; align-items:center; justify-content:center;
				gap:0.15rem; font-size:10px; color:#64748b; text-align:center;
			}
			.nav-btn i { font-size:20px; }
			.nav-btn.active { color:#0f172a; font-weight:600; }
		';
		wp_add_inline_style( 'apollo-uni-css', $comunidade_css );
	},
	10
);

// Trigger enqueue if not already done.
if ( ! did_action( 'wp_enqueue_scripts' ) ) {
	do_action( 'wp_enqueue_scripts' );
}

global $post;

// Get group data.
$group_id         = get_the_ID();
$group_title      = get_the_title();
$content          = get_the_content();
$description_meta = get_post_meta( $group_id, '_group_description', true );
$description      = ! empty( $description_meta ) ? $description_meta : wp_trim_words( $content, 50 );

// Meta data.
$cover_url     = get_post_meta( $group_id, '_group_cover', true );
$avatar_url    = get_post_meta( $group_id, '_group_avatar', true );
$members_count = (int) get_post_meta( $group_id, '_group_members_count', true );
$events_count  = (int) get_post_meta( $group_id, '_group_events_count', true );
$is_private    = (bool) get_post_meta( $group_id, '_group_is_private', true );
$category      = get_post_meta( $group_id, '_group_category', true );
$location      = get_post_meta( $group_id, '_group_location', true );
$tags          = get_post_meta( $group_id, '_group_tags', true );
$rules         = get_post_meta( $group_id, '_group_rules', true );
$subtitle_meta = get_post_meta( $group_id, '_group_subtitle', true );
$subtitle      = ! empty( $subtitle_meta ) ? $subtitle_meta : 'Comunidade';

// Founders/Moderators.
$creator_id = (int) get_post_field( 'post_author', $group_id );
$creator    = get_userdata( $creator_id );
$moderators = get_post_meta( $group_id, '_group_moderators', true );
if ( ! is_array( $moderators ) ) {
	$moderators = array();
}

// Members preview.
$members_list = get_post_meta( $group_id, '_group_members', true );
if ( ! is_array( $members_list ) ) {
	$members_list = array();
}

// Current user membership.
$current_user_id = get_current_user_id();
$is_member       = false;
$is_admin        = false;
$is_moderator    = false;
if ( $current_user_id ) {
	$membership = get_user_meta( $current_user_id, '_group_memberships', true );
	if ( is_array( $membership ) && in_array( $group_id, $membership, true ) ) {
		$is_member = true;
	}
	$admin_groups = get_user_meta( $current_user_id, '_group_admin_of', true );
	if ( is_array( $admin_groups ) && in_array( $group_id, $admin_groups, true ) ) {
		$is_admin = true;
	}
	if ( in_array( $current_user_id, $moderators, true ) ) {
		$is_moderator = true;
	}
}

// Activity status (24h threshold).
$last_activity = get_post_meta( $group_id, '_group_last_activity', true );
$is_active     = $last_activity && ( time() - (int) $last_activity ) < 86400;

// Default rules if empty.
if ( ! is_array( $rules ) || empty( $rules ) ) {
	$rules = array(
		'Respeito total a todas as pessoas, independente de origem, gênero, orientação ou crença.',
		'Zero tolerância a assédio, exposição de terceiros ou discurso de ódio.',
		'Divulgação é bem-vinda, mas sem spam: máximo 1 post promocional por semana.',
		'Mantenha o conteúdo relevante para a comunidade.',
	);
}

// Tags array.
if ( ! is_array( $tags ) ) {
	$tags = $tags ? array_map( 'trim', explode( ',', $tags ) ) : array();
}

// Current user avatar.
$current_user_avatar = '';
$current_user_name   = 'Você';
if ( $current_user_id ) {
	$current_user_avatar = get_avatar_url( $current_user_id, array( 'size' => 80 ) );
	$logged_in_user      = wp_get_current_user();
	$current_user_name   = $logged_in_user->display_name;
}

// Fetch community posts.
$community_posts = get_posts(
	array(
		'post_type'      => 'apollo_social_post',
		'post_status'    => 'publish',
		'posts_per_page' => 10,
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required to filter posts by community.
		'meta_query'     => array(
			array(
				'key'   => '_post_community_id',
				'value' => $group_id,
			),
		),
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);

// NO get_header() - Canvas mode.
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full w-full bg-slate-50 antialiased selection:bg-neutral-500 selection:text-white">
<head>
	<meta charset="UTF-8" />
	<title>Apollo :: Comunidade · <?php echo esc_html( $group_title ); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0" />
	<?php wp_head(); ?>
</head>

<body class="min-h-screen">
	<div class="min-h-screen flex bg-slate-50">

	<!-- SIDEBAR DESKTOP: APOLLO SOCIAL -->
	<aside class="hidden md:flex md:flex-col w-64 border-r border-slate-200 bg-white/95 backdrop-blur-xl" data-component="sidebar-social-desktop" data-type="S0">
		<!-- Logo / topo -->
		<div class="h-16 flex items-center gap-3 px-6 border-b border-slate-100">
		<div class="h-9 w-9 rounded-xl bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center shadow-md shadow-orange-500/60" data-ap-tooltip="Apollo::Rio">
			<i class="ri-slack-fill text-white text-[22px]"></i>
		</div>
		<div class="flex flex-col leading-tight">
			<span class="text-[9.5px] font-regular text-slate-400 uppercase tracking-[0.18em]">plataforma</span>
			<span class="text-[15px] font-extrabold text-slate-900">Apollo::rio</span>
		</div>
		</div>

		<!-- Navegação -->
		<nav class="aprio-sidebar-nav flex-1 px-4 pt-4 pb-2 overflow-y-auto no-scrollbar text-[13px]">
		<div class="px-1 mb-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Navegação</div>

		<a href="<?php echo esc_url( home_url( '/feed/' ) ); ?>" data-ap-tooltip="Ver feed">
			<i class="ri-home-5-line text-lg"></i>
			<span>Feed</span>
		</a>

		<a href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>" data-ap-tooltip="Ver agenda de eventos">
			<i class="ri-calendar-event-line text-lg"></i>
			<span>Eventos</span>
		</a>

		<a href="<?php echo esc_url( home_url( '/comunidades/' ) ); ?>" aria-current="page" data-ap-tooltip="Ver comunidades">
			<i class="ri-group-line text-lg"></i>
			<span>Comunidades</span>
		</a>

		<a href="<?php echo esc_url( home_url( '/nucleos/' ) ); ?>" data-ap-tooltip="Ver núcleos">
			<i class="ri-layout-5-line text-lg"></i>
			<span>Núcleos</span>
		</a>

		<a href="<?php echo esc_url( home_url( '/classificados/' ) ); ?>" data-ap-tooltip="Ver classificados">
			<i class="ri-ticket-line text-lg"></i>
			<span>Classificados</span>
		</a>

		<a href="<?php echo esc_url( home_url( '/docs/' ) ); ?>" data-ap-tooltip="Documentos e contratos">
			<i class="ri-file-list-3-line text-lg"></i>
			<span>Docs & Contratos</span>
		</a>

		<a href="<?php echo esc_url( home_url( '/perfil/' ) ); ?>" data-ap-tooltip="Ver perfil">
			<i class="ri-user-3-line text-lg"></i>
			<span>Perfil</span>
		</a>

		<?php if ( current_user_can( 'edit_others_posts' ) || current_user_can( 'manage_options' ) ) : ?>
		<div class="mt-4 px-1 mb-0 text-[9.5px] font-regular text-slate-400 uppercase tracking-wider">Cena::rio</div>
		<a href="<?php echo esc_url( home_url( '/cena-rio/' ) ); ?>" data-ap-tooltip="Calendário Cena Rio">
			<i class="ri-calendar-line text-lg"></i>
			<span>Agenda</span>
		</a>
		<a href="<?php echo esc_url( home_url( '/cena-rio/fornecedores/' ) ); ?>" data-ap-tooltip="Fornecedores">
			<i class="ri-bar-chart-grouped-line text-lg"></i>
			<span>Fornecedores</span>
		</a>
		<a href="<?php echo esc_url( home_url( '/cena-rio/docs/' ) ); ?>" data-ap-tooltip="Documentos Cena Rio">
			<i class="ri-file-text-line text-lg"></i>
			<span>Documentos</span>
		</a>
		<?php endif; ?>

		<div class="mt-4 px-1 mb-0 text-[9.5px] font-regular text-slate-400 uppercase tracking-wider">Acesso Rápido</div>
		<a href="<?php echo esc_url( home_url( '/ajustes/' ) ); ?>" data-ap-tooltip="Configurações">
			<i class="ri-settings-3-line text-lg"></i>
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
				data-ap-tooltip="<?php echo esc_attr( $current_user_name ); ?>"
			/>
			</div>
			<div class="flex flex-col leading-tight">
			<span class="text-[12px] font-semibold text-slate-900"><?php echo esc_html( $current_user_name ); ?></span>
			<span class="text-[10px] text-slate-500">@<?php echo esc_html( $logged_in_user->user_login ?? '' ); ?></span>
			</div>
			<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="ml-auto text-slate-400 hover:text-slate-700" data-ap-tooltip="Sair">
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
			<div class="h-9 w-9 rounded-[6px] bg-slate-900 flex items-center justify-center md:hidden text-white">
			<i class="ri-vip-crown-2-line text-[20px]"></i>
			</div>

			<div class="flex flex-col leading-none">
			<h1 class="text-[15px] font-extrabold mt-2 text-slate-900">
			<?php echo esc_html( $group_title ); ?>
			</h1>
			<p class="text-[12px] text-slate-500">
				<?php echo esc_html( $subtitle ); ?> · <?php echo esc_html( $description ? wp_trim_words( $description, 8 ) : 'Comunidade Apollo' ); ?>
			</p>
			</div>
		</div>

		<!-- Desktop controls -->
		<div class="hidden md:flex items-center gap-3 text-[12px]">
			<div class="inline-flex items-center gap-1 text-slate-500">
			<i class="ri-user-3-line text-xs"></i>
			<span><?php echo esc_html( number_format_i18n( $members_count ) ); ?> membros</span>
			</div>
			<?php if ( $is_active ) : ?>
			<div class="inline-flex items-center gap-1 text-slate-500">
			<i class="ri-flashlight-line text-xs"></i>
			<span>ativo agora</span>
			</div>
			<?php endif; ?>
			<?php if ( ! $is_member && $current_user_id ) : ?>
			<button class="inline-flex items-center gap-1 rounded-full border border-slate-900 bg-slate-900 text-white px-3 py-1.5 text-[12px] font-semibold shadow-sm hover:bg-slate-800" data-action="join-community" data-community-id="<?php echo esc_attr( $group_id ); ?>">
			<i class="ri-add-line text-xs"></i>
			Entrar na comunidade
			</button>
			<?php elseif ( $is_member ) : ?>
			<span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 text-emerald-700 px-3 py-1.5 text-[12px] font-semibold">
			<i class="ri-check-line text-xs"></i>
			Membro
			</span>
			<?php endif; ?>
		</div>

		<!-- Mobile actions -->
		<div class="flex md:hidden items-center gap-2">
			<?php if ( ! $is_member && $current_user_id ) : ?>
			<button class="rounded-full border border-slate-900 bg-slate-900 text-white px-3 py-1.5 text-[11px] font-semibold" data-action="join-community" data-community-id="<?php echo esc_attr( $group_id ); ?>">
			Entrar
			</button>
			<?php elseif ( $is_member ) : ?>
			<span class="rounded-full bg-emerald-100 text-emerald-700 px-3 py-1.5 text-[11px] font-semibold">
			Membro
			</span>
			<?php endif; ?>
		</div>
		</header>

		<!-- MAIN CONTENT -->
		<main class="flex-1 px-0 md:px-6 py-4 md:py-6 pb-24 md:pb-8">
		<div class="w-full max-w-6xl mx-auto flex flex-col lg:flex-row gap-5 lg:gap-7">

			<!-- LEFT COLUMN · SOBRE / REGRAS / RESPONSÁVEIS / MEMBROS -->
			<section class="w-full lg:w-[360px] shrink-0 flex flex-col gap-4 lg:sticky lg:top-16 lg:self-start px-4 lg:px-0">

			<!-- Hero / capa comunidade -->
			<div class="overflow-hidden rounded-2xl bg-slate-900 border border-slate-800 relative">
				<div class="h-32 w-full bg-gradient-to-r from-fuchsia-500 via-sky-400 to-emerald-400 opacity-80">
				<?php if ( $cover_url ) : ?>
				<img src="<?php echo esc_url( $cover_url ); ?>" alt="" class="w-full h-full object-cover" />
				<?php endif; ?>
				</div>
				<div class="absolute inset-0 flex flex-col justify-end p-4 bg-gradient-to-t from-slate-950/80 via-slate-950/40 to-transparent">
				<div class="inline-flex items-center gap-2 mb-2">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-black/40 border border-white/20">
					<?php if ( $avatar_url ) : ?>
					<img src="<?php echo esc_url( $avatar_url ); ?>" alt="" class="w-full h-full object-cover rounded-full" />
					<?php else : ?>
					<i class="ri-vip-crown-2-line text-lg text-amber-300"></i>
					<?php endif; ?>
					</span>
					<?php if ( $is_active ) : ?>
					<span class="inline-flex items-center gap-1.5 rounded-full bg-black/40 border border-white/15 px-2.5 py-1">
					<span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
					<span class="text-[11px] text-slate-100 font-medium uppercase tracking-wide">Comunidade ativa</span>
					</span>
					<?php endif; ?>
				</div>
				<h2 class="text-base md:text-lg font-semibold text-white leading-snug">
					<?php echo esc_html( $group_title ); ?>
				</h2>
				<p class="text-[11px] text-slate-200 mt-1">
					<?php echo esc_html( wp_trim_words( $description, 20 ) ); ?>
				</p>
				<div class="mt-3 flex items-center gap-3 text-[11px] text-slate-300">
					<span class="inline-flex items-center gap-1">
					<i class="ri-user-3-line text-xs"></i> <?php echo esc_html( number_format_i18n( $members_count ) ); ?> membros
					</span>
					<?php if ( ! empty( $tags ) ) : ?>
					<span class="inline-flex items-center gap-1">
					<i class="ri-hashtag text-xs"></i> #<?php echo esc_html( implode( ' #', array_slice( $tags, 0, 3 ) ) ); ?>
					</span>
					<?php endif; ?>
				</div>
				</div>
			</div>

			<!-- Sobre / descrição -->
			<div class="bg-white/95 border border-slate-200 rounded-2xl px-4 py-3 md:px-4 md:py-4 shadow-sm">
				<div class="flex items-center justify-between mb-2.5">
				<h3 class="text-[12px] font-semibold text-slate-900 uppercase tracking-[0.16em]">
					Sobre
				</h3>
				<span class="inline-flex items-center gap-1 text-[11px] text-slate-400">
					<i class="ri-earth-line text-xs"></i>
					<?php echo $is_private ? 'Privada' : 'Pública'; ?>
				</span>
				</div>
				<p class="text-[13px] leading-relaxed text-slate-600">
				<?php echo esc_html( ! empty( $description ) ? $description : 'Nenhuma descrição adicionada ainda.' ); ?>
				</p>
			</div>

			<!-- Regras da comunidade -->
			<div class="bg-white/95 border border-slate-200 rounded-2xl px-4 py-3 md:px-4 md:py-4 shadow-sm">
				<div class="flex items-center justify-between mb-2.5">
				<h3 class="text-[12px] font-semibold text-slate-900 uppercase tracking-[0.16em]">
					Regras da comunidade
				</h3>
				<span class="text-[11px] text-slate-400">
					Leitura obrigatória
				</span>
				</div>
				<ul class="space-y-2.5 text-[13px] text-slate-600">
				<?php foreach ( $rules as $rule ) : ?>
				<li class="flex gap-2">
					<span class="mt-[3px] h-1.5 w-1.5 rounded-full bg-emerald-500 shrink-0"></span>
					<span><?php echo esc_html( $rule ); ?></span>
				</li>
				<?php endforeach; ?>
				</ul>
			</div>

			<!-- Responsáveis -->
			<div class="bg-white/95 border border-slate-200 rounded-2xl px-4 py-3 md:px-4 md:py-4 shadow-sm">
				<div class="flex items-center justify-between mb-3">
				<h3 class="text-[12px] font-semibold text-slate-900 uppercase tracking-[0.16em]">
					Responsáveis
				</h3>
				<span class="inline-flex items-center gap-1 text-[11px] text-slate-400">
					<i class="ri-shield-user-line text-xs"></i>
					Moderação
				</span>
				</div>
				<div class="space-y-2.5">
				<!-- owner -->
				<?php if ( $creator ) : ?>
				<div class="flex items-center gap-3">
					<div class="h-8 w-8 rounded-full overflow-hidden bg-slate-100">
					<?php echo get_avatar( $creator_id, 32, '', $creator->display_name, array( 'class' => 'w-full h-full object-cover' ) ); ?>
					</div>
					<div class="flex-1">
					<p class="text-[13px] font-semibold text-slate-900"><?php echo esc_html( $creator->display_name ); ?></p>
					<p class="text-[11px] text-slate-500">@<?php echo esc_html( $creator->user_login ); ?> · Donx</p>
					</div>
					<span class="inline-flex items-center gap-1 rounded-full bg-slate-900 text-white text-[10px] px-2 py-0.5">
					<i class="ri-star-smile-line text-[11px]"></i> fundador(a)
					</span>
				</div>
				<?php endif; ?>

				<!-- moderators -->
				<?php
				foreach ( $moderators as $mod_id ) :
					$mod = get_userdata( $mod_id );
					if ( ! $mod || $mod_id === $creator_id ) {
						continue;
					}
					// Mock online status - replace with real online status.
					$is_online = wp_rand( 0, 1 );
					?>
				<div class="flex items-center gap-3">
					<div class="h-8 w-8 rounded-full overflow-hidden bg-slate-100">
					<?php echo get_avatar( $mod_id, 32, '', $mod->display_name, array( 'class' => 'w-full h-full object-cover' ) ); ?>
					</div>
					<div class="flex-1">
					<p class="text-[13px] font-semibold text-slate-900"><?php echo esc_html( $mod->display_name ); ?></p>
					<p class="text-[11px] text-slate-500">@<?php echo esc_html( $mod->user_login ); ?> · Moderação</p>
					</div>
					<?php if ( $is_online ) : ?>
					<span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] px-2 py-0.5">
					<span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> online
					</span>
					<?php else : ?>
					<span class="inline-flex items-center gap-1 rounded-full bg-slate-100 text-slate-700 text-[10px] px-2 py-0.5">
					<i class="ri-command-line text-[11px]"></i> curadoria
					</span>
					<?php endif; ?>
				</div>
				<?php endforeach; ?>
				</div>
			</div>

			<!-- Membros em destaque -->
			<div class="bg-white/95 border border-slate-200 rounded-2xl px-4 py-3 md:px-4 md:py-4 shadow-sm">
				<div class="flex items-center justify-between mb-2.5">
				<h3 class="text-[12px] font-semibold text-slate-900 uppercase tracking-[0.16em]">
					Membros
				</h3>
				<span class="text-[11px] text-slate-400"><?php echo esc_html( number_format_i18n( $members_count ) ); ?> no total</span>
				</div>
				<div class="flex flex-wrap gap-2">
				<?php
				$displayed_members = array_slice( $members_list, 0, 4 );
				foreach ( $displayed_members as $member_id ) :
					$member = get_userdata( $member_id );
					if ( ! $member ) {
						continue;
					}
					?>
				<a href="<?php echo esc_url( home_url( '/id/' . $member->user_login ) ); ?>" class="inline-flex items-center gap-1 rounded-full bg-slate-100 text-slate-700 text-[11px] px-2.5 py-1 hover:bg-slate-200 transition-colors">
					<span class="h-5 w-5 rounded-full bg-slate-300 overflow-hidden">
					<?php echo get_avatar( $member_id, 20, '', $member->display_name, array( 'class' => 'w-full h-full' ) ); ?>
					</span>
					<span>@<?php echo esc_html( $member->user_login ); ?></span>
				</a>
				<?php endforeach; ?>

				<button class="inline-flex items-center gap-1 rounded-full bg-slate-900 text-white text-[11px] px-2.5 py-1 hover:bg-slate-800 transition-colors" data-action="view-all-members" data-community-id="<?php echo esc_attr( $group_id ); ?>">
					<i class="ri-user-search-line text-xs"></i>
					<span>Ver todos</span>
				</button>
				</div>
			</div>
			</section>

			<!-- RIGHT COLUMN · POSTS DA COMUNIDADE -->
			<section class="flex-1 flex flex-col gap-4 px-4 lg:px-0">

			<!-- Caixa de novo post -->
			<?php if ( $is_member || $is_admin ) : ?>
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
					id="community-post-content"
					placeholder="Compartilhe algo com a <?php echo esc_attr( $group_title ); ?>..."
					class="w-full resize-none border border-slate-200 rounded-2xl px-3 py-2 text-[13px] focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900/60"
					></textarea>
					<div class="mt-2 flex items-center justify-between">
					<div class="flex items-center gap-2 text-slate-400 text-[12px]">
						<button type="button" class="inline-flex items-center gap-1 hover:text-slate-700" data-action="add-image">
						<i class="ri-image-add-line text-sm"></i>
						<span>Imagem</span>
						</button>
						<button type="button" class="inline-flex items-center gap-1 hover:text-slate-700" data-action="suggest-event">
						<i class="ri-calendar-line text-sm"></i>
						<span>Indicar festa</span>
						</button>
					</div>
					<button type="button" class="inline-flex items-center gap-1 rounded-full bg-slate-900 text-white text-[12px] px-3 py-1.5 font-semibold shadow-sm hover:bg-slate-800" data-action="publish-post" data-community-id="<?php echo esc_attr( $group_id ); ?>">
						<i class="ri-send-plane-2-line text-xs"></i>
						<span>Postar</span>
					</button>
					</div>
				</div>
				</div>
			</div>
			<?php elseif ( $current_user_id ) : ?>
			<div class="bg-slate-100 border border-slate-200 rounded-2xl px-4 py-4 text-center">
				<p class="text-[13px] text-slate-600">Entre na comunidade para publicar.</p>
			</div>
			<?php endif; ?>

			<!-- Posts -->
			<?php if ( ! empty( $community_posts ) ) : ?>
				<?php
				foreach ( $community_posts as $post_item ) :
					$post_author_id      = (int) $post_item->post_author;
					$post_author         = get_userdata( $post_author_id );
					$post_content        = $post_item->post_content;
					$post_date           = human_time_diff( strtotime( $post_item->post_date ), time() );
					$post_location       = get_post_meta( $post_item->ID, '_post_location', true );
					$post_tags           = get_post_meta( $post_item->ID, '_post_tags', true );
					$post_image          = get_post_meta( $post_item->ID, '_post_image', true );
					$post_likes          = (int) get_post_meta( $post_item->ID, '_post_likes_count', true );
					$post_comments_count = (int) get_post_meta( $post_item->ID, '_post_comments_count', true );
					$is_notice           = (bool) get_post_meta( $post_item->ID, '_post_is_notice', true );
					$is_owner            = in_array( $post_author_id, array( $creator_id, ...$moderators ), true );

					if ( ! is_array( $post_tags ) ) {
						$post_tags = $post_tags ? array_map( 'trim', explode( ',', $post_tags ) ) : array();
					}

					// First featured comment.
					$featured_comment = get_comments(
						array(
							'post_id' => $post_item->ID,
							'number'  => 1,
							'status'  => 'approve',
							'order'   => 'DESC',
						)
					);
					?>
			<article class="bg-white/95 border border-slate-200 rounded-2xl px-4 py-3 md:px-5 md:py-4 shadow-sm">
				<header class="flex items-start gap-3">
				<div class="h-9 w-9 rounded-full overflow-hidden bg-slate-100">
						<?php if ( $post_author ) : ?>
							<?php echo get_avatar( $post_author_id, 36, '', $post_author->display_name, array( 'class' => 'h-full w-full object-cover' ) ); ?>
					<?php endif; ?>
				</div>
				<div class="flex-1">
					<div class="flex items-center justify-between gap-2">
					<div>
						<div class="flex items-center gap-1.5">
						<span class="text-[13px] font-semibold text-slate-900"><?php echo esc_html( $post_author->display_name ?? 'Usuário' ); ?></span>
						<?php if ( $is_owner ) : ?>
						<span class="text-[11px] text-slate-400">@<?php echo esc_html( $post_author->user_login ?? '' ); ?> · <?php echo $post_author_id === $creator_id ? 'Responsável' : 'Moderação'; ?></span>
						<?php else : ?>
						<span class="text-[11px] text-slate-400">@<?php echo esc_html( $post_author->user_login ?? '' ); ?></span>
						<?php endif; ?>
						</div>
						<div class="flex items-center gap-2 text-[11px] text-slate-400">
						<span>há <?php echo esc_html( $post_date ); ?></span>
						<?php if ( $post_location ) : ?>
						<span class="h-1 w-1 rounded-full bg-slate-300"></span>
						<span class="inline-flex items-center gap-1">
							<i class="ri-map-pin-2-line text-[10px]"></i> <?php echo esc_html( $post_location ); ?>
						</span>
						<?php endif; ?>
						</div>
					</div>
					<?php if ( $is_notice ) : ?>
					<span class="inline-flex items-center gap-1 rounded-full bg-amber-50 text-amber-700 text-[10px] px-2 py-0.5">
						<i class="ri-notification-3-line text-[11px]"></i> aviso
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
				<div class="mt-3 rounded-xl overflow-hidden border border-slate-200 bg-slate-900">
				<img src="<?php echo esc_url( $post_image ); ?>" alt="" class="w-full h-auto" />
				</div>
				<?php endif; ?>

				<div class="mt-3 flex flex-wrap items-center justify-between gap-3">
					<?php if ( ! empty( $post_tags ) ) : ?>
				<div class="inline-flex flex-wrap gap-1.5 text-[11px]">
						<?php foreach ( array_slice( $post_tags, 0, 3 ) as $post_tag_item ) : ?>
					<span class="inline-flex items-center gap-1 rounded-full bg-slate-100 text-slate-700 px-2 py-0.5">
					<i class="ri-hashtag text-[10px]"></i> <?php echo esc_html( $post_tag_item ); ?>
					</span>
					<?php endforeach; ?>
				</div>
				<?php else : ?>
				<div></div>
				<?php endif; ?>
				<div class="flex items-center gap-4 text-[12px] text-slate-500">
					<button class="inline-flex items-center gap-1 hover:text-slate-900" data-action="like-post" data-post-id="<?php echo esc_attr( $post_item->ID ); ?>">
					<i class="ri-heart-3-line text-sm"></i> <?php echo esc_html( $post_likes ); ?>
					</button>
					<button class="inline-flex items-center gap-1 hover:text-slate-900" data-action="view-comments" data-post-id="<?php echo esc_attr( $post_item->ID ); ?>">
					<i class="ri-message-2-line text-sm"></i> <?php echo esc_html( $post_comments_count ); ?>
					</button>
					<button class="inline-flex items-center gap-1 hover:text-slate-900" data-action="share-post" data-post-id="<?php echo esc_attr( $post_item->ID ); ?>">
					<i class="ri-share-forward-line text-sm"></i>
					</button>
				</div>
				</div>

				<!-- Comentário em destaque -->
					<?php
					if ( ! empty( $featured_comment ) ) :
						$featured_item     = $featured_comment[0];
						$comment_author_id = (int) $featured_item->user_id;
						$comment_author    = $comment_author_id ? get_userdata( $comment_author_id ) : null;
						?>
				<div class="mt-3 border-t border-dashed border-slate-200 pt-3">
				<div class="flex items-start gap-2.5">
					<div class="h-7 w-7 rounded-full overflow-hidden bg-slate-100">
						<?php if ( $comment_author ) : ?>
							<?php echo get_avatar( $comment_author_id, 28, '', $comment_author->display_name, array( 'class' => 'h-full w-full object-cover' ) ); ?>
					<?php else : ?>
					<div class="h-full w-full bg-slate-200 flex items-center justify-center">
						<i class="ri-user-line text-xs text-slate-400"></i>
					</div>
					<?php endif; ?>
					</div>
					<div class="flex-1">
					<div class="flex items-center gap-1.5">
						<span class="text-[12px] font-semibold text-slate-900"><?php echo esc_html( $comment_author ? $comment_author->display_name : $featured_item->comment_author ); ?></span>
						<span class="text-[10px] text-slate-400">há <?php echo esc_html( human_time_diff( strtotime( $featured_item->comment_date ), time() ) ); ?></span>
					</div>
					<p class="text-[12px] text-slate-700 leading-relaxed">
						<?php echo esc_html( wp_trim_words( $featured_item->comment_content, 30 ) ); ?>
					</p>
					</div>
				</div>
				</div>
						<?php endif; ?>
			</article>
				<?php endforeach; ?>
			<?php else : ?>
			<!-- Empty state -->
			<div class="bg-white/95 border border-slate-200 rounded-2xl px-4 py-8 shadow-sm text-center">
				<div class="h-16 w-16 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
				<i class="ri-chat-3-line text-3xl text-slate-300"></i>
				</div>
				<h3 class="text-[14px] font-semibold text-slate-900 mb-1">Nenhuma publicação ainda</h3>
				<p class="text-[13px] text-slate-500">Seja o primeiro a compartilhar algo com a comunidade!</p>
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
			<button class="h-14 w-14 rounded-full bg-slate-900 text-white flex items-center justify-center shadow-[0_8px_20px_-6px_rgba(15,23,42,0.6)]" data-action="mobile-add-menu">
				<i class="ri-add-line text-3xl"></i>
			</button>
			</div>
			<a href="<?php echo esc_url( home_url( '/comunidades/' ) ); ?>" class="nav-btn active w-14 pb-1">
			<i class="ri-group-line"></i>
			<span>Comunidade</span>
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
	// Join community
	document.querySelectorAll('[data-action="join-community"]').forEach(btn => {
		btn.addEventListener('click', async function() {
			const communityId = this.dataset.communityId;
			try {
				const response = await fetch('<?php echo esc_url( rest_url( 'apollo-social/v1/community/join' ) ); ?>', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
					},
					body: JSON.stringify({ community_id: communityId })
				});
				const data = await response.json();
				if (data.success) {
					location.reload();
				} else {
					alert(data.message || 'Erro ao entrar na comunidade');
				}
			} catch (e) {
				console.error(e);
			}
		});
	});

	// Publish post
	document.querySelectorAll('[data-action="publish-post"]').forEach(btn => {
		btn.addEventListener('click', async function() {
			const communityId = this.dataset.communityId;
			const content = document.getElementById('community-post-content')?.value;
			if (!content?.trim()) {
				alert('Digite algo para publicar');
				return;
			}
			try {
				const response = await fetch('<?php echo esc_url( rest_url( 'apollo-social/v1/community/post' ) ); ?>', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
					},
					body: JSON.stringify({ community_id: communityId, content: content })
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
					const count = this.querySelector('span') || this.childNodes[1];
					if (data.liked) {
						icon.className = 'ri-heart-3-fill text-sm text-red-500';
					} else {
						icon.className = 'ri-heart-3-line text-sm';
					}
					if (count) count.textContent = ' ' + data.count;
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
