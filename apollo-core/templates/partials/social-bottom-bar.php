<?php
/**
 * Social Bottom Bar Partial
 *
 * Mobile-only fixed bottom navigation with FAB button.
 * Based on: approved templates/apollo-social/social - layout - official.html
 *
 * @package ApolloCore\Templates\Partials
 *
 * @var array $args {
 *     @type string $active_section Current active section slug
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active_section = $args['active_section'] ?? '';

// Bottom navigation items.
$bottom_items = array(
	array(
		'slug'  => 'eventos',
		'icon'  => 'ri-calendar-event-line',
		'label' => __( 'Eventos', 'apollo-core' ),
		'url'   => home_url( '/eventos/' ),
	),
	array(
		'slug'  => 'dashboard',
		'icon'  => 'ri-dashboard-line',
		'label' => __( 'Dashboard', 'apollo-core' ),
		'url'   => home_url( '/dashboard/' ),
	),
	// FAB placeholder (rendered separately).
	array(
		'slug'  => 'feed',
		'icon'  => 'ri-file-list-3-line',
		'label' => __( 'Feed', 'apollo-core' ),
		'url'   => home_url( '/feed/' ),
	),
	array(
		'slug'  => 'perfil',
		'icon'  => 'ri-user-line',
		'label' => __( 'Perfil', 'apollo-core' ),
		'url'   => home_url( '/perfil/' ),
	),
);
?>

<style>
	.apollo-bottom-bar {
		display: flex;
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		z-index: 1200;
		background: rgba(255, 255, 255, 0.95);
		backdrop-filter: blur(20px);
		-webkit-backdrop-filter: blur(20px);
		border-top: 1px solid var(--ap-border);
		padding-bottom: env(safe-area-inset-bottom, 0px);
	}

	body.dark-mode .apollo-bottom-bar {
		background: rgba(19, 21, 23, 0.95);
	}

	@media (min-width: 769px) {
		.apollo-bottom-bar {
			display: none !important;
		}
	}

	.apollo-bottom-bar-inner {
		display: flex;
		align-items: flex-end;
		justify-content: space-between;
		width: 100%;
		height: 60px;
		padding: 0 0.5rem;
	}

	.apollo-bottom-nav-item {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		flex: 1;
		padding: 8px 4px;
		color: var(--ap-text-muted);
		text-decoration: none;
		transition: all 0.2s;
		background: transparent;
		border: none;
		cursor: pointer;
	}

	.apollo-bottom-nav-item:hover,
	.apollo-bottom-nav-item.active {
		color: var(--ap-text-primary);
	}

	.apollo-bottom-nav-item i {
		font-size: 1.375rem;
		margin-bottom: 2px;
	}

	.apollo-bottom-nav-item span {
		font-size: 0.625rem;
		font-weight: 500;
	}

	.apollo-bottom-nav-item.active i {
		color: var(--ap-orange-500);
	}

	/* FAB Button */
	.apollo-fab-container {
		position: relative;
		display: flex;
		align-items: flex-end;
		justify-content: center;
		flex: 1;
		padding-bottom: 5px;
	}

	.apollo-fab {
		position: relative;
		top: -20px;
		width: 56px;
		height: 56px;
		border-radius: 50%;
		background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
		color: white;
		display: flex;
		align-items: center;
		justify-content: center;
		border: none;
		cursor: pointer;
		box-shadow: 0 4px 20px rgba(15, 23, 42, 0.3);
		transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
	}

	.apollo-fab:hover {
		transform: scale(1.05);
		box-shadow: 0 6px 25px rgba(15, 23, 42, 0.4);
	}

	.apollo-fab:active {
		transform: scale(0.95);
	}

	.apollo-fab i {
		font-size: 1.75rem;
	}

	/* FAB Menu (expanded state) */
	.apollo-fab-menu {
		display: none;
		position: absolute;
		bottom: 70px;
		left: 50%;
		transform: translateX(-50%);
		background: white;
		border-radius: 16px;
		padding: 0.5rem;
		box-shadow: 0 10px 40px rgba(15, 23, 42, 0.2);
		flex-direction: column;
		gap: 0.25rem;
		min-width: 180px;
	}

	body.dark-mode .apollo-fab-menu {
		background: #1e293b;
	}

	.apollo-fab-menu.active {
		display: flex;
	}

	.apollo-fab-menu-item {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		padding: 0.75rem 1rem;
		border-radius: 12px;
		color: var(--ap-text-secondary);
		text-decoration: none;
		transition: all 0.2s;
	}

	.apollo-fab-menu-item:hover {
		background: var(--ap-bg-surface);
	}

	.apollo-fab-menu-item i {
		font-size: 1.25rem;
		opacity: 0.7;
	}

	.apollo-fab-menu-item span {
		font-size: 0.875rem;
		font-weight: 500;
	}
</style>

<nav class="apollo-bottom-bar" role="navigation" aria-label="<?php esc_attr_e( 'Mobile navigation', 'apollo-core' ); ?>">
	<div class="apollo-bottom-bar-inner">

		<?php
		$item_count = 0;
		foreach ( $bottom_items as $item ) :
			++$item_count;
			$is_active = ( $active_section === $item['slug'] );

			// Insert FAB after second item.
			if ( $item_count === 3 ) :
				?>
				<div class="apollo-fab-container">
					<button
						type="button"
						class="apollo-fab"
						id="fab-toggle"
						aria-label="<?php esc_attr_e( 'Create new', 'apollo-core' ); ?>"
						aria-expanded="false"
						aria-controls="fab-menu"
					>
						<i class="ri-add-line"></i>
					</button>

					<div class="apollo-fab-menu" id="fab-menu" role="menu">
						<a href="<?php echo esc_url( home_url( '/novo-post/' ) ); ?>" class="apollo-fab-menu-item" role="menuitem">
							<i class="ri-edit-line"></i>
							<span><?php esc_html_e( 'Nova publicação', 'apollo-core' ); ?></span>
						</a>
						<a href="<?php echo esc_url( home_url( '/novo-evento/' ) ); ?>" class="apollo-fab-menu-item" role="menuitem">
							<i class="ri-calendar-event-line"></i>
							<span><?php esc_html_e( 'Novo evento', 'apollo-core' ); ?></span>
						</a>
						<a href="<?php echo esc_url( home_url( '/novo-anuncio/' ) ); ?>" class="apollo-fab-menu-item" role="menuitem">
							<i class="ri-megaphone-line"></i>
							<span><?php esc_html_e( 'Novo anúncio', 'apollo-core' ); ?></span>
						</a>
					</div>
				</div>
				<?php
			endif;
			?>

			<a
				href="<?php echo esc_url( $item['url'] ); ?>"
				class="apollo-bottom-nav-item <?php echo $is_active ? 'active' : ''; ?>"
				<?php echo $is_active ? 'aria-current="page"' : ''; ?>
			>
				<i class="<?php echo esc_attr( $item['icon'] ); ?>"></i>
				<span><?php echo esc_html( $item['label'] ); ?></span>
			</a>

		<?php endforeach; ?>

	</div>
</nav>

<script>
(function() {
	var fabToggle = document.getElementById('fab-toggle');
	var fabMenu = document.getElementById('fab-menu');

	if (fabToggle && fabMenu) {
		fabToggle.addEventListener('click', function(e) {
			e.stopPropagation();
			var isExpanded = fabToggle.getAttribute('aria-expanded') === 'true';
			fabToggle.setAttribute('aria-expanded', !isExpanded);
			fabMenu.classList.toggle('active');

			// Rotate icon.
			var icon = fabToggle.querySelector('i');
			if (icon) {
				icon.style.transform = isExpanded ? 'rotate(0deg)' : 'rotate(45deg)';
			}
		});

		document.addEventListener('click', function() {
			fabToggle.setAttribute('aria-expanded', 'false');
			fabMenu.classList.remove('active');
			var icon = fabToggle.querySelector('i');
			if (icon) {
				icon.style.transform = 'rotate(0deg)';
			}
		});

		fabMenu.addEventListener('click', function(e) {
			e.stopPropagation();
		});
	}
})();
</script>
