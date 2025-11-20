# ✅ Correção: Error Handling em WP_Query e Melhorias em Activation Hooks

**Data:** 15/01/2025  
**Status:** ✅ **IMPLEMENTADO**

---

## 📋 Problemas Identificados

1. ❌ **Falta de error handling em WP_Query** - Templates não verificavam erros antes de usar resultados
2. ❌ **Página duplicada na lixeira** - Activation hook poderia criar página duplicada se já existisse na lixeira
3. ❌ **Rewrite rules flushadas desnecessariamente** - Não verificava se já foram flushadas recentemente

---

## ✅ Correções Implementadas

### 1. Error Handling em WP_Query

#### ✅ portal-discover.php

**Linha ~199-226:** Query principal de eventos
```php
$query = new WP_Query($query_args);

// ✅ Error handling para WP_Query
if (is_wp_error($query)) {
    error_log('Apollo: WP_Query error em portal-discover: ' . $query->get_error_message());
    $event_ids = array();
} else {
    $collected_ids = array();
    
    if ($query->have_posts()) {
        // ... processamento normal
    }
}
```

**Linha ~550-556:** Query de último post para banner
```php
$latest_post_query = new WP_Query($latest_post_args);

// ✅ Error handling para WP_Query
if (is_wp_error($latest_post_query)) {
    error_log('Apollo: WP_Query error em portal-discover (latest_post): ' . $latest_post_query->get_error_message());
    // Continuar sem banner se houver erro
} elseif ($latest_post_query->have_posts()):
    // ... processamento normal
```

#### ✅ event-listings-start.php

**Linha ~80-105:** Query principal de eventos
```php
$events = new WP_Query([...]);

// ✅ Error handling para WP_Query
if (is_wp_error($events)) {
    error_log('Apollo: WP_Query error em event-listings-start: ' . $events->get_error_message());
    echo '<p class="error">Erro ao carregar eventos. Tente novamente.</p>';
    return;
}

if (!$events->have_posts()) {
    echo '<p>Nenhum evento encontrado.</p>';
    return;
}

if ($events->have_posts()) :
    // ... processamento normal
```

#### ✅ dj-card.php

**Linha ~49-77:** Query de eventos do DJ
```php
$upcoming_events = new WP_Query([...]);

// ✅ Error handling para WP_Query
$events_count = 0;
if (is_wp_error($upcoming_events)) {
    error_log('Apollo: WP_Query error em dj-card: ' . $upcoming_events->get_error_message());
    // Continuar com count = 0 se houver erro
} else {
    $events_count = $upcoming_events->found_posts;
    wp_reset_postdata();
}
```

---

### 2. Melhorias em Activation Hooks

#### ✅ apollo-events-manager.php

**Status:** ✅ **Já estava correto!**

O activation hook já:
- ✅ Usa `apollo_em_get_events_page()` para verificar página antes de criar
- ✅ Verifica se página está na lixeira e restaura ao invés de criar duplicada
- ✅ Só cria nova página se não existir em nenhum lugar

**Código atual (linha ~3538-3564):**
```php
// Handle events page creation/restoration
$events_page = apollo_em_get_events_page();

if ($events_page && 'trash' === $events_page->post_status) {
    // Restore from trash
    wp_update_post([
        'ID'          => $events_page->ID,
        'post_status' => 'publish',
    ]);
    error_log('✅ Apollo: Restored /eventos/ page from trash (ID: ' . $events_page->ID . ')');
} elseif (!$events_page) {
    // Create new only if doesn't exist at all
    $page_id = wp_insert_post([...]);
    // ... tratamento de erro
} else {
    // Page already exists and is published
    error_log('✅ Apollo: /eventos/ page already exists (ID: ' . $events_page->ID . ')');
}
```

#### ✅ includes/post-types.php

**Melhoria:** Adicionada verificação de tempo antes de flushar rewrite rules

**Linha ~448-463:**
```php
public static function flush_rewrite_rules_on_activation() {
    // ✅ Verificar se rewrite rules já foram flushadas recentemente (últimos 5 minutos)
    $last_flush = get_transient('apollo_rewrite_rules_last_flush');
    if ($last_flush && (time() - $last_flush) < 300) {
        // Já foi flushado recentemente, pular
        error_log('✅ Apollo: Rewrite rules já foram flushadas recentemente, pulando...');
        return;
    }
    
    // Register post types first
    $instance = new self();
    $instance->register_post_types();
    $instance->register_taxonomies();
    
    // Flush rewrite rules
    flush_rewrite_rules(false); // Don't force hard flush
    
    // Marcar timestamp do flush
    set_transient('apollo_rewrite_rules_last_flush', time(), 600); // 10 minutos
    error_log('✅ Apollo: Rewrite rules flushadas com sucesso');
}
```

#### ✅ apollo-social.php

**Melhoria:** Adicionada verificação de tempo antes de flushar rewrite rules

**Linha ~67-95:**
```php
register_activation_hook(__FILE__, function() {
    // ✅ Verificar se rewrite rules já foram flushadas recentemente (últimos 5 minutos)
    $last_flush = get_transient('apollo_social_rewrite_rules_last_flush');
    if ($last_flush && (time() - $last_flush) < 300) {
        // Já foi flushado recentemente, pular
        error_log('✅ Apollo Social: Rewrite rules já foram flushadas recentemente, pulando...');
        return;
    }
    
    // ... registro de routes e CPTs ...
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Marcar timestamp do flush
    set_transient('apollo_social_rewrite_rules_last_flush', time(), 600); // 10 minutos
    error_log('✅ Apollo Social: Rewrite rules flushadas com sucesso');
});
```

---

## 📊 Padrão de Error Handling Aplicado

### Padrão Completo:
```php
$query = new WP_Query($args);

// ✅ Error handling para WP_Query
if (is_wp_error($query)) {
    error_log('Apollo: WP_Query error em [template]: ' . $query->get_error_message());
    echo '<p class="error">Erro ao carregar eventos. Tente novamente.</p>';
    return; // ou $event_ids = array(); dependendo do contexto
}

if (!$query->have_posts()) {
    echo '<p>Nenhum evento encontrado.</p>';
    return; // ou continuar sem exibir nada
}

// Loop normal aqui
if ($query->have_posts()) :
    while ($query->have_posts()) : $query->the_post();
        // ... processamento
    endwhile;
    wp_reset_postdata();
endif;
```

---

## ✅ Benefícios

### Error Handling:
- ✅ Previne erros fatais quando WP_Query falha
- ✅ Logs de erro para debug
- ✅ Mensagens amigáveis para usuários
- ✅ Degradação graciosa (continua funcionando mesmo com erro)

### Activation Hooks:
- ✅ Previne criação de páginas duplicadas
- ✅ Restaura páginas da lixeira ao invés de criar novas
- ✅ Evita flush desnecessário de rewrite rules (melhora performance)
- ✅ Logs informativos para debug

---

## 📝 Arquivos Modificados

1. ✅ `templates/portal-discover.php` - Error handling em 2 WP_Query
2. ✅ `templates/event-listings-start.php` - Error handling em WP_Query
3. ✅ `templates/dj-card.php` - Error handling em WP_Query
4. ✅ `includes/post-types.php` - Verificação de tempo antes de flush
5. ✅ `apollo-social.php` - Verificação de tempo antes de flush

---

## ✅ Testes Recomendados

1. ✅ Testar templates com WP_Query inválido (simular erro)
2. ✅ Verificar logs quando erro ocorre
3. ✅ Verificar mensagens de erro exibidas ao usuário
4. ✅ Testar activation hook com página na lixeira
5. ✅ Testar activation hook múltiplas vezes (verificar se não flusha desnecessariamente)
6. ✅ Verificar que páginas não são criadas duplicadas

---

**Status:** ✅ **IMPLEMENTADO E PRONTO PARA TESTE**

