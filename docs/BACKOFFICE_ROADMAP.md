# MysteryMarket Post‑V1 Backoffice Roadmap

Status: Owner-approved direction
Baseline: MysteryMarket V1.0.0 on `main`
Active development branch: `r1.1-little-backoffice`

## Product boundary

MysteryMarket Backoffice owns:
- Elite Shopper membership and identity
- Elite Shopper private member area
- internal member information / project hints
- credential / badge / card lifecycle
- Apple Wallet pass lifecycle
- physical card ordering and fulfilment
- admin-only operational notes and approval tracking
- read-only visibility of stored website contact requests
- internal communication workflow with agencies, e.g. logo / usage approvals

ShopperMatch owns:
- shopper registration for job/matching activity
- job/match discovery and matching workflows
- job-specific participation workflows

Important:
MysteryMarket Elite may announce or discuss project opportunities internally. These posts are not a job marketplace and do not imply that execution or registration takes place inside MysteryMarket. Elite members should additionally maintain a ShopperMatch profile, but the systems remain intentionally separate.

## R1.1 — Little Backoffice

Goal: secure, small operational admin/member foundation.

### Authentication and access
- admin login
- Elite Shopper login
- role separation: admin / elite
- secure sessions
- password hashing
- login throttling
- account state checks
- explicit logout
- audit log for security-relevant actions

### Elite member lifecycle
Suggested states:
- invited
- pending_review
- active
- paused
- suspended
- ended

Admin:
- list members
- open member detail
- activate / pause / suspend
- internal notes
- qualification overview
- membership history

Elite member:
- own profile
- membership status
- delivery address
- preferred regions
- mobility profile
- own credential status
- link to ShopperMatch
- membership pause/end request

### Private Elite feed
Admin creates posts with:
- title
- category
- body
- optional agency
- optional project/context
- optional region
- optional external link
- publish from / until
- target audience
- pinned flag

Suggested categories:
- Project hint
- Agency
- Training
- OPS
- Important
- General

Initial interaction:
- read only for members
- optional "read" acknowledgement later
- no forum
- no public comments

### Small admin area
Navigation:
- Dashboard
- Elite Shopper
- Credentials
- Communication
- Contacts
- Settings

Dashboard:
- active members
- expiring credentials
- pending card orders
- new contact requests
- recent internal posts
- open agency approvals

### Contact requests
Initial scope:
- read-only list and detail
- status: new / seen / done
- no reply functionality

### Agency approval tracking
Track items such as logo/brand usage approval:
- agency
- contact person
- purpose
- request status
- sent date
- response date
- approved / rejected
- evidence/reference
- internal note

No full email client in R1.1.

## R1.2 — Credentials

### Credential lifecycle
Suggested states:
- draft
- approved
- active
- print_ordered
- printed
- shipped
- expired
- revoked
- replaced

Admin capabilities:
- issue credential
- renew
- revoke
- replace
- inspect history
- QR verification
- print queue
- shipping state

Member capabilities:
- view own credential
- QR badge
- printable badge
- order physical card
- Apple Wallet

### Apple Wallet

Target: real iPhone Apple Wallet pass (`.pkpass`) for an Elite Shopper credential.

Planned fields:
- MysteryMarket / Elite Shopper branding
- member display name
- Elite Shopper ID
- validity
- status
- QR / verification reference
- direct Verify URL

Technical requirements:
- Apple Developer account / Pass Type ID
- signing certificate
- signed pass generation
- secure pass download endpoint
- optional PassKit web service for updates
- device registration / push update support later

The Wallet pass is a convenience representation. MysteryMarket Verify remains the authoritative validation source.

### Physical card products
- card only
- card + transparent protective holder
- card + MysteryMarket lanyard
- card + Elite Shopper lanyard
- complete set
- replacement card

Potential later accessories:
- horizontal holder
- clothing clip
- spare holder

Principle: professional field equipment, not merchandise.

### Zebra fulfilment
Long-term:
- print-ready output
- admin print queue
- printed / packed / shipped
- shipping date
- optional tracking
- replacement workflow

## R1.3 — Connected Elite

- OPS connection
- qualifications
- regions
- mobility profile
- planning support
- richer member status / readiness
- ShopperMatch profile link / presence marker
- no duplicate job marketplace

## Security principles

- all backoffice/member pages authenticated
- least-privilege role checks
- CSRF protection for all changes
- strict session cookies
- login throttling
- no secrets in repository
- sensitive member data excluded from public responses
- security-relevant actions logged
- credentials revocable immediately
- private files outside webroot where appropriate

## Release principle

V1.0.0 on `main` remains the public baseline.

Post‑V1 work happens only on dedicated branches and is released incrementally after:
- migrations
- lint
- preflight
- security review
- owner test
- explicit owner approval


## Shared location/address integration

Approved direction:
- do not build a second global postal/city database inside MysteryMarket
- prefer the existing ATLAS location dataset/API as the central source
- MysteryMarket stores normalized address values needed by the product
- UI should use search/autocomplete for postal code and city, not giant global dropdowns

Minimal normalized address contract:
- `country_iso`
- `postal_code`
- `city`
- `region` / state / Bundesland
- optional stable ATLAS location identifier

Desired ATLAS API capabilities:
- country list
- postal-code / city search
- region/state resolution
- normalized ISO country code
- ideally stable IDs for returned locations

This abstraction should later be reusable in:
- Elite profile
- Backoffice member editing
- registration/login-adjacent onboarding forms
- contact flows where structured location is useful

Implementation waits for the actual ATLAS API contract; no speculative endpoint names or payloads are hard-coded.

## Credential ownership model

Credentials must belong to a person/identity, not to a login role.

Reason:
- an admin may also hold personal MysteryMarket credentials
- Elite members hold credentials
- Verify / QR / Apple Wallet / printed cards should all refer to the same subject identity
- admin vs Elite is an access role, not credential ownership

R1.2 will therefore introduce a credential subject/identity relationship that can be linked to a backoffice account independently of whether that account is `admin` or `elite`.
