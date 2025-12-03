<?php

namespace Apollo\Modules\Onboarding\Services;

/**
 * Conversational Onboarding Service
 *
 * Handles chat-style onboarding flow with social verification.
 */
class OnboardingService {

	private array $onboarding_steps = array(
		'welcome'             => array(
			'title'     => 'Bem-vindo ao Apollo Social! 👋',
			'message'   => 'Olá! Eu sou o assistente Apollo. Vou te ajudar a configurar seu perfil em poucos passos. Pronto para começar?',
			'buttons'   => array( '🚀 Vamos começar!', '❓ Preciso de ajuda' ),
			'next_step' => 'profile_basic',
		),
		'profile_basic'       => array(
			'title'             => 'Vamos conhecer você melhor 😊',
			'message'           => 'Como gostaria de ser chamado(a) na plataforma?',
			'input_type'        => 'text',
			'input_placeholder' => 'Digite seu nome ou apelido',
			'validation'        => 'required|min:2',
			'next_step'         => 'location',
		),
		'location'            => array(
			'title'             => 'Onde você está localizado? 📍',
			'message'           => 'Isso nos ajuda a conectar você com pessoas e eventos próximos.',
			'input_type'        => 'location',
			'input_placeholder' => 'Digite sua cidade ou região',
			'buttons'           => array( '🌍 Detectar automaticamente', '⏭️ Pular este passo' ),
			'next_step'         => 'interests',
		),
		'interests'           => array(
			'title'      => 'Quais são seus interesses? ⭐',
			'message'    => 'Selecione os temas que mais te interessam (pode escolher vários):',
			'input_type' => 'multi_select',
			'options'    => array(
				'tecnologia'       => '💻 Tecnologia',
				'cultura'          => '🎭 Cultura',
				'esportes'         => '⚽ Esportes',
				'educacao'         => '📚 Educação',
				'meio_ambiente'    => '🌱 Meio Ambiente',
				'saude'            => '🏥 Saúde',
				'arte'             => '🎨 Arte',
				'musica'           => '🎵 Música',
				'gastronomia'      => '🍽️ Gastronomia',
				'empreendedorismo' => '💼 Empreendedorismo',
			),
			'next_step'  => 'social_verification',
		),
		'social_verification' => array(
			'title'     => 'Verificação Social 🔐',
			'message'   => 'Para aumentar a confiança na plataforma, você pode verificar suas redes sociais:',
			'buttons'   => array(
				'📱 WhatsApp',
				'📸 Instagram',
				'🐦 Twitter',
				'⏭️ Verificar depois',
			),
			'next_step' => 'verification_code',
		),
		'verification_code'   => array(
			'title'             => 'Código de Verificação 📨',
			'message'           => 'Enviamos um código para sua conta. Digite o código recebido:',
			'input_type'        => 'verification_code',
			'input_placeholder' => 'Digite o código de 6 dígitos',
			'validation'        => 'required|digits:6',
			'next_step'         => 'privacy_settings',
		),
		'privacy_settings'    => array(
			'title'      => 'Configurações de Privacidade 🛡️',
			'message'    => 'Como você gostaria que seu perfil fosse visível?',
			'input_type' => 'select',
			'options'    => array(
				'public'    => '🌍 Público - Todos podem ver',
				'community' => '👥 Comunidade - Apenas membros Apollo',
				'private'   => '🔒 Privado - Apenas amigos',
			),
			'next_step'  => 'completion',
		),
		'completion'          => array(
			'title'      => 'Parabéns! Tudo pronto! 🎉',
			'message'    => 'Seu perfil foi configurado com sucesso. Agora você pode explorar grupos, criar eventos e conectar-se com a comunidade!',
			'buttons'    => array( '🏠 Ir para Dashboard', '👥 Explorar Grupos', '📅 Ver Eventos' ),
			'completion' => true,
		),
	);

	/**
	 * Start onboarding session
	 */
	public function startOnboarding( int $user_id ): array {
		$session_id = $this->generateSessionId();

		$session_data = array(
			'user_id'         => $user_id,
			'session_id'      => $session_id,
			'current_step'    => 'welcome',
			'completed_steps' => array(),
			'user_data'       => array(),
			'started_at'      => current_time( 'mysql' ),
			'last_activity'   => current_time( 'mysql' ),
		);

		$this->saveOnboardingSession( $session_data );

		return array(
			'session_id' => $session_id,
			'step_data'  => $this->getStepData( 'welcome' ),
			'progress'   => $this->calculateProgress( 'welcome' ),
		);
	}

	/**
	 * Process step response
	 */
	public function processStep( string $session_id, string $step, array $response_data ): array {
		$session = $this->getOnboardingSession( $session_id );

		if ( ! $session ) {
			return array( 'error' => 'Sessão de onboarding não encontrada' );
		}

		// Validate response
		$validation = $this->validateStepResponse( $step, $response_data );
		if ( ! $validation['valid'] ) {
			return array(
				'error'     => $validation['message'],
				'step_data' => $this->getStepData( $step ),
				'progress'  => $this->calculateProgress( $step ),
			);
		}

		// Save step data
		$session['completed_steps'][]  = $step;
		$session['user_data'][ $step ] = $response_data;
		$session['last_activity']      = current_time( 'mysql' );

		// Handle special steps
		switch ( $step ) {
			case 'social_verification':
				$verification_result             = $this->initiateSocialVerification( $response_data['platform'], $session['user_id'] );
				$session['verification_pending'] = $verification_result;
				break;

			case 'verification_code':
				$verification_valid = $this->validateVerificationCode( $session['verification_pending'], $response_data['code'] );
				if ( ! $verification_valid ) {
					return array(
						'error'     => 'Código de verificação inválido',
						'step_data' => $this->getStepData( $step ),
						'progress'  => $this->calculateProgress( $step ),
					);
				}
				$session['verified_platforms'][] = $session['verification_pending']['platform'];
				break;
		}

		// Get next step
		$next_step = $this->onboarding_steps[ $step ]['next_step'] ?? null;

		if ( ! $next_step || $this->onboarding_steps[ $next_step ]['completion'] ?? false ) {
			// Complete onboarding
			$this->completeOnboarding( $session );

			return array(
				'completed'     => true,
				'step_data'     => $this->getStepData( 'completion' ),
				'progress'      => 100,
				'badges_earned' => $this->awardOnboardingBadges( $session['user_id'], $session ),
			);
		}

		// Update session
		$session['current_step'] = $next_step;
		$this->saveOnboardingSession( $session );

		return array(
			'session_id'      => $session_id,
			'step_data'       => $this->getStepData( $next_step ),
			'progress'        => $this->calculateProgress( $next_step ),
			'completed_steps' => $session['completed_steps'],
		);
	}

	/**
	 * Generate verification token for social platforms
	 */
	public function generateVerificationToken( int $user_id, string $platform ): array {
		$token      = strtoupper( wp_generate_password( 6, false, false ) );
		$token_data = array(
			'user_id'    => $user_id,
			'platform'   => $platform,
			'token'      => $token,
			'expires_at' => date( 'Y-m-d H:i:s', strtotime( '+15 minutes' ) ),
			'used'       => false,
		);

		$this->saveVerificationToken( $token_data );

		return array(
			'token'                        => $token,
			'expires_in'                   => 900, 
			// 15 minutes
							'instructions' => $this->getVerificationInstructions( $platform, $token ),
		);
	}

	/**
	 * Get verification instructions for each platform
	 */
	private function getVerificationInstructions( string $platform, string $token ): array {
		$instructions = array(
			'whatsapp'  => array(
				'title'       => 'Verificação via WhatsApp',
				'steps'       => array(
					'1. Abra o WhatsApp',
					'2. Envie uma mensagem para +55 11 99999-9999',
					"3. Digite exatamente: APOLLO {$token}",
					'4. Aguarde a confirmação e volte aqui',
				),
				'qr_code_url' => "https://wa.me/5511999999999?text=APOLLO%20{$token}",
				'deep_link'   => "whatsapp://send?phone=5511999999999&text=APOLLO%20{$token}",
			),
			'instagram' => array(
				'title'       => 'Verificação via Instagram',
				'steps'       => array(
					'1. Abra o Instagram',
					'2. Siga @apollo.social.oficial',
					"3. Envie uma DM com o código: {$token}",
					'4. Aguarde a confirmação automática',
				),
				'profile_url' => 'https://instagram.com/apollo.social.oficial',
				'dm_link'     => 'https://instagram.com/apollo.social.oficial',
			),
			'twitter'   => array(
				'title'       => 'Verificação via Twitter',
				'steps'       => array(
					'1. Abra o Twitter/X',
					'2. Siga @ApolloSocial',
					"3. Envie um tweet mencionando @ApolloSocial com: {$token}",
					'4. Aguarde a confirmação',
				),
				'profile_url' => 'https://twitter.com/ApolloSocial',
				'tweet_link'  => "https://twitter.com/intent/tweet?text=@ApolloSocial%20{$token}",
			),
		);

		return $instructions[ $platform ] ?? array();
	}

	/**
	 * Validate verification code
	 */
	public function validateVerificationCode( array $verification_data, string $code ): bool {
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

		if ( $token ) {
			// Mark as used
			$wpdb->update(
				$table_name,
				array(
					'used'    => 1,
					'used_at' => current_time( 'mysql' ),
				),
				array( 'id' => $token['id'] )
			);

			return true;
		}

		return false;
	}

	/**
	 * Complete onboarding process
	 */
	private function completeOnboarding( array $session ): void {
		$user_id   = $session['user_id'];
		$user_data = $session['user_data'];

		// Update user profile
		$this->updateUserProfile( $user_id, $user_data );

		// Set onboarding completion flag
		update_user_meta( $user_id, 'apollo_onboarding_completed', true );
		update_user_meta( $user_id, 'apollo_onboarding_completed_at', current_time( 'mysql' ) );

		// Save onboarding data
		update_user_meta( $user_id, 'apollo_onboarding_data', $user_data );

		// Mark session as completed
		$session['status']       = 'completed';
		$session['completed_at'] = current_time( 'mysql' );
		$this->saveOnboardingSession( $session );
	}

	/**
	 * Update user profile with onboarding data
	 */
	private function updateUserProfile( int $user_id, array $user_data ): void {
		// Update display name
		if ( ! empty( $user_data['profile_basic']['name'] ) ) {
			wp_update_user(
				array(
					'ID'           => $user_id,
					'display_name' => sanitize_text_field( $user_data['profile_basic']['name'] ),
				)
			);
		}

		// Update location
		if ( ! empty( $user_data['location']['location'] ) ) {
			update_user_meta( $user_id, 'apollo_location', sanitize_text_field( $user_data['location']['location'] ) );
		}

		// Update interests
		if ( ! empty( $user_data['interests']['selected'] ) ) {
			update_user_meta( $user_id, 'apollo_interests', $user_data['interests']['selected'] );
		}

		// Update privacy settings
		if ( ! empty( $user_data['privacy_settings']['visibility'] ) ) {
			update_user_meta( $user_id, 'apollo_profile_visibility', $user_data['privacy_settings']['visibility'] );
		}

		// Update verified platforms
		if ( ! empty( $user_data['verified_platforms'] ) ) {
			update_user_meta( $user_id, 'apollo_verified_platforms', $user_data['verified_platforms'] );
		}
	}

	/**
	 * Award onboarding badges
	 */
	private function awardOnboardingBadges( int $user_id, array $session ): array {
		$badges_earned = array();

		// Welcome badge for completing onboarding
		$badges_earned[] = array(
			'id'          => 'onboarding_complete',
			'name'        => 'Bem-vindo Apollo',
			'description' => 'Completou o processo de onboarding',
			'icon'        => '🏆',
		);

		// Verification badges
		$verified_platforms = $session['verified_platforms'] ?? array();
		foreach ( $verified_platforms as $platform ) {
			$badges_earned[] = array(
				'id'          => "verified_{$platform}",
				'name'        => ucfirst( $platform ) . ' Verificado',
				'description' => "Conta {$platform} verificada",
				'icon'        => $this->getPlatformIcon( $platform ),
			);
		}

		// Interest-based badges
		$interests = $session['user_data']['interests']['selected'] ?? array();
		if ( count( $interests ) >= 3 ) {
			$badges_earned[] = array(
				'id'          => 'diverse_interests',
				'name'        => 'Interesses Diversos',
				'description' => 'Selecionou 3+ áreas de interesse',
				'icon'        => '🌟',
			);
		}

		// Award badges via existing badge system
		foreach ( $badges_earned as $badge ) {
			do_action(
				'apollo_award_badge',
				$user_id,
				$badge['id'],
				array(
					'reason'     => 'onboarding_completion',
					'badge_data' => $badge,
				)
			);
		}

		return $badges_earned;
	}

	/**
	 * Get platform icon
	 */
	private function getPlatformIcon( string $platform ): string {
		$icons = array(
			'whatsapp'  => '📱',
			'instagram' => '📸',
			'twitter'   => '🐦',
		);

		return $icons[ $platform ] ?? '✅';
	}

	/**
	 * Calculate onboarding progress
	 */
	private function calculateProgress( string $current_step ): int {
		$total_steps = count( $this->onboarding_steps ) - 1; 
		// Exclude completion step
		$step_names    = array_keys( $this->onboarding_steps );
		$current_index = array_search( $current_step, $step_names );

		return min( 100, round( ( $current_index / $total_steps ) * 100 ) );
	}

	/**
	 * Get step data for rendering
	 */
	private function getStepData( string $step ): array {
		return $this->onboarding_steps[ $step ] ?? array();
	}

	/**
	 * Validate step response
	 */
	private function validateStepResponse( string $step, array $response_data ): array {
		$step_config = $this->onboarding_steps[ $step ] ?? array();
		$validation  = $step_config['validation'] ?? '';

		if ( empty( $validation ) ) {
			return array( 'valid' => true );
		}

		$rules = explode( '|', $validation );

		foreach ( $rules as $rule ) {
			if ( $rule === 'required' && empty( $response_data['value'] ) ) {
				return array(
					'valid'   => false,
					'message' => 'Este campo é obrigatório',
				);
			}

			if ( strpos( $rule, 'min:' ) === 0 ) {
				$min_length = (int) str_replace( 'min:', '', $rule );
				if ( strlen( $response_data['value'] ?? '' ) < $min_length ) {
					return array(
						'valid'   => false,
						'message' => "Mínimo de {$min_length} caracteres",
					);
				}
			}

			if ( $rule === 'digits:6' && ! preg_match( '/^\d{6}$/', $response_data['value'] ?? '' ) ) {
				return array(
					'valid'   => false,
					'message' => 'Digite um código de 6 dígitos',
				);
			}
		}//end foreach

		return array( 'valid' => true );
	}

	/**
	 * Generate unique session ID
	 */
	private function generateSessionId(): string {
		return 'apollo_onb_' . wp_generate_password( 20, false );
	}

	/**
	 * Save onboarding session
	 */
	private function saveOnboardingSession( array $session_data ): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_onboarding_sessions';

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table_name} WHERE session_id = %s",
				$session_data['session_id']
			)
		);

		if ( $existing ) {
			$wpdb->update(
				$table_name,
				array(
					'current_step'    => $session_data['current_step'],
					'completed_steps' => json_encode( $session_data['completed_steps'] ),
					'user_data'       => json_encode( $session_data['user_data'] ),
					'last_activity'   => $session_data['last_activity'],
					'status'          => $session_data['status'] ?? 'active',
				),
				array( 'session_id' => $session_data['session_id'] )
			);
		} else {
			$wpdb->insert(
				$table_name,
				array(
					'user_id'         => $session_data['user_id'],
					'session_id'      => $session_data['session_id'],
					'current_step'    => $session_data['current_step'],
					'completed_steps' => json_encode( $session_data['completed_steps'] ),
					'user_data'       => json_encode( $session_data['user_data'] ),
					'started_at'      => $session_data['started_at'],
					'last_activity'   => $session_data['last_activity'],
					'status'          => 'active',
				)
			);
		}//end if
	}

	/**
	 * Get onboarding session
	 */
	private function getOnboardingSession( string $session_id ): ?array {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_onboarding_sessions';

		$session = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table_name} WHERE session_id = %s", $session_id ),
			ARRAY_A
		);

		if ( $session ) {
			$session['completed_steps'] = json_decode( $session['completed_steps'], true ) ?: array();
			$session['user_data']       = json_decode( $session['user_data'], true ) ?: array();
		}

		return $session;
	}

	/**
	 * Save verification token
	 */
	private function saveVerificationToken( array $token_data ): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_verification_tokens';

		$wpdb->insert(
			$table_name,
			array(
				'user_id'    => $token_data['user_id'],
				'platform'   => $token_data['platform'],
				'token'      => $token_data['token'],
				'expires_at' => $token_data['expires_at'],
				'used'       => 0,
				'created_at' => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Initiate social verification
	 */
	private function initiateSocialVerification( string $platform, int $user_id ): array {
		$token_data = $this->generateVerificationToken( $user_id, $platform );

		return array(
			'platform'     => $platform,
			'token'        => $token_data['token'],
			'instructions' => $token_data['instructions'],
			'expires_at'   => date( 'Y-m-d H:i:s', strtotime( '+15 minutes' ) ),
		);
	}
}
