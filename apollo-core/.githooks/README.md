# Apollo Core - Git Hooks

## Pre-commit Hook

This hook prevents commits that:
- Touch files outside `apollo-core/`
- Contain debug code (var_dump, console.log, etc.)
- Contain hardcoded credentials
- Have PHP syntax errors

## Installation

### Unix/Linux/Mac

```bash
# Make hook executable
chmod +x .githooks/pre-commit

# Configure Git to use this hooks directory
git config core.hooksPath .githooks
```

### Windows (PowerShell)

```powershell
# Configure Git to use this hooks directory
git config core.hooksPath .githooks

# For PowerShell support, edit .git/hooks/pre-commit:
powershell -ExecutionPolicy Bypass -File .githooks/pre-commit.ps1
```

## Testing

```bash
# Try committing a file outside apollo-core (should fail)
touch ../some-other-plugin/test.php
git add ../some-other-plugin/test.php
git commit -m "test"

# Expected: ❌ COMMIT BLOCKED!
```

## Bypass (Not Recommended)

If you REALLY need to bypass the hook:

```bash
git commit --no-verify -m "your message"
```

⚠️ **WARNING**: Only use `--no-verify` when absolutely necessary and you understand the risks.

## What It Checks

### 1. File Location
- ✅ Allows: `apollo-core/**`
- ❌ Blocks: `wp-admin/**`, `wp-includes/**`, `wp-content/themes/**`

### 2. Debug Code
- Warns about: `var_dump()`, `print_r()`, `console.log()`, `die()`, `exit("debug")`
- Asks for confirmation before proceeding

### 3. Credentials
- Warns about: `password=`, `api_key=`, `secret_key=`, `access_token=`
- Asks for confirmation before proceeding

### 4. PHP Syntax
- Runs `php -l` on all staged PHP files
- Blocks commit if syntax errors found

## Output Examples

### ✅ Success

```
🔍 Apollo Core Pre-commit Check...
✅ All files are in apollo-core/
🔍 Checking for debug code...
🔍 Checking for hardcoded credentials...
🔍 Checking PHP syntax...
✅ PHP syntax check passed

✅ Pre-commit checks passed!
```

### ❌ Blocked

```
🔍 Apollo Core Pre-commit Check...

❌ COMMIT BLOCKED!

⚠️  You are trying to commit files OUTSIDE apollo-core/:
  - wp-admin/index.php
  - wp-includes/post.php

🚨 RULE: Only edit files under apollo-core/

Forbidden:
  ❌ /wp-admin/**
  ❌ /wp-includes/**
  ❌ Any WordPress core files

Allowed:
  ✅ apollo-core/**

To commit anyway (NOT RECOMMENDED):
  git commit --no-verify -m "your message"
```

### ⚠️ Warning

```
🔍 Apollo Core Pre-commit Check...
✅ All files are in apollo-core/
🔍 Checking for debug code...

⚠️  WARNING: Debug code detected!

Found patterns: var_dump, print_r

Please review your changes before committing.

Continue anyway? (y/N)
```

## Troubleshooting

### Hook Not Running

```bash
# Check hooks path
git config core.hooksPath

# Should output: .githooks
```

### Permission Denied (Unix)

```bash
# Make executable
chmod +x .githooks/pre-commit
```

### PowerShell Execution Policy

```powershell
# Allow script execution
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

## Customization

Edit `.githooks/pre-commit` (or `.ps1` for PowerShell) to:
- Add more file patterns
- Change warning messages
- Add custom checks
- Adjust regex patterns

## Integration with CI/CD

This hook runs locally. For server-side validation, add similar checks to your CI/CD pipeline (GitHub Actions, GitLab CI, etc.).

