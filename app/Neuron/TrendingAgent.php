<?php

namespace App\Neuron;

use App\Neuron\Tools\FetchTrendingRepositoriesTool;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;

class TrendingAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return AIProviderFactory::make();
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
            new FetchTrendingRepositoriesTool,
        ];
    }
}
