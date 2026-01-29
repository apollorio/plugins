# 📋 CENA.PHP - Complete Analysis & Refactor Roadmap

**Analyzed**: `apollo-social/templates/cena/cena.php` (374 lines)
**Template Type**: Canvas mode (uses `wp_head()` and `wp_footer()`)
**Route**: `/cena/` (industry calendar for Rio de Janeiro culture scene)

---

## 1. 📦 INVENTORY

### 1.1 Hardcoded Asset Loads

| Asset             | Type | Line    | Current Method      | Issue                                   |
| ----------------- | ---- | ------- | ------------------- | --------------------------------------- |
| Inter Font        | CSS  | 42-44   | `<link>` hardcode   | ❌ Not enqueued                         |
| Remix Icon        | CSS  | 47      | `<link>` hardcode   | ❌ Not enqueued                         |
| Leaflet CSS       | CSS  | 50      | `<link>` hardcode   | ❌ Not enqueued                         |
| cena-calendar.css | CSS  | 53-54   | `<link>` hardcode   | ❌ Not enqueued, but has cache-busting  |
| Leaflet JS        | JS   | 366     | `<script>` hardcode | ❌ Not enqueued, loads before custom JS |
| cena-calendar.js  | JS   | 369-371 | `<script>` hardcode | ❌ Not enqueued, but has cache-busting  |

**CRITICAL**: All 6 assets bypassed WordPress enqueue system!

### 1.2 Inline Config (window.apolloCenaConfig)

**Location**: Lines 56-70
**Type**: Inline `<script>` with PHP interpolation

| Property         | Source                               | Escaped?                  | Purpose                      |
| ---------------- | ------------------------------------ | ------------------------- | ---------------------------- |
| `restUrl`        | `rest_url('apollo/v1/cena-events')`  | ✅ `esc_js()`             | API endpoint for events CRUD |
| `restNonce`      | `wp_create_nonce('wp_rest')`         | ✅ `esc_js()`             | REST authentication          |
| `geocodeUrl`     | `rest_url('apollo/v1/cena-geocode')` | ✅ `esc_js()`             | Geocoding API for addresses  |
| `today`          | `wp_date('Y-m-d')`                   | ✅ `esc_js()`             | Current date for calendar    |
| `currentYear`    | `wp_date('Y')`                       | ✅ `(int)` cast           | Current year for navigation  |
| `currentMonth`   | `wp_date('m')`                       | ✅ `(int)` cast           | Current month (1-12)         |
| `user.id`        | `get_current_user_id()`              | ✅ No escape needed (int) | Current user ID              |
| `user.username`  | `wp_get_current_user()->user_login`  | ✅ `esc_js()`             | Username for event author    |
| `user.canEdit`   | `current_user_can('edit_posts')`     | ✅ Boolean                | Edit permission check        |
| `user.canDelete` | `current_user_can('delete_posts')`   | ✅ Boolean                | Delete permission check      |

**Security**: ✅ All properly escaped
**Method**: ❌ Should use `wp_localize_script()` instead of inline

### 1.3 JavaScript Dependencies

**Elements that MUST exist before cena-calendar.js runs**:

| Element ID       | Purpose                  | Line | Type                  |
| ---------------- | ------------------------ | ---- | --------------------- |
| `#prev-month`    | Month navigation         | 102  | Button                |
| `#current-month` | Month display            | 105  | Text span             |
| `#next-month`    | Month navigation         | 106  | Button                |
| `#btn-add-event` | Open create modal        | 128  | Button                |
| `#calendar-days` | Mini calendar container  | 150  | Div (empty, JS fills) |
| `#upcoming-list` | Upcoming events          | 157  | Div (empty, JS fills) |
| `#event-map`     | Leaflet map container    | 168  | Div                   |
| `#map-zoom-in`   | Map zoom in              | 170  | Button                |
| `#map-zoom-out`  | Map zoom out             | 173  | Button                |
| `#map-reset`     | Reset map view           | 176  | Button                |
| `#events-title`  | Events section title     | 197  | H2                    |
| `#events-grid`   | Events card container    | 220  | Div (empty, JS fills) |
| `#modal-overlay` | Modal backdrop           | 227  | Div                   |
| `#modal-title`   | Modal heading            | 230  | H2                    |
| `#modal-close`   | Close modal button       | 231  | Button                |
| `#event-form`    | Event form               | 236  | Form                  |
| `#ev-id`         | Hidden field (event ID)  | 237  | Input hidden          |
| `#ev-status`     | Hidden field (status)    | 238  | Input hidden          |
| `#ev-lat`        | Hidden field (latitude)  | 239  | Input hidden          |
| `#ev-lng`        | Hidden field (longitude) | 240  | Input hidden          |
| `#ev-title`      | Event title input        | 248  | Input text            |
| `#ev-date`       | Event date input         | 256  | Input date            |
| `#ev-time`       | Event time input         | 264  | Input time            |
| `#ev-location`   | Event location input     | 272  | Input text            |
| `#ev-type`       | Event type input         | 280  | Input text            |
| `#ev-author`     | Event author input       | 290  | Input text            |
| `#ev-coauthor`   | Event coauthor input     | 299  | Input text            |
| `#ev-tags`       | Event tags input         | 308  | Input text            |

**Data Attributes**:

- `[data-stat="confirmado"]` - Lines 117, 121, 125 (stat value spans)
- `[data-filter="*"]` - Lines 204-217 (filter pills)
- `[data-status="*"]` - Lines 321-344 (status option buttons)

### 1.4 REST Endpoint References

| Endpoint                 | Purpose                    | Usage              | Security                                |
| ------------------------ | -------------------------- | ------------------ | --------------------------------------- |
| `apollo/v1/cena-events`  | CRUD for events            | Main data API      | ⚠️ Need to verify `permission_callback` |
| `apollo/v1/cena-geocode` | Convert address to lat/lng | Location geocoding | ⚠️ Need to verify `permission_callback` |

### 1.5 Capabilities/Permission Checks

| Check                              | Location | Purpose                 | Result                   |
| ---------------------------------- | -------- | ----------------------- | ------------------------ |
| `current_user_can('edit_posts')`   | Line 68  | Can user edit events?   | Used in `user.canEdit`   |
| `current_user_can('delete_posts')` | Line 69  | Can user delete events? | Used in `user.canDelete` |

**MISSING**:

- ❌ No top-level capability check (anyone can access `/cena/`)
- ❌ No role-specific check (should limit to `cena-rio` role?)

---

## 2. 🗺️ DEPENDENCY MAP

### 2.1 What cena-calendar.js Expects from Global Config

**Required Properties** (from `window.apolloCenaConfig`):

```javascript
{
  restUrl: string,        // API endpoint URL
  restNonce: string,      // WP REST nonce for auth
  geocodeUrl: string,     // Geocoding API URL
  today: string,          // Format: 'YYYY-MM-DD'
  currentYear: number,    // 4-digit year
  currentMonth: number,   // 1-12
  user: {
    id: number,
    username: string,
    canEdit: boolean,
    canDelete: boolean
  }
}
```

**Usage Assumptions**:

1. Config exists **before** cena-calendar.js loads
2. All URLs are valid and accessible
3. `restNonce` is fresh (not expired)
4. User permissions match server-side capabilities

### 2.2 DOM Elements Required Before JS Runs

**Critical Elements** (must exist on page load):

- `#event-map` - Leaflet map container (must have explicit height in CSS)
- `#calendar-days` - Mini calendar grid
- `#events-grid` - Main events card list
- `#upcoming-list` - Sidebar upcoming events
- Form inputs (`#ev-*`) - All 8 form fields

**Event Listeners** (JS attaches to these):

- Month navigation: `#prev-month`, `#next-month`
- Map controls: `#map-zoom-in`, `#map-zoom-out`, `#map-reset`
- Modal: `#modal-overlay`, `#modal-close`, `#btn-add-event`
- Filters: `[data-filter]` buttons
- Status picker: `[data-status]` buttons
- Form: `#event-form` submit

### 2.3 External Library Dependencies

| Library    | Version      | Purpose          | Loaded Line        | Critical For       |
| ---------- | ------------ | ---------------- | ------------------ | ------------------ |
| Leaflet    | 1.9.4        | Interactive maps | 50 (CSS), 366 (JS) | Map rendering      |
| Remix Icon | 4.0.0        | Icon font        | 47                 | UI icons           |
| Inter Font | Google Fonts | Typography       | 42-44              | Consistent styling |

**Leaflet-Specific Requirements**:

- Container must have explicit height CSS
- Must be initialized after DOM ready
- Map tiles from OpenStreetMap/CartoDB

---

## 3. ⚠️ RISK ASSESSMENT

### 3.1 Security Issues

| Issue                             | Severity  | Location   | Fix                                              |
| --------------------------------- | --------- | ---------- | ------------------------------------------------ |
| Missing top-level access control  | 🔴 HIGH   | Line 14    | Add capability check for `cena-rio` role         |
| No nonce verification in template | 🟡 MEDIUM | N/A        | Template is read-only, but REST API needs nonces |
| User login in inline JS           | 🟢 LOW    | Line 68    | Already escaped with `esc_js()` ✅               |
| Output escaping complete          | ✅ GOOD   | Throughout | All `esc_*()` functions used correctly           |

**Recommended Add** (after line 14):

```php
// Restrict to cena-rio role or admins
if ( ! current_user_can( 'manage_options' ) && ! in_array( 'cena-rio', wp_get_current_user()->roles, true ) ) {
    wp_die( 'Acesso negado. Apenas membros do CENA::Rio podem acessar esta página.', 403 );
}
```

### 3.2 Race Conditions

| Condition                        | Probability | Impact                           | Current State                                   |
| -------------------------------- | ----------- | -------------------------------- | ----------------------------------------------- |
| JS loads before DOM ready        | 🟡 MEDIUM   | Map fails to initialize          | ⚠️ Depends on `wp_footer()` timing              |
| Config undefined when JS runs    | 🟡 MEDIUM   | JS crashes on undefined property | ⚠️ Inline script is in `<head>`, should be safe |
| Leaflet CSS not loaded           | 🔴 HIGH     | Map displays broken              | ❌ Hardcoded `<link>` in wrong order            |
| Leaflet JS loads after custom JS | 🔴 HIGH     | `L` is undefined error           | ❌ Hardcoded scripts at bottom                  |

**Critical Order Issues**:

1. Leaflet CSS (line 50) loads AFTER Remix Icon (line 47) - should be first
2. Leaflet JS (line 366) loads AFTER cena-calendar.js (line 369) - **WRONG ORDER!**
   - Custom JS tries to use `L.map()` before Leaflet is loaded
   - **High risk of crashes on slow connections**

### 3.3 Leaflet-Specific Gotchas

| Gotcha                       | Risk      | Current Handling                  | Fix Needed                          |
| ---------------------------- | --------- | --------------------------------- | ----------------------------------- |
| Map container hidden on init | 🟡 MEDIUM | Map is always visible             | ✅ OK                               |
| Container has no height      | 🔴 HIGH   | CSS defines `.cena-map-container` | ⚠️ Verify CSS has explicit `height` |
| Map init before tiles load   | 🟢 LOW    | Leaflet handles async tiles       | ✅ OK                               |
| Multiple map instances       | 🟡 MEDIUM | Only one map in template          | ✅ OK                               |
| Map in hidden tab            | 🟡 MEDIUM | Not using tabs                    | ✅ OK                               |

**Must Verify in CSS**:

```css
.cena-map-container,
#event-map {
  height: 400px; /* Or any explicit value */
}
```

---

## 4. 🔧 REFACTOR ROADMAP

### 4.1 Extract to Separate Functions/Classes

#### **Create**: `class-cena-renderer.php`

**Purpose**: Separate business logic from template

```php
<?php
namespace Apollo\Social\Cena;

class Cena_Renderer {

    /**
     * Get view data for template.
     */
    public function get_view_data() {
        return array(
            'user'       => $this->get_user_data(),
            'rest_url'   => rest_url( 'apollo/v1/cena/' ),
            'rest_nonce' => wp_create_nonce( 'wp_rest' ),
            'today'      => wp_date( 'Y-m-d' ),
        );
    }

    /**
     * Get current user data.
     */
    private function get_user_data() {
        $user = wp_get_current_user();
        return array(
            'id'        => $user->ID,
            'username'  => $user->user_login,
            'canEdit'   => current_user_can( 'edit_posts' ),
            'canDelete' => current_user_can( 'delete_posts' ),
        );
    }

    /**
     * Check if user can access CENA.
     */
    public function can_access() {
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        $user = wp_get_current_user();
        return in_array( 'cena-rio', $user->roles, true );
    }

    /**
     * Enqueue assets.
     */
    public function enqueue_assets() {
        // CSS
        wp_enqueue_style(
            'google-fonts-inter',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
            array(),
            null
        );

        wp_enqueue_style(
            'remixicon',
            'https://cdn.jsdelivr.net/npm/remixicon@4.0.0/fonts/remixicon.css',
            array(),
            '4.0.0'
        );

        wp_enqueue_style(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            array(),
            '1.9.4'
        );

        wp_enqueue_style(
            'apollo-cena-calendar',
            plugins_url( 'templates/cena/assets/cena-calendar.css', APOLLO_SOCIAL_PLUGIN_FILE ),
            array( 'leaflet' ),
            filemtime( APOLLO_SOCIAL_PLUGIN_DIR . 'templates/cena/assets/cena-calendar.css' )
        );

        // JS
        wp_enqueue_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            array(),
            '1.9.4',
            true
        );

        wp_enqueue_script(
            'apollo-cena-calendar',
            plugins_url( 'templates/cena/assets/cena-calendar.js', APOLLO_SOCIAL_PLUGIN_FILE ),
            array( 'leaflet' ), // Dependency on Leaflet!
            filemtime( APOLLO_SOCIAL_PLUGIN_DIR . 'templates/cena/assets/cena-calendar.js' ),
            true
        );

        // Localize config
        wp_localize_script(
            'apollo-cena-calendar',
            'apolloCenaConfig',
            array(
                'restUrl'      => rest_url( 'apollo/v1/cena-events' ),
                'restNonce'    => wp_create_nonce( 'wp_rest' ),
                'geocodeUrl'   => rest_url( 'apollo/v1/cena-geocode' ),
                'today'        => wp_date( 'Y-m-d' ),
                'currentYear'  => (int) wp_date( 'Y' ),
                'currentMonth' => (int) wp_date( 'm' ),
                'user'         => $this->get_user_data(),
            )
        );
    }
}
```

#### **Create**: `template-functions.php`

**Purpose**: Template helper functions

```php
<?php
/**
 * Render cena stats block.
 */
function apollo_cena_stats() {
    ?>
    <div class="cena-quick-stats">
        <div class="cena-stat">
            <span class="cena-stat-dot confirmado"></span>
            <span class="cena-stat-value" data-stat="confirmado">0</span>
            <span>confirmados</span>
        </div>
        <div class="cena-stat">
            <span class="cena-stat-dot previsto"></span>
            <span class="cena-stat-value" data-stat="previsto">0</span>
            <span>previstos</span>
        </div>
        <div class="cena-stat">
            <span class="cena-stat-dot adiado"></span>
            <span class="cena-stat-value" data-stat="adiado">0</span>
            <span>adiados</span>
        </div>
    </div>
    <?php
}

/**
 * Render event form modal.
 */
function apollo_cena_event_form() {
    get_template_part( 'cena/parts/event-form' );
}
```

### 4.2 What Should Be Enqueued vs Inline

| Asset/Config      | Current           | Should Be              | Reason                     |
| ----------------- | ----------------- | ---------------------- | -------------------------- |
| **CSS**           |                   |                        |                            |
| Google Fonts      | Inline `<link>`   | Enqueued               | WordPress cache control    |
| Remix Icon        | Inline `<link>`   | Enqueued               | Version management         |
| Leaflet CSS       | Inline `<link>`   | Enqueued               | Dependency tracking        |
| cena-calendar.css | Inline `<link>`   | Enqueued ✅            | Already has cache-busting  |
| **JavaScript**    |                   |                        |                            |
| Leaflet JS        | Inline `<script>` | Enqueued               | Must load BEFORE custom JS |
| cena-calendar.js  | Inline `<script>` | Enqueued ✅            | Dependency on Leaflet      |
| **Config**        |                   |                        |                            |
| apolloCenaConfig  | Inline `<script>` | `wp_localize_script()` | WordPress standard         |

**Benefits of Enqueuing**:

1. **Dependency management**: Leaflet loads before custom JS automatically
2. **Cache busting**: WordPress handles version strings
3. **Conditional loading**: Only loads on `/cena/` page
4. **Minification**: Integrates with optimization plugins
5. **No FOUC**: Proper CSS loading order

### 4.3 Suggested File Structure

```
apollo-social/
├── templates/
│   └── cena/
│       ├── cena.php                    # Main template (simplified)
│       ├── assets/
│       │   ├── cena-calendar.css       # Existing
│       │   └── cena-calendar.js        # Existing
│       └── parts/                      # NEW: Template partials
│           ├── topbar.php              # Lines 83-134
│           ├── sidebar.php             # Lines 143-161
│           ├── map.php                 # Lines 166-194
│           ├── events-grid.php         # Lines 197-223
│           └── event-form-modal.php    # Lines 227-361
│
├── src/                                # NEW: Business logic
│   └── Cena/
│       ├── class-cena-renderer.php     # View data + asset enqueuing
│       ├── class-cena-controller.php   # Request handling
│       └── class-cena-api.php          # REST endpoint registration
│
└── includes/                           # Existing
    └── cena/
        └── template-functions.php      # NEW: Helper functions
```

---

## 5. ✅ REFACTOR CHECKLIST

### Phase 1: Security & Access Control

- [ ] Add capability check at top of `cena.php`
- [ ] Verify REST API endpoints have `permission_callback`
- [ ] Test with different user roles (admin, cena-rio, subscriber)

### Phase 2: Asset Management

- [ ] Create `Cena_Renderer` class
- [ ] Move all hardcoded `<link>` to `wp_enqueue_style()`
- [ ] Move all hardcoded `<script>` to `wp_enqueue_script()`
- [ ] Set Leaflet as dependency for cena-calendar.js
- [ ] Move inline config to `wp_localize_script()`
- [ ] Remove all `<link>` and `<script>` tags from template

### Phase 3: Template Splitting

- [ ] Extract topbar to `parts/topbar.php`
- [ ] Extract sidebar to `parts/sidebar.php`
- [ ] Extract map to `parts/map.php`
- [ ] Extract events grid to `parts/events-grid.php`
- [ ] Extract modal to `parts/event-form-modal.php`
- [ ] Simplify main `cena.php` to use `get_template_part()`

### Phase 4: JavaScript Updates

- [ ] Update cena-calendar.js to handle missing config gracefully
- [ ] Add console errors if Leaflet not loaded
- [ ] Wrap in `DOMContentLoaded` if not already
- [ ] Test on slow connections (throttled network)

### Phase 5: CSS Verification

- [ ] Verify `.cena-map-container` has explicit height
- [ ] Verify `#event-map` has explicit height
- [ ] Test map rendering on page load
- [ ] Test map rendering after window resize

### Phase 6: Testing

- [ ] Test as logged-out user (should see access denied)
- [ ] Test as subscriber (should see access denied)
- [ ] Test as cena-rio role (should have full access)
- [ ] Test as admin (should have full access)
- [ ] Test event creation/edit/delete
- [ ] Test geocoding
- [ ] Test map controls (zoom, reset)
- [ ] Test month navigation
- [ ] Test filter pills
- [ ] Test mobile responsive layout

---

## 6. 🎯 PRIORITY ORDER

### Immediate (Fix Now)

1. **Fix JavaScript load order** - Leaflet must load BEFORE cena-calendar.js
2. **Add access control** - Restrict to cena-rio role
3. **Enqueue Leaflet** - Remove hardcoded `<script>` tag

### High Priority (This Week)

4. **Move config to wp_localize_script()** - Remove inline `<script>`
5. **Enqueue all CSS** - Remove hardcoded `<link>` tags
6. **Create Cena_Renderer class** - Separate logic from template

### Medium Priority (Next Sprint)

7. **Split template into partials** - Better maintainability
8. **Add error handling in JS** - Graceful degradation
9. **Verify CSS map height** - Prevent rendering issues

### Low Priority (Backlog)

10. **Add PHPDoc blocks** - Documentation
11. **Add unit tests** - For Cena_Renderer
12. **Optimize geocoding** - Cache results

---

## 7. 📝 FINAL NOTES

### What's Working Well ✅

- All output properly escaped
- Cache-busting with `filemtime()`
- Semantic HTML structure
- Accessible form labels with tooltips
- Clean ID naming convention
- User capability checks in config

### What Needs Urgent Fixing 🔴

- **JavaScript load order is BROKEN** - Will crash on slow networks
- No access control - Anyone can view `/cena/`
- All assets bypass WordPress enqueue system
- Inline config should use `wp_localize_script()`

### Estimated Refactor Time

- **Phase 1** (Security): 1 hour
- **Phase 2** (Assets): 2 hours
- **Phase 3** (Templates): 3 hours
- **Phase 4** (JS): 1 hour
- **Phase 5** (CSS): 1 hour
- **Phase 6** (Testing): 2 hours

**Total**: ~10 hours of development time

---

**Generated**: 2026-01-10
**Template Version**: 2.1.0
**Analysis Tool**: Claude Sonnet 4.5

🔴 Problemas Críticos Encontrados
ORDEM DE CARREGAMENTO QUEBRADA - Leaflet.js carrega DEPOIS do cena-calendar.js (linha 366 vs 369), vai crashar em conexões lentas
SEM CONTROLE DE ACESSO - Qualquer usuário pode acessar /cena/
6 ASSETS HARDCODED - Nenhum usa wp_enqueue_style/script()
📊 Resumo Rápido
Segurança: ✅ Todos os outputs escapados, mas ❌ sem verificação de capability
Performance: ❌ Assets não enfileirados, sem controle de dependências
Manutenibilidade: 🟡 Template monolítico (374 linhas), precisa dividir em partials
Tempo de Refatoração: ~10 horas total
