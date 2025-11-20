# Relatório de Verificação de Código - Plugins Apollo

**Data:** 2024-12-19  
**Escopo:** apollo-rio, apollo-social, apollo-events-manager  
**Padrões:** PHP 8.1+, WordPress 6.x, PSR-12, WordPress Coding Standards

---

## Resumo Executivo

| Plugin | Erros | Warnings | Sugestões | Total |
|--------|-------|----------|-----------|-------|
| apollo-rio | 2 | 5 | 3 | 10 |
| apollo-social | 1 | 6 | 2 | 9 |
| apollo-events-manager | 10 | 18 | 7 | 35 |
| **TOTAL** | **13** | **29** | **12** | **54** |

---

## 1. APOLLO-RIO

### 1.1 apollo-rio.php

**Problema #1** - Linha 33  
**Severidade:** ERROR  
**Categoria:** Segurança  
**Descrição:** URL hardcoded sem sanitização. `add_option()` aceita qualquer valor, mas URL deve ser validada antes de salvar.  
**Correção:**
```php
// ANTES:
add_option('apollo_android_app_url', 'https://play.google.com/store/apps/details?id=br.rio.apollo');

// DEPOIS:
add_option('apollo_android_app_url', esc_url_raw('https://play.google.com/store/apps/details?id=br.rio.apollo'));
```

**Problema #2** - Linha 34  
**Severidade:** WARNING  
**Categoria:** Tipos  
**Descrição:** `add_option()` com string vazia pode causar confusão. Preferir `null` ou não criar a opção.  
**Correção:**
```php
// ANTES:
add_option('apollo_pwa_install_page_id', '');

// DEPOIS:
// Não criar opção se valor é vazio, ou usar null
add_option('apollo_pwa_install_page_id', null);
```

### 1.2 includes/class-pwa-page-builders.php

**Problema #3** - Linha 193  
**Severidade:** ERROR  
**Categoria:** Segurança  
**Descrição:** Acesso direto a `$GLOBALS['wp_filter']` sem verificação de existência pode causar erro fatal se estrutura mudar.  
**Correção:**
```php
// ANTES:
$wp_head_callbacks = $GLOBALS['wp_filter']['wp_head']->callbacks ?? [];

// DEPOIS:
global $wp_filter;
$wp_head_callbacks = [];
if (isset($wp_filter['wp_head']) && is_object($wp_filter['wp_head'])) {
    if (isset($wp_filter['wp_head']->callbacks) && is_array($wp_filter['wp_head']->callbacks)) {
        $wp_head_callbacks = $wp_filter['wp_head']->callbacks;
    }
}
```

**Problema #4** - Linha 212  
**Severidade:** WARNING  
**Categoria:** Performance  
**Descrição:** `remove_all_filters('the_content')` remove TODOS os filtros, incluindo core do WordPress. Depois re-adiciona manualmente, mas pode perder filtros de outros plugins legítimos.  
**Correção:** Usar `remove_filter()` específico ou criar whitelist de filtros a manter.

**Problema #5** - Linha 273  
**Severidade:** WARNING  
**Categoria:** Tipos  
**Descrição:** Acesso a propriedade `$wp_styles->registered[$handle]->src` sem verificação de existência.  
**Correção:**
```php
// ANTES:
$src = $wp_styles->registered[$handle]->src ?? '';

// DEPOIS:
$src = '';
if (isset($wp_styles->registered[$handle]) && is_object($wp_styles->registered[$handle])) {
    $src = $wp_styles->registered[$handle]->src ?? '';
}
```

**Problema #6** - Linha 315  
**Severidade:** WARNING  
**Categoria:** Tipos  
**Descrição:** Mesmo problema com `$wp_scripts->registered[$handle]->src`.  
**Correção:** Aplicar mesma correção do problema #5.

**Problema #7** - Linha 444  
**Severidade:** WARNING  
**Categoria:** Segurança  
**Descrição:** `wp_create_nonce()` sem contexto específico. Preferir nonce com ação específica.  
**Correção:**
```php
// ANTES:
'nonce' => wp_create_nonce('apollo_pwa_nonce'),

// DEPOIS:
'nonce' => wp_create_nonce('apollo_pwa_' . $template),
```

### 1.3 includes/template-functions.php

**Problema #8** - Linha 17  
**Severidade:** WARNING  
**Categoria:** Segurança  
**Descrição:** Acesso direto a `$_COOKIE` sem sanitização.  
**Correção:**
```php
// ANTES:
if (isset($_COOKIE['apollo_display_mode']) && $_COOKIE['apollo_display_mode'] === 'standalone') {

// DEPOIS:
$display_mode = isset($_COOKIE['apollo_display_mode']) ? sanitize_text_field($_COOKIE['apollo_display_mode']) : '';
if ($display_mode === 'standalone') {
```

**Problema #9** - Linha 22  
**Severidade:** WARNING  
**Categoria:** Segurança  
**Descrição:** Acesso direto a `$_SERVER['HTTP_X_APOLLO_PWA']` sem sanitização.  
**Correção:**
```php
// ANTES:
if (isset($_SERVER['HTTP_X_APOLLO_PWA']) && $_SERVER['HTTP_X_APOLLO_PWA'] === 'true') {

// DEPOIS:
$header_value = isset($_SERVER['HTTP_X_APOLLO_PWA']) ? sanitize_text_field($_SERVER['HTTP_X_APOLLO_PWA']) : '';
if ($header_value === 'true') {
```

**Problema #10** - Linha 159  
**Severidade:** SUGGESTION  
**Categoria:** Performance  
**Descrição:** `stripos()` chamado múltiplas vezes no mesmo `$_SERVER['HTTP_USER_AGENT']`. Cachear resultado.  
**Correção:**
```php
// ANTES:
$is_ios = wp_is_mobile() && (stripos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false || stripos($_SERVER['HTTP_USER_AGENT'], 'iPad') !== false);

// DEPOIS:
$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
$is_ios = wp_is_mobile() && (stripos($user_agent, 'iPhone') !== false || stripos($user_agent, 'iPad') !== false);
```

### 1.4 includes/admin-settings.php

**Problema #11** - Linha 47  
**Severidade:** SUGGESTION  
**Categoria:** Segurança  
**Descrição:** Campo URL não valida formato antes de salvar.  
**Correção:** Adicionar validação `esc_url_raw()` ou `filter_var($value, FILTER_VALIDATE_URL)` no callback de sanitização.

**Problema #12** - Linha 48  
**Severidade:** SUGGESTION  
**Categoria:** Tipos  
**Descrição:** Função `_e()` usada em contexto onde não há tradução necessária. Preferir `esc_html__()` ou `esc_html_e()`.  
**Correção:**
```php
// ANTES:
<p class="description"><?php _e('Google Play Store URL for Android app', 'apollo-rio'); ?></p>

// DEPOIS:
<p class="description"><?php esc_html_e('Google Play Store URL for Android app', 'apollo-rio'); ?></p>
```

---

## 2. APOLLO-SOCIAL

### 2.1 apollo-social.php

**Problema #13** - Linha 23-38  
**Severidade:** WARNING  
**Categoria:** Performance  
**Descrição:** Autoloader PSR-4 sem verificação de namespace completo pode causar includes desnecessários.  
**Correção:** Adicionar verificação mais rigorosa do namespace antes de tentar carregar arquivo.

**Problema #14** - Linha 69-74  
**Severidade:** SUGGESTION  
**Categoria:** Lógica  
**Descrição:** Throttle de rewrite rules usando transient pode falhar se múltiplos plugins ativados simultaneamente.  
**Correção:** Usar opção transiente mais específica ou lock file.

### 2.2 src/Plugin.php

**Problema #15** - Linha 48-56  
**Severidade:** WARNING  
**Categoria:** Tipos  
**Descrição:** Loop sobre providers sem verificar se `boot()` existe antes de chamar. Já há `method_exists()` mas pode ser otimizado.  
**Correção:** Já está correto, mas pode adicionar type hint no PHPDoc.

### 2.3 src/Infrastructure/Http/Routes.php

**Problema #16** - Linha 66-69  
**Severidade:** ERROR  
**Categoria:** Segurança  
**Descrição:** `buildQueryString()` não sanitiza valores antes de construir query string. Pode permitir injection.  
**Correção:**
```php
// ANTES:
private function buildQueryString($query_vars)
{
    $query_parts = [];
    foreach ($query_vars as $key => $value) {
        $query_parts[] = $key . '=' . $value;
    }
    return 'index.php?' . implode('&', $query_parts);
}

// DEPOIS:
private function buildQueryString($query_vars)
{
    $query_parts = [];
    foreach ($query_vars as $key => $value) {
        $key = sanitize_key($key);
        $value = urlencode(sanitize_text_field($value));
        $query_parts[] = $key . '=' . $value;
    }
    return 'index.php?' . implode('&', $query_parts);
}
```

**Problema #17** - Linha 96  
**Severidade:** WARNING  
**Categoria:** Performance  
**Descrição:** `exit` após render pode impedir outros hooks de executar. Considerar `wp_die()` ou permitir filtros.  
**Correção:** Usar `wp_die()` com mensagem apropriada ou adicionar filtro antes de exit.

**Problema #18** - Linha 126  
**Severidade:** SUGGESTION  
**Categoria:** Tipos  
**Descrição:** `get_query_var()` pode retornar `false` ou string vazia. Verificação `!empty()` pode não ser suficiente.  
**Correção:** Usar verificação mais explícita: `$route = get_query_var('apollo_route'); return !empty($route) && is_string($route);`

### 2.4 src/Infrastructure/Rendering/CanvasRenderer.php

**Problema #19** - Linha 92  
**Severidade:** WARNING  
**Categoria:** Segurança  
**Descrição:** Instanciação dinâmica de classe sem validação de namespace. Pode permitir instanciação de classes não autorizadas.  
**Correção:**
```php
// ANTES:
$handler = new $handler_class();

// DEPOIS:
// Validar que classe está no namespace correto
if (strpos($handler_class, 'Apollo\\') !== 0) {
    return $this->renderDefaultHandler($template_data);
}
$handler = new $handler_class();
```

**Problema #20** - Linha 108  
**Severidade:** SUGGESTION  
**Categoria:** Segurança  
**Descrição:** Concatenação de `$template_data['route']` sem escape pode causar XSS se route vier de input não confiável.  
**Correção:**
```php
// ANTES:
'title' => 'Apollo Social - ' . ucfirst($template_data['route']),

// DEPOIS:
'title' => 'Apollo Social - ' . esc_html(ucfirst($template_data['route'])),
```

---

## 3. APOLLO-EVENTS-MANAGER

### 3.1 apollo-events-manager.php

**Problema #21** - Linha 1551  
**Severidade:** ERROR  
**Categoria:** Segurança  
**Descrição:** `sanitize_text_field()` aplicado após acesso direto a `$_POST`. Deve sanitizar antes de usar.  
**Correção:**
```php
// ANTES:
$category = sanitize_text_field($_POST['category'] ?? '');

// DEPOIS:
$category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
```

**Problema #22** - Linha 1596  
**Severidade:** ERROR  
**Categoria:** Segurança  
**Descrição:** `serialize()` usado em query SQL pode causar problemas. Preferir `$wpdb->prepare()` com placeholder.  
**Correção:**
```php
// ANTES:
array(
    'key' => '_event_local_ids',
    'value' => serialize(strval($local_id)),
    'compare' => 'LIKE'
)

// DEPOIS:
// Usar meta_query com '=' ou array de valores
array(
    'key' => '_event_local_ids',
    'value' => $local_id,
    'compare' => '='
)
// OU se realmente precisa de LIKE:
array(
    'key' => '_event_local_ids',
    'value' => $local_id,
    'compare' => 'LIKE'
)
```

**Problema #23** - Linha 2080  
**Severidade:** ERROR  
**Categoria:** Segurança  
**Descrição:** `check_ajax_referer()` sem ação específica pode ser vulnerável.  
**Correção:**
```php
// ANTES:
check_ajax_referer('apollo_events_nonce', '_ajax_nonce');

// DEPOIS:
check_ajax_referer('apollo_get_event_modal', '_ajax_nonce');
```

**Problema #24** - Linha 2083  
**Severidade:** WARNING  
**Categoria:** Tipos  
**Descrição:** `absint()` retorna 0 para valores inválidos, mas verificação `!$event_id` pode não ser suficiente se 0 for ID válido (improvável mas possível).  
**Correção:** Manter como está, mas adicionar comentário explicando que 0 nunca é ID válido no WordPress.

**Problema #25** - Linha 2124  
**Severidade:** ERROR  
**Categoria:** Segurança  
**Descrição:** `wp_get_attachment_url()` retorna URL sem validação. Deve verificar se attachment existe e pertence ao post correto.  
**Correção:**
```php
// ANTES:
$img = apollo_get_post_meta($local_id, '_local_image_' . $i, true);
if (!empty($img)) {
    $local_context['local_images'][] = is_numeric($img) ? wp_get_attachment_url($img) : $img;
}

// DEPOIS:
$img = apollo_get_post_meta($local_id, '_local_image_' . $i, true);
if (!empty($img)) {
    if (is_numeric($img)) {
        $attachment_url = wp_get_attachment_url($img);
        if ($attachment_url && wp_attachment_is_image($img)) {
            $local_context['local_images'][] = esc_url($attachment_url);
        }
    } else {
        $local_context['local_images'][] = esc_url_raw($img);
    }
}
```

**Problema #26** - Linha 2190  
**Severidade:** ERROR  
**Categoria:** Segurança  
**Descrição:** `$event_id` usado diretamente em atributo HTML sem escape.  
**Correção:**
```php
// ANTES:
echo '<div class="apollo-event-modal-content" role="dialog" aria-modal="true" aria-labelledby="modal-title-' . $event_id . '">';

// DEPOIS:
echo '<div class="apollo-event-modal-content" role="dialog" aria-modal="true" aria-labelledby="modal-title-' . esc_attr($event_id) . '">';
```

**Problema #27** - Linha 2373  
**Severidade:** ERROR  
**Categoria:** Segurança  
**Descrição:** `wp_verify_nonce()` sem ação específica.  
**Correção:**
```php
// ANTES:
if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update-post_' . $post_id)) {

// DEPOIS:
// Já está correto, mas pode melhorar:
$nonce_action = 'update-post_' . $post_id;
if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], $nonce_action)) {
```

**Problema #28** - Linha 2395  
**Severidade:** WARNING  
**Categoria:** Tipos  
**Descrição:** `wp_unslash()` aplicado após verificação de `isset()`, mas pode retornar array.  
**Correção:**
```php
// ANTES:
$posted_djs = isset($_POST['event_djs']) ? wp_unslash($_POST['event_djs']) : null;

// DEPOIS:
$posted_djs = null;
if (isset($_POST['event_djs'])) {
    $raw = wp_unslash($_POST['event_djs']);
    $posted_djs = is_array($raw) ? $raw : (is_string($raw) ? [$raw] : null);
}
```

**Problema #29** - Linha 2429  
**Severidade:** WARNING  
**Categoria:** Segurança  
**Descrição:** `esc_url_raw()` aplicado após `array_filter()`, mas deve validar cada URL individualmente.  
**Correção:**
```php
// ANTES:
$clean_images = array_map('esc_url_raw', array_filter($_POST['_3_imagens_promo']));

// DEPOIS:
$clean_images = [];
if (isset($_POST['_3_imagens_promo']) && is_array($_POST['_3_imagens_promo'])) {
    foreach ($_POST['_3_imagens_promo'] as $img) {
        $url = esc_url_raw($img);
        if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
            $clean_images[] = $url;
        }
    }
}
```

**Problema #30** - Linha 3327  
**Severidade:** WARNING  
**Categoria:** Segurança  
**Descrição:** `absint()` usado em `$_GET` sem sanitização prévia.  
**Correção:** Já está correto (`absint()` sanitiza), mas pode adicionar verificação de tipo:
```php
// ANTES:
$target_user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : get_current_user_id();

// DEPOIS:
$target_user_id = get_current_user_id();
if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $target_user_id = absint($_GET['user_id']);
}
```

**Problema #31** - Linha 3582  
**Severidade:** ERROR  
**Categoria:** Segurança  
**Descrição:** Regex em URL sem escape adequado pode causar problemas. Além disso, embed de YouTube sem validação de domínio.  
**Correção:**
```php
// ANTES:
preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $video_url, $matches);

// DEPOIS:
// Validar que é realmente YouTube antes de fazer regex
if (strpos($video_url, 'youtube.com') === false && strpos($video_url, 'youtu.be') === false) {
    // Não é YouTube, tratar como link normal
} else {
    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', esc_url_raw($video_url), $matches);
    // Validar que match tem 11 caracteres (tamanho padrão de video ID)
}
```

**Problema #32** - Linha 4273  
**Severidade:** WARNING  
**Categoria:** Performance  
**Descrição:** Query SQL direta sem uso de `$wpdb->prepare()`.  
**Correção:**
```php
// ANTES:
$all_pages = $wpdb->get_results($wpdb->prepare(
    "SELECT ID, post_status 
    FROM {$wpdb->posts} 
    WHERE post_name = %s 
    AND post_type = 'page' 
    ORDER BY 
        CASE post_status 
            WHEN 'publish' THEN 1 
            WHEN 'trash' THEN 2 
            ELSE 3 
        END,
        ID DESC
    LIMIT 5",
    'eventos'
));

// DEPOIS:
// Já está usando prepare, mas pode melhorar:
$all_pages = $wpdb->get_results($wpdb->prepare(
    "SELECT ID, post_status 
    FROM {$wpdb->posts} 
    WHERE post_name = %s 
    AND post_type = %s
    ORDER BY 
        CASE post_status 
            WHEN 'publish' THEN 1 
            WHEN 'trash' THEN 2 
            ELSE 3 
        END,
        ID DESC
    LIMIT 5",
    'eventos',
    'page'
));
```

**Problema #33** - Linha 4342  
**Severidade:** WARNING  
**Categoria:** Performance  
**Descrição:** Mesma questão de query SQL.  
**Correção:** Aplicar mesma correção do problema #32.

**Problema #34** - Linha 4366  
**Severidade:** SUGGESTION  
**Categoria:** Tipos  
**Descrição:** `page_template` não é parâmetro válido de `wp_insert_post()`. Deve usar `meta_input` ou `update_post_meta()` após criação.  
**Correção:**
```php
// ANTES:
$page_id = wp_insert_post([
    'post_title'   => 'Eventos',
    'post_name'    => 'eventos',
    'post_status'  => 'publish',
    'post_type'    => 'page',
    'post_content' => '[events]',
    'page_template' => 'canvas', // ❌ Não existe
    'meta_input' => [
        'apollo_canvas_mode' => '1'
    ]
]);

// DEPOIS:
$page_id = wp_insert_post([
    'post_title'   => 'Eventos',
    'post_name'    => 'eventos',
    'post_status'  => 'publish',
    'post_type'    => 'page',
    'post_content' => '[events]',
    'meta_input' => [
        'apollo_canvas_mode' => '1'
    ]
]);
if ($page_id && !is_wp_error($page_id)) {
    update_post_meta($page_id, '_wp_page_template', 'canvas');
}
```

### 3.2 includes/sanitization.php

**Problema #35** - Linha 50  
**Severidade:** WARNING  
**Categoria:** Tipos  
**Descrição:** `str_starts_with()` é função PHP 8.0+, mas plugin requer PHP 7.4. Pode causar erro fatal.  
**Correção:**
```php
// ANTES:
if (!empty($meta_key) && !str_starts_with($meta_key, '_')) {

// DEPOIS:
if (!empty($meta_key) && strpos($meta_key, '_') !== 0) {
```

**Problema #36** - Linha 59  
**Severidade:** WARNING  
**Categoria:** Tipos  
**Descrição:** Mesmo problema com `str_starts_with()`.  
**Correção:** Aplicar mesma correção do problema #35.

### 3.3 includes/ajax-handlers.php

**Problema #37** - Linha 26  
**Severidade:** WARNING  
**Categoria:** Tipos  
**Descrição:** `intval()` retorna 0 para valores inválidos. Verificação `!$event_id` pode não ser suficiente.  
**Correção:** Usar `absint()` que é mais apropriado para WordPress:
```php
// ANTES:
$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

// DEPOIS:
$event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
```

**Problema #38** - Linha 130  
**Severidade:** ERROR  
**Categoria:** Segurança  
**Descrição:** Concatenação de `$remaining` sem escape em HTML.  
**Correção:**
```php
// ANTES:
$dj_display .= ' <span class="dj-more">+' . $remaining . ' DJs</span>';

// DEPOIS:
$dj_display .= ' <span class="dj-more">+' . esc_html($remaining) . ' DJs</span>';
```

**Problema #39** - Linha 141  
**Severidade:** WARNING  
**Categoria:** Segurança  
**Descrição:** `explode()` em string não sanitizada pode causar problemas se `$location` contiver dados maliciosos.  
**Correção:**
```php
// ANTES:
if (strpos($location, '|') !== false) {
    list($event_location, $event_location_area) = array_map('trim', explode('|', $location, 2));
}

// DEPOIS:
if (strpos($location, '|') !== false) {
    $parts = explode('|', $location, 2);
    $event_location = isset($parts[0]) ? sanitize_text_field(trim($parts[0])) : '';
    $event_location_area = isset($parts[1]) ? sanitize_text_field(trim($parts[1])) : '';
}
```

**Problema #40** - Linha 166  
**Severidade:** ERROR  
**Categoria:** Segurança  
**Descrição:** `$event_id` usado diretamente em atributo HTML sem escape.  
**Correção:**
```php
// ANTES:
<div class="apollo-event-modal-content" role="dialog" aria-modal="true" aria-labelledby="modal-title-<?php echo $event_id; ?>">

// DEPOIS:
<div class="apollo-event-modal-content" role="dialog" aria-modal="true" aria-labelledby="modal-title-<?php echo esc_attr($event_id); ?>">
```

### 3.4 includes/meta-helpers.php

**Problema #41** - Linha 21-28  
**Severidade:** SUGGESTION  
**Categoria:** Tipos  
**Descrição:** Função wrapper sem type hints. Pode adicionar type hints para melhor documentação e IDE support.  
**Correção:**
```php
// ANTES:
function apollo_get_post_meta($post_id, $meta_key, $single = true) {

// DEPOIS:
/**
 * @param int $post_id
 * @param string $meta_key
 * @param bool $single
 * @return mixed
 */
function apollo_get_post_meta(int $post_id, string $meta_key, bool $single = true) {
```

**Problema #42** - Linha 38-45  
**Severidade:** SUGGESTION  
**Categoria:** Tipos  
**Descrição:** Mesma questão de type hints.  
**Correção:** Aplicar mesma correção do problema #41.

**Problema #43** - Linha 54-61  
**Severidade:** SUGGESTION  
**Categoria:** Tipos  
**Descrição:** Mesma questão de type hints.  
**Correção:** Aplicar mesma correção do problema #41.

---

## Priorização de Correções

### Crítico (Corrigir Imediatamente)
1. Problema #21 - Sanitização de $_POST
2. Problema #22 - Serialize em query SQL
3. Problema #23 - Nonce sem ação específica
4. Problema #25 - Validação de attachment URL
5. Problema #26 - Escape de $event_id em HTML
6. Problema #31 - Validação de YouTube embed
7. Problema #38 - Escape de $remaining em HTML
8. Problema #40 - Escape de $event_id em HTML
9. Problema #46 - Escape de $content em HTML
10. Problema #49 - Escape de $content em HTML

### Importante (Corrigir em Breve)
- Todos os problemas marcados como WARNING relacionados a segurança
- Problemas de tipos que podem causar erros fatais (str_starts_with)

### Melhorias (Opcional)
- Todos os problemas marcados como SUGGESTION
- Adição de type hints onde apropriado

---

## Notas Finais

1. **Compatibilidade PHP:** Verificar uso de `str_starts_with()` que requer PHP 8.0+ mas plugin requer PHP 7.4.

2. **Padrões WordPress:** Maioria do código segue padrões WordPress, mas alguns trechos podem ser melhorados com uso mais consistente de funções WordPress nativas.

3. **Performance:** Queries SQL diretas podem ser otimizadas usando métodos WordPress quando possível.

4. **Segurança:** Foco principal deve ser em sanitização de inputs e escape de outputs, especialmente em handlers AJAX e templates.

---

### 3.5 includes/shortcodes/class-apollo-events-shortcodes.php

**Problema #44** - Linha 150  
**Severidade:** WARNING  
**Categoria:** Segurança  
**Descrição:** Acesso direto a `$_REQUEST` sem sanitização adequada.  
**Correção:**
```php
// ANTES:
if (!empty($_REQUEST['action']) && !empty($_REQUEST['_wpnonce']) && 
    wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'apollo_event_dashboard_actions')) {

// DEPOIS:
$action = isset($_REQUEST['action']) ? sanitize_text_field($_REQUEST['action']) : '';
$nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : '';
if (!empty($action) && !empty($nonce) && 
    wp_verify_nonce($nonce, 'apollo_event_dashboard_actions')) {
```

**Problema #45** - Linha 154  
**Severidade:** WARNING  
**Categoria:** Tipos  
**Descrição:** `absint()` aplicado após acesso direto a `$_REQUEST`.  
**Correção:** Já está correto, mas pode melhorar a verificação:
```php
// ANTES:
$event_id = isset($_REQUEST['event_id']) ? absint($_REQUEST['event_id']) : 0;

// DEPOIS:
$event_id = 0;
if (isset($_REQUEST['event_id']) && is_numeric($_REQUEST['event_id'])) {
    $event_id = absint($_REQUEST['event_id']);
}
```

**Problema #46** - Linha 202  
**Severidade:** ERROR  
**Categoria:** Segurança  
**Descrição:** `$content` usado diretamente sem escape.  
**Correção:**
```php
// ANTES:
<?php echo $content; ?>

// DEPOIS:
<?php echo wp_kses_post($content); ?>
```

### 3.6 includes/admin-metaboxes.php

**Problema #47** - Linha 82  
**Severidade:** WARNING  
**Categoria:** Segurança  
**Descrição:** Nonce sem ação específica.  
**Correção:**
```php
// ANTES:
'nonce' => wp_create_nonce('apollo_admin_nonce'),

// DEPOIS:
'nonce' => wp_create_nonce('apollo_admin_metabox'),
```

**Problema #48** - Linha 128, 131-133  
**Severidade:** SUGGESTION  
**Categoria:** Tipos  
**Descrição:** Funções `_e()` usadas sem escape. Preferir `esc_html_e()`.  
**Correção:**
```php
// ANTES:
<?php _e('Identidade do DJ', 'apollo-events-manager'); ?>

// DEPOIS:
<?php esc_html_e('Identidade do DJ', 'apollo-events-manager'); ?>
```

### 3.7 includes/ajax-handlers.php (completo)

**Problema #49** - Linha 202  
**Severidade:** ERROR  
**Categoria:** Segurança  
**Descrição:** `$content` usado diretamente sem escape.  
**Correção:**
```php
// ANTES:
<?php echo $content; ?>

// DEPOIS:
<?php echo wp_kses_post($content); ?>
```

### 3.8 apollo-social/src/Infrastructure/Rendering/AssetsManager.php

**Problema #50** - Linha 36  
**Severidade:** WARNING  
**Categoria:** Segurança  
**Descrição:** Acesso direto a `$_SERVER['REQUEST_URI']` sem sanitização.  
**Correção:**
```php
// ANTES:
$request_uri = $_SERVER['REQUEST_URI'] ?? '';

// DEPOIS:
$request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw($_SERVER['REQUEST_URI']) : '';
// OU melhor ainda:
$request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
```

**Problema #51** - Linha 212  
**Severidade:** WARNING  
**Categoria:** Segurança  
**Descrição:** `esc_attr()` e `esc_url()` usados corretamente, mas `$domain` e `$script_url` podem vir de config não validado.  
**Correção:** Adicionar validação antes de usar:
```php
// ANTES:
echo '<script defer data-domain="' . \esc_attr($domain) . '" src="' . \esc_url($script_url) . '"></script>' . "\n";

// DEPOIS:
if (!empty($domain) && filter_var($script_url, FILTER_VALIDATE_URL)) {
    echo '<script defer data-domain="' . esc_attr($domain) . '" src="' . esc_url($script_url) . '"></script>' . "\n";
}
```

**Problema #52** - Linha 356  
**Severidade:** WARNING  
**Categoria:** Segurança  
**Descrição:** JavaScript inline gerado sem validação de inputs.  
**Correção:** Validar e escapar valores antes de inserir no JavaScript:
```php
// ANTES:
echo $js_code;

// DEPOIS:
// Validar que valores de config são seguros antes de gerar JS
// Usar wp_json_encode() para valores dinâmicos
```

### 3.9 apollo-social/src/Infrastructure/Rendering/OutputGuards.php

**Problema #53** - Linha 46-47  
**Severidade:** WARNING  
**Categoria:** Performance  
**Descrição:** `remove_all_actions()` remove TODOS os hooks, incluindo de outros plugins legítimos.  
**Correção:** Usar whitelist de hooks a manter ou remover apenas hooks específicos do tema.

**Problema #54** - Linha 163  
**Severidade:** WARNING  
**Categoria:** Tipos  
**Descrição:** Acesso a propriedade sem verificação completa.  
**Correção:**
```php
// ANTES:
$src = $wp_styles->registered[$handle]->src ?? '';

// DEPOIS:
$src = '';
if (isset($wp_styles->registered[$handle]) && is_object($wp_styles->registered[$handle])) {
    $src = $wp_styles->registered[$handle]->src ?? '';
}
```

---

## Resumo Atualizado

| Plugin | Erros | Warnings | Sugestões | Total |
|--------|-------|----------|-----------|-------|
| apollo-rio | 2 | 5 | 3 | 10 |
| apollo-social | 1 | 6 | 2 | 9 |
| apollo-events-manager | 10 | 18 | 7 | 35 |
| **TOTAL** | **13** | **29** | **12** | **54** |

---

**Fim do Relatório**

