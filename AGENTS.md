# Trivia Nationals agent instructions

This repository is the durable project memory shared by Codex, Claude, and the
owner's computers.

Before substantive work:

1. Read `PROJECT.md`.
2. Read `docs/CURRENT-STATE.md`, `docs/DEPLOYMENT.md`, and `docs/HANDOFF.md`.
3. Run `git status --short` and preserve unrelated user changes.
4. Pull or fetch before editing when network access and the current workflow
   permit it. Never overwrite a dirty working tree merely to synchronize.

After a meaningful release, production change, architectural decision, or
investigation:

1. Update the relevant shared documents.
2. Put durable facts in `PROJECT.md` or the appropriate topic document, not
   only in a conversational handoff.
3. Update `docs/HANDOFF.md` with recent results, verification, and next steps.
4. Run `scripts/project-checkpoint.sh --check`.
5. Do not commit credentials, tokens, attendee/order exports, customer data,
   generated QR codes, photos, backups, deployment state, or local settings.

When asked for a complete project checkpoint, follow
`docs/CHECKPOINT-PROCESS.md`, run `scripts/project-checkpoint.sh --write`, review
the generated inventory, update the human-authored documents, run safety checks,
and then commit and push if authorized.

Deployment status must always distinguish:

- source present locally;
- committed and pushed;
- deployed to production;
- behavior verified on production.

Secrets may be documented by storage location and retrieval method, but never
by secret value.
