# 🔧 Como Forçar Cursor a Reconhecer Project Rules

**Problema:** "NO PROJECT RULES YET" e botão não funciona

---

## ✅ ARQUIVOS CRIADOS

Criei múltiplos arquivos em diferentes formatos para garantir reconhecimento:

1. ✅ `.cursorrules` (raiz do plugin) - Formato padrão
2. ✅ `.cursor/rules.md` - Formato alternativo
3. ✅ `.cursorrules.md` - Formato alternativo 2
4. ✅ `.cursor/project-rules.md` - Formato alternativo 3
5. ✅ `.cursor/instructions.md` - Formato alternativo 4
6. ✅ `cursor.json` - Formato JSON

---

## 🔄 PASSOS PARA FORÇAR RECONHECIMENTO

### Método 1: Reiniciar Cursor

1. **Feche completamente o Cursor**
2. **Reabra o Cursor**
3. **Abra o projeto novamente**
4. **Verifique:** Settings → Project Rules

### Método 2: Recarregar Janela

1. **Abra Command Palette:** `Ctrl+Shift+P`
2. **Digite:** `Reload Window`
3. **Selecione:** "Developer: Reload Window"
4. **Verifique:** Settings → Project Rules

### Método 3: Verificar Localização do Arquivo

O arquivo `.cursorrules` deve estar na **raiz do workspace**:

```
C:\Users\rafae\Local Sites\1212\app\public\wp-content\plugins\apollo-events-manager\.cursorrules
```

**Se você abriu uma pasta diferente:**
- Feche Cursor
- Abra a pasta correta: `apollo-events-manager`
- Reabra Cursor

### Método 4: Copiar Conteúdo Manualmente

1. **Abra Settings:** `Ctrl+,`
2. **Busque:** "Project Rules" ou "Rules"
3. **Clique em:** "Edit Rules" ou "Add Rules"
4. **Cole o conteúdo:**

```
Este projeto é o plugin WordPress "apollo-events-manager", forkado de WP Event Manager.

Regras para qualquer alteração aqui:

- Trate o projeto como plugin de produção: não quebre hooks, filters ou shortcodes públicos existentes sem avisar.

- Preserve a estrutura geral do plugin (includes, templates, shortcodes, assets) e o padrão de nomes atual.

- Quando sugerir refactors grandes, divida em passos pequenos e aplicáveis, que caibam em um único commit.

- Respeite o autoload e a organização de classes já existentes; não crie frameworks paralelos.

- Evite dependências externas novas sempre que não forem estritamente necessárias.

- Gere código compatível com PHP 8.1+ e WordPress 6.x.
```

### Método 5: Via Command Palette

1. **Abra Command Palette:** `Ctrl+Shift+P`
2. **Digite:** `Cursor: Edit Project Rules`
3. **Se não aparecer:** Tente `Settings: Open Settings`
4. **Navegue até:** Project Rules

### Método 6: Verificar Workspace

**Se você usa workspace file (.code-workspace):**

1. Verifique se `.cursorrules` está na raiz do workspace
2. Ou adicione regras no arquivo `.code-workspace`:

```json
{
  "settings": {
    "cursor.projectRules": "Este projeto é o plugin WordPress \"apollo-events-manager\"..."
  }
}
```

---

## 🎯 VERIFICAR SE FUNCIONOU

### Teste 1: Verificar na UI

1. **Settings** (`Ctrl+,`)
2. **Buscar:** "rules" ou "project"
3. **Deve aparecer:** Suas regras listadas

### Teste 2: Testar no Composer

1. **Abra Composer:** `Ctrl+I`
2. **Digite:** "Refatore este código"
3. **Verifique:** Se as sugestões seguem as regras (não quebra hooks, etc.)

### Teste 3: Verificar Arquivo

1. **Abra:** `.cursorrules` na raiz
2. **Verifique:** Se conteúdo está correto
3. **Formato:** Deve ser texto simples, sem markdown complexo

---

## 🚨 SE AINDA NÃO FUNCIONAR

### Solução Alternativa: Usar .cursorignore + README

Crie arquivo `.cursorignore` e adicione regras no README principal.

### Solução Manual: Sempre Mencionar no Prompt

Sempre que usar Composer ou Chat, adicione no início do prompt:

```
CONTEXTO DO PROJETO:
Este é o plugin WordPress "apollo-events-manager", forkado de WP Event Manager.

REGRAS:
- Não quebrar hooks, filters ou shortcodes públicos
- Preservar estrutura do plugin
- Refactors em passos pequenos
- Respeitar autoload existente
- Evitar dependências novas
- Compatível PHP 8.1+ e WordPress 6.x

[seu prompt aqui]
```

---

## 📋 CHECKLIST DE VERIFICAÇÃO

- [ ] Arquivo `.cursorrules` existe na raiz do projeto
- [ ] Conteúdo do arquivo está correto
- [ ] Cursor foi reiniciado após criar arquivo
- [ ] Workspace está aberto na pasta correta
- [ ] Versão do Cursor é 2.0+ (verificar em Settings → About)
- [ ] Tentou recarregar janela (`Ctrl+Shift+P` → Reload Window)

---

## 💡 DICA FINAL

Se o botão ainda não funciona, **copie e cole manualmente** o conteúdo nas configurações:

1. `Ctrl+,` → Settings
2. Buscar "rules"
3. Clicar em "Edit" ou "Add"
4. Colar conteúdo do arquivo `.cursorrules`

---

**Status:** ✅ Múltiplos arquivos criados  
**Próximo Passo:** Reiniciar Cursor e verificar  
**Arquivo Principal:** `.cursorrules` na raiz do plugin

