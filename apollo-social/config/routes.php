<?php
/**
 * Routes configuration for Apollo Social Core
 *
 * Maps URL patterns to handlers/templates with Canvas Mode settings.
 */

return array(
	// Users
	'/a/{id|login}'      => array(
		'pattern'     => '^a/([^/]+)/?$',
		'query_vars'  => array(
			'apollo_route' => 'user',
			'apollo_param' => '$matches[1]',
		),
		'template'    => 'users/single-user.php',
		'handler'     => 'Apollo\\Infrastructure\\Rendering\\UserPageRenderer',
		'canvas'      => true,
		'description' => 'User profile by ID or login',
	),

	// Groups - Directory pages
	'/comunidade/'       => array(
		'pattern'    => '^comunidade/?$',
		'query_vars' => array(
			'apollo_route' => 'group_directory',
			'apollo_type'  => 'comunidade',
		),
		'template'   => 'groups/directory.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\GroupDirectoryRenderer',
		'canvas'     => true,
	),
	'/nucleo/'           => array(
		'pattern'    => '^nucleo/?$',
		'query_vars' => array(
			'apollo_route' => 'group_directory',
			'apollo_type'  => 'nucleo',
		),
		'template'   => 'groups/directory.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\GroupDirectoryRenderer',
		'canvas'     => true,
	),
	'/season/'           => array(
		'pattern'    => '^season/?$',
		'query_vars' => array(
			'apollo_route' => 'group_directory',
			'apollo_type'  => 'season',
		),
		'template'   => 'groups/directory.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\GroupDirectoryRenderer',
		'canvas'     => true,
	),

	// Groups - Single pages
	'/comunidade/{slug}' => array(
		'pattern'    => '^comunidade/([^/]+)/?$',
		'query_vars' => array(
			'apollo_route' => 'group_single',
			'apollo_type'  => 'comunidade',
			'apollo_param' => '$matches[1]',
		),
		'template'   => 'groups/single-comunidade.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\GroupPageRenderer',
		'canvas'     => true,
	),
	'/nucleo/{slug}'     => array(
		'pattern'    => '^nucleo/([^/]+)/?$',
		'query_vars' => array(
			'apollo_route' => 'group_single',
			'apollo_type'  => 'nucleo',
			'apollo_param' => '$matches[1]',
		),
		'template'   => 'groups/single-nucleo.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\GroupPageRenderer',
		'canvas'     => true,
	),
	'/season/{slug}'     => array(
		'pattern'    => '^season/([^/]+)/?$',
		'query_vars' => array(
			'apollo_route' => 'group_single',
			'apollo_type'  => 'season',
			'apollo_param' => '$matches[1]',
		),
		'template'   => 'groups/single-season.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\GroupPageRenderer',
		'canvas'     => true,
	),

	// Membros (Portuguese naming - primary routes)
	'/membro/'           => array(
		'pattern'    => '^membro/?$',
		'query_vars' => array( 'apollo_route' => 'membro_directory' ),
		'template'   => 'memberships/archive.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\MembroDirectoryRenderer',
		'canvas'     => true,
	),
	'/membro/{slug}'     => array(
		'pattern'    => '^membro/([^/]+)/?$',
		'query_vars' => array(
			'apollo_route' => 'membro_single',
			'apollo_param' => '$matches[1]',
		),
		'template'   => 'memberships/single.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\MembroPageRenderer',
		'canvas'     => true,
	),

	// Legacy: Memberships / Unions (deprecated aliases)
	'/membership/'       => array(
		'pattern'    => '^membership/?$',
		'query_vars' => array( 'apollo_route' => 'membro_directory' ),
		'template'   => 'memberships/archive.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\MembroDirectoryRenderer',
		'canvas'     => true,
	),
	'/uniao/{slug}'      => array(
		'pattern'    => '^uniao/([^/]+)/?$',
		'query_vars' => array(
			'apollo_route' => 'membro_single',
			'apollo_param' => '$matches[1]',
		),
		'template'   => 'memberships/single.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\MembroPageRenderer',
		'canvas'     => true,
	),

	// Classifieds
	'/anuncio/'          => array(
		'pattern'    => '^anuncio/?$',
		'query_vars' => array( 'apollo_route' => 'ad_directory' ),
		'template'   => 'classifieds/archive.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\AdDirectoryRenderer',
		'canvas'     => true,
	),
	'/anuncio/{slug}'    => array(
		'pattern'    => '^anuncio/([^/]+)/?$',
		'query_vars' => array(
			'apollo_route' => 'ad_single',
			'apollo_param' => '$matches[1]',
		),
		'template'   => 'classifieds/single.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\AdPageRenderer',
		'canvas'     => true,
	),

	// Cena-Rio Canvas Page
	'/cena/'             => array(
		'pattern'    => '^cena/?$',
		'query_vars' => array( 'apollo_route' => 'cena_rio' ),
		'template'   => 'cena/cena.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\CenaRioRenderer',
		'canvas'     => true,
		'assets'     => array(
			'css' => array(
				'apollo-cena' => array(
					'src'     => APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/cena.css',
					'deps'    => array( 'apollo-canvas-mode' ),
					'version' => APOLLO_SOCIAL_VERSION,
				),
			),
			'js'  => array(
				'apollo-cena' => array(
					'src'     => APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/cena.js',
					'deps'    => array( 'apollo-canvas', 'motion' ),
					'version' => APOLLO_SOCIAL_VERSION,
				),
			),
		),
	),
	'/cena-rio/'         => array(
		'pattern'    => '^cena-rio/?$',
		'query_vars' => array( 'apollo_route' => 'cena_rio' ),
		'template'   => 'cena/cena.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\CenaRioRenderer',
		'canvas'     => true,
		'assets'     => array(
			'css' => array(
				'apollo-cena' => array(
					'src'     => APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/cena.css',
					'deps'    => array( 'apollo-canvas-mode' ),
					'version' => APOLLO_SOCIAL_VERSION,
				),
			),
			'js'  => array(
				'apollo-cena' => array(
					'src'     => APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/cena.js',
					'deps'    => array( 'apollo-canvas', 'motion' ),
					'version' => APOLLO_SOCIAL_VERSION,
				),
			),
		),
	),

	// User Dashboard (Painel)
	'/painel/'           => array(
		'pattern'    => '^painel/?$',
		'query_vars' => array( 'apollo_route' => 'user_dashboard_private' ),
		'template'   => 'users/private-profile.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\RawTemplateRenderer',
		'canvas'     => true,
		'raw_html'   => true,
	),

	// Public Profile Alias
	'/clubber/{userID}'  => array(
		'pattern'     => '^clubber/([0-9]+)/?$',
		'query_vars'  => array(
			'apollo_route' => 'user_dashboard',
			'user_id'      => '$matches[1]',
		),
		'template'    => 'users/dashboard.php',
		'handler'     => 'Apollo\\Infrastructure\\Rendering\\UserDashboardRenderer',
		'canvas'      => true,
		'description' => 'Public profile (Clubber alias)',
	),

	// Feed (Apollo Social Feed)
	'/feed/'             => array(
		'pattern'        => '^feed/?$',
		'query_vars'     => array( 'apollo_route' => 'feed' ),
		'template'       => 'feed/feed.php',
		'handler'        => 'Apollo\\Infrastructure\\Rendering\\FeedRenderer',
		// FASE 2: Usar FeedRenderer
				'canvas' => true,
		'priority'       => 'bottom',
		'raw_html'       => true,
		'assets'         => array(
			// FASE 2: Assets específicos do feed
						'css' => array(
							'apollo-feed-css' => array(
								'src'     => APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/feed.css',
								'deps'    => array( 'apollo-canvas-mode' ),
								'version' => APOLLO_SOCIAL_VERSION,
							),
						),
			'js'              => array(
				'apollo-feed-js' => array(
					'src'       => APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/feed.js',
					'deps'      => array( 'jquery', 'apollo-canvas' ),
					'version'   => APOLLO_SOCIAL_VERSION,
					'in_footer' => true,
				),
			),
		),
	),

	// Chat
	'/chat/'             => array(
		'pattern'    => '^chat/?$',
		'query_vars' => array( 'apollo_route' => 'chat' ),
		'template'   => 'chat/chat.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\RawTemplateRenderer',
		'canvas'     => true,
		'raw_html'   => true,
	),

	// Chat - Single User
	'/chat/{userID}'     => array(
		'pattern'    => '^chat/([0-9]+)/?$',
		'query_vars' => array(
			'apollo_route' => 'chat_user',
			'user_id'      => '$matches[1]',
		),
		'template'   => 'chat/chat-single.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\ChatSingleRenderer',
		'canvas'     => true,
		'assets'     => array(
			'css' => array(
				'apollo-chat' => array(
					'src'     => APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/chat.css',
					'deps'    => array( 'apollo-canvas-mode' ),
					'version' => APOLLO_SOCIAL_VERSION,
				),
			),
			'js'  => array(
				'apollo-chat' => array(
					'src'     => APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/chat.js',
					'deps'    => array( 'apollo-canvas' ),
					'version' => APOLLO_SOCIAL_VERSION,
				),
			),
		),
	),

	// User Profile by ID - Customizable Dashboard
	'/id/{userID}'       => array(
		'pattern'    => '^id/([0-9]+)/?$',
		'query_vars' => array(
			'apollo_route' => 'user_dashboard',
			'user_id'      => '$matches[1]',
		),
		'template'   => 'users/dashboard.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\UserDashboardRenderer',
		'canvas'     => true,
		'assets'     => array(
			'css' => array(
				'apollo-dashboard' => array(
					'src'     => APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/dashboard.css',
					'deps'    => array( 'apollo-canvas-mode' ),
					'version' => APOLLO_SOCIAL_VERSION,
				),
			),
			'js'  => array(
				'apollo-dashboard' => array(
					'src'     => APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/dashboard-builder.js',
					'deps'    => array( 'apollo-canvas', 'motion' ),
					'version' => APOLLO_SOCIAL_VERSION,
				),
			),
		),
	),

	// Users Directory - /eco/
	'/eco/'              => array(
		'pattern'    => '^eco/?$',
		'query_vars' => array( 'apollo_route' => 'users_directory' ),
		'template'   => 'users/directory.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\UsersDirectoryRenderer',
		'canvas'     => true,
		'assets'     => array(
			'css' => array(
				'apollo-users-directory' => array(
					'src'     => APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/users-directory.css',
					'deps'    => array( 'apollo-canvas-mode' ),
					'version' => APOLLO_SOCIAL_VERSION,
				),
			),
			'js'  => array(
				'apollo-users-directory' => array(
					'src'     => APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/users-directory.js',
					'deps'    => array( 'apollo-canvas' ),
					'version' => APOLLO_SOCIAL_VERSION,
				),
			),
		),
	),

	// Users Directory Alternative - /ecoa/
	'/ecoa/'             => array(
		'pattern'    => '^ecoa/?$',
		'query_vars' => array( 'apollo_route' => 'users_directory' ),
		'template'   => 'users/directory.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\UsersDirectoryRenderer',
		'canvas'     => true,
		'assets'     => array(
			'css' => array(
				'apollo-users-directory' => array(
					'src'     => APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/users-directory.css',
					'deps'    => array( 'apollo-canvas-mode' ),
					'version' => APOLLO_SOCIAL_VERSION,
				),
			),
			'js'  => array(
				'apollo-users-directory' => array(
					'src'     => APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/users-directory.js',
					'deps'    => array( 'apollo-canvas' ),
					'version' => APOLLO_SOCIAL_VERSION,
				),
			),
		),
	),

	// Cena::rio Page
	'/cena/'             => array(
		'pattern'    => '^cena/?$',
		'query_vars' => array( 'apollo_route' => 'cena' ),
		'template'   => 'cena/cena.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\CenaRenderer',
		'canvas'     => true,
		'assets'     => array(
			'css' => array(
				'apollo-cena' => array(
					'src'     => APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/cena.css',
					'deps'    => array( 'apollo-canvas-mode' ),
					'version' => APOLLO_SOCIAL_VERSION,
				),
			),
			'js'  => array(
				'apollo-cena' => array(
					'src'     => APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/cena.js',
					'deps'    => array( 'apollo-canvas', 'motion' ),
					'version' => APOLLO_SOCIAL_VERSION,
				),
			),
		),
	),

	// Cena::rio Alternative Route
	'/cena-rio/'         => array(
		'pattern'    => '^cena-rio/?$',
		'query_vars' => array( 'apollo_route' => 'cena' ),
		'template'   => 'cena/cena.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\CenaRenderer',
		'canvas'     => true,
		'assets'     => array(
			'css' => array(
				'apollo-cena' => array(
					'src'     => APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/cena.css',
					'deps'    => array( 'apollo-canvas-mode' ),
					'version' => APOLLO_SOCIAL_VERSION,
				),
			),
			'js'  => array(
				'apollo-cena' => array(
					'src'     => APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/cena.js',
					'deps'    => array( 'apollo-canvas', 'motion' ),
					'version' => APOLLO_SOCIAL_VERSION,
				),
			),
		),
	),

	// User Dashboard /painel/
	'/painel/'           => array(
		'pattern'    => '^painel/?$',
		'query_vars' => array( 'apollo_route' => 'user_dashboard' ),
		'template'   => 'users/dashboard.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\UserDashboardRenderer',
		'canvas'     => true,
		'assets'     => array(
			'css' => array(
				'apollo-dashboard' => array(
					'src'     => APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/dashboard.css',
					'deps'    => array( 'apollo-canvas-mode' ),
					'version' => APOLLO_SOCIAL_VERSION,
				),
			),
			'js'  => array(
				'apollo-dashboard' => array(
					'src'     => APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/dashboard-builder.js',
					'deps'    => array( 'apollo-canvas', 'motion' ),
					'version' => APOLLO_SOCIAL_VERSION,
				),
			),
		),
	),

	// Clubber route (alternative to /id/)
	'/clubber/{userID}'  => array(
		'pattern'    => '^clubber/([0-9]+)/?$',
		'query_vars' => array(
			'apollo_route' => 'user_dashboard',
			'user_id'      => '$matches[1]',
		),
		'template'   => 'users/dashboard.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\UserDashboardRenderer',
		'canvas'     => true,
		'assets'     => array(
			'css' => array(
				'apollo-dashboard' => array(
					'src'     => APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/dashboard.css',
					'deps'    => array( 'apollo-canvas-mode' ),
					'version' => APOLLO_SOCIAL_VERSION,
				),
			),
			'js'  => array(
				'apollo-dashboard' => array(
					'src'     => APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/dashboard-builder.js',
					'deps'    => array( 'apollo-canvas', 'motion' ),
					'version' => APOLLO_SOCIAL_VERSION,
				),
			),
		),
	),

	// FASE 1: Documentos Routes (Canvas Mode)
	'/doc/new'           => array(
		'pattern'    => '^doc/new/?$',
		'query_vars' => array( 'apollo_route' => 'doc_new' ),
		'template'   => 'documents/editor.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\RawTemplateRenderer',
		'canvas'     => true,
		'raw_html'   => true,
	),
	'/doc/{file_id}'     => array(
		'pattern'    => '^doc/([a-zA-Z0-9]+)/?$',
		'query_vars' => array(
			'apollo_route' => 'doc_edit',
			'file_id'      => '$matches[1]',
		),
		'template'   => 'documents/editor.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\RawTemplateRenderer',
		'canvas'     => true,
		'raw_html'   => true,
	),
	'/pla/new'           => array(
		'pattern'    => '^pla/new/?$',
		'query_vars' => array( 'apollo_route' => 'pla_new' ),
		'template'   => 'documents/spreadsheet-editor.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\RawTemplateRenderer',
		'canvas'     => true,
		'raw_html'   => true,
	),
	'/pla/{file_id}'     => array(
		'pattern'    => '^pla/([a-zA-Z0-9]+)/?$',
		'query_vars' => array(
			'apollo_route' => 'pla_edit',
			'file_id'      => '$matches[1]',
		),
		'template'   => 'documents/spreadsheet-editor.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\RawTemplateRenderer',
		'canvas'     => true,
		'raw_html'   => true,
	),
	'/sign'              => array(
		'pattern'    => '^sign/?$',
		'query_vars' => array( 'apollo_route' => 'sign_list' ),
		'template'   => 'documents/sign-list.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\RawTemplateRenderer',
		'canvas'     => true,
		'raw_html'   => true,
	),
	'/sign/{token}'      => array(
		'pattern'    => '^sign/([a-zA-Z0-9]+)/?$',
		'query_vars' => array(
			'apollo_route'    => 'sign_document',
			'signature_token' => '$matches[1]',
		),
		'template'   => 'documents/sign-document.php',
		'handler'    => 'Apollo\\Infrastructure\\Rendering\\RawTemplateRenderer',
		'canvas'     => true,
		'raw_html'   => true,
	),
);
