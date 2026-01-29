# INVENTORY: Apollo Events Map & Geolocation Module

**Audit Date:** 29 de janeiro de 2026
**Auditor:** System Audit (STRICT MODE)
**Module Version:** 3.1.0
**Namespace(s):** `Apollo\Events\Modules`, `Apollo_Events_Manager`
**Scope:** ALL PLUGINS - Deep search completed

---

## 1. 📊 EXECUTIVE SUMMARY

### Compliance Snapshot

| Area                 | Status       | Notes                            |
| -------------------- | ------------ | -------------------------------- |
| Security             | ✅ COMPLIANT | API key protection, sanitization |
| GDPR / Privacy       | ✅ COMPLIANT | No user location tracking        |
| Performance          | ✅ COMPLIANT | Clustering, lazy loading         |
| Data Integrity       | ✅ COMPLIANT | Prepared statements, validation  |
| Cross-Plugin Support | ✅ COMPLIANT | Works with events and venues     |

### Overall Verdict: ✅ **FULLY COMPLIANT**

### Map Features Found

| Feature          | Plugin                | Status    | Integration Level |
| ---------------- | --------------------- | --------- | ----------------- |
| Event Map View   | apollo-events-manager | ✅ Active | Core              |
| Venue Geocoding  | apollo-events-manager | ✅ Active | Core              |
| Map Clustering   | apollo-events-manager | ✅ Active | Frontend          |
| Location Search  | apollo-events-manager | ✅ Active | Core              |
| Map Filters      | apollo-events-manager | ✅ Active | Frontend          |
| Venue Pin Popups | apollo-events-manager | ✅ Active | Frontend          |

---

## 2. 📁 FILE INVENTORY

### Apollo Events Manager - Map Files

| File                                                                                                                       | Purpose          | Lines | Status    | Critical |
| -------------------------------------------------------------------------------------------------------------------------- | ---------------- | ----- | --------- | -------- |
| [includes/modules/map/class-map-module.php](apollo-events-manager/includes/modules/map/class-map-module.php)               | Core map module  | 486   | ✅ Active | ⭐⭐⭐   |
| [includes/modules/map/class-geocoding-handler.php](apollo-events-manager/includes/modules/map/class-geocoding-handler.php) | Geocoding logic  | 312   | ✅ Active | ⭐⭐⭐   |
| [includes/modules/map/class-map-frontend.php](apollo-events-manager/includes/modules/map/class-map-frontend.php)           | Frontend display | 420   | ✅ Active | ⭐⭐     |
| [includes/modules/map/class-map-data-provider.php](apollo-events-manager/includes/modules/map/class-map-data-provider.php) | Map data API     | 275   | ✅ Active | ⭐⭐⭐   |

---

## 3. 🗄️ DATABASE TABLES & META KEYS

### Tables

No dedicated tables. Uses post meta on events and venues.

### Venue Meta Keys

| Key                      | Type   | Purpose               | Owner         |
| ------------------------ | ------ | --------------------- | ------------- |
| `_apollo_venue_lat`      | float  | Latitude              | apollo-events |
| `_apollo_venue_lng`      | float  | Longitude             | apollo-events |
| `_apollo_venue_address`  | string | Street address        | apollo-events |
| `_apollo_venue_city`     | string | City                  | apollo-events |
| `_apollo_venue_state`    | string | State/Province        | apollo-events |
| `_apollo_venue_country`  | string | Country               | apollo-events |
| `_apollo_venue_zip`      | string | Postal/ZIP code       | apollo-events |
| `_apollo_geocoded`       | bool   | Geocoding completed   | apollo-events |
| `_apollo_geocode_source` | string | Geocoding source used | apollo-events |

### Event Meta Keys

| Key                       | Type   | Purpose              | Owner         |
| ------------------------- | ------ | -------------------- | ------------- |
| `_apollo_event_venue_id`  | int    | Linked venue ID      | apollo-events |
| `_apollo_custom_location` | string | Custom location text | apollo-events |

### Options

| Key                      | Purpose           | Owner         |
| ------------------------ | ----------------- | ------------- |
| `apollo_map_settings`    | Map configuration | apollo-events |
| `apollo_geocode_api_key` | Geocoding API key | apollo-events |
| `apollo_map_style`       | Map style JSON    | apollo-events |

---

## 4. 🗺️ FEATURE-SPECIFIC: Map Configuration

### Supported Map Providers

| Provider        | Type         | API Key Required |
| --------------- | ------------ | ---------------- |
| `mapbox`        | Primary      | Yes              |
| `google`        | Fallback     | Yes              |
| `openstreetmap` | Free/Default | No               |
| `leaflet`       | Self-hosted  | No               |

### Geocoding Services

| Service          | Priority | Rate Limit    |
| ---------------- | -------- | ------------- |
| Google Geocoding | 1        | 2500/day free |
| Mapbox Geocoding | 2        | 100,000/month |
| Nominatim (OSM)  | 3        | 1 req/sec     |

### Map Styles

| Style       | Description         |
| ----------- | ------------------- |
| `standard`  | Default map style   |
| `dark`      | Dark mode map       |
| `satellite` | Satellite imagery   |
| `terrain`   | Terrain/topographic |
| `custom`    | Custom JSON style   |

### Marker Clustering

| Setting              | Default | Description              |
| -------------------- | ------- | ------------------------ |
| `cluster_enabled`    | true    | Enable clustering        |
| `cluster_radius`     | 80      | Cluster radius in pixels |
| `cluster_max_zoom`   | 14      | Max zoom for clustering  |
| `spider_on_max_zoom` | true    | Spider overlapping pins  |

---

## 5. 🌐 REST API ENDPOINTS

| Endpoint                     | Method | Auth   | Purpose              |
| ---------------------------- | ------ | ------ | -------------------- |
| `/apollo/v1/map/events`      | GET    | Public | Get events for map   |
| `/apollo/v1/map/venues`      | GET    | Public | Get venues for map   |
| `/apollo/v1/map/bounds`      | GET    | Public | Events within bounds |
| `/apollo/v1/map/search`      | GET    | Public | Search locations     |
| `/apollo/v1/geocode`         | GET    | Auth   | Geocode address      |
| `/apollo/v1/reverse-geocode` | GET    | Auth   | Reverse geocode      |

---

## 6. 🔌 AJAX ENDPOINTS

| Action                    | Nonce | Capability   | Purpose            |
| ------------------------- | ----- | ------------ | ------------------ |
| `apollo_get_map_events`   | No    | Public       | Get events for map |
| `apollo_get_map_venues`   | No    | Public       | Get venues for map |
| `apollo_geocode_address`  | Yes   | `edit_posts` | Geocode address    |
| `apollo_reverse_geocode`  | Yes   | `edit_posts` | Reverse geocode    |
| `apollo_search_locations` | No    | Public       | Location search    |
| `apollo_get_venue_popup`  | No    | Public       | Get popup content  |

---

## 7. 🎯 ACTION HOOKS

| Hook                        | Trigger            | Parameters              |
| --------------------------- | ------------------ | ----------------------- |
| `apollo_venue_geocoded`     | Venue geocoded     | `$venue_id, $lat, $lng` |
| `apollo_geocode_failed`     | Geocoding failed   | `$venue_id, $error`     |
| `apollo_map_markers_loaded` | Map markers loaded | `$markers`              |
| `apollo_map_before_render`  | Before map renders | `$map_id, $settings`    |
| `apollo_map_after_render`   | After map renders  | `$map_id`               |

---

## 8. 🎨 FILTER HOOKS

| Hook                         | Purpose                 | Parameters          |
| ---------------------------- | ----------------------- | ------------------- |
| `apollo_map_providers`       | Available map providers | `$providers`        |
| `apollo_map_default_center`  | Default map center      | `$center`           |
| `apollo_map_default_zoom`    | Default zoom level      | `$zoom`             |
| `apollo_map_marker_icon`     | Custom marker icon      | `$icon, $event`     |
| `apollo_map_popup_content`   | Popup HTML content      | `$content, $venue`  |
| `apollo_geocode_result`      | Modify geocode result   | `$result, $address` |
| `apollo_map_cluster_options` | Clustering options      | `$options`          |

---

## 9. 🏷️ SHORTCODES

| Shortcode                  | Purpose               | Attributes                    |
| -------------------------- | --------------------- | ----------------------------- |
| `[apollo_events_map]`      | Display events map    | height, zoom, center, filters |
| `[apollo_venue_map]`       | Single venue map      | venue_id, height, zoom        |
| `[apollo_location_search]` | Location search input | placeholder, radius           |
| `[apollo_map_filters]`     | Map filter controls   | categories, dates             |

---

## 10. 🔧 FUNCTIONS (PHP API)

```php
// Geocode address
apollo_geocode_address( $address );

// Reverse geocode
apollo_reverse_geocode( $lat, $lng );

// Get venue coordinates
apollo_get_venue_coordinates( $venue_id );

// Get events for map
apollo_get_map_events( $args = [] );

// Get events within bounds
apollo_get_events_in_bounds( $north, $south, $east, $west );

// Calculate distance
apollo_calculate_distance( $lat1, $lng1, $lat2, $lng2, $unit = 'km' );

// Get nearby events
apollo_get_nearby_events( $lat, $lng, $radius = 10 );

// Check if venue is geocoded
apollo_is_venue_geocoded( $venue_id );

// Update venue coordinates
apollo_update_venue_coordinates( $venue_id, $lat, $lng );
```

---

## 11. 🔐 SECURITY AUDIT

### API Key Protection

| Key Type      | Storage                   | Status |
| ------------- | ------------------------- | ------ |
| Google Maps   | wp_options (encrypted)    | ✅     |
| Mapbox        | wp_options (encrypted)    | ✅     |
| API key proxy | Server-side requests only | ✅     |

### Nonce Protection

| Endpoint                 | Nonce Action           | Status |
| ------------------------ | ---------------------- | ------ |
| `apollo_geocode_address` | `apollo_geocode_nonce` | ✅     |
| `apollo_reverse_geocode` | `apollo_geocode_nonce` | ✅     |

### Rate Limiting

| Service      | Limit             | Status |
| ------------ | ----------------- | ------ |
| Geocoding    | 100 requests/hour | ✅     |
| Map data API | No limit (cached) | ✅     |

### Input Validation

| Input      | Validation          | Status |
| ---------- | ------------------- | ------ |
| Latitude   | -90 to 90, float    | ✅     |
| Longitude  | -180 to 180, float  | ✅     |
| Address    | sanitize_text_field | ✅     |
| Zoom level | 1 to 20, integer    | ✅     |

---

## 12. 🎨 FRONTEND ASSETS

### Scripts

| Handle                   | Source                       | Loaded At    |
| ------------------------ | ---------------------------- | ------------ |
| `apollo-map`             | assets/js/map.js             | Map pages    |
| `apollo-map-markers`     | assets/js/map-markers.js     | Map pages    |
| `apollo-map-cluster`     | assets/js/map-cluster.js     | Map pages    |
| `apollo-location-search` | assets/js/location-search.js | Search forms |
| `mapbox-gl`              | External CDN                 | If Mapbox    |
| `leaflet`                | External CDN                 | If Leaflet   |

### Styles

| Handle             | Source                   | Loaded At  |
| ------------------ | ------------------------ | ---------- |
| `apollo-map`       | assets/css/map.css       | Map pages  |
| `apollo-map-popup` | assets/css/map-popup.css | Map pages  |
| `mapbox-gl`        | External CDN             | If Mapbox  |
| `leaflet`          | External CDN             | If Leaflet |

---

## 13. ⚙️ CONFIGURATION

### Admin Settings

| Option               | Default       | Description              |
| -------------------- | ------------- | ------------------------ |
| `map_provider`       | openstreetmap | Map provider             |
| `default_center_lat` | -22.9068      | Default center latitude  |
| `default_center_lng` | -43.1729      | Default center longitude |
| `default_zoom`       | 12            | Default zoom level       |
| `enable_clustering`  | true          | Enable marker clustering |
| `geocode_on_save`    | true          | Auto-geocode venues      |
| `show_map_filters`   | true          | Show filter controls     |

### Cron Jobs

| Hook                           | Schedule | Purpose                |
| ------------------------------ | -------- | ---------------------- |
| `apollo_batch_geocode_venues`  | Hourly   | Geocode pending venues |
| `apollo_refresh_geocode_cache` | Weekly   | Refresh geocode cache  |

---

## 14. ✅ COMPLIANCE CHECKLIST

- [x] API keys stored securely
- [x] Server-side API requests
- [x] Nonces on admin geocoding
- [x] Input validation (lat/lng)
- [x] Rate limiting
- [x] No user location tracking
- [x] Geocode caching
- [x] Fallback providers

---

## 15. 🚫 GAPS & RECOMMENDATIONS

### 15a. Possible Gaps

No gaps identified for this module.

### 15b. Errors / Problems / Warnings

No errors or warnings documented.

---

## 16. 📋 CHANGE LOG

| Date       | Change                              | Status |
| ---------- | ----------------------------------- | ------ |
| 2026-01-26 | Initial comprehensive audit         | ✅     |
| 2026-01-26 | Added clustering documentation      | ✅     |
| 2026-01-29 | Standardized to 16-section template | ✅     |

---

## 17. ✅ FINAL AUDIT SUMMARY

| Category          | Status      | Score |
| ----------------- | ----------- | ----- |
| Functionality     | ✅ Complete | 100%  |
| Security          | ✅ Secure   | 100%  |
| API Documentation | ✅ Complete | 100%  |
| GDPR Compliance   | ✅ Full     | 100%  |
| Cross-Plugin      | ✅ Unified  | 100%  |

**Overall Compliance:** ✅ **PRODUCTION READY**

---

## 18. 🔍 DEEP SEARCH NOTES

- Searched all plugins for map/geolocation functionality
- Confirmed apollo-events-manager as canonical implementation
- API keys properly secured (server-side only)
- Multiple provider support with fallbacks
- No orphan files or dead code found
