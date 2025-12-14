<?php

/**
 * Analytics Configuration (Script Only - No API)
 * Simple analytics with Plausible script injection on Canvas routes
 */

return [
	/*
	|--------------------------------------------------------------------------
	| Analytics Driver
	|--------------------------------------------------------------------------
	|
	| Driver for analytics tracking. Options:
	| - 'plausible': Plausible Analytics (script only, no API)
	| - 'local': Local storage counters only
	| - 'disabled': No analytics
	|
	*/
	'enabled'          => true,
	'driver'           => 'plausible',

	/*
	|--------------------------------------------------------------------------
	| Plausible Configuration (Script Only)
	|--------------------------------------------------------------------------
	|
	| Simple Plausible integration without API - just script injection
	|
	*/
	'plausible'        => [
		'domain'                   => '',
		// Your domain (e.g., 'mysite.com')
				'script_url'       => 'https://plausible.io/js/script.js',
		// Or self-hosted
				'inject_on_canvas' => true,
		// Only inject on Apollo Canvas routes
				'custom_events'    => true,
		// Enable custom events tracking
				'outbound_links'   => true,
		// Track outbound clicks
				'file_downloads'   => false,
// Track file downloads
	],

	/*
	|--------------------------------------------------------------------------
	| Canvas Route Injection
	|--------------------------------------------------------------------------
	|
	| Analytics will only be injected on these Apollo routes
	|
	*/
	'canvas_routes'    => [
		'/a/*',
		'/comunidade/*',
		'/nucleo/*',
		'/season/*',
		'/membership',
		'/uniao/*',
		'/anuncio/*',
		'/apollo/*',
	],

	/*
	|--------------------------------------------------------------------------
	| Custom Events (Social + Events)
	|--------------------------------------------------------------------------
	|
	| Standard events tracked across the platform
	|
	*/
	'events'           => [
		// Groups & Communities
		'group_view'           => [
			'enabled'     => true,
			'description' => 'Usuário visualizou página de grupo',
		],
		'group_join'           => [
			'enabled'     => true,
			'description' => 'Usuário se juntou a um grupo',
		],
		'invite_sent'          => [
			'enabled'     => true,
			'description' => 'Convite para grupo enviado',
		],
		'invite_approved'      => [
			'enabled'     => true,
			'description' => 'Convite para grupo aprovado',
		],

		// Classified Ads
		'ad_view'              => [
			'enabled'     => true,
			'description' => 'Visualização de anúncio',
		],
		'ad_create'            => [
			'enabled'     => true,
			'description' => 'Criação de novo anúncio',
		],
		'ad_publish'           => [
			'enabled'     => true,
			'description' => 'Anúncio publicado',
		],

		// Events
		'event_view'           => [
			'enabled'     => true,
			'description' => 'Visualização de evento',
		],
		'event_filter_applied' => [
			'enabled'     => true,
			'description' => 'Filtro aplicado na listagem de eventos',
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Local Storage Counters
	|--------------------------------------------------------------------------
	|
	| Simple session-based counters for Canvas statistics panel
	|
	*/
	'local_counters'   => [
		'session_page_views' => true,
		'session_events'     => true,
		'total_interactions' => true,
		'group_interactions' => true,
		'ad_interactions'    => true,
		'event_interactions' => true,
	],

	/*
	|--------------------------------------------------------------------------
	| Statistics Panel Configuration
	|--------------------------------------------------------------------------
	|
	| Configuration for the Canvas statistics page
	|
	*/
	'statistics_panel' => [
		'enabled'                => true,
		'show_session_stats'     => true,
		'show_local_counters'    => true,
		'show_plausible_embed'   => false,
		// Set to true if you have public dashboard
				'cache_duration' => 300,
	// 5 minutes
	],
];
