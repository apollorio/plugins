# ✅ GEOCODING - Status e Configuração

## 🗺️ GEOCODING JÁ ESTÁ FUNCIONANDO!

### Locais Implementados:

#### 1. WP Event Manager Core ✅
**Arquivo**: `wp-event-manager/forms/wp-event-manager-form-submit-event.php`
**Linhas**: 1236-1258
**Quando**: Ao salvar/atualizar evento

```php
// Geocodifica automaticamente quando tem _event_address
$address = get_post_meta($event_id, '_event_address', true);
// Converte para:
// _event_latitude
// _event_longitude
// _event_city
// _event_state
```

#### 2. Local (Venue) Geocoding ✅
**Arquivo**: `wp-event-manager/forms/wp-event-manager-form-submit-local.php`
**Linhas**: 504-526
**Quando**: Ao salvar/atualizar local

```php
// Geocodifica automaticamente quando tem _local_address
$address = get_post_meta($local_id, '_local_address', true);
// Converte para:
// _local_latitude
// _local_longitude
// _local_city
// _local_state
```

#### 3. Plugin WPEM OSM ✅
**Arquivo**: `wpem-osm/wpem-osm.php`
**Função**: `wem_geocode($address)`
**API**: OpenStreetMap Nominatim

---

## 🎯 COMO FUNCIONA:

### Fluxo Automático:

```
User cria Evento
    ↓
Preenche _event_address: "Av. Rio Branco, 123, Centro, Rio de Janeiro"
    ↓
WP Event Manager salva
    ↓
Hook: event_manager_update_event_data
    ↓
Geocoding automático (linhas 1236-1258)
    ↓
API Nominatim: https://nominatim.openstreetmap.org/search
    ↓
Response: { lat: -22.9068, lon: -43.1729, address: {...} }
    ↓
Salva metafields:
    - _event_latitude: -22.9068
    - _event_longitude: -43.1729
    - _event_city: Rio de Janeiro
    - _event_state: Rio de Janeiro
```

---

## 📍 METAFIELDS DE COORDENADAS:

### Event:
```php
'_event_latitude'        // Auto-preenchido via geocoding
'_event_longitude'       // Auto-preenchido via geocoding
'_event_city'            // Extraído da API
'_event_state'           // Extraído da API
'geolocation_lat'        // Legacy (WP Event Manager antigo)
'geolocation_long'       // Legacy (WP Event Manager antigo)
```

### Local (Venue):
```php
'_local_latitude'        // Auto-preenchido via geocoding
'_local_longitude'       // Auto-preenchido via geocoding
'_local_lat'             // Variação (compatibilidade)
'_local_lng'             // Variação (compatibilidade)
'_local_city'            // Extraído da API
'_local_state'           // Extraído da API
```

---

## 🗺️ EXIBIÇÃO DO MAPA:

### No Template single-event.php:

```php
// Busca coordenadas (em ordem de prioridade):
1. Local ID → _local_latitude, _local_longitude
2. Local ID → _local_lat, _local_lng (fallback)
3. Event → _event_latitude, _event_longitude
4. Event → geolocation_lat, geolocation_long (legacy)
```

### Mapa só aparece se:
```php
if ($local_lat && $local_long) {
    // Renderiza:
    // - Leaflet map
    // - Marker no local
    // - Input para rota
    // - Botão "Track Route"
}
```

---

## 🚗 SISTEMA DE ROTAS:

### Quando user clica "Track Route":

```javascript
// Pega endereço de origem do input
var origin = document.getElementById('origin-input').value;

// Abre Google Maps Directions
var url = 'https://www.google.com/maps/dir/?api=1' +
          '&origin=' + encodeURIComponent(origin) +
          '&destination=' + lat + ',' + lng;

window.open(url, '_blank');
```

**Features**:
- ✅ User digita endereço de partida
- ✅ Clica botão com ícone de avião
- ✅ Abre Google Maps em nova aba
- ✅ Rota já calculada automaticamente

---

## 🎬 YOUTUBE vs IMAGE FALLBACK:

### No Template single-event.php (linhas 96-108):

```php
// Tenta processar YouTube URL
$video_id = '';
if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/', $video_url, $id)) {
    $video_id = $id[1];
}

// Se tem video_id: Embeda YouTube
<?php if ($youtube_embed) : ?>
<div class="video-cover">
    <iframe src="<?php echo $youtube_embed; ?>" ...></iframe>
</div>

// Se NÃO tem: Usa banner image
<?php else : ?>
<img src="<?php echo $banner_url; ?>" alt="...">
<?php endif; ?>
```

**Ordem de fallback**:
1. YouTube embed (se _event_video_url válido)
2. Banner image (_event_banner)
3. Featured image (post thumbnail)
4. Unsplash default

---

## ✅ VERIFICAÇÃO RÁPIDA:

### Geocoding funciona?
```
wp-admin → Event Listings → Add New
Preencha: Event Address: "Rua da Assembleia, 10, Centro, RJ"
Salve
Verifique: Custom Fields deve ter _event_latitude e _event_longitude
```

### Mapa aparece?
```
Acesse evento com coordenadas
Deve renderizar mapa Leaflet
Marker deve estar posicionado
```

### Rota funciona?
```
Digite endereço no input
Clique botão avião
Deve abrir Google Maps com rota
```

### YouTube funciona?
```
Evento com _event_video_url: https://www.youtube.com/watch?v=XXXXX
Deve embedir video no hero
```

---

## 🔧 STATUS FINAL:

✅ Geocoding → JÁ IMPLEMENTADO (WP Event Manager)
✅ Mapas → Leaflet integrado
✅ Rotas → Google Maps Directions
✅ YouTube → Embed com fallback
✅ Coordenadas → Múltiplas fontes (compatibilidade)

**Tudo funcionando!** 🎯

