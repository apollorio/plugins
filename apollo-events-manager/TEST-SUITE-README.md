# 🧪 Apollo Events Manager - Test Suite

Suite completa de testes para validação do plugin Apollo Events Manager.

## 📋 Arquivos de Teste

### 1. `tests/debug-test.php`
**Teste completo de todas as funcionalidades**

Acesse via browser: `/wp-content/plugins/apollo-events-manager/tests/debug-test.php`

**Testa:**
- ✅ Conexão com banco de dados
- ✅ Custom Post Types (event_listing, event_dj, event_local)
- ✅ Shortcodes registrados
- ✅ Meta keys canônicas
- ✅ AJAX handlers
- ✅ Templates existentes
- ✅ Assets (CSS/JS)
- ✅ User roles (clubber)

**Saída:** Relatório HTML completo com status de cada teste

---

### 2. `tests/db-test.php`
**Teste de conexão e estrutura do banco de dados**

Acesse via browser: `/wp-content/plugins/apollo-events-manager/tests/db-test.php`

**Testa:**
- ✅ Conexão direta com MySQL
- ✅ Tabelas do WordPress
- ✅ Custom Post Types no banco
- ✅ Meta keys canônicas
- ✅ Meta keys legadas (para migração)

**Configuração:**
```php
DB_HOST: localhost:10005
DB_NAME: local
DB_USER: root
DB_PASS: root
```

---

### 3. `tests/page-verification.php`
**Verificação de páginas, shortcodes e CPTs**

Acesse via browser: `/wp-content/plugins/apollo-events-manager/tests/page-verification.php`

**Testa:**
- ✅ Shortcodes e seus outputs
- ✅ Páginas de Custom Post Types
- ✅ Páginas de arquivo
- ✅ Arquivos de template

**Saída:** Lista completa com links para testar cada página

---

## 🚀 Como Usar

### Via Browser (Recomendado)
1. Acesse `http://localhost:10004/wp-content/plugins/apollo-events-manager/tests/debug-test.php`
2. Veja o relatório completo de testes
3. Verifique quaisquer falhas ou avisos

### Via CLI
```bash
cd wp-content/plugins/apollo-events-manager
php tests/debug-test.php > test-results.html
```

---

## 🔧 Configuração Xdebug

### Verificar se Xdebug está ativo:
```php
<?php
if (function_exists('xdebug_info')) {
    xdebug_info();
}
?>
```

### Configurações Recomendadas (php.ini):
```ini
xdebug.mode=debug,develop
xdebug.start_with_request=yes
xdebug.max_nesting_level=256
xdebug.max_stack_frames=-1
xdebug.output_dir=C:\Windows\Temp
```

---

## 📊 Interpretando os Resultados

### Status dos Testes:
- ✅ **PASS** (Verde) - Teste passou com sucesso
- ❌ **FAIL** (Vermelho) - Teste falhou, ação necessária
- ⚠️ **WARNING** (Amarelo) - Aviso, pode ser normal
- ℹ️ **INFO** (Azul) - Informação adicional

### Taxa de Sucesso:
- **100%** - Todos os testes passaram ✅
- **< 100%** - Alguns testes falharam, revisar ⚠️

---

## 🐛 Debugging

### Ativar Debug no WordPress:
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('APOLLO_PORTAL_DEBUG', true);
```

### Logs:
- WordPress: `/wp-content/debug.log`
- Xdebug: `C:\Windows\Temp\`

---

## ✅ Checklist de Testes

Antes de fazer deploy, execute todos os testes:

- [ ] `debug-test.php` - Todos os testes passando
- [ ] `db-test.php` - Conexão OK, estrutura OK
- [ ] `page-verification.php` - Todas as páginas acessíveis
- [ ] Testar formulário de submissão manualmente
- [ ] Testar autenticação (registro/login)
- [ ] Testar portal de eventos
- [ ] Testar filtros e busca
- [ ] Testar lightbox modal
- [ ] Testar mobile responsivo

---

## 📝 Notas

- Os testes são **não-destrutivos** - não modificam dados
- Execute em ambiente de **desenvolvimento** primeiro
- Revise os resultados antes de fazer deploy
- Mantenha os testes atualizados conforme novas funcionalidades são adicionadas

---

**Versão:** 0.1.0  
**Última Atualização:** <?php echo date('d/m/Y'); ?>

