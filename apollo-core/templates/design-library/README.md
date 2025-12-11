# Apollo Design Library · HTML Map

This document captures the structure and integration expectations for every HTML reference under `apollo-core/templates/design-library`. Use it as the source of truth before wiring WordPress templates, shortcodes, or React/Vue renderers to the live data coming from Apollo Core, Events Manager, Social, and Docs modules.

## How to work with these templates
- **Respect the app shell**: Nearly every layout expects the global `app-shell` (top nav, sidebar, bottom mobile nav) injected by the hosting plugin. Keep the markup returned here inside WordPress template parts so the shell can wrap it.
- **Split CSS/JS**: Inline assets exist only for reference. Real implementations must enqueue styles/scripts from their plugin (`apollo-core`, `apollo-events-manager`, `apollo-social`).
- **Replace fake data**: Avatars, stats, and text marked as sample copy should be swapped for dynamic fields. Each section below lists the WordPress data that needs to hydrate the view.
- **Re-use components**: Before duplicating markup, check `_components/` for cards, pills, modals, and action bars referenced by multiple templates.

## Template index (quick lookup)
| File | Surface | Primary purpose | Key data sources | Live target |
| --- | --- | --- | --- | --- |
| `body_eventos   ----list all.html` | Discover | Events archive grid + hero filters | `wp_query` on `apollo_event`, tax queries, hero WYSIWYG | `apollo-events-manager` discover screen |
| `body_eventos_add -----form template base.html` | Form wizard | Event creation flow w/ combos, modals | `apollo_events_form_state`, media uploads, custom endpoints | `/nova-festa` form (Phase 3 wiring) |
| `body_docs_editor.html` | Productivity | Apollo Word-esque editor | Gutenberg blocks, custom document CPT | Docs module editor UI |
| `body_evento_eventoID  ----single page.html` | Event detail | Single event hero + tickets + lineup | `the_post()`, meta (video, DJ list, tickets) | `single-apollo_event.php` |
| `body_dj_djID  ----single page.html` | Talent | DJ profile, SoundCloud embed | `apollo_talent` CPT, SoundCloud URLs | `single-dj.php` |
| `main_doc_sign  ----single page.html` | Workflow | Digital signature workspace | Doc status, signer list, CTA actions | `/docs/sign/{id}` |
| `main_groups  ----list all.html` | Communities | Groups directory cards | Community CPT, membership counts | `archives/community` |
| `main_groups_groupID ----single page.html` | Community detail | About, rules, members, post composer | Community meta + posts | `/communities/{slug}` |
| `layout_fornecedores  ----list all.html` | Suppliers | Marketplace with filters & stats | Supplier CPT, tax filters, stats widget | `cena-rio/fornecedores` |
| `body_space_localID  ----single page.html` | Venues | Venue hero, map, testimonials | Venue CPT, related events, reviews | `single-venue.php` |
| `body_login-register.html` | Auth | Secure terminal (login/register) | WP auth endpoints, quiz config | `apollo-social/templates/auth` |
| `main_explore ---social feed .html` | Social feed | Main timeline + cards + right rail | Feed API (`apollo_social_post`), user summary data | `/explore` feed |
| `main_docs  ----list all.html` | Docs hub | Document filters + list + stats sidebar | Docs CPT, signature stats, quick actions | `/docs` index |
| `main_cena-rio_agenda.html` | Calendar | Cross-view calendar + map + list | Aggregated events, Leaflet map data | `cena-rio/agenda` |

---

## Template details

### body_eventos   ----list all.html
- **Purpose**: Marketing-style discover page for events with hero CTA, filters (chips + selects), and event card grid.
- **Layout**: Hero (banner + copy + CTA), filters/search, featured cards, carousel banner, optional dark-mode toggle, bottom CTA.
- **Dynamic data**:
  - Hero: highlight event (image, title, stats) sourced from curated post or ACF group.
  - Filters: taxonomy lists (genre, bairro, data-range). Hook into `wp_dropdown_categories` or custom REST endpoint.
  - Cards: `WP_Query` for `apollo_event` w/ meta (date, location, price flag, status).
- **Special behaviors**: Dark-mode toggle script, date picker, horizontal scroll for chips.
- **Implementation notes**: Break hero, filters, and card loop into separate template parts for reuse across modules (Discover + `portal-discover`).

### body_eventos_add -----form template base.html
- **Purpose**: Full event creation wizard with uploader, combos (DJs, venues), schedules, modals.
- **Layout**: Progress header, multi-step form (title, media, description, schedule slider, audience, budget, modals for DJ/Venue pickers).
- **Dynamic data**:
  - Pre-fill from `apollo_event_draft` meta.
  - Combo lists from AJAX endpoints (search DJs, venues, suppliers).
  - File uploads to WordPress media via REST (`/wp/v2/media`).
- **JS hooks**: Multiple modals, step navigation, tag selectors, schedule slider. Move inline JS to `apollo-events-manager/assets/js/form-new-event.js` when wiring.
- **Implementation notes**: Keep form state in Redux-like store or hidden inputs; ensure WP nonces guard each AJAX action.

### body_docs_editor.html
- **Purpose**: In-browser editor similar to Google Docs with toolbar, workspace, autosave, export.
- **Layout**: App shell top, toolbar chips, canvas grid background, status panel.
- **Dynamic data**: Document title, editor content (likely `wp.data` or ProseMirror), autosave status, collaborator list.
- **Special behaviors**: Autosave indicator, PDF export button, toolbar toggles.
- **Implementation notes**: Replace static HTML with Gutenberg block editor (iframe) or custom React editor using WP REST for docs CPT.

### body_evento_eventoID  ----single page.html
- **Purpose**: Single event page with hero imagery, quick actions, RSVP avatars, lineup, map cards, tickets, bottom action bar.
- **Dynamic data**:
  - Hero: event background (video/image), status chips, CTA links.
  - RSVP avatars from attendees meta.
  - Lineup list from repeater (DJs w/ times + socials).
  - Venue slider pulls from `related_venues` taxonomy.
  - Tickets component uses inventory meta (prices, status, CTA routes).
- **Behaviors**: Sticky quick actions, share menu, bottom CTA for mobile.
- **Implementation notes**: Map to `single-apollo_event.php`; ensure meta fields exist in `apollo_events_manager` (ACF or custom tables).

### body_dj_djID  ----single page.html
- **Purpose**: Artist profile with hero vinyl, stats, SoundCloud embed, booking CTA, testimonials.
- **Dynamic data**: DJ bio, SoundCloud track URL, genres, socials, availability calendar, booking contact.
- **Implementation notes**: Use `apollo_talent` CPT; embed SoundCloud via WP oEmbed; connect “Adicionar ao line-up” to event builder modal.

### main_doc_sign  ----single page.html
- **Purpose**: Signing workspace for a single document (preview + signer checklist + action buttons).
- **Layout**: Two-column desktop (preview + panels); mobile stack with fixed CTA.
- **Dynamic data**: Document metadata (title, owner, status), signer list (avatars, status), timeline events, next action CTA.
- **Implementation notes**: Hook to Docs module endpoints for `sign_request`, integrate e-sign provider (WP REST). Replace placeholder timers with actual SLA data.

### main_groups  ----list all.html
- **Purpose**: Communities directory with filters, cards, membership stats.
- **Dynamic data**: `apollo_community` CPT list, tags (#pista, #seguranca), member counts, CTA states (joined/pending).
- **Implementation notes**: Filters map to taxonomy, cards link to `main_groups_groupID` detail.

### main_groups_groupID ----single page.html
- **Purpose**: Community detail page (hero, about, rules, admins, featured members, feed composer + posts).
- **Dynamic data**:
  - Hero: community meta (status, tags, cover image).
  - Rules list from repeater meta.
  - Admin list from user relationships.
  - Members chips from membership API.
  - Feed posts: WP loop for `apollo_social_post` filtered by community ID.
- **Behaviors**: Compose textarea, filter chips, like/comment buttons.
- **Implementation notes**: Break into partials: hero, sidebar cards, feed loop. Align with Social feed components for reuse.

### layout_fornecedores  ----list all.html
- **Purpose**: Supplier marketplace (search, category chips, cards, stats, app dropdowns, notifications).
- **Dynamic data**: Supplier CPT (logo, category, rating, tags), filters by `supplier_type`, nav menus.
- **Behaviors**: Filter chips, search, dynamic counts, dark-mode toggle, message/notification menus.
- **Implementation notes**: Requires JS for filtering/responsive nav; integrate with `cena-rio` module. Keep nav/app dropdowns in global shell.

### body_space_localID  ----single page.html
- **Purpose**: Venue single page with hero slider, social CTA, bio, map + route planner, upcoming events, testimonials.
- **Dynamic data**: Gallery images, venue metadata (address, socials), map coordinates, upcoming events query, reviews (perhaps `apollo_reviews`).
- **Behaviors**: Auto-advancing hero slider, route input opens Google Maps.
- **Implementation notes**: Use `venues` CPT; ensure slider accessible; reuse event cards from events archive.

### body_login-register.html
- **Purpose**: Security-heavy auth terminal with multiple states (normal/warning/danger/success) and aptitude quiz overlay.
- **Dynamic data**: WP login/register endpoints, attempt counters, quiz questions from options table.
- **Behaviors**: Animated scan line, state changes (e.g., lockout overlay), multi-step aptitude tests (pattern, Simon game, ethics quiz, reaction test).
- **Implementation notes**: Split into template parts per comment block; move scripts/styles to enqueued assets; wire up AJAX for quizzes/lockout timers.

### main_explore ---social feed .html
- **Purpose**: Global social feed (two-column layout with composer and right-rail summaries).
- **Dynamic data**: Feed items from Social API (post type, attachments), composer text, filters (events, communities, market), right-rail data (upcoming events, núcleos, communities, docs).
- **Behaviors**: Tabs filtering, like button persistence, dark-mode toggle, infinite scroll placeholder.
- **Implementation notes**: Feed cards live in `_components/`; ensure server filters align with chips; hydrate right-rail via aggregated endpoints.

### main_docs  ----list all.html
- **Purpose**: Document index with filter pills, document list rows, signature stats, quick actions.
- **Dynamic data**: Docs CPT list (type, status, event, updated_at, signatures count), stats (`created`, `signed`, `pending`), quick templates.
- **Implementation notes**: Document rows should open detail view or signing page; integrate filter chips with query vars, not front-end only.

### main_cena-rio_agenda.html
- **Purpose**: Advanced calendar mixing calendar grid, map, events list, mobile nav.
- **Dynamic data**: Aggregated events across modules; status-coded dots; Leaflet markers; events list grouped by day.
- **Behaviors**: Month navigation, date selection toggles, map recenter, add-event modal stub, localStorage fallback for sample data.
- **Implementation notes**: Replace localStorage with REST/GraphQL fetch; leverage `apollo_events_manager` endpoints; keep Leaflet initialization in dedicated JS file.

---

## Next steps for Phase 1
1. Convert each section into Confluence/VSC task checklist before wiring (Phase 2).
2. Extract reusable components (cards, hero, chips) into `_components/` partials with PHP includes.
3. Align naming conventions with plugin routes so designers, devs, and copy know which HTML maps to which PHP template.
