<?php
/**
 * Apollo Events Analytics - Core Analytics Engine
 * 
 * Handles event views, user interactions, and analytics data persistence.
 * Does not depend on external analytics services (Plausible is separate, client-side only).
 * 
 * @package Apollo_Events_Manager
 * @since 2.1.0
 */

defined('ABSPATH') || exit;

/**
 * Apollo Events Analytics Class
 * 
 * Manages:
 * - Event views tracking
 * - User interactions (co-author, interest/favorites)
 * - Internal analytics data persistence
 * - Stats calculations for dashboards
 */
class Apollo_Events_Analytics {
    
    /**
     * Table name for event stats
     * @var string
     */
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'apollo_event_stats';
        
        // Hooks
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_apollo_record_view', array($this, 'ajax_record_view'));
        add_action('wp_ajax_nopriv_apollo_record_view', array($this, 'ajax_record_view'));
    }
    
    /**
     * Initialize analytics
     */
    public function init() {
        // Create table if needed (idempotent)
        $this->maybe_create_table();
    }
    
    /**
     * Create analytics table if it doesn't exist
     * Idempotent operation - safe to call multiple times
     */
    public function maybe_create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT 0,
            event_id bigint(20) unsigned NOT NULL,
            views int(11) unsigned DEFAULT 0,
            favorited tinyint(1) unsigned DEFAULT 0,
            is_coauthor tinyint(1) unsigned DEFAULT 0,
            last_interaction datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_event (user_id, event_id),
            KEY event_id (event_id),
            KEY user_id (user_id),
            KEY last_interaction (last_interaction)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Record event view
     * 
     * @param int $event_id Event post ID
     * @param int|null $user_id User ID (null = anonymous)
     * @return bool Success
     */
    public function record_view($event_id, $user_id = null) {
        global $wpdb;
        
        if (!$event_id || !get_post($event_id)) {
            return false;
        }
        
        // Update global view count
        $current_total = (int) get_post_meta($event_id, '_apollo_event_views_total', true);
        update_post_meta($event_id, '_apollo_event_views_total', $current_total + 1);
        
        // If user is logged in, track per-user
        if ($user_id) {
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE user_id = %d AND event_id = %d",
                $user_id,
                $event_id
            ));
            
            if ($existing) {
                // Update existing record
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$this->table_name} 
                    SET views = views + 1, last_interaction = NOW() 
                    WHERE user_id = %d AND event_id = %d",
                    $user_id,
                    $event_id
                ));
            } else {
                // Insert new record
                $wpdb->insert(
                    $this->table_name,
                    array(
                        'user_id' => $user_id,
                        'event_id' => $event_id,
                        'views' => 1,
                        'last_interaction' => current_time('mysql')
                    ),
                    array('%d', '%d', '%d', '%s')
                );
            }
        }
        
        // Fire action hook for extensibility
        do_action('apollo_event_view_recorded', $event_id, $user_id);
        
        return true;
    }
    
    /**
     * AJAX handler for recording views
     */
    public function ajax_record_view() {
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $user_id = is_user_logged_in() ? get_current_user_id() : null;
        
        if ($this->record_view($event_id, $user_id)) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Invalid event');
        }
    }
    
    /**
     * Mark event as favorited by user
     * 
     * @param int $event_id Event post ID
     * @param int $user_id User ID
     * @param bool $favorited True = add favorite, False = remove favorite
     * @return bool Success
     */
    public function set_favorite($event_id, $user_id, $favorited = true) {
        global $wpdb;
        
        if (!$event_id || !$user_id) {
            return false;
        }
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d AND event_id = %d",
            $user_id,
            $event_id
        ));
        
        if ($existing) {
            // Update existing
            $wpdb->update(
                $this->table_name,
                array('favorited' => $favorited ? 1 : 0, 'last_interaction' => current_time('mysql')),
                array('user_id' => $user_id, 'event_id' => $event_id),
                array('%d', '%s'),
                array('%d', '%d')
            );
        } else {
            // Insert new
            $wpdb->insert(
                $this->table_name,
                array(
                    'user_id' => $user_id,
                    'event_id' => $event_id,
                    'favorited' => $favorited ? 1 : 0,
                    'last_interaction' => current_time('mysql')
                ),
                array('%d', '%d', '%d', '%s')
            );
        }
        
        do_action('apollo_event_favorite_updated', $event_id, $user_id, $favorited);
        
        return true;
    }
    
    /**
     * Mark user as co-author of event
     * 
     * @param int $event_id Event post ID
     * @param int $user_id User ID
     * @param bool $is_coauthor True = add co-author, False = remove
     * @return bool Success
     */
    public function set_coauthor($event_id, $user_id, $is_coauthor = true) {
        global $wpdb;
        
        if (!$event_id || !$user_id) {
            return false;
        }
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d AND event_id = %d",
            $user_id,
            $event_id
        ));
        
        if ($existing) {
            $wpdb->update(
                $this->table_name,
                array('is_coauthor' => $is_coauthor ? 1 : 0, 'last_interaction' => current_time('mysql')),
                array('user_id' => $user_id, 'event_id' => $event_id),
                array('%d', '%s'),
                array('%d', '%d')
            );
        } else {
            $wpdb->insert(
                $this->table_name,
                array(
                    'user_id' => $user_id,
                    'event_id' => $event_id,
                    'is_coauthor' => $is_coauthor ? 1 : 0,
                    'last_interaction' => current_time('mysql')
                ),
                array('%d', '%d', '%d', '%s')
            );
        }
        
        // Also update post meta for easy querying
        $coauthors = get_post_meta($event_id, '_apollo_coauthors', true);
        $coauthors = $coauthors ? maybe_unserialize($coauthors) : array();
        
        if ($is_coauthor && !in_array($user_id, $coauthors)) {
            $coauthors[] = $user_id;
            update_post_meta($event_id, '_apollo_coauthors', $coauthors);
        } elseif (!$is_coauthor && in_array($user_id, $coauthors)) {
            $coauthors = array_diff($coauthors, array($user_id));
            update_post_meta($event_id, '_apollo_coauthors', $coauthors);
        }
        
        do_action('apollo_event_coauthor_updated', $event_id, $user_id, $is_coauthor);
        
        return true;
    }
    
    /**
     * Get user event statistics
     * 
     * @param int $user_id User ID
     * @return array Stats array
     */
    public function get_user_stats($user_id) {
        global $wpdb;
        
        if (!$user_id) {
            return array(
                'coauthored_count' => 0,
                'favorited_count' => 0,
                'total_views' => 0,
                'coauthored_events' => array(),
                'favorited_events' => array(),
            );
        }
        
        // Get counts
        $coauthored_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d AND is_coauthor = 1",
            $user_id
        ));
        
        $favorited_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d AND favorited = 1",
            $user_id
        ));
        
        $total_views = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(views) FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        ));
        
        // Get event IDs
        $coauthored_events = $wpdb->get_col($wpdb->prepare(
            "SELECT event_id FROM {$this->table_name} WHERE user_id = %d AND is_coauthor = 1",
            $user_id
        ));
        
        $favorited_events = $wpdb->get_col($wpdb->prepare(
            "SELECT event_id FROM {$this->table_name} WHERE user_id = %d AND favorited = 1",
            $user_id
        ));
        
        return array(
            'coauthored_count' => (int) $coauthored_count,
            'favorited_count' => (int) $favorited_count,
            'total_views' => (int) $total_views,
            'coauthored_events' => array_map('intval', $coauthored_events),
            'favorited_events' => array_map('intval', $favorited_events),
        );
    }
    
    /**
     * Get sound distribution for user
     * Based on events user interacted with (co-author + favorited)
     * 
     * @param int $user_id User ID
     * @return array Array of sound_slug => count
     */
    public function get_user_sound_distribution($user_id) {
        $stats = $this->get_user_stats($user_id);
        $event_ids = array_unique(array_merge(
            $stats['coauthored_events'],
            $stats['favorited_events']
        ));
        
        if (empty($event_ids)) {
            return array();
        }
        
        $distribution = array();
        
        foreach ($event_ids as $event_id) {
            $sounds = wp_get_post_terms($event_id, 'event_sounds', array('fields' => 'slugs'));
            foreach ($sounds as $sound_slug) {
                if (!isset($distribution[$sound_slug])) {
                    $distribution[$sound_slug] = 0;
                }
                $distribution[$sound_slug]++;
            }
        }
        
        arsort($distribution);
        return $distribution;
    }
    
    /**
     * Get location distribution for user
     * Based on events user interacted with
     * 
     * @param int $user_id User ID
     * @return array Array of local_id => count
     */
    public function get_user_location_distribution($user_id) {
        $stats = $this->get_user_stats($user_id);
        $event_ids = array_unique(array_merge(
            $stats['coauthored_events'],
            $stats['favorited_events']
        ));
        
        if (empty($event_ids)) {
            return array();
        }
        
        $distribution = array();
        
        foreach ($event_ids as $event_id) {
            $local_id = get_post_meta($event_id, '_event_local_ids', true);
            if ($local_id) {
                if (!isset($distribution[$local_id])) {
                    $distribution[$local_id] = 0;
                }
                $distribution[$local_id]++;
            }
        }
        
        arsort($distribution);
        return $distribution;
    }
}

/**
 * Global helper function to record event view
 * 
 * @param int $event_id Event post ID
 * @param int|null $user_id User ID (null = use current user)
 * @return bool Success
 */
function apollo_record_event_view($event_id, $user_id = null) {
    if ($user_id === null && is_user_logged_in()) {
        $user_id = get_current_user_id();
    }
    
    $analytics = new Apollo_Events_Analytics();
    return $analytics->record_view($event_id, $user_id);
}

/**
 * Get global event statistics
 * 
 * @return array Stats array
 */
function apollo_events_analytics_get_global_stats() {
    $today = date('Y-m-d');
    
    // Total events
    $total_events = wp_count_posts('event_listing');
    $published_events = isset($total_events->publish) ? $total_events->publish : 0;
    
    // Future events
    $future_events = get_posts(array(
        'post_type' => 'event_listing',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => '_event_start_date',
                'value' => $today,
                'compare' => '>=',
                'type' => 'DATE'
            )
        )
    ));
    $future_count = count($future_events);
    
    // Total views (sum of all _apollo_event_views_total)
    global $wpdb;
    $total_views = $wpdb->get_var(
        "SELECT SUM(meta_value) 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = '_apollo_event_views_total'"
    );
    
    return array(
        'total_events' => (int) $published_events,
        'future_events' => (int) $future_count,
        'past_events' => (int) ($published_events - $future_count),
        'total_views' => (int) $total_views,
    );
}

/**
 * Get top events by views
 * 
 * @param int $limit Number of results
 * @return array Array of event data
 */
function apollo_events_analytics_get_top_events($limit = 10) {
    global $wpdb;
    
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT p.ID, p.post_title, pm.meta_value as views
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'event_listing'
        AND p.post_status = 'publish'
        AND pm.meta_key = '_apollo_event_views_total'
        ORDER BY CAST(pm.meta_value AS UNSIGNED) DESC
        LIMIT %d",
        $limit
    ));
    
    return $results;
}

/**
 * Get top sounds by event count
 * 
 * @param int $limit Number of results
 * @return array Array of sound data
 */
function apollo_events_analytics_get_top_sounds($limit = 10) {
    global $wpdb;
    
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT t.term_id, t.name, t.slug, COUNT(tr.object_id) as event_count
        FROM {$wpdb->terms} t
        INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
        INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
        INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
        WHERE tt.taxonomy = 'event_sounds'
        AND p.post_type = 'event_listing'
        AND p.post_status = 'publish'
        GROUP BY t.term_id
        ORDER BY event_count DESC
        LIMIT %d",
        $limit
    ));
    
    return $results;
}

/**
 * Get top locals by event count
 * 
 * @param int $limit Number of results
 * @return array Array of local data
 */
function apollo_events_analytics_get_top_locals($limit = 10) {
    global $wpdb;
    
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT p2.ID, p2.post_title, COUNT(p.ID) as event_count
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        INNER JOIN {$wpdb->posts} p2 ON pm.meta_value = p2.ID
        WHERE p.post_type = 'event_listing'
        AND p.post_status = 'publish'
        AND pm.meta_key = '_event_local_ids'
        AND p2.post_type = 'event_local'
        AND p2.post_status = 'publish'
        GROUP BY p2.ID
        ORDER BY event_count DESC
        LIMIT %d",
        $limit
    ));
    
    return $results;
}
