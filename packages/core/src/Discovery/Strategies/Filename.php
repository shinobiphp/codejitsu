<?php

declare(strict_types=1);

namespace Codejitsu\Discovery\Strategies;

use Codejitsu\Contracts\Discovery\Strategy as StrategyContract;
use ReflectionClass;

final class Filename implements StrategyContract
{
    public function matches(ReflectionClass $reflection, array $params = []): bool
    {
        $pattern = $params['pattern'] ?? '/Scroll$/';
        $shortName = $reflection->getShortName();

        return (bool) preg_match($pattern, $shortName);
    }
}