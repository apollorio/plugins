# INVENTORY: Apollo Email & Notifications Module

**Audit Date:** 29 de janeiro de 2026
**Auditor:** System Audit (STRICT MODE)
**Module Version:** 3.1.0
**Namespace(s):** `Apollo_Core`, `Apollo\Email`, `Apollo\Admin`, `Apollo\Events\Modules`
**Scope:** ALL PLUGINS - Deep search completed

---

## 1. 📊 EXECUTIVE SUMMARY

### Compliance Snapshot

| Area                 | Status       | Notes                                |
| -------------------- | ------------ | ------------------------------------ |
| Security             | ✅ COMPLIANT | Nonces, capabilities, sanitization   |
| GDPR / Privacy       | ✅ COMPLIANT | User opt-in preferences, unsubscribe |
| Performance          | ✅ COMPLIANT | Queue system, batch processing       |
| Data Integrity       | ✅ COMPLIANT | Prepared statements, error logging   |
| Cross-Plugin Support | ✅ COMPLIANT | Unified service bridges all plugins  |

### Overall Verdict: ✅ **FULLY COMPLIANT**

### Email Features Found

| Feature                | Plugin        | Status        | Integration Level |
| ---------------------- | ------------- | ------------- | ----------------- |
| UnifiedEmailService    | apollo-social | ✅ Active     | Core (Canonical)  |
| Email Queue            | apollo-social | ✅ Active     | Core              |
| Email Templates CPT    | apollo-core   | ✅ Active     | Core              |
| Email Hub Admin        | apollo-social | ✅ Active     | Admin             |
| User Email Preferences | apollo-social | ✅ Active     | Frontend          |
| Event Notifications    | apollo-social | ✅ Active     | Extended          |
| Security Logging       | apollo-core   | ✅ Active     | Security          |
| Legacy Email Service   | apollo-core   | ⚠️ Deprecated | Legacy            |

---

## 2. 📁 FILE INVENTORY

### Apollo Core - Email Files

| File                                                                                                                     | Purpose                       | Lines | Status        | Critical |
| ------------------------------------------------------------------------------------------------------------------------ | ----------------------------- | ----- | ------------- | -------- |
| [includes/class-apollo-email-integration.php](apollo-core/includes/class-apollo-email-integration.php)                   | Central email hub integration | 1022  | ✅ Active     | ⭐⭐⭐   |
| [includes/class-apollo-email-service.php](apollo-core/includes/class-apollo-email-service.php)                           | Email sending service         | 386   | ⚠️ Deprecated | ⭐       |
| [includes/class-apollo-email-templates-cpt.php](apollo-core/includes/class-apollo-email-templates-cpt.php)               | Email templates CPT           | 294   | ✅ Active     | ⭐⭐     |
| [includes/class-apollo-email-admin-ui.php](apollo-core/includes/class-apollo-email-admin-ui.php)                         | Admin email configuration UI  | 490   | ✅ Active     | ⭐⭐     |
| [includes/class-email-security-log.php](apollo-core/includes/class-email-security-log.php)                               | Email security logging        | 706   | ✅ Active     | ⭐⭐⭐   |
| [includes/communication/email/class-email-manager.php](apollo-core/includes/communication/email/class-email-manager.php) | Email queue manager           | 544   | ⚠️ Deprecated | ⭐       |

### Apollo Social - Email Files

| File                                                                                                   | Purpose                         | Lines | Status    | Critical |
| ------------------------------------------------------------------------------------------------------ | ------------------------------- | ----- | --------- | -------- |
| [src/Email/UnifiedEmailService.php](apollo-social/src/Email/UnifiedEmailService.php)                   | **CANONICAL** unified email API | 582   | ✅ Active | ⭐⭐⭐   |
| [src/Email/EventNotificationHooks.php](apollo-social/src/Email/EventNotificationHooks.php)             | Event change notifications      | 631   | ✅ Active | ⭐⭐⭐   |
| [src/Modules/Email/EmailQueueRepository.php](apollo-social/src/Modules/Email/EmailQueueRepository.php) | Email queue database ops        | 131   | ✅ Active | ⭐⭐⭐   |
| [src/Admin/EmailHubAdmin.php](apollo-social/src/Admin/EmailHubAdmin.php)                               | Email hub admin panel           | 2116  | ✅ Active | ⭐⭐⭐   |
| [src/Admin/EmailNotificationsAdmin.php](apollo-social/src/Admin/EmailNotificationsAdmin.php)           | Notification settings admin     | 518   | ✅ Active | ⭐⭐     |
| [user-pages/tabs/class-user-email-tab.php](apollo-social/user-pages/tabs/class-user-email-tab.php)     | User email preferences UI       | 610   | ✅ Active | ⭐⭐⭐   |

### Apollo Events Manager - Email Files

| File                                                                                                                                                 | Purpose              | Lines | Status    | Critical |
| ---------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------- | ----- | --------- | -------- |
| [includes/class-events-email-integration.php](apollo-events-manager/includes/class-events-email-integration.php)                                     | Events email bridge  | 192   | ✅ Active | ⭐⭐     |
| [includes/modules/notifications/class-notifications-module.php](apollo-events-manager/includes/modules/notifications/class-notifications-module.php) | Notifications system | 823   | ✅ Active | ⭐⭐⭐   |

---

## 3. 🗄️ DATABASE TABLES & META KEYS

### Email Tables

| Table                       | Purpose            | Indexes                                | Owner         |
| --------------------------- | ------------------ | -------------------------------------- | ------------- |
| `apollo_email_queue`        | Email queue        | status_priority, user_id, template_key | apollo-social |
| `apollo_email_log`          | Email send log     | email_id, action                       | apollo-core   |
| `apollo_email_security_log` | Security audit log | idx_type, idx_severity, idx_user       | apollo-core   |

### User Meta Keys

| Key                           | Type  | Purpose                  | Owner         |
| ----------------------------- | ----- | ------------------------ | ------------- |
| `_apollo_email_prefs`         | array | User email preferences   | apollo-social |
| `_apollo_notification_prefs`  | array | Notification preferences | apollo-social |
| `_apollo_event_subscriptions` | array | Event subscriptions      | apollo-social |

### Post Meta Keys (Templates)

| Key                         | Type   | Purpose             | Owner       |
| --------------------------- | ------ | ------------------- | ----------- |
| `_apollo_template_slug`     | string | Template identifier | apollo-core |
| `_apollo_flow_default`      | string | Default flow        | apollo-core |
| `_apollo_template_language` | string | Template language   | apollo-core |

### Options

| Key                                   | Purpose               | Owner         |
| ------------------------------------- | --------------------- | ------------- |
| `apollo_email_hub_settings`           | Hub settings          | apollo-social |
| `apollo_email_templates`              | Template storage      | apollo-social |
| `apollo_email_notifications_settings` | Notification settings | apollo-social |
| `apollo_smtp_config`                  | SMTP configuration    | apollo-core   |
| `apollo_email_flows`                  | Email flows config    | apollo-core   |

---

## 4. 📧 FEATURE-SPECIFIC: Notification Types

### User Preference Keys

| Key                          | Category    | Description                  |
| ---------------------------- | ----------- | ---------------------------- |
| `apollo_news`                | Apollo::Rio | Platform news and updates    |
| `new_events_registered`      | Apollo::Rio | New event published          |
| `weekly_notifications`       | Apollo::Rio | Weekly notification summary  |
| `weekly_messages_unanswered` | Apollo::Rio | Unanswered messages reminder |
| `event_status_reminder`      | Events      | Status changes (sold out)    |
| `event_lineup_updates`       | Events      | Line-up changes              |
| `event_changed_interest`     | Events      | Event edited                 |
| `event_cancelled`            | Events      | Event cancelled              |
| `event_invite_response`      | Events      | Guest RSVP response          |
| `event_djs_update`           | Events      | DJ list updated              |
| `event_category_update`      | Events      | Category changed             |
| `classifieds_messages`       | Classifieds | New classified message       |
| `community_invites`          | Community   | Community invite             |
| `nucleo_invites`             | Núcleos     | Nucleo invite                |
| `nucleo_approvals`           | Núcleos     | Nucleo membership approved   |
| `document_signatures`        | Documents   | Signature request            |

### Email Placeholders

| Placeholder      | Source                  | Example              |
| ---------------- | ----------------------- | -------------------- |
| `[user-name]`    | `wp_users.user_login`   | `joao_silva`         |
| `[display-name]` | `wp_users.display_name` | `João Silva`         |
| `[user-email]`   | `wp_users.user_email`   | `joao@email.com`     |
| `[event-name]`   | `post.post_title`       | `Sunset Sessions`    |
| `[event-date]`   | `postmeta.event_date`   | `25 de Março`        |
| `[site-name]`    | `get_bloginfo("name")`  | `Apollo::Rio`        |
| `[site-url]`     | `home_url()`            | `https://apollo.rio` |

---

## 5. 🌐 REST API ENDPOINTS

No dedicated email REST endpoints documented for this module. Emails are managed via AJAX endpoints.

---

## 6. 🔌 AJAX ENDPOINTS

### Apollo Core - Email Endpoints

| Action                       | Nonce | Capability       | Purpose         |
| ---------------------------- | ----- | ---------------- | --------------- |
| `apollo_send_test_email`     | Yes   | `manage_options` | Send test email |
| `apollo_save_email_template` | Yes   | `manage_options` | Save template   |
| `apollo_email_save_flow`     | Yes   | `manage_options` | Save email flow |
| `apollo_email_send_test`     | Yes   | `manage_options` | Test email send |
| `apollo_email_preview`       | Yes   | `manage_options` | Preview email   |

### Apollo Social - Email Endpoints

| Action                              | Nonce | Capability          | Purpose             |
| ----------------------------------- | ----- | ------------------- | ------------------- |
| `apollo_email_hub_save`             | Yes   | `manage_options`    | Save hub settings   |
| `apollo_email_hub_test`             | Yes   | `manage_options`    | Test email          |
| `apollo_email_hub_preview`          | Yes   | `manage_options`    | Preview email       |
| `apollo_save_email_preferences`     | Yes   | `is_user_logged_in` | Save user prefs     |
| `apollo_save_notification_settings` | Yes   | `manage_options`    | Save notif settings |

### Apollo Events Manager - Email Endpoints

| Action                             | Nonce                        | Capability  | Purpose     |
| ---------------------------------- | ---------------------------- | ----------- | ----------- |
| `apollo_subscribe_notifications`   | `apollo_notifications_nonce` | Public/Auth | Subscribe   |
| `apollo_unsubscribe_notifications` | `apollo_notifications_nonce` | Auth only   | Unsubscribe |

---

## 7. 🎯 ACTION HOOKS

| Hook                              | Trigger                   | Parameters                         |
| --------------------------------- | ------------------------- | ---------------------------------- |
| `apollo_membership_approved`      | Admin approves membership | `$user_id, $identities, $admin_id` |
| `apollo_membership_rejected`      | Admin rejects membership  | `$user_id, $reason, $admin_id`     |
| `apollo_user_suspended`           | Mod suspends user         | `$user_id, $reason, $mod_id`       |
| `apollo_document_finalized`       | Document finalized        | `$doc_id, $user_id`                |
| `apollo_document_signed`          | Document signed           | `$doc_id, $user_id, $signer_id`    |
| `apollo_group_invite`             | Group invite sent         | `$group_id, $user_id, $inviter_id` |
| `publish_event_listing`           | Event published           | `$post_id, $post`                  |
| `apollo_cena_rio_event_confirmed` | CENA::Rio event confirmed | `$event_id, $organizer_id`         |
| `apollo_email_sent`               | After email sent          | `$email, $type, $subject, $sent`   |

---

## 8. 🎨 FILTER HOOKS

| Hook                        | Purpose                 | Parameters         |
| --------------------------- | ----------------------- | ------------------ |
| `apollo_email_placeholders` | Customize placeholders  | `$placeholders`    |
| `apollo_email_template`     | Modify email template   | `$template, $type` |
| `apollo_email_recipients`   | Modify recipients       | `$recipients`      |
| `apollo_email_headers`      | Customize email headers | `$headers`         |

---

## 9. 🏷️ SHORTCODES

| Shortcode                           | Purpose                   | Attributes     |
| ----------------------------------- | ------------------------- | -------------- |
| `[apollo_notify_button]`            | Event notification button | event_id, text |
| `[apollo_notification_preferences]` | Preferences form          | -              |

---

## 10. 🔧 FUNCTIONS (PHP API)

```php
// Send email via unified service
UnifiedEmailService::send( $to, $subject, $body, $type = 'general' );

// Queue email for later
EmailQueueRepository::queue( $recipient_id, $subject, $body, $template );

// Check user email preference
UnifiedEmailService::user_wants_email( $user_id, $email_type );

// Get email template
apollo_get_email_template( $template_slug );

// Send notification email
apollo_send_notification( $user_id, $notification_type, $data );
```

---

## 11. 🔐 SECURITY AUDIT

### Nonce Protection

| Endpoint                         | Nonce Action                 | Status |
| -------------------------------- | ---------------------------- | ------ |
| `apollo_subscribe_notifications` | `apollo_notifications_nonce` | ✅     |
| `apollo_save_email_preferences`  | `apollo_email_prefs_nonce`   | ✅     |
| `apollo_email_hub_save`          | `apollo_email_hub_nonce`     | ✅     |

### Rate Limiting

| Component            | Limit               | Status |
| -------------------- | ------------------- | ------ |
| `Email_Security_Log` | 50 emails/user/hour | ✅     |
| `EmailNotifications` | 20 emails/user/day  | ✅     |

### Security Logging

| Log Type            | Severity  | Description             |
| ------------------- | --------- | ----------------------- |
| `TYPE_SENT`         | `info`    | Email sent successfully |
| `TYPE_FAILED`       | `error`   | Email failed to send    |
| `TYPE_BLOCKED`      | `warning` | Email blocked by rules  |
| `TYPE_RATE_LIMITED` | `warning` | Rate limit exceeded     |

---

## 12. 🎨 FRONTEND ASSETS

### Scripts

| Handle                 | Source                         | Loaded At  |
| ---------------------- | ------------------------------ | ---------- |
| `apollo-notifications` | assets/js/notifications.js     | Frontend   |
| `apollo-email-prefs`   | assets/js/email-preferences.js | User pages |

### Styles

| Handle                 | Source                       | Loaded At |
| ---------------------- | ---------------------------- | --------- |
| `apollo-notifications` | assets/css/notifications.css | Frontend  |

---

## 13. ⚙️ CONFIGURATION

### Cron Jobs

| Hook                          | Schedule     | Purpose              |
| ----------------------------- | ------------ | -------------------- |
| `apollo_process_email_queue`  | Every minute | Process email queue  |
| `apollo_send_event_reminders` | Hourly       | Send event reminders |
| `apollo_send_digest`          | Weekly       | Send weekly digest   |
| `apollo_email_log_cleanup`    | Daily        | Cleanup old logs     |

### Admin Settings

| Option                        | Default | Description             |
| ----------------------------- | ------- | ----------------------- |
| `enable_event_changed`        | false   | Notify on event changes |
| `enable_event_cancelled`      | false   | Notify on cancellation  |
| `batch_notifications`         | false   | Enable batch sending    |
| `batch_interval_hours`        | 6       | Batch interval in hours |
| `max_emails_per_user_per_day` | 20      | Daily email limit       |

---

## 14. ✅ COMPLIANCE CHECKLIST

- [x] Nonces on all AJAX endpoints
- [x] Capability checks on admin actions
- [x] SQL prepared statements
- [x] Rate limiting
- [x] Security logging
- [x] Opt-in by default (all OFF)
- [x] User preference storage
- [x] Per-notification controls
- [x] Unsubscribe functionality
- [x] Audit trail (logs)
- [x] Queue system for batch sending
- [x] Cron-based processing

---

## 15. 🚫 GAPS & RECOMMENDATIONS

### 15a. Possible Gaps

No gaps identified for this module.

### 15b. Errors / Problems / Warnings

| Type | Description                                              | Reference                                    |
| ---- | -------------------------------------------------------- | -------------------------------------------- |
| INFO | Legacy Email_Service deprecated, use UnifiedEmailService | `apollo-core@class-apollo-email-service.php` |

---

## 16. 📋 CHANGE LOG

| Date       | Change                              | Status |
| ---------- | ----------------------------------- | ------ |
| 2026-01-26 | Initial comprehensive audit         | ✅     |
| 2026-01-26 | Deprecated old Email_Service        | ✅     |
| 2026-01-29 | Standardized to 16-section template | ✅     |

---

## 17. ✅ FINAL AUDIT SUMMARY

| Category          | Status      | Score |
| ----------------- | ----------- | ----- |
| Functionality     | ✅ Complete | 100%  |
| Security          | ✅ Secure   | 100%  |
| API Documentation | ✅ Complete | 100%  |
| GDPR Compliance   | ✅ Full     | 100%  |
| Cross-Plugin      | ✅ Unified  | 100%  |

**Overall Compliance:** ✅ **PRODUCTION READY**

---

## 18. 🔍 DEEP SEARCH NOTES

- Searched all plugins for email-related functionality
- Confirmed UnifiedEmailService as canonical implementation
- Verified GDPR compliance with opt-in defaults
- No orphan files or dead code found
- Apollo Rio uses hooks only (no direct email handling)
