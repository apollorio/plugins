# APOLLO RIO PWA PAGE BUILDERS PLUGIN INVENTORY

**Audit Date:** 24 de janeiro de 2026
**Plugin Version:** 1.0.0
**WordPress Required:** 6.4+
**PHP Required:** 8.1+
**Dependencies:** apollo-core

## 01.b.1 - Plugin Identification

- **Name:** Apollo::Rio - PWA Page Builders
- **Version:** 1.0.0
- **Author:** Apollo Rio Team
- **Main File:** apollo-rio.php
- **Text Domain:** apollo-rio
- **Requires Plugins:** apollo-core

## 01.b.2 - Custom Post Types (CPTs)

| CPT Slug | Label | Supports | Rewrite Slug | Capability Type | Has Archive | File/Line |
| -------- | ----- | -------- | ------------ | --------------- | ----------- | --------- |
| None     | N/A   | N/A      | N/A          | N/A             | N/A         | N/A       |

## 01.b.3 - Taxonomies

| Taxonomy Slug | Hierarchical | Rewrite Slug | Linked CPTs | File/Line |
| ------------- | ------------ | ------------ | ----------- | --------- |
| None          | N/A          | N/A          | N/A         | N/A       |

## 01.b.4 - Meta Keys / Post Meta

| Meta Key           | Example Values | Usage Count (est.) | File/Line                                 |
| ------------------ | -------------- | ------------------ | ----------------------------------------- |
| \_event_start_date | Date string    | Low                | includes/class-apollo-seo-handler.php:395 |
| \_event_local_ids  | Array          | Low                | includes/class-apollo-seo-handler.php:396 |
| \_dj_bio           | String         | Low                | includes/class-apollo-seo-handler.php:429 |
| \_local_address    | String         | Low                | includes/class-apollo-seo-handler.php:464 |
| \_local_city       | String         | Low                | includes/class-apollo-seo-handler.php:465 |
| \_wp_page_template | String         | High               | includes/class-pwa-page-builders.php:128  |

## 01.b.5 - User Meta Keys

| Meta Key              | Example Values | Usage Count (est.) | File/Line                                                    |
| --------------------- | -------------- | ------------------ | ------------------------------------------------------------ |
| description           | String         | Medium             | includes/class-apollo-seo-handler.php:231                    |
| apollo_profile_image  | String         | Low                | includes/class-apollo-seo-handler.php:235                    |
| dismissed_wp_pointers | String         | Low                | modules/pwa/wp-admin/options-reading-offline-browsing.php:84 |

## 01.b.6 - Custom Database Tables

| Table Name | Schema | Created In | Used In |
| ---------- | ------ | ---------- | ------- |
| None       | N/A    | N/A        | N/A     |

## 01.b.7 - Shortcodes

| Shortcode            | File/Line                                   | Behavior                     |
| -------------------- | ------------------------------------------- | ---------------------------- |
| apollo_builder       | src/Modules/Builder/PageBuilder.php:23      | Renders page builder content |
| apollo_rio_optimized | includes/class-apollo-rio-shortcodes.php:65 | Renders optimized content    |
| apollo_rio_lazy      | includes/class-apollo-rio-shortcodes.php:68 | Renders lazy-loaded content  |
| apollo_rio_skeleton  | includes/class-apollo-rio-shortcodes.php:71 | Renders skeleton loading     |
| apollo_rio_image     | includes/class-apollo-rio-shortcodes.php:74 | Renders progressive images   |
| apollo_rio_defer     | includes/class-apollo-rio-shortcodes.php:77 | Renders deferred content     |
| apollo_rio_prefetch  | includes/class-apollo-rio-shortcodes.php:80 | Renders prefetch links       |
| apollo_rio_viewport  | includes/class-apollo-rio-shortcodes.php:83 | Renders viewport triggers    |
| apollo_rio_debug     | includes/class-apollo-rio-shortcodes.php:86 | Renders debug information    |

## 01.b.8 - REST API Endpoints

| Route                   | Methods | Controller          | Permission Callback |
| ----------------------- | ------- | ------------------- | ------------------- |
| /wp/v2/web-app-manifest | GET     | WP_Web_App_Manifest | Public              |

## 01.b.9 - AJAX Actions

| Action            | File/Line                                       | Permission Check |
| ----------------- | ----------------------------------------------- | ---------------- |
| wp_service_worker | modules/pwa/wp-includes/service-workers.php:244 | Public           |
| wp_error_template | modules/pwa/wp-admin/admin.php:21               | Public           |

## 01.b.10 - Options / Settings

| Option Key          | Purpose                 | File/Line                                                                                      |
| ------------------- | ----------------------- | ---------------------------------------------------------------------------------------------- |
| offline_browsing    | Enable offline browsing | modules/pwa/wp-admin/options-reading-offline-browsing.php:52                                   |
| short_name          | PWA short name          | modules/pwa/wp-includes/class-wp-web-app-manifest.php:220                                      |
| site_icon           | Site icon ID            | modules/pwa/wp-includes/class-wp-web-app-manifest.php:372                                      |
| site_icon_maskable  | Maskable icon setting   | modules/pwa/wp-includes/class-wp-web-app-manifest.php:523                                      |
| blogname            | Site title              | modules/pwa/wp-includes/components/class-wp-service-worker-navigation-routing-component.php:88 |
| blogdescription     | Site description        | modules/pwa/wp-includes/components/class-wp-service-worker-navigation-routing-component.php:89 |
| permalink_structure | Permalink structure     | modules/pwa/tests/test-service-workers.php:46                                                  |

## 01.b.11 - Assets

- **Scripts:** PWA service worker scripts, page builder JS
- **Styles:** PWA styles, builder CSS
- **Enqueue:** Via includes/class-pwa-page-builders.php and PWA modules

## 01.b.12 - Hooks (Filters/Actions)

- PWA-related hooks for service workers, web app manifest
- Page builder hooks for content rendering
- SEO hooks for meta generation

## 01.b.13 - Templates/Frontend Overrides

- PWA templates in modules/pwa/
- Page builder templates in templates/
- Offline browsing templates

## 01.b.14 - Capabilities & Roles

- Uses default WordPress capabilities
- PWA features available to all users

## 01.b.15 - Security / Sanitization

- Input sanitization in shortcodes and handlers
- Nonce checks in AJAX where applicable
- File validation for PWA assets

## 01.b.16 - Uninstall/Cleanup

- No explicit uninstall.php found
- Options may persist on uninstall

## 01.b.17 - Performance

- PWA optimizations for offline browsing
- Lazy loading and progressive image loading
- Service worker caching

## 01.b.18 - Dependencies

- **Required:** apollo-core
- **WP Version:** 6.4+
- **PHP Version:** 8.1+
- **External Libs:** PWA modules, service worker libraries

## 01.b.19 - I18n

- Text domain: apollo-rio
- Limited strings, mostly PWA-related

## 01.b.20 - GDPR / Privacy

- PWA manifest exposes site information
- Service worker may cache user data

## 01.b.21 - Tests / CI / Composer

- Composer dependencies present
- PWA module tests in modules/pwa/tests/
- PHPCS configuration

## REQUEST #02 - Slug Conflicts Check

- No CPTs or taxonomies to check for slug conflicts

## REQUEST #03 - Security & Quality Audit

- **AJAX Security:** Public endpoints for PWA functionality
- **SQL Security:** No direct queries found
- **Meta Query Performance:** Minimal meta usage
- **File Uploads:** PWA icon handling with validation
- **Email Handling:** None
- **Cron Jobs:** None
- **Uninstall:** No cleanup script found

## REQUEST #04 - CSV Export Format

```
item_type,name,slug,file_location,usage_count,exposes_api,permission_required,sanitization,notes
Shortcode,apollo_builder,,src/Modules/Builder/PageBuilder.php:23,Medium,No,read,Yes,PWA page builder
Shortcode,apollo_rio_optimized,,includes/class-apollo-rio-shortcodes.php:65,Low,No,read,Yes,Content optimization
Shortcode,apollo_rio_lazy,,includes/class-apollo-rio-shortcodes.php:68,Medium,No,read,Yes,Lazy loading
Shortcode,apollo_rio_skeleton,,includes/class-apollo-rio-shortcodes.php:71,Low,No,read,Yes,Loading skeleton
Shortcode,apollo_rio_image,,includes/class-apollo-rio-shortcodes.php:74,High,No,read,Yes,Progressive images
Shortcode,apollo_rio_defer,,includes/class-apollo-rio-shortcodes.php:77,Medium,No,read,Yes,Deferred content
Shortcode,apollo_rio_prefetch,,includes/class-apollo-rio-shortcodes.php:80,Low,No,read,Yes,Resource prefetch
Shortcode,apollo_rio_viewport,,includes/class-apollo-rio-shortcodes.php:83,Low,No,read,Yes,Viewport triggers
Shortcode,apollo_rio_debug,,includes/class-apollo-rio-shortcodes.php:86,Low,No,read,Yes,Debug info
REST,/wp/v2/web-app-manifest,GET,modules/pwa/wp-includes/class-wp-web-app-manifest.php:459,Medium,Yes,public,Yes,PWA manifest
AJAX,wp_service_worker,,modules/pwa/wp-includes/service-workers.php:244,High,No,public,Yes,Service worker
AJAX,wp_error_template,,modules/pwa/wp-admin/admin.php:21,Low,No,public,Yes,Error template
Option,offline_browsing,,modules/pwa/wp-admin/options-reading-offline-browsing.php:52,Low,No,,Yes,PWA setting
Option,short_name,,modules/pwa/wp-includes/class-wp-web-app-manifest.php:220,Low,No,,Yes,PWA name
Option,site_icon,,modules/pwa/wp-includes/class-wp-web-app-manifest.php:372,Low,No,,Yes,Site icon
Option,site_icon_maskable,,modules/pwa/wp-includes/class-wp-web-app-manifest.php:523,Low,No,,Yes,Maskable icon
Meta Key,_wp_page_template,,includes/class-pwa-page-builders.php:128,High,No,read,Yes,Page template
```

## REQUEST #00 - Priority Setup

- Activate in staging environment
- Verify PWA functionality and service workers
- Test offline browsing features
- Confirm apollo-core dependency
- Review PWA manifest for exposed data
- Check service worker caching policies
