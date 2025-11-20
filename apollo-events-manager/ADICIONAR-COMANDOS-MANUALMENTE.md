# 📝 Adicionar Comandos Cursor Manualmente
## Passo a Passo Completo

**Se os arquivos JSON não forem reconhecidos automaticamente, siga este guia.**

---

## 🎯 COMANDO 1: php-inspect

### Passo 1: Abrir Command Palette
```
Ctrl+Shift+P
```

### Passo 2: Buscar Comando
```
Digite: "Add Command" ou "Add User Command"
Selecione: "Cursor: Add Command" ou "Add User Command"
```

### Passo 3: Preencher Formulário

**Name:**
```
php-inspect
```

**Description:**
```
Revisar arquivo PHP atual como PhpStorm
```

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

### Passo 4: Salvar
- Clique em "Save" ou "Add"
- Comando será salvo e disponível

---

## 🎯 COMANDO 2: php-refactor-safe

### Passo 1: Abrir Command Palette
```
Ctrl+Shift+P
```

### Passo 2: Buscar Comando
```
Digite: "Add Command" ou "Add User Command"
Selecione: "Cursor: Add Command" ou "Add User Command"
```

### Passo 3: Preencher Formulário

**Name:**
```
php-refactor-safe
```

**Description:**
```
Refatorar o arquivo atual com foco em clareza e segurança
```

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

### Passo 4: Salvar
- Clique em "Save" ou "Add"
- Comando será salvo e disponível

---

## 🎯 COMANDO 3: php-phpdoc

### Passo 1: Abrir Command Palette
```
Ctrl+Shift+P
```

### Passo 2: Buscar Comando
```
Digite: "Add Command" ou "Add User Command"
Selecione: "Cursor: Add Command" ou "Add User Command"
```

### Passo 3: Preencher Formulário

**Name:**
```
php-phpdoc
```

**Description:**
```
Gerar ou melhorar PHPDoc no arquivo atual
```

**Command text / Instructions:**
```
Analise o arquivo PHP atual e:

- Gere ou atualize PHPDocs para classes, métodos e funções públicas.
- Use descrições curtas e claras, em português.
- Garanta que os tipos dos parâmetros e retornos no PHPDoc batam com os type hints e com o uso real.
- Não apague comentários importantes já existentes; apenas complemente.

No final, liste rapidamente quais símbolos receberam novos PHPDocs.
```

### Passo 4: Salvar
- Clique em "Save" ou "Add"
- Comando será salvo e disponível

---

## ✅ VERIFICAR SE FUNCIONOU

### Teste 1: Listar Comandos
1. `Ctrl+Shift+P`
2. Digite: `php-inspect` ou `php-refactor` ou `php-phpdoc`
3. Deve aparecer na lista

### Teste 2: Executar Comando
1. Abra arquivo PHP (ex: `apollo-events-manager.php`)
2. `Ctrl+Shift+P`
3. Selecione: `php-inspect`
4. Verifique se análise é executada

### Teste 3: Via Chat
1. Abra Chat: `Ctrl+L`
2. Digite: `@php-inspect` ou `/php-inspect`
3. Verifique se comando é reconhecido

---

## 🔄 ALTERNATIVA: Via Settings

Se o Command Palette não funcionar:

1. **Abrir Settings:** `Ctrl+,`
2. **Buscar:** "commands" ou "custom commands"
3. **Clicar em:** "Edit Commands" ou "User Commands"
4. **Adicionar** cada comando manualmente
5. **Salvar** configurações

---

## 📋 CHECKLIST

- [ ] Comando 1 (php-inspect) adicionado
- [ ] Comando 2 (php-refactor-safe) adicionado
- [ ] Comando 3 (php-phpdoc) adicionado
- [ ] Comandos aparecem em `Ctrl+Shift+P`
- [ ] Comandos funcionam corretamente

---

## 💡 DICA

**Para usar rapidamente:**
- `Ctrl+Shift+P` → Digite nome do comando
- Ou use no Chat: `@php-inspect`

---

**Status:** ✅ Guia Completo Criado  
**Próximo Passo:** Adicionar comandos manualmente seguindo este guia

