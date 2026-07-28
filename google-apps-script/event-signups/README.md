# Event signup Google Sheets endpoint

1. Open the event signup spreadsheet:
   `https://docs.google.com/spreadsheets/d/12313QDfiuz96DhApw65MieHHePBcSxXIZJI82ductCA/edit`
2. Choose **Extensions > Apps Script**.
3. Replace `Code.gs` with this directory's `Code.gs`.
4. Open **Project Settings > Script Properties** and add `SYNC_SECRET` with a
   new long random value. Never place the value in source control.
5. Deploy as a web app that executes as the spreadsheet owner and allows access
   to anyone.
6. Copy the `/exec` web app URL into WordPress under **Events > Signup
   Settings**.
7. Paste the same secret value into **Events > Signup Settings**.

The endpoint creates or updates a `Signups` tab, validates the shared secret, and upserts rows by `signup_id`.

If a secret was ever committed to Git, rotate it in both Script Properties and
WordPress. Removing it from the current file does not erase it from Git history.
