<?php
/**
 * Onboarding Configuration
 *
 * Defines steps, validation rules, and flow for conversational onboarding.
 */

return array(
	'steps'        => array(
		'ask_name'           => array(
			'question'    => 'Hey, qual seu nome?',
			'type'        => 'text',
			'placeholder' => 'Digite seu nome',
			'required'    => true,
			'field'       => 'name',
			'validation'  => array( 'min_length' => 2 ),
		),
		'ask_industry'       => array(
			'question' => 'Você é da indústria de Música/Eventos/Cultura Eletrônica no Rio?',
			'type'     => 'buttons',
			'options'  => array( 'Yes', 'No', 'Future yes!' ),
			'field'    => 'industry',
		),
		'ask_roles'          => array(
			'question'     => 'Em que frentes você atua? (múltipla escolha)',
			'type'         => 'multi_select',
			'options'      => array(
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
			),
			'field'        => 'roles',
			'condition'    => array( 'industry' => array( 'Yes', 'Future yes!' ) ),
			'min_selected' => 1,
		),
		'ask_memberships'    => array(
			'question'                  => 'É membro de algum Núcleo / Club / DJ Bar?',
			'type'                      => 'multi_select',
			'options'                   => array(),
			// Loaded dynamically from Events Manager + Núcleos
								'field' => 'member_of',
			'condition'                 => array( 'industry' => array( 'Yes', 'Future yes!' ) ),
		),
		'ask_contacts'       => array(
			'question' => 'Vamos coletar seus contatos para verificação:',
			'type'     => 'contacts',
			'fields'   => array(
				'whatsapp'  => array(
					'label'       => 'WhatsApp',
					'type'        => 'phone',
					'placeholder' => '(21) 99999-9999',
					'required'    => true,
					'mask'        => 'br_phone',
				),
				'instagram' => array(
					'label'       => 'Instagram',
					'type'        => 'instagram',
					'placeholder' => '@seuusuario',
					'required'    => true,
				),
			),
		),
		'verification_rules' => array(
			'question' => 'Perfeito! Vamos verificar seu Instagram.',
			'type'     => 'verification',
			'field'    => 'verification',
		),
		'summary_submit'     => array(
			'question' => 'Confira seus dados e envie:',
			'type'     => 'summary',
			'field'    => 'submit',
		),
	),

	'verification' => array(
		'token_format' => 'YYYYMMDD + username (lowercase, no @, no spaces)',
		'instructions' => 'Poste um story ou envie screenshot com: eu sou @<username> no apollo :: <token>',
		'status'       => 'awaiting_instagram_verify',
	),

	'rate_limit'   => array(
		'enabled'              => true,
		'window'               => 60,
		// seconds
				'max_requests' => 1,
	),

	'analytics'    => array(
		'track_on_canvas' => true,
		'events'          => array(
			'onboarding_started',
			'onboarding_step_completed',
			'onboarding_completed',
			'verification_dm_requested',
			'verification_approved',
			'verification_canceled',
			'verification_rejected',
		),
	),
);
