# Apollo Template Conversion Map

> **Last Updated:** 2024-12-21
> **Status:** Phase D - Systematic Template Conversion (4/6 Complete)

---

## Overview

This document maps approved HTML design files to their PHP runtime templates, establishing clear data contracts between wrapper templates and core rendering templates.

### Architecture Pattern

```
[Events Manager Wrapper] → (builds $template_data) → [Core Template] → (uses partials) → [Rendered HTML]
```

- **Wrapper templates** (apollo-events-manager/templates/) handle data preparation, ViewModel creation, and routing
- **Core templates** (apollo-core/templates/) receive extracted variables and render the approved HTML structure
- **Partials** (apollo-core/templates/partials/) contain reusable components

---

## Template Conversion Table

| # | Source HTML | Core Template | Wrapper Template | Status |
|---|-------------|---------------|------------------|--------|
| 1 | body_eventos ----list all.html | core-events-listing.php | portal-discover.php | ✅ DONE |
| 2 | body_evento_eventoID ----single page.html | core-event-single.php | single-event_listing.php | ✅ DONE |
| 3 | eventos - local - single.html | core-local-single.php | single-event_local.php | ✅ DONE |
| 4 | eventos - dj - single.html | core-dj-single.php | single-event_dj.php | ✅ DONE |
| 5 | forms.html | core-forms.php | page-cenario-new-event.php | 🔄 PENDING |
| 6 | login - register.html | core-login-register.php | (apollo-social) | 🔄 PENDING |
| 7 | eventos - evento - single.html | (alternate) | (reference only) | ⏸️ SKIP |
| 8 | eventos - eventos - listing.html | (alternate) | (reference only) | ⏸️ SKIP |

---

## Data Contracts

### 1. core-events-listing.php

**Source HTML:** `body_eventos ----list all.html`
**Wrapper:** `apollo-events-manager/templates/portal-discover.php`

**Required $context keys:**

```php
$context = [
    // Page metadata
    'page_title'        => string,       // "Discover Events"
    'is_print'          => bool,         // false (for print mode toggling)
    
    // User context
    'current_user'      => array|null,   // ['id', 'name', 'avatar_url', 'is_logged_in']
    'navigation_links'  => array,        // [['label', 'url', 'icon', 'active']]
    
    // Hero section
    'hero_title'        => string,       // "Descubra eventos..."
    'hero_subtitle'     => string,       // tagline
    'hero_background'   => string,       // URL or empty
    
    // Filters
    'period_filters'    => array,        // [['slug', 'label', 'url', 'active']]
    'category_filters'  => array,        // [['slug', 'label', 'url', 'active', 'type']]
    'current_month'     => string,       // "Dezembro 2024"
    
    // Events data
    'event_sections'    => array,        // [['slug', 'title', 'icon', 'show_title', 'grid_class', 'events' => [...]]]
    
    // Banner (optional)
    'banner'            => array|null,   // ['image', 'title', 'subtitle', 'excerpt', 'url', 'cta_text']
    
    // Bottom bar
    'show_bottom_bar'   => bool,
    'bottom_bar_data'   => array,
    
    // Template loader instance
    'template_loader'   => Apollo_Template_Loader,
];
```

**Event item structure (inside event_sections[].events[]):**

```php
$event_data = [
    'id'             => int,
    'title'          => string,
    'permalink'      => string,
    'thumbnail_url'  => string,
    'start_date'     => string,      // 'Y-m-d'
    'start_time'     => string,      // 'H:i'
    'end_time'       => string,
    'day'            => string,      // '25'
    'month'          => string,      // 'DEZ'
    'venue_name'     => string,
    'venue_url'      => string,
    'ticket_url'     => string,
    'ticket_price'   => string,
    'tags'           => array,       // [['name', 'slug']]
    'djs'            => array,       // [['id', 'name', 'thumbnail_url']]
    'is_featured'    => bool,
    'is_today'       => bool,
    'is_tomorrow'    => bool,
];
```

---

### 2. core-event-single.php (PENDING)

**Source HTML:** `body_evento_eventoID ----single page.html`
**Wrapper:** `apollo-events-manager/templates/single-event_listing.php`

**Required $context keys:**

```php
$context = [
    'event'             => WP_Post,
    'event_id'          => int,
    'title'             => string,
    'description'       => string,       // HTML content
    'thumbnail_url'     => string,
    'banner_url'        => string,
    'video_url'         => string,       // YouTube embed
    'start_date'        => string,
    'start_time'        => string,
    'end_time'          => string,
    'formatted_date'    => string,       // "Sábado, 25 de Dezembro"
    'venue'             => array,        // ['id', 'name', 'address', 'lat', 'lng', 'permalink']
    'ticket_url'        => string,
    'ticket_price'      => string,
    'dj_slots'          => array,        // [['dj_id', 'name', 'thumbnail', 'start', 'end']]
    'tags'              => array,
    'share_url'         => string,
    'is_print'          => bool,
    'template_loader'   => Apollo_Template_Loader,
];
```

---

### 3. core-local-single.php (PENDING)

**Source HTML:** `eventos - local - single.html`
**Wrapper:** `apollo-events-manager/templates/single-event_local.php`

**Required $context keys:**

```php
$context = [
    'local_id'          => int,
    'local_name'        => string,
    'local_desc'        => string,       // HTML
    'venue_label'       => string,       // "Club" | "Venue"
    'full_address'      => string,
    'local_lat'         => string,
    'local_lng'         => string,
    'gallery'           => array,        // URLs
    'local_website'     => string,
    'local_instagram'   => string,
    'local_facebook'    => string,
    'upcoming_events'   => array,        // Event objects
    'is_print'          => bool,
    'template_loader'   => Apollo_Template_Loader,
];
```

---

### 4. core-dj-single.php

**Source HTML:** `eventos - dj - single.html`
**Wrapper:** `apollo-events-manager/templates/single-event_dj.php`

**Required $context keys:**

```php
$context = [
    // Identity
    'dj_id'             => int,
    'dj_name'           => string,
    'dj_name_formatted' => string,       // Name with <br> for display
    'dj_photo_url'      => string,
    'dj_tagline'        => string,
    'dj_roles'          => string,       // "DJ · Producer · Live Selector"
    'dj_projects'       => array,        // ['Apollo::rio', 'Dismantle']
    
    // Bio
    'dj_bio_excerpt'    => string,       // Trimmed plain text
    'dj_bio_full'       => string,       // Full HTML content
    
    // Player
    'dj_track_title'    => string,
    'sc_embed_url'      => string,       // Full SoundCloud iframe src URL
    
    // Links (pre-filtered, only items with URLs)
    'music_links'       => array,        // [['url', 'icon', 'label']]
    'social_links'      => array,        // [['url', 'icon', 'label']]
    'asset_links'       => array,        // [['url', 'icon', 'label']]
    'platform_links'    => array,        // [['url', 'icon', 'label']]
    'media_kit_url'     => string,       // Direct URL for header button
    
    // Flags
    'is_print'          => bool,
];
```

---

### 5. core-forms.php (PENDING)

**Source HTML:** `forms.html`
**Wrapper:** `apollo-events-manager/templates/page-cenario-new-event.php`

**Required $context keys:**

```php
$context = [
    'form_action'       => string,       // AJAX action name
    'nonce_field'       => string,       // wp_nonce_field() output
    'dj_options'        => array,        // [['id', 'name']]
    'local_options'     => array,        // [['id', 'name', 'address']]
    'category_options'  => array,
    'tag_options'       => array,
    'user_can_edit'     => bool,
    'existing_event'    => array|null,   // For edit mode
    'template_loader'   => Apollo_Template_Loader,
];
```

---

### 6. core-login-register.php (PENDING)

**Source HTML:** `login - register.html`
**Wrapper:** `apollo-social/templates/auth/login-register.php`

**Required $context keys:**

```php
$context = [
    'login_url'         => string,
    'register_url'      => string,
    'forgot_password_url' => string,
    'terms_url'         => string,
    'redirect_to'       => string,
    'error_message'     => string|null,
    'is_login_mode'     => bool,
    'quiz_enabled'      => bool,
    'template_loader'   => Apollo_Template_Loader,
];
```

---

## Partials Inventory

| Partial Name | Location | Used By |
|--------------|----------|---------|
| assets.php | partials/ | All templates |
| header-nav.php | partials/ | Events listing, Event single |
| hero-section.php | partials/ | Events listing |
| event-card.php | partials/ | Events listing, Local single, DJ single |
| bottom-bar.php | partials/ | Events listing, Event single |
| social-navbar.php | partials/ | Social templates |
| social-sidebar.php | partials/ | Social templates |

---

## Conversion Log

### 2024-12-21: core-dj-single.php

- **Source:** eventos - dj - single.html
- **Core Template:** apollo-core/templates/core-dj-single.php
- **Wrapper Updated:** apollo-events-manager/templates/single-event_dj.php
- **Backup:** single-event_dj.php.backup-before-core
- **Features:** DJ hero with photo, vinyl SoundCloud player, bio modal, categorized links (music/social/assets/platforms)
- **Data Contract:** See section 4 above
- **Verification:** Open any `/dj/{slug}/` page

### 2024-12-21: core-local-single.php

- **Source:** eventos - local - single.html
- **Core Template:** apollo-core/templates/core-local-single.php
- **Wrapper Updated:** apollo-events-manager/templates/single-event_local.php
- **Backup:** single-event_local.php.backup-before-core
- **Features:** Hero slider, venue info, social buttons, map with route, upcoming events with date-cutout cards, testimonials scroller
- **Data Contract:** See section 3 above
- **Verification:** Open any `/local/{slug}/` page

### 2024-12-21: core-event-single.php

- **Source:** body_evento_eventoID ----single page.html
- **Core Template:** apollo-core/templates/core-event-single.php
- **Wrapper Updated:** apollo-events-manager/templates/single-event_listing.php
- **Backup:** single-event_listing.php.backup-before-core
- **Features:** Hero with YouTube/image, quick-actions, RSVP avatars, DJ lineup, venue slider, map, tickets, bottom bar
- **Data Contract:** See section 2 above
- **Verification:** Open any `/evento/{slug}/` page

### 2024-12-20: core-events-listing.php

- **Source:** body_eventos ----list all.html
- **Core Template:** apollo-core/templates/core-events-listing.php
- **Wrapper Updated:** apollo-events-manager/templates/portal-discover.php
- **New Partials:** None (existing partials sufficient)
- **Data Contract:** See section 1 above
- **Verification:** Open `/eventos/` or events archive page

---

## Notes

1. **No Subfolders:** Apollo_Template_Loader sanitizes names, so all core templates use flat naming: `core-*.php`
2. **Print Mode:** Pass `['is_print' => true]` to hide nav/animations
3. **Recursion Prevention:** Wrappers must NOT call themselves; always call `core-*` templates
4. **Escaping:** All output must use esc_html(), esc_url(), esc_attr(), wp_kses_post()
