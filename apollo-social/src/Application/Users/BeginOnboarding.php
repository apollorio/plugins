<?php

/**
 * BeginOnboarding.
 *
 * Handles initial onboarding data collection and validation.
 *
 * @package Apollo\Application\Users
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 */

namespace Apollo\Application\Users;

/**
 * BeginOnboarding class.
 *
 * Handles initial onboarding data collection and validation.
 */
class BeginOnboarding
{
    /**
     * User profile repository.
     *
     * @var UserProfileRepository
     */
    private UserProfileRepository $userRepo;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->userRepo = new UserProfileRepository();
    }

    /**
     * Start onboarding process for user.
     *
     * @param int   $user_id The user ID.
     * @param array $data    The onboarding data.
     * @return array Result with success status.
     */
    public function handle(int $user_id, array $data): array
    {
        try {
            // Validate user exists.
            $user = get_user_by('ID', $user_id);
            if (! $user) {
                return [
                    'success' => false,
                    'message' => 'Usuário não encontrado',
                ];
            }

            // Validate required data.
            $validation = $this->validateOnboardingData($data);
            if (! $validation['valid']) {
                return [
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors'  => $validation['errors'],
                ];
            }

            // Sanitize and normalize data.
            $sanitized_data = $this->sanitizeOnboardingData($data);

            // Check for Instagram username conflicts.
            $instagram_check = $this->checkInstagramAvailability($sanitized_data['instagram'], $user_id);
            if (! $instagram_check['available']) {
                return [
                    'success'    => false,
                    'message'    => 'Instagram já cadastrado',
                    'suggestion' => $instagram_check['suggestion'],
                ];
            }

            // Generate verification token.
            $verify_token = $this->generateVerificationToken($sanitized_data['instagram']);

            // Save onboarding progress.
            $this->saveOnboardingProgress($user_id, $sanitized_data, $verify_token);

            // Update username if needed.
            $this->updateUsername($user, $sanitized_data['instagram']);

            // Log onboarding start.
            $this->logOnboardingEvent($user_id, 'onboarding_started', $sanitized_data);

            return [
                'success'      => true,
                'message'      => 'Onboarding iniciado com sucesso',
                'verify_token' => $verify_token,
                'progress'     => $this->getUserProgress($user_id),
            ];

        } catch (\Exception $e) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Production error logging.
            error_log('BeginOnboarding error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro interno. Tente novamente.',
            ];
        }//end try
    }

    /**
     * Validate onboarding data.
     *
     * @param array $data The data to validate.
     * @return array Validation result with valid flag and errors.
     */
    private function validateOnboardingData(array $data): array
    {
        $errors = [];

        // Name validation.
        if (empty($data['name']) || strlen(trim($data['name'])) < 2) {
            $errors['name'] = 'Nome deve ter pelo menos 2 caracteres';
        }

        // Industry validation.
        if (empty($data['industry']) || ! in_array($data['industry'], [ 'Yes', 'No', 'Future yes!' ], true)) {
            $errors['industry'] = 'Selecione uma opção válida para indústria';
        }

        // Roles validation (if industry member).
        if (in_array($data['industry'], [ 'Yes', 'Future yes!' ], true)) {
            if (empty($data['roles']) || ! is_array($data['roles'])) {
                $errors['roles'] = 'Selecione pelo menos um role';
            }
        }

        // WhatsApp validation.
        if (empty($data['whatsapp'])) {
            $errors['whatsapp'] = 'WhatsApp é obrigatório';
        } else {
            $normalized_whatsapp = $this->normalizeWhatsapp($data['whatsapp']);
            if (! $this->isValidWhatsapp($normalized_whatsapp)) {
                $errors['whatsapp'] = 'WhatsApp inválido';
            }
        }

        // Instagram validation.
        if (empty($data['instagram'])) {
            $errors['instagram'] = 'Instagram é obrigatório';
        } else {
            $normalized_instagram = $this->normalizeInstagram($data['instagram']);
            if (! $this->isValidInstagram($normalized_instagram)) {
                $errors['instagram'] = 'Instagram inválido';
            }
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Sanitize onboarding data.
     *
     * @param array $data The data to sanitize.
     * @return array Sanitized data.
     */
    private function sanitizeOnboardingData(array $data): array
    {
        return [
            'name'      => sanitize_text_field(trim($data['name'])),
            'industry'  => sanitize_text_field($data['industry']),
            'roles'     => isset($data['roles']) ? array_map('sanitize_text_field', $data['roles']) : [],
            'member_of' => isset($data['member_of']) ? array_map('sanitize_text_field', $data['member_of']) : [],
            'whatsapp'  => $this->normalizeWhatsapp($data['whatsapp']),
            'instagram' => $this->normalizeInstagram($data['instagram']),
        ];
    }

    /**
     * Check Instagram username availability.
     *
     * @param string $instagram       The Instagram username.
     * @param int    $current_user_id The current user ID to exclude.
     * @return array Availability result with suggestion if taken.
     */
    private function checkInstagramAvailability(string $instagram, int $current_user_id): array
    {
        global $wpdb;

        // Check if Instagram is already taken by another user.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance critical unique check.
        $existing_user = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta}
             WHERE meta_key = 'apollo_instagram'
             AND meta_value = %s
             AND user_id != %d",
                $instagram,
                $current_user_id
            )
        );

        if ($existing_user) {
            // Suggest variations.
            $suggestion = $this->suggestUsernameVariation($instagram);

            return [
                'available'  => false,
                'suggestion' => $suggestion,
            ];
        }

        return [ 'available' => true ];
    }

    /**
     * Suggest username variation.
     *
     * @param string $base_username The base username.
     * @return string Suggested variation.
     */
    private function suggestUsernameVariation(string $base_username): string
    {
        global $wpdb;

        $counter = 1;

        do {
            $suggestion = $base_username . $counter;

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance critical unique check.
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT user_id FROM {$wpdb->usermeta}
                 WHERE meta_key = 'apollo_instagram'
                 AND meta_value = %s",
                    $suggestion
                )
            );

            if (! $exists) {
                return $suggestion;
            }

            ++$counter;
        } while ($counter <= 999);

        // Fallback with timestamp.
        return $base_username . time();
    }

    /**
     * Generate verification token.
     *
     * @param string $instagram The Instagram username.
     * @return string The verification token.
     */
    private function generateVerificationToken(string $instagram): string
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('America/Sao_Paulo'));

        return $now->format('Ymd') . strtolower($instagram);
    }

    /**
     * Save onboarding progress.
     *
     * @param int    $user_id      The user ID.
     * @param array  $data         The onboarding data.
     * @param string $verify_token The verification token.
     * @return void
     */
    private function saveOnboardingProgress(int $user_id, array $data, string $verify_token): void
    {
        // Save individual meta fields.
        update_user_meta($user_id, 'apollo_name', $data['name']);
        update_user_meta($user_id, 'apollo_industry', $data['industry']);
        update_user_meta($user_id, 'apollo_roles', $data['roles']);
        update_user_meta($user_id, 'apollo_member_of', $data['member_of']);
        update_user_meta($user_id, 'apollo_whatsapp', $data['whatsapp']);
        update_user_meta($user_id, 'apollo_instagram', $data['instagram']);
        update_user_meta($user_id, 'apollo_verify_token', $verify_token);
        update_user_meta($user_id, 'apollo_verify_status', 'awaiting_instagram_verify');
        update_user_meta($user_id, 'apollo_verify_assets', []);

        // Save progress state.
        update_user_meta(
            $user_id,
            'apollo_onboarding_progress',
            [
                'current_step'    => 'verification_rules',
                'completed_steps' => [
                    'ask_name',
                    'ask_industry',
                    'ask_roles',
                    'ask_memberships',
                    'ask_contacts',
                ],
                'started_at' => current_time('mysql'),
                'data'       => $data,
            ]
        );
    }

    /**
     * Update WordPress username based on Instagram.
     *
     * @param \WP_User $user      The user object.
     * @param string   $instagram The Instagram username.
     * @return void
     */
    private function updateUsername(\WP_User $user, string $instagram): void
    {
        $desired_username = $instagram;

        // Check if username is already the same.
        if ($user->user_login === $desired_username) {
            return;
        }

        // Check if desired username is available.
        if (! username_exists($desired_username)) {
            // Update username.
            wp_update_user(
                [
                    'ID'         => $user->ID,
                    'user_login' => $desired_username,
                ]
            );

            // Update user_nicename as well.
            global $wpdb;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core user update.
            $wpdb->update(
                $wpdb->users,
                [ 'user_nicename' => $desired_username ],
                [ 'ID'            => $user->ID ]
            );
        }
    }

    /**
     * Get user progress.
     *
     * @param int $user_id The user ID.
     * @return array The user progress.
     */
    private function getUserProgress(int $user_id): array
    {
        $progress = get_user_meta($user_id, 'apollo_onboarding_progress', true);

        return is_array($progress) ? $progress : [];
    }

    /**
     * Log onboarding event.
     *
     * @param int    $user_id The user ID.
     * @param string $event   The event name.
     * @param array  $data    Additional event data.
     * @return void
     */
    private function logOnboardingEvent(int $user_id, string $event, array $data = []): void
    {
        global $wpdb;

        $audit_table = $wpdb->prefix . 'apollo_audit_log';

        // Check if table exists.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table existence check.
        $table_check = $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $wpdb->esc_like($audit_table)
            )
        );
        if ($table_check !== $audit_table) {
            // Table doesn't exist yet.
            return;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Audit logging.
        $wpdb->insert(
            $audit_table,
            [
                'user_id'     => $user_id,
                'action'      => $event,
                'entity_type' => 'user',
                'entity_id'   => $user_id,
                'metadata'    => wp_json_encode(
                    [
                        'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
                        'ip_address' => $this->getClientIp(),
                        'data'       => $data,
                    ]
                ),
                'created_at' => current_time('mysql'),
            ]
        );
    }

    /**
     * Normalize WhatsApp number.
     *
     * @param string $whatsapp The WhatsApp number.
     * @return string Normalized number.
     */
    private function normalizeWhatsapp(string $whatsapp): string
    {
        $digits = preg_replace('/\D+/', '', $whatsapp);

        // Add country code if needed.
        if (strlen($digits) === 11) {
            $digits = '55' . $digits;
        }

        return '+' . ltrim($digits, '+');
    }

    /**
     * Normalize Instagram username.
     *
     * @param string $instagram The Instagram username.
     * @return string Normalized username.
     */
    private function normalizeInstagram(string $instagram): string
    {
        $instagram = trim($instagram);
        $instagram = ltrim($instagram, '@');

        return strtolower($instagram);
    }

    /**
     * Validate WhatsApp format.
     *
     * @param string $whatsapp The WhatsApp number.
     * @return bool True if valid.
     */
    private function isValidWhatsapp(string $whatsapp): bool
    {
        // Remove + and check if all digits.
        $digits = ltrim($whatsapp, '+');

        return ctype_digit($digits) && strlen($digits) >= 10 && strlen($digits) <= 15;
    }

    /**
     * Validate Instagram username.
     *
     * @param string $instagram The Instagram username.
     * @return bool True if valid.
     */
    private function isValidInstagram(string $instagram): bool
    {
        return preg_match('/^[a-zA-Z0-9._]{1,30}$/', $instagram);
    }

    /**
     * Get client IP address.
     *
     * @return string The client IP address or 'unknown'.
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
            'REMOTE_ADDR',
        ];

        foreach ($ip_headers as $header) {
            if (isset($_SERVER[ $header ]) && ! empty($_SERVER[ $header ])) {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- IP address validation.
                return sanitize_text_field(wp_unslash($_SERVER[ $header ]));
            }
        }

        return 'unknown';
    }
}
