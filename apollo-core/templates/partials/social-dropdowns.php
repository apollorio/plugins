<?php
/**
 * Social Dropdowns Partial
 *
 * Notification, Apps, and Profile dropdown menus.
 * Based on: approved templates/apollo-social/social - layout - official.html
 *
 * @package ApolloCore\Templates\Partials
 *
 * @var array $args {
 *     @type array $notifications Notification items
 *     @type array $apps          App grid items
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$notifications = $args['notifications'] ?? array();
$apps          = $args['apps'] ?? array();

// Default apps grid if not provided.
if ( empty( $apps ) ) {
	$apps = array(
		array(
			'icon'  => 'ri-calendar-event-fill',
			'label' => __( 'Eventos', 'apollo-core' ),
			'url'   => home_url( '/eventos/' ),
			'color' => '#f97316',
		),
		array(
			'icon'  => 'ri-group-fill',
			'label' => __( 'Comunidades', 'apollo-core' ),
			'url'   => home_url( '/comunidades/' ),
			'color' => '#3b82f6',
		),
		array(
			'icon'  => 'ri-store-2-fill',
			'label' => __( 'Fornecedores', 'apollo-core' ),
			'url'   => home_url( '/fornecedores/' ),
			'color' => '#10b981',
		),
		array(
			'icon'  => 'ri-megaphone-fill',
			'label' => __( 'Anúncios', 'apollo-core' ),
			'url'   => home_url( '/anuncios/' ),
			'color' => '#a855f7',
		),
		array(
			'icon'  => 'ri-palette-fill',
			'label' => __( 'Studio', 'apollo-core' ),
			'url'   => home_url( '/studio/' ),
			'color' => '#ec4899',
		),
		array(
			'icon'  => 'ri-settings-3-fill',
			'label' => __( 'Configurações', 'apollo-core' ),
			'url'   => home_url( '/configuracoes/' ),
			'color' => '#64748b',
		),
	);
}
?>

<style>
	.apollo-dropdown {
		display: none;
		position: fixed;
		top: calc(var(--nav-height) + 10px);
		right: 16px;
		width: 380px;
		max-width: 90vw;
		background: rgba(255, 255, 255, 0.98);
		backdrop-filter: blur(30px);
		-webkit-backdrop-filter: blur(30px);
		border: 1px solid var(--ap-border);
		border-radius: 20px;
		box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
		z-index: 2000;
		flex-direction: column;
		opacity: 0;
		transform: translateY(-10px) scale(0.98);
		transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
		overflow: hidden;
		max-height: 85vh;
	}

	body.dark-mode .apollo-dropdown {
		background: rgba(30, 41, 59, 0.98);
	}

	.apollo-dropdown.active {
		display: flex;
		opacity: 1;
		transform: translateY(0) scale(1);
	}

	.apollo-dropdown-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 1rem 1.25rem;
		border-bottom: 1px solid var(--ap-border);
	}

	.apollo-dropdown-title {
		font-size: 1rem;
		font-weight: 600;
		color: var(--ap-text-primary);
	}

	.apollo-dropdown-action {
		font-size: 0.75rem;
		color: var(--ap-orange-500);
		text-decoration: none;
		font-weight: 500;
	}

	.apollo-dropdown-action:hover {
		text-decoration: underline;
	}

	.apollo-dropdown-content {
		overflow-y: auto;
		max-height: 60vh;
	}

	/* Notifications */
	.apollo-notif-item {
		display: flex;
		gap: 0.75rem;
		padding: 1rem 1.25rem;
		border-bottom: 1px solid var(--ap-border);
		transition: background 0.2s;
	}

	.apollo-notif-item:hover {
		background: var(--ap-bg-surface);
	}

	.apollo-notif-item:last-child {
		border-bottom: none;
	}

	.apollo-notif-avatar {
		width: 40px;
		height: 40px;
		border-radius: 50%;
		object-fit: cover;
		flex-shrink: 0;
	}

	.apollo-notif-body {
		flex: 1;
		min-width: 0;
	}

	.apollo-notif-text {
		font-size: 0.875rem;
		color: var(--ap-text-secondary);
		line-height: 1.4;
	}

	.apollo-notif-text strong {
		font-weight: 600;
		color: var(--ap-text-primary);
	}

	.apollo-notif-time {
		font-size: 0.75rem;
		color: var(--ap-text-muted);
		margin-top: 0.25rem;
	}

	.apollo-notif-empty {
		padding: 2rem;
		text-align: center;
		color: var(--ap-text-muted);
	}

	/* Apps Grid */
	.apollo-apps-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 0.5rem;
		padding: 1rem;
	}

	.apollo-app-item {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 0.5rem;
		padding: 1rem 0.5rem;
		border-radius: 12px;
		text-decoration: none;
		color: var(--ap-text-secondary);
		transition: all 0.2s;
	}

	.apollo-app-item:hover {
		background: var(--ap-bg-surface);
	}

	.apollo-app-icon {
		width: 48px;
		height: 48px;
		border-radius: 12px;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 1.5rem;
		color: white;
	}

	.apollo-app-label {
		font-size: 0.75rem;
		font-weight: 500;
		text-align: center;
	}

	/* Profile Menu */
	.apollo-profile-menu {
		padding: 0.5rem;
	}

	.apollo-profile-header {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		padding: 1rem;
		border-bottom: 1px solid var(--ap-border);
		margin-bottom: 0.5rem;
	}

	.apollo-profile-avatar {
		width: 48px;
		height: 48px;
		border-radius: 50%;
		object-fit: cover;
	}

	.apollo-profile-info {
		flex: 1;
	}

	.apollo-profile-name {
		font-weight: 600;
		color: var(--ap-text-primary);
	}

	.apollo-profile-email {
		font-size: 0.75rem;
		color: var(--ap-text-muted);
	}

	.apollo-profile-item {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		padding: 0.75rem 1rem;
		border-radius: 10px;
		color: var(--ap-text-secondary);
		text-decoration: none;
		transition: all 0.2s;
	}

	.apollo-profile-item:hover {
		background: var(--ap-bg-surface);
	}

	.apollo-profile-item i {
		font-size: 1.125rem;
		opacity: 0.7;
	}

	.apollo-profile-item.logout {
		color: #ef4444;
	}

	/* Mobile fullscreen for apps */
	@media (max-width: 768px) {
		#menu-app.active {
			width: 90vw !important;
			height: auto;
			max-height: 80vh;
			position: fixed;
			top: 50%;
			left: 50%;
			transform: translate(-50%, -50%) !important;
		}
	}
</style>

<!-- Notifications Dropdown -->
<div class="apollo-dropdown" id="menu-notif" role="dialog" aria-label="<?php esc_attr_e( 'Notifications', 'apollo-core' ); ?>">
	<div class="apollo-dropdown-header">
		<span class="apollo-dropdown-title"><?php esc_html_e( 'Notificações', 'apollo-core' ); ?></span>
		<?php if ( ! empty( $notifications ) ) : ?>
			<a href="<?php echo esc_url( home_url( '/notificacoes/' ) ); ?>" class="apollo-dropdown-action">
				<?php esc_html_e( 'Ver todas', 'apollo-core' ); ?>
			</a>
		<?php endif; ?>
	</div>
	<div class="apollo-dropdown-content">
		<?php if ( empty( $notifications ) ) : ?>
			<div class="apollo-notif-empty">
				<i class="ri-notification-off-line" style="font-size: 2rem; opacity: 0.5;"></i>
				<p><?php esc_html_e( 'Nenhuma notificação', 'apollo-core' ); ?></p>
			</div>
		<?php else : ?>
			<?php foreach ( $notifications as $notif ) : ?>
				<div class="apollo-notif-item">
					<img
						src="<?php echo esc_url( $notif['avatar'] ?? '' ); ?>"
						alt=""
						class="apollo-notif-avatar"
						loading="lazy"
					>
					<div class="apollo-notif-body">
						<div class="apollo-notif-text">
							<?php echo wp_kses_post( $notif['text'] ?? '' ); ?>
						</div>
						<div class="apollo-notif-time">
							<?php echo esc_html( $notif['time'] ?? '' ); ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>

<!-- Apps Dropdown -->
<div class="apollo-dropdown" id="menu-app" role="dialog" aria-label="<?php esc_attr_e( 'Apps', 'apollo-core' ); ?>">
	<div class="apollo-dropdown-header">
		<span class="apollo-dropdown-title"><?php esc_html_e( 'Aplicativos', 'apollo-core' ); ?></span>
	</div>
	<div class="apollo-apps-grid">
		<?php foreach ( $apps as $app ) : ?>
			<a href="<?php echo esc_url( $app['url'] ); ?>" class="apollo-app-item">
				<div class="apollo-app-icon" style="background-color: <?php echo esc_attr( $app['color'] ?? '#64748b' ); ?>;">
					<i class="<?php echo esc_attr( $app['icon'] ); ?>"></i>
				</div>
				<span class="apollo-app-label"><?php echo esc_html( $app['label'] ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
</div>

<!-- Profile Dropdown -->
<div class="apollo-dropdown" id="menu-profile" role="dialog" aria-label="<?php esc_attr_e( 'Profile menu', 'apollo-core' ); ?>">
	<?php if ( is_user_logged_in() ) : ?>
		<?php $current_user = wp_get_current_user(); ?>
		<div class="apollo-profile-menu">
			<div class="apollo-profile-header">
				<img
					src="<?php echo esc_url( get_avatar_url( $current_user->ID, array( 'size' => 96 ) ) ); ?>"
					alt="<?php echo esc_attr( $current_user->display_name ); ?>"
					class="apollo-profile-avatar"
				>
				<div class="apollo-profile-info">
					<div class="apollo-profile-name"><?php echo esc_html( $current_user->display_name ); ?></div>
					<div class="apollo-profile-email"><?php echo esc_html( $current_user->user_email ); ?></div>
				</div>
			</div>

			<a href="<?php echo esc_url( home_url( '/perfil/' ) ); ?>" class="apollo-profile-item">
				<i class="ri-user-line"></i>
				<span><?php esc_html_e( 'Meu Perfil', 'apollo-core' ); ?></span>
			</a>
			<a href="<?php echo esc_url( home_url( '/configuracoes/' ) ); ?>" class="apollo-profile-item">
				<i class="ri-settings-3-line"></i>
				<span><?php esc_html_e( 'Configurações', 'apollo-core' ); ?></span>
			</a>
			<a href="<?php echo esc_url( home_url( '/ajuda/' ) ); ?>" class="apollo-profile-item">
				<i class="ri-question-line"></i>
				<span><?php esc_html_e( 'Ajuda', 'apollo-core' ); ?></span>
			</a>
			<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="apollo-profile-item logout">
				<i class="ri-logout-box-r-line"></i>
				<span><?php esc_html_e( 'Sair', 'apollo-core' ); ?></span>
			</a>
		</div>
	<?php else : ?>
		<div class="apollo-profile-menu">
			<a href="<?php echo esc_url( wp_login_url() ); ?>" class="apollo-profile-item">
				<i class="ri-login-box-line"></i>
				<span><?php esc_html_e( 'Entrar', 'apollo-core' ); ?></span>
			</a>
			<a href="<?php echo esc_url( wp_registration_url() ); ?>" class="apollo-profile-item">
				<i class="ri-user-add-line"></i>
				<span><?php esc_html_e( 'Cadastrar', 'apollo-core' ); ?></span>
			</a>
		</div>
	<?php endif; ?>
</div>

<script>
(function() {
	var buttons = {
		'btn-notif': 'menu-notif',
		'btn-apps': 'menu-app',
		'btn-profile': 'menu-profile'
	};

	function closeAllMenus() {
		Object.keys(buttons).forEach(function(btnId) {
			var btn = document.getElementById(btnId);
			var menu = document.getElementById(buttons[btnId]);
			if (btn) btn.setAttribute('aria-expanded', 'false');
			if (menu) menu.classList.remove('active');
		});
	}

	Object.keys(buttons).forEach(function(btnId) {
		var btn = document.getElementById(btnId);
		var menuId = buttons[btnId];
		var menu = document.getElementById(menuId);

		if (btn && menu) {
			btn.addEventListener('click', function(e) {
				e.stopPropagation();
				var isExpanded = btn.getAttribute('aria-expanded') === 'true';
				closeAllMenus();
				if (!isExpanded) {
					btn.setAttribute('aria-expanded', 'true');
					menu.classList.add('active');
				}
			});
		}
	});

	document.addEventListener('click', function() {
		closeAllMenus();
	});

	// Prevent clicks inside menus from closing them.
	Object.keys(buttons).forEach(function(btnId) {
		var menu = document.getElementById(buttons[btnId]);
		if (menu) {
			menu.addEventListener('click', function(e) {
				e.stopPropagation();
			});
		}
	});

	// Close on Escape key.
	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape') {
			closeAllMenus();
		}
	});
})();
</script>
