const ROSTER_SPREADSHEET_ID = '1eukojlFNUvf1HPGNGxaKYFTfyDismqf7IWp-rApAXSo';
const ROSTER_SHEET_NAME = 'Team Roster';
const ROSTER_HEADERS = [
  'Team ID',
  'Team Name',
  'Captain',
  'Player Names',
  'Contact Email',
  'Division',
  'Status',
  'Last Updated',
];

function doPost(e) {
  try {
    const payload = JSON.parse((e && e.postData && e.postData.contents) || '{}');
    const configuredSecret = PropertiesService.getScriptProperties().getProperty('ROSTER_SHARED_SECRET');
    if (!configuredSecret || payload.secret !== configuredSecret) {
      return rosterJsonResponse_({ ok: false, error: 'Unauthorized' });
    }
    if (payload.action !== 'team_roster_snapshot' || !Array.isArray(payload.rows)) {
      return rosterJsonResponse_({ ok: false, error: 'Unsupported action' });
    }

    const lock = LockService.getScriptLock();
    lock.waitLock(30000);
    try {
      const spreadsheet = SpreadsheetApp.openById(ROSTER_SPREADSHEET_ID);
      const sheet = spreadsheet.getSheetByName(ROSTER_SHEET_NAME);
      if (!sheet) throw new Error('Team Roster sheet not found');

      const values = payload.rows.map((row) => [
        rosterText_(row.team_id),
        rosterText_(row.team_name),
        rosterText_(row.captain),
        rosterText_(row.player_names),
        rosterText_(row.contact_email),
        rosterText_(row.division),
        rosterText_(row.status),
        rosterDate_(row.last_updated),
      ]);

      const existingRows = Math.max(0, sheet.getLastRow() - 1);
      const rowsToClear = Math.max(existingRows, values.length);
      if (rowsToClear > 0) {
        sheet.getRange(2, 1, rowsToClear, ROSTER_HEADERS.length).clearContent();
      }
      if (values.length > 0) {
        sheet.getRange(2, 1, values.length, ROSTER_HEADERS.length).setValues(values);
      }
      sheet.getRange(1, 1, 1, ROSTER_HEADERS.length).setValues([ROSTER_HEADERS]);
      SpreadsheetApp.flush();
      return rosterJsonResponse_({ ok: true, rows: values.length });
    } finally {
      lock.releaseLock();
    }
  } catch (error) {
    return rosterJsonResponse_({
      ok: false,
      error: error && error.message ? error.message : String(error),
    });
  }
}

function rosterText_(value) {
  return value === null || value === undefined ? '' : String(value);
}

function rosterDate_(value) {
  const text = rosterText_(value).trim();
  if (!text) return '';
  const parsed = new Date(text.replace(' ', 'T'));
  return Number.isNaN(parsed.getTime()) ? text : parsed;
}

function rosterJsonResponse_(payload) {
  return ContentService
    .createTextOutput(JSON.stringify(payload))
    .setMimeType(ContentService.MimeType.JSON);
}
