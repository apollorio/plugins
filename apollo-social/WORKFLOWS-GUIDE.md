# 🔄 Apollo Social - Workflows Inteligentes

## Visão Geral

O sistema de workflows do Apollo Social foi redesenhado para implementar regras específicas baseadas no papel do usuário e tipo de conteúdo.

## 📋 Regras por Papel de Usuário

### 🔴 **Subscribers** (Usuários Básicos)
- **User Posts (Grupos)**: Publicação direta ✅
  - Posts, discussões, perguntas → `published`
- **Classifieds (Anúncios)**: Contrato & Publicação direta ✅
  - Todos os anúncios → `published`
- **Eventos**: Requer aprovação ⏳
  - Eventos → `pending_review`
- **Grupos Especiais**: Requer aprovação ⏳
  - Comunidades e Núcleos → `pending_review`

### 🟡 **Contributors** (Colaboradores)
- **Todo conteúdo**: Apenas rascunhos 📝
  - Grupos, eventos, anúncios → `draft`
- **Sem publicação direta**: Precisam de elevação de permissão

### 🟢 **Authors** (Autores)
- **Eventos**: Publicação direta ✅
  - Eventos → `published`
- **Outros conteúdos**: Requer aprovação ⏳
  - Grupos, anúncios → `pending_review`

### 🔵 **Editors** (Editores)
- **Todo conteúdo**: Publicação direta ✅
  - Grupos, eventos, anúncios → `published`
- **Capacidade de moderação**: Pode aprovar conteúdo de outros

### ⚫ **Administrators** (Administradores)
- **Controle total**: Todas as permissões ✅
- **Moderação avançada**: Pode fazer qualquer transição de estado
- **Gestão de sistema**: Acesso a todas as ferramentas

## 🔄 Estados de Conteúdo

| Estado | Ícone | Descrição | Visível Publicamente |
|--------|-------|-----------|---------------------|
| `draft` | 📝 | Rascunho em edição | ❌ |
| `pending_review` | ⏳ | Aguardando aprovação | ❌ |
| `published` | ✅ | Publicado e ativo | ✅ |
| `rejected` | ❌ | Rejeitado na moderação | ❌ |
| `suspended` | ⏸️ | Temporariamente suspenso | ❌ |
| `cancelled` | 🚫 | Cancelado (eventos) | ✅ |
| `expired` | ⌛ | Expirado (anúncios) | ❌ |

## 🎯 Casos de Uso Específicos

### Grupos por Tipo
```php
// User posts - publicação direta para subscribers
$group_types = ['post', 'discussion', 'question'];
$initial_state = 'published'; // ✅

// Grupos especiais - requer aprovação
$special_types = ['comunidade', 'nucleo'];
$initial_state = 'pending_review'; // ⏳
```

### Eventos por Papel
```php
// Authors e acima - publicação direta
if (in_array('author', $user->roles)) {
    $initial_state = 'published'; // ✅
}

// Subscribers - requer aprovação  
if (in_array('subscriber', $user->roles)) {
    $initial_state = 'pending_review'; // ⏳
}
```

### Anúncios/Classificados
```php
// Subscribers e acima - publicação direta (contrato & published)
$initial_state = 'published'; // ✅

// Contributors - apenas rascunhos
if (in_array('contributor', $user->roles)) {
    $initial_state = 'draft'; // 📝
}
```

## 🚀 Transições Permitidas

### Diagrama de Estados
```
📝 draft ──────────→ ⏳ pending_review ──────────→ ✅ published
   │                        │                          │
   └────────────────────────┼──────────────────────────┤
                            ↓                          ↓
                         ❌ rejected              ⏸️ suspended
                            │                          │
                            └──→ 📝 draft              └──→ ✅ published
```

### Permissões por Transição
- **draft → pending_review**: Qualquer usuário autenticado
- **pending_review → published**: Moderadores (Editor+)
- **pending_review → rejected**: Moderadores (Editor+)
- **published → suspended**: Moderadores (Editor+)
- **suspended → published**: Moderadores (Editor+)
- **rejected → draft**: Autor original

## 📊 Exemplos de Workflow

### Subscriber criando um post
```php
$workflow = new ContentWorkflow();
$initial_state = $workflow->getInitialState('group', ['type' => 'post']);
// Resultado: 'published' ✅
```

### Author criando evento
```php
$workflow = new ContentWorkflow();
$initial_state = $workflow->getInitialState('event', []);
// Resultado: 'published' ✅
```

### Subscriber criando núcleo
```php
$workflow = new ContentWorkflow();
$initial_state = $workflow->getInitialState('group', ['type' => 'nucleo']);
// Resultado: 'pending_review' ⏳
```

## 🛠️ Comandos CLI

### Testar workflows
```bash
# Testar como subscriber
wp apollo test-workflow group --user-role=subscriber

# Testar evento como author
wp apollo test-workflow event --user-role=author

# Testar anúncio como contributor
wp apollo test-workflow ad --user-role=contributor
```

### Configurar permissões
```bash
# Instalar schema e permissões
wp apollo install
wp apollo setup-permissions

# Ver estatísticas
wp apollo stats
```

## 🎨 Interface Visual

O Command Center mostra as permissões e ações disponíveis baseadas no papel do usuário:

```
🚀 Apollo Command Center
┌─────────────────────────────────────┐
│ Subscriber (João):                  │
│ ✅ Criar Posts                      │
│ ✅ Criar Anúncios                   │
│ ⏳ Criar Eventos (aprovação)        │
│ ⏳ Criar Núcleos (aprovação)        │
└─────────────────────────────────────┘
```

## 🔧 Implementação Técnica

### Classes Principais
- `ContentWorkflow`: Lógica de transições
- `Caps`: Gestão de capabilities do WordPress
- `Gate`: Validação de permissões
- `Schema`: Estrutura do banco de dados

### Hooks WordPress
- `init`: Registro de capabilities
- `admin_init`: Atribuição de permissões aos papéis
- `wp_insert_post`: Aplicação de workflow inicial

### Tabelas de Banco
- `apollo_workflow_log`: Log de transições
- `apollo_mod_queue`: Fila de moderação
- `apollo_analytics`: Eventos de sistema

## 📝 Notas de Implementação

1. **Backward Compatibility**: Sistema mantém compatibilidade com conteúdo existente
2. **Performance**: Workflows são cached para evitar queries desnecessárias  
3. **Auditoria**: Todas as transições são logadas com timestamp e usuário
4. **Notificações**: Sistema integrado de notificações por email/dashboard
5. **Escalabilidade**: Workflows podem ser estendidos para novos tipos de conteúdo

---

*Sistema implementado em 4 de novembro de 2025 - Apollo Social v1.0.0*