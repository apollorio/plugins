#!/bin/bash
#
# Apollo Map Tileset Guardrail Script
# ====================================
# STRICT MODE: Prevents hardcoded tile URLs in Apollo plugins.
#
# This script scans for tile URLs that should ONLY exist in:
#   apollo-core/includes/class-apollo-map-provider.php
#
# Usage:
#   ./check-hardcoded-tiles.sh
#
# Run this in CI/pre-commit to catch regressions.

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGINS_DIR="${SCRIPT_DIR}/../.."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "🔍 Apollo Map Tileset Guardrail"
echo "================================"
echo ""

# Forbidden patterns (tile URLs that should only be in provider)
FORBIDDEN_PATTERNS=(
    "tile.openstreetmap.org"
    "basemaps.cartocdn.com"
    "tiles.mapbox.com"
    "api.mapbox.com/styles"
)

# Allowed files (the ONLY place these URLs should exist)
ALLOWED_FILES=(
    "apollo-core/includes/class-apollo-map-provider.php"
    "apollo-core/scripts/check-hardcoded-tiles.sh"
    "apollo-core/assets/js/apollo-map-tileset.js"
)

# Note: Fallback URLs inside `else { ... using fallback ... }` blocks are acceptable.
# The grep below will find them, but manual review should confirm they're in fallback blocks.
# For stricter CI, use AST parsing or pattern matching for `} else {` context.

# Track violations
VIOLATIONS=0
VIOLATION_FILES=()

# Scan function
scan_for_pattern() {
    local pattern=$1

    # Search in apollo-* plugins, excluding allowed files and vendor directories
    while IFS= read -r file; do
        # Skip if file is in allowed list
        is_allowed=false
        for allowed in "${ALLOWED_FILES[@]}"; do
            if [[ "$file" == *"$allowed"* ]]; then
                is_allowed=true
                break
            fi
        done

        # Skip vendor directories
        if [[ "$file" == *"/vendor/"* ]]; then
            is_allowed=true
        fi

        # Skip node_modules
        if [[ "$file" == *"/node_modules/"* ]]; then
            is_allowed=true
        fi

        # Skip OLD/backup files
        if [[ "$file" == *"-OLD"* ]] || [[ "$file" == *".bak"* ]]; then
            is_allowed=true
        fi

        if [ "$is_allowed" = false ]; then
            echo -e "${RED}❌ VIOLATION:${NC} $pattern found in:"
            echo "   $file"
            grep -n "$pattern" "$file" | head -3 | while read -r line; do
                echo "   $line"
            done
            echo ""
            VIOLATIONS=$((VIOLATIONS + 1))
            VIOLATION_FILES+=("$file")
        fi
    done < <(grep -rl "$pattern" "${PLUGINS_DIR}"/apollo-*/  2>/dev/null || true)
}

# Run scans
for pattern in "${FORBIDDEN_PATTERNS[@]}"; do
    echo "Checking for: $pattern"
    scan_for_pattern "$pattern"
done

echo ""
echo "================================"

if [ $VIOLATIONS -eq 0 ]; then
    echo -e "${GREEN}✅ PASS: No hardcoded tile URLs found!${NC}"
    echo ""
    echo "All Apollo plugins are using the central tileset provider."
    exit 0
else
    echo -e "${RED}❌ FAIL: Found $VIOLATIONS hardcoded tile URL(s)!${NC}"
    echo ""
    echo "STRICT MODE VIOLATION: Tile URLs must only be defined in:"
    echo "  apollo-core/includes/class-apollo-map-provider.php"
    echo ""
    echo "To fix:"
    echo "  1. Remove the hardcoded URL"
    echo "  2. Use window.ApolloMapTileset.apply(map) in JavaScript"
    echo "  3. Use Apollo\\Core\\Apollo_Map_Provider::get_instance()->get_tileset() in PHP"
    echo ""
    echo "Affected files:"
    printf '  %s\n' "${VIOLATION_FILES[@]}" | sort -u
    exit 1
fi
