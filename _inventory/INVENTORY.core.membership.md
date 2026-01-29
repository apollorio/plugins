# INVENTORY: Apollo Membership Module

**Audit Date:** 29 de janeiro de 2026
**Auditor:** System Audit (STRICT MODE)
**Module Version:** 3.1.0
**Namespace(s):** `Apollo_Core`, `Apollo\Membership`, `Apollo\Admin`, `Apollo\CulturaRio`
**Scope:** ALL PLUGINS - Deep search completed

---

## 1. 📊 EXECUTIVE SUMMARY

### Compliance Snapshot

| Area                 | Status       | Notes                                |
| -------------------- | ------------ | ------------------------------------ |
| Security             | ✅ COMPLIANT | Nonces, capabilities, admin-only ops |
| GDPR / Privacy       | ✅ COMPLIANT | Data export/erasure, consent forms   |
| Performance          | ✅ COMPLIANT | Pagination, caching                  |
| Data Integrity       | ✅ COMPLIANT | Prepared statements, audit trail     |
| Cross-Plugin Support | ✅ COMPLIANT | Unified membership across plugins    |

### Overall Verdict: ✅ **FULLY COMPLIANT**

### Membership Features Found

| Feature                    | Plugin        | Status    | Integration Level |
| -------------------------- | ------------- | --------- | ----------------- |
| Membership Approval Queue  | apollo-core   | ✅ Active | Core              |
| CulturaRio Requirements    | apollo-core   | ✅ Active | Core              |
| User Preferences Storage   | apollo-social | ✅ Active | Core              |
| Manual Verification Admin  | apollo-core   | ✅ Active | Admin             |
| Membership Rejection       | apollo-core   | ✅ Active | Admin             |
| User Identity Verification | apollo-core   | ✅ Active | Security          |
| Email Integration          | apollo-social | ✅ Active | Extended          |

---

## 2. 📁 FILE INVENTORY

### Apollo Core - Membership Files

| File                                                                                                         | Purpose                  | Lines | Status    | Critical |
| ------------------------------------------------------------------------------------------------------------ | ------------------------ | ----- | --------- | -------- |
| [admin/class-apollo-membership-admin.php](apollo-core/admin/class-apollo-membership-admin.php)               | Membership admin panel   | 823   | ✅ Active | ⭐⭐⭐   |
| [admin/class-apollo-user-verification-page.php](apollo-core/admin/class-apollo-user-verification-page.php)   | User verification page   | 640   | ✅ Active | ⭐⭐⭐   |
| [includes/class-apollo-membership-handler.php](apollo-core/includes/class-apollo-membership-handler.php)     | Membership handler logic | 476   | ✅ Active | ⭐⭐⭐   |
| [includes/class-cultura-rio-requirements.php](apollo-core/includes/class-cultura-rio-requirements.php)       | CulturaRio verification  | 350   | ✅ Active | ⭐⭐⭐   |
| [includes/class-apollo-membership-approval.php](apollo-core/includes/class-apollo-membership-approval.php)   | Approval workflow        | 220   | ✅ Active | ⭐⭐     |
| [includes/class-apollo-membership-rejection.php](apollo-core/includes/class-apollo-membership-rejection.php) | Rejection handling       | 180   | ✅ Active | ⭐⭐     |

### Apollo Social - Membership Files

| File                                                                                                           | Purpose             | Lines | Status    | Critical |
| -------------------------------------------------------------------------------------------------------------- | ------------------- | ----- | --------- | -------- |
| [user-pages/tabs/class-user-preferences-tab.php](apollo-social/user-pages/tabs/class-user-preferences-tab.php) | User preferences UI | 486   | ✅ Active | ⭐⭐⭐   |
| [src/Modules/Membership/MembershipService.php](apollo-social/src/Modules/Membership/MembershipService.php)     | Membership service  | 210   | ✅ Active | ⭐⭐     |

---

## 3. 🗄️ DATABASE TABLES & META KEYS

### Tables

| Table                     | Purpose                | Indexes           | Owner       |
| ------------------------- | ---------------------- | ----------------- | ----------- |
| `apollo_membership_queue` | Approval queue         | status, user_id   | apollo-core |
| `apollo_verification_log` | Verification audit log | user_id, admin_id | apollo-core |

### User Meta Keys

| Key                            | Type     | Purpose                  | Owner         |
| ------------------------------ | -------- | ------------------------ | ------------- |
| `_apollo_membership_status`    | string   | Membership status        | apollo-core   |
| `_apollo_membership_approved`  | datetime | Approval date            | apollo-core   |
| `_apollo_membership_rejected`  | datetime | Rejection date           | apollo-core   |
| `_apollo_rejection_reason`     | string   | Rejection reason         | apollo-core   |
| `_apollo_user_identities`      | array    | User identities verified | apollo-core   |
| `_apollo_cultura_rio_verified` | bool     | CulturaRio verification  | apollo-core   |
| `_apollo_user_preferences`     | array    | User preferences         | apollo-social |

### Options

| Key                                  | Purpose                  | Owner       |
| ------------------------------------ | ------------------------ | ----------- |
| `apollo_membership_settings`         | Membership settings      | apollo-core |
| `apollo_cultura_rio_config`          | CulturaRio configuration | apollo-core |
| `apollo_membership_approval_enabled` | Enable approval workflow | apollo-core |

---

## 4. 🎫 FEATURE-SPECIFIC: CulturaRio Requirements

### Identity Types

| Type       | Description     | Required Documents      |
| ---------- | --------------- | ----------------------- |
| `produtor` | Event Producer  | CPF, Production License |
| `artista`  | Artist/DJ       | CPF, Portfolio          |
| `venue`    | Venue Owner     | CNPJ, Venue License     |
| `standard` | Standard Member | Email Verification      |

### Verification Status

| Status      | Description           | Color  |
| ----------- | --------------------- | ------ |
| `pending`   | Awaiting admin review | Yellow |
| `approved`  | Membership approved   | Green  |
| `rejected`  | Membership rejected   | Red    |
| `suspended` | Temporarily suspended | Orange |

---

## 5. 🌐 REST API ENDPOINTS

No dedicated REST endpoints documented for this module. Membership is managed via admin screens and AJAX.

---

## 6. 🔌 AJAX ENDPOINTS

| Action                         | Nonce | Capability       | Purpose            |
| ------------------------------ | ----- | ---------------- | ------------------ |
| `apollo_approve_membership`    | Yes   | `manage_options` | Approve membership |
| `apollo_reject_membership`     | Yes   | `manage_options` | Reject membership  |
| `apollo_request_verification`  | Yes   | `manage_options` | Request more info  |
| `apollo_save_user_preferences` | Yes   | `read`           | Save user prefs    |
| `apollo_verify_cultura_rio`    | Yes   | `manage_options` | Verify CulturaRio  |
| `apollo_membership_queue_load` | Yes   | `manage_options` | Load queue data    |

---

## 7. 🎯 ACTION HOOKS

| Hook                          | Trigger                  | Parameters                         |
| ----------------------------- | ------------------------ | ---------------------------------- |
| `apollo_membership_approved`  | Membership approved      | `$user_id, $identities, $admin_id` |
| `apollo_membership_rejected`  | Membership rejected      | `$user_id, $reason, $admin_id`     |
| `apollo_membership_suspended` | Membership suspended     | `$user_id, $reason, $admin_id`     |
| `apollo_cultura_rio_verified` | CulturaRio verified      | `$user_id, $identity_type`         |
| `apollo_preferences_updated`  | User preferences updated | `$user_id, $preferences`           |

---

## 8. 🎨 FILTER HOOKS

| Hook                             | Purpose                     | Parameters        |
| -------------------------------- | --------------------------- | ----------------- |
| `apollo_membership_statuses`     | Customize status list       | `$statuses`       |
| `apollo_identity_types`          | Customize identity types    | `$types`          |
| `apollo_membership_capabilities` | Modify member capabilities  | `$caps, $user_id` |
| `apollo_verification_fields`     | Customize verification form | `$fields`         |

---

## 9. 🏷️ SHORTCODES

| Shortcode                    | Purpose               | Attributes |
| ---------------------------- | --------------------- | ---------- |
| `[apollo_membership_status]` | Display user status   | user_id    |
| `[apollo_verification_form]` | Verification form     | -          |
| `[apollo_user_preferences]`  | User preferences form | -          |

---

## 10. 🔧 FUNCTIONS (PHP API)

```php
// Check if user is approved member
apollo_is_member_approved( $user_id );

// Get membership status
apollo_get_membership_status( $user_id );

// Approve membership
apollo_approve_membership( $user_id, $admin_id, $identities = [] );

// Reject membership
apollo_reject_membership( $user_id, $admin_id, $reason );

// Check CulturaRio verification
apollo_is_cultura_rio_verified( $user_id );

// Get user preferences
apollo_get_user_preferences( $user_id );

// Save user preferences
apollo_save_user_preferences( $user_id, $preferences );
```

---

## 11. 🔐 SECURITY AUDIT

### Nonce Protection

| Endpoint                       | Nonce Action               | Status |
| ------------------------------ | -------------------------- | ------ |
| `apollo_approve_membership`    | `apollo_membership_nonce`  | ✅     |
| `apollo_reject_membership`     | `apollo_membership_nonce`  | ✅     |
| `apollo_save_user_preferences` | `apollo_preferences_nonce` | ✅     |

### Capability Checks

| Action                 | Required Capability | Status |
| ---------------------- | ------------------- | ------ |
| View membership queue  | `manage_options`    | ✅     |
| Approve/reject members | `manage_options`    | ✅     |
| Edit user preferences  | `read` (own data)   | ✅     |

### Audit Trail

| Event             | Logged Data                          |
| ----------------- | ------------------------------------ |
| Approval          | User ID, Admin ID, Timestamp         |
| Rejection         | User ID, Admin ID, Reason, Timestamp |
| Preference Change | User ID, Old/New Values, Timestamp   |

---

## 12. 🎨 FRONTEND ASSETS

### Scripts

| Handle                    | Source                        | Loaded At  |
| ------------------------- | ----------------------------- | ---------- |
| `apollo-membership-admin` | assets/js/membership-admin.js | Admin      |
| `apollo-preferences`      | assets/js/preferences.js      | User pages |

### Styles

| Handle                    | Source                          | Loaded At  |
| ------------------------- | ------------------------------- | ---------- |
| `apollo-membership-admin` | assets/css/membership-admin.css | Admin      |
| `apollo-preferences`      | assets/css/preferences.css      | User pages |

---

## 13. ⚙️ CONFIGURATION

### Admin Settings

| Option                         | Default | Description                  |
| ------------------------------ | ------- | ---------------------------- |
| `membership_approval_required` | true    | Require admin approval       |
| `cultura_rio_enabled`          | true    | Enable CulturaRio            |
| `auto_approve_email_verified`  | false   | Auto-approve verified emails |
| `membership_queue_per_page`    | 20      | Queue pagination             |

### Email Templates

| Template                    | Trigger              |
| --------------------------- | -------------------- |
| `membership_approved`       | On approval          |
| `membership_rejected`       | On rejection         |
| `membership_pending_review` | On submission        |
| `cultura_rio_verified`      | On CulturaRio verify |

---

## 14. ✅ COMPLIANCE CHECKLIST

- [x] Nonces on all AJAX endpoints
- [x] Capability checks (admin-only for approvals)
- [x] SQL prepared statements
- [x] Audit trail for all membership changes
- [x] GDPR data export support
- [x] GDPR data erasure support
- [x] User preference controls
- [x] Email notifications for status changes
- [x] Admin rejection reasons required

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
| 2026-01-26 | Added CulturaRio verification flow  | ✅     |
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

- Searched all plugins for membership-related functionality
- Confirmed apollo-core as canonical implementation
- CulturaRio verification integrated properly
- User preferences stored in apollo-social but referenced everywhere
- No orphan files or dead code found
