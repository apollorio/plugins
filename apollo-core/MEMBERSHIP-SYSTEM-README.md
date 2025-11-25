# Apollo Core - Sistema de Memberships

## Visão Geral

O sistema de memberships do Apollo Core fornece uma solução completa para gerenciar tipos de associação de usuários, com badges visuais no frontend, interface administrativa, REST API, comandos WP-CLI e auditoria completa.

## Funcionalidades

- ✅ **Atribuição Automática**: Novos usuários recebem automaticamente a membership `nao-verificado`
- ✅ **Badges Visuais**: Exibição de badges coloridos com Instagram ID opcional
- ✅ **Interface Admin**: UI completa para gerenciar tipos de membership e atribuir aos usuários
- ✅ **REST API**: Endpoints seguros para operações CRUD
- ✅ **WP-CLI**: Comandos de linha de comando para automação
- ✅ **Auditoria**: Todas as mudanças são registradas em `wp_apollo_mod_log`
- ✅ **Import/Export**: JSON para backup e migração

## Memberships Padrão

| Slug | Label | Cor | Uso |
|------|-------|-----|-----|
| `nao-verificado` | Não Verificado | `#9AA0A6` | Padrão para novos usuários |
| `apollo` | Apollo | `#FF8C42` | Membros verificados Apollo |
| `prod` | Prod | `#8A2BE2` | Produtores de eventos |
| `dj` | DJ | `#8A2BE2` | DJs verificados |
| `host` | Host | `#8A2BE2` | Hosts/MCs |
| `govern` | Govern | `#007BFF` | Governo/Parceiros oficiais |
| `business-pers` | Business | `#FFD700` | Empresas/Pessoas jurídicas |

## Uso no Código

### Obter Membership de um Usuário

```php
$user_id = 123;
$membership = apollo_get_user_membership( $user_id );
// Retorna: 'nao-verificado', 'apollo', etc.
```

### Atribuir Membership

```php
$user_id = 123;
$membership_slug = 'apollo';
$actor_id = get_current_user_id(); // Quem está fazendo a mudança

$result = apollo_set_user_membership( $user_id, $membership_slug, $actor_id );
if ( $result ) {
    // Sucesso - mudança foi registrada no log de auditoria
}
```

### Exibir Badge no Frontend

```php
// Badge completo com Instagram se disponível
echo apollo_display_membership_badge( $user_id );

// Badge sem Instagram
echo apollo_display_membership_badge( $user_id, array( 'show_instagram' => false ) );

// Badge customizado
echo apollo_display_membership_badge( $user_id, array(
    'show_instagram' => true,
    'badge_class'    => 'my-custom-class',
    'show_label'     => true,
) );
```

### Verificar se Membership Existe

```php
if ( apollo_membership_exists( 'vip-custom' ) ) {
    // Membership existe
}
```

### Obter Dados de uma Membership

```php
$data = apollo_get_membership_data( 'apollo' );
/*
Array (
    'label' => 'Apollo',
    'frontend_label' => 'Apollo',
    'color' => '#FF8C42',
    'text_color' => '#7A3E00'
)
*/
```

## REST API

### Base URL
```
https://seu-site.com/wp-json/apollo/v1/
```

### Endpoints

#### GET /memberships
Obter todos os tipos de membership (público).

```bash
curl -i "https://seu-site.com/wp-json/apollo/v1/memberships"
```

**Resposta:**
```json
{
    "success": true,
    "version": "1.0.0",
    "memberships": {
        "nao-verificado": {
            "label": "Não Verificado",
            "frontend_label": "Não Verificado",
            "color": "#9AA0A6",
            "text_color": "#6E7376"
        },
        ...
    }
}
```

#### POST /memberships/set
Atribuir membership a um usuário (requer `edit_apollo_users`).

```bash
curl -i -X POST "https://seu-site.com/wp-json/apollo/v1/memberships/set" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: SEU_NONCE" \
  -H "Cookie: wordpress_logged_in_...=..." \
  -d '{
    "user_id": 123,
    "membership_slug": "apollo"
  }'
```

**Resposta:**
```json
{
    "success": true,
    "message": "Membership updated successfully",
    "user_id": 123,
    "user_name": "João Silva",
    "membership": {
        "label": "Apollo",
        "frontend_label": "Apollo",
        "color": "#FF8C42",
        "text_color": "#7A3E00"
    }
}
```

#### POST /memberships/create
Criar novo tipo de membership (requer `manage_options` - admin).

```bash
curl -i -X POST "https://seu-site.com/wp-json/apollo/v1/memberships/create" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: SEU_NONCE" \
  -d '{
    "slug": "vip-member",
    "label": "VIP Member",
    "frontend_label": "VIP",
    "color": "#FFD700",
    "text_color": "#8B6B00"
  }'
```

#### POST /memberships/update
Atualizar tipo de membership existente (requer `manage_options`).

```bash
curl -i -X POST "https://seu-site.com/wp-json/apollo/v1/memberships/update" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: SEU_NONCE" \
  -d '{
    "slug": "vip-member",
    "label": "VIP Premium Member",
    "color": "#FFD700"
  }'
```

#### POST /memberships/delete
Deletar tipo de membership (requer `manage_options`). Usuários com essa membership serão reatribuídos a `nao-verificado`.

```bash
curl -i -X POST "https://seu-site.com/wp-json/apollo/v1/memberships/delete" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: SEU_NONCE" \
  -d '{
    "slug": "vip-member"
  }'
```

#### GET /memberships/export
Exportar memberships como JSON (requer `manage_options`).

```bash
curl -i -H "X-WP-Nonce: SEU_NONCE" \
  "https://seu-site.com/wp-json/apollo/v1/memberships/export"
```

#### POST /memberships/import
Importar memberships de JSON (requer `manage_options`).

```bash
curl -i -X POST "https://seu-site.com/wp-json/apollo/v1/memberships/import" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: SEU_NONCE" \
  -d '{
    "data": "{\"version\":\"1.0.0\",\"memberships\":{...}}"
  }'
```

## WP-CLI

### Listar Memberships

```bash
wp apollo membership list
```

**Saída:**
```
+------------------+------------------+------------------+---------+-------------+
| Slug             | Label            | Frontend Label   | Color   | Text Color  |
+------------------+------------------+------------------+---------+-------------+
| nao-verificado   | Não Verificado   | Não Verificado   | #9AA0A6 | #6E7376     |
| apollo           | Apollo           | Apollo           | #FF8C42 | #7A3E00     |
| dj               | DJ               | DJ               | #8A2BE2 | #4B0082     |
+------------------+------------------+------------------+---------+-------------+

Schema Version: 1.0.2
```

### Adicionar Novo Tipo de Membership

```bash
wp apollo membership add vip-member \
  --label="VIP Member" \
  --frontend-label="VIP" \
  --color="#FFD700" \
  --text-color="#8B6B00"
```

### Atribuir Membership a Usuário

```bash
wp apollo membership assign 123 apollo
```

**Saída:**
```
Success: Membership "apollo" assigned to user 123 (João Silva).
```

### Obter Membership de Usuário

```bash
wp apollo membership get 123
```

**Saída:**
```
User: João Silva (joao@exemplo.com)
Membership Slug: apollo
Membership Label: Apollo
Frontend Label: Apollo
Color: #FF8C42
Text Color: #7A3E00
Instagram: @joaosilva
```

### Exportar Memberships

```bash
wp apollo membership export /tmp/memberships-backup.json
```

### Importar Memberships

```bash
wp apollo membership import /tmp/memberships-backup.json
```

### Deletar Membership

```bash
wp apollo membership delete vip-member --yes
```

### Estatísticas de Uso

```bash
wp apollo membership stats
```

**Saída:**
```
+--------------+------------------+-------+
| Membership   | Slug             | Users |
+--------------+------------------+-------+
| Não Verificado | nao-verificado | 1250  |
| Apollo       | apollo           | 50    |
| DJ           | dj               | 30    |
+--------------+------------------+-------+

Total Users: 1330
```

## Interface Admin

### Acessar

Navegue para: **WordPress Admin → Moderation → Moderate Users**

### Funcionalidades da UI

#### Para Moderadores (role `apollo`):
- ✅ Ver lista de usuários com suas memberships
- ✅ Alterar membership de usuários via dropdown
- ❌ Não podem criar/editar/deletar tipos de membership

#### Para Administradores (role `administrator`):
- ✅ Todas as funcionalidades de moderadores
- ✅ Gerenciar tipos de membership (seção "Membership Types Manager")
- ✅ Adicionar novos tipos de membership via modal
- ✅ Editar tipos de membership customizados
- ✅ Deletar tipos de membership customizados (com confirmação)
- ✅ Exportar/Importar memberships como JSON

### Membership Types Manager

Localizado na parte inferior da aba "Moderate Users", disponível apenas para admins:

- **Adicionar Membership**: Modal com campos para slug, label, frontend label, cores
- **Editar Membership**: Editar memberships customizadas (memberships padrão não podem ser editadas)
- **Deletar Membership**: Confirma e reatribui usuários para `nao-verificado`
- **Export JSON**: Baixa arquivo JSON com todas as memberships
- **Import JSON**: Upload de JSON para restaurar/migrar memberships

## Hooks e Filtros

### Actions

```php
// Executado quando novo usuário se registra
do_action( 'apollo_membership_assigned', $user_id, $membership_slug );

// Executado quando membership é alterada
do_action( 'apollo_membership_changed', $user_id, $old_slug, $new_slug, $actor_id );
```

### Filters

```php
// Filtrar memberships disponíveis
$memberships = apply_filters( 'apollo_available_memberships', $memberships );

// Filtrar HTML do badge
$badge_html = apply_filters( 'apollo_membership_badge_html', $html, $user_id, $membership_data );

// Filtrar CSS do badge
$css = apply_filters( 'apollo_membership_badge_css', $css );
```

## Banco de Dados

### User Meta

| Meta Key | Tipo | Descrição |
|----------|------|-----------|
| `_apollo_membership` | string | Slug da membership do usuário |

### Options

| Option | Tipo | Descrição |
|--------|------|-----------|
| `apollo_memberships` | array | Memberships customizadas (padrão: `[]`) |
| `apollo_memberships_version` | string | Versão do schema (incrementa a cada mudança) |

### Audit Log

Todas as mudanças são registradas em `wp_apollo_mod_log`:

```php
// Exemplo de registro
array(
    'actor_id'    => 1,
    'actor_role'  => 'administrator',
    'action'      => 'membership_changed',
    'target_type' => 'user',
    'target_id'   => 123,
    'details'     => json_encode(array(
        'from'       => 'nao-verificado',
        'to'         => 'apollo',
        'from_label' => 'Não Verificado',
        'to_label'   => 'Apollo',
        'timestamp'  => '2025-11-24 10:30:00',
    )),
    'created_at'  => '2025-11-24 10:30:00',
)
```

## Segurança

### Capabilities

| Capability | Quem Tem | Permite |
|------------|----------|---------|
| `edit_apollo_users` | `apollo`, `administrator` | Alterar membership de usuários |
| `manage_options` | `administrator` | Criar/editar/deletar tipos de membership |

### Validações

- ✅ Nonces obrigatórios em todas as requisições REST e admin
- ✅ Sanitização de inputs (cores hex, slugs, textos)
- ✅ Validação de existência de usuário e membership
- ✅ Memberships padrão não podem ser deletadas ou editadas
- ✅ Membership `nao-verificado` não pode ser deletada

## Testes

Execute os testes PHPUnit:

```bash
cd wp-content/plugins/apollo-core
vendor/bin/phpunit --filter Apollo_Membership_Test
```

### Testes Incluídos

- ✅ Opção de memberships criada na ativação
- ✅ Novos usuários recebem `nao-verificado`
- ✅ Atribuir membership funciona corretamente
- ✅ Membership inválida retorna false
- ✅ Permissões REST funcionam
- ✅ REST endpoints retornam dados corretos
- ✅ Display badge retorna HTML correto
- ✅ Instagram ID é exibido no badge
- ✅ Salvar membership customizada funciona
- ✅ Validação rejeita dados inválidos
- ✅ Deletar membership reatribui usuários
- ✅ Não pode deletar memberships padrão
- ✅ Export/import funciona
- ✅ Mudanças são registradas no audit log

## Migração de Plugins Antigos

Se você está migrando de `apollo-events-manager` ou `apollo-social`:

1. **Ativação do Apollo Core** atribuirá automaticamente `nao-verificado` a todos os usuários sem membership
2. **Não há conflito** com metas existentes (usa `_apollo_membership`)
3. **Reversível**: Desativar o plugin não remove os dados

## Troubleshooting

### Usuário não tem membership

Execute no WP-CLI:
```bash
wp apollo membership assign USER_ID nao-verificado
```

### Badge não aparece no frontend

Verifique se o CSS está carregado:
```php
if ( ! did_action( 'wp_head' ) ) {
    echo apollo_get_membership_badge_css();
}
```

### Permissões REST não funcionam

Verifique se o usuário tem a capability:
```php
var_dump( current_user_can( 'edit_apollo_users' ) );
```

### Erro ao criar membership customizada

Verifique formato das cores:
- ✅ `#FF5733` (correto)
- ❌ `FF5733` (falta #)
- ❌ `rgb(255,87,51)` (formato inválido)

## Próximas Funcionalidades (Roadmap)

- [ ] Expiração de memberships (tempo limitado)
- [ ] Múltiplas memberships por usuário
- [ ] Benefícios por membership (CPTs, taxonomias permitidas)
- [ ] Integração com pagamentos (WooCommerce, etc.)
- [ ] Notificação automática ao receber nova membership

## Suporte

Para dúvidas ou problemas:
- Verifique o log de auditoria: `wp apollo db-test`
- Consulte logs do WordPress: `wp-content/debug.log`
- Execute testes: `vendor/bin/phpunit`

---

**Apollo Core v3.0.0** | Desenvolvido com ❤️ pela equipe Apollo

