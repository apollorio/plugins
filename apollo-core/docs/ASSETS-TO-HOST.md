# Assets to Host on assets.apollo.rio.br

**Generated:** 2025-12-20

All remixicon CDN references have been replaced with `icon.js`. However, the following assets are referenced in approved HTML templates and need to be hosted on `assets.apollo.rio.br`:

---

## ✅ Already Hosted (Verified)

| Asset | URL | Status |
|-------|-----|--------|
| uni.css | `https://assets.apollo.rio.br/uni.css` | ✅ Active |
| base.js | `https://assets.apollo.rio.br/base.js` | ✅ Active |
| icon.js | `https://assets.apollo.rio.br/icon.js` | ✅ Active |
| SVG Icons | `https://assets.apollo.rio.br/i/*.svg` | ✅ Active |
| Default Event Image | `https://assets.apollo.rio.br/img/default-event.jpg` | ✅ Active |
| Favicon | `https://assets.apollo.rio.br/img/neon-green.webp` | ✅ Active |

---

## ⚠️ Needs to be Downloaded & Hosted

### 1. Phosphor Icons (phosphor.js)
**Source:** `https://unpkg.com/@phosphor-icons/web`  
**Target:** `https://assets.apollo.rio.br/phosphor.js`  
**Used in:** forms.html

**Download command:**
```bash
curl -o phosphor.js "https://unpkg.com/@phosphor-icons/web@2.1.1/lib/index.umd.js"
```

**Alternative:** Create a similar SVG loader like icon.js for Phosphor icons, storing SVGs at `/i/ph-*.svg`

---

### 2. Bootstrap CSS (bootstrap.min.css)
**Source:** `https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css`  
**Target:** `https://assets.apollo.rio.br/bootstrap.min.css`  
**Used in:** body_docs_editor.html

**Download command:**
```bash
curl -o bootstrap.min.css "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
```

---

### 3. jQuery (jquery.min.js)
**Source:** `https://code.jquery.com/jquery-3.6.0.min.js`  
**Target:** `https://assets.apollo.rio.br/jquery.min.js`  
**Used in:** body_docs_editor.html

**Download command:**
```bash
curl -o jquery.min.js "https://code.jquery.com/jquery-3.6.0.min.js"
```

---

### 4. Motion One (motion.js)
**Source:** `https://unpkg.com/@motionone/dom@10.16.4/dist/index.js`  
**Target:** `https://assets.apollo.rio.br/motion.js`  
**Used in:** eventos - dj - single.html

**Download command:**
```bash
curl -o motion.js "https://unpkg.com/@motionone/dom@10.16.4/dist/index.js"
```

---

### 5. Tailwind CSS (tailwind.js) - OPTIONAL
**Source:** `https://cdn.tailwindcss.com`  
**Target:** `https://assets.apollo.rio.br/tailwind.js`  
**Used in:** eventos - dj - single.html

**Note:** Consider removing Tailwind dependency and using uni.css exclusively. If Tailwind is required:

**Download command:**
```bash
curl -o tailwind.js "https://cdn.tailwindcss.com/3.4.1?plugins=forms,typography"
```

---

## External APIs (Keep as-is)

These are third-party APIs that cannot be self-hosted:

| API | URL | Reason |
|-----|-----|--------|
| SoundCloud Player | `https://w.soundcloud.com/player/api.js` | Required for vinyl player widget |
| Google Fonts | `https://fonts.googleapis.com/*` | Google Fonts CDN (or download locally) |

---

## Complete Download Script

```bash
#!/bin/bash
# Download all required assets for assets.apollo.rio.br

mkdir -p ~/apollo-cdn-assets
cd ~/apollo-cdn-assets

# Phosphor Icons
curl -o phosphor.js "https://unpkg.com/@phosphor-icons/web@2.1.1/lib/index.umd.js"

# Bootstrap
curl -o bootstrap.min.css "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"

# jQuery  
curl -o jquery.min.js "https://code.jquery.com/jquery-3.6.0.min.js"

# Motion One
curl -o motion.js "https://unpkg.com/@motionone/dom@10.16.4/dist/index.js"

# Tailwind (optional)
curl -o tailwind.js "https://cdn.tailwindcss.com/3.4.1"

echo "Done! Upload these files to assets.apollo.rio.br/"
```

---

## Files Modified

The following files were updated to use `assets.apollo.rio.br` instead of external CDNs:

### HTML Templates (approved templates/)
1. `forms.html` - remixicon → icon.js, phosphor → phosphor.js
2. `body_docs_editor.html` - remixicon → icon.js, bootstrap/jquery → local
3. `body_evento_eventoID  ----single page.html` - remixicon → icon.js
4. `body_eventos   ----list all.html` - remixicon → icon.js
5. `eventos - dj - single.html` - remixicon → icon.js, motion/tailwind → local
6. `eventos - evento - single.html` - remixicon → icon.js
7. `eventos - eventos - listing.html` - remixicon → icon.js
8. `eventos - local - single.html` - remixicon → icon.js
9. `login - register.html` - remixicon → icon.js

### PHP Files
1. `apollo-core/src/Assets/class-apollo-assets-loader.php` - remixicon CSS → icon.js script

---

## Icon.js Usage

The icon.js loader works with any `<i class="ri-*">` element:

```html
<!-- Before (CSS font) -->
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet">
<i class="ri-home-line"></i>

<!-- After (SVG via icon.js) -->
<script src="https://assets.apollo.rio.br/icon.js" defer></script>
<i class="ri-home-line"></i>
```

The script automatically:
1. Converts `ri-*-line` → `*-v.svg`
2. Converts `ri-*-fill` → `*-s.svg`
3. Loads SVGs from `https://assets.apollo.rio.br/i/`
4. Uses CSS masks for color inheritance via `currentColor`
