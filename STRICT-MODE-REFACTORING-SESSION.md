# Apollo Plugins - Strict Mode Template Refactoring
## Session: 2025-11-25 (Continuation)

### ✅ COMPLETED TASKS

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

## 3. Git Commits

### Commit 1: DJ Template Refactoring
```
3a01a22 refactor(dj-template): 100% design spec conformance with forced tooltips
```

### Commit 2: Private Profile Page
```
99c3f47 feat(private-profile): add /meu-perfil/ private dashboard page
```

---

## 📋 PENDING TASKS (For Next Session)

1. **Event Single Page (`/evento/{event-id}/`)**
   - Fix broken map/favorites layout
   - Add "Registros/Registrar" comment section
   - Strict tooltips on all placeholders

2. **Event Discover Page (`/eventos/`)**
   - Align CSS with uni.css
   - Add forced tooltips

3. **Social Feed (`/feed/` or `/fx/`)**
   - Add sidebar with widgets
   - Align with design spec

4. **Public User Page (`/id/{user-id}/`)**
   - Add Depoimentos (comments system relabeled)
   - Ensure widgets render correctly

5. **Register Missing Meta Keys**
   - `_dj_tagline`
   - `_dj_roles`
   - `_dj_bio_excerpt`
   - `_dj_track_title`
   - `_local_region`
   - `_local_capacity`

---

## 🔧 FLUSH REWRITE RULES

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
