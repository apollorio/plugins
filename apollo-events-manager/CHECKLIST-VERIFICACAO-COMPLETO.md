# ✅ Checklist de Verificação Pós-Correção

**Data:** 15/01/2025  
**Status:** ✅ **Scripts Criados**

---

## 📋 Scripts de Verificação Disponíveis

### 1. ✅ Checklist Completo
**Arquivo:** `CHECKLIST-VERIFICACAO-POS-CORRECAO.php`

**Verifica:**
- ✅ Status dos plugins (ativos/inativos)
- ✅ Meta keys no banco de dados
- ✅ Activation hooks funcionando
- ✅ Templates e funções disponíveis
- ✅ Banner e mapa funcionando
- ✅ Sistema de cache
- ✅ Debug.log sem erros críticos

**Uso:**
```bash
wp eval-file wp-content/plugins/apollo-events-manager/CHECKLIST-VERIFICACAO-POS-CORRECAO.php
```

---

### 2. ✅ Teste de Banner e Mapa
**Arquivo:** `TESTE-BANNER-MAPA.php`

**Verifica:**
- ✅ Banner aparece (se configurado)
  - Valida URLs
  - Verifica attachments
  - Testa acessibilidade
- ✅ Mapa funciona (se coordenadas existem)
  - Valida coordenadas
  - Verifica se estão no Brasil
  - Gera link do Google Maps

**Uso:**
```bash
wp eval-file wp-content/plugins/apollo-events-manager/TESTE-BANNER-MAPA.php
```

---

### 3. ✅ Verificação de Debug.log
**Arquivo:** `VERIFICAR-DEBUG-LOG.php`

**Verifica:**
- ✅ Erros PHP relacionados ao Apollo
- ✅ Erros fatal críticos
- ✅ Avisos e notices
- ✅ Tamanho e última modificação do log

**Uso:**
```bash
wp eval-file wp-content/plugins/apollo-events-manager/VERIFICAR-DEBUG-LOG.php
```

---

## 📝 Checklist Manual

### Verificação 1: Teste de Ativação

1. ✅ Desative todos os plugins Apollo
2. ✅ Ative `apollo-social` primeiro
3. ✅ Ative `apollo-events-manager`
4. ✅ Ative `apollo-rio`
5. ✅ Verifique logs de ativação:
   ```bash
   tail -f wp-content/debug.log | grep Apollo
   ```
6. ✅ Verifique se página `/eventos/` foi criada/restaurada
7. ✅ Verifique se rewrite rules foram flushadas (apenas uma vez)

**Resultado Esperado:**
- ✅ Nenhuma página duplicada criada
- ✅ Página restaurada da lixeira se existir
- ✅ Rewrite rules flushadas apenas uma vez
- ✅ Logs informativos sem erros

---

### Verificação 2: Banner Aparece (se configurado)

1. ✅ Acesse um evento com banner configurado
2. ✅ Verifique se banner aparece na listagem
3. ✅ Verifique se banner aparece na página single
4. ✅ Verifique se banner aparece no modal/lightbox
5. ✅ Execute teste automatizado:
   ```bash
   wp eval-file wp-content/plugins/apollo-events-manager/TESTE-BANNER-MAPA.php
   ```

**Resultado Esperado:**
- ✅ Banner aparece em todos os templates
- ✅ URL válida ou attachment encontrado
- ✅ Imagem carrega corretamente

---

### Verificação 3: Mapa Funciona (se coordenadas existem)

1. ✅ Acesse um evento com local configurado
2. ✅ Verifique se mapa aparece na página single
3. ✅ Verifique se coordenadas estão corretas
4. ✅ Clique no mapa e verifique se abre Google Maps
5. ✅ Execute teste automatizado:
   ```bash
   wp eval-file wp-content/plugins/apollo-events-manager/TESTE-BANNER-MAPA.php
   ```

**Resultado Esperado:**
- ✅ Mapa aparece quando coordenadas existem
- ✅ Coordenadas válidas (lat: -90 a 90, lng: -180 a 180)
- ✅ Link do Google Maps funciona

---

### Verificação 4: Debug.log sem Erros PHP

1. ✅ Execute verificação automatizada:
   ```bash
   wp eval-file wp-content/plugins/apollo-events-manager/VERIFICAR-DEBUG-LOG.php
   ```
2. ✅ Verifique manualmente:
   ```bash
   tail -100 wp-content/debug.log | grep -i "apollo\|error\|fatal"
   ```
3. ✅ Procure por erros específicos:
   - `Undefined function`
   - `Fatal error`
   - `Parse error`
   - `Warning:`

**Resultado Esperado:**
- ✅ Nenhum erro fatal relacionado ao Apollo
- ✅ Avisos são normais (não críticos)
- ✅ Logs informativos aparecem normalmente

---

## 🔍 Verificações Adicionais

### Meta Keys no Banco

Execute verificação completa:
```bash
wp eval-file wp-content/plugins/apollo-events-manager/verify-meta-keys-activation.php
```

**Verifica:**
- ✅ `_event_dj_ids` existe e é serialized array
- ✅ `_event_local_ids` existe e é int
- ✅ `_event_timetable` existe e é array (não número)
- ✅ NÃO existe `_event_djs` ou `_event_local` (keys antigas)

---

### Cache Funcionando

1. ✅ Edite um evento
2. ✅ Salve alterações
3. ✅ Verifique se mudanças aparecem imediatamente
4. ✅ Verifique logs de cache:
   ```bash
   tail -f wp-content/debug.log | grep "Apollo Cache"
   ```

**Resultado Esperado:**
- ✅ Mudanças aparecem imediatamente (sem esperar 5 minutos)
- ✅ Cache é limpo automaticamente ao salvar

---

### Error Handling em Templates

1. ✅ Acesse página de eventos
2. ✅ Verifique se não há erros PHP na tela
3. ✅ Verifique se mensagens de erro aparecem quando apropriado
4. ✅ Verifique console do navegador (F12) para erros JS

**Resultado Esperado:**
- ✅ Nenhum erro fatal na tela
- ✅ Mensagens amigáveis quando não há eventos
- ✅ Degradação graciosa em caso de erro

---

## 📊 Resumo das Verificações

| Verificação | Script | Status |
|-------------|--------|--------|
| Checklist Completo | `CHECKLIST-VERIFICACAO-POS-CORRECAO.php` | ✅ |
| Banner e Mapa | `TESTE-BANNER-MAPA.php` | ✅ |
| Debug.log | `VERIFICAR-DEBUG-LOG.php` | ✅ |
| Meta Keys | `verify-meta-keys-activation.php` | ✅ |

---

## ✅ Executar Todas as Verificações

```bash
# 1. Checklist completo
wp eval-file wp-content/plugins/apollo-events-manager/CHECKLIST-VERIFICACAO-POS-CORRECAO.php

# 2. Teste de banner e mapa
wp eval-file wp-content/plugins/apollo-events-manager/TESTE-BANNER-MAPA.php

# 3. Verificar debug.log
wp eval-file wp-content/plugins/apollo-events-manager/VERIFICAR-DEBUG-LOG.php

# 4. Verificar meta keys
wp eval-file wp-content/plugins/apollo-events-manager/verify-meta-keys-activation.php
```

---

**Status:** ✅ **SCRIPTS CRIADOS E PRONTOS PARA USO**

