<?php

use App\Models\DailyTrending;
use App\Models\TrendingRepository;
use App\Neuron\Tools\FetchTrendingRepositoriesTool;
use App\Neuron\TrendingAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;

uses(RefreshDatabase::class);

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

    $tool = new FetchTrendingRepositoriesTool;
    $tool->setInputs(['date' => '2026-03-19']);
    $tool->execute();

    $result = json_decode($tool->getResult(), true);

    expect($result)->toHaveCount(1)
        ->and($result[0]['full_name'])->toBe('octocat/my-repo')
        ->and($result[0]['owner'])->toBe('octocat')
        ->and($result[0]['html_url'])->toBe('https://github.com/octocat/my-repo');
});

it('returns an empty array when no repositories are trending on the given date', function () {
    $tool = new FetchTrendingRepositoriesTool;
    $tool->setInputs(['date' => '2026-03-19']);
    $tool->execute();

    expect(json_decode($tool->getResult(), true))->toBeEmpty();
});

it('limits results to 3 repositories', function () {
    collect(range(1, 7))->each(function (int $i): void {
        $fullName = "octocat/repo-{$i}";
        TrendingRepository::factory()->create(['full_name' => $fullName]);
        DailyTrending::factory()->create(['full_name' => $fullName, 'trending_date' => '2026-03-19']);
    });

    $tool = new FetchTrendingRepositoriesTool;
    $tool->setInputs(['date' => '2026-03-19']);
    $tool->execute();

    expect(json_decode($tool->getResult(), true))->toHaveCount(3);
});

it('agent calls the tool and returns a summary', function () {
    TrendingRepository::factory()->create(['full_name' => 'octocat/my-repo']);
    DailyTrending::factory()->create(['full_name' => 'octocat/my-repo', 'trending_date' => '2026-03-19']);

    $fetchTool = new FetchTrendingRepositoriesTool;

    $provider = new FakeAIProvider(
        new ToolCallMessage(null, [
            (clone $fetchTool)->setCallId('call_1')->setInputs(['date' => '2026-03-19']),
        ]),
        new AssistantMessage('Repo name: my-repo\nOwner: octocat\nGithub repo: https://github.com/octocat/my-repo\nSummary: A great repo.')
    );

    $message = TrendingAgent::make()
        ->setAiProvider($provider)
        ->chat(new UserMessage('Give me a summary of trending repos for 2026-03-19.'))
        ->getMessage();

    expect($message->getContent())->toContain('my-repo');
    $provider->assertCallCount(2);
});
