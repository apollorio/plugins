<?php
/**
 * Apollo Events Manager - Shortcodes
 * 
 * Organized shortcode system with analytics integration
 * Migrated from wp-event-manager with Apollo updates
 * 
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Apollo Events Shortcodes Class
 */
class Apollo_Events_Shortcodes {
    
    private $event_dashboard_message = '';
    private $dj_dashboard_message = '';
    private $local_dashboard_message = '';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp', array($this, 'shortcode_action_handler'));
        
        // Event shortcodes
        add_shortcode('submit_event_form', array($this, 'submit_event_form'));
        add_shortcode('event_dashboard', array($this, 'event_dashboard'));
        add_shortcode('events', array($this, 'output_events'));
        add_shortcode('event', array($this, 'output_event'));
        add_shortcode('event_summary', array($this, 'output_event_summary'));
        add_shortcode('past_events', array($this, 'output_past_events'));
        add_shortcode('upcoming_events', array($this, 'output_upcoming_events'));
        add_shortcode('related_events', array($this, 'output_related_events'));
        add_shortcode('event_register', array($this, 'output_event_register'));
        
        // DJ shortcodes
        add_shortcode('submit_dj_form', array($this, 'submit_dj_form'));
        add_shortcode('dj_dashboard', array($this, 'dj_dashboard'));
        add_shortcode('event_djs', array($this, 'output_event_djs'));
        add_shortcode('event_dj', array($this, 'output_event_dj'));
        add_shortcode('single_event_dj', array($this, 'output_single_event_dj'));
        
        // Local shortcodes
        add_shortcode('submit_local_form', array($this, 'submit_local_form'));
        add_shortcode('local_dashboard', array($this, 'local_dashboard'));
        add_shortcode('event_locals', array($this, 'output_event_locals'));
        add_shortcode('event_local', array($this, 'output_event_local'));
        add_shortcode('single_event_local', array($this, 'output_single_event_local'));
    }
    
    /**
     * Handle actions which need to be run before the shortcode
     */
    public function shortcode_action_handler() {
        global $post;
        
        if (!$post instanceof WP_Post) {
            return;
        }
        
        if (has_shortcode($post->post_content, 'event_dashboard')) {
            $this->event_dashboard_handler();
            $this->dj_dashboard_handler();
            $this->local_dashboard_handler();
        } elseif (has_shortcode($post->post_content, 'dj_dashboard')) {
            $this->dj_dashboard_handler();
        } elseif (has_shortcode($post->post_content, 'local_dashboard')) {
            $this->local_dashboard_handler();
        }
    }
    
    /**
     * Show the event submission form
     */
    public function submit_event_form($atts = array()) {
        if (!is_user_logged_in()) {
            return '<div class="apollo-alert apollo-alert-info">' . 
                   esc_html__('You must be logged in to submit an event.', 'apollo-events-manager') . 
                   ' <a href="' . esc_url(wp_login_url()) . '">' . 
                   esc_html__('Login', 'apollo-events-manager') . '</a></div>';
        }
        
        // TODO: Integrate with Apollo forms system
        return '<div class="apollo-submit-event-form">' . 
               esc_html__('Event submission form coming soon.', 'apollo-events-manager') . 
               '</div>';
    }
    
    /**
     * Show the DJ submission form
     */
    public function submit_dj_form($atts = array()) {
        if (!is_user_logged_in()) {
            return '<div class="apollo-alert apollo-alert-info">' . 
                   esc_html__('You must be logged in to submit a DJ profile.', 'apollo-events-manager') . 
                   ' <a href="' . esc_url(wp_login_url()) . '">' . 
                   esc_html__('Login', 'apollo-events-manager') . '</a></div>';
        }
        
        // TODO: Integrate with Apollo forms system
        return '<div class="apollo-submit-dj-form">' . 
               esc_html__('DJ submission form coming soon.', 'apollo-events-manager') . 
               '</div>';
    }
    
    /**
     * Show the local submission form
     */
    public function submit_local_form($atts = array()) {
        if (!is_user_logged_in()) {
            return '<div class="apollo-alert apollo-alert-info">' . 
                   esc_html__('You must be logged in to submit a venue.', 'apollo-events-manager') . 
                   ' <a href="' . esc_url(wp_login_url()) . '">' . 
                   esc_html__('Login', 'apollo-events-manager') . '</a></div>';
        }
        
        // TODO: Integrate with Apollo forms system
        return '<div class="apollo-submit-local-form">' . 
               esc_html__('Venue submission form coming soon.', 'apollo-events-manager') . 
               '</div>';
    }
    
    /**
     * Handles actions on event dashboard
     */
    public function event_dashboard_handler() {
        if (!empty($_REQUEST['action']) && !empty($_REQUEST['_wpnonce']) && 
            wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'apollo_event_dashboard_actions')) {
            
            $action = sanitize_title($_REQUEST['action']);
            $event_id = isset($_REQUEST['event_id']) ? absint($_REQUEST['event_id']) : 0;
            
            if (!$event_id) {
                $this->event_dashboard_message = '<div class="apollo-alert apollo-alert-danger">' . 
                    esc_html__('Invalid event ID.', 'apollo-events-manager') . '</div>';
                return;
            }
            
            try {
                $event = get_post($event_id);
                
                if (!$event || $event->post_type !== 'event_listing') {
                    throw new Exception(__('Invalid event.', 'apollo-events-manager'));
                }
                
                // Check ownership
                if ($event->post_author != get_current_user_id() && !current_user_can('edit_others_posts')) {
                    throw new Exception(__('You do not have permission to perform this action.', 'apollo-events-manager'));
                }
                
                switch ($action) {
                    case 'delete':
                        wp_trash_post($event_id);
                        $this->event_dashboard_message = '<div class="apollo-alert apollo-alert-success">' . 
                            sprintf(__('%s has been deleted.', 'apollo-events-manager'), esc_html($event->post_title)) . 
                            '</div>';
                        break;
                        
                    case 'duplicate':
                        // TODO: Implement duplicate functionality
                        $this->event_dashboard_message = '<div class="apollo-alert apollo-alert-info">' . 
                            esc_html__('Duplicate functionality coming soon.', 'apollo-events-manager') . 
                            '</div>';
                        break;
                        
                    default:
                        do_action('apollo_event_dashboard_do_action_' . $action, $event_id);
                        break;
                }
                
            } catch (Exception $e) {
                $this->event_dashboard_message = '<div class="apollo-alert apollo-alert-danger">' . 
                    esc_html($e->getMessage()) . '</div>';
            }
        }
    }
    
    /**
     * Shortcode which lists the logged in user's events
     */
    public function event_dashboard($atts) {
        if (!is_user_logged_in()) {
            ob_start();
            ?>
            <div class="apollo-event-dashboard">
                <p class="apollo-alert apollo-alert-info">
                    <?php esc_html_e('You need to be signed in to manage your events.', 'apollo-events-manager'); ?> 
                    <a href="<?php echo esc_url(wp_login_url()); ?>">
                        <?php esc_html_e('Sign in', 'apollo-events-manager'); ?>
                    </a>
                </p>
            </div>
            <?php
            return ob_get_clean();
        }
        
        $atts = shortcode_atts(array(
            'posts_per_page' => 10,
        ), $atts);
        
        ob_start();
        
        $args = array(
            'post_type' => 'event_listing',
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => absint($atts['posts_per_page']),
            'paged' => max(1, get_query_var('paged')),
            'orderby' => 'date',
            'order' => 'DESC',
            'author' => get_current_user_id()
        );
        
        $events = new WP_Query($args);
        
        echo wp_kses_post($this->event_dashboard_message);
        
        // Get analytics data
        $stats = array();
        if (function_exists('apollo_get_user_event_stats')) {
            $stats = apollo_get_user_event_stats(get_current_user_id());
        }
        
        ?>
        <div class="apollo-event-dashboard">
            <h2><?php esc_html_e('My Events', 'apollo-events-manager'); ?></h2>
            
            <?php if (!empty($stats)): ?>
            <div class="apollo-dashboard-stats">
                <div class="stat-item">
                    <i class="ri-calendar-event-line"></i>
                    <span class="stat-value"><?php echo esc_html($stats['total_events'] ?? 0); ?></span>
                    <span class="stat-label"><?php esc_html_e('Total Events', 'apollo-events-manager'); ?></span>
                </div>
                <div class="stat-item">
                    <i class="ri-eye-line"></i>
                    <span class="stat-value"><?php echo esc_html($stats['total_views'] ?? 0); ?></span>
                    <span class="stat-label"><?php esc_html_e('Total Views', 'apollo-events-manager'); ?></span>
                </div>
                <div class="stat-item">
                    <i class="ri-heart-line"></i>
                    <span class="stat-value"><?php echo esc_html($stats['total_favorites'] ?? 0); ?></span>
                    <span class="stat-label"><?php esc_html_e('Favorites', 'apollo-events-manager'); ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($events->have_posts()): ?>
            <table class="apollo-dashboard-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Title', 'apollo-events-manager'); ?></th>
                        <th><?php esc_html_e('Location', 'apollo-events-manager'); ?></th>
                        <th><?php esc_html_e('Start Date', 'apollo-events-manager'); ?></th>
                        <th><?php esc_html_e('Views', 'apollo-events-manager'); ?></th>
                        <th><?php esc_html_e('Status', 'apollo-events-manager'); ?></th>
                        <th><?php esc_html_e('Actions', 'apollo-events-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($events->have_posts()): $events->the_post(); 
                        $event_id = get_the_ID();
                        $views = function_exists('apollo_get_event_views') ? apollo_get_event_views($event_id) : 0;
                        $location = get_post_meta($event_id, '_event_location', true);
                        $start_date = get_post_meta($event_id, '_event_start_date', true);
                    ?>
                    <tr>
                        <td><strong><?php the_title(); ?></strong></td>
                        <td><?php echo esc_html($location ?: '-'); ?></td>
                        <td><?php echo esc_html($start_date ? date_i18n(get_option('date_format'), strtotime($start_date)) : '-'); ?></td>
                        <td><i class="ri-eye-line"></i> <?php echo esc_html($views); ?></td>
                        <td><span class="status-<?php echo esc_attr(get_post_status()); ?>"><?php echo esc_html(get_post_status()); ?></span></td>
                        <td>
                            <a href="<?php echo esc_url(get_edit_post_link($event_id)); ?>" class="apollo-btn apollo-btn-sm">
                                <i class="ri-edit-line"></i> <?php esc_html_e('Edit', 'apollo-events-manager'); ?>
                            </a>
                            <a href="<?php echo esc_url(get_permalink($event_id)); ?>" class="apollo-btn apollo-btn-sm">
                                <i class="ri-eye-line"></i> <?php esc_html_e('View', 'apollo-events-manager'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <?php
            // Pagination
            if ($events->max_num_pages > 1) {
                echo '<div class="apollo-pagination">';
                echo paginate_links(array(
                    'total' => $events->max_num_pages,
                    'current' => max(1, get_query_var('paged')),
                ));
                echo '</div>';
            }
            ?>
            
            <?php else: ?>
            <p class="apollo-alert apollo-alert-info">
                <?php esc_html_e('You have not created any events yet.', 'apollo-events-manager'); ?>
            </p>
            <?php endif; ?>
        </div>
        <?php
        
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    /**
     * Output of events
     */
    public function output_events($atts) {
        $atts = shortcode_atts(array(
            'per_page' => 10,
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_key' => '_event_start_date',
            'show_pagination' => false,
            'categories' => '',
            'event_types' => '',
            'featured' => null,
            'cancelled' => null,
        ), $atts);
        
        ob_start();
        
        $args = array(
            'post_type' => 'event_listing',
            'post_status' => 'publish',
            'posts_per_page' => absint($atts['per_page']),
            'paged' => max(1, get_query_var('paged')),
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
        );
        
        if ($atts['orderby'] === 'meta_value' && !empty($atts['meta_key'])) {
            $args['meta_key'] = $atts['meta_key'];
            $args['meta_type'] = 'DATETIME';
        }
        
        // Featured filter
        if (!is_null($atts['featured'])) {
            $args['meta_query'][] = array(
                'key' => '_featured',
                'value' => '1',
                'compare' => $atts['featured'] ? '=' : '!='
            );
        }
        
        // Cancelled filter
        if (!is_null($atts['cancelled'])) {
            $args['meta_query'][] = array(
                'key' => '_cancelled',
                'value' => '1',
                'compare' => $atts['cancelled'] ? '=' : '!='
            );
        }
        
        // Categories filter
        if (!empty($atts['categories'])) {
            $categories = array_map('trim', explode(',', $atts['categories']));
            $args['tax_query'][] = array(
                'taxonomy' => 'event_listing_category',
                'field' => 'slug',
                'terms' => $categories,
            );
        }
        
        $events = new WP_Query($args);
        
        if ($events->have_posts()):
            echo '<div class="apollo-events-list">';
            while ($events->have_posts()): $events->the_post();
                // Track view
                if (function_exists('apollo_record_event_view')) {
                    apollo_record_event_view(get_the_ID());
                }
                
                // Use event card template
                include APOLLO_WPEM_PATH . 'templates/event-card.php';
            endwhile;
            echo '</div>';
            
            // Pagination
            if ($atts['show_pagination'] && $events->max_num_pages > 1) {
                echo '<div class="apollo-pagination">';
                echo paginate_links(array(
                    'total' => $events->max_num_pages,
                    'current' => max(1, get_query_var('paged')),
                ));
                echo '</div>';
            }
        else:
            echo '<p class="apollo-alert apollo-alert-info">' . 
                 esc_html__('No events found.', 'apollo-events-manager') . 
                 '</p>';
        endif;
        
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    /**
     * Output single event
     */
    public function output_event($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);
        
        $event_id = absint($atts['id']);
        if (!$event_id) {
            return '<p class="apollo-alert apollo-alert-danger">' . 
                   esc_html__('Event ID is required.', 'apollo-events-manager') . 
                   '</p>';
        }
        
        $event = get_post($event_id);
        if (!$event || $event->post_type !== 'event_listing') {
            return '<p class="apollo-alert apollo-alert-danger">' . 
                   esc_html__('Event not found.', 'apollo-events-manager') . 
                   '</p>';
        }
        
        // Track view
        if (function_exists('apollo_record_event_view')) {
            apollo_record_event_view($event_id);
        }
        
        ob_start();
        global $post;
        $post = $event;
        setup_postdata($post);
        
        // Use single event template
        include APOLLO_WPEM_PATH . 'templates/single-event-standalone.php';
        
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    /**
     * Event Summary shortcode
     */
    public function output_event_summary($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'width' => '250px',
            'align' => 'left',
            'featured' => null,
            'limit' => -1,
        ), $atts);
        
        ob_start();
        
        $args = array(
            'post_type' => 'event_listing',
            'post_status' => 'publish',
        );
        
        if ($atts['id']) {
            $args['p'] = absint($atts['id']);
        } else {
            $args['posts_per_page'] = absint($atts['limit']);
            $args['orderby'] = 'rand';
        }
        
        if (!is_null($atts['featured'])) {
            $args['meta_query'][] = array(
                'key' => '_featured',
                'value' => '1',
                'compare' => $atts['featured'] ? '=' : '!='
            );
        }
        
        $events = new WP_Query($args);
        
        if ($events->have_posts()):
            while ($events->have_posts()): $events->the_post();
                echo '<div class="apollo-event-summary" style="width: ' . esc_attr($atts['width']) . '; text-align: ' . esc_attr($atts['align']) . ';">';
                the_title('<h3>', '</h3>');
                the_excerpt();
                echo '</div>';
            endwhile;
        endif;
        
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    /**
     * Past Events shortcode
     */
    public function output_past_events($atts) {
        $atts = shortcode_atts(array(
            'per_page' => 10,
            'order' => 'DESC',
            'orderby' => 'event_start_date',
        ), $atts);
        
        ob_start();
        
        $today = current_time('Y-m-d');
        
        $args = array(
            'post_type' => 'event_listing',
            'post_status' => array('publish', 'expired'),
            'posts_per_page' => absint($atts['per_page']),
            'paged' => max(1, get_query_var('paged')),
            'order' => $atts['order'],
            'meta_query' => array(
                array(
                    'key' => '_event_start_date',
                    'value' => $today,
                    'compare' => '<',
                    'type' => 'DATE'
                )
            )
        );
        
        if ($atts['orderby'] === 'event_start_date') {
            $args['orderby'] = 'meta_value';
            $args['meta_key'] = '_event_start_date';
            $args['meta_type'] = 'DATETIME';
        }
        
        $events = new WP_Query($args);
        
        if ($events->have_posts()):
            echo '<div class="apollo-past-events">';
            while ($events->have_posts()): $events->the_post();
                include APOLLO_WPEM_PATH . 'templates/event-card.php';
            endwhile;
            echo '</div>';
        else:
            echo '<p class="apollo-alert apollo-alert-info">' . 
                 esc_html__('No past events found.', 'apollo-events-manager') . 
                 '</p>';
        endif;
        
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    /**
     * Upcoming Events shortcode
     */
    public function output_upcoming_events($atts) {
        $atts = shortcode_atts(array(
            'per_page' => 10,
            'order' => 'ASC',
            'orderby' => 'event_start_date',
        ), $atts);
        
        ob_start();
        
        $today = current_time('Y-m-d');
        
        $args = array(
            'post_type' => 'event_listing',
            'post_status' => 'publish',
            'posts_per_page' => absint($atts['per_page']),
            'paged' => max(1, get_query_var('paged')),
            'order' => $atts['order'],
            'meta_query' => array(
                array(
                    'key' => '_event_start_date',
                    'value' => $today,
                    'compare' => '>=',
                    'type' => 'DATE'
                )
            )
        );
        
        if ($atts['orderby'] === 'event_start_date') {
            $args['orderby'] = 'meta_value';
            $args['meta_key'] = '_event_start_date';
            $args['meta_type'] = 'DATETIME';
        }
        
        $events = new WP_Query($args);
        
        if ($events->have_posts()):
            echo '<div class="apollo-upcoming-events">';
            while ($events->have_posts()): $events->the_post();
                // Track view
                if (function_exists('apollo_record_event_view')) {
                    apollo_record_event_view(get_the_ID());
                }
                
                include APOLLO_WPEM_PATH . 'templates/event-card.php';
            endwhile;
            echo '</div>';
        else:
            echo '<p class="apollo-alert apollo-alert-info">' . 
                 esc_html__('No upcoming events found.', 'apollo-events-manager') . 
                 '</p>';
        endif;
        
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    /**
     * Related Events shortcode
     */
    public function output_related_events($atts) {
        global $post;
        
        $atts = shortcode_atts(array(
            'id' => 0,
            'per_page' => 5,
        ), $atts);
        
        $event_id = $atts['id'] ? absint($atts['id']) : (is_singular('event_listing') ? get_the_ID() : 0);
        
        if (!$event_id) {
            return '';
        }
        
        ob_start();
        
        // Get event categories and sounds
        $categories = wp_get_post_terms($event_id, 'event_listing_category', array('fields' => 'ids'));
        $sounds = wp_get_post_terms($event_id, 'event_sounds', array('fields' => 'ids'));
        
        $args = array(
            'post_type' => 'event_listing',
            'post_status' => 'publish',
            'posts_per_page' => absint($atts['per_page']),
            'post__not_in' => array($event_id),
            'orderby' => 'rand',
        );
        
        if (!empty($categories) || !empty($sounds)) {
            $args['tax_query'] = array('relation' => 'OR');
            
            if (!empty($categories)) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'event_listing_category',
                    'field' => 'term_id',
                    'terms' => $categories,
                );
            }
            
            if (!empty($sounds)) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'event_sounds',
                    'field' => 'term_id',
                    'terms' => $sounds,
                );
            }
        }
        
        $events = new WP_Query($args);
        
        if ($events->have_posts()):
            echo '<div class="apollo-related-events">';
            echo '<h3>' . esc_html__('Related Events', 'apollo-events-manager') . '</h3>';
            while ($events->have_posts()): $events->the_post();
                include APOLLO_WPEM_PATH . 'templates/event-card.php';
            endwhile;
            echo '</div>';
        endif;
        
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    /**
     * Event Register shortcode
     */
    public function output_event_register($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);
        
        $event_id = $atts['id'] ? absint($atts['id']) : (is_singular('event_listing') ? get_the_ID() : 0);
        
        if (!$event_id) {
            return '<p class="apollo-alert apollo-alert-danger">' . 
                   esc_html__('Event ID is required.', 'apollo-events-manager') . 
                   '</p>';
        }
        
        $event = get_post($event_id);
        if (!$event || $event->post_type !== 'event_listing') {
            return '<p class="apollo-alert apollo-alert-danger">' . 
                   esc_html__('Event not found.', 'apollo-events-manager') . 
                   '</p>';
        }
        
        ob_start();
        ?>
        <div class="apollo-event-register">
            <h3><?php esc_html_e('Register for this Event', 'apollo-events-manager'); ?></h3>
            <p><?php esc_html_e('Registration form coming soon.', 'apollo-events-manager'); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // DJ Dashboard methods (similar structure to event_dashboard)
    public function dj_dashboard_handler() {
        // Similar to event_dashboard_handler
    }
    
    public function dj_dashboard($atts) {
        // Similar to event_dashboard but for DJs
        if (!is_user_logged_in()) {
            return '<div class="apollo-alert apollo-alert-info">' . 
                   esc_html__('You need to be signed in to manage your DJ profiles.', 'apollo-events-manager') . 
                   '</div>';
        }
        
        // TODO: Implement DJ dashboard
        return '<div class="apollo-dj-dashboard">DJ Dashboard coming soon.</div>';
    }
    
    public function output_event_djs($atts) {
        // Already implemented in main plugin file
        return '';
    }
    
    public function output_event_dj($atts) {
        // TODO: Implement single DJ output
        return '';
    }
    
    public function output_single_event_dj($atts) {
        // Already implemented in main plugin file
        return '';
    }
    
    // Local Dashboard methods
    public function local_dashboard_handler() {
        // Similar to event_dashboard_handler
    }
    
    public function local_dashboard($atts) {
        // Similar to event_dashboard but for Locals
        if (!is_user_logged_in()) {
            return '<div class="apollo-alert apollo-alert-info">' . 
                   esc_html__('You need to be signed in to manage your venues.', 'apollo-events-manager') . 
                   '</div>';
        }
        
        // TODO: Implement Local dashboard
        return '<div class="apollo-local-dashboard">Venue Dashboard coming soon.</div>';
    }
    
    public function output_event_locals($atts) {
        // Already implemented in main plugin file
        return '';
    }
    
    public function output_event_local($atts) {
        // TODO: Implement single Local output
        return '';
    }
    
    public function output_single_event_local($atts) {
        // Already implemented in main plugin file
        return '';
    }
}

// Initialize
new Apollo_Events_Shortcodes();

