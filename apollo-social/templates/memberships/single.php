<?php
/**
 * Single Membership Template - /membership/{slug}
 * STRICT MODE: 100% UNI.CSS conformance
 *
 * @package Apollo_Social
 * @subpackage Memberships
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Enqueue UNI.CSS assets
if ( function_exists( 'apollo_enqueue_global_assets' ) ) {
	apollo_enqueue_global_assets();
}

$user_obj            = wp_get_current_user();
$user_id             = get_current_user_id();
$membership_slug_raw = get_query_var( 'membership_slug' );
$membership_slug     = ! empty( $membership_slug_raw ) ? $membership_slug_raw : 'clubber';

// Membership levels configuration
$membership_levels = array(
	'clubber'  => array(
		'icon'         => 'ri-user-line',
		'label'        => 'Clubber',
		'description'  => 'Acesso básico à comunidade Apollo - para todos os amantes da cena eletrônica.',
		'color'        => 'var(--ap-text-muted)',
		'requirements' => array( 'Cadastro completo', 'Verificação de email' ),
		'features'     => array(
			array(
				'icon'  => 'ri-user-3-line',
				'title' => 'Perfil Público',
				'desc'  => 'Crie seu perfil na comunidade',
			),
			array(
				'icon'  => 'ri-newspaper-line',
				'title' => 'Feed Social',
				'desc'  => 'Acompanhe posts e atualizações',
			),
			array(
				'icon'  => 'ri-heart-line',
				'title' => 'Favoritar Eventos',
				'desc'  => 'Salve eventos do seu interesse',
			),
			array(
				'icon'  => 'ri-team-line',
				'title' => 'Comunidades',
				'desc'  => 'Participe de grupos públicos',
			),
		),
	),
	'dj'       => array(
		'icon'         => 'ri-disc-line',
		'label'        => 'DJ',
		'description'  => 'Para DJs profissionais que querem expandir sua presença na cena carioca.',
		'color'        => 'var(--ap-orange-500)',
		'requirements' => array( 'Ser DJ ativo', 'Pelo menos 1 apresentação comprovada', 'Perfil com foto profissional' ),
		'features'     => array(
			array(
				'icon'  => 'ri-user-star-line',
				'title' => 'Página de Artista',
				'desc'  => 'Perfil profissional verificado',
			),
			array(
				'icon'  => 'ri-calendar-schedule-line',
				'title' => 'Agenda Pública',
				'desc'  => 'Mostre seus próximos sets',
			),
			array(
				'icon'  => 'ri-mail-send-line',
				'title' => 'Booking Requests',
				'desc'  => 'Receba solicitações de booking',
			),
			array(
				'icon'  => 'ri-line-chart-line',
				'title' => 'Analytics',
				'desc'  => 'Métricas do seu perfil',
			),
			array(
				'icon'  => 'ri-music-2-line',
				'title' => 'Sound System',
				'desc'  => 'Vincule sons e gêneros',
			),
			array(
				'icon'  => 'ri-file-text-line',
				'title' => 'Contratos',
				'desc'  => 'Assine contratos digitalmente',
			),
		),
	),
	'producer' => array(
		'icon'         => 'ri-music-2-line',
		'label'        => 'Producer',
		'description'  => 'Para produtores musicais que criam e lançam música eletrônica.',
		'color'        => '#a855f7',
		'requirements' => array( 'Portfolio de produções', 'Pelo menos 1 release', 'Links para plataformas' ),
		'features'     => array(
			array(
				'icon'  => 'ri-album-line',
				'title' => 'Portfolio',
				'desc'  => 'Showcase de suas produções',
			),
			array(
				'icon'  => 'ri-group-line',
				'title' => 'Colaborações',
				'desc'  => 'Conecte-se com outros produtores',
			),
			array(
				'icon'  => 'ri-disc-line',
				'title' => 'Releases',
				'desc'  => 'Gerencie seus lançamentos',
			),
			array(
				'icon'  => 'ri-bar-chart-box-line',
				'title' => 'Analytics',
				'desc'  => 'Métricas de alcance',
			),
		),
	),
	'promoter' => array(
		'icon'         => 'ri-megaphone-line',
		'label'        => 'Promoter',
		'description'  => 'Para produtores de eventos e festas da cena eletrônica.',
		'color'        => '#3b82f6',
		'requirements' => array( 'CNPJ ou MEI', 'Histórico de eventos', 'Referências na cena' ),
		'features'     => array(
			array(
				'icon'  => 'ri-calendar-event-line',
				'title' => 'Criar Eventos',
				'desc'  => 'Publique eventos na plataforma',
			),
			array(
				'icon'  => 'ri-team-line',
				'title' => 'Gestão de Lineup',
				'desc'  => 'Monte e gerencie lineups',
			),
			array(
				'icon'  => 'ri-file-list-3-line',
				'title' => 'Contratos',
				'desc'  => 'Gere e envie contratos',
			),
			array(
				'icon'  => 'ri-money-dollar-circle-line',
				'title' => 'Vendas',
				'desc'  => 'Integração com vendas',
			),
			array(
				'icon'  => 'ri-group-2-line',
				'title' => 'Staff',
				'desc'  => 'Gerencie equipe',
			),
		),
	),
	'venue'    => array(
		'icon'         => 'ri-building-2-line',
		'label'        => 'Venue',
		'description'  => 'Para casas noturnas, clubs e espaços de eventos.',
		'color'        => '#10b981',
		'requirements' => array( 'Local físico', 'Alvará de funcionamento', 'Fotos do espaço' ),
		'features'     => array(
			array(
				'icon'  => 'ri-home-4-line',
				'title' => 'Página do Local',
				'desc'  => 'Perfil do seu espaço',
			),
			array(
				'icon'  => 'ri-calendar-line',
				'title' => 'Calendário',
				'desc'  => 'Agenda de eventos',
			),
			array(
				'icon'  => 'ri-bookmark-line',
				'title' => 'Booking',
				'desc'  => 'Receba solicitações',
			),
			array(
				'icon'  => 'ri-pie-chart-line',
				'title' => 'Analytics',
				'desc'  => 'Métricas do local',
			),
		),
	),
);

// Get current membership data
$membership            = isset( $membership_levels[ $membership_slug ] ) ? $membership_levels[ $membership_slug ] : $membership_levels['clubber'];
$user_membership_raw   = get_user_meta( $user_id, 'membership_level', true );
$user_membership       = ! empty( $user_membership_raw ) ? $user_membership_raw : 'clubber';
$is_current            = ( $user_membership === $membership_slug );
$membership_status_raw = get_user_meta( $user_id, 'membership_status', true );
$membership_status     = ! empty( $membership_status_raw ) ? $membership_status_raw : 'active';
$has_pending_request   = ( $membership_status === 'pending' );

get_header();
?>

<div class="ap-page ap-page-membership-single">
	<!-- Hero Section -->
	<header class="ap-hero ap-hero-sm" style="background: linear-gradient(135deg, <?php echo esc_attr( $membership['color'] ); ?>20, var((--bg-main)-surface));">
		<div class="ap-container">
			<div class="ap-breadcrumb">
				<a href="<?php echo esc_url( home_url() ); ?>" data-ap-tooltip="Voltar ao início">
					<i class="ri-home-4-line"></i>
				</a>
				<i class="ri-arrow-right-s-line"></i>
				<a href="<?php echo esc_url( home_url( '/membership' ) ); ?>" data-ap-tooltip="Ver todos os níveis">
					Memberships
				</a>
				<i class="ri-arrow-right-s-line"></i>
				<span><?php echo esc_html( $membership['label'] ); ?></span>
			</div>

			<div class="ap-hero-content ap-text-center">
				<div class="ap-avatar ap-avatar-xl ap-mx-auto" style="background: <?php echo esc_attr( $membership['color'] ); ?>;">
					<i class="<?php echo esc_attr( $membership['icon'] ); ?>" style="color: white; font-size: 2rem;"></i>
				</div>
				<h1 class="ap-heading-1" style="color: <?php echo esc_attr( $membership['color'] ); ?>;">
					<?php echo esc_html( $membership['label'] ); ?>
				</h1>
				<p class="ap-text-lg ap-text-muted"><?php echo esc_html( $membership['description'] ); ?></p>

				<?php if ( $is_current ) : ?>
				<div class="ap-badge ap-badge-success ap-badge-lg">
					<i class="ri-checkbox-circle-line"></i>
					Este é seu nível atual
				</div>
				<?php elseif ( $has_pending_request ) : ?>
				<div class="ap-badge ap-badge-warning ap-badge-lg">
					<i class="ri-time-line"></i>
					Solicitação pendente
				</div>
				<?php endif; ?>
			</div>
		</div>
	</header>

	<!-- Features Grid -->
	<section class="ap-section">
		<div class="ap-container">
			<div class="ap-section-header">
				<h2 class="ap-heading-2">
					<i class="ri-star-line ap-text-accent"></i>
					Recursos Incluídos
				</h2>
			</div>

			<div class="ap-grid ap-grid-3 ap-gap-4">
				<?php foreach ( $membership['features'] as $feature ) : ?>
				<div class="ap-card ap-card-hover">
					<div class="ap-card-body ap-text-center">
						<div class="ap-avatar ap-avatar-md ap-mx-auto" style="background: <?php echo esc_attr( $membership['color'] ); ?>15;">
							<i class="<?php echo esc_attr( $feature['icon'] ); ?>" style="color: <?php echo esc_attr( $membership['color'] ); ?>;"></i>
						</div>
						<h3 class="ap-card-title"><?php echo esc_html( $feature['title'] ); ?></h3>
						<p class="ap-card-text ap-text-sm"><?php echo esc_html( $feature['desc'] ); ?></p>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- Requirements Section -->
	<?php if ( ! empty( $membership['requirements'] ) ) : ?>
	<section class="ap-section ap-bg-muted">
		<div class="ap-container">
			<div class="ap-grid ap-grid-2 ap-gap-6 ap-flex-center-v">
				<div>
					<h2 class="ap-heading-2">
						<i class="ri-checkbox-multiple-line ap-text-accent"></i>
						Requisitos
					</h2>
					<p class="ap-text-muted">Para se tornar <?php echo esc_html( $membership['label'] ); ?>, você precisa:</p>

					<ul class="ap-list ap-list-check ap-mt-4">
						<?php foreach ( $membership['requirements'] as $req ) : ?>
						<li>
							<i class="ri-check-double-line" style="color: <?php echo esc_attr( $membership['color'] ); ?>;"></i>
							<?php echo esc_html( $req ); ?>
						</li>
						<?php endforeach; ?>
					</ul>
				</div>

				<div class="ap-card">
					<div class="ap-card-body">
						<?php if ( ! is_user_logged_in() ) : ?>
						<h3 class="ap-card-title">Faça login para solicitar</h3>
						<p class="ap-card-text">Você precisa estar logado para solicitar este membership.</p>
						<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"
							class="ap-btn ap-btn-primary ap-btn-block ap-mt-3"
							data-ap-tooltip="Fazer login na plataforma">
							<i class="ri-login-box-line"></i>
							Fazer Login
						</a>

						<?php elseif ( $is_current ) : ?>
						<h3 class="ap-card-title">Você já possui este nível!</h3>
						<p class="ap-card-text">Aproveite todos os recursos disponíveis para você.</p>
						<a href="<?php echo esc_url( home_url( '/painel' ) ); ?>"
							class="ap-btn ap-btn-secondary ap-btn-block ap-mt-3"
							data-ap-tooltip="Acessar seu painel">
							<i class="ri-dashboard-line"></i>
							Ir para o Painel
						</a>

						<?php elseif ( $has_pending_request ) : ?>
						<h3 class="ap-card-title">Solicitação em análise</h3>
						<p class="ap-card-text">Sua solicitação está sendo analisada. Você receberá uma notificação em breve.</p>
						<div class="ap-progress ap-mt-3">
							<div class="ap-progress-bar" style="width: 60%; background: <?php echo esc_attr( $membership['color'] ); ?>;"></div>
						</div>
						<p class="ap-text-xs ap-text-muted ap-mt-2">Tempo médio: 24-48 horas</p>

						<?php elseif ( $membership_slug === 'clubber' ) : ?>
						<h3 class="ap-card-title">Nível Básico</h3>
						<p class="ap-card-text">Este é o nível padrão para todos os usuários cadastrados.</p>

						<?php else : ?>
						<h3 class="ap-card-title">Solicitar Upgrade</h3>
						<p class="ap-card-text">Preencha os requisitos e solicite seu upgrade.</p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ap-mt-3">
							<?php wp_nonce_field( 'apollo_membership_upgrade', 'membership_nonce' ); ?>
							<input type="hidden" name="action" value="apollo_request_membership_upgrade">
							<input type="hidden" name="membership_level" value="<?php echo esc_attr( $membership_slug ); ?>">

							<div class="ap-form-group">
								<label class="ap-form-label">Por que você quer este nível?</label>
								<textarea name="membership_reason"
											class="ap-form-input"
											rows="3"
											required
											placeholder="Descreva sua atuação na cena..."
											data-ap-tooltip="Explique brevemente sua experiência"></textarea>
							</div>

							<button type="submit"
									class="ap-btn ap-btn-primary ap-btn-block"
									style="background: <?php echo esc_attr( $membership['color'] ); ?>;"
									data-ap-tooltip="Enviar solicitação para análise">
								<i class="ri-send-plane-line"></i>
								Enviar Solicitação
							</button>
						</form>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<!-- Back Link -->
	<section class="ap-section ap-text-center">
		<a href="<?php echo esc_url( home_url( '/membership' ) ); ?>"
			class="ap-btn ap-btn-outline"
			data-ap-tooltip="Ver todos os níveis disponíveis">
			<i class="ri-arrow-left-line"></i>
			Ver Todos os Níveis
		</a>
	</section>
</div>

<?php get_footer(); ?>
