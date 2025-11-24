# Apollo Core - Moderation System Documentation

**Version**: 3.0.0  
**Last Updated**: November 24, 2025

---

## Table of Contents

1. [Overview](#overview)
2. [Roles & Capabilities](#roles--capabilities)
3. [Settings Structure](#settings-structure)
4. [REST API Endpoints](#rest-api-endpoints)
5. [Admin UI](#admin-ui)
6. [Authentication Filters](#authentication-filters)
7. [Audit Logging](#audit-logging)
8. [WP-CLI Commands](#wp-cli-commands)
9. [Testing](#testing)
10. [Migration Guide](#migration-guide)
11. [Security Checklist](#security-checklist)
12. [Development](#development)

---

## Overview

The Apollo Core moderation system provides a comprehensive content moderation framework with:

- **Custom Role**: `apollo` moderator role with granular capabilities
- **Content Approval**: Queue system for draft/pending content
- **User Management**: Suspend or block users
- **Audit Logging**: Complete history of moderation actions
- **REST API**: Secure endpoints for moderation actions
- **Admin UI**: Three-tabbed interface for settings, queue, and user management

---

## Roles & Capabilities

### Apollo Moderator Role (`apollo`)

Based on the `editor` role with additional capabilities:

```php
- moderate_apollo_content   // Approve/reject content
- edit_apollo_users          // Edit user profiles and memberships
- view_moderation_queue      // View pending content
- send_user_notifications    // Send notifications to users
```

### Administrator Additions

Administrators get additional moderation capabilities:

```php
- manage_apollo_mod_settings  // Edit moderation settings
- suspend_users               // Temporarily suspend users
- block_users                 // Permanently block users
```

### Capability Matrix

| Capability | apollo | administrator | Notes |
|------------|--------|---------------|-------|
| `moderate_apollo_content` | ✅ | ✅ | Approve content |
| `edit_apollo_users` | ✅ | ✅ | Edit user data |
| `view_moderation_queue` | ✅ | ✅ | View pending items |
| `send_user_notifications` | ✅ | ✅ | Send notifications |
| `manage_apollo_mod_settings` | ❌ | ✅ | Admin only |
| `suspend_users` | ❌ | ✅ | Admin only |
| `block_users` | ❌ | ✅ | Admin only |

---

## Settings Structure

### apollo_mod_settings Option

```php
[
  'mods' => [1, 5, 12],  // Array of user IDs with apollo role
  'enabled_caps' => [
    'publish_events'      => false,  // Can approve events
    'publish_locals'      => false,  // Can approve venues
    'publish_djs'         => false,  // Can approve DJs
    'publish_nucleos'     => false,  // Can approve núcleos
    'publish_comunidades' => false,  // Can approve comunidades
    'edit_posts'          => true,   // Can approve social posts
    'edit_classifieds'    => true,   // Can approve classifieds
  ],
  'audit_log_enabled' => true,       // Enable audit logging
  'version' => '1.0.0'               // Settings version
]
```

### Post Type to Capability Mapping

| Post Type | Capability Key | Default |
|-----------|----------------|---------|
| `event_listing` | `publish_events` | false |
| `event_local` | `publish_locals` | false |
| `event_dj` | `publish_djs` | false |
| `apollo_nucleo` | `publish_nucleos` | false |
| `apollo_comunidade` | `publish_comunidades` | false |
| `apollo_social_post` | `edit_posts` | true |
| `apollo_classified` | `edit_classifieds` | true |

---

## REST API Endpoints

### Base URL

```
https://yoursite.com/wp-json/apollo/v1/
```

### Authentication

All endpoints require:
- **Header**: `X-WP-Nonce: <nonce_value>`
- **User**: Must be logged in
- **Capability**: Specific capability for each endpoint

Get nonce:
```javascript
const nonce = wpApiSettings.nonce; // In WP Admin
// or
const nonce = wp.create_nonce('wp_rest'); // Via wp.nonce
```

---

### 1. Approve Content

**Endpoint**: `POST /moderation/approve`

**Permission**: `moderate_apollo_content` + enabled capability for post type

**Request Body**:
```json
{
  "post_id": 123,
  "note": "Approved for quality content"
}
```

**Response** (200):
```json
{
  "success": true,
  "message": "Content approved and published successfully.",
  "post": {
    "id": 123,
    "title": "Amazing Event",
    "status": "publish",
    "link": "https://yoursite.com/events/amazing-event/"
  }
}
```

**Errors**:
- **401**: Not logged in
- **403**: Missing capability or capability disabled
- **404**: Post not found
- **400**: Post not in draft/pending status

**cURL Example**:
```bash
curl -X POST "https://yoursite.com/wp-json/apollo/v1/moderation/approve" \
  -H "X-WP-Nonce: abc123xyz" \
  -H "Content-Type: application/json" \
  -d '{
    "post_id": 123,
    "note": "Looks good"
  }'
```

---

### 2. Suspend User

**Endpoint**: `POST /users/suspend`

**Permission**: `suspend_users` (admin only)

**Request Body**:
```json
{
  "user_id": 45,
  "days": 7,
  "reason": "Violation of community guidelines"
}
```

**Response** (200):
```json
{
  "success": true,
  "message": "User suspended for 7 days.",
  "user": {
    "id": 45,
    "suspended_until": "2025-12-01 14:30:00",
    "reason": "Violation of community guidelines"
  }
}
```

**Errors**:
- **403**: Cannot suspend administrators or missing permission
- **404**: User not found

**cURL Example**:
```bash
curl -X POST "https://yoursite.com/wp-json/apollo/v1/users/suspend" \
  -H "X-WP-Nonce: abc123xyz" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 45,
    "days": 7,
    "reason": "Spam behavior"
  }'
```

---

### 3. Block User

**Endpoint**: `POST /users/block`

**Permission**: `block_users` (admin only)

**Request Body**:
```json
{
  "user_id": 67,
  "reason": "Severe ToS violation"
}
```

**Response** (200):
```json
{
  "success": true,
  "message": "User blocked successfully.",
  "user": {
    "id": 67,
    "reason": "Severe ToS violation"
  }
}
```

**Errors**:
- **403**: Cannot block administrators or missing permission
- **404**: User not found

**cURL Example**:
```bash
curl -X POST "https://yoursite.com/wp-json/apollo/v1/users/block" \
  -H "X-WP-Nonce: abc123xyz" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 67,
    "reason": "Repeated violations"
  }'
```

---

## Admin UI

### Access

**Menu Location**: WordPress Admin → Moderation (shield icon)

**Required Capability**: `view_moderation_queue`

### Tab 1: Settings

**Access**: Administrators only (`manage_apollo_mod_settings`)

**Features**:
- Select moderators (users with apollo/editor/admin roles)
- Toggle enabled capabilities (which content types apollos can approve)
- Enable/disable audit logging

**Screenshot Placeholder**: `[Admin > Moderation > Settings Tab]`

---

### Tab 2: Moderation Queue

**Access**: Any user with `view_moderation_queue`

**Features**:
- Lists all draft/pending posts for enabled content types
- Shows thumbnail, title, author, date
- **Approve** button (calls REST API)
- Filters by type (future enhancement)

**Acceptance Criteria**:
- ✅ Displays only content types enabled in settings
- ✅ Approve button disabled after click
- ✅ Row fades out after successful approval
- ✅ Error messages displayed in alert

**Screenshot Placeholder**: `[Admin > Moderation > Queue Tab]`

---

### Tab 3: Moderate Users

**Access**: Users with `edit_apollo_users`

**Features**:
- List of recent users with avatar, name, email, role, status
- Status indicators: Active, Suspended (orange), Blocked (red)
- Actions:
  - **Suspend** (admin only): Prompts for days and reason
  - **Block** (admin only): Prompts for reason with confirmation

**Acceptance Criteria**:
- ✅ Cannot suspend/block administrators
- ✅ Actions refresh page after success
- ✅ Status correctly reflects suspension/block state

**Screenshot Placeholder**: `[Admin > Moderation > Users Tab]`

---

## Authentication Filters

### Suspended User Check

**Filter**: `authenticate` (priority 30)

**Behavior**:
- Checks `_apollo_suspended_until` user meta
- If current time < suspended_until → return `WP_Error('apollo_user_suspended')`
- Automatically clears expired suspensions

**Error Message**:
```
Your account is suspended until 2025-12-01 14:30:00. 
Reason: Violation of community guidelines
```

---

### Blocked User Check

**Filter**: `authenticate` (priority 30)

**Behavior**:
- Checks `_apollo_blocked` user meta
- If blocked → return `WP_Error('apollo_user_blocked')`

**Error Message**:
```
Your account has been blocked. Reason: Severe ToS violation
```

---

### Active Session Check

**Action**: `init` (priority 1)

**Behavior**:
- Checks if currently logged-in user is suspended/blocked
- Logs them out immediately
- Redirects to login with error message

---

## Audit Logging

### Database Table: wp_apollo_mod_log

```sql
CREATE TABLE wp_apollo_mod_log (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  actor_id bigint(20) unsigned NOT NULL,
  actor_role varchar(50) NOT NULL,
  action varchar(50) NOT NULL,
  target_type varchar(50) NOT NULL,
  target_id bigint(20) unsigned NOT NULL,
  details longtext,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY actor_id_idx (actor_id),
  KEY action_idx (action),
  KEY target_type_idx (target_type),
  KEY target_id_idx (target_id),
  KEY created_at_idx (created_at)
);
```

### Logged Actions

| Action | Target Type | Target ID | Details |
|--------|-------------|-----------|---------|
| `approve_post` | post_type | post_id | `{note}` |
| `reject_post` | post_type | post_id | `{note}` |
| `suspend_user` | user | user_id | `{days, until, reason}` |
| `block_user` | user | user_id | `{reason}` |
| `unsuspend_user` | user | user_id | `{}` |
| `unblock_user` | user | user_id | `{}` |

### Helper Functions

```php
// Log an action
apollo_mod_log_action(
  $actor_id,    // User performing action
  $action,      // Action name
  $target_type, // Type of target
  $target_id,   // ID of target
  $details      // Associative array
);

// Get logs
$logs = apollo_get_mod_log([
  'actor_id'    => 1,
  'action'      => 'approve_post',
  'target_type' => 'event_listing',
  'target_id'   => 123,
  'limit'       => 50,
  'orderby'     => 'created_at',
  'order'       => 'DESC',
]);

// Cleanup old logs (90+ days)
apollo_cleanup_mod_log(90);
```

---

## WP-CLI Commands

### 1. Database Test

**Command**:
```bash
wp apollo db-test
```

**Tests**:
1. Database connectivity
2. `wp_apollo_mod_log` table exists
3. `apollo_mod_settings` option exists
4. `apollo` role and capabilities

**Exit Codes**:
- `0`: All tests passed
- `1`: One or more tests failed

**Example Output**:
```
=== Apollo Core Database Test ===

1. Testing database connectivity...
Success: Database connection OK

2. Checking apollo_mod_log table...
Success: Table wp_apollo_mod_log exists
  Rows: 142

3. Checking apollo_mod_settings option...
Success: apollo_mod_settings option exists
  Structure:
    - Moderators: 3
    - Enabled caps: 2
    - Audit log: enabled
    - Version: 1.0.0

4. Checking apollo role...
Success: apollo role exists
  Capabilities:
    - moderate_apollo_content: ✓
    - edit_apollo_users: ✓
    - view_moderation_queue: ✓
    - send_user_notifications: ✓

=== Test Summary ===
Success: All tests passed!
```

---

### 2. View Moderation Log

**Command**:
```bash
wp apollo mod-log [--limit=<number>] [--actor=<user_id>]
```

**Options**:
- `--limit`: Number of entries (default: 20)
- `--actor`: Filter by actor user ID

**Examples**:
```bash
# Recent 20 entries
wp apollo mod-log

# Last 100 entries
wp apollo mod-log --limit=100

# Actions by user ID 1
wp apollo mod-log --actor=1

# Last 50 actions by admin
wp apollo mod-log --limit=50 --actor=1
```

**Output**:
```
+----+---------------------+------------+---------------+--------------------+
| ID | Date                | Actor      | Action        | Target             |
+----+---------------------+------------+---------------+--------------------+
| 45 | 2025-11-24 10:30:00 | 1 (admin)  | approve_post  | event_listing:123  |
| 44 | 2025-11-24 09:15:00 | 2 (apollo) | suspend_user  | user:67            |
+----+---------------------+------------+---------------+--------------------+
```

---

## Testing

### PHPUnit Tests

**Location**: `tests/test-rest-moderation.php`

**Run Tests**:
```bash
cd apollo-core
vendor/bin/phpunit --filter Test_Apollo_REST_Moderation
```

**Test Coverage**:
1. ✅ Approve endpoint permission check
2. ✅ Approve success with moderator
3. ✅ Approve fails when capability disabled
4. ✅ Suspend user success
5. ✅ Suspend permission denied for non-admin
6. ✅ Block user success
7. ✅ Cannot suspend administrators
8. ✅ Audit log entries created

---

## Migration Guide

### From OUTDATED-* Folders

**Scenario**: Migrating from old `apollo-events-manager`, `apollo-social`, `apollo-rio` to unified `apollo-core`.

#### Step 1: Backup

```bash
# Backup database
wp db export backup-$(date +%Y%m%d).sql

# Backup wp-content
cp -r wp-content/plugins wp-content/plugins-backup
```

#### Step 2: Deactivate Old Plugins

```bash
wp plugin deactivate apollo-events-manager apollo-social apollo-rio
```

#### Step 3: Rename Old Plugins

```bash
mv wp-content/plugins/apollo-events-manager wp-content/plugins/OUTDATED-apollo-events-manager
mv wp-content/plugins/apollo-social wp-content/plugins/OUTDATED-apollo-social
mv wp-content/plugins/apollo-rio wp-content/plugins/OUTDATED-apollo-rio
```

#### Step 4: Activate Apollo Core

```bash
wp plugin activate apollo-core
```

This will:
- Create `apollo` role
- Create `apollo_mod_settings` option
- Create `wp_apollo_mod_log` table
- Add capabilities to administrator role

#### Step 5: Verify

```bash
# Check DB
wp apollo db-test

# Check roles
wp role list

# Check option
wp option get apollo_mod_settings
```

#### Step 6: Configure Settings

1. Go to **Admin → Moderation → Settings**
2. Select moderators
3. Enable content type capabilities
4. Save settings

#### Step 7: Test

1. Create a draft event
2. Go to **Moderation → Queue**
3. Approve the event
4. Verify in **Moderation Log**

#### Step 8: Clean Up (After Verification)

```bash
# Remove OUTDATED folders (only after full verification)
rm -rf wp-content/plugins/OUTDATED-*
```

---

## Security Checklist

### ✅ Before Deployment

- [ ] All REST endpoints use nonce verification (`X-WP-Nonce`)
- [ ] All endpoints check capabilities
- [ ] All inputs sanitized (`absint`, `sanitize_text_field`, etc.)
- [ ] All outputs escaped (`esc_html`, `esc_attr`, etc.)
- [ ] SQL queries use `$wpdb->prepare()` or `$wpdb->insert()`
- [ ] Cannot suspend/block administrators
- [ ] Audit log enabled by default
- [ ] No debug scripts exposed publicly
- [ ] All functions prefixed with `apollo_`
- [ ] No hardcoded credentials

### ✅ During Development

- [ ] Test with `WP_DEBUG = true`
- [ ] Test with different user roles
- [ ] Test permission denied scenarios
- [ ] Test with invalid inputs
- [ ] Test with non-existent resources
- [ ] Verify audit logs created
- [ ] Check for SQL injection vulnerabilities
- [ ] Check for XSS vulnerabilities

### ✅ Post-Deployment

- [ ] Monitor `debug.log` for errors
- [ ] Review audit log regularly
- [ ] Rotate DB credentials
- [ ] Remove temporary test accounts
- [ ] Schedule log cleanup (`apollo_cleanup_mod_log()`)

---

## Development

### VS Code + Intelephense + Copilot

**Workspace Settings** (`.vscode/settings.json`):

```json
{
  "intelephense.environment.phpVersion": "8.1.0",
  "intelephense.stubs": [
    "wordpress",
    "wordpress-globals",
    "wordpress-stubs"
  ],
  "php.validate.executablePath": "/usr/bin/php",
  "phpcs.standard": "WordPress",
  "editor.formatOnSave": true,
  "[php]": {
    "editor.defaultFormatter": "bmewburn.vscode-intelephense-client"
  }
}
```

### Copilot Prompts

**Add new moderation action**:
```
Add a new REST endpoint to Apollo Core moderation system:
- Endpoint: POST /moderation/reject
- Permission: moderate_apollo_content
- Behavior: Set post status to 'trash', log action
- Follow apollo_rest_approve_content pattern
```

**Extend capabilities**:
```
Add a new capability 'moderate_comments' to apollo role.
Update admin settings tab to include toggle.
Add REST endpoint to approve/reject comments.
```

**Add new audit action**:
```
Log all changes to apollo_mod_settings option.
Action: 'settings_updated'
Target: 'settings'
Details: changed fields
```

### File Structure

```
apollo-core/
├── apollo-core.php               # Main bootstrap
├── includes/
│   ├── settings-defaults.php     # Settings helpers
│   ├── roles.php                 # Role management
│   ├── db-schema.php             # Database & logging
│   ├── rest-moderation.php       # REST endpoints
│   └── auth-filters.php          # Authentication hooks
├── admin/
│   ├── moderation-page.php       # Admin UI
│   └── js/
│       └── moderation-admin.js   # Admin JavaScript
├── wp-cli/
│   └── commands.php              # WP-CLI commands
├── tests/
│   ├── test-activation.php       # Activation tests
│   └── test-rest-moderation.php  # REST API tests
└── README_MODERATION.md          # This file
```

---

## Troubleshooting

### Issue: "You do not have permission"

**Solution**: Check if:
1. User has `apollo` role or is administrator
2. Capability is enabled in **Settings → Enabled Capabilities**
3. User is logged in and nonce is valid

---

### Issue: Suspended users can still log in

**Solution**:
1. Check `_apollo_suspended_until` user meta exists
2. Verify `apollo_check_user_suspension` filter is registered
3. Clear user sessions: `wp user session destroy <user_id> --all`

---

### Issue: Audit log not recording

**Solution**:
1. Check `apollo_mod_settings.audit_log_enabled = true`
2. Verify table exists: `wp apollo db-test`
3. Check `wp-content/debug.log` for SQL errors

---

## Support

- **Documentation**: `apollo-core/README_MODERATION.md`
- **Issues**: GitHub Issues
- **Security**: Email security@apollo.rio.br

---

**Last Updated**: November 24, 2025  
**Apollo Core Version**: 3.0.0

