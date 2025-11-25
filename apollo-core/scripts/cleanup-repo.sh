#!/bin/bash
# Apollo Ecosystem - GitHub Repository Cleanup Script
# Removes unnecessary files from git tracking

set -e

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$REPO_ROOT"

echo "🧹 Apollo Ecosystem - Repository Cleanup"
echo "========================================"
echo ""
echo "Repository: $REPO_ROOT"
echo ""

# Counter
REMOVED_COUNT=0

# Function to remove file from git
remove_file() {
    local file="$1"
    if [ -f "$file" ] && git ls-files --error-unmatch "$file" &>/dev/null; then
        echo "  🗑️  Removing: $file"
        git rm --cached "$file" 2>/dev/null || git rm -f "$file" 2>/dev/null
        REMOVED_COUNT=$((REMOVED_COUNT + 1))
    fi
}

echo "📦 Step 1: Removing duplicate .zip files..."
echo "-------------------------------------------"

# Remove duplicate apollo-events-manager zips (keep only the main one if needed)
remove_file "apollo-events-manager (2).zip"
remove_file "apollo-events-manager (3).zip"
remove_file "apollo-events-manager (4).zip"

# These are plugin distribution zips - decide if you want to keep them
# Uncomment to remove:
# remove_file "apollo-events-manager.zip"
# remove_file "apollo-social.zip"
# remove_file "apollo-rio.zip"

echo ""
echo "📝 Step 2: Removing backup files..."
echo "-------------------------------------------"

# Remove backup files
remove_file "apollo-events-manager/includes/admin-metaboxes.php.backup.2025-11-18-211233"

# Find and remove all .backup files
find . -name "*.backup*" -type f | while read -r backup_file; do
    remove_file "$backup_file"
done

echo ""
echo "🗂️  Step 3: Removing outdated/old files..."
echo "-------------------------------------------"

# Remove OLD files (but not from vendor/node_modules)
find . -name "*OLD*" -type f ! -path "*/vendor/*" ! -path "*/node_modules/*" | while read -r old_file; do
    remove_file "$old_file"
done

# Remove OUTDATED files
find . -name "OUTDATED-*" -type f | while read -r outdated_file; do
    remove_file "$outdated_file"
done

# Remove DEPRECATED files
find . -name "*DEPRECATED*" -type f | while read -r deprecated_file; do
    remove_file "$deprecated_file"
done

echo ""
echo "🔧 Step 4: Removing temporary/test files..."
echo "-------------------------------------------"

# Remove debug/test files that shouldn't be in repo
remove_file "apollo-events-manager/debug-text.php"
remove_file "apollo-events-manager/db-test.php"
remove_file "apollo-social/test-playbook.php"
remove_file "apollo-social/demo-dj-contacts.php"
remove_file "apollo-social/workflow-integration-example.php"

# Remove temporary commit message files
remove_file "apollo-core/.git-commit-message.txt"

echo ""
echo "📄 Step 5: Removing redundant documentation..."
echo "-------------------------------------------"

# These are implementation-specific docs that can be consolidated
# Keeping them for now, but you can uncomment to remove:
# remove_file "apollo-social/HOLD-TO-CONFIRM-GUIDE.md"
# remove_file "apollo-social/HOLD-TO-CONFIRM-IMPLEMENTATION.md"
# remove_file "apollo-social/IMPLEMENTATION-COMPLETE.md"
# remove_file "apollo-social/IMPLEMENTATION-STATUS.md"
# remove_file "apollo-social/P0-11-IMPLEMENTATION-REPORT.md"
# remove_file "apollo-social/PHASE-1-COMPLETE.md"
# remove_file "apollo-social/PHASE-2-COMPLETE.md"

echo ""
echo "✅ Step 6: Update .gitignore..."
echo "-------------------------------------------"

# Create/update .gitignore
cat > .gitignore << 'EOF'
# WordPress
wp-config.php
wp-content/uploads/
wp-content/cache/
wp-content/backup-db/
wp-content/advanced-cache.php
wp-content/wp-cache-config.php
wp-content/blogs.dir/
wp-content/upgrade/

# Plugin Development
*.zip
*.backup*
*OLD*
*OUTDATED-*
*DEPRECATED*
.git-commit-message.txt

# IDE
.vscode/
.idea/
*.swp
*.swo
*~
.DS_Store
Thumbs.db

# Node
node_modules/
npm-debug.log
yarn-error.log
package-lock.json

# PHP
vendor/
composer.lock
*.log

# Testing
phpunit.xml
.phpunit.result.cache
tests/_output/*
tests/_support/_generated

# Build
dist/
build/
*.map

# Temporary
tmp/
temp/
*.tmp
EOF

echo "  ✅ Created/Updated .gitignore"

echo ""
echo "========================================"
echo "🎉 Cleanup Complete!"
echo "========================================"
echo ""
echo "📊 Summary:"
echo "  - Files removed from git: $REMOVED_COUNT"
echo "  - .gitignore updated: ✅"
echo ""
echo "📝 Next steps:"
echo "  1. Review changes: git status"
echo "  2. Commit changes: git commit -m 'chore: cleanup repository - remove zips, backups, and outdated files'"
echo "  3. Push to GitHub: git push origin main"
echo ""

