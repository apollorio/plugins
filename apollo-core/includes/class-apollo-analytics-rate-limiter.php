<?php
/**
 * Apollo Analytics Rate Limiter
 *
 * Provides rate limiting functionality for analytics tracking endpoints
 * to prevent abuse and ensure fair usage.
 *
 * @package Apollo_Core
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Apollo_Analytics_Rate_Limiter {

    /**
     * Check if request is within rate limits
     *
     * @param string $action Action identifier
     * @param int $limit Maximum requests per window
     * @param int $window_seconds Time window in seconds
     * @return bool True if within limits, false if exceeded
     */
    public static function check_rate_limit($action, $limit = 100, $window_seconds = HOUR_IN_SECONDS) {
        $ip_hash = self::get_client_ip_hash();
        $transient_key = 'apollo_analytics_rl_' . $action . '_' . $ip_hash;
        $count = get_transient($transient_key) ?: 0;

        if ($count >= $limit) {
            self::log_rate_limit_exceeded($action, $ip_hash, $count);
            return false;
        }

        set_transient($transient_key, $count + 1, $window_seconds);
        return true;
    }

    /**
     * Get hashed client IP for rate limiting
     *
     * @return string Hashed IP address
     */
    private static function get_client_ip_hash() {
        $ip = self::get_client_ip();
        return wp_hash($ip, 'nonce');
    }

    /**
     * Get real client IP address
     *
     * @return string Client IP address
     */
    private static function get_client_ip() {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = trim($_SERVER[$header]);
                // Handle comma-separated IPs (X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Log rate limit violations for monitoring
     *
     * @param string $action Action that was rate limited
     * @param string $ip_hash Hashed IP
     * @param int $count Request count
     */
    private static function log_rate_limit_exceeded($action, $ip_hash, $count) {
        $log_data = [
            'timestamp' => current_time('mysql'),
            'action' => $action,
            'ip_hash' => $ip_hash,
            'count' => $count,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
        ];

        // Log to error log for monitoring
        error_log(sprintf(
            'Apollo Analytics Rate Limit Exceeded: Action=%s, IP_Hash=%s, Count=%d',
            $action,
            $ip_hash,
            $count
        ));

        // Store in database for admin review (optional)
        if (get_option('apollo_analytics_log_rate_limits', false)) {
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'apollo_audit_log',
                [
                    'event_type' => 'rate_limit_exceeded',
                    'event_data' => wp_json_encode($log_data),
                    'created_at' => current_time('mysql')
                ],
                ['%s', '%s', '%s']
            );
        }
    }

    /**
     * Clean up expired rate limit transients
     * Called by maintenance cron
     */
    public static function cleanup_expired_limits() {
        global $wpdb;

        // WordPress transients auto-expire, but we can clean up old ones
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE %s
             AND option_value = '0'",
            $wpdb->esc_like('_transient_apollo_analytics_rl_') . '%'
        ));
    }
}
