# Claude project instructions

The shared project memory is tool-neutral. Read and follow `AGENTS.md` first,
then use `PROJECT.md` and `docs/` as the source of truth.

The Claude command `/project-checkpoint` performs the full checkpoint workflow.
Do not rely on Claude conversation memory as a substitute for updating the
tracked project documents.

Keep Claude-only workflow details in this file or `.claude/commands/`; keep
project facts in the shared Markdown documents so Codex and other tools receive
the same information.
