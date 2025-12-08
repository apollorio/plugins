<?php
/**
 * Onboarding Configuration
 *
 * Defines steps, validation rules, and flow for conversational onboarding.
 */

return [
	'steps'        => [
		'ask_name'           => [
			'question'    => 'Hey, qual seu nome?',
			'type'        => 'text',
			'placeholder' => 'Digite seu nome',
			'required'    => true,
			'field'       => 'name',
			'validation'  => [ 'min_length' => 2 ],
		],
		'ask_industry'       => [
			'question' => 'Você é da indústria de Música/Eventos/Cultura Eletrônica no Rio?',
			'type'     => 'buttons',
			'options'  => [ 'Yes', 'No', 'Future yes!' ],
			'field'    => 'industry',
		],
		'ask_roles'          => [
			'question'     => 'Em que frentes você atua? (múltipla escolha)',
			'type'         => 'multi_select',
			'options'      => [
				'DJ',
				'PRODUCER',
				'CULTURAL PRODUCER',
				'MUSIC PRODUCER',
				'PHOTOGRAPHER',
				'VISUALS & DIGITAL ART',
				'BAR TEAM',
				'FINANCE TEAM',
				'GOVERNMENT',
				'BUSINESS PERSON',
				'HOSTESS',
				'PROMOTER',
				'INFLUENCER',
			],
			'field'        => 'roles',
			'condition'    => [ 'industry' => [ 'Yes', 'Future yes!' ] ],
			'min_selected' => 1,
		],
		'ask_memberships'    => [
			'question'                  => 'É membro de algum Núcleo / Club / DJ Bar?',
			'type'                      => 'multi_select',
			'options'                   => [],
			// Loaded dynamically from Events Manager + Núcleos
								'field' => 'member_of',
			'condition'                 => [ 'industry' => [ 'Yes', 'Future yes!' ] ],
		],
		'ask_contacts'       => [
			'question' => 'Vamos coletar seus contatos para verificação:',
			'type'     => 'contacts',
			'fields'   => [
				'whatsapp'  => [
					'label'       => 'WhatsApp',
					'type'        => 'phone',
					'placeholder' => '(21) 99999-9999',
					'required'    => true,
					'mask'        => 'br_phone',
				],
				'instagram' => [
					'label'       => 'Instagram',
					'type'        => 'instagram',
					'placeholder' => '@seuusuario',
					'required'    => true,
				],
			],
		],
		'verification_rules' => [
			'question' => 'Perfeito! Vamos verificar seu Instagram.',
			'type'     => 'verification',
			'field'    => 'verification',
		],
		'summary_submit'     => [
			'question' => 'Confira seus dados e envie:',
			'type'     => 'summary',
			'field'    => 'submit',
		],
	],

	'verification' => [
		'token_format' => 'YYYYMMDD + username (lowercase, no @, no spaces)',
		'instructions' => 'Poste um story ou envie screenshot com: eu sou @<username> no apollo :: <token>',
		'status'       => 'awaiting_instagram_verify',
	],

	'rate_limit'   => [
		'enabled'              => true,
		'window'               => 60,
		// seconds
				'max_requests' => 1,
	],

	'analytics'    => [
		'track_on_canvas' => true,
		'events'          => [
			'onboarding_started',
			'onboarding_step_completed',
			'onboarding_completed',
			'verification_dm_requested',
			'verification_approved',
			'verification_canceled',
			'verification_rejected',
		],
	],
];
