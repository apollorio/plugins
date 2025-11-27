# Apollo Plugins - Strict Mode Template Refactoring
## Session: 2025-11-25 (Updated - Full Completion)

### ✅ ALL ROUTES COMPLETED

| Route | Template | Status | Commit |
|-------|----------|--------|--------|
| `/dj/` | `single-event_dj.php` | ✅ 100% | 3a01a22 |
| `/meu-perfil/` | `private-profile.php` | ✅ 100% | 99c3f47 |
| `/evento/{slug}/` | `single-event-standalone.php` | ✅ 100% | a06448a |
| `/eventos/` | `portal-discover.php`, `event-card.php` | ✅ 100% | b315627 |
| `/feed/` | `feed.php` | ✅ 100% | f1cd6aa |
| `/id/{user}/` | `user-page-view.php` | ✅ 100% | e732332 |

---

## 1. DJ Template - 100% Design Conformance
**File:** `apollo-events-manager/templates/single-event_dj.php`

### Structure Change (BREAKING):
- **Before:** `<main class="page-wrap">` with `bento-grid` layout
- **After:** `<section class="dj-shell">` with proper hierarchy:
  ```
  dj-shell > dj-page > dj-content > dj-header > dj-hero > dj-player-block > dj-info-grid > dj-footer
  ```

### New Features Added:
- ✅ `dj-header` with navigation (back link, title, share button)
- ✅ `dj-hero` with:
  - Photo with mediakit badge
  - Tagline (`_dj_tagline`)
  - Main name, Sub name (`_dj_name_sub`)
  - Roles tags (`_dj_roles` - comma-separated)
  - Projects section
- ✅ `vinyl-player` with **TONEARM** (per design spec):
  - Vinyl disc with grooves and label
  - Tonearm (base, arm, head)
  - Play/pause toggle
- ✅ `dj-info-grid` with 4 cards:
  - Bio card with excerpt and modal trigger
  - Music links (SoundCloud, Spotify, YouTube, etc.)
  - Social links (Instagram, Twitter, TikTok, Facebook, Website)
  - Downloads (Media Kit, Rider, Press Photos, EPK)
- ✅ `dj-footer` with location and device info
- ✅ Bio Modal overlay with full biography
- ✅ Share API integration (Web Share API with clipboard fallback)
- ✅ Motion.js animations for page entrance

### FORCED TOOLTIPS:
All empty placeholder fields show `data-tooltip`:
- Tagline: "Tagline não definida"
- Sub name: "Nome secundário não definido"
- Roles: "Adicione roles no admin"
- Projects: "Projetos não cadastrados"
- Bio excerpt: "Bio não cadastrada"
- Music links: "Nenhum link de música cadastrado"
- Social links: "Nenhuma rede social cadastrada"
- Downloads: "Nenhum arquivo para download"
- Track title: "Track title não definido"

### New Meta Keys Supported:
```php
$dj_name_sub       // Real name or alias
$dj_tagline        // Short tagline
$dj_roles          // Comma-separated: "DJ, Producer, Label Owner"
$dj_bio_excerpt    // Short bio for card
$dj_track_title    // Title for vinyl player
$dj_soundcloud_track  // Specific track URL for player
$dj_more_platforms // "More platforms:" text
$dj_apple_music    // Apple Music URL
$dj_tidal          // Tidal URL
$dj_deezer         // Deezer URL
$dj_press_photos_url  // Press photos download
$dj_epk_url        // EPK download
```

---

## 2. Private Profile Page - NEW TEMPLATE
**File:** `apollo-social/templates/private-profile.php`
**Route:** `/meu-perfil/`

### Features:
- ✅ Auto-detect logged-in user (redirects to login if not authenticated)
- ✅ Profile card with avatar, stats, memberships
- ✅ 5-tab system:
  1. **Eventos favoritos** - Favorited events with status (Ir/Talvez)
  2. **Meus números** - Performance metrics (placeholder)
  3. **Núcleo (privado)** - Private groups/workspaces
  4. **Comunidades** - Public communities
  5. **Documentos** - Contracts, forms to sign
- ✅ Sidebar with:
  - Quick summary (next event, pending docs, unread messages)
  - Social status (active núcleos, featured communities)
- ✅ Motion.js animations for tab transitions
- ✅ Forced tooltips on all empty placeholders

### Route Registration:
**File:** `apollo-social/user-pages/class-user-page-rewrite.php`
```php
add_rewrite_rule('^meu-perfil/?$', 'index.php?apollo_private_profile=1', 'top');
add_rewrite_tag('%apollo_private_profile%', '([0-1]+)');
```

### Template Loader:
**File:** `apollo-social/user-pages/class-user-page-template-loader.php`
- Added check for `apollo_private_profile` query var
- Loads `private-profile.php` template

---

## 3. Event Single Page - 100% Design Conformance
**File:** `apollo-events-manager/templates/single-event-standalone.php`
**Route:** `/evento/{slug}/`
**Commit:** `a06448a`

### Features:
- ✅ Mobile-first layout with `mobile-container` pattern
- ✅ `hero-media` with video-cover placeholder
- ✅ `quick-actions` (4 buttons: Share, Favorite, Navigate, Tickets)
- ✅ `rsvp-row` with avatars-explosion animation
- ✅ `info` section with music-tags-marquee
- ✅ `promo-gallery-slider` with navigation
- ✅ `lineup-list` with `dj-link` class linking to `/dj/{slug}/`
- ✅ `local-images-slider` with Leaflet map integration
- ✅ `route-controls` with Uber/Waze/Google Maps links
- ✅ `tickets-grid` with early-bird/regular pricing
- ✅ **NEW: Registros section** - Custom comments system for event check-ins
- ✅ `bottom-bar` sticky with quick actions
- ✅ Forced tooltips on ALL placeholders

### AJAX Handler:
**File:** `apollo-events-manager/apollo-events-manager.php`
- Added `wp_ajax_apollo_submit_event_comment`
- Nonce verification, input validation, rate limiting

---

## 4. Discover Events - Tooltips Added
**Files:** 
- `apollo-events-manager/templates/portal-discover.php`
- `apollo-events-manager/templates/parts/event-card.php`
**Route:** `/eventos/`
**Commit:** `b315627`

### Changes:
- ✅ Added `base.css` stylesheet reference
- ✅ Forced tooltips on event card placeholders:
  - Image: "Banner do evento - imagem principal"
  - Date badge: "Data do evento"
  - DJ lineup fallback: "Line-up a confirmar"
  - Location fallback: "Local a confirmar"

---

## 5. Social Feed - Sidebar Added
**File:** `apollo-social/templates/feed/feed.php`
**Route:** `/feed/`
**Commit:** `f1cd6aa`

### Changes:
- ✅ 2-column responsive grid (`lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]`)
- ✅ **Próximos 7 dias** widget with real events from DB
- ✅ **Comunidades em alta** widget with top groups
- ✅ Forced tooltips on empty-state placeholders
- ✅ Footer links (Privacidade, Termos, Apollo Business)

---

## 6. User Page - Depoimentos Section
**File:** `apollo-social/templates/user-page-view.php`
**Route:** `/id/{user}/`
**Commit:** `e732332`

### Complete Refactor:
- ✅ Mobile-first layout with `mobile-container` pattern
- ✅ Hero cover image with gradient overlay
- ✅ Profile card with avatar, verification badge, stats row
- ✅ Bio section with fallback placeholder + tooltip
- ✅ Action buttons: Follow/Message (guests see login CTA)
- ✅ Widgets grid with `aprioEXP-card-shell` styling
- ✅ **NEW: Depoimentos (testimonials) section**:
  - Real comments fetched from WordPress comments system
  - AJAX form for logged-in users
  - Rate limiting: 3 depoimentos per user per day
  - Self-testimonial prevention
- ✅ Forced tooltips on ALL interactive elements

### AJAX Handler:
**File:** `apollo-social/apollo-social.php`
- Added `wp_ajax_apollo_submit_depoimento`
- Nonce verification, input validation (5-1000 chars)
- Rate limiting with transients

---

## 🔧 POST-DEPLOYMENT STEPS

After pulling these changes, run:
```bash
wp rewrite flush --hard
```
Or visit: **Settings > Permalinks > Save Changes** (twice)

---

## 📚 Design Reference Files

Located in: `apollo-core/templates/designs-final-hmtl/`
- `design expemple for PAGE-FOR-CPT DJ stylesheet by unicss imported by script.md`
- `design example PAGE-PRIVATE-PROFILE-PAGE-TAB stylesheet by tailwind .md`
- `design expemple for PAGE-FOR-CPT EVENT LISTING single page stylesheet by unicss imported by script.md`
- `design expemple for PAGE-FOR-DISCOVER EVENTS stylesheet by tailwind .md`
- `design expemple for PAGE-FOR-SOCIAL FEED stylesheet by tailwind .md`

---

**Session completed:** 2025-11-25
**Branch:** `fix/apollo-strict-finalize-gh-202511251530`
**Total commits this session:** 7 (3a01a22, 99c3f47, bd548ae, a06448a, b315627, f1cd6aa, e732332)
- `design expemple for PAGE-FOR-CPT EVENT LISTING single page stylesheet by unicss imported by script.md`
- `design expemple for PAGE-FOR-DISCOVER EVENTS stylesheet by tailwind .md`
- `design expemple for PAGE-FOR-SOCIAL FEED stylesheet by tailwind .md`

---

**Session completed:** 2025-11-25
**Branch:** `fix/apollo-strict-finalize-gh-202511251530`
