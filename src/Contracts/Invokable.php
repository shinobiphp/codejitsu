<?php

declare(strict_types=1);

namespace Codejitsu\Contracts;

interface Invokable
{
    public function __invoke(mixed ...$args): mixed;
}