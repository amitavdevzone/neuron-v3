<?php

namespace App\Actions;

use App\Models\DailyTrending;

class UpsertDailyTrending
{
    /**
     * @param  array<int, array{full_name: string}>  $repositories
     */
    public function handle(array $repositories, ?string $trendingDate = null): void
    {
        if ($repositories === []) {
            return;
        }

        $date = $trendingDate ?? now()->toDateString();
        $now = now()->toISOString();

        $dailyRecords = collect($repositories)
            ->map(fn (array $repo) => [
                'full_name' => (string) ($repo['full_name'] ?? ''),
                'trending_date' => $date,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->filter(fn (array $record) => $record['full_name'] !== '')
            ->values()
            ->all();

        if ($dailyRecords === []) {
            return;
        }

        DailyTrending::upsert(
            $dailyRecords,
            uniqueBy: ['full_name', 'trending_date'],
            update: ['updated_at'],
        );
    }
}
