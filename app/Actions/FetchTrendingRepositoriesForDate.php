<?php

namespace App\Actions;

use App\Models\DailyTrending;
use App\Models\TrendingRepository;

class FetchTrendingRepositoriesForDate
{
    /**
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     full_name: string,
     *     owner: string,
     *     description: ?string,
     *     language: ?string,
     *     stars_count: int,
     *     html_url: string
     * }>
     */
    public function handle(string $date): array
    {
        $fullNames = DailyTrending::query()
            ->whereDate('trending_date', $date)
            ->orderBy('id')
            ->limit(5)
            ->pluck('full_name');

        $repositoriesByFullName = TrendingRepository::query()
            ->whereIn('full_name', $fullNames)
            ->get()
            ->keyBy('full_name');

        return $fullNames
            ->map(fn (string $fullName): ?TrendingRepository => $repositoriesByFullName->get($fullName))
            ->filter()
            ->take(3)
            ->map(fn (TrendingRepository $repo): array => [
                'id' => $repo->id,
                'name' => $repo->name,
                'full_name' => $repo->full_name,
                'owner' => $repo->owner,
                'description' => $repo->description,
                'language' => $repo->language,
                'stars_count' => $repo->stars_count,
                'html_url' => $repo->html_url,
            ])
            ->values()
            ->all();
    }
}
