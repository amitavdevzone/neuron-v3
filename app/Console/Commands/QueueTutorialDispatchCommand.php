<?php

namespace App\Console\Commands;

use App\Jobs\TutorialAnalyticsJob;
use App\Jobs\TutorialEmailJob;
use App\Jobs\TutorialReportJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Demo workers (`--queue=` order controls priority):
 *
 * php artisan queue:work database --queue=analytics,email,reports
 * php artisan queue:work database --queue=analytics,email
 *
 * Reports are dispatched first so workers can be busy on long jobs; UX jobs
 * follow after a short pause so the log shows whether a UX-only worker picks
 * them up immediately.
 */
#[Signature('queue:tutorial-dispatch')]
#[Description('Tutorial demo: 2 reports first, then 3 analytics + 3 email (see log phases)')]
class QueueTutorialDispatchCommand extends Command
{
    public function handle(): int
    {
        TutorialReportJob::dispatch();
        TutorialReportJob::dispatch();
        Log::info('--- Phase 1: Dispatched 2 TutorialReport jobs ---');

        sleep(2);

        for ($i = 0; $i < 2; $i++) {
            TutorialAnalyticsJob::dispatch();
        }
        for ($i = 0; $i < 2; $i++) {
            TutorialEmailJob::dispatch();
        }
        Log::info('--- Phase 2: Dispatched 2 TutorialAnalytics + 2 TutorialEmail jobs ---');

        Log::info('--- Phase 3: And some extra jobs ---');
        TutorialReportJob::dispatch();
        TutorialAnalyticsJob::dispatch();
        TutorialEmailJob::dispatch();

        $this->info('Phase 1: 2 reports. Phase 2 (after 2s pause): 3 analytics + 3 email. Check storage/logs/laravel.log for markers.');

        return self::SUCCESS;
    }
}
