<?php

declare(strict_types=1);
/**
 * Apollo Navbar - Placeholder Data
 *
 * Provides example data for notifications and chat when system has no real data.
 * Remove or comment out these filters when real notification/chat systems are active.
 *
 * @package Apollo_Core
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Add placeholder notifications for scroll row (horizontal cards)
 *
 * Filter: 'apollo_navbar_scroll_notifications'
 * Returns array of notification objects with: title, message, time, color
 */
add_filter('apollo_navbar_scroll_notifications', function ($notifications) {
	// Only add placeholders if empty
	if (!empty($notifications)) {
		return $notifications;
	}

	return [
		[
			'title' => 'Alerta Sistema',
			'message' => 'Backup do servidor concluído.',
			'time' => '2m atrás',
			'color' => 'bg-red'
		],
		[
			'title' => 'Júlia M.',
			'message' => 'Marcou você em "Revisão de UI".',
			'time' => '15m atrás',
			'color' => 'bg-blue'
		],
		[
			'title' => 'Gabriel',
			'message' => 'Projeto finalizado com sucesso.',
			'time' => '30m atrás',
			'color' => 'bg-green'
		]
	];
}, 10, 1);

/**
 * Add placeholder notifications for dropdown menu (vertical list)
 *
 * Filter: 'apollo_navbar_notifications'
 * Returns array of notification objects with: id, title, message, time, color
 */
add_filter('apollo_navbar_notifications', function ($notifications) {
	// Only add placeholders if empty
	if (!empty($notifications)) {
		return $notifications;
	}

	return [
		[
			'id' => 'notif-1',
			'title' => 'Alerta de Sistema',
			'message' => 'Backup do servidor finalizado.',
			'time' => '2 min atrás',
			'color' => 'bg-red'
		],
		[
			'id' => 'notif-2',
			'title' => 'Júlia M.',
			'message' => 'Marcou você em "Revisão de UI".',
			'time' => '15 min atrás',
			'color' => 'bg-blue'
		],
		[
			'id' => 'notif-3',
			'title' => 'Recursos Humanos',
			'message' => 'Documento de férias aprovado.',
			'time' => '1 hora atrás',
			'color' => 'bg-green'
		]
	];
}, 10, 1);

/**
 * Add placeholder chat conversations
 *
 * Filter: 'apollo_navbar_chat_conversations'
 * Returns array of conversation objects with: id, name, message, time, color, is_me
 */
add_filter('apollo_navbar_chat_conversations', function ($conversations) {
	// Only add placeholders if empty
	if (!empty($conversations)) {
		return $conversations;
	}

	return [
		[
			'id' => 'chat-1',
			'name' => 'Matheus',
			'message' => 'Cara, você viu o novo layout? Ficou insano!',
			'time' => 'Agora',
			'color' => 'bg-gray',
			'is_me' => false
		],
		[
			'id' => 'chat-2',
			'name' => 'Bruna',
			'message' => 'Reunião adiada para as 16h.',
			'time' => '5m atrás',
			'color' => 'bg-purple',
			'is_me' => false
		],
		[
			'id' => 'chat-3',
			'name' => 'Equipe Dev',
			'message' => 'Subindo o deploy em 5 minutos...',
			'time' => '20m atrás',
			'color' => 'bg-orange',
			'is_me' => true
		]
	];
}, 10, 1);

/**
 * Add Admin Panel app for administrators
 *
 * Filter: 'apollo_navbar_apps_list'
 * Adds admin panel icon for users with 'manage_options' capability
 */
add_filter('apollo_navbar_apps_list', function ($apps) {
	// Only show for administrators
	if (!current_user_can('manage_options')) {
		return $apps;
	}

	// Check if admin panel app already exists
	$has_admin = false;
	foreach ($apps as $app) {
		if (isset($app['id']) && $app['id'] === 'admin-panel') {
			$has_admin = true;
			break;
		}
	}

	// Add admin panel app at the beginning
	if (!$has_admin) {
		array_unshift($apps, [
			'id' => 'admin-panel',
			'label' => 'Admin',
			'icon' => 'ri-settings-3-fill',
			'icon_text' => 'AD',
			'background_type' => 'gradient',
			'background_gradient' => 'linear-gradient(135deg, #6366f1, #4f46e5)',
			'background_image' => '',
			'url' => admin_url(),
			'target' => '_blank',
			'is_default' => false,
			'order' => 0
		]);
	}

	return $apps;
}, 10, 1);
