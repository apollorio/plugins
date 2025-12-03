<?php
/**
 * Integration Configuration for External Plugins
 */

return array(
	'itthinx_groups'   => array(
		'enabled'            => true,
		'auto_sync'          => true,
		'default_capability' => 'read',
		'group_mapping'      => array(
			'comunidade' => 'apollo_community',
			'nucleo'     => 'apollo_core',
			'season'     => 'apollo_season',
		),
		'sync_frequency'     => 'hourly',
	),

	'wp_event_manager' => array(
		'enabled'            => true,
		'auto_create_events' => false,
		'default_status'     => 'pending',
		'allowed_post_types' => array( 'event_listing' ),
		'meta_sync'          => true,
		'featured_events'    => true,
	),

	'wpadverts'        => array(
		'enabled'            => true,
		'auto_approve'       => false,
		'category_mapping'   => array(
			'veiculos' => 'vehicles',
			'imoveis'  => 'real_estate',
			'servicos' => 'services',
			'produtos' => 'products',
		),
		'price_range_filter' => true,
		'location_filter'    => true,
	),

	'badgeos'          => array(
		'enabled'                 => true,
		'auto_award'              => true,
		'point_types'             => array( 'points' ),
		'achievement_types'       => array( 'badge', 'certificate' ),
		'leaderboard_integration' => true,
	),

	'elementor'        => array(
		'enabled'               => true,
		'widget_categories'     => array( 'apollo-social' ),
		'canvas_compatibility'  => true,
		'auto_register_widgets' => true,
	),
);
