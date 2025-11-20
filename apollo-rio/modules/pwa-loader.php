<?php
/**
 * Apollo::Rio - PWA Module Loader
 * 
 * Loads the Progressive Web App module if available
 */

if (!defined('ABSPATH')) exit;

$pwa_file = __DIR__ . '/pwa/pwa.php';

if (file_exists($pwa_file)) {
    require_once $pwa_file;
}







