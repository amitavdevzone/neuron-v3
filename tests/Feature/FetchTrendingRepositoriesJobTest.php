<?php

use App\Actions\UpsertDailyTrending;
use App\Actions\UpsertTrendingRepositories;
use App\Jobs\FetchTrendingRepositoriesJob;
use App\Models\DailyTrending;
use App\Models\TrendingRepository;
use App\Services\GitHubService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\artisan;
use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

it('upserts repositories', function () {
    mock(GitHubService::class)
        ->shouldReceive('fetchTrendingRepositories')
        ->once()
        ->andReturn([
            [
                'github_id' => 123,
                'name' => 'repo',
                'full_name' => 'octocat/repo',
                'owner' => 'octocat',
                'description' => 'Example',
                'language' => 'PHP',
                'stars_count' => 10,
                'forks_count' => 2,
                'open_issues_count' => 1,
                'html_url' => 'https://github.com/octocat/repo',
                'github_created_at' => now()->subDay()->toISOString(),
                'fetched_at' => now()->toISOString(),
            ],
        ]);

    (new FetchTrendingRepositoriesJob)->handle(
        app(GitHubService::class),
        app(UpsertTrendingRepositories::class),
        app(UpsertDailyTrending::class),
    );

    expect(TrendingRepository::query()->count())->toBe(1);
    expect(DailyTrending::query()->count())->toBe(1);
});

it('does not duplicate on rerun and updates stars', function () {
    TrendingRepository::factory()->create([
        'github_id' => 123,
        'full_name' => 'octocat/repo',
        'stars_count' => 10,
    ]);

    mock(GitHubService::class)
        ->shouldReceive('fetchTrendingRepositories')
        ->once()
        ->andReturn([
            [
                'github_id' => 123,
                'name' => 'repo',
                'full_name' => 'octocat/repo',
                'owner' => 'octocat',
                'description' => 'Example',
                'language' => 'PHP',
                'stars_count' => 99,
                'forks_count' => 2,
                'open_issues_count' => 1,
                'html_url' => 'https://github.com/octocat/repo',
                'github_created_at' => now()->subDay()->toISOString(),
                'fetched_at' => now()->toISOString(),
            ],
        ]);

    (new FetchTrendingRepositoriesJob)->handle(
        app(GitHubService::class),
        app(UpsertTrendingRepositories::class),
        app(UpsertDailyTrending::class),
    );

    expect(TrendingRepository::query()->count())->toBe(1)
        ->and(TrendingRepository::query()->first()->stars_count)->toBe(99);
});

it('handles empty response gracefully', function () {
    mock(GitHubService::class)
        ->shouldReceive('fetchTrendingRepositories')
        ->once()
        ->andReturn([]);

    (new FetchTrendingRepositoriesJob)->handle(
        app(GitHubService::class),
        app(UpsertTrendingRepositories::class),
        app(UpsertDailyTrending::class),
    );

    expect(TrendingRepository::query()->count())->toBe(0);
    expect(DailyTrending::query()->count())->toBe(0);
});

it('does not duplicate daily_trending on rerun for same day', function () {
    $payload = [
        [
            'github_id' => 123,
            'name' => 'repo',
            'full_name' => 'octocat/repo',
            'owner' => 'octocat',
            'description' => 'Example',
            'language' => 'PHP',
            'stars_count' => 10,
            'forks_count' => 2,
            'open_issues_count' => 1,
            'html_url' => 'https://github.com/octocat/repo',
            'github_created_at' => now()->subDay()->toISOString(),
            'fetched_at' => now()->toISOString(),
        ],
    ];

    mock(GitHubService::class)
        ->shouldReceive('fetchTrendingRepositories')
        ->once()
        ->andReturn($payload);

    (new FetchTrendingRepositoriesJob)->handle(
        app(GitHubService::class),
        app(UpsertTrendingRepositories::class),
        app(UpsertDailyTrending::class),
    );

    mock(GitHubService::class)
        ->shouldReceive('fetchTrendingRepositories')
        ->once()
        ->andReturn($payload);

    (new FetchTrendingRepositoriesJob)->handle(
        app(GitHubService::class),
        app(UpsertTrendingRepositories::class),
        app(UpsertDailyTrending::class),
    );

    expect(DailyTrending::query()->count())->toBe(1);
});

it('command dispatches the job', function () {
    Queue::fake();

    artisan('github:fetch-trending')->assertExitCode(0);

    Queue::assertPushed(FetchTrendingRepositoriesJob::class);
});
