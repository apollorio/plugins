# 🛠️ Guia de Desenvolvimento - Apollo Plugins

**Última Atualização:** 2025-01-15

---

## 📋 ÍNDICE

1. [Quick Start](#quick-start)
2. [Estrutura do Projeto](#estrutura-do-projeto)
3. [Convenções de Código](#convenções-de-código)
4. [Hooks e Filters](#hooks-e-filters)
5. [API e Shortcodes](#api-e-shortcodes)
6. [Templates e Assets](#templates-e-assets)
7. [Debug e Testes](#debug-e-testes)
8. [Troubleshooting](#troubleshooting)

---

## 🚀 QUICK START

### 1. Abrir Workspace
```bash
# Abrir workspace no Cursor/VSCode
Duplo-clique em: apollo-plugins.code-workspace
```

### 2. Verificar Configuração
```bash
# Verificar localização
pwd
# Esperado: C:\Users\rafae\Local Sites\1212\app\public\wp-content\plugins

# Verificar Git
git status
```

### 3. Ativar Debug
```php
// wp-config.php
define('APOLLO_DEBUG', true);
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

---

## 📁 ESTRUTURA DO PROJETO

### apollo-events-manager
```
apollo-events-manager/
├── apollo-events-manager.php    # Arquivo principal
├── includes/
│   ├── post-types.php           # CPTs e taxonomias
│   ├── admin-metaboxes.php      # Meta boxes admin
│   ├── ajax-handlers.php        # Handlers AJAX
│   ├── cache.php                # Helpers de cache
│   └── event-helpers.php        # Funções auxiliares
├── templates/
│   ├── event-card.php           # Card de evento (MASTER)
│   ├── portal-discover.php      # Portal de eventos
│   └── single-event-page.php    # Página de evento
└── assets/
    ├── js/                      # JavaScript
    └── css/                     # CSS (fallback local)
```

### apollo-social
```
apollo-social/
├── apollo-social.php            # Arquivo principal
├── src/
│   ├── Core/                   # Classes principais
│   ├── Infrastructure/         # Serviços e providers
│   ├── Modules/               # Módulos funcionais
│   └── Plugin.php             # Classe principal
├── config/                     # Arquivos de configuração
├── templates/                  # Templates WordPress
└── assets/                    # CSS, JS, imagens
```

### apollo-rio
```
apollo-rio/
├── apollo-rio.php              # Arquivo principal
├── includes/
│   ├── class-pwa-page-builders.php  # Main class
│   ├── template-functions.php      # Helper functions
│   └── admin-settings.php          # Admin panel
├── templates/
│   ├── pagx_site.php          # Builder 1: Site::rio
│   ├── pagx_app.php           # Builder 2: App::rio
│   └── pagx_appclean.php      # Builder 3: App::rio clean
└── assets/
    ├── js/
    └── css/
```

---

## 📝 CONVENÇÕES DE CÓDIGO

### PHP (PSR-12 + WordPress Standards)

#### Classes
```php
// PascalCase com prefixo do plugin
class Apollo_Events_Manager_Plugin {}
class Apollo_Social_Core {}
class Apollo_Rio_PWA_Builder {}
```

#### Métodos
```php
// camelCase
public function saveCustomEventFields() {}
public function enqueueAssets() {}
```

#### Hooks
```php
// snake_case com prefixo
add_action('apollo_events_ajax', ...);
add_filter('apollo_social_canvas_init', ...);
```

#### Constantes
```php
// UPPER_SNAKE_CASE
define('APOLLO_DEBUG', true);
define('APOLLO_WPEM_VERSION', '2.0.0');
```

### JavaScript (ES6+)

#### Funções
```javascript
// camelCase
function toggleLayout(el) {}
function handleEventClick(event) {}
```

#### Variáveis
```javascript
// camelCase
const displayDate = new Date();
const eventCards = document.querySelectorAll('.event_listing');
```

#### Constantes
```javascript
// UPPER_SNAKE_CASE
const MONTH_NAMES = ['jan', 'fev', 'mar', ...];
const API_ENDPOINT = '/wp-admin/admin-ajax.php';
```

---

## 🔧 HOOKS E FILTERS

### Actions Disponíveis

#### Plugin Lifecycle
```php
// Após plugin ser carregado
do_action('apollo_events_loaded');
do_action('apollo_social_loaded');
```

#### Event Actions
```php
// Antes de exibir event card
do_action('apollo_before_event_card', $event_id);

// Depois de exibir event card
do_action('apollo_after_event_card', $event_id);

// Quando view é trackado
do_action('apollo_event_view_tracked', $event_id, $type);
```

#### Canvas Mode
```php
// Canvas Mode initialization
do_action('apollo_canvas_init');
do_action('apollo_canvas_head');
do_action('apollo_canvas_footer');
```

### Filters Disponíveis

#### Event Display
```php
// Modificar título do evento
apply_filters('apollo_event_title', $title, $event_id);

// Modificar args da query de eventos
apply_filters('apollo_events_query_args', $args);

// Modificar template path
apply_filters('apollo_event_template_path', $path, $template_name);
```

#### Canvas Mode
```php
// Modificar whitelist de assets no canvas mode
apply_filters('apollo_canvas_keep_styles', $keep_styles);
apply_filters('apollo_canvas_keep_scripts', $keep_scripts);

// Modificar body classes
apply_filters('apollo_body_classes', $classes);
```

---

## 📚 API E SHORTCODES

### Funções Públicas

#### Event Data Functions
```php
// Get event meta (safe wrapper)
apollo_get_post_meta($post_id, $meta_key, $single = false);

// Get event lineup
apollo_get_event_lineup($event_id); // Returns array of DJs

// Get primary local ID
apollo_get_primary_local_id($event_id); // Returns local post ID
```

#### Template Functions
```php
// Load template part
apollo_get_template_part($slug, $name = '', $args = array());

// Locate template
apollo_locate_template($template_name);
```

### Shortcodes

#### Events
```php
[events]                    // Main events listing
[event id="123"]           // Single event
[past_events]              // Past events only
[upcoming_events]          // Future events only
[related_events]           // Related to current event
```

#### DJs
```php
[event_djs]                // DJs listing
[event_dj id="456"]        // Single DJ
```

#### Locals
```php
[event_locals]             // Locals listing
[event_local id="789"]     // Single local
```

#### Forms
```php
[submit_event_form]        // Event submission form
[event_dashboard]          // User event dashboard
```

---

## 🎨 TEMPLATES E ASSETS

### Template Hierarchy

**apollo-events-manager:**
1. `single-event-standalone.php` - Para eventos individuais
2. `portal-discover.php` - Para `/eventos/` ou arquivo de eventos
3. Template padrão do tema

**STRICT MODE:** Força uso dos templates do plugin independente do tema ativo

### Assets Externos (CDN)

#### CSS
```php
// uni.css (UNIVERSAL & MAIN CSS)
'https://assets.apollo.rio.br/uni.css'

// RemixIcon
'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css'
```

#### JavaScript
```php
// Apollo Scripts
'https://assets.apollo.rio.br/base.js'        // Event portal pages
'https://assets.apollo.rio.br/event-page.js'  // Single event pages

// Framer Motion
'https://cdn.jsdelivr.net/npm/framer-motion@11.0.0/dist/framer-motion.js'

// Leaflet (maps)
'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
```

### Carregamento de Assets

```php
// Condicional: apenas em páginas relevantes
if (is_singular('event_listing') || is_post_type_archive('event_listing')) {
    wp_enqueue_style('apollo-uni-css', 'https://assets.apollo.rio.br/uni.css');
    wp_enqueue_script('apollo-base-js', 'https://assets.apollo.rio.br/base.js');
}
```

---

## 🐛 DEBUG E TESTES

### Debug Mode

```php
// wp-config.php
define('APOLLO_DEBUG', true);
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Logging

```php
// No código
if (APOLLO_DEBUG) {
    error_log('✅ Success');
    error_log('❌ Error: ' . $error_message);
    error_log('⚠️ Warning: ' . $warning_message);
}
```

### Verificar Logs

```bash
# Ver últimos erros
tail -20 ../wp-content/debug.log | grep -i "error\|fatal"

# Monitorar log em tempo real
tail -f ../wp-content/debug.log
```

### WP-CLI Testing

```bash
# Listar eventos
wp post list --post_type=event_listing

# Contar eventos
wp post list --post_type=event_listing --format=count

# Flush rewrite rules
wp rewrite flush

# Verificar CPTs
wp post-type list | grep event
```

---

## 🔍 TROUBLESHOOTING

### Problema: Assets não carregam

**Solução:**
1. Verificar se página está correta (`is_singular('event_listing')`)
2. Verificar se shortcode está presente na página
3. Verificar console do navegador para erros
4. Verificar se CDN está acessível

### Problema: Templates não aparecem

**Solução:**
1. Verificar STRICT MODE ativado
2. Verificar se template existe em `templates/`
3. Flush rewrite rules: `wp rewrite flush`
4. Limpar cache do WordPress

### Problema: AJAX não funciona

**Solução:**
1. Verificar nonce correto
2. Verificar action name correto
3. Verificar `admin-ajax.php` acessível
4. Verificar console do navegador para erros

### Problema: Meta keys não salvam

**Solução:**
1. Verificar hook `save_post_{post_type}` registrado
2. Verificar capability do usuário
3. Verificar sanitização dos dados
4. Verificar logs de debug

---

## 📖 RECURSOS ADICIONAIS

### Documentação por Plugin
- **apollo-events-manager:** Ver `apollo-events-manager/README.md`
- **apollo-social:** Ver `apollo-social/README.md`
- **apollo-rio:** Ver `apollo-rio/README.md`

### Links Úteis
- [WordPress Codex](https://developer.wordpress.org/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [PSR-12 Coding Style Guide](https://www.php-fig.org/psr/psr-12/)
- [Leaflet Documentation](https://leafletjs.com/)
- [RemixIcon](https://remixicon.com/)

---

**Última Atualização:** 2025-01-15

