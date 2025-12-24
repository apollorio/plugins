<?php
/**
 * ShadCN UI App Sidebar Component (New York Style)
 *
 * Full-featured sidebar with navigation, user menu, and collapsible sections.
 * Based on shadcn/ui sidebar component with Apollo branding.
 *
 * @package    ApolloSocial
 * @subpackage Dashboard/Components
 * @since      1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the App Sidebar.
 *
 * @param array $args {
 *     Configuration options.
 *     @type array  $nav_items     Navigation items array.
 *     @type array  $user          Current user data.
 *     @type string $variant       Sidebar variant: 'default', 'inset', 'floating'. Default 'inset'.
 *     @type string $collapsible   Collapsible mode: 'icon', 'none', 'offcanvas'. Default 'icon'.
 * }
 */
function apollo_render_app_sidebar( array $args = array() ) {
	$current_user = wp_get_current_user();

	$defaults = array(
		'variant'     => 'inset',
		'collapsible' => 'icon',
		'user'        => array(
			'id'     => $current_user->ID,
			'name'   => $current_user->display_name,
			'email'  => $current_user->user_email,
			'avatar' => get_avatar_url( $current_user->ID, array( 'size' => 64 ) ),
			'role'   => implode( ', ', $current_user->roles ),
		),
		'nav_items'   => apollo_get_default_nav_items(),
	);
	$args     = wp_parse_args( $args, $defaults );

	$sidebar_classes  = 'apollo-app-sidebar peer hidden md:flex flex-col';
	$sidebar_classes .= ' group-data-[variant=inset]:border-r-0';
	$sidebar_classes .= ' h-svh w-[--sidebar-width] transition-[width] duration-200 ease-linear';
	$sidebar_classes .= ' group-data-[state=collapsed]:w-[--sidebar-width-collapsed]';
	$sidebar_classes .= ' border-r bg-sidebar text-sidebar-foreground';

	?>
	<aside 
		class="<?php echo esc_attr( $sidebar_classes ); ?>"
		data-variant="<?php echo esc_attr( $args['variant'] ); ?>"
		data-collapsible="<?php echo esc_attr( $args['collapsible'] ); ?>"
	>
		<!-- Sidebar Header -->
		<div class="flex h-[--header-height] items-center gap-2 border-b px-4">
			<button 
				type="button"
				class="apollo-sidebar-trigger inline-flex h-8 w-8 items-center justify-center rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
				aria-label="Toggle Sidebar"
			>
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
					<rect width="18" height="18" x="3" y="3" rx="2"/>
					<path d="M9 3v18"/>
				</svg>
			</button>
			<div class="flex flex-1 items-center gap-2 group-data-[state=collapsed]:hidden">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2">
					<div class="flex h-6 w-6 items-center justify-center rounded-md bg-primary text-primary-foreground">
						<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<circle cx="12" cy="12" r="10"/>
							<path d="m4.93 4.93 14.14 14.14"/>
						</svg>
					</div>
					<span class="text-sm font-semibold">Apollo::rio</span>
				</a>
			</div>
		</div>

		<!-- Sidebar Content -->
		<div class="flex flex-1 flex-col gap-4 overflow-y-auto py-4">
			<?php apollo_render_sidebar_nav_section( $args['nav_items'] ); ?>
		</div>

		<!-- Sidebar Footer (User Menu) -->
		<div class="mt-auto border-t p-4">
			<?php apollo_render_sidebar_user_menu( $args['user'] ); ?>
		</div>
	</aside>

	<!-- Mobile Sidebar (Offcanvas) -->
	<div class="apollo-sidebar-mobile fixed inset-0 z-50 hidden" data-mobile-sidebar>
		<div class="apollo-sidebar-overlay fixed inset-0 bg-black/50" data-sidebar-close></div>
		<aside class="fixed left-0 top-0 h-full w-72 bg-sidebar shadow-lg">
			<!-- Mobile sidebar content (same as desktop) -->
			<div class="flex h-14 items-center justify-between border-b px-4">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2">
					<div class="flex h-6 w-6 items-center justify-center rounded-md bg-primary text-primary-foreground">
						<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<circle cx="12" cy="12" r="10"/>
							<path d="m4.93 4.93 14.14 14.14"/>
						</svg>
					</div>
					<span class="text-sm font-semibold">Apollo::rio</span>
				</a>
				<button type="button" class="h-8 w-8 rounded-md hover:bg-sidebar-accent" data-sidebar-close>
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto">
						<path d="M18 6 6 18"/><path d="m6 6 12 12"/>
					</svg>
				</button>
			</div>
			<div class="flex flex-1 flex-col gap-4 overflow-y-auto py-4">
				<?php apollo_render_sidebar_nav_section( $args['nav_items'] ); ?>
			</div>
			<div class="border-t p-4">
				<?php apollo_render_sidebar_user_menu( $args['user'] ); ?>
			</div>
		</aside>
	</div>
	<?php
}

/**
 * Render sidebar navigation section.
 *
 * @param array $nav_items Navigation items grouped by section.
 */
function apollo_render_sidebar_nav_section( array $nav_items ) {
	foreach ( $nav_items as $section ) {
		$section_label = $section['label'] ?? '';
		$items         = $section['items'] ?? array();
		?>
		<div class="px-3">
			<?php if ( ! empty( $section_label ) ) : ?>
				<h4 class="mb-2 px-2 text-xs font-medium text-sidebar-foreground/60 uppercase tracking-wider group-data-[state=collapsed]:hidden">
					<?php echo esc_html( $section_label ); ?>
				</h4>
			<?php endif; ?>
			<nav class="flex flex-col gap-1">
				<?php
				foreach ( $items as $item ) :
					$is_active     = apollo_is_nav_item_active( $item );
					$item_classes  = 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors';
					$item_classes .= $is_active
						? ' bg-sidebar-accent text-sidebar-accent-foreground'
						: ' text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground';
					?>
					<a 
						href="<?php echo esc_url( $item['url'] ?? '#' ); ?>"
						class="<?php echo esc_attr( $item_classes ); ?>"
						<?php
						if ( ! empty( $item['target'] ) ) {
							echo 'target="' . esc_attr( $item['target'] ) . '"';
						}
						?>
					>
						<?php if ( ! empty( $item['icon'] ) ) : ?>
							<span class="flex h-5 w-5 shrink-0 items-center justify-center">
                                <?php echo $item['icon']; // phpcs:ignore -- SVG icon?>
							</span>
						<?php endif; ?>
						<span class="group-data-[state=collapsed]:hidden">
							<?php echo esc_html( $item['label'] ); ?>
						</span>
						<?php if ( ! empty( $item['badge'] ) ) : ?>
							<span class="ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1.5 text-[10px] font-semibold text-primary-foreground group-data-[state=collapsed]:hidden">
								<?php echo esc_html( $item['badge'] ); ?>
							</span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</nav>
		</div>
		<?php
	}//end foreach
}

/**
 * Render sidebar user menu.
 *
 * @param array $user User data.
 */
function apollo_render_sidebar_user_menu( array $user ) {
	?>
	<div class="apollo-user-menu relative" data-user-menu>
		<button 
			type="button"
			class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-sm hover:bg-sidebar-accent"
			data-user-menu-trigger
		>
			<div class="h-8 w-8 shrink-0 overflow-hidden rounded-full bg-sidebar-accent">
				<img 
					src="<?php echo esc_url( $user['avatar'] ); ?>" 
					alt="<?php echo esc_attr( $user['name'] ); ?>"
					class="h-full w-full object-cover"
				/>
			</div>
			<div class="flex flex-1 flex-col items-start text-left group-data-[state=collapsed]:hidden">
				<span class="text-sm font-medium"><?php echo esc_html( $user['name'] ); ?></span>
				<span class="text-xs text-sidebar-foreground/60"><?php echo esc_html( $user['email'] ); ?></span>
			</div>
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-sidebar-foreground/60 group-data-[state=collapsed]:hidden">
				<path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/>
			</svg>
		</button>

		<!-- User Dropdown Menu -->
		<div class="apollo-user-dropdown absolute bottom-full left-0 mb-2 hidden w-56 rounded-lg border bg-popover p-1 shadow-lg" data-user-dropdown>
			<div class="px-2 py-1.5 text-xs text-muted-foreground">
				<?php echo esc_html( $user['email'] ); ?>
			</div>
			<div class="my-1 h-px bg-border"></div>
			<a href="<?php echo esc_url( get_edit_profile_url() ); ?>" class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-accent">
				<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
				</svg>
				Meu Perfil
			</a>
			<a href="<?php echo esc_url( home_url( '/painel/' ) ); ?>" class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-accent">
				<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>
				</svg>
				Dashboard
			</a>
			<a href="#" class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-accent" data-theme-toggle>
				<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>
				</svg>
				Alternar Tema
			</a>
			<div class="my-1 h-px bg-border"></div>
			<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-destructive hover:bg-destructive/10">
				<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>
				</svg>
				Sair
			</a>
		</div>
	</div>
	<?php
}

/**
 * Check if a navigation item is currently active.
 *
 * @param array $item Navigation item.
 * @return bool
 */
function apollo_is_nav_item_active( array $item ) {
	$current_url = home_url( add_query_arg( array() ) );
	$item_url    = $item['url'] ?? '';

	if ( empty( $item_url ) || $item_url === '#' ) {
		return false;
	}

	// Exact match or starts with (for section pages)
	return $current_url === $item_url || strpos( $current_url, rtrim( $item_url, '/' ) ) === 0;
}

/**
 * Get default navigation items for Apollo dashboard.
 *
 * @return array Navigation items grouped by section.
 */
function apollo_get_default_nav_items() {
	$pending_docs = apollo_get_pending_docs_count();

	return array(
		array(
			'label' => 'Principal',
			'items' => array(
				array(
					'label' => 'Dashboard',
					'url'   => home_url( '/painel/' ),
					'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>',
				),
				array(
					'label' => 'Eventos',
					'url'   => home_url( '/eventos/' ),
					'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>',
				),
				array(
					'label' => 'Minha Página',
					'url'   => home_url( '/id/' . get_current_user_id() . '/' ),
					'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
				),
			),
		),
		array(
			'label' => 'Conteúdo',
			'items' => array(
				array(
					'label' => 'Comunidades',
					'url'   => home_url( '/comunidades/' ),
					'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
				),
				array(
					'label' => 'Núcleos',
					'url'   => home_url( '/nucleos/' ),
					'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>',
				),
				array(
					'label' => 'Documentos',
					'url'   => home_url( '/doc/' ),
					'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>',
					'badge' => $pending_docs > 0 ? $pending_docs : null,
				),
			),
		),
		array(
			'label' => 'Sistema',
			'items' => array(
				array(
					'label' => 'Configurações',
					'url'   => home_url( '/painel/config/' ),
					'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>',
				),
				array(
					'label' => 'Ajuda',
					'url'   => home_url( '/ajuda/' ),
					'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>',
				),
			),
		),
	);
}

/**
 * Get count of pending documents for current user.
 *
 * @return int
 */
function apollo_get_pending_docs_count() {
	if ( ! is_user_logged_in() ) {
		return 0;
	}

	// This can be expanded to query actual pending documents
	return 0;
}
