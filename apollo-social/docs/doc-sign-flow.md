# Apollo Documents · Fluxo de Assinatura Digital

> Documentação técnica do sistema de assinatura digital - FASE 1-4 completas

## Visão Geral

O módulo de assinatura digital permite assinar documentos com validade jurídica usando certificados ICP-Brasil (A1/A3) ou backend stub para desenvolvimento.

```
┌─────────────────────────────────────────────────────────────────────┐
│                      FLUXO DE ASSINATURA                            │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐      │
│  │  Criar   │───▶│  Gerar   │───▶│  Assinar │───▶│ Verificar│      │
│  │Documento │    │   PDF    │    │  (Modal) │    │   Hash   │      │
│  └──────────┘    └──────────┘    └──────────┘    └──────────┘      │
│                                                                     │
│  POST /docs      POST /pdf       POST /sign      POST /verify      │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

## Arquitetura

### Módulos

```
src/Modules/
├── Documents/
│   ├── Contracts/
│   │   └── DmsAdapterInterface.php     # Interface DMS
│   ├── Adapters/
│   │   └── LocalWordPressDmsAdapter.php # Implementação WP
│   └── DocumentsManager.php            # Gerenciador principal
│
├── Signatures/
│   ├── Contracts/
│   │   └── SignatureBackendInterface.php # Interface backends
│   ├── Backends/
│   │   ├── LocalStubBackend.php          # Dev/testes
│   │   └── DemoiselleBackend.php         # ICP-Brasil
│   ├── Services/
│   │   └── DocumentSignatureService.php  # Orquestrador
│   ├── Controllers/
│   │   └── SignaturesRestController.php  # REST API
│   ├── SignaturesModule.php              # Bootstrap
│   └── AuditLog.php                      # Trilha de auditoria
│
templates/
└── documents/
    ├── editor.php                        # Editor de documento
    └── partials/
        └── signature-modal.php           # Modal de assinatura

assets/
└── js/
    └── sign-document.js                  # Frontend JS
```

## Fluxo Detalhado

### 1. Criar/Editar Documento

```php
// Via DocumentsManager
$manager = new DocumentsManager();
$doc = $manager->createDocument([
    'title'   => 'Ofício 001/2025',
    'content' => '<p>Conteúdo HTML...</p>',
    'type'    => 'oficio',
    'status'  => 'draft'
]);
```

**Endpoint REST:**
```
POST /wp-json/apollo-social/v1/documents
Content-Type: application/json

{
    "title": "Ofício 001/2025",
    "content": "<p>Conteúdo HTML...</p>",
    "type": "oficio"
}
```

### 2. Gerar PDF

```php
// O PDF é gerado automaticamente antes da assinatura
// ou pode ser gerado manualmente:
$pdf_path = $manager->generatePdf($document_id);
```

**Endpoint REST:**
```
POST /wp-json/apollo-social/v1/documents/{id}/pdf
```

### 3. Assinar Documento

#### Via REST API

```
POST /wp-json/apollo-social/v1/documents/{id}/sign
Content-Type: application/json
Authorization: Bearer {token}

{
    "certificate_type": "A1",
    "certificate_path": "/path/to/cert.pfx",
    "certificate_pass": "senha",
    "reason": "Aprovação",
    "location": "Rio de Janeiro"
}
```

#### Via Modal (Frontend)

```javascript
// O modal é aberto pelo botão "Assinar Documento"
// O JS em sign-document.js gerencia o fluxo:

ApolloSignDocument.openSignatureModal(documentId);
```

### 4. Verificar Assinatura

```
POST /wp-json/apollo-social/v1/documents/{id}/verify

Response:
{
    "valid": true,
    "document": {
        "id": 123,
        "title": "Ofício 001/2025",
        "status": "signed"
    },
    "verification": {
        "valid": true,
        "signatures": [...],
        "chain_valid": true,
        "timestamp_valid": true
    },
    "protocol": "APL-123456789",
    "signatures_count": 1
}
```

## Backends de Assinatura

### Local Stub (Desenvolvimento)

```php
// Sempre disponível, simula assinatura
// Usado para testes e desenvolvimento

$backend = new LocalStubBackend();
$result = $backend->sign($doc_id, $user_id, $options);
// $result['is_stub'] === true
```

### Demoiselle (ICP-Brasil)

```php
// Requer configuração no wp-config.php:
define('APOLLO_DEMOISELLE_JAR_PATH', '/path/to/demoiselle-signer.jar');
define('APOLLO_DEMOISELLE_JAVA_PATH', '/usr/bin/java');
define('APOLLO_DEMOISELLE_TSA_URL', 'http://timestamp.digicert.com');
define('APOLLO_DEMOISELLE_CRL_CHECK', true);
```

### Registrar Novo Backend

```php
add_action('apollo_register_signature_backends', function($service) {
    $service->register_backend(new MyCustomBackend());
});
```

## Endpoints REST

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| `POST` | `/documents/{id}/sign` | Assinar documento |
| `GET`  | `/documents/{id}/signatures` | Listar assinaturas |
| `POST` | `/documents/{id}/verify` | Verificar assinatura |
| `GET`  | `/signatures/backends` | Listar backends |
| `POST` | `/signatures/backends/set` | Definir backend ativo |

## Modal de Assinatura

O modal (`signature-modal.php`) oferece:

- ✅ Seleção de tipo de certificado (A1/A3)
- ✅ Upload de certificado A1 (.pfx/.p12)
- ✅ Campo de senha do certificado
- ✅ Motivo da assinatura
- ✅ Localização
- ✅ Feedback de progresso
- ✅ Acessibilidade (ARIA, foco)

```html
<!-- Incluído no editor.php -->
<?php get_template_part('templates/documents/partials/signature-modal'); ?>
```

## Trilha de Auditoria

Todas as operações são logadas:

```php
$audit = new AuditLog();
$audit->log($document_id, 'signature_requested', [
    'actor_id'    => $user_id,
    'actor_name'  => $user->display_name,
    'details'     => ['backend' => 'demoiselle']
]);
```

Eventos logados:
- `created` - Documento criado
- `updated` - Documento atualizado
- `signature_requested` - Assinatura solicitada
- `signed` - Documento assinado
- `rejected` - Assinatura rejeitada
- `verified` - Documento verificado

## Hooks e Filtros

```php
// Verificar permissão de assinatura
add_filter('apollo_user_can_sign_document', function($can, $doc_id, $user_id) {
    // Lógica customizada
    return $can;
}, 10, 3);

// Após assinatura
add_action('apollo_document_signed', function($doc_id, $user_id, $result) {
    // Notificar, workflow, etc.
}, 10, 3);

// Registrar backends
add_action('apollo_register_signature_backends', function($service) {
    $service->register_backend(new MyBackend());
});
```

## URLs de QA

### Admin

| URL | Descrição |
|-----|-----------|
| `/wp-admin/admin.php?page=apollo-signatures` | Configuração de backends |
| `/wp-admin/edit.php?post_type=apollo_document` | Lista de documentos |

### Frontend (Cena-Rio)

| URL | Descrição |
|-----|-----------|
| `/cena-rio/docs` | Lista de documentos |
| `/cena-rio/docs/new` | Criar documento |
| `/cena-rio/docs/{id}` | Editor de documento |
| `/cena-rio/docs/{id}/sign` | Página de assinatura |
| `/cena-rio/docs/{id}/verify` | Verificar documento |

### REST API

| URL | Descrição |
|-----|-----------|
| `/wp-json/apollo-social/v1/documents` | CRUD documentos |
| `/wp-json/apollo-social/v1/documents/{id}/sign` | Assinar |
| `/wp-json/apollo-social/v1/documents/{id}/signatures` | Listar assinaturas |
| `/wp-json/apollo-social/v1/documents/{id}/verify` | Verificar |
| `/wp-json/apollo-social/v1/signatures/backends` | Backends disponíveis |

## Checklist de Testes

- [ ] Criar documento via admin
- [ ] Criar documento via REST
- [ ] Gerar PDF do documento
- [ ] Abrir modal de assinatura
- [ ] Assinar com backend stub
- [ ] Verificar assinatura gerada
- [ ] Verificar trilha de auditoria
- [ ] Listar backends disponíveis
- [ ] Alternar backend ativo

## Notas de Segurança

1. **Senhas de certificado**: Nunca logadas, transmitidas via HTTPS
2. **Permissões**: `user_can_sign()` verifica `edit_post` + filtro
3. **Nonces**: AJAX usa `check_ajax_referer()`
4. **Sanitização**: Todos inputs sanitizados antes de uso
5. **Escaping**: Outputs escapados com `esc_*` functions

## Versão

- **Módulo**: 2.0.0
- **Data**: Junho 2025
- **FASE**: 1-4 completas
