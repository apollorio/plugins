# SUITE HANDOFF NOTE — Apollo Ecosystem

**Data**: 30 de Dezembro de 2025
**De**: apollo-social Release Manager
**Para**: apollo-core, apollo-rio, apollo-events-manager Teams

---

## STATUS: apollo-social FECHADO ✅

O plugin **apollo-social v2.3.0** está **CLOSED** e aprovado para produção.

### Escopo Concluído
- ✅ Groups Module (Comunas/Núcleos) com REST API segura
- ✅ Migrations idempotentes com version gating
- ✅ RestSecurity (nonce + capability + rate-limit)
- ✅ Apollo_Router centralizado
- ✅ WP-CLI commands (schema:status, schema:upgrade, groups:reconcile)
- ✅ Documentação completa (runbook, checklist, closure report)

---

## PRÓXIMOS ALVOS

| Plugin | Prioridade | Foco Principal |
|--------|------------|----------------|
| **apollo-core** | 🔴 Alta | Centralizar Router do Suite, Schema Orchestrator |
| **apollo-rio** | 🟡 Média | Alinhar com padrões RestSecurity |
| **apollo-events-manager** | 🟡 Média | Migrar rewrites para Router, audit de permissions |

---

## PADRÕES DO SOCIAL → PADRÕES DO SUITE

Os seguintes componentes do apollo-social devem ser **exportados como padrão do suite**:

### 1. RestSecurity (OBRIGATÓRIO)
**Arquivo**: `apollo-social/src/Api/RestSecurity.php`

```php
// Uso em permission_callback:
'permission_callback' => fn($r) => RestSecurity::verify($r, 'capability_name')
```

**Funcionalidades**:
- Verificação de login obrigatória
- Nonce validation em POST/PUT/PATCH/DELETE
- Capability check opcional
- Rate limiting por user+action+target

**Ação**: Mover para `apollo-core` e importar em todos plugins.

### 2. Router Policy (OBRIGATÓRIO)
**Arquivo**: `apollo-social/src/Infrastructure/Http/Apollo_Router.php`

**Regras**:
- `add_rewrite_rule` APENAS via Router centralizado
- `flush_rewrite_rules` APENAS em activation/deactivation
- Version gating com `RULES_VERSION`
- Protected paths (feed, wp-json, wp-admin, etc.)

**Ação**: Criar `ApolloSuiteRouter` em apollo-core que todos plugins usam.

### 3. Schema Gating (OBRIGATÓRIO)
**Arquivo**: `apollo-social/src/Infrastructure/Database/Migrations.php`

**Regras**:
- Migrations version-gated (só rodam se versão < alvo)
- Uso de `dbDelta()` para idempotência
- WP-CLI commands para verificar/executar
- Rollback não requer DB restore (migrations aditivas)

**Ação**: Criar `ApolloSchemaFacade` em apollo-core.

---

## SUITE BLOCKERS IDENTIFICADOS

| # | Blocker | Plugin Afetado | Ação Requerida |
|---|---------|----------------|----------------|
| 1 | `add_rewrite_rule` disperso em módulos | apollo-events-manager | Migrar para Suite Router |
| 2 | `flush_rewrite_rules` em runtime | apollo-rio (verificar) | Remover, usar activation-only |
| 3 | `__return_true` em writes REST | AUDIT TODOS | Substituir por RestSecurity::verify |
| 4 | Múltiplas tabelas com prefixo `apollo_` sem owner claro | ALL | Definir ownership matrix |
| 5 | WP-CLI namespace conflitos (`wp apollo` vs `wp apollo-events`) | ALL | Unificar sob `wp apollo` com subcommands |

---

## CHECKLIST PARA PRÓXIMO PLUGIN

Antes de declarar qualquer plugin "CLOSED", executar:

```bash
# 1. Zero flush_rewrite_rules runtime
grep -rn "flush_rewrite_rules" src | grep -v "activation\|deactivation\|// NOTE"
# MUST return: empty

# 2. Zero add_rewrite_rule fora do Router
grep -rn "add_rewrite_rule" src | grep -v "Router\|// NOTE"
# MUST return: empty

# 3. Zero __return_true em writes
grep -rn "register_rest_route" src | grep -E "POST|PUT|DELETE" | grep "__return_true"
# MUST return: empty

# 4. RestSecurity em todos endpoints protegidos
grep -rn "RestSecurity::verify\|permission_callback.*is_user_logged_in" src
# MUST return: >= 1 por endpoint write

# 5. Schema migrations idempotentes
grep -rn "dbDelta\|IF NOT EXISTS" src/Database src/Infrastructure/Database
# MUST return: >= 1 por tabela
```

---

## PRÓXIMOS PASSOS IMEDIATOS

1. **apollo-core (URGENTE)**:
   - [ ] Criar `ApolloSuiteRouter` exportável
   - [ ] Criar `RestSecurity` como trait/class base
   - [ ] Criar `SchemaFacade` com orchestrator

2. **apollo-rio**:
   - [ ] Audit de rewrites e flush
   - [ ] Migrar para Suite Router

3. **apollo-events-manager**:
   - [ ] Audit de REST permissions
   - [ ] Migrar add_rewrite_rule para Suite Router
   - [ ] Implementar RestSecurity em endpoints

---

## CONTATO

Dúvidas sobre o padrão apollo-social: revisar documentação em:
- `apollo-social/FASES-0-6-SUMMARY-EXECUTIVO.md`
- `apollo-social/DEPLOYMENT-RUNBOOK-2-3-0.md`
- `apollo-social/CLOSURE-REPORT-2-3-0.md`

---

**CLOSED: YES** ✅
**NEXT FOCUS: apollo-core, apollo-rio, apollo-events-manager**
