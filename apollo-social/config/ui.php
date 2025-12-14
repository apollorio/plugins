<?php

/**
 * UI Feature Flags Configuration
 */

return [
	'features'    => [
		'anon_posts'              => true,
		'chat_enabled'            => true,
		'file_uploads'            => true,
		'real_time_notifications' => false,
		'advanced_search'         => true,
		'content_mod'             => true,
	],

	'anon_posts'  => [
		'global_enabled' => false,
		'rate_limit'     => [
			'posts_per_minute' => 1,
			'posts_per_hour'   => 10,
			'posts_per_day'    => 50,
		],
		'mod'            => [
			'auto_approve'    => false,
			'require_captcha' => true,
			'spam_detection'  => true,
		],
	],

	'uploads'     => [
		'max_file_size'         => 10485760,
		// 10MB
				'allowed_types' => [ 'jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx' ],
		'max_files_per_post'    => 5,
		'storage_path'          => 'apollo-uploads/',
	],

	'chat'        => [
		'enabled'       => true,
		'message_limit' => 100,
		'history_days'  => 30,
		'rate_limit'    => [
			'messages_per_minute' => 10,
			'messages_per_hour'   => 300,
		],
	],

	'performance' => [
		'cache_enabled'         => true,
		'cache_duration'        => 3600,
		// 1 hour
				'rate_limiting' => true,
		'audit_logging'         => true,
	],
];
