# Apollo Builder: Backgrounds & Stickers Feature

## Overview

This document describes the Habbo-style backgrounds and stickers feature added to the Apollo Builder module. The feature allows users to customize their profile pages with decorative backgrounds and draggable stickers.

## Architecture

### File Structure

```
src/Modules/Builder/
├── Assets/
│   ├── BackgroundRegistry.php    # Static registry of available backgrounds
│   └── StickerRegistry.php       # Static registry of available stickers
├── Admin/
│   └── BuilderAdminPage.php      # Admin UI entry point (updated)
├── Http/
│   └── BuilderRestController.php # REST API endpoints (extended)
├── LayoutRepository.php          # Data persistence layer (extended)
├── Renderer.php                  # HTML output renderer (extended)
└── BuilderServiceProvider.php    # Service registration (updated)

templates/
└── apollo-builder.php            # Admin UI template (updated)

assets/
├── js/
│   └── apollo-builder-assets.js  # JavaScript module for UI interactions
└── css/
    ├── apollo-builder.css        # Admin builder styles (extended)
    └── apollo-home.css           # Public profile styles (extended)
```

## REST API Endpoints

All endpoints use the namespace `apollo-social/v1/builder`.

### GET /assets

Returns all available backgrounds and stickers for the current user.

**Response:**
```json
{
  "backgrounds": [
    {
      "id": "solid_navy",
      "name": "Navy",
      "category": "solid",
      "type": "color",
      "value": "#1a1a2e",
      "is_limited": false
    }
  ],
  "stickers": [
    {
      "id": "emoji_star",
      "name": "Star",
      "category": "emoji",
      "url": "https://assets.apollo.rio.br/builder/stickers/emoji/star.png",
      "is_limited": false
    }
  ]
}
```

### POST /background

Sets the background for the current user's layout.

**Request Body:**
```json
{
  "background_id": "gradient_sunset"
}
```

**Response:**
```json
{
  "success": true,
  "background": {
    "id": "gradient_sunset",
    "name": "Sunset",
    "type": "gradient",
    "value": "linear-gradient(135deg, #ff6b6b 0%, #ffd93d 100%)"
  }
}
```

### GET /stickers

Returns all stickers in the current user's layout.

**Response:**
```json
{
  "stickers": [
    {
      "id": "sticker_1733108400",
      "sticker_id": "emoji_fire",
      "x": 100,
      "y": 50,
      "scale": 1.2,
      "rotation": 15,
      "z_index": 10
    }
  ]
}
```

### POST /sticker/add

Adds a new sticker to the layout.

**Request Body:**
```json
{
  "sticker_id": "badge_vip",
  "x": 200,
  "y": 100,
  "scale": 1.0,
  "rotation": 0
}
```

**Response:**
```json
{
  "success": true,
  "sticker": {
    "id": "sticker_1733108400",
    "sticker_id": "badge_vip",
    "x": 200,
    "y": 100,
    "scale": 1.0,
    "rotation": 0,
    "z_index": 1
  }
}
```

### PATCH /sticker/{id}

Updates an existing sticker's properties.

**Request Body (partial update):**
```json
{
  "x": 250,
  "y": 120,
  "rotation": 45
}
```

### DELETE /sticker/{id}

Removes a sticker from the layout.

## Registry System

### BackgroundRegistry

Static registry providing available backgrounds. Supports filtering by user capabilities.

**Categories:**
- `solid` - Solid color backgrounds
- `gradient` - CSS gradient backgrounds
- `pattern` - Repeating pattern images
- `premium` - Premium/limited backgrounds (requires `apollo_premium_assets` capability)

**Methods:**
- `get_all()` - Returns all backgrounds (filtered by `apollo_builder_backgrounds` hook)
- `get_by_id($id)` - Returns a single background by ID
- `get_available_for_user($user_id)` - Returns backgrounds available to a specific user
- `get_categories()` - Returns list of category names with labels

### StickerRegistry

Static registry providing available stickers.

**Categories:**
- `emoji` - Emoji-style stickers
- `badge` - Badge/achievement stickers
- `decoration` - Decorative elements
- `social` - Social media icons
- `music` - Music-related stickers
- `premium` - Premium/limited stickers

**Methods:**
- `get_all()` - Returns all stickers
- `get_by_id($id)` - Returns a single sticker by ID
- `get_available_for_user($user_id)` - Returns stickers available to a specific user
- `get_by_category($category, $user_id)` - Returns stickers in a category

## Data Storage

Layout data is stored in user meta with the key `apollo_builder_layout`.

**Schema:**
```json
{
  "background": "gradient_sunset",
  "stickers": [
    {
      "id": "sticker_1733108400",
      "sticker_id": "emoji_star",
      "x": 100,
      "y": 50,
      "scale": 1.0,
      "rotation": 0,
      "z_index": 1
    }
  ],
  "widgets": []
}
```

## JavaScript Module

The `apollo-builder-assets.js` module provides two classes:

### BackgroundManager

Manages background selection UI in the admin builder.

**Features:**
- Category tabs for filtering backgrounds
- Grid display with visual previews
- Premium badge indicators
- Live preview on canvas

### StickerManager

Manages sticker palette and canvas interactions.

**Features:**
- Category-filtered sticker palette
- Drag-and-drop from palette to canvas
- Sticker selection with resize/rotate handles
- Delete functionality
- Position persistence via REST API

## CSS Classes

### Admin Builder

- `.apollo-bg-modal` - Background selection modal
- `.bg-category-tabs` - Category tab navigation
- `.bg-grid` - Background options grid
- `.bg-card` - Individual background option
- `.stickers-palette` - Sticker selection palette
- `.sticker-item` - Individual sticker in palette
- `.apollo-stickers-layer` - Canvas layer for stickers
- `.apollo-sticker` - Individual sticker on canvas
- `.sticker-selected` - Selected sticker state
- `.sticker-controls` - Resize/rotate handles

### Public View

- `.apollo-stickers-layer` - Container for rendered stickers
- `.apollo-sticker` - Individual sticker with position styles

## Hooks and Filters

### PHP Filters

- `apollo_builder_backgrounds` - Filter available backgrounds array
- `apollo_builder_stickers` - Filter available stickers array

### JavaScript Events

- `apollo-sticker-added` - Fired when a sticker is added to canvas
- `apollo-sticker-updated` - Fired when a sticker is modified
- `apollo-sticker-removed` - Fired when a sticker is deleted
- `apollo-background-changed` - Fired when background is changed

## Backward Compatibility

The feature maintains backward compatibility with existing layouts:

1. Layouts without `background` key default to no background
2. Layouts without `stickers` key default to empty array
3. Legacy `is_limited` field on widgets is preserved
4. Existing widget positions are unaffected

## CDN Assets

Background images and stickers are served from:
- Backgrounds: `https://assets.apollo.rio.br/builder/backgrounds/`
- Stickers: `https://assets.apollo.rio.br/builder/stickers/`

## Security

1. All REST endpoints require `edit_posts` capability
2. Background/sticker IDs are validated against registries
3. Position values are sanitized (floats, constrained ranges)
4. User can only modify their own layout
5. Premium assets require appropriate user capabilities

## Performance

1. Registries are static arrays (no database queries)
2. Stickers rendered as simple positioned divs
3. CSS animations use GPU-accelerated transforms
4. Images loaded from CDN with proper caching headers
