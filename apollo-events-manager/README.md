# Apollo Events Manager v0.1.0

Modern WordPress event management plugin with Motion.dev animations, ShadCN components, and Tailwind CSS.

## 🚀 Features

### Canvas Mode
- **Theme-Independent Pages**: Removes ALL theme CSS/JS
- **Automatic Page Creation**: /eventos/, /dashboard-eventos/, /djs/, /locais/
- **Whitelist System**: Only Apollo assets load

### uni.css Universal CSS
- **Single Source of Truth**: https://assets.apollo.rio.br/uni.css
- **Loads Last**: Priority 999999
- **Overrides Everything**: Tailwind, ShadCN, theme CSS

### Motion.dev Animations
- **Smooth Transitions**: Card animations, modal animations
- **Stagger Effects**: List items, dashboard tabs
- **Spring Animations**: Context menu, interactions

### Statistics & Analytics
- **CPT-based Tracking**: apollo_event_stat custom post type
- **Page vs Modal Views**: Separate counters
- **Line Graphs**: 90-day visualization (pure JavaScript)
- **User Dashboards**: Performance metrics

### Event Display
- **CodePen Exact Match**: 
  - Cards: https://codepen.io/Rafael-Valle-the-looper/pen/raxqVGR
  - Single: https://codepen.io/Rafael-Valle-the-looper/pen/raxKGqM
- **Mobile-Centered**: max-width 500px
- **Hero Tags**: Category + Tags + Type (NO sounds)
- **Marquee**: Only sounds (8x repetition)

## 📦 Installation

1. Upload to `/wp-content/plugins/apollo-events-manager/`
2. Activate plugin
3. Run: `npm install && npm run build`
4. Pages auto-created: /eventos/, /dashboard-eventos/

## 🔧 Requirements

- PHP: 8.1+
- WordPress: 6.0+
- Node.js: 16+ (for Tailwind build)

## 📚 Usage

### Shortcodes
```
[events] - Main events listing
[event_dashboard] - User dashboard
[event_djs] - DJs listing
[event_locals] - Venues listing
```

### Build Commands
```bash
npm run build        # Production build
npm run watch        # Development watch
npm run build:prod   # Optimized production
```

## 🎨 CSS Architecture

1. **uni.css** (MAIN) - Universal styles from CDN
2. RemixIcon - Icons
3. apollo-shadcn-components - ShadCN components
4. Tailwind - Forms, dashboards only

**Priority:** uni.css loads LAST and overrides all

## 📊 Statistics

- Track page/modal views separately
- 90-day daily breakdown
- Line graphs with animations
- Admin + user dashboards

## 🔒 Security

- XSS prevention (all outputs escaped)
- SQL injection prevention (prepared statements)
- CSRF protection (nonces)
- Capability checks
- Sanitization & validation

## ♿ Accessibility

- WCAG 2.1 Level AA compliant
- ARIA labels
- Keyboard navigation
- Screen reader compatible

## 📝 License

GPL-2.0-or-later

## 👥 Credits

Based on WP Event Manager, enhanced for Apollo::Rio
