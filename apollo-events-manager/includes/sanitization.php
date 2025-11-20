<?php
/**
 * Apollo Events Manager - Sanitization System
 * 
 * Powerful, independent sanitization for all inputs and meta keys
 * Strict mode: Forces correct slugs and validates all data
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Apollo Sanitization Class
 * Centralized sanitization for the entire plugin
 */
class Apollo_Events_Sanitization {
    
    /**
     * Sanitize meta key slug
     * Forces correct format: lowercase, underscores, alphanumeric
     * 
     * @param string $meta_key Raw meta key
     * @return string Sanitized meta key
     */
    public static function sanitize_meta_key($meta_key) {
        if (!is_string($meta_key)) {
            return '';
        }
        
        // Remove whitespace
        $meta_key = trim($meta_key);
        
        // Convert to lowercase
        $meta_key = strtolower($meta_key);
        
        // Replace spaces and hyphens with underscores
        $meta_key = preg_replace('/[\s\-]+/', '_', $meta_key);
        
        // Remove all characters except alphanumeric and underscores
        $meta_key = preg_replace('/[^a-z0-9_]/', '', $meta_key);
        
        // Remove multiple consecutive underscores
        $meta_key = preg_replace('/_+/', '_', $meta_key);
        
        // Remove leading/trailing underscores
        $meta_key = trim($meta_key, '_');
        
        // Ensure it starts with underscore if it's a private meta key
        if (!empty($meta_key) && !str_starts_with($meta_key, '_')) {
            // Check if it should be private (most Apollo meta keys are private)
            $private_keys = [
                'event', 'dj', 'local', 'apollo', 'timetable', 'banner',
                'title', 'date', 'time', 'location', 'address', 'latitude',
                'longitude', 'capacity', 'image', 'video', 'tickets', 'cupom'
            ];
            
            foreach ($private_keys as $prefix) {
                if (str_starts_with($meta_key, $prefix)) {
                    $meta_key = '_' . $meta_key;
                    break;
                }
            }
        }
        
        return $meta_key;
    }
    
    /**
     * Validate meta key against allowed list
     * 
     * @param string $meta_key Meta key to validate
     * @return bool True if valid
     */
    public static function validate_meta_key($meta_key) {
        $allowed_keys = self::get_allowed_meta_keys();
        return in_array($meta_key, $allowed_keys, true);
    }
    
    /**
     * Get all allowed meta keys
     * 
     * @return array List of allowed meta keys
     */
    public static function get_allowed_meta_keys() {
        return [
            // Event meta keys
            '_event_title',
            '_event_start_date',
            '_event_end_date',
            '_event_start_time',
            '_event_end_time',
            '_event_banner',
            '_event_dj_ids',
            '_event_local_ids',
            '_event_timetable',
            '_event_video_url',
            '_tickets_ext',
            '_cupom_ario',
            '_3_imagens_promo',
            '_imagem_final',
            '_event_location',
            '_favorites_count',
            
            // DJ meta keys
            '_dj_name',
            '_dj_instagram',
            '_dj_soundcloud',
            '_dj_bio',
            '_dj_original_project_1',
            '_dj_set_url',
            '_dj_media_kit_url',
            '_dj_rider_url',
            '_dj_mix_url',
            
            // Local meta keys
            '_local_name',
            '_local_address',
            '_local_latitude',
            '_local_longitude',
            '_local_city',
            '_local_state',
            '_local_region',
            '_local_capacity',
            '_local_image_1',
            '_local_image_2',
            '_local_image_3',
            
            // Moderation meta keys
            '_apollo_mod_approved',
            '_apollo_mod_approved_date',
            '_apollo_mod_approved_by',
            '_apollo_mod_rejected',
            '_apollo_mod_rejected_date',
            '_apollo_mod_rejected_by',
            '_apollo_user_id',
        ];
    }
    
    /**
     * Sanitize text field
     * 
     * @param mixed $value Value to sanitize
     * @return string Sanitized text
     */
    public static function sanitize_text($value) {
        if (is_array($value) || is_object($value)) {
            return '';
        }
        return sanitize_text_field((string) $value);
    }
    
    /**
     * Sanitize textarea
     * 
     * @param mixed $value Value to sanitize
     * @return string Sanitized textarea
     */
    public static function sanitize_textarea($value) {
        if (is_array($value) || is_object($value)) {
            return '';
        }
        return sanitize_textarea_field((string) $value);
    }
    
    /**
     * Sanitize URL
     * 
     * @param mixed $value Value to sanitize
     * @return string Sanitized URL
     */
    public static function sanitize_url($value) {
        if (is_array($value) || is_object($value)) {
            return '';
        }
        return esc_url_raw((string) $value);
    }
    
    /**
     * Sanitize email
     * 
     * @param mixed $value Value to sanitize
     * @return string Sanitized email
     */
    public static function sanitize_email($value) {
        if (is_array($value) || is_object($value)) {
            return '';
        }
        return sanitize_email((string) $value);
    }
    
    /**
     * Sanitize integer
     * 
     * @param mixed $value Value to sanitize
     * @return int Sanitized integer
     */
    public static function sanitize_int($value) {
        return absint($value);
    }
    
    /**
     * Sanitize float
     * 
     * @param mixed $value Value to sanitize
     * @return float Sanitized float
     */
    public static function sanitize_float($value) {
        return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    /**
     * Sanitize array of IDs
     * 
     * @param mixed $value Value to sanitize
     * @return array Sanitized array of IDs
     */
    public static function sanitize_id_array($value) {
        if (!is_array($value)) {
            if (is_string($value)) {
                $value = explode(',', $value);
            } else {
                return [];
            }
        }
        
        return array_map('absint', array_filter($value, 'is_numeric'));
    }
    
    /**
     * Sanitize timetable array
     * 
     * @param mixed $value Value to sanitize
     * @return array Sanitized timetable
     */
    public static function sanitize_timetable($value) {
        if (!is_array($value)) {
            return [];
        }
        
        $sanitized = [];
        foreach ($value as $slot) {
            if (!is_array($slot)) {
                continue;
            }
            
            $sanitized_slot = [
                'dj' => isset($slot['dj']) ? absint($slot['dj']) : 0,
                'from' => isset($slot['from']) ? sanitize_text_field($slot['from']) : '',
                'to' => isset($slot['to']) ? sanitize_text_field($slot['to']) : '',
            ];
            
            if ($sanitized_slot['dj'] > 0) {
                $sanitized[] = $sanitized_slot;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize post meta value based on meta key
     * 
     * @param string $meta_key Meta key
     * @param mixed $value Value to sanitize
     * @return mixed Sanitized value
     */
    public static function sanitize_meta_value($meta_key, $value) {
        // First sanitize the meta key itself
        $meta_key = self::sanitize_meta_key($meta_key);
        
        // Validate meta key
        if (!self::validate_meta_key($meta_key)) {
            error_log("Apollo: Invalid meta key attempted: {$meta_key}");
            return null;
        }
        
        // Sanitize based on meta key type
        if (strpos($meta_key, '_id') !== false || strpos($meta_key, 'count') !== false) {
            // Integer fields
            return self::sanitize_int($value);
        } elseif (strpos($meta_key, '_ids') !== false) {
            // Array of IDs
            return self::sanitize_id_array($value);
        } elseif (strpos($meta_key, 'latitude') !== false || strpos($meta_key, 'longitude') !== false) {
            // Float fields
            return self::sanitize_float($value);
        } elseif (strpos($meta_key, 'timetable') !== false) {
            // Timetable array
            return self::sanitize_timetable($value);
        } elseif (strpos($meta_key, 'url') !== false || strpos($meta_key, 'banner') !== false || strpos($meta_key, 'image') !== false) {
            // URL fields
            return self::sanitize_url($value);
        } elseif (strpos($meta_key, 'email') !== false) {
            // Email fields
            return self::sanitize_email($value);
        } elseif (strpos($meta_key, 'bio') !== false || strpos($meta_key, 'description') !== false) {
            // Textarea fields
            return self::sanitize_textarea($value);
        } else {
            // Default: text field
            return self::sanitize_text($value);
        }
    }
    
    /**
     * Get post meta with sanitization
     * 
     * @param int $post_id Post ID
     * @param string $meta_key Meta key
     * @param bool $single Single value
     * @return mixed Sanitized meta value
     */
    public static function get_post_meta($post_id, $meta_key, $single = true) {
        $meta_key = self::sanitize_meta_key($meta_key);
        
        if (!self::validate_meta_key($meta_key)) {
            error_log("Apollo: Attempted to get invalid meta key: {$meta_key}");
            return $single ? '' : [];
        }
        
        $value = get_post_meta($post_id, $meta_key, $single);
        
        // Sanitize on retrieval
        return self::sanitize_meta_value($meta_key, $value);
    }
    
    /**
     * Update post meta with sanitization
     * 
     * @param int $post_id Post ID
     * @param string $meta_key Meta key
     * @param mixed $meta_value Meta value
     * @return int|false Meta ID on success, false on failure
     */
    public static function update_post_meta($post_id, $meta_key, $meta_value) {
        $meta_key = self::sanitize_meta_key($meta_key);
        
        if (!self::validate_meta_key($meta_key)) {
            error_log("Apollo: Attempted to update invalid meta key: {$meta_key}");
            return false;
        }
        
        $sanitized_value = self::sanitize_meta_value($meta_key, $meta_value);
        
        if ($sanitized_value === null) {
            return false;
        }
        
        return update_post_meta($post_id, $meta_key, $sanitized_value);
    }
    
    /**
     * Delete post meta with validation
     * 
     * @param int $post_id Post ID
     * @param string $meta_key Meta key
     * @return bool True on success, false on failure
     */
    public static function delete_post_meta($post_id, $meta_key) {
        $meta_key = self::sanitize_meta_key($meta_key);
        
        if (!self::validate_meta_key($meta_key)) {
            error_log("Apollo: Attempted to delete invalid meta key: {$meta_key}");
            return false;
        }
        
        return delete_post_meta($post_id, $meta_key);
    }
}

