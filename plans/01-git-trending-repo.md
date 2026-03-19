# Plan: Daily Trending GitHub Repositories Cron Job

## Context
The project needs a daily automated process to fetch trending GitHub repositories and persist them. GitHub has no official "trending" API, so we use the Search API (`/search/repositories?q=created:>{date}&sort=stars&order=desc`) to approximate trending by finding recently-created repos sorted by stars. The job runs daily via Laravel's scheduler, dispatching a queued job that uses the database queue driver (already configured).

---

## Files to Create

| File | Command |
|------|---------|
| Migration | `php artisan make:migration create_trending_repositories_table --no-interaction` |
| Model + Factory | `php artisan make:model TrendingRepository --factory --no-interaction` |
| Service | `php artisan make:class App/Services/GitHubService --no-interaction` |
| Job | `php artisan make:job FetchTrendingRepositoriesJob --no-interaction` |
| Command | `php artisan make:command FetchTrendingRepositoriesCommand --no-interaction` |
| Test | `php artisan make:test FetchTrendingRepositoriesJobTest --pest --no-interaction` |

## Files to Modify

- `config/services.php` — add `github` config block
- `.env.example` — document new env vars
- `routes/console.php` — add daily schedule

---

## Implementation Details

### 1. Migration — `trending_repositories` table
Columns:
- `id`, `timestamps()`
- `github_id` (unsignedBigInteger, unique) — upsert key
- `name` (string), `full_name` (string, unique), `owner` (string)
- `description` (text, nullable), `language` (string, nullable)
- `stars_count`, `forks_count`, `open_issues_count` (unsignedInteger)
- `html_url` (string)
- `github_created_at` (timestamp), `fetched_at` (timestamp)

### 2. Model — `TrendingRepository`
Use PHP 8 attribute style matching `User.php`:
```php
#[Fillable(['github_id', 'name', 'full_name', 'owner', 'description',
    'language', 'stars_count', 'forks_count', 'open_issues_count',
    'html_url', 'github_created_at', 'fetched_at'])]
class TrendingRepository extends Model
{
    use HasFactory;
    protected function casts(): array {
        return ['github_created_at' => 'datetime', 'fetched_at' => 'datetime'];
    }
}
```

### 3. Config — `config/services.php`
```php
'github' => [
    'token' => env('GITHUB_TOKEN'),
    'trending_days' => env('GITHUB_TRENDING_DAYS', 7),
    'per_page' => env('GITHUB_TRENDING_PER_PAGE', 30),
],
```
`.env.example` additions: `GITHUB_TOKEN=`, `GITHUB_TRENDING_DAYS=7`, `GITHUB_TRENDING_PER_PAGE=30`

### 4. Service — `GitHubService`
- No constructor parameters — reads config internally via a single private method
- `private function config(): array` — returns `['token' => config('services.github.token', ''), 'trending_days' => 7, 'per_page' => 30]`
- Method: `fetchTrendingRepositories(): array` — calls GitHub Search API using values from `$this->config()`, maps response to flat array matching DB columns
- Uses `Http::retry(3, 2000)` for resilience; adds `Authorization: Bearer` header when token is present
- Returns mapped array; knows nothing about the DB
- No `AppServiceProvider` binding needed — Laravel resolves it automatically

### 5. Job — `FetchTrendingRepositoriesJob`
- `public int $tries = 3; public int $backoff = 120;`
- `handle(GitHubService $gitHubService)` — calls service, then `TrendingRepository::upsert($repos, ['github_id'], [...all other fields])`
- `failed(\Throwable $e)` — logs error
- Idempotent: re-running updates stars/forks without creating duplicates

### 7. Command — `github:fetch-trending`
Dispatches `FetchTrendingRepositoriesJob::dispatch()` to queue and outputs success message. Returns `self::SUCCESS`.

### 8. Schedule — `routes/console.php`
```php
use Illuminate\Support\Facades\Schedule;
Schedule::job(new FetchTrendingRepositoriesJob)->daily()->at('02:00')->withoutOverlapping();
```

---

## Tests — `FetchTrendingRepositoriesJobTest`

1. **Job upserts repos** — mock `GitHubService`, run job, assert `TrendingRepository::count() === 1`
2. **No duplicates on re-run** — seed one record, re-run job with updated stars, assert count still 1 and stars updated
3. **Command dispatches job** — `Queue::fake()`, run `github:fetch-trending`, assert `Queue::assertPushed(FetchTrendingRepositoriesJob::class)`
4. **Empty response handled gracefully** — mock returns `[]`, assert no exception, count stays 0

Run: `php artisan test --compact --filter=FetchTrendingRepositoriesJobTest`

---

## Verification
1. `php artisan migrate` — apply schema
2. Add `GITHUB_TOKEN` to `.env` (optional but increases rate limit)
3. `php artisan github:fetch-trending` — dispatch job manually
4. `php artisan queue:work --once` — process the queued job
5. `php artisan tinker --execute "dump(App\Models\TrendingRepository::count())"` — verify records saved
6. Run tests to confirm all pass
7. `vendor/bin/pint --dirty --format agent` — fix code style
