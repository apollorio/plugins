# Deployment Runbook - Apollo Social Plugin

**Version**: 2.3.0
**Date**: 30 de Dezembro de 2025
**Status**: READY FOR PRODUCTION

---

## 📋 Quick Reference

- **Branch**: `hotfix/comuna-nucleo-api`
- **Affected Modules**: Groups, REST API, Schema
- **DB Changes**: Non-destructive migrations (group_type column, indexes)
- **Downtime**: ~2-5 minutes (schema upgrade)
- **Rollback**: Revert plugin, no DB restore needed

---

## FASE 0: Pre-Deployment Checklist

### 1. Code Review Gate
- [ ] Security team reviewed `src/Api/RestSecurity.php` (nonce, capability, rate-limit)
- [ ] Backend team reviewed `src/Modules/Groups/GroupsBusinessRules.php` (type enforcement)
- [ ] DevOps reviewed `src/Infrastructure/Database/Migrations.php` (idempotency)
- [ ] All PHP files pass linting: `php -l src/**/*.php`

### 2. Automated Checks

```bash
# Run from plugin root directory
echo "=== LINT CHECK ==="
for file in src/**/*.php; do php -l "$file" || exit 1; done
echo "✅ All PHP files valid"

# Grep for dangerous patterns
echo ""
echo "=== PATTERN CHECK ==="
echo "Checking for flush_rewrite_rules in runtime..."
grep -r "flush_rewrite_rules" src/ --exclude-dir=CLI || echo "✅ No runtime flushes"

echo ""
echo "Checking for __return_true in write endpoints..."
grep -r "__return_true.*permission_callback" src/ || echo "✅ No overpermissive callbacks"

echo ""
echo "Checking for unguarded register_rest_route..."
grep -r "register_rest_route.*groups" src/ || echo "✅ No legacy /groups registration"
```

### 3. Database Backup

```bash
# CRITICAL: Always backup production database first
wp db export apollo-social-backup-$(date +%Y%m%d-%H%M%S).sql --include-post-data=false

# Verify backup exists and is valid
ls -lh apollo-social-backup-*.sql
wp db check
```

### 4. Feature Flags Verification

```bash
# Check current feature flag values
wp option get apollo_groups_api
wp option get apollo_groups_api_legacy
wp option get apollo_feed

# Expected output: groups_api = 1, groups_api_legacy = 0, feed = 1
```

### 5. Current Routes Inventory

```bash
# Get current route state
wp db query "SELECT option_name, option_value FROM wp_options WHERE option_name LIKE 'apollo_%' AND option_name NOT LIKE 'apollo_log%';"

# Should show: apollo_rewrite_version and apollo_migration_version
```

---

## FASE 1: Staging Deployment

### 1. Deploy Code

```bash
# Create feature branch (if not already done)
git checkout -b hotfix/comuna-nucleo-api origin/main

# Deploy to staging
git merge main
git push origin hotfix/comuna-nucleo-api

# SSH into staging server
ssh staging-server

# Navigate to plugin directory
cd /var/www/staging/wp-content/plugins/apollo-social

# Pull latest code
git pull origin hotfix/comuna-nucleo-api
```

### 2. Activation & Schema Upgrade

```bash
# Deactivate plugin (if active)
wp plugin deactivate apollo-social

# Reactivate to trigger schema upgrade
wp plugin activate apollo-social

# Expected logs:
# ✅ Apollo Social: Activation completed successfully (v2.3.0)
# ✅ Apollo: Migration 2.2.0 completed
# ✅ Apollo: Migration 2.3.0 completed

# Verify in error log
tail -n 20 wp-content/debug.log | grep "Apollo Social"
```

### 3. Schema Verification

```bash
# Check schema status
wp apollo schema:status

# Expected output:
# Stored Version:  2.3.0
# Current Version: 2.3.0
# Needs Upgrade:   NO
```

### 4. Database Integrity Check

```bash
# Check group_type column exists
wp db query "DESCRIBE wp_apollo_groups;" | grep group_type

# Should return: group_type | enum('comuna','nucleo','season') | NO | MUL | comuna

# Check indexes were created
wp db query "SHOW INDEXES FROM wp_apollo_groups WHERE Column_name IN ('owner_id', 'group_type');"

# Check unique key on group_members
wp db query "SHOW INDEXES FROM wp_apollo_group_members WHERE Column_name = 'group_id' AND Column_name = 'user_id';"
```

### 5. Functional Tests

```bash
# Test REST endpoints (comunas)
curl -s http://staging-server/wp-json/apollo/v1/comunas | head -50

# Test REST endpoints (nucleos)
curl -s -H "Authorization: Bearer $(wp eval 'echo wp_create_nonce("apollo_nonce");')" \
  http://staging-server/wp-json/apollo/v1/nucleos | head -50

# Test nonce validation (should return 403)
curl -s -X POST http://staging-server/wp-json/apollo/v1/comunas/create \
  -H "Content-Type: application/json" \
  | grep -q "401\|403" && echo "✅ Nonce validation works"

# Test rate limiting (send 11 requests in succession)
for i in {1..11}; do
  curl -s -X POST http://staging-server/wp-json/apollo/v1/comunas/{id}/join \
    -H "X-WP-Nonce: valid-nonce" \
    -H "Cookie: wordpress_logged_in=user-session"
done
# 11th request should return 429 (Too Many Requests)

# Test WordPress feed is unaffected
curl -s http://staging-server/?feed=rss2 | grep -q "rss" && echo "✅ WordPress feed intact"
curl -s http://staging-server/feed/atom | grep -q "atom" && echo "✅ WordPress Atom feed intact"
```

### 6. Performance Baseline

```bash
# Measure query time before migration
wp eval "
\$start = microtime(true);
\$groups = \$GLOBALS['wpdb']->get_results('SELECT * FROM wp_apollo_groups LIMIT 1000');
\$time = round((microtime(true) - \$start) * 1000, 2);
echo 'Query time: ' . \$time . 'ms';
"

# Record baseline: _______ms

# Load test migration
wp apollo groups:reconcile --dry-run

# Measure query time after indexes
wp eval "
\$start = microtime(true);
\$groups = \$GLOBALS['wpdb']->get_results('SELECT * FROM wp_apollo_groups WHERE group_type = \"nucleo\" LIMIT 1000');
\$time = round((microtime(true) - \$start) * 1000, 2);
echo 'Query time: ' . \$time . 'ms';
"

# Should be similar or faster (baseline: _______ms, after: _______ms)
```

### 7. Sign-off

- [ ] Code review passed
- [ ] Database backup verified
- [ ] Schema upgrade succeeded
- [ ] All endpoints responding correctly
- [ ] Rate limiting works
- [ ] WordPress feeds intact
- [ ] Performance acceptable

---

## FASE 2: Production Deployment

### 1. Pre-Deployment (30 minutes before)

```bash
# SSH into production
ssh production-server

# Create backup
wp db export apollo-social-backup-$(date +%Y%m%d-%H%M%S).sql --include-post-data=false

# Verify backup
ls -lh apollo-social-backup-*.sql
wp db check

# Record current version
wp plugin list | grep apollo-social
wp option get apollo_schema_version

# Put site in maintenance mode (optional but recommended)
wp maintenance-mode activate
```

### 2. Deploy Code

```bash
cd /var/www/production/wp-content/plugins/apollo-social

# Pull latest code
git fetch origin
git checkout hotfix/comuna-nucleo-api
git pull origin hotfix/comuna-nucleo-api

# Verify files changed
git diff HEAD~1 --name-only | head -20
```

### 3. Activation & Schema Upgrade

```bash
# Skip if plugin stays active during upgrade
# If downtime acceptable, do full deactivate/activate:

# Deactivate
wp plugin deactivate apollo-social

# Wait 10 seconds
sleep 10

# Reactivate (triggers schema upgrade)
wp plugin activate apollo-social

# Monitor activation
tail -f wp-content/debug.log &
# Let it run for 30 seconds, then Ctrl+C

# Verify success
wp apollo schema:status
```

### 4. Data Reconciliation (if needed)

```bash
# Dry-run first (no changes)
wp apollo groups:reconcile --dry-run

# Review output - should show:
# 📌 Found 0 groups with NULL/empty type
# ⚠️  Found 0 groups with invalid type
# 📊 Group Distribution (XXXX total):

# If any found, run with actual changes
wp apollo groups:reconcile

# Verify
wp apollo groups:reconcile --dry-run
# Should show: 📌 Found 0 groups...
```

### 5. Production Verification Tests

```bash
# Health check #1: Schema status
wp apollo schema:status | grep "Needs Upgrade: NO" || exit 1
echo "✅ Schema OK"

# Health check #2: REST endpoints
curl -s http://production-server/wp-json/apollo/v1/comunas \
  -H "User-Agent: monitoring-bot" \
  | grep -q "^\[" && echo "✅ Comunas endpoint OK"

# Health check #3: WordPress feeds
curl -s http://production-server/?feed=rss2 | grep -q "rss" && echo "✅ Feed OK"

# Health check #4: Nonce validation
curl -s -X POST http://production-server/wp-json/apollo/v1/comunas/create \
  | grep -q "403\|401\|400" && echo "✅ Auth validation OK"

# Health check #5: Rate limiting
# (send 11 requests)
for i in {1..11}; do
  STATUS=$(curl -s -w "%{http_code}" -o /dev/null \
    -X POST http://production-server/wp-json/apollo/v1/comunas/1/join \
    -H "X-WP-Nonce: valid-nonce" \
    -H "Cookie: wordpress_logged_in=session-id")
done
[[ "$STATUS" == "429" ]] && echo "✅ Rate limiting OK" || echo "⚠️ Check rate limiting"
```

### 6. Exit Maintenance Mode

```bash
# Deactivate maintenance mode
wp maintenance-mode deactivate

# Verify site is live
curl -s http://production-server | grep -q "<html" && echo "✅ Site live"
```

### 7. Post-Deployment Monitoring (24 hours)

```bash
# Monitor error logs
tail -n 100 wp-content/debug.log | grep "Apollo"

# Monitor database queries (if query monitor active)
wp qm data

# Check for nonce validation errors
wp db query "SELECT * FROM wp_options WHERE option_name = 'apollo_log_errors' LIMIT 5;"

# Check rate limiting transients
wp transient list | grep -i "rate_limit"

# Monitor performance
wp cli cache clear
wp eval "
global \$wpdb;
\$start = microtime(true);
\$result = \$wpdb->get_results('SELECT COUNT(*) FROM wp_apollo_groups');
\$time = round((microtime(true) - \$start) * 1000, 2);
echo 'Groups query: ' . \$time . 'ms';
"
```

---

## ROLLBACK PROCEDURE

### If Schema Upgrade Failed

```bash
# Check error log
tail -n 50 wp-content/debug.log | grep -i "error\|fail"

# Check database integrity
wp db check

# If corrupted, restore from backup
# IMPORTANT: Only do this if migration failed and incomplete

# Deactivate plugin
wp plugin deactivate apollo-social

# Restore backup
# mysql -u user -p database_name < apollo-social-backup-YYYYMMDD-HHMMSS.sql

# Reactivate
wp plugin activate apollo-social

# Check status
wp apollo schema:status
```

### If REST Endpoints Broken

```bash
# Check nonce-related errors
wp db query "SELECT option_value FROM wp_options WHERE option_name = 'apollo_nonce_errors';"

# Clear feature flag cache
wp transient delete apollo_feature_flags

# Verify feature flags
wp option update apollo_groups_api 1
wp option update apollo_groups_api_legacy 0

# Reactivate plugin
wp plugin deactivate apollo-social
wp plugin activate apollo-social

# Test again
curl -s http://production-server/wp-json/apollo/v1/comunas | head -20
```

### If Rate Limiting Too Aggressive

```bash
# Check rate limit transients
wp transient list | grep "rate_limit"

# Clear rate limit transients
wp eval "
global \$wpdb;
\$wpdb->query('DELETE FROM ' . \$wpdb->prefix . 'options WHERE option_name LIKE \"%transient%rate_limit%\"');
echo 'Rate limit cache cleared';
"

# Adjust limits in GroupsBusinessRules.php (if needed):
# join: 10/hour -> increase to 20/hour
# invite: 20/hour -> increase to 30/hour
# nucleo_join: 5/hour -> increase to 10/hour
```

### Full Rollback (if necessary)

```bash
# Revert to previous version
git checkout main
git pull origin main

# Deactivate
wp plugin deactivate apollo-social

# Restore DB (LAST RESORT ONLY)
# mysql -u user -p database_name < apollo-social-backup-YYYYMMDD-HHMMSS.sql

# Activate previous version
wp plugin activate apollo-social

# Verify
wp option get apollo_schema_version
# Should show previous version (e.g., 2.2.0)
```

---

## Monitoring Dashboard (24-72 hours post-deployment)

### Key Metrics to Watch

1. **Error Log Volume**
   - Baseline: <50 errors per hour
   - Alert if: >500 errors per hour

2. **Database Query Time**
   - Baseline: <100ms for group queries
   - Alert if: >500ms (indicates missing index)

3. **REST API Response Time**
   - Baseline: <200ms for /comunas list
   - Alert if: >1000ms

4. **Rate Limit Blocks**
   - Expected: 0-10 per hour (normal abuse prevention)
   - Alert if: >100 per hour (indicates legitimate users hitting limits)

5. **Nonce Validation Failures**
   - Expected: 0 (all clients should send valid nonce)
   - Alert if: >5 per hour (indicates client issues)

### Monitoring Commands

```bash
# Check errors
wp eval "
\$since = date('Y-m-d H:i:s', strtotime('-1 hour'));
\$errors = \$GLOBALS['wpdb']->get_results(
  \"SELECT COUNT(*) as count FROM wp_options
   WHERE option_name = 'apollo_log_errors' AND option_value LIKE '%\$since%'\"
);
echo 'Errors (1h): ' . \$errors[0]->count;
"

# Check rate limits
wp eval "
\$transients = \$GLOBALS['wpdb']->get_results(
  \"SELECT COUNT(*) as count FROM wp_options
   WHERE option_name LIKE '%transient%rate_limit%'\"
);
echo 'Active rate limits: ' . \$transients[0]->count;
"

# Check group distribution
wp apollo groups:reconcile --dry-run | grep "Group Distribution"

# Performance check
wp eval "
\$start = microtime(true);
\$groups = \$GLOBALS['wpdb']->get_results('SELECT * FROM wp_apollo_groups LIMIT 1000');
\$time = round((microtime(true) - \$start) * 1000, 2);
echo 'Query time: ' . \$time . 'ms';
"
```

---

## Troubleshooting

### Issue: "Schema upgrade failed"

**Symptoms**: Plugin shows error on activation
**Solution**:
```bash
# Check specific error
wp apollo schema:status

# Run migrations individually
wp apollo schema:upgrade --dry-run

# Check database for table issues
wp db check --repair
```

### Issue: "403 Unauthorized on all POST requests"

**Symptoms**: Nonce validation fails even with correct header
**Solution**:
```bash
# Verify nonce generation works
wp eval "echo wp_create_nonce('apollo_nonce');"

# Check X-WP-Nonce header is actually being sent
curl -v -X POST http://localhost/wp-json/apollo/v1/comunas \
  -H "X-WP-Nonce: test-nonce" 2>&1 | grep "X-WP-Nonce"

# Verify nonce validation is enabled
wp option get apollo_groups_api
```

### Issue: "Rate limiting too strict or not working"

**Symptoms**: Users can't use group features or limits not enforced
**Solution**:
```bash
# Check rate limit transients
wp eval "
\$transients = \$GLOBALS['wpdb']->get_results(
  \"SELECT option_name, option_value FROM wp_options
   WHERE option_name LIKE '%transient%rate_limit%'\"
);
foreach(\$transients as \$t) {
  echo \$t->option_name . ' = ' . \$t->option_value . PHP_EOL;
}
"

# Clear rate limits if stuck
wp eval "
\$GLOBALS['wpdb']->query(
  'DELETE FROM ' . \$GLOBALS['wpdb']->prefix . 'options
   WHERE option_name LIKE \"%transient%rate_limit%\"'
);
echo 'Cleared ' . \$GLOBALS['wpdb']->rows_affected . ' transients';
"
```

### Issue: "Groups showing wrong type or missing members"

**Symptoms**: Groups not showing as 'nucleo' or members lists empty
**Solution**:
```bash
# Run reconciliation with dry-run first
wp apollo groups:reconcile --dry-run

# Then apply changes
wp apollo groups:reconcile

# Verify
wp apollo groups:reconcile --dry-run
# Should show: Found 0 groups...
```

---

## Checklists

### Pre-Deployment ✅
- [ ] Code reviewed
- [ ] DB backup created and verified
- [ ] Feature flags set correctly
- [ ] Staging deployment passed
- [ ] All tests green

### Deployment ✅
- [ ] Maintenance mode enabled
- [ ] Code deployed
- [ ] Schema upgraded
- [ ] All verification tests passed
- [ ] Maintenance mode disabled

### Post-Deployment (24h) ✅
- [ ] Error log normal
- [ ] Performance metrics normal
- [ ] Rate limiting working
- [ ] No user complaints
- [ ] Backups stored safely

---

## Emergency Contacts

- **Backend Lead**: [Name/Slack]
- **DevOps Lead**: [Name/Slack]
- **Database Admin**: [Name/Slack]
- **On-Call**: [Escalation Process]

---

**Last Updated**: 30/12/2025
**Reviewed By**: [Name]
**Approved By**: [Name]

