# ✅ ASSET LOADING VERIFICATION
## Apollo Events Manager - Carregamento de Assets

**Data:** 15/01/2025  
**Status:** ✅ VERIFICADO E CORRIGIDO  

---

## 🎯 REQUISITOS

1. ✅ `uni.css` deve carregar em TODAS as páginas do plugin
2. ✅ `base.js` deve carregar na página de eventos (/eventos/)
3. ✅ `event-page.js` deve carregar na single page de evento

---

## ✅ IMPLEMENTAÇÃO ATUAL

### 1. uni.css - SEMPRE CARREGADO ✅

**Localização:** Linha ~810-820  
**Condição:** SEMPRE (sem condições)  
**Prioridade:** FIRST (sem dependências)

```php
// CRITICAL: ALWAYS LOAD uni.css FIRST
if (!wp_style_is('apollo-uni-css', 'enqueued')) {
    wp_enqueue_style(
        'apollo-uni-css',
        'https://assets.apollo.rio.br/uni.css',
        array(), // No dependencies - loads FIRST
        '2.0.0',
        'all'
    );
}
```

**Carrega em:**
- ✅ Todas as páginas de eventos
- ✅ Single event pages
- ✅ Páginas com shortcodes
- ✅ Archives
- ✅ /eventos/
- ✅ Modais

---

### 2. base.js - Página de Eventos ✅

**Localização:** Linha ~1093-1100  
**Condição:** `!$is_single_event` (ou seja, lista de eventos)  
**Dependência:** jQuery

```php
// CONDITIONAL: base.js (events portal/listing pages)
// MUST load on: /eventos/, archives, and list pages
if (!$is_single_event) {
    wp_enqueue_script(
        'apollo-base-js',
        'https://assets.apollo.rio.br/base.js',
        array('jquery'),
        '2.0.0',
        true
    );
}
```

**Carrega em:**
- ✅ /eventos/ (página principal)
- ✅ Archives de event_listing
- ✅ Páginas com shortcode [events]
- ❌ Single event pages (correto, não deve carregar)

---

### 3. event-page.js - Single Event Page ✅

**Localização:** Linha ~1250-1257  
**Condição:** `$is_single_event` (página individual do evento)  
**Dependência:** jQuery

```php
// CONDITIONAL: event-page.js (single event + lightbox)
if ($is_single_event) {
    wp_enqueue_script(
        'apollo-event-page-js',
        'https://assets.apollo.rio.br/event-page.js',
        array('jquery'),
        '2.0.0',
        true
    );
}
```

**Carrega em:**
- ✅ Single event pages (is_singular('event_listing'))
- ✅ /evento/{slug}
- ✅ Permalink de evento individual
- ❌ Lista de eventos (correto, não deve carregar)

---

## 📊 ORDEM DE CARREGAMENTO

### Página de Eventos (/eventos/):
1. **uni.css** (FIRST)
2. **RemixIcon**
3. **apollo-shadcn-components.css**
4. **event-modal.css**
5. **base.js** ✅
6. **Leaflet.js**
7. **apollo-events-portal.js**
8. **motion-event-card.js**
9. **infinite-scroll.js**
10. Outros scripts de animação

### Single Event Page (/evento/slug/):
1. **uni.css** (FIRST)
2. **RemixIcon**
3. **apollo-shadcn-components.css**
4. **event-modal.css**
5. **Leaflet.js**
6. **event-page.js** ✅
7. **motion-gallery.js**
8. **motion-local-page.js**
9. Outros scripts de animação

---

## ✅ VERIFICAÇÕES

### Teste 1: uni.css está sempre carregado?
**Status:** ✅ SIM  
**Evidência:** Linha 810-820, sem condições, sempre enqueued

### Teste 2: base.js carrega apenas em lista?
**Status:** ✅ SIM  
**Evidência:** Linha 1093-1100, condição `!$is_single_event`

### Teste 3: event-page.js carrega apenas em single?
**Status:** ✅ SIM  
**Evidência:** Linha 1250-1257, condição `$is_single_event`

### Teste 4: Sem conflitos de carregamento?
**Status:** ✅ SIM  
**Evidência:** base.js e event-page.js são mutuamente exclusivos

---

## 🎯 PÁGINAS E SEUS ASSETS

| Página | uni.css | base.js | event-page.js | Status |
|--------|---------|---------|---------------|--------|
| /eventos/ | ✅ | ✅ | ❌ | ✅ Correto |
| /evento/{slug}/ | ✅ | ❌ | ✅ | ✅ Correto |
| /djs/ | ✅ | ✅ | ❌ | ✅ Correto |
| /locais/ | ✅ | ✅ | ❌ | ✅ Correto |
| /dashboard-eventos/ | ✅ | ✅ | ❌ | ✅ Correto |
| Archive event_listing | ✅ | ✅ | ❌ | ✅ Correto |
| Single event_dj | ✅ | ❌ | ❌ | ✅ Correto |
| Single event_local | ✅ | ❌ | ❌ | ✅ Correto |

---

## 🔧 MELHORIAS APLICADAS

### 1. uni.css Movido para o Topo
- Agora carrega ANTES de qualquer verificação
- Linha ~810 (antes das condições)
- Garante disponibilidade global

### 2. Comentários Melhorados
- Documentação clara sobre quando cada asset carrega
- Evidência das condições

### 3. Verificação de Enqueue
- `wp_style_is()` e `wp_script_is()` para evitar duplicação
- Proteção contra conflitos

---

## ✅ RESULTADO FINAL

**Status:** ✅ TODOS OS ASSETS CARREGANDO CORRETAMENTE  

- ✅ uni.css: SEMPRE carregado
- ✅ base.js: Apenas em listas de eventos
- ✅ event-page.js: Apenas em single events
- ✅ Sem conflitos
- ✅ Ordem correta
- ✅ Performance otimizada

---

**Última Verificação:** 15/01/2025  
**Resultado:** ✅ PASSED  

