# Apollo Plugins Frontend Migration Build Plan

**Workspace**
- `apollo-core/`
- `apollo-events-manager/`
- `apollo-rio/`

**Approved HTML sources (design truth)**
- `apollo-core/templates/design-library/approved templates/*.html`

---

## 1) Objectives

### 1.1 Single source of truth
- Apollo’s UI templates should live in **apollo-core**.
- Other Apollo plugins should render **core templates/partials** (or use core blocks/shortcodes) instead of duplicating markup.

### 1.2 Blank-canvas rendering
All Apollo public pages must behave like a “page builder blank canvas”:
- zero/near-zero theme CSS/JS influence
- Apollo-owned fonts + base variables + uni.css
- plugin scripts and WP core scripts still load safely (no broken forms, AJAX, maps).

### 1.3 Editable in Elementor (admin-friendly)
Provide a workflow where admins can:
- create a WP page using an **Apollo Canvas** template,
- insert Apollo components via **shortcodes** or **Gutenberg blocks** (Elementor can embed via Shortcode widget / Gutenberg widget),
- optionally: later add Elementor widgets (phase “optional”).

### 1.4 Safety
- No “big bang” swaps.
- Feature flags + page-by-page cutover.
- Rollback path always available.

---

## 2) Current state (what is already in your code)

### 2.1 apollo-events-manager already overrides templates
- `apollo-events-manager/apollo-events-manager.php` → `canvas_template()` (filter `template_include`) forces plugin templates for:
  - single `event_listing`, `event_dj`, `event_local`
  - `/eventos/` and archive `event_listing`
  - pages `event-dashboard`, `mod-events`, `cenario-new-event`, etc.
- It also has a **theme asset dequeuer** (`dequeue_theme_assets`) with allowlists.

### 2.2 apollo-rio already has a “theme isolation” mechanism
- `apollo-rio/includes/class-pwa-page-builders.php` has `block_theme_interference()` that:
  - removes theme content filters and
  - filters enqueued scripts/styles with allowlists.
- `apollo-rio/includes/template-functions.php` expects template partials under `apollo-rio/templates/partials/...`
  - In this workspace snapshot, `apollo-rio/templates/` is missing → PWA templates/partials are likely incomplete.

### 2.3 apollo-core has the start of a shared design system
- Shared partials exist: `apollo-core/templates/partials/` (assets, header-nav, hero-section, event-card, bottom-bar).
- A template loader exists: `apollo-core/src/Templates/class-apollo-template-loader.php`
  - BUT it references constants like `APOLLO_EVENTS_PATH` that are not defined in these repos → must be guarded or replaced.
- ViewModels exist, but:
  - `Apollo_ViewModel_Factory` does not handle contexts used by events-manager templates (`events_listing`, `dj_profile`, `venue`, `calendar_agenda`, etc.).
  - Several templates in events-manager call non-existent ViewModel methods (ex: `get_dj_profile_data()`).

**Immediate implication:** some “PHASE X migrated” templates in events-manager can be broken until ViewModels/factory are completed.

---

## 3) Target architecture (end-state)

### 3.1 Rendering pipeline
1. **Routing / template selection**
   - Keep overrides in `apollo-events-manager` (template_include) but the returned file becomes a **core template path** (core is the canonical template host).
2. **Blank canvas**
   - A shared **Canvas Mode** module in apollo-core handles:
     - allowlisted scripts/styles
     - optional removal of theme filters
     - stable hooks for other plugins to extend allowlist
3. **Data**
   - Page templates never read raw meta keys directly.
   - They call a ViewModel for the correct context (event single, events listing, DJ profile, venue, dashboards, etc.).
4. **UI composition**
   - Page templates are composed from:
     - “layout” (canvas shell)
     - shared partials (header, bottom bar, cards…)
     - component partials (tickets buttons, slider…) that can also be exposed as blocks/shortcodes.

### 3.2 Elementor strategy (pragmatic)
- Build **server-rendered components** as:
  - Shortcodes first (lowest friction, Elementor embeds easily).
  - Gutenberg dynamic blocks second (reusable and future-proof).
- Optional later: dedicated Elementor widgets.

---

## 4) Phased plan (with Levelpoints)

### Levelpoint 0 — Baseline and safety rails
**Exit criteria**
- You can run linting checks and identify template entrypoints without changing behavior.

**To-do**
1. Create a branch: `feature/templates-core-migration`.
2. Add a doc: `docs/build-plan.md` (this plan).
3. Inventory entrypoints:
   - Events Manager forced templates list (from `canvas_template()`).
   - Apollo Rio page templates list (from `class-pwa-page-builders.php`).
4. Create a “feature flag” approach:
   - Use constants:
     - `APOLLO_USE_CORE_TEMPLATES` (default false)
     - `APOLLO_CANVAS_STRICT_MODE` (default true for Apollo-owned templates; false for Elementor pages)
5. Add WP_DEBUG logging guards (do not spam):
   - a single helper `apollo_log_once( $key, $message )`.

**Copilot prompt**
> Scan `apollo-events-manager/apollo-events-manager.php` for all template routing in `canvas_template()` and list every forced template (slug + CPT). Create a markdown inventory in `docs/template-entrypoints.md`.

---

### Levelpoint 1 — Fix structural blockers (before any migration)
**Exit criteria**
- No fatals from missing constants / missing template folders.
- ViewModel factory can return something valid for every context used by existing templates.

#### 1A) Template loader safety
**To-do**
1. In `apollo-core/src/Templates/class-apollo-template-loader.php`:
   - replace direct use of undefined constants with `defined()` checks, e.g.
     - if `defined('APOLLO_CORE_PATH')` use it
     - if module constants are missing, skip them (don’t fatal)
   - add a filter: `apollo_template_paths` so other plugins can register template roots safely.
2. Add a helper in core: `Apollo_Module_Paths::get_paths()` that:
   - checks if specific plugins are active and returns their template directories.

**Copilot prompt**
> Update `Apollo_Template_Loader` so it never references undefined constants. Add `defined()` guards and a filter `apollo_template_paths` to allow external template roots. Ensure default root always includes `APOLLO_CORE_PATH . 'templates/'`.

#### 1B) ViewModel factory completion
**To-do**
1. Extend `apollo-core/src/ViewModels/class-apollo-viewmodel-factory.php`:
   - handle contexts used today:
     - `events_listing`
     - `single_event`
     - `dj_profile`
     - `venue`
     - `calendar_agenda`
     - `user_dashboard`
     - `dashboard_access`
2. Add ViewModel classes (minimum viable) under `apollo-core/src/ViewModels/`:
   - `class-apollo-events-listing-viewmodel.php`
   - `class-apollo-dj-viewmodel.php`
   - `class-apollo-venue-viewmodel.php`
   - `class-apollo-calendar-viewmodel.php` (if needed by `shortcode-cena-rio.php`)
3. Fix `Apollo_Event_ViewModel` to match Events Manager contract:
   - CPT: `event_listing`
   - Taxonomy names in events manager (ex: `event_listing_category`, `event_listing_tag`)
   - Meta keys used by events manager (`_event_start_date`, `_event_end_date`, `_event_date_display`, etc.)
4. Ensure each new ViewModel exposes the methods used by templates:
   - DJ: `get_dj_profile_data()`
   - Venue: `get_venue_profile_data()` (or whatever template calls)
   - Events listing: `get_events_portal_data()` (used by portal template)
   - Calendar: `get_calendar_data()`

**Copilot prompt**
> For each events-manager template that calls `Apollo_ViewModel_Factory::create_from_data(..., '<context>')`, implement the missing context handling in the factory and create the missing ViewModel classes/methods so templates do not fatal. Keep output keys identical to what templates expect.

#### 1C) Apollo Rio templates folder is missing
**To-do**
1. Create `apollo-rio/templates/` with minimal files required by:
   - `apollo-rio/includes/class-pwa-page-builders.php` (assigned templates)
   - `apollo-rio/includes/template-functions.php` (`apollo_get_header/footer` partials)
2. For now, make those template files “thin shells” that:
   - call `wp_head()` and `wp_footer()`
   - call `do_blocks()` / `the_content()` depending on strict mode
   - include core assets via `Apollo_Template_Loader->load_partial('assets')` when Apollo Core is active

**Copilot prompt**
> Create missing `apollo-rio/templates` and `apollo-rio/templates/partials` files expected by `template-functions.php`. Make them minimal: print `<html>`, `<head>` with `wp_head()`, include Apollo assets via core loader, then a `<main>` with `the_content()`, then `wp_footer()`.

---

### Levelpoint 2 — Create a shared “Canvas Mode” module (centralize theme isolation)
**Exit criteria**
- One reusable function/class controls asset allowlisting for ALL Apollo plugin pages.
- Events Manager and Apollo Rio can both call it.

**To-do**
1. Add in core: `apollo-core/src/Canvas/class-apollo-canvas-mode.php`
   - `Apollo_Canvas_Mode::enable( array $args )`
   - hooks:
     - `wp_enqueue_scripts` late: dequeue/deregister non-allowlisted assets
     - `style_loader_tag` and `script_loader_tag` (optional)
     - `body_class` add `apollo-canvas-mode` and `apollo-independent-page`
   - allowlists via filters:
     - `apollo_canvas_allowed_styles`
     - `apollo_canvas_allowed_scripts`
2. Update events-manager to call this instead of its local `dequeue_theme_assets` (keep old behavior behind a fallback flag):
   - when rendering forced templates → `Apollo_Canvas_Mode::enable(['strict' => true])`
3. Update apollo-rio to call this too (or align allowlists).

**Copilot prompt**
> Implement `Apollo_Canvas_Mode` in apollo-core and refactor events-manager’s dequeue logic to use it (feature-flagged). Preserve existing keep lists as defaults but move them to filters.

---

### Levelpoint 3 — Move page templates into apollo-core (controlled cutover)
**Exit criteria**
- Events Manager routes still work, but templates are served from core paths.
- No visual regressions for at least: `/eventos/`, single event, single DJ, single local.

**To-do**
1. Create a new folder in core:
   - `apollo-core/templates/pages/`
   - suggested structure:
     - `pages/events/portal-discover.php`
     - `pages/events/single-event_listing.php`
     - `pages/events/single-event_dj.php`
     - `pages/events/single-event_local.php`
     - `pages/dashboards/event-dashboard.php`
     - `pages/moderation/mod-events.php`
2. For each template, start by copying the existing events-manager template, then:
   - replace duplicated markup with core partials where available
   - replace direct helper usage with ViewModels
3. Update events-manager `canvas_template()` to return core templates **only if** `APOLLO_USE_CORE_TEMPLATES` is true:
   - else return existing plugin template path (rollback path)

**Copilot prompt**
> Add core templates under `apollo-core/templates/pages/events/` by porting the matching files from `apollo-events-manager/templates/`. Then update `Apollo_Events_Manager_Plugin::canvas_template()` to switch between local vs core templates based on `APOLLO_USE_CORE_TEMPLATES`.

---

### Levelpoint 4 — Convert approved HTML into reusable partial “Block Parts”
**Exit criteria**
- The approved HTML pages can be expressed as:
  - layout shell + partials
  - with 0 inline data and only ViewModel-fed data.

**To-do**
1. Create a mapping table (source HTML → target template + partials):

| Approved HTML file | Target core template | New partials likely needed |
|---|---|---|
| `body_eventos ----list all.html` | `pages/events/portal-discover.php` | filters bar, date chip, section title, modal shell |
| `body_evento_eventoID ----single page.html` | `pages/events/single-event_listing.php` | tickets CTA module, map module, gallery module |
| `eventos - dj - single.html` | `pages/events/single-event_dj.php` | dj hero, dj meta grid, dj music links |
| `eventos - local - single.html` | `pages/events/single-event_local.php` | local hero, address/meta, map module |
| `login - register.html` | `pages/auth/login-register.php` | auth form components |
| `forms.html` | `pages/forms/event-submit.php` | form fields partials, validation messages |
| `body_docs_editor.html` | decide target route | editor shell partials |

2. Create new partials in core:
   - `templates/partials/filters-bar.php`
   - `templates/partials/date-chip.php`
   - `templates/partials/tickets-cta.php`
   - `templates/partials/map-leaflet.php`
   - `templates/partials/gallery.php`
   - `templates/partials/modal-shell.php`
3. Keep CSS strategy stable during migration:
   - do not redesign; move inline CSS later.
   - short-term: allow some inline `<style>` per template if needed to match approved HTML.
4. Add “contract tests”:
   - output must include expected class names and data attributes used by JS.

**Copilot prompt**
> For each approved HTML file under `apollo-core/templates/design-library/approved templates/`, extract repeated sections into PHP partials in `apollo-core/templates/partials/`. Preserve class names exactly. Replace hardcoded content with `$args[...]` values only.

---

### Levelpoint 5 — Expose components as shortcodes + Gutenberg dynamic blocks
**Exit criteria**
- Admin can embed Apollo components in Elementor via shortcodes.
- Gutenberg users can insert the same components via blocks.

**To-do**
1. In core, add a `Blocks/` module:
   - `apollo-core/src/Blocks/`
   - register blocks with `block.json` + `render.php` (server render).
2. Build the initial component set (minimum):
   - Event slider (query-based)
   - Ticket buttons/CTA (meta-driven)
   - Event list (query-based)
   - Event card (already as partial; add a wrapper block)
3. Provide shortcodes as stable API (Elementor):
   - `[apollo_event_slider ...]`
   - `[apollo_ticket_buttons event_id="..."]`
   - `[apollo_event_list ...]`

**Example attributes to support (event slider / list)**
- `source="future|all|meta_query"`
- `category="slug"`
- `tag="slug"`
- `limit="12"`
- `orderby="_event_start_date"`
- `layout="card|list"`
- `cta="tickets|details|external"`

**Copilot prompt**
> Implement a server-rendered Gutenberg block `apollo/event-slider` and a matching shortcode `[apollo_event_slider]`. The render function must use the existing event query logic from `Apollo_Event_Data_Helper` and feed data through `Apollo_Event_ViewModel` before rendering `templates/partials/event-card.php`.

---

### Levelpoint 6 — Hardening, QA, and rollout
**Exit criteria**
- “Core templates” mode can be enabled in production with confidence.

**To-do**
1. Add WP-CLI smoke tests that reflect current plugins:
   - update `apollo-core/wp-cli/apollo-template-tests.php` to only test available templates (core + events + rio), and to test the new ViewModels/blocks.
2. Add “visual regression checklist”:
   - check `/eventos/`
   - check single event
   - check single DJ
   - check single local
   - check dashboard pages
3. Performance:
   - ensure heavy queries use cached IDs like `Apollo_Event_Data_Helper::get_cached_event_ids()`
   - ViewModels cache should include `post_modified` keys (already in factory).
4. Rollout plan:
   - enable `APOLLO_USE_CORE_TEMPLATES` on staging
   - then production
   - keep rollback by disabling the flag.

**Copilot prompt**
> Extend `wp apollo test-templates` to validate the new contexts (`events_listing`, `dj_profile`, `venue`) and ensure templates output includes `<!DOCTYPE html>`, `viewport`, and Apollo CDN asset markers.

---

## 5) “Stop line” for switching to Cursor (mass refactor)
Use Copilot for Levelpoints 0–3 (architecture, factory, canvas mode, routing).  
Switch to Cursor at Levelpoint 4 for **bulk HTML→PHP partial extraction** because it’s repetitive and multi-file.

**Cursor batch refactor loop (later)**
1. Open one approved HTML file (source).
2. Identify 2–5 components inside.
3. Generate partials + update one core page template.
4. Run lint + refresh page.
5. Repeat.

---

## 6) Minimal acceptance checklist (must pass before production)
- No PHP fatals on:
  - `/eventos/`
  - `single event_listing`
  - `single event_dj`
  - `single event_local`
- Theme CSS/JS does not leak (verify handles list actually being enforced).
- uni.css loads and wins priority.
- base.js (or your plugin JS) loads (confirm `wp_footer()` is present).
- Event meta keys display correct values (start date, venue, ticket link).
- AJAX endpoints still work (portal filters, modal load).
- Elementor page with Apollo Canvas template still renders content correctly (if strict mode is off for Elementor pages).

---

## 7) Notes on strict vs Elementor-safe canvas
- **Strict mode** (Apollo-owned templates): allowlist + theme filter removal.
- **Elementor-safe mode** (admin-built pages):
  - allowlist is still used
  - but DO NOT `remove_all_filters('the_content')` globally
  - keep Elementor frontend scripts if Elementor is active (handle names must be allowlisted via filter)

Suggested flag:
- `APOLLO_CANVAS_STRICT_MODE=true` for forced templates
- `APOLLO_CANVAS_STRICT_MODE=false` for WP Pages intended for Elementor editing
