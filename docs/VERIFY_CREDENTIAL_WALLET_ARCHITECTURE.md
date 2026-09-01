# Verify Credential / Wallet Subsystem Architecture

Status: R1.2 active implementation  
Purpose: reusable project-credential issuance pattern  
Authoritative credential source: `audit_verifications`

## 1. Core principle

A Verify credential is the identity-bearing project record.

Everything else is an output or representation of that same credential:

```text
audit_verifications
        |
        +--> public Verify / QR
        +--> protected photo / logos / evidence
        +--> printable CR80 card
        +--> Apple Wallet .pkpass
        +--> physical card / holder / lanyard fulfilment
```

Do not introduce a second credential identity for Wallet, print, login role or fulfilment.

The Backoffice role (admin / Elite) controls access to management functions only. It is not the credential identity.

Private holder access is bound directly on the authoritative credential through `subject_user_id`. Admin may manage all credentials; an Elite user may open only credentials whose `subject_user_id` equals that user's Backoffice account ID. This binding controls private representations only and does not alter the public Verify identity.

## 2. Authoritative data model

### `audit_verifications`

This table remains authoritative for project credentials.

Important fields used by the reusable subsystem:

- `id`
- `reference_code`
- `person_name`
- `role_label`
- `agency_name`
- `project_name`
- `brand_name`
- `valid_from`
- `valid_until`
- `photo_asset`
- `brand_logo_asset`
- `agency_logo_asset`
- `scope_key`
- `document_asset`
- `document_label`
- `document_enabled`
- `print_card_enabled`
- `is_personal_verification`
- `subject_user_id` (private Backoffice holder/access binding; not a second credential identity)
- `is_active`
- `supersedes_verification_id`
- `revision_no`

A product reusing this subsystem may rename the table, but it must preserve the same semantic contract: one authoritative credential record with one stable verification reference.

### `verify_credential_outputs`

This table represents issuance / fulfilment requests for a credential.

It must always reference the authoritative credential by ID.

Current output types:

- `apple_wallet`
- `print_card`
- `physical_card`
- `transparent_holder`
- `mysterymarket_lanyard`
- `elite_shopper_lanyard`
- `full_set`
- `replacement_card`

The product-specific accessory names may be replaced in another system. The important reusable concept is that all outputs point back to the same credential.

Output status contract:

```text
requested
  -> approved
  -> processing
  -> ready
  -> shipped       (physical only)

requested/approved/processing/ready
  -> cancelled
```

## 3. Credential lifecycle

### Create

New credentials are created inactive.

A newly created record must not become publicly valid merely because mandatory text fields exist.

### Equip

The draft is equipped with:

- photo
- brand/client logo
- agency/partner logo
- project scope
- evidence/authorisation PDF
- document label
- validity window

Private assets are stored outside the public webroot.

### Integrity check

`mmCredentialIntegrityErrors()` is the shared activation contract.

Activation requires:

- personal Verify record
- person
- role
- agency
- project
- brand/client
- valid-from date
- valid-until date
- logical date range
- photo
- brand logo
- agency logo
- known scope
- evidence PDF
- evidence label
- evidence enabled
- print card enabled
- private files actually present
- MIME type valid

This function is deliberately reused by Backoffice activation, Wallet generation and review tooling.

Do not create separate weaker validation rules for new output channels.

### Activate

Activation changes `is_active` only after the integrity contract passes.

### Deactivate

Deactivation immediately removes the credential from productive Verify validity.

### Revise

Active credentials are not edited directly.

A revision:

1. gets a new Verify reference,
2. is created inactive,
3. copies the existing credential baseline,
4. references its predecessor through `supersedes_verification_id`,
5. increments `revision_no`.

When a revision is activated, activation of the new record and deactivation of the superseded record are performed atomically.

## 4. Verify is authoritative

Wallet, print and physical cards do not decide whether a credential is valid.

Authoritative checks remain in the Verify system.

For MysteryMarket:

```text
https://mysterymarket.de/verify?code=<REFERENCE>#credential
```

A reused implementation should replace only the product URL and branding, not the authority model.

## 5. Protected assets

Configuration key:

```php
mmConfig()['security']['verify_asset_dir']
```

MysteryMarket production location:

```text
/var/www/vhosts/mysterymarket.de/private/verify-assets
```

Rules:

- outside public webroot
- server-readable
- upload target must resolve inside configured directory
- bind only plain basenames
- image formats: PNG/JPEG/WebP
- evidence documents: PDF
- MIME validation is mandatory
- no SVG for credential uploads
- no direct public filesystem URL
- admin preview and public Verify delivery use controlled PHP endpoints

Relevant implementation:

- `scripts/verify-asset-bind.php`
- `backoffice/credential-asset.php`
- `verify-asset.php`
- `mmCredentialStoreUploadedAsset()`

## 6. Apple Wallet architecture

Apple Wallet is an output channel of the Verify credential.

```text
Verify credential
     |
     +--> Wallet output request
             |
             +--> readiness check
             +--> pass.json
             +--> artwork
             +--> manifest.json
             +--> CMS signature
             +--> .pkpass package
```

The pass uses:

- Apple Generic Pass
- Verify reference as serial number
- same project/person/agency data
- same validity
- QR code back to Verify
- `expirationDate` derived from credential expiry

The pass is generated only when:

- credential is active
- credential integrity is complete
- Apple signing configuration is ready
- output request is in a permitted processing state

## 7. Apple Wallet private configuration

Configuration lives only in private `config/local.php`.

Required keys:

```php
'apple_wallet' => [
    'enabled' => true,
    'pass_type_identifier' => 'pass.example.product',
    'team_identifier' => 'APPLE_TEAM_ID',
    'organization_name' => 'Product Name',
    'certificate_path' => '/private/path/pass-signing.p12',
    'certificate_password' => 'PRIVATE_PASSWORD',
    'wwdr_certificate_path' => '/private/path/AppleWWDRCA.cer',
    'icon_path' => '/private/path/icon.png',
    'icon_2x_path' => '/private/path/icon@2x.png',
    'logo_path' => '/private/path/logo.png',
],
```

Never commit:

- P12/PFX files
- private keys
- certificate passwords
- Apple signing secrets

## 8. Wallet generation

Reusable functions in `includes/credentials.php`:

- `mmAppleWalletReadiness()`
- `mmAppleWalletPassPayload()`
- `mmAppleWalletBuildPass()`

Authenticated download endpoint:

- `backoffice/credential-wallet.php`
- `backoffice/card-calibration.php`

Pre-signing Backoffice mapping preview:

- `backoffice/credential-wallet-preview.php`

Readiness CLI:

- `scripts/wallet-readiness.php`

Signed package structural review:

- `scripts/wallet-package-review.php`

The generator:

1. validates readiness,
2. validates active credential integrity,
3. creates a private temporary workspace,
4. writes `pass.json`,
5. copies configured artwork,
6. builds SHA-1 `manifest.json`,
7. reads the P12/PFX,
8. extracts certificate/private key,
9. creates detached CMS signature with WWDR chain,
10. packages the pass with `ZipArchive`,
11. returns a temporary `.pkpass`,
12. removes temporary build files.

## 9. Fulfilment architecture

Physical fulfilment keeps the credential representation printer-independent, while MysteryMarket's selected operational print target is now the Epson TS705a with a PVC card tray.

Current subsystem handles:

- request
- approval
- processing
- ready
- shipping
- tracking/reference
- cancellation

It deliberately does not encode:

- Zebra commands
- printer command language
- vendor SDK assumptions
- hard-coded driver internals

Approved MysteryMarket print direction:

- Epson TS705a is the future physical card printer.
- PVC cards are fed through the selected PVC card tray.
- Zebra is no longer a planned MysteryMarket print target.
- The credential remains a CR80 print-ready representation; printer-specific mechanics stay outside the credential identity/data model.
- Prepare exact-size print CSS and a calibration/test page before productive card printing.
- Do not hard-code tray X/Y offsets or scaling until the real printer, tray, driver and card stock have been physically tested.
- Browser/PDF output should default to 100% / actual-size semantics and must avoid automatic fit-to-page scaling where possible.

Any future printer adapter or print helper should consume the existing print-ready credential representation rather than creating a second card layout or credential identity.

## 10. Reuse boundary

When porting this subsystem to another product, preserve:

- one authoritative credential record
- stable verification reference
- inactive-draft-first lifecycle
- shared integrity validator
- protected assets outside webroot
- output records referencing credential ID
- Verify as authority
- Wallet as representation
- revision lineage
- audit trail
- explicit private subject binding for holder-facing outputs
- CSRF/authentication on management endpoints

Replace/configure:

- table names if necessary
- product branding
- Verify base URL
- scope registry
- output accessory labels
- Wallet organization/pass identifiers
- filesystem paths
- Backoffice auth adapter
- audit-log adapter

Do not copy:

- production secrets
- certificate files
- product-specific customer/project data

## 11. Current MysteryMarket file map

Core:

- `includes/credentials.php`
- `backoffice/credentials.php`
- `backoffice/credential.php`
- `backoffice/credential-new.php`
- `backoffice/credential-asset.php`
- `backoffice/credential-output.php`
- `backoffice/credential-wallet.php`

Public Verify:

- `verify.php`
- `verify-asset.php`
- `verify-card.php`

CLI / review:

- `scripts/verify-record.php`
- `scripts/verify-asset-bind.php`
- `scripts/credentials-review.php`
- `scripts/wallet-readiness.php`

Schema:

- `database/20260830_credentials_foundation.sql`
- `database/20260830_verify_credential_revision.sql`

Configuration template:

- `config/local.example.php`

## 12. Regression rules

A future change must not:

- create a generic parallel MysteryMarket credential
- let Wallet become a separate identity
- expose private credential assets directly
- bypass the shared integrity check
- activate incomplete credentials
- modify active credentials in-place
- leave an activated revision and its superseded predecessor both active
- generate unsigned/fake `.pkpass` files
- embed Apple private keys or certificate passwords in repository code
