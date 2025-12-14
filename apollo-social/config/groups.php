<?php

/**
 * Configuração de Políticas dos Grupos
 */

return array(
	'comunidade' => array(
		'label'      => 'Comunidade',
		'visibility' => 'public',
		'join'       => 'open',
		'invite'     => 'any_member',
		'chat'       => array(
			'visibility' => 'members_only',
		),
		'posting'    => array(
			'roles'  => array( 'member', 'moderator', 'admin' ),
			'scopes' => array( 'post', 'discussion' ),
		),
	),

	'nucleo'     => array(
		'label'      => 'Núcleo',
		'visibility' => 'private',
		'join'       => 'invite_only',
		'invite'     => 'insiders_only',
		'chat'       => array(
			'visibility' => 'members_only',
		),
		'posting'    => array(
			'roles'  => array( 'member', 'moderator', 'admin' ),
			'scopes' => array( 'post', 'discussion' ),
		),
	),

	'season'     => array(
		'label'      => 'Season',
		'visibility' => 'public',
		'join'       => 'request',
		'invite'     => 'moderators',
		'chat'       => array(
			'visibility' => 'members_only',
		),
		'posting'    => array(
			'roles'  => array( 'member', 'moderator', 'admin' ),
			'scopes' => array( 'post', 'classified' ),
		),
	),
);
