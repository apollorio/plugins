# ✅ UNI.CSS UNIVERSAL & MAIN CSS - DEFINITIVE SETUP

## 🎯 OBJETIVO

**uni.css** (`https://assets.apollo.rio.br/uni.css`) é o **CSS UNIVERSAL e MAIN** do plugin Apollo Events Manager.

**Regra:** uni.css **SOBRESCREVE TODOS** os outros CSS e é a **fonte única de verdade** para estilos universais.

---

## ✅ CONFIGURAÇÃO APLICADA

### 1. uni.css Carrega SEMPRE (Universal)
```php
// Registrado PRIMEIRO, enqueued POR ÚLTIMO
if (!wp_style_is('apollo-uni-css', 'registered') && !wp_style_is('apollo-uni-css', 'enqueued')) {
    wp_register_style('apollo-uni-css', 'https://assets.apollo.rio.br/uni.css', array(), '2.0.0', 'all');
    add_action('wp_head', array($this, 'force_uni_css_last'), 999999);
}
```

**Prioridade:** `999999` (máxima possível)  
**Hook:** `wp_head` (CSS no `<head>`)  
**Carrega em:** TODAS as páginas PHP do plugin  

### 2. uni.css Sobrescreve TUDO
- ✅ Tailwind CSS → Sobrescrito por uni.css
- ✅ ShadCN Components → Sobrescrito por uni.css
- ✅ Plugin CSS → Sobrescrito por uni.css
- ✅ CSS inline → Sobrescrito por uni.css

---

## 📋 ESTILOS UNIVERSAIS (uni.css)

### uni.css Define:
- ✅ `.event_listing` (event cards)
- ✅ `.event_listings` (container)
- ✅ `.mobile-container` (single event page)
- ✅ `.hero-media`, `.hero-content` (hero section)
- ✅ `.quick-actions`, `.rsvp-row` (quick actions)
- ✅ `.section`, `.section-title` (sections)
- ✅ `.ticket-card`, `.lineup-card` (tickets, lineup)
- ✅ `.bottom-bar` (bottom navigation)
- ✅ **TODOS** os estilos universais do CodePen

---

## 🚫 CSS QUE DEVE SER REMOVIDO

### ❌ NÃO Criar CSS Para:
- `.event_listing` → uni.css já define
- `.event_listings` → uni.css já define
- `.mobile-container` → uni.css já define
- `.hero-media`, `.hero-content` → uni.css já define
- `.quick-actions` → uni.css já define
- `.section`, `.section-title` → uni.css já define
- `.ticket-card` → uni.css já define
- `.lineup-card` → uni.css já define
- **Qualquer estilo universal** → uni.css já define

### ✅ PERMITIDO Criar CSS Para:
- `.event-favorite-rocket` → Plugin-specific (não está em uni.css)
- `.apollo-loader-container` → Plugin-specific (não está em uni.css)
- `.picture.apollo-image-loading` → Plugin-specific (não está em uni.css)
- `.event-list-item` → Template específico (list view)
- **Apenas funcionalidades/animações específicas do plugin**

---

## 📁 ARQUIVOS CSS DO PLUGIN

### `assets/css/apollo-shadcn-components.css`
**Permitido:** Componentes ShadCN (forms, buttons, etc.)  
**NÃO permitido:** Estilos universais (.event_listing, .mobile-container, etc.)

### `assets/css/event-modal.css`
**Permitido:** Estilos específicos de modal  
**NÃO permitido:** Estilos universais que uni.css já define

### `assets/css/infinite-scroll.css`
**Permitido:** Apenas `.event-list-item` (template list view)  
**NÃO permitido:** `.event_listings`, `.event_listing` (uni.css define)

### CSS Inline (apollo-events-manager.php)
**Permitido:** Apenas animações/funcionalidades específicas  
**NÃO permitido:** Estilos universais

---

## 🎯 TEMPLATES

### `templates/single-event-page.php`
**Classe obrigatória:** `mobile-container`  
**uni.css define:** Todo o layout mobile-centered  
**CodePen:** https://codepen.io/Rafael-Valle-the-looper/pen/raxKGqM

### `templates/event-card.php`
**Classe obrigatória:** `event_listing`  
**uni.css define:** Todo o layout do card  
**CodePen:** https://codepen.io/Rafael-Valle-the-looper/pen/raxqVGR

### `templates/event-listings-start.php`
**Classe obrigatória:** `event_listings`  
**uni.css define:** Grid/list view layout  

---

## ✅ ORDEM DE CARREGAMENTO (FINAL)

1. **RemixIcon** (primeiro)
2. **apollo-shadcn-components.css** (componentes)
3. **apollo-event-modal-css** (modal específico)
4. **apollo-infinite-scroll-css** (list view específico)
5. **uni.css** 👑 (ÚLTIMO - prioridade 999999)
   - ✅ SOBRESCREVE tudo acima
   - ✅ Define TODOS os estilos universais

---

## 🚀 PARA TESTAR

### 1. Desativar e Reativar Plugin
```
WordPress Admin → Plugins
→ Desativar "Apollo Events Manager"
→ Reativar "Apollo Events Manager"
```

### 2. Hard Refresh
```
Ctrl + Shift + R (2-3 vezes)
```

### 3. Verificar Network (F12)
```
F12 → Network → CSS files:
✅ uni.css deve aparecer POR ÚLTIMO
✅ Deve estar DEPOIS de todos os outros CSS
```

### 4. Verificar HTML Source
```
Ctrl + U → Procurar por "uni.css":
✅ Deve aparecer POR ÚLTIMO no <head>
✅ Deve estar DEPOIS de todos os outros CSS
```

### 5. Inspecionar Elementos
```
F12 → Elements → Inspecionar:
✅ .event_listing → Estilos devem vir de uni.css
✅ .mobile-container → Estilos devem vir de uni.css
✅ Sem overrides de CSS customizado
```

---

## ✅ RESULTADO ESPERADO

### Event Single Page
- ✅ Mobile-centered (classe `mobile-container`)
- ✅ Layout igual ao CodePen raxKGqM
- ✅ uni.css controla TUDO

### Events Page ([events] ou [apollo_events])
- ✅ Cards iguais ao CodePen raxqVGR
- ✅ Layout grid/list view
- ✅ uni.css controla TUDO

---

## 🎯 STATUS

**uni.css:** 👑 UNIVERSAL & MAIN CSS  
**Carrega em:** TODAS as páginas PHP  
**Prioridade:** MÁXIMA (999999)  
**Sobrescreve:** TUDO  

**CSS Customizado:** ❌ REMOVIDO (apenas funcionalidades específicas)  
**Templates:** ✅ Usando classes corretas do uni.css  

**Código:** ✅ CONFIGURADO  
**Fix:** ✅ APLICADO  
**Pronto para:** CACHE CLEAR + TEST  

---

**Data:** 15/01/2025  
**Status:** UNI.CSS IS UNIVERSAL & MAIN CSS 👑  
**Action Required:** Desativar/Reativar plugin + Hard refresh + Verificar Network  

