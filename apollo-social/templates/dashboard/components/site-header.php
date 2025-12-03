<?php
/**
 * ShadCN UI Site Header Component (New York Style)
 *
 * Top header bar with breadcrumbs, search, and user actions.
 * Based on shadcn/ui header component.
 *
 * @package    ApolloSocial
 * @subpackage Dashboard/Components
 * @since      1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Site Header.
 *
 * @param array $args {
 *     Configuration options.
 *     @type array  $breadcrumbs  Breadcrumb items array.
 *     @type string $title        Page title (optional).
 *     @type bool   $show_search  Show search input. Default true.
 * }
 */
function apollo_render_site_header( array $args = array() ) {
	$defaults = array(
		'breadcrumbs' => array(),
		'title'       => '',
		'show_search' => true,
	);
	$args     = wp_parse_args( $args, $defaults );

	$header_classes = 'apollo-site-header flex h-[--header-height] shrink-0 items-center gap-2 border-b bg-background px-4 lg:px-6';
	?>
	<header class="<?php echo esc_attr( $header_classes ); ?>">
		<!-- Mobile Menu Trigger -->
		<button 
			type="button"
			class="apollo-mobile-trigger md:hidden inline-flex h-9 w-9 items-center justify-center rounded-md hover:bg-accent"
			data-mobile-sidebar-open
			aria-label="Abrir menu"
		>
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/>
			</svg>
		</button>

		<!-- Separator -->
		<div class="hidden md:block h-4 w-px bg-border"></div>

		<!-- Breadcrumbs -->
		<?php if ( ! empty( $args['breadcrumbs'] ) ) : ?>
			<nav class="hidden md:flex items-center gap-1.5 text-sm">
				<?php foreach ( $args['breadcrumbs'] as $index => $crumb ) : ?>
					<?php if ( $index > 0 ) : ?>
						<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground">
							<path d="m9 18 6-6-6-6"/>
						</svg>
					<?php endif; ?>
					<?php if ( ! empty( $crumb['url'] ) && $index < count( $args['breadcrumbs'] ) - 1 ) : ?>
						<a href="<?php echo esc_url( $crumb['url'] ); ?>" class="text-muted-foreground hover:text-foreground transition-colors">
							<?php echo esc_html( $crumb['label'] ); ?>
						</a>
					<?php else : ?>
						<span class="font-medium text-foreground">
							<?php echo esc_html( $crumb['label'] ); ?>
						</span>
					<?php endif; ?>
				<?php endforeach; ?>
			</nav>
		<?php elseif ( ! empty( $args['title'] ) ) : ?>
			<h1 class="text-sm font-semibold"><?php echo esc_html( $args['title'] ); ?></h1>
		<?php endif; ?>

		<!-- Spacer -->
		<div class="flex-1"></div>

		<!-- Search -->
		<?php if ( $args['show_search'] ) : ?>
			<div class="hidden lg:flex relative">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground">
					<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
				</svg>
				<input 
					type="search"
					placeholder="Buscar..."
					class="h-9 w-64 rounded-md border bg-background pl-8 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
				/>
				<kbd class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 hidden sm:inline-flex h-5 select-none items-center gap-1 rounded border bg-muted px-1.5 font-mono text-[10px] font-medium text-muted-foreground">
					<span class="text-xs">⌘</span>K
				</kbd>
			</div>
		<?php endif; ?>

		<!-- Header Actions -->
		<div class="flex items-center gap-2">
			<!-- Notifications -->
			<button 
				type="button" 
				class="relative inline-flex h-9 w-9 items-center justify-center rounded-md hover:bg-accent"
				aria-label="Notificações"
			>
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
				</svg>
				<span class="absolute right-1 top-1 flex h-2 w-2">
					<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
					<span class="relative inline-flex rounded-full h-2 w-2 bg-primary"></span>
				</span>
			</button>

			<!-- Quick Add -->
			<div class="hidden sm:block relative" data-dropdown="quick-add">
				<button 
					type="button" 
					class="inline-flex h-9 items-center gap-1.5 rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground hover:bg-primary/90"
					data-dropdown-trigger
				>
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M5 12h14"/><path d="M12 5v14"/>
					</svg>
					<span>Criar</span>
					<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="m6 9 6 6 6-6"/>
					</svg>
				</button>

				<!-- Quick Add Dropdown -->
				<div class="apollo-dropdown absolute right-0 top-full mt-2 hidden w-48 rounded-lg border bg-popover p-1 shadow-lg z-50" data-dropdown-content>
					<a href="<?php echo esc_url( home_url( '/enviar/' ) ); ?>" class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-accent">
						<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>
						</svg>
						Novo Evento
					</a>
					<a href="<?php echo esc_url( home_url( '/doc/novo/' ) ); ?>" class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-accent">
						<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/>
						</svg>
						Novo Documento
					</a>
					<a href="<?php echo esc_url( home_url( '/comunidades/criar/' ) ); ?>" class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-accent">
						<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
						</svg>
						Nova Comunidade
					</a>
				</div>
			</div>
		</div>
	</header>
	<?php
}
