/**
 * Question-screen client. Three jobs: show the countdown, autosave the draft,
 * and auto-submit at zero.
 *
 * None of this is trusted. The server stamped when the question was served and
 * decides what counts; this script only makes the deadline visible and gives
 * the honest player the behaviour they were promised. If it fails to load, the
 * Submit button still works and the server still closes the window on time.
 */
(function () {
  'use strict';

  var cfg = window.__QUIZ__;
  if (!cfg) return;

  var form = document.getElementById('answerForm');
  var input = document.getElementById('answer');
  var clock = document.getElementById('clock');
  var bar = document.getElementById('timerbar');
  var autoFlag = document.getElementById('autoFlag');
  var submitBtn = document.getElementById('submitBtn');
  var status = document.getElementById('status');

  var submitted = false;

  // Anchor to a monotonic clock. Changing the system time won't shift this,
  // and it stays correct across tab throttling because each tick recomputes
  // from the delta rather than counting down by a fixed step.
  var start = performance.now();
  var remaining = cfg.remainingMs;

  function msLeft() {
    return Math.max(0, remaining - (performance.now() - start));
  }

  function render() {
    var left = msLeft();
    var seconds = Math.ceil(left / 1000);
    clock.textContent = seconds + 's';
    clock.classList.toggle('urgent', left <= 5000);
    bar.style.width = (left / cfg.durationMs) * 100 + '%';

    if (left <= 0) {
      autoSubmit();
      return;
    }
    requestAnimationFrame(render);
  }

  function autoSubmit() {
    if (submitted) return;
    autoFlag.value = '1';
    status.textContent = "Time's up — submitting your answer.";
    doSubmit();
  }

  function doSubmit() {
    if (submitted) return;
    submitted = true;
    submitBtn.disabled = true;
    input.readOnly = true;
    // Use requestSubmit so the browser runs the normal form submission path.
    if (form.requestSubmit) form.requestSubmit();
    else form.submit();
  }

  form.addEventListener('submit', function (event) {
    if (submitted) return;
    // A manual click: mark it handled so the timer can't fire a second one.
    if (msLeft() <= 0) autoFlag.value = '1';
    submitted = true;
    submitBtn.disabled = true;
    void event;
  });

  // --- Draft autosave -------------------------------------------------------
  // Keeps the server's copy of the answer box current, so a closed laptop or a
  // dead tab still submits what the player had typed.

  var lastSaved = input.value;

  function saveDraft(useBeacon) {
    if (submitted) return;
    var text = input.value;
    if (text === lastSaved) return;
    lastSaved = text;

    var payload = JSON.stringify({ position: cfg.position, text: text });

    if (useBeacon && navigator.sendBeacon) {
      navigator.sendBeacon('/draft', new Blob([payload], { type: 'application/json' }));
      return;
    }
    fetch('/draft', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: payload,
      keepalive: true
    }).catch(function () {
      // A failed autosave is not worth interrupting the player over; the next
      // tick retries, and the submit itself carries the authoritative text.
      lastSaved = null;
    });
  }

  setInterval(function () { saveDraft(false); }, cfg.draftIntervalMs);
  input.addEventListener('blur', function () { saveDraft(false); });

  // Best-effort flush when the page is being torn down. `visibilitychange` is
  // the reliable one on mobile; `pagehide` covers Safari's bfcache.
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') saveDraft(true);
  });
  window.addEventListener('pagehide', function () { saveDraft(true); });

  // --- Tab-switch logging ---------------------------------------------------
  // Recorded as an admin signal only. Twenty seconds is short enough that
  // looking something up is difficult; this exists so a suspiciously perfect
  // result can be examined, not to punish anyone automatically.
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState !== 'hidden' || submitted) return;
    var payload = JSON.stringify({ position: cfg.position, kind: 'tab_hidden' });
    if (navigator.sendBeacon) {
      navigator.sendBeacon('/signal', new Blob([payload], { type: 'application/json' }));
    }
  });

  input.focus();
  requestAnimationFrame(render);
})();
