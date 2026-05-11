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

Schedule::command('iha:sync')->cron('*/15 * * * *')->withoutOverlapping()
    ->onFailure($onScheduleFailure);

Schedule::command('iha:monitor-forward --limit=20')->hourly()
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

Schedule::command('sitemap:generate')->dailyAt('04:00')
    ->onFailure($onScheduleFailure);
