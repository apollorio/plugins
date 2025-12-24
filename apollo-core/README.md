# Apollo Core

Core plugin for Apollo ecosystem - unifies Events Manager and Social features into a modular architecture.

## Features

- **Modular Architecture**: Auto-loading modules from `modules/` directory
- **Custom Roles**: `apollo`, `cena-rio`, `dj` roles with specific capabilities
- **Canvas Mode**: Isolated template rendering for plugin pages
- **REST API**: Unified `/apollo/v1` namespace for all endpoints
- **Migration System**: Automated migration from old plugins
- **Audit Logging**: Track all mod actions in `wp_apollo_mod_log` table

## Installation

1. Upload the `apollo-core` directory to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Run migration if upgrading from older plugins

## Modules

### Events Module
- CPTs: `event_listing`, `event_dj`, `event_local`
- REST endpoints: `/apollo/v1/events`, `/apollo/v1/events/{id}`

### Social Module
- CPTs: `apollo_social_post`, `user_page`
- REST endpoints: `/apollo/v1/feed`, `/apollo/v1/posts`, `/apollo/v1/like`

## Development

### Requirements
- PHP 8.1+
- WordPress 6.0+
- Composer (for dependencies)

### Running Tests

```bash
# Set up WordPress test environment
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest

# Run PHPUnit tests
vendor/bin/phpunit
```

### Adding a New Module

1. Create directory: `modules/your-module/`
2. Create bootstrap file: `modules/your-module/bootstrap.php`
3. Register CPTs and REST routes in bootstrap file
4. Module will auto-load on plugin init

## REST API

### Authentication
All POST/PUT/DELETE endpoints require authentication. Include nonce in header:

```
X-WP-Nonce: <nonce_value>
```

### Endpoints

**Health Check**
```
GET /wp-json/apollo/v1/health
```

**Events**
```
GET /wp-json/apollo/v1/events
POST /wp-json/apollo/v1/events
GET /wp-json/apollo/v1/events/{id}
```

**Feed**
```
GET /wp-json/apollo/v1/feed
```

**Like**
```
POST /wp-json/apollo/v1/like
```

## Permissions

Use helper functions for permission checks:

```php
Apollo_Core_Permissions::can_approve_events();
Apollo_Core_Permissions::can_access_cena_rio();
Apollo_Core_Permissions::can_sign_documents();
Apollo_Core_Permissions::can_manage_lists();
```

## Migration

To migrate from older plugins:

1. Ensure old plugins are active
2. Go to **Apollo Core → Migration**
3. Click "Run Migration"
4. Verify results
5. Deactivate old plugins

Rollback is available if issues occur.

## License

GPL v2 or later

## Support

https://apollo.rio.br

