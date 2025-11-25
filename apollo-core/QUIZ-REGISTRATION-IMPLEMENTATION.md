# Apollo Core - Quiz & Multi-Step Registration Implementation

## 📋 Implementação Parcial Concluída

### ✅ **Arquivos Criados (Backend Completo)**

1. **`includes/quiz/schema-manager.php`** (360 linhas)
   - Gerenciamento de schemas de quiz
   - CRUD de questões
   - Limite de 5 questões ativas (server-side)
   - Instagram info configurável
   - Estatísticas de quiz
   - Migração idempotente

2. **`includes/quiz/attempts.php`** (230 linhas)
   - Registro de tentativas em `wp_apollo_quiz_attempts`
   - Validação de respostas
   - Histórico de tentativas por usuário
   - Rate limiting (10 tentativas/hora)
   - Processamento de quiz para registro
   - Status de quiz do usuário

3. **`includes/quiz/rest.php`** (185 linhas)
   - **POST /apollo/v1/quiz/attempt** - Registrar tentativa
   - **GET /apollo/v1/quiz/stats** - Estatísticas (admin)
   - **GET /apollo/v1/quiz/user-attempts** - Histórico do usuário
   - Permissões e rate limiting

4. **`tests/test-registration-quiz.php`** (330 linhas)
   - 13 testes PHPUnit completos
   - Cobertura de funcionalidades do quiz

### ✅ **Arquivos Modificados**

1. **`apollo-core.php`**
   - Adicionados `require_once` para arquivos de quiz

2. **`includes/class-activation.php`**
   - Adicionado `init_quiz()` no activation hook
   - Chama `apollo_migrate_quiz_schema()`

3. **`includes/forms/rest.php`**
   - Integração com quiz em `/forms/submit`
   - Validação de respostas antes de criar usuário
   - Salvamento de campos extras (social_name, whatsapp, birthday, music_tastes)
   - Strip de @ do Instagram ID
   - Registro de tentativas de quiz com user_id real
   - `/forms/schema` retorna questões ativas e Instagram info

## 🎯 Funcionalidades Implementadas

### ✅ Backend Core
- [x] Schema de quiz configurável por form_type
- [x] CRUD de questões com validação
- [x] Limite máximo de 5 questões ativas (enforced)
- [x] Validação de respostas (múltiplas corretas suportadas)
- [x] Registro de tentativas em banco de dados
- [x] Limite de tentativas por questão
- [x] Rate limiting (IP + user_id)
- [x] Estatísticas por questão (tentativas, aprovação)
- [x] Status de quiz por usuário (_apollo_quiz_status)
- [x] Instagram info configurável

### ✅ REST API
- [x] POST /apollo/v1/quiz/attempt
- [x] GET /apollo/v1/quiz/stats
- [x] GET /apollo/v1/quiz/user-attempts
- [x] GET /apollo/v1/forms/schema (com quiz)
- [x] POST /apollo/v1/forms/submit (integrado com quiz)

### ✅ Database
- [x] Tabela `wp_apollo_quiz_attempts`
- [x] Options: `apollo_quiz_schemas`, `apollo_quiz_schema_version`, `apollo_insta_info`
- [x] User metas: `_apollo_quiz_status`, `_apollo_social_name`, `_apollo_whatsapp`, `_apollo_birthday`, `_apollo_music_tastes`, `_apollo_instagram_id`

### ✅ Testes
- [x] 13 testes PHPUnit cobrindo:
  - Inicialização de schemas
  - CRUD de questões
  - Limite de 5 questões ativas
  - Validação de respostas
  - Registro de tentativas
  - Limite de tentativas
  - Processamento de quiz
  - Registro com quiz pass/fail
  - Rate limiting

## 🚧 **Arquivos Faltando (Frontend & Admin UI)**

### Ainda Não Implementados:

5. ❌ `admin/quiz-admin.php` - UI administrativa para gerenciar questões
6. ❌ `admin/js/quiz-admin.js` - JavaScript do admin
7. ❌ `admin/css/quiz-admin.css` - Estilos do admin
8. ❌ `public/js/multi-step-registration.js` - Frontend multi-step
9. ❌ `public/js/quiz-ui.js` - UI do quiz no frontend
10. ❌ `public/css/registration.css` - Estilos do registro
11. ❌ `includes/registration/multi-step-renderer.php` - Renderização dos steps

## 🧪 Testes

### Ativar Plugin e Migrar

```bash
wp plugin deactivate apollo-core
wp plugin activate apollo-core

# Verificar se tabela foi criada
wp db query "SHOW TABLES LIKE 'wp_apollo_quiz_attempts';"

# Verificar options
wp option get apollo_quiz_schemas
wp option get apollo_quiz_schema_version
```

### Testar PHP Lint

```bash
cd wp-content/plugins/apollo-core

find . -path ./vendor -prune -o -name '*.php' -print0 | xargs -0 -n1 php -l
```

### Executar Testes PHPUnit

```bash
# Instalar dependências se necessário
composer install --no-interaction

# Rodar testes de quiz
vendor/bin/phpunit --filter Apollo_Registration_Quiz_Test

# Rodar todos os testes
vendor/bin/phpunit
```

### Testar REST API

#### 1. Obter Schema com Quiz

```bash
curl -i "http://localhost:10004/wp-json/apollo/v1/forms/schema?form_type=new_user"
```

**Resposta esperada:**
```json
{
  "form_type": "new_user",
  "schema": [...],
  "quiz_enabled": false,
  "quiz_questions": {},
  "insta_info": {
    "title": "Conecte seu Instagram",
    "subtitle": "Encontre amigos e compartilhe momentos",
    ...
  }
}
```

#### 2. Criar Questão de Quiz via PHP

```php
// wp-admin ou wp-cli
$question = array(
    'title'       => 'Qual estilo musical é considerado eletrônico?',
    'answers'     => array(
        'A' => 'House',
        'B' => 'Rock',
        'C' => 'Samba',
    ),
    'correct'     => array( 'A' ),
    'mandatory'   => true,
    'explanation' => 'House é um gênero de música eletrônica que se originou em Chicago.',
    'max_retries' => 5,
    'active'      => true,
);

$question_id = apollo_save_quiz_question( 'new_user', $question );

// Habilitar quiz
apollo_set_quiz_enabled( 'new_user', true );
```

#### 3. Registrar Tentativa de Quiz

```bash
curl -i -X POST "http://localhost:10004/wp-json/apollo/v1/quiz/attempt" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: SEU_NONCE" \
  -d '{
    "question_id": 1,
    "answers": ["A"],
    "form_type": "new_user"
  }'
```

**Resposta esperada (correto):**
```json
{
  "success": true,
  "passed": true,
  "explanation": "",
  "attempt_count": 1,
  "max_retries": 5
}
```

**Resposta esperada (incorreto):**
```json
{
  "success": true,
  "passed": false,
  "explanation": "House é um gênero de música eletrônica...",
  "attempt_count": 1,
  "max_retries": 5
}
```

#### 4. Submeter Registro com Quiz

```bash
curl -i -X POST "http://localhost:10004/wp-json/apollo/v1/forms/submit" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: SEU_NONCE" \
  -d '{
    "form_type": "new_user",
    "data": {
      "user_login": "testuser",
      "user_email": "test@example.com",
      "user_pass": "SecurePass123!",
      "social_name": "Rafael Valle",
      "instagram_user_id": "@meuinsta",
      "whatsapp": "+5521999887766",
      "birthday": "1990-01-15",
      "music_tastes": ["house", "techno"],
      "quiz_answers": {
        "1": ["A"]
      }
    }
  }'
```

**Resposta esperada:**
```json
{
  "success": true,
  "message": "Form submitted successfully.",
  "data": {
    "user_id": 123,
    "user_login": "testuser"
  }
}
```

#### 5. Verificar Status do Quiz do Usuário

```bash
wp user meta get 123 _apollo_quiz_status
# Output: passed ou failed
```

#### 6. Ver Tentativas de um Usuário

```bash
curl -i -H "X-WP-Nonce: SEU_NONCE" \
  "http://localhost:10004/wp-json/apollo/v1/quiz/user-attempts?question_id=1"
```

#### 7. Ver Estatísticas (Admin)

```bash
curl -i -H "X-WP-Nonce: ADMIN_NONCE" \
  "http://localhost:10004/wp-json/apollo/v1/quiz/stats?question_id=1&form_type=new_user"
```

**Resposta esperada:**
```json
{
  "success": true,
  "stats": {
    "total_attempts": 50,
    "passed_users": 35,
    "failed_users": 15,
    "pass_rate": 70
  },
  "attempts": [
    {
      "user_id": 123,
      "user_name": "Rafael Valle",
      "user_email": "test@example.com",
      "total_attempts": 2,
      "passed": true,
      "latest_attempt": {...},
      "all_attempts": [...]
    }
  ]
}
```

## 🔧 WP-CLI Helper Commands

```bash
# Criar questão
wp eval '
$q = array(
  "title" => "Qual é a BPM típica de House?",
  "answers" => array("A"=>"120-130", "B"=>"140-150", "C"=>"80-100"),
  "correct" => array("A"),
  "active" => true
);
echo apollo_save_quiz_question("new_user", $q);
'

# Habilitar quiz
wp eval 'echo apollo_set_quiz_enabled("new_user", true);'

# Ver questões ativas
wp eval 'print_r(apollo_get_active_quiz_questions("new_user"));'

# Ver tentativas de um usuário
wp eval 'print_r(apollo_get_user_attempts(1, 1));'

# Ver estatísticas
wp eval 'print_r(apollo_get_quiz_stats("new_user", 1));'

# Verificar tabela
wp db query "SELECT * FROM wp_apollo_quiz_attempts LIMIT 5;"
```

## 📊 Estrutura do Banco de Dados

### Tabela: `wp_apollo_quiz_attempts`

```sql
CREATE TABLE wp_apollo_quiz_attempts (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned NOT NULL,
  question_id bigint(20) unsigned NOT NULL,
  answers longtext NOT NULL,  -- JSON array
  passed tinyint(1) NOT NULL DEFAULT 0,
  attempt_number int(11) NOT NULL DEFAULT 1,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY user_id (user_id),
  KEY question_id (question_id),
  KEY created_at (created_at)
);
```

### Options

- **`apollo_quiz_schemas`**: Array de schemas por form_type
  ```php
  array(
    'new_user' => array(
      'enabled' => true,
      'require_pass' => false,
      'questions' => array(
        1 => array(
          'title' => 'Question title',
          'answers' => array('A'=>'...', 'B'=>'...'),
          'correct' => array('A'),
          'mandatory' => true,
          'explanation' => '...',
          'max_retries' => 5,
          'active' => true,
        ),
      ),
    ),
  )
  ```

- **`apollo_insta_info`**: Conteúdo editável para Instagram
  ```php
  array(
    'new_user' => array(
      'title' => 'Conecte seu Instagram',
      'subtitle' => '...',
      'paragraph' => '...',
      'quote' => '...',
    ),
  )
  ```

### User Metas

- `_apollo_quiz_status`: 'passed', 'failed', 'pending'
- `_apollo_social_name`: Nome social do usuário
- `_apollo_whatsapp`: WhatsApp
- `_apollo_birthday`: Data de nascimento
- `_apollo_music_tastes`: Array de estilos musicais
- `_apollo_instagram_id`: Instagram ID (sem @)

## 🎯 Próximos Passos para Completar

### 1. Admin UI para Quiz

Criar `admin/quiz-admin.php`:
- Aba "Register Quiz" em Apollo → Formulários
- Tabela de questões (título, tentativas, usuários, ativo)
- Modal para adicionar/editar questão
- Toggle ativo/inativo (enforçar max 5)
- Modal de estatísticas com lista de usuários
- Editor de Instagram Info

### 2. Frontend Multi-Step

Criar `public/js/multi-step-registration.js`:
- 4 steps com navegação
- Step 01: social_name, instagram_user_id, whatsapp
- Step 02: birthday, music_tastes
- Step 03: email, password, privacy checkbox
- Step 04: quiz (se habilitado) + submit
- Validação client-side
- AJAX submit com feedback
- Progress bar

### 3. Frontend Quiz UI

Criar `public/js/quiz-ui.js`:
- Renderização de questões
- Seleção de respostas (radio/checkbox)
- Validação em tempo real
- Retry logic com contador
- Exibição de explicações
- Feedback visual (correto/incorreto)

## 🔒 Segurança Implementada

- ✅ Nonces em todos os endpoints REST
- ✅ Rate limiting (10 tentativas/hora por IP/user)
- ✅ Validação de inputs (sanitize_text_field, absint)
- ✅ Prepared statements em queries SQL
- ✅ Permissões baseadas em capabilities
- ✅ Escapamento de outputs
- ✅ Validação de existência de questões
- ✅ Limit enforcement (max 5 questões ativas)

## 📝 Notas de Desenvolvimento

### Para VS Code + Intelephense

```json
// .vscode/settings.json
{
  "intelephense.stubs": [
    "wordpress",
    "wordpress-tests"
  ],
  "php.validate.executablePath": "C:/path/to/php.exe",
  "intelephense.files.maxSize": 5000000
}
```

### Para Copilot

Adicione nos arquivos PHP:

```php
// @copilot: This file manages quiz schemas for registration forms
// Available functions: apollo_save_quiz_question, apollo_validate_quiz_answer, apollo_process_quiz_submission
```

## ✅ Checklist de Migração

- [x] Tabela `wp_apollo_quiz_attempts` criada via dbDelta
- [x] Options `apollo_quiz_schemas` e `apollo_insta_info` criadas
- [x] Arquivos de quiz incluídos em apollo-core.php
- [x] Activation hook chama `init_quiz()`
- [x] forms/rest.php integrado com quiz
- [x] 13 testes PHPUnit passando
- [x] PHP lint sem erros
- [ ] Admin UI implementada
- [ ] Frontend multi-step implementado
- [ ] Quiz UI no frontend implementado
- [ ] Documentação de usuário criada

## 🐛 Troubleshooting

### Tabela não criada

```bash
wp eval 'apollo_migrate_quiz_schema();'
wp db query "SHOW TABLES LIKE 'wp_apollo_quiz_attempts';"
```

### Quiz não aparece no schema

```bash
wp eval 'apollo_set_quiz_enabled("new_user", true);'
wp option get apollo_quiz_schemas
```

### Rate limit bloqueando

```bash
# Limpar transientes de rate limit
wp transient delete apollo_quiz_rate_*
```

### Reset completo do quiz

```bash
# CUIDADO: Isto apaga todos os dados!
wp option delete apollo_quiz_schemas
wp option delete apollo_quiz_schema_version
wp option delete apollo_insta_info
wp db query "DROP TABLE IF EXISTS wp_apollo_quiz_attempts;"
wp plugin deactivate apollo-core
wp plugin activate apollo-core
```

---

**Status:** Backend Completo ✅ | Frontend & Admin Pendente ⏳

**Versão:** Apollo Core 3.0.0  
**Data:** 24 de Novembro de 2025

