# 🧪 FINAL TEST PLAN - Apollo Events Manager
**Data:** 5 de Novembro de 2025  
**Ambiente:** Local by Flywheel  
**Database:** MySQL 8.0.35 (localhost:10005)  
**WordPress:** 6.8.3  
**XDebug:** 3.2.1 (ENABLED)  
**Site URL:** http://localhost:10004

---

## 🎯 OBJETIVO

Verificar se todas as correções estão funcionando com dados reais do banco de dados:
1. ✅ Event cards exibindo DJs e Locais
2. ✅ Modal abrindo corretamente
3. ✅ Dashboards acessíveis
4. ✅ Shortcodes retornando dados
5. ✅ Placeholders funcionando
6. ✅ REST API sem erros

---

## 📋 TO-DO LIST (Executar em Sequência)

### FASE 1: DATABASE INVESTIGATION 🔍

#### ✅ TODO 1.1: Verificar dados de Event Listings
**Objetivo:** Confirmar que temos eventos com DJs e Locais no banco

**SQL Query:**
```sql
-- Verificar eventos publicados
SELECT 
    p.ID,
    p.post_title,
    p.post_status,
    (SELECT meta_value FROM wp_postmeta WHERE post_id = p.ID AND meta_key = '_event_start_date' LIMIT 1) as start_date,
    (SELECT meta_value FROM wp_postmeta WHERE post_id = p.ID AND meta_key = '_event_dj_ids' LIMIT 1) as dj_ids,
    (SELECT meta_value FROM wp_postmeta WHERE post_id = p.ID AND meta_key = '_event_local_ids' LIMIT 1) as local_ids,
    (SELECT meta_value FROM wp_postmeta WHERE post_id = p.ID AND meta_key = '_event_location' LIMIT 1) as location_string
FROM wp_posts p
WHERE p.post_type = 'event_listing'
AND p.post_status = 'publish'
ORDER BY p.ID DESC
LIMIT 10;
```

**Expected Output:**
- Pelo menos 1 evento publicado
- Pelo menos 1 evento com `dj_ids` OU `location_string` preenchido

**Status:** [ ] Pending

---

#### ✅ TODO 1.2: Verificar dados de DJs
**Objetivo:** Confirmar que temos DJs cadastrados

**SQL Query:**
```sql
-- Verificar DJs
SELECT 
    p.ID,
    p.post_title,
    p.post_status,
    (SELECT meta_value FROM wp_postmeta WHERE post_id = p.ID AND meta_key = '_dj_name' LIMIT 1) as dj_name,
    (SELECT meta_value FROM wp_postmeta WHERE post_id = p.ID AND meta_key = '_dj_bio' LIMIT 1) as dj_bio
FROM wp_posts p
WHERE p.post_type = 'event_dj'
AND p.post_status = 'publish'
ORDER BY p.ID DESC
LIMIT 10;
```

**Expected Output:**
- Pelo menos 1 DJ publicado
- Nome do DJ preenchido em `_dj_name` ou `post_title`

**Status:** [ ] Pending

---

#### ✅ TODO 1.3: Verificar dados de Locais
**Objetivo:** Confirmar que temos locais cadastrados

**SQL Query:**
```sql
-- Verificar Locais
SELECT 
    p.ID,
    p.post_title,
    p.post_status,
    (SELECT meta_value FROM wp_postmeta WHERE post_id = p.ID AND meta_key = '_local_name' LIMIT 1) as local_name,
    (SELECT meta_value FROM wp_postmeta WHERE post_id = p.ID AND meta_key = '_local_city' LIMIT 1) as local_city,
    (SELECT meta_value FROM wp_postmeta WHERE post_id = p.ID AND meta_key = '_local_address' LIMIT 1) as local_address
FROM wp_posts p
WHERE p.post_type = 'event_local'
AND p.post_status = 'publish'
ORDER BY p.ID DESC
LIMIT 10;
```

**Expected Output:**
- Pelo menos 1 local publicado
- Nome do local preenchido em `_local_name` ou `post_title`

**Status:** [ ] Pending

---

#### ✅ TODO 1.4: Verificar relacionamentos Event → DJ
**Objetivo:** Confirmar que eventos estão linkados a DJs

**SQL Query:**
```sql
-- Verificar relacionamentos Event → DJ
SELECT 
    e.ID as event_id,
    e.post_title as event_title,
    m.meta_value as dj_ids_serialized,
    d.ID as dj_id,
    d.post_title as dj_title,
    (SELECT meta_value FROM wp_postmeta WHERE post_id = d.ID AND meta_key = '_dj_name' LIMIT 1) as dj_name
FROM wp_posts e
LEFT JOIN wp_postmeta m ON e.ID = m.post_id AND m.meta_key = '_event_dj_ids'
LEFT JOIN wp_posts d ON FIND_IN_SET(d.ID, REPLACE(REPLACE(REPLACE(m.meta_value, 'a:', ''), 's:', ''), '"', ''))
WHERE e.post_type = 'event_listing'
AND e.post_status = 'publish'
AND m.meta_value IS NOT NULL
LIMIT 20;
```

**Expected Output:**
- Eventos com DJs linkados
- Se não houver, precisamos criar manualmente

**Status:** [ ] Pending

---

#### ✅ TODO 1.5: Verificar relacionamentos Event → Local
**Objetivo:** Confirmar que eventos estão linkados a locais

**SQL Query:**
```sql
-- Verificar relacionamentos Event → Local
SELECT 
    e.ID as event_id,
    e.post_title as event_title,
    m.meta_value as local_id,
    l.ID as local_post_id,
    l.post_title as local_title,
    (SELECT meta_value FROM wp_postmeta WHERE post_id = l.ID AND meta_key = '_local_name' LIMIT 1) as local_name
FROM wp_posts e
LEFT JOIN wp_postmeta m ON e.ID = m.post_id AND m.meta_key = '_event_local_ids'
LEFT JOIN wp_posts l ON l.ID = m.meta_value
WHERE e.post_type = 'event_listing'
AND e.post_status = 'publish'
AND m.meta_value IS NOT NULL
LIMIT 20;
```

**Expected Output:**
- Eventos com locais linkados
- Se não houver, verificar `_event_location` string

**Status:** [ ] Pending

---

### FASE 2: FRONTEND TESTING 🎨

#### ✅ TODO 2.1: Testar /eventos/ - Event Cards Display
**Objetivo:** Verificar se event cards exibem DJs e Local

**URL:** http://localhost:10004/eventos/

**Checklist:**
- [ ] Página carrega sem erros
- [ ] Event cards aparecem no grid
- [ ] **Cada card exibe:**
  - [ ] Título do evento
  - [ ] Data (dia + mês PT-BR)
  - [ ] Banner image
  - [ ] **DJs:** Pelo menos "Line-up em breve" ou nomes reais
  - [ ] **Local:** Nome do local + área (se disponível)
  - [ ] Tags/sounds (se houver)

**XDebug Breakpoints (se necessário):**
- `portal-discover.php:230` - Após query de eventos
- `portal-discover.php:240` - Parse de DJs
- `portal-discover.php:340` - Parse de Local

**Expected Output:**
- Exemplo: "**Marta Supernova**, Leo Janeiro, MMarilds +3 DJs"
- Exemplo: "D-Edge <opacity>(Centro, RJ)</opacity>"

**Status:** [ ] Pending

---

#### ✅ TODO 2.2: Testar Modal/Lightbox Opening
**Objetivo:** Verificar se modal abre ao clicar no event card

**Steps:**
1. Ir para http://localhost:10004/eventos/
2. Clicar em qualquer event card
3. Observar se modal abre

**Checklist:**
- [ ] Modal abre com animação
- [ ] Conteúdo do evento aparece:
  - [ ] Banner
  - [ ] Título
  - [ ] Data
  - [ ] DJs (se disponível)
  - [ ] Local (se disponível)
  - [ ] Descrição
- [ ] Botão "X" fecha o modal
- [ ] Clicar fora do modal fecha
- [ ] ESC fecha o modal

**Browser Console Check:**
- Verificar se há erros JavaScript
- Verificar se AJAX call retorna `success: true`

**XDebug Breakpoints (se necessário):**
- `apollo-events-manager.php:988` - `ajax_get_event_modal()` start
- `apollo-events-manager.php:1050` - Modal HTML generation

**Status:** [ ] Pending

---

#### ✅ TODO 2.3: Testar Admin Dashboards
**Objetivo:** Verificar se dashboards são acessíveis

**URLs:**
1. http://localhost:10004/wp-admin/admin.php?page=apollo-events-dashboard
2. http://localhost:10004/wp-admin/admin.php?page=apollo-events-user-overview
3. http://localhost:10004/wp-admin/admin.php?page=apollo-events-placeholders

**Checklist Dashboard:**
- [ ] Página carrega sem "Sem permissão"
- [ ] **Key Metrics exibidos:**
  - [ ] Total Events
  - [ ] Future Events
  - [ ] Total Views
- [ ] **Top Events by Views:** Pelo menos lista vazia (não erro)
- [ ] **Top Sounds:** Pelo menos lista vazia
- [ ] **Top Locations:** Pelo menos lista vazia

**Checklist User Overview:**
- [ ] Página carrega
- [ ] Dropdown de usuário funciona
- [ ] Co-authored events exibidos (ou mensagem "nenhum")
- [ ] Favorited events exibidos (ou mensagem "nenhum")

**Checklist Placeholders:**
- [ ] Página carrega
- [ ] 3 tabelas exibidas (Event Listing, Local, DJ)
- [ ] Cada tabela tem placeholders listados
- [ ] Exemplos de uso exibidos

**Status:** [ ] Pending

---

#### ✅ TODO 2.4: Testar Shortcodes
**Objetivo:** Verificar se shortcodes retornam conteúdo

**Test Page:** Criar página "Test Shortcodes" no WordPress

**Shortcodes para testar:**
```
[apollo_events]

[event_djs limit="5"]

[event_locals limit="3"]

[past_events limit="5"]
```

**Checklist:**
- [ ] `[apollo_events]` - Exibe portal completo
- [ ] `[event_djs]` - Exibe grid de DJs (ou mensagem "No DJs found")
- [ ] `[event_locals]` - Exibe grid de locais (ou mensagem "No venues found")
- [ ] `[past_events]` - Exibe eventos passados (ou mensagem "No past events")

**Status:** [ ] Pending

---

#### ✅ TODO 2.5: Testar Placeholders API
**Objetivo:** Verificar se placeholders retornam valores

**Test Code (adicionar temporariamente em `functions.php` do tema ou criar página PHP):**
```php
<?php
// Get a real event ID from database
$events = get_posts([
    'post_type' => 'event_listing',
    'posts_per_page' => 1,
    'post_status' => 'publish',
]);

if (!empty($events)) {
    $event_id = $events[0]->ID;
    echo '<h2>Testing Placeholders for Event #' . $event_id . '</h2>';
    echo '<p><strong>Title:</strong> ' . apollo_event_get_placeholder_value('title', $event_id) . '</p>';
    echo '<p><strong>Start Date:</strong> ' . apollo_event_get_placeholder_value('start_date', $event_id) . '</p>';
    echo '<p><strong>DJ List:</strong> ' . apollo_event_get_placeholder_value('dj_list', $event_id) . '</p>';
    echo '<p><strong>Location:</strong> ' . apollo_event_get_placeholder_value('location', $event_id) . '</p>';
    echo '<p><strong>Location Area:</strong> ' . apollo_event_get_placeholder_value('location_area', $event_id) . '</p>';
    echo '<p><strong>Banner URL:</strong> ' . apollo_event_get_placeholder_value('banner_url', $event_id) . '</p>';
} else {
    echo '<p>No events found.</p>';
}
?>
```

**Checklist:**
- [ ] Função `apollo_event_get_placeholder_value()` existe
- [ ] Retorna valores não vazios para evento real
- [ ] DJs retornam nomes ou "Line-up em breve"
- [ ] Location retorna nome ou vazio

**Status:** [ ] Pending

---

#### ✅ TODO 2.6: Testar REST API Settings
**Objetivo:** Verificar se página Rest-API carrega sem erro

**URL:** http://localhost:10004/wp-admin/edit.php?post_type=event_listing&page=wpem-rest-api-settings

**Checklist:**
- [ ] Página carrega sem fatal error
- [ ] Form exibido
- [ ] jQuery UI styles carregando

**Status:** [ ] Pending

---

### FASE 3: DATA INTEGRITY CHECK 🔒

#### ✅ TODO 3.1: Verificar meta keys corretos
**Objetivo:** Confirmar que meta keys seguem padrão Apollo

**SQL Query:**
```sql
-- Ver todos os meta keys de event_listing
SELECT DISTINCT meta_key, COUNT(*) as count
FROM wp_postmeta pm
JOIN wp_posts p ON p.ID = pm.post_id
WHERE p.post_type = 'event_listing'
GROUP BY meta_key
ORDER BY meta_key;
```

**Expected Meta Keys:**
- `_event_start_date`
- `_event_end_date`
- `_event_start_time`
- `_event_end_time`
- `_event_location`
- `_event_banner`
- `_event_dj_ids`
- `_event_local_ids`
- `_event_timetable` (opcional)
- `_tickets_ext`
- `_cupom_ario`

**Status:** [ ] Pending

---

#### ✅ TODO 3.2: Verificar serialização de arrays
**Objetivo:** Confirmar que `_event_dj_ids` está serializado corretamente

**SQL Query:**
```sql
-- Ver serialização de _event_dj_ids
SELECT 
    p.ID,
    p.post_title,
    m.meta_value
FROM wp_posts p
JOIN wp_postmeta m ON p.ID = m.post_id
WHERE p.post_type = 'event_listing'
AND m.meta_key = '_event_dj_ids'
AND m.meta_value IS NOT NULL
LIMIT 5;
```

**Expected Output:**
- Valores no formato: `a:2:{i:0;s:2:"92";i:1;s:2:"94";}` (serializado)
- OU: `92,94` (string simples)
- OU: `92` (ID único)

**Status:** [ ] Pending

---

#### ✅ TODO 3.3: Verificar transients de cache
**Objetivo:** Confirmar que cache está funcionando

**SQL Query:**
```sql
-- Ver transients do Apollo
SELECT 
    option_name,
    option_value,
    LENGTH(option_value) as value_length
FROM wp_options
WHERE option_name LIKE '_transient_apollo%'
OR option_name LIKE '_transient_timeout_apollo%'
ORDER BY option_name;
```

**Expected Output:**
- `_transient_apollo_upcoming_events_YYYYMMDD`
- `_transient_timeout_apollo_upcoming_events_YYYYMMDD`

**Status:** [ ] Pending

---

### FASE 4: XDEBUG DEEP DIVE 🐛

#### ✅ TODO 4.1: Debug Event Cards Logic
**Objetivo:** Verificar valores exatos durante render

**XDebug Breakpoints:**
1. `portal-discover.php:230` - Após `get_post_meta($event_id, '_event_dj_ids', true)`
2. `portal-discover.php:240` - Após `maybe_unserialize($dj_ids_raw)`
3. `portal-discover.php:315` - Após loop de DJs
4. `portal-discover.php:340` - Após `get_post_meta($event_id, '_event_local_ids', true)`
5. `portal-discover.php:360` - Após parse de location

**Variables to Inspect:**
- `$event_id`
- `$dj_ids_raw`
- `$dj_ids` (após unserialize)
- `$djs_names` (array final)
- `$dj_display` (string formatada)
- `$event_location`
- `$event_location_area`

**Status:** [ ] Pending

---

#### ✅ TODO 4.2: Debug Modal AJAX
**Objetivo:** Verificar se modal recebe dados corretos

**XDebug Breakpoints:**
1. `apollo-events-manager.php:988` - Start of `ajax_get_event_modal()`
2. `apollo-events-manager.php:1000` - Após `get_post($event_id)`
3. `apollo-events-manager.php:1050` - Modal HTML generation

**Variables to Inspect:**
- `$_POST['event_id']`
- `$event_id`
- `$post`
- `$event_data` (se existir)

**Status:** [ ] Pending

---

### FASE 5: PERFORMANCE CHECK ⚡

#### ✅ TODO 5.1: Verificar número de queries
**Objetivo:** Confirmar que não há N+1 queries

**Plugin:** Query Monitor (instalar temporariamente)

**URL:** http://localhost:10004/eventos/

**Checklist:**
- [ ] Total de queries < 50 para 10 eventos
- [ ] Sem queries duplicadas
- [ ] `update_meta_cache()` sendo usado

**Expected Queries:**
- 1x WP_Query para eventos
- 1x `update_meta_cache` (bulk)
- 1x transient check
- Nenhuma query individual de meta dentro do loop

**Status:** [ ] Pending

---

#### ✅ TODO 5.2: Verificar cache hits
**Objetivo:** Confirmar que transients estão funcionando

**Steps:**
1. Acessar http://localhost:10004/eventos/
2. Verificar DB: `SELECT * FROM wp_options WHERE option_name LIKE '_transient_apollo%'`
3. Recarregar página
4. Verificar se queries diminuíram

**Expected:**
- Primeira visita: ~40-50 queries
- Segunda visita (cached): ~20-30 queries

**Status:** [ ] Pending

---

### FASE 6: ERROR HANDLING 🚨

#### ✅ TODO 6.1: Testar evento sem DJs
**Objetivo:** Verificar fallback gracioso

**Steps:**
1. Criar evento de teste sem DJs
2. Acessar /eventos/
3. Verificar display

**Expected:**
- Card exibe "Line-up em breve"
- Sem erros PHP no log

**Status:** [ ] Pending

---

#### ✅ TODO 6.2: Testar evento sem Local
**Objetivo:** Verificar fallback gracioso

**Steps:**
1. Criar evento de teste sem local
2. Acessar /eventos/
3. Verificar display

**Expected:**
- Card NÃO exibe linha de local
- Sem erros PHP no log

**Status:** [ ] Pending

---

#### ✅ TODO 6.3: Verificar PHP Error Log
**Objetivo:** Confirmar que não há warnings/notices

**Log Location:** 
- Local by Flywheel: `~/Local Sites/1212/logs/php/error.log`
- Ou: WordPress Debug: `wp-content/debug.log`

**Command:**
```bash
tail -f ~/Local\ Sites/1212/logs/php/error.log
```

**Expected:**
- Nenhum erro relacionado a Apollo Events
- Sem warnings de undefined variable
- Sem notices de array offset

**Status:** [ ] Pending

---

### FASE 7: FINAL VALIDATION ✅

#### ✅ TODO 7.1: Run Full Test Suite
**Checklist Final:**
- [ ] Todos os eventos exibem DJs (ou fallback)
- [ ] Todos os eventos exibem Local (ou oculto se vazio)
- [ ] Modal abre para todos os eventos
- [ ] Dashboards acessíveis
- [ ] Shortcodes funcionando
- [ ] Placeholders retornando valores
- [ ] REST API sem erros
- [ ] Performance aceitável (< 2s page load)
- [ ] Sem erros PHP no log
- [ ] XDebug não reporta avisos

**Status:** [ ] Pending

---

#### ✅ TODO 7.2: Criar relatório de bugs (se houver)
**Formato:**
```markdown
# Bug Report

## Bug #1: [Descrição]
- **Severidade:** Critical | High | Medium | Low
- **Arquivo:** path/to/file.php:line
- **Reprodução:** Steps to reproduce
- **Esperado:** Expected behavior
- **Atual:** Current behavior
- **Fix sugerido:** Suggested fix
```

**Status:** [ ] Pending

---

#### ✅ TODO 7.3: Atualizar PLUGIN-SUMMARY.md
**Adicionar:**
- Data do último teste
- Versão testada
- Status de cada feature
- Known issues (se houver)

**Status:** [ ] Pending

---

## 🔧 TROUBLESHOOTING GUIDE

### Se Event Cards não exibirem DJs:

**Check 1:** Verificar meta key
```sql
SELECT meta_key, meta_value 
FROM wp_postmeta 
WHERE post_id = [EVENT_ID] 
AND meta_key LIKE '%dj%';
```

**Check 2:** XDebug em `portal-discover.php:240`
- Verificar `$dj_ids_raw` (deve ser string serializada ou array)
- Verificar `$dj_ids` após `maybe_unserialize()`

**Check 3:** Criar DJ manualmente e linkar ao evento

---

### Se Modal não abrir:

**Check 1:** Browser Console
- Verificar erros JavaScript
- Verificar se `apollo_events_ajax` está definido

**Check 2:** Network tab
- Verificar se AJAX call retorna 200
- Verificar se `data.success === true`

**Check 3:** XDebug em `apollo-events-manager.php:988`
- Verificar se `$_POST['event_id']` está chegando
- Verificar nonce

---

### Se Dashboard der "Sem permissão":

**Check 1:** Verificar role
```sql
SELECT * FROM wp_usermeta 
WHERE user_id = 1 
AND meta_key = 'wp_capabilities';
```

**Check 2:** Verificar capability
```php
current_user_can('manage_options') // deve retornar true
```

**Check 3:** Forçar capability
```sql
UPDATE wp_usermeta 
SET meta_value = 'a:1:{s:13:"administrator";b:1;}' 
WHERE user_id = 1 
AND meta_key = 'wp_capabilities';
```

---

## 📊 SUCCESS CRITERIA

### Mínimo para PASS:
- ✅ Pelo menos 1 evento exibindo DJs OU Local
- ✅ Modal abrindo sem erros
- ✅ Dashboards acessíveis para admin
- ✅ Nenhum fatal error PHP
- ✅ Nenhum erro JavaScript console

### Ideal para PASS+:
- ✅ Todos os eventos com dados completos
- ✅ Performance < 1.5s
- ✅ Cache funcionando
- ✅ Todos os shortcodes funcionais
- ✅ Todos os placeholders retornando valores

---

## 📝 EXECUTION LOG

**Tester:** _____________________  
**Date:** _____________________  
**Environment:** Local by Flywheel  
**Browser:** _____________________  
**Total Tests:** 32  
**Passed:** _____  
**Failed:** _____  
**Skipped:** _____  

**Overall Status:** [ ] PASS | [ ] PASS+ | [ ] FAIL

---

**Última Atualização:** 2025-11-05  
**Versão do Plugin:** 2.0.1  
**Status:** 🟡 AWAITING EXECUTION



