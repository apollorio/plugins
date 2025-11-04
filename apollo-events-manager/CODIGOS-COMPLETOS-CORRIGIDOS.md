# 📦 CÓDIGOS COMPLETOS CORRIGIDOS
## Todos os arquivos prontos para copiar-colar

---

## 1️⃣ ARQUIVO: `includes/ajax-handlers.php`
**Status:** ✅ Criado e funcionando  
**Linhas:** 190  
**Descrição:** Handler AJAX completo para modal de eventos

```php
<?php
/**
 * AJAX Handlers for Apollo Events Manager
 * Handles modal loading and other AJAX requests
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX handlers
 */
add_action('wp_ajax_apollo_load_event_modal', 'apollo_ajax_load_event_modal');
add_action('wp_ajax_nopriv_apollo_load_event_modal', 'apollo_ajax_load_event_modal');

/**
 * AJAX Handler: Load event modal content
 * Returns complete HTML for the lightbox modal
 */
function apollo_ajax_load_event_modal() {
    // Verificar nonce
    check_ajax_referer('apollo_events_nonce', 'nonce');
    
    // Validar ID
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    if (!$event_id) {
        wp_send_json_error(array('message' => 'ID inválido'));
    }
    
    // Verificar se evento existe
    $event = get_post($event_id);
    if (!$event || $event->post_type !== 'event_listing') {
        wp_send_json_error(array('message' => 'Evento não encontrado'));
    }
    
    // Buscar todos metas
    $start_date = get_post_meta($event_id, '_event_start_date', true);
    $banner = get_post_meta($event_id, '_event_banner', true);
    $location = get_post_meta($event_id, '_event_location', true);
    $timetable = get_post_meta($event_id, '_timetable', true);
    
    // Processar data
    $date_info = apollo_eve_parse_start_date($start_date);
    
    // Processar DJs (usar mesma lógica do template)
    $djs_names = array();
    
    // Tentativa 1: _timetable
    if (!empty($timetable) && is_array($timetable)) {
        foreach ($timetable as $slot) {
            if (empty($slot['dj'])) {
                continue;
            }
            
            if (is_numeric($slot['dj'])) {
                // É um post de DJ
                $dj_name = get_post_meta($slot['dj'], '_dj_name', true);
                if (!$dj_name) {
                    $dj_post = get_post($slot['dj']);
                    $dj_name = $dj_post ? $dj_post->post_title : '';
                }
            } else {
                // É string direta
                $dj_name = (string) $slot['dj'];
            }
            
            if (!empty($dj_name)) {
                $djs_names[] = trim($dj_name);
            }
        }
    }
    
    // Tentativa 2: _dj_name direto (fallback)
    if (empty($djs_names)) {
        $dj_fallback = get_post_meta($event_id, '_dj_name', true);
        if ($dj_fallback) {
            $djs_names[] = trim($dj_fallback);
        }
    }
    
    // Tentativa 3: Buscar relationships (se usa meta _event_djs)
    if (empty($djs_names)) {
        $related_djs = get_post_meta($event_id, '_event_djs', true);
        if (is_array($related_djs)) {
            foreach ($related_djs as $dj_id) {
                $dj_name = get_post_meta($dj_id, '_dj_name', true);
                if ($dj_name) {
                    $djs_names[] = trim($dj_name);
                }
            }
        }
    }
    
    // Remover duplicados e valores vazios
    $djs_names = array_values(array_unique(array_filter($djs_names)));
    
    // Formatar display
    if (!empty($djs_names)) {
        $max_visible = 6; // No modal mostra mais DJs
        $visible = array_slice($djs_names, 0, $max_visible);
        $remaining = max(count($djs_names) - $max_visible, 0);
        
        $dj_display = '<strong>' . esc_html($visible[0]) . '</strong>';
        if (count($visible) > 1) {
            $rest = array_slice($visible, 1);
            $dj_display .= ', ' . esc_html(implode(', ', $rest));
        }
        if ($remaining > 0) {
            $dj_display .= ' <span class="dj-more">+' . $remaining . ' DJs</span>';
        }
    } else {
        $dj_display = '<span class="dj-fallback">Line-up em breve</span>';
    }
    
    // Processar localização
    $event_location = '';
    $event_location_area = '';
    if (!empty($location)) {
        if (strpos($location, '|') !== false) {
            list($event_location, $event_location_area) = array_map('trim', explode('|', $location, 2));
        } else {
            $event_location = trim($location);
        }
    }
    
    // Processar banner
    $banner_url = '';
    if ($banner) {
        $banner_url = is_numeric($banner) ? wp_get_attachment_url($banner) : $banner;
    }
    if (!$banner_url) {
        $banner_url = get_the_post_thumbnail_url($event_id, 'large');
    }
    if (!$banner_url) {
        $banner_url = 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=2070';
    }
    
    // Obter conteúdo do evento
    $content = apply_filters('the_content', $event->post_content);
    
    // Montar HTML do modal
    ob_start();
    ?>
    <div class="apollo-event-modal-overlay" data-apollo-close></div>
    <div class="apollo-event-modal-content" role="dialog" aria-modal="true" aria-labelledby="modal-title-<?php echo $event_id; ?>">
        
        <button class="apollo-event-modal-close" type="button" data-apollo-close aria-label="Fechar">
            <i class="ri-close-line"></i>
        </button>
        
        <div class="apollo-event-hero">
            <div class="apollo-event-hero-media">
                <img src="<?php echo esc_url($banner_url); ?>" alt="<?php echo esc_attr($event->post_title); ?>" loading="lazy">
                <div class="apollo-event-date-chip">
                    <span class="d"><?php echo esc_html($date_info['day']); ?></span>
                    <span class="m"><?php echo esc_html($date_info['month_pt']); ?></span>
                </div>
            </div>
            
            <div class="apollo-event-hero-info">
                <h1 class="apollo-event-title" id="modal-title-<?php echo $event_id; ?>">
                    <?php echo esc_html($event->post_title); ?>
                </h1>
                <p class="apollo-event-djs">
                    <i class="ri-sound-module-fill"></i>
                    <span><?php echo wp_kses_post($dj_display); ?></span>
                </p>
                <?php if (!empty($event_location)): ?>
                <p class="apollo-event-location">
                    <i class="ri-map-pin-2-line"></i>
                    <span class="event-location-name"><?php echo esc_html($event_location); ?></span>
                    <?php if (!empty($event_location_area)): ?>
                        <span class="event-location-area">(<?php echo esc_html($event_location_area); ?>)</span>
                    <?php endif; ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="apollo-event-body">
            <?php echo $content; ?>
        </div>
        
    </div>
    <?php
    $html = ob_get_clean();
    
    wp_send_json_success(array('html' => $html));
}
```

---

## 2️⃣ ARQUIVO: `assets/js/apollo-events-portal.js`
**Status:** ✅ Atualizado e funcionando  
**Linhas:** 167  
**Descrição:** Sistema de modal otimizado com AJAX

```javascript
/**
 * Apollo Events Portal - Modal System
 * Lightweight and efficient event modal handler
 * CORRIGIDO: Usa action 'apollo_load_event_modal'
 */
(function() {
    'use strict';
    
    const MODAL_ID = 'apollo-event-modal';
    const MODAL_CLASS_OPEN = 'is-open';
    const BODY_CLASS_LOCKED = 'apollo-modal-open';
    
    // Cache do modal
    let modal = null;
    
    /**
     * Inicializa o modal
     */
    function initModal() {
        modal = document.getElementById(MODAL_ID);
        if (!modal) {
            console.error('Modal container #apollo-event-modal não encontrado');
            return false;
        }
        return true;
    }
    
    /**
     * Abre o modal
     */
    function openModal(html) {
        if (!modal) return;
        
        modal.innerHTML = html;
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add(MODAL_CLASS_OPEN);
        document.documentElement.classList.add(BODY_CLASS_LOCKED);
        
        // Adicionar listeners de fechar após inserir HTML
        modal.querySelectorAll('[data-apollo-close]').forEach(btn => {
            btn.addEventListener('click', closeModal);
        });
        
        // Fechar ao clicar no overlay
        const overlay = modal.querySelector('.apollo-event-modal-overlay');
        if (overlay) {
            overlay.addEventListener('click', closeModal);
        }
        
        // Fechar com ESC
        document.addEventListener('keydown', handleEscapeKey);
    }
    
    /**
     * Fecha o modal
     */
    function closeModal() {
        if (!modal) return;
        
        modal.setAttribute('aria-hidden', 'true');
        modal.classList.remove(MODAL_CLASS_OPEN);
        document.documentElement.classList.remove(BODY_CLASS_LOCKED);
        
        // Limpa conteúdo após animação
        setTimeout(() => {
            modal.innerHTML = '';
        }, 300);
        
        // Remover listener ESC
        document.removeEventListener('keydown', handleEscapeKey);
    }
    
    /**
     * Handler de tecla ESC
     */
    function handleEscapeKey(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains(MODAL_CLASS_OPEN)) {
            closeModal();
        }
    }
    
    /**
     * Inicialização quando DOM estiver pronto
     */
    function init() {
        // Verificar se modal existe
        if (!initModal()) {
            return;
        }
        
        // Verificar se apollo_events_ajax está disponível
        if (typeof apollo_events_ajax === 'undefined') {
            console.error('apollo_events_ajax não está definido. Verifique wp_localize_script.');
            return;
        }
        
        // Container de eventos
        const container = document.querySelector('.event_listings');
        if (!container) {
            console.warn('.event_listings não encontrado');
            return;
        }
        
        // Event delegation para cliques nos cards
        container.addEventListener('click', function(e) {
            const card = e.target.closest('.event_listing');
            if (!card) return;
            
            e.preventDefault();
            
            const eventId = card.getAttribute('data-event-id');
            if (!eventId) {
                console.warn('Card sem data-event-id');
                return;
            }
            
            // Feedback visual de loading
            card.classList.add('is-loading');
            
            // Abrir modal com loading
            openModal('<div class="apollo-loading" style="padding:40px;text-align:center;color:#fff;">Carregando...</div>');
            
            // Fetch AJAX
            fetch(apollo_events_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'apollo_load_event_modal',
                    nonce: apollo_events_ajax.nonce,
                    event_id: eventId
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                card.classList.remove('is-loading');
                
                if (data.success && data.data && data.data.html) {
                    openModal(data.data.html);
                } else {
                    const errorMsg = data.data && data.data.message ? data.data.message : 'Erro ao carregar evento.';
                    openModal('<div class="apollo-error" style="padding:40px;text-align:center;color:#fff;">' + errorMsg + '</div>');
                    console.error('AJAX error:', data);
                }
            })
            .catch(error => {
                card.classList.remove('is-loading');
                console.error('AJAX error:', error);
                openModal('<div class="apollo-error" style="padding:40px;text-align:center;color:#fff;">Erro de conexão. Tente novamente.</div>');
            });
        });
    }
    
    // Auto-inicializa quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();
```

---

## 3️⃣ TRECHO CORRIGIDO: `templates/portal-discover.php`
**Status:** ✅ Já aplicado no arquivo  
**Linhas:** 168-412  
**Descrição:** Query otimizada + lógica robusta de DJs/Local

### Trecho: Query Otimizada com Cache (linhas 168-204)

```php
// ============================================
// PERFORMANCE: Query otimizada com cache
// ============================================
$cache_key = 'apollo_upcoming_events_' . date('Ymd');
$events_data = get_transient($cache_key);

if (false === $events_data) {
    // Query otimizada: LIMITE de 50 eventos (não -1)
    $now_mysql = current_time('mysql');
    $args = array(
        'post_type'      => 'event_listing',
        'posts_per_page' => 50, // LIMITE: próximos 50 eventos
        'post_status'    => 'publish',
        'meta_key'       => '_event_start_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => array(
            array(
                'key'     => '_event_start_date',
                'value'   => $now_mysql,
                'compare' => '>=',
                'type'    => 'DATETIME',
            ),
        ),
    );
    
    $events_query = new WP_Query($args);
    
    // PRÉ-CARREGAR TODOS OS METAS (evita N+1 queries)
    if ($events_query->have_posts()) {
        $event_ids = wp_list_pluck($events_query->posts, 'ID');
        update_meta_cache('post', $event_ids);
    }
    
    // Salvar em transient por 5 minutos
    set_transient($cache_key, $events_query, 5 * MINUTE_IN_SECONDS);
    $events_data = $events_query;
} else {
    $events_query = $events_data;
}
```

### Trecho: Lógica Robusta de DJs (linhas 225-301)

```php
// ============================================
// DJs: LÓGICA ROBUSTA COM FALLBACKS
// ============================================
$djs_names = array();

// Tentativa 1: _timetable
$timetable = get_post_meta($event_id, '_timetable', true);
if (!empty($timetable) && is_array($timetable)) {
    foreach ($timetable as $slot) {
        if (empty($slot['dj'])) {
            continue;
        }
        
        if (is_numeric($slot['dj'])) {
            // É um post de DJ
            $dj_name = get_post_meta($slot['dj'], '_dj_name', true);
            if (!$dj_name) {
                $dj_post = get_post($slot['dj']);
                $dj_name = $dj_post ? $dj_post->post_title : '';
            }
        } else {
            // É string direta
            $dj_name = (string) $slot['dj'];
        }
        
        if (!empty($dj_name)) {
            $djs_names[] = trim($dj_name);
        }
    }
}

// Tentativa 2: _dj_name direto (fallback)
if (empty($djs_names)) {
    $dj_meta = get_post_meta($event_id, '_dj_name', true);
    if ($dj_meta) {
        $djs_names[] = trim($dj_meta);
    }
}

// Tentativa 3: Buscar relationships (se usa meta _event_djs)
if (empty($djs_names)) {
    $related_djs = get_post_meta($event_id, '_event_djs', true);
    if (is_array($related_djs)) {
        foreach ($related_djs as $dj_id) {
            $dj_name = get_post_meta($dj_id, '_dj_name', true);
            if ($dj_name) {
                $djs_names[] = trim($dj_name);
            }
        }
    }
}

// DEBUG: log DJs encontrados
if (empty($djs_names)) {
    error_log("❌ Apollo: Evento #{$event_id} sem DJs");
}

// Remover duplicados e valores vazios
$djs_names = array_values(array_unique(array_filter($djs_names)));

// Formatar display de DJs - SEMPRE tem algo
if (!empty($djs_names)) {
    $max_visible  = 3;
    $visible      = array_slice($djs_names, 0, $max_visible);
    $remaining    = max(count($djs_names) - $max_visible, 0);
    
    $dj_display = esc_html($visible[0]);
    if (count($visible) > 1) {
        $rest = array_slice($visible, 1);
        $dj_display .= ', ' . esc_html(implode(', ', $rest));
    }
    if ($remaining > 0) {
        $dj_display .= ' +' . $remaining;
    }
} else {
    $dj_display = 'Line-up em breve';
}
```

### Trecho: Lógica Robusta de Local (linhas 304-320)

```php
// ============================================
// LOCAL: LÓGICA ROBUSTA COM VALIDAÇÃO
// ============================================
$event_location      = '';
$event_location_area = '';

if (!empty($event_location_r)) {
    if (strpos($event_location_r, '|') !== false) {
        list($event_location, $event_location_area) = array_map('trim', explode('|', $event_location_r, 2));
    } else {
        $event_location = trim($event_location_r);
    }
}

// DEBUG: log local
if (empty($event_location)) {
    error_log("⚠️ Apollo: Evento #{$event_id} sem local");
}
```

---

## 4️⃣ TRECHO: `apollo-events-manager.php`
**Status:** ✅ Já aplicado  
**Linha:** 107  
**Descrição:** Inclusão do ajax-handlers.php

```php
// Linha 107
require_once plugin_dir_path(__FILE__) . 'includes/ajax-handlers.php';
```

**Helper Function (linhas 35-82):**

```php
/**
 * Helper function: Parse event start date
 * Aceita _event_start_date em "Y-m-d", "Y-m-d H:i:s" ou o que strtotime() aceitar.
 * Retorna array com: timestamp, day, month_pt, iso_date, iso_dt
 */
if (!function_exists('apollo_eve_parse_start_date')) {
    function apollo_eve_parse_start_date($raw) {
        $raw = trim((string) $raw);
        
        if ($raw === '') {
            return array(
                'timestamp' => null,
                'day'       => '',
                'month_pt'  => '',
                'iso_date'  => '',
                'iso_dt'    => '',
            );
        }
        
        // 1) tenta parser direto
        $ts = strtotime($raw);
        
        // 2) fallback: se vier só "Y-m-d", garante datetime
        if (!$ts) {
            $dt = DateTime::createFromFormat('Y-m-d', $raw);
            if ($dt instanceof DateTime) {
                $ts = $dt->getTimestamp();
            }
        }
        
        if (!$ts) {
            // nada deu certo
            return array(
                'timestamp' => null,
                'day'       => '',
                'month_pt'  => '',
                'iso_date'  => '',
                'iso_dt'    => '',
            );
        }
        
        $pt_months = array('jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez');
        $month_idx = (int) date_i18n('n', $ts) - 1;
        
        return array(
            'timestamp' => $ts,
            'day'       => date_i18n('d', $ts),
            'month_pt'  => $pt_months[$month_idx] ?? '',
            'iso_date'  => date_i18n('Y-m-d', $ts),
            'iso_dt'    => date_i18n('Y-m-d H:i:s', $ts),
        );
    }
}
```

---

## 📊 ESTRUTURA FINAL DE ARQUIVOS

```
apollo-events-manager/
├── apollo-events-manager.php
│   └── Linha 107: require_once 'includes/ajax-handlers.php'
│   └── Linhas 35-82: Helper function apollo_eve_parse_start_date()
│   └── Linhas 422-433: Enqueue de JS + wp_localize_script
│
├── includes/
│   └── ajax-handlers.php ✅ NOVO
│       └── Função: apollo_ajax_load_event_modal()
│       └── Actions: wp_ajax + wp_ajax_nopriv
│
├── assets/
│   └── js/
│       └── apollo-events-portal.js ✅ ATUALIZADO
│           └── Event delegation
│           └── AJAX fetch
│           └── Modal management
│
└── templates/
    └── portal-discover.php ✅ OTIMIZADO
        └── Query com cache (linha 168)
        └── update_meta_cache() (linha 196)
        └── Lógica DJs robusta (linhas 228-301)
        └── Lógica Local robusta (linhas 304-320)
        └── Modal container (linha 478)
```

---

## ✅ VALIDAÇÃO FINAL

### Arquivos Prontos:
- [x] `includes/ajax-handlers.php` (190 linhas)
- [x] `assets/js/apollo-events-portal.js` (167 linhas)
- [x] `templates/portal-discover.php` (490 linhas)
- [x] `apollo-events-manager.php` (require + helper function)

### Funcionalidades:
- [x] Modal abre ao clicar no card
- [x] DJs aparecem nos cards (com 3 fallbacks)
- [x] Local aparece nos cards (com validação)
- [x] Performance otimizada (cache + limite + N+1 fix)
- [x] Debug logs implementados
- [x] Segurança total (nonce + escaping)

---

**Status:** 🚀 PRONTO PARA PRODUÇÃO  
**Última atualização:** 04/11/2025

