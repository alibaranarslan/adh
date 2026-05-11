# ADH Pre-Staging Secret and Smoke Scan - 2026-05-11

## Purpose

Record the pre-staging scan for secret keys, AdSense smoke values, and local runtime artifacts.

No files were staged while preparing this report.

## Result

Status: PASS with exclusions

No real secret value candidate was found in `.env.example` or `.env.production.example`.

The scan found expected key names and configuration references in code, tests, and reports.
These are not treated as secret leaks when they use `env(...)`, placeholders, test fixtures,
or documentation-only text.

## Environment Example Files

Checked files:

- `.env.example`
- `.env.production.example`

Checked keys:

- `APP_KEY`
- `DB_PASSWORD`
- `MAIL_PASSWORD`
- `IHA_PASSWORD`
- `IHA_USERNAME`
- `IHA_USER_CODE`
- `SENTRY_LARAVEL_DSN`

Observed state:

- `.env.example`: sensitive values are empty or placeholder.
- `.env.production.example`: sensitive values are empty.

Decision:

- Both files can remain staging candidates.
- Real production values must only be configured on Hetzner and must not be committed.

## Expected Code References

The scan found sensitive key names in expected code locations:

- `config/app.php`
- `config/database.php`
- `config/mail.php`
- `config/services.php`
- `config/sentry.php`
- `app/Services/IhaApiService.php`
- `app/Filament/Pages/IhaHealth.php`
- `app/Filament/Pages/IntegrationSettings.php`
- `app/Support/AdminSafeText.php`
- `app/Console/Commands/VerifyDeployCommand.php`

Decision:

- These are expected references to environment/configuration keys, not committed secret values.

## AdSense and Smoke References

The scan found AdSense-related strings in expected places:

- advertisement model/resource code,
- public ad-slot Blade component,
- integration settings,
- demo content seeder,
- advertising tests,
- advertising reports.

`ADH_SMOKE` was found only in local readiness/report documentation.

Decision:

- This is acceptable for documentation and test history.
- No temporary smoke ad records should be staged because smoke data lives in the local database,
  not in release source files.
- Real AdSense publisher/client values remain a production configuration task.

## Local Database Finding

`database/database.sqlite` contains historical AdSense/smoke strings from local testing.

Git state:

- `database/database.sqlite` is ignored by `database/.gitignore` through `*.sqlite*`.

Decision:

- Do not stage local SQLite databases.
- Do not use local SQLite contents as release data.

## Exclusions That Must Remain

Keep these out of staging:

- `.env`
- `database/database.sqlite`
- any `*.sqlite*`
- `.phpunit.result.cache`
- `storage/`
- `vendor/`
- `node_modules/`
- `get([`
- `where(`

## Production Note

Production secrets must be verified on Hetzner by presence only, without printing values:

- `APP_KEY`
- database credentials
- mail credentials
- IHA credentials
- Sentry DSN, if enabled
- Google AdSense publisher/client and slot values

## Recommendation

The source tree can proceed to selective staging after reviewing the full staged file list.
Do not stage ignored local database files or accidental root artifacts.
