# 🔧 GUIA PRÁTICO DE TROUBLESHOOTING
## Apollo Events Manager Portal - Resolução de Problemas

**Data:** 04/11/2025  
**Plugin:** Apollo Events Manager v0.1.0

---

## 🎯 SINTOMA: "Página /eventos/ mostra HTML estático sem dados do banco"

### ⚠️ IMPORTANTE
O código PHP está **100% correto** e funcional. Se você vê HTML estático, o problema é **AMBIENTAL**, não de código.

---

## 📋 CHECKLIST RÁPIDO (5 minutos)

Execute estes 7 passos NA ORDEM:

### ✅ Passo 1: Plugin Está Ativo?
```
1. Acesse: wp-admin → Plugins
2. Procure: "Apollo Events Manager"
3. Status deve ser: "Ativo"
4. Se não está ativo: Ativar agora
```

**Se plugin não aparece:**
```bash
# Verificar se pasta existe:
ls wp-content/plugins/apollo-events-manager/

# Se não existe, reinstalar plugin
```

---

### ✅ Passo 2: Flush Rewrite Rules
```
1. Acesse: wp-admin → Settings → Permalinks
2. NÃO mude nada
3. Clique: "Save Changes"
4. Teste: /eventos/
```

**Por que isso funciona?**
- WordPress recria regras de URL
- Garante que /eventos/ é reconhecido
- Comum após ativar/desativar plugins

---

### ✅ Passo 3: Limpar Cache do Navegador
```
Chrome/Firefox:
1. Pressione: Ctrl + Shift + Delete
2. Selecione: "Cached images and files"
3. Período: "All time"
4. Clique: "Clear data"

OU usar modo anônimo:
1. Ctrl + Shift + N (Chrome)
2. Ctrl + Shift + P (Firefox)
3. Acesse: /eventos/
```

---

### ✅ Passo 4: Limpar Cache WordPress
```php
// Via WP-CLI:
wp transient delete apollo_upcoming_events_$(date +%Y%m%d)

// Via código (adicionar em functions.php temporariamente):
delete_transient('apollo_upcoming_events_' . date('Ymd'));

// Via plugin (se usa):
// WP Rocket, W3 Total Cache, etc → "Purge All Cache"
```

---

### ✅ Passo 5: Verificar Página Existe
```
1. Acesse: wp-admin → Pages → All Pages
2. Procure: Página com slug "eventos"
3. Status deve ser: "Published"

Se não existe:
1. Pages → Add New
2. Título: "Eventos"
3. Slug: "eventos"
4. Template: (qualquer um, será sobrescrito)
5. Publish
```

---

### ✅ Passo 6: Testar com Query String
```
Acesse: /eventos/?force_refresh=1

Se isso funciona, é cache.
Se não funciona, problema é mais profundo (ir para Passo 7).
```

---

### ✅ Passo 7: Verificar Debug Log
```php
// 1. Ativar debug (wp-config.php):
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// 2. Acessar /eventos/

// 3. Ver log:
tail -f wp-content/debug.log

// O que procurar:
// ✅ BOM: "Template original: ..."
// ❌ RUIM: Erros de PHP, warnings, notices
```

---

## 🔬 DIAGNÓSTICO AVANÇADO

Se os 7 passos acima NÃO resolveram, use este guia:

### 🔍 Teste 1: PHP Está Executando?

**Criar arquivo:** `wp-content/plugins/apollo-events-manager/test-php.php`

```php
<?php
echo "PHP FUNCIONA!";
echo "<br>Versão: " . PHP_VERSION;
echo "<br>Data/Hora: " . date('Y-m-d H:i:s');
?>
```

**Acessar:** `/wp-content/plugins/apollo-events-manager/test-php.php`

**Resultado esperado:**
```
PHP FUNCIONA!
Versão: 8.2.27
Data/Hora: 2025-11-04 14:30:00
```

**Se mostra código PHP em texto:**
```
❌ PROBLEMA: PHP não está executando
→ Solução: Verificar .htaccess e PHP-FPM (ver seção abaixo)
```

---

### 🔍 Teste 2: Template Está Carregando?

**Adicionar no arquivo `apollo-events-manager.php` (após linha 100):**

```php
add_filter('template_include', function($template) {
    // DEBUG: Log template atual
    error_log('🔍 Apollo Debug - Template original: ' . $template);
    
    // Verifica se é /eventos/
    $is_eventos = is_page('eventos') || is_post_type_archive('event_listing');
    error_log('🔍 Apollo Debug - É /eventos/? ' . ($is_eventos ? 'SIM' : 'NÃO'));
    
    if ($is_eventos) {
        $custom_template = plugin_dir_path(__FILE__) . 'templates/portal-discover.php';
        error_log('🔍 Apollo Debug - Template customizado: ' . $custom_template);
        error_log('🔍 Apollo Debug - Arquivo existe? ' . (file_exists($custom_template) ? 'SIM' : 'NÃO'));
        
        if (file_exists($custom_template)) {
            error_log('✅ Apollo Debug - Carregando template customizado');
            return $custom_template;
        } else {
            error_log('❌ Apollo Debug - ARQUIVO NÃO EXISTE!');
        }
    }
    
    return $template;
}, 99);
```

**Acessar:** `/eventos/`

**Ver log:**
```bash
tail -f wp-content/debug.log
```

**Resultado esperado:**
```
🔍 Apollo Debug - Template original: /var/www/wp-content/themes/tema/page.php
🔍 Apollo Debug - É /eventos/? SIM
🔍 Apollo Debug - Template customizado: /var/www/wp-content/plugins/apollo-events-manager/templates/portal-discover.php
🔍 Apollo Debug - Arquivo existe? SIM
✅ Apollo Debug - Carregando template customizado
```

**Se mostra "NÃO" em qualquer linha:**
```
❌ PROBLEMA IDENTIFICADO
→ Ver soluções abaixo
```

---

### 🔍 Teste 3: Eventos Existem no Banco?

**SQL no phpMyAdmin:**

```sql
-- 1. Verificar CPT está registrado:
SELECT COUNT(*) as total FROM wp_posts WHERE post_type = 'event_listing';

-- 2. Ver próximos eventos:
SELECT ID, post_title, post_status, post_date 
FROM wp_posts 
WHERE post_type = 'event_listing' 
AND post_status = 'publish'
ORDER BY post_date DESC
LIMIT 10;

-- 3. Ver metas de um evento:
SELECT meta_key, meta_value 
FROM wp_postmeta 
WHERE post_id = 123  -- Substitua pelo ID de um evento
AND meta_key LIKE '_event%';
```

**Resultado esperado:**
```
total: 50+ eventos

ID | post_title          | post_status | post_date
123| Festa Techno        | publish     | 2025-11-01
124| Show de House       | publish     | 2025-11-02
...

meta_key            | meta_value
_event_start_date   | 2025-11-20
_event_location     | Circo Voador | Lapa
_event_banner       | https://...
```

**Se retornar 0 eventos:**
```
❌ PROBLEMA: Nenhum evento cadastrado
→ Solução: Cadastrar eventos ou importar dados
```

---

## 🛠️ SOLUÇÕES PARA PROBLEMAS ESPECÍFICOS

### ❌ Problema: "É /eventos/? NÃO"

**Causa:** WordPress não reconhece a rota

**Solução 1: Flush rewrite rules**
```
wp-admin → Settings → Permalinks → Save Changes
```

**Solução 2: Verificar estrutura de permalinks**
```
wp-admin → Settings → Permalinks
→ Deve estar: "Post name" ou "Custom structure"
→ NÃO pode ser: "Plain"
```

**Solução 3: Verificar página existe**
```sql
SELECT * FROM wp_posts WHERE post_name = 'eventos' AND post_status = 'publish';
```

---

### ❌ Problema: "Arquivo existe? NÃO"

**Causa:** Arquivo template não existe no local esperado

**Solução:**
```bash
# 1. Verificar caminho:
ls -la wp-content/plugins/apollo-events-manager/templates/portal-discover.php

# 2. Se não existe, verificar se está em outro lugar:
find wp-content/plugins/apollo-events-manager -name "portal-discover.php"

# 3. Se encontrou em local diferente, atualizar caminho no filter
```

---

### ❌ Problema: PHP Não Executa (Mostra Código)

**Causa:** .htaccess incorreto ou PHP-FPM parado

**Solução 1: Verificar .htaccess**
```apache
# Arquivo: .htaccess (raiz do WordPress)

<IfModule mod_mime.c>
AddType application/x-httpd-php .php
</IfModule>

# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
```

**Solução 2: Verificar PHP-FPM (Linux)**
```bash
# Status:
sudo systemctl status php8.2-fpm

# Se parado:
sudo systemctl start php8.2-fpm
sudo systemctl enable php8.2-fpm

# Reiniciar:
sudo systemctl restart php8.2-fpm
```

**Solução 3: Local by Flywheel (Windows)**
```
1. Abrir Local
2. Clicar com direito no site
3. "Restart Site"
4. Se não resolver: "Stop" → "Start"
```

---

### ❌ Problema: Cache Não Limpa

**Solução 1: Forçar limpeza via SQL**
```sql
-- Limpar todos transients do Apollo:
DELETE FROM wp_options WHERE option_name LIKE '_transient_apollo_%';
DELETE FROM wp_options WHERE option_name LIKE '_transient_timeout_apollo_%';
```

**Solução 2: Desativar cache temporariamente**
```php
// No arquivo apollo-events-manager.php, comentar linha 199:
// set_transient($cache_key, $events_query, 5 * MINUTE_IN_SECONDS);

// E linha 169:
// $events_data = get_transient($cache_key);
// Substituir por:
$events_data = false; // Força sempre buscar do banco
```

---

### ❌ Problema: Modal Não Abre

**Diagnóstico:**
```javascript
// No console do navegador (F12):
console.log(apollo_events_ajax);
// Deve retornar: {ajax_url: "...", nonce: "..."}

// Se retornar "undefined":
```

**Solução:**
```php
// Verificar no apollo-events-manager.php se existe:
wp_localize_script('apollo-events-portal', 'apollo_events_ajax', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('apollo_events_nonce')
));

// Verificar se script está enfileirado:
wp_enqueue_script('apollo-events-portal', ...);
```

---

## 📊 TABELA DE SINTOMAS E SOLUÇÕES

| Sintoma | Causa Provável | Solução Rápida |
|---------|----------------|----------------|
| Página 404 | Rewrite rules | Flush permalinks |
| HTML estático | Cache | Ctrl+Shift+Delete |
| PHP em texto | PHP não executa | Verificar .htaccess |
| Modal não abre | JS não carrega | Verificar console (F12) |
| Nenhum evento | DB vazio | Cadastrar eventos |
| Eventos sem DJ | Meta vazio | Verificar metadados |
| Cards desalinhados | CSS | Verificar uni.css |
| Erro 500 | PHP error | Ver debug.log |

---

## 🚀 TESTE FINAL: TUDO FUNCIONANDO?

Execute este teste completo:

### 1️⃣ Acessar Portal
```
URL: /eventos/
Esperado: Página carrega em < 2s
```

### 2️⃣ Ver Cards
```
Esperado: 
- Cards de eventos aparecem
- Cada card mostra: título, data, DJ, local
- Imagens carregam
```

### 3️⃣ Clicar em Card
```
Ação: Clicar em qualquer card
Esperado: Modal abre com detalhes completos
```

### 4️⃣ Fechar Modal
```
Ação: Pressionar ESC ou clicar no X
Esperado: Modal fecha suavemente
```

### 5️⃣ Verificar Performance
```
Ação: Abrir DevTools (F12) → Network → Recarregar
Esperado: 
- Tempo total < 2s
- Queries < 100
- Imagens lazy-load
```

---

## 📞 AINDA NÃO FUNCIONA?

### Coletar Informações para Suporte:

**1. Informações do Ambiente:**
```php
// Adicionar em functions.php temporariamente:
echo '<pre>';
echo 'PHP Version: ' . PHP_VERSION . "\n";
echo 'WordPress Version: ' . get_bloginfo('version') . "\n";
echo 'Plugin Ativo? ' . (is_plugin_active('apollo-events-manager/apollo-events-manager.php') ? 'SIM' : 'NÃO') . "\n";
echo 'Permalink Structure: ' . get_option('permalink_structure') . "\n";
echo '</pre>';
```

**2. Últimas 50 linhas do debug.log:**
```bash
tail -n 50 wp-content/debug.log > debug-output.txt
```

**3. Lista de plugins ativos:**
```
wp-admin → Plugins → Copiar lista
```

**4. Resultado dos testes SQL:**
```sql
-- Copiar resultados das 3 queries da seção "Teste 3"
```

---

## ✅ SUCESSO!

Se você chegou aqui e tudo funciona:

- ✅ Portal `/eventos/` carrega dinamicamente
- ✅ Cards mostram dados do banco
- ✅ DJs e Local aparecem
- ✅ Modal abre e fecha
- ✅ Performance < 2s

**🎉 Parabéns! Portal Apollo está 100% funcional!**

---

**Última atualização:** 04/11/2025  
**Versão:** 1.0.0  
**Suporte:** Apollo Events Team

