<?php

namespace Apollo\Infrastructure\Adapters;

/**
 * Adapter for DocuSeal integration
 * Provides seamless integration with DocuSeal for document signing workflows
 */
class DocuSealAdapter {
    
    private $config;
    private $api_endpoint;
    private $api_key;
    private $webhook_secret;
    
    public function __construct() {
        $this->config = config('integrations.docuseal');
        $this->api_endpoint = $this->config['api_endpoint'] ?? '';
        $this->api_key = $this->config['api_key'] ?? '';
        $this->webhook_secret = $this->config['webhook_secret'] ?? '';
        
        if ($this->config['enabled'] ?? false) {
            $this->init_hooks();
        }
    }
    
    /**
     * Initialize WordPress hooks for DocuSeal integration
     */
    private function init_hooks() {
        // DocuSeal webhook handler
        add_action('wp_ajax_nopriv_apollo_docuseal_webhook', [$this, 'handle_webhook']);
        add_action('wp_ajax_apollo_docuseal_webhook', [$this, 'handle_webhook']);
        
        // Document workflow hooks
        add_action('apollo_document_request', [$this, 'create_signing_request'], 10, 3);
        add_action('apollo_membership_approved', [$this, 'send_membership_agreement'], 10, 2);
        add_action('apollo_season_registration', [$this, 'send_season_contract'], 10, 2);
        add_action('apollo_classified_terms_required', [$this, 'send_classified_terms'], 10, 2);
        
        // Auto-processing
        if ($this->config['auto_process'] ?? false) {
            add_action('apollo_document_signed', [$this, 'auto_process_signed_document'], 10, 2);
        }
        
        // Admin hooks for document management
        add_action('admin_menu', [$this, 'add_admin_menu'], 30);
        add_action('admin_post_apollo_send_document', [$this, 'admin_send_document']);
        
        // REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_endpoints']);
    }
    
    /**
     * Check if DocuSeal is properly configured
     */
    public function is_available(): bool {
        return !empty($this->api_endpoint) && !empty($this->api_key);
    }
    
    /**
     * Handle DocuSeal webhooks
     */
    public function handle_webhook() {
        // Verify webhook secret
        $signature = $_SERVER['HTTP_X_DOCUSEAL_SIGNATURE'] ?? '';
        if (!$this->verify_webhook_signature(file_get_contents('php://input'), $signature)) {
            wp_die('Invalid signature', 'Unauthorized', ['response' => 401]);
        }
        
        $payload = json_decode(file_get_contents('php://input'), true);
        
        if (!$payload || !isset($payload['event_type'])) {
            wp_die('Invalid payload', 'Bad Request', ['response' => 400]);
        }
        
        $this->process_webhook_event($payload);
        
        wp_die('OK', 'OK', ['response' => 200]);
    }
    
    /**
     * Process webhook events
     */
    private function process_webhook_event($payload) {
        $event_type = $payload['event_type'];
        $data = $payload['data'] ?? [];
        
        switch ($event_type) {
            case 'form.completed':
                $this->handle_document_completed($data);
                break;
                
            case 'form.signed':
                $this->handle_document_signed($data);
                break;
                
            case 'form.declined':
                $this->handle_document_declined($data);
                break;
                
            case 'form.expired':
                $this->handle_document_expired($data);
                break;
                
            default:
                do_action('apollo_docuseal_unknown_event', $event_type, $data);
        }
        
        do_action('apollo_docuseal_webhook_processed', $event_type, $data);
    }
    
    /**
     * Handle document completed
     */
    private function handle_document_completed($data) {
        $document_id = $data['id'] ?? null;
        $submitter_email = $data['submitter']['email'] ?? null;
        
        if (!$document_id || !$submitter_email) return;
        
        // Find user by email
        $user = get_user_by('email', $submitter_email);
        if (!$user) return;
        
        // Store completion data
        $this->store_document_completion($user->ID, $document_id, $data);
        
        // Trigger Apollo events
        do_action('apollo_document_completed', $document_id, $user->ID, $data);
        
        // Award points for document signing
        do_action('apollo_award_points', $user->ID, 'document_signed', [
            'document_id' => $document_id,
            'completion_data' => $data
        ]);
    }
    
    /**
     * Handle document signed
     */
    private function handle_document_signed($data) {
        $document_id = $data['id'] ?? null;
        $submitter_email = $data['submitter']['email'] ?? null;
        
        if (!$document_id || !$submitter_email) return;
        
        // Find user by email
        $user = get_user_by('email', $submitter_email);
        if (!$user) return;
        
        // Update document status
        $this->update_document_status($user->ID, $document_id, 'signed', $data);
        
        // Trigger Apollo events
        do_action('apollo_document_signed', $document_id, $user->ID, $data);
        
        // Process specific document types
        $apollo_meta = get_user_meta($user->ID, '_apollo_document_' . $document_id, true);
        if ($apollo_meta && isset($apollo_meta['type'])) {
            $this->process_document_type_completion($apollo_meta['type'], $user->ID, $document_id, $data);
        }
    }
    
    /**
     * Handle document declined
     */
    private function handle_document_declined($data) {
        $document_id = $data['id'] ?? null;
        $submitter_email = $data['submitter']['email'] ?? null;
        
        if (!$document_id || !$submitter_email) return;
        
        $user = get_user_by('email', $submitter_email);
        if (!$user) return;
        
        $this->update_document_status($user->ID, $document_id, 'declined', $data);
        
        do_action('apollo_document_declined', $document_id, $user->ID, $data);
    }
    
    /**
     * Handle document expired
     */
    private function handle_document_expired($data) {
        $document_id = $data['id'] ?? null;
        $submitter_email = $data['submitter']['email'] ?? null;
        
        if (!$document_id || !$submitter_email) return;
        
        $user = get_user_by('email', $submitter_email);
        if (!$user) return;
        
        $this->update_document_status($user->ID, $document_id, 'expired', $data);
        
        do_action('apollo_document_expired', $document_id, $user->ID, $data);
    }
    
    /**
     * Create signing request
     */
    public function create_signing_request($template_id, $user_id, $document_type, $additional_data = []): ?string {
        if (!$this->is_available()) return null;
        
        $user = get_userdata($user_id);
        if (!$user) return null;
        
        // Prepare request data
        $request_data = [
            'template_id' => $template_id,
            'submitters' => [
                [
                    'email' => $user->user_email,
                    'name' => $user->display_name,
                    'role' => 'Signer'
                ]
            ],
            'send_email' => true,
            'webhook_url' => admin_url('admin-ajax.php?action=apollo_docuseal_webhook')
        ];
        
        // Merge additional data
        $request_data = array_merge($request_data, $additional_data);
        
        // Make API request
        $response = $this->make_api_request('POST', '/forms', $request_data);
        
        if ($response && isset($response['id'])) {
            $document_id = $response['id'];
            
            // Store document metadata
            $meta_data = [
                'document_id' => $document_id,
                'type' => $document_type,
                'template_id' => $template_id,
                'status' => 'sent',
                'created_at' => current_time('mysql'),
                'additional_data' => $additional_data
            ];
            
            update_user_meta($user_id, '_apollo_document_' . $document_id, $meta_data);
            
            do_action('apollo_document_sent', $document_id, $user_id, $document_type, $meta_data);
            
            return $document_id;
        }
        
        return null;
    }
    
    /**
     * Send membership agreement
     */
    public function send_membership_agreement($user_id, $group_data) {
        $template_mapping = $this->config['template_mapping'] ?? [];
        $template_id = $template_mapping['membership_agreement'] ?? null;
        
        if (!$template_id) return;
        
        $document_id = $this->create_signing_request(
            $template_id,
            $user_id,
            'membership_agreement',
            [
                'group_type' => $group_data['type'] ?? '',
                'group_id' => $group_data['id'] ?? ''
            ]
        );
        
        if ($document_id) {
            do_action('apollo_membership_agreement_sent', $document_id, $user_id, $group_data);
        }
    }
    
    /**
     * Send season contract
     */
    public function send_season_contract($user_id, $season_data) {
        $template_mapping = $this->config['template_mapping'] ?? [];
        $template_id = $template_mapping['season_contract'] ?? null;
        
        if (!$template_id) return;
        
        $document_id = $this->create_signing_request(
            $template_id,
            $user_id,
            'season_contract',
            [
                'season_id' => $season_data['season_id'] ?? '',
                'season_year' => $season_data['year'] ?? ''
            ]
        );
        
        if ($document_id) {
            do_action('apollo_season_contract_sent', $document_id, $user_id, $season_data);
        }
    }
    
    /**
     * Send classified terms
     */
    public function send_classified_terms($user_id, $classified_data) {
        $template_mapping = $this->config['template_mapping'] ?? [];
        $template_id = $template_mapping['classified_terms'] ?? null;
        
        if (!$template_id) return;
        
        $document_id = $this->create_signing_request(
            $template_id,
            $user_id,
            'classified_terms',
            [
                'classified_id' => $classified_data['id'] ?? '',
                'classified_type' => $classified_data['type'] ?? ''
            ]
        );
        
        if ($document_id) {
            do_action('apollo_classified_terms_sent', $document_id, $user_id, $classified_data);
        }
    }
    
    /**
     * Auto-process signed documents
     */
    public function auto_process_signed_document($document_id, $user_id) {
        $apollo_meta = get_user_meta($user_id, '_apollo_document_' . $document_id, true);
        
        if (!$apollo_meta || !isset($apollo_meta['type'])) return;
        
        switch ($apollo_meta['type']) {
            case 'membership_agreement':
                // Activate membership
                $group_data = $apollo_meta['additional_data'] ?? [];
                if (isset($group_data['group_type'])) {
                    $groups_adapter = new GroupsAdapter();
                    $groups_adapter->add_user_to_group($user_id, $group_data['group_type']);
                    
                    do_action('apollo_membership_activated', $user_id, $group_data);
                }
                break;
                
            case 'season_contract':
                // Activate season participation
                $season_data = $apollo_meta['additional_data'] ?? [];
                update_user_meta($user_id, '_apollo_season_active', $season_data['season_id'] ?? '');
                
                do_action('apollo_season_activated', $user_id, $season_data);
                break;
                
            case 'classified_terms':
                // Enable classified posting
                update_user_meta($user_id, '_apollo_classified_terms_accepted', true);
                
                do_action('apollo_classified_terms_accepted', $user_id);
                break;
        }
    }
    
    /**
     * Get user documents
     */
    public function get_user_documents($user_id, $status = null): array {
        $meta_keys = get_user_meta($user_id);
        $documents = [];
        
        foreach ($meta_keys as $key => $values) {
            if (strpos($key, '_apollo_document_') === 0) {
                $document_data = maybe_unserialize($values[0]);
                
                if ($status && isset($document_data['status']) && $document_data['status'] !== $status) {
                    continue;
                }
                
                $documents[] = $document_data;
            }
        }
        
        return $documents;
    }
    
    /**
     * Check if user has signed document type
     */
    public function user_has_signed_document($user_id, $document_type): bool {
        $documents = $this->get_user_documents($user_id, 'signed');
        
        foreach ($documents as $document) {
            if (isset($document['type']) && $document['type'] === $document_type) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get document status
     */
    public function get_document_status($document_id): ?string {
        if (!$this->is_available()) return null;
        
        $response = $this->make_api_request('GET', '/forms/' . $document_id);
        
        return $response['status'] ?? null;
    }
    
    /**
     * Download signed document
     */
    public function download_document($document_id): ?string {
        if (!$this->is_available()) return null;
        
        $response = $this->make_api_request('GET', '/forms/' . $document_id . '/download');
        
        return $response['download_url'] ?? null;
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'apollo-social',
            'Documentos Apollo',
            'Documentos',
            'manage_options',
            'apollo-documents',
            [$this, 'admin_documents_page']
        );
    }
    
    /**
     * Admin documents page
     */
    public function admin_documents_page() {
        echo '<div class="wrap">';
        echo '<h1>Gestão de Documentos Apollo</h1>';
        
        // Document sending form
        echo '<form method="post" action="' . admin_url('admin-post.php') . '">';
        echo '<input type="hidden" name="action" value="apollo_send_document">';
        wp_nonce_field('apollo_send_document');
        
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th><label for="user_id">Usuário:</label></th>';
        echo '<td>';
        echo '<select name="user_id" id="user_id" required>';
        echo '<option value="">Selecione um usuário...</option>';
        
        $users = get_users();
        foreach ($users as $user) {
            echo '<option value="' . $user->ID . '">' . esc_html($user->display_name . ' (' . $user->user_email . ')') . '</option>';
        }
        
        echo '</select>';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="document_type">Tipo de Documento:</label></th>';
        echo '<td>';
        echo '<select name="document_type" id="document_type" required>';
        echo '<option value="">Selecione...</option>';
        echo '<option value="membership_agreement">Acordo de Adesão</option>';
        echo '<option value="season_contract">Contrato de Season</option>';
        echo '<option value="classified_terms">Termos de Classificados</option>';
        echo '</select>';
        echo '</td>';
        echo '</tr>';
        
        echo '</table>';
        
        submit_button('Enviar Documento');
        
        echo '</form>';
        
        // Recent documents table
        $this->display_recent_documents();
        
        echo '</div>';
    }
    
    /**
     * Handle admin document sending
     */
    public function admin_send_document() {
        check_admin_referer('apollo_send_document');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $user_id = intval($_POST['user_id']);
        $document_type = sanitize_text_field($_POST['document_type']);
        
        if (!$user_id || !$document_type) {
            wp_die('Invalid parameters');
        }
        
        $template_mapping = $this->config['template_mapping'] ?? [];
        $template_id = $template_mapping[$document_type] ?? null;
        
        if (!$template_id) {
            wp_die('Template not configured for document type: ' . $document_type);
        }
        
        $document_id = $this->create_signing_request($template_id, $user_id, $document_type);
        
        if ($document_id) {
            wp_redirect(admin_url('admin.php?page=apollo-documents&message=sent'));
        } else {
            wp_redirect(admin_url('admin.php?page=apollo-documents&message=error'));
        }
        
        exit;
    }
    
    /**
     * Register REST endpoints
     */
    public function register_rest_endpoints() {
        register_rest_route('apollo/v1', '/documents/(?P<user_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_user_documents'],
            'permission_callback' => function($request) {
                return current_user_can('read') && (current_user_can('manage_options') || $request['user_id'] == get_current_user_id());
            }
        ]);
        
        register_rest_route('apollo/v1', '/documents/send', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_send_document'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ]);
    }
    
    /**
     * REST: Get user documents
     */
    public function rest_get_user_documents($request) {
        $user_id = $request['user_id'];
        $documents = $this->get_user_documents($user_id);
        
        return rest_ensure_response($documents);
    }
    
    /**
     * REST: Send document
     */
    public function rest_send_document($request) {
        $user_id = $request['user_id'];
        $document_type = $request['document_type'];
        $additional_data = $request['additional_data'] ?? [];
        
        $template_mapping = $this->config['template_mapping'] ?? [];
        $template_id = $template_mapping[$document_type] ?? null;
        
        if (!$template_id) {
            return new WP_Error('template_not_found', 'Template not configured', ['status' => 400]);
        }
        
        $document_id = $this->create_signing_request($template_id, $user_id, $document_type, $additional_data);
        
        if ($document_id) {
            return rest_ensure_response(['document_id' => $document_id, 'status' => 'sent']);
        } else {
            return new WP_Error('send_failed', 'Failed to send document', ['status' => 500]);
        }
    }
    
    /**
     * Helper methods
     */
    private function make_api_request($method, $endpoint, $data = null): ?array {
        $url = rtrim($this->api_endpoint, '/') . $endpoint;
        
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30
        ];
        
        if ($data) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            do_action('apollo_docuseal_api_error', $response->get_error_message(), $method, $endpoint);
            return null;
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (wp_remote_retrieve_response_code($response) >= 400) {
            do_action('apollo_docuseal_api_error', $decoded['error'] ?? 'Unknown error', $method, $endpoint);
            return null;
        }
        
        return $decoded;
    }
    
    private function verify_webhook_signature($payload, $signature): bool {
        if (empty($this->webhook_secret)) return true; // Skip verification if no secret configured
        
        $expected_signature = hash_hmac('sha256', $payload, $this->webhook_secret);
        return hash_equals($signature, $expected_signature);
    }
    
    private function store_document_completion($user_id, $document_id, $data) {
        $apollo_meta = get_user_meta($user_id, '_apollo_document_' . $document_id, true);
        if ($apollo_meta) {
            $apollo_meta['status'] = 'completed';
            $apollo_meta['completed_at'] = current_time('mysql');
            $apollo_meta['completion_data'] = $data;
            
            update_user_meta($user_id, '_apollo_document_' . $document_id, $apollo_meta);
        }
    }
    
    private function update_document_status($user_id, $document_id, $status, $data) {
        $apollo_meta = get_user_meta($user_id, '_apollo_document_' . $document_id, true);
        if ($apollo_meta) {
            $apollo_meta['status'] = $status;
            $apollo_meta['updated_at'] = current_time('mysql');
            $apollo_meta['status_data'] = $data;
            
            update_user_meta($user_id, '_apollo_document_' . $document_id, $apollo_meta);
        }
    }
    
    private function process_document_type_completion($document_type, $user_id, $document_id, $data) {
        do_action('apollo_document_type_completed', $document_type, $user_id, $document_id, $data);
        do_action('apollo_document_type_completed_' . $document_type, $user_id, $document_id, $data);
    }
    
    private function display_recent_documents() {
        // Implementation for displaying recent documents in admin
        echo '<h2>Documentos Recentes</h2>';
        echo '<p>Lista de documentos enviados recentemente apareceria aqui.</p>';
    }
    
    /**
     * Get configuration
     */
    public function get_config(): array {
        return $this->config;
    }
    
    /**
     * Update configuration
     */
    public function update_config(array $config): bool {
        $this->config = array_merge($this->config, $config);
        return update_option('apollo_docuseal_config', $this->config);
    }
}