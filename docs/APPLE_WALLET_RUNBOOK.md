# Apple Wallet Provisioning and Operations Runbook

Status: prepared; signing material not yet provisioned  
Scope: MysteryMarket R1.2 project-specific Verify credentials

## 1. Expected pre-provisioning state

Before Apple credentials are installed:

```text
MYSTERYMARKET_APPLE_WALLET_NOT_READY
```

This is expected and must not fail the general credential-service review.

Run:

```bash
PHP=/opt/plesk/php/8.5/bin/php
"$PHP" scripts/wallet-readiness.php
```

## 2. Private server directory

Recommended production structure:

```text
/var/www/vhosts/mysterymarket.de/private/
├── verify-assets/
└── wallet/
    ├── pass-signing.p12
    ├── AppleWWDRCA.cer
    ├── icon.png
    ├── icon@2x.png
    └── logo.png
```

The Wallet directory must never be placed under `httpdocs`.

Create only the directory structure:

```bash
mkdir -p /var/www/vhosts/mysterymarket.de/private/wallet
chown mysterymarket.de:psacln /var/www/vhosts/mysterymarket.de/private/wallet
chmod 750 /var/www/vhosts/mysterymarket.de/private/wallet
```

Do not recursively change permissions on `/var/www/vhosts/mysterymarket.de`.

Certificate and artwork files should normally be:

```text
owner: mysterymarket.de
group: psacln
mode: 640
```

Adjust only if the actual Plesk PHP-FPM user requires a different safe ownership model.

## 3. Apple-side prerequisites

Required external material:

1. Apple Developer account
2. Pass Type Identifier for the product
3. Pass Type certificate/private key exported to P12/PFX
4. current Apple WWDR intermediate certificate
5. Apple Team Identifier

Do not paste the private key or certificate password into chat, tickets or repository documentation.

## 4. Artwork

Required by the current generator:

- `icon.png`

Optional but recommended:

- `icon@2x.png`
- `logo.png`

Current generator validates PNG MIME type.

Artwork is configured by private filesystem path so product-specific branding can be changed without changing credential logic.

## 5. Production configuration

Edit:

```text
/var/www/vhosts/mysterymarket.de/httpdocs/config/local.php
```

Add/merge:

```php
'apple_wallet' => [
    'enabled' => true,
    'pass_type_identifier' => 'pass.REAL_IDENTIFIER',
    'team_identifier' => 'REAL_TEAM_ID',
    'organization_name' => 'MysteryMarket',
    'certificate_path' => '/var/www/vhosts/mysterymarket.de/private/wallet/pass-signing.p12',
    'certificate_password' => 'PRIVATE_PASSWORD',
    'wwdr_certificate_path' => '/var/www/vhosts/mysterymarket.de/private/wallet/AppleWWDRCA.cer',
    'icon_path' => '/var/www/vhosts/mysterymarket.de/private/wallet/icon.png',
    'icon_2x_path' => '/var/www/vhosts/mysterymarket.de/private/wallet/icon@2x.png',
    'logo_path' => '/var/www/vhosts/mysterymarket.de/private/wallet/logo.png',
],
```

The real values remain local-only.

## 6. Readiness verification

Run:

```bash
cd /var/www/vhosts/mysterymarket.de/httpdocs
PHP=/opt/plesk/php/8.5/bin/php

"$PHP" scripts/wallet-readiness.php
"$PHP" scripts/credentials-review.php
```

Expected Wallet marker once provisioned:

```text
MYSTERYMARKET_APPLE_WALLET_READY
```

The credential review should continue to end with:

```text
MYSTERYMARKET_VERIFY_CREDENTIAL_SERVICE_OK
```

## 7. First signed pass test

Use an existing active, integrity-valid personal Verify credential.

Backoffice path:

```text
/backoffice/credentials.php
```

Procedure:

1. open the desired Verify credential
2. request `Apple Wallet`
3. open the output request
4. move `requested -> approved`
5. move `approved -> processing`
6. once readiness is green, use `Wallet-Pass erzeugen`
7. open the downloaded `.pkpass` on iPhone
8. add to Apple Wallet
9. inspect name/project/agency/validity/reference
10. scan/tap QR and verify it reaches the same MysteryMarket Verify record

Do not use an inactive draft for the first test.

## 8. Expected pass content

Current Generic Pass maps:

Front:

- project
- person
- agency
- valid-until date
- Verify reference

Back:

- role
- brand/client
- project
- agency
- validity
- Verify URL

Barcode:

- QR
- message = authoritative Verify URL
- alt text = Verify reference

Pass-level lifecycle:

- `expirationDate` = end of `valid_until`
- `relevantDate` = start of `valid_from`

## 9. Failure handling

### NOT_READY

Use:

```bash
"$PHP" scripts/wallet-readiness.php
```

Resolve only the listed prerequisite. Do not bypass the readiness gate.

### Certificate cannot be unlocked

Check:

- P12/PFX file readable by PHP
- password in private config is correct
- P12 actually contains certificate + private key

Do not log or echo the password.

### Signature fails

Check:

- Pass Type certificate matches configured Pass Type Identifier
- WWDR path is correct and readable
- OpenSSL CMS support exists
- certificate is valid/not expired

### iPhone rejects pass

Do not weaken signing checks.

Inspect generated pass package and validate:

- `pass.json`
- `manifest.json`
- `signature`
- `icon.png`
- Pass Type Identifier
- Team Identifier
- certificate chain

## 10. Security requirements

- Wallet endpoint requires authenticated admin session
- output request must be Apple Wallet
- output status must permit generation
- Verify credential must be active
- shared credential integrity must pass
- signing material is private
- pass build workspace is temporary
- temporary build files are removed
- resulting pass contains verification data, not private certificate material
- no fake/unsigned fallback is permitted

## 11. Reuse in another product

Recommended porting sequence:

1. copy/adapt the authoritative credential contract
2. port shared integrity validator
3. port protected asset service
4. port output-request model
5. port Wallet readiness helper
6. replace product Verify URL in pass payload
7. replace product branding/artwork paths
8. provision a product-specific Pass Type Identifier and certificate
9. keep signing material outside webroot/repository
10. test one active credential end-to-end on real iPhone

Each product should have its own Apple Pass Type identity/signing material unless an explicit shared-platform design is approved later.

## 12. Later enhancements

Not required for first R1.2 pass:

- PassKit web service
- device registration
- APNs pass updates
- automatic revocation/status push into installed Wallet passes
- richer per-project artwork
- self-service Wallet download for credential holders

Verify remains authoritative even if those update capabilities are added later.


## 13. Pre-signing technical preview

Before Apple signing material exists, the Backoffice can already display the exact
credential-to-pass mapping without generating an unsigned pass.

Route:

```text
/backoffice/credential-wallet-preview.php?output_id=<OUTPUT_ID>
```

The preview shows:

- Generic Pass layout mapping
- project
- person
- agency
- valid-until date
- Verify reference
- QR/Verify URL
- serial number
- configured Pass Type Identifier / Team Identifier
- relevant/expiration dates
- current Wallet readiness blockers

This is deliberately not a fake Wallet pass and does not create a `.pkpass`.

## 14. Signed package structural review

Once a real signed pass exists, review its package structure before device testing:

```bash
PHP=/opt/plesk/php/8.5/bin/php
"$PHP" scripts/wallet-package-review.php /path/to/MM-REFERENCE.pkpass
```

Expected marker:

```text
MYSTERYMARKET_APPLE_WALLET_PACKAGE_OK
```

The review checks:

- required package members
- valid `pass.json`
- required pass contract keys
- manifest JSON
- manifest SHA-1 digests against packaged files
- detached signature payload presence
- serial number / Pass Type / expiration metadata reporting

This is a structural package check. Final Apple signature acceptance is still proven by
real iPhone Wallet import; do not treat this tool as a replacement for Apple validation.


## 15. Apple Developer account / team setup

Owner direction:

- Apple setup is a long-term INSODEMA foundation, not a Wallet-only account.
- The same Apple Developer organization/team should be suitable for future App Store application publishing.
- INSODEMA domain identity is preferred for organizational consistency.
- The next setup step is to decide whether the Account Holder uses:
  1. a new dedicated Apple Account under the INSODEMA domain, or
  2. the Owner's existing active Apple Account.

Do not create a Pass Type ID or certificate before this account/team structure is decided.

The account/team decision must consider:

- long-term ownership continuity
- Account Holder recovery
- two-factor authentication
- separation of personal device usage from organizational administration
- future invitation of Admin/Developer/App Manager users
- future App Store Connect usage
- future PassKit/Wallet certificate ownership

The email address alone does not define the Apple Developer organization. The important boundary is the Apple Account that becomes Account Holder and the Apple Developer organization/team enrolled around it.

Recommended next-chat sequence:

1. decide Account Holder identity
2. create/verify Apple Account with two-factor authentication if needed
3. enroll the INSODEMA organization in the Apple Developer Program
4. verify organization/legal-entity details requested by Apple
5. record Team ID
6. create product Pass Type ID
7. generate CSR
8. create Pass Type certificate
9. export certificate + private key to P12/PFX
10. install WWDR certificate
11. place all signing material only in private server storage
12. configure local.php
13. run wallet-readiness.php
14. generate and test first signed pass on iPhone

Never store the Apple Account password, recovery keys, private keys or P12/PFX password in this repository or in product handoff files.
