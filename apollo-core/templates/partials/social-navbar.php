<?php
/**
 * Social Navbar Partial
 *
 * Fixed top navigation bar with glass effect.
 * Based on: approved templates/apollo-social/social - layout - official.html
 *
 * @package ApolloCore\Templates\Partials
 *
 * @var array $args {
 *     @type array $user          Current user data
 *     @type array $notifications Notification items
 *     @type array $apps          App grid items
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user          = $args['user'] ?? array();
$notifications = $args['notifications'] ?? array();
$apps          = $args['apps'] ?? array();

$has_notifications = ! empty( $notifications );
$avatar_url        = $user['avatar_url'] ?? get_avatar_url( 0, array( 'size' => 96 ) );
$display_name      = $user['display_name'] ?? __( 'Visitante', 'apollo-core' );
?>

<style>
	.apollo-navbar {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		z-index: 1000;
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 0 1rem;
		height: var(--nav-height);
		background: rgba(255, 255, 255, 0.6);
		backdrop-filter: var(--glass-blur);
		-webkit-backdrop-filter: var(--glass-blur);
	}

	body.dark-mode .apollo-navbar {
		background: rgba(19, 21, 23, 0.8);
	}

	.apollo-navbar-logo {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		text-decoration: none;
		color: inherit;
	}

	.apollo-navbar-logo-icon {
		height: 36px;
		width: 36px;
		border-radius: 12px;
		background: linear-gradient(135deg, #fb923c 0%, #ea580c 100%);
		display: flex;
		align-items: center;
		justify-content: center;
		color: white;
		font-size: 1.25rem;
	}

	.apollo-navbar-logo-text {
		display: none;
	}

	@media (min-width: 769px) {
		.apollo-navbar-logo-text {
			display: flex;
			flex-direction: column;
		}

		.apollo-navbar-logo-brand {
			font-size: 0.875rem;
			font-weight: 700;
			color: var(--ap-text-primary);
		}

		.apollo-navbar-logo-sub {
			font-size: 0.625rem;
			color: var(--ap-text-muted);
			text-transform: uppercase;
			letter-spacing: 0.1em;
		}
	}

	.apollo-navbar-actions {
		display: flex;
		align-items: center;
		gap: 0.5rem;
	}

	.apollo-navbar-clock {
		padding: 0.4rem 1rem;
		border-radius: 99px;
		font-size: 0.75rem;
		font-weight: 600;
		color: var(--ap-text-primary);
		display: none;
	}

	@media (min-width: 769px) {
		.apollo-navbar-clock {
			display: block;
		}
	}

	.apollo-nav-btn {
		width: 42px;
		height: 42px;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		color: var(--ap-text-primary);
		transition: all 0.2s ease;
		position: relative;
		background: transparent;
		border: none;
		cursor: pointer;
	}

	.apollo-nav-btn:hover,
	.apollo-nav-btn[aria-expanded="true"] {
		background: rgba(0, 0, 0, 0.05);
		color: var(--ap-orange-500);
		transform: scale(1.05);
	}

	.apollo-nav-btn svg,
	.apollo-nav-btn i {
		width: 22px;
		height: 22px;
		font-size: 22px;
	}

	.apollo-nav-btn-notif {
		display: none;
	}

	@media (min-width: 769px) {
		.apollo-nav-btn-notif {
			display: flex;
		}
	}

	.apollo-badge-pulse {
		position: absolute;
		top: 8px;
		right: 8px;
		width: 8px;
		height: 8px;
		border-radius: 50%;
		background: var(--ap-orange-500);
		box-shadow: 0 0 10px var(--ap-orange-500);
		animation: pulse-badge 2s infinite ease-in-out;
	}

	@keyframes pulse-badge {
		0%, 100% {
			transform: scale(1);
			opacity: 1;
		}
		50% {
			transform: scale(1.2);
			opacity: 0.7;
		}
	}

	.apollo-navbar-avatar {
		width: 36px;
		height: 36px;
		border-radius: 50%;
		object-fit: cover;
		border: 2px solid var(--ap-border);
	}
</style>

<nav class="apollo-navbar" role="navigation" aria-label="<?php esc_attr_e( 'Main navigation', 'apollo-core' ); ?>">

	<!-- Logo -->
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="apollo-navbar-logo">
		<div class="apollo-navbar-logo-icon">
			<i class="ri-slack-fill"></i>
		</div>
		<div class="apollo-navbar-logo-text">
			<span class="apollo-navbar-logo-brand">Apollo::rio</span>
			<span class="apollo-navbar-logo-sub">plataforma</span>
		</div>
	</a>

	<!-- Actions -->
	<div class="apollo-navbar-actions">

		<!-- Clock (desktop only) -->
		<div class="apollo-navbar-clock" id="digital-clock">
			<span>--:--</span>
		</div>

		<!-- Notifications (desktop only) -->
		<button
			type="button"
			id="btn-notif"
			class="apollo-nav-btn apollo-nav-btn-notif"
			aria-label="<?php esc_attr_e( 'Notifications', 'apollo-core' ); ?>"
			aria-expanded="false"
			aria-controls="menu-notif"
		>
			<i class="ri-notification-3-line"></i>
			<?php if ( $has_notifications ) : ?>
				<span class="apollo-badge-pulse"></span>
			<?php endif; ?>
		</button>

		<!-- Apps Grid -->
		<button
			type="button"
			id="btn-apps"
			class="apollo-nav-btn"
			aria-label="<?php esc_attr_e( 'Apps', 'apollo-core' ); ?>"
			aria-expanded="false"
			aria-controls="menu-app"
		>
			<i class="ri-apps-2-line"></i>
		</button>

		<!-- User Profile -->
		<button
			type="button"
			id="btn-profile"
			class="apollo-nav-btn"
			aria-label="<?php echo esc_attr( $display_name ); ?>"
			aria-expanded="false"
			aria-controls="menu-profile"
		>
			<img
				src="<?php echo esc_url( $avatar_url ); ?>"
				alt="<?php echo esc_attr( $display_name ); ?>"
				class="apollo-navbar-avatar"
				loading="lazy"
			>
		</button>

	</div>

</nav>
