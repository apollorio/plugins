# Membership Badges Audit - FASE 1

## Membership Data Model

### Storage Location
- **Primary**: `_apollo_membership` (user meta) - armazena UM membership slug (string)
- **Types Definition**: `apollo_memberships` (option) - array de tipos disponíveis
- **Functions**: 
  - `apollo_get_user_membership( $user_id )` - retorna slug do membership
  - `apollo_set_user_membership( $user_id, $slug )` - define membership
  - `apollo_get_memberships()` - retorna todos os tipos

### Data Structure Example
```php
// User meta (_apollo_membership):
'apollo' // ou 'dj', 'prod', 'govern', etc.

// Option (apollo_memberships):
array(
    'apollo' => array(
        'label' => 'Apollo',
        'frontend_label' => 'Apollo',
        'color' => '#FF8C42',
        'text_color' => '#7A3E00',
    ),
    'dj' => array(
        'label' => 'DJ',
        'frontend_label' => 'DJ',
        'color' => '#8A2BE2',
        'text_color' => '#4B0082',
    ),
    // ...
)
```

### Limitation
**O sistema atual só permite UM membership por usuário.** Para múltiplos badges, precisamos criar um novo sistema que armazena array de badges.

## Admin Mod Panel

### Location
- **File**: `apollo-core/admin/moderation-page.php`
- **Tab**: "Members" (aba 2)
- **Function**: `apollo_render_user_membership_selector( $user )`

### Current Implementation
- Dropdown single-select que permite escolher UM membership
- Salva via REST: `POST /apollo/v1/membros/definir`
- Permissions: `edit_apollo_users` capability

### Actions Available
1. **Set membership**: Seleciona um membership do dropdown
2. **Manage types**: `apollo_render_membership_types_manager()` - criar/editar/deletar tipos

### Data Flow
```
Admin UI → REST API (/membros/definir) → apollo_set_user_membership() → update_user_meta( '_apollo_membership', $slug )
```

## Templates & REST

### Social Feed Templates
- **File**: `apollo-social/templates/feed/partials/post-user.php`
- **Line 42**: Renderiza nome do autor: `<?php echo esc_html( $data['author']['name'] ?? '' ); ?>`
- **Status**: Não renderiza badges atualmente

### Missing Integration
- ❌ Não há função helper para badges
- ❌ Templates não renderizam badges
- ❌ Sistema atual não suporta múltiplos badges

## Status & Issues

### Overall Classification
**Partially Implemented** - Sistema de membership existe mas:
- Só suporta UM membership por usuário
- Não há integração visual com badges no frontend
- Não há mapeamento membership → badge CSS class

### Key Issues
1. **Single membership limitation**: Sistema atual não permite múltiplos badges
2. **No badge helper**: Não há função canônica para obter badges do usuário
3. **No template integration**: Templates sociais não renderizam badges
4. **No CSS mapping**: Não há mapeamento entre membership slugs e classes CSS de badges

## Recommended Next Phases

1. **Criar sistema de badges múltiplos**:
   - Novo user meta: `_apollo_badges` (array de slugs)
   - Mapeamento membership → badge (class + label)

2. **Implementar helper canônico**:
   - `apollo_social_get_user_badges( $user_id )`
   - Retorna array normalizado: `[['class' => 'apollo', 'label' => 'Producer'], ...]`

3. **Conectar nos templates**:
   - Atualizar `post-user.php` para renderizar badges
   - Usar classes CSS existentes: `ap-social-badge`, `apollo`, `green`, etc.

4. **Atualizar admin panel**:
   - Mudar de single-select para multi-select de badges
   - Salvar array em `_apollo_badges`

