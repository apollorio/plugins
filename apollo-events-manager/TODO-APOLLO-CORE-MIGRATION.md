# Apollo Core Migration - TO-DO List

**Status**: 📋 Planning Phase
**Priority**: P1 (Post-P0 Implementation)
**Target**: Unify Apollo Events Manager + Apollo Social into Apollo Core
**Version**: 3.0.0 (Future)

---

## 📋 ÍNDICE

1. [Audit & Prep](#1-audit--prep)
2. [Scaffold Apollo Core](#2-scaffold-apollo-core)
3. [Move & Adapt Code](#3-move--adapt-code)
4. [Migration & Compatibility](#4-migration--compatibility)
5. [Admin & MOD](#5-admin--mod)
6. [Testing & CI](#6-testing--ci)
7. [Docs & Rollout](#7-docs--rollout)

---

## 1. Audit & Prep

### 1.1. Listar arquivos e endpoints atuais

**Status**: ⏳ Pending
**Owner**: TBD
**Priority**: P1

**Tasks**:
- [ ] **Mapear CPTs em apollo-events-manager**
  - [ ] `event_listing` (post type, meta keys, taxonomies)
  - [ ] `event_dj` (post type, meta keys)
  - [ ] `event_local` (post type, meta keys, co-authors)
  - [ ] Documentar todos os meta keys usados
  - [ ] Listar todas as taxonomias

- [ ] **Mapear CPTs em apollo-social**
  - [ ] `apollo_social_post` (post type, meta keys)
  - [ ] `user_page` (post type, meta keys, widgets)
  - [ ] Documentar estrutura de widgets
  - [ ] Listar meta keys de user pages

- [ ] **Mapear Custom Tables**
  - [ ] `wp_apollo_groups` (structure, indexes)
  - [ ] `wp_apollo_group_members` (structure, indexes)
  - [ ] `wp_apollo_likes` (structure, indexes)
  - [ ] `wp_apollo_documents` (structure, indexes)
  - [ ] `wp_apollo_signature_requests` (structure, indexes)
  - [ ] `wp_apollo_feed_posts` (if exists)

- [ ] **Mapear REST Routes**
  - [ ] `apollo-events-manager`: Listar todos os endpoints REST
  - [ ] `apollo-social`: Listar todos os endpoints REST
  - [ ] Documentar permission callbacks
  - [ ] Documentar request/response schemas
  - [ ] Identificar duplicações ou conflitos

- [ ] **Mapear Options & Settings**
  - [ ] Listar todas as `get_option()` calls
  - [ ] Listar todas as `update_option()` calls
  - [ ] Documentar option keys e valores padrão
  - [ ] Identificar opções compartilhadas

- [ ] **Mapear Assets (CSS/JS)**
  - [ ] Listar todos os `wp_enqueue_style()` calls
  - [ ] Listar todos os `wp_enqueue_script()` calls
  - [ ] Documentar dependências
  - [ ] Identificar assets duplicados

**Deliverables**:
- `AUDIT-CPTs.md` - Complete CPT documentation
- `AUDIT-REST-ROUTES.md` - Complete REST API documentation
- `AUDIT-META-KEYS.md` - All meta keys inventory
- `AUDIT-OPTIONS.md` - All options inventory
- `AUDIT-ASSETS.md` - All assets inventory

**Estimated Time**: 4-6 hours

---

### 1.2. Exportar current DB meta keys and sample rows

**Status**: ⏳ Pending
**Owner**: TBD
**Priority**: P1

**Tasks**:
- [ ] **Export Meta Keys**
  - [ ] Query `wp_postmeta` for all Apollo-related meta keys
  - [ ] Query `wp_usermeta` for all Apollo-related meta keys
  - [ ] Export to CSV: `meta_key`, `count`, `sample_value`
  - [ ] Document data types and formats

- [ ] **Export Sample Rows**
  - [ ] Export 10 sample `event_listing` posts with all meta
  - [ ] Export 10 sample `event_dj` posts with all meta
  - [ ] Export 10 sample `event_local` posts with all meta
  - [ ] Export 10 sample `apollo_social_post` posts with all meta
  - [ ] Export 10 sample `user_page` posts with all meta
  - [ ] Export sample rows from custom tables

- [ ] **Create Migration Mapping**
  - [ ] Map old meta keys → new meta keys (if changing)
  - [ ] Map old option keys → new option keys
  - [ ] Document transformation rules
  - [ ] Create validation queries

**Deliverables**:
- `MIGRATION-META-KEYS.csv` - Meta keys inventory
- `MIGRATION-SAMPLE-DATA.sql` - Sample data export
- `MIGRATION-MAPPING.md` - Key mapping documentation

**Estimated Time**: 2-3 hours

---

## 2. Scaffold Apollo Core

### 2.1. Criar plugin apollo-core

**Status**: ⏳ Pending
**Owner**: TBD
**Priority**: P1

**Tasks**:
- [ ] **Create Plugin Structure**
  ```
  apollo-core/
  ├── apollo-core.php (main plugin file)
  ├── includes/
  │   ├── class-apollo-core.php
  │   ├── activation.php
  │   ├── deactivation.php
  │   ├── permissions.php
  │   └── migrations.php
  ├── modules/
  │   ├── events/
  │   ├── social/
  │   ├── moderation/
  │   └── loader.php
  ├── templates/
  │   └── canvas.php
  ├── assets/
  ├── config/
  └── README.md
  ```

- [ ] **Implement register_activation_hook**
  - [ ] Register roles: `apollo`, `cena-rio`, `dj`
  - [ ] Create `apollo_mod_settings` option (default values)
  - [ ] Run `dbDelta` for `wp_apollo_mod_log` table
  - [ ] Create default Canvas pages
  - [ ] Set rewrite rules
  - [ ] Idempotency checks (version tracking)

- [ ] **Implement register_deactivation_hook**
  - [ ] Clean up temporary data
  - [ ] Flush rewrite rules
  - [ ] Clear caches

- [ ] **Plugin Header**
  ```php
  /**
   * Plugin Name: Apollo Core
   * Plugin URI: https://apollo.rio.br
   * Description: Core plugin for Apollo ecosystem (Events + Social)
   * Version: 3.0.0
   * Author: Apollo Team
   * License: GPL v2 or later
   * Text Domain: apollo-core
   * Requires at least: 6.0
   * Requires PHP: 8.1
   */
  ```

**Deliverables**:
- `apollo-core/apollo-core.php` - Main plugin file
- `apollo-core/includes/activation.php` - Activation logic
- `apollo-core/includes/class-apollo-core.php` - Core class

**Estimated Time**: 4-6 hours

---

### 2.2. Implementar modules/ loader (PSR-4 style)

**Status**: ⏳ Pending
**Owner**: TBD
**Priority**: P1

**Tasks**:
- [ ] **Create Autoloader**
  - [ ] Implement PSR-4 autoloader
  - [ ] Register namespace: `Apollo\Core\`
  - [ ] Map namespaces to directories

- [ ] **Create Module Loader**
  ```php
  // apollo-core/modules/loader.php
  class Apollo_Module_Loader {
      public function load_modules() {
          // Auto-discover modules
          // Load module bootstrap files
          // Register module hooks
      }
  }
  ```

- [ ] **Module Structure**
  ```
  modules/
  ├── events/
  │   ├── bootstrap.php
  │   ├── includes/
  │   ├── templates/
  │   └── assets/
  ├── social/
  │   ├── bootstrap.php
  │   ├── includes/
  │   ├── templates/
  │   └── assets/
  └── moderation/
      ├── bootstrap.php
      ├── includes/
      └── templates/
  ```

- [ ] **Module Interface**
  ```php
  interface Apollo_Module {
      public function register();
      public function get_version();
      public function get_dependencies();
  }
  ```

**Deliverables**:
- `apollo-core/modules/loader.php` - Module loader
- `apollo-core/includes/autoloader.php` - PSR-4 autoloader
- Module bootstrap files

**Estimated Time**: 3-4 hours

---

## 3. Move & Adapt Code

### 3.1. Mover CPT registration

**Status**: ⏳ Pending
**Owner**: TBD
**Priority**: P1

**Tasks**:
- [ ] **Move Events CPTs**
  - [ ] Move `event_listing` registration → `modules/events/includes/post-types.php`
  - [ ] Move `event_dj` registration → `modules/events/includes/post-types.php`
  - [ ] Move `event_local` registration → `modules/events/includes/post-types.php`
  - [ ] Update namespace and class names
  - [ ] Test CPT registration

- [ ] **Move Social CPTs**
  - [ ] Move `apollo_social_post` registration → `modules/social/includes/post-types.php`
  - [ ] Move `user_page` registration → `modules/social/includes/post-types.php`
  - [ ] Update namespace and class names
  - [ ] Test CPT registration

- [ ] **Update References**
  - [ ] Find all `post_type_exists('event_listing')` calls
  - [ ] Find all `post_type_exists('apollo_social_post')` calls
  - [ ] Update to use new module structure
  - [ ] Update template includes

**Deliverables**:
- `modules/events/includes/post-types.php`
- `modules/social/includes/post-types.php`
- Updated references throughout codebase

**Estimated Time**: 4-5 hours

---

### 3.2. Refactor REST routes

**Status**: ⏳ Pending
**Owner**: TBD
**Priority**: P1

**Tasks**:
- [ ] **Centralize REST Routes**
  - [ ] Move all routes to `wp-json/apollo/v1/*` namespace
  - [ ] Create `apollo-core/includes/rest-api.php`
  - [ ] Register all routes in one place
  - [ ] Remove duplicate route registrations

- [ ] **Centralize Permission Checks**
  - [ ] Create `apollo-core/includes/permissions.php`
  - [ ] Implement permission helper functions:
    ```php
    function apollo_can_approve_events($user_id = null);
    function apollo_can_access_cena_rio($user_id = null);
    function apollo_can_sign_documents($user_id = null);
    function apollo_can_manage_lists($user_id = null);
    ```
  - [ ] Replace inline permission checks with helpers
  - [ ] Add permission caching (transients)

- [ ] **Update Endpoint Classes**
  - [ ] Update namespace: `Apollo\Core\API\Endpoints\`
  - [ ] Use centralized permission helpers
  - [ ] Standardize response format
  - [ ] Add rate limiting hooks

**Deliverables**:
- `apollo-core/includes/rest-api.php` - Centralized routes
- `apollo-core/includes/permissions.php` - Permission helpers
- Updated endpoint classes

**Estimated Time**: 6-8 hours

---

### 3.3. Replace template includes with Canvas loader

**Status**: ⏳ Pending
**Owner**: TBD
**Priority**: P1

**Tasks**:
- [ ] **Create Canvas Template Loader**
  - [ ] Create `apollo-core/templates/canvas.php`
  - [ ] Implement template hierarchy:
    ```
    templates/canvas/{route}.php
    templates/canvas/{module}/{route}.php
    templates/canvas/default.php
    ```
  - [ ] Add template caching (if needed)

- [ ] **Update Template Includes**
  - [ ] Find all direct `include`/`require` template calls
  - [ ] Replace with Canvas loader:
    ```php
    apollo_canvas_template('feed/feed.php', $data);
    ```
  - [ ] Update all route handlers
  - [ ] Test template rendering

- [ ] **Template Data Injection**
  - [ ] Standardize template data structure
  - [ ] Add filters for template data
  - [ ] Ensure all templates receive consistent data

**Deliverables**:
- `apollo-core/templates/canvas.php` - Canvas loader
- Updated template includes
- Template hierarchy documentation

**Estimated Time**: 4-5 hours

---

## 4. Migration & Compatibility

### 4.1. Add migration script

**Status**: ⏳ Pending
**Owner**: TBD
**Priority**: P1

**Tasks**:
- [ ] **Create Migration Script**
  - [ ] Create `apollo-core/includes/migrations/migrate-to-core.php`
  - [ ] Map old options → new options:
    ```php
    $mapping = [
        'apollo_events_version' => 'apollo_core_version',
        'apollo_social_version' => 'apollo_core_version',
        // ... more mappings
    ];
    ```
  - [ ] Map old meta keys → new meta keys (if changed)
  - [ ] Create backups before migration:
    ```php
    // Backup options
    $backup = get_option('apollo_migration_backup_' . date('Y-m-d'));
    update_option('apollo_migration_backup_' . date('Y-m-d'), $all_options);
    ```

- [ ] **Migration Safety**
  - [ ] Add dry-run mode (preview changes)
  - [ ] Add rollback functionality
  - [ ] Log all changes to `wp_apollo_mod_log`
  - [ ] Validate data integrity after migration
  - [ ] Create migration report

- [ ] **Migration UI**
  - [ ] Add admin page: "Apollo Migration"
  - [ ] Show migration status
  - [ ] Allow manual trigger
  - [ ] Display migration log

**Deliverables**:
- `apollo-core/includes/migrations/migrate-to-core.php`
- Migration UI admin page
- Migration runbook documentation

**Estimated Time**: 6-8 hours

---

### 4.2. Add feature toggles

**Status**: ⏳ Pending
**Owner**: TBD
**Priority**: P1

**Tasks**:
- [ ] **Create Feature Toggle System**
  - [ ] Create `apollo-core/includes/feature-toggles.php`
  - [ ] Implement toggle functions:
    ```php
    function apollo_is_feature_enabled($feature);
    function apollo_enable_feature($feature);
    function apollo_disable_feature($feature);
    ```
  - [ ] Store toggles in `apollo_mod_settings` option

- [ ] **Add Compatibility Mode**
  - [ ] Toggle: `use_legacy_plugins` (default: true)
  - [ ] When enabled, old plugins continue working
  - [ ] When disabled, only Apollo Core active
  - [ ] Show warning if both active simultaneously

- [ ] **Gradual Migration**
  - [ ] Toggle: `migrate_events_module` (default: false)
  - [ ] Toggle: `migrate_social_module` (default: false)
  - [ ] Allow enabling modules one at a time
  - [ ] Test each module independently

**Deliverables**:
- `apollo-core/includes/feature-toggles.php`
- Admin UI for toggles
- Compatibility mode documentation

**Estimated Time**: 3-4 hours

---

## 5. Admin & MOD

### 5.1. Implement modules/moderation

**Status**: ⏳ Pending
**Owner**: TBD
**Priority**: P1

**Tasks**:
- [ ] **Create Moderation Module**
  ```
  modules/moderation/
  ├── bootstrap.php
  ├── includes/
  │   ├── class-moderation-queue.php
  │   ├── class-moderation-log.php
  │   └── class-moderation-settings.php
  ├── templates/
  │   ├── admin-tab1.php (admin-only)
  │   └── mod-view.php (apollo-read)
  └── assets/
  ```

- [ ] **Moderation Settings**
  - [ ] Use `apollo_mod_settings` option
  - [ ] Settings structure:
    ```php
    [
        'auto_approve_events' => false,
        'auto_approve_posts' => false,
        'require_moderation' => ['event_listing', 'apollo_social_post'],
        'mod_roles' => ['apollo', 'editor', 'administrator'],
    ]
    ```

- [ ] **Admin Tab1 (Admin Only)**
  - [ ] Access: `current_user_can('manage_options')`
  - [ ] Features:
    - [ ] View all moderation queues
    - [ ] Configure moderation settings
    - [ ] View audit logs
    - [ ] Manage mod roles
  - [ ] Template: `templates/admin-tab1.php`

- [ ] **Mod View (apollo-read)**
  - [ ] Access: `current_user_can('apollo_read')` or `apollo` role
  - [ ] Features:
    - [ ] View assigned moderation queue
    - [ ] Approve/reject items
    - [ ] Add moderation notes
  - [ ] Template: `templates/mod-view.php`

**Deliverables**:
- `modules/moderation/` - Complete module
- Admin UI for moderation
- Mod UI for reviewers

**Estimated Time**: 8-10 hours

---

### 5.2. Add audit logging

**Status**: ⏳ Pending
**Owner**: TBD
**Priority**: P1

**Tasks**:
- [ ] **Create Audit Log Table**
  - [ ] Table: `wp_apollo_mod_log`
  - [ ] Structure:
    ```sql
    id BIGINT PRIMARY KEY AUTO_INCREMENT
    action VARCHAR(50) -- 'publish', 'approve', 'suspend', etc.
    content_type VARCHAR(50) -- 'event_listing', 'apollo_social_post', etc.
    content_id BIGINT
    user_id BIGINT
    old_status VARCHAR(20)
    new_status VARCHAR(20)
    notes TEXT
    created_at DATETIME
    ```
  - [ ] Indexes: `content_type`, `content_id`, `user_id`, `created_at`

- [ ] **Implement Logging Functions**
  ```php
  function apollo_log_action($action, $content_type, $content_id, $data = []);
  function apollo_get_audit_log($filters = []);
  ```

- [ ] **Add Logging Calls**
  - [ ] Log on `publish` action
  - [ ] Log on `approve` action
  - [ ] Log on `suspend` action
  - [ ] Log on `delete` action
  - [ ] Log on role changes
  - [ ] Log on permission changes

- [ ] **Audit Log UI**
  - [ ] Admin page to view logs
  - [ ] Filter by action, content type, user
  - [ ] Export logs to CSV
  - [ ] Pagination for large logs

**Deliverables**:
- `wp_apollo_mod_log` table
- Logging functions
- Audit log UI

**Estimated Time**: 4-5 hours

---

## 6. Testing & CI

### 6.1. Add PHPUnit tests

**Status**: ⏳ Pending
**Owner**: TBD
**Priority**: P1

**Tasks**:
- [ ] **Setup PHPUnit**
  - [ ] Create `phpunit.xml` configuration
  - [ ] Create `tests/bootstrap.php`
  - [ ] Setup WordPress test environment
  - [ ] Create test database

- [ ] **Activation Tests**
  - [ ] Test role creation
  - [ ] Test option creation
  - [ ] Test table creation (dbDelta)
  - [ ] Test idempotency (multiple activations)

- [ ] **REST Endpoint Tests**
  - [ ] Test all `/wp-json/apollo/v1/*` endpoints
  - [ ] Test permission checks
  - [ ] Test request validation
  - [ ] Test response format
  - [ ] Test error handling

- [ ] **Permission Tests**
  - [ ] Test `apollo_can_approve_events()`
  - [ ] Test `apollo_can_access_cena_rio()`
  - [ ] Test `apollo_can_sign_documents()`
  - [ ] Test role-based permissions
  - [ ] Test ownership checks

- [ ] **Migration Tests**
  - [ ] Test option migration
  - [ ] Test meta key migration
  - [ ] Test rollback functionality
  - [ ] Test data integrity

**Deliverables**:
- `tests/` directory with all tests
- `phpunit.xml` configuration
- Test coverage report

**Estimated Time**: 8-10 hours

---

### 6.2. Update GitHub Actions

**Status**: ⏳ Pending
**Owner**: TBD
**Priority**: P1

**Tasks**:
- [ ] **Create CI Workflow**
  - [ ] Create `.github/workflows/ci.yml`
  - [ ] Run PHPUnit tests
  - [ ] Run PHPCS (coding standards)
  - [ ] Run PHPStan (static analysis)
  - [ ] Run on PR and push to main

- [ ] **Create Build Workflow**
  - [ ] Create `.github/workflows/build.yml`
  - [ ] Build plugin zip on tag release
  - [ ] Upload artifact
  - [ ] Create GitHub release

- [ ] **Add Quality Checks**
  - [ ] Minimum test coverage (80%)
  - [ ] No PHP errors/warnings
  - [ ] No security vulnerabilities
  - [ ] Code style compliance

**Deliverables**:
- `.github/workflows/ci.yml`
- `.github/workflows/build.yml`
- CI/CD documentation

**Estimated Time**: 3-4 hours

---

## 7. Docs & Rollout

### 7.1. Update README

**Status**: ⏳ Pending
**Owner**: TBD
**Priority**: P1

**Tasks**:
- [ ] **Update Main README**
  - [ ] Document Apollo Core architecture
  - [ ] Document module system
  - [ ] Document migration path
  - [ ] Add installation instructions
  - [ ] Add configuration guide
  - [ ] Add troubleshooting section

- [ ] **Create DEVELOPMENT.md**
  - [ ] Local setup instructions
  - [ ] Development workflow
  - [ ] Module development guide
  - [ ] Testing guide
  - [ ] Debugging guide
  - [ ] Contribution guidelines

- [ ] **Create Migration Runbook**
  - [ ] Pre-migration checklist
  - [ ] Step-by-step migration guide
  - [ ] Rollback procedures
  - [ ] Post-migration validation
  - [ ] Troubleshooting common issues

**Deliverables**:
- `README.md` - Updated
- `DEVELOPMENT.md` - New
- `MIGRATION-RUNBOOK.md` - New

**Estimated Time**: 4-5 hours

---

## 📊 PROGRESS TRACKING

### Overall Progress: 0% (0/47 tasks completed)

**By Phase**:
- [ ] Audit & Prep: 0% (0/2 tasks)
- [ ] Scaffold Apollo Core: 0% (0/2 tasks)
- [ ] Move & Adapt Code: 0% (0/3 tasks)
- [ ] Migration & Compatibility: 0% (0/2 tasks)
- [ ] Admin & MOD: 0% (0/2 tasks)
- [ ] Testing & CI: 0% (0/2 tasks)
- [ ] Docs & Rollout: 0% (0/1 tasks)

---

## 🎯 ESTIMATED TIMELINE

**Total Estimated Time**: 60-75 hours

**Breakdown**:
- Audit & Prep: 6-9 hours
- Scaffold Apollo Core: 7-10 hours
- Move & Adapt Code: 14-18 hours
- Migration & Compatibility: 9-12 hours
- Admin & MOD: 12-15 hours
- Testing & CI: 11-14 hours
- Docs & Rollout: 4-5 hours

**Recommended Sprint Plan**:
- **Sprint 1** (Week 1): Audit & Prep + Scaffold Apollo Core
- **Sprint 2** (Week 2): Move & Adapt Code
- **Sprint 3** (Week 3): Migration & Compatibility + Admin & MOD
- **Sprint 4** (Week 4): Testing & CI + Docs & Rollout

---

## 📝 NOTES

- This migration should be done incrementally
- Keep old plugins working during migration
- Test thoroughly before each phase
- Document all changes
- Create backups before migration
- Have rollback plan ready

---

**Last Updated**: 24/11/2025
**Next Review**: After Phase 1 completion

