# ADH Commit Inventory - 2026-05-11

## Purpose

Record the commit-readiness state of the local workspace before any staging or commit action.

No files were staged while preparing this inventory.

## Git Baseline

Initial repository state:

- Current HEAD: `c1fc3a0e`
- Last commit: `c1fc3a0e 2024-03-12 13:52:43 +0000 Adjusts minimum stability`

Current branch state:

- Branch created: `codex/adh-local-predeploy-package`
- No files were staged during branch creation.

Implication:

- The workspace is now on a named branch.
- Staging should still be selective because the working tree remains broad and dirty.

## Workspace Classification

### Tracked modifications

Tracked files with modifications include framework, configuration, routing, build, and test files:

- `.env.example`
- `app/Models/User.php`
- `app/Providers/AppServiceProvider.php`
- `bootstrap/app.php`
- `bootstrap/providers.php`
- `composer.json`
- `config/app.php`
- `config/cache.php`
- `config/logging.php`
- `config/mail.php`
- `config/services.php`
- `config/session.php`
- `database/seeders/DatabaseSeeder.php`
- `package.json`
- `phpunit.xml`
- `resources/css/app.css`
- `resources/js/app.js`
- `routes/console.php`
- `routes/web.php`
- `tests/Feature/ExampleTest.php`
- `vite.config.js`

Tracked deletions:

- `public/robots.txt`
- `resources/views/welcome.blade.php`

### Untracked application baseline

Most ADH-specific application code is currently untracked from Git's perspective:

- `app/Console/`
- `app/Filament/`
- `app/Http/Controllers/`
- `app/Http/Middleware/`
- `app/Jobs/`
- `app/Mail/`
- ADH domain models under `app/Models/`
- `app/Observers/`
- `app/Policies/`
- `app/Providers/Filament/`
- `app/Services/`
- `app/Support/`
- additional `config/` files
- `database/migrations/`
- `database/seeders/`
- `lang/`
- `routes/api.php`
- public Blade templates under `resources/views/`
- admin/public CSS and JS under `resources/css/` and `resources/js/`
- public and admin test suites under `tests/`
- project reports under `docs/`

Implication:

- A patch-only commit is not deployable unless the remote target branch already contains this
  ADH application baseline.

## Stage-Excluded Local Artifacts

The following files should remain outside staging unless explicitly reviewed and justified:

- `.env`
- `.phpunit.result.cache`
- `node_modules/`
- `vendor/`
- `storage/`
- `public/sitemap.xml`, unless generated sitemap is intentionally committed
- `get([`
- `where(`

The root files `get([` and `where(` contain PHP command-line parse error output and should be
treated as accidental local artifacts, not release files.

## Release Branch Recommendation

Recommended next Git action:

```bash
git switch -c codex/adh-local-predeploy-package
```

Then choose one staging route:

- Full release snapshot: use if the remote branch does not already contain the ADH baseline.
- Patch-only release: use only if the ADH baseline is already present upstream.

## Commit Safety Rules

- Do not run `git add .`.
- Do not stage secrets or local runtime files.
- Do not stage accidental root artifacts.
- Do not stage generated cache/build files unless the deployment policy requires them.
- Confirm staged diff with `git diff --cached --stat` and `git diff --cached --name-only`.
- Run the verification commands from `ADH-SELECTIVE-STAGING-PLAN-2026-05-11.md` before commit.

## Recommended Decision

Create a named branch first, then decide between full-snapshot staging and patch-only staging
after confirming the intended remote target branch state.
