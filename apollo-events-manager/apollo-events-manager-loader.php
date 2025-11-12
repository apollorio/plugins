<?php
/**
 * Loader: só ativa apollo-events-manager se apollo-social estiver ativo
 */
if (!is_plugin_active('apollo-social/apollo-social.php')) {
    wp_die('O plugin Apollo Social precisa estar ativo para usar Apollo Events Manager.');
}
// Carrega todos os módulos do apollo-events-manager
foreach (glob(__DIR__ . '/*.php') as $file) {
    if (basename($file) !== 'apollo-events-manager-loader.php') {
        require_once $file;
    }
}
