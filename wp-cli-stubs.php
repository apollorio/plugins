<?php
/**
 * WP_CLI Stubs for PHPStan
 * Basic stubs for WP_CLI classes and methods
 */

namespace WP_CLI {
    class WP_CLI {
        public static function log($message) {}
        public static function success($message) {}
        public static function error($message) {}
        public static function warning($message) {}
        public static function line($message) {}
        public static function colorize($message) {}
        public static function confirm($message) {}
    }
}

namespace WP_CLI\Utils {
    function format_items($format, $items, $fields) {}
}
