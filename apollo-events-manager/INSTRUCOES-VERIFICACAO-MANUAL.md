# 📋 Instruções de Verificação Manual

**Data:** 15/01/2025

---

## ✅ Verificação 1: Teste de Ativação

### Passo a Passo:

1. **Desative todos os plugins Apollo:**
   ```
   wp plugin deactivate apollo-events-manager apollo-social apollo-rio
   ```

2. **Ative os plugins na ordem correta:**
   ```bash
   wp plugin activate apollo-social
   wp plugin activate apollo-rio
   wp plugin activate apollo-events-manager
   ```

3. **Verifique logs de ativação:**
   ```bash
   tail -f wp-content/debug.log | grep Apollo
   ```
   
   **Resultado Esperado:**
   ```
   ✅ Apollo Social: Rewrite rules flushadas com sucesso
   ✅ Apollo Rio: Plugin ativado com sucesso
   ✅ Apollo: /eventos/ page already exists (ID: XXX)
   ✅ Apollo Events Manager 2.0.0 activated successfully
   ```

4. **Verifique se página /eventos/ foi criada:**
   ```bash
   wp post list --post_type=page --name=eventos --format=table
   ```
   
   **Resultado Esperado:**
   - ✅ Apenas UMA página com slug 'eventos'
   - ✅ Status: 'publish'
   - ✅ Conteúdo: '[apollo_events_portal]'

5. **Verifique CPTs registrados:**
   ```bash
   wp post-type list
   ```
   
   **Resultado Esperado:**
   - ✅ `event_listing` registrado
   - ✅ `event_dj` registrado
   - ✅ `event_local` registrado

6. **Verifique debug.log para erros fatal:**
   ```bash
   tail -100 wp-content/debug.log | grep -i "fatal\|parse\|syntax"
   ```
   
   **Resultado Esperado:**
   - ✅ Nenhum erro fatal relacionado ao Apollo

---

## ✅ Verificação 2: Teste de Salvamento

### Passo a Passo:

1. **Crie um evento de teste no WordPress Admin:**
   - Vá para: `Eventos > Adicionar Novo`
   - Preencha:
     - ✅ Título do evento
     - ✅ **DJs selecionados** (múltiplos)
     - ✅ **Local selecionado** (um único)
     - ✅ **Timetable preenchido** (com horários)
     - ✅ Banner (opcional)
   - Publique o evento

2. **Execute verificação automatizada:**
   ```bash
   wp eval-file wp-content/plugins/apollo-events-manager/EXECUTAR-VERIFICACOES-COMPLETAS.php
   ```

3. **Verifique manualmente no banco:**
   ```bash
   wp db query "SELECT post_id, meta_key, meta_value FROM wp_postmeta WHERE post_id = [EVENT_ID] AND meta_key IN ('_event_dj_ids', '_event_local_ids', '_event_timetable', '_event_djs', '_event_local') ORDER BY meta_key"
   ```
   
   **Substitua [EVENT_ID] pelo ID do evento criado**

   **Resultado Esperado:**
   - ✅ `_event_dj_ids` existe e é serialized array (ex: `a:2:{i:0;s:2:"92";i:1;s:2:"71";}`)
   - ✅ `_event_local_ids` existe e é int único (ex: `95`)
   - ✅ `_event_timetable` existe e é array serialized (ex: `a:2:{...}`)
   - ✅ `_event_djs` NÃO existe (key antiga)
   - ✅ `_event_local` NÃO existe (key antiga)

4. **Verifique formato específico:**
   ```bash
   wp eval "var_dump(get_post_meta([EVENT_ID], '_event_dj_ids', true));"
   wp eval "var_dump(get_post_meta([EVENT_ID], '_event_local_ids', true));"
   wp eval "var_dump(get_post_meta([EVENT_ID], '_event_timetable', true));"
   ```

---

## ✅ Verificação 3: Teste de Exibição

### Passo a Passo:

1. **Acesse a página do evento no frontend:**
   - URL: `http://seusite.com/evento/[slug-do-evento]/`
   - Ou através do portal: `http://seusite.com/eventos/`

2. **Verifique visualmente:**

   **DJs aparecem na página do evento:**
   - ✅ Nomes dos DJs aparecem
   - ✅ Links para perfis funcionam (se configurado)
   - ✅ Foto do DJ aparece (se configurado)

   **Local/endereço aparece corretamente:**
   - ✅ Nome do local aparece
   - ✅ Endereço completo aparece
   - ✅ Região/cidade aparece

   **Timetable/lineup aparece ordenado:**
   - ✅ Entradas aparecem em ordem cronológica
   - ✅ Horários de início e fim aparecem
   - ✅ Nomes dos DJs aparecem em cada slot

   **Banner aparece (se configurado):**
   - ✅ Banner aparece no topo da página
   - ✅ Banner aparece no card do evento (listagem)
   - ✅ Banner aparece no modal/lightbox
   - ✅ Imagem carrega corretamente

   **Mapa funciona (se coordenadas existem):**
   - ✅ Mapa aparece na página do evento
   - ✅ Marcador está na posição correta
   - ✅ Clique no mapa abre Google Maps
   - ✅ Coordenadas estão corretas

3. **Execute teste automatizado:**
   ```bash
   wp eval-file wp-content/plugins/apollo-events-manager/TESTE-BANNER-MAPA.php
   ```

4. **Verifique debug.log para erros PHP:**
   ```bash
   tail -50 wp-content/debug.log | grep -i "apollo\|error\|warning"
   ```
   
   **Resultado Esperado:**
   - ✅ Nenhum erro fatal
   - ✅ Avisos são normais (não críticos)
   - ✅ Logs informativos aparecem normalmente

---

## 🔍 Verificações Adicionais

### Verificar Cache

1. **Edite um evento existente**
2. **Salve as alterações**
3. **Acesse a página do evento imediatamente**
4. **Verifique se mudanças aparecem sem esperar**

**Resultado Esperado:**
- ✅ Mudanças aparecem imediatamente
- ✅ Cache foi limpo automaticamente

### Verificar Error Handling

1. **Acesse página de eventos sem eventos cadastrados**
2. **Verifique se mensagem amigável aparece**
3. **Verifique se não há erros PHP na tela**

**Resultado Esperado:**
- ✅ Mensagem: "Nenhum evento encontrado"
- ✅ Nenhum erro fatal na tela

---

## 📊 Scripts de Verificação Automatizada

### Executar Todas as Verificações:
```bash
wp eval-file wp-content/plugins/apollo-events-manager/EXECUTAR-VERIFICACOES-COMPLETAS.php
```

### Verificações Individuais:
```bash
# Checklist completo
wp eval-file wp-content/plugins/apollo-events-manager/CHECKLIST-VERIFICACAO-POS-CORRECAO.php

# Teste de banner e mapa
wp eval-file wp-content/plugins/apollo-events-manager/TESTE-BANNER-MAPA.php

# Verificar debug.log
wp eval-file wp-content/plugins/apollo-events-manager/VERIFICAR-DEBUG-LOG.php

# Verificar meta keys
wp eval-file wp-content/plugins/apollo-events-manager/verify-meta-keys-activation.php
```

---

## ✅ Checklist Rápido

- [ ] Plugins ativados na ordem correta
- [ ] Nenhum erro fatal no debug.log
- [ ] Página /eventos/ criada sem duplicatas
- [ ] CPTs registrados (event_listing, event_dj, event_local)
- [ ] Evento criado com DJs, Local e Timetable
- [ ] Meta keys corretas no banco (_event_dj_ids, _event_local_ids, _event_timetable)
- [ ] Keys antigas removidas (_event_djs, _event_local)
- [ ] DJs aparecem na página do evento
- [ ] Local/endereço aparece corretamente
- [ ] Timetable/lineup aparece ordenado
- [ ] Banner aparece (se configurado)
- [ ] Mapa funciona (se coordenadas existem)
- [ ] Nenhum erro PHP no debug.log

---

**Status:** ✅ **INSTRUÇÕES COMPLETAS**

