# ADH IHA Operator Cheatsheet

Date: 2026-04-16
Applies to the local recovered ADH runtime in `C:\nwp0203\haber-sitesi`

## 1. Set the Active DB Context First

```powershell
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE='C:\nwp0203\haber-sitesi\database\database.sqlite'
$env:QUEUE_CONNECTION='database'
```

Do this before every recovery / health command in this local environment.

## 2. Current / Live IHA Ingestion Command

```powershell
php artisan iha:sync --inline
```

Use this for normal live pulls and for rerun verification.

## 3. Historical / Backfill Command

Safe bounded first pass:

```powershell
php artisan iha:sync --inline --limit=10
```

Then the full current feed window:

```powershell
php artisan iha:sync --inline
```

## 4. Optional Image Refresh Command

Use only after checking missing-image counts:

```powershell
php artisan iha:refresh-images --hours=24 --limit=10
```

This command is now single-feed and `iha_id` matched. It is safe for controlled follow-up, but it can only recover images still available in the current feed snapshot.

## 5. Health-Check Commands

Scheduler path:

```powershell
php artisan schedule:list
```

Log tail:

```powershell
Get-Content .\storage\logs\laravel.log -Tail 60
```

App-scoped counts and latest sync log:

```powershell
@'
<?php
require __DIR__ . "/vendor/autoload.php";
$app = require __DIR__ . "/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo json_encode([
    'published' => App\Models\NewsArticle::published()->count(),
    'iha_total' => App\Models\NewsArticle::fromIha()->count(),
    'iha_published' => App\Models\NewsArticle::fromIha()->published()->count(),
    'iha_missing_images' => App\Models\NewsArticle::fromIha()->where(function ($q) {
        $q->whereNull('featured_image')->orWhere('featured_image', '');
    })->count(),
    'latest_log' => optional(App\Models\IhaSyncLog::query()->latest('started_at')->first())->only([
        'id','status','articles_fetched','articles_created','articles_updated','articles_skipped'
    ]),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
'@ | php
```

Public homepage quick check:

```powershell
$page = Invoke-WebRequest -UseBasicParsing http://127.0.0.1:8000/
($page.Links | Where-Object {
    $_.href -match '\?locale=tr$' -and
    $_.href -notmatch '/kategori/' -and
    $_.href -notmatch '/etiket/' -and
    $_.href -notmatch '/il/'
} | Select-Object -ExpandProperty href | Sort-Object -Unique).Count
```

## 6. Counts to Inspect After Each Run

Inspect these immediately after every sync:

1. latest `iha_sync_logs` row
2. `published` count
3. `source='iha'` count
4. `iha_missing_images` count
5. duplicate `iha_id` count
6. homepage article-link count

Duplicate `iha_id` check:

```powershell
@'
<?php
$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$rows = $db->query("SELECT iha_id, COUNT(*) AS total FROM news_articles WHERE source='iha' AND iha_id IS NOT NULL GROUP BY iha_id HAVING COUNT(*) > 1")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
'@ | php
```

## 7. When to Stop and Investigate

Stop immediately if any of these happen:

1. latest sync log status is `failed`
2. rerun creates large numbers of unexpected new rows
3. duplicate `iha_id` rows appear
4. public homepage drops back to one item
5. category or city pages stop showing recovered IHA rows
6. `iha:refresh-images` shows repeated failures
7. commands are accidentally targeting the wrong DB context

## 8. What Healthy Looks Like

Healthy signs after recovery:

1. `php artisan schedule:list` shows `php artisan iha:sync --inline`
2. latest sync log is `success`
3. rerun results are mostly `skipped`
4. homepage shows multiple current IHA articles
5. category and city pages expose recovered articles
