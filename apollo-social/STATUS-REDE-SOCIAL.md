# Status da Rede Social - Apollo Platform

## ❌ O QUE NÃO EXISTE

### 1. Feed/Timeline de Rede Social
**Status**: ❌ **NÃO IMPLEMENTADO**

Não temos:
- Sistema de posts/publicações sociais
- Feed de atividades tipo Facebook/Instagram
- Timeline de updates dos amigos
- Sistema de "curtir" e "compartilhar"
- Posts com texto/imagem/vídeo
- Feed algorítmico ou cronológico

**O que precisaria ser criado**:
```php
// CPT para posts sociais
register_post_type('social_post', [
    'public' => true,
    'has_archive' => false,
    'rewrite' => ['slug' => 'post'],
    'supports' => ['title', 'editor', 'thumbnail', 'comments']
]);

// Template: feed.php
// - Query posts dos usuários seguidos
// - Exibir com infinite scroll
// - Sistema de reações (like, love, etc)
```

### 2. Homepage para Usuário Logado
**Status**: ❌ **NÃO CONFIGURADA**

Atualmente quando usuário faz login:
- Vai para `/wp-admin/` (painel admin)
- OU volta para a página anterior
- Não há dashboard customizado
- Não há página inicial específica

**O que precisaria**:
```php
// Redirect após login
add_filter('login_redirect', function($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('subscriber', $user->roles)) {
            return home_url('/feed'); // Feed social
        }
    }
    return $redirect_to;
}, 10, 3);

// Template: page-feed.php
// - Exibir feed de posts
// - Widget de usuários sugeridos
// - Notificações recentes
// - Eventos próximos
```

### 3. Sistema de Notificações
**Status**: ❌ **NÃO IMPLEMENTADO**

Não temos:
- Notificações em tempo real
- Badges de contagem
- Centro de notificações
- Emails de notificação

### 4. Mensagens Diretas/Chat Privado
**Status**: ⚠️ **MÓDULO EXISTE MAS INCOMPLETO**

- Existe pasta `src/Modules/Chat/`
- Mas não há sistema de mensagens 1-a-1
- Não há inbox/outbox
- Não há notificações de mensagem

---

## ✅ O QUE EXISTE E FUNCIONA

### 1. Páginas de Usuário (/id/{userID})
- Perfil público personalizável
- Sistema de depoimentos (comentários)
- Editor drag-and-drop com widgets
- Auto-criação ao registrar

### 2. Sistema de Eventos
- CPT completo: `event_listing`
- Modal/lightbox para visualização
- Listagem e busca
- REST API disponível

### 3. Sistema de Onboarding
- Fluxo de cadastro
- Verificação de usuários
- Sessões de onboarding

### 4. Sistema de Badges
- Badges de conquistas
- Integração com BadgeOS
- Verificações de perfil

### 5. Módulo Builder/Canvas
- Editor de páginas customizado
- Sistema de widgets

---

## 🔨 RECOMENDAÇÕES DE IMPLEMENTAÇÃO

### Prioridade Alta

1. **Criar Homepage Logada**
   ```php
   // Arquivo: page-home-logged.php
   // - Welcome message personalizado
   // - Últimos eventos
   // - Link para perfil (/id/{userID})
   // - Atalhos rápidos
   ```

2. **Redirect Pós-Login**
   ```php
   add_filter('login_redirect', function($redirect_to, $request, $user) {
       return home_url('/inicio'); // Página de boas-vindas
   }, 10, 3);
   ```

### Prioridade Média

3. **Feed Simples (MVP)**
   - Listar últimos eventos criados
   - Listar novos usuários cadastrados
   - Atividades recentes (sem posts próprios)

4. **Sistema Básico de Notificações**
   - Notificar quando recebe depoimento
   - Notificar quando evento que participa é atualizado

### Prioridade Baixa

5. **Posts Sociais Completos**
   - CPT `social_post`
   - Sistema de curtidas
   - Sistema de comentários
   - Feed algorítmico

---

## 🎯 SOLUÇÃO TEMPORÁRIA

### Enquanto não há feed social:

**Opção 1: Homepage Simples**
```php
// Criar página "Início" no WordPress
// Template: page-inicio.php
<?php if (is_user_logged_in()): ?>
    <h1>Bem-vindo, <?php echo wp_get_current_user()->display_name; ?>!</h1>
    
    <div class="home-grid">
        <a href="<?php echo home_url('/eventos'); ?>">Ver Eventos</a>
        <a href="<?php echo home_url('/id/' . get_current_user_id()); ?>">Meu Perfil</a>
        <a href="<?php echo home_url('/djs'); ?>">Conhecer DJs</a>
    </div>
<?php else: ?>
    <!-- Landing page para visitantes -->
<?php endif; ?>
```

**Opção 2: Redirect para Perfil**
```php
// Após login, levar direto para página pessoal
add_filter('login_redirect', function($redirect_to, $request, $user) {
    return home_url('/id/' . $user->ID);
}, 10, 3);
```

**Opção 3: Redirect para Eventos**
```php
// Após login, mostrar eventos disponíveis
add_filter('login_redirect', function($redirect_to, $request, $user) {
    return home_url('/eventos');
}, 10, 3);
```

---

## 📊 COMPARAÇÃO: O QUE TEMOS vs O QUE FALTA

| Feature | Status | Observação |
|---------|--------|------------|
| Perfis de Usuário | ✅ Completo | `/id/{userID}` funcionando |
| Feed Social | ❌ Não existe | Precisa criar do zero |
| Posts Sociais | ❌ Não existe | Sem CPT para posts |
| Homepage Logada | ❌ Não existe | Usa wp-admin |
| Notificações | ❌ Não existe | Sem sistema |
| Mensagens Diretas | ⚠️ Incompleto | Módulo existe mas não funcional |
| Eventos | ✅ Completo | CPT + modal + API |
| DJs e Locais | ✅ Completo | CPTs funcionais |
| Depoimentos | ✅ Completo | Usando comments |
| Badges | ✅ Completo | BadgeOS integrado |
| Onboarding | ✅ Completo | Fluxo funcional |

---

**Última atualização**: 10 de novembro de 2025

**Status Geral**: Sistema focado em EVENTOS, não em rede social tradicional. Para transformar em rede social completa, precisa implementar feed de posts e homepage logada.
