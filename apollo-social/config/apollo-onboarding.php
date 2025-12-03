<?php

/**
 * Apollo Onboarding Configuration
 * Central configuration for the conversational onboarding system
 */

return array(
	/**
	 * Onboarding Flow Configuration
	 */
	'flow'           => array(
		'enabled'                    => true,
		'require_verification'       => true,
		'max_attempts'               => 3,
		'session_timeout'            => 30 * MINUTE_IN_SECONDS,
		// 30 minutes
				'auto_save_progress' => true,

		'steps'                      => array(
			'ask_name'           => array(
				'title'       => 'Nome',
				'description' => 'Como podemos te chamar?',
				'required'    => true,
				'validation'  => array(
					'min_length' => 2,
					'max_length' => 100,
					'pattern'    => '/^[a-zA-ZÀ-ÿ\s]+$/',
				),
				'messages'    => array(
					'prompt'      => 'Olá! 👋 Vamos começar o seu onboarding na Apollo.',
					'question'    => 'Primeiro, como você gostaria de ser chamado?',
					'placeholder' => 'Digite seu nome...',
					'success'     => 'Prazer em conhecer você, {name}! 😊',
				),
			),

			'ask_industry'       => array(
				'title'       => 'Indústria',
				'description' => 'Qual sua área de atuação?',
				'required'    => true,
				'type'        => 'single_choice',
				'messages'    => array(
					'question' => 'Agora me conta, {name}, qual é a sua área de atuação?',
					'success'  => 'Perfeito! {industry} é uma área muito interessante! 🚀',
				),
			),

			'ask_roles'          => array(
				'title'          => 'Funções',
				'description'    => 'Quais suas funções profissionais?',
				'required'       => false,
				'type'           => 'multiple_choice',
				'max_selections' => 5,
				'messages'       => array(
					'question' => 'Que legal! E qual(is) sua(s) função(ões) na área de {industry}?',
					'hint'     => 'Você pode selecionar até 5 opções',
					'success'  => 'Entendi! Você atua como {roles}. Muito bacana! 👨‍💼',
				),
			),

			'ask_memberships'    => array(
				'title'          => 'Comunidades',
				'description'    => 'Participa de outras comunidades?',
				'required'       => false,
				'type'           => 'multiple_choice',
				'max_selections' => 10,
				'messages'       => array(
					'question' => 'Você já participa de outras comunidades ou grupos profissionais?',
					'hint'     => 'Isso nos ajuda a entender melhor o seu perfil',
					'success'  => 'Que ótimo! Networking é fundamental! 🤝',
				),
			),

			'ask_contacts'       => array(
				'title'       => 'Contatos',
				'description' => 'WhatsApp e Instagram para verificação',
				'required'    => true,
				'type'        => 'form',
				'fields'      => array(
					'whatsapp'  => array(
						'label'       => 'WhatsApp',
						'required'    => true,
						'pattern'     => '/^(\+55)?[\s\-\(\)]?(\d{2})[\s\-\(\)]?(\d{4,5})[\s\-]?(\d{4})$/',
						'placeholder' => '(11) 99999-9999',
					),
					'instagram' => array(
						'label'       => 'Instagram',
						'required'    => true,
						'pattern'     => '/^@?[a-zA-Z0-9._]{1,30}$/',
						'placeholder' => '@seuusuario',
					),
				),
				'messages'    => array(
					'question' => 'Agora preciso do seu WhatsApp e Instagram para verificação:',
					'success'  => 'Perfeito! Seus contatos foram salvos! 📱',
				),
			),

			'verification_rules' => array(
				'title'       => 'Verificação',
				'description' => 'Regras do processo de verificação',
				'required'    => true,
				'type'        => 'info',
				'messages'    => array(
					'content' => 'Para completar seu cadastro, você precisará fazer uma verificação simples no Instagram:',
					'rules'   => array(
						'📸 Poste uma foto ou story com seu token de verificação',
						'🏷️ Use a hashtag #ApolloVerificacao',
						'📋 Envie uma captura de tela para nós',
						'⏱️ A verificação leva até 24 horas',
					),
					'success' => 'Entendi as regras! Vamos finalizar! ✅',
				),
			),

			'summary_submit'     => array(
				'title'       => 'Finalização',
				'description' => 'Revisão e submissão final',
				'required'    => true,
				'type'        => 'summary',
				'messages'    => array(
					'summary' => 'Vamos revisar suas informações:',
					'confirm' => 'Tudo certo! Pode finalizar meu onboarding! 🎉',
					'success' => 'Onboarding finalizado! Seu token de verificação é: {token}',
				),
			),
		),
	),

	/**
	 * Validation Configuration
	 */
	'validation'     => array(
		'token_format'       => 'YYYYMMDD_username',
		'instagram_username' => array(
			'min_length'      => 1,
			'max_length'      => 30,
			'pattern'         => '/^[a-zA-Z0-9._]+$/',
			'check_conflicts' => true,
		),
		'whatsapp_number'    => array(
			'country_code' => '+55',
			'min_length'   => 10,
			'max_length'   => 11,
			'pattern'      => '/^(\d{2})(\d{4,5})(\d{4})$/',
			'normalize'    => true,
		),
		'name'               => array(
			'min_length' => 2,
			'max_length' => 100,
			'pattern'    => '/^[a-zA-ZÀ-ÿ\s\-\'\.]+$/',
			'trim'       => true,
		),
	),

	/**
	 * Verification Configuration
	 */
	'verification'   => array(
		'methods'        => array( 'instagram' ),
		'required'       => true,
		'auto_approve'   => false,
		'review_timeout' => 24 * HOUR_IN_SECONDS,
		// 24 hours

				'assets' => array(
					'max_files'                 => 3,
					'max_file_size'             => 5 * 1024 * 1024,
					// 5MB
								'allowed_types' => array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ),
					'storage_path'              => 'apollo-verification-assets',
					'require_dimensions'        => false,
				),

		'instagram'      => array(
			'hashtag'       => '#ApolloVerificacao',
			'token_display' => true,
			'story_allowed' => true,
			'post_allowed'  => true,
		),
	),

	/**
	 * Security Configuration
	 */
	'security'       => array(
		'rate_limiting'    => array(
			'enabled'             => true,
			'onboarding_start'    => array(
				'requests' => 3,
				'window'   => 10 * MINUTE_IN_SECONDS,
// 10 minutes
			),
			'onboarding_complete' => array(
				'requests' => 1,
				'window'   => MINUTE_IN_SECONDS,
		// 1 minute
			),
			'verification_upload' => array(
				'requests' => 5,
				'window'   => HOUR_IN_SECONDS,
		// 1 hour
			),
			'api_requests'        => array(
				'requests' => 100,
				'window'   => HOUR_IN_SECONDS,
		// 1 hour
			),
		),

		'session_security' => array(
			'regenerate_id'    => true,
			'secure_cookies'   => true,
			'httponly_cookies' => true,
			'samesite'         => 'Strict',
		),

		'data_protection'  => array(
			'encrypt_sensitive'                  => false,
			// Would need encryption key
								'hash_tokens'    => false,
			// Tokens need to be readable
								'anonymize_logs' => true,
			'gdpr_compliant'                     => true,
		),
	),

	/**
	 * Analytics Configuration
	 */
	'analytics'      => array(
		'enable_on_canvas'      => true,
		'enable_external'       => false,
		// No external APIs as requested
				'local_storage' => true,
		'retention_days'        => 90,

		'events'                => array(
			'onboarding_started',
			'onboarding_step_completed',
			'onboarding_completed',
			'verification_submitted',
			'verification_approved',
			'verification_rejected',
		),

		'metrics'               => array(
			'completion_rate',
			'time_to_complete',
			'step_abandonment',
			'verification_success_rate',
			'industry_distribution',
			'role_distribution',
		),
	),

	/**
	 * Admin Interface Configuration
	 */
	'admin'          => array(
		'page_title'    => 'Apollo Verificações',
		'menu_position' => 30,
		'capability'    => 'manage_options',
		'auto_refresh'  => 30,
		// seconds

				'grid'  => array(
					'cards_per_page'        => 20,
					'card_height'           => 'auto',
					'responsive_breakpoint' => 768,
				),

		'filters'       => array(
			'status',
			'search',
			'date_range',
			'industry',
		),

		'bulk_actions'  => array(
			'approve_selected',
			'reject_selected',
			'export_data',
		),
	),

	/**
	 * Integration Configuration
	 */
	'integrations'   => array(
		'canvas_mode'      => array(
			'enabled'        => true,
			'theme_override' => false,
			'custom_header'  => true,
			'custom_footer'  => false,
		),

		'wordpress'        => array(
			'user_role'                => 'subscriber',
			'auto_assign_capabilities' => true,
			'sync_display_name'        => true,
			'update_user_meta'         => true,
		),

		'apollo_ecosystem' => array(
			'sync_with_groups'     => false,
			'sync_with_ads'        => false,
			'cross_platform_token' => true,
		),
	),

	/**
	 * Performance Configuration
	 */
	'performance'    => array(
		'caching'      => array(
			'enabled'             => true,
			'ttl'                 => 15 * MINUTE_IN_SECONDS,
			'cache_options'       => true,
			'cache_user_progress' => true,
		),

		'optimization' => array(
			'lazy_load_assets'  => true,
			'minify_responses'  => false,
			'compress_uploads'  => false,
			'optimize_database' => true,
		),

		'monitoring'   => array(
			'track_performance'                        => true,
			'slow_query_threshold'                     => 1.0,
			// seconds
								'memory_limit_warning' => 0.8,
	// 80% of limit
		),
	),

	/**
	 * UI/UX Configuration
	 */
	'ui'             => array(
		'theme'                => 'codepen-inspired',
		'primary_color'        => '#007acc',
		'animation_speed'      => 300,
		// milliseconds
				'typing_speed' => 50,
		// milliseconds per character

				'chat'         => array(
					'bubble_animation' => true,
					'typing_indicator' => true,
					'progress_bar'     => true,
					'sound_effects'    => false,
					'auto_scroll'      => true,
				),

		'responsive'           => array(
			'mobile_first' => true,
			'breakpoints'  => array(
				'mobile'  => 480,
				'tablet'  => 768,
				'desktop' => 1024,
			),
		),
	),

	/**
	 * Error Handling Configuration
	 */
	'error_handling' => array(
		'display_errors'        => false,
		'log_errors'            => true,
		'error_log_file'        => 'apollo-onboarding-errors.log',
		'max_log_size'          => 10 * 1024 * 1024,
		// 10MB

				'user_messages' => array(
					'generic_error'    => 'Ops! Algo deu errado. Tente novamente.',
					'validation_error' => 'Por favor, verifique os dados informados.',
					'network_error'    => 'Problema de conexão. Verifique sua internet.',
					'timeout_error'    => 'Tempo esgotado. Tente novamente.',
				),
	),

	/**
	 * Development Configuration
	 */
	'development'    => array(
		'debug_mode'         => defined( 'WP_DEBUG' ) && WP_DEBUG,
		'verbose_logging'    => false,
		'mock_verification'  => false,
		'skip_rate_limiting' => false,
		'test_data_enabled'  => false,
	),
);
