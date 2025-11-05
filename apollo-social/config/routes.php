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
];