<?php

namespace Apollo\API;

use Apollo\API\Endpoints\OnboardingEndpoints;

/**
 * APIRegister
 * Registers all Apollo REST API endpoints
 */
class APIRegister
{
    private OnboardingEndpoints $onboardingEndpoints;
    
    public function __construct()
    {
        $this->onboardingEndpoints = new OnboardingEndpoints();
    }
    
    /**
     * Initialize API registration
     */
    public function init(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
        add_action('rest_api_init', [$this, 'addCorsHeaders']);
    }
    
    /**
     * Register all API routes
     */
    public function registerRoutes(): void
    {
        // Register onboarding endpoints
        $this->onboardingEndpoints->registerEndpoints();
        
        // Add API documentation endpoint
        register_rest_route('apollo/v1', '/docs', [
            'methods' => 'GET',
            'callback' => [$this, 'getApiDocumentation'],
            'permission_callback' => '__return_true'
        ]);
        
        // Add health check endpoint
        register_rest_route('apollo/v1', '/health', [
            'methods' => 'GET',
            'callback' => [$this, 'healthCheck'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    /**
     * Add CORS headers for API requests
     */
    public function addCorsHeaders(): void
    {
        add_action('rest_pre_serve_request', function($served, $result, $request, $server) {
            $origin = get_http_origin();
            
            // Allow requests from same domain and Canvas Mode
            if ($origin && $this->isAllowedOrigin($origin)) {
                header("Access-Control-Allow-Origin: {$origin}");
            }
            
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce');
            header('Access-Control-Allow-Credentials: true');
            
            return $served;
        }, 10, 4);
    }
    
    /**
     * Check if origin is allowed for CORS
     */
    private function isAllowedOrigin(string $origin): bool
    {
        $site_url = get_site_url();
        $allowed_origins = [
            $site_url,
            str_replace('http://', 'https://', $site_url),
            str_replace('https://', 'http://', $site_url)
        ];
        
        // Allow localhost for development
        if (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false) {
            return true;
        }
        
        return in_array($origin, $allowed_origins);
    }
    
    /**
     * API documentation endpoint
     */
    public function getApiDocumentation(\WP_REST_Request $request): \WP_REST_Response
    {
        $documentation = [
            'version' => '1.0.0',
            'name' => 'Apollo Onboarding API',
            'description' => 'REST API for Apollo conversational onboarding system',
            'endpoints' => [
                'GET /apollo/v1/onboarding/options' => [
                    'description' => 'Get available industries, roles, and membership options',
                    'authentication' => 'required',
                    'response' => [
                        'industries' => 'object',
                        'roles' => 'object', 
                        'memberships' => 'object'
                    ]
                ],
                'POST /apollo/v1/onboarding/begin' => [
                    'description' => 'Begin onboarding process and validate user data',
                    'authentication' => 'required',
                    'parameters' => [
                        'name' => 'string (required)',
                        'industry' => 'string (required)',
                        'roles' => 'array (optional)',
                        'member_of' => 'array (optional)',
                        'whatsapp' => 'string (optional)',
                        'instagram' => 'string (optional)'
                    ]
                ],
                'POST /apollo/v1/onboarding/complete' => [
                    'description' => 'Complete onboarding and create verification record',
                    'authentication' => 'required',
                    'parameters' => [
                        'confirm' => 'boolean (required)'
                    ]
                ],
                'POST /apollo/v1/onboarding/verify/upload' => [
                    'description' => 'Upload Instagram verification images',
                    'authentication' => 'required',
                    'parameters' => [
                        'verification_images' => 'file[] (1-3 images, max 5MB each)'
                    ]
                ],
                'GET /apollo/v1/onboarding/verify/status' => [
                    'description' => 'Get current verification status',
                    'authentication' => 'required'
                ],
                'DELETE /apollo/v1/onboarding/verify/delete' => [
                    'description' => 'Delete verification assets for re-upload',
                    'authentication' => 'required'
                ],
                'GET /apollo/v1/onboarding/profile' => [
                    'description' => 'Get user onboarding profile data',
                    'authentication' => 'required'
                ]
            ],
            'authentication' => [
                'type' => 'WordPress Authentication',
                'description' => 'Uses WordPress user authentication and nonce verification',
                'headers' => [
                    'X-WP-Nonce' => 'WordPress nonce for CSRF protection'
                ]
            ],
            'rate_limiting' => [
                'description' => 'Rate limited to 100 requests per hour per IP',
                'headers' => [
                    'X-RateLimit-Limit' => 'Request limit per hour',
                    'X-RateLimit-Remaining' => 'Remaining requests'
                ]
            ],
            'errors' => [
                'format' => [
                    'success' => false,
                    'message' => 'Human readable error message',
                    'errors' => 'Field-specific validation errors (optional)'
                ],
                'http_codes' => [
                    '200' => 'Success',
                    '400' => 'Bad Request / Validation Error',
                    '401' => 'Unauthorized',
                    '429' => 'Rate Limited',
                    '500' => 'Internal Server Error'
                ]
            ]
        ];
        
        return new \WP_REST_Response($documentation, 200);
    }
    
    /**
     * Health check endpoint
     */
    public function healthCheck(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;
        
        $health = [
            'status' => 'healthy',
            'timestamp' => current_time('mysql'),
            'version' => '1.0.0',
            'checks' => []
        ];
        
        // Database check
        try {
            $wpdb->get_var("SELECT 1");
            $health['checks']['database'] = 'ok';
        } catch (\Exception $e) {
            $health['checks']['database'] = 'error';
            $health['status'] = 'unhealthy';
        }
        
        // Tables check
        $required_tables = [
            $wpdb->prefix . 'apollo_verifications',
            $wpdb->prefix . 'apollo_audit_log',
            $wpdb->prefix . 'apollo_analytics_events'
        ];
        
        $missing_tables = [];
        foreach ($required_tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") != $table) {
                $missing_tables[] = $table;
            }
        }
        
        if (empty($missing_tables)) {
            $health['checks']['tables'] = 'ok';
        } else {
            $health['checks']['tables'] = 'missing: ' . implode(', ', $missing_tables);
            $health['status'] = 'degraded';
        }
        
        // File permissions check
        $upload_dir = wp_upload_dir();
        if (is_writable($upload_dir['basedir'])) {
            $health['checks']['file_permissions'] = 'ok';
        } else {
            $health['checks']['file_permissions'] = 'uploads directory not writable';
            $health['status'] = 'degraded';
        }
        
        // API availability check
        $health['checks']['rest_api'] = 'ok';
        
        $status_code = $health['status'] === 'healthy' ? 200 : ($health['status'] === 'degraded' ? 200 : 503);
        
        return new \WP_REST_Response($health, $status_code);
    }
}