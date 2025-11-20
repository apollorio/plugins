# ✅ Correções de Templates - Meta Keys Corretas - 15/01/2025

## Objetivo

Corrigir todos os templates para usar as meta keys corretas e adicionar verificações defensivas.

## Meta Keys Corretos

- ✅ `_event_dj_ids` (serialized array) - usar `maybe_unserialize()`
- ✅ `_event_local_ids` (int único) - não array
- ✅ `_event_timetable` (array) - usar `apollo_sanitize_timetable()`
- ✅ `_event_banner` (URL string) - NÃO é attachment ID

## Meta Keys Antigas (NÃO USAR)

- ❌ `_event_djs` - usar `_event_dj_ids`
- ❌ `_event_local` - usar `_event_local_ids` (apenas fallback legacy)
- ❌ `_timetable` - usar `_event_timetable` (apenas fallback legacy)

---

## Templates Corrigidos

### 1. ✅ content-event_listing.php

**Correções:**
- ✅ Adicionada verificação `function_exists('apollo_get_primary_local_id')` antes de usar
- ✅ Fallback para `_event_local_ids` direto se função não existir
- ✅ Fallback legacy para `_event_local` mantido
- ✅ Banner já verifica se é URL antes de usar `wp_get_attachment_url()`

**Status:** ✅ Corrigido

---

### 2. ✅ event-card.php

**Correções:**
- ✅ Adicionada verificação `function_exists('apollo_get_primary_local_id')` antes de usar
- ✅ Fallback para `_event_local_ids` direto se função não existir
- ✅ Fallback legacy para `_event_local` mantido
- ✅ Banner já verifica se é URL antes de usar `wp_get_attachment_url()`

**Status:** ✅ Corrigido

---

### 3. ✅ single-event.php

**Correções:**
- ✅ Usa `_event_timetable` corretamente
- ✅ Banner verifica se é URL antes de usar `wp_get_attachment_url()`
- ✅ Já tem verificações `function_exists()` para funções de apollo-social

**Status:** ✅ Já estava correto

---

### 4. ✅ single-event-standalone.php

**Correções:**
- ✅ Corrigido para usar `_event_timetable` primeiro, depois fallback para `_timetable`
- ✅ `_event_local_ids` agora trata int e array corretamente
- ✅ Banner já verifica se é URL antes de usar `wp_get_attachment_url()`

**Status:** ✅ Corrigido

---

### 5. ✅ single-event-page.php

**Correções:**
- ✅ Adicionada verificação `function_exists('apollo_get_primary_local_id')` antes de usar
- ✅ Fallback para `_event_local_ids` direto se função não existir
- ✅ Fallback legacy para `_event_local` mantido
- ✅ Banner já verifica se é URL antes de usar `wp_get_attachment_url()`

**Status:** ✅ Corrigido

---

### 6. ✅ portal-discover.php

**Correções:**
- ✅ Já usa `_event_timetable` corretamente
- ✅ Banner já verifica se é URL antes de usar `wp_get_attachment_url()`

**Status:** ✅ Já estava correto

---

### 7. ✅ event-listings-start.php

**Correções:**
- ✅ Corrigido para usar `_event_timetable` primeiro, depois fallback para `_timetable`
- ✅ Adicionada verificação `function_exists('apollo_get_primary_local_id')` antes de usar
- ✅ Fallback para `_event_local_ids` direto se função não existir
- ✅ Fallback legacy para `_event_local` mantido
- ✅ Banner já verifica se é URL antes de usar `wp_get_attachment_url()`

**Status:** ✅ Corrigido

---

### 8. ✅ dj-card.php

**Correções:**
- ✅ Já usa `_event_dj_ids` corretamente na query (linha 56)
- ✅ Não precisa de correções adicionais

**Status:** ✅ Já estava correto

---

### 9. ✅ local-card.php

**Correções:**
- ✅ Não usa meta keys de eventos diretamente
- ✅ Não precisa de correções

**Status:** ✅ Já estava correto

---

## Padrão de Correção Aplicado

### Para Local IDs:

```php
// Verificar se função existe antes de usar
$local_id = function_exists('apollo_get_primary_local_id') 
    ? apollo_get_primary_local_id($event_id) 
    : 0;

if (!$local_id) {
    // Fallback: usar meta key direto
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

### Para Timetable:

```php
// ✅ CORRECT: Use _event_timetable first, then fallback to _timetable
$event_timetable = get_post_meta($event_id, '_event_timetable', true);
if (empty($event_timetable)) {
    $event_timetable = get_post_meta($event_id, '_timetable', true); // Fallback legacy
}
$timetable = apollo_sanitize_timetable($event_timetable);
```

### Para Banner:

```php
// ✅ CORRECT: Banner is URL, not attachment ID
$banner_url = '';
if ($event_banner && filter_var($event_banner, FILTER_VALIDATE_URL)) {
    $banner_url = $event_banner; // It's already a URL!
} elseif ($event_banner && is_numeric($event_banner)) {
    $banner_url = wp_get_attachment_url($event_banner); // Fallback if numeric
}
```

---

## Verificações Defensivas Adicionadas

### 1. ✅ apollo-events-manager.php

**Linha 1873:**
- ✅ Adicionada verificação `function_exists('apollo_get_top_users_by_interactions')` antes de chamar

### 2. ✅ includes/class-apollo-events-placeholders.php

**Linha 853:**
- ✅ Adicionada verificação `function_exists('apollo_get_primary_local_id')` antes de usar
- ✅ Fallback para meta key direto se função não existir

### 3. ✅ Todos os Templates

- ✅ Verificações `function_exists()` adicionadas antes de usar funções de outros plugins
- ✅ Fallbacks defensivos implementados

---

## Resumo das Correções

| Template | Meta Keys | Verificações Defensivas | Banner | Status |
|----------|-----------|------------------------|--------|--------|
| content-event_listing.php | ✅ | ✅ | ✅ | ✅ Corrigido |
| event-card.php | ✅ | ✅ | ✅ | ✅ Corrigido |
| single-event.php | ✅ | ✅ | ✅ | ✅ OK |
| single-event-standalone.php | ✅ | ✅ | ✅ | ✅ Corrigido |
| single-event-page.php | ✅ | ✅ | ✅ | ✅ Corrigido |
| portal-discover.php | ✅ | ✅ | ✅ | ✅ OK |
| event-listings-start.php | ✅ | ✅ | ✅ | ✅ Corrigido |
| dj-card.php | ✅ | N/A | N/A | ✅ OK |
| local-card.php | N/A | N/A | N/A | ✅ OK |

---

## Benefícios

1. ✅ **Meta Keys Consistentes:** Todos os templates usam as keys corretas
2. ✅ **Validação Defensiva:** Verificações antes de usar funções de outros plugins
3. ✅ **Fallbacks Robustos:** Múltiplos níveis de fallback para compatibilidade
4. ✅ **Banner Correto:** Verifica se é URL antes de tratar como attachment ID
5. ✅ **Backward Compatibility:** Mantém suporte a keys antigas como fallback

---

## Arquivos Modificados

1. ✅ `apollo-events-manager/apollo-events-manager.php` (linha 1873)
2. ✅ `apollo-events-manager/includes/class-apollo-events-placeholders.php` (linha 853)
3. ✅ `apollo-events-manager/templates/content-event_listing.php` (linha 35)
4. ✅ `apollo-events-manager/templates/event-card.php` (linha 50)
5. ✅ `apollo-events-manager/templates/single-event-standalone.php` (linhas 28-46)
6. ✅ `apollo-events-manager/templates/single-event-page.php` (linha 29)
7. ✅ `apollo-events-manager/templates/event-listings-start.php` (linhas 192, 227)

---

## Status

✅ **TODOS OS TEMPLATES CORRIGIDOS COM SUCESSO!**

- Meta keys corretas implementadas
- Verificações defensivas adicionadas
- Fallbacks robustos implementados
- Pronto para testes

---

**Data:** 15/01/2025  
**Próximo Passo:** Testar exibição de eventos nos templates

