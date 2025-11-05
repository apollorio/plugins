<?php
/**
 * Integração leve com Plausible Analytics (client-side apenas).
 */

defined('ABSPATH') || exit;

/**
 * Obtém domínio configurado para Plausible.
 */
function apollo_events_get_plausible_domain(): string {
    $domain = defined('APOLLO_PLAUSIBLE_DOMAIN') ? (string) APOLLO_PLAUSIBLE_DOMAIN : '';
    /**
     * Permite ajustar o domínio via filtro.
     */
    return apply_filters('apollo_events_plausible_domain', $domain);
}

/**
 * Obtém URL do script do Plausible.
 */
function apollo_events_get_plausible_script_url(): string {
    $default = 'https://plausible.io/js/script.js';
    $script = defined('APOLLO_PLAUSIBLE_SCRIPT_URL') ? (string) APOLLO_PLAUSIBLE_SCRIPT_URL : $default;
    /**
     * Permite ajustar a URL do script via filtro.
     */
    return apply_filters('apollo_events_plausible_script_url', $script);
}

/**
 * Injeta o script do Plausible nas páginas relevantes.
 */
function apollo_events_maybe_output_plausible_script(): void {
    if (!function_exists('apollo_events_is_event_context') || !apollo_events_is_event_context()) {
        return;
    }

    $domain = trim(apollo_events_get_plausible_domain());
    $script = trim(apollo_events_get_plausible_script_url());

    if ($domain === '' || $script === '') {
        return;
    }

    /**
     * Permite desligar a saída do script via filtro (para páginas específicas, por exemplo).
     */
    $should_output = apply_filters('apollo_events_should_output_plausible', true);
    if (!$should_output) {
        return;
    }

    printf(
        '<script defer data-domain="%s" src="%s"></script>' . "\n",
        esc_attr($domain),
        esc_url($script)
    );
}
add_action('wp_head', 'apollo_events_maybe_output_plausible_script', 90);

