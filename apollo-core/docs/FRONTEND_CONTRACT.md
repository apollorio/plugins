# Frontend Contract — Apollo Plugin Ecosystem

## Source of Truth
- **Approved templates** in `apollo-core/templates/design-library/approved templates/` define the exact DOM structure, class names, and visual layout.
- All PHP templates must generate markup that matches these approved HTML files exactly.
- Only replace static text/content with dynamic WordPress data.

## Non-Negotiables (Do Not Change)
- **CPT slugs** (post types like `event_listing`, `dj_profile`, etc.)
- **Taxonomy slugs** (categories, tags, custom taxonomies)
- **Meta keys** (custom fields like `_event_date_display`, `_apollo_interested_user_ids`)
- **REST API routes** and endpoints
- **Hook/filter names** (actions, filters, custom hooks)
- **Shortcode names** and parameters
- **AJAX action names**
- **Capability names** and permission checks
- **Database schema** (no new tables, no altering existing data formats)

## CSS/JS Rules
- **No SCSS nesting** in shipped CSS (`&:hover`, `& i` must be converted to valid CSS selectors)
- **uni.css-first**: All templates must load `https://assets.apollo.rio.br/uni.css` and `https://assets.apollo.rio.br/base.js` in `<head>`
- **No new external CDNs** without approval
- **Conditional enqueues**: Load assets only when needed (e.g., Leaflet only on map pages)

## Security & Standards
- **Escape all output**: `esc_html()`, `esc_attr()`, `esc_url()` for all dynamic content
- **Sanitize input**: Validate and sanitize all user inputs
- **Verify nonces**: For all form submissions and AJAX requests
- **Capability checks**: Proper permission validation
- **WordPress standards**: Follow WP coding standards and best practices

## Development Workflow
- **Branch**: `refactor/approved-visuals`
- **Commits**: One logical unit per commit (one page/block/shortcode)
- **Testing**: Manual testing with specific URLs + actions; automated tests where possible
- **Backward compatibility**: Preserve existing hooks/filters; wrap new code behind them

## Definition of Done
- DOM structure matches approved template exactly
- All dynamic content properly escaped and sanitized
- Visual appearance identical to approved HTML
- No console errors or broken functionality
- All existing hooks/filters preserved
- Assets load from CDN correctly
