# 🔄 Como Atualizar Cursor no Windows
## Guia de Atualização para Cursor 2.0+

**Sistema:** Windows 10/11  
**Data:** 15/01/2025

---

## 🚀 MÉTODO 1: Atualização Automática (Recomendado)

### Passo 1: Verificar Atualizações no Cursor

1. **Abrir Cursor**
2. **Clicar no ícone de engrenagem** (⚙️) no canto inferior esquerdo
3. **Selecionar:** "Check for Updates" ou "Verificar Atualizações"
4. **Se houver atualização:**
   - Clique em "Download Update" ou "Baixar Atualização"
   - Aguarde o download
   - Clique em "Restart to Update" ou "Reiniciar para Atualizar"

### Atalho Rápido:
```
Ctrl+Shift+J → Updates → Check for Updates
```

---

## 📥 MÉTODO 2: Download Manual

### Passo 1: Baixar Versão Mais Recente

1. **Visite:** https://cursor.sh/downloads
2. **Baixe:** Versão Windows (`.exe`)
3. **Execute:** O instalador baixado
4. **Siga:** Instruções na tela

### Passo 2: Instalar

- O instalador substitui a versão antiga automaticamente
- Suas configurações são preservadas
- Não precisa desinstalar versão anterior

---

## ⚙️ MÉTODO 3: Via Settings (Configurações)

### Passo 1: Abrir Settings

```
Atalho: Ctrl+, (vírgula)
Ou: File → Preferences → Settings
```

### Passo 2: Verificar Versão Atual

1. Vá para **"About"** ou **"Sobre"**
2. Veja a versão atual
3. Compare com versão mais recente em: https://cursor.sh/changelog

### Passo 3: Atualizar

1. Em Settings, procure por **"Updates"**
2. Selecione **"Check for Updates"**
3. Siga instruções na tela

---

## 🔧 MÉTODO 4: Via Command Palette

### Passo 1: Abrir Command Palette

```
Atalho: Ctrl+Shift+P
```

### Passo 2: Buscar Comando

1. Digite: `update`
2. Selecione: **"Check for Updates"** ou **"Verificar Atualizações"**
3. Siga instruções na tela

---

## 🆕 MÉTODO 5: Early Access (Beta Features)

### Para Acessar Features Experimentais:

1. **Abrir Settings:** `Ctrl+,`
2. **Navegar para:** "Beta" ou "Experimental"
3. **Selecionar:** "Early Access" como canal de atualização
4. **Aceitar:** Termos de beta testing

### Benefícios:
- ✅ Acesso a features antes do lançamento oficial
- ✅ Testar novas funcionalidades
- ✅ Contribuir com feedback

### Cuidados:
- ⚠️ Pode ter bugs
- ⚠️ Pode ser menos estável
- ⚠️ Use apenas se confortável com versões beta

---

## ✅ VERIFICAR VERSÃO INSTALADA

### Método 1: Via Settings

1. `Ctrl+,` → About
2. Ver versão (ex: 2.0.5)

### Método 2: Via Menu

1. **Help** → **About Cursor**
2. Ver versão na janela

### Método 3: Via Command Line (PowerShell)

```powershell
# Verificar versão instalada
Get-ItemProperty "HKLM:\Software\Microsoft\Windows\CurrentVersion\Uninstall\*" | 
    Where-Object { $_.DisplayName -like "*Cursor*" } | 
    Select-Object DisplayName, DisplayVersion
```

---

## 🔍 VERIFICAR SE ESTÁ ATUALIZADO

### Comparar Versões:

1. **Versão Instalada:** Settings → About
2. **Versão Mais Recente:** https://cursor.sh/changelog
3. **Se diferente:** Atualize usando métodos acima

---

## 🚨 SOLUÇÃO DE PROBLEMAS

### Problema 1: Atualização Não Aparece

**Solução:**
- Verifique conexão com internet
- Tente download manual
- Reinicie Cursor

### Problema 2: Erro Durante Instalação

**Solução:**
- Feche todas instâncias do Cursor
- Execute instalador como Administrador
- Desinstale versão antiga primeiro (se necessário)

### Problema 3: Configurações Perdidas

**Solução:**
- Configurações geralmente são preservadas
- Backup manual: `%APPDATA%\Cursor\User\settings.json`
- Restaure se necessário

### Problema 4: Extensões Quebradas

**Solução:**
- Algumas extensões podem precisar atualização
- Vá para Extensions (`Ctrl+Shift+X`)
- Atualize extensões manualmente

---

## 📋 CHECKLIST DE ATUALIZAÇÃO

Antes de atualizar:
- [ ] Salvar todos arquivos abertos
- [ ] Fazer commit de mudanças no Git (se aplicável)
- [ ] Fechar todas instâncias do Cursor
- [ ] Verificar espaço em disco (pelo menos 500MB)

Durante atualização:
- [ ] Não fechar instalador
- [ ] Aguardar conclusão
- [ ] Não desligar computador

Após atualização:
- [ ] Verificar versão instalada
- [ ] Testar funcionalidades principais
- [ ] Verificar extensões
- [ ] Verificar configurações

---

## 🎯 ATUALIZAÇÃO AUTOMÁTICA

### Habilitar Atualizações Automáticas:

1. **Settings** (`Ctrl+,`)
2. **Buscar:** "update"
3. **Habilitar:** "Automatically Check for Updates"
4. **Escolher:** Frequência (diária, semanal)

### Benefícios:
- ✅ Sempre atualizado
- ✅ Não precisa verificar manualmente
- ✅ Acesso rápido a novas features

---

## 📊 COMPARAR VERSÕES

### Versão Atual vs Mais Recente:

**Verificar:**
- Versão instalada: Settings → About
- Versão mais recente: https://cursor.sh/changelog

**Se versão instalada < versão mais recente:**
→ Atualize usando métodos acima

---

## 🚀 ATUALIZAÇÃO RÁPIDA (TL;DR)

### Método Mais Rápido:

1. **Abrir Cursor**
2. **Pressionar:** `Ctrl+Shift+J`
3. **Clicar:** "Check for Updates"
4. **Seguir:** Instruções na tela

**Ou:**

1. **Visitar:** https://cursor.sh/downloads
2. **Baixar:** Versão Windows
3. **Instalar:** Substitui versão antiga automaticamente

---

## 💡 DICAS

### 1. Manter Sempre Atualizado
- Habilite atualizações automáticas
- Verifique mensalmente se necessário

### 2. Backup de Configurações
- Antes de atualizar, faça backup de:
  - `%APPDATA%\Cursor\User\settings.json`
  - `%APPDATA%\Cursor\User\keybindings.json`

### 3. Testar Após Atualização
- Teste funcionalidades principais
- Verifique extensões
- Valide configurações

### 4. Early Access
- Use apenas se confortável com beta
- Reporte bugs encontrados
- Aproveite features experimentais

---

## 📚 RECURSOS ADICIONAIS

### Links Úteis:
- **Downloads:** https://cursor.sh/downloads
- **Changelog:** https://cursor.sh/changelog
- **Documentação:** https://cursor.sh/docs
- **Community:** https://forum.cursor.com/

### Suporte:
- **Issues:** https://github.com/getcursor/cursor/issues
- **Discord:** Comunidade Cursor
- **Email:** support@cursor.sh

---

## ✅ RESUMO RÁPIDO

**Método Mais Fácil:**
1. `Ctrl+Shift+J` → Check for Updates
2. Seguir instruções

**Método Alternativo:**
1. https://cursor.sh/downloads
2. Baixar e instalar

**Verificar Versão:**
- Settings → About
- Comparar com changelog

---

**Status:** ✅ Guia Completo Criado  
**Sistema:** Windows 10/11  
**Última Atualização:** 15/01/2025

---

**Criado por:** AI Assistant  
**Para:** Projeto Apollo Events Manager

