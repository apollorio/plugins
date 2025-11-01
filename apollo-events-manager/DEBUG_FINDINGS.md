# 🔍 Apollo Events - Debug Findings & Data Structure

**Last Debug Run:** November 1, 2025  
**Event Analyzed:** ID 143 (Teste)  
**Status:** ⚠️ Multiple critical mismatches found

---

## 🔴 CRITICAL ISSUES FOUND

### Issue 1: Timetable Mismatch
**Problem:** Templates expect array, database has numeric

**Database:**
```
_timetable => 355453 (numeric)
```

**Template Expects:**
```php
$timetable = array(
    array(
        'dj' => 92,
        'time_in' => '22:00',
        'time_out' => '23:00'
    )
);
```

**Impact:** DJ lineup not displaying  
**Priority:** 🔴 HIGH

---

### Issue 2: DJ IDs Storage
**Problem:** Template looks for wrong meta key

**Database:**
```
_event_dj_ids => 'a:2:{i:0;s:2:"92";i:1;s:2:"71";}'
```
Unserializes to: `array("92", "71")`

**Template Incorrectly Looks For:**
```php
get_post_meta($id, '_event_djs', true); // WRONG KEY!
```

**Correct Code:**
```php
$dj_ids = maybe_unserialize(get_post_meta($id, '_event_dj_ids', true));
if (is_array($dj_ids)) {
    foreach ($dj_ids as $dj_id) {
        $dj_id = intval($dj_id); // Convert string to int
        // Use $dj_id...
    }
}
```

**Impact:** DJ info not displaying  
**Priority:** 🔴 HIGH

---

### Issue 3: Local/Venue IDs Storage
**Problem:** Template looks for wrong meta key

**Database:**
```
_event_local_ids => 95 (numeric)
```

**Template Incorrectly Looks For:**
```php
get_post_meta($id, '_event_local', true); // WRONG KEY!
```

**Correct Code:**
```php
$local_id = get_post_meta($id, '_event_local_ids', true);
if (!empty($local_id) && is_numeric($local_id)) {
    $local_post = get_post(intval($local_id));
    if ($local_post && $local_post->post_status === 'publish') {
        // Use local data...
    }
}
```

**Impact:** Venue info not displaying  
**Priority:** 🔴 HIGH

---

### Issue 4: Banner is URL, not Attachment ID
**Problem:** Code treats banner as attachment ID

**Database:**
```
_event_banner => "http://localhost:10004/wp-content/uploads/2025/10/..."
```

**Template Incorrectly Does:**
```php
$banner_url = wp_get_attachment_url($banner); // FAILS - it's already URL!
```

**Correct Code:**
```php
$banner_url = get_post_meta($id, '_event_banner', true);
if (!empty($banner_url) && filter_var($banner_url, FILTER_VALIDATE_URL)) {
    echo '<img src="' . esc_url($banner_url) . '" alt="Event Banner">';
}
```

**Impact:** Banner images not showing  
**Priority:** 🟡 MEDIUM (has fallback)

---

## ✅ WORKING CORRECTLY

- Event title: `_event_title` ✓
- Date/time fields: `_event_start_date`, `_event_start_time` ✓
- Local post exists: ID 95 (dedge) ✓
- DJ posts exist: IDs 92, 71 ✓
- Taxonomy terms: event_sounds ✓

---

## 📊 ACTUAL META KEYS REFERENCE

**Copy this for AI assistants - ACTUAL DATABASE STRUCTURE:**

```php
// Events Meta Keys (CPT: event_listing)
'_event_title'           => string                    // Event name
'_event_banner'          => string (URL)              // Direct URL, NOT attachment ID!
'_event_video_url'       => string                    // YouTube/Vimeo URL
'_event_start_date'      => string (YYYY-MM-DD HH:MM:SS)
'_event_end_date'        => string (YYYY-MM-DD HH:MM:SS)
'_event_start_time'      => string (HH:MM:SS)
'_event_end_time'        => string (HH:MM:SS)
'_event_country'         => string ("br")
'_tickets_ext'           => string                    // External ticket URL
'_cupom_ario'           => numeric (0/1)              // Boolean flag
'_3_imagens_promo'      => string (serialized array)  // Promo images
'_timetable'            => numeric (⚠️ BUG: should be array)
'_imagem_final'         => string (serialized array)
'_event_dj_ids'         => string (serialized array)  // ["92", "71"] as strings!
'_event_local_ids'      => numeric                    // Single local post ID

// Local Meta Keys (CPT: event_local)
'_local_name'           => string
'_local_description'    => string
'_local_website'        => string (URL)
'_local_facebook'       => string (URL)
'_local_instagram'      => string (URL)
'_local_latitude'       => float/string
'_local_longitude'      => float/string
'_local_address'        => string

// DJ Meta Keys (CPT: event_dj)
'_dj_name'              => string
// (add more as discovered)
```

---

## 🛠️ REQUIRED FIXES

### Fix 1: Update all templates to use correct meta keys

**Files to update:**
- `templates/single-event_listing.php`
- `templates/single-event.php`
- `templates/single-event-standalone.php`
- `templates/single-event-page.php`
- `templates/content-event_listing.php`
- `templates/event-card.php`
- `templates/portal-discover.php`

**Changes required:**

1. **DJ IDs:**
   ```php
   // ❌ WRONG
   get_post_meta($id, '_event_djs', true);
   
   // ✅ CORRECT
   $dj_ids = maybe_unserialize(get_post_meta($id, '_event_dj_ids', true));
   ```

2. **Local/Venue ID:**
   ```php
   // ❌ WRONG
   get_post_meta($id, '_event_local', true);
   
   // ✅ CORRECT
   get_post_meta($id, '_event_local_ids', true);
   ```

3. **Banner URL:**
   ```php
   // ❌ WRONG
   wp_get_attachment_url($banner);
   
   // ✅ CORRECT
   $banner = get_post_meta($id, '_event_banner', true);
   // It's already a URL, use directly
   ```

### Fix 2: Add defensive validation everywhere

**All meta retrievals MUST include:**
```php
// 1. Check if exists
if (!empty($meta_value)) {
    
    // 2. Unserialize if needed
    $data = maybe_unserialize($meta_value);
    
    // 3. Type validation
    if (is_array($data)) {
        foreach ($data as $item) {
            // 4. Convert types
            $id = intval($item);
            
            // 5. Post existence check
            $post = get_post($id);
            if ($post && $post->post_status === 'publish') {
                // Safe to use
            }
        }
    }
}
```

### Fix 3: Timetable save logic
File: `apollo-events-manager.php`  
Function: `save_custom_event_fields()`

**Current issue:** Saves numeric ID instead of array structure

**Required structure:**
```php
$timetable = array(
    array(
        'dj' => 92,           // int
        'time_in' => '22:00', // string HH:MM
        'time_out' => '23:00' // string HH:MM
    ),
    array(
        'dj' => 71,
        'time_in' => '23:00',
        'time_out' => '00:00'
    )
);
```

---

## 🎯 TESTING CHECKLIST

After fixes, verify:

- [ ] DJ names display on event page
- [ ] DJ avatars/photos display
- [ ] DJ links work
- [ ] Venue name displays
- [ ] Venue address/map shows
- [ ] Event banner image shows
- [ ] Timetable displays correctly
- [ ] All external URLs work
- [ ] No PHP errors in debug.log

---

## 📝 NOTES FOR AI ASSISTANTS

**BEFORE making ANY changes to templates:**

1. ✅ **READ this file first**
2. ✅ **Use exact meta keys documented here**
3. ✅ **Add defensive validation (unserialize, type check, exists check)**
4. ✅ **Convert string IDs to integers before using**
5. ❌ **NEVER guess meta key names**
6. ❌ **NEVER assume data types without checking**

**Golden Rule:**  
*"If you haven't seen it in debug output, you don't know it exists."*

---

**Status:** ⚠️ Multiple critical fixes needed  
**Next Steps:** Apply fixes to all templates systematically  
**Priority:** Fix DJs and Local first (user-visible impact)

