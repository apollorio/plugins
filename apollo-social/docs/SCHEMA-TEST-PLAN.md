# Apollo Social - Schema Test Plan

## Pre-Test Checklist

- [ ] Fresh WordPress installation (6.0+)
- [ ] PHP 8.1+ installed
- [ ] WP-CLI available
- [ ] Database backup created

---

## A. Fresh Installation Tests

### A1. Plugin Activation

```bash
# Deactivate if active
wp plugin deactivate apollo-social --allow-root

# Clear any existing schema version
wp option delete apollo_schema_version --allow-root

# Activate plugin
wp plugin activate apollo-social --allow-root
```

**Expected:**
- [ ] No PHP errors in debug.log
- [ ] `apollo_schema_version` option set to `2.2.0`
- [ ] All tables created (verify with A4)

### A2. CLI Status Check

```bash
wp apollo schema status
```

**Expected Output:**
- [ ] Shows version `2.2.0`
- [ ] All tables show `✓` (exists)
- [ ] `needs_upgrade: false`

### A3. CLI Version Command

```bash
wp apollo schema version
```

**Expected:**
- [ ] Current version: `2.2.0`
- [ ] Stored version: `2.2.0`
- [ ] No upgrade needed

### A4. Table Existence Verification

```bash
wp db query "SHOW TABLES LIKE 'wp_apollo_%'"
```

**Expected Tables (14 total):**
- [ ] `wp_apollo_groups`
- [ ] `wp_apollo_group_members`
- [ ] `wp_apollo_workflow_log`
- [ ] `wp_apollo_mod_queue`
- [ ] `wp_apollo_analytics`
- [ ] `wp_apollo_signature_requests`
- [ ] `wp_apollo_onboarding_progress`
- [ ] `wp_apollo_verification_tokens`
- [ ] `wp_apollo_documents`
- [ ] `wp_apollo_document_signatures`
- [ ] `wp_apollo_chat_conversations`
- [ ] `wp_apollo_chat_messages`
- [ ] `wp_apollo_chat_participants`
- [ ] `wp_apollo_likes`

---

## B. Upgrade Path Tests

### B1. Simulate Legacy Installation

```bash
# Set old version to trigger upgrade
wp option update apollo_schema_version "2.0.0"

# Reload plugin (simulates plugins_loaded)
wp eval "do_action('plugins_loaded');"
```

**Expected:**
- [ ] Upgrade runs automatically
- [ ] Version updated to `2.2.0`
- [ ] No errors in debug.log

### B2. Manual Upgrade CLI

```bash
# Set older version
wp option update apollo_schema_version "2.1.0"

# Run upgrade
wp apollo schema upgrade
```

**Expected:**
- [ ] Success message displayed
- [ ] Version updated to `2.2.0`

### B3. Signature Migration Test

```bash
# Check if post_id column exists
wp db query "DESCRIBE wp_apollo_document_signatures" | grep post_id
```

**Expected:**
- [ ] `post_id` column present
- [ ] Column type: `BIGINT(20) UNSIGNED`

---

## C. Idempotency Tests

### C1. Multiple Install Runs

```bash
# Run install multiple times
wp apollo schema install --yes
wp apollo schema install --yes
wp apollo schema install --yes
```

**Expected:**
- [ ] All runs succeed
- [ ] No duplicate table errors
- [ ] No duplicate column errors
- [ ] Version remains `2.2.0`

### C2. dbDelta Idempotency

```bash
# Force re-run via PHP
wp eval "
\$schema = new \\Apollo\\Schema();
\$result1 = \$schema->install();
\$result2 = \$schema->install();
var_dump(\$result1, \$result2);
"
```

**Expected:**
- [ ] Both return `true`
- [ ] No SQL errors

---

## D. Module Isolation Tests

### D1. Documents Module Status

```bash
wp eval "
\$schema = new \\Apollo\\Modules\\Documents\\DocumentsSchema();
print_r(\$schema->getStatus());
"
```

**Expected:**
```php
Array(
    [apollo_documents] => 1,
    [apollo_document_signatures] => 1
)
```

### D2. Chat Module Status

```bash
wp eval "
\$schema = new \\Apollo\\Modules\\Chat\\ChatSchema();
print_r(\$schema->getStatus());
"
```

**Expected:**
```php
Array(
    [apollo_chat_conversations] => 1,
    [apollo_chat_messages] => 1,
    [apollo_chat_participants] => 1
)
```

### D3. Likes Module Status

```bash
wp eval "
\$schema = new \\Apollo\\Modules\\Likes\\LikesSchema();
print_r(\$schema->getStatus());
"
```

**Expected:**
```php
Array(
    [apollo_likes] => 1
)
```

---

## E. Destructive Tests (CAUTION)

### E1. Schema Reset

```bash
# Create backup first!
wp db export backup-before-reset.sql

# Reset schema
wp apollo schema reset --yes
```

**Expected:**
- [ ] All `wp_apollo_*` tables dropped
- [ ] `apollo_schema_version` option deleted
- [ ] Confirmation message displayed

### E2. Reinstall After Reset

```bash
wp apollo schema install --yes
```

**Expected:**
- [ ] All tables recreated
- [ ] Version set to `2.2.0`
- [ ] Status shows all tables exist

---

## F. Error Handling Tests

### F1. Missing CoreSchema Class

```bash
# Temporarily rename CoreSchema
mv src/Infrastructure/Database/CoreSchema.php src/Infrastructure/Database/CoreSchema.php.bak

# Try install
wp apollo schema install --yes

# Restore
mv src/Infrastructure/Database/CoreSchema.php.bak src/Infrastructure/Database/CoreSchema.php
```

**Expected:**
- [ ] Graceful error handling
- [ ] WP_Error returned (not fatal)
- [ ] Partial tables may exist

### F2. Database Connection Error

```bash
# Test with bad credentials (manual test)
# Modify wp-config.php temporarily with wrong DB password
```

**Expected:**
- [ ] Exception caught
- [ ] WP_Error returned
- [ ] Error logged to debug.log

---

## G. Interface Compliance Tests

### G1. Interface Implementation Check

```bash
wp eval "
\$classes = [
    '\\Apollo\\Modules\\Documents\\DocumentsSchema',
    '\\Apollo\\Modules\\Chat\\ChatSchema',
    '\\Apollo\\Modules\\Likes\\LikesSchema',
];

foreach (\$classes as \$class) {
    \$instance = new \$class();
    \$implements = \$instance instanceof \\Apollo\\Contracts\\SchemaModuleInterface;
    echo \$class . ': ' . (\$implements ? 'OK' : 'FAIL') . PHP_EOL;
}
"
```

**Expected:**
- [ ] All classes return `OK`

### G2. Method Signature Validation

```bash
wp eval "
\$schema = new \\Apollo\\Modules\\Documents\\DocumentsSchema();

// Test all interface methods exist
\$result1 = \$schema->install();
\$result2 = \$schema->upgrade('2.1.0', '2.2.0');
\$result3 = \$schema->getStatus();
\$schema->uninstall();

echo 'All methods callable: OK';
"
```

**Expected:**
- [ ] No method not found errors
- [ ] `All methods callable: OK` printed

---

## H. Performance Tests

### H1. Install Timing

```bash
time wp apollo schema install --yes
```

**Expected:**
- [ ] Completes in < 5 seconds
- [ ] No timeout errors

### H2. Status Query Timing

```bash
time wp apollo schema status
```

**Expected:**
- [ ] Completes in < 2 seconds
- [ ] No memory issues

---

## Summary Checklist

| Section | Tests | Pass |
|---------|-------|------|
| A. Fresh Installation | 4 | [ ] |
| B. Upgrade Path | 3 | [ ] |
| C. Idempotency | 2 | [ ] |
| D. Module Isolation | 3 | [ ] |
| E. Destructive | 2 | [ ] |
| F. Error Handling | 2 | [ ] |
| G. Interface Compliance | 2 | [ ] |
| H. Performance | 2 | [ ] |

**Total Tests:** 20

---

## Post-Test Cleanup

```bash
# Restore database if needed
wp db import backup-before-reset.sql

# Reset to production state
wp apollo schema install --yes
wp cache flush
```
