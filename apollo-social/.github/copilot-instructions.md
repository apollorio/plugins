# WordPress Plugin Development Rules - Apollo Platform

You are working on the **Apollo Platform** - a suite of 4 interconnected WordPress plugins for Rio de Janeiro's culture industry. Follow these rules STRICTLY:

---

## 🔒 Security (NON-NEGOTIABLE)

### Output Escaping

- **ALWAYS** escape output based on context:
  - HTML: `esc_html()`
  - Attributes: `esc_attr()`
  - URLs: `esc_url()`
  - Rich content: `wp_kses_post()`
  - JavaScript strings: `esc_js()`
- **NEVER** use `echo` without escaping
- **NEVER** trust user input or database content

### Input Sanitization

- **ALWAYS** sanitize before saving:
  - Text: `sanitize_text_field()`
  - Email: `sanitize_email()`
  - URLs: `esc_url_raw()`
  - Filenames: `sanitize_file_name()`
  - Keys: `sanitize_key()`
  - HTML: `wp_kses_post()` or `wp_filter_post_kses()`

### Capability Checks

- Check capabilities BEFORE any create/update/delete:
  ```php
  if ( ! current_user_can( 'manage_options' ) ) {
      wp_die( 'Unauthorized' );
  }
  ```
- Custom capabilities for Apollo roles (cena-rio, visual-artist, etc.)
- Use `user_can()` for checking other users

### Nonces

- Forms: `wp_nonce_field( 'action_name', 'nonce_field' )`
- Verify: `wp_verify_nonce( $_POST['nonce_field'], 'action_name' )`
- AJAX: `check_ajax_referer( 'action_name' )`
- REST API: Use `wp_create_nonce('wp_rest')` and send as `X-WP-Nonce` header

---

## 🎨 Asset Loading (NEVER HARDCODE)

### Enqueue System

- **NEVER** hardcode `<script>` or `<link>` tags in templates
- **ALWAYS** use WordPress enqueue system:
  ```php
  wp_enqueue_style( 'handle', $url, $deps, $version, $media );
  wp_enqueue_script( 'handle', $url, $deps, $version, $in_footer );
  ```
- Register dependencies properly (`array('jquery', 'leaflet')`)
- Use `filemtime()` for version/cache-busting:
  ```php
  filemtime( plugin_dir_path( __FILE__ ) . 'assets/css/style.css' )
  ```

### Config Data

- Use `wp_localize_script()` for simple config:
  ```php
  wp_localize_script( 'handle', 'apolloConfig', array(
      'ajaxUrl' => admin_url( 'admin-ajax.php' ),
      'nonce' => wp_create_nonce( 'apollo_action' )
  ) );
  ```
- For complex config, use `wp_add_inline_script()`:
  ```php
  wp_add_inline_script( 'handle', 'window.apollo = ' . wp_json_encode( $config ), 'before' );
  ```

### Apollo-Specific Assets

- CDN: `https://assets.apollo.rio.br/`
- Core styles: `apollo-core/assets/css/`
- Navbar: Load via `apollo-core/assets/css/navbar.css`
- Icons: Use Apollo icon system with masks

---

## 🔌 REST API (Modern Approach)

### Route Registration

```php
register_rest_route( 'apollo/v1', '/events', array(
    'methods' => 'GET',
    'callback' => array( $this, 'get_events' ),
    'permission_callback' => array( $this, 'check_permission' ),
    'args' => array(
        'date' => array(
            'validate_callback' => 'rest_validate_request_arg',
            'sanitize_callback' => 'sanitize_text_field',
        ),
    ),
) );
```

### Permissions

- **ALWAYS** include `permission_callback` on ALL endpoints
- Return `true`, `false`, or `WP_Error`:
  ```php
  public function check_permission() {
      return current_user_can( 'read' );
  }
  ```

### Responses

- Success: `return new WP_REST_Response( $data, 200 );`
- Error: `return new WP_Error( 'code', 'Message', array( 'status' => 400 ) );`

---

## 📝 Code Quality

### WordPress Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- Use WordPress functions over PHP equivalents:
  - `wp_json_encode()` instead of `json_encode()`
  - `wp_remote_get()` instead of `file_get_contents()`
  - `wp_safe_redirect()` instead of `header('Location:')`

### Naming Conventions

- Functions: `apollo_social_function_name()`
- Classes: `Apollo_Social_Class_Name`
- Hooks: `apollo_social_hook_name`
- Namespaces: `Apollo\Social\Module`

### Comments

- Comment complex logic
- Use PHPDoc blocks for functions:
  ```php
  /**
   * Get events for calendar.
   *
   * @param string $date Date in Y-m-d format.
   * @return array Events array.
   */
  ```

### Single Responsibility

- Keep functions single-purpose
- Extract complex logic to separate methods
- Max 50 lines per function (guideline)

---

## 🐛 Debugging

### Error Logging

```php
error_log( 'Apollo: Event save failed - ' . print_r( $data, true ) );
```

### Error Handling

- Return meaningful error messages
- Handle edge cases: empty data, missing permissions, invalid input
- Use `WP_Error` for recoverable errors:
  ```php
  if ( ! $event ) {
      return new WP_Error( 'not_found', 'Event not found', array( 'status' => 404 ) );
  }
  ```

---

## 🚫 Apollo-Specific Anti-Patterns

### DO NOT

- ❌ Use `wp_head()`, `wp_body_open()`, `wp_footer()` in **raw_html templates**
- ❌ Mix business logic in templates - use MVC pattern
- ❌ Hardcode URLs - use `home_url()`, `admin_url()`, `content_url()`
- ❌ Directly access `$_GET`, `$_POST`, `$_REQUEST` - sanitize first
- ❌ Use `extract()` - security risk
- ❌ Query database directly - use `WP_Query`, `get_posts()`, `$wpdb->prepare()`

### DO

- ✅ Use template hierarchy: `locate_template()`, `get_template_part()`
- ✅ Cache expensive operations: `wp_cache_get()`, `wp_cache_set()`, `set_transient()`
- ✅ Check `defined('ABSPATH')` at top of every file
- ✅ Use hooks for extensibility: `do_action()`, `apply_filters()`

---

## 📦 Apollo Platform Architecture

### Plugins

1. **apollo-core**: Foundation, navbar, assets, global utilities
2. **apollo-social**: Feed, mapa, cena, documents, messaging
3. **apollo-events-manager**: Events CPT, calendar, DJ/venue management
4. **apollo-rio**: PWA, theming, Rio-specific features

### Template Types

- **Canvas templates**: Full layout control, can use `wp_head()/wp_footer()`
- **Raw HTML templates**: Complete HTML output, NO `wp_head()/wp_footer()`
- **Partial templates**: Loaded via `include` or `get_template_part()`

### Custom Post Types

- `event_listing`, `event_dj`, `event_local`, `event_cena`
- `apollo_social_post`, `apollo_classified`, `user_page`, `apollo_document`

### Routes

- Custom rewrite rules in `apollo-social/config/routes.php`
- Canvas mode: `/cena/`, `/mapa/`, `/feed/`, `/painel/`
- Handled by `Apollo\Infrastructure\Http\Routes`

---

## 🎯 Development Workflow

1. **Before coding**: Check existing Apollo utilities (don't reinvent)
2. **Security check**: Escape output, sanitize input, verify nonces
3. **Asset check**: Enqueue properly, don't hardcode
4. **Error handling**: Log errors, return `WP_Error` on failure
5. **Testing**: Test as logged-in/logged-out, admin/subscriber
6. **Code review**: Follow standards, add comments, single responsibility

---

## 🔥 Common Gotchas

### Leaflet Maps

- Container must have explicit height
- Initialize only after DOM ready
- Check if tab/container is visible before init

### Admin Bar

- Templates with `raw_html => true` conflict with admin bar
- Remove admin bar hooks or don't use `wp_footer()`

### Navbar

- Official Apollo navbar: `apollo-core/templates/partials/navbar.php`
- Include manually in raw_html templates
- Load navbar.css and navbar.js manually

### AJAX

- Use `wp_ajax_{action}` and `wp_ajax_nopriv_{action}` hooks
- Always verify nonces: `check_ajax_referer()`
- Return JSON: `wp_send_json_success()` or `wp_send_json_error()`

---

**Remember**: Security first, WordPress standards always, no shortcuts!
