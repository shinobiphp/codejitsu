<?php

declare(strict_types=1);

namespace Codejitsu\Discovery\Strategies;

use Codejitsu\Contracts\Discovery\Strategy as StrategyContract;
use ReflectionClass;

final class ImplementsInterface implements StrategyContract
{
    public function matches(ReflectionClass $reflection, array $params = []): bool
    {
        $targetInterface = $params['interface'] ?? null;

        if ($targetInterface === null || !interface_exists($targetInterface)) {
            return false;
        }

        return $reflection->implementsInterface($targetInterface);
    }
}