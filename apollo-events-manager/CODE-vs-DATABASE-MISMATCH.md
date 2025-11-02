# ⚠️ CODE vs DATABASE - MISMATCH ANALYSIS

**Status:** 🔴 CRITICAL MISMATCH DETECTED  
**Impact:** Data saves but doesn't display  
**Root Cause:** Wrong meta key names in save function

---

## 📊 THE PROBLEM VISUALIZED

```
USER CREATES EVENT
       ↓
Admin saves data via save_custom_event_fields()
       ↓
Data goes to WRONG meta keys:
    _event_djs (should be _event_dj_ids)
    _event_local (should be _event_local_ids)
    _timetable (should be _event_timetable + validated)
       ↓
Templates try to read from CORRECT keys:
    _event_dj_ids ← Not found! (data is in _event_djs)
    _event_local_ids ← Not found! (data is in _event_local)
    _event_timetable ← Not found! (data is in _timetable as numeric)
       ↓
RESULT: Event displays but DJs/Local/Timetable are EMPTY
```

---

## 🔍 DETAILED COMPARISON

### DJs Field

**What code DOES:**
```php
// Line 1026
$_POST['event_djs'] = [92, 71]
         ↓
update_post_meta($post_id, '_event_djs', [92, 71])
         ↓
Database: _event_djs = "a:2:{i:0;i:92;i:1;i:71;}"
```

**What code SHOULD DO:**
```php
$_POST['event_djs'] = [92, 71]
         ↓
$djs = array_map('strval', $djs)  // ["92", "71"]
         ↓
update_post_meta($post_id, '_event_dj_ids', serialize($djs))
         ↓
Database: _event_dj_ids = "a:2:{i:0;s:2:\"92\";i:1;s:2:\"71\";}"
```

**Why it matters:**
- Templates expect: `_event_dj_ids` with STRING array
- Code saves to: `_event_djs` with INTEGER array
- **Result:** DJs don't display

---

### Local Field

**What code DOES:**
```php
// Line 1031
$_POST['event_local'] = 95
         ↓
update_post_meta($post_id, '_event_local', 95)
         ↓
Database: _event_local = 95
```

**What code SHOULD DO:**
```php
$_POST['event_local'] = 95
         ↓
update_post_meta($post_id, '_event_local_ids', 95)
         ↓
Database: _event_local_ids = 95
```

**Why it matters:**
- Templates expect: `_event_local_ids`
- Code saves to: `_event_local`
- **Result:** Local/venue doesn't display

---

### Timetable Field

**What code DOES:**
```php
// Line 1036
$_POST['timetable'] = "355453" (or random data)
         ↓
update_post_meta($post_id, '_timetable', "355453")
         ↓
Database: _timetable = 355453 (numeric)
```

**What code SHOULD DO:**
```php
$_POST['timetable'] = [
    ['dj' => 92, 'start' => '22:00', 'end' => '23:00'],
    ['dj' => 71, 'start' => '23:00', 'end' => '00:00']
]
         ↓
Validate + Sort by time
         ↓
update_post_meta($post_id, '_event_timetable', $sorted_array)
         ↓
Database: _event_timetable = array(...)
```

**Why it matters:**
- Templates expect: `_event_timetable` as array
- Code saves: `_timetable` as numeric (bug)
- **Result:** Line-up completely broken

---

## 🎯 ROOT CAUSE ANALYSIS

### Why this happened:

1. **Initial Implementation:**
   - Plugin was using standard WP Event Manager fields
   - Standard fields: `_event_location`, simple text inputs

2. **Enhancement Added:**
   - Admin metabox created with proper relational data
   - Correct keys: `_event_dj_ids`, `_event_local_ids`
   - Proper serialization and structure

3. **Old Code Not Updated:**
   - `save_custom_event_fields()` still uses old key names
   - Creates conflict with new metabox
   - Data exists in database but in WRONG place

---

## 🔧 FIX STRATEGY

### Option A: Quick Fix (Recommended for now)
**Time:** 5 minutes  
**Risk:** Low  
**Approach:** Change 3 lines

```php
// Line 1026
update_post_meta($post_id, '_event_dj_ids', serialize(array_map('strval', $djs)));

// Line 1031  
update_post_meta($post_id, '_event_local_ids', intval($_POST['event_local']));

// Line 1036
// Add full validation (see ERROR #3 solution)
```

### Option B: Complete Refactor
**Time:** 30 minutes  
**Risk:** Medium  
**Approach:** Remove old save function entirely

1. Comment out entire `save_custom_event_fields()` function
2. Rely only on `admin-metaboxes.php` for saving
3. Test thoroughly
4. Remove commented code if works

### Option C: Data Migration
**Time:** 60 minutes  
**Risk:** High  
**Approach:** Fix code + migrate existing data

1. Fix the 3 lines (Option A)
2. Create migration script to copy:
   - `_event_djs` → `_event_dj_ids`
   - `_event_local` → `_event_local_ids`
3. Delete old keys
4. Test on staging first

---

## 📈 TESTING CHECKLIST

After applying ANY fix:

**Backend Test:**
```
[ ] Create new event in admin
[ ] Select DJs
[ ] Select Local  
[ ] Add timetable
[ ] Save
[ ] Check database for CORRECT keys
[ ] Verify data structure is array (not numeric)
```

**Frontend Test:**
```
[ ] View event page
[ ] DJs display in line-up? ✅/❌
[ ] Local name displays? ✅/❌
[ ] Map shows? ✅/❌
[ ] Timetable shows with times? ✅/❌
```

**Database Verification:**
```sql
SELECT post_id, meta_key, meta_value 
FROM wp_postmeta 
WHERE post_id = [EVENT_ID]
AND meta_key LIKE '%event_%'
ORDER BY meta_key;

Expected to see:
✅ _event_dj_ids (serialized string array)
✅ _event_local_ids (numeric)
✅ _event_timetable (array with dj/start/end)

Should NOT see:
❌ _event_djs
❌ _event_local (unless fallback)
❌ _timetable as numeric
```

---

## 🚨 CRITICAL REMINDER

**BEFORE making ANY changes:**

1. ✅ Backup database
2. ✅ Backup plugin files
3. ✅ Git commit current state
4. ✅ Share this report with team
5. ✅ Test on staging FIRST
6. ⚠️ Coordinate with other developers

**Multiple people are debugging - COMMUNICATE BEFORE CHANGING!**

---

## 📞 COORDINATION CHECKLIST

Before fixing:
- [ ] Notify other developers
- [ ] Agree on which fix strategy (A, B, or C)
- [ ] Assign who makes the changes
- [ ] Set testing window
- [ ] Prepare rollback plan

During fix:
- [ ] One person changes code
- [ ] Others verify in database
- [ ] Test immediately after save
- [ ] Document what changed

After fix:
- [ ] Verify all events display correctly
- [ ] Check no new errors in debug.log
- [ ] Update documentation
- [ ] Share success report

---

**Analysis completed:** November 2, 2025  
**Status:** Ready for coordinated fix  
**Recommendation:** Use Option A (Quick Fix) first, test, then decide on B or C

---

⚠️ **REMINDER: NO FILES WERE MODIFIED BY THIS ANALYSIS**

