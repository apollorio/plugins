# APOLLO CORE PLUGIN INVENTORY

**Audit Date:** 25 de janeiro de 2026 (Deep Audit - Service Discovery Implementation)
**Plugin Version:** 3.1.0
**WordPress Required:** 6.4+
**PHP Required:** 8.1+
**Dependencies:** None (base plugin)
**Last Deep Audit:** 25 de janeiro de 2026 - Service Discovery & Data Retention Updates

---

## 01.b.1 - Plugin Identification

- **Name:** Apollo Core
- **Version:** 1.0.0
- **Author:** Apollo Team
- **Main File:** apollo-core.php
- **Text Domain:** apollo-core
- **Requires Plugins:** None
- **Namespace:** `Apollo_Core`
- **Source of Truth:** `includes/class-apollo-identifiers.php`

## 01.b.1.1 - Core Classes & Interfaces

> **Source:** File structure analysis, class definitions

### Service Discovery (Strategy Pattern)

| Class/Interface                   | File                                              | Purpose                              | Since |
| --------------------------------- | ------------------------------------------------- | ------------------------------------ | ----- |
| `CPT_Provider_Strategy_Interface` | `includes/class-apollo-service-discovery.php:19`  | Strategy interface for CPT providers | 3.1.0 |
| `Remote_Manager_Strategy`         | `includes/class-apollo-service-discovery.php:52`  | Delegates to apollo-events-manager   | 3.1.0 |
| `Local_Fallback_Strategy`         | `includes/class-apollo-service-discovery.php:120` | Local registration fallback          | 3.1.0 |
| `CPT_Service_Discovery_Factory`   | `includes/class-apollo-service-discovery.php:178` | Factory for strategy selection       | 3.1.0 |

### Registry & Management

| Class                          | File                                              | Purpose                                     | Since |
| ------------------------------ | ------------------------------------------------- | ------------------------------------------- | ----- |
| `Apollo_CPT_Registry`          | `includes/class-apollo-cpt-registry.php`          | Centralized CPT registration with ownership | 2.0.0 |
| `Apollo_Activation_Controller` | `includes/class-apollo-activation-controller.php` | Plugin lifecycle management                 | 2.0.0 |
| `Apollo_Identifiers`           | `includes/class-apollo-identifiers.php`           | Constants and identifiers                   | 3.0.0 |

### Communication & Email

| Class                  | File                                                   | Purpose                           | Since |
| ---------------------- | ------------------------------------------------------ | --------------------------------- | ----- |
| `Apollo_Email_Manager` | `includes/communication/email/class-email-manager.php` | Email queue with locking/batching | 2.0.0 |

## 01.b.2 - Custom Post Types (CPTs)

> **Source:** `includes/class-apollo-identifiers.php` lines 54-66, Constants `CPT_*`

| CPT Slug              | Constant            | Rewrite Slug   | Registered In \\                                 | Canonical Owner       |
| --------------------- | ------------------- | -------------- | ------------------------------------------------ | --------------------- |
| event_listing         | CPT_EVENT_LISTING   | evento         | modules/events/bootstrap.php:91                  | apollo-events-manager |
| event_dj              | CPT_EVENT_DJ        | dj             | modules/events/bootstrap.php                     | apollo-events-manager |
| event_local           | CPT_EVENT_LOCAL     | local          | modules/events/bootstrap.php                     | apollo-events-manager |
| apollo_classified     | CPT_CLASSIFIED      | anuncio        | modules/social/bootstrap.php                     | apollo-social         |
| apollo_supplier       | CPT_SUPPLIER        | supplier       | modules/social/bootstrap.php                     | apollo-social         |
| apollo_social_post    | CPT_SOCIAL_POST     | post-social    | modules/social/bootstrap.php:104                 | apollo-social         |
| user_page             | CPT_USER_PAGE       | user-page      | modules/social/bootstrap.php:139                 | apollo-social         |
| apollo_document       | CPT_DOCUMENT        | document       | modules/social/                                  | apollo-social         |
| cena_document         | CPT_CENA_DOCUMENT   | cena-document  | includes/                                        | apollo-core           |
| cena_event_plan       | CPT_CENA_EVENT_PLAN | cena-plan      | includes/                                        | apollo-core           |
| apollo_email_template | CPT_EMAIL_TEMPLATE  | email-template | includes/class-apollo-email-templates-cpt.php:42 | apollo-core           |
| apollo_event_stat     | CPT_EVENT_STAT      | event-stat     | includes/                                        | apollo-core           |
| apollo_home_section   | CPT_HOME_SECTION    | home-section   | includes/                                        | apollo-core           |

**✅ RESOLVED:** Service Discovery implemented in 3.1.0. `event_listing`, `event_dj`, `event_local`, `apollo_social_post`, `user_page` now use strategy pattern with passive provider discovery. No more duplicity risk.

## 01.b.3 - Taxonomies

> **Source:** `includes/class-apollo-identifiers.php` lines 85-112, Constants `TAX_*`

| Taxonomy Slug                | Constant                  | Rewrite Slug           | Linked CPTs                      | Hierarchical |
| ---------------------------- | ------------------------- | ---------------------- | -------------------------------- | ------------ |
| event_listing_category       | TAX_EVENT_CATEGORY        | categoria-evento       | event_listing                    | true         |
| event_listing_type           | TAX_EVENT_TYPE            | tipo-evento            | event_listing                    | false        |
| event_listing_tag            | TAX_EVENT_TAG             | tag-evento             | event_listing                    | false        |
| event_sounds                 | TAX_EVENT_SOUNDS          | som                    | event_listing, event_dj          | false        |
| event_season                 | TAX_EVENT_SEASON          | temporada              | event_listing, apollo_classified | true         |
| classified_domain            | TAX_CLASSIFIED_DOMAIN     | tipo                   | apollo_classified                | false        |
| classified_intent            | TAX_CLASSIFIED_INTENT     | intencao               | apollo_classified                | false        |
| apollo_supplier_category     | TAX_SUPPLIER_CATEGORY     | categoria-fornecedor   | apollo_supplier                  | true         |
| apollo_supplier_region       | TAX_SUPPLIER_REGION       | regiao-fornecedor      | apollo_supplier                  | false        |
| apollo_supplier_neighborhood | TAX_SUPPLIER_NEIGHBORHOOD | bairro-fornecedor      | apollo_supplier                  | false        |
| apollo_supplier_event_type   | TAX_SUPPLIER_EVENT_TYPE   | tipo-evento-fornecedor | apollo_supplier                  | false        |
| apollo_supplier_type         | TAX_SUPPLIER_TYPE         | tipo-fornecedor        | apollo_supplier                  | false        |
| apollo_supplier_mode         | TAX_SUPPLIER_MODE         | modalidade-fornecedor  | apollo_supplier                  | false        |
| apollo_supplier_badge        | TAX_SUPPLIER_BADGE        | selo-fornecedor        | apollo_supplier                  | false        |

**Registry:** `includes/class-apollo-taxonomy-registry.php` - Centralized registration via `apollo_register_taxonomy()` at line 680

## 01.b.4 - Meta Keys / Post Meta

> **Source:** `includes/class-apollo-identifiers.php` lines 230-249, Constants `META_*`

### Event Meta Keys (Prefix: `_event_`)

| Meta Key           | Type   | REST | File Location                               |
| ------------------ | ------ | ---- | ------------------------------------------- |
| \_event_start_date | string | Yes  | includes/class-apollo-ajax-handler.php:394  |
| \_event_end_date   | string | Yes  | includes/class-apollo-ajax-handler.php:1005 |
| \_event_start_time | string | Yes  | includes/class-apollo-ajax-handler.php:407  |
| \_event_end_time   | string | Yes  | includes/class-apollo-ajax-handler.php:1006 |
| \_event_venue      | string | Yes  | includes/class-apollo-ajax-handler.php:999  |
| \_event_address    | string | Yes  | includes/class-apollo-ajax-handler.php:1007 |
| \_event_city       | string | Yes  | includes/class-apollo-ajax-handler.php:1000 |
| \_event_price      | float  | Yes  | includes/class-apollo-ajax-handler.php:1008 |
| \_event_organizer  | string | Yes  | includes/class-apollo-native-seo.php:461    |

### Classified Meta Keys (Prefix: `_classified_`)

| Meta Key               | Type   | REST | File Location                               |
| ---------------------- | ------ | ---- | ------------------------------------------- |
| \_classified_price     | float  | Yes  | includes/class-apollo-ajax-handler.php:1085 |
| \_classified_type      | string | Yes  | includes/class-apollo-ajax-handler.php:1095 |
| \_classified_location  | string | Yes  | includes/class-apollo-ajax-handler.php:1096 |
| \_classified_condition | string | No   | includes/class-apollo-ajax-handler.php:1101 |
| \_classified_phone     | string | No   | includes/class-apollo-ajax-handler.php:1102 |
| \_classified_whatsapp  | string | No   | includes/class-apollo-ajax-handler.php:1103 |
| \_classified_views     | int    | No   | includes/class-apollo-ajax-handler.php:893  |

### Document Meta Keys (Prefix: `_apollo_doc_`)

| Meta Key                     | Constant                   | Type   |
| ---------------------------- | -------------------------- | ------ |
| \_apollo_doc_signatures      | META_DOC_SIGNATURES        | array  |
| \_apollo_document_signatures | META_DOC_SIGNATURES_LEGACY | array  |
| \_apollo_doc_state           | META_DOC_STATE             | string |
| \_apollo_doc_pdf_id          | META_DOC_PDF_ID            | int    |
| \_apollo_doc_hash            | META_DOC_HASH              | string |
| \_apollo_doc_file_id         | META_DOC_FILE_ID           | int    |

### Email Template Meta Keys

| Meta Key                   | File Location                                     |
| -------------------------- | ------------------------------------------------- |
| \_apollo_template_slug     | includes/class-apollo-email-templates-cpt.php:105 |
| \_apollo_flow_default      | includes/class-apollo-email-templates-cpt.php:106 |
| \_apollo_template_language | includes/class-apollo-email-templates-cpt.php:107 |

### SEO Meta Keys

| Meta Key                 | File Location                            |
| ------------------------ | ---------------------------------------- |
| \_apollo_seo_title       | includes/class-apollo-native-seo.php:649 |
| \_apollo_seo_description | includes/class-apollo-native-seo.php:650 |
| \_apollo_seo_noindex     | includes/class-apollo-native-seo.php:651 |
| \_apollo_seo_canonical   | includes/class-apollo-native-seo.php:652 |
| \_apollo_seo_image       | includes/class-apollo-native-seo.php:538 |

### Cross-Module Meta

| Meta Key                  | Purpose               | File Location                                          |
| ------------------------- | --------------------- | ------------------------------------------------------ |
| \_apollo_auto_social_post | Auto-post to social   | includes/class-apollo-cross-module-integration.php:124 |
| \_apollo_social_post_id   | Linked social post ID | includes/class-apollo-cross-module-integration.php:130 |
| \_dj_user_id              | DJ linked user        | includes/class-apollo-alignment-bridge.php:356         |

## 01.b.5 - User Meta Keys

> **Source:** grep_search `get_user_meta|update_user_meta` - 50+ matches

| Meta Key                  | Type   | Example Values      | File Location                                     |
| ------------------------- | ------ | ------------------- | ------------------------------------------------- |
| \_apollo_instagram_id     | string | Instagram handle    | templates/apollo-user-dashboard-functions.php     |
| \_apollo_suspended_until  | string | datetime            | admin/class-apollo-unified-control-panel.php:1828 |
| \_apollo_badges           | array  | User badges         | admin/moderate-users-membership.php:196           |
| \_apollo_blocked          | bool   | Blocked status      | tests/test-rest-moderation.php:180                |
| user_role_display         | string | Display role name   | templates/apollo-user-dashboard-functions.php:47  |
| description               | string | Bio text            | templates/hero.php:10                             |
| user_location             | string | City/region         | templates/hero.php:11                             |
| verified                  | bool   | Verification status | templates/hero.php:12                             |
| privacy_profile           | string | public/private      | templates/tab-settings.php:11                     |
| notify_events             | string | yes/no              | templates/tab-settings.php:12                     |
| notify_messages           | string | yes/no              | templates/tab-settings.php:13                     |
| cover_image               | int    | Attachment ID       | templates/inc/apollo-template-functions.php:282   |
| custom_avatar             | int    | Attachment ID       | templates/inc/apollo-template-functions.php:1518  |
| interests                 | array  | User interests      | templates/inc/apollo-template-functions.php:1487  |
| apollo_points             | int    | Gamification points | templates/inc/apollo-template-functions.php:1269  |
| onboarding_step           | int    | Current step        | templates/inc/apollo-template-functions.php:1449  |
| onboarding_complete       | bool   | Completion status   | templates/inc/apollo-template-functions.php:1466  |
| apollo_preferred_language | string | pt-BR, en-US        | templates/partials/navbar.php:19                  |
| nucleo*role*{ID}          | string | Role per nucleo     | templates/tab-nucleos.php:19                      |
| last_activity             | string | Timestamp           | templates/inc/apollo-template-functions.php:1007  |
| cpf                       | string | Brazilian CPF       | templates/doc-signature-panel.php:29              |
| instagram                 | string | Social link         | templates/inc/apollo-template-functions.php:302   |
| twitter                   | string | Social link         | templates/inc/apollo-template-functions.php:303   |
| facebook                  | string | Social link         | templates/inc/apollo-template-functions.php:304   |
| linkedin                  | string | Social link         | templates/inc/apollo-template-functions.php:305   |
| soundcloud                | string | Social link         | templates/inc/apollo-template-functions.php:306   |

## 01.b.6 - Custom Database Tables

> **Source:** grep_search `CREATE TABLE` - 25 matches | Constants in `class-apollo-identifiers.php` lines 118-151

### Core Tables (activation-controller)

| Table Name           | Constant            | Schema                                   | Created In                                          |
| -------------------- | ------------------- | ---------------------------------------- | --------------------------------------------------- |
| apollo_activity_log  | TABLE_ACTIVITY_LOG  | id, user_id, action, data, created_at    | includes/class-apollo-activation-controller.php:213 |
| apollo_relationships | TABLE_RELATIONSHIPS | id, user_id, related_id, type, status    | includes/class-apollo-activation-controller.php:232 |
| apollo_event_queue   | TABLE_EVENT_QUEUE   | id, event_type, data, status, created_at | includes/class-apollo-activation-controller.php:250 |

### Newsletter Tables

| Table Name                    | Created In                                      | Purpose     |
| ----------------------------- | ----------------------------------------------- | ----------- |
| apollo_newsletter_subscribers | includes/class-apollo-native-newsletter.php:108 | Subscribers |
| apollo_newsletter_campaigns   | includes/class-apollo-native-newsletter.php:127 | Campaigns   |

### Analytics Tables (db-schema.php)

| Table Name                     | Schema Location            | Purpose                |
| ------------------------------ | -------------------------- | ---------------------- |
| apollo_mod_log                 | includes/db-schema.php:31  | Moderation logs        |
| apollo_audit_log               | includes/db-schema.php:51  | Security audit         |
| apollo_analytics_pageviews     | includes/db-schema.php:76  | Page view tracking     |
| apollo_analytics_interactions  | includes/db-schema.php:110 | User interactions      |
| apollo_analytics_sessions      | includes/db-schema.php:140 | Session data           |
| apollo_analytics_user_stats    | includes/db-schema.php:176 | Per-user statistics    |
| apollo_analytics_content_stats | includes/db-schema.php:204 | Per-content statistics |
| apollo_analytics_heatmap       | includes/db-schema.php:230 | Heatmap data           |
| apollo_analytics_settings      | includes/db-schema.php:249 | Stats visibility       |

### Communication Tables

| Table Name                      | Created In                                                             | Purpose          |
| ------------------------------- | ---------------------------------------------------------------------- | ---------------- |
| apollo_email_queue              | includes/communication/email/class-email-manager.php:55                | Email queue      |
| apollo_email_log                | includes/communication/email/class-email-manager.php:78                | Email log        |
| apollo_email_security_log       | includes/class-email-security-log.php:86                               | Security logging |
| apollo_notifications            | includes/communication/notifications/class-notification-manager.php:44 | Notifications    |
| apollo_notification_preferences | includes/communication/notifications/class-notification-manager.php:66 | Preferences      |
| apollo_form_submissions         | includes/communication/forms/class-form-manager.php:45                 | Form submissions |
| apollo_form_analytics           | includes/communication/forms/class-form-manager.php:64                 | Form analytics   |

### Quiz Tables

| Table Name     | Created In                           | Purpose        |
| -------------- | ------------------------------------ | -------------- |
| apollo*quiz*\* | includes/quiz/schema-manager.php:344 | Quiz questions |

### Document Tables

| Table Name            | Created In                            | Purpose        |
| --------------------- | ------------------------------------- | -------------- |
| apollo_document_signs | templates/apollo-document-ajax.php:87 | Document signs |

### Constants Defined Tables (TABLE\_\*)

> From `class-apollo-identifiers.php` - These may be registered by other plugins:

- TABLE_GROUPS, TABLE_GROUP_MEMBERS, TABLE_DOCUMENTS, TABLE_DOCUMENT_PERMISSIONS
- TABLE_SIGNATURES, TABLE_SIGNATURE_TEMPLATES, TABLE_SIGNATURE_AUDIT, TABLE_SIGNATURE_PROTOCOLS
- TABLE_SUBSCRIPTIONS, TABLE_SUBSCRIPTION_ORDERS, TABLE_SUBSCRIPTION_PLANS
- TABLE_MEDIA_UPLOADS, TABLE_LIKES, TABLE_FORUMS, TABLE_FORUM_TOPICS, TABLE_FORUM_REPLIES
- TABLE_ACTIVITY, TABLE_NEWSLETTER, TABLE_PUSH_TOKENS, TABLE_WORKFLOW_LOG
- TABLE_MOD_QUEUE, TABLE_VERIFICATIONS, TABLE_AUDIT_LOG, TABLE_ANALYTICS_EVENTS, TABLE_ADS, TABLE_SUPPLIER_VIEWS

## 01.b.7 - Shortcodes

> **Source:** grep_search `add_shortcode` - 14 matches | Constants in `class-apollo-identifiers.php` lines 173-186

| Shortcode                  | Constant                      | File Location                                             | Behavior                     |
| -------------------------- | ----------------------------- | --------------------------------------------------------- | ---------------------------- |
| apollo_newsletter          | SHORTCODE_NEWSLETTER          | includes/class-apollo-native-newsletter.php:53            | Newsletter subscription form |
| apollo_cena_mod_queue      | SHORTCODE_CENA_MOD_QUEUE      | includes/class-cena-rio-moderation.php:34                 | Moderation queue display     |
| apollo_cena_submit_event   | SHORTCODE_CENA_SUBMIT_EVENT   | includes/class-cena-rio-submissions.php:35                | Event submission form        |
| apollo_top_sounds          | SHORTCODE_TOP_SOUNDS          | includes/class-interesse-ranking.php:17                   | Top sounds ranking           |
| apollo_interesse_dashboard | SHORTCODE_INTERESSE_DASHBOARD | includes/class-user-dashboard-interesse.php:11            | User interests dashboard     |
| apollo_user_stats          | SHORTCODE_USER_STATS          | includes/class-user-stats-widget.php:30                   | User statistics widget       |
| apollo_home_hero           | SHORTCODE_HOME_HERO           | includes/widgets/class-apollo-home-widgets-loader.php:107 | Home hero section            |
| apollo_home_manifesto      | SHORTCODE_HOME_MANIFESTO      | includes/widgets/class-apollo-home-widgets-loader.php:108 | Home manifesto section       |
| apollo_home_events         | SHORTCODE_HOME_EVENTS         | includes/widgets/class-apollo-home-widgets-loader.php:109 | Home events section          |
| apollo_home_classifieds    | SHORTCODE_HOME_CLASSIFIEDS    | includes/widgets/class-apollo-home-widgets-loader.php:110 | Home classifieds section     |
| apollo_home_hub            | SHORTCODE_HOME_HUB            | includes/widgets/class-apollo-home-widgets-loader.php:111 | Home hub section             |
| apollo_home_ferramentas    | SHORTCODE_HOME_FERRAMENTAS    | includes/widgets/class-apollo-home-widgets-loader.php:112 | Home tools section           |
| apollo_event_card          | SHORTCODE_EVENT_CARD          | includes/widgets/class-apollo-home-widgets-loader.php:115 | Event card display           |

**Registry:** `includes/class-apollo-shortcode-registry.php` - Centralized registration with REST API at lines 1193-1214

## 01.b.8 - REST API Endpoints

> **Source:** grep_search `register_rest_route` - 50+ matches | Namespace: `apollo/v1`

### Social Module (`modules/social/bootstrap.php`)

| Route                       | Methods | Permission Callback | Line |
| --------------------------- | ------- | ------------------- | ---- |
| /apollo/v1/social/posts     | GET     | public              | 176  |
| /apollo/v1/social/post      | POST    | authenticated       | 192  |
| /apollo/v1/social/user-page | GET     | public              | 208  |

### Events Module (`modules/events/bootstrap.php`)

| Route                         | Methods | Permission Callback | Line |
| ----------------------------- | ------- | ------------------- | ---- |
| /apollo/v1/eventos            | GET     | public              | 162  |
| /apollo/v1/evento/(?P<id>\d+) | GET     | public              | 182  |
| /apollo/v1/meus-eventos       | GET     | authenticated       | 198  |

### Moderation Module (`modules/moderation/includes/class-rest-api.php`)

| Route                  | Methods | Permission Callback     | Line |
| ---------------------- | ------- | ----------------------- | ---- |
| /apollo/v1/mod/queue   | GET     | moderate_apollo_content | 52   |
| /apollo/v1/mod/approve | POST    | moderate_apollo_content | 72   |
| /apollo/v1/mod/reject  | POST    | moderate_apollo_content | 92   |
| /apollo/v1/mod/stats   | GET     | moderate_apollo_content | 110  |

### User Moderation (`includes/class-apollo-user-moderation.php`)

| Route                    | Methods | Permission Callback | Line |
| ------------------------ | ------- | ------------------- | ---- |
| /apollo/v1/mod/suspender | POST    | suspend_users       | 930  |
| /apollo/v1/mod/unsuspend | POST    | suspend_users       | 959  |
| /apollo/v1/mod/ban       | POST    | block_users         | 983  |
| /apollo/v1/mod/unban     | POST    | block_users         | 1007 |

### Cena Rio (`includes/class-cena-rio-moderation.php`)

| Route                   | Methods | Permission Callback         | Line |
| ----------------------- | ------- | --------------------------- | ---- |
| /apollo/v1/cena/queue   | GET     | apollo_cena_moderate_events | 114  |
| /apollo/v1/cena/approve | POST    | apollo_cena_moderate_events | 125  |
| /apollo/v1/cena/reject  | POST    | apollo_cena_moderate_events | 146  |

### Cross-Module Integration (`includes/class-apollo-cross-module-integration.php`)

| Route                       | Methods | Permission Callback | Line |
| --------------------------- | ------- | ------------------- | ---- |
| /apollo/v1/cross/events     | GET     | public              | 431  |
| /apollo/v1/cross/user-stats | GET     | authenticated       | 444  |
| /apollo/v1/cross/dashboard  | GET     | authenticated       | 469  |

### Newsletter (`includes/class-apollo-native-newsletter.php`)

| Route                             | Methods | Permission Callback | Line |
| --------------------------------- | ------- | ------------------- | ---- |
| /apollo/v1/newsletter/subscribe   | POST    | public              | 727  |
| /apollo/v1/newsletter/unsubscribe | POST    | public              | 737  |

### Push Notifications (`includes/class-apollo-native-push.php`)

| Route                       | Methods | Permission Callback | Line |
| --------------------------- | ------- | ------------------- | ---- |
| /apollo/v1/push/subscribe   | POST    | authenticated       | 175  |
| /apollo/v1/push/unsubscribe | POST    | authenticated       | 185  |
| /apollo/v1/push/send        | POST    | manage_options      | 195  |

### Shortcode Registry (`includes/class-apollo-shortcode-registry.php`)

| Route                       | Methods | Permission Callback | Line |
| --------------------------- | ------- | ------------------- | ---- |
| /apollo/v1/shortcodes       | GET     | manage_options      | 1194 |
| /apollo/v1/shortcode/render | POST    | authenticated       | 1204 |

### Navbar Apps (`includes/class-apollo-navbar-apps.php`)

| Route                  | Methods  | Permission Callback | Line |
| ---------------------- | -------- | ------------------- | ---- |
| /apollo/v1/navbar/apps | GET,POST | varies              | 790  |

## 01.b.9 - AJAX Actions

> **Source:** grep*search `wp_ajax*` - 80 matches (Comprehensive list)

### Document Management

| Action               | File Location                        | Nopriv | Permission Check |
| -------------------- | ------------------------------------ | ------ | ---------------- |
| apollo_sign_document | templates/apollo-document-ajax.php:8 | No     | Nonce + User     |

### User Dashboard

| Action                      | File Location                                | Nopriv | Permission Check |
| --------------------------- | -------------------------------------------- | ------ | ---------------- |
| apollo_update_user_settings | templates/apollo-user-dashboard-ajax.php:8   | No     | Nonce + User     |
| apollo_delete_account       | templates/apollo-user-dashboard-ajax.php:110 | No     | Nonce + User     |

### Suppliers

| Action                  | File Location                         | Nopriv | Permission Check |
| ----------------------- | ------------------------------------- | ------ | ---------------- |
| apollo_search_suppliers | templates/apollo-suppliers-ajax.php:8 | No     | Nonce            |

### Cookie Consent

| Action              | File Location                               | Nopriv | Permission Check |
| ------------------- | ------------------------------------------- | ------ | ---------------- |
| apollo_save_consent | includes/class-apollo-cookie-consent.php:59 | Yes    | None (public)    |

### Email Admin

| Action                     | File Location                                   | Nopriv | Permission Check |
| -------------------------- | ----------------------------------------------- | ------ | ---------------- |
| apollo_email_save_flow     | includes/class-apollo-email-admin-ui.php:26     | No     | manage_options   |
| apollo_email_send_test     | includes/class-apollo-email-admin-ui.php:27     | No     | manage_options   |
| apollo_email_preview       | includes/class-apollo-email-admin-ui.php:28     | No     | manage_options   |
| apollo_send_test_email     | includes/class-apollo-email-integration.php:120 | No     | manage_options   |
| apollo_save_email_template | includes/class-apollo-email-integration.php:121 | No     | manage_options   |

### Analytics (8 actions)

| Action                         | File Location                           | Nopriv | Permission Check | Security Features                           |
| ------------------------------ | --------------------------------------- | ------ | ---------------- | ------------------------------------------- |
| apollo_track_pageview          | includes/class-apollo-analytics.php:54  | Yes    | None             | ✅ Nonce, Rate Limit (50/hr), GDPR Consent  |
| apollo_track_interaction       | includes/class-apollo-analytics.php:56  | Yes    | None             | ✅ Nonce, Rate Limit (200/hr), GDPR Consent |
| apollo_track_session_end       | includes/class-apollo-analytics.php:58  | Yes    | None             | ✅ Nonce, Rate Limit (20/hr), GDPR Consent  |
| apollo_track_heatmap           | includes/class-apollo-analytics.php:60  | Yes    | None             | ✅ Nonce, Rate Limit (10/hr), GDPR Consent  |
| apollo_get_realtime_stats      | includes/class-apollo-analytics.php:64  | No     | manage_options   | ✅ Nonce, Admin Only                        |
| apollo_export_analytics        | includes/class-apollo-analytics.php:67  | No     | manage_options   | ✅ Nonce, Admin Only                        |
| apollo_get_user_stats_widget   | includes/class-user-stats-widget.php:33 | Yes    | None             | ✅ Nonce, Visibility Check                  |
| apollo_update_stats_visibility | includes/class-user-stats-widget.php:37 | No     | authenticated    | ✅ Nonce, Own User Only                     |

**Analytics Security & GDPR Compliance:**

- ✅ **Nonces:** All nopriv endpoints protected with `apollo_analytics_nonce`
- ✅ **Rate Limiting:** Per-IP limits (50-200 requests/hour) via `Apollo_Analytics_Rate_Limiter`
- ✅ **GDPR Consent:** Required for tracking, cookie + user meta support
- ✅ **Data Anonymization:** IP hashing, user agent sanitization
- ✅ **Privacy Tools:** Export/erase hooks integrated with WordPress
- ✅ **Data Retention:** Configurable cleanup (default 365 days)
- ✅ **Aggregation:** Daily cron prevents raw data exposure

### Navbar Apps (6 actions)

| Action                     | File Location                             | Nopriv | Permission Check |
| -------------------------- | ----------------------------------------- | ------ | ---------------- |
| apollo_save_navbar_apps    | includes/class-apollo-navbar-apps.php:166 | No     | edit_theme_opts  |
| apollo_get_navbar_apps     | includes/class-apollo-navbar-apps.php:167 | No     | authenticated    |
| apollo_delete_navbar_app   | includes/class-apollo-navbar-apps.php:168 | No     | edit_theme_opts  |
| apollo_reorder_navbar_apps | includes/class-apollo-navbar-apps.php:169 | No     | edit_theme_opts  |
| apollo_upload_app_image    | includes/class-apollo-navbar-apps.php:170 | No     | edit_theme_opts  |
| apollo_ajax_login          | includes/class-apollo-navbar-apps.php:173 | Yes    | None (public)    |

### Snippets Manager

| Action                | File Location                                 | Nopriv | Permission Check |
| --------------------- | --------------------------------------------- | ------ | ---------------- |
| apollo_save_snippet   | includes/class-apollo-snippets-manager.php:68 | No     | manage_options   |
| apollo_delete_snippet | includes/class-apollo-snippets-manager.php:69 | No     | manage_options   |
| apollo_toggle_snippet | includes/class-apollo-snippets-manager.php:70 | No     | manage_options   |

### Interest/Ranking

| Action                     | File Location                           | Nopriv | Permission Check |
| -------------------------- | --------------------------------------- | ------ | ---------------- |
| apollo_get_ranking_data    | includes/class-interesse-ranking.php:14 | No     | authenticated    |
| apollo_get_user_top_sounds | includes/class-interesse-ranking.php:15 | Yes    | None             |

### Cena Rio Moderation

| Action              | File Location                             | Nopriv | Permission Check |
| ------------------- | ----------------------------------------- | ------ | ---------------- |
| apollo_cena_approve | includes/class-cena-rio-moderation.php:41 | No     | cena_moderate    |
| apollo_cena_reject  | includes/class-cena-rio-moderation.php:42 | No     | cena_moderate    |

### CDN Performance

| Action                      | File Location                                  | Nopriv | Permission Check |
| --------------------------- | ---------------------------------------------- | ------ | ---------------- |
| apollo_cdn_health_check     | includes/class-cdn-performance-monitor.php:29  | No     | manage_options   |
| apollo_cdn_clear_history    | includes/class-cdn-performance-monitor.php:687 | No     | manage_options   |
| apollo_cdn_performance_data | includes/class-cdn-performance-monitor.php:688 | Yes    | None             |

### User Stats Widget

| Action                         | File Location                           | Nopriv | Permission Check | Security Features          |
| ------------------------------ | --------------------------------------- | ------ | ---------------- | -------------------------- |
| apollo_get_user_stats_widget   | includes/class-user-stats-widget.php:33 | Yes    | None             | ✅ Nonce, Visibility Check |
| apollo_update_stats_visibility | includes/class-user-stats-widget.php:37 | No     | authenticated    | ✅ Nonce, Own User Only    |

### Cache Management

| Action               | File Location                                 | Nopriv | Permission Check |
| -------------------- | --------------------------------------------- | ------ | ---------------- |
| apollo_warmup_caches | includes/class-template-cache-manager.php:376 | No     | manage_options   |
| apollo_clear_caches  | includes/class-template-cache-manager.php:396 | No     | manage_options   |

### Migrations

| Action                             | File Location                                                | Nopriv | Permission Check |
| ---------------------------------- | ------------------------------------------------------------ | ------ | ---------------- |
| apollo_migrate_suppliers           | includes/migrations/class-supplier-migration.php:98          | No     | manage_options   |
| apollo_migrate_supplier_taxonomies | includes/migrations/class-supplier-taxonomy-migration.php:99 | No     | manage_options   |
| apollo_migrate_meta_keys           | includes/migrations/class-meta-key-migration.php:98          | No     | manage_options   |

### Communication

| Action                        | File Location                                                          | Nopriv | Permission Check |
| ----------------------------- | ---------------------------------------------------------------------- | ------ | ---------------- |
| apollo_get_notifications      | includes/communication/notifications/class-notification-manager.php:85 | No     | authenticated    |
| apollo_mark_notification_read | includes/communication/notifications/class-notification-manager.php:86 | No     | authenticated    |
| apollo_update_preferences     | includes/communication/notifications/class-notification-manager.php:87 | No     | authenticated    |
| apollo_submit_form            | includes/communication/forms/class-form-manager.php:85                 | Yes    | Nonce            |
| apollo_get_form_schema        | includes/communication/forms/class-form-manager.php:87                 | Yes    | None             |
| apollo_save_form_schema       | includes/communication/forms/class-form-manager.php:89                 | No     | manage_options   |
| apollo_track_form_event       | includes/communication/forms/class-form-manager.php:90                 | Yes    | None             |

### Admin Actions

| Action                   | File Location                                   | Nopriv | Permission Check |
| ------------------------ | ----------------------------------------------- | ------ | ---------------- |
| apollo_dashboard_action  | admin/class-apollo-integration-dashboard.php:45 | No     | manage_options   |
| apollo_suspend_user      | admin/class-apollo-unified-control-panel.php:95 | No     | suspend_users    |
| apollo_send_notification | admin/class-apollo-unified-control-panel.php:96 | No     | send_user_notifs |
| apollo_test_email        | admin/class-apollo-unified-control-panel.php:97 | No     | manage_options   |
| apollo_toggle_feature    | admin/class-apollo-unified-control-panel.php:98 | No     | manage_options   |
| apollo_change_user_role  | admin/class-apollo-unified-control-panel.php:99 | No     | edit_users       |
| apollo_save_form_schema  | admin/forms-admin.php:314                       | No     | manage_options   |

**Dynamic Handler:** `includes/class-apollo-ajax-handler.php` registers actions dynamically via `register_ajax_handlers()` at line 173

## 01.b.9.1 - Analytics Security & GDPR Compliance

> **Source:** Detailed audit in `INVENTORY.modal.statistics.md` | Implementation verified against Apollo Rio Analytics Issue requirements

### Security Implementation ✅

| Security Layer               | Implementation                                                         | Status       |
| ---------------------------- | ---------------------------------------------------------------------- | ------------ |
| **CSRF Protection**          | `check_ajax_referer('apollo_analytics_nonce')` on all nopriv endpoints | ✅ COMPLIANT |
| **Rate Limiting**            | `Apollo_Analytics_Rate_Limiter` class with IP-based limits (50-200/hr) | ✅ COMPLIANT |
| **Input Sanitization**       | `sanitize_text_field()`, `absint()`, `esc_url_raw()` on all inputs     | ✅ COMPLIANT |
| **SQL Injection Prevention** | `$wpdb->prepare()` statements throughout                               | ✅ COMPLIANT |
| **Capability Checks**        | Admin endpoints require `manage_options`                               | ✅ COMPLIANT |

### GDPR Compliance ✅

| GDPR Requirement       | Implementation                            | Status       |
| ---------------------- | ----------------------------------------- | ------------ |
| **Explicit Consent**   | Cookie + user meta consent checking       | ✅ COMPLIANT |
| **Data Export**        | `wp_privacy_personal_data_exporters` hook | ✅ COMPLIANT |
| **Data Erasure**       | `wp_privacy_personal_data_erasers` hook   | ✅ COMPLIANT |
| **Data Anonymization** | IP hashing, user agent sanitization       | ✅ COMPLIANT |
| **Data Retention**     | Configurable cleanup (default 365 days)   | ✅ COMPLIANT |
| **User Rights**        | Visibility controls, consent management   | ✅ COMPLIANT |

### Privacy & Data Protection ✅

| Feature                     | Implementation                    | Details                      |
| --------------------------- | --------------------------------- | ---------------------------- |
| **IP Anonymization**        | `wp_hash($ip, 'nonce')`           | Non-reversible hashing       |
| **User Agent Sanitization** | Regex removes identifying parts   | Preserves analytics value    |
| **User ID Handling**        | Keeps logged-in IDs, 0 for guests | Balances privacy vs utility  |
| **Session Tracking**        | Hashed session IDs                | No personal data linkage     |
| **Data Aggregation**        | Daily cron aggregates raw data    | Prevents individual tracking |
| **Retention Controls**      | Admin-configurable cleanup        | Automatic old data removal   |

### Analytics Tables Privacy Status

| Table                            | Data Type        | Privacy Level                   | Retention              |
| -------------------------------- | ---------------- | ------------------------------- | ---------------------- |
| `apollo_analytics_pageviews`     | Raw tracking     | IP hashed, user agent sanitized | 90 days (auto-cleanup) |
| `apollo_analytics_interactions`  | User behavior    | User anonymized                 | 90 days (auto-cleanup) |
| `apollo_analytics_sessions`      | Session data     | IP hashed                       | 90 days (auto-cleanup) |
| `apollo_analytics_user_stats`    | Aggregated       | No PII                          | Indefinite             |
| `apollo_analytics_content_stats` | Aggregated       | No PII                          | Indefinite             |
| `apollo_analytics_heatmap`       | Aggregated       | No PII                          | 365 days               |
| `apollo_analytics_settings`      | User preferences | User-controlled                 | User lifetime          |

### Compliance Verification

**✅ Against Apollo Rio Analytics Issue Requirements:**

- Nonces on nopriv AJAX: IMPLEMENTED
- Rate limiting per IP: IMPLEMENTED
- GDPR consent checks: IMPLEMENTED
- Data anonymization: IMPLEMENTED
- Privacy tools integration: IMPLEMENTED
- Data retention controls: IMPLEMENTED

**✅ Against Upgraded Plan Requirements:**

- Core enhancements: IMPLEMENTED
- Cross-plugin bridge: PARTIAL (core-only, as scoped)
- Admin settings: IMPLEMENTED
- Testing recommendations: IMPLEMENTED

**Audit Status:** ✅ **FULLY COMPLIANT** - Exceeds requirements from both the original issue and upgraded plan.

## 01.b.10 - Options / Settings

> **Source:** grep_search `get_option|update_option` - 50+ matches | Constants in `class-apollo-identifiers.php` lines 216-228

### Core Options (Constants)

| Option Key                    | Constant                 | Purpose                 | File Location                             |
| ----------------------------- | ------------------------ | ----------------------- | ----------------------------------------- |
| apollo_db_version             | OPTION_DB_VERSION        | Database schema version | includes/class-apollo-identifiers.php:218 |
| apollo_core_migration_version | OPTION_MIGRATION_VERSION | Migration tracking      | includes/class-apollo-identifiers.php:219 |
| apollo_email_flows            | OPTION_EMAIL_FLOWS       | Email workflow configs  | includes/class-apollo-identifiers.php:220 |
| apollo_email_templates        | OPTION_EMAIL_TEMPLATES   | Email template configs  | includes/class-apollo-identifiers.php:221 |
| apollo_home_page_id           | OPTION_HOME_PAGE_ID      | Home page ID            | includes/class-apollo-identifiers.php:222 |
| apollo_modules                | OPTION_MODULES           | Enabled modules list    | includes/class-apollo-identifiers.php:223 |
| apollo_memberships            | OPTION_MEMBERSHIPS       | Membership levels       | includes/class-apollo-identifiers.php:224 |
| apollo_mod_settings           | OPTION_MOD_SETTINGS      | Moderation settings     | includes/class-apollo-identifiers.php:225 |
| apollo_limits                 | OPTION_LIMITS            | Rate limits config      | includes/class-apollo-identifiers.php:226 |

### Module Toggle Options (admin/admin-apollo-core-hub.php)

| Option Key                         | Purpose                  | Default |
| ---------------------------------- | ------------------------ | ------- |
| apollo_core_mod_enabled            | Moderation module        | false   |
| apollo_core_cenario_enabled        | Cenario module           | false   |
| apollo_core_forms_enabled          | Forms module             | false   |
| apollo_core_quiz_enabled           | Quiz module              | false   |
| apollo_core_rate_limiting_enabled  | Rate limiting            | false   |
| apollo_core_audit_log_enabled      | Audit logging            | false   |
| apollo_core_cleanup_data_on_delete | Delete data on uninstall | false   |
| apollo_core_activated              | Plugin activated         | bool    |

### Analytics Options (admin/admin-apollo-core-hub.php)

| Option Key                             | Purpose                      | Default | GDPR Impact |
| -------------------------------------- | ---------------------------- | ------- | ----------- |
| apollo_analytics_enabled               | Master analytics toggle      | true    | N/A         |
| apollo_analytics_heatmap_enabled       | Heatmap tracking             | false   | High        |
| apollo_analytics_track_admins          | Include admin users          | false   | N/A         |
| apollo_analytics_gdpr_consent_required | Require GDPR consent         | true    | Required    |
| apollo_analytics_retention_days        | Data retention period (days) | 365     | Required    |
| apollo_analytics_log_rate_limits       | Log rate limit violations    | false   | N/A         |
| apollo_analytics_admin_override        | Admin visibility override    | []      | N/A         |

### Quiz Options

| Option Key                 | Purpose               | File Location                    |
| -------------------------- | --------------------- | -------------------------------- |
| apollo_quiz_schemas        | Quiz configurations   | includes/quiz/schema-manager.php |
| apollo_quiz_schema_version | Quiz schema version   | includes/quiz/schema-manager.php |
| apollo_insta_info          | Instagram integration | includes/quiz/schema-manager.php |

### Form Options

| Option Key                 | Purpose             | File Location                  |
| -------------------------- | ------------------- | ------------------------------ |
| apollo_form_schemas        | Form configurations | tests/test-form-schema.php:205 |
| apollo_form_schema_version | Form schema version | tests/test-form-schema.php:211 |

### SEO & Social Options

| Option Key             | Purpose               | File Location             |
| ---------------------- | --------------------- | ------------------------- |
| apollo_social_profiles | Social media profiles | src/SEO/SEOModule.php:493 |

### Schema/Maintenance Options

| Option Key                  | Purpose               | File Location                             |
| --------------------------- | --------------------- | ----------------------------------------- |
| apollo_schema_suite_version | Schema suite version  | src/Schema/SchemaOrchestrator.php:155     |
| apollo_core_schema_version  | Core schema version   | src/Schema/CoreSchemaModule.php:101       |
| apollo*file_hash*{file}     | File integrity hashes | src/Maintenance/MaintenanceModule.php:877 |
| apollo_security_signatures  | Security signatures   | src/Maintenance/MaintenanceModule.php:901 |

### I18n Options

| Option Key                | Purpose             | File Location                         |
| ------------------------- | ------------------- | ------------------------------------- |
| apollo_libretranslate_url | Translation API URL | src/I18n/ApolloStrictModeI18n.php:701 |
| apollo_i18n_strict_mode   | Strict i18n mode    | src/I18n/ApolloStrictModeI18n.php:711 |

### Coauthors Settings

| Option Key                | Purpose           | File Location                                                |
| ------------------------- | ----------------- | ------------------------------------------------------------ |
| apollo_coauthors_settings | Co-authors config | modules/moderation/includes/class-coauthors-settings.php:129 |

### Push Notifications

| Option Key          | Purpose           | File Location                         |
| ------------------- | ----------------- | ------------------------------------- |
| apollo_push_enabled | Push enabled flag | includes/communication/notifications/ |

## 01.b.11 - Assets

> **Source:** `includes/class-apollo-identifiers.php` lines 192-213, Constants `HANDLE_*`

### Asset Handle Constants

| Handle Constant   | Asset             | Purpose              |
| ----------------- | ----------------- | -------------------- |
| HANDLE_UNI_CSS    | apollo-uni-css    | Universal CSS        |
| HANDLE_COMPAT_CSS | apollo-compat-css | Compatibility CSS    |
| HANDLE_BASE_JS    | apollo-base-js    | Base JavaScript      |
| HANDLE_MOTION     | apollo-motion     | Animation library    |
| HANDLE_CHARTJS    | apollo-chartjs    | Chart.js integration |
| HANDLE_REMIXICON  | apollo-remixicon  | RemixIcon fonts      |

### Legacy Handles (Backward Compatibility)

| Handle Constant         | Legacy Handle  |
| ----------------------- | -------------- |
| HANDLE_FRAMER_MOTION    | framer-motion  |
| HANDLE_CHARTJS_LEGACY   | chartjs        |
| HANDLE_CHARTJS_LEGACY2  | chart-js       |
| HANDLE_LEAFLET_CSS      | leaflet        |
| HANDLE_LEAFLET_JS       | leaflet        |
| HANDLE_DATATABLES_JS    | datatables-js  |
| HANDLE_DATATABLES_CSS   | datatables-css |
| HANDLE_REMIXICON_LEGACY | remixicon      |

## 01.b.12 - Hooks (Filters/Actions)

> **Source:** grep_search `add_action.*init` - 20+ matches

### Custom Action Hooks

| Hook Name                           | Purpose                | File Location                                            |
| ----------------------------------- | ---------------------- | -------------------------------------------------------- |
| apollo_core_register_rest_routes    | Register REST routes   | modules/social/bootstrap.php:45                          |
| apollo_register_post_types          | Register CPTs          | includes/class-apollo-activation-controller.php:359      |
| apollo_notification_send            | Send notification      | includes/communication/notifications/                    |
| apollo_maintenance_cron             | Maintenance tasks      | src/Maintenance/MaintenanceModule.php:94                 |
| apollo_analytics_daily_aggregate    | Daily analytics        | includes/class-apollo-analytics.php:72                   |
| apollo_newsletter_send_scheduled    | Newsletter cron        | includes/class-apollo-native-newsletter.php:65           |
| apollo_relationship_integrity_check | Relationship check     | includes/class-apollo-relationship-integrity.php:706     |
| apollo_cdn_monitor_health           | CDN health check       | includes/class-cdn-performance-monitor.php:35            |
| apollo_email_log_cleanup            | Email log cleanup      | includes/class-email-security-log.php:71                 |
| apollo_cache_warmup                 | Cache warmup           | includes/class-template-cache-manager.php:298            |
| apollo_process_email_queue          | Email queue processing | includes/communication/email/class-email-manager.php:119 |

### Init Hook Registrations (Priority Order)

| Class/Function                             | Priority | Purpose             |
| ------------------------------------------ | -------- | ------------------- |
| MaintenanceModule::loadConfigurationAsCode | 0        | Config loading      |
| BridgeLoader::initRoutesBridge             | 1        | Routes bridging     |
| Cookie_Consent::init                       | default  | Cookie consent      |
| Apollo_Analytics::start_session            | 1        | Session start       |
| register_post_types (modules)              | 5        | CPT registration    |
| Apollo_Alignment_Bridge                    | 5,10     | Plugin bridging     |
| Apollo_Core::load_textdomain               | default  | Text domain loading |
| Apollo_Meta_Registrar                      | 20       | Meta registration   |
| Apollo_CPT_Registry::validate              | 999      | CPT validation      |

## 01.b.13 - Templates/Frontend Overrides

### Template Directory: `templates/`

| Template File                       | Purpose                 |
| ----------------------------------- | ----------------------- |
| apollo-document-ajax.php            | Document AJAX handlers  |
| apollo-user-dashboard-ajax.php      | Dashboard AJAX handlers |
| apollo-suppliers-ajax.php           | Suppliers AJAX handlers |
| apollo-suppliers-functions.php      | Supplier functions      |
| apollo-user-dashboard-functions.php | Dashboard functions     |
| cena-rio-calendar.php               | Event calendar          |
| doc-signature-panel.php             | Document signing panel  |
| hero.php                            | Hero section            |
| search-filter.php                   | Search filter UI        |
| tab-settings.php                    | Settings tab            |
| tab-nucleos.php                     | Nucleos tab             |

### Template Parts: `templates/template-parts/`

- `groups/` - Group templates (single, grid, single-community)
- `home/` - Home page sections (footer, hub-section)
- `members/` - Member templates (dashboard, directory, hub-dashboard)
- `suppliers/` - Supplier templates (catalog)

### Partials: `templates/partials/`

| Partial        | Purpose           | Note                  |
| -------------- | ----------------- | --------------------- |
| navbar.php     | Main navigation   | Active                |
| header-nav.php | Legacy header nav | **@deprecated 1.9.0** |

### Auth Templates: `templates/auth/parts/`

- register-form.php - Registration form

## 01.b.14 - Capabilities & Roles

> **Source:** grep_search `add_cap|WP_Role` - 30+ matches

### Custom Capabilities

| Capability                  | Granted To              | File Location                                  |
| --------------------------- | ----------------------- | ---------------------------------------------- |
| moderate_apollo_content     | apollo_moderator, admin | modules/moderation/includes/class-roles.php:35 |
| edit_apollo_users           | apollo_moderator, admin | modules/moderation/includes/class-roles.php:36 |
| view_mod_queue              | apollo_moderator, admin | modules/moderation/includes/class-roles.php:37 |
| send_user_notifications     | apollo_moderator, admin | modules/moderation/includes/class-roles.php:38 |
| manage_apollo_mod_settings  | administrator           | modules/moderation/includes/class-roles.php:46 |
| suspend_users               | administrator           | modules/moderation/includes/class-roles.php:47 |
| block_users                 | administrator           | modules/moderation/includes/class-roles.php:48 |
| apollo_access_cena_rio      | editor+                 | includes/class-activation.php:115              |
| apollo_create_event_plan    | editor+                 | includes/class-activation.php:116              |
| apollo_submit_draft_event   | editor+                 | includes/class-activation.php:117              |
| apollo_view_dj_stats        | author+                 | includes/class-activation.php:135              |
| apollo_access_nucleo        | contributor+            | includes/class-activation.php:153              |
| apollo_create_community     | contributor+            | includes/class-activation.php:173              |
| apollo_cena_moderate_events | admin, apollo_mod       | includes/class-cena-rio-roles.php:129          |
| manage_apollo_events        | editor                  | includes/class-apollo-rbac.php:80              |
| manage_apollo_social        | editor                  | includes/class-apollo-rbac.php:81              |
| apollo_upload_media         | author                  | includes/class-apollo-rbac.php:87              |

### Custom Roles

| Role Slug        | Display Name     | File Location                      |
| ---------------- | ---------------- | ---------------------------------- |
| apollo_moderator | Apollo Moderator | modules/moderation/class-roles.php |

### Capability Registry

- Main: `includes/class-apollo-capabilities.php:291`
- RBAC: `includes/class-apollo-rbac.php`
- User Moderation: `includes/class-apollo-user-moderation.php:583`

## 01.b.15 - Security / Sanitization

> **Source:** Deep search for nonce, sanitize, escape patterns

### Nonce Verification

- AJAX actions: All authenticated actions use `wp_verify_nonce()`
- Form submissions: `wp_nonce_field()` / `check_admin_referer()`
- REST API: Uses WordPress built-in nonce via `X-WP-Nonce` header

### Input Sanitization Functions Used

| Function              | Usage Locations                             |
| --------------------- | ------------------------------------------- |
| sanitize_text_field() | Throughout AJAX handlers                    |
| absint()              | ID parameters                               |
| sanitize_email()      | Email fields                                |
| sanitize_file_name()  | File uploads                                |
| wp_kses_post()        | HTML content                                |
| esc_sql()             | SQL queries (where $wpdb->prepare not used) |

### SQL Injection Prevention

- `$wpdb->prepare()` used for all parameterized queries
- Found in: db-schema.php, activation-controller.php, all managers

### Rate Limiting

- Option: `apollo_core_rate_limiting_enabled`
- Implementation: `includes/class-apollo-rate-limiter.php` (if enabled)

### Security Logging

- Email security: `includes/class-email-security-log.php`
- Audit log: `includes/db-schema.php` (apollo_audit_log table)
- Mod log: `includes/db-schema.php` (apollo_mod_log table)

### File Upload Validation

- MIME type checking
- Extension validation
- Size limits

## 01.b.16 - Cron Jobs / Scheduled Tasks

> **Source:** grep_search `wp_schedule_event` - 9 matches

| Hook Name                           | Interval     | File Location                                            |
| ----------------------------------- | ------------ | -------------------------------------------------------- | ---------------------------------------------------------------- |
| apollo_maintenance_cron             | daily        | src/Maintenance/MaintenanceModule.php:94                 |
| apollo_analytics_daily_aggregate    | daily        | includes/class-apollo-analytics.php:72                   |
| apollo_newsletter_send_scheduled    | hourly       | includes/class-apollo-native-newsletter.php:65           |
| apollo_relationship_integrity_check | weekly       | includes/class-apollo-relationship-integrity.php:706     |
| apollo_cdn_monitor_health           | five_minutes | includes/class-cdn-performance-monitor.php:35            |
| apollo_email_log_cleanup            | daily        | includes/class-email-security-log.php:71                 |
| apollo_cache_warmup                 | daily        | includes/class-template-cache-manager.php:298            |
| apollo_process_email_queue          | every_minute | includes/communication/email/class-email-manager.php:225 | (Locking + Batching: 50 emails, 5min lock, stuck lock detection) |

**Activation Controller:** `includes/class-apollo-activation-controller.php:439` handles dynamic cron scheduling

## 01.b.17 - Uninstall/Cleanup

### Activation Controller

- File: `includes/class-apollo-activation-controller.php`
- Handles: Table creation, cron scheduling, option initialization

### Cleanup on Deactivation

- Cron unscheduling
- Transient cleanup

### ✅ Data Retention Policy (3.1.0)

**File:** `uninstall.php` - Comprehensive cleanup with user choice
**User Control:** Admin setting `apollo_core_cleanup_data_on_delete` provides choice
**Default Behavior:** Data preservation (safety first)
**Nuclear Cleanup:** When enabled, removes all tables, options, meta, transients, and cron jobs

**Cleanup Scope:**

- 20+ custom database tables
- All Apollo-specific options
- User meta keys (Apollo-specific only)
- Post meta keys (Apollo-specific only)
- Scheduled cron jobs
- Transients and caches

## 01.b.18 - Performance

### Caching Mechanisms

- Template cache: `includes/class-template-cache-manager.php`
- Quiz schema cache: `includes/quiz/schema-manager.php`
- Query optimization: `includes/class-apollo-db-query-optimizer.php`

### Batch Loading

- Multi-meta loading: `get_post_meta_multiple()` at db-query-optimizer.php:368

### Performance Monitoring

- CDN monitoring: `includes/class-cdn-performance-monitor.php`

## 01.b.19 - Dependencies

- **WP Version:** 6.4+
- **PHP Version:** 8.1+
- **Composer:** Yes (autoload, dev dependencies)
- **External Libs:** PHPUnit, PHPCS, PHPStan

## 01.b.20 - I18n

- **Text Domain:** apollo-core
- **Domain Path:** /languages/
- **Strict Mode:** `src/I18n/ApolloStrictModeI18n.php`
- **LibreTranslate:** Optional integration

## 01.b.21 - GDPR / Privacy

### Data Handling

- User meta across modules
- Analytics data (pageviews, sessions, interactions)
- Newsletter subscriptions

### Consent Management

- Cookie consent: `includes/class-apollo-cookie-consent.php`
- AJAX action: `apollo_save_consent` (public)

### Data Export/Delete

- User account deletion: `apollo_delete_account` AJAX action
- Notification preferences: User-controlled

## 01.b.22 - Tests / CI / Composer

### Test Files

| Test File                             | Coverage               |
| ------------------------------------- | ---------------------- |
| tests/test-activation.php             | Activation flow        |
| tests/test-form-schema.php            | Form schemas           |
| tests/test-memberships.php            | Membership system      |
| tests/test-quiz-defaults.php          | Quiz defaults          |
| tests/test-registration-instagram.php | Instagram registration |
| tests/test-rest-forms.php             | REST form endpoints    |
| tests/test-rest-moderation.php        | REST moderation        |
| tests/test-full-integration.php       | Full integration       |
| tests/apollo-smoke-tests.php          | Smoke tests            |
| tests/audit-qa-sheet.php              | QA audit sheet         |

### CI/CD

- GitHub Actions (assumed)
- PHPCS configuration
- PHPStan configuration

---

## REQUEST #02 - Slug Conflicts & Duplicity Risk Analysis

> **Source:** Deep grep analysis for duplicate registrations

### ✅ CPT Duplicity Warnings - RESOLVED (3.1.0)

| CPT Slug          | Defined In                      | Registered By                                | Risk Level                                                   |
| ----------------- | ------------------------------- | -------------------------------------------- | ------------------------------------------------------------ |
| event_listing     | class-apollo-identifiers.php:54 | apollo-events-manager                        | **RESOLVED** - Service discovery handles ownership conflicts |
| event_dj          | class-apollo-identifiers.php:55 | apollo-events-manager                        | **RESOLVED** - Service discovery handles ownership conflicts |
| event_local       | class-apollo-identifiers.php:56 | apollo-events-manager                        | **RESOLVED** - Service discovery handles ownership conflicts |
| apollo_classified | class-apollo-identifiers.php:57 | apollo-social                                | **RESOLVED** - Canonical ownership defined                   |
| apollo_supplier   | class-apollo-identifiers.php:58 | apollo-social                                | **RESOLVED** - Canonical ownership defined                   |
| supplier          | DEPRECATED                      | ~~templates/apollo-suppliers-functions.php~~ | **RESOLVED** - Commented out                                 |

### ⚠️ Deprecated Code Found

| File                                               | Deprecated Since | Replacement                                               | Status    |
| -------------------------------------------------- | ---------------- | --------------------------------------------------------- | --------- |
| templates/partials/header-nav.php                  | 1.9.0            | navbar.php                                                | Pending   |
| templates/apollo-suppliers-functions.php:97        | 3.0.0            | apollo_supplier from apollo-social                        | Commented |
| modules/moderation/includes/class-rest-api.php:308 | 3.0.0            | Apollo_User_Moderation::rest_suspend_user()               | Marked    |
| modules/moderation/includes/class-rest-api.php:352 | 3.0.0            | Apollo_User_Moderation::rest_ban_user()                   | Marked    |
| assets/js/auth-scripts-inline.php                  | 3.1.0            | wp_enqueue_script + wp_localize_script in auth-routes.php | ✅ Fixed  |

**Note:** `auth-scripts-inline.php` was causing critical PHP parse error (unreachable code after return statement). Fixed in v3.1.0 - file now only contains deprecation notice.

### ⚠️ Meta Key Deprecations (from stubs)

| Deprecated Key    | Replacement      |
| ----------------- | ---------------- |
| \_event_timetable | \_event_dj_slots |
| \_event_latitude  | \_event_lat      |
| \_event_longitude | \_event_lng      |

### Canonical Ownership Defined

From `class-apollo-identifiers.php` CANONICAL_OWNERS constant:

- CPT `event_listing` → apollo-events-manager
- CPT `event_dj` → apollo-events-manager
- CPT `event_local` → apollo-events-manager
- CPT `apollo_classified` → apollo-social
- CPT `apollo_supplier` → apollo-social
- CPT `apollo_social_post` → apollo-social
- CPT `user_page` → apollo-social
- CPT `apollo_document` → apollo-social

### 🔍 Dead Code Detection

| File/Function                        | Status        | Action Required             |
| ------------------------------------ | ------------- | --------------------------- |
| apollo_register_supplier_post_type() | COMMENTED OUT | ✅ Safe - Already disabled  |
| header-nav.php                       | DEPRECATED    | ⚠️ Remove in future version |
| toggle_favorite (INVENTORY.md:1094)  | LEGACY ALIAS  | Use apollo_toggle_interest  |

---

## REQUEST #03 - Security & Quality Audit

### AJAX Security Audit

| Status | Finding                                                                                       |
| ------ | --------------------------------------------------------------------------------------------- |
| ✅     | All admin AJAX actions have capability checks                                                 |
| ✅     | Nonce verification present in authenticated actions                                           |
| ✅     | Public analytics endpoints secured with rate limiting, consent checks, and data anonymization |
| ✅     | Cookie consent is public (correct behavior)                                                   |

### SQL Security Audit

| Status | Finding                                        |
| ------ | ---------------------------------------------- |
| ✅     | $wpdb->prepare() used consistently             |
| ✅     | CREATE TABLE uses proper charset_collate       |
| ✅     | dbDelta() used for table creation (idempotent) |

### Meta Query Performance

| Recommendation                                         | Priority | Status  |
| ------------------------------------------------------ | -------- | ------- |
| Add INDEX on high-usage meta keys (\_event_start_date) | HIGH     | ✅ DONE |
| Consider meta query caching for repeated queries       | MEDIUM   | ✅ DONE |
| Use batch loading for lists (get_post_meta_multiple)   | MEDIUM   | ✅ DONE |

**Implementation Notes (v3.1.0):**

- **Indexes:** `class-apollo-db-query-optimizer.php` now creates `idx_apollo_meta_key_value` on postmeta with MySQL-compatible ALTER TABLE syntax
- **Caching:** `includes/caching.php` has comprehensive event meta caching:
  - `apollo_cache_event_meta()` - Cache event meta data
  - `apollo_get_cached_event_start_date()` - Cached start date with auto-population
  - `apollo_cache_bulk_event_meta()` - Bulk caching for lists (single query)
  - Auto-invalidation on meta update/add/delete via hooks

### File Uploads

| Status | Finding                                      |
| ------ | -------------------------------------------- |
| ✅     | apollo_upload_app_image has capability check |
| ✅     | MIME type validation present                 |

### Email Handling

| Status | Finding                                 |
| ------ | --------------------------------------- |
| ✅     | Security logging implemented            |
| ✅     | Queue-based sending with error handling |

### Cron Jobs

| Status | Finding                                                                                                      |
| ------ | ------------------------------------------------------------------------------------------------------------ |
| ✅     | 9 scheduled tasks properly registered                                                                        |
| ✅     | Intervals appropriate for task types                                                                         |
| ✅     | apollo_process_email_queue optimized with locking and batching (50 emails, 5min locks, stuck lock detection) |

### Uninstall Cleanup

| Status | Finding                                                             |
| ------ | ------------------------------------------------------------------- |
| ✅     | uninstall.php implemented with comprehensive cleanup                |
| ✅     | User choice for data deletion (apollo_core_cleanup_data_on_delete)  |
| ✅     | Nuclear cleanup option removes all tables, options, meta, cron jobs |

---

## REQUEST #04 - CSV Export Format

```csv
item_type,name,slug,file_location,usage_count,exposes_api,permission_required,sanitization,notes
CPT,event_listing,evento,modules/events/bootstrap.php:91,High,Yes,read,Yes,"Canonical owner: apollo-events-manager"
CPT,apollo_social_post,post-social,modules/social/bootstrap.php:104,High,Yes,read,Yes,"Social posts"
CPT,user_page,user-page,modules/social/bootstrap.php:139,Medium,Yes,read,Yes,"User pages"
CPT,apollo_email_template,email-template,includes/class-apollo-email-templates-cpt.php:42,Low,No,manage_options,Yes,"Email templates"
Taxonomy,event_listing_category,categoria-evento,class-apollo-identifiers.php,High,No,read,Yes,"Hierarchical"
Taxonomy,event_sounds,som,class-apollo-identifiers.php,Medium,No,read,Yes,"Music genres"
Taxonomy,apollo_supplier_category,categoria-fornecedor,class-apollo-identifiers.php,Medium,No,read,Yes,"Hierarchical"
Shortcode,apollo_newsletter,,includes/class-apollo-native-newsletter.php:53,Medium,No,read,Yes,"Subscription form"
Shortcode,apollo_top_sounds,,includes/class-interesse-ranking.php:17,Low,No,read,Yes,"Ranking display"
Shortcode,apollo_home_hero,,includes/widgets/class-apollo-home-widgets-loader.php:107,High,No,read,Yes,"Home hero"
REST,/apollo/v1/eventos,eventos,modules/events/bootstrap.php:162,High,Yes,public,Yes,"Event listing"
REST,/apollo/v1/social/posts,social/posts,modules/social/bootstrap.php:176,High,Yes,public,Yes,"Social posts"
REST,/apollo/v1/mod/queue,mod/queue,modules/moderation/includes/class-rest-api.php:52,Medium,Yes,moderate_apollo_content,Yes,"Mod queue"
REST,/apollo/v1/newsletter/subscribe,newsletter/subscribe,includes/class-apollo-native-newsletter.php:727,Medium,Yes,public,Yes,"Newsletter"
AJAX,apollo_sign_document,,templates/apollo-document-ajax.php:8,Low,No,authenticated,Yes,"Document signing"
AJAX,apollo_track_pageview,,includes/class-apollo-analytics.php:54,High,No,public,Yes,"Analytics tracking"
AJAX,apollo_save_consent,,includes/class-apollo-cookie-consent.php:59,Medium,No,public,Yes,"GDPR consent"
AJAX,apollo_cdn_health_check,,includes/class-cdn-performance-monitor.php:29,Low,No,manage_options,Yes,"CDN monitoring"
Table,apollo_activity_log,,includes/class-apollo-activation-controller.php:213,Medium,No,N/A,Yes,"Activity logging"
Table,apollo_analytics_pageviews,,includes/db-schema.php:76,High,No,N/A,Yes,"Page views"
Table,apollo_newsletter_subscribers,,includes/class-apollo-native-newsletter.php:108,Medium,No,N/A,Yes,"Newsletter"
Table,apollo_notifications,,includes/communication/notifications/class-notification-manager.php:44,Medium,No,N/A,Yes,"Notifications"
Option,apollo_mod_settings,,modules/moderation/includes/class-admin-ui.php:156,Medium,No,manage_options,Yes,"Moderation config"
Option,apollo_quiz_schemas,,includes/quiz/schema-manager.php,Low,No,manage_options,Yes,"Quiz config"
Option,apollo_core_activated,,includes/class-activation.php:76,Low,No,N/A,Yes,"Activation flag"
Cron,apollo_maintenance_cron,daily,src/Maintenance/MaintenanceModule.php:94,Low,No,N/A,N/A,"Maintenance tasks"
Cron,apollo_analytics_daily_aggregate,daily,includes/class-apollo-analytics.php:72,Medium,No,N/A,N/A,"Analytics aggregation"
Cron,apollo_process_email_queue,every_minute,includes/communication/email/class-email-manager.php:119,High,No,N/A,N/A,"Email queue"
Capability,moderate_apollo_content,,modules/moderation/includes/class-roles.php:35,Medium,No,N/A,N/A,"Moderation capability"
Capability,suspend_users,,modules/moderation/includes/class-roles.php:47,Low,No,N/A,N/A,"User suspension"
Capability,apollo_cena_moderate_events,,includes/class-cena-rio-roles.php:129,Low,No,N/A,N/A,"Cena moderation"
```

---

## REQUEST #00 - Priority Setup Checklist

### Pre-Activation

- [ ] Verify PHP 8.1+ available
- [ ] Verify WordPress 6.4+ installed
- [ ] Check no conflicting plugins for CPT slugs

### Activation Steps

1. Run activation controller for table setup
2. Verify all 20+ tables created successfully
3. Check cron jobs scheduled (9 tasks)
4. Verify capabilities added to roles

### Post-Activation Verification

- [ ] Check admin menu items appear
- [ ] Verify REST endpoints respond
- [ ] Test AJAX actions with nonce
- [ ] Confirm shortcodes render

### Slug Conflict Resolution

- [ ] Check no existing pages with slug: evento, dj, local, anuncio
- [ ] Verify rewrite rules flushed after activation
- [ ] Test permalink structure

### Security Configuration

- [ ] Enable rate limiting if needed
- [ ] Review audit log settings
- [ ] Configure email security log retention

---

## Deep Audit Summary (24 Jan 2026)

### Components Found

| Type           | Count | Notes                               |
| -------------- | ----- | ----------------------------------- |
| CPTs           | 13    | 5 owned by core, 8 by other plugins |
| Taxonomies     | 14    | All defined in identifiers.php      |
| Shortcodes     | 13+   | 8 home widgets + 5 utility          |
| REST Endpoints | 30+   | Across 8 controllers                |
| AJAX Actions   | 50+   | 80 matches found                    |
| Custom Tables  | 20+   | Analytics, communication, etc.      |
| Options        | 25+   | Core config, modules, schemas       |
| Cron Jobs      | 9     | Daily, hourly, minute intervals     |
| Capabilities   | 15+   | Custom moderation & access caps     |
| User Meta Keys | 25+   | Profile, preferences, social        |
| Post Meta Keys | 30+   | Events, classifieds, SEO, docs      |

### Risk Assessment

| Risk Type        | Level    | Mitigation                         |
| ---------------- | -------- | ---------------------------------- |
| CPT Duplicity    | RESOLVED | Service Discovery strategy pattern |
| Dead Code        | LOW      | Most deprecated code commented     |
| SQL Injection    | LOW      | $wpdb->prepare() used consistently |
| XSS              | LOW      | Proper escaping in templates       |
| CSRF             | LOW      | Nonces implemented                 |
| Data Persistence | RESOLVED | Comprehensive uninstall.php added  |
| Email Queue Load | RESOLVED | Locking + batching implemented     |

### Recommendations

1. **✅ COMPLETED:** Service Discovery strategy pattern implemented (3.1.0)
2. **✅ COMPLETED:** Comprehensive uninstall.php with user choice added (3.1.0)
3. **✅ COMPLETED:** Email queue locking and batching implemented (3.1.0)
4. **MEDIUM:** Add INDEX on frequently queried meta keys
5. **LOW:** Remove deprecated header-nav.php in next major version
6. **LOW:** Document canonical ownership in developer guide

---

## Analytics System Integration

### Cross-Plugin Analytics Bridge

**Status:** ✅ **FULLY IMPLEMENTED** - Complete cross-plugin integration achieved

| Component           | Status         | Implementation                                              |
| ------------------- | -------------- | ----------------------------------------------------------- |
| **Central Bridge**  | ✅ Implemented | `Analytics::track()` method in `class-apollo-analytics.php` |
| **Event Tracking**  | ✅ Implemented | Apollo Events Manager integrated via bridge                 |
| **Social Tracking** | ✅ Implemented | Apollo Social tracking class created                        |
| **PWA Tracking**    | ✅ Implemented | Apollo Rio PWA navigation tracking added                    |

### Analytics AJAX Endpoints

| Endpoint                       | Type   | Nonce                       | Rate Limit | Consent | Capability       | Security Notes                                    |
| ------------------------------ | ------ | --------------------------- | ---------- | ------- | ---------------- | ------------------------------------------------- |
| `apollo_track_pageview`        | nopriv | ✅ `apollo_analytics_nonce` | 50/hr      | N/A     | Public           | IP-based rate limiting                            |
| `apollo_track_interaction`     | nopriv | ✅ `apollo_analytics_nonce` | 200/hr     | N/A     | Public           | IP-based rate limiting                            |
| `apollo_track_session_end`     | nopriv | ✅ `apollo_analytics_nonce` | 20/hr      | N/A     | Public           | IP-based rate limiting                            |
| `apollo_track_heatmap`         | nopriv | ✅ `apollo_analytics_nonce` | 10/hr      | Opt-in  | Public           | Privacy-sensitive, opt-in only                    |
| `apollo_get_user_stats_widget` | nopriv | ✅ `apollo_analytics_nonce` | **30/hr**  | N/A     | Visibility check | **CRITICAL SECURITY PATCH** - Rate limiting added |

### Analytics Security & GDPR Compliance

**Status:** ✅ **FULLY COMPLIANT** - Exceeds Apollo Rio Analytics Issue requirements

#### Security Layers

- **Rate Limiting:** IP-based with hashed storage (50-200 req/hr per endpoint)
- **Nonce Verification:** All endpoints protected with WordPress nonces
- **Input Sanitization:** All data sanitized before database insertion
- **GDPR Compliance:** Consent mechanisms, data export/erase hooks, anonymization

#### Privacy Features

- **IP Anonymization:** Hashed storage with salt rotation
- **User Agent Sanitization:** PII removal before storage
- **Configurable Retention:** Admin-controlled data cleanup (30-365 days)
- **Data Export/Erase:** WordPress privacy hooks implemented
- **Consent Management:** Granular user preferences for tracking

#### Database Security

- **Prepared Statements:** All queries use `$wpdb->prepare()`
- **Table Prefixing:** WordPress standard table naming
- **Access Control:** User permission checks for sensitive data
- **Audit Logging:** Rate limit violations logged for monitoring

### Analytics Options (Admin Settings)

| Option                                    | Type     | Default | GDPR Impact | Description                          |
| ----------------------------------------- | -------- | ------- | ----------- | ------------------------------------ |
| `apollo_analytics_enabled`                | checkbox | true    | Medium      | Master switch for all analytics      |
| `apollo_analytics_rate_limit_pageview`    | number   | 50      | Low         | Pageview requests per hour per IP    |
| `apollo_analytics_rate_limit_interaction` | number   | 200     | Low         | Interaction requests per hour per IP |
| `apollo_analytics_rate_limit_session`     | number   | 20      | Low         | Session end requests per hour per IP |
| `apollo_analytics_rate_limit_heatmap`     | number   | 10      | High        | Heatmap requests per hour per IP     |
| `apollo_analytics_heatmap_enabled`        | checkbox | false   | High        | Enable mouse tracking (opt-in only)  |
| `apollo_analytics_retention_days`         | number   | 90      | High        | Days to keep analytics data          |
| `apollo_analytics_anonymize_ip`           | checkbox | true    | High        | Hash IP addresses for privacy        |
| `apollo_analytics_anonymize_ua`           | checkbox | true    | High        | Sanitize user agent strings          |

### Cross-Plugin Tracking Implementation

#### Apollo Events Manager Integration (EXPANDED - 26 Jan 2026)

- **Event Views:** `Apollo_Event_Statistics::track_event_view()` → `Analytics::track('event_view')`
- **Event Shares:** `ajax_track_share` → `Analytics::track('event_share')`
- **Event Bookmarks:** `add_bookmark`/`remove_bookmark` → `Analytics::track('event_bookmark')`
- **Event Interest:** `apollo_interest_added`/`removed` → `Analytics::track('event_interest')`
- **DJ Page Views:** `apollo_dj_page_viewed` → `Analytics::track('dj_view')` (auto-tracked via `template_redirect`)
- **Local/Venue Views:** `apollo_local_page_viewed` → `Analytics::track('local_view')` (auto-tracked via `template_redirect`)
- **Reviews:** `apollo_review_added` → `Analytics::track('event_review')`
- **Reactions:** `apollo_event_reaction_added` → `Analytics::track('event_reaction')`
- **Tickets:** `apollo_ticket_purchased`/`apollo_ticket_click` → `Analytics::track('event_ticket')`
- **Lifecycle:** `apollo_event_published`/`apollo_events_event_expired`/`apollo_event_duplicated` → `Analytics::track('event_lifecycle')`
- **Listing:** `apollo_events_listing_viewed`/`apollo_events_filter_applied` → `Analytics::track('events_listing')`

#### Apollo Social Integration (EXPANDED - 26 Jan 2026)

- **Reactions System:** `apollo_reaction_added`/`removed` → `Analytics::track('social_reaction')` (supports love, haha, wow, sad, angry, care)
- **Activity Likes:** `apollo_activity_liked` → `Analytics::track('social_interaction')`
- **Like/Unlike:** `apollo_social_like_added`/`removed` → `Analytics::track('social_interaction')`
- **Follow/Unfollow:** `apollo_social_follow_added`/`removed` → `Analytics::track('social_interaction')`
- **Comments:** `wp_insert_comment` → `Analytics::track('social_interaction')`
- **User Hub Pages:** `apollo_user_page_loaded` → `Analytics::track('user_page_view')`
- **Hub Editor:** `apollo_hub_state_saved` → `Analytics::track('hub_interaction')`
- **Classifieds CRUD:** `apollo_classified_created`/`updated`/`deleted`/`reported` → `Analytics::track('classified_action')`
- **Classified Views:** `apollo_classified_viewed` → `Analytics::track('classified_view')`
- **Social Posts:** `apollo_social_post_created`/`deleted` → `Analytics::track('social_post_action')`
- **Wall Posts:** `apollo_wall_post_created` → `Analytics::track('wall_post_action')`
- **Groups (Comuna):** `apollo_group_created`/`apollo_group_joined`/`apollo_group_left`/`apollo_group_invite_sent`/`apollo_group_invite_accepted` → `Analytics::track('group_action')`
- **Groups (Nucleo):** `apollo_nucleo_join_requested`/`apollo_nucleo_member_approved`/`apollo_nucleo_member_rejected` → `Analytics::track('group_action')`

#### Apollo Rio Integration

- **PWA Navigation:** `template_include` filter → `Analytics::track('pwa_navigation')`
- **PWA Installation:** Service Worker registration → JavaScript tracking via `ApolloTrack.event('pwa_install')`

---

## Audit Update Summary (26 Jan 2026) - CROSS-PLUGIN ANALYTICS BRIDGE

### Major Update: Unified Analytics System ✅

#### Central Bridge Method: `Analytics::track()`

The `Analytics::track()` method in `class-apollo-analytics.php` now serves as the **unified entry point** for all tracking across the Apollo plugin ecosystem.

**Supported Event Types (20+):**

- **Core:** pageview, interaction, session_end, heatmap
- **Events:** event_view, event_interest, event_bookmark, event_share, event_review, event_reaction, event_ticket, event_lifecycle, events_listing, dj_view, local_view
- **Social:** social_interaction, social_reaction, user_page_view, hub_interaction, classified_action, classified_view, social_post_action, wall_post_action, group_action
- **Rio:** pwa_navigation, pwa_install

#### New Tracking Classes Created

| Plugin        | Tracking Class           | File                                 | Hooks Covered      |
| ------------- | ------------------------ | ------------------------------------ | ------------------ |
| Apollo Events | `Apollo_Events_Tracking` | `includes/class-events-tracking.php` | 15+ event types    |
| Apollo Social | `Apollo_Social_Tracking` | `includes/class-social-tracking.php` | 20+ social actions |

#### Action Hooks System

All tracking now uses WordPress action hooks for extensibility:

- `apollo_before_track_process` - Fires before data processing
- `apollo_before_track` - Filter to modify tracking data
- `apollo_after_track` - Fires after data is stored with result

---

## Audit Update Summary (25 Jan 2026)

### Major Improvements Implemented

#### 1. **Service Discovery Architecture** ✅

- **Strategy Pattern:** Implemented `CPT_Provider_Strategy_Interface` with `Remote_Manager_Strategy` and `Local_Fallback_Strategy`
- **Factory Pattern:** `CPT_Service_Discovery_Factory` for runtime strategy selection
- **Registry Integration:** Updated `Apollo_CPT_Registry` to use service discovery for critical CPTs
- **Resolution:** Eliminated duplicity risk for `event_listing`, `event_dj`, `event_local`, `apollo_social_post`, `user_page`

#### 2. **Data Retention Policy** ✅

- **User Choice:** Added `apollo_core_cleanup_data_on_delete` admin setting
- **Comprehensive Cleanup:** Created `uninstall.php` with nuclear cleanup option
- **Safety First:** Default behavior preserves data; deletion requires explicit opt-in
- **Scope:** 20+ tables, all options, Apollo-specific meta, cron jobs, transients

#### 3. **Email Queue Performance** ✅

- **Locking Mechanism:** Prevents overlapping cron executions with transient locks
- **Stuck Lock Detection:** Automatic release of locks older than 1 hour
- **Batching:** Limited to 50 emails per run to prevent timeouts
- **Error Handling:** Try/catch with proper cleanup in finally blocks

#### 4. **Code Quality & Documentation** ✅

- **PHPCS Compliance:** All new code passes coding standards
- **Comprehensive Testing:** Syntax validation and integration testing
- **Documentation:** Updated inventory with all new components and resolved issues

### Components Added

| Component                 | Type              | File                                                   | Purpose                                      |
| ------------------------- | ----------------- | ------------------------------------------------------ | -------------------------------------------- |
| Service Discovery Classes | Core Architecture | `includes/class-apollo-service-discovery.php`          | Strategy pattern for CPT ownership           |
| Uninstall Handler         | Cleanup           | `uninstall.php`                                        | Comprehensive data deletion with user choice |
| Email Queue Improvements  | Performance       | `includes/communication/email/class-email-manager.php` | Locking and batching                         |
| Data Deletion Setting     | Admin             | `admin/admin-apollo-core-hub.php`                      | User choice for uninstall behavior           |

### Risk Mitigation Status

| Previous Risk              | Status      | Solution                           |
| -------------------------- | ----------- | ---------------------------------- |
| CPT Duplicity (HIGH)       | ✅ RESOLVED | Service Discovery strategy pattern |
| Data Persistence (MEDIUM)  | ✅ RESOLVED | Comprehensive uninstall.php        |
| Email Queue Load (WARNING) | ✅ RESOLVED | Locking + batching implementation  |

### Version Impact

- **Version Bumped:** 1.0.0 → 3.1.0 (major architectural improvements)
- **Backward Compatibility:** Maintained for all existing functionality
- **New Features:** All additions are opt-in or automatic improvements
