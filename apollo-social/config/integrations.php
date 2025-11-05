<?php
/**
 * Integration Configuration for External Plugins
 */

return [
    'itthinx_groups' => [
        'enabled' => true,
        'auto_sync' => true,
        'default_capability' => 'read',
        'group_mapping' => [
            'comunidade' => 'apollo_community',
            'nucleo' => 'apollo_core',
            'season' => 'apollo_season'
        ],
        'sync_frequency' => 'hourly'
    ],
    
    'wp_event_manager' => [
        'enabled' => true,
        'auto_create_events' => false,
        'default_status' => 'pending',
        'allowed_post_types' => ['event_listing'],
        'meta_sync' => true,
        'featured_events' => true
    ],
    
    'wpadverts' => [
        'enabled' => true,
        'auto_approve' => false,
        'category_mapping' => [
            'veiculos' => 'vehicles',
            'imoveis' => 'real_estate',
            'servicos' => 'services',
            'produtos' => 'products'
        ],
        'price_range_filter' => true,
        'location_filter' => true
    ],
    
    'badgeos' => [
        'enabled' => true,
        'auto_award' => true,
        'point_types' => ['points'],
        'achievement_types' => ['badge', 'certificate'],
        'leaderboard_integration' => true
    ],
    
    'docuseal' => [
        'enabled' => true,
        'api_endpoint' => '',
        'api_key' => '',
        'webhook_secret' => '',
        'auto_process' => true,
        'template_mapping' => [
            'membership_agreement' => '',
            'season_contract' => '',
            'classified_terms' => ''
        ]
    ],
    
    'elementor' => [
        'enabled' => true,
        'widget_categories' => ['apollo-social'],
        'canvas_compatibility' => true,
        'auto_register_widgets' => true
    ]
];