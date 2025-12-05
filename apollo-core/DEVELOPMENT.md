# Apollo Core - Development Guidelines

**Last Updated**: 24/11/2025  
**Version**: 3.0.0  
**Environment**: Local by Flywheel

---

## 🚨 Critical Rules

### 1. Edit ONLY Plugin Files

**ALLOWED**:
```
✅ C:\Users\rafae\Local Sites\1212\app\public\wp-content\plugins\apollo-core\**
```

**FORBIDDEN**:
```
❌ /wp-admin/**
❌ /wp-includes/**
❌ /wp-content/themes/**
❌ /wp-content/plugins/OUTDATED-*/**
❌ /wp-config.php
❌ Any WordPress core files
```

**Why**: Editing WordPress core files causes:
- Broken updates
- Security vulnerabilities
- Conflicts across environments
- Difficult debugging

---

## 🏗️ Development Environment

### Local Site Details

```yaml
Site URL: http://localhost:10004
WP Root: /path/to/wordpress
Plugin Path: /path/to/wordpress/wp-content/plugins/apollo-core

Database:
  # Store credentials in environment variables or a local .env file
  # Never commit real credentials to the repository
  Host: ${DB_HOST}
  Port: ${DB_PORT}
  DB Name: ${DB_NAME}
  DB User: ${DB_USER}
  DB Pass: ${DB_PASSWORD}
```

⚠️ **Security Note**: Database credentials should be stored in environment variables or a `.env` file (which must be in `.gitignore`). Never commit real credentials to version control.

---

## 📁 Project Structure

```
apollo-core/
├── apollo-core.php              # Main plugin file
├── includes/                    # Core classes
│   ├── class-activation.php
│   ├── class-apollo-core.php
│   ├── class-autoloader.php
│   ├── class-canvas-loader.php
│   ├── class-migration.php
│   ├── class-module-loader.php
│   ├── class-permissions.php
│   └── class-rest-bootstrap.php
├── modules/                     # Modular features
│   ├── events/
│   ├── social/
│   └── moderation/
├── templates/                   # Canvas templates
│   └── canvas.php
├── tests/                       # PHPUnit tests
│   ├── bootstrap.php
│   ├── test-activation.php
│   └── test-rest-api.php
├── DEVELOPMENT.md              # This file
├── README.md                   # User documentation
├── CAPABILITIES-COMPLIANCE.md  # Capabilities spec
├── VERIFICATION-AUDIT-REPORT.md # Audit results
└── phpunit.xml                 # Test configuration
```

---

## 🔧 Development Workflow

### 1. Create Feature Branch

```bash
git checkout -b feature/your-feature-name
# or
git checkout -b fix/issue-description
```

### 2. Make Changes (ONLY in apollo-core/)

```bash
# Good ✅
edit apollo-core/includes/class-permissions.php
edit apollo-core/modules/events/bootstrap.php

# Bad ❌
edit wp-admin/index.php
edit wp-includes/post.php
```

### 3. Test Locally

#### PHP Syntax Check
```bash
php -l apollo-core/includes/class-permissions.php
```

#### Run All Tests
```bash
cd apollo-core
vendor/bin/phpunit
```

#### Check WP-CLI
```bash
wp plugin list --path="C:\Users\rafae\Local Sites\1212\app\public"
wp role list --path="C:\Users\rafae\Local Sites\1212\app\public"
```

### 4. Commit Changes

```bash
git add apollo-core/
git commit -m "feat: add new permission helper"
# or
git commit -m "fix: resolve activation hook issue"
```

### 5. Push & Create PR

```bash
git push origin feature/your-feature-name
```

Then create PR on GitHub/GitLab and tag reviewer.

---

## 🧪 Testing Commands

### Verify Plugin Activation

```bash
# Check if active
wp plugin is-active apollo-core --path="C:\Users\rafae\Local Sites\1212\app\public"

# Activate if needed
wp plugin activate apollo-core --path="C:\Users\rafae\Local Sites\1212\app\public"
```

### Check Roles & Options

```bash
# List all roles
wp role list --path="C:\Users\rafae\Local Sites\1212\app\public"

# Get settings
wp option get apollo_mod_settings --path="C:\Users\rafae\Local Sites\1212\app\public"
```

### Test REST Endpoints

```bash
# Health check
curl -i -X GET "http://localhost:10004/wp-json/apollo/v1/health"

# Get feed (public)
curl -i -X GET "http://localhost:10004/wp-json/apollo/v1/feed"

# Get events
curl -i -X GET "http://localhost:10004/wp-json/apollo/v1/events"
```

### Check Canvas Pages

```bash
# List pages
wp post list --post_type=page --path="C:\Users\rafae\Local Sites\1212\app\public" --format=csv --fields=ID,post_title,post_name

# Check canvas meta
wp post meta get <PAGE_ID> _apollo_canvas --path="C:\Users\rafae\Local Sites\1212\app\public"
```

### View Audit Log

```bash
wp apollo mod-log --limit=50 --path="C:\Users\rafae\Local Sites\1212\app\public"
```

### Get Statistics

```bash
wp apollo mod-stats --path="C:\Users\rafae\Local Sites\1212\app\public"
```

---

## 🔍 Debugging

### Enable WP Debug

Edit `wp-config.php` (only for local development):

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Check Debug Log

```bash
tail -f C:\Users\rafae\Local Sites\1212\app\public\wp-content\debug.log
```

### PHP Error Log

```bash
php -i | grep error_log
```

---

## 🔒 Security Checklist

### Before Committing

- [ ] No hardcoded credentials
- [ ] No debug scripts in public root
- [ ] All user inputs sanitized
- [ ] All outputs escaped
- [ ] Nonce verification on AJAX/forms
- [ ] Permission checks on REST endpoints
- [ ] No SQL injection vulnerabilities

### Debug Scripts Security

If you create debug scripts, **ALWAYS** add this at the top:

```php
<?php
if (!defined('WPINC') || !defined('APOLLO_DEBUG') || !APOLLO_DEBUG || !current_user_can('manage_options')) {
    http_response_code(403);
    exit('Forbidden');
}
```

And place them in `tests/` or `tools/`, never in plugin root.

---

## 📦 Building for Production

### Create Plugin ZIP

```bash
cd C:\Users\rafae\Local Sites\1212\app\public\wp-content\plugins
zip -r apollo-core-3.0.0.zip apollo-core/ -x "*.git*" "*/node_modules/*" "*/tests/*"
```

### Or PowerShell:

```powershell
cd "C:\Users\rafae\Local Sites\1212\app\public\wp-content\plugins"
Compress-Archive -Path apollo-core -DestinationPath apollo-core-3.0.0.zip -Force
```

---

## 🤝 Collaborator Onboarding

### Message Template

```
Hi [Name],

I need help debugging Apollo Core on my LocalWP site.

Environment:
- Site URL: [YOUR_LOCAL_URL]
- WP path: [YOUR_WP_PATH]
- Plugin: [YOUR_PLUGIN_PATH]

Database:
- Host: ${DB_HOST}
- Port: ${DB_PORT}
- DB name: ${DB_NAME}
- DB user: ${DB_USER}
- DB pass: [See .env file - never share credentials]

Important Rules:
1. Edit ONLY files under: wp-content\plugins\apollo-core\
2. Do NOT edit wp-admin, wp-includes, or theme files
3. OUTDATED-* folders are archives only
4. Use feature branches and open PRs
5. After debugging, I'll rotate DB credentials

What I need:
- Check activation flow and pages created
- Verify Canvas pages render with uni.css only
- Test REST endpoints under /wp-json/apollo/v1
- Find and secure any public debug scripts

Commands to run:
See apollo-core/DEVELOPMENT.md for full list

Please reply with:
- Exact commands you ran
- Any errors or warnings
- Git diff if you made changes

Thanks!
```

### Collaborator Checklist

They should run and report:

1. **Confirm Environment**
   ```bash
   ls -la apollo-core/
   ```

2. **Check Plugin**
   ```bash
   wp plugin list --path="..."
   wp role list --path="..."
   ```

3. **Test REST**
   ```bash
   curl http://localhost:10004/wp-json/apollo/v1/health
   ```

4. **Find Debug Scripts**
   ```bash
   rg "debug-text|db-test" -n
   ```

5. **Provide Results**
   - Copy/paste all outputs
   - Screenshot any errors
   - Git diff if they made changes

---

## 🎯 Code Standards

### PHP

- Follow **WordPress Coding Standards**
- Use **PSR-4 autoloading**
- Add **PHPDoc blocks** for all classes/methods
- **Type hints** where possible (PHP 8.1+)
- Use **strict_types** when appropriate

### JavaScript

- Use **ES6+ syntax**
- Follow **WordPress JS Standards**
- Add **JSDoc comments**
- Use **jQuery** for WP Admin
- Use **vanilla JS** or Motion.dev for Canvas pages

### CSS

- Use **Tailwind utility classes** (via uni.css)
- Follow **BEM naming** for custom classes
- Mobile-first responsive design
- Use **RemixIcon** for icons

---

## 📝 Commit Message Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types**:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation only
- `style`: Code style (formatting)
- `refactor`: Code refactoring
- `test`: Adding tests
- `chore`: Build process or auxiliary tools

**Examples**:
```
feat(moderation): add bulk approve action

Added bulk actions to moderation queue to approve multiple items at once.
Updates Tab 2 UI and REST endpoint.

Closes #123
```

```
fix(activation): prevent duplicate role creation

Added idempotency check in activation hook to prevent roles being
created multiple times.

Fixes #456
```

---

## 🚫 Common Mistakes to Avoid

### ❌ Editing Core Files

```php
// NEVER DO THIS
edit wp-admin/admin.php
edit wp-includes/post.php
```

### ❌ Hardcoded Paths

```php
// Bad
$path = 'C:\Users\rafae\...';

// Good
$path = APOLLO_CORE_PLUGIN_DIR . 'includes/';
```

### ❌ Missing Nonce Checks

```php
// Bad
if ($_POST['action'] === 'save') { ... }

// Good
if (isset($_POST['action']) && wp_verify_nonce($_POST['nonce'], 'save_action')) { ... }
```

### ❌ SQL Injection

```php
// Bad
$wpdb->query("SELECT * FROM table WHERE id = {$id}");

// Good
$wpdb->get_results($wpdb->prepare("SELECT * FROM table WHERE id = %d", $id));
```

---

## 📞 Support

- **Issues**: Open on GitHub
- **Questions**: Tag @rafael in PR
- **Urgent**: Contact via project channel

---

## 📚 Additional Resources

- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Plugin Handbook](https://developer.wordpress.org/plugins/)
- [REST API Handbook](https://developer.wordpress.org/rest-api/)
- [WP-CLI Commands](https://developer.wordpress.org/cli/commands/)
- [Local by Flywheel Docs](https://localwp.com/help-docs/)

---

**Remember**: When in doubt, ask before editing! 🛡️

