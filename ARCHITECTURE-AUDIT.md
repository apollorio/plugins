# 🔍 Arquitetura Apollo - Auditoria de Duplicados e Plano de Migração

**Data:** <?= date('d/m/Y') ?>
**Objetivo:** Eliminar duplicação de código e conflitos de componentes gerados por VSCode AI

---

## 📊 RESUMO EXECUTIVO

### Problemas Identificados

1. **Duplicação de Features Across Plugins**
   - Sistema de notificações implementado em 3 plugins diferentes
   - Sistema de email fragmentado entre apollo-core e apollo-social
   - User pages/profile com múltiplos caminhos de URL e classes
   - Chat module existe mas marcado como "não funcional"

2. **Conflitos de Namespaces e Classes**
   - Classes deprecated em apollo-core ainda referenciadas
   - Múltiplos managers para mesma funcionalidade
   - Falta de "single source of truth" para features

3. **Estrutura Atual vs Ideal**
   - **Atual:** Features espalhadas em apollo-core, apollo-social, apollo-events-manager
   - **Ideal:** Plugins separados por feature com apollo-core apenas para shared utilities

---

## 🗺️ MAPA DE FEATURES DUPLICADAS

### 1. NOTIFICATIONS (Sistema de Notificações)

#### Arquivos Distribuídos

| Plugin                    | Arquivo                                                               | Linhas | Status    | Descrição                       |
| ------------------------- | --------------------------------------------------------------------- | ------ | --------- | ------------------------------- |
| **apollo-core**           | `includes/communication/notifications/class-notification-manager.php` | ~200   | ✅ Active | Frontend notification manager   |
| **apollo-core**           | `includes/class-apollo-native-push.php`                               | ~150   | ✅ Active | Push notifications (browser)    |
| **apollo-core**           | `admin/class-apollo-unified-control-panel.php`                        | 1432+  | ✅ Active | Admin UI para notificações      |
| **apollo-social**         | `src/Email/EventNotificationHooks.php`                                | 631    | ✅ Active | Hooks de notificação de eventos |
| **apollo-social**         | `user-pages/tabs/class-user-email-tab.php`                            | 610    | ✅ Active | User preferences UI             |
| **apollo-social**         | `assets/js/quill-editor.js`                                           | 157+   | ✅ Active | Toast notifications (frontend)  |
| **apollo-events-manager** | `includes/modules/notifications/class-notifications-module.php`       | 823    | ✅ Active | Notifications system (events)   |
| **apollo-events-manager** | `templates/notifications-list.php`                                    | ~100   | ✅ Active | UI template                     |
| **apollo-events-manager** | `assets/css/notifications.css`                                        | ~50    | ✅ Active | Styles                          |
| **apollo-events-manager** | `assets/js/notifications.js`                                          | ~100   | ✅ Active | JS frontend                     |

#### Tabelas de Banco de Dados

- `apollo_notifications` (class-notification-manager.php)
- `apollo_notification_preferences` (class-notification-manager.php)
- `apollo_push_subscriptions` (class-apollo-native-push.php)

#### User Meta Keys (Duplicados)

- `_apollo_notification_prefs` (apollo-events-manager)
- `_apollo_event_subscriptions` (apollo-events-manager)
- `_apollo_email_prefs` (apollo-social)
- `notify_events`, `notify_messages`, `notify_docs` (legacy)

#### 🎯 **Proposta:** Plugin `apollo-notifications`

**Responsabilidades:**

- Frontend notifications (bell icon, dropdown list)
- Push notifications (browser native)
- User preferences (per notification type)
- Toast/alert system (JS)
- Admin UI para gestão de notificações

**Dependências:**

- `apollo-core` (capabilities, utilities)
- Hooks de outros plugins (events, chat, etc.)

---

### 2. EMAIL (Sistema de Email)

#### Arquivos Distribuídos

| Plugin                    | Arquivo                                                | Linhas | Status           | Descrição                   |
| ------------------------- | ------------------------------------------------------ | ------ | ---------------- | --------------------------- |
| **apollo-core**           | `includes/class-apollo-email-integration.php`          | 1022   | ✅ Active        | Email hub integration       |
| **apollo-core**           | `includes/class-apollo-email-service.php`              | 386    | ⚠️ Deprecated    | Email sending service (OLD) |
| **apollo-core**           | `includes/class-apollo-email-templates-cpt.php`        | 294    | ✅ Active        | Email templates CPT         |
| **apollo-core**           | `includes/class-apollo-email-admin-ui.php`             | 490    | ✅ Active        | Admin UI                    |
| **apollo-core**           | `includes/class-email-security-log.php`                | 706    | ✅ Active        | Security logging            |
| **apollo-core**           | `includes/communication/email/class-email-manager.php` | 544    | ⚠️ Deprecated    | Email queue (OLD)           |
| **apollo-social**         | `src/Email/UnifiedEmailService.php`                    | 582    | ✅ **CANONICAL** | **CURRENT EMAIL API**       |
| **apollo-social**         | `src/Email/EventNotificationHooks.php`                 | 631    | ✅ Active        | Event email hooks           |
| **apollo-social**         | `src/Modules/Email/EmailQueueRepository.php`           | 131    | ✅ Active        | Queue DB operations         |
| **apollo-social**         | `src/Admin/EmailHubAdmin.php`                          | 2116   | ✅ Active        | Email hub admin panel       |
| **apollo-social**         | `src/Admin/EmailNotificationsAdmin.php`                | 518    | ✅ Active        | Notifications admin         |
| **apollo-social**         | `src/Security/EmailSecurityLog.php`                    | ~50    | ✅ Active        | Security wrapper            |
| **apollo-social**         | `user-pages/tabs/class-user-email-tab.php`             | 610    | ✅ Active        | User email prefs UI         |
| **apollo-events-manager** | `includes/class-events-email-integration.php`          | 192    | ✅ Active        | Events email bridge         |

#### Tabelas de Banco de Dados

- `apollo_email_queue` (usado por 2 classes diferentes)
- `apollo_email_log`
- `apollo_email_security_log`

#### 🎯 **Proposta:** Plugin `apollo-email`

**Responsabilidades:**

- Unified Email Service (canonical API)
- Email queue + queue processing
- Email templates (CPT + rendering)
- SMTP configuration
- Security logging
- Admin UI (email hub)
- User preferences UI

**Migração de Código:**

- `apollo-social/src/Email/UnifiedEmailService.php` → `apollo-email/src/UnifiedEmailService.php`
- `apollo-social/src/Modules/Email/*` → `apollo-email/src/Queue/`
- `apollo-core/includes/class-apollo-email-templates-cpt.php` → `apollo-email/src/Templates/`
- Deprecate classes antigas em apollo-core

---

### 3. CHAT (Sistema de Mensagens)

#### Arquivos Distribuídos

| Plugin                    | Arquivo                                                                                         | Linhas | Status    | Descrição                                |
| ------------------------- | ----------------------------------------------------------------------------------------------- | ------ | --------- | ---------------------------------------- |
| **apollo-social**         | `src/Modules/Chat/ChatModule.php`                                                               | 1259   | ⚠️ Exists | Chat system (marcado como não funcional) |
| **apollo-social**         | REST routes: `/chat/conversations`, `/chat/messages/{id}`, `/chat/poll`, `/chat/context-thread` | -      | ⚠️ Exists | REST API endpoints                       |
| **apollo-events-manager** | `modules/rest-api/includes/aprio-rest-matchmaking-user-messages.php`                            | -      | ✅ Active | Legacy matchmaking messages API          |

#### Tabelas de Banco de Dados

- `apollo_chat_conversations`
- `apollo_chat_messages`
- `apollo_chat_participants`

#### 🎯 **Proposta:** Plugin `apollo-chat`

**Responsabilidades:**

- Direct messages (DMs)
- Group conversations (núcleos, comunidades)
- Message history
- Real-time polling
- Unread badge counts
- Integração com Classifieds/Suppliers

**Status:** Módulo existe mas não está funcional. Precisa ser ativado e testado.

**Prioridade:** BAIXA (implementar depois de notifications e email)

---

### 4. USER PAGES / PROFILE (Páginas de Usuário)

#### Arquivos Distribuídos

| Plugin            | Arquivo                                          | Linhas | Status        | Descrição                                |
| ----------------- | ------------------------------------------------ | ------ | ------------- | ---------------------------------------- |
| **apollo-social** | `user-pages/class-user-page-cpt.php`             | ~60    | ⚠️ Deprecated | CPT `user_page` (LEGACY)                 |
| **apollo-social** | `user-pages/class-user-page-autocreate.php`      | ~35    | ✅ Active     | Auto-create on registration              |
| **apollo-social** | `user-pages/class-user-page-rewrite.php`         | ~22    | ✅ Active     | Rewrites: `/id/{userID}`, `/meu-perfil/` |
| **apollo-social** | `user-pages/class-user-page-template-loader.php` | ~45    | ✅ Active     | Template loader                          |
| **apollo-social** | `user-pages/class-user-page-editor-ajax.php`     | ~55    | ✅ Active     | AJAX handlers                            |
| **apollo-social** | `user-pages/class-user-page-widgets.php`         | ~145   | ✅ Active     | Widget definitions                       |
| **apollo-social** | `user-pages/class-user-page-seo.php`             | ~40    | ✅ Active     | SEO meta tags                            |
| **apollo-social** | `user-pages/class-user-page-permissions.php`     | ~30    | ✅ Active     | Permission checks                        |
| **apollo-social** | `user-pages/tabs/class-user-privacy-tab.php`     | ~110   | ✅ Active     | Privacy tab                              |
| **apollo-social** | `user-pages/tabs/class-user-language-tab.php`    | ~280   | ✅ Active     | Language tab                             |
| **apollo-social** | `user-pages/tabs/class-user-email-tab.php`       | ~610   | ✅ Active     | Email prefs tab (DUPLICATE)              |
| **apollo-social** | `src/Modules/UserPages/*`                        | -      | ✅ Active     | Modern UserPages module                  |
| **apollo-social** | `src/Modules/Profile/*`                          | -      | ✅ Active     | Profile module                           |

#### Problemas Identificados

- **Múltiplos URL patterns:** `/id/{userID}`, `/id/{username}`, `/meu-perfil/`, `/hub/{username}`
- **Duas implementações:** CPT `user_page` (legacy) + `Modules/UserPages` (modern)
- **Confusão:** User Profile vs HUB Page (Linktree-style)

#### 🎯 **Proposta:** Plugin `apollo-profile`

**Responsabilidades:**

- User profile pages (BuddyPress-style): `/id/{username}`
- HUB pages (Linktree-style, admin-approved): `/hub/{username}`
- Profile editor (cover, avatar, bio, social links)
- Privacy settings
- SEO optimization
- User widgets (canvas, playlists)

**Arquitetura Proposta:**

| Sistema          | URL               | Descrição                                   | Acesso                |
| ---------------- | ----------------- | ------------------------------------------- | --------------------- |
| **User Profile** | `/id/{username}`  | Perfil social completo (cover, tabs, posts) | Todos os usuários     |
| **HUB Page**     | `/hub/{username}` | Página Linktree com links customizáveis     | Solicitado + aprovado |

---

## 🎯 PROPOSTA DE ARQUITETURA FINAL

```
wp-content/plugins/
├── apollo-core/                 # Shared utilities ONLY
│   ├── includes/
│   │   ├── class-apollo-capabilities.php
│   │   ├── class-apollo-router.php
│   │   ├── class-apollo-feature-flags.php
│   │   └── traits/
│   ├── src/
│   │   ├── CLI/
│   │   └── Admin/              # Go-No-Go checklist, unified control panel
│   └── compatibility/          # Re-exports para backward compatibility
│
├── apollo-notifications/        # 🆕 Notifications plugin
│   ├── src/
│   │   ├── NotificationManager.php
│   │   ├── PushService.php
│   │   ├── ToastService.php
│   │   ├── Preferences/
│   │   └── Admin/
│   ├── assets/
│   │   ├── js/notifications.js
│   │   └── css/notifications.css
│   ├── templates/
│   │   └── notification-list.php
│   └── index.php
│
├── apollo-email/                # 🆕 Email plugin
│   ├── src/
│   │   ├── UnifiedEmailService.php
│   │   ├── Queue/
│   │   │   ├── QueueManager.php
│   │   │   └── QueueRepository.php
│   │   ├── Templates/
│   │   │   ├── TemplateEngine.php
│   │   │   └── TemplateCPT.php
│   │   ├── Security/
│   │   │   └── EmailSecurityLog.php
│   │   └── Admin/
│   │       ├── EmailHubAdmin.php
│   │       └── NotificationsAdmin.php
│   └── index.php
│
├── apollo-chat/                 # 🆕 Chat plugin (low priority)
│   ├── src/
│   │   ├── ChatModule.php
│   │   ├── ConversationManager.php
│   │   ├── MessageRepository.php
│   │   └── Polling/
│   └── index.php
│
├── apollo-profile/              # 🆕 Profile/User Pages plugin
│   ├── src/
│   │   ├── ProfileManager.php
│   │   ├── HubManager.php
│   │   ├── Rewrite/
│   │   ├── Editor/
│   │   ├── Tabs/
│   │   │   ├── PrivacyTab.php
│   │   │   ├── LanguageTab.php
│   │   │   └── SocialTab.php
│   │   └── Widgets/
│   └── index.php
│
├── apollo-social/               # Social features (feed, connections, etc.)
│   ├── compatibility/          # Re-exports apontando para novos plugins
│   └── src/
│       ├── Feed/
│       ├── Connections/
│       └── Groups/
│
├── apollo-events-manager/       # Events ONLY
│   ├── compatibility/
│   └── includes/
│       └── events/
│
└── apollo-rio/                  # Tema principal
    └── (sem mudanças)
```

---

## 📋 PLANO DE MIGRAÇÃO (Incremental)

### Princípios

1. **Nunca quebrar a aplicação:** Cada step deve deixar o app funcional
2. **Backward compatibility:** Classes antigas devem re-exportar para novas
3. **Feature-first migration:** Migrar features completas, não arquivos isolados
4. **Verificação contínua:** Dev/UI route para testar cada feature isolada

---

### FASE 1: Email Service (MENOR RISCO) ⭐ **COMEÇAR AQUI**

**Por que começar com Email?**

- ✅ Já tem canonical implementation (`UnifiedEmailService`)
- ✅ Poucas dependências externas
- ✅ Fácil de testar (enviar email de teste)
- ✅ Não impacta frontend/UX diretamente

#### Step 1.1: Criar estrutura do plugin

```bash
wp-content/plugins/apollo-email/
├── apollo-email.php            # Main plugin file
├── composer.json
├── src/
│   ├── UnifiedEmailService.php
│   ├── Queue/
│   ├── Templates/
│   ├── Security/
│   └── Admin/
├── assets/
│   ├── css/
│   └── js/
├── templates/
├── tests/
│   └── EmailServiceTest.php
└── index.php                   # Public exports
```

**Comando:**

```bash
cd wp-content/plugins
mkdir -p apollo-email/{src/{Queue,Templates,Security,Admin},assets/{css,js},templates,tests}
```

#### Step 1.2: Mover código

1. **Copiar** (não mover ainda) arquivos:
   - `apollo-social/src/Email/UnifiedEmailService.php` → `apollo-email/src/UnifiedEmailService.php`
   - `apollo-social/src/Modules/Email/*` → `apollo-email/src/Queue/`
   - `apollo-core/includes/class-apollo-email-templates-cpt.php` → `apollo-email/src/Templates/TemplateCPT.php`

2. **Atualizar namespaces:**

   ```php
   // Before:
   namespace Apollo\Email;

   // After:
   namespace ApolloEmail;
   ```

#### Step 1.3: Criar compatibility layer

**`apollo-social/compatibility/email.php`:**

```php
<?php
/**
 * Backward compatibility layer for Email Service
 * Re-exports to apollo-email plugin
 */

// Alias old class to new location
if ( class_exists( 'ApolloEmail\UnifiedEmailService' ) ) {
    class_alias( 'ApolloEmail\UnifiedEmailService', 'Apollo\Email\UnifiedEmailService' );
}
```

**`apollo-social/src/Email/UnifiedEmailService.php` (deprecated wrapper):**

```php
<?php
/**
 * @deprecated 4.0.0 Use ApolloEmail\UnifiedEmailService instead
 */
namespace Apollo\Email;

if ( ! class_exists( 'ApolloEmail\UnifiedEmailService' ) ) {
    _doing_it_wrong(
        __CLASS__,
        'Please activate apollo-email plugin',
        '4.0.0'
    );
    return;
}

class_alias( 'ApolloEmail\UnifiedEmailService', __CLASS__ );
```

#### Step 1.4: Criar dev/ui route

**`apollo-email/dev/test-email.php`:**

```php
<?php
/**
 * Dev UI: Test Email Service
 * URL: /dev/email
 */

use ApolloEmail\UnifiedEmailService;

$email_service = new UnifiedEmailService();

// Mock data
$test_recipient = 'admin@apollo.rio.br';
$test_subject = 'Apollo Email Test';
$test_body = '<h1>Email Service Working!</h1><p>Sent at ' . current_time( 'mysql' ) . '</p>';

// Send test email
$result = $email_service->send( $test_recipient, $test_subject, $test_body );

// Display result
?>
<div style="padding: 20px; font-family: sans-serif;">
    <h1>Apollo Email Service - Dev UI</h1>

    <h2>Test Email Send</h2>
    <pre><?php var_dump( $result ); ?></pre>

    <h2>Queue Status</h2>
    <?php
    global $wpdb;
    $queue_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}apollo_email_queue WHERE status = 'pending'" );
    echo "<p>Pending emails in queue: <strong>$queue_count</strong></p>";
    ?>

    <form method="post">
        <button type="submit" name="send_test">Send Test Email</button>
    </form>
</div>
```

**Register route:**

```php
// apollo-email/apollo-email.php
add_action( 'init', function() {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        add_rewrite_rule( '^dev/email/?$', 'index.php?apollo_dev=email', 'top' );
    }
});
```

#### Step 1.5: Validar

```bash
# 1. Run tests
cd wp-content/plugins/apollo-email
composer install
vendor/bin/phpunit

# 2. Check coding standards
vendor/bin/phpcs --standard=WordPress src/

# 3. Static analysis
vendor/bin/phpstan analyze src/

# 4. Manual test
# Visit: http://local.apollo.rio.br/dev/email
```

#### Step 1.6: Deploy

1. Ativar plugin `apollo-email`
2. Verificar logs de deprecated warnings
3. Monitorar email queue
4. Após 1 semana sem erros → remover classes deprecated de apollo-social/apollo-core

---

### FASE 2: Notifications Service (RISCO MÉDIO)

**Ordem de implementação:**

1. Criar `apollo-notifications/src/NotificationManager.php`
2. Mover `apollo-core/includes/communication/notifications/class-notification-manager.php`
3. Mover `apollo-core/includes/class-apollo-native-push.php`
4. Consolidar classes de `apollo-events-manager/includes/modules/notifications/`
5. Criar compatibility layer
6. Dev UI: `/dev/notifications` com lista mockada + toast test

**Risco:** Frontend depende de JS/CSS. Testar bem antes de deploy.

---

### FASE 3: Profile/User Pages (RISCO ALTO)

**Por que fase 3?**

- ⚠️ Múltiplos URL patterns
- ⚠️ Duas implementações (CPT legacy + Modules)
- ⚠️ Impacta SEO e navigation

**Estratégia:**

1. Unificar URL patterns ANTES de migrar código:
   - `/id/{username}` → User Profile
   - `/hub/{username}` → HUB Page (Linktree)
2. Deprecate CPT `user_page`, migrar para `Modules/UserPages`
3. Mover tudo para `apollo-profile`
4. Criar compatibility rewrites

---

### FASE 4: Chat (BAIXA PRIORIDADE)

**Status:** Módulo existe mas não funcional. Implementar **depois** de estabilizar Notifications + Email + Profile.

---

## ✅ CHECKLIST DE VALIDAÇÃO (Por Feature)

Para cada plugin migrado:

- [ ] Plugin estrutura criada (`composer.json`, `index.php`, `/src`, `/tests`)
- [ ] Código movido + namespaces atualizados
- [ ] Compatibility layer implementada (class_alias ou re-exports)
- [ ] Dev/UI route funcional (`/dev/{feature}`)
- [ ] Testes unitários passando (`phpunit`)
- [ ] Coding standards OK (`phpcs`)
- [ ] Static analysis OK (`phpstan`)
- [ ] Manual testing OK (admin UI + frontend)
- [ ] No deprecated warnings nos logs
- [ ] Database migrations rodadas (se necessário)
- [ ] README.md criado com instruções
- [ ] Plugin ativado em produção
- [ ] Monitoramento de 1 semana sem erros
- [ ] Classes deprecated removidas dos plugins antigos

---

## 🚨 RISCOS E MITIGAÇÕES

| Risco                                 | Probabilidade | Impacto | Mitigação                                     |
| ------------------------------------- | ------------- | ------- | --------------------------------------------- |
| Quebrar imports existentes            | Alta          | Alto    | Compatibility layer com class_alias           |
| Quebrar URL rewrites (user pages)     | Média         | Alto    | Testar rewrites em staging antes              |
| Performance degradation (email queue) | Baixa         | Médio   | Manter índices de banco, monitorar query time |
| Conflito de dependências (composer)   | Média         | Médio   | Usar `conflict` em composer.json              |
| Perda de dados (migrations)           | Baixa         | Crítico | Backup antes, migrations testadas             |

---

## 📊 MÉTRICAS DE SUCESSO

- **Redução de duplicação:** De ~15 classes duplicadas para 0
- **Linhas de código:** Reduzir ~20% via consolidação
- **Tempo de onboarding:** Novo dev entende arquitetura em <1 dia
- **Bugs de conflito:** Zero bugs de namespace collision
- **Performance:** Sem degradação (manter query time < 100ms)

---

## 🎯 PRÓXIMOS PASSOS IMEDIATOS

1. ✅ **APROVAÇÃO:** Revisar este documento e aprovar plano
2. 🚀 **FASE 1 - Email:** Criar estrutura `apollo-email/` e migrar `UnifiedEmailService`
3. 🧪 **TESTAR:** Dev UI `/dev/email` + phpunit
4. 📦 **DEPLOY:** Ativar plugin e monitorar por 1 semana
5. 🔄 **REPEAT:** Seguir para FASE 2 (Notifications)

---

**Questões?** Abrir issue ou discutir em reunião de arquitetura.
