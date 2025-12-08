<?php
/**
 * User Dashboard Template - Private Dashboard (/painel/)
 *
 * Updated to use ShadCN New York style components.
 * Based on shadcn/ui dashboard template.
 *
 * @package    ApolloSocial
 * @subpackage Dashboard
 * @since      1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load dashboard layout
require_once APOLLO_SOCIAL_PLUGIN_DIR . 'templates/dashboard/dashboard-layout.php';

// Get user data
$user             = $view['data']['user'] ?? [];
$tabs             = $view['data']['tabs'] ?? [];
$is_own_dashboard = $view['data']['is_own_dashboard'] ?? false;

// Prepare events data for table
$events_data = [];
if ( ! empty( $tabs['events']['data'] ) ) {
	foreach ( $tabs['events']['data'] as $event ) {
		$events_data[] = [
			'id'        => $event['id'] ?? 0,
			'title'     => $event['title'] ?? 'Evento',
			'date'      => $event['date'] ?? '',
			'status'    => $event['status'] ?? 'publish',
			'type'      => $event['type'] ?? 'Evento',
			'permalink' => $event['permalink'] ?? '',
			'edit_url'  => $event['edit_url'] ?? '',
		];
	}
}

// Prepare my events data for table
$my_events_data = [];
if ( ! empty( $tabs['my_events']['data'] ) ) {
	foreach ( $tabs['my_events']['data'] as $event ) {
		$my_events_data[] = [
			'id'        => $event['id'] ?? 0,
			'title'     => $event['title'] ?? 'Evento',
			'date'      => $event['date'] ?? '',
			'status'    => $event['status'] ?? 'publish',
			'coauthor'  => $event['is_coauthor'] ?? false,
			'permalink' => $event['permalink'] ?? '',
			'edit_url'  => $event['edit_url'] ?? '',
		];
	}
}

// Render dashboard
apollo_render_dashboard_page(
	[
		'title'       => 'Meu Painel',
		'breadcrumbs' => [
			[
				'label' => 'Início',
				'url'   => home_url( '/' ),
			],
			[
				'label' => 'Meu Painel',
				'url'   => '',
			],
		],
		'cards'       => [
			[
				'title'       => 'Eventos Criados',
				'value'       => count( $tabs['my_events']['data'] ?? [] ),
				'description' => 'eventos publicados',
				'trend'       => 'up',
				'trend_value' => '+2 este mês',
				'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>',
			],
			[
				'title'       => 'Favoritos',
				'value'       => count( $tabs['events']['data'] ?? [] ),
				'description' => 'eventos salvos',
				'trend'       => 'up',
				'trend_value' => '+5 recentes',
				'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>',
			],
			[
				'title'       => 'Comunidades',
				'value'       => count( $tabs['communities']['data'] ?? [] ),
				'description' => 'grupos ativos',
				'badge'       => count( $tabs['nucleo']['data'] ?? [] ) . ' núcleos',
				'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
			],
			[
				'title'       => 'Documentos',
				'value'       => count( $tabs['docs']['data'] ?? [] ),
				'description' => 'para assinar',
				'trend'       => null,
				'footer'      => 'Última atividade: agora',
				'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>',
			],
		],
		'chart'       => [
			'title' => 'Atividade Recente',
			'data'  => [],
	// Add chart data here
		],
		'table'       => [
			'title'         => 'Eventos Favoritos',
			'description'   => 'Eventos que você marcou para acompanhar',
			'columns'       => [
				[
					'key'   => 'title',
					'label' => 'Evento',
				],
				[
					'key'   => 'date',
					'label' => 'Data',
				],
				[
					'key'    => 'status',
					'label'  => 'Status',
					'render' => function ( $value ) {
						return apollo_render_table_badge( $value );
					},
				],
				[
					'key'   => 'type',
					'label' => 'Tipo',
				],
			],
			'data'          => $events_data,
			'empty_message' => 'Nenhum evento favoritado ainda. Explore eventos e marque seus favoritos!',
		],
	]
);
