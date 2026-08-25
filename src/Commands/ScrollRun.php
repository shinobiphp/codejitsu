<?php

declare(strict_types=1);

namespace Codejitsu\Commands;

use Codejitsu\ExecutionContext;
use Codejitsu\Contracts\Scrolls\Scroll as ScrollContract;
use InvalidArgumentException;
use LogicException;

final class ScrollRun
{
    public static function run(ExecutionContext $context): mixed
    {
        $arguments = is_array($context->arguments) ? array_values($context->arguments) : [$context->arguments];
        $uri = array_values(array_filter(
            $arguments,
            static fn (mixed $argument): bool => is_string($argument) && !str_starts_with($argument, '--'),
        ))[0] ?? null;

        if (!is_string($uri) || trim($uri) === '') {
            throw new InvalidArgumentException('A Scroll URI is required.');
        }

        $codex = $context->codex ?? throw new LogicException('Scroll execution requires a ScrollCodex.');
        $scroll = $codex->resolve($uri);

        if (!$scroll instanceof ScrollContract) {
            throw new LogicException(sprintf('Target [%s] did not resolve to a Scroll.', $uri));
        }

        $payload = array_values(array_filter(
            $arguments,
            static fn (mixed $argument): bool => $argument !== $uri && is_string($argument) && !str_starts_with($argument, '--'),
        ));

        return $scroll(...$payload);
    }
}
