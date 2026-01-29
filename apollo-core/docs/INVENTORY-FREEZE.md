# Apollo Plugin Ecosystem - Inventory Freeze

**Date:** 2025-01-13  
**Version:** 2.0.0  
**Status:** LOCKED FOR DEPLOYMENT

---

## Purpose

This document provides a complete inventory of all Apollo plugins and their components.  
**DO NOT MODIFY** CPT slugs, taxonomies, meta keys, option names, REST routes, or AJAX actions.

---

## Plugin Inventory

### 1. apollo-core

**Path:** `/wp-content/plugins/apollo-core/`  
**Version:** 2.0.0  
**Main File:** `apollo-core.php`

#### Key Classes
| Class | File | Purpose |
|-------|------|---------|
| `Apollo_Core` | `apollo-core.php` | Main plugin bootstrap |
| `Apollo_Assets` | `includes/class-apollo-assets.php` | Unified asset manager |
| `Apollo_Snippets_Manager` | `includes/class-apollo-snippets-manager.php` | Custom CSS/JS injection |
| `Apollo_Customizations` | `customizations/class-apollo-customizations.php` | Theme customizations |
| `Apollo_Audit_Log` | `includes/class-apollo-audit-log.php` | Security audit logging |
| `Apollo_Email_Security_Log` | `includes/class-email-security-log.php` | Email security logging |

#### Constants (DO NOT CHANGE)
```php
APOLLO_CORE_VERSION
APOLLO_CORE_PLUGIN_DIR
APOLLO_CORE_PLUGIN_URL
APOLLO_CORE_BOOTSTRAPPED
```

#### Options (DO NOT RENAME)
- `apollo_core_settings`
- `apollo_snippets_*`
- `apollo_customizations_*`
- `apollo_mod_settings`

#### Database Tables
- `{prefix}apollo_audit_log`
- `{prefix}apollo_mod_log`
- `{prefix}apollo_email_security_log`

---

### 2. apollo-events-manager

**Path:** `/wp-content/plugins/apollo-events-manager/`  
**Version:** 1.0.0  
**Main File:** `apollo-events-manager.php`

#### Custom Post Types (DO NOT RENAME)
| Slug | Label | Purpose |
|------|-------|---------|
| `event_listing` | Events | Main event CPT |
| `event_dj` | DJs | DJ profiles |
| `event_local` | Venues | Venue locations |

#### Taxonomies (DO NOT RENAME)
| Slug | Associated CPT | Purpose |
|------|----------------|---------|
| `event_type` | event_listing | Event categories |
| `event_tag` | event_listing | Event tags |
| `music_genre` | event_dj | Music genres |

#### Meta Keys (DO NOT RENAME)
**event_listing:**
- `_event_start_date`
- `_event_end_date`
- `_event_start_time`
- `_event_end_time`
- `_event_venue_id`
- `_event_dj_ids`
- `_event_ticket_url`
- `_event_guestlist_url`
- `_event_coupon_code`
- `_event_latitude`
- `_event_longitude`
- `_event_youtube_url`
- `_event_banner_url`
- `_event_gallery_images`

**event_dj:**
- `_dj_instagram`
- `_dj_soundcloud`
- `_dj_spotify`
- `_dj_bio`
- `_dj_photo`

**event_local:**
- `_local_address`
- `_local_latitude`
- `_local_longitude`
- `_local_phone`
- `_local_website`
- `_local_gallery_images`

#### REST Routes (DO NOT CHANGE)
| Endpoint | Method | Purpose |
|----------|--------|---------|
| `apollo/v1/events` | GET | List events |
| `apollo/v1/events/{id}` | GET | Get single event |
| `apollo/v1/events/{id}/interested` | POST | Toggle interest |
| `apollo/v1/user/favorites` | GET | Get user favorites |

#### AJAX Actions (DO NOT RENAME)
- `ajax_filter_events`
- `apollo_save_event_meta`
- `apollo_quick_create_dj`
- `apollo_quick_create_local`
- `apollo_upload_event_banner`

#### Constants
```php
APOLLO_APRIO_VERSION
APOLLO_APRIO_PATH
APOLLO_APRIO_URL
APOLLO_DEBUG
APOLLO_PORTAL_DEBUG
```

---

### 3. apollo-social

**Path:** `/wp-content/plugins/apollo-social/`  
**Version:** 1.0.0

#### User Meta Keys (DO NOT RENAME)
- `apollo_profile_views`
- `apollo_last_active`
- `apollo_privacy_settings`
- `apollo_social_links`
- `apollo_bio`

---

### 4. apollo-email-newsletter

**Path:** `/wp-content/plugins/apollo-email-newsletter/`  
**Version:** 1.0.0

#### Options (DO NOT RENAME)
- `newsletter_*` (various newsletter settings)

---

### 5. apollo-email-templates

**Path:** `/wp-content/plugins/apollo-email-templates/`  
**Version:** 1.0.0

#### Options (DO NOT RENAME)
- `mailtpl_*` (email template settings)

---

### 6. apollo-hardening

**Path:** `/wp-content/plugins/apollo-hardening/`  
**Version:** 1.0.0

#### Options (DO NOT RENAME)
- `apollo_hardening_*`

---

### 7. apollo-rio

**Path:** `/wp-content/plugins/apollo-rio/`  
**Version:** 1.0.0

---

### 8. apollo-secure-upload

**Path:** `/wp-content/plugins/apollo-secure-upload/`  
**Version:** 1.0.0

#### Options (DO NOT RENAME)
- `apollo_secure_upload_*`

---

### 9. apollo-webp-compressor

**Path:** `/wp-content/plugins/apollo-webp-compressor/`  
**Version:** 1.0.0

#### Options (DO NOT RENAME)
- `apollo_webp_*`

---

## Asset Handles (Apollo_Assets)

### Vendor Assets
| Handle | File | Version Method |
|--------|------|----------------|
| `apollo-vendor-remixicon` | vendor/remixicon/remixicon.css | filemtime() |
| `apollo-vendor-leaflet` | vendor/leaflet/leaflet.css + .js | filemtime() |
| `apollo-vendor-motion` | vendor/motion/motion.min.js | filemtime() |
| `apollo-vendor-chartjs` | vendor/chartjs/chart.umd.min.js | filemtime() |
| `apollo-vendor-datatables` | vendor/datatables/* | filemtime() |
| `apollo-vendor-sortable` | vendor/sortablejs/Sortable.min.js | filemtime() |
| `apollo-vendor-phosphor` | vendor/phosphor-icons/phosphor-icons.js | filemtime() |

### Core Assets
| Handle | File | Dependencies |
|--------|------|--------------|
| `apollo-core-uni` | core/uni.css | apollo-vendor-remixicon |
| `apollo-core-base` | core/base.js | none |
| `apollo-core-darkmode` | core/dark-mode.js | apollo-core-base |
| `apollo-core-animate` | core/animate.css | none |
| `apollo-core-clock` | core/clock.js | none |
| `apollo-core-event-page` | core/event-page.js | apollo-core-base |

### Legacy Handles (Backwards Compatibility)
| Legacy Handle | Maps To |
|---------------|---------|
| `apollo-uni-css` | apollo-core-uni |
| `remixicon` | apollo-vendor-remixicon |
| `apollo-remixicon` | apollo-vendor-remixicon |
| `apollo-base-js` | apollo-core-base |
| `apollo-motion` | apollo-vendor-motion |
| `framer-motion` | apollo-vendor-motion |
| `chartjs` | apollo-vendor-chartjs |
| `chart-js` | apollo-vendor-chartjs |
| `leaflet` | apollo-vendor-leaflet |
| `datatables-js` | apollo-vendor-datatables |
| `datatables-css` | apollo-vendor-datatables |

---

## Freeze Rules

### DO NOT MODIFY
1. CPT slugs (`event_listing`, `event_dj`, `event_local`)
2. Taxonomy slugs (`event_type`, `event_tag`, `music_genre`)
3. Meta key names (all `_event_*`, `_dj_*`, `_local_*` prefixed)
4. Option names (all `apollo_*`, `newsletter_*`, `mailtpl_*` prefixed)
5. REST route namespaces (`apollo/v1`)
6. AJAX action names
7. Database table names
8. Asset handle names

### Changes Require
- Migration script for database changes
- Version bump in all affected plugins
- Documentation update
- QA verification across all environments

---

**Document Version:** 1.0.0  
**Last Updated:** 2025-01-13
