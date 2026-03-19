# Plan: NeuronAI Trending Repositories Agent

## Context
Build a NeuronAI agent backed by OpenAI that answers user questions about trending GitHub repositories. The agent has a tool that fetches the top 5 trending repos for a given date from the existing `daily_trending` and `trending_repositories` tables, then generates per-repo summaries in a structured format.

---

## Files to Create/Modify

| File | Action |
|------|--------|
| `config/services.php` | Add `openai.key` entry |
| `.env` + `.env.example` | Add `OPENAI_API_KEY` placeholder |
| `app/Neuron/Tools/FetchTrendingRepositoriesTool.php` | New — the data-fetching tool |
| `app/Neuron/TrendingAgent.php` | New — the NeuronAI agent |
| `app/Console/Commands/TrendingAgentCommand.php` | New — artisan command to interact |
| `tests/Feature/FetchTrendingRepositoriesToolTest.php` | New — Pest tests for tool + agent |

---

## Step 1 — `config/services.php`

Add to existing array:
```php
'openai' => [
    'key' => env('OPENAI_API_KEY'),
],
```

Add `OPENAI_API_KEY=` to `.env` (real key) and `.env.example` (empty placeholder).

---

## Step 2 — `app/Neuron/Tools/FetchTrendingRepositoriesTool.php`

```php
<?php

namespace App\Neuron\Tools;

use App\Models\DailyTrending;
use App\Models\TrendingRepository;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

class FetchTrendingRepositoriesTool extends Tool
{
    public function __construct()
    {
        parent::__construct(
            name: 'fetch_trending_repositories',
            description: 'Fetches the top 5 trending GitHub repositories for a given date.',
        );
    }

    protected function properties(): array
    {
        return [
            new ToolProperty(
                name: 'date',
                type: PropertyType::STRING,
                description: 'The date to fetch trending repositories for, in Y-m-d format.',
                required: true,
            ),
        ];
    }

    public function __invoke(string $date): array
    {
        $fullNames = DailyTrending::query()
            ->whereDate('trending_date', $date)
            ->limit(5)
            ->pluck('full_name');

        return TrendingRepository::query()
            ->whereIn('full_name', $fullNames)
            ->get()
            ->map(fn (TrendingRepository $repo): array => [
                'name' => $repo->name,
                'full_name' => $repo->full_name,
                'owner' => $repo->owner,
                'description' => $repo->description,
                'language' => $repo->language,
                'stars_count' => $repo->stars_count,
                'html_url' => $repo->html_url,
            ])
            ->all();
    }
}
```

**Notes:**
- Uses `new ToolProperty(...)` — matches NeuronAI docs pattern.
- `__invoke` returns an array; `Tool::setResult()` auto-JSON-encodes it.
- Constructor delegates to `parent::__construct()` with tool name and description.

---

## Step 3 — `app/Neuron/TrendingAgent.php`

```php
<?php

namespace App\Neuron;

use App\Neuron\Tools\FetchTrendingRepositoriesTool;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\OpenAI\OpenAI;

class TrendingAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return new OpenAI(
            key: (string) config('services.openai.key'),
            model: 'gpt-4o',
        );
    }

    protected function instructions(): string
    {
        $today = now()->toDateString();

        return (string) new SystemPrompt(
            background: [
                'You are a helpful assistant that provides summaries of trending GitHub repositories.',
                "Today's date is {$today}. Use this to resolve relative dates like \"today\" and \"yesterday\".",
            ],
            steps: [
                'Determine the date the user is asking about.',
                'Call the fetch_trending_repositories tool with that date in Y-m-d format.',
                'Write a summary for each repository returned.',
            ],
            output: [
                'Format each repository entry exactly as follows:',
                'Repo name: [name]',
                'Owner: [owner]',
                'Github repo: [html_url]',
                'Summary: [A concise summary based on the description, language, star count, and other available details]',
                'Separate each repository entry with a blank line.',
            ],
        );
    }

    protected function tools(): array
    {
        return [
            new FetchTrendingRepositoriesTool(),
        ];
    }
}
```

**Key NeuronAI patterns:**
- Tools registered via `protected function tools(): array` — NOT `addTool()` in constructor.
- `instructions()` uses `SystemPrompt` with `background`, `steps`, `output` arrays.
- Instantiated with `TrendingAgent::make()` (static constructor from `StaticConstructor` trait).

---

## Step 4 — `app/Console/Commands/TrendingAgentCommand.php`

Scaffold: `php artisan make:command TrendingAgentCommand --no-interaction`

```php
<?php

namespace App\Console\Commands;

use App\Neuron\TrendingAgent;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Observability\LogObserver;

#[Signature('app:trending-agent')]
#[Description('Ask the trending repositories AI agent a question')]
class TrendingAgentCommand extends Command
{
    public function handle(): int
    {
        $userInput = $this->ask('What would you like to know about trending repositories?');

        if (! is_string($userInput) || trim($userInput) === '') {
            $this->error('Please provide a valid question.');

            return self::FAILURE;
        }

        $message = TrendingAgent::make()
            ->observe(new LogObserver(logger()))
            ->chat(new UserMessage($userInput))
            ->getMessage();

        $this->line($message->getContent() ?? 'No response received.');

        return self::SUCCESS;
    }
}
```

---

## Step 5 — `tests/Feature/FetchTrendingRepositoriesToolTest.php`

Scaffold: `php artisan make:test --pest FetchTrendingRepositoriesToolTest --no-interaction`

```php
<?php

use App\Neuron\TrendingAgent;
use App\Models\DailyTrending;
use App\Models\TrendingRepository;
use App\Neuron\Tools\FetchTrendingRepositoriesTool;
use NeuronAI\Chat\Messages\Stream\AssistantMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;

// Tool tests — no OpenAI calls needed
it('returns repositories trending on a given date', function () {
    TrendingRepository::factory()->create([
        'full_name' => 'octocat/my-repo',
        'owner' => 'octocat',
        'html_url' => 'https://github.com/octocat/my-repo',
    ]);
    DailyTrending::factory()->create([
        'full_name' => 'octocat/my-repo',
        'trending_date' => '2026-03-19',
    ]);

    $tool = new FetchTrendingRepositoriesTool();
    $tool->setInputs(['date' => '2026-03-19']);
    $tool->execute();

    $result = json_decode($tool->getResult(), true);

    expect($result)->toHaveCount(1)
        ->and($result[0]['full_name'])->toBe('octocat/my-repo')
        ->and($result[0]['owner'])->toBe('octocat')
        ->and($result[0]['html_url'])->toBe('https://github.com/octocat/my-repo');
});

it('returns an empty array when no repositories are trending on the given date', function () {
    $tool = new FetchTrendingRepositoriesTool();
    $tool->setInputs(['date' => '2026-03-19']);
    $tool->execute();

    expect(json_decode($tool->getResult(), true))->toBeEmpty();
});

it('limits results to 5 repositories', function () {
    collect(range(1, 7))->each(function (int $i): void {
        $fullName = "octocat/repo-{$i}";
        TrendingRepository::factory()->create(['full_name' => $fullName]);
        DailyTrending::factory()->create(['full_name' => $fullName, 'trending_date' => '2026-03-19']);
    });

    $tool = new FetchTrendingRepositoriesTool();
    $tool->setInputs(['date' => '2026-03-19']);
    $tool->execute();

    expect(json_decode($tool->getResult(), true))->toHaveCount(5);
});

// Agent test — uses FakeAIProvider to avoid real OpenAI calls
it('agent calls the tool and returns a summary', function () {
    TrendingRepository::factory()->create(['full_name' => 'octocat/my-repo']);
    DailyTrending::factory()->create(['full_name' => 'octocat/my-repo', 'trending_date' => '2026-03-19']);

    $fetchTool = new FetchTrendingRepositoriesTool();

    $provider = new FakeAIProvider(
        // First call: model decides to call the tool
        new ToolCallMessage(null, [
            (clone $fetchTool)->setCallId('call_1')->setInputs(['date' => '2026-03-19']),
        ]),
        // Second call: model returns the formatted summary
        new AssistantMessage('Repo name: my-repo\nOwner: octocat\nGithub repo: https://github.com/octocat/my-repo\nSummary: A great repo.')
    );

    $message = TrendingAgent::make()
        ->setAiProvider($provider)
        ->chat(new UserMessage('Give me a summary of trending repos for 2026-03-19.'))
        ->getMessage();

    expect($message->getContent())->toContain('my-repo');
    $provider->assertCallCount(2);
});
```

---

## Step 6 — Post-implementation

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact --filter=FetchTrendingRepositoriesToolTest
```

---

## Verification

1. Tests pass (tool tests hit real DB, agent test uses `FakeAIProvider`)
2. Add real `OPENAI_API_KEY` to `.env`
3. Run `php artisan app:trending-agent` → ask "give me a summary of trending repos today"
4. Agent calls tool, returns formatted summaries per repo
