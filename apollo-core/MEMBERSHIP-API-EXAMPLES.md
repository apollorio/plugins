# Apollo Core - Membership API Test Examples

Este documento contém exemplos práticos para testar todos os endpoints do sistema de memberships.

## 🔑 Obtendo o Nonce

Antes de fazer requisições autenticadas, você precisa obter o nonce. Há duas formas:

### Opção 1: Via Console do Navegador (Recomendado)

1. Faça login no WordPress Admin
2. Abra o Console de Desenvolvedor (F12)
3. Execute:

```javascript
console.log('Nonce:', wpApiSettings.nonce);
console.log('Root URL:', wpApiSettings.root);
```

### Opção 2: Via Cookie e PHP

```php
<?php
// wp-content/plugins/apollo-core/get-nonce.php
require_once('../../../wp-load.php');

if (is_user_logged_in()) {
    echo 'Nonce: ' . wp_create_nonce('wp_rest') . PHP_EOL;
    echo 'User ID: ' . get_current_user_id() . PHP_EOL;
    echo 'User: ' . wp_get_current_user()->user_login . PHP_EOL;
} else {
    echo 'Not logged in. Visit: ' . wp_login_url() . PHP_EOL;
}
```

## 📋 Variáveis de Ambiente

Para facilitar os testes, defina estas variáveis no terminal:

```bash
# Windows PowerShell
$BASE_URL = "http://localhost:10004/wp-json/apollo/v1"
$NONCE = "SEU_NONCE_AQUI"
$COOKIE = "wordpress_logged_in_...=..."

# Linux/Mac Bash
export BASE_URL="http://localhost:10004/wp-json/apollo/v1"
export NONCE="SEU_NONCE_AQUI"
export COOKIE="wordpress_logged_in_...=..."
```

## 🧪 Testes dos Endpoints

### 1. GET /memberships (Público)

Listar todos os tipos de membership disponíveis.

**curl (Windows PowerShell):**
```powershell
curl -i "$BASE_URL/memberships"
```

**curl (Linux/Mac):**
```bash
curl -i "$BASE_URL/memberships"
```

**JavaScript (Console do Navegador):**
```javascript
fetch('/wp-json/apollo/v1/memberships')
  .then(r => r.json())
  .then(data => {
    console.log('Memberships:', data.memberships);
    console.log('Version:', data.version);
  });
```

**Resposta Esperada:**
```json
{
  "success": true,
  "version": "1.0.0",
  "memberships": {
    "nao-verificado": {
      "label": "Não Verificado",
      "frontend_label": "Não Verificado",
      "color": "#9AA0A6",
      "text_color": "#6E7376"
    },
    "apollo": {
      "label": "Apollo",
      "frontend_label": "Apollo",
      "color": "#FF8C42",
      "text_color": "#7A3E00"
    }
  }
}
```

---

### 2. POST /memberships/set (Requer `edit_apollo_users`)

Atribuir membership a um usuário.

**curl (Windows PowerShell):**
```powershell
$body = @{
    user_id = 1
    membership_slug = "apollo"
} | ConvertTo-Json

curl -i -X POST "$BASE_URL/memberships/set" `
  -H "Content-Type: application/json" `
  -H "X-WP-Nonce: $NONCE" `
  -H "Cookie: $COOKIE" `
  -d $body
```

**curl (Linux/Mac):**
```bash
curl -i -X POST "$BASE_URL/memberships/set" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: $NONCE" \
  -H "Cookie: $COOKIE" \
  -d '{
    "user_id": 1,
    "membership_slug": "apollo"
  }'
```

**JavaScript (Console do Navegador):**
```javascript
fetch('/wp-json/apollo/v1/memberships/set', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': wpApiSettings.nonce
  },
  body: JSON.stringify({
    user_id: 1, // Substitua pelo ID do usuário
    membership_slug: 'apollo'
  })
})
.then(r => r.json())
.then(console.log)
.catch(console.error);
```

**Resposta Esperada:**
```json
{
  "success": true,
  "message": "Membership updated successfully",
  "user_id": 1,
  "user_name": "admin",
  "membership": {
    "label": "Apollo",
    "frontend_label": "Apollo",
    "color": "#FF8C42",
    "text_color": "#7A3E00"
  }
}
```

**Possíveis Erros:**
```json
// Erro 403: Usuário não tem permissão
{
  "code": "rest_forbidden",
  "message": "Sorry, you are not allowed to do that.",
  "data": { "status": 403 }
}

// Erro 400: Membership inválida
{
  "success": false,
  "message": "Failed to update membership"
}
```

---

### 3. POST /memberships/create (Requer `manage_options` - Admin)

Criar novo tipo de membership.

**curl (Windows PowerShell):**
```powershell
$body = @{
    slug = "vip-member"
    label = "VIP Member"
    frontend_label = "VIP"
    color = "#FFD700"
    text_color = "#8B6B00"
} | ConvertTo-Json

curl -i -X POST "$BASE_URL/memberships/create" `
  -H "Content-Type: application/json" `
  -H "X-WP-Nonce: $NONCE" `
  -H "Cookie: $COOKIE" `
  -d $body
```

**curl (Linux/Mac):**
```bash
curl -i -X POST "$BASE_URL/memberships/create" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: $NONCE" \
  -H "Cookie: $COOKIE" \
  -d '{
    "slug": "vip-member",
    "label": "VIP Member",
    "frontend_label": "VIP",
    "color": "#FFD700",
    "text_color": "#8B6B00"
  }'
```

**JavaScript (Console do Navegador):**
```javascript
fetch('/wp-json/apollo/v1/memberships/create', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': wpApiSettings.nonce
  },
  body: JSON.stringify({
    slug: 'vip-member',
    label: 'VIP Member',
    frontend_label: 'VIP',
    color: '#FFD700',
    text_color: '#8B6B00'
  })
})
.then(r => r.json())
.then(console.log)
.catch(console.error);
```

**Resposta Esperada:**
```json
{
  "success": true,
  "message": "Membership created successfully",
  "membership": {
    "label": "VIP Member",
    "frontend_label": "VIP",
    "color": "#FFD700",
    "text_color": "#8B6B00"
  }
}
```

---

### 4. POST /memberships/update (Requer `manage_options` - Admin)

Atualizar tipo de membership existente (apenas customizadas).

**curl (Windows PowerShell):**
```powershell
$body = @{
    slug = "vip-member"
    label = "VIP Premium Member"
    color = "#FFD700"
} | ConvertTo-Json

curl -i -X POST "$BASE_URL/memberships/update" `
  -H "Content-Type: application/json" `
  -H "X-WP-Nonce: $NONCE" `
  -H "Cookie: $COOKIE" `
  -d $body
```

**curl (Linux/Mac):**
```bash
curl -i -X POST "$BASE_URL/memberships/update" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: $NONCE" \
  -H "Cookie: $COOKIE" \
  -d '{
    "slug": "vip-member",
    "label": "VIP Premium Member",
    "color": "#FFD700"
  }'
```

**JavaScript (Console do Navegador):**
```javascript
fetch('/wp-json/apollo/v1/memberships/update', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': wpApiSettings.nonce
  },
  body: JSON.stringify({
    slug: 'vip-member',
    label: 'VIP Premium Member'
  })
})
.then(r => r.json())
.then(console.log);
```

**Resposta Esperada:**
```json
{
  "success": true,
  "message": "Membership updated successfully",
  "membership": {
    "label": "VIP Premium Member",
    "frontend_label": "VIP",
    "color": "#FFD700",
    "text_color": "#8B6B00"
  }
}
```

---

### 5. POST /memberships/delete (Requer `manage_options` - Admin)

Deletar tipo de membership customizada.

**curl (Windows PowerShell):**
```powershell
$body = @{
    slug = "vip-member"
} | ConvertTo-Json

curl -i -X POST "$BASE_URL/memberships/delete" `
  -H "Content-Type: application/json" `
  -H "X-WP-Nonce: $NONCE" `
  -H "Cookie: $COOKIE" `
  -d $body
```

**curl (Linux/Mac):**
```bash
curl -i -X POST "$BASE_URL/memberships/delete" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: $NONCE" \
  -H "Cookie: $COOKIE" \
  -d '{
    "slug": "vip-member"
  }'
```

**JavaScript (Console do Navegador):**
```javascript
if (confirm('Deletar membership? Usuários serão reatribuídos a nao-verificado.')) {
  fetch('/wp-json/apollo/v1/memberships/delete', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
      slug: 'vip-member'
    })
  })
  .then(r => r.json())
  .then(console.log);
}
```

**Resposta Esperada:**
```json
{
  "success": true,
  "message": "Membership deleted successfully. Users reassigned to Não Verificado."
}
```

---

### 6. GET /memberships/export (Requer `manage_options` - Admin)

Exportar memberships como JSON.

**curl (Windows PowerShell):**
```powershell
curl -i "$BASE_URL/memberships/export" `
  -H "X-WP-Nonce: $NONCE" `
  -H "Cookie: $COOKIE"
```

**curl (Linux/Mac):**
```bash
curl -i "$BASE_URL/memberships/export" \
  -H "X-WP-Nonce: $NONCE" \
  -H "Cookie: $COOKIE"
```

**JavaScript (Console do Navegador) - Download automático:**
```javascript
fetch('/wp-json/apollo/v1/memberships/export', {
  headers: {
    'X-WP-Nonce': wpApiSettings.nonce
  }
})
.then(r => r.json())
.then(response => {
  const dataStr = response.data;
  const dataBlob = new Blob([dataStr], {type: 'application/json'});
  const url = URL.createObjectURL(dataBlob);
  const link = document.createElement('a');
  link.href = url;
  link.download = `apollo-memberships-${new Date().toISOString().split('T')[0]}.json`;
  link.click();
  console.log('Exported!');
});
```

**Resposta Esperada:**
```json
{
  "success": true,
  "data": "{\"version\":\"1.0.0\",\"exported_at\":\"2025-11-24 10:30:00\",\"memberships\":{...}}"
}
```

---

### 7. POST /memberships/import (Requer `manage_options` - Admin)

Importar memberships de JSON.

**curl (Windows PowerShell):**
```powershell
$jsonData = Get-Content -Raw memberships-backup.json | ConvertTo-Json
$body = @{
    data = $jsonData
} | ConvertTo-Json

curl -i -X POST "$BASE_URL/memberships/import" `
  -H "Content-Type: application/json" `
  -H "X-WP-Nonce: $NONCE" `
  -H "Cookie: $COOKIE" `
  -d $body
```

**curl (Linux/Mac):**
```bash
JSON_DATA=$(cat memberships-backup.json)

curl -i -X POST "$BASE_URL/memberships/import" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: $NONCE" \
  -H "Cookie: $COOKIE" \
  -d "{\"data\": $(printf '%s' "$JSON_DATA" | jq -c .)}"
```

**JavaScript (Console do Navegador) - Upload de arquivo:**
```javascript
const fileInput = document.createElement('input');
fileInput.type = 'file';
fileInput.accept = '.json';
fileInput.onchange = (e) => {
  const file = e.target.files[0];
  const reader = new FileReader();
  reader.onload = (event) => {
    const jsonData = event.target.result;
    
    fetch('/wp-json/apollo/v1/memberships/import', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
      },
      body: JSON.stringify({
        data: jsonData
      })
    })
    .then(r => r.json())
    .then(console.log)
    .catch(console.error);
  };
  reader.readAsText(file);
};
fileInput.click();
```

**Resposta Esperada:**
```json
{
  "success": true,
  "message": "Memberships imported successfully"
}
```

---

## 🔍 Testando com Postman

### 1. Configurar Headers Globais

Na aba "Headers" da coleção:

```
Content-Type: application/json
X-WP-Nonce: {{nonce}}
Cookie: {{wordpress_cookie}}
```

### 2. Configurar Variáveis de Ambiente

Criar environment "Apollo Local" com:

```
base_url: http://localhost:10004/wp-json/apollo/v1
nonce: [SEU_NONCE]
wordpress_cookie: wordpress_logged_in_...=[SEU_COOKIE]
user_id: 1
```

### 3. Importar Coleção

Criar nova coleção "Apollo Memberships API" e adicionar requests para cada endpoint acima.

---

## 🐛 Debugging

### Ver Logs de Auditoria

```bash
wp db query "SELECT * FROM wp_apollo_mod_log WHERE action LIKE 'membership%' ORDER BY created_at DESC LIMIT 10;"
```

### Ver Membership de Usuário

```bash
wp user meta get 1 _apollo_membership
```

### Ver Options

```bash
wp option get apollo_memberships
wp option get apollo_memberships_version
```

### Resetar Memberships (Cuidado!)

```bash
# Backup primeiro
wp apollo membership export /tmp/backup.json

# Deletar todas customizadas
wp option delete apollo_memberships

# Resetar versão
wp option update apollo_memberships_version "1.0.0"

# Reatribuir todos a nao-verificado
wp db query "DELETE FROM wp_usermeta WHERE meta_key = '_apollo_membership';"
wp apollo db-test
```

---

## ✅ Checklist de Testes Completo

### Testes Básicos
- [ ] GET /memberships retorna 7 tipos padrão
- [ ] POST /memberships/set atribui membership corretamente
- [ ] Mudança aparece no audit log
- [ ] Badge aparece no frontend

### Testes de Permissões
- [ ] Usuário subscriber não pode criar memberships (403)
- [ ] Moderador `apollo` pode atribuir memberships
- [ ] Moderador `apollo` não pode criar tipos (403)
- [ ] Admin pode tudo

### Testes de Validação
- [ ] Cores inválidas são rejeitadas
- [ ] Slug duplicado é rejeitado
- [ ] User_id inválido é rejeitado
- [ ] Membership_slug inválida é rejeitada

### Testes de Delete
- [ ] Deletar membership reatribui usuários a `nao-verificado`
- [ ] Não pode deletar `nao-verificado` (403)
- [ ] Não pode deletar memberships padrão (403)

### Testes de Export/Import
- [ ] Export retorna JSON válido
- [ ] Import restaura memberships corretamente
- [ ] Import com JSON inválido retorna erro

---

**Última atualização:** 24 de Novembro de 2025  
**Versão:** Apollo Core 3.0.0

