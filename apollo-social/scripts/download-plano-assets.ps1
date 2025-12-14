# PowerShell script to download Plano editor assets locally
# NO CDN - ALL LOCAL
# Run: .\scripts\download-plano-assets.ps1

$ErrorActionPreference = "Stop"

$baseDir = Split-Path -Parent $PSScriptRoot
$jsDir = Join-Path $baseDir "assets\js"
$fontsDir = Join-Path $baseDir "assets\fonts"
$remixiconDir = Join-Path $fontsDir "remixicon"
$interDir = Join-Path $fontsDir "inter"

Write-Host "=== Apollo Plano Editor - Download Assets (LOCAL ONLY) ===" -ForegroundColor Green
Write-Host ""

# Create directories
Write-Host "Creating directories..." -ForegroundColor Yellow
New-Item -ItemType Directory -Force -Path $jsDir | Out-Null
New-Item -ItemType Directory -Force -Path $fontsDir | Out-Null
New-Item -ItemType Directory -Force -Path $remixiconDir | Out-Null
New-Item -ItemType Directory -Force -Path $interDir | Out-Null
Write-Host "✓ Directories created" -ForegroundColor Green
Write-Host ""

# Download Fabric.js
Write-Host "Downloading Fabric.js 5.3.0..." -ForegroundColor Yellow
$fabricUrl = "https://cdn.jsdelivr.net/npm/fabric@5.3.0/dist/fabric.min.js"
$fabricPath = Join-Path $jsDir "fabric.min.js"
try {
    Invoke-WebRequest -Uri $fabricUrl -OutFile $fabricPath -UseBasicParsing
    Write-Host "✓ Fabric.js downloaded" -ForegroundColor Green
} catch {
    Write-Host "✗ Failed to download Fabric.js: $_" -ForegroundColor Red
    exit 1
}

# Download html2canvas
Write-Host "Downloading html2canvas 1.4.1..." -ForegroundColor Yellow
$html2canvasUrl = "https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"
$html2canvasPath = Join-Path $jsDir "html2canvas.min.js"
try {
    Invoke-WebRequest -Uri $html2canvasUrl -OutFile $html2canvasPath -UseBasicParsing
    Write-Host "✓ html2canvas downloaded" -ForegroundColor Green
} catch {
    Write-Host "✗ Failed to download html2canvas: $_" -ForegroundColor Red
    exit 1
}

# Download Sortable.js
Write-Host "Downloading Sortable.js 1.15.0..." -ForegroundColor Yellow
$sortableUrl = "https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"
$sortablePath = Join-Path $jsDir "sortable.min.js"
try {
    Invoke-WebRequest -Uri $sortableUrl -OutFile $sortablePath -UseBasicParsing
    Write-Host "✓ Sortable.js downloaded" -ForegroundColor Green
} catch {
    Write-Host "✗ Failed to download Sortable.js: $_" -ForegroundColor Red
    exit 1
}

# Download Remixicon CSS
Write-Host "Downloading Remixicon CSS 2.5.0..." -ForegroundColor Yellow
$remixiconCssUrl = "https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css"
$remixiconCssPath = Join-Path $fontsDir "remixicon.css"
try {
    Invoke-WebRequest -Uri $remixiconCssUrl -OutFile $remixiconCssPath -UseBasicParsing
    Write-Host "✓ Remixicon CSS downloaded" -ForegroundColor Green
} catch {
    Write-Host "✗ Failed to download Remixicon CSS: $_" -ForegroundColor Red
    exit 1
}

# Download Remixicon fonts (woff2 files)
Write-Host "Downloading Remixicon fonts..." -ForegroundColor Yellow
$remixiconFonts = @(
    "https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.woff2"
)
foreach ($fontUrl in $remixiconFonts) {
    $fontName = Split-Path -Leaf $fontUrl
    $fontPath = Join-Path $remixiconDir $fontName
    try {
        Invoke-WebRequest -Uri $fontUrl -OutFile $fontPath -UseBasicParsing
        Write-Host "  ✓ $fontName downloaded" -ForegroundColor Green
    } catch {
        Write-Host "  ✗ Failed to download $fontName: $_" -ForegroundColor Red
    }
}

# Update Remixicon CSS to use local fonts
Write-Host "Updating Remixicon CSS paths..." -ForegroundColor Yellow
$remixiconCssContent = Get-Content $remixiconCssPath -Raw
$remixiconCssContent = $remixiconCssContent -replace 'url\(["'']?https://cdn\.jsdelivr\.net[^"'']+["'']?\)', 'url("./remixicon/remixicon.woff2")'
Set-Content -Path $remixiconCssPath -Value $remixiconCssContent
Write-Host "✓ Remixicon CSS updated" -ForegroundColor Green

# Note: Inter font should be downloaded separately or use system fonts
Write-Host ""
Write-Host "=== IMPORTANT ===" -ForegroundColor Yellow
Write-Host "Inter font: Use system fonts or download manually from Google Fonts" -ForegroundColor Yellow
Write-Host "Place Inter font files in: $interDir" -ForegroundColor Yellow
Write-Host ""

Write-Host "=== Download Complete ===" -ForegroundColor Green
Write-Host "All assets are now LOCAL - NO CDN dependencies" -ForegroundColor Green
Write-Host ""
