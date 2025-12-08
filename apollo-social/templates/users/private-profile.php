<?php
/**
 * Private Profile Template - User Dashboard
 * STRICT MODE: 100% UNI.CSS conformance
 *
 * @package Apollo_Social
 * @subpackage Users
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

// Ensure user is logged in.
if ( ! is_user_logged_in() ) {
	auth_redirect();
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
			[],
			'2.0.0'
		);

		// Remix Icons.
		wp_enqueue_style(
			'remixicon',
			'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css',
			[],
			'4.7.0'
		);

		// Base JS.
		wp_enqueue_script(
			'apollo-base-js',
			'https://assets.apollo.rio.br/base.js',
			[],
			'2.0.0',
			true
		);

		// Inline profile-specific styles.
		$profile_css = '
			.ap-page-profile .ap-main { padding: 24px 0; }
			.ap-profile-header { display: flex; flex-direction: column; gap: 1.5rem; }
			@media (min-width: 768px) { .ap-profile-header { flex-direction: row; align-items: flex-start; } }
			.ap-profile-user { display: flex; align-items: flex-start; gap: 1rem; flex: 1; }
			.ap-profile-info { flex: 1; min-width: 0; }
			.ap-profile-stats { display: flex; flex-direction: column; align-items: flex-start; gap: 0.75rem; }
			@media (min-width: 768px) { .ap-profile-stats { align-items: flex-end; } }
			.ap-stats-grid { display: flex; flex-wrap: wrap; gap: 0.5rem; }
			.ap-stat-mini { display: flex; flex-direction: column; align-items: center; padding: 0.25rem 0.5rem; background: var(--ap-bg-muted); border-radius: var(--ap-radius-md); min-width: 3rem; }
			.ap-stat-label { font-size: 0.625rem; text-transform: uppercase; color: var(--ap-text-muted); letter-spacing: 0.05em; }
			.ap-stat-value { font-size: 0.875rem; font-weight: 700; color: var(--ap-text); }
			.ap-avatar-gradient { background: linear-gradient(135deg, var(--ap-orange-500), var(--ap-pink-500)); padding: 3px; }
			.ap-avatar-gradient img { border-radius: 50%; }
			.ap-avatar-badge { position: absolute; bottom: 0; right: 0; width: 1.25rem; height: 1.25rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.625rem; border: 2px solid var(--ap-bg); }
			.ap-chip { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; font-size: 0.75rem; background: var(--ap-bg-muted); border-radius: var(--ap-radius-full); color: var(--ap-text-muted); }
			.ap-chip-sm { font-size: 0.625rem; padding: 0.125rem 0.375rem; }
            ';
			wp_add_inline_style( 'apollo-uni-css', $profile_css );

		// Inline profile-specific scripts.
		$profile_js = "
			document.addEventListener('DOMContentLoaded', function() {
				const tabs = document.querySelectorAll('[role=\"tab\"]');
				const panels = document.querySelectorAll('[role=\"tabpanel\"]');
				tabs.forEach(function(tab) {
					tab.addEventListener('click', function() {
						tabs.forEach(function(t) { t.classList.remove('active'); t.setAttribute('aria-selected', 'false'); });
						panels.forEach(function(p) { p.classList.remove('active'); });
						this.classList.add('active');
						this.setAttribute('aria-selected', 'true');
						var target = this.getAttribute('data-tab-target');
						var panel = document.querySelector('[data-tab-panel=\"' + target + '\"]');
						if (panel) { panel.classList.add('active'); }
					});
				});
			});
		";
		wp_add_inline_script( 'apollo-base-js', $profile_js );
	},
	10
);

// Trigger enqueue if not already done.
if ( ! did_action( 'wp_enqueue_scripts' ) ) {
	do_action( 'wp_enqueue_scripts' );
}

// User data - avoid overriding WP globals.
$user_obj      = wp_get_current_user();
$user_id       = $user_obj->ID;
$avatar_url    = get_avatar_url( $user_id, [ 'size' => 200 ] );
$display_name  = $user_obj->display_name;
$user_username = $user_obj->user_login;
$user_initials = strtoupper( substr( $display_name, 0, 2 ) );

// User meta - avoid short ternaries.
$user_bio_raw = get_user_meta( $user_id, 'description', true );
$user_bio     = ! empty( $user_bio_raw ) ? $user_bio_raw : __( 'Conectando eventos, comunidades e dados da cena eletrônica do Rio.', 'apollo-social' );

$user_location_raw = get_user_meta( $user_id, 'user_location', true );
$user_location     = ! empty( $user_location_raw ) ? $user_location_raw : 'Rio de Janeiro';

$membership_level_raw = get_user_meta( $user_id, 'membership_level', true );
$membership_level     = ! empty( $membership_level_raw ) ? $membership_level_raw : 'clubber';

// Stats (placeholder - replace with actual queries).
$stats = [
	'producer'  => 3,
	'favorited' => 11,
	'posts'     => 5,
	'comments'  => 37,
	'liked'     => 26,
];

// Membership labels.
$membership_labels = [
	'clubber'  => __( 'Clubber', 'apollo-social' ),
	'dj'       => __( 'DJ', 'apollo-social' ),
	'producer' => __( 'Producer', 'apollo-social' ),
	'promoter' => __( 'Promoter', 'apollo-social' ),
];

// Get WordPress head.
wp_head();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="h-full">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<title><?php echo esc_html__( 'Apollo :: Perfil Social', 'apollo-social' ); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'ap-body' ); ?>>
<div class="ap-page ap-page-profile">

	<!-- Top Header -->
	<header class="ap-header ap-header-sticky">
		<div class="ap-container ap-flex ap-flex-between ap-flex-center-v">
			<div class="ap-flex ap-gap-3 ap-flex-center-v">
				<a href="<?php echo esc_url( home_url() ); ?>"
					class="ap-btn ap-btn-icon"
					style="background: var(--ap-orange-500); color: white;"
					data-ap-tooltip="<?php esc_attr_e( 'Voltar ao início', 'apollo-social' ); ?>">
					<i class="ri-slack-line"></i>
				</a>
				<div>
					<span class="ap-text-xs ap-text-muted ap-text-uppercase"><?php esc_html_e( 'Rede Social Cultural Carioca', 'apollo-social' ); ?></span>
					<span class="ap-text-sm ap-text-bold">@<?php echo esc_html( $user_username ); ?> · Apollo::rio</span>
				</div>
			</div>

			<div class="ap-flex ap-gap-2 ap-flex-center-v">
				<a href="<?php echo esc_url( home_url( '/id/' . $user_username ) ); ?>"
					class="ap-btn ap-btn-outline ap-btn-sm ap-hide-mobile"
					data-ap-tooltip="<?php esc_attr_e( 'Ver como outros usuários veem seu perfil', 'apollo-social' ); ?>">
					<i class="ri-eye-line"></i>
					<span><?php esc_html_e( 'Ver como visitante', 'apollo-social' ); ?></span>
				</a>
				<a href="<?php echo esc_url( home_url( '/id/' . $user_username ) ); ?>"
					class="ap-btn ap-btn-primary ap-btn-sm"
					data-ap-tooltip="<?php esc_attr_e( 'Abrir sua página pública', 'apollo-social' ); ?>">
					<i class="ri-external-link-line"></i>
					<span><?php esc_html_e( 'Página pública', 'apollo-social' ); ?></span>
				</a>
				<div class="ap-avatar ap-avatar-sm" data-ap-tooltip="<?php echo esc_attr( $display_name ); ?>">
					<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $display_name ); ?>">
				</div>
			</div>
		</div>
	</header>

	<!-- Main Content -->
	<main class="ap-main">
		<div class="ap-container">
			<div class="ap-grid ap-grid-sidebar">

				<!-- Left Column: Profile + Tabs -->
				<div class="ap-col-main">

					<!-- Profile Card -->
					<section class="ap-card ap-card-profile ap-mb-4">
						<div class="ap-card-body">
							<div class="ap-profile-header">
								<!-- User Info -->
								<div class="ap-profile-user">
									<div class="ap-avatar ap-avatar-xl ap-avatar-gradient">
										<img src="<?php echo esc_url( $avatar_url ); ?>" alt="Avatar">
										<span class="ap-avatar-badge ap-badge-success" data-ap-tooltip="Perfil verificado">
											<i class="ri-flashlight-fill"></i>
										</span>
									</div>
									<div class="ap-profile-info">
										<div class="ap-flex ap-gap-2 ap-flex-center-v ap-flex-wrap">
											<h1 class="ap-heading-4"><?php echo esc_html( $display_name ); ?></h1>
											<span class="ap-badge ap-badge-muted">
												<i class="ri-music-2-line"></i>
												<?php echo esc_html( $membership_labels[ $membership_level ] ?? 'Clubber' ); ?>
											</span>
										</div>
										<p class="ap-text-sm ap-text-muted ap-mt-1"><?php echo esc_html( $user_bio ); ?></p>
										<div class="ap-flex ap-gap-2 ap-flex-wrap ap-mt-2">
											<span class="ap-chip">
												<i class="ri-map-pin-line"></i>
												<?php echo esc_html( $user_location ); ?>
											</span>
											<span class="ap-chip">
												<i class="ri-vip-crown-2-line"></i>
												Industry access
											</span>
											<span class="ap-chip">
												<i class="ri-group-line"></i>
												3 núcleos · 8 comunidades
											</span>
										</div>
									</div>
								</div>

								<!-- Stats -->
								<div class="ap-profile-stats">
									<div class="ap-stats-grid">
										<?php foreach ( $stats as $key => $value ) : ?>
										<div class="ap-stat-mini" data-ap-tooltip="Total de <?php echo esc_attr( $key ); ?>">
											<span class="ap-stat-label"><?php echo esc_html( ucfirst( $key ) ); ?></span>
											<span class="ap-stat-value"><?php echo esc_html( $value ); ?></span>
										</div>
										<?php endforeach; ?>
									</div>
									<a href="<?php echo esc_url( home_url( '/painel/editar' ) ); ?>"
										class="ap-btn ap-btn-outline ap-btn-sm ap-btn-block ap-mt-3"
										data-ap-tooltip="Editar informações do perfil">
										<i class="ri-pencil-line"></i>
										Editar perfil
									</a>
								</div>
							</div>
						</div>
					</section>

					<!-- Tabs Section -->
					<section class="ap-card">
						<div class="ap-card-body">
							<!-- Tab Navigation -->
							<div class="ap-tabs-header">
								<div class="ap-tabs-nav" role="tablist">
									<button class="ap-tab active" data-tab-target="events" role="tab" aria-selected="true" data-ap-tooltip="Eventos que você favoritou">
										<i class="ri-heart-3-line"></i>
										<span>Favoritos</span>
									</button>
									<button class="ap-tab" data-tab-target="metrics" role="tab" data-ap-tooltip="Suas métricas de atividade">
										<i class="ri-bar-chart-2-line"></i>
										<span>Métricas</span>
									</button>
									<button class="ap-tab" data-tab-target="nucleo" role="tab" data-ap-tooltip="Grupos privados que você participa">
										<i class="ri-lock-2-line"></i>
										<span>Núcleos</span>
									</button>
									<button class="ap-tab" data-tab-target="communities" role="tab" data-ap-tooltip="Comunidades públicas">
										<i class="ri-community-line"></i>
										<span>Comunidades</span>
									</button>
									<button class="ap-tab" data-tab-target="docs" role="tab" data-ap-tooltip="Documentos e contratos">
										<i class="ri-file-text-line"></i>
										<span>Documentos</span>
									</button>
								</div>
							</div>

							<!-- Tab Panels -->
							<div class="ap-tabs-content ap-mt-4">

								<!-- Events Tab -->
								<div class="ap-tab-panel active" data-tab-panel="events" role="tabpanel">
									<div class="ap-section-header ap-mb-3">
										<div>
											<h2 class="ap-heading-5">Eventos favoritados</h2>
											<p class="ap-text-sm ap-text-muted">Eventos marcados como <strong>Ir</strong>, <strong>Talvez</strong> ou salvos.</p>
										</div>
										<button class="ap-btn ap-btn-outline ap-btn-sm" data-ap-tooltip="Filtrar eventos por data">
											<i class="ri-filter-3-line"></i>
											Filtrar
										</button>
									</div>

									<div class="ap-grid ap-grid-2 ap-gap-3">
										<!-- Event Card 1 -->
										<article class="ap-card ap-card-hover">
											<div class="ap-card-body">
												<div class="ap-flex ap-flex-between ap-gap-2">
													<div>
														<h3 class="ap-card-title">Dismantle · Puro Suco do Caos</h3>
														<p class="ap-text-xs ap-text-muted">Copacabana · 22:00 · sexta</p>
														<p class="ap-text-sm ap-text-muted ap-mt-1">Noite longa de techno, house e caos carioca.</p>
													</div>
													<div class="ap-text-right">
														<span class="ap-badge ap-badge-primary" data-ap-tooltip="<?php esc_attr_e( 'Você confirmou presença', 'apollo-social' ); ?>">Ir</span>
														<p class="ap-text-xs ap-text-muted ap-mt-1">+143 pessoas</p>
													</div>
												</div>
												<div class="ap-card-footer ap-mt-3">
													<div class="ap-flex ap-gap-2">
														<span class="ap-chip ap-chip-sm">
															<i class="ri-moon-clear-line"></i> After
														</span>
														<span class="ap-chip ap-chip-sm">
															<i class="ri-map-pin-2-line"></i> Copacabana
														</span>
													</div>
													<a href="#" class="ap-btn ap-btn-ghost ap-btn-sm" data-ap-tooltip="Ver detalhes do evento">
														<i class="ri-external-link-line"></i>
														Ver
													</a>
												</div>
											</div>
										</article>

										<!-- Event Card 2 -->
										<article class="ap-card ap-card-hover">
											<div class="ap-card-body">
												<div class="ap-flex ap-flex-between ap-gap-2">
													<div>
														<h3 class="ap-card-title">Afters em Botafogo · Apollo</h3>
														<p class="ap-text-xs ap-text-muted">Botafogo · 04:30 · domingo</p>
														<p class="ap-text-sm ap-text-muted ap-mt-1">Pós-festa com grooves leves, disco e house.</p>
													</div>
													<div class="ap-text-right">
														<span class="ap-badge ap-badge-warning" data-ap-tooltip="<?php esc_attr_e( 'Presença não confirmada', 'apollo-social' ); ?>">Talvez</span>
														<p class="ap-text-xs ap-text-muted ap-mt-1">+57 pessoas</p>
													</div>
												</div>
												<div class="ap-card-footer ap-mt-3">
													<div class="ap-flex ap-gap-2">
														<span class="ap-chip ap-chip-sm">
															<i class="ri-vip-diamond-line"></i> Lista amiga
														</span>
													</div>
													<a href="#" class="ap-btn ap-btn-ghost ap-btn-sm" data-ap-tooltip="Ver detalhes do evento">
														<i class="ri-external-link-line"></i>
														Ver
													</a>
												</div>
											</div>
										</article>
									</div>
								</div>

								<!-- Metrics Tab -->
								<div class="ap-tab-panel" data-tab-panel="metrics" role="tabpanel">
									<div class="ap-empty-state">
										<i class="ri-bar-chart-2-line"></i>
										<h3>Métricas em desenvolvimento</h3>
										<p>Seus dados de performance estão sendo calculados...</p>
									</div>
								</div>

								<!-- Nucleos Tab -->
								<div class="ap-tab-panel" data-tab-panel="nucleo" role="tabpanel">
									<div class="ap-section-header ap-mb-3">
										<div>
											<h2 class="ap-heading-5">Núcleos privados</h2>
											<p class="ap-text-sm ap-text-muted">Espaços de trabalho fechados, visíveis apenas para convidados.</p>
										</div>
										<div class="ap-flex ap-gap-2">
											<button class="ap-btn ap-btn-primary ap-btn-sm" data-ap-tooltip="Criar um novo núcleo privado">
												<i class="ri-team-line"></i>
												Criar núcleo
											</button>
											<button class="ap-btn ap-btn-outline ap-btn-sm" data-ap-tooltip="Gerenciar permissões">
												<i class="ri-shield-keyhole-line"></i>
												Acessos
											</button>
										</div>
									</div>

									<div class="ap-grid ap-grid-2 ap-gap-3">
										<article class="ap-card ap-card-hover">
											<div class="ap-card-body">
												<div class="ap-flex ap-flex-between ap-gap-2">
													<div>
														<div class="ap-flex ap-gap-2 ap-flex-center-v ap-mb-1">
															<h3 class="ap-card-title">Núcleo Cena::rio</h3>
															<span class="ap-badge ap-badge-dark" data-ap-tooltip="<?php esc_attr_e( 'Grupo privado - apenas membros', 'apollo-social' ); ?>">
																<i class="ri-lock-2-line"></i> Privado
															</span>
														</div>
														<p class="ap-text-sm ap-text-muted">Registro vivo da cena eletrônica carioca.</p>
													</div>
													<div class="ap-text-right ap-text-xs ap-text-muted">
														<p>12 membros</p>
														<p>3 eventos</p>
													</div>
												</div>
												<div class="ap-card-footer ap-mt-3">
													<div class="ap-flex ap-gap-2">
														<span class="ap-chip ap-chip-sm">
															<i class="ri-database-2-line"></i> Sync
														</span>
													</div>
													<a href="#" class="ap-btn ap-btn-ghost ap-btn-sm" data-ap-tooltip="Entrar no núcleo">
														<i class="ri-arrow-right-line"></i>
														Entrar
													</a>
												</div>
											</div>
										</article>

										<article class="ap-card ap-card-hover">
											<div class="ap-card-body">
												<div class="ap-flex ap-flex-between ap-gap-2">
													<div>
														<div class="ap-flex ap-gap-2 ap-flex-center-v ap-mb-1">
															<h3 class="ap-card-title">Produção & Tech</h3>
															<span class="ap-badge ap-badge-dark" data-ap-tooltip="<?php esc_attr_e( 'Grupo privado - apenas membros', 'apollo-social' ); ?>">
																<i class="ri-lock-2-line"></i> Privado
															</span>
														</div>
														<p class="ap-text-sm ap-text-muted">Automação e integrações Apollo.</p>
													</div>
													<div class="ap-text-right ap-text-xs ap-text-muted">
														<p>7 membros</p>
														<p>2 projetos</p>
													</div>
												</div>
												<div class="ap-card-footer ap-mt-3">
													<div class="ap-flex ap-gap-2">
														<span class="ap-chip ap-chip-sm">
															<i class="ri-code-s-slash-line"></i> Stack
														</span>
													</div>
													<a href="#" class="ap-btn ap-btn-ghost ap-btn-sm" data-ap-tooltip="Abrir board">
														<i class="ri-arrow-right-line"></i>
														Board
													</a>
												</div>
											</div>
										</article>
									</div>
								</div>

								<!-- Communities Tab -->
								<div class="ap-tab-panel" data-tab-panel="communities" role="tabpanel">
									<div class="ap-section-header ap-mb-3">
										<div>
											<h2 class="ap-heading-5">Comunidades públicas</h2>
											<p class="ap-text-sm ap-text-muted">Grupos abertos para a cena.</p>
										</div>
										<button class="ap-btn ap-btn-primary ap-btn-sm" data-ap-tooltip="Criar uma nova comunidade">
											<i class="ri-community-line"></i>
											Nova comunidade
										</button>
									</div>

									<div class="ap-grid ap-grid-3 ap-gap-3">
										<article class="ap-card ap-card-hover">
											<div class="ap-card-body">
												<div class="ap-flex ap-gap-2 ap-flex-center-v ap-mb-1">
													<h3 class="ap-card-title">Tropicalis :: RJ</h3>
													<span class="ap-badge ap-badge-success" data-ap-tooltip="<?php esc_attr_e( 'Comunidade aberta para todos', 'apollo-social' ); ?>">
														<i class="ri-sun-line"></i> Aberta
													</span>
												</div>
												<p class="ap-text-sm ap-text-muted">Guia vivo de festas eletrônicas do Rio.</p>
												<div class="ap-card-footer ap-mt-3">
													<span class="ap-text-xs ap-text-muted">943 membros</span>
													<a href="#" class="ap-btn ap-btn-ghost ap-btn-sm" data-ap-tooltip="Ver comunidade">
														<i class="ri-arrow-right-line"></i>
													</a>
												</div>
											</div>
										</article>

										<article class="ap-card ap-card-hover">
											<div class="ap-card-body">
												<div class="ap-flex ap-gap-2 ap-flex-center-v ap-mb-1">
													<h3 class="ap-card-title">After Lovers</h3>
													<span class="ap-badge ap-badge-success" data-ap-tooltip="<?php esc_attr_e( 'Comunidade aberta para todos', 'apollo-social' ); ?>">
														<i class="ri-sparkling-2-line"></i> Aberta
													</span>
												</div>
												<p class="ap-text-sm ap-text-muted">Afters e rolês pela madrugada.</p>
												<div class="ap-card-footer ap-mt-3">
													<span class="ap-text-xs ap-text-muted">312 membros</span>
													<a href="#" class="ap-btn ap-btn-ghost ap-btn-sm" data-ap-tooltip="Ver comunidade">
														<i class="ri-arrow-right-line"></i>
													</a>
												</div>
											</div>
										</article>

										<article class="ap-card ap-card-hover">
											<div class="ap-card-body">
												<div class="ap-flex ap-gap-2 ap-flex-center-v ap-mb-1">
													<h3 class="ap-card-title">Produtores BR</h3>
													<span class="ap-badge ap-badge-success" data-ap-tooltip="<?php esc_attr_e( 'Comunidade aberta para todos', 'apollo-social' ); ?>">
														<i class="ri-global-line"></i> Aberta
													</span>
												</div>
												<p class="ap-text-sm ap-text-muted">Fórum sobre produção musical.</p>
												<div class="ap-card-footer ap-mt-3">
													<span class="ap-text-xs ap-text-muted">1.2k membros</span>
													<a href="#" class="ap-btn ap-btn-ghost ap-btn-sm" data-ap-tooltip="Ver comunidade">
														<i class="ri-arrow-right-line"></i>
													</a>
												</div>
											</div>
										</article>
									</div>
								</div>

								<!-- Documents Tab -->
								<div class="ap-tab-panel" data-tab-panel="docs" role="tabpanel">
									<div class="ap-section-header ap-mb-3">
										<div>
											<h2 class="ap-heading-5">Documentos</h2>
											<p class="ap-text-sm ap-text-muted">Contratos de DJ, staff, núcleos e parcerias.</p>
										</div>
										<div class="ap-flex ap-gap-2">
											<button class="ap-btn ap-btn-primary ap-btn-sm" data-ap-tooltip="Criar novo documento">
												<i class="ri-file-add-line"></i>
												Novo
											</button>
											<button class="ap-btn ap-btn-outline ap-btn-sm" data-ap-tooltip="Ver documentos pendentes de assinatura">
												<i class="ri-ink-bottle-line"></i>
												Assinar
											</button>
										</div>
									</div>

									<div class="ap-grid ap-grid-3 ap-gap-3">
										<article class="ap-card ap-card-hover">
											<div class="ap-card-body">
												<div class="ap-flex ap-flex-between ap-gap-2 ap-mb-2">
													<h3 class="ap-card-title ap-text-sm">Contrato DJ · Dismantle</h3>
													<span class="ap-badge ap-badge-warning" data-ap-tooltip="<?php esc_attr_e( 'Aguardando sua assinatura', 'apollo-social' ); ?>">Pendente</span>
												</div>
												<p class="ap-text-xs ap-text-muted">Prestação de serviços para set principal.</p>
												<div class="ap-card-footer ap-mt-3">
													<span class="ap-text-xs ap-text-muted">Atualizado: hoje</span>
													<a href="#" class="ap-btn ap-btn-ghost ap-btn-sm" data-ap-tooltip="Revisar documento">
														<i class="ri-edit-box-line"></i>
													</a>
												</div>
											</div>
										</article>

										<article class="ap-card ap-card-hover">
											<div class="ap-card-body">
												<div class="ap-flex ap-flex-between ap-gap-2 ap-mb-2">
													<h3 class="ap-card-title ap-text-sm">Acordo Núcleo Cena::rio</h3>
													<span class="ap-badge ap-badge-success" data-ap-tooltip="<?php esc_attr_e( 'Documento assinado', 'apollo-social' ); ?>">Assinado</span>
												</div>
												<p class="ap-text-xs ap-text-muted">Termos internos de coordenação.</p>
												<div class="ap-card-footer ap-mt-3">
													<span class="ap-text-xs ap-text-muted">03/11/2025</span>
													<a href="#" class="ap-btn ap-btn-ghost ap-btn-sm" data-ap-tooltip="Baixar PDF">
														<i class="ri-download-2-line"></i>
													</a>
												</div>
											</div>
										</article>

										<article class="ap-card ap-card-hover">
											<div class="ap-card-body">
												<div class="ap-flex ap-flex-between ap-gap-2 ap-mb-2">
													<h3 class="ap-card-title ap-text-sm">Ficha técnica · Staff</h3>
													<span class="ap-badge ap-badge-secondary" data-ap-tooltip="<?php esc_attr_e( 'Documento em rascunho', 'apollo-social' ); ?>">Rascunho</span>
												</div>
												<p class="ap-text-xs ap-text-muted">Formulário de funções e horários.</p>
												<div class="ap-card-footer ap-mt-3">
													<span class="ap-text-xs ap-text-muted">28/10/2025</span>
													<a href="#" class="ap-btn ap-btn-ghost ap-btn-sm" data-ap-tooltip="Abrir ficha">
														<i class="ri-arrow-right-up-line"></i>
													</a>
												</div>
											</div>
										</article>
									</div>
								</div>
							</div>
						</div>
					</section>
				</div>

				<!-- Right Sidebar -->
				<aside class="ap-col-sidebar">
					<!-- Quick Summary -->
					<section class="ap-card ap-mb-4">
						<div class="ap-card-body">
							<h3 class="ap-heading-5 ap-mb-3">Resumo rápido</h3>
							<ul class="ap-list ap-list-icon">
								<li>
									<i class="ri-calendar-event-line ap-text-muted"></i>
									<div>
										<span class="ap-text-sm ap-text-bold">Próximo compromisso</span>
										<span class="ap-text-xs ap-text-muted ap-block">Dismantle · sexta, 22:00</span>
									</div>
								</li>
								<li>
									<i class="ri-file-text-line ap-text-muted"></i>
									<div>
										<span class="ap-text-sm ap-text-bold">Docs pendentes</span>
										<span class="ap-text-xs ap-text-muted ap-block">1 contrato · 2 fichas</span>
									</div>
								</li>
								<li>
									<i class="ri-message-3-line ap-text-muted"></i>
									<div>
										<span class="ap-text-sm ap-text-bold">Mensagens</span>
										<span class="ap-text-xs ap-text-muted ap-block">4 não lidas</span>
									</div>
								</li>
							</ul>
							<a href="<?php echo esc_url( home_url( '/gestor' ) ); ?>"
								class="ap-btn ap-btn-primary ap-btn-block ap-mt-3"
								data-ap-tooltip="Abrir o Gestor Apollo">
								Abrir Gestor Apollo
								<i class="ri-arrow-right-line"></i>
							</a>
						</div>
					</section>

					<!-- Social Status -->
					<section class="ap-card">
						<div class="ap-card-body">
							<h3 class="ap-heading-5 ap-mb-3">Status social</h3>

							<div class="ap-mb-3">
								<p class="ap-text-sm ap-text-bold ap-mb-1">Núcleos ativos</p>
								<div class="ap-flex ap-gap-1 ap-flex-wrap">
									<span class="ap-chip ap-chip-sm">Cena::rio</span>
									<span class="ap-chip ap-chip-sm">Produção & Tech</span>
									<span class="ap-chip ap-chip-sm">Tropicalis Core</span>
								</div>
							</div>

							<div>
								<p class="ap-text-sm ap-text-bold ap-mb-1">Comunidades em destaque</p>
								<div class="ap-flex ap-gap-1 ap-flex-wrap">
									<span class="ap-chip ap-chip-sm ap-chip-success">Tropicalis :: RJ</span>
									<span class="ap-chip ap-chip-sm" style="background: #f3e8ff; color: #7c3aed;">After Lovers</span>
									<span class="ap-chip ap-chip-sm ap-chip-info">Produtores BR</span>
								</div>
							</div>
						</div>
					</section>
				</aside>
			</div>
		</div>
	</main>
</div>
<?php wp_footer(); ?>
</body>
</html>
