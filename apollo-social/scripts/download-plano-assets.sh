#!/bin/bash
# Bash script to download Plano editor assets locally
# NO CDN - ALL LOCAL
# Run: bash scripts/download-plano-assets.sh

set -e

BASE_DIR="$(cd "$(dirname "$0")/.." && pwd)"
JS_DIR="$BASE_DIR/assets/js"
FONTS_DIR="$BASE_DIR/assets/fonts"
REMIXICON_DIR="$FONTS_DIR/remixicon"
INTER_DIR="$FONTS_DIR/inter"

echo "=== Apollo Plano Editor - Download Assets (LOCAL ONLY) ==="
echo ""

# Create directories
echo "Creating directories..."
mkdir -p "$JS_DIR"
mkdir -p "$FONTS_DIR"
mkdir -p "$REMIXICON_DIR"
mkdir -p "$INTER_DIR"
echo "✓ Directories created"
echo ""

# Download Fabric.js
echo "Downloading Fabric.js 5.3.0..."
FABRIC_URL="https://cdn.jsdelivr.net/npm/fabric@5.3.0/dist/fabric.min.js"
FABRIC_PATH="$JS_DIR/fabric.min.js"
if curl -f -L "$FABRIC_URL" -o "$FABRIC_PATH" 2>/dev/null; then
    echo "✓ Fabric.js downloaded"
else
    echo "✗ Failed to download Fabric.js"
    exit 1
fi

# Download html2canvas
echo "Downloading html2canvas 1.4.1..."
HTML2CANVAS_URL="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"
HTML2CANVAS_PATH="$JS_DIR/html2canvas.min.js"
if curl -f -L "$HTML2CANVAS_URL" -o "$HTML2CANVAS_PATH" 2>/dev/null; then
    echo "✓ html2canvas downloaded"
else
    echo "✗ Failed to download html2canvas"
    exit 1
fi

# Download Sortable.js
echo "Downloading Sortable.js 1.15.0..."
SORTABLE_URL="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"
SORTABLE_PATH="$JS_DIR/sortable.min.js"
if curl -f -L "$SORTABLE_URL" -o "$SORTABLE_PATH" 2>/dev/null; then
    echo "✓ Sortable.js downloaded"
else
    echo "✗ Failed to download Sortable.js"
    exit 1
fi

# Download Remixicon CSS
echo "Downloading Remixicon CSS 2.5.0..."
REMIXICON_CSS_URL="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css"
REMIXICON_CSS_PATH="$FONTS_DIR/remixicon.css"
if curl -f -L "$REMIXICON_CSS_URL" -o "$REMIXICON_CSS_PATH" 2>/dev/null; then
    echo "✓ Remixicon CSS downloaded"
    # Update CSS to use local fonts
    sed -i.bak 's|url("https://cdn.jsdelivr.net[^"]*")|url("./remixicon/remixicon.woff2")|g' "$REMIXICON_CSS_PATH"
    rm -f "$REMIXICON_CSS_PATH.bak"
    echo "✓ Remixicon CSS updated"
else
    echo "✗ Failed to download Remixicon CSS"
    exit 1
fi

# Download Remixicon fonts
echo "Downloading Remixicon fonts..."
REMIXICON_FONT_URL="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.woff2"
REMIXICON_FONT_PATH="$REMIXICON_DIR/remixicon.woff2"
if curl -f -L "$REMIXICON_FONT_URL" -o "$REMIXICON_FONT_PATH" 2>/dev/null; then
    echo "✓ Remixicon font downloaded"
else
    echo "✗ Failed to download Remixicon font"
fi

echo ""
echo "=== IMPORTANT ==="
echo "Inter font: Use system fonts or download manually from Google Fonts"
echo "Place Inter font files in: $INTER_DIR"
echo ""
echo "=== Download Complete ==="
echo "All assets are now LOCAL - NO CDN dependencies"
echo ""

