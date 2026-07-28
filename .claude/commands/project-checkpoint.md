# Project checkpoint

Perform a complete, evidence-based project checkpoint for this repository.

1. Read `AGENTS.md`, `PROJECT.md`, and every current document directly under
   `docs/` (excluding dated inventory snapshots until comparison is needed).
2. Inspect the complete tracked tree, Git status, recent history, plugin
   headers, scripts, dependencies, public endpoints, deployment mechanisms,
   and relevant tests. Treat untracked content as user-owned and do not open
   likely customer-data exports or secret-bearing files unless necessary.
3. Run `scripts/project-checkpoint.sh --write`.
4. Review the generated `docs/inventory/LATEST.md`. Correct or supplement the
   human-authored documents based on evidence:
   - `PROJECT.md` for stable architecture and component ownership;
   - `docs/CURRENT-STATE.md` for live/local/pending status;
   - `docs/DEPLOYMENT.md` for safe deploy and rollback procedures;
   - `docs/DATA-MODEL.md` for identifiers and persistent data;
   - `docs/OPERATIONS.md` for routine procedures;
   - `docs/DECISIONS.md` for durable decisions and rationale;
   - `docs/HANDOFF.md` for recent work, verification, risks, and next actions.
5. Create or refresh the dated inventory snapshot requested by the script.
6. Run `scripts/project-checkpoint.sh --check`, `git diff --check`, and relevant
   syntax/tests. Inspect the exact staged diff before committing.
7. Never stage credentials, customer/attendee data, CSV exports, generated QR
   codes, photos, backups, deployment state, local settings, or unknown
   untracked files. Secret locations may be recorded without values.
8. Commit with a descriptive checkpoint message and push the current branch
   when the user has authorized the complete checkpoint workflow.

Report what changed, what was verified, what remains uncertain, and the pushed
commit. Do not claim production equivalence unless it was directly checked.
