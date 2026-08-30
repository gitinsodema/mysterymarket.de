#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT="${1:-$ROOT/build/mysterymarket-web}"

rm -rf -- "$OUT"
mkdir -p -- "$OUT"

git -C "$ROOT" archive --format=tar HEAD | tar -xf - -C "$OUT"

for forbidden in   docs   AGENTS.md   AI_START_HERE.md   README.md   VERSION   .gitignore   .gitattributes
do
  if [ -e "$OUT/$forbidden" ]; then
    echo "[FAIL] production artifact contains forbidden source/control-plane path: $forbidden" >&2
    exit 1
  fi
done

for required in   .htaccess   index.php   contact.php   verify.php   verify-asset.php   verify-card.php   public/css/style.css
do
  if [ ! -e "$OUT/$required" ]; then
    echo "[FAIL] production artifact is missing required path: $required" >&2
    exit 1
  fi
done

echo "[PASS] production web artifact excludes source/control-plane documentation"
echo "[PASS] production web artifact contains required website entry points"
echo "Artifact: $OUT"
