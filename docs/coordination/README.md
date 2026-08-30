# MysteryMarket Product Coordination

This directory is the DOS-facing semantic handoff for MysteryMarket.

Authority:
- Owner decisions and directives remain final authority.
- Git history remains the technical source of truth.
- `PRODUCT_HANDOFF.json` is the current semantic state consumed by DOS.
- Historical context must not silently override current code, current documentation or later Owner direction.

Required reading order for a product chat:
1. `/AGENTS.md`
2. `/AI_START_HERE.md`
3. `/docs/RELEASE_V1.md`
4. this file
5. `PRODUCT_HANDOFF.json`
6. current branch/code relevant to the active task

Protocol:
- Repository: `gitinsodema/DOS`
- Document: `docs/DOS_PRODUCT_CHAT_HANDOFF_OWNER_APPROVAL_AND_PROGRESS_PROTOCOL_V1.md`
- Protocol revision read for this baseline: 1.1

Update rule:
Update `PRODUCT_HANDOFF.json` whenever version, release phase, blocker, Owner action,
deployment/migration state, architecture, public state, major capability, approval state,
product relationship or next recommended action materially changes.

Important preserved capability:
The MysteryMarket Verify system is a reusable INSODEMA capability pattern and must not
be lost during later website refactoring. It includes code/reference verification,
mobile QR scanning, personal credentials, protected evidence assets, validity windows,
project-specific scopes and printable CR80 identity cards.
