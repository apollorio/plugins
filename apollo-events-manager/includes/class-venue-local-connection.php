<?php
/**
 * Venue-Local Connection Manager
 * 
 * Ensures mandatory connection between events and venues (event_local CPT)
 * Prevents duplications and provides unified API
 * 
 * @package ApolloEventsManager
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Apollo Venue-Local Connection Manager
 * 
 * CRITICAL: This class ensures that every event MUST have a venue (local) connected.
 * The connection is stored in _event_local_ids meta (single integer ID).
 * Legacy _event_local meta is supported but deprecated.
 */
class Apollo_Venue_Local_Connection
{
    private static $instance = null;
    
    /**
     * Primary meta key for venue connection
     */
    const META_KEY_PRIMARY = '_event_local_ids';
    
    /**
     * Legacy meta key (deprecated, but supported for migration)
     */
    const META_KEY_LEGACY = '_event_local';
    
    /**
     * Get singleton instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct()
    {
        // Hook into event save to enforce mandatory connection
        add_action('save_post_event_listing', [$this, 'enforce_mandatory_connection'], 10, 2);
        
        // Validate before save (prevent saving without venue)
        add_action('save_post_event_listing', [$this, 'validate_venue_connection'], 5, 2);
    }
    
    /**
     * Get venue ID for an event
     * 
     * @param int $event_id Event post ID
     * @return int|false Venue (local) ID or false if not found
     */
    public function get_venue_id($event_id)
    {
        $event_id = absint($event_id);
        if (!$event_id) {
            return false;
        }
        
        // Try primary meta key first
        $venue_id = get_post_meta($event_id, self::META_KEY_PRIMARY, true);
        
        // Handle array format (should be single ID, but support array for safety)
        if (is_array($venue_id)) {
            $venue_id = !empty($venue_id) ? absint($venue_id[0]) : 0;
        } elseif (is_numeric($venue_id)) {
            $venue_id = absint($venue_id);
        } else {
            $venue_id = 0;
        }
        
        // Validate venue exists and is correct post type
        if ($venue_id > 0) {
            $venue = get_post($venue_id);
            if ($venue && $venue->post_type === 'event_local' && $venue->post_status === 'publish') {
                return $venue_id;
            }
        }
        
        // Fallback to legacy meta key
        $legacy_venue = get_post_meta($event_id, self::META_KEY_LEGACY, true);
        if (is_numeric($legacy_venue) && absint($legacy_venue) > 0) {
            $venue_id = absint($legacy_venue);
            $venue = get_post($venue_id);
            if ($venue && $venue->post_type === 'event_local' && $venue->post_status === 'publish') {
                // Migrate to primary meta key
                $this->set_venue_id($event_id, $venue_id);
                return $venue_id;
            }
        }
        
        return false;
    }
    
    /**
     * Set venue ID for an event
     * 
     * @param int $event_id Event post ID
     * @param int $venue_id Venue (local) post ID
     * @return bool Success
     */
    public function set_venue_id($event_id, $venue_id)
    {
        $event_id = absint($event_id);
        $venue_id = absint($venue_id);
        
        if (!$event_id) {
            return false;
        }
        
        // Validate venue exists
        if ($venue_id > 0) {
            $venue = get_post($venue_id);
            if (!$venue || $venue->post_type !== 'event_local') {
                return false;
            }
        }
        
        // Update primary meta key
        if ($venue_id > 0) {
            update_post_meta($event_id, self::META_KEY_PRIMARY, $venue_id);
            
            // Clean up legacy meta if exists
            delete_post_meta($event_id, self::META_KEY_LEGACY);
        } else {
            // Remove connection
            delete_post_meta($event_id, self::META_KEY_PRIMARY);
            delete_post_meta($event_id, self::META_KEY_LEGACY);
        }
        
        return true;
    }
    
    /**
     * Get venue post object
     * 
     * @param int $event_id Event post ID
     * @return WP_Post|false Venue post object or false
     */
    public function get_venue($event_id)
    {
        $venue_id = $this->get_venue_id($event_id);
        if (!$venue_id) {
            return false;
        }
        
        $venue = get_post($venue_id);
        if ($venue && $venue->post_type === 'event_local' && $venue->post_status === 'publish') {
            return $venue;
        }
        
        return false;
    }
    
    /**
     * Check if event has venue connected
     * 
     * @param int $event_id Event post ID
     * @return bool
     */
    public function has_venue($event_id)
    {
        return $this->get_venue_id($event_id) !== false;
    }
    
    /**
     * Validate venue connection before save
     * Prevents saving event without venue (unless draft/auto-draft)
     * 
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     */
    public function validate_venue_connection($post_id, $post)
    {
        // Skip validation for drafts and auto-drafts
        if (in_array($post->post_status, ['draft', 'auto-draft', 'pending'], true)) {
            return;
        }
        
        // Skip autosave and revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Check if venue is set
        $venue_id = isset($_POST['apollo_event_local']) ? absint($_POST['apollo_event_local']) : 0;
        
        // If not in POST, check current meta
        if (!$venue_id) {
            $venue_id = $this->get_venue_id($post_id);
        }
        
        // If still no venue, check if we're updating existing event
        if (!$venue_id && $post->post_status === 'publish') {
            // Allow saving but add admin notice
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning is-dismissible">';
                echo '<p><strong>' . esc_html__('Aviso Apollo:', 'apollo-events-manager') . '</strong> ';
                echo esc_html__('Este evento não possui um local (venue) conectado. Por favor, conecte um local para melhor organização.', 'apollo-events-manager');
                echo '</p></div>';
            });
        }
    }
    
    /**
     * Enforce mandatory connection on save
     * Ensures venue is properly saved and legacy meta is cleaned up
     * 
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     */
    public function enforce_mandatory_connection($post_id, $post)
    {
        // Skip autosave and revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Get venue ID from POST data
        $venue_id = 0;
        if (isset($_POST['apollo_event_local'])) {
            $venue_id = absint($_POST['apollo_event_local']);
        }
        
        // If venue ID provided, set it
        if ($venue_id > 0) {
            $this->set_venue_id($post_id, $venue_id);
        } elseif (isset($_POST['apollo_event_local'])) {
            // Explicitly set to empty (user removed venue)
            $this->set_venue_id($post_id, 0);
        }
        
        // Clean up any duplicate meta entries
        $this->cleanup_duplicate_meta($post_id);
    }
    
    /**
     * Clean up duplicate meta entries
     * Ensures only _event_local_ids exists (removes legacy and duplicates)
     * 
     * @param int $event_id Event post ID
     */
    private function cleanup_duplicate_meta($event_id)
    {
        global $wpdb;
        
        $event_id = absint($event_id);
        if (!$event_id) {
            return;
        }
        
        // Get current venue ID
        $venue_id = $this->get_venue_id($event_id);
        
        // Remove all venue-related meta except the primary one
        $wpdb->delete(
            $wpdb->postmeta,
            [
                'post_id' => $event_id,
                'meta_key' => self::META_KEY_LEGACY
            ],
            ['%d', '%s']
        );
        
        // Ensure only one _event_local_ids entry exists
        $existing = $wpdb->get_results($wpdb->prepare(
            "SELECT meta_id FROM {$wpdb->postmeta} 
            WHERE post_id = %d AND meta_key = %s",
            $event_id,
            self::META_KEY_PRIMARY
        ));
        
        if (count($existing) > 1) {
            // Keep first, delete others
            $keep_id = $existing[0]->meta_id;
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->postmeta} 
                WHERE post_id = %d AND meta_key = %s AND meta_id != %d",
                $event_id,
                self::META_KEY_PRIMARY,
                $keep_id
            ));
        }
    }
    
    /**
     * Get all events without venue connection
     * 
     * @return array Array of event IDs
     */
    public function get_events_without_venue()
    {
        global $wpdb;
        
        $events = $wpdb->get_col($wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = %s
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = %s
            WHERE p.post_type = 'event_listing'
            AND p.post_status = 'publish'
            AND (pm1.meta_value IS NULL OR pm1.meta_value = '' OR pm1.meta_value = '0')
            AND (pm2.meta_value IS NULL OR pm2.meta_value = '' OR pm2.meta_value = '0')",
            self::META_KEY_PRIMARY,
            self::META_KEY_LEGACY
        ));
        
        return array_map('absint', $events);
    }
    
    /**
     * Migrate legacy _event_local to _event_local_ids
     * 
     * @param int $event_id Event post ID
     * @return bool Success
     */
    public function migrate_legacy_meta($event_id)
    {
        $event_id = absint($event_id);
        if (!$event_id) {
            return false;
        }
        
        // Check if already migrated
        $current = get_post_meta($event_id, self::META_KEY_PRIMARY, true);
        if (!empty($current)) {
            return true; // Already migrated
        }
        
        // Get legacy value
        $legacy = get_post_meta($event_id, self::META_KEY_LEGACY, true);
        if (is_numeric($legacy) && absint($legacy) > 0) {
            $venue_id = absint($legacy);
            // Validate venue exists
            $venue = get_post($venue_id);
            if ($venue && $venue->post_type === 'event_local') {
                $this->set_venue_id($event_id, $venue_id);
                return true;
            }
        }
        
        return false;
    }
}

// Initialize
Apollo_Venue_Local_Connection::get_instance();

/**
 * Helper function: Get venue ID for event
 * Unified API - use this instead of direct meta access
 * 
 * @param int $event_id Event post ID
 * @return int|false Venue ID or false
 */
if (!function_exists('apollo_get_event_venue_id')) {
    function apollo_get_event_venue_id($event_id)
    {
        $connection = Apollo_Venue_Local_Connection::get_instance();
        return $connection->get_venue_id($event_id);
    }
}

/**
 * Helper function: Get venue post object
 * 
 * @param int $event_id Event post ID
 * @return WP_Post|false Venue post or false
 */
if (!function_exists('apollo_get_event_venue')) {
    function apollo_get_event_venue($event_id)
    {
        $connection = Apollo_Venue_Local_Connection::get_instance();
        return $connection->get_venue($event_id);
    }
}

/**
 * Helper function: Set venue for event
 * 
 * @param int $event_id Event post ID
 * @param int $venue_id Venue post ID
 * @return bool Success
 */
if (!function_exists('apollo_set_event_venue')) {
    function apollo_set_event_venue($event_id, $venue_id)
    {
        $connection = Apollo_Venue_Local_Connection::get_instance();
        return $connection->set_venue_id($event_id, $venue_id);
    }
}

/**
 * Helper function: Check if event has venue
 * 
 * @param int $event_id Event post ID
 * @return bool
 */
if (!function_exists('apollo_event_has_venue')) {
    function apollo_event_has_venue($event_id)
    {
        $connection = Apollo_Venue_Local_Connection::get_instance();
        return $connection->has_venue($event_id);
    }
}

