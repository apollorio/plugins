# 🏗️ Canvas Builder - Strong Constructor for Apollo Canvas Pages

## Visão Geral

O **CanvasBuilder** é um construtor robusto que cria páginas Canvas Mode **APENAS com assets Apollo** (JS, CSS, Data), garantindo isolamento completo do tema WordPress.

## Rotas Criadas

### ✅ Rotas Implementadas

1. **`/feed/`** - Feed Social Apollo
   - Handler: `FeedRenderer`
   - Template: `templates/feed/feed.php`
   - Assets: `feed.css`, `feed.js`

2. **`/chat/`** - Lista de Conversas
   - Handler: `ChatListRenderer`
   - Template: `templates/chat/chat-list.php`
   - Assets: `chat.css`, `chat.js`

3. **`/chat/{userID}`** - Chat com Usuário Específico
   - Handler: `ChatSingleRenderer`
   - Template: `templates/chat/chat-single.php`
   - Assets: `chat.css`, `chat.js`
   - Parâmetro: `user_id` (query var)

4. **`/id/{userID}`** - Perfil de Usuário por ID
   - Handler: `UserProfileRenderer`
   - Template: `templates/users/profile.php`
   - Assets: `user-profile.css`, `user-profile.js`
   - Parâmetro: `user_id` (query var)

5. **`/eco/`** - Diretório de Usuários
   - Handler: `UsersDirectoryRenderer`
   - Template: `templates/users/directory.php`
   - Assets: `users-directory.css`, `users-directory.js`
   - Exibe: Todos os usuários registrados

6. **`/ecoa/`** - Diretório de Usuários (Alternativo)
   - Handler: `UsersDirectoryRenderer` (mesmo handler)
   - Template: `templates/users/directory.php`
   - Assets: `users-directory.css`, `users-directory.js`
   - Exibe: Todos os usuários registrados

## Arquitetura

### CanvasBuilder Class

```php
CanvasBuilder::build($route_config)
```

**Fluxo de Construção:**

1. **Install Output Guards** - Remove interferência do tema
2. **Prepare Template Data** - Coleta dados da rota
3. **Render Handler** - Executa handler específico da rota
4. **Enqueue Apollo Assets** - Carrega APENAS assets Apollo
5. **Render Canvas Layout** - Renderiza layout completo

### Filtro Forte de Assets

O `AssetsManager` agora possui filtros **fortes** que garantem:

- ✅ **Apenas handles Apollo** são mantidos
- ✅ **Apenas URLs Apollo** são permitidas
- ✅ **Todos os outros assets são removidos**

**Handles Permitidos:**
- `apollo-canvas-mode`
- `apollo-modules`
- `apollo-feed`
- `apollo-chat`
- `apollo-user-profile`
- `apollo-users-directory`
- Qualquer handle que comece com `apollo-`

**Patterns Permitidos:**
- `/apollo-` (em URLs)
- `assets.apollo.rio.br`
- `remixicon`

## Handlers Criados

### FeedRenderer
- Renderiza feed social
- Busca posts recentes
- Inclui dados do usuário atual

### ChatListRenderer
- Renderiza lista de conversas
- TODO: Implementar lógica de conversas

### ChatSingleRenderer
- Renderiza chat com usuário específico
- Valida `user_id`
- TODO: Implementar lógica de mensagens

### UserProfileRenderer
- Renderiza perfil de usuário por ID
- Valida `user_id`
- Coleta dados completos do usuário

### UsersDirectoryRenderer
- Renderiza diretório completo de usuários
- Busca TODOS os usuários registrados
- Ordena por data de registro (mais recentes primeiro)

## Dados Disponíveis no JavaScript

Todas as rotas têm acesso a `apolloCanvasData`:

```javascript
apolloCanvasData = {
    route: 'feed',
    type: '',
    param: '',
    user_id: 0,
    ajaxUrl: '/wp-admin/admin-ajax.php',
    nonce: '...',
    pluginUrl: '...',
    // Dados específicos do handler
    posts: [...], // FeedRenderer
    conversations: [...], // ChatListRenderer
    messages: [...], // ChatSingleRenderer
    user: {...}, // UserProfileRenderer
    users: [...], // UsersDirectoryRenderer
}
```

## Proteção contra Feed RSS

O sistema **NÃO interfere** com feeds RSS do WordPress:

- ✅ `/feed/rss/` - Feed RSS padrão funciona
- ✅ `/feed/atom/` - Feed Atom funciona
- ✅ `/feed/` - Apenas intercepta se `apollo_route=feed` estiver presente

## Próximos Passos

1. **Criar Templates** - Templates Canvas para cada rota
2. **Criar Assets** - CSS e JS específicos para cada rota
3. **Implementar Lógica** - Completar TODOs nos handlers
4. **Testar Rotas** - Verificar funcionamento de cada rota

## Status

✅ **CanvasBuilder criado**  
✅ **Rotas registradas**  
✅ **Handlers criados**  
✅ **Filtro forte de assets implementado**  
⏳ **Templates pendentes**  
⏳ **Assets CSS/JS pendentes**

---

**Última Atualização:** $(date)

