#!/usr/bin/env bash
set -euo pipefail

PLUGIN_SLUG="wc-footer-logos"

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="${ROOT_DIR}/dist"

VERSION="$(php -r '
$txt = file_get_contents("'"$ROOT_DIR"'/wc-footer-logos.php");
if (preg_match("/Version:\s*([0-9.]+)/", $txt, $matches)) { echo $matches[1]; }
')"

mkdir -p "$DIST_DIR"
ZIP="${DIST_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
rm -f "$ZIP"

# We zippen de contents van ROOT_DIR, maar plaatsen ze in een topfolder met PLUGIN_SLUG
cd "$ROOT_DIR"
zip -r "$ZIP" . \
  -x "vendor/*" \
  -x "node_modules/*" \
  -x "dist/*" \
  -x ".github/*" \
  -x ".git/*" \
  -x ".gitignore" \
  -x ".gitattributes" \
  -x ".editorconfig" \
  -x "composer.lock" \
  -x "phpcs.xml.dist" \
  -x "scripts/*"

echo "Built: $ZIP"
