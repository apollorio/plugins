<?php
/**
 * API Usage Examples - Comunas & Nucleos
 *
 * This file provides JavaScript/Fetch examples for consuming the new REST API.
 * All POST/PUT/PATCH/DELETE requests require nonce verification.
 *
 * @package Apollo\Docs
 */

?>
# API Usage Guide: Comunas & Nucleos

## Getting the Nonce

**PHP (in template)**:
```php
wp_localize_script('apollo-js', 'apolloData', [
    'nonce' => wp_create_nonce('apollo_rest_nonce'),
    'api_url' => rest_url('apollo/v1'),
]);
```

**JavaScript**:
```javascript
const nonce = window.apolloData.nonce;
const apiUrl = window.apolloData.api_url;
```

---

## Comunas (Public Communities)

### List Comunas
```javascript
// No auth required, public list
fetch(`${apiUrl}/comunas?limit=20&offset=0`)
    .then(r => r.json())
    .then(data => console.log(data));
```

### Get Single Comuna
```javascript
fetch(`${apiUrl}/comunas/123`)
    .then(r => r.json())
    .then(data => console.log(data));
```

### Create Comuna
```javascript
fetch(`${apiUrl}/comunas/create`, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,  // ⚠️ REQUIRED
    },
    body: JSON.stringify({
        name: 'My Community',
        description: 'A public community',
        visibility: 'public',  // optional
    }),
})
    .then(r => r.json())
    .then(data => console.log('Created:', data.id));
```

### Join Comuna
```javascript
fetch(`${apiUrl}/comunas/123/join`, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,  // ⚠️ REQUIRED
    },
})
    .then(r => r.json())
    .then(data => console.log('Joined:', data.success));
```

### Leave Comuna
```javascript
fetch(`${apiUrl}/comunas/123/leave`, {
    method: 'POST',
    headers: {
        'X-WP-Nonce': nonce,
    },
})
    .then(r => r.json())
    .then(data => console.log('Left:', data.success));
```

### View My Comunas
```javascript
// Auth required
fetch(`${apiUrl}/comunas/my`)
    .then(r => r.json())
    .then(data => console.log('My comunas:', data));
```

### Invite User to Comuna
```javascript
fetch(`${apiUrl}/comunas/123/invite`, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
    },
    body: JSON.stringify({
        user_id: 456,
    }),
})
    .then(r => r.json())
    .then(data => console.log('Invited:', data.success));
```

### View Comuna Members
```javascript
// Auth required, must be member
fetch(`${apiUrl}/comunas/123/members`)
    .then(r => r.json())
    .then(data => console.log('Members:', data));
```

---

## Nucleos (Private Teams)

### List Nucleos
```javascript
// Auth required (only shows nucleos you can access)
fetch(`${apiUrl}/nucleos?limit=20&offset=0`)
    .then(r => r.json())
    .then(data => console.log(data));
```

### Get Single Nucleo
```javascript
fetch(`${apiUrl}/nucleos/789`)
    .then(r => r.json())
    .then(data => console.log(data));
```

### Create Nucleo
```javascript
// Requires 'apollo_create_nucleo' capability
fetch(`${apiUrl}/nucleos/create`, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
    },
    body: JSON.stringify({
        name: 'Engineering Team',
        description: 'Private development team',
        visibility: 'private',  // auto
        post_cap: 'moderator',  // only mods can post
    }),
})
    .then(r => r.json())
    .then(data => console.log('Created nucleo:', data.id));
```

### Join Nucleo (with Approval)
```javascript
// Nucleos require approval: returns 202 (pending) not 200
fetch(`${apiUrl}/nucleos/789/join`, {
    method: 'POST',
    headers: {
        'X-WP-Nonce': nonce,
    },
})
    .then(r => r.json())
    .then(data => {
        if (data.status === 'pending_approval') {
            console.log('Request sent, awaiting approval');
        } else {
            console.log('Joined immediately');
        }
    });
```

### View My Nucleos
```javascript
fetch(`${apiUrl}/nucleos/my`)
    .then(r => r.json())
    .then(data => console.log('My nucleos:', data));
```

---

## Error Handling

```javascript
async function handleApiCall(url, options = {}) {
    try {
        const res = await fetch(url, {
            headers: {
                'X-WP-Nonce': nonce,
                'Content-Type': 'application/json',
                ...options.headers,
            },
            ...options,
        });

        // Check HTTP status
        if (res.status === 401) {
            console.error('Not authenticated');
            // Redirect to login
            return;
        }
        if (res.status === 403) {
            console.error('Permission denied');
            return;
        }
        if (res.status === 429) {
            console.error('Too many requests. Wait before retrying.');
            return;
        }
        if (!res.ok) {
            const error = await res.json();
            console.error('API error:', error);
            return;
        }

        return await res.json();
    } catch (e) {
        console.error('Network error:', e);
    }
}

// Usage
await handleApiCall(`${apiUrl}/comunas/123/join`, { method: 'POST' });
```

---

## Legacy: Using /groups (Deprecated)

```javascript
// ⚠️ DO NOT USE - Will be removed
// Use /comunas or /nucleos instead
fetch(`${apiUrl}/groups`)
    .then(r => {
        console.log(r.headers.get('Deprecation')); // "true"
        console.log(r.headers.get('Sunset'));      // future date
        console.log(r.headers.get('Link'));        // "</apollo/v1/comunas>"
        return r.json();
    });
```

---

## Response Status Codes

| Code | Meaning | Example |
|------|---------|---------|
| 200 | Success | User joined immediately |
| 201 | Created | New group created |
| 202 | Accepted | Request sent (pending approval) |
| 400 | Bad request | Invalid parameters |
| 401 | Unauthorized | Not logged in |
| 403 | Forbidden | No permission / Invalid nonce |
| 404 | Not found | Group doesn't exist |
| 429 | Too many requests | Rate limit exceeded, wait 1h |
| 500 | Server error | Internal error |

---

## Rate Limits (per user per group per hour)

- **Join**: 10 requests
- **Join Nucleo**: 5 requests
- **Invite**: 20 requests
- **Other mutations**: 20 requests

---

## Migration from Old API

**Old** (deprecated):
```javascript
// POST /apollo/v1/groups/create
// POST /apollo/v1/groups/{id}/join
// GET /apollo/v1/groups/my
```

**New**:
```javascript
// POST /apollo/v1/comunas/create       (for public communities)
// POST /apollo/v1/nucleos/create       (for private teams)
// POST /apollo/v1/comunas/{id}/join
// POST /apollo/v1/nucleos/{id}/join
// GET /apollo/v1/comunas/my
// GET /apollo/v1/nucleos/my
```

---

## Support

- API docs: `wp-content/plugins/apollo-social/README.md`
- Implementation: See `PHASE-2-3-IMPLEMENTATION.md`
- Issues: Contact admin or create WP-CLI diagnostic: `wp apollo schema status`
