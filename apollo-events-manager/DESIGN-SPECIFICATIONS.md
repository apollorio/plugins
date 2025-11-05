# 🎨 Design Specification: Final Frontend Echo Expectations

**Data:** 5 de Novembro de 2025  
**Versão:** 1.0  
**Status:** 🟢 APPROVED

---

## ✅ General Rule

> All rendered pages must **echo pixel-perfect** the layouts and interactions shown in the CodePen previews below.

**Zero deviation policy:** Backend rendering must match CodePen output exactly.

---

## 🔗 Design References

| Page Type | Expected Output URL | Template File | Status |
|-----------|---------------------|---------------|--------|
| 🧭 Hub | [View on CodePen](https://codepen.io/Rafael-Valle-the-looper/pen/emJaJzQ) | `template-hub.php` | ⏳ Pending |
| 📅 Eventos (Discover) | [View on CodePen](https://codepen.io/Rafael-Valle-the-looper/pen/raxqVGR) | `templates/portal-discover.php` | ✅ **Implemented** |
| 🎟️ Evento (Single) | [View on CodePen](https://codepen.io/Rafael-Valle-the-looper/pen/EaPpjXP) | `template-evento-single.php` | ⏳ Pending |
| 🎧 DJ (Single) | [View on CodePen](https://codepen.io/Rafael-Valle-the-looper/pen/YPwezXX) | `template-dj-single.php` | ⏳ Pending |

---

## 📋 Implementation Checklist

### ✅ DONE: Eventos (Discover) - raxqVGR
**Template:** `templates/portal-discover.php`  
**Status:** ✅ Implemented and tested  
**CodePen:** https://codepen.io/Rafael-Valle-the-looper/pen/raxqVGR

**Features Implemented:**
- [x] Grid layout com event cards
- [x] Date chip (dia + mês PT-BR) outside `.picture`
- [x] Event card hover effects
- [x] Tags/sounds display
- [x] DJ list formatting
- [x] Location display com opacity
- [x] Filter chips (category, date, search)
- [x] Layout toggle button
- [x] Dark mode toggle
- [x] Hero section com glassmorphism
- [x] Banner destaque do blog

**Data Integration:**
- [x] DJs: Multi-fallback logic (`_event_dj_ids` → `_event_timetable` → `_timetable` → `_dj_name`)
- [x] Local: Multi-fallback logic (`_event_local_ids` → `_event_location`)
- [x] Display: `<strong>DJ1</strong>, DJ2, DJ3 +N DJs`
- [x] Display: `Local Name <opacity>(Area)</opacity>`

---

### ⏳ TODO: Hub - emJaJzQ
**Template:** `template-hub.php` (to be created)  
**Status:** ⏳ Pending  
**CodePen:** https://codepen.io/Rafael-Valle-the-looper/pen/emJaJzQ

**Requirements:**
- [ ] Hero section com animações
- [ ] Navigation cards/tiles
- [ ] Featured content sections
- [ ] Responsive grid
- [ ] Dark mode support
- [ ] Scroll animations (se houver)

**Data Sources:**
- WordPress pages
- Custom meta fields
- Featured posts
- Menu items

**Template Location:** `templates/template-hub.php`

---

### ⏳ TODO: Evento Single - EaPpjXP
**Template:** `template-evento-single.php` (to be created)  
**Status:** ⏳ Pending  
**CodePen:** https://codepen.io/Rafael-Valle-the-looper/pen/EaPpjXP

**Requirements:**
- [ ] Hero section com banner full-width
- [ ] Event details sidebar
- [ ] Timetable display (se houver)
- [ ] DJ line-up com fotos
- [ ] Location map integration
- [ ] Tickets/RSVP section
- [ ] Social sharing buttons
- [ ] Related events carousel

**Data Sources:**
- Event post meta (`_event_*`)
- Related DJs (`_event_dj_ids`)
- Related Local (`_event_local_ids`)
- Event taxonomies (`event_sounds`, `event_listing_category`)

**Template Location:** `templates/single-event_listing.php` (refactor existing)

**Current State:** 
- Existing template: `templates/single-event-standalone.php`
- **Action:** Review and adapt to match CodePen EaPpjXP

---

### ⏳ TODO: DJ Single - YPwezXX
**Template:** `template-dj-single.php` (to be created)  
**Status:** ⏳ Pending  
**CodePen:** https://codepen.io/Rafael-Valle-the-looper/pen/YPwezXX

**Requirements:**
- [ ] Hero section com DJ photo
- [ ] Bio/description
- [ ] Social links (Instagram, SoundCloud, Facebook)
- [ ] Original Projects display
- [ ] DJ Set embed (se houver)
- [ ] Media Kit download
- [ ] Rider download
- [ ] DJ Mix embed (se houver)
- [ ] Upcoming events list
- [ ] Past events list

**Data Sources:**
- DJ post meta (`_dj_*`)
- Related events (via `_event_dj_ids` reverse lookup)
- Taxonomies (se houver)

**Placeholders Available:**
- `dj_name`
- `dj_bio`
- `dj_image`
- `dj_website`
- `dj_soundcloud`
- `dj_instagram`
- `dj_facebook`
- `dj_original_project_1`
- `dj_original_project_2`
- `dj_original_project_3`
- `dj_set_url`
- `dj_media_kit_url`
- `dj_rider_url`
- `dj_mix_url`

**Template Location:** `templates/single-event_dj.php` (create new)

---

## 🛠️ Backend Integration Rules

### Template Naming Convention
```
WordPress Template Hierarchy:
- single-{post_type}.php
- archive-{post_type}.php
- page-{slug}.php
- template-{name}.php
```

**Apollo Events Templates:**
```
templates/
├── portal-discover.php           ✅ Eventos Discover (raxqVGR)
├── single-event_listing.php      ⏳ Evento Single (EaPpjXP)
├── single-event_dj.php           ⏳ DJ Single (YPwezXX)
├── template-hub.php              ⏳ Hub (emJaJzQ)
└── ...
```

### CSS/JS Loading
**Required Assets:**
- `uni.css` - Universal Apollo CSS framework (CDN)
- `base.js` - Universal Apollo JS (CDN)
- RemixIcon - Icon library (CDN)

**CDN URLs:**
```html
<!-- CSS -->
<link rel="stylesheet" href="https://assets.apollo.rio.br/uni.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css">

<!-- JS -->
<script src="https://assets.apollo.rio.br/base.js"></script>
```

### Dynamic Content Integration
**Placeholder API:**
```php
// Get single placeholder value
echo apollo_event_get_placeholder_value('dj_list', $event_id);

// Get all placeholders
$placeholders = apollo_events_get_placeholders();
```

**Shortcode Usage:**
```php
// In template
echo do_shortcode('[apollo_event field="dj_list"]');

// In post content
[apollo_event field="location" id="143"]
```

---

## 📐 Layout Preservation Rules

### 1. Grid System
**Match CodePen exactly:**
- Use same breakpoints
- Same column counts
- Same gap/spacing

**Example:**
```css
.event_listings {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}
```

### 2. Typography
**Preserve:**
- Font sizes
- Line heights
- Font weights
- Letter spacing

**Do NOT:**
- Change font family (use uni.css variables)
- Alter heading hierarchy
- Modify text transforms

### 3. Colors & Theming
**Use uni.css variables:**
```css
:root {
    --bg-primary: ...;
    --bg-secondary: ...;
    --text-primary: ...;
    --text-main: ...;
    --border-color: ...;
}
```

**Dark mode:**
- Must toggle via `.dark-mode` class on `<html>`
- All colors must adapt via CSS variables

### 4. Spacing & Rhythm
**Maintain:**
- Padding values
- Margin values
- Section spacing
- Vertical rhythm

**Units:**
- Prefer `rem` for spacing
- `px` only for borders/shadows
- `%` or `fr` for grid

---

## 🎯 Quality Assurance Criteria

### Visual QA Checklist
- [ ] **Pixel-perfect match** - Overlay CodePen screenshot and compare
- [ ] **Responsive behavior** - Test all breakpoints (mobile, tablet, desktop)
- [ ] **Hover states** - All interactive elements match CodePen
- [ ] **Animations** - Smooth transitions (if present)
- [ ] **Dark mode** - Seamless toggle, all colors adapt
- [ ] **Typography** - Exact sizes, weights, spacing
- [ ] **Icons** - RemixIcon classes correct
- [ ] **Images** - Aspect ratios preserved, lazy loading

### Functional QA Checklist
- [ ] **Data binding** - All dynamic fields populated
- [ ] **Placeholders** - All placeholders working
- [ ] **Shortcodes** - All shortcodes functional
- [ ] **Links** - All navigation links correct
- [ ] **Forms** - All forms (if any) working
- [ ] **AJAX** - All AJAX interactions functional
- [ ] **Performance** - Page load < 2s
- [ ] **No errors** - No PHP/JS console errors

---

## 🚫 Anti-Patterns to Avoid

### ❌ DON'T:
- Add custom CSS that overrides CodePen styles
- Change HTML structure "because it's easier"
- Skip dark mode implementation
- Use different icon library
- Hardcode content that should be dynamic
- Ignore responsive breakpoints
- Skip hover/active states
- Use inline styles (except for dynamic data like colors)

### ✅ DO:
- Match CodePen HTML structure exactly
- Use uni.css classes as-is
- Implement all interactive states
- Test on real data
- Use placeholders for dynamic content
- Preserve all animations/transitions
- Follow WordPress template hierarchy
- Cache when possible

---

## 📊 Implementation Priority

### Phase 1: ✅ DONE
- [x] Eventos Discover (raxqVGR) - `portal-discover.php`

### Phase 2: 🎯 HIGH PRIORITY
1. **Evento Single (EaPpjXP)** - User clicks event card and expects this
2. **DJ Single (YPwezXX)** - Users navigate to DJ profiles

### Phase 3: 📅 MEDIUM PRIORITY
3. **Hub (emJaJzQ)** - Landing page

### Phase 4: 🔮 FUTURE
- Local/Venue single page
- User profile pages
- Admin custom views

---

## 🔗 Related Documentation

- [PLUGIN-SUMMARY.md](PLUGIN-SUMMARY.md) - Plugin architecture
- [PLACEHOLDERS-REFERENCE.md](PLACEHOLDERS-REFERENCE.md) - All available placeholders
- [COMPREHENSIVE-FIXES-2025-11-05.md](COMPREHENSIVE-FIXES-2025-11-05.md) - Latest fixes
- [FINAL-TEST-PLAN-2025-11-05.md](FINAL-TEST-PLAN-2025-11-05.md) - Testing procedures

---

## 📝 Notes for Developers

### When Creating New Templates:

1. **Copy CodePen HTML structure** exactly
2. **Replace hardcoded content** with WordPress functions:
   ```php
   // Static
   <h1>Event Title</h1>
   
   // Dynamic
   <h1><?php echo esc_html(get_the_title()); ?></h1>
   ```
3. **Use placeholder API** for Apollo-specific data:
   ```php
   // Instead of hardcoded DJ names
   <?php echo apollo_event_get_placeholder_value('dj_list', get_the_ID()); ?>
   ```
4. **Enqueue assets** via WordPress hooks (not inline)
5. **Test with XDebug** to verify data flow
6. **Validate HTML** and accessibility
7. **Test responsive** on real devices
8. **Document** any deviations (with approval)

### CodePen to WordPress Workflow:

```
1. Download CodePen HTML/CSS/JS
2. Convert to WordPress template
3. Replace static content with dynamic functions
4. Test with sample data
5. Test with real data
6. QA against CodePen preview
7. Deploy to staging
8. Final QA
9. Deploy to production
```

---

## ✅ Sign-Off

**Design Approved By:** Rafael Valle  
**Development Lead:** [To be assigned]  
**QA Lead:** [To be assigned]  
**Date:** 2025-11-05  

**Status:** 🟢 APPROVED - Ready for implementation

---

**Última Atualização:** 2025-11-05  
**Versão:** 1.0  
**Próxima Revisão:** After Phase 2 completion

