# Apollo CDN Integration — Template Optimization Summary

**Date:** 2026-01-17
**Status:** ✅ In Progress

## 📋 Template Analysis Results

### ✅ Already Using CDN Correctly

| Template                  | Plugin      | CDN Version | Status    |
| ------------------------- | ----------- | ----------- | --------- |
| `canvas.php`              | apollo-core | 4.3.0       | ✓ Correct |
| `page-home.php`           | apollo-core | CDN base    | ✓ Correct |
| `cena-rio-calendar.php`   | apollo-core | 4.3.0       | ✓ Correct |
| `cena-rio-moderation.php` | apollo-core | 4.3.0       | ✓ Correct |
| `pagx_app.php`            | apollo-rio  | CDN base    | ✓ Correct |
| `pagx_apolloapp.php`      | apollo-rio  | CDN base    | ✓ Correct |
| `pagx_appclean.php`       | apollo-rio  | CDN base    | ✓ Correct |

### ⚠️ Needs CDN Version Update

| Template              | Plugin        | Current | Should Be | Action         |
| --------------------- | ------------- | ------- | --------- | -------------- |
| `private-profile.php` | apollo-social | 3.1.0   | 4.3.0     | Update version |

### 🎨 Needs Design System Integration

| Template                     | Plugin        | Background    | Design Compliance        | Action                    |
| ---------------------------- | ------------- | ------------- | ------------------------ | ------------------------- |
| `layout-base.php`            | apollo-social | `#f5f5f5`     | ❌ Should use CDN tokens | Apply --bg CSS vars       |
| `canvas-layout.php`          | apollo-social | Mixed         | ⚠️ Partial               | Standardize to CDN tokens |
| `page-user-dashboard.php`    | apollo-core   | Via partials  | ⚠️ Unknown               | Verify CDN load           |
| `page-suppliers-catalog.php` | apollo-core   | Via partials  | ⚠️ Unknown               | Verify CDN load           |
| `page-sign-centered.php`     | apollo-core   | `bg-white/95` | ✓ Close to spec          | Minor adjust              |

### 🚫 Excluded (Per User Request)

- `event-card.php` (apollo-events-manager)
- `single-event_listing.php` (apollo-events-manager)
- `single-event_dj.php` (apollo-events-manager)
- `page-eventos.php` (apollo-events-manager)

## 🎯 Design System Target (Apollo Clean Modern Minimalist)

```css
/* Main theme background should be pure white */
:root {
  --bg: #fff; /* NOT #f5f5f5 or #fafafa */
}

/* Load from CDN tokens automatically */
@import url("https://cdn.apollo.rio.br/styles/00-tokens.css");
```

### Design Principles Applied

1. **Background:** Pure `#fff` (white) as main canvas
2. **Cards/Surfaces:** Subtle rgba-based grays from CDN tokens
3. **Typography:** System fonts via CDN base.css
4. **Spacing:** Consistent grid from CDN layout.css
5. **Animations:** MutationObserver-based from scroll.min.js

## 🔧 Optimization Actions

### Priority 1: Update CDN Version

```php
// private-profile.php line 30
wp_enqueue_script(
    'apollo-cdn-loader',
    'https://assets.apollo.rio.br/index.min.js',
    array(),
    '4.3.0', // Changed from 3.1.0
    true
);
```

### Priority 2: Remove Hardcoded Colors

**Before:**

```css
background: #f5f5f5;
color: #333;
```

**After:**

```css
background: var(--bg-main, #fff);
color: var(--txt-main);
```

### Priority 3: Ensure CDN Load in wp_head()

All templates should call `wp_head()` which automatically loads:

- `apollo-cdn-loader` (index.min.js v4.3.0)
- All design tokens inline
- Icon runtime, dark-mode, scroll animations

## 📦 Files to Update

1. ✅ `CDN-INTEGRATION-MAP.md` → Moved to `apollo-core/docs/`
2. 🔄 `private-profile.php` → Update CDN version 3.1.0 → 4.3.0
3. 🔄 `layout-base.php` → Replace hardcoded colors with CSS vars
4. 🔄 `canvas-layout.php` → Standardize to CDN tokens

## ✨ Expected Results

- **Consistency:** All templates use same design system from CDN
- **Performance:** Single CDN load, cached across all pages
- **Maintainability:** Update CDN version in one place (apollo-core)
- **UX:** Rich, clean, modern, minimalist feel with #fff background

---

**Next Steps:**

1. Apply version updates
2. Test on local environment
3. Verify design consistency across all pages
4. Document any custom overrides needed
