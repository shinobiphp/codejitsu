<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Neuron;

use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\OpenAI\OpenAI;
use RuntimeException;

final class ProviderFactory
{
    public function make(array $configuration, ?string $model): AIProviderInterface
    {
        $name = strtolower(trim((string) ($configuration['name'] ?? '')));
        if ($name !== 'openai') throw new RuntimeException(sprintf('AI provider [%s] is not supported.', $name));
        if (!is_string($model) || trim($model) === '') throw new RuntimeException('AI provider model is required.');
        $keyEnv = $configuration['keyEnv'] ?? null;
        if (!is_string($keyEnv) || preg_match('/^[A-Z][A-Z0-9_]*$/', $keyEnv) !== 1) throw new RuntimeException('AI provider keyEnv reference is invalid.');
        $key = getenv($keyEnv);
        if (!is_string($key) || $key === '') throw new RuntimeException(sprintf('AI provider environment variable [%s] is missing.', $keyEnv));
        return new OpenAI($key, trim($model));
    }
}
