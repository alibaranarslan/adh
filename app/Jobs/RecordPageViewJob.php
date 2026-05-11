<?php

namespace App\Jobs;

use App\Models\AnalyticsPageView;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordPageViewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(private array $data)
    {
        $this->onQueue('analytics');
    }

    public function handle(): void
    {
        AnalyticsPageView::create($this->data);
    }
}
