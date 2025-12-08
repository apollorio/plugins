<?php
/**
 * Badges and Gamification Configuration
 */

return [
	'enabled'             => true,
	'provider'            => 'badgeos',
	// 'badgeos', 'mycred', or 'none'

		'global_toggle'   => true,
	'union_level_control' => true,

	'events'              => [
		'post_created'             => [
			'points'      => 10,
			'badge'       => 'content_creator',
			'description' => 'Criou um post',
		],
		'classified_approved'      => [
			'points'      => 25,
			'badge'       => 'classified_master',
			'description' => 'Anúncio classificado aprovado',
		],

		// Digital Signature Badges
		'signature_completed'      => [
			'points'      => 15,
			'badge'       => 'contract_signed',
			'description' => 'Completou assinatura de documento legal',
			'levels'      => [
				'simple'    => [
					'points'      => 10,
					'badge'       => 'signature_simple',
					'description' => 'Assinatura eletrônica simples - Lei 14.063/2020 Art. 10 § 1º',
				],
				'advanced'  => [
					'points'      => 25,
					'badge'       => 'signature_advanced',
					'description' => 'Assinatura eletrônica avançada - Lei 14.063/2020 Art. 10 § 2º',
				],
				'qualified' => [
					'points'      => 50,
					'badge'       => 'signature_qualified',
					'description' => 'Assinatura eletrônica qualificada ICP-Brasil - Lei 14.063/2020 + MP 2.200-2/2001',
				],
			],
		],

		'document_created'         => [
			'points'      => 20,
			'badge'       => 'document_creator',
			'description' => 'Criou e enviou documento para assinatura',
		],

		'compliance_milestone'     => [
			'points'      => 100,
			'badge'       => 'compliance_champion',
			'description' => 'Atingiu marco de compliance em assinaturas qualificadas',
		],

		'marketplace_contribution' => [
			'badge'       => 'marketplace_contributor',
			'description' => 'Anúncio aprovado',
		],
		'document_signed'          => [
			'points'      => 50,
			'badge'       => 'verified_member',
			'description' => 'Documento assinado',
		],
		'group_joined'             => [
			'points'      => 5,
			'badge'       => 'community_member',
			'description' => 'Entrou em um grupo',
		],
		'invite_sent'              => [
			'points'      => 15,
			'badge'       => 'recruiter',
			'description' => 'Enviou um convite',
		],
		'event_attended'           => [
			'points'      => 20,
			'badge'       => 'participant',
			'description' => 'Participou de um evento',
		],
	],

	'levels'              => [
		'bronze'   => [
			'min_points' => 0,
			'color'      => '#CD7F32',
		],
		'silver'   => [
			'min_points' => 100,
			'color'      => '#C0C0C0',
		],
		'gold'     => [
			'min_points' => 500,
			'color'      => '#FFD700',
		],
		'platinum' => [
			'min_points' => 1000,
			'color'      => '#E5E4E2',
		],
		'diamond'  => [
			'min_points' => 2500,
			'color'      => '#B9F2FF',
		],
	],

	'display'             => [
		'show_points'      => true,
		'show_badges'      => true,
		'show_leaderboard' => true,
		'animate_awards'   => true,
	],
];
