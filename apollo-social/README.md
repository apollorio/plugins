# Apollo Social Core

Plugin principal do sistema Apollo que fornece funcionalidades sociais e de Canvas Mode para o WordPress.

## Funcionalidades

- **Canvas Mode**: Sistema de renderização isolada que remove assets do tema para experiência focada
- **Sistema de Grupos**: Comunidades e núcleos com gestão de membros
- **Sistema de Eventos**: Criação e gestão de eventos integrados
- **Sistema de Anúncios**: Marketplace de anúncios classificados
- **Analytics**: Integração com Plausible Analytics para tracking de engajamento
- **PWA**: Funcionalidades de Progressive Web App
- **API REST**: Endpoints para integração com aplicativos móveis

## Canvas Mode

O Canvas Mode é um sistema de renderização que:
- Remove todos os assets do tema ativo
- Carrega apenas assets essenciais do plugin
- Fornece interface limpa e focada
- Ativa automaticamente em rotas específicas do Apollo

### Rotas que ativam Canvas Mode:
- `/a/*` - Páginas gerais do Apollo
- `/comunidade/*` - Páginas de comunidades
- `/nucleo/*` - Páginas de núcleos
- `/season/*` - Páginas de temporadas
- `/membership` - Página de associação
- `/uniao/*` - Páginas da união
- `/anuncio/*` - Páginas de anúncios

## Sistema de Analytics

O Apollo Social Core inclui integração completa com Plausible Analytics para tracking de engajamento respeitando a privacidade dos usuários.

### Configuração do Plausible

1. **Acesse o painel administrativo**: WP Admin → Apollo → Analytics

2. **Configure suas credenciais**:
   - **Domain**: Seu domínio no Plausible (ex: `meusite.com`)
   - **API Key**: Chave da API do Plausible (opcional, para dashboard)
   - **Site ID**: ID do site no Plausible (opcional, para dashboard)

3. **Ative o tracking**: Marque "Ativar Analytics" e salve

### Dashboard Compartilhado (Opcional)

Para exibir o dashboard do Plausible dentro do WordPress:

1. No Plausible.io, acesse: Site Settings → Visibility → Make stats public
2. Copie o link público gerado
3. No WordPress: Apollo → Analytics → cole o link em "Dashboard URL"

### Eventos Customizados

O sistema rastreia automaticamente os seguintes eventos:

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

### Configuração Avançada

Edite `config/analytics.php` para configurações avançadas:

```php
// Personalizar eventos
'events' => [
    'custom_event' => [
        'enabled' => true,
        'description' => 'Meu evento customizado'
    ]
],

// Configurações de privacidade
'privacy' => [
    'respect_dnt' => true,        // Respeitar Do Not Track
    'exclude_ips' => ['127.0.0.1'], // IPs excluídos
    'hash_mode' => false          // Modo hash para IPs
]
```

### Tracking Manual

Para adicionar tracking customizado em templates:

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

## Instalação

1. Faça upload do plugin para `/wp-content/plugins/apollo-social/`
2. Ative o plugin através do menu 'Plugins' no WordPress
3. Configure as funcionalidades em WP Admin → Apollo

## Requisitos

- WordPress 5.0+
- PHP 7.4+
- Rewrite rules habilitadas

## Desenvolvimento

### Estrutura de Arquivos

```
apollo-social/
├── src/
│   ├── Core/              # Classes principais
│   ├── Infrastructure/    # Serviços e providers
│   ├── UI/               # Templates e componentes
│   └── Plugin.php        # Classe principal
├── config/               # Arquivos de configuração
├── assets/              # CSS, JS, imagens
├── templates/           # Templates do WordPress
└── public/             # Assets públicos
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

### Hooks Disponíveis

```php
// Canvas Mode
do_action('apollo_canvas_init');
do_action('apollo_canvas_head');
do_action('apollo_canvas_footer');

// Analytics
do_action('apollo_analytics_init');
apply_filters('apollo_analytics_events', $events);
apply_filters('apollo_analytics_config', $config);
```

## Suporte

Para suporte e documentação adicional, acesse o painel administrativo em WP Admin → Apollo.

## Licença

Este plugin é licenciado sob GPL v2 ou posterior.