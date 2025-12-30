#!/bin/bash
#
# Apollo Suite Pre-Deploy Validation Script
# Run from wp-content/plugins/ directory
#
# Usage: bash apollo-suite-validation.sh
#

set -e

echo "=========================================="
echo "Apollo Suite Pre-Deploy Validation"
echo "=========================================="
echo ""

PASS_COUNT=0
FAIL_COUNT=0
WARN_COUNT=0

check_pass() {
    echo "  ✅ PASS: $1"
    ((PASS_COUNT++))
}

check_fail() {
    echo "  ❌ FAIL: $1"
    ((FAIL_COUNT++))
}

check_warn() {
    echo "  ⚠️  WARN: $1"
    ((WARN_COUNT++))
}

# Check 1: Runtime flush_rewrite_rules (excluding activation/deactivation)
echo "[1/10] Checking for runtime flush_rewrite_rules..."
RUNTIME_FLUSH=$(grep -rn "flush_rewrite_rules" apollo-core apollo-social apollo-rio apollo-events-manager \
  --include="*.php" 2>/dev/null | grep -v "vendor\|stubs" | \
  grep -v "activation\|deactivation\|Activation\|Deactivation\|activate\|deactivate" | \
  grep -v "^[^:]*:[0-9]*:\s*//" | wc -l)

if [ "$RUNTIME_FLUSH" -eq 0 ]; then
    check_pass "No runtime flush_rewrite_rules found"
else
    check_fail "Found $RUNTIME_FLUSH runtime flush_rewrite_rules calls"
    grep -rn "flush_rewrite_rules" apollo-core apollo-social apollo-rio apollo-events-manager \
      --include="*.php" 2>/dev/null | grep -v "vendor\|stubs" | \
      grep -v "activation\|deactivation\|Activation\|Deactivation\|activate\|deactivate" | \
      grep -v "^[^:]*:[0-9]*:\s*//" | head -10
fi
echo ""

# Check 2: Direct add_rewrite_rule in modules (not Router)
echo "[2/10] Checking for direct add_rewrite_rule in modules..."
DIRECT_REWRITE=$(grep -rn "add_rewrite_rule" apollo-social/src/Modules --include="*.php" 2>/dev/null | wc -l)
if [ "$DIRECT_REWRITE" -eq 0 ]; then
    check_pass "No direct add_rewrite_rule in Modules"
else
    check_warn "Found $DIRECT_REWRITE direct add_rewrite_rule calls in Modules"
fi
echo ""

# Check 3: __return_true on write operations
echo "[3/10] Checking for __return_true on write operations..."
UNSAFE_RETURN_TRUE=$(grep -rn "'permission_callback'.*__return_true" apollo-core apollo-social \
  apollo-rio apollo-events-manager --include="*.php" 2>/dev/null | grep -v "vendor" | \
  while read line; do
    file=$(echo "$line" | cut -d: -f1)
    linenum=$(echo "$line" | cut -d: -f2)
    # Check next 3 lines for POST/PUT/PATCH/DELETE
    context=$(sed -n "$((linenum-2)),$((linenum+3))p" "$file" 2>/dev/null)
    if echo "$context" | grep -qE "'methods'\s*=>\s*'(POST|PUT|PATCH|DELETE)"; then
        echo "$line"
    fi
  done | wc -l)

if [ "$UNSAFE_RETURN_TRUE" -eq 0 ]; then
    check_pass "No __return_true on write operations"
else
    check_fail "Found $UNSAFE_RETURN_TRUE unsafe __return_true on write operations"
fi
echo ""

# Check 4: Missing nonce checks in AJAX handlers
echo "[4/10] Checking AJAX handlers for nonce verification..."
AJAX_NO_NONCE=$(grep -rln "wp_ajax_apollo" apollo-core apollo-social --include="*.php" 2>/dev/null | \
  while read file; do
    if ! grep -q "check_ajax_referer\|wp_verify_nonce" "$file" 2>/dev/null; then
        echo "$file"
    fi
  done | wc -l)

if [ "$AJAX_NO_NONCE" -eq 0 ]; then
    check_pass "All AJAX handlers have nonce checks"
else
    check_warn "Found $AJAX_NO_NONCE files with AJAX handlers potentially missing nonce checks"
fi
echo ""

# Check 5: Hardcoded wp_ prefix
echo "[5/10] Checking for hardcoded wp_ table prefix..."
HARDCODED_PREFIX=$(grep -rn "'wp_apollo\|\"wp_apollo" apollo-core apollo-social apollo-rio \
  apollo-events-manager --include="*.php" 2>/dev/null | grep -v "vendor" | wc -l)

if [ "$HARDCODED_PREFIX" -eq 0 ]; then
    check_pass "No hardcoded wp_ prefix found"
else
    check_fail "Found $HARDCODED_PREFIX hardcoded wp_ prefixes"
fi
echo ""

# Check 6: Multiple schema entry points
echo "[6/10] Counting dbDelta entry points..."
DBDELTA_FILES=$(grep -rln "dbDelta" apollo-core apollo-social apollo-rio apollo-events-manager \
  --include="*.php" 2>/dev/null | grep -v "vendor" | wc -l)

if [ "$DBDELTA_FILES" -lt 10 ]; then
    check_pass "dbDelta in $DBDELTA_FILES files (acceptable)"
else
    check_warn "dbDelta in $DBDELTA_FILES files - consider consolidating"
fi
echo ""

# Check 7: REST namespaces inventory
echo "[7/10] Inventorying REST namespaces..."
echo "  Found namespaces:"
grep -roh "register_rest_route\s*(\s*['\"][^'\"]*['\"]" apollo-core apollo-social \
  apollo-rio apollo-events-manager --include="*.php" 2>/dev/null | \
  grep -oP "(?<=['\"])[^'\"]+(?=['\"])" | sort -u | while read ns; do
    echo "    - $ns"
  done
check_pass "Namespace inventory complete"
echo ""

# Check 8: Routes without /apollo/ prefix
echo "[8/10] Checking for root-level routes..."
ROOT_ROUTES=$(grep -rn "add_rewrite_rule.*'\^[a-z]" apollo-social --include="*.php" 2>/dev/null | \
  grep -v "apollo/" | grep -v "vendor" | wc -l)

if [ "$ROOT_ROUTES" -eq 0 ]; then
    check_pass "No root-level routes without /apollo/ prefix"
else
    check_warn "Found $ROOT_ROUTES root-level routes without /apollo/ prefix"
fi
echo ""

# Check 9: Schema version options
echo "[9/10] Checking schema version tracking..."
echo "  Schema version options found:"
grep -roh "apollo.*schema.*version\|apollo.*version.*option" apollo-core apollo-social \
  --include="*.php" 2>/dev/null | grep -v "vendor" | sort -u | head -10 | while read opt; do
    echo "    - $opt"
  done
check_pass "Version tracking inventory complete"
echo ""

# Check 10: PHP syntax validation
echo "[10/10] Validating PHP syntax on key files..."
SYNTAX_ERRORS=0
for file in \
  apollo-core/src/Schema/SchemaOrchestrator.php \
  apollo-core/src/Schema/CoreSchemaModule.php \
  apollo-core/src/Security/RestSecurity.php \
  apollo-social/src/Infrastructure/Database/Schema.php \
  apollo-social/src/Infrastructure/Database/Migrations.php \
  apollo-social/src/Infrastructure/Http/Apollo_Router.php; do
  if [ -f "$file" ]; then
    if php -l "$file" 2>&1 | grep -q "No syntax errors"; then
      echo "    ✓ $file"
    else
      echo "    ✗ $file - SYNTAX ERROR"
      ((SYNTAX_ERRORS++))
    fi
  else
    echo "    - $file (not found)"
  fi
done

if [ "$SYNTAX_ERRORS" -eq 0 ]; then
    check_pass "All key files pass PHP syntax check"
else
    check_fail "$SYNTAX_ERRORS files have syntax errors"
fi
echo ""

# Summary
echo "=========================================="
echo "VALIDATION SUMMARY"
echo "=========================================="
echo "  ✅ Passed: $PASS_COUNT"
echo "  ⚠️  Warnings: $WARN_COUNT"
echo "  ❌ Failed: $FAIL_COUNT"
echo ""

if [ "$FAIL_COUNT" -gt 0 ]; then
    echo "❌ VALIDATION FAILED - Fix issues before deploy"
    exit 1
elif [ "$WARN_COUNT" -gt 0 ]; then
    echo "⚠️  VALIDATION PASSED WITH WARNINGS - Review before deploy"
    exit 0
else
    echo "✅ VALIDATION PASSED - Ready for deploy"
    exit 0
fi
