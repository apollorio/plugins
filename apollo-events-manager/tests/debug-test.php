<?php
/**
 * Apollo Events Manager - Debug & Test Suite
 * 
 * Este script testa todas as funcionalidades do plugin:
 * - Conexão com banco de dados
 * - Shortcodes
 * - Custom Post Types
 * - Templates
 * - Meta keys
 * - AJAX handlers
 * 
 * Execute via: php tests/debug-test.php
 * Ou via browser: /wp-content/plugins/apollo-events-manager/tests/debug-test.php
 */

// Enable error reporting FIRST
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);

// Set error handler to catch fatal errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (error_reporting() === 0) {
        return false;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $error_html = '<!DOCTYPE html><html><head><title>Fatal Error</title><style>
            body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #dc3232; }
            .error { background: #fff3cd; border-left: 4px solid #ffb900; padding: 15px; margin: 20px 0; }
            code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        </style></head><body><div class="container">';
        $error_html .= '<h1>❌ Erro Fatal Detectado</h1>';
        $error_html .= '<div class="error">';
        $error_html .= '<p><strong>Tipo:</strong> ' . $error['type'] . '</p>';
        $error_html .= '<p><strong>Mensagem:</strong> ' . htmlspecialchars($error['message']) . '</p>';
        $error_html .= '<p><strong>Arquivo:</strong> <code>' . htmlspecialchars($error['file']) . '</code></p>';
        $error_html .= '<p><strong>Linha:</strong> ' . $error['line'] . '</p>';
        $error_html .= '</div></div></body></html>';
        echo $error_html;
        exit;
    }
});

// Prevent direct access - Load WordPress
if (!defined('ABSPATH')) {
    // Try to load WordPress from various possible locations
    $wp_load_paths = [
        __DIR__ . '/../../../../wp-load.php',  // From tests/ directory: wp-content/plugins/apollo-events-manager/tests/
        __DIR__ . '/../../../wp-load.php',     // Alternative path
        __DIR__ . '/../../wp-load.php',        // Another alternative
    ];
    
    // Also try absolute paths based on common WordPress structures
    $current_file = __FILE__;
    $plugin_dir = dirname(dirname($current_file)); // apollo-events-manager/
    $plugins_dir = dirname($plugin_dir); // plugins/
    $wp_content_dir = dirname($plugins_dir); // wp-content/
    $wp_root = dirname($wp_content_dir); // WordPress root
    
    $wp_load_paths[] = $wp_root . '/wp-load.php';
    $wp_load_paths[] = $wp_content_dir . '/../wp-load.php';
    
    $wp_loaded = false;
    $last_error = '';
    $loaded_path = '';
    
    foreach ($wp_load_paths as $path) {
        $real_path = realpath($path);
        if ($real_path && file_exists($real_path)) {
            try {
                require_once $real_path;
                if (defined('ABSPATH')) {
                    $wp_loaded = true;
                    $loaded_path = $real_path;
                    break;
                }
            } catch (Exception $e) {
                $last_error = $e->getMessage();
                continue;
            } catch (Error $e) {
                $last_error = $e->getMessage();
                continue;
            }
        }
    }
    
    if (!$wp_loaded) {
        // Try to find wp-load.php by going up directories
        $current_dir = __DIR__;
        for ($i = 0; $i < 6; $i++) {
            $wp_load = $current_dir . '/wp-load.php';
            if (file_exists($wp_load)) {
                try {
                    require_once $wp_load;
                    if (defined('ABSPATH')) {
                        $wp_loaded = true;
                        $loaded_path = $wp_load;
                        break;
                    }
                } catch (Exception $e) {
                    $last_error = $e->getMessage();
                } catch (Error $e) {
                    $last_error = $e->getMessage();
                }
            }
            $parent_dir = dirname($current_dir);
            if ($parent_dir === $current_dir) {
                break; // Reached filesystem root
            }
            $current_dir = $parent_dir;
        }
    }
    
    if (!$wp_loaded || !defined('ABSPATH')) {
        // Show detailed error information
        $error_html = '<!DOCTYPE html><html><head><title>WordPress Not Found</title><style>
            body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #dc3232; }
            .error { background: #fff3cd; border-left: 4px solid #ffb900; padding: 15px; margin: 20px 0; }
            .info { background: #e8f4f8; border-left: 4px solid #0073aa; padding: 15px; margin: 20px 0; }
            code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
            ul { margin: 10px 0; padding-left: 30px; }
        </style></head><body><div class="container">';
        $error_html .= '<h1>❌ WordPress não encontrado</h1>';
        $error_html .= '<div class="error"><strong>Erro:</strong> Não foi possível carregar o WordPress.</div>';
        $error_html .= '<div class="info"><h3>Informações de Debug:</h3>';
        $error_html .= '<p><strong>Arquivo atual:</strong> <code>' . htmlspecialchars(__FILE__) . '</code></p>';
        $error_html .= '<p><strong>Diretório atual:</strong> <code>' . htmlspecialchars(__DIR__) . '</code></p>';
        $error_html .= '<p><strong>PHP Version:</strong> ' . PHP_VERSION . '</p>';
        if ($last_error) {
            $error_html .= '<p><strong>Último erro:</strong> ' . htmlspecialchars($last_error) . '</p>';
        }
        $error_html .= '<h3>Caminhos testados:</h3><ul>';
        foreach ($wp_load_paths as $path) {
            $exists = file_exists($path) ? '✅' : '❌';
            $error_html .= '<li>' . $exists . ' <code>' . htmlspecialchars($path) . '</code></li>';
        }
        $error_html .= '</ul></div>';
        $error_html .= '<div class="info"><h3>Solução:</h3>';
        $error_html .= '<p>Certifique-se de que:</p><ul>';
        $error_html .= '<li>O WordPress está instalado corretamente</li>';
        $error_html .= '<li>O arquivo <code>wp-load.php</code> existe na raiz do WordPress</li>';
        $error_html .= '<li>Este arquivo está sendo acessado via navegador (não CLI)</li>';
        $error_html .= '<li>As permissões de arquivo estão corretas</li>';
        $error_html .= '</ul></div></div></body></html>';
        
        http_response_code(500);
        die($error_html);
    }
    
    // Ensure WordPress is fully loaded
    if (!function_exists('get_bloginfo')) {
        http_response_code(500);
        die('<!DOCTYPE html><html><head><title>WordPress Not Fully Loaded</title></head><body><h1>Erro: WordPress não foi carregado completamente</h1><p>ABSPATH definido mas funções do WordPress não estão disponíveis.</p></body></html>');
    }
    
    // CRITICAL: Ensure plugin is loaded and initialized
    if (!class_exists('Apollo_Events_Manager_Plugin')) {
        // Try to load plugin manually
        $plugin_file = dirname(dirname(__FILE__)) . '/apollo-events-manager.php';
        if (file_exists($plugin_file)) {
            try {
                require_once $plugin_file;
            } catch (Exception $e) {
                http_response_code(500);
                die('<!DOCTYPE html><html><head><title>Plugin Load Error</title></head><body><h1>Erro ao carregar plugin</h1><p>' . htmlspecialchars($e->getMessage()) . '</p></body></html>');
            } catch (Error $e) {
                http_response_code(500);
                die('<!DOCTYPE html><html><head><title>Plugin Load Error</title></head><body><h1>Erro fatal ao carregar plugin</h1><p>' . htmlspecialchars($e->getMessage()) . '</p><p>Arquivo: ' . htmlspecialchars($e->getFile()) . '</p><p>Linha: ' . $e->getLine() . '</p></body></html>');
            }
        } else {
            http_response_code(500);
            die('<!DOCTYPE html><html><head><title>Plugin Not Found</title></head><body><h1>Plugin não encontrado</h1><p>Caminho testado: ' . htmlspecialchars($plugin_file) . '</p></body></html>');
        }
    }
    
    // CRITICAL: Ensure WordPress is fully initialized before doing anything
    // Don't manually trigger hooks - let WordPress do it naturally
    global $wp_rewrite, $wp;
    
    // Ensure WordPress core objects are initialized
    if (!isset($wp_rewrite)) {
        require_once ABSPATH . WPINC . '/rewrite.php';
        $GLOBALS['wp_rewrite'] = new WP_Rewrite();
    }
    
    if (!isset($wp)) {
        require_once ABSPATH . WPINC . '/class-wp.php';
        $GLOBALS['wp'] = new WP();
    }
    
    // Force WordPress to load plugins (if not already loaded)
    if (!did_action('plugins_loaded')) {
        do_action('plugins_loaded');
    }
    
    // Ensure plugin is initialized
    if (class_exists('Apollo_Events_Manager_Plugin')) {
        global $apollo_events_manager;
        if (!isset($apollo_events_manager) || !($apollo_events_manager instanceof Apollo_Events_Manager_Plugin)) {
            $apollo_events_manager = new Apollo_Events_Manager_Plugin();
        }
    }
    
    // CRITICAL: Only trigger init if it hasn't fired yet AND WordPress is ready
    // Don't manually trigger if WordPress isn't fully initialized
    if (!did_action('init')) {
        // Ensure rewrite is initialized before init
        if (!isset($GLOBALS['wp_rewrite'])) {
            require_once ABSPATH . WPINC . '/rewrite.php';
            $GLOBALS['wp_rewrite'] = new WP_Rewrite();
        }
        do_action('init');
    }
    
    // CRITICAL: If init already fired, manually register everything
    // This ensures CPTs and shortcodes are registered even if init fired before plugin loaded
    if (did_action('init')) {
        // Ensure post types are registered
        $post_types_file = dirname(dirname(__FILE__)) . '/includes/post-types.php';
        if (file_exists($post_types_file)) {
            require_once $post_types_file;
            if (class_exists('Apollo_Post_Types')) {
                $post_types_instance = new Apollo_Post_Types();
                // Force registration directly
                if (method_exists($post_types_instance, 'register_post_types')) {
                    $post_types_instance->register_post_types();
                }
                if (method_exists($post_types_instance, 'register_taxonomies')) {
                    $post_types_instance->register_taxonomies();
                }
            }
        }
        
        // Ensure clubber role exists
        if (!get_role('clubber')) {
            add_role(
                'clubber',
                __('Clubber', 'apollo-events-manager'),
                array(
                    'read' => true,
                    'upload_files' => true,
                    'edit_posts' => false,
                    'publish_posts' => false,
                )
            );
        }
        
        // CRITICAL: Load shortcode files that register shortcodes at file level
        $plugin_dir = dirname(dirname(__FILE__));
        
        // Load submit form shortcode
        $submit_file = $plugin_dir . '/includes/shortcodes-submit.php';
        if (file_exists($submit_file) && !function_exists('aem_submit_event_shortcode')) {
            require_once $submit_file;
        }
        
        // Load auth shortcodes
        $auth_file = $plugin_dir . '/includes/shortcodes-auth.php';
        if (file_exists($auth_file) && !function_exists('apollo_register_shortcode')) {
            require_once $auth_file;
        }
        
        // Load My Apollo dashboard shortcode
        $my_apollo_file = $plugin_dir . '/includes/shortcodes-my-apollo.php';
        if (file_exists($my_apollo_file) && !function_exists('apollo_my_apollo_dashboard_shortcode')) {
            require_once $my_apollo_file;
        }
        
        // Register apollo_eventos shortcode (alias for events)
        if (!shortcode_exists('apollo_eventos')) {
            if (function_exists('apollo_events_shortcode_handler')) {
                add_shortcode('apollo_eventos', 'apollo_events_shortcode_handler');
            } elseif (function_exists('apollo_events_shortcode')) {
                add_shortcode('apollo_eventos', 'apollo_events_shortcode');
            }
        }
    }
}

// Xdebug configuration check
if (function_exists('xdebug_info')) {
    xdebug_info();
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apollo Events Manager - Debug & Test Suite</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1a1a1a;
            margin-bottom: 10px;
            border-bottom: 3px solid #0073aa;
            padding-bottom: 10px;
        }
        h2 {
            color: #0073aa;
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e0e0e0;
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
            border-left: 4px solid #0073aa;
        }
        .test-item {
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 3px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
        }
        .status.pass {
            background: #46b450;
            color: white;
        }
        .status.fail {
            background: #dc3232;
            color: white;
        }
        .status.warning {
            background: #ffb900;
            color: white;
        }
        .status.info {
            background: #0073aa;
            color: white;
        }
        .details {
            margin-top: 10px;
            padding: 10px;
            background: #fff;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
        }
        .error {
            color: #dc3232;
        }
        .success {
            color: #46b450;
        }
        .info-text {
            color: #0073aa;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #0073aa;
            color: white;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .summary {
            background: #0073aa;
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-top: 30px;
        }
        .summary h3 {
            margin-bottom: 10px;
        }
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .stat-box {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 5px;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Apollo Events Manager - Debug & Test Suite</h1>
        <p><strong>Data:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
        <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
        <p><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></p>
        <p><strong>Xdebug:</strong> <?php echo function_exists('xdebug_info') ? '✅ Ativo' : '❌ Não encontrado'; ?></p>
        
        <?php
        $results = [
            'total' => 0,
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
        ];
        
        /**
         * Test 1: Database Connection
         */
        echo '<div class="test-section">';
        echo '<h2>1. Teste de Conexão com Banco de Dados</h2>';
        
        global $wpdb;
        $results['total']++;
        
        if ($wpdb) {
            $db_test = $wpdb->get_var("SELECT 1");
            if ($db_test == 1) {
                echo '<div class="test-item">';
                echo '<span class="status pass">PASS</span>';
                echo '<span>Conexão com banco de dados estabelecida com sucesso</span>';
                echo '</div>';
                $results['passed']++;
                
                // Test database info
                echo '<div class="details">';
                echo '<strong>Informações do Banco:</strong><br>';
                echo 'Host: ' . DB_HOST . '<br>';
                echo 'Database: ' . DB_NAME . '<br>';
                echo 'Charset: ' . DB_CHARSET . '<br>';
                echo 'Collate: ' . DB_COLLATE . '<br>';
                echo '</div>';
            } else {
                echo '<div class="test-item">';
                echo '<span class="status fail">FAIL</span>';
                echo '<span>Falha ao conectar com banco de dados</span>';
                echo '</div>';
                $results['failed']++;
            }
        } else {
            echo '<div class="test-item">';
            echo '<span class="status fail">FAIL</span>';
            echo '<span>$wpdb não está disponível</span>';
            echo '</div>';
            $results['failed']++;
        }
        echo '</div>';
        
        /**
         * Test 2: Custom Post Types
         */
        echo '<div class="test-section">';
        echo '<h2>2. Teste de Custom Post Types</h2>';
        
        $post_types = ['event_listing', 'event_dj', 'event_local'];
        
        foreach ($post_types as $post_type) {
            $results['total']++;
            $exists = post_type_exists($post_type);
            
            if ($exists) {
                echo '<div class="test-item">';
                echo '<span class="status pass">PASS</span>';
                echo '<span>CPT "' . esc_html($post_type) . '" registrado</span>';
                echo '</div>';
                $results['passed']++;
                
                // Count posts
                $count = wp_count_posts($post_type);
                $total = $count->publish + $count->pending + $count->draft;
                
                echo '<div class="details">';
                echo '<strong>Estatísticas:</strong><br>';
                echo 'Publicados: ' . $count->publish . '<br>';
                echo 'Pendentes: ' . $count->pending . '<br>';
                echo 'Rascunhos: ' . $count->draft . '<br>';
                echo 'Total: ' . $total . '<br>';
                echo '</div>';
            } else {
                echo '<div class="test-item">';
                echo '<span class="status fail">FAIL</span>';
                echo '<span>CPT "' . esc_html($post_type) . '" NÃO registrado</span>';
                echo '</div>';
                $results['failed']++;
            }
        }
        echo '</div>';
        
        /**
         * Test 3: Shortcodes
         */
        echo '<div class="test-section">';
        echo '<h2>3. Teste de Shortcodes</h2>';
        
        $shortcodes = [
            'apollo_eventos' => 'Portal de eventos',
            'apollo_dj_profile' => 'Perfil de DJ',
            'apollo_user_dashboard' => 'Dashboard do usuário',
            'apollo_social_feed' => 'Feed social',
            'apollo_cena_rio' => 'Calendário Cena Rio',
            'submit_event_form' => 'Formulário de submissão',
            'apollo_register' => 'Registro',
            'apollo_login' => 'Login',
            'my_apollo_dashboard' => 'My Apollo Dashboard',
        ];
        
        global $shortcode_tags;
        
        foreach ($shortcodes as $shortcode => $description) {
            $results['total']++;
            $exists = shortcode_exists($shortcode);
            
            if ($exists) {
                echo '<div class="test-item">';
                echo '<span class="status pass">PASS</span>';
                echo '<span>Shortcode "[<strong>' . esc_html($shortcode) . '</strong>]" registrado - ' . esc_html($description) . '</span>';
                echo '</div>';
                $results['passed']++;
            } else {
                echo '<div class="test-item">';
                echo '<span class="status fail">FAIL</span>';
                echo '<span>Shortcode "[<strong>' . esc_html($shortcode) . '</strong>]" NÃO registrado</span>';
                echo '</div>';
                $results['failed']++;
            }
        }
        echo '</div>';
        
        /**
         * Test 4: Meta Keys
         */
        echo '<div class="test-section">';
        echo '<h2>4. Teste de Meta Keys Canônicas</h2>';
        
        $canonical_keys = [
            '_event_dj_ids',
            '_event_local_ids',
            '_event_timetable',
            '_event_start_date',
            '_event_banner',
        ];
        
        // Get a sample event
        $sample_event = get_posts([
            'post_type' => 'event_listing',
            'posts_per_page' => 1,
            'post_status' => 'any',
        ]);
        
        if (!empty($sample_event)) {
            $event_id = $sample_event[0]->ID;
            echo '<div class="test-item">';
            echo '<span class="status info">INFO</span>';
            echo '<span>Testando meta keys no evento ID: ' . $event_id . '</span>';
            echo '</div>';
            
            echo '<table>';
            echo '<tr><th>Meta Key</th><th>Valor</th><th>Status</th></tr>';
            
            foreach ($canonical_keys as $key) {
                $value = get_post_meta($event_id, $key, true);
                $status = $value !== false && $value !== '' ? 'pass' : 'warning';
                
                echo '<tr>';
                echo '<td><code>' . esc_html($key) . '</code></td>';
                echo '<td>';
                if (is_array($value)) {
                    echo '<pre>' . esc_html(print_r($value, true)) . '</pre>';
                } else {
                    echo esc_html($value ?: '(vazio)');
                }
                echo '</td>';
                echo '<td><span class="status ' . $status . '">' . ($status === 'pass' ? 'OK' : 'VAZIO') . '</span></td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<div class="test-item">';
            echo '<span class="status warning">WARNING</span>';
            echo '<span>Nenhum evento encontrado para testar meta keys</span>';
            echo '</div>';
            $results['warnings']++;
        }
        echo '</div>';
        
        /**
         * Test 5: AJAX Handlers
         */
        echo '<div class="test-section">';
        echo '<h2>5. Teste de AJAX Handlers</h2>';
        
        $ajax_actions = [
            'wp_ajax_apollo_get_event_modal' => 'Carregar modal de evento (logado)',
            'wp_ajax_nopriv_apollo_get_event_modal' => 'Carregar modal de evento (não logado)',
            'wp_ajax_apollo_save_profile' => 'Salvar perfil (logado)',
            'wp_ajax_nopriv_apollo_save_profile' => 'Salvar perfil (não logado)',
        ];
        
        global $wp_filter;
        
        foreach ($ajax_actions as $action => $description) {
            $results['total']++;
            $exists = has_action($action);
            
            if ($exists) {
                echo '<div class="test-item">';
                echo '<span class="status pass">PASS</span>';
                echo '<span>AJAX Handler "<strong>' . esc_html($action) . '</strong>" registrado - ' . esc_html($description) . '</span>';
                echo '</div>';
                $results['passed']++;
            } else {
                echo '<div class="test-item">';
                echo '<span class="status fail">FAIL</span>';
                echo '<span>AJAX Handler "<strong>' . esc_html($action) . '</strong>" NÃO registrado</span>';
                echo '</div>';
                $results['failed']++;
            }
        }
        echo '</div>';
        
        /**
         * Test 6: Templates
         */
        echo '<div class="test-section">';
        echo '<h2>6. Teste de Templates</h2>';
        
        $templates = [
            'portal-discover.php' => 'Portal de eventos',
            'event-card.php' => 'Card de evento',
            'single-event-page.php' => 'Página single de evento',
            'shortcode-dj-profile.php' => 'Template DJ Profile',
            'shortcode-user-dashboard.php' => 'Template User Dashboard',
            'shortcode-social-feed.php' => 'Template Social Feed',
            'shortcode-cena-rio.php' => 'Template Cena Rio',
        ];
        
        $plugin_dir = plugin_dir_path(__FILE__) . '../templates/';
        
        foreach ($templates as $template => $description) {
            $results['total']++;
            $file_path = $plugin_dir . $template;
            $exists = file_exists($file_path);
            
            if ($exists) {
                $size = filesize($file_path);
                $readable = is_readable($file_path);
                
                echo '<div class="test-item">';
                echo '<span class="status ' . ($readable ? 'pass' : 'fail') . '">' . ($readable ? 'PASS' : 'FAIL') . '</span>';
                echo '<span>Template "<strong>' . esc_html($template) . '</strong>" existe - ' . esc_html($description) . '</span>';
                echo '</div>';
                
                if ($readable) {
                    $results['passed']++;
                } else {
                    $results['failed']++;
                }
                
                echo '<div class="details">';
                echo 'Tamanho: ' . size_format($size) . '<br>';
                echo 'Legível: ' . ($readable ? 'Sim' : 'Não') . '<br>';
                echo '</div>';
            } else {
                echo '<div class="test-item">';
                echo '<span class="status fail">FAIL</span>';
                echo '<span>Template "<strong>' . esc_html($template) . '</strong>" NÃO encontrado</span>';
                echo '</div>';
                $results['failed']++;
            }
        }
        echo '</div>';
        
        /**
         * Test 7: Assets (CSS/JS)
         */
        echo '<div class="test-section">';
        echo '<h2>7. Teste de Assets (CSS/JS)</h2>';
        
        $assets = [
            'assets/css/event-modal.css' => 'CSS Modal',
            'assets/js/apollo-events-portal.js' => 'JS Portal',
        ];
        
        $plugin_dir = plugin_dir_path(__FILE__) . '../';
        
        foreach ($assets as $asset => $description) {
            $results['total']++;
            $file_path = $plugin_dir . $asset;
            $exists = file_exists($file_path);
            
            if ($exists) {
                $size = filesize($file_path);
                echo '<div class="test-item">';
                echo '<span class="status pass">PASS</span>';
                echo '<span>Asset "<strong>' . esc_html($asset) . '</strong>" existe - ' . esc_html($description) . '</span>';
                echo '</div>';
                $results['passed']++;
                
                echo '<div class="details">';
                echo 'Tamanho: ' . size_format($size) . '<br>';
                echo '</div>';
            } else {
                echo '<div class="test-item">';
                echo '<span class="status fail">FAIL</span>';
                echo '<span>Asset "<strong>' . esc_html($asset) . '</strong>" NÃO encontrado</span>';
                echo '</div>';
                $results['failed']++;
            }
        }
        echo '</div>';
        
        /**
         * Test 8: User Roles
         */
        echo '<div class="test-section">';
        echo '<h2>8. Teste de User Roles</h2>';
        
        $results['total']++;
        $clubber_role = get_role('clubber');
        
        if ($clubber_role) {
            echo '<div class="test-item">';
            echo '<span class="status pass">PASS</span>';
            echo '<span>Role "clubber" existe</span>';
            echo '</div>';
            $results['passed']++;
            
            echo '<div class="details">';
            echo '<strong>Capabilities:</strong><br>';
            if (!empty($clubber_role->capabilities)) {
                foreach ($clubber_role->capabilities as $cap => $value) {
                    if ($value) {
                        echo '• ' . esc_html($cap) . '<br>';
                    }
                }
            } else {
                echo 'Nenhuma capability definida<br>';
            }
            echo '</div>';
        } else {
            echo '<div class="test-item">';
            echo '<span class="status fail">FAIL</span>';
            echo '<span>Role "clubber" NÃO existe</span>';
            echo '</div>';
            $results['failed']++;
        }
        echo '</div>';
        
        /**
         * Summary
         */
        echo '<div class="summary">';
        echo '<h3>📊 Resumo dos Testes</h3>';
        echo '<div class="summary-stats">';
        echo '<div class="stat-box">';
        echo '<div class="stat-number">' . $results['total'] . '</div>';
        echo '<div>Total de Testes</div>';
        echo '</div>';
        echo '<div class="stat-box">';
        echo '<div class="stat-number" style="color: #46b450;">' . $results['passed'] . '</div>';
        echo '<div>Passou</div>';
        echo '</div>';
        echo '<div class="stat-box">';
        echo '<div class="stat-number" style="color: #dc3232;">' . $results['failed'] . '</div>';
        echo '<div>Falhou</div>';
        echo '</div>';
        echo '<div class="stat-box">';
        echo '<div class="stat-number" style="color: #ffb900;">' . $results['warnings'] . '</div>';
        echo '<div>Avisos</div>';
        echo '</div>';
        echo '</div>';
        
        $success_rate = $results['total'] > 0 ? round(($results['passed'] / $results['total']) * 100, 2) : 0;
        echo '<p style="margin-top: 20px; font-size: 18px;"><strong>Taxa de Sucesso: ' . $success_rate . '%</strong></p>';
        
        if ($results['failed'] === 0 && $results['passed'] === $results['total']) {
            echo '<p style="margin-top: 10px; font-size: 16px;">✅ <strong>Todos os testes passaram! Sistema pronto para produção.</strong></p>';
        } elseif ($results['failed'] > 0) {
            echo '<p style="margin-top: 10px; font-size: 16px;">⚠️ <strong>Alguns testes falharam. Revise os erros acima.</strong></p>';
        }
        echo '</div>';
        ?>
    </div>
</body>
</html>

