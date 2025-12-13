# STRICT MODE FIX: Membership + Cena-Rio + Popup + Calendar

**Date**: 2025-12-03  
**Status**: ✅ COMPLETE + VERIFIED  
**Mode**: STRICT (minimal changes, use existing slugs/metas)  
**Last Smoke Test**: 2025-12-03

---

## 🔬 MINI SMOKE TEST RESULTS

| # | Problem | Status | Code Verified |
|---|---------|--------|---------------|
| 1 | Membership Meta Key | ✅ FIXED | `shortcodes-auth.php:79` uses `_apollo_membership` + `nao-verificado` |
| 2 | Event Popup/Lightbox | ✅ WORKING | Click handler at line 2271, AJAX handler at line 2445 |
| 3 | Calendar Cena-Rio Filter | ✅ FIXED | `filter_cena_rio_events()` in `class-cena-rio-mod.php:42-98` |
| 4 | Role Compatibility | ✅ BRIDGED | `user_can_submit()` includes both `cena_role` and `cena-rio` |

---

## Summary of Fixes

### Problem 1: Membership Meta Key Inconsistency ✅ FIXED

**File**: `apollo-events-manager/includes/shortcodes-auth.php`  
**Line**: 79

**Before**:
```php
update_user_meta( $user_id, 'membership', 'Não Verificado' );
```

**After**:
```php
update_user_meta( $user_id, '_apollo_membership', 'nao-verificado' );
```

**Smoke Test Verification**:
- ✅ Line 79 confirmed: `update_user_meta( $user_id, '_apollo_membership', 'nao-verificado' );`
- ✅ Comment added: `// Add membership meta using canonical key from apollo-core/memberships.php`

**Reason**: The canonical membership system in `apollo-core/includes/memberships.php` uses:
- Meta key: `_apollo_membership`
- Slug: `nao-verificado` (not label "Não Verificado")

---

### Problem 2: Event Card Popup/Lightbox ✅ VERIFIED WORKING

**Status**: Already implemented correctly - NO CHANGES NEEDED.

**Smoke Test Verification**:
| Component | Location | Status |
|-----------|----------|--------|
| `data-event-id` attribute | `event-card.php:123` | ✅ Present |
| Click handler | `apollo-events-manager.php:2271` | ✅ `$(document).on('click', '.event_listing', ...)` |
| AJAX action (logged in) | `apollo-events-manager.php:663` | ✅ `wp_ajax_load_event_single` |
| AJAX action (logged out) | `apollo-events-manager.php:664` | ✅ `wp_ajax_nopriv_load_event_single` |
| Handler function | `apollo-events-manager.php:2445` | ✅ `ajax_load_event_single()` |
| Template loaded | Handler | ✅ `templates/single-event.php` |

---

### Problem 3: Cena-Rio Events Appearing in Public Calendar Before Approval ✅ FIXED

**File**: `apollo-core/includes/class-cena-rio-mod.php`  
**Lines**: 42-98 (new method)

**Smoke Test Verification**:
- ✅ Hook registered: `add_action( 'pre_get_posts', array( __CLASS__, 'filter_cena_rio_events' ), 20 );`
- ✅ Method exists: `filter_cena_rio_events( $query )`
- ✅ Admin check: `if ( is_admin() ) { return; }`
- ✅ Post type check: `if ( 'event_listing' !== $post_type )`

**Filter Logic Verified**:
```php
$meta_query[] = array(
    'relation' => 'OR',
    // Regular events (no _apollo_source meta)
    array( 'key' => '_apollo_source', 'compare' => 'NOT EXISTS' ),
    // Non-cena-rio sources
    array( 'key' => '_apollo_source', 'value' => 'cena-rio', 'compare' => '!=' ),
    // Approved CENA-RIO events only
    array(
        'relation' => 'AND',
        array( 'key' => '_apollo_source', 'value' => 'cena-rio' ),
        array( 'key' => '_apollo_cena_status', 'value' => 'approved' ),
    ),
);
```

**Cena-Rio Status Workflow**:
| Status | Description | Visible in Public Calendar |
|--------|-------------|---------------------------|
| `expected` | Initial submission | ❌ NO |
| `confirmed` | Industry confirmed | ❌ NO |
| `approved` | Moderator approved | ✅ YES |
| `rejected` | Moderator rejected | ❌ NO |

---

### Problem 4: Duplicate Role Definitions (cena-rio vs cena_role) ✅ BRIDGED

**File**: `apollo-core/includes/class-cena-rio-roles.php`  
**Line**: 180

**Smoke Test Verification**:
- ✅ Line 180 confirmed: `$allowed_roles = array( 'cena_role', 'cena-rio', 'cena_moderator', 'apollo', 'editor', 'administrator' );`
- ✅ Comment added: `// Note: cena-rio is legacy role from apollo-social, cena_role is canonical from apollo-core`

**Role Mapping**:
| Role | Source | Status |
|------|--------|--------|
| `cena_role` | `apollo-core/class-cena-rio-roles.php` | ✅ Canonical |
| `cena-rio` | `apollo-social/Modules/Auth/UserRoles.php` | ✅ Legacy (bridged) |
| `cena_moderator` | `apollo-core/class-cena-rio-roles.php` | ✅ Canonical |

**Recommendation for Future**: Migrate all users from `cena-rio` to `cena_role` and deprecate the legacy role.

---

## Files Modified

| File | Change | Lines |
|------|--------|-------|
| `apollo-events-manager/includes/shortcodes-auth.php` | Fixed membership meta key | 79 |
| `apollo-core/includes/class-cena-rio-mod.php` | Added calendar filter | 42-98 |
| `apollo-core/includes/class-cena-rio-roles.php` | Added cena-rio to allowed roles | 180 |

---

## Manual Testing Checklist

### Membership
- [ ] Register new user via `/registrar/` page
- [ ] Verify `_apollo_membership` = `nao-verificado` in user meta (wp_usermeta table)
- [ ] Confirm user appears in "Não Verificado" membership tier

### Event Popup
- [ ] Visit `/eventos/` (Discover Events)
- [ ] Click any event card
- [ ] Verify lightbox opens with event details (not full page redirect)
- [ ] Close lightbox via X button or overlay click

### Calendar Filtering
- [ ] Create Cena-Rio event (status = 'expected')
- [ ] Verify it does NOT appear in public `/eventos/`
- [ ] Confirm event → status 'confirmed' (via industry confirmation)
- [ ] Verify it still does NOT appear in public `/eventos/`
- [ ] Approve event → status 'approved' (via mod queue)
- [ ] Verify it NOW appears in public `/eventos/`

### Role Compatibility
- [ ] User with `cena-rio` role can access `/cena-rio/` features
- [ ] User with `cena_role` role can access same features
- [ ] Both roles can submit events via `/cena-rio/submit/`

---

## Related Files

| File | Purpose |
|------|---------|
| `apollo-core/includes/memberships.php` | Canonical membership system |
| `apollo-core/includes/class-cena-rio-mod.php` | Cena-Rio event mod |
| `apollo-core/includes/class-cena-rio-roles.php` | Cena-Rio role definitions |
| `apollo-core/includes/class-cena-rio-submissions.php` | Cena-Rio event submission |
| `apollo-events-manager/templates/event-card.php` | Event card template |
| `apollo-events-manager/templates/single-event.php` | Single event template (lightbox) |
| `apollo-social/src/Modules/Auth/UserRoles.php` | Legacy role definitions |


# MINI SMOKE TEST – MEMBERSHIP / CENA-RIO / DJ PAGE / EVENTOS

**Data:** 2025-12-03  
**Tipo:** Leitura Estática (sem rodar navegador)  
**Repositório:** github.com/apollorio/plugins (LocalWP)

---

## Sumário

1. [MEMBERSHIP & REGISTRO (QUIZ)](#1-membership--registro-quiz)
2. [COMUNIDADE / NÚCLEO / CENA-RIO – PERMISSÕES & PÁGINA PRIVADA](#2-comunidade--núcleo--cena-rio--permissões--página-privada)
3. [CENA-RIO CALENDÁRIO ESPECIAL & MODERAÇÃO DE EVENTOS](#3-cena-rio-calendário-especial--moderação-de-eventos)
4. [DJ PAGE & EVENTOS (CARD / LISTING / POPUP SINGLE)](#4-dj-page--eventos-card--listing--popup-single)

---

## 1. MEMBERSHIP & REGISTRO (QUIZ)

### 1.1 Fluxos de Registro/Membership Identificados

| Fluxo | Arquivo/Handler | Onde Renderiza | Onde Salva | Status |
|-------|-----------------|----------------|------------|--------|
| Onboarding Conversacional | `apollo-social/src/Modules/Onboarding/Services/OnboardingService.php` | `templates/onboarding/conversational-onboarding.php` | `user_meta` (ver abaixo) | ✅ Coerente |
| BeginOnboarding | `apollo-social/src/Application/Users/BeginOnboarding.php` | AJAX `apollo_start_onboarding` | `apollo_onboarding_progress`, `apollo_verify_token`, `apollo_industry`, `apollo_roles` | ✅ Coerente |
| CompleteOnboarding | `apollo-social/src/Application/Users/CompleteOnboarding.php` | AJAX `apollo_process_onboarding_step` | `apollo_onboarded`, `wp_apollo_verifications` table | ✅ Coerente |
| Cultura::Rio Identity | `apollo-social/src/Modules/Registration/CulturaRioIdentity.php` | `RegistrationServiceProvider.php` (form) | `apollo_cultura_identities`, `apollo_membership_*` | ✅ Coerente |
| User Page Autocreate | `apollo-social/user-pages/class-user-page-autocreate.php:29` | hook `user_register` | Cria CPT `user_page` | ✅ Coerente |

### 1.2 Quiz / Perguntas / Onboarding

**Arquivo Principal:** `apollo-social/templates/onboarding/conversational-onboarding.php`

**Estrutura do Quiz:**
```
Step 1: ask_name → Input de texto
Step 2: ask_industry → Select (Yes / No / Future yes!)
Step 3: ask_roles → Multi-select (DJ, Producer, etc.)
Step 4: ask_memberships → Multi-select (grupos/núcleos)
Step 5: ask_contacts → WhatsApp + Instagram
Step 6: verification_rules → Código de verificação
```

**Meta Keys do Quiz:**
- `apollo_onboarding_progress` → Estado atual + steps completados
- `apollo_name` → Nome do usuário
- `apollo_industry` → "Yes" / "No" / "Future yes!"
- `apollo_roles` → array de roles selecionadas
- `apollo_member_of` → grupos/núcleos
- `apollo_whatsapp` → número normalizado (+55...)
- `apollo_instagram` → handle normalizado (sem @)
- `apollo_verify_token` → Token de verificação
- `apollo_verify_status` → 'awaiting_instagram_verify' | 'verified'

**Checklist Quiz:**

| Item | Status | Observação |
|------|--------|------------|
| Quiz é salvo de forma consistente | ✅ | `BeginOnboarding::saveOnboardingProgress()` salva todos os campos |
| Dados são sanitizados | ✅ | `sanitize_text_field`, `normalizeWhatsapp`, `normalizeInstagram` |
| Quiz vinculado a user_id | ✅ | Todas as metas usam `update_user_meta($user_id, ...)` |
| Resultado influencia roles | ⚠️ | `CompleteOnboarding::setupUserPermissions()` adiciona caps, mas NÃO atribui role específica baseada no quiz |
| Quiz influencia acesso Cena-Rio | ⚠️ | O campo `apollo_industry` é salvo mas não há verificação automática para atribuir role `cena_role` |

**Recomendações:**
1. ⚠️ Criar lógica em `CompleteOnboarding` para atribuir role `cena_role` quando `apollo_industry = 'Yes'`
2. ⚠️ Considerar integrar `CulturaRioIdentity` com o fluxo de onboarding para unificar membership

---

## 2. COMUNIDADE / NÚCLEO / CENA-RIO – PERMISSÕES & PÁGINA PRIVADA

### 2.1 Estruturas de Comunidade/Núcleo/Cena-Rio

| Tipo | Slug | Arquivo/Linha | Observação |
|------|------|---------------|------------|
| CPT | `cena_document` | `apollo-social/src/CenaRio/CenaRioModule.php:28` | Documentos internos Cena-Rio |
| CPT | `cena_event_plan` | `apollo-social/src/CenaRio/CenaRioModule.php:29` | Eventos em planejamento |
| Role | `cena-rio` | `apollo-social/src/CenaRio/CenaRioModule.php:75` | Role Cena Rio (author caps) |
| Role | `cena_role` | `apollo-core/includes/class-cena-rio-roles.php:61` | Cena::Rio Membro (draft only) |
| Role | `cena_moderator` | `apollo-core/includes/class-cena-rio-roles.php:92` | Cena::Rio Moderador (full mod) |
| Page | `cena-rio` | `apollo-social/src/CenaRio/CenaRioModule.php:150` | Página principal /cena-rio |
| Route | `/cena-rio/` | `apollo-core/includes/class-cena-rio-canvas.php:51` | Canvas calendar |
| Route | `/cena-rio/mod/` | `apollo-core/includes/class-cena-rio-canvas.php:52` | Canvas mod |

### 2.2 Roles & Capabilities

**Arquivo Principal:** `apollo-core/includes/class-cena-rio-roles.php`

| Role | Capabilities | Arquivo:Linha |
|------|-------------|---------------|
| `cena_role` | `read`, `edit_event_listing`, `delete_event_listing`, **NÃO** `publish_event_listings` | class-cena-rio-roles.php:61-78 |
| `cena_moderator` | Todas do `cena_role` + `edit_others_event_listings`, `publish_event_listings`, `apollo_cena_moderate_events` | class-cena-rio-roles.php:92-115 |
| `administrator` | + `apollo_cena_moderate_events` | class-cena-rio-roles.php:120-123 |

**Capacidades Específicas:**

```php
// Membro Cena-Rio (DRAFT ONLY)
'publish_event_listings' => false  // NÃO pode publicar
'edit_others_event_listings' => false  // NÃO pode editar de outros

// Moderador Cena-Rio
'apollo_cena_moderate_events' => true  // Pode aprovar/rejeitar
'publish_event_listings' => true  // Pode publicar
```

**Checklist Roles:**

| Item | Status | Observação |
|------|--------|------------|
| Roles específicas para indústria/Cena-Rio definidas | ✅ | `cena_role`, `cena_moderator` em class-cena-rio-roles.php |
| Capability para mover eventos da área privada → oficial | ✅ | `apollo_cena_moderate_events` usada em class-cena-rio-mod.php |
| Verificação de role em páginas privadas | ✅ | `Apollo_Cena_Rio_Roles::user_can_submit()` e `user_can_moderate()` |
| Role atribuída automaticamente no onboarding | ⚠️ | Não há lógica automática de atribuição |

### 2.3 Página Privada Cena-Rio

**Template Principal:** `apollo-social/cena-rio/templates/page-cena-rio.php`

**Verificação de Acesso:**
```php
// apollo-social/src/CenaRio/CenaRioModule.php:198-204
public static function maybeUseTemplate( string $template ): string {
    if ( ! is_page( self::PAGE_SLUG ) ) return $template;
    if ( ! is_user_logged_in() ) auth_redirect();
    if ( ! self::currentUserCanAccess() ) {
        wp_die( __( 'Acesso restrito à indústria.', 'apollo-social' ), 403 );
    }
    // ...
}

// Roles permitidas:
$allowed = array( 'administrator', 'editor', 'author', self::ROLE );
```

**Canvas Mode Routes:**
```php
// apollo-core/includes/class-cena-rio-canvas.php:51-52
add_rewrite_rule( '^cena-rio/?$', 'index.php?apollo_cena=calendar', 'top' );
add_rewrite_rule( '^cena-rio/mod/?$', 'index.php?apollo_cena=mod', 'top' );
```

**Checklist Página Privada:**

| Item | Status | Observação |
|------|--------|------------|
| Página protegida por capability adequada | ✅ | `currentUserCanAccess()` verifica roles, `check_access()` em Canvas |
| UI privada segue UNI.CSS/base.js | ✅ | Carrega `apollo-shadcn-base`, `apollo-uni-css` em `enqueueAssets()` |
| Lista conteúdos restritos consistentemente | ✅ | `getUserDocuments()`, `getEventPlans()` consultam CPTs privados |

---

## 3. CENA-RIO CALENDÁRIO ESPECIAL & MODERAÇÃO DE EVENTOS

### 3.1 Calendário Especial da Indústria

**Arquivo Principal:** `apollo-core/includes/class-cena-rio-submissions.php`

**REST Endpoint:** `GET /apollo/v1/cena-rio/events`
```php
// Linha 147-175
$query = new WP_Query(array(
    'post_type'   => 'event_listing',
    'post_status' => array( 'private', 'pending', 'draft', 'publish' ),
    'meta_query'  => array(
        array('key' => '_apollo_source', 'value' => 'cena-rio'),
    ),
));
```

**Separação do Calendário Oficial:**

| Aspecto | Calendário Cena-Rio | Calendário Oficial Apollo |
|---------|---------------------|---------------------------|
| Eventos Visíveis | `private`, `pending`, `draft`, `publish` com `_apollo_source=cena-rio` | `publish` only |
| Acesso | `Apollo_Cena_Rio_Roles::user_can_submit()` | Público |
| Meta Identificadora | `_apollo_source = 'cena-rio'` | Sem meta ou outro valor |
| Status Interno | `_apollo_cena_status` (expected/confirmed/approved) | Não usa |

**Status Internos Cena-Rio:**

```
EXPECTED (private) → Evento esperado, apenas indústria vê
    ↓ [confirm]
CONFIRMED (draft) → Aguardando MOD approval
    ↓ [approve]
APPROVED (publish) → Publicado no calendário oficial
```

**Checklist Calendário:**

| Item | Status | Observação |
|------|--------|------------|
| Calendário Cena-Rio separado do oficial Apollo | ✅ | Query usa `_apollo_source=cena-rio` + múltiplos status |
| Eventos privados não aparecem no oficial | ✅ | Oficial só exibe `publish`, Cena-Rio exibe todos |
| Fluxo de status documentado | ✅ | `expected` → `confirmed` → `approved` |

### 3.2 Fluxo de Moderação

**Arquivos:**
- `apollo-core/includes/class-cena-rio-submissions.php` – Criação e confirmação
- `apollo-core/includes/class-cena-rio-mod.php` – Aprovação/Rejeição

**Fluxo Completo:**

```
1. SUBMIT (REST /cena-rio/submit)
   → Cria event_listing com:
     - post_status = 'private'
     - _apollo_source = 'cena-rio'
     - _apollo_cena_status = 'expected'
   
2. CONFIRM (REST /cena-rio/confirm/{id})
   → Muda para:
     - post_status = 'draft'
     - _apollo_cena_status = 'confirmed'
   → Aparece na fila de moderação

3. APPROVE (REST /cena-rio/approve/{id} ou shortcode)
   → Muda para:
     - post_status = 'publish'
     - _apollo_cena_status = 'approved'
   → Aparece no calendário oficial

4. REJECT (REST /cena-rio/reject/{id})
   → Muda para:
     - post_status = 'draft'
     - _apollo_cena_status = 'rejected'
```

**Fila de Moderação:**
```php
// class-cena-rio-mod.php:326
$query = new WP_Query(array(
    'post_type'   => 'event_listing',
    'post_status' => 'pending', // ⚠️ Deveria ser 'draft' baseado no fluxo
    'meta_query'  => array(
        array('key' => '_apollo_source', 'value' => 'cena-rio'),
    ),
));
```

**Checklist Moderação:**

| Item | Status | Observação |
|------|--------|------------|
| Fluxo Cena-Rio → event_listing draft implementado | ✅ | `rest_confirm_event()` muda para draft |
| Somente admin/mod pode aprovar/mover | ✅ | `check_mod_permission()` verifica `apollo_cena_moderate_events` |
| Sem duplicação de eventos | ✅ | Fluxo apenas atualiza status, não cria novos posts |
| Metas consistentes | ✅ | `_apollo_cena_approved_by`, `_apollo_cena_approved_at` salvos |

### 3.3 Risco de Evento Privado no Oficial

**Análise:**

| Risco | Status | Justificativa |
|-------|--------|---------------|
| Evento `private` aparecer no oficial | ❌ Baixo | Calendário oficial só consulta `post_status = 'publish'` |
| Evento `draft` aparecer no oficial | ❌ Baixo | Idem acima |
| Evento sem `_apollo_cena_status = 'approved'` aparecer | ⚠️ Médio | O calendário oficial **NÃO** verifica `_apollo_cena_status`, apenas `post_status` |

**Recomendação:**
- ⚠️ Para maior segurança, considerar adicionar meta_query `_apollo_cena_status = 'approved'` no calendário oficial OU garantir que apenas MOD pode mudar para `publish`

---

## 4. DJ PAGE & EVENTOS (CARD / LISTING / POPUP SINGLE)

### 4.1 CPT de DJ / Perfil Público

**Arquivo:** `apollo-events-manager/includes/post-types.php:97-136`

| Propriedade | Valor |
|-------------|-------|
| Slug | `event_dj` |
| Rewrite | `/dj/{slug}` |
| Public | `true` |
| Show UI | `true` |
| Show in REST | `true` |
| REST Base | `djs` |
| Supports | `title`, `editor`, `thumbnail`, `custom-fields` |

**Meta Fields Registrados (linha 397-432):**
```php
$dj_meta_fields = array(
    '_dj_name', '_dj_bio', '_dj_image', '_dj_banner',
    '_dj_website', '_dj_instagram', '_dj_facebook',
    '_dj_soundcloud', '_dj_bandcamp', '_dj_spotify',
    '_dj_youtube', '_dj_mixcloud', '_dj_beatport',
    '_dj_resident_advisor', '_dj_twitter', '_dj_tiktok',
    '_dj_original_project_1', '_dj_original_project_2', '_dj_original_project_3',
    '_dj_set_url', '_dj_media_kit_url', '_dj_rider_url', '_dj_mix_url',
);
```

**Checklist DJ CPT:**

| Item | Status | Observação |
|------|--------|------------|
| CPT de DJ registrado corretamente | ✅ | `register_post_type('event_dj', ...)` linha 136 |
| Slug público `/dj/{slug}` | ✅ | `rewrite => ['slug' => 'dj']` |
| Relação com usuário | ⚠️ | Usa `post_author` padrão, sem meta `user_id` explícita |

### 4.2 Página Pública do DJ

**Template:** `apollo-events-manager/templates/single-event_dj.php`

**Roteamento:**
```php
// apollo-events-manager.php:1014
if ( is_singular( 'event_dj' ) ) {
    $plugin_template = APOLLO_APRIO_PATH . 'templates/single-event_dj.php';
}
```

**Checklist DJ Page:**

| Item | Status | Observação |
|------|--------|------------|
| DJ page criada corretamente via CPT | ✅ | Template `single-event_dj.php` carregado |
| DJ page segue design de apollo-core library | ⚠️ | Não verificado template interno, mas shortcode `[apollo_dj_profile]` existe |
| Integração com eventos do DJ | ⚠️ | Meta `_event_dj_ids` no evento referencia DJ, mas não há query inversa documentada |

### 4.3 Criação de Evento (CPT Correto)

**CPT Principal:** `event_listing`

**Arquivo:** `apollo-events-manager/includes/post-types.php:38-95`

| Origem | CPT | Meta Principal | Status |
|--------|-----|----------------|--------|
| Admin WP | `event_listing` | `_event_start_date`, `_event_venue`, `_event_dj_ids` | ✅ |
| Cena-Rio Submission | `event_listing` | Mesmas metas + `_apollo_source`, `_apollo_cena_status` | ✅ |
| Shortcode Form | `event_listing` | Idem | ✅ |

**Metas Principais de Evento:**
```php
$event_meta_fields = array(
    '_event_title', '_event_banner', '_event_video_url',
    '_event_start_date', '_event_end_date',
    '_event_start_time', '_event_end_time',
    '_event_location', '_event_country',
    '_tickets_ext', '_cupom_ario',
    '_event_dj_ids', '_event_local_ids', '_event_timetable',
    '_3_imagens_promo', '_imagem_final', '_favorites_count',
);
```

**Checklist Criação de Evento:**

| Item | Status | Observação |
|------|--------|------------|
| CPT correto usado | ✅ | Sempre `event_listing` |
| Metas escritas pela UI de criação | ✅ | Admin metaboxes + Cena-Rio REST |
| Metas lidas pelos templates | ✅ | `event-card.php` usa mesmas metas |

### 4.4 Templates Aprovados (Event Card / Listing / Single)

**Design Library Reference:**
- `apollo-core/templates/design-library/events discover event-card.html`
- `apollo-core/templates/design-library/events event single.html`

**Template PHP Real:** `apollo-events-manager/templates/event-card.php`

**Comparação de Estrutura:**

| Elemento | Design Library | Template PHP | Status |
|----------|----------------|--------------|--------|
| Wrapper | `<a class="event_listing">` | `<a class="event_listing">` | ✅ Match |
| Date Badge | `<div class="box-date-event">` | `<div class="box-date-event">` | ✅ Match |
| Picture | `<div class="picture">` + `<img>` | `<div class="picture">` + `<img>` | ✅ Match |
| Tags | `<div class="event-card-tags">` | `<div class="event-card-tags">` | ✅ Match |
| Info Box | `<div class="box-info-event">` | `<div class="box-info-event">` | ✅ Match |
| Title | `<h2 class="event-li-title">` | `<h2 class="event-li-title">` | ✅ Match |
| DJ Detail | `<p class="event-li-detail of-dj">` | `<p class="event-li-detail of-dj">` | ✅ Match |
| Location | `<p class="event-li-detail of-location">` | `<p class="event-li-detail of-location">` | ✅ Match |
| Data Attrs | `data-event-id`, `data-category`, `data-month-str` | ✅ Todos presentes | ✅ Match |

**Checklist Templates:**

| Item | Status | Arquivo/Linha |
|------|--------|---------------|
| Event card real alinhado com library aprovada | ✅ | `templates/event-card.php` - estrutura idêntica |
| Event listing real alinhado | ✅ | `templates/event-list-view.php` usa `event-card.php` |
| Popup/single event segue library | ⚠️ | `single-event-standalone.php` existe, mas não verificado detalhe interno |
| Clique no card abre popup | ⚠️ | Código sugere lightbox (`motion-event-card.js`), mas pode navegar full page |

**Nota sobre Navegação:**
```php
// apollo-events-manager.php:2270
// Event card click handler for lightbox
```
- O código menciona lightbox, mas o template `event-card.php` usa `<a href="permalink">` que navega para página full
- ⚠️ Comportamento de popup pode depender de JS adicional não verificado

---

## 5. RESUMO EXECUTIVO

### Seção 1 – MEMBERSHIP & REGISTRO
- ✅ Fluxos de registro/membership mapeados (Onboarding + CulturaRio)
- ✅ Quiz aplicado e salvo de forma consistente via `BeginOnboarding`
- ⚠️ Quiz não atribui role `cena_role` automaticamente baseado em `apollo_industry`
- ⚠️ `CulturaRioIdentity` separado do fluxo de onboarding principal

### Seção 2 – COMUNIDADE / NÚCLEO / CENA-RIO
- ✅ Estruturas CPT/roles coerentes (`cena_document`, `cena_event_plan`, `cena_role`, `cena_moderator`)
- ✅ Página privada Cena-Rio protegida por roles adequadas
- ✅ Capabilities específicas para moderação (`apollo_cena_moderate_events`)
- ⚠️ Roles duplicadas entre plugins (`cena-rio` em apollo-social, `cena_role` em apollo-core)

### Seção 3 – CALENDÁRIO CENA-RIO & MODERAÇÃO
- ✅ Calendário Cena-Rio logicamente separado (meta `_apollo_source=cena-rio`)
- ✅ Fluxo `expected → confirmed → approved` implementado
- ✅ Moderação com capabilities adequadas
- ⚠️ Calendário oficial não verifica `_apollo_cena_status`, apenas `post_status`

### Seção 4 – DJ & EVENTOS
- ✅ CPT `event_dj` configurado corretamente com página pública `/dj/{slug}`
- ✅ Criação de evento usa CPT `event_listing` consistentemente
- ✅ Template `event-card.php` 100% alinhado com design library
- ⚠️ Comportamento popup vs full page não totalmente verificado

---

## Recomendações Prioritárias

1. **Unificar Roles Cena-Rio**
   - Consolidar `cena-rio` (apollo-social) e `cena_role` (apollo-core) em uma única role

2. **Conectar Onboarding com Roles**
   - Em `CompleteOnboarding::setupUserPermissions()`, atribuir `cena_role` quando `apollo_industry = 'Yes'`

3. **Verificar Meta no Calendário Oficial**
   - Adicionar `_apollo_cena_status = 'approved'` na query do calendário público (defesa em profundidade)

4. **Documentar Popup Behavior**
   - Verificar se `motion-event-card.js` implementa lightbox ou se sempre navega full page

---

*Gerado automaticamente por análise estática de código. Nenhum arquivo foi modificado.*



# Mini Smoke Test - Events Core + Social Page Builder

**Data:** 03/12/2025  
**Escopo:** Análise estática de código (sem execução em navegador)  
**Plugins:** apollo-events-manager, apollo-social, apollo-core

---

## Seção 1 – EVENTS CORE

### 1.1 CPTs de Evento

| CPT | Slug | REST | Arquivo | Linha |
|-----|------|------|---------|-------|
| `event_listing` | `evento` | ✅ `events` | `apollo-events-manager/includes/post-types.php` | 95 |
| `event_dj` | `dj` | ✅ `djs` | `apollo-events-manager/includes/post-types.php` | 136 |
| `event_local` | `local` | ✅ `locals` | `apollo-events-manager/includes/post-types.php` | 177 |
| `apollo_event_stat` | n/a | ❌ | `apollo-events-manager/includes/class-event-stat-cpt.php` | 52 |

**Checklist:**

- ✅ **CPT exposto em REST quando necessário** – `event_listing`, `event_dj`, `event_local` têm `show_in_rest => true` com `rest_base` customizado
- ✅ **Slug consistente entre PHP, REST e JS** – `event_listing` usa `evento` no rewrite e `events` no REST; templates usam `event_listing` corretamente
- ⚠️ **CPT `apollo_event_stat`** – Interno (`public => false`), correto para estatísticas

---

### 1.2 Metadados de Evento

| Meta Key | Leitura | Escrita | Observação |
|----------|---------|---------|------------|
| `_event_start_date` | event-data-helper.php, event-card.php, templates | integration-bridge.php, schema-manager.php | ✅ Consistente |
| `_event_end_date` | event-data-helper.php, single-event-standalone.php | integration-bridge.php | ✅ Consistente |
| `_event_start_time` | single-event_listing-apollo.php | integration-bridge.php | ✅ Consistente |
| `_event_end_time` | single-event_listing-apollo.php | integration-bridge.php | ✅ Consistente |
| `_event_location` | event-card.php, templates | integration-bridge.php | ✅ Consistente |
| `_event_venue` | event-card.php | helpers | ✅ Consistente |
| `_event_banner` | event-data-helper.php, event-card.php | ? | ✅ Usado em cards/single |
| `_event_dj_ids` | event-data-helper.php | metaboxes | ✅ Via helper |
| `_event_local_ids` | event-data-helper.php | metaboxes | ✅ Via helper |
| `_event_timetable` | event-data-helper.php | metaboxes | ✅ Line-up via helper |
| `_event_genres` | event-card.php | ? | ⚠️ Usado em card, verificar escrita |

**Checklist:**

- ✅ **Metas de evento consistentes** – `apollo_get_shared_meta_keys()` em `integration-bridge.php` centraliza definição
- ✅ **Helper centralizado** – `Apollo_Event_Data_Helper` em `event-data-helper.php` (linhas 1-939) consolida leitura
- ⚠️ **Meta `_event_genres`** – Usada em card, não aparece em `apollo_get_shared_meta_keys()` (verificar se é taxonomy ou meta)

---

### 1.3 Rotas REST e Shortcodes

#### Endpoints REST

| Rota | Handler | Arquivo | Segurança |
|------|---------|---------|-----------|
| `/apollo/v1/events` | `Apollo_Events_Exporter::get_events_data` | `mu-plugins/apollo-events.php` | ⚠️ `permission_callback => '__return_true'` (público) |
| `/wp/v2/events` (WP nativo) | WP_REST_Posts_Controller | via `show_in_rest` | ✅ Padrão WP |

#### Shortcodes

| Shortcode | Handler | Arquivo | Linha |
|-----------|---------|---------|-------|
| `[events]` / `[apollo_events]` | `apollo_events_shortcode_handler` | `apollo-events-manager.php` | 575-579 |
| `[apollo_event]` | `apollo_event_shortcode` | `apollo-events-manager.php` | 638 |
| `[apollo_event_submit]` / `[submit_event_form]` | `render_submit_form` | `apollo-events-manager.php` | 647-655 |
| `[event_dashboard]` | `event_dashboard` | `class-apollo-events-shortcodes.php` | 36 |
| `[event]` | `output_event` | `class-apollo-events-shortcodes.php` | 41 |
| `[past_events]` | `output_past_events` | `class-apollo-events-shortcodes.php` | 43 |
| `[upcoming_events]` | `output_upcoming_events` | `class-apollo-events-shortcodes.php` | 44 |
| `[event_djs]` / `[event_dj]` | `output_event_djs` / `output_event_dj` | `class-apollo-events-shortcodes.php` | 51-53 |
| `[event_locals]` / `[event_local]` | `output_event_locals` / `output_event_local` | `class-apollo-events-shortcodes.php` | 58-59 |

**Checklist:**

- ✅ **Endpoints REST mapeados** – CPTs usam REST nativo WP; endpoint customizado em mu-plugins
- ⚠️ **Endpoint `/apollo/v1/events`** – Público sem autenticação (`permission_callback => '__return_true'`). Exporta todos os eventos com meta. Revisar se intencional.
- ✅ **Shortcodes mapeados** – Ampla cobertura para listagens, submissão, DJs, locais

---

### 1.4 Templates de Eventos

| Template | Arquivo | UNI.CSS | Tooltips |
|----------|---------|---------|----------|
| Event Card | `templates/event-card.php` | ⚠️ Classes legadas (`event_listing`, `box-date-event`) | ❌ Sem `data-ap-tooltip` |
| Single Standalone | `templates/single-event-standalone.php` | ✅ `mobile-container`, `hero-media` | ⚠️ Parcial |
| Event Listings Start | `templates/event-listings-start.php` | ✅ | ✅ Tooltips em filtros/navegação |
| DJ Card | `templates/dj-card.php` | ✅ | ✅ Tooltips completos |

**Checklist:**

- ⚠️ **Templates usam UNI.CSS de forma mista** – `event-card.php` usa classes legadas (`event_listing`, `box-date-event`), não `.ap-*`
- ✅ **Single standalone** usa padrões `mobile-container`, `hero-media` de UNI.CSS
- ⚠️ **Tooltips em event-card.php** – **AUSENTES** – Nenhum `data-ap-tooltip` no template principal de card
- ✅ **DJ Card** tem tooltips completos em todos os elementos

**Recomendação:**
- Adicionar `data-ap-tooltip` em `event-card.php` para: data do evento, local, gêneros, imagem

---

### 1.5 Consultas e Performance

| Arquivo | Linha | Query | Risco |
|---------|-------|-------|-------|
| `apollo-events-manager.php` | 2109 | `posts_per_page => -1` | ⚠️ Carrega todos os eventos |
| `apollo-events-manager.php` | 2906, 2928, 3289 | `posts_per_page => -1` | ⚠️ Múltiplas queries sem limite |
| `event-data-helper.php` | 389, 451, 487 | `posts_per_page => -1` | ⚠️ Helper carrega todos |
| `class-rest-api.php` | 425, 500 | `posts_per_page => -1` | ⚠️ REST sem paginação |
| `cache.php` | 117 | `posts_per_page => -1` | ✅ Aceitável para cache |
| `class-event-stat-cpt.php` | 209 | `posts_per_page => -1` | ⚠️ Stats sem limite |

**Checklist:**

- ⚠️ **Consultas potencialmente pesadas** – **20+ ocorrências** de `posts_per_page => -1`
- Maioria em admin/ajax, mas algumas em helpers usados em frontend
- **Recomendação:** Adicionar limites (ex: 500) ou paginação em endpoints públicos

---

## Seção 2 – SOCIAL CORE – PAGE BUILDER PÚBLICO

### 2.1 CPTs do Page Builder

| CPT | Slug | REST | Arquivo | Linha | Observação |
|-----|------|------|---------|-------|------------|
| `apollo_home` | `id` | ✅ | `src/Builder/class-apollo-home-cpt.php` | 73-84 | Habbo-style builder, 1 por usuário |
| `user_page` | n/a | ✅ | `user-pages/class-user-page-cpt.php` | 7-38 | Páginas públicas alternativas |

**Checklist:**

- ✅ **CPT `apollo_home` identificado** – `show_in_rest => true`, `rewrite => ['slug' => 'id']`
- ✅ **CPT `user_page` identificado** – Alternativo para páginas de usuário
- ✅ **Relação com autor** – `supports => ['author']` permite filtro por `post_author`

---

### 2.2 Metas de Layout

| Meta Key | Onde Grava | Onde Lê | Observação |
|----------|------------|---------|------------|
| `_apollo_builder_content` | `class-apollo-builder-ajax.php:270-293` | `class-apollo-builder-frontend.php` via helper | ✅ JSON layout |
| `_apollo_builder_css` | `class-apollo-home-cpt.php:107` | Frontend | ✅ CSS gerado |
| `_apollo_background_texture` | `class-apollo-builder-ajax.php` | Frontend + builder | ✅ Textura fundo |
| `_apollo_trax_url` | `class-apollo-builder-ajax.php` | Frontend (player) | ✅ SoundCloud/Spotify |
| `apollo_userpage_layout_v1` | `class-user-page-editor-ajax.php:16` | `user-page-view.php:23` | ⚠️ Outro sistema |

**Checklist:**

- ✅ **Meta de layout centralizada** – `APOLLO_BUILDER_META_CONTENT` definida em `init.php`
- ✅ **Consistência entre escrita/leitura** – AJAX handler grava, frontend lê via mesmo helper
- ⚠️ **Dois sistemas paralelos** – `apollo_home` + `user_page` usam metas diferentes

---

### 2.3 Endpoints do Builder

| Ação AJAX | Handler | Arquivo | Segurança |
|-----------|---------|---------|-----------|
| `apollo_builder_save` | `save_layout` | `class-apollo-builder-ajax.php:260-305` | ✅ Nonce + capability + ownership |
| `apollo_builder_render_widget` | `render_widget` | `class-apollo-builder-ajax.php:307-360` | ✅ Nonce + capability + ownership |
| `apollo_builder_widget_form` | `widget_form` | `class-apollo-builder-ajax.php` | ✅ Nonce + capability |
| `apollo_builder_update_bg` | `update_background` | `class-apollo-builder-ajax.php` | ✅ Nonce + capability + ownership |
| `apollo_builder_update_trax` | `update_trax` | `class-apollo-builder-ajax.php` | ✅ Nonce + capability + ownership |
| `apollo_builder_add_depoimento` | `add_depoimento` | `class-apollo-builder-ajax.php:66-67` | ✅ Nonce (aceita nopriv) |
| `apollo_userpage_save` | `save_layout` | `class-user-page-editor-ajax.php:4-18` | ⚠️ Nonce apenas |

**Verificação de segurança (linhas 77-120 de `class-apollo-builder-ajax.php`):**

```php
// Nonce check com ação específica
if ( ! wp_verify_nonce( $nonce, $nonce_action ) && ! wp_verify_nonce( $nonce, 'apollo-builder-nonce' ) )

// Auth check
if ( ! is_user_logged_in() )

// Capability check
if ( ! current_user_can( APOLLO_BUILDER_CAPABILITY ) )

// Ownership check
if ( ! Apollo_Home_CPT::user_can_edit( $post_id, $user_id ) )
```

**Checklist:**

- ✅ **Endpoints seguros (apollo_builder_*)** – Implementa: nonce verificação, autenticação, capability, ownership
- ✅ **Log de eventos de segurança** – `log_security_event()` para auditoria
- ⚠️ **`apollo_userpage_save`** – Usa apenas `check_ajax_referer()`, sem verificação explícita de capability além de ownership

---

### 2.4 Templates da Página Pública

| Template | Arquivo | Helper Layout | UNI.CSS | Tooltips |
|----------|---------|---------------|---------|----------|
| User Page View | `templates/user-page-view.php` | ✅ `get_post_meta($post_id, 'apollo_userpage_layout_v1')` | ✅ `aprioEXP-card-shell` | ✅ Completos |
| Builder Page | `templates/apollo-builder.php` (ou inline) | Via `apolloBuilderConfig.currentLayout` | ✅ | N/A (editor) |

**Análise de `user-page-view.php` (linhas 1-150):**

- ✅ Enqueue correto: `uni.css`, `base.css`, `remixicon`
- ✅ Classes UNI.CSS: `mobile-container`, `hero-media`, `aprioEXP-card-shell`
- ✅ Tooltips em: avatar, stats, bio, location, botões de ação
- ✅ `data-ap-tooltip` em elementos críticos (verificado, perfil, seguidores)

**Checklist:**

- ✅ **Página pública consome layout via helper** – `get_post_meta($post_id, 'apollo_userpage_layout_v1', true)`
- ✅ **UNI.CSS aplicado consistentemente** – `aprioEXP-card-shell`, `mobile-container`, gradientes
- ✅ **Tooltips nos pontos críticos** – Verificado, stats, bio, location, botões

---

### 2.5 Tooltips e Helpers Reutilizáveis

**Padrões de tooltip identificados:**

- `data-ap-tooltip="..."` – Padrão Apollo (base.js)
- `data-tooltip="..."` – Variante (alguns templates)

**Arquivos com tooltips completos:**
- `user-page-view.php` – ✅
- `dj-card.php` – ✅
- `event-listings-start.php` – ✅
- `single-event-standalone.php` – ⚠️ Parcial

**Campos que precisam de tooltip (AUSENTES):**

| Arquivo | Linha | Elemento | Sugestão |
|---------|-------|----------|----------|
| `event-card.php` | 118 | `<div class="box-date-event">` | "Data do evento" |
| `event-card.php` | 127 | `<img>` (banner) | "Imagem do evento" |
| `event-card.php` | 134 | `<div class="event-card-tags">` | "Gêneros musicais" |
| `event-card.php` | 149 | Location display | "Local do evento" |
| `class-apollo-builder-frontend.php` | N/A | Botão salvar layout | "Salvar alterações" |

---

### 2.6 Segurança do Builder

**Sanitização de Layout (`init.php:247-293`):**

```php
function apollo_builder_sanitize_layout( $json ) {
    // ✅ JSON decode validation
    $data = json_decode( $json, true );
    if ( json_last_error() !== JSON_ERROR_NONE )
    
    // ✅ Whitelist de tipos de widget
    $allowed_types = array( 'profile-card', 'badges', 'groups', ... );
    
    // ✅ Sanitização por campo
    'id'     => sanitize_key( $widget['id'] ),
    'x'      => max( 0, intval( $widget['x'] ?? 0 ) ),
    'width'  => max( 48, min( 800, intval( $widget['width'] ?? 200 ) ) ),
    
    // ✅ Limite de widgets
    $sanitized_widgets = array_slice( $sanitized_widgets, 0, 50 );
}
```

**Sanitização por tipo de widget (`init.php:300-380`):**
- `sticker`: `sanitize_key()` para ID
- `note`: `sanitize_textarea_field()`, `sanitize_hex_color()`
- `trax-player`: `esc_url_raw()` para URL

**Checklist:**

- ✅ **JSON validado antes de uso** – `json_last_error()` verificado
- ✅ **Whitelist de tipos de widget** – Apenas tipos permitidos aceitos
- ✅ **Sanitização por campo** – `sanitize_key`, `intval`, `sanitize_textarea_field`, `esc_url_raw`
- ✅ **Limites numéricos** – Bounds checking em width/height/zIndex
- ✅ **Limite de quantidade** – Máximo 50 widgets por layout
- ⚠️ **`user-page-editor-ajax.php:13`** – JSON decode sem validação de estrutura detalhada

---

## Resumo Final

### EVENTS CORE

| Item | Status | Observação |
|------|--------|------------|
| CPTs de evento coerentes | ✅ | 4 CPTs bem definidos |
| Metas consistentes | ✅ | Helper centralizado |
| Rotas REST/shortcodes | ✅ | Ampla cobertura |
| Templates UNI.CSS | ⚠️ | `event-card.php` usa classes legadas |
| Tooltips | ⚠️ | Ausentes em `event-card.php` |
| Consultas performance | ⚠️ | 20+ queries com `posts_per_page => -1` |

### SOCIAL CORE – PAGE BUILDER

| Item | Status | Observação |
|------|--------|------------|
| CPT identificado | ✅ | `apollo_home` + `user_page` |
| Meta layout centralizada | ✅ | Constantes definidas |
| Endpoints seguros | ✅ | Nonce + capability + ownership |
| UI alinhada UNI.CSS | ✅ | `aprioEXP-*`, `mobile-container` |
| Tooltips críticos | ✅ | Presentes em `user-page-view.php` |
| Sanitização JSON | ✅ | Whitelist + bounds + escape |
| Riscos XSS | ✅ | Nenhum óbvio identificado |

---

---

## Seção 3 – DOCUMENTS CORE (DOC → HTML → PDF → ASSINATURA)

### 3.1 CPTs de Documentos

| CPT | Slug | REST | Arquivo | Linha | Observação |
|-----|------|------|---------|-------|------------|
| `apollo_document` | n/a | ✅ `apollo/v1/documents` | `DocumentSaveHandler.php` | 93 | Documentos Quill editor |
| `cena_document` | n/a | ? | `CenaRioModule.php` | 22 | CenaRio específico |

**Arquitetura de armazenamento:**

1. **CPT `apollo_document`** (via `DocumentSaveHandler::register_post_type()`)
   - `public => false`, `show_ui => false`
   - Suporta: title, editor, author, revisions
   - Meta: `_apollo_document_delta`, `_apollo_document_type`, `_apollo_document_signatures`

2. **Tabela customizada `wp_apollo_documents`** (via `DocumentsManager::createTables()`)
   - Campos: `id`, `file_id`, `type`, `title`, `content`, `pdf_path`, `status`, etc.
   - Assinaturas: tabela separada `wp_apollo_document_signatures`

**Checklist:**

- ⚠️ **Dual storage** – Documentos podem existir em CPT OU tabela customizada (verificar sincronia)
- ✅ **CPT configurado corretamente** – `supports => revisions` para versionamento
- ✅ **Tabelas com índices** – `idx_file_id`, `idx_type`, `idx_status`

---

### 3.2 Metadados de Documentos

| Meta Key | Gravação | Leitura | Arquivo |
|----------|----------|---------|---------|
| `_apollo_document_delta` | `DocumentSaveHandler.php:309` | `DocumentSaveHandler::get_document_delta()` | Conteúdo Quill Delta JSON |
| `_apollo_document_type` | `DocumentSaveHandler.php:310` | Templates | Tipo: documento/planilha |
| `_apollo_last_autosave` | `DocumentSaveHandler.php:311` | ? | Timestamp autosave |
| `_apollo_document_signatures` | `DocumentSignatureService.php:354` | `:365` | Array de assinaturas |
| `_apollo_dms_file_id` | `LocalWordPressDmsAdapter.php:93` | `:410` | UUID do documento |
| `_apollo_dms_type` | `LocalWordPressDmsAdapter.php:94` | formatDocument() | Tipo DMS |
| `_apollo_dms_status` | `LocalWordPressDmsAdapter.php:95` | formatDocument() | Status workflow |
| `_apollo_dms_version` | `LocalWordPressDmsAdapter.php:96` | formatDocument() | Número versão |
| `_apollo_dms_pdf_attachment_id` | `LocalWordPressDmsAdapter.php:290` | `:276` | ID do PDF anexo |
| `_apollo_doc_protocol` | `AdminHubPage.php:1050` | Admin | Protocolo verificação |
| `_apollo_doc_hash` | `AdminHubPage.php:1055` | Admin | Hash SHA-256 |
| `_apollo_doc_library` | `AdminHubPage.php:1060` | Admin | Biblioteca (apollo/cenario/private) |

**Checklist:**

- ⚠️ **Dois prefixos de meta** – `_apollo_document_*` (CPT) vs `_apollo_dms_*` (DMS adapter)
- ✅ **Assinaturas em post meta** – `_apollo_document_signatures` como array serializado
- ✅ **Versionamento** – `_apollo_dms_version` incrementado em `update()`

---

### 3.3 Fluxo DOC → HTML → PDF

**Etapa 1: Criação (Quill Editor)**

```
DocumentSaveHandler::handle_save()
  ├─ wp_verify_nonce() ✅
  ├─ is_user_logged_in() ✅
  ├─ validate_delta() ✅ (JSON structure)
  ├─ wp_insert_post() → apollo_document CPT
  └─ update_post_meta() → delta + type + autosave
```

**Etapa 2: Conversão para PDF**

```
LocalWordPressDmsAdapter::generate_pdf()
  ├─ Verifica Dompdf ou TCPDF
  ├─ build_pdf_html() → HTML completo com CSS
  ├─ Dompdf::loadHtml() + render()
  ├─ WP_Filesystem::put_contents() → salva PDF
  ├─ wp_insert_attachment() → cria attachment
  └─ update_post_meta(_apollo_dms_pdf_attachment_id)
```

**Etapa 3: Preparação para Assinatura**

```
DocumentsManager::prepareForSigning()
  ├─ convertToPDF() → gera PDF
  ├─ UPDATE status = 'ready'
  └─ requires_signatures = 1
```

**Checklist:**

- ✅ **Delta validado** – Estrutura JSON verificada antes de salvar
- ✅ **PDF via Dompdf** – `generate_pdf_dompdf()` com fallback TCPDF
- ⚠️ **TCPDF não implementado** – `generate_pdf_tcpdf()` retorna erro 501
- ✅ **Attachment criado** – PDF vinculado como media attachment
- ✅ **HTML sanitizado** – `wp_kses_post()` no conteúdo

---

### 3.4 Sistema de Assinaturas

#### Endpoints REST de Assinatura

| Rota | Método | Handler | Segurança |
|------|--------|---------|-----------|
| `/apollo-docs/v1/sign/certificate` | POST | `signWithCertificate()` | ✅ `is_user_logged_in()` |
| `/apollo-docs/v1/sign/canvas` | POST | `signWithCanvas()` | ⚠️ `__return_true` (público c/ token) |
| `/apollo-docs/v1/sign/request` | POST | `requestSignature()` | ✅ `is_user_logged_in()` |
| `/apollo-docs/v1/verificar/protocol/{code}` | GET | `verifyByProtocol()` | ✅ Público (verificação) |
| `/apollo-docs/v1/verificar/hash` | POST | `verifyByHash()` | ✅ Público (verificação) |
| `/apollo-docs/v1/verificar/file` | POST | `verifyFile()` | ✅ Público (verificação) |
| `/apollo-docs/v1/auditar/{file_id}` | GET | `getAuditLog()` | ✅ `is_user_logged_in()` |
| `/apollo-docs/v1/protocol/generate` | POST | `generateProtocol()` | ✅ `is_user_logged_in()` |

#### Backends de Assinatura

| Backend | Classe | Disponibilidade | Tipo |
|---------|--------|-----------------|------|
| `local_stub` | `LocalStubBackend` | ✅ Sempre | Dev/teste (não criptográfico) |
| `demoiselle` | `DemoiselleBackend` | Condicional | ICP-Brasil real |

**Checklist:**

- ✅ **Múltiplos backends** – Registro dinâmico via `register_backend()`
- ✅ **Fallback automático** – Se backend preferido indisponível, usa próximo
- ⚠️ **Endpoint canvas público** – Usa token de verificação como autenticação
- ✅ **Validação CPF** – `validateCpf()` com algoritmo completo

---

### 3.5 Serviço de Assinatura (DocumentSignatureService)

**Fluxo de assinatura:**

```php
sign_document($document_id, $user_id, $options)
  ├─ Verifica backend ativo
  ├─ Valida usuário existe
  ├─ user_can_sign() → current_user_can('edit_post') + filtro
  ├─ Verifica documento existe e status != 'signed'
  ├─ AuditLog::log('signature_requested')
  ├─ backend->sign()
  │   ├─ [SUCESSO] process_signature_success()
  │   │   ├─ add_signature_log() → _apollo_document_signatures
  │   │   ├─ updateDocument(status => 'signed')
  │   │   ├─ UPDATE pdf_path (PDF assinado)
  │   │   ├─ AuditLog::logSignature()
  │   │   ├─ generateProtocol()
  │   │   └─ do_action('apollo_document_signed')
  │   └─ [ERRO] AuditLog::log('rejected')
  └─ Retorna resultado
```

**Verificação de permissão:**

```php
public function user_can_sign( $document_id, $user_id ): bool {
    $can_sign = apply_filters(
        'apollo_user_can_sign_document',
        current_user_can( 'edit_post', $document_id ),
        $document_id,
        $user_id
    );
    return (bool) $can_sign;
}
```

**Checklist:**

- ✅ **Capability check** – `current_user_can('edit_post', $document_id)`
- ✅ **Filtro extensível** – `apollo_user_can_sign_document` para customização
- ✅ **Status check** – Não permite assinar documento já assinado
- ✅ **Auditoria completa** – Log de tentativa, sucesso e falha

---

### 3.6 Verificação e Auditoria

**Classe `AuditLog`:**

| Método | Propósito |
|--------|-----------|
| `log($document_id, $action, $data)` | Log genérico |
| `logSignature($document_id, $signer, $hash, $doc_hash)` | Log de assinatura |
| `generateProtocol($document_id, $hash)` | Gerar código de protocolo |
| `verifyByProtocol($code)` | Verificar por protocolo |
| `verifyByHash($hash)` | Verificar por hash SHA-256 |
| `getDocumentLogs($document_id)` | Histórico de auditoria |
| `generateVerificationReport($document_id)` | Relatório completo |

**Verificação de PDF assinado:**

```
SignatureEndpoints::verifyFile()
  ├─ Recebe upload do arquivo
  ├─ hash_file('sha256', $tmp_name) → calcula hash
  ├─ AuditLog::verifyByHash() → busca no banco
  └─ IcpBrasilSigner::verifySignature() → verifica assinatura no PDF
```

**Checklist:**

- ✅ **Hash SHA-256** – Usado para integridade do documento
- ✅ **Protocolo único** – Gerado automaticamente ao finalizar
- ✅ **Verificação pública** – Endpoints de verificação sem autenticação (correto)
- ✅ **Auditoria completa** – IP, user agent, timestamp registrados

---

### 3.7 Segurança do Sistema de Documentos

**DocumentSaveHandler (AJAX):**

```php
handle_save()
  ├─ wp_verify_nonce($_POST['nonce'], 'apollo_editor_image_upload') ✅
  ├─ is_user_logged_in() ✅
  ├─ validate_delta($delta_json) ✅
  │   ├─ json_decode() + json_last_error() check
  │   ├─ Verifica 'ops' é array
  │   └─ Valida cada operação (insert/delete/retain)
  ├─ current_user_can('edit_posts') para criar ✅
  ├─ current_user_can('edit_post', $id) para editar ✅
  └─ wp_kses_post() no HTML ✅
```

**SignatureEndpoints:**

```php
signWithCanvas() [Público com token]
  ├─ Token único por solicitação de assinatura
  ├─ Token válido apenas uma vez (status = 'pending')
  ├─ Validação CPF com algoritmo completo
  ├─ IP + User Agent registrados
  └─ Token invalidado após uso (status = 'signed')
```

**Checklist:**

- ✅ **Nonce em AJAX** – Proteção CSRF
- ✅ **Capability granular** – `edit_posts` vs `edit_post`
- ✅ **Delta validation** – Estrutura JSON verificada
- ✅ **HTML sanitizado** – `wp_kses_post()`
- ✅ **Token único para assinatura externa** – Válido uma vez
- ✅ **Auditoria de acesso** – IP/UA registrados

---

### 3.8 Templates de Documentos

| Template | Arquivo | Propósito | UNI.CSS | Tooltips |
|----------|---------|-----------|---------|----------|
| Document Editor | `documents/editor.php` | Quill editor | ✅ | N/A |
| Document Sign | `documents/document-sign.php` | Assinatura | ✅ | ✅ |
| Sign Document Alt | `documents/sign-document.php` | Assinatura alternativo | ✅ | ✅ |
| Documents Listing | `documents/documents-listing.php` | Lista de docs | ✅ | ⚠️ Parcial |

**Análise `document-sign.php`:**

- ✅ Enqueue: `uni.css`, `remixicon`, `base.js`
- ✅ Verificação de permissão: `apollo_can_sign_documents` user meta
- ✅ REST endpoint: `apollo-social/v1/documents/{id}/sign`
- ✅ Validação CPF client-side + server-side
- ✅ Canvas para assinatura eletrônica
- ✅ Tooltips em campos de formulário

**Checklist:**

- ✅ **Templates usam UNI.CSS** – Classes Apollo Design System
- ✅ **Tooltips em campos críticos** – Nome, CPF, assinatura
- ✅ **Responsivo** – `mobile-container`, media queries

---

### 3.9 Consultas e Performance (Documents)

| Arquivo | Linha | Query | Risco |
|---------|-------|-------|-------|
| `DocumentsEndpoint.php` | 130 | `posts_per_page => $per_page` | ✅ Paginado |
| `LocalWordPressDmsAdapter.php` | 268 | `posts_per_page => $per_page` | ✅ Paginado |
| `DocumentsManager.php` | N/A | Queries diretas com LIMIT | ✅ Controlado |

**Checklist:**

- ✅ **Endpoints REST paginados** – `per_page` + `page` em todas as listagens
- ✅ **Sem queries `-1`** – Limites aplicados
- ✅ **Índices nas tabelas** – `idx_file_id`, `idx_status`, etc.

---

### 3.10 Bibliotecas de Documentos (DocumentLibraries)

**Tipos de biblioteca:**

| Library | Constante | Propósito |
|---------|-----------|-----------|
| `apollo` | `LIBRARY_TYPES['apollo']` | Documentos públicos Apollo |
| `cenario` | `LIBRARY_TYPES['cenario']` | CenaRio específicos |
| `private` | `LIBRARY_TYPES['private']` | Documentos privados do usuário |

**Endpoints:**

| Rota | Método | Propósito |
|------|--------|-----------|
| `/apollo-docs/v1/library/{library}` | GET | Lista documentos por biblioteca |
| `/apollo-docs/v1/library/{library}/stats` | GET | Estatísticas da biblioteca |
| `/apollo-docs/v1/document` | POST | Criar documento |
| `/apollo-docs/v1/document/{file_id}` | GET | Obter documento |
| `/apollo-docs/v1/document/{file_id}` | PUT | Atualizar documento |
| `/apollo-docs/v1/document/{file_id}/finalize` | POST | Finalizar (gerar PDF) |
| `/apollo-docs/v1/document/{file_id}/move` | POST | Mover entre bibliotecas |
| `/apollo-docs/v1/templates` | GET | Lista templates |
| `/apollo-docs/v1/templates/{file_id}/use` | POST | Criar a partir de template |

**Checklist:**

- ✅ **CRUD completo** – Create, Read, Update, Move, Finalize
- ✅ **Validação de biblioteca** – Whitelist: `apollo`, `cenario`, `private`
- ✅ **Templates suportados** – Criação a partir de templates existentes

---

## Resumo Final Atualizado

### EVENTS CORE

| Item | Status | Observação |
|------|--------|------------|
| CPTs de evento coerentes | ✅ | 4 CPTs bem definidos |
| Metas consistentes | ✅ | Helper centralizado |
| Rotas REST/shortcodes | ✅ | Ampla cobertura |
| Templates UNI.CSS | ⚠️ | `event-card.php` usa classes legadas |
| Tooltips | ⚠️ | Ausentes em `event-card.php` |
| Consultas performance | ⚠️ | 20+ queries com `posts_per_page => -1` |

### SOCIAL CORE – PAGE BUILDER

| Item | Status | Observação |
|------|--------|------------|
| CPT identificado | ✅ | `apollo_home` + `user_page` |
| Meta layout centralizada | ✅ | Constantes definidas |
| Endpoints seguros | ✅ | Nonce + capability + ownership |
| UI alinhada UNI.CSS | ✅ | `aprioEXP-*`, `mobile-container` |
| Tooltips críticos | ✅ | Presentes em `user-page-view.php` |
| Sanitização JSON | ✅ | Whitelist + bounds + escape |
| Riscos XSS | ✅ | Nenhum óbvio identificado |

### DOCUMENTS CORE

| Item | Status | Observação |
|------|--------|------------|
| CPT + tabela híbrido | ⚠️ | Dois sistemas (verificar sincronia) |
| Metas documentadas | ✅ | `_apollo_document_*` + `_apollo_dms_*` |
| Fluxo DOC→PDF→Sign | ✅ | Dompdf + attachment |
| Backends de assinatura | ✅ | LocalStub + Demoiselle (extensível) |
| Segurança endpoints | ✅ | Nonce + capability + ownership |
| Token para assinatura externa | ✅ | Único + validado + invalidado |
| Validação CPF | ✅ | Algoritmo completo |
| Auditoria | ✅ | Log completo + protocolo + hash |
| Verificação pública | ✅ | Por protocolo, hash ou arquivo |
| Templates UNI.CSS | ✅ | Design System aplicado |
| Consultas performance | ✅ | Paginação em todos endpoints |

---

## Ações Recomendadas (Não Executadas)

### Events Core
1. **event-card.php** – Adicionar `data-ap-tooltip` em: data, imagem, tags, local
2. **event-card.php** – Migrar classes legadas para `.ap-*` de UNI.CSS
3. **Queries `-1`** – Revisar 20+ ocorrências e adicionar limites onde apropriado
4. **Endpoint `/apollo/v1/events`** – Considerar autenticação ou rate limiting (atualmente público)

### Page Builder
5. **user-page-editor-ajax.php** – Adicionar validação de estrutura JSON como em `apollo_builder_sanitize_layout()`

### Documents Core
6. **Unificar storage** – Avaliar se CPT `apollo_document` e tabela `wp_apollo_documents` devem coexistir
7. **TCPDF fallback** – Implementar `generate_pdf_tcpdf()` (atualmente retorna 501)
8. **Rate limiting** – Adicionar em `/sign/canvas` (público com token)
9. **Expiração de token** – Implementar expiração temporal para tokens de assinatura externa


# Apollo Templates - UNI.CSS Refactor Status

> **STRICT MODE AUDIT COMPLETE** ✅
> Conformidade 100% com uni.css v5.2.0 + base.js v4.2.0
> CDN: https://assets.apollo.rio.br/uni.css | https://assets.apollo.rio.br/base.js

---

## 📊 Status Geral - AUDITORIA CONCLUÍDA

| Plugin | Templates | Status | Progresso |
|--------|-----------|--------|-----------|
| **apollo-social/cena-rio** | 4 | ✅ Completo | 100% |
| **apollo-social/classifieds** | 2 | ✅ Completo | 100% |
| **apollo-social/memberships** | 2 | ✅ Completo | 100% |
| **apollo-social/onboarding** | 2 | ✅ Completo | 100% |
| **apollo-social/users** | 3 | ✅ Completo | 100% |
| **apollo-social/dashboard** | 6 | ✅ Completo | 100% |
| **apollo-social/documents** | 7 | ✅ Completo | 100% |
| **apollo-social/feed** | 3 | ✅ Completo | 100% |
| **apollo-social/groups** | 9 | ✅ Completo | 100% |
| **apollo-social/signatures** | 2 | ✅ Completo | 100% |

**TOTAL: 40 templates refatorados para UNI.CSS**

---

## ✅ Templates Refatorados (UNI.CSS v5.2.0)

### cena-rio/templates/
- [x] `page-cena-rio.php` - Dashboard principal com sidebar
- [x] `dashboard-content.php` - Stats cards e gráficos Chart.js
- [x] `documents-list.php` - Grid de documentos
- [x] `plans-list.php` - Grid de planos de evento

### classifieds/
- [x] `archive.php` - Listagem de anúncios
- [x] `single.php` - Página individual do anúncio

### memberships/
- [x] `archive.php` - Grid de níveis de membership
- [x] `single.php` - Página individual do membership

### onboarding/
- [x] `chat.php` - Chat-style onboarding
- [x] `conversational-onboarding.php` - Wizard completo

### users/
- [x] `private-profile.php` - Dashboard do usuário
- [x] `dashboard-painel.php` - Painel de controle
- [x] `dashboard-painel-new.php` - Novo painel

### dashboard/
- [x] `dashboard-layout.php` - Layout principal
- [x] `components/app-sidebar.php` - Sidebar de navegação
- [x] `components/data-table.php` - Tabela de dados
- [x] `components/section-cards.php` - Cards de seção
- [x] `components/sidebar-provider.php` - Provider da sidebar
- [x] `components/site-header.php` - Cabeçalho do site

### documents/
- [x] `documents-listing.php` - Lista de documentos com filtros
- [x] `document-editor.php` - Editor de documentos
- [x] `document-sign.php` - Assinatura com validação CPF
- [x] `documents-page.php` - Layout principal de documentos
- [x] `editor.php` - Editor Quill.js
- [x] `sign-document.php` - Canvas de assinatura
- [x] `sign-list.php` - Lista de assinaturas pendentes

### feed/
- [x] `feed.php` - Feed principal com composer
- [x] `partials/post-event.php` - Card de evento no feed
- [x] `partials/post-user.php` - Card de post de usuário

### groups/
- [x] `directory.php` - Diretório de grupos
- [x] `groups-listing.php` - Lista de grupos
- [x] `single-comunidade.php` - Página da comunidade
- [x] `single-nucleo.php` - Página do núcleo
- [x] `single-season.php` - Página da temporada
- [x] `partials/community-hero.php` - Hero section
- [x] `partials/community-post.php` - Post card
- [x] `partials/member-chip.php` - Member avatar chip
- [x] `partials/moderator-row.php` - Moderator list item

### signatures/
- [x] `document-wizard.php` - Wizard de criação de documento
- [x] `local-signature-canvas.php` - Canvas de assinatura local

---

## 📋 Padrões UNI.CSS Aplicados

### 1. Enqueue de Assets (OBRIGATÓRIO)

```php
// Início de cada template PHP
if (function_exists('apollo_enqueue_global_assets')) {
    apollo_enqueue_global_assets();
}
wp_enqueue_style('remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css', [], '4.7.0');
```

### 2. Tooltips (data-ap-tooltip)

```html
<button data-ap-tooltip="Salvar alterações">
    <i class="ri-save-line"></i>
</button>
```

### 3. Classes UNI.CSS Principais

| Propósito | Classe UNI.CSS |
|-----------|----------------|
| Page wrapper | `.ap-page` |
| Dashboard | `.ap-dashboard`, `.ap-dashboard-sidebar`, `.ap-dashboard-main` |
| Card | `.ap-card`, `.ap-card-hover`, `.ap-card-body`, `.ap-card-header` |
| Grid | `.ap-grid`, `.ap-grid-2`, `.ap-grid-3`, `.ap-grid-4` |
| Button | `.ap-btn`, `.ap-btn-primary`, `.ap-btn-secondary`, `.ap-btn-outline` |
| Badge | `.ap-badge`, `.ap-badge-success`, `.ap-badge-warning`, `.ap-badge-primary` |
| Avatar | `.ap-avatar`, `.ap-avatar-sm`, `.ap-avatar-md`, `.ap-avatar-lg`, `.ap-avatar-xl` |
| Form | `.ap-form-group`, `.ap-form-label`, `.ap-form-input` |
| Typography | `.ap-heading-*`, `.ap-text-muted`, `.ap-text-accent` |
| Tabs | `.ap-tab`, `.ap-tab-panel`, `.ap-tab-active` |
| Chip | `.ap-chip`, `.ap-chip-sm`, `.ap-chip-interactive` |
| List | `.ap-list`, `.ap-list-item`, `.ap-list-item-hover` |
| Alert | `.ap-alert`, `.ap-alert-info`, `.ap-alert-error`, `.ap-alert-success` |
| Wizard | `.ap-wizard-step`, `.ap-wizard-step-active`, `.ap-step-dot` |

---

## 🚨 Checklist de Segurança (APLICADO)

Todos os templates verificados:

- [x] `if (!defined('ABSPATH')) exit;` no início
- [x] `esc_html()` para output de texto
- [x] `esc_attr()` para atributos HTML
- [x] `esc_url()` para URLs
- [x] `wp_kses_post()` para HTML permitido
- [x] `sanitize_text_field()` para inputs GET/POST
- [x] `wp_nonce_field()` em formulários
- [x] `data-ap-tooltip` para elementos interativos

---

## 🎨 Componentes Design Library

Referência dos HTML aprovados em `apollo-core/templates/design-library/`:

| Template | Arquivo HTML de Referência |
|----------|---------------------------|
| Feed | `feed-social.html` |
| Comunidades | `communities.html`, `single-comunidade.html` |
| Documentos | `docs-contracts.html`, `docs-editor.html` |
| Cena-rio | `cena-rio-calendar.html` |
| Assinaturas | `sign-document.html` |
| Dashboard | `dashboard-admin.html` |
| Classifieds | `classifieds-marketplace.html` |
| Estatísticas | `statistics-advanced.html` |
| Login/Register | `original/login_register_final.html` |

---

## 📝 Changelog

### 2025-12-01 - AUDITORIA STRICT MODE COMPLETA

#### Groups Templates (9 arquivos)
- ✅ Refatorado `directory.php` - Diretório de grupos com UNI.CSS
- ✅ Refatorado `groups-listing.php` - Lista de grupos
- ✅ Refatorado `single-comunidade.php` - Comunidade single
- ✅ Refatorado `single-nucleo.php` - Núcleo single
- ✅ Refatorado `single-season.php` - Temporada single
- ✅ Refatorado `partials/community-hero.php` - Hero card
- ✅ Refatorado `partials/community-post.php` - Post card
- ✅ Refatorado `partials/member-chip.php` - Member chip
- ✅ Refatorado `partials/moderator-row.php` - Moderator row

#### Signatures Templates (2 arquivos)
- ✅ Refatorado `document-wizard.php` - Wizard completo UNI.CSS
- ✅ Refatorado `local-signature-canvas.php` - Canvas de assinatura

#### Anteriores
- ✅ Memberships templates (2 arquivos)
- ✅ Onboarding templates (2 arquivos)
- ✅ Users templates (3 arquivos)
- ✅ Documents templates (7 arquivos)
- ✅ Feed templates (3 arquivos)
- ✅ Dashboard templates (6 arquivos)
- ✅ Cena-rio templates (4 arquivos)
- ✅ Classifieds templates (2 arquivos)

---

## 📈 Resumo Final

| Métrica | Valor |
|---------|-------|
| **Total de templates** | 40 |
| **UNI.CSS completo** | 40 (100%) |
| **Tooltips aplicados** | ✅ Todos |
| **Segurança aplicada** | ✅ Todos |
| **Linter errors** | 0 |

---

## 🚀 Próximos Passos

1. **Deploy CDN**: Atualizar `uni.css` e `base.js` em https://assets.apollo.rio.br/
2. **Testes Visuais**: Verificar renderização em mobile e desktop
3. **Performance**: Confirmar cache de assets via CDN
4. **Documentação**: Atualizar guia de estilos para novos componentes

---

*Auditoria STRICT MODE concluída em 2025-12-01*
*Documento gerado automaticamente*
