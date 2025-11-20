<?php
/**
 * Event Dashboard with Motion.dev Tabs
 * 
 * Public-facing event dashboard with smooth tab transitions
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

if (!is_user_logged_in()) {
    echo '<p>' . esc_html__('Você precisa estar logado para ver esta página.', 'apollo-events-manager') . '</p>';
    return;
}

$current_user_id = get_current_user_id();
?>

<div class="apollo-event-dashboard" data-motion-tabs="true">
    <h1><?php echo esc_html__('Meu Dashboard de Eventos', 'apollo-events-manager'); ?></h1>
    
    <!-- Tabs Navigation -->
    <div class="tabs-nav" style="display: flex; gap: 1rem; border-bottom: 2px solid #e0e0e0; padding-bottom: 0; position: relative; margin: 2rem 0 1rem;">
        <button class="tab-trigger active" data-tab-trigger="overview" aria-selected="true" style="padding: 1rem 1.5rem; border: none; background: none; cursor: pointer; font-weight: 600; color: #007cba; position: relative;">
            <?php echo esc_html__('Visão Geral', 'apollo-events-manager'); ?>
        </button>
        <button class="tab-trigger" data-tab-trigger="my-events" aria-selected="false" style="padding: 1rem 1.5rem; border: none; background: none; cursor: pointer; font-weight: 600; color: #666; position: relative;">
            <?php echo esc_html__('Meus Eventos', 'apollo-events-manager'); ?>
        </button>
        <button class="tab-trigger" data-tab-trigger="favorites" aria-selected="false" style="padding: 1rem 1.5rem; border: none; background: none; cursor: pointer; font-weight: 600; color: #666; position: relative;">
            <?php echo esc_html__('Favoritos', 'apollo-events-manager'); ?>
        </button>
        <button class="tab-trigger" data-tab-trigger="statistics" aria-selected="false" style="padding: 1rem 1.5rem; border: none; background: none; cursor: pointer; font-weight: 600; color: #666; position: relative;">
            <?php echo esc_html__('Estatísticas', 'apollo-events-manager'); ?>
        </button>
    </div>
    
    <!-- Tab Panels -->
    <div class="tabs-content">
        <!-- Overview Panel -->
        <div class="tab-panel" data-tab-panel="overview" style="display: block;">
            <div class="overview-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin: 2rem 0;">
                <?php
                // Get user's event count
                $user_events_count = count_user_posts($current_user_id, 'event_listing');
                
                // Get favorites count
                $user_favorites = get_user_meta($current_user_id, 'apollo_favorites', true);
                $favorites_count = is_array($user_favorites) ? count($user_favorites) : 0;
                
                // Get total views for user's events
                $user_events_query = new WP_Query(array(
                    'post_type' => 'event_listing',
                    'author' => $current_user_id,
                    'posts_per_page' => -1
                ));
                
                $total_user_views = 0;
                if ($user_events_query->have_posts()) {
                    while ($user_events_query->have_posts()) {
                        $user_events_query->the_post();
                        if (class_exists('Apollo_Event_Statistics')) {
                            $stats = Apollo_Event_Statistics::get_event_stats(get_the_ID());
                            $total_user_views += $stats['total_views'];
                        }
                    }
                    wp_reset_postdata();
                }
                ?>
                
                <div class="stat-card" style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <div style="font-size: 0.875rem; color: #666; margin-bottom: 0.5rem;">
                        <?php echo esc_html__('Meus Eventos', 'apollo-events-manager'); ?>
                    </div>
                    <div style="font-size: 2rem; font-weight: bold; color: #007cba;">
                        <?php echo esc_html($user_events_count); ?>
                    </div>
                </div>
                
                <div class="stat-card" style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <div style="font-size: 0.875rem; color: #666; margin-bottom: 0.5rem;">
                        <?php echo esc_html__('Favoritos', 'apollo-events-manager'); ?>
                    </div>
                    <div style="font-size: 2rem; font-weight: bold; color: #9b51e0;">
                        <?php echo esc_html($favorites_count); ?>
                    </div>
                </div>
                
                <div class="stat-card" style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <div style="font-size: 0.875rem; color: #666; margin-bottom: 0.5rem;">
                        <?php echo esc_html__('Total de Visualizações', 'apollo-events-manager'); ?>
                    </div>
                    <div style="font-size: 2rem; font-weight: bold; color: #00a32a;">
                        <?php echo number_format($total_user_views, 0, ',', '.'); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- My Events Panel -->
        <div class="tab-panel" data-tab-panel="my-events" style="display: none;">
            <?php
            $template_path = APOLLO_WPEM_PATH . 'templates/user-event-dashboard.php';
            if (file_exists($template_path)) {
                include $template_path;
            }
            ?>
        </div>
        
        <!-- Favorites Panel -->
        <div class="tab-panel" data-tab-panel="favorites" style="display: none;">
            <p><?php echo esc_html__('Lista de favoritos em desenvolvimento...', 'apollo-events-manager'); ?></p>
        </div>
        
        <!-- Statistics Panel -->
        <div class="tab-panel" data-tab-panel="statistics" style="display: none;">
            <p><?php echo esc_html__('Estatísticas detalhadas em desenvolvimento...', 'apollo-events-manager'); ?></p>
        </div>
    </div>
</div>

<style>
.tab-trigger {
    transition: color 0.2s ease;
}

.tab-trigger:hover {
    color: #007cba !important;
}

.tab-trigger.active {
    color: #007cba !important;
}

.tab-panel {
    animation: fadeInTab 0.3s ease-out;
}

@keyframes fadeInTab {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

