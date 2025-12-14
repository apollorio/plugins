# Apollo Documents Signature - Inventory Report

## 1. INVENTORY – SIGNATURE FLOW

### Existing Sign Endpoints/Routes

1. **REST Endpoints** (`apollo-social/src/Modules/Documents/SignatureEndpoints.php`):
   - `POST /apollo-docs/v1/assinar/certificado` - Assinatura com certificado ICP-Brasil
   - `POST /apollo-docs/v1/assinar/canvas` - Assinatura eletrônica (canvas)
   - `POST /apollo-docs/v1/assinar/request` - Solicitar assinatura
   - `GET /apollo-docs/v1/verificar/protocolo/{code}` - Verificar por protocolo
   - `GET /apollo-docs/v1/verificar/hash` - Verificar por hash
   - `POST /apollo-docs/v1/verificar/arquivo` - Verificar arquivo PDF

2. **Frontend Routes** (`apollo-social/config/routes.php`):
   - `/sign` - Lista de documentos para assinar
   - `/sign/{token}` - Página de assinatura com token

3. **Templates**:
   - `apollo-social/templates/documents/document-sign.php` - Página de assinatura principal
   - `apollo-social/templates/documents/sign-document.php` - Assinatura com token
   - `apollo-social/templates/documents/sign-list.php` - Lista de documentos

### Existing Signature Storage Structure

**Database Tables**:
- `wp_apollo_documents` - Tabela principal de documentos
  - `pdf_path` - Caminho do PDF
  - `pdf_hash` - Hash do PDF (já existe!)
  - `status` - Status do documento

- `wp_apollo_document_signatures` - Tabela de assinaturas
  - `document_id` - FK para documento
  - `signer_name`, `signer_cpf`, `signer_email`
  - `signature_data` - Dados da assinatura (canvas/base64)
  - `status` - 'pending' ou 'signed'
  - `signed_at` - Timestamp
  - `ip_address`, `user_agent`
  - `verification_token` - Token único

**Post Meta** (CPT `apollo_document`):
- `_apollo_document_signatures` - Array de assinaturas (usado em alguns lugares)
- `_apollo_signatures` - Alias alternativo
- `_document_signers` - Array de signatários (usado no template)

### Existing UI for Signing

**Template**: `document-sign.php`
- Preview do documento
- Lista de signatários e status
- Checkboxes de consentimento
- Botões: "Assinar com gov.br" e "Certificado ICP-Brasil"
- Validação de CPF obrigatório
- Bloqueio para usuários com passaporte

### PKI Integration

**Existing**: `apollo-social/src/Modules/Signatures/IcpBrasilSigner.php`
- Classe `IcpBrasilSigner` já implementada
- Suporta certificados .pfx/.p12
- Validação ICP-Brasil
- Extração de CPF do certificado
- Assinatura de PDF com certificado

**Status**: ✅ PKI integration já existe!

## 2. ISSUES IDENTIFIED

1. **Meta Key Inconsistency**:
   - Template usa `_document_signers` (array simples)
   - Alguns lugares usam `_apollo_document_signatures`
   - Não há estrutura padronizada para `_apollo_doc_signatures` (meta do CPT)

2. **PDF Hash**:
   - Tabela `wp_apollo_documents` tem `pdf_hash`
   - Mas não há função helper `aprio_docs_get_pdf_hash()` para CPT
   - Hash não é computado/atualizado quando PDF é gerado

3. **Signature Model**:
   - Tabela `wp_apollo_document_signatures` existe mas é para sistema antigo
   - CPT `apollo_document` não tem estrutura de meta padronizada
   - Falta integração entre tabela e meta do CPT

4. **Verification Endpoint**:
   - Existe `/verificar/protocolo/{code}` mas não há endpoint público simples
   - Falta endpoint que aceita doc_id e compara hash

5. **Signature Block in PDF**:
   - Não há implementação de bloco visível de assinaturas no PDF
   - Assinaturas ficam apenas em metadata

## 3. RECOMMENDED STRUCTURE

### Meta Key for Signatures
```php
'_apollo_doc_signatures' => [
    [
        'signer_id' => 123, // WP user ID ou null
        'signer_name' => 'João Silva',
        'signer_email' => 'joao@example.com',
        'role' => 'producer',
        'signed_at' => '2024-01-15 10:30:00',
        'ip_address' => '192.168.1.1',
        'user_agent' => 'Mozilla/5.0...',
        'pdf_hash' => 'abc123...',
        'signature_method' => 'e-sign-basic',
        'pki_signature_id' => null, // Para PKI
    ],
    // ... mais assinaturas
]
```

### Integration Points
- Usar tabela `wp_apollo_document_signatures` para sistema legado
- Adicionar meta `_apollo_doc_signatures` para CPT
- Sincronizar ambos quando possível




