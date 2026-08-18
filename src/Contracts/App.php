<?php

declare(strict_types=1);

namespace Codejitsu\Contracts;

use Codejitsu\Kernel\Kernel;

interface App
{
    /**
     * Get the multiton Kernel instance bound to this App context.
     */
    public Kernel $kernel { get; }

    /**
     * Run the application I/O lifecycle.
     *
     * @param mixed ...$args Runtime arguments (e.g., $argv for CLI or request parameters)
     */
    public function run(mixed ...$args): mixed;
}