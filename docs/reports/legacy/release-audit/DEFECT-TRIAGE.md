# DEFECT-TRIAGE

## ADH IHA Recovery

- Severity: Critical
- Status: Mitigated locally on 2026-04-16, regressed in active runtime on 2026-04-17
- Symptom: ADH public showed only one visible news item.
- Confirmed root cause:
  - active ADH DB contained only one published row and zero IHA rows;
  - IHA sync entrypoints were queue-first by default;
  - no working queue consumer was evidenced for this environment;
  - scheduled/admin sync therefore had no proven path to create IHA rows in the active DB.
- Minimal fix applied:
  - added bounded `--limit` support to existing `iha:sync` path;
  - changed scheduler to `iha:sync --inline`;
  - changed admin manual sync actions to inline;
  - patched `iha:refresh-images` to single-feed, `iha_id`-matched behavior for safe follow-up.
- Evidence:
  - post-recovery `source='iha'` count: `61`
  - post-recovery `published()` count: `62`
  - rerun produced `0` creates and `61` skips
- Regression evidence on 2026-04-17:
  - active `http://127.0.0.1:8000` runtime still responds
  - active `database/database.sqlite` counts are back to `news_articles=0`, `iha_sync_logs=0`
  - public homepage again collapses to `Bilgi Panosu` + widget-only state
- Remaining risks:
  - the defect must now be treated as a persistence / active DB alignment issue, not only a one-time sync-path issue;
  - older-than-current-feed historical recovery remains unproven;
  - 8 recovered IHA rows still have no image.
