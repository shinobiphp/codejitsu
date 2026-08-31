<?php

declare(strict_types=1);

namespace Codejitsu\Contracts\Console;

use Codejitsu\Scrolls\Types\Command;

interface Driver
{
    /** @param iterable<Command> $commands */
    public function run(array $argv, iterable $commands, callable $execute): int;
}
