# MysteryMarket R1.2 Credentials / Verify / Wallet — Technical Release Review

Status: technical review in progress; Owner release approval pending

Branch: `r1.2-credentials`

Release candidate version: `1.2.0`

## Scope completed

### Verify credential service

- `audit_verifications` remains the authoritative credential source
- existing HP/BARE and Vodafone/SKOPOS credentials remain the reference model
- new project-specific personal Verify credentials can be created in Backoffice
- new credentials start inactive as drafts
- person, role, agency, project, brand/client, validity and confidentiality are editable on drafts
- active credentials are not edited in place

### Protected credential assets

- photo upload
- brand/client logo upload
- agency logo upload
- protected PDF/evidence upload
- MIME validation
- private storage outside webroot
- authenticated Admin preview
- controlled unbind
- no second public asset path

### Credential integrity and activation

Activation requires:

- person
- role
- agency
- project
- brand/client
- validity start/end
- valid date range
- photo
- brand logo
- agency logo
- known scope
- evidence PDF
- evidence label
- document enabled
- print card enabled
- private files present/readable
- MIME types valid

The same shared integrity contract is used by:

- Backoffice activation
- active-credential review
- Apple Wallet generation

### Revision lifecycle

- active credentials remain read-only
- active credential can create a new inactive revision draft
- revision receives a new Verify reference
- `supersedes_verification_id` records predecessor
- `revision_no` records revision sequence
- activating a revision atomically activates the revision and deactivates its superseded credential
- review blocks a state where revision and predecessor are both active
- controlled deactivation is available for active credentials

### Credential outputs / fulfilment

`verify_credential_outputs` references `audit_verifications.id`.

Output channels include:

- Apple Wallet
- physical card
- transparent holder
- MysteryMarket lanyard
- Elite Shopper lanyard
- full set
- replacement card

Controlled fulfilment flow:

```text
requested
-> approved
-> processing
-> ready
-> shipped    (physical only)
```

Cancellation is available from open states.

Physical shipping requires a shipping reference.

Printer-specific device integration is explicitly deferred until actual hardware is selected.

### Apple Wallet

Implemented:

- Apple Generic Pass payload
- project/person/agency mapping
- Verify reference as serial number
- QR to authoritative Verify URL
- validity mapping
- `expirationDate`
- `relevantDate`
- private artwork paths
- readiness checks
- signed package generation
- manifest creation
- CMS signing
- WWDR chain input
- authenticated `.pkpass` download
- technical pass preview without fake/unsigned package
- signed-package structural review CLI

Not yet externally provisioned:

- Apple Pass Type Identifier
- Apple Team Identifier
- Pass Type signing certificate/private key
- Apple WWDR certificate
- production Wallet artwork

These are external prerequisites and are not treated as code defects.

No unsigned/fake `.pkpass` fallback exists.

### Reusable subsystem documentation

- `docs/VERIFY_CREDENTIAL_WALLET_ARCHITECTURE.md`
- `docs/APPLE_WALLET_RUNBOOK.md`

Future product chats are directed to these files through:

- `AI_START_HERE.md`
- `docs/coordination/README.md`

Preflight requires both documents.

## Owner/runtime evidence already reported

Owner confirmed:

- inactive Verify credential draft creation works
- protected asset binding works
- guarded activation behaves as expected
- revision creation produces a separate inactive draft while predecessor remains active
- Wallet technical preview works
- credential review currently reports:
  - 4 personal Verify credentials
  - 2 active personal Verify credentials
  - all active credentials pass activation integrity
  - no active revision leaves its superseded credential active
  - shipped outputs have shipping references
  - Wallet is NOT_READY only because external Apple configuration is absent
  - `MYSTERYMARKET_VERIFY_CREDENTIAL_SERVICE_OK`

## Explicitly deferred / non-blocking

### Printer hardware adapter

No Zebra-, Canon-, tray-, driver- or vendor-specific integration is part of R1.2.

The printer adapter will only be designed when the actual production printer is selected.

### Apple signing provisioning

Real Wallet installation cannot be completed until Apple Developer signing material is installed in private production storage.

The code must remain safe in `NOT_READY` state until then.

## Security invariants

R1.2 must preserve:

- authenticated Backoffice access
- CSRF protection
- active credentials read-only
- activation integrity gate
- protected assets outside webroot
- no private signing material in repository
- no direct public access to credential files
- audit logging of credential/output lifecycle changes
- no parallel generic credential identity
- Verify remains authoritative
- no fake/unsigned Wallet pass

## R1.2 technical close criteria

Before Owner release approval:

1. tracked PHP lint passes
2. `scripts/preflight.php` passes
3. `scripts/backoffice-review.php` returns `MYSTERYMARKET_BACKOFFICE_REVIEW_OK`
4. `scripts/credentials-review.php` returns `MYSTERYMARKET_VERIFY_CREDENTIAL_SERVICE_OK`
5. `scripts/atlas-smoke.php` returns `MYSTERYMARKET_ATLAS_SMOKE_OK`
6. `scripts/wallet-readiness.php` executes successfully; `NOT_READY` is acceptable until Apple signing material exists
7. `scripts/r1.2-technical-gate.php` returns `MYSTERYMARKET_R1_2_TECHNICAL_GATE_OK`
8. no secrets/signing material are committed
9. Owner performs final runtime review
10. Owner explicitly approves R1.2 release

## Release approval

Owner approval has not yet been requested/granted for R1.2.

Do not merge/release R1.2 solely because the technical gate passes.
