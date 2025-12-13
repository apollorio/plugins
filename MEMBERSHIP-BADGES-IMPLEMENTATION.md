# Membership Badges - Implementação Completa

## Final Wiring Summary

### Helper Function Criada
**Arquivo**: `apollo-social/src/Helpers/BadgesHelper.php`

**Função principal**:
```php
apollo_social_get_user_badges( $user_id )
```

**Retorna**: Array normalizado de badges:
```php
[
    ['class' => 'apollo', 'label' => 'Producer'],
    ['class' => 'green',  'label' => 'DJ'],
]
```

**Características**:
- Lê de `_apollo_badges` (user meta) - novo sistema de múltiplos badges
- Fallback para `_apollo_membership` (legacy single membership)
- Mapeia membership slugs para classes CSS e labels
- Retorna array vazio se não houver badges

### Onde o Helper é Usado

1. **Template: Feed Posts de Usuário**
   - Arquivo: `apollo-social/templates/feed/partials/post-user.php`
   - Linha: ~42-60
   - Renderiza badges após o nome do autor

2. **Template: Feed Posts de Eventos**
   - Arquivo: `apollo-social/templates/feed/partials/post-event.php`
   - Linha: ~40-60
   - Renderiza badges após o nome do autor do evento

### Como o Painel Admin Conecta ao Helper

**Data Flow**: Admin → REST API → User Meta → Helper → HTML

1. **Admin Panel** (`apollo-core/admin/moderate-users-membership.php`):
   - UI: Checkboxes múltiplos para selecionar badges
   - JavaScript: Envia array de badges via AJAX para `/apollo/v1/membros/badges`

2. **REST API** (`apollo-core/includes/rest-membership.php`):
   - Rota: `POST /apollo/v1/membros/badges`
   - Callback: `apollo_rest_set_user_badges()`
   - Salva: `update_user_meta( $user_id, '_apollo_badges', $badges_array )`

3. **Helper Function** (`apollo-social/src/Helpers/BadgesHelper.php`):
   - Lê: `get_user_meta( $user_id, '_apollo_badges', true )`
   - Mapeia: Membership slugs → CSS classes + labels
   - Retorna: Array normalizado para templates

4. **Templates** (`apollo-social/templates/feed/partials/*.php`):
   - Chama: `apollo_social_get_user_badges( $author_id )`
   - Renderiza: `<span class="ap-social-badge {class}">{label}</span>`

## E2E Checklist

### Teste Manual

1. **Criar usuário e atribuir badges**:
   - Acesse: Admin → Mod → Members
   - Selecione um usuário
   - Marque checkboxes: "Producer" (apollo) e "DJ" (green)
   - Verifique que os checkboxes são salvos automaticamente (feedback visual verde)

2. **Publicar post social**:
   - Faça login como o usuário com badges
   - Publique um post no feed social
   - Verifique o feed

3. **Confirmar renderização**:
   - No feed, o post deve mostrar:
     ```html
     <span class="ap-social-username">Nome do Usuário</span>
     <span class="ap-social-badge apollo">Producer</span>
     <span class="ap-social-badge green">DJ</span>
     ```

4. **Testar usuário sem badges**:
   - Usuário sem badges não deve mostrar nenhum `<span class="ap-social-badge">`
   - Apenas o nome deve aparecer

## Arquivos Modificados/Criados

### Criados
1. `apollo-social/src/Helpers/BadgesHelper.php` - Helper canônico para badges
2. `MEMBERSHIP-BADGES-AUDIT.md` - Relatório da Fase 1 (análise)
3. `MEMBERSHIP-BADGES-IMPLEMENTATION.md` - Este arquivo

### Modificados
1. `apollo-social/apollo-social.php` - Carrega BadgesHelper.php
2. `apollo-social/templates/feed/partials/post-user.php` - Renderiza badges
3. `apollo-social/templates/feed/partials/post-event.php` - Renderiza badges
4. `apollo-core/admin/moderate-users-membership.php` - UI multi-select de badges
5. `apollo-core/includes/rest-membership.php` - Rota REST `/membros/badges`

## Mapeamento Membership → Badge CSS

O helper mapeia automaticamente cores de membership para classes CSS:

- `#FF8C42` / `#f97316` → `apollo` (laranja)
- `#63c720` → `green` (verde)
- `#007BFF` / `#167cf9` → `blue` (azul)
- `#8A2BE2` / `#9820c7` → `purple` (roxo)
- `#FFD700` / `#edd815` → `yellow` (amarelo)
- `#9AA0A6` → `muted` (cinza)
- `#d90d21` → `red` (vermelho)
- `#d615b6` → `pink` (rosa)

## Risks / TODOs

### Implementado ✅
- ✅ Helper canônico criado
- ✅ Templates sociais atualizados
- ✅ Admin panel com multi-select
- ✅ REST API para salvar badges
- ✅ Fallback para legacy single membership

### Pendente / Melhorias Futuras
1. **Outros templates**: Verificar se há outros templates que precisam de badges (comentários, perfis, etc.)
2. **Migração de dados**: Script para migrar `_apollo_membership` → `_apollo_badges` (opcional)
3. **Cache**: Considerar cache para `apollo_social_get_user_badges()` se performance for problema
4. **Validação**: Adicionar validação de badges no onboarding (se aplicável)
5. **Eventos**: Verificar se templates de eventos precisam de badges (já implementado em post-event.php)
6. **Grupos/Comunidades**: Verificar se templates de grupos precisam de badges

### Notas Importantes
- O sistema mantém compatibilidade com o sistema legacy (`_apollo_membership`)
- Badges são opcionais - usuários sem badges não mostram nada
- O admin panel permite múltiplos badges por usuário
- CSS classes já existem e não foram modificadas

