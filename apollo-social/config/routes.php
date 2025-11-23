<?php
/**
 * Routes configuration for Apollo Social Core
 *
 * Maps URL patterns to handlers/templates with Canvas Mode settings.
 */

return [
    // Users
    '/a/{id|login}' => [
        'pattern' => '^a/([^/]+)/?$',
        'query_vars' => ['apollo_route' => 'user', 'apollo_param' => '$matches[1]'],
        'template' => 'users/single-user.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\UserPageRenderer',
        'canvas' => true,
        'description' => 'User profile by ID or login',
    ],

    // Groups - Directory pages
    '/comunidade/' => [
        'pattern' => '^comunidade/?$',
        'query_vars' => ['apollo_route' => 'group_directory', 'apollo_type' => 'comunidade'],
        'template' => 'groups/directory.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\GroupDirectoryRenderer',
        'canvas' => true,
    ],
    '/nucleo/' => [
        'pattern' => '^nucleo/?$',
        'query_vars' => ['apollo_route' => 'group_directory', 'apollo_type' => 'nucleo'],
        'template' => 'groups/directory.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\GroupDirectoryRenderer',
        'canvas' => true,
    ],
    '/season/' => [
        'pattern' => '^season/?$',
        'query_vars' => ['apollo_route' => 'group_directory', 'apollo_type' => 'season'],
        'template' => 'groups/directory.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\GroupDirectoryRenderer',
        'canvas' => true,
    ],

    // Groups - Single pages
    '/comunidade/{slug}' => [
        'pattern' => '^comunidade/([^/]+)/?$',
        'query_vars' => ['apollo_route' => 'group_single', 'apollo_type' => 'comunidade', 'apollo_param' => '$matches[1]'],
        'template' => 'groups/single-comunidade.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\GroupPageRenderer',
        'canvas' => true,
    ],
    '/nucleo/{slug}' => [
        'pattern' => '^nucleo/([^/]+)/?$',
        'query_vars' => ['apollo_route' => 'group_single', 'apollo_type' => 'nucleo', 'apollo_param' => '$matches[1]'],
        'template' => 'groups/single-nucleo.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\GroupPageRenderer',
        'canvas' => true,
    ],
    '/season/{slug}' => [
        'pattern' => '^season/([^/]+)/?$',
        'query_vars' => ['apollo_route' => 'group_single', 'apollo_type' => 'season', 'apollo_param' => '$matches[1]'],
        'template' => 'groups/single-season.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\GroupPageRenderer',
        'canvas' => true,
    ],

    // Memberships / Unions
    '/membership/' => [
        'pattern' => '^membership/?$',
        'query_vars' => ['apollo_route' => 'union_directory'],
        'template' => 'memberships/archive.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\UnionDirectoryRenderer',
        'canvas' => true,
    ],
    '/uniao/{slug}' => [
        'pattern' => '^uniao/([^/]+)/?$',
        'query_vars' => ['apollo_route' => 'union_single', 'apollo_param' => '$matches[1]'],
        'template' => 'memberships/single.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\UnionPageRenderer',
        'canvas' => true,
    ],

    // Classifieds
    '/anuncio/' => [
        'pattern' => '^anuncio/?$',
        'query_vars' => ['apollo_route' => 'ad_directory'],
        'template' => 'classifieds/archive.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\AdDirectoryRenderer',
        'canvas' => true,
    ],
    '/anuncio/{slug}' => [
        'pattern' => '^anuncio/([^/]+)/?$',
        'query_vars' => ['apollo_route' => 'ad_single', 'apollo_param' => '$matches[1]'],
        'template' => 'classifieds/single.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\AdPageRenderer',
        'canvas' => true,
    ],

    // Cena-Rio Canvas Page
    '/cena/' => [
        'pattern' => '^cena/?$',
        'query_vars' => ['apollo_route' => 'cena_rio'],
        'template' => 'cena-rio/page.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\RawTemplateRenderer',
        'canvas' => true,
        'raw_html' => true,
    ],
    '/cena-rio/' => [
        'pattern' => '^cena-rio/?$',
        'query_vars' => ['apollo_route' => 'cena_rio'],
        'template' => 'cena-rio/page.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\RawTemplateRenderer',
        'canvas' => true,
        'raw_html' => true,
    ],

    // User Dashboard (Painel)
    '/painel/' => [
        'pattern' => '^painel/?$',
        'query_vars' => ['apollo_route' => 'user_dashboard_private'],
        'template' => 'users/private-profile.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\RawTemplateRenderer',
        'canvas' => true,
        'raw_html' => true,
    ],

    // Public Profile Alias
    '/clubber/{userID}' => [
        'pattern' => '^clubber/([0-9]+)/?$',
        'query_vars' => ['apollo_route' => 'user_dashboard', 'user_id' => '$matches[1]'],
        'template' => 'users/dashboard.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\UserDashboardRenderer',
        'canvas' => true,
        'description' => 'Public profile (Clubber alias)',
    ],

    // Feed (Apollo Social Feed)
    '/feed/' => [
        'pattern' => '^feed/?$',
        'query_vars' => ['apollo_route' => 'feed'],
        'template' => 'feed/feed.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\FeedRenderer', // FASE 2: Usar FeedRenderer
        'canvas' => true,
        'priority' => 'bottom',
        'raw_html' => true,
        'assets' => [ // FASE 2: Assets específicos do feed
            'css' => [
                'apollo-feed-css' => [
                    'src' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/feed.css',
                    'deps' => ['apollo-canvas-mode'],
                    'version' => APOLLO_SOCIAL_VERSION,
                ],
            ],
            'js' => [
                'apollo-feed-js' => [
                    'src' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/feed.js',
                    'deps' => ['jquery', 'apollo-canvas'],
                    'version' => APOLLO_SOCIAL_VERSION,
                    'in_footer' => true,
                ],
            ],
        ],
    ],

    // Chat
    '/chat/' => [
        'pattern' => '^chat/?$',
        'query_vars' => ['apollo_route' => 'chat'],
        'template' => 'chat/chat.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\RawTemplateRenderer',
        'canvas' => true,
        'raw_html' => true,
    ],

    // Chat - Single User
    '/chat/{userID}' => [
        'pattern' => '^chat/([0-9]+)/?$',
        'query_vars' => ['apollo_route' => 'chat_user', 'user_id' => '$matches[1]'],
        'template' => 'chat/chat-single.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\ChatSingleRenderer',
        'canvas' => true,
        'assets' => [
            'css' => [
                'apollo-chat' => [
                    'src' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/chat.css',
                    'deps' => ['apollo-canvas-mode'],
                    'version' => APOLLO_SOCIAL_VERSION,
                ],
            ],
            'js' => [
                'apollo-chat' => [
                    'src' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/chat.js',
                    'deps' => ['apollo-canvas'],
                    'version' => APOLLO_SOCIAL_VERSION,
                ],
            ],
        ],
    ],

    // User Profile by ID - Customizable Dashboard
    '/id/{userID}' => [
        'pattern' => '^id/([0-9]+)/?$',
        'query_vars' => ['apollo_route' => 'user_dashboard', 'user_id' => '$matches[1]'],
        'template' => 'users/dashboard.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\UserDashboardRenderer',
        'canvas' => true,
        'assets' => [
            'css' => [
                'apollo-dashboard' => [
                    'src' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/dashboard.css',
                    'deps' => ['apollo-canvas-mode'],
                    'version' => APOLLO_SOCIAL_VERSION,
                ],
            ],
            'js' => [
                'apollo-dashboard' => [
                    'src' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/dashboard-builder.js',
                    'deps' => ['apollo-canvas', 'motion'],
                    'version' => APOLLO_SOCIAL_VERSION,
                ],
            ],
        ],
    ],

    // Users Directory - /eco/
    '/eco/' => [
        'pattern' => '^eco/?$',
        'query_vars' => ['apollo_route' => 'users_directory'],
        'template' => 'users/directory.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\UsersDirectoryRenderer',
        'canvas' => true,
        'assets' => [
            'css' => [
                'apollo-users-directory' => [
                    'src' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/users-directory.css',
                    'deps' => ['apollo-canvas-mode'],
                    'version' => APOLLO_SOCIAL_VERSION,
                ],
            ],
            'js' => [
                'apollo-users-directory' => [
                    'src' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/users-directory.js',
                    'deps' => ['apollo-canvas'],
                    'version' => APOLLO_SOCIAL_VERSION,
                ],
            ],
        ],
    ],

    // Users Directory Alternative - /ecoa/
    '/ecoa/' => [
        'pattern' => '^ecoa/?$',
        'query_vars' => ['apollo_route' => 'users_directory'],
        'template' => 'users/directory.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\UsersDirectoryRenderer',
        'canvas' => true,
        'assets' => [
            'css' => [
                'apollo-users-directory' => [
                    'src' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/users-directory.css',
                    'deps' => ['apollo-canvas-mode'],
                    'version' => APOLLO_SOCIAL_VERSION,
                ],
            ],
            'js' => [
                'apollo-users-directory' => [
                    'src' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/users-directory.js',
                    'deps' => ['apollo-canvas'],
                    'version' => APOLLO_SOCIAL_VERSION,
                ],
            ],
        ],
    ],

    // Cena::rio Page
    '/cena/' => [
        'pattern' => '^cena/?$',
        'query_vars' => ['apollo_route' => 'cena'],
        'template' => 'cena/cena.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\CenaRenderer',
        'canvas' => true,
        'assets' => [
            'css' => [
                'apollo-cena' => [
                    'src' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/cena.css',
                    'deps' => ['apollo-canvas-mode'],
                    'version' => APOLLO_SOCIAL_VERSION,
                ],
            ],
            'js' => [
                'apollo-cena' => [
                    'src' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/cena.js',
                    'deps' => ['apollo-canvas', 'motion'],
                    'version' => APOLLO_SOCIAL_VERSION,
                ],
            ],
        ],
    ],

    // Cena::rio Alternative Route
    '/cena-rio/' => [
        'pattern' => '^cena-rio/?$',
        'query_vars' => ['apollo_route' => 'cena'],
        'template' => 'cena/cena.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\CenaRenderer',
        'canvas' => true,
        'assets' => [
            'css' => [
                'apollo-cena' => [
                    'src' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/cena.css',
                    'deps' => ['apollo-canvas-mode'],
                    'version' => APOLLO_SOCIAL_VERSION,
                ],
            ],
            'js' => [
                'apollo-cena' => [
                    'src' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/cena.js',
                    'deps' => ['apollo-canvas', 'motion'],
                    'version' => APOLLO_SOCIAL_VERSION,
                ],
            ],
        ],
    ],

    // User Dashboard /painel/
    '/painel/' => [
        'pattern' => '^painel/?$',
        'query_vars' => ['apollo_route' => 'user_dashboard'],
        'template' => 'users/dashboard.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\UserDashboardRenderer',
        'canvas' => true,
        'assets' => [
            'css' => [
                'apollo-dashboard' => [
                    'src' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/dashboard.css',
                    'deps' => ['apollo-canvas-mode'],
                    'version' => APOLLO_SOCIAL_VERSION,
                ],
            ],
            'js' => [
                'apollo-dashboard' => [
                    'src' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/dashboard-builder.js',
                    'deps' => ['apollo-canvas', 'motion'],
                    'version' => APOLLO_SOCIAL_VERSION,
                ],
            ],
        ],
    ],

    // Clubber route (alternative to /id/)
    '/clubber/{userID}' => [
        'pattern' => '^clubber/([0-9]+)/?$',
        'query_vars' => ['apollo_route' => 'user_dashboard', 'user_id' => '$matches[1]'],
        'template' => 'users/dashboard.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\UserDashboardRenderer',
        'canvas' => true,
        'assets' => [
            'css' => [
                'apollo-dashboard' => [
                    'src' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/dashboard.css',
                    'deps' => ['apollo-canvas-mode'],
                    'version' => APOLLO_SOCIAL_VERSION,
                ],
            ],
            'js' => [
                'apollo-dashboard' => [
                    'src' => APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/dashboard-builder.js',
                    'deps' => ['apollo-canvas', 'motion'],
                    'version' => APOLLO_SOCIAL_VERSION,
                ],
            ],
        ],
    ],

    // FASE 1: Documentos Routes (Canvas Mode)
    '/doc/new' => [
        'pattern' => '^doc/new/?$',
        'query_vars' => ['apollo_route' => 'doc_new'],
        'template' => 'documents/editor.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\RawTemplateRenderer',
        'canvas' => true,
        'raw_html' => true,
    ],
    '/doc/{file_id}' => [
        'pattern' => '^doc/([a-zA-Z0-9]+)/?$',
        'query_vars' => ['apollo_route' => 'doc_edit', 'file_id' => '$matches[1]'],
        'template' => 'documents/editor.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\RawTemplateRenderer',
        'canvas' => true,
        'raw_html' => true,
    ],
    '/pla/new' => [
        'pattern' => '^pla/new/?$',
        'query_vars' => ['apollo_route' => 'pla_new'],
        'template' => 'documents/spreadsheet-editor.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\RawTemplateRenderer',
        'canvas' => true,
        'raw_html' => true,
    ],
    '/pla/{file_id}' => [
        'pattern' => '^pla/([a-zA-Z0-9]+)/?$',
        'query_vars' => ['apollo_route' => 'pla_edit', 'file_id' => '$matches[1]'],
        'template' => 'documents/spreadsheet-editor.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\RawTemplateRenderer',
        'canvas' => true,
        'raw_html' => true,
    ],
    '/sign' => [
        'pattern' => '^sign/?$',
        'query_vars' => ['apollo_route' => 'sign_list'],
        'template' => 'documents/sign-list.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\RawTemplateRenderer',
        'canvas' => true,
        'raw_html' => true,
    ],
    '/sign/{token}' => [
        'pattern' => '^sign/([a-zA-Z0-9]+)/?$',
        'query_vars' => ['apollo_route' => 'sign_document', 'signature_token' => '$matches[1]'],
        'template' => 'documents/sign-document.php',
        'handler' => 'Apollo\\Infrastructure\\Rendering\\RawTemplateRenderer',
        'canvas' => true,
        'raw_html' => true,
    ],
];