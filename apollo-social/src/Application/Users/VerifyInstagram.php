<?php

namespace Apollo\Application\Users;

/**
 * VerifyInstagram
 * Handles Instagram verification via DM (no upload)
 */
class VerifyInstagram
{
    /**
     * Request DM verification
     */
    public function requestDmVerification(int $user_id): array
    {
        try {
            // Validate user and verification status
            $validation = $this->validateDmRequest($user_id);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message']
                ];
            }
            
            // Get or generate token
            $instagram = get_user_meta($user_id, 'apollo_instagram', true);
            $verify_token = $this->buildVerifyToken($instagram);
            
            // Update verification status to dm_requested
            $this->updateVerificationStatus($user_id, 'dm_requested', $verify_token);
            
            // Log event
            $this->logVerificationEvent($user_id, 'verification_dm_requested', [
                'token' => $verify_token,
                'instagram' => $instagram
            ]);
            
            return [
                'success' => true,
                'token' => $verify_token,
                'ig_username' => $instagram,
                'status' => 'dm_requested',
                'phrase' => $this->buildVerificationPhrase($instagram, $verify_token)
            ];
            
        } catch (\Exception $e) {
            error_log('VerifyInstagram::requestDmVerification error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro interno. Tente novamente.'
            ];
        }
    }
    
    /**
     * Confirm verification (admin/mod)
     */
    public function confirmVerification(int $user_id, int $reviewer_id): array
    {
        try {
            // Validate user exists
            $user = get_user_by('ID', $user_id);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Usuário não encontrado'
                ];
            }
            
            // Update verification status
            $this->updateVerificationStatus($user_id, 'verified', null, $reviewer_id);
            
            // Send email
            $this->sendAccountReleasedEmail($user_id);
            
            // Log event
            $this->logVerificationEvent($user_id, 'verification_approved', [
                'reviewer_id' => $reviewer_id
            ]);
            
            return [
                'success' => true,
                'message' => 'Verificação confirmada com sucesso'
            ];
            
        } catch (\Exception $e) {
            error_log('VerifyInstagram::confirmVerification error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro interno. Tente novamente.'
            ];
        }
    }
    
    /**
     * Cancel verification (admin/mod)
     */
    public function cancelVerification(int $user_id, int $reviewer_id, string $reason = ''): array
    {
        try {
            // Validate user exists
            $user = get_user_by('ID', $user_id);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Usuário não encontrado'
                ];
            }
            
            // Determine new status
            $new_status = !empty($reason) ? 'rejected' : 'awaiting_instagram_verify';
            
            // Update verification status
            $this->updateVerificationStatus($user_id, $new_status, null, $reviewer_id, $reason);
            
            // Log event
            $event = !empty($reason) ? 'verification_rejected' : 'verification_canceled';
            $this->logVerificationEvent($user_id, $event, [
                'reviewer_id' => $reviewer_id,
                'reason' => $reason
            ]);
            
            return [
                'success' => true,
                'message' => $new_status === 'rejected' ? 'Verificação rejeitada' : 'Verificação cancelada'
            ];
            
        } catch (\Exception $e) {
            error_log('VerifyInstagram::cancelVerification error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro interno. Tente novamente.'
            ];
        }
    }
    
    /**
     * Get verification status for user
     */
    public function getVerificationStatus(int $user_id): array
    {
        global $wpdb;
        
        $verification_table = $wpdb->prefix . 'apollo_verifications';
        
        $verification = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$verification_table} WHERE user_id = %d ORDER BY id DESC LIMIT 1",
            $user_id
        ), ARRAY_A);
        
        if (!$verification) {
            return [
                'status' => 'not_found',
                'message' => 'Verificação não encontrada'
            ];
        }
        
        $instagram = get_user_meta($user_id, 'apollo_instagram', true);
        $verify_token = $verification['verify_token'] ?? $this->buildVerifyToken($instagram);
        
        return [
            'status' => $verification['verify_status'],
            'instagram_username' => $instagram,
            'verify_token' => $verify_token,
            'submitted_at' => $verification['submitted_at'],
            'reviewed_at' => $verification['reviewed_at'],
            'reviewer_id' => $verification['reviewer_id'],
            'rejection_reason' => $verification['rejection_reason'],
            'phrase' => $this->buildVerificationPhrase($instagram, $verify_token)
        ];
    }
    
    /**
     * Validate DM request
     */
    private function validateDmRequest(int $user_id): array
    {
        // Check if user exists and is onboarded
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return [
                'valid' => false,
                'message' => 'Usuário não encontrado'
            ];
        }
        
        // Check if onboarding is completed
        $onboarded = get_user_meta($user_id, 'apollo_onboarded', true);
        if (!$onboarded) {
            return [
                'valid' => false,
                'message' => 'Complete o onboarding primeiro'
            ];
        }
        
        // Check verification record exists
        global $wpdb;
        $verification_table = $wpdb->prefix . 'apollo_verifications';
        
        $verification = $wpdb->get_row($wpdb->prepare(
            "SELECT verify_status FROM {$verification_table} WHERE user_id = %d ORDER BY id DESC LIMIT 1",
            $user_id
        ));
        
        if (!$verification) {
            return [
                'valid' => false,
                'message' => 'Registro de verificação não encontrado'
            ];
        }
        
        // Check if already verified
        if ($verification->verify_status === 'verified') {
            return [
                'valid' => false,
                'message' => 'Conta já verificada'
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Update verification status
     */
    private function updateVerificationStatus(int $user_id, string $status, ?string $token = null, ?int $reviewer_id = null, string $reason = ''): void
    {
        global $wpdb;
        
        $verification_table = $wpdb->prefix . 'apollo_verifications';
        
        $update_data = [
            'verify_status' => $status
        ];
        
        if ($token !== null) {
            $update_data['verify_token'] = $token;
        }
        
        if ($status === 'verified' || $status === 'rejected') {
            $update_data['reviewed_at'] = current_time('mysql');
            if ($reviewer_id) {
                $update_data['reviewer_id'] = $reviewer_id;
            }
        }
        
        if ($status === 'rejected' && !empty($reason)) {
            $update_data['rejection_reason'] = $reason;
        }
        
        if ($status === 'dm_requested') {
            $update_data['submitted_at'] = current_time('mysql');
        }
        
        // Build format array for each field
        $formats = [];
        foreach ($update_data as $key => $value) {
            if ($key === 'reviewer_id') {
                $formats[] = '%d';
            } else {
                $formats[] = '%s';
            }
        }
        
        $wpdb->update(
            $verification_table,
            $update_data,
            ['user_id' => $user_id],
            $formats,
            ['%d']
        );
        
        // Update user meta
        update_user_meta($user_id, 'apollo_verify_status', $status);
        if ($token) {
            update_user_meta($user_id, 'apollo_verify_token', $token);
        }
    }
    
    /**
     * Build verification token (deterministic: YYYYMMDD_username)
     */
    private function buildVerifyToken(string $instagram): string
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('America/Sao_Paulo'));
        $date_str = $now->format('Ymd');
        $username = strtolower(trim($instagram, '@'));
        
        return $date_str . '_' . $username;
    }
    
    /**
     * Build verification phrase
     */
    private function buildVerificationPhrase(string $instagram, string $token): string
    {
        $username = trim($instagram, '@');
        return "eu sou @{$username} no apollo :: {$token}";
    }
    
    /**
     * Send account released email
     */
    private function sendAccountReleasedEmail(int $user_id): void
    {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return;
        }
        
        $subject = 'Apollo — Account Released';
        $message = "EMAIL ACCOUN RELEASED! WELCOME TO OUR WORLD, WELCOME TO APOLLO!\n\n";
        $message .= "Sua conta foi verificada e liberada. Bem-vindo ao Apollo!\n\n";
        $message .= "Acesse: " . home_url() . "\n\n";
        $message .= "Equipe Apollo";
        
        wp_mail($user->user_email, $subject, $message);
        
        // Log email sent
        $this->logVerificationEvent($user_id, 'account_released_email_sent', []);
    }
    
    /**
     * Log verification event
     */
    private function logVerificationEvent(int $user_id, string $event, array $data): void
    {
        global $wpdb;
        
        $audit_table = $wpdb->prefix . 'apollo_audit_log';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$audit_table}'") != $audit_table) {
            return;
        }
        
        $ip = $this->getClientIp();
        $ip_hash = hash('sha256', $ip);
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ua_hash = hash('sha256', $ua);
        
        $wpdb->insert($audit_table, [
            'user_id' => $user_id,
            'action' => $event,
            'entity_type' => 'verification',
            'entity_id' => $user_id,
            'metadata' => json_encode([
                'ip_hash' => $ip_hash,
                'ua_hash' => $ua_hash,
                'data' => $data
            ]),
            'created_at' => current_time('mysql')
        ]);
    }
    
    /**
     * Get client IP address
     */
    private function getClientIp(): string
    {
        $ip_headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                return $_SERVER[$header];
            }
        }
        
        return 'unknown';
    }
}
