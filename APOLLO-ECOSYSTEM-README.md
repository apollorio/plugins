# Apollo Ecosystem - WordPress Plugins Suite

> **Version:** 1.0.0  
> **Last Updated:** December 22, 2025  
> **Status:** PRODUCTION READY ✅

---

## 📦 Plugin Overview

The Apollo Ecosystem is a suite of 4 interconnected WordPress plugins designed to power the Apollo::Rio platform - a social network and events discovery platform for the Brazilian music and nightlife scene.

| Plugin | Version | Description |
|--------|---------|-------------|
| **apollo-core** | 1.0.0 | Core utilities, shared assets, REST API backbone |
| **apollo-social** | 1.0.0 | Social features: groups, messaging, documents, user profiles |
| **apollo-rio** | 1.0.0 | PWA support, CDN asset loading, performance optimization |
| **apollo-events-manager** | 1.0.0 | Events, DJs, venues management with calendar views |

---

## 🔧 Requirements

- **PHP:** 8.1+ (8.3 recommended)
- **WordPress:** 6.0+
- **MySQL:** 8.0+ or MariaDB 10.5+
- **Node.js:** 18+ (for asset compilation)

---

## 📁 Directory Structure

```
wp-content/plugins/
├── apollo-core/                    # Core utilities & shared infrastructure
│   ├── admin/                      # Admin pages and settings
│   ├── assets/                     # CSS, JS, images
│   ├── includes/                   # PHP classes and utilities
│   ├── modules/                    # Auto-loaded modules (events, social)
│   └── templates/                  # Shared template files
│
├── apollo-social/                  # Social networking features
│   ├── assets/                     # Social-specific assets
│   ├── config/                     # Routes configuration
│   ├── src/                        # PSR-4 namespaced classes
│   ├── templates/                  # Social templates (groups, chat, feed)
│   └── user-pages/                 # User profile page system
│
├── apollo-rio/                     # PWA & Performance
│   ├── modules/pwa/                # Progressive Web App
│   └── assets/                     # Rio-specific assets
│
└── apollo-events-manager/          # Events management
    ├── includes/                   # CPT, REST, helpers
    └── templates/                  # Event templates & partials
```

---

## 🚀 Installation

### 1. Plugin Activation Order

**IMPORTANT:** Activate plugins in this order:

1. **apollo-core** (required first - provides foundation)
2. **apollo-social** (depends on apollo-core)
3. **apollo-rio** (optional, for PWA)
4. **apollo-events-manager** (depends on apollo-core)

### 2. Database Tables

The plugins automatically create the following tables on activation:

- `wp_apollo_mod_queue` - Moderation queue
- `wp_apollo_mod_log` - Moderation action log
- `wp_apollo_newsletter_subscribers` - Newsletter subscribers
- `wp_apollo_newsletter_campaigns` - Newsletter campaigns
- `wp_apollo_email_security_log` - Email security log
- `wp_apollo_user_visits` - User visit tracking

### 3. Flush Permalinks

After activation, go to **Settings → Permalinks** and click **Save Changes** to register custom routes.

---

## 🌐 Available Routes

### Apollo Social Routes

| Route | Template | Description |
|-------|----------|-------------|
| `/mural/` | feed/feed.php | Social feed (timeline) |
| `/chat/` | chat/chat-list.php | Instant messaging |
| `/painel/` | users/dashboard.php | User dashboard |
| `/documentos/` | documents/documents-page.php | Document manager |
| `/grupos/` | groups/groups-listing.php | Groups directory |
| `/comunidade/{slug}/` | groups/single-comunidade.php | Community page |
| `/nucleo/{slug}/` | groups/single-nucleo.php | Private group page |
| `/login/` | auth/login-register.php | Login/Register |

### Apollo Events Routes

| Route | Template | Description |
|-------|----------|-------------|
| `/eventos/` | portal-discover.php | Events discovery |
| `/evento/{id}/` | single-event_listing.php | Single event |
| `/enviar/` | page-cenario-new-event.php | Submit new event |
| `/dj/{id}/` | single-event_dj.php | DJ profile |
| `/local/{id}/` | single-event_local.php | Venue profile |

---

## 🔌 REST API Endpoints

Base namespace: `/wp-json/apollo/v1/`

### Events

- `GET /eventos` - List events
- `GET /evento/{id}` - Single event
- `POST /salvos/{id}` - Toggle bookmark
- `GET /categorias` - Event categories
- `GET /locais` - Venues list

### Social

- `GET /feed` - Social feed posts
- `GET /posts` - User posts
- `POST /like/{id}` - Toggle like
- `GET /groups` - Groups list
- `POST /groups/{id}/join` - Join group

### Documents

- `GET /documents` - User documents
- `POST /documents` - Create document
- `PUT /documents/{id}` - Update document

---

## 🎨 Design System

Apollo uses a unified design system called **uni.css** with:

- **CSS Variables** for theming (light/dark mode)
- **Remix Icons** for iconography
- **ShadCN-inspired** components
- **Mobile-first** responsive design

### Dark Mode

Toggle with `.dark-mode` class on `<body>`. Persisted in `localStorage.apolloDarkMode`.

### Key Variables

```css
:root {
    --bg-main: #fff;
    --text-primary: rgba(19, 21, 23, .85);
    --text-secondary: rgba(19, 21, 23, .7);
    --border-color: #e0e2e4;
    --radius-main: 12px;
    --accent-color: #FFA17F;
}

body.dark-mode {
    --bg-main: #131517;
    --text-primary: #fdfdfdfa;
    --text-secondary: #ffffff91;
    --border-color: #333537;
}
```

---

## 🔒 Security

### Implemented Measures

- ✅ Input sanitization on all user inputs
- ✅ Output escaping (esc_html, esc_attr, esc_url)
- ✅ Nonce verification on all forms
- ✅ Capability checks on admin actions
- ✅ CSRF protection on AJAX calls
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS prevention
- ✅ Email security logging

---

## 📋 Development

### PHP Coding Standards

```bash
# Run PHPCS
vendor/bin/phpcs --standard=phpcs.xml apollo-core/ apollo-social/

# Auto-fix
vendor/bin/phpcbf --standard=phpcs.xml apollo-core/
```

### Testing

```bash
# PHPUnit tests
vendor/bin/phpunit

# PHP syntax check
find apollo-* -name "*.php" -exec php -l {} \;
```

---

## 📚 Documentation Files

### Apollo Core
- [README.md](apollo-core/README.md) - Main documentation
- [docs/conversion-map.md](apollo-core/docs/conversion-map.md) - Template data contracts
- [docs/FRONTEND_CONTRACT.md](apollo-core/docs/FRONTEND_CONTRACT.md) - Frontend integration

### Apollo Social
- [README.md](apollo-social/README.md) - Main documentation
- [CANVAS-BUILDER-README.md](apollo-social/CANVAS-BUILDER-README.md) - Canvas mode system
- [WORKFLOWS-GUIDE.md](apollo-social/WORKFLOWS-GUIDE.md) - Moderation workflows

### Apollo Events Manager
- [README.md](apollo-events-manager/README.md) - Main documentation
- [QA-AUDIT-FINAL-REPORT.md](apollo-events-manager/QA-AUDIT-FINAL-REPORT.md) - QA status
- [SECURITY-FIXES-APPLIED.md](apollo-events-manager/SECURITY-FIXES-APPLIED.md) - Security log

---

## 🐛 Known Issues & Workarounds

### 1. Feed Route Conflict
**Issue:** `/feed/` conflicts with WordPress RSS feed.  
**Solution:** Social feed moved to `/mural/`

### 2. Co-Authors Plus Optional
**Issue:** Plugin logs warnings if Co-Authors Plus not installed.  
**Solution:** Warnings removed; feature gracefully degrades.

### 3. Newsletter Tables
**Issue:** Tables may not exist on fresh install.  
**Solution:** Auto-created on first admin page load via `maybe_create_tables()`.

---

## 📞 Support

For issues and feature requests, contact the Apollo development team.

---

## 📄 License

Proprietary - All Rights Reserved © Apollo::Rio 2025
