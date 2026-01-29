# Apollo Plugin Ecosystem - Coupling Hazards & Integration Reference

> **Version**: 1.0.0
> **Last Updated**: 2025-01-XX
> **Maintained by**: Apollo::Rio Team

Este documento centraliza todos os pontos de acoplamento, dependências e padrões de integração entre os plugins do ecossistema Apollo.

---

## 📋 Índice

1. [Arquitetura de Plugins](#arquitetura-de-plugins)
2. [Constantes Globais](#constantes-globais)
3. [Ordem de Carregamento](#ordem-de-carregamento)
4. [Custom Post Types (CPTs)](#custom-post-types-cpts)
5. [Taxonomias](#taxonomias)
6. [Hooks de Integração](#hooks-de-integração)
7. [REST API Namespaces](#rest-api-namespaces)
8. [Options (wp_options)](#options-wp_options)
9. [User Meta Keys](#user-meta-keys)
10. [Módulos e Configurações](#módulos-e-configurações)
11. [Mitigações Implementadas](#mitigações-implementadas)

---

## 1. Arquitetura de Plugins

```
┌─────────────────────────────────────────────────────────────────┐
│                        APOLLO CORE                               │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │ Foundation Layer:                                            │ │
│  │ - Autoloader, I18n, REST Controller Base                    │ │
│  │ - CPT Registry, Integration Bridge, SchemaOrchestrator      │ │
│  │ - Analytics, Moderation, Communication subsystems           │ │
│  └─────────────────────────────────────────────────────────────┘ │
│                              │                                    │
│         ┌────────────────────┼────────────────────┐              │
│         ▼                    ▼                    ▼              │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐        │
│  │   SOCIAL    │     │   EVENTS    │     │    RIO      │        │
│  │   MODULE    │     │   MODULE    │     │   MODULE    │        │
│  │  (fallback) │     │  (fallback) │     │  (PWA/SEO)  │        │
│  └─────────────┘     └─────────────┘     └─────────────┘        │
└─────────────────────────────────────────────────────────────────┘
         │                    │                    │
         ▼                    ▼                    ▼
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│ APOLLO SOCIAL   │  │ APOLLO EVENTS   │  │  APOLLO RIO     │
│    (main)       │  │ MANAGER (main)  │  │    (main)       │
│                 │  │                 │  │                 │
│ - user_page CPT │  │ - event_listing │  │ - PWA Builders  │
│ - Social Posts  │  │ - event_dj      │  │ - SEO Handler   │
│ - Feed, Chat    │  │ - event_local   │  │ - Templates     │
│ - Documents     │  │ - event_stat    │  │                 │
└─────────────────┘  └─────────────────┘  └─────────────────┘
```

### Dependências Declaradas (WordPress 6.5+)

```php
// apollo-social/apollo-social.php
* Requires Plugins: apollo-core

// apollo-events-manager/apollo-events-manager.php
* Requires Plugins: apollo-core

// apollo-rio/apollo-rio.php
* Requires Plugins: apollo-core
```

---

## 2. Constantes Globais

### ✅ Constantes com Guards (if !defined)

| Constante                  | Plugin                | Arquivo                       | Valor                    |
| -------------------------- | --------------------- | ----------------------------- | ------------------------ |
| `APOLLO_CORE_VERSION`      | apollo-core           | apollo-core.php               | '1.0.0'                  |
| `APOLLO_CORE_PLUGIN_DIR`   | apollo-core           | apollo-core.php               | plugin_dir_path()        |
| `APOLLO_CORE_PLUGIN_URL`   | apollo-core           | apollo-core.php               | plugin_dir_url()         |
| `APOLLO_CORE_BOOTSTRAPPED` | apollo-core           | apollo-core.php               | true                     |
| `APOLLO_SOCIAL_VERSION`    | apollo-social         | apollo-social.php             | '1.0.0'                  |
| `APOLLO_APRIO_VERSION`     | apollo-events-manager | apollo-events-manager.php     | '1.0.0'                  |
| `APOLLO_APRIO_PATH`        | apollo-events-manager | apollo-events-manager.php     | plugin_dir_path()        |
| `APOLLO_RIO_VERSION`       | apollo-rio            | apollo-rio.php                | '1.0.0'                  |
| `APOLLO_RIO_PATH`          | apollo-rio            | apollo-rio.php                | plugin_dir_path()        |
| `APOLLO_RIO_URL`           | apollo-rio            | apollo-rio.php                | plugin_dir_url()         |
| `APOLLO_DEBUG`             | apollo-events-manager | apollo-events-manager.php     | false                    |
| `APOLLO_PORTAL_DEBUG`      | apollo-events-manager | apollo-events-manager.php     | WP_DEBUG && APOLLO_DEBUG |
| `APOLLO_API_NAMESPACE`     | apollo-core           | apollo-template-functions.php | 'apollo/v1'              |
| `APOLLO_EVENTS_NAMESPACE`  | apollo-core           | apollo-template-functions.php | 'apollo-events/v1'       |
| `APOLLO_BUILDER_VERSION`   | apollo-social         | Builder/init.php              | '1.0.0'                  |

### Legacy Aliases (Deprecated)

```php
// Em apollo-rio.php - aliases para compatibilidade retroativa
define('APOLLO_PATH', APOLLO_RIO_PATH);   // Use APOLLO_RIO_PATH
define('APOLLO_URL', APOLLO_RIO_URL);     // Use APOLLO_RIO_URL
```

---

## 3. Ordem de Carregamento

### Prioridade de Hooks (plugins_loaded)

```
1. apollo-core (priority 5)    - Define APOLLO_CORE_BOOTSTRAPPED
2. apollo-social (priority 10) - Verifica APOLLO_CORE_BOOTSTRAPPED
3. apollo-events (priority 10) - Verifica APOLLO_CORE_BOOTSTRAPPED
4. apollo-rio (priority 10)    - Verifica APOLLO_CORE_BOOTSTRAPPED
```

### Verificação de Dependência (Padrão)

```php
// Em plugins dependentes
if (! defined('APOLLO_CORE_BOOTSTRAPPED') && ! class_exists('Apollo_Core')) {
    add_action('admin_notices', 'my_plugin_missing_core_notice');
    return; // Abort loading
}
```

---

## 4. Custom Post Types (CPTs)

### CPTs Registrados

| CPT Slug             | Plugin Principal      | Plugin Fallback            | Priority |
| -------------------- | --------------------- | -------------------------- | -------- |
| `user_page`          | apollo-social         | apollo-core/modules/social | 10       |
| `event_listing`      | apollo-events-manager | apollo-core/modules/events | 10       |
| `event_dj`           | apollo-events-manager | N/A                        | -        |
| `event_local`        | apollo-events-manager | N/A                        | -        |
| `apollo_event_stat`  | apollo-events-manager | N/A                        | -        |
| `apollo_social_post` | apollo-social         | N/A                        | -        |

### ✅ Proteção Implementada

Todos os CPTs agora usam `post_type_exists()` antes do registro:

```php
if ( ! post_type_exists( 'event_listing' ) ) {
    register_post_type('event_listing', $args);
}
```

---

## 5. Taxonomias

| Taxonomy                 | CPT           | Plugin                |
| ------------------------ | ------------- | --------------------- |
| `event_listing_category` | event_listing | apollo-events-manager |
| `event_listing_type`     | event_listing | apollo-events-manager |
| `event_listing_tag`      | event_listing | apollo-events-manager |
| `event_sounds`           | event_listing | apollo-events-manager |
| `event_season`           | event_listing | apollo-events-manager |

---

## 6. Hooks de Integração

### Hooks Críticos do Apollo Core

```php
/**
 * Fired when Apollo Core's Integration Bridge is ready.
 * Other plugins should hook here to register with the bridge.
 *
 * @param Apollo_Integration_Bridge $bridge The bridge instance.
 *
 * @example
 * add_action('apollo_integration_bridge_ready', function($bridge) {
 *     $bridge->register_plugin('my-plugin', [...]);
 * });
 */
do_action('apollo_integration_bridge_ready', $bridge);

/**
 * Register schema modules with the SchemaOrchestrator.
 *
 * @param Apollo_SchemaOrchestrator $orchestrator The orchestrator instance.
 *
 * Priorities:
 * - apollo-core: 10
 * - apollo-social: 20
 * - apollo-events-manager: 30
 * - apollo-rio: 40
 */
do_action('apollo_register_schema_modules', $orchestrator);

/**
 * Fired after Apollo Core displays event content.
 *
 * @param int    $event_id The event post ID.
 * @param string $context  Display context ('single', 'archive', 'widget').
 */
do_action('apollo_core_after_event_display', $event_id, $context);

/**
 * Fired after Apollo Events post types are loaded.
 */
do_action('apollo_events_post_types_loaded');

/**
 * Filter to modify Apollo Events Manager settings.
 *
 * @param array $settings Current settings.
 * @return array Modified settings.
 */
apply_filters('apollo_events_settings', $settings);
```

---

## 7. REST API Namespaces

| Namespace          | Plugin                | Uso                       |
| ------------------ | --------------------- | ------------------------- |
| `apollo/v1`        | Shared                | Main unified API          |
| `apollo-core/v1`   | apollo-core           | Core-specific endpoints   |
| `apollo-events/v1` | apollo-events-manager | Events-specific endpoints |

### Rotas Principais

Ver [REST.md](./REST.md) para inventário completo de rotas.

---

## 8. Options (wp_options)

### Convenção de Nomenclatura

| Plugin                | Prefixo Recomendado                    |
| --------------------- | -------------------------------------- |
| apollo-core           | `apollo_core_*` ou `apollo_*` (global) |
| apollo-social         | `apollo_social_*`                      |
| apollo-events-manager | `apollo_events_*`                      |
| apollo-rio            | `apollo_rio_*`                         |

### ⚠️ Conflitos Resolvidos

- `event_manager_osm_*` → `apollo_events_osm_*` (renomeado)

### Options Críticas

```php
// Versões de Schema (migração)
'apollo_core_schema_version'     // apollo-core
'apollo_social_schema_version'   // apollo-social
'apollo_events_schema_version'   // apollo-events-manager

// Flags de Módulos
'apollo_core_mod_enabled'        // Toggle de moderação
'apollo_core_quiz_enabled'       // Toggle de quiz
'apollo_events_map_enabled'      // Toggle de mapa
'apollo_events_favorites_enabled' // Toggle de favoritos

// Configurações
'apollo_unified_settings'        // Settings unificados
'apollo_memberships'             // Memberships
```

---

## 9. User Meta Keys

| Meta Key                  | Plugin                | Descrição                                 |
| ------------------------- | --------------------- | ----------------------------------------- |
| `_apollo_events_attended` | apollo-events-manager | Array de event IDs que usuário participou |
| `_apollo_user_page_id`    | apollo-social         | ID do user_page do usuário                |
| `_apollo_referral_code`   | apollo-social         | Código de referral único                  |
| `_apollo_points`          | apollo-core           | Pontos de gamificação                     |
| `_apollo_badges`          | apollo-core           | Array de badges conquistados              |

---

## 10. Módulos e Configurações

### Sincronização de Módulos

Quando `apollo_core_mod_enabled` muda, outros plugins devem reagir:

```php
// Em apollo-core
add_action('update_option_apollo_core_mod_enabled', function($old, $new) {
    do_action('apollo_module_state_changed', 'moderation', $new);
}, 10, 2);

// Em plugins dependentes
add_action('apollo_module_state_changed', function($module, $enabled) {
    if ($module === 'moderation' && !$enabled) {
        // Desabilitar features dependentes
    }
});
```

---

## 11. Mitigações Implementadas

### ✅ Correções Aplicadas

1. **Constantes com Guards**

   - Todas constantes agora usam `if (!defined())`
   - Previne erros de redefinição

2. **Funções com Guards**

   - Todas funções `apollo_*` públicas usam `if (!function_exists())`
   - 60+ funções protegidas em apollo-template-functions.php

3. **CPT Registration Guards**

   - `post_type_exists()` check antes de cada `register_post_type()`
   - Previne conflitos entre plugins principal e fallback

4. **Plugin Dependency Headers**

   - `Requires Plugins: apollo-core` em todos plugins dependentes
   - WordPress 6.5+ mostra erro se Core não estiver ativo

5. **Option Key Prefixes**
   - Renomeado `event_manager_osm_*` → `apollo_events_osm_*`
   - Padrão estabelecido para novos options

### 🔧 Recomendações Futuras

1. **Email Templates Conflict**

   - Considerar renomear:
     - apollo-core: `apollo_core_email_templates`
     - apollo-social: `apollo_social_email_templates`

2. **Centralizar Apollo_Identifiers**

   - Usar constantes de `Apollo_Core\Apollo_Identifiers` em todos plugins
   - Evita hardcoded slugs espalhados

3. **Implementar Migração de Options**
   - Ao renomear option keys, criar migration script
   - `get_option()` com fallback para nome antigo

---

## 📝 Changelog

### 1.0.0 (2025-01-XX)

- Documento inicial criado
- Documentadas todas as constantes, CPTs, taxonomias, hooks e options
- Listadas mitigações implementadas e recomendações futuras
