<?php
/**
 * Force Match - Event Listing & Eventos Page Complete Sync
 *
 * Este script força a sincronização completa entre:
 * - CPT event_listing
 * - Página /eventos/
 * - Rewrite rules
 * - Template loading
 *
 * IMPORTANTE: Delete este arquivo após usar!
 *
 * @package Apollo_Events_Manager
 */

// Load WordPress
$wp_load_path = dirname(__FILE__) . '/../../../../wp-load.php';
if (!file_exists($wp_load_path)) {
    die('WordPress não encontrado.');
}
require_once $wp_load_path;

// Security check
if (!current_user_can('manage_options')) {
    wp_die('Acesso negado. Faça login como administrador.');
}

// Get action
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
$results = [];

// === ACTION: Full Force Sync ===
if ($action === 'force_sync') {

    // 1. Re-register all CPTs
    if (class_exists('Apollo_Post_Types')) {
        $pt = new Apollo_Post_Types();
        $pt->register_post_types();
        $pt->register_taxonomies();
        $results[] = '✓ CPTs re-registrados';
    } else {
        $results[] = '✗ Apollo_Post_Types não encontrada - recarregando...';

        $post_types_file = dirname(__FILE__) . '/includes/post-types.php';
        if (file_exists($post_types_file)) {
            require_once $post_types_file;
            $pt = new Apollo_Post_Types();
            $results[] = '✓ post-types.php carregado e CPTs registrados';
        }
    }

    // 2. Delete transient to force page check
    delete_transient('apollo_eventos_page_check');
    $results[] = '✓ Transient de verificação de página deletado';

    // 3. Ensure eventos page exists
    $eventos_page = get_page_by_path('eventos');
    if (!$eventos_page) {
        $page_id = wp_insert_post([
            'post_title'   => 'Eventos',
            'post_name'    => 'eventos',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '',
            'meta_input'   => [
                '_wp_page_template' => 'page-eventos.php',
            ],
        ]);

        if (!is_wp_error($page_id)) {
            $results[] = '✓ Página /eventos/ criada (ID: ' . $page_id . ')';
        } else {
            $results[] = '✗ Erro ao criar página: ' . $page_id->get_error_message();
        }
    } else {
        // Ensure page is published
        if ($eventos_page->post_status !== 'publish') {
            wp_update_post([
                'ID' => $eventos_page->ID,
                'post_status' => 'publish',
            ]);
            $results[] = '✓ Página /eventos/ republicada (ID: ' . $eventos_page->ID . ')';
        } else {
            $results[] = '✓ Página /eventos/ já existe e está publicada (ID: ' . $eventos_page->ID . ')';
        }
    }

    // 4. Flush rewrite rules HARD
    global $wp_rewrite;
    $wp_rewrite->init();
    $wp_rewrite->flush_rules(true);
    $results[] = '✓ Rewrite rules reconstruídas (hard flush)';

    // 5. Clear any caches
    wp_cache_flush();
    $results[] = '✓ Cache do WordPress limpo';
}

// === Gather current status ===
$event_listing_cpt = get_post_type_object('event_listing');
$eventos_page = get_page_by_path('eventos');
$event_count = wp_count_posts('event_listing');

// Get rewrite rules for events
global $wp_rewrite;
$all_rules = $wp_rewrite->wp_rewrite_rules();
$event_rules = [];
if (is_array($all_rules)) {
    foreach ($all_rules as $pattern => $rewrite) {
        if (strpos($pattern, 'evento') !== false || strpos($rewrite, 'event_listing') !== false) {
            $event_rules[$pattern] = $rewrite;
        }
    }
}

// Test WP_Query
$test_query = new WP_Query([
    'post_type' => 'event_listing',
    'post_status' => ['publish', 'future'],
    'posts_per_page' => 5,
]);

// Check if loader class exists
$loader_exists = class_exists('Apollo_Page_Eventos_Loader');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Force Match - Apollo Events</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a0a2e 100%);
            color: #e5e5e5;
            min-height: 100vh;
            padding: 40px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #a855f7, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .subtitle { color: #737373; margin-bottom: 40px; }

        .action-btn {
            display: inline-block;
            padding: 16px 32px;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, #a855f7, #7c3aed);
            border: none;
            border-radius: 12px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(168, 85, 247, 0.3);
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(168, 85, 247, 0.4);
        }

        .results {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid #10b981;
            border-radius: 12px;
            padding: 20px;
            margin: 30px 0;
        }
        .results h3 { color: #10b981; margin-bottom: 15px; }
        .results li { padding: 8px 0; list-style: none; }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }
        .status-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 24px;
        }
        .status-card h3 {
            color: #a855f7;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }
        .status-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .status-item:last-child { border-bottom: none; }
        .status-label { color: #737373; }
        .status-value { font-weight: 600; }
        .status-value.ok { color: #10b981; }
        .status-value.warn { color: #f59e0b; }
        .status-value.error { color: #ef4444; }

        .links { margin-top: 40px; }
        .links a {
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 10px;
            color: #06b6d4;
            text-decoration: none;
        }
        .links a:hover { text-decoration: underline; }

        .warning-box {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            border-radius: 12px;
            padding: 20px;
            margin-top: 40px;
        }
        .warning-box strong { color: #ef4444; }
        code {
            background: rgba(255,255,255,0.1);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚡ Force Match</h1>
        <p class="subtitle">Sincronização forçada do CPT event_listing e página /eventos/</p>

        <a href="?action=force_sync" class="action-btn">🔄 Forçar Sincronização Completa</a>

        <?php if (!empty($results)): ?>
        <div class="results">
            <h3>Resultados da Sincronização</h3>
            <ul>
                <?php foreach ($results as $r): ?>
                    <li><?php echo esc_html($r); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="status-grid">
            <div class="status-card">
                <h3>CPT event_listing</h3>
                <div class="status-item">
                    <span class="status-label">Status</span>
                    <span class="status-value <?php echo $event_listing_cpt ? 'ok' : 'error'; ?>">
                        <?php echo $event_listing_cpt ? 'Registrado' : 'NÃO REGISTRADO'; ?>
                    </span>
                </div>
                <?php if ($event_listing_cpt): ?>
                <div class="status-item">
                    <span class="status-label">Slug</span>
                    <span class="status-value"><?php echo $event_listing_cpt->rewrite['slug'] ?? 'N/A'; ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">Archive</span>
                    <span class="status-value"><?php echo $event_listing_cpt->has_archive ?: 'false'; ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">REST Base</span>
                    <span class="status-value"><?php echo $event_listing_cpt->rest_base; ?></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="status-card">
                <h3>Página /eventos/</h3>
                <div class="status-item">
                    <span class="status-label">Status</span>
                    <span class="status-value <?php echo $eventos_page && $eventos_page->post_status === 'publish' ? 'ok' : 'error'; ?>">
                        <?php echo $eventos_page ? 'Existe (ID: ' . $eventos_page->ID . ')' : 'NÃO EXISTE'; ?>
                    </span>
                </div>
                <div class="status-item">
                    <span class="status-label">Post Status</span>
                    <span class="status-value"><?php echo $eventos_page ? $eventos_page->post_status : 'N/A'; ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">Loader Class</span>
                    <span class="status-value <?php echo $loader_exists ? 'ok' : 'error'; ?>">
                        <?php echo $loader_exists ? 'Carregada' : 'NÃO ENCONTRADA'; ?>
                    </span>
                </div>
            </div>

            <div class="status-card">
                <h3>Contagem de Eventos</h3>
                <div class="status-item">
                    <span class="status-label">Publicados</span>
                    <span class="status-value <?php echo ($event_count->publish ?? 0) > 0 ? 'ok' : 'warn'; ?>">
                        <?php echo $event_count->publish ?? 0; ?>
                    </span>
                </div>
                <div class="status-item">
                    <span class="status-label">Futuros</span>
                    <span class="status-value"><?php echo $event_count->future ?? 0; ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">Rascunhos</span>
                    <span class="status-value"><?php echo $event_count->draft ?? 0; ?></span>
                </div>
            </div>

            <div class="status-card">
                <h3>Rewrite Rules</h3>
                <div class="status-item">
                    <span class="status-label">Total Rules</span>
                    <span class="status-value"><?php echo count($event_rules); ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">evento/* rule</span>
                    <span class="status-value <?php echo isset($all_rules['evento/([^/]+)/?$']) ? 'ok' : 'warn'; ?>">
                        <?php echo isset($all_rules['evento/([^/]+)/?$']) ? 'OK' : 'Não encontrada'; ?>
                    </span>
                </div>
                <div class="status-item">
                    <span class="status-label">eventos archive</span>
                    <span class="status-value <?php echo isset($all_rules['eventos/?$']) ? 'ok' : 'warn'; ?>">
                        <?php echo isset($all_rules['eventos/?$']) ? 'OK' : 'Não encontrada'; ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if ($test_query->have_posts()): ?>
        <div class="status-card" style="margin-top: 30px;">
            <h3>Últimos Eventos (via WP_Query)</h3>
            <?php while ($test_query->have_posts()): $test_query->the_post(); ?>
            <div class="status-item">
                <span class="status-label">
                    ID <?php the_ID(); ?>: <?php the_title(); ?>
                </span>
                <span class="status-value"><?php echo get_post_status(); ?></span>
            </div>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
        <?php endif; ?>

        <div class="links">
            <h3 style="margin-bottom: 15px;">Links de Teste</h3>
            <a href="<?php echo home_url('/eventos/'); ?>" target="_blank">🔗 /eventos/</a>
            <a href="<?php echo get_post_type_archive_link('event_listing'); ?>" target="_blank">🔗 Archive event_listing</a>
            <a href="<?php echo rest_url('wp/v2/events'); ?>" target="_blank">🔗 REST API /events</a>
            <a href="<?php echo admin_url('edit.php?post_type=event_listing'); ?>" target="_blank">🔗 Admin Eventos</a>
        </div>

        <div class="warning-box">
            <strong>⚠️ SEGURANÇA:</strong> Delete este arquivo após usar!<br>
            <code>wp-content/plugins/apollo-events-manager/force-match.php</code>
        </div>
    </div>
</body>
</html>
