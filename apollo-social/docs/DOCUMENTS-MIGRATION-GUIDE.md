# Apollo Documents Migration Rollout Guide

## FASE 11: Rollout Seguro e Limpeza

Este guia documenta o processo completo de migração do módulo Documents para a nova arquitetura unificada.

---

## Resumo das Mudanças

### Problema Original
- **Duas fontes de verdade**: CPT `apollo_document` vs tabela `wp_apollo_documents`
- **Metakeys duplicados**: `_apollo_doc_signatures` vs `_apollo_document_signatures`
- **Status incorreto**: documentos em assinatura mapeados para `publish`
- **FK fraca**: assinaturas referenciavam `document_id` (tabela) ao invés de `post_id`

### Solução Implementada
- **CPT como fonte única**: `apollo_document` é a verdade, tabela é índice
- **Metakey canônico**: `_apollo_doc_signatures` (legado lido e migrado)
- **Status 2 camadas**: `post_status` (visibilidade) + `apollo_doc_state` (workflow)
- **FK correta**: `post_id` na tabela de assinaturas

---

## Arquivos Criados

| Arquivo | Propósito |
|---------|-----------|
| `DocumentsRepository.php` | Ponto único de leitura/escrita |
| `DocumentStatus.php` | Modelo de status em 2 camadas |
| `DocumentsSchema.php` | Migrações de banco (post_id, etc) |
| `DocumentsSyncHooks.php` | Hooks WordPress para sync CPT → tabela |
| `DocumentsCLI.php` | Comandos WP-CLI |
| `DocumentsMigration.php` | Handler de migração e rollout |
| `ApolloWorkflow.php` | Máquina de estados unificada |
| `SignatureSecurity.php` | Validação CPF, auditoria, verificação |

---

## Fases do Rollout

### Fase 1: Leitura Compatível ✓
**Objetivo**: Ler dados de ambas as fontes sem quebrar nada.

```php
// DocumentsRepository já implementa isto:
// - Lê metakey canônico primeiro
// - Se vazio, lê metakey legado
// - Migration-on-read: se leu do legado, escreve no canônico
```

**Verificação**:
```bash
wp apollo dms stats
# Deve mostrar contagens de ambos metakeys
```

---

### Fase 2: Novo Mapeamento de Status ✓
**Objetivo**: Status corretos para documentos.

| Estado Apollo | Post Status | Visibilidade |
|--------------|-------------|--------------|
| draft | draft | Rascunho |
| pending_review | pending | Aguardando |
| ready | private | Pronto (interno) |
| signing | private | Em assinatura (interno) |
| signed | private | Assinado (interno) |
| completed | publish | Completo (público) |
| archived | private | Arquivado |
| cancelled | draft | Cancelado |

**Nota**: signing/signed NÃO devem ser `publish`!

---

### Fase 3: Migrações Executadas
**Objetivo**: Atualizar banco de dados e metadados.

```bash
# 1. Verificar estado atual
wp apollo dms status

# 2. Dry run da migração
wp apollo dms migrate --dry-run

# 3. Executar migração
wp apollo dms migrate

# 4. Verificar reconciliação
wp apollo dms reconcile --dry-run
```

**O que a migração faz**:
1. Adiciona coluna `post_id` na tabela de assinaturas
2. Migra dados do metakey legado para o canônico
3. Popula `post_id` nas assinaturas existentes
4. Sincroniza todos os CPTs com a tabela índice

---

### Fase 4: Escrita no Caminho Novo
**Objetivo**: Todas as escritas passam pelo DocumentsRepository.

**Verificação**:
```php
// Correto - usar sempre:
DocumentsRepository::createDocument( $data );
DocumentsRepository::transitionStatus( $post_id, 'signing' );
DocumentsRepository::storeSignature( $post_id, $signature );

// Incorreto - não usar diretamente:
wp_insert_post( ... ); // sem hooks
update_post_meta( $post_id, '_apollo_document_signatures', ... );
$wpdb->insert( 'wp_apollo_documents', ... );
```

---

### Fase 5: Legado Desativado
**Objetivo**: Parar de ler do caminho antigo.

```bash
# Verificar que não há mais dados legados
wp apollo dms stats
# legacy_metakey deve ser 0

# Marcar fase como completa
wp apollo dms complete-phase phase_5
```

---

### Fase 6: Cleanup Concluído
**Objetivo**: Remover dados legados.

```bash
# Verificar estado final
wp apollo dms status

# Executar cleanup (IRREVERSÍVEL)
wp apollo dms cleanup --yes

# Ou forçar se necessário
wp apollo dms cleanup --force --yes
```

**O que o cleanup remove**:
- Metakey legado `_apollo_document_signatures`
- Entradas órfãs na tabela índice
- Assinaturas órfãs (sem post correspondente)

---

## Comandos WP-CLI Disponíveis

### Diagnóstico
```bash
# Status da migração
wp apollo dms status

# Estatísticas
wp apollo dms stats

# Auditoria completa
wp apollo dms audit

# Reconciliação (detectar divergências)
wp apollo dms reconcile --dry-run
```

### Migração
```bash
# Migrar metakeys de assinatura
wp apollo dms migrate-signatures --dry-run
wp apollo dms migrate-signatures

# Migração completa
wp apollo dms migrate --dry-run
wp apollo dms migrate
```

### Cleanup
```bash
# Cleanup legado (após migração)
wp apollo dms cleanup --yes

# Forçar cleanup
wp apollo dms cleanup --force --yes

# Marcar fase manualmente
wp apollo dms complete-phase phase_4
```

---

## Checklist de Produção

### Pré-Deploy
- [ ] Backup completo do banco de dados
- [ ] Backup da pasta `wp-content/plugins/apollo-social`
- [ ] Feature flags configurados (módulos desabilitados se necessário)
- [ ] Testar em staging primeiro

### Deploy
- [ ] Upload dos novos arquivos
- [ ] Limpar cache de opcode (OPcache, etc)
- [ ] Verificar logs de erro

### Pós-Deploy
- [ ] `wp apollo dms status` - verificar fase atual
- [ ] `wp apollo dms migrate --dry-run` - preview
- [ ] `wp apollo dms migrate` - executar
- [ ] `wp apollo dms reconcile` - verificar sync
- [ ] Testar fluxo de assinatura end-to-end

### Rollback (se necessário)
```bash
# Reverter para backup
# Não há rollback automático - usar backup!
```

---

## Verificação Final

### Critérios de Sucesso
1. `wp apollo dms status` mostra `phase_6` como current
2. `wp apollo dms stats` mostra `legacy_metakey: 0`
3. Todas as assinaturas têm `post_id` preenchido
4. CPT count == index count

### Teste Funcional
1. Criar novo documento via interface
2. Transicionar para "signing"
3. Assinar documento (verificar CPF validado)
4. Verificar que signature tem `post_id`
5. Verificar URL de verificação funciona

---

## Suporte

### Logs
```bash
# Ver logs do Apollo
tail -f wp-content/uploads/apollo-logs/apollo-*.log
```

### Debug
```php
// Temporariamente habilitar debug
define( 'APOLLO_DEBUG', true );
```

### Contato
- Issues: GitHub do projeto
- Documentação: `APOLLO-PLUGINS-FINAL-README.md`

---

## Histórico de Versões

| Versão | Data | Mudança |
|--------|------|---------|
| 2.1.0 | 2024-XX | FASE 4-11 implementada |
| 2.0.0 | 2024-XX | FASE 0-3 (containment) |
| 1.x | - | Versão original (legado) |
