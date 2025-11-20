<?php
/**
 * Apollo Ecosystem Unification & Testing Script
 * 
 * This script verifies and unifies all Apollo plugins:
 * - apollo-events-manager
 * - apollo-social
 * - apollo-rio
 * 
 * Run via WP-CLI: wp eval-file APOLLO-ECOSYSTEM-UNIFICATION.php
 */

if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "🔍 APOLLO ECOSYSTEM - VERIFICAÇÃO E UNIFICAÇÃO COMPLETA\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

$errors = [];
$warnings = [];
$success = [];

// ============================================================================
// 1. VERIFICAR PLUGINS ATIVOS
// ============================================================================
echo "📦 1. VERIFICANDO PLUGINS ATIVOS...\n";
echo "────────────────────────────────────────────────────────────────\n";

$required_plugins = [
    'apollo-events-manager/apollo-events-manager.php' => 'Apollo Events Manager',
    'apollo-social/apollo-social.php' => 'Apollo Social',
    'apollo-rio/apollo-rio.php' => 'Apollo Rio',
];

$active_plugins = [];
foreach ($required_plugins as $plugin_file => $plugin_name) {
    if (is_plugin_active($plugin_file)) {
        $active_plugins[] = $plugin_name;
        $success[] = "✅ Plugin ativo: {$plugin_name}";
        echo "   ✅ {$plugin_name}\n";
    } else {
        $errors[] = "❌ Plugin inativo: {$plugin_name}";
        echo "   ❌ {$plugin_name} (INATIVO)\n";
    }
}

echo "\n";

// ============================================================================
// 2. VERIFICAR CONSTANTES E FUNÇÕES GLOBAIS
// ============================================================================
echo "🔧 2. VERIFICANDO CONSTANTES E FUNÇÕES...\n";
echo "────────────────────────────────────────────────────────────────\n";

$required_constants = [
    'APOLLO_WPEM_PATH' => 'Apollo Events Manager Path',
    'APOLLO_WPEM_URL' => 'Apollo Events Manager URL',
    'APOLLO_SOCIAL_PLUGIN_DIR' => 'Apollo Social Plugin Dir',
    'APOLLO_SOCIAL_PLUGIN_URL' => 'Apollo Social Plugin URL',
    'APOLLO_PATH' => 'Apollo Rio Path',
    'APOLLO_URL' => 'Apollo Rio URL',
];

foreach ($required_constants as $constant => $description) {
    if (defined($constant)) {
        $success[] = "✅ Constante definida: {$constant}";
        echo "   ✅ {$constant}\n";
    } else {
        $warnings[] = "⚠️ Constante não definida: {$constant}";
        echo "   ⚠️ {$constant} (não definida)\n";
    }
}

$required_functions = [
    'apollo_shadcn_init' => 'ShadCN Loader',
    'apollo_get_header_for_template' => 'Apollo Header Helper',
    'apollo_get_footer_for_template' => 'Apollo Footer Helper',
    'apollo_is_pwa' => 'PWA Detection',
    'apollo_aem_parse_ids' => 'Parse IDs Helper',
    'apollo_sanitize_timetable' => 'Sanitize Timetable',
];

echo "\n";
echo "   Funções globais:\n";
foreach ($required_functions as $function => $description) {
    if (function_exists($function)) {
        $success[] = "✅ Função disponível: {$function}";
        echo "   ✅ {$function}\n";
    } else {
        $warnings[] = "⚠️ Função não disponível: {$function}";
        echo "   ⚠️ {$function} (não disponível)\n";
    }
}

echo "\n";

// ============================================================================
// 3. VERIFICAR UNI.CSS CARREGAMENTO
// ============================================================================
echo "🎨 3. VERIFICANDO UNI.CSS...\n";
echo "────────────────────────────────────────────────────────────────\n";

$uni_css_url = 'https://assets.apollo.rio.br/uni.css';
$ch = curl_init($uni_css_url);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $success[] = "✅ uni.css acessível em: {$uni_css_url}";
    echo "   ✅ uni.css acessível (HTTP {$http_code})\n";
} else {
    $errors[] = "❌ uni.css não acessível (HTTP {$http_code})";
    echo "   ❌ uni.css não acessível (HTTP {$http_code})\n";
}

echo "\n";

// ============================================================================
// 4. VERIFICAR BANCO DE DADOS
// ============================================================================
echo "💾 4. VERIFICANDO BANCO DE DADOS...\n";
echo "────────────────────────────────────────────────────────────────\n";

global $wpdb;

// Verificar tabelas customizadas
$custom_tables = [
    $wpdb->prefix . 'apollo_verifications' => 'Verificações',
    $wpdb->prefix . 'apollo_documents' => 'Documentos',
    $wpdb->prefix . 'apollo_document_signatures' => 'Assinaturas',
    $wpdb->prefix . 'apollo_audit_log' => 'Audit Log',
    $wpdb->prefix . 'apollo_analytics_events' => 'Analytics Events',
];

foreach ($custom_tables as $table => $description) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $success[] = "✅ Tabela existe: {$table} ({$count} registros)";
        echo "   ✅ {$table} ({$count} registros)\n";
    } else {
        $warnings[] = "⚠️ Tabela não existe: {$table}";
        echo "   ⚠️ {$table} (não existe)\n";
    }
}

// Verificar Custom Post Types
echo "\n";
echo "   Custom Post Types:\n";
$cpts = ['event_listing', 'event_dj', 'event_local', 'user_page', 'cena_document', 'cena_event_plan'];
foreach ($cpts as $cpt) {
    $count = wp_count_posts($cpt);
    $total = $count->publish + $count->draft + $count->pending;
    if (post_type_exists($cpt)) {
        $success[] = "✅ CPT registrado: {$cpt} ({$total} posts)";
        echo "   ✅ {$cpt} ({$total} posts)\n";
    } else {
        $errors[] = "❌ CPT não registrado: {$cpt}";
        echo "   ❌ {$cpt} (não registrado)\n";
    }
}

// Verificar Taxonomies
echo "\n";
echo "   Taxonomies:\n";
$taxonomies = ['event_listing_category', 'event_sounds', 'event_listing_tag'];
foreach ($taxonomies as $tax) {
    if (taxonomy_exists($tax)) {
        $terms = wp_count_terms(['taxonomy' => $tax]);
        $success[] = "✅ Taxonomy registrada: {$tax} ({$terms} termos)";
        echo "   ✅ {$tax} ({$terms} termos)\n";
    } else {
        $warnings[] = "⚠️ Taxonomy não registrada: {$tax}";
        echo "   ⚠️ {$tax} (não registrada)\n";
    }
}

echo "\n";

// ============================================================================
// 5. VERIFICAR INTEGRAÇÃO ENTRE PLUGINS
// ============================================================================
echo "🔗 5. VERIFICANDO INTEGRAÇÃO ENTRE PLUGINS...\n";
echo "────────────────────────────────────────────────────────────────\n";

// Verificar se apollo-social pode usar apollo-events-manager
if (function_exists('apollo_aem_parse_ids')) {
    $success[] = "✅ apollo-social pode usar funções de apollo-events-manager";
    echo "   ✅ apollo-social → apollo-events-manager (OK)\n";
} else {
    $warnings[] = "⚠️ apollo-social não pode usar funções de apollo-events-manager";
    echo "   ⚠️ apollo-social → apollo-events-manager (funções não disponíveis)\n";
}

// Verificar se apollo-rio pode usar apollo-social
if (function_exists('apollo_shadcn_init')) {
    $success[] = "✅ apollo-rio pode usar ShadCN loader de apollo-social";
    echo "   ✅ apollo-rio → apollo-social ShadCN (OK)\n";
} else {
    $warnings[] = "⚠️ apollo-rio não pode usar ShadCN loader";
    echo "   ⚠️ apollo-rio → apollo-social ShadCN (não disponível)\n";
}

// Verificar se apollo-events-manager pode usar apollo-social
if (function_exists('apollo_shadcn_init')) {
    $success[] = "✅ apollo-events-manager pode usar ShadCN loader";
    echo "   ✅ apollo-events-manager → apollo-social ShadCN (OK)\n";
} else {
    $warnings[] = "⚠️ apollo-events-manager não pode usar ShadCN loader";
    echo "   ⚠️ apollo-events-manager → apollo-social ShadCN (não disponível)\n";
}

echo "\n";

// ============================================================================
// 6. VERIFICAR RESPONSIVIDADE E ASSETS
// ============================================================================
echo "📱 6. VERIFICANDO RESPONSIVIDADE E ASSETS...\n";
echo "────────────────────────────────────────────────────────────────\n";

// Verificar se uni.css tem media queries
$uni_css_content = @file_get_contents($uni_css_url);
if ($uni_css_content && (strpos($uni_css_content, '@media') !== false || strpos($uni_css_content, 'responsive') !== false)) {
    $success[] = "✅ uni.css contém media queries";
    echo "   ✅ uni.css tem suporte responsivo\n";
} else {
    $warnings[] = "⚠️ uni.css pode não ter media queries";
    echo "   ⚠️ uni.css pode não ter media queries\n";
}

// Verificar RemixIcon
$remixicon_url = 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css';
$ch = curl_init($remixicon_url);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_exec($ch);
$remixicon_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($remixicon_code === 200) {
    $success[] = "✅ RemixIcon acessível";
    echo "   ✅ RemixIcon acessível\n";
} else {
    $warnings[] = "⚠️ RemixIcon não acessível";
    echo "   ⚠️ RemixIcon não acessível\n";
}

echo "\n";

// ============================================================================
// 7. VERIFICAR SHORTCODES
// ============================================================================
echo "📝 7. VERIFICANDO SHORTCODES...\n";
echo "────────────────────────────────────────────────────────────────\n";

$shortcodes = ['events', 'apollo_events', 'apollo_user_page', 'apollo_builder'];
foreach ($shortcodes as $shortcode) {
    if (shortcode_exists($shortcode)) {
        $success[] = "✅ Shortcode registrado: [{$shortcode}]";
        echo "   ✅ [{$shortcode}]\n";
    } else {
        $warnings[] = "⚠️ Shortcode não registrado: [{$shortcode}]";
        echo "   ⚠️ [{$shortcode}] (não registrado)\n";
    }
}

echo "\n";

// ============================================================================
// 8. VERIFICAR PÁGINAS OBRIGATÓRIAS
// ============================================================================
echo "📄 8. VERIFICANDO PÁGINAS OBRIGATÓRIAS...\n";
echo "────────────────────────────────────────────────────────────────\n";

$required_pages = [
    'eventos' => 'Lista de Eventos',
    'cenario-new-event' => 'Formulário de Evento',
    'mod-events' => 'Moderação de Eventos',
    'registro' => 'Registro de Usuário',
];

foreach ($required_pages as $slug => $title) {
    $page = get_page_by_path($slug);
    if ($page && $page->post_status === 'publish') {
        $success[] = "✅ Página existe: /{$slug}/";
        echo "   ✅ /{$slug}/ ({$title})\n";
    } else {
        $warnings[] = "⚠️ Página não existe: /{$slug}/";
        echo "   ⚠️ /{$slug}/ ({$title}) - não existe\n";
    }
}

echo "\n";

// ============================================================================
// 9. VERIFICAR PERMISSÕES E CAPABILITIES
// ============================================================================
echo "🔐 9. VERIFICANDO PERMISSÕES...\n";
echo "────────────────────────────────────────────────────────────────\n";

$roles = ['administrator', 'editor', 'author', 'subscriber'];
foreach ($roles as $role) {
    $role_obj = get_role($role);
    if ($role_obj) {
        $caps = $role_obj->capabilities;
        $success[] = "✅ Role existe: {$role}";
        echo "   ✅ {$role} (" . count($caps) . " capabilities)\n";
    } else {
        $warnings[] = "⚠️ Role não existe: {$role}";
        echo "   ⚠️ {$role} (não existe)\n";
    }
}

echo "\n";

// ============================================================================
// 10. RESUMO FINAL
// ============================================================================
echo "════════════════════════════════════════════════════════════════\n";
echo "📊 RESUMO FINAL\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

echo "✅ SUCESSOS: " . count($success) . "\n";
echo "⚠️ AVISOS: " . count($warnings) . "\n";
echo "❌ ERROS: " . count($errors) . "\n";
echo "\n";

if (!empty($errors)) {
    echo "❌ ERROS CRÍTICOS:\n";
    foreach ($errors as $error) {
        echo "   {$error}\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️ AVISOS:\n";
    foreach (array_slice($warnings, 0, 10) as $warning) {
        echo "   {$warning}\n";
    }
    if (count($warnings) > 10) {
        echo "   ... e mais " . (count($warnings) - 10) . " avisos\n";
    }
    echo "\n";
}

$health_score = (count($success) / (count($success) + count($warnings) + count($errors))) * 100;
echo "🏥 HEALTH SCORE: " . round($health_score, 1) . "%\n";
echo "\n";

if ($health_score >= 90 && empty($errors)) {
    echo "════════════════════════════════════════════════════════════════\n";
    echo "✨ APOLLO ECOSYSTEM ESTÁ PRONTO PARA PRODUÇÃO!\n";
    echo "════════════════════════════════════════════════════════════════\n";
} else {
    echo "════════════════════════════════════════════════════════════════\n";
    echo "⚠️ CORREÇÕES NECESSÁRIAS ANTES DO LANÇAMENTO\n";
    echo "════════════════════════════════════════════════════════════════\n";
}

echo "\n";

