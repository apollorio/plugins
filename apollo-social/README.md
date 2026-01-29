# Apollo Social Core v2.0.0

Plugin principal do sistema Apollo que fornece funcionalidades sociais e de Canvas Mode para o WordPress.

---

## 🚀 Features

### Canvas Mode
- **Theme-Independent Rendering**: Removes ALL theme assets
- **Isolated Experience**: Only Apollo assets load
- **Automatic Activation**: Activates on specific Apollo routes

### Sistema de Grupos
- **Comunidades**: Comunidades e núcleos com gestão de membros
- **Moderation**: Sistema de moderação (approve/reject)
- **Group Policies**: Políticas de acesso configuráveis

### Sistema de Eventos
- **Integration**: Criação e gestão de eventos integrados
- **REST API**: Endpoints para integração com aplicativos móveis

### Sistema de Documentos
- **Document Management**: Gestão completa de documentos
- **Digital Signatures**: Integração com GOV.BR (stub)

### Analytics
- **Plausible Integration**: Tracking de engajamento respeitando privacidade
- **Custom Events**: Eventos customizados para grupos, eventos, anúncios
- **Dashboard**: Dashboard compartilhado opcional

### PWA
- **Progressive Web App**: Funcionalidades de PWA
- **Service Worker**: Suporte offline

### User Pages
- **Customizable Profiles**: Páginas personalizáveis `/id/{userID}`
- **Auto-creation**: Criação automática ao registrar
- **Drag-and-Drop Editor**: Editor com widgets

---

## 📦 Installation

1. Upload to `/wp-content/plugins/apollo-social/`
2. Activate plugin through WordPress admin
3. Configure features in WP Admin → Apollo

---

## 🔧 Requirements

- WordPress: 5.0+
- PHP: 7.4+
- Rewrite rules habilitadas

---

## 🎨 Canvas Mode

O Canvas Mode é um sistema de renderização que:
- Remove todos os assets do tema ativo
- Carrega apenas assets essenciais do plugin
- Fornece interface limpa e focada
- Ativa automaticamente em rotas específicas do Apollo

### Rotas que Ativam Canvas Mode:
- `/a/*` - Páginas gerais do Apollo
- `/comunidade/*` - Páginas de comunidades
- `/nucleo/*` - Páginas de núcleos
- `/season/*` - Páginas de temporadas
- `/membership` - Página de associação
- `/uniao/*` - Páginas da união
- `/anuncio/*` - Páginas de anúncios
- `/feed/` - Feed social
- `/chat/` - Chat
- `/id/{userID}` - Perfis de usuário
- `/eco/` e `/ecoa/` - Diretório de usuários

---

## 📊 Analytics

### Configuração do Plausible

1. **Acesse o painel administrativo**: WP Admin → Apollo → Analytics
2. **Configure suas credenciais**:
   - **Domain**: Seu domínio no Plausible (ex: `meusite.com`)
   - **API Key**: Chave da API do Plausible (opcional, para dashboard)
   - **Site ID**: ID do site no Plausible (opcional, para dashboard)
3. **Ative o tracking**: Marque "Ativar Analytics" e salve

### Eventos Customizados

O sistema rastreia automaticamente:

#### Grupos e Comunidades
- `group_view` - Visualização de página de grupo
- `group_join` - Usuário se junta a um grupo
- `group_leave` - Usuário deixa um grupo
- `invite_sent` - Convite para grupo enviado

#### Eventos
- `event_view` - Visualização de evento
- `event_create` - Criação de novo evento
- `event_filter_applied` - Filtro aplicado na listagem
- `event_share` - Compartilhamento de evento

#### Anúncios
- `ad_view` - Visualização de anúncio
- `ad_create` - Criação de novo anúncio
- `ad_contact` - Contato através de anúncio

#### Navegação
- `page_view` - Visualização de página (automático)
- `membership_view` - Visualização da página de associação

### Tracking Manual

```php
// No PHP (server-side)
apollo_track_event('custom_event', [
    'page' => get_the_title(),
    'user_type' => 'premium'
]);

// No JavaScript (client-side)
apolloAnalytics.track('custom_event', {
    page: document.title,
    section: 'header'
});
```

### Configurações de Privacidade

O sistema respeita:
- **GDPR/LGPD**: Sem cookies, dados anônimos
- **Do Not Track**: Respeita header DNT do navegador
- **IP Anonymization**: IPs são anonimizados por padrão
- **Opt-out**: Usuários podem desativar via configuração do navegador

---

## 🏗️ Architecture

### Estrutura de Arquivos

```
apollo-social/
├── src/
│   ├── Core/              # Classes principais
│   ├── Infrastructure/    # Serviços e providers
│   ├── Domain/            # Entidades de domínio
│   ├── Application/       # Casos de uso
│   ├── Modules/           # Módulos funcionais
│   │   ├── Builder/       # Page builder (SiteOrigin optional)
│   │   ├── Documents/     # Sistema de documentos
│   │   ├── UserPages/     # Páginas de usuário
│   │   └── Signatures/    # Assinaturas digitais
│   └── Plugin.php         # Classe principal
├── config/                # Arquivos de configuração
│   ├── analytics.php      # Configuração de analytics
│   ├── canvas.php         # Configuração de canvas mode
│   ├── routes.php         # Rotas do sistema
│   └── ui.php             # Configuração de UI
├── assets/                # CSS, JS, imagens
├── templates/             # Templates do WordPress
└── public/               # Assets públicos
```

### Service Providers

O plugin usa o padrão Service Provider para organização:

```php
// Registrar novo provider
$providers = [
    new CoreServiceProvider(),
    new AnalyticsServiceProvider(),
    new YourCustomProvider(),
];
```

---

## 🔧 Hooks Disponíveis

### Canvas Mode
```php
do_action('apollo_canvas_init');
do_action('apollo_canvas_head');
do_action('apollo_canvas_footer');
```

### Analytics
```php
do_action('apollo_analytics_init');
apply_filters('apollo_analytics_events', $events);
apply_filters('apollo_analytics_config', $config);
```

### Groups
```php
do_action('apollo_group_created', $group_id);
do_action('apollo_group_joined', $group_id, $user_id);
do_action('apollo_group_left', $group_id, $user_id);
```

---

## 📚 Rotas Implementadas

### Canvas Routes
- `/feed/` - Feed Social Apollo
- `/chat/` - Lista de Conversas
- `/chat/{userID}` - Chat com Usuário Específico
- `/id/{userID}` - Perfil de Usuário por ID
- `/eco/` e `/ecoa/` - Diretório de Usuários
- `/comunidade/` - Diretório de comunidades
- `/nucleo/` - Diretório de núcleos
- `/season/` - Diretório de seasons

### User Pages
- `/id/{userID}` - Perfil público personalizável
- Auto-criação ao registrar usuário
- Editor drag-and-drop com widgets

---

## 🔒 Security

- Sanitização de inputs (`sanitize_text_field`, `esc_html`, `esc_url`)
- Escape de outputs (`esc_html`, `esc_url`, `wp_kses_post`)
- Nonces em endpoints AJAX
- Capability checks
- Validação de tipos e permissões
- Proteção contra directory traversal

---

## 🐛 Debug

### Enable Debug Mode
```php
// wp-config.php
define('APOLLO_DEBUG', true);
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Logging
```php
if (APOLLO_DEBUG) {
    error_log('✅ Success');
    error_log('❌ Error: ' . $error_message);
}
```

---

## 📝 Status de Funcionalidades

### ✅ Implementado
- Canvas Mode completo
- Sistema de grupos (básico)
- User Pages (`/id/{userID}`)
- Analytics (Plausible)
- PWA support
- REST API endpoints

### ⚠️ Parcialmente Implementado
- Sistema de grupos (interface admin incompleta)
- Chat (módulo existe mas não funcional)
- Documentos (gestão básica)

### ❌ Não Implementado
- Feed social completo (posts sociais)
- Sistema de notificações
- Mensagens diretas funcionais

**Nota:** O sistema está focado em EVENTOS e perfis de usuário, não em rede social tradicional.

---

## 📚 Documentation

- **Canvas Builder:** Ver `CANVAS-BUILDER-README.md`
- **Status Rede Social:** Ver `STATUS-REDE-SOCIAL.md`
- **Main README:** Ver `../README.md`

---

## 📝 License

GPL v2 or later

---

**Version:** 2.0.0  
**Last Updated:** 2025-01-15  
**Status:** ✅ Production Ready
