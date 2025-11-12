# 🛡️ Trilho B - Assinatura Qualificada GOV.BR

## 📋 Visão Geral

**Trilho B** é a assinatura digital **qualificada** (ICP-Brasil) implementada via **GOV.BR**, com validade jurídica **equivalente à assinatura manuscrita** conforme Lei 14.063/2020 + MP 2.200-2/2001.

---

## ⚖️ Base Legal

| Legislação | Descrição |
|------------|-----------|
| **Lei 14.063/2020** | Art. 4º - Define assinatura eletrônica qualificada |
| **MP 2.200-2/2001** | Institui a ICP-Brasil (Infraestrutura de Chaves Públicas) |
| **Decreto 10.543/2020** | Regulamenta assinatura eletrônica em serviços públicos |

**Validade Jurídica:** Presunção de **autenticidade absoluta** - equivale à assinatura manuscrita com firma reconhecida.

---

## 🔐 Características Técnicas

### Certificação Digital
- **ICP-Brasil:** Infraestrutura oficial brasileira de chaves públicas
- **Certificado Digital:** e-CPF ou e-CNPJ (A1, A3)
- **Algoritmo:** RSA 2048-bit mínimo
- **Hash:** SHA-256 ou superior
- **Timestamping:** Carimbo de tempo confiável (ICP-Brasil)

### Autenticação GOV.BR
- **Níveis de Conta:**
  - **Ouro:** Validação presencial (necessária para assinatura qualificada)
  - **Prata:** Validação biométrica (bases gov)
  - **Bronze:** Validação básica (não aceita para Trilho B)

### Processo de Assinatura
1. **Login GOV.BR** (conta nível Ouro/Prata)
2. **Autenticação multifator** (SMS/Token/Biometria)
3. **Apresentação do documento** (PDF/XML)
4. **Assinatura criptográfica** com certificado ICP-Brasil
5. **Timestamping** automático via ICP-Brasil
6. **Hash do documento** armazenado em blockchain (opcional)

---

## 📊 Níveis de Segurança

| Nível | Autenticação | Certificado | Validade Jurídica |
|-------|--------------|-------------|-------------------|
| **Simples** | E-mail/SMS | Não requer | Presunção relativa |
| **Avançada** | Biometria | Não requer | Presunção de autenticidade |
| **Qualificada** (Trilho B) | GOV.BR Ouro/ICP | e-CPF/e-CNPJ | **Equivale à manuscrita** |

---

## 🎯 Casos de Uso Obrigatórios

### Setor Público
- ✅ Contratos com órgãos públicos federais
- ✅ Licitações e pregões eletrônicos
- ✅ Processos judiciais eletrônicos (PJe)
- ✅ Documentos fiscais (NF-e, CT-e)
- ✅ Certidões e atestados oficiais

### Setor Privado (Opcional mas Recomendado)
- ✅ Contratos de alto valor (> R$ 100.000)
- ✅ Escrituras de imóveis
- ✅ Procurações com poderes especiais
- ✅ Documentos societários (alterações contratuais)
- ✅ Termos de adesão regulados (bancos, seguros)

### Apollo Social
- ✅ **Contratos de Membership** (Season Pass)
- ✅ **Termos de Parceria** com venues/promoters
- ✅ **Acordos de Confidencialidade** (artistas, DJs)
- ✅ **Autorizações de Uso de Imagem** para conteúdo oficial
- ✅ **Contratos de Prestação de Serviços** (staff, fornecedores)

---

## 🏗️ Arquitetura de Implementação

### Fluxo de Assinatura (Apollo)

```
1. USUÁRIO → Escolhe documento no Apollo
              ↓
2. APOLLO → Gera PDF + metadados
              ↓
3. GOV.BR API → Redirect para login GOV.BR
              ↓
4. USUÁRIO → Autentica (Ouro: biometria + senha)
              ↓
5. GOV.BR → Apresenta documento para assinar
              ↓
6. USUÁRIO → Confirma assinatura
              ↓
7. ICP-BRASIL → Assina com certificado digital
              ↓
8. TIMESTAMPING → Carimbo de tempo oficial
              ↓
9. WEBHOOK → Apollo recebe confirmação
              ↓
10. APOLLO → Armazena hash + PDF assinado
              ↓
11. BLOCKCHAIN → (opcional) Hash em rede pública
              ↓
12. NOTIFICAÇÃO → E-mail para signatário
```

### Endpoints GOV.BR (Produção)

```
Base URL: https://signer.staging.iti.br/api/v1

POST /oauth/token                    # Autenticação OAuth2
POST /signatures/batch               # Criar lote de assinaturas
GET  /signatures/{id}/status         # Status da assinatura
GET  /signatures/{id}/download       # Download PDF assinado
POST /webhook/signatures             # Webhook de callbacks
```

### Credenciais (Produção)
```env
GOVBR_CLIENT_ID=apollo-social-prod-12345
GOVBR_CLIENT_SECRET=******************************
GOVBR_REDIRECT_URI=https://apollo.rio.br/signatures/callback
GOVBR_WEBHOOK_SECRET=******************************
```

---

## 📦 Estrutura de Dados

### Tabela: `wp_apollo_digital_signatures`

```sql
CREATE TABLE wp_apollo_digital_signatures (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  template_id BIGINT NOT NULL,
  document_hash VARCHAR(64) NOT NULL UNIQUE,
  signer_name VARCHAR(255) NOT NULL,
  signer_email VARCHAR(255) NOT NULL,
  signer_document VARCHAR(20) NOT NULL,        -- CPF/CNPJ
  signature_level ENUM('qualified') NOT NULL,  -- Apenas Trilho B
  provider ENUM('govbr','icp_provider') NOT NULL,
  provider_envelope_id VARCHAR(255),
  signing_url TEXT,
  status ENUM('pending','signed','declined','expired','error'),
  metadata LONGTEXT,                           -- JSON com detalhes
  signed_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_signer_email (signer_email),
  INDEX idx_status (status),
  INDEX idx_provider (provider)
);
```

### Metadados JSON (campo `metadata`)

```json
{
  "govbr_account_level": "ouro",
  "govbr_cpf": "123.456.789-00",
  "certificate_serial": "1A2B3C4D5E6F",
  "certificate_issuer": "AC Serpro RFB v5",
  "certificate_validity": "2026-12-31",
  "signature_algorithm": "RSA-SHA256",
  "timestamp": "2025-11-08T14:30:00Z",
  "timestamp_authority": "ACT-ICP-Brasil",
  "document_pages": 3,
  "signature_position": "page_1_bottom_right",
  "ip_address": "177.12.34.56",
  "user_agent": "Mozilla/5.0...",
  "geolocation": {"lat": -22.9068, "lng": -43.1729}
}
```

---

## 🔒 Segurança e Compliance

### Criptografia
- **Em trânsito:** TLS 1.3
- **Em repouso:** AES-256
- **Chaves:** Armazenadas em HSM (Hardware Security Module)

### Auditoria
```php
// Log de auditoria para cada assinatura
apollo_log_signature_audit([
    'action' => 'signature_created',
    'user_id' => $user_id,
    'signature_id' => $signature_id,
    'document_hash' => $document_hash,
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'timestamp' => current_time('mysql'),
    'metadata' => json_encode($govbr_response)
]);
```

### LGPD Compliance
- ✅ **Consentimento explícito** antes de assinar
- ✅ **Minimização de dados** (apenas CPF/e-mail necessários)
- ✅ **Direito ao esquecimento** (anonimização após 5 anos)
- ✅ **Portabilidade** (exportação de documentos assinados)
- ✅ **Logs de acesso** (quem/quando visualizou documentos)

---

## 💰 Custos

| Item | Valor | Observação |
|------|-------|------------|
| **Certificado e-CPF A1** | R$ 120-150/ano | Válido 1 ano (software) |
| **Certificado e-CPF A3** | R$ 200-300/3 anos | Válido 3 anos (token/cartão) |
| **API GOV.BR** | **Gratuito** | Sem custo para uso (gov federal) |
| **Assinatura via GOV.BR** | **Gratuito** | Sem custo por assinatura |
| **Timestamping ICP-Brasil** | **Gratuito** | Incluído no processo |

**Total Apollo:** R$ 0 (usuários já possuem certificado ou conta GOV.BR Ouro)

---

## 📈 Métricas e KPIs

### Monitoramento
```php
// Métricas de assinatura
apollo_track_signature_metrics([
    'conversion_rate' => '85%',           // % que completam assinatura
    'avg_time_to_sign' => '3m 20s',       // Tempo médio para assinar
    'success_rate' => '92%',              // % assinaturas bem-sucedidas
    'decline_rate' => '5%',               // % assinaturas recusadas
    'error_rate' => '3%',                 // % erros técnicos
    'govbr_auth_failures' => '2%',        // % falhas autenticação GOV.BR
    'peak_hours' => [10, 14, 16],         // Horários de pico
]);
```

---

## 🚨 Troubleshooting

### Erro: "Conta GOV.BR não é nível Ouro"
**Solução:** Usuário precisa validar conta presencialmente em banco conveniado (Caixa, Banco do Brasil, Correios).

### Erro: "Certificado digital expirado"
**Solução:** Renovar certificado e-CPF/e-CNPJ antes do vencimento.

### Erro: "Documento já foi assinado"
**Solução:** Verificar hash do documento - não é possível assinar duplicatas.

### Erro: "Timeout na API GOV.BR"
**Solução:** Retry automático após 30 segundos (máximo 3 tentativas).

---

## 📚 Referências

- [Lei 14.063/2020](http://www.planalto.gov.br/ccivil_03/_ato2019-2022/2020/lei/L14063.htm) - Assinaturas Eletrônicas
- [MP 2.200-2/2001](http://www.planalto.gov.br/ccivil_03/mpv/antigas_2001/2200-2.htm) - ICP-Brasil
- [GOV.BR Docs](https://www.gov.br/governodigital/pt-br) - Documentação oficial
- [ITI - Instituto Nacional de Tecnologia da Informação](https://www.gov.br/iti/pt-br) - Autoridade Certificadora Raiz

---

**✅ Trilho B implementado no Apollo Social garante máxima segurança jurídica e compliance com legislação brasileira!**
