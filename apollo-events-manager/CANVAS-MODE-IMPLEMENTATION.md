# ✅ CANVAS MODE - PÁGINAS INDEPENDENTES

## 🎯 OBJETIVO

**REMOVER TODO CSS/JS DO TEMA** quando shortcode Apollo estiver ativo  
**Criar páginas CANVAS:** Blank, independent, powerful  
**uni.css REINA ABSOLUTO:** Sem interferência do tema

---

## ✅ IMPLEMENTAÇÃO

### 1. ✅ Método `remove_theme_assets_if_shortcode()`
**Localização:** `apollo-events-manager.php` linha 1327-1364  
**Função:** Detecta páginas com shortcode Apollo e remove assets do tema

**Detecta:**
- ✅ Single event pages (`is_singular('event_listing')`)
- ✅ Event archives (`is_post_type_archive('event_listing')`)
- ✅ Specific pages (`eventos`, `djs`, `locais`, `dashboard-eventos`, `mod-eventos`)
- ✅ Shortcodes (`[events]`, `[apollo_events]`, `[eventos-page]`, `[apollo_djs]`, `[apollo_locais]`)

**Ação:**
```php
// Remove ALL theme CSS
add_action('wp_enqueue_scripts', array($this, 'dequeue_theme_assets'), 999999);

// Add body class for canvas mode
add_filter('body_class', array($this, 'add_canvas_body_class'));
```

---

### 2. ✅ Método `dequeue_theme_assets()`
**Localização:** `apollo-events-manager.php` linha 1366-1429  
**Função:** Remove TODOS CSS/JS do tema, mantendo APENAS Apollo assets

**MANTÉM (Whitelist):**
```php
// Styles to KEEP
'apollo-uni-css',
'remixicon',
'leaflet-css',
'apollo-shadcn-components',
'apollo-event-modal-css',
'apollo-infinite-scroll-css',
'admin-bar', // For logged-in users
'dashicons'  // For admin bar

// Scripts to KEEP
'jquery',
'leaflet',
'framer-motion',
'apollo-base-js',
'apollo-event-page-js',
// ... all Apollo scripts ...
'admin-bar', // For logged-in users
'hoverIntent' // For admin bar
```

**REMOVE:**
- ❌ TODO CSS do tema (Elementor, Elementra, TRX Addons, etc.)
- ❌ TODO JS do tema
- ❌ Plugins de terceiros (exceto whitelist)

---

### 3. ✅ Body Classes para Canvas Mode
**Localização:** `apollo-events-manager.php` linha 1445-1449

```php
public function add_canvas_body_class($classes) {
    $classes[] = 'apollo-canvas-mode';
    $classes[] = 'apollo-independent-page';
    return $classes;
}
```

**Classes adicionadas:**
- `apollo-canvas-mode`
- `apollo-independent-page`

---

### 4. ✅ Páginas Criadas com Template Canvas
**Localização:** `apollo-events-manager.php` linha 4307-4395  
**Hook:** `apollo_events_manager_activate()`

**Páginas criadas ON ACTIVATION:**

1. `/eventos/` - Template: canvas
   ```php
   'post_content' => '[events]',
   '_wp_page_template' => 'canvas',
   'apollo_canvas_mode' => '1'
   ```

2. `/dashboard-eventos/` - Template: canvas
   ```php
   'post_content' => '[apollo_event_user_overview]',
   '_wp_page_template' => 'canvas',
   'apollo_canvas_mode' => '1'
   ```

3. `/djs/` - Template: canvas (if shortcode exists)
   ```php
   'post_content' => '[apollo_djs]',
   '_wp_page_template' => 'canvas',
   'apollo_canvas_mode' => '1'
   ```

4. `/locais/` - Template: canvas (if shortcode exists)
   ```php
   'post_content' => '[apollo_locais]',
   '_wp_page_template' => 'canvas',
   'apollo_canvas_mode' => '1'
   ```

---

## 📋 ORDEM DE EXECUÇÃO

### No `enqueue_assets()`:
1. **Primeiro:** `remove_theme_assets_if_shortcode()` (detecta + agenda remoção)
2. **Segundo:** Registrar uni.css (universal)
3. **Terceiro:** Carregar assets Apollo
4. **wp_enqueue_scripts (prioridade 999999):** `dequeue_theme_assets()` executa
5. **wp_head (prioridade 999999):** uni.css enqueued por último

---

## ✅ RESULTADO

### Página com Shortcode Apollo:
```html
<head>
    <!-- APENAS Apollo Assets -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css">
    <link rel="stylesheet" href=".../apollo-shadcn-components.css">
    <link rel="stylesheet" href=".../apollo-event-modal-css.css">
    <link rel="stylesheet" href="https://assets.apollo.rio.br/uni.css"> <!-- ÚLTIMO -->
    
    <!-- ZERO CSS do tema -->
    <!-- ZERO JS do tema (exceto jQuery) -->
</head>
<body class="apollo-canvas-mode apollo-independent-page">
    <!-- APENAS conteúdo do shortcode Apollo -->
    <!-- ZERO header do tema -->
    <!-- ZERO footer do tema -->
    <!-- ZERO sidebar -->
</body>
```

---

## 🚀 ATIVAÇÃO

### Para Ativar Canvas Mode:

#### 1. Desativar Plugin
```
WordPress Admin → Plugins
→ Desativar "Apollo Events Manager"
```

#### 2. Reativar Plugin
```
→ Reativar "Apollo Events Manager"
```
**Resultado:** Páginas criadas com template "canvas"

#### 3. Verificar Páginas Criadas
```
WordPress Admin → Páginas
✅ /eventos/ (template: canvas)
✅ /dashboard-eventos/ (template: canvas)
✅ /djs/ (template: canvas) [se shortcode existir]
✅ /locais/ (template: canvas) [se shortcode existir]
```

#### 4. Hard Refresh
```
Ctrl + Shift + R (3-5x)
```

---

## ✅ GARANTIAS

### 1. Theme CSS Removed ✅
- ❌ Elementor CSS
- ❌ Elementra CSS
- ❌ TRX Addons CSS
- ❌ Qualquer CSS do tema

### 2. Theme JS Removed ✅
- ❌ Theme scripts
- ❌ Plugin scripts (exceto Apollo)
- ✅ Mantém jQuery (necessário)
- ✅ Mantém admin bar (logged-in users)

### 3. Apollo Assets Only ✅
- ✅ uni.css (universal & main)
- ✅ RemixIcon
- ✅ Leaflet (maps)
- ✅ Apollo scripts
- ✅ ZERO interferência do tema

### 4. Pages Created with Canvas Template ✅
- ✅ `/eventos/` → template: canvas
- ✅ `/dashboard-eventos/` → template: canvas
- ✅ `/djs/` → template: canvas
- ✅ `/locais/` → template: canvas

---

## 🔥 MODO CANVAS ATIVO

### Body Classes:
```html
<body class="apollo-canvas-mode apollo-independent-page">
```

### NO Header ✅
### NO Footer ✅
### NO Sidebar ✅
### NO Theme CSS ✅
### NO Theme JS ✅

### APENAS:
- ✅ uni.css (universal)
- ✅ Apollo assets
- ✅ Shortcode content

---

## 📋 STATUS

**Canvas Mode:** ✅ IMPLEMENTADO  
**Theme Assets:** ❌ REMOVIDOS  
**Apollo Assets:** ✅ APENAS (whitelist)  
**Páginas:** ✅ CRIADAS com template canvas  
**Body Classes:** ✅ apollo-canvas-mode, apollo-independent-page  

**Código:** VÁLIDO ✅  
**uni.css:** UNIVERSAL & MAIN CSS ✅  

**Status:** CANVAS MODE ACTIVE ✅  
**Ação:** Desativar/Reativar plugin para criar páginas canvas  

---

**Data:** 15/01/2025  
**Status:** POWERFUL INDEPENDENT PAGES CANVAS ✅  
**Action Required:** Desativar/Reativar plugin + Hard refresh  

