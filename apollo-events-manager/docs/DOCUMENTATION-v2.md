# Apollo Events Manager v2.0.0 - Documentação Completa

**O plugin definitivo de gerenciamento de eventos para WordPress.**

![Versão](https://img.shields.io/badge/version-2.0.0-blue)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue)

---

## 📋 Sumário

1. [Arquitetura Modular](#arquitetura-modular)
2. [Módulos Disponíveis](#módulos-disponíveis)
3. [Shortcodes Reference](#shortcodes-reference)
4. [Blocos Gutenberg](#blocos-gutenberg)
5. [Meta Fields Reference](#meta-fields-reference)
6. [Hooks e Filtros](#hooks-e-filtros)
7. [REST API](#rest-api)
8. [Customização de Templates](#customização-de-templates)

---

## Arquitetura Modular

### Estrutura de Diretórios

```
apollo-events-manager/
├── apollo-events-manager.php    # Main plugin file
├── assets/
│   ├── css/                     # Stylesheets por módulo
│   └── js/                      # JavaScript por módulo
├── includes/
│   ├── core/                    # Classes core
│   │   ├── class-bootloader.php
│   │   ├── class-registry.php
│   │   ├── class-assets-manager.php
│   │   ├── class-abstract-module.php
│   │   └── class-health-check.php
│   └── modules/                 # Módulos independentes
│       ├── lists/
│       ├── interest/
│       ├── speakers/
│       ├── photos/
│       ├── reviews/
│       ├── tickets/
│       ├── duplicate/
│       ├── tracking/
│       ├── notifications/
│       ├── share/
│       ├── qrcode/
│       ├── seo/
│       ├── import-export/
│       └── blocks/
└── templates/                   # Template files
```

### Core Classes

| Classe | Responsabilidade |
|--------|------------------|
| `Bootloader` | Inicialização do plugin |
| `Registry` | Registro e gerenciamento de módulos |
| `Assets_Manager` | Enqueue de CSS/JS |
| `Abstract_Module` | Base para todos os módulos |
| `Health_Check` | Verificação de saúde do plugin |

---

## Módulos Disponíveis

### 1. Lists Module (`lists`)
**Arquivo:** `includes/modules/lists/class-lists-module.php`

Responsável por diferentes formatos de listagem de eventos.

### 2. Interest Module (`interest`)
**Arquivo:** `includes/modules/interest/class-interest-module.php`

Sistema de "Tenho Interesse" com contagem e avatares.

### 3. Speakers Module (`speakers`)
**Arquivo:** `includes/modules/speakers/class-speakers-module.php`

Gerenciamento de DJs, line-ups e timetables.

### 4. Photos Module (`photos`)
**Arquivo:** `includes/modules/photos/class-photos-module.php`

Galerias, upload de fotos e lightbox.

### 5. Reviews Module (`reviews`)
**Arquivo:** `includes/modules/reviews/class-reviews-module.php`

Sistema de avaliações com estrelas e votos úteis.

### 6. Tickets Module (`tickets`)
**Arquivo:** `includes/modules/tickets/class-tickets-module.php`

Integração WooCommerce e links externos.

### 7. Duplicate Module (`duplicate`)
**Arquivo:** `includes/modules/duplicate/class-duplicate-module.php`

Duplicação de eventos e séries recorrentes.

### 8. Tracking Module (`tracking`)
**Arquivo:** `includes/modules/tracking/class-tracking-module.php`

Analytics, estatísticas e dashboard.

### 9. Notifications Module (`notifications`)
**Arquivo:** `includes/modules/notifications/class-notifications-module.php`

Lembretes, digest semanal e toast notifications.

### 10. Share Module (`share`)
**Arquivo:** `includes/modules/share/class-share-module.php`

Compartilhamento em 7 redes sociais.

### 11. QR Code Module (`qrcode`)
**Arquivo:** `includes/modules/qrcode/class-qrcode-module.php`

Geração de QR codes para eventos.

### 12. SEO Module (`seo`)
**Arquivo:** `includes/modules/seo/class-seo-module.php`

Meta tags, Open Graph e Schema.org.

### 13. Import/Export Module (`import_export`)
**Arquivo:** `includes/modules/import-export/class-import-export-module.php`

CSV, JSON e iCal import/export.

### 14. Blocks Module (`blocks`)
**Arquivo:** `includes/modules/blocks/class-blocks-module.php`

Blocos Gutenberg para editor visual.

---

## Shortcodes Reference

### Lists Module

```
[apollo_events_grid count="6" columns="3" layout="card"]
[apollo_events_list count="10" show_date="true"]
[apollo_events_table columns="title,date,local"]
[apollo_events_slider count="5" autoplay="true"]
[apollo_events_compact count="5"]
[apollo_featured_events count="3"]
```

### Interest Module

```
[apollo_interest_button event_id="123"]
[apollo_interest_count event_id="123"]
[apollo_user_interests count="10"]
[apollo_interest_avatars event_id="123" count="5"]
```

### Speakers Module

```
[apollo_dj_card id="456"]
[apollo_dj_grid count="8" columns="4"]
[apollo_event_lineup event_id="123"]
[apollo_dj_timetable event_id="123"]
[apollo_schedule_tabs event_id="123"]
```

### Photos Module

```
[apollo_event_gallery event_id="123"]
[apollo_photo_slider event_id="123"]
[apollo_photo_masonry event_id="123" columns="3"]
[apollo_photo_upload event_id="123"]
[apollo_photo_lightbox event_id="123"]
```

### Reviews Module

```
[apollo_event_reviews event_id="123"]
[apollo_review_form event_id="123"]
[apollo_review_summary event_id="123"]
[apollo_star_rating event_id="123"]
```

### Tickets Module

```
[apollo_ticket_button event_id="123"]
[apollo_ticket_info event_id="123"]
[apollo_ticket_card event_id="123"]
```

### Tracking Module

```
[apollo_event_stats event_id="123"]
[apollo_analytics_chart event_id="123" type="line"]
```

### Notifications Module

```
[apollo_notify_button event_id="123"]
[apollo_notification_preferences]
```

### Share Module

```
[apollo_share_buttons networks="whatsapp,facebook,twitter"]
[apollo_share_count]
[apollo_share_single network="whatsapp"]
```

### QR Code Module

```
[apollo_event_qr event_id="123" size="200"]
[apollo_qr_download event_id="123"]
[apollo_qr_card event_id="123"]
```

---

## Blocos Gutenberg

### apollo-events/event-list
Grid ou lista de eventos com controles de layout.

**Atributos:**
- `layout`: grid | list
- `columns`: 1-4
- `count`: 1-12
- `showPast`: boolean
- `showDate`: boolean
- `showLocal`: boolean
- `showDJs`: boolean

### apollo-events/single-event
Exibe um evento específico.

**Atributos:**
- `eventId`: number
- `layout`: card | horizontal | minimal
- `showImage`: boolean
- `showDate`: boolean
- `showLocal`: boolean
- `showButton`: boolean

### apollo-events/countdown
Contagem regressiva para evento.

**Atributos:**
- `eventId`: number (0 = próximo evento)
- `showTitle`: boolean
- `showDays`: boolean
- `showHours`: boolean
- `showMinutes`: boolean
- `showSeconds`: boolean
- `style`: default | minimal | dark | gradient

### apollo-events/calendar
Calendário mensal de eventos.

**Atributos:**
- `month`: number (0 = atual)
- `year`: number (0 = atual)
- `showNav`: boolean

### apollo-events/dj-grid
Grid de DJs.

**Atributos:**
- `columns`: 2-6
- `count`: 1-24
- `showName`: boolean
- `showGenre`: boolean

### apollo-events/local-grid
Grid de locais/venues.

**Atributos:**
- `columns`: 2-4
- `count`: 1-12
- `showAddress`: boolean

### apollo-events/search
Formulário de busca com filtros.

**Atributos:**
- `showDate`: boolean
- `showLocal`: boolean
- `showDJ`: boolean
- `placeholder`: string

---

## Meta Fields Reference

### Event Listing

| Meta Key | Tipo | Descrição |
|----------|------|-----------|
| `_event_start_date` | datetime | Data/hora de início |
| `_event_end_date` | datetime | Data/hora de término |
| `_event_dj_ids` | array | IDs dos DJs |
| `_event_local_ids` | array | IDs dos locais |
| `_event_dj_slots` | array | Horários dos DJs |
| `_event_ticket_price` | float | Preço do ingresso |
| `_event_ticket_url` | string | URL externa |
| `_event_woo_product_id` | int | ID produto WooCommerce |
| `_event_interested_users` | array | Usuários interessados |
| `_event_photos` | array | IDs das fotos oficiais |
| `_event_community_photos` | array | Fotos da comunidade |
| `_event_reviews` | array | Reviews do evento |
| `_event_rsvps` | array | RSVPs |
| `_event_tracking` | array | Dados de tracking |
| `_event_shares` | array | Contagem por rede |
| `_apollo_seo_title` | string | Título SEO |
| `_apollo_seo_description` | string | Descrição SEO |
| `_apollo_seo_keywords` | string | Keywords SEO |
| `_apollo_seo_noindex` | boolean | NoIndex |
| `_apollo_seo_nofollow` | boolean | NoFollow |

### Event DJ

| Meta Key | Tipo | Descrição |
|----------|------|-----------|
| `_dj_genre` | string | Gênero musical |
| `_dj_social_links` | array | Links redes sociais |

### Event Local

| Meta Key | Tipo | Descrição |
|----------|------|-----------|
| `_local_address` | string | Endereço completo |
| `_local_capacity` | int | Capacidade |
| `_local_latitude` | float | Latitude |
| `_local_longitude` | float | Longitude |

### User Meta

| Meta Key | Tipo | Descrição |
|----------|------|-----------|
| `_apollo_event_subscriptions` | array | Eventos com notificação |
| `_apollo_notification_prefs` | array | Preferências de notificação |

---

## Hooks e Filtros

### Actions

```php
// Ciclo de vida do evento
do_action( 'apollo_before_save_event', $post_id, $data );
do_action( 'apollo_after_save_event', $post_id, $data );
do_action( 'apollo_before_delete_event', $post_id );

// Interest
do_action( 'apollo_user_interested', $user_id, $event_id );
do_action( 'apollo_user_uninterested', $user_id, $event_id );

// Reviews
do_action( 'apollo_review_submitted', $review_id, $event_id, $user_id );
do_action( 'apollo_review_approved', $review_id );

// Tracking
do_action( 'apollo_event_viewed', $event_id, $user_id );
do_action( 'apollo_ticket_clicked', $event_id, $user_id );

// Notifications
do_action( 'apollo_before_notification', $user_id, $event_id, $type );
do_action( 'apollo_after_notification', $user_id, $event_id, $type );

// Share
do_action( 'apollo_event_shared', $event_id, $network, $user_id );

// Import/Export
do_action( 'apollo_before_import', $file_path, $format );
do_action( 'apollo_after_import', $imported_ids, $format );
do_action( 'apollo_before_export', $event_ids, $format );
```

### Filters

```php
// Query de eventos
add_filter( 'apollo_events_query_args', function( $args ) {
    return $args;
});

// Output de shortcodes
add_filter( 'apollo_events_grid_output', function( $output, $events, $atts ) {
    return $output;
}, 10, 3 );

// Meta do evento
add_filter( 'apollo_event_meta', function( $meta, $event_id ) {
    return $meta;
}, 10, 2 );

// Redes de compartilhamento
add_filter( 'apollo_share_networks', function( $networks ) {
    return $networks;
});

// Configurações de QR Code
add_filter( 'apollo_qrcode_options', function( $options, $event_id ) {
    return $options;
}, 10, 2 );

// Template do email
add_filter( 'apollo_email_template', function( $template, $type ) {
    return $template;
}, 10, 2 );

// Schema.org markup
add_filter( 'apollo_schema_event', function( $schema, $event_id ) {
    return $schema;
}, 10, 2 );

// Campos de import
add_filter( 'apollo_import_field_mapping', function( $mapping ) {
    return $mapping;
});

// Módulos habilitados
add_filter( 'apollo_enabled_modules', function( $modules ) {
    unset( $modules['reviews'] ); // Desabilitar módulo
    return $modules;
});
```

---

## REST API

### Endpoints Públicos

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/wp-json/apollo-events/v1/events` | Lista eventos |
| GET | `/wp-json/apollo-events/v1/events/{id}` | Evento único |
| GET | `/wp-json/apollo-events/v1/djs` | Lista DJs |
| GET | `/wp-json/apollo-events/v1/locals` | Lista locais |
| GET | `/wp-json/apollo-events/v1/qr/{id}` | QR Code do evento |

### Endpoints Autenticados

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/wp-json/apollo-events/v1/interest` | Toggle interesse |
| POST | `/wp-json/apollo-events/v1/review` | Enviar review |
| GET | `/wp-json/apollo-events/v1/export` | Exportar eventos |
| GET | `/wp-json/apollo-events/v1/stats` | Estatísticas |

### Feeds Públicos

```
GET /apollo-events-feed/json/
GET /apollo-events-feed/ical/
```

---

## Customização de Templates

### Hierarquia de Templates

1. `seu-tema/apollo-events/{template}.php`
2. `apollo-events-manager/templates/{template}.php`

### Templates Disponíveis

- `single-event_listing.php`
- `archive-event_listing.php`
- `single-event_dj.php`
- `archive-event_dj.php`
- `single-event_local.php`
- `archive-event_local.php`

### Template Tags

```php
// Obter data formatada
apollo_get_event_date( $event_id, $format = 'd/m/Y H:i' );

// Obter DJs do evento
apollo_get_event_djs( $event_id );

// Obter locais do evento
apollo_get_event_locals( $event_id );

// Verificar se usuário tem interesse
apollo_user_is_interested( $event_id, $user_id = null );

// Obter contagem de interessados
apollo_get_interest_count( $event_id );

// Obter rating médio
apollo_get_event_rating( $event_id );
```

---

## Changelog v2.0.0

### Novidades
- ✨ Arquitetura modular completa
- ✨ 14 módulos independentes
- ✨ 7 blocos Gutenberg
- ✨ Sistema de health check integrado ao Site Health
- ✨ Import/Export (CSV, JSON, iCal)
- ✨ REST API expandida
- ✨ Integração WooCommerce para tickets
- ✨ Schema.org Event markup
- ✨ QR Codes dinâmicos

### Melhorias
- 🔧 PHP 8.0+ requerido (typed properties, union types)
- 🔧 WordPress 6.0+ requerido
- 🔧 Código 100% compatível com WPCS
- 🔧 Todos os módulos com feature flags
- 🔧 Assets carregados condicionalmente

### Correções
- 🐛 Sanitização completa de inputs
- 🐛 Escape de outputs
- 🐛 Nonces em todos os forms
- 🐛 Capability checks consistentes

---

*Documentação atualizada em 2024*
