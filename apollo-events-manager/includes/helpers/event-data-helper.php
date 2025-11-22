<?php
/**
 * FILE: apollo-events-manager/includes/helpers/event-data-helper.php
 * Purpose: Centralize all event data retrieval logic (DJs, Local, Banner)
 * Eliminates 300+ lines of duplicated code across templates
 * 
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

class Apollo_Event_Data_Helper {
    
    /**
     * Get DJ lineup with fallback chain
     * @param int $event_id Event post ID
     * @return array DJ names array
     */
    public static function get_dj_lineup($event_id) {
        $dj_names = [];
        
        // Strategy 1: _event_dj_ids (primary)
        $dj_ids = apollo_aem_parse_ids(
            apollo_get_post_meta($event_id, '_event_dj_ids', true)
        );
        
        if (!empty($dj_ids)) {
            foreach ($dj_ids as $dj_id) {
                $dj_post = get_post($dj_id);
                if ($dj_post && $dj_post->post_status === 'publish' && $dj_post->post_type === 'event_dj') {
                    $name = apollo_get_post_meta($dj_id, '_dj_name', true) ?: $dj_post->post_title;
                    if ($name) $dj_names[] = trim($name);
                }
            }
        }
        
        // Strategy 2: _event_timetable (fallback)
        if (empty($dj_names)) {
            $timetable = apollo_sanitize_timetable(
                apollo_get_post_meta($event_id, '_event_timetable', true)
            );
            if (empty($timetable)) {
                $timetable = apollo_sanitize_timetable(
                    apollo_get_post_meta($event_id, '_timetable', true)
                );
            }
            
            if (!empty($timetable)) {
                foreach ($timetable as $slot) {
                    $dj_id = isset($slot['dj']) ? (int)$slot['dj'] : 0;
                    if (!$dj_id) continue;
                    
                    $dj_post = get_post($dj_id);
                    if ($dj_post && $dj_post->post_status === 'publish') {
                        $name = apollo_get_post_meta($dj_id, '_dj_name', true) ?: $dj_post->post_title;
                        if ($name) $dj_names[] = trim($name);
                    }
                }
            }
        }
        
        // Strategy 3: Direct _dj_name (last fallback)
        if (empty($dj_names)) {
            $direct = apollo_get_post_meta($event_id, '_dj_name', true);
            if ($direct) $dj_names[] = trim($direct);
        }
        
        return array_values(array_unique(array_filter($dj_names)));
    }
    
    /**
     * Format DJ display string with +N indicator
     * @param array $dj_names DJ names
     * @param int $max_visible Max to show (default 6)
     * @return string HTML formatted DJ list
     */
    public static function format_dj_display($dj_names, $max_visible = 6) {
        if (empty($dj_names)) {
            return __('Line-up em breve', 'apollo-events-manager');
        }
        
        $visible = array_slice($dj_names, 0, $max_visible);
        $remaining = max(count($dj_names) - $max_visible, 0);
        
        $display = '<strong>' . esc_html($visible[0]) . '</strong>';
        if (count($visible) > 1) {
            $display .= ', ' . esc_html(implode(', ', array_slice($visible, 1)));
        }
        if ($remaining > 0) {
            $display .= sprintf(' <span style="opacity:0.7">+%d DJs</span>', $remaining);
        }
        
        return $display;
    }
    
    /**
     * Get primary local data with comprehensive validation
     * @param int $event_id Event post ID
     * @return array|false ['id' => int, 'name' => string, 'slug' => string, 'address' => string, 'city' => string, 'state' => string, 'lat' => float, 'lng' => float]
     */
    public static function get_local_data($event_id) {
        $local_id = 0;
        
        // Get local ID: primary method
        $local_ids = apollo_aem_parse_ids(
            apollo_get_post_meta($event_id, '_event_local_ids', true)
        );
        $local_id = !empty($local_ids) ? (int)$local_ids[0] : 0;
        
        // Fallback: legacy _event_local
        if (!$local_id) {
            $legacy = apollo_get_post_meta($event_id, '_event_local', true);
            $local_id = $legacy ? (int)$legacy : 0;
        }
        
        if (!$local_id) return false;
        
        $local_post = get_post($local_id);
        if (!$local_post || $local_post->post_status !== 'publish') return false;
        
        // Build comprehensive local data
        $name = apollo_get_post_meta($local_id, '_local_name', true) ?: $local_post->post_title;
        $address = apollo_get_post_meta($local_id, '_local_address', true);
        $city = apollo_get_post_meta($local_id, '_local_city', true);
        $state = apollo_get_post_meta($local_id, '_local_state', true);
        
        // Get coordinates (multiple key variations)
        $lat = apollo_get_post_meta($local_id, '_local_latitude', true);
        if (!$lat) $lat = apollo_get_post_meta($local_id, '_local_lat', true);
        
        $lng = apollo_get_post_meta($local_id, '_local_longitude', true);
        if (!$lng) $lng = apollo_get_post_meta($local_id, '_local_lng', true);
        
        $lat = is_numeric($lat) ? floatval($lat) : 0;
        $lng = is_numeric($lng) ? floatval($lng) : 0;
        
        // Slug for filtering
        $slug = $local_post->post_name ?: sanitize_title($name);
        
        return [
            'id'      => $local_id,
            'name'    => $name,
            'slug'    => $slug,
            'address' => $address,
            'city'    => $city,
            'state'   => $state,
            'region'  => trim(implode(', ', array_filter([$city, $state]))),
            'lat'     => $lat,
            'lng'     => $lng,
            'has_coords' => $lat !== 0 && $lng !== 0 && abs($lat) <= 90 && abs($lng) <= 180
        ];
    }
    
    /**
     * Get valid banner URL with comprehensive fallbacks
     * @param int $event_id Event post ID
     * @return string Valid image URL
     */
    public static function get_banner_url($event_id) {
        $banner = apollo_get_post_meta($event_id, '_event_banner', true);
        
        // Try 1: Valid URL
        if ($banner && filter_var($banner, FILTER_VALIDATE_URL)) {
            return $banner;
        }
        
        // Try 2: Attachment ID
        if ($banner && is_numeric($banner)) {
            $url = wp_get_attachment_url($banner);
            if ($url) return $url;
        }
        
        // Try 3: String URL (even if filter fails)
        if ($banner && is_string($banner)) {
            return $banner;
        }
        
        // Try 4: Featured image
        if (has_post_thumbnail($event_id)) {
            $url = get_the_post_thumbnail_url($event_id, 'large');
            if ($url) return $url;
        }
        
        // Default fallback
        return 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=2070';
    }
    
    /**
     * Parse and format start date
     * @param string $raw Date string (Y-m-d or Y-m-d H:i:s format)
     * @return array ['timestamp' => int, 'day' => string, 'month_pt' => string, 'iso_date' => string, 'iso_dt' => string]
     */
    public static function parse_event_date($raw) {
        $raw = trim((string)$raw);
        
        if (empty($raw)) {
            return [
                'timestamp' => null, 'day' => '', 'month_pt' => '', 
                'iso_date' => '', 'iso_dt' => ''
            ];
        }
        
        $ts = strtotime($raw);
        
        // Fallback: Try Y-m-d format explicitly
        if (!$ts) {
            try {
                $dt = DateTime::createFromFormat('Y-m-d', $raw);
                $ts = $dt instanceof DateTime ? $dt->getTimestamp() : 0;
            } catch (Exception $e) {
                $ts = 0;
            }
        }
        
        if (!$ts) {
            return [
                'timestamp' => null, 'day' => '', 'month_pt' => '', 
                'iso_date' => '', 'iso_dt' => ''
            ];
        }
        
        $pt_months = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];
        $month_idx = (int)date_i18n('n', $ts) - 1;
        
        return [
            'timestamp' => $ts,
            'day'       => date_i18n('d', $ts),
            'month_pt'  => $pt_months[$month_idx] ?? '',
            'iso_date'  => date_i18n('Y-m-d', $ts),
            'iso_dt'    => date_i18n('Y-m-d H:i:s', $ts),
        ];
    }
    
    /**
     * Extract YouTube video ID from URL
     * @param string $url YouTube URL
     * @return string|false Video ID or false
     */
    public static function get_youtube_video_id($url) {
        if (empty($url)) return false;
        
        $patterns = [
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
            '/youtu\.be\/([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        
        return false;
    }
    
    /**
     * Build YouTube embed URL
     * @param string $video_id YouTube video ID
     * @return string Embed URL
     */
    public static function build_youtube_embed_url($video_id) {
        if (empty($video_id)) return '';
        return sprintf(
            'https://www.youtube.com/embed/%s?autoplay=1&mute=1&loop=1&playlist=%s&controls=0&showinfo=0&modestbranding=1',
            esc_attr($video_id),
            esc_attr($video_id)
        );
    }
    
    /**
     * Get event coordinates with fallback chain
     * @param int $event_id Event post ID
     * @param int $local_id Local post ID (optional)
     * @return array ['lat' => float, 'lng' => float, 'valid' => bool]
     */
    public static function get_coordinates($event_id, $local_id = 0) {
        $lat = $lng = 0;
        
        // Try 1: Local coordinates
        if ($local_id) {
            foreach (['_local_latitude', '_local_lat'] as $key) {
                if ($val = apollo_get_post_meta($local_id, $key, true)) {
                    $lat = is_numeric($val) ? floatval($val) : 0;
                    if ($lat) break;
                }
            }
            foreach (['_local_longitude', '_local_lng'] as $key) {
                if ($val = apollo_get_post_meta($local_id, $key, true)) {
                    $lng = is_numeric($val) ? floatval($val) : 0;
                    if ($lng) break;
                }
            }
        }
        
        // Try 2: Event coordinates
        if (!$lat) {
            foreach (['_event_latitude', 'geolocation_lat'] as $key) {
                if ($val = apollo_get_post_meta($event_id, $key, true)) {
                    $lat = is_numeric($val) ? floatval($val) : 0;
                    if ($lat) break;
                }
            }
        }
        
        if (!$lng) {
            foreach (['_event_longitude', 'geolocation_long'] as $key) {
                if ($val = apollo_get_post_meta($event_id, $key, true)) {
                    $lng = is_numeric($val) ? floatval($val) : 0;
                    if ($lng) break;
                }
            }
        }
        
        // Validate
        $valid = $lat !== 0 && $lng !== 0 && abs($lat) <= 90 && abs($lng) <= 180;
        
        return [
            'lat'   => $lat,
            'lng'   => $lng,
            'valid' => $valid
        ];
    }
}

// Register as available globally
if (!function_exists('apollo_get_event_data')) {
    function apollo_get_event_data($event_id) {
        return new stdClass([
            'djs' => Apollo_Event_Data_Helper::get_dj_lineup($event_id),
            'local' => Apollo_Event_Data_Helper::get_local_data($event_id),
            'banner' => Apollo_Event_Data_Helper::get_banner_url($event_id),
        ]);
    }
}