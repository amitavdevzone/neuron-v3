---
name: daily-trending-compare
overview: Add a dated snapshot table (`daily_trending`) and a DB-backed compare action that returns repos newly trending on a later date, while keeping `GitHubService` focused on GitHub API calls.
todos:
  - id: migration-daily-trending
    content: Create migration for `daily_trending` with unique(full_name,trending_date).
    status: completed
  - id: model-daily-trending
    content: Add `App\Models\DailyTrending` with fillable + date cast.
    status: completed
  - id: action-upsert-daily
    content: Add `App\Actions\UpsertDailyTrending` and implement idempotent upsert.
    status: completed
  - id: job-write-daily
    content: Update `FetchTrendingRepositoriesJob` to call `UpsertDailyTrending` after repo upsert.
    status: completed
  - id: action-compare
    content: Add `App\Actions\CompareDailyTrending` returning new `TrendingRepository` rows for date2 vs date1.
    status: completed
  - id: tests-compare
    content: Add Pest tests for compare action (new, none new, date1 empty => all new, date2 empty).
    status: completed
  - id: tests-job
    content: Update `FetchTrendingRepositoriesJobTest` to assert daily snapshot rows are written and non-duplicating.
    status: completed
  - id: format-and-run
    content: Run focused Pest tests and Pint formatting for modified PHP files.
    status: completed
isProject: false
---

## Goal

Persist a daily snapshot of trending repo `full_name`s and provide a compare operation that returns `TrendingRepository` rows that are newly trending on `$date2` vs `$date1`.

## Decisions locked in

- `compare($date1, $date2)` treats **missing `$date1` rows as “all on `$date2` are new”**.
- DB comparison logic lives in a **new action class**, not `GitHubService`.

## Current code touchpoints

- `FetchTrendingRepositoriesJob` currently only fetches + calls `UpsertTrendingRepositories`.

```30:35:/Users/amitavroy/code/tutorials/neuron-v3/app/Jobs/FetchTrendingRepositoriesJob.php
    public function handle(GitHubService $gitHubService, UpsertTrendingRepositories $upsertTrendingRepositories): void
    {
        $repositories = $gitHubService->fetchTrendingRepositories();

        $upsertTrendingRepositories->handle($repositories);
    }
```

## Implementation approach

### 1) Create `daily_trending` table

- Create migration `create_daily_trending_table` with:
  - `full_name` (string)
  - `trending_date` (date)
  - timestamps
  - unique index on (`full_name`, `trending_date`)

### 2) Add `DailyTrending` model

- `app/Models/DailyTrending.php`
  - `#[Fillable(['full_name','trending_date'])]`
  - cast `trending_date` to `date`

### 3) Add action to persist daily snapshot

- New action `app/Actions/UpsertDailyTrending.php`
  - `handle(array $repositories, ?string $trendingDate = null): void`
  - If `$repositories` is empty: return
  - Build rows from `full_name` + `trending_date` (default `now()->toDateString()`)
  - Use `DailyTrending::upsert(..., uniqueBy: ['full_name','trending_date'], update: ['updated_at'])`

### 4) Update job to write both tables

- Update `app/Jobs/FetchTrendingRepositoriesJob.php` to inject and call `UpsertDailyTrending` after `UpsertTrendingRepositories`.
- Keep the job thin and consistent with the existing action-driven persistence style.

### 5) Add compare action (DB diff)

- New action `app/Actions/CompareDailyTrending.php`
  - `handle(string $date1, string $date2): \Illuminate\Support\Collection`
  - Steps:
    - `date1Names = DailyTrending where trending_date=date1 pluck full_name`
    - `newNames = DailyTrending where trending_date=date2 whereNotIn full_name,date1Names pluck full_name`
    - Return `TrendingRepository::whereIn('full_name', newNames)->get()` (or empty `collect()` when none)
  - This preserves the existing plan’s behavior while keeping `GitHubService` API-only.

### 6) Tests (Pest)

- Add `tests/Feature/CompareDailyTrendingTest.php` (or `.../CompareDailyTrendingActionTest.php`), covering:
  - **returns new repos** (A on date1; A+B on date2; returns B)
  - **returns empty when nothing new**
  - **date1 empty => returns all date2** (your chosen behavior)
  - **date2 empty => empty**
- Update existing `tests/Feature/FetchTrendingRepositoriesJobTest.php` to assert `daily_trending` is written on job run and is re-runnable (no duplicates for same day).

## Verification commands

- Run migrations: `php artisan migrate`
- Run tests (focused): `php artisan test --compact --filter=CompareDailyTrending`
- Run formatting after PHP edits: `vendor/bin/pint --dirty --format agent`

