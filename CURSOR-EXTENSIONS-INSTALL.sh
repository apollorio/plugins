#!/bin/bash
# Script de instalação de extensões para Cursor IDE
# Execute este script no terminal do Cursor: bash CURSOR-EXTENSIONS-INSTALL.sh

echo "🚀 Instalando extensões essenciais no Cursor..."
echo ""

# ============================================
# ESSENCIAIS - DESENVOLVIMENTO APOLLO
# ============================================
echo "📦 [1/8] Core Development Tools..."
cursor --install-extension github.copilot
cursor --install-extension github.copilot-chat
cursor --install-extension github.vscode-pull-request-github

echo "📦 [2/8] PHP Development..."
cursor --install-extension bmewburn.vscode-intelephense-client
cursor --install-extension aequabit.php-cs-fixer
cursor --install-extension xdebug.php-debug
cursor --install-extension xdebug.php-pack
cursor --install-extension neilbrayfield.php-docblocker

echo "📦 [3/8] Database Tools..."
cursor --install-extension cweijan.vscode-mysql-client2
cursor --install-extension mtxr.sqltools
cursor --install-extension mtxr.sqltools-driver-mysql

echo "📦 [4/8] WordPress Development..."
cursor --install-extension wordpresstoolbox.wordpress-toolbox
cursor --install-extension johnbillion.vscode-wordpress-hooks
cursor --install-extension ryanwelcher.modern-wordpress-development-snippets
cursor --install-extension wordpressplayground.wordpress-playground

echo "📦 [5/8] Frontend & Tailwind..."
cursor --install-extension bradlc.vscode-tailwindcss
cursor --install-extension esbenp.prettier-vscode
cursor --install-extension formulahendry.auto-rename-tag
cursor --install-extension ecmel.vscode-html-css

echo "📦 [6/8] Git & Project Management..."
cursor --install-extension eamodio.gitlens
cursor --install-extension mhutchie.git-graph
cursor --install-extension alefragnani.project-manager
cursor --install-extension donjayamanne.githistory

echo "📦 [7/8] UI & Quality of Life..."
cursor --install-extension usernamehw.errorlens
cursor --install-extension pkief.material-icon-theme
cursor --install-extension gruntfuggly.todo-tree
cursor --install-extension mikestead.dotenv

echo "📦 [8/8] ShadCN & Modern UI..."
cursor --install-extension akhil017.shadcn-ui-assist
cursor --install-extension emranweb.shadcnui-snippet
cursor --install-extension shakililham.remix-icon-snippets-for-html

echo ""
echo "✅ Instalação concluída!"
echo ""
echo "🔧 Próximos passos:"
echo "1. Reiniciar o Cursor (Ctrl+Shift+P > 'Reload Window')"
echo "2. Configurar Intelephense: Ctrl+Shift+P > 'Intelephense: Index Workspace'"
echo "3. Configurar MySQL Client: localhost:10005 / local / root / root"
echo "4. Abrir arquivo: .vscode/settings.json (já configurado)"
echo ""
echo "📁 Workspace Apollo pronto para uso!"
