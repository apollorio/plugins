# 🔍 Apollo Events Manager - Debug Checklist

## ✅ CORREÇÕES APLICADAS:

### 1. Config.php
- ❌ ANTES: Tinha lixo no final do arquivo (path do plugin)
- ✅ AGORA: Arquivo limpo, retorna array corretamente

### 2. Asset Enqueuing
- ❌ ANTES: Desenfil

eirava TUDO exceto apollo/leaflet
- ✅ AGORA: Whitelist mais inteligente (mantém jQuery, WP core, remixicon)
- ✅ AGORA: Debug helper mostra se CSS não carregou

### 3. Template Canvas
- ❌ ANTES: `strpos()` muito agressivo
- ✅ AGORA: Só remove temas (twenty*, theme*), mantém plugins

---

## 🧪 TESTE RÁPIDO:

### 1. Acesse a página:
```
http://localhost:10004/eventos/
```

### 2. Abra DevTools (F12):
- **Console**: Veja se há erros JS
- **Network**: Verifique se `uni.css` carregou (status 200)
- **Elements**: Inspect e veja se classes CSS existem

### 3. View Source (Ctrl+U):
Procure por:
```html
<link rel='stylesheet' id='apollo-events-uni-css' href='...uni.css' />
```

### 4. Verifique se shortcode está na página:
```
wp-admin → Pages → Eventos → Edit
```
Confirme que tem: `[apollo_events]`

---

## 🐛 SE CSS NÃO CARREGAR:

### Problema A: Assets não enfileiram
**Causa**: `should_enqueue_assets()` retorna false

**Debug**:
Adicione no `apollo-events-manager.php` linha 110:
```php
error_log('Apollo Assets Check: ' . ($this->should_enqueue_assets() ? 'YES' : 'NO'));
```

Veja o log em: `wp-content/debug.log`

### Problema B: Config retorna vazio
**Causa**: `apollo_cfg()` não carrega

**Debug**:
Adicione no shortcode linha 186:
```php
error_log('Config: ' . print_r($config, true));
```

### Problema C: Template desfaz enqueue
**Causa**: `apollo-canvas.php` ainda muito agressivo

**Teste**: Comente linhas 6-39 do `apollo-canvas.php` temporariamente

---

## 📊 VERIFICAÇÃO MANUAL:

### Arquivo uni.css existe?
```
wp-content/plugins/apollo-events-manager/assets/uni.css
```
✅ SIM (1997 linhas)

### Arquivo uni.js existe?
```
wp-content/plugins/apollo-events-manager/assets/uni.js
```
❓ VERIFICAR

### Plugin ativo?
```
wp-admin → Plugins → Apollo Events Manager
```
Status: ❓ VERIFICAR

---

## 🔧 COMANDOS ÚTEIS:

### Flush rewrite rules:
```bash
cd "c:\Users\rafae\Local Sites\1212\app\public"
wp rewrite flush
```

### Ver shortcodes na página:
```bash
wp post get $(wp post list --post_type=page --name=eventos --field=ID --format=ids) --field=post_content
```

### Verificar se assets enfileiram:
Adicione no `wp-config.php`:
```php
define('SCRIPT_DEBUG', true);
```

---

## 🎯 SOLUÇÃO RÁPIDA (Se nada funcionar):

### Opção 1: Force Enqueue
No `apollo-canvas.php` linha 25, ANTES de `wp_head()`:
```php
wp_enqueue_style('apollo-events-uni', APOLLO_WPEM_URL . 'assets/uni.css', [], APOLLO_WPEM_VERSION);
wp_enqueue_script('apollo-events-uni', APOLLO_WPEM_URL . 'assets/uni.js', ['jquery'], APOLLO_WPEM_VERSION, true);
```

### Opção 2: Inline CSS
No `apollo-canvas.php` linha 25, adicione:
```php
<link rel="stylesheet" href="<?php echo APOLLO_WPEM_URL; ?>assets/uni.css?v=<?php echo APOLLO_WPEM_VERSION; ?>">
```

### Opção 3: Debug Mode
No `apollo-events-manager.php` linha 107, sempre retorne true:
```php
public function enqueue_assets() {
    // FORCE ENQUEUE FOR DEBUG
    wp_enqueue_style('apollo-events-uni', APOLLO_WPEM_URL . 'assets/uni.css', [], APOLLO_WPEM_VERSION);
    wp_enqueue_script('apollo-events-uni', APOLLO_WPEM_URL . 'assets/uni.js', ['jquery'], APOLLO_WPEM_VERSION, true);
}
```

---

## 📝 PRÓXIMOS PASSOS:

1. ✅ Teste `http://localhost:10004/eventos/`
2. 🔍 Veja se CSS carrega (DevTools Network tab)
3. 📊 Se não carregar, cole output do View Source aqui
4. 🐛 Ative debug.log e veja erros

---

**Status**: ✅ Correções aplicadas
**Próximo**: Testar no navegador
**Se falhar**: Force enqueue (Opção 1 acima)

