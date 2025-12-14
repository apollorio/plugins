<?php

// phpcs:ignoreFile
/**
 * Security Audit Helper Functions
 * TODO 130: Centralized security checks and sanitization
 *
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

/**
 * Security audit helper class
 */
class Apollo_Events_Security_Audit
{
    /**
     * Sanitize output for display
     * TODO 130: XSS prevention
     *
     * @param mixed  $value Value to sanitize
     * @param string $type Type of sanitization (text, url, email, html)
     * @return string Sanitized value
     */
    public static function sanitize_output($value, $type = 'text')
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        switch ($type) {
            case 'url':
                return esc_url($value);

            case 'email':
                return sanitize_email($value);

            case 'html':
                return wp_kses_post($value);

            case 'textarea':
                return sanitize_textarea_field($value);

            case 'text':
            default:
                return esc_html($value);
        }
    }

    /**
     * Sanitize input from user
     * TODO 130: SQL injection prevention
     *
     * @param mixed  $value Value to sanitize
     * @param string $type Type of sanitization
     * @return mixed Sanitized value
     */
    public static function sanitize_input($value, $type = 'text')
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        switch ($type) {
            case 'int':
                return absint($value);

            case 'float':
                return floatval($value);

            case 'url':
                return esc_url_raw($value);

            case 'email':
                return sanitize_email($value);

            case 'textarea':
                return sanitize_textarea_field($value);

            case 'text':
            default:
                return sanitize_text_field($value);
        }
    }

    /**
     * Verify nonce
     * TODO 130: CSRF prevention
     *
     * @param string $action Action name
     * @param string $nonce Nonce value
     * @return bool True if valid
     */
    public static function verify_nonce($action, $nonce)
    {
        return wp_verify_nonce($nonce, $action);
    }

    /**
     * Check user capability
     * TODO 130: Authorization check
     *
     * @param string   $capability Required capability
     * @param int|null $user_id User ID (null for current user)
     * @return bool True if user has capability
     */
    public static function check_capability($capability, $user_id = null)
    {
        if ($user_id === null) {
            return current_user_can($capability);
        }
        $user = get_user_by('id', $user_id);

        return $user && $user->has_cap($capability);
    }

    /**
     * Sanitize array recursively
     * TODO 130: Deep sanitization
     *
     * @param array  $array Array to sanitize
     * @param string $type Type of sanitization
     * @return array Sanitized array
     */
    public static function sanitize_array($array, $type = 'text')
    {
        if (! is_array($array)) {
            return self::sanitize_input($array, $type);
        }

        $sanitized = [];
        foreach ($array as $key => $value) {
            $sanitized_key = sanitize_key($key);
            if (is_array($value)) {
                $sanitized[ $sanitized_key ] = self::sanitize_array($value, $type);
            } else {
                $sanitized[ $sanitized_key ] = self::sanitize_input($value, $type);
            }
        }

        return $sanitized;
    }

    /**
     * Escape attribute value
     * TODO 130: Attribute XSS prevention
     *
     * @param string $value Value to escape
     * @return string Escaped value
     */
    public static function esc_attr($value)
    {
        return esc_attr($value);
    }

    /**
     * Escape JavaScript
     * TODO 130: JS injection prevention
     *
     * @param string $value Value to escape
     * @return string Escaped value
     */
    public static function esc_js($value)
    {
        return esc_js($value);
    }

    /**
     * Escape URL
     * TODO 130: URL injection prevention
     *
     * @param string $url URL to escape
     * @return string Escaped URL
     */
    public static function esc_url($url)
    {
        return esc_url($url);
    }

    /**
     * Prepare SQL query safely
     * TODO 130: SQL injection prevention
     *
     * @param string $query SQL query with placeholders
     * @param array  $args Arguments for placeholders
     * @return string|WP_Error Prepared query or error
     */
    public static function prepare_query($query, $args = [])
    {
        global $wpdb;

        if (empty($args)) {
            return $query;
        }

        return $wpdb->prepare($query, $args);
    }
}
