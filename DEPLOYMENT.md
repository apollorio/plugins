# 🚀 Guia de Deploy - Apollo Plugins

**Última Atualização:** 2025-01-15

---

## 📋 ÍNDICE

1. [Pré-Deploy Checklist](#pré-deploy-checklist)
2. [Scripts de Deploy](#scripts-de-deploy)
3. [Processo de Deploy](#processo-de-deploy)
4. [Pós-Deploy](#pós-deploy)
5. [Rollback](#rollback)

---

## ✅ PRÉ-DEPLOY CHECKLIST

### Verificações de Código

- [ ] Todos os erros críticos resolvidos
- [ ] Código duplicado eliminado
- [ ] Sanitização de inputs verificada
- [ ] Escape de outputs verificado
- [ ] Nonces em todos os AJAX handlers
- [ ] Capability checks implementados
- [ ] Validação de dados completa

### Verificações de Funcionalidade

- [ ] Todos os shortcodes funcionando
- [ ] Templates renderizando corretamente
- [ ] AJAX handlers respondendo
- [ ] Assets carregando do CDN
- [ ] Canvas Mode funcionando
- [ ] PWA Detection funcionando

### Verificações de Segurança

- [ ] Sem SQL injection vulnerabilities
- [ ] Sem XSS vulnerabilities
- [ ] CSRF protection ativo
- [ ] Sanitização de URLs
- [ ] Validação de tipos
- [ ] Escape de dados do usuário

---

## 📦 SCRIPTS DE DEPLOY

### Scripts Disponíveis

#### 1. Deploy Individual

**apollo-events-manager:**
```powershell
cd apollo-events-manager
.\create-production-zip.ps1
```

**apollo-social:**
```powershell
cd apollo-social
.\create-production-zip.ps1
```

**apollo-rio:**
```powershell
cd apollo-rio
.\create-production-zip.ps1
```

#### 2. Deploy Todos os Plugins

```powershell
cd "C:\Users\rafae\Local Sites\1212\app\public\wp-content\plugins"
.\create-all-plugins-zip.ps1
```

### O que os Scripts Fazem

1. **Excluem arquivos de desenvolvimento:**
   - `.git/`
   - `node_modules/`
   - `*.md` (exceto README.md)
   - Arquivos de teste
   - Arquivos temporários

2. **Criam ZIP de produção:**
   - Nome: `{plugin-name}-v{version}.zip`
   - Contém apenas arquivos necessários
   - Estrutura limpa e organizada

---

## 🚀 PROCESSO DE DEPLOY

### 1. Preparação Local

```bash
# 1. Verificar status do Git
git status

# 2. Criar branch de release (opcional)
git checkout -b release/v2.0.0

# 3. Executar scripts de deploy
.\create-all-plugins-zip.ps1
```

### 2. Backup do Servidor

**Antes de fazer deploy, fazer backup completo:**

- [ ] Banco de dados WordPress
- [ ] Pasta `wp-content/plugins/`
- [ ] Arquivo `wp-config.php`
- [ ] Uploads (`wp-content/uploads/`)

### 3. Upload dos Plugins

**Opção A: Via FTP/SFTP**
1. Conectar ao servidor
2. Navegar para `wp-content/plugins/`
3. Fazer backup da pasta atual
4. Upload dos ZIPs
5. Descompactar cada plugin

**Opção B: Via WordPress Admin**
1. WP Admin → Plugins → Add New
2. Upload Plugin
3. Escolher arquivo ZIP
4. Install Now
5. Activate Plugin

### 4. Ordem de Ativação

**Importante:** Ativar na ordem correta:

1. **apollo-rio** (base PWA)
2. **apollo-social** (core social)
3. **apollo-events-manager** (events manager)

### 5. Configuração Pós-Instalação

```bash
# Via WP-CLI (recomendado)
wp rewrite flush
wp cache flush

# Ou via WordPress Admin
# Settings → Permalinks → Save Changes
```

---

## ✅ PÓS-DEPLOY

### Verificações Imediatas

- [ ] Plugins ativados corretamente
- [ ] Sem erros no log do WordPress
- [ ] Páginas principais carregando
- [ ] Assets do CDN carregando
- [ ] Shortcodes funcionando

### Testes Funcionais

#### apollo-events-manager
- [ ] Página `/eventos/` carregando
- [ ] Cards de eventos exibindo
- [ ] Filtros funcionando
- [ ] Modal de evento abrindo
- [ ] Favoritos funcionando

#### apollo-social
- [ ] Canvas Mode funcionando
- [ ] Rotas `/feed/`, `/chat/` funcionando
- [ ] Páginas `/id/{userID}` funcionando
- [ ] Analytics configurado

#### apollo-rio
- [ ] PWA Detection funcionando
- [ ] Templates PWA carregando
- [ ] Theme blocking ativo

### Monitoramento

```bash
# Verificar logs de erro
tail -f wp-content/debug.log

# Verificar performance
# Usar ferramentas como:
# - Query Monitor (plugin WordPress)
# - New Relic
# - Google PageSpeed Insights
```

---

## 🔄 ROLLBACK

### Se algo der errado:

#### 1. Desativar Plugins Problemáticos

```bash
# Via WP-CLI
wp plugin deactivate apollo-events-manager
wp plugin deactivate apollo-social
wp plugin deactivate apollo-rio
```

#### 2. Restaurar Backup

```bash
# Restaurar plugins antigos
# Restaurar banco de dados se necessário
```

#### 3. Investigar Problema

```bash
# Verificar logs
tail -100 wp-content/debug.log

# Verificar erros PHP
# Verificar conflitos com outros plugins
# Verificar versão do WordPress/PHP
```

#### 4. Corrigir e Re-deploy

```bash
# Corrigir problema localmente
# Testar localmente
# Criar novo ZIP
# Fazer deploy novamente
```

---

## 📊 CHECKLIST FINAL

### Antes do Deploy
- [ ] Código revisado
- [ ] Testes locais passando
- [ ] Scripts de deploy executados
- [ ] ZIPs criados corretamente
- [ ] Backup do servidor feito

### Durante o Deploy
- [ ] Plugins enviados ao servidor
- [ ] Plugins descompactados
- [ ] Plugins ativados na ordem correta
- [ ] Rewrite rules atualizadas

### Após o Deploy
- [ ] Funcionalidades testadas
- [ ] Sem erros nos logs
- [ ] Performance verificada
- [ ] Usuários notificados (se necessário)

---

## 🆘 SUPORTE

### Problemas Comuns

**Problema:** Plugin não ativa
- Verificar versão do PHP (requer 7.4+)
- Verificar versão do WordPress (requer 5.0+)
- Verificar logs de erro

**Problema:** Assets não carregam
- Verificar CDN acessível
- Verificar permissões de arquivos
- Limpar cache do navegador

**Problema:** Templates não aparecem
- Flush rewrite rules
- Limpar cache do WordPress
- Verificar STRICT MODE ativado

### Contato

- **GitHub Issues:** https://github.com/apollorio/plugins/issues
- **Email:** dev@apollo.rio.br

---

**Última Atualização:** 2025-01-15

