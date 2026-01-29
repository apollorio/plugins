# Apollo CDN ↔ WordPress Plugin Integration Map

> **Version:** 4.3.0
> **Last Updated:** 2026-01-17
> **Status:** ✅ Production Ready

## 🏗️ Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          CDN (cdn.apollo.rio.br)                            │
├─────────────────────────────────────────────────────────────────────────────┤
│  index.min.js v4.3.0 ─→ Entry Point (Asset Loader + Inline Tokens)          │
│       │                                                                     │
│       ├── styles/00-tokens.css      → Design Tokens (CSS Custom Props)      │
│       ├── styles/01-reset.css       → Browser Normalization                 │
│       ├── styles/02-base.css        → Typography, Base Elements             │
│       ├── styles/03-layout.css      → Grid, Flexbox, Containers             │
│       ├── styles/04-utilities.css   → Helper/Utility Classes                │
│       ├── styles/99-overrides.css   → Final Overrides (ALWAYS LAST)         │
│       │                                                                     │
│       ├── icon.js v5.1.0            → SVG Icon Runtime (CSS Masks)          │
│       ├── js/dark-mode.js v2.1.0    → Theme Toggle + localStorage           │
│       ├── js/tab.js v2.0.0          → ARIA Tabs + View Transitions          │
│       ├── js/scroll.min.js v3.0.0   → Scroll Animations (IntersectionObs)   │
│       ├── js/apollo-wp-bridge.js    → WordPress Integration Layer           │
│       └── js/jquery.min.js          → jQuery (Legacy Support)               │
└─────────────────────────────────────────────────────────────────────────────┘
                                     │
                                     ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                       WordPress (apollo-core Bridge)                        │
├─────────────────────────────────────────────────────────────────────────────┤
│  apollo-core.php ─→ Central Hub                                             │
│       │                                                                     │
│       ├── class-apollo-assets.php         → Asset Registration/Enqueueing   │
│       ├── class-apollo-integration-bridge → Plugin Communication            │
│       └── APOLLO_CDN_BASE constant        → 'https://assets.apollo.rio.br'  │
│                                                                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐              │
│  │ apollo-events   │  │ apollo-social   │  │ apollo-rio      │              │
│  │ manager         │  │                 │  │ (PWA)           │              │
│  ├─────────────────┤  ├─────────────────┤  ├─────────────────┤              │
│  │ CPT: event_list │  │ User Pages      │  │ Service Worker  │              │
│  │ event_dj        │  │ Classifieds     │  │ Offline Support │              │
│  │ event_local     │  │ Social Feed     │  │ Preconnect      │              │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘              │
└─────────────────────────────────────────────────────────────────────────────┘
```

## 🔗 CDN Asset Manifest

| File                     | Version | Size | Guard                   | MutationObserver | Caching      |
| ------------------------ | ------- | ---- | ----------------------- | ---------------- | ------------ |
| `index.min.js`           | 4.3.0   | ~2KB | `__APOLLO_RIO__`        | ✗                | Set()        |
| `icon.js`                | 5.1.0   | ~8KB | `__apolloIconRuntime`   | ✓                | WeakMap      |
| `js/scroll.min.js`       | 3.0.0   | ~4KB | `__apolloScrollRuntime` | ✓                | WeakSet      |
| `js/tab.js`              | 2.0.0   | ~3KB | `__apolloTabRuntime`    | ✗                | Map          |
| `js/dark-mode.js`        | 2.1.0   | ~1KB | `__apolloDarkMode`      | ✗                | localStorage |
| `js/apollo-wp-bridge.js` | 1.0.0   | ~2KB | `__apolloWPBridge`      | ✗                | -            |

## 📋 CSS Class Contract (DO NOT CHANGE)

### Scroll/Emerge Classes

```css
.ap-emerge,
.ap-emerge-left,
.ap-emerge-right,
.ap-emerge-scale,
.ap-emerge-fade .reveal-up,
.apollo-card .ap-stagger,
.ap-delay-100 ... .ap-delay-1000 [data-emerge],
[data-emerge="up|down|left|right|scale|fade"];
```

### Icon Classes

```css
.ri-* (RemixIcon),
.fa-* (FontAwesome → Apollo),
.i-* (Apollo Icons) .icon-*,
[data-apollo-icon] .i-0.5x ... .i-10x (sizing) .i-beat,
.i-fade,
.i-bounce,
.i-flip,
.i-shake,
.i-spin (animations);
```

### Tab Classes

```css
[data-tabs], [data-tablist], [data-tab], [data-tabpanel]
[role="tablist"], [role="tab"], [role="tabpanel"]
[data-tabs-active], [data-tabs-duration], [data-tabs-easing]
```

### Dark Mode

```css
html.dark-mode
[data-theme="dark|light|auto"]
```

## 🔌 WordPress Integration Points

### 1. Asset Loading (apollo-core)

```php
// class-apollo-assets.php
wp_register_script(
    'apollo-cdn-loader',
    'https://assets.apollo.rio.br/index.min.js',
    array(),
    '4.3.0',
    false // Load in <head> for priority
);
```

### 2. Plugin Registration

```php
// In each plugin (events, social, rio):
add_action('apollo_integration_bridge_ready', function($bridge) {
    $bridge->register_plugin('events', [
        'version' => '2.0.0',
        'supports' => ['events', 'calendar', 'maps']
    ]);
});
```

### 3. JavaScript Events

```javascript
// Cross-plugin communication via apollo-wp-bridge.js
Apollo.wp.on("event:view", function (data) {
  console.log("Event viewed:", data.eventId);
});

Apollo.wp.trigger("analytics:track", "page_view", { page: "eventos" });
```

## 🔄 Data Flow

```
User Action → CDN Script → Apollo.wp.trigger() → WP Hook → Plugin Handler
     ↑                                                           │
     └────────────────── Apollo.wp.emit() ◄─────────────────────┘
```

### Example: Event Favorite

```javascript
// Frontend (CDN)
document.querySelector(".favorite-btn").addEventListener("click", function () {
  Apollo.wp.trigger("event:favorite", eventId, true);
});

// WordPress (apollo-events-manager)
Apollo.wp.on("event:favorite", async function (data) {
  await Apollo.wp.ajax("apollo_toggle_favorite", {
    event_id: data.eventId,
    favorited: data.favorited,
  });
});
```

## 📦 Version Sync Matrix

| Component     | CDN   | apollo-core | events-manager | social | rio    |
| ------------- | ----- | ----------- | -------------- | ------ | ------ |
| CDN Loader    | 4.3.0 | 4.3.0       | 4.3.0          | 4.3.0  | 4.3.0  |
| Design Tokens | 2.0.0 | 2.0.0       | compat         | compat | compat |
| Icon Runtime  | 5.1.0 | -           | -              | -      | -      |
| WP Bridge     | 1.0.0 | 1.0.0       | 1.0.0          | 1.0.0  | 1.0.0  |

## 🛡️ Security Checklist

- [x] All CDN scripts have runtime guards (prevent double-init)
- [x] CORS headers configured for `cdn.apollo.rio.br`
- [x] SRI hashes available for critical scripts
- [x] No inline `eval()` or `Function()` calls
- [x] All API calls use nonce verification
- [x] Scripts use `crossorigin="anonymous"`

## 🚀 Performance Optimizations

1. **Inline Tokens**: Critical CSS variables injected by `index.min.js` before any network request
2. **Preconnect**: `<link rel="preconnect" href="https://cdn.apollo.rio.br">`
3. **Parallel Loading**: JS files loaded with `async` (independent) or `defer` (ordered)
4. **WeakMap/WeakSet**: No memory leaks from DOM element references
5. **MutationObserver**: Dynamic content handled without polling
6. **IntersectionObserver**: Lazy animations, reduce main thread work

## 📝 Deprecation Notes

| Deprecated              | Replacement        | Removal Date |
| ----------------------- | ------------------ | ------------ |
| `reveal-up.js`          | `scroll.min.js`    | Removed      |
| `uni.css` (direct load) | CDN `index.min.js` | Q2 2026      |
| `base.js`               | `index.min.js`     | Q2 2026      |

## 🧪 Testing Checklist

```bash
# Verify CDN loads correctly
curl -I https://cdn.apollo.rio.br/index.min.js

# Check runtime guards
console.log(window.__APOLLO_RIO__);        // Should be 1
console.log(window.__apolloIconRuntime);   // Should be 1
console.log(window.__apolloScrollRuntime); // Should be true
console.log(window.__apolloTabRuntime);    // Should be 1

# Verify WP integration
console.log(Apollo.wp.getActivePlugins()); // ['core', 'events', ...]
console.log(Apollo.wp.nonce());            // WP nonce string
```

---

**Maintainer:** Apollo Team
**Documentation:** https://docs.apollo.rio.br/cdn
