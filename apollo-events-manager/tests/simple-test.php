<?php
/**
 * Simple Test - Verifica apenas se WordPress e Plugin carregam
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Simple Test - Apollo Events Manager</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        .success { color: #46b450; }
        .error { color: #dc3232; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Simple Test - Apollo Events Manager</h1>
        
        <h2>1. Carregando WordPress...</h2>
        <?php
        $wp_loaded = false;
        $wp_load_paths = [
            __DIR__ . '/../../../../wp-load.php',
            __DIR__ . '/../../../wp-load.php',
        ];
        
        foreach ($wp_load_paths as $path) {
            if (file_exists($path)) {
                try {
                    require_once $path;
                    if (defined('ABSPATH')) {
                        $wp_loaded = true;
                        echo '<p class="success">✅ WordPress carregado de: <code>' . htmlspecialchars($path) . '</code></p>';
                        break;
                    }
                } catch (Exception $e) {
                    echo '<p class="error">❌ Erro ao carregar: ' . htmlspecialchars($e->getMessage()) . '</p>';
                } catch (Error $e) {
                    echo '<p class="error">❌ Erro fatal: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '<p>Arquivo: ' . htmlspecialchars($e->getFile()) . '</p>';
                    echo '<p>Linha: ' . $e->getLine() . '</p>';
                }
            }
        }
        
        if (!$wp_loaded) {
            echo '<p class="error">❌ WordPress não carregado</p>';
            die('</div></body></html>');
        }
        ?>
        
        <h2>2. Carregando Plugin...</h2>
        <?php
        $plugin_file = dirname(dirname(__FILE__)) . '/apollo-events-manager.php';
        echo '<p>Arquivo do plugin: <code>' . htmlspecialchars($plugin_file) . '</code></p>';
        echo '<p>Existe: ' . (file_exists($plugin_file) ? '✅ Sim' : '❌ Não') . '</p>';
        
        if (file_exists($plugin_file)) {
            try {
                require_once $plugin_file;
                echo '<p class="success">✅ Arquivo do plugin carregado</p>';
            } catch (Exception $e) {
                echo '<p class="error">❌ Erro ao carregar plugin: ' . htmlspecialchars($e->getMessage()) . '</p>';
                die('</div></body></html>');
            } catch (Error $e) {
                echo '<p class="error">❌ Erro fatal ao carregar plugin: ' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<p>Arquivo: ' . htmlspecialchars($e->getFile()) . '</p>';
                echo '<p>Linha: ' . $e->getLine() . '</p>';
                die('</div></body></html>');
            }
        } else {
            echo '<p class="error">❌ Arquivo do plugin não encontrado</p>';
            die('</div></body></html>');
        }
        ?>
        
        <h2>3. Verificando Classe do Plugin...</h2>
        <?php
        if (class_exists('Apollo_Events_Manager_Plugin')) {
            echo '<p class="success">✅ Classe Apollo_Events_Manager_Plugin existe</p>';
        } else {
            echo '<p class="error">❌ Classe Apollo_Events_Manager_Plugin NÃO existe</p>';
            die('</div></body></html>');
        }
        ?>
        
        <h2>4. Instanciando Plugin...</h2>
        <?php
        global $apollo_events_manager;
        try {
            if (!isset($apollo_events_manager) || !($apollo_events_manager instanceof Apollo_Events_Manager_Plugin)) {
                $apollo_events_manager = new Apollo_Events_Manager_Plugin();
            }
            echo '<p class="success">✅ Plugin instanciado</p>';
        } catch (Exception $e) {
            echo '<p class="error">❌ Erro ao instanciar: ' . htmlspecialchars($e->getMessage()) . '</p>';
            die('</div></body></html>');
        } catch (Error $e) {
            echo '<p class="error">❌ Erro fatal ao instanciar: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p>Arquivo: ' . htmlspecialchars($e->getFile()) . '</p>';
            echo '<p>Linha: ' . $e->getLine() . '</p>';
            die('</div></body></html>');
        }
        ?>
        
        <h2>5. Verificando Hooks...</h2>
        <?php
        // Ensure WordPress core objects are initialized
        global $wp_rewrite, $wp;
        
        if (!isset($wp_rewrite)) {
            require_once ABSPATH . WPINC . '/rewrite.php';
            $GLOBALS['wp_rewrite'] = new WP_Rewrite();
            echo '<p class="success">✅ WP_Rewrite inicializado</p>';
        } else {
            echo '<p>ℹ️ WP_Rewrite já existe</p>';
        }
        
        if (!isset($wp)) {
            require_once ABSPATH . WPINC . '/class-wp.php';
            $GLOBALS['wp'] = new WP();
            echo '<p class="success">✅ WP inicializado</p>';
        } else {
            echo '<p>ℹ️ WP já existe</p>';
        }
        
        if (!did_action('plugins_loaded')) {
            do_action('plugins_loaded');
            echo '<p class="success">✅ Hook plugins_loaded executado</p>';
        } else {
            echo '<p>ℹ️ Hook plugins_loaded já foi executado</p>';
        }
        
        if (!did_action('init')) {
            // Ensure rewrite is ready before init
            if (!isset($GLOBALS['wp_rewrite'])) {
                require_once ABSPATH . WPINC . '/rewrite.php';
                $GLOBALS['wp_rewrite'] = new WP_Rewrite();
            }
            do_action('init');
            echo '<p class="success">✅ Hook init executado</p>';
        } else {
            echo '<p>ℹ️ Hook init já foi executado</p>';
        }
        ?>
        
        <h2>6. Verificando CPTs...</h2>
        <?php
        $cpts = ['event_listing', 'event_dj', 'event_local'];
        foreach ($cpts as $cpt) {
            if (post_type_exists($cpt)) {
                echo '<p class="success">✅ CPT "' . $cpt . '" registrado</p>';
            } else {
                echo '<p class="error">❌ CPT "' . $cpt . '" NÃO registrado</p>';
            }
        }
        ?>
        
        <h2>7. Verificando Shortcodes...</h2>
        <?php
        $shortcodes = ['apollo_eventos', 'apollo_dj_profile', 'submit_event_form', 'apollo_register', 'apollo_login', 'my_apollo_dashboard'];
        foreach ($shortcodes as $shortcode) {
            if (shortcode_exists($shortcode)) {
                echo '<p class="success">✅ Shortcode "' . $shortcode . '" registrado</p>';
            } else {
                echo '<p class="error">❌ Shortcode "' . $shortcode . '" NÃO registrado</p>';
            }
        }
        ?>
        
        <h2>✅ Teste Completo!</h2>
        <p>Se você chegou até aqui, o plugin está carregando corretamente.</p>
    </div>
</body>
</html>

