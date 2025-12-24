# Apollo Ecosystem - GitHub Repository Cleanup Script (PowerShell)
# Removes unnecessary files from git tracking

$ErrorActionPreference = "Continue"

$REPO_ROOT = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
Set-Location $REPO_ROOT

Write-Host "🧹 Apollo Ecosystem - Repository Cleanup" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Repository: $REPO_ROOT"
Write-Host ""

$REMOVED_COUNT = 0

# Function to remove file from git
function Remove-GitFile {
    param([string]$file)
    
    if (Test-Path $file) {
        try {
            git rm --cached "$file" 2>$null
            if ($LASTEXITCODE -eq 0) {
                Write-Host "  🗑️  Removed from git: $file" -ForegroundColor Yellow
                $script:REMOVED_COUNT++
            }
        } catch {
            # File might not be tracked
        }
    }
}

Write-Host "📦 Step 1: Removing duplicate .zip files..." -ForegroundColor Green
Write-Host "-------------------------------------------"

Remove-GitFile "apollo-events-manager (2).zip"
Remove-GitFile "apollo-events-manager (3).zip"
Remove-GitFile "apollo-events-manager (4).zip"

Write-Host ""
Write-Host "📝 Step 2: Removing backup files..." -ForegroundColor Green
Write-Host "-------------------------------------------"

Remove-GitFile "apollo-events-manager/includes/admin-metaboxes.php.backup.2025-11-18-211233"

# Find and remove all .backup files
Get-ChildItem -Recurse -Filter "*.backup*" -File | ForEach-Object {
    Remove-GitFile $_.FullName.Replace("$REPO_ROOT\", "").Replace("\", "/")
}

Write-Host ""
Write-Host "🗂️  Step 3: Removing outdated/old files..." -ForegroundColor Green
Write-Host "-------------------------------------------"

# Remove OLD files (but not from vendor/node_modules)
Get-ChildItem -Recurse -Filter "*OLD*" -File | 
    Where-Object { $_.FullName -notmatch "vendor|node_modules" } | 
    ForEach-Object {
        Remove-GitFile $_.FullName.Replace("$REPO_ROOT\", "").Replace("\", "/")
    }

# Remove OUTDATED files
Get-ChildItem -Recurse -Filter "OUTDATED-*" -File | ForEach-Object {
    Remove-GitFile $_.FullName.Replace("$REPO_ROOT\", "").Replace("\", "/")
}

# Remove DEPRECATED files
Get-ChildItem -Recurse -Filter "*DEPRECATED*" -File | ForEach-Object {
    Remove-GitFile $_.FullName.Replace("$REPO_ROOT\", "").Replace("\", "/")
}

Write-Host ""
Write-Host "🔧 Step 4: Removing temporary/test files..." -ForegroundColor Green
Write-Host "-------------------------------------------"

$tempFiles = @(
    "apollo-events-manager/debug-text.php",
    "apollo-events-manager/db-test.php",
    "apollo-social/test-playbook.php",
    "apollo-social/demo-dj-contacts.php",
    "apollo-social/workflow-integration-example.php"
)

foreach ($file in $tempFiles) {
    Remove-GitFile $file
}

Write-Host ""
Write-Host "✅ Step 5: Update .gitignore..." -ForegroundColor Green
Write-Host "-------------------------------------------"

$gitignoreContent = @"
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
"@

Set-Content -Path ".gitignore" -Value $gitignoreContent
Write-Host "  ✅ Created/Updated .gitignore" -ForegroundColor Green

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "🎉 Cleanup Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "📊 Summary:" -ForegroundColor Cyan
Write-Host "  - Files removed from git: $REMOVED_COUNT"
Write-Host "  - .gitignore updated: ✅"
Write-Host ""
Write-Host "📝 Next steps:" -ForegroundColor Yellow
Write-Host "  1. Review changes: git status"
Write-Host "  2. Commit changes: git commit -m 'chore: cleanup repository - remove zips, backups, and outdated files'"
Write-Host "  3. Push to GitHub: git push origin main"
Write-Host ""

