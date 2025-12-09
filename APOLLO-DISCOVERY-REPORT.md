# Apollo Plugins Discovery Report

**Generated:** 2024-12-08  
**Scope:** apollo-core, apollo-social, apollo-events-manager, apollo-rio  
**Exclusions:** chat/messages, notifications, moderation, coauthors, analytics, mail/email, SEO, PWA

---

## 1. Admin Menu Structure

### Apollo Core (position 25)
| Menu Slug | Parent | Capability | Callback |
|-----------|--------|-----------|----------|
| `apollo-core-hub` | — | `manage_options` | `apollo_core_render_hub_page` |
| `apollo-core-cenario` | apollo-core-hub | `manage_options` | `apollo_core_render_cenario_page` |
| `apollo-core-design` | apollo-core-hub | `manage_options` | `apollo_core_render_design_page` |
| `apollo-moderation` | — | `view_moderation_queue` | `apollo_render_moderation_page` |

### Apollo Social (position 27)
| Menu Slug | Parent | Capability | Callback |
|-----------|--------|-----------|----------|
| `apollo-social-hub` | — | `manage_options` | `AdminHubPage::renderHubPage` |
| `apollo-social-shortcodes` | apollo-social-hub | `manage_options` | `AdminHubPage::renderShortcodesPage` |
| `apollo-social-security` | apollo-social-hub | `manage_options` | `AdminHubPage::renderSecurityPage` |

### Apollo Events Manager (position 26)
| Menu Slug | Parent | Capability | Callback |
|-----------|--------|-----------|----------|
| `apollo-events-hub` | — | `manage_options` | `apollo_events_render_hub_page` |
| `apollo-events-shortcodes` | apollo-events-hub | `manage_options` | `apollo_events_render_shortcodes_tab` |
| `apollo-events-metakeys` | apollo-events-hub | `manage_options` | `apollo_events_render_metakeys_tab` |
| `apollo-events-roles` | apollo-events-hub | `manage_options` | `apollo_events_render_roles_tab` |
| `apollo-events-settings` | apollo-events-hub | `manage_options` | `apollo_events_render_settings_tab` |

### Apollo RIO
- No main admin menus (only vendor stubs)

---

## 2. Module System

### Module Loader (`apollo-core`)
**Location:** `includes/class-module-loader.php`

**Settings Key:** `apollo_mod_settings` with `enabled_modules` array  
**Default Modules:** `['events', 'social', 'moderation']`

**Available Modules:** (in `modules/` directory)
- `events/bootstrap.php` → `Apollo_Events_Module`
- `moderation/bootstrap.php`
- `social/bootstrap.php`

### Feature Toggles
| Option Key | Purpose |
|------------|---------|
| `apollo_mod_settings['enabled_modules']` | Module enable/disable |
| `apollo_seopress_initialized` | SEOPress first-run flag |
| `apollo_jwt_secret_key` | Secure JWT fallback secret |
| `apollo_core_migration_version` | DB migration version tracking |

---

## 3. REST API Routes

### Apollo Core (`apollo/v1`)
| File | Routes |
|------|--------|
| `class-cena-rio-submissions.php` | 4 routes (submissions CRUD) |
| `class-moderation-queue-unified.php` | 2 routes (queue management) |
| `class-rest-bootstrap.php` | 1 base route + hook |
| `rest-membership.php` | 9 routes (membership management) |

### Apollo Social (`apollo-social/v1`)
| File | Routes |
|------|--------|
| `RestRoutes.php` | 13 routes (social features) |
| `SignatureEndpoints.php` | 8+ routes (document library, signatures) |

### Apollo Events Manager (`aprio/v1`)
| File | Routes |
|------|--------|
| `admin-dashboard.php` | 6 routes (dashboard stats) |
| `class-bookmarks.php` | 2 routes (bookmarks) |
| `aprio-rest-authentication.php` | 2 routes (JWT auth) |
| `class-rest-api.php` | 5+ routes (events CRUD) |
| `aprio-rest-events-controller.php` | 2+ routes (events query) |

---

## 4. Shortcodes

### Apollo Events Manager
| Shortcode | Handler | Purpose |
|-----------|---------|---------|
| `[events]` | `apollo_events_shortcode_handler` | Events listing |
| `[apollo_events]` | `apollo_events_shortcode_handler` | Alias |
| `[apollo_event]` | `AEM::apollo_event_shortcode` | Single event display |
| `[apollo_event_user_overview]` | `AEM::apollo_event_user_overview_shortcode` | User event overview |
| `[apollo_dj_profile]` | `AEM::apollo_dj_profile_shortcode` | DJ profile |
| `[apollo_user_dashboard]` | `AEM::apollo_user_dashboard_shortcode` | User dashboard |
| `[apollo_cena_rio]` | `AEM::apollo_cena_rio_shortcode` | CENA-RIO display |
| `[apollo_event_submit]` | `AEM::render_submit_form` | Event submission |
| `[submit_event_form]` | Multiple handlers | Legacy alias |
| `[apollo_eventos]` | `AEM::render_submit_form` | PT alias |
| `[apollo_bookmarks]` | `Bookmarks::bookmarks_shortcode` | Bookmark listing |
| `[apollo_public_event_form]` | `apollo_render_public_event_form` | Public submission |
| `[apollo_register]` | `apollo_register_shortcode` | Registration |
| `[apollo_login]` | `apollo_login_shortcode` | Login form |
| `[my_apollo_dashboard]` | `apollo_my_apollo_dashboard_shortcode` | My Apollo |
| `[event_dashboard]` | `Apollo_Events_Shortcodes::event_dashboard` | Event dashboard |

---

## 5. User Roles & Capabilities

### Custom Role: `apollo_moderator`
**Location:** `apollo-core/includes/roles.php`, `modules/moderation/includes/class-roles.php`

| Capability | Description |
|------------|-------------|
| `moderate_apollo_content` | Moderate user-submitted content |
| `edit_apollo_users` | Edit Apollo user profiles |
| `view_moderation_queue` | View moderation queue |
| `send_user_notifications` | Send notifications to users |

### Administrator Additions
| Capability | Description |
|------------|-------------|
| `manage_apollo_mod_settings` | Manage moderation settings |
| `suspend_users` | Suspend user accounts |
| `block_users` | Block user accounts |
| All moderator caps | Inherits all moderator capabilities |

---

## 6. Known Issues - xdebug_break() Calls

⚠️ **SECURITY WARNING:** Debug breakpoints present in production code

### apollo-social
| File | Lines |
|------|-------|
| `Modules/Signatures/Controllers/SignaturesRestController.php` | 83-84, 171-172 |
| `Modules/Builder/Http/BuilderRestController.php` | 64-65, 272-273 |

### apollo-events-manager
| File | Lines |
|------|-------|
| `includes/class-rest-api.php` | 46-47, 142-143 |
| `includes/ajax-handlers.php` | 30-31 |
| `includes/class-bookmarks.php` | 310-311 |
| `apollo-events-manager.php` | 2447-2448, 2545-2546, 2598-2599+ |

**Total:** 20+ xdebug_break() calls to remove

---

## 7. Security Findings Summary

### ✅ Previously Fixed
- JWT secret now uses `APOLLO_JWT_SECRET` from wp-config or secure option
- SignatureEndpoints permission callbacks enhanced with nonce + capability checks
- Event duplicate action implemented with proper authorship

### ⚠️ Outstanding Issues
1. **xdebug_break() calls** - Remove all debug breakpoints
2. **Review all REST permission_callbacks** - Ensure proper auth on all routes
3. **Audit shortcode output escaping** - Verify esc_html/esc_attr usage

---

## 8. Next Steps

1. Remove all xdebug_break() calls (20+ instances)
2. Audit REST API permission callbacks for proper capability checks
3. Review shortcode output for XSS vulnerabilities
4. Document all post meta keys used
5. Create integration test suite for critical paths
