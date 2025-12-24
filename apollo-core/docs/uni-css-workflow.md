# UNI.CSS Workflow Guide - Source Files vs CDN

## 🏗️ Architecture Overview

The UNI.CSS system uses a **dual-source architecture**:

```
┌─────────────────────────────────────────────────────────┐
│  SOURCE FILES (Version Control)                        │
│  apollo-core/templates/design-library/                 │
│  └── global assets-apollo-rio-br/                       │
│      ├── uni.css          ← Edit here                  │
│      ├── base.js          ← Edit here                  │
│      ├── animate.css      ← Edit here                  │
│      └── event-page.js    ← Edit here                  │
└─────────────────────────────────────────────────────────┘
                    ↓ (Upload when ready)
┌─────────────────────────────────────────────────────────┐
│  PRODUCTION CDN (Live Site)                            │
│  https://assets.apollo.rio.br/                         │
│  ├── uni.css          ← Served to users                │
│  ├── base.js          ← Served to users                │
│  ├── animate.css      ← Served to users                │
│  └── event-page.js    ← Served to users                │
└─────────────────────────────────────────────────────────┘
```

## 📁 File Locations

### **Source Files (Edit These)**
```
apollo-core/
└── templates/
    └── design-library/
        └── global assets-apollo-rio-br/
            ├── uni.css          ← Main design system CSS
            ├── base.js          ← Global JavaScript
            ├── animate.css      ← Animation utilities
            └── event-page.js    ← Event page scripts
```

**These files are:**
- ✅ In version control (Git)
- ✅ Editable locally
- ✅ Used for local development
- ✅ Source of truth for CDN uploads

### **CDN Files (Production)**
```
https://assets.apollo.rio.br/
├── uni.css
├── base.js
├── animate.css
└── event-page.js
```

**These files are:**
- 🌐 Served to production sites
- 🚀 Fast CDN delivery
- 📦 Cached globally
- ⚠️ **NOT in version control** (upload manually)

## 🔄 How It Works

The `Apollo_Global_Assets` class automatically chooses which source to use:

```php
// In class-global-assets.php
private static $use_cdn = true;  // Default: Use CDN

public static function get_asset_url(string $asset): string {
    if (self::$use_cdn) {
        // Production: Use CDN
        return 'https://assets.apollo.rio.br/' . $asset;
    } else {
        // Development: Use local files
        return APOLLO_CORE_PLUGIN_URL . 'templates/design-library/global assets-apollo-rio-br/' . $asset;
    }
}
```

## 🛠️ Development Workflow

### **Step 1: Edit Source Files Locally**

Edit files in `apollo-core/templates/design-library/global assets-apollo-rio-br/`:

```bash
# Example: Edit uni.css
code apollo-core/templates/design-library/global\ assets-apollo-rio-br/uni.css
```

### **Step 2: Test Locally (Use Local Files)**

Force local files for development:

```php
// In wp-config.php or functions.php (temporary)
add_filter('apollo_use_cdn_assets', '__return_false');

// OR programmatically
apollo_set_use_cdn(false);
```

Now WordPress will load:
- `http://yoursite.local/wp-content/plugins/apollo-core/templates/design-library/global assets-apollo-rio-br/uni.css`

### **Step 3: Upload to CDN When Ready**

Once tested, upload files to `https://assets.apollo.rio.br/`:

**Option A: FTP/SFTP**
```bash
# Upload these files:
uni.css          → https://assets.apollo.rio.br/uni.css
base.js          → https://assets.apollo.rio.br/base.js
animate.css      → https://assets.apollo.rio.br/animate.css
event-page.js    → https://assets.apollo.rio.br/event-page.js
```

**Option B: Git Deploy Hook**
```bash
# If you have a deploy script
./deploy-assets.sh
```

**Option C: Manual Upload**
- Access your CDN server
- Upload files to the root directory
- Ensure files are publicly accessible

### **Step 4: Update Version Number**

After uploading, bump the version in `class-global-assets.php`:

```php
private static $asset_versions = [
    'uni.css' => '2.0.1',  // ← Increment this
    'base.js' => '2.0.1',  // ← Increment this
    // ...
];
```

This forces browsers to reload the new files (cache busting).

## ⚙️ Configuration

### **Toggle CDN/Local Mode**

**Method 1: Filter Hook**
```php
// In functions.php or plugin
add_filter('apollo_use_cdn_assets', '__return_false'); // Use local
add_filter('apollo_use_cdn_assets', '__return_true');  // Use CDN (default)
```

**Method 2: Function Call**
```php
// Use local files
apollo_set_use_cdn(false);

// Use CDN
apollo_set_use_cdn(true);
```

**Method 3: Environment Variable**
```php
// In wp-config.php
define('APOLLO_USE_LOCAL_ASSETS', true);

// Then in class-global-assets.php, add:
if (defined('APOLLO_USE_LOCAL_ASSETS') && APOLLO_USE_LOCAL_ASSETS) {
    self::$use_cdn = false;
}
```

### **Check Current Mode**

```php
if (apollo_is_using_cdn()) {
    echo "Using CDN: https://assets.apollo.rio.br/uni.css";
} else {
    echo "Using local files";
}
```

## 📋 Recommended Workflow

### **For Daily Development:**

1. **Edit locally** → `apollo-core/templates/design-library/global assets-apollo-rio-br/uni.css`
2. **Use local mode** → `apollo_set_use_cdn(false);`
3. **Test changes** → Refresh browser, see updates immediately
4. **Commit to Git** → `git commit -m "Update uni.css: add new component"`

### **For Production Release:**

1. **Test thoroughly** in local mode
2. **Upload to CDN** → Copy files to `https://assets.apollo.rio.br/`
3. **Bump version** → Update `$asset_versions` in `class-global-assets.php`
4. **Deploy code** → Push to production
5. **Verify CDN** → Check `https://assets.apollo.rio.br/uni.css` loads correctly

## 🚨 Important Notes

### ✅ **DO:**
- ✅ Edit source files in `apollo-core/templates/design-library/global assets-apollo-rio-br/`
- ✅ Use local mode for development
- ✅ Upload to CDN before production release
- ✅ Update version numbers after CDN upload
- ✅ Keep source files in version control

### ❌ **DON'T:**
- ❌ Edit CDN files directly (they'll be overwritten)
- ❌ Skip version bumping (users won't see updates)
- ❌ Upload untested files to CDN
- ❌ Forget to commit source file changes

## 🔍 Verification

### **Check Which Source Is Active:**

```php
// In browser console or PHP
console.log('CDN Mode:', apollo_is_using_cdn());

// Check actual URL being loaded
var link = document.querySelector('link[href*="uni.css"]');
console.log('Loaded from:', link.href);
```

### **Verify CDN Files:**

```bash
# Check if CDN file exists
curl -I https://assets.apollo.rio.br/uni.css

# Should return: HTTP/1.1 200 OK
```

## 📝 Summary

| Question | Answer |
|----------|--------|
| **Where do I edit files?** | `apollo-core/templates/design-library/global assets-apollo-rio-br/` |
| **Where are files served from?** | CDN (`https://assets.apollo.rio.br/`) by default, local files when `$use_cdn = false` |
| **Do I need to upload to CDN?** | Yes, for production. Local files are for development only. |
| **How do I switch modes?** | `apollo_set_use_cdn(false)` for local, `apollo_set_use_cdn(true)` for CDN |
| **What about version control?** | Source files are in Git, CDN files are NOT (upload manually) |

---

**TL;DR:** 
- 📝 **Edit** → `apollo-core/templates/design-library/global assets-apollo-rio-br/uni.css`
- 🧪 **Test** → Use local mode (`apollo_set_use_cdn(false)`)
- 🚀 **Deploy** → Upload to `https://assets.apollo.rio.br/uni.css`
- ✅ **Done** → Production uses CDN automatically

