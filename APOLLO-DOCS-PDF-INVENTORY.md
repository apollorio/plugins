# Apollo Documents & PDF - Inventory Report

## 1. INVENTORY - DOCS MODULE

### Custom Post Type
- **CPT Name**: `apollo_document`
- **Registration**: `apollo-social/src/Ajax/DocumentSaveHandler.php` (linha 135-158)
- **Status**: Registrado, mas `show_ui => false` (edição via editor customizado)

### Meta Keys Atuais

#### Document Content
- `_apollo_document_delta` - Delta JSON do Quill editor (formato estruturado)
- `post_content` - HTML renderizado (fallback para exibição)
- `_apollo_document_type` - Tipo: 'documento' ou 'planilha'

#### Document Status & Metadata
- `_apollo_doc_status` - Status: 'draft', 'pending', 'publish', 'signed'
- `_apollo_doc_protocol` - Protocolo de assinatura
- `_apollo_doc_hash` - Hash do documento
- `_apollo_doc_library` - Biblioteca usada para gerar PDF
- `_apollo_template_id` - ID do template usado

#### Signatures
- `_apollo_document_signatures` - Array de assinaturas
- `_apollo_signatures` - Alias alternativo (usado em alguns lugares)

#### Autosave
- `_apollo_last_autosave` - Timestamp do último autosave

### Templates Existentes

1. **Editor Template**: `apollo-social/templates/documents/document-editor.php`
   - Editor word-like com toolbar
   - Salva via AJAX para `DocumentSaveHandler`
   - Usa `post_content` para HTML

2. **Document Sign**: `apollo-social/templates/documents/document-sign.php`
   - Página de assinatura de documentos

3. **Documents Listing**: `apollo-social/templates/documents/documents-listing.php`
   - Lista de documentos do usuário

### REST Endpoints

1. **DocumentsEndpoint**: `apollo-social/src/API/Endpoints/DocumentsEndpoint.php`
   - `GET /apollo/v1/doc` - Lista documentos
   - `GET /apollo/v1/doc/{id}` - Busca documento
   - `GET /apollo/v1/doc/{id}/export` - Exporta documento

2. **SignatureEndpoints**: `apollo-social/src/Modules/Documents/SignatureEndpoints.php`
   - Múltiplos endpoints para assinatura

### PDF Generation

1. **PdfGenerator**: `apollo-social/src/Modules/Documents/PdfGenerator.php`
   - Suporta: mPDF, TCPDF, Dompdf (em ordem de preferência)
   - Método: `generateFromHtml($html, $filename, $options)`
   - Salva em: `/wp-content/uploads/apollo-documents/pdf/`

2. **PdfExportHandler**: `apollo-social/src/Ajax/PdfExportHandler.php`
   - AJAX handler: `apollo_export_pdf`
   - Gera PDF a partir de HTML enviado via POST
   - Retorna URL do PDF gerado

### Hooks & Actions

- `apollo_document_signed` - Disparado após assinatura (usado em BadgeOS adapter)

### Issues Identificados

1. **Meta Key Inconsistente**: 
   - Especificação pede `_apollo_doc_body_html` mas código usa `post_content`
   - Delta está em `_apollo_document_delta` mas HTML deveria estar em meta dedicada

2. **PDF Integration**:
   - PdfGenerator existe mas não está totalmente integrado com CPT
   - PdfExportHandler recebe HTML via POST, não lê do documento salvo

3. **Print View**:
   - Não existe função dedicada `aprio_docs_render_print_view()`
   - PdfExportHandler tem `prepareStyledHtml()` mas não é reutilizável

4. **Admin UI**:
   - Não há botão "Save as PDF" no admin (edit screen)
   - Editor tem botão Preview mas não tem Save as PDF

5. **Versioning**:
   - Não há `_apollo_doc_version` implementado

## 2. RECOMMENDED STRUCTURE

### Meta Keys Normalizadas

```php
// Content
'_apollo_doc_body_html'     => HTML sanitizado do documento
'_apollo_document_delta'    => Delta JSON (mantido para editor)
'post_content'              => HTML renderizado (fallback, mantido)

// Metadata
'_apollo_doc_title'         => Título (opcional, usa post_title se vazio)
'_apollo_doc_slug'          => Slug externo (opcional)
'_apollo_doc_version'       => Versão (integer, incrementa a cada save)
'_apollo_doc_status'        => Status: 'draft', 'pending', 'publish', 'signed'
'_apollo_doc_type'          => Tipo: 'documento' ou 'planilha'

// PDF
'_apollo_doc_pdf_file'      => Attachment ID ou path relativo do PDF gerado
'_apollo_doc_pdf_generated' => Timestamp da última geração de PDF

// Signatures (mantido)
'_apollo_document_signatures' => Array de assinaturas
```

### Funções Necessárias

1. `aprio_docs_render_print_view($doc_id)` - Renderiza HTML para print/PDF
2. `aprio_docs_generate_pdf($doc_id)` - Gera PDF e salva attachment
3. `aprio_docs_get_pdf_url($doc_id)` - Retorna URL do PDF ou null

### UI Components

1. Admin metabox com botão "Salvar como PDF"
2. Editor toolbar com botão "Salvar como PDF"
3. Admin notice se PDF engine não configurado

