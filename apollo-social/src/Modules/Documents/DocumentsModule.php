<?php
declare(strict_types=1);
/**
 * Apollo Documents Module
 * 
 * Módulo principal que inicializa todas as funcionalidades de documentos e assinaturas.
 * 
 * Funcionalidades:
 * - Bibliotecas de documentos (Apollo, Cena-rio, Private)
 * - Editor de documentos com Quill.js
 * - Geração de PDF
 * - Assinatura digital ICP-Brasil
 * - Assinatura eletrônica (canvas)
 * - Verificação e auditoria
 * 
 * @package Apollo\Modules\Documents
 * @since 2.0.0
 */

namespace Apollo\Modules\Documents;

use Apollo\Modules\Signatures\AuditLog;

/**
 * Documents Module Initializer
 */
class DocumentsModule
{
    /** @var string Module version */
    private const VERSION = '2.0.0';
    
    /** @var bool Initialized flag */
    private static bool $initialized = false;
    
    /**
     * Initialize the module
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }
        
        self::$initialized = true;
        
        // Register activation hook
        // Use global constant with backslash prefix to access from namespace
        if (defined('\APOLLO_SOCIAL_PLUGIN_FILE')) {
            $plugin_file = \APOLLO_SOCIAL_PLUGIN_FILE;
        } else {
            // Fallback: calculate path to main plugin file
            $plugin_file = dirname(dirname(dirname(__DIR__))) . '/apollo-social.php';
        }
        register_activation_hook($plugin_file, [self::class, 'activate']);
        
        // Initialize on plugins loaded
        add_action('plugins_loaded', [self::class, 'setup']);
        
        // Register REST endpoints
        add_action('rest_api_init', [self::class, 'registerEndpoints']);
        
        // Register shortcodes
        add_action('init', [self::class, 'registerShortcodes']);
        
        // Register Canvas routes
        add_action('init', [self::class, 'registerRoutes']);
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', [self::class, 'registerAdminMenu']);
        }
    }
    
    /**
     * Activate module (create tables)
     */
    public static function activate(): void
    {
        // Create document libraries tables
        $libraries = new DocumentLibraries();
        $libraries->createTables();
        
        // Create documents manager tables
        $manager = new DocumentsManager();
        $manager->createTables();
        
        // Create audit tables
        $audit = new AuditLog();
        $audit->createTables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set version
        update_option('apollo_documents_version', self::VERSION);
    }
    
    /**
     * Setup module
     */
    public static function setup(): void
    {
        // Check if tables need update
        $current_version = get_option('apollo_documents_version', '0.0.0');
        
        if (version_compare($current_version, self::VERSION, '<')) {
            self::activate();
        }
    }
    
    /**
     * Register REST endpoints
     */
    public static function registerEndpoints(): void
    {
        $endpoints = new SignatureEndpoints();
        $endpoints->registerRoutes();
    }
    
    /**
     * Register shortcodes
     */
    public static function registerShortcodes(): void
    {
        // Document editor shortcode
        add_shortcode('apollo_document_editor', [self::class, 'renderDocumentEditor']);
        
        // Document list shortcode
        add_shortcode('apollo_documents', [self::class, 'renderDocumentList']);
        
        // Signature page shortcode
        add_shortcode('apollo_sign_document', [self::class, 'renderSignaturePage']);
        
        // Verification form shortcode
        add_shortcode('apollo_verify_document', [self::class, 'renderVerificationForm']);
    }
    
    /**
     * Register custom routes
     */
    public static function registerRoutes(): void
    {
        // Add rewrite rules for document routes
        add_rewrite_rule('^doc/new/?$', 'index.php?apollo_doc=new', 'top');
        add_rewrite_rule('^doc/([a-zA-Z0-9]+)/?$', 'index.php?apollo_doc=$matches[1]', 'top');
        add_rewrite_rule('^pla/new/?$', 'index.php?apollo_pla=new', 'top');
        add_rewrite_rule('^pla/([a-zA-Z0-9]+)/?$', 'index.php?apollo_pla=$matches[1]', 'top');
        add_rewrite_rule('^sign/([a-zA-Z0-9-]+)/?$', 'index.php?apollo_sign=$matches[1]', 'top');
        add_rewrite_rule('^verificar/([A-Z0-9-]+)/?$', 'index.php?apollo_verify=$matches[1]', 'top');
        
        // Register query vars
        add_filter('query_vars', function($vars) {
            $vars[] = 'apollo_doc';
            $vars[] = 'apollo_pla';
            $vars[] = 'apollo_sign';
            $vars[] = 'apollo_verify';
            return $vars;
        });
        
        // Handle template redirect
        add_action('template_redirect', [self::class, 'handleCanvasRoutes']);
    }
    
    /**
     * Handle Canvas mode routes
     */
    public static function handleCanvasRoutes(): void
    {
        global $wp_query;
        
        // Document editor
        $doc_id = get_query_var('apollo_doc');
        if ($doc_id) {
            if (!is_user_logged_in()) {
                auth_redirect();
            }
            self::loadTemplate('editor', ['file_id' => $doc_id, 'type' => 'document']);
            exit;
        }
        
        // Spreadsheet editor
        $pla_id = get_query_var('apollo_pla');
        if ($pla_id) {
            if (!is_user_logged_in()) {
                auth_redirect();
            }
            self::loadTemplate('editor', ['file_id' => $pla_id, 'type' => 'spreadsheet']);
            exit;
        }
        
        // Signature page
        $sign_token = get_query_var('apollo_sign');
        if ($sign_token) {
            self::loadTemplate('sign', ['token' => $sign_token]);
            exit;
        }
        
        // Verification page
        $verify_code = get_query_var('apollo_verify');
        if ($verify_code) {
            self::loadTemplate('verify', ['code' => $verify_code]);
            exit;
        }
    }
    
    /**
     * Load template
     */
    private static function loadTemplate(string $template, array $args = []): void
    {
        // Extract args for template
        extract($args);
        
        $template_file = APOLLO_SOCIAL_PLUGIN_DIR . "templates/documents/{$template}.php";
        
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            wp_die('Template não encontrado: ' . esc_html($template), 'Erro', ['response' => 404]);
        }
    }
    
    /**
     * Register admin menu
     */
    public static function registerAdminMenu(): void
    {
        add_submenu_page(
            'apollo-social',
            'Documentos',
            'Documentos',
            'manage_options',
            'apollo-documents',
            [self::class, 'renderAdminPage']
        );
    }
    
    /**
     * Render admin page
     */
    public static function renderAdminPage(): void
    {
        $libraries = new DocumentLibraries();
        
        $apollo_stats = $libraries->getLibraryStats('apollo');
        $cenario_stats = $libraries->getLibraryStats('cenario');
        
        ?>
        <div class="wrap">
            <h1>Apollo Documents</h1>
            
            <div class="apollo-admin-cards" style="display: flex; gap: 20px; margin-top: 20px;">
                <!-- Apollo Library -->
                <div class="card" style="flex: 1; padding: 20px;">
                    <h2><span class="dashicons dashicons-building"></span> Biblioteca Apollo</h2>
                    <p>Documentos institucionais e templates oficiais</p>
                    <ul>
                        <li><strong><?php echo esc_html($apollo_stats['total']); ?></strong> documentos</li>
                        <li><strong><?php echo esc_html($apollo_stats['templates']); ?></strong> templates</li>
                        <li><strong><?php echo esc_html($apollo_stats['completed']); ?></strong> assinados</li>
                    </ul>
                </div>
                
                <!-- Cena-rio Library -->
                <div class="card" style="flex: 1; padding: 20px;">
                    <h2><span class="dashicons dashicons-groups"></span> Biblioteca Cena-rio</h2>
                    <p>Documentos comunitários e modelos compartilhados</p>
                    <ul>
                        <li><strong><?php echo esc_html($cenario_stats['total']); ?></strong> documentos</li>
                        <li><strong><?php echo esc_html($cenario_stats['templates']); ?></strong> templates</li>
                        <li><strong><?php echo esc_html($cenario_stats['signing']); ?></strong> em assinatura</li>
                    </ul>
                </div>
            </div>
            
            <div class="card" style="margin-top: 20px; padding: 20px;">
                <h2>Configurações</h2>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('apollo_documents_settings');
                    do_settings_sections('apollo_documents_settings');
                    ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Biblioteca PDF</th>
                            <td>
                                <?php
                                $generator = new PdfGenerator();
                                $available = $generator->getAvailableLibraries();
                                if (empty($available)) {
                                    echo '<span style="color: #dc3545;">⚠ Nenhuma biblioteca PDF disponível. Instale mPDF, TCPDF ou Dompdf.</span>';
                                } else {
                                    echo '<span style="color: #28a745;">✓ ' . implode(', ', $available) . '</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">OpenSSL</th>
                            <td>
                                <?php
                                if (extension_loaded('openssl')) {
                                    echo '<span style="color: #28a745;">✓ Disponível</span>';
                                } else {
                                    echo '<span style="color: #dc3545;">⚠ Não disponível</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
            
            <div class="card" style="margin-top: 20px; padding: 20px;">
                <h2>Endpoints da API</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Endpoint</th>
                            <th>Método</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>/wp-json/apollo-docs/v1/library/{type}</code></td>
                            <td>GET</td>
                            <td>Listar documentos de uma biblioteca</td>
                        </tr>
                        <tr>
                            <td><code>/wp-json/apollo-docs/v1/document</code></td>
                            <td>POST</td>
                            <td>Criar novo documento</td>
                        </tr>
                        <tr>
                            <td><code>/wp-json/apollo-docs/v1/document/{file_id}/finalize</code></td>
                            <td>POST</td>
                            <td>Finalizar documento (gerar PDF)</td>
                        </tr>
                        <tr>
                            <td><code>/wp-json/apollo-docs/v1/sign/certificate</code></td>
                            <td>POST</td>
                            <td>Assinar com certificado ICP-Brasil</td>
                        </tr>
                        <tr>
                            <td><code>/wp-json/apollo-docs/v1/sign/canvas</code></td>
                            <td>POST</td>
                            <td>Assinar com canvas (eletrônica)</td>
                        </tr>
                        <tr>
                            <td><code>/wp-json/apollo-docs/v1/verify/protocol/{code}</code></td>
                            <td>GET</td>
                            <td>Verificar documento por protocolo</td>
                        </tr>
                        <tr>
                            <td><code>/wp-json/apollo-docs/v1/verify/hash</code></td>
                            <td>POST</td>
                            <td>Verificar documento por hash</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    // ===== SHORTCODE RENDERERS =====
    
    /**
     * Render document editor shortcode
     */
    public static function renderDocumentEditor($atts): string
    {
        if (!is_user_logged_in()) {
            return '<p>Faça login para editar documentos.</p>';
        }
        
        $atts = shortcode_atts([
            'file_id' => '',
            'type' => 'document'
        ], $atts);
        
        ob_start();
        self::loadTemplate('editor-shortcode', $atts);
        return ob_get_clean() ?: '';
    }
    
    /**
     * Render document list shortcode
     */
    public static function renderDocumentList($atts): string
    {
        if (!is_user_logged_in()) {
            return '<p>Faça login para ver seus documentos.</p>';
        }
        
        $atts = shortcode_atts([
            'library' => 'private',
            'limit' => 10
        ], $atts);
        
        $libraries = new DocumentLibraries();
        $result = $libraries->getDocumentsByLibrary($atts['library'], null, [
            'per_page' => (int) $atts['limit']
        ]);
        
        ob_start();
        ?>
        <div class="apollo-documents-list">
            <?php if (empty($result['documents'])): ?>
                <p>Nenhum documento encontrado.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($result['documents'] as $doc): ?>
                        <li>
                            <a href="<?php echo esc_url(site_url('/doc/' . $doc['file_id'])); ?>">
                                <?php echo esc_html($doc['title']); ?>
                            </a>
                            <span class="status">(<?php echo esc_html($doc['status']); ?>)</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean() ?: '';
    }
    
    /**
     * Render signature page shortcode
     */
    public static function renderSignaturePage($atts): string
    {
        $atts = shortcode_atts([
            'token' => ''
        ], $atts);
        
        if (empty($atts['token'])) {
            return '<p>Token de assinatura não fornecido.</p>';
        }
        
        ob_start();
        self::loadTemplate('sign-shortcode', $atts);
        return ob_get_clean() ?: '';
    }
    
    /**
     * Render verification form shortcode
     */
    public static function renderVerificationForm($atts): string
    {
        ob_start();
        ?>
        <div class="apollo-verify-form">
            <h3>Verificar Documento</h3>
            <form id="apollo-verify-form">
                <div class="form-group">
                    <label for="protocol-code">Código do Protocolo:</label>
                    <input type="text" id="protocol-code" name="protocol" placeholder="APR-DOC-2025-XXXXX" />
                </div>
                <p>- ou -</p>
                <div class="form-group">
                    <label for="document-file">Upload do Documento:</label>
                    <input type="file" id="document-file" name="file" accept=".pdf" />
                </div>
                <button type="submit" class="button">Verificar</button>
            </form>
            <div id="verify-result" style="display: none;"></div>
        </div>
        <script>
        document.getElementById('apollo-verify-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const resultDiv = document.getElementById('verify-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'Verificando...';
            
            const protocol = document.getElementById('protocol-code').value;
            const file = document.getElementById('document-file').files[0];
            
            try {
                let response;
                if (protocol) {
                    response = await fetch('<?php echo esc_url(rest_url('apollo-docs/v1/verify/protocol/')); ?>' + protocol);
                } else if (file) {
                    const formData = new FormData();
                    formData.append('file', file);
                    response = await fetch('<?php echo esc_url(rest_url('apollo-docs/v1/verify/file')); ?>', {
                        method: 'POST',
                        body: formData
                    });
                } else {
                    resultDiv.innerHTML = '<p style="color: red;">Informe um protocolo ou faça upload do documento.</p>';
                    return;
                }
                
                const data = await response.json();
                
                if (data.valid) {
                    resultDiv.innerHTML = '<p style="color: green;">✓ Documento válido!</p>' +
                        '<p><strong>Título:</strong> ' + (data.document?.title || 'N/A') + '</p>' +
                        '<p><strong>Status:</strong> ' + (data.document?.status || 'N/A') + '</p>';
                } else {
                    resultDiv.innerHTML = '<p style="color: red;">✗ ' + (data.error || 'Documento não encontrado') + '</p>';
                }
            } catch (err) {
                resultDiv.innerHTML = '<p style="color: red;">Erro na verificação: ' + err.message + '</p>';
            }
        });
        </script>
        <?php
        return ob_get_clean() ?: '';
    }
}

