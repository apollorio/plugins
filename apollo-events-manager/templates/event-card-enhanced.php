<?php 
/**
 * Template Apollo: Event Card (Enhanced with Tailwind/ShadCN)
 * 
 * Versão melhorada do event-card.php com suporte Tailwind/ShadCN
 * Mantém compatibilidade total com uni.css e CodePen raxqVGR
 * 
 * @package Apollo Events Manager
 * @version 2.0.0
 */

// Este arquivo é um wrapper que garante compatibilidade Tailwind
// O template original event-card.php já está correto conforme CodePen

// Carregar template original
$original_template = plugin_dir_path(__FILE__) . 'event-card.php';
if (file_exists($original_template)) {
    // Garantir Tailwind/ShadCN antes de incluir
    if (function_exists('apollo_shadcn_init')) {
        apollo_shadcn_init();
    } elseif (class_exists('Apollo_ShadCN_Loader')) {
        Apollo_ShadCN_Loader::get_instance();
    }
    
    include $original_template;
} else {
    // Fallback se template original não existir
    echo '<!-- Template event-card.php não encontrado -->';
}

