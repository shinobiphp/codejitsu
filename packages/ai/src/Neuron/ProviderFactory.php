<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Neuron;

use Codejitsu\Ai\Definitions\ProviderDefinition;

use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\OpenAI\OpenAI;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\Providers\OpenAILike;
use RuntimeException;

final class ProviderFactory
{
    public function make(ProviderDefinition $configuration, ?string $model = null): AIProviderInterface
    {
        $model = is_string($model) && trim($model) !== '' ? trim($model) : $configuration->model;
        if ($configuration->adapter === 'ollama') {
            $url = $configuration->options['url'] ?? 'http://127.0.0.1:11434/api';
            $timeout = $configuration->options['timeout'] ?? 300.0;
            $parameters = $configuration->options['parameters'] ?? [];
            if (!is_string($url) || trim($url) === '') throw new RuntimeException('Ollama provider url must be a non-empty string.');
            if (!is_int($timeout) && !is_float($timeout) || $timeout <= 0) throw new RuntimeException('Ollama provider timeout must be a positive number.');
            if (!is_array($parameters)) throw new RuntimeException('Ollama provider parameters must be a map.');
            $provider = new Ollama(rtrim($url, '/'), $model, $parameters);
            $provider->getHttpClient()->withTimeout((float)$timeout);
            return $provider;
        }
        if (!in_array($configuration->adapter,['openai','groq'],true)) throw new RuntimeException(sprintf('AI provider adapter [%s] is not supported.', $configuration->adapter));
        $reference = $configuration->credentials['apiKey'] ?? null;
        if (!is_string($reference) || !str_starts_with($reference, 'env://')) throw new RuntimeException(sprintf('%s provider requires an apiKey credential reference.', ucfirst($configuration->adapter)));
        $keyEnv = substr($reference, 6);
        $key = $_ENV[$keyEnv] ?? getenv($keyEnv);
        if (!is_string($key) || $key === '') throw new RuntimeException(sprintf('AI provider environment variable [%s] is missing.', $keyEnv));
        if ($configuration->adapter === 'groq') {
            $parameters=$configuration->options['parameters']??[];
            if (!is_array($parameters)) throw new RuntimeException('Groq provider parameters must be a map.');
            return new OpenAILike('https://api.groq.com/openai/v1',$key,$model,$parameters);
        }
        return new OpenAI($key, $model);
    }

    public function test(ProviderDefinition $configuration): array
    {
        if ($configuration->adapter === 'ollama') {
            $this->make($configuration);
            return ['adapter'=>$configuration->adapter, 'model'=>$configuration->model, 'credentials'=>[]];
        }
        if (!in_array($configuration->adapter,['openai','groq'],true)) throw new RuntimeException(sprintf('AI provider adapter [%s] is not supported.', $configuration->adapter));
        $statuses = [];
        foreach ($configuration->credentials as $name => $reference) {
            $environment = substr($reference, 6);
            $value = $_ENV[$environment] ?? getenv($environment);
            if (!is_string($value) || $value === '') throw new RuntimeException(sprintf('AI provider environment variable [%s] is missing.', $environment));
            $statuses[$name] = 'available';
        }
        return ['adapter'=>$configuration->adapter, 'model'=>$configuration->model, 'credentials'=>$statuses];
    }
}
