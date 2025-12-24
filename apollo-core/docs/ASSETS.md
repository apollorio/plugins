# Apollo Assets Documentation

## Overview

Apollo uses a **local-first asset strategy**. All CSS, JavaScript, fonts, and images are bundled with the plugin to ensure:

1. **Zero CDN dependencies** - Works offline and in restricted networks
2. **Version consistency** - Assets match the plugin version
3. **GDPR compliance** - No third-party requests for fonts/icons
4. **Performance** - Assets can be cached by the server

---

## Directory Structure

```
apollo-core/
├── assets/
│   ├── core/                    # Apollo design system
│   │   ├── uni.css              # Main CSS framework
│   │   ├── base.js              # Global JavaScript behaviors
│   │   ├── animate.css          # Animation utilities
│   │   ├── dark-mode.js         # Theme toggle
│   │   ├── clock.js             # Clock widget
│   │   └── event-page.js        # Event-specific interactions
│   │
│   ├── img/                     # Images and placeholders
│   │   ├── apollo-logo.webp     # Apollo branding
│   │   ├── neon-green.webp      # Background asset
│   │   ├── placeholder-dj.webp  # DJ placeholder
│   │   ├── placeholder-event.webp
│   │   ├── placeholder-venue.webp
│   │   ├── default-event.jpg
│   │   └── vinyl.webp           # Decorative asset
│   │
│   └── vendor/                  # Third-party libraries
│       ├── remixicon/           # Icon font
│       │   ├── remixicon.css
│       │   └── fonts/           # woff2, woff, ttf
│       ├── leaflet/             # Map library
│       │   ├── leaflet.js
│       │   ├── leaflet.css
│       │   └── images/          # Map markers
│       ├── phosphor-icons/      # Alternative icons
│       │   └── phosphor-icons.js
│       ├── sortablejs/          # Drag & drop
│       │   └── Sortable.min.js
│       └── motion/              # Animation library
│           └── motion.min.js
```

---

## Asset Handles

All Apollo assets use prefixed handles for identification:

### Core Assets (apollo-core-*)

| Handle | Type | Description |
|--------|------|-------------|
| `apollo-core-uni` | CSS | Main design system stylesheet |
| `apollo-core-animate` | CSS | Animation utility classes |
| `apollo-core-base` | JS | Global behaviors and utilities |
| `apollo-core-darkmode` | JS | Dark mode toggle functionality |
| `apollo-core-clock` | JS | Clock widget for events |
| `apollo-core-event-page` | JS | Event page interactions |

### Vendor Assets (apollo-vendor-*)

| Handle | Type | Description |
|--------|------|-------------|
| `apollo-vendor-remixicon` | CSS | RemixIcon font (4.7.0) |
| `apollo-vendor-leaflet` | CSS | Leaflet map styles (1.9.4) |
| `apollo-vendor-leaflet` | JS | Leaflet map library |
| `apollo-vendor-sortable` | JS | SortableJS (1.15.0) |
| `apollo-vendor-motion` | JS | Motion animation library |
| `apollo-vendor-phosphor` | JS | Phosphor Icons |

---

## Using Apollo Assets

### In PHP (Recommended)

```php
// Enqueue all Apollo frontend assets
if ( class_exists( 'Apollo_Assets' ) ) {
    Apollo_Assets::enqueue_frontend();
}

// Enqueue only admin assets
if ( class_exists( 'Apollo_Assets' ) ) {
    Apollo_Assets::enqueue_admin();
}

// Get asset URL for inline use
$logo_url = APOLLO_CORE_PLUGIN_URL . 'assets/img/apollo-logo.webp';
```

### Using wp_enqueue_script/style

```php
// After Apollo_Assets::init() runs, handles are registered
wp_enqueue_style( 'apollo-core-uni' );
wp_enqueue_script( 'apollo-core-base' );
wp_enqueue_style( 'apollo-vendor-remixicon' );
```

---

## Versioning

All assets use **filemtime() versioning**:

- Cache busting based on actual file modification time
- No manual version bumps required
- Browser caches cleared automatically when files change

```php
// Internal implementation
$version = filemtime( $file_path );
wp_enqueue_style( 'handle', $url, $deps, $version );
```

---

## Scoped Loading

Apollo assets only load on **Apollo pages** to avoid conflicts:

```php
// Check if current page is an Apollo page
if ( function_exists( 'apollo_is_apollo_page' ) && apollo_is_apollo_page() ) {
    // Load assets
}
```

### What counts as an "Apollo page"?

- Routes: `/eventos`, `/locais`, `/djs`, `/evento/*`
- Post types: `event_listing`, `local`, `dj`
- Canvas mode: `?canvas=1`
- Filter: `add_filter( 'apollo_is_apollo_page', '__return_true' )`

---

## Legacy Handle Mapping

For backwards compatibility, old CDN handles are mapped to local files:

| Old Handle | New Handle |
|------------|------------|
| `remixicon` | `apollo-vendor-remixicon` |
| `apollo-uni-css` | `apollo-core-uni` |
| `apollo-base-js` | `apollo-core-base` |
| `apollo-motion` | `apollo-vendor-motion` |

---

## Helper Functions

### Get Placeholder Image

```php
$placeholder = apollo_get_placeholder_image( 'event' ); // event, dj, venue, user
```

### Get Logo URL

```php
$logo = apollo_get_logo_url();
```

---

## Adding Custom Assets

For custom CSS/JS that applies only to Apollo pages:

1. **Small snippets**: Use Apollo Snippets Manager (Apollo > Snippets in admin)
2. **Larger files**: Add to `assets/` and register in `Apollo_Assets::register_all()`

---

## CDN Migration Checklist

When migrating from CDN to local assets:

1. ✅ Download asset to appropriate `assets/` subdirectory
2. ✅ Add handle registration in `Apollo_Assets::register_all()`
3. ✅ Update any template `<link>` or `<script>` tags to use `wp_enqueue_*`
4. ✅ Test on Apollo pages and non-Apollo pages
5. ✅ Verify no external requests in browser Network tab

---

## Troubleshooting

### Assets not loading?

1. Check `apollo_is_apollo_page()` returns true
2. Verify file exists in `assets/` directory
3. Check browser console for 404 errors

### Font icons not displaying?

1. Verify `remixicon/fonts/` contains woff2, woff, ttf files
2. Check CSS path references fonts correctly
3. Clear browser cache

### Conflicts with theme?

Apollo assets only load on Apollo pages. If conflicts occur:

1. Use Canvas Mode (`?canvas=1`) for complete theme isolation
2. Increase CSS specificity with `.apollo-page` class
3. Check for duplicate handle registrations
