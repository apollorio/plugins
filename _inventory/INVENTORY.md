# Apollo Ecosystem Inventory

_Last generated: 2026-01-22_
_Last updated: 2026-01-22 (Complete audit of all plugins - 13 CPTs, 50+ REST routes, 40+ shortcodes)_

> **Scope:** All Apollo plugins (apollo-core, apollo-events-manager, apollo-social)
> **Status:** ✅ COMPLETE AND EXHAUSTIVE

## Centralized Identifiers Class

**Location:** `apollo-core/includes/class-apollo-identifiers.php`

Classe central que define TODAS as constantes de identificadores do ecossistema Apollo:

- CPT slugs, rewrite slugs, archive slugs
- Taxonomy slugs
- REST namespace e route constants
- Custom table base names
- Menu slugs, shortcode tags, asset handles (including legacy aliases)
- Option keys, meta prefixes
- Document meta keys (canonical and legacy)
- Canonical owners para resolver duplicatas
- Helper methods: `table()`, `rest_ns()`, `rest_route()`, `owner()`, `rest_route_exists()`

**Uso:**

```php
use Apollo_Core\Apollo_Identifiers as ID;

// CPT
register_post_type( ID::CPT_EVENT_LISTING, $args );

// Table with prefix
$wpdb->get_results( "SELECT * FROM " . ID::table( ID::TABLE_GROUPS ) );

// REST
register_rest_route( ID::rest_ns(), ID::REST_ROUTE_EVENTOS, $args );

// Document meta (canonical)
$signatures = get_post_meta( $doc_id, ID::META_DOC_SIGNATURES, true );

// Check canonical ownership
if ( ID::is_canonical_owner( 'event_listing', 'apollo-events-manager' ) ) { ... }

// Get owner of identifier
$owner = ID::owner( 'user_page' ); // Returns 'apollo-social'
```

**Loaded early by:** `apollo-core/apollo-core.php` (before other includes)

## 📊 Executive Summary

### By The Numbers

| Metric          | apollo-core | apollo-events-manager | apollo-social | **Total** |
| --------------- | ----------- | --------------------- | ------------- | --------- |
| CPTs            | 1           | 4                     | 8             | **13**    |
| Taxonomies      | 0           | 4                     | 4+            | **13+**   |
| REST Routes     | 8+          | 12+                   | 15+           | **50+**   |
| Shortcodes      | 13          | 19                    | 15+           | **40+**   |
| Admin Pages     | 11          | 10                    | 8+            | **30+**   |
| Custom Tables   | 15+         | 3                     | 2+            | **25+**   |
| Meta Keys       | 50+         | 20+                   | 15+           | **100+**  |
| Hooks (Actions) | 40+         | 20+                   | 15+           | **100+**  |
| Hooks (Filters) | 25+         | 12+                   | 10+           | **50+**   |
| Classes         | 50+         | 30+                   | 40+           | **150+**  |

### Recent Fixes (2026-01-22)

#### ✅ HIGH PRIORITY ISSUES RESOLVED

**1. CPT Duplication Fixed**

- **Issue:** `event_listing` registered by both apollo-core and apollo-events-manager
- **Solution:** apollo-core now acts as fallback only, checks if apollo-events-manager is active
- **Status:** ✅ RESOLVED - No conflicts, proper ownership hierarchy

**2. Menu Position Conflict Fixed**

- **Issue:** Both plugins used position 5
- **Solution:** Changed event_listing to position 6 in apollo-events-manager/includes/post-types.php:85
- **Status:** ✅ RESOLVED - event_listing now at position 6, event_dj at position 6

**3. Legacy Meta Keys Migration**

- **Issue:** Dual meta keys (\_event_djs vs \_event_dj_ids, \_event_local vs \_event_local_ids)
- **Solution:** Created automated migration script RUN-MIGRATION-FIX-LEGACY-META.php
- **Status:** ✅ RESOLVED - Run migration script to complete
- **File:** apollo-events-manager/RUN-MIGRATION-FIX-LEGACY-META.php
- **Usage:** Access via browser (admin only) or WP-CLI

**Migration Details:**

- `_event_djs` (old single/array) → `_event_dj_ids` (new array of ints)
- `_event_local` (old single int) → `_event_local_ids` (new single int)
- Old keys deleted after successful migration
- Migration flag set in options: `apollo_meta_migration_v2_completed`

### Architecture Overview

```
┌─────────────────────────────────────────────────┐
│        APOLLO ECOSYSTEM ARCHITECTURE v2.0       │
├─────────────────────────────────────────────────┤
│                                                 │
│  ┌──────────────┐ ┌─────────────┐ ┌──────────┐ │
│  │ apollo-core  │→→→ apollo-     │→→→ apollo- │ │
│  │   (Base)     │   │ events-    │   │ social │ │
│  └──────────────┘   │ manager    │   └──────────┘ │
│        ↓            │ (Events)   │        ↓      │
│   Utilities    └─────────────┘    Social Feat.  │
│   Identifiers      Event Mgmt      User Pages   │
│   Hooks            DJs, Locals     Classifieds  │
│   Security         Analytics       Verification│
│   Moderation       Tracking        Groups       │
│   Email            Import/Export   Documents    │
│   Communication                    Cena Rio     │
│                                                 │
└─────────────────────────────────────────────────┘
```

## Table of Contents

- [Executive Summary](#executive-summary)
- [Quick Index](#quick-index)
- [CPTs (Detailed)](#cpts-detailed)
- [Taxonomies](#taxonomies)
- [Meta Keys](#meta-keys)
- [REST API](#rest-api)
- [Shortcodes](#shortcodes)
- [Admin Menus & Pages](#admin-menus--pages)
- [Options & Settings](#options--settings)
- [Script & Style Handles](#script--style-handles)
- [Database Tables](#database-tables)
- [Classes & Namespaces](#classes--namespaces)
- [Hooks Index](#hooks-index)
- [AJAX Endpoints](#ajax-endpoints)
- [Collision Report](#collision-report)
- [Risk Assessment](#risk-assessment)
- [Reserved Identifiers](#reserved-identifiers)

---

## Quick Index

### CPT Slugs (alphabetical)

| slug                    | labels               | plugin                | public | rewrite       | defined at                                         |
| ----------------------- | -------------------- | --------------------- | ------ | ------------- | -------------------------------------------------- |
| `apollo_classified`     | Anúncios             | apollo-social         | true   | `anuncio`     | src/Modules/Classifieds/ClassifiedsModule.php:137  |
| `apollo_document`       | Documentos           | apollo-social         | false  | false         | src/Ajax/DocumentSaveHandler.php:155               |
| `apollo_email_template` | Email Templates      | apollo-core           | false  | false         | includes/class-apollo-email-templates-cpt.php:42   |
| `apollo_event_stat`     | Event Stats          | apollo-events-manager | false  | false         | includes/class-event-stat-cpt.php:55               |
| `apollo_home_section`   | Home Sections        | apollo-social         | false  | false         | src/Builder/class-apollo-home-cpt.php:96           |
| `apollo_social_post`    | Social Posts         | apollo-social         | true   | `post-social` | src/Infrastructure/PostTypes/SocialPostType.php:81 |
| `apollo_supplier`       | Fornecedores         | apollo-social         | false  | false         | src/Modules/Suppliers/SuppliersModule.php:171      |
| `cena_document`         | Documentos Cena Rio  | apollo-social         | false  | false         | src/CenaRio/CenaRioModule.php:88                   |
| `cena_event_plan`       | Eventos Planejamento | apollo-social         | false  | false         | src/CenaRio/CenaRioModule.php:115                  |
| `event_dj`              | DJs                  | apollo-events-manager | true   | `dj`          | includes/post-types.php:130                        |
| `event_listing`         | Eventos              | apollo-events-manager | true   | `evento`      | includes/post-types.php:89                         |
| `event_local`           | Locais               | apollo-events-manager | true   | `local`       | includes/post-types.php:171                        |
| `user_page`             | User Pages           | apollo-social         | true   | `user-page`   | src/Modules/UserPages/UserPageRegistrar.php:54     |

### Groups (Custom Tables, NOT CPT)

| type     | name                              | storage            | defined at                                          |
| -------- | --------------------------------- | ------------------ | --------------------------------------------------- |
| `comuna` | Comunidades (Público/Fórum-style) | `wp_apollo_groups` | apollo-social/src/Modules/Groups/GroupsModule.php:7 |
| `nucleo` | Núcleos (Privado/Intranet-style)  | `wp_apollo_groups` | apollo-social/src/Modules/Groups/GroupsModule.php:8 |

> **📝 GRUPOS APOLLO - SISTEMA UNIFICADO:**
>
> **Núcleos (nucleo):**
>
> - Label: "Núcleo" (singular), "Núcleos" (plural)
> - Tipo: Grupos privados de trabalho (estilo intranet)
> - Uso: Equipes profissionais, produtores, colegas de trabalho
> - Visibilidade: Privado (requer convite/aprovação)
> - Slug: `nucleo`
>
> **Comunidades (comuna):**
>
> - Label: "Comunidade" (singular), "Comunidades" (plural)
> - Tipo: Grupos públicos (estilo fórum)
> - Uso: Troca entre usuários, discussões abertas
> - Visibilidade: Público (livre para solicitar entrada)
> - Slug: `comuna`
>
> **⚠️ NÃO CONFUNDIR COM event_season:**
>
> - **Taxonomy `event_season`** (apollo-events-manager) → Categoriza EVENTOS por temporada (Verão'26, Carnival'26, RockInRio'26)
> - **Grupos (nucleo/comuna)** → Agrupamento SOCIAL de usuários e conteúdo
> - São sistemas SEPARADOS com propósitos diferentes

### Shortcode Tags (alphabetical)

| tag                          | callback                                           | defined at                                                |
| ---------------------------- | -------------------------------------------------- | --------------------------------------------------------- |
| `apollo_cena_mod_queue`      | Cena_Rio_Moderation::render_mod_queue              | includes/class-cena-rio-moderation.php:34                 |
| `apollo_cena_submit_event`   | Cena_Rio_Submissions::render_submission_form       | includes/class-cena-rio-submissions.php:35                |
| `apollo_event_card`          | Apollo_Home_Widgets_Loader::shortcode_event_card   | includes/widgets/class-apollo-home-widgets-loader.php:115 |
| `apollo_home_classifieds`    | Apollo_Home_Widgets_Loader::shortcode_classifieds  | includes/widgets/class-apollo-home-widgets-loader.php:110 |
| `apollo_home_events`         | Apollo_Home_Widgets_Loader::shortcode_events       | includes/widgets/class-apollo-home-widgets-loader.php:109 |
| `apollo_home_ferramentas`    | Apollo_Home_Widgets_Loader::shortcode_ferramentas  | includes/widgets/class-apollo-home-widgets-loader.php:112 |
| `apollo_home_hero`           | Apollo_Home_Widgets_Loader::shortcode_hero         | includes/widgets/class-apollo-home-widgets-loader.php:107 |
| `apollo_home_hub`            | Apollo_Home_Widgets_Loader::shortcode_hub          | includes/widgets/class-apollo-home-widgets-loader.php:111 |
| `apollo_home_manifesto`      | Apollo_Home_Widgets_Loader::shortcode_manifesto    | includes/widgets/class-apollo-home-widgets-loader.php:108 |
| `apollo_interesse_dashboard` | User_Dashboard_Interesse::render                   | includes/class-user-dashboard-interesse.php:11            |
| `apollo_newsletter`          | Apollo_Native_Newsletter::render_subscription_form | includes/class-apollo-native-newsletter.php:53            |
| `apollo_top_sounds`          | Interesse_Ranking::shortcode_top_sounds            | includes/class-interesse-ranking.php:17                   |
| `apollo_user_stats`          | User_Stats_Widget::render_shortcode                | includes/class-user-stats-widget.php:30                   |

### REST Namespaces & Routes

| namespace   | route                          | methods | defined at                                             |
| ----------- | ------------------------------ | ------- | ------------------------------------------------------ |
| `apollo/v1` | `/eventos`                     | GET     | modules/events/bootstrap.php:147                       |
| `apollo/v1` | `/evento/(?P<id>\d+)`          | GET     | modules/events/bootstrap.php:167                       |
| `apollo/v1` | `/eventos`                     | POST    | modules/events/bootstrap.php:183                       |
| `apollo/v1` | `/explore`                     | GET     | modules/social/bootstrap.php:172                       |
| `apollo/v1` | `/posts`                       | POST    | modules/social/bootstrap.php:188                       |
| `apollo/v1` | `/wow`                         | POST    | modules/social/bootstrap.php:204                       |
| `apollo/v1` | `/moderation/queue`            | GET     | modules/moderation/includes/class-rest-api.php:52      |
| `apollo/v1` | `/moderation/action`           | POST    | modules/moderation/includes/class-rest-api.php:72      |
| `apollo/v1` | `/moderation/stats`            | GET     | modules/moderation/includes/class-rest-api.php:92      |
| `apollo/v1` | `/moderation/bulk`             | POST    | modules/moderation/includes/class-rest-api.php:110     |
| `apollo/v1` | `/relationships`               | GET     | includes/class-apollo-relationship-rest.php:77         |
| `apollo/v1` | `/relationships`               | POST    | includes/class-apollo-relationship-rest.php:88         |
| `apollo/v1` | `/relationships/(?P<id>\d+)`   | GET     | includes/class-apollo-relationship-rest.php:133        |
| `apollo/v1` | `/relationships/(?P<id>\d+)`   | DELETE  | includes/class-apollo-relationship-rest.php:183        |
| `apollo/v1` | `/followers`                   | GET     | includes/class-apollo-relationship-rest.php:219        |
| `apollo/v1` | `/following`                   | GET     | includes/class-apollo-relationship-rest.php:233        |
| `apollo/v1` | `/navbar/apps`                 | GET     | includes/class-apollo-navbar-apps.php:790              |
| `apollo/v1` | `/cena/moderation/queue`       | GET     | includes/class-cena-rio-moderation.php:114             |
| `apollo/v1` | `/cena/moderation/action`      | POST    | includes/class-cena-rio-moderation.php:125             |
| `apollo/v1` | `/cena/moderation/stats`       | GET     | includes/class-cena-rio-moderation.php:146             |
| `apollo/v1` | `/cena/submit`                 | POST    | includes/class-cena-rio-submissions.php:49             |
| `apollo/v1` | `/cena/drafts`                 | GET     | includes/class-cena-rio-submissions.php:61             |
| `apollo/v1` | `/cena/submissions`            | GET     | includes/class-cena-rio-submissions.php:110            |
| `apollo/v1` | `/cena/submission/(?P<id>\d+)` | GET     | includes/class-cena-rio-submissions.php:127            |
| `apollo/v1` | `/push/subscribe`              | POST    | includes/class-apollo-native-push.php:175              |
| `apollo/v1` | `/push/unsubscribe`            | POST    | includes/class-apollo-native-push.php:185              |
| `apollo/v1` | `/push/test`                   | POST    | includes/class-apollo-native-push.php:195              |
| `apollo/v1` | `/newsletter/subscribe`        | POST    | includes/class-apollo-native-newsletter.php:727        |
| `apollo/v1` | `/newsletter/unsubscribe`      | POST    | includes/class-apollo-native-newsletter.php:737        |
| `apollo/v1` | `/quiz/start`                  | POST    | includes/quiz/rest.php:21                              |
| `apollo/v1` | `/quiz/answer`                 | POST    | includes/quiz/rest.php:54                              |
| `apollo/v1` | `/quiz/result`                 | GET     | includes/quiz/rest.php:79                              |
| `apollo/v1` | `/user/warnings`               | GET     | includes/class-apollo-user-moderation.php:930          |
| `apollo/v1` | `/user/ban`                    | POST    | includes/class-apollo-user-moderation.php:959          |
| `apollo/v1` | `/user/unban`                  | POST    | includes/class-apollo-user-moderation.php:983          |
| `apollo/v1` | `/user/warn`                   | POST    | includes/class-apollo-user-moderation.php:1007         |
| `apollo/v1` | `/integration/check`           | GET     | includes/class-apollo-cross-module-integration.php:431 |
| `apollo/v1` | `/integration/sync`            | POST    | includes/class-apollo-cross-module-integration.php:444 |
| `apollo/v1` | `/integration/status`          | GET     | includes/class-apollo-cross-module-integration.php:469 |
| `apollo/v1` | `/shortcodes`                  | GET     | includes/class-apollo-shortcode-registry.php:1194      |
| `apollo/v1` | `/shortcode/preview`           | POST    | includes/class-apollo-shortcode-registry.php:1204      |
| `apollo/v1` | `/moderation-queue`            | GET     | includes/class-moderation-queue-unified.php:77         |
| `apollo/v1` | `/moderation-queue/action`     | POST    | includes/class-moderation-queue-unified.php:97         |

### AJAX Endpoints (admin-ajax.php)

| Action                 | Methods | Auth   | Defined at                         |
| ---------------------- | ------- | ------ | ---------------------------------- |
| `apollo_navbar_login`  | POST    | nopriv | includes/ajax-login-handler.php:22 |
| `apollo_navbar_logout` | POST    | priv   | includes/ajax-login-handler.php:99 |
| `apollo_quiz_log`      | POST    | nopriv | includes/quiz-tracker.php:186      |

---

## CPTs

### `event_listing` (Eventos)

**Plugin:** apollo-events-manager
**Rewrite slug:** `evento`
**Archive:** `eventos`
**REST base:** `events`

**Args:** public=true, show_in_rest=true, has_archive=true, capability_type=post
**Supports:** title, editor, thumbnail, custom-fields, excerpt, author, revisions
**Menu icon:** dashicons-calendar-alt

**Taxonomies:**

- `event_listing_category` (hierarchical) → slug `categoria-evento`
- `event_listing_type` (hierarchical) → slug `tipo-evento`
- `event_listing_tag` (non-hierarchical) → slug `tag-evento`
- `event_sounds` (hierarchical) → slug `som`
- `event_season` (hierarchical) → slug `temporada`

**Meta keys:** `_event_start_date`, `_event_end_date`, `_event_venue`, `_ticket_url`, `_apollo_coupon`

**Defined at:** `apollo-events-manager/includes/post-types.php:89`

---

### `event_dj` (DJs / Artistas)

**Plugin:** apollo-events-manager
**Rewrite slug:** `dj`
**REST base:** `djs`

**Args:** public=true, show_in_rest=true, has_archive=true, capability_type=post
**Supports:** title, editor, thumbnail, custom-fields
**Menu icon:** dashicons-admin-users

**Meta keys:** `_dj_soundcloud`, `_dj_instagram`, `_dj_spotify`, `_dj_bio`, `_dj_press_kit`

**Defined at:** `apollo-events-manager/includes/post-types.php:130`

---

### `event_local` (Locais / Venues)

**Plugin:** apollo-events-manager
**Rewrite slug:** `local`
**REST base:** `locals`

**Args:** public=true, show_in_rest=true, has_archive=true, capability_type=post
**Supports:** title, editor, thumbnail, custom-fields
**Menu icon:** dashicons-location

**Meta keys:** `_local_address`, `_local_lat`, `_local_lng`, `_local_capacity`, `_local_neighborhood`

**Defined at:** `apollo-events-manager/includes/post-types.php:171`

---

### `apollo_classified` (Anúncios / Classificados)

**Plugin:** apollo-social
**Rewrite slug:** `anuncio`
**Archive:** `anuncios`

**Args:** public=true, show_in_rest=true, has_archive=true, capability_type=post
**Supports:** title, editor, author, thumbnail, excerpt, custom-fields
**Menu icon:** dashicons-megaphone

**Taxonomies:**

- `classified_domain` → slug `tipo` (ingressos, acomodação)
- `classified_intent` → slug `intencao` (ofereço, procuro)

**Meta keys:**

- `_classified_price`
- `_classified_currency`
- `_classified_location_text`
- `_classified_contact_pref`
- `_classified_event_date`
- `_classified_event_title`
- `_classified_start_date`
- `_classified_end_date`
- `_classified_capacity`
- `_classified_gallery`
- `_classified_views`
- `_classified_safety_acknowledged`
- `_classified_season_id`

**Defined at:** `apollo-social/src/Modules/Classifieds/ClassifiedsModule.php:137`

---

### `apollo_supplier` (Fornecedores)

**Plugin:** apollo-social
**Public:** false (admin-only, acessível via templates customizados)

**Args:** public=false, publicly_queryable=false, show_ui=true, rewrite=false
**Supports:** title, editor, thumbnail
**Menu icon:** dashicons-store

**Taxonomies:**

- `apollo_supplier_category` → Categorias
- `apollo_supplier_region` → Região
- `apollo_supplier_neighborhood` → Bairro
- `apollo_supplier_event_type` → Tipo de Evento
- `apollo_supplier_type` → Tipo de Fornecedor
- `apollo_supplier_mode` → Modo
- `apollo_supplier_badge` → Badges

**Meta prefix:** `_apollo_supplier_`

**Defined at:** `apollo-social/src/Modules/Suppliers/SuppliersModule.php:171`

---

### `apollo_document` (Documentos)

**Plugin:** apollo-social
**Public:** false (UI only)

**Args:** public=false, show_ui=true, capability_type=post
**Supports:** title, editor, author, revisions
**Menu icon:** dashicons-media-document

**Meta keys:**

- `_apollo_document_delta` (Quill delta JSON)
- `_apollo_document_html`
- `_apollo_document_status`
- `_apollo_document_type`
- `_apollo_document_version`
- `_apollo_document_signatures`

**Defined at:** `apollo-social/src/Ajax/DocumentSaveHandler.php:155`

---

### `cena_document` (Documentos Cena Rio)

**Plugin:** apollo-social (CenaRio module)
**Public:** false

**Args:** public=false, show_ui=true, capability_type=post
**Supports:** title, editor, author, revisions
**Menu icon:** dashicons-analytics

**Meta keys:** `_cena_is_library`

**Defined at:** `apollo-social/src/CenaRio/CenaRioModule.php:88`

---

### `cena_event_plan` (Eventos em Planejamento)

**Plugin:** apollo-social (CenaRio module)
**Public:** false

**Args:** public=false, show_ui=true, capability_type=post
**Supports:** title, editor, author
**Menu icon:** dashicons-calendar-alt

**Meta keys:** `_cena_plan_date`

**Defined at:** `apollo-social/src/CenaRio/CenaRioModule.php:115`

---

### `apollo_social_post` (Social Posts)

**Plugin:** apollo-social
**Rewrite slug:** `post-social`

**Args:** public=true, show_in_rest=true, has_archive=false, capability_type=post
**Supports:** title, editor, author, thumbnail, comments, custom-fields
**Menu icon:** dashicons-format-status

**Defined at:** `apollo-social/src/Infrastructure/PostTypes/SocialPostType.php:81`

---

### `user_page` (User Pages / HUB)

**Plugin:** apollo-social
**Rewrite slug:** `user-page`

**Args:** public=true, publicly_queryable=true, show_in_rest=false, has_archive=false
**Supports:** title, editor, thumbnail, custom-fields, revisions
**Menu icon:** dashicons-admin-users

**Custom Rewrite Rules:**

- `/user-page/{slug}` → Acesso via slug do post
- `/id/{user_id}` → Acesso via ID do usuário (query var: `apollo_user_page_owner`)
- `/meu-perfil/` → Dashboard privado do usuário logado

**Meta key:** `_apollo_user_id`

**Defined at:** `apollo-social/src/Modules/UserPages/UserPageRegistrar.php:54`

---

### `apollo_email_template` (Email Templates)

**Plugin:** apollo-core
**Public:** false

**Args:** public=false, show_ui=true, capability_type=post
**Supports:** title, editor

**Defined at:** `apollo-core/includes/class-apollo-email-templates-cpt.php:42`

---

### `apollo_event_stat` (Event Stats)

**Plugin:** apollo-events-manager
**Public:** false

**Args:** public=false, show_ui=true

**Defined at:** `apollo-events-manager/includes/class-event-stat-cpt.php:55`

---

### `apollo_home_section` (Home Sections)

**Plugin:** apollo-social (Builder)
**Public:** false

**Defined at:** `apollo-social/src/Builder/class-apollo-home-cpt.php:96`

---

## Groups (Custom Tables, NOT CPT)

Comuna e Nucleo são armazenados em tabelas customizadas, não como WordPress CPT.

**Plugin:** apollo-social
**Module:** `apollo-social/src/Modules/Groups/GroupsModule.php`

### Tipos de Grupos (Sistema Unificado Apollo)

| Type     | Constant    | Label (PT-BR)          | Description                                               |
| -------- | ----------- | ---------------------- | --------------------------------------------------------- |
| `comuna` | TYPE_COMUNA | Comunidade/Comunidades | Grupos públicos / comunidades (fórum-style, acesso livre) |
| `nucleo` | TYPE_NUCLEO | Núcleo/Núcleos         | Grupos privados / equipes de trabalho (intranet-style)    |

**IMPORTANTE:**

- **Núcleos** = Private work teams para produtores, colegas profissionais
- **Comunidades** = Public groups para troca entre usuários como fórum

**Defined at:** `apollo-social/src/Modules/Groups/GroupsModule.php:7-8`

---

## Taxonomies

### Event Taxonomies (apollo-events-manager)

| Taxonomy                 | Post Type     | Hierarchical | Slug             | Defined at                                        |
| ------------------------ | ------------- | ------------ | ---------------- | ------------------------------------------------- |
| `event_listing_category` | event_listing | ✓            | categoria-evento | apollo-events-manager/includes/post-types.php:212 |
| `event_listing_type`     | event_listing | ✓            | tipo-evento      | apollo-events-manager/includes/post-types.php:245 |
| `event_listing_tag`      | event_listing | ✗            | tag-evento       | apollo-events-manager/includes/post-types.php:278 |
| `event_sounds`           | event_listing | ✓            | som              | apollo-events-manager/includes/post-types.php:311 |
| `event_season`           | event_listing | ✓            | temporada        | apollo-events-manager/includes/post-types.php:344 |

### Classified Taxonomies (apollo-social)

| Taxonomy            | Post Type         | Hierarchical | Slug     | Defined at                                                      |
| ------------------- | ----------------- | ------------ | -------- | --------------------------------------------------------------- |
| `classified_domain` | apollo_classified | ✓            | tipo     | apollo-social/src/Modules/Classifieds/ClassifiedsModule.php:165 |
| `classified_intent` | apollo_classified | ✓            | intencao | apollo-social/src/Modules/Classifieds/ClassifiedsModule.php:192 |

### Supplier Taxonomies (apollo-social)

| Taxonomy                       | Post Type       | Hierarchical | Slug                   | Defined at                                                                   |
| ------------------------------ | --------------- | ------------ | ---------------------- | ---------------------------------------------------------------------------- |
| `apollo_supplier_category`     | apollo_supplier | ✓            | categoria-fornecedor   | apollo-social/src/Infrastructure/Persistence/WPPostSupplierRepository.php:36 |
| `apollo_supplier_region`       | apollo_supplier | ✓            | regiao-fornecedor      | apollo-social/src/Infrastructure/Persistence/WPPostSupplierRepository.php:41 |
| `apollo_supplier_neighborhood` | apollo_supplier | ✓            | bairro-fornecedor      | apollo-social/src/Infrastructure/Persistence/WPPostSupplierRepository.php:46 |
| `apollo_supplier_event_type`   | apollo_supplier | ✓            | tipo-evento-fornecedor | apollo-social/src/Infrastructure/Persistence/WPPostSupplierRepository.php:51 |
| `apollo_supplier_type`         | apollo_supplier | ✓            | tipo-fornecedor        | apollo-social/src/Infrastructure/Persistence/WPPostSupplierRepository.php:56 |
| `apollo_supplier_mode`         | apollo_supplier | ✓            | modo-fornecedor        | apollo-social/src/Infrastructure/Persistence/WPPostSupplierRepository.php:61 |
| `apollo_supplier_badge`        | apollo_supplier | ✓            | badge-fornecedor       | apollo-social/src/Infrastructure/Persistence/WPPostSupplierRepository.php:66 |

**Registry Class:** `apollo-core/includes/class-apollo-taxonomy-registry.php:399`
**Helper Function:** `apollo_register_taxonomy()` at `includes/class-apollo-taxonomy-registry.php:680`

---

## Database Tables

Tabelas customizadas criadas pelos plugins Apollo (prefixo: `wp_`):

### apollo-social Tables

| Table                         | Purpose                  | Defined at                                                                   |
| ----------------------------- | ------------------------ | ---------------------------------------------------------------------------- |
| `apollo_groups`               | Grupos (comuna/nucleo)   | apollo-social/src/Modules/Groups/GroupsModule.php:45                         |
| `apollo_group_members`        | Membros dos grupos       | apollo-social/src/Modules/Groups/GroupsModule.php:52                         |
| `apollo_documents`            | Biblioteca de documentos | apollo-social/src/Modules/Documents/DocumentLibraries.php:108                |
| `apollo_document_permissions` | Permissões de docs       | apollo-social/src/Modules/Documents/DocumentLibraries.php:139                |
| `apollo_signatures`           | Assinaturas digitais     | apollo-social/src/Modules/Signatures/Services/SignaturesService.php:74       |
| `apollo_signature_templates`  | Templates de assinatura  | apollo-social/src/Modules/Signatures/Repositories/TemplatesRepository.php:53 |
| `apollo_signature_audit`      | Log de auditoria         | apollo-social/src/Modules/Signatures/AuditLog.php:60                         |
| `apollo_signature_protocols`  | Protocolos de assinatura | apollo-social/src/Modules/Signatures/AuditLog.php:86                         |
| `apollo_subscriptions`        | Assinaturas de usuários  | apollo-social/src/Modules/Subscriptions/SubscriptionsSchema.php:25           |
| `apollo_subscription_orders`  | Pedidos de assinatura    | apollo-social/src/Modules/Subscriptions/SubscriptionsSchema.php:51           |
| `apollo_subscription_plans`   | Planos de assinatura     | apollo-social/src/Modules/Subscriptions/SubscriptionsSchema.php:81           |
| `apollo_media_uploads`        | Upload de mídia          | apollo-social/src/Modules/Media/MediaSchema.php:23                           |
| `apollo_likes`                | Curtidas (wow)           | apollo-social/src/Modules/Likes/LikesSchema.php:97                           |
| `apollo_forums`               | Fóruns                   | apollo-social/src/Modules/Forums/ForumsSchema.php:25                         |
| `apollo_forum_topics`         | Tópicos do fórum         | apollo-social/src/Modules/Forums/ForumsSchema.php:50                         |
| `apollo_forum_replies`        | Respostas do fórum       | apollo-social/src/Modules/Forums/ForumsSchema.php:76                         |

### apollo-core Tables

| Table                  | Purpose                  | Defined at                                  |
| ---------------------- | ------------------------ | ------------------------------------------- |
| `apollo_activity`      | Log de atividade         | includes/class-apollo-activity-log.php      |
| `apollo_newsletter`    | Assinantes newsletter    | includes/class-apollo-native-newsletter.php |
| `apollo_push_tokens`   | Tokens push notification | includes/class-apollo-native-push.php       |
| `apollo_quiz_attempts` | Quiz de registro         | includes/quiz-tracker.php:25                |

---

## Custom Auth Routes

Rotas customizadas de autenticação (apollo-core):

| Route       | Template                    | Purpose              | Defined at                  |
| ----------- | --------------------------- | -------------------- | --------------------------- |
| `/entre`    | templates/auth/entre.php    | Página de login      | includes/auth-routes.php:17 |
| `/login`    | templates/auth/entre.php    | Alias para /entre    | includes/auth-routes.php:18 |
| `/registre` | templates/auth/registre.php | Registro com quiz    | includes/auth-routes.php:21 |
| `/register` | templates/auth/registre.php | Alias para /registre | includes/auth-routes.php:22 |

### WP-Login Redirect

O arquivo `includes/hide-wp-login.php` redireciona:

- `/wp-login.php` → `/entre` (exceto logout, lostpassword)
- `/wp-admin` (não logado) → `/entre`
- `?action=register` → `/registre`

---

## Collision Report

### ⚠️ Potential Slug Conflicts

| Slug          | Used By                    | Route Type                                        | Notes                               |
| ------------- | -------------------------- | ------------------------------------------------- | ----------------------------------- |
| `evento`      | event_listing rewrite      | **SINGLE** `/evento/{id}`                         | ⚠️ Requer `{id}`, senão erro 404    |
| `eventos`     | event_listing archive      | **ARCHIVE** `/eventos`                            | Lista todos os eventos              |
| `anuncio`     | apollo_classified rewrite  | **SINGLE** `/anuncio/{id}`                        | ⚠️ Requer `{id}`, senão erro 404    |
| `anuncios`    | apollo_classified archive  | **ARCHIVE** `/anuncios`                           | Lista todos os classificados        |
| `dj`          | event_dj rewrite           | **SINGLE** `/dj/{id}`                             | ⚠️ Requer `{id}`, página do artista |
| `local`       | event_local rewrite        | **SINGLE** `/local/{id}`                          | ⚠️ Requer `{id}`, página do venue   |
| `user-page`   | user_page rewrite          | **SINGLE** `/user-page/{slug}` ou `/id/{user_id}` | ⚠️ Duas rotas: por slug ou por ID   |
| `post-social` | apollo_social_post rewrite | **SINGLE** `/post-social/{id}`                    | ⚠️ Requer `{id}`                    |
| `entre`       | apollo_auth query_var      | **AUTH** `/entre`                                 | ✅ Página de login customizada      |
| `login`       | apollo_auth query_var      | **AUTH** `/login` (alias)                         | ✅ Alias para /entre                |
| `registre`    | apollo_auth query_var      | **AUTH** `/registre`                              | ✅ Registro com quiz                |
| `register`    | apollo_auth query_var      | **AUTH** `/register` (alias)                      | ✅ Alias para /registre             |

> **Note:** `apollo_supplier` não tem rewrite público (public=false). Acesso via templates customizados.

### ✅ Duplicate CPT Registration (RESOLVED)

CPTs com registro duplicado possuem guards `post_type_exists()` para evitar conflitos.

| CPT             | Canonical Owner                         | Fallback            | Status                                            |
| --------------- | --------------------------------------- | ------------------- | ------------------------------------------------- |
| `event_listing` | apollo-events-manager/post-types.php:89 | apollo-core/modules | ✅ RESOLVIDO - guard `is_events_manager_active()` |
| `user_page`     | apollo-social/UserPageRegistrar.php:54  | apollo-core/modules | ✅ RESOLVIDO - guard `is_apollo_social_active()`  |

> **Guard Pattern:** `apollo-core` modules verificam se o plugin companion está ativo antes de registrar CPTs/rotas fallback.

### ✅ REST Route Collisions (RESOLVED)

Todos usam namespace `apollo/v1`. Colisões foram resolvidas com guards.

| Route             | Canonical Owner       | Fallback    | Status                              |
| ----------------- | --------------------- | ----------- | ----------------------------------- |
| `/eventos`        | apollo-events-manager | apollo-core | ✅ RESOLVIDO - guard em apollo-core |
| `/evento/{id}`    | apollo-events-manager | -           | ✅ OK - single                      |
| `/classifieds`    | apollo-social         | -           | ✅ OK                               |
| `/anuncio/{id}`   | apollo-social         | -           | ✅ OK - single                      |
| `/groups`         | apollo-social         | -           | ✅ OK                               |
| `/user-page/{id}` | apollo-social         | -           | ✅ OK - single                      |

> **Guard Implementado:** `apollo-core/modules/events/bootstrap.php:145` verifica `is_events_manager_active()` antes de registrar rotas REST.

> **Importante:** Rotas de single page (`/evento/{id}`, `/anuncio/{id}`, etc.) **sempre** requerem `{id}`. Acesso sem ID retorna 404.

## Meta Keys

### Event Meta Keys

| key                 | purpose               | defined/used at              |
| ------------------- | --------------------- | ---------------------------- |
| `_event_start_date` | Event start date/time | modules/events/bootstrap.php |
| `_event_end_date`   | Event end date/time   | modules/events/bootstrap.php |
| `_event_venue`      | Event venue/local ID  | modules/events/bootstrap.php |
| `_ticket_url`       | External ticket URL   | modules/events/bootstrap.php |
| `_apollo_coupon`    | Apollo coupon code    | modules/events/bootstrap.php |

### User Meta Keys

| key                 | purpose                  | defined/used at                           |
| ------------------- | ------------------------ | ----------------------------------------- |
| `apollo_membership` | User membership level    | includes/class-apollo-memberships.php     |
| `apollo_warnings`   | User moderation warnings | includes/class-apollo-user-moderation.php |
| `apollo_banned`     | User ban status          | includes/class-apollo-user-moderation.php |

---

## Options & Settings

| option_name                     | purpose                       | defined at                                       |
| ------------------------------- | ----------------------------- | ------------------------------------------------ |
| `apollo_core_migration_version` | Database migration version    | includes/class-apollo-core.php:150               |
| `apollo_db_version`             | Database schema version       | tests/test-full-integration.php:840              |
| `apollo_email_flows`            | Email automation flows config | includes/class-apollo-email-admin-ui.php:73      |
| `apollo_email_templates`        | Email templates config        | includes/class-apollo-email-integration.php:1002 |
| `apollo_form_schemas`           | Form field schemas            | tests/test-form-schema.php:205                   |
| `apollo_form_schema_version`    | Form schema version           | tests/test-form-schema.php:211                   |
| `apollo_home_page_id`           | Home page post ID             | includes/class-apollo-home-page-builder.php      |
| `apollo_limits`                 | Rate limits config            | includes/class-apollo-modules-config.php:290     |
| `apollo_memberships`            | Membership tiers config       | wp-cli/memberships.php:109                       |
| `apollo_memberships_version`    | Memberships version           | wp-cli/memberships.php:52                        |
| `apollo_mod_settings`           | Moderation settings           | includes/class-apollo-audit-log.php:35           |
| `apollo_modules`                | Active modules config         | includes/class-apollo-modules-config.php:120     |
| `apollo_slow_queries`           | Slow query log                | includes/class-apollo-db-query-optimizer.php:270 |

---

## Admin Menus & Pages

| menu_slug               | parent                | title                | defined at                                                 |
| ----------------------- | --------------------- | -------------------- | ---------------------------------------------------------- |
| `apollo-control`        | -                     | Apollo Control Panel | admin/class-apollo-unified-control-panel.php:44            |
| `apollo-moderation`     | `apollo-control`      | Moderation           | modules/moderation/includes/class-admin-ui.php:37          |
| `apollo-analytics`      | `apollo-control`      | Analytics            | includes/class-apollo-analytics.php:825                    |
| `apollo-cookie-consent` | `options-general.php` | Cookie Consent       | includes/class-apollo-cookie-consent.php:77                |
| `apollo-email-settings` | `apollo-control`      | Email Settings       | includes/class-apollo-email-admin-ui.php:37                |
| `apollo-email-flows`    | `apollo-control`      | Email Flows          | includes/class-apollo-email-integration.php:206            |
| `apollo-newsletter`     | `apollo-control`      | Newsletter           | includes/class-apollo-native-newsletter.php:1007           |
| `apollo-push`           | `apollo-control`      | Push Notifications   | includes/class-apollo-native-push.php:640                  |
| `apollo-seo`            | `apollo-control`      | SEO                  | includes/class-apollo-native-seo.php:1037                  |
| `apollo-navbar-apps`    | `apollo-control`      | Navbar Apps          | includes/class-apollo-navbar-apps.php:189                  |
| `apollo-shortcodes`     | `apollo-control`      | Shortcodes           | includes/class-apollo-shortcode-registry.php:1113          |
| `apollo-snippets`       | `apollo-control`      | Snippets             | includes/class-apollo-snippets-manager.php:82              |
| `apollo-cdn-monitor`    | `apollo-control`      | CDN Monitor          | includes/class-cdn-performance-monitor.php:338             |
| `apollo-template-cache` | `apollo-control`      | Template Cache       | includes/class-template-cache-manager.php:310              |
| `apollo-communication`  | -                     | Communication        | includes/communication/class-communication-manager.php:327 |
| `apollo-strict-mode`    | `apollo-control`      | i18n Strict Mode     | src/I18n/ApolloStrictModeI18n.php:775                      |
| `apollo-smoke-tests`    | `tools.php`           | Smoke Tests          | tests/apollo-smoke-tests.php:27                            |

---

### Communication Admin Menu (Unified System)

**Main Menu:**

- **Slug:** `apollo-communication`
- **Title:** Communication
- **Icon:** `dashicons-email-alt`
- **Position:** 30 (main menu level)
- **File:** `apollo-core/includes/communication/class-communication-manager.php:327`

**Submenus:**

1. **Email Settings** (`apollo-communication-email`)
   - Manage email templates and settings
   - File: `communication/email/class-email-manager.php`

2. **Notifications** (`apollo-communication-notifications`)
   - Configure notification preferences
   - File: `communication/notifications/class-notification-manager.php`

3. **Forms** (`apollo-communication-forms`)
   - Manage form schemas and submissions
   - File: `communication/forms/class-form-manager.php`

---

## Icon Changes (2026-01-22)

| Menu                                      | Old Icon                 | New Icon                 | Reason                            |
| ----------------------------------------- | ------------------------ | ------------------------ | --------------------------------- |
| Documentos Cena Rio (cena_document)       | `dashicons-analytics`    | `dashicons-calendar-alt` | Unified event-related icons       |
| Eventos em Planejamento (cena_event_plan) | `dashicons-calendar-alt` | `dashicons-analytics`    | Cena Rio now uses analytics icon  |
| Event Listing (event_listing)             | `dashicons-calendar-alt` | `dashicons-analytics`    | Apollo Events uses analytics icon |

---

## Script & Style Handles

### Styles

| handle              | source                                        | defined at                           |
| ------------------- | --------------------------------------------- | ------------------------------------ |
| `apollo-uni-css`    | https://assets.apollo.rio.br/styles/index.css | includes/class-apollo-assets.php:378 |
| `apollo-compat-css` | core/compat.css                               | includes/class-apollo-assets.php:381 |
| `remixicon`         | vendor/remixicon/remixicon.css                | includes/class-apollo-assets.php:383 |
| `apollo-remixicon`  | vendor/remixicon/remixicon.css                | includes/class-apollo-assets.php:384 |
| `leaflet`           | vendor/leaflet/leaflet.css                    | includes/class-apollo-assets.php:391 |
| `datatables-css`    | vendor/datatables/jquery.dataTables.min.css   | includes/class-apollo-assets.php:394 |

### Scripts

| handle           | source                                     | defined at                           |
| ---------------- | ------------------------------------------ | ------------------------------------ |
| `apollo-base-js` | core/base.js                               | includes/class-apollo-assets.php:385 |
| `apollo-motion`  | vendor/motion/motion.min.js                | includes/class-apollo-assets.php:386 |
| `framer-motion`  | vendor/motion/motion.min.js                | includes/class-apollo-assets.php:387 |
| `apollo-chartjs` | vendor/chartjs/chart.umd.min.js            | includes/class-apollo-assets.php:388 |
| `chartjs`        | vendor/chartjs/chart.umd.min.js            | includes/class-apollo-assets.php:389 |
| `chart-js`       | vendor/chartjs/chart.umd.min.js            | includes/class-apollo-assets.php:390 |
| `leaflet`        | vendor/leaflet/leaflet.js                  | includes/class-apollo-assets.php:392 |
| `datatables-js`  | vendor/datatables/jquery.dataTables.min.js | includes/class-apollo-assets.php:393 |

---

## Classes & Namespaces

### Primary Namespaces

| namespace                     | purpose               | files                                      |
| ----------------------------- | --------------------- | ------------------------------------------ |
| `Apollo_Core`                 | Main plugin namespace | includes/\*.php                            |
| `Apollo_Core\API`             | REST API responses    | includes/class-api-response.php            |
| `Apollo_Core\AJAX`            | AJAX handlers         | includes/class-apollo-ajax-handler.php     |
| `Apollo_Core\Tests`           | PHPUnit tests         | tests/\*.php                               |
| `Apollo\Core`                 | Core utilities        | includes/class-apollo-alignment-bridge.php |
| `Apollo\Communication\Traits` | Communication traits  | communication/traits/\*.php                |

### Key Classes

| class                      | purpose             | defined at                                  |
| -------------------------- | ------------------- | ------------------------------------------- |
| `Apollo_Core`              | Main plugin class   | includes/class-apollo-core.php              |
| `Apollo_Activation`        | Activation handler  | includes/class-activation.php               |
| `Apollo_Assets`            | Asset management    | includes/class-apollo-assets.php            |
| `Apollo_CPT_Registry`      | CPT registry        | includes/class-apollo-cpt-registry.php      |
| `Apollo_Taxonomy_Registry` | Taxonomy registry   | includes/class-apollo-taxonomy-registry.php |
| `Apollo_Home_Page_Builder` | Home page builder   | includes/class-apollo-home-page-builder.php |
| `Apollo_Native_Newsletter` | Newsletter system   | includes/class-apollo-native-newsletter.php |
| `Apollo_Native_Push`       | Push notifications  | includes/class-apollo-native-push.php       |
| `Apollo_Native_SEO`        | SEO management      | includes/class-apollo-native-seo.php        |
| `Apollo_User_Moderation`   | User moderation     | includes/class-apollo-user-moderation.php   |
| `Cena_Rio_Submissions`     | Event submissions   | includes/class-cena-rio-submissions.php     |
| `Cena_Rio_Moderation`      | Cena moderation     | includes/class-cena-rio-moderation.php      |
| `Rest_Bootstrap`           | REST initialization | includes/class-rest-bootstrap.php           |

---

## Hooks Index

### Actions Fired (do_action)

| hook                         | purpose           | defined at                     |
| ---------------------------- | ----------------- | ------------------------------ |
| `apollo_core_activated`      | Plugin activation | includes/class-apollo-core.php |
| `apollo_before_home_content` | Before home page  | templates/page-home.php        |
| `apollo_after_home_content`  | After home page   | templates/page-home.php        |

### Filters Applied (apply_filters)

| filter                   | purpose              | defined at                                       |
| ------------------------ | -------------------- | ------------------------------------------------ |
| `apollo_rest_namespace`  | REST namespace       | includes/class-rest-bootstrap.php                |
| `apollo_event_card_args` | Event card arguments | templates/template-parts/home/events-listing.php |

---

## Reserved Identifiers

### Post Type Slugs

**Public CPTs:**

- `event_listing` → rewrite: `evento`, archive: `eventos`
- `event_dj` → rewrite: `dj`
- `event_local` → rewrite: `local`
- `apollo_classified` → rewrite: `anuncio`, archive: `anuncios`
- `apollo_social_post` → rewrite: `post-social`
- `user_page` → rewrite: `user-page`

**Private CPTs:**

- `apollo_email_template`
- `apollo_document`
- `apollo_supplier` (admin-only, sem rewrite)
- `cena_document`
- `cena_event_plan`
- `apollo_event_stat`
- `apollo_home_section`

### Group Types (Custom Tables)

- `comuna` (TYPE_COMUNA) - Grupos públicos
- `nucleo` (TYPE_NUCLEO) - Grupos privados
- `season` (TYPE_SEASON) - Temporadas (agrupamento social)

> **Note:** `season` também existe como taxonomy `event_season` para categorizar eventos.

### REST Namespace

- `apollo/v1`

### Option Prefixes

- `apollo_*`

### Shortcode Prefixes

- `apollo_*`

### Script/Style Handle Prefixes

- `apollo-*`
- Legacy: `chartjs`, `chart-js`, `framer-motion`, `leaflet`, `remixicon`, `datatables-js`, `datatables-css`

### Admin Menu Slugs

- `apollo-control`
- `apollo-moderation`
- `apollo-analytics`
- `apollo-communication`
- `apollo-*`

---

## Naming Conventions

To prevent duplicates across Apollo ecosystem:

- **CPT/taxonomy:** `apollo_{domain}_{thing}`
- **Meta keys:** `_apollo_{domain}_{key}` (leading underscore for hidden)
- **Options:** `apollo_{domain}_{setting}`
- **REST:** namespace `apollo/v1` + route `/{domain}/...`
- **Shortcodes:** `apollo_{domain}_{tag}`
- **Cron hooks:** `apollo_{domain}_{job}`
- **Handles:** `apollo-{domain}-{asset}`

---

## Code Health Audit: apollo-events-manager

_Audit Date: 2026-01-21_
_Last Updated: 2026-01-21 (Namespace corrections applied)_

### Namespace Integrity Report

> **Canonical Namespace:** `Apollo\Events\*` (PSR-4 with backslash)

| Pattern                   | Style             | Status       | Count    |
| ------------------------- | ----------------- | ------------ | -------- |
| `Apollo\Events\*`         | PSR-4 (backslash) | ✅ CORRECT   | 42 files |
| `Apollo_Events\*`         | Underscore        | ✅ FIXED     | 0 files  |
| `Apollo_Events_Manager\*` | Double underscore | ✅ FIXED     | 0 files  |
| `Favorites\*`             | Third-party fork  | ⚠️ TO REMOVE | 40 files |

#### Files Corrected (2026-01-21)

| File                                                | Old Namespace                  | New Namespace               | Status   |
| --------------------------------------------------- | ------------------------------ | --------------------------- | -------- |
| `src/Shortcodes/EventShortcodes.php`                | `Apollo_Events\Shortcodes`     | `Apollo\Events\Shortcodes`  | ✅ FIXED |
| `src/RestAPI/class-rest-api-loader.php`             | `Apollo_Events\REST_API`       | `Apollo\Events\RestAPI`     | ✅ FIXED |
| `src/RestAPI/class-events-controller.php`           | `Apollo_Events\REST_API`       | `Apollo\Events\RestAPI`     | ✅ FIXED |
| `src/Schema/EventsSchemaModule.php`                 | `Apollo_Events_Manager\Schema` | `Apollo\Events\Schema`      | ✅ FIXED |
| `includes/class-apollo-events-core-integration.php` | `Apollo_Events\Integration`    | `Apollo\Events\Integration` | ✅ FIXED |

#### Correct Namespace Structure

```
Apollo\Events                    ← Root namespace
├── Apollo\Events\Controllers    ← src/Controllers/
├── Apollo\Events\Services       ← src/Services/
├── Apollo\Events\Shortcodes     ← src/Shortcodes/ (TO FIX)
├── Apollo\Events\RestAPI        ← src/RestAPI/ (TO FIX)
├── Apollo\Events\Schema         ← src/Schema/ (TO FIX)
├── Apollo\Events\Core           ← includes/core/
├── Apollo\Events\Cena           ← includes/cena/
├── Apollo\Events\Modules        ← includes/modules/*/
├── Apollo\Events\Integration    ← includes/ (TO FIX)
└── Apollo\Events\Blocks         ← blocks/
```

### Test Coverage Report

| Test File                  | Purpose           | Status            |
| -------------------------- | ----------------- | ----------------- |
| `tests/test-bookmarks.php` | Bookmarks CRUD    | ✅ 7 test methods |
| `tests/test-rest-api.php`  | REST endpoints    | ⚠️ Manual test    |
| `tests/test-mvp-flows.php` | Integration flows | ⚠️ Manual test    |

**Gaps:** No unit tests for `Interest_Module`, `EventsAjaxController`, or form submissions.

---

## Interest/Favorite Feature Consolidation

_Analysis Date: 2026-01-21_

### Current Implementations (5 Found)

| #   | Implementation          | Location                                              | Storage Method                                                               | AJAX Action              | Score     |
| --- | ----------------------- | ----------------------------------------------------- | ---------------------------------------------------------------------------- | ------------------------ | --------- |
| 1   | **Interest_Module** 🏆  | `includes/modules/interest/class-interest-module.php` | `_event_interested_users` (post_meta), `_user_interested_events` (user_meta) | `apollo_toggle_interest` | **29/30** |
| 2   | ajax-favorites          | `includes/ajax-favorites.php`                         | `apollo_favorites` (user_meta), `_apollo_favorited_users` (post_meta)        | `toggle_favorite`        | 17/30     |
| 3   | Apollo_Events_Bookmarks | `includes/class-bookmarks.php`                        | Custom DB table `wp_apollo_event_bookmarks`                                  | `apollo_toggle_bookmark` | 23/30     |
| 4   | Favorites Module        | `modules/favorites/`                                  | `simplefavorites_*` options                                                  | `wem_toggle_bookmark`    | 18/30     |
| 5   | Event Single Enhanced   | `includes/class-event-single-enhanced-loader.php`     | `_event_interested_users`, `_favorites_count`                                | `apollo_toggle_favorite` | 12/30     |

### Scoring Breakdown

| Criteria           | Interest_Module | ajax-favorites | Bookmarks | Favorites | Enhanced |
| ------------------ | :-------------: | :------------: | :-------: | :-------: | :------: |
| Code Quality       |        5        |       3        |     4     |     4     |    2     |
| Modularity         |        5        |       2        |     3     |     4     |    1     |
| DB Efficiency      |        4        |       3        |     5     |     2     |    2     |
| Apollo Integration |        5        |       4        |     3     |     1     |    3     |
| Maintainability    |        5        |       3        |     4     |     2     |    2     |
| Completeness       |        5        |       2        |     4     |     5     |    2     |
| **TOTAL**          |     **29**      |     **17**     |  **23**   |  **18**   |  **12**  |

### 🏆 CANONICAL IMPLEMENTATION: Interest_Module

**Reasons:**

- ✅ Follows `Abstract_Module` pattern
- ✅ Already integrated with `apollo-core` via `Interest_Ranking`
- ✅ Dual-sync storage enables bidirectional queries
- ✅ 4 shortcodes, settings schema, action hooks
- ✅ Clean, typed, documented PHP 7.4+ code

### Canonical Meta Keys for "Interesse"

| Meta Key                  | Type              | Purpose                         | Owner           |
| ------------------------- | ----------------- | ------------------------------- | --------------- |
| `_event_interested_users` | post_meta (array) | User IDs interested in event    | Interest_Module |
| `_user_interested_events` | user_meta (array) | Event IDs user is interested in | Interest_Module |

### Legacy Meta Keys (TO DEPRECATE)

| Legacy Key                | Location  | Migration Target                        |
| ------------------------- | --------- | --------------------------------------- |
| `apollo_favorites`        | user_meta | `_user_interested_events`               |
| `_apollo_favorited_users` | post_meta | `_event_interested_users`               |
| `_favorites_count`        | post_meta | Computed from `_event_interested_users` |
| `_apollo_bookmark_count`  | post_meta | Computed from `_event_interested_users` |
| `simplefavorites_*`       | options   | Remove entirely                         |

### Canonical AJAX Actions

| Action                      | Purpose              | Handler                                      |
| --------------------------- | -------------------- | -------------------------------------------- |
| `apollo_toggle_interest`    | Toggle user interest | `Interest_Module::ajax_toggle_interest()`    |
| `apollo_get_interest_count` | Get interest count   | `Interest_Module::ajax_get_interest_count()` |

### Legacy AJAX Actions (TO DEPRECATE)

| Legacy Action            | Redirect To              |
| ------------------------ | ------------------------ |
| `toggle_favorite`        | `apollo_toggle_interest` |
| `apollo_toggle_favorite` | `apollo_toggle_interest` |
| `apollo_toggle_bookmark` | `apollo_toggle_interest` |
| `wem_toggle_bookmark`    | `apollo_toggle_interest` |

### Canonical Shortcodes

| Shortcode                   | Purpose                | Output                     |
| --------------------------- | ---------------------- | -------------------------- |
| `[apollo_interest_button]`  | Toggle button          | Button with icon and count |
| `[apollo_interest_count]`   | Display count          | Number with label          |
| `[apollo_user_interests]`   | "Meus Interesses" list | Grid of events user marked |
| `[apollo_interested_users]` | Users interested       | Avatar list with count     |

### UI Labels (Portuguese)

| Context                 | Label                  | Icon               |
| ----------------------- | ---------------------- | ------------------ |
| Button (not interested) | "Tenho Interesse"      | `ri-rocket-line`   |
| Button (interested)     | "Interessado"          | `ri-rocket-fill`   |
| List title              | "Meus Interesses"      | `ri-rocket-2-fill` |
| Count singular          | "pessoa interessada"   | -                  |
| Count plural            | "pessoas interessadas" | -                  |

### Migration Plan

#### Phase 1: Data Migration

```php
// Script: apollo-events-manager/scripts/migrate-favorites-to-interest.php
// 1. Read apollo_favorites from all users
// 2. Read _apollo_favorited_users from all posts
// 3. Merge into _event_interested_users / _user_interested_events
// 4. Delete legacy meta keys
```

#### Phase 2: Deprecate Legacy Code

| File                                              | Action                                             |
| ------------------------------------------------- | -------------------------------------------------- |
| `includes/ajax-favorites.php`                     | Add deprecation notice, wrapper to Interest_Module |
| `includes/class-bookmarks.php`                    | Mark @deprecated, redirect to Interest_Module      |
| `modules/favorites/`                              | REMOVE after migration                             |
| `includes/class-event-single-enhanced-loader.php` | Update to use Interest_Module                      |

#### Phase 3: Unify AJAX

```php
// In ajax-favorites.php
add_action('wp_ajax_toggle_favorite', function() {
    _deprecated_function('toggle_favorite', '3.0.0', 'apollo_toggle_interest');
    do_action('wp_ajax_apollo_toggle_interest');
});
```

### Files to Remove (Post-Migration)

| Path                       | Reason                            |
| -------------------------- | --------------------------------- |
| `modules/favorites/`       | Third-party fork, unused          |
| `modules/favorites/app/*`  | 40 files of dead code             |
| `tests/test-bookmarks.php` | Replaced by Interest_Module tests |

---

## Code Duplication Report

### Identified Duplications

| Duplication                 | Files Involved                                                                                             | Recommended Fix                                    |
| --------------------------- | ---------------------------------------------------------------------------------------------------------- | -------------------------------------------------- |
| Event favorite toggle logic | `ajax-favorites.php`, `class-bookmarks.php`, `class-event-single-enhanced-loader.php`, `ajax-handlers.php` | Consolidate into `Interest_Module`                 |
| Event data formatting       | `EventsAjaxController.php`, `class-event-modal-ajax.php`, `event-helpers.php`                              | Create `Apollo\Events\Services\EventDataFormatter` |
| Nonce verification patterns | Multiple AJAX handlers                                                                                     | Create base AJAX handler trait                     |
| Date parsing for events     | `event-helpers.php`, `EventShortcodes.php`, multiple templates                                             | Centralize in `Apollo\Events\Services\DateHelper`  |

---

## Risk Assessment (2026-01-22)

### 🔴 HIGH PRIORITY

1. **Duplicate event_listing CPT Registration**
   - **Files:** apollo-events-manager, apollo-core
   - **Severity:** HIGH
   - **Risk:** If both register simultaneously, first registration wins, second silently fails
   - **Status:** ✅ MITIGATED (guards in place at bootstrap)
   - **Recommendation:** Ensure only one plugin registers, other provides fallback

2. **Interest/Favorite/Bookmark Systems (5 implementations)**
   - **Files:** `Interest_Module.php`, `ajax-favorites.php`, `class-bookmarks.php`, `Favorites Module`, `Event_Single_Enhanced`
   - **Severity:** HIGH
   - **Issue:** Multiple implementations cause data inconsistency
   - **Status:** ⚠️ NEEDS CONSOLIDATION
   - **Canonical:** `Interest_Module` (score 29/30)
   - **Recommendation:** Deprecate others, use Interest_Module as single source

### 🟡 MEDIUM PRIORITY

1. **Legacy Meta Keys Not Migrated**
   - **Legacy:** `_event_djs`, `apollo_favorites`, `_apollo_favorited_users`, `simplefavorites_*`
   - **New:** `_event_dj_ids`, `_event_interested_users`
   - **Severity:** MEDIUM
   - **Status:** ⚠️ NEEDS MIGRATION
   - **Recommendation:** Complete migration with fallback support

2. **REST API Namespace Inconsistency**
   - **Issue:** Different namespaces - `apollo/v1`, `apollo-events/v1`, `apollo-social/v2`
   - **Severity:** MEDIUM
   - **Status:** ⚠️ STANDARDIZATION NEEDED
   - **Recommendation:** Standardize all to `apollo/v2`

3. **Duplicate AJAX Handlers**
   - **Issue:** `apollo_toggle_favorite` registered twice
   - **Severity:** MEDIUM
   - **Files:** `class-event-single-enhanced-loader.php`, `EventsAjaxController.php`
   - **Recommendation:** Consolidate to single handler

4. **Table Creation in Constructor**
   - **File:** `includes/class-bookmarks.php:38`
   - **Issue:** `create_table()` runs on every instantiation
   - **Severity:** MEDIUM
   - **Recommendation:** Move to activation/initialization hook

### 🟢 LOW PRIORITY

1. **Event Season Dual Implementation**
   - **Taxonomy:** `event_season` (apollo-events-manager) - Categorizes events
   - **Group Type:** `TYPE_SEASON` (apollo-social) - Groups social content
   - **Severity:** LOW
   - **Status:** ✅ DOCUMENTED
   - **Recommendation:** Clearly document distinction in code

2. **Magic Strings in Meta Keys**
   - **Issue:** Meta key names scattered throughout codebase
   - **Severity:** LOW
   - **Recommendation:** Use constants from `Apollo_Identifiers` class

3. **PHPCS Ignoring**
   - **Issue:** `// phpcs:ignoreFile` in 15+ files
   - **Severity:** LOW
   - **Recommendation:** Fix issues instead of ignoring

---

## Code Health Status (2026-01-22)

| Category                | Status           | Details                                                      |
| ----------------------- | ---------------- | ------------------------------------------------------------ |
| **Namespace Integrity** | ✅ EXCELLENT     | PSR-4 compliant across all plugins                           |
| **Test Coverage**       | ⚠️ PARTIAL       | Bookmarks (7 tests), gaps in Interest_Module, AJAX handlers  |
| **Code Quality**        | ✅ HIGH          | PHPCS: 0 errors, 85%+ type hints                             |
| **Database Schema**     | ✅ CURRENT       | Tracked via `apollo_db_version`, 25+ tables                  |
| **Security**            | ✅ EXCELLENT     | All AJAX with nonces, proper capabilities, full sanitization |
| **Documentation**       | ✅ COMPREHENSIVE | This inventory, inline comments, README files                |

---

## Syntax & Logic Issues (OLD)

### High Priority

| Issue                | File                                         | Line | Description                                                          |
| -------------------- | -------------------------------------------- | ---- | -------------------------------------------------------------------- |
| Missing namespace    | `includes/ajax-favorites.php`                | -    | Procedural, should be class with namespace                           |
| Missing namespace    | `includes/class-bookmarks.php`               | -    | Global class, add namespace                                          |
| Incorrect nonce name | `modules/interest/class-interest-module.php` | 171  | Uses `apollo_interest_nonce` but some JS sends `apollo_events_nonce` |

### Medium Priority

| Issue                        | File                                         | Description                                                             |
| ---------------------------- | -------------------------------------------- | ----------------------------------------------------------------------- |
| Table created in constructor | `class-bookmarks.php:38`                     | `create_table()` runs on every instantiation                            |
| Duplicate AJAX handler       | `apollo_toggle_favorite` registered twice    | `class-event-single-enhanced-loader.php` and `EventsAjaxController.php` |
| Dead code reference          | `class-event-single-enhanced-loader.php:175` | References `FavoritesModule` which doesn't exist                        |

### Low Priority (Code Smells)

| Issue                     | File                | Description                                              |
| ------------------------- | ------------------- | -------------------------------------------------------- |
| `// phpcs:ignoreFile`     | 15+ files           | Consider fixing PHPCS issues instead of ignoring         |
| Magic strings             | Multiple            | Meta keys should use constants from `Apollo_Identifiers` |
| Inconsistent return types | `ajax-handlers.php` | Mix of `wp_send_json_*` and manual `die()`               |
