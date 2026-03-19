<?php

namespace App\Actions;

use App\Models\TrendingRepository;

class UpsertTrendingRepositories
{
    /**
     * @param  array<int, array{
     *   github_id: int,
     *   name: string,
     *   full_name: string,
     *   owner: string,
     *   description: string|null,
     *   language: string|null,
     *   stars_count: int,
     *   forks_count: int,
     *   open_issues_count: int,
     *   html_url: string,
     *   github_created_at: string,
     *   fetched_at: string
     * }>  $repositories
     */
    public function handle(array $repositories): void
    {
        if ($repositories === []) {
            return;
        }

        $now = now()->toISOString();

        $repositories = collect($repositories)
            ->map(fn (array $repository) => [
                ...$repository,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        TrendingRepository::upsert(
            $repositories,
            ['github_id'],
            [
                'name',
                'full_name',
                'owner',
                'description',
                'language',
                'stars_count',
                'forks_count',
                'open_issues_count',
                'html_url',
                'github_created_at',
                'fetched_at',
                'updated_at',
            ],
        );
    }
}
