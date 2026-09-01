<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Runtime;
use RuntimeException;
final class RuntimeRegistry
{
    private array $runtimes = [];
    public function register(string $name, AiRuntime $runtime): void
    {
        $name = strtolower(trim($name));
        if ($name === '') throw new RuntimeException('Runtime name cannot be empty.');
        $this->runtimes[$name] = $runtime;
    }
    public function get(string $name): AiRuntime
    {
        return $this->runtimes[strtolower(trim($name))] ?? throw new RuntimeException(sprintf('AI runtime [%s] is not registered.', $name));
    }
}
