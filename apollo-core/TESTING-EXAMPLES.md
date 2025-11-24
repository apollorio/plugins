# Apollo Core - Testing Examples & API Reference

**Quick Reference for Developers**

---

## cURL Examples

### Get WordPress Nonce

```bash
# Method 1: Via WP-CLI
NONCE=$(wp eval 'echo wp_create_nonce("wp_rest");')
echo "Nonce: $NONCE"

# Method 2: Via JavaScript console (in WP Admin)
# Open browser console and run:
# wp.apiRequest.nonceMiddleware.nonce
```

### Get Cookie for Authentication

```bash
# Login and capture cookie
curl -c cookies.txt \
  -d "log=admin&pwd=password" \
  "http://yoursite.local/wp-login.php"

# Use cookie in subsequent requests
curl -b cookies.txt \
  -H "X-WP-Nonce: $NONCE" \
  "http://yoursite.local/wp-json/apollo/v1/moderation/approve"
```

---

## REST API Examples

### 1. Approve Content

**Scenario**: Approve a draft event

```bash
# Variables
SITE="http://yoursite.local"
POST_ID=123
NONCE="abc123xyz"

# Request
curl -X POST "$SITE/wp-json/apollo/v1/moderation/approve" \
  -H "X-WP-Nonce: $NONCE" \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "post_id": '$POST_ID',
    "note": "Content looks good, approved!"
  }' | jq .
```

**Success Response**:
```json
{
  "success": true,
  "message": "Content approved and published successfully.",
  "post": {
    "id": 123,
    "title": "Amazing Event",
    "status": "publish",
    "link": "http://yoursite.local/events/amazing-event/"
  }
}
```

**Error Response (403 - Capability Disabled)**:
```json
{
  "code": "capability_disabled",
  "message": "Publishing event_listing is not currently enabled.",
  "data": {
    "status": 403
  }
}
```

---

### 2. Suspend User

**Scenario**: Suspend user for 7 days

```bash
# Variables
USER_ID=45
DAYS=7
REASON="Violation of community guidelines"

# Request
curl -X POST "$SITE/wp-json/apollo/v1/users/suspend" \
  -H "X-WP-Nonce: $NONCE" \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "user_id": '$USER_ID',
    "days": '$DAYS',
    "reason": "'"$REASON"'"
  }' | jq .
```

**Success Response**:
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

**Error Response (403 - Cannot Suspend Admin)**:
```json
{
  "code": "cannot_suspend_admin",
  "message": "Cannot suspend an administrator.",
  "data": {
    "status": 403
  }
}
```

---

### 3. Block User

**Scenario**: Permanently block user

```bash
# Variables
USER_ID=67
REASON="Severe ToS violation"

# Request
curl -X POST "$SITE/wp-json/apollo/v1/users/block" \
  -H "X-WP-Nonce: $NONCE" \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "user_id": '$USER_ID',
    "reason": "'"$REASON"'"
  }' | jq .
```

**Success Response**:
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

---

## WP-CLI Examples

### Database Test

```bash
# Run full database connectivity and structure test
wp apollo db-test

# Example output:
# === Apollo Core Database Test ===
#
# 1. Testing database connectivity...
# Success: Database connection OK
#
# 2. Checking apollo_mod_log table...
# Success: Table wp_apollo_mod_log exists
#   Rows: 142
#
# 3. Checking apollo_mod_settings option...
# Success: apollo_mod_settings option exists
#
# 4. Checking apollo role...
# Success: apollo role exists
#
# === Test Summary ===
# Success: All tests passed!
```

---

### View Moderation Log

```bash
# Last 20 entries
wp apollo mod-log

# Last 100 entries
wp apollo mod-log --limit=100

# Actions by specific user
wp apollo mod-log --actor=1

# Combined filters
wp apollo mod-log --limit=50 --actor=1

# Example output:
# +----+---------------------+--------------+---------------+--------------------+
# | ID | Date                | Actor        | Action        | Target             |
# +----+---------------------+--------------+---------------+--------------------+
# | 45 | 2025-11-24 10:30:00 | 1 (admin)    | approve_post  | event_listing:123  |
# | 44 | 2025-11-24 09:15:00 | 2 (apollo)   | suspend_user  | user:67            |
# | 43 | 2025-11-23 16:45:00 | 1 (admin)    | block_user    | user:89            |
# +----+---------------------+--------------+---------------+--------------------+
```

---

## PHP Function Examples

### Settings Management

```php
// Get default settings
$defaults = apollo_get_default_mod_settings();
/* Returns:
[
  'mods' => [],
  'enabled_caps' => [
    'publish_events' => false,
    'publish_locals' => false,
    // ...
  ],
  'audit_log_enabled' => true,
  'version' => '1.0.0'
]
*/

// Get current settings with fallback
$settings = apollo_get_mod_settings();

// Update settings
apollo_update_mod_settings([
  'mods' => [1, 2, 5],
  'enabled_caps' => [
    'publish_events' => true,
    'publish_locals' => true,
  ],
  'audit_log_enabled' => true,
]);

// Check if capability enabled
if ( apollo_is_cap_enabled('publish_events') ) {
  // Apollo moderators can approve events
}
```

---

### Role Management

```php
// Check if user is moderator
$is_mod = apollo_user_is_moderator(); // Current user
$is_mod = apollo_user_is_moderator(123); // Specific user

// Assign apollo role
apollo_assign_moderator_role(123);

// Remove apollo role
apollo_remove_moderator_role(123);
```

---

### Audit Logging

```php
// Log a moderation action
apollo_mod_log_action(
  get_current_user_id(),  // Actor
  'approve_post',          // Action
  'event_listing',         // Target type
  123,                     // Target ID
  [                        // Details
    'note' => 'Looks good!',
    'previous_status' => 'draft'
  ]
);

// Get log entries
$logs = apollo_get_mod_log([
  'actor_id'    => 1,
  'action'      => 'approve_post',
  'target_type' => 'event_listing',
  'limit'       => 50,
  'orderby'     => 'created_at',
  'order'       => 'DESC',
]);

foreach ( $logs as $log ) {
  echo "{$log->created_at}: {$log->action} on {$log->target_type}:{$log->target_id}\n";
}

// Cleanup old logs (90+ days)
apollo_cleanup_mod_log(90);
```

---

### User Status Check

```php
// Check if user can perform actions
if ( apollo_user_can_perform_actions(123) ) {
  // User is not suspended or blocked
}

// Get detailed status
$status = apollo_get_user_status(123);
/* Returns:
[
  'is_blocked' => false,
  'is_suspended' => true,
  'suspended_until' => '2025-12-01 14:30:00',
  'block_reason' => null,
  'suspend_reason' => 'Spam behavior'
]
*/

if ( $status['is_suspended'] ) {
  echo "User suspended until " . $status['suspended_until'];
}
```

---

## JavaScript Examples

### Admin UI - Approve Post

```javascript
jQuery(document).on('click', '.apollo-approve-btn', function(e) {
  e.preventDefault();
  
  const postId = jQuery(this).data('post-id');
  const note = prompt('Add approval note (optional):');
  
  if (note === null) return; // User cancelled
  
  jQuery.ajax({
    url: apolloModerationAdmin.restUrl + 'moderation/approve',
    method: 'POST',
    beforeSend: function(xhr) {
      xhr.setRequestHeader('X-WP-Nonce', apolloModerationAdmin.nonce);
    },
    data: JSON.stringify({
      post_id: postId,
      note: note
    }),
    contentType: 'application/json',
    success: function(response) {
      if (response.success) {
        alert('Content approved!');
        location.reload();
      }
    },
    error: function(xhr) {
      alert(xhr.responseJSON.message || 'Failed to approve content.');
    }
  });
});
```

---

### Frontend - Check User Status

```javascript
// Example: Disable commenting for suspended users
fetch('/wp-json/apollo/v1/users/status?user_id=123', {
  headers: {
    'X-WP-Nonce': apolloNonce
  }
})
.then(res => res.json())
.then(data => {
  if (data.is_suspended || data.is_blocked) {
    document.getElementById('comment-form').style.display = 'none';
    document.getElementById('user-suspended-message').style.display = 'block';
  }
});
```

---

## PHPUnit Test Examples

### Run Tests

```bash
# All tests
cd apollo-core
vendor/bin/phpunit

# Specific test class
vendor/bin/phpunit --filter Test_Apollo_REST_Moderation

# Specific test method
vendor/bin/phpunit --filter test_approve_success

# With coverage
vendor/bin/phpunit --coverage-html coverage/
```

---

### Writing New Tests

```php
<?php
class Test_Apollo_Custom_Feature extends WP_UnitTestCase {
  
  public function test_custom_moderation_action() {
    // Create test user with apollo role
    $mod_id = $this->factory->user->create(['role' => 'apollo']);
    wp_set_current_user($mod_id);
    
    // Create draft post
    $post_id = $this->factory->post->create([
      'post_type' => 'event_listing',
      'post_status' => 'draft'
    ]);
    
    // Enable capability
    $settings = apollo_get_default_mod_settings();
    $settings['enabled_caps']['publish_events'] = true;
    update_option('apollo_mod_settings', $settings);
    
    // Make REST request
    $request = new WP_REST_Request('POST', '/apollo/v1/moderation/approve');
    $request->set_param('post_id', $post_id);
    $request->set_param('note', 'Test approval');
    
    $response = rest_do_request($request);
    $data = $response->get_data();
    
    // Assertions
    $this->assertEquals(200, $response->get_status());
    $this->assertTrue($data['success']);
    
    // Check post status changed
    $post = get_post($post_id);
    $this->assertEquals('publish', $post->post_status);
    
    // Check audit log
    $logs = apollo_get_mod_log(['target_id' => $post_id, 'limit' => 1]);
    $this->assertNotEmpty($logs);
    $this->assertEquals('approve_post', $logs[0]->action);
  }
}
```

---

## Database Queries

### Direct SQL Examples (for debugging)

```sql
-- View recent audit log
SELECT * FROM wp_apollo_mod_log 
ORDER BY created_at DESC 
LIMIT 20;

-- Count actions by actor
SELECT actor_id, actor_role, COUNT(*) as action_count
FROM wp_apollo_mod_log
GROUP BY actor_id, actor_role
ORDER BY action_count DESC;

-- Actions in last 24 hours
SELECT *
FROM wp_apollo_mod_log
WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- Suspended users
SELECT u.ID, u.user_login, um.meta_value as suspended_until
FROM wp_users u
JOIN wp_usermeta um ON u.ID = um.user_id
WHERE um.meta_key = '_apollo_suspended_until'
  AND CAST(um.meta_value AS UNSIGNED) > UNIX_TIMESTAMP();

-- Blocked users
SELECT u.ID, u.user_login, um2.meta_value as reason
FROM wp_users u
JOIN wp_usermeta um ON u.ID = um.user_id
LEFT JOIN wp_usermeta um2 ON u.ID = um2.user_id AND um2.meta_key = '_apollo_block_reason'
WHERE um.meta_key = '_apollo_blocked' 
  AND um.meta_value = '1';
```

---

## Troubleshooting Commands

### Reset Moderation Settings

```bash
# Reset to defaults
wp option delete apollo_mod_settings
wp plugin deactivate apollo-core && wp plugin activate apollo-core

# Or update specific setting
wp option patch update apollo_mod_settings audit_log_enabled true
```

---

### Unsuspend User

```bash
# Via WP-CLI
wp user meta delete <user_id> _apollo_suspended_until
wp user meta delete <user_id> _apollo_suspension_reason

# Or via SQL
# UPDATE wp_usermeta 
# SET meta_value = '0' 
# WHERE user_id = <user_id> 
#   AND meta_key = '_apollo_suspended_until';
```

---

### Unblock User

```bash
# Via WP-CLI
wp user meta delete <user_id> _apollo_blocked
wp user meta delete <user_id> _apollo_block_reason
```

---

### Clear Audit Log

```bash
# Delete entries older than 30 days
wp eval 'apollo_cleanup_mod_log(30);'

# Delete all entries (CAUTION!)
wp eval '
  global $wpdb;
  $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}apollo_mod_log");
'
```

---

### Check Permissions

```bash
# Check user capabilities
wp user get 123 --field=caps

# Check role capabilities
wp cap list apollo

# Add capability to role
wp cap add apollo custom_capability

# Remove capability
wp cap remove apollo custom_capability
```

---

## Development Workflow

### 1. Create New Feature Branch

```bash
git checkout -b feature/add-reject-endpoint
```

---

### 2. Add New REST Endpoint

**Edit**: `apollo-core/includes/rest-moderation.php`

```php
// Add after existing endpoints
register_rest_route(
  'apollo/v1',
  '/moderation/reject',
  array(
    'methods'             => WP_REST_Server::CREATABLE,
    'callback'            => 'apollo_rest_reject_content',
    'permission_callback' => 'apollo_rest_can_moderate',
    'args'                => array(
      'post_id' => array(
        'required'          => true,
        'validate_callback' => function( $param ) {
          return is_numeric( $param );
        },
        'sanitize_callback' => 'absint',
      ),
      'reason'  => array(
        'required'          => true,
        'sanitize_callback' => 'sanitize_textarea_field',
      ),
    ),
  )
);

function apollo_rest_reject_content( $request ) {
  $post_id = $request->get_param( 'post_id' );
  $reason  = $request->get_param( 'reason' );
  
  // Verify post exists
  $post = get_post( $post_id );
  if ( ! $post ) {
    return new WP_Error(
      'post_not_found',
      __( 'Post not found.', 'apollo-core' ),
      array( 'status' => 404 )
    );
  }
  
  // Set to trash
  wp_trash_post( $post_id );
  
  // Log action
  apollo_mod_log_action(
    get_current_user_id(),
    'reject_post',
    $post->post_type,
    $post_id,
    array( 'reason' => $reason )
  );
  
  return new WP_REST_Response(
    array(
      'success' => true,
      'message' => __( 'Content rejected and moved to trash.', 'apollo-core' ),
    ),
    200
  );
}
```

---

### 3. Add Test

**Edit**: `apollo-core/tests/test-rest-moderation.php`

```php
public function test_reject_content() {
  wp_set_current_user( $this->moderator_id );
  
  $request = new WP_REST_Request( 'POST', '/apollo/v1/moderation/reject' );
  $request->set_param( 'post_id', $this->post_id );
  $request->set_param( 'reason', 'Low quality' );
  
  $response = rest_do_request( $request );
  $data = $response->get_data();
  
  $this->assertEquals( 200, $response->get_status() );
  $this->assertTrue( $data['success'] );
  
  // Check post trashed
  $post = get_post( $this->post_id );
  $this->assertEquals( 'trash', $post->post_status );
}
```

---

### 4. Run Tests

```bash
vendor/bin/phpunit --filter test_reject_content
```

---

### 5. Update Documentation

**Edit**: `README_MODERATION.md` - Add section for new endpoint

---

### 6. Commit & Push

```bash
git add includes/rest-moderation.php tests/test-rest-moderation.php README_MODERATION.md
git commit -m "feat: add reject content endpoint"
git push origin feature/add-reject-endpoint
```

---

## Quick Reference Card

### Files to Edit

| Feature | File |
|---------|------|
| New REST endpoint | `includes/rest-moderation.php` |
| New capability | `includes/roles.php` |
| New setting | `includes/settings-defaults.php` |
| New admin page | `admin/moderation-page.php` |
| New WP-CLI command | `wp-cli/commands.php` |
| New test | `tests/test-*.php` |

### Helper Functions

| Function | Purpose |
|----------|---------|
| `apollo_get_mod_settings()` | Get settings |
| `apollo_is_cap_enabled($cap)` | Check if capability enabled |
| `apollo_mod_log_action()` | Log action |
| `apollo_user_is_moderator()` | Check moderator role |
| `apollo_get_user_status()` | Get suspension/block status |

---

**Last Updated**: November 24, 2025  
**Apollo Core Version**: 3.0.0

