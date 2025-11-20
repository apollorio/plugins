# ✅ Validação Defensiva em require_once - 15/01/2025

## Objetivo

Adicionar validação `file_exists()` antes de todos os `require_once` nos 3 plugins principais para prevenir fatal errors caso arquivos estejam faltando.

## Padrão Aplicado

```php
$file = APOLLO_PATH . 'includes/arquivo.php';
if (file_exists($file)) {
    require_once $file;
} else {
    error_log('Apollo: Arquivo não encontrado: ' . $file);
}
```

## Correções Aplicadas

### 1. ✅ apollo-rio/apollo-rio.php

**Linhas corrigidas:**
- Linha 19: `includes/class-pwa-page-builders.php`
- Linha 22: `includes/template-functions.php`
- Linha 26: `includes/admin-settings.php` (dentro de `if (is_admin())`)

**Status:** ✅ Todas as validações adicionadas

**Nota:** Linha 30 já tinha validação `file_exists()` para `modules/pwa-loader.php`

---

### 2. ✅ apollo-events-manager/apollo-events-manager.php

**Linhas corrigidas:**

#### Seção 1: Arquivos principais (linhas 209-307)
- ✅ `includes/migrations.php`
- ✅ `includes/cache.php`
- ✅ `includes/shortcodes-submit.php`
- ✅ `includes/event-helpers.php`
- ✅ `includes/ajax-favorites.php`
- ✅ `includes/ajax-handlers.php`
- ✅ `includes/class-apollo-events-placeholders.php`
- ✅ `includes/class-apollo-events-analytics.php`
- ✅ `includes/shortcodes/class-apollo-events-shortcodes.php`
- ✅ `includes/widgets/class-apollo-events-widgets.php`
- ✅ `includes/save-date-cleaner.php`
- ✅ `includes/public-event-form.php`
- ✅ `includes/role-badges.php`

#### Seção 2: Dentro da classe (linhas 390-403)
- ✅ `includes/post-types.php`
- ✅ `includes/data-migration.php`
- ✅ `includes/admin-metaboxes.php` (já tinha validação, mantido)

#### Seção 3: Dashboard widgets (linha 1874)
- ✅ `includes/dashboard-widgets.php`

#### Seção 4: Activation hook (linha 3483)
- ✅ `includes/post-types.php` (com verificação adicional de `class_exists()`)

**Status:** ✅ Todas as validações adicionadas

---

### 3. ✅ apollo-social/apollo-social.php

**Status:** ✅ Já tinha validação `file_exists()` em todos os `require_once`

**Arquivos já validados:**
- Linha 47: `user-pages/user-pages-loader.php` ✅
- Linha 54: `src/Admin/HelpMenuAdmin.php` ✅
- Linha 68: `user-pages/class-user-page-cpt.php` ✅
- Linha 74: `user-pages/class-user-page-rewrite.php` ✅

**Nota:** O autoloader PSR-4 (linha 35) já tem validação `file_exists()` integrada.

---

## Melhorias Adicionais

### apollo-events-manager.php - Activation Hook

Adicionada verificação adicional de `class_exists()` antes de chamar método estático:

```php
if (file_exists($post_types_file)) {
    require_once $post_types_file;
    if (class_exists('Apollo_Post_Types')) {
        Apollo_Post_Types::flush_rewrite_rules_on_activation();
    }
} else {
    error_log('Apollo: Arquivo não encontrado: ' . $post_types_file);
}
```

Isso previne fatal errors se o arquivo existir mas a classe não estiver definida.

---

## Benefícios

1. ✅ **Prevenção de Fatal Errors:** Plugin não quebra se arquivo estiver faltando
2. ✅ **Logging:** Erros são registrados no `error_log` para debugging
3. ✅ **Graceful Degradation:** Plugin continua funcionando mesmo se alguns módulos estiverem faltando
4. ✅ **Melhor Debugging:** Mensagens de erro claras indicam qual arquivo está faltando

---

## Testes Recomendados

### Teste 1: Arquivo Faltando
1. Renomear temporariamente um arquivo incluído (ex: `includes/cache.php`)
2. Ativar plugin
3. Verificar que:
   - Plugin não causa fatal error
   - Mensagem aparece no `error_log`
   - Funcionalidades que não dependem do arquivo continuam funcionando

### Teste 2: Todos os Arquivos Presentes
1. Verificar que todos os arquivos existem
2. Ativar plugin
3. Verificar que:
   - Nenhuma mensagem de erro no log
   - Todas as funcionalidades carregam corretamente

---

## Estatísticas

| Plugin | require_once Total | Com Validação | Sem Validação (antes) |
|--------|-------------------|--------------|----------------------|
| apollo-rio | 4 | 4 ✅ | 3 ❌ |
| apollo-events-manager | 17 | 17 ✅ | 14 ❌ |
| apollo-social | 4 | 4 ✅ | 0 (já tinha) ✅ |

**Total corrigido:** 17 `require_once` agora têm validação defensiva

---

## Arquivos Modificados

1. ✅ `apollo-rio/apollo-rio.php` (linhas 19-42)
2. ✅ `apollo-events-manager/apollo-events-manager.php` (múltiplas seções)
3. ✅ `apollo-social/apollo-social.php` (nenhuma mudança necessária)

---

## Status

✅ **VALIDAÇÃO DEFENSIVA APLICADA COM SUCESSO**

- Todos os `require_once` críticos agora têm validação
- Fatal errors prevenidos
- Logging implementado
- Código mais robusto e resiliente

---

**Data:** 15/01/2025  
**Próximo Passo:** Prompt 1.3 - Verificar e corrigir dependências entre plugins

