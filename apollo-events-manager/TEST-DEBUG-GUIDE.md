# 🔍 Guia de Debug dos Testes - Apollo Events Manager

## Problema: "Temporariamente indisponível"

Se você está vendo esta mensagem, significa que há um erro fatal ou o plugin não está sendo carregado corretamente.

## Solução Passo a Passo

### 1. Execute o Teste Simples Primeiro

Acesse:
```
http://localhost:10004/wp-content/plugins/apollo-events-manager/tests/simple-test.php
```

Este teste mostra **exatamente** onde está falhando:
- ✅ Carregamento do WordPress
- ✅ Carregamento do plugin
- ✅ Instanciação da classe
- ✅ Execução dos hooks
- ✅ Registro de CPTs e shortcodes

### 2. Verifique os Erros

O teste simples mostrará:
- **Erro fatal:** Mensagem, arquivo e linha exata
- **Erro de carregamento:** Caminho testado e motivo da falha
- **Erro de instanciação:** Detalhes do problema

### 3. Soluções Comuns

#### Erro: "Plugin não encontrado"
**Solução:** Verifique se o caminho do plugin está correto

#### Erro: "Erro fatal ao carregar plugin"
**Solução:** 
1. Verifique `wp-content/debug.log`
2. Verifique se há erros de sintaxe no plugin
3. Verifique se todas as dependências estão instaladas

#### Erro: "Classe não existe"
**Solução:**
1. Verifique se o arquivo `apollo-events-manager.php` está correto
2. Verifique se há erros de sintaxe que impedem a definição da classe

#### Erro: "CPTs não registrados"
**Solução:**
1. Execute o teste simples para ver onde está falhando
2. Verifique se `includes/post-types.php` existe
3. Verifique se há erros no arquivo de post types

## Debug Avançado

### Ativar Logs Detalhados

Adicione no início de `debug-test.php`:
```php
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/test-errors.log');
```

### Verificar Logs do WordPress

Verifique `wp-content/debug.log` para erros do WordPress.

### Verificar Logs do PHP

Verifique os logs do servidor PHP para erros fatais.

## Próximos Passos

1. ✅ Execute `simple-test.php` primeiro
2. ✅ Identifique onde está falhando
3. ✅ Corrija o erro específico
4. ✅ Execute novamente

---

**Última Atualização:** <?php echo date('d/m/Y'); ?>



