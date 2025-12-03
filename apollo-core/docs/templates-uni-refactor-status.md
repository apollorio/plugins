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
