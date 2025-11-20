# 📋 Plano Completo: Motion.dev + ShadCN + Tailwind - Refatoração Apollo
## Lista Detalhada de Todas as Fases e Subitens

**Data:** 15/01/2025  
**Projeto:** Apollo Events Manager  
**Total de Fases:** 13  
**Total de Tarefas:** 144+

---

## 📊 VISÃO GERAL

### Fases Principais:
1. ✅ **FASE 1:** Setup Base e Instalação
2. ✅ **FASE 2:** Refatoração Event Card
3. ✅ **FASE 3:** Toggle List View
4. ✅ **FASE 4:** Modal Popup
5. ✅ **FASE 5:** Standalone Page Galeria
6. ✅ **FASE 6:** Sistema de Estatísticas
7. ✅ **FASE 7:** Dashboards
8. ✅ **FASE 8:** Local Page
9. ✅ **FASE 9:** Context Menu
10. ✅ **FASE 10:** Forms ShadCN
11. ✅ **FASE 11:** Construtor (Criação Automática)
12. ✅ **FASE 12:** Apollo Social
13. ✅ **FASE 13:** Estatísticas Apollo Social

---

## 🎯 FASE 1: Setup Base e Instalação

### 1.1 Instalar Motion.dev e Dependências
- [ ] Criar `package.json` em `apollo-events-manager/`
- [ ] Adicionar `framer-motion@latest`
- [ ] Adicionar `@radix-ui/react-*` (base para ShadCN)
- [ ] Adicionar `tailwindcss@latest`
- [ ] Adicionar `autoprefixer@latest`
- [ ] Adicionar `postcss@latest`
- [ ] Criar `tailwind.config.js` com tema iOS
- [ ] Criar `postcss.config.js`
- [ ] Configurar build script para compilar Tailwind

### 1.2 Criar Loader Centralizado Motion.dev
- [ ] Criar `includes/motion-loader.php`
- [ ] Carregar framer-motion via CDN ou bundle local
- [ ] Verificar se já carregado (evitar duplicatas)
- [ ] Hook em `wp_enqueue_scripts` com prioridade alta
- [ ] Integrar com `apollo-shadcn-loader.php` existente

### 1.3 Atualizar Versões para 0.1.0
- [ ] `apollo-events-manager.php`: Linha 6 → `'0.1.0'`
- [ ] `apollo-events-manager.php`: Linha 21 → `'0.1.0'`
- [ ] Remover `APOLLO_AEM_VERSION`
- [ ] Usar apenas `APOLLO_WPEM_VERSION`
- [ ] Atualizar todos os arquivos que referenciam versão

### 1.4 Remover Shortcode [apollo_events]
- [ ] Remover registro em `apollo-events-manager.php`
- [ ] Verificar e remover handlers em `includes/shortcodes/`
- [ ] Manter apenas `[events]` como shortcode principal

---

## 🎨 FASE 2: Refatoração Event Card (MANTÉM DESIGN INTACTO)

### 2.1 Event Card Base
- [ ] **MANTER** HTML/CSS exato conforme CodePen original
- [ ] Manter border radius invertido (superior direito arredondado)
- [ ] Manter dia acima da imagem (box-date-event fora do picture)
- [ ] Adicionar `data-motion-card="true"`
- [ ] Adicionar `data-event-id="<?php echo $event_id; ?>"`
- [ ] Adicionar classes Tailwind: `transition-all duration-300`
- [ ] Adicionar classes Tailwind: `hover:scale-[1.02]`

### 2.2 Animações Motion.dev no Event Card
- [ ] Criar `assets/js/motion-event-card.js`
- [ ] Usar `motion.div` para animação de entrada (fade + slide)
- [ ] Hover effect com `whileHover={{ scale: 1.02 }}`
- [ ] Click animation com `whileTap={{ scale: 0.98 }}`
- [ ] Integrar com `apollo-events-portal.js` existente

---

## 📋 FASE 3: Toggle List View com Infinite Loading

### 3.1 List View Toggle
- [ ] Modificar `assets/js/apollo-events-portal.js`
- [ ] Toggle entre grid (cards) e list (infinite-loading style)
- [ ] Quando list: usar estilo do exemplo Motion.dev
- [ ] Animação de transição com `motion.div` e `layoutId`
- [ ] Criar `templates/event-list-view.php`
- [ ] Layout vertical estilo infinite-loading
- [ ] Data, nome, evento, local em linha
- [ ] Sem imagem (conforme solicitado)
- [ ] Animações de entrada stagger

### 3.2 Implementar Infinite Scroll (opcional)
- [ ] Adicionar Intersection Observer
- [ ] Carregar mais eventos ao scroll
- [ ] Animação de entrada para novos cards

---

## 🪟 FASE 4: Event Single Page como Popup (Modal)

### 4.1 Modal com Motion.dev
- [ ] Modificar `assets/js/event-modal.js`
- [ ] Usar `AnimatePresence` do Motion.dev
- [ ] Layout shared entre card e modal (smooth transition)
- [ ] Backdrop blur com `motion.div`
- [ ] Animação de entrada: scale + fade
- [ ] Modificar `templates/single-event-page.php`
- [ ] Adicionar `data-motion-modal="true"`
- [ ] Estrutura compatível com shared layout

### 4.2 Funcionalidades do Modal
- [ ] Botão "Copiar URL" do evento
- [ ] Botão "Abrir como página" (navega para URL standalone)
- [ ] Fechar com ESC ou click fora
- [ ] Animações suaves de entrada/saída

---

## 🖼️ FASE 5: Event Single Page Standalone (Galeria Card Stack)

### 5.1 Galeria de Imagens
- [ ] Modificar `templates/single-event-standalone.php`
- [ ] Seção de galeria com estilo card-stack
- [ ] Swipe left/right para navegar imagens
- [ ] Imagens de produção (`_3_imagens_promo`, `_imagem_final`)
- [ ] Implementar com `motion.div` e drag gestures

### 5.2 Melhorias na Página Standalone
- [ ] Animações de scroll reveal
- [ ] Transições suaves entre seções
- [ ] Layout melhorado com ShadCN components

---

## 📊 FASE 6: Sistema de Estatísticas/Tracker

### 6.1 Criar Tabela de Estatísticas
- [ ] Criar `includes/class-event-statistics.php`
- [ ] Método `track_event_view($event_id, $type)`
- [ ] `$type = 'popup'` ou `'page'`
- [ ] Método `get_event_stats($event_id)`
- [ ] Retorna: popup_count, page_count, total_views
- [ ] Usar `wp_insert_post` para criar CPT `apollo_event_stat` ou tabela custom
- [ ] Hook em `wp_footer` para track automático
- [ ] Se modal aberto: `track_event_view($event_id, 'popup')`
- [ ] Se página standalone: `track_event_view($event_id, 'page')`

### 6.2 Dashboard de Estatísticas
- [ ] Criar `templates/admin-event-statistics.php`
- [ ] Exibir contadores estilo Motion.dev
- [ ] Animações de números incrementando
- [ ] Gráficos com `line-graph` style
- [ ] Adicionar submenu em admin: "Eventos > Estatísticas"

### 6.3 AJAX Endpoint para Estatísticas
- [ ] Criar `includes/ajax-statistics.php`
- [ ] `wp_ajax_apollo_track_event_view`
- [ ] `wp_ajax_apollo_get_event_stats`
- [ ] Nonce verification e sanitization

---

## 📈 FASE 7: Dashboards com Smooth Tabs

### 7.1 Dashboard Principal
- [ ] Modificar `templates/page-event-dashboard.php`
- [ ] Implementar tabs com Motion.dev
- [ ] Transições suaves entre tabs
- [ ] Animações de conteúdo ao trocar tab
- [ ] Criar `assets/js/motion-dashboard.js`
- [ ] Componente de tabs reutilizável
- [ ] Usar `motion.div` com `layoutId` para transições

### 7.2 Dashboard de Usuário (Co-Author)
- [ ] Criar `templates/user-event-dashboard.php`
- [ ] Estatísticas dos próprios eventos
- [ ] Visualizações, cliques, compartilhamentos
- [ ] Gráficos estilo `line-graph`

---

## 📍 FASE 8: Local Page com Cursor Trail

### 8.1 Página de Local
- [ ] Modificar `templates/single-event_local.php`
- [ ] Efeito cursor trail no nome do local
- [ ] Animação de entrada do endereço
- [ ] Lista de eventos futuros abaixo
- [ ] Mapa OSM/Google Maps funcional
- [ ] Criar `assets/js/motion-local-page.js`
- [ ] Implementar cursor trail effect
- [ ] Animações de reveal para eventos

---

## 🎯 FASE 9: Context Menu (base-context-menu)

### 9.1 Sistema de Context Menu
- [ ] Criar `includes/class-context-menu.php`
- [ ] Admin: menu completo (copy, paste, edit, delete)
- [ ] Usuários/Guests: menu simplificado (copy URL, share)
- [ ] Criar `assets/js/motion-context-menu.js`
- [ ] Usar estilo base-context-menu do Motion.dev
- [ ] Animações de entrada/saída
- [ ] Posicionamento inteligente (evitar sair da tela)

---

## 📝 FASE 10: Forms com ShadCN Components

### 10.1 Refatorar Metaboxes Admin
- [ ] Modificar `includes/admin-metaboxes.php`
- [ ] Substituir inputs nativos por ShadCN components
- [ ] Usar `base-tabs` para organizar campos
- [ ] Usar `base-select` para selects
- [ ] Animações de validação com Motion.dev

### 10.2 Formulário Público
- [ ] Modificar `templates/page-cenario-new-event.php`
- [ ] Implementar ShadCN form components
- [ ] Contador de caracteres estilo `characters-remaining`
- [ ] Validação com animações
- [ ] Submit com loading state animado

---

## 🏗️ FASE 11: Construtor Poderoso (Criação Automática de Páginas)

### 11.1 Refatorar Activation Hook
- [ ] Modificar `apollo_events_manager_activate()`
- [ ] Criar página `/eventos/` automaticamente:
  - Título: "Eventos"
  - Slug: `eventos`
  - Conteúdo: `[events]`
  - Template: `pagx_appclean` (se disponível)
  - Status: `publish`
- [ ] Criar página `/djs/` (se shortcode existir)
- [ ] Criar página `/locais/` (se shortcode existir)
- [ ] Verificar se páginas já existem (evitar duplicatas)

### 11.2 Criar Páginas de Dashboard
- [ ] Criar página `/dashboard-eventos/`:
  - Conteúdo: `[apollo_event_user_overview]`
  - Template: canvas
- [ ] Criar página `/mod-eventos/`:
  - Conteúdo: template de moderação
  - Restrito a editores

---

## 👥 FASE 12: Apollo Social (Após apollo-events-manager)

### 12.1 Feed Social (app-store style)
- [ ] Criar `templates/social-feed.php`
- [ ] Cards estilo App Store
- [ ] Swipe actions para interações
- [ ] Animações de entrada stagger
- [ ] Layout com ShadCN

### 12.2 Postagem com Contador
- [ ] Criar `templates/social-post-form.php`
- [ ] Limite de 281 caracteres
- [ ] Contador animado estilo Motion.dev
- [ ] Validação em tempo real
- [ ] Submit com animação

### 12.3 Chat/Mensagens
- [ ] Modificar templates de chat
- [ ] Variants para estados (enviado, entregue, lido)
- [ ] Warp overlay para transições
- [ ] Swipe actions para ações rápidas

### 12.4 Notificações
- [ ] Criar sistema de notificações
- [ ] Lista estilo Motion.dev
- [ ] Animações de entrada
- [ ] Desktop: popup notifications
- [ ] Mobile: lista integrada

### 12.5 Imagens Fullscreen
- [ ] Implementar em todos os plugins
- [ ] Click em imagem → modal fullscreen
- [ ] Shared layout animation
- [ ] Navegação entre imagens
- [ ] Zoom e pan

---

## 📊 FASE 13: Estatísticas Apollo Social

### 13.1 Engagement Stats
- [ ] Criar dashboard admin
- [ ] Estatísticas de todos os usuários
- [ ] Performance de eventos e CPTs
- [ ] Co-authors veem próprias estatísticas
- [ ] Animações de números incrementando

### 13.2 Gráficos (line-graph)
- [ ] Implementar em dashboards
- [ ] Gráfico de visualizações ao longo do tempo
- [ ] Gráfico de engajamento
- [ ] Gráfico de eventos por categoria

---

## 📁 ARQUIVOS PRINCIPAIS

### Novos Arquivos a Criar:
- `apollo-events-manager/package.json`
- `apollo-events-manager/tailwind.config.js`
- `apollo-events-manager/postcss.config.js`
- `apollo-events-manager/includes/motion-loader.php`
- `apollo-events-manager/includes/class-event-statistics.php`
- `apollo-events-manager/includes/ajax-statistics.php`
- `apollo-events-manager/includes/class-context-menu.php`
- `apollo-events-manager/assets/js/motion-event-card.js`
- `apollo-events-manager/assets/js/motion-dashboard.js`
- `apollo-events-manager/assets/js/motion-local-page.js`
- `apollo-events-manager/assets/js/motion-context-menu.js`
- `apollo-events-manager/templates/event-list-view.php`
- `apollo-events-manager/templates/admin-event-statistics.php`
- `apollo-events-manager/templates/user-event-dashboard.php`

### Arquivos a Modificar:
- `apollo-events-manager/apollo-events-manager.php`
- `apollo-events-manager/templates/event-card.php`
- `apollo-events-manager/assets/js/apollo-events-portal.js`
- `apollo-events-manager/assets/js/event-modal.js`
- `apollo-events-manager/templates/single-event-page.php`
- `apollo-events-manager/templates/single-event-standalone.php`
- `apollo-events-manager/templates/single-event_local.php`
- `apollo-events-manager/includes/admin-metaboxes.php`
- `apollo-events-manager/templates/page-cenario-new-event.php`
- `apollo-events-manager/templates/page-event-dashboard.php`
- `apollo-social/includes/apollo-shadcn-loader.php`

---

## ⚠️ NOTAS IMPORTANTES

- ✅ Event Card deve manter design original (border radius invertido, dia acima)
- ✅ [apollo_events] shortcode deve ser completamente removido
- ✅ [events] é o único shortcode principal
- ✅ Versão 0.1.0 para todos os plugins
- ✅ Estatísticas devem rastrear popup vs page views
- ✅ Construtor deve criar páginas automaticamente na ativação
- ✅ Design focado em iOS (rounded, shadows suaves, animações fluidas)

---

## 🎯 ORDEM DE EXECUÇÃO RECOMENDADA

1. **FASE 1:** Setup base (Motion.dev, versões, remover shortcode)
2. **FASE 2:** Event Card (manter design, adicionar animações)
3. **FASE 3:** List View toggle
4. **FASE 4:** Modal popup
5. **FASE 5:** Standalone page galeria
6. **FASE 6:** Sistema de estatísticas
7. **FASE 7:** Dashboards
8. **FASE 8:** Local page
9. **FASE 9:** Context menu
10. **FASE 10:** Forms ShadCN
11. **FASE 11:** Construtor (criação automática)
12. **FASE 12-13:** Apollo Social (após eventos completo)

---

**Status:** ✅ Lista Completa Criada  
**Total de Tarefas:** 144+  
**Próximo Passo:** Começar FASE 1

---

**Criado por:** AI Assistant  
**Data:** 15/01/2025  
**Versão:** 1.0

