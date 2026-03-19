<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GitHubService
{
    /**
     * @return array<int, array{
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
     * }>
     */
    public function fetchTrendingRepositories(): array
    {
        $config = $this->config();

        $createdAfter = now()->subDays($config['trending_days'])->toDateString();
        $query = 'created:>'.$createdAfter;

        $request = Http::retry(3, 2000)
            ->acceptJson()
            ->baseUrl('https://api.github.com')
            ->withHeaders([
                'User-Agent' => (string) config('app.name', 'Laravel'),
            ]);

        if ($config['token'] !== '') {
            $request = $request->withToken($config['token']);
        }

        $response = $request->get('/search/repositories', [
            'q' => $query,
            'sort' => 'stars',
            'order' => 'desc',
            'per_page' => $config['per_page'],
        ])->throw();

        $items = $response->json('items', []);
        if (! is_array($items)) {
            return [];
        }

        $fetchedAt = now()->toISOString();

        return collect($items)
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item) use ($fetchedAt): array {
                $ownerLogin = data_get($item, 'owner.login');

                return [
                    'github_id' => (int) data_get($item, 'id'),
                    'name' => (string) data_get($item, 'name', ''),
                    'full_name' => (string) data_get($item, 'full_name', ''),
                    'owner' => is_string($ownerLogin) ? $ownerLogin : '',
                    'description' => $this->nullableString(data_get($item, 'description')),
                    'language' => $this->nullableString(data_get($item, 'language')),
                    'stars_count' => (int) data_get($item, 'stargazers_count', 0),
                    'forks_count' => (int) data_get($item, 'forks_count', 0),
                    'open_issues_count' => (int) data_get($item, 'open_issues_count', 0),
                    'html_url' => (string) data_get($item, 'html_url', ''),
                    'github_created_at' => (string) data_get($item, 'created_at', now()->toISOString()),
                    'fetched_at' => $fetchedAt,
                ];
            })
            ->filter(fn (array $repo) => $repo['github_id'] > 0 && $repo['full_name'] !== '' && Str::contains($repo['html_url'], 'http'))
            ->values()
            ->all();
    }

    /**
     * @return array{token: string, trending_days: int, per_page: int}
     */
    private function config(): array
    {
        return [
            'token' => (string) config('services.github.token', ''),
            'trending_days' => (int) config('services.github.trending_days', 7),
            'per_page' => (int) config('services.github.per_page', 30),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
