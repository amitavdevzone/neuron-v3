<?php

namespace App\Console\Commands;

use App\Jobs\FetchTrendingRepositoriesJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('github:fetch-trending')]
#[Description('Dispatch a job to fetch trending GitHub repositories')]
class FetchTrendingRepositoriesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        FetchTrendingRepositoriesJob::dispatch();

        $this->info('Trending repositories fetch job dispatched.');

        return self::SUCCESS;
    }
}
