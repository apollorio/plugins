# Apollo Core - Forms System Documentation

**Version**: 3.1.0  
**Last Updated**: November 24, 2025

---

## Table of Contents

1. [Overview](#overview)
2. [Instagram User ID Feature](#instagram-user-id-feature)
3. [Admin UI - Form Builder](#admin-ui---form-builder)
4. [Form Rendering](#form-rendering)
5. [REST API Endpoints](#rest-api-endpoints)
6. [Testing](#testing)
7. [Migration Guide](#migration-guide)
8. [Developer Guide](#developer-guide)

---

## Overview

The Apollo Core Forms System provides a configurable form builder with:

- **Instagram User ID** field on registration and profile
- **Admin UI** for managing form schemas per form type
- **Dynamic form rendering** based on schemas
- **REST API** for form submissions with validation
- **Four form types**: `new_user`, `cpt_event`, `cpt_local`, `cpt_dj`

### Key Features

✅ Add custom fields via admin UI  
✅ Drag-and-drop field ordering  
✅ Real-time form preview  
✅ Server and client-side validation  
✅ Instagram ID with uniqueness validation  
✅ Auto-binding to user registration and CPT creation  
✅ Export/Import schemas as JSON  
✅ Audit logging of schema changes  

---

## Instagram User ID Feature

### User Registration

**Field**: `instagram_user_id`  
**Meta Key**: `_apollo_instagram_id`  
**Validation**: Alphanumeric + underscores, max 30 chars  
**Uniqueness**: Checked automatically  

### Frontend Display

```php
<?php
// Display Instagram link on user page
$instagram_html = apollo_display_user_instagram( $user_id );
echo $instagram_html;

// Output: <a href="https://instagram.com/username" target="_blank" rel="noopener" class="apollo-instagram-link">@username</a>
```

### Admin Profile

Instagram field automatically added to user profile edit screens:
- **Location**: User Profile → Apollo Social → Instagram ID
- **Format**: `@username` prefix shown
- **Validation**: Real-time, prevents invalid characters

### Hooks

```php
// Save Instagram ID on registration
add_action( 'user_register', 'apollo_save_user_instagram_on_register' );

// Save Instagram ID on profile update
add_action( 'profile_update', 'apollo_save_user_instagram_on_profile_update' );
```

### Retrieving Instagram ID

```php
// Get user's Instagram ID
$instagram_id = get_user_meta( $user_id, '_apollo_instagram_id', true );

// Get for post/event
$instagram_id = get_post_meta( $post_id, '_apollo_instagram_id', true );
```

---

## Admin UI - Form Builder

### Access

**Menu Location**: Apollo → Formulários  
**Capability Required**: `manage_options`  
**URL**: `wp-admin/admin.php?page=apollo-forms`

### Form Types

| Form Type | Description | Post Type |
|-----------|-------------|-----------|
| `new_user` | User registration form | N/A (creates WP_User) |
| `cpt_event` | Event submission form | `event_listing` |
| `cpt_local` | Venue submission form | `event_local` |
| `cpt_dj` | DJ profile submission form | `event_dj` |

### Field Properties

| Property | Type | Description |
|----------|------|-------------|
| **Field Key** | string | Machine name (lowercase, underscores) |
| **Label** | string | Human-readable label |
| **Type** | select | Field type (see below) |
| **Required** | boolean | Is field required? |
| **Visible** | boolean | Show field in form? |
| **Default Value** | string | Default/placeholder value |
| **Validation** | regex | Validation regex pattern |
| **Order** | number | Display order (drag to reorder) |

### Field Types

| Type | HTML Input | Validation |
|------|------------|------------|
| `text` | `<input type="text">` | Custom regex |
| `textarea` | `<textarea>` | - |
| `number` | `<input type="number">` | Numeric |
| `email` | `<input type="email">` | Email format |
| `select` | `<select>` | - |
| `checkbox` | `<input type="checkbox">` | Boolean |
| `date` | `<input type="date">` | Date format |
| `instagram` | `<input type="text">` with `@` prefix | `/^[A-Za-z0-9_]{1,30}$/` |
| `password` | `<input type="password">` | - |

### UI Actions

#### Add Field
1. Click **"Add Field"** button
2. Fill in field properties in modal
3. Click **"Save Field"**
4. Field appears in table and preview

#### Edit Field
1. Click **"Edit"** button in row
2. Modify properties in modal
3. Click **"Save Field"**
4. Changes reflected immediately

#### Reorder Fields
1. Drag field rows by handle icon (☰)
2. Drop in new position
3. Click **"Save Changes"** to persist

#### Duplicate Field
1. Click **"Duplicate"** button
2. Field copied with `_copy` suffix
3. Edit to customize

#### Delete Field
1. Click **"Delete"** button
2. Confirm deletion
3. Field removed from schema

#### Save Schema
- Click **"Save Changes"** to persist to database
- Schema versioned and audit logged

#### Revert Changes
- Click **"Revert"** to reload page
- Discards unsaved changes

#### Export Schema
- Click **"Export JSON"** to download schema as JSON file
- Can be imported later (feature to be implemented)

### Schema Storage

Schemas stored in WordPress options table:

```php
// Option name
'apollo_form_schemas'

// Structure
[
  'new_user' => [
    [
      'key' => 'field_name',
      'label' => 'Field Label',
      'type' => 'text',
      'required' => true,
      'visible' => true,
      'default' => '',
      'validation' => '/^[a-z]+$/',
      'order' => 1
    ],
    // ... more fields
  ],
  'cpt_event' => [ /* ... */ ],
  'cpt_local' => [ /* ... */ ],
  'cpt_dj' => [ /* ... */ ]
]
```

### Preview Pane

Right sidebar shows live preview of form:
- Updates automatically when fields changed
- Shows field types, labels, required marks
- Matches frontend rendering

---

## Form Rendering

### PHP Function

```php
<?php
// Render form
$form_html = apollo_render_form( 
  'new_user', 
  array(
    'action'    => '/submit-url/',
    'method'    => 'post',
    'css_class' => 'my-custom-form',
    'id'        => 'registration-form',
    'values'    => array( // Pre-fill values
      'user_login' => 'defaultuser',
    )
  )
);

echo $form_html;
```

### Shortcode

```
[apollo_form type="new_user"]
```

### Template Usage

```php
<!-- registration-page.php -->
<div class="apollo-registration">
  <h2>Create Account</h2>
  <?php echo apollo_render_form( 'new_user', array( 'action' => '' ) ); ?>
</div>
```

### Auto-Binding

Forms automatically bound to:

**User Registration**:
- Hook: `register_form` (WordPress default registration)
- Fields from `new_user` schema added automatically

**CPT Creation**:
- Front-end submission forms use schema
- Admin edit screens show meta boxes with schema fields

---

## REST API Endpoints

### Base URL

```
https://yoursite.com/wp-json/apollo/v1/
```

### Authentication

All endpoints require nonce in header:

```bash
X-WP-Nonce: <nonce_value>
```

Get nonce:
```php
$nonce = wp_create_nonce( 'wp_rest' );
```

---

### 1. Get Form Schema

**Endpoint**: `GET /forms/schema`

**Parameters**:
- `form_type` (required): Form type (new_user, cpt_event, cpt_local, cpt_dj)

**Response** (200):
```json
{
  "form_type": "new_user",
  "schema": [
    {
      "key": "user_login",
      "label": "Username",
      "type": "text",
      "required": true,
      "visible": true,
      "default": "",
      "validation": "/^[a-z0-9_-]{3,15}$/i"
    },
    {
      "key": "instagram_user_id",
      "label": "Instagram ID",
      "type": "instagram",
      "required": false,
      "visible": true,
      "default": "",
      "validation": "/^[A-Za-z0-9_]{1,30}$/"
    }
  ]
}
```

**cURL Example**:
```bash
curl -X GET "https://yoursite.com/wp-json/apollo/v1/forms/schema?form_type=new_user"
```

---

### 2. Submit Form

**Endpoint**: `POST /forms/submit`

**Headers**:
- `X-WP-Nonce`: Required nonce
- `Content-Type: application/json`

**Body**:
```json
{
  "form_type": "new_user",
  "data": {
    "user_login": "newuser",
    "user_email": "newuser@example.com",
    "user_pass": "SecurePassword123",
    "instagram_user_id": "my_instagram"
  }
}
```

**Success Response** (200):
```json
{
  "success": true,
  "message": "Form submitted successfully.",
  "data": {
    "user_id": 123,
    "user_login": "newuser"
  }
}
```

**Validation Error Response** (400):
```json
{
  "success": false,
  "errors": {
    "user_email": "Invalid email address.",
    "instagram_user_id": "Invalid Instagram username. Only letters, numbers, and underscores allowed (max 30 characters)."
  }
}
```

**cURL Example**:
```bash
# Get nonce first (via browser or WP-CLI)
NONCE=$(curl -s -b cookies.txt "https://yoursite.com/wp-admin/admin-ajax.php?action=rest-nonce")

# Submit form
curl -X POST "https://yoursite.com/wp-json/apollo/v1/forms/submit" \
  -H "X-WP-Nonce: $NONCE" \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "form_type": "new_user",
    "data": {
      "user_login": "testuser",
      "user_email": "test@example.com",
      "user_pass": "password123",
      "instagram_user_id": "test_instagram"
    }
  }' | jq .
```

---

### 3. Submit CPT Form

**Endpoint**: `POST /forms/submit`

**Body**:
```json
{
  "form_type": "cpt_event",
  "data": {
    "post_title": "Amazing Event",
    "post_content": "Event description here",
    "_event_start_date": "2025-12-01",
    "instagram_user_id": "event_instagram"
  }
}
```

**Success Response** (200):
```json
{
  "success": true,
  "message": "Form submitted successfully.",
  "data": {
    "post_id": 456,
    "post_type": "event_listing",
    "status": "draft"
  }
}
```

**Note**: CPT submissions create posts as `draft` status for moderation approval.

---

## Testing

### Run PHPUnit Tests

```bash
# All forms tests
cd apollo-core
vendor/bin/phpunit --filter Test_Apollo_Form

# Specific test classes
vendor/bin/phpunit tests/test-form-schema.php
vendor/bin/phpunit tests/test-registration-instagram.php
vendor/bin/phpunit tests/test-rest-forms.php
```

### Test Coverage

**test-form-schema.php** (8 tests):
- ✅ Get default schema
- ✅ Save and retrieve schema
- ✅ Schema validation
- ✅ Field value validation
- ✅ Instagram ID uniqueness
- ✅ Schema initialization
- ✅ Email validation
- ✅ Instagram format validation

**test-registration-instagram.php** (8 tests):
- ✅ Instagram saved on registration
- ✅ Invalid Instagram not saved
- ✅ Instagram updated on profile update
- ✅ Duplicate Instagram rejected
- ✅ Instagram display function
- ✅ Empty Instagram display
- ✅ Instagram format validation

**test-rest-forms.php** (8 tests):
- ✅ Get schema endpoint
- ✅ Submit with validation error
- ✅ Submit success
- ✅ Submit without nonce (403)
- ✅ Submit CPT form
- ✅ Invalid form type
- ✅ Email field validation
- ✅ Instagram field validation

**Total**: 24 tests

### Manual Testing

#### Test User Registration with Instagram

1. Go to WordPress registration page
2. Fill in username, email, password
3. Enter Instagram ID (e.g., `testuser123`)
4. Submit form
5. **Verify**:
   - User created
   - `_apollo_instagram_id` meta saved
   - Instagram displayed on user profile

#### Test Admin Form Builder

1. Go to Admin → Apollo → Formulários
2. Select "New User Registration"
3. Click "Add Field"
4. Create test field:
   - Key: `test_field`
   - Label: `Test Field`
   - Type: `text`
   - Required: Yes
5. Click "Save Field"
6. **Verify**:
   - Field appears in table
   - Field appears in preview
7. Click "Save Changes"
8. Reload page
9. **Verify**: Field persisted

#### Test REST API

```bash
# 1. Login and get cookies
curl -c cookies.txt -d "log=admin&pwd=password" "https://yoursite.com/wp-login.php"

# 2. Get nonce
NONCE=$(wp eval 'echo wp_create_nonce("wp_rest");')

# 3. Test get schema
curl -X GET "https://yoursite.com/wp-json/apollo/v1/forms/schema?form_type=new_user" | jq .

# 4. Test submit form
curl -X POST "https://yoursite.com/wp-json/apollo/v1/forms/submit" \
  -H "X-WP-Nonce: $NONCE" \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{"form_type":"new_user","data":{"user_login":"apitest","user_email":"api@test.com","user_pass":"pass123","instagram_user_id":"apitest_ig"}}' | jq .

# 5. Verify user created
wp user get apitest
wp user meta get $(wp user get apitest --field=ID) _apollo_instagram_id
```

---

## Migration Guide

### From Older Versions

If you have existing form data, the system will:

1. **Auto-initialize** default schemas on first admin load
2. **Version schemas** for future migrations
3. **Preserve** custom fields added manually

### Migration Steps

```bash
# 1. Backup database
wp db export backup-forms-$(date +%Y%m%d).sql

# 2. Activate/update plugin
wp plugin activate apollo-core

# 3. Initialize schemas (happens automatically)
# Or manually:
wp eval 'apollo_init_form_schemas();'

# 4. Verify schemas created
wp option get apollo_form_schemas --format=json | jq .

# 5. Check version
wp option get apollo_form_schema_version
# Expected: 1.0.0
```

### Idempotent Migration

Migration function `apollo_migrate_form_schema()` is idempotent:
- Safe to run multiple times
- Checks current version before migrating
- Skips if already at latest version

```php
// Run migration
apollo_migrate_form_schema();
```

---

## Developer Guide

### VS Code + Intelephense + Copilot

**Workspace Settings** (`.vscode/settings.json`):

```json
{
  "intelephense.environment.phpVersion": "8.1.0",
  "intelephense.stubs": [
    "wordpress",
    "wordpress-globals"
  ],
  "editor.formatOnSave": true,
  "[php]": {
    "editor.defaultFormatter": "bmewburn.vscode-intelephense-client"
  }
}
```

**Copilot Prompts**:

```
// Add new field type to Apollo Forms
// Support: video_url field type with YouTube/Vimeo validation
// Update: schema-manager.php, render.php, admin UI
```

```
// Add conditional field visibility
// If field X has value Y, show field Z
// Use: data attributes and JavaScript
```

### Helper Functions

```php
// Get schema
$schema = apollo_get_form_schema( 'new_user' );

// Save schema
apollo_save_form_schema( 'new_user', $schema );

// Validate schema
$is_valid = apollo_validate_form_schema( $schema );

// Validate field value
$validation = apollo_validate_field_value( $value, $field_schema );
if ( is_wp_error( $validation ) ) {
  echo $validation->get_error_message();
}

// Check Instagram uniqueness
$is_unique = apollo_is_instagram_id_unique( 'username', $exclude_user_id );

// Display Instagram
echo apollo_display_user_instagram( $user_id );

// Render form
echo apollo_render_form( 'new_user', array( 'action' => '' ) );
```

### Extending Field Types

To add a new field type:

1. **Add to valid types** in `schema-manager.php`:
```php
$valid_types = array( 'text', 'textarea', 'number', 'email', 'select', 'checkbox', 'date', 'instagram', 'password', 'your_new_type' );
```

2. **Add rendering** in `render.php`:
```php
case 'your_new_type':
  ?>
  <input 
    type="text" 
    id="apollo-field-<?php echo esc_attr( $field['key'] ); ?>" 
    name="<?php echo esc_attr( $field['key'] ); ?>" 
    class="apollo-input apollo-your-type"
    data-custom-attr="value"
  >
  <?php
  break;
```

3. **Add validation** in `schema-manager.php`:
```php
case 'your_new_type':
  if ( ! your_custom_validation( $value ) ) {
    return new WP_Error( 'invalid_type', __( 'Invalid format.', 'apollo-core' ) );
  }
  break;
```

4. **Update admin UI** in `forms-admin.php`:
```php
<option value="your_new_type"><?php esc_html_e( 'Your Type', 'apollo-core' ); ?></option>
```

### Mapping Fields to Meta

**Default Behavior**:
- `new_user` form → fields save to `user_meta`
- CPT forms → fields starting with `_` save to `post_meta`
- Core WP fields (`post_title`, `post_content`, `user_login`, `user_email`) handled automatically

**Custom Mapping**:

```php
// Hook into form processing
add_filter( 'apollo_process_cpt_form_data', 'my_custom_field_mapping', 10, 3 );

function my_custom_field_mapping( $result, $form_type, $data ) {
  // Custom logic
  if ( isset( $data['custom_field'] ) ) {
    update_post_meta( $result['post_id'], '_my_custom_meta', $data['custom_field'] );
  }
  return $result;
}
```

---

## Troubleshooting

### Issue: Instagram ID not saving

**Solution**:
1. Check format matches `/^[A-Za-z0-9_]{1,30}$/`
2. Verify uniqueness (no other user has same ID)
3. Check debug.log for errors

### Issue: Form not rendering

**Solution**:
1. Verify schema exists: `wp option get apollo_form_schemas --format=json`
2. Check form type is valid
3. Ensure schema has at least one visible field

### Issue: REST endpoint 403 error

**Solution**:
1. Verify nonce is valid
2. Check user is logged in (for protected endpoints)
3. Ensure nonce matches current session

### Issue: Schema changes not saving

**Solution**:
1. Check user has `manage_options` capability
2. Verify nonce in AJAX request
3. Check browser console for JavaScript errors
4. Check `wp-content/debug.log`

---

## Files Created

```
apollo-core/
├── includes/forms/
│   ├── schema-manager.php   (300 lines)
│   ├── render.php           (400 lines)
│   └── rest.php             (300 lines)
├── admin/
│   ├── forms-admin.php      (500 lines)
│   ├── js/forms-admin.js    (400 lines)
│   └── css/forms-admin.css  (250 lines)
├── tests/
│   ├── test-form-schema.php           (200 lines)
│   ├── test-registration-instagram.php (200 lines)
│   └── test-rest-forms.php            (250 lines)
└── FORMS-SYSTEM-README.md   (this file)
```

**Total**: 2,800+ lines of code

---

## Security

✅ **Input Sanitization**:
- `sanitize_text_field()` for text
- `sanitize_email()` for emails
- `absint()` for numbers
- `wp_kses_post()` for HTML content

✅ **Output Escaping**:
- `esc_html()` for text
- `esc_attr()` for attributes
- `esc_url()` for URLs
- `wp_kses_post()` for allowed HTML

✅ **Nonce Verification**:
- Admin forms: `check_admin_referer()`
- AJAX: `check_ajax_referer()`
- REST: `wp_verify_nonce()` in header

✅ **Capability Checks**:
- Admin UI: `manage_options`
- REST API: Per-endpoint permissions

✅ **SQL Injection Prevention**:
- Use WordPress functions (`update_user_meta`, `update_post_meta`)
- Never direct SQL queries with user input

---

## Support

- **Documentation**: This file
- **Issues**: GitHub Issues
- **Development**: See DEVELOPMENT.md

---

**Last Updated**: November 24, 2025  
**Apollo Core Version**: 3.1.0

