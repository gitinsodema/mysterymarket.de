# MysteryMarket AI Start Here

Read in this order before product work:

1. `AGENTS.md`
2. `docs/RELEASE_V1.md`
3. `docs/BACKOFFICE_ROADMAP.md`
4. `docs/VERIFY_CREDENTIAL_WALLET_ARCHITECTURE.md`
5. `docs/APPLE_WALLET_RUNBOOK.md` when credential/Wallet work is involved
6. `docs/coordination/README.md`
7. `docs/coordination/PRODUCT_HANDOFF.json`
8. the current branch/code relevant to the requested task

Mandatory cross-product coordination protocol:

`gitinsodema/DOS/docs/DOS_PRODUCT_CHAT_HANDOFF_OWNER_APPROVAL_AND_PROGRESS_PROTOCOL_V1.md`

Current repository:
- `gitinsodema/mysterymarket.de`
- released baseline: `main` / V1.0.0
- active post-release development branch: `r1.2-credentials`

Production safety:
- operate inside `/var/www/vhosts/mysterymarket.de/httpdocs`
- never recursively modify `/var/www/vhosts/mysterymarket.de`
- private Verify assets are outside webroot at `/var/www/vhosts/mysterymarket.de/private/verify-assets`

Important product continuity:
The Verify/Credential/Wallet subsystem is a preserved reusable capability. Future
refactoring must retain its source-of-truth model, protected-asset boundary, integrity
gate, revision lifecycle and output semantics unless the Owner explicitly replaces or
cancels them. Reuse guidance is documented in
`docs/VERIFY_CREDENTIAL_WALLET_ARCHITECTURE.md`.


Backoffice continuity:
MysteryMarket owns Elite membership, identity, credentials, private member information and lightweight administration. ShopperMatch remains a separate job/matching product and must not be duplicated inside MysteryMarket.


Current continuation point:
- R1.2 Credentials/Verify/Wallet is in release review on branch `r1.2-credentials`, version `1.2.0`.
- Wallet code and preview are implemented; real signing is blocked only by external Apple Developer provisioning.
- Next chat must first read `docs/APPLE_WALLET_RUNBOOK.md` and `docs/coordination/PRODUCT_HANDOFF.json`, then resolve the long-term INSODEMA Apple Developer Account Holder/team structure before creating Pass Type IDs or certificates.
- R1.2 still requires final technical gate evidence and explicit Owner release approval before merge to `main`.
