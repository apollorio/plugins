<?php
/**
 * Memberships Archive Template - /membership/
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

$current_user = wp_get_current_user();
$user_id      = get_current_user_id();

// Membership levels
$membership_levels = array(
	'clubber'  => array(
		'icon'        => 'ri-user-line',
		'label'       => 'Clubber',
		'description' => 'Acesso básico à comunidade Apollo',
		'color'       => 'var(--ap-text-muted)',
		'features'    => array( 'Perfil público', 'Feed social', 'Favoritar eventos' ),
	),
	'dj'       => array(
		'icon'        => 'ri-disc-line',
		'label'       => 'DJ',
		'description' => 'Artista verificado na plataforma',
		'color'       => 'var(--ap-orange-500)',
		'features'    => array( 'Página de artista', 'Agenda pública', 'Booking requests', 'Analytics' ),
	),
	'producer' => array(
		'icon'        => 'ri-music-2-line',
		'label'       => 'Producer',
		'description' => 'Produtor musical verificado',
		'color'       => '#a855f7',
		'features'    => array( 'Portfolio de tracks', 'Colaborações', 'Releases', 'Analytics' ),
	),
	'promoter' => array(
		'icon'        => 'ri-megaphone-line',
		'label'       => 'Promoter',
		'description' => 'Produtor de eventos verificado',
		'color'       => '#3b82f6',
		'features'    => array( 'Criar eventos', 'Gestão de lineup', 'Contratos', 'Vendas' ),
	),
	'venue'    => array(
		'icon'        => 'ri-building-2-line',
		'label'       => 'Venue',
		'description' => 'Local/club verificado',
		'color'       => '#10b981',
		'features'    => array( 'Página do local', 'Calendário', 'Booking', 'Analytics' ),
	),
);

// Get user's current membership
$user_membership   = get_user_meta( $user_id, 'membership_level', true ) ?: 'clubber';
$membership_status = get_user_meta( $user_id, 'membership_status', true ) ?: 'active';

get_header();
?>

<div class="ap-page ap-page-memberships">
	<!-- Header -->
	<header class="ap-page-header">
		<div class="ap-container">
			<div class="ap-header-content">
				<div class="ap-breadcrumb">
					<a href="<?php echo esc_url( home_url() ); ?>" data-ap-tooltip="Voltar ao início">
						<i class="ri-home-4-line"></i>
					</a>
					<i class="ri-arrow-right-s-line"></i>
					<span>Memberships</span>
				</div>
				<h1 class="ap-heading-1">Apollo Memberships</h1>
				<p class="ap-text-muted">Escolha seu nível de acesso na comunidade Apollo</p>
			</div>
		</div>
	</header>

	<!-- Current Membership Banner -->
	<?php if ( is_user_logged_in() ) : ?>
	<section class="ap-section ap-bg-surface">
		<div class="ap-container">
			<div class="ap-card ap-card-highlight" style="border-left: 4px solid <?php echo esc_attr( $membership_levels[ $user_membership ]['color'] ); ?>;">
				<div class="ap-card-body">
					<div class="ap-flex ap-flex-between ap-flex-center-v">
						<div class="ap-flex ap-gap-3 ap-flex-center-v">
							<div class="ap-avatar ap-avatar-lg" style="background: <?php echo esc_attr( $membership_levels[ $user_membership ]['color'] ); ?>;">
								<i class="<?php echo esc_attr( $membership_levels[ $user_membership ]['icon'] ); ?>"></i>
							</div>
							<div>
								<h3 class="ap-heading-4">Seu nível atual</h3>
								<p class="ap-text-lg ap-text-bold" style="color: <?php echo esc_attr( $membership_levels[ $user_membership ]['color'] ); ?>;">
									<?php echo esc_html( $membership_levels[ $user_membership ]['label'] ); ?>
								</p>
							</div>
						</div>
						<div class="ap-flex ap-gap-2">
							<?php if ( $membership_status === 'pending' ) : ?>
							<span class="ap-badge ap-badge-warning" data-ap-tooltip="Aguardando aprovação do admin">
								<i class="ri-time-line"></i> Pendente
							</span>
							<?php else : ?>
							<span class="ap-badge ap-badge-success" data-ap-tooltip="Membership ativo">
								<i class="ri-checkbox-circle-line"></i> Ativo
							</span>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<!-- Membership Levels Grid -->
	<section class="ap-section">
		<div class="ap-container">
			<div class="ap-section-header ap-text-center">
				<h2 class="ap-heading-2">Níveis de Membership</h2>
				<p class="ap-text-muted">Cada nível oferece recursos exclusivos para sua atuação na cena</p>
			</div>

			<div class="ap-grid ap-grid-3 ap-gap-4">
				<?php
				foreach ( $membership_levels as $level_key => $level ) :
					$is_current = ( $user_membership === $level_key );
					?>
				<div class="ap-card ap-card-hover <?php echo $is_current ? 'ap-card-active' : ''; ?>" 
					style="<?php echo $is_current ? 'border-color: ' . esc_attr( $level['color'] ) . ';' : ''; ?>">
					
					<!-- Card Header -->
					<div class="ap-card-header">
						<div class="ap-avatar ap-avatar-md" style="background: <?php echo esc_attr( $level['color'] ); ?>;">
							<i class="<?php echo esc_attr( $level['icon'] ); ?>" style="color: white;"></i>
						</div>
						<?php if ( $is_current ) : ?>
						<span class="ap-badge ap-badge-primary" data-ap-tooltip="Este é seu nível atual">
							<i class="ri-check-line"></i> Atual
						</span>
						<?php endif; ?>
					</div>

					<!-- Card Body -->
					<div class="ap-card-body">
						<h3 class="ap-card-title" style="color: <?php echo esc_attr( $level['color'] ); ?>;">
							<?php echo esc_html( $level['label'] ); ?>
						</h3>
						<p class="ap-card-text"><?php echo esc_html( $level['description'] ); ?></p>
						
						<!-- Features List -->
						<ul class="ap-list ap-list-check">
							<?php foreach ( $level['features'] as $feature ) : ?>
							<li>
								<i class="ri-check-line" style="color: <?php echo esc_attr( $level['color'] ); ?>;"></i>
								<?php echo esc_html( $feature ); ?>
							</li>
							<?php endforeach; ?>
						</ul>
					</div>

					<!-- Card Footer -->
					<div class="ap-card-footer">
						<?php if ( $is_current ) : ?>
						<a href="<?php echo esc_url( home_url( '/membership/' . $level_key ) ); ?>" 
							class="ap-btn ap-btn-outline ap-btn-block"
							data-ap-tooltip="Ver detalhes do seu membership">
							<i class="ri-eye-line"></i>
							Ver Detalhes
						</a>
						<?php elseif ( $level_key === 'clubber' ) : ?>
						<span class="ap-btn ap-btn-secondary ap-btn-block ap-btn-disabled">
							<i class="ri-user-line"></i>
							Nível Básico
						</span>
						<?php else : ?>
						<a href="<?php echo esc_url( home_url( '/membership/' . $level_key ) ); ?>" 
							class="ap-btn ap-btn-primary ap-btn-block"
							data-ap-tooltip="Solicitar upgrade para <?php echo esc_attr( $level['label'] ); ?>">
							<i class="ri-arrow-up-circle-line"></i>
							Solicitar Upgrade
						</a>
						<?php endif; ?>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- FAQ Section -->
	<section class="ap-section ap-bg-muted">
		<div class="ap-container">
			<div class="ap-section-header ap-text-center">
				<h2 class="ap-heading-2">Perguntas Frequentes</h2>
			</div>
			
			<div class="ap-grid ap-grid-2 ap-gap-4">
				<div class="ap-card">
					<div class="ap-card-body">
						<h4 class="ap-card-title">
							<i class="ri-question-line ap-text-accent"></i>
							Como solicitar um upgrade?
						</h4>
						<p class="ap-card-text">
							Clique em "Solicitar Upgrade" no nível desejado. Um admin irá analisar seu perfil e aprovar a solicitação.
						</p>
					</div>
				</div>
				
				<div class="ap-card">
					<div class="ap-card-body">
						<h4 class="ap-card-title">
							<i class="ri-time-line ap-text-accent"></i>
							Quanto tempo leva a aprovação?
						</h4>
						<p class="ap-card-text">
							Geralmente entre 24-48 horas úteis. Você receberá uma notificação quando seu status for atualizado.
						</p>
					</div>
				</div>
				
				<div class="ap-card">
					<div class="ap-card-body">
						<h4 class="ap-card-title">
							<i class="ri-verified-badge-line ap-text-accent"></i>
							Preciso de verificação?
						</h4>
						<p class="ap-card-text">
							Sim, níveis acima de Clubber requerem verificação de identidade profissional na cena.
						</p>
					</div>
				</div>
				
				<div class="ap-card">
					<div class="ap-card-body">
						<h4 class="ap-card-title">
							<i class="ri-money-dollar-circle-line ap-text-accent"></i>
							Memberships são pagos?
						</h4>
						<p class="ap-card-text">
							Atualmente todos os níveis são gratuitos. Planos premium podem ser introduzidos no futuro.
						</p>
					</div>
				</div>
			</div>
		</div>
	</section>
</div>

<?php get_footer(); ?>
