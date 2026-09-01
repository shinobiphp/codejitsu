<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Scaffolding;

use Codejitsu\Codecs\Neon;
use Codejitsu\Scrolls\TypeRegistry;
use RuntimeException;

final readonly class AiScaffolder
{
    public function __construct(private string $root, private TypeRegistry $types) {}

    public function spark(string $name, string $instructions): string
    {
        return $this->write('spark', $name, ['instructions' => $this->required($instructions, 'Spark instructions')]);
    }
    public function vessel(string $name, string $runtime, string $spark, string $provider): string
    {
        return $this->write('vessel', $name, ['runtime' => $this->required($runtime, 'Vessel runtime'), 'spark' => $this->required($spark, 'Vessel Spark'), 'provider'=>$this->required($provider, 'Vessel Provider')]);
    }
    public function skill(string $name, string $prompt, array $inputs = []): string
    {
        return $this->write('skill', $name, ['prompt' => $this->required($prompt, 'Skill prompt'), 'inputs' => $inputs]);
    }
    public function tool(string $name, string $description, string $capability, array $inputSchema): string
    {
        if ($inputSchema === []) throw new RuntimeException('Tool input schema cannot be empty.');
        return $this->write('tool', $name, ['description' => $this->required($description, 'Tool description'), 'capability' => $this->required($capability, 'Tool Capability'), 'inputSchema' => $inputSchema]);
    }
    public function toolset(string $name, array $tools, ?string $guidelines = null): string
    {
        if ($tools === []) throw new RuntimeException('Toolset Tools cannot be empty.');
        $data = ['tools' => array_values($tools)];
        if (is_string($guidelines) && trim($guidelines) !== '') $data['guidelines'] = trim($guidelines);
        return $this->write('toolset', $name, $data);
    }
    public function provider(string $name, string $adapter, string $model, string $apiKeyReference): string
    {
        return $this->write('provider', $name, ['adapter'=>$this->required($adapter, 'Provider adapter'), 'model'=>$this->required($model, 'Provider model'), 'credentials'=>['apiKey'=>$this->required($apiKeyReference, 'Provider API key reference')]]);
    }

    private function write(string $typeName, string $name, array $attributes): string
    {
        $name = strtolower(trim($name, " \t\n\r\0\x0B/"));
        if (preg_match('/^[a-z0-9][a-z0-9._-]*(?:\/[a-z0-9][a-z0-9._-]*)*$/', $name) !== 1 || str_contains($name, '..')) throw new RuntimeException('Invalid AI Scroll name.');
        $type = $this->types->get($typeName);
        $directory = rtrim($this->root, '/\\') . '/scrolls/' . $type->plural;
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) throw new RuntimeException('Unable to create Scroll directory.');
        $path = $directory . '/' . str_replace('/', '_', $name) . '.' . $type->extension;
        if (is_file($path)) throw new RuntimeException(sprintf('Scroll [%s://%s] already exists.', $typeName, $name));
        $content = (new Neon())->encode(['name' => $name, 'type' => $typeName, 'version' => '1.0.0', ...$attributes]);
        $temporary = tempnam($directory, '.scroll-');
        if ($temporary === false || file_put_contents($temporary, $content, LOCK_EX) === false || !rename($temporary, $path)) {
            if (is_string($temporary) && is_file($temporary)) @unlink($temporary);
            throw new RuntimeException('Unable to atomically create AI Scroll.');
        }
        return $path;
    }

    private function required(string $value, string $label): string
    {
        if (trim($value) === '') throw new RuntimeException($label . ' is required.');
        return trim($value);
    }
}
