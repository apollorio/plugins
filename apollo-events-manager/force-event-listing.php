<?php
/**
 * FORCE EVENT LISTING - Diagnostic and Fix Script
 *
 * Este script força a reinicialização do CPT event_listing e suas rewrite rules.
 * IMPORTANTE: Delete este arquivo após usar!
 *
 * Uso:
 * 1. Acesse: /wp-content/plugins/apollo-events-manager/force-event-listing.php
 * 2. Clique em "Forçar Flush" para reconstruir as rewrite rules
 *
 * @package Apollo_Events_Manager
 */

// Load WordPress
$wp_load_paths = [
    dirname(__FILE__) . '/../../../../wp-load.php',
    dirname(__FILE__) . '/../../../wp-load.php',
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('WordPress não encontrado. Execute via WP admin.');
}

// Security check
if (!current_user_can('manage_options')) {
    wp_die('Acesso negado. Faça login como administrador.');
}

// Handle actions
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
$message = '';

if ($action === 'flush') {
    // Force re-register post types
    if (class_exists('Apollo_Post_Types')) {
        $pt = new Apollo_Post_Types();
        $pt->register_post_types();
        $pt->register_taxonomies();
    }

    // Flush rewrite rules
    flush_rewrite_rules(true);
    $message = '<div style="background:#10b981;color:white;padding:15px;border-radius:8px;margin:20px 0;">
        ✓ Rewrite rules reconstruídas com sucesso! CPT event_listing re-registrado.
    </div>';
}

if ($action === 'test_query') {
    global $wpdb;
    $events = $wpdb->get_results("
        SELECT ID, post_title, post_status, post_type
        FROM {$wpdb->posts}
        WHERE post_type = 'event_listing'
        AND post_status IN ('publish', 'future', 'draft')
        ORDER BY ID DESC
        LIMIT 10
    ");

    $message = '<div style="background:#3b82f6;color:white;padding:15px;border-radius:8px;margin:20px 0;">
        <h3>Query de Teste Executada</h3>
        <p>Encontrados ' . count($events) . ' eventos event_listing</p>';

    if (!empty($events)) {
        $message .= '<ul>';
        foreach ($events as $event) {
            $message .= "<li>ID: {$event->ID} - {$event->post_title} ({$event->post_status})</li>";
        }
        $message .= '</ul>';
    }
    $message .= '</div>';
}

// Get CPT info
$event_listing_cpt = get_post_type_object('event_listing');
$event_dj_cpt = get_post_type_object('event_dj');
$event_local_cpt = get_post_type_object('event_local');

// Get event counts
$event_count = wp_count_posts('event_listing');
$dj_count = wp_count_posts('event_dj');
$local_count = wp_count_posts('event_local');

// Get rewrite rules
global $wp_rewrite;
$rewrite_rules = $wp_rewrite->wp_rewrite_rules();
$event_rules = [];

if (is_array($rewrite_rules)) {
    foreach ($rewrite_rules as $rule => $rewrite) {
        if (strpos($rule, 'evento') !== false || strpos($rewrite, 'event_listing') !== false) {
            $event_rules[$rule] = $rewrite;
        }
    }
}

// Get sample events via WP_Query
$sample_query = new WP_Query([
    'post_type' => 'event_listing',
    'posts_per_page' => 5,
    'post_status' => ['publish', 'future'],
    'orderby' => 'meta_value',
    'meta_key' => '_event_start_date',
    'order' => 'ASC',
    'meta_query' => [
        [
            'key' => '_event_start_date',
            'value' => date('Y-m-d'),
            'compare' => '>=',
            'type' => 'DATE',
        ]
    ]
]);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Force Event Listing - Apollo Events Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a0a0a;
            color: #e5e5e5;
            padding: 30px;
            line-height: 1.6;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 {
            color: #fff;
            font-size: 2rem;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        h2 { color: #a855f7; margin: 25px 0 15px; font-size: 1.3rem; }
        .card {
            background: #171717;
            border: 1px solid #262626;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .status { display: inline-flex; align-items: center; gap: 8px; }
        .status.ok { color: #10b981; }
        .status.warn { color: #f59e0b; }
        .status.error { color: #ef4444; }
        .status::before { content: '●'; font-size: 0.8em; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td {
            padding: 10px 15px;
            text-align: left;
            border-bottom: 1px solid #262626;
        }
        th { color: #a855f7; font-weight: 600; }
        code {
            background: #262626;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9em;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #a855f7;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn:hover { background: #9333ea; }
        .btn.secondary { background: #3b82f6; }
        .btn.secondary:hover { background: #2563eb; }
        .actions { display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap; }
        .warn-box {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 15px;
            margin-top: 30px;
        }
        .event-list { list-style: none; }
        .event-list li {
            padding: 10px 0;
            border-bottom: 1px solid #262626;
        }
        .event-list li:last-child { border-bottom: none; }
        .meta { color: #737373; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎵 Force Event Listing - Diagnóstico</h1>

        <?php echo $message; ?>

        <div class="actions">
            <a href="?action=flush" class="btn">⚡ Forçar Flush das Rewrite Rules</a>
            <a href="?action=test_query" class="btn secondary">🔍 Testar Query</a>
            <a href="<?php echo admin_url('edit.php?post_type=event_listing'); ?>" class="btn secondary">📋 Ver no Admin</a>
        </div>

        <h2>📊 Status dos CPTs</h2>
        <div class="grid">
            <div class="card">
                <h3>event_listing (Eventos)</h3>
                <p class="status <?php echo $event_listing_cpt ? 'ok' : 'error'; ?>">
                    <?php echo $event_listing_cpt ? 'Registrado' : 'NÃO REGISTRADO!'; ?>
                </p>
                <?php if ($event_listing_cpt): ?>
                <table>
                    <tr><th>Slug</th><td><code><?php echo $event_listing_cpt->rewrite['slug'] ?? 'N/A'; ?></code></td></tr>
                    <tr><th>Archive</th><td><code><?php echo $event_listing_cpt->has_archive; ?></code></td></tr>
                    <tr><th>REST Base</th><td><code><?php echo $event_listing_cpt->rest_base; ?></code></td></tr>
                    <tr><th>Public</th><td><?php echo $event_listing_cpt->public ? '✓ Sim' : '✗ Não'; ?></td></tr>
                    <tr><th>Publicly Queryable</th><td><?php echo $event_listing_cpt->publicly_queryable ? '✓ Sim' : '✗ Não'; ?></td></tr>
                </table>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3>Contagem de Posts</h3>
                <table>
                    <tr><th>Tipo</th><th>Publicados</th><th>Rascunhos</th><th>Futuros</th></tr>
                    <tr>
                        <td>event_listing</td>
                        <td><?php echo $event_count->publish ?? 0; ?></td>
                        <td><?php echo $event_count->draft ?? 0; ?></td>
                        <td><?php echo $event_count->future ?? 0; ?></td>
                    </tr>
                    <tr>
                        <td>event_dj</td>
                        <td><?php echo $dj_count->publish ?? 0; ?></td>
                        <td><?php echo $dj_count->draft ?? 0; ?></td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td>event_local</td>
                        <td><?php echo $local_count->publish ?? 0; ?></td>
                        <td><?php echo $local_count->draft ?? 0; ?></td>
                        <td>-</td>
                    </tr>
                </table>
            </div>
        </div>

        <h2>🔗 Rewrite Rules para event_listing</h2>
        <div class="card">
            <?php if (empty($event_rules)): ?>
                <p class="status error">Nenhuma rewrite rule encontrada para evento/event_listing!</p>
                <p>Clique em "Forçar Flush" acima para reconstruir.</p>
            <?php else: ?>
                <p class="status ok"><?php echo count($event_rules); ?> regras encontradas</p>
                <table>
                    <tr><th>Pattern</th><th>Rewrite</th></tr>
                    <?php foreach (array_slice($event_rules, 0, 10) as $rule => $rewrite): ?>
                    <tr>
                        <td><code><?php echo esc_html($rule); ?></code></td>
                        <td><code><?php echo esc_html($rewrite); ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <h2>📅 Próximos Eventos (via WP_Query)</h2>
        <div class="card">
            <?php if ($sample_query->have_posts()): ?>
                <p class="status ok"><?php echo $sample_query->found_posts; ?> eventos futuros encontrados</p>
                <ul class="event-list">
                    <?php while ($sample_query->have_posts()): $sample_query->the_post(); ?>
                        <li>
                            <strong><?php the_title(); ?></strong>
                            <span class="meta">
                                ID: <?php the_ID(); ?> |
                                Data: <?php echo get_post_meta(get_the_ID(), '_event_start_date', true); ?> |
                                Status: <?php echo get_post_status(); ?>
                            </span>
                        </li>
                    <?php endwhile; wp_reset_postdata(); ?>
                </ul>
            <?php else: ?>
                <p class="status warn">Nenhum evento futuro encontrado com a query:</p>
                <pre style="background:#262626;padding:15px;border-radius:8px;overflow-x:auto;">
WP_Query([
    'post_type' => 'event_listing',
    'posts_per_page' => 5,
    'post_status' => ['publish', 'future'],
    'orderby' => 'meta_value',
    'meta_key' => '_event_start_date',
    'order' => 'ASC',
    'meta_query' => [[
        'key' => '_event_start_date',
        'value' => '<?php echo date('Y-m-d'); ?>',
        'compare' => '>=',
        'type' => 'DATE',
    ]]
]);</pre>
            <?php endif; ?>
        </div>

        <h2>🔗 Links Importantes</h2>
        <div class="card">
            <table>
                <tr>
                    <th>Página /eventos/</th>
                    <td><a href="<?php echo home_url('/eventos/'); ?>" target="_blank"><?php echo home_url('/eventos/'); ?></a></td>
                </tr>
                <tr>
                    <th>Archive event_listing</th>
                    <td><a href="<?php echo get_post_type_archive_link('event_listing'); ?>" target="_blank"><?php echo get_post_type_archive_link('event_listing') ?: 'Não disponível'; ?></a></td>
                </tr>
                <tr>
                    <th>REST API Events</th>
                    <td><a href="<?php echo rest_url('wp/v2/events'); ?>" target="_blank"><?php echo rest_url('wp/v2/events'); ?></a></td>
                </tr>
            </table>
        </div>

        <div class="warn-box">
            <strong>⚠️ IMPORTANTE:</strong> Delete este arquivo após usar por razões de segurança!<br>
            Caminho: <code>wp-content/plugins/apollo-events-manager/force-event-listing.php</code>
        </div>
    </div>
</body>
</html>
