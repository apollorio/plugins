<?php
/**
 * Apollo Events Manager - Dashboards & Admin Pages
 */

defined('ABSPATH') || exit;

/**
 * Registra menu/suplementos no admin.
 */
function apollo_events_register_analytics_admin_pages(): void {
    $parent_slug = 'apollo-events';

    // Se o menu principal ainda não existe, criamos um hub neutro.
    if (!isset($GLOBALS['admin_page_hooks'][$parent_slug])) {
        add_menu_page(
            __('Apollo Events', 'apollo-events-manager'),
            __('Apollo Events', 'apollo-events-manager'),
            'manage_options',
            $parent_slug,
            'apollo_events_render_admin_hub_page',
            'dashicons-calendar-alt',
            56
        );
    }

    add_submenu_page(
        $parent_slug,
        __('Dashboard', 'apollo-events-manager'),
        __('Dashboard', 'apollo-events-manager'),
        'view_apollo_event_stats',
        'apollo-events-dashboard',
        'apollo_events_render_dashboard_page',
        5
    );

    add_submenu_page(
        $parent_slug,
        __('User Overview', 'apollo-events-manager'),
        __('User Overview', 'apollo-events-manager'),
        'view_apollo_event_stats',
        'apollo-events-user-overview',
        'apollo_events_render_user_overview_page',
        6
    );
}
add_action('admin_menu', 'apollo_events_register_analytics_admin_pages');

/**
 * Hub simples para o menu principal (apenas mensagem informativa).
 */
function apollo_events_render_admin_hub_page(): void {
    if (!current_user_can('manage_options')) {
        wp_die(__('Acesso negado.', 'apollo-events-manager'));
    }

    echo '<div class="wrap"><h1>' . esc_html__('Apollo Events', 'apollo-events-manager') . '</h1>';
    echo '<p>' . esc_html__('Bem-vindo ao painel Apollo Events. Utilize os submenus para acessar recursos disponíveis.', 'apollo-events-manager') . '</p></div>';
}

/**
 * Renderiza dashboard global de analytics.
 */
function apollo_events_render_dashboard_page(): void {
    if (!current_user_can('view_apollo_event_stats')) {
        wp_die(__('Você não possui permissão para visualizar estas estatísticas.', 'apollo-events-manager'));
    }

    $kpis        = apollo_events_analytics_get_global_kpis();
    $top_events  = apollo_events_analytics_get_top_events(10);
    $top_sounds  = apollo_events_analytics_get_top_terms('event_sounds', 10);
    $top_locales = apollo_events_analytics_get_top_locations(10);

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Apollo Events – Dashboard', 'apollo-events-manager') . '</h1>';

    echo '<div class="apollo-kpis" style="display:flex;flex-wrap:wrap;gap:20px;margin:20px 0;">';

    $kpi_cards = array(
        array('label' => __('Eventos publicados', 'apollo-events-manager'), 'value' => $kpis['total_events'] ?? 0),
        array('label' => __('Eventos futuros', 'apollo-events-manager'), 'value' => $kpis['future_events'] ?? 0),
        array('label' => __('Eventos passados', 'apollo-events-manager'), 'value' => $kpis['past_events'] ?? 0),
        array('label' => __('Views registradas', 'apollo-events-manager'), 'value' => $kpis['total_views'] ?? 0),
    );

    foreach ($kpi_cards as $card) {
        echo '<div class="apollo-kpi-card" style="flex:1 1 220px;background:#fff;border:1px solid #ccd0d4;border-radius:6px;padding:16px;">';
        echo '<span style="display:block;font-size:32px;font-weight:700;">' . esc_html(number_format_i18n((int) $card['value'])) . '</span>';
        echo '<small style="text-transform:uppercase;letter-spacing:0.05em;color:#555;">' . esc_html($card['label']) . '</small>';
        echo '</div>';
    }

    echo '</div>';

    // Top eventos
    echo '<h2>' . esc_html__('Top eventos por views', 'apollo-events-manager') . '</h2>';
    if (!empty($top_events)) {
        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html__('Evento', 'apollo-events-manager') . '</th>';
        echo '<th style="width:120px;">' . esc_html__('Views', 'apollo-events-manager') . '</th>';
        echo '<th style="width:120px;">' . esc_html__('Ações', 'apollo-events-manager') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($top_events as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row['title'] ?: sprintf(__('ID %d (sem título)', 'apollo-events-manager'), $row['event_id'])) . '</td>';
            echo '<td>' . esc_html(number_format_i18n((int) $row['views'])) . '</td>';
            $edit_link = $row['edit_url'] ? esc_url($row['edit_url']) : '';
            echo '<td>' . ($edit_link ? '<a href="' . $edit_link . '" class="button button-small">' . esc_html__('Editar', 'apollo-events-manager') . '</a>' : '&mdash;') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    } else {
        echo '<p>' . esc_html__('Nenhum dado de views registrado ainda.', 'apollo-events-manager') . '</p>';
    }

    // Top sons
    echo '<h2 style="margin-top:40px;">' . esc_html__('Top sons (event_sounds)', 'apollo-events-manager') . '</h2>';
    if (!empty($top_sounds)) {
        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html__('Som', 'apollo-events-manager') . '</th>';
        echo '<th style="width:120px;">' . esc_html__('Eventos', 'apollo-events-manager') . '</th>';
        echo '<th style="width:120px;">' . esc_html__('Ações', 'apollo-events-manager') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($top_sounds as $term) {
            echo '<tr>';
            echo '<td>' . esc_html($term['name']) . '</td>';
            echo '<td>' . esc_html(number_format_i18n($term['count'])) . '</td>';
            $edit_link = !empty($term['edit']) ? esc_url($term['edit']) : '';
            echo '<td>' . ($edit_link ? '<a href="' . $edit_link . '" class="button button-small">' . esc_html__('Editar termo', 'apollo-events-manager') . '</a>' : '&mdash;') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    } else {
        echo '<p>' . esc_html__('Nenhum termo encontrado para a taxonomia event_sounds.', 'apollo-events-manager') . '</p>';
    }

    // Top locais
    echo '<h2 style="margin-top:40px;">' . esc_html__('Top locais', 'apollo-events-manager') . '</h2>';
    if (!empty($top_locales)) {
        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html__('Local', 'apollo-events-manager') . '</th>';
        echo '<th style="width:120px;">' . esc_html__('Eventos', 'apollo-events-manager') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($top_locales as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row['location']) . '</td>';
            echo '<td>' . esc_html(number_format_i18n($row['count'])) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    } else {
        echo '<p>' . esc_html__('Nenhum local encontrado.', 'apollo-events-manager') . '</p>';
    }

    echo '</div>';
}

/**
 * Renderiza overview detalhado por usuário.
 */
function apollo_events_render_user_overview_page(): void {
    if (!current_user_can('view_apollo_event_stats')) {
        wp_die(__('Você não possui permissão para visualizar estas estatísticas.', 'apollo-events-manager'));
    }

    $requested_id   = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;
    $identifier_raw = isset($_GET['user_identifier']) ? sanitize_text_field(wp_unslash($_GET['user_identifier'])) : '';

    $target_user = null;

    if ($requested_id > 0) {
        $target_user = get_user_by('id', $requested_id);
    } elseif ($identifier_raw !== '') {
        if (is_email($identifier_raw)) {
            $target_user = get_user_by('email', $identifier_raw);
        }
        if (!$target_user) {
            $target_user = get_user_by('login', $identifier_raw);
        }
        if (!$target_user && is_numeric($identifier_raw)) {
            $target_user = get_user_by('id', absint($identifier_raw));
        }
    }

    if (!$target_user) {
        $current_id = get_current_user_id();
        if ($current_id) {
            $target_user = get_user_by('id', $current_id);
        }
    }

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Visão por usuário', 'apollo-events-manager') . '</h1>';

    echo '<form method="get" style="margin:20px 0;display:flex;flex-wrap:wrap;gap:10px;align-items:center;">';
    echo '<input type="hidden" name="page" value="apollo-events-user-overview" />';

    // Select de usuários (limite 100 para evitar sobrecarga)
    $dropdown_args = array(
        'name'              => 'user_id',
        'selected'          => $target_user ? $target_user->ID : 0,
        'show_option_none'  => __('— Selecionar usuário —', 'apollo-events-manager'),
        'number'            => 100,
        'orderby'           => 'display_name',
        'class'             => 'regular-text',
    );
    wp_dropdown_users($dropdown_args);

    echo '<span>' . esc_html__('ou', 'apollo-events-manager') . '</span>';
    echo '<input type="text" name="user_identifier" class="regular-text" placeholder="ID / login / email" value="' . esc_attr($identifier_raw) . '" />';
    submit_button(__('Filtrar', 'apollo-events-manager'), 'secondary', '', false);
    echo '</form>';

    if (!$target_user) {
        echo '<p>' . esc_html__('Nenhum usuário encontrado.', 'apollo-events-manager') . '</p></div>';
        return;
    }

    $overview = apollo_events_analytics_get_user_overview($target_user->ID);

    echo '<h2>' . esc_html($target_user->display_name . ' (ID ' . $target_user->ID . ')') . '</h2>';

    echo '<div class="apollo-user-metrics" style="display:flex;flex-wrap:wrap;gap:20px;margin:20px 0;">';
    $user_metrics = array(
        array('label' => __('Eventos coautor', 'apollo-events-manager'), 'value' => $overview['coauthor_total']),
        array('label' => __('Eventos de interesse', 'apollo-events-manager'), 'value' => $overview['favorites_total']),
        array('label' => __('Eventos visualizados', 'apollo-events-manager'), 'value' => count($overview['viewed_ids'])),
    );

    foreach ($user_metrics as $metric) {
        echo '<div style="flex:1 1 220px;background:#fff;border:1px solid #ccd0d4;border-radius:6px;padding:16px;">';
        echo '<span style="display:block;font-size:28px;font-weight:700;">' . esc_html(number_format_i18n((int) $metric['value'])) . '</span>';
        echo '<small style="text-transform:uppercase;letter-spacing:0.05em;color:#555;">' . esc_html($metric['label']) . '</small>';
        echo '</div>';
    }
    echo '</div>';

    // Distribuição de sons
    echo '<h3>' . esc_html__('Distribuição de sons (coautor + interesse)', 'apollo-events-manager') . '</h3>';
    if (!empty($overview['sounds'])) {
        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html__('Som', 'apollo-events-manager') . '</th>';
        echo '<th style="width:140px;">' . esc_html__('Participações', 'apollo-events-manager') . '</th>';
        echo '<th style="width:200px;">' . esc_html__('%', 'apollo-events-manager') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($overview['sounds'] as $row) {
            $percent = min(100, max(0, (float) $row['percent']));
            echo '<tr>';
            echo '<td>' . esc_html($row['label']) . '</td>';
            echo '<td>' . esc_html(number_format_i18n((int) $row['count'])) . '</td>';
            echo '<td>';
            echo '<div style="background:#f1f1f1;border-radius:999px;overflow:hidden;height:12px;">';
            echo '<div style="background:#0073aa;height:100%;width:' . esc_attr($percent) . '%;"></div>';
            echo '</div>';
            echo '<small style="display:block;margin-top:4px;">' . esc_html(number_format_i18n($percent, 2)) . '%</small>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    } else {
        echo '<p>' . esc_html__('Sem interações suficientes para sons.', 'apollo-events-manager') . '</p>';
    }

    // Distribuição de locais
    echo '<h3 style="margin-top:30px;">' . esc_html__('Distribuição de locais (views + interesse + coautor)', 'apollo-events-manager') . '</h3>';
    if (!empty($overview['locations'])) {
        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html__('Local', 'apollo-events-manager') . '</th>';
        echo '<th style="width:140px;">' . esc_html__('Interações', 'apollo-events-manager') . '</th>';
        echo '<th style="width:200px;">' . esc_html__('%', 'apollo-events-manager') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($overview['locations'] as $row) {
            $percent = min(100, max(0, (float) $row['percent']));
            echo '<tr>';
            echo '<td>' . esc_html($row['label']) . '</td>';
            echo '<td>' . esc_html(number_format_i18n((int) $row['count'])) . '</td>';
            echo '<td>';
            echo '<div style="background:#f1f1f1;border-radius:999px;overflow:hidden;height:12px;">';
            echo '<div style="background:#46b450;height:100%;width:' . esc_attr($percent) . '%;"></div>';
            echo '</div>';
            echo '<small style="display:block;margin-top:4px;">' . esc_html(number_format_i18n($percent, 2)) . '%</small>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    } else {
        echo '<p>' . esc_html__('Sem interações suficientes para locais.', 'apollo-events-manager') . '</p>';
    }

    // Listas de eventos
    echo '<div style="display:flex;flex-wrap:wrap;gap:20px;margin-top:30px;">';

    $sections = array(
        array('title' => __('Eventos coautor', 'apollo-events-manager'), 'ids' => $overview['coauthored_ids'], 'color' => '#0073aa'),
        array('title' => __('Eventos de interesse', 'apollo-events-manager'), 'ids' => $overview['favorites_ids'], 'color' => '#d54e21'),
        array('title' => __('Eventos visualizados', 'apollo-events-manager'), 'ids' => $overview['viewed_ids'], 'color' => '#46b450'),
    );

    foreach ($sections as $section) {
        echo '<div style="flex:1 1 260px;background:#fff;border:1px solid #ccd0d4;border-radius:6px;padding:16px;">';
        echo '<h4 style="margin-top:0;border-left:4px solid ' . esc_attr($section['color']) . ';padding-left:8px;">' . esc_html($section['title']) . '</h4>';

        if (!empty($section['ids'])) {
            echo '<ul style="margin:0;padding-left:16px;">';
            foreach ($section['ids'] as $event_id) {
                $title = get_the_title($event_id);
                $edit  = get_edit_post_link($event_id, '');
                echo '<li>' . esc_html($title ?: sprintf(__('Evento %d', 'apollo-events-manager'), $event_id));
                if ($edit) {
                    echo ' – <a href="' . esc_url($edit) . '">' . esc_html__('Editar', 'apollo-events-manager') . '</a>';
                }
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>' . esc_html__('Nenhum evento encontrado.', 'apollo-events-manager') . '</p>';
        }

        echo '</div>';
    }

    echo '</div>';

    echo '</div>'; // wrap
}

