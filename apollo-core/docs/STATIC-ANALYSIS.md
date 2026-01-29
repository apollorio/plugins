# Static Analysis Configuration - Apollo Plugins

**Date:** 2025-01-13  
**Tools:** PHPCS, PHPCBF, PHPStan, PHPCompatibility

---

## Overview

This document describes the static analysis setup for achieving zero errors/warnings in the Apollo plugin ecosystem.

---

## Prerequisites

### Install Dependencies

```bash
cd /path/to/wp-content/plugins
composer install
```

### Required Packages (in composer.json)

```json
{
    "require-dev": {
        "squizlabs/php_codesniffer": "^3.7",
        "phpstan/phpstan": "^2.1",
        "wp-coding-standards/wpcs": "^3.3",
        "phpcompatibility/phpcompatibility-wp": "^2.1",
        "sirbrillig/phpcs-variable-analysis": "^2.11",
        "php-stubs/wordpress-stubs": "^6.4",
        "szepeviktor/phpstan-wordpress": "^2.0"
    }
}
```

---

## PHPCS Configuration

### Configuration File: `phpcs.xml.dist`

```xml
<?xml version="1.0"?>
<ruleset name="Apollo">
    <description>Coding standards for Apollo WordPress plugins</description>

    <!-- Paths to check -->
    <file>./apollo-core</file>
    <file>./apollo-events-manager</file>
    <file>./apollo-social</file>
    <file>./apollo-email-newsletter</file>
    <file>./apollo-email-templates</file>
    <file>./apollo-hardening</file>
    <file>./apollo-rio</file>
    <file>./apollo-secure-upload</file>
    <file>./apollo-webp-compressor</file>

    <!-- Exclude vendor and node_modules -->
    <exclude-pattern>*/vendor/*</exclude-pattern>
    <exclude-pattern>*/node_modules/*</exclude-pattern>
    <exclude-pattern>*/.git/*</exclude-pattern>
    <exclude-pattern>*/tests/*</exclude-pattern>

    <!-- Use WordPress Coding Standards -->
    <rule ref="WordPress-Core"/>
    <rule ref="WordPress-Extra"/>
    <rule ref="WordPress.Security"/>

    <!-- PHP Compatibility -->
    <rule ref="PHPCompatibilityWP"/>
    <config name="testVersion" value="8.1-"/>

    <!-- WordPress version -->
    <config name="minimum_supported_wp_version" value="6.4"/>

    <!-- Text domains -->
    <rule ref="WordPress.WP.I18n">
        <properties>
            <property name="text_domain" type="array">
                <element value="apollo-core"/>
                <element value="apollo-events-manager"/>
                <element value="apollo-social"/>
            </property>
        </properties>
    </rule>

    <!-- Prefixes -->
    <rule ref="WordPress.NamingConventions.PrefixAllGlobals">
        <properties>
            <property name="prefixes" type="array">
                <element value="apollo"/>
                <element value="Apollo"/>
                <element value="APOLLO"/>
            </property>
        </properties>
    </rule>

    <!-- Variable analysis -->
    <rule ref="VariableAnalysis"/>

    <!-- Allow short array syntax -->
    <rule ref="Generic.Arrays.DisallowShortArraySyntax">
        <severity>0</severity>
    </rule>

    <!-- Modern PHP features -->
    <rule ref="Universal.UseStatements.DisallowMixedGroupUse"/>
</ruleset>
```

---

## PHPStan Configuration

### Configuration File: `phpstan.neon.dist`

```neon
includes:
    - vendor/szepeviktor/phpstan-wordpress/extension.neon

parameters:
    level: 6
    
    paths:
        - apollo-core
        - apollo-events-manager
        - apollo-social
        - apollo-email-newsletter
        - apollo-email-templates
        - apollo-hardening
        - apollo-rio
        - apollo-secure-upload
        - apollo-webp-compressor

    excludePaths:
        - */vendor/*
        - */node_modules/*
        - */tests/*

    bootstrapFiles:
        - vendor/php-stubs/wordpress-stubs/wordpress-stubs.php

    checkMissingIterableValueType: false
    checkGenericClassInNonGenericObjectType: false

    ignoreErrors:
        # WordPress dynamic function calls
        - '#Function [a-z_]+ not found#'
        # WooCommerce integration
        - '#Class WC_[A-Za-z_]+ not found#'
        # WordPress hooks with mixed types
        - '#expects array<string>, array<int, mixed> given#'

    dynamicConstantNames:
        - APOLLO_CORE_VERSION
        - APOLLO_CORE_PLUGIN_DIR
        - APOLLO_CORE_PLUGIN_URL
        - APOLLO_APRIO_VERSION
        - APOLLO_APRIO_PATH
        - APOLLO_APRIO_URL
        - APOLLO_DEBUG
        - WP_DEBUG
```

---

## Running Static Analysis

### PHPCS - Check Coding Standards

```bash
# Check all files
vendor/bin/phpcs

# Check specific plugin
vendor/bin/phpcs apollo-core/

# Show only errors (no warnings)
vendor/bin/phpcs -n

# Generate report
vendor/bin/phpcs --report=json > phpcs-report.json
```

### PHPCBF - Auto-Fix Issues

```bash
# Fix all auto-fixable issues
vendor/bin/phpcbf

# Fix specific plugin
vendor/bin/phpcbf apollo-core/

# Dry run (show what would be fixed)
vendor/bin/phpcbf --dry-run
```

### PHPStan - Type Analysis

```bash
# Run analysis
vendor/bin/phpstan analyse

# Generate baseline (for legacy code)
vendor/bin/phpstan analyse --generate-baseline

# Run with specific level
vendor/bin/phpstan analyse --level=5
```

---

## Common Issues and Fixes

### 1. Missing Escaping

**Error:**
```
WordPress.Security.EscapeOutput.OutputNotEscaped
```

**Fix:**
```php
// Before
echo $value;

// After
echo esc_html($value);
echo esc_attr($value); // for attributes
echo esc_url($value);  // for URLs
echo wp_kses_post($value); // for HTML content
```

### 2. Missing Nonce Verification

**Error:**
```
WordPress.Security.NonceVerification.Missing
```

**Fix:**
```php
// Before
if (isset($_POST['action'])) { ... }

// After
if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'action_name')) {
    wp_die('Security check failed');
}
if (isset($_POST['action'])) { ... }
```

### 3. Direct Database Query

**Error:**
```
WordPress.DB.DirectDatabaseQuery.DirectQuery
```

**Fix:**
```php
// Add caching
$cache_key = 'apollo_query_' . md5($sql);
$results = wp_cache_get($cache_key);
if (false === $results) {
    $results = $wpdb->get_results($wpdb->prepare($sql, $args));
    wp_cache_set($cache_key, $results, '', 3600);
}

// Or add ignore comment if caching is not appropriate
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$results = $wpdb->get_results($wpdb->prepare($sql, $args));
```

### 4. Unprepared SQL

**Error:**
```
WordPress.DB.PreparedSQL.NotPrepared
```

**Fix:**
```php
// Before
$wpdb->get_results("SELECT * FROM $table WHERE id = $id");

// After
$wpdb->get_results(
    $wpdb->prepare("SELECT * FROM %i WHERE id = %d", $table, $id)
);
```

### 5. Variable Analysis

**Error:**
```
VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
```

**Fix:**
```php
// Ensure variable is defined before use
$value = isset($value) ? $value : '';
```

---

## CI/CD Integration

### GitHub Actions Workflow

```yaml
name: Static Analysis

on: [push, pull_request]

jobs:
  phpcs:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - run: composer install
      - run: vendor/bin/phpcs

  phpstan:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - run: composer install
      - run: vendor/bin/phpstan analyse
```

---

## Baseline Management

For legacy code with many existing issues, use baselines:

### Generate Baseline

```bash
# PHPCS baseline
vendor/bin/phpcs --report=json > .phpcs-baseline.json

# PHPStan baseline
vendor/bin/phpstan analyse --generate-baseline
```

### Use Baseline

```bash
# PHPCS (compare against baseline)
vendor/bin/phpcs --report-diff=.phpcs-baseline.json

# PHPStan (uses phpstan-baseline.neon automatically)
vendor/bin/phpstan analyse
```

---

## Target: Zero Errors

### Current Status

| Tool | Errors | Warnings | Target |
|------|--------|----------|--------|
| PHPCS | TBD | TBD | 0 |
| PHPStan L6 | TBD | - | 0 |

### Action Plan

1. Run PHPCBF to auto-fix formatting issues
2. Manually fix remaining PHPCS errors
3. Run PHPStan and fix type errors
4. Generate baseline for unfixable legacy issues
5. Add pre-commit hook to prevent new issues

---

## Pre-Commit Hook

Install a pre-commit hook to prevent introducing new issues:

```bash
# .githooks/pre-commit
#!/bin/bash

# Run PHPCS on staged PHP files
STAGED_FILES=$(git diff --cached --name-only --diff-filter=ACM | grep '\.php$')

if [ -n "$STAGED_FILES" ]; then
    vendor/bin/phpcs $STAGED_FILES
    if [ $? -ne 0 ]; then
        echo "PHPCS failed. Please fix errors before committing."
        exit 1
    fi
fi
```

Enable the hook:
```bash
git config core.hooksPath .githooks
```

---

**Document Version:** 1.0.0  
**Last Updated:** 2025-01-13
