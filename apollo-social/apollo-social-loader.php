<?php
/**
 * Loader: só ativa apollo-social se o plugin base (WordPress) estiver ativo
 */
if (!function_exists('add_action')) {
    wp_die('WordPress precisa estar ativo para usar Apollo Social.');
}
// Carrega todos os módulos do apollo-social
foreach (glob(__DIR__ . '/*.php') as $file) {
    if (basename($file) !== 'apollo-social-loader.php') {
        require_once $file;
    }
}
