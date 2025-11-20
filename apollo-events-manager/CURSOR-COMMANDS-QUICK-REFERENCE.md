# ⚡ Comandos Cursor - Referência Rápida
## PHP Inspect, Refactor Safe e PHPDoc

---

## 🎯 COMANDOS DISPONÍVEIS

### 1. `php-inspect`
**O que faz:** Inspeciona arquivo PHP como PhpStorm  
**Quando usar:** Antes de commit, revisão de código, encontrar bugs  
**Como usar:** `Ctrl+Shift+P` → `php-inspect`

### 2. `php-refactor-safe`
**O que faz:** Refatora código com foco em segurança e clareza  
**Quando usar:** Melhorar legibilidade, adicionar segurança, refatorar sem quebrar API  
**Como usar:** `Ctrl+Shift+P` → `php-refactor-safe`

### 3. `php-phpdoc`
**O que faz:** Gera ou melhora PHPDoc  
**Quando usar:** Documentar código, padronizar documentação  
**Como usar:** `Ctrl+Shift+P` → `php-phpdoc`

---

## 🚀 USO RÁPIDO

### Inspecionar Arquivo
```
1. Abrir arquivo PHP
2. Ctrl+Shift+P
3. php-inspect
4. Revisar problemas
```

### Refatorar com Segurança
```
1. Abrir arquivo PHP
2. Ctrl+Shift+P
3. php-refactor-safe
4. Revisar plano
5. Aplicar mudanças
```

### Gerar PHPDoc
```
1. Abrir arquivo PHP
2. Ctrl+Shift+P
3. php-phpdoc
4. Revisar documentação
5. Aplicar melhorias
```

---

## 📋 O QUE CADA COMANDO FAZ

### php-inspect
✅ Encontra problemas de tipo, null, lógica  
✅ Detecta vulnerabilidades de segurança  
✅ Identifica problemas de performance  
✅ Sugere correções mínimas (diff)

### php-refactor-safe
✅ Melhora legibilidade  
✅ Adiciona segurança  
✅ Ajusta type hints e PHPDoc  
✅ Preserva API pública (hooks, shortcodes)

### php-phpdoc
✅ Gera PHPDoc para classes/métodos públicos  
✅ Atualiza documentação existente  
✅ Garante tipos corretos  
✅ Mantém comentários importantes

---

## ⚠️ IMPORTANTE

Todos os comandos respeitam:
- ✅ Project Rules (`.cursorrules`)
- ✅ Estrutura do plugin
- ✅ Hooks/filters públicos
- ✅ Compatibilidade PHP 8.1+ e WordPress 6.x

---

**Atalho:** `Ctrl+Shift+P` → Digite nome do comando

