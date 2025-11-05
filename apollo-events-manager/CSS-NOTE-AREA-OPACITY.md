# 📝 NOTA: CSS para Área do Local

**Data:** 2025-11-04  
**Arquivo:** `portal-discover.php` - Event cards agora exibem área do local separada

---

## 🎨 CSS Necessário

Adicionar ao `uni.css` (ou CSS global) para estilizar a área do local com 50% de opacidade:

```css
.event-li-detail.of-location .event-location-area {
    opacity: 0.5;
    margin-left: 0.35rem;
}
```

**Localização:** `https://assets.apollo.rio.br/uni.css`

**Efeito:**
- Área do local (ex: "(Copacabana)") aparece com 50% de opacidade
- Espaçamento adequado entre nome e área

---

## 📋 Estrutura HTML Gerada

O template agora gera:

```html
<p class="event-li-detail of-location mb04rem">
    <i class="ri-map-pin-2-line"></i>
    <span class="event-location-name">D-Edge</span>
    <span class="event-location-area">(Copacabana)</span>
</p>
```

**Formato do Meta:**
- `_event_location` pode conter: `"D-Edge|Copacabana"`
- Separação por pipe `|` - nome do local antes, área depois

---

**Status:** ⚠️ CSS precisa ser adicionado ao uni.css externo  
**Nota:** Não foi alterado nenhum arquivo CSS local (conforme solicitado)

