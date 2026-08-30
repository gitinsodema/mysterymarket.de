# MysteryMarket Website V1

Version: 1.0.0  
Release candidate date: 2026-08-28

## Scope

MysteryMarket V1 provides the public operational website for independent audit and field services.

Included:
- DE / EN / NL public website
- Services and current audit contexts
- About and contact areas
- OPS product presentation
- Elite Shopper Partner area
- Legal notice, privacy and cookie settings
- Contact storage, CSRF protection, honeypot and rate limiting
- Audit / authorisation Verify flow
- DE / EN / NL / TR / AR Verify languages
- QR scanning in the browser
- Personal auditor credentials
- Protected private Verify assets
- Official authorisation-document delivery
- Printable CR80 auditor ID with locally generated QR code
- Security headers, secure sessions, no-index/no-cache controls for personal credentials
- Accessibility and responsive/mobile refinements

## Production

Document root:

```text
/var/www/vhosts/mysterymarket.de/httpdocs
```

Private Verify assets:

```text
/var/www/vhosts/mysterymarket.de/private/verify-assets
```

Production runtime:
- PHP 8.5
- MariaDB
- HTTPS / Plesk

## Release gates

Before merging V1 to `main`:
1. All tracked PHP files lint without errors.
2. `scripts/preflight.php` passes.
3. All production migrations are applied.
4. Home, Services, Current Audits, Verify, OPS, Elite Shopper, About, Contact, Legal Notice and Privacy return HTTP 200.
5. Verify QR scanner works on a real mobile device.
6. Personal Verify credential displays photo, project context and protected evidence correctly.
7. CR80 print view fits without clipped content.
8. Contact form stores exactly one request after a successful submission and browser refresh.
9. Security headers contain first-party camera permission only.
10. Owner explicitly approves merge/release.

## Known follow-up

Email confirmation delivery depends on the production mail transport. A successful PHP `mail()` handoff does not by itself prove final inbox delivery. This is a mail-deliverability follow-up and is not part of the core website rendering/Verify release gate.


## Production artifact boundary

Repository documentation and coordination files belong to the source/control-plane checkout,
not to the public production web artifact.

The production web artifact must exclude at least:

```text
docs/
AGENTS.md
AI_START_HERE.md
README.md
VERSION
.gitignore
.gitattributes
```

`.htaccess` deny rules remain a defence-in-depth fallback for the current release-candidate
checkout, but are not a substitute for excluding these files from the production web sync.

The repository marks these paths with `export-ignore` so an archive-based production artifact
can omit them by construction.
