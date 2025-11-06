# 🚀 APOLLO FRONTEND STANDARDIZATION — REMIXICON + UNI.CSS

**Status:** ✅ Active  
**Version:** 1.0.0  
**Date:** 2025-11-05

---

## 📋 OVERVIEW

Este documento define os padrões de frontend para todos os plugins Apollo, garantindo consistência visual e funcional em toda a plataforma.

### Stack Padronizado:
- ✅ **uni.css** - Framework CSS Apollo (CDN: `https://assets.apollo.rio.br/uni.css`)
- ✅ **RemixIcon** - Biblioteca de ícones (CDN: `https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css`)
- ✅ **WordPress Native** - Templates PHP (não React/Next.js)

---

## 1️⃣ UNI.CSS — CARREGAMENTO PRIORITÁRIO

### Regra Crítica:
> **uni.css DEVE ser carregado ANTES de qualquer outro CSS customizado**

### Implementação em Templates PHP:

```php
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 1. UNI.CSS PRIMEIRO (CRÍTICO!) -->
    <link rel="stylesheet" href="https://assets.apollo.rio.br/uni.css">
    
    <!-- 2. RemixIcon -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css">
    
    <!-- 3. CSS Customizado (se necessário) -->
    <style>
        /* Custom styles que podem sobrescrever uni.css */
    </style>
</head>
```

### Implementação via WordPress Hooks:

```php
// No plugin principal (apollo-events-manager.php)
add_action('wp_enqueue_scripts', function() {
    // uni.css PRIMEIRO (priority 1)
    wp_enqueue_style(
        'apollo-uni-css',
        'https://assets.apollo.rio.br/uni.css',
        array(), // No dependencies
        null,
        'all'
    );
    
    // RemixIcon (priority 2)
    wp_enqueue_style(
        'remixicon',
        'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css',
        array('apollo-uni-css'), // Depends on uni.css
        '4.7.0',
        'all'
    );
}, 1); // Priority 1 = loads first
```

---

## 2️⃣ REMIXICON — SUBSTITUIÇÃO DE ÍCONES

### Regra:
> **Todos os ícones devem usar RemixIcon, nunca Lucide ou outras bibliotecas**

### Formato Padrão:

```html
<!-- Ícone de linha (outline) -->
<i class="ri-calendar-event-line"></i>

<!-- Ícone preenchido (fill) -->
<i class="ri-user-3-fill"></i>

<!-- Ícone com animação -->
<i class="ri-sound-module-fill ri-spin"></i>
```

### Mapeamento de Ícones Comuns:

| Uso | RemixIcon Class |
|-----|----------------|
| Calendário | `ri-calendar-event-line` |
| Usuário | `ri-user-3-line` |
| Música/DJ | `ri-sound-module-fill` |
| Localização | `ri-map-pin-2-line` |
| Busca | `ri-search-line` |
| Fechar | `ri-close-line` |
| Play | `ri-play-fill` |
| Pause | `ri-pause-fill` |
| Download | `ri-download-line` |
| Link Externo | `ri-external-link-line` |
| Mais Opções | `ri-more-2-line` |
| Seta Direita | `ri-arrow-right-line` |
| Seta Esquerda | `ri-arrow-left-line` |
| Seta Superior Esquerda | `ri-arrow-top-left-long-line` |

### Exemplos em Templates:

```php
<!-- Botão com ícone -->
<button class="btn">
    <i class="ri-calendar-event-line"></i>
    Ver Eventos
</button>

<!-- Link com ícone -->
<a href="#" class="social-link">
    <i class="ri-instagram-line"></i>
    Instagram
</a>

<!-- Badge com ícone -->
<span class="badge">
    <i class="ri-map-pin-2-line"></i>
    Rio de Janeiro
</span>
```

---

## 3️⃣ ESTRUTURA DE TEMPLATES

### Template Base Padrão:

```php
<?php
/**
 * Template: [Nome do Template]
 * Baseado no CodePen [ID]
 * 
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

get_header();
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title('|', true, 'right'); ?></title>
    
    <!-- 1. UNI.CSS PRIMEIRO -->
    <link rel="stylesheet" href="https://assets.apollo.rio.br/uni.css">
    
    <!-- 2. REMIXICON -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css">
    
    <!-- 3. CSS Customizado (se necessário) -->
    <style>
        /* Custom styles */
    </style>
</head>
<body <?php body_class('apollo-template'); ?>>

<main class="page-wrap">
    <!-- Conteúdo do template -->
</main>

<script>
// JavaScript customizado
</script>

<?php get_footer(); ?>
```

---

## 4️⃣ COMPONENTES PADRÃO

### Botão Padrão:

```php
<button class="apollo-btn apollo-btn-primary">
    <i class="ri-calendar-event-line"></i>
    <span>Texto do Botão</span>
</button>
```

### Card Padrão:

```php
<div class="apollo-card">
    <div class="apollo-card-header">
        <h3 class="apollo-card-title">
            <i class="ri-sound-module-fill"></i>
            Título do Card
        </h3>
    </div>
    <div class="apollo-card-body">
        <!-- Conteúdo -->
    </div>
</div>
```

### Badge Padrão:

```php
<span class="apollo-badge">
    <i class="ri-map-pin-2-line"></i>
    Texto do Badge
</span>
```

---

## 5️⃣ DASHBOARD SIDEBAR — BUTTON GROUP

### Estrutura Padrão para Sidebar:

```php
<div 
    role="group"
    data-slot="button-group"
    class="apollo-button-group"
>
    <!-- Botão Hora/RJ -->
    <button class="apollo-btn apollo-btn-secondary">
        <a class="a-hover">
            <span id="agoraH"><?php echo date('H:i:s'); ?></span> RJ
        </a>
    </button>
    
    <!-- Botão Eventos -->
    <button class="apollo-btn apollo-btn-secondary">
        Eventos 
        <i class="ri-arrow-top-left-long-line"></i>
    </button>
    
    <!-- Grupo Usuário -->
    <div class="apollo-button-group-nested">
        <button class="apollo-btn apollo-btn-secondary">
            Oi, <?php echo wp_get_current_user()->display_name; ?>
        </button>
        <button 
            class="apollo-btn apollo-btn-icon"
            aria-label="More Options"
        >
            <i class="ri-more-2-line"></i>
        </button>
    </div>
</div>
```

### CSS para Button Group:

```css
.apollo-button-group {
    display: flex;
    align-items: stretch;
    gap: 0;
    width: fit-content;
}

.apollo-button-group > * {
    border-radius: 0;
}

.apollo-button-group > *:not(:first-child) {
    border-left: none;
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

.apollo-button-group > *:not(:last-child) {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.apollo-button-group-nested {
    display: flex;
    gap: 0;
}

.apollo-button-group-nested > *:first-child {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.apollo-button-group-nested > *:last-child {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    border-left: none;
}
```

---

## 6️⃣ PLUGINS AFETADOS

### ✅ apollo-events-manager
- [x] Templates já usam RemixIcon
- [x] uni.css carregado primeiro
- [ ] Dashboard sidebar precisa do button group

### ⏳ apollo-rio
- [ ] Verificar uso de ícones
- [ ] Garantir uni.css primeiro
- [ ] Atualizar sidebar se houver

### ⏳ apollo-social
- [ ] Verificar uso de Lucide (se houver)
- [ ] Substituir por RemixIcon
- [ ] Garantir uni.css primeiro

---

## 7️⃣ CHECKLIST DE MIGRAÇÃO

### Para cada template/componente:

- [ ] uni.css carregado ANTES de qualquer CSS customizado
- [ ] RemixIcon carregado após uni.css
- [ ] Todos os ícones usam formato `<i class="ri-ICON-name"></i>`
- [ ] Nenhum uso de Lucide ou outras bibliotecas de ícones
- [ ] Classes CSS seguem padrão `apollo-*`
- [ ] Estrutura HTML semântica
- [ ] Responsivo (mobile-first)
- [ ] Dark mode compatível (se aplicável)

---

## 8️⃣ RECURSOS

### CDN Links:
- **uni.css:** `https://assets.apollo.rio.br/uni.css`
- **RemixIcon:** `https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css`

### Documentação:
- **RemixIcon:** https://remixicon.com/
- **uni.css:** (Documentação interna Apollo)

---

## 📌 NOTAS IMPORTANTES

1. **uni.css define variáveis CSS root** - Por isso deve carregar primeiro
2. **RemixIcon é apenas CSS** - Não precisa de JavaScript
3. **WordPress não usa React** - Adaptar componentes shadcn para PHP/HTML
4. **Consistência visual** - Todos os plugins devem seguir este padrão

---

**Última atualização:** 2025-11-05  
**Mantido por:** Apollo Development Team

