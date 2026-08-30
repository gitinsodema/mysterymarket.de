#!/usr/bin/env bash
set -euo pipefail

BASE="${1:-https://mysterymarket.de}"

fail=0

check_status() {
  local expected="$1"
  local url="$2"
  local method="${3:-GET}"
  local actual
  actual="$(curl -sS -o /dev/null -w '%{http_code}' -X "$method" "$url" || true)"
  if [ "$actual" = "$expected" ]; then
    echo "[PASS] $method $url -> $actual"
  else
    echo "[FAIL] $method $url -> $actual (expected $expected)" >&2
    fail=1
  fi
}

check_header() {
  local url="$1"
  local pattern="$2"
  if curl -sS -D - -o /dev/null "$url" | grep -qiE "$pattern"; then
    echo "[PASS] header $pattern"
  else
    echo "[FAIL] missing header $pattern on $url" >&2
    fail=1
  fi
}

for path in   /   /services.php   /audits.php   /verify.php   /tools.php   /elite-shopper.php   /about.php   /contact.php   /legal-notice.php   /privacy.php
do
  check_status 200 "$BASE$path"
done

check_status 403 "$BASE/docs/coordination/PRODUCT_HANDOFF.json"
check_status 403 "$BASE/AI_START_HERE.md"
check_status 403 "$BASE/VERSION"

git_status="$(curl -sS -o /dev/null -w '%{http_code}' "$BASE/.git/config" || true)"
if [ "$git_status" = "403" ] || [ "$git_status" = "404" ]; then
  echo "[PASS] GET $BASE/.git/config -> $git_status"
else
  echo "[FAIL] GET $BASE/.git/config -> $git_status (expected 403 or 404)" >&2
  fail=1
fi

check_status 200 "$BASE/verify"
check_status 405 "$BASE/contact.php" PUT
check_status 405 "$BASE/verify.php" DELETE
check_status 403 "$BASE/verify-asset.php?code=MM-K4AD8HQR&type=document" GET
check_status 403 "$BASE/verify-card.php?code=MM-K4AD8HQR" GET

check_header "$BASE/" '^content-security-policy:'
check_header "$BASE/" '^permissions-policy:.*camera=\(self\)'
check_header "$BASE/" '^cross-origin-opener-policy:.*same-origin'
check_header "$BASE/" '^cross-origin-resource-policy:.*same-origin'
check_header "$BASE/" '^x-content-type-options:.*nosniff'
check_header "$BASE/" '^x-frame-options:.*SAMEORIGIN'
check_header "$BASE/" '^referrer-policy:.*strict-origin-when-cross-origin'

check_header "$BASE/contact.php" '^cache-control:.*no-store'
check_header "$BASE/verify.php" '^cache-control:.*no-store'

oversize="$(head -c 33000 /dev/zero | tr '\0' a | curl -sS -o /dev/null -w '%{http_code}'   -X POST -H 'Content-Type: application/x-www-form-urlencoded' --data-binary @- "$BASE/contact.php" || true)"
if [ "$oversize" = "413" ]; then
  echo "[PASS] oversized contact POST -> 413"
else
  echo "[FAIL] oversized contact POST -> $oversize (expected 413)" >&2
  fail=1
fi

if [ "$fail" -ne 0 ]; then
  echo "MYSTERYMARKET_RELEASE_SMOKE_FAILED" >&2
  exit 1
fi

echo "MYSTERYMARKET_RELEASE_SMOKE_OK"
