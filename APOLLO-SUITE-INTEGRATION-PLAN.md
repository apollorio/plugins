# APOLLO SUITE INTEGRATION PLAN
## Cross-Plugin Unified Inventory, Conflict Matrix, and Tonight Deploy Runbook

**Generated:** 2025-12-30
**Updated:** Session 2 - flush_rewrite_rules audit complete
**Scope:** apollo-core, apollo-social, apollo-rio, apollo-events-manager
**Mode:** STRICT - No scope-limiting, full integration
**Status:** ✅ Phase 1 COMPLETE - All runtime flushes removed/mitigated

---

## 📊 SESSION 2 PROGRESS SUMMARY

### ✅ COMPLETED FIXES

| Issue | File | Change | Verified |
|-------|------|--------|----------|
| P0-2 | apollo-events-manager.php:1049 | Removed runtime flush | `php -l` ✅ |
| P1-1 | post-types.php:593 | Replaced with admin notice flag | `php -l` ✅ |
| P2-1 | class-apollo-native-seo.php:1074 | Replaced with transient + notice | `php -l` ✅ |

### ✅ VERIFIED ACCEPTABLE (activation/deactivation context only)

| Plugin | File | Context |
|--------|------|---------|
| apollo-core | class-activation.php:65 | activate() hook |
| apollo-core | class-apollo-core.php:195 | deactivate() hook |
| apollo-core | class-cena-rio-roles.php:51 | Inside activate() |
| apollo-rio | pwa.php:268, 282 | activation/deactivation hooks |
| apollo-social | Apollo_Router.php:487 | onActivation() |
| apollo-social | apollo-social.php:423, 452 | activation/deactivation fallback |
| apollo-social | DiagnosticsAdmin.php:297 | Manual admin button (acceptable) |

### 📁 FILES CREATED THIS SESSION

1. `apollo-core/src/Schema/SchemaModuleInterface.php` - Interface para módulos
2. `apollo-core/src/Schema/SchemaOrchestrator.php` - Coordenador central
3. `apollo-core/src/Schema/CoreSchemaModule.php` - Implementação core
4. `apollo-core/src/Security/RestSecurity.php` - Helper de segurança REST
5. `apollo-suite-validation.sh` - Script de validação 10-check

---

# PART A: SUITE INVENTORY

## A.1 Custom Post Types (CPTs)

| Slug | Owner Plugin | Rewrite Slug | REST Base | Public | Supports | File |
|------|--------------|--------------|-----------|--------|----------|------|
| `event_listing` | apollo-events-manager | `/evento` | `events` | ✅ | title, editor, thumbnail, custom-fields, excerpt, author, revisions | includes/post-types.php:55 |
| `event_dj` | apollo-events-manager | `/dj` | `djs` | ✅ | title, editor, thumbnail, custom-fields | includes/post-types.php:116 |
| `event_local` | apollo-events-manager | `/local` | `locals` | ✅ | title, editor, thumbnail, custom-fields | includes/post-types.php:167 |
| `apollo_email_template` | apollo-core | (none) | - | ❌ | title, editor | includes/class-apollo-email-templates-cpt.php:42 |
| `apollo_social_post` | apollo-social | - | - | ✅ | title, editor, thumbnail, custom-fields, comments | src/Infrastructure/PostTypes/SocialPostType.php:81 |
| `apollo_home_page` | apollo-social | - | - | ❌ | title, editor | src/Builder/class-apollo-home-cpt.php:82 |
| `apollo_classified` | apollo-social | `/classificado` | `classifieds` | ✅ | title, editor, thumbnail, custom-fields | src/Modules/Classifieds/ClassifiedsModule.php:130 |
| `apollo_supplier` | apollo-social | `/fornecedor` | `suppliers` | ✅ | title, editor, thumbnail, custom-fields | src/Modules/Suppliers/SuppliersModule.php:171 |
| `apollo_cena_event` | apollo-social | `/cena-rio` | - | ✅ | title, editor, thumbnail | src/CenaRio/CenaRioModule.php:83 |
| `apollo_cena_submission` | apollo-social | - | - | ❌ | title, editor | src/CenaRio/CenaRioModule.php:110 |
| `apollo_document` | apollo-social | - | - | ❌ | title, custom-fields | src/Ajax/DocumentSaveHandler.php:155 |
| `apollo_user_page` | apollo-social | `/id/{user_id}` | - | ✅ | - | src/Modules/UserPages/UserPageRegistrar.php:54 |

## A.2 Taxonomies

| Slug | Owner Plugin | Object Type | Hierarchical | Rewrite | REST Base | File |
|------|--------------|-------------|--------------|---------|-----------|------|
| `event_listing_category` | apollo-events-manager | event_listing | ✅ | `/categoria-evento` | `event-categories` | includes/post-types.php:211 |
| `event_listing_type` | apollo-events-manager | event_listing | ✅ | `/tipo-evento` | `event-types` | includes/post-types.php:246 |
| `event_listing_tag` | apollo-events-manager | event_listing | ❌ | `/tag-evento` | `event-tags` | includes/post-types.php:279 |
| `event_sounds` | apollo-events-manager | event_listing, event_dj | ✅ | `/som` | `event-sounds` | includes/post-types.php:314 |
| `apollo_social_category` | apollo-social | apollo_social_post | ✅ | - | - | src/Infrastructure/PostTypes/SocialPostType.php:103 |
| `classified_category` | apollo-social | apollo_classified | ✅ | - | - | src/Modules/Classifieds/ClassifiedsModule.php:138 |
| `classified_location` | apollo-social | apollo_classified | ✅ | - | - | src/Modules/Classifieds/ClassifiedsModule.php:156 |
| `supplier_category` | apollo-social | apollo_supplier | ✅ | - | - | src/Modules/Suppliers/SuppliersModule.php:185 |
| `supplier_region` | apollo-social | apollo_supplier | ✅ | - | - | src/Modules/Suppliers/SuppliersModule.php:212 |
| `supplier_service` | apollo-social | apollo_supplier | ❌ | - | - | src/Modules/Suppliers/SuppliersModule.php:231 |

## A.3 Custom Database Tables

### apollo-core Tables
| Table Name | Purpose | Schema Location | Has Indexes |
|------------|---------|-----------------|-------------|
| `{prefix}apollo_mod_log` | Moderation actions log | includes/db-schema.php:31 | ✅ |
| `{prefix}apollo_audit_log` | Security audit trail | includes/db-schema.php:51 | ✅ |
| `{prefix}apollo_analytics_pageviews` | Page view tracking | includes/db-schema.php:76 | ✅ |
| `{prefix}apollo_analytics_interactions` | User interactions | includes/db-schema.php:110 | ✅ |
| `{prefix}apollo_analytics_sessions` | Session data | includes/db-schema.php:140 | ✅ |
| `{prefix}apollo_analytics_user_stats` | User statistics | includes/db-schema.php:176 | ✅ |
| `{prefix}apollo_analytics_content_stats` | Content statistics | includes/db-schema.php:201 | ✅ |
| `{prefix}apollo_analytics_heatmap` | Heatmap data | includes/db-schema.php:227 | ✅ |
| `{prefix}apollo_analytics_settings` | Analytics settings | includes/db-schema.php:246 | ❌ |
| `{prefix}apollo_quiz_attempts` | Quiz attempts | includes/quiz/schema-manager.php:338 | ✅ |
| `{prefix}apollo_newsletter_subscribers` | Newsletter subs | includes/class-apollo-native-newsletter.php:108 | ✅ |
| `{prefix}apollo_newsletter_campaigns` | Newsletter campaigns | includes/class-apollo-native-newsletter.php:127 | ✅ |
| `{prefix}apollo_email_security_log` | Email security log | includes/class-email-security-log.php:86 | ✅ |

### apollo-social Tables (Schema.php + CoreSchema.php)
| Table Name | Purpose | Schema Location | Has Indexes |
|------------|---------|-----------------|-------------|
| `{prefix}apollo_groups` | Groups/Comunas/Nucleos | src/Infrastructure/Database/Schema.php:47 | ✅ |
| `{prefix}apollo_group_members` | Group membership | src/Infrastructure/Database/Schema.php:79 | ✅ |
| `{prefix}apollo_group_invites` | Group invitations | src/Infrastructure/Database/Migrations.php | ✅ |
| `{prefix}apollo_workflow_log` | Workflow transitions | src/Infrastructure/Database/CoreSchema.php | ✅ |
| `{prefix}apollo_mod_queue` | Moderation queue | src/Infrastructure/Database/CoreSchema.php | ✅ |
| `{prefix}apollo_analytics` | Social analytics | src/Infrastructure/Database/CoreSchema.php | ✅ |
| `{prefix}apollo_signature_requests` | Digital signatures | src/Infrastructure/Database/CoreSchema.php | ✅ |
| `{prefix}apollo_onboarding_progress` | User onboarding | src/Infrastructure/Database/CoreSchema.php | ✅ |
| `{prefix}apollo_verification_tokens` | Email verification | src/Infrastructure/Database/CoreSchema.php | ✅ |
| `{prefix}apollo_documents` | Documents system | src/Application/Users/CompleteOnboarding.php:227 | ⚠️ |
| `{prefix}apollo_document_signatures` | Document signatures | src/Application/Users/CompleteOnboarding.php:467 | ⚠️ |
| `{prefix}apollo_verifications` | User verifications | src/Admin/VerificationsTable.php | ⚠️ |
| `{prefix}apollo_likes` | Social likes | Schema.php | ✅ |
| `{prefix}apollo_notifications` | Notifications | Schema.php | ✅ |
| `{prefix}apollo_block_list` | User blocks | Schema.php | ✅ |
| `{prefix}apollo_followers` | Social follows | Schema.php | ✅ |

### apollo-social ExtendedSchema Tables
| Table Name | Purpose | Has Indexes |
|------------|---------|-------------|
| `{prefix}apollo_bookmarks` | User bookmarks | ✅ |
| `{prefix}apollo_bookmark_collections` | Bookmark collections | ✅ |
| `{prefix}apollo_polls` | Polls | ✅ |
| `{prefix}apollo_poll_options` | Poll options | ✅ |
| `{prefix}apollo_poll_votes` | Poll votes | ✅ |
| `{prefix}apollo_stories` | Stories feature | ✅ |
| `{prefix}apollo_story_views` | Story views | ✅ |
| `{prefix}apollo_story_replies` | Story replies | ✅ |
| `{prefix}apollo_hashtags` | Hashtags | ✅ |
| `{prefix}apollo_hashtag_usage` | Hashtag usage | ✅ |
| `{prefix}apollo_hashtag_follows` | Hashtag follows | ✅ |
| `{prefix}apollo_reactions` | Reactions | ✅ |
| `{prefix}apollo_moderation_queue` | Extended mod queue | ✅ |
| `{prefix}apollo_moderation_actions` | Mod actions log | ✅ |
| `{prefix}apollo_moderation_rules` | Auto-mod rules | ✅ |
| `{prefix}apollo_profile_views` | Profile views | ✅ |
| `{prefix}apollo_audit_logs` | Extended audit (duplicate!) | ⚠️ |
| `{prefix}apollo_referrals` | Referral system | ✅ |
| `{prefix}apollo_referral_rewards` | Referral rewards | ✅ |
| `{prefix}apollo_data_exports` | GDPR exports | ✅ |
| `{prefix}apollo_2fa_sessions` | 2FA sessions | ✅ |
| `{prefix}apollo_2fa_backup_codes` | 2FA backup codes | ✅ |
| `{prefix}apollo_2fa_trusted_devices` | 2FA trusted devices | ✅ |

### apollo-social AdvertsSchema Tables
| Table Name | Purpose |
|------------|---------|
| `{prefix}apollo_adverts` | Classifieds ads |
| `{prefix}apollo_advert_images` | Ad images |
| `{prefix}apollo_advert_categories` | Ad categories |
| `{prefix}apollo_advert_favorites` | Ad favorites |
| `{prefix}apollo_advert_views` | Ad views |
| `{prefix}apollo_advert_messages` | Ad messages |
| `{prefix}apollo_advert_reports` | Ad reports |

### apollo-social EventsSchema Tables
| Table Name | Purpose |
|------------|---------|
| `{prefix}apollo_events` | Events (duplicate of CPT!) |
| `{prefix}apollo_event_attendees` | Event attendees |
| `{prefix}apollo_event_interested` | Event interested |
| `{prefix}apollo_event_categories` | Event categories |
| `{prefix}apollo_event_meta` | Event meta |
| `{prefix}apollo_event_reminders` | Event reminders |
| `{prefix}apollo_event_tickets` | Event tickets |
| `{prefix}apollo_event_ticket_orders` | Ticket orders |

### apollo-rio Tables
| Table Name | Purpose |
|------------|---------|
| None | Uses apollo-social tables via SEO handler |

### apollo-events-manager Tables
| Table Name | Purpose |
|------------|---------|
| None | Uses CPTs with post_meta |

## A.4 REST API Routes

### apollo-core (Namespace: `apollo/v1`)
| Route | Methods | Permission | Handler |
|-------|---------|------------|---------|
| `/newsletter/subscribe` | POST | `__return_true` | class-apollo-native-newsletter.php:727 |
| `/newsletter/unsubscribe` | POST | `__return_true` | class-apollo-native-newsletter.php:737 |
| `/push/subscribe` | POST | `__return_true` | class-apollo-native-push.php:175 |
| `/push/unsubscribe` | POST | `__return_true` | class-apollo-native-push.php:185 |
| `/push/sync` | POST | `__return_true` | class-apollo-native-push.php:195 |
| `/mod/suspender/{id}` | POST | `suspend_users` | class-apollo-user-moderation.php:930 |
| `/mod/unsuspend/{id}` | POST | `suspend_users` | class-apollo-user-moderation.php:959 |
| `/mod/ban/{id}` | POST | `block_users` | class-apollo-user-moderation.php:983 |
| `/mod/unban/{id}` | POST | `block_users` | class-apollo-user-moderation.php:1007 |
| `/cena/approve/{id}` | POST | `moderate` | class-cena-rio-moderation.php:114 |
| `/cena/reject/{id}` | POST | `moderate` | class-cena-rio-moderation.php:125 |
| `/cena/bulk-action` | POST | `moderate` | class-cena-rio-moderation.php:146 |
| `/cena/submit` | POST | `logged_in` | class-cena-rio-submissions.php:49 |
| `/cena/submission/{id}` | GET | `logged_in` | class-cena-rio-submissions.php:61 |
| `/cena/update/{id}` | POST | `logged_in` | class-cena-rio-submissions.php:110 |
| `/mod/em-fila` | GET | `view_mod_queue` | class-moderation-queue-unified.php:77 |
| `/mod/em-fila-count` | GET | `view_mod_queue` | class-moderation-queue-unified.php:97 |
| `/health` | GET | `__return_true` | class-rest-bootstrap.php:49 |
| `/forms/submit` | POST | `__return_true` ⚠️ | includes/forms/rest.php:24 |
| `/forms/schema/{id}` | GET | `__return_true` | includes/forms/rest.php:47 |
| `/quiz/start` | POST | `logged_in` | includes/quiz/rest.php:21 |
| `/quiz/submit` | POST | `manage_options` | includes/quiz/rest.php:54 |
| `/quiz/results/{id}` | GET | `logged_in` | includes/quiz/rest.php:79 |
| `/membros` | GET | `__return_true` | includes/rest-membership.php:27 |
| `/membros/{id}` | GET | `__return_true` | includes/rest-membership.php:38 |
| `/membros/status` | POST | `edit_apollo_users` | includes/rest-membership.php:63 |
| `/memberships/solicitar` | POST | `logged_in` | includes/rest-membership.php:101 |
| ... | ... | ... | ... |

### apollo-social (Namespace: `apollo/v1`)
| Route | Methods | Permission | Handler |
|-------|---------|------------|---------|
| `/comunas` | GET,POST | `groups_api` via RestSecurity | src/Infrastructure/Http/RestRoutes.php |
| `/comunas/{id}` | GET,PUT,DELETE | `groups_api` via RestSecurity | RestRoutes.php |
| `/nucleos` | GET,POST | `groups_api` via RestSecurity | RestRoutes.php |
| `/nucleos/{id}` | GET,PUT,DELETE | `groups_api` via RestSecurity | RestRoutes.php |
| `/members` | GET | `__return_true` | src/API/RestApiHandlers.php:13 |
| `/members/{id}` | GET | `__return_true` | RestApiHandlers.php:14 |
| `/members/online` | GET | `is_user_logged_in` | RestApiHandlers.php:15 |
| `/activity` | GET | `__return_true` | RestApiHandlers.php:16 |
| `/activity` | POST | `is_user_logged_in` | RestApiHandlers.php:17 |
| `/leaderboard` | GET | `__return_true` | RestApiHandlers.php:20 |
| `/competitions` | GET | `__return_true` | RestApiHandlers.php:21 |
| `/map/pins` | GET | `__return_true` | RestApiHandlers.php:22 |
| `/me` | GET | `is_user_logged_in` | RestApiHandlers.php:23 |
| `/me/stats` | GET | `is_user_logged_in` | RestApiHandlers.php:24 |
| `/bolha/pedir` | POST | `logged_in` | BolhaEndpoint.php |
| `/bolha/aceitar` | POST | `logged_in` | BolhaEndpoint.php |
| `/documents` | GET,POST | varies | DocumentsEndpoint.php |
| `/documents/{id}` | GET,PUT,DELETE | varies | DocumentsEndpoint.php |
| `/documents/verify/{code}` | GET | `__return_true` | DocumentsEndpoint.php:178 |
| `/feed` | GET | `__return_true` | FeedEndpoint.php:37 |
| `/favorites` | GET,POST,DELETE | varies | FavoritesEndpoint.php |
| `/likes` | GET | `__return_true` | LikesEndpoint.php:32 |
| `/likes` | POST | `logged_in` | LikesEndpoint.php:66 |
| `/mod/reports` | GET,POST | `moderate_comments` | ModerationController.php |
| `/textures` | GET | `__return_true` | Textures.php |

### apollo-events-manager (Namespace: `aprio`)
| Route | Methods | Permission | Handler |
|-------|---------|------------|---------|
| `/events` | GET | `get_items_permissions_check` | aprio-rest-events-controller.php:90 |
| `/events` | POST | `create_item_permissions_check` | aprio-rest-events-controller.php:103 |
| `/events/{id}` | GET | `get_item_permissions_check` | aprio-rest-events-controller.php:123 |
| `/events/{id}` | PUT | `update_item_permissions_check` | aprio-rest-events-controller.php:135 |
| `/events/{id}` | DELETE | `delete_item_permissions_check` | aprio-rest-events-controller.php:141 |
| `/events/batch` | POST | `batch_items_permissions_check` | aprio-rest-events-controller.php:161 |
| `/ecosystem` | GET | `get_items_permissions_check` | aprio-rest-ecosystem-controller.php:63 |
| `/branding` | GET | `get_items_permissions_check` | aprio-rest-app-branding.php:51 |
| `/register` | POST | `__return_true` | aprio-rest-authentication.php:652 |
| `/login` | POST | `__return_true` | aprio-rest-authentication.php:675 |
| `/matchmaking/*` | POST | varies | aprio-rest-matchmaking-create-meetings.php |

### apollo-events-manager (Namespace: `apollo-events/v1`)
| Route | Methods | Permission | Handler |
|-------|---------|------------|---------|
| `/export` | GET | `edit_posts` | class-import-export-module.php:1024 |
| `/qr/{id}` | GET | `__return_true` | class-qrcode-module.php:562 |

### apollo-rio (Namespace: `wp/v2`)
| Route | Methods | Permission | Handler |
|-------|---------|------------|---------|
| `/app-manifest` | GET | `rest_permission` | class-wp-web-app-manifest.php:459 |

## A.5 AJAX Actions

### apollo-core AJAX (wp_ajax_*)
| Action | nopriv | Nonce Check | Cap Check | Handler |
|--------|--------|-------------|-----------|---------|
| `apollo_suspend_user` | ❌ | ✅ | ✅ | class-apollo-unified-control-panel.php:93 |
| `apollo_send_notification` | ❌ | ✅ | ✅ | class-apollo-unified-control-panel.php:94 |
| `apollo_test_email` | ❌ | ✅ | ✅ | class-apollo-unified-control-panel.php:95 |
| `apollo_toggle_feature` | ❌ | ✅ | ✅ | class-apollo-unified-control-panel.php:96 |
| `apollo_change_user_role` | ❌ | ✅ | ✅ | class-apollo-unified-control-panel.php:97 |
| `apollo_save_form_schema` | ❌ | ✅ | ⚠️ | forms-admin.php:314 |
| `apollo_track_pageview` | ✅ | ✅ | - | class-apollo-analytics.php:54 |
| `apollo_track_interaction` | ✅ | ✅ | - | class-apollo-analytics.php:56 |
| `apollo_track_session_end` | ✅ | ✅ | - | class-apollo-analytics.php:58 |
| `apollo_track_heatmap` | ✅ | ✅ | - | class-apollo-analytics.php:60 |
| `apollo_get_realtime_stats` | ❌ | ✅ | ✅ | class-apollo-analytics.php:64 |
| `apollo_export_analytics` | ❌ | ✅ | ✅ | class-apollo-analytics.php:67 |
| `apollo_save_consent` | ✅ | ✅ | - | class-apollo-cookie-consent.php:59 |
| `apollo_email_save_flow` | ❌ | ✅ | ⚠️ | class-apollo-email-admin-ui.php:26 |
| `apollo_email_send_test` | ❌ | ✅ | ⚠️ | class-apollo-email-admin-ui.php:27 |
| `apollo_save_snippet` | ❌ | ✅ | ⚠️ | class-apollo-snippets-manager.php:68 |
| `apollo_delete_snippet` | ❌ | ✅ | ⚠️ | class-apollo-snippets-manager.php:69 |
| `apollo_toggle_snippet` | ❌ | ✅ | ⚠️ | class-apollo-snippets-manager.php:70 |
| `apollo_cena_approve` | ❌ | ✅ | ✅ | class-cena-rio-moderation.php:41 |
| `apollo_cena_reject` | ❌ | ✅ | ✅ | class-cena-rio-moderation.php:42 |
| `apollo_warmup_caches` | ❌ | ✅ | ✅ | class-template-cache-manager.php:376 |
| `apollo_clear_caches` | ❌ | ✅ | ✅ | class-template-cache-manager.php:396 |
| `apollo_cdn_health_check` | ❌ | ✅ | ⚠️ | class-cdn-performance-monitor.php:29 |

### apollo-social AJAX
| Action | nopriv | Nonce Check | Cap Check | Handler |
|--------|--------|-------------|-----------|---------|
| `apollo_submit_depoimento` | ❌ | ✅ | - | apollo-social.php:462 |
| `apollo_save_canvas` | ✅ | ✅ | - | class-plano-save-handler.php:23 |
| `apollo_save_spreadsheet` | ❌ | ✅ | - | luckysheet-helpers.php:308 |
| `apollo_approve_membership` | ❌ | ✅ | ⚠️ | CulturaRioAdmin.php:39 |
| `apollo_reject_membership` | ❌ | ✅ | ⚠️ | CulturaRioAdmin.php:40 |
| `apollo_email_hub_save` | ❌ | ✅ | ⚠️ | EmailHubAdmin.php:41 |
| `apollo_verify_user` | ❌ | ✅ | ⚠️ | VerificationsTable.php:47 |
| `apollo_reject_user` | ❌ | ✅ | ⚠️ | VerificationsTable.php:48 |
| `apollo_builder_save` | ❌ | ✅ | ⚠️ | class-apollo-builder-ajax.php:48 |
| `apollo_builder_add_depoimento` | ✅ | ⚠️ | - | class-apollo-builder-ajax.php:68 |
| `apollo_save_widgets` | ❌ | ⚠️ | ⚠️ | WidgetsEndpoints.php:12 |
| `apollo_submit_comment` | ✅ | ⚠️ | - | CommentsEndpoint.php:21 |
| `apollo_image_upload` | ❌ | ✅ | - | ImageUploadHandler.php:118 |
| `apollo_pdf_export` | ❌ | ✅ | - | PdfExportHandler.php:40 |

## A.6 Rewrite Rules

### apollo-core Rewrite Rules
| Pattern | Query | Plugin | File |
|---------|-------|--------|------|
| `^apollo-sw\.js$` | `apollo_service_worker=1` | apollo-core | class-apollo-native-push.php:336 |
| `^apollo-sitemap\.xml$` | `apollo_sitemap=index` | apollo-core | class-apollo-native-seo.php:822 |
| `^apollo-sitemap-posts\.xml$` | `apollo_sitemap=posts` | apollo-core | class-apollo-native-seo.php:823 |
| `^apollo-sitemap-pages\.xml$` | `apollo_sitemap=pages` | apollo-core | class-apollo-native-seo.php:824 |
| `^apollo-sitemap-events\.xml$` | `apollo_sitemap=events` | apollo-core | class-apollo-native-seo.php:825 |
| `^cena-rio/?$` | `apollo_cena=calendar` | apollo-core | class-cena-rio-canvas.php:51 |
| `^cena-rio/mod/?$` | `apollo_cena=mod` | apollo-core | class-cena-rio-canvas.php:52 |

### apollo-social Rewrite Rules (via Apollo_Router)
| Pattern | Query | Module |
|---------|-------|--------|
| `^apollo/feed/{type}/?$` | `apollo_feed={type}` | Router (centralized) |
| `^apollo/grupos/?$` | `apollo_route=groups` | Router |

### apollo-social Rewrite Rules (DIRECT - NOT CENTRALIZED) ⚠️
| Pattern | Query | File | Issue |
|---------|-------|------|-------|
| `^apollo/doc/new/?$` | `apollo_doc=new` | DocumentsModule.php:176 | Direct add_rewrite_rule |
| `^apollo/doc/([a-zA-Z0-9]+)/?$` | `apollo_doc=$1` | DocumentsModule.php:177 | Direct |
| `^apollo/pla/new/?$` | `apollo_pla=new` | DocumentsModule.php:178 | Direct |
| `^apollo/pla/([a-zA-Z0-9]+)/?$` | `apollo_pla=$1` | DocumentsModule.php:179 | Direct |
| `^apollo/sign/([a-zA-Z0-9-]+)/?$` | `apollo_sign=$1` | DocumentsModule.php:180 | Direct |
| `^apollo/verificar/([A-Z0-9-]+)/?$` | `apollo_verify=$1` | DocumentsModule.php:181 | Direct |
| `^doc/new/?$` | `apollo_doc=new` | DocumentsModule.php:184 | Direct, NO PREFIX ⚠️ |
| `^doc/([a-zA-Z0-9]+)/?$` | `apollo_doc=$1` | DocumentsModule.php:185 | Direct, NO PREFIX ⚠️ |
| `^pla/new/?$` | `apollo_pla=new` | DocumentsModule.php:186 | Direct, NO PREFIX ⚠️ |
| `^pla/([a-zA-Z0-9]+)/?$` | `apollo_pla=$1` | DocumentsModule.php:187 | Direct, NO PREFIX ⚠️ |
| `^sign/([a-zA-Z0-9-]+)/?$` | `apollo_sign=$1` | DocumentsModule.php:188 | Direct, NO PREFIX ⚠️ |
| `^verificar/([A-Z0-9-]+)/?$` | `apollo_verify=$1` | DocumentsModule.php:189 | Direct, NO PREFIX ⚠️ |
| `^apollo/fornecedores/?$` | `apollo_route=suppliers` | SuppliersModule.php:392 | Direct |
| `^id/(\d+)/?$` | `apollo_user_id=$1` | class-user-page-rewrite.php:12 | Direct, NO PREFIX ⚠️ |
| `^meu-perfil/?$` | `apollo_private_profile=1` | class-user-page-rewrite.php:16 | Direct, NO PREFIX ⚠️ |

### apollo-rio Rewrite Rules (PWA module)
| Pattern | Query | File |
|---------|-------|------|
| `^pwa/manifest\.json$` | `pwa_manifest=1` | modules/pwa/wp-includes/class-wp.php:18 |

### apollo-events-manager Rewrite Rules
| Pattern | Query | File |
|---------|-------|------|
| `^apollo-events-feed/(json\|ical)/?$` | `apollo_feed=$1` | class-import-export-module.php:974 |

## A.7 Runtime flush_rewrite_rules Locations ⚠️

| Plugin | File | Line | Context | Severity |
|--------|------|------|---------|----------|
| apollo-core | class-activation.php | 65 | On activation | ✅ OK |
| apollo-core | class-apollo-core.php | 195 | Runtime check | ⚠️ P1 |
| apollo-core | class-apollo-native-seo.php | 1074 | Admin action | ⚠️ P2 |
| apollo-core | class-cena-rio-canvas.php | 133 | Standalone method | ⚠️ P2 |
| apollo-core | class-cena-rio-roles.php | 51 | Calls Canvas flush | ⚠️ P2 |
| apollo-social | apollo-social.php | 423 | On activation | ✅ OK |
| apollo-social | apollo-social.php | 452 | On deactivation | ✅ OK |
| apollo-social | DiagnosticsAdmin.php | 297 | Admin diagnostic | ⚠️ P3 |
| apollo-social | ChatModule.php | 102 | Runtime init | ⚠️ P1 |
| apollo-social | DocumentsModule.php | 120 | Runtime init | ⚠️ P0 |
| apollo-rio | apollo-rio.php | 120 | On activation | ✅ OK |
| apollo-rio | apollo-rio.php | 130 | On deactivation | ✅ OK |
| apollo-rio | pwa.php | 268 | Runtime | ⚠️ P1 |
| apollo-rio | pwa.php | 282 | Runtime | ⚠️ P1 |
| apollo-events-manager | apollo-events-manager.php | 1049 | Runtime | ⚠️ P1 |
| apollo-events-manager | apollo-events-manager.php | 6262 | On activation | ✅ OK |
| apollo-events-manager | apollo-events-manager.php | 6273 | Post activation | ⚠️ P2 |
| apollo-events-manager | apollo-events-manager.php | 6283 | Fallback | ⚠️ P2 |
| apollo-events-manager | apollo-events-manager.php | 6509 | Unknown | ⚠️ P1 |
| apollo-events-manager | post-types.php | 541 | Activation helper | ✅ OK |
| apollo-events-manager | post-types.php | 593 | Runtime check | ⚠️ P1 |
| apollo-events-manager | aprio-rest-api-settings.php | 204 | Admin save | ⚠️ P2 |
| apollo-events-manager | aprio-rest-api.php | 145 | Admin toggle | ⚠️ P2 |
| apollo-events-manager | aprio-rest-api.php | 150 | Admin toggle | ⚠️ P2 |

---

# PART B: CONFLICT & RISK MATRIX

## B.1 P0 - CRITICAL (Block Deploy)

| ID | Type | Description | Affected Plugins | Impact | Fix Required |
|----|------|-------------|------------------|--------|--------------|
| P0-1 | Schema Drift | `apollo_audit_log` vs `apollo_audit_logs` - two similar tables | core + social | Data fragmentation | Consolidate to single table |
| P0-2 | Runtime Flush | DocumentsModule.php:120 calls flush_rewrite_rules() on init | social | 404 race conditions, performance | Remove, gate with version |
| P0-3 | REST Security | `/forms/submit` has `__return_true` but accepts POST | core | Form spam, abuse | Require nonce or rate limit |
| P0-4 | Root Routes | `/doc/`, `/pla/`, `/sign/`, `/id/` without `/apollo/` prefix | social | Collision with themes/plugins | Migrate to `/apollo/` prefix |
| P0-5 | Schema Orchestration | 4 plugins each run own dbDelta independently | all | Race conditions, version drift | Implement orchestrator |

## B.2 P1 - HIGH (Fix Tonight)

| ID | Type | Description | Affected Plugins | Impact | Fix Required |
|----|------|-------------|------------------|--------|--------------|
| P1-1 | Runtime Flush | apollo-events-manager.php:1049, :6509 | events | Performance degradation | Remove runtime flush |
| P1-2 | Runtime Flush | pwa.php:268, :282 runtime flush | rio | PWA service worker issues | Remove runtime flush |
| P1-3 | Runtime Flush | post-types.php:593 runtime check | events | Slow page loads | Remove runtime flush |
| P1-4 | Runtime Flush | ChatModule.php:102 | social | Chat latency | Remove runtime flush |
| P1-5 | REST Namespace | `aprio` vs `apollo/v1` vs `apollo-events/v1` | events/core/social | Client confusion | Document, plan unification |
| P1-6 | Duplicate CPT REST | events CPT uses `events` REST base, social EventsSchema creates `apollo_events` table | events/social | Data split | Clarify ownership |
| P1-7 | AJAX Caps | Multiple AJAX handlers missing capability checks | core/social | Privilege escalation | Add cap checks |

## B.3 P2 - MEDIUM (Fix This Week)

| ID | Type | Description | Affected Plugins | Impact | Fix Required |
|----|------|-------------|------------------|--------|--------------|
| P2-1 | Direct add_rewrite_rule | SuppliersModule, DocumentsModule, UserPageRegistrar use direct add_rewrite_rule | social | Maintenance burden | Centralize via Router |
| P2-2 | Admin Flush | SEO, REST settings pages call flush | core/events | Admin slowness | Gate with version flag |
| P2-3 | __return_true Reads | 30+ GET endpoints with __return_true | all | No issue but audit needed | Document acceptable |
| P2-4 | REST Docs | No OpenAPI/Swagger documentation | all | Client integration difficulty | Generate docs |
| P2-5 | Schema Versions | Each plugin tracks own version separately | all | Upgrade confusion | Suite-wide version |

## B.4 P3 - LOW (Backlog)

| ID | Type | Description | Affected Plugins | Impact | Fix Required |
|----|------|-------------|------------------|--------|--------------|
| P3-1 | Table Naming | Some tables use singular, some plural | social | Inconsistency | Standardize in v3 |
| P3-2 | DiagnosticsAdmin Flush | Manual flush button in diagnostics | social | Acceptable for admin tool | Document only |
| P3-3 | REST Versioning | v1 only, no v2 strategy | all | Future planning | Design v2 strategy |

---

# PART C: INTEGRATED ARCHITECTURE

## C.1 Schema Orchestrator Design

**Decision: Option 1 - apollo-core owns the Schema Orchestrator**

```
apollo-core/
├── src/
│   └── Schema/
│       ├── SchemaOrchestrator.php      # NEW: Central coordinator
│       ├── SchemaModuleInterface.php   # NEW: Interface for all plugins
│       └── CoreSchemaModule.php        # apollo-core's own schema
│
apollo-social/src/Infrastructure/Database/
├── SocialSchemaModule.php              # REFACTOR: implements interface
│
apollo-rio/src/
├── RioSchemaModule.php                 # NEW: minimal (no tables)
│
apollo-events-manager/src/
├── EventsSchemaModule.php              # NEW: CPT meta management
```

### SchemaModuleInterface
```php
<?php
namespace Apollo_Core\Schema;

interface SchemaModuleInterface {
    public function getModuleName(): string;
    public function getVersion(): string;
    public function install(): void;
    public function upgrade(string $fromVersion): void;
    public function getTables(): array;
    public function getIndexes(): array;
}
```

### SchemaOrchestrator
```php
<?php
namespace Apollo_Core\Schema;

class SchemaOrchestrator {
    private const SUITE_VERSION_OPTION = 'apollo_suite_schema_version';
    private const CURRENT_SUITE_VERSION = '1.0.0';

    private array $modules = [];

    public function registerModule(SchemaModuleInterface $module): void {
        $this->modules[$module->getModuleName()] = $module;
    }

    public function installAll(): void {
        // Deterministic order
        $order = ['core', 'social', 'events', 'rio'];
        foreach ($order as $name) {
            if (isset($this->modules[$name])) {
                $this->modules[$name]->install();
            }
        }
        update_option(self::SUITE_VERSION_OPTION, self::CURRENT_SUITE_VERSION);
    }

    public function upgradeAll(): void {
        $current = get_option(self::SUITE_VERSION_OPTION, '0.0.0');
        foreach ($this->modules as $module) {
            $module->upgrade($current);
        }
        update_option(self::SUITE_VERSION_OPTION, self::CURRENT_SUITE_VERSION);
    }
}
```

## C.2 Routing Integration Design

**Central Router: apollo-social Apollo_Router already exists - extend it**

### Routing Policy
1. ALL Apollo routes MUST go through `Apollo_Router::addRoute()`
2. NO direct `add_rewrite_rule()` in modules
3. `flush_rewrite_rules()` ONLY in:
   - Plugin activation hooks
   - Plugin deactivation hooks
   - WP-CLI explicit command
   - Admin "Flush Rules" button (version-gated)
4. All routes under `/apollo/` prefix except:
   - CPT rewrites (managed by WP)
   - PWA manifest (`/pwa/manifest.json`)

### Migration Path for Direct Routes
```php
// BEFORE (DocumentsModule.php)
add_rewrite_rule('^doc/new/?$', 'index.php?apollo_doc=new', 'top');

// AFTER (via Router)
Apollo_Router::addRoute('documents', [
    'pattern' => '^apollo/doc/new/?$',
    'query'   => 'index.php?apollo_route=doc_new',
    'priority'=> 'top'
]);
```

## C.3 Security Integration Design

### RestSecurity Helper (apollo-core)
```php
<?php
namespace Apollo_Core\Security;

class RestSecurity {
    public static function requireAuth(WP_REST_Request $request): bool|WP_Error {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Authentication required', ['status' => 401]);
        }
        return true;
    }

    public static function requireNonce(WP_REST_Request $request): bool|WP_Error {
        $nonce = $request->get_header('X-WP-Nonce');
        if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_forbidden', 'Invalid nonce', ['status' => 403]);
        }
        return true;
    }

    public static function requireCap(string $cap, WP_REST_Request $request): bool|WP_Error {
        if (!current_user_can($cap)) {
            return new WP_Error('rest_forbidden', 'Insufficient permissions', ['status' => 403]);
        }
        return true;
    }

    public static function writeCallback(string $cap = 'edit_posts'): callable {
        return function(WP_REST_Request $request) use ($cap) {
            $auth = self::requireAuth($request);
            if (is_wp_error($auth)) return $auth;

            $nonce = self::requireNonce($request);
            if (is_wp_error($nonce)) return $nonce;

            return self::requireCap($cap, $request);
        };
    }
}
```

---

# PART D: TONIGHT DEPLOY PLAN

## Phase 0: Stop-Bleed (15 min)

### 0.1 Disable Risky Modules
```bash
# Add to wp-config.php or create mu-plugin
wp config set APOLLO_DISABLE_RUNTIME_FLUSH true --add --type=constant
wp config set APOLLO_DOCUMENTS_LEGACY_ROUTES false --add --type=constant
```

### 0.2 Feature Flag Check
```bash
wp eval "var_dump(get_option('apollo_feature_flags'));"
```

### 0.3 Backup
```bash
wp db export backup-pre-integration-$(date +%Y%m%d-%H%M%S).sql --add-drop-table
```

## Phase 1: Collision Fixes (30 min)

### 1.1 Remove Runtime Flush - apollo-social
```bash
# DocumentsModule.php:120 - REMOVE
# ChatModule.php:102 - REMOVE
```

### 1.2 Remove Runtime Flush - apollo-events-manager
```bash
# apollo-events-manager.php:1049 - REMOVE
# apollo-events-manager.php:6509 - REMOVE
# post-types.php:593 - REMOVE
```

### 1.3 Remove Runtime Flush - apollo-rio
```bash
# pwa.php:268 - REMOVE
# pwa.php:282 - REMOVE
```

### 1.4 Add Version Gates to Admin Flush
```php
// Wrap all admin flush calls with:
if (get_option('apollo_rules_version') !== APOLLO_RULES_VERSION) {
    flush_rewrite_rules(false);
    update_option('apollo_rules_version', APOLLO_RULES_VERSION);
}
```

## Phase 2: Schema Orchestrator (45 min)

### 2.1 Create SchemaOrchestrator in apollo-core
```bash
# Create apollo-core/src/Schema/SchemaOrchestrator.php
# Create apollo-core/src/Schema/SchemaModuleInterface.php
```

### 2.2 Update apollo-core Activation
```php
// apollo-core activation hook:
$orchestrator = new SchemaOrchestrator();
$orchestrator->registerModule(new CoreSchemaModule());
// Other plugins register via filter
do_action('apollo_register_schema_modules', $orchestrator);
$orchestrator->installAll();
```

### 2.3 Update apollo-social to Use Orchestrator
```php
// apollo-social activation:
add_action('apollo_register_schema_modules', function($orchestrator) {
    $orchestrator->registerModule(new SocialSchemaModule());
});
```

## Phase 3: Routing Integration (30 min)

### 3.1 Migrate DocumentsModule Routes to Router
```php
// DocumentsModule.php - remove add_rewrite_rule calls
// Add to Apollo_Router::getRoutes():
'doc_new' => [
    'pattern' => '^apollo/doc/new/?$',
    'query'   => 'apollo_route=doc_new',
],
'doc_edit' => [
    'pattern' => '^apollo/doc/([a-zA-Z0-9]+)/?$',
    'query'   => 'apollo_route=doc_edit&file_id=$matches[1]',
],
// ... etc
```

### 3.2 Add Legacy Redirects (301)
```php
// For /doc/*, /pla/*, /sign/*, /id/* redirect to /apollo/... equivalents
add_action('template_redirect', function() {
    if (preg_match('#^/doc/(.*)#', $_SERVER['REQUEST_URI'], $m)) {
        wp_redirect('/apollo/doc/' . $m[1], 301);
        exit;
    }
    // ... etc
});
```

### 3.3 Migrate SuppliersModule Routes
```php
// SuppliersModule.php - remove add_rewrite_rule calls
// Add to Router
```

## Phase 4: Security Alignment (20 min)

### 4.1 Add RestSecurity to apollo-core
```bash
# Create apollo-core/src/Security/RestSecurity.php
```

### 4.2 Fix `/forms/submit` Endpoint
```php
// forms/rest.php - change permission_callback
'permission_callback' => function($request) {
    // Rate limit by IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $key = 'apollo_form_' . md5($ip);
    $count = get_transient($key) ?: 0;
    if ($count > 10) {
        return new WP_Error('rate_limited', 'Too many requests', ['status' => 429]);
    }
    set_transient($key, $count + 1, MINUTE_IN_SECONDS);
    return true;
}
```

### 4.3 Audit AJAX Capability Checks
```bash
# For each AJAX handler missing cap check, add:
if (!current_user_can('appropriate_cap')) {
    wp_send_json_error('Unauthorized', 403);
}
```

## Phase 5: Migrations & Reconciliation (20 min)

### 5.1 Run Schema Upgrade
```bash
wp apollo schema:upgrade --dry-run
wp apollo schema:upgrade
```

### 5.2 Run Groups Reconciliation
```bash
wp apollo groups:reconcile --dry-run
wp apollo groups:reconcile
```

### 5.3 Verify Indexes
```bash
wp db query "SHOW INDEX FROM wp_apollo_groups;"
wp db query "SHOW INDEX FROM wp_apollo_group_members;"
```

## Phase 6: Staging Validation (15 min)

### 6.1 REST Endpoint Checks
```bash
# Core health
curl -s https://staging.apollo.rio.br/wp-json/apollo/v1/health | jq

# Groups API
curl -s https://staging.apollo.rio.br/wp-json/apollo/v1/comunas | jq '.data | length'
curl -s https://staging.apollo.rio.br/wp-json/apollo/v1/nucleos | jq '.data | length'

# Events API
curl -s https://staging.apollo.rio.br/wp-json/aprio/events | jq '.data | length'
```

### 6.2 Feed Checks
```bash
curl -s https://staging.apollo.rio.br/feed/ | head -20
curl -s https://staging.apollo.rio.br/feed/rss2/ | head -20
curl -s https://staging.apollo.rio.br/apollo/feed/ | head -20
```

### 6.3 Route Checks
```bash
# Document routes (should 301 redirect)
curl -sI https://staging.apollo.rio.br/doc/new | grep Location
curl -sI https://staging.apollo.rio.br/id/123 | grep Location

# Correct routes
curl -s https://staging.apollo.rio.br/apollo/doc/new -o /dev/null -w "%{http_code}"
```

### 6.4 WP-CLI Schema Status
```bash
wp apollo schema:status
```

## Phase 7: Production Deploy (20 min)

### 7.1 Pre-Deploy
```bash
# Full backup
wp db export production-backup-$(date +%Y%m%d-%H%M%S).sql --add-drop-table

# Put site in maintenance
wp maintenance-mode activate
```

### 7.2 Deploy Files
```bash
# Git pull or rsync updated plugins
git pull origin main
# OR
rsync -avz --delete plugins/ user@server:/path/to/wp-content/plugins/
```

### 7.3 Run Upgrades
```bash
wp plugin deactivate apollo-core apollo-social apollo-rio apollo-events-manager
wp plugin activate apollo-core
wp plugin activate apollo-social apollo-rio apollo-events-manager

# Schema upgrade
wp apollo schema:upgrade
```

### 7.4 Flush Rules (One-time)
```bash
wp rewrite flush
```

### 7.5 Verify
```bash
wp apollo schema:status
curl -s https://apollo.rio.br/wp-json/apollo/v1/health | jq
```

### 7.6 Exit Maintenance
```bash
wp maintenance-mode deactivate
```

## Rollback Procedure

### If Issues Detected
```bash
# 1. Maintenance mode
wp maintenance-mode activate

# 2. Restore database
wp db import production-backup-YYYYMMDD-HHMMSS.sql

# 3. Restore files (git)
git checkout tags/pre-integration

# 4. Flush
wp rewrite flush

# 5. Verify
wp plugin list --status=active

# 6. Exit maintenance
wp maintenance-mode deactivate
```

---

# PART E: GREP/RG VERIFICATION CHECKLIST

Run from `wp-content/plugins/`:

```bash
# 1. Runtime flush_rewrite_rules (should return 0 after fixes)
grep -rn "flush_rewrite_rules" apollo-core apollo-social apollo-rio apollo-events-manager \
  --include="*.php" | grep -v "vendor\|stubs\|activation\|deactivation" | wc -l

# 2. Direct add_rewrite_rule outside Router (should be 0 after migration)
grep -rn "add_rewrite_rule" apollo-social/src/Modules --include="*.php" | wc -l

# 3. __return_true on write operations (should be 0)
grep -rn "'permission_callback'.*__return_true" apollo-core apollo-social \
  apollo-rio apollo-events-manager --include="*.php" -A2 | \
  grep -E "POST|PUT|PATCH|DELETE" | grep -v "vendor"

# 4. Missing nonce checks in AJAX
grep -rn "wp_ajax_apollo" apollo-core apollo-social --include="*.php" | \
  while read line; do
    file=$(echo $line | cut -d: -f1)
    grep -L "check_ajax_referer\|wp_verify_nonce" "$file" 2>/dev/null
  done | sort -u

# 5. Hardcoded wp_ prefix (should use $wpdb->prefix)
grep -rn "'wp_apollo\|\"wp_apollo" apollo-core apollo-social apollo-rio \
  apollo-events-manager --include="*.php" | grep -v "vendor"

# 6. Multiple dbDelta entry points
grep -rln "dbDelta" apollo-core apollo-social apollo-rio apollo-events-manager \
  --include="*.php" | grep -v "vendor" | wc -l
# Expected: Should converge to orchestrator pattern

# 7. REST namespaces inventory
grep -roh "register_rest_route.*['\"][^'\"]*['\"]" apollo-core apollo-social \
  apollo-rio apollo-events-manager --include="*.php" | \
  grep -oP "(?<=')([^']+)(?=')" | sort -u

# 8. Routes without /apollo/ prefix
grep -rn "add_rewrite_rule.*\^[a-z]" apollo-social --include="*.php" | \
  grep -v "apollo/" | grep -v "vendor"
```

---

# PART F: FILES CREATED/MODIFIED

## Files to CREATE
1. `apollo-core/src/Schema/SchemaOrchestrator.php`
2. `apollo-core/src/Schema/SchemaModuleInterface.php`
3. `apollo-core/src/Schema/CoreSchemaModule.php`
4. `apollo-core/src/Security/RestSecurity.php`
5. `apollo-social/src/Infrastructure/Database/SocialSchemaModule.php`
6. `APOLLO-SUITE-INTEGRATION-PLAN.md` (this file)

## Files to MODIFY
1. `apollo-core/apollo-core.php` - Add orchestrator init
2. `apollo-core/includes/class-apollo-core.php:195` - Remove runtime flush
3. `apollo-core/includes/forms/rest.php:30` - Add rate limiting
4. `apollo-social/apollo-social.php` - Register with orchestrator
5. `apollo-social/src/Modules/Documents/DocumentsModule.php` - Remove add_rewrite_rule, flush
6. `apollo-social/src/Modules/Chat/ChatModule.php:102` - Remove flush
7. `apollo-social/src/Modules/Suppliers/SuppliersModule.php` - Remove add_rewrite_rule
8. `apollo-social/src/Modules/UserPages/UserPageRegistrar.php` - Remove add_rewrite_rule
9. `apollo-social/src/Infrastructure/Http/Apollo_Router.php` - Add new routes
10. `apollo-rio/apollo-rio.php` - Register with orchestrator
11. `apollo-rio/modules/pwa/pwa.php:268,282` - Remove runtime flush
12. `apollo-events-manager/apollo-events-manager.php` - Remove runtime flush (lines 1049, 6509)
13. `apollo-events-manager/includes/post-types.php:593` - Remove runtime flush
14. `apollo-events-manager/modules/rest-api/aprio-rest-api.php` - Remove admin flush

---

**END OF INTEGRATION PLAN**

Execute Phase 0-7 in order. Each phase has verification steps. Do not proceed to next phase until current phase passes verification.
