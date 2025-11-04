# 🔒 Security Fixes - 2025-11-04

## ✅ CRITICAL SECURITY FIXES APPLIED

### 1. ✅ AJAX CSRF Protection (CRITICAL)

**Problem:** AJAX endpoints without nonce verification
**Risk:** CSRF attacks, unauthorized data manipulation

**Files Modified:**
- `apollo-events-manager.php`

**Changes:**

#### `ajax_load_event_single()` - Line 802
```php
// BEFORE
public function ajax_load_event_single() {
    $event_id = intval($_POST['event_id'] ?? 0);
    if (!$event_id) {
        wp_die('Invalid event ID');
    }
    // ...
}

// AFTER
public function ajax_load_event_single() {
    check_ajax_referer('apollo_events_nonce', 'nonce');
    
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    
    if ($event_id <= 0) {
        wp_send_json_error('Evento inválido');
    }
    
    // Verify event exists and is correct post type
    $post = get_post($event_id);
    if (!$post || $post->post_type !== 'event_listing') {
        wp_send_json_error('Evento não encontrado');
    }
    // ...
}
```

#### `ajax_toggle_favorite()` - Line 829
```php
// BEFORE
public function ajax_toggle_favorite() {
    $event_id = intval($_POST['event_id'] ?? 0);
    if (!$event_id) {
        wp_send_json_error('Invalid event ID');
    }
    // ...
}

// AFTER
public function ajax_toggle_favorite() {
    check_ajax_referer('apollo_events_nonce', 'nonce');
    
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    
    if ($event_id <= 0) {
        wp_send_json_error('Evento inválido');
    }
    
    // Verify event exists
    $event = get_post($event_id);
    if (!$event || $event->post_type !== 'event_listing') {
        wp_send_json_error('Evento não encontrado');
    }
    // ...
}
```

**JavaScript Integration:**
Nonce already being sent via `wp_localize_script()`:
```php
// Line 324 & 343
wp_localize_script('apollo-base-js', 'apollo_events_ajax', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('apollo_events_nonce')
));
```

**Impact:** 🔴 HIGH - Prevents CSRF attacks on AJAX endpoints

---

### 2. ✅ Remote CSS Injection Removed (CRITICAL)

**Problem:** Remote CSS fetched via HTTP and injected inline without sanitization
**Risk:** XSS if CDN compromised, 15s timeout blocking page load

**Files Modified:**
- `templates/portal-discover.php`
- `templates/single-event-standalone.php`

**Changes:**

#### Before (portal-discover.php):
```php
// Lines 11-18 & 36-51 (REMOVED)
$css_url = 'https://assets.apollo.rio.br/uni.css';
$css_response = wp_remote_get($css_url, ['timeout' => 15]);

if (!is_wp_error($css_response)) {
    $css_content = wp_remote_retrieve_body($css_response);
    if ($css_content) {
        echo '<style type="text/css">' . $css_content . '</style>';
    }
}
```

#### After:
```php
// Just call wp_head() - CSS loaded via wp_enqueue_style()
<?php wp_head(); ?>
```

**CSS Loading:**
Now handled properly via `enqueue_assets()` in main plugin:
```php
// Line 304-309
wp_enqueue_style(
    'apollo-uni-css',
    'https://assets.apollo.rio.br/uni.css',
    array('remixicon'),
    '2.0.0'
);
```

**Benefits:**
- ✅ No blocking HTTP request during page render
- ✅ Browser caching works properly
- ✅ No inline injection vulnerability
- ✅ Standard WordPress asset loading

**Impact:** 🔴 HIGH - Eliminates XSS risk and improves performance

---

### 3. ✅ Serialization Handled by WordPress (MEDIUM)

**Problem:** Manual `serialize()` calls causing data fragility
**Risk:** Data corruption, inconsistent formats, harder debugging

**Files Modified:**
- `apollo-events-manager.php`
- `includes/admin-metaboxes.php`

**Changes:**

#### apollo-events-manager.php - Line 1019
```php
// BEFORE
if (isset($_POST['event_djs'])) {
    $djs = array_map('strval', array_map('intval', (array) $_POST['event_djs']));
    update_post_meta($post_id, '_event_dj_ids', serialize($djs));
}

// AFTER
if (isset($_POST['event_djs'])) {
    $djs = array_map('intval', (array) $_POST['event_djs']);
    update_post_meta($post_id, '_event_dj_ids', $djs);
}
```

#### admin-metaboxes.php - Line 404
```php
// BEFORE
if (isset($_POST['apollo_event_djs']) && is_array($_POST['apollo_event_djs'])) {
    $dj_ids = array_map('strval', array_map('intval', $_POST['apollo_event_djs']));
    update_post_meta($post_id, '_event_dj_ids', serialize($dj_ids));
}

// AFTER
if (isset($_POST['apollo_event_djs']) && is_array($_POST['apollo_event_djs'])) {
    $dj_ids = array_map('intval', $_POST['apollo_event_djs']);
    update_post_meta($post_id, '_event_dj_ids', $dj_ids);
}
```

**Benefits:**
- ✅ WordPress handles serialization automatically
- ✅ Consistent data format (integers, not strings)
- ✅ `maybe_unserialize()` works correctly
- ✅ Easier debugging
- ✅ Backward compatible (WordPress handles both formats)

**Impact:** 🟡 MEDIUM - Improves data reliability and consistency

---

## 📊 Summary

### Files Modified: 6
1. `apollo-events-manager/apollo-events-manager.php` (5 functions + 1 require)
2. `apollo-events-manager/includes/admin-metaboxes.php` (1 function)
3. `apollo-events-manager/templates/portal-discover.php` (removed CSS injection)
4. `apollo-events-manager/templates/single-event-standalone.php` (removed CSS injection)
5. `wpem-rest-api/wpem-rest-api.php` (ALTER TABLE safety)
6. `apollo-events-manager/includes/data-migration.php` (NEW - 400+ lines)

### Lines Changed: ~500 lines
- Added: ~450 lines (security checks + migration utilities)
- Removed: ~40 lines (CSS injection code)
- Modified: ~20 lines (serialize removal, template override, ALTER TABLE)

### Security Improvements:
- 🔴 **CRITICAL:** CSRF protection on 2 AJAX endpoints
- 🔴 **CRITICAL:** Removed remote CSS injection vulnerability
- 🟡 **MEDIUM:** Improved data serialization reliability
- 🟡 **MEDIUM:** Template override respects theme
- 🟡 **MEDIUM:** ALTER TABLE with existence check

### Performance Improvements:
- ⚡ Eliminated 15s timeout risk on page load
- ⚡ Proper browser caching for CSS assets
- ⚡ Reduced HTTP requests during render

### Maintenance Tools:
- 🛠️ WP-CLI migration commands
- 🛠️ Meta key standardization
- 🛠️ Timetable structure normalization
- 🛠️ Migration status reporting

---

## ✅ Testing Checklist

### AJAX Endpoints:
- [ ] Test event lightbox loading (requires nonce)
- [ ] Test favorite toggle (requires nonce)
- [ ] Verify error messages in console if nonce missing
- [ ] Test with logged-out users

### CSS Loading:
- [ ] Verify uni.css loads via wp_head()
- [ ] Check browser network tab (should be single request, cached)
- [ ] Test page load speed (should be faster)
- [ ] Verify no inline <style> tags with remote CSS

### Data Integrity:
- [ ] Create new event with DJs
- [ ] Verify `_event_dj_ids` is array of integers
- [ ] Edit existing event with DJs
- [ ] Verify data retrieval still works (backward compatible)

---

## 🚀 Deployment Notes

### No Database Migration Required
- Changes are backward compatible
- Old serialized data still works
- New data uses WordPress native serialization

### No JavaScript Changes Required
- Nonce already being sent via `apollo_events_ajax.nonce`
- External JS files (base.js, event-page.js) should already use it

### Cache Clearing Recommended
- Clear WordPress object cache
- Clear browser cache for CSS changes
- Flush rewrite rules if needed

---

## 🔧 Additional Improvements (Applied)

### 4. ✅ Template Override Robustness (MEDIUM)

**Problem:** Template override was fragile, could conflict with theme
**Risk:** Theme conflicts, unexpected behavior

**File Modified:** `apollo-events-manager.php` - Line 265

**Changes:**
```php
// BEFORE
public function canvas_template($template) {
    if (is_page('eventos')) {
        global $post;
        if (isset($post) && has_shortcode($post->post_content, 'eventos-page')) {
            return $template;
        }
        return APOLLO_WPEM_PATH . 'templates/portal-discover.php';
    }
    return $template;
}

// AFTER
public function canvas_template($template) {
    // Don't override in admin or if not the eventos page
    if (!is_page('eventos') || is_admin()) {
        return $template;
    }
    
    // If theme already has page-eventos.php, don't override
    $theme_template = locate_template('page-eventos.php');
    if ($theme_template) {
        return $theme_template;
    }
    
    global $post;
    // Don't override if page has eventos-page shortcode
    if (isset($post) && has_shortcode($post->post_content, 'eventos-page')) {
        return $template;
    }
    
    return APOLLO_WPEM_PATH . 'templates/portal-discover.php';
}
```

**Benefits:**
- ✅ Admin guard prevents override in backend
- ✅ Respects theme's `page-eventos.php` if exists
- ✅ Better error prevention
- ✅ More predictable behavior

**Impact:** 🟡 MEDIUM - Prevents theme conflicts

---

### 5. ✅ ALTER TABLE Safety (MEDIUM)

**Problem:** ALTER TABLE without checking if table exists
**Risk:** SQL errors on plugin updates

**File Modified:** `wpem-rest-api/wpem-rest-api.php` - Line 171

**Changes:**
```php
// BEFORE
$columns = $wpdb->get_col("DESC {$table_name}", 0);

if (!in_array('event_show_by', $columns)) {
    $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN event_show_by...");
}

// AFTER
// Verify table exists before attempting ALTER
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");

if ($table_exists) {
    $columns = $wpdb->get_col("DESC {$table_name}", 0);
    
    if (!in_array('event_show_by', $columns)) {
        $result = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN event_show_by...");
        if ($result === false) {
            error_log('WPEM REST API: Failed to add event_show_by column - ' . $wpdb->last_error);
        }
    }
}
```

**Benefits:**
- ✅ Checks table existence before ALTER
- ✅ Error logging for failed queries
- ✅ Prevents SQL errors on updates
- ✅ Better debugging

**Impact:** 🟡 MEDIUM - Prevents SQL errors

---

### 6. ✅ Data Migration Utilities (LOW - Maintenance)

**Problem:** Inconsistent meta keys and timetable structures
**Solution:** Created migration utility with WP-CLI commands

**File Created:** `includes/data-migration.php` (400+ lines)

**Features:**
- ✅ Migrate `_event_local` → `_event_local_ids`
- ✅ Migrate `_local_lat` → `_local_latitude`
- ✅ Migrate `_local_lng` → `_local_longitude`
- ✅ Standardize timetable structure (3 formats → 1)
- ✅ Fix coordinate data types
- ✅ Migration flag to prevent re-runs
- ✅ WP-CLI commands

**WP-CLI Commands:**
```bash
# Run migration
wp apollo migrate run

# Check status
wp apollo migrate status

# Reset flag (for re-running)
wp apollo migrate reset
```

**Migration Process:**
1. Checks for old meta keys
2. Migrates to standard keys
3. Keeps old keys for backward compatibility
4. Standardizes timetable array structure
5. Fixes coordinate data types
6. Marks migration as completed

**Impact:** 🟢 LOW - Maintenance tool, not critical for production

---

## 📝 Additional Recommendations

### Still TODO (Not Critical):
1. 🟡 Implement user-based favorites tracking (currently just counter)
2. 🟡 Add rate limiting to AJAX endpoints
3. 🟢 Add retry logic for Nominatim geocoding
4. 🟢 Optimize duplicate check in admin (use WP_Query instead of loop)

### Monitoring:
- Watch error logs for AJAX nonce failures
- Monitor CSS load times
- Check for any serialization issues with existing data

---

**Status:** ✅ ALL CRITICAL FIXES APPLIED  
**Date:** 2025-11-04  
**Version:** 2.0.1 (recommended bump)  
**Tested:** ⏳ Pending production testing

---

## 🔗 Related Documentation
- Architecture report: `ARCHITECTURE-REPORT.md`
- Original security audit: Architecture map output
- WordPress nonce documentation: https://developer.wordpress.org/apis/security/nonces/

