<?php
/**
 * Apollo Events Plausible Analytics Integration
 * 
 * CLIENT-SIDE ONLY - No server-side API calls.
 * Simply injects the Plausible script and provides JS helpers for custom events.
 * 
 * @package Apollo_Events_Manager
 * @since 2.1.0
 */

defined('ABSPATH') || exit;

/**
 * Apollo Events Plausible Class
 * 
 * Handles:
 * - Script injection on event pages
 * - Custom event tracking helpers (JS-side)
 */
class Apollo_Events_Plausible {
    
    /**
     * Plausible domain
     * @var string
     */
    private $domain;
    
    /**
     * Plausible script URL
     * @var string
     */
    private $script_url;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Get configuration from constants or options
        $this->domain = defined('APOLLO_PLAUSIBLE_DOMAIN') 
            ? APOLLO_PLAUSIBLE_DOMAIN 
            : get_option('apollo_plausible_domain', '');
        
        $this->script_url = defined('APOLLO_PLAUSIBLE_SCRIPT_URL') 
            ? APOLLO_PLAUSIBLE_SCRIPT_URL 
            : get_option('apollo_plausible_script_url', 'https://plausible.io/js/script.js');
        
        // Only initialize if domain is configured
        if (!empty($this->domain)) {
            add_action('wp_head', array($this, 'inject_plausible_script'), 5);
            add_action('wp_enqueue_scripts', array($this, 'enqueue_tracking_helpers'));
        }
    }
    
    /**
     * Check if we should load Plausible on current page
     * 
     * @return bool
     */
    private function should_load_plausible() {
        // Load on eventos page
        if (is_page('eventos')) {
            return true;
        }
        
        // Load on event single pages
        if (is_singular('event_listing')) {
            return true;
        }
        
        // Load on event archives
        if (is_post_type_archive('event_listing')) {
            return true;
        }
        
        // Load on pages with apollo shortcodes
        global $post;
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'apollo_events') ||
            has_shortcode($post->post_content, 'eventos-page')
        )) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Inject Plausible script in head
     */
    public function inject_plausible_script() {
        if (!$this->should_load_plausible()) {
            return;
        }
        
        ?>
        <!-- Apollo Events - Plausible Analytics -->
        <script 
            defer 
            data-domain="<?php echo esc_attr($this->domain); ?>" 
            src="<?php echo esc_url($this->script_url); ?>"
        ></script>
        <?php
    }
    
    /**
     * Enqueue tracking helper JS
     */
    public function enqueue_tracking_helpers() {
        if (!$this->should_load_plausible()) {
            return;
        }
        
        // Add inline JS helper for tracking
        // This helper will be used by other scripts (base.js, event-page.js)
        wp_add_inline_script('jquery', $this->get_tracking_helper_js(), 'after');
    }
    
    /**
     * Get tracking helper JavaScript code
     * 
     * @return string JS code
     */
    private function get_tracking_helper_js() {
        return "
/**
 * Apollo Plausible Tracking Helper
 * Safe wrapper for plausible() function
 * 
 * @param {string} eventName Event name
 * @param {object} props Event properties (optional)
 */
window.apolloTrackPlausible = function(eventName, props) {
    // Check if Plausible is loaded
    if (typeof window.plausible !== 'function') {
        console.debug('Plausible not loaded, skipping event:', eventName);
        return;
    }
    
    // Send event
    try {
        if (props && typeof props === 'object') {
            window.plausible(eventName, { props: props });
        } else {
            window.plausible(eventName);
        }
        console.debug('Plausible event tracked:', eventName, props);
    } catch (error) {
        console.error('Error tracking Plausible event:', error);
    }
};

// Track page view (automatic by Plausible, but we can add custom logic here if needed)
jQuery(document).ready(function($) {
    console.debug('Apollo Plausible tracking initialized');
});
";
    }
}

// Initialize Plausible integration
new Apollo_Events_Plausible();

/**
 * Helper function to check if Plausible is enabled
 * 
 * @return bool
 */
function apollo_events_is_plausible_enabled() {
    $domain = defined('APOLLO_PLAUSIBLE_DOMAIN') 
        ? APOLLO_PLAUSIBLE_DOMAIN 
        : get_option('apollo_plausible_domain', '');
    
    return !empty($domain);
}
