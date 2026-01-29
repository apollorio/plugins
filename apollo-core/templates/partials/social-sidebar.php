<?php
/**
 * Social Sidebar Partial
 *
 * Desktop-only aside navigation with sections.
 * Based on: approved templates/apollo-social/social - layout - official.html
 *
 * @package ApolloCore\Templates\Partials
 *
 * @var array $args {
 *     @type array  $nav_items      Navigation sections with items
 *     @type string $active_section Current active section slug
 *     @type array  $user           Current user data
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$nav_items      = $args['nav_items'] ?? array();
$active_section = $args['active_section'] ?? '';
$user           = $args['user'] ?? array();

$avatar_url   = $user['avatar_url'] ?? get_avatar_url( 0, array( 'size' => 96 ) );
$display_name = $user['display_name'] ?? __( 'Visitante', 'apollo-core' );
$username     = $user['username'] ?? '';
?>

<style>
	.apollo-sidebar {
		display: none;
		position: fixed;
		top: var(--nav-height);
		left: 0;
		width: var(--sidebar-width);
		height: calc(100vh - var(--nav-height));
		flex-direction: column;
		background: var(--ap-bg-main);
		border-right: 1px solid var(--ap-border);
		overflow-y: auto;
		z-index: 40;
	}

	@media (min-width: 769px) {
		.apollo-sidebar {
			display: flex;
		}
	}

	.apollo-sidebar-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 1rem;
		border-bottom: 1px solid var(--ap-border);
	}

	.apollo-sidebar-nav {
		flex: 1;
		overflow-y: auto;
		padding: 1rem 0.75rem;
	}

	.apollo-sidebar-section {
		margin-bottom: 1.5rem;
	}

	.apollo-sidebar-label {
		font-size: 0.625rem;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.1em;
		color: var(--ap-text-muted);
		padding: 0 0.75rem;
		margin-bottom: 0.5rem;
	}

	.apollo-sidebar-link {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		padding: 10px 12px;
		margin-bottom: 4px;
		border-radius: 12px;
		border-left: 2px solid transparent;
		font-size: 13px;
		color: #64748b;
		text-decoration: none;
		transition: all 0.2s;
	}

	.apollo-sidebar-link:hover {
		background-color: var(--ap-bg-surface);
		color: var(--ap-text-primary);
	}

	.apollo-sidebar-link[aria-current="page"] {
		background-color: #f1f5f9;
		color: var(--ap-text-primary);
		border-left-color: var(--ap-text-primary);
		font-weight: 600;
	}

	body.dark-mode .apollo-sidebar-link[aria-current="page"] {
		background-color: rgba(255, 255, 255, 0.1);
	}

	.apollo-sidebar-link i {
		font-size: 1.125rem;
		opacity: 0.7;
	}

	.apollo-sidebar-link[aria-current="page"] i {
		opacity: 1;
	}

	.apollo-sidebar-user {
		padding: 1rem;
		border-top: 1px solid var(--ap-border);
		display: flex;
		align-items: center;
		gap: 0.75rem;
	}

	.apollo-sidebar-user-avatar {
		width: 40px;
		height: 40px;
		border-radius: 50%;
		object-fit: cover;
	}

	.apollo-sidebar-user-info {
		flex: 1;
		min-width: 0;
	}

	.apollo-sidebar-user-name {
		font-size: 0.875rem;
		font-weight: 600;
		color: var(--ap-text-primary);
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}

	.apollo-sidebar-user-username {
		font-size: 0.75rem;
		color: var(--ap-text-muted);
	}

	.apollo-sidebar-user-btn {
		width: 32px;
		height: 32px;
		border-radius: 8px;
		display: flex;
		align-items: center;
		justify-content: center;
		color: var(--ap-text-muted);
		background: transparent;
		border: none;
		cursor: pointer;
		transition: all 0.2s;
	}

	.apollo-sidebar-user-btn:hover {
		background: var(--ap-bg-surface);
		color: var(--ap-text-primary);
	}
</style>

<aside class="apollo-sidebar" role="navigation" aria-label="<?php esc_attr_e( 'Sidebar navigation', 'apollo-core' ); ?>">

	<nav class="apollo-sidebar-nav">
		<?php foreach ( $nav_items as $section_key => $section ) : ?>
			<div class="apollo-sidebar-section">
				<?php if ( ! empty( $section['label'] ) ) : ?>
					<div class="apollo-sidebar-label"><?php echo esc_html( $section['label'] ); ?></div>
				<?php endif; ?>

				<?php foreach ( $section['items'] as $item ) : ?>
					<?php
					$is_active    = ( $active_section === $item['slug'] );
					$aria_current = $is_active ? 'page' : 'false';
					?>
					<a
						href="<?php echo esc_url( $item['url'] ); ?>"
						class="apollo-sidebar-link"
						<?php echo $is_active ? 'aria-current="page"' : ''; ?>
					>
						<i class="<?php echo esc_attr( $item['icon'] ); ?>"></i>
						<span><?php echo esc_html( $item['label'] ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
	</nav>

	<!-- User Profile Card -->
	<?php if ( is_user_logged_in() ) : ?>
		<div class="apollo-sidebar-user">
			<img
				src="<?php echo esc_url( $avatar_url ); ?>"
				alt="<?php echo esc_attr( $display_name ); ?>"
				class="apollo-sidebar-user-avatar"
				loading="lazy"
			>
			<div class="apollo-sidebar-user-info">
				<div class="apollo-sidebar-user-name"><?php echo esc_html( $display_name ); ?></div>
				<?php if ( $username ) : ?>
					<div class="apollo-sidebar-user-username">@<?php echo esc_html( $username ); ?></div>
				<?php endif; ?>
			</div>
			<a
				href="<?php echo esc_url( home_url( '/configuracoes/' ) ); ?>"
				class="apollo-sidebar-user-btn"
				aria-label="<?php esc_attr_e( 'Settings', 'apollo-core' ); ?>"
			>
				<i class="ri-settings-3-line"></i>
			</a>
		</div>
	<?php endif; ?>

</aside>
