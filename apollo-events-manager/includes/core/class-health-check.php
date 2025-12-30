<?php
/**
 * Health Check Module
 *
 * Plugin health and status verification.
 *
 * @package Apollo_Events_Manager
 * @subpackage Core
 * @since 2.0.0
 */

namespace Apollo\Events\Core;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Health_Check
 *
 * Performs health checks and reports plugin status.
 *
 * @since 2.0.0
 */
class Health_Check {

    /**
     * Singleton instance.
     *
     * @var Health_Check|null
     */
    private static ?Health_Check $instance = null;

    /**
     * Check results.
     *
     * @var array
     */
    private array $results = array();

    /**
     * Get singleton instance.
     *
     * @since 2.0.0
     * @return Health_Check
     */
    public static function get_instance(): Health_Check {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor.
     *
     * @since 2.0.0
     */
    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_filter( 'debug_information', array( $this, 'add_debug_info' ) );
        add_filter( 'site_status_tests', array( $this, 'add_site_health_tests' ) );
    }

    /**
     * Add admin menu.
     *
     * @since 2.0.0
     * @return void
     */
    public function add_menu(): void {
        add_submenu_page(
            'edit.php?post_type=event_listing',
            __( 'Saúde do Plugin', 'apollo-events' ),
            __( 'Saúde', 'apollo-events' ),
            'manage_options',
            'apollo-health',
            array( $this, 'render_page' )
        );
    }

    /**
     * Run all health checks.
     *
     * @since 2.0.0
     * @return array
     */
    public function run_checks(): array {
        $this->results = array();

        $this->check_php_version();
        $this->check_wp_version();
        $this->check_required_tables();
        $this->check_cpt_registration();
        $this->check_modules();
        $this->check_templates();
        $this->check_assets();
        $this->check_permissions();
        $this->check_cron_jobs();
        $this->check_dependencies();

        return $this->results;
    }

    /**
     * Check PHP version.
     *
     * @since 2.0.0
     * @return void
     */
    private function check_php_version(): void {
        $required = '8.0';
        $current  = PHP_VERSION;
        $status   = version_compare( $current, $required, '>=' );

        $this->results['php_version'] = array(
            'label'       => __( 'Versão do PHP', 'apollo-events' ),
            'status'      => $status ? 'good' : 'critical',
            'badge'       => $status ? 'success' : 'error',
            'description' => sprintf(
                /* translators: 1: Current version, 2: Required version */
                __( 'Versão atual: %1$s. Mínimo requerido: %2$s', 'apollo-events' ),
                $current,
                $required
            ),
        );
    }

    /**
     * Check WordPress version.
     *
     * @since 2.0.0
     * @return void
     */
    private function check_wp_version(): void {
        global $wp_version;

        $required = '6.0';
        $status   = version_compare( $wp_version, $required, '>=' );

        $this->results['wp_version'] = array(
            'label'       => __( 'Versão do WordPress', 'apollo-events' ),
            'status'      => $status ? 'good' : 'critical',
            'badge'       => $status ? 'success' : 'error',
            'description' => sprintf(
                /* translators: 1: Current version, 2: Required version */
                __( 'Versão atual: %1$s. Mínimo requerido: %2$s', 'apollo-events' ),
                $wp_version,
                $required
            ),
        );
    }

    /**
     * Check required database tables.
     *
     * @since 2.0.0
     * @return void
     */
    private function check_required_tables(): void {
        global $wpdb;

        // Check if standard WP tables exist (we don't create custom tables)
        $tables = array(
            $wpdb->posts,
            $wpdb->postmeta,
            $wpdb->options,
        );

        $missing = array();
        foreach ( $tables as $table ) {
            $result = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
            if ( $result !== $table ) {
                $missing[] = $table;
            }
        }

        $status = empty( $missing );

        $this->results['database'] = array(
            'label'       => __( 'Banco de Dados', 'apollo-events' ),
            'status'      => $status ? 'good' : 'critical',
            'badge'       => $status ? 'success' : 'error',
            'description' => $status
                ? __( 'Todas as tabelas necessárias existem.', 'apollo-events' )
                : sprintf( __( 'Tabelas ausentes: %s', 'apollo-events' ), implode( ', ', $missing ) ),
        );
    }

    /**
     * Check CPT registration.
     *
     * @since 2.0.0
     * @return void
     */
    private function check_cpt_registration(): void {
        $cpts = array( 'event_listing', 'event_dj', 'event_local' );
        $missing = array();

        foreach ( $cpts as $cpt ) {
            if ( ! post_type_exists( $cpt ) ) {
                $missing[] = $cpt;
            }
        }

        $status = empty( $missing );

        $this->results['cpts'] = array(
            'label'       => __( 'Custom Post Types', 'apollo-events' ),
            'status'      => $status ? 'good' : 'critical',
            'badge'       => $status ? 'success' : 'error',
            'description' => $status
                ? __( 'Todos os CPTs estão registrados.', 'apollo-events' )
                : sprintf( __( 'CPTs não registrados: %s', 'apollo-events' ), implode( ', ', $missing ) ),
        );
    }

    /**
     * Check modules.
     *
     * @since 2.0.0
     * @return void
     */
    private function check_modules(): void {
        $modules_dir = dirname( __DIR__ ) . '/modules';
        $modules     = array(
            'lists'         => 'class-lists-module.php',
            'interest'      => 'class-interest-module.php',
            'speakers'      => 'class-speakers-module.php',
            'photos'        => 'class-photos-module.php',
            'reviews'       => 'class-reviews-module.php',
            'tickets'       => 'class-tickets-module.php',
            'duplicate'     => 'class-duplicate-module.php',
            'tracking'      => 'class-tracking-module.php',
            'notifications' => 'class-notifications-module.php',
            'share'         => 'class-share-module.php',
            'qrcode'        => 'class-qrcode-module.php',
            'seo'           => 'class-seo-module.php',
            'import-export' => 'class-import-export-module.php',
            'blocks'        => 'class-blocks-module.php',
        );

        $found   = 0;
        $missing = array();

        foreach ( $modules as $folder => $file ) {
            $path = "{$modules_dir}/{$folder}/{$file}";
            if ( file_exists( $path ) ) {
                $found++;
            } else {
                $missing[] = $folder;
            }
        }

        $total  = count( $modules );
        $status = $found === $total;

        $this->results['modules'] = array(
            'label'       => __( 'Módulos', 'apollo-events' ),
            'status'      => $status ? 'good' : 'recommended',
            'badge'       => $status ? 'success' : 'warning',
            'description' => sprintf(
                /* translators: 1: Found modules, 2: Total modules */
                __( '%1$d de %2$d módulos encontrados.', 'apollo-events' ),
                $found,
                $total
            ) . ( ! empty( $missing ) ? ' ' . sprintf( __( 'Ausentes: %s', 'apollo-events' ), implode( ', ', $missing ) ) : '' ),
        );
    }

    /**
     * Check templates.
     *
     * @since 2.0.0
     * @return void
     */
    private function check_templates(): void {
        $templates_dir = dirname( dirname( __DIR__ ) ) . '/templates';
        $has_templates = is_dir( $templates_dir );

        $this->results['templates'] = array(
            'label'       => __( 'Templates', 'apollo-events' ),
            'status'      => $has_templates ? 'good' : 'recommended',
            'badge'       => $has_templates ? 'success' : 'warning',
            'description' => $has_templates
                ? __( 'Diretório de templates existe.', 'apollo-events' )
                : __( 'Diretório de templates não encontrado.', 'apollo-events' ),
        );
    }

    /**
     * Check assets.
     *
     * @since 2.0.0
     * @return void
     */
    private function check_assets(): void {
        $assets_dir = dirname( dirname( __DIR__ ) ) . '/assets';
        $css_dir    = $assets_dir . '/css';
        $js_dir     = $assets_dir . '/js';

        $has_css = is_dir( $css_dir ) && count( glob( $css_dir . '/*.css' ) ) > 0;
        $has_js  = is_dir( $js_dir ) && count( glob( $js_dir . '/*.js' ) ) > 0;
        $status  = $has_css && $has_js;

        $this->results['assets'] = array(
            'label'       => __( 'Assets (CSS/JS)', 'apollo-events' ),
            'status'      => $status ? 'good' : 'recommended',
            'badge'       => $status ? 'success' : 'warning',
            'description' => $status
                ? __( 'Arquivos CSS e JS encontrados.', 'apollo-events' )
                : __( 'Alguns arquivos de assets podem estar faltando.', 'apollo-events' ),
        );
    }

    /**
     * Check file permissions.
     *
     * @since 2.0.0
     * @return void
     */
    private function check_permissions(): void {
        $upload_dir = wp_upload_dir();
        $writable   = wp_is_writable( $upload_dir['basedir'] );

        $this->results['permissions'] = array(
            'label'       => __( 'Permissões de Escrita', 'apollo-events' ),
            'status'      => $writable ? 'good' : 'recommended',
            'badge'       => $writable ? 'success' : 'warning',
            'description' => $writable
                ? __( 'Diretório de uploads é gravável.', 'apollo-events' )
                : __( 'Diretório de uploads não é gravável.', 'apollo-events' ),
        );
    }

    /**
     * Check cron jobs.
     *
     * @since 2.0.0
     * @return void
     */
    private function check_cron_jobs(): void {
        $crons = array(
            'apollo_send_event_reminders',
            'apollo_weekly_digest',
        );

        $scheduled = 0;
        foreach ( $crons as $hook ) {
            if ( wp_next_scheduled( $hook ) ) {
                $scheduled++;
            }
        }

        $status = $scheduled > 0;

        $this->results['cron'] = array(
            'label'       => __( 'Tarefas Agendadas', 'apollo-events' ),
            'status'      => $status ? 'good' : 'recommended',
            'badge'       => $status ? 'success' : 'info',
            'description' => sprintf(
                /* translators: Number of scheduled tasks */
                __( '%d tarefas agendadas.', 'apollo-events' ),
                $scheduled
            ),
        );
    }

    /**
     * Check dependencies.
     *
     * @since 2.0.0
     * @return void
     */
    private function check_dependencies(): void {
        $dependencies = array(
            'WooCommerce' => class_exists( 'WooCommerce' ),
        );

        $info = array();
        foreach ( $dependencies as $name => $active ) {
            $info[] = $name . ': ' . ( $active ? __( 'Ativo', 'apollo-events' ) : __( 'Inativo', 'apollo-events' ) );
        }

        $this->results['dependencies'] = array(
            'label'       => __( 'Dependências Opcionais', 'apollo-events' ),
            'status'      => 'good',
            'badge'       => 'info',
            'description' => implode( ', ', $info ),
        );
    }

    /**
     * Render health check page.
     *
     * @since 2.0.0
     * @return void
     */
    public function render_page(): void {
        $results = $this->run_checks();
        $good    = count( array_filter( $results, fn( $r ) => 'good' === $r['status'] ) );
        $total   = count( $results );
        $percent = round( ( $good / $total ) * 100 );
        ?>
        <div class="wrap apollo-health-check">
            <h1><?php esc_html_e( 'Saúde do Plugin Apollo Events', 'apollo-events' ); ?></h1>

            <div class="apollo-health-summary">
                <div class="apollo-health-score">
                    <div class="apollo-health-score__circle" style="--percent: <?php echo esc_attr( $percent ); ?>">
                        <span class="apollo-health-score__value"><?php echo esc_html( $percent ); ?>%</span>
                    </div>
                    <p class="apollo-health-score__label">
                        <?php printf(
                            /* translators: 1: Good checks, 2: Total checks */
                            esc_html__( '%1$d de %2$d verificações OK', 'apollo-events' ),
                            $good,
                            $total
                        ); ?>
                    </p>
                </div>
            </div>

            <div class="apollo-health-results">
                <?php foreach ( $results as $key => $result ) : ?>
                    <div class="apollo-health-item apollo-health-item--<?php echo esc_attr( $result['badge'] ); ?>">
                        <div class="apollo-health-item__header">
                            <span class="apollo-health-item__badge"><?php echo esc_html( $result['badge'] ); ?></span>
                            <h3 class="apollo-health-item__title"><?php echo esc_html( $result['label'] ); ?></h3>
                        </div>
                        <p class="apollo-health-item__description"><?php echo esc_html( $result['description'] ); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="apollo-health-actions">
                <a href="<?php echo esc_url( admin_url( 'site-health.php' ) ); ?>" class="button">
                    <?php esc_html_e( 'Ver Saúde do Site', 'apollo-events' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'site-health.php?tab=debug' ) ); ?>" class="button">
                    <?php esc_html_e( 'Informações de Debug', 'apollo-events' ); ?>
                </a>
            </div>
        </div>

        <style>
            .apollo-health-check { max-width: 800px; }
            .apollo-health-summary {
                text-align: center;
                padding: 40px 20px;
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                margin-bottom: 20px;
            }
            .apollo-health-score__circle {
                width: 120px;
                height: 120px;
                border-radius: 50%;
                background: conic-gradient(
                    #00a32a calc(var(--percent) * 1%),
                    #dcdcde calc(var(--percent) * 1%)
                );
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 16px;
                position: relative;
            }
            .apollo-health-score__circle::before {
                content: '';
                position: absolute;
                width: 90px;
                height: 90px;
                background: #fff;
                border-radius: 50%;
            }
            .apollo-health-score__value {
                position: relative;
                font-size: 28px;
                font-weight: 700;
                color: #1d2327;
            }
            .apollo-health-score__label {
                margin: 0;
                color: #50575e;
            }
            .apollo-health-results {
                display: flex;
                flex-direction: column;
                gap: 12px;
                margin-bottom: 20px;
            }
            .apollo-health-item {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                padding: 16px;
                border-left-width: 4px;
            }
            .apollo-health-item--success { border-left-color: #00a32a; }
            .apollo-health-item--warning { border-left-color: #dba617; }
            .apollo-health-item--error { border-left-color: #d63638; }
            .apollo-health-item--info { border-left-color: #72aee6; }
            .apollo-health-item__header {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 8px;
            }
            .apollo-health-item__badge {
                font-size: 10px;
                font-weight: 600;
                text-transform: uppercase;
                padding: 2px 8px;
                border-radius: 3px;
                background: #f0f0f1;
                color: #50575e;
            }
            .apollo-health-item--success .apollo-health-item__badge { background: #edfaef; color: #00a32a; }
            .apollo-health-item--warning .apollo-health-item__badge { background: #fcf9e8; color: #996800; }
            .apollo-health-item--error .apollo-health-item__badge { background: #fcf0f1; color: #d63638; }
            .apollo-health-item--info .apollo-health-item__badge { background: #f0f6fc; color: #0a4b78; }
            .apollo-health-item__title {
                margin: 0;
                font-size: 14px;
            }
            .apollo-health-item__description {
                margin: 0;
                color: #50575e;
                font-size: 13px;
            }
            .apollo-health-actions {
                display: flex;
                gap: 12px;
            }
        </style>
        <?php
    }

    /**
     * Add debug info to Site Health.
     *
     * @since 2.0.0
     * @param array $debug_info Debug info array.
     * @return array
     */
    public function add_debug_info( array $debug_info ): array {
        $results = $this->run_checks();

        $fields = array();
        foreach ( $results as $key => $result ) {
            $fields[ $key ] = array(
                'label' => $result['label'],
                'value' => $result['description'],
            );
        }

        // Add plugin info.
        $fields['version'] = array(
            'label' => __( 'Versão do Plugin', 'apollo-events' ),
            'value' => defined( 'APOLLO_EVENTS_VERSION' ) ? APOLLO_EVENTS_VERSION : '2.0.0',
        );

        $fields['event_count'] = array(
            'label' => __( 'Total de Eventos', 'apollo-events' ),
            'value' => wp_count_posts( 'event_listing' )->publish,
        );

        $fields['dj_count'] = array(
            'label' => __( 'Total de DJs', 'apollo-events' ),
            'value' => wp_count_posts( 'event_dj' )->publish,
        );

        $fields['local_count'] = array(
            'label' => __( 'Total de Locais', 'apollo-events' ),
            'value' => wp_count_posts( 'event_local' )->publish,
        );

        $debug_info['apollo-events'] = array(
            'label'  => 'Apollo Events Manager',
            'fields' => $fields,
        );

        return $debug_info;
    }

    /**
     * Add Site Health tests.
     *
     * @since 2.0.0
     * @param array $tests Tests array.
     * @return array
     */
    public function add_site_health_tests( array $tests ): array {
        $tests['direct']['apollo_events_check'] = array(
            'label' => __( 'Apollo Events Manager', 'apollo-events' ),
            'test'  => array( $this, 'site_health_test' ),
        );

        return $tests;
    }

    /**
     * Site Health test callback.
     *
     * @since 2.0.0
     * @return array
     */
    public function site_health_test(): array {
        $results = $this->run_checks();
        $good    = count( array_filter( $results, fn( $r ) => 'good' === $r['status'] ) );
        $total   = count( $results );
        $percent = round( ( $good / $total ) * 100 );

        if ( $percent === 100 ) {
            $status = 'good';
            $label  = __( 'Apollo Events está funcionando corretamente', 'apollo-events' );
        } elseif ( $percent >= 70 ) {
            $status = 'recommended';
            $label  = __( 'Apollo Events tem algumas recomendações', 'apollo-events' );
        } else {
            $status = 'critical';
            $label  = __( 'Apollo Events precisa de atenção', 'apollo-events' );
        }

        return array(
            'label'       => $label,
            'status'      => $status,
            'badge'       => array(
                'label' => 'Apollo Events',
                'color' => 'blue',
            ),
            'description' => sprintf(
                '<p>%s</p>',
                sprintf(
                    /* translators: 1: Good checks, 2: Total checks, 3: Percentage */
                    __( '%1$d de %2$d verificações OK (%3$d%%).', 'apollo-events' ),
                    $good,
                    $total,
                    $percent
                )
            ),
            'actions'     => sprintf(
                '<a href="%s">%s</a>',
                admin_url( 'edit.php?post_type=event_listing&page=apollo-health' ),
                __( 'Ver detalhes', 'apollo-events' )
            ),
            'test'        => 'apollo_events_check',
        );
    }
}
