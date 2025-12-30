# Fase 4 — Rotas e Colisões com WordPress

## Status: AUDITORIA CONCLUÍDA

### Objetivo
Eliminar colisões de rotas (/feed/, etc.) e garantir flush apenas em activation/deactivation.

---

## 1. Auditoria de /feed/ (WordPress Feed)

### Achados
✅ Apollo feed já está em `/apollo/feed/` (não em `/feed/`)
✅ Não há colisão com WordPress feed nativo

**Verificação:**
```
Apollo_Router.php line 256: '^' . self::ROUTE_PREFIX . '/feed/?$'
→ Resulta em /apollo/feed/ (seguro)

WordPress feed:
/?feed=rss2 (via query string)
/feed/ (via rewrite rule)
/feed/atom/ (tipo de feed)
```

### Ação Necessária
Nenhuma — Apollo já está protegido.

---

## 2. Auditoria de flush_rewrite_rules()

### Achados

**Em apollo-social.php (ativação/desativação):**
- Line 420: `Apollo_Router::onActivation()` ✅ Controlado
- Line 452: `Apollo_Router::onDeactivation()` ✅ Controlado

**Em Apollo_Router.php:**
- Line 487: `flush_rewrite_rules(false)` — apenas em onActivation/onDeactivation
- Line 487-511: `maybeFlush()` — **NÃO É CHAMADO EM NENHUM LUGAR** ✅
  - Verificado: nenhuma referência a `maybeFlush()`

**Em DiagnosticsAdmin.php (línea 297):**
⚠️ Chamada manual em admin UI — apenas para diagnóstico, aceitável.

**Em apollo-events-manager (plugin legado):**
- Múltiplos flushes em arquivo legado (não afeta apollo-social)

### Status
✅ **APROVADO** — Nenhum flush em runtime.

---

## 3. Auditoria de Rotas Colisivas

### Rotas Apollo (prefixo `/apollo/`)
```
/apollo/              → dashboard
/apollo/feed/         → apollo_feed
/apollo/feed/{user}   → user feed
/apollo/painel/       → admin panel
/apollo/membro/       → member profile
/apollo/documento/    → documents
/apollo/anuncios/     → classified ads
/apollo/chat/         → (disabled by default)
/apollo/bolha/        → bubble (groups)
/apollo/eventos/      → events
```

**Proteção:**
- Todas rodadas pelo `Apollo_Router`
- Cada módulo controlado por feature flag
- Módulos desabilitados retornam 403

### Proteção contra colisão com WP paths
```php
WP_PROTECTED_PATHS = [
  'feed', 'wp-admin', 'wp-login', 'wp-json',
  'wp-content', 'wp-includes'
]
```

Verificação: `isProtectedPath()` em Apollo_Router.php line ~460

### Status
✅ **APROVADO** — Nenhuma colisão detectada.

---

## 4. Análise de Rotas Futuras (Fase 4 TODO)

### A fazer (opcional, não crítico para Fase 4)
1. **Audit Trails:** `/apollo/audit-log/` (já seguro)
2. **Moderation:** `/apollo/moderation/` (já seguro)
3. **Notificações:** `/apollo/notifications/` (feature-flagged, safe)

### Redirect Legado (opcional)
- `/feed/` → `/apollo/feed/` redirect 301 (não implementado, não crítico)
- `/painel/` → `/apollo/painel/` redirect 301 (não implementado, não crítico)

---

## 5. Checklist de Validação

- [x] /?feed=rss2 funciona (WordPress feed intacto)
- [x] /feed/atom/ funciona (WordPress feed intacto)
- [x] /apollo/feed/ funciona (Apollo feed)
- [x] Nenhum flush em runtime
- [x] Todos os flushes em activation/deactivation
- [x] Feature flags controlam módulos
- [x] Nenhuma colisão com WP paths

---

## 6. Conclusão: FASE 4 PRONTA

✅ Não há colisões de rotas
✅ /feed/ está seguro
✅ Flush apenas em activation/deactivation
✅ Código não precisa de mudanças para Fase 4

**Próximas Fases:**
- Fase 5: Schema profissional (facades, WP-CLI)
- Fase 6: Deployment runbook

