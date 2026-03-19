---
name: git-trending-repo
overview: Implement a daily scheduled + queued job that fetches “trending” GitHub repos via Search API and upserts them into a new `trending_repositories` table, with a console command and Pest coverage.
todos:
  - id: schema
    content: Create migration for `trending_repositories` and run migrations in tests.
    status: completed
  - id: model-factory
    content: Create `TrendingRepository` model (fillable attribute style + casts) and factory.
    status: completed
  - id: github-service
    content: Create `GitHubService` that fetches and normalizes Search API results using config + retry.
    status: completed
  - id: job
    content: Create `FetchTrendingRepositoriesJob` that calls service and upserts by `github_id` with retries/backoff + logging on failure.
    status: completed
  - id: command
    content: Create `github:fetch-trending` command that dispatches the job and returns SUCCESS.
    status: completed
  - id: schedule-config
    content: Wire config (`config/services.php` + `.env.example`) and add daily scheduler entry in `routes/console.php`.
    status: completed
  - id: tests
    content: Add Pest feature tests for job upsert/idempotency, empty response, and command dispatch.
    status: completed
  - id: verify
    content: Run targeted tests and Pint (`vendor/bin/pint --dirty --format agent`).
    status: completed
isProject: false
---

## Scope

- Create persistence for trending repositories (`trending_repositories` table + `TrendingRepository` model + factory).
- Implement `GitHubService` to call GitHub Search API and return normalized arrays.
- Implement `FetchTrendingRepositoriesJob` that upserts results (idempotent).
- Implement `github:fetch-trending` command that dispatches the job.
- Schedule the job daily at 02:00 with overlap protection.
- Add configuration + env example entries for GitHub token and tuning.
- Add Pest tests covering job upsert/idempotency and command dispatch.

## Implementation details (key decisions)

- **Trending approximation**: Use GitHub Search API `created:>{date}` sorted by stars (`/search/repositories?q=created:>{date}&sort=stars&order=desc`).
- **Idempotency**: Use `TrendingRepository::upsert($repos, ['github_id'], [...])` so re-runs update star counts etc. without duplicates.
- **HTTP resilience**: Use `Http::retry(3, 2000)` and set `Authorization: Bearer` header only if a token is configured.
- **Queue**: Command dispatches the job; scheduler schedules the job; database queue driver already exists per plan context.

## Files to create

- Migration: `database/migrations/*_create_trending_repositories_table.php`
- Model: `app/Models/TrendingRepository.php`
- Factory: `database/factories/TrendingRepositoryFactory.php`
- Service: `app/Services/GitHubService.php`
- Job: `app/Jobs/FetchTrendingRepositoriesJob.php`
- Command: `app/Console/Commands/FetchTrendingRepositoriesCommand.php` (signature `github:fetch-trending`)
- Test: `tests/Feature/FetchTrendingRepositoriesJobTest.php` (Pest)

## Files to modify

- `config/services.php` add `github` block:
  - `token` from `GITHUB_TOKEN`
  - `trending_days` default 7
  - `per_page` default 30
- `.env.example` add `GITHUB_TOKEN=`, `GITHUB_TRENDING_DAYS=7`, `GITHUB_TRENDING_PER_PAGE=30`
- `routes/console.php` schedule:
  - `Schedule::job(new FetchTrendingRepositoriesJob)->daily()->at('02:00')->withoutOverlapping();`

## Test plan (minimum)

- Run the new Pest test file:
  - `php artisan test --compact --filter=FetchTrendingRepositoriesJobTest`
- Coverage to include:
  - job upserts repos (count increments)
  - job re-run doesn’t create duplicates and updates stars
  - command dispatches job (`Queue::fake()` + `assertPushed`)
  - empty service response handled gracefully

## Formatting

- After PHP edits, run:
  - `vendor/bin/pint --dirty --format agent`

## Notes / assumptions

- GitHub token is optional (improves rate limits). Without it, the job may hit unauthenticated limits depending on traffic.
- “Trending” is approximated (recently created + high stars), not GitHub’s web Trending page.

