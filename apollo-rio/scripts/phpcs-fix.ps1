# PHP CodeSniffer Auto-Fix Script for Apollo::Rio (PowerShell)
# Usage: .\scripts\phpcs-fix.ps1
# WARNING: This will modify your files. Make sure you have a backup or are using version control.

$phpcbfPath = "modules\pwa\vendor\bin\phpcbf"

# Check if phpcbf exists
if (-not (Test-Path $phpcbfPath)) {
    Write-Host "Error: phpcbf not found. Please run 'composer install' in modules/pwa directory first." -ForegroundColor Red
    exit 1
}

Write-Host "⚠️  WARNING: This will modify your PHP files!" -ForegroundColor Yellow
$confirmation = Read-Host "Press Ctrl+C to cancel, or Enter to continue"

# Run phpcbf with WordPress standards (limited auto-fix)
& $phpcbfPath `
    --standard=.phpcs.xml.dist `
    --ignore=vendor,node_modules,modules/pwa/vendor,modules/pwa/node_modules `
    --extensions=php `
    .

Write-Host ""
Write-Host "✅ PHPCBF auto-fix completed!" -ForegroundColor Green
Write-Host "Review the changes and run phpcs-check.ps1 again to verify." -ForegroundColor Yellow


