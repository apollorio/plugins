<?php
/**
 * Apollo Database Testing Script
 * 
 * Comprehensive database integrity and performance testing
 * Run via WP-CLI: wp eval-file APOLLO-DATABASE-TEST.php
 */

if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "💾 APOLLO DATABASE TESTING SUITE\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

global $wpdb;

$test_results = [];
$errors = [];
$warnings = [];

// ============================================================================
// 1. VERIFICAR ESTRUTURA DE TABELAS
// ============================================================================
echo "📊 1. VERIFICANDO ESTRUTURA DE TABELAS...\n";
echo "────────────────────────────────────────────────────────────────\n";

$required_tables = [
    $wpdb->users => 'Users',
    $wpdb->posts => 'Posts',
    $wpdb->postmeta => 'Post Meta',
    $wpdb->usermeta => 'User Meta',
    $wpdb->terms => 'Terms',
    $wpdb->term_taxonomy => 'Term Taxonomy',
    $wpdb->term_relationships => 'Term Relationships',
];

$custom_tables = [
    $wpdb->prefix . 'apollo_verifications' => 'Apollo Verifications',
    $wpdb->prefix . 'apollo_documents' => 'Apollo Documents',
    $wpdb->prefix . 'apollo_document_signatures' => 'Apollo Document Signatures',
    $wpdb->prefix . 'apollo_audit_log' => 'Apollo Audit Log',
    $wpdb->prefix . 'apollo_analytics_events' => 'Apollo Analytics Events',
];

foreach ($required_tables as $table => $name) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $test_results[] = ['table' => $table, 'status' => 'OK', 'count' => $count];
        echo "   ✅ {$name}: {$count} registros\n";
    } else {
        $errors[] = "Tabela não existe: {$table}";
        echo "   ❌ {$name}: não existe\n";
    }
}

echo "\n";
echo "   Tabelas customizadas:\n";
foreach ($custom_tables as $table => $name) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $test_results[] = ['table' => $table, 'status' => 'OK', 'count' => $count];
        echo "   ✅ {$name}: {$count} registros\n";
    } else {
        $warnings[] = "Tabela customizada não existe: {$table}";
        echo "   ⚠️ {$name}: não existe (pode ser criada sob demanda)\n";
    }
}

echo "\n";

// ============================================================================
// 2. VERIFICAR ÍNDICES E PERFORMANCE
// ============================================================================
echo "⚡ 2. VERIFICANDO ÍNDICES E PERFORMANCE...\n";
echo "────────────────────────────────────────────────────────────────\n";

$tables_to_check = [
    $wpdb->posts => ['post_type', 'post_status', 'post_author'],
    $wpdb->postmeta => ['post_id', 'meta_key'],
    $wpdb->usermeta => ['user_id', 'meta_key'],
];

foreach ($tables_to_check as $table => $columns) {
    $indexes = $wpdb->get_results("SHOW INDEX FROM {$table}");
    $indexed_columns = [];
    foreach ($indexes as $index) {
        $indexed_columns[] = $index->Column_name;
    }
    
    echo "   {$table}:\n";
    foreach ($columns as $column) {
        if (in_array($column, $indexed_columns)) {
            echo "      ✅ {$column} (indexado)\n";
        } else {
            $warnings[] = "Coluna sem índice: {$table}.{$column}";
            echo "      ⚠️ {$column} (não indexado)\n";
        }
    }
}

echo "\n";

// ============================================================================
// 3. VERIFICAR INTEGRIDADE DE DADOS
// ============================================================================
echo "🔍 3. VERIFICANDO INTEGRIDADE DE DADOS...\n";
echo "────────────────────────────────────────────────────────────────\n";

// Verificar orphaned postmeta
$orphaned_meta = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM {$wpdb->postmeta} pm
    LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
    WHERE p.ID IS NULL
");
if ($orphaned_meta > 0) {
    $warnings[] = "Postmeta órfão encontrado: {$orphaned_meta} registros";
    echo "   ⚠️ Postmeta órfão: {$orphaned_meta} registros\n";
} else {
    echo "   ✅ Nenhum postmeta órfão\n";
}

// Verificar orphaned usermeta
$orphaned_usermeta = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM {$wpdb->usermeta} um
    LEFT JOIN {$wpdb->users} u ON um.user_id = u.ID
    WHERE u.ID IS NULL
");
if ($orphaned_usermeta > 0) {
    $warnings[] = "Usermeta órfão encontrado: {$orphaned_usermeta} registros";
    echo "   ⚠️ Usermeta órfão: {$orphaned_usermeta} registros\n";
} else {
    echo "   ✅ Nenhum usermeta órfão\n";
}

// Verificar eventos sem data
$events_no_date = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_event_start_date'
    WHERE p.post_type = 'event_listing' 
    AND p.post_status = 'publish'
    AND (pm.meta_value IS NULL OR pm.meta_value = '')
");
if ($events_no_date > 0) {
    $warnings[] = "Eventos sem data de início: {$events_no_date}";
    echo "   ⚠️ Eventos sem data: {$events_no_date}\n";
} else {
    echo "   ✅ Todos eventos têm data\n";
}

echo "\n";

// ============================================================================
// 4. VERIFICAR META KEYS CRÍTICAS
// ============================================================================
echo "🔑 4. VERIFICANDO META KEYS CRÍTICAS...\n";
echo "────────────────────────────────────────────────────────────────\n";

$critical_meta_keys = [
    'event_listing' => [
        '_event_title',
        '_event_start_date',
        '_event_banner',
        '_event_dj_ids',
        '_event_local_ids',
    ],
    'event_dj' => [
        '_dj_name',
    ],
    'event_local' => [
        '_local_name',
        '_local_address',
    ],
];

foreach ($critical_meta_keys as $post_type => $meta_keys) {
    echo "   {$post_type}:\n";
    foreach ($meta_keys as $meta_key) {
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT pm.post_id)
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = %s
            AND p.post_status = 'publish'
            AND pm.meta_key = %s
            AND pm.meta_value != ''
        ", $post_type, $meta_key));
        
        $total = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = %s 
            AND post_status = 'publish'
        ", $post_type));
        
        $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
        
        if ($percentage >= 80) {
            echo "      ✅ {$meta_key}: {$count}/{$total} ({$percentage}%)\n";
        } else {
            $warnings[] = "Meta key com baixa cobertura: {$post_type}.{$meta_key} ({$percentage}%)";
            echo "      ⚠️ {$meta_key}: {$count}/{$total} ({$percentage}%)\n";
        }
    }
}

echo "\n";

// ============================================================================
// 5. VERIFICAR USER METAS DE REGISTRO
// ============================================================================
echo "👤 5. VERIFICANDO USER METAS DE REGISTRO...\n";
echo "────────────────────────────────────────────────────────────────\n";

$registration_meta_keys = [
    'apollo_cpf',
    'apollo_sounds',
    'apollo_quizz_answers',
    'apollo_registration_complete',
];

foreach ($registration_meta_keys as $meta_key) {
    $count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT user_id)
        FROM {$wpdb->usermeta}
        WHERE meta_key = %s
    ", $meta_key));
    
    $total_users = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
    $percentage = $total_users > 0 ? round(($count / $total_users) * 100, 1) : 0;
    
    echo "   {$meta_key}: {$count}/{$total_users} ({$percentage}%)\n";
}

echo "\n";

// ============================================================================
// 6. TESTE DE PERFORMANCE
// ============================================================================
echo "⚡ 6. TESTE DE PERFORMANCE...\n";
echo "────────────────────────────────────────────────────────────────\n";

// Test query performance
$start_time = microtime(true);
$events = $wpdb->get_results("
    SELECT p.ID, p.post_title, pm.meta_value as start_date
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_event_start_date'
    WHERE p.post_type = 'event_listing'
    AND p.post_status = 'publish'
    LIMIT 100
");
$query_time = (microtime(true) - $start_time) * 1000;

if ($query_time < 100) {
    echo "   ✅ Query de eventos: {$query_time}ms (excelente)\n";
} elseif ($query_time < 500) {
    echo "   ⚠️ Query de eventos: {$query_time}ms (aceitável)\n";
} else {
    $warnings[] = "Query lenta detectada: {$query_time}ms";
    echo "   ⚠️ Query de eventos: {$query_time}ms (lenta)\n";
}

// Test cache performance
$start_time = microtime(true);
for ($i = 0; $i < 100; $i++) {
    wp_cache_set("test_key_{$i}", "test_value_{$i}", '', 60);
    wp_cache_get("test_key_{$i}");
}
$cache_time = (microtime(true) - $start_time) * 1000;
echo "   ✅ Cache performance: " . round($cache_time / 100, 2) . "ms por operação\n";

echo "\n";

// ============================================================================
// 7. VERIFICAR DADOS DE EXEMPLO
// ============================================================================
echo "📝 7. VERIFICANDO DADOS DE EXEMPLO...\n";
echo "────────────────────────────────────────────────────────────────\n";

// Verificar se há eventos de exemplo
$sample_events = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM {$wpdb->posts} 
    WHERE post_type = 'event_listing' 
    AND post_status = 'publish'
");
echo "   Eventos publicados: {$sample_events}\n";

// Verificar se há DJs
$sample_djs = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM {$wpdb->posts} 
    WHERE post_type = 'event_dj' 
    AND post_status = 'publish'
");
echo "   DJs publicados: {$sample_djs}\n";

// Verificar se há locais
$sample_locals = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM {$wpdb->posts} 
    WHERE post_type = 'event_local' 
    AND post_status = 'publish'
");
echo "   Locais publicados: {$sample_locals}\n";

echo "\n";

// ============================================================================
// RESUMO FINAL
// ============================================================================
echo "════════════════════════════════════════════════════════════════\n";
echo "📊 RESUMO FINAL\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

echo "✅ Testes passados: " . count(array_filter($test_results, fn($r) => $r['status'] === 'OK')) . "\n";
echo "⚠️ Avisos: " . count($warnings) . "\n";
echo "❌ Erros: " . count($errors) . "\n";
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

$health_score = (count($test_results) - count($errors)) / max(count($test_results), 1) * 100;
echo "🏥 DATABASE HEALTH SCORE: " . round($health_score, 1) . "%\n";
echo "\n";

if ($health_score >= 90 && empty($errors)) {
    echo "════════════════════════════════════════════════════════════════\n";
    echo "✨ BANCO DE DADOS PRONTO PARA PRODUÇÃO!\n";
    echo "════════════════════════════════════════════════════════════════\n";
} else {
    echo "════════════════════════════════════════════════════════════════\n";
    echo "⚠️ CORREÇÕES NECESSÁRIAS NO BANCO DE DADOS\n";
    echo "════════════════════════════════════════════════════════════════\n";
}

echo "\n";

