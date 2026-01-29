# 📝 Inventário: Event Creation Forms - Apollo WordPress Plugins

**Data de Geração:** 2026-01-29
**Escopo:** `apollo-events-manager/` + `apollo-core/`
**Status:** ✅ Completo

---

## 📋 Sumário Executivo

O sistema Apollo possui **múltiplos pontos de entrada** para criação de eventos, distribuídos entre `apollo-events-manager` e `apollo-core`. Existem **6 principais mecanismos** de submissão de eventos:

| Tipo                                   | Plugin         | Localização Principal                           |
| -------------------------------------- | -------------- | ----------------------------------------------- |
| Shortcode `[submit_event_form]`        | events-manager | `includes/shortcodes-submit.php`                |
| Shortcode `[apollo_event_submit]`      | events-manager | `apollo-events-manager.php`                     |
| Shortcode `[apollo_public_event_form]` | events-manager | `includes/public-event-form.php`                |
| Shortcode `[apollo_cena_submit_event]` | apollo-core    | `includes/class-cena-rio-submissions.php`       |
| Template `page-cenario-new-event.php`  | events-manager | `templates/page-cenario-new-event.php`          |
| Template `event-form.php`              | apollo-core    | `templates/template-parts/forms/event-form.php` |
| REST API `/apollo/v1/events`           | events-manager | `src/RestAPI/class-events-controller.php`       |
| REST API `/apollo/v1/cena-rio/enviar`  | apollo-core    | `includes/class-cena-rio-submissions.php`       |

---

## 1. 📁 PHP Classes/Files Handling Event Forms

### 1.1 apollo-events-manager

| Arquivo                                                                                                                                   | Classe/Função                              | Linhas  | Propósito                                 |
| ----------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------ | ------- | ----------------------------------------- |
| [includes/shortcodes-submit.php](../apollo-events-manager/includes/shortcodes-submit.php)                                                 | `aem_submit_event_shortcode()`             | 12-441  | Shortcode principal `[submit_event_form]` |
| [includes/public-event-form.php](../apollo-events-manager/includes/public-event-form.php)                                                 | `apollo_render_public_event_form()`        | 19-451  | Formulário público simplificado           |
| [includes/shortcodes/class-apollo-events-shortcodes.php](../apollo-events-manager/includes/shortcodes/class-apollo-events-shortcodes.php) | `Apollo_Events_Shortcodes`                 | 1-1412  | Classe principal de shortcodes            |
| [templates/page-cenario-new-event.php](../apollo-events-manager/templates/page-cenario-new-event.php)                                     | `apollo_process_new_event_submission()`    | 517-634 | Template de submissão completo            |
| [includes/admin-shortcodes-page.php](../apollo-events-manager/includes/admin-shortcodes-page.php)                                         | `apollo_process_public_event_submission()` | 323-468 | Handler de submissão pública              |
| [src/RestAPI/class-events-controller.php](../apollo-events-manager/src/RestAPI/class-events-controller.php)                               | `Events_Controller`                        | 1-971   | REST API CRUD                             |
| [includes/cena/class-event-cena-cpt.php](../apollo-events-manager/includes/cena/class-event-cena-cpt.php)                                 | `Event_Cena_CPT`                           | 1-753   | CPT CENA e REST                           |
| [apollo-events-manager.php](../apollo-events-manager/apollo-events-manager.php)                                                           | `render_submit_form()`                     | 839-848 | Alias shortcode                           |

### 1.2 apollo-core

| Arquivo                                                                                                       | Classe/Função          | Linhas | Propósito                   |
| ------------------------------------------------------------------------------------------------------------- | ---------------------- | ------ | --------------------------- |
| [includes/class-cena-rio-submissions.php](../apollo-core/includes/class-cena-rio-submissions.php)             | `Cena_Rio_Submissions` | 1-745  | Submissão CENA-RIO completa |
| [templates/template-parts/forms/event-form.php](../apollo-core/templates/template-parts/forms/event-form.php) | N/A (template)         | 1-1282 | Template de formulário rico |
| [includes/forms/rest.php](../apollo-core/includes/forms/rest.php)                                             | N/A                    | 344+   | Criação via REST            |
| [modules/events/bootstrap.php](../apollo-core/modules/events/bootstrap.php)                                   | N/A                    | 304+   | Bootstrap de eventos        |

---

## 2. 📜 JavaScript Files for Form Handling

| Arquivo                                                                                                       | Funções Principais                            | Linhas  |
| ------------------------------------------------------------------------------------------------------------- | --------------------------------------------- | ------- |
| [assets/js/apollo-api.js](../apollo-core/assets/js/apollo-api.js)                                             | `events.create()`, `events.update()`          | 183-210 |
| [templates/page-cenario-new-event.php](../apollo-events-manager/templates/page-cenario-new-event.php)         | `rebuildTimetable()`, `updateTimetableData()` | 407-516 |
| [templates/template-parts/forms/event-form.php](../apollo-core/templates/template-parts/forms/event-form.php) | Form validation, DJ selector                  | 1047+   |
| [assets/js/cena-rio-calendar.js](../apollo-core/assets/js/cena-rio-calendar.js)                               | Inline form handling                          | 411+    |

### JavaScript API Methods

```javascript
// apollo-core/assets/js/apollo-api.js
ApolloAPI.events.create(data); // Linha 183-192
ApolloAPI.events.update(id, data); // Linha 198-206
```

---

## 3. 🔌 AJAX Endpoints

### 3.1 Event Creation/Modification

| Action                        | Handler                                 | Arquivo                                | Linha   | Auth Required |
| ----------------------------- | --------------------------------------- | -------------------------------------- | ------- | ------------- |
| `filter_events`               | `ajax_filter_events`                    | apollo-events-manager.php              | 850-851 | Não           |
| `apollo_save_profile`         | `ajax_save_profile`                     | apollo-events-manager.php              | 854     | Sim           |
| `apollo_get_event_modal`      | `ajax_get_event_modal`                  | apollo-events-manager.php              | 859-860 | Não           |
| `apollo_mod_approve_event`    | `ajax_mod_approve_event`                | apollo-events-manager.php              | 869     | Sim           |
| `apollo_mod_reject_event`     | `ajax_mod_reject_event`                 | apollo-events-manager.php              | 870     | Sim           |
| `apollo_submit_event_comment` | `ajax_submit_event_comment`             | apollo-events-manager.php              | 944     | Sim           |
| `apollo_load_event_modal`     | `apollo_ajax_load_event_modal`          | includes/ajax-handlers.php             | 19-20   | Não           |
| `apollo_upload_event_photo`   | `ajax_upload_photo`                     | modules/photos/class-photos-module.php | 113     | Sim           |
| `apollo_create_canvas_page`   | `apollo_events_ajax_create_canvas_page` | includes/admin-shortcodes-page.php     | 980     | Sim           |

### 3.2 Related AJAX (apollo-core)

| Action                    | Handler                        | Arquivo               | Linha | Auth Required |
| ------------------------- | ------------------------------ | --------------------- | ----- | ------------- |
| `apollo_save_form_schema` | `apollo_ajax_save_form_schema` | admin/forms-admin.php | 314   | Sim           |

---

## 4. 🌐 REST API Endpoints

### 4.1 apollo-events-manager

| Route                             | Method   | Controller                                   | Arquivo                                    | Linha   |
| --------------------------------- | -------- | -------------------------------------------- | ------------------------------------------ | ------- |
| `/apollo/v1/events`               | GET      | `Events_Controller::get_items`               | src/RestAPI/class-events-controller.php    | 57-75   |
| `/apollo/v1/events`               | POST     | `Events_Controller::create_item`             | src/RestAPI/class-events-controller.php    | 333-400 |
| `/apollo/v1/events/{id}`          | GET      | `Events_Controller::get_item`                | src/RestAPI/class-events-controller.php    | 79-108  |
| `/apollo/v1/events/{id}`          | PUT      | `Events_Controller::update_item`             | src/RestAPI/class-events-controller.php    | 400+    |
| `/apollo/v1/events/{id}`          | DELETE   | `Events_Controller::delete_item`             | src/RestAPI/class-events-controller.php    | 100+    |
| `/apollo/v1/events/calendar`      | GET      | `Events_Controller::get_calendar`            | src/RestAPI/class-events-controller.php    | 117-145 |
| `/apollo/v1/events/upcoming`      | GET      | `Events_Controller::get_upcoming`            | src/RestAPI/class-events-controller.php    | 147-175 |
| `/apollo/v1/events/{id}/favorite` | POST     | `Events_Controller::toggle_favorite`         | src/RestAPI/class-events-controller.php    | 177-200 |
| `/apollo/v1/events/{id}/interest` | POST     | `REST_API_Module::toggle_interest`           | modules/rest-api/class-rest-api-module.php | 94-111  |
| `/apollo/v1/events/{id}/reviews`  | GET/POST | `REST_API_Module::get_reviews/submit_review` | modules/rest-api/class-rest-api-module.php | 112-165 |
| `/apollo/v1/events/{id}/track`    | POST     | `REST_API_Module::track_view`                | modules/rest-api/class-rest-api-module.php | 166-200 |
| `/apollo/v1/cena-events`          | GET      | `Event_Cena_CPT::rest_get_events`            | includes/cena/class-event-cena-cpt.php     | 145-154 |
| `/apollo/v1/cena-events`          | POST     | `Event_Cena_CPT::rest_create_event`          | includes/cena/class-event-cena-cpt.php     | 156-167 |

### 4.2 apollo-core

| Route                                | Method | Controller                                   | Arquivo                                 | Linha   |
| ------------------------------------ | ------ | -------------------------------------------- | --------------------------------------- | ------- |
| `/apollo/v1/cena-rio/agenda`         | GET    | `Cena_Rio_Submissions::rest_get_events`      | includes/class-cena-rio-submissions.php | 47-57   |
| `/apollo/v1/cena-rio/enviar`         | POST   | `Cena_Rio_Submissions::rest_submit_event`    | includes/class-cena-rio-submissions.php | 59-105  |
| `/apollo/v1/cena-rio/confirmar/{id}` | POST   | `Cena_Rio_Submissions::rest_confirm_event`   | includes/class-cena-rio-submissions.php | 107-123 |
| `/apollo/v1/cena-rio/cancelar/{id}`  | POST   | `Cena_Rio_Submissions::rest_unconfirm_event` | includes/class-cena-rio-submissions.php | 125-140 |

---

## 5. 🎣 Action Hooks

### Event Creation Hooks

| Hook                              | Arquivo                                   | Linha    | Propósito                  |
| --------------------------------- | ----------------------------------------- | -------- | -------------------------- |
| `save_post_event_listing`         | apollo-events-manager.php                 | 863, 882 | Salvar campos customizados |
| `transition_post_status`          | apollo-events-manager.php                 | 864      | Limpar cache               |
| `publish_event_listing`           | class-apollo-cross-module-integration.php | 36       | Criar post social          |
| `apollo_event_reminder`           | class-events-email-integration.php        | 39       | Enviar lembrete            |
| `apollo_event_bookmarked`         | class-events-email-integration.php        | 40       | Notificar bookmark         |
| `apollo_cena_rio_event_approved`  | class-events-email-integration.php        | 42       | Notificar aprovação        |
| `apollo_events_post_types_loaded` | apollo-events-manager.php                 | 893      | Pós-registro de CPTs       |

---

## 6. 🔄 Filter Hooks

### Event Form Filters

| Hook                                | Arquivo                                   | Linha    | Propósito                  |
| ----------------------------------- | ----------------------------------------- | -------- | -------------------------- |
| `submit_event_form_fields`          | apollo-events-manager.php                 | 873, 876 | Adicionar/modificar campos |
| `submit_event_form_validate_fields` | apollo-events-manager.php                 | 879      | Validar campos             |
| `apollo_events_query_args`          | docs/DOCUMENTATION-v2.md                  | 400      | Modificar query            |
| `apollo_events_grid_output`         | docs/DOCUMENTATION-v2.md                  | 405      | Modificar output grid      |
| `apollo_event_meta`                 | docs/DOCUMENTATION-v2.md                  | 410      | Modificar meta             |
| `apollo_can_create_event`           | class-apollo-cross-module-integration.php | 52       | Verificar permissão        |

---

## 7. 📝 Form Fields and Validation

### 7.1 Campos do Formulário Principal `[submit_event_form]`

| Campo       | Name                     | Tipo            | Obrigatório | Validação              |
| ----------- | ------------------------ | --------------- | ----------- | ---------------------- |
| Título      | `post_title`             | text            | ✅          | `sanitize_text_field`  |
| Descrição   | `post_content`           | textarea        | ❌          | `wp_kses_post`         |
| Data Início | `event_start_date`       | date            | ✅          | `sanitize_text_field`  |
| Hora Início | `event_start_time`       | time            | ❌          | `sanitize_text_field`  |
| DJs         | `event_djs[]`            | select multiple | ❌          | `absint` array         |
| Local       | `event_local`            | select          | ✅          | `absint`               |
| Timetable   | `apollo_event_timetable` | hidden (JSON)   | ❌          | JSON decode + sanitize |
| Banner      | `event_banner`           | file            | ❌          | File upload validation |

### 7.2 Campos do Formulário CENA-RIO

| Campo       | Name                | Tipo     | Obrigatório | Validação             |
| ----------- | ------------------- | -------- | ----------- | --------------------- |
| Título      | `event_title`       | text     | ✅          | `sanitize_text_field` |
| Descrição   | `event_description` | textarea | ❌          | `wp_kses_post`        |
| Data Início | `event_start_date`  | date     | ✅          | `sanitize_text_field` |
| Data Fim    | `event_end_date`    | date     | ❌          | `sanitize_text_field` |
| Hora Início | `event_start_time`  | time     | ❌          | `sanitize_text_field` |
| Hora Fim    | `event_end_time`    | time     | ❌          | `sanitize_text_field` |
| Local       | `event_venue`       | text     | ❌          | `sanitize_text_field` |
| Latitude    | `event_lat`         | number   | ❌          | `floatval`            |
| Longitude   | `event_lng`         | number   | ❌          | `floatval`            |

### 7.3 Campos Template Completo (event-form.php)

| Campo       | Name          | Tipo            | Obrigatório | Validação               |
| ----------- | ------------- | --------------- | ----------- | ----------------------- |
| Título      | `title`       | text            | ✅          | maxlength=100           |
| Descrição   | `description` | textarea        | ✅          | maxlength=2000          |
| Data        | `date`        | date            | ✅          | min=today               |
| Hora Início | `time_start`  | time            | ✅          | -                       |
| Hora Fim    | `time_end`    | time            | ❌          | -                       |
| Local Nome  | `venue`       | text            | ✅          | -                       |
| Endereço    | `address`     | text            | ❌          | -                       |
| Preço       | `price`       | number          | ❌          | step=0.01, min=0        |
| Tipo Preço  | `price_type`  | radio           | ❌          | free/paid/donation      |
| Link        | `link`        | url             | ❌          | -                       |
| Gêneros     | `genres[]`    | checkbox        | ❌          | term_ids                |
| DJs         | `djs[]`       | hidden (via JS) | ❌          | post_ids                |
| Comunidade  | `community`   | select          | ❌          | post_id                 |
| Privacidade | `privacy`     | radio           | ❌          | public/private/unlisted |
| Capa        | `cover_image` | file            | ❌          | image/\*                |

---

## 8. 🔐 Security (Nonces, Capabilities)

### 8.1 Nonces

| Nonce Action               | Nonce Field                 | Arquivo                        | Linha    |
| -------------------------- | --------------------------- | ------------------------------ | -------- |
| `apollo_submit_event`      | `apollo_submit_event_nonce` | shortcodes-submit.php          | 30, 273  |
| `apollo_public_event`      | `apollo_event_nonce`        | public-event-form.php          | 36, 68   |
| `apollo_new_event_submit`  | `apollo_new_event_nonce`    | page-cenario-new-event.php     | 26, 74   |
| `apollo_cena_submit_event` | `apollo_cena_nonce`         | class-cena-rio-submissions.php | 445, 616 |
| `apollo_event_form`        | `nonce`                     | event-form.php                 | 109, 129 |
| `apollo_event_meta_save`   | `apollo_event_meta_nonce`   | admin-metaboxes.php            | 346, 856 |
| `apollo_admin_nonce`       | `nonce`                     | admin-metaboxes.php            | 108      |
| `apollo_events_nonce`      | `nonce`                     | ajax-handlers.php              | 31       |

### 8.2 Capabilities

| Capability                  | Propósito             | Arquivo                        | Linha                       |
| --------------------------- | --------------------- | ------------------------------ | --------------------------- |
| `create_event_listings`     | Criar eventos         | class-apollo-capabilities.php  | 55, 148, 192, 212, 230, 463 |
| `edit_event_listings`       | Editar eventos        | class-apollo-capabilities.php  | (várias)                    |
| `publish_event_listings`    | Publicar eventos      | class-apollo-capabilities.php  | (várias)                    |
| `apollo_submit_event`       | Submeter eventos CENA | class-apollo-roles-manager.php | 112, 117                    |
| `apollo_cena_submit_events` | Submeter eventos CENA | class-cena-rio-submissions.php | 158                         |
| `apollo_access_cena_rio`    | Acesso CENA-RIO       | class-cena-rio-submissions.php | 158                         |
| `apollo_create_event_plan`  | Criar plano de evento | class-activation.php           | 118                         |

### 8.3 Permission Callbacks (REST API)

| Endpoint                | Permission Check              | Arquivo                        | Linha |
| ----------------------- | ----------------------------- | ------------------------------ | ----- |
| POST `/events`          | `check_create_permission`     | class-events-controller.php    | 70    |
| PUT `/events/{id}`      | `check_update_permission`     | class-events-controller.php    | 95    |
| DELETE `/events/{id}`   | `check_delete_permission`     | class-events-controller.php    | 101   |
| POST `/cena-rio/enviar` | `check_submission_permission` | class-cena-rio-submissions.php | 66    |

---

## 9. 💾 Database Operations

### 9.1 wp_insert_post Calls (Event Creation)

| Arquivo                        | Linha   | Status Padrão |
| ------------------------------ | ------- | ------------- |
| shortcodes-submit.php          | 117-126 | `pending`     |
| class-cena-rio-submissions.php | 675-685 | `private`     |
| class-events-controller.php    | 366     | varies        |
| page-cenario-new-event.php     | 559     | `draft`       |
| admin-shortcodes-page.php      | 360     | varies        |
| class-event-cena-cpt.php       | 305     | `publish`     |

### 9.2 Meta Keys Utilizados

| Meta Key                      | Tipo      | Propósito                        |
| ----------------------------- | --------- | -------------------------------- |
| `_event_start_date`           | datetime  | Data/hora início                 |
| `_event_end_date`             | datetime  | Data/hora término                |
| `_event_start_time`           | string    | Hora início                      |
| `_event_end_time`             | string    | Hora término                     |
| `_event_dj_ids`               | array     | IDs dos DJs                      |
| `_event_local_ids`            | int/array | ID(s) do local                   |
| `_event_dj_slots`             | array     | Timetable/horários DJs           |
| `_event_timetable`            | array     | Alias para timetable             |
| `_apollo_frontend_submission` | string    | Flag submissão frontend          |
| `_apollo_submission_date`     | datetime  | Data submissão                   |
| `_apollo_source`              | string    | Fonte (e.g., 'cena-rio')         |
| `_apollo_cena_status`         | string    | Status CENA (expected/confirmed) |
| `_apollo_cena_submitted_by`   | int       | User ID submissor                |
| `_apollo_cena_submitted_at`   | datetime  | Timestamp submissão              |

---

## 10. 📤 File Upload Handling

### 10.1 Handlers de Upload

| Arquivo                            | Função/Método         | Linha         | Campo          |
| ---------------------------------- | --------------------- | ------------- | -------------- |
| shortcodes-submit.php              | `wp_handle_upload`    | 198           | `event_banner` |
| class-photos-module.php            | `media_handle_upload` | 667           | `photo`        |
| aprio-rest-matchmaking-profile.php | `wp_handle_upload`    | 473, 498, 623 | profile/logo   |

### 10.2 Configurações de Upload

```php
// shortcodes-submit.php:198
$upload = wp_handle_upload($_FILES['event_banner'], ['test_form' => false]);

// Após upload bem-sucedido:
$attachment = [
    'post_mime_type' => $upload['type'],
    'post_title'     => sanitize_file_name(basename($upload['file'])),
    'post_content'   => '',
    'post_status'    => 'inherit',
    'post_parent'    => $post_id,
];
$attach_id = wp_insert_attachment($attachment, $upload['file'], $post_id);
set_post_thumbnail($post_id, $attach_id);
```

### 10.3 Formatos Aceitos

| Formulário        | Formatos      | Tamanho Recomendado |
| ----------------- | ------------- | ------------------- |
| submit_event_form | JPG, PNG, GIF | 1200x600px          |
| event-form.php    | JPG, PNG      | 1200x630px          |

---

## 11. 📊 Fluxo de Submissão

### 11.1 Fluxo Público `[submit_event_form]`

```
1. Usuário acessa página com shortcode
2. Verifica login (redireciona se não logado)
3. Preenche formulário
4. Submissão POST com nonce
5. Validação de campos obrigatórios
6. wp_insert_post com status 'pending'
7. Salva meta fields
8. Processa upload de banner (se houver)
9. Limpa cache
10. Exibe mensagem de sucesso
```

### 11.2 Fluxo CENA-RIO

```
1. Usuário com role CENA acessa formulário
2. Verifica permissão apollo_cena_submit_events
3. Preenche formulário
4. Submissão POST ou REST API
5. Validação de campos
6. wp_insert_post com status 'private'
7. Marca _apollo_source = 'cena-rio'
8. Marca _apollo_cena_status = 'expected'
9. Evento visível apenas no calendário interno CENA
10. Moderador confirma → status 'confirmed', post 'draft'
11. MOD aprova → status 'publish' (público)
```

---

## 12. 🔗 Dependências Entre Plugins

| Função/Classe                       | Definido em           | Usado em                       |
| ----------------------------------- | --------------------- | ------------------------------ |
| `apollo_update_post_meta()`         | apollo-core           | apollo-events-manager          |
| `apollo_sanitize_timetable()`       | apollo-core           | apollo-events-manager          |
| `apollo_clear_events_cache()`       | apollo-events-manager | apollo-events-manager          |
| `Apollo_Local_Connection`           | apollo-events-manager | shortcodes-submit.php          |
| `Cena_Rio_Roles::user_can_submit()` | apollo-core           | class-cena-rio-submissions.php |

---

## 13. ⚠️ Notas de Duplicidade

### Shortcodes Duplicados

| Shortcode             | Locais de Registro                                                                              |
| --------------------- | ----------------------------------------------------------------------------------------------- |
| `submit_event_form`   | apollo-events-manager.php:844, shortcodes-submit.php:439, class-apollo-events-shortcodes.php:35 |
| `apollo_event_submit` | apollo-events-manager.php:839                                                                   |

### Mitigação

- Verificação `shortcode_exists()` antes de registrar
- Prioridade de registro controlada

---

## 14. 🧪 Arquivos de Teste Relacionados

| Arquivo                                                                       | Propósito          |
| ----------------------------------------------------------------------------- | ------------------ |
| [tests/test-mvp-flows.php](../apollo-events-manager/tests/test-mvp-flows.php) | Teste de fluxo MVP |
| [tests/test-rest-forms.php](../apollo-core/tests/test-rest-forms.php)         | Teste REST forms   |
| [tests/test-activation.php](../apollo-core/tests/test-activation.php)         | Teste capabilities |

---

## 15. 📚 Documentação Relacionada

- [DOCUMENTATION-v2.md](../apollo-events-manager/docs/DOCUMENTATION-v2.md) - Documentação geral
- [REST.md](../apollo-core/REST.md) - Documentação REST API
- [FRONTEND_ENTRYPOINTS_MAP.json](../apollo-core/docs/FRONTEND_ENTRYPOINTS_MAP.json) - Mapa de entrypoints
- [S6-DATA-RELATIONSHIPS.md](../apollo-core/docs/S6-DATA-RELATIONSHIPS.md) - Relacionamentos de dados

---

## 16. 🎯 Recomendações

### Consolidação Sugerida

1. **Unificar shortcodes** - Manter apenas `[apollo_event_submit]` como oficial
2. **Centralizar validação** - Criar classe `Apollo_Event_Validator`
3. **Padronizar meta keys** - Documentar e usar constantes
4. **Unificar handlers** - Consolidar AJAX handlers duplicados

### Segurança

1. ✅ Todos os forms usam nonces
2. ✅ Capabilities verificadas
3. ✅ Sanitização implementada
4. ⚠️ Rate limiting não implementado (considerar)

---

**Gerado por:** GitHub Copilot
**Versão do Inventário:** 1.0.0
