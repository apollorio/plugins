# Apollo Core Developer Documentation

> Technical documentation for developers extending or integrating with Apollo Core.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Module System](#module-system)
3. [REST API](#rest-api)
4. [Caching System](#caching-system)
5. [Asset Management](#asset-management)
6. [Security](#security)
7. [Hooks Reference](#hooks-reference)

---

## Architecture Overview

Apollo Core follows a modular architecture with clear separation of concerns:

```
apollo-core/
├── apollo-core.php          # Main plugin file
├── includes/
│   ├── class-apollo-core.php           # Core class
│   ├── class-autoloader.php            # PSR-4 autoloader
│   ├── class-module-loader.php         # Module discovery
│   │
│   ├── rest-api/
│   │   ├── class-apollo-rest-controller.php  # Base controller
│   │   └── class-rest-api-loader.php         # Route registration
│   │
│   ├── class-apollo-query-cache.php    # Query caching
│   ├── class-apollo-asset-optimizer.php # Asset optimization
│   └── class-apollo-code-cleanup.php   # Development utilities
│
├── modules/
│   ├── events/
│   │   └── bootstrap.php               # Events module
│   └── social/
│       └── bootstrap.php               # Social module
│
├── assets/
│   ├── core/                           # Design system
│   ├── vendor/                         # Third-party libs
│   ├── css/                            # Plugin styles
│   └── js/                             # Plugin scripts
│
└── templates/                          # Template files
```

### Key Components

| Component | Purpose |
|-----------|---------|
| `Apollo_Core` | Main plugin class, initialization |
| `Module_Loader` | Auto-discovers and loads modules |
| `Apollo_REST_Controller` | Base class for all REST endpoints |
| `Query_Cache` | Transient-based query caching |
| `Asset_Optimizer` | Conditional asset loading |

---

## Module System

### Creating a Module

1. Create directory: `modules/your-module/`
2. Create `bootstrap.php`:

```php
<?php
/**
 * Your Module
 *
 * @package Apollo_Core\Modules\YourModule
 */

declare(strict_types=1);

namespace Apollo_Core\Modules\YourModule;

// Only load if Apollo Core is active.
if ( ! defined( 'APOLLO_CORE_BOOTSTRAPPED' ) ) {
    return;
}

/**
 * Initialize the module.
 */
function init(): void {
    add_action( 'init', __NAMESPACE__ . '\\register_post_types' );
    add_action( 'rest_api_init', __NAMESPACE__ . '\\register_routes' );
}

function register_post_types(): void {
    register_post_type( 'your_cpt', [
        'public' => true,
        'label'  => 'Your CPT',
        // ...
    ]);
}

function register_routes(): void {
    // Register REST routes
}

// Auto-initialize.
init();
```

### Module Loading Priority

Modules are loaded in alphabetical order. Use numbered prefixes for explicit ordering:

```
modules/
├── 01-core/
├── 02-events/
└── 03-social/
```

---

## REST API

### Base Controller

All Apollo REST endpoints should extend `Apollo_REST_Controller`:

```php
<?php

declare(strict_types=1);

namespace Your_Plugin\REST_API;

use Apollo_Core\REST_API\Apollo_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

class Your_Controller extends Apollo_REST_Controller {

    protected $namespace = 'apollo/v1';
    protected $rest_base = 'your-endpoint';

    public function register_routes(): void {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_items' ],
                'permission_callback' => [ $this, 'check_read_permission' ],
            ]
        );
    }

    public function get_items( WP_REST_Request $request ): WP_REST_Response {
        $data = $this->fetch_your_data();

        // Use standardized response format
        return $this->success( $data, 'Items retrieved successfully' );
    }
}
```

### Response Format

All endpoints use a standardized JSON format:

```json
{
    "success": true,
    "data": { ... },
    "message": "Operation successful"
}
```

Error responses:

```json
{
    "success": false,
    "code": "error_code",
    "message": "Error description",
    "data": null
}
```

### Available Methods

| Method | Purpose |
|--------|---------|
| `success($data, $message, $status)` | Return success response |
| `error($code, $message, $status, $data)` | Return WP_Error |
| `paginated_response($items, $total, $page, $per_page, $request)` | Paginated response with headers |
| `check_read_permission()` | Returns true (public) |
| `check_create_permission()` | Requires login |
| `check_update_permission($request)` | Requires login + edit_post |
| `check_delete_permission($request)` | Requires login + delete_post |

---

## Caching System

### Query Cache

Use `Query_Cache` for expensive database queries:

```php
use Apollo_Core\Query_Cache;

// Initialize (called by Apollo Core)
Query_Cache::init();

// Cache a custom query
$results = Query_Cache::remember(
    'my_unique_key',
    function() {
        return expensive_database_query();
    },
    'events',        // Cache group
    HOUR_IN_SECONDS  // TTL
);

// Get cached events
$query = Query_Cache::get_events([
    'posts_per_page' => 10,
    'meta_key'       => '_event_date',
    'orderby'        => 'meta_value',
    'order'          => 'ASC',
]);

// Manually invalidate
Query_Cache::invalidate_group( 'events' );
```

### Cache Groups

| Group | Post Types |
|-------|------------|
| `events` | event_listing, event_dj, event_local |
| `classifieds` | advert |
| `social` | activity, profile |
| `users` | user objects |
| `general` | everything else |

### Automatic Invalidation

Cache is automatically invalidated when:
- A post is saved/updated/deleted
- Post status changes to/from 'publish'
- User profile is updated
- Terms are edited

---

## Asset Management

### Asset Optimizer

```php
use Apollo_Core\Asset_Optimizer;

// Initialize (called by Apollo Core)
Asset_Optimizer::init();

// Get critical CSS for inlining
$css = Asset_Optimizer::get_critical_css();

// Inline in head
Asset_Optimizer::inline_critical_css();

// Get stats
$stats = Asset_Optimizer::get_stats();
// Returns: ['deferred_scripts' => 5, 'async_scripts' => 2, ...]
```

### Adding to Deferred Scripts

Modify the static arrays in `Asset_Optimizer`:

```php
private static array $defer_scripts = [
    'apollo-vendor-leaflet',
    'apollo-vendor-sortable',
    'your-heavy-script',  // Add here
];
```

### Page-Specific Assets

Assets are loaded based on page context:

```php
private static array $page_assets = [
    'events' => [
        'styles'  => ['apollo-events-calendar'],
        'scripts' => ['apollo-events-manager'],
    ],
    'your-context' => [
        'styles'  => ['your-styles'],
        'scripts' => ['your-scripts'],
    ],
];
```

---

## Security

### Permission Callbacks

Always use permission callbacks on REST routes:

```php
// Public read
'permission_callback' => '__return_true'

// Requires login
'permission_callback' => 'is_user_logged_in'

// Uses base controller methods
'permission_callback' => [ $this, 'check_create_permission' ]

// Custom capability
'permission_callback' => function() {
    return current_user_can( 'apollo_manage_events' );
}
```

### Nonce Verification

For forms and AJAX:

```php
// In template
wp_nonce_field( 'apollo_action', 'apollo_nonce' );

// In handler
if ( ! wp_verify_nonce( $_POST['apollo_nonce'], 'apollo_action' ) ) {
    wp_die( 'Security check failed' );
}
```

For REST API, nonce is passed via `X-WP-Nonce` header.

### Input Sanitization

Use base controller sanitization methods:

```php
$title   = $this->sanitize_text( $request['title'] );
$content = $this->sanitize_html( $request['content'] );
$count   = $this->sanitize_int( $request['count'] );
$price   = $this->sanitize_float( $request['price'] );
$active  = $this->sanitize_bool( $request['active'] );
$ids     = $this->sanitize_int_array( $request['ids'] );
$date    = $this->sanitize_date( $request['date'] );
```

---

## Hooks Reference

### Actions

```php
// After cache group invalidated
do_action( 'apollo_cache_group_invalidated', $group );

// After full cache clear
do_action( 'apollo_cache_cleared' );

// After query cached
do_action( 'apollo_query_cache_set', $result, $key, $group );
```

### Filters

```php
// Modify cached data on retrieval
add_filter( 'apollo_query_cache_hit', 'my_function', 10, 3 );

// Modify critical CSS
add_filter( 'apollo_critical_css', 'my_function' );
```

---

## Testing

### Running Tests

```bash
cd apollo-core
composer install
./vendor/bin/phpunit
```

### Code Standards

```bash
composer run phpcs
composer run phpcbf  # Auto-fix
```

### Generating Cleanup Report

```php
use Apollo_Core\Code_Cleanup;

$report = Code_Cleanup::generate_report( APOLLO_CORE_PLUGIN_DIR );
echo Code_Cleanup::format_report_text( $report );
```

---

## Version Compatibility

| Apollo Core | WordPress | PHP |
|-------------|-----------|-----|
| 2.0.x | 6.4+ | 8.1+ |
| 1.9.x | 6.0+ | 8.0+ |
| 1.x | 5.9+ | 7.4+ |

---

**Last updated:** January 3, 2026
**Version:** 2.0.0
