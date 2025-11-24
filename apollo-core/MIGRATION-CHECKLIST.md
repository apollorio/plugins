# Apollo Core - Migration Checklist

**From**: `OUTDATED-apollo-*` plugins  
**To**: `apollo-core` unified plugin

---

## Pre-Migration

### ☑ Backup

```bash
# 1. Backup database
cd /path/to/wp/root
wp db export backup-$(date +%Y%m%d-%H%M%S).sql

# 2. Backup plugins folder
cp -r wp-content/plugins wp-content/plugins-backup-$(date +%Y%m%d)

# 3. Verify backups
ls -lh backup-*.sql
ls -ld wp-content/plugins-backup-*
```

### ☑ Document Current State

```bash
# List active plugins
wp plugin list --status=active

# Export current options
wp option get apollo_events_settings > pre-migration-events-settings.json
wp option get apollo_social_settings > pre-migration-social-settings.json
wp option get apollo_rio_settings > pre-migration-rio-settings.json

# Export roles
wp role list --format=json > pre-migration-roles.json

# Count posts by type
wp post list --post_type=event_listing --format=count
wp post list --post_type=event_dj --format=count
wp post list --post_type=event_local --format=count
```

---

## Migration Steps

### Step 1: Deactivate Old Plugins

```bash
wp plugin deactivate apollo-events-manager --skip-plugins
wp plugin deactivate apollo-social --skip-plugins
wp plugin deactivate apollo-rio --skip-plugins
```

**Verify**:
```bash
wp plugin list --status=inactive | grep apollo
```

Expected: All three plugins show as `inactive`.

---

### Step 2: Rename Old Plugin Folders

```bash
cd wp-content/plugins

# Rename to OUTDATED-*
mv apollo-events-manager OUTDATED-apollo-events-manager
mv apollo-social OUTDATED-apollo-social
mv apollo-rio OUTDATED-apollo-rio
```

**Verify**:
```bash
ls -ld OUTDATED-*
```

Expected: Three folders with `OUTDATED-` prefix.

---

### Step 3: Activate Apollo Core

```bash
# From WP root
wp plugin activate apollo-core
```

**What happens**:
- ✅ Creates `apollo` role
- ✅ Creates `apollo_mod_settings` option
- ✅ Creates `wp_apollo_mod_log` table
- ✅ Adds capabilities to administrator
- ✅ Sets activation flag `apollo_core_activated_v1`

**Verify**:
```bash
# Check activation
wp option get apollo_core_activated_v1

# Should output: timestamp (e.g., 1700000000)
```

---

### Step 4: Verify Database

```bash
wp apollo db-test
```

**Expected Output**:
```
=== Apollo Core Database Test ===

1. Testing database connectivity...
Success: Database connection OK

2. Checking apollo_mod_log table...
Success: Table wp_apollo_mod_log exists

3. Checking apollo_mod_settings option...
Success: apollo_mod_settings option exists

4. Checking apollo role...
Success: apollo role exists

=== Test Summary ===
Success: All tests passed!
```

**Exit code should be 0**.

---

### Step 5: Verify Roles & Capabilities

```bash
# List all roles
wp role list

# Check apollo role exists
wp role list | grep apollo

# Check apollo capabilities
wp cap list apollo
```

**Expected Capabilities for apollo**:
- `moderate_apollo_content`
- `edit_apollo_users`
- `view_moderation_queue`
- `send_user_notifications`
- All editor capabilities

---

### Step 6: Configure Moderation Settings

#### Via Admin UI

1. Log in as administrator
2. Go to **WordPress Admin → Moderation**
3. Click **Settings** tab
4. Select moderators (users with apollo role)
5. Enable content type capabilities:
   - ☑ Publish Events
   - ☑ Publish Venues (Locals)
   - ☑ Publish DJs
   - ☑ Edit Social Posts
   - ☑ Edit Classifieds
6. Ensure **Audit Log** is enabled
7. Click **Save Settings**

#### Via WP-CLI (Alternative)

```bash
wp option patch update apollo_mod_settings enabled_caps publish_events true
wp option patch update apollo_mod_settings enabled_caps publish_locals true
wp option patch update apollo_mod_settings enabled_caps publish_djs true
wp option patch update apollo_mod_settings audit_log_enabled true
```

**Verify**:
```bash
wp option get apollo_mod_settings --format=json | jq .
```

---

### Step 7: Test Moderation Workflow

#### Create Test Content

```bash
# Create draft event
wp post create \
  --post_type=event_listing \
  --post_status=draft \
  --post_title="Test Event Migration" \
  --post_author=2 \
  --porcelain
```

Note the post ID (e.g., `123`).

#### Approve via REST API

```bash
# Get nonce (requires logged-in session)
NONCE=$(wp eval 'echo wp_create_nonce("wp_rest");')

# Approve post
curl -X POST "http://yoursite.local/wp-json/apollo/v1/moderation/approve" \
  -H "X-WP-Nonce: $NONCE" \
  -H "Content-Type: application/json" \
  -d '{
    "post_id": 123,
    "note": "Migration test approval"
  }' \
  --cookie "wordpress_logged_in_XXX=YYY"
```

#### Verify Approval

```bash
# Check post status
wp post get 123 --field=post_status

# Should output: publish
```

#### Check Audit Log

```bash
wp apollo mod-log --limit=1
```

Expected: Recent log entry with action `approve_post`.

---

### Step 8: Test User Suspension (Admin Only)

```bash
# Create test user
TEST_USER=$(wp user create testuser test@example.com --role=subscriber --porcelain)

# Suspend for 7 days
curl -X POST "http://yoursite.local/wp-json/apollo/v1/users/suspend" \
  -H "X-WP-Nonce: $NONCE" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": '$TEST_USER',
    "days": 7,
    "reason": "Migration test suspension"
  }' \
  --cookie "wordpress_logged_in_XXX=YYY"

# Verify suspension meta
wp user meta get $TEST_USER _apollo_suspended_until
```

Expected: Unix timestamp in the future.

---

### Step 9: Test Authentication Block

```bash
# Try to authenticate as suspended user (should fail)
wp user check-password testuser testpassword
```

Expected: Error message about suspension.

---

### Step 10: Clean Up Test Data

```bash
# Delete test event
wp post delete 123 --force

# Delete test user
wp user delete $TEST_USER --yes
```

---

## Post-Migration Verification

### ☑ Content Check

```bash
# Count posts by type (should match pre-migration)
echo "Events:"
wp post list --post_type=event_listing --format=count

echo "DJs:"
wp post list --post_type=event_dj --format=count

echo "Locals:"
wp post list --post_type=event_local --format=count
```

Compare with pre-migration counts.

---

### ☑ User Check

```bash
# List users with apollo role
wp user list --role=apollo --fields=ID,user_login,display_name

# Verify moderators in settings
wp option get apollo_mod_settings --format=json | jq .mods
```

---

### ☑ Frontend Check

1. Visit event listing page
2. Verify events display correctly
3. Check event detail pages
4. Test social feed (if applicable)
5. Verify user pages load

---

### ☑ Admin Check

1. Go to **Moderation → Queue**
2. Should see pending items (if any)
3. Go to **Moderation → Users**
4. Should see user list
5. Test approve/suspend actions

---

## Rollback Procedure (If Needed)

### If Migration Fails

```bash
# 1. Deactivate apollo-core
wp plugin deactivate apollo-core

# 2. Restore old plugin folders
cd wp-content/plugins
mv OUTDATED-apollo-events-manager apollo-events-manager
mv OUTDATED-apollo-social apollo-social
mv OUTDATED-apollo-rio apollo-rio

# 3. Reactivate old plugins
wp plugin activate apollo-events-manager
wp plugin activate apollo-social
wp plugin activate apollo-rio

# 4. Restore database backup (if needed)
wp db import backup-YYYYMMDD-HHMMSS.sql

# 5. Verify
wp plugin list --status=active
```

---

## Clean Up (After Successful Migration)

### ⚠️ ONLY after 1+ week of successful operation

```bash
# 1. Remove OUTDATED folders
rm -rf wp-content/plugins/OUTDATED-apollo-events-manager
rm -rf wp-content/plugins/OUTDATED-apollo-social
rm -rf wp-content/plugins/OUTDATED-apollo-rio

# 2. Delete old options (optional)
wp option delete apollo_events_settings
wp option delete apollo_social_settings
wp option delete apollo_rio_settings

# 3. Clean up old backups
rm backup-*.sql
rm -rf wp-content/plugins-backup-*

# 4. Cleanup old mod logs (optional, keep 90 days)
wp eval 'apollo_cleanup_mod_log(90);'
```

---

## Idempotency Notes

Apollo Core activation is **idempotent**. Running activation multiple times will NOT:
- ❌ Duplicate roles
- ❌ Duplicate database tables
- ❌ Overwrite existing settings
- ❌ Delete data

It WILL:
- ✅ Ensure roles exist
- ✅ Ensure capabilities assigned
- ✅ Ensure tables exist
- ✅ Create default settings if missing
- ✅ Update activation timestamp

**Safe to run**:
```bash
wp plugin deactivate apollo-core && wp plugin activate apollo-core
```

---

## Monitoring Post-Migration

### Week 1

- [ ] Check `wp-content/debug.log` daily for errors
- [ ] Run `wp apollo db-test` daily
- [ ] Verify moderation queue working
- [ ] Check audit log for anomalies: `wp apollo mod-log --limit=100`
- [ ] Monitor user complaints about suspensions
- [ ] Test REST endpoints manually

### Week 2-4

- [ ] Check debug.log weekly
- [ ] Review audit log for patterns
- [ ] Verify no data loss (post counts, user counts)
- [ ] Performance check (page load times)
- [ ] SEO check (ensure permalinks unchanged)

---

## Support Contacts

- **Technical Issues**: GitHub Issues
- **Migration Help**: migration@apollo.rio.br
- **Emergency**: Call XXX-XXXX

---

## Migration Completion Checklist

### Before Declaring Success

- [ ] All pre-migration backups created
- [ ] Old plugins deactivated and renamed
- [ ] Apollo Core activated successfully
- [ ] Database test passed (`wp apollo db-test`)
- [ ] Roles and capabilities verified
- [ ] Moderation settings configured
- [ ] Test content approved via REST API
- [ ] Test user suspended successfully
- [ ] Audit log recording actions
- [ ] Post counts match pre-migration
- [ ] Frontend pages load correctly
- [ ] Admin UI functional
- [ ] No errors in debug.log
- [ ] Team trained on new moderation UI
- [ ] Documentation updated
- [ ] Monitoring schedule established

### Sign-Off

- **Migrated By**: _______________________
- **Date**: _______________________
- **Verified By**: _______________________
- **Date**: _______________________

---

**Last Updated**: November 24, 2025  
**Apollo Core Version**: 3.0.0

