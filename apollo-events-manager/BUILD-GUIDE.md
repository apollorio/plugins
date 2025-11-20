# 🔧 BUILD GUIDE - Tailwind CSS Compilation

## ✅ TODO 89: Build Script Configurado

---

## 📋 Scripts Disponíveis

### 1. Build (Production)
```bash
npm run build
```
**O que faz:**
- Compila `assets/css/input.css` → `assets/css/tailwind-output.css`
- Minifica o CSS
- Remove classes não utilizadas (purge)

### 2. Build Production (Optimized)
```bash
npm run build:prod
```
**O que faz:**
- Compila com NODE_ENV=production
- Minificação agressiva
- Purge completo de classes não usadas
- Otimizado para deploy

### 3. Watch Mode (Development)
```bash
npm run watch
# ou
npm run dev
# ou
npm run build:watch
```
**O que faz:**
- Monitora mudanças em `input.css`
- Recompila automaticamente
- **NÃO** minifica (melhor para debug)
- Ideal para desenvolvimento

---

## 🚀 Como Usar

### Setup Inicial
```bash
# 1. Instalar dependências
npm install

# 2. Compilar CSS pela primeira vez
npm run build
```

### Durante Desenvolvimento
```bash
# Deixar rodando em terminal separado
npm run watch
```

### Antes de Deploy
```bash
# Build otimizado para produção
npm run build:prod
```

---

## 📁 Arquivos Envolvidos

### Input
- `assets/css/input.css` - Arquivo fonte com @tailwind directives

### Output
- `assets/css/tailwind-output.css` - CSS compilado (gitignored)

### Config
- `tailwind.config.js` - Configuração do Tailwind
- `postcss.config.js` - Configuração do PostCSS

---

## ⚠️ IMPORTANTE

### uni.css É O PRINCIPAL CSS
- **uni.css** (https://assets.apollo.rio.br/uni.css) é o CSS universal
- **Tailwind** é APENAS para components específicos (forms, dashboards, ShadCN)
- **NÃO** usar Tailwind para `.event_listing`, `.mobile-container`, etc.

### O Que Compilar no Tailwind
- ✅ Form components
- ✅ Dashboard components
- ✅ Admin pages
- ✅ ShadCN components
- ❌ Event cards (usa uni.css)
- ❌ Single event page (usa uni.css)
- ❌ Universal layouts (usa uni.css)

---

## ✅ Status

**TODO 89:** ✅ CONCLUÍDO  
**Scripts:** ✅ Configurados  
**Dependencies:** ✅ Instaladas  

**Pronto para:** Build e deploy  

---

**Arquivo:** `BUILD-GUIDE.md`  
**Data:** 15/01/2025  
**Status:** BUILD SCRIPT READY ✅

