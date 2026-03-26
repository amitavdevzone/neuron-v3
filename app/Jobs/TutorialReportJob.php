<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class TutorialReportJob implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue('reports');
    }

    public function handle(): void
    {
        sleep(8);
        Log::info('Log from job TutorialReport');
    }
}
