## Análise Completa do Ecossistema Apollo - Auditoria dos 4 Plugins

Vou fazer uma auditoria completa baseada nos arquivos anexados para responder suas perguntas.

---

### 1. TESTES AUTOMATIZADOS - Status Atual

#### ✅ **Infraestrutura EXISTE, mas está incompleta**

| Plugin                    | PHPUnit        | Jest   | QA Scripts | Cobertura |
| ------------------------- | -------------- | ------ | ---------- | --------- |
| **apollo-core**           | ✅ Configurado | ❌ Não | ✅ Parcial | ~20%      |
| **apollo-events-manager** | ✅ Configurado | ❌ Não | ✅ Vários  | ~15%      |
| **apollo-social**         | ✅ Básico      | ❌ Não | ⚠️ Mínimo  | ~5%       |
| **apollo-rio**            | ❌ Não         | ❌ Não | ❌ Não     | 0%        |

#### Arquivos de Teste Encontrados:

```text
apollo-core/
├── phpunit.xml              ✅ Configurado
├── tests/                   ✅ Existe
│   └── (estrutura presente)

apollo-events-manager/
├── phpunit.xml              ✅ Configurado
├── tests/                   ✅ Existe
├── RUN-PRE-RELEASE-TESTS.php    ✅ QA Manual
├── EXECUTAR-VERIFICACOES-COMPLETAS.php  ✅ QA
├── test-local-slugs.php     ✅ Smoke test
├── test-map-coordinates.php ✅ Smoke test
├── test-meta-keys.php       ✅ Smoke test

apollo-social/
├── phpcs.xml                ✅ Linting apenas
├── phpstan.neon             ✅ Análise estática
├── tests/                   ⚠️ Estrutura básica
```

#### **Diagnóstico:**

```
🔴 CRÍTICO: Não há testes de integração cross-plugin
🟡 ALERTA: Jest/JS testing completamente ausente
🟢 OK: PHPUnit configurado em 3/4 plugins
🟢 OK: Scripts de QA manual existem
```

---

### 2. AMBIENTE DE STAGING - Status

#### ✅ **Local by Flywheel está configurado**

```text
Caminho: C:\Users\rafae\Local Sites\1212\app\public\

Estrutura detectada:
├── wp-content/
│   └── plugins/
│       ├── apollo-core/
│       ├── apollo-events-manager/
│       ├── apollo-social/
│       └── apollo-rio/
```

#### **Verificações Necessárias:**

Execute estes comandos para confirmar o ambiente:

```powershell
# Verificar versão do WordPress
cd "C:\Users\rafae\Local Sites\1212\app\public"
wp core version

# Verificar versão do PHP
php -v

# Verificar plugins ativos
wp plugin list --status=active

# Verificar estrutura do banco
wp db query "SHOW TABLES LIKE '%apollo%'"
```

#### **Diagnóstico Provável:**

```
🟢 OK: Ambiente local funcional
🟡 ALERTA: Precisa validar se espelha produção
🔴 CRÍTICO: Sem backup automatizado antes de testes
```

---

### 3. AUDITORIA COMPLETA DOS 4 PLUGINS

#### **APOLLO-CORE** (Fundação)

| Métrica       | Valor       | Status        |
| ------------- | ----------- | ------------- |
| Arquivos PHP  | ~80+        | 🟢            |
| Classes       | ~50+        | 🟢            |
| REST Routes   | ~15         | 🟢            |
| Hooks         | ~40         | 🟢            |
| Custom Tables | 15+         | 🟢            |
| Assets JS     | ~10         | 🟡 Precisa TS |
| PHPCS Status  | Configurado | 🟢            |
| PHPStan       | Configurado | 🟢            |

**Arquivos Críticos:**

```text
includes/
├── class-apollo-core.php           # Bootstrap principal
├── class-apollo-identifiers.php    # CRÍTICO: Source of truth
├── class-apollo-capabilities.php   # Permissões
├── class-apollo-cpt-registry.php   # CPTs centralizados
├── class-apollo-rest-controller.php # API base
```

---

#### **APOLLO-EVENTS-MANAGER** (Eventos)

| Métrica     | Valor                                                       | Status                   |
| ----------- | ----------------------------------------------------------- | ------------------------ |
| CPTs        | 4 (event_listing, event_dj, event_local, apollo_event_stat) | 🟢                       |
| Taxonomias  | 4                                                           | 🟢                       |
| Meta Keys   | 20+                                                         | 🟡 Migração em andamento |
| Shortcodes  | 19                                                          | 🟢                       |
| REST Routes | ~20                                                         | 🟢                       |
| Blocks      | 4                                                           | 🟢                       |
| Assets JS   | ~15                                                         | 🔴 Precisa TS urgente    |

**Arquivos Críticos:**

```text
includes/
├── post-types.php              # CPTs principais
├── class-rest-api.php          # Endpoints
├── admin-metaboxes.php         # Meta fields
├── event-helpers.php           # Funções utilitárias
├── migrations.php              # CRÍTICO: Migração de dados

blocks/
├── event-calendar/
├── events-grid/
├── featured-events/
```

**Problemas Identificados:**

```text
🔴 migrate-apollo-meta.php      # Migração pendente
🔴 RUN-MIGRATION-FIX-LEGACY-META.php  # Fix necessário
🟡 DUPLICITY-REPORT.md          # Duplicações detectadas
```

---

#### **APOLLO-SOCIAL** (Social)

| Métrica     | Valor                                                                                  | Status           |
| ----------- | -------------------------------------------------------------------------------------- | ---------------- |
| CPTs        | 5 (apollo_social_post, user_page, apollo_classified, apollo_supplier, apollo_document) | 🟢               |
| Módulos     | Groups, Classifieds, Documents, User Pages                                             | 🟢               |
| REST Routes | ~15                                                                                    | 🟢               |
| Blocks      | 4                                                                                      | 🟢               |
| Cena Rio    | Integrado                                                                              | 🟢               |
| Assets JS   | ~20                                                                                    | 🔴 Mais complexo |

**Arquivos Críticos:**

```text
src/
├── Plugin.php                  # Bootstrap
├── Schema.php                  # Estrutura de dados
├── Modules/                    # Sistema modular
├── Builder/                    # Page builder
├── CenaRio/                    # Módulo Cena Rio

cena-rio/
├── assets/
├── templates/
```

**Complexidade Alta:**

```text
🔴 class-plano-editor-assets.php  # Editor customizado
🔴 Builder/                        # Sistema complexo de builder
🟡 cena-rio/                       # Módulo semi-independente
```

---

#### **APOLLO-RIO** (PWA)

| Métrica        | Valor            | Status          |
| -------------- | ---------------- | --------------- |
| Templates      | 5                | 🟢              |
| Service Worker | Customizado      | 🟢              |
| PWA Module     | Integrado (fork) | 🟡              |
| Assets JS      | ~5               | 🟢 Menor escopo |

**Arquivos Críticos:**

```text
modules/pwa/              # Fork do plugin PWA
includes/
├── class-apollo-rio-core-integration.php
├── class-apollo-seo-handler.php

templates/
├── pagx_apolloapp.php    # Template PWA principal
├── pwa-redirector.php
```

---

### 4. VISÃO GERAL CRÍTICA - O QUE FAZER AGORA

#### **🔴 PRIORIDADE MÁXIMA (Fazer Primeiro)**

1. **Backup Completo**

```powershell
# Executar AGORA antes de qualquer mudança
cd "C:\Users\rafae\Local Sites\1212\app\public"
wp db export backup-pre-migration-$(Get-Date -Format "yyyyMMdd").sql
```

2. **Validar Migrações Pendentes**

```powershell
# Verificar se há migrações de meta keys pendentes
php apollo-events-manager/migrate-apollo-meta.php --dry-run
```

3. **Rodar Testes Existentes**

```powershell
# PHPUnit nos plugins que têm
cd apollo-core && vendor/bin/phpunit
cd ../apollo-events-manager && vendor/bin/phpunit
```

---

#### **🟡 PRIORIDADE ALTA (Esta Semana)**

| Tarefa                               | Plugin         | Esforço |
| ------------------------------------ | -------------- | ------- |
| Completar migração de meta keys      | events-manager | 2-4h    |
| Criar baseline de testes             | todos          | 4-8h    |
| Setup TypeScript básico              | core           | 2-4h    |
| Documentar dependências cross-plugin | todos          | 2-4h    |

---

#### **🟢 PRIORIDADE MÉDIA (Próximas Semanas)**

| Tarefa               | Descrição                 |
| -------------------- | ------------------------- |
| Migração JS→TS       | Começar pelo apollo-core  |
| Type hints PHP       | Funções públicas primeiro |
| Testes de integração | Cross-plugin              |
| CI/CD                | GitHub Actions            |

---

### 5. MAPA DE DEPENDÊNCIAS CRÍTICAS

```text
┌─────────────────────────────────────────────────────────────┐
│                      APOLLO ECOSYSTEM                        │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────┐                                           │
│  │ apollo-core  │ ◄── FUNDAÇÃO (todos dependem)             │
│  │              │     - Apollo_Identifiers                   │
│  │              │     - Capabilities                         │
│  │              │     - REST Base                            │
│  └──────┬───────┘                                           │
│         │                                                    │
│         ├────────────────┬────────────────┬────────────────┐ │
│         ▼                ▼                ▼                ▼ │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ │
│  │apollo-events │ │apollo-social │ │ apollo-rio   │ │   (futuro)    │ │
│  │   manager    │ │              │ │    (PWA)     │ │   plugins      │ │
│  │              │ │   ┌────────┐ │ │              │ │                │ │
│  │  - Events    │ │   │Cena Rio│ │ │  - Templates │ │                │ │
│  │  - DJs       │ │   │(módulo)│ │ │  - SW        │ │                │ │
│  │  - Locais    │ │   └────────┘ │ │              │ │                │ │
│  └──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘ │
│                                                              │
└─────────────────────────────────────────────────────────────┘

DEPENDÊNCIAS:
apollo-core ──► NENHUMA (base)
apollo-events-manager ──► apollo-core
apollo-social ──► apollo-core
apollo-rio ──► apollo-core, (opcional: events, social)
```

---

### 6. RECOMENDAÇÃO FINAL

#### **Decisão Crítica: Qual Caminho Seguir?**

| Opção                   | Tempo       | Risco       | Resultado                        |
| ----------------------- | ----------- | ----------- | -------------------------------- |
| **A) Produção Rápida**  | 1-2 semanas | Médio       | Deploy com mínimo de mudanças    |
| **B) Qualidade Máxima** | 4-6 semanas | Baixo       | Migração completa JS→TS          |
| **C) Híbrido**          | 2-3 semanas | Médio-Baixo | TS incremental + testes críticos |

#### **Minha Recomendação: Opção C (Híbrido)**

```text
Semana 1:
  ✓ Backup + validação de staging
  ✓ Completar migrações pendentes
  ✓ Setup TypeScript no apollo-core
  ✓ Baseline de testes

Semana 2:
  ✓ Migrar JS crítico para TS (core apenas)
  ✓ Type hints em funções públicas
  ✓ Testes de integração básicos

Semana 3:
  ✓ Expandir para events-manager
  ✓ CI/CD básico
  ✓ Deploy para staging final
```

---

### Próximos Passos Imediatos

Quer que eu:

1. **Gere os comandos de backup e validação** para executar agora?
2. **Crie o tsconfig.json** inicial para apollo-core?
3. **Identifique os arquivos JS específicos** que precisam migração prioritária?
4. **Analise o INVENTORY.md** para validar consistência de identificadores?

Qual direção você quer seguir?
