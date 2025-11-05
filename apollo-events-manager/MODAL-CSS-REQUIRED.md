# 🎨 CSS Necessário para Modal de Eventos

**Data:** 2025-11-04  
**Localização:** `https://assets.apollo.rio.br/uni.css`

---

## 📋 CSS Completo para Modal

Adicionar ao `uni.css` externo:

```css
/* ==========================================================================
   Modal Container & Overlay
   ========================================================================== */
.apollo-event-modal {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: none;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.apollo-event-modal.is-open {
    display: block;
    opacity: 1;
}

.apollo-event-modal-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(4px);
    cursor: pointer;
}

/* Previne scroll do body quando modal aberto */
.apollo-modal-open {
    overflow: hidden;
}

/* ==========================================================================
   Modal Content
   ========================================================================== */
.apollo-event-modal-content {
    position: relative;
    max-width: 960px;
    max-height: 90vh;
    margin: 5vh auto;
    background: #050509;
    border-radius: 20px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* ==========================================================================
   Modal Close Button
   ========================================================================== */
.apollo-event-modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    z-index: 10;
    width: 40px;
    height: 40px;
    border: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8px);
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 24px;
    transition: all 0.2s ease;
}

.apollo-event-modal-close:hover {
    background: rgba(0, 0, 0, 0.9);
    transform: rotate(90deg);
}

.apollo-event-modal-close:focus-visible {
    outline: 2px solid #c7ff00;
    outline-offset: 2px;
}

/* ==========================================================================
   Modal Hero Section
   ========================================================================== */
.apollo-event-hero {
    position: relative;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    min-height: 300px;
}

.apollo-event-hero-media {
    position: relative;
    overflow: hidden;
}

.apollo-event-hero-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* Date chip no banner */
.apollo-event-date-chip {
    position: absolute;
    bottom: 20px;
    left: 20px;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(12px);
    border-radius: 12px;
    padding: 12px 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.apollo-event-date-chip .d {
    font-size: 32px;
    font-weight: 700;
    line-height: 1;
    color: #c7ff00;
}

.apollo-event-date-chip .m {
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    color: #fff;
    letter-spacing: 1px;
    margin-top: 4px;
}

/* Info section */
.apollo-event-hero-info {
    padding: 32px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 16px;
    background: linear-gradient(135deg, #0a0a0f 0%, #050509 100%);
}

.apollo-event-title {
    font-size: 32px;
    font-weight: 700;
    line-height: 1.2;
    color: #fff;
    margin: 0;
}

/* DJs display */
.apollo-event-djs {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 16px;
    color: #fff;
    margin: 0;
}

.apollo-event-djs i {
    font-size: 20px;
    color: #c7ff00;
    flex-shrink: 0;
}

.apollo-event-djs strong {
    color: #c7ff00;
}

.apollo-event-djs .dj-more {
    color: rgba(255, 255, 255, 0.6);
    font-size: 14px;
}

.apollo-event-djs .dj-fallback {
    color: rgba(255, 255, 255, 0.5);
    font-style: italic;
}

/* Location display */
.apollo-event-location {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 16px;
    color: #fff;
    margin: 0;
}

.apollo-event-location i {
    font-size: 20px;
    color: #c7ff00;
    flex-shrink: 0;
}

/* Local: área 50% opacidade (também no card) */
.event-li-detail.of-location .event-location-area,
.apollo-event-location .event-location-area {
    opacity: 0.5;
    margin-left: 0.35rem;
}

/* ==========================================================================
   Modal Body
   ========================================================================== */
.apollo-event-body {
    padding: 32px;
    overflow-y: auto;
    flex: 1;
    background: #050509;
}

.apollo-event-body p {
    line-height: 1.7;
    color: rgba(255, 255, 255, 0.85);
    margin-bottom: 16px;
}

.apollo-event-body h2,
.apollo-event-body h3 {
    color: #fff;
    margin-top: 24px;
    margin-bottom: 12px;
}

/* ==========================================================================
   Loading State
   ========================================================================== */
.event_listing.is-loading {
    pointer-events: none;
    opacity: 0.6;
    position: relative;
}

.event_listing.is-loading::after {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.3);
    border-radius: inherit;
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 0.3; }
    50% { opacity: 0.6; }
}

/* ==========================================================================
   Responsive Design
   ========================================================================== */
@media (max-width: 768px) {
    .apollo-event-modal-content {
        margin: 0;
        max-height: 100vh;
        border-radius: 0;
    }
    
    .apollo-event-hero {
        grid-template-columns: 1fr;
    }
    
    .apollo-event-hero-media {
        min-height: 250px;
    }
    
    .apollo-event-hero-info {
        padding: 24px;
    }
    
    .apollo-event-title {
        font-size: 24px;
    }
    
    .apollo-event-body {
        padding: 24px;
    }
}

@media (max-width: 480px) {
    .apollo-event-modal-close {
        width: 36px;
        height: 36px;
        font-size: 20px;
    }
    
    .apollo-event-date-chip {
        bottom: 12px;
        left: 12px;
        padding: 8px 12px;
    }
    
    .apollo-event-date-chip .d {
        font-size: 24px;
    }
    
    .apollo-event-hero-info {
        gap: 12px;
    }
}

/* ==========================================================================
   Accessibility
   ========================================================================== */
.apollo-event-modal[aria-hidden="true"] {
    pointer-events: none;
}

.apollo-event-modal[aria-hidden="false"] {
    pointer-events: auto;
}

/* Focus visível para navegação por teclado */
.apollo-event-modal a:focus-visible,
.apollo-event-modal button:focus-visible {
    outline: 2px solid #c7ff00;
    outline-offset: 2px;
}

/* Customização da scrollbar */
.apollo-event-body::-webkit-scrollbar {
    width: 8px;
}

.apollo-event-body::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

.apollo-event-body::-webkit-scrollbar-thumb {
    background: rgba(199, 255, 0, 0.3);
    border-radius: 4px;
}

.apollo-event-body::-webkit-scrollbar-thumb:hover {
    background: rgba(199, 255, 0, 0.5);
}
```

---

## ✅ Status

- ✅ JavaScript implementado (`assets/js/apollo-events-portal.js`)
- ✅ Endpoint AJAX implementado (`ajax_get_event_modal`)
- ✅ Container do modal no template (`#apollo-event-modal`)
- ⚠️ CSS precisa ser adicionado ao `uni.css` externo

---

**Nota:** Este CSS deve ser adicionado ao arquivo `uni.css` em `https://assets.apollo.rio.br/uni.css` para que o modal funcione corretamente.

