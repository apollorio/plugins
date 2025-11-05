<?php
/**
 * Apollo Events Admin Dashboard
 * 
 * Admin pages for analytics and user overviews.
 * 
 * @package Apollo_Events_Manager
 * @since 2.1.0
 */

defined('ABSPATH') || exit;

/**
 * Apollo Events Admin Dashboard Class
 * 
 * Manages admin menu pages for:
 * - Global dashboard
 * - User overview
 * - Does NOT touch the existing placeholders page
 */
class Apollo_Events_Admin_Dashboard {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'add_capabilities'));
        add_action('admin_menu', array($this, 'register_admin_pages'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Add analytics capabilities to roles
     * Idempotent - safe to call multiple times
     */
    public function add_capabilities() {
        // Only run once
        if (get_option('apollo_analytics_caps_added')) {
            return;
        }
        
        // Add capability to administrator
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('view_apollo_event_stats');
        }
        
        // Add capability to editor
        $editor = get_role('editor');
        if ($editor) {
            $editor->add_cap('view_apollo_event_stats');
        }
        
        // Mark as completed
        update_option('apollo_analytics_caps_added', true);
    }
    
    /**
     * Register admin menu pages
     */
    public function register_admin_pages() {
        // Dashboard page
        add_submenu_page(
            'edit.php?post_type=event_listing',
            __('Dashboard', 'apollo-events-manager'),
            __('Dashboard', 'apollo-events-manager'),
            'view_apollo_event_stats',
            'apollo-events-dashboard',
            array($this, 'render_dashboard_page')
        );
        
        // User Overview page
        add_submenu_page(
            'edit.php?post_type=event_listing',
            __('User Overview', 'apollo-events-manager'),
            __('User Overview', 'apollo-events-manager'),
            'view_apollo_event_stats',
            'apollo-events-user-overview',
            array($this, 'render_user_overview_page')
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only on our pages
        if (strpos($hook, 'apollo-events-') === false) {
            return;
        }
        
        wp_enqueue_style('apollo-admin-dashboard', APOLLO_WPEM_URL . 'assets/admin-dashboard.css', array(), APOLLO_WPEM_VERSION);
    }
    
    /**
     * Render Dashboard Page
     */
    public function render_dashboard_page() {
        if (!current_user_can('view_apollo_event_stats')) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'apollo-events-manager'));
        }
        
        // Get global stats
        $global_stats = apollo_events_analytics_get_global_stats();
        $top_events = apollo_events_analytics_get_top_events(10);
        $top_sounds = apollo_events_analytics_get_top_sounds(10);
        $top_locals = apollo_events_analytics_get_top_locals(10);
        
        ?>
        <div class="wrap apollo-dashboard">
            <h1><?php _e('Apollo Events Dashboard', 'apollo-events-manager'); ?></h1>
            
            <!-- KPIs -->
            <div class="apollo-kpi-grid">
                <div class="apollo-kpi-card">
                    <div class="apollo-kpi-value"><?php echo number_format_i18n($global_stats['total_events']); ?></div>
                    <div class="apollo-kpi-label"><?php _e('Total de Eventos', 'apollo-events-manager'); ?></div>
                </div>
                
                <div class="apollo-kpi-card">
                    <div class="apollo-kpi-value"><?php echo number_format_i18n($global_stats['future_events']); ?></div>
                    <div class="apollo-kpi-label"><?php _e('Eventos Futuros', 'apollo-events-manager'); ?></div>
                </div>
                
                <div class="apollo-kpi-card">
                    <div class="apollo-kpi-value"><?php echo number_format_i18n($global_stats['past_events']); ?></div>
                    <div class="apollo-kpi-label"><?php _e('Eventos Passados', 'apollo-events-manager'); ?></div>
                </div>
                
                <div class="apollo-kpi-card">
                    <div class="apollo-kpi-value"><?php echo number_format_i18n($global_stats['total_views']); ?></div>
                    <div class="apollo-kpi-label"><?php _e('Total de Visualizações', 'apollo-events-manager'); ?></div>
                </div>
            </div>
            
            <!-- Top Events -->
            <div class="apollo-dashboard-section">
                <h2><?php _e('Top Eventos por Visualizações', 'apollo-events-manager'); ?></h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Evento', 'apollo-events-manager'); ?></th>
                            <th width="150"><?php _e('Visualizações', 'apollo-events-manager'); ?></th>
                            <th width="100"><?php _e('Ações', 'apollo-events-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($top_events)): ?>
                            <?php foreach ($top_events as $event): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($event->post_title); ?></strong>
                                    </td>
                                    <td><?php echo number_format_i18n($event->views); ?></td>
                                    <td>
                                        <a href="<?php echo get_edit_post_link($event->ID); ?>" class="button button-small">
                                            <?php _e('Editar', 'apollo-events-manager'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3"><?php _e('Nenhum dado disponível ainda.', 'apollo-events-manager'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Top Sounds -->
            <div class="apollo-dashboard-section">
                <h2><?php _e('Top Sons por Número de Eventos', 'apollo-events-manager'); ?></h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Som', 'apollo-events-manager'); ?></th>
                            <th width="150"><?php _e('Eventos', 'apollo-events-manager'); ?></th>
                            <th width="200"><?php _e('Percentual', 'apollo-events-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($top_sounds)): ?>
                            <?php 
                            $total = array_sum(wp_list_pluck($top_sounds, 'event_count'));
                            foreach ($top_sounds as $sound): 
                                $percent = $total > 0 ? ($sound->event_count / $total) * 100 : 0;
                            ?>
                                <tr>
                                    <td><strong><?php echo esc_html($sound->name); ?></strong></td>
                                    <td><?php echo number_format_i18n($sound->event_count); ?></td>
                                    <td>
                                        <div class="apollo-progress-bar">
                                            <div class="apollo-progress-fill" style="width: <?php echo esc_attr($percent); ?>%;"></div>
                                            <span class="apollo-progress-label"><?php echo number_format($percent, 1); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3"><?php _e('Nenhum dado disponível ainda.', 'apollo-events-manager'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Top Locals -->
            <div class="apollo-dashboard-section">
                <h2><?php _e('Top Locais por Número de Eventos', 'apollo-events-manager'); ?></h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Local', 'apollo-events-manager'); ?></th>
                            <th width="150"><?php _e('Eventos', 'apollo-events-manager'); ?></th>
                            <th width="200"><?php _e('Percentual', 'apollo-events-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($top_locals)): ?>
                            <?php 
                            $total = array_sum(wp_list_pluck($top_locals, 'event_count'));
                            foreach ($top_locals as $local): 
                                $percent = $total > 0 ? ($local->event_count / $total) * 100 : 0;
                            ?>
                                <tr>
                                    <td><strong><?php echo esc_html($local->post_title); ?></strong></td>
                                    <td><?php echo number_format_i18n($local->event_count); ?></td>
                                    <td>
                                        <div class="apollo-progress-bar">
                                            <div class="apollo-progress-fill" style="width: <?php echo esc_attr($percent); ?>%;"></div>
                                            <span class="apollo-progress-label"><?php echo number_format($percent, 1); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3"><?php _e('Nenhum dado disponível ainda.', 'apollo-events-manager'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <style>
        .apollo-dashboard {
            max-width: 1400px;
        }
        .apollo-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .apollo-kpi-card {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }
        .apollo-kpi-value {
            font-size: 48px;
            font-weight: bold;
            color: #2271b1;
            margin-bottom: 10px;
        }
        .apollo-kpi-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .apollo-dashboard-section {
            background: #fff;
            padding: 20px;
            margin: 30px 0;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .apollo-dashboard-section h2 {
            margin-top: 0;
            padding-bottom: 15px;
            border-bottom: 2px solid #2271b1;
        }
        .apollo-progress-bar {
            position: relative;
            height: 25px;
            background: #f0f0f0;
            border-radius: 4px;
            overflow: hidden;
        }
        .apollo-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #2271b1, #4a9fd8);
            transition: width 0.3s ease;
        }
        .apollo-progress-label {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            font-size: 12px;
            font-weight: bold;
            color: #fff;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }
        </style>
        <?php
    }
    
    /**
     * Render User Overview Page
     */
    public function render_user_overview_page() {
        if (!current_user_can('view_apollo_event_stats')) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'apollo-events-manager'));
        }
        
        // Get target user (from query param or current user)
        $target_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : get_current_user_id();
        $target_user = get_userdata($target_user_id);
        
        if (!$target_user) {
            $target_user_id = get_current_user_id();
            $target_user = get_userdata($target_user_id);
        }
        
        // Get analytics
        $analytics = new Apollo_Events_Analytics();
        $user_stats = $analytics->get_user_stats($target_user_id);
        $sound_distribution = $analytics->get_user_sound_distribution($target_user_id);
        $location_distribution = $analytics->get_user_location_distribution($target_user_id);
        
        ?>
        <div class="wrap apollo-user-overview">
            <h1><?php _e('User Overview', 'apollo-events-manager'); ?></h1>
            
            <!-- User Selector -->
            <div class="apollo-user-selector">
                <form method="get" action="">
                    <input type="hidden" name="post_type" value="event_listing">
                    <input type="hidden" name="page" value="apollo-events-user-overview">
                    
                    <label for="user_id"><?php _e('Selecionar Usuário:', 'apollo-events-manager'); ?></label>
                    <?php
                    wp_dropdown_users(array(
                        'name' => 'user_id',
                        'id' => 'user_id',
                        'selected' => $target_user_id,
                        'show_option_all' => false,
                    ));
                    ?>
                    
                    <button type="submit" class="button button-primary">
                        <?php _e('Ver Overview', 'apollo-events-manager'); ?>
                    </button>
                </form>
            </div>
            
            <!-- User Info -->
            <div class="apollo-user-info">
                <h2><?php echo esc_html($target_user->display_name); ?></h2>
                <p class="description">
                    <?php printf(__('Email: %s | Função: %s', 'apollo-events-manager'), 
                        esc_html($target_user->user_email),
                        esc_html(implode(', ', $target_user->roles))
                    ); ?>
                </p>
            </div>
            
            <!-- Stats Cards -->
            <div class="apollo-kpi-grid">
                <div class="apollo-kpi-card">
                    <div class="apollo-kpi-value"><?php echo number_format_i18n($user_stats['coauthored_count']); ?></div>
                    <div class="apollo-kpi-label"><?php _e('Eventos como Co-autor', 'apollo-events-manager'); ?></div>
                </div>
                
                <div class="apollo-kpi-card">
                    <div class="apollo-kpi-value"><?php echo number_format_i18n($user_stats['favorited_count']); ?></div>
                    <div class="apollo-kpi-label"><?php _e('Eventos de Interesse', 'apollo-events-manager'); ?></div>
                </div>
                
                <div class="apollo-kpi-card">
                    <div class="apollo-kpi-value"><?php echo number_format_i18n($user_stats['total_views']); ?></div>
                    <div class="apollo-kpi-label"><?php _e('Total de Visualizações', 'apollo-events-manager'); ?></div>
                </div>
            </div>
            
            <!-- Sound Distribution -->
            <div class="apollo-dashboard-section">
                <h2><?php _e('Distribuição de Sons', 'apollo-events-manager'); ?></h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Som', 'apollo-events-manager'); ?></th>
                            <th width="150"><?php _e('Contagem', 'apollo-events-manager'); ?></th>
                            <th width="200"><?php _e('Percentual', 'apollo-events-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($sound_distribution)): ?>
                            <?php 
                            $total = array_sum($sound_distribution);
                            foreach ($sound_distribution as $sound_slug => $count): 
                                $term = get_term_by('slug', $sound_slug, 'event_sounds');
                                $percent = $total > 0 ? ($count / $total) * 100 : 0;
                            ?>
                                <tr>
                                    <td><strong><?php echo $term ? esc_html($term->name) : esc_html($sound_slug); ?></strong></td>
                                    <td><?php echo number_format_i18n($count); ?></td>
                                    <td>
                                        <div class="apollo-progress-bar">
                                            <div class="apollo-progress-fill" style="width: <?php echo esc_attr($percent); ?>%;"></div>
                                            <span class="apollo-progress-label"><?php echo number_format($percent, 1); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3"><?php _e('Nenhuma interação com eventos ainda.', 'apollo-events-manager'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Location Distribution -->
            <div class="apollo-dashboard-section">
                <h2><?php _e('Distribuição de Locais', 'apollo-events-manager'); ?></h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Local', 'apollo-events-manager'); ?></th>
                            <th width="150"><?php _e('Contagem', 'apollo-events-manager'); ?></th>
                            <th width="200"><?php _e('Percentual', 'apollo-events-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($location_distribution)): ?>
                            <?php 
                            $total = array_sum($location_distribution);
                            foreach ($location_distribution as $local_id => $count): 
                                $local = get_post($local_id);
                                $percent = $total > 0 ? ($count / $total) * 100 : 0;
                            ?>
                                <tr>
                                    <td><strong><?php echo $local ? esc_html($local->post_title) : __('Local desconhecido', 'apollo-events-manager'); ?></strong></td>
                                    <td><?php echo number_format_i18n($count); ?></td>
                                    <td>
                                        <div class="apollo-progress-bar">
                                            <div class="apollo-progress-fill" style="width: <?php echo esc_attr($percent); ?>%;"></div>
                                            <span class="apollo-progress-label"><?php echo number_format($percent, 1); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3"><?php _e('Nenhuma interação com eventos ainda.', 'apollo-events-manager'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <style>
        .apollo-user-overview {
            max-width: 1400px;
        }
        .apollo-user-selector {
            background: #fff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .apollo-user-selector form {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .apollo-user-selector label {
            font-weight: 600;
        }
        .apollo-user-info {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border-left: 4px solid #2271b1;
        }
        .apollo-user-info h2 {
            margin: 0 0 10px 0;
        }
        .apollo-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .apollo-kpi-card {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }
        .apollo-kpi-value {
            font-size: 48px;
            font-weight: bold;
            color: #2271b1;
            margin-bottom: 10px;
        }
        .apollo-kpi-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .apollo-dashboard-section {
            background: #fff;
            padding: 20px;
            margin: 30px 0;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .apollo-dashboard-section h2 {
            margin-top: 0;
            padding-bottom: 15px;
            border-bottom: 2px solid #2271b1;
        }
        .apollo-progress-bar {
            position: relative;
            height: 25px;
            background: #f0f0f0;
            border-radius: 4px;
            overflow: hidden;
        }
        .apollo-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #2271b1, #4a9fd8);
            transition: width 0.3s ease;
        }
        .apollo-progress-label {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            font-size: 12px;
            font-weight: bold;
            color: #fff;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }
        </style>
        <?php
    }
}

// Initialize admin dashboard
if (is_admin()) {
    new Apollo_Events_Admin_Dashboard();
}
