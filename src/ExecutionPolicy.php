<?php

declare(strict_types=1);

namespace Codejitsu;

final readonly class ExecutionPolicy
{
    public function __construct(
        public int $timeoutMilliseconds = 1000,
        public int $memoryBytes = 67108864,
        public array $filesystemRoots = [],
        public array $environment = [],
        public bool $allowNetwork = false,
        public bool $allowProcess = false,
    ) {
    }

    public static function defaults(): self
    {
        return new self();
    }
}
