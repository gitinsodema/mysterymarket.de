# MysteryMarket AI Start Here

Read in this order before product work:

1. `AGENTS.md`
2. `docs/RELEASE_V1.md`
3. `docs/BACKOFFICE_ROADMAP.md`
4. `docs/coordination/README.md`
5. `docs/coordination/PRODUCT_HANDOFF.json`
6. the current branch/code relevant to the requested task

Mandatory cross-product coordination protocol:

`gitinsodema/DOS/docs/DOS_PRODUCT_CHAT_HANDOFF_OWNER_APPROVAL_AND_PROGRESS_PROTOCOL_V1.md`

Current repository:
- `gitinsodema/mysterymarket.de`
- released baseline: `main` / V1.0.0
- active post-release development branch: `r1.1-little-backoffice`

Production safety:
- operate inside `/var/www/vhosts/mysterymarket.de/httpdocs`
- never recursively modify `/var/www/vhosts/mysterymarket.de`
- private Verify assets are outside webroot at `/var/www/vhosts/mysterymarket.de/private/verify-assets`

Important product continuity:
The Verify system is a preserved reusable capability. Future refactoring must retain its
core functions unless the Owner explicitly replaces or cancels them.


Backoffice continuity:
MysteryMarket owns Elite membership, identity, credentials, private member information and lightweight administration. ShopperMatch remains a separate job/matching product and must not be duplicated inside MysteryMarket.
