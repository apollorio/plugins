<?php

/**
 * Analytics Configuration (Script Only - No API)
 * Simple analytics with Plausible script injection on Canvas routes
 */

return array(
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
	'plausible'        => array(
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
	),

	/*
	|--------------------------------------------------------------------------
	| Canvas Route Injection
	|--------------------------------------------------------------------------
	|
	| Analytics will only be injected on these Apollo routes
	|
	*/
	'canvas_routes'    => array(
		'/a/*',
		'/comunidade/*',
		'/nucleo/*',
		'/season/*',
		'/membership',
		'/uniao/*',
		'/anuncio/*',
		'/apollo/*',
	),

	/*
	|--------------------------------------------------------------------------
	| Custom Events (Social + Events)
	|--------------------------------------------------------------------------
	|
	| Standard events tracked across the platform
	|
	*/
	'events'           => array(
		// Groups & Communities
		'group_view'           => array(
			'enabled'     => true,
			'description' => 'Usuário visualizou página de grupo',
		),
		'group_join'           => array(
			'enabled'     => true,
			'description' => 'Usuário se juntou a um grupo',
		),
		'invite_sent'          => array(
			'enabled'     => true,
			'description' => 'Convite para grupo enviado',
		),
		'invite_approved'      => array(
			'enabled'     => true,
			'description' => 'Convite para grupo aprovado',
		),

		// Classified Ads
		'ad_view'              => array(
			'enabled'     => true,
			'description' => 'Visualização de anúncio',
		),
		'ad_create'            => array(
			'enabled'     => true,
			'description' => 'Criação de novo anúncio',
		),
		'ad_publish'           => array(
			'enabled'     => true,
			'description' => 'Anúncio publicado',
		),

		// Events
		'event_view'           => array(
			'enabled'     => true,
			'description' => 'Visualização de evento',
		),
		'event_filter_applied' => array(
			'enabled'     => true,
			'description' => 'Filtro aplicado na listagem de eventos',
		),
	),

	/*
	|--------------------------------------------------------------------------
	| Local Storage Counters
	|--------------------------------------------------------------------------
	|
	| Simple session-based counters for Canvas statistics panel
	|
	*/
	'local_counters'   => array(
		'session_page_views' => true,
		'session_events'     => true,
		'total_interactions' => true,
		'group_interactions' => true,
		'ad_interactions'    => true,
		'event_interactions' => true,
	),

	/*
	|--------------------------------------------------------------------------
	| Statistics Panel Configuration
	|--------------------------------------------------------------------------
	|
	| Configuration for the Canvas statistics page
	|
	*/
	'statistics_panel' => array(
		'enabled'                => true,
		'show_session_stats'     => true,
		'show_local_counters'    => true,
		'show_plausible_embed'   => false,
		// Set to true if you have public dashboard
				'cache_duration' => 300,
	// 5 minutes
	),
);
