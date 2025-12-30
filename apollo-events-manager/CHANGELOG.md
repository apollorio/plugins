# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [2.0.0] - 2024-XX-XX

### Adicionado

#### Core
- Arquitetura modular completa com 14 módulos independentes
- Sistema de registro de módulos (`Registry`)
- Classe base abstrata para módulos (`Abstract_Module`)
- Gerenciador de assets centralizado (`Assets_Manager`)
- Sistema de health check integrado ao Site Health do WordPress
- Suporte a feature flags para cada módulo

#### Módulos Novos
- **Lists Module**: 6 shortcodes para listagem (grid, list, table, slider, compact, featured)
- **Interest Module**: Sistema "Tenho Interesse" com contagem e avatares
- **Speakers Module**: Gerenciamento de DJs, line-ups e timetables
- **Photos Module**: Galeria, upload, masonry e lightbox
- **Reviews Module**: Avaliações com estrelas e votos úteis
- **Tickets Module**: Integração WooCommerce e links externos
- **Duplicate Module**: Duplicação de eventos e séries recorrentes
- **Tracking Module**: Analytics com Chart.js e dashboard
- **Notifications Module**: Lembretes por email e toast notifications
- **Share Module**: Compartilhamento em 7 redes sociais
- **QR Code Module**: Geração de QR codes com qrcode.js
- **SEO Module**: Meta tags, Open Graph, Twitter Cards, Schema.org
- **Import/Export Module**: CSV, JSON e iCal
- **Blocks Module**: 7 blocos Gutenberg

#### Blocos Gutenberg
- Lista de Eventos (grid/list configurável)
- Evento Único (destaque)
- Contagem Regressiva (timer)
- Calendário Mensal
- Grid de DJs
- Grid de Locais
- Busca de Eventos

#### REST API
- `GET /apollo-events/v1/events` - Listar eventos
- `GET /apollo-events/v1/events/{id}` - Evento único
- `GET /apollo-events/v1/djs` - Listar DJs
- `GET /apollo-events/v1/locals` - Listar locais
- `GET /apollo-events/v1/qr/{id}` - QR Code
- `POST /apollo-events/v1/interest` - Toggle interesse
- `POST /apollo-events/v1/review` - Enviar review
- `GET /apollo-events/v1/export` - Exportar eventos
- `GET /apollo-events/v1/stats` - Estatísticas

#### Feeds
- Feed JSON público: `/apollo-events-feed/json/`
- Feed iCal público: `/apollo-events-feed/ical/`

#### Shortcodes (50+)
- Listagem: `[apollo_events_grid]`, `[apollo_events_list]`, `[apollo_events_table]`, `[apollo_events_slider]`, `[apollo_events_compact]`, `[apollo_featured_events]`
- Interesse: `[apollo_interest_button]`, `[apollo_interest_count]`, `[apollo_user_interests]`, `[apollo_interest_avatars]`
- DJs: `[apollo_dj_card]`, `[apollo_dj_grid]`, `[apollo_event_lineup]`, `[apollo_dj_timetable]`, `[apollo_schedule_tabs]`
- Fotos: `[apollo_event_gallery]`, `[apollo_photo_slider]`, `[apollo_photo_masonry]`, `[apollo_photo_upload]`, `[apollo_photo_lightbox]`
- Reviews: `[apollo_event_reviews]`, `[apollo_review_form]`, `[apollo_review_summary]`, `[apollo_star_rating]`
- Tickets: `[apollo_ticket_button]`, `[apollo_ticket_info]`, `[apollo_ticket_card]`
- Tracking: `[apollo_event_stats]`, `[apollo_analytics_chart]`
- Notificações: `[apollo_notify_button]`, `[apollo_notification_preferences]`
- Share: `[apollo_share_buttons]`, `[apollo_share_count]`, `[apollo_share_single]`
- QR Code: `[apollo_event_qr]`, `[apollo_qr_download]`, `[apollo_qr_card]`

### Alterado
- Requisito mínimo de PHP alterado para 8.0
- Requisito mínimo de WordPress alterado para 6.0
- Estrutura de diretórios reorganizada para arquitetura modular
- Meta keys padronizados com prefixo `_event_` e `_apollo_`

### Melhorado
- Performance com carregamento condicional de assets
- Segurança com sanitização e escape em todo o código
- Internacionalização 100% traduzível
- Compatibilidade com WPCS (WordPress Coding Standards)

### Corrigido
- Validação de nonces em todos os formulários
- Capability checks em todas as ações administrativas
- Escape de output em todos os templates

---

## [1.x.x] - Versões Anteriores

Consulte o histórico de commits para versões anteriores.

---

## Tipos de Mudanças

- `Adicionado` para novas funcionalidades.
- `Alterado` para mudanças em funcionalidades existentes.
- `Depreciado` para funcionalidades que serão removidas em breve.
- `Removido` para funcionalidades removidas.
- `Corrigido` para correções de bugs.
- `Segurança` para vulnerabilidades corrigidas.
