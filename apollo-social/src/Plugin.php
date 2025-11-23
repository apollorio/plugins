<?php
namespace Apollo;

use Apollo\Modules\Core\CoreServiceProvider;
use Apollo\Infrastructure\Http\Routes;
use Apollo\Core\RoleManager;
use Apollo\Core\PWADetector;

/**
 * Main Plugin class
 *
 * Handles plugin lifecycle and service providers registration.
 */
class Plugin
{
    private $providers = [];
    private $initialized = false;

    /**
     * Constructor - MANDATORY for all Apollo plugins
     * Automatically initializes the plugin
     * Builds all CPT hooks, preparations, and Canvas pages
     */
    public function __construct()
    {
        // Prevent double initialization
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;
        
        // Register Role Manager (rename WordPress roles)
        $role_manager = new \Apollo\Core\RoleManager();
        $role_manager->register();
        
        // Bootstrap plugin automatically
        $this->bootstrap();
        
        // Initialize Canvas pages
        $this->initializeCanvasPages();
    }

    /**
     * Initialize Canvas pages on plugin activation/construction
     * Creates all Apollo pages independently from theme
     */
    private function initializeCanvasPages()
    {
        // Hook into init to ensure WordPress is ready
        add_action('init', [$this, 'createCanvasPages'], 20);
    }

    /**
     * Create Canvas pages
     * FASE 1: Inclui páginas faltantes (Documentos e Enviar Evento)
     * Melhora idempotência verificando páginas individualmente
     */
    public function createCanvasPages()
    {
        // Bug fix: Early return para evitar execução desnecessária em cada request
        $pages_created = get_option('apollo_social_canvas_pages_created', false);
        $pages_version = get_option('apollo_social_canvas_pages_version', '1.0');
        $current_version = '2.0'; // Incrementar quando adicionar novas páginas
        
        // Se páginas já foram criadas e versão está atualizada, não executar
        if ($pages_created && $pages_version === $current_version) {
            return;
        }
        
        // FASE 1: Verificar versão da opção para upgrades futuros
        
        // Pages to create
        $pages = [
            'feed' => [
                'title' => 'Feed Social',
                'slug' => 'feed',
                'template' => 'feed/feed.php',
            ],
            'chat' => [
                'title' => 'Chat',
                'slug' => 'chat',
                'template' => 'chat/chat-list.php',
            ],
            'painel' => [
                'title' => 'Painel',
                'slug' => 'painel',
                'template' => 'users/dashboard.php',
            ],
            'cena' => [
                'title' => 'Cena::rio',
                'slug' => 'cena',
                'template' => 'cena/cena.php',
            ],
            'cena-rio' => [
                'title' => 'Cena::rio',
                'slug' => 'cena-rio',
                'template' => 'cena/cena.php',
            ],
            // FASE 1: Páginas faltantes
            'documentos' => [
                'title' => 'Documentos',
                'slug' => 'documentos',
                'template' => 'documents/documents-page.php',
            ],
            'enviar' => [
                'title' => 'Enviar Conteúdo',
                'slug' => 'enviar',
                'template' => 'users/submit-content.php',
                'content' => '[apollo_event_submit]', // Shortcode para formulário de eventos
            ],
        ];

        $created_count = 0;
        $existing_count = 0;

        // FASE 1: Verificar cada página individualmente (idempotência melhorada)
        foreach ($pages as $key => $page_data) {
            // Check if page exists by slug
            $existing = get_page_by_path($page_data['slug']);
            
            if ($existing) {
                // Página existe - verificar se tem metadados Canvas
                $is_canvas = get_post_meta($existing->ID, '_apollo_canvas_page', true);
                if (!$is_canvas) {
                    // Atualizar página existente para Canvas
                    update_post_meta($existing->ID, '_apollo_canvas_page', true);
                    if (isset($page_data['template'])) {
                        update_post_meta($existing->ID, '_apollo_canvas_template', $page_data['template']);
                    }
                }
                $existing_count++;
            } else {
                // Criar nova página
                $page_content = isset($page_data['content']) ? $page_data['content'] : '<!-- Apollo Canvas Page -->';
                
                $page_id = wp_insert_post([
                    'post_title' => $page_data['title'],
                    'post_name' => $page_data['slug'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_content' => $page_content,
                ]);

                if ($page_id && !is_wp_error($page_id)) {
                    // Mark as Apollo Canvas page
                    update_post_meta($page_id, '_apollo_canvas_page', true);
                    if (isset($page_data['template'])) {
                        update_post_meta($page_id, '_apollo_canvas_template', $page_data['template']);
                    }
                    $created_count++;
                }
            }
        }

        // FASE 1: Atualizar versão apenas se houve mudanças ou upgrade necessário
        if ($pages_version !== $current_version || $created_count > 0) {
            update_option('apollo_social_canvas_pages_version', $current_version);
            update_option('apollo_social_canvas_pages_created', true);
            
            // Log para debug
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    'Apollo Social: Canvas pages updated (v%s). Created: %d, Existing: %d',
                    $current_version,
                    $created_count,
                    $existing_count
                ));
            }
        }
    }

    /**
     * Bootstrap the plugin (register providers, hooks)
     */
    public function bootstrap()
    {
        // Register service providers
        $this->registerProviders();
        
        // Initialize core functionality
        $this->initializeCore();
    }

    /**
     * Register all service providers
     */
    private function registerProviders()
    {
        // Load helper functions
        if (!function_exists('config')) {
            require_once APOLLO_SOCIAL_PLUGIN_DIR . 'src/helpers.php';
        }
        
        $this->providers = [
            new CoreServiceProvider(),
            new \Apollo\Modules\Auth\AuthServiceProvider(),
            new \Apollo\Modules\Registration\RegistrationServiceProvider(),
            new \Apollo\Modules\Builder\BuilderServiceProvider(),
            new \Apollo\Modules\Shortcodes\ShortcodeServiceProvider(),
            new \Apollo\Modules\Pwa\PwaServiceProvider(),
            new \Apollo\Infrastructure\Providers\AnalyticsServiceProvider(),
        ];
        
        // FASE 1: Carregar DocumentsRoutes se módulo de documentos existir
        $documents_routes_file = APOLLO_SOCIAL_PLUGIN_DIR . 'src/Modules/Documents/DocumentsRoutes.php';
        if (file_exists($documents_routes_file)) {
            require_once $documents_routes_file;
            if (class_exists('\Apollo\Modules\Documents\DocumentsRoutes')) {
                new \Apollo\Modules\Documents\DocumentsRoutes();
            }
        }

        // Register Widgets API endpoints
        $widgets_endpoints = new \Apollo\API\Endpoints\WidgetsEndpoints();
        $widgets_endpoints->register();

        // FASE 2: Register Likes endpoint
        $likes_endpoint = new \Apollo\API\Endpoints\LikesEndpoint();
        $likes_endpoint->register();

        // FASE 2: Register Comments endpoint
        $comments_endpoint = new \Apollo\API\Endpoints\CommentsEndpoint();
        $comments_endpoint->register();

        // P0-6: Register Favorites endpoint
        $favorites_endpoint = new \Apollo\API\Endpoints\FavoritesEndpoint();
        $favorites_endpoint->register();

        // P0-5: Register Feed endpoint
        $feed_endpoint = new \Apollo\API\Endpoints\FeedEndpoint();
        $feed_endpoint->register();

        // P0-7: Register DJ/Local endpoints
        $dj_local_endpoint = new \Apollo\API\Endpoints\DJLocalEndpoint();
        $dj_local_endpoint->register();

        // P0-7: Register Groups endpoint
        $groups_endpoint = new \Apollo\API\Endpoints\GroupsEndpoint();
        $groups_endpoint->register();

        // P0-10: Register CENA RIO Event endpoint
        $cena_rio_endpoint = new \Apollo\API\Endpoints\CenaRioEventEndpoint();
        $cena_rio_endpoint->register();

        // Register User Page Auto-Create hook
        $user_page_auto_create = new \Apollo\Hooks\UserPageAutoCreate();
        $user_page_auto_create->register();

        foreach ($this->providers as $provider) {
            $provider->register();
        }

        foreach ($this->providers as $provider) {
            if (method_exists($provider, 'boot')) {
                $provider->boot();
            }
        }
    }

    /**
     * Initialize core functionality
     */
    private function initializeCore()
    {
        // Register routes
        add_action('init', [$this, 'registerRoutes']);
        
        // Handle plugin requests
        add_action('template_redirect', [$this, 'handlePluginRequests']);
    }

    /**
     * Register plugin routes
     */
    public function registerRoutes()
    {
        $routes = new Routes();
        $routes->register();
    }

    /**
     * P0-4: Handle plugin requests and Canvas Mode with template_redirect
     * This ensures Canvas Mode pages are rendered before theme template selection
     */
    public function handlePluginRequests()
    {
        // Check if this is an Apollo Canvas route
        $routes = new Routes();
        $current_route = $routes->getCurrentRoute();
        
        // If route is already matched and is Canvas, prevent theme template
        if ($current_route && !empty($current_route['canvas'])) {
            // P0-4: Force Canvas Mode - prevent theme template loading
            add_filter('template_include', function($template) {
                // Return empty string to prevent theme template
                // CanvasBuilder will handle full rendering via wp_die()
                return '';
            }, 999);
        }
        
        // Handle the route (will call wp_die() if Canvas route matched)
        $routes->handleRequest();
    }
}