<?php
// phpcs:ignoreFile
/**
 * Apollo Events Manager - Pre-Release Debugging Script
 *
 * Execute via: php DEBUG-PRE-RELEASE.php
 * OU via WP-CLI: wp eval-file DEBUG-PRE-RELEASE.php
 */

if ( php_sapi_name() === 'cli' && ! defined( 'ABSPATH' ) ) {
	require_once __DIR__ . '/../../../wp-load.php';
}

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Execute via WP-CLI ou linha de comando' );
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "🔍 APOLLO EVENTS MANAGER - PRE-RELEASE DEBUG\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

$checks   = array();
$errors   = array();
$warnings = array();

// ═══════════════════════════════════════════════════════════════
// 1. VERIFICAR SISTEMA DE SANITIZAÇÃO
// ═══════════════════════════════════════════════════════════════
echo "1️⃣  VERIFICANDO SISTEMA DE SANITIZAÇÃO...\n";

if ( class_exists( 'Apollo_Events_Sanitization' ) ) {
	echo "   ✅ Classe Apollo_Events_Sanitization carregada\n";
	$checks[] = '✅ Sanitization system loaded';
} else {
	echo "   ❌ Classe Apollo_Events_Sanitization NÃO encontrada\n";
	$errors[] = 'Sanitization system not loaded';
}

if ( function_exists( 'apollo_get_post_meta' ) ) {
	echo "   ✅ apollo_get_post_meta() disponível\n";
	$checks[] = '✅ apollo_get_post_meta() available';
} else {
	echo "   ❌ apollo_get_post_meta() NÃO encontrada\n";
	$errors[] = 'apollo_get_post_meta() not found';
}

if ( function_exists( 'apollo_update_post_meta' ) ) {
	echo "   ✅ apollo_update_post_meta() disponível\n";
	$checks[] = '✅ apollo_update_post_meta() available';
} else {
	echo "   ❌ apollo_update_post_meta() NÃO encontrada\n";
	$errors[] = 'apollo_update_post_meta() not found';
}

if ( function_exists( 'apollo_delete_post_meta' ) ) {
	echo "   ✅ apollo_delete_post_meta() disponível\n";
	$checks[] = '✅ apollo_delete_post_meta() available';
} else {
	echo "   ❌ apollo_delete_post_meta() NÃO encontrada\n";
	$errors[] = 'apollo_delete_post_meta() not found';
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// 2. VERIFICAR MIGRAÇÃO PARA STRICT MODE
// ═══════════════════════════════════════════════════════════════
echo "2️⃣  VERIFICANDO MIGRAÇÃO PARA STRICT MODE...\n";

$files_to_check = array(
	'apollo-events-manager.php',
	'includes/admin-metaboxes.php',
	'templates/single-event-page.php',
	'templates/single-event-standalone.php',
	'templates/event-card.php',
);

foreach ( $files_to_check as $file ) {
	$full_path = APOLLO_WPEM_PATH . $file;
	if ( file_exists( $full_path ) ) {
		$content   = file_get_contents( $full_path );
		$old_count = preg_match_all( '/(?<!apollo_)get_post_meta\s*\(/', $content );

		if ( $old_count > 0 ) {
			echo "   ⚠️ {$file}: {$old_count} chamadas antigas encontradas\n";
			$warnings[] = "{$file}: {$old_count} old calls";
		} else {
			echo "   ✅ {$file}: totalmente migrado\n";
			$checks[] = "✅ {$file} migrated";
		}
	} else {
		echo "   ❌ {$file}: não encontrado\n";
		$errors[] = "{$file} not found";
	}
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// 3. VERIFICAR TEMPLATES E ASSETS
// ═══════════════════════════════════════════════════════════════
echo "3️⃣  VERIFICANDO TEMPLATES E ASSETS...\n";

$critical_templates = array(
	'templates/event-card.php'              => 'Event card template',
	'templates/single-event-page.php'       => 'Single event (modal)',
	'templates/single-event-standalone.php' => 'Single event (standalone)',
	'templates/portal-discover.php'         => 'Events portal',
	'templates/page-cenario-new-event.php'  => 'New event form',
	'templates/page-mod-events.php'         => 'Moderation page',
);

foreach ( $critical_templates as $file => $desc ) {
	$full_path = APOLLO_WPEM_PATH . $file;
	if ( file_exists( $full_path ) ) {
		echo "   ✅ {$desc}\n";
		$checks[] = "✅ {$desc}";
	} else {
		echo "   ❌ {$desc} FALTANDO\n";
		$errors[] = "{$desc} missing";
	}
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// 4. VERIFICAR LEAFLET (MAPA OSM)
// ═══════════════════════════════════════════════════════════════
echo "4️⃣  VERIFICANDO LEAFLET (MAPA OSM)...\n";

$templates_with_maps = array(
	'templates/single-event-page.php',
	'templates/single-event-standalone.php',
);

foreach ( $templates_with_maps as $file ) {
	$full_path = APOLLO_WPEM_PATH . $file;
	if ( file_exists( $full_path ) ) {
		$content = file_get_contents( $full_path );

		// Verificar se tem inicialização do mapa
		if ( strpos( $content, 'L.map(' ) !== false ) {
			echo '   ✅ ' . basename( $file ) . ": inicialização do mapa encontrada\n";
			$checks[] = '✅ Map init in ' . basename( $file );
		} else {
			echo '   ❌ ' . basename( $file ) . ": SEM inicialização do mapa\n";
			$errors[] = 'No map init in ' . basename( $file );
		}

		// Verificar estratégias múltiplas
		if ( strpos( $content, 'apollo:modal:content:loaded' ) !== false ) {
			echo '   ✅ ' . basename( $file ) . ": event listeners configurados\n";
			$checks[] = '✅ Event listeners in ' . basename( $file );
		} else {
			echo '   ⚠️ ' . basename( $file ) . ": sem event listeners\n";
			$warnings[] = 'No event listeners in ' . basename( $file );
		}
	}//end if
}//end foreach

echo "\n";

// ═══════════════════════════════════════════════════════════════
// 5. VERIFICAR SHORTCODES
// ═══════════════════════════════════════════════════════════════
echo "5️⃣  VERIFICANDO SHORTCODES REGISTRADOS...\n";

global $shortcode_tags;
$apollo_shortcodes = array(
	'events',
	'apollo_event',
	'apollo_event_user_overview',
	'event',
	'event_djs',
	'event_locals',
	'event_summary',
	'local_dashboard',
	'past_events',
	'single_event_dj',
	'single_event_local',
);

foreach ( $apollo_shortcodes as $sc ) {
	if ( isset( $shortcode_tags[ $sc ] ) ) {
		echo "   ✅ [{$sc}] registrado\n";
		$checks[] = "✅ [{$sc}] registered";
	} else {
		echo "   ❌ [{$sc}] NÃO registrado\n";
		$errors[] = "[{$sc}] not registered";
	}
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// 6. VERIFICAR POST TYPES
// ═══════════════════════════════════════════════════════════════
echo "6️⃣  VERIFICANDO POST TYPES...\n";

$required_post_types = array(
	'event_listing' => 'Eventos',
	'event_dj'      => 'DJs',
	'event_local'   => 'Locais',
);

foreach ( $required_post_types as $pt => $label ) {
	if ( post_type_exists( $pt ) ) {
		echo "   ✅ {$label} ({$pt})\n";
		$checks[] = "✅ Post type {$pt}";
	} else {
		echo "   ❌ {$label} ({$pt}) NÃO registrado\n";
		$errors[] = "Post type {$pt} not registered";
	}
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// 7. VERIFICAR TAXONOMIAS
// ═══════════════════════════════════════════════════════════════
echo "7️⃣  VERIFICANDO TAXONOMIAS...\n";

$required_taxonomies = array(
	'event_listing_category' => 'Categorias',
	'event_listing_type'     => 'Tipos',
	'event_sounds'           => 'Gêneros Musicais',
);

foreach ( $required_taxonomies as $tax => $label ) {
	if ( taxonomy_exists( $tax ) ) {
		echo "   ✅ {$label} ({$tax})\n";
		$checks[] = "✅ Taxonomy {$tax}";
	} else {
		echo "   ❌ {$label} ({$tax}) NÃO registrada\n";
		$errors[] = "Taxonomy {$tax} not registered";
	}
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// 8. VERIFICAR PÁGINAS PRINCIPAIS
// ═══════════════════════════════════════════════════════════════
echo "8️⃣  VERIFICANDO PÁGINAS PRINCIPAIS...\n";

$eventos_page = get_page_by_path( 'eventos' );
if ( $eventos_page ) {
	if ( $eventos_page->post_status === 'publish' ) {
		echo "   ✅ Página 'Eventos' (/eventos/) publicada\n";
		$checks[] = '✅ /eventos/ published';
	} else {
		echo "   ⚠️ Página 'Eventos' existe mas status: {$eventos_page->post_status}\n";
		$warnings[] = "/eventos/ exists but status: {$eventos_page->post_status}";
	}
} else {
	echo "   ⚠️ Página 'Eventos' não existe (criar via Eventos > Shortcodes)\n";
	$warnings[] = '/eventos/ not created yet';
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// 9. VERIFICAR DADOS DE TESTE
// ═══════════════════════════════════════════════════════════════
echo "9️⃣  VERIFICANDO DADOS DE TESTE...\n";

$events_count = wp_count_posts( 'event_listing' );
$djs_count    = wp_count_posts( 'event_dj' );
$locals_count = wp_count_posts( 'event_local' );

echo "   📊 Eventos: {$events_count->publish} publicados, {$events_count->draft} drafts\n";
echo "   📊 DJs: {$djs_count->publish} publicados\n";
echo "   📊 Locais: {$locals_count->publish} publicados\n";

if ( $events_count->publish > 0 ) {
	$checks[] = "✅ {$events_count->publish} events published";
} else {
	$warnings[] = 'No events published yet';
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// 10. VERIFICAR COORDENADAS DE LOCAIS
// ═══════════════════════════════════════════════════════════════
echo "🔟 VERIFICANDO COORDENADAS DE LOCAIS...\n";

$locals = get_posts(
	array(
		'post_type'      => 'event_local',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
	)
);

$locals_with_coords    = 0;
$locals_without_coords = 0;

foreach ( $locals as $local ) {
	$lat = apollo_get_post_meta( $local->ID, '_local_latitude', true );
	$lng = apollo_get_post_meta( $local->ID, '_local_longitude', true );

	if ( ! empty( $lat ) && ! empty( $lng ) && $lat != 0 && $lng != 0 ) {
		++$locals_with_coords;
		echo '   ✅ ' . get_the_title( $local->ID ) . ": ({$lat}, {$lng})\n";
	} else {
		++$locals_without_coords;
		echo '   ⚠️ ' . get_the_title( $local->ID ) . ": SEM coordenadas\n";
	}
}

echo "\n   📊 {$locals_with_coords} locais com coordenadas, {$locals_without_coords} sem coordenadas\n";

if ( $locals_without_coords > 0 ) {
	$warnings[] = "{$locals_without_coords} locals without coordinates";
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// 11. VERIFICAR ASSETS (uni.css, Leaflet, RemixIcon)
// ═══════════════════════════════════════════════════════════════
echo "1️⃣1️⃣  VERIFICANDO ASSETS...\n";

// Verificar se uni.css está sendo carregado
$main_file_content = file_get_contents( APOLLO_WPEM_PATH . 'apollo-events-manager.php' );

if ( strpos( $main_file_content, 'assets.apollo.rio.br/uni.css' ) !== false ) {
	echo "   ✅ uni.css remoto configurado\n";
	$checks[] = '✅ uni.css remote configured';
} else {
	echo "   ❌ uni.css NÃO configurado\n";
	$errors[] = 'uni.css not configured';
}

if ( strpos( $main_file_content, 'leaflet' ) !== false || strpos( $main_file_content, 'Leaflet' ) !== false ) {
	echo "   ✅ Leaflet.js configurado\n";
	$checks[] = '✅ Leaflet.js configured';
} else {
	echo "   ❌ Leaflet.js NÃO configurado\n";
	$errors[] = 'Leaflet.js not configured';
}

if ( strpos( $main_file_content, 'remixicon' ) !== false ) {
	echo "   ✅ RemixIcon configurado\n";
	$checks[] = '✅ RemixIcon configured';
} else {
	echo "   ❌ RemixIcon NÃO configurado\n";
	$errors[] = 'RemixIcon not configured';
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// 12. VERIFICAR HANDLERS AJAX
// ═══════════════════════════════════════════════════════════════
echo "1️⃣2️⃣  VERIFICANDO AJAX HANDLERS...\n";

$ajax_actions = array(
	'apollo_get_event_modal',
	'filter_events',
	'apollo_mod_approve_event',
	'apollo_mod_reject_event',
	'apollo_create_canvas_page',
);

foreach ( $ajax_actions as $action ) {
	if ( has_action( "wp_ajax_{$action}" ) || has_action( "wp_ajax_nopriv_{$action}" ) ) {
		echo "   ✅ {$action}\n";
		$checks[] = "✅ AJAX {$action}";
	} else {
		echo "   ⚠️ {$action} não registrado\n";
		$warnings[] = "AJAX {$action} not registered";
	}
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// 13. VERIFICAR META KEYS CRÍTICAS
// ═══════════════════════════════════════════════════════════════
echo "1️⃣3️⃣  VERIFICANDO META KEYS CRÍTICAS...\n";

$events = get_posts(
	array(
		'post_type'      => 'event_listing',
		'posts_per_page' => 5,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);

foreach ( $events as $event ) {
	$title      = get_the_title( $event->ID );
	$start_date = apollo_get_post_meta( $event->ID, '_event_start_date', true );
	$banner     = apollo_get_post_meta( $event->ID, '_event_banner', true );
	$djs        = apollo_get_post_meta( $event->ID, '_event_dj_ids', true );
	$local      = apollo_get_post_meta( $event->ID, '_event_local_ids', true );

	echo "   📅 {$title}:\n";
	echo '      → Data: ' . ( $start_date ? "✅ {$start_date}" : '❌ SEM DATA' ) . "\n";
	echo '      → Banner: ' . ( $banner ? '✅' : '⚠️ sem banner' ) . "\n";
	echo '      → DJs: ' . ( is_array( $djs ) && ! empty( $djs ) ? '✅ ' . count( $djs ) . ' DJs' : '⚠️ sem DJs' ) . "\n";
	echo '      → Local: ' . ( $local ? '✅' : '⚠️ sem local' ) . "\n";

	if ( ! $start_date ) {
		$errors[] = "Event '{$title}' without start_date";
	}
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// RESUMO FINAL
// ═══════════════════════════════════════════════════════════════
echo "════════════════════════════════════════════════════════════════\n";
echo "📊 RESUMO FINAL\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

echo '✅ CHECKS PASSED: ' . count( $checks ) . "\n";
if ( ! empty( $errors ) ) {
	echo '❌ ERRORS: ' . count( $errors ) . "\n";
	foreach ( $errors as $error ) {
		echo "   • {$error}\n";
	}
}
if ( ! empty( $warnings ) ) {
	echo '⚠️ WARNINGS: ' . count( $warnings ) . "\n";
	foreach ( $warnings as $warning ) {
		echo "   • {$warning}\n";
	}
}

echo "\n";

if ( empty( $errors ) ) {
	echo "✅✅✅ PRONTO PARA RELEASE! ✅✅✅\n";
	echo "\n";
	echo "Próximos passos:\n";
	echo "1. Desativar WP_DEBUG em wp-config.php\n";
	echo "2. Limpar transients: wp transient delete --all\n";
	echo "3. Flush rewrite rules: wp rewrite flush\n";
	echo "4. Testar em navegador privado\n";
	echo "\n";
} else {
	echo "❌❌❌ CORREÇÕES NECESSÁRIAS ANTES DO RELEASE ❌❌❌\n";
	echo "\n";
	echo "Por favor, corrija os erros listados acima.\n";
	echo "\n";
}

echo "════════════════════════════════════════════════════════════════\n";
echo "\n";
