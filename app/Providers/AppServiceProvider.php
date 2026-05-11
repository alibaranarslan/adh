<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\LocalInfoEntry;
use App\Models\NewsArticle;
use App\Models\Setting;
use App\Models\User;
use App\Observers\NewsArticleObserver;
use App\Services\PharmacyService;
use App\Services\PrayerTimesService;
use App\Services\WeatherService;
use App\Support\DynamicMailConfig;
use App\Support\HeaderThemeResolver;
use App\Support\LocalizedSettings;
use App\Support\SiteBranding;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Spatie\Translatable\Facades\Translatable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Fabricator page routing is managed manually in this project.
        config()->set('filament-fabricator.routing.enabled', false);
        config()->set('filament-fabricator.hook-to-commands', false);
    }

    public function boot(): void
    {
        Translatable::fallback('tr', true);

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));
        RateLimiter::for('newsletter', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        if (! $this->app->runningUnitTests()) {
            DynamicMailConfig::apply();
        }

        // Register model observers
        NewsArticle::observe(NewsArticleObserver::class);

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            }
        });

        View::composer('*', function ($view): void {
            $view->with('navCategories', once(fn () => $this->safeRemember(
                'nav_categories',
                300,
                fn () => Category::active()
                    ->roots()
                    ->orderBy('sort_order')
                    ->get(),
                collect()
            )));

            $view->with('siteSettings', $this->safeRemember(
                'site_settings_'.app()->getLocale(),
                3600,
                fn () => Setting::query()
                    ->pluck('value', 'key')
                    ->map(function ($value, $key) {
                        return in_array($key, ['site_name', 'site_tagline', 'address'], true)
                            ? LocalizedSettings::resolveText($value, '')
                            : $value;
                    }),
                collect()
            ));

            $view->with('siteBranding', $this->safeRemember(
                'site_branding_'.app()->getLocale(),
                3600,
                fn () => SiteBranding::current(),
                []
            ));

            $view->with('activeHeaderTheme', app(HeaderThemeResolver::class)->resolve());
            $view->with('breakingNews', once(fn () => $this->safeRemember('breaking_news', 60, function () {
                $breaking = NewsArticle::published()
                    ->where('is_breaking', true)
                    ->latest('published_at')
                    ->take(10)
                    ->get(['id', 'title', 'slug']);

                if ($breaking->isEmpty()) {
                    $breaking = NewsArticle::published()
                        ->orderByRaw('editorial_score DESC, published_at DESC')
                        ->take(10)
                        ->get(['id', 'title', 'slug']);
                }

                return $breaking;
            }, collect())));

            $view->with('mostRead', once(fn () => $this->safeRemember('most_read_sidebar', 300, function () {
                $pool = NewsArticle::published()
                    ->with('category')
                    ->where('published_at', '>=', now()->subDays(7))
                    ->take(50)
                    ->get();

                if ($pool->isEmpty()) {
                    $pool = NewsArticle::published()
                        ->with('category')
                        ->latest('published_at')
                        ->take(20)
                        ->get();
                }

                return $pool->sortByDesc('most_read_score')->take(5)->values();
            }, collect())));

            $view->with('weather', once(fn () => $this->safeExternalData(fn () => WeatherService::get(), [
                'temp' => '--',
                'description' => '',
                'icon' => '',
                'feels_like' => '--',
                'humidity' => '--',
                'wind' => '--',
            ])));

            $view->with('prayerTimes', once(fn () => $this->safeExternalData(fn () => PrayerTimesService::get(), [])));
            $view->with('pharmacies', once(fn () => $this->safeExternalData(fn () => app(PharmacyService::class)->getTodayPharmacies(), [])));
            $view->with('localAnnouncements', once(fn () => $this->safeRemember(
                'local_announcements',
                300,
                fn () => LocalInfoEntry::current()->orderByDesc('created_at')->take(5)->get(),
                collect()
            )));
        });

        URL::defaults(['locale' => app()->getLocale()]);

        Queue::failing(function (JobFailed $event) {
            Log::channel('daily')->error('[QUEUE_FAILURE] Job failed', [
                'job'        => $event->job->resolveName(),
                'connection' => $event->connectionName,
                'queue'      => $event->job->getQueue(),
                'exception'  => $event->exception->getMessage(),
            ]);

            if (app()->bound('sentry')) {
                \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($event): void {
                    $scope->setTag('queue.job', $event->job->resolveName());
                    $scope->setTag('queue.connection', $event->connectionName);
                    \Sentry\captureException($event->exception);
                });
            }
        });

        if (! $this->app->runningUnitTests()) {
            Queue::before(function (): void {
                DynamicMailConfig::apply();
            });
        }
    }

    private function safeRemember(string $key, int $ttl, callable $resolver, mixed $fallback): mixed
    {
        if ($this->app->runningUnitTests()) {
            try {
                return $resolver();
            } catch (\Throwable $e) {
                Log::warning('View share fallback used', [
                    'key' => $key,
                    'message' => $e->getMessage(),
                ]);

                return $fallback;
            }
        }

        try {
            return Cache::remember($key, $ttl, $resolver);
        } catch (\Throwable $e) {
            Log::warning('View share fallback used', [
                'key' => $key,
                'message' => $e->getMessage(),
            ]);

            return $fallback;
        }
    }

    private function safeExternalData(callable $resolver, mixed $fallback): mixed
    {
        if ($this->app->runningUnitTests()) {
            return $fallback;
        }

        try {
            return $resolver();
        } catch (\Throwable $e) {
            Log::warning('External shared data fallback used', [
                'message' => $e->getMessage(),
            ]);

            return $fallback;
        }
    }
}
