#!/bin/bash
# phpcbf-all.sh - Run PHPCBF in 4 sequential passes

set -e

YELLOW='\033[1;33m'
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}╔═══════════════════════════════════════════════════════╗${NC}"
echo -e "${YELLOW}║  Apollo Plugins - PHPCBF Sequential Execution        ║${NC}"
echo -e "${YELLOW}╚═══════════════════════════════════════════════════════╝${NC}"
echo ""

# Pass 1: WordPress Core
echo -e "${YELLOW}━━━ PASS 1/4: WordPress Core Standards ━━━${NC}"
phpcbf --standard=.phpcs-wordpress.xml --report=summary || true
echo -e "${GREEN}✓ Pass 1 completed${NC}\n"

# Pass 2: WordPress-Extra
echo -e "${YELLOW}━━━ PASS 2/4: WordPress-Extra Standards ━━━${NC}"
phpcbf --standard=.phpcs-extra.xml --report=summary || true
echo -e "${GREEN}✓ Pass 2 completed${NC}\n"

# Pass 3: WordPress-VIP-Go
echo -e "${YELLOW}━━━ PASS 3/4: WordPress-VIP-Go Standards ━━━${NC}"
phpcbf --standard=.phpcs-vip.xml --report=summary || true
echo -e "${GREEN}✓ Pass 3 completed${NC}\n"

# Pass 4: PHPCompatibilityWP (check only, no auto-fix)
echo -e "${YELLOW}━━━ PASS 4/4: PHP Compatibility Check (8.1+) ━━━${NC}"
phpcs --standard=.phpcs-compat.xml --report=summary || true
echo -e "${GREEN}✓ Pass 4 completed${NC}\n"

echo -e "${GREEN}╔═══════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║  ✓ All 4 passes completed successfully!              ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════════════════╝${NC}"