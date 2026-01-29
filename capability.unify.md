1.  Unify on APOLLO-CORE plugin, to create entire system of user roles and control, but basically we never create one new user roles, your task now is: MAKE ALL CONTROL AND EDIT of USER ROLES on wp plugin apollo-core that feed all other apollo plugins and adjust all registered user roles to the original standard wordpress as below instructed:

2)  All user roles to the standard wordpress, where slug backend non visible its kept the original wordpress, but change the 'label' which prints frontend
    As example: slug: ‘subscriber’ that printing label must change from "Subscriber" to "Clubber", as well to other slug: ‘author’.
    Make sure user_roles wp standard of ‘contributor’ with label to:

a. Standard for admin user role:
(slug: ‘administrator’ super adm) and (slug: ‘administrator’) to (label: 'apollo')

b. Standard for `MOD` rinted of backend working `editor` user role:
"(slug: ‘editor’) to (label: 'MOD')" extra of (unified removing user role and pluged to all place registered of user roles:
`apollo_moderator` `moderator` `mod` only.

a. Standard for cult::rio printed of backend working author user role:
"(slug: ‘author’) to (label: 'cult::rio')" extra of (unified removing user role and pluged to all place registered of user roles:
`friends` `friendz` only.

"(slug: ‘contributor’) to (label: 'cena::rio')" extra of (unified removing user role and pluged to all place registered of user roles:
`cena_role` `cenario` `cena-rio` `industry` and related to mod roles found.

(slug: ‘subscriber’) to (label: 'clubber') extra of (unified removing user role and pluged to all place registered of user roles: `apollo_member`, `clubber`, `apollo_member` and related to mod roles found.

Table of Capabilities
Backend (internal slugs) (slug: ‘administrator’) (slug: ‘administrator’) (slug: ‘editor’) (slug: ‘author’) (slug: ‘contributor’) (slug: ‘subscriber’)
Front end (print as) (label: 'apollo') (label: 'apollo') (label: 'MOD') (label: 'cult::rio') (label: 'cena::rio') (label: 'clubber')
Merge inside self all user_roles existant of: `cena-rio` => avoid creating new user role in 3 locations and connect all created role connections here; `apollo_member` => avoid creating new user role and connect all created role connections here;
create_sites yes x x x x x
delete_sites yes x x x x x
manage_network yes x x x x x
manage_sites yes x x x x x
manage_network_users yes x x x x x
manage_network_plugins yes x x x x x
manage_network_themes yes x x x x x
manage_network_options yes x x x x x
upload_plugins yes x x x x x
upload_themes yes x x x x x
upload_network yes x x x x x
upgrade_network yes x x x x x
setup_network yes x x x x x
activate_plugins yes "yes (single site or
enabled by
network setting)" x x x x
create_users yes yes (single site) x x x x
delete_plugins yes yes (single site) x x x x
delete_themes yes yes (single site) x x x x
delete_users yes yes (single site) x x x x
edit_files yes yes (single site) x x x x
edit_plugins yes yes (single site) x x x x
edit_theme_options yes yes x x x x
edit_themes yes yes (single site) x x x x
edit_users yes yes (single site) x x x x
export yes yes x x x x
import yes yes x x x x
install_plugins yes yes (single site) x x x x
install_themes yes yes (single site) x x x x
list_users yes yes x x x x
manage_options yes yes x x x x
promote_users yes yes x x x x
remove_users yes yes x x x x
switch_themes yes yes x x x x
update_core yes yes (single site) x x x x
update_plugins yes yes (single site) x x x x
update_themes yes yes (single site) x x x x
edit_dashboard yes yes x x x x
customize yes yes x x x x
delete_site yes yes x x x x
moderate_comments yes yes yes x x x
manage_categories yes yes yes x x x
manage_links yes yes yes x x x
edit_others_posts yes yes yes x x x
edit_pages yes yes yes x x x
edit_others_pages yes yes yes x x x
edit_published_pages yes yes yes x x x
publish_pages yes yes yes x x x
delete_pages yes yes yes x x x
delete_others_pages yes yes yes x x x
delete_published_pages yes yes yes x x x
delete_others_pos yes yes yes x x x
delete_private_posts yes yes yes x x x
edit_private_posts yes yes yes x x x
read_private_posts yes yes yes x x x
delete_private_pages yes yes yes x x x
edit_private_pages yes yes yes x x x
read_private_pages yes yes yes x x x
unfiltered_html yes yes (single site) yes (single site) x x x
unfiltered_html yes yes yes x x x
edit_published_posts yes yes yes yes x x
upload_files yes yes yes yes x x
publish_posts yes yes yes yes x x
delete_published_posts yes yes yes yes x x
edit_posts yes yes yes yes yes x
delete_posts yes yes yes yes yes x
read yes yes yes yes yes yes

---

## CRITICAL PRIORITY MAP & ACTION PLAN

### **🔴 PRIORITY 1 - CRITICAL (Week 1-2)**

**Status:** IMMEDIATE ACTION REQUIRED

#### **1.1 Unify User Roles in Apollo Core**

**Action:** Create centralized role management system
**File:** `apollo-core/includes/class-apollo-roles-manager.php`
**Impact:** Eliminates duplicate roles across all plugins
**Risk if delayed:** Role conflicts, permission inconsistencies

**Implementation:**

```php
class Apollo_Roles_Manager {
    private static $role_mapping = [
        'administrator' => 'apollo',
        'editor' => 'MOD',
        'author' => 'cult::rio',
        'contributor' => 'cena::rio',
        'subscriber' => 'clubber'
    ];

    private static $deprecated_roles = [
        'apollo_moderator', 'moderator', 'mod', // → editor
        'friends', 'friendz', // → author
        'cena_role', 'cenario', 'cena-rio', 'industry', // → contributor
        'apollo_member', 'clubber' // → subscriber
    ];
}
```

#### **1.2 Remove Duplicate Role Registrations**

**Files to fix:**

- `apollo-events-manager/apollo-events-manager.php`
- `apollo-social/includes/roles.php`
- `apollo-memberships/includes/class-membership-roles.php`

**Search patterns:**

- `add_role('apollo_moderator'`
- `add_role('cena_role'`
- `add_role('apollo_member'`

### **🟠 PRIORITY 2 - HIGH (Week 3-4)**

**Status:** Required for form access system

#### **2.1 Implement Level-Based Form Access**

**Dependency:** Priority 1 must be complete
**Reference:** See capability.md Implementation Plan Phase 2

**Form Access Matrix:**
| Capability | clubber | cena::rio | cult::rio | MOD | apollo |
|------------|---------|-----------|-----------|-----|--------|
| Submit basic event | ✓ | ✓ | ✓ | ✓ | ✓ |
| Add description | ✗ | ✓ | ✓ | ✓ | ✓ |
| Select DJs | ✗ | ✗ | ✓ | ✓ | ✓ |
| Upload images | ✗ | ✗ | ✓ | ✓ | ✓ |
| Publish directly | ✗ | ✗ | ✓ | ✓ | ✓ |
| Add coupons | ✗ | ✗ | ✗ | ✗ | ✓ |
| Moderate events | ✗ | ✗ | ✗ | ✓ | ✓ |

#### **2.2 Update REST API Permissions**

**Files:**

- `apollo-events-manager/src/RestAPI/class-events-controller.php`
- `apollo-social/src/RestAPI/class-posts-controller.php`

### **🟡 PRIORITY 3 - MEDIUM (Week 5-6)**

**Status:** Quality of life improvements

#### **3.1 Frontend Label Translation**

**Action:** Replace role names displayed to users
**Files:**

- All template files with `$user->roles` display
- User profile pages
- Dashboard widgets

#### **3.2 Capability Cleanup**

**Action:** Remove unused custom capabilities
**Target:** Custom event capabilities that duplicate WordPress core

### **🟢 PRIORITY 4 - LOW (Week 7-8)**

**Status:** Enhancement & polish

#### **4.1 Analytics & Monitoring**

**Action:** Track role usage and form completions
**4.2 Documentation**
**Action:** Update all plugin READMEs with new role structure

---

## TABLE OF APOLLO PLUGINS CUSTOM CAPABILITIES

### **Apollo Events Manager - Event Capabilities**

| Capability                    | administrator | editor (MOD) | author (cult::rio) | contributor (cena::rio) | subscriber (clubber) |
| ----------------------------- | ------------- | ------------ | ------------------ | ----------------------- | -------------------- |
| **Event Listings**            |               |              |                    |                         |                      |
| `edit_event_listing`          | ✓             | ✓            | ✓                  | ✓                       | ✗                    |
| `edit_event_listings`         | ✓             | ✓            | ✓                  | ✓                       | ✗                    |
| `edit_others_event_listings`  | ✓             | ✓            | ✗                  | ✗                       | ✗                    |
| `publish_event_listings`      | ✓             | ✓            | ✓                  | ✗                       | ✗                    |
| `read_event_listing`          | ✓             | ✓            | ✓                  | ✓                       | ✓                    |
| `read_private_event_listings` | ✓             | ✓            | ✗                  | ✗                       | ✗                    |
| `delete_event_listing`        | ✓             | ✓            | ✓                  | ✓                       | ✗                    |
| **Event DJs**                 |               |              |                    |                         |                      |
| `edit_event_dj`               | ✓             | ✓            | ✓                  | ✗                       | ✗                    |
| `edit_event_djs`              | ✓             | ✓            | ✓                  | ✗                       | ✗                    |
| `edit_others_event_djs`       | ✓             | ✓            | ✗                  | ✗                       | ✗                    |
| `publish_event_djs`           | ✓             | ✓            | ✓                  | ✗                       | ✗                    |
| **Event Locals**              |               |              |                    |                         |                      |
| `edit_event_local`            | ✓             | ✓            | ✓                  | ✗                       | ✗                    |
| `edit_event_locals`           | ✓             | ✓            | ✓                  | ✗                       | ✗                    |
| `publish_event_locals`        | ✓             | ✓            | ✓                  | ✗                       | ✗                    |
| **Event Moderation**          |               |              |                    |                         |                      |
| `moderate_events`             | ✓             | ✓            | ✗                  | ✗                       | ✗                    |
| `approve_pending_events`      | ✓             | ✓            | ✗                  | ✗                       | ✗                    |
| `view_event_analytics`        | ✓             | ✓            | ✗                  | ✗                       | ✗                    |

### **Apollo Social - Activity Capabilities**

| Capability                | administrator | editor (MOD) | author (cult::rio) | contributor (cena::rio) | subscriber (clubber) |
| ------------------------- | ------------- | ------------ | ------------------ | ----------------------- | -------------------- |
| **Activity Posts**        |               |              |                    |                         |                      |
| `publish_activity`        | ✓             | ✓            | ✓                  | ✓                       | ✓                    |
| `edit_own_activity`       | ✓             | ✓            | ✓                  | ✓                       | ✓                    |
| `edit_others_activity`    | ✓             | ✓            | ✗                  | ✗                       | ✗                    |
| `delete_own_activity`     | ✓             | ✓            | ✓                  | ✓                       | ✓                    |
| `delete_others_activity`  | ✓             | ✓            | ✗                  | ✗                       | ✗                    |
| **Comments & Moderation** |               |              |                    |                         |                      |
| `moderate_activity`       | ✓             | ✓            | ✗                  | ✗                       | ✗                    |
| `report_content`          | ✓             | ✓            | ✓                  | ✓                       | ✓                    |
| `view_reported_content`   | ✓             | ✓            | ✗                  | ✗                       | ✗                    |
| **Social Features**       |               |              |                    |                         |                      |
| `follow_users`            | ✓             | ✓            | ✓                  | ✓                       | ✓                    |
| `send_messages`           | ✓             | ✓            | ✓                  | ✓                       | ✓                    |
| `create_groups`           | ✓             | ✓            | ✓                  | ✗                       | ✗                    |
| `moderate_groups`         | ✓             | ✓            | ✗                  | ✗                       | ✗                    |

### **Apollo Memberships - Membership Capabilities**

| Capability                  | administrator | editor (MOD) | author (cult::rio) | contributor (cena::rio) | subscriber (clubber) |
| --------------------------- | ------------- | ------------ | ------------------ | ----------------------- | -------------------- |
| **Membership Management**   |               |              |                    |                         |                      |
| `view_memberships`          | ✓             | ✓            | ✓                  | ✓                       | ✓                    |
| `edit_own_membership`       | ✓             | ✓            | ✓                  | ✓                       | ✓                    |
| `edit_others_memberships`   | ✓             | ✓            | ✗                  | ✗                       | ✗                    |
| `assign_memberships`        | ✓             | ✓            | ✗                  | ✗                       | ✗                    |
| **Premium Features**        |               |              |                    |                         |                      |
| `access_premium_content`    | ✓             | ✓            | ✓                  | membership-based        | ✗                    |
| `download_exclusive_tracks` | ✓             | ✓            | ✓                  | membership-based        | ✗                    |
| `early_ticket_access`       | ✓             | ✓            | ✓                  | membership-based        | ✗                    |

### **Apollo Core - System Capabilities**

| Capability            | administrator | editor (MOD) | author (cult::rio) | contributor (cena::rio) | subscriber (clubber) |
| --------------------- | ------------- | ------------ | ------------------ | ----------------------- | -------------------- |
| **API Access**        |               |              |                    |                         |                      |
| `use_apollo_api`      | ✓             | ✓            | ✓                  | ✓                       | ✓                    |
| `generate_api_keys`   | ✓             | ✓            | ✓                  | ✗                       | ✗                    |
| `view_api_logs`       | ✓             | ✓            | ✗                  | ✗                       | ✗                    |
| **Analytics**         |               |              |                    |                         |                      |
| `view_site_analytics` | ✓             | ✓            | ✗                  | ✗                       | ✗                    |
| `view_own_analytics`  | ✓             | ✓            | ✓                  | ✓                       | ✗                    |
| **Content Curation**  |               |              |                    |                         |                      |
| `feature_content`     | ✓             | ✓            | ✗                  | ✗                       | ✗                    |
| `pin_content`         | ✓             | ✓            | ✗                  | ✗                       | ✗                    |
| `hide_content`        | ✓             | ✓            | ✗                  | ✗                       | ✗                    |

---

## MIGRATION & CLEANUP CHECKLIST

### **Database Cleanup Required**

- [x] Identify all users with deprecated roles (Apollo_Roles_Manager::$deprecated_roles)
- [x] Map deprecated roles to standard WordPress equivalents (see $role_migration_map)
- [x] Migrate user role assignments (migrate_all_deprecated_roles() method)
- [x] Remove deprecated role definitions from wp_options (cleanup_deprecated_role_definitions())
- [ ] Verify capability preservation after migration ⚠️ RUN ON PRODUCTION

### **Code Cleanup Required**

- [x] Search and replace all `add_role()` calls in plugins ✅ REMOVED
- [x] Update all `current_user_can()` checks to use standard capabilities ✅ DONE
- [x] Remove custom role registration hooks ✅ DONE
- [x] Update role display functions to use new labels (translate_role_names())
- [x] Clean up capability assignment code ✅ DONE

### **Files Updated** ✅

**Apollo Events Manager:**

- ✅ `apollo-events-manager.php` - REMOVED duplicate role registration
- ✅ `includes/role-badges.php` - Updated to use standard WP roles

**Apollo Social:**

- ✅ `src/API/Endpoints/CenaRioEventEndpoint.php` - Updated permissionCheck()
- ✅ `src/Infrastructure/Rendering/CenaRioRenderer.php` - Updated access check
- ✅ `src/Modules/Registration/RegistrationServiceProvider.php` - clubber→subscriber

**Apollo Core:**

- ✅ `includes/class-apollo-roles-manager.php` - CREATED (single source of truth)
- ✅ `includes/class-permissions.php` - Updated can_access_cena_rio(), can_create_nucleo()
- ✅ `tests/test-activation.php` - Updated tests for standard WP roles

---

## QUICK REFERENCE - ROLE MAPPING

| Old Role Name    | WordPress Slug | New Label Display | Priority to Remove |
| ---------------- | -------------- | ----------------- | ------------------ |
| apollo_moderator | editor         | MOD               | 🔴 Critical        |
| moderator        | editor         | MOD               | 🔴 Critical        |
| mod              | editor         | MOD               | 🔴 Critical        |
| friends          | author         | cult::rio         | 🟠 High            |
| friendz          | author         | cult::rio         | 🟠 High            |
| cena_role        | contributor    | cena::rio         | 🔴 Critical        |
| cenario          | contributor    | cena::rio         | 🔴 Critical        |
| cena-rio         | contributor    | cena::rio         | 🔴 Critical        |
| industry         | contributor    | cena::rio         | 🟡 Medium          |
| apollo_member    | subscriber     | clubber           | 🔴 Critical        |
| clubber (custom) | subscriber     | clubber           | 🔴 Critical        |

---

**Document Version:** 2.0
**Last Updated:** January 24, 2026
**Status:** ✅ IMPLEMENTED - Ready for Deployment
**Cross-Reference:** See capability.plan.md for deployment checklist

---

## IMPLEMENTATION SUMMARY

### Changes Made:

1. **Apollo_Roles_Manager** created in apollo-core as SINGLE SOURCE OF TRUTH
2. **All deprecated role references** replaced with standard WordPress roles:
   - `cena-rio` → `contributor`
   - `clubber` (custom) → `subscriber`
   - `apollo_member` → `subscriber`
3. **Role label translation** via `translate_role_names()` filter
4. **Migration system** for existing users with deprecated roles
5. **Tests updated** to validate standard WordPress roles

### Standard WordPress Roles (FINAL):

| WP Slug       | Apollo Label | Deprecated Roles Merged         |
| ------------- | ------------ | ------------------------------- |
| administrator | apollo       | -                               |
| editor        | MOD          | apollo_moderator, moderator     |
| author        | cult::rio    | friends, friendz                |
| contributor   | cena::rio    | cena-rio, cena_role, industry   |
| subscriber    | clubber      | apollo_member, clubber (custom) |
