<?php

declare(strict_types=1);

namespace Codejitsu\Contracts;

use Closure;

interface Middleware
{
    public function process(
        mixed $passable,
        callable $next,
    ): mixed;
}