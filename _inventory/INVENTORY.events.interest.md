# INVENTORY: Apollo Interest & Bookmarks Module

**Audit Date:** 29 de janeiro de 2026
**Auditor:** System Audit (STRICT MODE)
**Module Version:** 3.1.0
**Namespace(s):** `Apollo\Events\Modules`, `Apollo_Events_Manager`
**Scope:** ALL PLUGINS - Deep search completed

---

## 1. 📊 EXECUTIVE SUMMARY

### Compliance Snapshot

| Area                 | Status       | Notes                              |
| -------------------- | ------------ | ---------------------------------- |
| Security             | ✅ COMPLIANT | Nonces, capabilities, sanitization |
| GDPR / Privacy       | ✅ COMPLIANT | User data export, privacy controls |
| Performance          | ✅ COMPLIANT | Indexed queries, caching           |
| Data Integrity       | ✅ COMPLIANT | Prepared statements, validation    |
| Cross-Plugin Support | ✅ COMPLIANT | Works with events, venues, DJs     |

### Overall Verdict: ✅ **FULLY COMPLIANT**

### Interest Features Found

| Feature                | Plugin                | Status    | Integration Level |
| ---------------------- | --------------------- | --------- | ----------------- |
| Event Interest         | apollo-events-manager | ✅ Active | Core              |
| Event Bookmarks        | apollo-events-manager | ✅ Active | Core              |
| Interest Notifications | apollo-events-manager | ✅ Active | Extended          |
| Interest Count Display | apollo-events-manager | ✅ Active | Frontend          |
| User Interest List     | apollo-events-manager | ✅ Active | User Pages        |
| Calendar Integration   | apollo-events-manager | ✅ Active | Extended          |

---

## 2. 📁 FILE INVENTORY

### Apollo Events Manager - Interest Files

| File                                                                                                                                           | Purpose              | Lines | Status    | Critical |
| ---------------------------------------------------------------------------------------------------------------------------------------------- | -------------------- | ----- | --------- | -------- |
| [includes/modules/interest/class-interest-module.php](apollo-events-manager/includes/modules/interest/class-interest-module.php)               | Core interest module | 486   | ✅ Active | ⭐⭐⭐   |
| [includes/modules/interest/class-interest-handler.php](apollo-events-manager/includes/modules/interest/class-interest-handler.php)             | Interest logic       | 312   | ✅ Active | ⭐⭐⭐   |
| [includes/modules/interest/class-interest-notifications.php](apollo-events-manager/includes/modules/interest/class-interest-notifications.php) | Notifications        | 275   | ✅ Active | ⭐⭐     |
| [includes/modules/interest/class-interest-display.php](apollo-events-manager/includes/modules/interest/class-interest-display.php)             | Frontend display     | 186   | ✅ Active | ⭐⭐     |

---

## 3. 🗄️ DATABASE TABLES & META KEYS

### Tables

| Table                    | Purpose          | Indexes                    | Owner         |
| ------------------------ | ---------------- | -------------------------- | ------------- |
| `apollo_event_interest`  | Interest records | user_id, event_id, created | apollo-events |
| `apollo_event_bookmarks` | Bookmark records | user_id, event_id          | apollo-events |

### Event Meta Keys

| Key                      | Type | Purpose              | Owner         |
| ------------------------ | ---- | -------------------- | ------------- |
| `_apollo_interest_count` | int  | Interest count cache | apollo-events |
| `_apollo_bookmark_count` | int  | Bookmark count cache | apollo-events |

### User Meta Keys

| Key                         | Type  | Purpose                 | Owner         |
| --------------------------- | ----- | ----------------------- | ------------- |
| `_apollo_interested_events` | array | Interested event IDs    | apollo-events |
| `_apollo_bookmarked_events` | array | Bookmarked event IDs    | apollo-events |
| `_apollo_interest_notify`   | bool  | Notify on event changes | apollo-events |

### Options

| Key                        | Purpose           | Owner         |
| -------------------------- | ----------------- | ------------- |
| `apollo_interest_settings` | Interest settings | apollo-events |

---

## 4. ⭐ FEATURE-SPECIFIC: Interest Types

### Interest vs Bookmark

| Type       | Description                    | Public | Notifications |
| ---------- | ------------------------------ | ------ | ------------- |
| `interest` | "I'm interested" public signal | Yes    | Yes           |
| `bookmark` | Private save for later         | No     | Optional      |

### Interest Status

| Status       | Description               |
| ------------ | ------------------------- |
| `interested` | User marked as interested |
| `going`      | User confirmed attendance |
| `maybe`      | User marked as maybe      |

### Notification Triggers

| Trigger             | Description              |
| ------------------- | ------------------------ |
| `event_updated`     | Event details changed    |
| `event_cancelled`   | Event was cancelled      |
| `event_rescheduled` | Event date/time changed  |
| `lineup_changed`    | DJ/artist lineup changed |
| `event_reminder`    | 24h/1h before event      |

---

## 5. 🌐 REST API ENDPOINTS

| Endpoint                                  | Method | Auth | Purpose               |
| ----------------------------------------- | ------ | ---- | --------------------- |
| `/apollo/v1/events/{id}/interest`         | POST   | Auth | Mark interested       |
| `/apollo/v1/events/{id}/interest`         | DELETE | Auth | Remove interest       |
| `/apollo/v1/events/{id}/bookmark`         | POST   | Auth | Bookmark event        |
| `/apollo/v1/events/{id}/bookmark`         | DELETE | Auth | Remove bookmark       |
| `/apollo/v1/events/{id}/interested-users` | GET    | Auth | List interested users |
| `/apollo/v1/users/me/interests`           | GET    | Auth | User's interests      |
| `/apollo/v1/users/me/bookmarks`           | GET    | Auth | User's bookmarks      |

---

## 6. 🔌 AJAX ENDPOINTS

| Action                            | Nonce | Capability          | Purpose             |
| --------------------------------- | ----- | ------------------- | ------------------- |
| `apollo_toggle_interest`          | Yes   | `is_user_logged_in` | Toggle interest     |
| `apollo_toggle_bookmark`          | Yes   | `is_user_logged_in` | Toggle bookmark     |
| `apollo_get_interested_users`     | Yes   | `is_user_logged_in` | Get interested list |
| `apollo_get_my_interests`         | Yes   | `is_user_logged_in` | Get user interests  |
| `apollo_get_my_bookmarks`         | Yes   | `is_user_logged_in` | Get user bookmarks  |
| `apollo_update_interest_status`   | Yes   | `is_user_logged_in` | Update status       |
| `apollo_export_interested_events` | Yes   | `is_user_logged_in` | Export to calendar  |

---

## 7. 🎯 ACTION HOOKS

| Hook                             | Trigger          | Parameters                      |
| -------------------------------- | ---------------- | ------------------------------- |
| `apollo_interest_added`          | Interest marked  | `$event_id, $user_id`           |
| `apollo_interest_removed`        | Interest removed | `$event_id, $user_id`           |
| `apollo_bookmark_added`          | Bookmark added   | `$event_id, $user_id`           |
| `apollo_bookmark_removed`        | Bookmark removed | `$event_id, $user_id`           |
| `apollo_interest_status_changed` | Status changed   | `$event_id, $user_id, $status`  |
| `apollo_notify_interested_users` | Notify trigger   | `$event_id, $notification_type` |

---

## 8. 🎨 FILTER HOOKS

| Hook                                 | Purpose                 | Parameters       |
| ------------------------------------ | ----------------------- | ---------------- |
| `apollo_interest_statuses`           | Available statuses      | `$statuses`      |
| `apollo_interest_button_text`        | Button text             | `$text, $status` |
| `apollo_interest_notification_types` | Notification types      | `$types`         |
| `apollo_interest_reminder_times`     | Reminder timing options | `$times`         |
| `apollo_interest_export_formats`     | Calendar export formats | `$formats`       |

---

## 9. 🏷️ SHORTCODES

| Shortcode                   | Purpose                  | Attributes      |
| --------------------------- | ------------------------ | --------------- |
| `[apollo_interest_button]`  | Interest button          | event_id, style |
| `[apollo_bookmark_button]`  | Bookmark button          | event_id        |
| `[apollo_interest_count]`   | Display interest count   | event_id        |
| `[apollo_my_interests]`     | User's interested events | limit, columns  |
| `[apollo_my_bookmarks]`     | User's bookmarked events | limit           |
| `[apollo_interested_users]` | List interested users    | event_id, limit |

---

## 10. 🔧 FUNCTIONS (PHP API)

```php
// Add interest
apollo_add_interest( $event_id, $user_id, $status = 'interested' );

// Remove interest
apollo_remove_interest( $event_id, $user_id );

// Check if interested
apollo_is_interested( $event_id, $user_id );

// Get interest count
apollo_get_interest_count( $event_id );

// Add bookmark
apollo_add_bookmark( $event_id, $user_id );

// Remove bookmark
apollo_remove_bookmark( $event_id, $user_id );

// Check if bookmarked
apollo_is_bookmarked( $event_id, $user_id );

// Get user interests
apollo_get_user_interests( $user_id, $args = [] );

// Get user bookmarks
apollo_get_user_bookmarks( $user_id, $args = [] );

// Get interested users
apollo_get_interested_users( $event_id, $limit = 10 );

// Notify interested users
apollo_notify_interested_users( $event_id, $notification_type, $data = [] );

// Export to calendar
apollo_export_interests_calendar( $user_id, $format = 'ics' );
```

---

## 11. 🔐 SECURITY AUDIT

### Nonce Protection

| Endpoint                 | Nonce Action            | Status |
| ------------------------ | ----------------------- | ------ |
| `apollo_toggle_interest` | `apollo_interest_nonce` | ✅     |
| `apollo_toggle_bookmark` | `apollo_bookmark_nonce` | ✅     |

### Capability Checks

| Action               | Required Capability | Status |
| -------------------- | ------------------- | ------ |
| Add/remove interest  | `is_user_logged_in` | ✅     |
| Add/remove bookmark  | `is_user_logged_in` | ✅     |
| View interested list | `is_user_logged_in` | ✅     |
| Export calendar      | `is_user_logged_in` | ✅     |

### Data Privacy

| Data            | Privacy Level           | Status |
| --------------- | ----------------------- | ------ |
| Interest status | Public (username shown) | ✅     |
| Bookmark status | Private (user only)     | ✅     |
| Interest count  | Public (number only)    | ✅     |

---

## 12. 🎨 FRONTEND ASSETS

### Scripts

| Handle                 | Source                     | Loaded At   |
| ---------------------- | -------------------------- | ----------- |
| `apollo-interest`      | assets/js/interest.js      | Event pages |
| `apollo-bookmark`      | assets/js/bookmark.js      | Event pages |
| `apollo-interest-list` | assets/js/interest-list.js | User pages  |

### Styles

| Handle                 | Source                       | Loaded At   |
| ---------------------- | ---------------------------- | ----------- |
| `apollo-interest`      | assets/css/interest.css      | Event pages |
| `apollo-interest-list` | assets/css/interest-list.css | User pages  |

---

## 13. ⚙️ CONFIGURATION

### Admin Settings

| Option                    | Default | Description                |
| ------------------------- | ------- | -------------------------- |
| `enable_interest`         | true    | Enable interest feature    |
| `enable_bookmarks`        | true    | Enable bookmarks           |
| `show_interest_count`     | true    | Display interest count     |
| `show_interested_users`   | true    | Show interested users list |
| `interest_requires_login` | true    | Require login for interest |
| `notify_on_event_changes` | true    | Notify interested users    |
| `default_reminder_time`   | 24      | Hours before reminder      |

### Cron Jobs

| Hook                            | Schedule | Purpose              |
| ------------------------------- | -------- | -------------------- |
| `apollo_send_event_reminders`   | Hourly   | Send event reminders |
| `apollo_cleanup_past_interests` | Daily    | Clean up past events |

---

## 14. ✅ COMPLIANCE CHECKLIST

- [x] Nonces on all AJAX endpoints
- [x] Capability checks
- [x] SQL prepared statements
- [x] User owns their data
- [x] Bookmark privacy (private)
- [x] Interest visibility controls
- [x] GDPR data export
- [x] Notification preferences
- [x] Calendar export

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
| 2026-01-26 | Added calendar export documentation | ✅     |
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

- Searched all plugins for interest/bookmark functionality
- Confirmed apollo-events-manager as canonical implementation
- Notifications integrate with apollo-social email service
- Calendar export supports ICS and Google Calendar
- No orphan files or dead code found
