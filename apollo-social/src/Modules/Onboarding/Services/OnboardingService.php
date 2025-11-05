<?php

namespace Apollo\Modules\Onboarding\Services;

/**
 * Conversational Onboarding Service
 * 
 * Handles chat-style onboarding flow with social verification.
 */
class OnboardingService
{
    private array $onboarding_steps = [
        'welcome' => [
            'title' => 'Bem-vindo ao Apollo Social! 👋',
            'message' => 'Olá! Eu sou o assistente Apollo. Vou te ajudar a configurar seu perfil em poucos passos. Pronto para começar?',
            'buttons' => ['🚀 Vamos começar!', '❓ Preciso de ajuda'],
            'next_step' => 'profile_basic'
        ],
        'profile_basic' => [
            'title' => 'Vamos conhecer você melhor 😊',
            'message' => 'Como gostaria de ser chamado(a) na plataforma?',
            'input_type' => 'text',
            'input_placeholder' => 'Digite seu nome ou apelido',
            'validation' => 'required|min:2',
            'next_step' => 'location'
        ],
        'location' => [
            'title' => 'Onde você está localizado? 📍',
            'message' => 'Isso nos ajuda a conectar você com pessoas e eventos próximos.',
            'input_type' => 'location',
            'input_placeholder' => 'Digite sua cidade ou região',
            'buttons' => ['🌍 Detectar automaticamente', '⏭️ Pular este passo'],
            'next_step' => 'interests'
        ],
        'interests' => [
            'title' => 'Quais são seus interesses? ⭐',
            'message' => 'Selecione os temas que mais te interessam (pode escolher vários):',
            'input_type' => 'multi_select',
            'options' => [
                'tecnologia' => '💻 Tecnologia',
                'cultura' => '🎭 Cultura',
                'esportes' => '⚽ Esportes',
                'educacao' => '📚 Educação',
                'meio_ambiente' => '🌱 Meio Ambiente',
                'saude' => '🏥 Saúde',
                'arte' => '🎨 Arte',
                'musica' => '🎵 Música',
                'gastronomia' => '🍽️ Gastronomia',
                'empreendedorismo' => '💼 Empreendedorismo'
            ],
            'next_step' => 'social_verification'
        ],
        'social_verification' => [
            'title' => 'Verificação Social 🔐',
            'message' => 'Para aumentar a confiança na plataforma, você pode verificar suas redes sociais:',
            'buttons' => [
                '📱 WhatsApp',
                '📸 Instagram', 
                '🐦 Twitter',
                '⏭️ Verificar depois'
            ],
            'next_step' => 'verification_code'
        ],
        'verification_code' => [
            'title' => 'Código de Verificação 📨',
            'message' => 'Enviamos um código para sua conta. Digite o código recebido:',
            'input_type' => 'verification_code',
            'input_placeholder' => 'Digite o código de 6 dígitos',
            'validation' => 'required|digits:6',
            'next_step' => 'privacy_settings'
        ],
        'privacy_settings' => [
            'title' => 'Configurações de Privacidade 🛡️',
            'message' => 'Como você gostaria que seu perfil fosse visível?',
            'input_type' => 'select',
            'options' => [
                'public' => '🌍 Público - Todos podem ver',
                'community' => '👥 Comunidade - Apenas membros Apollo',
                'private' => '🔒 Privado - Apenas amigos'
            ],
            'next_step' => 'completion'
        ],
        'completion' => [
            'title' => 'Parabéns! Tudo pronto! 🎉',
            'message' => 'Seu perfil foi configurado com sucesso. Agora você pode explorar grupos, criar eventos e conectar-se com a comunidade!',
            'buttons' => ['🏠 Ir para Dashboard', '👥 Explorar Grupos', '📅 Ver Eventos'],
            'completion' => true
        ]
    ];

    /**
     * Start onboarding session
     */
    public function startOnboarding(int $user_id): array
    {
        $session_id = $this->generateSessionId();
        
        $session_data = [
            'user_id' => $user_id,
            'session_id' => $session_id,
            'current_step' => 'welcome',
            'completed_steps' => [],
            'user_data' => [],
            'started_at' => current_time('mysql'),
            'last_activity' => current_time('mysql')
        ];
        
        $this->saveOnboardingSession($session_data);
        
        return [
            'session_id' => $session_id,
            'step_data' => $this->getStepData('welcome'),
            'progress' => $this->calculateProgress('welcome')
        ];
    }

    /**
     * Process step response
     */
    public function processStep(string $session_id, string $step, array $response_data): array
    {
        $session = $this->getOnboardingSession($session_id);
        
        if (!$session) {
            return ['error' => 'Sessão de onboarding não encontrada'];
        }
        
        // Validate response
        $validation = $this->validateStepResponse($step, $response_data);
        if (!$validation['valid']) {
            return [
                'error' => $validation['message'],
                'step_data' => $this->getStepData($step),
                'progress' => $this->calculateProgress($step)
            ];
        }
        
        // Save step data
        $session['completed_steps'][] = $step;
        $session['user_data'][$step] = $response_data;
        $session['last_activity'] = current_time('mysql');
        
        // Handle special steps
        switch ($step) {
            case 'social_verification':
                $verification_result = $this->initiateSocialVerification($response_data['platform'], $session['user_id']);
                $session['verification_pending'] = $verification_result;
                break;
                
            case 'verification_code':
                $verification_valid = $this->validateVerificationCode($session['verification_pending'], $response_data['code']);
                if (!$verification_valid) {
                    return [
                        'error' => 'Código de verificação inválido',
                        'step_data' => $this->getStepData($step),
                        'progress' => $this->calculateProgress($step)
                    ];
                }
                $session['verified_platforms'][] = $session['verification_pending']['platform'];
                break;
        }
        
        // Get next step
        $next_step = $this->onboarding_steps[$step]['next_step'] ?? null;
        
        if (!$next_step || $this->onboarding_steps[$next_step]['completion'] ?? false) {
            // Complete onboarding
            $this->completeOnboarding($session);
            
            return [
                'completed' => true,
                'step_data' => $this->getStepData('completion'),
                'progress' => 100,
                'badges_earned' => $this->awardOnboardingBadges($session['user_id'], $session)
            ];
        }
        
        // Update session
        $session['current_step'] = $next_step;
        $this->saveOnboardingSession($session);
        
        return [
            'session_id' => $session_id,
            'step_data' => $this->getStepData($next_step),
            'progress' => $this->calculateProgress($next_step),
            'completed_steps' => $session['completed_steps']
        ];
    }

    /**
     * Generate verification token for social platforms
     */
    public function generateVerificationToken(int $user_id, string $platform): array
    {
        $token = strtoupper(wp_generate_password(6, false, false));
        $token_data = [
            'user_id' => $user_id,
            'platform' => $platform,
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+15 minutes')),
            'used' => false
        ];
        
        $this->saveVerificationToken($token_data);
        
        return [
            'token' => $token,
            'expires_in' => 900, // 15 minutes
            'instructions' => $this->getVerificationInstructions($platform, $token)
        ];
    }

    /**
     * Get verification instructions for each platform
     */
    private function getVerificationInstructions(string $platform, string $token): array
    {
        $instructions = [
            'whatsapp' => [
                'title' => 'Verificação via WhatsApp',
                'steps' => [
                    "1. Abra o WhatsApp",
                    "2. Envie uma mensagem para +55 11 99999-9999",
                    "3. Digite exatamente: APOLLO {$token}",
                    "4. Aguarde a confirmação e volte aqui"
                ],
                'qr_code_url' => "https://wa.me/5511999999999?text=APOLLO%20{$token}",
                'deep_link' => "whatsapp://send?phone=5511999999999&text=APOLLO%20{$token}"
            ],
            'instagram' => [
                'title' => 'Verificação via Instagram',
                'steps' => [
                    "1. Abra o Instagram",
                    "2. Siga @apollo.social.oficial",
                    "3. Envie uma DM com o código: {$token}",
                    "4. Aguarde a confirmação automática"
                ],
                'profile_url' => 'https://instagram.com/apollo.social.oficial',
                'dm_link' => "https://instagram.com/apollo.social.oficial"
            ],
            'twitter' => [
                'title' => 'Verificação via Twitter',
                'steps' => [
                    "1. Abra o Twitter/X",
                    "2. Siga @ApolloSocial",
                    "3. Envie um tweet mencionando @ApolloSocial com: {$token}",
                    "4. Aguarde a confirmação"
                ],
                'profile_url' => 'https://twitter.com/ApolloSocial',
                'tweet_link' => "https://twitter.com/intent/tweet?text=@ApolloSocial%20{$token}"
            ]
        ];
        
        return $instructions[$platform] ?? [];
    }

    /**
     * Validate verification code
     */
    public function validateVerificationCode(array $verification_data, string $code): bool
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'apollo_verification_tokens';
        
        $token = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE token = %s AND platform = %s AND used = 0 AND expires_at > NOW()",
                $code,
                $verification_data['platform']
            ),
            ARRAY_A
        );
        
        if ($token) {
            // Mark as used
            $wpdb->update(
                $table_name,
                ['used' => 1, 'used_at' => current_time('mysql')],
                ['id' => $token['id']]
            );
            
            return true;
        }
        
        return false;
    }

    /**
     * Complete onboarding process
     */
    private function completeOnboarding(array $session): void
    {
        $user_id = $session['user_id'];
        $user_data = $session['user_data'];
        
        // Update user profile
        $this->updateUserProfile($user_id, $user_data);
        
        // Set onboarding completion flag
        update_user_meta($user_id, 'apollo_onboarding_completed', true);
        update_user_meta($user_id, 'apollo_onboarding_completed_at', current_time('mysql'));
        
        // Save onboarding data
        update_user_meta($user_id, 'apollo_onboarding_data', $user_data);
        
        // Mark session as completed
        $session['status'] = 'completed';
        $session['completed_at'] = current_time('mysql');
        $this->saveOnboardingSession($session);
    }

    /**
     * Update user profile with onboarding data
     */
    private function updateUserProfile(int $user_id, array $user_data): void
    {
        // Update display name
        if (!empty($user_data['profile_basic']['name'])) {
            wp_update_user([
                'ID' => $user_id,
                'display_name' => sanitize_text_field($user_data['profile_basic']['name'])
            ]);
        }
        
        // Update location
        if (!empty($user_data['location']['location'])) {
            update_user_meta($user_id, 'apollo_location', sanitize_text_field($user_data['location']['location']));
        }
        
        // Update interests
        if (!empty($user_data['interests']['selected'])) {
            update_user_meta($user_id, 'apollo_interests', $user_data['interests']['selected']);
        }
        
        // Update privacy settings
        if (!empty($user_data['privacy_settings']['visibility'])) {
            update_user_meta($user_id, 'apollo_profile_visibility', $user_data['privacy_settings']['visibility']);
        }
        
        // Update verified platforms
        if (!empty($user_data['verified_platforms'])) {
            update_user_meta($user_id, 'apollo_verified_platforms', $user_data['verified_platforms']);
        }
    }

    /**
     * Award onboarding badges
     */
    private function awardOnboardingBadges(int $user_id, array $session): array
    {
        $badges_earned = [];
        
        // Welcome badge for completing onboarding
        $badges_earned[] = [
            'id' => 'onboarding_complete',
            'name' => 'Bem-vindo Apollo',
            'description' => 'Completou o processo de onboarding',
            'icon' => '🏆'
        ];
        
        // Verification badges
        $verified_platforms = $session['verified_platforms'] ?? [];
        foreach ($verified_platforms as $platform) {
            $badges_earned[] = [
                'id' => "verified_{$platform}",
                'name' => ucfirst($platform) . ' Verificado',
                'description' => "Conta {$platform} verificada",
                'icon' => $this->getPlatformIcon($platform)
            ];
        }
        
        // Interest-based badges
        $interests = $session['user_data']['interests']['selected'] ?? [];
        if (count($interests) >= 3) {
            $badges_earned[] = [
                'id' => 'diverse_interests',
                'name' => 'Interesses Diversos',
                'description' => 'Selecionou 3+ áreas de interesse',
                'icon' => '🌟'
            ];
        }
        
        // Award badges via existing badge system
        foreach ($badges_earned as $badge) {
            do_action('apollo_award_badge', $user_id, $badge['id'], [
                'reason' => 'onboarding_completion',
                'badge_data' => $badge
            ]);
        }
        
        return $badges_earned;
    }

    /**
     * Get platform icon
     */
    private function getPlatformIcon(string $platform): string
    {
        $icons = [
            'whatsapp' => '📱',
            'instagram' => '📸',
            'twitter' => '🐦'
        ];
        
        return $icons[$platform] ?? '✅';
    }

    /**
     * Calculate onboarding progress
     */
    private function calculateProgress(string $current_step): int
    {
        $total_steps = count($this->onboarding_steps) - 1; // Exclude completion step
        $step_names = array_keys($this->onboarding_steps);
        $current_index = array_search($current_step, $step_names);
        
        return min(100, round(($current_index / $total_steps) * 100));
    }

    /**
     * Get step data for rendering
     */
    private function getStepData(string $step): array
    {
        return $this->onboarding_steps[$step] ?? [];
    }

    /**
     * Validate step response
     */
    private function validateStepResponse(string $step, array $response_data): array
    {
        $step_config = $this->onboarding_steps[$step] ?? [];
        $validation = $step_config['validation'] ?? '';
        
        if (empty($validation)) {
            return ['valid' => true];
        }
        
        $rules = explode('|', $validation);
        
        foreach ($rules as $rule) {
            if ($rule === 'required' && empty($response_data['value'])) {
                return [
                    'valid' => false,
                    'message' => 'Este campo é obrigatório'
                ];
            }
            
            if (strpos($rule, 'min:') === 0) {
                $min_length = (int) str_replace('min:', '', $rule);
                if (strlen($response_data['value'] ?? '') < $min_length) {
                    return [
                        'valid' => false,
                        'message' => "Mínimo de {$min_length} caracteres"
                    ];
                }
            }
            
            if ($rule === 'digits:6' && !preg_match('/^\d{6}$/', $response_data['value'] ?? '')) {
                return [
                    'valid' => false,
                    'message' => 'Digite um código de 6 dígitos'
                ];
            }
        }
        
        return ['valid' => true];
    }

    /**
     * Generate unique session ID
     */
    private function generateSessionId(): string
    {
        return 'apollo_onb_' . wp_generate_password(20, false);
    }

    /**
     * Save onboarding session
     */
    private function saveOnboardingSession(array $session_data): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'apollo_onboarding_sessions';
        
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table_name} WHERE session_id = %s",
                $session_data['session_id']
            )
        );
        
        if ($existing) {
            $wpdb->update(
                $table_name,
                [
                    'current_step' => $session_data['current_step'],
                    'completed_steps' => json_encode($session_data['completed_steps']),
                    'user_data' => json_encode($session_data['user_data']),
                    'last_activity' => $session_data['last_activity'],
                    'status' => $session_data['status'] ?? 'active'
                ],
                ['session_id' => $session_data['session_id']]
            );
        } else {
            $wpdb->insert($table_name, [
                'user_id' => $session_data['user_id'],
                'session_id' => $session_data['session_id'],
                'current_step' => $session_data['current_step'],
                'completed_steps' => json_encode($session_data['completed_steps']),
                'user_data' => json_encode($session_data['user_data']),
                'started_at' => $session_data['started_at'],
                'last_activity' => $session_data['last_activity'],
                'status' => 'active'
            ]);
        }
    }

    /**
     * Get onboarding session
     */
    private function getOnboardingSession(string $session_id): ?array
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'apollo_onboarding_sessions';
        
        $session = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE session_id = %s", $session_id),
            ARRAY_A
        );
        
        if ($session) {
            $session['completed_steps'] = json_decode($session['completed_steps'], true) ?: [];
            $session['user_data'] = json_decode($session['user_data'], true) ?: [];
        }
        
        return $session;
    }

    /**
     * Save verification token
     */
    private function saveVerificationToken(array $token_data): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'apollo_verification_tokens';
        
        $wpdb->insert($table_name, [
            'user_id' => $token_data['user_id'],
            'platform' => $token_data['platform'],
            'token' => $token_data['token'],
            'expires_at' => $token_data['expires_at'],
            'used' => 0,
            'created_at' => current_time('mysql')
        ]);
    }

    /**
     * Initiate social verification
     */
    private function initiateSocialVerification(string $platform, int $user_id): array
    {
        $token_data = $this->generateVerificationToken($user_id, $platform);
        
        return [
            'platform' => $platform,
            'token' => $token_data['token'],
            'instructions' => $token_data['instructions'],
            'expires_at' => date('Y-m-d H:i:s', strtotime('+15 minutes'))
        ];
    }
}