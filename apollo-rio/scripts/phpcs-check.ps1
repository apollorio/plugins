# PHP CodeSniffer Check Script for Apollo::Rio (PowerShell)
# Usage: .\scripts\phpcs-check.ps1

$phpcsPath = "modules\pwa\vendor\bin\phpcs"

# Check if phpcs exists
if (-not (Test-Path $phpcsPath)) {
    Write-Host "Error: phpcs not found. Please run 'composer install' in modules/pwa directory first." -ForegroundColor Red
    exit 1
}

# Run phpcs with WordPress standards
& $phpcsPath `
    --standard=.phpcs.xml.dist `
    --ignore=vendor,node_modules,modules/pwa/vendor,modules/pwa/node_modules `
    --extensions=php `
    .

Write-Host ""
Write-Host "✅ PHPCS check completed!" -ForegroundColor Green
Write-Host "To auto-fix issues, run: .\scripts\phpcs-fix.ps1" -ForegroundColor Yellow


