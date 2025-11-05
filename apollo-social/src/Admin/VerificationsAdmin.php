<?php

namespace Apollo\Admin;

use Apollo\Application\Users\UserProfileRepository;

/**
 * VerificationsTable
 * Admin interface with CodePen-inspired grid design for verification management
 */
class VerificationsTable
{
    private UserProfileRepository $userRepo;
    
    public function __construct()
    {
        $this->userRepo = new UserProfileRepository();
    }
    
    /**
     * Initialize admin interface
     */
    public function init(): void
    {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_apollo_get_verifications', [$this, 'ajaxGetVerifications']);
        add_action('wp_ajax_apollo_get_verification_details', [$this, 'ajaxGetVerificationDetails']);
        add_action('wp_ajax_apollo_get_verification_stats', [$this, 'ajaxGetVerificationStats']);
        add_action('wp_ajax_apollo_approve_verification', [$this, 'ajaxApproveVerification']);
        add_action('wp_ajax_apollo_reject_verification', [$this, 'ajaxRejectVerification']);
    }
    
    /**
     * Add admin menu pages
     */
    public function addAdminMenu(): void
    {
        add_menu_page(
            'Apollo Verificações',
            'Verificações',
            'manage_options',
            'apollo-verifications',
            [$this, 'renderVerificationsPage'],
            'dashicons-shield-alt',
            30
        );
        
        add_submenu_page(
            'apollo-verifications',
            'Verificações Pendentes',
            'Pendentes',
            'manage_options',
            'apollo-verifications',
            [$this, 'renderVerificationsPage']
        );
        
        add_submenu_page(
            'apollo-verifications',
            'Analytics',
            'Analytics',
            'manage_options',
            'apollo-verifications-analytics',
            [$this, 'renderAnalyticsPage']
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueueAssets($hook): void
    {
        if (!in_array($hook, ['toplevel_page_apollo-verifications', 'verifications_page_apollo-verifications-analytics'])) {
            return;
        }
        
        $plugin_url = plugin_dir_url(__FILE__) . '../../assets/admin/';
        
        wp_enqueue_style(
            'apollo-verifications-admin',
            $plugin_url . 'verifications.css',
            [],
            '1.0.0'
        );
        
        wp_enqueue_script(
            'apollo-verifications-admin',
            $plugin_url . 'verifications.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_localize_script('apollo-verifications-admin', 'apolloAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('apollo_admin_nonce'),
            'strings' => [
                'confirmApprove' => 'Tem certeza que deseja aprovar esta verificação?',
                'confirmReject' => 'Tem certeza que deseja rejeitar esta verificação?',
                'loadingText' => 'Carregando...',
                'errorGeneric' => 'Ocorreu um erro. Tente novamente.',
                'successApprove' => 'Verificação aprovada com sucesso!',
                'successReject' => 'Verificação rejeitada com sucesso!'
            ]
        ]);
    }
    
    /**
     * Render main verifications page
     */
    public function renderVerificationsPage(): void
    {
        $stats = $this->userRepo->getVerificationStats();
        
        ?>
        <div class="apollo-verifications-container">
            <div class="apollo-verifications-header">
                <h1 class="apollo-page-title">
                    <span class="apollo-icon">🛡️</span>
                    Verificações Apollo
                </h1>
                
                <!-- Stats Grid -->
                <div class="apollo-stats-grid">
                    <div class="apollo-stat-card awaiting">
                        <div class="stat-number" id="stat-awaiting"><?php echo $stats['awaiting_instagram_verify']; ?></div>
                        <div class="stat-label">Aguardando</div>
                    </div>
                    <div class="apollo-stat-card awaiting">
                        <div class="stat-number" id="stat-submitted"><?php echo $stats['assets_submitted'] ?? 0; ?></div>
                        <div class="stat-label">Submetidos</div>
                    </div>
                    <div class="apollo-stat-card verified">
                        <div class="stat-number" id="stat-verified"><?php echo $stats['verified']; ?></div>
                        <div class="stat-label">Verificados</div>
                    </div>
                    <div class="apollo-stat-card rejected">
                        <div class="stat-number" id="stat-rejected"><?php echo $stats['rejected']; ?></div>
                        <div class="stat-label">Rejeitados</div>
                    </div>
                    <div class="apollo-stat-card total">
                        <div class="stat-number" id="stat-total"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total</div>
                    </div>
                </div>
            </div>
            
            <!-- Filters Section -->
            <div class="apollo-filters-section">
                <form id="apollo-filter-form" class="apollo-filter-form">
                    <div class="filter-group">
                        <label for="status-filter">Status:</label>
                        <select id="status-filter" name="status" class="apollo-select">
                            <option value="">Todos os status</option>
                            <option value="awaiting_instagram_verify">Aguardando</option>
                            <option value="assets_submitted">Submetidos</option>
                            <option value="verified">Verificados</option>
                            <option value="rejected">Rejeitados</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="search-input">Buscar:</label>
                        <input type="text" id="search-input" name="search" class="apollo-input" 
                               placeholder="Nome, username, Instagram...">
                    </div>
                    
                    <div class="filter-group">
                        <label for="date-filter">Período:</label>
                        <select id="date-filter" name="period" class="apollo-select">
                            <option value="">Todos os períodos</option>
                            <option value="today">Hoje</option>
                            <option value="week">Esta semana</option>
                            <option value="month">Este mês</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="apollo-btn primary">
                            <span class="btn-icon">🔍</span>
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Verifications Grid -->
            <div id="verifications-grid" class="apollo-verifications-grid">
                <!-- Cards will be loaded via JavaScript -->
            </div>
        </div>
        
        <style>
        /* Quick inline styles for immediate loading */
        .apollo-verifications-container { opacity: 0; animation: fadeIn 0.3s ease forwards; }
        @keyframes fadeIn { to { opacity: 1; } }
        </style>
        <?php
    }
    
    /**
     * Render analytics page
     */
    public function renderAnalyticsPage(): void
    {
        $analytics = $this->userRepo->getOnboardingAnalytics('30d');
        
        ?>
        <div class="apollo-analytics-container">
            <div class="apollo-verifications-header">
                <h1 class="apollo-page-title">
                    <span class="apollo-icon">📊</span>
                    Analytics de Onboarding
                </h1>
            </div>
            
            <div class="analytics-grid">
                <div class="analytics-card">
                    <h3>Completions nos últimos 30 dias</h3>
                    <div class="chart-container">
                        <div class="chart-placeholder">
                            Gráfico de linha - <?php echo count($analytics['completions_over_time']); ?> dias com dados
                        </div>
                    </div>
                </div>
                
                <div class="analytics-card">
                    <h3>Distribuição por Indústria</h3>
                    <div class="industry-list">
                        <?php foreach ($analytics['industry_distribution'] as $industry): ?>
                            <div class="industry-item">
                                <span class="industry-name"><?php echo esc_html($industry['industry']); ?></span>
                                <span class="industry-count"><?php echo $industry['count']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="analytics-card">
                    <h3>Métricas Gerais</h3>
                    <div class="metrics-grid">
                        <div class="metric">
                            <div class="metric-value"><?php echo array_sum(array_column($analytics['completions_over_time'], 'completions')); ?></div>
                            <div class="metric-label">Completions Total</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value"><?php echo count($analytics['industry_distribution']); ?></div>
                            <div class="metric-label">Indústrias</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value"><?php echo $this->calculateAveragePerDay($analytics['completions_over_time']); ?></div>
                            <div class="metric-label">Média/Dia</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value"><?php echo count($analytics['completions_over_time']); ?></div>
                            <div class="metric-label">Dias Ativos</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX: Get verifications list
     */
    public function ajaxGetVerifications(): void
    {
        check_ajax_referer('apollo_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        try {
            $filters = $this->sanitizeFilters($_POST);
            $verifications = $this->getVerificationsWithFilters($filters);
            
            wp_send_json_success([
                'verifications' => $verifications,
                'pagination' => [
                    'total' => count($verifications),
                    'current_page' => 1,
                    'total_pages' => 1
                ]
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Erro ao carregar verificações']);
        }
    }
    
    /**
     * AJAX: Get verification details
     */
    public function ajaxGetVerificationDetails(): void
    {
        check_ajax_referer('apollo_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $user_id = intval($_POST['user_id'] ?? 0);
        if (!$user_id) {
            wp_send_json_error(['message' => 'ID do usuário inválido']);
        }
        
        try {
            $verification = $this->getVerificationDetails($user_id);
            
            if (!$verification) {
                wp_send_json_error(['message' => 'Verificação não encontrada']);
            }
            
            wp_send_json_success($verification);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Erro ao carregar detalhes']);
        }
    }
    
    /**
     * AJAX: Get verification stats
     */
    public function ajaxGetVerificationStats(): void
    {
        check_ajax_referer('apollo_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        try {
            $stats = $this->userRepo->getVerificationStats();
            wp_send_json_success($stats);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Erro ao carregar estatísticas']);
        }
    }
    
    /**
     * AJAX: Approve verification
     */
    public function ajaxApproveVerification(): void
    {
        check_ajax_referer('apollo_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $user_id = intval($_POST['user_id'] ?? 0);
        if (!$user_id) {
            wp_send_json_error(['message' => 'ID do usuário inválido']);
        }
        
        try {
            $result = $this->approveVerification($user_id);
            
            if ($result) {
                wp_send_json_success(['message' => 'Verificação aprovada com sucesso']);
            } else {
                wp_send_json_error(['message' => 'Erro ao aprovar verificação']);
            }
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Erro interno no servidor']);
        }
    }
    
    /**
     * AJAX: Reject verification
     */
    public function ajaxRejectVerification(): void
    {
        check_ajax_referer('apollo_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $user_id = intval($_POST['user_id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        
        if (!$user_id) {
            wp_send_json_error(['message' => 'ID do usuário inválido']);
        }
        
        try {
            $result = $this->rejectVerification($user_id, $reason);
            
            if ($result) {
                wp_send_json_success(['message' => 'Verificação rejeitada']);
            } else {
                wp_send_json_error(['message' => 'Erro ao rejeitar verificação']);
            }
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Erro interno no servidor']);
        }
    }
    
    /**
     * Get verifications with filters
     */
    private function getVerificationsWithFilters(array $filters): array
    {
        global $wpdb;
        
        $verification_table = $wpdb->prefix . 'apollo_verifications';
        $users_table = $wpdb->users;
        
        $where_conditions = ['1=1'];
        $params = [];
        
        // Status filter
        if (!empty($filters['status'])) {
            $where_conditions[] = 'v.verify_status = %s';
            $params[] = $filters['status'];
        }
        
        // Search filter
        if (!empty($filters['search'])) {
            $search = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_conditions[] = '(u.display_name LIKE %s OR u.user_login LIKE %s OR v.instagram_username LIKE %s)';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        // Date filter
        if (!empty($filters['period'])) {
            switch ($filters['period']) {
                case 'today':
                    $where_conditions[] = 'DATE(v.submitted_at) = CURDATE()';
                    break;
                case 'week':
                    $where_conditions[] = 'v.submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
                    break;
                case 'month':
                    $where_conditions[] = 'v.submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
                    break;
            }
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "
            SELECT v.*, u.user_login, u.user_email, u.display_name, u.user_registered
            FROM {$verification_table} v
            INNER JOIN {$users_table} u ON v.user_id = u.ID
            WHERE {$where_clause}
            ORDER BY v.submitted_at DESC
            LIMIT 50
        ";
        
        $results = $wpdb->get_results(
            $wpdb->prepare($query, ...$params),
            ARRAY_A
        );
        
        // Parse JSON fields
        foreach ($results as &$result) {
            if (!empty($result['metadata'])) {
                $result['metadata'] = json_decode($result['metadata'], true) ?: [];
            }
            
            if (!empty($result['verify_assets'])) {
                $result['verify_assets'] = json_decode($result['verify_assets'], true) ?: [];
            }
        }
        
        return $results;
    }
    
    /**
     * Get verification details for modal
     */
    private function getVerificationDetails(int $user_id): ?array
    {
        global $wpdb;
        
        $verification_table = $wpdb->prefix . 'apollo_verifications';
        $users_table = $wpdb->users;
        
        $query = "
            SELECT v.*, u.user_login, u.user_email, u.display_name, u.user_registered
            FROM {$verification_table} v
            INNER JOIN {$users_table} u ON v.user_id = u.ID
            WHERE v.user_id = %d
            ORDER BY v.id DESC
            LIMIT 1
        ";
        
        $result = $wpdb->get_row(
            $wpdb->prepare($query, $user_id),
            ARRAY_A
        );
        
        if (!$result) {
            return null;
        }
        
        // Parse JSON fields
        if (!empty($result['metadata'])) {
            $result['metadata'] = json_decode($result['metadata'], true) ?: [];
        }
        
        if (!empty($result['verify_assets'])) {
            $result['verify_assets'] = json_decode($result['verify_assets'], true) ?: [];
        }
        
        return $result;
    }
    
    /**
     * Approve verification
     */
    private function approveVerification(int $user_id): bool
    {
        global $wpdb;
        
        $verification_table = $wpdb->prefix . 'apollo_verifications';
        $current_user_id = get_current_user_id();
        
        try {
            $wpdb->query('START TRANSACTION');
            
            // Update verification record
            $updated = $wpdb->update(
                $verification_table,
                [
                    'verify_status' => 'verified',
                    'reviewed_at' => current_time('mysql'),
                    'reviewer_id' => $current_user_id,
                    'rejection_reason' => null
                ],
                ['user_id' => $user_id],
                ['%s', '%s', '%d', '%s'],
                ['%d']
            );
            
            if ($updated === false) {
                throw new \Exception('Failed to update verification record');
            }
            
            // Update user meta
            update_user_meta($user_id, 'apollo_verify_status', 'verified');
            
            // Log action
            $this->logAdminAction($user_id, 'verification_approved', [
                'reviewer_id' => $current_user_id
            ]);
            
            $wpdb->query('COMMIT');
            return true;
            
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Apollo approve verification error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reject verification
     */
    private function rejectVerification(int $user_id, string $reason = ''): bool
    {
        global $wpdb;
        
        $verification_table = $wpdb->prefix . 'apollo_verifications';
        $current_user_id = get_current_user_id();
        
        try {
            $wpdb->query('START TRANSACTION');
            
            // Update verification record
            $updated = $wpdb->update(
                $verification_table,
                [
                    'verify_status' => 'rejected',
                    'reviewed_at' => current_time('mysql'),
                    'reviewer_id' => $current_user_id,
                    'rejection_reason' => $reason
                ],
                ['user_id' => $user_id],
                ['%s', '%s', '%d', '%s'],
                ['%d']
            );
            
            if ($updated === false) {
                throw new \Exception('Failed to update verification record');
            }
            
            // Update user meta
            update_user_meta($user_id, 'apollo_verify_status', 'rejected');
            
            // Log action
            $this->logAdminAction($user_id, 'verification_rejected', [
                'reviewer_id' => $current_user_id,
                'reason' => $reason
            ]);
            
            $wpdb->query('COMMIT');
            return true;
            
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Apollo reject verification error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log admin action
     */
    private function logAdminAction(int $user_id, string $action, array $data = []): void
    {
        global $wpdb;
        
        $audit_table = $wpdb->prefix . 'apollo_audit_log';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$audit_table}'") != $audit_table) {
            return;
        }
        
        $wpdb->insert($audit_table, [
            'user_id' => get_current_user_id(),
            'action' => $action,
            'entity_type' => 'verification',
            'entity_id' => $user_id,
            'metadata' => json_encode([
                'target_user_id' => $user_id,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'action_data' => $data
            ]),
            'created_at' => current_time('mysql')
        ]);
    }
    
    /**
     * Sanitize filters from request
     */
    private function sanitizeFilters(array $data): array
    {
        $filters = [];
        
        if (!empty($data['status'])) {
            $allowed_statuses = ['awaiting_instagram_verify', 'assets_submitted', 'verified', 'rejected'];
            if (in_array($data['status'], $allowed_statuses)) {
                $filters['status'] = $data['status'];
            }
        }
        
        if (!empty($data['search'])) {
            $filters['search'] = sanitize_text_field($data['search']);
        }
        
        if (!empty($data['period'])) {
            $allowed_periods = ['today', 'week', 'month'];
            if (in_array($data['period'], $allowed_periods)) {
                $filters['period'] = $data['period'];
            }
        }
        
        return $filters;
    }
    
    /**
     * Calculate average per day for analytics
     */
    private function calculateAveragePerDay(array $completions): string
    {
        if (empty($completions)) return '0';
        
        $total = array_sum(array_column($completions, 'completions'));
        $days = count($completions);
        
        return number_format($total / $days, 1);
    }
}