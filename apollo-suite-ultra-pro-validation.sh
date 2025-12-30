#!/bin/bash
# Apollo Ultra-Pro Validation Script
# Validates Ultra-Pro WordPress Structure compliance

set -e

echo "🛡️  Apollo Ultra-Pro Validation Script"
echo "======================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APOLLO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
WP_CONTENT="$APOLLO_ROOT/wp-content"
PLUGINS_DIR="$WP_CONTENT/plugins"
MU_PLUGINS_DIR="$WP_CONTENT/mu-plugins"

# Results tracking
CHECKS_PASSED=0
CHECKS_FAILED=0
TOTAL_CHECKS=0

# Helper function to report results
check_result() {
    local name="$1"
    local status="$2"
    local message="$3"

    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))

    if [ "$status" = "pass" ]; then
        echo -e "${GREEN}✅ PASS${NC} $name"
        CHECKS_PASSED=$((CHECKS_PASSED + 1))
    elif [ "$status" = "warn" ]; then
        echo -e "${YELLOW}⚠️  WARN${NC} $name"
        if [ -n "$message" ]; then
            echo -e "   $message"
        fi
    else
        echo -e "${RED}❌ FAIL${NC} $name"
        CHECKS_FAILED=$((CHECKS_FAILED + 1))
        if [ -n "$message" ]; then
            echo -e "   $message"
        fi
    fi
}

echo "🔍 Checking Ecosystem Architecture..."
echo "-----------------------------------"

# 1. Modular Architecture Check
if [ -d "$PLUGINS_DIR/apollo-core/src" ]; then
    check_result "Modular Core Structure" "pass"
else
    check_result "Modular Core Structure" "fail" "apollo-core/src directory missing"
fi

# 2. Pillar Modules Check
pillars=("Security" "Performance" "SEO" "Maintenance")
for pillar in "${pillars[@]}"; do
    if [ -d "$PLUGINS_DIR/apollo-core/src/$pillar" ]; then
        check_result "$pillar Module" "pass"
    else
        check_result "$pillar Module" "fail" "$pillar module directory missing"
    fi
done

# 3. MU-Plugin Hardening Check
if [ -f "$MU_PLUGINS_DIR/apollo-safe-mode.php" ]; then
    check_result "MU-Plugin Hardening" "pass"
else
    check_result "MU-Plugin Hardening" "fail" "MU-plugin hardening missing"
fi

echo ""
echo "🔒 Checking Security Engineering..."
echo "----------------------------------"

# 4. Firewall Implementation
if grep -q "FIREWALL" "$MU_PLUGINS_DIR/apollo-safe-mode.php" 2>/dev/null; then
    check_result "Firewall Implementation" "pass"
else
    check_result "Firewall Implementation" "fail" "Firewall rules not found"
fi

# 5. Malware Scanning
if grep -q "malware\|scan\|signature" "$MU_PLUGINS_DIR/apollo-safe-mode.php" 2>/dev/null; then
    check_result "Malware Scanning" "pass"
else
    check_result "Malware Scanning" "fail" "Malware scanning not implemented"
fi

# 6. Login Protection
if grep -q "login.*attempt\|rate.*limit" "$MU_PLUGINS_DIR/apollo-safe-mode.php" 2>/dev/null; then
    check_result "Login Protection" "pass"
else
    check_result "Login Protection" "fail" "Login protection missing"
fi

# 7. CVE Mitigation
if grep -q "xmlrpc_enabled\|pingback\|author_rewrite" "$MU_PLUGINS_DIR/apollo-safe-mode.php" 2>/dev/null; then
    check_result "CVE Mitigation" "pass"
else
    check_result "CVE Mitigation" "fail" "CVE mitigations not found"
fi

# 8. Input Sanitization
if grep -q "sanitize_text_field\|wp_kses" "$MU_PLUGINS_DIR/apollo-safe-mode.php" 2>/dev/null; then
    check_result "Input Sanitization" "pass"
else
    check_result "Input Sanitization" "fail" "Input sanitization missing"
fi

echo ""
echo "⚡ Checking Performance & Execution..."
echo "------------------------------------"

# 9. Asset Minification
if [ -f "$PLUGINS_DIR/apollo-core/src/Performance/PerformanceModule.php" ]; then
    if grep -q "minifyJs\|minifyCss" "$PLUGINS_DIR/apollo-core/src/Performance/PerformanceModule.php"; then
        check_result "Asset Minification" "pass"
    else
        check_result "Asset Minification" "fail" "Minification functions missing"
    fi
else
    check_result "Asset Minification" "fail" "PerformanceModule missing"
fi

# 10. Non-blocking Scripts
if grep -q "async\|defer" "$PLUGINS_DIR/apollo-core/src/Performance/PerformanceModule.php" 2>/dev/null; then
    check_result "Non-blocking Scripts" "pass"
else
    check_result "Non-blocking Scripts" "fail" "Async/defer not implemented"
fi

# 11. Aggressive Caching
if grep -q "Cache-Control.*max-age\|Expires" "$PLUGINS_DIR/apollo-core/src/Performance/PerformanceModule.php" 2>/dev/null; then
    check_result "Aggressive Caching" "pass"
else
    check_result "Aggressive Caching" "fail" "Caching headers missing"
fi

echo ""
echo "🔍 Checking SEO Pillar..."
echo "-----------------------"

# 12. Schema.org Implementation
if [ -f "$PLUGINS_DIR/apollo-core/src/SEO/SEOModule.php" ]; then
    if grep -q "application/ld+json\|schema\.org" "$PLUGINS_DIR/apollo-core/src/SEO/SEOModule.php"; then
        check_result "Schema.org JSON-LD" "pass"
    else
        check_result "Schema.org JSON-LD" "fail" "Schema markup missing"
    fi
else
    check_result "Schema.org JSON-LD" "fail" "SEOModule missing"
fi

# 13. MutationObserver for Dynamic Content
if grep -q "MutationObserver" "$PLUGINS_DIR/apollo-core/src/SEO/SEOModule.php" 2>/dev/null; then
    check_result "Dynamic Schema Updates" "pass"
else
    check_result "Dynamic Schema Updates" "fail" "MutationObserver not implemented"
fi

# 14. CDN Integration
if grep -q "cdn\.apollo\.rio\.br" "$PLUGINS_DIR/apollo-core/src/SEO/SEOModule.php" 2>/dev/null; then
    check_result "CDN Integration" "pass"
else
    check_result "CDN Integration" "fail" "CDN not configured"
fi

echo ""
echo "🛠️  Checking DevOps & Modernization..."
echo "------------------------------------"

# 15. Configuration as Code
if [ -f "$PLUGINS_DIR/apollo-core/src/Maintenance/MaintenanceModule.php" ]; then
    if grep -q "Configuration.*Code\|config.*code" "$PLUGINS_DIR/apollo-core/src/Maintenance/MaintenanceModule.php"; then
        check_result "Configuration as Code" "pass"
    else
        check_result "Configuration as Code" "fail" "Config as code not implemented"
    fi
else
    check_result "Configuration as Code" "fail" "MaintenanceModule missing"
fi

# 16. Factory/Storefront Separation
if grep -q "Factory\|Storefront\|admin_init" "$PLUGINS_DIR/apollo-core/src/Maintenance/MaintenanceModule.php" 2>/dev/null; then
    check_result "Factory/Storefront Separation" "pass"
else
    check_result "Factory/Storefront Separation" "fail" "Separation not enforced"
fi

# 17. MutationObserver for Frontend Updates
if grep -q "MutationObserver" "$PLUGINS_DIR/apollo-core/src/SEO/SEOModule.php" 2>/dev/null; then
    check_result "Dynamic Frontend Updates" "pass"
else
    check_result "Dynamic Frontend Updates" "fail" "Frontend MutationObserver missing"
fi

# 18. Health Checks
if grep -q "health.*check\|automated.*check" "$PLUGINS_DIR/apollo-core/src/Maintenance/MaintenanceModule.php" 2>/dev/null; then
    check_result "Automated Health Checks" "pass"
else
    check_result "Automated Health Checks" "fail" "Health checks not implemented"
fi

echo ""
echo "🔗 Checking Bridge Integration..."
echo "-------------------------------"

# 19. Bridge Loader
if [ -f "$PLUGINS_DIR/apollo-core/src/Bridge/BridgeLoader.php" ]; then
    check_result "Bridge Loader" "pass"
else
    check_result "Bridge Loader" "fail" "Bridge loader missing"
fi

# 20. Schema Orchestrator
if [ -f "$PLUGINS_DIR/apollo-core/src/Schema/SchemaOrchestrator.php" ]; then
    check_result "Schema Orchestrator" "pass"
else
    check_result "Schema Orchestrator" "fail" "Schema orchestrator missing"
fi

# 21. Plugin Detection
if grep -q "apollo-social\|apollo-events\|apollo-rio" "$PLUGINS_DIR/apollo-core/src/Bridge/BridgeLoader.php" 2>/dev/null; then
    check_result "Plugin Detection" "pass"
else
    check_result "Plugin Detection" "fail" "Plugin detection not implemented"
fi

echo ""
echo "📊 Validation Summary"
echo "===================="

echo "Total Checks: $TOTAL_CHECKS"
echo -e "Passed: ${GREEN}$CHECKS_PASSED${NC}"
echo -e "Failed: ${RED}$CHECKS_FAILED${NC}"

if [ $CHECKS_FAILED -eq 0 ]; then
    echo ""
    echo -e "${GREEN}🎉 All Ultra-Pro checks passed!${NC}"
    echo "Your Apollo ecosystem is Ultra-Pro compliant."
    exit 0
else
    echo ""
    echo -e "${RED}⚠️  $CHECKS_FAILED checks failed.${NC}"
    echo "Please address the failed checks to achieve Ultra-Pro compliance."
    exit 1
fi
