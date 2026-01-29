<?php
/**
 * Apollo Events Manager - Unify User Roles
 *
 * This script ensures all user roles have the necessary capabilities
 * for the Apollo Events Manager system.
 *
 * @package Apollo_Events_Manager
 */

// Prevent direct access without WP.
if ( ! defined( 'ABSPATH' ) ) {
	require_once '../../../wp-load.php';
}

$current_user = wp_get_current_user();
echo '<h1>🔐 Apollo Events Manager - Unificação de Roles de Usuário</h1>';
echo '<p><strong>Executado por:</strong> ' . esc_html( $current_user->display_name ) . ' (' . esc_html( $current_user->user_email ) . ')</p>';
echo '<p><strong>Data:</strong> ' . esc_html( date( 'd/m/Y H:i:s' ) ) . '</p>';
echo '<hr>';

// Define all capabilities needed.
$event_listing_capabilities = array(
	// Core event capabilities.
	'edit_event_listing',
	'read_event_listing',
	'delete_event_listing',
	'edit_event_listings',
	'edit_others_event_listings',
	'publish_event_listings',
	'read_private_event_listings',
	'delete_event_listings',
	'delete_private_event_listings',
	'delete_published_event_listings',
	'delete_others_event_listings',
	'edit_private_event_listings',
	'edit_published_event_listings',
);

$dj_capabilities = array(
	'edit_event_dj',
	'read_event_dj',
	'delete_event_dj',
	'edit_event_djs',
	'edit_others_event_djs',
	'publish_event_djs',
	'read_private_event_djs',
	'delete_event_djs',
	'delete_private_event_djs',
	'delete_published_event_djs',
	'delete_others_event_djs',
	'edit_private_event_djs',
	'edit_published_event_djs',
);

$local_capabilities = array(
	'edit_event_local',
	'read_event_local',
	'delete_event_local',
	'edit_event_locals',
	'edit_others_event_locals',
	'publish_event_locals',
	'read_private_event_locals',
	'delete_event_locals',
	'delete_private_event_locals',
	'delete_published_event_locals',
	'delete_others_event_locals',
	'edit_private_event_locals',
	'edit_published_event_locals',
);

$taxonomy_capabilities = array(
	'manage_categories',
	'edit_event_listing_category',
	'edit_event_listing_type',
	'edit_event_listing_tag',
	'edit_event_sounds',
);

$general_capabilities = array(
	'upload_files',
	'view_apollo_event_stats',
	'manage_apollo_events',
);

// Define roles to update.
$roles_to_update = array(
	'administrator' => 'Administrador',
	'editor'        => 'Editor',
	'author'        => 'Autor',
	'contributor'   => 'Contribuidor',
	'subscriber'    => 'Assinante',
);

// Custom Apollo roles.
$apollo_roles = array(
	'apollo'           => 'Apollo',
	'apollo_moderator' => 'Apollo Moderator',
	'cena_role'        => 'CENA-RIO User',
	'cena_moderator'   => 'CENA-RIO Moderator',
);

echo '<h2>📋 Capacidades a serem verificadas/adicionadas:</h2>';
echo '<ul>';
echo '<li><strong>Eventos:</strong> ' . esc_html( count( $event_listing_capabilities ) ) . ' capabilities</li>';
echo '<li><strong>DJs:</strong> ' . esc_html( count( $dj_capabilities ) ) . ' capabilities</li>';
echo '<li><strong>Locais:</strong> ' . esc_html( count( $local_capabilities ) ) . ' capabilities</li>';
echo '<li><strong>Taxonomias:</strong> ' . esc_html( count( $taxonomy_capabilities ) ) . ' capabilities</li>';
echo '<li><strong>Gerais:</strong> ' . esc_html( count( $general_capabilities ) ) . ' capabilities</li>';
echo '</ul>';

echo '<h2>🔄 Atualizando Roles Padrão do WordPress:</h2>';

// Update standard WordPress roles.
foreach ( $roles_to_update as $role_slug => $role_name ) {
	$role = get_role( $role_slug );

	if ( ! $role ) {
		echo "<div style='color: orange; margin: 10px 0; padding: 10px; border: 1px solid orange; border-radius: 5px;'>";
		echo "⚠️ Role '" . esc_html( $role_name ) . "' (" . esc_html( $role_slug ) . ') não encontrada - pulando...';
		echo '</div>';
		continue;
	}

	echo '<h3>👤 ' . esc_html( $role_name ) . ' (' . esc_html( $role_slug ) . ')</h3>';
	echo "<table style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
	echo "<thead><tr style='background: #f5f5f5;'><th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Capability</th><th style='border: 1px solid #ddd; padding: 8px; text-align: center;'>Antes</th><th style='border: 1px solid #ddd; padding: 8px; text-align: center;'>Depois</th><th style='border: 1px solid #ddd; padding: 8px; text-align: center;'>Ação</th></tr></thead>";
	echo '<tbody>';

	$added_count       = 0;
	$already_had_count = 0;

	// Determine which capabilities this role should have based on hierarchy.
	$capabilities_to_add = array();

	switch ( $role_slug ) {
		case 'administrator':
			$capabilities_to_add = array_merge(
				$event_listing_capabilities,
				$dj_capabilities,
				$local_capabilities,
				$taxonomy_capabilities,
				$general_capabilities
			);
			break;

		case 'editor':
			$capabilities_to_add = array_merge(
				$event_listing_capabilities,
				$dj_capabilities,
				$local_capabilities,
				$taxonomy_capabilities,
				array( 'upload_files', 'view_apollo_event_stats' )
			);
			break;

		case 'author':
			$capabilities_to_add = array_merge(
				$event_listing_capabilities,
				$dj_capabilities,
				array( 'upload_files' )
			);
			break;

		case 'contributor':
			$capabilities_to_add = array_merge(
				$event_listing_capabilities,
				$dj_capabilities
			);
			break;

		case 'subscriber':
			$capabilities_to_add = array(
				'read_event_listing',
				'read_event_dj',
				'read_event_local',
			);
			break;
	}

	foreach ( $capabilities_to_add as $cap ) {
		$had_before = $role->has_cap( $cap );

		if ( ! $had_before ) {
			$role->add_cap( $cap );
			$has_after = $role->has_cap( $cap );
			$action    = $has_after ? '✅ Adicionada' : '❌ Falhou';
			++$added_count;
		} else {
			$has_after = true;
			$action    = 'ℹ️ Já tinha';
			++$already_had_count;
		}

		$before_status = $had_before ? '✅' : '❌';
		$after_status  = $has_after ? '✅' : '❌';

		echo '<tr>';
		echo "<td style='border: 1px solid #ddd; padding: 8px; font-family: monospace; font-size: 12px;'>" . esc_html( $cap ) . '</td>';
		echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center;'>" . esc_html( $before_status ) . '</td>';
		echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center;'>" . esc_html( $after_status ) . '</td>';
		echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center;'>" . esc_html( $action ) . '</td>';
		echo '</tr>';
	}

	echo '</tbody></table>';
	echo '<p><strong>Resumo:</strong> ' . esc_html( $added_count ) . ' adicionadas, ' . esc_html( $already_had_count ) . ' já existiam</p>';
}

echo '<h2>🚀 Atualizando Roles Customizadas Apollo:</h2>';

// Update Apollo custom roles.
foreach ( $apollo_roles as $role_slug => $role_name ) {
	$role = get_role( $role_slug );

	if ( ! $role ) {
		echo "<div style='color: orange; margin: 10px 0; padding: 10px; border: 1px solid orange; border-radius: 5px;'>";
		echo "⚠️ Role '" . esc_html( $role_name ) . "' (" . esc_html( $role_slug ) . ') não encontrada - pulando...';
		echo '<br><small>Esta role pode ser criada automaticamente pelo sistema quando necessário.</small>';
		echo '</div>';
		continue;
	}

	echo '<h3>👤 ' . esc_html( $role_name ) . ' (' . esc_html( $role_slug ) . ')</h3>';

	$capabilities_to_add = array();

	switch ( $role_slug ) {
		case 'apollo':
		case 'apollo_moderator':
			$capabilities_to_add = array_merge(
				$event_listing_capabilities,
				$dj_capabilities,
				$local_capabilities,
				$taxonomy_capabilities,
				$general_capabilities
			);
			break;

		case 'cena_role':
			$capabilities_to_add = array_merge(
				$event_listing_capabilities,
				$dj_capabilities,
				$local_capabilities,
				array( 'upload_files', 'publish_event_listings', 'publish_event_djs' )
			);
			break;

		case 'cena_moderator':
			$capabilities_to_add = array_merge(
				$event_listing_capabilities,
				$dj_capabilities,
				$local_capabilities,
				$taxonomy_capabilities,
				$general_capabilities
			);
			break;
	}

	$added_count       = 0;
	$already_had_count = 0;

	foreach ( $capabilities_to_add as $cap ) {
		$had_before = $role->has_cap( $cap );

		if ( ! $had_before ) {
			$role->add_cap( $cap );
			++$added_count;
		} else {
			++$already_had_count;
		}
	}

	echo '<p><strong>Resumo:</strong> ' . esc_html( $added_count ) . ' capabilities adicionadas, ' . esc_html( $already_had_count ) . ' já existiam</p>';
}

echo '<hr>';
echo '<h2>✅ Verificação Final:</h2>';

// Final verification.
echo '<h3>Testando capabilities críticas:</h3>';
$critical_caps = array(
	'edit_event_listings',
	'edit_event_djs',
	'edit_event_locals',
	'manage_categories',
	'upload_files',
);

echo "<table style='border-collapse: collapse; width: 100%;'>";
echo "<thead><tr style='background: #f5f5f5;'><th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Capability</th><th style='border: 1px solid #ddd; padding: 8px; text-align: center;'>Administrator</th><th style='border: 1px solid #ddd; padding: 8px; text-align: center;'>Editor</th></tr></thead>";
echo '<tbody>';

foreach ( $critical_caps as $cap ) {
	$admin_role  = get_role( 'administrator' );
	$editor_role = get_role( 'editor' );

	$admin_has  = $admin_role ? ( $admin_role->has_cap( $cap ) ? '✅' : '❌' ) : 'N/A';
	$editor_has = $editor_role ? ( $editor_role->has_cap( $cap ) ? '✅' : '❌' ) : 'N/A';

	echo '<tr>';
	echo "<td style='border: 1px solid #ddd; padding: 8px; font-family: monospace; font-size: 12px;'>" . esc_html( $cap ) . '</td>';
	echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center; " . ( '✅' === $admin_has ? '' : 'color: red; font-weight: bold;' ) . "'>" . esc_html( $admin_has ) . '</td>';
	echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center; " . ( '✅' === $editor_has ? '' : 'color: red; font-weight: bold;' ) . "'>" . esc_html( $editor_has ) . '</td>';
	echo '</tr>';
}

echo '</tbody></table>';

echo '<hr>';
echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3 style='margin-top: 0;'>🎉 Unificação de Roles Concluída!</h3>";
echo '<p>Todas as roles de usuário foram atualizadas com as capabilities necessárias do Apollo Events Manager.</p>';
echo '<p><strong>Próximos passos:</strong></p>';
echo '<ul>';
echo '<li>✅ Acesse o menu <strong>Eventos</strong> no admin para ver a nova organização</li>';
echo '<li>✅ Teste criar novos eventos, DJs e locais</li>';
echo '<li>✅ Verifique se todos os usuários conseguem acessar suas funcionalidades</li>';
echo '</ul>';
echo '</div>';

echo "<p><a href='" . esc_url( admin_url() ) . "' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>← Voltar ao Admin</a></p>";
