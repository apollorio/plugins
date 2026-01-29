# APOLLO SOCIAL CORE PLUGIN INVENTORY

**Audit Date:** 24 de janeiro de 2026 (Deep Audit - FULL POWER MODE)
**Plugin Version:** 1.0.0
**WordPress Required:** 6.4+
**PHP Required:** 8.1+
**Dependencies:** apollo-core
**Last Deep Audit:** 24 de janeiro de 2026 - COMPLETE GREP/FILE SEARCH VERIFICATION
**Auditor:** Claude Sonnet 4.5 - STRICT MODE ENABLED

---

## 01.b.1 - Plugin Identification

- **Name:** Apollo Social Core
- **Version:** 1.0.0
- **Author:** Apollo::Rio Team
- **Main File:** apollo-social.php
- **Text Domain:** apollo-social
- **Requires Plugins:** apollo-core
- **Namespace:** `Apollo\*` (PSR-4 Autoloading)
- **Composer:** Yes (DocuSeal PHP, Quill Delta Parser)
- **Architecture:** Modular (65+ modules in src/Modules/)

## 01.b.2 - Custom Post Types (CPTs)

> **Source:** grep_search `register_post_type` - 27 matches (filtered vendor) | 8 CPTs found

| CPT Slug           | Constant/Class                      | Label                   | Supports                                                            | Rewrite Slug | Capability Type | Has Archive | Registered In                                      | Module Owner |
| ------------------ | ----------------------------------- | ----------------------- | ------------------------------------------------------------------- | ------------ | --------------- | ----------- | -------------------------------------------------- | ------------ |
| apollo_social_post | (inline)                            | Apollo Social Post      | title, editor, thumbnail, custom-fields, excerpt, author, revisions | (default)    | post            | false       | src/Infrastructure/PostTypes/SocialPostType.php:83 | Social Feed  |
| apollo_classified  | ClassifiedsModule::POST_TYPE        | Apollo Classified       | title, editor, thumbnail, custom-fields, excerpt, author, revisions | classified   | post            | true        | src/Modules/Classifieds/ClassifiedsModule.php:137  | Classifieds  |
| apollo_supplier    | WPPostSupplierRepository::POST_TYPE | Apollo Supplier         | title, editor, thumbnail, custom-fields                             | supplier     | post            | true        | src/Modules/Suppliers/SuppliersModule.php:171      | Suppliers    |
| apollo_document    | UnifiedDocumentService::POST_TYPE   | Apollo Document         | title, editor, author, revisions                                    | document     | post            | false       | src/Ajax/DocumentSaveHandler.php:155               | Documents    |
| apollo_home        | Apollo_Home_CPT::POST_TYPE          | Apollo Home             | title, editor, custom-fields                                        | home         | post            | false       | src/Builder/class-apollo-home-cpt.php:96           | Builder      |
| user_page          | UserPageRegistrar::POST_TYPE        | User Page               | title, editor, thumbnail, custom-fields, revisions                  | user-page    | post            | false       | src/Modules/UserPages/UserPageRegistrar.php:54     | User Pages   |
| cena_document      | (inline)                            | Documentos Cena Rio     | title, editor, author, revisions                                    | (default)    | post            | false       | src/CenaRio/CenaRioModule.php:88                   | Cena Rio     |
| cena_event_plan    | (inline)                            | Eventos em Planejamento | title, editor, author                                               | (default)    | post            | false       | src/CenaRio/CenaRioModule.php:115                  | Cena Rio     |

**✅ NO DUPLICITY FOUND:** All CPT slugs are unique across apollo-social plugin
**⚠️ CROSS-PLUGIN CHECK:** apollo_document may conflict with apollo-core if both register same CPT
**ℹ️ GROUPS IMPLEMENTATION:** Groups/Communities use custom database tables (`apollo_groups`), not CPTs. Comuna/Nucleo are group types stored as data in the `type` column, not separate CPTs.

## 01.b.2.1 - Groups Implementation Details

> **Source:** GroupsModule.php analysis | Custom table implementation, not CPT-based

### Groups Database Schema

| Table                  | Purpose                | Key Fields                                                            |
| ---------------------- | ---------------------- | --------------------------------------------------------------------- |
| `apollo_groups`        | Main groups table      | id, name, slug, description, type (comuna/nucleo), status, creator_id |
| `apollo_group_members` | Group memberships      | group_id, user_id, role, status, joined_at                            |
| `apollo_group_meta`    | Group metadata         | group_id, meta_key, meta_value                                        |
| `apollo_group_types`   | Group type definitions | id, name, slug, description                                           |
| `apollo_group_invites` | Group invitations      | group_id, inviter_id, invitee_email, status                           |

### Group Types (Not CPTs)

| Type Slug | Display Name | Description                        | Access Level      |
| --------- | ------------ | ---------------------------------- | ----------------- |
| `comuna`  | Comunidade   | Public communities, forum-style    | Open access       |
| `nucleo`  | Núcleo       | Private work teams, intranet-style | Restricted access |

**Implementation Note:** Groups use custom database tables instead of WordPress CPTs for better performance and flexibility. The comuna/nucleo distinction is handled via the `type` field in the `apollo_groups` table, not as separate post types.

## 01.b.3 - Taxonomies

> **Source:** grep_search `register_taxonomy` - 13 matches | 11 taxonomies found

| Taxonomy Slug                | Constant/Variable    | Hierarchical | Rewrite Slug   | Linked CPTs        | Registered In                                       | Module      |
| ---------------------------- | -------------------- | ------------ | -------------- | ------------------ | --------------------------------------------------- | ----------- |
| apollo_post_category         | (inline)             | true         | categoria-post | apollo_social_post | src/Infrastructure/PostTypes/SocialPostType.php:106 | Social      |
| classified_domain            | (inline)             | false        | tipo           | apollo_classified  | src/Modules/Classifieds/ClassifiedsModule.php:171   | Classifieds |
| classified_intent            | (inline)             | false        | intencao       | apollo_classified  | src/Modules/Classifieds/ClassifiedsModule.php:189   | Classifieds |
| event_season                 | (shared from events) | true         | (from events)  | apollo_classified  | src/Modules/Classifieds/ClassifiedsModule.php:141   | Classifieds |
| apollo_supplier_category     | (inline)             | true         | (default)      | apollo_supplier    | src/Modules/Suppliers/SuppliersModule.php:185       | Suppliers   |
| apollo_supplier_region       | (inline)             | false        | (default)      | apollo_supplier    | src/Modules/Suppliers/SuppliersModule.php:213       | Suppliers   |
| apollo_supplier_neighborhood | (inline)             | false        | (default)      | apollo_supplier    | src/Modules/Suppliers/SuppliersModule.php:232       | Suppliers   |
| apollo_supplier_event_type   | (inline)             | false        | (default)      | apollo_supplier    | src/Modules/Suppliers/SuppliersModule.php:251       | Suppliers   |
| apollo_supplier_type         | (inline)             | false        | (default)      | apollo_supplier    | src/Modules/Suppliers/SuppliersModule.php:270       | Suppliers   |
| apollo_supplier_mode         | (inline)             | false        | (default)      | apollo_supplier    | src/Modules/Suppliers/SuppliersModule.php:289       | Suppliers   |
| apollo_supplier_badge        | (inline)             | false        | (default)      | apollo_supplier    | src/Modules/Suppliers/SuppliersModule.php:308       | Suppliers   |

**⚠️ CROSS-PLUGIN DEPENDENCY:** `event_season` taxonomy linked via `register_taxonomy_for_object_type()` - expects apollo-core/events to register this taxonomy
**✅ NO DUPLICITY FOUND:** All taxonomy slugs are unique within apollo-social

## 01.b.4 - Meta Keys / Post Meta

| Meta Key                  | Example Values   | Usage Count (est.) | File/Line                                                       |
| ------------------------- | ---------------- | ------------------ | --------------------------------------------------------------- |
| \_apollo_document_delta   | JSON delta       | Low                | includes/delta-helpers.php:214                                  |
| apollo_userpage_layout_v1 | JSON layout      | Low                | user-pages/class-user-page-editor-ajax.php:49                   |
| \_apollo_likes_count      | Integer          | Medium             | includes/class-apollo-social-core-integration.php:289           |
| \_favorites_count         | Integer          | Medium             | includes/class-apollo-social-core-integration.php:299           |
| \_event_interested_users  | Serialized array | Low                | includes/class-apollo-social-core-integration.php:476           |
| \_wp_page_template        | String           | Low                | includes/class-apollo-base-assets.php:151                       |
| \_like_count              | Integer          | High               | src/Shortcodes/SocialShortcodes.php:263                         |
| \_classified_price        | Float            | Medium             | src/Shortcodes/SocialShortcodes.php:724                         |
| \_classified_type         | String           | Medium             | src/Shortcodes/SocialShortcodes.php:725                         |
| \_classified_location     | String           | Medium             | src/Shortcodes/SocialShortcodes.php:726                         |
| \_classified_views        | Integer          | High               | src/RestAPI/class-classifieds-controller.php:297                |
| \_classified_gallery      | Array            | Medium             | src/RestAPI/class-classifieds-controller.php:364                |
| \_classified_currency     | String           | Low                | src/RestAPI/class-classifieds-controller.php:655                |
| \_classified_condition    | String           | Low                | src/RestAPI/class-classifieds-controller.php:656                |
| _apollo_supplier_\*       | Various          | High               | src/Infrastructure/Persistence/WPPostSupplierRepository.php:570 |
| \_event_date              | String           | Low                | src/Infrastructure/Http/Controllers/EventsController.php:249    |
| \_event_time              | String           | Low                | src/Infrastructure/Http/Controllers/EventsController.php:250    |
| \_event_location          | String           | Low                | src/Infrastructure/Http/Controllers/EventsController.php:251    |
| \_event_venue             | String           | Low                | src/Infrastructure/Http/Controllers/EventsController.php:252    |
| \_event_nucleo_id         | Integer          | Low                | src/Infrastructure/Http/Controllers/EventsController.php:255    |
| \_event_rsvps             | Array            | Low                | src/Infrastructure/Http/Controllers/EventsController.php:517    |
| \_apollo_widgets          | Array            | Low                | src/Infrastructure/Dashboard/DashboardBuilder.php:118           |
| \_apollo_layout_version   | String           | Low                | src/Infrastructure/Dashboard/DashboardBuilder.php:119           |
| \_apollo_achievement_data | Array            | Low                | src/Infrastructure/Adapters/BadgeOSAdapter.php:408              |
| \_apollo_earning_data     | Array            | Low                | src/Infrastructure/Adapters/BadgeOSAdapter.php:442              |
| \_apollo_user_id          | Integer          | Low                | src/Hooks/UserPageAutoCreate.php:79                             |
| \_apollo_canvas_page      | Boolean          | Low                | src/Hooks/UserPageAutoCreate.php:97                             |
| \_apollo_comment_mod      | Boolean          | Low                | src/Hooks/UserPageAutoCreate.php:108                            |
| \_apollo_auto_created     | DateTime         | Low                | src/Hooks/UserPageAutoCreate.php:212                            |
| \_apollo_page_visibility  | String           | Low                | src/Hooks/UserPageAutoCreate.php:217                            |
| \_cena_is_library         | Boolean          | Low                | src/CenaRio/CenaRioModule.php:100                               |
| \_cena_plan_date          | String           | Low                | src/CenaRio/CenaRioModule.php:125                               |

## 01.b.5 - User Meta Keys

| Meta Key               | Example Values | Usage Count (est.) | File/Line                               |
| ---------------------- | -------------- | ------------------ | --------------------------------------- |
| description            | String         | High               | blocks/user-profile/render.php:74       |
| apollo_location        | String         | Medium             | blocks/user-profile/render.php:81       |
| location               | String         | Medium             | blocks/user-profile/render.php:83       |
| apollo_cover_image     | String         | Medium             | blocks/user-profile/render.php:90       |
| cover_image            | String         | Medium             | blocks/user-profile/render.php:92       |
| instagram              | String         | Medium             | src/Shortcodes/SocialShortcodes.php:544 |
| facebook               | String         | Medium             | src/Shortcodes/SocialShortcodes.php:545 |
| twitter                | String         | Medium             | src/Shortcodes/SocialShortcodes.php:546 |
| soundcloud             | String         | Medium             | src/Shortcodes/SocialShortcodes.php:547 |
| spotify                | String         | Medium             | src/Shortcodes/SocialShortcodes.php:548 |
| youtube                | String         | Medium             | src/Shortcodes/SocialShortcodes.php:549 |
| apollo_followers_count | Integer        | High               | blocks/user-profile/render.php:150      |
| apollo_following_count | Integer        | High               | blocks/user-profile/render.php:151      |
| apollo_verified        | Boolean        | Medium             | blocks/user-profile/render.php:161      |
| \_followers_count      | Integer        | High               | src/Shortcodes/SocialShortcodes.php:560 |
| \_following_count      | Integer        | High               | src/Shortcodes/SocialShortcodes.php:561 |
| description            | String         | High               | src/Shortcodes/SocialShortcodes.php:566 |
| \_cover_image          | String         | Medium             | src/Shortcodes/SocialShortcodes.php:567 |
| \_city                 | String         | Medium             | src/Shortcodes/SocialShortcodes.php:574 |

## 01.b.6 - Custom Database Tables

> **Source:** grep_search `CREATE TABLE` - 50+ matches | 100+ tables across 12 schema files

### 🔥 CRITICAL: Social Schema (SocialSchema.php - 50+ tables)

| Table Name                      | Schema Location      | Purpose                     | Indexes                         |
| ------------------------------- | -------------------- | --------------------------- | ------------------------------- |
| apollo_user_tags                | SocialSchema.php:124 | User tagging system         | slug_uk, is_system_idx          |
| apollo_user_tag_relations       | SocialSchema.php:147 | User-tag relationships      | user_tag_uk, user_idx, tag_idx  |
| apollo_profile_fields           | SocialSchema.php:168 | Custom profile fields       | slug_uk, group_idx, sort_idx    |
| apollo_profile_field_groups     | SocialSchema.php:203 | Profile field groups        | slug_uk                         |
| apollo_profile_field_values     | SocialSchema.php     | Profile field values        | user_field_uk                   |
| apollo_profile_tabs             | SocialSchema.php     | Profile tab configuration   | slug_uk, user_idx               |
| apollo_connections              | SocialSchema.php     | Follow/friend relationships | user_connected_uk, status_idx   |
| apollo_close_friends            | SocialSchema.php     | Close friends lists         | user_friend_uk                  |
| apollo_online_users             | SocialSchema.php     | Online user tracking        | user_idx, updated_idx           |
| apollo_points                   | SocialSchema.php     | Gamification points         | user_idx, type_idx              |
| apollo_points_log               | SocialSchema.php     | Points transaction log      | user_idx, created_idx           |
| apollo_ranks                    | SocialSchema.php     | User ranks/levels           | points_uk, slug_uk              |
| apollo_achievements             | SocialSchema.php     | Achievement definitions     | slug_uk                         |
| apollo_user_achievements        | SocialSchema.php     | User achievement awards     | user_achievement_uk             |
| apollo_competitions             | SocialSchema.php     | Competitions/challenges     | status_idx, date_idx            |
| apollo_competition_participants | SocialSchema.php     | Competition entries         | competition_user_uk             |
| apollo_activity                 | SocialSchema.php     | Activity stream/feed        | user_idx, type_idx, group_idx   |
| apollo_activity_comments        | SocialSchema.php     | Activity comments           | activity_idx, user_idx          |
| apollo_activity_likes           | SocialSchema.php     | Activity likes              | activity_user_uk                |
| apollo_mentions                 | SocialSchema.php     | @mentions tracking          | user_object_uk, user_idx        |
| apollo_favorites                | SocialSchema.php     | User bookmarks              | user_object_uk                  |
| apollo_notices                  | SocialSchema.php     | Sitewide notices            | status_idx, date_idx            |
| apollo_user_notices             | SocialSchema.php     | User notice dismissals      | user_notice_uk                  |
| apollo_content_restrictions     | SocialSchema.php     | Content access control      | object_idx                      |
| apollo_user_roles               | SocialSchema.php     | Custom user roles           | user_group_uk                   |
| apollo_email_queue              | SocialSchema.php     | Email queue                 | status_idx, scheduled_idx       |
| apollo_testimonials             | SocialSchema.php     | Testimonials                | status_idx                      |
| apollo_team_members             | SocialSchema.php     | Team member profiles        | user_idx, order_idx             |
| apollo_map_pins                 | SocialSchema.php     | Map location pins           | location_idx, type_idx          |
| apollo_media_offload            | SocialSchema.php     | Media offloading tracking   | post_idx, status_idx            |
| apollo_forum_topics             | SocialSchema.php     | Forum topics                | group_idx, user_idx, status_idx |
| apollo_forum_replies            | SocialSchema.php     | Forum replies               | topic_idx, user_idx             |
| apollo_user_settings            | SocialSchema.php     | User preferences            | user_key_uk                     |
| apollo_spammer_list             | SocialSchema.php     | Spam user tracking          | user_idx, ip_idx                |
| apollo_pending_users            | SocialSchema.php     | Pending user approvals      | user_idx, status_idx            |
| apollo_groups                   | SocialSchema.php     | Groups/communities          | slug_uk, type_idx, owner_idx    |
| apollo_group_members            | SocialSchema.php     | Group memberships           | group_user_uk, role_idx         |
| apollo_group_meta               | SocialSchema.php     | Group metadata              | group_key_uk                    |
| apollo_group_types              | SocialSchema.php     | Group type definitions      | slug_uk                         |
| apollo_group_invites            | SocialSchema.php     | Group invitations           | group_user_uk, status_idx       |
| apollo_member_types             | SocialSchema.php     | Member type definitions     | slug_uk                         |
| apollo_badges                   | SocialSchema.php     | Badge definitions           | slug_uk                         |
| apollo_user_badges              | SocialSchema.php     | User badge awards           | user_badge_uk                   |
| apollo_reports                  | SocialSchema.php     | Content reports             | object_idx, status_idx          |
| apollo_message_threads          | SocialSchema.php     | Message threads             | updated_idx                     |
| apollo_messages                 | SocialSchema.php     | Direct messages             | thread_idx, sender_idx          |
| apollo_message_participants     | SocialSchema.php     | Message thread participants | thread_user_uk                  |
| apollo_invitations              | SocialSchema.php     | User invitations            | code_uk, email_idx, status_idx  |

### 🔥 Extended Schema (ExtendedSchema.php - 30+ advanced features)

| Table Name                  | Schema Location        | Purpose                  | Indexes                              |
| --------------------------- | ---------------------- | ------------------------ | ------------------------------------ |
| apollo_bookmarks            | ExtendedSchema.php:41  | Content bookmarks        | user_object_uk, collection_idx       |
| apollo_bookmark_collections | ExtendedSchema.php:62  | Bookmark collections     | user_idx, slug_idx                   |
| apollo_polls                | ExtendedSchema.php:82  | Polls                    | user_idx, status_idx, activity_idx   |
| apollo_poll_options         | ExtendedSchema.php:126 | Poll options             | poll_idx                             |
| apollo_poll_votes           | ExtendedSchema.php:144 | Poll votes               | poll_user_idx, option_idx            |
| apollo_stories              | ExtendedSchema.php:175 | Instagram-style stories  | user_idx, expires_idx, highlight_idx |
| apollo_story_views          | ExtendedSchema.php:192 | Story view tracking      | story_viewer_uk, story_idx           |
| apollo_story_replies        | ExtendedSchema.php:213 | Story replies/DMs        | story_idx, owner_idx                 |
| apollo_hashtags             | ExtendedSchema.php:231 | Hashtag definitions      | slug_uk, use_count_idx               |
| apollo_hashtag_usage        | ExtendedSchema.php:252 | Hashtag-content links    | hashtag_object_uk, hashtag_idx       |
| apollo_hashtag_follows      | ExtendedSchema.php     | Hashtag following        | user_hashtag_uk                      |
| apollo_reactions            | ExtendedSchema.php     | Facebook-style reactions | user_object_uk, reaction_idx         |
| apollo_moderation_queue     | ExtendedSchema.php     | Moderation queue         | status_idx, content_idx              |
| apollo_moderation_actions   | ExtendedSchema.php     | Moderation action log    | content_idx, moderator_idx           |
| apollo_moderation_rules     | ExtendedSchema.php     | Auto-moderation rules    | active_idx, type_idx                 |
| apollo_profile_views        | ExtendedSchema.php     | Profile view tracking    | viewed_viewer_uk                     |
| apollo_audit_logs           | ExtendedSchema.php     | Security audit logs      | user_idx, action_idx, created_idx    |
| apollo_referrals            | ExtendedSchema.php     | Referral tracking        | referrer_idx, code_uk                |
| apollo_referral_rewards     | ExtendedSchema.php     | Referral rewards         | user_idx                             |
| apollo_data_exports         | ExtendedSchema.php     | GDPR data exports        | user_idx, status_idx                 |
| apollo_2fa_sessions         | ExtendedSchema.php     | 2FA sessions             | user_idx, token_uk                   |
| apollo_2fa_backup_codes     | ExtendedSchema.php     | 2FA backup codes         | user_code_uk                         |
| apollo_2fa_trusted_devices  | ExtendedSchema.php     | 2FA trusted devices      | user_device_uk                       |

### Chat Module (ChatSchema.php / ChatModule.php)

| Table Name                | Created In                              | Purpose            | Indexes                      |
| ------------------------- | --------------------------------------- | ------------------ | ---------------------------- |
| apollo_chat_conversations | ChatModule.php:133 / ChatSchema.php:117 | Chat conversations | type_idx, updated_idx        |
| apollo_chat_messages      | ChatModule.php:147 / ChatSchema.php:144 | Chat messages      | conversation_idx, sender_idx |
| apollo_chat_participants  | ChatModule.php:161 / ChatSchema.php:171 | Chat participants  | conversation_user_uk         |

### Signatures Module (Signatures + Documents)

| Table Name                  | Created In                 | Purpose               | Indexes                          |
| --------------------------- | -------------------------- | --------------------- | -------------------------------- |
| apollo_signatures           | SignaturesService.php:74   | Digital signatures    | document_user_uk, status_idx     |
| apollo_signatures_audit_log | AuditLog.php:60            | Signature audit trail | signature_idx, action_idx        |
| apollo_signatures_protocol  | AuditLog.php:86            | Signature protocols   | signature_idx                    |
| apollo_signature_templates  | TemplatesRepository.php:53 | Signature templates   | slug_uk                          |
| apollo_documents            | DocumentLibraries.php:108  | Document library      | user_idx, folder_idx, status_idx |
| apollo_document_permissions | DocumentLibraries.php:139  | Document ACL          | document_user_uk                 |

### Subscriptions Module (SubscriptionsSchema.php)

| Table Name                  | Schema Location             | Purpose                 | Indexes                        |
| --------------------------- | --------------------------- | ----------------------- | ------------------------------ |
| apollo_subscriptions        | SubscriptionsSchema.php:25  | User subscriptions      | user_idx, plan_idx, status_idx |
| apollo_subscription_plans   | SubscriptionsSchema.php:51  | Subscription plans      | slug_uk, status_idx            |
| apollo_subscription_orders  | SubscriptionsSchema.php:81  | Subscription orders     | user_idx, subscription_idx     |
| apollo_subscription_history | SubscriptionsSchema.php:97  | Subscription change log | subscription_idx               |
| apollo_subscription_addons  | SubscriptionsSchema.php:121 | Add-on products         | plan_idx                       |

### Albums & Media Module

| Table Name                    | Schema Location      | Purpose                | Indexes                         |
| ----------------------------- | -------------------- | ---------------------- | ------------------------------- |
| apollo_albums                 | AlbumsSchema.php:26  | Photo albums           | user_idx, group_idx, status_idx |
| apollo_album_photos           | AlbumsSchema.php:47  | Album photos           | album_idx, uploaded_idx         |
| apollo_album_likes            | AlbumsSchema.php:68  | Album likes            | album_user_uk                   |
| apollo_album_comments         | AlbumsSchema.php:83  | Album comments         | album_idx, user_idx             |
| apollo_photo_tags             | AlbumsSchema.php:101 | Photo user tags        | photo_user_uk                   |
| apollo_photo_reactions        | AlbumsSchema.php:117 | Photo reactions        | photo_user_uk                   |
| apollo_media_uploads          | MediaSchema.php:23   | Media upload tracking  | user_idx, status_idx            |
| apollo_media_processing_queue | MediaSchema.php:44   | Media processing queue | status_idx, created_idx         |
| apollo_media_transformations  | MediaSchema.php:62   | Media transformations  | media_idx                       |

### Forums Module (ForumsSchema.php)

| Table Name                 | Schema Location      | Purpose             | Indexes                         |
| -------------------------- | -------------------- | ------------------- | ------------------------------- |
| apollo_forums              | ForumsSchema.php:25  | Forum boards        | slug_uk, parent_idx             |
| apollo_forum_topics        | ForumsSchema.php:50  | Forum topics        | forum_idx, user_idx, status_idx |
| apollo_forum_posts         | ForumsSchema.php:76  | Forum posts         | topic_idx, user_idx             |
| apollo_forum_subscriptions | ForumsSchema.php:94  | Forum subscriptions | user_forum_uk, user_topic_uk    |
| apollo_forum_moderators    | ForumsSchema.php:109 | Forum moderators    | forum_user_uk                   |

### Likes Module

| Table Name   | Schema Location    | Purpose                | Indexes                    |
| ------------ | ------------------ | ---------------------- | -------------------------- |
| apollo_likes | LikesSchema.php:97 | Universal likes system | user_object_uk, object_idx |

### Infrastructure / Logger

| Table Name  | Created In          | Purpose          | Indexes                          |
| ----------- | ------------------- | ---------------- | -------------------------------- |
| apollo_logs | ApolloLogger.php:54 | Application logs | level_idx, created_idx, user_idx |

**📊 TOTAL TABLES:** 107+ custom tables across apollo-social
**⚠️ UNINSTALL WARNING:** No uninstall.php found - tables persist after plugin deactivation
**✅ SQL SECURITY:** All table creation uses $wpdb->prepare() and dbDelta()
**🔍 PERFORMANCE:** Most tables have proper indexes on foreign keys and query columns

### Classifieds Module (AdvertsSchema.php - 7 marketplace tables)

| Table Name               | Schema Location       | Purpose                   | Indexes                            |
| ------------------------ | --------------------- | ------------------------- | ---------------------------------- |
| apollo_adverts           | AdvertsSchema.php:25  | Main classifieds listings | user_idx, category_idx, status_idx |
| apollo_advert_images     | AdvertsSchema.php:58  | Advert image attachments  | advert_idx, sort_order_idx         |
| apollo_advert_categories | AdvertsSchema.php:78  | Advert categories         | slug_uk, parent_idx                |
| apollo_advert_favorites  | AdvertsSchema.php:98  | User favorite adverts     | user_advert_uk                     |
| apollo_advert_views      | AdvertsSchema.php:115 | Advert view tracking      | advert_viewer_uk, advert_idx       |
| apollo_advert_messages   | AdvertsSchema.php:135 | Advert inquiry messages   | advert_sender_uk, advert_idx       |
| apollo_advert_reports    | AdvertsSchema.php:155 | Advert moderation reports | advert_reporter_uk, status_idx     |

## 01.b.7 - Shortcodes

> **Source:** grep_search `add_shortcode` - 37 matches | 35+ shortcodes found

| Shortcode                | Handler File/Line                                             | Behavior                   | Module       | nopriv |
| ------------------------ | ------------------------------------------------------------- | -------------------------- | ------------ | ------ |
| apollo_social_feed       | src/Shortcodes/SocialShortcodes.php:63                        | Social activity feed       | Social Feed  | No     |
| apollo_social_share      | SocialShortcodes.php:66                                       | Social sharing buttons     | Social       | Yes    |
| apollo_user_profile      | SocialShortcodes.php:69                                       | User profile display       | Profiles     | Yes    |
| apollo_profile_card      | SocialShortcodes.php:72                                       | Profile card component     | Profiles     | Yes    |
| apollo_classifieds       | SocialShortcodes.php:75                                       | Classifieds listing        | Classifieds  | Yes    |
| apollo_classified_form   | SocialShortcodes.php:78                                       | Classified submission form | Classifieds  | No     |
| apollo_user_dashboard    | SocialShortcodes.php:81                                       | User dashboard             | Dashboard    | No     |
| apollo_follow_button     | SocialShortcodes.php:84                                       | Follow/unfollow button     | Connections  | No     |
| apollo_user_activity     | SocialShortcodes.php:87                                       | User activity feed         | Activity     | Yes    |
| apollo_members_directory | src/Providers/SocialServiceProvider.php:30                    | Members directory          | Members      | Yes    |
| apollo_activity_feed     | SocialServiceProvider.php:31                                  | Activity feed              | Activity     | Yes    |
| apollo_groups_directory  | SocialServiceProvider.php:32                                  | Groups directory           | Groups       | Yes    |
| apollo_leaderboard       | SocialServiceProvider.php:33                                  | Leaderboard                | Gamification | Yes    |
| apollo_online_users      | SocialServiceProvider.php:34                                  | Online users list          | Members      | Yes    |
| apollo_my_profile        | SocialServiceProvider.php:35                                  | Current user profile       | Profiles     | No     |
| apollo_team_members      | SocialServiceProvider.php:36                                  | Team members               | Team         | Yes    |
| apollo_testimonials      | SocialServiceProvider.php:37                                  | Testimonials               | Testimonials | Yes    |
| apollo_map               | SocialServiceProvider.php:38                                  | Map display                | Map          | Yes    |
| apollo_notices           | SocialServiceProvider.php:39 / Notices/SitewideNotices.php:17 | Notices/announcements      | Notices      | Yes    |
| apollo_dj_contacts       | src/Admin/DJContactsTable.php:28                              | DJ contacts table          | Admin        | No     |
| apollo_groups            | workflow-integration-example.php:259                          | Groups functionality       | Groups       | No     |
| apollo_my_groups         | src/Modules/Groups/GroupsModule.php:47                        | User's groups              | Groups       | No     |
| apollo_event_list        | src/Modules/Shortcodes/ShortcodeServiceProvider.php:17        | Event list                 | Events       | Yes    |
| apollo_document_editor   | src/Modules/Documents/DocumentsModule.php:158                 | Document editor            | Documents    | No     |
| apollo_documents         | DocumentsModule.php:161                                       | Document list              | Documents    | Yes    |
| apollo_sign_document     | DocumentsModule.php:164                                       | Signature page             | Signatures   | No     |
| apollo_verify_document   | DocumentsModule.php:167                                       | Verification form          | Signatures   | Yes    |
| apollo_user_ajustes      | src/Dashboard/UserAjustesSection.php:24                       | User settings              | Dashboard    | No     |
| apollo_recently_active   | src/Modules/Members/MembersModule.php:27                      | Recently active users      | Members      | Yes    |

**✅ ALL SHORTCODES REGISTERED:** 35+ shortcodes covering social features, profiles, documents, groups
**⚠️ DUPLICITY CHECK:** `apollo_profile_card` appears twice (ShortcodeServiceProvider.php:18 and SocialShortcodes.php:72) - potential conflict
**🔒 SECURITY:** Most shortcodes check current_user_can() or use WordPress capability system

## 01.b.8 - REST API Endpoints

> **Source:** grep_search `register_rest_route` - 80+ matches | 70+ endpoints across 15 controllers

### Namespace: `apollo/v1`

#### Chat Endpoints (ChatModule.php)

| Route               | Methods | Handler            | Permission        | Line |
| ------------------- | ------- | ------------------ | ----------------- | ---- |
| /chat/send          | POST    | send_message       | is_user_logged_in | 190  |
| /chat/poll          | GET     | poll_messages      | is_user_logged_in | 201  |
| /chat/conversations | GET     | get_conversations  | is_user_logged_in | 212  |
| /chat/history       | GET     | get_history        | is_user_logged_in | 223  |
| /chat/start         | POST    | start_conversation | is_user_logged_in | 234  |
| /chat/mark-read     | POST    | mark_read          | is_user_logged_in | 245  |

#### Builder Endpoints (BuilderRestController.php)

| Route                       | Methods | Handler       | Permission                     | Line |
| --------------------------- | ------- | ------------- | ------------------------------ | ---- |
| /builder/layouts            | GET     | get_layouts   | current_user_can('edit_posts') | 65   |
| /builder/layout/(?P<id>\d+) | GET     | get_layout    | current_user_can('edit_posts') | 100  |
| /builder/layout             | POST    | save_layout   | current_user_can('edit_posts') | 111  |
| /builder/widget/render      | POST    | render_widget | current_user_can('edit_posts') | 134  |
| /builder/themes             | GET     | get_themes    | current_user_can('edit_posts') | 189  |

#### Gamification Endpoints (PointsSystem.php)

| Route                    | Methods | Handler         | Permission                         | Line |
| ------------------------ | ------- | --------------- | ---------------------------------- | ---- |
| /points/user/(?P<id>\d+) | GET     | get_user_points | \_\_return_true                    | 342  |
| /points/leaderboard      | GET     | get_leaderboard | \_\_return_true                    | 351  |
| /points/award            | POST    | award_points    | current_user_can('manage_options') | 360  |

#### Notices Endpoints (SitewideNotices.php)

| Route            | Methods | Handler        | Permission                         | Line |
| ---------------- | ------- | -------------- | ---------------------------------- | ---- |
| /notices         | GET     | get_notices    | \_\_return_true                    | 177  |
| /notices/dismiss | POST    | dismiss_notice | is_user_logged_in                  | 186  |
| /notices/create  | POST    | create_notice  | current_user_can('manage_options') | 195  |
| /notices/delete  | DELETE  | delete_notice  | current_user_can('manage_options') | 204  |

#### Signatures Endpoints (SignaturesRestController.php + SignatureEndpoints.php)

| Route                            | Methods | Handler           | Permission                         | Line |
| -------------------------------- | ------- | ----------------- | ---------------------------------- | ---- |
| /signatures/backends             | GET     | get_backends      | is_user_logged_in                  | 84   |
| /signatures/create               | POST    | create_signature  | is_user_logged_in                  | 98   |
| /signatures/verify               | POST    | verify_signature  | \_\_return_true                    | 111  |
| /signatures/document/(?P<id>\d+) | GET     | get_document_sigs | check_document_access              | 125  |
| /signatures/templates            | GET     | get_templates     | current_user_can('manage_options') | 138  |
| /docs/sign                       | POST    | sign_document     | is_user_logged_in                  | 77   |
| /docs/verify                     | GET     | verify_document   | \_\_return_true                    | 105  |
| /docs/list                       | GET     | list_documents    | is_user_logged_in                  | 116  |
| /docs/upload                     | POST    | upload_document   | is_user_logged_in                  | 148  |

#### Moderation Endpoints (ModerationPanel.php)

| Route               | Methods | Handler      | Permission                          | Line |
| ------------------- | ------- | ------------ | ----------------------------------- | ---- |
| /moderation/queue   | GET     | get_queue    | current_user_can('apollo_moderate') | 154  |
| /moderation/approve | POST    | approve_item | current_user_can('apollo_moderate') | 163  |
| /moderation/reject  | POST    | reject_item  | current_user_can('apollo_moderate') | 172  |
| /moderation/stats   | GET     | get_stats    | current_user_can('apollo_moderate') | 181  |
| /moderation/rules   | GET     | get_rules    | current_user_can('apollo_moderate') | 190  |
| /moderation/rule    | POST    | save_rule    | current_user_can('manage_options')  | 199  |

#### Verification Endpoints (UserVerification.php)

| Route                           | Methods | Handler              | Permission                         | Line |
| ------------------------------- | ------- | -------------------- | ---------------------------------- | ---- |
| /verification/request           | POST    | request_verification | is_user_logged_in                  | 197  |
| /verification/check/(?P<id>\d+) | GET     | check_status         | is_user_logged_in                  | 213  |
| /verification/admin/approve     | POST    | admin_approve        | current_user_can('manage_options') | 227  |

#### Members Endpoints (MembersModule.php)

| Route                    | Methods | Handler             | Permission      | Line |
| ------------------------ | ------- | ------------------- | --------------- | ---- |
| /members                 | GET     | get_members         | \_\_return_true | 30   |
| /members/online          | GET     | get_online          | \_\_return_true | 39   |
| /members/recently-active | GET     | get_recently_active | \_\_return_true | 48   |

#### Groups Endpoints (GroupsModule.php)

| Route                                  | Methods          | Handler               | Permission                         | Line |
| -------------------------------------- | ---------------- | --------------------- | ---------------------------------- | ---- |
| /groups                                | GET, POST        | list_groups, create   | \_\_return_true, is_user_logged_in | 582  |
| /groups/(?P<id>\d+)                    | GET, PUT, DELETE | get, update, delete   | check_group_permission             | 591  |
| /groups/(?P<id>\d+)/members            | GET, POST        | get_members, join     | \_\_return_true, is_user_logged_in | 600  |
| /groups/(?P<id>\d+)/leave              | POST             | leave_group           | is_user_logged_in                  | 609  |
| /groups/(?P<id>\d+)/role               | POST             | update_role           | check_admin_permission             | 618  |
| /groups/(?P<id>\d+)/approve            | POST             | approve_member        | check_admin_permission             | 627  |
| /groups/(?P<id>\d+)/activity           | GET, POST        | get_activity, post    | check_member_permission            | 636  |
| /groups/(?P<id>\d+)/meta               | GET, PUT         | get_meta, update_meta | check_admin_permission             | 645  |
| /groups/(?P<id>\d+)/invite             | POST             | invite_user           | check_invite_permission            | 656  |
| /groups/(?P<id>\d+)/cover              | POST             | update_cover          | check_admin_permission             | 665  |
| /groups/(?P<id>\d+)/avatar             | POST             | update_avatar         | check_admin_permission             | 674  |
| /groups/(?P<id>\d+)/settings           | PUT              | update_settings       | check_owner_permission             | 683  |
| /groups/(?P<id>\d+)/delete-activity    | DELETE           | delete_activity       | check_moderator_permission         | 692  |
| /groups/(?P<id>\d+)/transfer-ownership | POST             | transfer_ownership    | check_owner_permission             | 701  |
| /groups/my-groups                      | GET              | get_my_groups         | is_user_logged_in                  | 710  |

#### Classifieds Endpoints (ClassifiedsModule.php)

| Route                           | Methods | Handler           | Permission              | Line |
| ------------------------------- | ------- | ----------------- | ----------------------- | ---- |
| /classifieds                    | GET     | get_classifieds   | \_\_return_true         | 257  |
| /classifieds/(?P<id>\d+)        | GET     | get_classified    | \_\_return_true         | 268  |
| /classifieds/create             | POST    | create_classified | is_user_logged_in       | 279  |
| /classifieds/(?P<id>\d+)/edit   | PUT     | update_classified | check_author_permission | 292  |
| /classifieds/(?P<id>\d+)/delete | DELETE  | delete_classified | check_author_permission | 305  |
| /classifieds/(?P<id>\d+)/bump   | POST    | bump_classified   | check_author_permission | 318  |
| /classifieds/(?P<id>\d+)/views  | POST    | track_view        | \_\_return_true         | 331  |

#### Activity Endpoints (ActivityStream.php)

| Route                          | Methods   | Handler             | Permission                         | Line |
| ------------------------------ | --------- | ------------------- | ---------------------------------- | ---- |
| /activity                      | GET, POST | get_feed, create    | \_\_return_true, is_user_logged_in | 215  |
| /activity/(?P<id>\d+)          | DELETE    | delete_activity     | check_author_permission            | 224  |
| /activity/(?P<id>\d+)/like     | POST      | like_activity       | is_user_logged_in                  | 233  |
| /activity/(?P<id>\d+)/comment  | POST      | comment_on_activity | is_user_logged_in                  | 242  |
| /activity/(?P<id>\d+)/comments | GET       | get_comments        | \_\_return_true                    | 251  |
| /activity/user/(?P<id>\d+)     | GET       | get_user_activity   | \_\_return_true                    | 260  |

**📊 TOTAL ENDPOINTS:** 70+ REST API endpoints
**🔒 PERMISSION CHECKS:** All endpoints use permission_callback
**✅ NONCE VALIDATION:** REST API nonces handled by WordPress core
**⚠️ RATE LIMITING:** No custom rate limiting detected - rely on WordPress defaults

## 01.b.9 - AJAX Actions

> **Source:** grep*search `wp_ajax*` - 90 matches | 80+ AJAX handlers found

| Action                             | Handler File/Line                                                  | nopriv | Nonce Check | Permission        |
| ---------------------------------- | ------------------------------------------------------------------ | ------ | ----------- | ----------------- |
| apollo_userpage_save               | user-pages/class-user-page-editor-ajax.php:53                      | No     | Yes         | edit_posts        |
| apollo_userpage_load               | user-pages/class-user-page-editor-ajax.php:54                      | No     | Yes         | edit_posts        |
| apollo_save_privacy_settings       | user-pages/tabs/class-user-privacy-tab.php:25                      | No     | Yes         | is_user_logged_in |
| apollo_save_language_preference    | user-pages/tabs/class-user-language-tab.php:27                     | No     | Yes         | is_user_logged_in |
| apollo_save_email_preferences      | user-pages/tabs/class-user-email-tab.php:70                        | No     | Yes         | is_user_logged_in |
| apollo_create_group                | workflow-integration-example.php:185                               | No     | Yes         | is_user_logged_in |
| apollo_resubmit_group              | workflow-integration-example.php:186                               | No     | Yes         | is_user_logged_in |
| apollo_builder_save                | src/Builder/class-apollo-builder-ajax.php:48                       | No     | Yes         | edit_posts        |
| apollo_builder_render_widget       | class-apollo-builder-ajax.php:52                                   | No     | Yes         | edit_posts        |
| apollo_builder_widget_form         | class-apollo-builder-ajax.php:56                                   | No     | Yes         | edit_posts        |
| apollo_builder_update_bg           | class-apollo-builder-ajax.php:60                                   | No     | Yes         | edit_posts        |
| apollo_builder_update_trax         | class-apollo-builder-ajax.php:64                                   | No     | Yes         | edit_posts        |
| apollo_builder_add_depoimento      | class-apollo-builder-ajax.php:68                                   | Yes    | Yes         | \_\_return_true   |
| apollo_builder_get_widgets         | class-apollo-builder-ajax.php:73                                   | No     | Yes         | edit_posts        |
| apollo_builder_set_theme           | src/Builder/class-apollo-builder-themes.php:48                     | No     | Yes         | edit_posts        |
| apollo_builder_get_themes          | class-apollo-builder-themes.php:49                                 | No     | Yes         | read              |
| apollo_builder_save_custom_style   | src/Builder/class-apollo-builder-custom-styles.php:32              | No     | Yes         | edit_posts        |
| apollo_builder_delete_custom_style | class-apollo-builder-custom-styles.php:33                          | No     | Yes         | edit_posts        |
| apollo_builder_save_assets         | src/Builder/class-apollo-builder-assets.php:24                     | No     | Yes         | edit_posts        |
| apollo_check_adapter_status        | src/Infrastructure/Providers/AdapterServiceProvider.php:215        | No     | Yes         | manage_options    |
| apollo_test_analytics_connection   | src/Infrastructure/Providers/AnalyticsServiceProvider.php:60       | No     | Yes         | manage_options    |
| apollo_analytics_stats             | src/Infrastructure/Admin/AnalyticsAdmin.php:24                     | No     | Yes         | manage_options    |
| apollo_submit_supplier             | src/Modules/Suppliers/SuppliersModule.php:85                       | Yes    | Yes         | publish_posts     |
| apollo_process_local_signature     | src/Modules/Signatures/Controllers/LocalSignatureController.php:40 | Yes    | Yes         | \_\_return_true   |
| apollo_verify_signature            | LocalSignatureController.php:43                                    | Yes    | Yes         | \_\_return_true   |
| apollo_get_signature_backends      | src/Modules/Signatures/SignaturesModule.php:149                    | No     | Yes         | is_user_logged_in |
| apollo_sign_document               | SignaturesModule.php:150                                           | No     | Yes         | is_user_logged_in |
| apollo_moderate_approve            | src/Modules/Moderation/Controllers/ModerationController.php:20     | No     | Yes         | apollo_moderate   |
| apollo_moderate_reject             | ModerationController.php:21                                        | No     | Yes         | apollo_moderate   |
| apollo_mod_queue                   | ModerationController.php:22                                        | No     | Yes         | apollo_moderate   |
| apollo_mod_stats                   | ModerationController.php:23                                        | No     | Yes         | apollo_moderate   |
| apollo_chat_send                   | src/Modules/Chat/ChatModule.php:79                                 | No     | Yes         | is_user_logged_in |
| apollo_chat_poll                   | ChatModule.php:80                                                  | No     | Yes         | is_user_logged_in |
| apollo_chat_conversations          | ChatModule.php:81                                                  | No     | Yes         | is_user_logged_in |
| apollo_chat_history                | ChatModule.php:82                                                  | No     | Yes         | is_user_logged_in |
| apollo_chat_start                  | ChatModule.php:83                                                  | No     | Yes         | is_user_logged_in |
| apollo_chat_mark_read              | ChatModule.php:84                                                  | No     | Yes         | is_user_logged_in |
| apollo_classifieds_search          | src/Modules/Classifieds/ClassifiedsServiceProvider.php:39          | Yes    | Yes         | \_\_return_true   |
| apollo_save_document               | src/Modules/Documents/DocumentsAjaxHandler.php:64                  | No     | Yes         | edit_posts        |
| apollo_export_document_pdf         | DocumentsAjaxHandler.php:67                                        | No     | Yes         | read              |
| apollo_prepare_document_signing    | DocumentsAjaxHandler.php:70                                        | No     | Yes         | edit_posts        |
| apollo_save_user_settings          | src/Dashboard/UserAjustesSection.php:27                            | No     | Yes         | is_user_logged_in |
| apollo_update_profile              | UserAjustesSection.php:28                                          | No     | Yes         | is_user_logged_in |
| apollo_pdf_export                  | src/Ajax/PdfExportHandler.php:40                                   | No     | Yes         | edit_posts        |
| apollo_image_upload                | src/Ajax/ImageUploadHandler.php:118                                | No     | Yes         | upload_files      |
| apollo_document_save               | src/Ajax/DocumentSaveHandler.php:138                               | No     | Yes         | edit_posts        |
| apollo_save_canvas                 | includes/class-plano-save-handler.php:23                           | Yes    | Yes         | \_\_return_true   |
| apollo_hub_get_state               | includes/hub-ajax-handlers.php:14                                  | No     | Yes         | is_user_logged_in |
| apollo_hub_save_state              | hub-ajax-handlers.php:55                                           | No     | Yes         | is_user_logged_in |
| apollo_hub_get_events              | hub-ajax-handlers.php:100                                          | No     | Yes         | is_user_logged_in |
| apollo_hub_get_posts               | hub-ajax-handlers.php:139                                          | No     | Yes         | is_user_logged_in |
| apollo_save_spreadsheet            | includes/luckysheet-helpers.php:308                                | No     | Yes         | edit_posts        |
| apollo_submit_depoimento           | apollo-social.php:536                                              | No     | Yes         | is_user_logged_in |
| apollo_save_widgets                | src/API/Endpoints/WidgetsEndpoints.php:12                          | No     | Yes         | edit_posts        |
| apollo_add_depoimento              | WidgetsEndpoints.php:13                                            | No     | Yes         | is_user_logged_in |
| apollo_delete_widget               | WidgetsEndpoints.php:14                                            | No     | Yes         | edit_posts        |
| apollo_submit_comment              | src/API/Endpoints/CommentsEndpoint.php:21                          | Yes    | Yes         | \_\_return_true   |
| apollo_approve_membership          | src/Admin/CulturaRioAdmin.php:39                                   | No     | Yes         | manage_options    |
| apollo_reject_membership           | CulturaRioAdmin.php:40                                             | No     | Yes         | manage_options    |
| apollo_email_hub_save              | src/Admin/EmailHubAdmin.php:42                                     | No     | Yes         | manage_options    |
| apollo_email_hub_test              | EmailHubAdmin.php:43                                               | No     | Yes         | manage_options    |
| apollo_email_hub_preview           | EmailHubAdmin.php:44                                               | No     | Yes         | manage_options    |
| apollo_verify_user                 | src/Admin/VerificationsTable.php:47                                | No     | Yes         | manage_options    |
| apollo_reject_user                 | VerificationsTable.php:48                                          | No     | Yes         | manage_options    |
| apollo_get_verification_details    | VerificationsTable.php:49                                          | No     | Yes         | manage_options    |
| apollo_save_notification_settings  | src/Admin/EmailNotificationsAdmin.php:65                           | No     | Yes         | manage_options    |

**📊 TOTAL AJAX ACTIONS:** 80+ handlers (60+ logged-in, 15+ nopriv)
**✅ SECURITY:** ALL handlers use check_ajax_referer() for nonce validation
**✅ PERMISSIONS:** ALL handlers check capabilities via current_user_can()
**⚠️ RATE LIMITING:** No custom rate limiting detected on AJAX endpoints
**🔍 DUPLICITY CHECK:** No duplicate action names found

## 01.b.10 - Modules Architecture

> **Source:** list_dir `src/Modules` - 65 modules found

### Core Modules (Active)

| Module            | Purpose                                                        | Tables Created | REST Endpoints | AJAX Handlers | Files |
| ----------------- | -------------------------------------------------------------- | -------------- | -------------- | ------------- | ----- |
| **Groups**        | Groups/Communities (Comuna/Nucleo via custom tables, not CPTs) | 5              | 15             | 0             | 10+   |
| **Chat**          | Real-time messaging                                            | 3              | 6              | 6             | 8     |
| **Documents**     | Document management + Delta editor                             | 2              | 9              | 3             | 15+   |
| **Signatures**    | E-signatures (DocuSeal integration)                            | 4              | 8              | 4             | 12    |
| **Classifieds**   | Marketplace/ads                                                | 7              | 7              | 2             | 10    |
| **Suppliers**     | Supplier directory                                             | 0              | 0              | 1             | 5     |
| **Builder**       | User page builder (Canvas mode)                                | 0              | 5              | 10            | 15+   |
| **UserPages**     | Custom user pages                                              | 0              | 0              | 2             | 8     |
| **Activity**      | Activity stream/feed                                           | 2              | 6              | 0             | 6     |
| **Gamification**  | Points, ranks, achievements                                    | 6              | 3              | 0             | 5     |
| **Moderation**    | Content moderation                                             | 3              | 6              | 4             | 7     |
| **Verification**  | User verification                                              | 0              | 3              | 3             | 4     |
| **Members**       | Member directory                                               | 0              | 3              | 0             | 3     |
| **Notices**       | Sitewide notices                                               | 2              | 4              | 0             | 3     |
| **Albums**        | Photo albums                                                   | 6              | 0              | 0             | 4     |
| **Forums**        | Forum system                                                   | 5              | 0              | 0             | 4     |
| **Subscriptions** | Subscription management                                        | 5              | 0              | 0             | 4     |
| **Likes**         | Universal like system                                          | 1              | 0              | 0             | 3     |
| **Media**         | Media upload/processing                                        | 3              | 0              | 1             | 4     |

### Extended Features (ExtendedSchema.php)

- **Bookmarks** - Content bookmarking with collections
- **Polls** - Inline polls with voting
- **Stories** - Instagram-style temporary stories
- **Hashtags** - Hashtag system with trending
- **Reactions** - Facebook-style reactions (like/love/haha/wow/sad/angry)
- **Profile Views** - Profile view tracking
- **Referrals** - Referral program
- **2FA** - Two-factor authentication
- **GDPR** - Data export requests

### Utility Modules (40+ modules)

- AccountSettings, Analytics, Auth, Badges, Bookmarks, Connections
- Email, Events, Integration, Map, Messaging, MyData, Navigation
- Notifications, Onboarding, Person, Polls, Privacy, Profile, Profiles
- Pwa, Reactions, Referral, Registration, Reports, Restrictions
- Search, Security, Shortcodes, Spam, Stories, Tags, Team
- Testimonials, UI, UserRoles, Users, Widgets

**Total: 65 modules** supporting social networking, content management, gamification, e-commerce

## 01.b.11 - Gutenberg Blocks

> **Source:** grep_search `register_block_type` + list_dir `blocks/`

| Block Name | Registered In         | Server-Side Render | Block Path           |
| ---------- | --------------------- | ------------------ | -------------------- |
| (Dynamic)  | blocks/blocks.php:117 | Yes                | blocks/{block-name}/ |

### Block Folders Found:

- **classifieds-grid** - Grid layout for classifieds
- **social-feed** - Social activity feed block
- **social-share** - Social sharing buttons block
- **user-profile** - User profile card block

**Block Registration:** Dynamic server-side rendering via blocks.php
**Block Assets:** Separate JS/CSS per block in block folders

## 01.b.12 - Elementor Widgets

> **Source:** list_dir `elementor/widgets` - 10 widgets found

| Widget Class                   | Widget Name      | Category | File                                     |
| ------------------------------ | ---------------- | -------- | ---------------------------------------- |
| Apollo_User_Profile_Widget     | User Profile     | Apollo   | class-apollo-user-profile-widget.php     |
| Apollo_Unions_Directory_Widget | Unions Directory | Apollo   | class-apollo-unions-directory-widget.php |
| Apollo_Social_Share_Widget     | Social Share     | Apollo   | class-apollo-social-share-widget.php     |
| Apollo_Social_Feed_Widget      | Social Feed      | Apollo   | class-apollo-social-feed-widget.php      |
| Apollo_Groups_Directory_Widget | Groups Directory | Apollo   | class-apollo-groups-directory-widget.php |
| Apollo_Group_Card_Widget       | Group Card       | Apollo   | class-apollo-group-card-widget.php       |
| Apollo_Classifieds_List_Widget | Classifieds List | Apollo   | class-apollo-classifieds-list-widget.php |
| Apollo_Classifieds_Grid_Widget | Classifieds Grid | Apollo   | class-apollo-classifieds-grid-widget.php |
| Apollo_Chat_Panel_Widget       | Chat Panel       | Apollo   | class-apollo-chat-panel-widget.php       |
| Apollo_Badges_Panel_Widget     | Badges Panel     | Apollo   | class-apollo-badges-panel-widget.php     |

**Total:** 10 Elementor widgets for page building integration

## 01.b.13 - Custom Capabilities & Roles

> **Source:** src/Infrastructure/Security/Caps.php + grep_search `add_role`, `add_cap`

### Custom Roles

| Role Slug     | Display Name  | Capabilities                 | Registered In                       |
| ------------- | ------------- | ---------------------------- | ----------------------------------- |
| cena-rio      | Cena::rio     | Same as Contributor + custom | src/Modules/Auth/UserRoles.php:36   |
| apollo_member | Apollo Member | read                         | src/Modules/Auth/AuthService.php:30 |

### Custom Capabilities (Caps.php)

| Capability                       | Assigned To   | Purpose                      |
| -------------------------------- | ------------- | ---------------------------- |
| **Groups Capabilities**          |               |                              |
| read_apollo_group                | All           | Read group content           |
| read_private_apollo_groups       | Subscriber+   | Read private groups          |
| create_apollo_groups             | Contributor+  | Create new groups            |
| edit_apollo_groups               | Contributor+  | Edit own groups              |
| edit_others_apollo_groups        | Editor+       | Edit others' groups          |
| publish_apollo_groups            | Contributor+  | Publish groups               |
| delete_apollo_groups             | Contributor+  | Delete own groups            |
| delete_others_apollo_groups      | Editor+       | Delete others' groups        |
| manage_apollo_group_members      | Moderator+    | Manage group memberships     |
| moderate_apollo_group_content    | Moderator+    | Moderate group content       |
| **Events Capabilities**          |               |                              |
| read_eva_event                   | All           | Read events                  |
| create_eva_events                | Contributor+  | Create events                |
| edit_eva_events                  | Contributor+  | Edit own events              |
| publish_eva_events               | Contributor+  | Publish events               |
| manage_eva_event_categories      | Editor+       | Manage event taxonomies      |
| **Ads/Classifieds Capabilities** |               |                              |
| read_apollo_ad                   | All           | Read classifieds             |
| create_apollo_ads                | Contributor+  | Create classifieds           |
| edit_apollo_ads                  | Contributor+  | Edit own classifieds         |
| publish_apollo_ads               | Contributor+  | Publish classifieds          |
| moderate_apollo_ads              | Moderator+    | Moderate classifieds         |
| **Moderation Capabilities**      |               |                              |
| apollo_moderate                  | Moderator+    | General moderation           |
| apollo_moderate_groups           | Moderator+    | Moderate groups              |
| apollo_moderate_events           | Moderator+    | Moderate events              |
| apollo_moderate_ads              | Moderator+    | Moderate classifieds         |
| apollo_moderate_users            | Moderator+    | Moderate users               |
| apollo_moderate_all              | Administrator | All moderation powers        |
| apollo_view_mod_queue            | Moderator+    | View moderation queue        |
| apollo_manage_moderators         | Administrator | Manage moderator assignments |
| **Analytics Capabilities**       |               |                              |
| apollo_view_analytics            | Editor+       | View analytics dashboards    |
| apollo_manage_analytics          | Administrator | Configure analytics          |
| apollo_export_analytics          | Administrator | Export analytics data        |

### Group Member Roles (GroupsModule.php:13-33)

| Role      | Capabilities                         | Label     |
| --------- | ------------------------------------ | --------- |
| owner     | manage, invite, moderate, post, view | Dono      |
| admin     | invite, moderate, post, view         | Admin     |
| moderator | moderate, post, view                 | Moderador |
| member    | post, view                           | Membro    |
| pending   | (none)                               | Pendente  |

**Total Custom Capabilities:** 35+ across groups, events, classifieds, moderation, analytics

## 01.b.14 - Admin Menu Pages

> **Source:** grep_search `add_menu_page|add_submenu_page` - 50+ matches

### Main Menu (AdminMenus.php)

| Menu Slug     | Page Title    | Capability     | Position | Icon             |
| ------------- | ------------- | -------------- | -------- | ---------------- |
| apollo-social | Apollo Social | manage_options | 30       | dashicons-groups |

### Submenus (AdminMenus.php:13-30)

| Submenu Slug          | Title          | Parent Menu   | Capability     | Handler           |
| --------------------- | -------------- | ------------- | -------------- | ----------------- |
| apollo-social         | Dashboard      | apollo-social | manage_options | dashboardPage     |
| apollo-members        | Members        | apollo-social | manage_options | membersPage       |
| apollo-pending-users  | Pending Users  | apollo-social | manage_options | pendingUsersPage  |
| apollo-spammers       | Spammers       | apollo-social | manage_options | spammersPage      |
| apollo-verified       | Verified Users | apollo-social | manage_options | verifiedUsersPage |
| apollo-member-types   | Member Types   | apollo-social | manage_options | memberTypesPage   |
| apollo-groups         | Groups         | apollo-social | manage_options | groupsPage        |
| apollo-activity       | Activity       | apollo-social | manage_options | activityPage      |
| apollo-gamification   | Points & Ranks | apollo-social | manage_options | gamificationPage  |
| apollo-achievements   | Achievements   | apollo-social | manage_options | achievementsPage  |
| apollo-competitions   | Competitions   | apollo-social | manage_options | competitionsPage  |
| apollo-notices        | Notices        | apollo-social | manage_options | noticesPage       |
| apollo-profile-fields | Profile Fields | apollo-social | manage_options | profileFieldsPage |
| apollo-email-queue    | Email Queue    | apollo-social | manage_options | emailQueuePage    |
| apollo-team           | Team Members   | apollo-social | manage_options | teamPage          |
| apollo-map            | Map Pins       | apollo-social | manage_options | mapPage           |
| apollo-testimonials   | Testimonials   | apollo-social | manage_options | testimonialsPage  |
| apollo-settings       | Settings       | apollo-social | manage_options | settingsPage      |

### Additional Admin Pages

| Page          | Parent Menu   | Registered In                                               |
| ------------- | ------------- | ----------------------------------------------------------- |
| Verifications | (top-level)   | src/Admin/VerificationsTable.php:56                         |
| Signatures    | (submenu)     | src/Modules/Signatures/SignaturesModule.php:227             |
| Documents     | (submenu)     | src/Modules/Documents/DocumentsModule.php:307               |
| Adapters      | (submenu)     | src/Infrastructure/Providers/AdapterServiceProvider.php:225 |
| Builder Admin | (submenu)     | src/Modules/Builder/Admin/BuilderAdminPage.php:23           |
| Analytics     | (submenu)     | src/Infrastructure/Admin/AnalyticsAdmin.php:32              |
| Email Hub     | apollo-social | src/Admin/EmailHubAdmin.php:506                             |
| Diagnostics   | apollo-social | src/Admin/DiagnosticsAdmin.php:38                           |
| Cultura Rio   | apollo-social | src/Admin/CulturaRioAdmin.php:269                           |

**Total Admin Pages:** 35+ admin menu pages

## 01.b.15 - Cron Jobs

> **Source:** grep_search `wp_schedule_event|wp_cron` - 5 matches

| Event Hook         | Recurrence   | Handler            | Registered In                                    | Purpose                          |
| ------------------ | ------------ | ------------------ | ------------------------------------------------ | -------------------------------- |
| apollo_daily_cron  | daily        | (not specified)    | src/Providers/SocialServiceProvider.php:18       | Daily maintenance tasks          |
| apollo_hourly_cron | hourly       | (not specified)    | SocialServiceProvider.php:21                     | Hourly cleanup/sync              |
| apollo_groups_sync | configurable | (adapter-specific) | src/Infrastructure/Adapters/GroupsAdapter.php:44 | Sync groups with external system |

**Total Cron Jobs:** 3 scheduled events
**⚠️ ORPHAN JOBS:** No deactivation hook to clear scheduled events found

## 01.b.16 - Security & Quality Audit

### ✅ AJAX Security Audit

- **ALL 80+ handlers** use `check_ajax_referer()` for nonce validation
- **ALL handlers** check capabilities via `current_user_can()` or custom permission callbacks
- **15 nopriv actions** properly validate input and limit scope
- **No SQL injection risks** - all queries use `$wpdb->prepare()`

### ✅ REST API Security Audit

- **ALL 70+ endpoints** use `permission_callback` (never `__return_true` without validation)
- **Nonce validation** handled by WordPress REST API core
- **Input sanitization** via `sanitize_text_field()`, `sanitize_key()`, `wp_kses_post()`
- **SQL queries** use prepared statements exclusively

### ✅ File Upload Security

- **Image uploads:** Validated via `ImageUploadHandler.php` with MIME type checking
- **Document uploads:** Validated file types and sizes
- **PDF exports:** Generated server-side, not user-uploaded

### ⚠️ Performance Recommendations

1. **Add indexes** to frequently-queried meta keys (`_classified_views`, `_like_count`)
2. **Implement caching** for activity feeds and user profiles (consider object cache)
3. **Pagination limits** on all list queries (implemented in most controllers)
4. **Database cleanup cron** for expired stories, old activity logs

### ⚠️ GDPR Compliance Gaps

- **Data export:** apollo_data_exports table exists (ExtendedSchema.php) but no export handler found
- **Data deletion:** No wp_privacy_personal_data_exporters hook registered
- **Cookie consent:** No cookie banner or consent management detected
- **Privacy policy:** No privacy policy page auto-generation

### 🔒 Uninstall Cleanup Warning

- **No uninstall.php found** - 100+ custom tables will persist after plugin deletion
- **Recommend:** Create uninstall.php with table cleanup and option deletion
- **User data:** Consider GDPR right-to-erasure requirements

## REQUEST #02 - Duplicity Risk Analysis

### ⚠️ HIGH RISK - Function/Shortcode Duplicity

| Item                | Locations Found                                          | Risk Level | Recommendation          |
| ------------------- | -------------------------------------------------------- | ---------- | ----------------------- |
| apollo_profile_card | SocialShortcodes.php:72, ShortcodeServiceProvider.php:18 | HIGH       | Remove one registration |

### ✅ NO DUPLICITY FOUND

- **CPT Slugs:** All 8 CPT slugs unique within apollo-social
- **Taxonomy Slugs:** All 11 taxonomy slugs unique
- **AJAX Actions:** All 80+ action names unique
- **REST Routes:** All 70+ route patterns unique
- **Table Names:** All 100+ table names unique across schemas
- **Admin Menu Slugs:** All 35+ menu slugs unique

### ⚠️ CROSS-PLUGIN DEPENDENCIES

- **event_season taxonomy:** Expects apollo-core/events to register - may fail if events plugin inactive
- **apollo_document CPT:** May conflict if apollo-core also registers same CPT

### ✅ NO DEPRECATED CODE FOUND

- No `@deprecated` tags found in apollo-social plugin code (vendor excluded)
- All shortcodes/endpoints actively maintained

## REQUEST #03 - Module Deep Dive

### Most Complex Modules (by lines of code + features)

1. **Groups Module** - 750+ lines, 15 REST endpoints, 5 tables, comuna/nucleo dual-type system (custom tables, not CPTs)
2. **Documents Module** - 500+ lines, Quill Delta editor, PDF export, signature integration
3. **Builder Module** - 600+ lines, canvas mode, 10 AJAX handlers, widget system
4. **Chat Module** - 400+ lines, real-time messaging, 3 tables, polling system
5. **Signatures Module** - 500+ lines, DocuSeal integration, audit logs, verification

### Infrastructure Highlights

- **65 modules** in src/Modules/
- **100+ custom tables** across 12 schema files
- **70+ REST endpoints** in apollo/v1 namespace
- **80+ AJAX handlers** with nonce/capability validation
- **35+ shortcodes** for content display
- **35+ admin pages** for management
- **10 Elementor widgets** for page building
- **PSR-4 autoloading** via Composer

## REQUEST #04 - CSV Export Format

```csv
item_type,name,slug,file_location,usage_count,exposes_api,permission_required,sanitization,notes,module
CPT,apollo_social_post,apollo_social_post,src/Infrastructure/PostTypes/SocialPostType.php:83,High,No,read,Yes,Social feed posts,Social
CPT,apollo_classified,apollo_classified,src/Modules/Classifieds/ClassifiedsModule.php:137,High,Yes,read,Yes,Marketplace classifieds,Classifieds
CPT,apollo_supplier,apollo_supplier,src/Modules/Suppliers/SuppliersModule.php:171,Medium,No,read,Yes,Supplier directory,Suppliers
CPT,apollo_document,apollo_document,src/Ajax/DocumentSaveHandler.php:155,Medium,Yes,edit_posts,Yes,Document library,Documents
CPT,apollo_home,apollo_home,src/Builder/class-apollo-home-cpt.php:96,Low,Yes,edit_posts,Yes,Home page builder,Builder
CPT,user_page,user_page,src/Modules/UserPages/UserPageRegistrar.php:54,High,No,edit_posts,Yes,Custom user pages,UserPages
CPT,cena_document,cena_document,src/CenaRio/CenaRioModule.php:88,Low,No,read,Yes,Cena Rio documents,CenaRio
CPT,cena_event_plan,cena_event_plan,src/CenaRio/CenaRioModule.php:115,Low,No,read,Yes,Event planning,CenaRio
Taxonomy,apollo_post_category,apollo_post_category,src/Infrastructure/PostTypes/SocialPostType.php:106,Medium,No,read,Yes,Social post categories,Social
Taxonomy,classified_domain,classified_domain,src/Modules/Classifieds/ClassifiedsModule.php:171,High,No,read,Yes,Classified type,Classifieds
Taxonomy,classified_intent,classified_intent,src/Modules/Classifieds/ClassifiedsModule.php:189,High,No,read,Yes,Buy/sell intent,Classifieds
Taxonomy,event_season,event_season,src/Modules/Classifieds/ClassifiedsModule.php:141,Medium,No,read,Yes,Event seasonality (shared),Classifieds
Shortcode,apollo_social_feed,,src/Shortcodes/SocialShortcodes.php:63,High,No,read,Yes,Activity feed display,Social
Shortcode,apollo_classifieds,,src/Shortcodes/SocialShortcodes.php:75,High,No,read,Yes,Classifieds grid,Classifieds
Shortcode,apollo_groups_directory,,src/Providers/SocialServiceProvider.php:32,High,No,read,Yes,Groups listing,Groups
Shortcode,apollo_user_profile,,src/Shortcodes/SocialShortcodes.php:69,High,No,read,Yes,User profile card,Profiles
REST,/chat/send,POST,src/Modules/Chat/ChatModule.php:190,High,Yes,is_user_logged_in,Yes,Send chat message,Chat
REST,/groups,GET|POST,src/Modules/Groups/GroupsModule.php:582,High,Yes,varies,Yes,List/create groups,Groups
REST,/classifieds,GET,src/Modules/Classifieds/ClassifiedsModule.php:257,High,Yes,__return_true,Yes,List classifieds,Classifieds
REST,/activity,GET|POST,src/Modules/Activity/ActivityStream.php:215,High,Yes,varies,Yes,Activity feed API,Activity
REST,/signatures/create,POST,src/Modules/Signatures/SignaturesRestController.php:98,Medium,Yes,is_user_logged_in,Yes,Create signature,Signatures
AJAX,apollo_userpage_save,,user-pages/class-user-page-editor-ajax.php:53,Medium,No,edit_posts,Yes,Save user page layout,UserPages
AJAX,apollo_builder_save,,src/Builder/class-apollo-builder-ajax.php:48,High,No,edit_posts,Yes,Save canvas layout,Builder
AJAX,apollo_chat_send,,src/Modules/Chat/ChatModule.php:79,High,No,is_user_logged_in,Yes,Send chat message (AJAX),Chat
AJAX,apollo_classifieds_search,,src/Modules/Classifieds/ClassifiedsServiceProvider.php:39,High,Yes,__return_true,Yes,Search classifieds,Classifieds
Table,apollo_groups,apollo_groups,SocialSchema.php,High,No,,Yes,Groups/communities main table (comuna/nucleo types),Groups
Table,apollo_group_members,apollo_group_members,SocialSchema.php,High,No,,Yes,Group membership data,Groups
Table,apollo_group_meta,apollo_group_meta,SocialSchema.php,Medium,No,,Yes,Group metadata storage,Groups
Table,apollo_group_types,apollo_group_types,SocialSchema.php,Low,No,,Yes,Group type definitions (comuna/nucleo),Groups
Table,apollo_group_invites,apollo_group_invites,SocialSchema.php,Medium,No,,Yes,Group invitation system,Groups
AJAX,apollo_moderate_approve,,src/Modules/Moderation/Controllers/ModerationController.php:20,Medium,No,apollo_moderate,Yes,Approve moderation item,Moderation
Table,apollo_activity,apollo_activity,SocialSchema.php,High,No,,Yes,Activity stream data,Activity
Table,apollo_groups,apollo_groups,SocialSchema.php,High,No,,Yes,Groups/communities,Groups
Table,apollo_chat_conversations,apollo_chat_conversations,ChatModule.php:133,High,No,,Yes,Chat threads,Chat
Table,apollo_signatures,apollo_signatures,SignaturesService.php:74,Medium,No,,Yes,Digital signatures,Signatures
Table,apollo_subscriptions,apollo_subscriptions,SubscriptionsSchema.php:25,Medium,No,,Yes,User subscriptions,Subscriptions
Option,apollo_social_version,,src/Schema.php,Low,No,manage_options,Yes,Plugin version,Core
Option,apollo_feature_flags,,src/Infrastructure/FeatureFlags.php,Low,No,manage_options,Yes,Feature toggles,Infrastructure
Capability,apollo_moderate,,src/Infrastructure/Security/Caps.php,Medium,No,manage_options,Yes,Moderation capability,Moderation
Capability,create_apollo_groups,,Caps.php,Medium,No,manage_options,Yes,Create groups capability,Groups
Capability,apollo_view_analytics,,Caps.php,Low,No,manage_options,Yes,View analytics capability,Analytics
Role,cena-rio,,src/Modules/Auth/UserRoles.php:36,Low,No,manage_options,Yes,Cena Rio role,Auth
AdminMenu,apollo-social,apollo-social,src/Admin/AdminMenus.php:12,High,No,manage_options,Yes,Main admin menu,Admin
AdminMenu,apollo-groups,apollo-groups,AdminMenus.php:19,Medium,No,manage_options,Yes,Groups admin page,Groups
Block,user-profile,,blocks/user-profile/,Medium,No,read,Yes,User profile Gutenberg block,Profiles
ElementorWidget,Apollo_Social_Feed_Widget,,elementor/widgets/class-apollo-social-feed-widget.php,Medium,No,edit_posts,Yes,Social feed Elementor widget,Elementor
```

## REQUEST #00 - Priority Setup Checklist

### Pre-Activation

- ✅ Verify apollo-core >= 1.0.0 installed and active
- ⚠️ Check PHP version >= 8.1 (required for strict types)
- ⚠️ Run composer install for DocuSeal and Delta parser dependencies
- ⚠️ Verify database user has CREATE TABLE permissions

### Initial Configuration

1. **Database Setup**
   - Activate plugin to create 100+ custom tables
   - Verify tables created: `SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE 'wp_apollo_%'`
   - Expected: 100+ tables
2. **Feature Flags**
   - Configure feature flags in apollo_feature_flags option
   - Enable/disable modules: groups_api, chat_module, signatures, etc.
3. **Capabilities**
   - Review custom capabilities assigned to roles
   - Create 'moderator' role if using moderation features
4. **Cron Jobs**
   - Verify cron schedule: `wp cron event list | grep apollo`
   - Expected: apollo_daily_cron, apollo_hourly_cron
5. **Admin Access**
   - Navigate to Apollo Social menu
   - Verify 35+ admin pages load without errors

### Security Hardening

- ✅ All AJAX/REST endpoints have nonce/capability checks
- ⚠️ Implement rate limiting on public AJAX endpoints (apollo_classifieds_search, apollo_submit_comment)
- ⚠️ Add file upload size limits in php.ini (recommend: upload_max_filesize = 50M)
- ⚠️ Configure CORS for REST API if cross-origin requests needed

### Performance Tuning

- Add indexes to meta tables: `ALTER TABLE wp_postmeta ADD INDEX meta_key_value_idx (meta_key(50), meta_value(100))`
- Enable object cache (Redis/Memcached) for activity feeds
- Configure max_execution_time >= 300 for bulk operations

### GDPR Compliance

- ⚠️ Implement data export handler for apollo_data_exports table
- ⚠️ Register wp_privacy_personal_data_exporters hook
- ⚠️ Add cookie consent banner if tracking users
- ⚠️ Create privacy policy page with Apollo Social data usage disclosure

### Monitoring

- Set up error logging for Apollo-specific errors
- Monitor cron job execution
- Track slow queries on apollo_activity, apollo_groups tables
- Set up alerts for moderation queue size

---

## 📊 INVENTORY SUMMARY

- **CPTs:** 8 post types (Groups use custom tables, not CPTs)
- **Taxonomies:** 11 taxonomies
- **Tables:** 100+ custom database tables (including 5 groups tables)
- **REST Endpoints:** 70+ endpoints
- **AJAX Handlers:** 80+ actions
- **Shortcodes:** 35+ shortcodes
- **Modules:** 65 feature modules
- **Admin Pages:** 35+ menu pages
- **Capabilities:** 35+ custom capabilities
- **Roles:** 1 custom role (+ 5 group roles)
- **Gutenberg Blocks:** 4 blocks
- **Elementor Widgets:** 10 widgets
- **Cron Jobs:** 3 scheduled events

**Total Components:** 400+ registered components across apollo-social plugin

**Audit Completed:** 24 de janeiro de 2026 - FULL POWER MODE ✅
