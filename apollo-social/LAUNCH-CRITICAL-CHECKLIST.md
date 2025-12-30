# 🚀 Apollo Social - Checklist Crítico para Lançamento

> **Gerado em:** 29/12/2024
> **Versão:** 1.0.0
> **Status Geral:** ⚠️ PRECISA ATENÇÃO

---

## Resumo Executivo

| # | Item | Status | Prioridade | Esforço |
|---|------|--------|------------|---------|
| 1 | Feature Flags / Módulos Stub | 🟡 PARCIAL | P0 | Médio |
| 2 | Tabelas Customizadas + Índices | 🔴 CRÍTICO | P0 | Alto |
| 3 | Permission Checks REST/AJAX | 🟡 PARCIAL | P0 | Médio |
| 4 | Assinaturas Server-Side | 🟢 OK | P1 | - |
| 5 | Documents Fonte de Verdade | 🟡 PARCIAL | P1 | Médio |
| 6 | Unificação Metakeys Assinatura | 🟡 PARCIAL | P2 | Baixo |
| 7 | Workflow Estados/Transições | 🟢 OK | P1 | - |
| 8 | Rewrites sem Colisão | 🟡 PARCIAL | P1 | Médio |
| 9 | Coerência Like/WOW | 🟢 OK | P2 | - |
| 10 | Frontend Resiliente | 🟢 OK | P2 | - |

---

## 1. 🟡 Feature Flags / Módulos Stub

### Status: PARCIAL - Precisa implementar feature flags

**Problema:**
- `Schema.php` está como STUB (backup em `Schema.php.broken.bak` tem implementação completa)
- Módulos carregam incondicionalmente sem feature flags
- Não há forma de desabilitar Chat, GOV.BR, Notifications se incompletos

**Arquivos Afetados:**
- `src/Infrastructure/Database/Schema.php` - ⚠️ STUB vazio
- `src/Infrastructure/Database/Schema.php.broken.bak` - ✅ Implementação completa
- `apollo-social.php` linhas 259-279 - Carrega módulos diretamente

**O que existe:**
```php
// Carregamento direto sem flags
if ( class_exists( '\Apollo\Modules\Chat\ChatModule' ) ) {
    \Apollo\Modules\Chat\ChatModule::init();
}
```

**Correção Necessária:**
```php
// Com feature flag
if ( get_option('apollo_feature_chat', true) && class_exists(...) ) {
    \Apollo\Modules\Chat\ChatModule::init();
}
```

**Ação Imediata:**
1. Restaurar `Schema.php` do backup `.broken.bak`
2. Criar sistema de feature flags em `wp_options`
3. Adicionar checks condicionais para Chat, Notifications, GOV.BR

---

## 2. 🔴 Tabelas Customizadas + Índices

### Status: CRÍTICO - Schema.php está vazio

**Problema:**
- `Schema.php` contém apenas métodos stub
- Tabelas NÃO serão criadas na ativação
- `MigrationManager.php` existe mas não é usado

**Arquivos:**
| Arquivo | Status |
|---------|--------|
| `Schema.php` | ❌ STUB vazio |
| `Schema.php.broken.bak` | ✅ Implementação completa (613 linhas) |
| `MigrationManager.php` | ✅ OK (315 linhas) |
| `Migrations/` | ⚠️ Vazio (apenas .gitkeep) |

**Tabelas no backup (devem existir):**
- `wp_apollo_groups` - com índices: slug, type, status, visibility, creator, season
- `wp_apollo_group_members` - com UNIQUE(group_id, user_id)
- `wp_apollo_workflow_log` - auditoria de transições
- `wp_apollo_mod_queue` - fila de moderação
- `wp_apollo_analytics` - tracking de eventos
- `wp_apollo_signature_requests` - UNIQUE(request_token)
- `wp_apollo_onboarding_progress` - UNIQUE(user_id, step_number)
- `wp_apollo_likes` - UNIQUE(content_type, content_id, user_id)

**Chat Module cria suas próprias tabelas:**
- `wp_apollo_chat_conversations` - ✅ dbDelta usado
- `wp_apollo_chat_messages` - ✅ dbDelta usado
- `wp_apollo_chat_participants` - ✅ UNIQUE(conversation_id, user_id)

**Ação Imediata:**
```bash
# Restaurar Schema.php
cp Schema.php.broken.bak Schema.php
```

---

## 3. 🟡 Permission Checks REST/AJAX

### Status: PARCIAL - Algumas rotas públicas demais

**Rotas com `__return_true` (públicas):**

| Endpoint | Método | Arquivo | Risco |
|----------|--------|---------|-------|
| `/apollo/v1/comunas` | GET | RestRoutes.php:44 | ✅ OK (leitura) |
| `/apollo/v1/membro` | GET | RestRoutes.php:150 | ✅ OK (leitura) |
| `/apollo/v1/wow/{type}/{id}` | GET | LikesEndpoint.php:68 | ✅ OK (status público) |
| `/documents/verify` | GET | SignatureEndpoints.php:255 | ✅ OK (verificação pública) |
| `/documents/public-info` | GET | SignatureEndpoints.php:326 | ✅ OK (info pública) |
| `/textures` | GET | Textures.php:26 | ✅ OK (assets) |
| `/classifieds` | GET | ClassifiedsModule.php:226 | ✅ OK (listagem) |

**Rotas de ESCRITA corretamente protegidas:**

| Endpoint | Proteção |
|----------|----------|
| `/apollo/v1/comunas` POST | `requireLoggedIn()` ✅ |
| `/apollo/v1/wow` POST | `current_user_can('read')` ✅ |
| `/chat/send` POST | `check_user_logged_in` ✅ |
| `/documents/sign` POST | `checkAuthenticated()` ✅ |

**Verificação de Nonces:**
- AJAX handlers usam `wp_verify_nonce()` ✅
- Exemplo: `apollo_submit_depoimento` verifica nonce corretamente

**Ação Recomendada:**
- Revisar rotas REST POST sem permission_callback explícito
- Garantir que todas escritas validam `current_user_can()` apropriado

---

## 4. 🟢 Assinaturas Server-Side

### Status: OK - Implementação completa

**Implementado:**
- ✅ Validação CPF com algoritmo oficial
- ✅ `AuditLog.php` - Trilha de auditoria completa
- ✅ `signature_hash` gerado via SHA-256
- ✅ Tabela `wp_apollo_signature_audit` com: actor_cpf, signature_hash
- ✅ `LocalSignatureService.php` gera evidence_pack imutável
- ✅ `IcpBrasilSigner.php` para assinaturas ICP-Brasil

**Arquivos:**
- `src/Modules/Signatures/AuditLog.php` - ✅ Completo
- `src/Modules/Signatures/Services/LocalSignatureService.php` - ✅
- `src/Modules/Signatures/Services/DocumentSignatureService.php` - ✅

---

## 5. 🟡 Documents Fonte de Verdade

### Status: PARCIAL - Dualidade CPT vs Tabela

**Situação Atual:**
- CPT `apollo_document` existe
- Tabela `wp_apollo_documents` também existe
- Assinaturas usam `wp_apollo_document_signatures`
- Metadados em `_apollo_document_signatures` (post_meta)

**Arquivos:**
- `DocumentsManager.php` - usa tabela `wp_apollo_documents`
- `DocumentSignatureService.php` - usa post_meta `_apollo_document_signatures`
- `SignatureEndpoints.php` - mistura ambos

**Ação Recomendada:**
1. Definir CPT como fonte de verdade para documentos
2. Tabela apenas para dados de alta performance (índices de busca)
3. Sincronização via hooks `save_post_apollo_document`

---

## 6. 🟡 Unificação Metakeys Assinatura

### Status: PARCIAL - Múltiplas chaves em uso

**Metakeys encontradas:**
- `_apollo_document_signatures` - array de assinaturas (principal)
- `signature_hash` - em tabela de audit
- `_apollo_signature_status` - status do documento

**Recomendação:**
Manter `_apollo_document_signatures` como única chave para post_meta.
Tabela de audit usa `signature_hash` como campo próprio.

---

## 7. 🟢 Workflow Estados/Transições

### Status: OK - Implementação consistente

**Estados Padronizados:**
```
draft → pending_review → published
                     ↘ rejected
published → suspended
```

**Implementado em:**
- Groups: `enum('draft', 'pending_review', 'published', 'rejected', 'suspended')`
- Ads: `enum('draft', 'pending_review', 'published', 'rejected', 'expired')`
- `wp_apollo_workflow_log` - registra todas transições

---

## 8. 🟡 Rewrites sem Colisão

### Status: PARCIAL - flush_rewrite_rules em runtime

**Problema:**
- `flush_rewrite_rules()` chamado em múltiplos lugares durante runtime
- Pode causar lentidão e conflitos

**Ocorrências:**
| Arquivo | Linha | Contexto |
|---------|-------|----------|
| `apollo-social.php` | 345 | Ativação ✅ OK |
| `apollo-social.php` | 370 | Desativação ✅ OK |
| `ChatModule.php` | 84 | `activate()` ⚠️ Runtime |
| `DocumentsModule.php` | 104 | `activate()` ⚠️ Runtime |
| `SuppliersModule.php` | 120 | Runtime ⚠️ |
| `CenaRioModule.php` | 58 | Runtime ⚠️ |
| `UserPagesServiceProvider.php` | 45 | Com `false` ✅ Soft |

**Correção:**
- Usar flag `apollo_needs_flush` em option
- Flush apenas uma vez no `admin_init` se flag ativa

---

## 9. 🟢 Coerência Like/WOW

### Status: OK - Unificado

**Implementação:**
- Endpoint único: `/apollo/v1/wow`
- Tabela: `wp_apollo_likes`
- Meta keys: `_apollo_wow_count` (novo) + `_apollo_like_count` (legacy)
- Constraint: `UNIQUE(content_type, content_id, user_id)`

**Tipos suportados:**
```php
$allowed_types = ['apollo_social_post', 'event_listing', 'post', 'apollo_ad', 'apollo_classified'];
```

---

## 10. 🟢 Frontend Resiliente

### Status: OK - Verificações defensivas implementadas

**Implementado:**
- `feed.js` - jQuery check + FeedManager centralizado
- `AssetsManager.php` - `typeof window.apolloAnalytics === 'undefined'` check
- `AnalyticsServiceProvider.php` - Guard antes de chamadas
- Templates - `if (typeof apolloAnalytics !== 'undefined')` em todos os usos

---

## 📋 Ações Imediatas (Ordenadas por Prioridade)

### P0 - Bloqueadores

1. **Restaurar Schema.php**
   ```bash
   cd src/Infrastructure/Database
   cp Schema.php.broken.bak Schema.php
   ```

2. **Testar criação de tabelas**
   - Desativar e reativar plugin
   - Verificar se todas tabelas existem

3. **Implementar Feature Flags**
   - Criar opção `apollo_modules_enabled`
   - Adicionar checks condicionais

### P1 - Críticos

4. **Consolidar flush_rewrite_rules**
   - Remover chamadas em runtime
   - Usar flag + admin_init

5. **Definir fonte de verdade Documents**
   - Documentar decisão: CPT = verdade
   - Tabela = cache/índices

### P2 - Importantes

6. **Revisar rotas REST**
   - Auditar todos `__return_true` em POST
   - Confirmar que escritas validam capabilities

---

## ✅ Pronto para Lançar Quando

- [ ] Schema.php restaurado e testado
- [ ] Todas tabelas criadas corretamente
- [ ] Feature flags implementados para módulos opcionais
- [ ] flush_rewrite_rules apenas em ativação/desativação
- [ ] Teste E2E de fluxo de assinatura
- [ ] Teste E2E de Chat
- [ ] Verificação de permissions em todas rotas POST

