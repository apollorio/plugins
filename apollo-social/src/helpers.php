<?php
/**
 * Configuration helper function
 * Provides a simple way to access configuration files
 */

if (!function_exists('config')) {
    /**
     * Get configuration value by key
     * 
     * @param string $key Configuration key in dot notation (e.g., 'integrations.badgeos')
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    function config($key, $default = null) {
        static $configs = [];
        
        // Parse the key
        $parts = explode('.', $key);
        $file = $parts[0];
        
        // Load config file if not already loaded
        if (!isset($configs[$file])) {
            $config_file = __DIR__ . "/../config/{$file}.php";
            
            if (file_exists($config_file)) {
                $configs[$file] = require $config_file;
            } else {
                $configs[$file] = [];
            }
        }
        
        // Navigate through the config array
        $value = $configs[$file];
        
        for ($i = 1; $i < count($parts); $i++) {
            if (isset($value[$parts[$i]])) {
                $value = $value[$parts[$i]];
            } else {
                return $default;
            }
        }
        
        return $value;
    }
}

if (!function_exists('apollo_get_user_page')) {
    /**
     * Fetch the user_page assigned to the given user.
     *
     * @param int $user_id User identifier.
     *
     * @return \WP_Post|null
     */
    function apollo_get_user_page($user_id)
    {
        $user_id = absint($user_id);

        if ($user_id <= 0) {
            return null;
        }

        return \Apollo\Modules\UserPages\UserPageRepository::get($user_id);
    }
}

if (!function_exists('apollo_get_or_create_user_page')) {
    /**
     * Retrieve the user_page or create it when missing.
     *
     * @param int $user_id User identifier.
     *
     * @return \WP_Post|null
     */
    function apollo_get_or_create_user_page($user_id)
    {
        $user_id = absint($user_id);

        if ($user_id <= 0) {
            return null;
        }

        return \Apollo\Modules\UserPages\UserPageRepository::getOrCreate($user_id);
    }
}