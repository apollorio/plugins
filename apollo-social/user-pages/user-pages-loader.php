<?php

/**
 * Loader: integra user-pages ao apollo-social, mas só ativa se apollo-social estiver ativo
 */

// Check for Apollo Social plugin using constants (safe for frontend)
if (! defined('APOLLO_SOCIAL_PLUGIN_DIR')) {
    // Apollo Social not loaded, return silently during frontend.
    if (! is_admin()) {
        return;
    }
    // In admin, show error.
    wp_die(esc_html__('O plugin Apollo Social precisa estar ativo para usar User Pages.', 'apollo-social'));
}

// Carrega todos os módulos do user-pages
foreach (glob(__DIR__ . '/*.php') as $file) {
    if (basename($file) !== 'user-pages-loader.php') {
        require_once $file;
    }
}
require_once APOLLO_SOCIAL_PLUGIN_DIR . 'user-pages/tabs/class-user-privacy-tab.php';
