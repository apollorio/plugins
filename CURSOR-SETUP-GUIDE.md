# 🎯 Guia de Instalação - Cursor IDE para Apollo

**Data:** 8 de novembro de 2025  
**Projeto:** Apollo Social (LocalWP Site 1212)  
**Ambiente:** PHP 8.2.27, MySQL 8.0.35, Xdebug 3.2.1

---

## 📋 Pré-requisitos

- ✅ Cursor IDE instalado ([cursor.sh](https://cursor.sh))
- ✅ LocalWP rodando (Site 1212)
- ✅ Git configurado
- ✅ Node.js (opcional, para frontend)

---

## 🚀 Instalação Rápida (Automática)

### Windows (PowerShell):
```powershell
cd "C:\Users\rafae\Local Sites\1212\app\public\wp-content\plugins"
.\CURSOR-EXTENSIONS-INSTALL.ps1
```

### Linux/Mac (Bash):
```bash
cd "/c/Users/rafae/Local Sites/1212/app/public/wp-content/plugins"
bash CURSOR-EXTENSIONS-INSTALL.sh
```

---

## 🔧 Instalação Manual (se necessário)

### 1. Core Development Tools (3 extensões)
```bash
cursor --install-extension github.copilot
cursor --install-extension github.copilot-chat
cursor --install-extension github.vscode-pull-request-github
```

### 2. PHP Development (5 extensões)
```bash
cursor --install-extension bmewburn.vscode-intelephense-client
cursor --install-extension aequabit.php-cs-fixer
cursor --install-extension xdebug.php-debug
cursor --install-extension xdebug.php-pack
cursor --install-extension neilbrayfield.php-docblocker
```

### 3. Database Tools (3 extensões)
```bash
cursor --install-extension cweijan.vscode-mysql-client2
cursor --install-extension mtxr.sqltools
cursor --install-extension mtxr.sqltools-driver-mysql
```

### 4. WordPress Development (4 extensões)
```bash
cursor --install-extension wordpresstoolbox.wordpress-toolbox
cursor --install-extension johnbillion.vscode-wordpress-hooks
cursor --install-extension ryanwelcher.modern-wordpress-development-snippets
cursor --install-extension wordpressplayground.wordpress-playground
```

### 5. Frontend & Tailwind (4 extensões)
```bash
cursor --install-extension bradlc.vscode-tailwindcss
cursor --install-extension esbenp.prettier-vscode
cursor --install-extension formulahendry.auto-rename-tag
cursor --install-extension ecmel.vscode-html-css
```

### 6. Git & Project Management (4 extensões)
```bash
cursor --install-extension eamodio.gitlens
cursor --install-extension mhutchie.git-graph
cursor --install-extension alefragnani.project-manager
cursor --install-extension donjayamanne.githistory
```

### 7. UI & Quality of Life (4 extensões)
```bash
cursor --install-extension usernamehw.errorlens
cursor --install-extension pkief.material-icon-theme
cursor --install-extension gruntfuggly.todo-tree
cursor --install-extension mikestead.dotenv
```

### 8. ShadCN & Modern UI (3 extensões)
```bash
cursor --install-extension akhil017.shadcn-ui-assist
cursor --install-extension emranweb.shadcnui-snippet
cursor --install-extension shakililham.remix-icon-snippets-for-html
```

---

## ⚙️ Configuração Pós-Instalação

### 1. Copiar Configurações do VS Code

O arquivo `.vscode/settings.json` já está configurado com:

```json
{
  "php.version": "8.2.27",
  "intelephense.stubs": ["wordpress", "acf-pro", "wp-event-manager"],
  "intelephense.files.maxSize": 5000000,
  "sqltools.connections": [{
    "name": "LocalWP - Apollo 1212",
    "driver": "MySQL",
    "server": "localhost",
    "port": 10005,
    "database": "local",
    "username": "root",
    "password": "root"
  }]
}
```

**Ação:** Apenas abra o Cursor no workspace e as configs serão aplicadas automaticamente! ✅

### 2. Configurar MySQL Client 2

1. Abrir extensão **Database Client** (ícone na sidebar)
2. Clicar em **"+ Add Connection"**
3. Preencher:
   - Host: `localhost`
   - Port: `10005`
   - User: `root`
   - Password: `root`
   - Database: `local`
4. Salvar e testar conexão

### 3. Configurar Xdebug

O arquivo `.vscode/launch.json` já está configurado:

```json
{
  "name": "Listen for Xdebug (LocalWP Apollo 1212)",
  "type": "php",
  "request": "launch",
  "port": 9003,
  "hostname": "localhost",
  "log": true
}
```

**Para usar:**
1. Colocar breakpoint em arquivo PHP
2. Pressionar **F5** (Start Debugging)
3. Recarregar página no navegador
4. Debugger pausará no breakpoint

### 4. Indexar Workspace (Intelephense)

1. `Ctrl+Shift+P` → `Intelephense: Index Workspace`
2. Aguardar indexação completa (~2 minutos)
3. IntelliSense estará pronto para uso

---

## 📦 Extensões Instaladas (30 total)

| Categoria | Extensões | Quantidade |
|-----------|-----------|------------|
| Core Dev | Copilot, Copilot Chat, GitHub PR | 3 |
| PHP | Intelephense, PHP-CS-Fixer, Xdebug, DocBlocker | 5 |
| Database | MySQL Client 2, SQLTools | 3 |
| WordPress | Toolbox, Hooks, Snippets, Playground | 4 |
| Frontend | Tailwind CSS, Prettier, Auto Rename Tag, HTML CSS | 4 |
| Git | GitLens, Git Graph, Project Manager, Git History | 4 |
| UI/QoL | Error Lens, Material Icons, TODO Tree, DotEnv | 4 |
| ShadCN | UI Assist, Snippets, Remix Icons | 3 |

---

## 🎯 Extensões NÃO Instaladas (por redundância)

As seguintes extensões do VS Code **NÃO foram incluídas** no Cursor por serem:
- Redundantes (múltiplos PHP CS Fixers)
- Específicas de outros frameworks (Laravel, Vue, React em excesso)
- Desnecessárias para Apollo (Java, Spring, Kubernetes, Azure, Python extras)

**Mantivemos apenas o essencial para Apollo Social!**

---

## ✅ Checklist Final

Após instalação, verificar:

- [ ] Cursor aberto no workspace plugins
- [ ] IntelliSense PHP funcionando (testar ctrl+space em arquivo PHP)
- [ ] MySQL conectado (ver 55 tabelas WordPress)
- [ ] Xdebug ativo (ver status na barra inferior)
- [ ] Tailwind IntelliSense ativo (autocomplete de classes CSS)
- [ ] GitLens mostrando histórico (gutter annotations)
- [ ] Error Lens mostrando erros inline
- [ ] Material Icons nos arquivos

---

## 🆘 Troubleshooting

### Problema: "cursor: command not found"
**Solução:** Adicionar Cursor ao PATH
```bash
# Windows (PowerShell como Admin)
$env:Path += ";C:\Users\$env:USERNAME\AppData\Local\Programs\cursor\resources\app\bin"

# Linux/Mac
echo 'export PATH="$PATH:~/.cursor/bin"' >> ~/.bashrc
source ~/.bashrc
```

### Problema: Intelephense não funciona
**Solução:**
1. `Ctrl+Shift+P` → `Intelephense: Clear Cache and Reload`
2. Verificar se PHP 8.2.27 está configurado em settings.json

### Problema: MySQL não conecta
**Solução:**
1. Verificar LocalWP rodando (site 1212 ativo)
2. Testar conexão via terminal:
```bash
mysql -h localhost -P 10005 -u root -proot local
```

### Problema: Xdebug não para em breakpoints
**Solução:**
1. Verificar Xdebug ativo: `php -m | grep xdebug`
2. Verificar porta 9003 livre: `netstat -an | grep 9003`
3. Reiniciar LocalWP

---

## 🎓 Dicas de Produtividade

### Atalhos Cursor Essenciais:
- `Ctrl+P` - Quick Open (arquivo)
- `Ctrl+Shift+P` - Command Palette
- `Ctrl+G` - Go to Line
- `Ctrl+D` - Select Next Occurrence
- `Alt+Up/Down` - Move Line
- `Ctrl+/` - Toggle Comment
- `F5` - Start Debugging
- `Ctrl+Space` - IntelliSense

### GitHub Copilot:
- `Tab` - Accept suggestion
- `Ctrl+Enter` - Open Copilot suggestions panel
- `Ctrl+I` - Inline chat (Cursor AI)
- `Ctrl+K` - Command mode (Cursor AI)

### Tailwind CSS:
- Digitar classe → autocomplete automático
- Hover sobre classe → ver CSS gerado
- `@apply` no CSS → autocomplete de classes Tailwind

---

## 📚 Recursos Adicionais

- [Cursor Documentation](https://docs.cursor.sh)
- [Intelephense Documentation](https://intelephense.com)
- [Xdebug Setup Guide](https://xdebug.org/docs/install)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)

---

**Pronto! Cursor configurado e 100% compatível com o ambiente Apollo! 🚀✨**
