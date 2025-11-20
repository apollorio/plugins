# 📋 TODO LIST - TAREFAS PENDENTES (88/144 → 144+)

## ✅ PROGRESSO ATUAL: 88/144 TAREFAS CONCLUÍDAS

---

## 📋 FASE 1: Setup Base e Instalação (PENDENTES)

### 1.1 Build Configuration
- [ ] **TODO 89:** Configurar build script para compilar Tailwind
  - Criar `package.json` scripts para build
  - Configurar watch mode para desenvolvimento
  - Testar compilação de `input.css` → `output.css`

### 1.4 Shortcode Cleanup
- [ ] **TODO 90:** Verificar e remover handlers em `includes/shortcodes/`
  - Revisar todos os arquivos em `includes/shortcodes/`
  - Remover handlers de `[apollo_events]`
  - Manter apenas handlers de `[events]`

---

## 📋 FASE 3: Toggle List View com Infinite Loading (PENDENTES)

### 3.1 Motion.dev Animations
- [ ] **TODO 91:** Criar animação de transição com `motion.div` e `layoutId`
  - Implementar `layoutId` para smooth transition entre grid/list
  - Usar `AnimatePresence` para entrada/saída
  - Testar transição suave entre modos

- [ ] **TODO 92:** Adicionar animações de entrada stagger para lista
  - Implementar stagger delay para itens da lista
  - Usar `motion.div` com `variants`
  - Testar animação cascata

---

## 📋 FASE 4: Event Single Page como Popup (Modal) (PENDENTES)

### 4.1 Shared Layout & Motion.dev
- [ ] **TODO 93:** Implementar layout shared entre card e modal (smooth transition)
  - Usar `layoutId` para transição do card → modal
  - Implementar `AnimatePresence` wrapper
  - Testar transição suave

- [ ] **TODO 94:** Estrutura compatível com shared layout em `single-event-page.php`
  - Adicionar `layoutId` nos elementos compartilhados
  - Configurar motion components
  - Testar sincronização card → modal

### 4.2 Modal Animations
- [ ] **TODO 95:** Animações suaves de entrada/saída no modal com Motion.dev
  - Substituir MicroModal básico por Motion.dev
  - Implementar `scale` + `fade` + `blur` animations
  - Testar performance

---

## 📋 FASE 5: Event Single Page Standalone (Galeria Card Stack) (PENDENTES)

### 5.2 Layout Improvements
- [ ] **TODO 96:** Layout melhorado com ShadCN components
  - Implementar ShadCN components no standalone
  - Melhorar UI/UX com components library
  - Testar responsividade

---

## 📋 FASE 6: Sistema de Estatísticas/Tracker (PENDENTES)

### 6.1 Database Structure
- [ ] **TODO 97:** Criar CPT `apollo_event_stat` ou tabela custom para armazenar estatísticas
  - Decidir: CPT vs tabela custom
  - Implementar estrutura de dados
  - Criar índices para performance
  - Testar queries

### 6.2 Dashboard Graphics
- [ ] **TODO 98:** Gráficos com estilo line-graph
  - Implementar Chart.js ou biblioteca similar
  - Criar gráficos de visualizações ao longo do tempo
  - Estilo Apollo (cores, animações)
  - Testar dados reais

---

## 📋 FASE 7: Dashboards com Smooth Tabs (PENDENTES)

### 7.1 Motion.dev Tab Transitions
- [ ] **TODO 99:** Usar `motion.div` com `layoutId` para transições de tabs
  - Substituir transições CSS por Motion.dev
  - Implementar underline animado
  - Testar smooth transitions

### 7.2 User Dashboard Graphics
- [ ] **TODO 100:** Gráficos estilo line-graph no dashboard de usuário
  - Implementar gráficos de engajamento
  - Visualizações dos próprios eventos
  - Métricas de performance
  - Testar com dados reais

---

## 📋 FASE 9: Context Menu (base-context-menu) (PENDENTES)

### 9.1 Motion.dev Style
- [ ] **TODO 101:** Usar estilo base-context-menu do Motion.dev
  - Implementar animations do Motion.dev
  - Spring animations para entrada/saída
  - Testar performance e UX

---

## 📋 FASE 10: Forms com ShadCN Components (PENDENTES)

### 10.1 Admin Metaboxes Refactor
- [ ] **TODO 102:** Modificar `admin-metaboxes.php`: substituir inputs nativos por ShadCN
  - Mapear todos os inputs atuais
  - Substituir por ShadCN components
  - Manter funcionalidade existente
  - Testar saving de dados

- [ ] **TODO 103:** Usar base-tabs para organizar campos
  - Implementar tab system nos metaboxes
  - Organizar campos por categoria
  - Testar navegação entre tabs

- [ ] **TODO 104:** Usar base-select para selects
  - Substituir selects nativos por ShadCN
  - Implementar search/filter
  - Testar funcionalidade

- [ ] **TODO 105:** Animações de validação com Motion.dev
  - Implementar shake animations para erros
  - Success animations para validação
  - Testar UX de validação

---

## 📋 FASE 12: Apollo Social (Após apollo-events-manager) (PENDENTES)

### 12.1 Social Feed
- [ ] **TODO 106:** Criar `templates/social-feed.php`: cards estilo App Store
  - Design cards com preview de posts
  - Layout responsivo
  - Infinite scroll
  - Testar performance

- [ ] **TODO 107:** Swipe actions para interações
  - Implementar swipe left/right
  - Actions: like, share, delete
  - Feedback visual
  - Testar touch gestures

- [ ] **TODO 108:** Animações de entrada stagger
  - Implementar stagger delay
  - Fade + slide entrance
  - Testar performance

- [ ] **TODO 109:** Layout com ShadCN
  - Usar ShadCN components para feed
  - Consistent styling
  - Testar responsividade

### 12.2 Social Post Form
- [ ] **TODO 110:** Criar `templates/social-post-form.php`
  - Form de criação de posts
  - Upload de mídia
  - Preview antes de publicar
  - Testar submissão

- [ ] **TODO 111:** Limite de 281 caracteres
  - Implementar contador de caracteres
  - Visual feedback quando próximo do limite
  - Prevenir submit se exceder
  - Testar edge cases

- [ ] **TODO 112:** Contador animado estilo characters-remaining
  - Animação do contador
  - Mudança de cor conforme limite
  - Smooth transitions
  - Testar UX

- [ ] **TODO 113:** Validação em tempo real
  - Validar enquanto digita
  - Feedback visual imediato
  - Mensagens de erro inline
  - Testar performance

- [ ] **TODO 114:** Submit com animação
  - Loading state animado
  - Success/error feedback
  - Disable durante submit
  - Testar fluxo completo

### 12.3 Chat Templates
- [ ] **TODO 115:** Modificar templates de chat: variants para estados (enviado, entregue, lido)
  - Implementar status indicators
  - Visual diferenciado para cada estado
  - Animações de transição
  - Testar sincronização

- [ ] **TODO 116:** Warp overlay para transições
  - Implementar warp effect
  - Smooth transitions entre screens
  - Testar performance

- [ ] **TODO 117:** Swipe actions para ações rápidas
  - Reply, delete, forward
  - Visual feedback
  - Confirmação de ações destrutivas
  - Testar gestures

### 12.4 Notifications System
- [ ] **TODO 118:** Criar sistema de notificações: lista estilo notifications-list
  - Design de notificações
  - Tipos: mention, like, comment, follow
  - Mark as read functionality
  - Testar em tempo real

- [ ] **TODO 119:** Animações de entrada
  - Slide from top para novas notificações
  - Fade out para dismiss
  - Stack animations
  - Testar performance

- [ ] **TODO 120:** Desktop: popup notifications
  - Popup no canto da tela
  - Auto-dismiss após X segundos
  - Click to open
  - Testar UX

- [ ] **TODO 121:** Mobile: lista integrada
  - Lista em página dedicada
  - Pull to refresh
  - Infinite scroll
  - Testar touch interactions

### 12.5 Fullscreen Images
- [ ] **TODO 122:** Shared layout animation
  - Smooth transition thumbnail → fullscreen
  - `layoutId` para sincronização
  - Gesture controls
  - Testar performance

---

## 📋 FASE 13: Estatísticas Apollo Social (PENDENTES)

### 13.1 Engagement Dashboard
- [ ] **TODO 123:** Criar dashboard admin: estatísticas de todos os usuários
  - Overview geral do site
  - Top users, top events, top content
  - Filtros por período
  - Testar com dados reais

- [ ] **TODO 124:** Performance de eventos e CPTs
  - Métricas de cada evento
  - Comparação entre eventos
  - Trends ao longo do tempo
  - Testar queries

- [ ] **TODO 125:** Co-authors veem próprias estatísticas
  - Dashboard individual para co-authors
  - Apenas dados dos próprios eventos
  - Comparação com média do site
  - Testar permissões

- [ ] **TODO 126:** Animações de números incrementando
  - Counter animations com Motion.dev
  - Smooth increment from 0 to value
  - Easing functions
  - Testar performance

### 13.2 Analytics Graphics
- [ ] **TODO 127:** Gráfico de visualizações ao longo do tempo
  - Line graph com Chart.js
  - Filtros por período (dia, semana, mês, ano)
  - Tooltips interativos
  - Testar performance

- [ ] **TODO 128:** Gráfico de engajamento
  - Métricas: likes, shares, comments
  - Comparação entre tipos de engajamento
  - Trends
  - Testar dados reais

- [ ] **TODO 129:** Gráfico de eventos por categoria
  - Pie chart ou bar chart
  - Drill-down por categoria
  - Comparação temporal
  - Testar performance

---

## 🔧 TAREFAS TÉCNICAS ADICIONAIS

### Code Quality & Security
- [ ] **TODO 130:** Code review completo de segurança
  - XSS prevention em todos os outputs
  - SQL injection prevention
  - CSRF tokens em todos os forms
  - Sanitization/validation audit

- [ ] **TODO 131:** Performance optimization
  - Database query optimization
  - Caching strategy
  - Lazy loading de assets
  - Image optimization

- [ ] **TODO 132:** Accessibility audit
  - ARIA labels em todos os interativos
  - Keyboard navigation
  - Screen reader compatibility
  - Contrast ratios

### Documentation
- [ ] **TODO 133:** Documentação de API pública
  - Hooks disponíveis
  - Filters disponíveis
  - Actions disponíveis
  - Exemplos de uso

- [ ] **TODO 134:** Guia de desenvolvimento
  - Como adicionar novos CPTs
  - Como estender funcionalidades
  - Best practices
  - Troubleshooting

### Testing
- [ ] **TODO 135:** Testes de integração
  - Testar com temas populares
  - Testar com plugins populares
  - Testar em diferentes PHP versions
  - Testar em diferentes WP versions

- [ ] **TODO 136:** Testes de performance
  - Load testing com muitos eventos
  - Stress testing de AJAX
  - Memory usage profiling
  - Query optimization

### Deploy & Release
- [ ] **TODO 137:** Preparar para release
  - Version bump
  - Changelog update
  - README update
  - Assets optimization

- [ ] **TODO 138:** Backup & migration strategy
  - Export/import de eventos
  - Backup de configurações
  - Migration helper
  - Rollback strategy

---

## 🎯 PRIORIDADES IMEDIATAS (Top 10)

### HIGH PRIORITY (Must Have)
1. **TODO 97:** Criar estrutura de dados para estatísticas
2. **TODO 98:** Implementar line-graphs
3. **TODO 102-105:** Refatorar metaboxes com ShadCN
4. **TODO 130:** Security audit completo
5. **TODO 131:** Performance optimization

### MEDIUM PRIORITY (Should Have)
6. **TODO 91-92:** Motion.dev animations para list view
7. **TODO 93-95:** Shared layout animations
8. **TODO 96:** ShadCN layout improvements
9. **TODO 101:** Context menu Motion.dev style
10. **TODO 135:** Integration testing

### LOW PRIORITY (Nice to Have)
- TODOs 106-129: Apollo Social features (futuro)
- TODOs 132-134: Documentation
- TODOs 136-138: Advanced testing & deploy

---

## 📊 ESTATÍSTICAS

**Total de Tarefas:** 144  
**Concluídas:** 88 (61%)  
**Pendentes:** 56 (39%)  

**Por Fase:**
- FASE 1: 2 pendentes (89, 90)
- FASE 3: 2 pendentes (91, 92)
- FASE 4: 3 pendentes (93, 94, 95)
- FASE 5: 1 pendente (96)
- FASE 6: 2 pendentes (97, 98)
- FASE 7: 2 pendentes (99, 100)
- FASE 9: 1 pendente (101)
- FASE 10: 4 pendentes (102, 103, 104, 105)
- FASE 12: 24 pendentes (106-129)
- Técnicas: 9 pendentes (130-138)

---

## 🚀 PRÓXIMOS PASSOS

### Curto Prazo (Esta Semana)
1. ✅ Canvas mode implementation (DONE)
2. ✅ uni.css as universal CSS (DONE)
3. ✅ CodePen exact match (DONE)
4. ⏳ Security audit (TODO 130)
5. ⏳ Performance optimization (TODO 131)

### Médio Prazo (Próximas 2 Semanas)
1. Estatísticas database structure (TODO 97)
2. Line-graphs implementation (TODO 98, 100)
3. Metaboxes ShadCN refactor (TODO 102-105)
4. Motion.dev animations (TODO 91-95, 99, 101)

### Longo Prazo (Próximo Mês)
1. Apollo Social features (TODO 106-129)
2. Documentation (TODO 133-134)
3. Advanced testing (TODO 135-136)
4. Release preparation (TODO 137-138)

---

## 🎯 STATUS ATUAL

**Completadas:** 88/144 (61%)  
**Pendentes:** 56/144 (39%)  

**Último Update:** 15/01/2025  
**Progresso Recente:**
- ✅ Canvas mode para páginas independentes
- ✅ uni.css como CSS universal
- ✅ CodePen exact match implementation
- ✅ Theme assets removal
- ✅ HERO TAGS (category+tags+type, NO SOUNDS)
- ✅ MARQUEE (ONLY SOUNDS)
- ✅ Cupom APOLLO sempre visível
- ✅ mobile-container centrado

---

## 📝 NOTAS IMPORTANTES

### Canvas Mode (NEW - TODO implícito)
- ✅ **IMPLEMENTADO:** Remove ALL theme CSS/JS
- ✅ **IMPLEMENTADO:** Páginas canvas auto-criadas
- ✅ **IMPLEMENTADO:** Body classes para canvas mode
- ✅ **IMPLEMENTADO:** Whitelist de assets Apollo

### uni.css Universal
- ✅ **IMPLEMENTADO:** uni.css loads LAST (priority 999999)
- ✅ **IMPLEMENTADO:** Overrides ALL other CSS
- ✅ **IMPLEMENTADO:** Single source of truth

### Templates Alignment
- ✅ **IMPLEMENTADO:** Event card matches CodePen raxqVGR
- ✅ **IMPLEMENTADO:** Single event page matches CodePen raxKGqM
- ✅ **IMPLEMENTADO:** HERO TAGS: category+tags+type (NO SOUNDS)
- ✅ **IMPLEMENTADO:** MARQUEE: ONLY SOUNDS

---

**Arquivo:** `TODO-PENDENTES-88-144.md`  
**Criado:** 15/01/2025  
**Status:** READY FOR NEXT PHASE ✅

