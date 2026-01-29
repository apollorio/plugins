# APOLLO-RIO TEMPLATE SYSTEM — AI & DEVELOPER GUIDE

**Version**: 2.3.0  
**Date**: 31 December 2025  
**Purpose**: Enable AI assistants and developers to build missing template pages

---

## 🎯 QUICK START

### File Structure
```
theme/
├── inc/
│   └── apollo-template-functions.php    ← Include in functions.php
├── template-parts/
│   ├── activity/feed.php
│   ├── members/directory.php, single.php
│   ├── groups/listing.php, single.php
│   ├── events/listing.php, single.php
│   ├── chat/interface.php
│   ├── documents/listing.php
│   ├── classifieds/marketplace.php
│   ├── connections/bubble.php
│   ├── gamification/leaderboard.php
│   ├── moderation/panel.php
│   └── onboarding/wizard.php
├── assets/
│   ├── css/apollo-templates.css
│   └── js/apollo-templates.js
└── page-*.php                            ← Page templates
```

### Installation
```php
// In functions.php
require_once get_template_directory() . '/inc/apollo-template-functions.php';
```

---

## 📡 REST API REFERENCE

### Namespaces
| Namespace | Base URL | Plugin |
|-----------|----------|--------|
| `apollo/v1` | `/wp-json/apollo/v1/` | apollo-social, apollo-core |
| `apollo-core/v1` | `/wp-json/apollo-core/v1/` | apollo-core |
| `apollo-events/v1` | `/wp-json/apollo-events/v1/` | apollo-events-manager |

### Authentication
```php
// Server-side: Get nonce
$nonce = wp_create_nonce('wp_rest');

// Client-side: Include in headers
headers: { 'X-WP-Nonce': apolloData.nonce }
```

---

## 📋 ENDPOINTS BY MODULE

### 1. ACTIVITY STREAM
| Endpoint | Method | Auth | Template |
|----------|--------|------|----------|
| `/activity` | GET | No | feed.php |
| `/activity` | POST | Yes | feed.php |
| `/activity/me` | GET | Yes | feed.php |
| `/activity/friends` | GET | Yes | feed.php |
| `/activity/mentions` | GET | Yes | feed.php |
| `/activity/group/{id}` | GET | No | single-group.php |

**PHP Helper**: `apollo_get_activity_feed()`, `apollo_get_my_activity()`

### 2. MEMBERS
| Endpoint | Method | Auth | Template |
|----------|--------|------|----------|
| `/members` | GET | No | directory.php |
| `/members/online` | GET | No | directory.php |
| `/members/{id}` | GET | No | single.php |
| `/me` | GET | Yes | my-profile.php |
| `/me/stats` | GET | Yes | dashboard.php |

**PHP Helper**: `apollo_get_members()`, `apollo_get_member_profile()`

### 3. GROUPS (COMUNAS & NUCLEOS)
| Endpoint | Method | Auth | Template |
|----------|--------|------|----------|
| `/comunas` | GET | No | listing.php |
| `/comunas/{id}` | GET | No | single.php |
| `/comunas/{id}/members` | GET | Member | single.php |
| `/comunas/create` | POST | Yes | listing.php |
| `/comunas/{id}/join` | POST | Yes | single.php |
| `/comunas/{id}/leave` | POST | Yes | single.php |
| `/comunas/{id}/invite` | POST | Admin | single.php |
| `/nucleos` | GET | Yes | listing.php |
| `/nucleos/{id}` | GET | Yes | single.php |
| `/nucleos/{id}/members` | GET | Member | single.php |
| `/nucleos/create` | POST | Cap | listing.php |
| `/nucleos/{id}/join` | POST | Yes | single.php |

**PHP Helper**: `apollo_get_comunas()`, `apollo_get_nucleos()`, `apollo_is_group_member()`

### 4. EVENTS
| Endpoint | Method | Auth | Template |
|----------|--------|------|----------|
| `/eventos` | GET | No | listing.php |
| `/eventos/{id}` | GET | No | single.php |
| `/eventos/proximos` | GET | No | listing.php |
| `/eventos/passados` | GET | No | listing.php |
| `/eventos/{id}/confirmar` | POST | Yes | single.php |
| `/eventos/{id}/convidados` | GET | Yes | single.php |
| `/events/{id}/interest` | POST | Yes | single.php |
| `/events/{id}/rsvp` | POST | Yes | single.php |

**PHP Helper**: `apollo_get_upcoming_events()`, `apollo_get_event()`, `apollo_get_user_event_rsvp()`

### 5. CONNECTIONS (BOLHA)
| Endpoint | Method | Auth | Template |
|----------|--------|------|----------|
| `/bolha/listar` | GET | Yes | bubble.php |
| `/bolha/pedidos` | GET | Yes | bubble.php |
| `/bolha/pedir` | POST | Yes | single-member.php |
| `/bolha/aceitar` | POST | Yes | bubble.php |
| `/bolha/rejeitar` | POST | Yes | bubble.php |
| `/bolha/remover` | POST | Yes | bubble.php |
| `/bubble` | GET | Yes | bubble.php |
| `/bubble/add` | POST | Yes | bubble.php |
| `/bubble/remove` | POST | Yes | bubble.php |
| `/follow` | POST | Yes | single-member.php |
| `/unfollow` | POST | Yes | single-member.php |

**PHP Helper**: `apollo_get_user_friends()`, `apollo_get_friendship_status()`

### 6. CHAT
| Endpoint | Method | Auth | Template |
|----------|--------|------|----------|
| `/chat/conversations` | GET | Yes | interface.php |
| `/chat/conversations/{id}` | GET | Yes | interface.php |
| `/chat/messages` | POST | Yes | interface.php |
| `/chat/messages/{id}/read` | POST | Yes | interface.php |
| `/chat/typing` | POST | Yes | interface.php |
| `/chat/online` | GET | Yes | interface.php |

**PHP Helper**: `apollo_get_conversations()`, `apollo_get_conversation_messages()`

### 7. DOCUMENTS & SIGNATURES
| Endpoint | Method | Auth | Template |
|----------|--------|------|----------|
| `/documents` | GET | Yes | listing.php |
| `/documents` | POST | Yes | listing.php |
| `/documents/{id}` | GET | Yes | single.php |
| `/documents/{id}/share` | POST | Yes | single.php |
| `/signatures/create` | POST | Yes | sign.php |
| `/signatures/{id}/sign` | POST | Yes | sign.php |
| `/signatures/{id}/verify` | GET | No | verify.php |

**PHP Helper**: `apollo_get_user_documents()`, `apollo_get_document()`

### 8. CLASSIFIEDS
| Endpoint | Method | Auth | Template |
|----------|--------|------|----------|
| `/anuncios` | GET | No | marketplace.php |
| `/anuncio/{id}` | GET | No | single.php |
| `/anuncio/add` | POST | Yes | create.php |

**PHP Helper**: `apollo_get_classifieds()`, `apollo_get_classified()`

### 9. GAMIFICATION
| Endpoint | Method | Auth | Template |
|----------|--------|------|----------|
| `/points/me` | GET | Yes | leaderboard.php |
| `/leaderboard` | GET | No | leaderboard.php |
| `/competitions` | GET | No | competitions.php |

**PHP Helper**: `apollo_get_user_points()`, `apollo_get_leaderboard()`

### 10. MODERATION
| Endpoint | Method | Auth | Template |
|----------|--------|------|----------|
| `/mod/fila` | GET | Mod | panel.php |
| `/mod/stats` | GET | Mod | panel.php |
| `/mod/aprovar` | POST | Mod | panel.php |
| `/mod/negar` | POST | Mod | panel.php |
| `/mod/reports` | GET | Mod | panel.php |
| `/mod/report` | POST | Yes | (any) |

**PHP Helper**: `apollo_get_moderation_queue()`, `apollo_get_moderation_stats()`

### 11. ONBOARDING
| Endpoint | Method | Auth | Template |
|----------|--------|------|----------|
| `/onboarding/step` | GET | Yes | wizard.php |
| `/onboarding/profile` | POST | Yes | wizard.php |
| `/onboarding/interests` | POST | Yes | wizard.php |

**PHP Helper**: `apollo_get_onboarding_progress()`, `apollo_is_onboarding_complete()`

---

## 🔧 MISSING TEMPLATES TO BUILD

Based on REST endpoints, these pages need templates:

### Priority 1 (Core)
| Page | REST Endpoints | Status |
|------|----------------|--------|
| Feed/Explore | `/explore`, `/feed` | ✅ Done |
| Members Directory | `/members` | ✅ Done |
| Member Profile | `/members/{id}` | ✅ Done |
| Groups Listing | `/comunas`, `/nucleos` | ✅ Done |
| Single Group | `/comunas/{id}` | ✅ Done |
| Events Listing | `/eventos` | ✅ Done |
| Single Event | `/eventos/{id}` | ✅ Done |
| Chat | `/chat/*` | ✅ Done |

### Priority 2 (Features)
| Page | REST Endpoints | Status |
|------|----------------|--------|
| Documents | `/documents` | ✅ Done |
| Document Sign | `/signatures/{id}/sign` | ⏳ Need |
| Marketplace | `/anuncios` | ✅ Done |
| Classified Single | `/anuncio/{id}` | ⏳ Need |
| Connections | `/bolha/*` | ✅ Done |
| Leaderboard | `/leaderboard` | ✅ Done |
| Onboarding | `/onboarding/*` | ✅ Done |

### Priority 3 (Admin)
| Page | REST Endpoints | Status |
|------|----------------|--------|
| Moderation Panel | `/mod/*` | ✅ Done |
| User Dashboard | Various | ✅ From ZIP |
| Suppliers Catalog | Custom | ✅ From ZIP |
| Sign Centered | Custom | ✅ From ZIP |

---

## 🏗️ TEMPLATE PATTERN

### Basic Page Template
```php
<?php
/**
 * Template Name: [Name]
 * File: page-[slug].php
 * REST: [endpoints used]
 */

// Auth check if needed
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

$user_id = get_current_user_id();
// Fetch data using helper functions

get_header();
?>

<div class="apollo-[module]">
    <!-- Content -->
</div>

<?php get_footer(); ?>
```

### Template Part Pattern
```php
<?php
/**
 * [Description]
 * File: template-parts/[module]/[name].php
 * REST: [endpoints]
 */

$user_id = get_current_user_id();
$data = apollo_helper_function();
?>

<div class="apollo-[module]">
    <?php if (!empty($data)): ?>
        <?php foreach ($data as $item): ?>
        <!-- Render item -->
        <?php endforeach; ?>
    <?php else: ?>
    <div class="empty-state">
        <i class="ri-[icon]-line"></i>
        <p>No data found.</p>
    </div>
    <?php endif; ?>
</div>
```

---

## 🎨 CSS CLASS CONVENTIONS

| Pattern | Usage |
|---------|-------|
| `.apollo-[module]` | Main container |
| `.[module]-card` | Card components |
| `.[module]-grid` | Grid layouts |
| `.btn-[action]` | Action buttons |
| `.empty-state` | No data message |
| `.tab-btn`, `.tab-content` | Tab navigation |
| `.modal`, `.modal-content` | Modal dialogs |

---

## ⚡ JAVASCRIPT API

```javascript
// Activity
Apollo.activity.post(content)
Apollo.activity.like(id)

// Groups
Apollo.groups.join(groupId, 'comuna')
Apollo.groups.join(groupId, 'nucleo')
Apollo.groups.leave(groupId, type)
Apollo.groups.invite(groupId, userId, type)

// Events
Apollo.events.rsvp(eventId)
Apollo.events.toggleInterest(eventId)

// Connections
Apollo.connections.sendRequest(userId)
Apollo.connections.acceptRequest(userId)
Apollo.connections.follow(userId)

// Chat
Apollo.chat.sendMessage(conversationId, content, receiverId)

// UI Helpers
Apollo.ui.toast('Message', 'success')
Apollo.ui.confirm('Are you sure?').then(confirmed => {})
```

---

## 📱 RESPONSIVE BREAKPOINTS

| Breakpoint | Width |
|------------|-------|
| Mobile | < 768px |
| Tablet | 768px - 1024px |
| Desktop | > 1024px |

---

## ✅ CHECKLIST FOR NEW TEMPLATE

1. [ ] Identify REST endpoints needed
2. [ ] Check if PHP helper function exists (or create)
3. [ ] Create template file with proper header
4. [ ] Add auth check if required
5. [ ] Fetch data using helpers
6. [ ] Render with proper CSS classes
7. [ ] Add empty state
8. [ ] Wire up JS actions
9. [ ] Test REST calls
10. [ ] Mobile responsive check

---

## 🔗 USEFUL REFERENCES

- **REST Endpoints**: See `REST-ENDPOINTS.md` and `rest.md`
- **PHP Functions**: See `inc/apollo-template-functions.php`
- **JS API**: See `assets/js/apollo-templates.js`
- **Existing Templates**: See ZIP files from dashboard, documents, suppliers

---

**Document Version**: 2.3.0  
**Last Updated**: 31 December 2025
