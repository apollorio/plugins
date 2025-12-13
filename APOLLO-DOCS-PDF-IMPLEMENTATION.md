# Apollo Documents & PDF - Implementation Summary

## 1. INVENTORY - DOCS MODULE ✅

### Custom Post Type
- **CPT Name**: `apollo_document`
- **Location**: `apollo-social/src/Ajax/DocumentSaveHandler.php`
- **Status**: Registrado com `show_ui => true` (habilitado para admin)

### Meta Keys Normalizadas

#### Content
- `_apollo_doc_body_html` - HTML sanitizado do documento (canonical)
- `_apollo_document_delta` - Delta JSON do Quill (mantido para editor)
- `post_content` - HTML renderizado (fallback)

#### Metadata
- `_apollo_doc_title` - Título (opcional, usa post_title se vazio)
- `_apollo_doc_version` - Versão (integer, incrementa a cada save)
- `_apollo_doc_status` - Status: 'draft', 'pending', 'publish', 'signed'
- `_apollo_document_type` - Tipo: 'documento' ou 'planilha'

#### PDF
- `_apollo_doc_pdf_file` - Attachment ID do PDF gerado
- `_apollo_doc_pdf_generated` - Timestamp da última geração
- `_apollo_doc_library` - Biblioteca usada (mPDF, TCPDF, Dompdf)

### Templates
- **Editor**: `apollo-social/templates/documents/document-editor.php`
- **Sign**: `apollo-social/templates/documents/document-sign.php`
- **Listing**: `apollo-social/templates/documents/documents-listing.php`

## 2. CANONICAL "DOC" STRUCTURE ✅

### CPT Configuration
- **Name**: `apollo_document`
- **Public**: `false` (não aparece em frontend archives)
- **Show UI**: `true` (aparece no admin)
- **Capabilities**: `post` (usa `edit_posts`, `edit_post`)

### Meta Keys Implementadas
✅ `_apollo_doc_body_html` - Salvo automaticamente em `DocumentSaveHandler`
✅ `_apollo_doc_version` - Incrementa a cada save
✅ `_apollo_doc_pdf_file` - Attachment ID salvo após geração
✅ `_apollo_doc_pdf_generated` - Timestamp salvo após geração

## 3. EDITOR UI ✅

### Admin Editor
- **Metabox**: `apollo-social/src/Admin/DocumentsPdfMetabox.php`
- **Location**: Sidebar do edit screen
- **Features**:
  - Botão "Salvar como PDF"
  - Status do PDF (se já gerado)
  - Link para download
  - Timestamp da última geração
  - Aviso se biblioteca PDF não configurada

### Apollo Canvas Editor
- **Template**: `apollo-social/templates/documents/document-editor.php`
- **Features**:
  - Botão "PDF" na toolbar (aparece após salvar)
  - Geração via REST API
  - Feedback visual durante geração
  - Abre PDF em nova aba após sucesso

## 4. GENERATE HTML "PRINT VIEW" ✅

### Function Created
**File**: `apollo-social/src/Modules/Documents/DocumentsPrintView.php`

**Method**: `DocumentsPrintView::render($doc_id)`

**Features**:
- Lê `_apollo_doc_body_html` (fallback para `post_content`)
- Renderiza HTML completo com:
  - Logo Apollo (ou texto fallback)
  - Header com título, autor, data, versão
  - Conteúdo do documento
  - Footer com branding
- CSS inline otimizado para PDF
- Determinístico (sem JS, sem animações)

**Usage**:
```php
use Apollo\Modules\Documents\DocumentsPrintView;
$html = DocumentsPrintView::render($doc_id);
```

## 5. SAVE AS PDF - BACKEND FLOW ✅

### Service Class
**File**: `apollo-social/src/Modules/Documents/DocumentsPdfService.php`

**Method**: `DocumentsPdfService::generate_pdf($doc_id)`

**Flow**:
1. Valida documento e permissões
2. Chama `DocumentsPrintView::render()` para obter HTML
3. Usa `PdfGenerator` para gerar PDF
4. Cria WordPress attachment
5. Salva attachment ID em `_apollo_doc_pdf_file`
6. Salva timestamp em `_apollo_doc_pdf_generated`
7. Retorna array com `pdf_url`, `attachment_id`, `library`

**PDF Engine**:
- Suporta: mPDF, TCPDF, Dompdf (em ordem de preferência)
- Verifica disponibilidade automaticamente
- Retorna erro se nenhuma biblioteca disponível

## 6. UI: "SAVE AS PDF" BUTTON ✅

### Admin Screen
- **Metabox**: `DocumentsPdfMetabox`
- **Button**: "Salvar como PDF"
- **AJAX**: Chama REST endpoint `/apollo/v1/doc/{id}/generate-pdf`
- **Feedback**: Mostra status, link de download, timestamp

### Editor Canvas
- **Button**: "PDF" na toolbar
- **Visibility**: Aparece apenas após documento ser salvo
- **Action**: Abre PDF em nova aba após geração

## 7. PERMISSIONS & SECURITY ✅

### Capabilities
- **Create**: `edit_posts`
- **Edit**: `edit_post` (verifica ownership)
- **Generate PDF**: `edit_post` (verifica ownership)
- **Download PDF**: Acesso via attachment URL (WordPress padrão)

### Security Measures
✅ Nonce verification em todas as requisições
✅ Capability checks (`current_user_can('edit_post')`)
✅ Ownership verification (autor ou `edit_others_posts`)
✅ Sanitization (`wp_kses_post`, `absint`, `sanitize_file_name`)
✅ REST API permission callbacks

### File Protection
- PDFs salvos como WordPress attachments
- Acesso controlado por WordPress padrão
- Path: `/wp-content/uploads/apollo-documents/pdf/`
- Index.php criado automaticamente

## 8. REST ENDPOINTS

### New Endpoint
**Route**: `POST /apollo/v1/doc/{id}/gerar-pdf`

**Handler**: `DocumentsEndpoint::generatePdf()`

**Response**:
```json
{
  "success": true,
  "pdf_url": "https://example.com/wp-content/uploads/.../documento.pdf",
  "attachment_id": 123,
  "library": "mPDF",
  "message": "PDF generated successfully"
}
```

## 9. FILES CREATED/MODIFIED

### Created
1. `apollo-social/src/Modules/Documents/DocumentsPrintView.php` - Print view renderer
2. `apollo-social/src/Modules/Documents/DocumentsPdfService.php` - PDF service
3. `apollo-social/src/Admin/DocumentsPdfMetabox.php` - Admin metabox
4. `APOLLO-DOCS-PDF-INVENTORY.md` - Inventory report
5. `APOLLO-DOCS-PDF-IMPLEMENTATION.md` - This file

### Modified
1. `apollo-social/src/Ajax/DocumentSaveHandler.php` - Adiciona meta keys normalizadas e versionamento
2. `apollo-social/src/API/Endpoints/DocumentsEndpoint.php` - Adiciona endpoint `generate-pdf`
3. `apollo-social/templates/documents/document-editor.php` - Adiciona botão PDF
4. `apollo-social/apollo-social.php` - Carrega DocumentsPdfMetabox

## 10. QUICK TEST CHECKLIST

### ✅ Create a Doc
1. Acesse editor: `/doc/new` ou via shortcode `[apollo_document_editor]`
2. Digite conteúdo e salve
3. Verifique que `_apollo_doc_body_html` foi salvo
4. Verifique que `_apollo_doc_version` = 1

### ✅ Edit Body and Save
1. Edite o documento
2. Salve novamente
3. Verifique que `_apollo_doc_version` incrementou
4. Verifique que `_apollo_doc_body_html` foi atualizado

### ✅ Click "Save as PDF"
1. No admin: Edite documento → Sidebar → "Salvar como PDF"
2. No editor: Toolbar → Botão "PDF"
3. Verifique geração (se biblioteca disponível)
4. Verifique que `_apollo_doc_pdf_file` foi salvo
5. Verifique que `_apollo_doc_pdf_generated` foi salvo

### ✅ Download PDF and Verify
1. Clique no link de download
2. Verifique conteúdo do PDF
3. Verifique layout (header, footer, branding)
4. Verifique que versão está correta

### ✅ Confirm Unauthorized Access
1. Faça logout
2. Tente acessar `/wp-json/apollo/v1/doc/{id}/generate-pdf`
3. Deve retornar 401/403
4. Tente acessar URL do PDF diretamente
5. WordPress deve verificar permissões

## 11. PDF ENGINE CONFIGURATION

### Required Libraries
Instale uma das seguintes bibliotecas via Composer:

```bash
# Option 1: mPDF (recommended)
composer require mpdf/mpdf

# Option 2: TCPDF
composer require tecnickcom/tcpdf

# Option 3: Dompdf
composer require dompdf/dompdf
```

### Check Available Libraries
```php
$pdf_gen = new \Apollo\Modules\Documents\PdfGenerator();
$libraries = $pdf_gen->getAvailableLibraries();
// Returns: ['mPDF'] or ['TCPDF'] or ['Dompdf'] or []
```

## 12. NOTES

### Print View Template
- Determinístico (sem JS, sem animações)
- CSS inline para compatibilidade com PDF engines
- Logo Apollo (fallback para texto)
- Header com metadados
- Footer com branding legal

### Versioning
- Versão incrementa automaticamente a cada save
- Útil para rastreamento de mudanças
- Exibido no print view

### Fallbacks
- Se `_apollo_doc_body_html` vazio, usa `post_content`
- Se PDF library não disponível, mostra aviso no admin
- Se geração falhar, retorna erro detalhado

### Integration Points
- `DocumentSaveHandler` salva `_apollo_doc_body_html` automaticamente
- `DocumentsPdfService` usa `DocumentsPrintView` para HTML
- `PdfGenerator` já existia, apenas integrado
- REST endpoint adicionado para geração via API

