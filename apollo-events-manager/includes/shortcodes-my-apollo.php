<?php
// phpcs:ignoreFile
/**
 * My Apollo Dashboard Shortcode
 * Shortcode: [my_apollo_dashboard]
 *
 * Shows user's events: created, co-authored, and favorited
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * My Apollo Dashboard Shortcode
 */
function apollo_my_apollo_dashboard_shortcode($atts)
{
    // Check if user is logged in
    if (! is_user_logged_in()) {
        $login_url = wp_login_url(get_permalink());

        return '<div class="apollo-auth-required glass p-6 rounded-lg text-center">
            <h3 class="text-xl font-semibold mb-4">Login Necessário</h3>
            <p class="mb-4">Você precisa estar logado para ver seu dashboard.</p>
            <a href="' . esc_url($login_url) . '" class="btn btn-primary">Entrar</a>
        </div>';
    }

    $current_user_id = get_current_user_id();
    $current_user    = wp_get_current_user();

    // Get active tab from URL or default to 'created'
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'created';
    $valid_tabs = [ 'created', 'gestao', 'favorites' ];
    if (! in_array($active_tab, $valid_tabs)) {
        $active_tab = 'created';
    }

    // Get events based on tab
    $events    = [];
    $tab_title = '';

    switch ($active_tab) {
        case 'created':
            // Events created by user
            $query = new WP_Query(
                [
                    'post_type'      => 'event_listing',
                    'author'         => $current_user_id,
                    'posts_per_page' => -1,
                    'post_status'    => [ 'publish', 'pending', 'draft' ],
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                ]
            );
            $events    = $query->posts;
            $tab_title = 'Meus Eventos Criados';

            break;

        case 'gestao':
            // Events em gestão pelo usuário (using Co-Authors Plus if available)
            $event_ids = [];

            if (function_exists('get_coauthors')) {
                // Use Co-Authors Plus API
                $all_events = get_posts(
                    [
                        'post_type'      => 'event_listing',
                        'posts_per_page' => -1,
                        'post_status'    => [ 'publish', 'pending', 'draft' ],
                    ]
                );

                foreach ($all_events as $event) {
                    $coauthors = get_coauthors($event->ID);
                    foreach ($coauthors as $coauthor) {
                        if ($coauthor->ID == $current_user_id) {
                            $event_ids[] = $event->ID;

                            break;
                        }
                    }
                }
            } else {
                // Fallback: check _event_gestao meta
                $query = new WP_Query(
                    [
                        'post_type'      => 'event_listing',
                        'posts_per_page' => -1,
                        'post_status'    => [ 'publish', 'pending', 'draft' ],
                        'meta_query'     => [
                            [
                                'key'     => '_event_gestao',
                                'value'   => $current_user_id,
                                'compare' => 'LIKE',
                            ],
                        ],
                    ]
                );
                $event_ids = wp_list_pluck($query->posts, 'ID');
            }//end if

            if (! empty($event_ids)) {
                $events = get_posts(
                    [
                        'post_type'      => 'event_listing',
                        'post__in'       => $event_ids,
                        'posts_per_page' => -1,
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                    ]
                );
            }
            $tab_title = 'Eventos Co-Autorados';

            break;

        case 'favorites':
            // Favorited events
            $favorite_ids = [];

            // Try aprio-bookmarks first
            if (function_exists('get_user_favorites')) {
                $favorite_ids = get_user_favorites($current_user_id, null, [ 'post_types' => 'event_listing' ]);
            } else {
                // Fallback: check user meta
                $favorites_raw = get_user_meta($current_user_id, 'apollo_favorites', true);
                if (is_array($favorites_raw)) {
                    $favorite_ids = array_map('absint', $favorites_raw);
                }
            }

            if (! empty($favorite_ids)) {
                $events = get_posts(
                    [
                        'post_type'      => 'event_listing',
                        'post__in'       => $favorite_ids,
                        'posts_per_page' => -1,
                        'post_status'    => 'publish',
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                    ]
                );
            }
            $tab_title = 'Meus Favoritos';

            break;
    }//end switch

    ob_start();
    ?>
	<div class="apollo-my-apollo-dashboard glass p-6 rounded-lg max-w-7xl mx-auto">
		<div class="mb-6">
			<h1 class="text-3xl font-bold mb-2">Meu Apollo</h1>
			<p class="text-gray-600">Olá, <strong><?php echo esc_html($current_user->display_name); ?></strong></p>
		</div>
		
		<!-- Tabs -->
		<div class="apollo-dashboard-tabs mb-6 border-b">
			<nav class="flex gap-4">
				<a 
					href="<?php echo esc_url(add_query_arg('tab', 'created', get_permalink())); ?>" 
					class="px-4 py-2 font-medium <?php echo $active_tab === 'created' ? 'border-b-2 border-primary text-primary' : 'text-gray-600 hover:text-primary'; ?>"
				>
					<i class="ri-calendar-event-line"></i> Criados
					<?php if ($active_tab === 'created') : ?>
						<span class="ml-2 text-sm text-gray-500">(<?php echo count($events); ?>)</span>
					<?php endif; ?>
				</a>
				<a 
					href="<?php echo esc_url(add_query_arg('tab', 'gestao', get_permalink())); ?>" 
					class="px-4 py-2 font-medium <?php echo $active_tab === 'gestao' ? 'border-b-2 border-primary text-primary' : 'text-gray-600 hover:text-primary'; ?>"
				>
					<i class="ri-team-line"></i> Gestão
					<?php if ($active_tab === 'gestao') : ?>
						<span class="ml-2 text-sm text-gray-500">(<?php echo count($events); ?>)</span>
					<?php endif; ?>
				</a>
				<a 
					href="<?php echo esc_url(add_query_arg('tab', 'favorites', get_permalink())); ?>" 
					class="px-4 py-2 font-medium <?php echo $active_tab === 'favorites' ? 'border-b-2 border-primary text-primary' : 'text-gray-600 hover:text-primary'; ?>"
				>
					<i class="ri-heart-line"></i> Favoritos
					<?php if ($active_tab === 'favorites') : ?>
						<span class="ml-2 text-sm text-gray-500">(<?php echo count($events); ?>)</span>
					<?php endif; ?>
				</a>
			</nav>
		</div>
		
		<!-- Tab Content -->
		<div class="apollo-dashboard-content">
			<h2 class="text-xl font-semibold mb-4"><?php echo esc_html($tab_title); ?></h2>
			
			<?php if (empty($events)) : ?>
				<div class="apollo-empty-state text-center py-12">
					<i class="ri-calendar-event-line text-6xl text-gray-300 mb-4"></i>
					<p class="text-gray-600 text-lg mb-2">
						<?php
                        switch ($active_tab) {
                            case 'created':
                                echo 'Você ainda não criou nenhum evento.';

                                break;
                            case 'gestao':
                                echo 'Você ainda não está em gestão de nenhum evento.';

                                break;
                            case 'favorites':
                                echo 'Você ainda não favoritou nenhum evento.';

                                break;
                        }
			    ?>
					</p>
					<?php if ($active_tab === 'created') : ?>
						<a href="<?php echo esc_url(home_url('/eventos/submit/')); ?>" class="btn btn-primary mt-4">
							<i class="ri-add-line"></i> Criar Primeiro Evento
						</a>
					<?php elseif ($active_tab === 'favorites') : ?>
						<a href="<?php echo esc_url(home_url('/eventos/')); ?>" class="btn btn-primary mt-4">
							<i class="ri-search-line"></i> Explorar Eventos
						</a>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<!-- Events Grid (using same card component as portal) -->
				<div class="event_listings card-view grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
					<?php
                    global $post;
			    foreach ($events as $post) :
			        setup_postdata($post);
			        $event_id = get_the_ID();

			        // Include event card template
			        $card_template = APOLLO_APRIO_PATH . 'templates/event-card.php';
			        if (file_exists($card_template)) {
			            include $card_template;
			        } else {
			            // Fallback: simple card
			            ?>
							<div class="event_listing glass rounded-lg overflow-hidden">
								<div class="p-4">
									<h3 class="font-semibold text-lg mb-2">
										<a href="<?php echo esc_url(get_permalink()); ?>">
											<?php echo esc_html(get_the_title()); ?>
										</a>
									</h3>
									<p class="text-sm text-gray-600">
										<?php echo esc_html(get_the_date()); ?>
									</p>
									<span class="inline-block mt-2 px-2 py-1 text-xs rounded <?php echo get_post_status() === 'publish' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
										<?php
			                        $status_labels = [
			                            'publish' => 'Publicado',
			                            'pending' => 'Pendente',
			                            'draft'   => 'Rascunho',
			                        ];
			            echo esc_html($status_labels[ get_post_status() ] ?? get_post_status());
			            ?>
									</span>
								</div>
							</div>
							<?php
			        }//end if
			    endforeach;
			    wp_reset_postdata();
			    ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	
	<style>
	.apollo-my-apollo-dashboard .apollo-dashboard-tabs nav a {
		transition: all 0.2s;
	}
	.apollo-my-apollo-dashboard .apollo-dashboard-tabs nav a:hover {
		color: var(--primary-color, #3b82f6);
	}
	.apollo-my-apollo-dashboard .event_listings.card-view {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
		gap: 1.5rem;
	}
	@media (max-width: 768px) {
		.apollo-my-apollo-dashboard .event_listings.card-view {
			grid-template-columns: 1fr;
		}
	}
	</style>
	<?php
    return ob_get_clean();
}

// Register shortcode
add_shortcode('my_apollo_dashboard', 'apollo_my_apollo_dashboard_shortcode');
