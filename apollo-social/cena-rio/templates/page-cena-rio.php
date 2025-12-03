<?php
/**
 * Template: Cena::Rio Dashboard
 * STRICT MODE: 100% UNI.CSS conformance
 *
 * @package Apollo_Social
 * @subpackage CenaRio
 * @version 2.0.0
 * @uses UNI.CSS v5.2.0 - Dashboard components
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Verificar se usuário tem acesso
if ( ! current_user_can( 'cena-rio' ) && ! current_user_can( 'administrator' ) ) {
	wp_die(
		esc_html__( 'Acesso negado. Você precisa da permissão "cena-rio" para acessar esta página.', 'apollo-social' ),
		esc_html__( 'Acesso Negado', 'apollo-social' ),
		array( 'response' => 403 )
	);
}

$current_user  = wp_get_current_user();
$user_id       = $current_user->ID;
$user_initials = strtoupper( substr( $current_user->display_name, 0, 2 ) );

// Buscar documentos do usuário
$user_documents = array();
if ( class_exists( 'Apollo\CenaRio\CenaRioModule' ) && method_exists( 'Apollo\CenaRio\CenaRioModule', 'getUserDocuments' ) ) {
	$user_documents = Apollo\CenaRio\CenaRioModule::getUserDocuments( $user_id );
}

// Tab atual
$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'dashboard';

// Enqueue UNI.CSS assets
if ( function_exists( 'apollo_enqueue_global_assets' ) ) {
	apollo_enqueue_global_assets();
} else {
	wp_enqueue_style( 'apollo-uni-css', 'https://assets.apollo.rio.br/uni.css', array(), '5.2.0' );
	wp_enqueue_script( 'apollo-base-js', 'https://assets.apollo.rio.br/base.js', array(), '4.2.0', true );
}
wp_enqueue_style( 'remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css', array(), '4.7.0' );

get_header();
?>

<div class="ap-dashboard">
	<!-- Sidebar -->
	<aside class="ap-dashboard-sidebar">
		<!-- Logo & Brand -->
		<div class="ap-sidebar-header">
			<button type="button" class="ap-sidebar-logo" id="notificationsToggle" data-ap-tooltip="Abrir notificações">
				<i class="ri-notification-3-line"></i>
			</button>
			<div class="ap-sidebar-brand">
				<h2 class="ap-sidebar-title">Cena<span class="ap-text-accent">::</span>Rio</h2>
				<p class="ap-sidebar-subtitle">Dashboard</p>
			</div>
		</div>

		<!-- Navigation -->
		<nav class="ap-sidebar-nav">
			<a href="<?php echo esc_url( home_url( '/cena-rio' ) ); ?>" 
				class="ap-nav-item <?php echo $current_tab === 'dashboard' ? 'active' : ''; ?>"
				data-ap-tooltip="Visão geral do dashboard">
				<i class="ri-home-4-line"></i>
				<span>Início</span>
			</a>
			
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'documents', home_url( '/cena-rio' ) ) ); ?>" 
				class="ap-nav-item <?php echo $current_tab === 'documents' ? 'active' : ''; ?>"
				data-ap-tooltip="Gerenciar documentos e contratos">
				<i class="ri-file-text-line"></i>
				<span>Documentos</span>
				<?php if ( ! empty( $user_documents ) ) : ?>
				<span class="ap-badge ap-badge-primary"><?php echo count( $user_documents ); ?></span>
				<?php endif; ?>
			</a>
			
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'plans', home_url( '/cena-rio' ) ) ); ?>" 
				class="ap-nav-item <?php echo $current_tab === 'plans' ? 'active' : ''; ?>"
				data-ap-tooltip="Planos e cronogramas de eventos">
				<i class="ri-calendar-line"></i>
				<span>Planos de Evento</span>
			</a>
			
			<a href="<?php echo esc_url( home_url( '/chat' ) ); ?>" 
				class="ap-nav-item"
				data-ap-tooltip="Mensagens e conversas">
				<i class="ri-message-3-line"></i>
				<span>Mensagens</span>
			</a>
			
			<div class="ap-nav-divider"></div>
			
			<a href="<?php echo esc_url( home_url( '/id/' . $current_user->user_login ) ); ?>" 
				class="ap-nav-item"
				data-ap-tooltip="Ver seu perfil público">
				<i class="ri-user-line"></i>
				<span>Meu Perfil</span>
			</a>
			
			<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" 
				class="ap-nav-item ap-nav-item-danger"
				data-ap-tooltip="Sair da sua conta">
				<i class="ri-logout-box-r-line"></i>
				<span>Sair</span>
			</a>
		</nav>

		<!-- User Footer -->
		<div class="ap-sidebar-footer">
			<div class="ap-user-card">
				<div class="ap-avatar ap-avatar-sm" style="background: linear-gradient(135deg, var(--ap-orange-500), var(--ap-orange-600));">
					<span><?php echo esc_html( $user_initials ); ?></span>
				</div>
				<div class="ap-user-card-info">
					<p class="ap-user-card-name"><?php echo esc_html( $current_user->display_name ); ?></p>
					<p class="ap-user-card-email"><?php echo esc_html( $current_user->user_email ); ?></p>
				</div>
			</div>
		</div>
	</aside>

	<!-- Main Content -->
	<main class="ap-dashboard-main">
		<!-- Header -->
		<header class="ap-dashboard-header">
			<div class="ap-header-content">
				<div class="ap-header-title-group">
					<h1 class="ap-heading-2">
						<?php
						$greeting = '';
						$hour     = (int) current_time( 'G' );
						if ( $hour < 12 ) {
							$greeting = 'Bom dia';
						} elseif ( $hour < 18 ) {
							$greeting = 'Boa tarde';
						} else {
							$greeting = 'Boa noite';
						}
						echo esc_html( $greeting . ', ' . $current_user->display_name );
						?>
						!
					</h1>
					<p class="ap-text-muted">Gerencie seus documentos e planos de evento</p>
				</div>
				<div class="ap-header-actions">
					<button class="ap-btn ap-btn-outline ap-btn-sm" data-ap-tooltip="Buscar documentos">
						<i class="ri-search-line"></i>
						<span class="ap-hide-mobile">Buscar</span>
					</button>
				</div>
			</div>
		</header>

		<!-- Content Area -->
		<div class="ap-dashboard-content">
			<?php
			switch ( $current_tab ) {
				case 'documents':
					include APOLLO_SOCIAL_PLUGIN_DIR . 'cena-rio/templates/documents-list.php';
					break;
				case 'plans':
					include APOLLO_SOCIAL_PLUGIN_DIR . 'cena-rio/templates/plans-list.php';
					break;
				default:
					include APOLLO_SOCIAL_PLUGIN_DIR . 'cena-rio/templates/dashboard-content.php';
					break;
			}
			?>
		</div>
	</main>

	<!-- Notifications Modal -->
	<div class="ap-modal" id="notificationsModal">
		<div class="ap-modal-backdrop" data-close-modal></div>
		<div class="ap-modal-content ap-modal-sm">
			<div class="ap-modal-header">
				<h3 class="ap-modal-title">
					<i class="ri-notification-3-line"></i>
					Centro de Notificações
				</h3>
				<button class="ap-btn-icon-sm" data-close-modal data-ap-tooltip="Fechar">
					<i class="ri-close-line"></i>
				</button>
			</div>
			<div class="ap-modal-body">
				<div class="ap-empty-state">
					<i class="ri-notification-off-line"></i>
					<p>Nenhuma notificação no momento</p>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Notifications Modal
	const notificationsToggle = document.getElementById('notificationsToggle');
	const notificationsModal = document.getElementById('notificationsModal');
	
	notificationsToggle?.addEventListener('click', function() {
		notificationsModal?.classList.add('open');
	});
	
	notificationsModal?.querySelectorAll('[data-close-modal]').forEach(function(el) {
		el.addEventListener('click', function() {
			notificationsModal.classList.remove('open');
		});
	});
	
	// Escape to close modal
	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape' && notificationsModal?.classList.contains('open')) {
			notificationsModal.classList.remove('open');
		}
	});
});
</script>

<?php get_footer(); ?>
