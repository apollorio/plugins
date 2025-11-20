<?php
/**
 * Checklist de Verificação Pós-Correção
 * 
 * Executa verificações completas após aplicar todas as correções:
 * - Meta keys corretas
 * - Activation hooks funcionando
 * - Templates carregando corretamente
 * - Banner e mapa funcionando
 * - Debug.log sem erros críticos
 * 
 * Uso: wp eval-file wp-content/plugins/apollo-events-manager/CHECKLIST-VERIFICACAO-POS-CORRECAO.php
 */

if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

echo "\n";
echo str_repeat("═", 70) . "\n";
echo "  CHECKLIST DE VERIFICAÇÃO PÓS-CORREÇÃO - Apollo Events Manager\n";
echo str_repeat("═", 70) . "\n\n";

$total_checks = 0;
$passed_checks = 0;
$failed_checks = 0;
$warnings = 0;

// Função helper para exibir resultado
function check_result($test_name, $passed, $message = '', $is_warning = false) {
    global $total_checks, $passed_checks, $failed_checks, $warnings;
    
    $total_checks++;
    
    if ($is_warning) {
        $warnings++;
        echo "  ⚠️  {$test_name}\n";
        if ($message) echo "     {$message}\n";
    } elseif ($passed) {
        $passed_checks++;
        echo "  ✅ {$test_name}\n";
        if ($message) echo "     {$message}\n";
    } else {
        $failed_checks++;
        echo "  ❌ {$test_name}\n";
        if ($message) echo "     {$message}\n";
    }
    echo "\n";
}

// ============================================
// 1. VERIFICAÇÃO DE PLUGINS ATIVOS
// ============================================
echo str_repeat("━", 70) . "\n";
echo "1. STATUS DOS PLUGINS\n";
echo str_repeat("━", 70) . "\n\n";

$plugins = array(
    'apollo-events-manager/apollo-events-manager.php' => 'Apollo Events Manager',
    'apollo-social/apollo-social.php' => 'Apollo Social',
    'apollo-rio/apollo-rio.php' => 'Apollo Rio',
);

foreach ($plugins as $plugin_file => $plugin_name) {
    $is_active = is_plugin_active($plugin_file);
    check_result(
        "Plugin: {$plugin_name}",
        $is_active,
        $is_active ? "Ativo e funcionando" : "INATIVO - Ative o plugin primeiro",
        false
    );
}

// ============================================
// 2. VERIFICAÇÃO DE META KEYS
// ============================================
echo str_repeat("━", 70) . "\n";
echo "2. VERIFICAÇÃO DE META KEYS NO BANCO\n";
echo str_repeat("━", 70) . "\n\n";

global $wpdb;

$events = $wpdb->get_results("
    SELECT ID, post_title 
    FROM {$wpdb->posts} 
    WHERE post_type = 'event_listing' 
    AND post_status = 'publish'
    ORDER BY ID DESC
    LIMIT 10
");

if (empty($events)) {
    check_result(
        "Eventos no banco",
        false,
        "Nenhum evento encontrado - crie pelo menos um evento para testar",
        true
    );
} else {
    check_result(
        "Eventos no banco",
        true,
        count($events) . " evento(s) encontrado(s)",
        false
    );
    
    $events_with_correct_keys = 0;
    $events_with_old_keys = 0;
    $events_with_issues = 0;
    
    foreach ($events as $event) {
        $event_id = $event->ID;
        $has_old_keys = false;
        $has_issues = false;
        
        // Verificar keys antigas
        $dj_old = get_post_meta($event_id, '_event_djs', true);
        $local_old = get_post_meta($event_id, '_event_local', true);
        
        if ($dj_old !== false && $dj_old !== '') {
            $has_old_keys = true;
        }
        if ($local_old !== false && $local_old !== '') {
            $has_old_keys = true;
        }
        
        // Verificar keys corretas
        $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
        $local_ids = get_post_meta($event_id, '_event_local_ids', true);
        $timetable = get_post_meta($event_id, '_event_timetable', true);
        
        // Verificar formato de _event_dj_ids
        if ($dj_ids !== false && $dj_ids !== '') {
            $dj_unserialized = maybe_unserialize($dj_ids);
            if (!is_array($dj_unserialized) && !is_numeric($dj_ids)) {
                $has_issues = true;
            }
        }
        
        // Verificar formato de _event_local_ids
        if ($local_ids !== false && $local_ids !== '') {
            if (!is_numeric($local_ids) && !is_array($local_ids)) {
                $has_issues = true;
            }
        }
        
        // Verificar formato de _event_timetable
        if ($timetable !== false && $timetable !== '') {
            $timetable_unserialized = maybe_unserialize($timetable);
            if (is_numeric($timetable)) {
                $has_issues = true; // Timetable não pode ser número
            }
        }
        
        if ($has_old_keys) {
            $events_with_old_keys++;
        } elseif ($has_issues) {
            $events_with_issues++;
        } else {
            $events_with_correct_keys++;
        }
    }
    
    check_result(
        "Meta keys corretas",
        $events_with_correct_keys > 0,
        "{$events_with_correct_keys} evento(s) com meta keys corretas",
        false
    );
    
    if ($events_with_old_keys > 0) {
        check_result(
            "Keys antigas removidas",
            false,
            "{$events_with_old_keys} evento(s) ainda tem keys antigas (_event_djs ou _event_local)",
            false
        );
    } else {
        check_result(
            "Keys antigas removidas",
            true,
            "Nenhuma key antiga encontrada",
            false
        );
    }
    
    if ($events_with_issues > 0) {
        check_result(
            "Formato de meta keys",
            false,
            "{$events_with_issues} evento(s) com formato incorreto",
            true
        );
    } else {
        check_result(
            "Formato de meta keys",
            true,
            "Todos os eventos têm formato correto",
            false
        );
    }
}

// ============================================
// 3. VERIFICAÇÃO DE ACTIVATION HOOKS
// ============================================
echo str_repeat("━", 70) . "\n";
echo "3. VERIFICAÇÃO DE ACTIVATION HOOKS\n";
echo str_repeat("━", 70) . "\n\n";

// Verificar função helper
check_result(
    "Função apollo_em_get_events_page() existe",
    function_exists('apollo_em_get_events_page'),
    function_exists('apollo_em_get_events_page') ? "Função disponível" : "Função NÃO encontrada",
    false
);

// Verificar página de eventos
if (function_exists('apollo_em_get_events_page')) {
    $events_page = apollo_em_get_events_page();
    if ($events_page) {
        check_result(
            "Página /eventos/ existe",
            true,
            "ID: {$events_page->ID}, Status: {$events_page->post_status}",
            false
        );
        
        if ($events_page->post_status === 'trash') {
            check_result(
                "Página /eventos/ não está na lixeira",
                false,
                "Página está na lixeira - será restaurada no próximo activation",
                true
            );
        } else {
            check_result(
                "Página /eventos/ não está na lixeira",
                true,
                "Página está publicada",
                false
            );
        }
    } else {
        check_result(
            "Página /eventos/ existe",
            false,
            "Página não encontrada - será criada no próximo activation",
            true
        );
    }
}

// Verificar rewrite rules flush
$last_flush = get_transient('apollo_rewrite_rules_last_flush');
if ($last_flush) {
    $time_ago = time() - $last_flush;
    $minutes_ago = round($time_ago / 60);
    check_result(
        "Rewrite rules flush (apollo-events-manager)",
        true,
        "Último flush: {$minutes_ago} minuto(s) atrás",
        false
    );
} else {
    check_result(
        "Rewrite rules flush (apollo-events-manager)",
        true,
        "Nenhum flush registrado ainda (normal na primeira ativação)",
        true
    );
}

$last_flush_social = get_transient('apollo_social_rewrite_rules_last_flush');
if ($last_flush_social) {
    $time_ago = time() - $last_flush_social;
    $minutes_ago = round($time_ago / 60);
    check_result(
        "Rewrite rules flush (apollo-social)",
        true,
        "Último flush: {$minutes_ago} minuto(s) atrás",
        false
    );
} else {
    check_result(
        "Rewrite rules flush (apollo-social)",
        true,
        "Nenhum flush registrado ainda (normal na primeira ativação)",
        true
    );
}

$last_flush_rio = get_transient('apollo_rio_rewrite_rules_last_flush');
if ($last_flush_rio) {
    $time_ago = time() - $last_flush_rio;
    $minutes_ago = round($time_ago / 60);
    check_result(
        "Rewrite rules flush (apollo-rio)",
        true,
        "Último flush: {$minutes_ago} minuto(s) atrás",
        false
    );
} else {
    check_result(
        "Rewrite rules flush (apollo-rio)",
        true,
        "Nenhum flush registrado ainda (normal na primeira ativação)",
        true
    );
}

// ============================================
// 4. VERIFICAÇÃO DE TEMPLATES E FUNÇÕES
// ============================================
echo str_repeat("━", 70) . "\n";
echo "4. VERIFICAÇÃO DE TEMPLATES E FUNÇÕES\n";
echo str_repeat("━", 70) . "\n\n";

$templates_to_check = array(
    'templates/portal-discover.php' => 'Portal Discover',
    'templates/event-card.php' => 'Event Card',
    'templates/content-event_listing.php' => 'Content Event Listing',
    'templates/single-event.php' => 'Single Event',
    'templates/single-event-page.php' => 'Single Event Page',
    'templates/single-event-standalone.php' => 'Single Event Standalone',
    'templates/dj-card.php' => 'DJ Card',
    'templates/local-card.php' => 'Local Card',
);

$plugin_path = plugin_dir_path(__FILE__);

foreach ($templates_to_check as $template_file => $template_name) {
    $full_path = $plugin_path . $template_file;
    check_result(
        "Template: {$template_name}",
        file_exists($full_path),
        file_exists($full_path) ? "Arquivo encontrado" : "Arquivo NÃO encontrado: {$template_file}",
        false
    );
}

// Verificar funções importantes
$functions_to_check = array(
    'apollo_clear_events_cache' => 'Limpeza de cache',
    'apollo_aem_parse_ids' => 'Parse de IDs',
    'apollo_sanitize_timetable' => 'Sanitize timetable',
    'apollo_get_primary_local_id' => 'Get primary local ID',
    'apollo_get_event_lineup' => 'Get event lineup',
);

foreach ($functions_to_check as $func_name => $func_desc) {
    check_result(
        "Função: {$func_desc}",
        function_exists($func_name),
        function_exists($func_name) ? "Disponível" : "NÃO encontrada",
        true // Warning porque pode não estar disponível em todos os contextos
    );
}

// ============================================
// 5. VERIFICAÇÃO DE BANNER E MAPA
// ============================================
echo str_repeat("━", 70) . "\n";
echo "5. VERIFICAÇÃO DE BANNER E MAPA\n";
echo str_repeat("━", 70) . "\n\n";

if (!empty($events)) {
    $event_with_banner = 0;
    $event_with_map = 0;
    $event_with_valid_banner = 0;
    
    foreach ($events as $event) {
        $event_id = $event->ID;
        
        // Verificar banner
        $banner = get_post_meta($event_id, '_event_banner', true);
        if ($banner !== false && $banner !== '') {
            $event_with_banner++;
            
            // Verificar se é URL válida
            if (filter_var($banner, FILTER_VALIDATE_URL)) {
                $event_with_valid_banner++;
            } elseif (is_numeric($banner)) {
                // É attachment ID - verificar se existe
                $attachment_url = wp_get_attachment_url($banner);
                if ($attachment_url) {
                    $event_with_valid_banner++;
                }
            }
        }
        
        // Verificar mapa (coordenadas)
        $local_id = get_post_meta($event_id, '_event_local_ids', true);
        if ($local_id) {
            $local_id = is_array($local_id) ? (int) reset($local_id) : (int) $local_id;
            
            if ($local_id > 0) {
                $lat = get_post_meta($local_id, '_local_latitude', true);
                $lng = get_post_meta($local_id, '_local_longitude', true);
                
                if (empty($lat)) {
                    $lat = get_post_meta($local_id, '_local_lat', true);
                }
                if (empty($lng)) {
                    $lng = get_post_meta($local_id, '_local_lng', true);
                }
                
                if (!empty($lat) && !empty($lng) && is_numeric($lat) && is_numeric($lng)) {
                    $event_with_map++;
                }
            }
        }
    }
    
    check_result(
        "Eventos com banner configurado",
        $event_with_banner > 0,
        "{$event_with_banner} evento(s) com banner",
        false
    );
    
    check_result(
        "Banners válidos (URL ou attachment)",
        $event_with_valid_banner === $event_with_banner,
        "{$event_with_valid_banner}/{$event_with_banner} banner(s) válido(s)",
        $event_with_valid_banner < $event_with_banner
    );
    
    check_result(
        "Eventos com coordenadas para mapa",
        $event_with_map > 0,
        "{$event_with_map} evento(s) com coordenadas válidas",
        false
    );
} else {
    check_result(
        "Banner e Mapa",
        false,
        "Nenhum evento para verificar - crie eventos para testar",
        true
    );
}

// ============================================
// 6. VERIFICAÇÃO DE DEBUG.LOG
// ============================================
echo str_repeat("━", 70) . "\n";
echo "6. VERIFICAÇÃO DE DEBUG.LOG\n";
echo str_repeat("━", 70) . "\n\n";

$debug_log_path = WP_CONTENT_DIR . '/debug.log';

if (file_exists($debug_log_path)) {
    check_result(
        "Arquivo debug.log existe",
        true,
        "Caminho: {$debug_log_path}",
        false
    );
    
    // Ler últimas 50 linhas do log
    $log_lines = file($debug_log_path);
    $recent_lines = array_slice($log_lines, -50);
    
    $apollo_errors = array();
    $apollo_warnings = array();
    $critical_errors = array();
    
    foreach ($recent_lines as $line) {
        // Buscar erros relacionados ao Apollo
        if (stripos($line, 'apollo') !== false || stripos($line, 'Apollo') !== false) {
            if (stripos($line, 'error') !== false || stripos($line, 'fatal') !== false || stripos($line, 'warning') !== false) {
                if (stripos($line, 'fatal') !== false) {
                    $critical_errors[] = trim($line);
                } elseif (stripos($line, 'error') !== false) {
                    $apollo_errors[] = trim($line);
                } else {
                    $apollo_warnings[] = trim($line);
                }
            }
        }
    }
    
    if (empty($critical_errors) && empty($apollo_errors)) {
        check_result(
            "Erros críticos no debug.log",
            true,
            "Nenhum erro crítico encontrado nas últimas 50 linhas",
            false
        );
    } else {
        check_result(
            "Erros críticos no debug.log",
            false,
            count($critical_errors) . " erro(s) fatal(is), " . count($apollo_errors) . " erro(s) encontrado(s)",
            false
        );
        
        if (!empty($critical_errors)) {
            echo "     Erros fatais encontrados:\n";
            foreach (array_slice($critical_errors, 0, 3) as $error) {
                echo "     - " . esc_html(substr($error, 0, 100)) . "...\n";
            }
            echo "\n";
        }
    }
    
    if (!empty($apollo_warnings)) {
        check_result(
            "Avisos no debug.log",
            true,
            count($apollo_warnings) . " aviso(s) encontrado(s) (normal)",
            true
        );
    } else {
        check_result(
            "Avisos no debug.log",
            true,
            "Nenhum aviso encontrado",
            false
        );
    }
} else {
    check_result(
        "Arquivo debug.log existe",
        true,
        "Debug.log não existe (normal se WP_DEBUG_LOG estiver desabilitado)",
        true
    );
}

// ============================================
// 7. VERIFICAÇÃO DE CACHE
// ============================================
echo str_repeat("━", 70) . "\n";
echo "7. VERIFICAÇÃO DE SISTEMA DE CACHE\n";
echo str_repeat("━", 70) . "\n\n";

check_result(
    "Função apollo_clear_events_cache() existe",
    function_exists('apollo_clear_events_cache'),
    function_exists('apollo_clear_events_cache') ? "Sistema de cache disponível" : "Função NÃO encontrada",
    false
);

if (function_exists('apollo_clear_events_cache')) {
    // Verificar transients de cache
    $cache_transients = array(
        'apollo_events:list:futuro',
        'apollo_events_portal_cache',
        'apollo_events_home_cache',
    );
    
    $active_caches = 0;
    foreach ($cache_transients as $transient_key) {
        if (get_transient($transient_key) !== false) {
            $active_caches++;
        }
    }
    
    check_result(
        "Transients de cache",
        true,
        "{$active_caches}/" . count($cache_transients) . " cache(s) ativo(s)",
        false
    );
}

// ============================================
// RESUMO FINAL
// ============================================
echo str_repeat("═", 70) . "\n";
echo "  RESUMO FINAL\n";
echo str_repeat("═", 70) . "\n\n";

echo "  Total de verificações: {$total_checks}\n";
echo "  ✅ Passou: {$passed_checks}\n";
echo "  ⚠️  Avisos: {$warnings}\n";
echo "  ❌ Falhou: {$failed_checks}\n\n";

$success_rate = $total_checks > 0 ? round(($passed_checks / $total_checks) * 100) : 0;
echo "  Taxa de sucesso: {$success_rate}%\n\n";

if ($failed_checks === 0 && $warnings === 0) {
    echo "  🎉 TODAS AS VERIFICAÇÕES PASSARAM!\n";
    echo "  O sistema está funcionando corretamente.\n\n";
} elseif ($failed_checks === 0) {
    echo "  ✅ VERIFICAÇÕES CRÍTICAS PASSARAM!\n";
    echo "  Alguns avisos foram encontrados, mas não são críticos.\n\n";
} else {
    echo "  ⚠️  ALGUMAS VERIFICAÇÕES FALHARAM!\n";
    echo "  Revise os itens marcados com ❌ acima.\n\n";
}

echo str_repeat("═", 70) . "\n";
echo "\nPara executar via WP-CLI:\n";
echo "wp eval-file wp-content/plugins/apollo-events-manager/CHECKLIST-VERIFICACAO-POS-CORRECAO.php\n\n";

