# ✅ Correções de Dependências entre Plugins - 15/01/2025

## Objetivo

Corrigir verificações de dependências entre os 3 plugins principais para prevenir erros quando plugins não estão carregados ou ativos.

## Problemas Identificados

### 1. ❌ apollo-events-manager-loader.php
- **Problema:** Usava `is_plugin_active()` diretamente, que pode falhar se plugin não estiver carregado ainda
- **Impacto:** Plugin pode não carregar mesmo com apollo-social ativo
- **Solução:** Usar hook `plugins_loaded` com múltiplas verificações

### 2. ⚠️ apollo-social-loader.php  
- **Problema:** Verificação básica, mas poderia melhorar validação defensiva
- **Solução:** Adicionar validação de arquivos e fallback

### 3. ✅ apollo-rio
- **Status:** Não tem dependências explícitas (independente)

---

## Correções Aplicadas

### 1. ✅ apollo-events-manager-loader.php

**ANTES:**
```php
if (!is_plugin_active('apollo-social/apollo-social.php')) {
    wp_die('O plugin Apollo Social precisa estar ativo...');
}
// Carrega imediatamente
```

**DEPOIS:**
```php
add_action('plugins_loaded', function() {
    // Múltiplas verificações:
    // 1. Verificar função/classe do plugin
    // 2. Verificar constantes do plugin
    // 3. Verificar is_plugin_active() como fallback
    
    if (!$apollo_social_active) {
        add_action('admin_notices', ...); // Aviso amigável
        return; // Não carrega
    }
    
    // Carrega com validação file_exists()
}, 20); // Prioridade 20: depois de apollo-social
```

**Melhorias:**
- ✅ Usa hook `plugins_loaded` para garantir ordem correta
- ✅ Múltiplas verificações (função, classe, constante, is_plugin_active)
- ✅ Aviso amigável no admin ao invés de wp_die()
- ✅ Validação `file_exists()` antes de carregar
- ✅ Prioridade 20 garante que apollo-social carrega primeiro

---

### 2. ✅ apollo-social-loader.php

**ANTES:**
```php
if (!function_exists('add_action')) {
    wp_die('WordPress precisa estar ativo...');
}
// Carrega todos os arquivos PHP
```

**DEPOIS:**
```php
if (!defined('ABSPATH')) {
    exit; // Segurança
}

if (!function_exists('add_action')) {
    // Fallback se wp_die não existir
    if (function_exists('wp_die')) {
        wp_die('...');
    } else {
        die('WordPress não está carregado...');
    }
}

// Carrega arquivo principal com validação
if (file_exists($main_file)) {
    require_once $main_file;
} else {
    // Fallback: carregar outros arquivos PHP
}
```

**Melhorias:**
- ✅ Verificação `ABSPATH` para segurança
- ✅ Fallback se `wp_die()` não existir
- ✅ Validação `file_exists()` antes de carregar
- ✅ Fallback para carregar outros arquivos PHP se principal não existir

---

### 3. ✅ apollo-rio/apollo-rio.php

**Status:** Não requer correções - plugin independente

**Nota:** Se no futuro apollo-rio precisar de funcionalidades de apollo-social, adicionar verificações similares.

---

## Verificações Defensivas Adicionadas

### Padrão de Verificação de Dependências

```php
add_action('plugins_loaded', function() {
    $plugin_active = false;
    
    // Método 1: Verificar função/classe
    if (function_exists('plugin_function') || class_exists('Plugin\\Class')) {
        $plugin_active = true;
    }
    
    // Método 2: Verificar constante
    if (defined('PLUGIN_CONSTANT')) {
        $plugin_active = true;
    }
    
    // Método 3: Verificar is_plugin_active() (fallback)
    if (!$plugin_active && function_exists('is_plugin_active')) {
        $plugin_active = is_plugin_active('plugin-name/plugin-file.php');
    }
    
    if (!$plugin_active) {
        // Aviso ou retorno
        return;
    }
    
    // Carregar plugin dependente
}, 20); // Prioridade adequada
```

---

## Ordem de Carregamento

1. **WordPress Core** (prioridade padrão)
2. **apollo-social** (prioridade padrão 10)
3. **apollo-events-manager** (prioridade 20 - depois de apollo-social)
4. **apollo-rio** (independente, pode carregar a qualquer momento)

---

## Benefícios

1. ✅ **Prevenção de Erros:** Plugins não quebram se dependências não estiverem ativas
2. ✅ **Ordem Correta:** Hook `plugins_loaded` garante ordem de carregamento
3. ✅ **Múltiplas Verificações:** Não depende de apenas um método
4. ✅ **Avisos Amigáveis:** Admin notices ao invés de wp_die()
5. ✅ **Graceful Degradation:** Plugin não carrega mas não quebra o site

---

## Testes Recomendados

### Teste 1: apollo-social sem WordPress
1. Executar loader diretamente (sem WordPress)
2. Verificar que não causa fatal error
3. Verificar mensagem de erro apropriada

### Teste 2: apollo-events-manager sem apollo-social
1. Desativar apollo-social
2. Tentar ativar apollo-events-manager
3. Verificar que:
   - Plugin não ativa
   - Aviso aparece no admin
   - Site não quebra

### Teste 3: Todos os plugins ativos
1. Ativar apollo-social primeiro
2. Ativar apollo-events-manager
3. Ativar apollo-rio
4. Verificar que todos carregam corretamente

### Teste 4: Ordem de ativação
1. Ativar apollo-events-manager ANTES de apollo-social
2. Verificar que aviso aparece
3. Ativar apollo-social
4. Verificar que apollo-events-manager carrega automaticamente

---

## Arquivos Modificados

1. ✅ `apollo-social/apollo-social-loader.php` (completamente reescrito)
2. ✅ `apollo-events-manager/apollo-events-manager-loader.php` (completamente reescrito)
3. ✅ `apollo-rio/apollo-rio.php` (nenhuma mudança necessária)

---

## Status

✅ **DEPENDÊNCIAS CORRIGIDAS COM SUCESSO**

- Verificações defensivas implementadas
- Ordem de carregamento garantida
- Múltiplos métodos de verificação
- Avisos amigáveis no admin
- Código mais robusto e resiliente

---

**Data:** 15/01/2025  
**Próximo Passo:** Testar em ambiente local e verificar logs

