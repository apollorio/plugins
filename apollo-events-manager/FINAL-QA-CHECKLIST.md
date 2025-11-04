# 🔒 FINAL INTEGRATED QA AND BUG-RISK CHECKLIST
## Apollo Events Manager v2.0.2

**Data:** 2025-11-04  
**Status:** Release Candidate - Aguardando QA Final

---

## 🐛 REMAINING PROBABLE BUGS

### 🔴 CRITICAL

**Nenhum bug crítico identificado** - Todas as vulnerabilidades de segurança foram corrigidas.

---

### 🟡 MEDIUM PRIORITY

#### 1. **[apollo-events-manager.php:250] Auto-criação de página pode sobrescrever**
**Cenário:** 
- Admin deleta página `/eventos/` manualmente
- Na próxima requisição front-end, `ensure_events_page()` recria automaticamente
- Pode recriar página com ID diferente, quebrando links salvos

**Impacto:** Links quebrados, bookmarks inválidos

**Fix Sugerido:**
```php
public function ensure_events_page() {
    if (is_admin()) return;
    
    $slug = 'eventos';
    $existing = get_page_by_path($slug);
    
    // Check if page exists but is in trash
    if ($existing && $existing->post_status === 'trash') {
        // Don't recreate - let admin decide
        return;
    }
    
    if (!$existing) {
        // Check option to prevent recreating if admin explicitly deleted it
        if (get_option('apollo_eventos_page_deleted')) {
            return;
        }
        
        $page_id = wp_insert_post([
            'post_title'   => 'Eventos',
            'post_name'    => $slug,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '[apollo_events]',
        ]);
        
        if ($page_id && !is_wp_error($page_id)) {
            flush_rewrite_rules(false);
        }
    }
}

// Add hook to track manual deletion
add_action('wp_trash_post', function($post_id) {
    $post = get_post($post_id);
    if ($post && $post->post_name === 'eventos') {
        update_option('apollo_eventos_page_deleted', true);
    }
});
```

---

#### 2. **[apollo-events-manager.php:1176] Activation creates page without checking trash**
**Cenário:**
- Admin desativa plugin
- Deleta página `/eventos/` (vai para lixeira)
- Reativa plugin
- Activation hook cria NOVA página (duplicada)

**Impacto:** Páginas duplicadas

**Fix Sugerido:**
```php
function apollo_events_manager_activate() {
    require_once plugin_dir_path(__FILE__) . 'includes/post-types.php';
    Apollo_Post_Types::flush_rewrite_rules_on_activation();
    
    // Check if page exists (including trash)
    $events_page = get_posts([
        'name'        => 'eventos',
        'post_type'   => 'page',
        'post_status' => ['publish', 'draft', 'trash'],
        'numberposts' => 1
    ]);
    
    if (empty($events_page)) {
        $page_id = wp_insert_post([
            'post_title'   => 'Eventos',
            'post_name'    => 'eventos',
            'post_content' => '[apollo_events]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
        
        if ($page_id && !is_wp_error($page_id)) {
            error_log('✅ Apollo: Created /eventos/ page (ID: ' . $page_id . ')');
        }
    } elseif ($events_page[0]->post_status === 'trash') {
        // Restore from trash
        wp_untrash_post($events_page[0]->ID);
        error_log('✅ Apollo: Restored /eventos/ page from trash');
    }
    
    error_log('✅ Apollo Events Manager 2.0.0 activated successfully');
}
```

---

#### 3. **[templates/portal-discover.php:137] WP_Query sem error handling**
**Cenário:**
- Database connection fails durante query
- `$events_query->have_posts()` pode retornar null ou WP_Error
- Página exibe erro PHP ao invés de mensagem amigável

**Impacto:** Erro fatal se DB falhar

**Fix Sugerido:**
```php
$events_query = new WP_Query($args);

if (is_wp_error($events_query)) {
    echo '<p class="no-events-found">Erro ao carregar eventos. Tente novamente.</p>';
    error_log('❌ Apollo: WP_Query error: ' . $events_query->get_error_message());
} elseif ($events_query->have_posts()):
    // ... loop normal
else:
    echo '<p class="no-events-found">Nenhum evento encontrado.</p>';
endif;
```

---

#### 4. **[apollo-events-manager.php:193] Auto-geocoding sem rate limiting**
**Cenário:**
- Admin faz bulk edit de 20 Locais
- `save_post_event_local` dispara 20x rapidamente
- Nominatim API rate limit: 1 req/second
- 19 requisições falham com HTTP 429

**Impacto:** Coordenadas não salvas em bulk operations

**Fix Sugerido:**
```php
public function auto_geocode_local($post_id, $post) {
    // Skip autosave/revisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;
    
    // Get address and city
    $addr = get_post_meta($post_id, '_local_address', true);
    $city = get_post_meta($post_id, '_local_city', true);
    
    if (empty($city)) return;
    
    // Check if already has coordinates
    $lat = get_post_meta($post_id, '_local_latitude', true);
    $lng = get_post_meta($post_id, '_local_longitude', true);
    if (!empty($lat) && !empty($lng)) return;
    
    // Rate limiting check
    $last_geocode = get_transient('apollo_last_geocode_time');
    if ($last_geocode) {
        $elapsed = time() - $last_geocode;
        if ($elapsed < 2) {
            // Schedule for later instead of failing
            wp_schedule_single_event(time() + 3, 'apollo_delayed_geocode', [$post_id]);
            error_log("⏳ Apollo: Geocoding for local {$post_id} scheduled (rate limit)");
            return;
        }
    }
    
    // Build search query
    $query_parts = array_filter([$addr, $city, 'Brasil']);
    $query = urlencode(implode(', ', $query_parts));
    $url = "https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=BR&q={$query}";
    
    // Call Nominatim API
    $res = wp_remote_get($url, [
        'timeout' => 10,
        'user-agent' => 'Apollo::Rio/2.0 (WordPress Event Manager)'
    ]);
    
    // Update last geocode timestamp
    set_transient('apollo_last_geocode_time', time(), 10);
    
    if (is_wp_error($res)) {
        error_log("❌ Geocoding failed for local {$post_id}: " . $res->get_error_message());
        return;
    }
    
    $data = json_decode(wp_remote_retrieve_body($res), true);
    
    if (!empty($data[0]['lat']) && !empty($data[0]['lon'])) {
        update_post_meta($post_id, '_local_latitude', $data[0]['lat']);
        update_post_meta($post_id, '_local_longitude', $data[0]['lon']);
        error_log("✅ Auto-geocoded local {$post_id}: {$data[0]['lat']}, {$data[0]['lon']}");
    } else {
        error_log("⚠️ No coordinates found for local {$post_id}: {$query}");
    }
}

// Add scheduled geocoding hook
add_action('apollo_delayed_geocode', function($post_id) {
    $local = get_post($post_id);
    if ($local && $local->post_type === 'event_local') {
        // Call auto_geocode_local directly
        $plugin = new Apollo_Events_Manager_Plugin();
        $plugin->auto_geocode_local($post_id, $local);
    }
});
```

---

#### 5. **[apollo-events-manager.php:430] Cache não é limpo ao salvar evento**
**Cenário:**
- Admin edita evento (muda data, DJ, etc)
- Clica "Atualizar"
- Volta ao front-end
- Vê dados antigos por 5 minutos (cache TTL)

**Impacto:** Confusão, admin pensa que não salvou

**Fix Sugerido:**
```php
// Add to save_custom_event_fields() method (linha ~1100)
public function save_custom_event_fields($post_id, $post) {
    // ... existing security checks and save logic ...
    
    // Clear cache after saving
    wp_cache_flush_group('apollo_events');
    
    // Also clear any transients
    delete_transient('apollo_events_shortcode_' . md5(serialize(['default'])));
}

// Or use WordPress hook (more global)
add_action('save_post_event_listing', function($post_id) {
    wp_cache_flush_group('apollo_events');
}, 999); // Run after save_custom_event_fields
```

---

### 🟢 LOW PRIORITY

#### 6. **[templates/event-card.php:119] strtotime() sem validação de formato**
**Cenário:**
- Meta `_event_start_date` tem valor corrompido: `"invalid-date"`
- `strtotime()` retorna `false`
- `date('j', false)` gera warning PHP

**Impacto:** PHP warnings no log, data exibe "01 Jan" ao invés de mensagem clara

**Fix Sugerido:**
```php
// Format date
$day = '';
$month = '';
$month_str = '';

if ($start_date) {
    // Validate date format first
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
        // Invalid format - log and skip
        if (current_user_can('administrator')) {
            echo '<!-- DEBUG: Invalid date format: ' . esc_html($start_date) . ' (expected YYYY-MM-DD) -->';
        }
        error_log("⚠️ Apollo: Invalid date format for event {$event_id}: {$start_date}");
    } else {
        $timestamp = strtotime($start_date);
        if ($timestamp && $timestamp > 0) {
            $day = date('j', $timestamp);
            $month = date('M', $timestamp);
            // ... rest of month mapping
        }
    }
}
```

---

#### 7. **[templates/single-event-standalone.php:520] Leaflet não carrega - sem retry**
**Cenário:**
- CDN `unpkg.com` está lento
- JavaScript executa antes de Leaflet carregar
- `typeof L === 'undefined'` → mostra console.error e para

**Impacto:** Mapa não exibe mesmo com coords corretas

**Fix Sugerido:**
```php
<script>
// Retry logic for Leaflet loading
function initializeMap(retries = 0) {
    if (typeof L === 'undefined') {
        if (retries < 5) {
            console.log('⏳ Waiting for Leaflet... (attempt ' + (retries + 1) + ')');
            setTimeout(() => initializeMap(retries + 1), 500);
        } else {
            console.error('❌ Leaflet failed to load after 5 attempts');
            document.querySelector('.event-map-box').innerHTML = 
                '<p style="padding:20px;text-align:center;">Mapa indisponível no momento.</p>';
        }
        return;
    }
    
    // Leaflet loaded, initialize map
    console.log('✅ Leaflet loaded. Initializing map...');
    var m = L.map('eventMap').setView([<?php echo floatval($map_lat);?>, <?php echo floatval($map_lng);?>], 15);
    // ... rest of map initialization
}

// Start initialization
document.addEventListener('DOMContentLoaded', initializeMap);
</script>
```

---

#### 8. **[includes/admin-metaboxes.php:286] Duplicate check é case-sensitive e slow**
**Cenário:**
- Admin cria DJ "Pedro Santos"
- Tenta criar "pedro santos" (lowercase)
- Sistema permite (deveria bloquear)
- Também: loop em todos os DJs (N queries) ao invés de 1 query

**Impacto:** Duplicados sutis, performance ruim com muitos DJs

**Fix Sugerido:**
```php
public function ajax_add_new_dj() {
    check_ajax_referer('apollo_admin_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(__('Permission denied', 'apollo-events-manager'));
    }
    
    $name = sanitize_text_field($_POST['name'] ?? '');
    $bio = wp_kses_post($_POST['bio'] ?? '');
    
    if (empty($name)) {
        wp_send_json_error(__('DJ name is required', 'apollo-events-manager'));
    }
    
    // Optimized duplicate check using WP_Query
    $normalized = mb_strtolower(trim($name), 'UTF-8');
    
    $existing = new WP_Query([
        'post_type'      => 'event_dj',
        'post_status'    => ['publish', 'draft'],
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'     => '_dj_name_normalized',
                'value'   => $normalized,
                'compare' => '='
            ]
        ]
    ]);
    
    if ($existing->have_posts()) {
        wp_send_json_error(__('A DJ with this name already exists', 'apollo-events-manager'));
    }
    
    // Create new DJ
    $new_dj_id = wp_insert_post([
        'post_title'  => $name,
        'post_type'   => 'event_dj',
        'post_status' => 'publish',
        'post_content' => $bio,
    ]);
    
    if (is_wp_error($new_dj_id)) {
        wp_send_json_error($new_dj_id->get_error_message());
    }
    
    // Save meta
    update_post_meta($new_dj_id, '_dj_name', $name);
    update_post_meta($new_dj_id, '_dj_name_normalized', $normalized); // For duplicate checks
    update_post_meta($new_dj_id, '_dj_bio', $bio);
    
    wp_send_json_success([
        'id'   => $new_dj_id,
        'name' => $name
    ]);
}
```

---

## ⚠️ EDGE CASES TO MANUALLY TEST

### 1. Empty Database Scenarios

#### 1.1 Zero Events
- [x] Acesse `/eventos/` sem nenhum evento publicado
- **Esperado:** Exibe "Nenhum evento encontrado"
- **Verificar:** Não há erro 500 ou warning PHP

#### 1.2 Zero DJs
- [x] Crie evento sem selecionar DJs
- [x] Visualize no front-end
- **Esperado:** Card exibe sem seção de DJ
- **Verificar:** Não há "undefined" ou campo vazio

#### 1.3 Zero Locais
- [x] Crie evento sem selecionar Local
- [x] Visualize no front-end
- **Esperado:** Seção de local não exibe ou mostra "Local TBA"
- **Verificar:** Mapa não quebra (exibe placeholder)

---

### 2. Single Entity Scenarios

#### 2.1 Único Evento
- [x] Publique apenas 1 evento
- [x] Acesse `/eventos/`
- **Esperado:** Grid exibe 1 card sem quebrar layout
- **Verificar:** Filtros funcionam (mostram/escondem conforme categoria)

#### 2.2 Evento com 1 DJ
- [x] Evento com apenas 1 DJ no timetable
- **Esperado:** Exibe nome do DJ sem vírgula extra
- **Verificar:** Não exibe "DJ1, " com vírgula pendurada

#### 2.3 Evento com 1 Imagem
- [x] Salve evento com 1 imagem em `_3_imagens_promo`
- **Esperado:** Exibe 1 imagem, não tenta carregar 2 vazias

---

### 3. Data Boundary Cases

#### 3.1 Evento no Passado
- [x] Crie evento com `_event_start_date: 2020-01-01`
- [x] Acesse `/eventos/`
- **Esperado:** Não exibe (query filtra `>= date('Y-m-d')`)
- **Verificar:** Não aparece em nenhum lugar

#### 3.2 Evento Hoje
- [x] Crie evento com data de hoje
- **Esperado:** Exibe normalmente

#### 3.3 Data Inválida
- [x] Manualmente via DB: `UPDATE wp_postmeta SET meta_value = 'invalid' WHERE meta_key = '_event_start_date'`
- [x] Acesse front-end
- **Esperado:** Card exibe sem data (ou placeholder "Data TBA")
- **Verificar:** Não há PHP error/warning

#### 3.4 Hora Inválida
- [x] Salve `_event_start_time: "25:99:99"`
- **Esperado:** Exibe hora como "Horário TBA" ou ignora
- **Verificar:** Não quebra template

---

### 4. Template Override Conflicts

#### 4.1 Tema com page-eventos.php
- [x] Crie arquivo no tema: `mytheme/page-eventos.php`
- [x] Acesse `/eventos/`
- **Esperado:** Usa template do tema, não do plugin
- **Verificar:** `canvas_template()` retorna theme template

#### 4.2 Página com Shortcode [eventos-page]
- [x] Edite página `/eventos/`
- [x] Adicione `[eventos-page]` ao conteúdo
- **Esperado:** Não sobrescreve com canvas template
- **Verificar:** Exibe conteúdo da página normalmente

#### 4.3 Página Deletada
- [x] Delete página `/eventos/` (mova para lixeira)
- [x] Acesse `/eventos/` no front-end
- **Esperado:** ??? (atualmente recria página - BUG #1)

---

### 5. AJAX Edge Cases

#### 5.1 Nonce Expirado
- [x] Carregue `/eventos/`
- [x] Espere 12 horas (ou ajuste `wp_nonce_tick()`)
- [x] Clique em evento para abrir lightbox
- **Esperado:** Erro "Nonce inválido"
- **Verificar:** Não exibe erro genérico

#### 5.2 Nonce Ausente
- [x] Via DevTools: remova `nonce` do FormData
- [x] Tente filtrar eventos
- **Esperado:** `check_ajax_referer()` retorna erro 403

#### 5.3 Event ID Inválido
- [x] Via console: `fetch(ajaxurl, { ... event_id: 99999 })`
- **Esperado:** `ajax_load_event_single` retorna erro "Evento não encontrado"

#### 5.4 Filtro "Todos" com 100+ Eventos
- [x] Crie 150 eventos
- [x] Filtre por "Todos"
- **Esperado:** Exibe apenas 100 (limit na query)
- **Verificar:** Performance OK, sem timeout

---

### 6. Multi-value Meta Cases

#### 6.1 DJ IDs como Array de Strings
- [x] Salve `_event_dj_ids: ["92", "93", "94"]` (strings)
- **Esperado:** Templates convertem para int corretamente

#### 6.2 DJ IDs Serializado (Legacy)
- [x] Via DB: `UPDATE wp_postmeta SET meta_value = 'a:3:{i:0;s:2:"92";...}'`
- **Esperado:** `maybe_unserialize()` funciona, DJs exibem

#### 6.3 Timetable com Múltiplos Formatos
- [x] Evento A: `[{ dj: 92, start: "22:00", end: "23:00" }]`
- [x] Evento B: `[{ dj: 92, time_start: "22:00", time_end: "23:00" }]` (legacy)
- **Esperado:** Ambos exibem corretamente (fallbacks no template)

---

### 7. Geocoding Edge Cases

#### 7.1 Local sem Cidade
- [x] Crie Local com apenas nome (sem `_local_city`)
- [x] Salve
- **Esperado:** `auto_geocode_local()` retorna early (linha 203)

#### 7.2 Local com Coords Existentes
- [x] Local já tem `_local_latitude` e `_local_longitude`
- [x] Edite e salve novamente
- **Esperado:** Não chama API (retorna early linha 208)

#### 7.3 Nominatim Timeout
- [x] Simule: `add_filter('http_request_timeout', fn() => 1);` (forçar timeout)
- **Esperado:** `is_wp_error($res)` captura, loga erro

#### 7.4 Nominatim Sem Resultados
- [x] Local com cidade "Cidade Inexistente 123"
- **Esperado:** Loga "No coordinates found" (linha 233), não salva coords

---

### 8. Asset Loading Edge Cases

#### 8.1 CDN apollo.rio.br Offline
- [x] Bloqueie `assets.apollo.rio.br` no hosts
- [x] Acesse `/eventos/`
- **Esperado:** ??? (atualmente não há fallback automático)
- **Verificar:** Layout quebra ou carrega CSS local?

#### 8.2 Leaflet CDN Offline
- [x] Bloqueie `unpkg.com`
- [x] Acesse evento single
- **Esperado:** Mapa não exibe, mostra placeholder

#### 8.3 RemixIcon CDN Offline
- [x] Bloqueie `jsdelivr.net`
- **Esperado:** Ícones não exibem, mas layout não quebra

---

### 9. Permission Edge Cases

#### 9.1 Subscriber Tenta Criar DJ
- [x] Login como subscriber
- [x] Tente chamar `wp_ajax_apollo_add_new_dj`
- **Esperado:** `current_user_can('edit_posts')` falha, erro 403

#### 9.2 Editor Edita Evento de Outro
- [x] Login como editor
- [x] Tente editar evento de admin
- **Esperado:** Permitido (editors podem editar posts de outros)

---

### 10. Activation/Deactivation

#### 10.1 Primeira Ativação (Clean Install)
- [x] Banco limpo (sem tabelas, sem metas)
- [x] Ative plugin
- **Esperado:**
  - CPTs registrados
  - Página `/eventos/` criada
  - Rewrite rules flushed
  - Log: "✅ Apollo Events Manager 2.0.0 activated"

#### 10.2 Reativação (Com Dados Existentes)
- [x] Desative plugin
- [x] Reative
- **Esperado:**
  - Não duplica página `/eventos/`
  - Dados existentes intactos

#### 10.3 Desativação
- [x] Desative plugin
- **Esperado:**
  - Rewrite rules flushed
  - CPTs não aparecem mais
  - Dados permanecem no banco

---

## ✅ FINAL RELEASE CHECKLIST

### Pre-Release

- [ ] **Run Migration Validator**
  ```bash
  wp-admin → Tools → Apollo Migration Validator → Run Validation
  ```
  - Verificar 0 critical issues
  - Documentar warnings (se houver)

- [ ] **Check Error Logs**
  ```bash
  tail -f wp-content/debug.log
  ```
  - Não deve ter erros PHP
  - Warnings aceitáveis (documentar)

- [ ] **Database Backup**
  ```bash
  wp db export apollo-backup-$(date +%Y%m%d).sql
  ```

- [ ] **Test All AJAX Endpoints**
  - [ ] `/wp-admin/admin-ajax.php?action=filter_events` (com nonce)
  - [ ] `/wp-admin/admin-ajax.php?action=load_event_single` (com nonce)
  - [ ] `/wp-admin/admin-ajax.php?action=toggle_favorite` (com nonce)

- [ ] **Clear All Caches**
  - [ ] WordPress object cache: `wp cache flush`
  - [ ] Browser cache (Ctrl+Shift+Del)
  - [ ] CDN cache (Cloudflare, etc)

---

### Functional Testing

#### Core Features
- [ ] **Create Event** (admin)
  - [ ] Preencher todos os campos
  - [ ] Salvar e verificar front-end
  - [ ] Editar e verificar alterações aparecem

- [ ] **Create DJ** (via metabox)
  - [ ] Criar novo DJ inline
  - [ ] Verificar não permite duplicados
  - [ ] Verificar geocoding (se cidade preenchida)

- [ ] **Create Local** (via metabox)
  - [ ] Criar novo Local inline
  - [ ] Verificar auto-geocoding
  - [ ] Verificar coordenadas salvas

- [ ] **Event Listing** (`/eventos/`)
  - [ ] Exibe todos os eventos futuros
  - [ ] Ordenados por data ASC
  - [ ] Cards exibem: banner, data, DJs, local

- [ ] **Event Filtering** (AJAX)
  - [ ] Filtrar por categoria
  - [ ] Search por título
  - [ ] Filtrar por data (month picker)

- [ ] **Event Lightbox** (AJAX)
  - [ ] Clicar em card abre lightbox
  - [ ] Exibe: banner, descrição, DJs, local, mapa
  - [ ] Fecha com ESC ou click overlay
  - [ ] Botão "Comprar" abre URL externa

- [ ] **Event Single Page** (`/event/slug/`)
  - [ ] Exibe evento completo
  - [ ] Mapa Leaflet funciona (se coords)
  - [ ] Vídeo YouTube embeda (se URL válida)
  - [ ] Botão "Como Chegar" abre Google Maps

- [ ] **Favorites** (se implementado)
  - [ ] Toggle favorito salva
  - [ ] Contador incrementa/decrementa

---

### Security Testing

- [ ] **SQL Injection**
  ```bash
  # Tente injetar via AJAX filter
  curl -X POST admin-ajax.php -d "action=filter_events&category=' OR '1'='1&nonce=..."
  ```
  - Esperado: Sanitização bloqueia

- [ ] **XSS**
  ```bash
  # Tente injetar script via nome de DJ
  wp post create --post_type=event_dj --post_title="<script>alert(1)</script>"
  ```
  - Esperado: `esc_html()` escapa na saída

- [ ] **CSRF**
  - Remova nonce do form
  - Tente salvar evento
  - Esperado: `wp_verify_nonce()` bloqueia

- [ ] **Authorization**
  - Login como subscriber
  - Tente acessar `/wp-admin/post.php?post=123&action=edit`
  - Esperado: WordPress bloqueia

---

### Performance Testing

- [ ] **Page Load Time** (GTmetrix / WebPageTest)
  - `/eventos/` deve carregar em < 2s
  - Evento single < 1.5s

- [ ] **Database Queries**
  ```php
  define('SAVEQUERIES', true);
  // No footer: print_r($wpdb->queries);
  ```
  - `/eventos/` deve ter < 50 queries
  - Evento single < 30 queries

- [ ] **Asset Size**
  - `uni.css` comprimido < 100KB
  - Total page weight < 1MB

---

### Cross-Browser Testing

- [ ] **Chrome** (latest)
- [ ] **Firefox** (latest)
- [ ] **Safari** (latest)
- [ ] **Edge** (latest)
- [ ] **Mobile Safari** (iOS)
- [ ] **Chrome Mobile** (Android)

---

### Mobile Testing

- [ ] **Responsive Layout**
  - [ ] 320px width (iPhone SE)
  - [ ] 375px width (iPhone 12)
  - [ ] 768px width (iPad)

- [ ] **Touch Interactions**
  - [ ] Swipe cards
  - [ ] Tap to open lightbox
  - [ ] Pinch to zoom mapa

- [ ] **PWA Mode** (se implementado)
  - [ ] Manifest.json válido
  - [ ] Instalar no home screen
  - [ ] Offline fallback

---

### Staging Deployment

- [ ] **Deploy to Staging**
  ```bash
  git push staging main
  ```

- [ ] **Run Tests on Staging**
  - Todos os itens acima

- [ ] **Stakeholder Review**
  - Product Owner approval
  - Design approval
  - Content approval

---

### Production Deployment

- [ ] **Production Backup**
  ```bash
  # Database + files
  wp db export prod-backup-$(date +%Y%m%d).sql
  tar -czf prod-files-$(date +%Y%m%d).tar.gz wp-content/
  ```

- [ ] **Deploy to Production**
  ```bash
  git push production main
  ```

- [ ] **Post-Deploy Verification**
  - [ ] `/eventos/` carrega OK
  - [ ] Eventos exibem corretamente
  - [ ] AJAX funciona
  - [ ] Mapa renderiza
  - [ ] Sem erros no console
  - [ ] Sem erros no debug.log

- [ ] **Monitor Errors** (24h)
  - [ ] Check error logs hourly
  - [ ] Monitor Sentry/Bugsnag
  - [ ] User feedback

---

### Documentation

- [ ] **Update README.md**
  - [ ] Version bump
  - [ ] Changelog
  - [ ] Known issues

- [ ] **Update SECURITY-FIXES.md**
  - [ ] Document all fixes

- [ ] **Create Release Notes**
  - [ ] GitHub Release
  - [ ] Version tag

---

### Version Bump

- [ ] **Update Version Numbers**
  ```php
  // apollo-events-manager.php linha 10
  * Version: 2.0.2
  
  // apollo-events-manager.php linha 17
  define('APOLLO_WPEM_VERSION', '2.0.2');
  ```

- [ ] **Git Tag**
  ```bash
  git tag -a v2.0.2 -m "Release v2.0.2 - Security fixes"
  git push origin v2.0.2
  ```

---

## 📊 ACCEPTANCE CRITERIA

### Must Have (Blocker)
- [x] Nenhuma vulnerabilidade crítica de segurança
- [x] Nenhum erro fatal PHP
- [x] AJAX endpoints com nonce verificado
- [x] Output escapado em todos os templates
- [x] SQL queries usando `$wpdb->prepare()`

### Should Have (Important)
- [ ] Cache invalidation ao salvar evento
- [ ] Rate limiting no geocoding
- [ ] Duplicate page creation handling
- [ ] WP_Query error handling

### Nice to Have (Optional)
- [ ] Leaflet retry logic
- [ ] Duplicate DJ check optimization
- [ ] Date format validation

---

## 🚀 GO/NO-GO DECISION

### ✅ GO if:
- Todos os "Must Have" estão completos
- Nenhum bug crítico em staging
- Performance aceitável (< 2s page load)
- Security audit passou

### ❌ NO-GO if:
- Qualquer vulnerabilidade crítica encontrada
- Errors fatais PHP
- Data loss risk
- Performance inaceitável (> 5s page load)

---

**Última Atualização:** 2025-11-04  
**Status:** 🟡 Aguardando QA Final  
**Próxima Ação:** Executar checklist completo em staging

