# 🚀 Como Usar Composer + ChatGPT Codex Simultaneamente
## Guia Prático: Duas Ferramentas ao Mesmo Tempo - Cursor 2.0+

**Data:** 15/01/2025  
**Versão Cursor:** 2.0+ (Latest)  
**Ferramentas:** Cursor Composer + Chat (GPT-4o/Codex)

---

## 🎯 OBJETIVO

Usar **Composer** (para refatorações grandes) e **ChatGPT Codex** (para código específico) **ao mesmo tempo** para máxima produtividade.

---

## 📋 MÉTODO 1: Composer + Chat Separados

### Passo 1: Abrir Composer
```
Atalho: Ctrl+I (Windows) ou Cmd+I (Mac)
Ou: Clique no ícone Composer na barra lateral
```

### Passo 2: Abrir Chat (Codex)
```
Atalho: Ctrl+L (Windows) ou Cmd+L (Mac)
Ou: Clique no ícone Chat na barra lateral
```

### Passo 3: Usar Ambos Simultaneamente

**Composer (Ctrl+I):**
- Use para tarefas grandes e complexas
- Refatorações de múltiplos arquivos
- Planejamento e arquitetura

**Chat Codex (Ctrl+L):**
- Use para código específico
- Perguntas rápidas
- Debugging em tempo real

---

## 💡 EXEMPLO PRÁTICO: Trabalhando com Ambos

### Cenário: Refatorar Event Card

#### 1. No Composer (Ctrl+I):
```
Refatore templates/event-card.php para usar ShadCN Card:

Plano completo:
- Analisar código atual
- Criar componente ShadCN equivalente
- Integrar com WordPress
- Adicionar animações Motion
```

#### 2. No Chat Codex (Ctrl+L) - Enquanto Composer trabalha:
```
Como criar componente ShadCN Card em React?
Mostre exemplo com TypeScript.
```

#### 3. Copiar resultado do Codex para Composer:
```
@GPT-4-Codex Use este componente ShadCN Card como base:
[cole o código do Codex aqui]
```

---

## 🔄 WORKFLOW RECOMENDADO

### Fluxo de Trabalho:

```
1. COMPOSER (Ctrl+I)
   ↓
   Define tarefa grande
   ↓
2. CHAT CODEX (Ctrl+L)
   ↓
   Pergunta específica sobre implementação
   ↓
3. COMPOSER (Ctrl+I)
   ↓
   Usa resposta do Codex para continuar
   ↓
4. Repetir conforme necessário
```

---

## 🎨 MÉTODO 2: Composer com Codex Integrado

### No Composer, especifique Codex diretamente:

```
@GPT-4-Codex Crie componente ShadCN Card
@Claude-Sonnet Revise e integre no WordPress
```

### Ou use tags:

```
[Codex] Implemente função JavaScript para filtrar eventos
[Sonnet] Integre com WordPress AJAX
```

---

## 📱 LAYOUT IDEAL: Duas Janelas

### Configuração Recomendada:

```
┌─────────────────────────────────────┐
│         COMPOSER (Ctrl+I)          │
│  Tarefas grandes, refatorações     │
│  Planejamento, arquitetura         │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│      CHAT CODEX (Ctrl+L)            │
│  Código específico, perguntas       │
│  Debugging, exemplos rápidos        │
└─────────────────────────────────────┘
```

### Como Organizar:

1. **Divida tela** (Windows: `Win + ←` ou `Win + →`)
2. **Composer à esquerda** (trabalho principal)
3. **Chat Codex à direita** (consultas rápidas)

---

## 🚀 EXEMPLO COMPLETO: Refatoração Apollo

### Passo 1: Abrir Composer (`Ctrl+I`)

**Prompt no Composer:**
```
Refatore templates/event-card.php completamente:

Objetivo: Usar ShadCN Card + Tailwind + Framer Motion
Arquivo: templates/event-card.php
Referência: https://codepen.io/Rafael-Valle-the-looper/pen/raxqVGR

Plano:
1. Analisar código atual
2. Criar componente ShadCN
3. Adicionar animações Motion
4. Integrar WordPress
```

### Passo 2: Abrir Chat Codex (`Ctrl+L`) - Em outra janela

**Pergunta no Chat:**
```
Como criar componente ShadCN Card em React com TypeScript?
Preciso de exemplo completo com props.
```

### Passo 3: Usar Resposta do Codex no Composer

**Voltar ao Composer e adicionar:**
```
@GPT-4-Codex Use este exemplo como base:
[cole código do Codex]

Agora adapte para:
- WordPress integration
- Props: eventId, title, date, banner
- Funcionalidade de favoritos
```

### Passo 4: Continuar no Composer

**Composer continua automaticamente** usando o código do Codex como referência.

---

## ⚡ ATALHOS RÁPIDOS (Cursor 2.0+)

### Composer
- **Abrir:** `Ctrl+I` / `Cmd+I`
- **Fechar:** `Esc`
- **Enviar:** `Ctrl+Enter` / `Cmd+Enter`
- **Aceitar mudanças:** `Ctrl+Shift+Enter` / `Cmd+Shift+Enter`
- **Rejeitar mudanças:** `Esc`
- **Aplicar parcialmente:** Selecione código e `Ctrl+Enter`

### Chat (GPT-4o/Codex)
- **Abrir:** `Ctrl+L` / `Cmd+L`
- **Fechar:** `Esc`
- **Enviar:** `Enter`
- **Nova conversa:** `Ctrl+K` / `Cmd+K`
- **Limpar histórico:** `Ctrl+Shift+K` / `Cmd+Shift+K`
- **Copiar resposta:** `Ctrl+C` na resposta

### Alternar Entre Eles (Novo Cursor 2.0+)
- **Composer:** `Ctrl+I`
- **Chat:** `Ctrl+L`
- **Alternar rapidamente:** Use os atalhos
- **Dividir tela:** `Ctrl+\` (abre ambos lado a lado)
- **Foco rápido:** `Ctrl+Shift+P` → "Focus Composer" ou "Focus Chat"

---

## 🎯 QUANDO USAR CADA UM

### Use COMPOSER quando:
- ✅ Refatoração de múltiplos arquivos
- ✅ Tarefas grandes e complexas
- ✅ Precisa de planejamento detalhado
- ✅ Quer múltiplos modelos colaborando
- ✅ Mudanças estruturais no código

### Use CHAT (GPT-4o/Codex) quando:
- ✅ Pergunta rápida sobre código
- ✅ Precisa de exemplo específico
- ✅ Debugging em tempo real
- ✅ Consulta sobre biblioteca/framework
- ✅ Código isolado e simples
- ✅ Explicações e documentação
- ✅ Ideias rápidas e brainstorming

### Novos Recursos Chat Cursor 2.0+:
- ✅ **Contexto de arquivos abertos** automaticamente
- ✅ **Sugestões de código** inline enquanto digita
- ✅ **Histórico melhorado** com busca
- ✅ **Múltiplas conversas** simultâneas (abas)

---

## 💻 EXEMPLO PRÁTICO: Setup Tailwind

### No Composer (`Ctrl+I`):
```
Configure Tailwind CSS no projeto Apollo:

Tarefas:
1. Criar tailwind.config.js
2. Configurar build tools
3. Integrar com WordPress
4. Criar componentes base
```

### No Chat Codex (`Ctrl+L`) - Simultaneamente:
```
Qual é a melhor configuração de tailwind.config.js 
para WordPress? Mostre exemplo completo.
```

### Resultado:
- **Composer** trabalha no plano geral
- **Codex** fornece código específico
- **Você** combina ambos para resultado final

---

## 🔧 DICAS AVANÇADAS (Cursor 2.0+)

### 1. Copiar Entre Ferramentas (Melhorado)

**Do Chat para Composer:**
1. Selecione código no Chat
2. `Ctrl+C` para copiar
3. Vá para Composer (`Ctrl+I`)
4. `Ctrl+V` para colar
5. Adicione contexto: `@GPT-4o Use este código como base:`

**Novo: Drag & Drop (Cursor 2.0+)**
- Arraste código do Chat diretamente para o Composer
- Mantém formatação e contexto

### 2. Referenciar Conversas (Novo)

**No Composer, referencie Chat:**
```
@GPT-4o Baseado na conversa anterior sobre ShadCN,
implemente componente Button completo.
```

**Novo: Link Direto (Cursor 2.0+)**
- Composer pode acessar histórico do Chat automaticamente
- Use `@previous` para referenciar última conversa

### 3. Usar Histórico Melhorado

- **Composer:** Mantém contexto da sessão + histórico de projetos
- **Chat:** Mantém histórico de conversas com busca
- **Ambos:** Sincronizam contexto automaticamente
- **Novo:** Histórico compartilhado entre sessões

### 4. Novos Recursos Cursor 2.0+

**Composer:**
- ✅ Preview de mudanças antes de aplicar
- ✅ Aplicar mudanças parcialmente (selecionar código)
- ✅ Sugestões automáticas baseadas em contexto
- ✅ Integração com Git (sugere commits)

**Chat:**
- ✅ Múltiplas abas de conversa
- ✅ Busca no histórico
- ✅ Exportar conversas
- ✅ Compartilhar conversas com equipe

---

## 📊 COMPARAÇÃO RÁPIDA

| Recurso | Composer | Chat Codex |
|---------|----------|------------|
| **Uso** | Tarefas grandes | Perguntas rápidas |
| **Múltiplos arquivos** | ✅ Sim | ❌ Limitado |
| **Multi-model** | ✅ Sim | ❌ Não |
| **Velocidade** | Mais lento | Mais rápido |
| **Contexto** | Muito contexto | Contexto limitado |
| **Ideal para** | Refatorações | Código específico |

---

## 🎓 WORKFLOW RECOMENDADO COMPLETO

### Para Refatoração Grande (ex: Apollo):

```
1. COMPOSER (Ctrl+I)
   └─> Define plano completo
   
2. CHAT CODEX (Ctrl+L) - Em paralelo
   └─> Pergunta sobre implementação específica
   
3. COMPOSER (Ctrl+I)
   └─> Usa resposta do Codex
   └─> Continua refatoração
   
4. CHAT CODEX (Ctrl+L) - Quando necessário
   └─> Debugging rápido
   └─> Consultas pontuais
   
5. COMPOSER (Ctrl+I)
   └─> Finaliza e revisa tudo
```

---

## ✅ CHECKLIST: Usando Ambos

Antes de começar:
- [ ] Composer aberto (`Ctrl+I`)
- [ ] Chat Codex aberto (`Ctrl+L`)
- [ ] Janelas organizadas (dividir tela)
- [ ] Objetivo claro definido
- [ ] Arquivos relevantes abertos

Durante trabalho:
- [ ] Composer para tarefas grandes
- [ ] Codex para código específico
- [ ] Copiar código entre ferramentas
- [ ] Manter contexto em ambos

---

## 🚀 EXEMPLO FINAL: Refatoração Completa

### Setup Inicial:

1. **Abra Composer:** `Ctrl+I`
2. **Abra Chat Codex:** `Ctrl+L` (em outra janela)
3. **Divida tela:** `Win + ←` e `Win + →`

### No Composer:
```
Refatore Apollo Events Manager para ShadCN + Tailwind + Motion:

Arquivos principais:
- templates/event-card.php
- templates/portal-discover.php
- templates/single-event-standalone.php

Plano completo de migração.
```

### No Chat Codex (simultaneamente):
```
Como configurar Tailwind CSS com Vite para WordPress?
Preciso de exemplo completo de configuração.
```

### Continuar:
- **Composer** trabalha no plano geral
- **Codex** fornece configurações específicas
- **Você** combina ambos para implementar

---

## 💡 DICA PRO

**Use Composer como "orquestrador" e Codex como "especialista":**

```
COMPOSER: "Preciso criar componente Button"
         ↓
CODEX:   "Aqui está exemplo completo ShadCN Button"
         ↓
COMPOSER: "Agora integre este Button no WordPress"
```

---

## 🎯 RESUMO RÁPIDO

1. **Composer:** `Ctrl+I` - Tarefas grandes
2. **Chat Codex:** `Ctrl+L` - Código específico
3. **Use ambos:** Simultaneamente
4. **Copie entre eles:** Quando necessário
5. **Organize janelas:** Divida tela

---

**Status:** ✅ Guia Prático Criado  
**Próximo Passo:** Testar workflow com projeto Apollo  
**Dúvidas?** Consulte exemplos acima

---

**Criado por:** AI Assistant  
**Data:** 15/01/2025  
**Versão:** 2.0 (Atualizado para Cursor 2.0+)  
**Última Atualização:** 15/01/2025

