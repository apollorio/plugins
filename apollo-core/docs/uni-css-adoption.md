# Apollo UNI.CSS Design System - Adoption Guide

## Overview

UNI.CSS is the unified design system for the Apollo ecosystem. It provides a consistent visual language across all Apollo plugins (Core, Social, Events, Rio) with:

- **Root CSS Variables** for colors, typography, spacing, and effects
- **Component Classes** for cards, buttons, badges, avatars, forms, etc.
- **Utility Classes** for spacing, colors, layout helpers
- **Dark Mode Support** built-in
- **Mobile-First Responsive Design**

## Asset Location

| Environment | URL |
|-------------|-----|
| **Production (CDN)** | `https://assets.apollo.rio.br/uni.css` |
| **Local Development** | `apollo-core/templates/design-library/global assets-apollo-rio-br/uni.css` |

## How to Use

### For PHP/WordPress

```php
// Recommended: Use the global function
apollo_enqueue_global_assets(); // Enqueues CSS + JS

// Or specific assets only
apollo_enqueue_global_assets('css'); // CSS only
apollo_enqueue_global_assets('js');  // JS only

// Alternative: Direct class usage
Apollo_Global_Assets::enqueue_all();
Apollo_Global_Assets::enqueue_css();
Apollo_Global_Assets::enqueue_js();

// For Canvas mode pages
apollo_enqueue_canvas_assets();
```

### For HTML Templates

```html
<!-- Required: RemixIcon (for icons) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css">

<!-- Main stylesheet -->
<link rel="stylesheet" href="https://assets.apollo.rio.br/uni.css">

<!-- Optional: Base JS for interactions -->
<script src="https://assets.apollo.rio.br/base.js" defer></script>
```

## CSS Variables (Root)

### Typography
```css
--font-primary: "Urbanist", system-ui, sans-serif;
--text-size: 14.75px;
--text-regular: 12.75px;
--text-small: 10.75px;
--text-smaller: 8.35px;
```

### Colors (Light Mode)
```css
--bg-main: #fff;
--bg-surface: #f5f5f5;
--text-main: rgba(19, 21, 23, .6);
--text-primary: rgba(19, 21, 23, .85);
--text-secondary: var(--text-main);
--border-color: #e0e2e4;
--accent-color: #FFA17F;
--vermelho: #fd5c02;
--laranja: #FFA17F;
```

### Orange Scale (Primary Brand Color)
```css
--orange-0: #FFF6F0;    /* lightest */
--orange-100: #FFE1CC;
--orange-200: #FFC299;
--orange-300: #FFA066;
--orange-400: #FF8640;
--orange-500: #FF6925;  /* base */
--orange-600: #E55A1E;
--orange-700: #C7491A;
--orange-800: #9E3713;
--orange-900: #70250D;
--orange-1000: #1A0C04; /* darkest */
```

### Spacing & Radius
```css
--radius-sec: 20px;
--radius-main: 12px;
--radius-card: 16px;
```

### Transitions
```css
--transition-main: all 0.75s cubic-bezier(0.25, 0.8, 0.25, 1);
--transition-two: all 1.75s cubic-bezier(0.25, 0.8, 0.25, 1);
```

## Component Classes

### Cards

| Class | Description |
|-------|-------------|
| `.aprioEXP-card-shell` | Standard Apollo card with subtle shadow |
| `.card-glass` | Glassmorphism card effect |
| `.community-card` | Community/group card with gradient blur |
| `.user-card` | User profile card |
| `.event-card` | Event listing card |
| `.ticket-card` | Ticket/CTA card |
| `.calendar-card` | Calendar widget card |

**Example:**
```html
<div class="aprioEXP-card-shell">
    <h3>Card Title</h3>
    <p>Card content...</p>
</div>
```

### Buttons

| Class | Description |
|-------|-------------|
| `.bottom-btn` | Standard action button |
| `.bottom-btn.primary` | Primary action button (filled) |
| `.btn-player-main` | Media player button |
| `.route-button` | Navigation/route button |
| `.menutag` | Filter/category tag button |

**Example:**
```html
<button class="bottom-btn">Secondary Action</button>
<button class="bottom-btn primary">Primary Action</button>
```

### Badges

| Class | Description |
|-------|-------------|
| `.apollo-badge` | Base badge class |
| `.apollo-badge-role-dj` | DJ role badge (blue) |
| `.apollo-badge-role-producer` | Producer role badge (blue) |
| `.apollo-badge-role-promoter` | Promoter role badge (blue) |
| `.apollo-badge-unverified` | Unverified user badge |
| `.apollo-badge-mod` | Moderator badge |
| `.apollo-badge-reported` | Reported content badge (red) |
| `.status-badge` | Status indicator badge |
| `.status-badge-expected` | Expected status (orange) |
| `.status-badge-confirmed` | Confirmed status (green) |
| `.status-badge-published` | Published status (blue) |
| `.status-badge-pending` | Pending status (yellow) |
| `.status-badge-rejected` | Rejected status (red) |

**Example:**
```html
<span class="apollo-badge apollo-badge-role-dj">
    <i class="ri-disc-line"></i> DJ
</span>

<span class="status-badge status-badge-confirmed">Confirmed</span>
```

### Avatars

| Class | Description |
|-------|-------------|
| `.avatar` | Base avatar class |
| `.avatar-small` | 24px avatar |
| `.avatar-medium` | 32px avatar |
| `.avatar-large` | 40px avatar |
| `.avatar-xlarge` | 56px avatar |
| `.avatar-with-status` | Avatar with online indicator |

**Example:**
```html
<div class="avatar avatar-large">
    <img src="user.jpg" alt="User">
</div>

<div class="avatar avatar-medium avatar-with-status">
    <img src="user.jpg" alt="User">
    <span class="avatar-status-dot"></span>
</div>
```

### Forms

| Class | Description |
|-------|-------------|
| `.box-search` | Search input container |
| `.route-input` | Text input with icon |
| `.menutag` | Filter/category tag (clickable) |
| `.menutag[data-active="true"]` | Active state |

**Example:**
```html
<div class="box-search">
    <i class="ri-search-line"></i>
    <input type="text" placeholder="Search...">
</div>

<div class="menutags">
    <button class="menutag" data-active="true">All</button>
    <button class="menutag">Music</button>
    <button class="menutag">Art</button>
</div>
```

### Layout

| Class | Description |
|-------|-------------|
| `.main-container` | Main page container |
| `.hero-section` | Hero/header section |
| `.event_listings` | Event grid container |
| `.event_listing` | Single event card in grid |
| `.card-grid` | Bento/masonry grid |

## Utility Classes

### Glassmorphism
```html
<div class="glass">Glassmorphism effect</div>
```

### Scrollbar
```html
<div class="no-scrollbar">Hidden scrollbar</div>
```

### Safe Area (iOS)
```html
<div class="pb-safe">Safe area padding</div>
```

### Accessibility
```html
<span class="visually-hidden">Screen reader only text</span>
```

### State
```html
<div class="disabled">Disabled element</div>
```

### Color Utilities

**Backgrounds:**
```html
<div class="bg-orange-500">Orange background</div>
<div class="bg-grama">Green gradient</div>
```

**Text:**
```html
<span class="text-orange-500">Orange text</span>
```

**Borders:**
```html
<div class="border-orange-500">Orange border</div>
```

## Dark Mode

Dark mode is activated by adding `.dark-mode` class to `<body>`:

```html
<body class="dark-mode">
    <!-- All UNI.CSS components automatically adapt -->
</body>
```

Toggle with JavaScript:
```javascript
document.body.classList.toggle('dark-mode');
localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
```

## Migration Status

### Fully Migrated to UNI.CSS ✅

| Plugin | Component | Status |
|--------|-----------|--------|
| apollo-core | Admin Hub | ✅ Uses UNI variables |
| apollo-events | Event Cards | ✅ `.event_listing` |
| apollo-events | Event Modal | ✅ `.apollo-event-popup` |
| apollo-social | Feed Cards | ✅ `.aprioEXP-card-shell` |
| apollo-social | Community Cards | ✅ `.community-card` |
| apollo-social | Badges | ✅ `.apollo-badge-*` |
| apollo-social | Canvas Mode | ✅ Full UNI integration |

### Partial Migration 🔄

| Plugin | Component | Notes |
|--------|-----------|-------|
| apollo-events | Admin Metabox | Uses some shadcn-components.css |
| apollo-social | Dashboard | Uses dashboard.css overlay |

### Still Using Local CSS ⚠️

| Plugin | File | Reason |
|--------|------|--------|
| apollo-events | admin-dashboard.css | WP Admin specific styles |
| apollo-events | admin-metabox.css | WP Admin metabox overrides |
| apollo-social | registration.css | Complex multi-step form |

## Best Practices

### DO ✅

1. **Use CSS variables** instead of hardcoded colors:
   ```css
   /* Good */
   color: var(--text-primary);
   
   /* Bad */
   color: #131517;
   ```

2. **Use component classes** for standard UI elements:
   ```html
   <!-- Good -->
   <div class="aprioEXP-card-shell">...</div>
   
   <!-- Bad -->
   <div class="my-custom-card">...</div>
   ```

3. **Use the enqueue function** instead of direct wp_enqueue:
   ```php
   // Good
   apollo_enqueue_global_assets();
   
   // Avoid
   wp_enqueue_style('apollo-uni-css', '...');
   ```

4. **Test dark mode** for all new components

### DON'T ❌

1. **Don't duplicate existing classes** - check UNI.CSS first
2. **Don't use !important** unless absolutely necessary
3. **Don't create plugin-specific CSS** for standard components
4. **Don't hardcode CDN URLs** - use `apollo_get_asset_url()`

## Adding New Components

If you need a component that doesn't exist in UNI.CSS:

1. **Check if it's truly generic** - will other plugins use it?
2. **Follow naming conventions**: `.apollo-*` or `.ap-*` prefix
3. **Use CSS variables** for colors, spacing, and radius
4. **Include dark mode support**
5. **Submit PR to add to UNI.CSS**

## Support

For questions or issues with UNI.CSS:
- Check the Design Library: `apollo-core/templates/design-library/`
- Review component HTML examples: `apollo-core/templates/design-library/_components/`
- Contact the Apollo Team

---

*Last updated: November 2024*
*UNI.CSS Version: 2.0.0*

