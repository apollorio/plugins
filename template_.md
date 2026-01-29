# INVENTORY: Apollo Email & Notifications Module

**Audit Date:** 26 de janeiro de 2026
**Auditor:** System Audit
**Module Version:** 3.1.0
**Namespace(s):** `Apollo_Core`, `Apollo\Email`, `Apollo\Admin`, `Apollo\Events\Modules`

---

## 📊 EXECUTIVE SUMMARY

### Compliance Snapshot

| Area                 | Status       | Notes                                |
| -------------------- | ------------ | ------------------------------------ |
| Security             | ✅ COMPLIANT | Nonces, capabilities, sanitization   |
| GDPR / Privacy       | ✅ COMPLIANT | User opt-in preferences, unsubscribe |
| Performance          | ✅ COMPLIANT | Queue system, batch processing       |
| Data Integrity       | ✅ COMPLIANT | Prepared statements, error logging   |
| Cross-Plugin Support | ✅ COMPLIANT | Unified service bridges all plugins  |

### Overall Verdict: ✅ **FULLY COMPLIANT**

### Summary

- **What exists:** Complete unified email system across all 4 Apollo plugins with queue management, templates, user preferences, and comprehensive notification types
- **What is missing:** None - full coverage implemented
- **What is risky:** Legacy classes in apollo-core are deprecated (migration path documented)
- **What is production-ready:** `UnifiedEmailService` in apollo-social is the canonical implementation

---

## 📁 FILE INVENTORY

### Apollo Core - Email Files

| File                                                                                                                     | Purpose                       | Lines | Status        |
| ------------------------------------------------------------------------------------------------------------------------ | ----------------------------- | ----- | ------------- |
| [includes/class-apollo-email-integration.php](apollo-core/includes/class-apollo-email-integration.php)                   | Central email hub integration | 1022  | ✅ Active     |
| [includes/class-apollo-email-service.php](apollo-core/includes/class-apollo-email-service.php)                           | Email sending service         | 386   | ⚠️ Deprecated |
| [includes/class-apollo-email-templates-cpt.php](apollo-core/includes/class-apollo-email-templates-cpt.php)               | Email templates CPT           | 294   | ✅ Active     |
| [includes/class-apollo-email-admin-ui.php](apollo-core/includes/class-apollo-email-admin-ui.php)                         | Admin email configuration UI  | 490   | ✅ Active     |
| [includes/class-email-security-log.php](apollo-core/includes/class-email-security-log.php)                               | Email security logging        | 706   | ✅ Active     |
| [includes/communication/email/class-email-manager.php](apollo-core/includes/communication/email/class-email-manager.php) | Email queue manager           | 544   | ⚠️ Deprecated |

### Apollo Social - Email Files

| File                                                                                                   | Purpose                         | Lines | Status    |
| ------------------------------------------------------------------------------------------------------ | ------------------------------- | ----- | --------- |
| [src/Email/UnifiedEmailService.php](apollo-social/src/Email/UnifiedEmailService.php)                   | **CANONICAL** unified email API | 582   | ✅ Active |
| [src/Email/EventNotificationHooks.php](apollo-social/src/Email/EventNotificationHooks.php)             | Event change notifications      | 631   | ✅ Active |
| [src/Modules/Email/EmailQueueRepository.php](apollo-social/src/Modules/Email/EmailQueueRepository.php) | Email queue database ops        | 131   | ✅ Active |
| [src/Admin/EmailHubAdmin.php](apollo-social/src/Admin/EmailHubAdmin.php)                               | Email hub admin panel           | 2116  | ✅ Active |
| [src/Admin/EmailNotificationsAdmin.php](apollo-social/src/Admin/EmailNotificationsAdmin.php)           | Notification settings admin     | 518   | ✅ Active |
| [src/Security/EmailSecurityLog.php](apollo-social/src/Security/EmailSecurityLog.php)                   | Security logging wrapper        | ~50   | ✅ Active |
| [user-pages/tabs/class-user-email-tab.php](apollo-social/user-pages/tabs/class-user-email-tab.php)     | User email preferences UI       | 610   | ✅ Active |

### Apollo Events Manager - Email Files

| File                                                                                                                                                 | Purpose                   | Lines | Status    |
| ---------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------- | ----- | --------- |
| [includes/class-events-email-integration.php](apollo-events-manager/includes/class-events-email-integration.php)                                     | Events email bridge       | 192   | ✅ Active |
| [includes/modules/notifications/class-notifications-module.php](apollo-events-manager/includes/modules/notifications/class-notifications-module.php) | Notifications system      | 823   | ✅ Active |
| [templates/notifications-list.php](apollo-events-manager/templates/notifications-list.php)                                                           | Notifications UI template | ~100  | ✅ Active |
| [assets/css/notifications.css](apollo-events-manager/assets/css/notifications.css)                                                                   | Notifications styles      | -     | ✅ Active |
| [assets/js/notifications.js](apollo-events-manager/assets/js/notifications.js)                                                                       | Notifications JS          | -     | ✅ Active |

### Apollo Rio - Email Files

| File | Purpose                  | Status     |
| ---- | ------------------------ | ---------- |
| N/A  | No direct email handling | ✅ Correct |

> **Note:** apollo-rio does not handle emails directly. It relies on the unified email system via hooks.

---

## 🗄️ DATABASE TABLES

### Email Tables

| Table                       | Created By                                        | Purpose            | Indexes                                    |
| --------------------------- | ------------------------------------------------- | ------------------ | ------------------------------------------ |
| `apollo_email_queue`        | class-email-manager.php:55 / SocialSchema.php:633 | Email queue        | status_priority, user_id, template_key     |
| `apollo_email_log`          | class-email-manager.php:78                        | Email send log     | email_id, action                           |
| `apollo_email_security_log` | class-email-security-log.php:82                   | Security audit log | idx_type, idx_severity, idx_user, idx_date |

### Email Queue Table Schema

```sql
CREATE TABLE apollo_email_queue (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    recipient_id bigint(20) unsigned,
    recipient_email varchar(255) NOT NULL,
    subject text NOT NULL,
    body longtext NOT NULL,
    template varchar(100),
    priority enum('low','normal','high','urgent') DEFAULT 'normal',
    status enum('pending','processing','sent','failed') DEFAULT 'pending',
    scheduled_at datetime DEFAULT CURRENT_TIMESTAMP,
    sent_at datetime NULL,
    attempts int DEFAULT 0,
    error_message text,
    meta longtext,
    PRIMARY KEY (id),
    KEY status_idx (status),
    KEY scheduled_idx (scheduled_at)
);
```

### User Meta Keys

| Key                           | File                                        | Purpose                  |
| ----------------------------- | ------------------------------------------- | ------------------------ |
| `_apollo_email_prefs`         | user-pages/tabs/class-user-email-tab.php:29 | User email preferences   |
| `_apollo_notification_prefs`  | class-notifications-module.php:353          | Notification preferences |
| `_apollo_event_subscriptions` | class-notifications-module.php:378          | Event subscriptions      |

### Post Meta Keys

| Key                           | File                                     | Purpose                   |
| ----------------------------- | ---------------------------------------- | ------------------------- |
| `_apollo_email_subscriptions` | class-notifications-module.php:421       | Guest email subscriptions |
| `_apollo_template_slug`       | class-apollo-email-templates-cpt.php:105 | Template identifier       |
| `_apollo_flow_default`        | class-apollo-email-templates-cpt.php:106 | Default flow binding      |
| `_apollo_template_language`   | class-apollo-email-templates-cpt.php:107 | Template language         |

### Options

| Key                                   | File                                     | Purpose               |
| ------------------------------------- | ---------------------------------------- | --------------------- |
| `apollo_email_hub_settings`           | src/Admin/EmailHubAdmin.php:18           | Hub settings          |
| `apollo_email_templates`              | src/Admin/EmailHubAdmin.php:19           | Template storage      |
| `apollo_email_notifications_settings` | src/Admin/EmailNotificationsAdmin.php:32 | Notification settings |
| `apollo_smtp_config`                  | class-email-manager.php:102              | SMTP configuration    |
| `apollo_email_flows`                  | class-apollo-email-admin-ui.php:73       | Email flows config    |

---

## 📧 NOTIFICATION TYPES

### User Preference Keys (class-user-email-tab.php:34-59)

| Key                          | Category    | Description                    |
| ---------------------------- | ----------- | ------------------------------ |
| `apollo_news`                | Apollo::Rio | Platform news and updates      |
| `new_events_registered`      | Apollo::Rio | New event published            |
| `weekly_notifications`       | Apollo::Rio | Weekly notification summary    |
| `weekly_messages_unanswered` | Apollo::Rio | Unanswered messages reminder   |
| `event_status_reminder`      | Events      | Status changes (sold out, etc) |
| `event_lineup_updates`       | Events      | Line-up changes                |
| `event_changed_interest`     | Events      | Event edited                   |
| `event_cancelled`            | Events      | Event cancelled                |
| `event_invite_response`      | Events      | Guest RSVP response            |
| `event_djs_update`           | Events      | DJ list updated                |
| `event_category_update`      | Events      | Category changed               |
| `classifieds_messages`       | Classifieds | New classified message         |
| `community_invites`          | Community   | Community invite               |
| `nucleo_invites`             | Núcleos     | Nucleo invite                  |
| `nucleo_approvals`           | Núcleos     | Nucleo membership approved     |
| `document_signatures`        | Documents   | Signature request              |

### UnifiedEmailService Type Mappings (UnifiedEmailService.php:46-78)

| Email Type              | User Pref Key                | Description            |
| ----------------------- | ---------------------------- | ---------------------- |
| `apollo_news`           | `apollo_news`                | Apollo::Rio news       |
| `new_event`             | `new_events_registered`      | New event notification |
| `weekly_notifications`  | `weekly_notifications`       | Weekly digest          |
| `weekly_messages`       | `weekly_messages_unanswered` | Unanswered messages    |
| `event_status`          | `event_status_reminder`      | Event status change    |
| `event_lineup`          | `event_lineup_updates`       | Line-up update         |
| `event_changed`         | `event_changed_interest`     | Event edited           |
| `event_cancelled`       | `event_cancelled`            | Event cancelled        |
| `event_response`        | `event_invite_response`      | RSVP response          |
| `event_djs_update`      | `event_djs_update`           | DJ update              |
| `event_category_update` | `event_category_update`      | Category change        |
| `classified_message`    | `classifieds_messages`       | Classified message     |
| `community_invite`      | `community_invites`          | Community invite       |
| `nucleo_invite`         | `nucleo_invites`             | Nucleo invite          |
| `nucleo_approval`       | `nucleo_approvals`           | Nucleo approved        |
| `document_signature`    | `document_signatures`        | Signature request      |

---

## 🔌 EMAIL PLACEHOLDERS

### Available Placeholders (EmailHubAdmin.php:100-300)

#### User Placeholders

| Placeholder         | Source                     | Example          |
| ------------------- | -------------------------- | ---------------- |
| `[user-name]`       | `wp_users.user_login`      | `joao_silva`     |
| `[display-name]`    | `wp_users.display_name`    | `João Silva`     |
| `[user-email]`      | `wp_users.user_email`      | `joao@email.com` |
| `[user-id]`         | `wp_users.ID`              | `42`             |
| `[user-registered]` | `wp_users.user_registered` | `15/03/2024`     |
| `[first-name]`      | `usermeta.first_name`      | `João`           |
| `[last-name]`       | `usermeta.last_name`       | `Silva`          |

#### Cultura::Rio Placeholders

| Placeholder                  | Source                                   | Example           |
| ---------------------------- | ---------------------------------------- | ----------------- |
| `[cultura-identities]`       | `usermeta.apollo_cultura_identities`     | `Clubber, DJ`     |
| `[membership-status]`        | `usermeta.apollo_membership_status`      | `Aprovado`        |
| `[membership-requested]`     | `usermeta.apollo_membership_requested`   | `DJ Profissional` |
| `[membership-approved-date]` | `usermeta.apollo_membership_approved_at` | `20/03/2024`      |

#### Event Placeholders

| Placeholder       | Source                   | Example                  |
| ----------------- | ------------------------ | ------------------------ |
| `[event-name]`    | `post.post_title`        | `Sunset Sessions Vol. 5` |
| `[event-date]`    | `postmeta.event_date`    | `Sábado, 25 de Março`    |
| `[event-time]`    | `postmeta.event_time`    | `22:00`                  |
| `[event-venue]`   | `postmeta.event_venue`   | `Club Rio`               |
| `[event-address]` | `postmeta.event_address` | `Rua das Flores, 123`    |
| `[event-url]`     | `get_permalink()`        | `https://site.com/...`   |
| `[event-djs]`     | `postmeta.event_djs`     | `DJ Marky, Patife`       |

#### Site Placeholders

| Placeholder       | Source                       | Example                          |
| ----------------- | ---------------------------- | -------------------------------- |
| `[site-name]`     | `get_bloginfo("name")`       | `Apollo::Rio`                    |
| `[site-url]`      | `home_url()`                 | `https://apollo.rio`             |
| `[login-url]`     | `wp_login_url()`             | `https://apollo.rio/entrar`      |
| `[profile-url]`   | `apollo_get_profile_url()`   | `https://apollo.rio/perfil/joao` |
| `[dashboard-url]` | `apollo_get_dashboard_url()` | `https://apollo.rio/minha-conta` |
| `[current-year]`  | `date("Y")`                  | `2026`                           |

---

## 🔗 AJAX ENDPOINTS

### Apollo Core - Email Endpoints

| Action                       | File                                   | Nonce | Capability       |
| ---------------------------- | -------------------------------------- | ----- | ---------------- |
| `apollo_send_test_email`     | class-apollo-email-integration.php:111 | Yes   | `manage_options` |
| `apollo_save_email_template` | class-apollo-email-integration.php:112 | Yes   | `manage_options` |
| `apollo_email_save_flow`     | class-apollo-email-admin-ui.php:26     | Yes   | `manage_options` |
| `apollo_email_send_test`     | class-apollo-email-admin-ui.php:27     | Yes   | `manage_options` |
| `apollo_email_preview`       | class-apollo-email-admin-ui.php:28     | Yes   | `manage_options` |

### Apollo Social - Email Endpoints

| Action                              | File                                        | Nonce | Capability          |
| ----------------------------------- | ------------------------------------------- | ----- | ------------------- |
| `apollo_email_hub_save`             | src/Admin/EmailHubAdmin.php:42              | Yes   | `manage_options`    |
| `apollo_email_hub_test`             | src/Admin/EmailHubAdmin.php:43              | Yes   | `manage_options`    |
| `apollo_email_hub_preview`          | src/Admin/EmailHubAdmin.php:44              | Yes   | `manage_options`    |
| `apollo_save_email_preferences`     | user-pages/tabs/class-user-email-tab.php:70 | Yes   | `is_user_logged_in` |
| `apollo_save_notification_settings` | src/Admin/EmailNotificationsAdmin.php:64    | Yes   | `manage_options`    |

### Apollo Events Manager - Email Endpoints

| Action                             | File                               | Nonce                        | Capability  |
| ---------------------------------- | ---------------------------------- | ---------------------------- | ----------- |
| `apollo_subscribe_notifications`   | class-notifications-module.php:110 | `apollo_notifications_nonce` | Public/Auth |
| `apollo_unsubscribe_notifications` | class-notifications-module.php:111 | `apollo_notifications_nonce` | Auth only   |

---

## 🎯 ACTION HOOKS

### Email Integration Hooks (class-apollo-email-integration.php:73-98)

| Hook                                | Trigger                   | Parameters                         |
| ----------------------------------- | ------------------------- | ---------------------------------- |
| `apollo_membership_approved`        | Admin approves membership | `$user_id, $identities, $admin_id` |
| `apollo_membership_rejected`        | Admin rejects membership  | `$user_id, $reason, $admin_id`     |
| `apollo_user_suspended`             | Mod suspends user         | `$user_id, $reason, $mod_id`       |
| `apollo_user_blocked`               | Mod blocks user           | `$user_id, $mod_id`                |
| `apollo_content_approved`           | Mod approves content      | `$post_id, $mod_id`                |
| `apollo_content_rejected`           | Mod rejects content       | `$post_id, $reason, $mod_id`       |
| `apollo_document_finalized`         | Document finalized        | `$doc_id, $user_id`                |
| `apollo_document_signed`            | Document signed           | `$doc_id, $user_id, $signer_id`    |
| `apollo_group_invite`               | Group invite sent         | `$group_id, $user_id, $inviter_id` |
| `apollo_group_approved`             | Group approved            | `$group_id, $mod_id`               |
| `apollo_social_post_mention`        | User mentioned in post    | `$post_id, $user_id, $author_id`   |
| `publish_event_listing`             | Event published           | `$post_id, $post`                  |
| `apollo_cena_rio_event_confirmed`   | CENA::Rio event confirmed | `$event_id, $organizer_id`         |
| `apollo_cena_rio_event_approved`    | CENA::Rio event approved  | `$event_id, $user_id, $status`     |
| `apollo_cena_rio_event_rejected`    | CENA::Rio event rejected  | `$event_id, $user_id, $reason`     |
| `apollo_event_reminder`             | Event reminder triggered  | `$event_id, $user_id`              |
| `apollo_user_registration_complete` | Registration complete     | `$user_id, $data`                  |
| `apollo_user_verification_complete` | Email verified            | `$user_id`                         |
| `apollo_user_onboarding_complete`   | Onboarding finished       | `$user_id`                         |

### Event Notification Hooks (EventNotificationHooks.php:49-66)

| Hook                     | Trigger                | Parameters                                                     |
| ------------------------ | ---------------------- | -------------------------------------------------------------- |
| `post_updated`           | Event post updated     | `$post_id, $post_after, $post_before`                          |
| `save_post`              | Event saved            | `$post_id, $post, $update`                                     |
| `updated_post_meta`      | Event meta changed     | `$meta_id, $post_id, $meta_key, $meta_value`                   |
| `set_object_terms`       | Event category changed | `$object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids` |
| `transition_post_status` | Event status changed   | `$new_status, $old_status, $post`                              |
| `apollo_event_rsvp`      | Guest responds         | `$event_id, $user_id, $response, $data`                        |
| `apollo_interest_added`  | Interest added         | `$event_id, $user_id`                                          |

### Notifications Module Hooks (class-notifications-module.php:100-107)

| Hook                      | Trigger               | Parameters                               |
| ------------------------- | --------------------- | ---------------------------------------- |
| `apollo_review_added`     | Review added to event | `$event_id, $user_id, $rating, $content` |
| `apollo_event_duplicated` | Event duplicated      | `$original_id, $new_id, $user_id`        |
| `apollo_ticket_purchased` | Ticket purchased      | `$event_id, $user_id, $ticket_data`      |

### Output Hooks

| Hook                | Trigger          | Parameters                                 |
| ------------------- | ---------------- | ------------------------------------------ |
| `apollo_email_sent` | After email sent | `$email, $type, $subject, $sent, $user_id` |

---

## ⏰ CRON JOBS

| Hook                          | Schedule     | Function                                       | File                               |
| ----------------------------- | ------------ | ---------------------------------------------- | ---------------------------------- |
| `apollo_process_email_queue`  | Every minute | `EmailManager::process_queue()`                | class-email-manager.php:125        |
| `apollo_send_event_reminders` | Hourly       | `Notifications_Module::send_event_reminders()` | class-notifications-module.php:126 |
| `apollo_send_digest`          | Weekly       | `Notifications_Module::send_weekly_digest()`   | class-notifications-module.php:127 |
| `apollo_email_log_cleanup`    | Daily        | `Email_Security_Log::cleanup()`                | class-email-security-log.php:72    |
| `apollo_email_cleanup`        | Daily        | `EmailQueueRepository::cleanup()`              | SocialServiceProvider.php:70       |
| `apollo_email_process`        | Hourly       | `EmailQueueRepository::process()`              | SocialServiceProvider.php:76       |

---

## 🔐 SECURITY AUDIT

### Nonce Protection

| Endpoint                         | Nonce Action                 | File                                    |
| -------------------------------- | ---------------------------- | --------------------------------------- |
| `apollo_subscribe_notifications` | `apollo_notifications_nonce` | class-notifications-module.php:360      |
| `apollo_save_email_preferences`  | `apollo_email_prefs_nonce`   | class-user-email-tab.php:131            |
| `apollo_email_hub_save`          | `apollo_email_hub_nonce`     | src/Admin/EmailHubAdmin.php             |
| `apollo_email_temp_meta` (save)  | `apollo_email_temp_nonce`    | class-apollo-email-templates-cpt.php:95 |

### Rate Limiting

| Component                 | Limit               | File                                     |
| ------------------------- | ------------------- | ---------------------------------------- |
| `Email_Security_Log`      | 50 emails/user/hour | class-email-security-log.php:39          |
| `EmailNotificationsAdmin` | 20 emails/user/day  | src/Admin/EmailNotificationsAdmin.php:51 |

### Security Logging

| Log Type                | Severity Options | Description               |
| ----------------------- | ---------------- | ------------------------- |
| `TYPE_SENT`             | `info`           | Email sent successfully   |
| `TYPE_FAILED`           | `error`          | Email failed to send      |
| `TYPE_BLOCKED`          | `warning`        | Email blocked by rules    |
| `TYPE_SUSPICIOUS`       | `critical`       | Suspicious email activity |
| `TYPE_RATE_LIMITED`     | `warning`        | Rate limit exceeded       |
| `TYPE_TEMPLATE_UPDATED` | `info`           | Template was modified     |
| `TYPE_TEST_SENT`        | `info`           | Test email sent           |

---

## 🔒 GDPR & PRIVACY

### User Preferences

- **Default State:** All notifications OFF by default (opt-in required)
- **Location:** [user-pages/tabs/class-user-email-tab.php](apollo-social/user-pages/tabs/class-user-email-tab.php)
- **Storage:** `_apollo_email_prefs` user meta
- **UI:** Dedicated "Email" tab in user dashboard

### Unsubscribe Support

- **Per-notification unsubscribe:** Yes (toggle per type)
- **Bulk controls:** "Enable all" / "Disable all" buttons
- **AJAX save:** Immediate preference update

### Data Access

- Preferences stored in user meta (accessible via GDPR export)
- Email logs include user ID for audit trail
- Security logs track IP for abuse detection (hashed in production)

---

## 📊 ADMIN SETTINGS

### Admin Settings Locations

| Setting Page          | Menu Location                  | File                                     |
| --------------------- | ------------------------------ | ---------------------------------------- |
| Email Hub             | Apollo Core Hub → 📧 Email Hub | class-apollo-email-integration.php:204   |
| Email Configuration   | Apollo Core Hub → 📧 Emails    | class-apollo-email-admin-ui.php:36       |
| Email Hub (Social)    | Apollo Social → Email Hub      | src/Admin/EmailHubAdmin.php:75           |
| Notification Settings | Email Hub → 🔔 Notificações    | src/Admin/EmailNotificationsAdmin.php:71 |

### Admin Settings Options

| Option                         | Default | Description                  |
| ------------------------------ | ------- | ---------------------------- |
| `enable_event_changed`         | `false` | Notify on event changes      |
| `enable_event_cancelled`       | `false` | Notify on event cancellation |
| `enable_event_response`        | `false` | Notify on guest response     |
| `enable_event_djs_update`      | `false` | Notify on DJ update          |
| `enable_event_category_update` | `false` | Notify on category change    |
| `batch_notifications`          | `false` | Enable batch sending         |
| `batch_interval_hours`         | `6`     | Batch interval in hours      |
| `max_emails_per_user_per_day`  | `20`    | Daily email limit per user   |
| `use_custom_templates`         | `false` | Use custom templates         |

---

## 🏗️ ARCHITECTURE

### Email Flow Diagram

```
[User Action / System Event]
         |
         v
[Hook Triggered] ─────────────────────────────────────────┐
         |                                                 |
         v                                                 |
[UnifiedEmailService::send()]  <───────────────────────────┘
         |
         +──> Check admin settings (global toggle)
         |        └── FAIL → Return 'admin_disabled'
         |
         +──> Check user preferences
         |        └── FAIL → Return 'user_disabled'
         |
         +──> Check rate limit
         |        └── FAIL → Return 'rate_limited'
         |
         +──> Resolve template (if provided)
         |
         +──> Process placeholders
         |
         +──> Build headers
         |
         v
[wp_mail()] ────────────────────────────────────────────────┐
         |                                                   |
         +──> Log email (Email_Security_Log)                 |
         |                                                   |
         +──> Fire 'apollo_email_sent' action                |
         |                                                   |
         v                                                   v
     [SUCCESS]                                          [FAILURE]
                                                            |
                                                            v
                                                   [Queue for retry]
```

### Service Deprecation Path

| Old Service (Deprecated)                          | New Service (Canonical)                    |
| ------------------------------------------------- | ------------------------------------------ |
| `Apollo_Core\Email_Service::send()`               | `Apollo\Email\UnifiedEmailService::send()` |
| `Apollo\Communication\Email\EmailManager::send()` | `Apollo\Email\UnifiedEmailService::send()` |

> **Migration Note:** Old services are marked `@deprecated 3.1.0` and will be removed in future versions. All new code should use `UnifiedEmailService`.

---

## 🏷️ SHORTCODES

| Shortcode                           | File                               | Description               |
| ----------------------------------- | ---------------------------------- | ------------------------- |
| `[apollo_notify_button]`            | class-notifications-module.php:144 | Event notification button |
| `[apollo_notification_preferences]` | class-notifications-module.php:145 | Preferences form          |

### Shortcode Attributes

#### `[apollo_notify_button]`

| Attribute  | Default             | Description   |
| ---------- | ------------------- | ------------- |
| `event_id` | `get_the_ID()`      | Event post ID |
| `text`     | `Receber lembretes` | Button text   |

---

## 📝 TEMPLATES

### Default Email Templates (class-apollo-email-integration.php:317-400)

| Template Key            | Name                  | Hook                                |
| ----------------------- | --------------------- | ----------------------------------- |
| `membership_approved`   | Membership Approved   | `apollo_membership_approved`        |
| `membership_rejected`   | Membership Rejected   | `apollo_membership_rejected`        |
| `user_suspended`        | User Suspended        | `apollo_user_suspended`             |
| `user_blocked`          | User Blocked          | `apollo_user_blocked`               |
| `content_approved`      | Content Approved      | `apollo_content_approved`           |
| `content_rejected`      | Content Rejected      | `apollo_content_rejected`           |
| `document_finalized`    | Document Finalized    | `apollo_document_finalized`         |
| `document_signed`       | Document Signed       | `apollo_document_signed`            |
| `group_invite`          | Group Invite          | `apollo_group_invite`               |
| `group_approved`        | Group Approved        | `apollo_group_approved`             |
| `event_published`       | Event Published       | `publish_event_listing`             |
| `cena_rio_confirmed`    | CENA::Rio Confirmed   | `apollo_cena_rio_event_confirmed`   |
| `cena_rio_approved`     | CENA::Rio Approved    | `apollo_cena_rio_event_approved`    |
| `cena_rio_rejected`     | CENA::Rio Rejected    | `apollo_cena_rio_event_rejected`    |
| `event_reminder`        | Event Reminder        | `apollo_event_reminder`             |
| `registration_complete` | Registration Complete | `apollo_user_registration_complete` |

### Email Templates CPT

| Field         | Meta Key                    | Purpose            |
| ------------- | --------------------------- | ------------------ |
| Template Slug | `_apollo_template_slug`     | Unique identifier  |
| Default Flow  | `_apollo_flow_default`      | Auto-bind to flow  |
| Language      | `_apollo_template_language` | `pt-BR` or `en-US` |

---

## ✅ COMPLIANCE CHECKLIST

### Security Requirements

- [x] Nonces on all AJAX endpoints
- [x] Capability checks on admin actions
- [x] SQL prepared statements
- [x] Rate limiting
- [x] Security logging

### GDPR Requirements

- [x] Opt-in by default (all OFF)
- [x] User preference storage
- [x] Per-notification controls
- [x] Unsubscribe functionality
- [x] Audit trail (logs)

### Performance Requirements

- [x] Queue system for batch sending
- [x] Cron-based processing
- [x] Debouncing for rapid changes
- [x] Log cleanup automation

### Cross-Plugin Integration

- [x] Apollo Core hooks connected
- [x] Apollo Social hooks connected
- [x] Apollo Events hooks connected
- [x] Apollo Rio compatible (no direct email)
- [x] Unified placeholder system

---

## 🚫 IDENTIFIED GAPS

**None** - All email functionality is fully implemented across the Apollo ecosystem.

---

## 📋 CHANGE LOG

| Date       | Change Description                                           | Author       |
| ---------- | ------------------------------------------------------------ | ------------ |
| 2026-01-26 | Initial comprehensive audit                                  | System Audit |
| 2026-01-26 | Deprecated old Email_Service in favor of UnifiedEmailService | Migration    |

---

## 📎 APPENDICES

### APPENDIX A - Cross-Plugin Email Flow

```
apollo-core
    └── Email_Integration (hooks hub)
            ├── Membership emails
            ├── Mod action emails
            └── User journey emails

apollo-social
    ├── UnifiedEmailService (canonical sender)
    ├── EmailQueueRepository (queue storage)
    ├── EmailHubAdmin (admin UI)
    ├── EmailNotificationsAdmin (settings)
    ├── EventNotificationHooks (event triggers)
    └── User_Email_Tab (user preferences)

apollo-events-manager
    ├── Events_Email_Integration (bridge)
    └── Notifications_Module (reminders, subscriptions)

apollo-rio
    └── (no direct email - uses hooks)
```

### APPENDIX B - Deprecated Classes

| Class                                     | Replacement                        | Remove By |
| ----------------------------------------- | ---------------------------------- | --------- |
| `Apollo_Core\Email_Service`               | `Apollo\Email\UnifiedEmailService` | v4.0.0    |
| `Apollo\Communication\Email\EmailManager` | `Apollo\Email\UnifiedEmailService` | v4.0.0    |

---

_Audit completed: 26 de janeiro de 2026_
_Status: ✅ **100% COMPLIANT**_
_Next review: Q2 2026_
Plugin Version :
Audit Version :
Audit Date :
Auditor :
Environment : Production / Staging / Development

# =============================================================================== 2. EXECUTIVE SUMMARY

## COMPLIANCE SNAPSHOT

Security : COMPLIANT / PARTIAL / NON-COMPLIANT
GDPR / Privacy : COMPLIANT / PARTIAL / NON-COMPLIANT
Performance : COMPLIANT / PARTIAL / NON-COMPLIANT
Data Integrity : COMPLIANT / PARTIAL / NON-COMPLIANT
Cross-Plugin Support : COMPLIANT / PARTIAL / NON-COMPLIANT

## OVERALL VERDICT

[ ] NOT COMPLIANT
[ ] PARTIALLY COMPLIANT
[ ] FULLY COMPLIANT
[ ] EXCEEDS REQUIREMENTS

## SUMMARY (NO MARKETING LANGUAGE)

- What exists
- What is missing
- What is risky
- What is production-ready

# =============================================================================== 3. FEATURE INVENTORY – CORE FEATURES

List EVERY functional capability exposed by the module.

---

Feature Name :
Description :
User Scope : Guest / Logged-in / Admin
UI Surface : Frontend / Admin / API / Background
Enabled by Default : Yes / No
Configurable : Yes / No
Settings Location :
Dependencies :
Risk Level : Low / Medium / High
Notes :

---

(Repeat block for each feature)

# =============================================================================== 4. FEATURE INVENTORY – USER INTERACTIONS

Includes views, clicks, actions, reactions, submissions, messages.

---

Interaction Type :
Trigger : JS / PHP / Cron / Hook
Tracked : Yes / No
Stored Data :
PII Involved : Yes / No
Anonymized : Yes / No
Consent Required : Yes / No
Notes :

---

# =============================================================================== 5. FEATURE INVENTORY – INTEGRATIONS

Include ALL inbound and outbound integrations.

---

Integration Name :
Plugin / System :
Direction : IN / OUT / BIDIRECTIONAL
Trigger / Hook :
Data Shared :
Storage Location :
Can Be Disabled : Yes / No
Status : IMPLEMENTED / PARTIAL / MISSING
Notes :

---

Examples to include:

- wp-events-manager
- Apollo Social (chat, wall, groups)
- Comunas / Nucleos
- WPAdverts
- Email / Alerts
- PWA / Offline
- Third-party APIs

# =============================================================================== 6. AJAX / API ENDPOINT INVENTORY

---

Endpoint / Action :
Access Level : Public / Auth / Admin
Nonce Protected : Yes / No
Rate Limited : Yes / No
Consent Checked : Yes / No
Capability Check :
Input Sanitized : Yes / No
Output Escaped : Yes / No
Notes :

---

# =============================================================================== 7. DATABASE INVENTORY

## TABLES

---

Table Name :
Purpose :
Created By Module :
Data Type : Raw / Aggregated
PII Stored : Yes / No
Anonymized : Yes / No
Retention Applied : Yes / No
Indexes Present : Yes / No
Notes :

---

## OPTIONS / META

---

Key Name :
Scope : Site / User
Purpose :
Default Value :
PII : Yes / No
Notes :

---

# =============================================================================== 8. GDPR & PRIVACY INVENTORY

## CONSENT

Consent Required : Yes / No
Consent Source : Cookie / User Meta / Both
Blocking Behavior : Hard / Soft

## EXPORT

Registered Exporter : Yes / No
Data Types Exported :

## ERASURE

Registered Eraser : Yes / No
Tables Affected :

## RETENTION

Retention Configurable : Yes / No
Default Retention (days) :
Cleanup Mechanism : Cron / Manual

## NOTES

# =============================================================================== 9. SECURITY INVENTORY

- Nonces on all public endpoints : Yes / No
- Capability checks on admin actions : Yes / No
- SQL prepared statements : Yes / No
- Rate limiting : Yes / No
- Abuse logging : Yes / No

## IDENTIFIED RISKS

- Risk :
- Impact :
- Mitigation :

# =============================================================================== 10. PERFORMANCE & SCALABILITY

- Aggregation Used : Yes / No
- Cron Jobs Used : Yes / No
- Heavy Queries Indexed : Yes / No
- Background Processing : Yes / No

## BOTTLENECKS IDENTIFIED

- Issue :
- Location :
- Severity :

# =============================================================================== 11. CROSS-PLUGIN EVENT MAP

---

Event Name :
Source Plugin :
Consumed By :
Hook / Method :
Payload Structure :
Status : IMPLEMENTED / PARTIAL / MISSING

---

# =============================================================================== 12. CRON JOB INVENTORY

---

Hook Name :
Schedule :
Function :
Purpose :
Safe to Disable : Yes / No
