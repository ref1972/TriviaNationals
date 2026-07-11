const SPREADSHEET_ID = '12313QDfiuz96DhApw65MieHHePBcSxXIZJI82ductCA';
const SHEET_NAME = 'Signups';
const SYNC_SECRET = '6b4c584070e064f06cc7790808bcb0c2546a5ad1016f89c0';
const SUMMARY_FROM_EMAIL = 'info@trivianationals.org';
const SUMMARY_FROM_NAME = 'Trivia Nationals';

const SIGNUP_HEADERS = [
  'signup_id',
  'submitted_at',
  'event_slug',
  'event_title',
  'event_session',
  'event_day',
  'event_date',
  'event_start',
  'event_end',
  'event_location',
  'name',
  'email',
  'flight',
  'team',
  'team_members',
  'notes',
  'status',
  'status_changed_at',
  'status_reason',
];

function doGet() {
  return jsonResponse({ ok: true, service: 'Trivia Nationals event signups' });
}

function authorizeMailApp() {
  return MailApp.getRemainingDailyQuota();
}

function doPost(e) {
  try {
    const payload = JSON.parse((e && e.postData && e.postData.contents) || '{}');
    if (payload.secret !== SYNC_SECRET) {
      return jsonResponse({ ok: false, error: 'Unauthorized' });
    }

    if (payload.action === 'ping') {
      return jsonResponse({ ok: true, action: 'ping' });
    }

    if (payload.action === 'event_signup_email_summary') {
      if (!payload.email) {
        return jsonResponse({ ok: false, error: 'Missing email' });
      }
      return emailSignupSummary(String(payload.email));
    }

    if (payload.action === 'event_signup_summary_lookup') {
      if (!payload.email) {
        return jsonResponse({ ok: false, error: 'Missing email' });
      }
      return jsonResponse(lookupSignupSummary(String(payload.email)));
    }

    if (payload.action === 'send_email') {
      const to = normalizeEmail(payload.to);
      if (!to || !/@/.test(to) || !payload.subject) {
        return jsonResponse({ ok: false, error: 'Missing to/subject' });
      }
      const htmlBody = String(payload.html_body || '');
      const plainBody = String(payload.plain_body || '') || htmlBody.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
      GmailApp.sendEmail(to, String(payload.subject), plainBody, {
        htmlBody: htmlBody,
        from: SUMMARY_FROM_EMAIL,
        replyTo: SUMMARY_FROM_EMAIL,
        name: SUMMARY_FROM_NAME,
      });
      return jsonResponse({ ok: true, action: 'sent', to: to });
    }

    if (payload.action === 'event_signup_delete') {
      if (!payload.signup || !payload.signup.signup_id) {
        return jsonResponse({ ok: false, error: 'Invalid payload' });
      }
      const deleteLock = LockService.getScriptLock();
      deleteLock.waitLock(30000);
      try {
        const spreadsheet = SpreadsheetApp.openById(SPREADSHEET_ID);
        const sheet = getOrCreateSheet(spreadsheet, SHEET_NAME);
        ensureHeaders(sheet);
        const row = findSignupRow(sheet, String(payload.signup.signup_id));
        if (row) {
          sheet.deleteRow(row);
          SpreadsheetApp.flush();
        }
        return jsonResponse({
          ok: true,
          action: row ? 'deleted' : 'not_found',
          signup_id: String(payload.signup.signup_id),
        });
      } finally {
        deleteLock.releaseLock();
      }
    }

    if (payload.action !== 'event_signup_upsert' || !payload.signup || !payload.signup.signup_id) {
      return jsonResponse({ ok: false, error: 'Invalid payload' });
    }

    const lock = LockService.getScriptLock();
    lock.waitLock(30000);
    try {
      const spreadsheet = SpreadsheetApp.openById(SPREADSHEET_ID);
      const sheet = getOrCreateSheet(spreadsheet, SHEET_NAME);
      ensureHeaders(sheet);

      const signup = payload.signup;
      const values = SIGNUP_HEADERS.map((header) => sanitizeCellValue(signup[header]));
      const row = findSignupRow(sheet, String(signup.signup_id)) || findActiveSignupDuplicateRow(sheet, signup);

      if (row) {
        sheet.getRange(row, 1, 1, SIGNUP_HEADERS.length).setValues([values]);
      } else {
        sheet.appendRow(values);
      }

      SpreadsheetApp.flush();
      return jsonResponse({
        ok: true,
        action: row ? 'updated' : 'appended',
        signup_id: String(signup.signup_id),
      });
    } finally {
      lock.releaseLock();
    }
  } catch (error) {
    console.error(error);
    return jsonResponse({ ok: false, error: String(error && error.message ? error.message : error) });
  }
}

function emailSignupSummary(email) {
  const summary = lookupSignupSummary(email);
  if (!summary.ok) {
    return jsonResponse(summary);
  }
  sendSignupSummaryEmail(summary.email, summary.signups);
  return jsonResponse({ ok: true, action: 'emailed', count: summary.signups.length });
}

function lookupSignupSummary(email) {
  const normalizedEmail = normalizeEmail(email);
  if (!normalizedEmail || !/@/.test(normalizedEmail)) {
    return { ok: false, error: 'Invalid email' };
  }

  const lock = LockService.getScriptLock();
  lock.waitLock(30000);
  try {
    const spreadsheet = SpreadsheetApp.openById(SPREADSHEET_ID);
    const sheet = getOrCreateSheet(spreadsheet, SHEET_NAME);
    ensureHeaders(sheet);
    const rows = findSignupsByEmail(sheet, normalizedEmail);
    return { ok: true, action: 'lookup', email: normalizedEmail, count: rows.length, signups: rows };
  } finally {
    lock.releaseLock();
  }
}

function getOrCreateSheet(spreadsheet, sheetName) {
  return spreadsheet.getSheetByName(sheetName) || spreadsheet.insertSheet(sheetName);
}

function ensureHeaders(sheet) {
  const headers = sheet.getRange(1, 1, 1, SIGNUP_HEADERS.length).getDisplayValues()[0];
  if (headers.join('|') !== SIGNUP_HEADERS.join('|')) {
    sheet.getRange(1, 1, 1, SIGNUP_HEADERS.length).setValues([SIGNUP_HEADERS]);
  }
}

function findSignupRow(sheet, signupId) {
  const lastRow = sheet.getLastRow();
  if (lastRow < 2) {
    return null;
  }

  const match = sheet
    .getRange(2, 1, lastRow - 1, 1)
    .createTextFinder(signupId)
    .matchEntireCell(true)
    .findNext();
  return match ? match.getRow() : null;
}

function findActiveSignupDuplicateRow(sheet, signup) {
  const lastRow = sheet.getLastRow();
  if (lastRow < 2) {
    return null;
  }

  const incomingSignature = signupDuplicateSignature(signup);
  if (!incomingSignature) {
    return null;
  }

  const values = sheet.getRange(2, 1, lastRow - 1, SIGNUP_HEADERS.length).getDisplayValues();
  for (let index = values.length - 1; index >= 0; index--) {
    const existing = rowToSignup(values[index]);
    const status = String(existing.status || 'active').trim().toLowerCase();
    if (status && status !== 'active') {
      continue;
    }
    if (signupDuplicateSignature(existing) === incomingSignature) {
      return index + 2;
    }
  }
  return null;
}

function signupDuplicateSignature(signup) {
  const parts = [
    normalizeEmail(signup.email),
    normalizeKey(signup.event_slug),
    normalizeKey(signup.flight),
    normalizeKey(signup.team),
    normalizeKey(signup.team_members),
  ];
  return parts[0] && parts[1] ? parts.join('|') : '';
}

function findSignupsByEmail(sheet, normalizedEmail) {
  const lastRow = sheet.getLastRow();
  if (lastRow < 2) {
    return [];
  }

  const values = sheet.getRange(2, 1, lastRow - 1, SIGNUP_HEADERS.length).getDisplayValues();
  return values
    .filter((row) => normalizeEmail(row[11]) === normalizedEmail)
    .map(rowToSignup);
}

function rowToSignup(row) {
  return SIGNUP_HEADERS.reduce((signup, header, index) => {
    signup[header] = row[index] || '';
    return signup;
  }, {});
}

function sendSignupSummaryEmail(email, signups) {
  const subject = 'Your Trivia Nationals 2026 event signups';
  const plainBody = buildPlainSignupSummary(signups);
  const htmlBody = buildHtmlSignupSummary(signups);
  GmailApp.sendEmail(email, subject, plainBody, {
    htmlBody,
    from: SUMMARY_FROM_EMAIL,
    replyTo: SUMMARY_FROM_EMAIL,
    name: SUMMARY_FROM_NAME,
  });
}

function assertSummarySenderIsConfigured() {
  const aliases = GmailApp.getAliases();
  if (aliases.indexOf(SUMMARY_FROM_EMAIL) === -1) {
    throw new Error(`${SUMMARY_FROM_EMAIL} is not configured as a Gmail send-as alias.`);
  }
  return true;
}

function legacyMailAppSend(email, subject, plainBody, htmlBody) {
  MailApp.sendEmail({
    to: email,
    subject,
    body: plainBody,
    htmlBody,
    name: SUMMARY_FROM_NAME,
  });
}

function buildPlainSignupSummary(signups) {
  if (!signups.length) {
    return [
      'We did not find any Trivia Nationals 2026 event signups associated with this email address.',
      '',
      'If you recently submitted a signup, please give it a minute and try again.',
    ].join('\n');
  }

  const lines = [
    'Here are the Trivia Nationals 2026 event signups associated with this email address:',
    '',
  ];
  signups.forEach((signup, index) => {
    lines.push(`${index + 1}. ${signup.event_title || 'Event signup'}`);
    if (signup.flight) lines.push(`   Flight: ${signup.flight}`);
    if (signup.event_day || signup.event_date || signup.event_start) {
      lines.push(`   Time: ${[signup.event_day, signup.event_date, signup.event_start].filter(Boolean).join(', ')}`);
    }
    if (signup.event_location) lines.push(`   Location: ${signup.event_location}`);
    if (signup.team) lines.push(`   Team Name: ${signup.team}`);
    if (signup.team_members) lines.push(`   Team Members: ${signup.team_members}`);
    lines.push('');
  });
  return lines.join('\n');
}

function buildHtmlSignupSummary(signups) {
  if (!signups.length) {
    return '<p>We did not find any Trivia Nationals 2026 event signups associated with this email address.</p><p>If you recently submitted a signup, please give it a minute and try again.</p>';
  }

  const items = signups.map((signup) => {
    const details = [];
    if (signup.flight) details.push(`<li><strong>Flight:</strong> ${escapeHtml(signup.flight)}</li>`);
    if (signup.event_day || signup.event_date || signup.event_start) {
      details.push(`<li><strong>Time:</strong> ${escapeHtml([signup.event_day, signup.event_date, signup.event_start].filter(Boolean).join(', '))}</li>`);
    }
    if (signup.event_location) details.push(`<li><strong>Location:</strong> ${escapeHtml(signup.event_location)}</li>`);
    if (signup.team) details.push(`<li><strong>Team Name:</strong> ${escapeHtml(signup.team)}</li>`);
    if (signup.team_members) details.push(`<li><strong>Team Members:</strong> ${escapeHtml(signup.team_members)}</li>`);
    return `<li><h3>${escapeHtml(signup.event_title || 'Event signup')}</h3><ul>${details.join('')}</ul></li>`;
  }).join('');

  return `<p>Here are the Trivia Nationals 2026 event signups associated with this email address:</p><ol>${items}</ol>`;
}

function normalizeEmail(email) {
  return String(email || '').trim().toLowerCase();
}

function normalizeKey(value) {
  return String(value || '').trim().toLowerCase().replace(/\s+/g, ' ');
}

function sanitizeCellValue(value) {
  if (value === null || value === undefined) {
    return '';
  }

  const text = String(value);
  return /^[=+\-@]/.test(text) ? `'${text}` : text;
}

function escapeHtml(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function jsonResponse(value) {
  return ContentService
    .createTextOutput(JSON.stringify(value))
    .setMimeType(ContentService.MimeType.JSON);
}
