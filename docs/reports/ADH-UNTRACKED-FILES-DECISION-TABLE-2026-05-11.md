# ADH Untracked Files Decision Table - 2026-05-11

## Purpose

Classify files left untracked after commit `bd1279b3` on branch
`codex/adh-local-predeploy-package`.

No files were staged while preparing this table.

## Current Untracked Groups

### 1. Runtime-required public image assets

Files:

- `public/images/branding/adh-logo-dark.svg`
- `public/images/branding/adh-logo-light.svg`
- `public/images/branding/favicon.svg`
- `public/images/news/meclis-siyaset.jpg`
- `public/images/news/placeholder-news.jpg`

Evidence:

- `app/Support/SiteBranding.php` falls back to `images/branding/adh-logo-light.svg`,
  `images/branding/adh-logo-dark.svg`, and `images/branding/favicon.svg`.
- `app/Support/NewsPresenter.php` falls back to `images/news/placeholder-news.jpg`.
- `app/Providers/Filament/AdminPanelProvider.php` uses the branding SVGs.
- `resources/views/layouts/partials/meta.blade.php` and footer/header surfaces use these
  branding fallbacks.
- Tests assert `public_path('images/news/placeholder-news.jpg')` exists.

Recommendation:

- Stage and commit these public image assets in a follow-up commit.

Risk if left untracked:

- Fresh clone/deploy can miss logo, favicon, and fallback news image assets.
- Admin/public UI may render broken image paths.

### 2. Generated Filament public assets

Files:

- `public/css/filament/`
- `public/js/filament/`

Evidence:

- These files look like package-published/generated Filament assets.
- No direct application reference was found outside reports.

Recommendation:

- Do not stage by default.
- Prefer generating/publishing these assets during deploy or dependency install.
- If Hetzner deploy policy cannot reliably publish them, add them in a separate asset commit.

Risk if left untracked:

- Admin panel CSS/JS may break on a fresh deployment if the deploy process does not publish
  Filament assets.

### 3. Generated sitemap

File:

- `public/sitemap.xml`

Evidence:

- `routes/web.php` serves `/sitemap.xml` from `public/sitemap.xml` if present.
- `php artisan sitemap:generate` writes this file.
- Scheduler runs `sitemap:generate` daily.

Recommendation:

- Do not stage by default.
- Generate on deploy or by scheduler.

Risk if left untracked:

- `/sitemap.xml` may be empty/missing until first generation run unless deploy executes
  `php artisan sitemap:generate`.

### 4. Historical handoff/forensic files

Files:

- `handoff/03-quality/release-audit/ADH-IHA-OPERATOR-CHEATSHEET.md`
- `handoff/03-quality/release-audit/ADH-IHA-RECOVERY-DIAGNOSIS.md`
- `handoff/03-quality/release-audit/ADH-IHA-RECOVERY-EXECUTION.md`
- `handoff/03-quality/release-audit/ADH-IHA-RECOVERY-PLAN.md`
- `handoff/03-quality/release-audit/ADH-IHA-RECOVERY-VERIFICATION.md`
- `handoff/03-quality/release-audit/ADH-RUNTIME-DB-EVIDENCE.json`
- `handoff/03-quality/release-audit/ADH-RUNTIME-DB-FORENSICS.md`

Recommendation:

- Keep untracked unless the project wants full forensic history in Git.
- If needed, move relevant documents into `docs/reports/` in a separate documentation commit.

### 5. Root audit/triage markdown

Files:

- `ADH-ADMIN-AUDIT.md`
- `ADH-PUBLIC-AUDIT.md`
- `DEFECT-TRIAGE.md`
- `FINAL-RELEASE-GATE.md`

Recommendation:

- Keep untracked for now.
- If still useful, migrate curated content into `docs/reports/` and commit separately.

### 6. Accidental local artifacts

Files:

- `get([`
- `where(`

Content:

- PHP command-line parse error output.

Recommendation:

- Delete after user approval.
- Never stage.

## Recommended Immediate Action

Create a small follow-up commit for runtime-required public image assets:

```bash
git add public/images/branding public/images/news
git commit -m "Add required public image assets"
```

Do not add:

- generated Filament assets,
- sitemap,
- handoff files,
- root audit files,
- accidental artifacts.

## Recommended Deploy Action

Add to Hetzner deploy checklist:

```bash
php artisan filament:assets
php artisan sitemap:generate
```

If `filament:assets` is not available in the installed Filament version, use the correct
Filament asset publish command for that version.
