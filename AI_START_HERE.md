# MysteryMarket AI Start Here

Read in this order before product work:

1. `AGENTS.md`
2. `docs/RELEASE_V1.md`
3. `docs/coordination/README.md`
4. `docs/coordination/PRODUCT_HANDOFF.json`
5. the current branch/code relevant to the requested task

Mandatory cross-product coordination protocol:

`gitinsodema/DOS/docs/DOS_PRODUCT_CHAT_HANDOFF_OWNER_APPROVAL_AND_PROGRESS_PROTOCOL_V1.md`

Current repository:
- `gitinsodema/mysterymarket.de`
- active release branch: `website-v1`
- V1 release candidate: `1.0.0`

Production safety:
- operate inside `/var/www/vhosts/mysterymarket.de/httpdocs`
- never recursively modify `/var/www/vhosts/mysterymarket.de`
- private Verify assets are outside webroot at `/var/www/vhosts/mysterymarket.de/private/verify-assets`

Important product continuity:
The Verify system is a preserved reusable capability. Future refactoring must retain its
core functions unless the Owner explicitly replaces or cancels them.
