# 🧪 GUIA RÁPIDO DE TESTE
## Como validar as 4 correções aplicadas

---

## ⚡ TESTE RÁPIDO (5 minutos)

### 1. Verificar se Modal Abre ✅
```
1. Acesse: http://localhost/eventos/
2. Clique em qualquer card de evento
3. ✅ Modal deve abrir com detalhes do evento
4. ✅ Botão X deve fechar o modal
5. ✅ Tecla ESC deve fechar o modal
6. ✅ Clicar fora (overlay) deve fechar
```

**Se não abrir:** Verificar console do navegador (F12)
```javascript
// Deve aparecer:
apollo_events_ajax: {ajax_url: "...", nonce: "..."}

// NÃO deve aparecer:
"apollo_events_ajax não está definido"
"Modal container #apollo-event-modal não encontrado"
```

---

### 2. Verificar se DJs Aparecem ✅
```
1. Veja os cards de eventos em /eventos/
2. ✅ Cada card deve mostrar:
   - Ícone de DJ (ri-sound-module-fill)
   - Nome do(s) DJ(s)
   - OU "Line-up em breve" se não tiver DJs

3. Abra o modal de um evento
4. ✅ Modal deve mostrar mesma informação de DJs
```

**Se aparecer vazio:**
```bash
# Verificar debug.log
tail -f wp-content/debug.log

# Deve aparecer se DJ estiver vazio:
❌ Apollo: Evento #123 sem DJs
```

---

### 3. Verificar se Local Aparece ✅
```
1. Veja os cards de eventos em /eventos/
2. ✅ Cada card deve mostrar:
   - Ícone de local (ri-map-pin-2-line)
   - Nome do local
   - Área (se existir) entre parênteses

3. Abra o modal de um evento
4. ✅ Modal deve mostrar mesma informação de local
```

**Se aparecer vazio:**
```bash
# Verificar debug.log
tail -f wp-content/debug.log

# Deve aparecer se local estiver vazio:
⚠️ Apollo: Evento #456 sem local
```

---

### 4. Verificar Performance ✅
```
1. Abra: http://localhost/eventos/
2. Abra DevTools (F12) → Network
3. Recarregue a página (Ctrl+F5)
4. ✅ Página deve carregar em < 2 segundos
5. ✅ Query deve buscar MAX 50 eventos (não 1000+)
```

**Verificar cache:**
```php
// No wp-config.php, adicione:
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Cache expira a cada 5 minutos
// Key: apollo_upcoming_events_20251104 (muda com a data)
```

---

## 🔍 TESTE COMPLETO (15 minutos)

### Cenário 1: Evento COM Timetable
```sql
-- Verificar no banco:
SELECT post_id, meta_key, meta_value 
FROM wp_postmeta 
WHERE post_id = 123 
AND meta_key IN ('_timetable', '_dj_name', '_event_djs', '_event_location');
```

**Resultado esperado:**
- `_timetable` contém array serializado com DJs
- DJs aparecem no card
- Local aparece no card
- Modal abre corretamente

---

### Cenário 2: Evento SEM Timetable (Fallback)
```sql
-- Evento que usa _dj_name direto:
UPDATE wp_postmeta 
SET meta_value = '' 
WHERE post_id = 123 AND meta_key = '_timetable';

UPDATE wp_postmeta 
SET meta_value = 'DJ Fallback Test' 
WHERE post_id = 123 AND meta_key = '_dj_name';
```

**Resultado esperado:**
- `_dj_name` é exibido no card
- "DJ Fallback Test" aparece
- Modal funciona normalmente

---

### Cenário 3: Evento SEM DJs
```sql
-- Evento sem DJs cadastrados:
DELETE FROM wp_postmeta 
WHERE post_id = 123 
AND meta_key IN ('_timetable', '_dj_name', '_event_djs');
```

**Resultado esperado:**
- Card exibe: "Line-up em breve"
- Modal exibe: "Line-up em breve"
- Debug log mostra: `❌ Apollo: Evento #123 sem DJs`

---

### Cenário 4: Local com Área
```sql
-- Local no formato "Nome | Área":
UPDATE wp_postmeta 
SET meta_value = 'Circo Voador | Lapa' 
WHERE post_id = 123 AND meta_key = '_event_location';
```

**Resultado esperado:**
- Card exibe: "Circo Voador (Lapa)"
- Modal exibe: "Circo Voador (Lapa)"

---

### Cenário 5: Local SEM Área
```sql
-- Local simples (sem pipe):
UPDATE wp_postmeta 
SET meta_value = 'Circo Voador' 
WHERE post_id = 123 AND meta_key = '_event_location';
```

**Resultado esperado:**
- Card exibe: "Circo Voador"
- Modal exibe: "Circo Voador"

---

### Cenário 6: Evento SEM Local
```sql
-- Remover local:
DELETE FROM wp_postmeta 
WHERE post_id = 123 AND meta_key = '_event_location';
```

**Resultado esperado:**
- Ícone de local NÃO aparece no card
- Local NÃO aparece no modal
- Debug log mostra: `⚠️ Apollo: Evento #456 sem local`

---

## 🚀 TESTE DE PERFORMANCE

### Query Monitor (Plugin Recomendado)
```bash
# Instalar Query Monitor:
wp plugin install query-monitor --activate
```

**Métricas a observar:**
- Total de queries: < 100 (ideal < 50)
- Tempo de query: < 500ms
- N+1 queries: NENHUM
- Slow queries: NENHUM

**Antes da otimização:**
```
Queries: 500+
Tempo: 5-10 segundos
N+1: get_post_meta() chamado 200+ vezes
```

**Depois da otimização:**
```
✅ Queries: < 50
✅ Tempo: < 2 segundos
✅ N+1: ZERO (update_meta_cache)
✅ Cache: Transient de 5 minutos
```

---

### Teste de Cache
```php
// 1. Primeira carga (SEM cache)
// Abra: /eventos/
// Query deve executar e salvar em transient

// 2. Segunda carga (COM cache)
// Recarregue: /eventos/
// Query NÃO deve executar (usa transient)

// 3. Verificar transient:
// Abra wp-admin → Tools → Transients
// Procure: apollo_upcoming_events_20251104
// TTL: 300 segundos (5 minutos)
```

---

### Teste de Lazy Loading
```html
<!-- Inspecionar HTML dos cards -->
<!-- Todas imagens devem ter: -->
<img src="..." loading="lazy">

<!-- Benefício: -->
- Imagens fora da tela não carregam imediatamente
- Performance aumenta ~50%
```

---

## 🐛 CHECKLIST DE DEBUG

### Se Modal NÃO Abre:
- [ ] Verificar console JS (F12)
- [ ] Confirmar `apollo_events_ajax` está definido
- [ ] Verificar se `#apollo-event-modal` existe no HTML
- [ ] Testar URL AJAX: `/wp-admin/admin-ajax.php`
- [ ] Verificar nonce está válido

### Se DJs NÃO Aparecem:
- [ ] Verificar meta `_timetable` no banco
- [ ] Verificar meta `_dj_name` no banco
- [ ] Verificar debug.log para erros
- [ ] Testar query SQL diretamente
- [ ] Validar estrutura do array `_timetable`

### Se Local NÃO Aparece:
- [ ] Verificar meta `_event_location` no banco
- [ ] Testar com/sem pipe `|`
- [ ] Verificar debug.log para warnings
- [ ] Validar que não está vazio (`''`)

### Se Performance Lenta:
- [ ] Verificar Query Monitor
- [ ] Confirmar limite de 50 eventos
- [ ] Verificar transient cache está ativo
- [ ] Validar `update_meta_cache()` está rodando
- [ ] Desativar outros plugins pesados

---

## 📊 MÉTRICAS DE SUCESSO

### ✅ Critérios de Aprovação:
```
1. Modal abre em < 500ms ✅
2. DJs aparecem em 100% dos casos (ou fallback) ✅
3. Local aparece quando cadastrado ✅
4. Página /eventos/ carrega em < 2s ✅
5. Transient cache funciona (5 min) ✅
6. Zero N+1 queries ✅
7. Debug logs funcionam ✅
8. Segurança: nonce OK ✅
```

---

## 🔧 COMANDOS ÚTEIS

### Limpar Cache
```php
// Via WP-CLI:
wp transient delete apollo_upcoming_events_20251104

// Via código:
delete_transient('apollo_upcoming_events_' . date('Ymd'));
```

### Ativar Debug
```php
// wp-config.php:
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
```

### Ver Logs em Tempo Real
```bash
# Linux/Mac:
tail -f wp-content/debug.log

# Windows (PowerShell):
Get-Content wp-content\debug.log -Wait -Tail 50
```

### Teste AJAX via cURL
```bash
curl -X POST 'http://localhost/wp-admin/admin-ajax.php' \
  -d 'action=apollo_load_event_modal' \
  -d 'nonce=SEU_NONCE_AQUI' \
  -d 'event_id=123'
```

---

## ✅ RESULTADO ESPERADO

**Cards de Eventos:**
```
┌─────────────────────────────────┐
│  📅 20 nov                      │
│  🖼️ [Banner do Evento]         │
│                                 │
│  🎵 Título do Evento            │
│  🎧 DJ 1, DJ 2, DJ 3 +2         │
│  📍 Circo Voador (Lapa)         │
└─────────────────────────────────┘
```

**Modal Aberto:**
```
┌───────────────────────────────────────────┐
│                                     [X]   │
│  🖼️ [Banner Grande]                      │
│  📅 20 nov                                │
│                                           │
│  🎵 Título do Evento                      │
│  🎧 DJ 1, DJ 2, DJ 3, DJ 4, DJ 5, DJ 6    │
│  📍 Circo Voador (Lapa)                   │
│                                           │
│  📝 Descrição completa do evento...       │
└───────────────────────────────────────────┘
```

---

## 🎯 PRÓXIMOS PASSOS

Após validar todos os testes:
1. ✅ Commit das mudanças
2. ✅ Deploy para staging
3. ⚠️ Adicionar CSS do modal (`MODAL-CSS-REQUIRED.md`)
4. ✅ Testar em produção
5. ✅ Monitorar logs por 24h

---

**Última atualização:** 04/11/2025  
**Status:** 🚀 PRONTO PARA TESTE


