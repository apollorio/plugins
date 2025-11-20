<?php
/**
 * Apollo Events Manager - File Check (sem necessidade de DB)
 * Verifica arquivos críticos sem conectar ao WordPress
 */

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "🔍 APOLLO EVENTS MANAGER - FILE CHECK (PRE-RELEASE)\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

$base_dir = __DIR__;
$checks = 0;
$errors = 0;
$warnings = 0;

// ═══════════════════════════════════════════════════════════════
// 1. VERIFICAR ARQUIVOS PRINCIPAIS
// ═══════════════════════════════════════════════════════════════
echo "1️⃣  VERIFICANDO ARQUIVOS PRINCIPAIS...\n";

$critical_files = [
    'apollo-events-manager.php' => 'Plugin principal',
    'includes/sanitization.php' => 'Sistema de sanitização',
    'includes/meta-helpers.php' => 'Meta helpers',
    'includes/admin-shortcodes-page.php' => 'Página de shortcodes',
    'includes/admin-metaboxes.php' => 'Admin metaboxes',
    'includes/event-helpers.php' => 'Event helpers',
    'includes/ajax-handlers.php' => 'AJAX handlers',
    'includes/cache.php' => 'Sistema de cache',
];

foreach ($critical_files as $file => $desc) {
    $full_path = $base_dir . '/' . $file;
    if (file_exists($full_path)) {
        echo "   ✅ {$desc}\n";
        $checks++;
    } else {
        echo "   ❌ {$desc} FALTANDO\n";
        $errors++;
    }
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// 2. VERIFICAR TEMPLATES
// ═══════════════════════════════════════════════════════════════
echo "2️⃣  VERIFICANDO TEMPLATES...\n";

$templates = [
    'templates/event-card.php' => 'Event card',
    'templates/single-event-page.php' => 'Single event (modal)',
    'templates/single-event-standalone.php' => 'Single event (standalone)',
    'templates/portal-discover.php' => 'Events portal',
    'templates/page-cenario-new-event.php' => 'New event form',
    'templates/page-mod-events.php' => 'Moderation page',
    'templates/single-event_dj.php' => 'Single DJ',
    'templates/single-event_local.php' => 'Single local',
];

foreach ($templates as $file => $desc) {
    $full_path = $base_dir . '/' . $file;
    if (file_exists($full_path)) {
        echo "   ✅ {$desc}\n";
        $checks++;
    } else {
        echo "   ❌ {$desc} FALTANDO\n";
        $errors++;
    }
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// 3. VERIFICAR ASSETS
// ═══════════════════════════════════════════════════════════════
echo "3️⃣  VERIFICANDO ASSETS...\n";

$assets = [
    'assets/js/apollo-events-portal.js' => 'Portal JS',
    'assets/js/event-modal.js' => 'Modal JS',
    'assets/js/event-filters.js' => 'Filters JS',
    'assets/js/apollo-favorites.js' => 'Favorites JS',
    'assets/css/event-modal.css' => 'Modal CSS',
    'assets/css/apollo-shadcn-components.css' => 'ShadCN components',
];

foreach ($assets as $file => $desc) {
    $full_path = $base_dir . '/' . $file;
    if (file_exists($full_path)) {
        echo "   ✅ {$desc}\n";
        $checks++;
    } else {
        echo "   ⚠️ {$desc} faltando\n";
        $warnings++;
    }
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// 4. VERIFICAR MIGRAÇÃO PARA STRICT MODE
// ═══════════════════════════════════════════════════════════════
echo "4️⃣  VERIFICANDO MIGRAÇÃO PARA STRICT MODE...\n";

$files_to_check = [
    'apollo-events-manager.php',
    'includes/admin-metaboxes.php',
    'templates/single-event-page.php',
    'templates/single-event-standalone.php',
    'templates/event-card.php',
    'templates/portal-discover.php',
];

foreach ($files_to_check as $file) {
    $full_path = $base_dir . '/' . $file;
    if (file_exists($full_path)) {
        $content = file_get_contents($full_path);
        
        // Contar chamadas antigas (negative lookbehind)
        preg_match_all('/(?<!apollo_)get_post_meta\s*\(/', $content, $matches);
        $old_count = count($matches[0]);
        
        if ($old_count > 0) {
            echo "   ⚠️ {$file}: {$old_count} chamadas antigas\n";
            $warnings++;
        } else {
            echo "   ✅ {$file}: totalmente migrado\n";
            $checks++;
        }
    }
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// 5. VERIFICAR CARREGAMENTO DE ASSETS EXTERNOS
// ═══════════════════════════════════════════════════════════════
echo "5️⃣  VERIFICANDO ASSETS EXTERNOS...\n";

$main_file = file_get_contents($base_dir . '/apollo-events-manager.php');

$external_assets = [
    'assets.apollo.rio.br/uni.css' => 'uni.css remoto',
    'unpkg.com/leaflet' => 'Leaflet.js (OSM)',
    'remixicon' => 'RemixIcon',
];

foreach ($external_assets as $url => $desc) {
    if (strpos($main_file, $url) !== false) {
        echo "   ✅ {$desc}\n";
        $checks++;
    } else {
        echo "   ❌ {$desc} NÃO configurado\n";
        $errors++;
    }
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// 6. VERIFICAR INICIALIZAÇÃO DE MAPA
// ═══════════════════════════════════════════════════════════════
echo "6️⃣  VERIFICANDO INICIALIZAÇÃO DE MAPA...\n";

$templates_with_maps = [
    'templates/single-event-page.php' => 'Single event (modal)',
    'templates/single-event-standalone.php' => 'Single event (standalone)',
];

foreach ($templates_with_maps as $file => $desc) {
    $full_path = $base_dir . '/' . $file;
    if (file_exists($full_path)) {
        $content = file_get_contents($full_path);
        
        $has_leaflet = strpos($content, 'L.map(') !== false;
        $has_events = strpos($content, 'apollo:modal:content:loaded') !== false;
        $has_invalidate = strpos($content, 'invalidateSize') !== false;
        
        if ($has_leaflet && $has_events && $has_invalidate) {
            echo "   ✅ {$desc}: completo\n";
            $checks++;
        } elseif ($has_leaflet) {
            echo "   ⚠️ {$desc}: mapa OK, mas falta event listeners\n";
            $warnings++;
        } else {
            echo "   ❌ {$desc}: SEM inicialização de mapa\n";
            $errors++;
        }
    }
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// 7. VERIFICAR MODAL HANDLER
// ═══════════════════════════════════════════════════════════════
echo "7️⃣  VERIFICANDO MODAL HANDLER...\n";

$modal_js = $base_dir . '/assets/js/event-modal.js';
if (file_exists($modal_js)) {
    $content = file_get_contents($modal_js);
    
    if (strpos($content, 'apollo:modal:content:loaded') !== false) {
        echo "   ✅ Event dispatch configurado\n";
        $checks++;
    } else {
        echo "   ❌ Event dispatch FALTANDO\n";
        $errors++;
    }
    
    if (strpos($content, 'L.map(') !== false) {
        echo "   ✅ Inicialização direta do mapa configurada\n";
        $checks++;
    } else {
        echo "   ⚠️ Sem inicialização direta (confia apenas nos templates)\n";
        $warnings++;
    }
} else {
    echo "   ❌ event-modal.js FALTANDO\n";
    $errors++;
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// RESUMO FINAL
// ═══════════════════════════════════════════════════════════════
echo "════════════════════════════════════════════════════════════════\n";
echo "📊 RESUMO FINAL\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

echo "✅ Checks Passed: {$checks}\n";
echo "⚠️ Warnings: {$warnings}\n";
echo "❌ Errors: {$errors}\n";

echo "\n";

if ($errors === 0) {
    echo "✅✅✅ ARQUIVOS OK PARA RELEASE! ✅✅✅\n";
    echo "\n";
    if ($warnings > 0) {
        echo "⚠️ Avisos encontrados (não críticos)\n";
    }
} else {
    echo "❌❌❌ CORREÇÕES NECESSÁRIAS ANTES DO RELEASE ❌❌❌\n";
    echo "\n";
    echo "Por favor, corrija os erros listados acima.\n";
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

