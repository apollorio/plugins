# Pre-Deployment Grep Checklist

**Purpose**: Automated validation of critical code patterns before merge
**Date**: 30/12/2025
**Status**: READY TO RUN

---

## 1. NONCE & SECURITY PATTERNS

### Check 1: No `__return_true` on sensitive endpoints

```bash
# Should return: 0 matches (PASS)
# These are allowed only on read-only GET endpoints
grep -r "__return_true.*permission_callback" \
  src/Modules/Groups/ \
  src/Api/RestSecurity.php \
  --include="*.php" | grep -E "(POST|PUT|PATCH|DELETE)" || echo "✅ PASS: No overpermissive write endpoints"
```

**Expected**: ✅ PASS (0 matches)
**Status**: ☐ Checked

---

### Check 2: All REST write endpoints require security

```bash
# Should find: RestSecurity::verify() or wp_verify_nonce()
grep -r "register_rest_route" src/Modules/Groups/ --include="*.php" -A 5 | \
  grep -c "RestSecurity::verify\|wp_verify_nonce\|permission_callback" || echo "⚠️  Review: Not all endpoints checked"
```

**Expected**: ✅ Multiple matches for RestSecurity::verify()
**Status**: ☐ Checked

---

### Check 3: No hardcoded nonces

```bash
# Should return: 0 matches
grep -r "wp_create_nonce.*static\|'nonce_.*'.*=" \
  src/ --include="*.php" || echo "✅ PASS: No hardcoded nonces"
```

**Expected**: ✅ PASS (0 matches)
**Status**: ☐ Checked

---

## 2. REWRITE RULES & ROUTING

### Check 4: No runtime flush_rewrite_rules

```bash
# Should return: ONLY in Apollo_Router::onActivation() and onDeactivation()
grep -r "flush_rewrite_rules" src/ --include="*.php" | \
  grep -v "onActivation\|onDeactivation" && echo "⚠️  FAIL: Runtime flush found" || echo "✅ PASS: No runtime flush"
```

**Expected**: ✅ PASS (only in onActivation/onDeactivation)
**Status**: ☐ Checked

---

### Check 5: All rewrite rules use Apollo_Router

```bash
# Should return: 0 matches in modules (all routed through Apollo_Router)
grep -r "add_rewrite_rule\|register_rewrite_rule" src/Modules/ \
  --include="*.php" || echo "✅ PASS: All routes via Apollo_Router"
```

**Expected**: ✅ PASS (0 matches in modules)
**Status**: ☐ Checked

---

### Check 6: Feed routes protected

```bash
# Should return: /apollo/feed/ (not /feed/)
grep -r "'/feed/\|/feed/?$" src/Infrastructure/Http/Apollo_Router.php && \
  grep "ROUTE_PREFIX.*feed" src/Infrastructure/Http/Apollo_Router.php && \
  echo "✅ PASS: Feed routes under /apollo/ prefix" || echo "⚠️  FAIL: Check feed routes"
```

**Expected**: ✅ PASS (feed routes under /apollo/feed/)
**Status**: ☐ Checked

---

## 3. DATABASE & MIGRATIONS

### Check 7: Migrations are idempotent

```bash
# Should find: "column_exists", "IF NOT EXISTS", or "CONSTRAINT_EXISTS"
grep -r "SHOW COLUMNS\|INFORMATION_SCHEMA\|IF NOT EXISTS\|column_exists" \
  src/Infrastructure/Database/Migrations.php | wc -l

# Expected: 10+ matches (all checks are idempotent)
```

**Expected**: ✅ 10+ matches
**Status**: ☐ Checked

---

### Check 8: No direct INSERT without type checking

```bash
# Should return: GroupsModule::create() sanitizes type
grep -r "INSERT INTO.*apollo_groups\|UPDATE.*apollo_groups" \
  src/ --include="*.php" -B 5 | grep -c "group_type\|TYPE_COMUNA\|TYPE_NUCLEO"

# All group inserts should validate type
```

**Expected**: ✅ All group inserts validate type
**Status**: ☐ Checked

---

### Check 9: Foreign key checks managed properly

```bash
# Should show: FK checks disabled during migration, then re-enabled
grep -r "SET FOREIGN_KEY_CHECKS" src/Infrastructure/Database/Migrations.php | wc -l

# Expected: 4 lines (2 disables, 2 re-enables per migration)
```

**Expected**: ✅ 4 lines
**Status**: ☐ Checked

---

## 4. FEATURE FLAGS & PERMISSIONS

### Check 10: Feature flags on all module init

```bash
# Should find: isEnabled('groups_api') before registerEndpoints()
grep -r "FeatureFlags::isEnabled" src/Modules/Groups/ --include="*.php" -A 2 | \
  grep -c "registerEndpoints\|init" || echo "⚠️  Review: Feature flags on init"
```

**Expected**: ✅ Present on module init
**Status**: ☐ Checked

---

### Check 11: Capability checks on admin/mod operations

```bash
# Should find: current_user_can() or capability check before:
# - handleInvite
# - handleApprove
# - handleDelete
grep -r "handleInvite\|handleApprove\|handleDelete" src/ \
  --include="*.php" -B 5 | grep -c "current_user_can\|capability_check" || echo "⚠️  Review: Capability checks"
```

**Expected**: ✅ Capability checks present
**Status**: ☐ Checked

---

### Check 12: No direct role assignment

```bash
# Should return: 0 matches (roles assigned only via addMember, not direct UPDATE)
grep -r "UPDATE.*apollo_group_members.*SET role\|INSERT.*role\|UPDATE.*role.*value" \
  src/Modules/Groups/ --include="*.php" --exclude="*Test.php" || \
  echo "✅ PASS: Roles only via business logic"
```

**Expected**: ✅ PASS (roles via GroupsModule::addMember)
**Status**: ☐ Checked

---

## 5. DATA VALIDATION & SANITIZATION

### Check 13: All user inputs sanitized

```bash
# Should find: sanitize_* or wp_kses_* before database insert
grep -r "\$_POST\|\$_GET\|\$_REQUEST" src/ --include="*.php" -A 1 | \
  grep -c "sanitize_\|wp_kses_\|absint\|intval" || echo "⚠️  Review: Some inputs might not be sanitized"
```

**Expected**: ✅ Most/all sanitized
**Status**: ☐ Checked

---

### Check 14: REST parameters validated

```bash
# Should find: type casting and validation in REST controllers
grep -r "get_param\|json_decode" src/Infrastructure/Http/Controllers/ \
  --include="*.php" -A 2 | grep -c "sanitize\|validate\|cast" || echo "⚠️  Review: Parameter validation"
```

**Expected**: ✅ Parameters validated in controllers
**Status**: ☐ Checked

---

### Check 15: No SQL injection vectors

```bash
# Should use: $wpdb->prepare() or property access (not concatenation)
grep -r "\"SELECT\|\"UPDATE\|\"INSERT" src/ --include="*.php" | \
  grep -v "\$wpdb->prepare\|INFORMATION_SCHEMA" && echo "⚠️  Review: SQL queries" || \
  echo "✅ PASS: All SQL uses prepare() or constants"
```

**Expected**: ✅ PASS or safe queries
**Status**: ☐ Checked

---

## 6. API CONTRACTS

### Check 16: REST endpoint registration under correct namespace

```bash
# Should return: All under apollo/v1
grep -r "register_rest_route\|RestRoutes::register" src/ --include="*.php" | \
  grep -c "'apollo/v1'" || echo "⚠️  Review: Check REST namespace"
```

**Expected**: ✅ Multiple matches with 'apollo/v1'
**Status**: ☐ Checked

---

### Check 17: Deprecated endpoints have headers

```bash
# Should find: X-Deprecated or Deprecation header on legacy routes
grep -r "X-Deprecated\|Deprecation\|'/groups" src/Modules/Groups/ \
  --include="*.php" | head -5 && echo "✅ Found deprecation headers" || echo "⚠️  Review: Legacy headers"
```

**Expected**: ✅ Deprecation headers present
**Status**: ☐ Checked

---

### Check 18: Rate limiting implemented

```bash
# Should find: rate limiting logic in RestSecurity or AjaxHandlers
grep -r "rate_limit\|RateLimit\|transient.*rate" src/ --include="*.php" | wc -l

# Expected: 10+ lines
```

**Expected**: ✅ 10+ matches
**Status**: ☐ Checked

---

## 7. LOGGING & MONITORING

### Check 19: Critical operations logged

```bash
# Should find: ApolloLogger calls for:
# - plugin activation/deactivation
# - schema migration
# - permission denials
grep -r "ApolloLogger::\|do_action.*apollo" src/ --include="*.php" | \
  grep -c "activated\|migrat\|permission\|error" || echo "⚠️  Review: Logging coverage"
```

**Expected**: ✅ Multiple logging calls
**Status**: ☐ Checked

---

### Check 20: No sensitive data in logs

```bash
# Should return: 0 matches (no passwords, tokens, nonces in logs)
grep -r "password\|api_key\|secret\|nonce.*log" src/ --include="*.php" && \
  echo "⚠️  FAIL: Sensitive data in logs" || echo "✅ PASS: No sensitive data logged"
```

**Expected**: ✅ PASS (0 matches)
**Status**: ☐ Checked

---

## Automated Test Script

Run all checks at once:

```bash
#!/bin/bash

echo "=========================================="
echo " APOLLO SOCIAL PRE-DEPLOYMENT CHECKLIST"
echo "=========================================="
echo ""

CHECKS_PASSED=0
CHECKS_FAILED=0

# Check 1
if grep -r "__return_true.*permission_callback" src/ --include="*.php" | grep -E "(POST|PUT|PATCH|DELETE)" >/dev/null 2>&1; then
  echo "❌ Check 1: Overpermissive endpoints found"
  ((CHECKS_FAILED++))
else
  echo "✅ Check 1: No overpermissive write endpoints"
  ((CHECKS_PASSED++))
fi

# Check 2
if grep -r "flush_rewrite_rules" src/ --include="*.php" | grep -v "onActivation\|onDeactivation" >/dev/null 2>&1; then
  echo "❌ Check 2: Runtime flush_rewrite_rules found"
  ((CHECKS_FAILED++))
else
  echo "✅ Check 2: No runtime flush"
  ((CHECKS_PASSED++))
fi

# Check 3
if grep -r "add_rewrite_rule" src/Modules/ --include="*.php" >/dev/null 2>&1; then
  echo "❌ Check 3: Rewrite rules outside Apollo_Router"
  ((CHECKS_FAILED++))
else
  echo "✅ Check 3: All routes via Apollo_Router"
  ((CHECKS_PASSED++))
fi

# Check 4
if grep -r "INSERT INTO.*apollo_groups\|UPDATE.*apollo_groups" src/ --include="*.php" | \
   grep -v "group_type\|TYPE_COMUNA\|TYPE_NUCLEO\|sanitize\|validate" >/dev/null 2>&1; then
  echo "❌ Check 4: Group insert without type validation"
  ((CHECKS_FAILED++))
else
  echo "✅ Check 4: All group inserts validate type"
  ((CHECKS_PASSED++))
fi

# Check 5
if grep -r "\$_POST\|\$_GET" src/ --include="*.php" | grep -v "sanitize_\|wp_kses_\|absint\|intval" >/dev/null 2>&1; then
  echo "⚠️  Check 5: Some inputs might not be sanitized"
  ((CHECKS_FAILED++))
else
  echo "✅ Check 5: User inputs sanitized"
  ((CHECKS_PASSED++))
fi

# Check 6
if grep -r "\"SELECT\|\"UPDATE\|\"INSERT" src/ --include="*.php" | \
   grep -v "\$wpdb->prepare\|INFORMATION_SCHEMA" >/dev/null 2>&1; then
  echo "⚠️  Check 6: SQL queries - review"
  ((CHECKS_FAILED++))
else
  echo "✅ Check 6: Safe SQL queries"
  ((CHECKS_PASSED++))
fi

echo ""
echo "=========================================="
echo " RESULTS: $CHECKS_PASSED PASSED, $CHECKS_FAILED FAILED"
echo "=========================================="
echo ""

if [ $CHECKS_FAILED -eq 0 ]; then
  echo "✅ ALL CHECKS PASSED - READY FOR DEPLOYMENT"
  exit 0
else
  echo "❌ SOME CHECKS FAILED - REVIEW ABOVE"
  exit 1
fi
```

Save as `pre-deploy-check.sh`, make executable, and run:

```bash
chmod +x pre-deploy-check.sh
./pre-deploy-check.sh
```

---

## Summary Table

| Check | Pattern | Expected | Status |
|-------|---------|----------|--------|
| 1 | No `__return_true` on writes | 0 matches | ☐ |
| 2 | No runtime `flush_rewrite_rules` | 0 matches | ☐ |
| 3 | All rewrite rules via Apollo_Router | 0 matches in modules | ☐ |
| 4 | Feed routes under `/apollo/` | Present | ☐ |
| 5 | Migrations idempotent | 10+ checks | ☐ |
| 6 | Groups insert validates type | All validated | ☐ |
| 7 | FK checks managed | 4 lines | ☐ |
| 8 | Feature flags on init | Present | ☐ |
| 9 | Capability checks | Present | ☐ |
| 10 | Roles via business logic | 0 direct updates | ☐ |
| 11 | User inputs sanitized | All sanitized | ☐ |
| 12 | REST params validated | Present | ☐ |
| 13 | SQL uses prepare() | 0 unsafe queries | ☐ |
| 14 | REST under apollo/v1 | Multiple | ☐ |
| 15 | Deprecation headers | Present | ☐ |
| 16 | Rate limiting | 10+ matches | ☐ |
| 17 | Critical ops logged | Multiple | ☐ |
| 18 | No sensitive data logged | 0 matches | ☐ |

---

**Instructions**:
1. Run automated script above
2. Review each check manually
3. Mark ☐ as checked
4. Sign-off when all pass

**Signed By**: ________________
**Date**: ________________

