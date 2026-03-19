<?php

namespace App\Actions;

use App\Models\DailyTrending;
use App\Models\TrendingRepository;
use Illuminate\Support\Collection;

class CompareDailyTrending
{
    /**
     * Returns TrendingRepository rows that are new on $date2 compared to $date1.
     *
     * @return Collection<int, TrendingRepository>
     */
    public function handle(string $date1, string $date2): Collection
    {
        $fullNamesOnDate1 = DailyTrending::query()
            ->whereDate('trending_date', $date1)
            ->pluck('full_name');

        $newFullNames = DailyTrending::query()
            ->whereDate('trending_date', $date2)
            ->whereNotIn('full_name', $fullNamesOnDate1)
            ->pluck('full_name');

        if ($newFullNames->isEmpty()) {
            return collect();
        }

        return TrendingRepository::query()
            ->whereIn('full_name', $newFullNames)
            ->get();
    }
}
