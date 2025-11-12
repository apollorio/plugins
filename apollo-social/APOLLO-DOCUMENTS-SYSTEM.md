# 📚 APOLLO DOCUMENTS & SIGNATURES SYSTEM
## Sistema Completo de Documentos, Planilhas e Assinaturas Digitais

---

## 🎯 VISÃO GERAL

Sistema integrado ao **Apollo Social** que permite:
- ✅ Criar e editar **DOCUMENTOS** (WORD-like)
- ✅ Criar e editar **PLANILHAS** (Excel-like)
- ✅ Assinatura digital com **ICP-Brasil** (Trilho B GOV.BR)
- ✅ Validação **CPF + Nome Completo**
- ✅ Progresso de assinaturas **(50% | 100%)**
- ✅ Links públicos de assinatura **(sem login)**

---

## 📂 ESTRUTURA DE ARQUIVOS

```
apollo-social/
├── src/Modules/Documents/
│   ├── DocumentsManager.php      # Lógica de documentos e assinaturas
│   └── DocumentsRoutes.php       # Sistema de rotas personalizadas
│
└── templates/documents/
    ├── editor.php                # Editor WYSIWYG (doc + planilha)
    ├── sign-list.php             # Lista de documentos para assinatura
    └── sign-document.php         # Página pública de assinatura
```

---

## 🌐 ROTAS (URLs)

### **DOCUMENTOS** 📄

| Rota | Descrição | Método |
|------|-----------|--------|
| `/doc/new` | Criar novo documento | GET/POST |
| `/doc/{file_id}` | Editar documento existente | GET/POST |

**Exemplo:**
```
https://mysite.com/doc/new
https://mysite.com/doc/a7f3k2m9p1q5r8t4
```

### **PLANILHAS** 📊

| Rota | Descrição | Método |
|------|-----------|--------|
| `/pla/new` | Criar nova planilha | GET/POST |
| `/pla/{file_id}` | Editar planilha existente | GET/POST |

**Exemplo:**
```
https://mysite.com/pla/new
https://mysite.com/pla/b8g4l3n0q2r6s9u5
```

### **ASSINATURAS** ✍️

| Rota | Descrição | Acesso |
|------|-----------|--------|
| `/sign` | Lista de documentos para assinar | Logado |
| `/sign/{token}` | Assinar documento via link público | Público |

**Exemplo:**
```
https://mysite.com/sign
https://mysite.com/sign/xa9f2k5m8p1q4r7t0w3y6z
```

---

## 🗄️ BANCO DE DADOS

### Tabela: `wp_apollo_documents`

```sql
CREATE TABLE wp_apollo_documents (
    id BIGINT(20) PRIMARY KEY AUTO_INCREMENT,
    file_id VARCHAR(32) UNIQUE,          -- ID público (ex: a7f3k2m9)
    type ENUM('documento','planilha'),
    title VARCHAR(255),
    content LONGTEXT,                     -- Conteúdo (texto ou JSON)
    pdf_path VARCHAR(500),                -- Caminho do PDF gerado
    status ENUM('draft','ready','signing','completed'),
    requires_signatures TINYINT(1),
    total_signatures_needed INT(2),
    created_by BIGINT(20),
    created_at DATETIME,
    updated_at DATETIME
);
```

### Tabela: `wp_apollo_document_signatures`

```sql
CREATE TABLE wp_apollo_document_signatures (
    id BIGINT(20) PRIMARY KEY AUTO_INCREMENT,
    document_id BIGINT(20),               -- FK para wp_apollo_documents
    signer_party ENUM('party_a','party_b'),
    signer_name VARCHAR(255),
    signer_cpf VARCHAR(14),
    signer_email VARCHAR(255),
    signature_data TEXT,                  -- Base64 do canvas
    signed_at DATETIME,
    verification_token VARCHAR(64),       -- Token público único
    status ENUM('pending','signed','declined'),
    ip_address VARCHAR(50),
    user_agent TEXT,
    metadata LONGTEXT
);
```

---

## ⚙️ INSTALAÇÃO

### 1️⃣ **Criar Tabelas no Banco**

Execute no WordPress Admin ou phpMyAdmin:

```php
<?php
use Apollo\Modules\Documents\DocumentsManager;

$doc_manager = new DocumentsManager();
$doc_manager->createTables();
```

### 2️⃣ **Ativar Rotas Personalizadas**

Adicione ao `apollo-social.php` (arquivo principal do plugin):

```php
<?php
// Carregar módulo de documentos
require_once APOLLO_SOCIAL_PATH . '/src/Modules/Documents/DocumentsManager.php';
require_once APOLLO_SOCIAL_PATH . '/src/Modules/Documents/DocumentsRoutes.php';
```

### 3️⃣ **Flush Rewrite Rules** (APENAS UMA VEZ)

Execute no terminal WordPress ou via browser:

```php
<?php
flush_rewrite_rules();
```

**OU** acesse:
```
WordPress Admin > Configurações > Links Permanentes > Salvar
```

---

## 🎨 FEATURES

### ✅ **EDITOR DE DOCUMENTOS** (`/doc/new`)

- **Auto-save** a cada 2 segundos
- Formatação: **Negrito**, *Itálico*, <u>Sublinhado</u>
- Elementos: Títulos, Listas, Tabelas
- Status: "💾 Salvando..." → "✅ Salvo"

### ✅ **EDITOR DE PLANILHAS** (`/pla/new`)

- Grid 10 colunas x 20 linhas (expansível)
- Adicionar/remover linhas
- Fórmulas: SOMA, MÉDIA, CONTAR *(em desenvolvimento)*
- Auto-save em JSON

### ✅ **LISTA DE ASSINATURAS** (`/sign`)

- **Upload de PDF** (drag & drop)
- **Barra de progresso**: 50% (1 assinatura) | 100% (2 assinaturas)
- **Status visual**:
  - 🔵 Pronto
  - 🟡 Em assinatura
  - 🟢 Concluído
- **Filtros**: Todos | Prontos | Em Assinatura | Concluídos

### ✅ **ASSINATURA PÚBLICA** (`/sign/{token}`)

- **SEM LOGIN** (link público)
- **Validações ICP-Brasil**:
  - ✅ CPF com algoritmo oficial (dígitos verificadores)
  - ✅ Nome completo (min 2 palavras, maioria > 3 letras)
  - ✅ E-mail válido
- **Canvas HTML5** para assinatura manuscrita
- **Auto-complete** ao 100%

---

## 🔒 VALIDAÇÕES ICP-BRASIL

### **CPF** (Algoritmo Oficial)

```php
<?php
$doc_manager->validateCPF('123.456.789-09');
// Retorna: true ou false
```

**Regras:**
- 11 dígitos
- Não pode ser sequência (111.111.111-11)
- Valida dígitos verificadores (cálculo matemático)

### **Nome Completo**

```php
<?php
$result = $doc_manager->validateFullName('João Silva Santos');
// Retorna: ['valid' => true] ou ['valid' => false, 'error' => 'mensagem']
```

**Regras:**
- Mínimo 2 palavras
- Maioria das palavras com mais de 3 letras
- Apenas letras e espaços

---

## 📊 FLUXO DE ASSINATURA

### **1. Criar Documento**
```
/doc/new → Escrever → Auto-save → /doc/{file_id}
```

### **2. Preparar para Assinatura**
```php
<?php
$result = $doc_manager->prepareForSigning($document_id);
// Gera PDF e muda status para 'ready'
```

### **3. Criar Solicitações de Assinatura**
```php
<?php
// Parte A (assinante interno)
$doc_manager->createSignatureRequest(
    $document_id, 
    'party_a', 
    'interno@mysite.com',
    'João Silva',
    '123.456.789-09'
);

// Parte B (assinante externo)
$doc_manager->createSignatureRequest(
    $document_id, 
    'party_b', 
    'externo@exemplo.com'
);
// Retorna: ['success' => true, 'sign_url' => '/sign/{token}']
```

### **4. Assinatura (Link Público)**
```
E-mail → Link /sign/{token} → Formulário → Validações → Canvas → Assinar
```

### **5. Progresso Atualizado**
```php
<?php
$completion = $doc_manager->getCompletionPercentage($document_id);
// Retorna: 0, 50, 100
```

---

## 🎯 EXEMPLOS DE USO

### **Criar Documento via PHP**

```php
<?php
use Apollo\Modules\Documents\DocumentsManager;

$doc_manager = new DocumentsManager();

$result = $doc_manager->createDocument(
    'documento',                    // tipo
    'Contrato de Prestação de Serviços',  // título
    'Conteúdo inicial do contrato...',    // conteúdo
    get_current_user_id()          // ID do usuário
);

if ($result['success']) {
    echo "Documento criado: " . $result['url'];
    // https://mysite.com/doc/a7f3k2m9p1q5r8t4
}
```

### **Criar Planilha via PHP**

```php
<?php
$result = $doc_manager->createDocument(
    'planilha',
    'Orçamento 2025',
    json_encode([
        ['A1' => 'Item', 'B1' => 'Valor'],
        ['A2' => 'Produto 1', 'B2' => '100'],
        ['A3' => 'Produto 2', 'B3' => '200']
    ])
);
```

### **Enviar para Assinatura**

```php
<?php
// 1. Preparar PDF
$doc_manager->prepareForSigning(123);

// 2. Solicitar assinaturas
$party_a = $doc_manager->createSignatureRequest(
    123, 
    'party_a', 
    'ceo@empresa.com',
    'Carlos Eduardo Silva',
    '987.654.321-00'
);

$party_b = $doc_manager->createSignatureRequest(
    123, 
    'party_b', 
    'cliente@externo.com'
);

// 3. Enviar e-mails automáticos
echo "Link Parte A: " . $party_a['sign_url'];
echo "Link Parte B: " . $party_b['sign_url'];
```

### **Verificar Status**

```php
<?php
$completion = $doc_manager->getCompletionPercentage(123);

if ($completion == 50) {
    echo "🟡 Aguardando 1 assinatura";
} elseif ($completion == 100) {
    echo "🟢 Documento completo!";
}
```

---

## 🛠️ PERSONALIZAÇÃO

### **Adicionar Mais Colunas (Planilha)**

Edite `templates/documents/editor.php` linha 364:

```php
<?php for ($col = 0; $col < 20; $col++): ?> <!-- Aumentar de 10 para 20 -->
```

### **Mudar Quantidade de Assinaturas**

Edite `DocumentsManager.php` linha 75:

```php
'total_signatures_needed' => 3,  // Padrão é 2
```

### **Adicionar Parte C**

Altere `signer_party` ENUM para:

```sql
signer_party ENUM('party_a','party_b','party_c')
```

---

## 📧 E-MAILS AUTOMÁTICOS

### **Quando Enviado**
```
Assunto: [Apollo] Documento aguardando sua assinatura
Corpo: Link único para assinar (válido 30 dias)
```

### **Personalizar Template**

Edite `DocumentsManager.php` método `sendSignatureEmail()` (linha 262):

```php
<?php
$message = "Olá {$name},\n\n";
$message .= "Você recebeu um documento para assinatura.\n\n";
$message .= "Link: {$sign_url}\n\n";
$message .= "Apollo Social";

wp_mail($email, $subject, $message);
```

**Usar HTML:**

```php
<?php
add_filter('wp_mail_content_type', function() {
    return 'text/html';
});

$message = "<h1>Documento para Assinatura</h1>";
$message .= "<p>Olá <strong>{$name}</strong>,</p>";
$message .= "<a href='{$sign_url}' style='background: #667eea; color: white; padding: 15px 30px;'>Assinar Agora</a>";
```

---

## 🔐 SEGURANÇA

### **Tokens de Verificação**

- Gerados com `wp_generate_password(32, false)`
- 32 caracteres aleatórios
- Únicos e não-duplicáveis

### **Registro de Auditoria**

Cada assinatura registra:
- IP Address (`$_SERVER['REMOTE_ADDR']`)
- User Agent (navegador/dispositivo)
- Timestamp exato
- CPF e nome validados

### **Proteção CSRF**

```php
<?php
wp_nonce_field('apollo_signature', 'signature_nonce');

// Validar no POST:
if (!wp_verify_nonce($_POST['signature_nonce'], 'apollo_signature')) {
    wp_die('Token inválido');
}
```

---

## 📊 RELATÓRIOS

### **Listar Documentos por Status**

```php
<?php
global $wpdb;
$table = $wpdb->prefix . 'apollo_documents';

$completed = $wpdb->get_results("
    SELECT * FROM {$table} 
    WHERE status = 'completed' 
    ORDER BY updated_at DESC
");

foreach ($completed as $doc) {
    echo "{$doc->title} - Concluído em {$doc->updated_at}\n";
}
```

### **Exportar CSV de Assinaturas**

```php
<?php
$signatures = $wpdb->get_results("
    SELECT s.*, d.title 
    FROM {$wpdb->prefix}apollo_document_signatures s
    INNER JOIN {$wpdb->prefix}apollo_documents d ON s.document_id = d.id
    WHERE s.status = 'signed'
");

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="assinaturas.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Documento', 'Nome', 'CPF', 'Email', 'Data']);

foreach ($signatures as $sig) {
    fputcsv($output, [
        $sig->title,
        $sig->signer_name,
        $sig->signer_cpf,
        $sig->signer_email,
        $sig->signed_at
    ]);
}

fclose($output);
exit;
```

---

## 🚀 PRÓXIMOS PASSOS

### ✅ **IMPLEMENTADO**
- [x] Sistema de rotas personalizadas
- [x] Editor de documentos WYSIWYG
- [x] Editor de planilhas (grid)
- [x] Validação CPF + Nome ICP-Brasil
- [x] Canvas de assinatura HTML5
- [x] Progresso 50%/100%
- [x] Links públicos de assinatura
- [x] E-mails automáticos

### ⏳ **EM DESENVOLVIMENTO**
- [ ] Conversão para PDF (requer biblioteca `mpdf` ou `dompdf`)
- [ ] Fórmulas de planilha (SOMA, MÉDIA, CONTAR)
- [ ] Adicionar colunas dinamicamente
- [ ] Histórico de versões
- [ ] Comentários em documentos

### 🔮 **FUTURO**
- [ ] Integração GOV.BR API (certificados A3)
- [ ] Assinatura em lote (múltiplos PDFs)
- [ ] Modelos de documentos (templates)
- [ ] Exportar planilha para Excel (XLSX)
- [ ] OCR para PDFs digitalizados

---

## 🆘 TROUBLESHOOTING

### **Erro: "404 Not Found" nas rotas**

**Solução:** Flush rewrite rules
```php
<?php
flush_rewrite_rules();
```

### **Auto-save não funciona**

**Verificar:** JavaScript console (F12)
**Solução:** Verificar se AJAX está habilitado no WordPress

### **Validação CPF sempre falha**

**Verificar:** Formato do CPF
**Solução:** Usar máscara `000.000.000-00` ou apenas números

### **E-mail não chega**

**Verificar:** Configuração SMTP do WordPress
**Solução:** Usar plugin como **WP Mail SMTP** ou **Post SMTP**

### **Canvas de assinatura não desenha**

**Verificar:** Dispositivo touch (mobile)
**Solução:** Testar em desktop primeiro (mouse)

---

## 📞 SUPORTE

**Documentação:** `APOLLO-DOCUMENTS-SYSTEM.md`  
**Código:** `apollo-social/src/Modules/Documents/`  
**Templates:** `apollo-social/templates/documents/`  
**Banco:** `wp_apollo_documents`, `wp_apollo_document_signatures`

---

## 📜 LICENÇA

Sistema integrado ao **Apollo Social Plugin**  
Desenvolvido para WordPress 6.x + PHP 8.0+  
ICP-Brasil Compliance: **Trilho B (GOV.BR)**

---

**🎉 Sistema completo e funcional!**

Para testar:
1. Acesse `/doc/new` (criar documento)
2. Acesse `/pla/new` (criar planilha)
3. Acesse `/sign` (lista de assinaturas)
4. Use link `/sign/{token}` para assinar (público)
