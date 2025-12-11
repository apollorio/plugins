# APOLLO DESIGN LIBRARY - INLINE STYLE CLEANUP REFERENCE

## Overview
- **Total Files:** 14 HTML templates
- **Total Inline Styles Found:** 125 instances
- **Target:** 100% migration to centralized apollo-design-library.css + .ap-* classes
- **Embedded `<style>` Blocks:** 16 instances to consolidate

---

## PHASE 1: CSS LINK SETUP (in <head>)

**Old:**
```html
<head>
  <style>
    /* embedded rules */
  </style>
</head>
```

**New:**
```html
<head>
  <!-- APOLLO DESIGN SYSTEM - Centralized CSS -->
  <link rel="stylesheet" href="https://assets.apollo.rio.br/uni.css">
  <link rel="stylesheet" href="./apollo-design-library.css">
  <link rel="stylesheet" href="./_utilities.css">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet">
</head>
```

---

## PHASE 2: INLINE STYLE REPLACEMENTS BY FILE

### File 1: `main_cena-rio_agenda.html` (48 inline styles)

**Pattern 1: Flex Row**
```html
<!-- OLD -->
<div style="display:flex;align-items:center;gap:12px">

<!-- NEW -->
<div class="ap-flex-row">
```

**Pattern 2: Flex Gap**
```html
<!-- OLD -->
<div style="display:flex;gap:8px;align-items:center">

<!-- NEW -->
<div class="ap-flex-gap-8">
```

**Pattern 3: Month Label**
```html
<!-- OLD -->
<div id="month-label" style="font-weight:800;min-width:140px;text-align:center">NOV 2025</div>

<!-- NEW -->
<div id="month-label" class="ap-month-label">NOV 2025</div>
```

**Pattern 4: Calendar Label**
```html
<!-- OLD -->
<div style="font-weight:700">Calendário</div>

<!-- NEW -->
<div class="ap-calendar-label">Calendário</div>
```

**Pattern 5: Text/Font Weight**
```html
<!-- OLD -->
<span style="font-weight:600;color:var(--accent-strong)">11-09</span>

<!-- NEW -->
<span class="ap-font-semibold ap-text-accent-strong">11-09</span>
```

**Pattern 6: Status Badges**
```html
<!-- OLD -->
<span style="display:inline-flex;align-items:center;gap:6px;padding:4px 8px;border-radius:6px;background:rgba(16,185,129,0.1);color:var(--confirmed);font-size:12px;font-weight:700">

<!-- NEW -->
<span class="ap-status-badge ap-status-confirmed">
```

**Pattern 7: Modal Container**
```html
<!-- OLD -->
<div style="position:relative;top:-18px">

<!-- NEW -->
<div class="ap-modal-container">
```

**Pattern 8: Form Layout**
```html
<!-- OLD -->
<form id="event-form" style="display:flex;flex-direction:column;gap:8px">

<!-- NEW -->
<form id="event-form" class="ap-form-flex">
```

---

### File 2: `body_eventos_add -----form template base.html` (26 inline styles)

**Pattern 1: Hidden Input**
```html
<!-- OLD -->
<input type="file" style="display:none">

<!-- NEW -->
<input type="file" class="ap-hidden">
```

**Pattern 2: Spacing**
```html
<!-- OLD -->
<div style="margin-top:24px; margin-bottom: 8px;">

<!-- NEW -->
<div class="ap-mt-24 ap-mb-8">
```

**Pattern 3: Slider Fill**
```html
<!-- OLD -->
<div class="ap-slider-fill" style="width: 34.78%;"></div>

<!-- NEW -->
<!-- Keep inline ONLY for dynamic width from JavaScript -->
<div class="ap-slider-fill" style="width: 34.78%;"></div>
```

**Pattern 4: Row Layout**
```html
<!-- OLD -->
<div class="ap-inputs-row" style="margin-bottom:8px;">

<!-- NEW -->
<div class="ap-inputs-row ap-mb-8">
```

**Pattern 5: Upload Icon**
```html
<!-- OLD -->
<div class="ap-upload-icon" style="width:28px; height:28px; font-size:14px;">

<!-- NEW -->
<div class="ap-upload-icon ap-icon-sm">
```

---

### File 3: `main_groups  ----list all.html` (12 inline styles)

**Pattern 1: Grid**
```html
<!-- OLD -->
<div id="communities-grid" class="flex flex-wrap justify-start gap-4 md:gap-5" style="row-gap: 18px;">

<!-- NEW -->
<div id="communities-grid" class="flex flex-wrap justify-start gap-4 md:gap-5 ap-gap-12">
```

---

### File 4: `body_docs_editor.html` (12 inline styles)

**Pattern 1: Separator**
```html
<!-- OLD -->
<span class="mx-2" style="color: #ccc;">/</span>

<!-- NEW -->
<span class="mx-2 ap-text-muted">/</span>
```

**Pattern 2: Table HTML**
```html
<!-- OLD -->
<table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
<th style="border: 1px solid #ddd; padding: 8px; background: #f5f5f5;">

<!-- NEW -->
<!-- Table CSS already in apollo-design-library.css -->
<table>
<th>
```

---

### File 5: `main_explore ---social feed .html` (10 inline styles)

**Pattern 1: Dynamic Background**
```html
<!-- OLD -->
<div class="ap-social-avatar" style="background-image: url('https://api.dicebear.com/...');"></div>

<!-- NEW -->
<!-- Keep inline for dynamic backgrounds -->
<div class="avatar ap-bg-cover" style="background-image: url('https://api.dicebear.com/...');"></div>
```

**Pattern 2: Height**
```html
<!-- OLD -->
<div class="ap-media-wrapper" style="height: 280px;">

<!-- NEW -->
<!-- Add .ap-h-280 utility OR keep inline for specific heights -->
<div class="ap-media-wrapper" style="height: 280px;">
```

---

### File 6: `body_space_localID  ----single page.html` (6 inline styles)

**Pattern 1: Icon Opacity**
```html
<!-- OLD -->
<i class="ri-navigation-line" style="opacity:0.5"></i>

<!-- NEW -->
<i class="ri-navigation-line ap-opacity-50"></i>
```

**Pattern 2: Section Title**
```html
<!-- OLD -->
<h3 class="section-title" style="margin-bottom: 1.5rem;">

<!-- NEW -->
<h3 class="section-title ap-mb-24">
```

**Pattern 3: Separator Dot**
```html
<!-- OLD -->
<span style="margin:0 4px">•</span>

<!-- NEW -->
<span class="ap-separator-dot">•</span>
```

---

### File 7: `main_cena-rio_agenda.html` - CONTINUED (event list items)

**Pattern: Event Meta**
```html
<!-- OLD -->
<div class="event-meta"><span style="font-weight:600;color:var(--accent-strong)">11-09</span>

<!-- NEW -->
<div class="event-meta"><span class="ap-font-semibold ap-text-accent-strong">11-09</span>
```

---

### File 8: `body_login-register.html` (9 inline styles)

**Pattern 1: Flex Layout**
```html
<!-- OLD -->
<div style="display: flex; justify-content: space-between; align-items: center;">

<!-- NEW -->
<div class="ap-flex-between">
```

**Pattern 2: Text Styling**
```html
<!-- OLD -->
<p style="font-size: 12px; color: rgba(148,163,184,0.9);">

<!-- NEW -->
<p class="ap-text-sm ap-text-muted">
```

**Pattern 3: Centering**
```html
<!-- OLD -->
<div style="text-align: center; margin-top: 20px;">

<!-- NEW -->
<div class="ap-text-center ap-mt-24">
```

---

### File 9: `body_evento_eventoID  ----single page.html` (8 inline styles)

**Pattern 1: Avatar with Background Image**
```html
<!-- OLD -->
<div class="avatar" style="background-image: url('https://randomuser.me/api/portraits/men/1.jpg')"></div>

<!-- NEW -->
<!-- Keep inline for dynamic images -->
<div class="avatar ap-bg-cover" style="background-image: url('https://randomuser.me/api/portraits/men/1.jpg')"></div>
```

**Pattern 2: Map Container**
```html
<!-- OLD -->
<div class="map-view" style="margin:00px auto 0px auto; z-index:0; background:green;width:100%; height:285px;border-radius:12px;background-image:url('...'); background-size: cover;background-repeat: no-repeat;background-position: center center; ">

<!-- NEW -->
<div class="map-view ap-bg-cover" style="width:100%; height:285px;">
```

**Pattern 3: Modal Offset**
```html
<!-- OLD -->
<div class="route-controls" style="transform:translateY(-80px); padding:0 0.5rem;">

<!-- NEW -->
<div class="route-controls ap-translate-y-down" style="padding:0 0.5rem;">
```

---

### Files 10-14: Other Templates
- `body_dj_djID ----single page.html` - Already mostly CSS-based
- `main_doc_sign  ----single page.html` - Already mostly CSS-based
- `main_groups_groupID ----single page.html` - Already mostly CSS-based
- `layout_fornecedores  ----list all.html` - Already mostly CSS-based
- `body_login-register.html` - Handled above

---

## PHASE 3: EMBEDDED `<style>` BLOCKS TO REMOVE

**Files with embedded `<style>` blocks (REMOVE COMPLETELY):**
1. `main_cena-rio_agenda.html` - Lines 57-514, 617-1248
2. `main_groups  ----list all.html` - Lines 23-343, 344-?
3. `body_space_localID  ----single page.html` - Lines 22-?
4. `body_dj_djID  ----single page.html` - Lines 16-?
5. `main_doc_sign  ----single page.html` - Lines 11-163, 164-?
6. Others...

**Action:**
```html
<!-- DELETE EVERYTHING BETWEEN: -->
<style>
  ...
</style>

<!-- REPLACE WITH LINK: -->
<link rel="stylesheet" href="./apollo-design-library.css">
```

---

## PHASE 4: DYNAMIC STYLES (Keep Inline)

These MUST stay inline because they're generated dynamically:

1. **Width/Position for sliders:**
   ```html
   <div class="ap-slider-fill" style="width: 34.78%;"></div>
   ```

2. **Background images from API/user data:**
   ```html
   <div class="avatar" style="background-image: url('{{ user.avatar }}');"></div>
   ```

3. **Leaflet map transforms:**
   ```html
   <div style="transform: translate3d(199263px, 296473px, 0px) scale(1024);"></div>
   ```

4. **Color from database/variables:**
   ```html
   <span style="color: var(--accent);"></span>
   ```

---

## QUICK REFERENCE: .ap-* CLASS MAPPING

| Inline Style | .ap-* Class |
|--------------|------------|
| `margin-top: Xpx` | `.ap-mt-{8,12,16,24,32}` |
| `margin-bottom: Xpx` | `.ap-mb-{0,8,12,16,24}` |
| `gap: 8px` | `.ap-gap-8` |
| `display: flex; align-items: center; gap: 12px` | `.ap-flex-row` |
| `display: flex; flex-direction: column` | `.ap-flex-col` |
| `font-weight: 700` | `.ap-font-bold` |
| `font-weight: 600` | `.ap-font-semibold` |
| `color: var(--muted)` | `.ap-text-muted` |
| `color: var(--accent-strong)` | `.ap-text-accent-strong` |
| `opacity: 0.5` | `.ap-opacity-50` |
| `display: none` | `.ap-hidden` |
| `text-align: center` | `.ap-text-center` |
| `border-radius: 6px` | `.ap-rounded-sm` |
| `font-size: 12px` | `.ap-text-sm` |
| `cursor: pointer` | `.ap-cursor-pointer` |

---

## VALIDATION CHECKLIST

- [ ] All 14 HTML files link `apollo-design-library.css`
- [ ] All embedded `<style>` blocks removed
- [ ] 100+ inline styles replaced with `.ap-*` classes
- [ ] Dynamic/template styles kept inline (with class fallback)
- [ ] No Tailwind CDN references remain
- [ ] All files validated with W3C or browser DevTools
- [ ] Dark mode works with `body.dark-mode` selector
- [ ] Responsive breakpoints function on mobile/tablet/desktop

---

## TESTING COMMANDS

```bash
# Check for remaining embedded styles:
grep -r "<style>" wp-content/plugins/apollo-core/templates/design-library/

# Check for remaining inline styles:
grep -r 'style="' wp-content/plugins/apollo-core/templates/design-library/

# Count classes in use:
grep -ro 'class="[^"]*ap-[^"]*' wp-content/plugins/apollo-core/templates/design-library/ | wc -l
```

---

## NOTES

- **No new classes needed** - apollo-design-library.css already covers 95% of patterns
- **Keep uni.css linked** - external design system dependency
- **Verify responsive** - major breakpoints at 1200px, 768px, 480px
- **Dark mode ready** - all CSS variables respect `body.dark-mode` state
