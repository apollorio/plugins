# APOLLO-INTEGRATED

AREA = are which part of this ecosystem the code is working or exist, such as CHAT or NOTIFY or CLASSIF or EVENT, or SIGN or DOC, etc..
____________________________________
# PLUGIN NAME | AREA | FUNCTION NAME | WHAT EXPECTED TO DO | WHO INTEGRATE TO HER (in parentheses short really short expected what this function runs them)|
____________________________________

## 01. Plugin: apollo-core

### EMAIL HUB (NEW - Centralized Email Integration)
**Location**: `includes/class-apollo-email-integration.php`
**Status**: ✅ ACTIVE - Full ecosystem integration

| Function | Trigger Hook | What It Does | Integrates With |
|----------|--------------|--------------|-----------------|
| `on_membership_approved()` | `apollo_membership_approved` | Sends approval email to user | Core Memberships (REST: `/apollo/v1/membros/definir`) |
| `on_membership_rejected()` | `apollo_membership_rejected` | Sends rejection email with reason | Core Memberships (rejection action) |
| `on_user_suspended()` | `apollo_user_suspended` | Notifies suspended user | Core Moderation (`/users/suspend`) |
| `on_user_blocked()` | `apollo_user_blocked` | Notifies blocked user | Core Moderation (`/users/block`) |
| `on_content_approved()` | `apollo_content_approved` | Notifies author of approval | Core Moderation (`/mod/approve`) |
| `on_content_rejected()` | `apollo_content_rejected` | Notifies author of rejection with reason | Core Moderation (rejection flow) |
| `on_document_finalized()` | `apollo_document_finalized` | Notifies doc owner finalization complete | Social Documents (`/apollo-docs/v1/document/{id}/finalize`) |
| `on_document_signed()` | `apollo_document_signed` | Notifies doc owner of new signature | Social Signatures (`/apollo-docs/v1/sign/*`) |
| `on_group_invite()` | `apollo_group_invite` | Notifies user of group invitation | Social Groups (`/apollo/v1/comunas/{id}/invite`) |
| `on_group_approved()` | `apollo_group_approved` | Notifies group owner of approval | Social Groups (`/apollo/v1/comunas/{id}/approve`) |
| `on_social_mention()` | `apollo_social_post_mention` | Notifies mentioned user | Social Posts (@ mention parser) |
| `on_event_published()` | `publish_event_listing` | Notifies event author of publication | Events Manager (publish transition) |
| `on_cena_rio_confirmed()` | `apollo_cena_rio_event_confirmed` | Notifies user event moved to mod queue | Core CENA-RIO (`/cena-rio/confirmar/{id}`) |
| `on_cena_rio_approved()` | `apollo_cena_rio_event_approved` | Notifies user CENA-RIO event approved | Core CENA-RIO (`/cena-rio/approve/{id}`) |
| `on_cena_rio_rejected()` | `apollo_cena_rio_event_rejected` | Notifies user CENA-RIO rejection with reason | Core CENA-RIO (`/cena-rio/reject/{id}`) |
| `on_event_reminder()` | `apollo_event_reminder` | Sends 24h reminder to attendees | Events Manager (cron job for reminders) |
| `on_registration_complete()` | `apollo_user_registration_complete` | Sends welcome email to new user | Social Onboarding (`/onboarding/complete`) |
| `on_verification_complete()` | `apollo_user_verification_complete` | Notifies user verification approved | Social Onboarding (`/onboarding/verificar/confirm`) |
| `on_onboarding_complete()` | `apollo_user_onboarding_complete` | Congratulates user on completing onboarding | Social Onboarding (final step) |

**Admin Features**:
- Admin page under Apollo Core Hub → Email Hub
- Shows integration status (Newsletter plugin, Email Templates plugin, wp_mail)
- Displays emails sent today count
- Lists all 17 email templates with triggers
- Rate limiting: 1000 emails/day max
- Admin notice with tooltip showing status
- AJAX handlers for test emails and template editing

**Plugin Connectors**:
- ✅ Newsletter Plugin (apollo-email-newsletter): Optional, uses for advanced campaigns
- ✅ Email Templates Plugin (apollo-email-templates): Optional, uses for visual templates
- ✅ WordPress wp_mail(): Always available as fallback

**Email Templates Available**:
1. Membership Approved/Rejected
2. User Suspended/Blocked
3. Content Approved/Rejected
4. Document Finalized/Signed
5. Group Invite/Approved
6. Event Published/Reminder
7. CENA-RIO Confirmed/Approved/Rejected
8. Registration Welcome
9. Verification Complete
10. Onboarding Complete

**Data Flow Example**:
```
Admin approves membership → 
  apollo_membership_approved hook fires → 
    Email Integration catches → 
      Loads template (subject + body) → 
        Sends via Newsletter/wp_mail → 
          Increments daily counter → 
            Logs success
```

---

## 02. Plugin: apollo-events-manager



## 03. Plugin: apollo-social



## 04. Plugin: apollo-rio





____________________________________


============================================================
# HANDOVER AREA - COPILOT VSCODE CODEX-MAX-5.1 to SELF
Below space for records WITH TIMESTAMP of evaluation progress and all usefull info that we must know.

____________________________________
TIME STAMP | TOPIC | PLUGIN      | REMARK | Status [ GOOD / PERFECT / WEAK / CRITICAL ]
____________________________________
