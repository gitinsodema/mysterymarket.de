# MysteryMarket V1 Security Review

Review date: 2026-08-30
Branch: website-v1
Classification: CURRENT_AUTHORITY
Instruction classification: OWNER_DIRECTIVE

## Scope

Release-candidate review of the public web surface, sessions, Verify, protected assets,
contact processing, repository/webroot boundaries and deployment safety.

## Findings addressed

### MM-SEC-001 — Internal coordination files reachable from the public webroot

Severity: High
Status: fixed in repository

The repository intentionally contains DOS coordination and release documentation inside
the production checkout. Apache previously denied application-internal directories but
did not deny `docs/`, `AI_START_HERE.md`, `VERSION` or generic backup/archive files.

Risk included disclosure of internal product state, deployment paths and Verify references.

Remediation:
- deny `docs/`
- deny development dot-directories such as `.git/`
- deny start/readme/version files
- deny SQL, Markdown, JSON, logs and common backup/archive extensions

Production verification required after deploy:
- `/docs/coordination/PRODUCT_HANDOFF.json` => 403
- `/AI_START_HERE.md` => 403
- `/.git/config` => 403/404

### MM-SEC-002 — Browser policy lacked a Content Security Policy

Severity: Medium
Status: fixed in repository

Added a restrictive same-origin CSP baseline plus:
- base-uri self
- form-action self
- frame-ancestors self
- object-src none
- same-origin scripts/resources
- camera-compatible worker/media policy
- upgrade-insecure-requests

Inline script/style is still temporarily allowed where required by current V1 templates.
Removing `unsafe-inline` is a post-V1 hardening improvement and requires refactoring the
remaining inline card script/style and small inline presentation attributes.

### MM-SEC-003 — Session transport could be tightened

Severity: Low
Status: fixed in repository

Secure session startup now explicitly disables trans-SID and uses a dedicated
`MMSESSID` cookie name in addition to strict mode, HTTPS-only, HttpOnly and SameSite=Lax.

### MM-SEC-004 — Verify privilege entries accumulated for the session lifetime

Severity: Low
Status: fixed in repository

Expired Verify grants are pruned at request start. A successful verification regenerates
the session ID before protected assets are subsequently requested.

### MM-SEC-005 — Dynamic endpoints accepted unnecessary HTTP methods

Severity: Low
Status: fixed in repository

Allowed methods are now explicit:
- Contact: GET, POST
- Verify: GET, POST
- Verify assets: GET, HEAD
- Verify card: GET, HEAD

Protected asset delivery handles HEAD without streaming the asset body.

## Existing controls confirmed in code

- prepared PDO statements with emulated prepares disabled
- private Verify assets configured outside the public webroot
- realpath containment and basename checks for protected assets
- MIME detection and file-size limits
- active/date-valid credential checks before asset delivery
- 15-minute session-bound asset authorisation
- no-store/noindex handling for personal credentials
- persistent pseudonymous IP throttling plus session fallback
- CSRF token and honeypot on contact form
- Post/Redirect/Get after successful contact submission
- QR scanner processes camera frames locally in the browser
- server-side allow-list for Verify reference format
- first-party-only camera Permissions-Policy

## Open hardening items

1. Production HTTP verification of the new deny rules and CSP.
2. Complete and test HP/BARE credential on a real iPhone.
3. Resolve or archive duplicate inactive HP/BARE draft.
4. Mail deliverability remains an operational follow-up.
5. Post-V1: remove CSP `unsafe-inline` after extracting remaining inline script/style.
6. Final legal/privacy/contact visual QA before Owner release approval.

## Release position

No merge to `main` is authorised by this review. The release remains subject to the
Owner's explicit approval after production verification.
