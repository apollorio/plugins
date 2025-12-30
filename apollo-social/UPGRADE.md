# Apollo Social - UPGRADE.md

## Upgrade Guide: v2.2.x → v2.3.0

This document provides step-by-step instructions for upgrading Apollo Social.

---

## Pre-Upgrade Checklist

- [ ] **Backup database** before any upgrade
- [ ] **Test in staging** environment first
- [ ] Ensure PHP 8.1+ is installed
- [ ] Ensure WordPress 6.0+ is active
- [ ] Disable cache plugins temporarily
- [ ] Note down current feature flags state

---

## Automatic Upgrade

Apollo Social handles most upgrades automatically:

1. **Deactivate** the plugin (Dashboard → Plugins)
2. **Upload** the new version (or update via ZIP)
3. **Reactivate** the plugin
4. The `plugins_loaded` hook will:
   - Check schema version (`apollo_schema_version` option)
   - Run any pending migrations automatically
   - Update rewrite rules via Router

---

## Manual WP-CLI Upgrade (Recommended for Production)

### Step 1: Check Current Status

```bash
# Check schema status
wp apollo schema status

# Check feature flags
wp apollo diag flags

# Check registered routes
wp apollo diag routes
```

### Step 2: Run Schema Upgrade

```bash
# Force upgrade if needed
wp apollo schema upgrade

# Verify tables
wp apollo schema status
```

### Step 3: Documents Reconciliation

```bash
# Audit documents (no changes)
wp apollo dms audit

# Reconcile CPT ↔ Table (dry-run first)
wp apollo dms reconcile --dry-run

# Execute reconciliation
wp apollo dms reconcile --fix
```

### Step 4: Flush Rewrite Rules

```bash
# Flush rules
wp rewrite flush
```

### Step 5: Verify Upgrade

```bash
# Full diagnostics
wp apollo diag status
```

---

## Schema Versioning

| Version | Changes |
|---------|---------|
| **2.3.0** | Schema facade, module interfaces, CLI commands |
| **2.2.0** | CoreSchema, ChatSchema, LikesSchema introduced |
| **2.1.0** | Signatures post_id column, backfill migration |
| **2.0.0** | Initial modular schema |

### How Schema Upgrades Work

1. On plugin load, `\Apollo\Schema::needsUpgrade()` checks:
   ```php
   $stored = get_option('apollo_schema_version', '0.0.0');
   return version_compare($stored, '2.3.0', '<');
   ```

2. If upgrade needed, `\Apollo\Schema::upgrade()` runs:
   - Core migrations via `CoreSchema::upgrade()`
   - Module migrations via `{Module}Schema::upgrade()`
   - Updates `apollo_schema_version` option

3. All migrations use `dbDelta()` - idempotent and safe to re-run.

---

## Feature Flags

### Default States (v2.3.0)

| Feature | Default | Notes |
|---------|---------|-------|
| `documents` | ✅ ON | Stable |
| `signatures` | ✅ ON | Stable |
| `classifieds` | ✅ ON | Stable |
| `user_pages` | ✅ ON | Stable |
| `builder` | ✅ ON | Stable |
| `feed` | ✅ ON | Stable |
| `reactions` | ✅ ON | Stable |
| `analytics` | ✅ ON | Optional |
| `chat` | ❌ OFF | Incomplete UI |
| `notifications` | ❌ OFF | Not implemented |
| `groups` | ❌ OFF | Stub |
| `govbr` | ❌ OFF | Not implemented |
| `pwa` | ❌ OFF | Incomplete |

### Managing Feature Flags

```bash
# View all flags
wp apollo diag flags

# Enable a feature
wp apollo diag toggle chat --enable

# Disable a feature
wp apollo diag toggle chat --disable
```

Or via Admin UI: **Apollo Social → Diagnósticos**

### Fail-Closed Behavior

If `FeatureFlags::init()` is not called (bootstrap failure), all features default to **OFF** as a safety measure.

---

## Rewrite Rules

### What Changed

- **Before**: Each module called `flush_rewrite_rules()` independently
- **After**: Single flush via `Apollo_Router::onActivation()` only

### Upgrading Rewrite Rules

The Router uses version-based flushing:

```php
const RULES_VERSION = '2.1.0';
```

When `apollo_rewrite_version` option differs from code version, rules are flushed automatically (admin only).

### Manual Flush

```bash
wp rewrite flush
```

Or via Admin: **Apollo Social → Diagnósticos → Flush Rewrite Rules**

---

## Documents Module

### Source of Truth

- **CPT `apollo_document`** is the authoritative source
- **Table `wp_apollo_documents`** is an index/cache

### Migration Commands

```bash
# Run table migrations
wp apollo dms migrate

# Verify CPT ↔ Table consistency
wp apollo dms audit

# Fix divergences
wp apollo dms reconcile --fix

# Show statistics
wp apollo dms stats
```

### Signature Metakey Migration

Legacy signatures stored in `_apollo_document_signatures` are automatically migrated to `_apollo_doc_signatures` on first read.

---

## Breaking Changes

### v2.3.0

**None** - This is a backward-compatible release.

Deprecated (still functional):
- `\Apollo\Infrastructure\Database\Schema` → Use `\Apollo\Schema`

### v2.2.0

**None** - Schema changes are additive.

---

## Rollback Procedure

### Safe Rollback (Data Preserved)

1. Deactivate Apollo Social
2. Restore previous plugin version
3. Reactivate plugin
4. Run: `wp rewrite flush`

### Database Rollback (If Needed)

```bash
# Restore from backup
wp db import backup-before-upgrade.sql

# Or drop new tables manually (DANGEROUS)
wp db query "DROP TABLE IF EXISTS wp_apollo_new_table_name;"
```

---

## Troubleshooting

### Tables Not Created

```bash
# Check status
wp apollo schema status

# Force install
wp apollo schema install --yes
```

### Routes Not Working

```bash
# Flush rules
wp rewrite flush

# Check Router version
wp option get apollo_rewrite_version

# Force update
wp option update apollo_rewrite_version ""
wp eval "do_action('init');"
```

### Feature Not Loading

```bash
# Check if feature is enabled
wp apollo diag toggle feature_name

# Check if FeatureFlags initialized
wp eval "echo Apollo\\Infrastructure\\FeatureFlags::isInitialized() ? 'YES' : 'NO';"
```

### Schema Version Mismatch

```bash
# Check stored version
wp option get apollo_schema_version

# Reset and reinstall (CAUTION)
wp option delete apollo_schema_version
wp apollo schema install --yes
```

---

## Post-Upgrade Verification

```bash
# Full system check
wp apollo diag status

# Expected output:
# ✓ Schema version: 2.3.0
# ✓ All tables present
# ✓ FeatureFlags initialized
# ✓ Router rules version: 2.1.0
```

---

## Support

- **Issues**: GitHub Issues
- **Documentation**: `/docs/` folder
- **WP-CLI**: `wp apollo --help`

---

*Last updated: 2025-12-30*
