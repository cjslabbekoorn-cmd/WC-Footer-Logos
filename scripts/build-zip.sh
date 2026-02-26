#!/usr/bin/env bash
set -euo pipefail

PLUGIN_SLUG="wc-footer-logos"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="${ROOT_DIR}/dist"
VERSION="$(php -r '
$txt = file_get_contents("'"$ROOT_DIR"'/wc-footer-logos.php");
if (preg_match("/Version:\s*([0-9.]+)/", $txt, $matches)) { echo $matches[1]; }
')"

mkdir -p "${DIST_DIR}"
ZIP="${DIST_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"

rm -f "${ZIP}"

# Create zip excluding dev files
cd "${ROOT_DIR}/.."
zip -r "${ZIP}" "${PLUGIN_SLUG}"   -x "${PLUGIN_SLUG}/vendor/*"   -x "${PLUGIN_SLUG}/node_modules/*"   -x "${PLUGIN_SLUG}/dist/*"   -x "${PLUGIN_SLUG}/.github/*"

echo "Built: ${ZIP}"
