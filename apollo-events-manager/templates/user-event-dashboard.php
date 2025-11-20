<?php
/**
 * User Event Dashboard Template
 * 
 * Shows statistics for user's own events
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

// Security check
if (!is_user_logged_in()) {
    echo '<p>' . esc_html__('Você precisa estar logado para ver esta página.', 'apollo-events-manager') . '</p>';
    return;
}

$current_user_id = get_current_user_id();

// Get user's events
$user_events = new WP_Query(array(
    'post_type' => 'event_listing',
    'author' => $current_user_id,
    'posts_per_page' => -1,
    'post_status' => array('publish', 'pending', 'draft')
));

$total_events = $user_events->found_posts;
$total_views = 0;
$total_popup = 0;
$total_page = 0;
$events_data = array();

if ($user_events->have_posts()) {
    while ($user_events->have_posts()) {
        $user_events->the_post();
        $event_id = get_the_ID();
        
        // Use CPT if available, fallback to meta
        if (class_exists('Apollo_Event_Stat_CPT')) {
            $stats = Apollo_Event_Stat_CPT::get_stats($event_id);
        } elseif (class_exists('Apollo_Event_Statistics')) {
            $stats = Apollo_Event_Statistics::get_event_stats($event_id);
            
            $total_views += $stats['total_views'];
            $total_popup += $stats['popup_count'];
            $total_page += $stats['page_count'];
            
            $events_data[] = array(
                'id' => $event_id,
                'title' => get_the_title(),
                'status' => get_post_status(),
                'stats' => $stats,
                'date' => get_the_date('Y-m-d H:i:s')
            );
        }
    }
    wp_reset_postdata();
}
?>

<div class="apollo-user-dashboard" style="max-width: 1200px; margin: 0 auto; padding: 2rem;">
    <h1><?php echo esc_html__('Meus Eventos', 'apollo-events-manager'); ?></h1>
    
    <!-- Summary Cards -->
    <div class="dashboard-summary" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin: 2rem 0;">
        
        <div class="summary-card" style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div style="font-size: 0.875rem; color: #666; margin-bottom: 0.5rem;">
                <?php echo esc_html__('Total de Eventos', 'apollo-events-manager'); ?>
            </div>
            <div style="font-size: 2rem; font-weight: bold; color: #007cba;">
                <?php echo esc_html($total_events); ?>
            </div>
        </div>
        
        <div class="summary-card" style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div style="font-size: 0.875rem; color: #666; margin-bottom: 0.5rem;">
                <?php echo esc_html__('Visualizações Totais', 'apollo-events-manager'); ?>
            </div>
            <div style="font-size: 2rem; font-weight: bold; color: #9b51e0;">
                <?php echo number_format($total_views, 0, ',', '.'); ?>
            </div>
        </div>
        
        <div class="summary-card" style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div style="font-size: 0.875rem; color: #666; margin-bottom: 0.5rem;">
                <?php echo esc_html__('Visualizações Modal', 'apollo-events-manager'); ?>
            </div>
            <div style="font-size: 2rem; font-weight: bold; color: #00a32a;">
                <?php echo number_format($total_popup, 0, ',', '.'); ?>
            </div>
        </div>
        
        <div class="summary-card" style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div style="font-size: 0.875rem; color: #666; margin-bottom: 0.5rem;">
                <?php echo esc_html__('Visualizações Página', 'apollo-events-manager'); ?>
            </div>
            <div style="font-size: 2rem; font-weight: bold; color: #f15a24;">
                <?php echo number_format($total_page, 0, ',', '.'); ?>
            </div>
        </div>
    </div>
    
    <!-- Events List -->
    <?php if (!empty($events_data)): ?>
    <div class="events-list" style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 2rem;">
        <h2 style="margin-top: 0;"><?php echo esc_html__('Detalhes dos Eventos', 'apollo-events-manager'); ?></h2>
        
        <table class="wp-list-table widefat fixed striped" style="margin-top: 1rem;">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Evento', 'apollo-events-manager'); ?></th>
                    <th><?php echo esc_html__('Status', 'apollo-events-manager'); ?></th>
                    <th><?php echo esc_html__('Total', 'apollo-events-manager'); ?></th>
                    <th><?php echo esc_html__('Modal', 'apollo-events-manager'); ?></th>
                    <th><?php echo esc_html__('Página', 'apollo-events-manager'); ?></th>
                    <th><?php echo esc_html__('Ações', 'apollo-events-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events_data as $event): ?>
                <tr>
                    <td>
                        <strong>
                            <a href="<?php echo esc_url(get_permalink($event['id'])); ?>" target="_blank">
                                <?php echo esc_html($event['title']); ?>
                            </a>
                        </strong>
                    </td>
                    <td>
                        <?php
                        $status_labels = array(
                            'publish' => __('Publicado', 'apollo-events-manager'),
                            'pending' => __('Pendente', 'apollo-events-manager'),
                            'draft' => __('Rascunho', 'apollo-events-manager')
                        );
                        echo esc_html($status_labels[$event['status']] ?? $event['status']);
                        ?>
                    </td>
                    <td><strong><?php echo number_format($event['stats']['total_views'], 0, ',', '.'); ?></strong></td>
                    <td><?php echo number_format($event['stats']['popup_count'], 0, ',', '.'); ?></td>
                    <td><?php echo number_format($event['stats']['page_count'], 0, ',', '.'); ?></td>
                    <td>
                        <a href="<?php echo esc_url(get_edit_post_link($event['id'])); ?>" class="button button-small">
                            <?php echo esc_html__('Editar', 'apollo-events-manager'); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="no-events" style="background: #fff; border-radius: 12px; padding: 3rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; margin-top: 2rem;">
        <p><?php echo esc_html__('Você ainda não criou nenhum evento.', 'apollo-events-manager'); ?></p>
        <a href="<?php echo esc_url(admin_url('post-new.php?post_type=event_listing')); ?>" class="button button-primary">
            <?php echo esc_html__('Criar Primeiro Evento', 'apollo-events-manager'); ?>
        </a>
    </div>
    <?php endif; ?>
</div>

<style>
.summary-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15) !important;
}
</style>
