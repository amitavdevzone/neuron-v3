<?php

namespace App\Console\Commands;

use App\Neuron\TrendingAgent;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
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

        Log::channel('ai')->info('Trending agent query', ['query' => $userInput]);

        $message = TrendingAgent::make()
            ->observe(new LogObserver(Log::channel('ai')))
            ->chat(new UserMessage($userInput))
            ->getMessage();

        $this->line($message->getContent() ?? 'No response received.');

        return self::SUCCESS;
    }
}
