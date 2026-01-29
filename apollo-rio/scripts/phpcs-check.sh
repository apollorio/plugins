#!/bin/bash
# PHP CodeSniffer Check Script for Apollo::Rio
# Usage: ./scripts/phpcs-check.sh

# Check if vendor/bin/phpcs exists
if [ ! -f "modules/pwa/vendor/bin/phpcs" ]; then
    echo "Error: phpcs not found. Please run 'composer install' in modules/pwa directory first."
    exit 1
fi

# Run phpcs with WordPress standards
modules/pwa/vendor/bin/phpcs \
    --standard=.phpcs.xml.dist \
    --ignore=vendor,node_modules,modules/pwa/vendor,modules/pwa/node_modules \
    --extensions=php \
    .

echo ""
echo "✅ PHPCS check completed!"
echo "To auto-fix issues, run: ./scripts/phpcs-fix.sh"


