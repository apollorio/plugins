# 🔍 Análise: ShadCN/Tailwind nos Templates - 15/01/2025

## 📊 Status Atual

### ❌ PROBLEMA IDENTIFICADO

Os templates **NÃO estão usando ShadCN/Tailwind CSS corretamente** conforme os exemplos fornecidos:

- [Sidebar-15](https://ui.shadcn.com/view/new-york-v4/sidebar-15) - Dashboard Cena::rio
- [Sidebar-09](https://ui.shadcn.com/view/new-york-v4/sidebar-09) - Chat/Instant Message
- [Sidebar-14](https://ui.shadcn.com/view/new-york-v4/sidebar-14) - Documents Page
- [Event Cards](https://codepen.io/Rafael-Valle-the-looper/pen/raxqVGR) - Event Listing
- [Event Single](https://codepen.io/Rafael-Valle-the-looper/pen/EaPpjXP) - Mobile Container Popup

---

## 🔴 Situação Atual

### 1. Templates de Eventos
- ✅ Usam `uni.css` de `assets.apollo.rio.br`
- ❌ **NÃO usam Tailwind CSS**
- ❌ Classes "shadcn-card" são **customizadas**, não ShadCN reais
- ❌ Usam CSS customizado ao invés de classes Tailwind

### 2. Cena::Rio (Dashboard)
- ✅ Carrega Tailwind via CDN (`cdn.tailwindcss.com`)
- ❌ Template `page-cena-rio.php` **NÃO encontrado**
- ❌ Não implementa sidebar-15 conforme exemplo

### 3. Chat/Instant Message
- ❌ Template de chat existe mas **não usa ShadCN sidebar-09**
- ❌ Usa CSS customizado ao invés de Tailwind

### 4. Documents Page
- ❌ Template de documentos **não usa ShadCN sidebar-14**
- ❌ Usa CSS customizado inline

---

## ✅ O Que Precisa Ser Feito

### Prioridade 1: Implementar ShadCN Components

#### 1.1 Dashboard Cena::rio (Sidebar-15)
**Requisitos:**
- Sidebar com logo clicável
- Centro de notificações ao clicar no logo
- Resumo de mensagens de chat
- Layout conforme [sidebar-15](https://ui.shadcn.com/view/new-york-v4/sidebar-15)

**Ação Necessária:**
- Criar template `apollo-social/cena-rio/templates/page-cena-rio.php`
- Implementar sidebar ShadCN conforme exemplo
- Adicionar componente de notificações

#### 1.2 Chat Page (Sidebar-09)
**Requisitos:**
- Sidebar com inbox
- Lista de conversas
- Layout conforme [sidebar-09](https://ui.shadcn.com/view/new-york-v4/sidebar-09)

**Ação Necessária:**
- Atualizar `apollo-social/templates/onboarding/chat.php`
- Implementar sidebar ShadCN conforme exemplo
- Usar classes Tailwind corretas

#### 1.3 Documents Page (Sidebar-14)
**Requisitos:**
- Sidebar com navegação
- Lista de documentos
- Documentos assinados com ícones RemixIcon
- Layout conforme [sidebar-14](https://ui.shadcn.com/view/new-york-v4/sidebar-14)

**Ação Necessária:**
- Atualizar `apollo-social/templates/documents/editor.php`
- Criar template de listagem de documentos
- Implementar sidebar ShadCN conforme exemplo

#### 1.4 Event Cards & Listing
**Requisitos:**
- Design conforme [CodePen raxqVGR](https://codepen.io/Rafael-Valle-the-looper/pen/raxqVGR)
- Filtros e busca funcionais
- Cards com design ShadCN

**Ação Necessária:**
- Verificar se design atual corresponde ao CodePen
- Adicionar classes Tailwind se necessário
- Garantir compatibilidade com ShadCN

#### 1.5 Event Single Page/Popup
**Requisitos:**
- Popup mobile-container conforme [CodePen EaPpjXP](https://codepen.io/Rafael-Valle-the-looper/pen/EaPpjXP)
- Design ShadCN para modal

**Ação Necessária:**
- Verificar template `single-event.php`
- Garantir que popup segue design do CodePen
- Adicionar classes Tailwind se necessário

---

## 🛠️ Solução Proposta

### Opção 1: Adicionar Tailwind CSS Globalmente

```php
// Adicionar ao apollo-events-manager.php e apollo-social.php
wp_enqueue_script(
    'tailwind-cdn',
    'https://cdn.tailwindcss.com',
    [],
    null,
    false
);
```

### Opção 2: Usar ShadCN via CDN ou Build

```html
<!-- Adicionar aos templates -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@shadcn/ui@latest/dist/shadcn.css">
```

### Opção 3: Implementar Componentes ShadCN Manualmente

Criar componentes ShadCN seguindo a documentação oficial:
- [ShadCN UI Components](https://ui.shadcn.com/)

---

## 📋 Checklist de Implementação

### Dashboard Cena::rio
- [ ] Criar template `page-cena-rio.php`
- [ ] Implementar sidebar conforme sidebar-15
- [ ] Adicionar logo clicável
- [ ] Implementar centro de notificações
- [ ] Adicionar resumo de mensagens

### Chat Page
- [ ] Atualizar template de chat
- [ ] Implementar sidebar conforme sidebar-09
- [ ] Adicionar lista de conversas
- [ ] Implementar interface de mensagens

### Documents Page
- [ ] Criar template de listagem
- [ ] Implementar sidebar conforme sidebar-14
- [ ] Adicionar ícones RemixIcon
- [ ] Implementar filtros de documentos

### Event Templates
- [ ] Verificar compatibilidade com CodePen
- [ ] Adicionar classes Tailwind se necessário
- [ ] Garantir design ShadCN

---

## ⚠️ Observações Importantes

1. **uni.css vs Tailwind:**
   - `uni.css` é um framework CSS customizado Apollo
   - Pode coexistir com Tailwind, mas precisa de configuração
   - Verificar conflitos de classes

2. **ShadCN Components:**
   - ShadCN é baseado em Tailwind + Radix UI
   - Requer Tailwind configurado corretamente
   - Componentes precisam ser importados/implementados

3. **Assets Externos:**
   - `assets.apollo.rio.br/uni.css` já está sendo usado
   - Pode precisar adicionar Tailwind/ShadCN ao CDN
   - Ou carregar via CDN público

---

## 🎯 Próximos Passos Recomendados

1. **Decidir estratégia:**
   - Tailwind via CDN ou build local?
   - ShadCN via CDN ou componentes customizados?

2. **Implementar templates faltantes:**
   - Dashboard Cena::rio
   - Chat com sidebar
   - Documents com sidebar

3. **Atualizar templates existentes:**
   - Adicionar classes Tailwind
   - Implementar componentes ShadCN
   - Garantir compatibilidade

---

**Status:** ⚠️ **SHADCN/TAILWIND NÃO ESTÁ SENDO APLICADO CORRETAMENTE**

**Ação Necessária:** Implementar ShadCN/Tailwind conforme exemplos fornecidos

---

**Data:** 15/01/2025  
**Referências:**
- [ShadCN Sidebar-15](https://ui.shadcn.com/view/new-york-v4/sidebar-15)
- [ShadCN Sidebar-09](https://ui.shadcn.com/view/new-york-v4/sidebar-09)
- [ShadCN Sidebar-14](https://ui.shadcn.com/view/new-york-v4/sidebar-14)
- [Event Cards CodePen](https://codepen.io/Rafael-Valle-the-looper/pen/raxqVGR)
- [Event Single CodePen](https://codepen.io/Rafael-Valle-the-looper/pen/EaPpjXP)

