# 📑 ÍNDICE GERAL - AUDITORIA APOLLO PLUGINS

**Gerado em:** 22 de janeiro de 2026
**Escopo:** apollo-core, apollo-events-manager, apollo-social
**Status:** ✅ COMPLETO

---

## 📄 ARQUIVOS DISPONÍVEIS

### 1. **APOLLO_AUDIT_SUMMARY.md** ⭐ COMECE AQUI

**Tipo:** Resumo executivo
**Público:** Product, Project Managers, QA
**Tamanho:** ~5KB
**Conteúdo:**

- Dashboard com estatísticas gerais
- Arquitetura visual
- Top CPTs, REST routes, shortcodes
- Problemas identificados (críticos e importantes)
- Checklist de verificação
- Próximos passos e roadmap

**Como usar:** Ler primeiro para entender big picture

---

### 2. **APOLLO_COMPLETE_AUDIT.md** 📚 REFERÊNCIA COMPLETA

**Tipo:** Auditoria técnica detalhada
**Público:** Developers, Architects, Tech Leads
**Tamanho:** ~150KB
**Conteúdo:**

- Cada CPT com detalhes completos
  - Slug, labels, argumentos
  - Arquivo de definição
  - Taxonomies associadas
  - Meta keys relacionadas

- Todas taxonomies (13+)
  - Hierarchical status
  - CPTs associados
  - Arquivo de definição

- Meta keys catalogadas (100+)
  - Post meta
  - User meta
  - Tipo e propósito

- REST API routes (50+)
  - Namespace, caminho, métodos
  - Callback functions
  - Arquivo de definição

- Shortcodes (40+)
  - Tag, callback
  - Propósito
  - Arquivo

- Admin menus & páginas (30+)
- Options & settings (15+)
- Scripts & styles enqueued
- Tabelas customizadas (25+)
  - Nome, colunas, propósito
  - CREATE TABLE SQL
  - Arquivo de definição

- Classes e namespaces
  - Estrutura PSR-4
  - Localizações de arquivo

- Hooks (100+ actions/filters)
  - Nome, tipo, propósito
  - Arquivo de disparo

- Problemas identificados
  - Colisões
  - Riscos
  - Recomendações

**Como usar:** Pesquisar elemento específico, seguir `Arquivo:` para localizar código

---

### 3. **APOLLO_AUDIT_DATA.json** 🔧 DADOS ESTRUTURADOS

**Tipo:** Dados em formato JSON
**Público:** DevOps, Automation, Integrations
**Tamanho:** ~80KB
**Conteúdo:**

```json
{
  "audit_metadata": {...},
  "plugins": {
    "apollo-core": {
      "cpts": [...],
      "taxonomies": [...],
      "rest_routes": [...],
      "shortcodes": [...],
      ...
    },
    ...
  },
  "global_meta_keys": {...},
  "global_options": [...],
  "global_hooks": {...},
  "risks_and_conflicts": [...],
  "statistics": {...}
}
```

**Como usar:** Parsear JSON para:

- Gerar documentação automatizada
- Validar contra banco de dados real
- CI/CD checks
- Dashboard de monitoramento

---

### 4. **APOLLO_AUDIT_INDEX.md** 🗂️ ESTE ARQUIVO

**Tipo:** Guia de navegação
**Público:** Todos
**Conteúdo:** Este índice que você está lendo

---

## 🎯 GUIA DE USO POR PERFIL

### 👔 Product Manager / Project Lead

**Arquivo:** APOLLO_AUDIT_SUMMARY.md
**Seções:**

- Resumo Executivo
- Dashboard Rápido
- Problemas Identificados
- Próximos Passos

**Tempo:** 10-15 min

---

### 👨‍💻 Developer / Engineer

**Arquivo:** APOLLO_COMPLETE_AUDIT.md
**Seções:**

- CPTs (procurar slug específico)
- Meta Keys (procurar key name)
- REST Routes (procurar endpoint)
- Classes (procurar class name)

**Dica:** Use Ctrl+F para procurar elemento

**Tempo:** Consulta conforme necessário

---

### 🏗️ Architect / Tech Lead

**Arquivos:** Todos (em ordem)

1. APOLLO_AUDIT_SUMMARY.md (visão geral)
2. APOLLO_COMPLETE_AUDIT.md (detalhes)
3. APOLLO_AUDIT_DATA.json (estrutura)

**Foco:** Seções de:

- Arquitetura
- Problemas/colisões
- Classes e namespaces
- Próximos passos

**Tempo:** 30-45 min

---

### 🔒 DevOps / Infrastructure

**Arquivos:** APOLLO_AUDIT_DATA.json + APOLLO_COMPLETE_AUDIT.md
**Foco:**

- Tabelas customizadas
- Options/settings
- Database schema
- Performance considerations

**Automação:**

```python
import json
with open('APOLLO_AUDIT_DATA.json') as f:
    data = json.load(f)
    tables = data['plugins']['apollo-core']['tables']
    for table in tables:
        print(f"CREATE {table['name']}...")
```

---

### 🧪 QA / Tester

**Arquivo:** APOLLO_AUDIT_SUMMARY.md
**Seção:** "Checklist de Verificação"

**Teste Plan:**

1. CPTs - Verificar 13 registrados
2. Taxonomies - Testar associações
3. REST API - 50+ endpoints
4. Shortcodes - Renderização
5. Admin Pages - Acessibilidade
6. Database - Tabelas existem
7. Meta Keys - Valores corretos

---

## 📊 ESTATÍSTICAS RÁPIDAS

```
Total de Análise:
├── 13 CPTs
├── 13+ Taxonomies
├── 50+ REST Routes
├── 40+ Shortcodes
├── 30+ Admin Pages
├── 25+ Tabelas BD
├── 100+ Meta Keys
├── 100+ Hooks
├── 150+ Classes
└── 200+ Arquivos analisados
```

---

## 🔍 PROCURAR ELEMENTO

### CPT "event_listing"

**Arquivo:** APOLLO_COMPLETE_AUDIT.md
**Seção:** "1. CPTs" → "CPT: event_listing"
**Também em:**

- APOLLO_AUDIT_DATA.json → plugins.apollo-events-manager.cpts

### REST Route "/eventos"

**Arquivo:** APOLLO_COMPLETE_AUDIT.md
**Seção:** "4. REST API Routes" → "apollo-core REST Routes" → "Route: /eventos"
**Também em:**

- APOLLO_AUDIT_DATA.json → plugins.apollo-core.rest_routes

### Meta Key "\_event_dj_ids"

**Arquivo:** APOLLO_COMPLETE_AUDIT.md
**Seção:** "3. Meta Keys Utilizadas" → "Post Meta Keys"
**Também em:**

- APOLLO_AUDIT_DATA.json → global_meta_keys.post_meta

### Shortcode "apollo_events_grid"

**Arquivo:** APOLLO_AUDIT_SUMMARY.md
**Seção:** "Shortcodes Disponíveis"
**Detalhes em:** APOLLO_COMPLETE_AUDIT.md → "5. Shortcodes" → apollo-events-manager

### Tabela "wp_apollo_activity_log"

**Arquivo:** APOLLO_COMPLETE_AUDIT.md
**Seção:** "11. Tabelas de Banco de Dados" → "apollo-core Tables" → "wp_apollo_activity_log"

### Hook "apollo_activated"

**Arquivo:** APOLLO_COMPLETE_AUDIT.md
**Seção:** "12. Hooks" → "Actions Principais"

---

## ⚠️ PROBLEMAS CRÍTICOS

### 1. Duplicidade CPT event_listing

**Localização:**

- apollo-core/modules/events/bootstrap.php:91
- apollo-events-manager/includes/post-types.php:95

**Ação:** Revisar qual plugin deve ser responsável

---

### 2. Legacy Meta Keys

**Localização:** APOLLO_COMPLETE_AUDIT.md → "Meta Keys Utilizadas"

**Exemplo:**

```
LEGACY:  _event_djs
NOVO:    _event_dj_ids
```

**Ação:** Planejar migration

---

### 3. REST API Namespace Inconsistência

**Localização:** APOLLO_COMPLETE_AUDIT.md → "4. REST API Routes"

**Namespaces:**

- apollo/v1 (core)
- apollo-events/v1 (events)
- apollo-social/v2 (social)

**Ação:** Padronizar para apollo/v2

---

## 🔗 RELACIONAMENTOS

### CPT → Taxonomies

```
event_listing
  ├── event_listing_category
  ├── event_listing_type
  ├── event_sounds
  └── event_season

event_dj
  └── event_sounds

apollo_classified
  └── classified_domain

apollo_social_post
  └── social_category

apollo_supplier
  ├── supplier_category
  ├── supplier_type
  ├── supplier_service
  └── ...
```

### Plugin → Responsabilidades

```
apollo-core
├── Infrastructure (identifiers, registry, hooks)
├── Security (moderation, verification)
├── Analytics (tracking, logging)
└── Integration (bridges)

apollo-events-manager
├── CPT: event_listing, event_dj, event_local
├── Modules: Calendar, Interest, Reviews, Speakers, Tracking
└── Features: Import/Export, Analytics, Notifications

apollo-social
├── CPT: user_page, apollo_classified, apollo_supplier, apollo_document
├── Modules: UserPages, Classifieds, Suppliers, Verification
└── Features: Groups, Documents, E-signatures
```

---

## 🚀 COMO USAR PARA DESENVOLVIMENTO

### Adicionando Novo CPT

1. Abrir APOLLO_COMPLETE_AUDIT.md
2. Procurar seção "1. CPTs"
3. Seguir padrão documentado
4. Registrar em Arquivo: indicado
5. Atualizar APOLLO_AUDIT_DATA.json
6. Regenerar auditoria

### Adicionando Novo Hook

1. Abrir APOLLO_COMPLETE_AUDIT.md
2. Seção "12. Hooks"
3. Adicionar em `do_action()` ou `apply_filters()`
4. Documentar propósito
5. Atualizar AUDIT_DATA.json

### Adicionando Nova Tabela BD

1. APOLLO_COMPLETE_AUDIT.md → "11. Tabelas"
2. Seguir padrão CREATE TABLE
3. Registrar em Arquivo: indicado
4. Adicionar migration script
5. Atualizar schema version em options

---

## 🔄 REGENERANDO A AUDITORIA

Para regenerar esta auditoria após mudanças:

```bash
# Execute os seguintes grep searches:
grep -r "register_post_type\|register_taxonomy" plugins/apollo-* --include="*.php"
grep -r "register_rest_route" plugins/apollo-* --include="*.php"
grep -r "add_shortcode" plugins/apollo-* --include="*.php"
grep -r "CREATE TABLE" plugins/apollo-* --include="*.php"
grep -r "get_post_meta\|update_post_meta\|get_user_meta\|update_user_meta" plugins/apollo-* --include="*.php"
grep -r "do_action\|apply_filters" plugins/apollo-* --include="*.php"
grep -r "^class |^namespace " plugins/apollo-*/**/*.php --include="*.php"
grep -r "add_menu_page\|add_submenu_page" plugins/apollo-* --include="*.php"
grep -r "get_option\|add_option\|update_option" plugins/apollo-* --include="*.php"
grep -r "wp_register_style\|wp_register_script" plugins/apollo-* --include="*.php"
```

---

## 📞 CONTATO / SUPORTE

**Perguntas sobre a auditoria?**

1. Verificar se mudança recente ocorreu no código
2. Se data < 1 mês: usar como referência
3. Se data > 1 mês: considerar regenerar
4. Verificar git log para mudanças

---

## 📅 HISTÓRICO DE VERSÕES

| Data       | Versão | Status       | Notas                                 |
| ---------- | ------ | ------------ | ------------------------------------- |
| 22/01/2026 | 1.0    | ✅ Completo  | Primeira auditoria completa exaustiva |
| -          | 1.1    | 📅 Planejado | Incluir performance metrics           |
| -          | 2.0    | 📅 Planejado | Após resolução de colisões críticas   |

---

## 🎓 APÊNDICE

### Padrões Encontrados

#### Naming Convention

- CPT: `apollo_*` ou `event_*` ou `user_page` ou `cena_*`
- Taxonomy: `event_*` ou `*_category`/`*_type`
- Meta: `_*` para post meta privada, sem `_` para public
- Options: `apollo_*` para core, `apollo_events_*` para events
- Hooks: `apollo_*` (padronizado)

#### Code Style

- Namespaces: PSR-4 `Apollo\*`
- Classes: `Snake_Case` (legacy) → `PascalCase` (novo)
- Functions: `snake_case`
- Files: `class-*.php` ou `*.php`

#### Architecture Patterns

- Module interface pattern (apollo-events-manager)
- Registry pattern (CPT/taxonomy registration)
- Hook registry pattern (apollo-core)
- Service provider pattern (apollo-social)

---

**FIM DO ÍNDICE**

Use este arquivo para navegar todos os recursos de auditoria!

---

_Última atualização: 22/01/2026_
_Próxima revisão recomendada: Q2 2026_
