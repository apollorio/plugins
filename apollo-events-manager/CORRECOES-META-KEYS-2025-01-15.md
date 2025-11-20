# ✅ Correções de Meta Keys - 15/01/2025

## Problema Identificado

As funções de salvamento estavam usando meta keys incorretas ou estruturas de dados inconsistentes:

1. **`save_custom_event_fields()`** - Já estava correta, mas `_event_local_ids` era salvo como array
2. **`save_metabox_data()`** - Salva `_event_local_ids` como array e também `_event_local` como legacy

## Correções Aplicadas

### 1. ✅ `admin-metaboxes.php` - Função `save_metabox_data()`

**Linha 730-746:**
- ❌ **ANTES:** Salvava `_event_local_ids` como `array($local_selected)` e também `_event_local` como legacy
- ✅ **DEPOIS:** Salva apenas `_event_local_ids` como `int` único (não array)
- ✅ **Removido:** Salvamento duplicado de `_event_local` (não é mais necessário)

**Código corrigido:**
```php
// ✅ SAVE LOCAL - CRITICAL
$local_selected = isset($_POST['apollo_event_local']) ? absint($_POST['apollo_event_local']) : 0;

if ($local_selected > 0) {
    // Save as single integer (not array) - consistent with database structure
    update_post_meta($post_id, '_event_local_ids', $local_selected);
} else {
    delete_post_meta($post_id, '_event_local_ids');
}
```

### 2. ✅ `apollo-events-manager.php` - Função `save_custom_event_fields()`

**Linha 1562-1572:**
- ❌ **ANTES:** Salvava `_event_local_ids` como array mesmo quando era um único valor
- ✅ **DEPOIS:** Salva `_event_local_ids` como `int` único, com suporte a array para backward compatibility

**Código corrigido:**
```php
// Save local relationship as single integer (not array)
$posted_local = isset($_POST['event_local']) ? wp_unslash($_POST['event_local']) : null;
if ($posted_local !== null) {
    // Handle both single value and array (for backward compatibility)
    $local_id = is_array($posted_local) ? (int) reset($posted_local) : (int) $posted_local;
    if ($local_id > 0) {
        update_post_meta($post_id, '_event_local_ids', $local_id);
    } else {
        delete_post_meta($post_id, '_event_local_ids');
    }
}
```

## Meta Keys Corretos (Confirmados)

### Eventos (`event_listing`)
- ✅ `_event_dj_ids` - Array serialized de IDs de DJs (strings ou ints)
- ✅ `_event_local_ids` - **Int único** (não array)
- ✅ `_event_timetable` - Array validado com `apollo_sanitize_timetable()`

### ❌ Meta Keys Antigas (NÃO USAR)
- ❌ `_event_djs` - Removido, usar `_event_dj_ids`
- ❌ `_event_local` - Removido, usar `_event_local_ids`

## Verificações Realizadas

1. ✅ `save_custom_event_fields()` usa `_event_dj_ids` corretamente
2. ✅ `save_custom_event_fields()` usa `_event_local_ids` como int único
3. ✅ `save_custom_event_fields()` usa `_event_timetable` com validação
4. ✅ `save_metabox_data()` usa `_event_dj_ids` corretamente
5. ✅ `save_metabox_data()` usa `_event_local_ids` como int único
6. ✅ Removido salvamento duplicado de `_event_local`
7. ✅ Nenhum código salva mais em `_event_djs` ou `_event_local`

## Hooks de Salvamento

### Ordem de Execução:
1. **`save_custom_event_fields()`** - Prioridade 10
   - Hook: `save_post_event_listing`
   - Executa para campos do frontend (`event_djs`, `event_local`, `timetable`)
   - Usa meta keys corretas ✅

2. **`save_metabox_data()`** - Prioridade 20
   - Hook: `save_post_event_listing`
   - Executa para campos do admin (`apollo_event_djs`, `apollo_event_local`, `apollo_event_timetable`)
   - Usa meta keys corretas ✅

### Hook Antigo Removido:
- ❌ `event_manager_save_event_listing` - Removido na linha 139 via `apollo_disable_legacy_event_saver()`

## Próximos Passos

1. ✅ **Concluído:** Correção de meta keys
2. ⏭️ **Próximo:** Testar salvamento de eventos no admin e frontend
3. ⏭️ **Próximo:** Verificar se templates estão usando as meta keys corretas
4. ⏭️ **Próximo:** Executar migração de dados antigos se necessário

## Testes Recomendados

### Teste 1: Salvar Evento no Admin
1. Criar/editar evento no admin
2. Selecionar DJs e Local
3. Preencher timetable
4. Salvar
5. Verificar no banco:
   - `_event_dj_ids` existe e é array serialized
   - `_event_local_ids` existe e é int único
   - `_event_timetable` existe e é array
   - `_event_djs` NÃO existe
   - `_event_local` NÃO existe

### Teste 2: Salvar Evento no Frontend
1. Preencher formulário de evento no frontend
2. Selecionar DJs e Local
3. Preencher timetable
4. Submeter
5. Verificar no banco (mesmas verificações do Teste 1)

### Teste 3: Verificar Exibição
1. Visualizar evento salvo
2. Verificar se DJs aparecem corretamente
3. Verificar se Local aparece corretamente
4. Verificar se timetable aparece ordenado

## Status

✅ **CORREÇÕES APLICADAS COM SUCESSO**

- Meta keys corrigidas
- Estrutura de dados consistente
- Removido código duplicado/legacy
- Pronto para testes

---

**Data:** 15/01/2025  
**Arquivos Modificados:**
- `apollo-events-manager/apollo-events-manager.php` (linhas 1562-1572)
- `apollo-events-manager/includes/admin-metaboxes.php` (linhas 730-746)

