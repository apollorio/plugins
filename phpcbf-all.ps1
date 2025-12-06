# phpcbf-all.ps1
Write-Host "╔═══════════════════════════════════════════════════════╗" -ForegroundColor Yellow
Write-Host "║    Apollo Plugins - PHPCBF Sequential Execution      ║" -ForegroundColor Yellow
Write-Host "╚═══════════════════════════════════════════════════════╝" -ForegroundColor Yellow
Write-Host ""

# PASS 1: WordPress Core
Write-Host "━━━ PASS 1/4: WordPress Core Standards ━━━" -ForegroundColor Yellow
phpcbf --standard=.phpcs-wordpress.xml --report=summary
Write-Host "✓ Pass 1 completed`n" -ForegroundColor Green

# PASS 2: WordPress-Extra
Write-Host "━━━ PASS 2/4: WordPress-Extra Standards ━━━" -ForegroundColor Yellow
phpcbf --standard=.phpcs-extra.xml --report=summary
Write-Host "✓ Pass 2 completed`n" -ForegroundColor Green

# PASS 3: WordPress-VIP-Go
Write-Host "━━━ PASS 3/4: WordPress-VIP-Go Standards ━━━" -ForegroundColor Yellow
phpcbf --standard=.phpcs-vip.xml --report=summary
Write-Host "✓ Pass 3 completed`n" -ForegroundColor Green

# PASS 4: PHPCompatibilityWP
Write-Host "━━━ PASS 4/4: PHP Compatibility Check (8.1+) ━━━" -ForegroundColor Yellow
phpcs --standard=.phpcs-compat.xml --report=summary
Write-Host "✓ Pass 4 completed`n" -ForegroundColor Green

Write-Host "╔═══════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║  ✓ Todos os 4 passes finalizados com sucesso!        ║" -ForegroundColor Green
Write-Host "╚═══════════════════════════════════════════════════════╝" -ForegroundColor Green
