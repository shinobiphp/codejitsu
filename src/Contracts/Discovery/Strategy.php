<?php

declare(strict_types=1);

namespace Codejitsu\Contracts\Discovery;

use ReflectionClass;

interface Strategy
{
    /**
     * @param ReflectionClass<object> $reflection
     * @param array<string, mixed> $params
     */
    public function matches(ReflectionClass $reflection, array $params = []): bool;
}