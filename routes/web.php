<?php

use App\Http\Controllers\AdminGuideProgressController;
use App\Http\Controllers\AiVisibilityController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

$sitemapResponse = function (string $filename) {
    $path = public_path($filename);
    abort_unless(file_exists($path), 404);

    return response(\App\Support\SeoUrls::sanitizeXml((string) file_get_contents($path)), 200)
        ->header('Content-Type', 'application/xml; charset=UTF-8');
};

Route::get('/health', function () {
    $checks = [
        'status' => 'ok',
        'app' => 'adh',
        'timestamp' => now()->toIso8601String(),
        'debug' => (bool) config('app.debug'),
        'queue_driver' => (string) config('queue.default'),
    ];

    try {
        DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (\Throwable $e) {
        $checks['database'] = 'fail';
        $checks['status'] = 'degraded';
    }

    try {
        Cache::put('health_probe_adh', 'ok', 10);
        $checks['cache'] = Cache::get('health_probe_adh') === 'ok' ? 'ok' : 'fail';
        if ($checks['cache'] !== 'ok') {
            $checks['status'] = 'degraded';
        }
    } catch (\Throwable $e) {
        $checks['cache'] = 'fail';
        $checks['status'] = 'degraded';
    }

    if ($checks['queue_driver'] === 'database') {
        $checks['queue_table_present'] = Schema::hasTable(config('queue.connections.database.table', 'jobs'));
    }

    $code = $checks['status'] === 'ok' ? 200 : 503;
    return response()->json($checks, $code);
});

Route::middleware('auth')->prefix('admin')->group(function () {
    Route::post('/guide-progress', [AdminGuideProgressController::class, 'store'])
        ->name('admin.guides.progress.store');
});

Route::get('/sitemap.xml', fn () => $sitemapResponse('sitemap.xml'));

Route::get('/{sitemap}', function (string $sitemap) use ($sitemapResponse) {
    abort_unless(in_array($sitemap, [
        'sitemap-pages.xml',
        'sitemap-categories.xml',
        'sitemap-articles.xml',
        'sitemap-news.xml',
    ], true), 404);

    return $sitemapResponse($sitemap);
})->where('sitemap', 'sitemap-(pages|categories|articles|news)\.xml');

Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])
    ->middleware('throttle:newsletter')
    ->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
Route::get('/preview/home/{revision}', [HomePageController::class, 'preview'])
    ->middleware('signed')
    ->name('layout.preview.home');
Route::get('/preview/header-theme/{headerTheme}', [HomePageController::class, 'themePreview'])
    ->middleware('signed')
    ->name('header-theme.preview.home');

Route::get('/llms.txt', [AiVisibilityController::class, 'llms'])->name('llms');
Route::get('/rss.xml', [FeedController::class, 'rss'])->name('feeds.rss');
Route::get('/feed/news.xml', [FeedController::class, 'news'])->name('feeds.news');
Route::get('/feed/adiyaman.xml', [FeedController::class, 'adiyaman'])->name('feeds.adiyaman');
Route::get('/feed/kategori/{slug}.xml', [FeedController::class, 'category'])->name('feeds.category');

Route::get('/robots.txt', function () {
    $stored = \App\Models\Setting::get('seo', 'robots_txt');
    $content = \App\Support\RobotsTxt::render($stored);

    return response($content, 200)->header('Content-Type', 'text/plain');
});

Route::prefix('{locale}')
    ->where(['locale' => 'tr|en|ku'])
    ->middleware('set.locale')
    ->group(function () {
        Route::get('/', [HomePageController::class, 'index']);
        Route::get('/arama', [SearchController::class, 'index'])->middleware('throttle:search');
        Route::get('/arsiv', [ArchiveController::class, 'index']);
        Route::get('/iletisim', [PageController::class, 'contact']);
        Route::post('/iletisim', [PageController::class, 'submitContact'])->middleware('throttle:contact');
        Route::get('/hakkimizda', [PageController::class, 'about']);
        Route::get('/yayin-ilkeleri', [PageController::class, 'editorialPolicy']);
        Route::get('/gizlilik-politikasi', [PageController::class, 'privacy']);
        Route::get('/kvkk', [PageController::class, 'kvkk']);
        Route::get('/cerez-politikasi', [PageController::class, 'cookies']);
        Route::get('/kategori/{slug}', [NewsController::class, 'category']);
        Route::get('/iller', [CityController::class, 'index']);
        Route::get('/il/{slug}', [CityController::class, 'show']);
        Route::get('/etiket/{slug}', [NewsController::class, 'tag']);
        Route::get('/sayfa/{slug}', [PageController::class, 'show']);
        Route::get('/{slug}', [NewsController::class, 'show']);
    });

Route::middleware('set.locale')->group(function () {
    Route::get('/', [HomePageController::class, 'index'])->middleware('cache.page:300')->name('home');
    Route::get('/arama', [SearchController::class, 'index'])->middleware('throttle:search')->name('search');
    Route::get('/arsiv', [ArchiveController::class, 'index'])->middleware('cache.page:300')->name('news.archive');
    Route::get('/iletisim', [PageController::class, 'contact'])->name('contact');
    Route::post('/iletisim', [PageController::class, 'submitContact'])->middleware('throttle:contact')->name('contact.submit');
    Route::get('/hakkimizda', [PageController::class, 'about'])->middleware('cache.page:600')->name('page.about');
    Route::get('/yayin-ilkeleri', [PageController::class, 'editorialPolicy'])->middleware('cache.page:600')->name('page.editorial_policy');
    Route::get('/gizlilik-politikasi', [PageController::class, 'privacy'])->middleware('cache.page:600')->name('page.privacy');
    Route::get('/kvkk', [PageController::class, 'kvkk'])->middleware('cache.page:600')->name('page.kvkk');
    Route::get('/cerez-politikasi', [PageController::class, 'cookies'])->middleware('cache.page:600')->name('page.cookies');
    Route::get('/kategori/{slug}', [NewsController::class, 'category'])->middleware('cache.page:180')->name('news.category');
    Route::get('/iller', [CityController::class, 'index'])->middleware('cache.page:300')->name('city.index');
    Route::get('/il/{slug}', [CityController::class, 'show'])->middleware('cache.page:180')->name('city.show');
    Route::get('/etiket/{slug}', [NewsController::class, 'tag'])->middleware('cache.page:180')->name('news.tag');
    Route::get('/sayfa/{slug}', [PageController::class, 'show'])->middleware('cache.page:600')->name('page.show');
    Route::get('/{slug}', [NewsController::class, 'show'])->name('news.show');
});
