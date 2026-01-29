# APOLLO-RIO TEMPLATE PARTS — INSTALLATION GUIDE

**Version**: 2.3.0  
**Date**: 31 December 2025  
**Compatible with**: apollo-social 2.3.0, apollo-core 1.8.0, apollo-events-manager 1.5.0

---

## 📦 PACKAGE CONTENTS

```
apollo-templates/
├── inc/
│   └── apollo-template-functions.php   # Core PHP functions (REQUIRED)
├── template-parts/
│   ├── activity/
│   │   └── feed.php                    # Activity stream
│   ├── members/
│   │   ├── directory.php               # Members listing
│   │   └── single.php                  # Member profile
│   ├── groups/
│   │   ├── listing.php                 # Comunas/Nucleos listing
│   │   └── single.php                  # Group detail page
│   ├── events/
│   │   ├── listing.php                 # Events listing
│   │   └── single.php                  # Event detail
│   ├── chat/
│   │   └── interface.php               # Chat system
│   ├── documents/
│   │   ├── listing.php                 # Documents list
│   │   └── sign.php                    # Document signing
│   ├── classifieds/
│   │   ├── marketplace.php             # Ads listing
│   │   └── single.php                  # Ad detail
│   ├── connections/
│   │   └── bubble.php                  # Friends/Bolha
│   ├── gamification/
│   │   └── leaderboard.php             # Points/ranking
│   ├── moderation/
│   │   └── panel.php                   # Mod queue
│   └── onboarding/
│       └── wizard.php                  # Onboarding flow
├── assets/
│   ├── css/apollo-templates.css
│   └── js/apollo-templates.js
├── existing-from-zips/                  # From your uploaded ZIPs
│   ├── page-user-dashboard.php
│   ├── page-suppliers-catalog.php
│   ├── page-sign-centered.php
│   └── ... (all template parts)
├── AI-DEVELOPER-GUIDE.md                # Full API reference
└── README.md                            # This file
```

---

## 🚀 INSTALLATION

### Step 1: Copy Files
```bash
# Copy to your theme
cp -r inc/ /path/to/theme/
cp -r template-parts/ /path/to/theme/
cp -r assets/ /path/to/theme/
```

### Step 2: Include Functions
```php
// In functions.php
require_once get_template_directory() . '/inc/apollo-template-functions.php';
```

### Step 3: Create Page Templates
```php
<?php
/**
 * Template Name: Feed
 */
get_header();
get_template_part('template-parts/activity/feed');
get_footer();
```

---

## ⚡ QUICK REFERENCE

### REST API Endpoints Used
| Module | Namespace | Key Endpoints |
|--------|-----------|---------------|
| Activity | `apollo/v1` | `/activity`, `/explore` |
| Members | `apollo/v1` | `/members`, `/me` |
| Groups | `apollo/v1` | `/comunas`, `/nucleos` |
| Events | `apollo-events/v1` | `/eventos`, `/events` |
| Chat | `apollo/v1` | `/chat/conversations` |
| Docs | `apollo/v1` | `/documents`, `/signatures` |

### JavaScript API
```javascript
// All API calls available via Apollo namespace
Apollo.activity.post(content)
Apollo.groups.join(groupId, 'comuna')
Apollo.events.rsvp(eventId)
Apollo.connections.sendRequest(userId)
Apollo.chat.sendMessage(convId, content, receiverId)
```

---

## 📋 PAGE TEMPLATES TO CREATE

| Page | Template Part | URL |
|------|---------------|-----|
| Feed | `activity/feed` | `/feed` |
| Members | `members/directory` | `/membros` |
| Profile | `members/single` | `/membro/{slug}` |
| Groups | `groups/listing` | `/grupos` |
| Single Group | `groups/single` | `/grupo/{slug}` |
| Events | `events/listing` | `/eventos` |
| Single Event | `events/single` | `/evento/{id}` |
| Chat | `chat/interface` | `/mensagens` |
| Documents | `documents/listing` | `/documentos` |
| Marketplace | `classifieds/marketplace` | `/classificados` |
| Connections | `connections/bubble` | `/conexoes` |
| Leaderboard | `gamification/leaderboard` | `/ranking` |

---

## 🔗 DEPENDENCIES

- Remix Icon CDN (loaded automatically)
- jQuery (WordPress built-in)
- Apollo Plugins REST API

---

## 📖 DOCUMENTATION

- **AI-DEVELOPER-GUIDE.md** — Full REST API reference and patterns
- **REST-ENDPOINTS.md** — Complete endpoint list (from uploads)
- **rest.md** — Detailed endpoint documentation

---

**Ready to deploy!** 🚀
