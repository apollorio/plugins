# ⚡ QUICK START - Apollo Plugins Workspace

**5 minutos para começar a desenvolver!**

---

## 🚀 PASSO 1: ABRIR WORKSPACE

### Opção A: Workspace File (Recomendado)
```
Duplo-clique em: apollo-plugins.code-workspace
```

### Opção B: Open Folder
```
VSCode/Cursor > File > Open Folder...
Selecionar: C:\Users\rafae\Local Sites\1212\app\public\wp-content\plugins
```

---

## ✅ PASSO 2: VERIFICAR CONFIGURAÇÃO

### Abrir Terminal (Ctrl+`)
```powershell
# Verificar localização
pwd
# Esperado: C:\Users\rafae\Local Sites\1212\app\public\wp-content\plugins

# Verificar Git
git status
# Esperado: On branch main

# Verificar estrutura
ls
# Esperado: apollo-events-manager, wp-event-manager, etc
```

---

## 🎯 PASSO 3: ENTENDER A ESTRUTURA

```
plugins/                        ← VOCÊ ESTÁ AQUI
├── apollo-events-manager/      ← TRABALHAR AQUI
│   ├── apollo-events-manager.php
│   ├── includes/
│   ├── templates/
│   ├── assets/
│   └── docs/
├── wp-event-manager/           ← Legacy (não mexer)
├── wpem-bookmarks/             ← Legacy (não mexer)
└── wpem-rest-api/              ← Legacy (não mexer)
```

---

## 📝 PASSO 4: CONHECER OS META KEYS

### Events (event_listing)
```php
// DJs - SEMPRE unserialize!
$djs = maybe_unserialize(get_post_meta($id, '_event_dj_ids', true));

// Local - INT
$local_id = get_post_meta($id, '_event_local_ids', true);

// Banner - URL (NÃO é attachment ID!)
$banner = get_post_meta($id, '_event_banner', true);
```

### ❌ NÃO USAR:
```php
get_post_meta($id, '_event_djs', true);      // ❌ Wrong key!
get_post_meta($id, '_event_local', true);    // ❌ Wrong key!
get_post_meta($id, '_event_venue', true);    // ❌ Removed!
```

---

## 🎨 PASSO 5: ASSETS EXTERNOS

### Sempre usar assets.apollo.rio.br:
```php
// CSS (todos)
wp_enqueue_style('apollo-uni-css', 'https://assets.apollo.rio.br/uni.css');

// JS Portal (listagem)
wp_enqueue_script('apollo-base-js', 'https://assets.apollo.rio.br/base.js');

// JS Single (evento individual)
wp_enqueue_script('apollo-event-page-js', 'https://assets.apollo.rio.br/event-page.js');
```

### ❌ NÃO criar arquivos JS locais!

---

## 🧪 PASSO 6: TESTAR

### WP-CLI
```bash
# Listar plugins
wp plugin list

# Listar eventos
wp post list --post_type=event_listing

# Flush rewrite
wp rewrite flush
```

### Debug Log
```bash
# Ver erros
tail -20 ../wp-content/debug.log | grep -i error
```

---

## 🐛 PASSO 7: ATIVAR DEBUG

### wp-config.php
```php
define('APOLLO_DEBUG', true);
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### No código
```php
if (APOLLO_DEBUG) {
    error_log('✅ Success');
    error_log('❌ Error');
    error_log('⚠️ Warning');
}
```

---

## 📚 PASSO 8: LER DOCUMENTAÇÃO

### Essenciais:
1. `.copilot-instructions.md` - Contexto Copilot
2. `README.md` - Visão geral
3. `apollo-events-manager/docs/` - Docs completas

---

## 🎯 WORKFLOW TÍPICO

### 1. Criar Branch
```bash
git checkout -b feature/nova-funcionalidade
```

### 2. Desenvolver
```bash
cd apollo-events-manager
code apollo-events-manager.php
```

### 3. Testar
```bash
wp plugin activate apollo-events-manager
# Testar no navegador
```

### 4. Commit
```bash
git add .
git commit -m "feat: Nova funcionalidade"
```

### 5. Push
```bash
git push origin feature/nova-funcionalidade
```

---

## ⚡ COMANDOS RÁPIDOS

### Git
```bash
git status                    # Ver mudanças
git diff                      # Ver diferenças
git log --oneline            # Ver histórico
```

### WP-CLI
```bash
wp plugin list               # Listar plugins
wp post-type list           # Listar CPTs
wp rewrite flush            # Flush rewrite
```

### Debug
```bash
tail -f ../wp-content/debug.log    # Monitorar log
```

---

## 🆘 TROUBLESHOOTING

### Terminal não abre em /plugins
```json
// .vscode/settings.json
{
  "terminal.integrated.cwd": "${workspaceFolder}"
}
```

### Git não funciona
```bash
cd apollo-events-manager
git status
```

### Copilot sugere código errado
- Verificar `.copilot-instructions.md`
- Recarregar: Ctrl+Shift+P > Reload Window

---

## ✅ CHECKLIST

- [ ] Workspace aberto em /plugins
- [ ] Terminal abre em /plugins
- [ ] Git funciona
- [ ] Conhece meta keys corretos
- [ ] Sabe usar assets externos
- [ ] Debug ativado
- [ ] Documentação lida

---

## 🎉 PRONTO!

Agora você pode:
- ✅ Desenvolver em `apollo-events-manager/`
- ✅ Usar Copilot com contexto correto
- ✅ Fazer commits e push
- ✅ Testar com WP-CLI
- ✅ Debug com logs

**Happy Coding! 🚀**

---

**Dúvidas?** Consulte:
- `.copilot-instructions.md`
- `README.md`
- `apollo-events-manager/docs/`


