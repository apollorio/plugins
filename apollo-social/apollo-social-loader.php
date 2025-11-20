<?php
/**
 * Loader: só ativa apollo-social se o plugin base (WordPress) estiver ativo
 * 
 * Verifica se WordPress está carregado antes de inicializar o plugin.
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Verificar se WordPress está carregado
if (!function_exists('add_action')) {
    if (function_exists('wp_die')) {
        wp_die('WordPress precisa estar ativo para usar Apollo Social.');
    } else {
        die('WordPress não está carregado. Apollo Social requer WordPress.');
    }
}

// Carregar módulos do apollo-social com validação defensiva
$loader_dir = dirname(__FILE__);
$main_file = $loader_dir . '/apollo-social.php';

if (file_exists($main_file)) {
    require_once $main_file;
} else {
    error_log('Apollo Social: Arquivo principal não encontrado: ' . $main_file);
    
    // Tentar carregar outros arquivos PHP como fallback (compatibilidade)
    $php_files = glob($loader_dir . '/*.php');
    if (!empty($php_files)) {
        foreach ($php_files as $file) {
            if (basename($file) !== 'apollo-social-loader.php' && file_exists($file)) {
                require_once $file;
            }
        }
    }
}
