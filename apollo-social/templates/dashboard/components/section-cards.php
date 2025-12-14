<?php
/**
 * ShadCN UI Section Cards Component (New York Style)
 *
 * Dashboard statistics cards with icons and trends.
 * Based on shadcn/ui card component.
 *
 * @package    ApolloSocial
 * @subpackage Dashboard/Components
 * @since      1.3.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Render the Section Cards grid.
 *
 * @param array $cards Array of card data.
 */
function apollo_render_section_cards(array $cards = [])
{
    // Use default cards if none provided
    if (empty($cards)) {
        $cards = apollo_get_default_dashboard_cards();
    }
    ?>
	<div class="*:data-[slot=card]:shadow-xs @xl/main:grid-cols-2 @5xl/main:grid-cols-4 grid grid-cols-1 gap-4 px-4 lg:px-6">
		<?php foreach ($cards as $card) : ?>
			<?php apollo_render_stat_card($card); ?>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Render a single stat card.
 *
 * @param array $card {
 *     Card data.
 *     @type string $title       Card title.
 *     @type string $value       Main value to display.
 *     @type string $description Description or subtitle.
 *     @type string $trend       Trend indicator: 'up', 'down', or null.
 *     @type string $trend_value Trend percentage or value.
 *     @type string $icon        SVG icon markup.
 *     @type string $footer      Footer text.
 *     @type string $badge       Optional badge text.
 *     @type string $tooltip     Optional tooltip text for accessibility.
 * }
 */
function apollo_render_stat_card(array $card)
{
    $title       = $card['title']       ?? '';
    $value       = $card['value']       ?? '0';
    $description = $card['description'] ?? '';
    $trend       = $card['trend']       ?? null;
    $trend_value = $card['trend_value'] ?? '';
    $icon        = $card['icon']        ?? '';
    $footer      = $card['footer']      ?? '';
    $badge       = $card['badge']       ?? '';
    $tooltip     = $card['tooltip']     ?? '';

    $trend_classes = 'inline-flex items-center gap-1 text-xs font-medium';
    if ($trend === 'up') {
        $trend_classes .= ' text-emerald-600';
    } elseif ($trend === 'down') {
        $trend_classes .= ' text-red-600';
    } else {
        $trend_classes .= ' text-muted-foreground';
    }
    ?>
	<div 
		class="apollo-stat-card rounded-xl border bg-card text-card-foreground shadow-sm transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 cursor-help"
		data-slot="card"
		<?php if (! empty($tooltip)) : ?>
			data-tooltip="<?php echo esc_attr($tooltip); ?>"
			data-tooltip-multiline
		<?php endif; ?>
	>
		<div class="flex flex-col space-y-1.5 p-4 pb-2">
			<div class="flex items-center justify-between">
				<span class="text-sm font-medium text-muted-foreground">
					<?php echo esc_html($title); ?>
				</span>
				<?php if (! empty($icon)) : ?>
					<span class="text-muted-foreground/60">
                        <?php echo $icon; // phpcs:ignore -- SVG icon?>
					</span>
				<?php endif; ?>
			</div>
			<?php if (! empty($badge)) : ?>
				<span class="inline-flex self-start items-center rounded-md bg-muted px-2 py-0.5 text-xs font-medium">
					<?php echo esc_html($badge); ?>
				</span>
			<?php endif; ?>
		</div>
		<div class="p-4 pt-0">
			<div class="flex items-baseline gap-2">
				<span class="text-2xl font-bold tracking-tight">
					<?php echo esc_html($value); ?>
				</span>
				<?php if ($trend && $trend_value) : ?>
					<span class="<?php echo esc_attr($trend_classes); ?>">
						<?php if ($trend === 'up') : ?>
							<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="m5 12 7-7 7 7"/><path d="M12 19V5"/>
							</svg>
						<?php elseif ($trend === 'down') : ?>
							<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M12 5v14"/><path d="m19 12-7 7-7-7"/>
							</svg>
						<?php endif; ?>
						<?php echo esc_html($trend_value); ?>
					</span>
				<?php endif; ?>
			</div>
			<?php if (! empty($description)) : ?>
				<p class="text-xs text-muted-foreground mt-1">
					<?php echo esc_html($description); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php if (! empty($footer)) : ?>
			<div class="border-t px-4 py-3 text-xs text-muted-foreground">
				<?php echo esc_html($footer); ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Get default dashboard cards with real data.
 *
 * @return array Cards data.
 */
function apollo_get_default_dashboard_cards()
{
    $user_id = get_current_user_id();

    // Get real stats
    $events_count      = apollo_get_user_events_count($user_id);
    $favorites_count   = apollo_get_user_favorites_count($user_id);
    $communities_count = apollo_get_user_communities_count($user_id);
    $docs_count        = apollo_get_user_docs_count($user_id);

    return [
        [
            'title'       => 'Eventos Criados',
            'value'       => $events_count['total'],
            'description' => 'eventos publicados',
            'trend'       => $events_count['trend'] > 0 ? 'up' : ($events_count['trend'] < 0 ? 'down' : null),
            'trend_value' => $events_count['trend'] ? abs($events_count['trend']) . ' este mês' : '',
            'tooltip'     => __('Número total de eventos que você criou como organizador/produtor na plataforma Apollo', 'apollo-social'),
            'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>',
        ],
        [
            'title'       => 'Favoritos',
            'value'       => $favorites_count['total'],
            'description' => 'eventos salvos',
            'trend'       => 'up',
            'trend_value' => '+' . $favorites_count['recent'] . ' recentes',
            'tooltip'     => __('Eventos que você marcou como Ir, Talvez ou salvou na sua lista de favoritos', 'apollo-social'),
            'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>',
        ],
        [
            'title'       => 'Comunidades',
            'value'       => $communities_count['total'],
            'description' => 'grupos ativos',
            'badge'       => $communities_count['admin'] > 0 ? $communities_count['admin'] . ' admin' : '',
            'tooltip'     => __('Comunidades e grupos dos quais você faz parte na rede Apollo Social', 'apollo-social'),
            'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        ],
        [
            'title'       => 'Documentos',
            'value'       => $docs_count['total'],
            'description' => $docs_count['pending'] > 0 ? $docs_count['pending'] . ' pendente(s)' : 'todos assinados',
            'trend'       => $docs_count['pending'] > 0 ? 'down' : null,
            'footer'      => 'Última atividade: ' . $docs_count['last_activity'],
            'tooltip'     => __('Contratos e documentos que requerem sua assinatura ou foram assinados', 'apollo-social'),
            'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>',
        ],
    ];
}

/**
 * Helper function to get user events count.
 */
function apollo_get_user_events_count($user_id)
{
    $args = [
        'post_type'      => 'event_listing',
        'author'         => $user_id,
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
    ];
    $events = get_posts($args);

    // Count events this month
    $this_month = get_posts(
        array_merge(
            $args,
            [
                'date_query' => [
                    [
                        'after'     => '1 month ago',
                        'inclusive' => true,
                    ],
                ],
            ]
        )
    );

    return [
        'total' => count($events),
        'trend' => count($this_month),
    ];
}

/**
 * Helper function to get user favorites count.
 */
function apollo_get_user_favorites_count($user_id)
{
    $favorites = get_user_meta($user_id, '_apollo_favorites', true);
    $favorites = is_array($favorites) ? $favorites : [];

    return [
        'total'  => count($favorites),
        'recent' => min(5, count($favorites)),
    ];
}

/**
 * Helper function to get user communities count.
 */
function apollo_get_user_communities_count($user_id)
{
    global $wpdb;

    $table       = $wpdb->prefix . 'apollo_group_members';
    $count       = 0;
    $admin_count = 0;

    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE user_id = %d AND status = 'active'",
                $user_id
            )
        );
        $admin_count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE user_id = %d AND role = 'admin' AND status = 'active'",
                $user_id
            )
        );
    }

    return [
        'total' => $count,
        'admin' => $admin_count,
    ];
}

/**
 * Helper function to get user documents count.
 */
function apollo_get_user_docs_count($user_id)
{
    $args = [
        'post_type'      => [ 'apollo_document', 'apollo_contract' ],
        'author'         => $user_id,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];
    $docs = get_posts($args);

    // Get pending docs
    $pending = get_posts(
        array_merge(
            $args,
            [
                'meta_query' => [
                    [
                        'key'     => '_apollo_signature_status',
                        'value'   => 'pending',
                        'compare' => '=',
                    ],
                ],
            ]
        )
    );

    // Get last activity
    $last_doc = get_posts(
        array_merge(
            $args,
            [
                'posts_per_page' => 1,
                'orderby'        => 'modified',
                'order'          => 'DESC',
            ]
        )
    );

    $last_activity = ! empty($last_doc)
        ? human_time_diff(get_post_modified_time('U', false, $last_doc[0])) . ' atrás'
        : 'Nunca';

    return [
        'total'         => count($docs),
        'pending'       => count($pending),
        'last_activity' => $last_activity,
    ];
}
