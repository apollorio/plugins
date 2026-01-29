# S4: REST API Namespace Consolidation - Implementation Guide

## Overview

This document describes the REST API namespace consolidation for the Apollo plugin ecosystem.
The goal is to unify all REST endpoints under a single `apollo/v1` namespace.

## Files Created

### 1. class-apollo-rest-namespace.php
**Path:** `apollo-core/includes/rest-api/class-apollo-rest-namespace.php`

Defines namespace constants and route utilities:
- `Apollo_REST_Namespace::V1` = `'apollo/v1'` (canonical)
- `Apollo_REST_Namespace::V2` = `'apollo/v2'` (future)
- `Apollo_REST_Namespace::LEGACY_CORE` = `'apollo-core/v1'` (deprecated)
- `Apollo_REST_Namespace::LEGACY_EVENTS` = `'apollo-events/v1'` (deprecated)

### 2. class-apollo-rest-registry.php
**Path:** `apollo-core/includes/rest-api/class-apollo-rest-registry.php`

Central route registry with:
- Conflict detection
- Route documentation via `/apollo/v1/discover`
- OpenAPI spec generation
- Statistics and grouping

### 3. class-apollo-rest-compat.php
**Path:** `apollo-core/includes/rest-api/class-apollo-rest-compat.php`

Backward compatibility layer:
- Legacy namespace redirects
- Deprecation headers (Sunset: 2025-12-31)
- Usage logging and admin notices

### 4. class-apollo-rest-response.php
**Path:** `apollo-core/includes/rest-api/class-apollo-rest-response.php`

Unified response format:
- `Apollo_REST_Response::success()`
- `Apollo_REST_Response::error()`
- `Apollo_REST_Response::paginated()`
- `Apollo_REST_Response::from_query()`
- Standard headers and caching

---

## Plugin Update Instructions

### Apollo Events Manager

**Before (legacy):**
```php
register_rest_route( 'apollo-events/v1', '/events', [
    'methods'  => 'GET',
    'callback' => [ $this, 'get_events' ],
    'permission_callback' => '__return_true',
] );
```

**After (unified):**
```php
use Apollo_Core\REST_API\Apollo_REST_Namespace;
use Apollo_Core\REST_API\Apollo_REST_Registry;
use Apollo_Core\REST_API\Apollo_REST_Response;

// Register via central registry
add_action( 'apollo_rest_register_routes', function( $registry ) {
    $registry->register(
        'events',
        [
            'methods'  => 'GET',
            'callback' => [ $this, 'get_events' ],
            'permission_callback' => '__return_true',
        ],
        'apollo-events-manager',
        'events'
    );
} );

// Use unified response format
public function get_events( WP_REST_Request $request ) {
    $query = new WP_Query( $args );
    return Apollo_REST_Response::from_query( $query, [ $this, 'prepare_event' ] );
}
```

### Apollo Core

**Before:**
```php
register_rest_route( 'apollo-core/v1', '/user/profile', [...] );
```

**After:**
```php
use Apollo_Core\REST_API\Apollo_REST_Namespace;

register_rest_route(
    Apollo_REST_Namespace::V1,
    '/users/me',
    [...]
);
```

### Apollo Social

Apollo Social already uses `apollo/v1`, but should adopt the response format:

```php
use Apollo_Core\REST_API\Apollo_REST_Response;

public function get_activity( WP_REST_Request $request ) {
    // Instead of:
    // return new WP_REST_Response( $data, 200 );

    // Use:
    return Apollo_REST_Response::success( $data, 'Activity retrieved' );
}
```

---

## Route Migration Map

| Legacy Route | New Route | Notes |
|--------------|-----------|-------|
| `apollo-events/v1/events` | `apollo/v1/events` | Main events list |
| `apollo-events/v1/featured` | `apollo/v1/events/featured` | Featured events |
| `apollo-events/v1/search` | `apollo/v1/events/search` | Event search |
| `apollo-events/v1/venues` | `apollo/v1/venues` | Venues list |
| `apollo-events/v1/artists` | `apollo/v1/djs` | DJs list |
| `apollo-core/v1/events` | `apollo/v1/events` | Duplicate removal |
| `apollo-core/v1/djs` | `apollo/v1/djs` | DJs list |
| `apollo-core/v1/user/profile` | `apollo/v1/users/me` | User profile |
| `apollo-core/v1/activity` | `apollo/v1/social/activity` | Activity feed |
| `apollo-core/v1/mod/reports` | `apollo/v1/mod/reports` | Moderation |

---

## Response Format Standard

### Success Response
```json
{
    "success": true,
    "code": "success",
    "data": { ... },
    "message": "Optional message",
    "meta": {
        "api_version": "1.0.0",
        "timestamp": "2024-01-15T10:30:00+00:00"
    }
}
```

### Error Response
```json
{
    "success": false,
    "code": "not_found",
    "message": "Event with ID 123 not found.",
    "details": { ... },
    "meta": {
        "api_version": "1.0.0",
        "timestamp": "2024-01-15T10:30:00+00:00"
    }
}
```

### Paginated Response
```json
{
    "success": true,
    "code": "success",
    "data": [ ... ],
    "pagination": {
        "total": 100,
        "count": 10,
        "per_page": 10,
        "current_page": 1,
        "total_pages": 10,
        "has_previous": false,
        "has_next": true,
        "next_page": 2
    },
    "meta": {
        "api_version": "1.0.0",
        "timestamp": "2024-01-15T10:30:00+00:00"
    }
}
```

---

## Deprecation Headers

Legacy endpoints return these headers:
- `X-Apollo-Deprecated: true`
- `X-Apollo-Deprecated-Route: apollo-events/v1/events`
- `X-Apollo-Replacement-Route: apollo/v1/events`
- `Deprecation: true; sunset="2025-12-31T23:59:59Z"`
- `Sunset: Sat, 31 Dec 2025 23:59:59 GMT`
- `Link: <https://site.com/wp-json/apollo/v1/events>; rel="successor-version"`

---

## Testing

### Discovery Endpoint
```bash
curl https://yoursite.com/wp-json/apollo/v1/discover
```

### Group Discovery
```bash
curl https://yoursite.com/wp-json/apollo/v1/discover/events
```

### Legacy Endpoint (should redirect)
```bash
curl -I https://yoursite.com/wp-json/apollo-events/v1/events
# Check for X-Apollo-Deprecated header
```

---

## Loading Order

In `apollo-core.php`:

```php
// REST API files - load in order
require_once __DIR__ . '/includes/rest-api/class-apollo-rest-namespace.php';
require_once __DIR__ . '/includes/rest-api/class-apollo-rest-registry.php';
require_once __DIR__ . '/includes/rest-api/class-apollo-rest-response.php';
require_once __DIR__ . '/includes/rest-api/class-apollo-rest-compat.php';
```

---

## Frontend JavaScript Updates

### Before
```javascript
// Multiple namespaces
fetch('/wp-json/apollo-events/v1/events');
fetch('/wp-json/apollo-core/v1/user/profile');
fetch('/wp-json/apollo/v1/social/activity');
```

### After
```javascript
// Unified namespace
const API_BASE = '/wp-json/apollo/v1';

fetch(`${API_BASE}/events`);
fetch(`${API_BASE}/users/me`);
fetch(`${API_BASE}/social/activity`);
```

---

## Checklist

- [x] Create unified namespace constants
- [x] Create central route registry
- [x] Create backward compatibility layer
- [x] Create unified response format
- [ ] Update apollo-events-manager routes
- [ ] Update apollo-core routes
- [ ] Update apollo-social to use response format
- [ ] Update frontend JavaScript
- [ ] Remove legacy route registrations after sunset date

---

## Next Steps

1. Add these files to apollo-core autoloader
2. Test legacy endpoint redirects
3. Update each plugin's REST controllers
4. Monitor deprecation logs
5. Update frontend API calls
6. Remove legacy routes after 2025-12-31
