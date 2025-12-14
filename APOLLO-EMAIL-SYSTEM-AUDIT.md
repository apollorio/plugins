# Apollo Email System - Complete Audit & Refactor Plan

## PHASE 1: EMAIL INVENTORY

### 1.1 Main Email Classes & Functions

#### `apollo-core/includes/class-apollo-email-integration.php`
- **Class**: `Apollo_Email_Integration`
- **Purpose**: Central email integration hub
- **Status**: ✅ Active, needs WooCommerce removal
- **Features**:
  - Connects Newsletter + Email Templates plugins
  - Handles 18+ email flows (membership, moderation, documents, events, social)
  - Rate limiting (1000/day)
  - AJAX test email sender
- **WooCommerce Dependencies**: 
  - Line 60: Checks for `Mailtpl_Woomail_Composer` (WooCommerce email composer)
  - Line 99: Filter `emailtpl/placeholders` (may have WooCommerce placeholders)

#### `apollo-social/src/Admin/EmailHubAdmin.php`
- **Class**: `EmailHubAdmin`
- **Purpose**: Unified email management admin UI
- **Status**: ✅ Active, comprehensive
- **Features**:
  - Template configuration
  - Placeholder system
  - Test email functionality
  - Preview functionality
- **WooCommerce Dependencies**: None found

#### `apollo-email-templates/email-templates.php`
- **Class**: `Mailtpl_Woomail_Composer`
- **Purpose**: Email template system (originally for WooCommerce)
- **Status**: ⚠️ **HEAVILY WOOCOMMERCE-DEPENDENT**
- **WooCommerce Dependencies**:
  - Line 84: `mailtpl_woomail_is_woo_active()` check
  - Line 85: Admin notice if WooCommerce not active
  - Entire `/templates/woo/` directory (27 WooCommerce email templates)
  - Classes: `class-mailtpl-woomail-settings.php`, `class-mailtpl-woomail-customizer.php`

### 1.2 Direct `wp_mail()` Calls

1. **`apollo-core/modules/moderation/includes/class-rest-api.php`** (Line 407)
   - Flow: User notification from moderation
   - No WooCommerce dependency ✅

2. **`apollo-events-manager/includes/public-event-form.php`** (Line 422)
   - Flow: Admin notification on new event submission
   - No WooCommerce dependency ✅

3. **`apollo-events-manager/modules/rest-api/includes/aprio-rest-matchmaking-*.php`** (Multiple)
   - Flow: Meeting request emails
   - No WooCommerce dependency ✅

### 1.3 Email Flows Identified

#### Core Flows (via `Apollo_Email_Integration`):
1. `apollo_membership_approved` - Membership approved
2. `apollo_membership_rejected` - Membership rejected
3. `apollo_user_suspended` - User suspended
4. `apollo_user_blocked` - User blocked
5. `apollo_content_approved` - Content approved
6. `apollo_content_rejected` - Content rejected

#### Social Flows:
7. `apollo_document_finalized` - Document finalized
8. `apollo_document_signed` - Document signed
9. `apollo_group_invite` - Group invite
10. `apollo_group_approved` - Group approved
11. `apollo_social_post_mention` - Social mention

#### Events Flows:
12. `publish_event_listing` - Event published
13. `apollo_cena_rio_event_confirmed` - CENA-RIO confirmed
14. `apollo_cena_rio_event_approved` - CENA-RIO approved
15. `apollo_cena_rio_event_rejected` - CENA-RIO rejected
16. `apollo_event_reminder` - Event reminder (24h before)

#### User Journey Flows:
17. `apollo_user_registration_complete` - Registration welcome
18. `apollo_user_verification_complete` - Verification done
19. `apollo_user_onboarding_complete` - Onboarding done

### 1.4 Template Storage

- **Current**: Options API (`apollo_email_templates` option)
- **Email Templates Plugin**: Uses WordPress Customizer (WooCommerce-specific)
- **Newsletter Plugin**: Has its own template system

## PHASE 2: WOOCOMMERCE CLEANUP

### 2.1 Files to Remove/Neutralize

#### HIGH PRIORITY (Remove WooCommerce Dependencies):

1. **`apollo-email-templates/templates/woo/`** (Entire directory)
   - 27 WooCommerce-specific email templates
   - **Action**: Move to `/templates/woo/ARCHIVED/` or delete
   - **Impact**: None if WooCommerce not used

2. **`apollo-email-templates/includes/class-mailtpl-woomail-*.php`**
   - WooCommerce-specific classes
   - **Action**: Add conditional loading (only if WooCommerce active)
   - **Impact**: Low - Apollo doesn't use WooCommerce

3. **`apollo-email-templates/email-templates.php`** (Lines 82-100)
   - WooCommerce activation check
   - **Action**: Make optional, don't require WooCommerce
   - **Impact**: Medium - Plugin won't show error if WooCommerce missing

#### MEDIUM PRIORITY (Neutralize References):

4. **`apollo-core/includes/class-apollo-email-integration.php`** (Line 60)
   - Checks for `Mailtpl_Woomail_Composer`
   - **Action**: Keep check but don't require WooCommerce
   - **Impact**: Low - Already optional

### 2.2 WooCommerce References Found

| File | Line | Reference | Action |
|------|------|-----------|--------|
| `apollo-email-templates/email-templates.php` | 84 | `mailtpl_woomail_is_woo_active()` | Make optional |
| `apollo-email-templates/email-templates.php` | 85 | Admin notice requiring WooCommerce | Remove/neutralize |
| `apollo-email-templates/templates/woo/*` | All | 27 WooCommerce templates | Archive/remove |
| `apollo-core/includes/class-apollo-email-integration.php` | 60 | `Mailtpl_Woomail_Composer` check | Keep (optional) |

## PHASE 3: CENTRALIZED EMAIL SERVICE DESIGN

### 3.1 New Service Class Structure

**File**: `apollo-core/includes/class-apollo-email-service.php`

```php
class Apollo_Email_Service {
    // Core sending method
    public function send( $message_data ): bool|WP_Error
    
    // Template loading
    public function load_template( $template_slug, $variables = [] ): string
    
    // Flow mapping
    public function get_flow_config( $flow_slug ): array
    
    // Test sending
    public function send_test( $flow_slug, $test_email ): bool|WP_Error
}
```

### 3.2 Message Data Structure

```php
array(
    'to' => 'user@example.com',
    'cc' => [],
    'bcc' => [],
    'subject' => 'Subject with {{variables}}',
    'body_html' => '<html>...</html>',
    'body_text' => 'Plain text version',
    'headers' => [],
    'attachments' => [],
    'flow' => 'registration_confirm',
    'variables' => ['user_name' => 'João', ...]
)
```

## PHASE 4: TEMPLATE SYSTEM

### 4.1 Custom Post Type: `apollo_email_template`

- **Slug**: `apollo_email_template`
- **Fields**:
  - Title (template name)
  - Content (HTML template with placeholders)
  - Meta: `template_slug`, `flow_default`, `language`
- **Admin UI**: Simple editor with placeholder helper

### 4.2 Placeholder System

- Format: `{{variable_name}}` or `[variable-name]`
- Variables: User, Event, Site, System
- Sanitization: Auto-escape in HTML context

## PHASE 5: ADMIN UI

### 5.1 Email Configuration Screen

**Location**: `Apollo Hub > Emails`

**Sections**:
1. **Email Flows** - Configure each flow (template, subject, recipients)
2. **Templates** - Manage email templates (CPT list)
3. **Test Email** - Send test emails with sample data
4. **Email Log** - View sent emails (if logging enabled)

### 5.2 Flow Configuration

For each flow:
- **Template**: Dropdown (from CPT)
- **Subject**: Text field with variable hints
- **Recipients**: 
  - Primary: Dynamic (user, author, etc.)
  - Extra: Static emails or roles
- **Enabled**: Toggle

## PHASE 6: INTEGRATION POINTS

### 6.1 Registration Confirmation

- **Hook**: `apollo_user_registration_complete`
- **Flow**: `registration_confirm`
- **Variables**: `user_name`, `confirm_url`, `site_name`
- **Recipients**: New user email

### 6.2 Producer Notification

- **Hook**: `publish_event_listing` or custom event action
- **Flow**: `producer_notify`
- **Variables**: `event_title`, `event_url`, `producer_name`, `event_date`
- **Recipients**: Event author + configured producer emails

## NEXT STEPS

1. ✅ Complete inventory (DONE)
2. ⏳ Remove WooCommerce dependencies
3. ⏳ Create centralized email service
4. ⏳ Implement template CPT
5. ⏳ Build admin UI
6. ⏳ Wire flows
7. ⏳ Test & validate




