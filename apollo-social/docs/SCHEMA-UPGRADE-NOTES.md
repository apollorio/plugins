# Apollo Social - Schema System Upgrade Notes

## Version 2.2.0 - Schema Architecture Overhaul

### Breaking Changes

**None** - The upgrade is backward compatible. Old code calling deprecated classes will still work.

---

### Major Changes

#### 1. Single Entry Point: `\Apollo\Schema`

**Before:**
```php
// Multiple entry points, confusing
use Apollo\Infrastructure\Database\Schema;
$schema = new Schema();
$schema->install();
```

**After:**
```php
// Single authoritative facade
use Apollo\Schema;
$schema = new \Apollo\Schema();
$schema->install();   // All tables
$schema->upgrade();   // Version-gated migrations
$schema->getStatus(); // Table status report
```

#### 2. Module Interface Contract

All module schemas now implement `\Apollo\Contracts\SchemaModuleInterface`:

```php
interface SchemaModuleInterface {
    public function install();                           // Create tables
    public function upgrade(string $from, string $to);   // Run migrations
    public function getStatus(): array;                  // Table status
    public function uninstall(): void;                   // Drop tables
}
```

#### 3. Deprecated Files

| File | Status | Replacement |
|------|--------|-------------|
| `src/Infrastructure/Database/Schema.php` | **@deprecated 2.2.0** | `src/Schema.php` |
| `src/Modules/Documents/DocumentsSchemaNew.php` | **REMOVED** | `DocumentsSchema.php` |

---

### Migration Guide

#### For Plugin Activation

The main plugin file (`apollo-social.php`) now uses:

```php
register_activation_hook( __FILE__, function () {
    $schema = new \Apollo\Schema();
    $schema->install();
});
```

#### For Upgrades

Automatic on `plugins_loaded`:

```php
add_action( 'plugins_loaded', function () {
    $schema = new \Apollo\Schema();
    if ( $schema->needsUpgrade() ) {
        $schema->upgrade();
    }
}, 5 );
```

#### Adding New Module Schemas

1. Create a class implementing `SchemaModuleInterface`
2. Register via filter:

```php
add_filter( 'apollo_schema_modules', function ( $modules ) {
    $modules['my_module'] = new \My\Module\MySchema();
    return $modules;
});
```

---

### WP-CLI Commands

```bash
# Check installation status
wp apollo schema status

# Install all tables
wp apollo schema install --yes

# Run pending upgrades
wp apollo schema upgrade

# Show version info
wp apollo schema version

# Reset database (DESTRUCTIVE)
wp apollo schema reset --yes
```

---

### Database Tables

#### Core Tables (via `CoreSchema`)
| Table | Purpose |
|-------|---------|
| `wp_apollo_groups` | User groups |
| `wp_apollo_group_members` | Group membership |
| `wp_apollo_workflow_log` | Workflow audit trail |
| `wp_apollo_mod_queue` | Moderation queue |
| `wp_apollo_analytics` | Event tracking |
| `wp_apollo_signature_requests` | Signature request tracking |
| `wp_apollo_onboarding_progress` | User onboarding state |
| `wp_apollo_verification_tokens` | Email/action tokens |

#### Documents Module (via `DocumentsSchema`)
| Table | Purpose |
|-------|---------|
| `wp_apollo_documents` | Document index/cache (CPT is source of truth) |
| `wp_apollo_document_signatures` | Digital signature records |

#### Chat Module (via `ChatSchema`)
| Table | Purpose |
|-------|---------|
| `wp_apollo_chat_conversations` | Conversation threads |
| `wp_apollo_chat_messages` | Chat messages |
| `wp_apollo_chat_participants` | Conversation participants |

#### Likes Module (via `LikesSchema`)
| Table | Purpose |
|-------|---------|
| `wp_apollo_likes` | Like/reaction records |

---

### Version Option

- **Option name:** `apollo_schema_version`
- **Current version:** `2.2.0`
- **Check:** `get_option('apollo_schema_version', '0.0.0')`

---

### Troubleshooting

#### Tables Not Created

1. Run `wp apollo schema status` to check
2. Run `wp apollo schema install --yes`
3. Check PHP error log for exceptions

#### Migration Failed

1. Check `wp_options` for `apollo_schema_version`
2. Manually update version if needed: `wp option update apollo_schema_version 2.1.0`
3. Re-run `wp apollo schema upgrade`

#### Duplicate Columns Error

The schema uses `dbDelta()` which is idempotent. If you see duplicate column errors:
1. Clear any cached schema
2. The column already exists - this is safe to ignore

---

## Changelog

### 2.2.0
- Introduced `\Apollo\Schema` as single entry point facade
- Created `SchemaModuleInterface` contract
- Modularized schemas: DocumentsSchema, ChatSchema, LikesSchema
- Added `CoreSchema` for base tables
- Added WP-CLI commands (`wp apollo schema`)
- Deprecated `\Apollo\Infrastructure\Database\Schema`
- Removed duplicate `DocumentsSchemaNew.php`

### 2.1.0
- Added `post_id` column to signatures table
- Added signature backfill migration
- Enhanced indices for signatures table
