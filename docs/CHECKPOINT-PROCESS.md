# Shared checkpoint process

The goal of a checkpoint is to leave enough verified context that Claude,
Codex, or a different computer can continue safely without relying on chat
history.

## When to checkpoint

- after a production deployment or rollback;
- after a feature becomes usable;
- after a significant investigation or architectural decision;
- before switching computers or AI tools;
- before a risky migration;
- whenever the owner requests a complete inventory.

## Procedure

1. Synchronize carefully: inspect Git status, fetch/pull when safe, and preserve
   unrelated local work.
2. Inspect tracked source, recent commits, component versions, scripts, and
   production evidence available within the task's authorization.
3. Run:

   ```bash
   scripts/project-checkpoint.sh --write
   ```

4. Review `docs/inventory/LATEST.md` and update the human-authored project
   documents. Generated inventory is evidence, not a substitute for judgment.
5. Record uncertainty explicitly. Use the terms **local only**, **pushed**,
   **deployed**, and **live verified** precisely.
6. Run:

   ```bash
   scripts/project-checkpoint.sh --check
   git diff --check
   ```

7. Review the exact files being staged. Commit and push the checkpoint.
8. On another computer, clone the repository once and then pull before work.

## Information placement

| Information | Location |
|---|---|
| Stable purpose and architecture | `PROJECT.md` |
| Current live/local/pending state | `docs/CURRENT-STATE.md` |
| Deployment and rollback | `docs/DEPLOYMENT.md` |
| IDs, metadata, and persistent records | `docs/DATA-MODEL.md` |
| Routine administration | `docs/OPERATIONS.md` |
| Decisions and rationale | `docs/DECISIONS.md` |
| Latest results and next actions | `docs/HANDOFF.md` |
| Mechanical repository snapshot | `docs/inventory/` |

## Security boundary

Never commit secret values, WordPress recovery links, attendee/customer
information, WooCommerce exports, local Keychain output, generated QR codes,
photos, backups, or temporary deployment state. Document only the credential
owner, Keychain account/service, or configuration location needed to retrieve a
secret.
