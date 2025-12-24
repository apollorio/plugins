# Apollo Snippets Manager

## Overview

The Apollo Snippets Manager allows administrators to add custom **CSS and JavaScript** snippets that apply **only to Apollo pages** (never site-wide).

> ⚠️ **Security Note**: For security reasons, only CSS and JavaScript are supported. PHP execution from the database is not allowed.

---

## Accessing the Manager

1. Navigate to **Apollo > Snippets (CSS/JS)** in the WordPress admin
2. View existing snippets or click **Add New Snippet**

---

## Creating a Snippet

### Required Fields

| Field | Description |
|-------|-------------|
| **Title** | A descriptive name for the snippet |
| **Type** | `CSS` or `JavaScript` |

### Optional Fields

| Field | Description | Default |
|-------|-------------|---------|
| **Notes** | Internal documentation about what this snippet does | — |
| **Priority** | Loading order (lower = earlier) | 10 |
| **Enabled** | Toggle snippet on/off without deleting | ✓ |

---

## Snippet Types

### CSS Snippets

CSS snippets are injected via `wp_add_inline_style()` attached to the `apollo-core-uni` stylesheet.

```css
/* Example: Custom event card styling */
.event-card {
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
```

### JavaScript Snippets

JS snippets are injected via `wp_add_inline_script()` attached to the `apollo-core-base` script.

```javascript
// Example: Track event card clicks
document.querySelectorAll('.event-card').forEach(card => {
    card.addEventListener('click', () => {
        console.log('Event clicked:', card.dataset.eventId);
    });
});
```

---

## Scoping

**Snippets only run on Apollo pages.** This includes:

- Event listings (`/eventos`)
- Event singles (`/evento/{slug}`)
- Venue pages (`/locais`, `/local/{slug}`)
- DJ pages (`/djs`, `/dj/{slug}`)
- Post types: `event_listing`, `local`, `dj`
- Canvas mode (`?canvas=1`)
- Pages filtered via `apollo_is_apollo_page`

Snippets will **NOT** run on:

- Regular WordPress pages/posts
- WooCommerce pages
- Other plugin pages
- Admin dashboard

---

## Priority System

Priority controls loading order (CSS cascade / JS execution order):

| Priority | Use Case |
|----------|----------|
| 1-5 | Early overrides, variable definitions |
| 10 | Default (recommended for most snippets) |
| 20-50 | Late adjustments, DOM-dependent JS |
| 99 | Final overrides, cleanup |

```
Priority 5:  Loads first
Priority 10: Loads second (default)
Priority 20: Loads third
```

---

## Best Practices

### CSS Snippets

1. **Use Apollo classes** for specificity:
   ```css
   .apollo-page .my-element { }
   ```

2. **Avoid `!important`** unless necessary

3. **Group related styles** in one snippet

4. **Use CSS custom properties** when possible:
   ```css
   :root {
       --custom-accent: #ff6b6b;
   }
   ```

### JavaScript Snippets

1. **Wrap in IIFE** to avoid globals:
   ```javascript
   (function() {
       // Your code here
   })();
   ```

2. **Wait for DOM ready** if needed:
   ```javascript
   document.addEventListener('DOMContentLoaded', function() {
       // DOM is ready
   });
   ```

3. **Check for elements** before using:
   ```javascript
   const el = document.querySelector('.my-element');
   if (el) {
       // Safe to use
   }
   ```

4. **Use dataset** for data attributes:
   ```javascript
   element.dataset.eventId // reads data-event-id
   ```

---

## Managing Snippets

### Enable/Disable

Click the status icon (✓/○) in the snippets list to toggle without editing.

### Editing

Click the snippet title or **Edit** button to modify code.

### Deleting

Click **Delete** and confirm. This action is permanent.

---

## Storage

Snippets are stored in the WordPress options table as a serialized array:

- **Option name**: `apollo_snippets`
- **Max code size**: 200KB per snippet
- **Backup friendly**: Included in standard WordPress exports

---

## Technical Details

### Injection Points

| Type | Attached To | Hook Priority |
|------|-------------|---------------|
| CSS | `apollo-core-uni` | 100 |
| JS | `apollo-core-base` | 100 |

### Output Order

1. Apollo vendor assets load
2. Apollo core assets load
3. Snippets inject (sorted by priority)

---

## Troubleshooting

### Snippet not appearing?

1. ✅ Is the snippet **enabled**?
2. ✅ Are you on an **Apollo page**?
3. ✅ Is `apollo-core-uni` (CSS) or `apollo-core-base` (JS) enqueued?
4. ✅ Check browser console for JavaScript errors

### CSS not applying?

1. Check specificity (add `.apollo-page` prefix)
2. Inspect element to see if styles are being overridden
3. Try increasing priority (lower number)

### JavaScript errors?

1. Open browser DevTools console
2. Look for syntax errors in your snippet
3. Ensure DOM elements exist before accessing
4. Wrap code in try/catch for debugging

### Changes not visible?

1. Clear browser cache
2. Clear WordPress/server cache
3. Check if snippet is enabled

---

## Examples

### Hide Element on Events Page

```css
/* Hide sidebar on event listings - Priority: 10 */
.apollo-page.apollo-route-eventos .sidebar {
    display: none;
}
```

### Custom Event Card Hover

```css
/* Animated hover effect - Priority: 10 */
.event-card {
    transition: transform 0.2s ease;
}
.event-card:hover {
    transform: translateY(-4px);
}
```

### Track Outbound Clicks

```javascript
/* Analytics tracking - Priority: 20 */
(function() {
    document.querySelectorAll('a[href^="http"]:not([href*="' + location.hostname + '"])').forEach(link => {
        link.addEventListener('click', function() {
            console.log('Outbound click:', this.href);
            // Add your analytics call here
        });
    });
})();
```

### Lazy Load Images

```javascript
/* Intersection Observer lazy load - Priority: 10 */
(function() {
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    observer.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            observer.observe(img);
        });
    }
})();
```
