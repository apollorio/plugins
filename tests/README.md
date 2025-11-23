# Apollo Plugin Test Scripts

## Security Notice

**P0-2**: These test scripts are secured and should only be run in development environments or via WP-CLI.

## Usage

### Via WP-CLI (Recommended)

```bash
# Database tests
wp eval-file tests/APOLLO-DATABASE-TEST.php

# XDebug tests
wp eval-file tests/APOLLO-XDEBUG-TEST.php
```

### Via Web (Development Only)

Requires:
- `APOLLO_DEBUG` constant set to `true` in `wp-config.php`
- Authenticated admin user
- Direct URL access (not recommended for production)

## Security Features

- ✅ Authentication check (admin only)
- ✅ Debug mode requirement
- ✅ WP-CLI context detection
- ✅ Safe error handling (JSON responses)
- ✅ Prepared statements for all DB queries
- ✅ `.htaccess` protection layer

## Files

- `APOLLO-DATABASE-TEST.php` - Database integrity and performance tests
- `APOLLO-XDEBUG-TEST.php` - XDebug-enabled comprehensive test suite
- `.htaccess` - Web access protection
- `README.md` - This file

## Migration Notes

These scripts were moved from the plugin root directory to `tests/` as part of P0-2 security improvements.

