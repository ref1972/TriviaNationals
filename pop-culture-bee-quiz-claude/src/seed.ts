/**
 * Seeds placeholder questions and a local test player.
 *
 * The 50 real questions are not written yet (Phase 0). These placeholders
 * exist so the flow can be played end to end today; they are deliberately
 * obvious so nobody mistakes them for real content.
 *
 *   npm run seed
 */
import { db, nowIso, run } from './db.ts';
import { TOTAL_QUESTIONS } from './config.ts';

const PLACEHOLDERS: Array<[string, string, string[]]> = [
  ['Which band released the album "Abbey Road"?', 'The Beatles', ['Beatles']],
  ['What is the name of the coffee shop in "Friends"?', 'Central Perk', []],
  ['Who directed "Jurassic Park"?', 'Steven Spielberg', ['Spielberg']],
  ['What year did MTV first go on the air?', '1981', []],
  ['Which video game plumber rescues Princess Peach?', 'Mario', ['Super Mario']],
  ['What is Indiana Jones’s real first name?', 'Henry', ['Henry Jones Jr']],
  ['Who sang "Like a Prayer"?', 'Madonna', []],
  ['In "The Office" (US), what is Dwight’s last name?', 'Schrute', []],
  ['Which streaming show features the Upside Down?', 'Stranger Things', []],
  ['What does the "D" stand for in D.B. Cooper?', 'Dan', ['Daniel']],
];

const database = db();

database.exec('DELETE FROM answers');
database.exec('DELETE FROM events');
database.exec('DELETE FROM questions');

for (let position = 1; position <= TOTAL_QUESTIONS; position += 1) {
  const source = PLACEHOLDERS[(position - 1) % PLACEHOLDERS.length]!;
  const [prompt, answer, aliases] = source;
  run(
    `INSERT INTO questions (position, prompt, canonical_answer, aliases)
     VALUES (?, ?, ?, ?)`,
    position,
    `[Placeholder ${position}] ${prompt}`,
    answer,
    JSON.stringify(aliases),
  );
}

// Phase 1 has no invitations yet, so a single known local player stands in.
run(
  `INSERT INTO players (email, display_name, is_test, invited_at)
   VALUES (?, ?, 1, ?)
   ON CONFLICT(email) DO UPDATE SET
     started_at = NULL, finished_at = NULL`,
  'local@example.test',
  'Local Tester',
  nowIso(),
);

const player = database
  .prepare('SELECT id, email FROM players WHERE email = ?')
  .get('local@example.test') as { id: number; email: string };

console.log(`Seeded ${TOTAL_QUESTIONS} placeholder questions.`);
console.log(`Test player #${player.id} <${player.email}> reset to not-started.`);
