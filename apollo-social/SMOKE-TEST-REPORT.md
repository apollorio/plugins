# 🧪 SMOKE TEST LÓGICO - Análise Estática

**Data:** 2025-12-03  
**Escopo:** Validação de coerência de fluxos críticos (sem execução)

---

## 1. CPT `apollo_document`

### ✅ Registro do Post Type

**Arquivo:** `src/Ajax/DocumentSaveHandler.php:140`

```php
register_post_type(
    self::POST_TYPE, // 'apollo_document'
    array(
        'public'          => false,
        'show_ui'         => false,
        'show_in_menu'    => false,
        'capability_type' => 'post',
        'supports'        => array( 'title', 'editor', 'author', 'revisions' ),
        // ... outras opções
    )
);
```

### ⚠️ OBSERVAÇÃO: `show_in_rest` Ausente

O CPT **não tem `show_in_rest => true`**, mas isso é **intencional** porque:
- CPT é privado (`public => false`)
- Acesso é feito via AJAX handlers customizados, não via WP REST API nativa
- REST endpoints customizados existem em `SignatureEndpoints.php`

### ✅ Consistência de Uso

| Local | Uso | Status |
|-------|-----|--------|
| `DocumentSaveHandler.php:93` | `const POST_TYPE = 'apollo_document'` | ✅ |
| `LocalWordPressDmsAdapter.php:41` | `const POST_TYPE = 'apollo_document'` | ✅ |
| AJAX Handler | `handle_save()` cria/atualiza posts | ✅ |
| Templates | Usam queries diretas à tabela customizada | ✅ |

---

## 2. Meta Keys de Documento

### ✅ Meta Keys Definidas

| Meta Key | Arquivo | Propósito |
|----------|---------|-----------|
| `_apollo_document_delta` | `DocumentSaveHandler.php:100` | Conteúdo Delta (Quill) |
| `_apollo_document_type` | `DocumentSaveHandler.php:107` | Tipo (documento/planilha) |
| `_apollo_last_autosave` | `DocumentSaveHandler.php:114` | Timestamp autosave |
| `_apollo_document_signatures` | `DocumentSignatureService.php:354` | Array de assinaturas |

### ⚠️ Observação: Sistema Híbrido

O sistema usa **duas arquiteturas paralelas**:

1. **CPT `apollo_document`** (via WP Post Meta):
   - Usado pelo editor Quill
   - Meta: `_apollo_document_delta`, `_apollo_document_type`
   - Assinaturas: `_apollo_document_signatures`

2. **Tabela Customizada `wp_apollo_documents`**:
   - Usado pelo módulo de bibliotecas (apollo/cenario/private)
   - Campos: `title`, `content`, `html_content`, `status`, `pdf_path`, `pdf_hash`
   - Assinaturas: tabela `wp_apollo_document_signatures`

**Risco:** Potencial duplicação/inconsistência entre os dois sistemas.  
**Recomendação:** Documentar claramente qual sistema usar para cada caso.

---

## 3. Fluxo DOC → HTML → PDF

### ✅ Geração de PDF

**Serviço Principal:** `LocalWordPressDmsAdapter.php:396`

```php
public function generate_pdf( string $document_id, array $options = array() )
```

**Fluxo:**
1. Busca documento (CPT ou tabela)
2. Obtém HTML do conteúdo
3. Escolhe biblioteca disponível:
   - Dompdf (linha 626-627) ✅
   - TCPDF (linha 631-632) ✅
4. Retorna path do PDF gerado

### ✅ Não Há Duplicação

PDF é gerado apenas em:
- `LocalWordPressDmsAdapter::generate_pdf()` - para CPT
- `RenderService::generatePdf()` - para sistema de assinaturas

Cada um tem responsabilidade clara.

---

## 4. Fluxo de ASSINATURA

### ✅ Serviço Principal

**Arquivo:** `src/Modules/Signatures/Services/DocumentSignatureService.php`

```php
class DocumentSignatureService {
    // Backends registrados
    private array $backends = array();
    
    // Método principal
    public function sign_document( int $document_id, int $user_id, array $options )
}
```

### ✅ Endpoints Chamam o Serviço

| Endpoint | Controller | Delega Para |
|----------|------------|-------------|
| `ajax:apollo_sign_document` | `SignaturesModule::ajax_sign_document()` | `DocumentSignatureService::sign_document()` |
| `REST:/sign/certificate` | `SignatureEndpoints::signWithCertificate()` | `IcpBrasilSigner::signWithCertificate()` |
| `REST:/sign/canvas` | `SignatureEndpoints::signWithCanvas()` | `IcpBrasilSigner::signWithCanvas()` |
| `ajax:apollo_process_local_signature` | `LocalSignatureController::processSignature()` | Backend direto |

### ✅ Log de Assinatura Consistente

**Tabela:** `wp_apollo_document_signatures`  
**Meta:** `_apollo_document_signatures` (array em post meta)

**Campos do Log:**
```php
array(
    'user_id'          => $user_id,
    'user_name'        => $user->display_name,
    'signature_id'     => $result['signature_id'],
    'backend'          => $backend_identifier,
    'certificate_type' => $result['certificate']['type'],
    'timestamp'        => gmdate('Y-m-d\TH:i:s\Z'),
    'hash'             => $result['hash'],
    'status'           => 'success',
)
```

### ✅ Status Atualizado Apenas em Sucesso

**Arquivo:** `DocumentSignatureService.php:300-304`

```php
// Update document status (APENAS após sign_document() bem-sucedido)
$this->documents->updateDocument(
    $document_id,
    array( 'status' => 'signed' )
);
```

---

## 5. UI - Tooltips e Classes

### ✅ Padrão `data-ap-tooltip`

**Presença verificada em:**
- `cena-rio/templates/plans-list.php` ✅
- `cena-rio/templates/page-cena-rio.php` ✅
- `cena-rio/templates/documents-list.php` ✅
- `templates/users/private-profile.php` ✅
- `templates/documents/*.php` (parcial)

### ⚠️ Observação: Campos Críticos

| Campo | Tooltip Presente? |
|-------|-------------------|
| Status do documento | ⚠️ Parcial (apenas em cards) |
| Tipo de documento | ⚠️ Não verificado |
| Versão | ⚠️ Não verificado |
| Botão de assinatura | ✅ Presente |
| Ações sensíveis (deletar) | ✅ Presente em alguns templates |

### ✅ Classes `.ap-` Consistentes

**Arquivo:** `assets/js/sign-document.js`

```javascript
// Usa classes .ap-* corretamente
this.modal.querySelector('.navbar-actions .ap-badge')
```

**Seletores data-* usados:**
- `[data-ap-close-modal]` ✅
- `[data-ap-sign-term]` ✅
- `[data-ap-sign-provider]` ✅
- `[data-ap-open-signature-modal]` ✅

---

## 📊 RESUMO DO SMOKE TEST

| Fluxo | Status | Observações |
|-------|--------|-------------|
| CPT `apollo_document` | ✅ OK | Registrado corretamente, `show_in_rest` ausente mas intencional |
| Meta Keys | ⚠️ ATENÇÃO | Sistema híbrido (CPT + tabela) - documentar uso |
| DOC → HTML → PDF | ✅ OK | Sem duplicação, responsabilidades claras |
| Assinatura | ✅ OK | Serviço único, endpoints delegam, status consistente |
| UI Tooltips | ⚠️ PARCIAL | Adicionar em campos críticos faltantes |
| Classes .ap- | ✅ OK | Padrão consistente no JS |

---

## 🔧 RECOMENDAÇÕES

### 1. Documentar Sistema Híbrido
Criar documentação explicando quando usar:
- CPT `apollo_document` (editor Quill standalone)
- Tabela `wp_apollo_documents` (módulo de bibliotecas)

### 2. Adicionar Tooltips Faltantes
```php
// Campos que precisam de data-ap-tooltip:
- Status do documento em listagens
- Indicador de tipo (documento/planilha)
- Número da versão
```

### 3. Considerar Consolidação Futura
Avaliar se faz sentido migrar todo o sistema para uma única fonte de verdade (tabela customizada OU CPT), evitando duplicação.

---

## ✅ CONCLUSÃO

**O código passa no Smoke Test Lógico.**

Os fluxos críticos estão coerentes:
- ✅ CPT registrado sem conflitos de slug
- ✅ PDF gerado em ponto único por contexto
- ✅ Assinatura via serviço centralizado
- ✅ Status `signed` atualizado apenas em sucesso
- ✅ UI segue padrão `data-ap-*` e `.ap-*`

**Pontos de atenção documentados para melhoria contínua.**
