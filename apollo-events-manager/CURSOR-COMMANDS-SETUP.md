# 🔧 Configurar Comandos Customizados do Cursor
## PHP Inspect, Refactor Safe e PHPDoc

**Data:** 15/01/2025  
**Projeto:** Apollo Events Manager

---

## 📋 COMANDOS CRIADOS

Três comandos customizados foram configurados:

1. **`php-inspect`** - Inspetor de código estilo PhpStorm
2. **`php-refactor-safe`** - Refatoração segura
3. **`php-phpdoc`** - Gerar/ajustar PHPDoc

---

## 🚀 COMO ADICIONAR MANUALMENTE (Se necessário)

### Método 1: Via UI do Cursor (Recomendado)

1. **Abrir Command Palette:** `Ctrl+Shift+P`
2. **Digitar:** `Cursor: Add Command` ou `Add User Command`
3. **Selecionar:** "Add Command" ou "Add User Command"
4. **Preencher formulário** para cada comando:

#### Comando 1: php-inspect

**Name:** `php-inspect`

**Description:** `Revisar arquivo PHP atual como PhpStorm`

**Command text / Instructions:**
```
Você é um inspetor de código estilo PhpStorm especializado em PHP 8 e WordPress.

Tarefa:
1. Analise o arquivo atual em busca de:
   - Problemas de tipo, null, lógica, fluxo.
   - Problemas de segurança (XSS, SQL injection, uso inseguro de dados do usuário).
   - Problemas de performance óbvios.

2. Liste os problemas encontrados em tópicos numerados, com:
   - Linha aproximada.
   - Explicação breve.
   - Nível de severidade (warning, error, suggestion).

3. Só depois proponha um patch com as correções mínimas necessárias, em forma de diff.

4. Não reescreva o arquivo inteiro; apenas os trechos que precisam mudar.
```

#### Comando 2: php-refactor-safe

**Name:** `php-refactor-safe`

**Description:** `Refatorar o arquivo atual com foco em clareza e segurança`

**Command text / Instructions:**
```
Refatore o arquivo PHP atual com as seguintes prioridades:

1. Melhorar legibilidade (nomes claros, funções menores, menos duplicação).
2. Melhorar segurança (validação/sanitização de input, escape de output).
3. Adicionar ou ajustar type hints e PHPDocs para refletir o uso real, sem quebrar a API pública.

Passos:
- Explique rapidamente o plano de refatoração em 3–6 bullets.
- Mostre o diff completo das alterações.
- Aponte qualquer mudança que possa alterar comportamento externo (API, hooks, shortcodes).

Não introduza novas bibliotecas externas. Não mude estilo de código (indentação, aspas etc.) fora das linhas tocadas.
```

#### Comando 3: php-phpdoc

**Name:** `php-phpdoc`

**Description:** `Gerar ou melhorar PHPDoc no arquivo atual`

**Command text / Instructions:**
```
Analise o arquivo PHP atual e:

- Gere ou atualize PHPDocs para classes, métodos e funções públicas.
- Use descrições curtas e claras, em português.
- Garanta que os tipos dos parâmetros e retornos no PHPDoc batam com os type hints e com o uso real.
- Não apague comentários importantes já existentes; apenas complemente.

No final, liste rapidamente quais símbolos receberam novos PHPDocs.
```

---

## 📁 ARQUIVOS CRIADOS

Criei os seguintes arquivos de configuração:

1. ✅ `.cursor/commands.json` - Comandos do projeto
2. ✅ `.cursor/user-commands.json` - Comandos do usuário (global)

**Localização:**
- `apollo-events-manager/.cursor/commands.json`
- `apollo-events-manager/.cursor/user-commands.json`

---

## ✅ VERIFICAR SE FUNCIONOU

### Teste 1: Verificar Comandos Disponíveis

1. **Abrir Command Palette:** `Ctrl+Shift+P`
2. **Digitar:** `php-inspect` ou `php-refactor` ou `php-phpdoc`
3. **Deve aparecer:** Comandos customizados listados

### Teste 2: Usar Comando

1. **Abrir arquivo PHP** (ex: `apollo-events-manager.php`)
2. **Command Palette:** `Ctrl+Shift+P`
3. **Selecionar:** `php-inspect`
4. **Verificar:** Se análise é executada

### Teste 3: Via Chat/Composer

1. **Abrir Chat:** `Ctrl+L`
2. **Digitar:** `@php-inspect` ou `/php-inspect`
3. **Verificar:** Se comando é reconhecido

---

## 🔄 SE NÃO FUNCIONAR AUTOMATICAMENTE

### Opção 1: Reiniciar Cursor

1. Feche completamente o Cursor
2. Reabra o Cursor
3. Abra o projeto novamente
4. Teste os comandos

### Opção 2: Recarregar Janela

1. `Ctrl+Shift+P`
2. `Reload Window`
3. Teste os comandos novamente

### Opção 3: Adicionar Manualmente via UI

Siga o **Método 1** acima para adicionar cada comando manualmente através da interface do Cursor.

---

## 💡 COMO USAR OS COMANDOS

### php-inspect

**Quando usar:**
- Antes de fazer commit
- Ao revisar código
- Para encontrar bugs potenciais

**Como usar:**
1. Abra arquivo PHP
2. `Ctrl+Shift+P` → `php-inspect`
3. Revise problemas encontrados
4. Aplique correções sugeridas

### php-refactor-safe

**Quando usar:**
- Melhorar código legível
- Adicionar segurança
- Refatorar sem quebrar API

**Como usar:**
1. Abra arquivo PHP
2. `Ctrl+Shift+P` → `php-refactor-safe`
3. Revise plano de refatoração
4. Aplique mudanças sugeridas

### php-phpdoc

**Quando usar:**
- Documentar código novo
- Melhorar documentação existente
- Padronizar PHPDoc

**Como usar:**
1. Abra arquivo PHP
2. `Ctrl+Shift+P` → `php-phpdoc`
3. Revise PHPDocs gerados
4. Aplique melhorias

---

## 📝 EXEMPLOS DE USO

### Exemplo 1: Inspecionar arquivo

```
1. Abrir: apollo-events-manager.php
2. Ctrl+Shift+P
3. Selecionar: php-inspect
4. Aguardar análise
5. Revisar problemas encontrados
6. Aplicar correções
```

### Exemplo 2: Refatorar com segurança

```
1. Abrir: includes/admin-metaboxes.php
2. Ctrl+Shift+P
3. Selecionar: php-refactor-safe
4. Revisar plano de refatoração
5. Verificar se hooks/shortcodes são preservados
6. Aplicar mudanças
```

### Exemplo 3: Gerar PHPDoc

```
1. Abrir: includes/class-apollo-events-placeholders.php
2. Ctrl+Shift+P
3. Selecionar: php-phpdoc
4. Revisar PHPDocs gerados
5. Aplicar melhorias
```

---

## 🎯 INTEGRAÇÃO COM PROJECT RULES

Estes comandos respeitam automaticamente as **Project Rules** definidas em `.cursorrules`:

- ✅ Não quebram hooks/filters públicos
- ✅ Preservam estrutura do plugin
- ✅ Refatorações em passos pequenos
- ✅ Respeitam autoload existente
- ✅ Compatível PHP 8.1+ e WordPress 6.x

---

## 🔍 TROUBLESHOOTING

### Problema: Comandos não aparecem

**Solução:**
1. Verificar se arquivos `.cursor/commands.json` existem
2. Reiniciar Cursor
3. Recarregar janela
4. Adicionar manualmente via UI

### Problema: Comando não executa

**Solução:**
1. Verificar se arquivo PHP está aberto
2. Verificar sintaxe do JSON
3. Tentar comando via Chat (`@php-inspect`)

### Problema: Erro no JSON

**Solução:**
1. Validar JSON em: https://jsonlint.com/
2. Verificar escape de caracteres especiais
3. Usar formato correto (veja exemplos acima)

---

## 📚 REFERÊNCIAS

### Documentação Cursor:
- [Custom Commands](https://cursor.sh/docs/commands)
- [Command Palette](https://cursor.sh/docs/command-palette)

### PHP Standards:
- [PHP 8.1](https://www.php.net/releases/8.1/en.php)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)

---

## ✅ CHECKLIST

- [ ] Arquivos `.cursor/commands.json` criados
- [ ] Comandos adicionados manualmente (se necessário)
- [ ] Cursor reiniciado
- [ ] Comandos testados (`Ctrl+Shift+P`)
- [ ] Funcionalidade verificada em arquivo PHP

---

**Status:** ✅ Comandos Configurados  
**Próximo Passo:** Testar comandos em arquivo PHP  
**Arquivos:** `.cursor/commands.json` e `.cursor/user-commands.json`

---

**Criado por:** AI Assistant  
**Data:** 15/01/2025  
**Versão:** 1.0

