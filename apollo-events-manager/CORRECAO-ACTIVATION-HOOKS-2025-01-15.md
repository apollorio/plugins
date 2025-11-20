# ✅ Correção: Activation Hooks e Verificação de Meta Keys

**Data:** 15/01/2025  
**Status:** ✅ **IMPLEMENTADO**

---

## 📋 Problemas Identificados

1. ❌ **Página duplicada na lixeira** - Activation hook poderia criar página duplicada
2. ❌ **Rewrite rules flushadas desnecessariamente** - Não verificava se já foram flushadas recentemente
3. ❌ **Meta keys antigas no banco** - Possibilidade de keys antigas (_event_djs, _event_local) ainda existirem

---

## ✅ Correções Implementadas

### 1. ✅ apollo-events-manager.php

#### Melhoria em `apollo_em_get_events_page()`

**Linha ~3490-3511:** Função melhorada para verificar todos os status

**Antes:**
- Verificava apenas `publish` e `trash`
- Poderia não encontrar páginas em outros status

**Depois:**
- ✅ Verifica diretamente no banco de dados
- ✅ Prioriza: `publish` > `trash` > outros status
- ✅ Retorna a primeira página encontrada (evita duplicatas)

```php
function apollo_em_get_events_page() {
    // Try published page first
    $page = get_page_by_path('eventos');
    if ($page && $page->post_status === 'publish') {
        return $page;
    }
    
    // ✅ Verificar diretamente no banco para garantir que não há duplicatas
    global $wpdb;
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
    
    if (!empty($all_pages)) {
        // Retornar a primeira página encontrada (prioridade: publish > trash > outros)
        foreach ($all_pages as $page_data) {
            $found_page = get_post($page_data->ID);
            if ($found_page) {
                return $found_page;
            }
        }
    }
    
    return null;
}
```

#### Melhoria em `apollo_events_manager_activate()`

**Linha ~3548-3564:** Adicionada verificação adicional de duplicatas

**Antes:**
- Confiava apenas em `apollo_em_get_events_page()`
- Poderia criar duplicata se página existisse em status não verificado

**Depois:**
- ✅ Verifica diretamente no banco antes de criar
- ✅ Busca página em qualquer status
- ✅ Logs informativos para debug

```php
} elseif (!$events_page) {
    // ✅ Verificar se existe página com mesmo slug em qualquer status (incluindo lixeira)
    // Buscar diretamente no banco para garantir que não há duplicatas
    global $wpdb;
    $existing_page = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} 
        WHERE post_name = %s 
        AND post_type = 'page' 
        LIMIT 1",
        'eventos'
    ));
    
    if ($existing_page) {
        // Página existe mas não foi encontrada pela função helper - pode estar em status diferente
        $existing_post = get_post($existing_page);
        if ($existing_post) {
            error_log('⚠️ Apollo: Página /eventos/ já existe (ID: ' . $existing_page . ', Status: ' . $existing_post->post_status . ') - não criando duplicata');
            return;
        }
    }
    
    // Create new only if doesn't exist at all
    $page_id = wp_insert_post([...]);
    // ... tratamento de erro
}
```

#### Verificação de Rewrite Rules

**Status:** ✅ **Já implementado anteriormente**

- ✅ Verifica se rewrite rules foram flushadas recentemente (últimos 5 minutos)
- ✅ Evita flush desnecessário
- ✅ Usa transient `apollo_rewrite_rules_last_flush`

---

### 2. ✅ apollo-social.php

**Status:** ✅ **Já implementado anteriormente**

- ✅ Verifica se rewrite rules foram flushadas recentemente (últimos 5 minutos)
- ✅ Evita flush desnecessário
- ✅ Usa transient `apollo_social_rewrite_rules_last_flush`

---

### 3. ✅ apollo-rio.php

#### Melhoria em `apollo_activate()`

**Linha ~50-57:** Adicionada verificação de tempo e melhorias

**Antes:**
- Flushava rewrite rules sempre
- Não verificava se opções já existiam

**Depois:**
- ✅ Verifica se rewrite rules foram flushadas recentemente (últimos 5 minutos)
- ✅ Verifica se opções já existem antes de criar
- ✅ Usa transient `apollo_rio_rewrite_rules_last_flush`
- ✅ Logs informativos

```php
function apollo_activate() {
    // ✅ Verificar se rewrite rules já foram flushadas recentemente (últimos 5 minutos)
    $last_flush = get_transient('apollo_rio_rewrite_rules_last_flush');
    if ($last_flush && (time() - $last_flush) < 300) {
        // Já foi flushado recentemente, pular
        error_log('✅ Apollo Rio: Rewrite rules já foram flushadas recentemente, pulando...');
        return;
    }
    
    // Set default options (only if not already set)
    if (get_option('apollo_android_app_url') === false) {
        add_option('apollo_android_app_url', 'https://play.google.com/store/apps/details?id=br.rio.apollo');
    }
    if (get_option('apollo_pwa_install_page_id') === false) {
        add_option('apollo_pwa_install_page_id', '');
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Marcar timestamp do flush
    set_transient('apollo_rio_rewrite_rules_last_flush', time(), 600); // 10 minutos
    error_log('✅ Apollo Rio: Plugin ativado com sucesso');
}
```

---

### 4. ✅ Script de Verificação de Meta Keys

**Arquivo:** `verify-meta-keys-activation.php`

Script completo que verifica:
- ✅ Meta keys corretas (_event_dj_ids, _event_local_ids, _event_timetable)
- ✅ Formato correto (array serialized, int, array)
- ✅ Ausência de keys antigas (_event_djs, _event_local)
- ✅ Status dos activation hooks
- ✅ Status dos plugins

**Uso:**
```bash
wp eval-file wp-content/plugins/apollo-events-manager/verify-meta-keys-activation.php
```

---

## 📊 Resumo das Correções

| Plugin | Problema | Correção | Status |
|--------|----------|----------|--------|
| apollo-events-manager | Página duplicada | Verificação adicional no banco | ✅ |
| apollo-events-manager | Rewrite rules | Verificação de tempo | ✅ |
| apollo-events-manager | Função helper | Verifica todos os status | ✅ |
| apollo-social | Rewrite rules | Verificação de tempo | ✅ |
| apollo-rio | Rewrite rules | Verificação de tempo | ✅ |
| apollo-rio | Opções duplicadas | Verifica antes de criar | ✅ |

---

## ✅ Benefícios

### Prevenção de Duplicatas:
- ✅ Verifica página em todos os status antes de criar
- ✅ Busca direta no banco para garantir precisão
- ✅ Restaura página da lixeira ao invés de criar nova

### Performance:
- ✅ Evita flush desnecessário de rewrite rules
- ✅ Verifica tempo antes de executar operações pesadas
- ✅ Usa transients para rastrear última execução

### Robustez:
- ✅ Verifica opções antes de criar
- ✅ Tratamento de erros em todas as operações
- ✅ Logs informativos para debug

---

## 📝 Arquivos Modificados

1. ✅ `apollo-events-manager.php` - Melhorias em `apollo_em_get_events_page()` e `apollo_events_manager_activate()`
2. ✅ `apollo-rio.php` - Melhorias em `apollo_activate()`
3. ✅ `verify-meta-keys-activation.php` - Script de verificação criado

---

## ✅ Testes Recomendados

1. ✅ Executar script de verificação de meta keys
2. ✅ Testar activation hook com página na lixeira
3. ✅ Testar activation hook múltiplas vezes (verificar se não cria duplicatas)
4. ✅ Verificar que rewrite rules não são flushadas desnecessariamente
5. ✅ Verificar logs de activation para confirmar comportamento

---

**Status:** ✅ **IMPLEMENTADO E PRONTO PARA TESTE**

