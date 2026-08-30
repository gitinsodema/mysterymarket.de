#!/usr/bin/env bash
set -euo pipefail

BASE="${1:-https://mysterymarket.de}"
fail=0

check_status() {
  local expected="$1"
  local url="$2"
  local actual
  actual="$(curl -sS -o /dev/null -w '%{http_code}' "$url" || true)"
  if [ "$actual" = "$expected" ]; then
    echo "[PASS] $url -> $actual"
  else
    echo "[FAIL] $url -> $actual (expected $expected)" >&2
    fail=1
  fi
}

check_body() {
  local url="$1"
  local pattern="$2"
  local body

  if ! body="$(curl -sS "$url")"; then
    echo "[FAIL] could not load $url" >&2
    fail=1
    return
  fi

  if grep -qE "$pattern" <<<"$body"; then
    echo "[PASS] content $url"
  else
    echo "[FAIL] expected content not found on $url" >&2
    fail=1
  fi
}

check_absent() {
  local url="$1"
  local pattern="$2"
  local body

  if ! body="$(curl -sS "$url")"; then
    echo "[FAIL] could not load $url" >&2
    fail=1
    return
  fi

  if grep -qE "$pattern" <<<"$body"; then
    echo "[FAIL] unwanted public content found on $url" >&2
    fail=1
  else
    echo "[PASS] unwanted public content absent on $url"
  fi
}

for lang in de en nl; do
  suffix=""
  if [ "$lang" != "de" ]; then
    suffix="?lang=$lang"
  fi

  for path in     /     /services.php     /audits.php     /tools.php     /elite-shopper.php     /about.php     /contact.php     /legal-notice.php     /privacy.php
  do
    check_status 200 "$BASE$path$suffix"
  done
done

check_status 200 "$BASE/verify"
check_body "$BASE/elite-shopper.php" 'Elite Shopper Login'

for path in / /services.php /elite-shopper.php /about.php /contact.php /legal-notice.php /privacy.php; do
  check_absent "$BASE$path" '@mysterymarket\.de'
done

check_status 404 "$BASE/this-page-must-not-exist-v1"
check_body "$BASE/this-page-must-not-exist-v1" '404'

check_status 200 "$BASE/robots.txt"
check_body "$BASE/robots.txt" 'Sitemap: https://mysterymarket.de/sitemap.xml'

check_status 200 "$BASE/sitemap.xml"
check_body "$BASE/sitemap.xml" '<urlset'
check_body "$BASE/sitemap.xml" 'hreflang="de"'
check_body "$BASE/sitemap.xml" 'hreflang="en"'
check_body "$BASE/sitemap.xml" 'hreflang="nl"'

for path in / /services.php /audits.php /tools.php /elite-shopper.php /about.php /contact.php /legal-notice.php /privacy.php; do
  check_body "$BASE$path?lang=en" '<html lang="en"'
  check_body "$BASE$path?lang=nl" '<html lang="nl"'
done

if [ "$fail" -ne 0 ]; then
  echo "MYSTERYMARKET_FINAL_REVIEW_FAILED" >&2
  exit 1
fi

echo "MYSTERYMARKET_FINAL_REVIEW_OK"
