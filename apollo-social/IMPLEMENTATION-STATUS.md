# Status de Implementação - Apollo Social

## ✅ Implementado

### 1. Sistema de Roles
- **RoleManager** (`src/Core/RoleManager.php`)
  - Renomeia roles WordPress mantendo slugs
  - Subscriber → Clubber (pode submeter eventos como draft)
  - Contributor → Cena::rio
  - Author → Cena::rj
  - Editor → Apollo::rio
  - Administrator → Apollo
  - Cria role `cena-rio` com capabilities de Contributor

### 2. Detecção PWA
- **PWADetector** (`src/Core/PWADetector.php`)
  - Detecta se apollo-rio está instalado
  - Detecta modo PWA (cookie, headers, iOS standalone)
  - Fornece instruções de instalação iOS/Android
  - Suporta modo "clean" (sem header Apollo)

### 3. Constructor Robusto
- **Plugin.php** com `__construct()`
  - Registra RoleManager
  - Cria páginas Canvas automaticamente
  - Prepara hooks e CPTs
  - Inicializa Canvas pages: /feed/, /chat/, /painel/, /cena/, /cena-rio/

### 4. Canvas Builder
- **CanvasBuilder** atualizado
  - Integração com PWADetector
  - Layout template com suporte PWA
  - Instruções de instalação PWA quando necessário
  - Header Apollo condicional (app::rio vs apollo::rio clean)

### 5. Rotas Registradas
- `/feed/` - Feed Social
- `/chat/` - Lista de conversas
- `/chat/{userID}` - Chat individual
- `/id/{userID}` - Perfil público customizável
- `/clubber/{userID}` - Alternativa para perfil
- `/painel/` - Dashboard próprio com tabs
- `/cena/` e `/cena-rio/` - Página Cena::rio
- `/eco/` e `/ecoa/` - Diretório de usuários

### 6. Renderers Criados
- **FeedRenderer** - Feed social
- **ChatListRenderer** - Lista de conversas
- **ChatSingleRenderer** - Chat individual
- **UserDashboardRenderer** - Dashboard com tabs (próprio) e perfil customizável (público)
- **CenaRenderer** - Página Cena::rio
- **UsersDirectoryRenderer** - Diretório de usuários

## 🚧 Pendente

### 1. Templates Baseados em CodePen
- [ ] `/feed/feed.php` - Baseado em https://codepen.io/Rafael-Valle-the-looper/pen/OPNjrPm
- [ ] `/chat/chat-list.php` - Baseado em https://codepen.io/Rafael-Valle-the-looper/pen/vEGJvEG
- [ ] `/cena/cena.php` - Baseado em https://codepen.io/Rafael-Valle-the-looper/pen/ogxeJyz
- [ ] `/users/dashboard.php` - Atualizar com tabs (Events, Metrics, Nucleo, Communities, Docs)
  - Baseado em https://codepen.io/Rafael-Valle-the-looper/pen/qEZXyRQ

### 2. Sistema de Grupos
- [ ] CPT para Grupos (Comunidade/Núcleo)
- [ ] Sistema de aprovação admin
- [ ] Interface de criação de grupos
- [ ] Filtros e listagem

### 3. Funcionalidades dos Renderers
- [ ] FeedRenderer: Implementar query de posts sociais
- [ ] ChatListRenderer: Implementar sistema de conversas
- [ ] UserDashboardRenderer: Implementar métodos de tabs (getFavoriteEvents, getUserMetrics, etc.)
- [ ] CenaRenderer: Implementar dados da cena

### 4. Assets CSS/JS
- [ ] `assets/css/feed.css` - Estilos do feed
- [ ] `assets/js/feed.js` - Interações do feed (tabs, like, etc.)
- [ ] `assets/css/chat.css` - Estilos do chat
- [ ] `assets/js/chat.js` - Funcionalidade do chat
- [ ] `assets/css/cena.css` - Estilos da página Cena
- [ ] `assets/js/cena.js` - Interações da página Cena

### 5. Integração com apollo-events-manager
- [ ] Permitir Clubbers submeter eventos como draft
- [ ] Integração de eventos no feed
- [ ] Link entre eventos e grupos

## 📋 Próximos Passos

1. **Criar templates HTML** baseados nos CodePen designs
2. **Implementar sistema de grupos** com CPT e aprovação
3. **Completar funcionalidades** dos renderers
4. **Criar assets CSS/JS** para cada página
5. **Testar integração** com apollo-rio PWA

## 🔗 Referências

- Design Feed: https://codepen.io/Rafael-Valle-the-looper/pen/OPNjrPm
- Design Chat: https://codepen.io/Rafael-Valle-the-looper/pen/vEGJvEG
- Design Cena: https://codepen.io/Rafael-Valle-the-looper/pen/ogxeJyz
- Design Dashboard: https://codepen.io/Rafael-Valle-the-looper/pen/qEZXyRQ

