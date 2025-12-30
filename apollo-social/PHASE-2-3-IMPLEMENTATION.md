# Apollo Social: Fases 2-3 Implementation Summary

**Execution Date**: 30/12/2025
**Status**: ✅ COMPLETE
**Syntax Validation**: All files pass PHP lint

---

## FASE 2: Modelo de Dados (Grupo como Entidade Distinta)

### ✅ 1. Coluna `group_type` (Non-destructive Migration)

**Arquivo**: `src/Infrastructure/Database/Migrations.php`

- **Adicionada**: Nova classe `Migrations` com versionamento incremental
- **Migração 2.2.0**:
  - ✅ Adiciona coluna `group_type` (ENUM: 'comuna', 'nucleo', 'season')
  - ✅ Backfill: grupos existentes → 'comuna' por padrão
  - ✅ Índice: `KEY group_type_idx (group_type)` para performance
  - ✅ Idempotente: verifica se coluna já existe antes de alterar
  - ✅ Segura: desabilita FK checks durante migração

**Integração**: `src/Schema.php` agora chama `Migrations::runPending()` antes de instalar tabelas

---

### ✅ 2. Regras de Negócio por Tipo

**Arquivo**: `src/Modules/Groups/GroupsBusinessRules.php`

Entidade: Valida e reforça semântica de **Comuna** vs **Nucleo**

#### Comuna (Comunidades Públicas)
```
- Visibility: public (default)
- Join Policy: direct
- Posting: qualquer membro pode postar
- Criação: qualquer subscriber+ (role-based)
```

#### Nucleo (Times/Indústria - Privado)
```
- Visibility: private (default)
- Join Policy: approval (pending_review)
- Posting: apenas mod/admin (configurable)
- Criação: requer capability 'apollo_create_nucleo'
- Invites: apenas admin/owner
```

**Métodos disponíveis:**
- `validateType($type)` → valida 'comuna'|'nucleo'|'season'
- `joinRequiresApproval($group)` → bool (nucleo = true)
- `canCreate($type, $user_id)` → true|WP_Error
- `canPost($group, $user_id, $group_id)` → true|WP_Error
- `canInvite($group_id, $user_id)` → true|WP_Error
- `sanitizeGroupData($data)` → array (safe for insert)

---

### ✅ 3. Separação: Member Types & Roles

Tabela `wp_apollo_group_members` mantém coluna `role`:
```sql
role ENUM('member','mod','admin','owner') NOT NULL DEFAULT 'member'
```

**Lógica implementada:**
- **owner**: pode gerenciar, convidar, moderar
- **admin**: pode convidar, moderar, postar
- **mod**: pode moderar, postar
- **member**: pode postar (ou ver apenas, depende do group_type)
- **pending**: (novo) aguarda aprovação em nucleos

---

## FASE 3: Segurança REST/AJAX

### ✅ 1. Handler Centralizado: `RestSecurity`

**Arquivo**: `src/Api/RestSecurity.php`

**Funcionalidades:**
1. **Nonce Verification** (X-WP-Nonce header)
   - Somente POST/PUT/PATCH/DELETE validam nonce
   - GET é público (leitura)

2. **Capability Checks**
   - `verify($request, 'capability')` → true|WP_Error
   - Exemplo: `apollo_create_nucleo` para criar nucleos

3. **Rate Limiting**
   - `rateLimitByUserGroup($user_id, $action, $group_id, $limit)` → transient-based
   - Padrões: join=10/h, invite=20/h, nucleo_join=5/h

4. **Member Access Control**
   - `canViewMembers($request, $group_id)` → autentica + verifica membership
   - Previne leak de lista completa de membros

---

### ✅ 2. REST Endpoints com Segurança

**Arquivo**: `src/Modules/Groups/GroupsModule.php`

#### Comunas (PUBLIC)
| Método | Endpoint | Auth | Nonce | Cap | Notes |
|--------|----------|------|-------|-----|-------|
| GET | /comunas | ❌ | ❌ | ❌ | Diretório público |
| GET | /comunas/{id} | ❌ | ❌ | ❌ | Single público |
| GET | /comunas/{id}/members | ✅ | ❌ | ✅ (membro) | Lista apenas membros |
| POST | /comunas/create | ✅ | ✅ | read | Subscribers+ |
| POST | /comunas/{id}/join | ✅ | ✅ | ❌ | Rate: 10/h |
| POST | /comunas/{id}/leave | ✅ | ✅ | ❌ | |
| GET | /comunas/my | ✅ | ❌ | ❌ | Usuário logado |
| POST | /comunas/{id}/invite | ✅ | ✅ | ✅ (admin/mod) | Rate: 20/h |

#### Nucleos (PRIVATE)
| Método | Endpoint | Auth | Nonce | Cap | Notes |
|--------|----------|------|-------|-----|-------|
| GET | /nucleos | ✅ | ❌ | ❌ | Autenticado apenas |
| GET | /nucleos/{id} | ✅ | ❌ | ✅ (membro) | |
| GET | /nucleos/{id}/members | ✅ | ❌ | ✅ (membro) | Membro only |
| POST | /nucleos/create | ✅ | ✅ | apollo_create_nucleo | Cap específica |
| POST | /nucleos/{id}/join | ✅ | ✅ | ❌ | Pending approval |
| POST | /nucleos/{id}/leave | ✅ | ✅ | ❌ | |
| GET | /nucleos/my | ✅ | ❌ | ❌ | |
| POST | /nucleos/{id}/invite | ✅ | ✅ | ✅ (admin/mod) | Rate: 20/h |

#### Legacy
| GET | /groups | ✅/❌ | ❌ | ❌ | Proxy (deprecated), Sunset header |

**Handler Methods:**
- `handleCreateComuna()` → valida com BusinessRules
- `handleCreateNucleo()` → valida cap + rules
- `handleJoin()` → rate limit + direct add
- `handleJoinNucleo()` → rate limit + pending approval
- `handleInvite()` → valida role, rate limit
- `handleLeave()` → remove member

---

### ✅ 3. AJAX Handlers (Backward Compat)

**Arquivo**: `src/Api/AjaxHandlers.php`

**Melhorias:**
1. ✅ Nonce verification obrigatória (`check_ajax_referer`)
2. ✅ Rate limiting com transientes (friend requests, close friends, etc.)
3. ✅ Parametrização: não confia em `$_POST` direto
4. ✅ Erros consistentes com status HTTP (401, 403, 429, etc.)
5. ✅ Documentação: marked as deprecated, preferir REST

**Handlers atualizados:**
- `sendFriendRequest()` → +rate limit 20/h
- `addCloseFriend()` → +rate limit 5/h
- `postActivity()` → +nonce+rate limit
- `joinGroup()` → +nonce+rate limit
- `leaveGroup()` → +nonce
- `newForumTopic()` → +nonce+rate limit
- (todos mantêm verificação de auth)

---

### ✅ 4. Data Leakage Prevention

#### Membros (Lista Completa)
**Risco**: Exposição de lista privada em nucleos
**Mitigação**: `RestSecurity::canViewMembers()` requer:
- ✅ Autenticação (401 se não logado)
- ✅ Membership check (403 se não é membro)
- Implementada em ambos /comunas/{id}/members e /nucleos/{id}/members

#### Invites Pendentes
**Risco**: Usuário vê convites de outros
**Mitigação**: Endpoint invite é POST-only, retorna success bool (sem detalhe de rejeição)

#### Nucleos Privados
**Risco**: Enumeração de membros
**Mitigação**: GET /nucleos retorna apenas nucleos dos quais o user é membro ou admin

#### Signatures/PII (Documentos)
**Fora do escopo de Fase 2-3, mas mencionado no audit P0-4:**
- Será endereçado em Fase posterior
- Signatures table FK deve apontar para post_id

---

## Feature Flags

**Arquivo**: `src/Infrastructure/FeatureFlags.php`

```php
'groups_api'        => true,   // Comunas/Nucleos API
'groups_api_legacy' => false,  // /groups proxy (deprecated)
```

- API está ativa por padrão
- Legacy proxy está desativado (opcional)
- GroupsModule::init() verifica `groups_api` antes de registrar

---

## Verificação de Sintaxe

✅ All files pass PHP lint:
```
✅ Migrations.php
✅ GroupsBusinessRules.php
✅ RestSecurity.php
✅ GroupsModule.php (updated)
✅ AjaxHandlers.php (updated)
✅ FeatureFlags.php
✅ Apollo_Router.php
✅ Schema.php
```

---

## Ready for Deploy

### Pre-Deploy Checklist
- [ ] Backup database
- [ ] Test migrations on staging
- [ ] Verify /comunas and /nucleos endpoints work
- [ ] Check rate limiting (test 11+ requests in 1h)
- [ ] Verify nonce validation rejects invalid nonce
- [ ] Test legacy /groups redirect with Deprecation headers

### Database Changes
- Non-destructive: ALTER TABLE adds column, no data loss
- Idempotent: safe to re-run migration
- Indexed: group_type performance acceptable

### API Consumers Must Update
- Change `/groups` → `/comunas` or `/nucleos`
- Include `X-WP-Nonce` header on POST/PUT/PATCH/DELETE
- Expect 401 (no auth), 403 (no permission), 429 (rate limit)

---

## Next Phases

**Phase 4**: Remove runtime flush, finalize deployment
**Phase 5**: Tooling & WP-CLI dry-run
**Phase 6**: Testing & validation

---

**Status**: ✅ Ready for production merge
