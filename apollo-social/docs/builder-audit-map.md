# Apollo Builder Audit Map

## Data Model
*   **CPT**: `apollo_home` (One post per user)
*   **Meta Keys**:
    *   `_apollo_builder_content` (JSON layout)
    *   `_apollo_background_texture` (Texture ID)
    *   `_apollo_trax_url` (SoundCloud/Spotify URL)
    *   `_apollo_builder_css` (Generated CSS)
*   **Storage Strategy**: JSON in `post_meta`, similar to `_wow_content`.

## Admin Integration
*   **Slug**: `apollo-builder` (Currently a Frontend Rewrite Endpoint)
*   **Assets Page**: `apollo-builder-assets` (Submenu of `apollo-social-hub`)
*   **Capability**: `edit_posts` (Mapped to `APOLLO_BUILDER_CAPABILITY`)

## REST/AJAX Endpoints
*   `wp_ajax_apollo_builder_save`: Save layout JSON.
*   `wp_ajax_apollo_builder_render_widget`: Render single widget HTML.
*   `wp_ajax_apollo_builder_widget_form`: Get settings form for widget.
*   `wp_ajax_apollo_builder_update_bg`: Update background texture.
*   `wp_ajax_apollo_builder_update_trax`: Update music URL.
*   `wp_ajax_apollo_builder_add_depoimento`: Add guestbook comment.
*   `wp_ajax_apollo_builder_get_widgets`: Get list of available widgets.

## JS Entrypoints
*   `assets/js/apollo-builder.js`: Main builder engine (Drag & Drop, Grid, Save).
*   `assets/js/apollo-home.js`: Frontend interactions (Guestbook, Tooltips).
*   `assets/js/admin-builder-assets.js`: Admin media manager for assets.

## Security Patterns
*   **Nonce**: `apollo-builder-nonce` verified on all AJAX calls.
*   **Capability**: `current_user_can(APOLLO_BUILDER_CAPABILITY)`.
*   **Ownership**: `post_author` check for save operations.
*   **Sanitization**: `apollo_builder_sanitize_layout` for JSON structure.
*   **Response**: Migrating to `Apollo\API\Response`.

## UX & Tooltips
*   **Requirement**: Tooltips on all DB-bound inputs.
*   **Implementation**: `title` attributes on input fields and widget palette items.
