<?php
/**
 * Apollo Events Shortcodes - User-Facing Analytics
 * 
 * Front-end shortcodes for user analytics and overviews.
 * 
 * @package Apollo_Events_Manager
 * @since 2.1.0
 */

defined('ABSPATH') || exit;

/**
 * Apollo Events Shortcodes Class
 * 
 * Handles front-end shortcodes for analytics.
 */
class Apollo_Events_Shortcodes {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('apollo_event_user_overview', array($this, 'user_overview_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    /**
     * Enqueue frontend assets for shortcodes
     */
    public function enqueue_frontend_assets() {
        // Only enqueue when shortcode is present
        global $post;
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'apollo_event_user_overview')) {
            return;
        }
        
        // Inline CSS for user overview
        wp_add_inline_style('apollo-uni-css', '
            .apollo-user-overview {
                background: #fff;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                max-width: 900px;
                margin: 40px auto;
            }
            .apollo-user-overview__header {
                text-align: center;
                margin-bottom: 40px;
                padding-bottom: 20px;
                border-bottom: 2px solid #f0f0f0;
            }
            .apollo-user-overview__header h2 {
                margin: 0 0 10px 0;
                font-size: 32px;
                color: #2271b1;
            }
            .apollo-user-overview__header .description {
                color: #666;
                font-size: 16px;
            }
            .apollo-user-overview__metrics {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 40px;
            }
            .apollo-user-overview__metric {
                background: linear-gradient(135deg, #2271b1, #4a9fd8);
                color: #fff;
                padding: 30px;
                border-radius: 8px;
                text-align: center;
            }
            .apollo-user-overview__metric-value {
                font-size: 48px;
                font-weight: bold;
                margin-bottom: 10px;
            }
            .apollo-user-overview__metric-label {
                font-size: 14px;
                opacity: 0.9;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .apollo-user-overview__section {
                margin-bottom: 40px;
            }
            .apollo-user-overview__section h3 {
                font-size: 24px;
                margin-bottom: 20px;
                color: #333;
            }
            .apollo-user-overview__list {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            .apollo-user-overview__list-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 15px 0;
                border-bottom: 1px solid #f0f0f0;
            }
            .apollo-user-overview__list-item:last-child {
                border-bottom: none;
            }
            .apollo-user-overview__list-name {
                font-weight: 600;
                color: #333;
            }
            .apollo-user-overview__list-count {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .apollo-user-overview__list-number {
                font-size: 18px;
                font-weight: bold;
                color: #2271b1;
            }
            .apollo-user-overview__progress {
                width: 100px;
                height: 8px;
                background: #f0f0f0;
                border-radius: 4px;
                overflow: hidden;
            }
            .apollo-user-overview__progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #2271b1, #4a9fd8);
                transition: width 0.3s ease;
            }
            .apollo-user-overview__empty {
                text-align: center;
                padding: 60px 20px;
                color: #999;
            }
            .apollo-user-overview__empty i {
                font-size: 64px;
                margin-bottom: 20px;
                opacity: 0.3;
            }
            .apollo-user-overview__login-notice {
                text-align: center;
                padding: 40px;
                background: #f8f9fa;
                border-radius: 8px;
                border-left: 4px solid #2271b1;
            }
        ');
    }
    
    /**
     * User Overview Shortcode
     * Shows logged-in user's event statistics
     * 
     * Usage: [apollo_event_user_overview]
     * 
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string HTML output
     */
    public function user_overview_shortcode($atts, $content = '') {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return $this->render_login_notice();
        }
        
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        
        // Get analytics
        $analytics = new Apollo_Events_Analytics();
        $user_stats = $analytics->get_user_stats($user_id);
        $sound_distribution = $analytics->get_user_sound_distribution($user_id);
        $location_distribution = $analytics->get_user_location_distribution($user_id);
        
        ob_start();
        ?>
        <div class="apollo-user-overview">
            <div class="apollo-user-overview__header">
                <h2><?php echo esc_html($user->display_name); ?></h2>
                <p class="description">
                    <?php _e('Seu perfil de eventos Apollo', 'apollo-events-manager'); ?>
                </p>
            </div>
            
            <div class="apollo-user-overview__metrics">
                <div class="apollo-user-overview__metric">
                    <div class="apollo-user-overview__metric-value">
                        <?php echo number_format_i18n($user_stats['coauthored_count']); ?>
                    </div>
                    <div class="apollo-user-overview__metric-label">
                        <?php _e('Eventos como Co-autor', 'apollo-events-manager'); ?>
                    </div>
                </div>
                
                <div class="apollo-user-overview__metric">
                    <div class="apollo-user-overview__metric-value">
                        <?php echo number_format_i18n($user_stats['favorited_count']); ?>
                    </div>
                    <div class="apollo-user-overview__metric-label">
                        <?php _e('Eventos de Interesse', 'apollo-events-manager'); ?>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($sound_distribution)): ?>
                <div class="apollo-user-overview__section">
                    <h3><?php _e('Seus Sons Favoritos', 'apollo-events-manager'); ?></h3>
                    <ul class="apollo-user-overview__list">
                        <?php 
                        $total_sounds = array_sum($sound_distribution);
                        $count = 0;
                        foreach ($sound_distribution as $sound_slug => $sound_count): 
                            if ($count >= 5) break; // Show top 5 only
                            $term = get_term_by('slug', $sound_slug, 'event_sounds');
                            $percent = $total_sounds > 0 ? ($sound_count / $total_sounds) * 100 : 0;
                            $count++;
                        ?>
                            <li class="apollo-user-overview__list-item">
                                <span class="apollo-user-overview__list-name">
                                    <?php echo $term ? esc_html($term->name) : esc_html($sound_slug); ?>
                                </span>
                                <div class="apollo-user-overview__list-count">
                                    <span class="apollo-user-overview__list-number">
                                        <?php echo number_format_i18n($sound_count); ?>
                                    </span>
                                    <div class="apollo-user-overview__progress">
                                        <div class="apollo-user-overview__progress-fill" style="width: <?php echo esc_attr($percent); ?>%;"></div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($location_distribution)): ?>
                <div class="apollo-user-overview__section">
                    <h3><?php _e('Seus Locais Favoritos', 'apollo-events-manager'); ?></h3>
                    <ul class="apollo-user-overview__list">
                        <?php 
                        $total_locations = array_sum($location_distribution);
                        $count = 0;
                        foreach ($location_distribution as $local_id => $local_count): 
                            if ($count >= 5) break; // Show top 5 only
                            $local = get_post($local_id);
                            $percent = $total_locations > 0 ? ($local_count / $total_locations) * 100 : 0;
                            $count++;
                        ?>
                            <li class="apollo-user-overview__list-item">
                                <span class="apollo-user-overview__list-name">
                                    <?php echo $local ? esc_html($local->post_title) : __('Local desconhecido', 'apollo-events-manager'); ?>
                                </span>
                                <div class="apollo-user-overview__list-count">
                                    <span class="apollo-user-overview__list-number">
                                        <?php echo number_format_i18n($local_count); ?>
                                    </span>
                                    <div class="apollo-user-overview__progress">
                                        <div class="apollo-user-overview__progress-fill" style="width: <?php echo esc_attr($percent); ?>%;"></div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (empty($sound_distribution) && empty($location_distribution) && $user_stats['coauthored_count'] === 0 && $user_stats['favorited_count'] === 0): ?>
                <div class="apollo-user-overview__empty">
                    <i class="ri-calendar-line"></i>
                    <p><?php _e('Você ainda não interagiu com eventos. Comece explorando nossos eventos!', 'apollo-events-manager'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render login notice for non-authenticated users
     * 
     * @return string HTML output
     */
    private function render_login_notice() {
        ob_start();
        ?>
        <div class="apollo-user-overview">
            <div class="apollo-user-overview__login-notice">
                <h3><?php _e('Login Necessário', 'apollo-events-manager'); ?></h3>
                <p><?php _e('Disponível apenas para usuários autenticados.', 'apollo-events-manager'); ?></p>
                <p>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="button button-primary">
                        <?php _e('Fazer Login', 'apollo-events-manager'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize shortcodes
new Apollo_Events_Shortcodes();
