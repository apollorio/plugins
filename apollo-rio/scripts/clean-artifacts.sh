#!/bin/bash
# Clean Artifacts Script for Apollo Plugins
# Removes .DS_Store, .idea directories, node_modules, and vendor directories
# Usage: ./scripts/clean-artifacts.sh

set -e

PLUGINS_DIR="."

echo "🧹 Cleaning artifacts from Apollo plugins..."
echo ""

# Remove .DS_Store files
echo "Removing .DS_Store files..."
find "$PLUGINS_DIR" -name ".DS_Store" -type f -delete
echo "✅ .DS_Store files removed"

# Remove .idea directories
echo "Removing .idea directories..."
find "$PLUGINS_DIR" -name ".idea" -type d -exec rm -rf {} + 2>/dev/null || true
echo "✅ .idea directories removed"

# Remove node_modules directories (but keep modules/pwa/node_modules if needed)
echo "Removing node_modules directories..."
find "$PLUGINS_DIR" -name "node_modules" -type d -not -path "*/modules/pwa/node_modules*" -exec rm -rf {} + 2>/dev/null || true
echo "✅ node_modules directories removed (preserved modules/pwa/node_modules)"

# Note: vendor directories are kept as they may be necessary for PHP dependencies
# Uncomment the following lines if you want to remove vendor directories:
# echo "Removing vendor directories..."
# find "$PLUGINS_DIR" -name "vendor" -type d -not -path "*/modules/pwa/vendor*" -exec rm -rf {} + 2>/dev/null || true
# echo "✅ vendor directories removed (preserved modules/pwa/vendor)"

echo ""
echo "✨ Cleanup completed!"


