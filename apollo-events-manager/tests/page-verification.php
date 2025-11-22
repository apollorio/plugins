<?php
/**
 * Apollo Events Manager - Page Verification Test
 * 
 * Verifica todas as páginas criadas, shortcodes e CPTs
 */

// Prevent direct access - Load WordPress
if (!defined('ABSPATH')) {
    // Enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    
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
    
    foreach ($wp_load_paths as $path) {
        $real_path = realpath($path);
        if ($real_path && file_exists($real_path)) {
            try {
                require_once $real_path;
                if (defined('ABSPATH')) {
                    $wp_loaded = true;
                    break;
                }
            } catch (Exception $e) {
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
                        break;
                    }
                } catch (Exception $e) {
                    $last_error = $e->getMessage();
                }
            }
            $current_dir = dirname($current_dir);
            if ($current_dir === dirname($current_dir)) {
                break; // Reached filesystem root
            }
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
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apollo Events Manager - Page Verification</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1a1a1a;
            margin-bottom: 20px;
            border-bottom: 3px solid #0073aa;
            padding-bottom: 10px;
        }
        .section {
            margin-bottom: 40px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
            border-left: 4px solid #0073aa;
        }
        .page-item {
            margin: 15px 0;
            padding: 15px;
            background: white;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .page-item h3 {
            color: #0073aa;
            margin-bottom: 10px;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            margin-right: 10px;
        }
        .status.pass { background: #46b450; color: white; }
        .status.fail { background: #dc3232; color: white; }
        .status.warning { background: #ffb900; color: white; }
        .preview {
            margin-top: 10px;
            padding: 10px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 3px;
            max-height: 300px;
            overflow-y: auto;
        }
        iframe {
            width: 100%;
            height: 400px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        .shortcode-test {
            margin-top: 10px;
            padding: 15px;
            background: #e8f4f8;
            border-radius: 3px;
        }
        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📄 Apollo Events Manager - Page Verification</h1>
        <p><strong>Data:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
        <p><strong>Site URL:</strong> <?php echo home_url(); ?></p>
        
        <?php
        /**
         * Test 1: Shortcodes
         */
        echo '<div class="section">';
        echo '<h2>1. Shortcodes</h2>';
        
        $shortcodes = [
            'apollo_eventos' => [
                'description' => 'Portal de eventos',
                'test_url' => home_url('/eventos/'),
            ],
            'apollo_dj_profile' => [
                'description' => 'Perfil de DJ',
                'test_url' => null,
            ],
            'apollo_user_dashboard' => [
                'description' => 'Dashboard do usuário',
                'test_url' => home_url('/my-apollo/'),
            ],
            'apollo_social_feed' => [
                'description' => 'Feed social',
                'test_url' => null,
            ],
            'apollo_cena_rio' => [
                'description' => 'Calendário Cena Rio',
                'test_url' => null,
            ],
            'submit_event_form' => [
                'description' => 'Formulário de submissão',
                'test_url' => home_url('/submit-event/'),
            ],
            'apollo_register' => [
                'description' => 'Registro',
                'test_url' => home_url('/register/'),
            ],
            'apollo_login' => [
                'description' => 'Login',
                'test_url' => home_url('/login/'),
            ],
            'my_apollo_dashboard' => [
                'description' => 'My Apollo Dashboard',
                'test_url' => home_url('/my-apollo/'),
            ],
        ];
        
        foreach ($shortcodes as $shortcode => $info) {
            echo '<div class="page-item">';
            echo '<h3>[' . esc_html($shortcode) . ']</h3>';
            echo '<p>' . esc_html($info['description']) . '</p>';
            
            // Check if shortcode exists
            if (shortcode_exists($shortcode)) {
                echo '<p><span class="status pass">REGISTRADO</span>Shortcode está registrado</p>';
                
                // Test shortcode output
                echo '<div class="shortcode-test">';
                echo '<strong>Teste de Output:</strong><br>';
                ob_start();
                $output = do_shortcode('[' . $shortcode . ']');
                ob_end_clean();
                
                if (!empty($output)) {
                    echo '<span class="status pass">OK</span> Shortcode retorna conteúdo<br>';
                    echo '<div class="preview">';
                    echo '<strong>Preview (primeiros 500 caracteres):</strong><br>';
                    echo esc_html(substr(strip_tags($output), 0, 500)) . '...';
                    echo '</div>';
                } else {
                    echo '<span class="status warning">VAZIO</span> Shortcode não retorna conteúdo (pode ser normal se requer parâmetros)<br>';
                }
                echo '</div>';
                
                // Test URL if available
                if ($info['test_url']) {
                    echo '<p><strong>URL de Teste:</strong> <a href="' . esc_url($info['test_url']) . '" target="_blank">' . esc_html($info['test_url']) . '</a></p>';
                }
            } else {
                echo '<p><span class="status fail">NÃO REGISTRADO</span>Shortcode não está registrado</p>';
            }
            echo '</div>';
        }
        echo '</div>';
        
        /**
         * Test 2: Custom Post Types Pages
         */
        echo '<div class="section">';
        echo '<h2>2. Páginas de Custom Post Types</h2>';
        
        $cpts = ['event_listing', 'event_dj', 'event_local'];
        
        foreach ($cpts as $cpt) {
            echo '<div class="page-item">';
            echo '<h3>' . esc_html($cpt) . '</h3>';
            
            // Get sample posts
            $posts = get_posts([
                'post_type' => $cpt,
                'posts_per_page' => 5,
                'post_status' => 'publish',
            ]);
            
            if (!empty($posts)) {
                echo '<p><span class="status pass">OK</span> ' . count($posts) . ' post(s) encontrado(s)</p>';
                echo '<ul>';
                foreach ($posts as $post) {
                    $permalink = get_permalink($post->ID);
                    echo '<li><a href="' . esc_url($permalink) . '" target="_blank">' . esc_html($post->post_title) . '</a> (ID: ' . $post->ID . ')</li>';
                }
                echo '</ul>';
            } else {
                echo '<p><span class="status warning">VAZIO</span> Nenhum post publicado encontrado</p>';
            }
            echo '</div>';
        }
        echo '</div>';
        
        /**
         * Test 3: Archive Pages
         */
        echo '<div class="section">';
        echo '<h2>3. Páginas de Arquivo</h2>';
        
        $archive_urls = [
            'event_listing' => home_url('/eventos/'),
            'event_dj' => home_url('/djs/'),
            'event_local' => home_url('/locais/'),
        ];
        
        foreach ($archive_urls as $cpt => $url) {
            echo '<div class="page-item">';
            echo '<h3>Arquivo: ' . esc_html($cpt) . '</h3>';
            echo '<p><strong>URL:</strong> <a href="' . esc_url($url) . '" target="_blank">' . esc_html($url) . '</a></p>';
            
            // Check if archive page exists
            $archive_page = get_page_by_path(str_replace(home_url('/'), '', $url));
            if ($archive_page || $cpt === 'event_listing') {
                echo '<p><span class="status pass">OK</span> Página de arquivo disponível</p>';
            } else {
                echo '<p><span class="status warning">AVISO</span> Página pode não estar configurada</p>';
            }
            echo '</div>';
        }
        echo '</div>';
        
        /**
         * Test 4: Template Files
         */
        echo '<div class="section">';
        echo '<h2>4. Arquivos de Template</h2>';
        
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
            $file_path = $plugin_dir . $template;
            echo '<div class="page-item">';
            echo '<h3>' . esc_html($template) . '</h3>';
            echo '<p>' . esc_html($description) . '</p>';
            
            if (file_exists($file_path)) {
                echo '<p><span class="status pass">EXISTE</span> Arquivo encontrado</p>';
                echo '<p><strong>Caminho:</strong> <code>' . esc_html($file_path) . '</code></p>';
                echo '<p><strong>Tamanho:</strong> ' . size_format(filesize($file_path)) . '</p>';
            } else {
                echo '<p><span class="status fail">NÃO ENCONTRADO</span> Arquivo não existe</p>';
            }
            echo '</div>';
        }
        echo '</div>';
        ?>
    </div>
</body>
</html>

