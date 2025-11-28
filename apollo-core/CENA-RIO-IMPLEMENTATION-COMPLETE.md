# ✅ CENA-RIO Sistema Completo - Implementado

**Data**: 28 de novembro de 2025  
**Status**: ✅ **PRONTO PARA ATIVAÇÃO**  
**Versão**: Apollo Core 3.1.0

---

## 🎯 Objetivo Alcançado

Sistema completo de gestão de eventos comunitários onde:
- **CENA-ROLE** (membros) podem criar eventos como **draft/pending**
- **CENA-MOD** (moderadores) aprovam → **publish** → evento aparece no calendário público
- **Calendário visual** com mapa integrado (Leaflet)
- **Zero modificações** no apollo-events-manager

---

## 📋 Arquitetura Implementada

### 1. Data Model ✅

```
CPT: event_listing (do apollo-events-manager)
Metas CENA-RIO:
  - _apollo_source = 'cena-rio'
  - _apollo_cena_status = 'pending'|'approved'|'rejected'
  - _apollo_cena_submitted_by = user_id
  - _apollo_cena_submitted_at = timestamp
  - _apollo_cena_approved_by = user_id (quando aprovado)
  - _apollo_cena_approved_at = timestamp (quando aprovado)
```

### 2. Roles & Capabilities ✅

| Role | Capabilities | Pode Publicar? |
|------|--------------|----------------|
| **cena_role** | `edit_event_listing`, `edit_event_listings`, `delete_event_listing` | ❌ NÃO |
| **cena_moderator** | Todas de cena_role + `publish_event_listings`, `edit_others_event_listings`, `apollo_cena_moderate_events` | ✅ SIM |
| **apollo** | Recebe `apollo_cena_moderate_events` | ✅ SIM |
| **administrator** | Full access + `apollo_cena_moderate_events` | ✅ SIM |

---

## 📁 Arquivos Criados

### Core System Files

```
apollo-core/includes/
├── class-cena-rio-roles.php          # Gerenciamento de roles
├── class-cena-rio-submissions.php    # Sistema de submissão (draft only)
├── class-cena-rio-moderation.php     # Sistema de moderação (aprovar/rejeitar)
└── class-cena-rio-canvas.php         # Roteamento Canvas Mode
```

### Templates (Canvas Mode)

```
apollo-core/templates/
├── cena-rio-calendar.php             # Calendário visual com mapa
└── cena-rio-moderation.php           # Fila de moderação
```

### Assets

```
apollo-core/assets/js/
└── cena-rio-calendar.js               # JavaScript do calendário + integração REST API
```

### Configuration

```
apollo-core/apollo-core.php            # Atualizado com includes CENA-RIO
```

---

## 🚀 URLs e Rotas

| URL | Acesso | Função |
|-----|--------|--------|
| `/cena-rio/` | CENA-ROLE ou superior | Calendário visual com mapa |
| `/cena-rio/mod/` | CENA-MOD ou superior | Fila de moderação |

**Shortcodes disponíveis**:
- `[apollo_cena_submit_event]` - Formulário de submissão
- `[apollo_cena_moderation_queue]` - Fila de moderação

---

## 🔌 REST API Endpoints

Base URL: `/wp-json/apollo/v1/`

### Públicos

```
GET  /cena-rio/events?status=publish    # Buscar eventos publicados
```

### Autenticados (CENA-ROLE)

```
POST /cena-rio/submit                   # Enviar evento (cria como pending)
```

### Moderadores (CENA-MOD)

```
GET  /cena-rio/queue                    # Ver fila de moderação
POST /cena-rio/approve/{id}             # Aprovar evento (publish)
POST /cena-rio/reject/{id}              # Rejeitar evento (draft/trash)
```

**Autenticação**: Todos endpoints requerem `X-WP-Nonce` header.

---

## 📊 Fluxo Completo

```
┌─────────────────┐
│  CENA-ROLE      │
│  (membro)       │
└────────┬────────┘
         │
         │ 1. Preenche formulário /cena-rio
         │    Título, data, local, coordenadas
         │
         ▼
┌─────────────────────────────────┐
│  POST /cena-rio/submit          │
│  Cria event_listing:            │
│  - post_status = 'pending'      │
│  - _apollo_source = 'cena-rio'  │
└────────┬────────────────────────┘
         │
         │ 2. Evento entra na fila
         │
         ▼
┌─────────────────┐
│  CENA-MOD       │
│  (moderador)    │
└────────┬────────┘
         │
         │ 3. Acessa /cena-rio/mod
         │    Vê lista de pending events
         │
         ▼
┌─────────────────────────────────┐
│  Ação: Aprovar ou Rejeitar      │
│                                 │
│  APROVAR                        │
│  ├─ wp_update_post()            │
│  │  post_status = 'publish'     │
│  └─ _apollo_cena_status = 'approved' │
│                                 │
│  REJEITAR                       │
│  ├─ wp_update_post()            │
│  │  post_status = 'draft'       │
│  └─ _apollo_cena_status = 'rejected' │
└────────┬────────────────────────┘
         │
         │ 4. Se aprovado
         │
         ▼
┌─────────────────────────────────┐
│  Calendário Público             │
│  apollo-events-manager          │
│  Mostra: post_status='publish'  │
│  (SEM ALTERAÇÕES NO PLUGIN)     │
└─────────────────────────────────┘
```

---

## 🎨 Interface Visual

### Calendário (/cena-rio)

- ✅ **Layout responsivo** (desktop + mobile)
- ✅ **Calendário compacto** no topo esquerdo
- ✅ **Mapa Leaflet** no topo direito (mostra eventos com marcadores)
- ✅ **Lista de eventos** na parte inferior (todos ou filtrados por dia)
- ✅ **Sidebar esquerda** com navegação
- ✅ **Botão "Novo"** para adicionar eventos
- ✅ **Modal** para criar/editar eventos
- ✅ **Indicadores visuais**: eventos confirmados (verde) vs pending (laranja)

### Moderação (/cena-rio/mod)

- ✅ **Cards de eventos** com todas informações
- ✅ **Botões Aprovar/Rejeitar** com confirmação
- ✅ **Link para editar** no WP Admin
- ✅ **Status visual** (pending → cores laranja)
- ✅ **Info do submitter** (quem enviou, quando)
- ✅ **Proteção CSRF** (nonces em todos formulários)

---

## 🔒 Segurança Implementada

| Proteção | Status | Implementação |
|----------|--------|---------------|
| **CSRF Protection** | ✅ | Nonces em todos POST actions |
| **Permission Checks** | ✅ | `current_user_can()` em todos endpoints |
| **Input Sanitization** | ✅ | `sanitize_text_field()`, `wp_kses_post()` |
| **SQL Injection** | ✅ | `wp_insert_post()`, `update_post_meta()` |
| **XSS Protection** | ✅ | `esc_html()`, `esc_url()`, `esc_attr()` |
| **Rate Limiting** | ✅ | Sistema global apollo-core ativo |
| **Audit Logging** | ✅ | `apollo_mod_log_action()` para todas ações |

---

## 🧪 Como Testar

### 1. Ativar Plugin

```bash
wp plugin activate apollo-core
```

### 2. Flush Rewrite Rules

```bash
wp rewrite flush
```

### 3. Criar Usuário de Teste

```bash
# Criar cena_role
wp user create cenamembro cena@example.com --role=cena_role --user_pass=senha123

# Criar cena_moderator
wp user create cenamod mod@example.com --role=cena_moderator --user_pass=senha123
```

### 4. Testar Fluxo Completo

**Como CENA-ROLE:**
1. Login: `cenamembro` / `senha123`
2. Ir para: `https://seusite.com/cena-rio/`
3. Clicar em **"Novo"**
4. Preencher formulário:
   - Nome: "Test Event"
   - Data: Qualquer data futura
   - Local: "Copacabana"
   - Lat: `-22.9711`
   - Lng: `-43.1822`
5. Clicar **"Enviar para Moderação"**
6. Verificar mensagem de sucesso

**Como CENA-MOD:**
1. Logout e login: `cenamod` / `senha123`
2. Ir para: `https://seusite.com/cena-rio/mod/`
3. Verificar evento "Test Event" na fila
4. Clicar **"Aprovar"**
5. Verificar redirecionamento com mensagem de sucesso

**Verificar no Calendário Público:**
1. Ir para: `https://seusite.com/cena-rio/`
2. Evento deve aparecer no calendário
3. Marcador deve aparecer no mapa
4. Status deve ser "confirmado" (verde)

---

## 🔧 Configuração Avançada

### Adicionar Capability a Role Existente

```php
// Adicionar moderação a um editor existente
$editor = get_role( 'editor' );
if ( $editor ) {
    $editor->add_cap( 'apollo_cena_moderate_events' );
}
```

### Mudar Usuário para CENA-ROLE

```bash
wp user set-role USER_ID cena_role
```

### Ver Logs de Auditoria

```bash
wp apollo mod-log --action=cena_event_submitted --limit=20
wp apollo mod-log --action=cena_event_approved --limit=20
```

---

## 📝 Customizações Futuras

### Adicionar Campo Custom

**1. No formulário** (`class-cena-rio-submissions.php`):

```php
// No método render_submission_form(), adicionar:
<div>
  <label>Gênero Musical</label>
  <input name="music_genre" class="w-full..." />
</div>
```

**2. Salvar meta** (`create_cena_event()`):

```php
if ( ! empty( $event_data['music_genre'] ) ) {
    update_post_meta( $post_id, '_event_music_genre', $event_data['music_genre'] );
}
```

### Adicionar Status "Em Análise"

**1. Adicionar status intermediário**:

```php
// Ao criar evento, definir:
update_post_meta( $post_id, '_apollo_cena_status', 'under_review' );
```

**2. Adicionar botão "Marcar como Em Análise"** no moderation queue.

### Notificação por Email

**1. Ao aprovar evento** (`approve_event()`):

```php
// Após update_post_meta()
$author_email = get_the_author_meta( 'user_email', $post->post_author );
wp_mail(
    $author_email,
    'Seu evento foi aprovado!',
    sprintf( 'Parabéns! Seu evento "%s" foi aprovado.', $post->post_title )
);
```

---

## ⚠️ Notas Importantes

1. **Rewrite Rules**: Após ativação, executar `wp rewrite flush` ou visitar Settings → Permalinks no admin.

2. **Conflito com Temas**: As páginas `/cena-rio` usam Canvas Mode (sem CSS do tema). Se quiser usar o tema, remover linha `return APOLLO_CORE_PLUGIN_DIR . 'templates/...'` e criar templates no tema.

3. **Mapa Leaflet**: Requer conexão internet para carregar tiles do OpenStreetMap. Para offline, configurar servidor de tiles local.

4. **Dependências**: Sistema requer:
   - PHP 8.1+
   - WordPress 6.0+
   - apollo-events-manager ativo (para o CPT `event_listing`)

---

## ✅ Checklist Final

- [x] Roles criados (cena_role, cena_moderator)
- [x] Capabilities configurados corretamente
- [x] Sistema de submissão (draft only)
- [x] Sistema de moderação (aprovar/rejeitar)
- [x] REST API completo com autenticação
- [x] Calendário visual com mapa
- [x] Interface de moderação
- [x] Proteção CSRF completa
- [x] Audit logging implementado
- [x] Rate limiting ativo
- [x] Canvas Mode funcionando
- [x] Responsivo (mobile + desktop)
- [x] Documentação completa

---

## 🎉 Conclusão

Sistema **CENA-RIO** está **100% implementado e pronto para uso**.

**Principais benefícios**:
1. ✅ Membros podem sugerir eventos (sem publicar diretamente)
2. ✅ Moderadores têm controle total sobre o que é publicado
3. ✅ Interface visual moderna e intuitiva
4. ✅ Integração perfeita com apollo-events-manager (sem modificá-lo)
5. ✅ Segurança de produção (nonces, capabilities, audit log)
6. ✅ API REST para integrações futuras

**Próximos passos recomendados**:
1. Ativar plugin e flush rewrite rules
2. Criar usuários de teste
3. Testar fluxo completo
4. Treinar moderadores
5. Lançar para produção!

---

**Implementado por**: Apollo Core Team  
**Data**: 28 de novembro de 2025  
**Versão**: 3.1.0

