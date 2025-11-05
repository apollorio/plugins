<?php
/**
 * Apollo Events Manager - Analytics Core
 *
 * Camada central para registros de interações e cálculo de métricas
 * sem depender diretamente de integrações externas (ex.: Plausible).
 *
 * Responsabilidades:
 * - Criar/atualizar a tabela `{prefix}apollo_event_stats`
 * - Registrar visualizações de eventos e sinalizar favoritos/coautorias
 * - Disponibilizar funções helper reutilizáveis para dashboards (admin/front)
 *
 * Nota: Nenhum dado pessoal sensível deve ser persistido aqui
 * (apenas IDs e informações agregadas para analytics interno).
 */

defined('ABSPATH') || exit;

/**
 * Nome da tabela de estatísticas.
 */
function apollo_events_stats_table_name(): string {
    global $wpdb;
    return $wpdb->prefix . 'apollo_event_stats';
}

/**
 * Cria ou atualiza a tabela de estatísticas usando dbDelta.
 */
function apollo_events_install_analytics_schema(): void {
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();
    $table = apollo_events_stats_table_name();

    $sql = "CREATE TABLE {$table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        event_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        views BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        favorited TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
        is_coauthor TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
        last_interaction DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY  (id),
        UNIQUE KEY user_event (user_id, event_id),
        KEY event_id (event_id),
        KEY user_id (user_id)
    ) {$charset_collate};";

    dbDelta($sql);
}

/**
 * Verifica se a tabela existe e cria caso necessário.
 */
function apollo_events_maybe_upgrade_analytics_schema(): void {
    global $wpdb;

    $table = apollo_events_stats_table_name();
    $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));

    if ($exists !== $table) {
        apollo_events_install_analytics_schema();
    }
}
add_action('plugins_loaded', 'apollo_events_maybe_upgrade_analytics_schema');

/**
 * Garante que cargos principais possuam a capability de visualização das estatísticas.
 */
function apollo_events_register_stats_capabilities(): void {
    $roles = array('administrator', 'editor');

    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        if ($role && !$role->has_cap('view_apollo_event_stats')) {
            $role->add_cap('view_apollo_event_stats');
        }
    }
}
add_action('init', 'apollo_events_register_stats_capabilities');

/**
 * Retorna IDs de coautores para um evento (array de ints).
 */
function apollo_events_get_event_coauthors(int $event_id): array {
    $coauthors = get_post_meta($event_id, '_apollo_coauthors', true);
    if (empty($coauthors)) {
        return array();
    }

    if (!is_array($coauthors)) {
        $coauthors = maybe_unserialize($coauthors);
    }

    if (!is_array($coauthors)) {
        return array();
    }

    return array_values(array_filter(array_map('intval', $coauthors), static function ($id) {
        return $id > 0;
    }));
}

/**
 * Verifica se um usuário é coautor de determinado evento.
 */
function apollo_events_user_is_coauthor(int $user_id, int $event_id): bool {
    if ($user_id <= 0 || $event_id <= 0) {
        return false;
    }

    $coauthors = apollo_events_get_event_coauthors($event_id);
    return in_array($user_id, $coauthors, true);
}

/**
 * Obtém IDs de eventos onde o usuário é coautor.
 */
function apollo_events_get_user_coauthored_event_ids(int $user_id): array {
    if ($user_id <= 0) {
        return array();
    }

    $config = apollo_cfg();
    $post_type = isset($config['cpt']['event']) ? $config['cpt']['event'] : 'event_listing';

    $events = get_posts(array(
        'post_type'      => $post_type,
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_key'       => '_apollo_coauthors',
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ));

    if (!$events) {
        return array();
    }

    $matched = array();
    foreach ($events as $event_id) {
        if (apollo_events_user_is_coauthor($user_id, (int) $event_id)) {
            $matched[] = (int) $event_id;
        }
    }

    return $matched;
}

/**
 * Lê favoritos do usuário a partir do plugin wpem-bookmarks (quando presente).
 * Fallback: user meta `_apollo_event_favorites` (array de event IDs).
 */
function apollo_events_get_user_favorite_event_ids(int $user_id): array {
    if ($user_id <= 0) {
        return array();
    }

    global $wpdb;

    $ids = array();
    $table = $wpdb->prefix . 'wpem_bookmarks';
    $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));

    if ($table_exists === $table) {
        $columns = $wpdb->get_col("DESC {$table}", 0);
        $type_column = in_array('post_type', $columns, true) ? 'post_type' : (in_array('type', $columns, true) ? 'type' : '');

        if ($type_column) {
            $sql = $wpdb->prepare(
                "SELECT post_id FROM {$table} WHERE user_id = %d AND {$type_column} = %s",
                $user_id,
                'event_listing'
            );
        } else {
            $sql = $wpdb->prepare(
                "SELECT post_id FROM {$table} WHERE user_id = %d",
                $user_id
            );
        }

        $ids = $wpdb->get_col($sql);

        // Filtra apenas IDs válidos
        $ids = array_values(array_filter(array_map('intval', (array) $ids), static function ($id) {
            return $id > 0;
        }));
    }

    // Fallback em caso de ausência da tabela
    if (empty($ids)) {
        $meta = get_user_meta($user_id, '_apollo_event_favorites', true);
        if (is_array($meta)) {
            $ids = array_values(array_filter(array_map('intval', $meta), static function ($id) {
                return $id > 0;
            }));
        }
    }

    return array_unique($ids);
}

/**
 * Retorna IDs de eventos visualizados pelo usuário (com base na tabela interna).
 */
function apollo_events_get_user_viewed_event_ids(int $user_id): array {
    if ($user_id <= 0) {
        return array();
    }

    global $wpdb;
    $table = apollo_events_stats_table_name();
    $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));

    if ($exists !== $table) {
        return array();
    }

    $ids = $wpdb->get_col($wpdb->prepare(
        "SELECT event_id FROM {$table} WHERE user_id = %d AND views > 0",
        $user_id
    ));

    return array_values(array_filter(array_map('intval', (array) $ids), static function ($id) {
        return $id > 0;
    }));
}

/**
 * Atualiza/incrementa contadores de visualização.
 */
function apollo_record_event_view(int $event_id, ?int $user_id = null): void {
    if ($event_id <= 0) {
        return;
    }

    $current = (int) get_post_meta($event_id, '_apollo_event_views_total', true);
    update_post_meta($event_id, '_apollo_event_views_total', $current + 1);

    if ($user_id && $user_id > 0) {
        global $wpdb;
        $table = apollo_events_stats_table_name();
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));

        if ($exists === $table) {
            $is_coauthor = apollo_events_user_is_coauthor($user_id, $event_id) ? 1 : 0;
            $wpdb->query($wpdb->prepare(
                "INSERT INTO {$table} (user_id, event_id, views, favorited, is_coauthor, last_interaction)
                 VALUES (%d, %d, 1, 0, %d, %s)
                 ON DUPLICATE KEY UPDATE
                    views = views + 1,
                    is_coauthor = VALUES(is_coauthor),
                    last_interaction = VALUES(last_interaction)",
                $user_id,
                $event_id,
                $is_coauthor,
                current_time('mysql')
            ));
        }
    }

    /**
     * Hook interno para outras integrações.
     */
    do_action('apollo_event_view_recorded', $event_id, $user_id);
}

/**
 * Marca/desmarca favorito para refletir na tabela interna.
 */
function apollo_events_record_favorite_change(int $event_id, int $user_id, bool $is_favorited): void {
    if ($event_id <= 0 || $user_id <= 0) {
        return;
    }

    global $wpdb;
    $table = apollo_events_stats_table_name();
    $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));

    if ($exists !== $table) {
        return;
    }

    $wpdb->query($wpdb->prepare(
        "INSERT INTO {$table} (user_id, event_id, views, favorited, is_coauthor, last_interaction)
         VALUES (%d, %d, 0, %d, %d, %s)
         ON DUPLICATE KEY UPDATE
            favorited = VALUES(favorited),
            is_coauthor = VALUES(is_coauthor),
            last_interaction = VALUES(last_interaction)",
        $user_id,
        $event_id,
        $is_favorited ? 1 : 0,
        apollo_events_user_is_coauthor($user_id, $event_id) ? 1 : 0,
        current_time('mysql')
    ));

    do_action('apollo_event_favorite_recorded', $event_id, $user_id, $is_favorited);
}

/**
 * Helper para obter rótulo amigável de local (meta ou CPT relacionado).
 */
function apollo_events_get_event_location_label(int $event_id): string {
    $local_id = (int) get_post_meta($event_id, '_event_local_ids', true);

    if ($local_id > 0) {
        $local_post = get_post($local_id);
        if ($local_post && $local_post->post_status === 'publish') {
            $name = get_post_meta($local_id, '_local_name', true);
            if (!empty($name)) {
                return $name;
            }
            return $local_post->post_title;
        }
    }

    $raw_location = get_post_meta($event_id, '_event_location', true);
    return $raw_location ? (string) $raw_location : __('Não informado', 'apollo-events-manager');
}

/**
 * KPIs globais resumidos.
 */
function apollo_events_analytics_get_global_kpis(): array {
    $config = apollo_cfg();
    $post_type = isset($config['cpt']['event']) ? $config['cpt']['event'] : 'event_listing';

    $today = date('Y-m-d');

    $total_events = wp_count_posts($post_type);
    $total_published = $total_events ? (int) $total_events->publish : 0;

    $future_events = get_posts(array(
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'meta_key'       => '_event_start_date',
        'meta_value'     => $today,
        'meta_compare'   => '>=',
        'meta_type'      => 'DATE',
        'fields'         => 'ids',
        'posts_per_page' => -1,
        'no_found_rows'  => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ));

    $past_events = get_posts(array(
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'meta_key'       => '_event_start_date',
        'meta_value'     => $today,
        'meta_compare'   => '<',
        'meta_type'      => 'DATE',
        'fields'         => 'ids',
        'posts_per_page' => -1,
        'no_found_rows'  => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ));

    global $wpdb;
    $total_views = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = %s",
        '_apollo_event_views_total'
    ));

    return array(
        'total_events'   => $total_published,
        'future_events'  => is_array($future_events) ? count($future_events) : 0,
        'past_events'    => is_array($past_events) ? count($past_events) : 0,
        'total_views'    => $total_views,
    );
}

/**
 * Top N eventos por views.
 */
function apollo_events_analytics_get_top_events(int $limit = 10): array {
    $config = apollo_cfg();
    $post_type = isset($config['cpt']['event']) ? $config['cpt']['event'] : 'event_listing';

    $events = get_posts(array(
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'meta_key'       => '_apollo_event_views_total',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ));

    $data = array();

    foreach ((array) $events as $event_id) {
        $data[] = array(
            'event_id' => (int) $event_id,
            'title'    => get_the_title($event_id),
            'views'    => (int) get_post_meta($event_id, '_apollo_event_views_total', true),
            'edit_url' => get_edit_post_link($event_id, ''),
        );
    }

    return $data;
}

/**
 * Top termos de uma taxonomia (por contagem de posts).
 */
function apollo_events_analytics_get_top_terms(string $taxonomy, int $limit = 10): array {
    $terms = get_terms(array(
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'number'     => $limit,
        'orderby'    => 'count',
        'order'      => 'DESC',
    ));

    if (is_wp_error($terms) || empty($terms)) {
        return array();
    }

    return array_map(static function ($term) {
        return array(
            'term_id' => (int) $term->term_id,
            'name'    => $term->name,
            'slug'    => $term->slug,
            'count'   => (int) $term->count,
            'edit'    => get_edit_term_link($term, $term->taxonomy, 'event_listing'),
        );
    }, $terms);
}

/**
 * Top locais (baseado no metadado `_event_location`).
 */
function apollo_events_analytics_get_top_locations(int $limit = 10): array {
    global $wpdb;

    $meta_key = '_event_location';
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT meta_value AS location, COUNT(*) AS total
             FROM {$wpdb->postmeta}
             WHERE meta_key = %s AND meta_value <> ''
             GROUP BY meta_value
             ORDER BY total DESC
             LIMIT %d",
            $meta_key,
            $limit
        ),
        ARRAY_A
    );

    if (!$results) {
        return array();
    }

    return array_map(static function ($row) {
        return array(
            'location' => $row['location'],
            'count'    => (int) $row['total'],
        );
    }, $results);
}

/**
 * Calcula distribuição percentual para uma lista [label => contagem].
 */
function apollo_events_analytics_normalize_distribution(array $input): array {
    $total = array_sum($input);
    if ($total <= 0) {
        return array();
    }

    $output = array();
    foreach ($input as $label => $count) {
        $output[] = array(
            'label' => $label,
            'count' => (int) $count,
            'percent' => round(($count / $total) * 100, 2),
        );
    }

    usort($output, static function ($a, $b) {
        return $b['count'] <=> $a['count'];
    });

    return $output;
}

/**
 * Retorna overview consolidado para um usuário.
 */
function apollo_events_analytics_get_user_overview(int $user_id): array {
    $user_id = max(0, $user_id);

    $coauthored = apollo_events_get_user_coauthored_event_ids($user_id);
    $favorites = apollo_events_get_user_favorite_event_ids($user_id);
    $viewed    = apollo_events_get_user_viewed_event_ids($user_id);

    $sound_counts = array();
    $location_counts = array();

    $sound_event_ids = array_unique(array_merge($coauthored, $favorites));
    foreach ($sound_event_ids as $event_id) {
        $terms = wp_get_post_terms($event_id, 'event_sounds');
        if (is_wp_error($terms)) {
            continue;
        }
        foreach ($terms as $term) {
            $sound_counts[$term->name] = ($sound_counts[$term->name] ?? 0) + 1;
        }
    }

    $location_event_ids = array_unique(array_merge($coauthored, $favorites, $viewed));
    foreach ($location_event_ids as $event_id) {
        $label = apollo_events_get_event_location_label($event_id);
        if (!$label) {
            $label = __('Não informado', 'apollo-events-manager');
        }
        $location_counts[$label] = ($location_counts[$label] ?? 0) + 1;
    }

    return array(
        'user_id'        => $user_id,
        'coauthored_ids' => $coauthored,
        'favorites_ids'  => $favorites,
        'viewed_ids'     => $viewed,
        'coauthor_total' => count($coauthored),
        'favorites_total'=> count($favorites),
        'sounds'         => apollo_events_analytics_normalize_distribution($sound_counts),
        'locations'      => apollo_events_analytics_normalize_distribution($location_counts),
    );
}

/**
 * Shortcode: [apollo_event_user_overview]
 * Mostra resumo simples para o usuário autenticado no front-end.
 */
function apollo_event_user_overview_shortcode($atts, $content = ''): string {
    if (!is_user_logged_in()) {
        return '<div class="apollo-user-overview apollo-user-overview--guest">' . esc_html__('Disponível apenas para usuários autenticados.', 'apollo-events-manager') . '</div>';
    }

    $user_id  = get_current_user_id();
    $overview = apollo_events_analytics_get_user_overview($user_id);

    ob_start();
    ?>
    <div class="apollo-user-overview">
        <div class="apollo-user-overview__metrics">
            <div class="apollo-user-overview__metric">
                <span class="apollo-user-overview__value"><?php echo esc_html(number_format_i18n((int) $overview['coauthor_total'])); ?></span>
                <span class="apollo-user-overview__label"><?php esc_html_e('Eventos coautor', 'apollo-events-manager'); ?></span>
            </div>
            <div class="apollo-user-overview__metric">
                <span class="apollo-user-overview__value"><?php echo esc_html(number_format_i18n((int) $overview['favorites_total'])); ?></span>
                <span class="apollo-user-overview__label"><?php esc_html_e('Eventos de interesse', 'apollo-events-manager'); ?></span>
            </div>
            <div class="apollo-user-overview__metric">
                <span class="apollo-user-overview__value"><?php echo esc_html(number_format_i18n(count($overview['viewed_ids']))); ?></span>
                <span class="apollo-user-overview__label"><?php esc_html_e('Eventos visualizados', 'apollo-events-manager'); ?></span>
            </div>
        </div>

        <div class="apollo-user-overview__lists">
            <div class="apollo-user-overview__list">
                <h4><?php esc_html_e('Sons mais frequentes', 'apollo-events-manager'); ?></h4>
                <?php if (!empty($overview['sounds'])): ?>
                    <ul>
                        <?php foreach (array_slice($overview['sounds'], 0, 5) as $row): ?>
                            <li>
                                <span class="apollo-user-overview__list-label"><?php echo esc_html($row['label']); ?></span>
                                <span class="apollo-user-overview__list-meta"><?php echo esc_html(number_format_i18n((int) $row['count'])); ?> · <?php echo esc_html(number_format_i18n((float) $row['percent'], 2)); ?>%</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p><?php esc_html_e('Ainda não há dados suficientes.', 'apollo-events-manager'); ?></p>
                <?php endif; ?>
            </div>

            <div class="apollo-user-overview__list">
                <h4><?php esc_html_e('Locais mais frequentes', 'apollo-events-manager'); ?></h4>
                <?php if (!empty($overview['locations'])): ?>
                    <ul>
                        <?php foreach (array_slice($overview['locations'], 0, 5) as $row): ?>
                            <li>
                                <span class="apollo-user-overview__list-label"><?php echo esc_html($row['label']); ?></span>
                                <span class="apollo-user-overview__list-meta"><?php echo esc_html(number_format_i18n((int) $row['count'])); ?> · <?php echo esc_html(number_format_i18n((float) $row['percent'], 2)); ?>%</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p><?php esc_html_e('Ainda não há dados suficientes.', 'apollo-events-manager'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php

    return trim(ob_get_clean());
}

/**
 * Wrapper auxiliar para saber se estamos em contexto de eventos (para Plausible e scripts).
 */
function apollo_events_is_event_context(): bool {
    $config = apollo_cfg();
    if (!is_array($config) || empty($config['cpt']['event'])) {
        return false;
    }

    $event_post_type = $config['cpt']['event'];

    if (is_page('eventos') || is_post_type_archive($event_post_type) || is_singular($event_post_type)) {
        return true;
    }

    global $post;
    if (isset($post) && is_object($post)) {
        if (has_shortcode($post->post_content, 'apollo_events') || has_shortcode($post->post_content, 'eventos-page')) {
            return true;
        }
    }

    return false;
}

