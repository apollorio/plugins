# 🔍 Análise: Meta Keys nos Templates

**Data:** 15/01/2025  
**Status:** ⚠️ Correções Necessárias Identificadas

---

## 📋 Meta Keys Corretos (Conforme DEBUG_FINDINGS.md)

- ✅ `_event_dj_ids` (serialized array) - usar `maybe_unserialize()`
- ✅ `_event_local_ids` (int)
- ✅ `_event_timetable` (array)
- ✅ `_event_banner` (URL string, NÃO attachment ID)

---

## 🔍 Problemas Encontrados

### 1. ⚠️ content-event_listing.php
- ✅ Usa `_event_dj_ids` corretamente
- ⚠️ Usa `apollo_aem_parse_ids()` ao invés de `maybe_unserialize()` diretamente
- ✅ Banner tratado como URL primeiro (correto)
- ✅ `_event_local_ids` usado corretamente

### 2. ⚠️ event-card.php
- ✅ Usa `_event_dj_ids` corretamente
- ⚠️ Usa `apollo_aem_parse_ids()` ao invés de `maybe_unserialize()` diretamente
- ✅ Banner tratado como URL primeiro (correto)
- ✅ `_event_local_ids` usado corretamente

### 3. ⚠️ single-event.php
- ⚠️ Não verifica `_event_dj_ids` diretamente (usa `apollo_get_event_lineup()`)
- ⚠️ Banner: usa `is_numeric()` mas deveria verificar URL primeiro
- ✅ `_event_timetable` usado corretamente
- ⚠️ `_event_local_ids` não verificado diretamente

### 4. ❌ single-event-standalone.php
- ⚠️ Banner: usa `wp_get_attachment_url()` diretamente sem verificar se é URL primeiro
- ✅ `_event_timetable` usado corretamente
- ✅ `_event_local_ids` usado corretamente

### 5. ⚠️ single-event-page.php
- ⚠️ Banner: usa `is_numeric()` mas deveria verificar URL primeiro
- ✅ `_event_timetable` usado corretamente
- ✅ `_event_local_ids` usado corretamente

### 6. ⚠️ portal-discover.php
- ✅ Usa `maybe_unserialize()` corretamente para `_event_dj_ids`
- ⚠️ Banner: usa `is_numeric()` mas deveria verificar URL primeiro
- ✅ `_event_timetable` usado corretamente
- ✅ `_event_local_ids` usado corretamente

### 7. ✅ dj-card.php
- ✅ Usa `_event_dj_ids` na query (correto)
- N/A - Não precisa de correções (não lê meta keys de eventos diretamente)

### 8. ✅ local-card.php
- N/A - Não usa meta keys de eventos diretamente

---

## 🔧 Correções Necessárias

1. **Banner URL Validation:** Todos os templates devem verificar se `_event_banner` é URL primeiro antes de usar `wp_get_attachment_url()`
2. **DJ IDs Unserialize:** Verificar se `apollo_aem_parse_ids()` faz `maybe_unserialize()` corretamente
3. **Validação Defensiva:** Adicionar validações de tipo e existência

---

**Próximo Passo:** Aplicar correções nos templates

