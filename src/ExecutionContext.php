<?php

declare(strict_types=1);

namespace Codejitsu;

use Codejitsu\Scrolls\ScrollCodex;

final readonly class ExecutionContext
{
    public function __construct(
        public mixed $arguments,
        public ?ScrollCodex $codex = null,
    ) {
    }
}
