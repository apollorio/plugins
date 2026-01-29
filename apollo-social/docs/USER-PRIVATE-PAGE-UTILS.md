# 🏠 User Private Page (Dashboard) - Guia de Utilidades

> **Template Base:** `users/dashboard.php`
> **Canvas Route:** `/minha-pagina` ou `/dashboard`
> **Acesso:** Apenas usuário logado (owner) ou admin

---

## 📊 VISÃO GERAL DA ARQUITETURA

A página privada do usuário é o **centro de controle pessoal** onde o usuário gerencia:
- Perfil e ajustes de visibilidade
- Estatísticas da página pública
- Conteúdos criados (eventos, anúncios, documentos)
- Grupos e comunidades
- Relacionamentos sociais (close friends, bubble)
- Notificações e mensagens

---

## 🔧 FUNÇÕES HELPER DISPONÍVEIS

### Core User Functions

```php
// Obter página do usuário
$user_page = apollo_get_user_page( $user_id );          // WP_Post|null
$user_page = apollo_get_or_create_user_page( $user_id ); // WP_Post (cria se não existe)

// Config helper
$value = config( 'groups.comunidade.visibility' );  // Acesso a configs via dot notation
```

### User Identity & Membership

```php
use Apollo\Modules\Registration\CulturaRioIdentity;

// Identidades culturais do usuário
$identities = CulturaRioIdentity::getUserIdentities( $user_id );
// Returns: ['clubber', 'dj_amateur', 'producer_starter', ...]

// Status do membership
$membership = CulturaRioIdentity::getMembershipStatus( $user_id );
// Returns: [
//   'requested'    => ['dj_professional', 'event_producer_active'],
//   'status'       => 'pending' | 'approved' | 'rejected' | 'none',
//   'requested_at' => '2025-01-15 10:30:00',
//   'approved_at'  => '2025-01-20 14:00:00',
//   'approved_by'  => 1 (admin user ID)
// ]

// Identidades originais (primeira escolha - imutável)
$original = CulturaRioIdentity::getUserOriginalIdentities( $user_id );

// Jornada do membro (para mensagens de progressão)
$journey = CulturaRioIdentity::getMembershipJourney( $user_id );
// Returns: [
//   'started_as' => ['clubber', 'dj_amateur'],
//   'current' => ['clubber', 'dj_pro'],
//   'membership_status' => 'approved',
//   'registered_at' => '2024-06-01 00:00:00',
//   'progression_message' => 'Olha, você estava tentando ser DJ...'
// ]

// Label de identidade
$label = CulturaRioIdentity::getIdentityLabel( 'dj_amateur' );
// Returns: "DJ, aspirante/amador"
```

### User Badges

```php
// Badges do usuário para exibição social
$badges = apollo_social_get_user_badges( $user_id );
// Returns: [
//   ['class' => 'apollo', 'label' => 'Producer'],
//   ['class' => 'green', 'label' => 'DJ'],
//   ['class' => 'blue', 'label' => 'Govern']
// ]

// Mapa completo de badges
$badge_map = apollo_social_get_badge_map();
```

---

## 👤 USER META KEYS DISPONÍVEIS

### Perfil & Display

| Meta Key | Tipo | Descrição |
|----------|------|-----------|
| `apollo_user_page_id` | int | ID da página pública do usuário |
| `apollo_display_settings` | array | Configurações de visibilidade |
| `apollo_sounds` | array | Gêneros musicais favoritos |

### Cultura::Rio Identity

| Meta Key | Tipo | Descrição |
|----------|------|-----------|
| `apollo_cultura_identities` | array | Identidades culturais ativas |
| `apollo_cultura_registered_at` | datetime | Data de registro Cultura::Rio |
| `apollo_cultura_original_identities` | array | Identidades originais (imutável) |

### Membership Status

| Meta Key | Tipo | Descrição |
|----------|------|-----------|
| `apollo_membership_requested` | array | Memberships solicitados |
| `apollo_membership_status` | string | 'pending', 'approved', 'rejected', 'none' |
| `apollo_membership_requested_at` | datetime | Data da solicitação |
| `apollo_membership_approved_at` | datetime | Data da aprovação |
| `apollo_membership_approved_by` | int | ID do admin que aprovou |
| `_apollo_badges` | array | Array de slugs de badges |

---

## ⚙️ FEATURE VISIBILITY SETTINGS

O usuário pode controlar quais informações aparecem no perfil público:

```php
// Obter configurações de visibilidade
$settings = get_user_meta( $user_id, 'apollo_display_settings', true ) ?: [];

// Features disponíveis (de UserAjustesSection)
$features = [
    'show_sounds'             => true,   // Gêneros musicais
    'show_membership_badge'   => true,   // Badge de membro (requer aprovação)
    'show_dj_badge'           => true,   // Badge DJ (requer DJ + aprovação)
    'show_producer_badge'     => true,   // Badge Producer (requer Producer + aprovação)
    'show_cultura_identities' => true,   // Identidades Cultura::Rio
    'allow_messages'          => true,   // Permitir DMs
    'show_events_attended'    => false,  // Contador de eventos
];

// Verificar se feature está disponível para o usuário
function is_feature_available( $user_id, $feature_key ) {
    $status = CulturaRioIdentity::getMembershipStatus( $user_id );
    $is_approved = $status['status'] === 'approved';
    $identities = CulturaRioIdentity::getUserIdentities( $user_id );

    $is_dj = in_array( 'dj_amateur', $identities ) || in_array( 'dj_pro', $identities );
    $is_producer = in_array( 'producer_dreamer', $identities ) ||
                   in_array( 'producer_starter', $identities ) ||
                   in_array( 'producer_pro', $identities );

    $requirements = [
        'show_membership_badge' => $is_approved,
        'show_dj_badge'         => $is_approved && $is_dj,
        'show_producer_badge'   => $is_approved && $is_producer,
    ];

    return $requirements[$feature_key] ?? true;
}
```

---

## 📈 ESTATÍSTICAS DO USUÁRIO

### Dados disponíveis para exibição no dashboard

```php
// ========================
// LIKES/WOW RECEBIDOS
// ========================
// Tabela: wp_apollo_likes
global $wpdb;
$likes_table = $wpdb->prefix . 'apollo_likes';

// Total de likes recebidos (em conteúdos do usuário)
$total_likes = $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$likes_table} l
     INNER JOIN {$wpdb->posts} p ON l.content_id = p.ID AND l.content_type = 'post'
     WHERE p.post_author = %d",
    $user_id
) );

// Likes dados pelo usuário
$likes_given = $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$likes_table} WHERE user_id = %d",
    $user_id
) );

// ========================
// EVENTOS DE INTERESSE
// ========================
// Via WP Event Manager (adaptador)
$events_attending = get_posts([
    'post_type' => 'event_listing',
    'meta_query' => [
        [
            'key' => '_event_attendees',
            'value' => $user_id,
            'compare' => 'LIKE'
        ]
    ]
]);

// Eventos criados pelo usuário
$events_created = get_posts([
    'post_type'   => 'event_listing',
    'author'      => $user_id,
    'post_status' => ['publish', 'pending']
]);

// ========================
// DOCUMENTOS
// ========================
use Apollo\Modules\Documents\DocumentsRepository;

// Documentos do usuário
$user_documents = get_posts([
    'post_type'   => DocumentsRepository::POST_TYPE,
    'author'      => $user_id,
    'post_status' => 'any',
    'numberposts' => -1
]);

// Por status
$docs_by_status = [
    'draft'     => [],
    'ready'     => [],
    'signing'   => [],
    'completed' => [],
];

foreach ( $user_documents as $doc ) {
    $state = get_post_meta( $doc->ID, '_apollo_doc_state', true ) ?: 'draft';
    $docs_by_status[$state][] = $doc;
}

// Documentos pendentes de assinatura
$pending_signatures = get_posts([
    'post_type'   => DocumentsRepository::POST_TYPE,
    'meta_query'  => [
        [
            'key'     => '_apollo_doc_state',
            'value'   => 'signing',
        ],
        [
            'key'     => '_apollo_doc_signatures',
            'value'   => '"user_id":' . $user_id,
            'compare' => 'LIKE'
        ]
    ]
]);

// ========================
// CLASSIFICADOS (ANÚNCIOS)
// ========================
// Via WP Adverts (adaptador)
$user_classifieds = get_posts([
    'post_type'   => 'advert',
    'author'      => $user_id,
    'post_status' => ['publish', 'pending', 'expired']
]);

$active_ads = array_filter( $user_classifieds, fn($ad) => $ad->post_status === 'publish' );
$pending_ads = array_filter( $user_classifieds, fn($ad) => $ad->post_status === 'pending' );

// ========================
// COMUNIDADES (Público)
// ========================
// Tabela: Groups (Itthinx ou custom)
$user_comunidades = []; // Grupos tipo 'comunidade' onde usuário é membro

// Usando config
$comunidade_config = config( 'groups.comunidade' );
// visibility: 'public', join: 'open', invite: 'any_member'

// ========================
// NÚCLEOS (Privado - Equipes de Trabalho)
// ========================
$user_nucleos = []; // Grupos tipo 'nucleo' onde usuário é membro

// Usando config
$nucleo_config = config( 'groups.nucleo' );
// visibility: 'private', join: 'invite_only', invite: 'insiders_only'

// ========================
// SEASONS
// ========================
$user_seasons = []; // Seasons onde usuário participa

$season_config = config( 'groups.season' );
// visibility: 'public', join: 'request', invite: 'moderators'
```

---

## 💬 CHAT & MENSAGENS

### Estrutura de Dados

```php
// Tabelas:
// - wp_apollo_chat_conversations
// - wp_apollo_chat_messages
// - wp_apollo_chat_participants

// Tipos de conversa
$conversation_types = [
    'direct',      // DM entre 2 usuários
    'group',       // Chat de grupo genérico
    'nucleo',      // Chat de núcleo
    'comunidade',  // Chat de comunidade
    'classified',  // Chat sobre anúncio
    'supplier',    // Chat com fornecedor
];

// Obter conversas do usuário
global $wpdb;
$conversations_table = $wpdb->prefix . 'apollo_chat_conversations';
$participants_table = $wpdb->prefix . 'apollo_chat_participants';

$user_conversations = $wpdb->get_results( $wpdb->prepare(
    "SELECT c.* FROM {$conversations_table} c
     INNER JOIN {$participants_table} p ON c.id = p.conversation_id
     WHERE p.user_id = %d
     ORDER BY c.updated_at DESC",
    $user_id
) );

// Mensagens não lidas
$unread_count = $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}apollo_chat_messages m
     INNER JOIN {$participants_table} p ON m.conversation_id = p.conversation_id
     WHERE p.user_id = %d
       AND m.created_at > COALESCE(p.last_read_at, '1970-01-01')",
    $user_id
) );
```

---

## 🏅 GAMIFICAÇÃO & BADGES

### Configuração de Eventos

```php
// Obter config de badges
$badges_config = config( 'badges' );

// Eventos que geram pontos/badges
$badge_events = [
    'post_created'        => ['points' => 10, 'badge' => 'content_creator'],
    'classified_approved' => ['points' => 25, 'badge' => 'classified_master'],
    'document_signed'     => ['points' => 50, 'badge' => 'verified_member'],
    'group_joined'        => ['points' => 5,  'badge' => 'community_member'],
    'invite_sent'         => ['points' => 15, 'badge' => 'recruiter'],
    'event_attended'      => ['points' => 20, 'badge' => 'participant'],

    // Assinaturas digitais (níveis)
    'signature_simple'    => ['points' => 10],
    'signature_advanced'  => ['points' => 25],
    'signature_qualified' => ['points' => 50],
];

// Níveis de gamificação
$levels = [
    'bronze'   => ['min_points' => 0,    'color' => '#CD7F32'],
    'silver'   => ['min_points' => 100,  'color' => '#C0C0C0'],
    'gold'     => ['min_points' => 500,  'color' => '#FFD700'],
    'platinum' => ['min_points' => 1000, 'color' => '#E5E4E2'],
    'diamond'  => ['min_points' => 2500, 'color' => '#B9F2FF'],
];

// Calcular nível do usuário
function apollo_get_user_level( $user_id ) {
    $points = (int) get_user_meta( $user_id, '_apollo_total_points', true );
    $levels = config( 'badges.levels' );

    $current_level = 'bronze';
    foreach ( $levels as $level => $data ) {
        if ( $points >= $data['min_points'] ) {
            $current_level = $level;
        }
    }

    return [
        'level'  => $current_level,
        'points' => $points,
        'color'  => $levels[$current_level]['color'],
        'next'   => apollo_get_next_level_info( $points, $levels ),
    ];
}
```

---

## 🔗 RELACIONAMENTOS SOCIAIS

### Close Friends & Bubble

```php
// Meta keys para relacionamentos
// (preparado para uso com 'bubble' etc)

// Close Friends (amigos próximos)
$close_friends = get_user_meta( $user_id, '_apollo_close_friends', true ) ?: [];
// Returns: [user_id_1, user_id_2, ...]

// Bubble (círculo expandido - amigos de amigos ativos)
$bubble = get_user_meta( $user_id, '_apollo_bubble', true ) ?: [];

// Followers/Following
$followers = get_user_meta( $user_id, '_apollo_followers', true ) ?: [];
$following = get_user_meta( $user_id, '_apollo_following', true ) ?: [];

// Funções helper (a implementar)
function apollo_add_close_friend( $user_id, $friend_id ) {
    $friends = get_user_meta( $user_id, '_apollo_close_friends', true ) ?: [];
    if ( ! in_array( $friend_id, $friends ) ) {
        $friends[] = $friend_id;
        update_user_meta( $user_id, '_apollo_close_friends', $friends );
    }
}

function apollo_get_bubble_users( $user_id, $limit = 20 ) {
    // Algoritmo para determinar "bubble":
    // 1. Close friends (peso máximo)
    // 2. Membros dos mesmos grupos
    // 3. Participantes dos mesmos eventos
    // 4. Interações recentes (likes, comentários)

    $bubble = [];

    // Close friends primeiro
    $close = get_user_meta( $user_id, '_apollo_close_friends', true ) ?: [];
    $bubble = array_merge( $bubble, $close );

    // TODO: Expandir com algoritmo de relevância

    return array_slice( array_unique( $bubble ), 0, $limit );
}
```

---

## 📊 ANALYTICS LOCAL

### Contadores de Sessão

```php
// Config de analytics
$analytics = config( 'analytics' );

// Contadores locais disponíveis
$local_counters = [
    'session_page_views' => true,
    'session_events'     => true,
    'total_interactions' => true,
    'group_interactions' => true,
    'ad_interactions'    => true,
    'event_interactions' => true,
];

// Eventos trackados
$tracked_events = [
    'group_view',
    'group_join',
    'invite_sent',
    'invite_approved',
    'ad_view',
    'ad_create',
    'ad_publish',
    'event_view',
    'event_filter_applied',
];
```

---

## 🎨 COMPONENTES UI DISPONÍVEIS

```php
use Apollo\UI\Button;
use Apollo\UI\Input;
use Apollo\UI\Field;
use Apollo\UI\Dialog;
use Apollo\UI\Table;

// Botão
echo Button::render( 'Salvar', 'primary', 'md', ['id' => 'save-btn'] );

// Input
echo Input::render( 'email', $user->email, 'email', 'Seu email' );

// Campo com label
echo Field::render(
    'Nome Público',
    Input::render( 'display_name', $user->display_name ),
    'Este nome será exibido publicamente',
    'vertical'
);

// Modal/Dialog
echo Dialog::render(
    '<button>Abrir Config</button>',
    '<p>Conteúdo do modal</p>',
    'Configurações'
);

// Tabela
echo Table::render(
    ['Nome', 'Status', 'Ações'],
    [
        ['Documento 1', 'Rascunho', '<button>Editar</button>'],
        ['Documento 2', 'Concluído', '<button>Ver</button>'],
    ]
);
```

---

## 📐 ESTRUTURA SUGERIDA DO DASHBOARD

```html
<div class="apollo-dashboard">

    <!-- Header com Avatar e Status -->
    <section class="dashboard-header">
        <div class="user-avatar">...</div>
        <div class="user-info">
            <h1>{display_name}</h1>
            <div class="badges">
                <!-- foreach $badges -->
                <span class="badge badge-{class}">{label}</span>
            </div>
            <div class="membership-status">
                <!-- Cultura::Rio status -->
            </div>
        </div>
    </section>

    <!-- Quick Stats Cards -->
    <section class="dashboard-stats">
        <div class="stat-card">
            <span class="stat-value">{total_likes}</span>
            <span class="stat-label">Curtidas Recebidas</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{events_count}</span>
            <span class="stat-label">Eventos</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{documents_count}</span>
            <span class="stat-label">Documentos</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{unread_messages}</span>
            <span class="stat-label">Mensagens</span>
        </div>
    </section>

    <!-- Tabs Navigation -->
    <nav class="dashboard-tabs">
        <button data-tab="overview" class="active">Visão Geral</button>
        <button data-tab="events">Eventos</button>
        <button data-tab="documents">Documentos</button>
        <button data-tab="classifieds">Anúncios</button>
        <button data-tab="groups">Grupos</button>
        <button data-tab="social">Social</button>
        <button data-tab="settings">Ajustes</button>
    </nav>

    <!-- Tab Content -->
    <div class="dashboard-content">

        <!-- Overview Tab -->
        <div id="tab-overview" class="tab-panel active">
            <!-- Pending Actions -->
            <div class="pending-actions">
                <!-- Documentos para assinar -->
                <!-- Convites pendentes -->
                <!-- Notificações importantes -->
            </div>

            <!-- Recent Activity -->
            <div class="recent-activity">
                <!-- Timeline de atividades -->
            </div>

            <!-- Bubble / Close Friends -->
            <div class="social-bubble">
                <!-- Avatares de amigos próximos -->
            </div>
        </div>

        <!-- Events Tab -->
        <div id="tab-events" class="tab-panel">
            <h2>🎉 Meus Eventos</h2>

            <!-- Eventos criados -->
            <div class="events-created">...</div>

            <!-- Eventos de interesse -->
            <div class="events-attending">...</div>
        </div>

        <!-- Documents Tab -->
        <div id="tab-documents" class="tab-panel">
            <h2>📄 Meus Documentos</h2>

            <!-- Status cards -->
            <div class="docs-status-grid">
                <div class="status-card status-draft">
                    <span class="count">{draft_count}</span>
                    <span class="label">Rascunhos</span>
                </div>
                <div class="status-card status-signing">
                    <span class="count">{signing_count}</span>
                    <span class="label">Em Assinatura</span>
                </div>
                <div class="status-card status-completed">
                    <span class="count">{completed_count}</span>
                    <span class="label">Concluídos</span>
                </div>
            </div>

            <!-- Documents list -->
            <div class="documents-list">...</div>
        </div>

        <!-- Classifieds Tab -->
        <div id="tab-classifieds" class="tab-panel">
            <h2>📢 Meus Anúncios</h2>

            <div class="classifieds-grid">
                <!-- Anúncios ativos -->
                <!-- Anúncios pendentes -->
                <!-- Anúncios expirados -->
            </div>
        </div>

        <!-- Groups Tab -->
        <div id="tab-groups" class="tab-panel">
            <h2>👥 Meus Grupos</h2>

            <!-- Comunidades (público) -->
            <div class="group-section comunidades">
                <h3>🌐 Comunidades</h3>
                <!-- Lista de comunidades -->
            </div>

            <!-- Núcleos (privado) -->
            <div class="group-section nucleos">
                <h3>🔒 Núcleos de Trabalho</h3>
                <!-- Lista de núcleos -->
            </div>

            <!-- Seasons -->
            <div class="group-section seasons">
                <h3>🎭 Seasons</h3>
                <!-- Lista de seasons -->
            </div>
        </div>

        <!-- Social Tab -->
        <div id="tab-social" class="tab-panel">
            <h2>💬 Conexões</h2>

            <!-- Close Friends -->
            <div class="close-friends">
                <h3>⭐ Amigos Próximos</h3>
                <!-- Grid de close friends -->
            </div>

            <!-- Following -->
            <div class="following">
                <h3>👀 Seguindo</h3>
            </div>

            <!-- Followers -->
            <div class="followers">
                <h3>🙌 Seguidores</h3>
            </div>
        </div>

        <!-- Settings Tab -->
        <div id="tab-settings" class="tab-panel">
            <!-- Usar [apollo_user_ajustes] shortcode -->
            <!-- Ou renderizar UserAjustesSection::renderAjustesSection() -->
        </div>

    </div>
</div>
```

---

## 🔐 ENDPOINTS AJAX DISPONÍVEIS

| Action | Descrição | Params |
|--------|-----------|--------|
| `apollo_save_user_settings` | Salvar configurações do usuário | `display_name`, `features` |
| `apollo_update_profile` | Atualizar perfil | Diversos campos |

---

## 📦 SHORTCODES DISPONÍVEIS

```php
// Seção de ajustes completa
[apollo_user_ajustes]

// Shortcode de renderização (a implementar)
[apollo_user_dashboard]
[apollo_user_stats]
[apollo_user_documents]
[apollo_user_events]
[apollo_user_groups]
```

---

## 🎯 IDENTIDADES CULTURA::RIO

| Key | Label | Membership Level |
|-----|-------|------------------|
| `clubber` | Clubber (sempre ativo) | - |
| `dj_amateur` | DJ aspirante/amador | dj_amateur |
| `dj_pro` | DJ profissional | dj_professional |
| `producer_dreamer` | Producer - quer iniciar | event_producer_starter |
| `producer_starter` | Producer - iniciando | event_producer_active |
| `producer_pro` | Producer profissional | event_producer_professional |
| `music_producer` | Producer de Música | music_producer |
| `cultural_producer` | Producer Cultural | cultural_producer |
| `business` | Business Person | business |
| `government` | Government | government |
| `promoter` | Promoter | promoter |
| `visual_artist` | Visual Artist | visual_artist |

---

## 🎨 CSS CLASSES PARA BADGES

```css
/* Badge colors */
.badge-apollo  { background: #ff8c42; color: #000; } /* Orange/Apollo */
.badge-green   { background: #63c720; color: #fff; } /* DJ */
.badge-blue    { background: #167cf9; color: #fff; } /* Govern */
.badge-purple  { background: #9820c7; color: #fff; } /* Purple */
.badge-yellow  { background: #edd815; color: #000; } /* Business */
.badge-pink    { background: #d615b6; color: #fff; } /* Pink */
.badge-red     { background: #d90d21; color: #fff; } /* Red */
.badge-muted   { background: #9aa0a6; color: #fff; } /* Não Verificado */
```

---

## 📝 DOCUMENT STATUS HELPERS

```php
use Apollo\Modules\Documents\DocumentsHelpers;

// Obter info de status
$status_info = DocumentsHelpers::get_status_info( 'signing' );
// Returns: [
//   'label'   => 'Em Assinatura',
//   'icon'    => 'ri-quill-pen-line',
//   'color'   => 'amber',
//   'tooltip' => 'Documento aguardando assinaturas...'
// ]

// Helpers individuais
$label   = DocumentsHelpers::get_status_label( 'draft' );   // 'Rascunho'
$icon    = DocumentsHelpers::get_status_icon( 'completed' ); // 'ri-verified-badge-line'
$color   = DocumentsHelpers::get_status_color( 'cancelled' ); // 'red'
$tooltip = DocumentsHelpers::get_status_tooltip( 'archived' );

// Todos os status
$all_statuses = DocumentsHelpers::get_all_statuses();
```

---

## 🚀 EXEMPLO DE IMPLEMENTAÇÃO

```php
<?php
/**
 * Template: users/dashboard.php
 */

// Verificar login
if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url( get_permalink() ) );
    exit;
}

$user_id = get_current_user_id();
$user = get_userdata( $user_id );

// Carregar dados
use Apollo\Modules\Registration\CulturaRioIdentity;
use Apollo\Modules\Documents\DocumentsHelpers;

$identities = CulturaRioIdentity::getUserIdentities( $user_id );
$membership = CulturaRioIdentity::getMembershipStatus( $user_id );
$badges = apollo_social_get_user_badges( $user_id );
$settings = get_user_meta( $user_id, 'apollo_display_settings', true ) ?: [];
$sounds = get_user_meta( $user_id, 'apollo_sounds', true ) ?: [];

// Estatísticas
global $wpdb;
$likes_received = $wpdb->get_var( /* query */ );
$unread_messages = /* count unread */;
$pending_docs = /* count pending signatures */;

// Render dashboard...
?>
```

---

**📅 Última atualização:** 30/12/2025
**🔖 Versão:** 1.0.0
