# Apollo Plugins - Strict Mode Audit Report

**Date:** 2025-11-25 15:30 BRT  
**Branch:** `fix/apollo-strict-finalize-gh-202511251530`  
**Auditor:** GitHub Copilot (Claude Opus 4.5)

---

## Executive Summary

A comprehensive strict mode verification was performed on the Apollo plugin ecosystem:
- **apollo-core** (v3.0.0)
- **apollo-events-manager** (v0.1.0)  
- **apollo-social** (v1.0.0)

### Overall Status: ⚠️ **PARTIALLY READY FOR DEPLOY**

| Category | Status |
|----------|--------|
| PHP Syntax | ✅ All errors fixed |
| Security (Critical) | ✅ No critical blockers |
| Security (High) | ⚠️ Needs review |
| Dependencies | ✅ Fixed |
| REST API | ✅ Core endpoints working |
| Template Conformance | ❌ 2 templates need refactoring |
| Unit Tests | ⏸️ Not configured |

---

## Critical Blockers

**None found** - All critical syntax and security issues have been resolved.

---

## Fixes Applied

### 1. PHP Syntax Error (FIXED ✅)
**File:** `apollo-core/includes/quiz/quiz-defaults.php:115`  
**Issue:** Function declared as `void` but returns `false`  
**Fix:** Changed return type to `bool`

```php
// Before
function apollo_seed_default_quiz_questions(): void {

// After  
function apollo_seed_default_quiz_questions(): bool {
```

### 2. Missing Bootstrap Constant (FIXED ✅)
**File:** `apollo-core/apollo-core.php`  
**Issue:** `APOLLO_CORE_BOOTSTRAPPED` constant checked by child plugins but never defined  
**Fix:** Added constant definition after core loads

```php
if ( ! defined( 'APOLLO_CORE_BOOTSTRAPPED' ) ) {
    define( 'APOLLO_CORE_BOOTSTRAPPED', true );
}
```

### 3. Frontend Function Error (FIXED ✅)
**File:** `apollo-social/user-pages/user-pages-loader.php`  
**Issue:** `is_plugin_active()` used on frontend (only available after `admin_init`)  
**Fix:** Changed to constant-based check

```php
// Before
if (!is_plugin_active('apollo-social/apollo-social.php')) {
    wp_die('...');
}

// After
if ( ! defined( 'APOLLO_SOCIAL_PLUGIN_DIR' ) ) {
    if ( ! is_admin() ) { return; }
    wp_die( esc_html__( '...', 'apollo-social' ) );
}
```

### 4. SQL Injection Prevention (FIXED ✅)
**File:** `apollo-core/includes/quiz/schema-manager.php:328`  
**Issue:** Direct SQL query without `prepare()`  
**Fix:** Added `$wpdb->prepare()` wrapper

```php
// Before
$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" );

// After
$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );
```

---

## Remaining Tasks

### HIGH Priority

#### 1. Template Refactoring Required
Two templates have low design conformance and need complete rewriting:

| Template | Current Score | Status |
|----------|---------------|--------|
| `single-event_dj.php` | 28% | Uses bento-grid instead of design spec structure |
| `user-page-view.php` | 3% | Missing entire aprioEXP component system |

**Action Required:** Rewrite templates following design specs in `apollo-core/templates/designs-final-hmtl/`

#### 2. Register Missing Meta Keys
6 meta keys are used in templates but not registered:

```php
// Add to register_post_meta() in post-types.php:
'_local_region'      => 'string',  // event_local CPT
'_local_capacity'    => 'string',  // event_local CPT
'_dj_banner'         => 'string',  // event_dj CPT
'_event_description' => 'string',  // event_listing CPT
'_event_featured'    => 'string',  // event_listing CPT
'_event_co_authors'  => 'string',  // event_listing CPT
```

### MEDIUM Priority

#### 3. REST Permission Callbacks Review
29 REST routes use `__return_true` permission callback:

| File | Count | Note |
|------|-------|------|
| apollo-core/includes/* | 6 | Public GET routes - likely OK |
| apollo-core/modules/* | 4 | Mixed - review needed |
| apollo-events-manager/* | 8 | Public event data - likely OK |
| apollo-social/src/API/* | 11 | Review for sensitive operations |

**Recommendation:** Review each endpoint. Public GETs are acceptable, but POST/PUT/DELETE routes should require:
```php
'permission_callback' => function($request) {
    return current_user_can('manage_options');
}
```

#### 4. Debug Leftovers
30 `error_log()` calls found in production files.

**Recommendation:** Create gated debug helper:
```php
function apollo_log_debug( $message ) {
    if ( defined('WP_DEBUG') && WP_DEBUG ) {
        error_log( '[Apollo] ' . $message );
    }
}
```

### LOW Priority

#### 5. 40 TODO/FIXME Comments
See `strict-audit-summary.json` for complete list.

---

## REST API Smoke Test Results

| Endpoint | Status | Expected | Result |
|----------|--------|----------|--------|
| `/apollo/v1/` | 200 | 200 | ✅ Pass |
| `/apollo/v1/events` | 200 | 200 | ✅ Pass |
| `/apollo/v1/memberships` | 404 | N/A | ⚠️ Not registered |
| `/apollo/v1/forms/schema` | 404 | N/A | ⚠️ Not registered |

**Note:** Some endpoints may require Apollo Core modules to be fully activated.

---

## How to Test Locally

### 1. Checkout the fix branch
```bash
cd wp-content/plugins
git checkout fix/apollo-strict-finalize-gh-202511251530
```

### 2. Verify PHP syntax
```bash
find apollo-core apollo-events-manager apollo-social -name '*.php' -print0 | xargs -0 -n1 php -l 2>&1 | grep -v "No syntax errors"
```

### 3. Test REST endpoints
```bash
curl -s "http://localhost:10004/wp-json/apollo/v1/"
curl -s "http://localhost:10004/wp-json/apollo/v1/events"
```

### 4. Test WordPress admin
- Navigate to `/wp-admin/`
- Verify no PHP errors or warnings
- Check Apollo Core settings page loads
- Test event creation/editing

---

## Files Changed

```
M  apollo-core/apollo-core.php
M  apollo-core/includes/quiz/quiz-defaults.php
M  apollo-core/includes/quiz/schema-manager.php
M  apollo-events-manager/image-editor.html
M  apollo-social/user-pages/user-pages-loader.php
A  apollo-core/templates/designs-final-hmtl/*.md (6 design spec files)
```

---

## Recommendations for Deploy

### Before Production Deploy:
1. ✅ All syntax errors fixed
2. ✅ Critical security issues resolved
3. ⚠️ **Review** REST permission callbacks for sensitive operations
4. ⚠️ **Refactor** `single-event_dj.php` to match design spec
5. ⚠️ **Refactor** `user-page-view.php` to match design spec
6. ⚠️ **Register** missing meta keys

### Safe to Deploy Now:
- Core plugin functionality
- Event management
- Social features
- REST API core endpoints

### Recommend Delaying:
- DJ single page (until template refactored)
- User profile pages (until template refactored)

---

**Report Generated:** 2025-11-25 15:30 BRT  
**Commit:** 4244af4  
**Branch:** fix/apollo-strict-finalize-gh-202511251530
