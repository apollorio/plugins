# ✅ Correções: Meta Keys nos Templates - 15/01/2025

**Status:** ✅ **Correções Aplicadas**

---

## 📋 Meta Keys Corretos Aplicados

### ✅ `_event_dj_ids` (serialized array)
- ✅ Usa `maybe_unserialize()` diretamente ou via `apollo_aem_parse_ids()` (que já faz unserialize)
- ✅ Verificado em: `content-event_listing.php`, `event-card.php`, `portal-discover.php`, `event-listings-start.php`
- ✅ Função `apollo_get_event_lineup()` também usa corretamente

### ✅ `_event_local_ids` (int)
- ✅ Usado corretamente em todos os templates
- ✅ Fallback para `_event_local` (legacy) quando necessário
- ✅ Validação defensiva: verifica se é array ou int

### ✅ `_event_timetable` (array)
- ✅ Usa `maybe_unserialize()` quando necessário
- ✅ Fallback para `_timetable` (legacy) quando vazio
- ✅ Validação: verifica se é array

### ✅ `_event_banner` (URL string)
- ✅ **CORRIGIDO:** Agora verifica URL primeiro antes de usar `wp_get_attachment_url()`
- ✅ Validação: `filter_var($event_banner, FILTER_VALIDATE_URL)` primeiro
- ✅ Fallback: se numérico, trata como attachment ID

---

## 🔧 Correções Aplicadas

### 1. ✅ content-event_listing.php
- ✅ Usa `_event_dj_ids` com `apollo_aem_parse_ids()` (correto - já faz unserialize)
- ✅ Usa `_event_local_ids` corretamente
- ✅ Banner: **CORRIGIDO** - verifica URL primeiro

### 2. ✅ event-card.php
- ✅ Usa `_event_dj_ids` com `apollo_aem_parse_ids()` (correto)
- ✅ Usa `_event_local_ids` corretamente
- ✅ Banner: **CORRIGIDO** - verifica URL primeiro

### 3. ✅ single-event.php
- ✅ **CORRIGIDO:** Adicionado busca de `_event_local_ids` no início
- ✅ **CORRIGIDO:** `_event_timetable` agora usa `maybe_unserialize()`
- ✅ Banner: **CORRIGIDO** - verifica URL primeiro (2 ocorrências)

### 4. ✅ single-event-standalone.php
- ✅ Usa `_event_local_ids` corretamente
- ✅ Usa `_event_timetable` corretamente
- ✅ Banner: **CORRIGIDO** - verifica URL primeiro

### 5. ✅ single-event-page.php
- ✅ Usa `_event_local_ids` corretamente
- ✅ Usa `_event_timetable` corretamente
- ✅ Banner: **CORRIGIDO** - verifica URL primeiro

### 6. ✅ portal-discover.php
- ✅ Usa `_event_dj_ids` com `maybe_unserialize()` diretamente
- ✅ Usa `_event_timetable` com `maybe_unserialize()` diretamente
- ✅ Usa `_event_local_ids` corretamente
- ✅ Banner: **CORRIGIDO** - verifica URL primeiro

### 7. ✅ event-listings-start.php
- ✅ Usa `_event_dj_ids` com `maybe_unserialize()` diretamente
- ✅ Usa `_event_timetable` com `maybe_unserialize()` diretamente
- ✅ Usa `_event_local_ids` corretamente
- ✅ Banner: **CORRIGIDO** - verifica URL primeiro

### 8. ✅ dj-card.php
- ✅ Usa `_event_dj_ids` na query (correto)
- N/A - Não precisa de correções

### 9. ✅ local-card.php
- N/A - Não usa meta keys de eventos diretamente

---

## 📝 Padrão de Validação Aplicado

### Banner URL (Padrão Aplicado)
```php
// ✅ CORRECT: Banner is URL string, NOT attachment ID
$banner_url = '';
if ($event_banner) {
    // Try as URL first (correct format)
    if (filter_var($event_banner, FILTER_VALIDATE_URL)) {
        $banner_url = $event_banner;
    } elseif (is_numeric($event_banner)) {
        // Fallback: if numeric, treat as attachment ID
        $banner_url = wp_get_attachment_url($event_banner);
    } else {
        // Try as string URL even if filter_var fails
        $banner_url = is_string($event_banner) ? $event_banner : '';
    }
}
```

### DJ IDs (Padrão Aplicado)
```php
// ✅ CORRECT: Use _event_dj_ids with maybe_unserialize()
$dj_ids_raw = get_post_meta($event_id, '_event_dj_ids', true);
if (!empty($dj_ids_raw)) {
    // ✅ CORRECT: Unserialize if needed
    $dj_ids = maybe_unserialize($dj_ids_raw);
    if (is_array($dj_ids)) {
        // Process DJ IDs...
    }
}
```

### Local IDs (Padrão Aplicado)
```php
// ✅ CORRECT: Get local ID from _event_local_ids (int)
$local_id = 0;
if (function_exists('apollo_get_primary_local_id')) {
    $local_id = apollo_get_primary_local_id($event_id);
}

if (!$local_id) {
    $local_ids_meta = get_post_meta($event_id, '_event_local_ids', true);
    if (!empty($local_ids_meta)) {
        $local_id = is_array($local_ids_meta) ? (int) reset($local_ids_meta) : (int) $local_ids_meta;
    }
    
    // Fallback legacy
    if (!$local_id) {
        $legacy = get_post_meta($event_id, '_event_local', true);
        $local_id = $legacy ? (int) $legacy : 0;
    }
}
```

### Timetable (Padrão Aplicado)
```php
// ✅ CORRECT: Use _event_timetable with maybe_unserialize()
$timetable_raw = get_post_meta($event_id, '_event_timetable', true);
$timetable = !empty($timetable_raw) ? maybe_unserialize($timetable_raw) : array();
// Fallback to legacy _timetable if empty
if (empty($timetable) || !is_array($timetable)) {
    $legacy_timetable = get_post_meta($event_id, '_timetable', true);
    $timetable = !empty($legacy_timetable) ? maybe_unserialize($legacy_timetable) : array();
}
```

---

## ✅ Validação Defensiva Adicionada

### Verificações Implementadas:
1. ✅ Verifica se meta key existe antes de usar
2. ✅ Usa `maybe_unserialize()` para arrays serializados
3. ✅ Valida tipos (is_array, is_numeric, is_string)
4. ✅ Valida URLs com `filter_var(FILTER_VALIDATE_URL)`
5. ✅ Fallbacks para meta keys legacy quando necessário
6. ✅ Verifica se posts existem e estão publicados
7. ✅ Verifica se funções existem antes de chamar (`function_exists()`)

---

## 📊 Resumo das Correções

| Template | `_event_dj_ids` | `_event_local_ids` | `_event_timetable` | `_event_banner` | Status |
|----------|----------------|-------------------|-------------------|----------------|--------|
| content-event_listing.php | ✅ | ✅ | ✅ | ✅ CORRIGIDO | ✅ |
| event-card.php | ✅ | ✅ | ✅ | ✅ CORRIGIDO | ✅ |
| single-event.php | ✅ | ✅ CORRIGIDO | ✅ CORRIGIDO | ✅ CORRIGIDO | ✅ |
| single-event-standalone.php | ✅ | ✅ | ✅ | ✅ CORRIGIDO | ✅ |
| single-event-page.php | ✅ | ✅ | ✅ | ✅ CORRIGIDO | ✅ |
| portal-discover.php | ✅ | ✅ | ✅ | ✅ CORRIGIDO | ✅ |
| event-listings-start.php | ✅ | ✅ | ✅ | ✅ CORRIGIDO | ✅ |
| dj-card.php | ✅ | N/A | N/A | N/A | ✅ |
| local-card.php | N/A | N/A | N/A | N/A | ✅ |

---

## ✅ Conclusão

**Todos os templates foram verificados e corrigidos:**

1. ✅ **Meta keys corretas** - Todos usando `_event_dj_ids`, `_event_local_ids`, `_event_timetable`
2. ✅ **Unserialize correto** - `maybe_unserialize()` aplicado onde necessário
3. ✅ **Banner URL** - Validação URL primeiro em todos os templates
4. ✅ **Validação defensiva** - Verificações de tipo e existência adicionadas
5. ✅ **Fallbacks** - Meta keys legacy como fallback quando necessário

**Status:** ✅ **100% CORRIGIDO**

---

**Data:** 15/01/2025  
**Arquivos Modificados:** 7 templates + 1 helper function

