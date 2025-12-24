# Quick Fix Guide - Apollo Social PHPCS Violations

This guide provides common patterns and solutions for the 375 remaining PHPCS violations.

---

## 1. Unescaped Output (104 errors) - CRITICAL

### Pattern: Echo without escaping
```php
// ❌ WRONG - Will trigger: WordPress.Security.EscapeOutput.OutputNotEscaped
echo $variable;
echo $array['key'];

// ✅ RIGHT - Use appropriate escaping function
echo esc_html($variable);           // For text content
echo esc_attr($array['key']);        // For HTML attributes
echo wp_kses_post($html_content);    // For HTML content with allowed tags
```

### Common Escaping Functions

| Function | Use Case | Example |
|----------|----------|---------|
| `esc_html()` | Plain text output | `<?php echo esc_html($title); ?>` |
| `esc_attr()` | HTML attributes | `<div class="<?php echo esc_attr($class); ?>">` |
| `esc_url()` | URLs | `<a href="<?php echo esc_url($url); ?>">` |
| `wp_kses_post()` | HTML content | `<?php echo wp_kses_post($content); ?>` |
| `wp_json_encode()` | JSON data | `var data = <?php echo wp_json_encode($array); ?>;` |
| `esc_js()` | JavaScript strings | `console.log('<?php echo esc_js($message); ?>');` |

### Real Example from Codebase

**File**: `src/API/Endpoints/CommentsEndpoint.php:94`
```php
// ❌ WRONG
echo human_time_diff($date1, $date2);

// ✅ RIGHT
echo esc_html(human_time_diff($date1, $date2));
```

---

## 2. Unsafe Printing Functions (21 errors) - CRITICAL

### Pattern: Built-in functions that output directly
```php
// ❌ WRONG - Functions output directly without escaping
print_r($data);
var_dump($variable);
die($message);

// ✅ RIGHT - Escape before output
echo wp_json_encode($data);
wp_die(esc_html($message));
echo wp_kses_post(nl2br($message));
```

### Alternative Safe Functions

| Use | Wrong | Right |
|-----|-------|-------|
| Debug output | `print_r($data)` | `echo '<pre>' . wp_json_encode($data) . '</pre>'` |
| Translate & escape | `_e($text)` | `echo esc_html__($text)` |
| Human-readable time | `date()` | `gmdate()` or `wp_date()` |
| Array/object debug | `var_dump()` | `wp_json_encode()` or `error_log()` |

---

## 3. Unprepared SQL Queries (36+ errors) - CRITICAL

### Pattern: String interpolation in SQL
```php
global $wpdb;

// ❌ WRONG - SQL injection vulnerability
$results = $wpdb->get_results("SELECT * FROM {$table} WHERE id = {$id}");
$results = $wpdb->get_results("SELECT * FROM $table WHERE id = $id");

// ✅ RIGHT - Use $wpdb->prepare() with placeholders
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM %i WHERE id = %d",
        $table,    // %i for identifiers (table/column names)
        $id        // %d for integers
    )
);

// ✅ ALTERNATIVE - Use IDENTIFIER constant for dynamic table names
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM " . $wpdb->prefix . "custom_table WHERE id = %d",
        $id
    )
);
```

### Placeholder Reference

| Placeholder | Type | Example |
|-------------|------|---------|
| `%d` | Integer | `%d` for IDs, counts |
| `%f` | Float | `%f` for decimal numbers |
| `%s` | String | `%s` for text fields |
| `%i` | Identifier | `%i` for table/column names (WordPress 6.2+) |
| `{$var}` | Table name | Use `$wpdb->prefix . 'table_name'` instead |

### Real Examples from Codebase

**File**: `src/API/APIRegister.php:222`
```php
// ❌ WRONG
$wpdb->get_results("SHOW TABLES LIKE '{$table}'");

// ✅ RIGHT
$wpdb->get_results(
    $wpdb->prepare(
        "SHOW TABLES LIKE %s",
        $table
    )
);
```

**File**: `src/Application/Groups/CanvasController.php:100`
```php
// ❌ WRONG
$wpdb->get_results("SELECT * FROM {$groups_table} WHERE id = %d", $group_id);

// ✅ RIGHT
$wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM " . $groups_table . " WHERE id = %d",
        $group_id
    )
);
```

---

## 4. Non-Sanitized Input (72+ errors) - HIGH PRIORITY

### Pattern: Direct superglobal access
```php
// ❌ WRONG - WordPress.Security.ValidatedSanitizedInput.InputNotValidated
$_POST['email'];
$_GET['page'];
$_REQUEST['action'];

// ✅ RIGHT - Check existence and sanitize
if (isset($_POST['email'])) {
    $email = sanitize_email($_POST['email']);
}

if (isset($_GET['page'])) {
    $page = intval($_GET['page']);  // For integers
}

// ✅ ALTERNATIVE - Using REST API (recommended)
register_rest_route('apollo/v1', '/action', [
    'methods' => 'POST',
    'callback' => function(WP_REST_Request $request) {
        $email = $request->get_param('email');
        // REST API handles sanitization automatically
    },
    'permission_callback' => '__return_true',
]);
```

### Sanitization Functions

| Function | For | Example |
|----------|-----|---------|
| `sanitize_text_field()` | General text | `$title = sanitize_text_field($_POST['title']);` |
| `sanitize_email()` | Email addresses | `$email = sanitize_email($_POST['email']);` |
| `sanitize_url()` | URLs | `$url = sanitize_url($_POST['url']);` |
| `intval()` | Integers | `$id = intval($_GET['id']);` |
| `absint()` | Non-negative integers | `$count = absint($_GET['count']);` |
| `wp_parse_args()` | Arrays/options | `$args = wp_parse_args($_POST, $defaults);` |

### Real Example from Codebase

**File**: `src/Application/Groups/CanvasController.php:181-185`
```php
// ❌ WRONG
$nonce = $_POST['nonce'];           // Undefined index, not validated
$group_id = $_POST['group_id'];     // Undefined index, not sanitized

// ✅ RIGHT
if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'group_action')) {
    wp_die('Security check failed');
}

$group_id = isset($_POST['group_id']) ? absint($_POST['group_id']) : 0;
if (!$group_id) {
    wp_die('Invalid group ID');
}
```

---

## 5. Yoda Conditions (15+ errors) - STYLE

### Pattern: Literal before variable
```php
// ❌ WRONG - Yoda condition style
if ('value' === $variable) {}
if (null !== $result) {}
if (false === $enabled) {}

// ✅ RIGHT - Modern PHP style (more readable)
if ($variable === 'value') {}
if ($result !== null) {}
if ($enabled === false) {}
```

---

## 6. Date/Time Functions (3+ errors) - MEDIUM

### Pattern: Using `date()` instead of `gmdate()`
```php
// ❌ WRONG - Affected by WordPress timezone settings
$today = date('Y-m-d');
$timestamp = date('U');

// ✅ RIGHT - Consistent UTC/GMT time
$today = gmdate('Y-m-d');
$timestamp = time();  // Use time() for current Unix timestamp

// ✅ ALTERNATIVE - Use WordPress functions
$today = wp_date('Y-m-d');  // Respects WordPress timezone
$today = wp_date('Y-m-d', null, new DateTimeZone('America/New_York'));  // Specific timezone
```

---

## Batch Fixing Strategy

### For Your Team

1. **Generate Issue List**
   ```bash
   ./vendor/bin/phpcs --standard=phpcs.xml.dist src/ --report=csv > violations.csv
   ```

2. **Group by File/Type**
   ```bash
   # Get files with most violations
   grep "error" violations.csv | cut -d',' -f1 | sort | uniq -c | sort -rn
   ```

3. **Fix by Category**
   - Week 1: Escaping (104 errors)
   - Week 2: SQL (36 errors)
   - Week 3: Input validation (72 errors)
   - Week 4: Code style cleanup

4. **Test After Each Fix**
   ```bash
   # Test affected functionality
   # Re-run PHPCS on modified files
   ./vendor/bin/phpcs --standard=phpcs.xml.dist src/API/
   ```

---

## Adding Suppressions (When Necessary)

Sometimes a violation is a false positive or intentional. Use suppressions carefully:

```php
// @phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped content
echo $safe_content;
// @phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

// Or for a single line:
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped
echo $safe_content;
```

⚠️ **Use sparingly!** Document why the suppression is necessary.

---

## Testing Your Fixes

```bash
# Check a specific file after fixing
./vendor/bin/phpcs --standard=phpcs.xml.dist src/API/Endpoints/CommentsEndpoint.php

# Check everything
composer run lint

# Get a detailed report
./vendor/bin/phpcs --standard=phpcs.xml.dist src/ --report=full | grep "CommentsEndpoint"
```

---

## Resources

- [WordPress Security Handbook - Escaping Output](https://developer.wordpress.org/plugins/security/securing-output/)
- [WordPress Security Handbook - Validating Input](https://developer.wordpress.org/plugins/security/securing-input/)
- [WPDB Prepare Examples](https://developer.wordpress.org/plugins/security/sanitizing-input/#using-prepared-statements)
- [PHP CodeSniffer WordPress Standards](https://github.com/WordPress/WordPress-Coding-Standards/wiki)

---

## Questions?

When in doubt:
1. Check the PHPCS error message (it usually explains the issue)
2. Look for similar patterns in the same file
3. Ask in code review - security decisions should be discussed
4. Run tests to ensure the fix doesn't break functionality
