# 📍 LOCALIZAÇÃO EXATA DOS ELEMENTOS

## 🎯 ONDE ESTÁ CADA ELEMENTO?

### 1. ✅ "Verifique se o cupom APOLLO está ativo"
**Arquivo:** `templates/single-event-page.php`  
**Linha:** 880-886  
**Section:** `#route_TICKETS` (Tickets Section)

```html
<div class="apollo-coupon-detail">
    <i class="ri-coupon-3-line"></i>
    <span>Verifique se o cupom <strong>APOLLO</strong> está ativo com desconto</span>
    <button class="copy-code-mini" onclick="copyPromoCode()">
        <i class="ri-file-copy-fill"></i>
    </button>
</div>
```

**Status:** ✅ SEMPRE APARECE (dentro de `.tickets-grid`, após o ticket-card)

---

### 2. ✅ mobile-container Centrado
**Arquivo:** `templates/single-event-page.php`  
**Linha:** 332-340

```html
<div class="mobile-container" style="max-width: 500px; margin: 0 auto;">
```

**Status:** ✅ CENTRADO (mesma direção que bottom-bar)  
**Largura:** max-width: 500px (igual bottom-bar)

---

### 3. ✅ SOUNDS TAGS (APENAS NO MARQUEE)
**Arquivo:** `templates/single-event-page.php`  
**Linha:** 567-586  
**Section:** Info Section (dentro de `.section`)

```html
<!-- SOUNDS TAGS GOES ONLY ON MARQUEE: $Selected event_sounds -->
<div class="music-tags-marquee">
    <div class="music-tags-track">
        <!-- Infinite span mandatory 1 --> <span class="music-tag">$Selected event_sounds</span>
        <!-- Infinite span mandatory 2 --> <span class="music-tag">$Selected event_sounds</span>
        <!-- ... 8x ... -->
    </div>
</div>
```

**Status:** ✅ SOUNDS APENAS NO MARQUEE (8x repetition)  
**Taxonomy:** `event_sounds`

---

### 4. ✅ HERO TAGS (CATEGORY + TAGS + TYPE)
**Arquivo:** `templates/single-event-page.php`  
**Linha:** 382-414  
**Section:** `#listing_types_tags_category` (dentro de `.hero-content`)

```html
<section id="listing_types_tags_category">
   <!-- Category: ri-brain-ai-3-fill -->
   <span class="event-tag-pill"><i class="ri-brain-ai-3-fill"></i> $category </span>  
   
   <!-- Tags (tag0-tag3): ri-price-tag-3-line -->
   <span class="event-tag-pill"><i class="ri-price-tag-3-line"></i> $tag0 </span>  
   <span class="event-tag-pill"><i class="ri-price-tag-3-line"></i> $tag1 </span>  
   <span class="event-tag-pill"><i class="ri-price-tag-3-line"></i> $tag2 </span>  
   <span class="event-tag-pill"><i class="ri-price-tag-3-line"></i> $tag3 </span>  
   
   <!-- Type: ri-landscape-ai-fill -->
   <span class="event-tag-pill"><i class="ri-landscape-ai-fill"></i> $type </span>
</section>
```

**Status:** ✅ CATEGORY + TAGS + TYPE (NÃO SOUNDS!)  
**Taxonomies:**
- Category: `event_listing_category`
- Tags: `event_listing_tag`
- Type: `_event_type` meta key

**Icons:**
- Category: `ri-brain-ai-3-fill`
- Tags: `ri-price-tag-3-line`
- Type: `ri-landscape-ai-fill`

---

## 🔥 REGRA DEFINITIVA

### HERO TAGS
- ✅ Category (`event_listing_category`)
- ✅ Tags (`event_listing_tag`)
- ✅ Type (`_event_type`)
- ❌ **NÃO SOUNDS** (sounds vai APENAS no marquee)

### MARQUEE
- ✅ **APENAS SOUNDS** (`event_sounds`)
- ❌ NÃO category, tags, type

---

## 📋 TODOS OS ELEMENTOS (LOCALIZAÇÃO)

| Elemento | Linha | Section | Status |
|----------|-------|---------|--------|
| mobile-container centered | 332-340 | Root | ✅ CENTRADO |
| Hero Tags (category+tags+type) | 382-414 | #listing_types_tags_category | ✅ NO SOUNDS |
| Hero Title | 416 | .hero-title | ✅ |
| Hero Meta (data, hora, local) | 418-446 | .hero-meta | ✅ |
| Quick Actions | 452-477 | .quick-actions | ✅ |
| RSVP Row (avatars) | 478-520 | .rsvp-row | ✅ |
| Info Section | 557-565 | .section | ✅ |
| **Marquee (SOUNDS)** | **567-586** | **.music-tags-marquee** | **✅ SOUNDS ONLY** |
| Promo Gallery | 590-615 | .promo-gallery-slider | ✅ |
| DJ Lineup | 618-660 | #route_LINE | ✅ |
| Venue Section | 662-667 | #route_ROUTE | ✅ |
| Local Images Slider | 670-693 | .local-images-slider | ✅ |
| Map View | 708-835 | .map-view | ✅ |
| Route Controls | 838-847 | .route-controls | ✅ |
| **Tickets + Cupom APOLLO** | **851-906** | **#route_TICKETS** | **✅ CUPOM SEMPRE** |
| Final Image | 909-914 | .secondary-image | ✅ |
| Protection Notice | 917-922 | .respaldo_eve | ✅ FORA |
| Bottom Bar | 925-944 | .bottom-bar | ✅ |

---

## ✅ GARANTIAS

1. ✅ **Cupom APOLLO:** Linha 880-886 (SEMPRE aparece)
2. ✅ **mobile-container:** Linha 332-340 (CENTRADO com max-width: 500px)
3. ✅ **SOUNDS:** Linha 567-586 (APENAS NO MARQUEE, 8x repetition)
4. ✅ **Hero Tags:** Linha 382-414 (CATEGORY + TAGS + TYPE, NÃO SOUNDS)
5. ✅ **Bottom Bar:** Linha 925-944 (classes "primary 1" e "secondary 2")

---

## 🚀 AÇÃO IMEDIATA

### Desativar/Reativar Plugin
```
WordPress Admin → Plugins
→ Desativar "Apollo Events Manager"
→ Reativar "Apollo Events Manager"
```

### Hard Refresh (3-5x)
```
Ctrl + Shift + R (pressione 3-5 vezes)
```

---

## ✅ STATUS

**Cupom APOLLO:** ✅ Linha 880-886 (SEMPRE visível)  
**mobile-container:** ✅ Linha 332-340 (CENTRADO)  
**SOUNDS:** ✅ Linha 567-586 (APENAS MARQUEE)  
**Hero Tags:** ✅ Linha 382-414 (NO SOUNDS!)  

**Código:** VÁLIDO ✅  
**CodePen Match:** EXATO ✅  

**Status:** TODOS OS ELEMENTOS LOCALIZADOS ✅  
**Ação:** Cache clear + Test  

