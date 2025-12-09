# Apollo Core - Documentação de Funções Integradas

> Versão 2.0 | Atualizado: Janeiro 2025
> 
> Este documento lista todas as funções públicas disponíveis no plugin Apollo Core para uso em outros plugins e temas.

---

## 📋 Índice

1. [Moderação de Usuários](#moderação-de-usuários)
2. [Configuração de Módulos](#configuração-de-módulos)
3. [Sistema de Limites](#sistema-de-limites)
4. [Integração Cross-Module](#integração-cross-module)
5. [API REST Endpoints](#api-rest-endpoints)
6. [Hooks e Filtros](#hooks-e-filtros)

---

## 1. Moderação de Usuários

### `apollo_suspend_user( $user_id, $duration_hours, $reason )`
Suspende um usuário por um período determinado.

```php
// Suspender por 24 horas
apollo_suspend_user( 123, 24, 'Violação das regras da comunidade' );

// Suspender por 7 dias
apollo_suspend_user( 123, 168, 'Spam recorrente' );
```

**Parâmetros:**
| Nome | Tipo | Descrição |
|------|------|-----------|
| `$user_id` | `int` | ID do usuário |
| `$duration_hours` | `int` | Duração em horas |
| `$reason` | `string` | Motivo da suspensão |

**Retorna:** `bool` - Sucesso da operação

---

### `apollo_ban_user( $user_id, $reason )`
Bane permanentemente um usuário.

```php
apollo_ban_user( 123, 'Comportamento abusivo repetido' );
```

**Parâmetros:**
| Nome | Tipo | Descrição |
|------|------|-----------|
| `$user_id` | `int` | ID do usuário |
| `$reason` | `string` | Motivo do banimento |

**Retorna:** `bool` - Sucesso da operação

---

### `apollo_unsuspend_user( $user_id )`
Remove suspensão ou banimento de um usuário.

```php
apollo_unsuspend_user( 123 );
```

**Retorna:** `bool` - Sucesso da operação

---

### `apollo_is_user_suspended( $user_id )`
Verifica se um usuário está suspenso ou banido.

```php
if ( apollo_is_user_suspended( 123 ) ) {
    // Usuário está suspenso/banido
}
```

**Retorna:** `bool` - `true` se suspenso/banido

---

### `apollo_get_user_status( $user_id )`
Retorna status detalhado de um usuário.

```php
$status = apollo_get_user_status( 123 );
// Retorna: 'active', 'suspended', ou 'banned'
```

**Retorna:** `string` - Status do usuário

---

### `apollo_can_user_perform( $user_id, $action )`
Verifica se um usuário tem permissão para realizar uma ação.

```php
if ( apollo_can_user_perform( 123, 'create_event' ) ) {
    // Pode criar evento
}
```

**Ações disponíveis:**
- `create_event` - Criar evento
- `create_post` - Criar post social
- `send_message` - Enviar mensagem
- `join_comuna` - Entrar em comuna
- `moderate_basic` - Moderação básica
- `moderate_advanced` - Moderação avançada
- `suspend_users` - Suspender usuários

**Retorna:** `bool` - `true` se permitido

---

### `apollo_get_mod_level( $user_id )`
Retorna o nível de moderação do usuário.

```php
$level = apollo_get_mod_level( 123 );
// Retorna: 0, 1, ou 3
```

**Níveis:**
| Nível | Descrição | Capacidades |
|-------|-----------|-------------|
| 0 | MOD Básico | Moderar posts, esconder conteúdo |
| 1 | MOD Avançado | + Suspender usuários (máx 24h) |
| 3 | MOD Completo | + Banir, bloquear IP |

**Retorna:** `int` - Nível de moderação (0, 1, ou 3)

---

### `apollo_set_mod_level( $user_id, $level )`
Define o nível de moderação de um usuário.

```php
apollo_set_mod_level( 123, 1 ); // Promover a MOD 1
```

**Retorna:** `bool` - Sucesso da operação

---

## 2. Configuração de Módulos

### `apollo_is_module_enabled( $module )`
Verifica se um módulo está ativo.

```php
if ( apollo_is_module_enabled( 'events' ) ) {
    // Módulo de eventos está ativo
}
```

**Módulos disponíveis:**
- `social` - Feed social
- `events` - Eventos e agenda
- `chat` - Mensagens/chat
- `docs` - Documentos Docuseal
- `matchmaking` - Sistema de match
- `bolha` - Círculo íntimo

**Retorna:** `bool` - `true` se módulo ativo

---

### `apollo_set_module_enabled( $module, $enabled )`
Ativa ou desativa um módulo.

```php
// Desativar chat
apollo_set_module_enabled( 'chat', false );

// Ativar bolha
apollo_set_module_enabled( 'bolha', true );
```

**Retorna:** `bool` - Sucesso da operação

---

### `apollo_get_modules()`
Retorna todos os módulos e seus estados.

```php
$modules = apollo_get_modules();
// Retorna: array( 'social' => true, 'events' => true, ... )
```

**Retorna:** `array` - Módulos e estados

---

## 3. Sistema de Limites

### `apollo_get_limit( $limit_key )`
Retorna o valor de um limite global.

```php
$max_events = apollo_get_limit( 'max_events_per_user_month' );
// Retorna: 10
```

**Limites disponíveis:**
| Chave | Padrão | Descrição |
|-------|--------|-----------|
| `max_events_per_user_month` | 10 | Eventos por usuário/mês |
| `max_comunas_per_user` | 5 | Comunas que um usuário pode criar |
| `max_bubble_members` | 15 | Membros máximos na Bolha |
| `max_social_posts_per_day` | 20 | Posts por dia |

**Retorna:** `int` - Valor do limite

---

### `apollo_set_limit( $limit_key, $value )`
Define um limite global.

```php
apollo_set_limit( 'max_events_per_user_month', 15 );
```

**Retorna:** `bool` - Sucesso da operação

---

### `apollo_check_limit( $user_id, $resource_type )`
Verifica se usuário pode criar mais de um recurso.

```php
if ( apollo_check_limit( get_current_user_id(), 'events' ) ) {
    // Pode criar mais eventos
} else {
    // Limite atingido
}
```

**Tipos de recurso:**
- `events` - Eventos do mês
- `comunas` - Comunas criadas
- `bubble` - Membros na bolha
- `posts` - Posts do dia

**Retorna:** `bool` - `true` se pode criar mais

---

### `apollo_get_user_usage( $user_id, $resource_type )`
Retorna quantos recursos um usuário já criou.

```php
$count = apollo_get_user_usage( 123, 'events' );
echo "Eventos criados este mês: $count";
```

**Retorna:** `int` - Quantidade usada

---

## 4. Integração Cross-Module

### `apollo_notify_bubble_on_event( $event_id, $user_id )`
Notifica todos os membros da bolha sobre um evento.

```php
// Quando usuário cria evento, notificar bolha
apollo_notify_bubble_on_event( $event_id, $user_id );
```

---

### `apollo_auto_post_event_to_social( $event_id )`
Cria automaticamente um post no feed social sobre um evento.

```php
// Já é chamado automaticamente via hook 'publish_ap_event'
```

---

### `apollo_get_weighted_feed_items( $items, $user_id )`
Aplica pesos aos itens do feed baseado em Bolha, Eventos, etc.

```php
// Usado internamente no filtro apollo_filter_explore_items
$weighted = apollo_get_weighted_feed_items( $items, $user_id );
```

**Pesos:**
| Fonte | Peso |
|-------|------|
| Bolha | 3x |
| Eventos que participa | 2x |
| Comunas que participa | 2x |
| Padrão | 1x |

---

## 5. API REST Endpoints

### Moderação

```
POST /wp-json/apollo/v1/moderation/suspend
```
Suspende um usuário.

**Body:**
```json
{
    "user_id": 123,
    "duration": 24,
    "reason": "Violação de regras"
}
```

---

```
POST /wp-json/apollo/v1/moderation/ban
```
Bane um usuário permanentemente.

**Body:**
```json
{
    "user_id": 123,
    "reason": "Spam"
}
```

---

```
POST /wp-json/apollo/v1/moderation/unsuspend
```
Remove suspensão/banimento.

**Body:**
```json
{
    "user_id": 123
}
```

---

```
GET /wp-json/apollo/v1/moderation/status/{user_id}
```
Retorna status de moderação de um usuário.

---

### Módulos e Limites

```
GET /wp-json/apollo/v1/modules
```
Lista módulos e seus estados.

```
POST /wp-json/apollo/v1/modules/{module}/toggle
```
Alterna estado de um módulo.

```
GET /wp-json/apollo/v1/limits
```
Lista limites globais.

```
POST /wp-json/apollo/v1/limits/{key}
```
Atualiza um limite.

---

## 6. Hooks e Filtros

### Ações (Actions)

```php
// Quando usuário é suspenso
do_action( 'apollo_user_suspended', $user_id, $duration_hours, $reason );

// Quando usuário é banido
do_action( 'apollo_user_banned', $user_id, $reason );

// Quando suspensão é removida
do_action( 'apollo_user_unsuspended', $user_id );

// Quando módulo é alterado
do_action( 'apollo_module_toggled', $module, $enabled );

// Quando limite é atualizado
do_action( 'apollo_limit_updated', $key, $old_value, $new_value );

// Quando usuário atinge limite
do_action( 'apollo_user_limit_reached', $user_id, $resource_type );
```

### Filtros (Filters)

```php
// Filtrar itens do feed
add_filter( 'apollo_filter_explore_items', function( $items, $user_id ) {
    // Modificar itens
    return $items;
}, 10, 2 );

// Filtrar limites padrão
add_filter( 'apollo_default_limits', function( $limits ) {
    $limits['max_events_per_user_month'] = 20;
    return $limits;
} );

// Filtrar módulos padrão
add_filter( 'apollo_default_modules', function( $modules ) {
    $modules['new_module'] = true;
    return $modules;
} );

// Verificar se usuário pode ser suspenso
add_filter( 'apollo_can_suspend_user', function( $can, $user_id, $actor_id ) {
    // Lógica customizada
    return $can;
}, 10, 3 );
```

---

## 📁 Arquivos Principais

| Arquivo | Propósito |
|---------|-----------|
| `includes/class-apollo-user-moderation.php` | Sistema de moderação |
| `includes/class-apollo-modules-config.php` | Configuração de módulos |
| `includes/class-apollo-cross-module-integration.php` | Integração entre módulos |
| `admin/admin-apollo-cabin.php` | Painel Admin Cabin |
| `admin/assets/css/admin-cabin.css` | Estilos do Admin Cabin |
| `admin/assets/js/admin-cabin.js` | Scripts do Admin Cabin |

---

## 🔒 Permissões

| Capability | Descrição |
|------------|-----------|
| `apollo_moderate_basic` | Moderação básica (MOD 0+) |
| `apollo_moderate_advanced` | Moderação avançada (MOD 1+) |
| `apollo_suspend_users` | Suspender usuários (MOD 1+) |
| `apollo_ban_users` | Banir usuários (MOD 3) |
| `apollo_block_ip` | Bloquear IPs (MOD 3) |
| `manage_options` | Admin Cabin (admins only) |

---

## 📊 Tabelas do Banco de Dados

### `wp_apollo_audit_log`
Log de auditoria de todas as ações de moderação.

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | BIGINT | ID único |
| `actor_id` | BIGINT | Quem realizou ação |
| `target_id` | BIGINT | Alvo da ação |
| `action` | VARCHAR(100) | Tipo de ação |
| `details` | TEXT | JSON com detalhes |
| `ip_hash` | VARCHAR(64) | Hash SHA256 do IP |
| `created_at` | DATETIME | Data/hora |

### `wp_apollo_ip_blocklist`
Lista de IPs bloqueados.

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | BIGINT | ID único |
| `ip_hash` | VARCHAR(64) | Hash SHA256 do IP |
| `blocked_by` | BIGINT | Admin que bloqueou |
| `reason` | TEXT | Motivo |
| `blocked_at` | DATETIME | Data do bloqueio |
| `expires_at` | DATETIME | Expiração (NULL = permanente) |

---

## 🛠️ Admin Cabin

Acessível em: **WP Admin → Apollo Cabin**

### Abas:

1. **Módulos** - Ativar/desativar funcionalidades
2. **Limites** - Definir limites globais
3. **Moderadores** - Gerenciar níveis MOD
4. **Segurança** - IP blocklist
5. **Logs** - Auditoria de ações

---

*Última atualização: Janeiro 2025*
