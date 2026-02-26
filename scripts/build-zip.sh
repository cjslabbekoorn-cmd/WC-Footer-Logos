#!/usr/bin/env bash
set -euo pipefail

PLUGIN_SLUG="wc-footer-logos"

# Repo root = 1 level boven /scripts
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="${ROOT_DIR}/dist"

# Version uit plugin header halen (uit wc-footer-logos.php)
VERSION="$(php -r '
$txt = file_get_contents("'"$ROOT_DIR"'/wc-footer-logos.php");
if (preg_match("/Version:\s*([0-9.]+)/", $txt, $m)) { echo $m[1]; }
')"

if [[ -z "${VERSION}" ]]; then
  echo "ERROR: Could not determine plugin Version from wc-footer-logos.php"
  exit 1
fi

mkdir -p "$DIST_DIR"
ZIP="${DIST_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
rm -f "$ZIP"

# Build in tijdelijke map met correcte topfolder (WordPress verwacht dit)
TMP_DIR="$(mktemp -d)"
BUILD_DIR="${TMP_DIR}/${PLUGIN_SLUG}"
mkdir -p "$BUILD_DIR"

# Kopieer plugin bestanden, exclude dev/CI spul
rsync -a "${ROOT_DIR}/" "${BUILD_DIR}/" \
  --exclude "vendor" \
  --exclude "node_modules" \
  --exclude "dist" \
  --exclude ".github" \
  --exclude ".git" \
  --exclude "scripts" \
  --exclude ".gitignore" \
  --exclude ".gitattributes" \
  --exclude ".editorconfig" \
  --exclude "composer.lock" \
  --exclude "phpcs.xml.dist"

# ZIP met topfolder PLUGIN_SLUG
(
  cd "$TMP_DIR"
  zip -r "$ZIP" "$PLUGIN_SLUG" >/dev/null
)

# Cleanup
rm -rf "$TMP_DIR"

echo "Built: $ZIP"
