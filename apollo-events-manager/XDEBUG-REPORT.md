# 🔴 XDEBUG REPORT - CRITICAL ERRORS FOUND

**Data:** November 2, 2025  
**Análise:** apollo-events-manager.php  
**Status:** 🔴 3 ERROS CRÍTICOS ENCONTRADOS

---

## ⚠️ ATENÇÃO: NÃO MODIFIQUEI NENHUM ARQUIVO

Este é apenas um relatório de análise. Outras pessoas estão debugando também.

---

## 🔴 ERRO CRÍTICO #1: SALVAMENTO DE DJs INCORRETO

### Localização
**Arquivo:** `apollo-events-manager.php`  
**Linha:** 1026  
**Função:** `save_custom_event_fields()`

### Código Atual
```php
// Save DJs
if (isset($_POST['event_djs'])) {
    $djs = array_map('intval', (array) $_POST['event_djs']);
    update_post_meta($post_id, '_event_djs', $djs);  // ❌ WRONG KEY!
}
```

### Problema
**Salva em:** `_event_djs`  
**Database espera:** `_event_dj_ids` (serializado)

### Impacto
- ✅ DJs são salvos no meta key ERRADO
- ❌ Templates procuram em `_event_dj_ids` e NÃO encontram
- ❌ Line-up fica vazio nos eventos
- ❌ Cards não mostram DJs

### Evidência do Debug
```
Database Reality (Event ID 143):
_event_dj_ids => 'a:2:{i:0;s:2:"92";i:1;s:2:"71";}'

Código salva em:
_event_djs => array(92, 71)
```

### Solução Necessária
```php
// ✅ CORRETO
if (isset($_POST['event_djs'])) {
    $djs = array_map('strval', array_map('intval', (array) $_POST['event_djs']));
    update_post_meta($post_id, '_event_dj_ids', serialize($djs));
}
```

**Prioridade:** 🔴 CRÍTICA

---

## 🔴 ERRO CRÍTICO #2: SALVAMENTO DE LOCAL INCORRETO

### Localização
**Arquivo:** `apollo-events-manager.php`  
**Linha:** 1031  
**Função:** `save_custom_event_fields()`

### Código Atual
```php
// Save local
if (isset($_POST['event_local'])) {
    update_post_meta($post_id, '_event_local', intval($_POST['event_local']));  // ❌ WRONG KEY!
}
```

### Problema
**Salva em:** `_event_local`  
**Database espera:** `_event_local_ids`

### Impacto
- ✅ Local é salvo no meta key ERRADO
- ❌ Templates procuram em `_event_local_ids` e NÃO encontram
- ❌ Nome do local não aparece
- ❌ Mapa não funciona (sem coordenadas)

### Evidência do Debug
```
Database Reality (Event ID 143):
_event_local_ids => 95

Código salva em:
_event_local => 95
```

### Solução Necessária
```php
// ✅ CORRETO
if (isset($_POST['event_local'])) {
    update_post_meta($post_id, '_event_local_ids', intval($_POST['event_local']));
}
```

**Prioridade:** 🔴 CRÍTICA

---

## 🔴 ERRO CRÍTICO #3: SALVAMENTO DE TIMETABLE INCORRETO

### Localização
**Arquivo:** `apollo-events-manager.php`  
**Linha:** 1036  
**Função:** `save_custom_event_fields()`

### Código Atual
```php
// Save timetable
if (isset($_POST['timetable'])) {
    update_post_meta($post_id, '_timetable', $_POST['timetable']);  // ⚠️ NO VALIDATION!
}
```

### Problemas
1. ❌ Não valida se é array
2. ❌ Não sanitiza dados
3. ❌ Não ordena por horário
4. ❌ Pode salvar qualquer coisa (string, numeric, etc)

### Impacto Atual no Database
```
Database Reality (Event ID 143):
_timetable => 355453 (numeric - BUG!)

Expected:
_timetable => array(
    array('dj' => 92, 'start' => '22:00', 'end' => '23:00'),
    array('dj' => 71, 'start' => '23:00', 'end' => '00:00')
)
```

### Solução Necessária
```php
// ✅ CORRETO
if (isset($_POST['timetable'])) {
    $timetable = $_POST['timetable'];
    
    // Validate is array
    if (is_array($timetable)) {
        $clean = array();
        foreach ($timetable as $slot) {
            if (!empty($slot['dj']) && !empty($slot['start'])) {
                $clean[] = array(
                    'dj' => intval($slot['dj']),
                    'start' => sanitize_text_field($slot['start']),
                    'end' => sanitize_text_field($slot['end'] ?? $slot['start'])
                );
            }
        }
        
        // Sort by start time
        usort($clean, function($a, $b) {
            return strcmp($a['start'], $b['start']);
        });
        
        update_post_meta($post_id, '_event_timetable', $clean);
    }
}
```

**Prioridade:** 🔴 CRÍTICA

---

## 🟡 PROBLEMA #4: CONFLITO ENTRE DOIS MÉTODOS DE SALVAMENTO

### Localização
**Arquivo:** `apollo-events-manager.php`  
**Linhas:** 1022-1053 vs `includes/admin-metaboxes.php` linhas 224-272

### Problema
Existem DOIS métodos salvando os mesmos dados:

1. **Método 1:** `save_custom_event_fields()` (linha 1022)
   - Hook: `event_manager_save_event_listing`
   - Salva em: `_event_djs`, `_event_local`, `_timetable`

2. **Método 2:** `admin-metaboxes.php` → `save_metabox_data()` (linha 224)
   - Hook: `save_post_event_listing` (prioridade 20)
   - Salva em: `_event_dj_ids`, `_event_local_ids`, `_event_timetable`

### Conflito
Os dois hooks rodam ao salvar um evento:
1. Primeiro: `save_custom_event_fields()` salva keys ERRADAS
2. Depois: `save_metabox_data()` salva keys CORRETAS (prioridade 20)
3. **RESULTADO:** Keys corretas sobrescrevem, MAS keys erradas continuam no banco

### Impacto
- ⚠️ Database tem dados duplicados em keys diferentes
- ⚠️ Confusão sobre qual key usar
- ⚠️ Performance: dados salvos 2x

### Solução Necessária
Remover ou desabilitar `save_custom_event_fields()` completamente, OU mudar para salvar nos keys corretos.

**Prioridade:** 🟡 ALTA

---

## 🟡 PROBLEMA #5: ADMIN METABOX PODE NÃO CARREGAR

### Localização
**Arquivo:** `apollo-events-manager.php`  
**Linhas:** 103-106

### Código
```php
// Load admin metaboxes
if (is_admin()) {
    require_once APOLLO_WPEM_PATH . 'includes/admin-metaboxes.php';
}
```

### Problema
O arquivo é carregado **fora da classe** dentro de um método que roda no hook `plugins_loaded`.

Quando `is_admin()` é verdadeiro, o código tenta carregar o arquivo, mas:
- ⚠️ Se arquivo não existir: **Fatal error**
- ⚠️ Não há `file_exists()` check
- ⚠️ Se tiver syntax error no admin-metaboxes.php: **Fatal error**

### Impacto
Se `admin-metaboxes.php` tiver problema:
- 🔴 Admin inteiro quebra
- 🔴 Não consegue editar NENHUM post
- 🔴 White screen of death

### Solução Necessária
```php
// ✅ DEFENSIVO
if (is_admin()) {
    $admin_file = APOLLO_WPEM_PATH . 'includes/admin-metaboxes.php';
    if (file_exists($admin_file)) {
        require_once $admin_file;
    } else {
        error_log('Apollo Events: admin-metaboxes.php not found');
    }
}
```

**Prioridade:** 🟡 ALTA

---

## 🟠 PROBLEMA #6: CACHE PODE CAUSAR DADOS DESATUALIZADOS

### Localização
**Arquivo:** `apollo-events-manager.php`  
**Linhas:** 350-372

### Código
```php
$cache_key = 'apollo_events_shortcode_' . md5(serialize($atts));
$events = wp_cache_get($cache_key, 'apollo_events');

if ($events === false) {
    $events = get_posts(/* ... */);
    wp_cache_set($cache_key, $events, 'apollo_events', 300);  // 5 minutos
}
```

### Problema
- ⚠️ Cache dura 5 minutos
- ⚠️ Se admin editar evento, shortcode mostra versão antiga por até 5 min
- ⚠️ Cache não é limpo ao salvar evento

### Impacto
- User edita evento
- Recarrega página
- Vê dados antigos por 5 minutos
- Pensa que não salvou

### Solução Necessária
Adicionar hook para limpar cache ao salvar:
```php
add_action('save_post_event_listing', function($post_id) {
    wp_cache_delete_group('apollo_events');
});
```

**Prioridade:** 🟠 MÉDIA

---

## 🟠 PROBLEMA #7: AJAX FILTER USA VARIÁVEIS INDEFINIDAS

### Localização
**Arquivo:** `apollo-events-manager.php`  
**Linhas:** 456-469

### Código
```php
ob_start();
if ($events) {
    foreach ($events as $event) {
        $event_id = $event->ID;
        $start_date = get_post_meta($event_id, '_event_start_date', true);
        $location = $this->get_event_location($event);
        $categories = get_the_terms($event_id, 'event_listing_category');
        $category_slug = $categories ? $categories[0]->slug : 'music';
        $month_short = date('M', strtotime($start_date));
        $day = date('j', strtotime($start_date));
        $banner = $this->get_event_banner($event_id);
        $banner_url = is_array($banner) ? $banner[0] : '';

        include APOLLO_WPEM_PATH . 'templates/content-event_listing.php';
    }
}
```

### Problema
O código define variáveis (`$location`, `$month_short`, `$day`, `$banner_url`) mas o template `content-event_listing.php` **NÃO USA ESSAS VARIÁVEIS**.

O template recalcula tudo internamente:
```php
// content-event_listing.php linha 8-11
$event_id = get_the_ID();  // ❌ Pode ser diferente!
$event_title = get_post_meta($event_id, '_event_title', true);
$start_date = get_post_meta($event_id, '_event_start_date', true);
```

### Impacto
- ⚠️ `get_the_ID()` no template pode retornar ID errado (não está no loop)
- ⚠️ Variáveis definidas no AJAX são desperdiçadas
- ⚠️ Performance: busca mesmos dados 2x

### Solução Necessária
Passar `$event` para `global $post` antes do include:
```php
foreach ($events as $event) {
    global $post;
    $post = $event;
    setup_postdata($post);
    include APOLLO_WPEM_PATH . 'templates/content-event_listing.php';
}
wp_reset_postdata();
```

**Prioridade:** 🟠 MÉDIA

---

## 🟢 PROBLEMA #8: GEOCODING SEM RATE LIMIT

### Localização
**Arquivo:** `apollo-events-manager.php`  
**Linhas:** 116-158

### Código
```php
public function auto_geocode_local($post_id, $post) {
    // ...
    $url = "https://nominatim.openstreetmap.org/search?...";
    $res = wp_remote_get($url, [
        'timeout' => 10,
        'user-agent' => 'Apollo::Rio/1.0 (WordPress Event Manager)'
    ]);
    // ...
}
```

### Problema
Nominatim API tem rate limit de **1 request/segundo**.

Se admin salvar múltiplos Locais rapidamente:
- ⚠️ API retorna erro 429 (Too Many Requests)
- ⚠️ Geocoding falha silenciosamente
- ⚠️ Nenhuma coordenada é salva

### Impacto
- Bulk edit de Locais = falha
- Import de Locais = falha
- Quick edits = pode falhar

### Solução Necessária
Adicionar rate limit check:
```php
$last_geocode = get_transient('apollo_last_geocode_time');
if ($last_geocode && (time() - $last_geocode) < 2) {
    // Wait ou schedule for later
    wp_schedule_single_event(time() + 5, 'apollo_delayed_geocode', [$post_id]);
    return;
}
set_transient('apollo_last_geocode_time', time(), 10);
```

**Prioridade:** 🟢 BAIXA (só em bulk operations)

---

## 🔵 PROBLEMA #9: METABOX AJAX SEM NONCE CHECK EM UMA AÇÃO

### Localização
**Arquivo:** `includes/admin-metaboxes.php`  
**Linhas:** 89-130 (ajax_add_new_dj) e 132-187 (ajax_add_new_local)

### Código
```php
public function ajax_add_new_dj() {
    check_ajax_referer('apollo_admin_nonce', 'nonce');  // ✅ OK
    
    if (!current_user_can('edit_posts')) {  // ✅ OK
        wp_send_json_error('Permission denied');
    }
    // ...
}
```

### Análise
**Status:** ✅ SEGURO

Verificações presentes:
- ✅ `check_ajax_referer()` - previne CSRF
- ✅ `current_user_can()` - verifica permissão
- ✅ `sanitize_text_field()` - sanitiza input
- ✅ `mb_strtolower()` - normaliza para comparação

**Prioridade:** ✅ SEM PROBLEMAS

---

## 🔵 PROBLEMA #10: ESTRUTURA DO TIMETABLE NO METABOX

### Localização
**Arquivo:** `includes/admin-metaboxes.php`  
**Linhas:** 245-262

### Código
```php
if (!empty($_POST['apollo_event_timetable'])) {
    $timetable_json = stripslashes($_POST['apollo_event_timetable']);
    $timetable = json_decode($timetable_json, true);
    
    if (is_array($timetable)) {
        usort($timetable, function($a, $b) {
            return strcmp($a['start'] ?? '', $b['start'] ?? '');
        });
        
        update_post_meta($post_id, '_event_timetable', $timetable);  // ✅ CORRETO!
    }
}
```

### Análise
**Status:** ✅ CORRETO

Mas tem conflito com linha 1036 do arquivo principal!

### Impacto
Se usar formulário frontend (WP Event Manager):
- ❌ Salva em `_timetable` (errado)

Se usar admin metabox:
- ✅ Salva em `_event_timetable` (correto)

**Prioridade:** 🟡 ALTA (inconsistência)

---

## 📊 RESUMO DOS ERROS

| # | Problema | Linha | Prioridade | Impacto |
|---|----------|-------|------------|---------|
| 1 | DJs salvam em key errada | 1026 | 🔴 CRÍTICA | Line-up não aparece |
| 2 | Local salva em key errada | 1031 | 🔴 CRÍTICA | Venue/mapa não aparece |
| 3 | Timetable sem validação | 1036 | 🔴 CRÍTICA | Dados inconsistentes |
| 4 | Dois métodos de salvamento | 1022 + metabox | 🟡 ALTA | Dados duplicados |
| 5 | Admin metabox sem file_exists | 105 | 🟡 ALTA | Pode quebrar admin |
| 6 | Cache não limpa ao salvar | 350 | 🟠 MÉDIA | Dados desatualizados |
| 7 | AJAX define vars não usadas | 459-467 | 🟠 MÉDIA | Performance |
| 8 | Geocoding sem rate limit | 139 | 🟢 BAIXA | Bulk operations |
| 9 | AJAX handlers | - | ✅ OK | Sem problemas |
| 10 | Metabox timetable | - | ✅ OK | Mas conflita com #3 |

---

## 🎯 CORREÇÕES NECESSÁRIAS (ORDEM DE PRIORIDADE)

### 1. URGENTE (Fazer primeiro)
```php
// apollo-events-manager.php linha 1026
update_post_meta($post_id, '_event_djs', $djs);
↓
update_post_meta($post_id, '_event_dj_ids', serialize(array_map('strval', $djs)));

// apollo-events-manager.php linha 1031
update_post_meta($post_id, '_event_local', intval($_POST['event_local']));
↓
update_post_meta($post_id, '_event_local_ids', intval($_POST['event_local']));

// apollo-events-manager.php linha 1036
update_post_meta($post_id, '_timetable', $_POST['timetable']);
↓
// Add validation + sort (see solution in ERROR #3)
```

### 2. IMPORTANTE (Fazer depois)
- Adicionar `file_exists()` check antes de require admin-metaboxes
- Decidir: usar metabox OU frontend form (não ambos)
- Limpar cache ao salvar evento

### 3. MELHORIAS (Fazer quando possível)
- Rate limit no geocoding
- Otimizar AJAX filter
- Remover variáveis não usadas

---

## 🧪 COMO VERIFICAR SE ESTÁ BUGADO

### Teste 1: Criar evento novo
```bash
1. Admin → Eventos → Adicionar novo
2. Selecione DJs
3. Selecione Local
4. Salve
5. Verifique no banco:
   - Deve ter _event_dj_ids ✅
   - NÃO deve ter _event_djs ❌
   - Deve ter _event_local_ids ✅
   - NÃO deve ter _event_local ❌
```

### Teste 2: Ver no frontend
```bash
1. Acesse evento criado
2. Veja se DJs aparecem
3. Veja se Local aparece
4. Se NÃO aparecer = BUG CONFIRMADO
```

### Teste 3: Check database
```sql
SELECT post_id, meta_key, meta_value 
FROM wp_postmeta 
WHERE post_id = 143 
AND meta_key IN ('_event_djs', '_event_dj_ids', '_event_local', '_event_local_ids', '_timetable', '_event_timetable')
ORDER BY meta_key;
```

Resultado esperado se BUGADO:
```
_event_djs -> array(92,71)         ❌ Key errada
_event_dj_ids -> 'a:2:...'        ✅ Key correta
_event_local -> 95                 ❌ Key errada
_event_local_ids -> 95             ✅ Key correta
_timetable -> 355453               ❌ Numeric (bug)
_event_timetable -> array(...)     ✅ Array correto
```

---

## 💾 BACKUP ANTES DE CORRIGIR

```bash
# 1. Backup do arquivo
cp apollo-events-manager.php apollo-events-manager.php.backup

# 2. Backup do banco
wp db export backup-$(date +%Y%m%d-%H%M%S).sql

# 3. Git commit
git add -A
git commit -m "backup: Before fixing meta keys"
git push origin main
```

---

## 🎯 PLANO DE CORREÇÃO SUGERIDO

### Opção A: Fix Rápido (5 min)
Mudar apenas as 3 linhas:
- 1026: `_event_djs` → `_event_dj_ids` + serialize
- 1031: `_event_local` → `_event_local_ids`
- 1036: Adicionar validação + `_event_timetable`

### Opção B: Fix Completo (20 min)
- Remover `save_custom_event_fields()` completamente
- Usar apenas metabox para salvamento
- Limpar keys antigas do database
- Adicionar migration script

---

## ⚠️ AVISO FINAL

**NÃO MODIFIQUEI NENHUM ARQUIVO.**

Este é apenas um relatório de análise.

**Antes de corrigir:**
1. Fazer backup do banco
2. Fazer backup dos arquivos
3. Testar em ambiente de staging
4. Avisar outros developers que estão debugando

**Status:** 🔴 3 Erros críticos encontrados e documentados

**Próximo passo:** Decidir qual opção de correção usar (A ou B)




