# Clean Artifacts Script for Apollo Plugins (PowerShell)
# Removes .DS_Store, .idea directories, node_modules, and vendor directories
# Usage: .\scripts\clean-artifacts.ps1

$pluginsDir = "."

Write-Host "🧹 Cleaning artifacts from Apollo plugins..." -ForegroundColor Cyan
Write-Host ""

# Remove .DS_Store files
Write-Host "Removing .DS_Store files..." -ForegroundColor Yellow
Get-ChildItem -Path $pluginsDir -Filter ".DS_Store" -Recurse -Force -ErrorAction SilentlyContinue | Remove-Item -Force -ErrorAction SilentlyContinue
Write-Host "✅ .DS_Store files removed" -ForegroundColor Green

# Remove .idea directories
Write-Host "Removing .idea directories..." -ForegroundColor Yellow
Get-ChildItem -Path $pluginsDir -Filter ".idea" -Directory -Recurse -Force -ErrorAction SilentlyContinue | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue
Write-Host "✅ .idea directories removed" -ForegroundColor Green

# Remove node_modules directories (but keep modules/pwa/node_modules if needed)
Write-Host "Removing node_modules directories..." -ForegroundColor Yellow
Get-ChildItem -Path $pluginsDir -Filter "node_modules" -Directory -Recurse -Force -ErrorAction SilentlyContinue | 
    Where-Object { $_.FullName -notlike "*\modules\pwa\node_modules*" } | 
    Remove-Item -Recurse -Force -ErrorAction SilentlyContinue
Write-Host "✅ node_modules directories removed (preserved modules/pwa/node_modules)" -ForegroundColor Green

# Note: vendor directories are kept as they may be necessary for PHP dependencies
# Uncomment the following lines if you want to remove vendor directories:
# Write-Host "Removing vendor directories..." -ForegroundColor Yellow
# Get-ChildItem -Path $pluginsDir -Filter "vendor" -Directory -Recurse -Force -ErrorAction SilentlyContinue | 
#     Where-Object { $_.FullName -notlike "*\modules\pwa\vendor*" } | 
#     Remove-Item -Recurse -Force -ErrorAction SilentlyContinue
# Write-Host "✅ vendor directories removed (preserved modules/pwa/vendor)" -ForegroundColor Green

Write-Host ""
Write-Host "✨ Cleanup completed!" -ForegroundColor Green


