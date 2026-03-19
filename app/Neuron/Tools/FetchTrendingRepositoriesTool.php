<?php

namespace App\Neuron\Tools;

use App\Actions\FetchTrendingRepositoriesForDate;
use Illuminate\Support\Facades\Log;
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
        Log::channel('ai')->info('Tool called: fetch_trending_repositories', ['date' => $date]);

        $results = app(FetchTrendingRepositoriesForDate::class)
            ->handle($date);

        Log::channel('ai')->info('Tool result: fetch_trending_repositories', ['repositories' => $results]);

        return $results;
    }
}
