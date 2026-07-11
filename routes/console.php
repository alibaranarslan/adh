<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Schedule::useCache(env('SCHEDULE_CACHE_STORE', 'file'));

$onScheduleFailure = function (\Illuminate\Console\Scheduling\ScheduledTaskFailed $event): void {
    $command = $event->task->command ?? $event->task->description;
    Log::channel('daily')->error('[SCHEDULER_FAILURE] Scheduled task failed', [
        'command'   => $command,
        'exception' => $event->exception->getMessage(),
    ]);
    if (app()->bound('sentry')) {
        \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($command, $event): void {
            $scope->setTag('scheduler.command', $command);
            \Sentry\captureException($event->exception);
        });
    }
};

Schedule::command('iha:sync --inline --limit=50')->cron('*/10 * * * *')->withoutOverlapping(9)
    ->onFailure($onScheduleFailure);

if (filter_var(env('SCHEDULE_QUEUE_WORKER', true), FILTER_VALIDATE_BOOL)) {
    Schedule::command('queue:work database --queue=default,analytics,instagram --sleep=1 --tries=3 --max-time=50 --stop-when-empty')
        ->everyMinute()
        ->withoutOverlapping(5)
        ->onFailure($onScheduleFailure);
}

Schedule::command('iha:monitor-forward --limit=20')->everyTenMinutes()
    ->onFailure($onScheduleFailure);

Schedule::command('adh:security-ingest-audit --freshness-minutes=60')->everyThirtyMinutes()
    ->when(fn () => app()->environment('production'))
    ->onFailure($onScheduleFailure);

Schedule::command('iha:refresh-images')->cron('5,20,35,50 * * * *')
    ->onFailure($onScheduleFailure);

Schedule::command('news:archive')->dailyAt('03:00')
    ->onFailure($onScheduleFailure);

Schedule::command('pharmacy:refresh')->dailyAt('00:05')
    ->onFailure($onScheduleFailure);

Schedule::command('prayer:refresh')->dailyAt('00:10')
    ->onFailure($onScheduleFailure);

Schedule::command('weather:refresh')->everyThirtyMinutes()
    ->onFailure($onScheduleFailure);

Schedule::command('editorial:recalculate')->hourly()
    ->onFailure($onScheduleFailure);

Schedule::command('sitemap:generate')->everyThirtyMinutes()
    ->onFailure($onScheduleFailure);
