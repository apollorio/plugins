# Apollo Plugin Ecosystem - Fix Log

**Date:** 2025-01-13  
**Auditor:** Automated QA System

---

## Summary

| Category | Files Modified | Changes Made |
|----------|---------------|--------------|
| CDN Elimination | 25+ | Replaced all CDN URLs with local assets |
| Input Sanitization | 1 | Added proper sanitization to controls.php |
| SQL Hardening | 1 | Updated wp-cli/commands.php |
| Apollo_Assets | 1 | Added Chart.js, DataTables vendor libraries |

---

## Detailed Fix Log

### 1. Apollo_Assets Updates

**File:** `apollo-core/includes/class-apollo-assets.php`

| Line | Change |
|------|--------|
| +157-165 | Added `apollo-vendor-chartjs` registration |
| +167-182 | Added `apollo-vendor-datatables` CSS and JS registration |
| +289-293 | Added legacy handles: `chartjs`, `chart-js`, `datatables-js`, `datatables-css` |
| +286-288 | Added legacy handles: `framer-motion`, `leaflet` |

**New Vendor Libraries Downloaded:**
- `assets/vendor/chartjs/chart.umd.min.js` (205KB)
- `assets/vendor/datatables/jquery.dataTables.min.js` (87KB)
- `assets/vendor/datatables/jquery.dataTables.min.css` (23KB)

---

### 2. CDN Eliminations in apollo-events-manager

**File:** `apollo-events-manager.php`

| Line | Before | After |
|------|--------|-------|
| 1150 | `https://assets.apollo.rio.br/uni.css` | `APOLLO_CORE_PLUGIN_URL . 'assets/core/uni.css'` |
| 1362 | `https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css` | `wp_enqueue_style('remixicon')` |
| 1405 | Same CDN | `wp_enqueue_style('remixicon')` |
| 1451 | `https://unpkg.com/leaflet@1.9.4/dist/leaflet.js` | `wp_enqueue_script('leaflet')` |
| 1459 | `https://unpkg.com/leaflet@1.9.4/dist/leaflet.css` | `wp_enqueue_style('leaflet')` |
| 1674 | `https://assets.apollo.rio.br/event-page.js` | `wp_enqueue_script('apollo-core-event-page')` |
| 5716 | CDN uni.css | `wp_enqueue_style('apollo-uni-css')` |
| 5720 | CDN remixicon | `wp_enqueue_style('remixicon')` |

**File:** `includes/admin-dashboard.php`

| Line | Before | After |
|------|--------|-------|
| 143-148 | CDN DataTables | `wp_enqueue_script('datatables-js')` |
| 151-156 | CDN Chart.js | `wp_enqueue_script('chartjs')` |

**File:** `includes/admin-metaboxes.php`

| Line | Before | After |
|------|--------|-------|
| 86 | CDN remixicon | `wp_enqueue_style('remixicon')` |
| 91 | CDN framer-motion | `wp_enqueue_script('framer-motion')` |

**File:** `includes/admin-statistics-menu.php`

| Line | Before | After |
|------|--------|-------|
| 64-70 | CDN Chart.js | `wp_enqueue_script('chart-js')` |

**File:** `includes/motion-loader.php`

| Line | Before | After |
|------|--------|-------|
| 37-42 | CDN framer-motion | `wp_enqueue_script('framer-motion')` |

**File:** `includes/apollo-shadcn-loader.php`

| Line | Before | After |
|------|--------|-------|
| 68 | CDN uni.css | `wp_enqueue_style('apollo-uni-css')` |
| 78 | CDN remixicon | `wp_enqueue_style('remixicon')` |

---

### 3. Template CDN Fixes

**File:** `templates/single-event_listing.php`

| Line | Change |
|------|--------|
| 30-44 | Replaced Leaflet CDN enqueue with `wp_enqueue_style/script('leaflet')` |
| 305 | Replaced CDN `default-event.jpg` with dynamic PHP path |
| 377-379 | Replaced inline CDN links with `wp_head()` enqueued assets |
| 418-420 | Same as above for second HTML block |

**File:** `templates/dashboard/dashboard-main.php`

| Line | Change |
|------|--------|
| 155 | Removed inline Chart.js script, added `wp_enqueue_script('chartjs')` |

**Files with favicon/placeholder image fixes:**
- `templates/print-single-event.php`
- `templates/shortcode-cena-rio.php`
- `templates/shortcode-dj-profile.php`
- `templates/shortcode-user-dashboard.php`
- `templates/single-event_dj.php`
- `templates/single-event_local.php`
- `templates/single-event-standalone.php`

All updated to use:
```php
<?php echo esc_url( defined('APOLLO_CORE_PLUGIN_URL') 
    ? APOLLO_CORE_PLUGIN_URL . 'assets/img/' 
    : APOLLO_APRIO_URL . 'assets/img/' ); ?>
```

---

### 4. Security Fixes

**File:** `apollo-email-newsletter/includes/controls.php`

| Line | Before | After |
|------|--------|-------|
| 12 | `stripslashes_deep($_POST['options'])` | `map_deep(wp_unslash($_POST['options']), 'sanitize_text_field')` |
| 18 | `$_REQUEST['act']` | `sanitize_key(wp_unslash($_REQUEST['act']))` |
| 22 | `$_REQUEST['btn']` | `sanitize_text_field(wp_unslash($_REQUEST['btn']))` |
| 26 | `$_REQUEST['fields']` | Added `is_array()` check and sanitization |
| 35 | Direct `$_REQUEST` date access | Used `absint()` for all numeric fields |

**File:** `apollo-core/wp-cli/commands.php`

| Line | Before | After |
|------|--------|-------|
| 60 | `"SELECT COUNT(*) FROM $table_name"` | `$wpdb->prepare('SELECT COUNT(*) FROM \`%1$s\`', $table_name)` |

---

### 5. Files NOT Modified (Per Requirements)

The following files were intentionally NOT modified:
- `*.bak` files
- `*-OLD.php` files
- `*WRONG.php` files

These are backup/legacy files and should be removed in a future cleanup.

---

## Verification Commands

```bash
# Check for remaining CDN references (should return 0 for active files)
grep -r --include="*.php" "cdn.jsdelivr\|unpkg.com\|assets.apollo" apollo-events-manager/ | grep -v "\.bak\|OLD\|WRONG"

# Check for unsanitized input
grep -rn "\$_REQUEST\|\$_POST\|\$_GET" apollo-email-newsletter/includes/controls.php

# Run PHPCS
vendor/bin/phpcs --standard=phpcs.xml.dist apollo-core/ apollo-events-manager/
```

---

## Post-Fix Validation

| Check | Status |
|-------|--------|
| CDN references eliminated | ✅ Verified |
| All assets load from local vendor/ | ✅ Verified |
| Input sanitization in controls.php | ✅ Applied |
| SQL queries use prepare() | ✅ Verified |
| Legacy asset handles work | ✅ Registered |

---

**Document Version:** 1.0.0  
**Last Updated:** 2025-01-13
