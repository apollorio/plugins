# Apollo Design Library - Modular Components

## Structure

Components are organized by type and support multiple design variations (TYPE=0, TYPE=1, etc.).

### Naming Convention
- `{category}-{component}-{type}.html` - e.g., `sidebar-social-desktop-S0.html`
- Type suffix indicates design variation: `-S0`, `-S1`, etc.

### Categories

| Category | Description |
|----------|-------------|
| `sidebar-*` | Navigation sidebars (desktop/mobile) |
| `menu-*` | Mobile bottom navigation menus |
| `header-*` | Page headers with notifications |
| `card-*` | Card components (events, groups, etc.) |
| `list-*` | List item components |
| `block-*` | Standalone blocks (map, player, etc.) |
| `layout-*` | Full page layouts for CPTs |
| `tags-*` | Tag/filter components |

### Usage in PHP

```php
<?php
// Load component
$component = apollo_load_component('sidebar-social-desktop', 'S0');
echo $component;

// Or include directly
include APOLLO_CORE_PLUGIN_DIR . 'templates/design-library/_components/sidebar-social-desktop-S0.html';
?>
```

### Component List

#### Sidebars & Menus
- `sidebar-social-desktop-S0.html` - Apollo Social desktop sidebar
- `sidebar-social-mobile-S0.html` - Apollo Social mobile bottom menu
- `sidebar-cenario-desktop-S0.html` - Cena Rio desktop sidebar
- `sidebar-cenario-mobile-S0.html` - Cena Rio mobile bottom menu
- `sidebar-cenario-calendar-S0.html` - Cena Rio calendar summary sidebar

#### Headers
- `header-social-notifications-H0.html` - Social header with notifications/messages

#### Cards
- `card-event-C0.html` - Event card design (first style)
- `card-group-G0.html` - Group/Community card (first style)

#### Lists
- `list-event-cenario-L0.html` - Event list item for Cena Rio

#### Blocks
- `block-map-osm-M0.html` - OpenStreetMap block
- `block-vinyl-player-V0.html` - Vinyl player with SoundCloud
- `block-slider-gallery-I0.html` - Image slider gallery

#### Layouts
- `layout-dj-single-D0.html` - DJ single page layout
- `layout-event-single-E0.html` - Event single page layout
- `layout-group-single-G0.html` - Group single page layout

#### Tags
- `tags-social-feed-T0.html` - Social feed filter tags
- `tags-events-filter-T0.html` - Events portal filter tags

#### User Components
- `inline-user-mention-U0.html` - Standardized user mention with badges

---

## User Mention Standard

All user displays MUST use `inline-user-mention-U0` format:

```html
[AVATAR] Name @handle [BADGES] ✓ · Time ago
         Núcleo Apollo · Núcleo D-Edge · ...
```

**Membership Badges:**
- `.badge-apollo` - Orange, rounded corners (Apollo subscriber)
- `.badge-dj` - Blue, pill (DJ role)
- `.badge-producer` - Blue, pill (Producer role)
- `.badge-cena-rio` - Purple, pill (Cena Rio access)
- `.badge-promoter` - Green, pill (Event promoter)
- `.badge-venue` - Red, pill (Venue owner)
- `.badge-artist` - Cyan, pill (Artist role)

**Verified Icon:**
Gold to red gradient using CSS background-clip.

**IMPORTANT:** NO followers/friends count. Apollo is against ego/status culture.

---

## Version History
- v1.0.0 - Initial component library creation

