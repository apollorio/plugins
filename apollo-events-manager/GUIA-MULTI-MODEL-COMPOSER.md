# 🚀 Guia Completo: Multi-Model Composer + ChatGPT Codex
## Como Usar Múltiplos Modelos no Cursor 2.0+ Composer

**Data:** 15/01/2025  
**Versão Cursor:** 2.0+ (Latest)  
**Ferramenta:** Cursor Composer (Multi-Model Mode)

---

## 🎯 O QUE É COMPOSER MULTI-MODEL?

O **Composer** no Cursor permite usar múltiplos modelos de IA simultaneamente para trabalhar em tarefas complexas. Cada modelo pode focar em sua especialidade, colaborando para resultados melhores.

---

## 🔧 COMO ATIVAR E USAR

### 1. **Abrir Composer**

**Atalhos:**
- `Ctrl+I` (Windows/Linux)
- `Cmd+I` (Mac)
- Ou clique no ícone de Composer na barra lateral

### 2. **Selecionar Modo Multi-Model (Cursor 2.0+)**

No Composer 2.0+, você tem opções melhoradas:

**Opção A: Multi-Model Automático (Recomendado)**
- Composer detecta automaticamente quando precisa de múltiplos modelos
- Colaboração inteligente entre modelos
- Ativado por padrão em tarefas complexas

**Opção B: Seleção Manual de Modelos**
1. Abra Composer (`Ctrl+I`)
2. Clique no seletor de modelo (canto superior direito)
3. Selecione "Use Multiple Models"
4. Escolha modelos específicos:
   - Claude 3.5 Sonnet (planejamento)
   - GPT-4o (implementação)
   - Claude 3 Opus (integração WordPress)

**Opção C: Especificar no Prompt**
```
@Claude-3.5-Sonnet Planeje a refatoração
@GPT-4o Implemente os componentes
@Claude-3-Opus Integre com WordPress
```

---

## 🤖 MODELOS DISPONÍVEIS NO CURSOR 2.0+

### Modelos Principais (Atualizados 2025):

1. **Claude 3.5 Sonnet** (Anthropic) ⭐ RECOMENDADO
   - Melhor para: Planejamento, arquitetura, code review
   - Versão mais recente e poderosa
   - Excelente para refatorações complexas
   
2. **Claude 3 Opus** (Anthropic)
   - Melhor para: Código complexo, integrações WordPress
   - Máxima qualidade de código
   
3. **GPT-4o** (OpenAI) ⭐ NOVO
   - Melhor para: Implementação rápida, bibliotecas modernas
   - Versão otimizada e mais rápida
   - Excelente para ShadCN, Tailwind, Motion
   
4. **GPT-4 Turbo** (OpenAI)
   - Melhor para: Implementação rápida, bibliotecas modernas
   - Versão anterior ainda disponível
   
5. **GPT-4 Codex** (OpenAI)
   - Melhor para: Geração de código, autocomplete inteligente
   - Especializado em código

### Novos Recursos Cursor 2.0+:
- ✅ **Composer Multi-Model** melhorado (colaboração automática)
- ✅ **Chat melhorado** com contexto de arquivos
- ✅ **Seleção de modelos** mais intuitiva
- ✅ **Performance otimizada** para múltiplos modelos
- ✅ **Integração melhor** entre Composer e Chat

---

## 💡 COMO USAR MULTI-MODEL COMPOSER

### Método 1: Especificar Modelos no Prompt (Cursor 2.0+)

**Sintaxe Atualizada:**
```
@Claude-3.5-Sonnet Planeje a refatoração completa
@GPT-4o Implemente o componente Button do ShadCN
@Claude-3-Opus Integre com WordPress e valide padrões
```

**Ou use nomes simplificados:**
```
@Sonnet Planeje a refatoração
@GPT4o Implemente componentes
@Opus Integre WordPress
```

### Método 2: Usar Tags Especiais

```
[Codex] Crie a função JavaScript para filtrar eventos
[Sonnet] Revise e otimize a performance
[GPT-4] Adicione testes unitários
```

### Método 3: Dividir Tarefas por Modelo

```
Tarefa 1: @GPT-4-Codex - Criar componente React
Tarefa 2: @Claude-Sonnet - Integrar com WordPress
Tarefa 3: @GPT-4-Turbo - Adicionar animações
```

---

## 🎯 EXEMPLOS PRÁTICOS PARA APOLLO PROJECT

### Exemplo 1: Refatorar Event Card

**Prompt no Composer:**
```
Refatore o componente event-card.php para usar ShadCN + Tailwind + Motion:

1. @GPT-4-Codex: Crie o componente React/JS moderno com ShadCN Card
2. @Claude-Sonnet: Integre com WordPress template PHP mantendo compatibilidade
3. @GPT-4-Turbo: Adicione animações de entrada com Framer Motion
4. @Claude-Sonnet: Revise código final e valide padrões
```

### Exemplo 2: Setup Inicial Tailwind

**Prompt no Composer:**
```
Configure Tailwind CSS + ShadCN no projeto Apollo:

1. @GPT-4-Codex: Crie tailwind.config.js com tema customizado
2. @GPT-4-Turbo: Configure build tools (Vite/Webpack)
3. @Claude-Sonnet: Integre com WordPress wp_enqueue_script
4. @GPT-4-Codex: Crie componentes base ShadCN (Button, Card)
```

### Exemplo 3: Refatorar Portal Discover

**Prompt no Composer:**
```
Refatore portal-discover.php completamente:

@GPT-4-Codex: 
- Crie componentes ShadCN isolados (Card, Badge, Input)
- Implemente filtros com Tailwind classes

@Claude-Sonnet:
- Integre componentes no template PHP
- Mantenha AJAX handlers funcionando
- Valide performance e segurança

@GPT-4-Turbo:
- Adicione animações Motion para transições
- Otimize bundle size
```

---

## 📋 ESTRATÉGIAS DE PROMPTING

### ✅ BOM: Prompts Específicos e Divididos

```
Refatore event-card.php:

Fase 1 - @GPT-4-Codex:
- Criar componente React EventCard.tsx
- Usar ShadCN Card component
- Props: eventId, title, date, banner

Fase 2 - @Claude-Sonnet:
- Criar wrapper PHP que renderiza componente
- Integrar com WordPress get_post_meta
- Manter backward compatibility

Fase 3 - @GPT-4-Turbo:
- Adicionar animação de entrada (fadeIn + slideUp)
- Adicionar hover effect com scale
- Otimizar performance
```

### ❌ EVITAR: Prompts Vagos

```
Refatore tudo para usar Tailwind
```

**Por quê?** Muito vago, modelos não sabem por onde começar.

### ✅ BOM: Prompts com Contexto

```
No arquivo templates/event-card.php, refatore para usar ShadCN Card:

Contexto:
- Atualmente usa classes customizadas "apollo-card"
- Precisa manter funcionalidade de favoritos
- Deve funcionar com WordPress AJAX

@GPT-4-Codex: Crie componente ShadCN equivalente
@Claude-Sonnet: Integre mantendo funcionalidades existentes
```

---

## 🔄 WORKFLOW RECOMENDADO

### Passo 1: Planejamento (Claude Sonnet)
```
@Claude-Sonnet Analise o código atual e crie um plano detalhado 
de refatoração para usar ShadCN + Tailwind + Motion
```

### Passo 2: Implementação (GPT-4 Codex + GPT-4 Turbo)
```
@GPT-4-Codex Implemente os componentes base
@GPT-4-Turbo Adicione animações e otimizações
```

### Passo 3: Integração (Claude Sonnet)
```
@Claude-Sonnet Integre componentes no WordPress mantendo 
compatibilidade e performance
```

### Passo 4: Review (Todos)
```
@Claude-Sonnet Revise código final
@GPT-4-Codex Valide padrões de código
@GPT-4-Turbo Teste performance
```

---

## 🎨 EXEMPLO COMPLETO: Refatorar Event Modal

### Prompt Inicial no Composer:

```
Refatore o modal de eventos (single-event-standalone.php) 
para usar ShadCN Dialog + Framer Motion:

ARQUIVO ATUAL: templates/single-event-standalone.php
REFERÊNCIA: https://codepen.io/Rafael-Valle-the-looper/pen/EaPpjXP

TAREFAS:

1. @GPT-4-Codex:
   - Criar componente React EventModal.tsx
   - Usar ShadCN Dialog component
   - Props: isOpen, onClose, eventId
   - Carregar dados via WordPress AJAX

2. @GPT-4-Turbo:
   - Adicionar animações Motion:
     * Fade in do backdrop
     * Slide up do modal
     * Stagger animation para conteúdo
   - Otimizar performance (lazy load)

3. @Claude-Sonnet:
   - Criar handler PHP apollo_ajax_load_event_modal()
   - Integrar componente no template PHP
   - Manter compatibilidade com código existente
   - Validar segurança (nonces, sanitization)

4. @Claude-Sonnet:
   - Code review final
   - Validar padrões WordPress
   - Verificar acessibilidade
   - Documentar mudanças
```

---

## 💻 COMANDOS ÚTEIS NO COMPOSER 2.0+

### Especificar Arquivo Específico
```
@templates/event-card.php Refatore este arquivo
```

### Usar Contexto de Múltiplos Arquivos (Novo)
```
@templates/event-card.php @assets/js/apollo-events-portal.js 
Refatore ambos para usar ShadCN
```

### Selecionar Arquivos Visualmente (Cursor 2.0+)
- Clique em arquivos no explorador enquanto Composer está aberto
- Arquivos selecionados são automaticamente incluídos no contexto
- Use `Ctrl+Click` para múltiplos arquivos

### Combinar Modelos para Tarefa Específica
```
@Claude-3.5-Sonnet @GPT-4o Trabalhem juntos para criar 
componente Button com animações
```

### Pedir Revisão Específica
```
@Claude-3.5-Sonnet Revise este código focando em:
- Performance
- Segurança WordPress
- Acessibilidade
```

### Novos Comandos Cursor 2.0+:
```
# Incluir arquivo relacionado automaticamente
@related Refatore e todos os arquivos relacionados

# Usar contexto do projeto inteiro
@workspace Analise toda a estrutura do projeto

# Referenciar conversas anteriores
@previous Use a solução da conversa anterior sobre ShadCN
```

---

## 🚨 DICAS IMPORTANTES

### ✅ FAZER:

1. **Seja Específico**
   - Diga exatamente o que quer
   - Mencione arquivos específicos
   - Defina responsabilidades por modelo

2. **Forneça Contexto**
   - Mencione arquivos relacionados
   - Explique funcionalidades existentes
   - Cite referências (CodePens, docs)

3. **Divida em Etapas**
   - Não peça tudo de uma vez
   - Divida em fases lógicas
   - Revise entre etapas

4. **Use Modelos Apropriados**
   - Codex para código novo
   - Sonnet para planejamento/review
   - GPT-4 Turbo para implementação rápida

### ❌ EVITAR:

1. **Prompts Muito Longos**
   - Divida em múltiplos prompts
   - Foque em uma coisa por vez

2. **Mudanças Simultâneas**
   - Não refatore tudo de uma vez
   - Faça incrementalmente

3. **Ignorar Contexto**
   - Sempre forneça contexto
   - Mencione arquivos relacionados

---

## 🔍 VERIFICANDO SE MULTI-MODEL ESTÁ ATIVO (Cursor 2.0+)

### No Composer 2.0+:
1. Abra Composer (`Ctrl+I`)
2. Verifique indicadores:
   - **Seletor de modelo** no canto superior direito
   - **Badge "Multi-Model"** quando múltiplos modelos estão ativos
   - **Ícones de modelos** mostrando quais estão colaborando
   - **Status de colaboração** em tempo real

### Verificar Versão do Cursor:
1. Abra Settings: `Ctrl+,` (Windows) ou `Cmd+,` (Mac)
2. Vá para "About" ou "General"
3. Verifique versão (deve ser 2.0.0 ou superior)

### Atualizar para Cursor 2.0+:
1. **Atualização Automática:**
   - Settings → Check for Updates
   - Ou: `Ctrl+Shift+J` → Updates

2. **Download Manual:**
   - Visite: https://cursor.sh/downloads
   - Baixe versão mais recente

3. **Early Access (Beta):**
   - Settings → Beta → Enable Early Access
   - Acesso a features experimentais

---

## 📊 COMPARAÇÃO: Single vs Multi-Model

### Single Model (Um por vez)
```
✅ Mais rápido para tarefas simples
✅ Menos tokens usados
❌ Limitado a expertise de um modelo
```

### Multi-Model (Colaborativo)
```
✅ Melhor para tarefas complexas
✅ Combina forças de múltiplos modelos
✅ Resultados mais completos
❌ Usa mais tokens
❌ Pode ser mais lento
```

---

## 🎯 CASOS DE USO IDEIAIS

### ✅ Use Multi-Model Para:

1. **Refatorações Grandes**
   - Múltiplos arquivos
   - Múltiplas tecnologias
   - Requer planejamento + implementação

2. **Projetos Complexos**
   - WordPress + React
   - Múltiplas integrações
   - Requer diferentes expertises

3. **Code Review**
   - Implementação + Review
   - Validação de padrões
   - Otimização

### ❌ Use Single Model Para:

1. **Tarefas Simples**
   - Criar função isolada
   - Corrigir bug específico
   - Adicionar feature pequena

2. **Aprendizado**
   - Entender código
   - Explicar conceito
   - Documentar

---

## 🚀 EXEMPLO PRÁTICO COMPLETO

### Cenário: Refatorar Event Card com ShadCN + Motion

**Prompt no Composer (`Ctrl+I`):**

```
Refatore templates/event-card.php para usar ShadCN Card + Framer Motion:

CONTEXTO:
- Arquivo atual: templates/event-card.php
- Usa classes customizadas "apollo-card"
- Funcionalidade: favoritos, modal, filtros
- Referência: https://codepen.io/Rafael-Valle-the-looper/pen/raxqVGR

PLANO:

ETAPA 1 - @GPT-4-Codex:
Criar componente React EventCard.tsx:
- Usar ShadCN Card component
- Props: event (objeto com dados do evento)
- Manter funcionalidade de favoritos
- Integrar com sistema de modal existente

ETAPA 2 - @GPT-4-Turbo:
Adicionar animações Framer Motion:
- Animação de entrada: fadeIn + slideUp
- Hover effect: scale(1.02)
- Transição suave: duration 0.3s
- Stagger animation para lista de cards

ETAPA 3 - @Claude-Sonnet:
Integrar no WordPress:
- Criar wrapper PHP que renderiza componente
- Manter compatibilidade com AJAX handlers
- Integrar com get_post_meta para dados
- Validar segurança e performance

ETAPA 4 - @Claude-Sonnet:
Code review e validação:
- Verificar padrões WordPress
- Validar acessibilidade
- Testar performance
- Documentar mudanças
```

---

## 📝 CHECKLIST ANTES DE USAR MULTI-MODEL

- [ ] Definiu objetivo claro?
- [ ] Dividiu em etapas lógicas?
- [ ] Atribuiu modelos apropriados?
- [ ] Forneceu contexto suficiente?
- [ ] Mencionou arquivos específicos?
- [ ] Definiu responsabilidades por modelo?

---

## 🎓 RECURSOS ADICIONAIS

### Documentação Cursor:
- [Cursor Docs](https://cursor.sh/docs)
- [Composer Guide](https://cursor.sh/docs/composer)

### Modelos:
- [GPT-4 Codex](https://platform.openai.com/docs/models)
- [Claude Sonnet](https://www.anthropic.com/claude)

---

## ✅ RESUMO RÁPIDO

1. **Abra Composer:** `Ctrl+I`
2. **Ative Multi-Model** (se disponível)
3. **Especifique modelos:** `@GPT-4-Codex`, `@Claude-Sonnet`
4. **Divida tarefas** por especialidade
5. **Forneça contexto** completo
6. **Revise resultados** entre etapas

---

**Status:** ✅ Guia Completo Criado  
**Próximo Passo:** Testar Multi-Model Composer no projeto Apollo  
**Dúvidas?** Consulte exemplos práticos acima

---

**Criado por:** AI Assistant  
**Data:** 15/01/2025  
**Versão:** 2.0 (Atualizado para Cursor 2.0+)  
**Última Atualização:** 15/01/2025

