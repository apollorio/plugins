# Lista-Rio - Arquitetura e Endpoints Implementados

> Versão: 2.1.0 | Data: 2025-01-27

## 📌 Visão Geral

O Lista-Rio é a plataforma de guest list e relacionamento para produtores, promoters, DJs e clubbers. Esta documentação descreve as mudanças de arquitetura implementadas no ecossistema Apollo.

---

## 🏗️ Módulos Implementados

### 1. NucleosController (Grupos Privados)

**Localização:** `src/Infrastructure/Http/Controllers/NucleosController.php`

**Conceito:** Núcleos são times de produção privados, visíveis apenas para staff/promoters (não para clubbers/subscribers).

**Lógica de Acesso:**
- ❌ Clubbers e Subscribers NÃO podem ver núcleos
- ✅ Apenas staff, promoters, DJs, venue owners e admins têm acesso
- 🔒 Join é apenas por convite + aprovação de admin

**Endpoints:**

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/apollo/v1/nucleos` | Listar núcleos (apenas membros ou admin veem) |
| POST | `/apollo/v1/nucleos` | Criar núcleo (fica em DRAFT até aprovação) |
| POST | `/apollo/v1/nucleos/{id}/join` | Aceitar convite e solicitar entrada |
| POST | `/apollo/v1/nucleos/{id}/invite` | Convidar usuário para núcleo |
| POST | `/apollo/v1/nucleos/{id}/aprovar-join` | Admin aprova entrada de membro |

**Tabela de Convites:** `wp_apollo_nucleo_invites`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | BIGINT | PK auto-increment |
| nucleo_id | BIGINT | FK para grupo |
| inviter_user_id | BIGINT | Quem convidou |
| invitee_user_id | BIGINT | ID do convidado (opcional) |
| invitee_email | VARCHAR(255) | Email do convidado (opcional) |
| token | VARCHAR(64) | Token único de convite |
| status | ENUM | pending, used, expired, cancelled |
| created_at | DATETIME | Data de criação |
| expires_at | DATETIME | Expiração (7 dias) |
| used_at | DATETIME | Quando foi usado |

---

### 2. BolhaController (Friend Circles)

**Localização:** `src/Infrastructure/Http/Controllers/BolhaController.php`

**Conceito:** "Bolha" = círculo social íntimo. Gerencia relacionamentos de amizade entre usuários.

**Endpoints:**

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/apollo/v1/bolha/pedir` | Enviar pedido de amizade |
| POST | `/apollo/v1/bolha/aceitar` | Aceitar pedido de amizade |
| POST | `/apollo/v1/bolha/rejeitar` | Rejeitar pedido de amizade |
| POST | `/apollo/v1/bolha/remover` | Remover amigo da bolha |
| GET | `/apollo/v1/bolha/listar` | Listar amigos da minha bolha |
| GET | `/apollo/v1/bolha/pedidos` | Ver pedidos pendentes |
| GET | `/apollo/v1/bolha/status/{id}` | Status da amizade com usuário |
| POST | `/apollo/v1/bolha/cancelar` | Cancelar pedido enviado |

**Status de Relacionamento:**
- `none` - Sem relacionamento
- `pending_sent` - Eu enviei pedido
- `pending_received` - Recebi pedido
- `friends` - Somos amigos
- `blocked` - Bloqueado

**Tabela:** `wp_apollo_bolha`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | BIGINT | PK auto-increment |
| user_id | BIGINT | Quem enviou o pedido |
| friend_id | BIGINT | Quem recebe o pedido |
| status | ENUM | pending, accepted, rejected, blocked |
| created_at | DATETIME | Data do pedido |
| accepted_at | DATETIME | Data da aceitação |

---

## 🔧 Mudanças no Repository

### GroupsRepository

**Localização:** `src/Domain/Groups/Repositories/GroupsRepository.php`

**Novos Métodos Adicionados:**

```php
/**
 * Get member role in a group
 * @return string|null 'owner', 'admin', 'moderator', 'member', 'pending'
 */
public function getMemberRole(int $group_id, int $user_id): ?string

/**
 * Update member role in a group
 */
public function updateMemberRole(int $group_id, int $user_id, string $new_role): bool
```

---

## 📚 Nomenclatura (Português)

| Termo Técnico | Termo em Português | Descrição |
|---------------|-------------------|-----------|
| Event | EVA | Evento na plataforma |
| Membership | Membro | Assinatura/associação |
| Private Group | Núcleo | Time de produção privado |
| Public Group | Comuna | Comunidade pública |
| Friend Circle | Bolha | Círculo de amizade |
| Classified | Anúncio | Anúncio classificado |

---

## 🔐 Regras de Acesso

### Núcleo (Privado)
```
BLOQUEADOS: subscriber, clubber
PERMITIDOS: administrator, editor, promoter, staff, dj, venue_owner
VISIBILIDADE: Apenas membros e admins globais
JOIN: Convite obrigatório + Aprovação de admin do núcleo
CRIAÇÃO: Qualquer membro permitido, mas fica DRAFT até aprovação
```

### Comuna (Público)
```
BLOQUEADOS: nenhum
PERMITIDOS: todos logados
VISIBILIDADE: Lista pública
JOIN: Aberto ou requer aprovação (configurável)
CRIAÇÃO: Qualquer membro logado
```

---

## 📁 Arquivos Modificados/Criados

### Criados:
- `src/Infrastructure/Http/Controllers/NucleosController.php`
- `src/Infrastructure/Http/Controllers/BolhaController.php`
- `docs/lista-rio-architecture.md` (este arquivo)

### Modificados:
- `src/Infrastructure/Http/RestRoutes.php` - Novas rotas
- `src/Domain/Groups/Repositories/GroupsRepository.php` - Novos métodos

---

## 🧪 Testes de Endpoint

### Testar Núcleos
```bash
# Listar núcleos (autenticado)
curl -X GET "https://site.local/wp-json/apollo/v1/nucleos" -H "Cookie: wordpress_logged_in_xxx=..."

# Criar núcleo
curl -X POST "https://site.local/wp-json/apollo/v1/nucleos" \
  -H "Content-Type: application/json" \
  -d '{"title": "Núcleo Teste", "description": "Descrição"}' \
  -H "Cookie: ..."
```

### Testar Bolha
```bash
# Enviar pedido de amizade
curl -X POST "https://site.local/wp-json/apollo/v1/bolha/pedir" \
  -H "Content-Type: application/json" \
  -d '{"user_id": 123}' \
  -H "Cookie: ..."

# Listar amigos
curl -X GET "https://site.local/wp-json/apollo/v1/bolha/listar" -H "Cookie: ..."
```

---

## ⏳ Próximos Passos

1. [ ] Renomear CPT `event_listing` → `eva`
2. [ ] Criar endpoints de EVA (eventos)
3. [ ] Implementar módulo de Guest List
4. [ ] Criar sistema de notificações unificado
5. [ ] Implementar badges e verificações de perfil

---

*Documentação gerada automaticamente pelo Apollo Dev Team*
