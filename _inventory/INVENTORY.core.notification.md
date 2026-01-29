# INVENTORY: Apollo Notifications Module

**Audit Date:** 29 de janeiro de 2026
**Auditor:** System Audit (STRICT MODE)
**Module Version:** 3.1.0
**Namespace(s):** `Apollo_Core`, `Apollo\Notifications`, `Apollo\Events\Modules`
**Scope:** ALL PLUGINS - Deep search completed

---

## 1. 📊 EXECUTIVE SUMMARY

### Compliance Snapshot

| Area                 | Status       | Notes                              |
| -------------------- | ------------ | ---------------------------------- |
| Security             | ✅ COMPLIANT | Nonces, capabilities, sanitization |
| GDPR / Privacy       | ✅ COMPLIANT | User opt-in preferences            |
| Performance          | ✅ COMPLIANT | Batch processing, queue system     |
| Data Integrity       | ✅ COMPLIANT | Prepared statements, error logging |
| Cross-Plugin Support | ✅ COMPLIANT | Unified notification system        |

### Overall Verdict: ✅ **FULLY COMPLIANT**

### Notification Features Found

| Feature                  | Plugin                | Status    | Integration Level |
| ------------------------ | --------------------- | --------- | ----------------- |
| In-App Notifications     | apollo-social         | ✅ Active | Core              |
| Push Notifications       | apollo-social         | ✅ Active | Core              |
| Event Reminders          | apollo-events-manager | ✅ Active | Extended          |
| Email Notifications      | apollo-social         | ✅ Active | Extended          |
| Notification Bell        | apollo-social         | ✅ Active | Frontend          |
| Notification Preferences | apollo-social         | ✅ Active | User Settings     |

---

## 2. 📁 FILE INVENTORY

### Apollo Social - Notification Files

| File                                                                                                                         | Purpose                   | Lines | Status    | Critical |
| ---------------------------------------------------------------------------------------------------------------------------- | ------------------------- | ----- | --------- | -------- |
| [src/Modules/Notifications/NotificationService.php](apollo-social/src/Modules/Notifications/NotificationService.php)         | Core notification service | 486   | ✅ Active | ⭐⭐⭐   |
| [src/Modules/Notifications/NotificationRepository.php](apollo-social/src/Modules/Notifications/NotificationRepository.php)   | Database operations       | 312   | ✅ Active | ⭐⭐⭐   |
| [src/Modules/Notifications/PushNotificationHandler.php](apollo-social/src/Modules/Notifications/PushNotificationHandler.php) | Push notification handler | 275   | ✅ Active | ⭐⭐     |
| [user-pages/tabs/class-user-notifications-tab.php](apollo-social/user-pages/tabs/class-user-notifications-tab.php)           | User notification UI      | 420   | ✅ Active | ⭐⭐⭐   |

### Apollo Events Manager - Notification Files

| File                                                                                                                                                 | Purpose             | Lines | Status    | Critical |
| ---------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------- | ----- | --------- | -------- |
| [includes/modules/notifications/class-notifications-module.php](apollo-events-manager/includes/modules/notifications/class-notifications-module.php) | Event notifications | 823   | ✅ Active | ⭐⭐⭐   |
| [includes/modules/notifications/class-event-reminders.php](apollo-events-manager/includes/modules/notifications/class-event-reminders.php)           | Event reminders     | 345   | ✅ Active | ⭐⭐     |

---

## 3. 🗄️ DATABASE TABLES & META KEYS

### Tables

| Table                       | Purpose              | Indexes                   | Owner         |
| --------------------------- | -------------------- | ------------------------- | ------------- |
| `apollo_notifications`      | Notification storage | user_id, is_read, created | apollo-social |
| `apollo_push_subscriptions` | Push subscriptions   | user_id, endpoint         | apollo-social |

### User Meta Keys

| Key                          | Type  | Purpose                    | Owner         |
| ---------------------------- | ----- | -------------------------- | ------------- |
| `_apollo_notification_prefs` | array | Notification preferences   | apollo-social |
| `_apollo_push_enabled`       | bool  | Push notifications enabled | apollo-social |
| `_apollo_last_notification`  | int   | Last notification ID seen  | apollo-social |
| `_apollo_event_reminders`    | array | Event reminder settings    | apollo-events |

### Options

| Key                              | Purpose                    | Owner         |
| -------------------------------- | -------------------------- | ------------- |
| `apollo_notification_settings`   | Global notification config | apollo-social |
| `apollo_push_vapid_keys`         | VAPID keys for push        | apollo-social |
| `apollo_event_reminder_defaults` | Default reminder settings  | apollo-events |

---

## 4. 🔔 FEATURE-SPECIFIC: Notification Types

### Notification Categories

| Type                | Description              | Channel       |
| ------------------- | ------------------------ | ------------- |
| `event_reminder`    | Event reminder           | Push, Email   |
| `event_update`      | Event was updated        | In-app, Push  |
| `event_cancelled`   | Event was cancelled      | All channels  |
| `new_message`       | New chat message         | In-app, Push  |
| `new_follower`      | New follower             | In-app        |
| `group_invite`      | Group invitation         | In-app, Email |
| `document_request`  | Signature request        | In-app, Email |
| `membership_update` | Membership status change | Email         |

### Reminder Timing Options

| Option    | Value | Description       |
| --------- | ----- | ----------------- |
| `15_min`  | 15    | 15 minutes before |
| `30_min`  | 30    | 30 minutes before |
| `1_hour`  | 60    | 1 hour before     |
| `3_hours` | 180   | 3 hours before    |
| `1_day`   | 1440  | 1 day before      |
| `1_week`  | 10080 | 1 week before     |

---

## 5. 🌐 REST API ENDPOINTS

| Endpoint                             | Method | Auth | Purpose                |
| ------------------------------------ | ------ | ---- | ---------------------- |
| `/apollo/v1/notifications`           | GET    | Yes  | Get user notifications |
| `/apollo/v1/notifications/{id}/read` | POST   | Yes  | Mark as read           |
| `/apollo/v1/notifications/read-all`  | POST   | Yes  | Mark all as read       |
| `/apollo/v1/notifications/count`     | GET    | Yes  | Get unread count       |
| `/apollo/v1/push/subscribe`          | POST   | Yes  | Subscribe to push      |
| `/apollo/v1/push/unsubscribe`        | POST   | Yes  | Unsubscribe from push  |

---

## 6. 🔌 AJAX ENDPOINTS

| Action                               | Nonce | Capability          | Purpose             |
| ------------------------------------ | ----- | ------------------- | ------------------- |
| `apollo_get_notifications`           | Yes   | `is_user_logged_in` | Get notifications   |
| `apollo_mark_notification_read`      | Yes   | `is_user_logged_in` | Mark as read        |
| `apollo_mark_all_notifications_read` | Yes   | `is_user_logged_in` | Mark all read       |
| `apollo_delete_notification`         | Yes   | `is_user_logged_in` | Delete notification |
| `apollo_save_notification_prefs`     | Yes   | `is_user_logged_in` | Save preferences    |
| `apollo_subscribe_push`              | Yes   | `is_user_logged_in` | Subscribe to push   |
| `apollo_set_event_reminder`          | Yes   | `is_user_logged_in` | Set event reminder  |

---

## 7. 🎯 ACTION HOOKS

| Hook                                      | Trigger                  | Parameters                   |
| ----------------------------------------- | ------------------------ | ---------------------------- |
| `apollo_notification_created`             | Notification created     | `$notification_id, $user_id` |
| `apollo_notification_sent`                | Notification sent        | `$notification, $channel`    |
| `apollo_push_sent`                        | Push notification sent   | `$user_id, $payload`         |
| `apollo_reminder_triggered`               | Event reminder triggered | `$event_id, $user_id`        |
| `apollo_notification_preferences_updated` | Prefs updated            | `$user_id, $preferences`     |

---

## 8. 🎨 FILTER HOOKS

| Hook                           | Purpose                 | Parameters                |
| ------------------------------ | ----------------------- | ------------------------- |
| `apollo_notification_channels` | Available channels      | `$channels`               |
| `apollo_notification_types`    | Available types         | `$types`                  |
| `apollo_push_payload`          | Modify push payload     | `$payload, $notification` |
| `apollo_reminder_options`      | Reminder timing options | `$options`                |
| `apollo_notification_template` | Notification template   | `$template, $type`        |

---

## 9. 🏷️ SHORTCODES

| Shortcode                           | Purpose                | Attributes  |
| ----------------------------------- | ---------------------- | ----------- |
| `[apollo_notification_bell]`        | Notification bell icon | style       |
| `[apollo_notification_list]`        | Notification list      | limit, type |
| `[apollo_notification_preferences]` | Preferences form       | -           |

---

## 10. 🔧 FUNCTIONS (PHP API)

```php
// Send notification
NotificationService::send( $user_id, $type, $message, $data = [] );

// Send push notification
PushNotificationHandler::send( $user_id, $title, $body, $data = [] );

// Get user notifications
NotificationRepository::get_for_user( $user_id, $limit = 20, $offset = 0 );

// Get unread count
NotificationRepository::get_unread_count( $user_id );

// Mark as read
NotificationRepository::mark_read( $notification_id );

// Check notification preference
NotificationService::user_wants_notification( $user_id, $type, $channel );

// Set event reminder
apollo_set_event_reminder( $event_id, $user_id, $minutes_before );
```

---

## 11. 🔐 SECURITY AUDIT

### Nonce Protection

| Endpoint                         | Nonce Action                 | Status |
| -------------------------------- | ---------------------------- | ------ |
| `apollo_get_notifications`       | `apollo_notifications_nonce` | ✅     |
| `apollo_save_notification_prefs` | `apollo_prefs_nonce`         | ✅     |
| `apollo_subscribe_push`          | `apollo_push_nonce`          | ✅     |

### Capability Checks

| Action             | Required Capability | Status |
| ------------------ | ------------------- | ------ |
| View notifications | `is_user_logged_in` | ✅     |
| Manage preferences | `is_user_logged_in` | ✅     |
| Subscribe push     | `is_user_logged_in` | ✅     |

### Data Ownership

| Operation            | Validation               | Status |
| -------------------- | ------------------------ | ------ |
| View notifications   | User can only see own    | ✅     |
| Mark as read         | User can only mark own   | ✅     |
| Delete notifications | User can only delete own | ✅     |

---

## 12. 🎨 FRONTEND ASSETS

### Scripts

| Handle                     | Source                           | Loaded At |
| -------------------------- | -------------------------------- | --------- |
| `apollo-notifications`     | assets/js/notifications.js       | Frontend  |
| `apollo-push-worker`       | assets/js/push-service-worker.js | Global    |
| `apollo-notification-bell` | assets/js/notification-bell.js   | Header    |

### Styles

| Handle                     | Source                           | Loaded At |
| -------------------------- | -------------------------------- | --------- |
| `apollo-notifications`     | assets/css/notifications.css     | Frontend  |
| `apollo-notification-bell` | assets/css/notification-bell.css | Header    |

---

## 13. ⚙️ CONFIGURATION

### Admin Settings

| Option                        | Default | Description                |
| ----------------------------- | ------- | -------------------------- |
| `enable_push_notifications`   | true    | Enable push notifications  |
| `push_vapid_public_key`       | ''      | VAPID public key           |
| `push_vapid_private_key`      | ''      | VAPID private key          |
| `notification_retention_days` | 30      | Days to keep notifications |
| `batch_notifications`         | true    | Enable batching            |

### Cron Jobs

| Hook                                | Schedule     | Purpose                  |
| ----------------------------------- | ------------ | ------------------------ |
| `apollo_send_event_reminders`       | Every minute | Send due reminders       |
| `apollo_cleanup_notifications`      | Daily        | Delete old notifications |
| `apollo_process_notification_queue` | Every minute | Process queued notifs    |

---

## 14. ✅ COMPLIANCE CHECKLIST

- [x] Nonces on all AJAX endpoints
- [x] Capability checks
- [x] User data ownership validation
- [x] SQL prepared statements
- [x] Opt-in preferences
- [x] Per-channel controls
- [x] VAPID key security
- [x] Push subscription management
- [x] Notification retention policy

---

## 15. 🚫 GAPS & RECOMMENDATIONS

### 15a. Possible Gaps

No gaps identified for this module.

### 15b. Errors / Problems / Warnings

No errors or warnings documented.

---

## 16. 📋 CHANGE LOG

| Date       | Change                              | Status |
| ---------- | ----------------------------------- | ------ |
| 2026-01-26 | Initial comprehensive audit         | ✅     |
| 2026-01-26 | Added push notification support     | ✅     |
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

- Searched all plugins for notification-related functionality
- Confirmed apollo-social as canonical implementation
- Event reminders in apollo-events bridge to core notification service
- Push notification VAPID keys properly secured
- No orphan files or dead code found
