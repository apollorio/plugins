# 🧪 Test Playbook — Workflows & Permissões

## 📋 1) Checklist Rápido (antes de testar)

### ✅ Configuração WordPress
- [ ] **Salvar Permalinks**: Settings → Permalinks → Save Changes
- [ ] **Verificar usuários de teste**: Criar roles subscriber, contributor, author, editor

### ✅ Configuração Apollo
- [ ] **config/canvas.php**: `force_canvas_on_plugin_routes = true`
- [ ] **config/badges.php**: toggles nos estados que você quer ver
- [ ] **config/ui.php**: mostrar "status badges" no topo das páginas Canvas

### ✅ Database Schema
```bash
wp apollo install
wp apollo setup-permissions
```

## 📊 2) Matriz de Testes (papel × conteúdo → estado esperado)

| Papel | Social Post/Discussão | Classificado | Evento | Comunidade/Núcleo |
|-------|----------------------|--------------|--------|-------------------|
| **Subscriber** | `published` direto | `published` direto | `pending_review` | `pending_review` |
| **Contributor** | `draft` | `draft` | `draft` | `pending_review` |
| **Author** | `pending_review` | `pending_review` | `published` | `pending_review` |
| **Editor** | `published` | `published` | `published` | `published` (pode aprovar) |
| **Administrator** | `published` | `published` | `published` | `published` (controle total) |

### 🔄 Fluxo de Rejeição
Quando moderador recusa Comunidade/Núcleo → status `rejected` + notificação:

```html
Apollo rejeitou sua inclusão...<br>
Motivo: <span class="apollo-reason">{reason_text}</span>
```

## 🎯 3) Cenários Essenciais (passo a passo)

### Teste A: Social Post (Subscriber → published)
```bash
# 1. Criar usuário de teste
wp user create subscriber_test subscriber@test.com --role=subscriber

# 2. Testar via CLI
wp apollo create post --user=subscriber_test --title="Post social teste"

# 3. Verificar resultado
wp apollo groups list --status=published
```

**Esperado**: badge "Publicado", sem fila de moderação

### Teste B: Classificado (Subscriber → published)
```bash
# 1. Criar season de teste
wp apollo seed --seasons

# 2. Criar classificado
wp apollo create ad --user=subscriber_test --title="Mesa de som" --season=verao-2026

# 3. Verificar
wp apollo groups list --status=published
```

**Esperado**: published imediato; se season inconsistente → erro 422

### Teste C: Evento (Author → published)
```bash
# 1. Criar author
wp user create author_test author@test.com --role=author

# 2. Criar evento
wp apollo create event --user=author_test --title="Workshop de música"

# 3. Verificar
wp post list --post_type=eva_event --post_status=publish
```

**Esperado**: published direto (subscriber teria `pending_review`)

### Teste D: Núcleo (qualquer papel → sempre pending_review)
```bash
# 1. Criar núcleo
wp apollo create group --user=subscriber_test --title="Núcleo teste" --group-type=nucleo

# 2. Verificar status
wp apollo groups list --status=pending_review

# 3. Aprovar como editor
wp user create editor_test editor@test.com --role=editor
wp apollo groups approve {ID}

# 4. Rejeitar com motivo
wp apollo groups reject {ID} --reason="Dados incompletos"
```

**Esperado**: transição `pending_review` → `published` ou `rejected`

### Teste E: Comunidade (idem Núcleo)
```bash
wp apollo create group --user=author_test --title="Comunidade teste" --group-type=comunidade
```

**Esperado**: sempre `pending_review` para quem cria; editor/admin aprovam

## 🖥️ 4) WP-CLI Commands

### Setup Inicial
```bash
# Instalar schema
wp apollo install

# Configurar permissões
wp apollo setup-permissions

# Criar dados de teste
wp apollo seed --users --seasons --content
```

### Criação de Conteúdo
```bash
# Posts sociais
wp apollo create post --user=subscriber_test --title="Post social sub"

# Classificados
wp apollo create ad --user=subscriber_test --title="Mesa de som"

# Eventos
wp apollo create event --user=author_test --title="Evento author ok"

# Grupos especiais
wp apollo create group --user=subscriber_test --type=nucleo --title="Núcleo teste"
```

### Moderação
```bash
# Aprovar conteúdo
wp apollo groups approve --id=123

# Rejeitar com motivo
wp apollo groups reject --id=123 --reason="Dados incompletos"

# Listar por status
wp apollo groups list --status=pending_review
wp apollo groups list --status=rejected
```

### Verificação
```bash
# Ver matriz de estados
wp apollo status-map

# Rodar testes automatizados
wp apollo test-matrix

# Ver estatísticas
wp apollo stats
```

## 🌐 5) REST API "Smoke Tests"

### Listar grupos por tipo
```bash
curl -s "https://seusite/apollo/v1/groups?type=nucleo"
```

### Criar grupo (deve cair em pending_review)
```bash
curl -s -X POST "https://seusite/apollo/v1/groups" \
  -H "X-WP-Nonce: <nonce>" \
  -d "title=Núcleo X&type=nucleo"
```

### Aprovar ou rejeitar
```bash
# Aprovar
curl -s -X POST "https://seusite/apollo/v1/groups/123/approve" \
  -H "X-WP-Nonce: <nonce>"

# Rejeitar
curl -s -X POST "https://seusite/apollo/v1/groups/123/reject" \
  -H "X-WP-Nonce: <nonce>" \
  -d "reason=Dados incompletos"
```

### Classificado em season válida (ok)
```bash
curl -s -X POST "https://seusite/apollo/v1/classifieds" \
  -H "X-WP-Nonce: <nonce>" \
  -d "title=Vendo Controladora&season_slug=verao-2026"
```

### Classificado season inválida (422)
```bash
curl -s -X POST "https://seusite/apollo/v1/classifieds" \
  -H "X-WP-Nonce: <nonce>" \
  -d "title=Erro Season&season_slug=carnaval-2030"
```

## 🎨 6) Integração Canvas (UI que precisa estar visível)

### Status Badges
- [ ] **Draft**: 📝 Rascunho (cinza)
- [ ] **Pending**: ⏳ Aguardando Aprovação (amarelo)
- [ ] **Published**: ✅ Publicado (verde)
- [ ] **Rejected**: ❌ Rejeitado (vermelho)

### Action Bar Contextual
- [ ] **Se pending_review e editor/admin**: botões "Aprovar" / "Rejeitar" (modal com "Motivo da rejeição")
- [ ] **Se rejected (autor)**: exibir mensagem padrão e botão "Editar e reenviar"
- [ ] **Command Center**: esconder botões que o papel não pode executar

### Toasts/Alerts Padronizados
- [ ] **Success**: "Conteúdo publicado com sucesso"
- [ ] **Error**: "Erro ao processar solicitação"
- [ ] **422**: "Dados inválidos: {detalhes}"
- [ ] **403**: "Você não tem permissão para esta ação"

### Auditoria
- [ ] **Ao aprovar/rejeitar**: "Aprovado por X às HH:MM (UTC-3)"
- [ ] **Histórico de transições**: log completo de mudanças de estado

## 🔒 7) Observabilidade & Segurança

### Audit Log
- [ ] **Criação**: user_id, content_type, initial_status, timestamp
- [ ] **Aprovação**: moderator_id, from_status, to_status, reason
- [ ] **Rejeição**: moderator_id, reason, timestamp
- [ ] **Publicação**: final_status, publish_timestamp

### Rate Limiting
- [ ] **Criar grupo/núcleo**: 1 por 5min por usuário
- [ ] **Submissões para moderação**: 3 por hora por usuário
- [ ] **Appeals (reenvio após rejeição)**: 1 por dia

### Sanitização
- [ ] **Reasons em rejeição**: permitir apenas `<br>` e `<span class="apollo-reason">`
- [ ] **Títulos**: strip_tags, wp_kses_post
- [ ] **Descriptions**: wp_kses_post com tags permitidas

### Analytics Mínimos
```javascript
// Eventos essenciais
plausible('group_request_submitted', {props: {type: 'nucleo'}});
plausible('group_approved', {props: {moderator_role: 'editor'}});
plausible('group_rejected', {props: {reason_category: 'incomplete_data'}});
plausible('post_published', {props: {user_role: 'subscriber'}});
plausible('ad_published', {props: {season: 'verao-2026'}});
plausible('event_published', {props: {user_role: 'author'}});
```

## ✅ 8) Go/No-Go de Release Interno

### Checklist de Validação
- [ ] **Matriz de estados**: Tabela de testes bateu com resultado real
- [ ] **Rejeição**: Motivo aparece corretamente na interface
- [ ] **Moderação**: Editor/Admin conseguem aprovar via Canvas e CLI
- [ ] **REST**: Retorna 403 (ACL) e 422 (validação) onde esperado
- [ ] **Analytics**: Dispara eventos nos pontos-chave
- [ ] **CSS/JS**: Nenhum vazamento do tema no Canvas
- [ ] **Performance**: Workflows não adicionam > 100ms nas requests
- [ ] **Database**: Indices funcionando, queries otimizadas

### Testes Automatizados
```bash
# Rodar suite completa
php test-playbook.php

# Deve retornar
# ✅ Passed: 25
# ❌ Failed: 0
# 🎉 All tests passed!
```

### Cenários de Stress
- [ ] **100 usuários simultâneos**: criando conteúdo
- [ ] **50 pending items**: na fila de moderação
- [ ] **Moderador processando**: 10 items por minuto
- [ ] **Database**: Sem deadlocks ou timeouts

## 🚀 9) Comandos de Teste Rápido

### Setup Completo (1 comando)
```bash
wp apollo install && wp apollo setup-permissions && wp apollo seed --users --seasons --content
```

### Verificação Rápida
```bash
wp apollo test-matrix && wp apollo status-map
```

### Cleanup
```bash
wp apollo reset --confirm
```

---

**Implementado em**: 4 de novembro de 2025  
**Versão**: Apollo Social v1.0.0  
**Status**: ✅ Pronto para testes de integração