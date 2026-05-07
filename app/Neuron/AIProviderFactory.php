<?php

declare(strict_types=1);

namespace App\Neuron;

use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\OpenAI\OpenAI;
use NeuronAI\Providers\OpenAILike;
use RuntimeException;

class AIProviderFactory
{
    public static function make(?string $model = null): AIProviderInterface
    {
        $provider = (string) config('services.ai.provider', 'openai');

        return match ($provider) {
            'openai' => new OpenAI(
                key: (string) config('services.openai.key'),
                model: $model ?? 'gpt-4o-mini',
            ),
            'openrouter' => new OpenAILike(
                baseUri: (string) config('services.openrouter.base_uri', 'https://openrouter.ai/api/v1'),
                key: (string) config('services.openrouter.key'),
                model: $model ?? 'openai/gpt-4o-mini',
            ),
            default => throw new RuntimeException("Unsupported AI provider [{$provider}] configured in services.ai.provider."),
        };
    }
}
