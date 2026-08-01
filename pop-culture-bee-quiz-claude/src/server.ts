/**
 * HTTP layer. Deliberately thin: every decision about what a player may see or
 * do lives in quiz.ts, so the routes cannot accidentally become a second,
 * disagreeing source of truth about timing.
 *
 * Phase 1 identifies the player with a signed cookie pointing at the seeded
 * local test account. Phase 2 replaces that with emailed magic-link tokens;
 * the rest of the application should not need to change.
 */
import { randomBytes } from 'node:crypto';
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import Fastify from 'fastify';
import cookie from '@fastify/cookie';
import formbody from '@fastify/formbody';

import { HOST, PORT, START_DEADLINE_ISO, DEADLINE_DISPLAY } from './config.ts';
import { get, logEvent, run } from './db.ts';
import { currentState, saveDraft, startPlayer, submitAnswer } from './quiz.ts';
import { closedPage, finishedPage, landingPage, questionPage } from './views.ts';

const here = dirname(fileURLToPath(import.meta.url));

const COOKIE_NAME = 'pcb_player';
const COOKIE_SECRET = process.env.COOKIE_SECRET ?? randomBytes(32).toString('hex');

if (!process.env.COOKIE_SECRET) {
  console.warn(
    '[warn] COOKIE_SECRET not set; using a random per-boot value. ' +
      'Sessions will not survive a restart. Set it before deploying.',
  );
}

const app = Fastify({ logger: { level: process.env.LOG_LEVEL ?? 'info' } });

await app.register(cookie, { secret: COOKIE_SECRET });
await app.register(formbody);

/**
 * Exactly two static assets, served from named routes.
 *
 * A general static-file plugin would mean carrying a directory-traversal
 * surface (and the CVE history that goes with it) to serve two files whose
 * names are known at build time. These are read once at boot.
 */
const ASSETS: Record<string, { body: string; type: string }> = {
  '/app.css': {
    body: readFileSync(join(here, '..', 'public', 'app.css'), 'utf8'),
    type: 'text/css; charset=utf-8',
  },
  '/quiz.js': {
    body: readFileSync(join(here, '..', 'public', 'quiz.js'), 'utf8'),
    type: 'text/javascript; charset=utf-8',
  },
};

for (const [path, asset] of Object.entries(ASSETS)) {
  app.get(path, async (_request, reply) => {
    reply.type(asset.type);
    reply.header('cache-control', 'public, max-age=300');
    return reply.send(asset.body);
  });
}

type PlayerRow = { id: number; display_name: string | null; email: string };

/** Phase 1 stand-in for authentication: the seeded local test account. */
function devPlayer(): PlayerRow | undefined {
  return get(`SELECT id, display_name, email FROM players ORDER BY id LIMIT 1`) as
    | PlayerRow
    | undefined;
}

function playerFrom(request: { cookies: Record<string, string | undefined> }): PlayerRow | undefined {
  const raw = request.cookies[COOKIE_NAME];
  if (raw) {
    const unsigned = app.unsignCookie(raw);
    if (unsigned.valid && unsigned.value) {
      const row = get(
        `SELECT id, display_name, email FROM players WHERE id = ?`,
        Number(unsigned.value),
      ) as PlayerRow | undefined;
      if (row) return row;
    }
  }
  return devPlayer();
}

function html(reply: { type: (t: string) => unknown; send: (b: string) => unknown }, body: string) {
  reply.type('text/html; charset=utf-8');
  return reply.send(body);
}

/** Entries close at the deadline, but a session already underway runs to the end. */
function startingIsClosed(): boolean {
  if (!START_DEADLINE_ISO) return false;
  return Date.now() > Date.parse(START_DEADLINE_ISO);
}

app.get('/', async (request, reply) => {
  const player = playerFrom(request);
  if (!player) return html(reply, closedPage('No player account is configured yet.'));

  reply.setCookie(COOKIE_NAME, String(player.id), {
    path: '/',
    httpOnly: true,
    sameSite: 'lax',
    signed: true,
  });

  const state = currentState(player.id);
  if (state.status === 'question') return reply.redirect('/play');
  if (state.status === 'finished') return reply.redirect('/done');
  return html(reply, landingPage(player.display_name));
});

app.post('/start', async (request, reply) => {
  const player = playerFrom(request);
  if (!player) return reply.redirect('/');

  const existing = get(`SELECT started_at FROM players WHERE id = ?`, player.id);
  if (!existing?.['started_at'] && startingIsClosed()) {
    return html(
      reply,
      closedPage(`Entries closed ${DEADLINE_DISPLAY}. This quiz is no longer accepting new players.`),
    );
  }

  startPlayer(player.id);
  return reply.redirect('/play');
});

app.get('/play', async (request, reply) => {
  const player = playerFrom(request);
  if (!player) return reply.redirect('/');

  const state = currentState(player.id);
  if (state.status === 'not_started') return reply.redirect('/');
  if (state.status === 'finished') return reply.redirect('/done');
  return html(reply, questionPage(state));
});

app.post<{ Body: { position?: string; answer?: string; auto?: string } }>(
  '/answer',
  async (request, reply) => {
    const player = playerFrom(request);
    if (!player) return reply.redirect('/');

    const position = Number(request.body?.position);
    const answer = String(request.body?.answer ?? '');
    const auto = request.body?.auto === '1';

    if (Number.isFinite(position)) {
      submitAnswer(player.id, position, answer, auto);
    }

    // Post/redirect/get: a browser reload must not resubmit an answer.
    return reply.redirect('/play');
  },
);

app.post<{ Body: { position?: number; text?: string } }>('/draft', async (request, reply) => {
  const player = playerFrom(request);
  if (!player) return reply.code(204).send();

  const position = Number(request.body?.position);
  const text = String(request.body?.text ?? '');
  if (Number.isFinite(position)) saveDraft(player.id, position, text);

  return reply.code(204).send();
});

/** Low-value behavioural signals, recorded for admin review only. */
app.post<{ Body: { position?: number; kind?: string } }>('/signal', async (request, reply) => {
  const player = playerFrom(request);
  if (player) {
    const kind = String(request.body?.kind ?? 'unknown').slice(0, 40);
    logEvent(`client_${kind}`, {
      playerId: player.id,
      position: Number(request.body?.position) || null,
      ip: request.ip,
      userAgent: request.headers['user-agent'] ?? null,
    });
  }
  return reply.code(204).send();
});

app.get('/done', async (request, reply) => {
  const player = playerFrom(request);
  if (!player) return reply.redirect('/');

  const state = currentState(player.id);
  if (state.status === 'question') return reply.redirect('/play');
  if (state.status === 'not_started') return reply.redirect('/');
  return html(reply, finishedPage(player.display_name));
});

/** Local convenience only: wipe this player's progress and replay. */
app.post('/dev/reset', async (request, reply) => {
  if (process.env.NODE_ENV === 'production') return reply.code(404).send();
  const player = playerFrom(request);
  if (player) {
    run(`DELETE FROM answers WHERE player_id = ?`, player.id);
    run(`UPDATE players SET started_at = NULL, finished_at = NULL WHERE id = ?`, player.id);
  }
  return reply.redirect('/');
});

await app.listen({ port: PORT, host: HOST });
