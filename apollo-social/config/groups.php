<?php
/**
 * Configuração de Políticas dos Grupos
 */

return [
    'comunidade' => [
        'label' => 'Comunidade',
        'visibility' => 'public',
        'join' => 'open',
        'invite' => 'any_member',
        'chat' => [
            'visibility' => 'members_only'
        ],
        'posting' => [
            'roles' => ['member', 'moderator', 'admin'],
            'scopes' => ['post', 'discussion']
        ]
    ],
    
    'nucleo' => [
        'label' => 'Núcleo',
        'visibility' => 'private',
        'join' => 'invite_only',
        'invite' => 'insiders_only',
        'chat' => [
            'visibility' => 'members_only'
        ],
        'posting' => [
            'roles' => ['member', 'moderator', 'admin'],
            'scopes' => ['post', 'discussion']
        ]
    ],
    
    'season' => [
        'label' => 'Season',
        'visibility' => 'public',
        'join' => 'request',
        'invite' => 'moderators',
        'chat' => [
            'visibility' => 'members_only'
        ],
        'posting' => [
            'roles' => ['member', 'moderator', 'admin'],
            'scopes' => ['post', 'classified']
        ]
    ]
];