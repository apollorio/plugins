<?php

namespace Apollo\Application\Users;

use Apollo\Infrastructure\Repositories\UserProfileRepository;

/**
 * BeginOnboarding
 * Handles initial onboarding data collection and validation
 */
class BeginOnboarding
{
    private UserProfileRepository $userRepo;
    
    public function __construct()
    {
        $this->userRepo = new UserProfileRepository();
    }
    
    /**
     * Start onboarding process for user
     */
    public function handle(int $user_id, array $data): array
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
            
            // Validate required data
            $validation = $this->validateOnboardingData($data);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validation['errors']
                ];
            }
            
            // Sanitize and normalize data
            $sanitized_data = $this->sanitizeOnboardingData($data);
            
            // Check for Instagram username conflicts
            $instagram_check = $this->checkInstagramAvailability($sanitized_data['instagram'], $user_id);
            if (!$instagram_check['available']) {
                return [
                    'success' => false,
                    'message' => 'Instagram já cadastrado',
                    'suggestion' => $instagram_check['suggestion']
                ];
            }
            
            // Generate verification token
            $verify_token = $this->generateVerificationToken($sanitized_data['instagram']);
            
            // Save onboarding progress
            $this->saveOnboardingProgress($user_id, $sanitized_data, $verify_token);
            
            // Update username if needed
            $this->updateUsername($user, $sanitized_data['instagram']);
            
            // Log onboarding start
            $this->logOnboardingEvent($user_id, 'onboarding_started', $sanitized_data);
            
            return [
                'success' => true,
                'message' => 'Onboarding iniciado com sucesso',
                'verify_token' => $verify_token,
                'progress' => $this->getUserProgress($user_id)
            ];
            
        } catch (\Exception $e) {
            error_log('BeginOnboarding error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro interno. Tente novamente.'
            ];
        }
    }
    
    /**
     * Validate onboarding data
     */
    private function validateOnboardingData(array $data): array
    {
        $errors = [];
        
        // Name validation
        if (empty($data['name']) || strlen(trim($data['name'])) < 2) {
            $errors['name'] = 'Nome deve ter pelo menos 2 caracteres';
        }
        
        // Industry validation
        if (empty($data['industry']) || !in_array($data['industry'], ['Yes', 'No', 'Future yes!'])) {
            $errors['industry'] = 'Selecione uma opção válida para indústria';
        }
        
        // Roles validation (if industry member)
        if (in_array($data['industry'], ['Yes', 'Future yes!'])) {
            if (empty($data['roles']) || !is_array($data['roles'])) {
                $errors['roles'] = 'Selecione pelo menos um role';
            }
        }
        
        // WhatsApp validation
        if (empty($data['whatsapp'])) {
            $errors['whatsapp'] = 'WhatsApp é obrigatório';
        } else {
            $normalized_whatsapp = $this->normalizeWhatsapp($data['whatsapp']);
            if (!$this->isValidWhatsapp($normalized_whatsapp)) {
                $errors['whatsapp'] = 'WhatsApp inválido';
            }
        }
        
        // Instagram validation
        if (empty($data['instagram'])) {
            $errors['instagram'] = 'Instagram é obrigatório';
        } else {
            $normalized_instagram = $this->normalizeInstagram($data['instagram']);
            if (!$this->isValidInstagram($normalized_instagram)) {
                $errors['instagram'] = 'Instagram inválido';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Sanitize onboarding data
     */
    private function sanitizeOnboardingData(array $data): array
    {
        return [
            'name' => sanitize_text_field(trim($data['name'])),
            'industry' => sanitize_text_field($data['industry']),
            'roles' => isset($data['roles']) ? array_map('sanitize_text_field', $data['roles']) : [],
            'member_of' => isset($data['member_of']) ? array_map('sanitize_text_field', $data['member_of']) : [],
            'whatsapp' => $this->normalizeWhatsapp($data['whatsapp']),
            'instagram' => $this->normalizeInstagram($data['instagram'])
        ];
    }
    
    /**
     * Check Instagram username availability
     */
    private function checkInstagramAvailability(string $instagram, int $current_user_id): array
    {
        global $wpdb;
        
        // Check if Instagram is already taken by another user
        $existing_user = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} 
             WHERE meta_key = 'apollo_instagram' 
             AND meta_value = %s 
             AND user_id != %d",
            $instagram,
            $current_user_id
        ));
        
        if ($existing_user) {
            // Suggest variations
            $suggestion = $this->suggestUsernameVariation($instagram);
            
            return [
                'available' => false,
                'suggestion' => $suggestion
            ];
        }
        
        return ['available' => true];
    }
    
    /**
     * Suggest username variation
     */
    private function suggestUsernameVariation(string $base_username): string
    {
        global $wpdb;
        
        $counter = 1;
        
        do {
            $suggestion = $base_username . $counter;
            
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} 
                 WHERE meta_key = 'apollo_instagram' 
                 AND meta_value = %s",
                $suggestion
            ));
            
            if (!$exists) {
                return $suggestion;
            }
            
            $counter++;
        } while ($counter <= 999);
        
        // Fallback with timestamp
        return $base_username . time();
    }
    
    /**
     * Generate verification token
     */
    private function generateVerificationToken(string $instagram): string
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('America/Sao_Paulo'));
        return $now->format('Ymd') . strtolower($instagram);
    }
    
    /**
     * Save onboarding progress
     */
    private function saveOnboardingProgress(int $user_id, array $data, string $verify_token): void
    {
        // Save individual meta fields
        update_user_meta($user_id, 'apollo_name', $data['name']);
        update_user_meta($user_id, 'apollo_industry', $data['industry']);
        update_user_meta($user_id, 'apollo_roles', $data['roles']);
        update_user_meta($user_id, 'apollo_member_of', $data['member_of']);
        update_user_meta($user_id, 'apollo_whatsapp', $data['whatsapp']);
        update_user_meta($user_id, 'apollo_instagram', $data['instagram']);
        update_user_meta($user_id, 'apollo_verify_token', $verify_token);
        update_user_meta($user_id, 'apollo_verify_status', 'awaiting_instagram_verify');
        update_user_meta($user_id, 'apollo_verify_assets', []);
        
        // Save progress state
        update_user_meta($user_id, 'apollo_onboarding_progress', [
            'current_step' => 'verification_rules',
            'completed_steps' => [
                'ask_name', 'ask_industry', 'ask_roles', 
                'ask_memberships', 'ask_contacts'
            ],
            'started_at' => current_time('mysql'),
            'data' => $data
        ]);
    }
    
    /**
     * Update WordPress username based on Instagram
     */
    private function updateUsername(\WP_User $user, string $instagram): void
    {
        $desired_username = $instagram;
        
        // Check if username is already the same
        if ($user->user_login === $desired_username) {
            return;
        }
        
        // Check if desired username is available
        if (!username_exists($desired_username)) {
            // Update username
            wp_update_user([
                'ID' => $user->ID,
                'user_login' => $desired_username
            ]);
            
            // Update user_nicename as well
            global $wpdb;
            $wpdb->update(
                $wpdb->users,
                ['user_nicename' => $desired_username],
                ['ID' => $user->ID]
            );
        }
    }
    
    /**
     * Get user progress
     */
    private function getUserProgress(int $user_id): array
    {
        $progress = get_user_meta($user_id, 'apollo_onboarding_progress', true);
        return is_array($progress) ? $progress : [];
    }
    
    /**
     * Log onboarding event
     */
    private function logOnboardingEvent(int $user_id, string $event, array $data = []): void
    {
        global $wpdb;
        
        $audit_table = $wpdb->prefix . 'apollo_audit_log';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$audit_table}'") != $audit_table) {
            return; // Table doesn't exist yet
        }
        
        $wpdb->insert($audit_table, [
            'user_id' => $user_id,
            'action' => $event,
            'entity_type' => 'user',
            'entity_id' => $user_id,
            'metadata' => json_encode([
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip_address' => $this->getClientIp(),
                'data' => $data
            ]),
            'created_at' => current_time('mysql')
        ]);
    }
    
    /**
     * Normalize WhatsApp number
     */
    private function normalizeWhatsapp(string $whatsapp): string
    {
        $digits = preg_replace('/\D+/', '', $whatsapp);
        
        // Add country code if needed
        if (strlen($digits) === 11) {
            $digits = '55' . $digits;
        }
        
        return '+' . ltrim($digits, '+');
    }
    
    /**
     * Normalize Instagram username
     */
    private function normalizeInstagram(string $instagram): string
    {
        $instagram = trim($instagram);
        $instagram = ltrim($instagram, '@');
        return strtolower($instagram);
    }
    
    /**
     * Validate WhatsApp format
     */
    private function isValidWhatsapp(string $whatsapp): bool
    {
        // Remove + and check if all digits
        $digits = ltrim($whatsapp, '+');
        return ctype_digit($digits) && strlen($digits) >= 10 && strlen($digits) <= 15;
    }
    
    /**
     * Validate Instagram username
     */
    private function isValidInstagram(string $instagram): bool
    {
        return preg_match('/^[a-zA-Z0-9._]{1,30}$/', $instagram);
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