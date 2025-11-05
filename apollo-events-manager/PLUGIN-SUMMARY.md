# 📋 Apollo Events Manager - Resumo Completo do Plugin

**Versão:** 2.0.0  
**Data:** Novembro 2025  
**Status:** ✅ Produção

---

## 📦 INFORMAÇÕES GERAIS

### Plugin Details
- **Nome:** Apollo Events Manager
- **Slug:** `apollo-events-manager`
- **Versão:** 2.0.0
- **Autor:** Apollo Events Team
- **URI:** https://apollo.rio.br
- **Licença:** GPL v2 or later
- **Text Domain:** `apollo-events-manager`

### Requisitos
- WordPress: 5.0+
- PHP: 7.4+
- Testado até: WordPress 6.4

---

## 🏗️ ARQUITETURA DO PLUGIN

### Estrutura de Diretórios

```
apollo-events-manager/
├── apollo-events-manager.php          # Arquivo principal (2218 linhas)
├── includes/
│   ├── admin-metaboxes.php            # Meta boxes admin
│   ├── ajax-handlers.php              # Handlers AJAX
│   ├── class-apollo-events-analytics.php  # Analytics & Dashboards
│   ├── class-apollo-events-placeholders.php  # Sistema de placeholders
│   ├── config.php                     # Configurações
│   ├── data-migration.php             # Migrações de dados
│   ├── migration-validator.php        # Validação de migrações
│   └── post-types.php                 # CPTs e taxonomias
├── templates/
│   ├── apollo-canvas.php             # Canvas wrapper
│   ├── content-event_listing.php     # Content template
│   ├── event-card.php                # Card de evento
│   ├── event-listings-start.php      # Loop wrapper start
│   ├── event-listings-end.php        # Loop wrapper end
│   ├── portal-discover.php           # Portal de eventos (/eventos/)
│   ├── single-event_listing.php      # Single event (include)
│   ├── single-event-page.php         # Single event page
│   ├── single-event-standalone.php   # Single event standalone
│   └── single-event.php              # Single event (lightbox)
└── assets/
    ├── admin-metabox.css             # Estilos admin
    ├── admin-metabox.js              # JS admin
    └── uni.css                       # Fallback local
```

---

## 🎯 CUSTOM POST TYPES (CPTs)

### 1. `event_listing` (Eventos)
**Registrado em:** `includes/post-types.php`

**Taxonomias:**
- `event_listing_category` - Categorias de eventos
- `event_listing_type` - Tipos de eventos
- `event_listing_tag` - Tags de eventos
- `event_sounds` - Gêneros musicais

**Meta Keys Registrados (17):**
- `_event_title` - Título do evento
- `_event_banner` - Banner (URL ou attachment ID)
- `_event_video_url` - URL do vídeo (YouTube/Vimeo)
- `_event_start_date` - Data de início (YYYY-MM-DD HH:MM:SS)
- `_event_end_date` - Data de término
- `_event_start_time` - Horário de início (HH:MM:SS)
- `_event_end_time` - Horário de término
- `_event_location` - Localização (string)
- `_event_country` - País
- `_tickets_ext` - URL externa de ingressos
- `_cupom_ario` - Flag de cupom Ario (0/1)
- `_event_dj_ids` - IDs dos DJs (array serializado)
- `_event_local_ids` - ID do local (integer)
- `_event_timetable` - Timetable (array serializado)
- `_3_imagens_promo` - 3 imagens promocionais (array serializado)
- `_imagem_final` - Imagem final (array serializado)
- `_favorites_count` - Contador de favoritos

**Meta Keys Adicionais (Analytics):**
- `_apollo_event_views_total` - Total de visualizações
- `_apollo_coauthors` - Co-autores (array de user IDs)

### 2. `event_local` (Locais/Venues)
**Registrado em:** `includes/post-types.php`

**Meta Keys Registrados (9):**
- `_local_name` - Nome do local
- `_local_description` - Descrição
- `_local_address` - Endereço
- `_local_city` - Cidade
- `_local_latitude` - Latitude
- `_local_longitude` - Longitude
- `_local_website` - Website
- `_local_facebook` - Facebook URL
- `_local_instagram` - Instagram

**Funcionalidades:**
- Geocodificação automática via OpenStreetMap Nominatim
- Coordenadas salvos automaticamente ao salvar post

### 3. `event_dj` (DJs)
**Registrado em:** `includes/post-types.php`

**Meta Keys Registrados (14):**
- `_dj_name` - Nome do DJ
- `_dj_bio` - Biografia
- `_dj_website` - Website
- `_dj_soundcloud` - SoundCloud URL
- `_dj_instagram` - Instagram handle
- `_dj_facebook` - Facebook URL
- `_dj_image` - Imagem (URL ou attachment ID)
- `_dj_original_project_1` - Projeto Original 1
- `_dj_original_project_2` - Projeto Original 2
- `_dj_original_project_3` - Projeto Original 3
- `_dj_set_url` - URL do DJ Set
- `_dj_media_kit_url` - URL do Media Kit
- `_dj_rider_url` - URL do Rider
- `_dj_mix_url` - URL do DJ Mix

---

## 🔧 FUNCIONALIDADES PRINCIPAIS

### 1. Sistema de Placeholders (61 placeholders)

**Arquivo:** `includes/class-apollo-events-placeholders.php`

**Organização por CPT:**
- **Event Listing:** 43 placeholders
- **Local/Venue:** 9 placeholders
- **DJ:** 16 placeholders

**Categorias de Placeholders:**

#### Event Listing (43)
- **Básicos:** `event_id`, `title`, `permalink`, `content`, `excerpt`
- **Datas:** `start_date`, `start_day`, `start_month_pt`, `start_time`, `end_date`, `end_time`
- **Localização:** `location`, `location_area`, `location_full`, `country`
- **DJs:** `dj_list`, `dj_count`, `dj_ids`, `timetable`
- **Mídia:** `banner_url`, `video_url`, `promo_images`, `final_image`
- **Ingressos:** `tickets_url`, `cupom_ario`
- **Taxonomias:** `category_slug`, `category_name`, `type_slug`, `type_name`, `tags_list`, `sounds_list`
- **Estatísticas:** `favorites_count`, `local_id`

#### Local/Venue (9)
- `local_name`, `local_description`, `local_address`, `local_city`
- `local_coordinates`, `local_website`, `local_instagram`, `local_facebook`

#### DJ (16)
- **Básicos:** `dj_name`, `dj_bio`, `dj_image`
- **Redes Sociais:** `dj_website`, `dj_soundcloud`, `dj_instagram`, `dj_facebook`
- **Projetos:** `dj_original_project_1`, `dj_original_project_2`, `dj_original_project_3`
- **Mídia:** `dj_set_url`, `dj_media_kit_url`, `dj_rider_url`, `dj_mix_url`

**Função Principal:**
```php
apollo_event_get_placeholder_value($placeholder_id, $event_id = null, $args = [])
```

**Registro:**
```php
apollo_events_get_placeholders() // Retorna array completo
```

### 2. Shortcodes

#### `[apollo_event]`
**Handler:** `apollo_event_shortcode()`

**Atributos:**
- `field` (obrigatório) - ID do placeholder
- `id` (opcional) - ID do evento (padrão: post atual)

**Exemplos:**
```
[apollo_event field="dj_list"]
[apollo_event field="location" id="123"]
[apollo_event field="start_month_pt"]
[apollo_event field="banner_url"]
[apollo_event field="video_url"]
[apollo_event field="dj_set_url"]
```

#### `[apollo_event_user_overview]`
**Handler:** `apollo_event_user_overview_shortcode()`

**Descrição:** Exibe overview do usuário logado (co-author events, favorited, stats)

#### `[apollo_events]`
**Handler:** `events_shortcode()`

**Descrição:** Lista de eventos com filtros

#### `[eventos-page]`
**Handler:** `eventos_page_shortcode()`

**Descrição:** Portal completo de eventos

### 3. Sistema de Templates

#### Template Hierarchy
**Arquivo:** `apollo-events-manager.php` - `canvas_template()`

**Ordem de Prioridade:**
1. `single-event-standalone.php` - Para eventos individuais
2. `portal-discover.php` - Para `/eventos/` ou arquivo de eventos
3. Template padrão do tema

**STRICT MODE:** Força uso dos templates do plugin independente do tema ativo

#### Templates Principais

**`portal-discover.php`**
- Portal completo de eventos
- Grid responsivo
- Filtros AJAX
- Modal de eventos
- Carrega `uni.css` do CDN

**`single-event-standalone.php`**
- Página completa do evento
- Informações detalhadas
- DJs e timetable
- Localização e mapa

**`event-card.php`**
- Card individual de evento
- Reutilizado em loops
- Fallbacks defensivos

### 4. Sistema de Analytics & Dashboards

**Arquivo:** `includes/class-apollo-events-analytics.php`

#### Funções Principais

**Tracking:**
- `apollo_record_event_view($event_id, $user_id)` - Registra visualização
- `apollo_get_event_views($event_id)` - Obtém contagem de views

**User Analytics:**
- `apollo_get_user_favorited_events($user_id)` - Eventos favoritados
- `apollo_get_user_coauthor_events($user_id)` - Eventos co-autorados
- `apollo_get_user_event_stats($user_id)` - Estatísticas do usuário

**Global Analytics:**
- `apollo_get_global_event_stats()` - Estatísticas globais
- `apollo_get_top_users_by_interactions($limit)` - Top usuários

#### Dashboards Admin

**Página:** `Apollo Events > Dashboard`
- KPIs globais
- Top eventos por views
- Top sounds e locations
- Top usuários por interações

**Página:** `Apollo Events > User Overview`
- Seleção de usuário
- Eventos co-autorados
- Eventos favoritados
- Distribuição por sounds e locations

**Capability:** `view_apollo_event_stats` (administrator, editor)

### 5. AJAX Handlers

**Arquivo:** `includes/ajax-handlers.php` + métodos no arquivo principal

#### Handlers Disponíveis

**`wp_ajax_filter_events` / `wp_ajax_nopriv_filter_events`**
- Filtragem de eventos em tempo real
- Parâmetros: category, sounds, date, search

**`wp_ajax_load_event_single` / `wp_ajax_nopriv_load_event_single`**
- Carrega evento individual via AJAX

**`wp_ajax_apollo_get_event_modal` / `wp_ajax_nopriv_apollo_get_event_modal`**
- Gera conteúdo do modal de evento
- Tracking automático de views

**`wp_ajax_toggle_favorite` / `wp_ajax_nopriv_toggle_favorite`**
- Toggle de favoritos
- Integração com wpem-bookmarks

**`wp_ajax_apollo_add_new_dj`**
- Adiciona novo DJ via AJAX
- Validação de duplicatas

**`wp_ajax_apollo_add_new_local`**
- Adiciona novo local via AJAX
- Geocodificação automática

### 6. Admin Meta Boxes

**Arquivo:** `includes/admin-metaboxes.php`

#### Meta Boxes Registrados

**`apollo_event_details`** (para `event_listing`)
- Seleção de DJs (multi-select)
- Timetable dinâmico (DJ + horários)
- Seleção de Local
- URL de vídeo

**Funcionalidades:**
- Adicionar DJ novo via diálogo
- Adicionar local novo via diálogo
- Validação de duplicatas
- Timetable ordenado automaticamente

### 7. Geocodificação Automática

**Função:** `auto_geocode_local()`

**Processo:**
1. Detecta salvamento de `event_local`
2. Verifica se tem endereço e cidade
3. Chama OpenStreetMap Nominatim API
4. Salva latitude/longitude
5. Log de erros se falhar

**API:** `https://nominatim.openstreetmap.org/search`

### 8. Asset Management

**Método:** `enqueue_assets()`

#### Assets Carregados

**CDN (Sempre):**
- `https://assets.apollo.rio.br/uni.css` - CSS universal
- `https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css` - Ícones

**Local (Condicional):**
- `apollo-events-portal.js` - Funcionalidades do portal
- `admin-metabox.css` - Estilos admin
- `admin-metabox.js` - JS admin

**Condições de Enqueue:**
- Página `/eventos/` ou `event_listing` archive
- Single event pages
- Posts/páginas com shortcodes `[apollo_event]` ou `[apollo_events]`

### 9. Admin Pages

**Menu Principal:** `Apollo Events` (slug: `apollo-events`)

#### Submenus

**1. Dashboard** (`apollo-events-dashboard`)
- KPIs globais
- Top eventos/sounds/locations
- Top usuários
- Capability: `view_apollo_event_stats`

**2. User Overview** (`apollo-events-user-overview`)
- Seleção de usuário
- Estatísticas individuais
- Capability: `view_apollo_event_stats`

**3. Shortcodes & Placeholders** (`apollo-events-placeholders`)
- Tabela completa de placeholders
- Organizado por CPT
- Estilo uni.css
- Exemplos de uso
- Capability: `manage_options`

### 10. Helper Functions

#### Date Parsing
```php
apollo_eve_parse_start_date($raw)
```
**Retorna:** Array com `timestamp`, `day`, `month_pt`, `iso_date`, `iso_dt`

#### Location Helpers
```php
apollo_event_get_location_name($event_id)
apollo_event_get_location_area($event_id)
```

#### DJ Helpers
```php
apollo_event_get_dj_names($event_id)
```

#### Banner Helpers
```php
apollo_event_get_banner_url($event_id)
```

---

## 🔒 SEGURANÇA

### Sanitização
- Todos os inputs sanitizados com `sanitize_text_field()`, `esc_html()`, `esc_url()`, `esc_attr()`
- Nonces em todos os AJAX handlers
- Capability checks em admin pages

### Validação
- Validação de tipos de dados
- Validação de existência de posts
- Fallbacks defensivos

### Escaping
- Outputs escapados com `esc_html()`, `esc_url()`, `esc_attr()`
- `wp_kses_post()` para conteúdo HTML permitido

---

## 📊 INTEGRAÇÕES

### Plugins Integrados

**wpem-bookmarks**
- Sistema de favoritos
- Função: `get_user_favorites()`
- Fallback para user meta direto

**BuddyPress (Opcional)**
- Atividades de favoritos
- Verificação condicional

### APIs Externas

**OpenStreetMap Nominatim**
- Geocodificação de endereços
- Rate limiting respeitado

**CDN Assets**
- `https://assets.apollo.rio.br/uni.css`
- `https://cdn.jsdelivr.net/npm/remixicon@...`

---

## 🎨 TEMPLATES E ESTILOS

### CSS Framework
- **uni.css** - Framework CSS universal (CDN)
- **RemixIcon** - Ícones (CDN)

### Classes CSS Principais
- `.glass-table-card` - Cards com glass effect
- `.table-header` - Cabeçalho de tabela
- `.table-wrapper` - Wrapper de tabela
- `.event_listing` - Card de evento
- `.apollo-event-modal` - Modal de evento

---

## 🚀 FUNCIONALIDADES AVANÇADAS

### 1. Caching
- Transients para queries de eventos
- Cache de meta data com `update_meta_cache()`

### 2. Performance
- Queries otimizadas com `posts_per_page` limit
- Pre-loading de meta data
- Lazy loading de assets

### 3. Debug Mode
```php
define('APOLLO_DEBUG', true); // wp-config.php
```
- Logs detalhados em `error_log`
- Footer debug info (se habilitado)

### 4. Migration & Validation
- `migration-validator.php` - Validação de dados migrados
- `data-migration.php` - Scripts de migração

---

## 📝 HOOKS E FILTROS

### Actions
- `plugins_loaded` - Load textdomain
- `init` - Ensure events page
- `template_include` - Canvas template override
- `wp_enqueue_scripts` - Enqueue assets
- `save_post_event_listing` - Save custom fields
- `save_post_event_local` - Auto geocode
- `admin_menu` - Add admin pages
- `wp_ajax_*` - AJAX handlers

### Filters
- `submit_event_form_fields` - Custom event fields
- `submit_event_form_validate_fields` - Validation
- `event_manager_event_listing_templates` - Compatibility
- `event_manager_single_event_templates` - Compatibility
- `the_content` - Inject event content

---

## 🔄 WORKFLOW DE EVENTOS

### 1. Criação de Evento
1. Admin cria `event_listing`
2. Seleciona DJs (multi-select)
3. Configura timetable
4. Seleciona local
5. Adiciona mídia (banner, vídeo, imagens)
6. Salva → Custom fields salvos via `save_custom_event_fields()`

### 2. Exibição de Eventos
1. Usuário acessa `/eventos/`
2. `portal-discover.php` carregado
3. Query de eventos com cache
4. Cards renderizados com `event-card.php`
5. Modal via AJAX ao clicar

### 3. Tracking
1. Modal aberto → `apollo_get_event_modal` AJAX
2. View registrada via `apollo_record_event_view()`
3. Meta `_apollo_event_views_total` incrementado

---

## 📚 DOCUMENTAÇÃO ADICIONAL

### Arquivos de Documentação Incluídos
- `PLACEHOLDERS-REFERENCE.md` - Referência completa de placeholders
- `ADMIN-METABOX-GUIDE.md` - Guia de meta boxes
- `SECURITY-FIXES-2025-11-04.md` - Correções de segurança
- `DEBUG_FINDINGS.md` - Findings de debug
- `FINAL-QA-CHECKLIST.md` - Checklist de QA
- `README.md` - README básico

---

## ✅ CHECKLIST DE FUNCIONALIDADES

### Core Features
- [x] Custom Post Types (Events, DJs, Locals)
- [x] Taxonomies (Categories, Types, Tags, Sounds)
- [x] Meta Fields (17 eventos, 9 locais, 14 DJs)
- [x] Template System (STRICT MODE)
- [x] Shortcodes (4 tipos)
- [x] Placeholder System (61 placeholders)
- [x] Analytics & Dashboards
- [x] AJAX Handlers (5 handlers)
- [x] Admin Meta Boxes
- [x] Geocodificação Automática
- [x] Asset Management
- [x] Admin Pages (3 páginas)

### Integrations
- [x] wpem-bookmarks (favoritos)
- [x] OpenStreetMap (geocoding)
- [x] CDN Assets (uni.css, RemixIcon)

### Security
- [x] Sanitização de inputs
- [x] Escaping de outputs
- [x] Nonces em AJAX
- [x] Capability checks
- [x] Validação de dados

### Performance
- [x] Caching (transients)
- [x] Query optimization
- [x] Meta pre-loading
- [x] Lazy asset loading

---

## 🎯 RESUMO EXECUTIVO

**Apollo Events Manager** é um plugin WordPress completo para gestão de eventos musicais, com foco em:

1. **Gestão Completa:** Eventos, DJs e Locais como CPTs independentes
2. **Sistema de Placeholders:** 61 placeholders organizados por CPT
3. **Analytics:** Dashboards admin com estatísticas detalhadas
4. **Templates Modernos:** Sistema de templates com uni.css
5. **Integrações:** wpem-bookmarks, geocoding, CDN assets
6. **Segurança:** Sanitização, escaping, nonces, capabilities
7. **Performance:** Caching, query optimization, lazy loading

**Total de Linhas de Código:** ~5.000+ linhas (PHP, JS, CSS)  
**Arquivos Principais:** 20+ arquivos  
**Placeholders:** 61  
**Shortcodes:** 4  
**AJAX Handlers:** 5  
**Admin Pages:** 3

---

**Última Atualização:** 2025-11-04  
**Versão do Plugin:** 2.0.0  
**Status:** ✅ Produção

