# Script de instalação de extensões para Cursor IDE (PowerShell)
# Execute este script no PowerShell: .\CURSOR-EXTENSIONS-INSTALL.ps1

Write-Host "🚀 Instalando extensões essenciais no Cursor..." -ForegroundColor Cyan
Write-Host ""

# ============================================
# ESSENCIAIS - DESENVOLVIMENTO APOLLO
# ============================================
Write-Host "📦 [1/8] Core Development Tools..." -ForegroundColor Yellow
& cursor --install-extension github.copilot
& cursor --install-extension github.copilot-chat
& cursor --install-extension github.vscode-pull-request-github

Write-Host "📦 [2/8] PHP Development..." -ForegroundColor Yellow
& cursor --install-extension bmewburn.vscode-intelephense-client
& cursor --install-extension aequabit.php-cs-fixer
& cursor --install-extension xdebug.php-debug
& cursor --install-extension xdebug.php-pack
& cursor --install-extension neilbrayfield.php-docblocker

Write-Host "📦 [3/8] Database Tools..." -ForegroundColor Yellow
& cursor --install-extension cweijan.vscode-mysql-client2
& cursor --install-extension mtxr.sqltools
& cursor --install-extension mtxr.sqltools-driver-mysql

Write-Host "📦 [4/8] WordPress Development..." -ForegroundColor Yellow
& cursor --install-extension wordpresstoolbox.wordpress-toolbox
& cursor --install-extension johnbillion.vscode-wordpress-hooks
& cursor --install-extension ryanwelcher.modern-wordpress-development-snippets
& cursor --install-extension wordpressplayground.wordpress-playground

Write-Host "📦 [5/8] Frontend & Tailwind..." -ForegroundColor Yellow
& cursor --install-extension bradlc.vscode-tailwindcss
& cursor --install-extension esbenp.prettier-vscode
& cursor --install-extension formulahendry.auto-rename-tag
& cursor --install-extension ecmel.vscode-html-css

Write-Host "📦 [6/8] Git & Project Management..." -ForegroundColor Yellow
& cursor --install-extension eamodio.gitlens
& cursor --install-extension mhutchie.git-graph
& cursor --install-extension alefragnani.project-manager
& cursor --install-extension donjayamanne.githistory

Write-Host "📦 [7/8] UI & Quality of Life..." -ForegroundColor Yellow
& cursor --install-extension usernamehw.errorlens
& cursor --install-extension pkief.material-icon-theme
& cursor --install-extension gruntfuggly.todo-tree
& cursor --install-extension mikestead.dotenv

Write-Host "📦 [8/8] ShadCN & Modern UI..." -ForegroundColor Yellow
& cursor --install-extension akhil017.shadcn-ui-assist
& cursor --install-extension emranweb.shadcnui-snippet
& cursor --install-extension shakililham.remix-icon-snippets-for-html

Write-Host ""
Write-Host "✅ Instalação concluída!" -ForegroundColor Green
Write-Host ""
Write-Host "🔧 Próximos passos:" -ForegroundColor Cyan
Write-Host "1. Reiniciar o Cursor (Ctrl+Shift+P > 'Reload Window')"
Write-Host "2. Configurar Intelephense: Ctrl+Shift+P > 'Intelephense: Index Workspace'"
Write-Host "3. Configurar MySQL Client: localhost:10005 / local / root / root"
Write-Host "4. Abrir arquivo: .vscode/settings.json (já configurado)"
Write-Host ""
Write-Host "📁 Workspace Apollo pronto para uso!" -ForegroundColor Green
