<?php

namespace Apollo\API\Endpoints;

use Apollo\Application\Users\BeginOnboarding;
use Apollo\Application\Users\CompleteOnboarding;
use Apollo\Application\Users\VerifyInstagram;
use Apollo\Application\Users\UserProfileRepository;

/**
 * OnboardingEndpoints
 * REST API endpoints for onboarding system
 */
class OnboardingEndpoints
{
    private UserProfileRepository $userRepo;
    private BeginOnboarding $beginOnboarding;
    private CompleteOnboarding $completeOnboarding;
    private VerifyInstagram $verifyInstagram;
    
    public function __construct()
    {
        $this->userRepo = new UserProfileRepository();
        $this->beginOnboarding = new BeginOnboarding();
        $this->completeOnboarding = new CompleteOnboarding();
        $this->verifyInstagram = new VerifyInstagram();
    }
    
    /**
     * Register all onboarding endpoints
     */
    public function registerEndpoints(): void
    {
        // Get onboarding options (industries, roles, memberships)
        register_rest_route('apollo/v1', '/onboarding/options', [
            'methods' => 'GET',
            'callback' => [$this, 'getOnboardingOptions'],
            'permission_callback' => [$this, 'checkUserPermission']
        ]);
        
        // Begin onboarding process
        register_rest_route('apollo/v1', '/onboarding/begin', [
            'methods' => 'POST',
            'callback' => [$this, 'beginOnboardingProcess'],
            'permission_callback' => [$this, 'checkUserPermission'],
            'args' => $this->getBeginOnboardingArgs()
        ]);
        
        // Complete onboarding process
        register_rest_route('apollo/v1', '/onboarding/complete', [
            'methods' => 'POST',
            'callback' => [$this, 'completeOnboardingProcess'],
            'permission_callback' => [$this, 'checkUserPermission'],
            'args' => $this->getCompleteOnboardingArgs()
        ]);
        
        // Request DM verification (user)
        register_rest_route('apollo/v1', '/onboarding/verify/request-dm', [
            'methods' => 'POST',
            'callback' => [$this, 'requestDmVerification'],
            'permission_callback' => [$this, 'checkUserPermission']
        ]);
        
        // Get verification status
        register_rest_route('apollo/v1', '/onboarding/verify/status', [
            'methods' => 'GET',
            'callback' => [$this, 'getVerificationStatus'],
            'permission_callback' => [$this, 'checkUserPermission']
        ]);
        
        // Confirm verification (admin/mod)
        register_rest_route('apollo/v1', '/onboarding/verify/confirm', [
            'methods' => 'POST',
            'callback' => [$this, 'confirmVerification'],
            'permission_callback' => [$this, 'checkAdminPermission']
        ]);
        
        // Cancel verification (admin/mod)
        register_rest_route('apollo/v1', '/onboarding/verify/cancel', [
            'methods' => 'POST',
            'callback' => [$this, 'cancelVerification'],
            'permission_callback' => [$this, 'checkAdminPermission']
        ]);
        
        // Get user profile
        register_rest_route('apollo/v1', '/onboarding/profile', [
            'methods' => 'GET',
            'callback' => [$this, 'getUserProfile'],
            'permission_callback' => [$this, 'checkUserPermission']
        ]);
    }
    
    /**
     * Get onboarding options (industries, roles, memberships)
     */
    public function getOnboardingOptions(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            $options = [
                'industries' => $this->userRepo->getIndustryOptions(),
                'roles' => $this->userRepo->getRoleOptions(),
                'memberships' => $this->userRepo->getMembershipOptions()
            ];
            
            return new \WP_REST_Response([
                'success' => true,
                'data' => $options
            ], 200);
            
        } catch (\Exception $e) {
            error_log('OnboardingEndpoints::getOnboardingOptions error: ' . $e->getMessage());
            
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Erro ao carregar opções'
            ], 500);
        }
    }
    
    /**
     * Begin onboarding process
     */
    public function beginOnboardingProcess(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            $user_id = get_current_user_id();
            if (!$user_id) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Usuário não autenticado'
                ], 401);
            }
            
            $data = $request->get_json_params();
            
            // Validate required fields
            $validation = $this->validateBeginOnboardingData($data);
            if (!$validation['valid']) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validation['errors']
                ], 400);
            }
            
            // Process onboarding
            $result = $this->beginOnboarding->handle($user_id, $data);
            
            return new \WP_REST_Response($result, $result['success'] ? 200 : 400);
            
        } catch (\Exception $e) {
            error_log('OnboardingEndpoints::beginOnboardingProcess error: ' . $e->getMessage());
            
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Erro interno no servidor'
            ], 500);
        }
    }
    
    /**
     * Complete onboarding process
     */
    public function completeOnboardingProcess(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            $user_id = get_current_user_id();
            if (!$user_id) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Usuário não autenticado'
                ], 401);
            }
            
            $data = $request->get_json_params();
            
            // Rate limiting check
            $rate_check = $this->completeOnboarding->checkRateLimit($user_id);
            if (!$rate_check['allowed']) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => "Aguarde {$rate_check['wait_time']} segundos"
                ], 429);
            }
            
            // Process completion
            $result = $this->completeOnboarding->handle($user_id, $data);
            
            return new \WP_REST_Response($result, $result['success'] ? 200 : 400);
            
        } catch (\Exception $e) {
            error_log('OnboardingEndpoints::completeOnboardingProcess error: ' . $e->getMessage());
            
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Erro interno no servidor'
            ], 500);
        }
    }
    
    /**
     * Request DM verification (user)
     */
    public function requestDmVerification(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            $user_id = get_current_user_id();
            if (!$user_id) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Usuário não autenticado'
                ], 401);
            }
            
            // Rate limiting: 1 request per minute
            $rate_check = $this->checkDmRequestRateLimit($user_id);
            if (!$rate_check['allowed']) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => "Aguarde {$rate_check['wait_time']} segundos antes de solicitar novamente"
                ], 429);
            }
            
            // Request DM verification
            $result = $this->verifyInstagram->requestDmVerification($user_id);
            
            return new \WP_REST_Response($result, $result['success'] ? 200 : 400);
            
        } catch (\Exception $e) {
            error_log('OnboardingEndpoints::requestDmVerification error: ' . $e->getMessage());
            
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Erro interno no servidor'
            ], 500);
        }
    }
    
    /**
     * Get verification status
     */
    public function getVerificationStatus(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            $user_id = get_current_user_id();
            if (!$user_id) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Usuário não autenticado'
                ], 401);
            }
            
            $status = $this->verifyInstagram->getVerificationStatus($user_id);
            
            return new \WP_REST_Response([
                'success' => true,
                'data' => $status
            ], 200);
            
        } catch (\Exception $e) {
            error_log('OnboardingEndpoints::getVerificationStatus error: ' . $e->getMessage());
            
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Erro ao carregar status'
            ], 500);
        }
    }
    
    /**
     * Confirm verification (admin/mod)
     */
    public function confirmVerification(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            if (!current_user_can('manage_options') && !current_user_can('edit_users')) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Sem permissão'
                ], 403);
            }
            
            $params = $request->get_json_params();
            $user_id = isset($params['user_id']) ? intval($params['user_id']) : 0;
            
            if (!$user_id) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'ID do usuário inválido'
                ], 400);
            }
            
            $result = $this->verifyInstagram->confirmVerification($user_id, get_current_user_id());
            
            return new \WP_REST_Response($result, $result['success'] ? 200 : 400);
            
        } catch (\Exception $e) {
            error_log('OnboardingEndpoints::confirmVerification error: ' . $e->getMessage());
            
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Erro interno no servidor'
            ], 500);
        }
    }
    
    /**
     * Cancel verification (admin/mod)
     */
    public function cancelVerification(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            if (!current_user_can('manage_options') && !current_user_can('edit_users')) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Sem permissão'
                ], 403);
            }
            
            $params = $request->get_json_params();
            $user_id = isset($params['user_id']) ? intval($params['user_id']) : 0;
            $reason = isset($params['reason']) ? sanitize_textarea_field($params['reason']) : '';
            
            if (!$user_id) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'ID do usuário inválido'
                ], 400);
            }
            
            $result = $this->verifyInstagram->cancelVerification($user_id, get_current_user_id(), $reason);
            
            return new \WP_REST_Response($result, $result['success'] ? 200 : 400);
            
        } catch (\Exception $e) {
            error_log('OnboardingEndpoints::cancelVerification error: ' . $e->getMessage());
            
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Erro interno no servidor'
            ], 500);
        }
    }
    
    /**
     * Get user profile
     */
    public function getUserProfile(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            $user_id = get_current_user_id();
            if (!$user_id) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Usuário não autenticado'
                ], 401);
            }
            
            $profile = $this->userRepo->getUserProfile($user_id);
            
            return new \WP_REST_Response([
                'success' => true,
                'data' => $profile
            ], 200);
            
        } catch (\Exception $e) {
            error_log('OnboardingEndpoints::getUserProfile error: ' . $e->getMessage());
            
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Erro ao carregar perfil'
            ], 500);
        }
    }
    
    /**
     * Check user permission for API access
     */
    public function checkUserPermission(\WP_REST_Request $request): bool
    {
        // Must be logged in
        if (!is_user_logged_in()) {
            return false;
        }
        
        // Rate limiting per IP (100 requests per hour)
        $ip = $this->getClientIp();
        $cache_key = "apollo_api_rate_limit_{$ip}";
        $requests = wp_cache_get($cache_key) ?: 0;
        
        if ($requests >= 100) {
            return false;
        }
        
        wp_cache_set($cache_key, $requests + 1, '', HOUR_IN_SECONDS);
        
        return true;
    }
    
    /**
     * Check admin/mod permission for API access
     */
    public function checkAdminPermission(\WP_REST_Request $request): bool
    {
        return current_user_can('manage_options') || current_user_can('edit_users');
    }
    
    /**
     * Check DM request rate limit (1 per minute)
     */
    private function checkDmRequestRateLimit(int $user_id): array
    {
        $cache_key = "apollo_dm_request_rate_limit_{$user_id}";
        $last_request = wp_cache_get($cache_key);
        
        if ($last_request && (time() - $last_request) < 60) {
            return [
                'allowed' => false,
                'wait_time' => 60 - (time() - $last_request)
            ];
        }
        
        wp_cache_set($cache_key, time(), '', 60);
        
        return ['allowed' => true];
    }
    
    /**
     * Validate begin onboarding data
     */
    private function validateBeginOnboardingData(array $data): array
    {
        $errors = [];
        
        // Required fields
        $required_fields = ['name', 'industry'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $errors[$field] = "Campo {$field} é obrigatório";
            }
        }
        
        // Validate industry
        if (!empty($data['industry'])) {
            $industries = $this->userRepo->getIndustryOptions();
            if (!isset($industries[$data['industry']])) {
                $errors['industry'] = 'Indústria inválida';
            }
        }
        
        // Validate roles
        if (!empty($data['roles']) && is_array($data['roles'])) {
            $valid_roles = array_keys($this->userRepo->getRoleOptions());
            foreach ($data['roles'] as $role) {
                if (!in_array($role, $valid_roles)) {
                    $errors['roles'] = 'Função inválida detectada';
                    break;
                }
            }
        }
        
        // Validate memberships
        if (!empty($data['member_of']) && is_array($data['member_of'])) {
            $valid_memberships = array_keys($this->userRepo->getMembershipOptions());
            foreach ($data['member_of'] as $membership) {
                if (!in_array($membership, $valid_memberships)) {
                    $errors['member_of'] = 'Membro inválido detectado';
                    break;
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
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
    
    /**
     * Get args for begin onboarding endpoint
     */
    private function getBeginOnboardingArgs(): array
    {
        return [
            'name' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param) {
                    return !empty($param) && strlen($param) <= 100;
                }
            ],
            'industry' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'roles' => [
                'required' => false,
                'type' => 'array'
            ],
            'member_of' => [
                'required' => false,
                'type' => 'array'
            ],
            'whatsapp' => [
                'required' => false,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'instagram' => [
                'required' => false,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ]
        ];
    }
    
    /**
     * Get args for complete onboarding endpoint
     */
    private function getCompleteOnboardingArgs(): array
    {
        return [
            'confirm' => [
                'required' => true,
                'type' => 'boolean'
            ]
        ];
    }
}