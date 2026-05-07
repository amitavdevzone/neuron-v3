<?php

declare(strict_types=1);

namespace App\Neuron;

use App\Neuron\AIProviderFactory;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic;
use NeuronAI\Tools\ToolInterface;
use NeuronAI\Tools\Toolkits\ToolkitInterface;

class GeminiEmbed2 extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return AIProviderFactory::make(model: '');
    }

    protected function instructions(): string
    {
        return (string) new SystemPrompt(
            background: ["You are a friendly AI Agent created with Neuron AI framework."],
        );
    }

    /**
     * @return ToolInterface[]|ToolkitInterface[]
     */
    protected function tools(): array
    {
        return [];
    }

    /**
     * Attach middleware to nodes.
     */
    protected function middleware(): array
    {
        return [
            // ToolNode::class => [],
        ];
    }
}
