# Apollo Tooltips & Helpers Audit

## ✅ Arquivos Verificados e Atualizados

### 1. **DocumentsPdfMetabox.php**
- ✅ Tooltips adicionados em:
  - Container principal (`data-ap-tooltip`)
  - Botão "Salvar como PDF"
  - Informações do PDF gerado
  - Status da operação
- ✅ Dados sanitizados: IDs, URLs, timestamps

### 2. **DocumentsEndpoint.php**
- ✅ Respostas REST padronizadas com:
  - `success` (boolean)
  - `message` (string sanitizada)
  - `code` (string sanitizada)
  - `data` (array com dados sanitizados)
- ✅ Sanitização de:
  - Nomes de signatários (`sanitize_text_field`)
  - Emails (`sanitize_email`)
  - Roles (`sanitize_text_field`)
  - Hashes (`sanitize_text_field` + preview truncado)
  - IDs (`absint`)

### 3. **moderate-users-membership.php**
- ✅ Tooltips adicionados em:
  - Container principal
  - Botões de ação (Add, Export, Import)
  - Colunas da tabela (Color, Slug, Label, etc.)
  - Badges de tipo (Default/Custom)
  - Botões de edição/exclusão
- ✅ Dados sanitizados: slugs, labels, cores

### 4. **DocumentsPdfSignatureBlock.php**
- ✅ Sanitização completa de:
  - Nomes de signatários
  - Roles
  - Datas formatadas
  - Métodos de assinatura
  - Hashes de PDF (truncados para preview)
- ✅ Internacionalização de strings

### 5. **document-editor.php** (já tinha tooltips)
- ✅ Verificado: tooltips presentes em todos os elementos interativos
- ✅ Dados sanitizados: títulos, conteúdo, IDs

### 6. **document-sign.php** (já tinha tooltips)
- ✅ Verificado: tooltips presentes em todos os elementos
- ✅ Dados sanitizados: nomes, emails, CPF (mascarado), status

## 📋 Padrões Aplicados

### Tooltips
- **Atributo**: `data-ap-tooltip` (padrão Apollo) ou `data-tooltip`
- **Conteúdo**: Texto descritivo em português
- **Onde**: Todos os elementos interativos e dados importantes

### Sanitização
- **Textos**: `sanitize_text_field()`
- **Emails**: `sanitize_email()`
- **IDs**: `absint()`
- **Keys**: `sanitize_key()`
- **HTML**: `wp_kses_post()` ou `esc_html()`
- **URLs**: `esc_url()`

### Respostas REST
```php
array(
    'success' => bool,
    'message' => string (sanitizada),
    'code'    => string (sanitizada),
    'data'    => array (todos os valores sanitizados)
)
```

## ✅ Checklist Final

- [x] Todos os botões têm tooltips
- [x] Todos os dados são sanitizados antes de exibição
- [x] Respostas REST têm estrutura padronizada
- [x] Dados sensíveis são mascarados (emails, IPs, hashes)
- [x] Strings são internacionalizadas
- [x] IDs são validados com `absint()`
- [x] HTML é escapado com `esc_html()` ou `wp_kses_post()`

## 🎯 Próximos Passos

1. Verificar templates de eventos (`post-evento.php` se existir)
2. Adicionar tooltips em badges helper quando renderizado
3. Verificar páginas de moderação adicionais

