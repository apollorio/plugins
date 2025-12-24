# Apollo Plugins Ecosystem Integration Plan

## Overview
Migrate all code from "old-plugins" into 4 consolidated plugins to avoid WP core corruption and ensure smooth activation.

## Current Structure (Fixed)
- Moved nested `plugins/plugins/` content to `plugins/`.
- Created 4 plugin directories: `apollo-core`, `apollo-social`, `apollo-rio`, `apollo-events-manager`.

## Plugin Roles
- **apollo-core**: Bridge/integration, centralized taxonomies/post types, core helpers.
- **apollo-social**: Social features, user pages, groups, onboarding.
- **apollo-rio**: Page models, page builders, creative tools.
- **apollo-events-manager**: Events core, management, templates.

## Migration Plan

### Phase 1: Preparation
1. Backup entire `wp-content/plugins/` and database.
2. Enable safe mode: Add `define('APOLLO_SAFE_MODE', true);` to `wp-config.php`.
3. Deactivate all plugins via WP Admin or WP-CLI.

### Phase 2: Core Integration (apollo-core)
1. Move core helpers, bridges, and integration code from old-plugins to `apollo-core/includes/`.
2. Ensure centralized registry in `apollo-core.php` handles all taxonomies/post types.
3. Add dependency checks and version guards.

### Phase 3: Social Features (apollo-social)
1. Migrate social feed, groups, user pages from old-plugins.
2. Update to use `apollo_core_registry` filter for any new taxonomies.
3. Ensure AJAX handlers have proper nonces and capabilities.

### Phase 4: Rio (Page Builders/Models)
1. Integrate page builders, creative studio, canvas tools.
2. Move templates and assets to `apollo-rio/`.
3. Add hooks for integration with other plugins.

### Phase 5: Events Manager
1. Consolidate events core, templates, REST APIs.
2. Use centralized registration for event taxonomies.
3. Migrate any event-related modules.

### Phase 6: Testing & Activation
1. Activate in order: `apollo-core` → `apollo-social` → `apollo-rio` → `apollo-events-manager`.
2. Test each plugin individually.
3. Run PHPCS: `composer run lint:phpcs`.
4. Disable safe mode and test full suite.

### Phase 7: Final Cleanup
1. Remove old-plugins after successful migration.
2. Update documentation.
3. Commit to GitHub.

## Key Rules
- No direct `register_taxonomy()` outside `apollo-core.php`.
- All plugins require `apollo-core`.
- Use filters for extensibility.
- Test after each phase to avoid critical errors.
