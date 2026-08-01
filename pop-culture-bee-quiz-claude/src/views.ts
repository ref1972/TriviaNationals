/**
 * Server-rendered HTML. Plain template literals rather than a template engine:
 * there are five screens, and a dependency would cost more than it saves.
 */
import { DEADLINE_DISPLAY, DRAFT_INTERVAL_MS, QUESTION_DURATION_MS } from './config.ts';
import type { QuestionState } from './quiz.ts';

export function esc(value: unknown): string {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}

function layout(title: string, body: string, opts: { bodyClass?: string } = {}): string {
  return `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title>${esc(title)}</title>
<link rel="stylesheet" href="/app.css">
</head>
<body class="${esc(opts.bodyClass ?? '')}">
<main class="shell">
${body}
</main>
</body>
</html>`;
}

export function landingPage(name: string | null): string {
  return layout(
    'Pop Culture Bee — Preliminary Quiz',
    `
<header class="brand">
  <p class="kicker">Trivia Nationals</p>
  <h1>Pop Culture Bee</h1>
  <p class="subtitle">Preliminary Quiz</p>
</header>

${name ? `<p class="greeting">Good luck, ${esc(name)}.</p>` : ''}

<section class="card rules">
  <h2>Before you begin</h2>
  <ul>
    <li><strong>50 questions.</strong> You'll see one at a time.</li>
    <li><strong>20 seconds each.</strong> When time runs out, whatever is in the
        answer box is submitted automatically — so type something.</li>
    <li><strong>Spelling is judged by a human.</strong> Close counts. Don't burn
        your clock second-guessing an extra letter.</li>
    <li><strong>You get one attempt.</strong> There's no going back to an earlier
        question.</li>
    <li><strong>You won't see your score.</strong> Results are announced after
        the deadline.</li>
  </ul>
</section>

<section class="card warn">
  <h2>If you stop partway through</h2>
  <p>You can close this and come back — you'll pick up where you left off. But
     the question that was on screen when you left will run out of time and be
     scored as whatever you'd typed. Leaving costs you that one question.</p>
</section>

<p class="deadline">Entries close ${esc(DEADLINE_DISPLAY)}.</p>

<form method="post" action="/start" class="actions">
  <button type="submit" class="primary">Start the quiz</button>
</form>

<p class="fineprint">The timer starts as soon as the first question appears.</p>
`,
    { bodyClass: 'page-landing' },
  );
}

export function questionPage(state: QuestionState): string {
  return layout(
    `Question ${state.position} — Pop Culture Bee`,
    `
<div class="qhead">
  <span class="progress">Question ${state.position} of ${state.totalQuestions}</span>
  <span class="clock" id="clock" aria-live="off">--</span>
</div>

<div class="timerbar"><div class="timerbar-fill" id="timerbar"></div></div>

<section class="card question">
  <p class="prompt">${esc(state.prompt)}</p>
</section>

<form method="post" action="/answer" id="answerForm" class="answer">
  <input type="hidden" name="position" value="${state.position}">
  <input type="hidden" name="auto" id="autoFlag" value="0">
  <label class="sr-only" for="answer">Your answer</label>
  <input
    type="text"
    id="answer"
    name="answer"
    value="${esc(state.draft)}"
    autocomplete="off"
    autocorrect="off"
    autocapitalize="off"
    spellcheck="false"
    enterkeyhint="send"
    autofocus>
  <button type="submit" class="primary" id="submitBtn">Submit</button>
</form>

<p class="fineprint" id="status" role="status"></p>

<script>
  window.__QUIZ__ = {
    position: ${state.position},
    remainingMs: ${Math.max(0, Math.round(state.remainingMs))},
    durationMs: ${QUESTION_DURATION_MS},
    draftIntervalMs: ${DRAFT_INTERVAL_MS}
  };
</script>
<script src="/quiz.js"></script>
`,
    { bodyClass: 'page-question' },
  );
}

export function finishedPage(name: string | null): string {
  return layout(
    'Thank you — Pop Culture Bee',
    `
<header class="brand">
  <p class="kicker">Trivia Nationals</p>
  <h1>Pop Culture Bee</h1>
</header>

<section class="card done">
  <h2>That's all 50.</h2>
  <p class="lead">Thank you${name ? `, ${esc(name)},` : ''} for playing.</p>
  <p>Your score will determine whether you move on to the <strong>LIVE game on
     Saturday</strong>. Answers are reviewed by hand after the deadline, so
     results aren't instant.</p>
  <p>We'll be in touch by email. Good luck.</p>
</section>

<p class="fineprint">You can close this window. Your answers are saved.</p>
`,
    { bodyClass: 'page-done' },
  );
}

export function closedPage(reason: string): string {
  return layout(
    'Quiz closed — Pop Culture Bee',
    `
<header class="brand">
  <p class="kicker">Trivia Nationals</p>
  <h1>Pop Culture Bee</h1>
</header>
<section class="card warn">
  <h2>This quiz is closed</h2>
  <p>${esc(reason)}</p>
</section>
`,
    { bodyClass: 'page-closed' },
  );
}
