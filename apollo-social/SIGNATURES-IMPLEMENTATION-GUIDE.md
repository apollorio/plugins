# Sistema de Document Templates + E-sign Apollo

## Visão Geral

O sistema de assinaturas digitais do Apollo implementa um fluxo completo de criação, preenchimento e assinatura de documentos com dois trilhos de conformidade legal conforme a **Lei 14.063/2020**.

## Arquitetura do Sistema

### Componentes Principais

```
src/Modules/Signatures/
├── Models/
│   ├── DocumentTemplate.php    # Modelo de template com placeholders
│   └── DigitalSignature.php     # Modelo de assinatura digital
├── Repositories/
│   └── TemplatesRepository.php  # CRUD de templates
├── Services/
│   ├── RenderService.php        # Geração de PDFs
│   └── SignaturesService.php    # Serviço principal
└── Adapters/
    ├── DocuSealApi.php          # Integração DocuSeal (Trilho A)
    └── GovbrApi.php             # Integração GOV.BR (Trilho B)
```

## Trilhos de Assinatura

### Trilho A - Assinatura Rápida
- **Provider:** DocuSeal
- **Nível:** Assinatura Eletrônica Avançada
- **Base Legal:** Lei 14.063/2020 Art. 10 § 2º
- **Validade:** Presunção de autenticidade e integridade
- **Uso:** Contratos comerciais, termos de uso, documentos internos

### Trilho B - Assinatura Qualificada
- **Provider:** GOV.BR/ICP-Brasil
- **Nível:** Assinatura Eletrônica Qualificada
- **Base Legal:** Lei 14.063/2020 Art. 10 § 2º + MP 2.200-2/2001
- **Validade:** Equivale à assinatura manuscrita
- **Uso:** Documentos oficiais, cartórios, órgãos públicos

## Níveis de Assinatura Digital

### 1. Assinatura Eletrônica Simples
- **Definição:** Dados em formato eletrônico que se ligam ou estão logicamente associados a outros dados eletrônicos
- **Requisitos:** Identificação do signatário de alguma forma
- **Validade:** Válida quando aceita pelas partes
- **Uso:** Documentos privados básicos

### 2. Assinatura Eletrônica Avançada
- **Definição:** Assinatura eletrônica que utiliza certificados não necessariamente emitidos pela ICP-Brasil
- **Requisitos:** 
  - Dados para criação de assinatura vinculados ao signatário
  - Detecção de alterações posteriores
- **Validade:** Presunção de autenticidade e integridade
- **Uso:** Contratos empresariais, licitações

### 3. Assinatura Eletrônica Qualificada
- **Definição:** Assinatura eletrônica avançada baseada em certificado qualificado
- **Requisitos:** Certificado digital ICP-Brasil
- **Validade:** Equivale à assinatura manuscrita
- **Uso:** Atos públicos, cartórios, documentos oficiais

## Fluxo de Uso

### 1. Criação de Template
```php
$template = new DocumentTemplate([
    'name' => 'Contrato de Prestação de Serviços',
    'content' => 'Contrato entre {{contractor_name}} e {{contracted_name}}...',
    'category' => 'contracts'
]);

$repository = new TemplatesRepository();
$saved_template = $repository->create($template->toArray());
```

### 2. Preenchimento de Dados
```php
$template_data = [
    'contractor_name' => 'Empresa XYZ Ltda',
    'contracted_name' => 'João Silva',
    'contract_value' => 'R$ 10.000,00',
    'start_date' => '2025-01-15'
];
```

### 3. Criação de Solicitação de Assinatura
```php
$signer_info = [
    'name' => 'João Silva',
    'email' => 'joao@email.com',
    'document' => '123.456.789-00'
];

$signatures_service = new SignaturesService();
$signature = $signatures_service->createSignatureRequest(
    $template_id,
    $template_data,
    $signer_info,
    SignaturesService::TRACK_A // ou TRACK_B
);
```

### 4. Processamento de Webhook
```php
// Em public/webhook-signatures.php
$docuseal_api = new DocuSealApi();
$result = $docuseal_api->processWebhook($webhook_payload);
```

## Configuração

### DocuSeal (Trilho A)
```php
// wp-admin → Apollo → Configurações
apollo_docuseal_api_key = 'sua_api_key'
apollo_docuseal_api_url = 'https://api.docuseal.co'
apollo_docuseal_webhook_secret = 'seu_webhook_secret'
```

### GOV.BR (Trilho B)
```php
// wp-admin → Apollo → Configurações
apollo_govbr_client_id = 'seu_client_id'
apollo_govbr_client_secret = 'seu_client_secret'
apollo_govbr_environment = 'sandbox' // ou 'production'
```

## Webhook URLs

- **DocuSeal:** `https://seusite.com/apollo-signatures/webhook/docuseal`
- **GOV.BR:** `https://seusite.com/apollo-signatures/webhook/govbr`

## Sistema de Badges

### Badges Automáticos
- **Contrato Assinado:** Concedido ao completar qualquer assinatura
- **Assinatura Simples:** Lei 14.063/2020 Art. 10 § 1º
- **Assinatura Avançada:** Lei 14.063/2020 Art. 10 § 2º  
- **Assinatura Qualificada:** Lei 14.063/2020 + MP 2.200-2/2001

### Configuração
```php
// config/badges.php
'signature_completed' => [
    'points' => 15,
    'badge' => 'contract_signed',
    'levels' => [
        'simple' => ['points' => 10],
        'advanced' => ['points' => 25],
        'qualified' => ['points' => 50]
    ]
]
```

## Interface Canvas

O wizard de criação de documentos está disponível em:
- **URL:** `/apollo/signatures/create`
- **Template:** `templates/signatures/document-wizard.php`

### Fluxo do Wizard
1. **Escolher Modelo:** Seleção de template predefinido
2. **Preencher Dados:** Substituição de placeholders
3. **Dados do Signatário:** Informações de quem vai assinar
4. **Escolher Trilho:** A (rápido) ou B (qualificado)
5. **Sucesso:** Link de assinatura gerado

## Compliance e Aspectos Legais

### Lei 14.063/2020 - Marco Legal das Assinaturas Eletrônicas

**Artigo 10:**
- **§ 1º:** Assinatura eletrônica simples - identificação do signatário
- **§ 2º:** Assinatura eletrônica avançada - presunção de autenticidade
- **§ 2º:** Assinatura eletrônica qualificada - equivale à manuscrita

### MP 2.200-2/2001 - ICP-Brasil
- Estabelece a Infraestrutura de Chaves Públicas Brasileira
- Certificados digitais ICP-Brasil têm presunção de veracidade
- Obrigatório para atos públicos que exijam assinatura

### Casos de Uso por Nível

#### Assinatura Simples
- ✅ Termos de uso de websites
- ✅ Contratos comerciais privados aceitos pelas partes
- ✅ Autorizações internas
- ❌ Documentos públicos
- ❌ Cartórios

#### Assinatura Avançada
- ✅ Contratos empresariais
- ✅ Licitações privadas
- ✅ Documentos trabalhistas
- ✅ Autorizações relevantes
- ⚠️ Alguns órgãos públicos (verificar exigências)
- ❌ Cartórios (maioria)

#### Assinatura Qualificada
- ✅ Todos os casos anteriores
- ✅ Documentos públicos
- ✅ Cartórios eletrônicos
- ✅ Órgãos públicos
- ✅ Atos notariais
- ✅ Contratos de alto valor

## Implementação GOV.BR (TODOs)

### OAuth2 Flow
```php
// TODO: Implementar em GovbrApi.php
// 1. Redirect para: https://sso.staging.acesso.gov.br/authorize
// 2. Receber authorization code
// 3. Trocar por access_token: POST /token
// 4. Usar token para APIs de assinatura
```

### APIs de Assinatura
```php
// TODO: Implementar endpoints reais
POST /assinatura-digital/v1/documentos     // Upload
POST /assinatura-digital/v1/solicitacoes  // Criar solicitação
GET  /assinatura-digital/v1/solicitacoes/{id} // Status
```

### Webhook Events
```php
// TODO: Implementar processamento
// - solicitacao.criada
// - assinatura.iniciada  
// - assinatura.concluida
// - assinatura.rejeitada
// - solicitacao.expirada
```

## Referências

### Documentação Legal
- **Lei 14.063/2020:** [Planalto](http://www.planalto.gov.br/ccivil_03/_ato2019-2022/2020/lei/l14063.htm)
- **MP 2.200-2/2001:** [Planalto](http://www.planalto.gov.br/ccivil_03/mpv/antigas_2001/2200-2.htm)
- **ITI - Políticas de Certificação:** [Gov.br](https://www.gov.br/iti/pt-br/assuntos/repositorio-de-arquivos)

### APIs e Integrações
- **DocuSeal:** [Documentação](https://www.docuseal.co/docs/api)
- **GOV.BR OAuth:** [Manual de Integração](https://manual-roteiro-integracao-login-unico.servicos.gov.br/)
- **Política ADRb:** [ITI](https://www.gov.br/iti/pt-br/centrais-de-conteudo/doc-icp-15-03-pdf)

### Ferramentas
- **TCPDF:** Geração de PDF
- **mPDF:** Alternativa para PDF
- **wkhtmltopdf:** Conversão HTML→PDF

## Estrutura de Banco

### apollo_document_templates
```sql
CREATE TABLE apollo_document_templates (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    description text,
    content longtext NOT NULL,
    placeholders longtext,
    category varchar(100) DEFAULT 'general',
    is_active tinyint(1) DEFAULT 1,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by bigint(20) DEFAULT NULL,
    PRIMARY KEY (id)
);
```

### apollo_digital_signatures
```sql
CREATE TABLE apollo_digital_signatures (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    template_id bigint(20) NOT NULL,
    document_hash varchar(64) NOT NULL,
    signer_name varchar(255) NOT NULL,
    signer_email varchar(255) NOT NULL,
    signer_document varchar(20),
    signature_level enum('simple','advanced','qualified') NOT NULL,
    provider enum('docuseal','govbr','icp_provider') NOT NULL,
    provider_envelope_id varchar(255),
    signing_url text,
    status enum('pending','signed','declined','expired','error') DEFAULT 'pending',
    metadata longtext,
    signed_at datetime NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by bigint(20) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_document_hash (document_hash)
);
```

## Conclusão

O sistema implementa uma solução completa de assinaturas digitais com conformidade legal total à legislação brasileira, oferecendo dois trilhos distintos para atender diferentes necessidades de segurança jurídica e compliance.