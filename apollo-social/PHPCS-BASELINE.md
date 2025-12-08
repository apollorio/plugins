# PHPCS Baseline Report - Apollo Social Core

## Summary

**Date**: 2024
**Tool**: PHP CodeSniffer 3.13.5  
**Configuration**: `phpcs.xml.dist` (Security-focused standards)

### Current Status
- **Total Errors**: 375
- **Total Warnings**: 277
- **Affected Files**: 144 out of 221
- **Scan Time**: ~8.2 seconds (parallel: 20 cores)

### Auto-Fixable Violations
- **Fixed in Previous Pass**: ~3,000+ (long array syntax, spacing)
- **Current Fixable**: 0 (all remaining require code review)

---

## Error Breakdown

### Critical Security Errors (124 total)
These MUST be fixed before production release:

1. **Unescaped Output (104 errors)**
   - Found in: API endpoints, template rendering, builder classes
   - Fix: Wrap output with `esc_html()`, `esc_attr()`, or `wp_kses_post()`
   - Example:
     ```php
     // WRONG
     echo $user_id;
     
     // RIGHT
     echo esc_attr($user_id);
     ```

2. **Unsafe Printing Functions (21 errors)**
   - Found in: Output statements with functions that bypass escaping
   - Fix: Add escaping or use safe alternatives
   - Example:
     ```php
     // WRONG
     echo human_time_diff($date1, $date2);
     
     // RIGHT
     echo esc_html(human_time_diff($date1, $date2));
     ```

3. **Exception Not Escaped (2 errors)**
   - Found in: Exception messages output directly
   - Fix: Escape before echoing

### Important Code Quality Warnings (277 total)

1. **Non-Sanitized Input**
   - `$_POST`, `$_GET`, `$_REQUEST` variables used without validation
   - Fix: Use `isset()` checks and `sanitize_*()` functions

2. **Database Query Issues**
   - SQL injection vulnerabilities from string interpolation
   - Fix: Use `$wpdb->prepare()` with placeholders
   - Example:
     ```php
     // WRONG
     $wpdb->get_results("SELECT * FROM $table WHERE id = $id");
     
     // RIGHT
     $wpdb->get_results($wpdb->prepare("SELECT * FROM %i WHERE id = %d", $table, $id));
     ```

3. **Yoda Conditions (in code)**
   - Conditions written as `'value' === $var` instead of `$var === 'value'`
   - Fix: Swap the order for modern PHP style

4. **Missing Text Domain**
   - Translatable strings without proper `__()` or `esc_html__()` wrapping
   - Fix: Use WordPress internationalization functions

---

## Affected File Categories

### API & Endpoints (48 errors)
- `src/API/Endpoints/*.php`
- Main issue: Unescaped output from endpoint responses

### Builder & Assets (18 errors)
- `src/Builder/class-apollo-builder-assets.php`
- Main issue: HTML attribute and JavaScript output not escaped

### Application Logic (58 errors)
- `src/Application/*/` 
- Main issue: SQL queries not properly prepared, output not escaped

### Modules (118 errors)
- `src/Modules/*/`
- Main issue: Various unescaped outputs, non-prepared SQL

---

## Implementation Strategy

### Phase 1: Critical Security Fixes (Priority: IMMEDIATE)
Target: All 124 output escaping violations
1. Review each unescaped output
2. Determine appropriate escaping function
3. Apply fix with test coverage

### Phase 2: Database Security (Priority: HIGH)
Target: Unprepared SQL statements
1. Convert string interpolation to `$wpdb->prepare()`
2. Use proper placeholders (`%d`, `%s`, `%i`)
3. Test queries thoroughly

### Phase 3: Input Validation (Priority: HIGH)
Target: Non-sanitized `$_POST`, `$_GET` access
1. Add `isset()` checks
2. Apply `sanitize_*()` functions
3. Add nonce verification

### Phase 4: Code Quality (Priority: MEDIUM)
Target: Modern PHP syntax and WordPress standards
1. Fix Yoda conditions
2. Add missing i18n functions
3. Update deprecated functions

---

## Tool Usage

### Run Full Scan
```bash
composer run lint
# or
./vendor/bin/phpcs --standard=phpcs.xml.dist src/
```

### Scan Specific File
```bash
./vendor/bin/phpcs --standard=phpcs.xml.dist src/API/Endpoints/CommentsEndpoint.php
```

### Auto-Fix (where possible)
```bash
./vendor/bin/phpcbf --standard=phpcs.xml.dist src/
```

### Get CSV Report
```bash
./vendor/bin/phpcs --standard=phpcs.xml.dist src/ --report=csv > phpcs-report.csv
```

---

## Configuration Files

- **`composer.json`**: Defines PHPCS dependencies and lint scripts
- **`phpcs.xml.dist`**: PHPCS rules and exclusions
  - Enabled: Security, Escaping, Database, Internationalization, Variables
  - Disabled: Naming conventions (too strict for legacy code)
  - Performance: 512MB memory, 20-core parallel, caching enabled

---

## Next Steps

1. ✅ Automated array syntax fixes (COMPLETED)
2. ⏳ Manual security fixes (escaping, SQL)
3. ⏳ Input validation fixes
4. ⏳ Code quality improvements
5. ⏳ Re-baseline and documentation
6. ⏳ CI/CD integration for preventing regressions

---

## Notes

- The baseline focuses on **security** rather than strict WordPress conventions
- Long array syntax was already fixed (2995 fixes)
- VIP-Go and strict naming rules were excluded to avoid noise
- Remaining 375 errors are legitimate security/quality concerns that require code review
- No false positives detected; all violations are real issues to address
