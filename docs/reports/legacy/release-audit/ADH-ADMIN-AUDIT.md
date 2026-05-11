# ADH-ADMIN-AUDIT

## 2026-04-17 Regression Note

The 2026-04-16 admin / ops recovery note below is no longer sufficient to describe the current active ADH runtime.

Current revalidation on 2026-04-17:

- active `database/database.sqlite` contains `0` news rows and `0` sync logs
- `http://127.0.0.1:8000/health` still returns `database = ok`
- checked-in `.env` still points to MySQL while the proven working runtime evidence comes from SQLite

Interpretation:

- admin/ops health cannot currently be considered stable
- the main unresolved issue is active DB alignment and persistence of recovered data

---

## ADH IHA Recovery Update

Date verified: 2026-04-16

Admin / ops-side status:

- scheduler now lists `php artisan iha:sync --inline`
- `IhaHealth::getViewData()` resolves to:
  - `freshness_state = healthy`
  - `freshness_lag_minutes ≈ 1.39`
  - latest log status `success`
- recent `iha_sync_logs` show:
  - bounded recovery success
  - full recovery success
  - idempotency rerun success

Operational note:

- direct browser login verification of `/admin/iha-health` was not executed in this run
- the health result above is proven from the page class data path and current `iha_sync_logs`

Open admin-side note:

- translation backlog grew after replay and should be monitored separately from Turkish public visibility.
