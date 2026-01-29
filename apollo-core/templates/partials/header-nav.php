<?php
/**
 * Header/Navigation Partial - Apollo Design System
 *
 * @deprecated Since 1.9.0 - Replaced by navbar.php
 *             The new navbar is loaded automatically via wp_footer hook
 *             from class-apollo-navbar-apps.php
 *
 * This file is kept for backward compatibility only.
 * DO NOT USE THIS FILE - Use the new navbar system instead.
 *
 * @see templates/partials/navbar.php
 * @see includes/class-apollo-navbar-apps.php
 *
 * @param array $args {
 *     @type WP_User $current_user    Current user object
 *     @type array   $navigation_links Array of navigation links
 * }
 */

// Trigger deprecation notice in development mode
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    _deprecated_file(
        __FILE__,
        '1.9.0',
        'templates/partials/navbar.php',
        'The new navbar is loaded automatically via wp_footer hook. Do not include this file.'
    );
}

// Return early - the new navbar is loaded via wp_footer hook
return;

/*
 * ===========================================================================
 * DEPRECATED CODE BELOW
 * This code is no longer executed. The new navbar system is used instead.
 * See: templates/partials/navbar.php
 * See: includes/class-apollo-navbar-apps.php (render_navbar method)
 * ===========================================================================
 */

$current_user     = $args['current_user'];
$navigation_links = $args['navigation_links'];

// CDN with local fallback
$cdn_base   = 'https://assets.apollo.rio.br/';
$local_base = defined( 'APOLLO_CORE_PLUGIN_URL' )
	? APOLLO_CORE_PLUGIN_URL . 'assets/core/'
	: plugin_dir_url( dirname( __DIR__ ) ) . 'assets/core/';
?>

<header class="site-header" data-tooltip="<?php esc_attr_e( 'Cabeçalho Apollo', 'apollo-events-manager' ); ?>">
	<div class="menu-h-apollo-blur"></div>
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="menu-apollo-logo" data-tooltip="<?php esc_attr_e( 'Ir para início', 'apollo-events-manager' ); ?>"></a>
	<nav class="main-nav" data-tooltip="<?php esc_attr_e( 'Menu de navegação principal', 'apollo-events-manager' ); ?>">
		<a class="a-hover off" data-tooltip="<?php esc_attr_e( 'Horário atual no Rio de Janeiro', 'apollo-events-manager' ); ?>">
			<span id="agoraH"><?php echo esc_html( function_exists( 'apollo_get_placeholder' ) ? apollo_get_placeholder( 'APOLLO_PLACEHOLDER_CURRENT_TIME' ) : date( 'H:i' ) ); ?></span> RJ
		</a>
		<?php if ( isset( $navigation_links['events'] ) ) : ?>
			<a href="<?php echo esc_url( $navigation_links['events']['url'] ); ?>" class="ario-eve" title="<?php esc_attr_e( 'Portal de Eventos', 'apollo-events-manager' ); ?>" data-tooltip="<?php esc_attr_e( 'Acessar portal de eventos', 'apollo-events-manager' ); ?>">
				<?php echo esc_html( $navigation_links['events']['text'] ); ?><i class="ri-arrow-right-up-line"></i>
			</a>
		<?php endif; ?>
		<div class="menu-h-lista">
			<?php if ( is_user_logged_in() && $current_user ) : ?>
				<button class="menu-h-apollo-button caption" id="userMenuTrigger">
					<?php echo esc_html( $current_user->display_name ); ?>
				</button>
				<div class="list">
					<div class="item ok"><i class="ri-global-line"></i> Explorer</div>
					<hr>
					<div class="item ok"><i class="ri-fingerprint-2-fill"></i> My Apollo</div>
					<div class="item ok"><a href="<?php echo wp_logout_url( home_url() ); ?>"><i class="ri-logout-box-r-line"></i> Logout</a></div>
				</div>
			<?php else : ?>
				<button class="menu-h-apollo-button caption" id="userMenuTrigger">Login</button>
				<div class="list">
					<div class="item ok"><i class="ri-global-line"></i> Explorer</div>
					<hr>
					<div class="item ok"><i class="ri-fingerprint-2-fill"></i> My Apollo</div>
				</div>
			<?php endif; ?>
		</div>
	</nav>
</header>

<style>
/* Header Styles - Matching Original Design */
.site-header {
	position: relative;
	z-index: 999;
}

.menu-h-apollo-blur {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 75px;
	backdrop-filter: blur(12px);
	-webkit-backdrop-filter: blur(12px);
	background: linear-gradient(to bottom, rgb(253 253 253 / .35) 0%, rgb(253 253 253 / .1) 50%, #fff0 100%);
	mask: linear-gradient(to bottom, rgb(0 0 0) 0%, rgb(0 0 0 / .8) 40%, rgb(0 0 0 / .3) 70%, #fff0 100%);
	-webkit-mask: linear-gradient(to bottom, rgb(0 0 0) 0%, rgb(0 0 0 / .8) 40%, rgb(0 0 0 / .3) 70%, #fff0 100%);
	z-index: 998;
	pointer-events: none;
	transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.main-nav {
	display: flex;
	position: fixed;
	height: 22px;
	top: 20px;
	left: 50%;
	transform: translateX(-50%);
	z-index: 999;
	align-items: center;
	gap: 2rem;
}

.menu-apollo-logo {
	position: fixed;
	top: 20px;
	left: 20px;
	width: 32px;
	height: 32px;
	background: url('<?php echo esc_url( $asset_base . 'img/apollo-logo.webp' ); ?>') no-repeat center;
	background-size: contain;
	z-index: 1000;
	transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.menu-apollo-logo:hover {
	transform: scale(1.1);
}

.a-hover {
	color: rgba(19, 21, 23, 0.7);
	text-decoration: none;
	font-weight: 600;
	font-size: 0.9rem;
	transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
	padding: 0.5rem 1rem;
	border-radius: 12px;
}

.a-hover:hover {
	color: rgba(19, 21, 23, 0.85);
	background: rgba(255, 255, 255, 0.68);
	backdrop-filter: blur(8px);
}

.ario-eve {
	color: rgba(19, 21, 23, 0.7);
	text-decoration: none;
	font-weight: 600;
	font-size: 0.9rem;
	transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
	padding: 0.5rem 1rem;
	border-radius: 12px;
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.ario-eve:hover {
	color: rgba(19, 21, 23, 0.85);
	background: rgba(255, 255, 255, 0.68);
	backdrop-filter: blur(8px);
}

.ario-eve i {
	font-size: 0.8rem;
}

.menu-h-lista {
	position: relative;
}

.menu-h-apollo-button {
	background: none;
	border: none;
	cursor: pointer;
	color: rgba(19, 21, 23, 0.7);
	font-weight: 600;
	font-size: 0.9rem;
	transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
	padding: 0.5rem 1rem;
	border-radius: 12px;
}

.menu-h-apollo-button:hover {
	color: rgba(19, 21, 23, 0.85);
	background: rgba(255, 255, 255, 0.68);
	backdrop-filter: blur(8px);
}

.list {
	position: absolute;
	top: 100%;
	right: 0;
	margin-top: 0.5rem;
	background: rgba(255, 255, 255, 0.68);
	backdrop-filter: blur(18px);
	border: 1px solid rgba(224, 226, 228, 0.54);
	border-radius: 12px;
	box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
	padding: 0.5rem 0;
	min-width: 160px;
	opacity: 0;
	visibility: hidden;
	transform: translateY(-10px);
	transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.menu-h-lista:hover .list {
	opacity: 1;
	visibility: visible;
	transform: translateY(0);
}

.item {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 0.75rem 1rem;
	color: rgba(19, 21, 23, 0.7);
	text-decoration: none;
	font-size: 0.85rem;
	transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.item:hover {
	color: rgba(19, 21, 23, 0.85);
	background: rgba(255, 255, 255, 1);
}

.item.ok {
	cursor: pointer;
}

.item a {
	color: inherit;
	text-decoration: none;
}

.item a:hover {
	color: inherit;
}

/* Mobile Responsive */
@media (max-width: 768px) {
	.main-nav {
		left: 60px; /* Account for logo */
		transform: none;
	}

	.ario-eve {
		display: none; /* Hide events link on mobile */
	}
}
</style>

<script>
// User menu toggle.
document.addEventListener('DOMContentLoaded', function() {
	const userMenuTrigger = document.getElementById('userMenuTrigger');
	const list = userMenuTrigger ? userMenuTrigger.nextElementSibling : null;

	if (userMenuTrigger && list) {
		userMenuTrigger.addEventListener('click', function(e) {
			e.stopPropagation();
			list.classList.toggle('open');
		});

		// Close when clicking outside.
		document.addEventListener('click', function() {
			list.classList.remove('open');
		});
	}
});
</script>
