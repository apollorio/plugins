# Apollo-Core Strict Mode Implementation Plan

**Status**: 🟡 Ready to Execute  
**Priority**: 🔴 CRITICAL  
**Estimated Time**: 12-19 hours  
**Target Completion**: Before Production Deployment

---

## Phase 1: Critical Security Fixes (5-7 hours)

### Task 1.1: Add Nonce Verification (2-3 hours)

**Files to Update**:
1. `admin/moderation-page.php`
2. `admin/forms-admin.php`
3. `admin/moderate-users-membership.php`

**Implementation**:

```php
// Pattern for all admin forms
<?php wp_nonce_field( 'apollo_action_name', 'apollo_nonce' ); ?>

// Pattern for all handlers
if ( ! isset( $_POST['apollo_nonce'] ) || ! wp_verify_nonce( $_POST['apollo_nonce'], 'apollo_action_name' ) ) {
    wp_die( __( 'Security check failed', 'apollo-core' ) );
}
```

**Checklist**:
- [ ] Add nonce to moderation settings form
- [ ] Add nonce to membership editor form
- [ ] Add nonce to forms admin
- [ ] Add handlers with nonce verification
- [ ] Test all forms after changes

---

### Task 1.2: Implement REST API Rate Limiting (3-4 hours)

**New File**: `includes/rest-rate-limiting.php`

```php
<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Check rate limit for REST endpoint
 *
 * @param WP_REST_Request $request Request object.
 * @return bool|WP_Error True if within limit, WP_Error if exceeded.
 */
function apollo_rest_rate_limit_check( WP_REST_Request $request ) {
    $endpoint = $request->get_route();
    $user_id = get_current_user_id();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Different limits for different endpoints
    $limits = array(
        '/apollo/v1/forms/submit' => 10,  // 10 per minute
        '/apollo/v1/quiz/attempt' => 5,   // 5 per minute
        'default' => 100,                  // 100 per minute
    );
    
    $limit = $limits[ $endpoint ] ?? $limits['default'];
    
    $key = "apollo_rate_limit_{$endpoint}_{$user_id}_{$ip}";
    $attempts = get_transient( $key );
    
    if ( false === $attempts ) {
        $attempts = 0;
    }
    
    $attempts++;
    
    if ( $attempts > $limit ) {
        return new WP_Error(
            'rate_limit_exceeded',
            sprintf( 
                __( 'Rate limit exceeded. Maximum %d requests per minute.', 'apollo-core' ),
                $limit
            ),
            array( 'status' => 429 )
        );
    }
    
    set_transient( $key, $attempts, 60 );
    
    return true;
}

/**
 * Middleware to add rate limiting to REST endpoint
 *
 * @param mixed           $result  Response to replace the requested version with.
 * @param WP_REST_Server  $server  Server instance.
 * @param WP_REST_Request $request Request used to generate the response.
 * @return mixed
 */
function apollo_rest_rate_limit_middleware( $result, $server, $request ) {
    $route = $request->get_route();
    
    // Only apply to apollo endpoints
    if ( 0 !== strpos( $route, '/apollo/v1' ) ) {
        return $result;
    }
    
    $check = apollo_rest_rate_limit_check( $request );
    
    if ( is_wp_error( $check ) ) {
        return $check;
    }
    
    return $result;
}
add_filter( 'rest_pre_dispatch', 'apollo_rest_rate_limit_middleware', 10, 3 );
```

**Checklist**:
- [ ] Create `includes/rest-rate-limiting.php`
- [ ] Add `require_once` in `apollo-core.php`
- [ ] Test rate limiting on all endpoints
- [ ] Verify 429 status code returned
- [ ] Add unit tests for rate limiting

---

## Phase 2: PHP Strict Mode Compliance (4-6 hours)

### Task 2.1: Add Strict Types to All Files (4-6 hours)

**Script to Automate**:

```bash
#!/bin/bash
# add-strict-types.sh

for file in $(find ./includes ./admin ./modules ./public -name "*.php"); do
    # Check if file already has declare(strict_types=1)
    if ! grep -q "declare(strict_types=1)" "$file"; then
        # Get first line (<?php)
        first_line=$(head -n 1 "$file")
        
        if [ "$first_line" = "<?php" ]; then
            # Create temp file with declare statement
            {
                echo "<?php"
                echo "declare(strict_types=1);"
                echo ""
                tail -n +2 "$file"
            } > "$file.tmp"
            
            mv "$file.tmp" "$file"
            echo "✅ Added strict types to $file"
        fi
    fi
done
```

**Manual Pattern**:

```php
<?php
declare(strict_types=1);

/**
 * File header comment
 * ...
 */
```

**Checklist**:
- [ ] Run script or manually update all 49 files
- [ ] Test plugin activation
- [ ] Run PHP syntax check
- [ ] Verify no type errors
- [ ] Commit changes

---

## Phase 3: Type Hints (8-10 hours) - HIGH PRIORITY

### Task 3.1: Add Type Hints to Critical Functions (8-10 hours)

**Priority Files** (in order):
1. `includes/db-schema.php` - 5 functions
2. `includes/memberships.php` - 15 functions
3. `includes/forms/schema-manager.php` - 12 functions
4. `includes/quiz/schema-manager.php` - 18 functions
5. `includes/rest-*.php` - All REST callbacks

**Pattern**:

```php
// Before
function apollo_mod_log_action( $actor_id, $action, $target_type, $target_id, $details = array() ) {

// After
function apollo_mod_log_action( 
    int $actor_id, 
    string $action, 
    string $target_type, 
    int $target_id, 
    array $details = array() 
): bool {
```

**Checklist**:
- [ ] Update `includes/db-schema.php`
- [ ] Update `includes/memberships.php`
- [ ] Update `includes/forms/schema-manager.php`
- [ ] Update `includes/quiz/schema-manager.php`
- [ ] Update all REST callbacks
- [ ] Run PHPStan level 1
- [ ] Fix type errors
- [ ] Run tests

---

## Phase 4: Performance Optimizations (2-3 hours) - HIGH PRIORITY

### Task 4.1: Implement Caching (2-3 hours)

**Files to Update**:
1. `includes/quiz/schema-manager.php`
2. `includes/forms/schema-manager.php`
3. `includes/memberships.php`

**Pattern**:

```php
function apollo_get_quiz_schema( string $form_type ): array {
    $cache_key = "apollo_quiz_schema_{$form_type}";
    $cached = wp_cache_get( $cache_key, 'apollo_quiz' );
    
    if ( false !== $cached ) {
        return $cached;
    }
    
    // ...fetch from database
    
    wp_cache_set( $cache_key, $schema, 'apollo_quiz', 3600 );
    
    return $schema;
}

// Invalidate cache when schema changes
function apollo_save_quiz_schema( string $form_type, array $schema ): bool {
    $result = update_option( APOLLO_QUIZ_SCHEMAS_OPTION, $schemas );
    
    if ( $result ) {
        wp_cache_delete( "apollo_quiz_schema_{$form_type}", 'apollo_quiz' );
    }
    
    return $result;
}
```

**Checklist**:
- [ ] Add caching to quiz schema functions
- [ ] Add caching to forms schema functions
- [ ] Add caching to membership functions
- [ ] Test cache hits/misses
- [ ] Verify cache invalidation works

---

## Phase 5: Error Handling (2-3 hours) - HIGH PRIORITY

### Task 5.1: Add Try-Catch Blocks (2-3 hours)

**Files to Update**:
1. `includes/forms/rest.php`
2. `includes/quiz/rest.php`
3. `includes/rest-membership.php`

**Pattern**:

```php
function apollo_rest_submit_form( WP_REST_Request $request ) {
    try {
        // Risky operation
        $result = apollo_create_user_from_form( $data );
        
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        
        return new WP_REST_Response(
            array( 'success' => true, 'user_id' => $result ),
            200
        );
        
    } catch ( Exception $e ) {
        error_log( 'Apollo Core Error: ' . $e->getMessage() );
        
        return new WP_Error(
            'operation_failed',
            __( 'Operation failed. Please try again.', 'apollo-core' ),
            array( 'status' => 500 )
        );
    }
}
```

**Checklist**:
- [ ] Add try-catch to form submission
- [ ] Add try-catch to quiz submission
- [ ] Add try-catch to membership changes
- [ ] Test error scenarios
- [ ] Verify error logging

---

## Testing Checklist

### Security Testing
- [ ] Test CSRF protection on all forms
- [ ] Test rate limiting on all endpoints
- [ ] Test permission checks on all actions
- [ ] Test SQL injection vectors (none should work)
- [ ] Test XSS vectors (none should work)

### Functional Testing
- [ ] Test form submissions with strict types
- [ ] Test quiz attempts with strict types
- [ ] Test membership changes with strict types
- [ ] Test moderation actions with strict types
- [ ] Test REST API with strict types

### Performance Testing
- [ ] Measure cache hit rates
- [ ] Measure response times before/after caching
- [ ] Test under load (100+ concurrent requests)

---

## Rollout Plan

### Pre-Deployment
1. Run all automated tests
2. Run PHPCS with WordPress standards
3. Run PHPStan level 1
4. Manual security review
5. Performance benchmarks

### Deployment
1. Backup current plugin
2. Deploy to staging
3. Run smoke tests
4. Deploy to production
5. Monitor error logs

### Post-Deployment
1. Monitor error logs for 24 hours
2. Check performance metrics
3. Review rate limit logs
4. Address any issues

---

## Success Criteria

- [ ] **Security**: All forms have nonce verification
- [ ] **Security**: All endpoints have rate limiting
- [ ] **Code Quality**: All files use strict types
- [ ] **Code Quality**: Critical functions have type hints
- [ ] **Performance**: Caching reduces DB queries by 50%+
- [ ] **Error Handling**: All risky operations wrapped in try-catch
- [ ] **Testing**: All tests pass
- [ ] **Standards**: PHPCS reports 0 errors

---

## Risk Mitigation

### Risk: Strict types cause runtime errors
**Mitigation**: 
- Test thoroughly before deployment
- Have rollback plan ready
- Monitor error logs closely

### Risk: Rate limiting blocks legitimate users
**Mitigation**:
- Set generous limits initially
- Monitor rate limit hits
- Adjust limits based on usage patterns

### Risk: Caching causes stale data
**Mitigation**:
- Implement proper cache invalidation
- Use short TTLs initially (5 minutes)
- Test cache invalidation thoroughly

---

## Contact for Issues

If any issues arise during implementation:
1. Check error logs: `wp-content/debug.log`
2. Disable strict types temporarily to isolate issue
3. Roll back to previous version if critical
4. Document issue for post-mortem

---

**Last Updated**: 2025-11-25  
**Status**: Ready to begin implementation  
**Next Step**: Execute Phase 1 - Critical Security Fixes

