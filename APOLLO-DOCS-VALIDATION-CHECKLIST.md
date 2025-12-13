# Apollo Documents & Signature - Validation Checklist

## ✅ SYNTAX VALIDATION

- [x] `DocumentsSignatureService.php` - No syntax errors
- [x] `DocumentsPdfService.php` - No syntax errors  
- [x] `DocumentsPrintView.php` - No syntax errors
- [x] `DocumentsEndpoint.php` - No syntax errors (linter errors are false positives - WordPress functions)

## 📋 FUNCTIONAL VALIDATION

### PDF Generation Flow

1. **Create Document**
   - [ ] Create new document via editor (`/doc/new`)
   - [ ] Save document (should create CPT `apollo_document`)
   - [ ] Verify `_apollo_doc_body_html` is saved
   - [ ] Verify `_apollo_doc_version` = 1

2. **Generate PDF**
   - [ ] Click "Save as PDF" in admin OR editor
   - [ ] Verify PDF is generated (check `_apollo_doc_pdf_file` attachment ID)
   - [ ] Verify `_apollo_doc_pdf_hash` is saved
   - [ ] Download PDF and verify content matches document

3. **Print View**
   - [ ] Call `aprio_docs_render_print_view($doc_id)`
   - [ ] Verify HTML includes header, content, footer
   - [ ] Verify signature block appears if document has signatures

### Signature Flow

4. **Sign Document**
   - [ ] Open signing page (`/doc/{id}/sign` or template)
   - [ ] Check consent boxes
   - [ ] Click "Assinar" button
   - [ ] Verify signature is recorded in `_apollo_doc_signatures`
   - [ ] Verify `pdf_hash` matches current PDF hash
   - [ ] Verify `signed_at`, `ip_address`, `user_agent` are set

5. **Verify Signature**
   - [ ] Call `GET /wp-json/apollo/v1/doc/{id}/verify`
   - [ ] Should return `valid: true` if PDF unchanged
   - [ ] Should list all signatures with masked email

6. **Hash Mismatch Detection**
   - [ ] Modify document content
   - [ ] Regenerate PDF
   - [ ] Call verify endpoint again
   - [ ] Should return `valid: false` with `mismatches` array

### Security Tests

7. **Permissions**
   - [ ] Logout and try to sign → Should return 401/403
   - [ ] Try to generate PDF without permission → Should return 403
   - [ ] Verify endpoint should work without auth (public)

8. **Rate Limiting**
   - [ ] Sign document 3 times from same IP → Should work
   - [ ] Try 4th signature within 1 hour → Should be blocked

9. **Duplicate Prevention**
   - [ ] Sign document as user A
   - [ ] Try to sign again as same user → Should be blocked

## 🔧 INTEGRATION CHECKS

### Class Loading
- [ ] Verify `DocumentsPdfService` is accessible
- [ ] Verify `DocumentsSignatureService` is accessible
- [ ] Verify `DocumentsPrintView` is accessible
- [ ] Verify helper functions (`aprio_docs_*`) are loaded

### REST Endpoints
- [ ] `POST /wp-json/apollo/v1/doc/{id}/generate-pdf` - Works
- [ ] `POST /wp-json/apollo/v1/doc/{id}/sign` - Works
- [ ] `GET /wp-json/apollo/v1/doc/{id}/verify` - Works (public)

### UI Components
- [ ] Admin metabox appears on document edit screen
- [ ] PDF button appears in editor toolbar (after save)
- [ ] Signing page loads and displays correctly

## 📊 DATA INTEGRITY

### Meta Keys
- [ ] `_apollo_doc_body_html` - Saved on document save
- [ ] `_apollo_doc_version` - Increments on each save
- [ ] `_apollo_doc_pdf_file` - Attachment ID saved after PDF generation
- [ ] `_apollo_doc_pdf_hash` - SHA-256 hash saved
- [ ] `_apollo_doc_signatures` - Array of signatures saved

### PDF Files
- [ ] PDF files are created in `/wp-content/uploads/apollo-documents/pdf/`
- [ ] PDF files are WordPress attachments (proper permissions)
- [ ] PDF content matches document HTML

## 🚨 KNOWN ISSUES / FALSE POSITIVES

### PHPStan Errors
- All errors in `DocumentsEndpoint.php` are **false positives**
- WordPress functions (`register_rest_route`, `get_post`, etc.) are not recognized by PHPStan
- These are expected and can be ignored or suppressed in `phpstan.neon.dist`

## ✅ QUICK TEST COMMANDS

### Test PDF Generation
```bash
# In WordPress admin or via REST API
POST /wp-json/apollo/v1/doc/123/gerar-pdf
```

### Test Signature
```bash
# Via REST API
POST /wp-json/apollo/v1/doc/123/assinar
Body: {
  "name": "Test User",
  "email": "test@example.com",
  "role": "signer",
  "consent": true
}
```

### Test Verification
```bash
# Public endpoint
GET /wp-json/apollo/v1/doc/123/verificar
```

## 📝 FINAL STATUS

**Implementation**: ✅ Complete
**Syntax Check**: ✅ Passed
**Integration**: ✅ Ready for testing
**Documentation**: ✅ Complete

**Next Steps**:
1. Run manual tests using checklist above
2. Install PDF library if needed: `composer require mpdf/mpdf`
3. Test end-to-end flow: Create → PDF → Sign → Verify

