<?php

use App\Neuron\AIProviderFactory;
use Illuminate\Support\Facades\Config;
use NeuronAI\Providers\OpenAI\OpenAI;
use NeuronAI\Providers\OpenAILike;

it('resolves openai provider by default', function (): void {
    Config::set('services.openai.key', 'test-openai-key');
    Config::set('services.openai.model', 'test-openai-model');
    Config::set('services.ai.provider', 'openai');

    $provider = AIProviderFactory::make();

    expect($provider)->toBeInstanceOf(OpenAI::class);
});

it('resolves openrouter provider when configured', function (): void {
    Config::set('services.openrouter.key', 'test-openrouter-key');
    Config::set('services.openrouter.model', 'test-openrouter-model');
    Config::set('services.openrouter.base_uri', 'https://openrouter.ai/api/v1');
    Config::set('services.ai.provider', 'openrouter');

    $provider = AIProviderFactory::make();

    expect($provider)->toBeInstanceOf(OpenAILike::class);
});

it('throws when provider is unsupported', function (): void {
    Config::set('services.ai.provider', 'invalid-provider');

    AIProviderFactory::make();
})->throws(RuntimeException::class);
