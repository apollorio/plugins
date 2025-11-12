<?php
/**
 * Loader: integra user-pages ao apollo-social, mas só ativa se apollo-social estiver ativo
 */
if (!is_plugin_active('apollo-social/apollo-social.php')) {
    wp_die('O plugin Apollo Social precisa estar ativo para usar User Pages.');
}
// Carrega todos os módulos do user-pages
foreach (glob(__DIR__ . '/*.php') as $file) {
    if (basename($file) !== 'user-pages-loader.php') {
        require_once $file;
    }
}
