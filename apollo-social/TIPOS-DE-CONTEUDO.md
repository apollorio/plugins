# Tipos de Conteúdo - Apollo Platform

## 📋 Resumo Executivo

Esta documentação lista todos os tipos de conteúdo (Custom Post Types) disponíveis na plataforma Apollo.

---

## 🎉 Apollo Events Manager

### 1. **event_listing** - Eventos

- **Slug público**: `/evento/`
- **Arquivo**: `/eventos/`
- **Capabilities**: Sistema completo de permissões customizadas
- **REST API**: ✅ `/wp-json/wp/v2/events`
- **Suporta**: title, editor, thumbnail, custom-fields, excerpt, author, revisions
- **Menu Admin**: Dashicons calendar-alt (posição 5)

**Uso**: Eventos principais da plataforma (festas, shows, encontros)

### 2. **event_dj** - DJs

- **Slug público**: `/dj/`
- **Arquivo**: ✅ (habilitado)
- **REST API**: ✅ `/wp-json/wp/v2/djs`
- **Suporta**: title, editor, thumbnail, custom-fields
- **Menu Admin**: Dashicons admin-users (posição 6)

**Uso**: Perfis de DJs que tocam nos eventos

### 3. **event_local** - Locais

- **Slug público**: `/local/`
- **Arquivo**: ✅ (habilitado)
- **REST API**: ✅ `/wp-json/wp/v2/locals`
- **Suporta**: title, editor, thumbnail, custom-fields
- **Menu Admin**: Dashicons location (posição 7)

**Uso**: Casas noturnas, clubes, espaços de eventos

---

## 👤 Apollo Social

### 4. **user_page** - Páginas de Usuário (Depoimentos)

- **Slug público**: `/id/{userID}` (custom rewrite)
- **Capabilities**: Edição restrita ao próprio usuário
- **Suporta**: title, thumbnail, comments (renomeado para "Depoimentos"), revisions
- **Features**:
   - **Features**:
   - **Features**:
         - Editor drag-and-drop com SortableJS
         - Sistema de widgets (About, Depoimentos, Image, Canvas Plano)
         - Templates: `user-page-view.php` (público), `user-page-editor.php` (edição)
         - Auto-criação ao registrar usuário

**Uso**: Perfil público personalizável de cada usuário com depoimentos

---

## 🚫 Tipos de Conteúdo NÃO Implementados

### Páginas Sociais Faltando

1. **Feed/Timeline de Rede Social**: ❌ NÃO EXISTE
   - Não há sistema de posts/feed social
   - Não há timeline de atividades
   - Não há sistema de "posts" como Facebook/Instagram

2. **Página Inicial para Usuário Logado**: ❌ NÃO CONFIGURADA
   - Não há redirect pós-login
   - Não há dashboard personalizado
   - Não há homepage específica para logados

3. **Grupos/Comunidades**: ⚠️ ESTRUTURA EXISTE mas não registrada
   - Pasta: `src/Modules/Groups/`
   - Templates: `single-season.php`
   - Serviços implementados mas CPT não registrado

4. **Classificados**: ⚠️ ESTRUTURA EXISTE mas não registrada
   - Pasta: `src/Modules/Classifieds/`
   - ServiceProvider existe mas não ativo

---

## 📝 Conteúdo Nativo do WordPress

- **post**: Posts de blog (padrão WP)
- **page**: Páginas estáticas (padrão WP)
- **attachment**: Mídia (padrão WP)

---

## 🔌 Integrações REST API

Todos os CPTs Apollo têm endpoints REST disponíveis:

- `/wp-json/wp/v2/events` - Eventos
- `/wp-json/wp/v2/djs` - DJs
- `/wp-json/wp/v2/locals` - Locais

User Pages não expõe REST API por padrão (não tem `show_in_rest`).

---

## ⚠️ Limitações Atuais

1. **Sem Feed Social**: Não há sistema de publicações sociais/timeline
2. **Sem Homepage Logada**: Login leva para `/wp-admin/` ou página anterior
3. **Grupos Não Ativos**: Código existe mas CPT não registrado
4. **Classificados Não Ativos**: Código existe mas CPT não registrado
5. **Sem Notificações**: Sistema de notificações não implementado
6. **Sem Mensagens Diretas**: Chat não possui sistema de mensagens privadas

---

## 🎯 O Que Funciona Hoje

✅ Sistema completo de Eventos (criar, editar, visualizar)
✅ Cadastro de DJs e Locais
✅ Páginas personalizáveis de usuário (/id/123)
✅ Sistema de depoimentos (comentários em user_page)
✅ Onboarding de usuários
✅ Sistema de badges e verificações
✅ Canvas/Builder (módulo de construção)
✅ Analytics e moderação
✅ Sistema de assinaturas/memberships

---

**Última atualização**: 10 de novembro de 2025
