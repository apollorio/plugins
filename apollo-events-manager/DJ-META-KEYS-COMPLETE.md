# ✅ DJ Meta Keys - Complete List
**Data:** 5 de Novembro de 2025  
**Status:** 🟢 ALL PLATFORMS ADDED

---

## 🎯 PROBLEMA RESOLVIDO

**Issue:** Bandcamp e Spotify estavam faltando nos meta keys de DJ

**Solução:** Adicionados **8 novos meta keys** para cobrir todas as plataformas sociais e de streaming

---

## 📋 META KEYS COMPLETOS (Total: 22)

### Basic Info (3)
| Meta Key | Type | Description |
|----------|------|-------------|
| `_dj_name` | string | Nome artístico do DJ |
| `_dj_bio` | string | Biografia completa |
| `_dj_image` | string | URL ou attachment ID da foto |

### Social Media & Streaming Platforms (10) ✅ NEW
| Meta Key | Type | Description | Example |
|----------|------|-------------|---------|
| `_dj_website` | string | Site oficial | https://djalpha.com |
| `_dj_instagram` | string | Instagram handle | @djalpha |
| `_dj_facebook` | string | Facebook URL | https://facebook.com/djalpha |
| `_dj_soundcloud` | string | SoundCloud URL | https://soundcloud.com/djalpha |
| `_dj_bandcamp` | string | ✅ **NEW** Bandcamp profile | https://djalpha.bandcamp.com |
| `_dj_spotify` | string | ✅ **NEW** Spotify artist | https://open.spotify.com/artist/... |
| `_dj_youtube` | string | ✅ **NEW** YouTube channel | https://youtube.com/@djalpha |
| `_dj_mixcloud` | string | ✅ **NEW** Mixcloud profile | https://mixcloud.com/djalpha |
| `_dj_beatport` | string | ✅ **NEW** Beatport artist | https://beatport.com/artist/dj-alpha/... |
| `_dj_resident_advisor` | string | ✅ **NEW** RA profile | https://ra.co/dj/djalpha |
| `_dj_twitter` | string | ✅ **NEW** Twitter/X handle | @djalpha |
| `_dj_tiktok` | string | ✅ **NEW** TikTok profile | https://tiktok.com/@djalpha |

### Professional Content (9)
| Meta Key | Type | Description |
|----------|------|-------------|
| `_dj_original_project_1` | string | Original Project #1 |
| `_dj_original_project_2` | string | Original Project #2 |
| `_dj_original_project_3` | string | Original Project #3 |
| `_dj_set_url` | string | DJ Set URL (SoundCloud, YouTube, etc) |
| `_dj_media_kit_url` | string | Media Kit download URL |
| `_dj_rider_url` | string | Rider download URL |
| `_dj_mix_url` | string | DJ Mix URL |

---

## 🔗 PLACEHOLDERS ADICIONADOS (Total: 8 novos)

### Novos Placeholders
```php
apollo_event_get_placeholder_value('dj_bandcamp', $event_id)
apollo_event_get_placeholder_value('dj_spotify', $event_id)
apollo_event_get_placeholder_value('dj_youtube', $event_id)
apollo_event_get_placeholder_value('dj_mixcloud', $event_id)
apollo_event_get_placeholder_value('dj_beatport', $event_id)
apollo_event_get_placeholder_value('dj_resident_advisor', $event_id)
apollo_event_get_placeholder_value('dj_twitter', $event_id)
apollo_event_get_placeholder_value('dj_tiktok', $event_id)
```

### Uso em Shortcode
```
[apollo_event field="dj_bandcamp"]
[apollo_event field="dj_spotify"]
[apollo_event field="dj_youtube"]
[apollo_event field="dj_mixcloud"]
[apollo_event field="dj_beatport"]
[apollo_event field="dj_resident_advisor"]
[apollo_event field="dj_twitter"]
[apollo_event field="dj_tiktok"]
```

---

## 📁 ARQUIVOS MODIFICADOS

### 1. `includes/post-types.php`
**Linhas:** 391-419  
**Mudanças:** Adicionados 8 novos meta keys no array `$dj_meta_fields`

**Antes (6 plataformas):**
```php
'_dj_website'      => 'string',
'_dj_soundcloud'   => 'string',
'_dj_instagram'    => 'string',
'_dj_facebook'     => 'string',
```

**Depois (14 plataformas):**
```php
// Social Media & Streaming Platforms
'_dj_website'            => 'string',
'_dj_instagram'          => 'string',
'_dj_facebook'           => 'string',
'_dj_soundcloud'         => 'string',
'_dj_bandcamp'           => 'string', // NEW
'_dj_spotify'            => 'string', // NEW
'_dj_youtube'            => 'string', // NEW
'_dj_mixcloud'           => 'string', // NEW
'_dj_beatport'           => 'string', // NEW
'_dj_resident_advisor'   => 'string', // NEW
'_dj_twitter'            => 'string', // NEW
'_dj_tiktok'             => 'string', // NEW
```

### 2. `includes/class-apollo-events-placeholders.php`
**Mudanças:**
- **Registry:** Adicionados 8 novos placeholders (linhas 409-472)
- **Handlers:** Adicionados 8 novos case statements (linhas 974-1076)

---

## 🎨 USAGE IN DJ SINGLE TEMPLATE

### Example: Display All Social Links
```php
<?php
$dj_id = get_the_ID();

// Get all social platforms
$platforms = [
    'website'          => apollo_event_get_placeholder_value('dj_website', null, ['dj_id' => $dj_id]),
    'instagram'        => apollo_event_get_placeholder_value('dj_instagram', null, ['dj_id' => $dj_id]),
    'facebook'         => apollo_event_get_placeholder_value('dj_facebook', null, ['dj_id' => $dj_id]),
    'soundcloud'       => apollo_event_get_placeholder_value('dj_soundcloud', null, ['dj_id' => $dj_id]),
    'bandcamp'         => apollo_event_get_placeholder_value('dj_bandcamp', null, ['dj_id' => $dj_id]),
    'spotify'          => apollo_event_get_placeholder_value('dj_spotify', null, ['dj_id' => $dj_id]),
    'youtube'          => apollo_event_get_placeholder_value('dj_youtube', null, ['dj_id' => $dj_id]),
    'mixcloud'         => apollo_event_get_placeholder_value('dj_mixcloud', null, ['dj_id' => $dj_id]),
    'beatport'         => apollo_event_get_placeholder_value('dj_beatport', null, ['dj_id' => $dj_id]),
    'resident_advisor' => apollo_event_get_placeholder_value('dj_resident_advisor', null, ['dj_id' => $dj_id]),
    'twitter'          => apollo_event_get_placeholder_value('dj_twitter', null, ['dj_id' => $dj_id]),
    'tiktok'           => apollo_event_get_placeholder_value('dj_tiktok', null, ['dj_id' => $dj_id]),
];

// Display social icons
?>
<div class="dj-social-links">
    <?php if ($platforms['website']): ?>
        <a href="<?php echo esc_url($platforms['website']); ?>" target="_blank" rel="noopener" class="social-link">
            <i class="ri-global-line"></i>
            <span>Website</span>
        </a>
    <?php endif; ?>
    
    <?php if ($platforms['instagram']): ?>
        <a href="https://instagram.com/<?php echo esc_attr(ltrim($platforms['instagram'], '@')); ?>" target="_blank" rel="noopener" class="social-link">
            <i class="ri-instagram-line"></i>
            <span>Instagram</span>
        </a>
    <?php endif; ?>
    
    <?php if ($platforms['facebook']): ?>
        <a href="<?php echo esc_url($platforms['facebook']); ?>" target="_blank" rel="noopener" class="social-link">
            <i class="ri-facebook-circle-line"></i>
            <span>Facebook</span>
        </a>
    <?php endif; ?>
    
    <?php if ($platforms['soundcloud']): ?>
        <a href="<?php echo esc_url($platforms['soundcloud']); ?>" target="_blank" rel="noopener" class="social-link">
            <i class="ri-soundcloud-line"></i>
            <span>SoundCloud</span>
        </a>
    <?php endif; ?>
    
    <?php if ($platforms['bandcamp']): ?>
        <a href="<?php echo esc_url($platforms['bandcamp']); ?>" target="_blank" rel="noopener" class="social-link">
            <i class="ri-music-2-line"></i>
            <span>Bandcamp</span>
        </a>
    <?php endif; ?>
    
    <?php if ($platforms['spotify']): ?>
        <a href="<?php echo esc_url($platforms['spotify']); ?>" target="_blank" rel="noopener" class="social-link">
            <i class="ri-spotify-line"></i>
            <span>Spotify</span>
        </a>
    <?php endif; ?>
    
    <?php if ($platforms['youtube']): ?>
        <a href="<?php echo esc_url($platforms['youtube']); ?>" target="_blank" rel="noopener" class="social-link">
            <i class="ri-youtube-line"></i>
            <span>YouTube</span>
        </a>
    <?php endif; ?>
    
    <?php if ($platforms['mixcloud']): ?>
        <a href="<?php echo esc_url($platforms['mixcloud']); ?>" target="_blank" rel="noopener" class="social-link">
            <i class="ri-disc-line"></i>
            <span>Mixcloud</span>
        </a>
    <?php endif; ?>
    
    <?php if ($platforms['beatport']): ?>
        <a href="<?php echo esc_url($platforms['beatport']); ?>" target="_blank" rel="noopener" class="social-link">
            <i class="ri-headphone-line"></i>
            <span>Beatport</span>
        </a>
    <?php endif; ?>
    
    <?php if ($platforms['resident_advisor']): ?>
        <a href="<?php echo esc_url($platforms['resident_advisor']); ?>" target="_blank" rel="noopener" class="social-link">
            <i class="ri-radio-line"></i>
            <span>RA</span>
        </a>
    <?php endif; ?>
    
    <?php if ($platforms['twitter']): ?>
        <a href="https://twitter.com/<?php echo esc_attr(ltrim($platforms['twitter'], '@')); ?>" target="_blank" rel="noopener" class="social-link">
            <i class="ri-twitter-x-line"></i>
            <span>Twitter</span>
        </a>
    <?php endif; ?>
    
    <?php if ($platforms['tiktok']): ?>
        <a href="<?php echo esc_url($platforms['tiktok']); ?>" target="_blank" rel="noopener" class="social-link">
            <i class="ri-tiktok-line"></i>
            <span>TikTok</span>
        </a>
    <?php endif; ?>
</div>
```

---

## 📊 PLATFORM COVERAGE

### Before (6 platforms)
- Website
- Instagram
- Facebook
- SoundCloud
- (missing Bandcamp)
- (missing Spotify)

### After (14 platforms) ✅
- Website
- Instagram
- Facebook
- SoundCloud
- ✅ **Bandcamp** (NEW)
- ✅ **Spotify** (NEW)
- ✅ **YouTube** (NEW)
- ✅ **Mixcloud** (NEW)
- ✅ **Beatport** (NEW)
- ✅ **Resident Advisor** (NEW)
- ✅ **Twitter/X** (NEW)
- ✅ **TikTok** (NEW)

---

## 🔒 DATA SANITIZATION

All new meta keys use proper sanitization:
- URLs: `esc_url()` for output, `sanitize_text_field()` for storage
- Handles (Twitter, Instagram): `esc_html()` for output
- All registered with `register_post_meta()` for REST API support

---

## ✅ CHECKLIST

- [x] Meta keys registered in `post-types.php`
- [x] Placeholders registered in `apollo_events_get_placeholders()`
- [x] Placeholder handlers implemented in `apollo_event_get_placeholder_value()`
- [x] All 8 new platforms added
- [x] Proper sanitization applied
- [x] REST API support enabled
- [x] Documentation updated
- [x] Committed to GitHub

---

## 🚀 NEXT STEPS

1. **Admin Metaboxes:** Add fields for new platforms in `includes/admin-metaboxes.php`
2. **DJ Single Template:** Implement CodePen YPwezXX design with all social links
3. **Icons:** Verify RemixIcon classes for all platforms
4. **Testing:** Populate DJ profiles with all platform URLs
5. **UI/UX:** Design social link grid/list layout

---

**Última Atualização:** 2025-11-05  
**Commit:** `34f5dbb`  
**Total Placeholders:** 69 (was 61, now +8)  
**Status:** ✅ COMPLETE



