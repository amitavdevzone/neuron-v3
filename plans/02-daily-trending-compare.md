# Plan: daily_trending Table & GitHubService::compare()

## Context
The `trending_repositories` table stores full repo data but has no date dimension — it's always overwritten with the latest snapshot. A new `daily_trending` table records which `full_name` values were trending on each specific date. This enables the `compare()` method to diff two dates and return only the repos that are newly trending on the second date.

---

## Files to Create

| File | Command |
|------|---------|
| Migration | `php artisan make:migration create_daily_trending_table --no-interaction` |
| Model | `php artisan make:model DailyTrending --no-interaction` |
| Test | `php artisan make:test GitHubServiceCompareTest --pest --no-interaction` |

## Files to Modify

- `app/Services/GitHubService.php` — add `compare()` method
- `app/Jobs/FetchTrendingRepositoriesJob.php` — also write to `daily_trending` after upserting repos

---

## Implementation Details

### 1. Migration — `daily_trending` table

```php
Schema::create('daily_trending', function (Blueprint $table) {
    $table->id();
    $table->string('full_name');
    $table->date('trending_date');
    $table->timestamps();

    $table->unique(['full_name', 'trending_date']);
});
```

The unique constraint on `[full_name, trending_date]` makes the job safely re-runnable via `upsert()`.

### 2. Model — `DailyTrending`

```php
#[Fillable(['full_name', 'trending_date'])]
class DailyTrending extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['trending_date' => 'date'];
    }
}
```

### 3. Job — update `FetchTrendingRepositoriesJob`

After the existing `TrendingRepository::upsert(...)` call, add:

```php
$dailyRecords = collect($repositories)
    ->map(fn (array $repo) => [
        'full_name'     => $repo['full_name'],
        'trending_date' => now()->toDateString(),
        'created_at'    => now(),
        'updated_at'    => now(),
    ])
    ->all();

DailyTrending::upsert(
    $dailyRecords,
    uniqueBy: ['full_name', 'trending_date'],
    update: ['updated_at'],
);
```

### 4. Service — `compare(string $date1, string $date2): Collection`

Add to `GitHubService`:

```php
/**
 * Returns TrendingRepository rows that are new on $date2 compared to $date1.
 *
 * @return \Illuminate\Support\Collection<int, \App\Models\TrendingRepository>
 */
public function compare(string $date1, string $date2): Collection
{
    $fullNamesOnDate1 = DailyTrending::query()
        ->where('trending_date', $date1)
        ->pluck('full_name');

    $newFullNames = DailyTrending::query()
        ->where('trending_date', $date2)
        ->whereNotIn('full_name', $fullNamesOnDate1)
        ->pluck('full_name');

    if ($newFullNames->isEmpty()) {
        return collect();
    }

    return TrendingRepository::query()
        ->whereIn('full_name', $newFullNames)
        ->get();
}
```

**Flow:**
1. Fetch all `full_name` values for `$date1`
2. Fetch `full_name` values for `$date2` that are NOT in the `$date1` set
3. Pull the full rows from `trending_repositories` for those new names
4. Return the collection (empty collection if nothing is new)

---

## Tests — `GitHubServiceCompareTest`

1. **Returns new repos** — seed `daily_trending` with date1 having repo A, date2 having repos A + B; seed `trending_repositories` with A and B; assert `compare()` returns only B's full row
2. **Returns empty collection when nothing is new** — same repos on both dates; assert result is empty
3. **Returns empty collection when date1 has no data** — only date2 has data; all are considered new
4. **Returns empty collection when date2 has no data** — assert empty result, no errors

Run: `php artisan test --compact --filter=GitHubServiceCompareTest`

---

## Verification
1. `php artisan migrate`
2. Run `php artisan github:fetch-trending` + `php artisan queue:work --once` on two different days (or manually seed `daily_trending` with two dates)
3. Call `app(GitHubService::class)->compare('2026-03-18', '2026-03-19')` via tinker to verify the diff
4. Run tests
5. `vendor/bin/pint --dirty --format agent`
