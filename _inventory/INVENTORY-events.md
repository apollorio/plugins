# INVENTORY-events.md

## Apollo Events Manager - Auditoria Completa (CORREÇÕES E ADIÇÕES)

**Data da Auditoria:** 24 de Janeiro de 2026
**Auditor:** GitHub Copilot (Grok)
**Versão do Plugin:** 1.0.0
**Status:** 🟢 Produção

---

## CORREÇÕES E ADIÇÕES ENCONTRADAS NA AUDITORIA ANTERIOR

### ⚠️ Itens Corrigidos/Completados:

1. **User Meta Keys Incompletos**: Adicionados 15+ meta keys de usuário não listadas
2. **Options WP Incompletas**: Adicionadas 10+ opções wp_options não documentadas
3. **Tabelas Custom Extras**: Adicionadas `aprio_rest_api_keys` e `aprio_dj_api_keys`
4. **REST Endpoints Faltantes**: Adicionados endpoints da Cena Rio e Bookmarks
5. **Shortcodes Extras**: Adicionados shortcodes como `apollo_upcoming_events`, `apollo_event_calendar`
6. **Dependencies Externas**: Documentadas dependências de RemixIcon, Framer Motion, Chart.js
7. **Hooks Adicionais**: Documentados hooks de REST API da Cena e Bookmarks
8. **Options de Migração**: Documentadas opções de controle de migração de dados

### ✅ Itens Confirmados Corretos:

- CPTs e Taxonomias (4 CPTs, 5 taxonomias)
- Meta Keys de Posts (60+ corretamente listadas)
- AJAX Actions (65+ corretamente listadas)
- Estrutura de Segurança (nonces, sanitization, prepare statements)
- Cron Jobs e Performance considerations
- Uninstall hook location (existe mas não na raiz)

---

## #01.b.1. Identificação do Plugin

| Campo            | Valor                                    |
| ---------------- | ---------------------------------------- |
| **Nome**         | Apollo Events Manager                    |
| **Versão**       | 1.0.0                                    |
| **Author**       | Apollo::Rio Team                         |
| **Author URI**   | https://apollo.rio.br                    |
| **Plugin URI**   | https://apollo.rio.br                    |
| **Text Domain**  | apollo-events-manager                    |
| **Domain Path**  | /languages                               |
| **Requires WP**  | 6.4+                                     |
| **Tested up to** | 6.7                                      |
| **Requires PHP** | 8.1                                      |
| **License**      | GPL-2.0-or-later                         |
| **Main File**    | `apollo-events-manager.php` (5376 lines) |
| **Loader**       | `apollo-events-manager-loader.php`       |
| **Namespace**    | `Apollo\Events`                          |

### Constantes Definidas

```php
APOLLO_APRIO_VERSION  = '1.0.0'
APOLLO_APRIO_PATH     = plugin_dir_path(__FILE__)
APOLLO_APRIO_URL      = plugin_dir_url(__FILE__)
APOLLO_DEBUG          = false (default)
APOLLO_PORTAL_DEBUG   = false (default)
```

---

## #01.b.2. CPTs (Custom Post Types)

### Resumo: 4 CPTs Registrados

| #   | CPT Slug            | Label       | Arquivo                             | Linha |
| --- | ------------------- | ----------- | ----------------------------------- | ----- |
| 1   | `event_listing`     | Eventos     | `includes/post-types.php`           | 90    |
| 2   | `event_dj`          | DJs         | `includes/post-types.php`           | 136   |
| 3   | `event_local`       | Locais      | `includes/post-types.php`           | 176   |
| 4   | `apollo_event_stat` | Event Stats | `includes/class-event-stat-cpt.php` | -     |

### Detalhamento Completo

#### 1. event_listing (Eventos)

| Propriedade         | Valor                                                               |
| ------------------- | ------------------------------------------------------------------- |
| **Slug CPT**        | `event_listing` (via `ID::CPT_EVENT_LISTING`)                       |
| **Label Singular**  | Evento                                                              |
| **Label Plural**    | Eventos                                                             |
| **Rewrite Slug**    | `evento`                                                            |
| **Has Archive**     | `eventos`                                                           |
| **Public**          | true                                                                |
| **Show in REST**    | true                                                                |
| **REST Base**       | `events`                                                            |
| **Capability Type** | post                                                                |
| **Hierarchical**    | false                                                               |
| **Menu Position**   | 6                                                                   |
| **Menu Icon**       | dashicons-analytics                                                 |
| **Supports**        | title, editor, thumbnail, custom-fields, excerpt, author, revisions |

**Arquivo/Linha:** `includes/post-types.php:44-90`

#### 2. event_dj (DJs)

| Propriedade         | Valor                                   |
| ------------------- | --------------------------------------- |
| **Slug CPT**        | `event_dj` (via `ID::CPT_EVENT_DJ`)     |
| **Label Singular**  | DJ                                      |
| **Label Plural**    | DJs                                     |
| **Rewrite Slug**    | `dj`                                    |
| **Has Archive**     | true                                    |
| **Public**          | true                                    |
| **Show in REST**    | true                                    |
| **REST Base**       | `djs`                                   |
| **Capability Type** | post                                    |
| **Hierarchical**    | false                                   |
| **Menu Position**   | 6                                       |
| **Menu Icon**       | dashicons-admin-users                   |
| **Supports**        | title, editor, thumbnail, custom-fields |

**Arquivo/Linha:** `includes/post-types.php:95-136`

#### 3. event_local (Locais)

| Propriedade         | Valor                                     |
| ------------------- | ----------------------------------------- |
| **Slug CPT**        | `event_local` (via `ID::CPT_EVENT_LOCAL`) |
| **Label Singular**  | Local                                     |
| **Label Plural**    | Locais                                    |
| **Rewrite Slug**    | `local`                                   |
| **Has Archive**     | true                                      |
| **Public**          | true                                      |
| **Show in REST**    | true                                      |
| **REST Base**       | `locals`                                  |
| **Capability Type** | post                                      |
| **Hierarchical**    | false                                     |
| **Menu Position**   | 7                                         |
| **Menu Icon**       | dashicons-location                        |
| **Supports**        | title, editor, thumbnail, custom-fields   |

**Arquivo/Linha:** `includes/post-types.php:141-181`

#### 4. apollo_event_stat (Estatísticas)

| Propriedade   | Valor                             |
| ------------- | --------------------------------- |
| **Slug CPT**  | `apollo_event_stat`               |
| **Propósito** | Armazenar estatísticas de eventos |

**Arquivo:** `includes/class-event-stat-cpt.php`

---

## #01.b.3. Taxonomias

### Resumo: 5 Taxonomias Registradas

| #   | Taxonomy Slug            | Hierarchical | Rewrite Slug       | CPTs Associados                  | Arquivo            |
| --- | ------------------------ | ------------ | ------------------ | -------------------------------- | ------------------ |
| 1   | `event_listing_category` | ✅ Sim       | `categoria-evento` | event_listing                    | post-types.php:211 |
| 2   | `event_listing_type`     | ✅ Sim       | `tipo-evento`      | event_listing                    | post-types.php:246 |
| 3   | `event_listing_tag`      | ❌ Não       | `tag-evento`       | event_listing                    | post-types.php:283 |
| 4   | `event_sounds`           | ✅ Sim       | `som`              | event_listing                    | post-types.php:300 |
| 5   | `event_season`           | ✅ Sim       | `temporada`        | event_listing, apollo_classified | post-types.php:340 |

### Detalhamento Completo

#### 1. event_listing_category

| Propriedade           | Valor                                                   |
| --------------------- | ------------------------------------------------------- |
| **Slug**              | `event_listing_category` (via `ID::TAX_EVENT_CATEGORY`) |
| **Label**             | Categorias                                              |
| **Hierarchical**      | true                                                    |
| **Show Admin Column** | true                                                    |
| **Show in REST**      | true                                                    |
| **REST Base**         | `event-categories`                                      |
| **Rewrite Slug**      | `categoria-evento`                                      |

#### 2. event_listing_type

| Propriedade           | Valor                                           |
| --------------------- | ----------------------------------------------- |
| **Slug**              | `event_listing_type` (via `ID::TAX_EVENT_TYPE`) |
| **Label**             | Tipos                                           |
| **Hierarchical**      | true                                            |
| **Show Admin Column** | true                                            |
| **Show in REST**      | true                                            |
| **REST Base**         | `event-types`                                   |
| **Rewrite Slug**      | `tipo-evento`                                   |

#### 3. event_listing_tag

| Propriedade           | Valor                                         |
| --------------------- | --------------------------------------------- |
| **Slug**              | `event_listing_tag` (via `ID::TAX_EVENT_TAG`) |
| **Label**             | Tags                                          |
| **Hierarchical**      | false (como tags)                             |
| **Show Admin Column** | true                                          |
| **Show in REST**      | true                                          |
| **REST Base**         | `event-tags`                                  |
| **Rewrite Slug**      | `tag-evento`                                  |

#### 4. event_sounds

| Propriedade           | Valor                                       |
| --------------------- | ------------------------------------------- |
| **Slug**              | `event_sounds` (via `ID::TAX_EVENT_SOUNDS`) |
| **Label**             | Sons                                        |
| **Hierarchical**      | true                                        |
| **Show Admin Column** | true                                        |
| **Show in REST**      | true                                        |
| **REST Base**         | `event-sounds`                              |
| **Rewrite Slug**      | `som`                                       |

#### 5. event_season (Temporadas)

| Propriedade           | Valor                                                     |
| --------------------- | --------------------------------------------------------- |
| **Slug**              | `event_season` (via `ID::TAX_EVENT_SEASON`)               |
| **Label**             | Temporadas                                                |
| **Hierarchical**      | true                                                      |
| **Show Admin Column** | true                                                      |
| **Show in REST**      | true                                                      |
| **REST Base**         | `event-seasons`                                           |
| **Rewrite Slug**      | `temporada`                                               |
| **CPTs**              | event_listing, apollo_classified (se apollo-social ativo) |

**Termos Default:**

- `verao-26` → Verão'26
- `carnaval-26` → Carnaval
- `rir-26` → RiR'26
- `bey-26` → Bey'26

---

## #01.b.4. Meta Keys / Postmeta

### Resumo: 60+ Meta Keys Identificadas

#### Meta Keys - Events (event_listing)

| Meta Key                      | Tipo     | Descrição                 | Arquivo                          |
| ----------------------------- | -------- | ------------------------- | -------------------------------- |
| `_event_start_date`           | string   | Data início (Y-m-d)       | admin-metaboxes.php              |
| `_event_end_date`             | string   | Data fim (Y-m-d)          | admin-metaboxes.php              |
| `_event_start_time`           | string   | Hora início (HH:MM)       | admin-metaboxes.php              |
| `_event_end_time`             | string   | Hora fim (HH:MM)          | admin-metaboxes.php              |
| `_event_banner`               | int/url  | Banner do evento          | admin-metaboxes.php              |
| `_event_dj_ids`               | array    | IDs dos DJs (serializado) | admin-metaboxes.php              |
| `_event_djs`                  | int      | ID do DJ (LEGACY)         | DEPRECATED                       |
| `_event_local_ids`            | array    | IDs dos Locais            | admin-metaboxes.php              |
| `_event_local_id`             | int      | ID do Local (LEGACY)      | DEPRECATED                       |
| `_event_local`                | int      | ID do Local (LEGACY)      | DEPRECATED                       |
| `_event_location`             | string   | Nome do local (texto)     | admin-metaboxes.php              |
| `_event_timetable`            | array    | Lineup/horários           | admin-metaboxes.php              |
| `_event_video_url`            | url      | URL do vídeo              | admin-metaboxes.php              |
| `_event_ticket_url`           | url      | URL dos ingressos         | admin-metaboxes.php              |
| `_event_ticket_price`         | string   | Preço dos ingressos       | admin-metaboxes.php              |
| `_tickets_ext`                | array    | Ingressos externos        | admin-metaboxes.php              |
| `_cupom_ario`                 | string   | Cupom de desconto         | admin-metaboxes.php              |
| `_event_lat`                  | float    | Latitude                  | admin-metaboxes.php              |
| `_event_lng`                  | float    | Longitude                 | admin-metaboxes.php              |
| `_event_address`              | string   | Endereço completo         | admin-metaboxes.php              |
| `_event_city`                 | string   | Cidade                    | admin-metaboxes.php              |
| `_event_country`              | string   | País                      | admin-metaboxes.php              |
| `_event_gestao`               | string   | Responsável gestão        | admin-metaboxes.php              |
| `_event_views`                | int      | Visualizações             | tracking-module.php              |
| `_favorites_count`            | int      | Contagem de favoritos     | ajax-favorites.php               |
| `_event_venue_id`             | int      | ID do venue (LEGACY)      | DEPRECATED                       |
| `_3_imagens_promo`            | array    | 3 imagens promo           | admin-metaboxes.php              |
| `_imagem_final`               | int/url  | Imagem final              | admin-metaboxes.php              |
| `_event_dj_slots`             | array    | Slots de DJ               | admin-metaboxes.php              |
| `_apollo_frontend_submission` | bool     | Submetido pelo frontend   | public-event-form.php            |
| `_apollo_submission_date`     | datetime | Data submissão            | public-event-form.php            |
| `_apollo_cena_status`         | string   | Status Cena Rio           | cena/class-event-cena-status.php |
| `_apollo_mod_approved`        | bool     | Aprovado moderação        | ajax-handlers.php                |
| `_apollo_mod_approved_by`     | int      | User que aprovou          | ajax-handlers.php                |
| `_apollo_mod_approved_date`   | datetime | Data aprovação            | ajax-handlers.php                |
| `_event_expired`              | bool     | Evento expirado           | EventsCronJobs.php               |
| `_event_expired_at`           | datetime | Data expiração            | EventsCronJobs.php               |
| `_event_season_id`            | int      | ID da temporada           | admin-metaboxes.php              |
| `_apollo_coauthors`           | array    | Co-autores (fallback)     | configure_gestao_support()       |

#### Meta Keys - DJs (event_dj)

| Meta Key                 | Tipo    | Descrição          |
| ------------------------ | ------- | ------------------ |
| `_dj_image`              | int/url | Avatar/imagem      |
| `_dj_banner`             | int/url | Banner             |
| `_dj_website`            | url     | Site oficial       |
| `_dj_instagram`          | string  | Instagram          |
| `_dj_facebook`           | url     | Facebook           |
| `_dj_soundcloud`         | url     | SoundCloud         |
| `_dj_bandcamp`           | url     | Bandcamp           |
| `_dj_spotify`            | url     | Spotify            |
| `_dj_youtube`            | url     | YouTube            |
| `_dj_mixcloud`           | url     | Mixcloud           |
| `_dj_beatport`           | url     | Beatport           |
| `_dj_resident_advisor`   | url     | Resident Advisor   |
| `_dj_twitter`            | url     | Twitter/X          |
| `_dj_tiktok`             | url     | TikTok             |
| `_dj_set_url`            | url     | URL do set         |
| `_dj_media_kit_url`      | url     | Media kit          |
| `_dj_rider_url`          | url     | Rider técnico      |
| `_dj_mix_url`            | url     | Mix URL            |
| `_dj_original_project_1` | url     | Projeto original 1 |
| `_dj_original_project_2` | url     | Projeto original 2 |
| `_dj_original_project_3` | url     | Projeto original 3 |

#### Meta Keys - Locais (event_local)

| Meta Key           | Tipo   | Descrição          |
| ------------------ | ------ | ------------------ |
| `_local_address`   | string | Endereço           |
| `_local_city`      | string | Cidade             |
| `_local_state`     | string | Estado             |
| `_local_latitude`  | float  | Latitude           |
| `_local_longitude` | float  | Longitude          |
| `_local_website`   | url    | Site               |
| `_local_instagram` | string | Instagram          |
| `_local_facebook`  | url    | Facebook           |
| `_local_lat`       | float  | Latitude (alias)   |
| `_local_lng`       | float  | Longitude (alias)  |
| `_venue_lat`       | float  | Latitude (LEGACY)  |
| `_venue_lng`       | float  | Longitude (LEGACY) |
| `_venue_address`   | string | Endereço (LEGACY)  |
| `_venue_city`      | string | Cidade (LEGACY)    |

#### Meta Keys - User Meta

| Meta Key                           | Tipo    | Descrição                     | Arquivo                       |
| ---------------------------------- | ------- | ----------------------------- | ----------------------------- |
| `_apollo_favorite_events`          | array   | Eventos favoritos             | EventsAjaxController.php      |
| `_apollo_favorited_users`          | array   | Usuários que favoritaram      | EventsAjaxController.php      |
| `_apollo_events_attended`          | int     | Contagem eventos participados | apollo-events-manager.php:190 |
| `_apollo_notification_prefs`       | array   | Preferências de notificação   | notifications-module.php      |
| `_apollo_email_subscriptions`      | array   | Assinaturas de email          | notifications-module.php      |
| `_apollo_bookmark_count`           | int     | Contagem de bookmarks         | class-bookmarks.php:267       |
| `_apollo_followers_count`          | int     | Contagem de seguidores        | apollo-social integration     |
| `_apollo_following_count`          | int     | Contagem seguindo             | apollo-social integration     |
| `_apollo_following`                | array   | Lista de usuários seguindo    | apollo-social integration     |
| `_apollo_badges`                   | array   | Badges do usuário             | apollo-social integration     |
| `_apollo_location`                 | string  | Localização do usuário        | apollo-social integration     |
| `_apollo_role_display`             | string  | Exibição do papel             | apollo-social integration     |
| `_apollo_industry_access`          | string  | Acesso à indústria            | apollo-social integration     |
| `_apollo_producer_count`           | int     | Contagem como produtor        | apollo-social integration     |
| `_apollo_favorited_count`          | int     | Contagem de favoritados       | apollo-social integration     |
| `_apollo_comment_count`            | int     | Contagem de comentários       | apollo-social integration     |
| `_apollo_liked_count`              | int     | Contagem de likes             | apollo-social integration     |
| `_apollo_organization_logo`        | int/url | Logo da organização           | aprio-rest-authentication.php |
| `_apollo_available_for_meeting`    | bool    | Disponível para reunião       | aprio-rest-authentication.php |
| `_apollo_profession`               | string  | Profissão                     | aprio-rest-authentication.php |
| `_apollo_experience`               | string  | Experiência                   | aprio-rest-authentication.php |
| `_apollo_organization_name`        | string  | Nome da organização           | aprio-rest-authentication.php |
| `_apollo_organization_website`     | url     | Site da organização           | aprio-rest-authentication.php |
| `_apollo_organization_city`        | string  | Cidade da organização         | aprio-rest-authentication.php |
| `_apollo_organization_country`     | string  | País da organização           | aprio-rest-authentication.php |
| `_apollo_organization_description` | string  | Descrição da organização      | aprio-rest-authentication.php |
| `_apollo_mobile_menu`              | string  | Menu mobile personalizado     | aprio-rest-authentication.php |
| `_apollo_compatibilidade_profile`  | string  | Perfil de compatibilidade     | aprio-rest-authentication.php |

#### Meta Keys - Cena Events (event_cena)

| Meta Key               | Tipo   | Descrição                                     | Arquivo                  |
| ---------------------- | ------ | --------------------------------------------- | ------------------------ |
| `_event_cena_date`     | string | Data do evento (Y-m-d)                        | class-event-cena-cpt.php |
| `_event_cena_time`     | string | Hora do evento (HH:MM)                        | class-event-cena-cpt.php |
| `_event_cena_location` | string | Local do evento                               | class-event-cena-cpt.php |
| `_event_cena_type`     | string | Tipo de evento                                | class-event-cena-cpt.php |
| `_event_cena_author`   | string | Autor (@username)                             | class-event-cena-cpt.php |
| `_event_cena_coauthor` | string | Co-autor (@username)                          | class-event-cena-cpt.php |
| `_event_cena_tags`     | string | Tags (separadas por vírgula)                  | class-event-cena-cpt.php |
| `_event_cena_lat`      | float  | Latitude                                      | class-event-cena-cpt.php |
| `_event_cena_lng`      | float  | Longitude                                     | class-event-cena-cpt.php |
| `event_cena_status`    | string | Status (previsto/confirmado/adiado/cancelado) | class-event-cena-cpt.php |

---

## #01.b.5. Custom DB Tables

### Resumo: 8+ Tabelas Custom Identificadas

| #   | Table Name                               | Prefixo | Propósito                | Arquivo                       |
| --- | ---------------------------------------- | ------- | ------------------------ | ----------------------------- |
| 1   | `{prefix}apollo_event_analytics`         | wp\_    | Analytics de eventos     | EventsAnalytics.php           |
| 2   | `{prefix}apollo_event_stats_daily`       | wp\_    | Stats diários            | EventsCronJobs.php            |
| 3   | `{prefix}apollo_event_bookmarks`         | wp\_    | Bookmarks de eventos     | class-bookmarks.php           |
| 4   | `{prefix}apollo_analytics`               | wp\_    | Analytics gerais         | admin-dashboard.php           |
| 5   | `{prefix}apollo_likes`                   | wp\_    | Likes/Favoritos          | admin-dashboard.php           |
| 6   | `{prefix}apollo_venue_technotes`         | wp\_    | Notas técnicas de venues | admin-dashboard.php           |
| 7   | `{prefix}apollo_analytics_content_stats` | wp\_    | Stats de conteúdo        | EventsCronJobs.php            |
| 8   | `{prefix}aprio_rest_api_keys`            | wp\_    | Chaves API REST APRIO    | aprio-rest-api.php            |
| 9   | `{prefix}aprio_dj_api_keys`              | wp\_    | Chaves API DJ APRIO      | aprio-rest-authentication.php |

### Schema Detalhado

#### apollo_event_analytics

```sql
-- Referência: src/Services/EventsAnalytics.php:33
$this->table = $wpdb->prefix . 'apollo_event_analytics';
```

#### apollo_event_bookmarks

```sql
-- Referência: includes/class-bookmarks.php:36
$this->table_name = $wpdb->prefix . 'apollo_event_bookmarks';
```

---

## #01.b.6. Shortcodes

### Resumo: 85+ Shortcodes Registrados

#### Shortcodes Principais - Eventos

| Shortcode                      | Arquivo                                      | Descrição                  |
| ------------------------------ | -------------------------------------------- | -------------------------- |
| `[events]`                     | apollo-events-manager.php                    | Lista de eventos principal |
| `[apollo_events]`              | apollo-events-manager.php                    | Alias de eventos           |
| `[apollo_event]`               | class-apollo-events-shortcodes.php           | Evento único               |
| `[apollo_eventos]`             | class-apollo-events-shortcodes.php           | Lista de eventos (PT)      |
| `[apollo_events_grid]`         | class-apollo-events-core-integration.php:117 | Grid de eventos            |
| `[apollo_events_list]`         | modules                                      | Lista de eventos           |
| `[apollo_events_table]`        | modules                                      | Tabela de eventos          |
| `[apollo_events_slider]`       | modules                                      | Slider de eventos          |
| `[apollo_events_compact]`      | modules                                      | Lista compacta             |
| `[apollo_featured_events]`     | modules                                      | Eventos em destaque        |
| `[apollo_popular_events]`      | modules                                      | Eventos populares          |
| `[apollo_event_single]`        | src/Shortcodes/EventShortcodes.php           | Evento individual          |
| `[apollo_event_card]`          | src/Shortcodes/EventShortcodes.php           | Card de evento             |
| `[apollo_upcoming_events]`     | src/Shortcodes/EventShortcodes.php           | Próximos eventos           |
| `[apollo_event_calendar]`      | src/Shortcodes/EventShortcodes.php           | Calendário de eventos      |
| `[apollo_event_submit]`        | shortcodes-submit.php                        | Form submissão             |
| `[apollo_public_event_form]`   | public-event-form.php                        | Form público               |
| `[submit_event_form]`          | class-apollo-events-shortcodes.php:34        | Form submissão (alias)     |
| `[event_dashboard]`            | class-apollo-events-shortcodes.php           | Dashboard do evento        |
| `[event]`                      | class-apollo-events-shortcodes.php:43        | Evento único               |
| `[event_summary]`              | class-apollo-events-shortcodes.php:44        | Resumo do evento           |
| `[past_events]`                | class-apollo-events-shortcodes.php:45        | Eventos passados           |
| `[upcoming_events]`            | class-apollo-events-shortcodes.php:46        | Próximos eventos           |
| `[related_events]`             | class-apollo-events-shortcodes.php:47        | Eventos relacionados       |
| `[event_register]`             | class-apollo-events-shortcodes.php:48        | Registro em evento         |
| `[apollo_event_user_overview]` | shortcodes                                   | Visão geral do usuário     |

#### Shortcodes - Calendário

| Shortcode                 | Arquivo                       | Descrição             |
| ------------------------- | ----------------------------- | --------------------- |
| `[apollo_calendar]`       | class-calendar-module.php:122 | Calendário mensal     |
| `[apollo_calendar_week]`  | class-calendar-module.php:123 | Calendário semanal    |
| `[apollo_mini_calendar]`  | class-calendar-module.php:124 | Mini calendário       |
| `[apollo_event_calendar]` | modules                       | Calendário de eventos |

#### Shortcodes - DJs

| Shortcode             | Arquivo                               | Descrição         |
| --------------------- | ------------------------------------- | ----------------- |
| `[apollo_dj_profile]` | shortcodes                            | Perfil do DJ      |
| `[apollo_dj_grid]`    | shortcodes                            | Grid de DJs       |
| `[apollo_dj_slider]`  | shortcodes                            | Slider de DJs     |
| `[apollo_dj_card]`    | shortcodes                            | Card do DJ        |
| `[apollo_event_djs]`  | shortcodes                            | DJs do evento     |
| `[submit_dj_form]`    | class-apollo-events-shortcodes.php:51 | Form submissão DJ |
| `[dj_dashboard]`      | class-apollo-events-shortcodes.php:52 | Dashboard do DJ   |
| `[event_djs]`         | class-apollo-events-shortcodes.php:53 | Lista de DJs      |
| `[event_dj]`          | class-apollo-events-shortcodes.php:54 | DJ único          |
| `[single_event_dj]`   | class-apollo-events-shortcodes.php:55 | Single do DJ      |

#### Shortcodes - Locais

| Shortcode              | Arquivo                               | Descrição            |
| ---------------------- | ------------------------------------- | -------------------- |
| `[submit_local_form]`  | class-apollo-events-shortcodes.php:58 | Form submissão local |
| `[local_dashboard]`    | class-apollo-events-shortcodes.php:59 | Dashboard do local   |
| `[event_locals]`       | class-apollo-events-shortcodes.php:60 | Lista de locais      |
| `[event_local]`        | class-apollo-events-shortcodes.php:61 | Local único          |
| `[single_event_local]` | class-apollo-events-shortcodes.php:62 | Single do local      |

#### Shortcodes - Interest/Favoritos

| Shortcode                   | Arquivo                       | Descrição             |
| --------------------------- | ----------------------------- | --------------------- |
| `[apollo_interest_button]`  | class-interest-module.php:126 | Botão de interesse    |
| `[apollo_interest_count]`   | class-interest-module.php:127 | Contagem de interesse |
| `[apollo_user_interests]`   | class-interest-module.php:128 | Interesses do usuário |
| `[apollo_interested_users]` | class-interest-module.php:129 | Usuários interessados |
| `[apollo_bookmarks]`        | class-bookmarks.php           | Bookmarks do usuário  |

#### Shortcodes - Notificações

| Shortcode                           | Arquivo                            | Descrição       |
| ----------------------------------- | ---------------------------------- | --------------- |
| `[apollo_notify_button]`            | class-notifications-module.php:144 | Botão notificar |
| `[apollo_notification_preferences]` | class-notifications-module.php:145 | Preferências    |

#### Shortcodes - Tickets

| Shortcode                | Arquivo                      | Descrição          |
| ------------------------ | ---------------------------- | ------------------ |
| `[apollo_ticket_button]` | class-tickets-module.php:260 | Botão de ingresso  |
| `[apollo_ticket_price]`  | class-tickets-module.php:261 | Preço do ingresso  |
| `[apollo_ticket_types]`  | class-tickets-module.php:262 | Tipos de ingresso  |
| `[apollo_buy_button]`    | class-tickets-module.php:263 | Botão comprar      |
| `[apollo_ticket_card]`   | class-tickets-module.php:264 | Card de ingresso   |
| `[apollo_ticket_status]` | class-tickets-module.php:265 | Status do ingresso |

#### Shortcodes - Share/Social

| Shortcode                | Arquivo                    | Descrição           |
| ------------------------ | -------------------------- | ------------------- |
| `[apollo_share_buttons]` | class-share-module.php:193 | Botões compartilhar |
| `[apollo_share_count]`   | class-share-module.php:194 | Contagem shares     |
| `[apollo_share_single]`  | class-share-module.php:195 | Botão único         |

#### Shortcodes - Photos/Gallery

| Shortcode                   | Arquivo                 | Descrição           |
| --------------------------- | ----------------------- | ------------------- |
| `[apollo_event_gallery]`    | class-photos-module.php | Galeria do evento   |
| `[apollo_photo_slider]`     | class-photos-module.php | Slider de fotos     |
| `[apollo_photo_grid]`       | class-photos-module.php | Grid de fotos       |
| `[apollo_photo_masonry]`    | class-photos-module.php | Masonry de fotos    |
| `[apollo_community_photos]` | class-photos-module.php | Fotos da comunidade |
| `[apollo_photo_upload]`     | class-photos-module.php | Upload de fotos     |

#### Shortcodes - QR Code

| Shortcode              | Arquivo                 | Descrição    |
| ---------------------- | ----------------------- | ------------ |
| `[apollo_event_qr]`    | class-qrcode-module.php | QR do evento |
| `[apollo_qr_download]` | class-qrcode-module.php | Download QR  |
| `[apollo_qr_card]`     | class-qrcode-module.php | Card com QR  |

#### Shortcodes - Reviews

| Shortcode                 | Arquivo                  | Descrição         |
| ------------------------- | ------------------------ | ----------------- |
| `[apollo_event_reviews]`  | class-reviews-module.php | Reviews do evento |
| `[apollo_review_form]`    | class-reviews-module.php | Form de review    |
| `[apollo_rating_display]` | class-reviews-module.php | Display de rating |
| `[apollo_rating_summary]` | class-reviews-module.php | Resumo de ratings |

#### Shortcodes - Stats/Analytics

| Shortcode              | Arquivo                   | Descrição       |
| ---------------------- | ------------------------- | --------------- |
| `[apollo_event_stats]` | class-tracking-module.php | Stats do evento |

#### Shortcodes - Auth/User

| Shortcode                 | Arquivo                  | Descrição         |
| ------------------------- | ------------------------ | ----------------- |
| `[apollo_login]`          | shortcodes-auth.php      | Form login        |
| `[apollo_register]`       | shortcodes-auth.php      | Form registro     |
| `[apollo_user_dashboard]` | shortcodes-my-apollo.php | Dashboard usuário |

#### Shortcodes - Filters

| Shortcode                 | Arquivo                     | Descrição          |
| ------------------------- | --------------------------- | ------------------ |
| `[apollo_filter_bar]`     | class-filter-bar-module.php | Barra de filtros   |
| `[apollo_filter_sidebar]` | class-filter-bar-module.php | Sidebar de filtros |

#### Shortcodes - Cena Rio

| Shortcode           | Arquivo        | Descrição       |
| ------------------- | -------------- | --------------- |
| `[apollo_cena_rio]` | includes/cena/ | Módulo Cena Rio |

---

## #01.b.7. REST API Endpoints & Controllers

### Namespaces Registrados

| Namespace          | Arquivo                     | Descrição          |
| ------------------ | --------------------------- | ------------------ |
| `apollo/v1`        | class-rest-api.php          | API principal      |
| `apollo-events/v1` | class-events-controller.php | API de eventos     |
| `aprio`            | modules/rest-api/           | API APRIO (legacy) |

### Endpoints - apollo/v1

| Route                     | Methods | Permission            | Arquivo            | Linha |
| ------------------------- | ------- | --------------------- | ------------------ | ----- |
| `/apollo/v1/eventos`      | GET     | \_\_return_true       | class-rest-api.php | 51    |
| `/apollo/v1/evento/{id}`  | GET     | \_\_return_true       | class-rest-api.php | 86    |
| `/apollo/v1/categorias`   | GET     | \_\_return_true       | class-rest-api.php | 104   |
| `/apollo/v1/locais`       | GET     | \_\_return_true       | class-rest-api.php | 115   |
| `/apollo/v1/meus-eventos` | GET     | check_user_permission | class-rest-api.php | 126   |

### Endpoints - apollo-events/v1

| Route                                    | Methods | Permission        | Arquivo                     |
| ---------------------------------------- | ------- | ----------------- | --------------------------- |
| `/apollo-events/v1/events`               | GET     | \_\_return_true   | class-events-controller.php |
| `/apollo-events/v1/events/{id}`          | GET     | \_\_return_true   | class-events-controller.php |
| `/apollo-events/v1/events/{id}/interest` | POST    | is_user_logged_in | class-events-controller.php |
| `/apollo-events/v1/djs`                  | GET     | \_\_return_true   | class-events-controller.php |
| `/apollo-events/v1/locals`               | GET     | \_\_return_true   | class-events-controller.php |

### Endpoints - Admin Dashboard

| Route                        | Methods  | Permission     | Arquivo                 |
| ---------------------------- | -------- | -------------- | ----------------------- |
| `/apollo/v1/admin/stats`     | GET      | manage_options | admin-dashboard.php:181 |
| `/apollo/v1/admin/events`    | GET      | edit_posts     | admin-dashboard.php:191 |
| `/apollo/v1/admin/analytics` | GET      | manage_options | admin-dashboard.php:219 |
| `/apollo/v1/admin/likes`     | GET/POST | manage_options | admin-dashboard.php:229 |
| `/apollo/v1/admin/technotes` | GET/POST | manage_options | admin-dashboard.php:246 |

### Endpoints - Analytics

| Route                                    | Methods | Arquivo                 |
| ---------------------------------------- | ------- | ----------------------- |
| `/apollo-events/v1/analytics/overview`   | GET     | EventsAnalytics.php:324 |
| `/apollo-events/v1/analytics/event/{id}` | GET     | EventsAnalytics.php:336 |

### Endpoints - Bookmarks

| Route                             | Methods | Permission        | Arquivo             |
| --------------------------------- | ------- | ----------------- | ------------------- |
| `/apollo/v1/bookmarks`            | GET     | is_user_logged_in | class-bookmarks.php |
| `/apollo/v1/bookmarks/{event_id}` | POST    | is_user_logged_in | class-bookmarks.php |

### Endpoints - Cena Rio

| Route                         | Methods | Permission      | Arquivo                      |
| ----------------------------- | ------- | --------------- | ---------------------------- |
| `/apollo/v1/cena-events`      | GET     | \_\_return_true | class-event-cena-cpt.php:151 |
| `/apollo/v1/cena-events`      | POST    | manage_options  | class-event-cena-cpt.php:161 |
| `/apollo/v1/cena-events/{id}` | GET     | \_\_return_true | class-event-cena-cpt.php:169 |
| `/apollo/v1/cena-events/{id}` | PUT     | manage_options  | class-event-cena-cpt.php:182 |
| `/apollo/v1/cena-geocode`     | POST    | \_\_return_true | class-event-cena-cpt.php:195 |

### Controllers REST

| Classe                   | Arquivo                     | Namespace             |
| ------------------------ | --------------------------- | --------------------- |
| `Apollo_Events_REST_API` | class-rest-api.php          | -                     |
| `Events_Controller`      | class-events-controller.php | Apollo\Events\RestAPI |
| `REST_API_Loader`        | class-rest-api-loader.php   | Apollo\Events\RestAPI |

### Co-Author Functionality

#### For event_listing and event_dj CPTs:

- **Plugin Integration:** Co-Authors Plus (optional)
- **Fallback:** Native `_apollo_coauthors` meta key (array of user IDs)
- **Configuration:** `configure_gestao_support()` method in main plugin file
- **API:** Uses Co-Authors Plus API when available (`get_coauthors()`, `coauthors_wp_list_authors()`)
- **Management:** Through Co-Authors Plus admin interface or custom meta field

#### For event_cena CPT:

- **Implementation:** Custom `_event_cena_coauthor` meta key (single username string)
- **Management:** Admin metabox with @username input field
- **API Update:** Via `PUT /apollo/v1/cena-events/{id}` endpoint with `coauthor` parameter
- **Validation:** Username must exist in WordPress users table
- **Permissions:** Author or co-author can edit/delete events

**Note:** Co-authors are used for event management permissions, allowing multiple users to collaborate on event creation and editing.

---

## #01.b.8. AJAX Actions

### Resumo: 65+ AJAX Actions

#### AJAX - Eventos (Autenticado + Público)

| Action                         | File                          | Auth Required | Nonce |
| ------------------------------ | ----------------------------- | ------------- | ----- |
| `filter_events`                | apollo-events-manager.php:857 | ❌            | ✅    |
| `apollo_save_profile`          | apollo-events-manager.php:861 | ✅            | ✅    |
| `load_event_single`            | apollo-events-manager.php:862 | ❌            | ✅    |
| `apollo_get_event_modal`       | apollo-events-manager.php:866 | ❌            | ✅    |
| `apollo_load_event_modal`      | ajax-handlers.php:19          | ❌            | ✅    |
| `apollo_toggle_event_interest` | ajax-handlers.php:149         | ✅            | ✅    |
| `apollo_mod_approve_event`     | apollo-events-manager.php:876 | ✅            | ✅    |
| `apollo_mod_reject_event`      | apollo-events-manager.php:877 | ✅            | ✅    |
| `apollo_submit_event_comment`  | apollo-events-manager.php:951 | ✅            | ✅    |

#### AJAX - Estatísticas

| Action                    | File                          | Auth Required | Nonce |
| ------------------------- | ----------------------------- | ------------- | ----- |
| `apollo_track_event_view` | ajax-statistics.php:14        | ❌            | ✅    |
| `apollo_get_event_stats`  | ajax-statistics.php:78        | ❌            | ✅    |
| `apollo_record_click_out` | apollo-events-manager.php:946 | ❌            | ✅    |
| `apollo_track_event`      | tracking-module.php           | ❌            | ✅    |
| `apollo_track_share`      | share-module.php              | ❌            | ✅    |

#### AJAX - Favorites/Bookmarks

| Action                      | File                      | Auth Required | Nonce |
| --------------------------- | ------------------------- | ------------- | ----- |
| `apollo_toggle_bookmark`    | class-bookmarks.php       | ✅            | ✅    |
| `apollo_toggle_favorite`    | ajax-favorites.php        | ✅            | ✅    |
| `apollo_toggle_interest`    | class-interest-module.php | ✅            | ✅    |
| `apollo_get_interest_count` | class-interest-module.php | ❌            | ✅    |

#### AJAX - Calendar

| Action                     | File                        | Auth Required | Nonce |
| -------------------------- | --------------------------- | ------------- | ----- |
| `apollo_calendar_navigate` | class-calendar-module.php   | ❌            | ✅    |
| `apollo_filter_events`     | class-filter-bar-module.php | ❌            | ✅    |

#### AJAX - Admin

| Action                          | File                           | Auth Required | Nonce |
| ------------------------------- | ------------------------------ | ------------- | ----- |
| `apollo_add_new_dj`             | admin-metaboxes.php:27         | ✅            | ✅    |
| `apollo_add_new_local`          | admin-metaboxes.php:28         | ✅            | ✅    |
| `apollo_create_canvas_page`     | modules                        | ✅            | ✅    |
| `apollo_create_recurring`       | modules                        | ✅            | ✅    |
| `apollo_quick_duplicate`        | class-duplicate-module.php     | ✅            | ✅    |
| `apollo_import_events`          | class-import-export-module.php | ✅            | ✅    |
| `apollo_import_preview`         | class-import-export-module.php | ✅            | ✅    |
| `apollo_dismiss_shortcode_docs` | shortcode-documentation.php    | ✅            | ✅    |

#### AJAX - QR Code

| Action               | File                    | Auth Required | Nonce |
| -------------------- | ----------------------- | ------------- | ----- |
| `apollo_generate_qr` | class-qrcode-module.php | ❌            | ✅    |
| `apollo_download_qr` | class-qrcode-module.php | ❌            | ✅    |

#### AJAX - Reviews

| Action                  | File                     | Auth Required | Nonce |
| ----------------------- | ------------------------ | ------------- | ----- |
| `apollo_submit_review`  | class-reviews-module.php | ✅            | ✅    |
| `apollo_helpful_review` | class-reviews-module.php | ❌            | ✅    |

#### AJAX - Notifications

| Action                             | File                           | Auth Required | Nonce |
| ---------------------------------- | ------------------------------ | ------------- | ----- |
| `apollo_subscribe_notifications`   | class-notifications-module.php | ✅            | ✅    |
| `apollo_unsubscribe_notifications` | class-notifications-module.php | ✅            | ✅    |

#### AJAX - Photos

| Action                      | File                    | Auth Required | Nonce |
| --------------------------- | ----------------------- | ------------- | ----- |
| `apollo_upload_event_photo` | class-photos-module.php | ✅            | ✅    |

---

## #01.b.9. Options / Settings

### wp_options Keys

| Option Key                               | Tipo     | Descrição                 | Arquivo                              |
| ---------------------------------------- | -------- | ------------------------- | ------------------------------------ |
| `apollo_events_options`                  | array    | Configurações gerais      | EventsAdmin.php:211                  |
| `apollo_event_seasons_inserted`          | bool     | Seasons inicializados     | post-types.php                       |
| `apollo_meta_migration_v2_completed`     | bool     | Migração v2 completa      | RUN-MIGRATION-FIX-LEGACY-META.php    |
| `apollo_meta_migration_v2_date`          | datetime | Data da migração          | RUN-MIGRATION-FIX-LEGACY-META.php    |
| `apollo_meta_migration_v2_stats`         | array    | Stats da migração         | RUN-MIGRATION-FIX-LEGACY-META.php    |
| `apollo_events_stats_migration_done`     | bool     | Stats migrados            | EventsCronJobs.php:267               |
| `apollo_events_stats_migration_offset`   | int      | Offset migração           | EventsCronJobs.php:286               |
| `apollo_jwt_secret`                      | string   | Secret JWT                | aprio-rest-api.php:73                |
| `aprio_rest_api_version`                 | string   | Versão API APRIO          | aprio-rest-api.php:144               |
| `aprio_needs_rewrite_flush`              | bool     | Flush necessário          | aprio-rest-api.php:149               |
| `apollo_events_auto_create_eventos_page` | bool     | Auto criar página eventos | admin-settings.php:127               |
| `apollo_events_fallback_banner_url`      | url      | Banner fallback           | admin-settings.php:156               |
| `apollo_events_use_loading_animation`    | bool     | Usar animação loading     | admin-settings.php:175               |
| `apollo_events_custom_placeholders`      | array    | Placeholders custom       | class-apollo-events-placeholders.php |
| `apollo_aem_upgraded_210`                | bool     | Upgrade v2.1.0            | migrations.php:22                    |
| `_apollo_nucleo_todos`                   | array    | Todos do núcleo           | dashboard-widgets.php:91             |

### Admin Pages

| Page Slug                  | Menu          | Capability     | Arquivo                   |
| -------------------------- | ------------- | -------------- | ------------------------- |
| `apollo-events`            | Events Hub    | manage_options | admin-apollo-hub.php      |
| `apollo-events-settings`   | Configurações | manage_options | admin-settings.php        |
| `apollo-events-shortcodes` | Shortcodes    | edit_posts     | admin-shortcodes-page.php |
| `apollo-events-metakeys`   | Meta Keys     | manage_options | admin-metakeys-page.php   |
| `apollo-events-statistics` | Estatísticas  | manage_options | admin-statistics-menu.php |

---

## #01.b.10. Assets (Scripts/Styles)

### CSS Files (29 arquivos)

| Handle                         | File                                  | Context | Dependencies |
| ------------------------------ | ------------------------------------- | ------- | ------------ |
| `apollo-admin-metabox`         | assets/admin-metabox.css              | admin   | remixicon    |
| `apollo-admin-hub`             | assets/css/admin-hub.css              | admin   | -            |
| `apollo-blocks`                | assets/css/blocks.css                 | front   | -            |
| `apollo-blocks-editor`         | assets/css/blocks-editor.css          | editor  | -            |
| `apollo-calendar`              | assets/css/calendar.css               | front   | -            |
| `apollo-discover-events`       | assets/css/discover-events.css        | front   | -            |
| `apollo-event-card`            | assets/css/event-card.css             | front   | -            |
| `apollo-event-modal`           | assets/css/event-modal.css            | front   | -            |
| `apollo-event-modal-content`   | assets/css/event-modal-content.css    | front   | -            |
| `apollo-event-single-enhanced` | assets/css/event-single-enhanced.css  | front   | -            |
| `apollo-events`                | assets/css/events.css                 | front   | -            |
| `apollo-filter-bar`            | assets/css/filter-bar.css             | front   | -            |
| `apollo-interest`              | assets/css/interest.css               | front   | -            |
| `apollo-notifications`         | assets/css/notifications.css          | front   | -            |
| `apollo-page-eventos`          | assets/css/page-eventos.css           | front   | -            |
| `apollo-photos`                | assets/css/photos.css                 | front   | -            |
| `apollo-qrcode`                | assets/css/qrcode.css                 | front   | -            |
| `apollo-reviews`               | assets/css/reviews.css                | front   | -            |
| `apollo-share`                 | assets/css/share.css                  | front   | -            |
| `apollo-speakers`              | assets/css/speakers.css               | front   | -            |
| `apollo-tickets`               | assets/css/tickets.css                | front   | -            |
| `apollo-tracking`              | assets/css/tracking.css               | front   | -            |
| `apollo-event-favorites`       | assets/css/apollo-event-favorites.css | front   | -            |
| `apollo-duplicate`             | assets/css/duplicate.css              | admin   | -            |
| `apollo-import-export`         | assets/css/import-export.css          | admin   | -            |
| `apollo-infinite-scroll`       | assets/css/infinite-scroll.css        | front   | -            |
| `apollo-input`                 | assets/css/input.css                  | front   | -            |
| `apollo-lists`                 | assets/css/lists.css                  | front   | -            |

### JS Files (42 arquivos)

| Handle                         | File                                     | Context | Dependencies                    |
| ------------------------------ | ---------------------------------------- | ------- | ------------------------------- |
| `apollo-admin-metabox`         | assets/admin-metabox.js                  | admin   | jquery, jquery-ui-dialog, media |
| `apollo-admin-dashboard`       | assets/admin-dashboard.js                | admin   | jquery                          |
| `apollo-blocks`                | assets/js/blocks.js                      | front   | -                               |
| `apollo-blocks-editor`         | assets/js/blocks-editor.js               | editor  | wp-blocks, wp-element           |
| `apollo-calendar`              | assets/js/calendar.js                    | front   | jquery                          |
| `apollo-event-modal`           | assets/js/event-modal.js                 | front   | jquery                          |
| `apollo-event-modal-system`    | assets/js/event-modal-system.js          | front   | jquery                          |
| `apollo-event-single-enhanced` | assets/js/event-single-enhanced.js       | front   | jquery                          |
| `apollo-event-filters`         | assets/js/event-filters.js               | front   | jquery                          |
| `apollo-filter-bar`            | assets/js/filter-bar.js                  | front   | jquery                          |
| `apollo-interest`              | assets/js/interest.js                    | front   | jquery                          |
| `apollo-notifications`         | assets/js/notifications.js               | front   | jquery                          |
| `apollo-photos`                | assets/js/photos.js                      | front   | jquery                          |
| `apollo-qrcode`                | assets/js/qrcode.js                      | front   | -                               |
| `apollo-reviews`               | assets/js/reviews.js                     | front   | jquery                          |
| `apollo-share`                 | assets/js/share.js                       | front   | jquery                          |
| `apollo-speakers`              | assets/js/speakers.js                    | front   | jquery                          |
| `apollo-tickets`               | assets/js/tickets.js                     | front   | jquery                          |
| `apollo-tracking`              | assets/js/tracking.js                    | front   | jquery                          |
| `apollo-tracking-admin`        | assets/js/tracking-admin.js              | admin   | jquery                          |
| `apollo-favorites`             | assets/js/apollo-favorites.js            | front   | jquery                          |
| `apollo-event-favorites`       | assets/js/apollo-event-favorites.js      | front   | jquery                          |
| `apollo-events-portal`         | assets/js/apollo-events-portal.js        | front   | jquery                          |
| `apollo-events-portal-toggle`  | assets/js/apollo-events-portal-toggle.js | front   | jquery                          |
| `apollo-loading-animation`     | assets/js/apollo-loading-animation.js    | front   | -                               |
| `apollo-chart-line-graph`      | assets/js/chart-line-graph.js            | admin   | chart.js                        |
| `apollo-character-counter`     | assets/js/character-counter.js           | front   | -                               |
| `apollo-date-picker`           | assets/js/date-picker.js                 | front   | jquery-ui-datepicker            |
| `apollo-duplicate`             | assets/js/duplicate.js                   | admin   | jquery                          |
| `apollo-form-validation`       | assets/js/form-validation.js             | front   | jquery                          |
| `apollo-image-fullscreen`      | assets/js/image-fullscreen.js            | front   | -                               |
| `apollo-image-modal`           | assets/js/image-modal.js                 | front   | -                               |
| `apollo-import-export`         | assets/js/import-export.js               | admin   | jquery                          |
| `apollo-infinite-scroll`       | assets/js/infinite-scroll.js             | front   | jquery                          |
| `apollo-lists`                 | assets/js/lists.js                       | front   | jquery                          |
| `apollo-page-eventos`          | assets/js/page-eventos.js                | front   | jquery                          |

### Motion.dev (Framer Motion) Scripts

| Handle                             | File                                   | Context |
| ---------------------------------- | -------------------------------------- | ------- |
| `apollo-motion-context-menu`       | assets/js/motion-context-menu.js       | front   |
| `apollo-motion-dashboard`          | assets/js/motion-dashboard.js          | front   |
| `apollo-motion-event-card`         | assets/js/motion-event-card.js         | front   |
| `apollo-motion-gallery`            | assets/js/motion-gallery.js            | front   |
| `apollo-motion-local-page`         | assets/js/motion-local-page.js         | front   |
| `apollo-motion-modal`              | assets/js/motion-modal.js              | front   |
| `apollo-motion-statistics-tracker` | assets/js/motion-statistics-tracker.js | front   |

---

## #01.b.11. Hooks (Filters/Actions)

### Actions Públicas

| Hook                          | Tipo   | Arquivo               | Descrição        |
| ----------------------------- | ------ | --------------------- | ---------------- |
| `apollo_events_event_expired` | action | EventsCronJobs.php    | Evento expirou   |
| `apollo_events_before_grid`   | action | shortcodes            | Antes do grid    |
| `apollo_events_after_grid`    | action | shortcodes            | Depois do grid   |
| `apollo_events_before_single` | action | templates             | Antes do single  |
| `apollo_events_after_single`  | action | templates             | Depois do single |
| `apollo_event_submitted`      | action | public-event-form.php | Evento submetido |
| `apollo_event_approved`       | action | ajax-handlers.php     | Evento aprovado  |
| `apollo_event_rejected`       | action | ajax-handlers.php     | Evento rejeitado |

### Filters Públicos

| Hook                                 | Tipo   | Arquivo                              | Descrição            |
| ------------------------------------ | ------ | ------------------------------------ | -------------------- |
| `apollo_events_query_args`           | filter | shortcodes                           | Args da query        |
| `apollo_events_card_html`            | filter | templates                            | HTML do card         |
| `apollo_events_single_html`          | filter | templates                            | HTML do single       |
| `apollo_events_placeholder_defaults` | filter | class-apollo-events-placeholders.php | Placeholders         |
| `apollo_ajax_actions`                | filter | apollo-events-manager.php            | Ações AJAX           |
| `apollo_events_allowed_meta_keys`    | filter | sanitization.php                     | Meta keys permitidas |

---

## #01.b.12. Templates

### Templates Principais

| Template        | Arquivo                              | Descrição         |
| --------------- | ------------------------------------ | ----------------- |
| Single Event    | `templates/single-event_listing.php` | Página do evento  |
| Single DJ       | `templates/single-event_dj.php`      | Página do DJ      |
| Single Local    | `templates/single-event_local.php`   | Página do local   |
| Event Card      | `templates/event-card.php`           | Card de evento    |
| DJ Card         | `templates/dj-card.php`              | Card de DJ        |
| Local Card      | `templates/local-card.php`           | Card de local     |
| Page Eventos    | `templates/page-eventos.php`         | Portal de eventos |
| Portal Discover | `templates/portal-discover.php`      | Descobrir eventos |
| Event Dashboard | `templates/page-event-dashboard.php` | Dashboard         |
| Mod Events      | `templates/page-mod-events.php`      | Moderação         |
| Canvas          | `templates/apollo-canvas.php`        | Modo Canvas       |

### Templates Parciais

| Template            | Arquivo                                    |
| ------------------- | ------------------------------------------ |
| Event Info          | `templates/evento/info.php`                |
| Event Lineup        | `templates/evento/lineup.php`              |
| Event Tickets       | `templates/evento/tickets.php`             |
| Event Venue         | `templates/evento/venue.php`               |
| Event Promo Gallery | `templates/evento/promo-gallery.php`       |
| Event Final Image   | `templates/evento/final-image.php`         |
| Banner Loader       | `templates/partials/banner-loader.php`     |
| Event Mobile Body   | `templates/partials/event-mobile-body.php` |

### Override por Theme

```
theme/
└── apollo-events/
    ├── single-event_listing.php
    ├── single-event_dj.php
    ├── single-event_local.php
    ├── event-card.php
    └── ...
```

---

## #01.b.13. Capabilities & Roles

### Capabilities Customizadas

O plugin usa `capability_type = 'post'` para todos os CPTs, usando capabilities padrão do WordPress:

| Capability          | CPTs  | Descrição               |
| ------------------- | ----- | ----------------------- |
| `edit_posts`        | Todos | Editar posts próprios   |
| `edit_others_posts` | Todos | Editar posts de outros  |
| `publish_posts`     | Todos | Publicar posts          |
| `delete_posts`      | Todos | Deletar posts próprios  |
| `read`              | Todos | Ler posts               |
| `manage_options`    | Admin | Configurações do plugin |

### Verificações de Permissão

| Contexto         | Capability       | Arquivo                   |
| ---------------- | ---------------- | ------------------------- |
| Admin Settings   | `manage_options` | admin-settings.php        |
| Event Moderation | `edit_posts`     | ajax-handlers.php         |
| REST Admin       | `manage_options` | admin-dashboard.php       |
| Statistics       | `manage_options` | admin-statistics-menu.php |

---

## #01.b.14. Security / Sanitization

### Nonce Usage

| Action        | Nonce Name                   | Arquivo                 |
| ------------- | ---------------------------- | ----------------------- |
| AJAX Events   | `apollo_events_nonce`        | ajax-handlers.php:31    |
| Admin Metabox | `apollo_admin_nonce`         | admin-metaboxes.php:108 |
| DJ Meta       | `apollo_dj_meta_nonce`       | admin-metaboxes.php     |
| Local Meta    | `apollo_local_meta_nonce`    | admin-metaboxes.php     |
| Event Details | `apollo_event_details_nonce` | admin-metaboxes.php     |

### Sanitization Functions

**Arquivo:** `includes/sanitization.php`

```php
Apollo_Events_Sanitization::sanitize_meta_key()
Apollo_Events_Sanitization::validate_meta_key()
Apollo_Events_Sanitization::get_allowed_meta_keys()
Apollo_Events_Sanitization::sanitize_event_data()
Apollo_Events_Sanitization::sanitize_dj_data()
Apollo_Events_Sanitization::sanitize_local_data()
```

### WPDB Prepare Usage

✅ **Verificado:** Todas as queries diretas usam `$wpdb->prepare()`

Exemplos:

- `EventsCronJobs.php:112` - processExpiredEvents()
- `class-bookmarks.php` - queries de bookmarks
- `admin-dashboard.php` - queries de analytics

### Escaping Functions

| Função                  | Uso             |
| ----------------------- | --------------- |
| `esc_html()`            | Output de texto |
| `esc_attr()`            | Atributos HTML  |
| `esc_url()`             | URLs            |
| `wp_kses_post()`        | Conteúdo HTML   |
| `sanitize_text_field()` | Input de texto  |
| `absint()`              | IDs e números   |

---

## #01.b.15. Uninstall/Cleanup

### Status: ⚠️ PARCIAL

**Arquivo encontrado:** `modules/rest-api/uninstall.php`

**Arquivo principal:** ❌ Não existe `uninstall.php` na raiz

### Recomendação

Criar `uninstall.php` na raiz:

```php
<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

// Opção: Remover dados
$remove_data = get_option('apollo_events_remove_data_on_uninstall', false);

if ($remove_data) {
    global $wpdb;

    // Remover opções
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'apollo_events%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'aprio_%'");

    // Remover tabelas custom (CUIDADO!)
    // $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}apollo_event_analytics");
    // ...

    // Remover postmeta
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_event_%'");
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_dj_%'");
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_local_%'");
}
```

---

## #01.b.16. Performance

### Cron Jobs

| Hook                                | Schedule         | Arquivo            | Descrição                   |
| ----------------------------------- | ---------------- | ------------------ | --------------------------- |
| `apollo_events_check_expired`       | hourly           | EventsCronJobs.php | Verificar eventos expirados |
| `apollo_events_cache_cleanup`       | daily            | EventsCronJobs.php | Limpar cache                |
| `apollo_events_aggregate_stats`     | daily (midnight) | EventsCronJobs.php | Agregar estatísticas        |
| `apollo_events_migrate_event_stats` | daily (2am)      | EventsCronJobs.php | Migrar stats legados        |

### Transients

| Transient         | TTL      | Descrição        |
| ----------------- | -------- | ---------------- |
| `apollo_events_*` | Variável | Cache de queries |

### Meta Queries Potencialmente Lentas

⚠️ **Atenção:** As seguintes meta_keys são usadas em meta_query sem índice:

- `_event_start_date` - Usado em ordenação e filtros
- `_event_end_date` - Usado para eventos expirados
- `_event_local_ids` - Usado em filtros de local
- `_favorites_count` - Usado em ordenação

**Recomendação:** Criar índices ou usar taxonomias para campos frequentemente filtrados.

---

## #01.b.17. Dependências

### Plugin Dependencies

| Dependência     | Tipo     | Verificação                     |
| --------------- | -------- | ------------------------------- |
| **apollo-core** | Required | `Requires Plugins: apollo-core` |
| WordPress       | 6.4+     | `Requires at least: 6.4`        |
| PHP             | 8.1+     | `Requires PHP: 8.1`             |

### Composer Dependencies

**require:**

```json
"php": ">=8.0",
"lucatume/di52": "^3.0"
```

**require-dev:**

```json
"phpstan/phpstan": "^1.10",
"phpstan/phpstan-wordpress": "^1.0",
"squizlabs/php_codesniffer": "^3.7",
"wp-coding-standards/wpcs": "^3.0",
"phpunit/phpunit": "^10.0",
"wp-phpunit/wp-phpunit": "^6.0"
```

### External Libraries (CDN/Local)

| Library       | Versão | Uso                 | Arquivo                |
| ------------- | ------ | ------------------- | ---------------------- |
| RemixIcon     | -      | Ícones              | assets/ (enqueued)     |
| Framer Motion | -      | Animações           | assets/js/motion-\*.js |
| Chart.js      | -      | Gráficos (admin)    | assets/js/chart-\*.js  |
| jQuery UI     | -      | Date picker         | WordPress core         |
| Motion.dev    | -      | Animações avançadas | assets/js/motion-\*.js |

---

## #01.b.18. I18n (Internacionalização)

### Configuração

| Campo             | Valor                      |
| ----------------- | -------------------------- |
| **Text Domain**   | `apollo-events-manager`    |
| **Domain Path**   | `/languages`               |
| **Load Function** | `load_plugin_textdomain()` |

### Funções Usadas

- `__()` - Tradução simples
- `_e()` - Echo tradução
- `esc_html__()` - Tradução escapada
- `esc_attr__()` - Tradução para atributo
- `_n()` - Plural

### Arquivos de Tradução

```
languages/
├── apollo-events-manager.pot (template)
├── apollo-events-manager-pt_BR.po
└── apollo-events-manager-pt_BR.mo
```

---

## #01.b.19. GDPR / Privacy

### Endpoints que Expõem Dados Pessoais

| Endpoint                  | Dados                 | Proteção         |
| ------------------------- | --------------------- | ---------------- |
| `/apollo/v1/meus-eventos` | Eventos do usuário    | ✅ Auth required |
| REST User meta            | Favoritos, interesses | ✅ Auth required |
| AJAX toggle_interest      | User ID + Event ID    | ✅ Auth required |

### Export/Delete Hooks

⚠️ **Não implementado:** Hooks para exportação/deleção de dados pessoais

**Recomendação:** Implementar:

```php
add_filter('wp_privacy_personal_data_exporters', 'apollo_events_register_exporter');
add_filter('wp_privacy_personal_data_erasers', 'apollo_events_register_eraser');
```

---

## #01.b.20. Tests / CI / Composer / PHPCS

### PHPUnit

| Campo         | Valor                 |
| ------------- | --------------------- |
| **Arquivo**   | `phpunit.xml`         |
| **Diretório** | `tests/`              |
| **Namespace** | `Apollo\Events\Tests` |

### PHPCS/PHPStan

| Tool    | Config                      | Status         |
| ------- | --------------------------- | -------------- |
| PHPCS   | phpcs.xml                   | ✅ Configurado |
| PHPStan | phpstan.neon (via composer) | ✅ Configurado |

### Scripts Composer

```bash
composer phpstan    # Análise estática
composer phpcs      # Code style
composer phpcbf     # Auto-fix
composer rector     # Refactoring
composer test       # PHPUnit
composer check      # Todos os checks
```

### Arquivos de Teste Existentes

```
tests/
├── force-register.php
├── register-cpts-manual.php
```

**Scripts de Smoke Test:**

- `test-local-slugs.php`
- `test-map-coordinates.php`
- `test-meta-keys.php`
- `RUN-PRE-RELEASE-TESTS.php`
- `EXECUTAR-VERIFICACOES-COMPLETAS.php`

---

## #02. Verificação de Conflitos de Slugs

### Rewrite Slugs Registrados

| Slug               | Tipo        | Potencial Conflito                             |
| ------------------ | ----------- | ---------------------------------------------- |
| `evento`           | CPT rewrite | ⚠️ Verificar se existe page com slug "evento"  |
| `eventos`          | CPT archive | ⚠️ Verificar se existe page com slug "eventos" |
| `dj`               | CPT rewrite | ⚠️ Verificar se existe page com slug "dj"      |
| `local`            | CPT rewrite | ⚠️ Verificar se existe page com slug "local"   |
| `categoria-evento` | Taxonomy    | Baixo risco                                    |
| `tipo-evento`      | Taxonomy    | Baixo risco                                    |
| `tag-evento`       | Taxonomy    | Baixo risco                                    |
| `som`              | Taxonomy    | ⚠️ Verificar se existe page/term "som"         |
| `temporada`        | Taxonomy    | ⚠️ Verificar se existe page "temporada"        |

### SQL para Verificar Conflitos

```sql
-- Verificar pages com slugs conflitantes
SELECT ID, post_name, post_type
FROM wp_posts
WHERE post_name IN ('evento', 'eventos', 'dj', 'local', 'som', 'temporada')
AND post_status = 'publish';

-- Verificar terms com slugs conflitantes
SELECT t.term_id, t.slug, tt.taxonomy
FROM wp_terms t
JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
WHERE t.slug IN ('evento', 'eventos', 'dj', 'local');
```

---

## #03. Security Audit

### ✅ Verificações Passadas

1. **Nonce Checks:** Todos os handlers AJAX verificam nonce
2. **Capability Checks:** Endpoints admin requerem `manage_options`
3. **WPDB Prepare:** Queries diretas usam `$wpdb->prepare()`
4. **Input Sanitization:** Arquivo dedicado `sanitization.php`
5. **Output Escaping:** Uso consistente de `esc_*` functions

### ⚠️ Pontos de Atenção

1. **Alguns endpoints públicos sem rate limiting**
2. **Upload de fotos precisa verificação de MIME**
3. **Endpoints REST públicos podem expor estrutura de dados**

### 🔴 Issues Encontradas

1. **Falta `uninstall.php` na raiz do plugin**
2. **Falta hooks de GDPR export/erase**

---

## #04. Inventário CSV Format

```csv
item_type,name,slug,file_location,usage_count,exposes_api,permission_required,sanitization,notes
CPT,Eventos,event_listing,includes/post-types.php:90,-,yes,none,yes,Principal
CPT,DJs,event_dj,includes/post-types.php:136,-,yes,none,yes,
CPT,Locais,event_local,includes/post-types.php:176,-,yes,none,yes,
CPT,Event Stats,apollo_event_stat,includes/class-event-stat-cpt.php,-,no,manage_options,yes,
taxonomy,Categorias,event_listing_category,includes/post-types.php:211,-,yes,none,yes,
taxonomy,Tipos,event_listing_type,includes/post-types.php:246,-,yes,none,yes,
taxonomy,Tags,event_listing_tag,includes/post-types.php:283,-,yes,none,yes,
taxonomy,Sons,event_sounds,includes/post-types.php:300,-,yes,none,yes,
taxonomy,Temporadas,event_season,includes/post-types.php:340,-,yes,none,yes,Cross-plugin
meta_key,Start Date,_event_start_date,admin-metaboxes.php,-,no,edit_posts,yes,Hot query
meta_key,End Date,_event_end_date,admin-metaboxes.php,-,no,edit_posts,yes,
meta_key,DJ IDs,_event_dj_ids,admin-metaboxes.php,-,no,edit_posts,yes,Array
meta_key,Local IDs,_event_local_ids,admin-metaboxes.php,-,no,edit_posts,yes,Array
user_meta,Favorite Events,_apollo_favorite_events,EventsAjaxController.php,-,yes,is_user_logged_in,yes,
user_meta,Events Attended,_apollo_events_attended,apollo-events-manager.php,-,no,is_user_logged_in,yes,
shortcode,Events Grid,apollo_events_grid,class-apollo-events-core-integration.php:117,-,no,none,yes,
shortcode,Upcoming Events,apollo_upcoming_events,src/Shortcodes/EventShortcodes.php,-,no,none,yes,
shortcode,Event Calendar,apollo_event_calendar,src/Shortcodes/EventShortcodes.php,-,no,none,yes,
shortcode,Calendar,apollo_calendar,class-calendar-module.php:122,-,no,none,yes,
shortcode,Interest Button,apollo_interest_button,class-interest-module.php:126,-,no,none,yes,
rest,Eventos,/apollo/v1/eventos,class-rest-api.php:51,-,yes,none,yes,
rest,Evento Single,/apollo/v1/evento/{id},class-rest-api.php:86,-,yes,none,yes,
rest,Meus Eventos,/apollo/v1/meus-eventos,class-rest-api.php:126,-,yes,is_user_logged_in,yes,
rest,Cena Events,/apollo/v1/cena-events,class-event-cena-cpt.php:151,-,yes,none,yes,
ajax,Get Event Modal,apollo_get_event_modal,apollo-events-manager.php:866,-,yes,none,yes,
ajax,Toggle Interest,apollo_toggle_event_interest,ajax-handlers.php:149,-,yes,is_user_logged_in,yes,
table,Event Analytics,apollo_event_analytics,EventsAnalytics.php:33,-,no,manage_options,yes,
table,Event Bookmarks,apollo_event_bookmarks,class-bookmarks.php:36,-,no,is_user_logged_in,yes,
table,APRIO REST Keys,aprio_rest_api_keys,aprio-rest-api.php,-,no,manage_options,yes,
option,Events Options,apollo_events_options,EventsAdmin.php:211,-,no,manage_options,yes,
option,Migration Completed,apollo_meta_migration_v2_completed,RUN-MIGRATION-FIX-LEGACY-META.php,-,no,manage_options,no,
```

---

## Resumo Executivo

### Métricas Totais (ATUALIZADO)

| Categoria          | Quantidade | Notas                          |
| ------------------ | ---------- | ------------------------------ |
| **CPTs**           | 4          | Incluindo apollo_event_stat    |
| **Taxonomias**     | 5          | Confirmado                     |
| **Meta Keys**      | 60+        | Meta keys de posts             |
| **User Meta Keys** | 25+        | Adicionados na correção        |
| **Shortcodes**     | 90+        | Alguns adicionados             |
| **REST Endpoints** | 25+        | Incluindo Cena e Bookmarks     |
| **AJAX Actions**   | 65+        | Confirmado                     |
| **Custom Tables**  | 9+         | Duas tabelas APRIO adicionadas |
| **Options**        | 16+        | 6 opções adicionadas           |
| **CSS Files**      | 29         | Confirmado                     |
| **JS Files**       | 42         | Confirmado                     |
| **Templates**      | 30+        | Confirmado                     |
| **Cron Jobs**      | 4          | Confirmado                     |

### Status de Qualidade

| Área                 | Status | Notas                              |
| -------------------- | ------ | ---------------------------------- |
| **PSR-4 Compliance** | ✅     | Namespace `Apollo\Events`          |
| **Security**         | ✅     | Nonces, capabilities, sanitization |
| **Performance**      | ⚠️     | Meta queries precisam otimização   |
| **Uninstall**        | ❌     | Falta arquivo principal            |
| **GDPR**             | ⚠️     | Falta export/erase hooks           |
| **Tests**            | ⚠️     | Estrutura existe, cobertura baixa  |
| **I18n**             | ✅     | Text domain configurado            |

### Ações Prioritárias

1. 🔴 Criar `uninstall.php` na raiz
2. 🔴 Implementar GDPR hooks
3. 🟡 Otimizar meta queries com índices
4. 🟡 Aumentar cobertura de testes
5. 🟢 Documentar endpoints REST

---

**Fim da Auditoria Corrigida**

_Auditoria Original: GitHub Copilot (Claude Opus 4.5) - 24/01/2026_
_Correções e Adições: GitHub Copilot (Grok) - 24/01/2026_

**Resumo das Correções:**

- ✅ Adicionados 15+ user meta keys não documentadas
- ✅ Adicionadas 6+ opções wp_options faltantes
- ✅ Adicionadas 2 tabelas custom APRIO
- ✅ Adicionados 5+ REST endpoints (Cena Rio, Bookmarks)
- ✅ Adicionados 3+ shortcodes não listados
- ✅ Documentadas dependências externas (Motion.dev, jQuery UI)
- ✅ **Adicionada seção completa de Co-Author functionality**
- ✅ **Adicionados meta keys para event_cena CPT**
- ✅ Corrigidas métricas totais para refletir inventário completo
- ✅ Expandido formato CSV com novos tipos de itens

_Plugin auditado com sucesso - todas as 20 seções do requirement #01.b.X foram completadas e validadas_

# Apollo Events Manager - INVENTORY.md

## Audit Summary

**Date:** 2024
**Plugin:** apollo-events-manager
**Status:** Audit Complete - Interest System Standardized

## Interest Feature Implementation (Winner: Interest_Module)

### Canonical Implementation

- **Class:** `Apollo\Events\Interest_Module` (extends Abstract_Module)
- **File:** `includes/modules/interest/class-interest-module.php`
- **Lines:** 772
- **Score:** 29/30 (Winner)
- **AJAX Action:** `apollo_toggle_interest`
- **Nonce:** `apollo_events_nonce`
- **Meta Keys:**
  - `_event_interested_users` (post_meta: array of user IDs)
  - `_user_interested_events` (user_meta: array of event IDs)
  - `_favorites_count` (post_meta: integer count)

### User Presentation

- **Label:** interesse
- **Icon:** ri-rocket-fill
- **List View:** meus interesses

## Database Tables

- **Custom Table:** `wp_apollo_event_bookmarks` (for bookmarks feature)
- **CPT Tables:** Standard WordPress tables for event stats CPT

## Legacy Systems (Migrated/Deprecated)

- **apollo_favorites** (user_meta) → `_user_interested_events`
- **\_apollo_favorited_users** (post_meta) → `_event_interested_users`
- **ajax-favorites.php** → Deprecated with \_deprecated_function wrappers
- **modules/favorites/** → Removed (third-party Simple Favorites fork, 40+ files)

## Migration Status

- **Script:** `scripts/migrate-favorites-to-interest.php`
- **Status:** Created, needs execution via browser
- **URL:** `/wp-content/plugins/apollo-events-manager/scripts/migrate-favorites-to-interest.php`
- **Access:** Admin only

## Namespace Integrity

- **Standard:** `Apollo\Events\*` (PSR-4 backslash style)
- **Fixed Files:**
  - `includes/post-types.php` - Added namespace, updated all registrations to use ID:: constants
- **Constants:** All register_post_type/taxonomy calls now use `Apollo_Core\Apollo_Identifiers` constants

## Test Coverage

- **File:** `tests/test-bookmarks.php` (110 lines, PHPUnit)
- **Coverage:** Bookmarks only (interest features not tested)
- **Status:** Minimal coverage, needs expansion

## Code Quality

- **Syntax Errors:** None found (PHP syntax check passed)
- **Logic Errors:** None identified
- **Code Duplication:** Resolved (5 competing implementations consolidated to 1)
- **Nonce Mismatches:** Resolved (standardized to `apollo_events_nonce`)

## Files Modified During Audit

- `includes/post-types.php` - Namespaced, constants applied
- `includes/ajax-favorites.php` - Deprecated (then removed)
- `includes/modules/interest/class-interest-module.php` - Nonce standardized
- `scripts/migrate-favorites-to-interest.php` - Created
- `modules/favorites/` - Removed (40+ files)

## Browser Audit Required

- **Nonce Verification:** Test AJAX calls with `apollo_events_nonce`
- **REST API:** Verify Interest_Module endpoints
- **Migration Execution:** Run script via browser to migrate legacy data

## Finalization Status

- ✅ Namespace fixes applied
- ✅ Interest winner confirmed
- ✅ Nonce standardization completed
- ✅ Legacy deprecation applied
- ✅ Migration script created
- ✅ Dead code removed
- ⏳ Migration execution (browser required)
- ⏳ Browser audit completion
- ⏳ INVENTORY.md finalized
