# Checklist de Deploy - Fases 2-3

Data: 30/12/2025
Status: 🟢 READY FOR PRODUCTION

---

## ✅ Verificações Pré-Deploy (Executar antes de merge)

- [ ] **PHP Lint** - Todos os arquivos modificados passam em `php -l`
  ```bash
  php -l src/Infrastructure/Database/Migrations.php
  php -l src/Modules/Groups/GroupsBusinessRules.php
  php -l src/Api/RestSecurity.php
  php -l src/Modules/Groups/GroupsModule.php
  php -l src/Api/AjaxHandlers.php
  ```

- [ ] **Database Backup** - Criar dump de wp_apollo_groups e wp_apollo_group_members
  ```bash
  mysqldump -u[user] -p[pass] [db] wp_apollo_groups > backup_groups.sql
  ```

- [ ] **Feature Flags Check**
  ```php
  // Verify defaults in FeatureFlags.php
  'groups_api'        => true,   // Must be enabled
  'groups_api_legacy' => false,  // Disabled (optional)
  ```

- [ ] **No Runtime Flush**
  ```bash
  grep -r "maybeFlush\|add_action.*flush_rewrite" src/Infrastructure/Http/Apollo_Router.php
  # Should return NO matches (removed in Phase 0)
  ```

---

## 🚀 Deployment Steps

### 1. Pre-Activation (Local/Staging)

```bash
# Clone/pull latest code
git pull origin main

# Run unit tests (if applicable)
wp plugin test apollo-social

# Verify REST endpoints exist
curl -H "X-WP-Nonce: $(wp eval 'echo wp_create_nonce("apollo_rest_nonce");')" \
     http://staging.local/wp-json/apollo/v1/comunas

# Test migration in dry-run mode (when implemented)
wp apollo schema status
```

### 2. Activation

```bash
# Via WP-CLI (recommended for large installs)
wp plugin activate apollo-social

# Monitor activation hook
tail -f /var/log/php-errors.log
# Should see: "✅ Apollo: Migration 2.2.0 completed"
```

### 3. Post-Activation Verification

```bash
# Check migration version
wp eval 'echo get_option("apollo_migration_version");'
# Expected: "2.2.0"

# Verify group_type column exists
wp db query "DESCRIBE wp_apollo_groups" | grep group_type
# Expected: "group_type | enum('comuna','nucleo','season')"

# Test API endpoints
curl -s http://localhost/wp-json/apollo/v1/comunas | jq .
curl -s http://localhost/wp-json/apollo/v1/nucleos | jq .

# Verify nonce validation (should fail without nonce)
curl -X POST http://localhost/wp-json/apollo/v1/comunas/create
# Expected: 403 "Invalid nonce"

# Test rate limiting (make 11 join requests in < 1 hour)
for i in {1..11}; do
  curl -X POST \
    -H "X-WP-Nonce: $(nonce)" \
    http://localhost/wp-json/apollo/v1/comunas/1/join
  # Request 11 should return 429
done
```

### 4. Client Migration

**Alert all API consumers:**

```
⚠️ Apollo Social API Update

Deprecated:
- GET  /apollo/v1/groups
- POST /apollo/v1/groups/create
- POST /apollo/v1/groups/{id}/join

New endpoints:
- GET  /apollo/v1/comunas        → Public communities
- POST /apollo/v1/comunas/create
- POST /apollo/v1/comunas/{id}/join

- GET  /apollo/v1/nucleos        → Private teams (auth required)
- POST /apollo/v1/nucleos/create (needs apollo_create_nucleo cap)
- POST /apollo/v1/nucleos/{id}/join (pending approval)

All POST/PUT/PATCH/DELETE require:
  Header: X-WP-Nonce: <nonce from wp_create_nonce('apollo_rest_nonce')>

Status Codes:
  - 401: Not authenticated
  - 403: Invalid nonce / No permission
  - 429: Rate limited (wait 1 hour)

Migration deadline: 2025-01-30
See: /wp-content/plugins/apollo-social/API-USAGE-GUIDE.md
```

---

## 📊 Health Check Commands

### WordPress CLI

```bash
# Overall status
wp apollo schema status

# Check migrations
wp option get apollo_migration_version

# List groups by type
wp db query "SELECT id, name, group_type FROM wp_apollo_groups LIMIT 5"

# Test creation (as admin)
wp eval '
$gs = new Apollo\Modules\Groups\GroupsBusinessRules();
echo $gs->validateType("comuna") ? "✓ Valid" : "✗ Invalid";
'
```

### API Tests

```bash
#!/bin/bash

API="http://localhost/wp-json/apollo/v1"
NONCE=$(wp eval 'echo wp_create_nonce("apollo_rest_nonce");')

# Test 1: List comunas (public, no auth)
echo "Test 1: List comunas..."
curl -s "$API/comunas?limit=5" | jq .

# Test 2: Nonce validation
echo "Test 2: Nonce validation (should fail)..."
curl -s -X POST "$API/comunas/create" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test"}' | jq .

# Test 3: Create with nonce
echo "Test 3: Create with nonce (should succeed)..."
curl -s -X POST "$API/comunas/create" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: $NONCE" \
  -d '{"name":"Test Community","description":"Testing"}' | jq .

# Test 4: Rate limit
echo "Test 4: Rate limit (11 joins)..."
for i in {1..11}; do
  curl -s -X POST "$API/comunas/1/join" \
    -H "X-WP-Nonce: $NONCE" | jq -r '.code' | grep -q '429' && echo "✓ Rate limit hit on attempt $i"
done
```

---

## ⚠️ Rollback Plan

### If Migration Fails

```bash
# 1. Revert plugin to previous version
git revert HEAD

# 2. Deactivate plugin
wp plugin deactivate apollo-social

# 3. Restore database
mysql [db] < backup_groups.sql

# 4. Re-activate old version
wp plugin activate apollo-social
```

### If Feature Breaks

```bash
# Disable groups_api temporarily
wp option set apollo_feature_flags '{"groups_api":false}'

# Restore after fix
wp option set apollo_feature_flags '{"groups_api":true}'
```

---

## 🔍 Monitoring (24h post-deploy)

### Log Patterns to Watch For

```bash
# Monitor for errors
tail -f /var/log/apache2/error.log | grep apollo

# Expected logs (good):
✅ "Migration 2.2.0 completed"
✅ "FeatureFlags: groups_api enabled"

# Red flags (bad):
❌ "SQLSTATE[42S21]: Column already exists"
❌ "Undefined index: group_type"
❌ "RestSecurity: Invalid nonce"
```

### Performance Metrics

```bash
# Check index usage
wp db query "SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME='wp_apollo_groups'"

# Expected: group_type_idx should be present and used
```

---

## 📋 Go-Live Checklist

- [ ] All tests passed locally/staging
- [ ] Database backed up
- [ ] API consumers notified
- [ ] Documentation updated (PHASE-2-3-IMPLEMENTATION.md, API-USAGE-GUIDE.md)
- [ ] Feature flags verified
- [ ] Migration tested with actual data
- [ ] Rate limiting verified (10+1 = fail)
- [ ] Nonce validation verified (POST without nonce = 403)
- [ ] Legacy /groups endpoint shows deprecation headers
- [ ] Admin notified (email with rollback procedure)
- [ ] Post-deploy monitoring configured
- [ ] Incident response team briefed

---

## 📞 Support Contacts

- **Tech Lead**: [contact]
- **Admin**: [contact]
- **Incident Response**: [contact]

---

## 📝 Notes

- **No data loss**: Migration is non-destructive, uses ALTER TABLE
- **Backward compatible**: Legacy /groups still works (deprecated)
- **Safe to re-run**: Migration is idempotent
- **No downtime needed**: Deployment can happen during business hours

---

**Status**: 🟢 APPROVED FOR PRODUCTION
**Last Updated**: 30/12/2025
**Deployed By**: [User]
**Deployment Time**: [Timestamp]
