#!/bin/bash
# PHP CodeSniffer Auto-Fix Script for Apollo::Rio
# Usage: ./scripts/phpcs-fix.sh
# WARNING: This will modify your files. Make sure you have a backup or are using version control.

# Check if vendor/bin/phpcbf exists
if [ ! -f "modules/pwa/vendor/bin/phpcbf" ]; then
    echo "Error: phpcbf not found. Please run 'composer install' in modules/pwa directory first."
    exit 1
fi

echo "⚠️  WARNING: This will modify your PHP files!"
echo "Press Ctrl+C to cancel, or Enter to continue..."
read

# Run phpcbf with WordPress standards (limited auto-fix)
modules/pwa/vendor/bin/phpcbf \
    --standard=.phpcs.xml.dist \
    --ignore=vendor,node_modules,modules/pwa/vendor,modules/pwa/node_modules \
    --extensions=php \
    .

echo ""
echo "✅ PHPCBF auto-fix completed!"
echo "Review the changes and run phpcs-check.sh again to verify."


