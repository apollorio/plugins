#
# Apollo Core - Pre-commit Hook (PowerShell)
# Prevents commits that touch files outside apollo-core/
#
# Installation:
#   git config core.hooksPath .githooks
#   # For PowerShell: edit .git/hooks/pre-commit and add: powershell -ExecutionPolicy Bypass -File .githooks/pre-commit.ps1
#

Write-Host "🔍 Apollo Core Pre-commit Check..." -ForegroundColor Cyan

# Get list of staged files
$stagedFiles = git diff --cached --name-only --diff-filter=ACM

# Check if any staged files are outside apollo-core/
$invalidFiles = @()
foreach ($file in $stagedFiles) {
    # Allow only files in apollo-core/ or wp-content/plugins/apollo-core/
    if ($file -notmatch '^(wp-content/plugins/)?apollo-core/') {
        $invalidFiles += $file
    }
}

# If invalid files found, block commit
if ($invalidFiles.Count -gt 0) {
    Write-Host ""
    Write-Host "❌ COMMIT BLOCKED!" -ForegroundColor Red
    Write-Host ""
    Write-Host "⚠️  You are trying to commit files OUTSIDE apollo-core/:" -ForegroundColor Yellow
    foreach ($file in $invalidFiles) {
        Write-Host "  - $file" -ForegroundColor Yellow
    }
    Write-Host ""
    Write-Host "🚨 RULE: Only edit files under apollo-core/" -ForegroundColor Red
    Write-Host ""
    Write-Host "Forbidden:"
    Write-Host "  ❌ /wp-admin/**" -ForegroundColor Red
    Write-Host "  ❌ /wp-includes/**" -ForegroundColor Red
    Write-Host "  ❌ /wp-content/themes/**" -ForegroundColor Red
    Write-Host "  ❌ /wp-content/plugins/OUTDATED-*/**" -ForegroundColor Red
    Write-Host "  ❌ Any WordPress core files" -ForegroundColor Red
    Write-Host ""
    Write-Host "Allowed:"
    Write-Host "  ✅ apollo-core/**" -ForegroundColor Green
    Write-Host ""
    Write-Host "To commit anyway (NOT RECOMMENDED):"
    Write-Host "  git commit --no-verify -m 'your message'"
    Write-Host ""
    exit 1
}

# Check for common mistakes
Write-Host "✅ All files are in apollo-core/" -ForegroundColor Green

# Check for debug code
Write-Host "🔍 Checking for debug code..." -ForegroundColor Cyan
$debugPatterns = "var_dump\(|print_r\(|console\.log\(|die\(|exit\(`"debug"
$diffOutput = git diff --cached

if ($diffOutput -match $debugPatterns) {
    Write-Host ""
    Write-Host "⚠️  WARNING: Debug code detected!" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Found patterns: var_dump, print_r, console.log, die, exit" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Please review your changes before committing."
    Write-Host ""
    $response = Read-Host "Continue anyway? (y/N)"
    if ($response -notmatch '^[Yy]$') {
        Write-Host "❌ Commit cancelled." -ForegroundColor Red
        exit 1
    }
}

# Check for hardcoded credentials
Write-Host "🔍 Checking for hardcoded credentials..." -ForegroundColor Cyan
$credPatterns = "password|api_key|secret_key|access_token"

if ($diffOutput -match "($credPatterns).*=.*['`"]") {
    Write-Host ""
    Write-Host "⚠️  WARNING: Possible hardcoded credentials detected!" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Found patterns that might contain sensitive data." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Please review your changes before committing."
    Write-Host ""
    $response = Read-Host "Continue anyway? (y/N)"
    if ($response -notmatch '^[Yy]$') {
        Write-Host "❌ Commit cancelled." -ForegroundColor Red
        exit 1
    }
}

# Check PHP syntax
Write-Host "🔍 Checking PHP syntax..." -ForegroundColor Cyan
$phpFiles = $stagedFiles | Where-Object { $_ -match '\.php$' }

if ($phpFiles) {
    foreach ($file in $phpFiles) {
        if (Test-Path $file) {
            $result = php -l $file 2>&1
            if ($LASTEXITCODE -ne 0) {
                Write-Host ""
                Write-Host "❌ PHP SYNTAX ERROR in: $file" -ForegroundColor Red
                Write-Host ""
                Write-Host $result -ForegroundColor Red
                Write-Host ""
                exit 1
            }
        }
    }
    Write-Host "✅ PHP syntax check passed" -ForegroundColor Green
}

Write-Host ""
Write-Host "✅ Pre-commit checks passed!" -ForegroundColor Green
Write-Host ""

exit 0

