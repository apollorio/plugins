<?php
/**
 * AJAX Handlers for Apollo Events Manager
 * Handles modal loading and other AJAX requests
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX handlers
 * NOTE: Main handler is in apollo-events-manager.php (ajax_get_event_modal)
 * This handler is kept for backward compatibility but redirects to main handler
 */
add_action('wp_ajax_apollo_load_event_modal', 'apollo_ajax_load_event_modal');
add_action('wp_ajax_nopriv_apollo_load_event_modal', 'apollo_ajax_load_event_modal');

/**
 * AJAX Handler: Load event modal content (Legacy - redirects to main handler)
 * Returns complete HTML for the lightbox modal
 * 
 * @deprecated Use apollo_get_event_modal action instead
 */
function apollo_ajax_load_event_modal() {
    try {
        // Verify nonce (standardized)
        check_ajax_referer('apollo_events_nonce', 'nonce');

        // Validate event ID
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        if (!$event_id) {
            wp_send_json_error(array('message' => 'ID inválido'));
            return;
        }

        // Verify event exists
        $event = get_post($event_id);
        if (!$event || $event->post_type !== 'event_listing') {
            wp_send_json_error(array('message' => 'Evento não encontrado'));
            return;
        }

    // Use canonical meta keys: _event_dj_ids, _event_local_ids, _event_timetable
    $start_date = get_post_meta($event_id, '_event_start_date', true);
    $banner = get_post_meta($event_id, '_event_banner', true);
    
    // Get local using canonical key or connection manager
    $local_id = 0;
    if (class_exists('Apollo_Local_Connection')) {
        $connection = Apollo_Local_Connection::get_instance();
        $local_id = $connection->get_local_id($event_id);
    } else {
        $local_id = get_post_meta($event_id, '_event_local_ids', true);
        if (is_array($local_id)) {
            $local_id = !empty($local_id) ? absint($local_id[0]) : 0;
        } else {
            $local_id = absint($local_id);
        }
    }
    
    $location = '';
    if ($local_id > 0) {
        $local_post = get_post($local_id);
        if ($local_post) {
            $local_name = get_post_meta($local_id, '_local_name', true);
            $location = $local_name !== '' ? $local_name : $local_post->post_title;
        }
    }
    
    // Fallback to _event_location if no local found
    if (empty($location)) {
        $location = get_post_meta($event_id, '_event_location', true);
    }

    // Get timetable using canonical key
    $event_timetable = get_post_meta($event_id, '_event_timetable', true);
    $timetable = apollo_sanitize_timetable($event_timetable);

    // Migrate legacy _timetable if canonical is empty
    if (empty($timetable)) {
        $legacy_timetable = get_post_meta($event_id, '_timetable', true);
        $timetable = apollo_sanitize_timetable($legacy_timetable);

        if (!empty($timetable)) {
            update_post_meta($event_id, '_event_timetable', $timetable);
        }
    }
    
    // Process date
    $date_info = apollo_eve_parse_start_date($start_date);
    
    // Process DJs using canonical key _event_dj_ids
    $djs_names = array();
    
    // Priority 1: _event_timetable (has times)
    if (!empty($timetable)) {
        foreach ($timetable as $slot) {
            $dj_id = isset($slot['dj']) ? intval($slot['dj']) : 0;
            if (!$dj_id) {
                continue;
            }

            $dj_post = get_post($dj_id);
            if ($dj_post && $dj_post->post_status === 'publish') {
                $dj_name = get_post_meta($dj_id, '_dj_name', true);
                if ($dj_name === '') {
                    $dj_name = $dj_post->post_title;
                }

                if ($dj_name !== '') {
                    $djs_names[] = trim($dj_name);
                }
            }
        }
    }

    // Priority 2: _event_dj_ids (canonical key)
    if (empty($djs_names)) {
        $related_djs = get_post_meta($event_id, '_event_dj_ids', true);
        $related_djs = maybe_unserialize($related_djs);

        if (is_array($related_djs)) {
            foreach ($related_djs as $dj_id) {
                $dj_id = intval($dj_id);
                if (!$dj_id) {
                    continue;
                }

                $dj_post = get_post($dj_id);
                if ($dj_post && $dj_post->post_status === 'publish') {
                    $dj_name = get_post_meta($dj_id, '_dj_name', true);
                    if ($dj_name === '') {
                        $dj_name = $dj_post->post_title;
                    }

                    if ($dj_name !== '') {
                        $djs_names[] = trim($dj_name);
                    }
                }
            }
        }
    }

    // Priority 3: Legacy _dj_name fallback
    if (empty($djs_names)) {
        $dj_fallback = get_post_meta($event_id, '_dj_name', true);
        if ($dj_fallback) {
            $djs_names[] = trim($dj_fallback);
        }
    }
    
    // Remove duplicates and empty values
    $djs_names = array_values(array_unique(array_filter($djs_names)));
    
    // Format display
    if (!empty($djs_names)) {
        $max_visible = 6; // Show more DJs in modal
        $visible = array_slice($djs_names, 0, $max_visible);
        $remaining = max(count($djs_names) - $max_visible, 0);
        
        $dj_display = '<strong>' . esc_html($visible[0]) . '</strong>';
        if (count($visible) > 1) {
            $rest = array_slice($visible, 1);
            $dj_display .= ', ' . esc_html(implode(', ', $rest));
        }
        if ($remaining > 0) {
            $dj_display .= ' <span class="dj-more">+' . esc_html($remaining) . ' DJs</span>';
        }
    } else {
        $dj_display = '<span class="dj-fallback">Line-up em breve</span>';
    }
    
    // Process location
    $event_location = '';
    $event_location_area = '';
    if (!empty($location)) {
        if (strpos($location, '|') !== false) {
            list($event_location, $event_location_area) = array_map('trim', explode('|', $location, 2));
        } else {
            $event_location = trim($location);
        }
    }
    
    // Process banner
    $banner_url = '';
    if ($banner) {
        $banner_url = is_numeric($banner) ? wp_get_attachment_url($banner) : $banner;
    }
    if (!$banner_url) {
        $banner_url = get_the_post_thumbnail_url($event_id, 'large');
    }
    if (!$banner_url) {
        $banner_url = 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=2070';
    }
    
    // Get event content
    $content = apply_filters('the_content', $event->post_content);
    
    // Build modal HTML
    ob_start();
    ?>
    <div class="apollo-event-modal-overlay" data-apollo-close></div>
    <div class="apollo-event-modal-content" role="dialog" aria-modal="true" aria-labelledby="modal-title-<?php echo esc_attr($event_id); ?>">
        
        <button class="apollo-event-modal-close" type="button" data-apollo-close aria-label="Fechar">
            <i class="ri-close-line"></i>
        </button>
        
        <div class="apollo-event-hero">
            <div class="apollo-event-hero-media">
                <img src="<?php echo esc_url($banner_url); ?>" alt="<?php echo esc_attr($event->post_title); ?>" loading="lazy">
                <div class="apollo-event-date-chip">
                    <span class="d"><?php echo esc_html($date_info['day']); ?></span>
                    <span class="m"><?php echo esc_html($date_info['month_pt']); ?></span>
                </div>
            </div>
            
            <div class="apollo-event-hero-info">
                <h1 class="apollo-event-title" id="modal-title-<?php echo esc_attr($event_id); ?>">
                    <?php echo esc_html($event->post_title); ?>
                </h1>
                <p class="apollo-event-djs">
                    <i class="ri-sound-module-fill"></i>
                    <span><?php echo wp_kses_post($dj_display); ?></span>
                </p>
                <?php if (!empty($event_location)): ?>
                <p class="apollo-event-location">
                    <i class="ri-map-pin-2-line"></i>
                    <span class="event-location-name"><?php echo esc_html($event_location); ?></span>
                    <?php if (!empty($event_location_area)): ?>
                        <span class="event-location-area">(<?php echo esc_html($event_location_area); ?>)</span>
                    <?php endif; ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="apollo-event-body">
            <?php echo wp_kses_post($content); ?>
        </div>
        
    </div>
    <?php
    $html = ob_get_clean();
    
    wp_send_json_success(array('html' => $html));
    
    } catch (Exception $e) {
        // Log error in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG && defined('APOLLO_PORTAL_DEBUG') && APOLLO_PORTAL_DEBUG) {
            error_log('Apollo Events: Error in apollo_ajax_load_event_modal - ' . $e->getMessage());
        }
        
        // Return graceful error
        wp_send_json_error(array(
            'message' => 'Erro ao carregar evento. Tente novamente mais tarde.'
        ));
    }
}

