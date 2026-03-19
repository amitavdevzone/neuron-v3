<?php

namespace App\Jobs;

use App\Actions\UpsertDailyTrending;
use App\Actions\UpsertTrendingRepositories;
use App\Services\GitHubService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchTrendingRepositoriesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 120;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(
        GitHubService $gitHubService,
        UpsertTrendingRepositories $upsertTrendingRepositories,
        UpsertDailyTrending $upsertDailyTrending
    ): void {
        $repositories = $gitHubService->fetchTrendingRepositories();

        $upsertTrendingRepositories->handle($repositories);
        $upsertDailyTrending->handle($repositories);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to fetch trending repositories.', [
            'exception' => $exception,
        ]);
    }
}
