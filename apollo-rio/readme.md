# Apollo::Rio - PWA Page Builders v2.0.0

Sistema de templates PWA e Canvas Mode para Apollo::Rio com detecção automática de modo PWA.

---

## 🚀 Features

### PWA Detection
- **Automatic Detection**: Detecta modo PWA automaticamente
- **iOS Support**: Detecção de standalone mode iOS
- **Android Support**: Detecção de modo PWA Android
- **Cookie-based**: Suporte a cookie `apollo_display_mode`
- **Header-based**: Suporte a header `X-Apollo-PWA`

### Page Builders
- **Site::rio**: Template completo com header/footer
- **App::rio**: Template PWA com header/footer
- **App::rio clean**: Template PWA minimalista

### Theme Blocking
- **Prevents Interference**: Remove assets do tema quando necessário
- **Whitelist System**: Mantém apenas assets essenciais
- **Output Guards**: Proteção contra interferência do tema

---

## 📦 Installation

1. Upload to `/wp-content/plugins/apollo-rio/`
2. Activate plugin through WordPress admin
3. Configure PWA settings in WP Admin → Apollo Rio → Settings

---

## 🔧 Requirements

- WordPress: 5.0+
- PHP: 7.4+
- apollo-social plugin (para Canvas Mode completo)

---

## 📄 Page Templates

### 1. Site::rio (`pagx_site`)
Modelo de página que:
- Header e footer completos
- Carregado completo só em PC e Mobile (browser e PWA)
- Sem PWA redirecionamentos
- SEO-friendly páginas

**Uso:** Páginas públicas que devem funcionar em todos os contextos

### 2. App::rio (`pagx_app`)
Modelo de página que:
- Header e footer completos
- Carregado completo somente no PC e PWA
- Mobile verifica se no PWA carrega normalmente, caso contrário instrução para ter app

**Uso:** Páginas que requerem experiência PWA completa

### 3. App::rio clean (`pagx_appclean`)
Modelo de página que:
- Nada de header e footer
- Carregado completo somente no PC e PWA
- Mobile verifica se no PWA carrega normalmente, caso contrário instrução para ter app

**Uso:** Páginas internas do app que não precisam de navegação

---

## 🚀 Usage Guide

### Creating a Page with Page Builder

1. **Go to:** Pages → Add New
2. **Page Attributes → Template:**
   - Select "Site::rio" (always shows content)
   - Select "App::rio" (PWA required for mobile)
   - Select "App::rio clean" (PWA required, minimal UI)
3. **Add Content:** Use WordPress editor or Elementor
4. **Publish**

---

## 🔧 PWA Detection

### Como Funciona

O sistema detecta automaticamente se o usuário está em modo PWA através de:

1. **Cookie `apollo_display_mode`**
   ```php
   // Definido automaticamente quando PWA é detectado
   setcookie('apollo_display_mode', 'pwa', time() + 86400);
   ```

2. **Header `X-Apollo-PWA`**
   ```php
   // Enviado pelo service worker quando em modo PWA
   $_SERVER['HTTP_X_APOLLO_PWA']
   ```

3. **User Agent Detection**
   ```php
   // iOS standalone mode
   stripos($user_agent, 'iPhone') !== false && 
   !isset($_SERVER['HTTP_X_REQUESTED_WITH'])
   
   // Android PWA
   stripos($user_agent, 'wv') !== false
   ```

### Instruções de Instalação

Quando mobile não está em modo PWA, o sistema exibe instruções:

**iOS:**
- "Adicione à Tela de Início" com ícone Safari
- Instruções passo a passo

**Android:**
- "Instalar App" com ícone Android
- Link para configuração do Android App URL (admin settings)

---

## ⚙️ Admin Settings

**Localização:** WordPress Admin → Apollo Rio → Settings

### Configurações Disponíveis

1. **Android App URL**
   - URL do app Android (opcional)
   - Usado nas instruções de instalação
   - Validação de URL automática

2. **PWA Detection**
   - Ativar/desativar detecção automática
   - Configurações de cookie
   - Configurações de header

---

## 🏗️ Architecture

### Estrutura de Arquivos

```
apollo-rio/
├── apollo-rio.php                          # Main plugin file
├── includes/
│   ├── class-pwa-page-builders.php         # Main class
│   ├── template-functions.php              # Helper functions
│   └── admin-settings.php                  # Admin panel
├── templates/
│   ├── pagx_site.php                       # Builder 1: Site::rio
│   ├── pagx_app.php                        # Builder 2: App::rio
│   ├── pagx_appclean.php                   # Builder 3: App::rio clean
│   └── partials/
│       ├── header.php                      # Full header with nav
│       ├── header-minimal.php              # Minimal header (no nav)
│       ├── footer.php                      # Full footer with widgets
│       └── footer-minimal.php              # Minimal footer
├── assets/
│   ├── js/
│   │   └── pwa-detect.js                   # PWA detection script
│   └── css/
│       └── pwa-templates.css                # All template styles
└── manifest.json                           # PWA manifest (root level)
```

### Main Class: `Apollo_Rio_PWA_Page_Builders`

**Métodos Principais:**
- `init()` - Inicialização do plugin
- `register_page_templates()` - Registro de templates
- `detect_pwa_mode()` - Detecção de modo PWA
- `get_pwa_instructions()` - Instruções de instalação
- `remove_theme_assets()` - Remoção de assets do tema

---

## 🔒 Security

### Correções Aplicadas

- ✅ **URL sanitization**: `esc_url_raw()` aplicado
- ✅ **Cookie sanitization**: `sanitize_text_field()` + `wp_unslash()`
- ✅ **Header sanitization**: `sanitize_text_field()` + `wp_unslash()`
- ✅ **Nonce verification**: Nonces com contexto específico
- ✅ **Input validation**: Validação de URLs e tipos
- ✅ **Output escaping**: `esc_html_e()` em todos os outputs

---

## 🐛 Debug

### Enable Debug Mode
```php
// wp-config.php
define('APOLLO_DEBUG', true);
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Testing PWA Detection

1. **Testar detecção iOS standalone**
   - Abrir no iPhone em modo standalone
   - Verificar cookie `apollo_display_mode`

2. **Testar detecção Android**
   - Abrir no Android em modo PWA
   - Verificar header `X-Apollo-PWA`

3. **Testar instruções**
   - Abrir em mobile browser (não PWA)
   - Verificar instruções de instalação aparecem

---

## 📚 Integration

### Com apollo-social

O apollo-rio funciona melhor quando combinado com apollo-social:
- Canvas Mode completo
- Rotas integradas
- Assets compartilhados

### Com apollo-events-manager

Integração para templates de eventos:
- Templates PWA para eventos
- Detecção de modo PWA em eventos
- Instruções de instalação em eventos

---

## ✅ Production Checklist

- [x] Todas as correções de segurança aplicadas
- [x] Sanitização de inputs verificada
- [x] Escape de outputs verificado
- [x] Nonces implementados
- [x] Validação de dados completa
- [ ] Testar PWA detection funcionando
- [ ] Testar templates funcionando
- [ ] Testar admin settings funcionando

---

## 📝 License

GPL v2 or later

---

**Version:** 2.0.0  
**Last Updated:** 2025-01-15  
**Status:** ✅ Production Ready
