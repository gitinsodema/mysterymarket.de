# MysteryMarket Development Rules

## Scope
mysterymarket.de is the public presence for MysteryMarket operational audit and field services.

Public positioning:
- operational audit and fieldwork partner
- works for agencies and direct clients
- does not present MysteryMarket as a competing full-service evaluation/reporting agency
- ShopperMatch, OPS and future INSODEMA products remain clearly separate products/infrastructure

## Runtime
- PHP 8.5
- MariaDB
- Production domain: mysterymarket.de
- Production document root: /var/www/vhosts/mysterymarket.de/httpdocs
- Plesk user: mysterymarket.de

## Deployment safety
Production deployment is performed separately from Git publication.

All website operations must remain inside:

`/var/www/vhosts/mysterymarket.de/httpdocs`

Never recursively lint, chown, chmod or modify the parent vhost path `/var/www/vhosts/mysterymarket.de`; it contains unrelated subdomains/applications.

Prefer tracked-file linting with `git ls-files '*.php'`.

## Security
- Never commit production database credentials or private notification recipients.
- Never expose confidential client or audit-program information.
- Verification references must reveal only explicitly public information.
- Public customer/partner names require an approved public context.
- Partner/company logos require explicit written approval before publication.
- Files under `internal/` must remain inaccessible from the public web.


## DOS coordination
- Follow `gitinsodema/DOS/docs/DOS_PRODUCT_CHAT_HANDOFF_OWNER_APPROVAL_AND_PROGRESS_PROTOCOL_V1.md`.
- Read `AI_START_HERE.md`, `docs/coordination/README.md` and `docs/coordination/PRODUCT_HANDOFF.json` before meaningful product work.
- Update `docs/coordination/PRODUCT_HANDOFF.json` whenever product version, release state, blocker, Owner action, deployment/migration state, architecture, public state, major capability or approval state materially changes.
- Keep current state and historical context explicitly separated.
- Preserve the Verify system as a reusable capability unless the Owner explicitly replaces or cancels it.
