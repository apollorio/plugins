<?php
/**
 * ShadCN UI Data Table Component (New York Style)
 *
 * Full-featured data table with sorting, filtering, and pagination.
 * Based on shadcn/ui table component with TanStack Table patterns.
 *
 * @package    ApolloSocial
 * @subpackage Dashboard/Components
 * @since      1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render a Data Table.
 *
 * @param array $args {
 *     Configuration options.
 *     @type array  $columns    Column definitions.
 *     @type array  $data       Table data rows.
 *     @type string $title      Table title.
 *     @type string $description Table description.
 *     @type bool   $searchable Enable search. Default true.
 *     @type bool   $sortable   Enable sorting. Default true.
 *     @type bool   $paginated  Enable pagination. Default true.
 *     @type int    $per_page   Items per page. Default 10.
 *     @type string $empty_message Message when no data.
 * }
 */
function apollo_render_data_table( array $args = [] ) {
	$defaults = [
		'columns'       => [],
		'data'          => [],
		'title'         => '',
		'description'   => '',
		'searchable'    => true,
		'sortable'      => true,
		'paginated'     => true,
		'per_page'      => 10,
		'empty_message' => 'Nenhum registro encontrado.',
		'id'            => 'apollo-table-' . wp_rand( 1000, 9999 ),
	];
	$args     = wp_parse_args( $args, $defaults );

	$table_id = esc_attr( $args['id'] );
	?>
	<div 
		class="apollo-data-table-wrapper px-4 lg:px-6"
		data-table-id="<?php echo $table_id; ?>"
		data-per-page="<?php echo esc_attr( $args['per_page'] ); ?>"
	>
		<!-- Table Header -->
		<div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-4">
			<div>
				<?php if ( ! empty( $args['title'] ) ) : ?>
					<h2 class="text-lg font-semibold"><?php echo esc_html( $args['title'] ); ?></h2>
				<?php endif; ?>
				<?php if ( ! empty( $args['description'] ) ) : ?>
					<p class="text-sm text-muted-foreground"><?php echo esc_html( $args['description'] ); ?></p>
				<?php endif; ?>
			</div>
			
			<div class="flex items-center gap-3">
				<?php if ( $args['searchable'] ) : ?>
					<div class="relative">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground">
							<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
						</svg>
						<input 
							type="search"
							placeholder="Filtrar..."
							class="h-9 w-48 rounded-md border bg-background pl-8 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
							data-table-search="<?php echo $table_id; ?>"
						/>
					</div>
				<?php endif; ?>
				
				<!-- View Options Dropdown -->
				<div class="relative" data-dropdown="view-options">
					<button 
						type="button"
						class="inline-flex h-9 items-center gap-1.5 rounded-md border bg-background px-3 text-sm font-medium hover:bg-accent"
						data-dropdown-trigger
					>
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
							<circle cx="12" cy="12" r="3"/>
						</svg>
						<span>Visualização</span>
					</button>
					<div class="apollo-dropdown absolute right-0 top-full mt-2 hidden w-40 rounded-lg border bg-popover p-1 shadow-lg z-50" data-dropdown-content>
						<?php foreach ( $args['columns'] as $col ) : ?>
							<label class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-accent cursor-pointer">
								<input 
									type="checkbox" 
									checked 
									class="h-4 w-4 rounded border-input"
									data-column-toggle="<?php echo esc_attr( $col['key'] ); ?>"
								/>
								<?php echo esc_html( $col['label'] ); ?>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Table -->
		<div class="rounded-lg border bg-card overflow-hidden">
			<div class="overflow-x-auto">
				<table class="w-full text-sm" id="<?php echo $table_id; ?>">
					<thead class="border-b bg-muted/50">
						<tr>
							<?php
							foreach ( $args['columns'] as $col ) :
								$is_sortable = $args['sortable'] && ( $col['sortable'] ?? true );
								?>
								<th 
									class="h-12 px-4 text-left align-middle font-medium text-muted-foreground <?php echo $is_sortable ? 'cursor-pointer select-none hover:text-foreground' : ''; ?>"
									data-column="<?php echo esc_attr( $col['key'] ); ?>"
									<?php
									if ( $is_sortable ) :
										?>
										data-sortable="true"<?php endif; ?>
								>
									<div class="flex items-center gap-2">
										<span><?php echo esc_html( $col['label'] ); ?></span>
										<?php if ( $is_sortable ) : ?>
											<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground/50">
												<path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/>
											</svg>
										<?php endif; ?>
									</div>
								</th>
							<?php endforeach; ?>
							<th class="h-12 w-12 px-4"></th>
						</tr>
					</thead>
					<tbody class="divide-y">
						<?php if ( empty( $args['data'] ) ) : ?>
							<tr>
								<td colspan="<?php echo count( $args['columns'] ) + 1; ?>" class="h-24 text-center text-muted-foreground">
									<?php echo esc_html( $args['empty_message'] ); ?>
								</td>
							</tr>
						<?php else : ?>
							<?php foreach ( $args['data'] as $row_index => $row ) : ?>
								<tr class="hover:bg-muted/50 transition-colors" data-row-index="<?php echo esc_attr( $row_index ); ?>">
									<?php
									foreach ( $args['columns'] as $col ) :
										$value  = $row[ $col['key'] ] ?? '';
										$render = $col['render'] ?? null;
										?>
										<td class="p-4 align-middle" data-column="<?php echo esc_attr( $col['key'] ); ?>">
											<?php
											if ( is_callable( $render ) ) {
                                                echo $render( $value, $row ); // phpcs:ignore -- Custom render
											} else {
												echo esc_html( $value );
											}
											?>
										</td>
									<?php endforeach; ?>
									<td class="p-4 align-middle">
										<div class="relative" data-dropdown="row-actions-<?php echo esc_attr( $row_index ); ?>">
											<button 
												type="button"
												class="inline-flex h-8 w-8 items-center justify-center rounded-md hover:bg-accent"
												data-dropdown-trigger
											>
												<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
													<circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/>
												</svg>
											</button>
											<div class="apollo-dropdown absolute right-0 top-full mt-1 hidden w-40 rounded-lg border bg-popover p-1 shadow-lg z-50" data-dropdown-content>
												<?php if ( ! empty( $row['permalink'] ) ) : ?>
													<a href="<?php echo esc_url( $row['permalink'] ); ?>" class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-accent">
														<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
															<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/>
														</svg>
														Abrir
													</a>
												<?php endif; ?>
												<?php if ( ! empty( $row['edit_url'] ) ) : ?>
													<a href="<?php echo esc_url( $row['edit_url'] ); ?>" class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-accent">
														<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
															<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/>
														</svg>
														Editar
													</a>
												<?php endif; ?>
												<div class="my-1 h-px bg-border"></div>
												<button type="button" class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-sm text-destructive hover:bg-destructive/10">
													<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
														<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
													</svg>
													Excluir
												</button>
											</div>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<?php if ( $args['paginated'] && count( $args['data'] ) > $args['per_page'] ) : ?>
				<!-- Pagination -->
				<div class="flex items-center justify-between border-t px-4 py-3">
					<div class="text-sm text-muted-foreground">
						Mostrando <span data-pagination-start>1</span> a <span data-pagination-end><?php echo min( $args['per_page'], count( $args['data'] ) ); ?></span> de <?php echo count( $args['data'] ); ?> resultados
					</div>
					<div class="flex items-center gap-2">
						<button 
							type="button"
							class="inline-flex h-9 w-9 items-center justify-center rounded-md border bg-background hover:bg-accent disabled:opacity-50"
							data-pagination-prev
							disabled
						>
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="m15 18-6-6 6-6"/>
							</svg>
						</button>
						<div class="flex items-center gap-1" data-pagination-pages>
							<!-- Page numbers inserted by JS -->
						</div>
						<button 
							type="button"
							class="inline-flex h-9 w-9 items-center justify-center rounded-md border bg-background hover:bg-accent disabled:opacity-50"
							data-pagination-next
						>
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="m9 18 6-6-6-6"/>
							</svg>
						</button>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * Render a badge/status indicator.
 *
 * @param string $status Status value.
 * @param array  $variants Status-to-variant mapping.
 * @return string HTML output.
 */
function apollo_render_table_badge( $status, array $variants = [] ) {
	$defaults = [
		'publish'  => [
			'label' => 'Publicado',
			'class' => 'bg-emerald-100 text-emerald-700',
		],
		'draft'    => [
			'label' => 'Rascunho',
			'class' => 'bg-slate-100 text-slate-700',
		],
		'pending'  => [
			'label' => 'Pendente',
			'class' => 'bg-amber-100 text-amber-700',
		],
		'signed'   => [
			'label' => 'Assinado',
			'class' => 'bg-emerald-100 text-emerald-700',
		],
		'active'   => [
			'label' => 'Ativo',
			'class' => 'bg-emerald-100 text-emerald-700',
		],
		'inactive' => [
			'label' => 'Inativo',
			'class' => 'bg-slate-100 text-slate-700',
		],
	];
	$variants = wp_parse_args( $variants, $defaults );

	$variant = $variants[ $status ] ?? [
		'label' => ucfirst( $status ),
		'class' => 'bg-slate-100 text-slate-700',
	];

	return sprintf(
		'<span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium %s">%s</span>',
		esc_attr( $variant['class'] ),
		esc_html( $variant['label'] )
	);
}

/**
 * Render a user avatar cell.
 *
 * @param array $user User data with 'name', 'email', 'avatar'.
 * @return string HTML output.
 */
function apollo_render_table_user( array $user ) {
	$name   = $user['name'] ?? 'Usuário';
	$email  = $user['email'] ?? '';
	$avatar = $user['avatar'] ?? '';

	ob_start();
	?>
	<div class="flex items-center gap-3">
		<div class="h-8 w-8 shrink-0 overflow-hidden rounded-full bg-muted">
			<?php if ( $avatar ) : ?>
				<img src="<?php echo esc_url( $avatar ); ?>" alt="" class="h-full w-full object-cover" />
			<?php else : ?>
				<span class="flex h-full w-full items-center justify-center text-xs font-medium">
					<?php echo esc_html( strtoupper( substr( $name, 0, 2 ) ) ); ?>
				</span>
			<?php endif; ?>
		</div>
		<div>
			<div class="font-medium"><?php echo esc_html( $name ); ?></div>
			<?php if ( $email ) : ?>
				<div class="text-xs text-muted-foreground"><?php echo esc_html( $email ); ?></div>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
