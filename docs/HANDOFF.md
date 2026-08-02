# Current handoff

Last updated: 2026-08-01.

## 2026-08-01 — Shared Workspace Gmail API gateway implemented in source

- The owner confirmed Trivia Nationals must remain on HostGator. The selected
  architecture puts only a small isolated mail gateway on the existing
  DigitalOcean droplet; HostGator WordPress calls it over authenticated HTTPS.
- Read-only verification found DigitalOcean blocks outbound SMTP ports 25, 465,
  and 587 on all Droplets. The gateway transport was therefore corrected to
  Google Workspace's Gmail API over HTTPS.
- Secure-by-default organization policy blocked downloadable service-account
  keys. Rather than weakening it, the owner configured an Internal OAuth app
  and authorized `info@trivianationals.org` for exactly `gmail.send`. The local
  token contains a renewable refresh token and exactly that scope; credential
  values remain outside Git. The now-unneeded domain-wide delegation entry
  should be removed from Workspace Admin.
- Added `workspace-mail-relay/`: localhost-only Node service, app-specific
  bearer authentication with hashed configured secrets, fixed From/Reply-To,
  HTTPS Gmail API handoff, one recipient per request, rolling
  one-message-per-three-seconds global pacing, rolling hourly/24-hour safety
  capacity, hashed-recipient SQLite audit, bounded requests, and no fallback.
  eleven gateway/transport tests pass and npm audit reports zero
  vulnerabilities.
- Prepared unreleased source clients: Timed Quiz supports its own client ID;
  Event Schedule Manager supplies a shared structured WordPress helper;
  Announcements and Attendee Email pause without advancing the current
  recipient on every relay failure. The known-unreliable HostGator
  `wp_mail()` fallback is removed from those source paths.
- Full architecture/setup/rollout/rollback detail is in
  `docs/WORKSPACE-SMTP-RELAY.md`. Nothing is deployed, no DNS/Workspace rule or
  production option changed, no database/recipient state changed, and no email
  was sent. WooCommerce core order mail is deliberately not included yet.

## 2026-08-01 — Shared Workspace relay redeployed for Timed Quiz

- The existing `info@trivianationals.org`-owned Web App deployment matching
  WordPress's configured endpoint was updated in place as Version 5 with the
  tracked `email_quota` action and quota metadata; its URL did not change.
- Timed Quiz authenticated against it successfully. The first quota check
  returned 97, one explicitly authorized test invitation was accepted, and the
  relay returned 96 afterward. The owner confirmed inbox delivery and a working
  personalized production link. No batch or real-player message was sent;
  header inspection remains pending in the Timed Quiz project.
- The existing shared secret was surfaced during authenticated setup. Rotate it
  before the real send in Apps Script Script Properties, WordPress Signup
  Settings, and Timed Quiz's server environment.

## 2026-08-01 — Timed Quiz extracted from TriviaNationals

- Owner named the standalone project **Timed Quiz** and created
  [ref1972/timedquiz](https://github.com/ref1972/timedquiz). The consolidated
  application was extracted with its path-specific Git history and published
  on that repository's `main`; local checkout is
  `/Users/russellefriedewald/Documents/Projects/TimedQuiz`.
- The new repository contains its own `PROJECT.md`, `AGENTS.md`, current state,
  decisions, deployment, handoff, and historical design records. Independent
  verification passes 20/20 tests, TypeScript, diff checks, dependency audit,
  and a secret-pattern scan.
- The shared Workspace Apps Script remains here because Event Signups and
  Announcements also use it. Duplicate quiz application/design source was
  removed here and replaced with `docs/TIMED-QUIZ.md`.
- A nondeterministic release test was fixed during extraction: changing the
  final Base64URL character could alter only unused bits and decode to the same
  ciphertext. The test now changes an authenticated ciphertext character.

## 2026-08-01 — Pop Culture Bee deployment target selected

- Owner selected the existing CASS DigitalOcean droplet and the
  `triviaworkshop.com` domain. `https://bee.triviaworkshop.com` is now live as
  a rehearsal deployment; current operational detail is maintained in
  [ref1972/timedquiz](https://github.com/ref1972/timedquiz).
- Read-only host checks found low CPU load, 16 GB free disk, roughly 438 MB
  available memory plus swap, nginx/Certbot active, and CASS on ports
  3000/3001. This is sufficient for the expected ~70-player Express/SQLite
  launch.
- The droplet has Node 20 and no Docker. Deploy the quiz with a side-by-side
  Node 24 runtime, a dedicated service and localhost port, separate persistent
  data/backups, and a separate nginx virtual host so CASS remains untouched.

## 2026-07-31 — Pop Culture Bee overnight launch-readiness work

- Claude's consolidated baseline was committed/pushed to `main` at `82d6648`.
  Codex then created `codex/pop-culture-bee-launch-readiness` for isolated
  hardening. No production deploy and no real email send occurred.
- Implemented the required score tiebreak: each finalized exposure stores
  server-measured `elapsed_ms` capped at the question deadline; results and CSV
  sort by score descending then total correct-answer time ascending.
- Fixed technical restarts after cutoff: a player whose latest attempt was
  explicitly superseded with an admin restart reason may start the replacement
  generation after the general start cutoff; never-started players remain
  closed.
- Invitation tokens are now stored as both a lookup hash and AES-256-GCM
  ciphertext under a separate production encryption key. Admin can rotate a
  lost/compromised link, which invalidates the old URL and resets delivery
  state.
- Added Workspace invitation email controls: live quota check, test send, and
  confirmed five-recipient batches with per-player attempts/sent/error state.
  A failed or quota-exhausted recipient is not advanced; the sender stops and
  never falls back to `wp_mail()`.
- Added Apps Script `email_quota` and quota metadata around `send_email`.
  **Source only: the Apps Script web app must be redeployed in the morning.**
- Added Docker/Compose packaging, a read-only production preflight, a
  consistent SQLite backup script, admin login throttling, and deployment
  documentation.
- Verification: 20/20 Node tests; `npm run typecheck`; `git diff --check`;
  `scripts/project-checkpoint.sh --check`; fresh database seed/preflight;
  SQLite backup; localhost health, invitation redirect, admin login/dashboard,
  and invitation rotation. Docker image not built because Docker is absent.

### Morning blockers / deliberate owner actions

1. Review/finalize the 50 questions, categories, answers, and aliases before
   any real attempt; the current Tangents-derived set includes some unused
   source material and is a starting set, not final editorial approval.
2. Decide the advancing cut N. Host and hostname are now selected as above.
3. Provision HTTPS, one application instance, persistent `/data`, backups, and
   production secrets; run `npm run preflight`.
4. Redeploy the existing Apps Script web app with the tracked quota endpoint;
   configure relay URL/secret on the quiz host.
5. Send exactly one real test invitation, inspect delivery/headers and live
   quota, then rehearse on real phones and poor connectivity.
6. Import the final ~70 players only after the bank is frozen and reviewed;
   use the admin's confirmed batches for the deliberate real send.

## Recently completed

- 2026-07-30: **5 x 5 signup options restricted** (Event Schedule Manager
  v3.9). The final owner-confirmed rule is an exact allowlist: only Flights D
  and E are generated as selectable 5 x 5 signup options. Flights A-C,
  Semi-Finals, Finals, and any other labels are excluded; no stored signup
  records were changed. This supersedes the initial v3.8 blacklist, which
  correctly removed A-C but mistakenly left Semi-Finals selectable.
- 2026-07-30: **Team Rosters naming and ordering** (Event Schedule Manager,
  now **v3.7**), deployed and hash-verified.
  - The admin team dropdown is now sorted alphabetically within each event
    group (`strnatcasecmp` on the displayed name, so it reads the way it
    sorts and free agents cluster under "FA:"). Event grouping is unchanged.
  - New `tn_tde_team_display_name()` derives every displayed team name:
    blank name → "Team {captain}"; exactly one assigned player → "FA: "
    prefix; the two compose as "FA: Team {captain}". Applied in the admin
    dropdown and panel header, the CSV export, the public `/team-rosters/`
    page, and the "already claimed by" label in the picker.
  - Derived rather than written to the database, at the owner's explicit
    choice, because the "FA: " rule keys off a player count that changes
    with every roster edit — the 2026-07-28 one-time rewrite had already
    gone stale (e.g. "Straphangers" was solo but unprefixed). Any stored
    "FA: " is stripped before the rules re-apply, so nothing double-prefixes
    and the prefix now self-corrects. Stored names are untouched and remain
    what the Team Name field edits; the captain's confirmation email still
    quotes the captain's own stored text, not the derived name.
  - The public page transient was bumped `..._v1` → `..._v2` so the cache
    could not serve pre-deploy names.
  - **Live-verified on the public page**: 87 teams before and after, the 9
    "Unnamed team" entries are gone, 36 teams carry "FA: ", and an automated
    check found zero rule violations (prefix present iff exactly one player)
    and zero double prefixes. **Not verified**: the admin dropdown's
    alphabetical order and panel header, which need an authenticated
    wp-admin session — the sort itself was unit-checked separately against
    real names. Owner should eyeball the dropdown.
  - Two deploy-tooling bugs fixed in `scripts/wp-plugin-ftps.sh` along the
    way, both of which blocked this deploy: the Keychain lookup only tried
    `find-internet-password`, but the password on this machine is a
    *generic* item under service "Trivia Nationals FTPS" (failed with a bare
    `530`); and `deploy`'s error told you to "run 'pull' or 'diff' first"
    while only `pull` recorded the reviewed baseline — and `pull` rightly
    refuses to overwrite an edited working copy, so "edit, diff, deploy" was
    permanently blocked. `diff` now records the baseline too.
  - Pre-deploy drift check found **no** production drift this time: the live
    file matched `main` byte for byte before upload.
- 2026-07-30: reconciled the `agent/quiz-bowl-waitlist` branch into `main`.
  The branch's Quiz Bowl waitlist code was already reproduced on `main` by
  `24e698a`, so the only content `main` was still missing was the hardened
  `AGENTS.md` workflow (GitHub-first freshness check, share a plan before
  changes, commit the durable documentation record after meaningful work) —
  brought forward here. The branch's own doc edits were superseded by
  `main`'s newer `docs/CURRENT-STATE.md`/`docs/HANDOFF.md` and were
  deliberately not merged. `agent/quiz-bowl-waitlist` now holds nothing
  `main` lacks and can be deleted.
- 2026-07-29: fixed real sluggishness on the admin **Team Rosters** screen
  (Event Schedule Manager) and disabled captain self-service roster
  editing, both at the owner's explicit request.
  - **Performance**: `TN_My_Tickets::attendee_roster()` (My Tickets, now
    v0.6.1) rebuilt the entire ticket-holder roster from scratch on every
    call — `wc_get_orders(['limit' => -1, ...])` hydrates a full
    `WC_Order` object plus every line item for *every* order in the
    store, which got noticeably slower as real order volume grew closer
    to the event. Both the admin Team Rosters screen (via
    `tn_tde_team_roster_pool()`) and the public `/team-rosters/` page's
    cache-builder call this on every request, with no caching at the
    `attendee_roster()` level itself — the admin screen was deliberately
    left uncached in the original design (see docs/DECISIONS.md's
    2026-07-28 entry) on the assumption traffic was low; that held until
    the underlying roster rebuild itself grew slow enough to matter even
    at low traffic. Fixed by caching `attendee_roster()`'s result in a
    15-minute transient, invalidated immediately by `invalidate_roster_cache()`
    on the three things that actually change its contents: a
    `woocommerce_order_status_changed` hook (new paid orders,
    cancellations, refunds), allocated-ticket save/delete, and
    preferred-name edits (the "Ticket Names" admin tool). This benefits
    the admin screen, the CSV export, and the public page's cache-builder
    identically since they all go through the same function.
  - **Captain roster editing disabled**: added a single
    `tn_tde_captain_roster_editing_enabled()` switch (Event Schedule
    Manager, now v3.5) currently returning `false`, gating all three
    captain-facing touch points on `/manage-signups/`: the "Choose Team
    Members" link is hidden, the `?tn_view=roster` view itself no longer
    activates (falls through to the normal signups-card view instead of
    erroring), and the POST save handler bails with the standard "could
    not be saved" error as defense-in-depth against a stale bookmarked
    link. The admin Team Rosters screen (staff-only) is untouched and
    remains the sole way to assign team members while this is off.
    Flipping the one function back to `true` fully restores the captain
    flow.
  - **Live drift discovered and reconciled during deploy**: before
    uploading, a pre-deploy diff against production found
    `trivia-desc-editor.php` already running code not in `main` —
    `tn_tde_signup_is_ttg_event()` had been generalized into
    `tn_tde_signup_is_waitlist_event()` (also matching a normalized
    "quiz bowl" title) and the waitlist message now names the actual
    event instead of a hardcoded "All Trivia: The Gathering". This
    matches a remote branch spotted the same session,
    `agent/quiz-bowl-waitlist`, which has not been merged to `main`.
    Reconciled the same way as the 2026-07-28 waitlist-feature drift: took
    the live file as the base, isolated this session's own patch (the
    perf/disable changes above) via `git diff` against the pre-work
    commit, and reapplied just that patch — it applied with zero fuzz,
    confirming no overlap with the quiz-bowl change. Both plugins deployed
    and hash-verified; `main` now needs `agent/quiz-bowl-waitlist` merged
    in (or the equivalent change reproduced) to stop diverging from
    production — not yet done, see below.
  - **Not yet verified live**: rendering/functional verification of both
    changes (confirming the admin screen is actually faster, and that the
    captain flow is actually gone end to end) requires either an
    authenticated wp-admin session or a real captain magic-link token,
    neither of which an agent should generate/use unattended — the owner
    should confirm both in the browser.
  - Also spotted the same session: `agent/sync-live-production` moved on
    the remote too. Neither remote branch has been inspected or merged;
    flagging their existence here so the next person (Claude or Codex)
    knows to check before assuming `main` matches everything live.
  - **Follow-up same day**: the owner reported Team Rosters was still
    slow after the above. Investigation found a much bigger cost the
    first pass missed: `tn_tde_get_home_schedule_events()` parses the
    entire homepage's Elementor `_elementor_data` JSON into a full
    `DOMDocument` and runs an XPath query to reconstruct every scheduled
    event — real DOM-parsing work, and it had **no caching at all**, not
    even for the duration of one request. `tn_tde_team_signup_admin_rows()`
    (which the Team Rosters page calls to build its team dropdown) calls
    `tn_tde_get_event_by_detail_slug()` — which calls that function — once
    per team signup, so with ~75-100 teams the entire homepage schedule
    was being parsed from scratch 75-100 times on a single page load, far
    more expensive than the WooCommerce roster rebuild fixed in the first
    pass. Fixed with simple per-request memoization (a `static` variable
    in the function; the underlying Elementor data can't change
    mid-request, so this is always correct, not just a TTL tradeoff) —
    benefits all 6 call sites, not just this one. Two smaller fixes
    alongside it, both in `tn_tde_team_signup_admin_rows()`/
    `tn_tde_team_rosters_page()`: `get_posts(['fields' => 'ids', ...])`
    returns before WordPress's usual postmeta cache priming (a documented
    core quirk), so every subsequent `get_post_meta()` call across ~75-100
    signups was its own individual query — fixed with one
    `update_meta_cache('post', $ids)` call up front; and each team's
    assigned-player count was being computed twice (once for the summary
    table, again for the dropdown label) — now computed once and reused.
    Event Schedule Manager now v3.6, `php -l` clean, no new drift found
    against the v3.5 deploy, hash-verified live. **Not yet confirmed by
    the owner** whether the page is now actually fast — the DOM/XPath fix
    is the one expected to matter most.

- Built, deployed, and live-tested the My Tickets v0.5.3 system: passwordless
  ticket retrieval, printable QR tickets, mobile staff scanner, check-in roster,
  and editable allocated tickets.
- Restored event signup matching after the Knock Out Quiz title change.
- Built and activated Attendee Email v0.3.0. A read-only recipient preview found
  180 unique ticket/allocated addresses at test time; no email blast was sent.
- Added and deployed the static scores-site coming-soon page, then iterated its
  copy twice (subheading and info-card text); both edits were pushed as part of
  `8bc6ab4` and are **live verified** at `https://scores.trivianationals.org`.
- Verified read-only explicit-FTPS access to the scores account.
- Added this shared Claude/Codex project-memory and checkpoint system
  (`8bc6ab4`).
- 2026-07-28: Claude independently pulled `main`, read `AGENTS.md` → `PROJECT.md`
  → `docs/CURRENT-STATE.md` → `docs/DEPLOYMENT.md` → `docs/HANDOFF.md`, and ran
  `scripts/project-checkpoint.sh --check` — passed cleanly with the local tree
  already matching `origin/main` at `8bc6ab4`. Confirms the shared-memory
  handoff round-trips correctly between Codex and Claude on this machine.
- 2026-07-28: Built and **deployed to production** a team roster picker for
  team-based event signups: a filterable checkbox list of registered ticket
  holders, usable by both a WP admin ("Team Rosters" submenu under
  `trivia-desc-editor`) and the registering team captain (a "Choose Team
  Members" button on their `/manage-signups/` card, opening a dedicated
  picker screen on the same route). Once someone is assigned to a team for an
  event, they're greyed out for every other team in that event. Shipped as
  `tn_tickets_attendee_roster()` in My Tickets (now live at 0.5.4) plus the
  picker/assignment logic in Event Schedule Manager (now live at 2.1). Both
  deploys were hash-verified against the live files after upload.
- 2026-07-28: Before deploying the Event Schedule Manager change, a fresh
  `diff` against production found real drift never captured in Git: the
  "All Trivia: The Gathering" waitlist feature (`isWaitlist`,
  `tn_tde_signup_is_ttg_event()`, waitlist-specific flight/button copy) and a
  richer `tn_tde_render_description_html()` helper both exist live but were
  never pulled back. Reconciled by pulling live as the base, isolating the
  roster-picker patch via `git diff` against the pre-work commit, and
  reapplying just that patch (it applied with zero fuzz — confirms the
  feature never touched the drifted code) before deploying. Git now matches
  production for this file, including the previously-undocumented waitlist
  feature.
- 2026-07-28 incident (resolved): the first FTP upload attempt for
  `trivia-nationals-my-tickets.php` failed with `451 Error during read from
  data connection` **after** the full file was sent, leaving the live file
  **empty** for a few minutes (My Tickets fully down: ticket retrieval,
  check-in scanner, allocated tickets). Root cause: this host's FTPS data
  channel intermittently mishandles TLS 1.3 (seen negotiated in the failing
  transfer); forcing `--tlsv1.2 --tls-max 1.2` fixed it immediately. Restored
  the pre-incident content from a local backup taken seconds earlier, then
  redeployed the feature version — both hash-verified. `scripts/wp-plugin-ftps.sh`
  now forces TLS 1.2 on both `fetch_live`/`push_live` to prevent a repeat.
- 2026-07-28: the user tried the admin "Team Rosters" screen live and found
  two issues, both now fixed, committed, pushed, and **deployed**: (1) the
  page rendered a full attendee-list picker for every team at once — replaced
  with a single dropdown (grouped by event, `(count)` per team) driving one
  shared panel, GET-reload on change, no AJAX; (2) the attendee list showed
  apparent duplicate names — `tn_tickets_attendee_roster()`'s `name` field
  had a backwards ternary that preferred the order's shared billing name
  over the per-seat Preferred Name, so every seat from a multi-ticket order
  showed the identical billing name. Fixed to just use `$preferred_name`
  (which already falls back to the billing name itself when unset). My
  Tickets is now live at **0.5.5**, Event Schedule Manager at **2.2**, both
  hash-verified. Pre-deploy drift check on both files found no unexpected
  production changes this time.
- 2026-07-28: the duplicate-name symptom turned out to be a real, deeper
  bug the 0.5.5 fix hadn't fully addressed: `preferred_name($item, $order)`
  used `get_meta($key, true)`, which only ever returns the *first* value
  for a meta key — so every seat on a quantity>1 line item still shared
  one name, at all 4 call sites (roster, check-in roster, printable/QR
  tickets, QR-scan validation), not just the roster picker. Replaced with
  `preferred_names_for_item()`, which reads *all* values per key
  (`get_meta($key, false)`) and assigns one per seat. Also added, used
  once, then removed a temporary admin action to split a real order
  (19505, bought as one quantity-2 line item with both names crammed into
  one field as `"Will Webster/Britton Webster"`) into two distinct
  per-seat values — confirmed live via browser automation: the order now
  shows two separate `Preferred Name for Ticket/Badge` entries, and both
  "Will Webster" and "Britton Webster" now appear as separate, individually
  selectable rows in the Team Rosters picker. My Tickets is now live at
  **0.5.8**.
  - Two bugs surfaced and were fixed mid-rollout: `get_meta($key, false)`
    returns an array of `WC_Meta_Data` **objects**, not raw values —
    casting one directly to string fatals, which is what caused the
    Team Rosters "critical error" the user hit; and the one-off fix's
    button was a `<button type="submit">` inside a nested `<form>`,
    which WordPress's Edit Order screen already wraps in its own `<form>`
    — nested forms get silently dropped by the browser, so the button did
    nothing on the first click. Fixed by using a plain nonce'd `<a>` link
    instead (the same pattern `tn_tde_signup_status_action_url()` already
    uses in `trivia-desc-editor.php` for one-click admin actions).
  - Confirmed live via browser automation that allocated tickets
    (`tn_alloc_ticket` CPT) were already merged into the roster by
    `attendee_roster()` — all 9 current allocated-ticket holders appear in
    the Team Rosters picker (203 total rows). No code change was needed
    for this; it was a verification, not a bug.
- 2026-07-28: added a reusable **"Ticket Names"** admin screen (My Tickets,
  under the WooCommerce menu) — look up any order by ID, see each ticket
  seat's current Preferred Name, edit and save (only that meta key is
  touched). Replaces one-off scripts for future cases like order 19505.
  Used it to fix order 18490 (Michael Conrad → Dave Legler), confirmed
  live. Also confirmed WooCommerce's own Edit Order → Billing → Email
  address field is already natively editable — no new code needed for
  changing an order's billing email (it's per-order, not per-seat; the
  user confirmed per-order is sufficient).
- 2026-07-28: diagnosed (not a bug, confirmed intentional) why a
  "Semi-Finals" Quiz Bowl team showed 0 players despite its captain
  clearly having assigned herself somewhere — she has two separate Quiz
  Bowl signups (different flights) and assigned her seat to the *other*
  one. Cross-team exclusion is scoped by base event title, not by flight,
  so the same person can't be on two flight-rosters of the same event —
  user confirmed this is the desired behavior. Cancelled the redundant
  Semi-Finals signup via the existing admin "Cancel signup" row action (no
  code needed).
- 2026-07-28: three more Team Rosters upgrades, all in Event Schedule
  Manager (now v2.4), deployed and verified live: (1) an editable **Team
  Name** field on the admin roster panel; (2) an **"Export All Rosters
  (CSV)"** link (`Event, Team Name, Captain, Player Name` columns, one
  combined file); (3) a **public `/team-rosters/` page** listing every
  team event's teams, captains, and player names (confirmed via automated
  scan: zero email addresses in the page source). Also ran a one-time data
  update — 29 solo (1-player) teams got their name prefixed with "FA: "
  (blank ones became "FA: {captain name}") via a temporary admin action,
  applied, confirmed live, then removed from source.
- 2026-07-28: added a per-event **summary table** to the admin Team Rosters
  page (Total Teams / Total Players / Of Which Solo (Free Agent) columns,
  with a note clarifying the total already includes solo teams), and fixed
  a real N+1 performance bug the public `/team-rosters/` page had from the
  start: it was rebuilding the entire attendee roster and re-querying "taken
  by" state once per team (~75 times per page view). Fixed the root cause
  (resolve each team's assigned player ids through a single id→name map
  built once per page load) and added a 1-hour-TTL transient cache on top,
  invalidated immediately by the three functions that change what the page
  shows (roster save, signup status change, new signup). Verified ~8x
  speedup (3.5s → ~420ms) with identical rendered content, and verified the
  cache actually invalidates on a real roster edit. Event Schedule Manager
  is now v3.3.
- 2026-07-28: added a soft-**delete** row action for allocated tickets in
  My Tickets (`wp_trash_post()`-based, nonce-checked, `manage_woocommerce`
  capability), now live at **v0.6.0**.
- 2026-07-28: triaged a forwarded WordPress fatal-error notification email
  referencing My Tickets v0.5.6, line 544 — confirmed **stale/already
  resolved**: production is at v0.6.0, the referenced line is now just a
  comment, and the exact failing URL from the report loads without error.
  No action needed; this was a delayed notification about an incident
  already fixed earlier the same day.
- 2026-07-29: **migrated site email to Google Workspace**, replacing the
  personal-Gmail-alias relay setup. Root cause of "Email Attendees" test
  sends failing: (1) the Apps Script's Gmail account had hit its 100/day
  consumer sending quota, and (2) even successful sends were routing
  through HostGator's own SMTP server anyway, because the `info@` Gmail
  "send mail as" alias was configured to relay through
  `mail.trivianationals.org` rather than send natively — confirmed via a
  real bounce showing that exact path (`wartburg.websitewelcome.com` →
  `450 ... AUP#MXRT`).
  - DNS on `trivianationals.org` (cPanel Zone Editor): MX repointed to
    Google's 5 MX records; SPF gained `include:_spf.google.com`; DKIM added
    at `google._domainkey` using Google's **1024-bit** key option (cPanel's
    Zone Editor TXT field is a single-line ~255-char input with no
    multi-string support — the 2048-bit key silently truncated).
  - `info@trivianationals.org` is now a real Workspace mailbox (display
    name "Trivia Nationals"). The old HostGator forwarders (`info2@`,
    `leeann@`, `marketing@`, plus `info@`'s own dual-forward to two
    personal Gmail addresses) were recreated as Workspace email aliases on
    the `info@` user plus per-address Gmail filters forwarding externally
    (Google Groups were tried first but new Workspace orgs default to
    blocking external group members; the alias+filter approach avoided
    that policy entirely).
  - The Event Signups Apps Script (`google-apps-script/event-signups/Code.gs`)
    is now deployed under a **new** project owned directly by `info@`
    (sharing+redeploying the old personal-account-owned project failed with
    a bare "You do not have permission to perform this action" — Apps
    Script blocks Web App deployment by a non-owner even with Editor
    access). `SYNC_SECRET` was **rotated** into this new project's Script
    Properties rather than reused, closing out the long-standing
    "committed secret never rotated" caution. WordPress's **Event Schedule
    Manager → Signup Settings** admin page now points at the new
    deployment URL and secret.
  - Verified end to end twice — a direct API test and a real send through
    WordPress's actual `/event-signups/` "Email My Signups" flow — by
    inspecting full message headers: `dkim=pass`, `spf=pass`, `dmarc=pass`,
    delivered via `gmailapi.google.com` with zero HostGator hop in the
    `Received:` chain.
  - Not done in this migration: `wp_mail()` itself (the fallback path, and
    the direct path for other mail like WooCommerce order emails) still
    goes through HostGator's broken local `mail()`. Two stray Apps Script
    deployments from troubleshooting (under the old personal-account
    project) are still live but unused.
- 2026-07-29: added the Flight column to the Team Rosters CSV export
  (Event Schedule Manager, now v3.4). Deployed and hash-verified.
- 2026-07-29: built and deployed a brand-new **Announcements** plugin
  (`trivia-nationals-announcements/trivia-nationals-announcements.php`) from
  scratch, after an explicit requirements Q&A with the user. Unlike the
  existing `tn_tde_signup`/`tn_alloc_ticket` CPTs (bare shells + custom
  postmeta), `tn_announcement` uses native `post_title`/`post_content`
  (via `the_content` filter for shortcode/paragraph processing)/
  `post_excerpt`/`post_status` — the built-in post editor is the entire
  authoring UI, no custom fields needed. Ships as v0.1.0 with: an admin CPT
  screen (Teaser + Last Sent sortable columns), a public `/announcements/`
  list page (all Published, newest first, no per-announcement permalinks —
  confirmed a deliberate one-combined-list choice), and a **Send Digest**
  admin tool that reuses the exact same recipient logic as Attendee Email
  (product + allocated-ticket checkboxes) plus a new ticket-purchase-date
  range filter, a recipient-count preview, a send-test-to-self action, and
  a resumable transient-batched real send with a Recent Sends log (same
  patterns as Attendee Email throughout). Sends go through the same
  `tn_tde_send_signup_email()` Apps Script relay (with `wp_mail()` fallback)
  already verified for signups/tickets/Attendee Email. `php -l` clean,
  hash-verified live, and live-tested end to end (CPT create/edit, public
  page rendering including an embedded image, digest preview/test/real
  send).
  - Same day, two follow-up features, both live at **v0.2.0**: (1) a
    drag-and-drop **"Reorder"** admin screen (jQuery UI Sortable, ordering
    persisted to the native `menu_order` field, both the public page and
    admin list sorted by it) — the user first hit a real bug where
    dragging did nothing (the inline `<script>` calling `.sortable()` ran
    before the enqueued `jquery-ui-sortable` library loaded, since it
    wasn't wrapped in a DOM-ready handler; confirmed via
    `jQuery._data(el,'events')` showing no bound click handler before the
    fix, a bound one after) — fixed by wrapping in `jQuery(function ($)
    {...})}`, so this shipped as working drag-and-drop, not a fallback to
    manual numbering; (2) the public page's on-site header was renamed
    from "Announcements" to **"News & Notes"** (URL unchanged, still
    `/announcements/`), and the digest email now includes a link back to
    that page.
  - 2026-07-29: fixed an ampersand-entity bug the user caught in a real
    test digest subject (`&#038;` appearing literally instead of `&`).
    Root cause: `get_the_title()`'s output already runs through
    `wptexturize()`/`convert_chars()`, which HTML-entity-encodes a literal
    `&` to `&#038;` — wrapping that in `esc_html()` (as done in 3 HTML
    contexts) double-encoded it, and using it raw in 3 plain-text contexts
    (digest subject, log fields) leaked the entity text unrendered. Fixed
    all 6 locations: `wp_specialchars_decode($title, ENT_QUOTES)` for the
    plain-text ones, dropped the redundant `esc_html()` for the HTML ones.
    Live at **v0.2.1**.
  - 2026-07-29: investigated a serious data-integrity report — the Send
    Digest screen reported "182/182 sent, 0 failed" for a real digest send,
    but the user never received their own copy and Google Workspace's
    Email Log Search (`~/Downloads/LogSearchResults-20260729-0326.csv`,
    user-provided) showed only ~106 unique recipients actually reaching
    Gmail for that exact subject, with the rest split across `Transient
    Error`/no-further-event rows. Ruled out a duplicate/double-send theory
    initially suspected from a large cluster of Apps Script Executions
    (`~200 executions over 7 days`, user-pasted) — a ~2-hour timezone
    display offset between the WordPress dashboard and the Apps Script
    console had made two views of the *same* burst look like separate
    events; this was explicitly retracted once minute-by-minute analysis
    of `GMAIL_INSERTED` timestamps lined up exactly with the Apps Script
    burst. **Root cause, confirmed via a direct raw `curl` POST to the
    live Apps Script endpoint**: `{"ok":false,"error":"Service invoked too
    many times for one day: email."}` — Apps Script's own internal quota
    for the `MailApp`/`GmailApp` "email" service (tracked per
    script-owning account) was exhausted. This is **not** Gmail-level
    account throttling or SMTP-level reputation throttling (a related but
    separate rate-limiting theme also observed via cross-domain analysis
    of the stuck recipients); it is Apps Script's own service quota. New
    Workspace accounts are commonly granted a much lower initial allowance
    for this quota (sometimes ~100) than the documented 1,500/day ceiling,
    before it ramps up — not independently confirmed beyond the raw error
    text and a matching secondary source the user checked (Gemini, citing
    Google's own support threads), so this remains the best-evidence
    explanation rather than a fully verified one. The prior `send_to()`
    wrapper's silent `wp_mail()` fallback (which returns `true` on this
    host even when a message is actually dropped — see docs/OPERATIONS.md)
    is what let ~76 recipients "succeed" on screen while never actually
    being delivered by either path.
  - Same investigation, with the user's explicit approval, three
    mitigations shipped:
    1. **v0.2.2** — slowed all three send-throttling levers significantly
       (batch size 10→2 recipients per AJAX call, added a 500ms
       `usleep()` between individual sends within a batch, JS polling
       delay between batches 350ms→6000ms) to avoid tripping burst-volume
       abuse heuristics on a brand-new sending domain.
    2. **v0.3.0** — added a **"Send (or resend) to specific addresses"**
       manual tool on the Send Digest page: pick already-published
       announcements, paste a list of specific email addresses, and send
       just to those — built specifically so the failed/fallback-swept
       recipients from the 182-recipient send can be resent to later
       without resending to everyone. The user explicitly deferred
       actually running a resend until the next day.
    3. **v0.4.0 → v0.4.1** — added per-recipient success/path logging
       (`send_to()` now reports `via: 'apps_script'` or
       `'wp_mail_fallback'` per recipient, surfaced in the Recent Sends
       log as an expandable "Via fallback" address list) and
       quota-exhaustion **detection with a graceful batch pause**: a new
       `send_via_apps_script()` returns the Apps Script relay's actual
       error text (rather than collapsing every failure to a bare
       `false`, as the shared `tn_tde_send_email_via_apps_script()`
       helper does); `send_to()` flags `quota_exhausted` when that error
       text matches "too many times"/"quota"; `ajax_send_batch()` now
       stops a batch immediately at that point (rather than blindly
       burning through the rest of the recipients via the unreliable
       fallback mailer) with a message explaining the quota typically
       resets around midnight Pacific and to use "Resume interrupted
       send" afterward — the batch transient is already saved at the
       correct offset for that to work cleanly. `php -l` clean,
       hash-verified live as of 2026-07-29 (v0.4.1), committed and pushed
       to `main` at `32579cb`.
    - Functional verification of the live quota-pause path itself (i.e.
      actually triggering `wp_send_json_error()`'s pause message through a
      real AJAX batch call) has **not** been done — that requires an
      authenticated wp-admin session, which is outside what an agent
      should do unattended. The underlying detection logic reuses the
      exact `stripos()` match already confirmed correct against the real
      Apps Script error text via the raw `curl` test above.
    4. **v0.4.2** — a Codex code review of `32579cb` caught a real bug in
       the v0.4.1 quota-pause logic: `send_to()` still attempted the
       `wp_mail()` fallback for the recipient whose Apps Script call hit
       the quota, and `ajax_send_batch()` incremented `next_offset` and
       persisted the transient for that recipient *before* checking
       `quota_exhausted` — so that one recipient was marked "done" and
       skipped on every future "Resume interrupted send," even if the
       fallback had silently dropped their message (the exact failure
       mode this whole feature exists to prevent). Fixed by having
       `send_to()` skip the `wp_mail()` fallback entirely when quota
       exhaustion is detected (spending it on an already-distrusted
       fallback gains nothing once every subsequent send will fail the
       same way) and having `ajax_send_batch()` check `quota_exhausted`
       *before* incrementing the offset or saving the transient, so that
       recipient is left untouched for the next resume instead of counted
       as sent or failed. Also improved `ajax_test()`'s error message to
       name the quota specifically instead of a generic "could not be
       sent." `php -l` clean, hash-verified live, committed and pushed to
       `main`.

## Immediate next steps

- Use `/project-checkpoint` in Claude, or ask Codex for a complete project
  checkpoint, after meaningful future releases.
- Pull `main` on the MacBook before continuing work there.
- Scope the real scoring system before replacing the static placeholder.
- Consider pointing `wp_mail()` itself at Workspace SMTP directly (e.g. WP
  Mail SMTP plugin) so the fallback path and other mail (WooCommerce order
  emails) also drop the HostGator dependency, not just the Apps Script path.
- Delete the two unused Apps Script Web App deployments left over from the
  Workspace migration's troubleshooting (owned by the old personal-account
  project) — harmless but tidy up when convenient.
- Inspect the `agent/sync-live-production` remote branch before assuming
  `main` matches everything live. (`agent/quiz-bowl-waitlist` is now fully
  reconciled — see the 2026-07-30 entry above.)
- Confirm live in the browser that the Team Rosters admin dropdown is now
  alphabetical within each event and that the panel header shows the derived
  name (2026-07-30 entry above) — the public page was verified
  automatically, the admin screen needs a logged-in look.
- Confirm live in the browser that the Team Rosters admin screen is
  actually faster now and that the captain "Choose Team Members" flow is
  fully gone (link hidden, direct URL falls through safely) — both
  changes are deployed and hash-verified but not yet functionally
  verified end to end (see the 2026-07-29 entry above for why).
- Once ready to restore captain self-service roster editing, flip
  `tn_tde_captain_roster_editing_enabled()` back to `true`.
- The captain "Choose Team Members" flow (previously the remaining
  untested path here) is now deliberately disabled rather than pending
  test — see the 2026-07-29 entry above. If it's re-enabled later, still
  live-test it end to end, including the confirmation email and
  cross-team exclusion with two real team signups on the same event.
  The admin screen and the name-per-seat fix are now confirmed live.
- Watch for any recurrence of the `451`/TLS 1.3 FTP data-connection issue on
  future deploys, now that `wp-plugin-ftps.sh` forces TLS 1.2 — if it still
  recurs, the cause isn't (only) TLS version and needs more digging.
- If `get_meta($key, false)` comes up again in this codebase, remember it
  returns `WC_Meta_Data` objects, not raw values — extract `->value`
  (see `TN_My_Tickets::meta_values_for_key()` for the defensive pattern).
- Once the Apps Script quota resets, use the Announcements plugin's new
  "Send (or resend) to specific addresses" tool to resend the 2026-07-29
  digest to whichever addresses Email Log Search still shows as
  undelivered from the original 182-recipient send — the user deferred
  this deliberately until they were back at the keyboard.
- Consider directly confirming the Apps Script `MailApp` daily quota via
  `MailApp.getRemainingDailyQuota()` (run manually in the Apps Script
  editor, view the Execution log) rather than relying solely on the raw
  error text — would confirm whether/when the account's quota has ramped
  up from its apparent initial low allowance toward the documented
  1,500/day.

## Known cautions

- Local untracked customer exports and working assets predate this checkpoint;
  they are intentionally excluded.
- The Event Signups Apps Script now runs deployed, under Script Properties,
  with a rotated `SYNC_SECRET` (see docs/HANDOFF.md's 2026-07-29 entry above).
  The formerly committed secret is no longer in use but still exists in Git
  history; it is not sensitive to rotate again if ever needed.
- Other Apps Script working copies may contain live secret values; only
  reviewed, intentionally tracked code belongs in Git.
- Do not infer that every source directory is active in production. In
  particular, confirm the standalone Event Schedule plugin's role before
  deployment.
- Test-email and attendee-send actions produce real external messages and
  require deliberate authorization.
- The Apps Script relay used by both `tn_tde_send_signup_email()` and the
  Announcements plugin's `send_to()` can silently fail over to
  HostGator's `wp_mail()`, which returns `true` even when a message is
  actually dropped. Any bulk send should be treated as unverified until
  cross-checked against Google Workspace's Email Log Search, not just the
  sender/failure counts shown in the WordPress admin screen.
