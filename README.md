# Apollo Events Manager

**Custom templates and styling for WP Event Manager with Apollo Events integration**

## Description

Apollo Events Manager is a WordPress plugin that provides custom templates, styling, and enhanced functionality for the WP Event Manager plugin. It includes Brazilian localization, DJ management, venue management, timetable features, and OSM maps integration.

## Features

- 🎵 **DJ Management**: Add and manage DJs with custom post types
- 📍 **Venue Management**: Location management with coordinates and images
- 🕒 **Timetable System**: Flexible DJ performance scheduling
- 🗺️ **OSM Maps**: OpenStreetMap integration with Leaflet
- 🇧🇷 **Brazilian Localization**: Portuguese date formats and regional settings
- 🎨 **Custom Templates**: Beautiful event cards and single event pages
- 🔍 **AJAX Filtering**: Real-time event filtering and search
- 📱 **Responsive Design**: Mobile-friendly interface
- 🛡️ **Defensive Programming**: Comprehensive error handling and validation

## Requirements

- WordPress 5.0+
- PHP 7.4+
- WP Event Manager 3.0+
- WP Event Manager tested up to 3.1.3

## Installation

1. Upload the `apollo-events-manager` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin
3. Configure your events, DJs, and venues
4. Use the `[apollo_events]` shortcode to display events

## Usage

### Shortcodes

- `[apollo_events]` - Display events listing
- `[eventos-page]` - Complete portal page with filters

### Custom Post Types

- `event_listing` - Events (managed by WP Event Manager)
- `event_dj` - DJ profiles
- `event_local` - Venue locations

### Custom Fields

- **Events**: DJ selection, timetable, venue, promotional images, coupons
- **DJs**: Name, photo, bio
- **Venues**: Address, coordinates, images, region info

## Configuration

The plugin includes a configuration system in `includes/config.php` that defines:
- Custom post types and taxonomies
- Meta field mappings
- Default field configurations

## Development

### File Structure

```
apollo-events-manager/
├── apollo-events-manager.php    # Main plugin file
├── includes/
│   └── config.php              # Configuration
├── templates/                  # Custom templates
│   ├── event-card.php         # Event listing card
│   ├── single-event.php       # Single event page
│   └── ...
├── assets/                    # JavaScript and CSS
│   ├── portal-filters.js      # Frontend functionality
│   ├── uni.css               # Styles
│   └── uni.js                # Utilities
└── languages/                 # Translation files
```

### Key Features

- **Defensive Programming**: All data retrieval includes validation
- **Multiple Fallbacks**: Coordinate and image URL fallbacks
- **Flexible Timetable**: Supports various array structures
- **Error Handling**: WP_Error checks throughout
- **Performance**: Caching and query optimization

## Changelog

### 1.0.0
- Initial release
- DJ and venue management
- Timetable system
- OSM maps integration
- Brazilian localization
- AJAX filtering
- Comprehensive validation

## License

GPL v2 or later

## Author

Apollo Events Team
https://apollo.rio.br