# MysteryMarket Post‑V1 Backoffice Roadmap

Status: Owner-approved direction
Baseline: MysteryMarket R1.2.0 released on `main`
Active development: production feature development paused after R1.2; future concepts documented only until prerequisites are ready

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

Implementation status: started on `r1.2-credentials`.

Corrected implementation direction:
- `audit_verifications` remains the authoritative credential source
- existing project credentials such as Vodafone / SKOPOS NEXT and HP / BARE are the reference records
- R1.2 adds an output/fulfilment service around those Verify credentials
- no separate generic MysteryMarket identity credential is created
- authenticated Admin `/backoffice/credentials.php` lists existing personal Verify credentials
- each Verify credential can be opened in Verify, opened as the existing print card, and requested for Apple Wallet or physical fulfilment
- output requests are stored in `verify_credential_outputs` and reference `audit_verifications.id`
- "Ausweis bestellen" therefore means producing another representation of an existing Verify credential
- no real `.pkpass` is generated until Pass Type ID/signing certificate are configured server-side
- MysteryMarket Verify remains the authoritative validation source


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

Target: real iPhone Apple Wallet pass (`.pkpass`) for an existing project-specific Verify credential.

Implemented pass basis:
- Apple Generic Pass
- project/brand context
- person and agency
- validity
- Verify reference
- QR code to the authoritative Verify URL
- expiration date from the Verify credential
- same Verify identity; no separate Wallet credential

Technical requirements/status:
- Apple Developer account / Pass Type ID: external prerequisite
- Pass Type signing certificate/private key: external prerequisite
- Apple WWDR certificate: external prerequisite
- signed pass generation: implemented server-side, gated by readiness
- private artwork paths for icon/logo: configured outside repository
- authenticated .pkpass download endpoint: implemented
- optional PassKit web service for updates: later
- device registration / push update support: later

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

### Physical card print path

Current R1.2 scope remains:
- printer-independent CR80/card output
- browser/PDF print-ready representation
- admin fulfilment queue
- printed / packed / shipped states
- shipping date
- optional tracking
- replacement workflow

Owner-selected future production target:
- Epson TS705a
- PVC card tray
- Zebra is not planned for MysteryMarket

Preparation before productive physical printing:
- preserve one canonical CR80 card layout
- add exact-size print CSS
- add a printer/card calibration sheet
- validate front/back orientation, scaling, margins and tray offset on the actual Epson/tray/card-stock combination
- record the tested driver/print settings after physical validation

Do not invent or hard-code Epson tray offsets before the hardware is available. No vendor-specific print protocol is required at this stage.

## Post-R1.2 — Future concepts (not an active release)

MysteryMarket R1.2.0 is the final productive baseline for now.

The earlier R1.3 "Connected Elite" concept is intentionally parked because its prerequisites and data ownership boundaries are not yet ready.

### Qualification / certificate ownership

Do not maintain qualifications in both MysteryMarket and ShopperMatch.

Approved direction:

- ShopperMatch owns detailed shopper profiling and certificates.
- Certificates are available for all relevant shoppers, not only MysteryMarket Elite members.
- MysteryMarket may later consume successful/valid certificates from ShopperMatch through an API.
- MysteryMarket should display only the operational certificate facts it needs and must not recreate the detailed qualification model locally.

### ShopperMatch profile link

Preferred future integration:
- retrieve/link the ShopperMatch profile through an API or stable transfer/reference code.

Permitted fallback:
- manually store a ShopperMatch profile URL or code in the Elite profile.

MysteryMarket Elite remains deliberately small and does not become a second full shopper-profile system or job marketplace.

### ATLAS personal geography profile

Do not repeatedly ask the same person for region/geography data in every product.

Target concept:
- the user creates a personal geography/region profile once through ATLAS;
- ATLAS remains the canonical geography reference;
- another INSODEMA product can import that profile through a transfer code/API;
- each receiving product stores only the product-specific normalized values it actually requires.

The exact transfer contract remains future work and must be defined in ATLAS before implementation.

### OPS connection

MysteryMarket-to-OPS integration is deferred until OPS 1.0 is a stable prerequisite.

Current OPS work is still focused on Dispatcher completion/hardening, therefore no speculative MysteryMarket OPS integration is to be implemented now.

### Native MysteryMarket Backoffice app experiment

The next active experiment may be a small native iOS application for the authenticated MysteryMarket Backoffice only.

Initial scope should remain deliberately narrow:
- Backoffice login
- Face ID / biometric unlock after the first successful server login
- secure session/token storage in the iOS Keychain; never store the Backoffice password
- Elite member/admin views needed on mobile
- own credential/card view
- relevant private Backoffice actions
- no duplication of the public website
- no public Verify replacement
- no new qualification/profile data ownership
- reuse the existing MysteryMarket Backoffice/API authority where possible

Security principle for the app:
- first authentication remains server-authoritative;
- biometric login is an optional local unlock of a securely stored app session/refresh credential;
- Face ID must never create a parallel identity or bypass server-side account status/role checks;
- logout/revocation removes the local app session.

This app is an experiment after the R1.2 web release, not R1.3 product scope.

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

Project credentials are represented by personal `audit_verifications` records, not by a generic login-role credential.

Reason:
- the real credential is project-specific (for example Vodafone / SKOPOS NEXT or HP / BARE)
- Verify / QR / Apple Wallet / printed cards must all represent that same project credential
- Admin/Elite is only a Backoffice access role and must not create a second generic identity card
- one person may hold multiple project credentials at the same time


## R1.2 Credential ordering and Apple Wallet

Owner-confirmed direction:
- "Ausweis bestellen" is the single credential issuance/order area.
- Apple Wallet is a first-class issuance channel in the same area as physical card options.
- The UI should offer an action such as "Zu Apple Wallet hinzufügen" alongside physical ordering choices.
- Physical and digital outputs represent the same underlying credential/subject identity.
- A credential has an authoritative lifecycle including issue date, expiry date, status, replacement/revocation and verification state.
- Apple Wallet passes should use the pass-level `expirationDate` where appropriate.
- Expiry handling in Wallet does not replace MysteryMarket Verify or server-side revocation/suspension logic.
- Later Wallet update-service support should allow changed/revoked credentials to be reflected without issuing an unrelated new identity.

Planned order/issuance choices:
- Digital credential / Apple Wallet
- physical card
- transparent holder
- MysteryMarket lanyard
- Elite Shopper lanyard
- full set
- replacement card

The order UI is not a merchandise shop; it is credential/equipment issuance for field operations.


## ATLAS Geography Reference API v1 — integration status

Authoritative contract:
`docs/api/ATLAS_GEOGRAPHY_REFERENCE_API_V1.md`

The approved contract is now present on the active R1.1 branch.

Prepared MysteryMarket integration:
- server-side configuration under `config/local.php -> atlas`
- dedicated MYSTERYMARKET Bearer credential; never reuse SHOPPERMATCH credentials
- server-side client in `includes/atlas.php`
- authenticated Backoffice reference proxy in `backoffice/atlas-reference.php`
- CLI smoke test in `scripts/atlas-smoke.php`
- canonical Elite profile storage for administrative unit, postal area, locality and street ATLAS IDs
- no cross-database foreign keys
- token never exposed to browser JavaScript
- ATLAS request/correlation identifiers logged server-side when returned
- profile UI keeps controlled free-text fallback until the final response-item payloads have been verified live

Activation prerequisite:
- owner installs the dedicated MYSTERYMARKET token in production `config/local.php`
- run `scripts/atlas-smoke.php`
- only after successful smoke verification should live autocomplete/select UX be enabled
