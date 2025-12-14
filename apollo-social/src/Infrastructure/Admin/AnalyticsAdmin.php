<?php

namespace Apollo\Infrastructure\Admin;

/**
 * Analytics Admin Page
 * Provides analytics dashboard and configuration for Apollo Social
 */
class AnalyticsAdmin
{
    private $config;

    public function __construct()
    {
        $this->config = config('analytics');
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks()
    {
        add_action('admin_menu', [ $this, 'add_admin_menu' ], 30);
        add_action('admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ]);
        add_action('wp_ajax_apollo_analytics_stats', [ $this, 'ajax_get_analytics_stats' ]);
        add_action('admin_post_apollo_save_analytics_config', [ $this, 'save_analytics_config' ]);
    }

    /**
     * Add analytics submenu to Apollo admin
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'apollo-social',
            'Analytics Apollo',
            'Analytics',
            'manage_options',
            'apollo-analytics',
            [ $this, 'render_analytics_page' ]
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook)
    {
        if ($hook !== 'apollo-social_page_apollo-analytics') {
            return;
        }

        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'apollo-analytics-admin',
            APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/admin/analytics.js',
            [ 'jquery' ],
            APOLLO_SOCIAL_VERSION,
            true
        );

        wp_localize_script(
            'apollo-analytics-admin',
            'apolloAnalyticsAdmin',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('apollo_analytics_admin'),
                'config'  => $this->config,
            ]
        );

        wp_enqueue_style(
            'apollo-analytics-admin',
            APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/admin/analytics.css',
            [],
            APOLLO_SOCIAL_VERSION
        );
    }

    /**
     * Render analytics admin page
     */
    public function render_analytics_page()
    {
        $analytics_enabled = $this->config['enabled']   ?? false;
        $driver            = $this->config['driver']    ?? 'plausible';
        $plausible_config  = $this->config['plausible'] ?? [];

        echo '<div class="wrap apollo-analytics-admin">';
        echo '<h1>Analytics Apollo</h1>';

        // Show configuration status
        $this->render_configuration_status();

        // Configuration form
        $this->render_configuration_form();

        // Analytics dashboard (if configured)
        if ($analytics_enabled && ! empty($plausible_config['domain'])) {
            $this->render_analytics_dashboard();
        }

        echo '</div>';
    }

    /**
     * Render configuration status
     */
    private function render_configuration_status()
    {
        $status = $this->get_configuration_status();

        echo '<div class="apollo-analytics-status">';
        echo '<h2>Status da Configuração</h2>';

        echo '<div class="status-grid">';

        foreach ($status as $key => $item) {
            $status_class = $item['status'] ? 'status-ok' : 'status-error';
            $icon         = $item['status'] ? '✅' : '❌';

            echo '<div class="status-item ' . esc_attr($status_class) . '">';
            echo '<span class="status-icon">' . $icon . '</span>';
            echo '<span class="status-label">' . esc_html($item['label']) . '</span>';
            echo '<span class="status-description">' . esc_html($item['description']) . '</span>';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * Render configuration form
     */
    private function render_configuration_form()
    {
        $plausible_config = $this->config['plausible'] ?? [];

        echo '<div class="apollo-analytics-config">';
        echo '<h2>Configuração do Analytics</h2>';

        echo '<form method="post" action="' . admin_url('admin-post.php') . '">';
        echo '<input type="hidden" name="action" value="apollo_save_analytics_config">';
        wp_nonce_field('apollo_save_analytics_config');

        echo '<table class="form-table">';

        // Enable/Disable Analytics
        echo '<tr>';
        echo '<th scope="row"><label for="analytics_enabled">Habilitar Analytics</label></th>';
        echo '<td>';
        echo '<label><input type="checkbox" id="analytics_enabled" name="analytics[enabled]" value="1"' . checked($this->config['enabled'] ?? false, true, false) . '> Ativar tracking de analytics</label>';
        echo '<p class="description">Habilita o tracking de eventos e visualizações no Apollo Social.</p>';
        echo '</td>';
        echo '</tr>';

        // Analytics Driver
        echo '<tr>';
        echo '<th scope="row"><label for="analytics_driver">Provider de Analytics</label></th>';
        echo '<td>';
        echo '<select id="analytics_driver" name="analytics[driver]">';
        echo '<option value="plausible"' . selected($this->config['driver'] ?? '', 'plausible', false) . '>Plausible Analytics</option>';
        echo '<option value="matomo"' . selected($this->config['driver'] ?? '', 'matomo', false) . '>Matomo</option>';
        echo '<option value="umami"' . selected($this->config['driver'] ?? '', 'umami', false) . '>Umami</option>';
        echo '</select>';
        echo '<p class="description">Escolha o provider de analytics. Recomendado: Plausible (privacy-focused).</p>';
        echo '</td>';
        echo '</tr>';

        // Plausible Configuration
        echo '<tr class="plausible-config">';
        echo '<th scope="row">Configuração Plausible</th>';
        echo '<td>';

        echo '<div class="config-group">';
        echo '<label for="plausible_domain">Domínio do Site</label>';
        echo '<input type="text" id="plausible_domain" name="analytics[plausible][domain]" value="' . esc_attr($plausible_config['domain'] ?? '') . '" class="regular-text" placeholder="exemplo.com">';
        echo '<p class="description">O domínio configurado no Plausible (sem https://).</p>';
        echo '</div>';

        echo '<div class="config-group">';
        echo '<label for="plausible_api_base">Base da API</label>';
        echo '<input type="url" id="plausible_api_base" name="analytics[plausible][api_base]" value="' . esc_attr($plausible_config['api_base'] ?? 'https://plausible.io') . '" class="regular-text" placeholder="https://plausible.io">';
        echo '<p class="description">URL base da API do Plausible (para instâncias self-hosted).</p>';
        echo '</div>';

        echo '<div class="config-group">';
        echo '<label for="plausible_script_url">URL do Script</label>';
        echo '<input type="url" id="plausible_script_url" name="analytics[plausible][script_url]" value="' . esc_attr($plausible_config['script_url'] ?? 'https://plausible.io/js/plausible.js') . '" class="regular-text" placeholder="https://plausible.io/js/plausible.js">';
        echo '<p class="description">URL do script de tracking do Plausible.</p>';
        echo '</div>';

        echo '<div class="config-group">';
        echo '<label for="plausible_shared_dashboard">Dashboard Público</label>';
        echo '<input type="url" id="plausible_shared_dashboard" name="analytics[plausible][shared_dashboard_url]" value="' . esc_attr($plausible_config['shared_dashboard_url'] ?? '') . '" class="regular-text" placeholder="https://plausible.io/share/...">';
        echo '<p class="description">URL do dashboard público compartilhado do Plausible (opcional).</p>';
        echo '</div>';

        echo '<div class="config-group">';
        echo '<label for="plausible_api_key">Chave da API</label>';
        echo '<input type="password" id="plausible_api_key" name="analytics[plausible][api_key]" value="' . esc_attr($plausible_config['api_key'] ?? '') . '" class="regular-text" placeholder="Chave da API para consultas">';
        echo '<p class="description">Chave da API do Plausible para acessar estatísticas (opcional).</p>';
        echo '</div>';

        echo '</td>';
        echo '</tr>';

        echo '</table>';

        submit_button('Salvar Configurações');

        echo '</form>';
        echo '</div>';
    }

    /**
     * Render analytics dashboard
     */
    private function render_analytics_dashboard()
    {
        $plausible_config     = $this->config['plausible']                ?? [];
        $shared_dashboard_url = $plausible_config['shared_dashboard_url'] ?? '';

        echo '<div class="apollo-analytics-dashboard">';
        echo '<h2>Dashboard de Analytics</h2>';

        // Live stats cards
        echo '<div class="analytics-stats-grid" id="analytics-stats-grid">';
        echo '<div class="loading">Carregando estatísticas...</div>';
        echo '</div>';

        // Embedded dashboard
        if (! empty($shared_dashboard_url)) {
            echo '<div class="embedded-dashboard">';
            echo '<h3>Dashboard Público do Plausible</h3>';
            echo '<iframe src="' . esc_url($shared_dashboard_url) . '" width="100%" height="600" frameborder="0" loading="lazy"></iframe>';
            echo '</div>';
        }

        // Events tracking status
        $this->render_events_tracking_status();

        echo '</div>';
    }

    /**
     * Render events tracking status
     */
    private function render_events_tracking_status()
    {
        $events_config = $this->config['events'] ?? [];

        echo '<div class="apollo-analytics-events">';
        echo '<h3>Status dos Eventos Personalizados</h3>';

        echo '<div class="events-grid">';

        $categories = [ 'social', 'classifieds', 'events' ];

        foreach ($categories as $category) {
            if (! isset($events_config[ $category ])) {
                continue;
            }

            echo '<div class="events-category">';
            echo '<h4>' . esc_html(ucfirst($category)) . '</h4>';

            foreach ($events_config[ $category ] as $event_key => $event_config) {
                echo '<div class="event-item">';
                echo '<span class="event-name">' . esc_html($event_key) . '</span>';
                echo '<span class="event-description">' . esc_html($event_config['name'] ?? $event_key) . '</span>';
                echo '<span class="event-count" data-event="' . esc_attr($event_key) . '">-</span>';
                echo '</div>';
            }

            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * AJAX handler for analytics stats
     */
    public function ajax_get_analytics_stats()
    {
        check_ajax_referer('apollo_analytics_admin');

        if (! current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $stats = $this->fetch_analytics_stats();

        wp_send_json_success($stats);
    }

    /**
     * Save analytics configuration
     */
    public function save_analytics_config()
    {
        check_admin_referer('apollo_save_analytics_config');

        if (! current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $analytics_config = $_POST['analytics'] ?? [];

        // Sanitize configuration
        $sanitized_config = $this->sanitize_analytics_config($analytics_config);

        // Update configuration
        update_option('apollo_analytics_config', $sanitized_config);

        wp_redirect(admin_url('admin.php?page=apollo-analytics&message=saved'));
        exit;
    }

    /**
     * Get configuration status
     */
    private function get_configuration_status(): array
    {
        $plausible_config = $this->config['plausible'] ?? [];

        return [
            'enabled' => [
                'status'      => $this->config['enabled'] ?? false,
                'label'       => 'Analytics Habilitado',
                'description' => $this->config['enabled'] ? 'Analytics está ativo' : 'Analytics está desabilitado',
            ],
            'domain' => [
                'status'      => ! empty($plausible_config['domain']),
                'label'       => 'Domínio Configurado',
                'description' => ! empty($plausible_config['domain']) ? 'Domínio: ' . $plausible_config['domain'] : 'Domínio não configurado',
            ],
            'script' => [
                'status'      => ! empty($plausible_config['script_url']),
                'label'       => 'Script do Plausible',
                'description' => ! empty($plausible_config['script_url']) ? 'Script configurado' : 'URL do script não configurada',
            ],
            'api' => [
                'status'      => ! empty($plausible_config['api_key']),
                'label'       => 'API Key',
                'description' => ! empty($plausible_config['api_key']) ? 'API key configurada' : 'API key não configurada (estatísticas limitadas)',
            ],
            'dashboard' => [
                'status'      => ! empty($plausible_config['shared_dashboard_url']),
                'label'       => 'Dashboard Público',
                'description' => ! empty($plausible_config['shared_dashboard_url']) ? 'Dashboard público configurado' : 'Dashboard público não configurado',
            ],
        ];
    }

    /**
     * Fetch analytics stats from Plausible API
     */
    private function fetch_analytics_stats(): array
    {
        $plausible_config = $this->config['plausible']    ?? [];
        $api_key          = $plausible_config['api_key']  ?? '';
        $domain           = $plausible_config['domain']   ?? '';
        $api_base         = $plausible_config['api_base'] ?? 'https://plausible.io';

        if (empty($api_key) || empty($domain)) {
            return [
                'error' => 'API key ou domínio não configurados',
                'stats' => [],
            ];
        }

        // Fetch basic stats for last 30 days
        $stats_url = $api_base . '/api/v1/stats/aggregate?site_id=' . urlencode($domain) . '&period=30d&metrics=visitors,pageviews,bounce_rate,visit_duration';

        $response = wp_remote_get(
            $stats_url,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                ],
                'timeout' => 10,
            ]
        );

        if (is_wp_error($response)) {
            return [
                'error' => 'Erro ao conectar com a API: ' . $response->get_error_message(),
                'stats' => [],
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (! $data || isset($data['error'])) {
            return [
                'error' => 'Erro na resposta da API: ' . ($data['error'] ?? 'Resposta inválida'),
                'stats' => [],
            ];
        }

        // Format stats for display
        $formatted_stats = [
            'visitors' => [
                'label' => 'Visitantes Únicos',
                'value' => $data['results']['visitors']['value'] ?? 0,
                'icon'  => '👥',
            ],
            'pageviews' => [
                'label' => 'Visualizações',
                'value' => $data['results']['pageviews']['value'] ?? 0,
                'icon'  => '📄',
            ],
            'bounce_rate' => [
                'label' => 'Taxa de Rejeição',
                'value' => ($data['results']['bounce_rate']['value'] ?? 0) . '%',
                'icon'  => '📊',
            ],
            'visit_duration' => [
                'label' => 'Duração Média',
                'value' => $this->format_duration($data['results']['visit_duration']['value'] ?? 0),
                'icon'  => '⏱️',
            ],
        ];

        return [
            'error' => null,
            'stats' => $formatted_stats,
        ];
    }

    /**
     * Format duration in seconds to human readable
     */
    private function format_duration($seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        } elseif ($seconds < 3600) {
            return round($seconds / 60, 1) . 'm';
        } else {
            return round($seconds / 3600, 1) . 'h';
        }
    }

    /**
     * Sanitize analytics configuration
     */
    private function sanitize_analytics_config($config): array
    {
        return [
            'enabled'   => ! empty($config['enabled']),
            'driver'    => sanitize_text_field($config['driver'] ?? 'plausible'),
            'plausible' => [
                'domain'               => sanitize_text_field($config['plausible']['domain'] ?? ''),
                'api_base'             => esc_url_raw($config['plausible']['api_base'] ?? 'https://plausible.io'),
                'script_url'           => esc_url_raw($config['plausible']['script_url'] ?? 'https://plausible.io/js/plausible.js'),
                'shared_dashboard_url' => esc_url_raw($config['plausible']['shared_dashboard_url'] ?? ''),
                'api_key'              => sanitize_text_field($config['plausible']['api_key'] ?? ''),
            ],
        ];
    }
}
