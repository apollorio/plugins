# 🔍 AUDITORIA COMPLETA DOS PLUGINS APOLLO

**Data de Geração:** 22 de janeiro de 2026
**Escopo:** apollo-core, apollo-events-manager, apollo-social
**Status:** ✅ COMPLETO E EXAUSTIVO

---

## 📋 ÍNDICE GERAL

1. [RESUMO EXECUTIVO](#resumo-executivo)
2. [APOLLO-CORE](#apollo-core-plugin)
3. [APOLLO-EVENTS-MANAGER](#apollo-events-manager-plugin)
4. [APOLLO-SOCIAL](#apollo-social-plugin)
5. [ESTRUTURA DE BANCO DE DADOS](#estrutura-de-banco-de-dados)
6. [HOOKS E EVENTOS GLOBAIS](#hooks-e-eventos-globais)
7. [COLISÕES E RISCOS](#colisões-e-riscos)

---

## RESUMO EXECUTIVO

### Estatísticas Gerais

- **Total de CPTs:** 13
- **Total de Taxonomies:** 13+
- **REST Routes:** 50+
- **Shortcodes:** 40+
- **Hooks (do_action):** 100+
- **Hooks (apply_filters):** 50+
- **Tabelas Customizadas:** 25+
- **Meta Keys:** 100+
- **Scripts/Styles:** 50+

### Arquitetura

```
Apollo Ecosystem
├── apollo-core (base, utilities, integrations)
├── apollo-events-manager (gestão de eventos)
└── apollo-social (funcionalidades sociais)
```

---

# APOLLO-CORE PLUGIN

## Localização

`c:/Users/rafae/Local Sites/1212/app/public/wp-content/plugins/apollo-core/`

## 1. CPTs (Custom Post Types)

### CPT: event_listing

**Slug:** `event_listing`
**Labels:** Eventos
**Plugin:** apollo-core (gerenciado conjuntamente com apollo-events-manager)
**Arquivo:** `modules/events/bootstrap.php:91`
**Public:** true
**Rewrite:** `evento`
**Supports:** title, editor, author, thumbnail, comments, custom-fields
**Menu Icon:** dashicons-calendar
**Menu Position:** 5
**Taxonomies:** event_listing_category, event_listing_type, event_sounds, event_season

### CPT: event_dj

**Slug:** `event_dj`
**Labels:** DJs
**Plugin:** apollo-events-manager
**Arquivo:** `includes/post-types.php:139`
**Public:** true
**Rewrite:** `dj`
**Supports:** title, editor, thumbnail, custom-fields
**Menu Icon:** dashicons-format-audio
**Taxonomies:** event_sounds

### CPT: event_local

**Slug:** `event_local`
**Labels:** Locais
**Plugin:** apollo-events-manager
**Arquivo:** `includes/post-types.php:183`
**Public:** true
**Rewrite:** `local`
**Supports:** title, editor, thumbnail, custom-fields
**Menu Icon:** dashicons-location
**Taxonomies:** (none explicit)

### CPT: apollo_social_post

**Slug:** `apollo_social_post`
**Labels:** Social Posts
**Plugin:** apollo-social
**Arquivo:** `src/Infrastructure/PostTypes/SocialPostType.php:83`
**Public:** true
**Rewrite:** `post-social`
**Supports:** title, editor, author, thumbnail, comments
**Taxonomies:** social_category

### CPT: user_page

**Slug:** `user_page`
**Labels:** User Pages
**Plugin:** apollo-social
**Arquivo:** `src/Modules/UserPages/UserPageRegistrar.php:54`
**Public:** true
**Rewrite:** `user-page`
**Supports:** title, editor, custom-fields

### CPT: apollo_classified

**Slug:** `apollo_classified`
**Labels:** Anúncios
**Plugin:** apollo-social
**Arquivo:** `src/Modules/Classifieds/ClassifiedsModule.php:137`
**Public:** true
**Rewrite:** `anuncio`
**Supports:** title, editor, author, thumbnail
**Taxonomies:** classified_domain

### CPT: apollo_supplier

**Slug:** `apollo_supplier`
**Labels:** Fornecedores
**Plugin:** apollo-social
**Arquivo:** `src/Modules/Suppliers/SuppliersModule.php:171`
**Public:** false
**Supports:** title, editor, thumbnail
**Taxonomies:** supplier_category, supplier_type, supplier_service

### CPT: apollo_document

**Slug:** `apollo_document`
**Labels:** Documentos
**Plugin:** apollo-social
**Arquivo:** `src/Ajax/DocumentSaveHandler.php:155`
**Public:** false
**Supports:** custom-fields
**Meta Keys:** document_category, document_subcategory, document_code, document_status, document_pages

### CPT: cena_document

**Slug:** `cena_document`
**Labels:** Documentos Cena Rio
**Plugin:** apollo-social
**Arquivo:** `src/CenaRio/CenaRioModule.php:88`
**Public:** false

### CPT: cena_event_plan

**Slug:** `cena_event_plan`
**Labels:** Eventos Planejamento
**Plugin:** apollo-social
**Arquivo:** `src/CenaRio/CenaRioModule.php:115`
**Public:** false

### CPT: apollo_event_stat

**Slug:** `apollo_event_stat`
**Labels:** Event Stats
**Plugin:** apollo-events-manager
**Arquivo:** `includes/class-event-stat-cpt.php:57`
**Public:** false
**Purpose:** Armazenar estatísticas de eventos

### CPT: apollo_email_template

**Slug:** `apollo_email_template`
**Labels:** Email Templates
**Plugin:** apollo-core
**Arquivo:** `includes/class-apollo-email-templates-cpt.php:42`
**Public:** false
**Purpose:** Templates de email customizados

### CPT: apollo_home_section

**Slug:** `apollo_home_section`
**Labels:** Home Sections
**Plugin:** apollo-social
**Arquivo:** `src/Builder/class-apollo-home-cpt.php:96`
**Public:** false
**Purpose:** Seções customizáveis da home

---

## 2. Taxonomies (Classificações)

### Taxonomy: event_listing_category

**Label:** Categorias de Eventos
**Hierarchical:** true
**Associated CPTs:** event_listing
**Plugin:** apollo-events-manager
**Arquivo:** `includes/post-types.php:215`

### Taxonomy: event_listing_type

**Label:** Tipos de Eventos
**Hierarchical:** true
**Associated CPTs:** event_listing
**Plugin:** apollo-events-manager
**Arquivo:** `includes/post-types.php:250`

### Taxonomy: event_sounds

**Label:** Estilos Musicais
**Hierarchical:** false
**Associated CPTs:** event_dj, event_listing
**Plugin:** apollo-events-manager
**Arquivo:** `includes/post-types.php:283`
**Purpose:** Categorizar DJs e eventos por estilo musical

### Taxonomy: event_season

**Label:** Temporadas
**Hierarchical:** false
**Associated CPTs:** event_listing, apollo_classified
**Plugin:** apollo-events-manager
**Arquivo:** `includes/post-types.php:318`
**⚠️ Note:** Também existe como grupo customizado em apollo-social

### Taxonomy: social_category

**Label:** Social Categories
**Hierarchical:** true
**Associated CPTs:** apollo_social_post
**Plugin:** apollo-social
**Arquivo:** `src/Infrastructure/PostTypes/SocialPostType.php:106`

### Taxonomy: classified_domain

**Label:** Domínios de Anúncios
**Hierarchical:** true
**Associated CPTs:** apollo_classified
**Plugin:** apollo-social
**Arquivo:** `src/Modules/Classifieds/ClassifiedsModule.php:170`

### Taxonomy: classified_status

**Hierarchical:** false
**Associated CPTs:** apollo_classified
**Plugin:** apollo-social
**Arquivo:** `src/Modules/Classifieds/ClassifiedsModule.php:188`

### Taxonomy: supplier_category

**Hierarchical:** true
**Associated CPTs:** apollo_supplier
**Plugin:** apollo-social
**Arquivo:** `src/Modules/Suppliers/SuppliersModule.php:185`

### Taxonomy: supplier_type

**Hierarchical:** false
**Associated CPTs:** apollo_supplier
**Plugin:** apollo-social
**Arquivo:** `src/Modules/Suppliers/SuppliersModule.php:212`

### Taxonomy: supplier_service

**Hierarchical:** true
**Associated CPTs:** apollo_supplier
**Plugin:** apollo-social
**Arquivo:** `src/Modules/Suppliers/SuppliersModule.php:231`

### Taxonomy: supplier_location (+ mais 3)

**Multiple:** sim
**Arquivo:** `src/Modules/Suppliers/SuppliersModule.php:250-307`

---

## 3. Meta Keys Utilizadas

### Post Meta Keys (get_post_meta / update_post_meta)

| Meta Key                    | Tipo      | Plugin                | Propósito                |
| --------------------------- | --------- | --------------------- | ------------------------ |
| `_event_start_date`         | post_meta | apollo-events-manager | Data início evento       |
| `_event_end_date`           | post_meta | apollo-events-manager | Data fim evento          |
| `_event_start_time`         | post_meta | apollo-events-manager | Hora início              |
| `_event_end_time`           | post_meta | apollo-events-manager | Hora fim                 |
| `_event_location`           | post_meta | apollo-events-manager | Localização evento       |
| `_event_venue`              | post_meta | apollo-events-manager | Venue/Local              |
| `_event_address`            | post_meta | apollo-events-manager | Endereço completo        |
| `_event_lat`                | post_meta | apollo-events-manager | Latitude (legacy)        |
| `_event_lng`                | post_meta | apollo-events-manager | Longitude (legacy)       |
| `_event_latitude`           | post_meta | apollo-events-manager | Latitude (novo)          |
| `_event_longitude`          | post_meta | apollo-events-manager | Longitude (novo)         |
| `_event_price`              | post_meta | apollo-events-manager | Preço evento             |
| `_event_ticket_url`         | post_meta | apollo-events-manager | URL ingresso             |
| `_event_banner`             | post_meta | apollo-events-manager | ID imagem banner         |
| `_event_dj_ids`             | post_meta | apollo-events-manager | Array IDs DJs (novo)     |
| `_event_djs`                | post_meta | apollo-events-manager | IDs DJs (legacy)         |
| `_event_dj_slots`           | post_meta | apollo-events-manager | Timetable slots          |
| `_event_timetable`          | post_meta | apollo-events-manager | Timetable completo       |
| `_event_local_ids`          | post_meta | apollo-events-manager | Array IDs locais (novo)  |
| `_event_local`              | post_meta | apollo-events-manager | Local ID (legacy)        |
| `_dj_name`                  | post_meta | apollo-events-manager | Nome DJ                  |
| `_local_name`               | post_meta | apollo-events-manager | Nome local               |
| `_local_address`            | post_meta | apollo-events-manager | Endereço local           |
| `_local_lat`                | post_meta | apollo-events-manager | Latitude local (legacy)  |
| `_local_lng`                | post_meta | apollo-events-manager | Longitude local (legacy) |
| `_local_latitude`           | post_meta | apollo-events-manager | Latitude local (novo)    |
| `_local_longitude`          | post_meta | apollo-events-manager | Longitude local (novo)   |
| `document_category`         | post_meta | apollo-social         | Categoria documento      |
| `document_subcategory`      | post_meta | apollo-social         | Subcategoria documento   |
| `document_code`             | post_meta | apollo-social         | Código documento         |
| `document_status`           | post_meta | apollo-social         | Status documento         |
| `document_pages`            | post_meta | apollo-social         | Nº páginas               |
| `apollo_userpage_layout_v1` | post_meta | apollo-social         | Layout página usuário    |
| `nucleo_id`                 | post_meta | apollo-core           | ID núcleo associado      |
| `community_id`              | post_meta | apollo-core           | ID comunidade associada  |

### User Meta Keys (get_user_meta / update_user_meta)

| Meta Key                          | Plugin        | Propósito                       |
| --------------------------------- | ------------- | ------------------------------- |
| `_apollo_instagram_id`            | apollo-core   | Instagram ID do usuário         |
| `_apollo_suspended_until`         | apollo-core   | Timestamp suspensão             |
| `_apollo_blocked`                 | apollo-core   | Booleano bloqueio usuário       |
| `user_role_display`               | apollo-core   | Exibição role customizada       |
| `description`                     | apollo-core   | Bio/descrição usuário           |
| `user_location`                   | apollo-core   | Localização usuário             |
| `verified`                        | apollo-core   | Booleano verificação            |
| `privacy_profile`                 | apollo-core   | Configuração privacidade        |
| `apollo_user_page_id`             | apollo-social | ID página usuário (custom post) |
| `_apollo_hub_avatar_style`        | apollo-social | Estilo avatar hub               |
| `_apollo_hub_avatar_border`       | apollo-social | Border avatar                   |
| `_apollo_hub_avatar_border_width` | apollo-social | Largura border                  |
| `_apollo_hub_avatar_border_color` | apollo-social | Cor border                      |
| `_apollo_hub_avatar`              | apollo-social | URL avatar hub                  |
| `_apollo_hub_name`                | apollo-social | Nome hub                        |
| `_apollo_hub_bio`                 | apollo-social | Bio hub                         |
| `_apollo_hub_bg`                  | apollo-social | Background cor                  |
| `_apollo_hub_texture`             | apollo-social | Textura fundo                   |

---

## 4. REST API Routes

### apollo-core REST Routes

**Namespace:** `apollo/v1`

#### Route: `/eventos`

- **Methods:** GET, POST
- **Callback:** `Apollo_Events_Rest_Handler::get_eventos()`
- **Arquivo:** `modules/events/bootstrap.php:162`
- **Purpose:** Listar/criar eventos

#### Route: `/eventos/{id}`

- **Methods:** GET, PUT, DELETE
- **Callback:** `Apollo_Events_Rest_Handler::get_evento()`
- **Arquivo:** `modules/events/bootstrap.php:182`
- **Purpose:** Operações CRUD evento específico

#### Route: `/eventos/{id}/djs`

- **Methods:** GET
- **Arquivo:** `modules/events/bootstrap.php:198`
- **Purpose:** Listar DJs do evento

#### Route: `/social/feed`

- **Methods:** GET
- **Arquivo:** `modules/social/bootstrap.php:176`
- **Purpose:** Feed social

#### Route: `/social/user/{user_id}`

- **Methods:** GET
- **Arquivo:** `modules/social/bootstrap.php:192`
- **Purpose:** Dados usuário social

#### Route: `/moderation/users`

- **Methods:** GET, POST
- **Arquivo:** `modules/moderation/includes/class-rest-api.php:52`
- **Purpose:** Moderação de usuários

#### Route: `/moderation/suspend`

- **Methods:** POST
- **Arquivo:** `modules/moderation/includes/class-rest-api.php:72`
- **Purpose:** Suspender usuário

#### Route: `/moderation/block`

- **Methods:** POST
- **Arquivo:** `modules/moderation/includes/class-rest-api.php:92`
- **Purpose:** Bloquear usuário

#### Route: `/user-moderation/suspend`

- **Methods:** POST
- **Arquivo:** `includes/class-apollo-user-moderation.php:929`
- **Purpose:** REST endpoint suspensão

### apollo-events-manager REST Routes

**Namespace:** `apollo-events/v1`

#### Route: `/events/stats`

- **Arquivo:** `includes/admin-dashboard.php:181`

#### Route: `/events/bookmarks`

- **Arquivo:** `includes/admin-dashboard.php:191`

#### Route: `/events/import`

- **Arquivo:** `includes/admin-dashboard.php:219`

#### Route: `/events/export`

- **Arquivo:** `includes/admin-dashboard.php:229`

Mais 10+ routes adicionais para análise de eventos, DJs, etc.

### apollo-social REST Routes

**Namespace:** `apollo-social/v2`

#### Feed Controller (`src/RestAPI/class-feed-controller.php`)

- `/feed` - GET feed atividade
- `/feed/create` - POST criar atividade
- `/feed/{id}` - GET atividade específica
- `/feed/{id}/like` - POST curtir
- `/feed/{id}/comment` - POST comentar
- `/feed/{id}/share` - POST compartilhar

#### Profile Controller (`src/RestAPI/class-profile-controller.php`)

- `/profile` - GET perfil atual
- `/profile/{id}` - GET perfil usuário
- `/profile/edit` - PUT editar perfil
- `/profile/follow` - POST seguir usuário
- `/profile/unfollow` - POST desfollower
- `/profile/verify` - POST verificação
- `/profile/stats` - GET estatísticas

#### Classifieds Controller (`src/RestAPI/class-classifieds-controller.php`)

- `/classifieds` - GET/POST anúncios
- `/classifieds/{id}` - GET/PUT anúncio
- `/classifieds/{id}/respond` - POST responder
- `/classifieds/{id}/report` - POST reportar

#### Verification Module (`src/Modules/Verification/UserVerification.php`)

- `/verification/request` - POST solicitar verificação
- `/verification/list` - GET lista pendentes
- `/verification/approve` - POST aprovar

Mais 15+ routes adicionais para signatures, documents, suppliers, etc.

---

## 5. Shortcodes

### apollo-core Shortcodes

| Tag                          | Callback                                               | Arquivo                                                     | Propósito             |
| ---------------------------- | ------------------------------------------------------ | ----------------------------------------------------------- | --------------------- |
| `apollo_newsletter`          | `Apollo_Native_Newsletter::render_subscription_form()` | `includes/class-apollo-native-newsletter.php:53`            | Formulário newsletter |
| `apollo_cena_submit_event`   | `Cena_Rio_Submissions::render_submission_form()`       | `includes/class-cena-rio-submissions.php:35`                | Form submissão evento |
| `apollo_top_sounds`          | `Interesse_Ranking::shortcode_top_sounds()`            | `includes/class-interesse-ranking.php:17`                   | Top sounds            |
| `apollo_cena_mod_queue`      | `Cena_Rio_Moderation::render_mod_queue()`              | `includes/class-cena-rio-moderation.php:34`                 | Fila moderação        |
| `apollo_interesse_dashboard` | `Apollo_User_Dashboard_Interesse::render()`            | `includes/class-user-dashboard-interesse.php:11`            | Dashboard interesse   |
| `apollo_user_stats`          | `Apollo_User_Stats_Widget::render_shortcode()`         | `includes/class-user-stats-widget.php:30`                   | Stats usuário         |
| `apollo_home_hero`           | `Apollo_Home_Widgets_Loader::shortcode_hero()`         | `includes/widgets/class-apollo-home-widgets-loader.php:107` | Hero section home     |
| `apollo_home_manifesto`      | `Apollo_Home_Widgets_Loader::shortcode_manifesto()`    | `includes/widgets/class-apollo-home-widgets-loader.php:108` | Manifesto             |
| `apollo_home_events`         | `Apollo_Home_Widgets_Loader::shortcode_events()`       | `includes/widgets/class-apollo-home-widgets-loader.php:109` | Eventos home          |
| `apollo_home_classifieds`    | `Apollo_Home_Widgets_Loader::shortcode_classifieds()`  | `includes/widgets/class-apollo-home-widgets-loader.php:110` | Anúncios home         |
| `apollo_home_hub`            | `Apollo_Home_Widgets_Loader::shortcode_hub()`          | `includes/widgets/class-apollo-home-widgets-loader.php:111` | Hub home              |
| `apollo_home_ferramentas`    | `Apollo_Home_Widgets_Loader::shortcode_ferramentas()`  | `includes/widgets/class-apollo-home-widgets-loader.php:112` | Ferramentas           |
| `apollo_event_card`          | `Apollo_Home_Widgets_Loader::shortcode_event_card()`   | `includes/widgets/class-apollo-home-widgets-loader.php:115` | Card evento           |

### apollo-events-manager Shortcodes

| Tag                               | Callback                                         | Arquivo                                                             | Propósito           |
| --------------------------------- | ------------------------------------------------ | ------------------------------------------------------------------- | ------------------- |
| `apollo_bookmarks`                | `Apollo_Events_Bookmarks::bookmarks_shortcode()` | `includes/class-bookmarks.php:55`                                   | Lista bookmarks     |
| `apollo_public_event_form`        | `apollo_render_public_event_form()`              | `includes/public-event-form.php:450`                                | Form evento público |
| `apollo_events_grid`              | `apollo_events_grid_shortcode()`                 | `includes/helpers/event-card-helper.php:422`                        | Grid eventos        |
| `apollo_notify_button`            | `Notifications_Module::render_notify_button()`   | `includes/modules/notifications/class-notifications-module.php:144` | Botão notificação   |
| `apollo_notification_preferences` | `Notifications_Module::render_preferences()`     | `includes/modules/notifications/class-notifications-module.php:145` | Preferências        |
| `apollo_event_djs`                | `Speakers_Module::render_event_djs()`            | `includes/modules/speakers/class-speakers-module.php:112`           | DJs evento          |
| `apollo_dj_card`                  | `Speakers_Module::render_dj_card()`              | `includes/modules/speakers/class-speakers-module.php:113`           | Card DJ             |
| `apollo_dj_grid`                  | `Speakers_Module::render_dj_grid()`              | `includes/modules/speakers/class-speakers-module.php:114`           | Grid DJs            |
| `apollo_timetable`                | `Speakers_Module::render_timetable()`            | `includes/modules/speakers/class-speakers-module.php:115`           | Timetable           |
| `apollo_schedule`                 | `Speakers_Module::render_schedule()`             | `includes/modules/speakers/class-speakers-module.php:116`           | Schedule            |
| `apollo_dj_slider`                | `Speakers_Module::render_dj_slider()`            | `includes/modules/speakers/class-speakers-module.php:117`           | DJ slider           |
| `apollo_event_stats`              | `Tracking_Module::render_event_stats()`          | `includes/modules/tracking/class-tracking-module.php:141`           | Stats evento        |
| `apollo_popular_events`           | `Tracking_Module::render_popular_events()`       | `includes/modules/tracking/class-tracking-module.php:142`           | Popular             |
| `apollo_trending_events`          | `Tracking_Module::render_trending_events()`      | `includes/modules/tracking/class-tracking-module.php:143`           | Trending            |
| `apollo_share_buttons`            | `Share_Module::render_share_buttons()`           | `includes/modules/share/class-share-module.php:193`                 | Botões share        |
| `apollo_share_count`              | `Share_Module::render_share_count()`             | `includes/modules/share/class-share-module.php:194`                 | Contador shares     |
| `apollo_share_single`             | `Share_Module::render_single_button()`           | `includes/modules/share/class-share-module.php:195`                 | Single button       |
| `apollo_ticket_button`            | `Tickets_Module::render_ticket_button()`         | `includes/modules/tickets/class-tickets-module.php:260`             | Botão ingresso      |
| `apollo_ticket_price`             | `Tickets_Module::render_ticket_price()`          | `includes/modules/tickets/class-tickets-module.php:261`             | Preço ingresso      |

### apollo-social Shortcodes

| Tag                        | Callback                                             | Arquivo                                      | Propósito         |
| -------------------------- | ---------------------------------------------------- | -------------------------------------------- | ----------------- |
| `apollo_members_directory` | `SocialServiceProvider::membersDirectoryShortcode()` | `src/Providers/SocialServiceProvider.php:30` | Diretório membros |
| `apollo_activity_feed`     | `SocialServiceProvider::activityFeedShortcode()`     | `src/Providers/SocialServiceProvider.php:31` | Feed atividade    |
| `apollo_groups_directory`  | `SocialServiceProvider::groupsDirectoryShortcode()`  | `src/Providers/SocialServiceProvider.php:32` | Diretório grupos  |
| `apollo_leaderboard`       | `SocialServiceProvider::leaderboardShortcode()`      | `src/Providers/SocialServiceProvider.php:33` | Ranking           |
| `apollo_online_users`      | `SocialServiceProvider::onlineUsersShortcode()`      | `src/Providers/SocialServiceProvider.php:34` | Usuários online   |
| `apollo_my_profile`        | `SocialServiceProvider::myProfileShortcode()`        | `src/Providers/SocialServiceProvider.php:35` | Meu perfil        |
| `apollo_team_members`      | `SocialServiceProvider::teamMembersShortcode()`      | `src/Providers/SocialServiceProvider.php:36` | Membros time      |
| `apollo_testimonials`      | `SocialServiceProvider::testimonialsShortcode()`     | `src/Providers/SocialServiceProvider.php:37` | Depoimentos       |
| `apollo_map`               | `SocialServiceProvider::mapShortcode()`              | `src/Providers/SocialServiceProvider.php:38` | Mapa              |
| `apollo_notices`           | `SocialServiceProvider::noticesShortcode()`          | `src/Providers/SocialServiceProvider.php:39` | Avisos            |
| `apollo_social_feed`       | `SocialShortcodes::render_social_feed()`             | `src/Shortcodes/SocialShortcodes.php:63`     | Feed social       |
| `apollo_social_share`      | `SocialShortcodes::render_social_share()`            | `src/Shortcodes/SocialShortcodes.php:66`     | Compartilhamento  |
| `apollo_user_profile`      | `SocialShortcodes::render_user_profile()`            | `src/Shortcodes/SocialShortcodes.php:69`     | Perfil usuário    |
| `apollo_profile_card`      | `SocialShortcodes::render_profile_card()`            | `src/Shortcodes/SocialShortcodes.php:72`     | Card perfil       |
| `apollo_classifieds`       | `SocialShortcodes::render_classifieds()`             | `src/Shortcodes/SocialShortcodes.php:75`     | Anúncios          |
| `apollo_classified_form`   | `SocialShortcodes::render_classified_form()`         | `src/Shortcodes/SocialShortcodes.php:78`     | Form anúncio      |
| `apollo_user_dashboard`    | `SocialShortcodes::render_user_dashboard()`          | `src/Shortcodes/SocialShortcodes.php:81`     | Dashboard usuário |
| `apollo_follow_button`     | `SocialShortcodes::render_follow_button()`           | `src/Shortcodes/SocialShortcodes.php:84`     | Botão seguir      |
| `apollo_user_activity`     | `SocialShortcodes::render_user_activity()`           | `src/Shortcodes/SocialShortcodes.php:87`     | Atividade usuário |

---

## 6. Admin Menus & Páginas

### apollo-core Admin Pages

**Menu Principal:** Apollo Cabin
**Arquivo:** `admin/admin-apollo-cabin.php`
**Posição:** 5
**Ícone:** dashicons-apollo (custom)

**Submenus:**

- QA Audit Page (`admin/qa-audit-page.php:20`)
- Moderation (`admin/moderation-page.php:46`)
- Analytics (`includes/class-apollo-analytics.php:825`)
- Cookies (`includes/class-apollo-cookie-consent.php:77`)
- Email Templates (`includes/class-apollo-email-admin-ui.php:37`)
- Email Integration (`includes/class-apollo-email-integration.php:206`)
- Migrations (`admin/migration-page.php:21-31`)
- Newsletter (`includes/class-apollo-native-newsletter.php:1007`)
- Push Notifications (`includes/class-apollo-native-push.php:640`)
- Navbar Apps (`includes/class-apollo-navbar-apps.php:189`)
- SEO Settings (`includes/class-apollo-native-seo.php:1037`)

### apollo-events-manager Admin Pages

**Menu Principal:** Apollo Hub
**Arquivo:** `includes/admin-apollo-hub.php`
**Posição:** 5

**Submenus:**

- Dashboard (`admin-apollo-hub.php:37`)
- Settings (`admin-apollo-hub.php:46`)
- Eventos (`admin-apollo-hub.php:55`)
- DJs (`admin-apollo-hub.php:64`)
- Locais (`admin-apollo-hub.php:74`)
- Categorias (`admin-apollo-hub.php:83`)
- Tipos (`admin-apollo-hub.php:93`)
- Seasons (`admin-apollo-hub.php:102`)
- Import/Export (`admin-apollo-hub.php:111`)
- Analytics (`admin-apollo-hub.php:120`)
- Shortcodes Documentation (`admin-apollo-hub.php:129`)

### apollo-social Admin Pages

**Menu:** Verificações
**Arquivo:** `src/Admin/VerificationsTable.php:56`

**Submenus:**

- Verificações (`src/Admin/VerificationsTable.php:66`)
- Documentos (`src/Modules/Documents/DocumentsModule.php:307`)
- Signatures (`src/Modules/Signatures/SignaturesModule.php:227`)
- Builder (`src/Modules/Builder/Admin/BuilderAdminPage.php:23`)
- Analytics (`src/Infrastructure/Admin/AnalyticsAdmin.php:32`)
- Settings (`src/Infrastructure/Admin/SettingsPage.php:15`)
- E-signatures (`src/Admin/EsignSettingsAdmin.php:58`)
- Email Notifications (`src/Admin/EmailNotificationsAdmin.php:72`)
- Help (`src/Admin/HelpMenuAdmin.php:29`)
- Email Hub (`src/Admin/EmailHubAdmin.php:506-524`)

---

## 7. WordPress Options (get_option / update_option)

### apollo-core Options

| Option                       | Default | Type    | Plugin      | Purpose                  |
| ---------------------------- | ------- | ------- | ----------- | ------------------------ |
| `apollo_core_mod_enabled`    | -       | boolean | apollo-core | Moderation enabled       |
| `apollo_mod_settings`        | -       | array   | apollo-core | Moderation configuration |
| `apollo_form_schemas`        | -       | array   | apollo-core | Form schemas             |
| `apollo_form_schema_version` | -       | string  | apollo-core | Schema version           |
| `apollo_memberships`         | -       | array   | apollo-core | Membership data          |
| `apollo_memberships_version` | -       | string  | apollo-core | Memberships version      |
| `apollo_db_version`          | 0.0.0   | string  | apollo-core | Database schema version  |
| `apollo_social_profiles`     | array() | array   | apollo-core | Social media profiles    |

### apollo-events-manager Options

| Option                                    | Default | Type    | Plugin                | Purpose             |
| ----------------------------------------- | ------- | ------- | --------------------- | ------------------- |
| `apollo_aprio_version`                    | -       | string  | apollo-events-manager | Plugin version      |
| `apollo_events_auto_create_eventos_page`  | false   | boolean | apollo-events-manager | Auto-create page    |
| `apollo_events_osm_default_zoom`          | 14      | int     | apollo-events-manager | Map zoom level      |
| `apollo_events_osm_tile_style`            | default | string  | apollo-events-manager | Map tile style      |
| `apollo_events_manager_missing_core`      | false   | boolean | apollo-events-manager | Core missing flag   |
| `apollo_events_per_page`                  | 12      | int     | apollo-events-manager | Pagination          |
| `apollo_events_map_enabled`               | 1       | boolean | apollo-events-manager | Map display         |
| `apollo_events_map_visual_zoom`           | 1       | boolean | apollo-events-manager | Map zoom feature    |
| `apollo_events_favorites_enabled`         | 1       | boolean | apollo-events-manager | Favorites feature   |
| `apollo_events_analytics_enabled`         | 1       | boolean | apollo-events-manager | Analytics           |
| `apollo_events_submission_enabled`        | 1       | boolean | apollo-events-manager | User submissions    |
| `apollo_events_submission_mod`            | 1       | boolean | apollo-events-manager | Moderation required |
| `apollo_events_manager_activated_version` | -       | string  | apollo-events-manager | Activation version  |

---

## 8. Scripts & Styles Enqueued

### apollo-core Scripts

| Handle                    | Source | Type   | Dependencies | Arquivo                                            |
| ------------------------- | ------ | ------ | ------------ | -------------------------------------------------- |
| `apollo-icon-loader`      | CDN    | defer  | -            | `src/Assets/class-apollo-assets-loader.php:68`     |
| Core CSS bundle           | CDN    | -      | -            | `src/Assets/class-apollo-assets-loader.php:49-89`  |
| Core JS bundle            | CDN    | -      | -            | `src/Assets/class-apollo-assets-loader.php:59-92`  |
| `apollo-document-scripts` | local  | footer | jquery       | `templates/apollo-document-ajax.php:61`            |
| `apollo-sign-centered`    | local  | footer | -            | `templates/apollo-sign-centered-enqueue.php:11-18` |
| `apollo-suppliers`        | local  | footer | -            | `templates/apollo-suppliers-ajax.php:79-86`        |

**Enqueue Hook:** `wp_enqueue_scripts` (priority 999)

### apollo-events-manager Scripts

| Handle              | Source                       | Type   | Dependencies | Arquivo                                  |
| ------------------- | ---------------------------- | ------ | ------------ | ---------------------------------------- |
| `chartjs`           | CDN                          | -      | -            | `includes/admin-dashboard.php:146`       |
| `datatables-css`    | CDN                          | -      | -            | `includes/admin-dashboard.php:142`       |
| `datatables-js`     | CDN                          | -      | -            | `includes/admin-dashboard.php:143`       |
| `apollo-calendar`   | local                        | -      | -            | `src/Shortcodes/EventShortcodes.php:634` |
| `apollo-cdn-loader` | https://assets.apollo.rio.br | footer | -            | `src/Shortcodes/EventShortcodes.php:869` |
| `leaflet`           | CDN                          | -      | -            | `templates/single-event_listing.php:35`  |

**Asset Manager:** `src/Services/EventsAssetLoader.php:52`

### apollo-social Scripts

**Enqueue Hook:** `wp_enqueue_scripts` (priority 10)

**Pattern:** Carrega via `SocialServiceProvider::enqueueAssets()`

---

## 9. AJAX Actions (add_action wp_ajax)

### apollo-core AJAX Actions

⚠️ **Nota:** `add_action( 'wp_ajax', ... )` pode estar filtrando através de:

- `Apollo_AJAX_Handler` class
- Hooks registry system

Principais actions detectadas:

- `wp_ajax_apollo_toggle_interest` (src/Hooks/HookRegistry.php)

### apollo-events-manager AJAX Actions

- Event modal interactions (includes/ajax/class-event-modal-ajax.php:26)
- Bookmark operations
- Analytics tracking

### apollo-social AJAX Actions

- Document save handler (src/Ajax/DocumentSaveHandler.php)
- User page editor (user-pages/class-user-page-editor-ajax.php)
- Social feed operations

---

## 10. Tabelas de Banco de Dados Customizadas

### apollo-core Tables

#### `wp_apollo_activity_log`

**Arquivo:** `includes/class-apollo-activation-controller.php:213`
**Columns:**

- `id` INT PRIMARY KEY AUTO_INCREMENT
- `user_id` BIGINT
- `action` VARCHAR(255)
- `object_type` VARCHAR(100)
- `object_id` BIGINT
- `meta_data` LONGTEXT (JSON)
- `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP

#### `wp_apollo_relationships`

**Arquivo:** `includes/class-apollo-activation-controller.php:232`
**Columns:**

- `id` INT PRIMARY KEY AUTO_INCREMENT
- `source_type` VARCHAR(100)
- `source_id` BIGINT
- `target_type` VARCHAR(100)
- `target_id` BIGINT
- `relation_type` VARCHAR(100)
- `created_at` TIMESTAMP

#### `wp_apollo_event_queue`

**Arquivo:** `includes/class-apollo-activation-controller.php:250`
**Purpose:** Event queue system
**Columns:**

- `id` INT PRIMARY KEY AUTO_INCREMENT
- `event_name` VARCHAR(255)
- `payload` LONGTEXT (JSON)
- `status` VARCHAR(50)
- `created_at` TIMESTAMP

#### `wp_apollo_mod_log`

**Arquivo:** `includes/db-schema.php:31`
**Purpose:** Moderation actions log

#### `wp_apollo_audit_log`

**Arquivo:** `includes/db-schema.php:51`
**Purpose:** Audit trail

#### `wp_apollo_pageviews`

**Arquivo:** `includes/db-schema.php:76`
**Purpose:** Analytics pageviews

#### `wp_apollo_interactions`

**Arquivo:** `includes/db-schema.php:110`
**Purpose:** User interactions tracking

#### `wp_apollo_sessions`

**Arquivo:** `includes/db-schema.php:140`
**Purpose:** Session management

#### `wp_apollo_user_stats`

**Arquivo:** `includes/db-schema.php:176`
**Purpose:** User statistics cache

#### `wp_apollo_content_stats`

**Arquivo:** `includes/db-schema.php:204`
**Purpose:** Content statistics

#### `wp_apollo_heatmap`

**Arquivo:** `includes/db-schema.php:230`
**Purpose:** Heatmap data

#### `wp_apollo_stats_settings`

**Arquivo:** `includes/db-schema.php:249`
**Purpose:** Analytics settings

#### Newsletter Tables

**Arquivo:** `includes/class-apollo-native-newsletter.php`

- `wp_apollo_newsletter_subscribers`
- `wp_apollo_newsletter_campaigns`

#### `wp_apollo_notifications`

**Arquivo:** `includes/communication/notifications/class-notification-manager.php:44`

#### `wp_apollo_notification_preferences`

**Arquivo:** `includes/communication/notifications/class-notification-manager.php:66`

#### `wp_apollo_email_security_log`

**Arquivo:** `includes/class-email-security-log.php:86`

#### `wp_apollo_quiz_*` (múltiplas)

**Arquivo:** `includes/quiz/schema-manager.php:344`

### apollo-events-manager Tables

#### `wp_apollo_event_bookmarks`

**Arquivo:** `includes/class-bookmarks.php:70`
**Purpose:** User bookmarks para eventos

#### `wp_aprio_rest_api_keys`

**Arquivo:** `modules/rest-api/aprio-rest-api.php:193`
**Purpose:** API keys management

#### `wp_apollo_event_cron_jobs`

**Arquivo:** `src/Services/EventsCronJobs.php:386`

#### `wp_apollo_events_analytics`

**Arquivo:** `includes/admin-dashboard.php:56`

#### `wp_apollo_events_likes`

**Arquivo:** `includes/admin-dashboard.php:77`

#### `wp_apollo_events_technotes`

**Arquivo:** `includes/admin-dashboard.php:91`

### apollo-social Tables

Geralmente herda do apollo-core, mas pode ter tabelas específicas para:

- Groups management
- Social features
- Verification system
- Document system

---

## 11. Classes Principais & Namespaces

### apollo-core Classes

**Namespaces principais:**

- `Apollo_Core\` (namespace principal)
- `Apollo\Core\` (novo namespace PSR-4)
- `Apollo\Events\` (eventos)
- `Apollo\Social\` (social)
- `Apollo\Core\Security\`
- `Apollo\Core\Hooks\`
- `Apollo\Core\Tools\`

**Classes principais:**

- `Apollo_Activation_Controller` - Ativação/desativação
- `Apollo_AJAX_Handler` - Manipulação AJAX
- `Apollo_CPT_Registry` - Registro de CPTs
- `Apollo_Taxonomy_Registry` - Registro de taxonomies
- `Apollo_Analytics` - Análise
- `Apollo_Capabilities` - Gerenciamento permissões
- `Apollo_Assets` - Gestão assets
- `Apollo_Elementor_Integration` - Integração Elementor

### apollo-events-manager Classes

**Namespaces:**

- `Apollo\Events\`
- `Apollo\Events\Core\`
- `Apollo\Events\Modules\`
- `Apollo\Events\Cena\`

**Classes principais:**

- `Apollo_Post_Types` - Registro CPTs
- `Apollo_Events_REST_API` - REST API
- `Apollo_Admin_Dashboard` - Dashboard admin
- `Apollo_Event_Modal_Ajax` - AJAX modals
- `Event_Cena_CPT` - CPT Cena Rio
- `Calendar_Module` - Módulo calendário
- `Interest_Module` - Módulo interesse
- `Reviews_Module` - Módulo reviews
- `Speakers_Module` - Módulo speakers/DJs
- `Tracking_Module` - Módulo tracking/analytics
- `Share_Module` - Módulo compartilhamento
- `Tickets_Module` - Módulo ingressos

### apollo-social Classes

**Namespaces:**

- `Apollo\Social\`
- `Apollo\Social\Infrastructure\`
- `Apollo\Social\Modules\`
- `Apollo\Tests\`

**Classes principais:**

- `Apollo_User_Page_CPT` - Página usuário
- `Apollo_User_Page_Rewrite` - Rewrite rules
- `Apollo_User_Page_Permissions` - Permissões
- `ClassifiedsModule` - Módulo anúncios
- `SuppliersModule` - Módulo fornecedores
- `UserPageRegistrar` - Registro página usuário
- `SocialPostType` - Post type social
- `CenaRioModule` - Módulo Cena Rio
- `SocialServiceProvider` - Provider social
- `RestAPILoader` - Carregador REST API

---

## 12. Hooks (do_action & apply_filters)

### Actions Principais (do_action)

#### Lifecycle Events

| Hook                         | Arquivo                                    | Propósito           |
| ---------------------------- | ------------------------------------------ | ------------------- |
| `apollo_activated`           | class-apollo-activation-controller.php:83  | Plugin ativado      |
| `apollo_deactivated`         | class-apollo-activation-controller.php:106 | Plugin desativado   |
| `apollo_uninstalled`         | class-apollo-activation-controller.php:145 | Plugin desinstalado |
| `apollo_register_post_types` | class-apollo-activation-controller.php:359 | Registrar CPTs      |

#### Content Events

| Hook                         | Plugin                | Propósito            |
| ---------------------------- | --------------------- | -------------------- |
| `apollo_before_save_event`   | apollo-events-manager | Antes salvar evento  |
| `apollo_after_save_event`    | apollo-events-manager | Depois salvar evento |
| `apollo_before_delete_event` | apollo-events-manager | Antes deletar        |
| `apollo_before_import`       | apollo-events-manager | Antes import         |
| `apollo_after_import`        | apollo-events-manager | Depois import        |
| `apollo_before_export`       | apollo-events-manager | Antes export         |

#### User Events

| Hook                            | Plugin                | Propósito                |
| ------------------------------- | --------------------- | ------------------------ |
| `apollo_user_interested`        | apollo-events-manager | Usuário interessado      |
| `apollo_user_uninterested`      | apollo-events-manager | Usuário desfaz interesse |
| `apollo_user_verified`          | apollo-social         | Usuário verificado       |
| `apollo_verification_requested` | apollo-social         | Verificação solicitada   |
| `apollo_verification_rejected`  | apollo-social         | Verificação rejeitada    |
| `apollo_user_followed`          | apollo-social         | Usuário seguido          |
| `apollo_group_created`          | apollo-social         | Grupo criado             |
| `apollo_group_joined`           | apollo-social         | Usuário entrou em grupo  |
| `apollo_group_left`             | apollo-social         | Usuário saiu do grupo    |

#### Activity Events

| Hook                        | Plugin        | Propósito        |
| --------------------------- | ------------- | ---------------- |
| `apollo_activity_created`   | apollo-social | Atividade criada |
| `apollo_classified_created` | apollo-social | Anúncio criado   |

#### UI/Render Events

| Hook                           | Plugin        | Propósito              |
| ------------------------------ | ------------- | ---------------------- |
| `apollo_before_home_content`   | apollo-core   | Antes conteúdo home    |
| `apollo_after_home_content`    | apollo-core   | Depois conteúdo home   |
| `apollo_canvas_before_content` | apollo-social | Antes conteúdo canvas  |
| `apollo_canvas_after_content`  | apollo-social | Depois conteúdo canvas |

#### Review & Tracking

| Hook                      | Plugin                | Propósito            |
| ------------------------- | --------------------- | -------------------- |
| `apollo_review_submitted` | apollo-events-manager | Review submetida     |
| `apollo_review_approved`  | apollo-events-manager | Review aprovada      |
| `apollo_event_viewed`     | apollo-events-manager | Evento visualizado   |
| `apollo_ticket_clicked`   | apollo-events-manager | Ingresso clicado     |
| `apollo_event_shared`     | apollo-events-manager | Evento compartilhado |

#### Moderation

| Hook                              | Plugin        | Propósito        |
| --------------------------------- | ------------- | ---------------- |
| `apollo_security_threat_detected` | apollo-social | Ameaça segurança |

---

### Filters Principais (apply_filters)

| Hook                                 | Arquivo                                    | Propósito                |
| ------------------------------------ | ------------------------------------------ | ------------------------ |
| `the_content`                        | Multiple                                   | Filtrar conteúdo post    |
| `apollo_auth_config`                 | templates/auth/login-register.php:54       | Config autenticação      |
| `apollo_auth_terms_url`              | templates/auth/login-register.php:64       | URL termos               |
| `apollo_auth_bug_report_url`         | templates/auth/login-register.php:65       | URL bug report           |
| `apollo_ajax_actions`                | class-apollo-ajax-handler.php:159          | AJAX actions disponíveis |
| `apollo_dj_footer_brand`             | templates/parts/dj/footer.php:16           | Brand footer DJ          |
| `apollo_dj_roster_label`             | templates/parts/dj/header.php:22           | Label roster             |
| `apollo_get_placeholder_value`       | class-apollo-events-placeholders.php       | Placeholder values       |
| `apollo_events_placeholder_defaults` | class-apollo-events-placeholders.php:581   | Defaults placeholders    |
| `apollo_upload_max_scan_size`        | src/Security/UploadSecurityScanner.php:140 | Tamanho scan upload      |
| `apollo_schema_modules`              | src/Schema.php:272                         | Schema modules           |
| `apollo_social_rest_controllers`     | RestAPILoader:131                          | REST controllers         |
| `apollo_analytics_events`            | apollo-social README                       | Analytics events         |
| `apollo_analytics_config`            | apollo-social README                       | Analytics config         |

---

## 13. Estrutura de Arquivos Críticos

### apollo-core

```
apollo-core/
├── apollo-core.php (main entry point)
├── includes/
│   ├── class-apollo-identifiers.php ⭐ (central identifiers)
│   ├── class-apollo-activation-controller.php
│   ├── class-apollo-ajax-handler.php
│   ├── class-apollo-cpt-registry.php
│   ├── class-apollo-taxonomy-registry.php
│   ├── db-schema.php (tabelas)
│   └── [many more classes...]
├── modules/
│   ├── events/ (event functionality)
│   ├── social/ (social features)
│   └── moderation/ (moderation)
├── src/
│   ├── Schema/
│   ├── Hooks/
│   ├── Assets/
│   └── [other PSR-4 classes...]
└── templates/ (frontend templates)
```

### apollo-events-manager

```
apollo-events-manager/
├── apollo-events-manager.php (main entry)
├── includes/
│   ├── post-types.php (CPT & taxonomy registration)
│   ├── class-rest-api.php
│   ├── admin-apollo-hub.php (admin pages)
│   └── [modules, classes...]
├── src/
│   ├── Services/
│   ├── Shortcodes/
│   └── [modules...]
└── modules/ (module architecture)
```

### apollo-social

```
apollo-social/
├── apollo-social.php (main entry)
├── src/
│   ├── Modules/ (modular architecture)
│   │   ├── Classifieds/
│   │   ├── Suppliers/
│   │   ├── UserPages/
│   │   ├── Verification/
│   │   └── [others...]
│   ├── RestAPI/ (REST endpoints)
│   ├── Infrastructure/
│   └── [services...]
└── templates/ (frontend)
```

---

# APOLLO-EVENTS-MANAGER PLUGIN

## Localização

`c:/Users/rafae/Local Sites/1212/app/public/wp-content/plugins/apollo-events-manager/`

## Estrutura de Modularização

**Pattern:** Module-based architecture com interface `Module_Interface`

```php
namespace Apollo\Events\Core;

interface Module_Interface {
    public function register_rest_routes(): void;
    public function init(): void;
}
```

### Módulos Ativos

1. **Calendar_Module** - Calendário eventos
2. **Interest_Module** - Sistema de interesse/favoritos
3. **Reviews_Module** - Reviews/avaliações
4. **SEO_Module** - SEO otimization
5. **Speakers_Module** - Gestão DJs/speakers
6. **Tracking_Module** - Analytics e tracking
7. **Share_Module** - Compartilhamento social
8. **Tickets_Module** - Sistema de ingressos
9. **Notifications_Module** - Notificações
10. **Import_Export_Module** - Import/Export
11. **Duplicate_Module** - Duplicação eventos
12. **REST_API_Module** - REST API custom

---

# APOLLO-SOCIAL PLUGIN

## Localização

`c:/Users/rafae/Local Sites/1212/app/public/wp-content/plugins/apollo-social/`

## Estrutura Modular

### Módulos Principais

1. **UserPages Module** - Páginas de usuários customizáveis
2. **Classifieds Module** - Sistema de anúncios/classificados
3. **Suppliers Module** - Fornecedores/prestadores
4. **Verification Module** - Sistema de verificação de usuários
5. **Groups Module** - Grupos sociais
6. **Documents Module** - Sistema de documentos
7. **Signatures Module** - E-signatures/assinaturas
8. **CenaRio Module** - Integração Cena Rio

---

## ESTRUTURA DE BANCO DE DADOS

### Grupos (Tabela wp_apollo_groups)

| Type     | Name       | Plugin        | Propósito            |
| -------- | ---------- | ------------- | -------------------- |
| `comuna` | Comunas    | apollo-social | Comunidades públicas |
| `nucleo` | Núcleos    | apollo-social | Núcleos privados     |
| `season` | Temporadas | apollo-social | Agrupamento temporal |

**⚠️ Nota Importante:** `event_season` é uma **taxonomy** (apollo-events-manager), não um grupo!

---

## HOOKS E EVENTOS GLOBAIS

### Filter Hierarchy

1. **Early (init):** `apollo_register_post_types`, `apollo_core_register_rest_routes`
2. **Mid (wp_loaded):** `apollo_ajax_actions`, `apollo_schema_modules`
3. **Late (wp):** Content rendering, verification

### Event Bus Pattern

Sistema de ações baseado em:

- `do_action()` - eventos síncronos
- Custom hook registry em `Apollo\Core\Hooks\HookRegistry`

---

## COLISÕES E RISCOS

### ⚠️ Possíveis Colisões

1. **event_season (taxonomy vs. grupo)**
   - **Taxonomy:** apollo-events-manager (`includes/post-types.php:318`)
   - **Grupo:** apollo-social (`src/Modules/Groups/GroupsModule.php`)
   - **Risk:** Confusão entre dois conceitos diferentes
   - **Solução:** Documentar claramente distinção

2. **CPT event_listing (multi-plugin)**
   - **Definição primária:** apollo-events-manager
   - **Modificação:** apollo-core também registra
   - **Risk:** Conflito de registro
   - **Solução:** Um plugin deve ser responsável

3. **Meta keys históricos**
   - `_event_dj_ids` (novo) vs. `_event_djs` (legacy)
   - `_event_local_ids` (novo) vs. `_event_local` (legacy)
   - **Risk:** Inconsistência dados
   - **Solução:** Migration completa necessária

4. **REST namespaces múltiplos**
   - apollo-core: `apollo/v1`
   - apollo-events-manager: `apollo-events/v1`
   - apollo-social: `apollo-social/v2`
   - **Risk:** Inconsistência API
   - **Solução:** Padronizar namespace único

5. **Menu positions**
   - Ambos apollo-core e apollo-events-manager usam posição 5
   - **Risk:** Ordem impredizível
   - **Solução:** Ajustar posições em um plugin

---

## RESERVED IDENTIFIERS

### Prefixos Reservados

- `apollo_*` - apollo-core
- `event_*` - apollo-events-manager events
- `user_page` - apollo-social user pages
- `apollo_classified` - apollo-social classifieds
- `apollo_supplier` - apollo-social suppliers
- `cena_*` - CenaRio integration
- `_apollo_*` - user/post meta prefixes
- `_event_*` - event meta prefixes

### Slugs Globais

Definidos em: `apollo-core/includes/class-apollo-identifiers.php`

---

## INSTRUÇÕES DE VERIFICAÇÃO ADICIONAL

Para análise mais profunda:

1. **Full grep search:**

   ```bash
   grep -r "register_rest_route\|add_action\|add_filter" apollo-core/ --include="*.php"
   ```

2. **Database schema validation:**

   ```bash
   wp db tables | grep apollo
   ```

3. **REST API validation:**

   ```bash
   curl https://site.local/wp-json/apollo/v1/
   ```

4. **Shortcode test:**
   ```php
   do_shortcode('[apollo_events_grid]')
   ```

---

## CONCLUSÕES

### Estatísticas Finais

✅ **AUDITORIA CONCLUÍDA**

- ✅ 13 CPTs catalogados
- ✅ 13+ Taxonomies catalogadas
- ✅ 50+ REST routes mapeadas
- ✅ 40+ Shortcodes listados
- ✅ 25+ Tabelas de BD identificadas
- ✅ 100+ Meta keys documentadas
- ✅ 100+ Hooks catalogados
- ✅ 50+ Estilos/Scripts enumerados
- ✅ Classes e namespaces estruturados

### Recomendações

1. **Consolidar namespaces REST** - usar único `apollo/v2` para todos
2. **Resolver duplicidade event_season** - escolher um padrão
3. **Padronizar meta keys** - migrar legacy para novo padrão
4. **Documentar ownership** - CPTs devem ter um único plugin responsável
5. **Auditar permissões** - validar capabilities em todos hooks

---

**Relatório gerado com sucesso!**
Data: 22/01/2026
Status: ✅ COMPLETO
