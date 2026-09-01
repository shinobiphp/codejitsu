<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Tools\Context;

use Codejitsu\Context\ContextMemory;
use Codejitsu\ExecutionContext;
use RuntimeException;

final class ContextCapabilities
{
    public static function list(ExecutionContext $context): array
    {
        [$memory, $allowed] = self::scope($context);
        return array_values(array_filter($memory->list(), fn (array $item): bool => in_array($item['uri'], $allowed, true)));
    }

    public static function search(ExecutionContext $context): array
    {
        [$memory, $allowed] = self::scope($context);
        return array_values(array_filter($memory->search(self::argument($context, 'query')), fn (array $item): bool => in_array($item['uri'], $allowed, true)));
    }

    public static function show(ExecutionContext $context): string
    {
        [$memory] = self::scope($context);
        return $memory->show(self::allowedIdentifier($context, self::argument($context, 'name')));
    }

    public static function updateSection(ExecutionContext $context): string
    {
        [$memory] = self::scope($context);
        $name = self::allowedIdentifier($context, self::argument($context, 'name'));
        $memory->updateSection($name, self::argument($context, 'section'), self::argument($context, 'content'));
        return sprintf('Updated Context [%s].', $name);
    }

    private static function scope(ExecutionContext $context): array
    {
        if ($context->codex === null || !is_array($context->arguments)) throw new RuntimeException('Context Tool requires a bound Codex.');
        $policy = $context->arguments['_codejitsu'] ?? null;
        if (!is_array($policy) || !is_string($policy['contextRoot'] ?? null) || !is_array($policy['allowedContexts'] ?? null)) throw new RuntimeException('Context Tool policy is missing.');
        return [new ContextMemory($context->codex, $policy['contextRoot']), array_values($policy['allowedContexts'])];
    }

    private static function allowedIdentifier(ExecutionContext $context, string $identifier): string
    {
        [$memory, $allowed] = self::scope($context);
        foreach ($memory->list() as $item) {
            if (($item['name'] === $identifier || $item['uri'] === $identifier) && in_array($item['uri'], $allowed, true)) return $item['uri'];
        }
        throw new RuntimeException(sprintf('Context [%s] is outside the active policy.', $identifier));
    }

    private static function argument(ExecutionContext $context, string $name): string
    {
        $value = is_array($context->arguments) ? $context->arguments[$name] ?? null : null;
        if (!is_string($value) || trim($value) === '') throw new RuntimeException(sprintf('Context Tool argument [%s] is required.', $name));
        return trim($value);
    }
}
