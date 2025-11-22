<?php
/**
 * Force Register Test - Força registro de tudo manualmente
 * 
 * Este teste força o registro de CPTs, shortcodes e roles
 * sem depender dos hooks do WordPress
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load WordPress
if (!defined('ABSPATH')) {
    $wp_load_paths = [
        __DIR__ . '/../../../../wp-load.php',
        __DIR__ . '/../../../wp-load.php',
    ];
    
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
}

if (!defined('ABSPATH')) {
    die('WordPress not found');
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Force Register Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; }
        .success { color: #46b450; }
        .error { color: #dc3232; }
        code { background: #f0f0f0; padding: 2px 6px; }
    </style>
</head>
<body>
    <h1>Force Register Test</h1>
    
    <?php
    $plugin_dir = dirname(dirname(__FILE__));
    
    // 1. Load plugin
    echo '<h2>1. Carregando Plugin...</h2>';
    $plugin_file = $plugin_dir . '/apollo-events-manager.php';
    if (file_exists($plugin_file)) {
        require_once $plugin_file;
        echo '<p class="success">✅ Plugin carregado</p>';
    } else {
        echo '<p class="error">❌ Plugin não encontrado: ' . htmlspecialchars($plugin_file) . '</p>';
        die();
    }
    
    // 2. Instantiate plugin
    echo '<h2>2. Instanciando Plugin...</h2>';
    global $apollo_events_manager;
    if (!isset($apollo_events_manager)) {
        $apollo_events_manager = new Apollo_Events_Manager_Plugin();
    }
    echo '<p class="success">✅ Plugin instanciado</p>';
    
    // 3. Load and register post types
    echo '<h2>3. Registrando CPTs...</h2>';
    $post_types_file = $plugin_dir . '/includes/post-types.php';
    if (file_exists($post_types_file)) {
        require_once $post_types_file;
        if (class_exists('Apollo_Post_Types')) {
            $pt_instance = new Apollo_Post_Types();
            $pt_instance->register_post_types();
            $pt_instance->register_taxonomies();
            echo '<p class="success">✅ CPTs registrados</p>';
        } else {
            echo '<p class="error">❌ Classe Apollo_Post_Types não encontrada</p>';
        }
    } else {
        echo '<p class="error">❌ Arquivo post-types.php não encontrado</p>';
    }
    
    // 4. Register shortcodes
    echo '<h2>4. Registrando Shortcodes...</h2>';
    
    // Load shortcode files
    $shortcode_files = [
        'shortcodes-submit.php' => 'submit_event_form',
        'shortcodes-auth.php' => ['apollo_register', 'apollo_login'],
        'shortcodes-my-apollo.php' => 'my_apollo_dashboard',
    ];
    
    foreach ($shortcode_files as $file => $shortcodes) {
        $file_path = $plugin_dir . '/includes/' . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
            echo '<p class="success">✅ Arquivo ' . htmlspecialchars($file) . ' carregado</p>';
        } else {
            echo '<p class="error">❌ Arquivo ' . htmlspecialchars($file) . ' não encontrado</p>';
        }
    }
    
    // Register apollo_eventos
    if (function_exists('apollo_events_shortcode_handler')) {
        add_shortcode('apollo_eventos', 'apollo_events_shortcode_handler');
        echo '<p class="success">✅ Shortcode apollo_eventos registrado</p>';
    }
    
    // 5. Create clubber role
    echo '<h2>5. Criando Role Clubber...</h2>';
    if (!get_role('clubber')) {
        add_role('clubber', 'Clubber', ['read' => true, 'upload_files' => true]);
        echo '<p class="success">✅ Role clubber criado</p>';
    } else {
        echo '<p class="success">✅ Role clubber já existe</p>';
    }
    
    // 6. Verify registration
    echo '<h2>6. Verificando Registros...</h2>';
    
    $cpts = ['event_listing', 'event_dj', 'event_local'];
    foreach ($cpts as $cpt) {
        if (post_type_exists($cpt)) {
            echo '<p class="success">✅ CPT ' . $cpt . ' registrado</p>';
        } else {
            echo '<p class="error">❌ CPT ' . $cpt . ' NÃO registrado</p>';
        }
    }
    
    $shortcodes = ['apollo_eventos', 'apollo_dj_profile', 'submit_event_form', 'apollo_register', 'apollo_login', 'my_apollo_dashboard'];
    foreach ($shortcodes as $sc) {
        if (shortcode_exists($sc)) {
            echo '<p class="success">✅ Shortcode ' . $sc . ' registrado</p>';
        } else {
            echo '<p class="error">❌ Shortcode ' . $sc . ' NÃO registrado</p>';
        }
    }
    
    if (get_role('clubber')) {
        echo '<p class="success">✅ Role clubber existe</p>';
    } else {
        echo '<p class="error">❌ Role clubber NÃO existe</p>';
    }
    ?>
</body>
</html>

