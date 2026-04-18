<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class TutorialAnalyticsJob implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue('analytics');
    }

    public function handle(): void
    {
        sleep(3);
        Log::info('Log from job TutorialAnalytics');
    }
}
