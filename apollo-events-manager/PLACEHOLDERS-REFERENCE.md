# 📋 PLACEHOLDERS REFERENCE
## Apollo Events Manager - Lista Completa de Placeholders

**Última Atualização:** 2025-11-04  
**Versão:** 2.0.2

Este documento lista **todos os placeholders** usados no plugin, organizados por contexto (Landing, Evento Single, DJ, Local).

---

## 🎯 ESTRUTURA

### A. Página Landing (`/eventos/`) - Sem ID específico
### B. Página de Evento Single - Com `$event_id`
### C. Página de DJ - Com `$dj_id`
### D. Página de Local - Com `$local_id`

---

## A. PÁGINA LANDING (`/eventos/`) - Sem Select ID

### A.1 Imagens/Banners

#### A.1.1 Banner de Evento (Fallback)
**Arquivo:** `templates/portal-discover.php:209`  
**Arquivo:** `templates/event-card.php:172`  
**Arquivo:** `templates/content-event_listing.php:92`

```php
$banner_url = 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=2070';
```

**Uso:** Quando evento não tem `_event_banner` meta  
**Contexto:** Card de evento no grid  
**Substituir por:** `{APOLLO_PLACEHOLDER_EVENT_BANNER}`

---

#### A.1.2 Banner Highlight (Blog Post)
**Arquivo:** `templates/portal-discover.php:287`  
**Arquivo:** `templates/event-listings-end.php:15`

```php
$banner_image = 'https://images.unsplash.com/photo-1506157786151-b8491531f063?q=80&w=2070&auto=format&fit=crop';
```

**Uso:** Quando não há post de blog recente  
**Contexto:** Seção "Highlight Banner" no final do portal  
**Substituir por:** `{APOLLO_PLACEHOLDER_HIGHLIGHT_BANNER}`

---

#### A.1.3 Banner Highlight (Hardcoded)
**Arquivo:** `templates/portal-discover.php:314`  
**Arquivo:** `templates/event-listings-end.php:15`  
**Arquivo:** `apollo-events-manager.php:708`

```html
<img src="https://images.unsplash.com/photo-1506157786151-b8491531f063?q=80&w=2070&auto=format&fit=crop" class="ban-ario-1-img" alt="Upcoming Festival">
```

**Uso:** Imagem hardcoded no HTML (fallback quando não há posts)  
**Contexto:** Seção de highlight banner  
**Substituir por:** `{APOLLO_PLACEHOLDER_HIGHLIGHT_BANNER_STATIC}`

---

### A.2 Textos/Mensagens

#### A.2.1 Nenhum Evento Encontrado
**Arquivo:** `templates/portal-discover.php:265`  
**Arquivo:** `apollo-events-manager.php:471`  
**Arquivo:** `apollo-events-manager.php:701`

```php
echo '<p class="no-events-found">Nenhum evento encontrado.</p>';
// ou
echo '<p>Nenhum evento encontrado.</p>';
```

**Uso:** Quando `WP_Query` não retorna eventos  
**Contexto:** Grid de eventos vazio  
**Substituir por:** `{APOLLO_PLACEHOLDER_NO_EVENTS}`

---

#### A.2.2 Erro ao Carregar Eventos
**Arquivo:** `templates/portal-discover.php:142`

```php
echo '<p class="no-events-found">Erro ao carregar eventos. Tente novamente.</p>';
```

**Uso:** Quando `WP_Query` retorna `WP_Error`  
**Contexto:** Erro de conexão com banco  
**Substituir por:** `{APOLLO_PLACEHOLDER_EVENTS_ERROR}`

---

#### A.2.3 Placeholder de Busca
**Arquivo:** `templates/portal-discover.php:109`  
**Arquivo:** `templates/event-listings-start.php:70`

```html
<input type="text" name="search_keywords" id="eventSearchInput" placeholder="" inputmode="search" autocomplete="off">
```

**Uso:** Campo de busca (atualmente vazio)  
**Contexto:** Barra de busca no portal  
**Substituir por:** `placeholder="{APOLLO_PLACEHOLDER_SEARCH_INPUT}"`

---

#### A.2.4 Texto do Highlight Banner
**Arquivo:** `templates/portal-discover.php:319`  
**Arquivo:** `apollo-events-manager.php:713`

```php
A Retrospectiva Clubber 2026 está chegando! E em breve vamos liberar as primeiras novidades... Fique ligado, porque essa publicação promete celebrar tudo o que fez o coração da pista bater mais forte! Spoilers?
```

**Uso:** Texto hardcoded quando não há post de blog  
**Contexto:** Seção highlight banner  
**Substituir por:** `{APOLLO_PLACEHOLDER_HIGHLIGHT_TEXT}`

---

### A.3 Data/Hora

#### A.3.1 Data Inválida (Dia)
**Arquivo:** `templates/event-card.php:213`  
**Arquivo:** `templates/content-event_listing.php:104`

```php
<span class="date-day"><?php echo esc_html($day ?: '--'); ?></span>
```

**Uso:** Quando `_event_start_date` está vazio ou inválido  
**Contexto:** Card de evento no grid  
**Substituir por:** `{APOLLO_PLACEHOLDER_DATE_DAY}`

---

#### A.3.2 Data Inválida (Mês)
**Arquivo:** `templates/event-card.php:214`  
**Arquivo:** `templates/content-event_listing.php:105`

```php
<span class="date-month"><?php echo esc_html($month_str ?: '---'); ?></span>
```

**Uso:** Quando `_event_start_date` está vazio ou inválido  
**Contexto:** Card de evento no grid  
**Substituir por:** `{APOLLO_PLACEHOLDER_DATE_MONTH}`

---

#### A.3.3 Horário Atual (Header)
**Arquivo:** `templates/portal-discover.php:33`

```html
<span id="agoraH">--:--</span>
```

**Uso:** Placeholder para horário atual (preenchido via JS)  
**Contexto:** Header fixo  
**Substituir por:** `{APOLLO_PLACEHOLDER_CURRENT_TIME}`

---

## B. PÁGINA DE EVENTO SINGLE - Com `$event_id`

### B.1 Imagens/Banners

#### B.1.1 Banner do Evento (Fallback)
**Arquivo:** `templates/single-event-standalone.php:119`  
**Arquivo:** `templates/single-event-page.php:116`  
**Arquivo:** `templates/single-event.php:84`

```php
$event_banner_url = 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=2070';
```

**Uso:** Quando evento não tem `_event_banner` meta  
**Contexto:** Hero section do evento single  
**Substituir por:** `{APOLLO_PLACEHOLDER_EVENT_BANNER}`

---

#### B.1.2 Imagem de Promoção (Fallback)
**Arquivo:** `templates/single-event.php:286`

```php
$img_url = isset($promo_images[$i]) ? $promo_images[$i] : 'https://via.placeholder.com/400x300';
```

**Uso:** Quando `_3_imagens_promo` não tem imagem na posição `$i`  
**Contexto:** Galeria de imagens promocionais  
**Substituir por:** `{APOLLO_PLACEHOLDER_PROMO_IMAGE}`

---

#### B.1.3 Imagem do DJ (Fallback)
**Arquivo:** `templates/single-event.php:131`  
**Arquivo:** `templates/single-event.php:164`

```php
'image' => $dj_img ?: 'https://via.placeholder.com/100x100',
```

**Uso:** Quando DJ não tem imagem de perfil  
**Contexto:** Lista de DJs no evento  
**Substituir por:** `{APOLLO_PLACEHOLDER_DJ_IMAGE}`

---

#### B.1.4 Imagem do Local (Fallback)
**Arquivo:** `templates/single-event.php:330`

```php
<img src="https://via.placeholder.com/400x300?text=Local+Image+<?php echo $i; ?>">
```

**Uso:** Quando local não tem imagens  
**Contexto:** Slider de imagens do local  
**Substituir por:** `{APOLLO_PLACEHOLDER_LOCAL_IMAGE}`

---

### B.2 Textos/Mensagens

#### B.2.1 Evento Não Encontrado (AJAX)
**Arquivo:** `apollo-events-manager.php:834`  
**Arquivo:** `apollo-events-manager.php:860`

```php
wp_send_json_error('Evento não encontrado');
```

**Uso:** Quando `ajax_load_event_single` não encontra evento  
**Contexto:** Resposta AJAX de erro  
**Substituir por:** `{APOLLO_PLACEHOLDER_EVENT_NOT_FOUND}`

---

#### B.2.2 Line-up em Breve
**Arquivo:** `templates/single-event-standalone.php:422`

```html
<div class="lineup-placeholder">
    <p style="color:var(--text-secondary,#999);margin:0;">Line-up em breve</p>
</div>
```

**Uso:** Quando evento não tem DJs no timetable  
**Contexto:** Seção de DJ lineup  
**Substituir por:** `{APOLLO_PLACEHOLDER_LINEUP_TBA}`

---

#### B.2.3 Placeholder de Input (Rota)
**Arquivo:** `templates/single-event-standalone.php:543`  
**Arquivo:** `templates/single-event-page.php:445`  
**Arquivo:** `templates/single-event.php:340`

```html
<input type="text" id="origin-input" placeholder="Seu endereço de partida">
```

**Uso:** Campo de endereço de origem para rota  
**Contexto:** Seção de mapa/rotas  
**Substituir por:** `placeholder="{APOLLO_PLACEHOLDER_ROUTE_INPUT}"`

---

#### B.2.4 Location TBA
**Arquivo:** `apollo-events-manager.php:565`

```php
return $location ?: __('Location TBA', 'apollo-events-manager');
```

**Uso:** Quando evento não tem `_event_location` meta  
**Contexto:** Função helper `get_event_location()`  
**Substituir por:** `{APOLLO_PLACEHOLDER_LOCATION_TBA}`

---

### B.3 Mapa

#### B.3.1 Placeholder de Mapa
**Arquivo:** `templates/single-event-standalone.php:528`  
**Arquivo:** `templates/single-event-page.php:429`  
**Arquivo:** `templates/single-event.php:755`

```html
<div class="map-placeholder" style="height:285px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;border-radius:12px;">
    <!-- Mapa não disponível -->
</div>
```

**Uso:** Quando evento/local não tem coordenadas  
**Contexto:** Seção de mapa  
**Substituir por:** `{APOLLO_PLACEHOLDER_MAP_UNAVAILABLE}`

---

#### B.3.2 Mensagem de Mapa (Console)
**Arquivo:** `templates/single-event-standalone.php:535`

```javascript
console.log('⚠️ Map not displayed - no coordinates found for event <?php echo absint($event_id); ?>');
```

**Uso:** Log de debug quando mapa não carrega  
**Contexto:** Console do browser  
**Substituir por:** `{APOLLO_PLACEHOLDER_MAP_ERROR_LOG}`

---

## C. PÁGINA DE DJ - Com `$dj_id`

### C.1 Imagens

#### C.1.1 Imagem do DJ (Fallback)
**Arquivo:** `templates/single-event.php:131` (no contexto de evento)

```php
'image' => $dj_img ?: 'https://via.placeholder.com/100x100',
```

**Uso:** Quando DJ não tem imagem de perfil  
**Contexto:** Card de DJ na lista  
**Substituir por:** `{APOLLO_PLACEHOLDER_DJ_IMAGE}`

**Nota:** Não há template dedicado para página single de DJ ainda. Se houver, adicionar placeholders similares.

---

### C.2 Textos/Mensagens

**Nota:** Não há placeholders específicos de DJ encontrados no código atual.  
**Sugestão:** Criar placeholders quando template de DJ single for implementado.

---

## D. PÁGINA DE LOCAL - Com `$local_id`

### D.1 Imagens

#### D.1.1 Imagem do Local (Fallback)
**Arquivo:** `templates/single-event.php:330` (no contexto de evento)

```php
<img src="https://via.placeholder.com/400x300?text=Local+Image+<?php echo $i; ?>">
```

**Uso:** Quando local não tem imagens  
**Contexto:** Slider de imagens do local  
**Substituir por:** `{APOLLO_PLACEHOLDER_LOCAL_IMAGE}`

---

### D.2 Textos/Mensagens

**Nota:** Não há placeholders específicos de Local encontrados no código atual.  
**Sugestão:** Criar placeholders quando template de Local single for implementado.

---

## 📊 RESUMO DE PLACEHOLDERS POR CATEGORIA

### 🖼️ Imagens (8 placeholders)

1. `{APOLLO_PLACEHOLDER_EVENT_BANNER}` - Banner de evento (fallback)
2. `{APOLLO_PLACEHOLDER_HIGHLIGHT_BANNER}` - Banner highlight (blog post)
3. `{APOLLO_PLACEHOLDER_HIGHLIGHT_BANNER_STATIC}` - Banner highlight (hardcoded)
4. `{APOLLO_PLACEHOLDER_PROMO_IMAGE}` - Imagem promocional
5. `{APOLLO_PLACEHOLDER_DJ_IMAGE}` - Imagem de DJ
6. `{APOLLO_PLACEHOLDER_LOCAL_IMAGE}` - Imagem de local
7. `{APOLLO_PLACEHOLDER_MAP_UNAVAILABLE}` - Placeholder de mapa (HTML)
8. (Sem template específico para DJ/Local single ainda)

---

### 📝 Textos/Mensagens (10 placeholders)

1. `{APOLLO_PLACEHOLDER_NO_EVENTS}` - "Nenhum evento encontrado"
2. `{APOLLO_PLACEHOLDER_EVENTS_ERROR}` - "Erro ao carregar eventos"
3. `{APOLLO_PLACEHOLDER_SEARCH_INPUT}` - Placeholder do campo de busca
4. `{APOLLO_PLACEHOLDER_HIGHLIGHT_TEXT}` - Texto do highlight banner
5. `{APOLLO_PLACEHOLDER_EVENT_NOT_FOUND}` - "Evento não encontrado" (AJAX)
6. `{APOLLO_PLACEHOLDER_LINEUP_TBA}` - "Line-up em breve"
7. `{APOLLO_PLACEHOLDER_LOCATION_TBA}` - "Location TBA"
8. `{APOLLO_PLACEHOLDER_ROUTE_INPUT}` - "Seu endereço de partida"
9. `{APOLLO_PLACEHOLDER_MAP_ERROR_LOG}` - Mensagem de erro no console
10. `{APOLLO_PLACEHOLDER_CURRENT_TIME}` - Horário atual (header)

---

### 📅 Data/Hora (3 placeholders)

1. `{APOLLO_PLACEHOLDER_DATE_DAY}` - Dia inválido ("--")
2. `{APOLLO_PLACEHOLDER_DATE_MONTH}` - Mês inválido ("---")
3. `{APOLLO_PLACEHOLDER_CURRENT_TIME}` - Horário atual ("--:--")

---

## 🎯 MAPA DE SUBSTITUIÇÃO

### URLs de Imagens Unsplash

| Placeholder | URL Atual | Contexto |
|-------------|-----------|----------|
| `{APOLLO_PLACEHOLDER_EVENT_BANNER}` | `https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=2070` | Banner de evento |
| `{APOLLO_PLACEHOLDER_HIGHLIGHT_BANNER}` | `https://images.unsplash.com/photo-1506157786151-b8491531f063?q=80&w=2070&auto=format&fit=crop` | Banner highlight |

### URLs de Placeholder.com

| Placeholder | URL Atual | Contexto |
|-------------|-----------|----------|
| `{APOLLO_PLACEHOLDER_DJ_IMAGE}` | `https://via.placeholder.com/100x100` | Imagem de DJ |
| `{APOLLO_PLACEHOLDER_PROMO_IMAGE}` | `https://via.placeholder.com/400x300` | Imagem promocional |
| `{APOLLO_PLACEHOLDER_LOCAL_IMAGE}` | `https://via.placeholder.com/400x300?text=Local+Image+{i}` | Imagem de local |

---

## 📝 INSTRUÇÕES DE SUBSTITUIÇÃO

### 1. Criar Arquivo de Configuração

Criar `includes/placeholders.php`:

```php
<?php
if (!defined('ABSPATH')) exit;

function apollo_get_placeholder($key, $default = '') {
    $placeholders = apply_filters('apollo_placeholders', [
        // Imagens
        'APOLLO_PLACEHOLDER_EVENT_BANNER' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=2070',
        'APOLLO_PLACEHOLDER_HIGHLIGHT_BANNER' => 'https://images.unsplash.com/photo-1506157786151-b8491531f063?q=80&w=2070&auto=format&fit=crop',
        'APOLLO_PLACEHOLDER_DJ_IMAGE' => 'https://via.placeholder.com/100x100',
        'APOLLO_PLACEHOLDER_PROMO_IMAGE' => 'https://via.placeholder.com/400x300',
        'APOLLO_PLACEHOLDER_LOCAL_IMAGE' => 'https://via.placeholder.com/400x300',
        
        // Textos
        'APOLLO_PLACEHOLDER_NO_EVENTS' => 'Nenhum evento encontrado.',
        'APOLLO_PLACEHOLDER_EVENTS_ERROR' => 'Erro ao carregar eventos. Tente novamente.',
        'APOLLO_PLACEHOLDER_SEARCH_INPUT' => 'Buscar eventos...',
        'APOLLO_PLACEHOLDER_HIGHLIGHT_TEXT' => 'A Retrospectiva Clubber 2026 está chegando!...',
        'APOLLO_PLACEHOLDER_EVENT_NOT_FOUND' => 'Evento não encontrado',
        'APOLLO_PLACEHOLDER_LINEUP_TBA' => 'Line-up em breve',
        'APOLLO_PLACEHOLDER_LOCATION_TBA' => 'Location TBA',
        'APOLLO_PLACEHOLDER_ROUTE_INPUT' => 'Seu endereço de partida',
        
        // Data
        'APOLLO_PLACEHOLDER_DATE_DAY' => '--',
        'APOLLO_PLACEHOLDER_DATE_MONTH' => '---',
        'APOLLO_PLACEHOLDER_CURRENT_TIME' => '--:--',
    ]);
    
    return isset($placeholders[$key]) ? $placeholders[$key] : $default;
}
```

### 2. Substituir nos Templates

**Antes:**
```php
$banner_url = 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=2070';
```

**Depois:**
```php
$banner_url = apollo_get_placeholder('APOLLO_PLACEHOLDER_EVENT_BANNER');
```

### 3. Permitir Customização via Filter

```php
// No tema ou plugin customizado
add_filter('apollo_placeholders', function($placeholders) {
    $placeholders['APOLLO_PLACEHOLDER_EVENT_BANNER'] = 'https://meusite.com/banner-padrao.jpg';
    return $placeholders;
});
```

---

## ✅ CHECKLIST DE SUBSTITUIÇÃO

### Fase 1: Criar Sistema de Placeholders
- [ ] Criar `includes/placeholders.php`
- [ ] Adicionar função `apollo_get_placeholder()`
- [ ] Definir array de placeholders padrão
- [ ] Adicionar filter `apollo_placeholders`

### Fase 2: Substituir em Templates
- [ ] `templates/portal-discover.php` (5 placeholders)
- [ ] `templates/event-card.php` (3 placeholders)
- [ ] `templates/content-event_listing.php` (3 placeholders)
- [ ] `templates/single-event-standalone.php` (5 placeholders)
- [ ] `templates/single-event-page.php` (4 placeholders)
- [ ] `templates/single-event.php` (6 placeholders)
- [ ] `templates/event-listings-start.php` (1 placeholder)
- [ ] `templates/event-listings-end.php` (2 placeholders)

### Fase 3: Substituir em Código PHP
- [ ] `apollo-events-manager.php` (3 placeholders)

### Fase 4: Testar
- [ ] Verificar fallbacks funcionam
- [ ] Testar customização via filter
- [ ] Validar quebra de layout

---

**Total de Placeholders:** 21  
**Arquivos Afetados:** 8 templates + 1 arquivo principal  
**Prioridade:** 🟡 Média (não bloqueia release)

